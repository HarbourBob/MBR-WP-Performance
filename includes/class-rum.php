<?php
/**
 * MBR Performance — Real User Monitoring (RUM).
 *
 * Collects real-user Core Web Vitals (LCP, CLS, INP) entirely on-site: a tiny
 * front-end beacon posts each metric to a first-party REST route, which writes
 * to a local table. A nightly cron rolls raw samples into per-template /
 * per-URL aggregates and purges the raw. Nothing leaves the server; no cookies,
 * no IP storage, no PII.
 *
 * The field data this produces also feeds the Performance Doctor, letting it
 * prioritise against real numbers rather than a synthetic render.
 *
 * @package MBRPE
 * @since   1.21.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBRPE_RUM {

    /**
     * Single instance.
     *
     * @var MBRPE_RUM
     */
    private static $instance = null;

    /**
     * Schema version — bump to trigger a dbDelta on upgrade.
     */
    const DB_VERSION = 1;

    /**
     * Aggregation cron hook.
     */
    const CRON_HOOK = 'mbrpe_rum_aggregate';

    /**
     * Option storing the unix timestamp of the last completed aggregation.
     * Used to detect raw samples that have arrived since.
     */
    const LAST_AGG_OPTION = 'mbrpe_rum_last_agg';

    /**
     * Sample count below which a p75 is treated as provisional: still shown,
     * but the Doctor won't raise an actionable recommendation from it.
     */
    const MIN_SAMPLES = 10;

    /**
     * Metrics we accept. Anything else is rejected at ingest.
     *
     * @var string[]
     */
    private static $metrics = array( 'LCP', 'CLS', 'INP' );

    /**
     * Get instance.
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor. Wires the pieces that must always run (the REST route and the
     * cron callback), then the front-end capture only when enabled.
     */
    private function __construct() {
        // The aggregation cron callback must be registered unconditionally so a
        // scheduled event still fires even if the admin toggles collection off
        // (it will simply find no new raw and trim/purge as normal).
        add_action( self::CRON_HOOK, array( __CLASS__, 'run_aggregation' ) );

        // REST route is always registered; ingest() self-gates on the enabled
        // flag and returns 204 when collection is off, so the endpoint never
        // 404s mid-session if the toggle changes.
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );

        // Front-end capture only when enabled and only on the public site.
        if ( ! is_admin() && $this->enabled() ) {
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_capture' ), 99 );
        }

        // Admin-post handlers for the RUM tab buttons.
        if ( is_admin() ) {
            add_action( 'admin_post_mbrpe_rum_clear', array( $this, 'handle_clear' ) );
            add_action( 'admin_post_mbrpe_rum_aggregate_now', array( $this, 'handle_aggregate_now' ) );
        }
    }

    /**
     * Handle the "Run aggregation now" admin-post action.
     *
     * @return void
     */
    public function handle_aggregate_now() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to do this.', 'mbr-performance' ) );
        }
        check_admin_referer( 'mbrpe_rum_aggregate_now' );

        self::maybe_aggregate( true );

        wp_safe_redirect(
            add_query_arg(
                array(
                    'page'    => 'mbr-performance',
                    'tab'     => 'rum',
                    'rum_agg' => '1',
                ),
                admin_url( 'admin.php' )
            )
        );
        exit;
    }

    /**
     * Handle the "Clear RUM data" admin-post action.
     *
     * @return void
     */
    public function handle_clear() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to do this.', 'mbr-performance' ) );
        }
        check_admin_referer( 'mbrpe_rum_clear' );

        self::clear_data();

        wp_safe_redirect(
            add_query_arg(
                array(
                    'page'      => 'mbr-performance',
                    'tab'       => 'rum',
                    'rum_clear' => '1',
                ),
                admin_url( 'admin.php' )
            )
        );
        exit;
    }

    /* ---------------------------------------------------------------------
     * Options helpers
     * ------------------------------------------------------------------- */

    /**
     * Whether RUM collection is enabled.
     *
     * @return bool
     */
    private function enabled() {
        $opts = mbrpe()->get_options( 'rum' );
        return ! empty( $opts['enabled'] );
    }

    /**
     * Read a RUM option with a default.
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    private function opt( $key, $default = null ) {
        $opts = mbrpe()->get_options( 'rum' );
        return isset( $opts[ $key ] ) ? $opts[ $key ] : $default;
    }

    /* ---------------------------------------------------------------------
     * Table names
     * ------------------------------------------------------------------- */

    /**
     * Raw-samples table name for the current blog.
     *
     * @return string
     */
    public static function raw_table() {
        global $wpdb;
        return $wpdb->prefix . 'mbrpe_rum_raw';
    }

    /**
     * Aggregates table name for the current blog.
     *
     * @return string
     */
    public static function agg_table() {
        global $wpdb;
        return $wpdb->prefix . 'mbrpe_rum_agg';
    }

    /* ---------------------------------------------------------------------
     * Schema
     * ------------------------------------------------------------------- */

    /**
     * Create both RUM tables. Idempotent (dbDelta).
     *
     * @return void
     */
    public static function create_tables() {
        global $wpdb;

        $charset = $wpdb->get_charset_collate();
        $raw     = self::raw_table();
        $agg     = self::agg_table();

        // dbDelta formatting rules: two spaces after PRIMARY KEY, no backticks
        // in the key clauses, KEY not INDEX, uppercase types.
        $sql_raw = "CREATE TABLE {$raw} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            metric VARCHAR(8) NOT NULL,
            value FLOAT NOT NULL,
            rating TINYINT(1) NOT NULL DEFAULT 0,
            url_hash CHAR(32) NOT NULL,
            url_path VARCHAR(190) NOT NULL,
            template VARCHAR(32) NOT NULL,
            device TINYINT(1) NOT NULL DEFAULT 0,
            browser VARCHAR(16) NOT NULL DEFAULT '',
            target VARCHAR(255) NOT NULL DEFAULT '',
            detail VARCHAR(255) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY metric_created (metric, created_at),
            KEY template_metric (template, metric)
        ) {$charset};";

        $sql_agg = "CREATE TABLE {$agg} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            day DATE NOT NULL,
            scope VARCHAR(8) NOT NULL,
            scope_key VARCHAR(190) NOT NULL,
            metric VARCHAR(8) NOT NULL,
            device TINYINT(1) NOT NULL DEFAULT 0,
            samples INT(10) UNSIGNED NOT NULL DEFAULT 0,
            p75 FLOAT NOT NULL DEFAULT 0,
            good INT(10) UNSIGNED NOT NULL DEFAULT 0,
            ni INT(10) UNSIGNED NOT NULL DEFAULT 0,
            poor INT(10) UNSIGNED NOT NULL DEFAULT 0,
            worst_target VARCHAR(255) NOT NULL DEFAULT '',
            PRIMARY KEY  (id),
            UNIQUE KEY uniq_agg (day, scope, scope_key, metric, device),
            KEY template_lookup (scope, scope_key, metric)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_raw );
        dbDelta( $sql_agg );

        update_option( 'mbrpe_rum_db_version', self::DB_VERSION );
    }

    /**
     * Drop both tables. Called on uninstall (not deactivation).
     *
     * @return void
     */
    public static function drop_tables() {
        global $wpdb;
        $raw = self::raw_table();
        $agg = self::agg_table();
        // Table identifiers cannot be parameterised; both are built from the
        // trusted $wpdb->prefix, so this is safe.
        $wpdb->query( "DROP TABLE IF EXISTS {$raw}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $wpdb->query( "DROP TABLE IF EXISTS {$agg}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        delete_option( 'mbrpe_rum_db_version' );
    }

    /**
     * Ensure the aggregation cron is scheduled. Idempotent.
     *
     * @return void
     */
    public static function schedule_cron() {
        if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
        }
    }

    /**
     * Clear the aggregation cron.
     *
     * @return void
     */
    public static function unschedule_cron() {
        wp_clear_scheduled_hook( self::CRON_HOOK );
    }

    /* ---------------------------------------------------------------------
     * REST — write path
     * ------------------------------------------------------------------- */

    /**
     * Register the beacon endpoint. POST-only so full-page caches never cache it.
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route(
            'mbrpe/v1',
            '/rum',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'ingest' ),
                'permission_callback' => '__return_true',
            )
        );
    }

    /**
     * Ingest a single metric beacon. Deliberately dumb and fast: validate hard,
     * one prepared INSERT, return 204. Never reads options beyond the enabled
     * gate, never renders output.
     *
     * @param WP_REST_Request $req
     * @return WP_REST_Response
     */
    public function ingest( $req ) {
        // Self-gate: if collection is off, accept-and-ignore rather than error.
        if ( ! $this->enabled() ) {
            return new WP_REST_Response( null, 204 );
        }

        $params = $req->get_json_params();
        if ( ! is_array( $params ) ) {
            return new WP_REST_Response( null, 204 );
        }

        // Metric must be in the allowlist and enabled in settings.
        $metric = isset( $params['metric'] ) ? strtoupper( sanitize_text_field( (string) $params['metric'] ) ) : '';
        if ( ! in_array( $metric, self::$metrics, true ) || ! $this->metric_enabled( $metric ) ) {
            return new WP_REST_Response( null, 204 );
        }

        // Value: float, bounded. Reject negatives and absurd values.
        $value = isset( $params['value'] ) ? (float) $params['value'] : -1.0;
        if ( $value < 0 || $value > 3600000 ) { // hard ceiling: 1 hour in ms.
            return new WP_REST_Response( null, 204 );
        }

        // Rating: map the web-vitals string to 0/1/2.
        $rating = 0;
        if ( isset( $params['rating'] ) ) {
            $r = (string) $params['rating'];
            if ( 'poor' === $r ) {
                $rating = 2;
            } elseif ( 'needs-improvement' === $r ) {
                $rating = 1;
            }
        }

        // Template: validate against a known set; unknown → 'other'.
        $template = isset( $params['template'] ) ? sanitize_key( (string) $params['template'] ) : 'other';
        $template = $this->normalise_template( $template );

        // URL path: strip query/fragment, cap to 190, hash it.
        $path = $this->normalise_path( isset( $params['path'] ) ? (string) $params['path'] : $this->referer_path( $req ) );

        // Device + browser: prefer client hint, else derive from UA, then discard UA.
        $device  = $this->resolve_device( isset( $params['device'] ) ? (string) $params['device'] : '' );
        $browser = $this->resolve_browser( isset( $params['browser'] ) ? (string) $params['browser'] : '' );

        // Attribution — truncate to column widths.
        $target = isset( $params['target'] ) ? substr( sanitize_text_field( (string) $params['target'] ), 0, 255 ) : '';
        $detail = isset( $params['detail'] ) ? substr( sanitize_text_field( (string) $params['detail'] ), 0, 255 ) : '';

        // Cheap per-path rate limit to blunt beacon spam.
        if ( $this->rate_limited( $path, $metric ) ) {
            return new WP_REST_Response( null, 204 );
        }

        global $wpdb;
        $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            self::raw_table(),
            array(
                'metric'     => $metric,
                'value'      => $value,
                'rating'     => $rating,
                'url_hash'   => md5( $path ),
                'url_path'   => $path,
                'template'   => $template,
                'device'     => $device,
                'browser'    => $browser,
                'target'     => $target,
                'detail'     => $detail,
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%s', '%f', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s' )
        );

        return new WP_REST_Response( null, 204 );
    }

    /**
     * Whether a metric is enabled in settings (all on by default).
     *
     * @param string $metric
     * @return bool
     */
    private function metric_enabled( $metric ) {
        $enabled = $this->opt( 'metrics', self::$metrics );
        if ( ! is_array( $enabled ) || empty( $enabled ) ) {
            return true;
        }
        return in_array( $metric, $enabled, true );
    }

    /**
     * Short per-path+metric rate limit using a transient counter.
     *
     * @param string $path
     * @param string $metric
     * @return bool True if the request should be dropped.
     */
    private function rate_limited( $path, $metric ) {
        $key = 'mbrpe_rum_rl_' . md5( $path . '|' . $metric );
        $n   = (int) get_transient( $key );
        if ( $n >= 60 ) { // ~60 writes/path/metric per window.
            return true;
        }
        set_transient( $key, $n + 1, 10 ); // 10-second window.
        return false;
    }

    /* ---------------------------------------------------------------------
     * Normalisation helpers
     * ------------------------------------------------------------------- */

    /**
     * Normalise a URL/path to a stored path: strip scheme/host/query/fragment,
     * collapse to a leading-slash path, truncate to 190.
     *
     * @param string $url
     * @return string
     */
    private function normalise_path( $url ) {
        $url  = (string) $url;
        $path = (string) wp_parse_url( $url, PHP_URL_PATH );
        if ( '' === $path ) {
            $path = '/';
        }
        // Never store query params or fragments — they can carry tokens/PII.
        $path = '/' . ltrim( $path, '/' );
        if ( strlen( $path ) > 190 ) {
            $path = substr( $path, 0, 190 );
        }
        return $path;
    }

    /**
     * Fallback path from the request referer when the client didn't send one.
     *
     * @param WP_REST_Request $req
     * @return string
     */
    private function referer_path( $req ) {
        $ref = $req->get_header( 'referer' );
        return $ref ? $this->normalise_path( $ref ) : '/';
    }

    /**
     * Constrain a template key to the known set.
     *
     * @param string $template
     * @return string
     */
    private function normalise_template( $template ) {
        $known = array( 'home', 'blog', 'single', 'page', 'archive', 'search', 'product', 'shop', 'cart', 'checkout', 'other' );
        return in_array( $template, $known, true ) ? $template : 'other';
    }

    /**
     * Resolve device class (0 desktop, 1 tablet, 2 mobile) from a client hint or
     * the UA, then the UA is discarded by the caller.
     *
     * @param string $hint
     * @return int
     */
    private function resolve_device( $hint ) {
        $hint = strtolower( $hint );
        if ( 'mobile' === $hint ) {
            return 2;
        }
        if ( 'tablet' === $hint ) {
            return 1;
        }
        if ( 'desktop' === $hint ) {
            return 0;
        }
        // Derive from UA as a fallback.
        $ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( (string) wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
        if ( '' === $ua ) {
            return 0;
        }
        if ( false !== strpos( $ua, 'tablet' ) || false !== strpos( $ua, 'ipad' ) ) {
            return 1;
        }
        if ( false !== strpos( $ua, 'mobi' ) || false !== strpos( $ua, 'android' ) ) {
            return 2;
        }
        return 0;
    }

    /**
     * Reduce to a coarse browser family. UA discarded afterwards.
     *
     * @param string $hint
     * @return string
     */
    private function resolve_browser( $hint ) {
        $hint  = strtolower( $hint );
        $known = array( 'chrome', 'safari', 'firefox', 'edge', 'other' );
        if ( in_array( $hint, $known, true ) ) {
            return $hint;
        }
        $ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( (string) wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
        if ( false !== strpos( $ua, 'edg/' ) || false !== strpos( $ua, 'edge' ) ) {
            return 'edge';
        }
        if ( false !== strpos( $ua, 'firefox' ) ) {
            return 'firefox';
        }
        if ( false !== strpos( $ua, 'chrome' ) || false !== strpos( $ua, 'crios' ) ) {
            return 'chrome';
        }
        if ( false !== strpos( $ua, 'safari' ) ) {
            return 'safari';
        }
        return 'other';
    }

    /* ---------------------------------------------------------------------
     * Front-end capture
     * ------------------------------------------------------------------- */

    /**
     * Enqueue the vendored web-vitals build plus the capture wrapper.
     *
     * Kept out of combine/defer/delay so it is never mangled or deferred past
     * the visibilitychange/pagehide events LCP/CLS/INP finalise on.
     *
     * @return void
     */
    public function enqueue_capture() {
        // Skip page-builder editor/preview contexts.
        if ( $this->is_builder_preview() ) {
            return;
        }

        // Optionally keep logged-in sessions out of the field data.
        if ( $this->opt( 'exclude_logged_in', true ) && is_user_logged_in() ) {
            return;
        }

        $vendor_url = MBRPE_PLUGIN_URL . 'includes/vendor/web-vitals/web-vitals.attribution.iife.js';

        wp_register_script(
            'mbrpe-web-vitals',
            $vendor_url,
            array(),
            '4.2.4',
            true
        );

        wp_register_script(
            'mbrpe-rum',
            MBRPE_PLUGIN_URL . 'assets/js/rum.js',
            array( 'mbrpe-web-vitals' ),
            MBRPE_VERSION,
            true
        );

        $sample = (int) $this->opt( 'sample_rate', 100 );
        $sample = max( 1, min( 100, $sample ) );

        wp_localize_script(
            'mbrpe-rum',
            'mbrpeRum',
            array(
                'endpoint'   => esc_url_raw( rest_url( 'mbrpe/v1/rum' ) ),
                'sampleRate' => $sample / 100,
                'template'   => $this->current_template(),
                'path'       => $this->normalise_path( $this->current_path() ),
            )
        );

        wp_enqueue_script( 'mbrpe-web-vitals' );
        wp_enqueue_script( 'mbrpe-rum' );

        // Belt-and-braces: make sure our capture scripts are never combined,
        // deferred or delayed by this plugin's own passes.
        add_filter( 'mbrpe_footer_protected_handles', array( $this, 'protect_handles' ) );
    }

    /**
     * Keep the RUM handles out of move-to-footer / defer manipulation.
     *
     * @param array $handles
     * @return array
     */
    public function protect_handles( $handles ) {
        $handles[] = 'mbrpe-web-vitals';
        $handles[] = 'mbrpe-rum';
        return $handles;
    }

    /**
     * Detect page-builder editor/preview so we never measure the editor chrome.
     *
     * @return bool
     */
    private function is_builder_preview() {
        // WordPress core post preview.
        if ( function_exists( 'is_preview' ) && is_preview() ) {
            return true;
        }
        // Page-builder editor/preview query flags (read-only presence checks;
        // the values are never used, so no sanitisation is required).
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        foreach ( array( 'elementor-preview', 'preview', 'vcv-editable', 'fl_builder', 'ct_builder', 'bricks-is-builder' ) as $flag ) {
            if ( isset( $_GET[ $flag ] ) ) {
                return true;
            }
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        return false;
    }

    /**
     * Compute a template label for the current request.
     *
     * @return string
     */
    private function current_template() {
        if ( function_exists( 'is_front_page' ) && is_front_page() ) {
            return 'home';
        }
        if ( function_exists( 'is_home' ) && is_home() ) {
            return 'blog';
        }
        // WooCommerce templates, when active.
        if ( function_exists( 'is_product' ) && is_product() ) {
            return 'product';
        }
        if ( function_exists( 'is_shop' ) && is_shop() ) {
            return 'shop';
        }
        if ( function_exists( 'is_cart' ) && is_cart() ) {
            return 'cart';
        }
        if ( function_exists( 'is_checkout' ) && is_checkout() ) {
            return 'checkout';
        }
        if ( is_search() ) {
            return 'search';
        }
        if ( is_singular( 'page' ) ) {
            return 'page';
        }
        if ( is_single() ) {
            return 'single';
        }
        if ( is_archive() ) {
            return 'archive';
        }
        return 'other';
    }

    /**
     * Current request path (server-side), for hashing and storage.
     *
     * @return string
     */
    private function current_path() {
        if ( empty( $_SERVER['REQUEST_URI'] ) ) {
            return '/';
        }
        return (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH );
    }

    /* ---------------------------------------------------------------------
     * Aggregation cron
     * ------------------------------------------------------------------- */

    /**
     * Cron callback: aggregate yesterday-and-today's raw into daily buckets,
     * purge raw beyond the retention window, then trim old aggregates.
     *
     * @return void
     */
    public static function run_aggregation() {
        $self = self::instance();
        $self->aggregate();
        $self->purge_raw();
        $self->trim_aggregates();

        // Stamp completion so has_pending_raw() can tell what's new since.
        update_option( self::LAST_AGG_OPTION, time(), false );
    }

    /**
     * Timestamp of the newest raw sample, or 0 when the table is empty.
     *
     * @return int
     */
    public static function newest_raw_time() {
        global $wpdb;
        $raw = self::raw_table();
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $newest = $wpdb->get_var( "SELECT MAX(created_at) FROM {$raw}" );
        return $newest ? (int) strtotime( $newest ) : 0;
    }

    /**
     * Whether raw samples have arrived since the last completed aggregation.
     *
     * @return bool
     */
    public static function has_pending_raw() {
        $newest = self::newest_raw_time();
        if ( ! $newest ) {
            return false;
        }
        return $newest > (int) get_option( self::LAST_AGG_OPTION, 0 );
    }

    /**
     * Aggregate on demand when there is new raw data, so field data is visible
     * immediately rather than after the nightly cron.
     *
     * Without this there is a dead zone of up to 24 hours after enabling RUM
     * where raw samples accumulate but the scorecard and the Doctor — both of
     * which read aggregates — stay empty, which reads as "broken".
     *
     * Throttled so repeated admin page loads cannot hammer the database.
     *
     * @param bool $force Bypass the throttle (used by the manual button).
     * @return bool True when an aggregation actually ran.
     */
    public static function maybe_aggregate( $force = false ) {
        if ( ! self::has_pending_raw() ) {
            return false;
        }

        if ( ! $force ) {
            // At most one automatic run per minute.
            if ( get_transient( 'mbrpe_rum_agg_lock' ) ) {
                return false;
            }
            set_transient( 'mbrpe_rum_agg_lock', 1, MINUTE_IN_SECONDS );
        }

        self::run_aggregation();
        return true;
    }

    /**
     * Roll raw samples into per-template and per-URL daily aggregates.
     *
     * Aggregates each day fully from that day's raw (raw is retained long enough
     * that a day is always complete before its raw is purged), so each daily p75
     * is exact. Cross-day rollups in the UI are a trend of daily p75s.
     *
     * @return void
     */
    private function aggregate() {
        global $wpdb;
        $raw = self::raw_table();
        $agg = self::agg_table();

        // Which days still have raw rows to aggregate?
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $days = $wpdb->get_col( "SELECT DISTINCT DATE(created_at) FROM {$raw} ORDER BY DATE(created_at) ASC" );
        if ( empty( $days ) ) {
            return;
        }

        foreach ( $days as $day ) {
            // Per-template buckets for this day.
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT template, metric, device, value, rating, target
                     FROM {$raw} WHERE DATE(created_at) = %s",
                    $day
                ),
                ARRAY_A
            );
            if ( empty( $rows ) ) {
                continue;
            }

            $buckets = array();
            foreach ( $rows as $row ) {
                // Per-template scope here; per-URL scope is handled by a separate
                // pass below (aggregate_urls) so the two don't share a query.
                $k = 'template|' . $row['template'] . '|' . $row['metric'] . '|' . (int) $row['device'];
                if ( ! isset( $buckets[ $k ] ) ) {
                    $buckets[ $k ] = array(
                        'scope'     => 'template',
                        'scope_key' => $row['template'],
                        'metric'    => $row['metric'],
                        'device'    => (int) $row['device'],
                        'values'    => array(),
                        'good'      => 0,
                        'ni'        => 0,
                        'poor'      => 0,
                        'targets'   => array(),
                    );
                }
                $buckets[ $k ]['values'][] = (float) $row['value'];
                $this->tally( $buckets[ $k ], (int) $row['rating'], $row['target'] );
            }

            $this->write_buckets( $agg, $day, $buckets );

            // Per-URL buckets (top offenders) for this day.
            $this->aggregate_urls( $raw, $agg, $day );
        }
    }

    /**
     * Increment good/ni/poor tallies and record poor-rated attribution targets.
     *
     * @param array  $bucket Bucket passed by reference.
     * @param int    $rating
     * @param string $target
     * @return void
     */
    private function tally( &$bucket, $rating, $target ) {
        if ( 2 === $rating ) {
            $bucket['poor']++;
            if ( '' !== $target ) {
                $bucket['targets'][ $target ] = ( $bucket['targets'][ $target ] ?? 0 ) + 1;
            }
        } elseif ( 1 === $rating ) {
            $bucket['ni']++;
        } else {
            $bucket['good']++;
        }
    }

    /**
     * Aggregate per-URL buckets for a given day, keeping only URLs with poor
     * ratings so the table doesn't store every path on the site.
     *
     * @param string $raw
     * @param string $agg
     * @param string $day
     * @return void
     */
    private function aggregate_urls( $raw, $agg, $day ) {
        global $wpdb;
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT url_path, metric, device, value, rating, target
                 FROM {$raw} WHERE DATE(created_at) = %s AND rating = 2",
                $day
            ),
            ARRAY_A
        );
        if ( empty( $rows ) ) {
            return;
        }

        $buckets = array();
        foreach ( $rows as $row ) {
            $k = 'url|' . $row['url_path'] . '|' . $row['metric'] . '|' . (int) $row['device'];
            if ( ! isset( $buckets[ $k ] ) ) {
                $buckets[ $k ] = array(
                    'scope'     => 'url',
                    'scope_key' => substr( $row['url_path'], 0, 190 ),
                    'metric'    => $row['metric'],
                    'device'    => (int) $row['device'],
                    'values'    => array(),
                    'good'      => 0,
                    'ni'        => 0,
                    'poor'      => 0,
                    'targets'   => array(),
                );
            }
            $buckets[ $k ]['values'][] = (float) $row['value'];
            $this->tally( $buckets[ $k ], (int) $row['rating'], $row['target'] );
        }

        $this->write_buckets( $agg, $day, $buckets );
    }

    /**
     * Upsert a set of computed buckets into the aggregates table.
     *
     * @param string $agg
     * @param string $day
     * @param array  $buckets
     * @return void
     */
    private function write_buckets( $agg, $day, $buckets ) {
        global $wpdb;

        foreach ( $buckets as $b ) {
            $p75          = $this->percentile( $b['values'], 75 );
            $worst_target = '';
            if ( ! empty( $b['targets'] ) ) {
                arsort( $b['targets'] );
                $worst_target = (string) array_key_first( $b['targets'] );
            }

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->query(
                $wpdb->prepare(
                    "INSERT INTO {$agg}
                        (day, scope, scope_key, metric, device, samples, p75, good, ni, poor, worst_target)
                     VALUES (%s, %s, %s, %s, %d, %d, %f, %d, %d, %d, %s)
                     ON DUPLICATE KEY UPDATE
                        samples = VALUES(samples),
                        p75 = VALUES(p75),
                        good = VALUES(good),
                        ni = VALUES(ni),
                        poor = VALUES(poor),
                        worst_target = VALUES(worst_target)",
                    $day,
                    $b['scope'],
                    $b['scope_key'],
                    $b['metric'],
                    $b['device'],
                    count( $b['values'] ),
                    $p75,
                    $b['good'],
                    $b['ni'],
                    $b['poor'],
                    substr( $worst_target, 0, 255 )
                )
            );
        }
    }

    /**
     * Nearest-rank percentile.
     *
     * @param float[] $values
     * @param int     $p
     * @return float
     */
    private function percentile( $values, $p ) {
        if ( empty( $values ) ) {
            return 0.0;
        }
        sort( $values, SORT_NUMERIC );
        $n    = count( $values );
        $rank = (int) ceil( ( $p / 100 ) * $n );
        $rank = max( 1, min( $n, $rank ) );
        return (float) $values[ $rank - 1 ];
    }

    /**
     * Delete raw rows older than the configured retention window.
     *
     * @return void
     */
    private function purge_raw() {
        global $wpdb;
        $days = max( 1, (int) $this->opt( 'raw_retention_days', 3 ) );
        $raw  = self::raw_table();
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$raw} WHERE created_at < ( NOW() - INTERVAL %d DAY )",
                $days
            )
        );
    }

    /**
     * Trim aggregate rows older than the configured retention window.
     *
     * @return void
     */
    private function trim_aggregates() {
        global $wpdb;
        $days = max( 7, (int) $this->opt( 'agg_retention_days', 60 ) );
        $agg  = self::agg_table();
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$agg} WHERE day < ( CURDATE() - INTERVAL %d DAY )",
                $days
            )
        );
    }

    /* ---------------------------------------------------------------------
     * Read helpers (admin panel + Doctor)
     * ------------------------------------------------------------------- */

    /**
     * Site-wide scorecard: latest p75 per metric across all templates, with the
     * good/ni/poor distribution and a total sample count. Uses the most recent
     * $days of aggregates.
     *
     * @param int $days Look-back window.
     * @return array metric => [ p75, good, ni, poor, samples ]
     */
    public static function scorecard( $days = 28 ) {
        global $wpdb;
        $agg  = self::agg_table();
        $days = max( 1, (int) $days );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT metric,
                        SUM(samples) AS samples,
                        SUM(good) AS good,
                        SUM(ni) AS ni,
                        SUM(poor) AS poor,
                        SUM(p75 * samples) / NULLIF(SUM(samples),0) AS wp75
                 FROM {$agg}
                 WHERE scope = 'template' AND day >= ( CURDATE() - INTERVAL %d DAY )
                 GROUP BY metric",
                $days
            ),
            ARRAY_A
        );

        $out = array();
        foreach ( (array) $rows as $r ) {
            $out[ $r['metric'] ] = array(
                'p75'     => (float) $r['wp75'],
                'good'    => (int) $r['good'],
                'ni'      => (int) $r['ni'],
                'poor'    => (int) $r['poor'],
                'samples' => (int) $r['samples'],
            );
        }
        return $out;
    }

    /**
     * Per-template breakdown for a given metric.
     *
     * @param string $metric
     * @param int    $days
     * @return array Rows keyed by template with p75 and sample count per device.
     */
    public static function by_template( $metric, $days = 28 ) {
        global $wpdb;
        $agg    = self::agg_table();
        $metric = strtoupper( sanitize_text_field( $metric ) );
        $days   = max( 1, (int) $days );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT scope_key AS template, device,
                        SUM(p75 * samples) / NULLIF(SUM(samples),0) AS p75,
                        SUM(samples) AS samples
                 FROM {$agg}
                 WHERE scope = 'template' AND metric = %s AND day >= ( CURDATE() - INTERVAL %d DAY )
                 GROUP BY scope_key, device
                 ORDER BY p75 DESC",
                $metric,
                $days
            ),
            ARRAY_A
        );
    }

    /**
     * Worst URLs by poor-rating count for a metric.
     *
     * @param string $metric
     * @param int    $limit
     * @param int    $days
     * @return array
     */
    public static function worst_urls( $metric, $limit = 10, $days = 28 ) {
        global $wpdb;
        $agg    = self::agg_table();
        $metric = strtoupper( sanitize_text_field( $metric ) );
        $limit  = max( 1, min( 100, (int) $limit ) );
        $days   = max( 1, (int) $days );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT scope_key AS url_path,
                        SUM(poor) AS poor,
                        SUM(samples) AS samples,
                        SUM(p75 * samples) / NULLIF(SUM(samples),0) AS p75,
                        MAX(worst_target) AS worst_target
                 FROM {$agg}
                 WHERE scope = 'url' AND metric = %s AND day >= ( CURDATE() - INTERVAL %d DAY )
                 GROUP BY scope_key
                 ORDER BY poor DESC
                 LIMIT %d",
                $metric,
                $days,
                $limit
            ),
            ARRAY_A
        );
    }

    /**
     * Total raw + aggregate row counts, for the panel's data-health line.
     *
     * @return array [ raw => int, agg => int ]
     */
    public static function row_counts() {
        global $wpdb;
        $raw = self::raw_table();
        $agg = self::agg_table();
        return array(
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            'raw' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$raw}" ),
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            'agg' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$agg}" ),
        );
    }

    /**
     * The single most common poor-rated attribution target for a metric,
     * site-wide, over the look-back window. Used by the Doctor to name the
     * real culprit ("your INP is 380ms, most often on .menu-toggle").
     *
     * @param string $metric
     * @param int    $days
     * @return string Empty string if none recorded.
     */
    public static function worst_target( $metric, $days = 28 ) {
        global $wpdb;
        $agg    = self::agg_table();
        $metric = strtoupper( sanitize_text_field( $metric ) );
        $days   = max( 1, (int) $days );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $target = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT worst_target
                 FROM {$agg}
                 WHERE scope = 'template' AND metric = %s AND worst_target <> ''
                       AND day >= ( CURDATE() - INTERVAL %d DAY )
                 GROUP BY worst_target
                 ORDER BY SUM(poor) DESC
                 LIMIT 1",
                $metric,
                $days
            )
        );

        return $target ? (string) $target : '';
    }

    /**
     * Truncate both tables (admin "Clear RUM data").
     *
     * @return void
     */
    public static function clear_data() {
        global $wpdb;
        $raw = self::raw_table();
        $agg = self::agg_table();
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query( "TRUNCATE TABLE {$raw}" );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query( "TRUNCATE TABLE {$agg}" );
    }

    /**
     * Metric pass/fail thresholds (good ceiling), for panel and Doctor.
     *
     * @return array
     */
    public static function thresholds() {
        return array(
            'LCP' => array( 'good' => 2500, 'poor' => 4000, 'unit' => 'ms' ),
            'INP' => array( 'good' => 200,  'poor' => 500,  'unit' => 'ms' ),
            'CLS' => array( 'good' => 0.1,  'poor' => 0.25, 'unit' => '' ),
        );
    }
}
