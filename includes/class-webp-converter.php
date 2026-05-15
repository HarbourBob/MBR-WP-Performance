<?php
/**
 * WebP Converter functionality
 *
 * Converts JPG, JPEG, and PNG images to WebP format.
 * Integrated from the standalone MBR WebP Converter plugin.
 *
 * @package MBR_WP_Performance
 * @since   1.6.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * WebP Converter class
 */
class MBR_WP_Performance_WebP_Converter {

    /**
     * Single instance
     *
     * @var MBR_WP_Performance_WebP_Converter
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return MBR_WP_Performance_WebP_Converter
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $options = mbr_wp_performance()->get_options( 'webp' );

        // Auto-convert on upload.
        if ( ! empty( $options['auto_convert'] ) ) {
            add_filter( 'wp_generate_attachment_metadata', array( $this, 'handle_new_upload' ), 10, 2 );
        }

        // Front-end image wrapping (only when enabled and not in admin/editor).
        if ( ! empty( $options['picture_tags'] ) ) {
            add_filter( 'wp_get_attachment_image', array( $this, 'wrap_attachment_image' ), 20, 1 );
            add_filter( 'post_thumbnail_html', array( $this, 'wrap_post_thumbnail' ), 20, 1 );
            add_filter( 'the_content', array( $this, 'wrap_content_images' ), 20 );

            // Gutenberg block support.
            add_filter( 'render_block', array( $this, 'wrap_block_images' ), 20, 2 );

            // Elementor integration.
            if ( class_exists( '\Elementor\Plugin' ) ) {
                add_filter( 'elementor/widget/render_content', array( $this, 'wrap_elementor_images' ), 10, 2 );
            }
        }

        // .htaccess delivery rules.
        if ( ! empty( $options['htaccess_rules'] ) ) {
            // Rules are written on activation; nothing to hook at runtime.
        }

        // Registry migration check (lightweight, runs once).
        add_action( 'admin_init', array( $this, 'populate_registry_from_history' ) );
    }

    /* ======================================================================
     * CONVERSION ENGINE
     * ==================================================================== */

    /**
     * Convert a single image to WebP.
     *
     * @param string $full_path Absolute path to the source image.
     * @return array|false Result array on success/skip, false on failure.
     */
    public function convert_single_image( $full_path ) {
        if ( ! file_exists( $full_path ) ) {
            return false;
        }

        $options           = mbr_wp_performance()->get_options( 'webp' );
        $compression_level = isset( $options['compression_level'] ) ? intval( $options['compression_level'] ) : 75;

        $image     = null;
        $extension = strtolower( pathinfo( $full_path, PATHINFO_EXTENSION ) );

        if ( 'jpg' === $extension || 'jpeg' === $extension ) {
            $image = @imagecreatefromjpeg( $full_path );
        } elseif ( 'png' === $extension ) {
            $image = @imagecreatefrompng( $full_path );
            if ( $image ) {
                imagepalettetotruecolor( $image );
                imagealphablending( $image, true );
                imagesavealpha( $image, true );
            }
        }

        if ( ! $image ) {
            return false;
        }

        $webp_path = preg_replace( '/\.(jpe?g|png)$/i', '.webp', $full_path );
        $result    = @imagewebp( $image, $webp_path, $compression_level );
        imagedestroy( $image );

        if ( ! $result || ! file_exists( $webp_path ) ) {
            return false;
        }

        $original_size = filesize( $full_path );
        $webp_size     = filesize( $webp_path );

        // If WebP is larger, remove it and skip.
        if ( $webp_size >= $original_size ) {
            wp_delete_file( $webp_path );
            return array(
                'status'        => 'skipped',
                'original_path' => $full_path,
                'webp_path'     => $webp_path,
                'original_size' => $original_size,
                'webp_size'     => $webp_size,
            );
        }

        // Register in our WebP file registry.
        $upload_dir       = wp_upload_dir();
        $webp_relative    = str_replace( $upload_dir['basedir'] . '/', '', $webp_path );
        $this->register_webp_file( $webp_relative );

        return array(
            'status'        => 'success',
            'original_path' => $full_path,
            'webp_path'     => $webp_path,
            'original_size' => $original_size,
            'webp_size'     => $webp_size,
        );
    }

