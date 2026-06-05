<?php
/**
 * Server Headers
 *
 * Writes .htaccess rules for:
 *   - Browser caching (mod_expires + Cache-Control)
 *   - Text compression (mod_brotli + mod_deflate)
 *
 * Addresses two common PageSpeed Insights warnings:
 *   - "Serve static assets with an efficient cache policy"
 *   - "Enable text compression"
 *
 * On Nginx, .htaccess is ignored; the admin tab surfaces an info notice and
 * shows the equivalent server config snippet to copy.
 *
 * @package MBRPE
 * @since   1.12.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBRPE_Server_Headers {

    /**
     * Single instance.
     *
     * @var MBRPE_Server_Headers
     */
    private static $instance = null;

    /**
     * Options.
     *
     * @var array
     */
    private $options = array();

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
        $this->options = mbrpe()->get_options( 'server_headers' );

        // React to option changes — re-write rules on save.
        add_action( 'update_option_mbrpe_options', array( $this, 'on_options_updated' ), 10, 2 );

        // Re-apply on init in case the file got cleared by another tool.
        add_action( 'admin_init', array( $this, 'ensure_rules' ) );
    }

    /**
     * Detect web server.
     *
     * @return string 'apache' | 'litespeed' | 'nginx' | 'iis' | 'other'
     */
    public static function detect_server() {
        $sig = isset( $_SERVER['SERVER_SOFTWARE'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) ) : '';
        if ( false !== strpos( $sig, 'litespeed' ) ) {
            return 'litespeed';
        }
        if ( false !== strpos( $sig, 'apache' ) ) {
            return 'apache';
        }
        if ( false !== strpos( $sig, 'nginx' ) ) {
            return 'nginx';
        }
        if ( false !== strpos( $sig, 'iis' ) ) {
            return 'iis';
        }
        return 'other';
    }

    /**
     * Re-write .htaccess rules when settings change.
     *
     * @param mixed $old
     * @param mixed $new
     */
    public function on_options_updated( $old, $new ) {
        $this->options = mbrpe()->get_options( 'server_headers' );
        $this->ensure_rules();
    }

    /**
     * Idempotently apply current rules.
     */
    public function ensure_rules() {
        $server = self::detect_server();
        if ( ! in_array( $server, array( 'apache', 'litespeed' ), true ) ) {
            return; // .htaccess only.
        }

        if ( ! empty( $this->options['browser_cache'] ) ) {
            self::write_block( 'MBR Browser Cache', self::browser_cache_rules() );
        } else {
            self::write_block( 'MBR Browser Cache', array() );
        }

        if ( ! empty( $this->options['gzip_compression'] ) ) {
            self::write_block( 'MBR Compression', self::compression_rules() );
        } else {
            self::write_block( 'MBR Compression', array() );
        }
    }

    /**
     * Cache rules — long expiry for fingerprintable static assets, shorter for HTML.
     *
     * @return string[]
     */
    public static function browser_cache_rules() {
        return array(
            '<IfModule mod_expires.c>',
            'ExpiresActive On',
            'ExpiresDefault "access plus 1 month"',
            '# Images & video — assets with stable URLs, safe to cache hard',
            'ExpiresByType image/jpeg "access plus 1 year"',
            'ExpiresByType image/png  "access plus 1 year"',
            'ExpiresByType image/gif  "access plus 1 year"',
            'ExpiresByType image/webp "access plus 1 year"',
            'ExpiresByType image/avif "access plus 1 year"',
            'ExpiresByType image/svg+xml "access plus 1 year"',
            'ExpiresByType video/mp4  "access plus 1 year"',
            'ExpiresByType video/webm "access plus 1 year"',
            '# Fonts',
            'ExpiresByType font/woff  "access plus 1 year"',
            'ExpiresByType font/woff2 "access plus 1 year"',
            'ExpiresByType application/font-woff  "access plus 1 year"',
            'ExpiresByType application/font-woff2 "access plus 1 year"',
            '# CSS / JS — versioned via ?ver= or hash, 1 month is conservative-safe',
            'ExpiresByType text/css        "access plus 1 month"',
            'ExpiresByType application/javascript "access plus 1 month"',
            'ExpiresByType text/javascript "access plus 1 month"',
            '# HTML — short, so content updates are visible',
            'ExpiresByType text/html "access plus 0 seconds"',
            '# Feeds',
            'ExpiresByType application/rss+xml  "access plus 1 hour"',
            'ExpiresByType application/atom+xml "access plus 1 hour"',
            '</IfModule>',
            '<IfModule mod_headers.c>',
            '# Cache-Control belt-and-braces for proxies that ignore Expires',
            '<FilesMatch "\.(jpe?g|png|gif|webp|avif|svg|ico|woff2?|ttf|otf|eot|mp4|webm)$">',
            '    Header set Cache-Control "public, max-age=31536000, immutable"',
            '</FilesMatch>',
            '<FilesMatch "\.(css|js)$">',
            '    Header set Cache-Control "public, max-age=2592000"',
            '</FilesMatch>',
            '</IfModule>',
        );
    }

    /**
     * Brotli + Gzip text compression.
     *
     * @return string[]
     */
    public static function compression_rules() {
        return array(
            '# Prefer Brotli where available, fall back to gzip',
            '<IfModule mod_brotli.c>',
            'AddOutputFilterByType BROTLI_COMPRESS text/html text/plain text/xml text/css text/javascript',
            'AddOutputFilterByType BROTLI_COMPRESS application/javascript application/x-javascript application/json',
            'AddOutputFilterByType BROTLI_COMPRESS application/xml application/rss+xml application/atom+xml',
            'AddOutputFilterByType BROTLI_COMPRESS image/svg+xml font/woff font/woff2',
            '</IfModule>',
            '<IfModule mod_deflate.c>',
            'AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript',
            'AddOutputFilterByType DEFLATE application/javascript application/x-javascript application/json',
            'AddOutputFilterByType DEFLATE application/xml application/rss+xml application/atom+xml',
            'AddOutputFilterByType DEFLATE image/svg+xml font/woff font/woff2',
            '</IfModule>',
        );
    }

    /**
     * Write a marker block, or remove it if rules array is empty.
     *
     * @param string   $marker
     * @param string[] $rules
     * @return bool
     */
    private static function write_block( $marker, $rules ) {
        if ( ! function_exists( 'insert_with_markers' ) ) {
            require_once ABSPATH . 'wp-admin/includes/misc.php';
        }
        if ( ! function_exists( 'get_home_path' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        $htaccess = trailingslashit( get_home_path() ) . '.htaccess';
        return (bool) @insert_with_markers( $htaccess, $marker, $rules );
    }

    /**
     * Remove all rules on deactivation.
     */
    public static function cleanup_on_deactivation() {
        self::write_block( 'MBR Browser Cache', array() );
        self::write_block( 'MBR Compression', array() );
    }

    /**
     * Equivalent Nginx config snippet (informational — to display in admin).
     *
     * @return string
     */
    public static function nginx_snippet() {
        return '# Cache headers
location ~* \.(jpe?g|png|gif|webp|avif|svg|ico|woff2?|ttf|otf|eot|mp4|webm)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}
location ~* \.(css|js)$ {
    expires 30d;
    add_header Cache-Control "public";
}

# Gzip (Brotli requires the ngx_brotli module)
gzip on;
gzip_vary on;
gzip_types text/plain text/css text/xml application/json application/javascript
           application/xml+rss application/atom+xml image/svg+xml font/woff font/woff2;';
    }
}
