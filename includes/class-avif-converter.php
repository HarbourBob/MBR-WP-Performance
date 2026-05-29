<?php
/**
 * AVIF Converter
 *
 * Mirrors the WebP converter but produces AVIF (.avif) siblings.
 * When both AVIF and WebP are enabled, the <picture> wrapper emits:
 *
 *   <picture>
 *     <source type="image/avif" srcset="...">
 *     <source type="image/webp" srcset="...">
 *     <img ...>
 *   </picture>
 *
 * AVIF is typically 20-30% smaller than WebP at equivalent perceived quality
 * and is now broadly supported (Chrome 85+, Firefox 93+, Safari 16.4+).
 *
 * Requires PHP 8.1+ with GD compiled with AVIF support, OR Imagick 7.0.25+.
 * Falls back gracefully when AVIF encoding isn't available.
 *
 * @package MBR_WP_Performance
 * @since   1.12.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBR_WP_Performance_AVIF_Converter {

    /**
     * Single instance.
     *
     * @var MBR_WP_Performance_AVIF_Converter
     */
    private static $instance = null;

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
     * Constructor.
     */
    private function __construct() {
        $options = mbr_wp_performance()->get_options( 'webp' );

        if ( empty( $options['avif_enabled'] ) ) {
            return;
        }

        // If the user has AVIF enabled but the server can't actually encode
        // AVIF, hooking the upload filter just triggers per-upload warnings
        // and produces no .avif files. Surface the mismatch as an admin
        // notice on the plugin's own screens instead, and don't hook the
        // converter. The user can either fix the server stack or disable
        // the toggle.
        if ( ! self::avif_supported() ) {
            add_action( 'admin_notices', array( $this, 'notice_avif_unsupported' ) );
            return;
        }

        // Auto-convert on upload (alongside WebP).
        if ( ! empty( $options['auto_convert'] ) ) {
            add_filter( 'wp_generate_attachment_metadata', array( $this, 'handle_new_upload' ), 11, 2 );
        }
    }

    /**
     * Render an admin notice on the plugin's own pages when AVIF is enabled
     * in settings but the server has no working AVIF encoder.
     */
    public function notice_avif_unsupported() {
        if ( ! function_exists( 'get_current_screen' ) ) {
            return;
        }
        $screen = get_current_screen();
        if ( ! $screen || false === strpos( $screen->id, 'mbr-wp-performance' ) ) {
            return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        echo '<div class="notice notice-warning"><p>';
        echo esc_html__( 'MBR WP Performance: AVIF conversion is enabled, but this server has no working AVIF encoder. New uploads are not being converted to .avif and visitors are being served WebP (or the original) instead. See the AVIF section on the WebP tab for diagnostics — typically the server needs PHP 8.1+ with GD built against libavif, or Imagick built against libheif/libde265.', 'mbr-wp-performance' );
        echo '</p></div>';
    }

    /**
     * Whether the server supports AVIF encoding at all.
     *
     * Detection notes:
     *
     * - GD: function_exists('imageavif') is NOT a reliable check. PHP 8.1+
     *   declares the function symbol whether or not libgd was actually built
     *   against libavif; on the (very common) shared-host configuration
     *   where it wasn't, calling imageavif() just emits a warning and
     *   returns false. gd_info()['AVIF Support'] reflects what libgd was
     *   actually compiled with, so it's the only trustworthy GD signal
     *   here — and it's the same pattern the WebP converter already uses
     *   via gd_info()['WebP Support'].
     *
     * - Imagick: queryFormats() listing 'AVIF' is generally reliable, since
     *   it reports formats ImageMagick can actually delegate to libheif.
     *
     * @return bool
     */
    public static function avif_supported() {
        if ( function_exists( 'gd_info' ) ) {
            $info = @gd_info();
            if ( is_array( $info ) && ! empty( $info['AVIF Support'] ) ) {
                return true;
            }
        }
        if ( class_exists( 'Imagick' ) ) {
            $formats = @\Imagick::queryFormats();
            if ( is_array( $formats ) && in_array( 'AVIF', $formats, true ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Convert a single image to AVIF.
     *
     * @param string $full_path Absolute path to source image.
     * @return array|false
     */
    public function convert_single_image( $full_path ) {
        if ( ! file_exists( $full_path ) || ! self::avif_supported() ) {
            return false;
        }

        $options = mbr_wp_performance()->get_options( 'webp' );
        $quality = isset( $options['avif_quality'] ) ? (int) $options['avif_quality'] : 60;
        if ( $quality < 1 || $quality > 100 ) {
            $quality = 60;
        }
        $extension = strtolower( pathinfo( $full_path, PATHINFO_EXTENSION ) );

        $avif_path = preg_replace( '/\.(jpe?g|png)$/i', '.avif', $full_path );
        if ( $avif_path === $full_path ) {
            return false; // No extension match.
        }

        $ok = false;

        // Try GD first.
        if ( function_exists( 'imageavif' ) ) {
            $image = null;
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
            if ( $image ) {
                $ok = @imageavif( $image, $avif_path, $quality );
                imagedestroy( $image );
            }
        }

        // Fall back to Imagick.
        if ( ! $ok && class_exists( 'Imagick' ) ) {
            try {
                $img = new \Imagick( $full_path );
                $img->setImageFormat( 'AVIF' );
                $img->setImageCompressionQuality( $quality );
                $ok = $img->writeImage( $avif_path );
                $img->clear();
            } catch ( \Exception $e ) {
                $ok = false;
            }
        }

        if ( ! $ok || ! file_exists( $avif_path ) ) {
            return false;
        }

        $original_size = filesize( $full_path );
        $avif_size     = filesize( $avif_path );

        // If AVIF is larger, skip it.
        if ( $avif_size >= $original_size ) {
            wp_delete_file( $avif_path );
            return array(
                'status'        => 'skipped',
                'original_path' => $full_path,
                'avif_path'     => $avif_path,
                'original_size' => $original_size,
                'avif_size'     => $avif_size,
            );
        }

        $upload_dir    = wp_upload_dir();
        $avif_relative = str_replace( $upload_dir['basedir'] . '/', '', $avif_path );
        $this->register_avif_file( $avif_relative );

        return array(
            'status'        => 'success',
            'original_path' => $full_path,
            'avif_path'     => $avif_path,
            'original_size' => $original_size,
            'avif_size'     => $avif_size,
        );
    }

    /**
     * Auto-convert on upload.
     *
     * @param array $metadata
     * @param int   $attachment_id
     * @return array
     */
    public function handle_new_upload( $metadata, $attachment_id ) {
        $upload_dir = wp_upload_dir();
        $to_convert = array();

        if ( isset( $metadata['file'] ) ) {
            $main = $upload_dir['basedir'] . '/' . $metadata['file'];
            $ext  = strtolower( pathinfo( $main, PATHINFO_EXTENSION ) );
            if ( in_array( $ext, array( 'jpg', 'jpeg', 'png' ), true ) ) {
                $to_convert[] = $main;
            }
        }

        if ( isset( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
            $base_dir = $upload_dir['basedir'] . '/' . dirname( $metadata['file'] );
            foreach ( $metadata['sizes'] as $size ) {
                $size_ext = strtolower( pathinfo( $size['file'], PATHINFO_EXTENSION ) );
                if ( in_array( $size_ext, array( 'jpg', 'jpeg', 'png' ), true ) ) {
                    $to_convert[] = $base_dir . '/' . $size['file'];
                }
            }
        }

        foreach ( array_unique( $to_convert ) as $full_path ) {
            $this->convert_single_image( $full_path );
        }

        return $metadata;
    }

    /**
     * Register an AVIF file path.
     */
    public function register_avif_file( $relative ) {
        $registry = get_option( 'mbr_avif_registry', array() );
        if ( ! in_array( $relative, $registry, true ) ) {
            $registry[] = $relative;
            update_option( 'mbr_avif_registry', $registry );
        }
    }

    /**
     * Look up the AVIF variant URL for a given image URL.
     *
     * @param string $url
     * @return string|false
     */
    public static function avif_variant_for_url( $url ) {
        $uploads = wp_get_upload_dir();
        if ( strpos( $url, $uploads['baseurl'] ) !== 0 ) {
            return false;
        }
        $rel  = substr( $url, strlen( $uploads['baseurl'] ) );
        $path = wp_normalize_path( $uploads['basedir'] . $rel );

        // .ext → .avif
        $candidate = preg_replace( '/\.(jpe?g|png)$/i', '.avif', $path );
        if ( $candidate && file_exists( $candidate ) ) {
            $rel2 = substr( $candidate, strlen( wp_normalize_path( $uploads['basedir'] ) ) );
            return $uploads['baseurl'] . $rel2;
        }
        return false;
    }

    /**
     * Build an AVIF srcset from an original srcset string.
     *
     * @param string $srcset
     * @return string
     */
    public static function build_avif_srcset( $srcset ) {
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
            $avif = self::avif_variant_for_url( $url );
            if ( $avif ) {
                $out[] = trim( $avif . ( $descriptor ? ' ' . $descriptor : '' ) );
            }
        }
        return implode( ', ', $out );
    }

    /**
     * .htaccess rewrite rules for AVIF delivery.
     *
     * @return array
     */
    public static function htaccess_rules() {
        return array(
            '# Serve AVIF to browsers that support it',
            '<IfModule mod_rewrite.c>',
            'RewriteEngine On',
            'RewriteCond %{HTTP_ACCEPT} image/avif',
            'RewriteCond %{DOCUMENT_ROOT}/$1.avif -f',
            'RewriteRule ^(.*)\.(jpe?g|png)$ $1.avif [T=image/avif,E=avifserve:1,L]',
            '</IfModule>',
            '<IfModule mod_headers.c>',
            'Header append Vary: Accept env=avifserve',
            '</IfModule>',
            '<IfModule mod_mime.c>',
            'AddType image/avif .avif',
            '</IfModule>',
        );
    }

    /**
     * Write AVIF .htaccess rules.
     */
    public static function add_htaccess_rules() {
        if ( ! function_exists( 'insert_with_markers' ) ) {
            require_once ABSPATH . 'wp-admin/includes/misc.php';
        }
        if ( ! function_exists( 'get_home_path' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        $htaccess = trailingslashit( get_home_path() ) . '.htaccess';
        @insert_with_markers( $htaccess, 'MBR AVIF', self::htaccess_rules() );
    }

    /**
     * Remove AVIF .htaccess rules.
     */
    public static function remove_htaccess_rules() {
        if ( ! function_exists( 'insert_with_markers' ) ) {
            require_once ABSPATH . 'wp-admin/includes/misc.php';
        }
        if ( ! function_exists( 'get_home_path' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        $htaccess = trailingslashit( get_home_path() ) . '.htaccess';
        @insert_with_markers( $htaccess, 'MBR AVIF', array() );
    }

    /**
     * Diagnostics — server-side AVIF capability.
     *
     * @return array
     */
    public static function get_diagnostics() {
        $gd_avif = false;
        if ( function_exists( 'gd_info' ) ) {
            $info    = @gd_info();
            $gd_avif = is_array( $info ) && ! empty( $info['AVIF Support'] );
        }
        $imagick_avif = false;
        if ( class_exists( 'Imagick' ) ) {
            $formats      = @\Imagick::queryFormats();
            $imagick_avif = is_array( $formats ) && in_array( 'AVIF', $formats, true );
        }
        return array(
            'gd_avif'      => $gd_avif,
            'imagick_avif' => $imagick_avif,
            'any_avif'     => self::avif_supported(),
        );
    }

    /**
     * Clean up AVIF files on plugin deactivation.
     */
    public static function cleanup_on_deactivation() {
        $upload_dir = wp_upload_dir();
        $registry   = get_option( 'mbr_avif_registry', array() );
        if ( ! empty( $registry ) && is_array( $registry ) ) {
            foreach ( $registry as $rel ) {
                $full = $upload_dir['basedir'] . '/' . $rel;
                if ( file_exists( $full ) ) {
                    wp_delete_file( $full );
                }
            }
        }
        self::remove_htaccess_rules();
        delete_option( 'mbr_avif_registry' );
    }
}
