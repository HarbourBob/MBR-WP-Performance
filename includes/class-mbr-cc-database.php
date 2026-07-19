<?php
/**
 * Database handler for consent logging.
 *
 * @package MBR_Cookie_Consent
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database class.
 */
class MBR_CC_Database {
    
    /**
     * Single instance.
     *
     * @var MBR_CC_Database
     */
    private static $instance = null;
    
    /**
     * Consent logs table name.
     *
     * @var string
     */
    private $consent_table;
    
    /**
     * Get instance.
     *
     * @return MBR_CC_Database
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
        global $wpdb;
        // Use base_prefix for multisite, regular prefix for single-site
        if (is_multisite()) {
            $this->consent_table = $wpdb->base_prefix . 'mbr_cc_consent_logs';
        } else {
            $this->consent_table = $wpdb->prefix . 'mbr_cc_consent_logs';
        }
    }
    
    /**
     * Create database tables.
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Use base_prefix for multisite, regular prefix for single-site
        if (is_multisite()) {
            $table_name = $wpdb->base_prefix . 'mbr_cc_consent_logs';
        } else {
            $table_name = $wpdb->prefix . 'mbr_cc_consent_logs';
        }
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            blog_id bigint(20) UNSIGNED NOT NULL DEFAULT 1,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text NOT NULL,
            consent_given tinyint(1) NOT NULL DEFAULT 0,
            categories_accepted text DEFAULT NULL,
            consent_method varchar(50) NOT NULL,
            timestamp datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            cookie_hash varchar(64) NOT NULL,
            PRIMARY KEY (id),
            KEY blog_id (blog_id),
            KEY user_id (user_id),
            KEY timestamp (timestamp),
            KEY cookie_hash (cookie_hash)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
        
        // Store database version.
        if (is_multisite()) {
            update_site_option('mbr_cc_db_version', '1.5.1');
        } else {
            update_option('mbr_cc_db_version', '1.5.1');
        }
    }
    
    /**
     * Log consent action.
     *
     * @param array $data Consent data.
     * @return int|false Insert ID or false on failure.
     */
    public function log_consent($data) {
        global $wpdb;
        
        $defaults = array(
            'blog_id' => get_current_blog_id(),
            'user_id' => get_current_user_id(),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'consent_given' => false,
            'categories_accepted' => '',
            'consent_method' => 'banner',
            'timestamp' => current_time('mysql'),
            'cookie_hash' => '',
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Hash IP and user agent for privacy.
        $data['cookie_hash'] = hash('sha256', $data['ip_address'] . $data['user_agent']);
        
        // Anonymize IP (GDPR requirement).
        $data['ip_address'] = $this->anonymize_ip($data['ip_address']);
        
        // Serialize categories if array.
        if (is_array($data['categories_accepted'])) {
            $data['categories_accepted'] = json_encode($data['categories_accepted']);
        }
        
        $result = $wpdb->insert(
            $this->consent_table,
            array(
                'blog_id' => $data['blog_id'],
                'user_id' => $data['user_id'],
                'ip_address' => $data['ip_address'],
                'user_agent' => $data['user_agent'],
                'consent_given' => $data['consent_given'] ? 1 : 0,
                'categories_accepted' => $data['categories_accepted'],
                'consent_method' => $data['consent_method'],
                'timestamp' => $data['timestamp'],
                'cookie_hash' => $data['cookie_hash'],
            ),
            array('%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s')
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Get consent logs.
     *
     * @param array $args Query arguments.
     * @return array Consent logs.
     */
    public function get_consent_logs($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'limit' => 100,
            'offset' => 0,
            'orderby' => 'timestamp',
            'order' => 'DESC',
            'user_id' => null,
            'date_from' => null,
            'date_to' => null,
            'blog_id' => get_current_blog_id(), // Filter by current site
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where  = array('1=1');
        $values = array();
        
        // Always filter by blog_id (critical for multisite)
        if (!is_null($args['blog_id'])) {
            $where[]  = 'blog_id = %d';
            $values[] = $args['blog_id'];
        }
        
        if (!is_null($args['user_id'])) {
            $where[]  = 'user_id = %d';
            $values[] = $args['user_id'];
        }
        
        if (!is_null($args['date_from'])) {
            $where[]  = 'timestamp >= %s';
            $values[] = $args['date_from'];
        }
        
        if (!is_null($args['date_to'])) {
            $where[]  = 'timestamp <= %s';
            $values[] = $args['date_to'];
        }
        
        $where_clause = implode(' AND ', $where);
        
        // Whitelist orderby/order: these are SQL identifiers and cannot be bound as prepared values.
        $allowed_orderby = array('id', 'blog_id', 'user_id', 'consent_given', 'consent_method', 'timestamp', 'cookie_hash');
        $orderby = in_array($args['orderby'], $allowed_orderby, true) ? $args['orderby'] : 'timestamp';
        $order   = strtoupper((string) $args['order']) === 'ASC' ? 'ASC' : 'DESC';
        
        $values[] = (int) $args['limit'];
        $values[] = (int) $args['offset'];
        
        $query = "SELECT * FROM {$this->consent_table}
                  WHERE {$where_clause}
                  ORDER BY {$orderby} {$order}
                  LIMIT %d OFFSET %d";
        
        // The interpolated parts are a constant internal table name and whitelisted orderby/order identifiers; every user-supplied value is bound through prepare().
        $results = $wpdb->get_results(
            $wpdb->prepare( $query, $values ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
            ARRAY_A
        );
        
        return $results;
    }
    
    /**
     * Get total consent log count.
     *
     * @param array $args Query arguments.
     * @return int Count.
     */
    public function get_consent_count($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'blog_id' => get_current_blog_id(), // Filter by current site
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array('1=1');
        
        // Always filter by blog_id (critical for multisite)
        if (!empty($args['blog_id'])) {
            $where[] = $wpdb->prepare('blog_id = %d', $args['blog_id']);
        }
        
        if (!empty($args['user_id'])) {
            $where[] = $wpdb->prepare('user_id = %d', $args['user_id']);
        }
        
        if (!empty($args['date_from'])) {
            $where[] = $wpdb->prepare('timestamp >= %s', $args['date_from']);
        }
        
        if (!empty($args['date_to'])) {
            $where[] = $wpdb->prepare('timestamp <= %s', $args['date_to']);
        }
        
        $where_clause = implode(' AND ', $where);
        
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->consent_table} WHERE {$where_clause}");
        
        return (int) $count;
    }
    
    /**
     * Delete old consent logs.
     *
     * @param int $days Delete logs older than X days.
     * @return int|false Number of rows deleted or false on failure.
     */
    public function delete_old_logs($days = 365) {
        global $wpdb;
        
        // Defence in depth: never allow a cutoff of "now or later", which
        // would wipe the entire table. Callers should validate too.
        $days = (int) $days;
        if ($days < 1) {
            return false;
        }
        
        $date = gmdate('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        return $wpdb->query(
            $wpdb->prepare("DELETE FROM {$this->consent_table} WHERE timestamp < %s", $date)
        );
    }
    
    /**
     * Export consent logs to CSV.
     *
     * @param array $args Query arguments.
     * @return string CSV content.
     */
    public function export_to_csv($args = array()) {
        $logs = $this->get_consent_logs($args);
        
        if (empty($logs)) {
            return '';
        }
        
        // Create CSV header.
        $csv = array();
        if (is_multisite()) {
            $csv[] = array('ID', 'Blog ID', 'User ID', 'IP Address', 'Consent Given', 'Categories', 'Method', 'Timestamp');
        } else {
            $csv[] = array('ID', 'User ID', 'IP Address', 'Consent Given', 'Categories', 'Method', 'Timestamp');
        }
        
        // Add data rows.
        foreach ($logs as $log) {
            $categories = json_decode($log['categories_accepted'], true);
            if (is_array($categories)) {
                $categories = implode(', ', $categories);
            }
            
            if (is_multisite()) {
                $csv[] = array(
                    $log['id'],
                    $log['blog_id'],
                    $log['user_id'] ?: 'Guest',
                    $log['ip_address'],
                    $log['consent_given'] ? 'Yes' : 'No',
                    $categories,
                    $log['consent_method'],
                    $log['timestamp'],
                );
            } else {
                $csv[] = array(
                    $log['id'],
                    $log['user_id'] ?: 'Guest',
                    $log['ip_address'],
                    $log['consent_given'] ? 'Yes' : 'No',
                    $categories,
                    $log['consent_method'],
                    $log['timestamp'],
                );
            }
        }
        
        // Convert to CSV string.
        // Build the CSV string via an in-memory php://temp stream so fputcsv() handles correct field quoting/escaping. WP_Filesystem operates on real files and offers no CSV-encoding equivalent.
        $output = fopen('php://temp', 'r+'); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
        foreach ($csv as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csv_content = stream_get_contents($output);
        fclose($output); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
        
        return $csv_content;
    }
    
    /**
     * Get client IP address.
     *
     * @return string IP address.
     */
    private function get_client_ip() {
        $ip_keys = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        );
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER)) {
                $ip = $_SERVER[$key];
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Anonymize IP address for GDPR compliance.
     *
     * @param string $ip IP address.
     * @return string Anonymized IP.
     */
    private function anonymize_ip($ip) {
        // IPv4.
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $parts[3] = '0';
            return implode('.', $parts);
        }
        
        // IPv6.
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            $parts[count($parts) - 1] = '0';
            return implode(':', $parts);
        }
        
        return $ip;
    }
}
