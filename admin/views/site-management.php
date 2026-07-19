<?php
/**
 * Site Management page view.
 *
 * @package MBR_Cookie_Consent
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get all sites in network
$sites = get_sites(array(
    'number' => 999,
    'orderby' => 'path',
));

$table_name = $wpdb->base_prefix . 'mbr_cc_consent_logs';
?>

<div class="wrap">
    <h1><?php esc_html_e('Cookie Consent - Site Management', 'mbr-cookie-consent'); ?></h1>
    
    <p><?php esc_html_e('Manage cookie consent settings across all sites in your network.', 'mbr-cookie-consent'); ?></p>
    
    <!-- Sites Overview -->
    <div class="mbr-cc-settings-section" style="background: #fff; padding: 20px; margin: 20px 0;">
        <h2><?php esc_html_e('All Sites', 'mbr-cookie-consent'); ?></h2>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Site', 'mbr-cookie-consent'); ?></th>
                    <th><?php esc_html_e('Total Consents', 'mbr-cookie-consent'); ?></th>
                    <th><?php esc_html_e('Last Consent', 'mbr-cookie-consent'); ?></th>
                    <th><?php esc_html_e('Settings', 'mbr-cookie-consent'); ?></th>
                    <th><?php esc_html_e('Actions', 'mbr-cookie-consent'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sites as $site) : 
                    $blog_id = $site->blog_id;
                    $blog_details = get_blog_details($blog_id);
                    
                    // Get consent count for this site
                    $consent_count = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM $table_name WHERE blog_id = %d",
                        $blog_id
                    ));
                    
                    // Get last consent
                    $last_consent = $wpdb->get_var($wpdb->prepare(
                        "SELECT timestamp FROM $table_name WHERE blog_id = %d ORDER BY timestamp DESC LIMIT 1",
                        $blog_id
                    ));
                    
                    // Check if site has custom settings
                    switch_to_blog($blog_id);
                    $has_custom = get_option('mbr_cc_banner_position') !== false;
                    restore_current_blog();
                ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($blog_details->blogname); ?></strong>
                            <br>
                            <span style="color: #666; font-size: 13px;"><?php echo esc_url($blog_details->siteurl); ?></span>
                        </td>
                        <td><?php echo number_format($consent_count); ?></td>
                        <td>
                            <?php if ($last_consent) : ?>
                                <?php echo esc_html(human_time_diff(strtotime($last_consent), current_time('timestamp'))); ?> <?php esc_html_e('ago', 'mbr-cookie-consent'); ?>
                            <?php else : ?>
                                <span style="color: #999;"><?php esc_html_e('Never', 'mbr-cookie-consent'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($has_custom) : ?>
                                <span style="color: #d63638;">● <?php esc_html_e('Custom', 'mbr-cookie-consent'); ?></span>
                            <?php else : ?>
                                <span style="color: #00a32a;">● <?php esc_html_e('Network', 'mbr-cookie-consent'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo esc_url(get_admin_url($blog_id, 'admin.php?page=mbr-cc-settings')); ?>" class="button button-small">
                                <?php esc_html_e('Settings', 'mbr-cookie-consent'); ?>
                            </a>
                            <a href="<?php echo esc_url(get_admin_url($blog_id, 'admin.php?page=mbr-cc-consent-logs')); ?>" class="button button-small">
                                <?php esc_html_e('Logs', 'mbr-cookie-consent'); ?>
                            </a>
                            <a href="<?php echo esc_url($blog_details->siteurl); ?>" class="button button-small" target="_blank">
                                <?php esc_html_e('Visit', 'mbr-cookie-consent'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Bulk Actions -->
    <div class="mbr-cc-settings-section" style="background: #fff; padding: 20px; margin: 20px 0;">
        <h2><?php esc_html_e('Bulk Actions', 'mbr-cookie-consent'); ?></h2>
        
        <p><?php esc_html_e('Apply actions to all sites in the network.', 'mbr-cookie-consent'); ?></p>
        
        <div style="margin: 20px 0;">
            <button type="button" class="button" onclick="if(confirm('<?php esc_attr_e('This will reset all site-specific settings and force all sites to use network settings. This cannot be undone. Continue?', 'mbr-cookie-consent'); ?>')) { alert('<?php esc_attr_e('This feature will be available in a future update.', 'mbr-cookie-consent'); ?>'); }">
                <?php esc_html_e('Reset All Sites to Network Settings', 'mbr-cookie-consent'); ?>
            </button>
            
            <button type="button" class="button" onclick="if(confirm('<?php esc_attr_e('This will clear all consent logs across the entire network. This cannot be undone. Continue?', 'mbr-cookie-consent'); ?>')) { alert('<?php esc_attr_e('This feature will be available in a future update.', 'mbr-cookie-consent'); ?>'); }">
                <?php esc_html_e('Clear All Consent Logs', 'mbr-cookie-consent'); ?>
            </button>
        </div>
        
        <p class="description"><?php esc_html_e('Note: Bulk actions are currently in development and will be available in a future update.', 'mbr-cookie-consent'); ?></p>
    </div>
    
    <!-- Network Statistics Summary -->
    <div class="mbr-cc-settings-section" style="background: #fff; padding: 20px; margin: 20px 0;">
        <h2><?php esc_html_e('Network Summary', 'mbr-cookie-consent'); ?></h2>
        
        <?php
        $total_consents = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $sites_with_consents = $wpdb->get_var("SELECT COUNT(DISTINCT blog_id) FROM $table_name");
        $sites_with_custom = 0;
        
        foreach ($sites as $site) {
            switch_to_blog($site->blog_id);
            if (get_option('mbr_cc_banner_position') !== false) {
                $sites_with_custom++;
            }
            restore_current_blog();
        }
        ?>
        
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Total Sites in Network', 'mbr-cookie-consent'); ?></th>
                <td><strong><?php echo number_format(count($sites)); ?></strong></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Sites with Consent Data', 'mbr-cookie-consent'); ?></th>
                <td><strong><?php echo number_format($sites_with_consents); ?></strong></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Sites with Custom Settings', 'mbr-cookie-consent'); ?></th>
                <td><strong><?php echo number_format($sites_with_custom); ?></strong></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Sites Using Network Settings', 'mbr-cookie-consent'); ?></th>
                <td><strong><?php echo number_format(count($sites) - $sites_with_custom); ?></strong></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Total Network Consents', 'mbr-cookie-consent'); ?></th>
                <td><strong><?php echo number_format($total_consents); ?></strong></td>
            </tr>
        </table>
    </div>
    
</div>
