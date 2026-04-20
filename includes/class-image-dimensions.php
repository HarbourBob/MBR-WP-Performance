<?php
/**
 * Image Dimensions & Sizing
 *
 * Handles two optimisations to help address the PageSpeed Insights
 * "Properly size images" and "Ensure images have explicit width and height"
 * recommendations:
 *
 *   1. Resize large uploads to fit within a configurable maximum dimension
 *      using WordPress's built-in `big_image_size_threshold` filter.
 *
 *   2. Automatically inject missing width and height attributes into
 *      front-end <img> tags so browsers can reserve layout space and avoid
 *      Cumulative Layout Shift (CLS).
 *
 * @package MBR_WP_Performance
 * @since   1.7.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Image Dimensions class
 */
class MBR_WP_Performance_Image_Dimensions {

    /**
     * Single instance.
     *
     * @var MBR_WP_Performance_Image_Dimensions
     */
    private static $instance = null;

    /**
     * In-memory dimensions cache for the current request, keyed by URL.
     *
     * @var array
     */
    private static $url_cache = array();

    /**
     * Default maximum dimension (in pixels) applied to uploads when the
     * resize feature is enabled. Matches WordPress core's own default for
     * the `big_image_size_threshold` filter.
     */
    const DEFAULT_MAX_DIMENSION = 2560;

    /**
     * Hard floor for the user-configurable maximum dimension.
     */
    const MIN_MAX_DIMENSION = 100;

    /**
     * Hard ceiling for the user-configurable maximum dimension.
     */
    const MAX_MAX_DIMENSION = 10000;

    /**
     * Get instance.
     *
     * @return MBR_WP_Performance_Image_Dimensions
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $opts = $this->get_options();

        // Resize large uploads via the `big_image_size_threshold` filter.
        if ( ! empty( $opts['resize_on_upload'] ) ) {
            $max = isset( $opts['max_dimension'] ) ? absint( $opts['max_dimension'] ) : self::DEFAULT_MAX_DIMENSION;
            $max = max( self::MIN_MAX_DIMENSION, min( self::MAX_MAX_DIMENSION, $max ) );

            add_filter(
                'big_image_size_threshold',
                function () use ( $max ) {
                    return $max;
                },
                10,
                1
            );
        }

        // Add missing width/height attributes on the front-end.
        if ( ! empty( $opts['add_missing_dimensions'] ) ) {
            add_filter( 'the_content', array( $this, 'filter_content_add_dimensions' ), 15 );
            add_filter( 'wp_get_attachment_image', array( $this, 'filter_img_add_dimensions' ), 15 );
            add_filter( 'post_thumbnail_html', array( $this, 'filter_img_add_dimensions' ), 15 );
            add_filter( 'render_block', array( $this, 'filter_block_add_dimensions' ), 15, 2 );

            if ( class_exists( '\Elementor\Plugin' ) ) {
                add_filter( 'elementor/widget/render_content', array( $this, 'filter_elementor_add_dimensions' ), 15, 2 );
            }
        }
    }

    /**
     * Read the image_dimensions settings safely, even when the section is
     * missing from the stored options (e.g. on upgrade from < 1.7.0).
     *
     * @return array
     */
    private function get_options() {
        $all = mbr_wp_performance()->get_options();

        if ( is_array( $all ) && isset( $all['image_dimensions'] ) && is_array( $all['image_dimensions'] ) ) {
            return $all['image_dimensions'];
        }

        return array();
    }

    /**
     * Should we skip dimension injection in the current context?
     *
     * Avoids running inside admin screens and page builder editors / previews
     * where markup is under active construction.
     *
     * @return bool
     */
    private function skip_context() {
        if ( is_admin() ) {
            return true;
        }

        if ( class_exists( '\Elementor\Plugin' ) ) {
            if ( \Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
                return true;
            }
        }

        if ( function_exists( 'get_current_screen' ) ) {
            $screen = get_current_screen();
            if ( $screen && 'post' === $screen->base && ! empty( $screen->is_block_editor ) ) {
                return true;
            }
        }

        return false;
    }

    /* ======================================================================
     * DIMENSION INJECTION
     * ==================================================================== */

