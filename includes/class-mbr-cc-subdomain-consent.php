<?php
/**
 * Subdomain Consent Sharing
 *
 * @package MBR_Cookie_Consent
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Subdomain Consent class.
 */
class MBR_CC_Subdomain_Consent {
    
    /**
     * Single instance.
     *
     * @var MBR_CC_Subdomain_Consent
     */
    private static $instance = null;
    
    /**
     * Get instance.
     *
     * @return MBR_CC_Subdomain_Consent
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
        // Modify cookie domain if subdomain sharing is enabled.
        add_filter('mbr_cc_cookie_domain', array($this, 'get_cookie_domain'));
        add_filter('mbr_cc_cookie_path', array($this, 'get_cookie_path'));
    }
    
    /**
     * Get cookie domain for subdomain sharing.
     *
     * @param string $domain Default domain.
     * @return string Cookie domain.
     */
    public function get_cookie_domain($domain) {
        if (!get_option('mbr_cc_subdomain_sharing', false)) {
            return $domain;
        }
        
        // Get root domain for subdomain sharing.
        $root_domain = $this->get_root_domain();
        
        if (!empty($root_domain)) {
            // Return .example.com format for subdomain sharing.
            return '.' . $root_domain;
        }
        
        return $domain;
    }
    
    /**
     * Get cookie path.
     *
     * @param string $path Default path.
     * @return string Cookie path.
     */
    public function get_cookie_path($path) {
        // Always use / for subdomain sharing.
        if (get_option('mbr_cc_subdomain_sharing', false)) {
            return '/';
        }
        
        return $path;
    }
    
    /**
     * Get root domain from current URL.
     *
     * @return string Root domain.
     */
    private function get_root_domain() {
        // Allow manual override.
        $manual_domain = get_option('mbr_cc_subdomain_root_domain', '');
        if (!empty($manual_domain)) {
            return $manual_domain;
        }
        
        // Auto-detect from current domain.
        $host = $_SERVER['HTTP_HOST'];
        
        // Remove port if present.
        $host = explode(':', $host)[0];
        
        // Remove www if present.
        $host = preg_replace('/^www\./', '', $host);
        
        // Get parts.
        $parts = explode('.', $host);
        
        // For localhost or IP, return as-is.
        if (count($parts) <= 1 || filter_var($host, FILTER_VALIDATE_IP)) {
            return $host;
        }
        
        // Get last two parts (example.com from sub.example.com).
        $root_domain = implode('.', array_slice($parts, -2));
        
        return $root_domain;
    }
    
    /**
     * Get cookie domain display.
     *
     * @return string Display domain.
     */
    public static function get_cookie_domain_display() {
        $root = self::get_instance()->get_root_domain();
        return '.' . $root;
    }
    
    /**
     * Get example subdomains.
     *
     * @return array Example subdomains.
     */
    public static function get_example_subdomains() {
        $root = self::get_instance()->get_root_domain();
        
        return array(
            'www.' . $root,
            'shop.' . $root,
            'blog.' . $root,
            'app.' . $root,
        );
    }
    
    /**
     * Test subdomain consent sharing.
     *
     * @return array Test results.
     */
    public static function test_subdomain_sharing() {
        $enabled = get_option('mbr_cc_subdomain_sharing', false);
        
        if (!$enabled) {
            return array(
                'enabled' => false,
                'message' => 'Subdomain sharing is not enabled.',
            );
        }
        
        $domain = self::get_cookie_domain_display();
        $current_host = $_SERVER['HTTP_HOST'];
        
        return array(
            'enabled' => true,
            'cookie_domain' => $domain,
            'current_host' => $current_host,
            'message' => sprintf(
                'Consent cookies will be shared across all subdomains of %s',
                $domain
            ),
        );
    }
}
