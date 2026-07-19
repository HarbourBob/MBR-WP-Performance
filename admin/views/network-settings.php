<?php
/**
 * Network Settings page view.
 *
 * @package MBR_Cookie_Consent
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

$updated = isset($_GET['updated']) && $_GET['updated'] === 'true';
?>

<div class="wrap">
    <h1><?php esc_html_e('Cookie Consent - Network Settings', 'mbr-cookie-consent'); ?></h1>
    
    <?php if ($updated) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Network settings saved successfully!', 'mbr-cookie-consent'); ?></p>
        </div>
    <?php endif; ?>
    
    <p><?php esc_html_e('Configure network-wide cookie consent settings. Individual sites can optionally override these settings.', 'mbr-cookie-consent'); ?></p>
    
    <form method="post" action="<?php echo esc_url(network_admin_url('edit.php?action=mbr_cc_network_settings')); ?>">
        <?php wp_nonce_field('mbr-cc-network-settings'); ?>
        
        <!-- Banner Layout -->
        <div class="mbr-cc-settings-section">
            <h2><?php esc_html_e('Banner Layout', 'mbr-cookie-consent'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th><label for="network_banner_layout"><?php esc_html_e('Layout', 'mbr-cookie-consent'); ?></label></th>
                    <td>
                        <select name="mbr_cc_network_banner_layout" id="network_banner_layout">
                            <option value="bar" <?php selected(get_site_option('mbr_cc_network_banner_layout', 'bar'), 'bar'); ?>>
                                <?php esc_html_e('Bar', 'mbr-cookie-consent'); ?>
                            </option>
                            <option value="box-left" <?php selected(get_site_option('mbr_cc_network_banner_layout'), 'box-left'); ?>>
                                <?php esc_html_e('Box - Bottom Left', 'mbr-cookie-consent'); ?>
                            </option>
                            <option value="box-right" <?php selected(get_site_option('mbr_cc_network_banner_layout'), 'box-right'); ?>>
                                <?php esc_html_e('Box - Bottom Right', 'mbr-cookie-consent'); ?>
                            </option>
                            <option value="popup" <?php selected(get_site_option('mbr_cc_network_banner_layout'), 'popup'); ?>>
                                <?php esc_html_e('Popup - Center', 'mbr-cookie-consent'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="network_banner_position"><?php esc_html_e('Position', 'mbr-cookie-consent'); ?></label></th>
                    <td>
                        <select name="mbr_cc_network_banner_position" id="network_banner_position">
                            <option value="bottom" <?php selected(get_site_option('mbr_cc_network_banner_position', 'bottom'), 'bottom'); ?>>
                                <?php esc_html_e('Bottom', 'mbr-cookie-consent'); ?>
                            </option>
                            <option value="top" <?php selected(get_site_option('mbr_cc_network_banner_position'), 'top'); ?>>
                                <?php esc_html_e('Top', 'mbr-cookie-consent'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Colors -->
        <div class="mbr-cc-settings-section">
            <h2><?php esc_html_e('Colors', 'mbr-cookie-consent'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th><label for="network_primary_color"><?php esc_html_e('Primary Color', 'mbr-cookie-consent'); ?></label></th>
                    <td>
                        <input type="text" name="mbr_cc_network_primary_color" id="network_primary_color" 
                               value="<?php echo esc_attr(get_site_option('mbr_cc_network_primary_color', '#0073aa')); ?>" 
                               class="color-picker">
                    </td>
                </tr>
                
                <tr>
                    <th><label for="network_accept_button_color"><?php esc_html_e('Accept Button Color', 'mbr-cookie-consent'); ?></label></th>
                    <td>
                        <input type="text" name="mbr_cc_network_accept_button_color" id="network_accept_button_color" 
                               value="<?php echo esc_attr(get_site_option('mbr_cc_network_accept_button_color', '#00a32a')); ?>" 
                               class="color-picker">
                    </td>
                </tr>
                
                <tr>
                    <th><label for="network_reject_button_color"><?php esc_html_e('Reject Button Color', 'mbr-cookie-consent'); ?></label></th>
                    <td>
                        <input type="text" name="mbr_cc_network_reject_button_color" id="network_reject_button_color" 
                               value="<?php echo esc_attr(get_site_option('mbr_cc_network_reject_button_color', '#d63638')); ?>" 
                               class="color-picker">
                    </td>
                </tr>
                
                <tr>
                    <th><label for="network_text_color"><?php esc_html_e('Text Color', 'mbr-cookie-consent'); ?></label></th>
                    <td>
                        <input type="text" name="mbr_cc_network_text_color" id="network_text_color" 
                               value="<?php echo esc_attr(get_site_option('mbr_cc_network_text_color', '#ffffff')); ?>" 
                               class="color-picker">
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Banner Content -->
        <div class="mbr-cc-settings-section">
            <h2><?php esc_html_e('Banner Content', 'mbr-cookie-consent'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th><label for="network_banner_heading"><?php esc_html_e('Heading', 'mbr-cookie-consent'); ?></label></th>
                    <td>
                        <input type="text" name="mbr_cc_network_banner_heading" id="network_banner_heading" 
                               value="<?php echo esc_attr(get_site_option('mbr_cc_network_banner_heading', 'We value your privacy')); ?>" 
                               class="regular-text">
                    </td>
                </tr>
                
                <tr>
                    <th><label for="network_banner_description"><?php esc_html_e('Description', 'mbr-cookie-consent'); ?></label></th>
                    <td>
                        <textarea name="mbr_cc_network_banner_description" id="network_banner_description" rows="4" class="large-text"><?php 
                            echo esc_textarea(get_site_option('mbr_cc_network_banner_description', 'We use cookies to enhance your browsing experience, serve personalized content, and analyze our traffic. By clicking "Accept All", you consent to our use of cookies.'));
                        ?></textarea>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Banner Options -->
        <div class="mbr-cc-settings-section">
            <h2><?php esc_html_e('Banner Options', 'mbr-cookie-consent'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e('Button Options', 'mbr-cookie-consent'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="mbr_cc_network_show_reject_button" value="1" <?php checked(get_site_option('mbr_cc_network_show_reject_button', true)); ?>>
                            <?php esc_html_e('Show Reject All Button', 'mbr-cookie-consent'); ?>
                        </label>
                        <br>
                        <label>
                            <input type="checkbox" name="mbr_cc_network_show_customize_button" value="1" <?php checked(get_site_option('mbr_cc_network_show_customize_button', true)); ?>>
                            <?php esc_html_e('Show Customize Button', 'mbr-cookie-consent'); ?>
                        </label>
                        <br>
                        <label>
                            <input type="checkbox" name="mbr_cc_network_show_close_button" value="1" <?php checked(get_site_option('mbr_cc_network_show_close_button', false)); ?>>
                            <?php esc_html_e('Show X (Close) Button', 'mbr-cookie-consent'); ?>
                            <span class="description"><?php esc_html_e('(Required by Italian law)', 'mbr-cookie-consent'); ?></span>
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="network_cookie_expiry_days"><?php esc_html_e('Cookie Expiry (Days)', 'mbr-cookie-consent'); ?></label></th>
                    <td>
                        <input type="number" name="mbr_cc_network_cookie_expiry_days" id="network_cookie_expiry_days" 
                               value="<?php echo esc_attr(get_site_option('mbr_cc_network_cookie_expiry_days', 365)); ?>" 
                               min="1" max="730">
                        <p class="description"><?php esc_html_e('How long to remember user consent.', 'mbr-cookie-consent'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Site Override Settings -->
        <div class="mbr-cc-settings-section">
            <h2><?php esc_html_e('Site Override Settings', 'mbr-cookie-consent'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e('Allow Site Overrides', 'mbr-cookie-consent'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="mbr_cc_network_allow_site_override" value="1" <?php checked(get_site_option('mbr_cc_network_allow_site_override', true)); ?>>
                            <?php esc_html_e('Allow individual sites to override network settings', 'mbr-cookie-consent'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('When enabled, site administrators can customize their own cookie consent settings. When disabled, all sites use network settings.', 'mbr-cookie-consent'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <p class="submit">
            <input type="submit" name="submit" class="button button-primary" value="<?php esc_attr_e('Save Network Settings', 'mbr-cookie-consent'); ?>">
        </p>
    </form>
    
    <script>
    jQuery(document).ready(function($) {
        $('.color-picker').wpColorPicker();
    });
    </script>
</div>