    /**
     * Inject missing width/height attributes into a single <img> tag.
     *
     * @param string $img_html The <img> HTML.
     * @return string The updated <img> HTML, or the original on any skip.
     */
    public function add_dimensions_to_img( $img_html ) {
        if ( ! is_string( $img_html ) || stripos( $img_html, '<img' ) === false ) {
            return $img_html;
        }

        $has_width  = (bool) preg_match( '/\swidth\s*=\s*["\']?\d+/i', $img_html );
        $has_height = (bool) preg_match( '/\sheight\s*=\s*["\']?\d+/i', $img_html );

        // Both already present — nothing to do.
        if ( $has_width && $has_height ) {
            return $img_html;
        }

        // Prefer lazy data-src if the img has a placeholder src (common with
        // lazy-loading plugins), falling back to src.
        $src = '';
        if ( preg_match( '/\sdata-src=["\']([^"\']+)["\']/i', $img_html, $m ) ) {
            $src = $m[1];
        } elseif ( preg_match( '/\ssrc=["\']([^"\']+)["\']/i', $img_html, $m ) ) {
            $src = $m[1];
        }

        if ( empty( $src ) ) {
            return $img_html;
        }

        // Skip data URIs.
        if ( 0 === stripos( $src, 'data:' ) ) {
            return $img_html;
        }

        // Skip SVGs — getimagesize() is unreliable for them.
        $path_for_ext = wp_parse_url( $src, PHP_URL_PATH );
        $ext          = $path_for_ext ? strtolower( pathinfo( $path_for_ext, PATHINFO_EXTENSION ) ) : '';
        if ( 'svg' === $ext ) {
            return $img_html;
        }

        $dims = $this->get_image_dimensions( $src );
        if ( ! $dims ) {
            return $img_html;
        }

        $new = $img_html;

        if ( ! $has_width ) {
            $new = preg_replace( '/<img\b/i', '<img width="' . intval( $dims[0] ) . '"', $new, 1 );
        }
        if ( ! $has_height ) {
            $new = preg_replace( '/<img\b/i', '<img height="' . intval( $dims[1] ) . '"', $new, 1 );
        }

        return $new ? $new : $img_html;
    }

    /**
     * Resolve an image URL to [width, height], using caching.
     *
     * @param string $url Image URL.
     * @return array|false [width, height] or false on failure.
     */
    private function get_image_dimensions( $url ) {
        if ( isset( self::$url_cache[ $url ] ) ) {
            return self::$url_cache[ $url ];
        }

        $transient_key = 'mbr_imgdim_' . md5( $url );
        $cached        = get_transient( $transient_key );
        if ( false !== $cached ) {
            // Transient "false" == miss; we store a sentinel for negatives below.
            if ( is_array( $cached ) && count( $cached ) === 2 ) {
                self::$url_cache[ $url ] = $cached;
                return $cached;
            }
            if ( 'none' === $cached ) {
                self::$url_cache[ $url ] = false;
                return false;
            }
        }

        $path = $this->url_to_local_path( $url );
        if ( ! $path || ! file_exists( $path ) ) {
            self::$url_cache[ $url ] = false;
            set_transient( $transient_key, 'none', HOUR_IN_SECONDS );
            return false;
        }

        $size = @getimagesize( $path );
        if ( ! is_array( $size ) || empty( $size[0] ) || empty( $size[1] ) ) {
            self::$url_cache[ $url ] = false;
            set_transient( $transient_key, 'none', HOUR_IN_SECONDS );
            return false;
        }

        $dims                    = array( (int) $size[0], (int) $size[1] );
        self::$url_cache[ $url ] = $dims;
        set_transient( $transient_key, $dims, WEEK_IN_SECONDS );

        return $dims;
    }

