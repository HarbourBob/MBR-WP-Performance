<?php
/**
 * Cookie Scanner - detects scripts and cookies on the site.
 *
 * @package MBR_Cookie_Consent
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cookie Scanner class.
 */
class MBR_CC_Cookie_Scanner {
    
    /**
     * Single instance.
     *
     * @var MBR_CC_Cookie_Scanner
     */
    private static $instance = null;
    
    /**
     * Get instance.
     *
     * @return MBR_CC_Cookie_Scanner
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor.
     */
    private function __construct() {
        // AJAX handler for scanning.
        add_action('wp_ajax_mbr_cc_scan_cookies', array($this, 'ajax_scan_cookies'));
    }
    
    /**
     * AJAX: Scan site for cookies and scripts.
     */
    public function ajax_scan_cookies() {
        check_ajax_referer('mbr_cc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
        }
        
        $scan_type = isset($_POST['scan_type']) ? sanitize_text_field($_POST['scan_type']) : 'single';
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : home_url();
        
        if ($scan_type === 'site-wide') {
            $results = $this->scan_entire_site();
        } else {
            $results = $this->scan_page($url);
        }
        
        if (is_wp_error($results)) {
            wp_send_json_error(array('message' => $results->get_error_message()));
        }
        
        wp_send_json_success($results);
    }
    
    /**
     * Scan entire site (all published pages and posts).
     *
     * @return array Scan results organized by category.
     */
    public function scan_entire_site() {
        // Increase timeout for large sites.
        set_time_limit(300); // 5 minutes
        
        // Get all published pages and posts.
        $urls = $this->get_all_site_urls();
        
        $all_scripts = array();
        $all_iframes = array();
        $scanned_count = 0;
        $max_pages = 1000; // Increased from 50
        
        foreach (array_slice($urls, 0, $max_pages) as $url) {
            $page_results = $this->scan_page($url);
            
            if (!is_wp_error($page_results)) {
                $scanned_count++;
                
                // Merge scripts (avoiding duplicates).
                foreach ($page_results['scripts'] as $script) {
                    $identifier = $script['identifier'];
                    if (!isset($all_scripts[$identifier])) {
                        $all_scripts[$identifier] = $script;
                        $all_scripts[$identifier]['found_on'] = array();
                    }
                    $all_scripts[$identifier]['found_on'][] = $url;
                }
                
                // Merge iframes (avoiding duplicates).
                foreach ($page_results['iframes'] as $iframe) {
                    $identifier = $iframe['identifier'];
                    if (!isset($all_iframes[$identifier])) {
                        $all_iframes[$identifier] = $iframe;
                        $all_iframes[$identifier]['found_on'] = array();
                    }
                    $all_iframes[$identifier]['found_on'][] = $url;
                }
            }
        }
        
        // Organize by category.
        $organized = $this->organize_by_category(array_values($all_scripts), array_values($all_iframes));
        
        return array(
            'scripts' => array_values($all_scripts),
            'iframes' => array_values($all_iframes),
            'by_category' => $organized,
            'count' => count($all_scripts) + count($all_iframes),
            'pages_scanned' => $scanned_count,
            'total_urls' => count($urls),
            'max_pages' => $max_pages,
        );
    }
    
    /**
     * Get all site URLs (pages and posts).
     *
     * @return array URLs.
     */
    private function get_all_site_urls() {
        $urls = array();
        
        // Get homepage.
        $urls[] = home_url();
        
        // Get all published pages.
        $pages = get_posts(array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
        ));
        
        foreach ($pages as $page) {
            $urls[] = get_permalink($page->ID);
        }
        