    /**
     * Auto-convert images on upload.
     *
     * @param array $metadata      Attachment metadata.
     * @param int   $attachment_id Attachment post ID.
     * @return array
     */
    public function handle_new_upload( $metadata, $attachment_id ) {
        $upload_dir       = wp_upload_dir();
        $converted_images = get_option( 'mbr_webp_converted_images', array() );
        $files_to_convert = array();

        // Main file.
        if ( isset( $metadata['file'] ) ) {
            $main_file = $upload_dir['basedir'] . '/' . $metadata['file'];
            $ext       = strtolower( pathinfo( $main_file, PATHINFO_EXTENSION ) );
            if ( in_array( $ext, array( 'jpg', 'jpeg', 'png' ), true ) ) {
                $files_to_convert[] = $main_file;
            }
        }

        // Sub-sizes.
        if ( isset( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
            $base_dir = $upload_dir['basedir'] . '/' . dirname( $metadata['file'] );
            foreach ( $metadata['sizes'] as $size ) {
                $size_ext = strtolower( pathinfo( $size['file'], PATHINFO_EXTENSION ) );
                if ( in_array( $size_ext, array( 'jpg', 'jpeg', 'png' ), true ) ) {
                    $files_to_convert[] = $base_dir . '/' . $size['file'];
                }
            }
        }

        foreach ( array_unique( $files_to_convert ) as $full_path ) {
            $result = $this->convert_single_image( $full_path );
            if ( $result && 'success' === $result['status'] ) {
                $converted_images[] = array(
                    'original_path' => str_replace( $upload_dir['basedir'] . '/', '', $result['original_path'] ),
                    'webp_path'     => str_replace( $upload_dir['basedir'] . '/', '', $result['webp_path'] ),
                    'original_size' => $result['original_size'],
                    'webp_size'     => $result['webp_size'],
                );
            }
        }

        update_option( 'mbr_webp_converted_images', $converted_images );
        return $metadata;
    }

    /* ======================================================================
     * REGISTRY
     * ==================================================================== */

    /**
     * Register a WebP file in the registry.
     *
     * @param string $webp_relative_path Relative path from uploads dir.
     */
    public function register_webp_file( $webp_relative_path ) {
        $registry = get_option( 'mbr_webp_registry', array() );
        if ( ! in_array( $webp_relative_path, $registry, true ) ) {
            $registry[] = $webp_relative_path;
            update_option( 'mbr_webp_registry', $registry );
        }
    }

    /**
     * Get the WebP file registry.
     *
     * @return array
     */
    public function get_webp_registry() {
        return get_option( 'mbr_webp_registry', array() );
    }

    /**
     * Clear the WebP file registry.
     */
    public function clear_webp_registry() {
        delete_option( 'mbr_webp_registry' );
    }

    /**
     * Populate registry from conversion history (one-time migration).
     */
    public function populate_registry_from_history() {
        if ( get_option( 'mbr_webp_registry_migrated', false ) ) {
            return;
        }

        $registry         = get_option( 'mbr_webp_registry', array() );
        $converted_images = get_option( 'mbr_webp_converted_images', array() );

        // Also migrate from the old standalone plugin option.
        if ( empty( $converted_images ) ) {
            $converted_images = get_option( 'itwc_converted_images', array() );
        }

        if ( ! empty( $converted_images ) && is_array( $converted_images ) ) {
            foreach ( $converted_images as $image ) {
                if ( isset( $image['webp_path'] ) && ! in_array( $image['webp_path'], $registry, true ) ) {
                    $registry[] = $image['webp_path'];
                }
            }
            update_option( 'mbr_webp_registry', $registry );
        }

        update_option( 'mbr_webp_registry_migrated', true );
    }

    /* ======================================================================
     * FRONT-END <picture> TAG WRAPPING
     * ==================================================================== */

    /**
     * Check whether we should skip WebP wrapping in the current context.
     *
     * @return bool
     */
    private function skip_webp_wrapping() {
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
            if ( $screen && 'post' === $screen->base && $screen->is_block_editor ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Convert a URL to its local filesystem path.
     *
     * @param string $url Image URL.
     * @return string|false
     */
    private function url_to_local_path( $url ) {
        $uploads = wp_get_upload_dir();
        if ( strpos( $url, $uploads['baseurl'] ) === 0 ) {
            $rel = substr( $url, strlen( $uploads['baseurl'] ) );
            return wp_normalize_path( $uploads['basedir'] . $rel );
        }
        return false;
    }

    /**
     * Convert a local path to its URL.
     *
     * @param string $path Local file path.
     * @return string|false
     */
    private function local_path_to_url( $path ) {
        $uploads = wp_get_upload_dir();
        $basedir = wp_normalize_path( $uploads['basedir'] );
        $npath   = wp_normalize_path( $path );
        if ( strpos( $npath, $basedir ) === 0 ) {
            return $uploads['baseurl'] . substr( $npath, strlen( $basedir ) );
        }
        return false;
    }

    /**
     * Find the WebP variant URL for a given image URL.
     *
     * @param string $url Original image URL.
     * @return string|false WebP URL or false.
     */
    private function webp_variant_for_url( $url ) {
        $p = $this->url_to_local_path( $url );
        if ( ! $p ) {
            return false;
        }

        // Try appending .webp.
        $candidate1 = $p . '.webp';
        if ( file_exists( $candidate1 ) ) {
            return $this->local_path_to_url( $candidate1 );
        }

        // Try replacing extension with .webp.
        $candidate2 = preg_replace( '/\.(jpe?g|png)$/i', '.webp', $p );
        if ( $candidate2 && file_exists( $candidate2 ) ) {
            return $this->local_path_to_url( $candidate2 );
        }

        return false;
    }

    /**
     * Build a WebP srcset from an original srcset string.
     *
     * @param string $srcset Original srcset attribute.
     * @return string WebP srcset.
     */
    private function build_webp_srcset( $srcset ) {
        if ( empty( $srcset ) ) {
            return '';
        }

        $parts = array_map( 'trim', explode( ',', $srcset ) );
        $out   = array();

        foreach ( $parts as $part ) {
            if ( preg_match( '~\s+(\d+w|\d+x)$~', $part, $m ) ) {
                $descriptor = $m[1];
                $url        = trim( substr( $part, 0, -strlen( $m[0] ) ) );
            } else {
                $url        = $part;
                $descriptor = '';
            }

            $webp = $this->webp_variant_for_url( $url );
            if ( $webp ) {
                $out[] = trim( $webp . ( $descriptor ? ' ' . $descriptor : '' ) );
            }
        }

        return implode( ', ', $out );
    }

    /**
     * Wrap a single <img> tag with a <picture> element containing a WebP <source>.
     *
     * @param string $img_html The <img> HTML.
     * @return string Wrapped HTML or original.
     */
    public function wrap_img_with_picture( $img_html ) {
        if ( ! is_string( $img_html ) || stripos( $img_html, '<img' ) === false ) {
            return $img_html;
        }

        $src    = '';
        $srcset = '';

        if ( preg_match( '/\ssrc=["\']([^"\']+)["\']/', $img_html, $m ) ) {
            $src = $m[1];
        }
        if ( preg_match( '/\ssrcset=["\']([^"\']+)["\']/', $img_html, $m2 ) ) {
            $srcset = $m2[1];
        }

        $webp_srcset = $this->build_webp_srcset( $srcset );
        $webp_src    = $this->webp_variant_for_url( $src );

        // Look up AVIF variants when the AVIF converter is loaded and enabled.
        $avif_src    = false;
        $avif_srcset = '';
        $opts        = mbr_wp_performance()->get_options( 'webp' );
        if ( ! empty( $opts['avif_enabled'] ) && class_exists( 'MBR_WP_Performance_AVIF_Converter' ) ) {
            $avif_src    = MBR_WP_Performance_AVIF_Converter::avif_variant_for_url( $src );
            $avif_srcset = MBR_WP_Performance_AVIF_Converter::build_avif_srcset( $srcset );
        }

        if ( ! $webp_src && ! $webp_srcset && ! $avif_src && ! $avif_srcset ) {
            return $img_html;
        }

        $sources = '';

        // AVIF first — browsers pick the first <source> they understand.
        if ( $avif_srcset ) {
            $sources .= '<source type="image/avif" srcset="' . esc_attr( $avif_srcset ) . '">';
        } elseif ( $avif_src ) {
            $sources .= '<source type="image/avif" srcset="' . esc_attr( $avif_src ) . '">';
        }

        if ( $webp_srcset ) {
            $sources .= '<source type="image/webp" srcset="' . esc_attr( $webp_srcset ) . '">';
        } elseif ( $webp_src ) {
            $sources .= '<source type="image/webp" srcset="' . esc_attr( $webp_src ) . '">';
        }

        return '<picture>' . $sources . $img_html . '</picture>';
    }

    /* -- Filter callbacks ------------------------------------------------ */

    public function wrap_attachment_image( $html ) {
        if ( $this->skip_webp_wrapping() ) {
            return $html;
        }
        return $this->wrap_img_with_picture( $html );
    }

    public function wrap_post_thumbnail( $html ) {
        if ( $this->skip_webp_wrapping() ) {
            return $html;
        }
        return $this->wrap_img_with_picture( $html );
    }

    public function wrap_content_images( $content ) {
        if ( $this->skip_webp_wrapping() ) {
            return $content;
        }
        if ( false === stripos( $content, '<img' ) ) {
            return $content;
        }
        return preg_replace_callback( '/<img[^>]*>/', function ( $m ) {
            return $this->wrap_img_with_picture( $m[0] );
        }, $content );
    }

    public function wrap_block_images( $block_content, $parsed_block ) {
        if ( 'core/image' !== $parsed_block['blockName'] && 'core/gallery' !== $parsed_block['blockName'] ) {
            return $block_content;
        }
        return preg_replace_callback( '/<img[^>]*>/', function ( $m ) {
            return $this->wrap_img_with_picture( $m[0] );
        }, $block_content );
    }

    public function wrap_elementor_images( $content, $widget ) {
        $widget_names = array( 'image', 'image-gallery' );
        if ( ! in_array( $widget->get_name(), $widget_names, true ) ) {
            return $content;
        }
        return preg_replace_callback( '/<img[^>]*>/', function ( $m ) {
            return $this->wrap_img_with_picture( $m[0] );
        }, $content );
    }

    /* ======================================================================
     * .htaccess RULES
     * ==================================================================== */

    /**
     * Get .htaccess rules for WebP delivery.
     *
     * @return array
     */
    public static function htaccess_rules() {
        return array(
            '# Serve WebP to browsers that support it (originals must remain on disk)',
            '<IfModule mod_rewrite.c>',
            'RewriteEngine On',
            'RewriteCond %{HTTP_ACCEPT} image/webp',
            'RewriteCond %{DOCUMENT_ROOT}/$1.webp -f',
            'RewriteRule ^(.*)\.(jpe?g|png)$ $1.webp [T=image/webp,E=webpserve:1,L]',
            '</IfModule>',
            '<IfModule mod_headers.c>',
            'Header append Vary: Accept env=webpserve',
            '</IfModule>',
            '<IfModule mod_mime.c>',
            'AddType image/webp .webp',
            '</IfModule>',
        );
    }

    /**
     * Write .htaccess rules.
     */
    public static function add_htaccess_rules() {
        if ( ! function_exists( 'insert_with_markers' ) ) {
            require_once ABSPATH . 'wp-admin/includes/misc.php';
        }
        if ( ! function_exists( 'get_home_path' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        $htaccess = trailingslashit( get_home_path() ) . '.htaccess';
        @insert_with_markers( $htaccess, 'MBR WebP', self::htaccess_rules() );
    }

    /**
     * Remove .htaccess rules.
     */
    public static function remove_htaccess_rules() {
        if ( ! function_exists( 'insert_with_markers' ) ) {
            require_once ABSPATH . 'wp-admin/includes/misc.php';
        }
        if ( ! function_exists( 'get_home_path' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        $htaccess = trailingslashit( get_home_path() ) . '.htaccess';
        @insert_with_markers( $htaccess, 'MBR WebP', array() );
    }

    /* ======================================================================
     * SERVER DIAGNOSTICS
     * ==================================================================== */

    /**
     * Run server capability checks for WebP conversion.
     *
     * @return array Associative array of check results.
     */
    public static function get_diagnostics() {
        $checks = array();

        // GD Library.
        $checks['gd_installed'] = extension_loaded( 'gd' );

        // WebP support in GD.
        $gd_info               = function_exists( 'gd_info' ) ? gd_info() : array();
        $checks['webp_support'] = ! empty( $gd_info['WebP Support'] );

        // Uploads writable.
        $upload_dir              = wp_upload_dir();
        $checks['uploads_writable'] = wp_is_writable( $upload_dir['basedir'] );

        // All OK?
        $checks['all_ok'] = $checks['gd_installed'] && $checks['webp_support'] && $checks['uploads_writable'];

        return $checks;
    }

    /* ======================================================================
     * CLEANUP ON DEACTIVATION
     * ==================================================================== */

    /**
     * Clean up WebP files created by this plugin.
     */
    public static function cleanup_on_deactivation() {
        $upload_dir   = wp_upload_dir();
        $webp_registry = get_option( 'mbr_webp_registry', array() );

        if ( ! empty( $webp_registry ) && is_array( $webp_registry ) ) {
            foreach ( $webp_registry as $webp_relative_path ) {
                $webp_full_path = $upload_dir['basedir'] . '/' . $webp_relative_path;
                if ( file_exists( $webp_full_path ) ) {
                    wp_delete_file( $webp_full_path );
                }
            }
        }

        // Remove .htaccess rules.
        self::remove_htaccess_rules();

        // Clear plugin data.
        delete_option( 'mbr_webp_converted_images' );
        delete_option( 'mbr_webp_registry_migrated' );
        delete_option( 'mbr_webp_registry' );

        // Clear Elementor cache if active.
        if ( class_exists( '\Elementor\Plugin' ) ) {
            \Elementor\Plugin::instance()->files_manager->clear_cache();
        }
    }
}