    /**
     * Convert a same-site URL to an absolute local file path, or false.
     *
     * @param string $url Image URL.
     * @return string|false
     */
    private function url_to_local_path( $url ) {
        $parsed = wp_parse_url( $url );
        if ( empty( $parsed['path'] ) ) {
            return false;
        }

        $site = wp_parse_url( home_url() );

        // If the URL carries a host, it must match the site host.
        if ( ! empty( $parsed['host'] ) && ! empty( $site['host'] ) && $parsed['host'] !== $site['host'] ) {
            return false;
        }

        $uploads = wp_get_upload_dir();

        // Fast path: uploads folder.
        if ( isset( $uploads['baseurl'] ) && strpos( $url, $uploads['baseurl'] ) === 0 ) {
            $rel = substr( $url, strlen( $uploads['baseurl'] ) );
            return wp_normalize_path( $uploads['basedir'] . $rel );
        }

        // wp-content (themes, plugins, etc.).
        $content_url = content_url();
        if ( strpos( $url, $content_url ) === 0 ) {
            $rel = substr( $url, strlen( $content_url ) );
            return wp_normalize_path( WP_CONTENT_DIR . $rel );
        }

        // Site-relative paths (no host).
        if ( empty( $parsed['host'] ) && 0 === strpos( $parsed['path'], '/' ) ) {
            return wp_normalize_path( ABSPATH . ltrim( $parsed['path'], '/' ) );
        }

        return false;
    }

    /* ======================================================================
     * FILTER CALLBACKS
     * ==================================================================== */

    /**
     * Filter `the_content` to add dimensions to every <img> tag.
     *
     * @param string $content Content HTML.
     * @return string
     */
    public function filter_content_add_dimensions( $content ) {
        if ( $this->skip_context() || empty( $content ) || false === stripos( $content, '<img' ) ) {
            return $content;
        }

        return preg_replace_callback(
            '/<img[^>]*>/i',
            function ( $m ) {
                return $this->add_dimensions_to_img( $m[0] );
            },
            $content
        );
    }

    /**
     * Filter a single <img> HTML string (attachment image / post thumbnail).
     *
     * @param string $html Image HTML.
     * @return string
     */
    public function filter_img_add_dimensions( $html ) {
        if ( $this->skip_context() ) {
            return $html;
        }
        return $this->add_dimensions_to_img( $html );
    }

    /**
     * Filter rendered Gutenberg blocks that may contain images.
     *
     * @param string $block_content Rendered block HTML.
     * @param array  $parsed_block  Parsed block array.
     * @return string
     */
    public function filter_block_add_dimensions( $block_content, $parsed_block ) {
        if ( $this->skip_context() ) {
            return $block_content;
        }

        $blocks_with_images = array( 'core/image', 'core/gallery', 'core/media-text', 'core/cover' );
        if ( empty( $parsed_block['blockName'] ) || ! in_array( $parsed_block['blockName'], $blocks_with_images, true ) ) {
            return $block_content;
        }

        if ( false === stripos( $block_content, '<img' ) ) {
            return $block_content;
        }

        return preg_replace_callback(
            '/<img[^>]*>/i',
            function ( $m ) {
                return $this->add_dimensions_to_img( $m[0] );
            },
            $block_content
        );
    }

    /**
     * Filter Elementor widget output to inject dimensions.
     *
     * @param string $content Widget HTML.
     * @param object $widget  Elementor widget instance.
     * @return string
     */
    public function filter_elementor_add_dimensions( $content, $widget ) {
        unset( $widget ); // reserved for future widget-specific logic
        if ( $this->skip_context() || empty( $content ) || false === stripos( $content, '<img' ) ) {
            return $content;
        }

        return preg_replace_callback(
            '/<img[^>]*>/i',
            function ( $m ) {
                return $this->add_dimensions_to_img( $m[0] );
            },
            $content
        );
    }

    /* ======================================================================
     * BULK RESIZE (existing Media Library images)
     * ==================================================================== */

