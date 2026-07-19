<?php
/**
 * Scanner View
 *
 * @package MBR_Cookie_Consent
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

$blocker = MBR_CC_Script_Blocker::get_instance();
$blocked_scripts = $blocker->get_blocked_scripts();
?>

<div class="wrap mbr-cc-admin-wrap">
    <h1><?php esc_html_e('Cookie Scanner', 'mbr-cookie-consent'); ?></h1>
    
    <!-- Built-in Services Notice -->
    <div class="mbr-cc-settings-section" style="border-left: 4px solid #46b450; background: #f0faf0;">
        <h2><?php esc_html_e( 'Automatic Blocking — Built-in Services', 'mbr-cookie-consent' ); ?></h2>
        <p><?php esc_html_e( 'The following third-party services are blocked automatically by category — no manual configuration needed. They are active as soon as a visitor has not granted consent for that category.', 'mbr-cookie-consent' ); ?></p>

        <?php
        $builtin = MBR_CC_Script_Blocker::get_builtin_services();
        $category_labels = array(
            'marketing'   => __( 'Marketing', 'mbr-cookie-consent' ),
            'analytics'   => __( 'Analytics', 'mbr-cookie-consent' ),
            'preferences' => __( 'Preferences / Functional', 'mbr-cookie-consent' ),
        );
        foreach ( $builtin as $cat => $services ) :
            $label = isset( $category_labels[ $cat ] ) ? $category_labels[ $cat ] : ucfirst( $cat );
        ?>
        <div style="margin-bottom: 16px;">
            <h3 style="margin-bottom: 6px;"><?php echo esc_html( $label ); ?></h3>
            <table class="widefat striped" style="max-width: 700px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Service', 'mbr-cookie-consent' ); ?></th>
                        <th><?php esc_html_e( 'Blocks', 'mbr-cookie-consent' ); ?></th>
                        <th><?php esc_html_e( 'Matched domains / patterns', 'mbr-cookie-consent' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $services as $svc ) : ?>
                    <tr>
                        <td><strong><?php echo esc_html( $svc['name'] ); ?></strong></td>
                        <td><?php echo esc_html( ucfirst( $svc['type'] ) ); ?></td>
                        <td><code><?php echo esc_html( implode( ', ', $svc['domains'] ) ); ?></code></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Scanner -->
    <div class="mbr-cc-settings-section">
        <h2><?php esc_html_e('Scan Your Website', 'mbr-cookie-consent'); ?></h2>
        <p><?php esc_html_e('Choose between scanning a single page or your entire website for scripts and cookies.', 'mbr-cookie-consent'); ?></p>
        
        <div style="margin-bottom: 20px;">
            <label style="display: inline-block; margin-right: 20px;">
                <input type="radio" name="scan_type" value="single" checked> 
                <?php esc_html_e('Single Page Scan', 'mbr-cookie-consent'); ?>
            </label>
            <label style="display: inline-block;">
                <input type="radio" name="scan_type" value="site-wide"> 
                <?php esc_html_e('Site-Wide Scan (All Pages & Posts)', 'mbr-cookie-consent'); ?>
            </label>
        </div>
        
        <div id="mbr-cc-single-scan-options" style="margin-bottom: 15px;">
            <label>
                <?php esc_html_e('URL to scan:', 'mbr-cookie-consent'); ?>
                <input type="url" id="mbr-cc-scan-url" value="<?php echo esc_url(home_url()); ?>" style="width: 400px;">
            </label>
        </div>
        
        <div id="mbr-cc-site-wide-info" style="display: none; margin-bottom: 15px; padding: 10px; background: #fff3cd; border-left: 4px solid #ffc107;">
            <p style="margin: 0;">
                <strong><?php esc_html_e('Note:', 'mbr-cookie-consent'); ?></strong>
                <?php esc_html_e('Site-wide scan will check all published pages and posts (max 1000). This may take a few minutes depending on your site size. The scan runs in the background - please be patient.', 'mbr-cookie-consent'); ?>
            </p>
        </div>
        
        <p>
            <button type="button" id="mbr-cc-start-scan" class="button button-primary">
                <?php esc_html_e('Start Scan', 'mbr-cookie-consent'); ?>
            </button>
        </p>
        
        <div id="mbr-cc-scan-progress" style="display: none; margin-top: 15px;">
            <div style="background: #f0f0f0; border-radius: 4px; height: 30px; position: relative; overflow: hidden;">
                <div id="mbr-cc-progress-bar" style="background: #0073aa; height: 100%; width: 0%; transition: width 0.3s;"></div>
                <span id="mbr-cc-progress-text" style="position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); font-weight: 600;"></span>
            </div>
        </div>
        
        <div id="mbr-cc-scan-results"></div>
    </div>
    
    <!-- Manual Script Addition -->
    <div class="mbr-cc-settings-section">
        <h2><?php esc_html_e('Add Custom Script', 'mbr-cookie-consent'); ?></h2>
        
        <form id="mbr-cc-add-blocked-script-form">
            <table class="form-table">
                <tr>
                    <th><label for="script_name"><?php esc_html_e('Name', 'mbr-cookie-consent'); ?></label></th>
                    <td><input type="text" name="script_name" id="script_name" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="script_identifier"><?php esc_html_e('Identifier', 'mbr-cookie-consent'); ?></label></th>
                    <td>
                        <input type="text" name="script_identifier" id="script_identifier" class="regular-text" required>
                        <p class="description"><?php esc_html_e('URL pattern or content to match (e.g., "google-analytics.com" or "gtag(")', 'mbr-cookie-consent'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="script_type"><?php esc_html_e('Type', 'mbr-cookie-consent'); ?></label></th>
                    <td>
                        <select name="script_type" id="script_type">
                            <option value="src"><?php esc_html_e('External (src)', 'mbr-cookie-consent'); ?></option>
                            <option value="inline"><?php esc_html_e('Inline', 'mbr-cookie-consent'); ?></option>
                            <option value="iframe"><?php esc_html_e('Iframe', 'mbr-cookie-consent'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="script_category"><?php esc_html_e('Category', 'mbr-cookie-consent'); ?></label></th>
                    <td>
                        <select name="script_category" id="script_category">
                            <option value="analytics"><?php esc_html_e('Analytics', 'mbr-cookie-consent'); ?></option>
                            <option value="marketing"><?php esc_html_e('Marketing', 'mbr-cookie-consent'); ?></option>
                            <option value="preferences"><?php esc_html_e('Preferences', 'mbr-cookie-consent'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="script_description"><?php esc_html_e('Description', 'mbr-cookie-consent'); ?></label></th>
                    <td><textarea name="script_description" id="script_description" class="large-text" rows="3"></textarea></td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary"><?php esc_html_e('Add Script', 'mbr-cookie-consent'); ?></button>
            </p>
        </form>
    </div>
    
    <!-- Blocked Scripts List -->
    <?php if (!empty($blocked_scripts)) : ?>
        <div class="mbr-cc-settings-section mbr-cc-blocked-scripts">
            <h2><?php esc_html_e('Currently Blocked Scripts', 'mbr-cookie-consent'); ?></h2>
            
            <?php foreach ($blocked_scripts as $index => $script) : ?>
                <div class="mbr-cc-script-item">
                    <div class="mbr-cc-script-info">
                        <h4><?php echo esc_html($script['name']); ?></h4>
                        <p><strong><?php esc_html_e('Type:', 'mbr-cookie-consent'); ?></strong> <?php echo esc_html($script['type']); ?></p>
                        <p><strong><?php esc_html_e('Category:', 'mbr-cookie-consent'); ?></strong> <?php echo esc_html($script['category']); ?></p>
                        <p class="mbr-cc-script-meta"><code><?php echo esc_html($script['identifier']); ?></code></p>
                        <?php if (!empty($script['description'])) : ?>
                            <p><?php echo esc_html($script['description']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="mbr-cc-script-actions">
                        <button type="button" class="button mbr-cc-remove-script" data-index="<?php echo esc_attr($index); ?>">
                            <?php esc_html_e('Remove', 'mbr-cookie-consent'); ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
