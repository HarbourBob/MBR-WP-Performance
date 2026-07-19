<?php
/**
 * Network Reports page view.
 *
 * @package MBR_Cookie_Consent
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

$network_admin = MBR_CC_Network_Admin::get_instance();
$stats = $network_admin->get_network_stats();

// Handle export request
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    check_admin_referer('mbr-cc-export-network');
    $network_admin->export_network_consent_data();
}

global $wpdb;
$table_name = $wpdb->base_prefix . 'mbr_cc_consent_logs';
?>

<div class="wrap">
    <h1><?php esc_html_e('Cookie Consent - Network Reports', 'mbr-cookie-consent'); ?></h1>
    
    <p><?php esc_html_e('View aggregate consent statistics across your entire network.', 'mbr-cookie-consent'); ?></p>
    
    <!-- Network Overview -->
    <div class="mbr-cc-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
        
        <div class="mbr-cc-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px;">
            <h3 style="margin: 0 0 10px 0; font-size: 14px; color: #666;"><?php esc_html_e('Total Consents', 'mbr-cookie-consent'); ?></h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: #0073aa;"><?php echo number_format($stats['total_consents']); ?></p>
        </div>
        
        <div class="mbr-cc-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px;">
            <h3 style="margin: 0 0 10px 0; font-size: 14px; color: #666;"><?php esc_html_e('Last 30 Days', 'mbr-cookie-consent'); ?></h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: #00a32a;"><?php echo number_format($stats['recent_consents']); ?></p>
        </div>
        
        <div class="mbr-cc-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px;">
            <h3 style="margin: 0 0 10px 0; font-size: 14px; color: #666;"><?php esc_html_e('Acceptance Rate', 'mbr-cookie-consent'); ?></h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: #d63638;"><?php echo esc_html($stats['acceptance_rate']); ?>%</p>
        </div>
        
        <div class="mbr-cc-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px;">
            <h3 style="margin: 0 0 10px 0; font-size: 14px; color: #666;"><?php esc_html_e('Total Sites', 'mbr-cookie-consent'); ?></h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: #7e8993;"><?php echo number_format($stats['total_sites']); ?></p>
        </div>
        
    </div>
    
    <!-- Top Sites by Consent Volume -->
    <div class="mbr-cc-settings-section" style="background: #fff; padding: 20px; margin: 20px 0;">
        <h2><?php esc_html_e('Top Sites by Consent Volume', 'mbr-cookie-consent'); ?></h2>
        
        <?php if (!empty($stats['consents_by_site'])) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Site', 'mbr-cookie-consent'); ?></th>
                        <th><?php esc_html_e('Total Consents', 'mbr-cookie-consent'); ?></th>
                        <th><?php esc_html_e('Actions', 'mbr-cookie-consent'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats['consents_by_site'] as $site_stat) : 
                        $blog_details = get_blog_details($site_stat->blog_id);
                    ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($blog_details->blogname); ?></strong>
                                <br>
                                <span style="color: #666; font-size: 13px;"><?php echo esc_url($blog_details->siteurl); ?></span>
                            </td>
                            <td><?php echo number_format($site_stat->count); ?></td>
                            <td>
                                <a href="<?php echo esc_url(get_admin_url($site_stat->blog_id, 'admin.php?page=mbr-cc-consent-logs')); ?>" class="button button-small">
                                    <?php esc_html_e('View Logs', 'mbr-cookie-consent'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php esc_html_e('No consent data available yet.', 'mbr-cookie-consent'); ?></p>
        <?php endif; ?>
    </div>
    
    <!-- Consent Timeline (Last 30 Days) -->
    <div class="mbr-cc-settings-section" style="background: #fff; padding: 20px; margin: 20px 0;">
        <h2><?php esc_html_e('Consent Timeline (Last 30 Days)', 'mbr-cookie-consent'); ?></h2>
        
        <?php
        // Get daily consent counts for last 30 days
        $daily_stats = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(timestamp) as date, COUNT(*) as count 
            FROM $table_name 
            WHERE timestamp >= %s 
            GROUP BY DATE(timestamp) 
            ORDER BY date DESC 
            LIMIT 30",
            gmdate('Y-m-d H:i:s', strtotime('-30 days'))
        ));
        ?>
        
        <?php if (!empty($daily_stats)) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Date', 'mbr-cookie-consent'); ?></th>
                        <th><?php esc_html_e('Consents', 'mbr-cookie-consent'); ?></th>
                        <th style="width: 60%;"><?php esc_html_e('Visual', 'mbr-cookie-consent'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $max_count = max(array_column($daily_stats, 'count'));
                    foreach ($daily_stats as $day_stat) : 
                        $percentage = $max_count > 0 ? ($day_stat->count / $max_count) * 100 : 0;
                    ?>
                        <tr>
                            <td><?php echo esc_html(gmdate('M j, Y', strtotime($day_stat->date))); ?></td>
                            <td><?php echo number_format($day_stat->count); ?></td>
                            <td>
                                <div style="background: #e0e0e0; height: 20px; border-radius: 3px; overflow: hidden;">
                                    <div style="background: #0073aa; height: 100%; width: <?php echo esc_attr($percentage); ?>%;"></div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php esc_html_e('No consent data in the last 30 days.', 'mbr-cookie-consent'); ?></p>
        <?php endif; ?>
    </div>
    
    <!-- Consent Methods -->
    <div class="mbr-cc-settings-section" style="background: #fff; padding: 20px; margin: 20px 0;">
        <h2><?php esc_html_e('Consent Methods', 'mbr-cookie-consent'); ?></h2>
        
        <?php
        $method_stats = $wpdb->get_results(
            "SELECT consent_method, COUNT(*) as count 
            FROM $table_name 
            GROUP BY consent_method 
            ORDER BY count DESC"
        );
        ?>
        
        <?php if (!empty($method_stats)) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Method', 'mbr-cookie-consent'); ?></th>
                        <th><?php esc_html_e('Count', 'mbr-cookie-consent'); ?></th>
                        <th><?php esc_html_e('Percentage', 'mbr-cookie-consent'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($method_stats as $method_stat) : 
                        $percentage = $stats['total_consents'] > 0 ? ($method_stat->count / $stats['total_consents']) * 100 : 0;
                    ?>
                        <tr>
                            <td><?php echo esc_html(ucfirst($method_stat->consent_method)); ?></td>
                            <td><?php echo number_format($method_stat->count); ?></td>
                            <td><?php echo esc_html(round($percentage, 2)); ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php esc_html_e('No consent data available.', 'mbr-cookie-consent'); ?></p>
        <?php endif; ?>
    </div>
    
    <!-- Export Section -->
    <div class="mbr-cc-settings-section" style="background: #fff; padding: 20px; margin: 20px 0;">
        <h2><?php esc_html_e('Export Network Data', 'mbr-cookie-consent'); ?></h2>
        <p><?php esc_html_e('Download all consent records across the entire network for compliance and auditing purposes.', 'mbr-cookie-consent'); ?></p>
        
        <a href="<?php echo esc_url(wp_nonce_url(add_query_arg('action', 'export'), 'mbr-cc-export-network')); ?>" class="button button-primary">
            <?php esc_html_e('Export All Network Consent Data (CSV)', 'mbr-cookie-consent'); ?>
        </a>
    </div>
    
</div>
