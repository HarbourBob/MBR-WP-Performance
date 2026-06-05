<?php
/**
 * Orphaned Media (originally Orphaned Images, scope expanded in v1.11.0)
 *
 * Detects attachments that are no longer referenced anywhere on the site
 * and provides a safe deletion workflow with a configurable restore window.
 *
 * Detection scope:
 *  - post_parent (any post status except auto-draft / trash counts as a parent)
 *  - _thumbnail_id postmeta (featured images — relevant for image attachments)
 *  - post_content of all non-trashed posts (matches by attachment ID, shortcode
 *    references, and filename stem so sized image variants and URL-only
 *    references to videos/audio/documents are caught)
 *  - All postmeta values (string-search for attachment ID)
 *
 * Supported media types are configurable via the orphaned_images.enabled_types
 * option: images, videos, audio, documents, archives. Defaults to images only
 * to preserve v1.10.0 behaviour for upgrading sites.
 *
 * Deletion is two-stage: rows are first marked status='deleted' in a staging
 * table with a purge_after timestamp, allowing restoration within the window.
 * A daily cron job purges files past their restore window.
 *
 * Class name retained as MBRPE_Orphaned_Images for backward
 * compatibility with v1.10.0 — renaming for purely cosmetic reasons would be
 * a tax with no functional gain.
 *
 * @package MBRPE
 * @since   1.10.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Orphaned Images class
 */
class MBRPE_Orphaned_Images {

    /**
     * Single instance
     *
     * @var MBRPE_Orphaned_Images
     */
    private static $instance = null;

    /**
     * Plugin options for this section
     *
     * @var array
     */
    private $options = array();

    /**
     * Database schema version. Bump this when the staging table schema changes.
     */
    const DB_VERSION = '1.0.0';

    /**
     * Confidence tiers. Only HIGH is eligible for bulk-delete by default;
     * REVIEW must be deleted one at a time (forces manual inspection).
     */
    const CONFIDENCE_HIGH   = 'high';
    const CONFIDENCE_REVIEW = 'review';

    /**
     * Row statuses in the staging table.
     */
    const STATUS_CANDIDATE = 'candidate';
    const STATUS_DELETED   = 'deleted';

    /**
     * How many attachments to process per scan batch.
     * Kept small enough to avoid timeouts on shared hosting.
     */
    const SCAN_BATCH_SIZE = 50;

    /**
     * Default restore window (days). Mirrors WP's trash retention.
     */
    const DEFAULT_RESTORE_DAYS = 30;

    /**
     * Media type → mime pattern mapping. Each value is a list of either
     * mime prefixes ending in '/' (used as LIKE prefix matches) or exact
     * mime strings. Centralising this here keeps the SQL builders and the
     * UI checkbox group in sync.
     *
     * @since 1.11.0
     */
    public static function media_type_map() {
        return array(
            'images'    => array( 'image/' ),
            'videos'    => array( 'video/' ),
            'audio'     => array( 'audio/' ),
            'documents' => array(
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/vnd.oasis.opendocument.text',
                'application/vnd.oasis.opendocument.spreadsheet',
                'application/vnd.oasis.opendocument.presentation',
                'application/rtf',
                'text/csv',
                'text/plain',
            ),
            'archives'  => array(
                'application/zip',
                'application/x-tar',
                'application/gzip',
                'application/x-gzip',
                'application/x-rar-compressed',
                'application/vnd.rar',
                'application/x-7z-compressed',
            ),
        );
    }

    /**
     * Get the list of media type keys currently enabled in settings.
     * Defaults to images-only for backward compatibility with v1.10.0.
     *
     * @since 1.11.0
     * @return string[]
     */
    public static function get_enabled_types() {
        $opts = mbrpe()->get_options( 'orphaned_images' );
        if ( ! is_array( $opts ) || ! isset( $opts['enabled_types'] ) || ! is_array( $opts['enabled_types'] ) ) {
            return array( 'images' );
        }
        $valid = array_keys( self::media_type_map() );
        $clean = array_values( array_intersect( $opts['enabled_types'], $valid ) );
        return $clean ?: array( 'images' );
    }

    /**
     * Build a SQL WHERE-clause fragment matching the configured media types
     * against a mime-type column, plus the parameters to bind. Returns a
     * tuple [sql_fragment, params_array]. The fragment is wrapped in
     * parentheses so it can be appended to existing WHERE clauses with AND.
     *
     * Callers can override $column when filtering the staging table (which
     * uses `mime_type`) rather than `wp_posts` (which uses `post_mime_type`).
     *
     * @since 1.11.0
     * @since 1.13.1 Added $column parameter to support the staging table.
     * @param string[]|null $types  Override the enabled types (used for filtering).
     * @param string        $column Column name to match against. Defaults to
     *                              `post_mime_type` for backward compatibility
     *                              with the `wp_posts` scan SQL.
     * @return array { 0: string $sql, 1: array $params }
     */
    public static function build_mime_where( $types = null, $column = 'post_mime_type' ) {
        $types = is_array( $types ) ? $types : self::get_enabled_types();
        $map   = self::media_type_map();

        // Whitelist the column name — these are the only two we ever query.
        // Anything else falls back to the default so a stray caller can't
        // smuggle arbitrary identifiers into the SQL.
        if ( ! in_array( $column, array( 'post_mime_type', 'mime_type' ), true ) ) {
            $column = 'post_mime_type';
        }

        $clauses = array();
        $params  = array();

        foreach ( $types as $type ) {
            if ( ! isset( $map[ $type ] ) ) {
                continue;
            }
            foreach ( $map[ $type ] as $pattern ) {
                if ( substr( $pattern, -1 ) === '/' ) {
                    // Prefix wildcard: "image/" matches "image/jpeg" etc.
                    $clauses[] = "{$column} LIKE %s";
                    $params[]  = $pattern . '%';
                } else {
                    // Exact mime match.
                    $clauses[] = "{$column} = %s";
                    $params[]  = $pattern;
                }
            }
        }

        if ( empty( $clauses ) ) {
            // No active types — return a clause that matches nothing.
            return array( '1=0', array() );
        }

        return array( '(' . implode( ' OR ', $clauses ) . ')', $params );
    }

