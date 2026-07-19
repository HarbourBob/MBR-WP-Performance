<?php
/**
 * Logs View
 *
 * @package MBR_Cookie_Consent
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

$db = MBR_CC_Database::get_instance();
$total = $db->get_consent_count();
$logs = $db->get_consent_logs(array('limit' => 100));
?>

<div class="wrap mbr-cc-admin-wrap">
    <h1><?php esc_html_e('Consent Logs', 'mbr-cookie-consent'); ?></h1>
    
    <div class="mbr-cc-logs-filters">
        <div>
            <button type="button" id="mbr-cc-export-logs" class="button">
                <?php esc_html_e('Export to CSV', 'mbr-cookie-consent'); ?>
            </button>
        </div>
        <div style="display:flex; align-items:center; gap:8px; margin-top:12px;">
            <label for="mbr-cc-delete-logs-days" style="font-weight:600;">
                <?php esc_html_e('Delete logs older than:', 'mbr-cookie-consent'); ?>
            </label>
            <input type="number"
                   id="mbr-cc-delete-logs-days"
                   class="small-text"
                   value="365"
                   min="1"
                   style="width:70px;" />
            <span><?php esc_html_e('days', 'mbr-cookie-consent'); ?></span>
            <button type="button" id="mbr-cc-delete-old-logs" class="button button-secondary">
                <?php esc_html_e('Delete Old Logs', 'mbr-cookie-consent'); ?>
            </button>
        </div>
    </div>
    
    <div class="mbr-cc-settings-section">
        <p>
            <?php
            /* translators: %s: total number of consent log entries. */
            printf( esc_html__( 'Total logs: %s', 'mbr-cookie-consent' ), number_format( $total ) );
            ?>
        </p>
        
        <?php if (!empty($logs)) : ?>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Date/Time', 'mbr-cookie-consent'); ?></th>
                        <th><?php esc_html_e('User', 'mbr-cookie-consent'); ?></th>
                        <th><?php esc_html_e('IP Address', 'mbr-cookie-consent'); ?></th>
                        <th><?php esc_html_e('Consent', 'mbr-cookie-consent'); ?></th>
                        <th><?php esc_html_e('Categories', 'mbr-cookie-consent'); ?></th>
                        <th><?php esc_html_e('Method', 'mbr-cookie-consent'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log) : ?>
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
                                    <span style="color: green;">✓</span>
                                <?php else : ?>
                                    <span style="color: red;">✗</span>
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
        <?php else : ?>
            <p><?php esc_html_e('No consent logs found.', 'mbr-cookie-consent'); ?></p>
        <?php endif; ?>
    </div>
</div>
