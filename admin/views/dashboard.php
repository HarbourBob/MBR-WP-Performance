<?php
/**
 * Admin Dashboard View
 *
 * @package MBR_Cookie_Consent
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap mbr-cc-admin-wrap">
    <h1><?php esc_html_e('Cookie Consent Dashboard', 'mbr-cookie-consent'); ?></h1>
    
    <!-- Legal Disclaimer -->
    <div class="mbr-cc-disclaimer">
        <h3><?php esc_html_e('⚠️ Legal Disclaimer', 'mbr-cookie-consent'); ?></h3>
        <p><?php esc_html_e('This plugin provides technical tools to help implement cookie consent mechanisms. It does not constitute legal advice. You are responsible for ensuring your use of this plugin complies with applicable laws (GDPR, CCPA, etc.) and should consult with legal counsel regarding your specific compliance requirements.', 'mbr-cookie-consent'); ?></p>
    </div>
    
    <!-- Dashboard Stats -->
    <div class="mbr-cc-stats">
        <div class="mbr-cc-stat-card">
            <h3><?php esc_html_e('Total Consent Logs', 'mbr-cookie-consent'); ?></h3>
            <div class="stat-value"><?php echo number_format($total_logs); ?></div>
        </div>
        
        <div class="mbr-cc-stat-card">
            <h3><?php esc_html_e('Blocked Scripts', 'mbr-cookie-consent'); ?></h3>
            <div class="stat-value"><?php echo count($blocked_scripts); ?></div>
        </div>
        
        <div class="mbr-cc-stat-card">
            <h3><?php esc_html_e('Cookie Policy Page', 'mbr-cookie-consent'); ?></h3>
            <div class="stat-value">
                <?php if ($policy_page_id && get_post_status($policy_page_id)) : ?>
                    <a href="<?php echo esc_url(get_edit_post_link($policy_page_id)); ?>" class="button">
                        <?php esc_html_e('Edit Page', 'mbr-cookie-consent'); ?>
                    </a>
                <?php else : ?>
                    <span style="font-size: 14px;"><?php esc_html_e('Not Created', 'mbr-cookie-consent'); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="mbr-cc-quick-actions">
        <h3><?php esc_html_e('Quick Actions', 'mbr-cookie-consent'); ?></h3>
        
        <a href="<?php echo esc_url(admin_url('admin.php?page=mbr-cookie-consent-scanner')); ?>" class="button button-primary">
            <?php esc_html_e('Scan for Cookies', 'mbr-cookie-consent'); ?>
        </a>
        
        <?php if (!$policy_page_id || get_post_status($policy_page_id) === false) : ?>
            <button type="button" id="mbr-cc-generate-policy" class="button button-secondary">
                <?php esc_html_e('Generate Cookie Policy Page', 'mbr-cookie-consent'); ?>
            </button>
        <?php endif; ?>
        
        <?php 
        $privacy_policy_page_id = get_option('mbr_cc_privacy_policy_page_id');
        if (!$privacy_policy_page_id || get_post_status($privacy_policy_page_id) === false) : 
        ?>
            <button type="button" id="mbr-cc-generate-privacy-policy" class="button button-secondary">
                <?php esc_html_e('Generate Privacy Policy Page', 'mbr-cookie-consent'); ?>
            </button>
        <?php endif; ?>
        
        <a href="<?php echo esc_url(admin_url('admin.php?page=mbr-cookie-consent-settings')); ?>" class="button button-secondary">
            <?php esc_html_e('Configure Banner', 'mbr-cookie-consent'); ?>
        </a>
        
        <a href="<?php echo esc_url(admin_url('admin.php?page=mbr-cookie-consent-logs')); ?>" class="button button-secondary">
            <?php esc_html_e('View Consent Logs', 'mbr-cookie-consent'); ?>
        </a>
    </div>
    
    <!-- Recent Consent Logs -->
    <div class="mbr-cc-settings-section">
        <h2><?php esc_html_e('Recent Consent Logs', 'mbr-cookie-consent'); ?></h2>
        
        <?php if (!empty($recent_logs)) : ?>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Date/Time', 'mbr-cookie-consent'); ?></th>
                        <th><?php esc_html_e('User', 'mbr-cookie-consent'); ?></th>
                        <th><?php esc_html_e('IP Address', 'mbr-cookie-consent'); ?></th>
                        <th><?php esc_html_e('Consent Given', 'mbr-cookie-consent'); ?></th>
                        <th><?php esc_html_e('Categories', 'mbr-cookie-consent'); ?></th>
                        <th><?php esc_html_e('Method', 'mbr-cookie-consent'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_logs as $log) : ?>
                        <tr>
                            <td><?php echo esc_html($log['timestamp']); ?></td>
                            <td>
                                <?php 
                                if ($log['user_id']) {
                                    $user = get_userdata($log['user_id']);
                                    echo esc_html($user ? $user->display_name : 'User #' . $log['user_id']);
                                } else {
                                    esc_html_e('Guest', 'mbr-cookie-consent');
                                }
                                ?>
                            </td>
                            <td><?php echo esc_html($log['ip_address']); ?></td>
                            <td>
                                <?php if ($log['consent_given']) : ?>
                                    <span style="color: green;">✓ <?php esc_html_e('Yes', 'mbr-cookie-consent'); ?></span>
                                <?php else : ?>
                                    <span style="color: red;">✗ <?php esc_html_e('No', 'mbr-cookie-consent'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $categories = json_decode($log['categories_accepted'], true);
                                echo esc_html(is_array($categories) ? implode(', ', $categories) : '-');
                                ?>
                            </td>
                            <td><?php echo esc_html($log['consent_method']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=mbr-cookie-consent-logs')); ?>" class="button">
                    <?php esc_html_e('View All Logs', 'mbr-cookie-consent'); ?>
                </a>
            </p>
        <?php else : ?>
            <p><?php esc_html_e('No consent logs recorded yet.', 'mbr-cookie-consent'); ?></p>
        <?php endif; ?>
    </div>
    
    <!-- Blocked Scripts Overview -->
    <?php if (!empty($blocked_scripts)) : ?>
        <div class="mbr-cc-settings-section">
            <h2><?php esc_html_e('Currently Blocked Scripts', 'mbr-cookie-consent'); ?></h2>
            
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Name', 'mbr-cookie-consent'); ?></th>
                        <th><?php esc_html_e('Type', 'mbr-cookie-consent'); ?></th>
                        <th><?php esc_html_e('Category', 'mbr-cookie-consent'); ?></th>
                        <th><?php esc_html_e('Identifier', 'mbr-cookie-consent'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($blocked_scripts, 0, 5) as $script) : ?>
                        <tr>
                            <td><?php echo esc_html($script['name']); ?></td>
                            <td><?php echo esc_html($script['type']); ?></td>
                            <td><?php echo esc_html($script['category']); ?></td>
                            <td><code><?php echo esc_html($script['identifier']); ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if (count($blocked_scripts) > 5) : ?>
                <p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=mbr-cookie-consent-scanner')); ?>" class="button">
                        <?php esc_html_e('View All Blocked Scripts', 'mbr-cookie-consent'); ?>
                    </a>
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