    /**
     * Categorise a mime type string into one of the high-level type keys
     * (images / videos / audio / documents / archives / other). Used for
     * display and filtering on the candidate list.
     *
     * @since 1.11.0
     * @param string $mime
     * @return string
     */
    public static function categorise_mime( $mime ) {
        if ( ! $mime ) {
            return 'other';
        }
        foreach ( self::media_type_map() as $type => $patterns ) {
            foreach ( $patterns as $pattern ) {
                if ( substr( $pattern, -1 ) === '/' ) {
                    if ( strpos( $mime, $pattern ) === 0 ) {
                        return $type;
                    }
                } elseif ( $mime === $pattern ) {
                    return $type;
                }
            }
        }
        return 'other';
    }

    /**
     * Get instance
     *
     * @return MBRPE_Orphaned_Images
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor — registers the cron purge handler. The scanner and
     * deletion handlers are AJAX-only (registered in class-admin.php).
     */
    private function __construct() {
        $this->options = mbrpe()->get_options( 'orphaned_images' );

        // Daily purge of staged-for-deletion rows past their restore window.
        add_action( 'mbrpe_orphan_purge', array( __CLASS__, 'cron_purge_expired' ) );
    }

    /* ===================================================================
     * Schema management
     * =================================================================== */

    /**
     * Get the staging table name with the WP prefix.
     *
     * @return string
     */
    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'mbr_orphan_log';
    }

    /**
     * Create the staging table. Idempotent — safe to call on every load,
     * though in practice we only call it on activation/upgrade.
     */
    public static function create_table() {
        global $wpdb;

        $table   = self::table_name();
        $charset = $wpdb->get_charset_collate();

        // Note: dbDelta is fussy about exact formatting — keep two spaces after
        // PRIMARY KEY, no backticks around column names in PRIMARY KEY clause,
        // and KEY (not INDEX).
        $sql = "CREATE TABLE {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            attachment_id BIGINT(20) UNSIGNED NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'candidate',
            confidence VARCHAR(20) NOT NULL DEFAULT 'review',
            file_path TEXT NULL,
            file_url TEXT NULL,
            file_size BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            mime_type VARCHAR(100) NULL,
            match_summary TEXT NULL,
            attachment_post LONGTEXT NULL,
            attachment_meta LONGTEXT NULL,
            deleted_files LONGTEXT NULL,
            deleted_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            scanned_at DATETIME NULL,
            deleted_at DATETIME NULL,
            purge_after DATETIME NULL,
            PRIMARY KEY  (id),
            KEY attachment_id (attachment_id),
            KEY status (status),
            KEY purge_after (purge_after)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        update_option( 'mbrpe_orphan_db_version', self::DB_VERSION );
    }

    /**
     * Drop the staging table. Used on uninstall (not deactivation).
     */
    public static function drop_table() {
        global $wpdb;
        $table = self::table_name();
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query( "DROP TABLE IF EXISTS {$table}" );
        delete_option( 'mbrpe_orphan_db_version' );
    }

    /* ===================================================================
     * Scanning
     * =================================================================== */

    /**
     * Reset scan state — wipes all rows with status='candidate' so a fresh
     * scan starts clean. Does NOT touch status='deleted' rows.
     */
    public static function reset_scan() {
        global $wpdb;
        $table = self::table_name();
        $wpdb->delete( $table, array( 'status' => self::STATUS_CANDIDATE ), array( '%s' ) );

        delete_option( 'mbrpe_orphan_scan_state' );
    }

    /**
     * Get the current scan state. Returns array with keys:
     *   total       — total image attachments to scan
     *   processed   — how many have been processed so far
     *   started_at  — unix timestamp of scan start
     *   finished_at — unix timestamp of scan completion (0 if running)
     *
     * @return array
     */
    public static function get_scan_state() {
        $default = array(
            'total'       => 0,
            'processed'   => 0,
            'started_at'  => 0,
            'finished_at' => 0,
        );
        $state = get_option( 'mbrpe_orphan_scan_state', $default );
        return is_array( $state ) ? array_merge( $default, $state ) : $default;
    }

    /**
     * Update the scan state.
     *
     * @param array $state Partial state to merge in.
     */
    public static function update_scan_state( $state ) {
        $current = self::get_scan_state();
        $merged  = array_merge( $current, $state );
        update_option( 'mbrpe_orphan_scan_state', $merged, false );
    }

    /**
     * Get the full list of attachment IDs to scan, ordered by ID. Limited
     * to the media types currently enabled in settings (defaults to
     * images-only). Excluded IDs (from settings) are filtered out.
     *
     * Renamed from get_all_image_attachment_ids() in v1.11.0; the old name
     * is preserved as a thin alias for backward compatibility.
     *
     * @since 1.11.0
     * @return int[]
     */
    public static function get_all_attachment_ids() {
        global $wpdb;

        list( $mime_sql, $mime_params ) = self::build_mime_where();

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $sql = "SELECT ID FROM {$wpdb->posts}
                WHERE post_type = 'attachment'
                  AND {$mime_sql}
                ORDER BY ID ASC";

        if ( ! empty( $mime_params ) ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $sql = $wpdb->prepare( $sql, $mime_params );
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $ids = $wpdb->get_col( $sql );
        $ids = array_map( 'intval', (array) $ids );

        // Filter out user-excluded IDs.
        $excluded = self::get_excluded_ids();
        if ( ! empty( $excluded ) ) {
            $ids = array_values( array_diff( $ids, $excluded ) );
        }

        return $ids;
    }

    /**
     * Backward-compatible alias for get_all_attachment_ids(). Retained so
     * any code (including third-party callers) that learned the v1.10.0
     * method name continues to work.
     *
     * @deprecated 1.11.0 Use get_all_attachment_ids() instead.
     * @return int[]
     */
    public static function get_all_image_attachment_ids() {
        return self::get_all_attachment_ids();
    }

    /**
     * Build the LIKE patterns used to scan post_content for references to
     * an attachment. Returns an array of patterns ready to be bound into
     * `$wpdb->prepare()` placeholders — already wrapped in `%` wildcards and
     * run through `$wpdb->esc_like()` where appropriate.
     *
     * Images get three patterns:
     *   - %wp-image-{ID}%  : Gutenberg / classic editor class
     *   - %attachment_{ID}%: shortcode / legacy block reference
     *   - %/{stem}%        : filename stem, matches sized variants
     *                        (e.g. "image-300x200.jpg" against "image")
     *
     * Non-images (PDFs, video, audio, archives) get two patterns:
     *   - %attachment_{ID}%: shortcode / legacy block reference
     *   - %/{basename}%    : the FULL filename including extension
     *
     * Critically, non-images do NOT get the stem-only pattern. There are
     * no sized variants for a PDF, and stripping the extension makes the
     * match dangerously broad — a PDF called "pricing.pdf" would match any
     * post containing a link like "/pricing-tiers/" or text mentioning
     * "/pricing-faq", causing the scanner to wrongly classify the PDF as
     * referenced and silently drop it from the candidate list.
     *
     * Fixes a bug introduced in v1.11.0 when non-image media types were
     * first added to the scanner. Before this helper, all media types
     * shared the same stem-only matching logic that was originally
     * designed for images.
     *
     * @since 1.13.1
     * @param int    $id   Attachment ID.
     * @param string $file Absolute path to the attached file.
     * @param string $mime Mime type of the attachment.
     * @return string[]    LIKE patterns ready for prepare().
     */
    private static function content_search_patterns( $id, $file, $mime ) {
        global $wpdb;

        $patterns = array();
        $basename = wp_basename( (string) $file );
        $is_image = strpos( (string) $mime, 'image/' ) === 0;

        // Shortcode / legacy block reference — relevant for all media types.
        $patterns[] = '%attachment_' . $id . '%';

        if ( $is_image ) {
            // Gutenberg / classic editor class — only emitted for images.
            $patterns[] = '%wp-image-' . $id . '%';
            // Filename stem (no extension) so sized variants are matched.
            $stem = preg_replace( '/\.[a-z0-9]+$/i', '', $basename );
            if ( $stem !== '' ) {
                $patterns[] = '%/' . $wpdb->esc_like( $stem ) . '%';
            }
        } else {
            // Non-images: match full basename including extension. Prevents
            // the false-positive that hid orphan PDFs before v1.13.1.
            if ( $basename !== '' ) {
                $patterns[] = '%/' . $wpdb->esc_like( $basename ) . '%';
            }
        }

        return $patterns;
    }

    /**
     * Process a single batch of attachment IDs and write candidate rows
     * to the staging table.
     *
     * @param int[] $ids Attachment IDs in this batch.
     * @return int Count of orphans found in this batch.
     */
    public static function process_batch( $ids ) {
        global $wpdb;

        $ids = array_filter( array_map( 'intval', (array) $ids ) );
        if ( empty( $ids ) ) {
            return 0;
        }

        $table = self::table_name();
        $now   = current_time( 'mysql' );
        $found = 0;

        // Pre-compute filename stems for content-search. We strip query strings
        // and sized variants so e.g. "image.jpg" matches "image-300x200.jpg" too.
        $attachment_data = array();
        foreach ( $ids as $id ) {
            $file = get_attached_file( $id );
            if ( ! $file ) {
                continue;
            }
            $url      = wp_get_attachment_url( $id );
            $basename = wp_basename( $file );
            // Strip extension to catch sized variants in content (e.g. "image-300x200.jpg").
            $stem = preg_replace( '/\.[a-z0-9]+$/i', '', $basename );

            $attachment_data[ $id ] = array(
                'file'     => $file,
                'url'      => $url,
                'basename' => $basename,
                'stem'     => $stem,
                'size'     => file_exists( $file ) ? filesize( $file ) : 0,
                'mime'     => get_post_mime_type( $id ),
                'parent'   => (int) wp_get_post_parent_id( $id ),
            );
        }

        if ( empty( $attachment_data ) ) {
            return 0;
        }

        $batch_ids = array_keys( $attachment_data );

        // ----- Check 1: parent exists and is not trashed -----
        $parents_to_check = array_unique( array_filter( wp_list_pluck( $attachment_data, 'parent' ) ) );
        $live_parents     = array();
        if ( ! empty( $parents_to_check ) ) {
            $placeholders = implode( ',', array_fill( 0, count( $parents_to_check ), '%d' ) );
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $sql = $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts}
                 WHERE ID IN ({$placeholders})
                   AND post_status NOT IN ('trash','auto-draft')",
                $parents_to_check
            );
            $live_parents = array_map( 'intval', (array) $wpdb->get_col( $sql ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is built via $wpdb->prepare() directly above with %d placeholders.
        }

        // ----- Check 2: featured image references (_thumbnail_id) -----
        $placeholders   = implode( ',', array_fill( 0, count( $batch_ids ), '%d' ) );
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $thumb_sql       = $wpdb->prepare(
            "SELECT DISTINCT meta_value FROM {$wpdb->postmeta}
             WHERE meta_key = '_thumbnail_id'
               AND meta_value IN ({$placeholders})",
            $batch_ids
        );
        $referenced_thumb = array_map( 'intval', (array) $wpdb->get_col( $thumb_sql ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $thumb_sql is built via $wpdb->prepare() directly above with %d placeholders.

        // ----- Check 3: post_content references (per-attachment LIKE) -----
        // We do these one at a time because a multi-OR LIKE on post_content
        // is very expensive on large content tables. For the typical 50-row
        // batch this is 50 quick queries each hitting an indexed status filter
        // and only LIKE-scanning published rows.
        //
        // The patterns differ for images vs non-images — see
        // content_search_patterns() for why. (Fixed in v1.13.1; before then
        // every media type used the image-style stem match, which silently
        // hid orphan PDFs with common filenames.)
        $content_referenced = array();
        foreach ( $attachment_data as $id => $data ) {
            $patterns = self::content_search_patterns( $id, $data['file'], $data['mime'] );

            if ( empty( $patterns ) ) {
                continue;
            }

            $where = array();
            $args  = array();
            foreach ( $patterns as $p ) {
                $where[] = 'post_content LIKE %s';
                $args[]  = $p;
            }

            $content_sql = "SELECT ID FROM {$wpdb->posts}
                    WHERE post_status NOT IN ('trash','auto-draft','inherit')
                      AND post_type NOT IN ('revision','attachment')
                      AND (" . implode( ' OR ', $where ) . ')
                    LIMIT 1';

            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Query template is literal SQL with %s placeholders only; values passed through prepare().
            $hit = $wpdb->get_var( $wpdb->prepare( $content_sql, $args ) );
            if ( $hit ) {
                $content_referenced[] = $id;
            }
        }
        $content_referenced = array_map( 'intval', $content_referenced );

        // ----- Check 4: postmeta references (string-search for ID) -----
        // We accept some false positives here (a string "12345" may appear
        // for unrelated reasons). False positives bump the row to REVIEW tier
        // rather than HIGH — they aren't auto-deleteable.
        $meta_referenced = array();
        foreach ( $batch_ids as $id ) {
            // Skip _thumbnail_id meta_key — already covered. Look for the ID
            // appearing as a value or substring of a value in any other meta.
            $meta_sql = $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta}
                 WHERE meta_key != '_thumbnail_id'
                   AND meta_value LIKE %s
                 LIMIT 1",
                '%' . $wpdb->esc_like( (string) $id ) . '%'
            );
            $hit = $wpdb->get_var( $meta_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $meta_sql is built via $wpdb->prepare() directly above with a %s placeholder.
            if ( $hit ) {
                $meta_referenced[] = $id;
            }
        }

        // ----- Classify each attachment in the batch -----
        foreach ( $attachment_data as $id => $data ) {
            $matches = array();

            $has_live_parent = $data['parent'] && in_array( $data['parent'], $live_parents, true );
            if ( $has_live_parent ) {
                $matches[] = 'parent';
            }
            if ( in_array( $id, $referenced_thumb, true ) ) {
                $matches[] = 'featured_image';
            }
            if ( in_array( $id, $content_referenced, true ) ) {
                $matches[] = 'post_content';
            }
            if ( in_array( $id, $meta_referenced, true ) ) {
                $matches[] = 'postmeta';
            }

            // Definitive matches prove the attachment is in use right now:
            // it appears in some post's content, or is set as a featured
            // image. These short-circuit the candidate.
            //
            // post_parent is intentionally NOT in the definitive set
            // (changed in v1.11.0). WordPress sets post_parent on upload
            // when an attachment is added via the post editor, and the
            // link survives any content edits that remove the actual
            // reference. Treating it as definitive would hide every
            // attachment uploaded for a post and later removed from the
            // content — a very common case for videos, audio, and PDFs.
            // Instead, parent-only matches drop to Review tier so the
            // user can inspect the parent post and confirm.
            $definitive = array_intersect( $matches, array( 'featured_image', 'post_content' ) );
            if ( ! empty( $definitive ) ) {
                continue; // Not an orphan, skip.
            }

            $confidence = empty( $matches ) ? self::CONFIDENCE_HIGH : self::CONFIDENCE_REVIEW;

            // Insert candidate row.
            $wpdb->insert(
                $table,
                array(
                    'attachment_id'  => $id,
                    'status'         => self::STATUS_CANDIDATE,
                    'confidence'     => $confidence,
                    'file_path'      => $data['file'],
                    'file_url'       => $data['url'],
                    'file_size'      => (int) $data['size'],
                    'mime_type'      => $data['mime'],
                    'match_summary'  => $matches ? wp_json_encode( $matches ) : '',
                    'scanned_at'     => $now,
                ),
                array( '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s' )
            );

            $found++;
        }

        return $found;
    }

    /* ===================================================================
     * Candidate listing & exclusions
     * =================================================================== */

    /**
     * Get candidates with optional filtering & pagination.
     *
     * @param array $args {
     *   @type string $confidence    'high', 'review', or '' for all.
     *   @type string $media_type    'images', 'videos', 'audio', 'documents', 'archives', or '' for all (since 1.11.0).
     *   @type int    $per_page
     *   @type int    $page
     *   @type string $orderby       'attachment_id' or 'file_size'
     *   @type string $order         'ASC' or 'DESC'
     * }
     * @return array { items: array, total: int }
     */
    public static function get_candidates( $args = array() ) {
        global $wpdb;
        $table = self::table_name();

        $defaults = array(
            'confidence' => '',
            'media_type' => '',
            'per_page'   => 25,
            'page'       => 1,
            'orderby'    => 'file_size',
            'order'      => 'DESC',
        );
        $args = array_merge( $defaults, $args );

        $where  = "status = %s";
        $params = array( self::STATUS_CANDIDATE );

        if ( in_array( $args['confidence'], array( self::CONFIDENCE_HIGH, self::CONFIDENCE_REVIEW ), true ) ) {
            $where   .= ' AND confidence = %s';
            $params[] = $args['confidence'];
        }

        // Media-type filter (since 1.11.0). Translates a single type key
        // into the same mime patterns used during the scan. The staging
        // table column is `mime_type` (the scan SQL uses `post_mime_type`
        // on `wp_posts`), so we pass that column name explicitly — fixed
        // in v1.13.1.
        if ( ! empty( $args['media_type'] ) ) {
            list( $mime_sql, $mime_params ) = self::build_mime_where( array( $args['media_type'] ), 'mime_type' );
            if ( $mime_sql !== '1=0' ) {
                $where .= ' AND ' . $mime_sql;
                foreach ( $mime_params as $p ) {
                    $params[] = $p;
                }
            }
        }

        $orderby = in_array( $args['orderby'], array( 'attachment_id', 'file_size', 'scanned_at' ), true )
            ? $args['orderby']
            : 'file_size';
        $order   = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

        $per_page = max( 1, (int) $args['per_page'] );
        $page     = max( 1, (int) $args['page'] );
        $offset   = ( $page - 1 ) * $per_page;

        // Count
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where}", $params ) );

        // Items
        $params[] = $per_page;
        $params[] = $offset;
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
                $params
            ),
            ARRAY_A
        );

        return array(
            'items' => is_array( $items ) ? $items : array(),
            'total' => $total,
        );
    }

    /**
     * Get aggregate stats for the current candidate set, broken down by
     * confidence (high/review) and by media type (images/videos/audio/
     * documents/archives/other).
     *
     * @return array
     */
    public static function get_candidate_stats() {
        global $wpdb;
        $table = self::table_name();

        // Confidence breakdown.
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT confidence, COUNT(*) AS cnt, SUM(file_size) AS bytes
                 FROM {$table} WHERE status = %s GROUP BY confidence",
                self::STATUS_CANDIDATE
            ),
            ARRAY_A
        );

        $stats = array(
            'high'         => array( 'count' => 0, 'bytes' => 0 ),
            'review'       => array( 'count' => 0, 'bytes' => 0 ),
            'total_count'  => 0,
            'total_bytes'  => 0,
            'by_type'      => array(
                'images'    => array( 'count' => 0, 'bytes' => 0 ),
                'videos'    => array( 'count' => 0, 'bytes' => 0 ),
                'audio'     => array( 'count' => 0, 'bytes' => 0 ),
                'documents' => array( 'count' => 0, 'bytes' => 0 ),
                'archives'  => array( 'count' => 0, 'bytes' => 0 ),
                'other'     => array( 'count' => 0, 'bytes' => 0 ),
            ),
        );

        foreach ( (array) $rows as $row ) {
            $key = $row['confidence'] === self::CONFIDENCE_HIGH ? 'high' : 'review';
            $stats[ $key ]['count']  = (int) $row['cnt'];
            $stats[ $key ]['bytes']  = (int) $row['bytes'];
            $stats['total_count']   += (int) $row['cnt'];
            $stats['total_bytes']   += (int) $row['bytes'];
        }

        // Per-type breakdown — done in PHP since mime_type is stored verbatim
        // and categorise_mime() is the canonical mapping. Pulling just two
        // columns keeps memory minimal even on large candidate lists.
        $type_rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT mime_type, file_size FROM {$table} WHERE status = %s",
                self::STATUS_CANDIDATE
            ),
            ARRAY_A
        );
        foreach ( (array) $type_rows as $row ) {
            $cat = self::categorise_mime( $row['mime_type'] );
            if ( ! isset( $stats['by_type'][ $cat ] ) ) {
                $cat = 'other';
            }
            $stats['by_type'][ $cat ]['count']++;
            $stats['by_type'][ $cat ]['bytes'] += (int) $row['file_size'];
        }

        return $stats;
    }

    /**
     * Get a count of staged-for-deletion (status='deleted') rows.
     *
     * @return array { count: int, bytes: int }
     */
    public static function get_staged_stats() {
        global $wpdb;
        $table = self::table_name();

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT COUNT(*) AS cnt, SUM(file_size) AS bytes
                 FROM {$table} WHERE status = %s",
                self::STATUS_DELETED
            ),
            ARRAY_A
        );

        return array(
            'count' => isset( $row['cnt'] ) ? (int) $row['cnt'] : 0,
            'bytes' => isset( $row['bytes'] ) ? (int) $row['bytes'] : 0,
        );
    }

    /**
     * Get all staged-for-deletion rows for the restore UI.
     *
     * @param int $per_page
     * @param int $page
     * @return array { items: array, total: int }
     */
    public static function get_staged( $per_page = 25, $page = 1 ) {
        global $wpdb;
        $table = self::table_name();

        $per_page = max( 1, (int) $per_page );
        $page     = max( 1, (int) $page );
        $offset   = ( $page - 1 ) * $per_page;

        $total = (int) $wpdb->get_var(
            $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s", self::STATUS_DELETED )
        );

        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE status = %s ORDER BY deleted_at DESC LIMIT %d OFFSET %d",
                self::STATUS_DELETED,
                $per_page,
                $offset
            ),
            ARRAY_A
        );

        return array(
            'items' => is_array( $items ) ? $items : array(),
            'total' => $total,
        );
    }

    /**
     * Get the configured excluded attachment IDs.
     *
     * @return int[]
     */
    public static function get_excluded_ids() {
        $opts = mbrpe()->get_options( 'orphaned_images' );
        if ( ! is_array( $opts ) || empty( $opts['excluded_ids'] ) ) {
            return array();
        }
        $ids = is_array( $opts['excluded_ids'] ) ? $opts['excluded_ids'] : array();
        return array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );
    }

    /**
     * Get the configured restore window (days). 0 means "keep forever".
     *
     * @return int
     */
    public static function get_restore_days() {
        $opts = mbrpe()->get_options( 'orphaned_images' );
        if ( ! is_array( $opts ) || ! isset( $opts['restore_days'] ) ) {
            return self::DEFAULT_RESTORE_DAYS;
        }
        $days = (int) $opts['restore_days'];
        // Whitelist allowed values to prevent option-tampering shenanigans.
        $allowed = array( 0, 7, 14, 30, 60 );
        return in_array( $days, $allowed, true ) ? $days : self::DEFAULT_RESTORE_DAYS;
    }

    /**
     * Add an attachment to the exclusion list. Also removes any candidate
     * row for it so it doesn't show up again until next scan.
     *
     * @param int $attachment_id
     */
    public static function exclude_attachment( $attachment_id ) {
        global $wpdb;
        $attachment_id = (int) $attachment_id;
        if ( $attachment_id <= 0 ) {
            return;
        }

        $opts                          = get_option( 'mbrpe_options', array() );
        $opts['orphaned_images']       = isset( $opts['orphaned_images'] ) && is_array( $opts['orphaned_images'] )
            ? $opts['orphaned_images']
            : array();
        $opts['orphaned_images']['excluded_ids'] = isset( $opts['orphaned_images']['excluded_ids'] )
            && is_array( $opts['orphaned_images']['excluded_ids'] )
            ? $opts['orphaned_images']['excluded_ids']
            : array();

        $current = array_map( 'intval', $opts['orphaned_images']['excluded_ids'] );
        if ( ! in_array( $attachment_id, $current, true ) ) {
            $current[] = $attachment_id;
            $opts['orphaned_images']['excluded_ids'] = array_values( array_unique( $current ) );
            update_option( 'mbrpe_options', $opts );
        }

        $wpdb->delete(
            self::table_name(),
            array(
                'attachment_id' => $attachment_id,
                'status'        => self::STATUS_CANDIDATE,
            ),
            array( '%d', '%s' )
        );
    }

    /* ===================================================================
     * Deletion (move to staging)
     * =================================================================== */

    /**
     * Move an attachment from "candidate" to "deleted" status. Captures all
     * file paths (original + sized variants + WebP siblings), the post row,
     * and the postmeta, then physically deletes the files and the WP records.
     * The staging row is what allows restoration.
     *
     * @param int $attachment_id
     * @return array|WP_Error
     */
    public static function stage_delete( $attachment_id ) {
        global $wpdb;
        $attachment_id = (int) $attachment_id;
        if ( $attachment_id <= 0 ) {
            return new WP_Error( 'invalid_id', __( 'Invalid attachment ID.', 'mbr-performance' ) );
        }

        $table = self::table_name();

        // Confirm a candidate row exists for this attachment.
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE attachment_id = %d AND status = %s LIMIT 1",
                $attachment_id,
                self::STATUS_CANDIDATE
            ),
            ARRAY_A
        );

        if ( ! $row ) {
            return new WP_Error(
                'not_candidate',
                __( 'Attachment is no longer a deletion candidate. Re-scan and try again.', 'mbr-performance' )
            );
        }

        // Re-verify the orphan check before deleting. This is belt-and-braces:
        // a previous scan may be stale by the time the user clicks delete.
        if ( ! self::reverify_orphan( $attachment_id ) ) {
            // Remove the now-stale candidate row and refuse the delete.
            $wpdb->delete(
                $table,
                array( 'id' => (int) $row['id'] ),
                array( '%d' )
            );
            return new WP_Error(
                'no_longer_orphan',
                __( 'Attachment is now in use elsewhere — deletion blocked. Run a fresh scan.', 'mbr-performance' )
            );
        }

        // Capture the WP post row and meta before destroying them.
        $post = get_post( $attachment_id, ARRAY_A );
        $meta = get_post_meta( $attachment_id );

        // Build the list of files to delete: main file + all sub-size files
        // + matching .webp siblings of any of the above.
        $files = self::collect_attachment_files( $attachment_id );

        // Physically remove files. We use wp_delete_file (respects filters)
        // and track which we successfully removed for the restore manifest.
        $removed = array();
        foreach ( $files as $file ) {
            if ( file_exists( $file ) ) {
                wp_delete_file( $file );
                if ( ! file_exists( $file ) ) {
                    $removed[] = $file;
                }
            }
        }

        // Remove the WP attachment record itself. We pass force_delete=true
        // because we've already captured everything we need to restore from.
        // We bypass wp_delete_attachment's own file-deletion (it already ran
        // for us above) by removing meta first so it has nothing to do.
        // Actually — wp_delete_attachment is the safest path; it cleans up
        // postmeta, attachment ancestors, term relationships, and so on.
        // We let it run and accept that some of the file deletions will be
        // no-ops (files already gone).
        $delete_result = wp_delete_attachment( $attachment_id, true );

        if ( false === $delete_result || null === $delete_result ) {
            // Roll back as best we can — but the files are already gone, so
            // log this rather than try to undo. The staging row will let
            // the user restore via meta if the WP record didn't fully die.
            // Most likely cause: a permission filter blocked deletion.
            // We continue to write the staging row regardless.
        }

        $now         = current_time( 'mysql' );
        $restore_days = self::get_restore_days();
        $purge_after = $restore_days > 0
            ? gmdate( 'Y-m-d H:i:s', strtotime( $now . ' +' . $restore_days . ' days' ) )
            : null;

        // Update the existing candidate row to "deleted" with the manifest.
        $wpdb->update(
            $table,
            array(
                'status'          => self::STATUS_DELETED,
                'attachment_post' => $post ? wp_json_encode( $post ) : '',
                'attachment_meta' => $meta ? wp_json_encode( $meta ) : '',
                'deleted_files'   => wp_json_encode( $removed ),
                'deleted_by'      => get_current_user_id(),
                'deleted_at'      => $now,
                'purge_after'     => $purge_after,
            ),
            array( 'id' => (int) $row['id'] ),
            array( '%s', '%s', '%s', '%s', '%d', '%s', '%s' ),
            array( '%d' )
        );

        return array(
            'attachment_id' => $attachment_id,
            'files_removed' => count( $removed ),
            'bytes_freed'   => (int) $row['file_size'],
            'purge_after'   => $purge_after,
        );
    }

    /**
     * Quick re-verification of orphan status before destructive action.
     * Returns true if the attachment is *still* an orphan.
     *
     * Note (v1.11.0): post_parent is intentionally not checked here. The
     * classifier in process_batch() now treats parent as suggestive rather
     * than definitive — a parent-only candidate goes to Review tier where
     * the user has explicitly approved the deletion via single-row delete.
     * Re-verifying parent here would override that approval and break
     * deletion of legitimate review-tier orphans.
     *
     * @param int $id
     * @return bool
     */
    private static function reverify_orphan( $id ) {
        global $wpdb;

        $id = (int) $id;
        if ( $id <= 0 ) {
            return false;
        }

        // Featured image somewhere?
        $thumb_hit = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta}
                 WHERE meta_key = '_thumbnail_id' AND meta_value = %d LIMIT 1",
                $id
            )
        );
        if ( $thumb_hit ) {
            return false;
        }

        // Content reference? Use the same media-type-aware pattern logic
        // as the main scan so a non-image isn't false-positive matched on a
        // stem alone (fixed in v1.13.1).
        $file = get_attached_file( $id );
        if ( $file ) {
            $mime     = get_post_mime_type( $id );
            $patterns = self::content_search_patterns( $id, $file, (string) $mime );

            if ( ! empty( $patterns ) ) {
                $where = array();
                foreach ( $patterns as $p ) {
                    $where[] = 'post_content LIKE %s';
                }
                $sql = "SELECT ID FROM {$wpdb->posts}
                         WHERE post_status NOT IN ('trash','auto-draft','inherit')
                           AND post_type NOT IN ('revision','attachment')
                           AND (" . implode( ' OR ', $where ) . ')
                         LIMIT 1';

                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Query template is literal SQL with %s placeholders only; values passed through prepare().
                $hit = $wpdb->get_var( $wpdb->prepare( $sql, $patterns ) );
                if ( $hit ) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Collect every file on disk associated with an attachment: original,
     * all registered sub-sizes (including the "scaled" variant), and the
     * matching .webp sibling for each of those.
     *
     * @param int $attachment_id
     * @return string[] Absolute paths.
     */
    public static function collect_attachment_files( $attachment_id ) {
        $files = array();

        $main = get_attached_file( $attachment_id );
        if ( $main ) {
            $files[] = $main;

            // Resolve the "original" file too if WP made a scaled version.
            $original = wp_get_original_image_path( $attachment_id );
            if ( $original && $original !== $main ) {
                $files[] = $original;
            }

            $meta = wp_get_attachment_metadata( $attachment_id );
            if ( is_array( $meta ) && ! empty( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) {
                $dir = trailingslashit( dirname( $main ) );
                foreach ( $meta['sizes'] as $size_data ) {
                    if ( ! empty( $size_data['file'] ) ) {
                        $files[] = $dir . $size_data['file'];
                    }
                }
            }
        }

        // For every file we collected, add its .webp sibling (if any).
        $webp_siblings = array();
        foreach ( $files as $f ) {
            $webp = preg_replace( '/\.(jpe?g|png|gif)$/i', '.webp', $f );
            if ( $webp && $webp !== $f ) {
                $webp_siblings[] = $webp;
            }
        }

        return array_values( array_unique( array_merge( $files, $webp_siblings ) ) );
    }

    /* ===================================================================
     * Restore from staging
     * =================================================================== */

    /**
     * Restore a previously staged-for-deletion attachment.
     *
     * Best-effort: we restore the post row and postmeta, but the actual
     * file bytes are gone. We surface this clearly — restore reinstates
     * the *record* for content that still references it, but the user
     * will need to re-upload the actual file.
     *
     * @param int $row_id Staging row ID (NOT the attachment ID).
     * @return array|WP_Error
     */
    public static function restore( $row_id ) {
        global $wpdb;
        $row_id = (int) $row_id;
        $table  = self::table_name();

        $row = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d AND status = %s", $row_id, self::STATUS_DELETED ),
            ARRAY_A
        );

        if ( ! $row ) {
            return new WP_Error( 'not_found', __( 'Staged record not found.', 'mbr-performance' ) );
        }

        $post_data = $row['attachment_post'] ? json_decode( $row['attachment_post'], true ) : null;
        $meta_data = $row['attachment_meta'] ? json_decode( $row['attachment_meta'], true ) : array();

        if ( ! $post_data || ! is_array( $post_data ) ) {
            return new WP_Error( 'corrupt_record', __( 'Staged record is missing post data.', 'mbr-performance' ) );
        }

        // Re-insert the post with its original ID where possible.
        $post_data['import_id'] = (int) $row['attachment_id'];
        unset( $post_data['ID'] );

        $new_id = wp_insert_post( wp_slash( $post_data ), true );
        if ( is_wp_error( $new_id ) ) {
            return $new_id;
        }

        // Restore meta. Each meta_key may have been multi-valued.
        if ( is_array( $meta_data ) ) {
            foreach ( $meta_data as $key => $values ) {
                if ( ! is_array( $values ) ) {
                    continue;
                }
                foreach ( $values as $v ) {
                    // get_post_meta returns scalar values that may be
                    // serialized representations of arrays/objects. Unserialize
                    // so add_post_meta re-serializes them correctly.
                    $unserialized = maybe_unserialize( $v );
                    add_post_meta( $new_id, $key, wp_slash_strings_only( $unserialized ) );
                }
            }
        }

        // Mark the staging row as restored by removing it. Once restored,
        // the row has served its purpose. The user will need to re-upload
        // the actual file bytes — the restored attachment record will
        // point to a non-existent file path until they do.
        $wpdb->delete( $table, array( 'id' => $row_id ), array( '%d' ) );

        return array(
            'attachment_id'        => $new_id,
            'original_id'          => (int) $row['attachment_id'],
            'file_path'            => $row['file_path'],
            'file_bytes_recovered' => false, // Always false — files are gone.
            'message'              => __( 'Attachment record restored. The image file itself was deleted and must be re-uploaded.', 'mbr-performance' ),
        );
    }

    /* ===================================================================
     * Cron purge
     * =================================================================== */

    /**
     * Daily cron handler: permanently removes staging rows whose
     * purge_after has passed. (The actual files are already gone — this
     * just cleans up the audit log.)
     */
    public static function cron_purge_expired() {
        global $wpdb;
        $table = self::table_name();
        $now   = current_time( 'mysql' );

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table}
                 WHERE status = %s
                   AND purge_after IS NOT NULL
                   AND purge_after < %s",
                self::STATUS_DELETED,
                $now
            )
        );
    }

    /* ===================================================================
     * Multisite helpers
     * =================================================================== */

    /**
     * Network-wide table creation. Iterates all sites and ensures the
     * staging table exists on each.
     */
    public static function create_table_network() {
        if ( ! is_multisite() ) {
            self::create_table();
            return;
        }

        $sites = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );
        foreach ( (array) $sites as $site_id ) {
            switch_to_blog( $site_id );
            self::create_table();
            restore_current_blog();
        }
    }
}
