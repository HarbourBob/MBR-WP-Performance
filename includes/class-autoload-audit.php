<?php
/**
 * Autoloaded Options Audit
 *
 * Bloated wp_options entries with autoload=yes are loaded on every page hit
 * and are the single most common WordPress DB perf killer. This module
 * surfaces the top offenders and lets the user flip autoload off on
 * options that don't need it.
 *
 * The AJAX side runs from class-admin.php; this class provides the
 * underlying queries and the toggle operation.
 *
 * @package MBR_WP_Performance
 * @since   1.12.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBR_WP_Performance_Autoload_Audit {

    /**
     * Single instance.
     *
     * @var MBR_WP_Performance_Autoload_Audit
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
        // No runtime hooks — admin-side only via class-admin AJAX handlers.
    }

    /**
     * Top N autoloaded options by raw value size.
     *
     * Excludes our own options and a small allowlist of options that genuinely
     * need to autoload (siteurl, home, blogname, template, stylesheet, etc).
     *
     * @param int $limit
     * @return array of objects { option_name, size, size_h }
     */
    public static function top_autoloaded( $limit = 30 ) {
        global $wpdb;
        $limit = max( 1, min( 200, (int) $limit ) );

        // Exclude the plugin's own options — the docblock promises this, and
        // they're small, legitimately autoloaded, and not something we want to
        // invite the user to disable via our own tool.
        $own_prefix = $wpdb->esc_like( 'mbr_wp_performance_' ) . '%';

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, LENGTH(option_value) AS size
                 FROM {$wpdb->options}
                 WHERE autoload IN ('yes','on')
                   AND option_name NOT LIKE %s
                 ORDER BY size DESC
                 LIMIT %d",
                $own_prefix,
                $limit
            )
        );

        if ( empty( $rows ) ) {
            return array();
        }

        foreach ( $rows as $r ) {
            $r->size_h        = size_format( (int) $r->size );
            $r->is_protected  = self::is_protected_option( $r->option_name );
            $r->is_transient  = self::is_transient_option( $r->option_name );
        }
        return $rows;
    }

    /**
     * Total bytes autoloaded per page load.
     *
     * @return int
     */
    public static function total_autoloaded_size() {
        global $wpdb;
        return (int) $wpdb->get_var(
            "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE autoload IN ('yes','on')"
        );
    }

    /**
     * Count of autoloaded options.
     *
     * @return int
     */
    public static function total_autoloaded_count() {
        global $wpdb;
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} WHERE autoload IN ('yes','on')"
        );
    }

    /**
     * Toggle the autoload flag for an option.
     *
     * Refuses to operate on protected options.
     *
     * @param string $option_name
     * @param bool   $autoload    True to autoload, false to disable.
     * @return bool Success.
     */
    public static function set_autoload( $option_name, $autoload ) {
        if ( '' === $option_name || self::is_protected_option( $option_name ) ) {
            return false;
        }
        global $wpdb;
        $new = $autoload ? 'yes' : 'no';
        $ok = $wpdb->update(
            $wpdb->options,
            array( 'autoload' => $new ),
            array( 'option_name' => $option_name ),
            array( '%s' ),
            array( '%s' )
        );
        if ( false !== $ok ) {
            // Clear the alloptions cache so the change takes effect immediately.
            wp_cache_delete( 'alloptions', 'options' );
            wp_cache_delete( 'notoptions', 'options' );
        }
        return false !== $ok;
    }

    /**
     * Protected options that should never have autoload disabled.
     *
     * @param string $name
     * @return bool
     */
    public static function is_protected_option( $name ) {
        static $protected = array(
            'siteurl', 'home', 'blogname', 'blogdescription', 'users_can_register',
            'admin_email', 'start_of_week', 'use_balanceTags', 'use_smilies', 'require_name_email',
            'comments_notify', 'posts_per_rss', 'rss_use_excerpt', 'mailserver_url', 'mailserver_login',
            'mailserver_pass', 'mailserver_port', 'default_category', 'default_comment_status',
            'default_ping_status', 'default_pingback_flag', 'posts_per_page', 'date_format', 'time_format',
            'links_updated_date_format', 'comment_moderation', 'moderation_notify', 'permalink_structure',
            'rewrite_rules', 'hack_file', 'blog_charset', 'moderation_keys', 'active_plugins',
            'category_base', 'ping_sites', 'comment_max_links', 'gmt_offset', 'default_email_category',
            'recently_edited', 'template', 'stylesheet', 'comment_registration', 'html_type',
            'use_trackback', 'default_role', 'db_version', 'uploads_use_yearmonth_folders',
            'upload_path', 'blog_public', 'default_link_category', 'show_on_front', 'tag_base',
            'show_avatars', 'avatar_rating', 'upload_url_path', 'thumbnail_size_w', 'thumbnail_size_h',
            'thumbnail_crop', 'medium_size_w', 'medium_size_h', 'avatar_default', 'large_size_w',
            'large_size_h', 'image_default_link_type', 'image_default_size', 'image_default_align',
            'close_comments_for_old_posts', 'close_comments_days_old', 'thread_comments',
            'thread_comments_depth', 'page_comments', 'comments_per_page', 'default_comments_page',
            'comment_order', 'sticky_posts', 'widget_categories', 'widget_text', 'widget_rss',
            'uninstall_plugins', 'timezone_string', 'page_for_posts', 'page_on_front', 'default_post_format',
            'link_manager_enabled', 'finished_splitting_shared_terms', 'site_icon', 'medium_large_size_w',
            'medium_large_size_h', 'wp_page_for_privacy_policy', 'show_comments_cookies_opt_in',
            'admin_email_lifespan', 'disallowed_keys', 'comment_previously_approved', 'auto_plugin_theme_update_emails',
            'auto_update_core_dev', 'auto_update_core_minor', 'auto_update_core_major',
            'wp_force_deactivated_plugins', 'wp_attachment_pages_enabled', 'cron',
        );
        return in_array( $name, $protected, true );
    }

    /**
     * Heuristic: option appears to be a transient — should rarely autoload.
     *
     * @param string $name
     * @return bool
     */
    public static function is_transient_option( $name ) {
        return 0 === strpos( $name, '_transient_' ) || 0 === strpos( $name, '_site_transient_' );
    }
}