    /**
     * Find attachment IDs whose image exceeds the given maximum dimension.
     *
     * Paginated internally so large libraries don't blow up memory.
     *
     * @param int $max_dim Maximum dimension in pixels.
     * @return int[] Attachment IDs that would be resized.
     */
    public static function get_resize_candidates( $max_dim ) {
        $max_dim = max( self::MIN_MAX_DIMENSION, min( self::MAX_MAX_DIMENSION, absint( $max_dim ) ) );
        if ( ! $max_dim ) {
            return array();
        }

        $candidates = array();
        $paged      = 1;
        $per_page   = 200;

        do {
            $query = new WP_Query(
                array(
                    'post_type'      => 'attachment',
                    'post_status'    => 'inherit',
                    'post_mime_type' => array( 'image/jpeg', 'image/png' ),
                    'posts_per_page' => $per_page,
                    'paged'          => $paged,
                    'fields'         => 'ids',
                    'no_found_rows'  => true,
                    'orderby'        => 'ID',
                    'order'          => 'ASC',
                )
            );

            if ( empty( $query->posts ) ) {
                break;
            }

            foreach ( $query->posts as $id ) {
                $meta = wp_get_attachment_metadata( $id );
                if ( ! is_array( $meta ) ) {
                    continue;
                }

                $w = isset( $meta['width'] ) ? (int) $meta['width'] : 0;
                $h = isset( $meta['height'] ) ? (int) $meta['height'] : 0;

                if ( ! $w || ! $h ) {
                    // Fall back to reading the file if metadata is missing.
                    $file = get_attached_file( $id );
                    if ( $file && file_exists( $file ) ) {
                        $size = @getimagesize( $file );
                        if ( is_array( $size ) ) {
                            $w = (int) $size[0];
                            $h = (int) $size[1];
                        }
                    }
                }

                if ( $w > $max_dim || $h > $max_dim ) {
                    $candidates[] = (int) $id;
                }
            }

            $fetched = count( $query->posts );
            $paged++;
        } while ( $fetched === $per_page );

        return $candidates;
    }

    /**
     * Resize a single attachment in place, then regenerate its sub-sizes.
     *
     * This permanently overwrites the attached file on disk. The caller is
     * responsible for confirming the operation with the user first.
     *
     * @param int $attachment_id Attachment post ID.
     * @param int $max_dim       Maximum dimension in pixels.
     * @return array|WP_Error Result array on success/skip, WP_Error on failure.
     */
    public static function bulk_resize_attachment( $attachment_id, $max_dim ) {
        $attachment_id = absint( $attachment_id );
        if ( ! $attachment_id ) {
            return new WP_Error( 'invalid_id', __( 'Invalid attachment ID.', 'mbr-wp-performance' ) );
        }

        $max_dim = max( self::MIN_MAX_DIMENSION, min( self::MAX_MAX_DIMENSION, absint( $max_dim ) ) );
        if ( ! $max_dim ) {
            return new WP_Error( 'invalid_max', __( 'Invalid maximum dimension.', 'mbr-wp-performance' ) );
        }

        $mime = get_post_mime_type( $attachment_id );
        if ( ! in_array( $mime, array( 'image/jpeg', 'image/png' ), true ) ) {
            return new WP_Error( 'unsupported_type', __( 'Only JPEG and PNG images can be resized.', 'mbr-wp-performance' ) );
        }

        $file = get_attached_file( $attachment_id );
        if ( ! $file || ! file_exists( $file ) ) {
            return new WP_Error( 'file_missing', __( 'Image file not found on disk.', 'mbr-wp-performance' ) );
        }

        if ( ! is_writable( $file ) ) {
            return new WP_Error( 'file_not_writable', __( 'Image file is not writable.', 'mbr-wp-performance' ) );
        }

        // Resolve current dimensions.
        $meta = wp_get_attachment_metadata( $attachment_id );
        $current_w = ( is_array( $meta ) && isset( $meta['width'] ) ) ? (int) $meta['width'] : 0;
        $current_h = ( is_array( $meta ) && isset( $meta['height'] ) ) ? (int) $meta['height'] : 0;

        if ( ! $current_w || ! $current_h ) {
            $size = @getimagesize( $file );
            if ( is_array( $size ) ) {
                $current_w = (int) $size[0];
                $current_h = (int) $size[1];
            }
        }

        if ( ! $current_w || ! $current_h ) {
            return new WP_Error( 'no_dimensions', __( 'Unable to determine image dimensions.', 'mbr-wp-performance' ) );
        }

        // Already within limits — skip cleanly.
        if ( $current_w <= $max_dim && $current_h <= $max_dim ) {
            return array(
                'status'          => 'skipped',
                'reason'          => __( 'Already within the configured maximum.', 'mbr-wp-performance' ),
                'id'              => $attachment_id,
                'filename'        => basename( $file ),
                'original_width'  => $current_w,
                'original_height' => $current_h,
            );
        }

        $original_size = (int) @filesize( $file );

        // Resize with the WP image editor (GD / Imagick, whichever is available).
        $editor = wp_get_image_editor( $file );
        if ( is_wp_error( $editor ) ) {
            return $editor;
        }

        $resize_result = $editor->resize( $max_dim, $max_dim, false );
        if ( is_wp_error( $resize_result ) ) {
            return $resize_result;
        }

        $save_result = $editor->save( $file );
        if ( is_wp_error( $save_result ) ) {
            return $save_result;
        }

        clearstatcache( true, $file );
        $new_size = (int) @filesize( $file );

        // Remove stale WebP copies of this attachment's files before subsizes
        // regenerate — otherwise any WebPs sitting next to subsize files would
        // continue to be served but reflect the pre-resize content.
        $webp_removed = self::cleanup_stale_webp_for_attachment( $attachment_id );

        // Regenerate sub-sizes and metadata from the newly-resized original.
        if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $new_meta = wp_generate_attachment_metadata( $attachment_id, $file );
        if ( is_array( $new_meta ) ) {
            wp_update_attachment_metadata( $attachment_id, $new_meta );
        }

        // Clear Elementor's CSS cache so it re-renders with the new dimensions.
        if ( class_exists( '\Elementor\Plugin' ) && ! empty( \Elementor\Plugin::instance()->files_manager ) ) {
            \Elementor\Plugin::instance()->files_manager->clear_cache();
        }

        $new_w = isset( $save_result['width'] ) ? (int) $save_result['width'] : 0;
        $new_h = isset( $save_result['height'] ) ? (int) $save_result['height'] : 0;

        return array(
            'status'          => 'success',
            'id'              => $attachment_id,
            'filename'        => basename( $file ),
            'original_width'  => $current_w,
            'original_height' => $current_h,
            'new_width'       => $new_w,
            'new_height'      => $new_h,
            'original_size'   => $original_size,
            'new_size'        => $new_size,
            'saved_bytes'     => max( 0, $original_size - $new_size ),
            'webp_cleaned'    => (int) $webp_removed,
        );
    }