        // Get all published posts.
        $posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
        ));
        
        foreach ($posts as $post) {
            $urls[] = get_permalink($post->ID);
        }
        
        // Remove duplicates.
        $urls = array_unique($urls);
        
        return $urls;
    }
    
    /**
     * Organize scripts and iframes by category.
     *
     * @param array $scripts Scripts.
     * @param array $iframes Iframes.
     * @return array Organized by category.
     */
    private function organize_by_category($scripts, $iframes) {
        $categories = array(
            'necessary' => array(),
            'analytics' => array(),
            'marketing' => array(),
            'preferences' => array(),
        );
        
        foreach ($scripts as $script) {
            $category = $script['category'];
            if (isset($categories[$category])) {
                $categories[$category][] = $script;
            }
        }
        
        foreach ($iframes as $iframe) {
            $category = $iframe['category'];
            if (isset($categories[$category])) {
                $categories[$category][] = $iframe;
            }
        }
        
        return $categories;
    }
    
    /**
     * Scan a page for scripts and iframes.
     *
     * @param string $url Page URL to scan.
     * @return array|WP_Error Scan results or error.
     */
    public function scan_page($url) {
        // Fetch page content.
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'sslverify' => false,
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $html = wp_remote_retrieve_body($response);
        
        if (empty($html)) {
            return new WP_Error('empty_response', 'Failed to retrieve page content.');
        }
        
        // Parse HTML.
        $scripts = $this->extract_scripts($html);
        $iframes = $this->extract_iframes($html);
        
        return array(
            'scripts' => $scripts,
            'iframes' => $iframes,
        );
    }
    
    /**
     * Extract script tags from HTML.
     *
     * @param string $html Page HTML.
     * @return array Scripts found.
     */
    private function extract_scripts($html) {
        $scripts = array();
        
        // Match external scripts with src attribute.
        preg_match_all('/<script[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $html, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $src) {
                $category = $this->categorize_script($src);
                
                $scripts[] = array(
                    'type' => 'src',
                    'identifier' => $src,
                    'name' => $this->get_script_name($src),
                    'category' => $category,
                    'description' => $this->get_script_description($src),
                );
            }
        }
        
        // Match inline scripts with common tracking patterns.
        $patterns = array(
            'google-analytics' => '/ga\(|gtag\(|GoogleAnalyticsObject/i',
            'google-tag-manager' => '/googletagmanager\.com\/gtm\.js/i',
            'facebook-pixel' => '/fbq\(|facebook\.com\/tr/i',
            'google-ads' => '/googlesyndication\.com/i',
            'hotjar' => '/hotjar/i',
        );
        
        foreach ($patterns as $name => $pattern) {
            if (preg_match($pattern, $html)) {
                $scripts[] = array(
                    'type' => 'inline',
                    'identifier' => $name,
                    'name' => $this->format_script_name($name),
                    'category' => $this->categorize_script($name),
                    'description' => '',
                );
            }
        }
        
        return $scripts;
    }
    
    /**
     * Extract iframe tags from HTML.
     *
     * @param string $html Page HTML.
     * @return array Iframes found.
     */
    private function extract_iframes($html) {
        $iframes = array();
        
        preg_match_all('/<iframe[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $html, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $src) {
                $category = $this->categorize_script($src);
                
                $iframes[] = array(
                    'type' => 'iframe',
                    'identifier' => $src,
                    'name' => $this->get_iframe_name($src),
                    'category' => $category,
                    'description' => '',
                );
            }
        }
        
        return $iframes;
    }
    
    /**
     * Categorize script based on source.
     *
     * @param string $src Script source.
     * @return string Category.
     */
    private function categorize_script($src) {
        $analytics_patterns = array(
            'google-analytics',
            'googletagmanager',
            'analytics',
            'ga.js',
            'gtag',
            'matomo',
            'piwik',
        );
        
        $marketing_patterns = array(
            'facebook',
            'fbq',
            'doubleclick',
            'googlesyndication',
            'adservice',
            'advertising',
            'pixel',
            'ads',
            'twitter',
            'linkedin',
            'tiktok',
        );
        
        $src_lower = strtolower($src);
        
        foreach ($analytics_patterns as $pattern) {
            if (strpos($src_lower, $pattern) !== false) {
                return 'analytics';
            }
        }
        
        foreach ($marketing_patterns as $pattern) {
            if (strpos($src_lower, $pattern) !== false) {
                return 'marketing';
            }
        }
        
        // Default to marketing for third-party scripts.
        $site_domain = wp_parse_url(home_url(), PHP_URL_HOST);
        $script_domain = wp_parse_url($src, PHP_URL_HOST);
        
        if ($script_domain && $script_domain !== $site_domain) {
            return 'marketing';
        }
        
        return 'preferences';
    }
    
    /**
     * Get friendly script name from source.
     *
     * @param string $src Script source.
     * @return string Friendly name.
     */
    private function get_script_name($src) {
        // Known services.
        $known = array(
            'google-analytics.com' => 'Google Analytics',
            'googletagmanager.com' => 'Google Tag Manager',
            'facebook.com' => 'Facebook Pixel',
            'facebook.net' => 'Facebook SDK',
            'doubleclick.net' => 'Google DoubleClick',
            'googlesyndication.com' => 'Google AdSense',
            'hotjar.com' => 'Hotjar',
            'twitter.com' => 'Twitter',
            'linkedin.com' => 'LinkedIn',
            'youtube.com' => 'YouTube',
            'vimeo.com' => 'Vimeo',
        );
        
        foreach ($known as $domain => $name) {
            if (strpos($src, $domain) !== false) {
                return $name;
            }
        }
        
        // Extract domain from URL.
        $parsed = wp_parse_url($src);
        if (isset($parsed['host'])) {
            return ucfirst(str_replace('www.', '', $parsed['host']));
        }
        
        return basename($src);
    }
    
    /**
     * Get iframe name from source.
     *
     * @param string $src Iframe source.
     * @return string Friendly name.
     */
    private function get_iframe_name($src) {
        if (strpos($src, 'youtube.com') !== false || strpos($src, 'youtu.be') !== false) {
            return 'YouTube Video';
        }
        
        if (strpos($src, 'vimeo.com') !== false) {
            return 'Vimeo Video';
        }
        
        if (strpos($src, 'google.com/maps') !== false) {
            return 'Google Maps';
        }
        
        $parsed = wp_parse_url($src);
        if (isset($parsed['host'])) {
            return ucfirst(str_replace('www.', '', $parsed['host'])) . ' Embed';
        }
        
        return 'External Embed';
    }
    
    /**
     * Format script name from identifier.
     *
     * @param string $identifier Script identifier.
     * @return string Formatted name.
     */
    private function format_script_name($identifier) {
        return ucwords(str_replace(array('-', '_'), ' ', $identifier));
    }
    
    /**
     * Get script description.
     *
     * @param string $src Script source.
     * @return string Description.
     */
    private function get_script_description($src) {
        $descriptions = array(
            'google-analytics' => 'Web analytics service that tracks and reports website traffic.',
            'googletagmanager' => 'Tag management system that allows you to manage marketing tags.',
            'facebook' => 'Tracks conversions from Facebook ads and builds audiences.',
            'doubleclick' => 'Ad serving platform for managing digital advertising campaigns.',
            'hotjar' => 'Behavior analytics tool that provides heatmaps and user recordings.',
        );
        
        foreach ($descriptions as $key => $description) {
            if (strpos($src, $key) !== false) {
                return $description;
            }
        }
        
        return '';
    }
}
