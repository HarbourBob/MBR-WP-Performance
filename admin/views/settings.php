<?php
/**
 * Settings View - Complete Tabbed Interface
 * All 657 lines preserved and organized
 *
 * @package MBR_Cookie_Consent
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap mbr-cc-admin-wrap">
    <h1><?php esc_html_e('Cookie Consent Settings', 'mbr-cookie-consent'); ?></h1>
    
    <!-- Tab Navigation -->
    <nav class="mbr-cc-tab-nav">
        <button type="button" class="mbr-cc-tab-button active" data-tab="banner">
            <span class="dashicons dashicons-admin-appearance"></span>
            <?php esc_html_e('Banner Settings', 'mbr-cookie-consent'); ?>
        </button>
        <button type="button" class="mbr-cc-tab-button" data-tab="behavior">
            <span class="dashicons dashicons-admin-settings"></span>
            <?php esc_html_e('Banner Behavior', 'mbr-cookie-consent'); ?>
        </button>
        <button type="button" class="mbr-cc-tab-button" data-tab="consent-mode">
            <span class="dashicons dashicons-google"></span>
            <?php esc_html_e('Consent Mode', 'mbr-cookie-consent'); ?>
        </button>
        <button type="button" class="mbr-cc-tab-button" data-tab="geolocation">
            <span class="dashicons dashicons-location"></span>
            <?php esc_html_e('Geolocation', 'mbr-cookie-consent'); ?>
        </button>
        <button type="button" class="mbr-cc-tab-button" data-tab="i18n">
            <span class="dashicons dashicons-translation"></span>
            <?php esc_html_e('Int\'l & Accessibility', 'mbr-cookie-consent'); ?>
        </button>
        <button type="button" class="mbr-cc-tab-button" data-tab="advanced-consent">
            <span class="dashicons dashicons-shield"></span>
            <?php esc_html_e('Advanced Consent', 'mbr-cookie-consent'); ?>
        </button>
        <button type="button" class="mbr-cc-tab-button" data-tab="customization">
            <span class="dashicons dashicons-admin-tools"></span>
            <?php esc_html_e('Customization', 'mbr-cookie-consent'); ?>
        </button>
        <button type="button" class="mbr-cc-tab-button" data-tab="content-blocking">
            <span class="dashicons dashicons-hidden"></span>
            <?php esc_html_e('Content Blocking', 'mbr-cookie-consent'); ?>
        </button>
    </nav>
    
    <form method="post" id="mbr-cc-settings-form">
        
        <!-- TAB 1: BANNER SETTINGS -->
        <div class="mbr-cc-tab-content active" id="tab-banner">
        <!-- Banner Appearance -->
        <div class="mbr-cc-settings-section">
            <h2><?php esc_html_e('Banner Layout', 'mbr-cookie-consent'); ?></h2>
            <p class="description"><?php esc_html_e('Choose how your cookie banner appears to visitors', 'mbr-cookie-consent'); ?></p>
            
            <div class="mbr-cc-layout-grid">
                <?php
                $current_layout = get_option('mbr_cc_banner_layout', 'bar');
                $current_position = get_option('mbr_cc_banner_position', 'bottom');
                $layouts = array(
                    'bar-bottom' => array('label' => 'Bottom Bar', 'position' => 'bottom', 'layout' => 'bar'),
                    'bar-top' => array('label' => 'Top Bar', 'position' => 'top', 'layout' => 'bar'),
                    'box-left' => array('label' => 'Bottom Left', 'position' => 'bottom', 'layout' => 'box-left'),
                    'box-right' => array('label' => 'Bottom Right', 'position' => 'bottom', 'layout' => 'box-right'),
                    'popup' => array('label' => 'Center Popup', 'position' => 'bottom', 'layout' => 'popup'),
                );
                
                foreach ($layouts as $key => $layout) :
                    $is_selected = ($current_layout === $layout['layout'] && $current_position === $layout['position']);
                ?>
                    <label class="mbr-cc-layout-card <?php echo $is_selected ? 'selected' : ''; ?>">
                        <input type="radio" name="mbr_cc_layout_option" value="<?php echo esc_attr($key); ?>" <?php checked($is_selected); ?>>
                        <div class="layout-preview layout-preview-<?php echo esc_attr($key); ?>">
                            <div class="preview-browser">
                                <div class="preview-banner"></div>
                            </div>
                        </div>
                        <span class="layout-label"><?php echo esc_html($layout['label']); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            
            <!-- Hidden fields for actual values -->
            <input type="hidden" name="mbr_cc_banner_position" id="banner_position" value="<?php echo esc_attr($current_position); ?>">
            <input type="hidden" name="mbr_cc_banner_layout" id="banner_layout" value="<?php echo esc_attr($current_layout); ?>">
        </div>
        
        <!-- Colors -->
        <div class="mbr-cc-settings-section">
            <h2><?php esc_html_e('Colors', 'mbr-cookie-consent'); ?></h2>
            
            <div class="mbr-cc-form-row">
                <div class="mbr-cc-form-field">
                    <label for="primary_color"><?php esc_html_e('Primary Color', 'mbr-cookie-consent'); ?></label>
                    <input type="text" name="mbr_cc_primary_color" id="primary_color" class="mbr-cc-color-picker" value="<?php echo esc_attr(get_option('mbr_cc_primary_color', '#0073aa')); ?>">
                </div>
                
                <div class="mbr-cc-form-field">
                    <label for="text_color"><?php esc_html_e('Text Color', 'mbr-cookie-consent'); ?></label>
                    <input type="text" name="mbr_cc_text_color" id="text_color" class="mbr-cc-color-picker" value="<?php echo esc_attr(get_option('mbr_cc_text_color', '#ffffff')); ?>">
                </div>
                
                <div class="mbr-cc-form-field">
                    <label for="revisit_button_text_color"><?php esc_html_e('Floating Button Text Color', 'mbr-cookie-consent'); ?></label>
                    <input type="text" name="mbr_cc_revisit_button_text_color" id="revisit_button_text_color" class="mbr-cc-color-picker" value="<?php echo esc_attr(get_option('mbr_cc_revisit_button_text_color', '#000000')); ?>">
                </div>
            </div>
            
            <div class="mbr-cc-form-row">
                <div class="mbr-cc-form-field">
                    <label for="accept_button_color"><?php esc_html_e('Accept Button Color', 'mbr-cookie-consent'); ?></label>
                    <input type="text" name="mbr_cc_accept_button_color" id="accept_button_color" class="mbr-cc-color-picker" value="<?php echo esc_attr(get_option('mbr_cc_accept_button_color', '#00a32a')); ?>">
                </div>
                
                <div class="mbr-cc-form-field">
                    <label for="reject_button_color"><?php esc_html_e('Reject Button Color', 'mbr-cookie-consent'); ?></label>
                    <input type="text" name="mbr_cc_reject_button_color" id="reject_button_color" class="mbr-cc-color-picker" value="<?php echo esc_attr(get_option('mbr_cc_reject_button_color', '#d63638')); ?>">
                </div>
            </div>
        </div>
        <!-- Banner Content -->
        <div class="mbr-cc-settings-section">
            <h2><?php esc_html_e('Banner Content', 'mbr-cookie-consent'); ?></h2>
            
            <div class="mbr-cc-form-row">
                <div class="mbr-cc-form-field">
                    <label for="banner_heading"><?php esc_html_e('Banner Heading', 'mbr-cookie-consent'); ?></label>
                    <input type="text" name="mbr_cc_banner_heading" id="banner_heading" value="<?php echo esc_attr(get_option('mbr_cc_banner_heading', 'We value your privacy')); ?>">
                </div>
            </div>
            
            <div class="mbr-cc-form-row">
                <div class="mbr-cc-form-field" style="min-width: 100%;">
                    <label for="banner_description"><?php esc_html_e('Banner Description', 'mbr-cookie-consent'); ?></label>
                    <textarea name="mbr_cc_banner_description" id="banner_description"><?php echo esc_textarea(get_option('mbr_cc_banner_description', 'We use cookies to enhance your browsing experience, serve personalized content, and analyze our traffic. By clicking "Accept All", you consent to our use of cookies.')); ?></textarea>
                </div>
            </div>
            
            <div class="mbr-cc-form-row">
                <div class="mbr-cc-form-field">
                    <label for="accept_button_text"><?php esc_html_e('Accept Button Text', 'mbr-cookie-consent'); ?></label>
                    <input type="text" name="mbr_cc_accept_button_text" id="accept_button_text" value="<?php echo esc_attr(get_option('mbr_cc_accept_button_text', 'Accept All')); ?>">
                </div>
                
                <div class="mbr-cc-form-field">
                    <label for="reject_button_text"><?php esc_html_e('Reject Button Text', 'mbr-cookie-consent'); ?></label>
                    <input type="text" name="mbr_cc_reject_button_text" id="reject_button_text" value="<?php echo esc_attr(get_option('mbr_cc_reject_button_text', 'Reject All')); ?>">
                </div>
                
                <div class="mbr-cc-form-field">
                    <label for="customize_button_text"><?php esc_html_e('Customize Button Text', 'mbr-cookie-consent'); ?></label>
                    <input type="text" name="mbr_cc_customize_button_text" id="customize_button_text" value="<?php echo esc_attr(get_option('mbr_cc_customize_button_text', 'Customize')); ?>">
                </div>
            </div>
        </div>
        <!-- Revisit Consent -->
        <div class="mbr-cc-settings-section">
            <h2><?php esc_html_e('Revisit Consent Button', 'mbr-cookie-consent'); ?></h2>
            
            <div class="mbr-cc-form-row">
                <div class="mbr-cc-toggle-wrapper">
                    <label class="mbr-cc-toggle-switch">
                        <input type="checkbox" name="mbr_cc_revisit_consent_enabled" value="1" <?php checked(get_option('mbr_cc_revisit_consent_enabled', true)); ?>>
                        <span class="mbr-cc-toggle-slider"></span>
                    </label>
                    <div class="mbr-cc-toggle-label">
                        <strong><?php esc_html_e('Show Revisit Consent Button', 'mbr-cookie-consent'); ?></strong>
                        <p class="description"><?php esc_html_e('Display a floating button allowing users to change their cookie preferences.', 'mbr-cookie-consent'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="mbr-cc-form-row">
                <div class="mbr-cc-form-field">
                    <label for="revisit_consent_text"><?php esc_html_e('Button Text', 'mbr-cookie-consent'); ?></label>
                    <input type="text" name="mbr_cc_revisit_consent_text" id="revisit_consent_text" value="<?php echo esc_attr(get_option('mbr_cc_revisit_consent_text', 'Cookie Settings')); ?>">
                </div>
            </div>
        </div>
        <!-- Policy Links -->
        <div class="mbr-cc-settings-section">
            <h2><?php esc_html_e('Policy Links', 'mbr-cookie-consent'); ?></h2>
            <p><?php esc_html_e('Add links to your privacy and cookie policies in the banner.', 'mbr-cookie-consent'); ?></p>
            
            <div class="mbr-cc-form-row">
                <div class="mbr-cc-form-field" style="width: 100%;">
                    <h4><?php esc_html_e('Privacy Policy', 'mbr-cookie-consent'); ?></h4>
                    <label>
                        <input type="checkbox" name="mbr_cc_show_privacy_policy_link" value="1" <?php checked(get_option('mbr_cc_show_privacy_policy_link', false)); ?>>
                        <?php esc_html_e('Show Privacy Policy link in banner', 'mbr-cookie-consent'); ?>
                    </label>
                    <br><br>
                    <label for="privacy_policy_text"><?php esc_html_e('Link Text', 'mbr-cookie-consent'); ?></label>
                    <input type="text" name="mbr_cc_privacy_policy_text" id="privacy_policy_text" value="<?php echo esc_attr(get_option('mbr_cc_privacy_policy_text', 'Privacy Policy')); ?>">
                    <br><br>
                    <label for="privacy_policy_url"><?php esc_html_e('URL', 'mbr-cookie-consent'); ?></label>
                    <input type="url" name="mbr_cc_privacy_policy_url" id="privacy_policy_url" value="<?php echo esc_url(get_option('mbr_cc_privacy_policy_url', '')); ?>" placeholder="https://example.com/privacy-policy" style="width: 100%;">
                </div>
            </div>
            
            <div class="mbr-cc-form-row">
                <div class="mbr-cc-form-field" style="width: 100%;">
                    <h4><?php esc_html_e('Cookie Policy', 'mbr-cookie-consent'); ?></h4>
                    <label>
                        <input type="checkbox" name="mbr_cc_show_cookie_policy_link" value="1" <?php checked(get_option('mbr_cc_show_cookie_policy_link', false)); ?>>
                        <?php esc_html_e('Show Cookie Policy link in banner', 'mbr-cookie-consent'); ?>
                    </label>
                    <br><br>
                    <label for="cookie_policy_text"><?php esc_html_e('Link Text', 'mbr-cookie-consent'); ?></label>
                    <input type="text" name="mbr_cc_cookie_policy_text" id="cookie_policy_text" value="<?php echo esc_attr(get_option('mbr_cc_cookie_policy_text', 'Cookie Policy')); ?>">
                    <br><br>
                    <label for="cookie_policy_url"><?php esc_html_e('URL', 'mbr-cookie-consent'); ?></label>
                    <input type="url" name="mbr_cc_cookie_policy_url" id="cookie_policy_url" value="<?php echo esc_url(get_option('mbr_cc_cookie_policy_url', '')); ?>" placeholder="https://example.com/cookie-policy" style="width: 100%;">
                </div>
            </div>
        </div>
        
        <!-- Banner Branding -->
        <div class="mbr-cc-settings-section">
            <h2><?php esc_html_e('Banner Branding', 'mbr-cookie-consent'); ?></h2>
            <p><?php esc_html_e('Add your logo to the banner (recommended size: 150x150px).', 'mbr-cookie-consent'); ?></p>
            
            <div class="mbr-cc-form-row">
                <div class="mbr-cc-form-field" style="width: 100%;">
                    <label for="banner_logo_url"><?php esc_html_e('Logo URL', 'mbr-cookie-consent'); ?></label>
                    <input type="url" name="mbr_cc_banner_logo_url" id="banner_logo_url" value="<?php echo esc_url(get_option('mbr_cc_banner_logo_url', '')); ?>" placeholder="https://example.com/logo.png" style="width: 100%;">
                    <p class="description"><?php esc_html_e('Enter the URL of your logo image. The logo will be displayed to the left of the banner heading (150x150px recommended).', 'mbr-cookie-consent'); ?></p>
                    <?php if (get_option('mbr_cc_banner_logo_url')) : ?>
                        <div style="margin-top: 15px;">
                            <strong><?php esc_html_e('Preview:', 'mbr-cookie-consent'); ?></strong><br>
                            <img src="<?php echo esc_url(get_option('mbr_cc_banner_logo_url')); ?>" style="max-width: 150px; max-height: 150px; margin-top: 10px; border: 1px solid #ddd; padding: 5px; background: #fff;">
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        
        </div>
        
        <!-- TAB 2: BANNER BEHAVIOR -->
        <div class="mbr-cc-tab-content" id="tab-behavior">
        <!-- Banner Options -->
        <div class="mbr-cc-settings-section">
            <h2><?php esc_html_e('Banner Options', 'mbr-cookie-consent'); ?></h2>
            
            <div class="mbr-cc-form-row">
                <div class="mbr-cc-toggle-wrapper">
                    <label class="mbr-cc-toggle-switch">
                        <input type="checkbox" name="mbr_cc_show_reject_button" value="1" <?php checked(get_option('mbr_cc_show_reject_button', true)); ?>>
                        <span class="mbr-cc-toggle-slider"></span>
                    </label>
                    <div class="mbr-cc-toggle-label">
                        <strong><?php esc_html_e('Show Reject All Button', 'mbr-cookie-consent'); ?></strong>
                        <p class="description"><?php esc_html_e('Recommended for GDPR compliance', 'mbr-cookie-consent'); ?></p>
                    </div>
                </div>
                
                <div class="mbr-cc-toggle-wrapper">
                    <label class="mbr-cc-toggle-switch">
                        <input type="checkbox" name="mbr_cc_show_customize_button" value="1" <?php checked(get_option('mbr_cc_show_customize_button', true)); ?>>
                        <span class="mbr-cc-toggle-slider"></span>
                    </label>
                    <div class="mbr-cc-toggle-label">
                        <strong><?php esc_html_e('Show Customize Button', 'mbr-cookie-consent'); ?></strong>
                        <p class="description"><?php esc_html_e('Lets users choose which cookie categories to accept', 'mbr-cookie-consent'); ?></p>
                    </div>
                </div>
                
                <div class="mbr-cc-toggle-wrapper">
                    <label class="mbr-cc-toggle-switch">
                        <input type="checkbox" name="mbr_cc_show_close_button" value="1" <?php checked(get_option('mbr_cc_show_close_button', false)); ?>>
                        <span class="mbr-cc-toggle-slider"></span>
                    </label>
                    <div class="mbr-cc-toggle-label">
                        <strong><?php esc_html_e('Show X (Close) Button', 'mbr-cookie-consent'); ?></strong>
                        <p class="description"><?php esc_html_e('Required by Italian law. Closes banner without saving consent.', 'mbr-cookie-consent'); ?></p>
                    </div>
                </div>
                
                <div class="mbr-cc-toggle-wrapper">
                    <label class="mbr-cc-toggle-switch">
                        <input type="checkbox" name="mbr_cc_reload_on_consent" value="1" <?php checked(get_option('mbr_cc_reload_on_consent', false)); ?>>
                        <span class="mbr-cc-toggle-slider"></span>
                    </label>
                    <div class="mbr-cc-toggle-label">
                        <strong><?php esc_html_e('Reload Page on Consent', 'mbr-cookie-consent'); ?></strong>
                        <p class="description"><?php esc_html_e('Automatically reload the page when users accept or reject cookies.', 'mbr-cookie-consent'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="mbr-cc-form-row">
                <div class="mbr-cc-form-field">
                    <label for="cookie_expiry_days"><?php esc_html_e('Cookie Expiry (Days)', 'mbr-cookie-consent'); ?></label>
                    <input type="number" name="mbr_cc_cookie_expiry_days" id="cookie_expiry_days" value="<?php echo esc_attr(get_option('mbr_cc_cookie_expiry_days', 365)); ?>" min="1" max="730">
                    <p class="description"><?php esc_html_e('How long to remember user consent.', 'mbr-cookie-consent'); ?></p>
                </div>
            </div>
        </div>
        <!-- CCPA Options -->
        <div class="mbr-cc-settings-section">
            <h2><?php esc_html_e('CCPA / California Privacy', 'mbr-cookie-consent'); ?></h2>
            
            <div class="mbr-cc-form-row">
                <div class="mbr-cc-toggle-wrapper">
                    <label class="mbr-cc-toggle-switch">
                        <input type="checkbox" name="mbr_cc_enable_ccpa" value="1" <?php checked(get_option('mbr_cc_enable_ccpa', false)); ?>>
                        <span class="mbr-cc-toggle-slider"></span>
                    </label>
                    <div class="mbr-cc-toggle-label">
                        <strong><?php esc_html_e('Enable CCPA "Do Not Sell" Link', 'mbr-cookie-consent'); ?></strong>
                        <p class="description"><?php esc_html_e('Shows a clickable link in the banner that allows users to opt out of data selling/sharing. When clicked, it rejects all Marketing and Analytics cookies (CCPA compliance for California users).', 'mbr-cookie-consent'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="mbr-cc-form-row">
                <div class="mbr-cc-form-field">
                    <label for="ccpa_link_text"><?php esc_html_e('CCPA Link Text', 'mbr-cookie-consent'); ?></label>
                    <input type="text" name="mbr_cc_ccpa_link_text" id="ccpa_link_text" value="<?php echo esc_attr(get_option('mbr_cc_ccpa_link_text', 'Do Not Sell or Share My Personal Information')); ?>">
                    <p class="description"><?php esc_html_e('Customize the text shown for the CCPA opt-out link. The default wording is legally compliant with California law.', 'mbr-cookie-consent'); ?></p>
                </div>
            </div>
        </div>
        
        </div>
        
        <!-- TAB 3: CONSENT MODE INTEGRATION -->
        <div class="mbr-cc-tab-content" id="tab-consent-mode">
        <!-- Consent Mode Integration -->
        <div class="mbr-cc-settings-section">
            <h2><?php esc_html_e('Consent Mode Integration', 'mbr-cookie-consent'); ?></h2>
            <p><?php esc_html_e('Integrate with Google Consent Mode v2 and Microsoft UET Consent Mode for enhanced ad tracking compliance.', 'mbr-cookie-consent'); ?></p>
            
            <div class="mbr-cc-form-row">
                <div class="mbr-cc-form-field" style="width: 100%;">
                    <h3><?php esc_html_e('Google Consent Mode v2', 'mbr-cookie-consent'); ?></h3>
                    <p class="description"><?php esc_html_e('Google Consent Mode allows Google tags (Analytics, Ads) to adjust their behavior based on user consent choices. This ensures compliance while maintaining conversion measurement capabilities.', 'mbr-cookie-consent'); ?></p>
                    
                    <div class="mbr-cc-toggle-wrapper" style="margin-top: 15px;">
                        <label class="mbr-cc-toggle-switch">
                            <input type="checkbox" name="mbr_cc_google_consent_mode" value="1" <?php checked(get_option('mbr_cc_google_consent_mode', false)); ?>>
                            <span class="mbr-cc-toggle-slider"></span>
                        </label>
                        <div class="mbr-cc-toggle-label">
                            <strong><?php esc_html_e('Enable Google Consent Mode v2', 'mbr-cookie-consent'); ?></strong>
                            <p class="description"><?php esc_html_e('Automatically updates Google services consent status', 'mbr-cookie-consent'); ?></p>
                        </div>
                    </div>
                    <p class="description"><?php esc_html_e('Activates consent mode signals for Google Analytics, Google Ads, and other Google services.', 'mbr-cookie-consent'); ?></p>
                    
                    <div style="margin-top: 15px; padding-left: 25px;">
                        <label style="display: block; margin-bottom: 10px;">
                            <input type="checkbox" name="mbr_cc_google_default_deny" value="1" <?php checked(get_option('mbr_cc_google_default_deny', true)); ?>>
                            <?php esc_html_e('Default to "Denied" (Recommended for EU/EEA)', 'mbr-cookie-consent'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Sets default consent state to denied before user interaction. Recommended for GDPR compliance.', 'mbr-cookie-consent'); ?></p>
                        
                        <label style="display: block; margin-top: 15px; margin-bottom: 10px;">
                            <input type="checkbox" name="mbr_cc_google_ads_redaction" value="1" <?php checked(get_option('mbr_cc_google_ads_redaction', true)); ?>>
                            <?php esc_html_e('Enable Ads Data Redaction', 'mbr-cookie-consent'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Redacts ads-related data when marketing consent is not given. Recommended for privacy compliance.', 'mbr-cookie-consent'); ?></p>
                        
                        <label style="display: block; margin-top: 15px; margin-bottom: 10px;">
                            <input type="checkbox" name="mbr_cc_google_url_passthrough" value="1" <?php checked(get_option('mbr_cc_google_url_passthrough', false)); ?>>
                            <?php esc_html_e('Enable URL Passthrough', 'mbr-cookie-consent'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Passes ad click information through URLs for conversion tracking without cookies. Use with caution as it may affect privacy.', 'mbr-cookie-consent'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="mbr-cc-form-row" style="margin-top: 30px;">
                <div class="mbr-cc-form-field" style="width: 100%;">
                    <h3><?php esc_html_e('Microsoft UET Consent Mode', 'mbr-cookie-consent'); ?></h3>
                    <p class="description"><?php esc_html_e('Microsoft UET (Universal Event Tracking) Consent Mode ensures Microsoft Advertising tags comply with EU data protection requirements by adjusting tag behavior based on consent.', 'mbr-cookie-consent'); ?></p>
                    
                    <label style="margin-top: 15px; display: block;">
                        <input type="checkbox" name="mbr_cc_microsoft_consent_mode" value="1" <?php checked(get_option('mbr_cc_microsoft_consent_mode', false)); ?>>
                        <?php esc_html_e('Enable Microsoft UET Consent Mode', 'mbr-cookie-consent'); ?>
                    </label>
                    <p class="description"><?php esc_html_e('Activates consent mode signals for Microsoft Advertising (Bing Ads) UET tags.', 'mbr-cookie-consent'); ?></p>
                    
                    <div style="margin-top: 15px; padding-left: 25px;">
                        <label style="display: block; margin-bottom: 10px;">
                            <input type="checkbox" name="mbr_cc_microsoft_default_deny" value="1" <?php checked(get_option('mbr_cc_microsoft_default_deny', true)); ?>>
                            <?php esc_html_e('Default to "Denied" (Recommended for EU)', 'mbr-cookie-consent'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Sets default consent state to denied before user interaction. Recommended for GDPR compliance.', 'mbr-cookie-consent'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="mbr-cc-form-row" style="margin-top: 20px; padding: 15px; background: #f0f0f0; border-left: 4px solid #0073aa;">
                <p style="margin: 0;"><strong><?php esc_html_e('Important:', 'mbr-cookie-consent'); ?></strong> <?php esc_html_e('Consent Mode integration requires that your Google Analytics, Google Ads, or Microsoft UET tags are already installed on your site. The consent mode script must load BEFORE these tags. This plugin automatically handles the correct loading order.', 'mbr-cookie-consent'); ?></p>
            </div>
        </div>
        
        
        </div>
        
        <!-- TAB 3.5: GEOLOCATION -->
        <div class="mbr-cc-tab-content" id="tab-geolocation">
            <?php require_once MBR_CC_PLUGIN_DIR . 'admin/views/geolocation-settings.php'; ?>
        </div>
        
        <!-- TAB 4: INTERNATIONALIZATION & ACCESSIBILITY -->
        <div class="mbr-cc-tab-content" id="tab-i18n">
        <!-- Internationalization & Accessibility -->
        <div class="mbr-cc-settings-section">
            <h2><?php esc_html_e('Internationalization & Accessibility', 'mbr-cookie-consent'); ?></h2>
            <p><?php esc_html_e('Configure multilingual support and accessibility features to ensure your cookie banner is inclusive and WCAG compliant.', 'mbr-cookie-consent'); ?></p>
            
            <div class="mbr-cc-form-row">
                <div class="mbr-cc-form-field" style="width: 100%;">
                    <h3><?php esc_html_e('Auto-Translation (40+ Languages)', 'mbr-cookie-consent'); ?></h3>
                    <p class="description"><?php esc_html_e('Automatically translate banner text based on visitor browser language. Supports 40+ languages including English, Spanish, French, German, Japanese, Chinese, and many more.', 'mbr-cookie-consent'); ?></p>
                    
                    <label style="margin-top: 15px; display: block;">
                        <input type="checkbox" name="mbr_cc_auto_translate" value="1" <?php checked(get_option('mbr_cc_auto_translate', true)); ?>>
                        <?php esc_html_e('Enable Auto-Translation', 'mbr-cookie-consent'); ?>
                    </label>
                    <p class="description"><?php esc_html_e('Automatically detects visitor language from browser settings and displays the banner in their native language.', 'mbr-cookie-consent'); ?></p>
                    
                    <?php 
                    $multilingual_plugin = MBR_CC_I18n_Accessibility::get_active_multilingual_plugin();
                    if ($multilingual_plugin) : 
                    ?>
                        <div style="margin-top: 15px; padding: 15px; background: #e7f3e7; border-left: 4px solid #46b450;">
                            <p style="margin: 0;"><strong>
                                <?php
                                /* translators: %s: name of the detected multilingual plugin. */
                                echo sprintf( esc_html__( '%s Detected', 'mbr-cookie-consent' ), esc_html( $multilingual_plugin ) );
                                ?>
                            </strong></p>
                            <p style="margin: 5px 0 0 0;"><?php esc_html_e('Your multilingual plugin will handle translations. Banner strings have been registered for translation.', 'mbr-cookie-consent'); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div style="margin-top: 15px;">
                        <strong><?php esc_html_e('Supported Languages:', 'mbr-cookie-consent'); ?></strong>
                        <p style="margin: 5px 0; font-size: 13px; line-height: 1.8;">
                            <?php 
                            $languages = MBR_CC_I18n_Accessibility::get_supported_languages();
                            $lang_names = array();
                            foreach ($languages as $code => $name) {
                                $lang_names[] = $name;
                            }
                            echo esc_html(implode(', ', array_slice($lang_names, 0, 20)));
                            ?>
                            <em>
                                <?php
                                /* translators: %d: number of additional languages not shown. */
                                echo sprintf( esc_html__( 'and %d more...', 'mbr-cookie-consent' ), count( $lang_names ) - 20 );
                                ?>
                            </em>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="mbr-cc-form-row" style="margin-top: 30px;">
                <div class="mbr-cc-form-field" style="width: 100%;">
                    <h3><?php esc_html_e('WCAG/ADA Compliance', 'mbr-cookie-consent'); ?></h3>
                    <p class="description"><?php esc_html_e('Accessibility features ensure your cookie banner is usable by everyone, including people using screen readers and keyboard navigation.', 'mbr-cookie-consent'); ?></p>
                    
                    <label style="margin-top: 15px; display: block;">
                        <input type="checkbox" name="mbr_cc_wcag_compliance" value="1" <?php checked(get_option('mbr_cc_wcag_compliance', true)); ?>>
                        <?php esc_html_e('Enable WCAG/ADA Compliance Features', 'mbr-cookie-consent'); ?>
                    </label>
                    <p class="description"><?php esc_html_e('Enables accessibility enhancements including screen reader announcements, keyboard navigation, focus management, and proper ARIA attributes.', 'mbr-cookie-consent'); ?></p>
                    
                    <div style="margin-top: 15px; padding: 15px; background: #f0f0f0; border-left: 4px solid #0073aa;">
                        <strong><?php esc_html_e('Accessibility Features Included:', 'mbr-cookie-consent'); ?></strong>
                        <ul style="margin: 10px 0 0 20px; line-height: 1.8;">
                            <li><?php esc_html_e('✓ Screen reader announcements for all interactions', 'mbr-cookie-consent'); ?></li>
                            <li><?php esc_html_e('✓ Full keyboard navigation support', 'mbr-cookie-consent'); ?></li>
                            <li><?php esc_html_e('✓ Focus trap in modal dialogs', 'mbr-cookie-consent'); ?></li>
                            <li><?php esc_html_e('✓ Proper ARIA labels and roles', 'mbr-cookie-consent'); ?></li>
                            <li><?php esc_html_e('✓ High contrast mode support', 'mbr-cookie-consent'); ?></li>
                            <li><?php esc_html_e('✓ Reduced motion support for animations', 'mbr-cookie-consent'); ?></li>
                            <li><?php esc_html_e('✓ WCAG 2.1 AA compliant focus indicators', 'mbr-cookie-consent'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="mbr-cc-form-row" style="margin-top: 20px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107;">
                <p style="margin: 0;"><strong><?php esc_html_e('Note:', 'mbr-cookie-consent'); ?></strong> <?php esc_html_e('For WPML or Polylang users, edit your banner text translations through your multilingual plugin\'s string translation interface. The plugin automatically registers all translatable strings.', 'mbr-cookie-consent'); ?></p>
            </div>
        </div>
        
        
        </div>
        
        <!-- TAB 5: ADVANCED CONSENT MANAGEMENT -->
        <div class="mbr-cc-tab-content" id="tab-advanced-consent">
        <!-- Advanced Consent Management -->
        <div class="mbr-cc-settings-section">
            <h2><?php esc_html_e('Advanced Consent Management', 'mbr-cookie-consent'); ?></h2>
            <p><?php esc_html_e('Enterprise-grade consent frameworks for publishers and advertisers including IAB TCF v2.3 and Google Additional Consent Mode.', 'mbr-cookie-consent'); ?></p>
            
            <!-- IAB TCF v2.3 -->
            <div class="mbr-cc-form-row">
                <div class="mbr-cc-form-field" style="width: 100%;">
                    <h3><?php esc_html_e('IAB Transparency & Consent Framework (TCF) v2.3', 'mbr-cookie-consent'); ?></h3>
                    <p class="description"><?php esc_html_e('The IAB Europe Transparency & Consent Framework (TCF) is the industry standard for managing consent for digital advertising. Required for publishers and advertisers operating in Europe.', 'mbr-cookie-consent'); ?></p>
                    
                    <label style="margin-top: 15px; display: block;">
                        <input type="checkbox" name="mbr_cc_iab_tcf_enabled" value="1" <?php checked(get_option('mbr_cc_iab_tcf_enabled', false)); ?>>
                        <?php esc_html_e('Enable IAB TCF v2.3', 'mbr-cookie-consent'); ?>
                    </label>
                    <p class="description"><?php esc_html_e('Implements the __tcfapi JavaScript API and generates TCF-compliant consent strings.', 'mbr-cookie-consent'); ?></p>
                    
                    <div style="margin-top: 20px; padding: 15px; background: #e7f3e7; border-left: 4px solid #46b450;">
                        <strong><?php esc_html_e('What is IAB TCF?', 'mbr-cookie-consent'); ?></strong>
                        <p style="margin: 10px 0 0 0;"><?php esc_html_e('The TCF enables publishers and advertisers to communicate user consent to ad tech vendors in a standardized way. It includes:', 'mbr-cookie-consent'); ?></p>
                        <ul style="margin: 10px 0 0 20px; line-height: 1.8;">
                            <li><?php esc_html_e('11 standardized consent purposes', 'mbr-cookie-consent'); ?></li>
                            <li><?php esc_html_e('2 special features (geolocation, device scanning)', 'mbr-cookie-consent'); ?></li>
                            <li><?php esc_html_e('Global Vendor List (GVL) of registered vendors', 'mbr-cookie-consent'); ?></li>
                            <li><?php esc_html_e('TC String format for consent storage', 'mbr-cookie-consent'); ?></li>
                        </ul>
                    </div>
                    
                    <div style="margin-top: 15px;">
                        <h4><?php esc_html_e('TCF Configuration', 'mbr-cookie-consent'); ?></h4>
                        
                        <label for="publisher_country_code" style="display: block; margin-top: 10px;"><?php esc_html_e('Publisher Country Code:', 'mbr-cookie-consent'); ?></label>
                        <input type="text" 
                               name="mbr_cc_publisher_country_code" 
                               id="publisher_country_code" 
                               value="<?php echo esc_attr(get_option('mbr_cc_publisher_country_code', '')); ?>"
                               placeholder="GB"
                               maxlength="2"
                               style="width: 100px; text-transform: uppercase;">
                        <p class="description"><?php esc_html_e('2-letter ISO country code (e.g., GB, DE, FR). Required for TCF compliance.', 'mbr-cookie-consent'); ?></p>
                        
                        <label style="display: block; margin-top: 15px;">
                            <input type="checkbox" name="mbr_cc_purpose_one_treatment" value="1" <?php checked(get_option('mbr_cc_purpose_one_treatment', false)); ?>>
                            <?php esc_html_e('Enable Purpose One Treatment', 'mbr-cookie-consent'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('For publishers in jurisdictions that do not require consent for Purpose 1 (Store/Access Information). Consult legal counsel.', 'mbr-cookie-consent'); ?></p>
                        
                        <label for="gdpr_applies" style="display: block; margin-top: 15px;"><?php esc_html_e('GDPR Applies:', 'mbr-cookie-consent'); ?></label>
                        <select name="mbr_cc_gdpr_applies" id="gdpr_applies">
                            <option value="auto" <?php selected(get_option('mbr_cc_gdpr_applies'), 'auto'); ?>><?php esc_html_e('Auto-detect', 'mbr-cookie-consent'); ?></option>
                            <option value="yes" <?php selected(get_option('mbr_cc_gdpr_applies'), 'yes'); ?>><?php esc_html_e('Yes (Always)', 'mbr-cookie-consent'); ?></option>
                            <option value="no" <?php selected(get_option('mbr_cc_gdpr_applies'), 'no'); ?>><?php esc_html_e('No (Never)', 'mbr-cookie-consent'); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e('Whether GDPR applies to your users. Auto-detect recommended.', 'mbr-cookie-consent'); ?></p>
                    </div>
                    
                    <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107;">
                        <strong><?php esc_html_e('Important:', 'mbr-cookie-consent'); ?></strong>
                        <p style="margin: 5px 0 0 0;"><?php esc_html_e('IAB TCF requires CMP registration. You must register as a Consent Management Platform with IAB Europe to obtain a CMP ID. This plugin provides a placeholder implementation. For production use, complete registration at:', 'mbr-cookie-consent'); ?> 
                            <a href="https://iabeurope.eu/tcf-for-cmps/" target="_blank">https://iabeurope.eu/tcf-for-cmps/</a>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Google ACM -->
            <div class="mbr-cc-form-row" style="margin-top: 30px;">
                <div class="mbr-cc-form-field" style="width: 100%;">
                    <h3><?php esc_html_e('Google Additional Consent Mode (ACM)', 'mbr-cookie-consent'); ?></h3>
                    <p class="description"><?php esc_html_e('Google\'s Additional Consent Mode manages consent for Google Ad Tech Providers (ATPs) that are not part of the IAB Global Vendor List. Required if using Google advertising products.', 'mbr-cookie-consent'); ?></p>
                    
                    <label style="margin-top: 15px; display: block;">
                        <input type="checkbox" name="mbr_cc_google_acm_enabled" value="1" <?php checked(get_option('mbr_cc_google_acm_enabled', false)); ?>>
                        <?php esc_html_e('Enable Google Additional Consent Mode', 'mbr-cookie-consent'); ?>
                    </label>
                    <p class="description"><?php esc_html_e('Generates AC String for Google Ad Tech Providers outside the IAB framework.', 'mbr-cookie-consent'); ?></p>
                    
                    <div style="margin-top: 20px; padding: 15px; background: #e7f3e7; border-left: 4px solid #46b450;">
                        <strong><?php esc_html_e('What is Google ACM?', 'mbr-cookie-consent'); ?></strong>
                        <p style="margin: 10px 0 0 0;"><?php esc_html_e('Google ACM allows you to manage consent for Google\'s own advertising products separately from IAB TCF. This includes:', 'mbr-cookie-consent'); ?></p>
                        <ul style="margin: 10px 0 0 20px; line-height: 1.8;">
                            <li><?php esc_html_e('Google Ads', 'mbr-cookie-consent'); ?></li>
                            <li><?php esc_html_e('DoubleClick / Google Ad Manager', 'mbr-cookie-consent'); ?></li>
                            <li><?php esc_html_e('AdSense', 'mbr-cookie-consent'); ?></li>
                            <li><?php esc_html_e('Campaign Manager 360', 'mbr-cookie-consent'); ?></li>
                            <li><?php esc_html_e('Display & Video 360', 'mbr-cookie-consent'); ?></li>
                            <li><?php esc_html_e('And other Google ATP providers', 'mbr-cookie-consent'); ?></li>
                        </ul>
                    </div>
                    
                    <div style="margin-top: 15px;">
                        <h4><?php esc_html_e('How ACM Works', 'mbr-cookie-consent'); ?></h4>
                        <ol style="margin: 10px 0 0 20px; line-height: 1.8;">
                            <li><?php esc_html_e('User gives marketing consent via cookie banner', 'mbr-cookie-consent'); ?></li>
                            <li><?php esc_html_e('Plugin generates AC String (e.g., "1~1.2.3.4.5")', 'mbr-cookie-consent'); ?></li>
                            <li><?php esc_html_e('AC String passed to Google ad tags', 'mbr-cookie-consent'); ?></li>
                            <li><?php esc_html_e('Google applies consent to ATP providers', 'mbr-cookie-consent'); ?></li>
                        </ol>
                    </div>
                    
        
        </div>
        
        <!-- TAB 6: CUSTOMIZATION -->
            </div>
        </div>
        </div>
        <div class="mbr-cc-tab-content" id="tab-customization">
        <!-- Enhanced Customization -->
        <div class="mbr-cc-settings-section">
            <h2><?php esc_html_e('Enhanced Customization', 'mbr-cookie-consent'); ?></h2>
            <p><?php esc_html_e('Advanced banner customization including page-specific controls, custom CSS, and subdomain consent sharing.', 'mbr-cookie-consent'); ?></p>
            
            <!-- Page-Specific Controls -->
            <div class="mbr-cc-form-row">
                <div class="mbr-cc-form-field" style="width: 100%;">
                    <h3><?php esc_html_e('Page-Specific Controls', 'mbr-cookie-consent'); ?></h3>
                    <p class="description"><?php esc_html_e('Control where the cookie banner appears. Useful for hiding the banner on checkout, login, or other sensitive pages.', 'mbr-cookie-consent'); ?></p>
                    
                    <div style="margin-top: 15px;">
                        <h4><?php esc_html_e('Quick Exclusions', 'mbr-cookie-consent'); ?></h4>
                        
                        <label style="display: block; margin-bottom: 10px;">
                            <input type="checkbox" name="mbr_cc_exclude_login" value="1" <?php checked(get_option('mbr_cc_exclude_login', false)); ?>>
                            <?php esc_html_e('Hide on Login Pages', 'mbr-cookie-consent'); ?>
                        </label>
                        <p class="description" style="margin-left: 25px; margin-bottom: 15px;"><?php esc_html_e('Excludes WordPress login, WooCommerce login, and other login pages.', 'mbr-cookie-consent'); ?></p>
                        
                        <label style="display: block; margin-bottom: 10px;">
                            <input type="checkbox" name="mbr_cc_exclude_checkout" value="1" <?php checked(get_option('mbr_cc_exclude_checkout', false)); ?>>
                            <?php esc_html_e('Hide on Checkout Pages', 'mbr-cookie-consent'); ?>
                        </label>
                        <p class="description" style="margin-left: 25px; margin-bottom: 15px;"><?php esc_html_e('Excludes WooCommerce and Easy Digital Downloads checkout pages.', 'mbr-cookie-consent'); ?></p>
                        
                        <label style="display: block; margin-bottom: 10px;">
                            <input type="checkbox" name="mbr_cc_exclude_cart" value="1" <?php checked(get_option('mbr_cc_exclude_cart', false)); ?>>
                            <?php esc_html_e('Hide on Cart Pages', 'mbr-cookie-consent'); ?>
                        </label>
                        <p class="description" style="margin-left: 25px; margin-bottom: 15px;"><?php esc_html_e('Excludes WooCommerce cart page.', 'mbr-cookie-consent'); ?></p>
                        
                        <label style="display: block; margin-bottom: 10px;">
                            <input type="checkbox" name="mbr_cc_exclude_account" value="1" <?php checked(get_option('mbr_cc_exclude_account', false)); ?>>
                            <?php esc_html_e('Hide on Account Pages', 'mbr-cookie-consent'); ?>
                        </label>
                        <p class="description" style="margin-left: 25px;"><?php esc_html_e('Excludes WooCommerce and EDD account/dashboard pages.', 'mbr-cookie-consent'); ?></p>
                    </div>
                    
                    <div style="margin-top: 25px;">
                        <h4><?php esc_html_e('Exclude by URL Pattern', 'mbr-cookie-consent'); ?></h4>
                        <p class="description"><?php esc_html_e('Enter URL patterns to exclude (one per line). Use * as wildcard.', 'mbr-cookie-consent'); ?></p>
                        <textarea name="mbr_cc_excluded_url_patterns" 
                                  rows="5" 
                                  style="width: 100%; max-width: 600px; font-family: monospace;"
                                  placeholder="/checkout/*&#10;/my-account/*&#10;/login/*&#10;/register/*"><?php echo esc_textarea(get_option('mbr_cc_excluded_url_patterns', '')); ?></textarea>
                        <p class="description"><?php esc_html_e('Examples: /checkout/*, /my-account/*, /admin/*, etc.', 'mbr-cookie-consent'); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Custom CSS -->
            <div class="mbr-cc-form-row" style="margin-top: 30px;">
                <div class="mbr-cc-form-field" style="width: 100%;">
                    <h3><?php esc_html_e('Custom CSS', 'mbr-cookie-consent'); ?></h3>
                    <p class="description"><?php esc_html_e('Add custom CSS to override banner styles. Advanced users only.', 'mbr-cookie-consent'); ?></p>
                    
                    <textarea name="mbr_cc_custom_css" 
                              id="mbr-cc-custom-css" 
                              rows="10" 
                              style="width: 100%; font-family: 'Courier New', monospace; font-size: 13px;"
                              placeholder="/* Custom CSS for cookie banner */&#10;.mbr-cc-banner {&#10;    /* Your styles here */&#10;}"><?php echo esc_textarea(get_option('mbr_cc_custom_css', '')); ?></textarea>
                    
                    <div style="margin-top: 10px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107;">
                        <strong><?php esc_html_e('CSS Tips:', 'mbr-cookie-consent'); ?></strong>
                        <ul style="margin: 10px 0 0 20px; line-height: 1.8;">
                            <li><code>.mbr-cc-banner</code> - Main banner container</li>
                            <li><code>.mbr-cc-banner--box-left</code> - Box layout (left)</li>
                            <li><code>.mbr-cc-banner--box-right</code> - Box layout (right)</li>
                            <li><code>.mbr-cc-banner--popup</code> - Popup layout</li>
                            <li><code>.mbr-cc-btn-accept</code> - Accept button</li>
                            <li><code>.mbr-cc-btn-reject</code> - Reject button</li>
                            <li><code>.mbr-cc-modal</code> - Preferences modal</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Subdomain Consent Sharing -->
            <div class="mbr-cc-form-row" style="margin-top: 30px;">
                <div class="mbr-cc-form-field" style="width: 100%;">
                    <h3><?php esc_html_e('Subdomain Consent Sharing', 'mbr-cookie-consent'); ?></h3>
                    <p class="description"><?php esc_html_e('Share consent preferences across all subdomains. Useful for sites with multiple subdomains (shop.example.com, blog.example.com, etc.).', 'mbr-cookie-consent'); ?></p>
                    
                    <label style="margin-top: 15px; display: block;">
                        <input type="checkbox" name="mbr_cc_subdomain_sharing" value="1" <?php checked(get_option('mbr_cc_subdomain_sharing', false)); ?>>
                        <?php esc_html_e('Enable Subdomain Consent Sharing', 'mbr-cookie-consent'); ?>
                    </label>
                    <p class="description"><?php esc_html_e('Cookie consent will be shared across all subdomains of your root domain.', 'mbr-cookie-consent'); ?></p>
                    
                    <div style="margin-top: 15px; padding: 15px; background: #e7f3e7; border-left: 4px solid #46b450;">
                        <strong><?php esc_html_e('Current Configuration:', 'mbr-cookie-consent'); ?></strong>
                        <p style="margin: 10px 0 0 0;">
                            <?php esc_html_e('Cookie Domain:', 'mbr-cookie-consent'); ?> 
                            <code><?php echo esc_html(MBR_CC_Subdomain_Consent::get_cookie_domain_display()); ?></code>
                        </p>
                        <p style="margin: 5px 0 0 0;">
                            <?php esc_html_e('This will share consent across:', 'mbr-cookie-consent'); ?>
                        </p>
                        <ul style="margin: 5px 0 0 20px; font-family: monospace; font-size: 13px;">
                            <?php foreach (MBR_CC_Subdomain_Consent::get_example_subdomains() as $subdomain) : ?>
                                <li><?php echo esc_html($subdomain); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <div style="margin-top: 15px;">
                        <label for="subdomain_root_domain"><?php esc_html_e('Manual Root Domain (Optional):', 'mbr-cookie-consent'); ?></label><br>
                        <input type="text" 
                               name="mbr_cc_subdomain_root_domain" 
                               id="subdomain_root_domain" 
                               value="<?php echo esc_attr(get_option('mbr_cc_subdomain_root_domain', '')); ?>"
                               placeholder="example.com"
                               style="width: 300px;">
                        <p class="description"><?php esc_html_e('Leave empty for auto-detection. Only needed if auto-detection fails (e.g., example.co.uk).', 'mbr-cookie-consent'); ?></p>
                    </div>
                    
                    <div style="margin-top: 15px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107;">
                        <strong><?php esc_html_e('Important:', 'mbr-cookie-consent'); ?></strong>
                        <p style="margin: 5px 0 0 0;"><?php esc_html_e('When enabled, consent given on ANY subdomain will apply to ALL subdomains. Users won\'t see the banner again after giving consent on any subdomain.', 'mbr-cookie-consent'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        </div>
        
        <p class="submit">
            <button type="button" id="mbr-cc-save-settings" class="button button-primary">
                <?php esc_html_e('Save Settings', 'mbr-cookie-consent'); ?>
            </button>
        </p>

        <!-- ═══════════════════════════════════════════════════════════════
             TAB: CONTENT BLOCKING
             New in v1.7.0 — blocked content overlay settings
        ═══════════════════════════════════════════════════════════════ -->
        <div class="mbr-cc-tab-content" id="tab-content-blocking">
        <div class="mbr-cc-settings-section">
            <h2><?php esc_html_e( 'Blocked Content Overlay', 'mbr-cookie-consent' ); ?></h2>
            <p><?php esc_html_e( 'When content (such as an embedded map or video) is blocked because the visitor has not granted cookie consent, you can display a branded placeholder modal instead of a blank space. The modal explains why the content is hidden and provides a direct link to your cookie settings.', 'mbr-cookie-consent' ); ?></p>

            <div style="margin-bottom: 20px; padding: 14px 16px; background: #e7f3e7; border-left: 4px solid #46b450;">
                <strong><?php esc_html_e( 'Legal note:', 'mbr-cookie-consent' ); ?></strong>
                <?php esc_html_e( 'Displaying this overlay is considered best practice under GDPR, UK GDPR, and similar regulations. It satisfies the transparency requirement by clearly informing visitors why content is unavailable, and fulfils the requirement that consent must be as easy to withdraw or amend as it was to give.', 'mbr-cookie-consent' ); ?>
            </div>

            <!-- Enable toggle -->
            <div class="mbr-cc-form-row">
                <div class="mbr-cc-form-field" style="width: 100%;">
                    <h3><?php esc_html_e( 'Enable Overlay', 'mbr-cookie-consent' ); ?></h3>
                    <label>
                        <input type="checkbox"
                               name="mbr_cc_blocked_overlay_enabled"
                               id="mbr_cc_blocked_overlay_enabled"
                               value="1"
                               <?php checked( get_option( 'mbr_cc_blocked_overlay_enabled', false ) ); ?>>
                        <?php esc_html_e( 'Show a branded placeholder where content is blocked pending cookie consent', 'mbr-cookie-consent' ); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e( 'When enabled, blocked iframes and embeds display a styled modal card with your site logo, an explanatory message, and a button to open cookie settings. When disabled, blocked content is simply invisible.', 'mbr-cookie-consent' ); ?>
                    </p>
                </div>
            </div>

            <!-- Text customisation -->
            <div class="mbr-cc-form-row" style="margin-top: 24px;">
                <div class="mbr-cc-form-field" style="width: 100%;">
                    <h3><?php esc_html_e( 'Overlay Text', 'mbr-cookie-consent' ); ?></h3>
                    <p class="description"><?php esc_html_e( 'Leave any field blank to use the default text shown in the placeholder.', 'mbr-cookie-consent' ); ?></p>

                    <div style="margin-top: 16px;">
                        <label for="mbr_cc_blocked_overlay_heading" style="display:block; font-weight:600; margin-bottom:4px;">
                            <?php esc_html_e( 'Heading', 'mbr-cookie-consent' ); ?>
                        </label>
                        <input type="text"
                               name="mbr_cc_blocked_overlay_heading"
                               id="mbr_cc_blocked_overlay_heading"
                               value="<?php echo esc_attr( get_option( 'mbr_cc_blocked_overlay_heading', '' ) ); ?>"
                               class="regular-text"
                               placeholder="<?php esc_attr_e( 'Content blocked', 'mbr-cookie-consent' ); ?>">
                    </div>

                    <div style="margin-top: 16px;">
                        <label for="mbr_cc_blocked_overlay_message" style="display:block; font-weight:600; margin-bottom:4px;">
                            <?php esc_html_e( 'Message', 'mbr-cookie-consent' ); ?>
                        </label>
                        <textarea name="mbr_cc_blocked_overlay_message"
                                  id="mbr_cc_blocked_overlay_message"
                                  class="large-text"
                                  rows="3"
                                  placeholder="<?php esc_attr_e( "Some content on this page requires cookie consent that hasn't been granted yet. Update your preferences below to view it.", 'mbr-cookie-consent' ); ?>"><?php echo esc_textarea( get_option( 'mbr_cc_blocked_overlay_message', '' ) ); ?></textarea>
                    </div>

                    <div style="margin-top: 16px;">
                        <label for="mbr_cc_blocked_overlay_btn_text" style="display:block; font-weight:600; margin-bottom:4px;">
                            <?php esc_html_e( 'Button Label', 'mbr-cookie-consent' ); ?>
                        </label>
                        <input type="text"
                               name="mbr_cc_blocked_overlay_btn_text"
                               id="mbr_cc_blocked_overlay_btn_text"
                               value="<?php echo esc_attr( get_option( 'mbr_cc_blocked_overlay_btn_text', '' ) ); ?>"
                               class="regular-text"
                               placeholder="<?php esc_attr_e( 'Cookie Settings', 'mbr-cookie-consent' ); ?>">
                    </div>
                </div>
            </div>

            <!-- Branding -->
            <div class="mbr-cc-form-row" style="margin-top: 24px;">
                <div class="mbr-cc-form-field" style="width: 100%;">
                    <h3><?php esc_html_e( 'Branding', 'mbr-cookie-consent' ); ?></h3>
                    <p class="description">
                        <?php esc_html_e( 'The overlay automatically uses your WordPress site logo (set in Appearance > Customise), falling back to your site icon, then your site name as text. Use the field below only if you want to override this with a specific image.', 'mbr-cookie-consent' ); ?>
                    </p>

                    <div style="margin-top: 16px;">
                        <label for="mbr_cc_blocked_overlay_logo_url" style="display:block; font-weight:600; margin-bottom:4px;">
                            <?php esc_html_e( 'Custom Logo URL (optional override)', 'mbr-cookie-consent' ); ?>
                        </label>
                        <input type="url"
                               name="mbr_cc_blocked_overlay_logo_url"
                               id="mbr_cc_blocked_overlay_logo_url"
                               value="<?php echo esc_attr( get_option( 'mbr_cc_blocked_overlay_logo_url', '' ) ); ?>"
                               class="large-text"
                               placeholder="https://example.com/logo.png">
                        <p class="description"><?php esc_html_e( 'Recommended size: up to 150&thinsp;&times;&thinsp;150 px. Leave blank to use the automatic logo cascade described above.', 'mbr-cookie-consent' ); ?></p>
                    </div>

                    <div style="margin-top: 16px; padding: 12px 16px; background: #f0f6fc; border-left: 4px solid #0073aa;">
                        <strong><?php esc_html_e( 'Accent colour:', 'mbr-cookie-consent' ); ?></strong>
                        <?php esc_html_e( 'The overlay button automatically inherits the Primary Colour set on the Banner Settings tab — no separate colour picker needed.', 'mbr-cookie-consent' ); ?>
                    </div>
                </div>
            </div>

        </div><!-- .mbr-cc-settings-section -->
        </div><!-- #tab-content-blocking -->

    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Layout selection
    $('input[name="mbr_cc_layout_option"]').on('change', function() {
        var value = $(this).val();
        var parts = value.split('-');
        
        if (value === 'popup') {
            $('#banner_layout').val('popup');
            $('#banner_position').val('bottom');
        } else if (value.startsWith('bar-')) {
            $('#banner_layout').val('bar');
            $('#banner_position').val(parts[1]);
        } else {
            $('#banner_layout').val(value);
            $('#banner_position').val('bottom');
        }
        
        // Update visual selection
        $('.mbr-cc-layout-card').removeClass('selected');
        $(this).closest('.mbr-cc-layout-card').addClass('selected');
    });
});
</script>