    /**
     * Delete any stale WebP copies for a given attachment's original and
     * sub-size files, and purge the matching entries from the WebP registry.
     *
     * Called before regenerating sub-sizes during a bulk resize, so that
     * stale WebP content (converted from the pre-resize source) is removed.
     *
     * @param int $attachment_id Attachment post ID.
     * @return int Number of WebP files deleted.
     */
    private static function cleanup_stale_webp_for_attachment( $attachment_id ) {
        $main_file = get_attached_file( $attachment_id );
        if ( ! $main_file ) {
            return 0;
        }

        $meta     = wp_get_attachment_metadata( $attachment_id );
        $base_dir = dirname( $main_file );
        $files    = array( $main_file );

        if ( is_array( $meta ) && ! empty( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) {
            foreach ( $meta['sizes'] as $size ) {
                if ( ! empty( $size['file'] ) ) {
                    $files[] = $base_dir . '/' . $size['file'];
                }
            }
        }

        $upload_dir   = wp_upload_dir();
        $basedir_trim = trailingslashit( wp_normalize_path( $upload_dir['basedir'] ) );
        $registry     = get_option( 'mbr_webp_registry', array() );
        $registry     = is_array( $registry ) ? $registry : array();
        $to_remove    = array();
        $deleted      = 0;

        foreach ( $files as $file ) {
            $candidates = array(
                $file . '.webp',
                preg_replace( '/\.(jpe?g|png)$/i', '.webp', $file ),
            );
            foreach ( $candidates as $webp ) {
                if ( empty( $webp ) || $webp === $file ) {
                    continue;
                }
                if ( file_exists( $webp ) ) {
                    wp_delete_file( $webp );
                    $deleted++;
                    $rel = str_replace( $basedir_trim, '', wp_normalize_path( $webp ) );
                    $to_remove[] = $rel;
                }
            }
        }

        if ( ! empty( $to_remove ) ) {
            $registry = array_values( array_diff( $registry, $to_remove ) );
            update_option( 'mbr_webp_registry', $registry );
        }

        return $deleted;
    }
}
