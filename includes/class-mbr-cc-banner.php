<?php
/**
 * Cookie Consent Banner display.
 *
 * @package MBR_Cookie_Consent
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Banner class.
 */
class MBR_CC_Banner {
    
    /**
     * Single instance.
     *
     * @var MBR_CC_Banner
     */
    private static $instance = null;
    
    /**
     * Get instance.
     *
     * @return MBR_CC_Banner
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
        add_action('wp_footer', array($this, 'render_banner'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }
    
    /**
     * Enqueue banner assets.
     */
    public function enqueue_assets() {
        if (is_admin()) {
            return;
        }
        
        wp_enqueue_style(
            'mbr-cc-banner',
            MBR_CC_PLUGIN_URL . 'assets/css/banner.css',
            array(),
            MBR_CC_VERSION
        );
        
        wp_enqueue_script(
            'mbr-cc-banner',
            MBR_CC_PLUGIN_URL . 'assets/js/banner.js',
            array('jquery'),
            MBR_CC_VERSION,
            true
        );
        
        // Pass settings to JavaScript.
        wp_localize_script('mbr-cc-banner', 'mbrCcBanner', array(
            'categories' => MBR_CC_Consent_Manager::get_instance()->get_categories(),
            'revisitEnabled' => (bool) get_option('mbr_cc_revisit_consent_enabled', true),
            'revisitText' => get_option('mbr_cc_revisit_consent_text', 'Cookie Settings'),
        ));
        
        // Blocked content overlay assets (only when feature is enabled).
        if ( class_exists( 'MBR_CC_Blocked_Placeholder' ) && MBR_CC_Blocked_Placeholder::is_enabled() ) {
            wp_enqueue_style(
                'mbr-cc-blocked-content',
                MBR_CC_PLUGIN_URL . 'assets/css/blocked-content.css',
                array( 'mbr-cc-banner' ),
                MBR_CC_VERSION
            );
            wp_enqueue_script(
                'mbr-cc-blocked-content',
                MBR_CC_PLUGIN_URL . 'assets/js/blocked-content.js',
                array( 'jquery', 'mbr-cc-banner' ),
                MBR_CC_VERSION,
                true
            );
        }
        
        // Elementor video blocker — runs when Elementor is active on the page.
        // Enqueued in the <head> (in_footer=false) with high priority so it
        // executes BEFORE Elementor's own frontend script initialises widgets.
        if ( defined( 'ELEMENTOR_VERSION' ) || class_exists( '\Elementor\Plugin' ) ) {
            wp_enqueue_script(
                'mbr-cc-elementor-video-blocker',
                MBR_CC_PLUGIN_URL . 'assets/js/elementor-video-blocker.js',
                array(), // No jQuery dependency — pure JS runs earlier.
                MBR_CC_VERSION,
                false    // Load in <head> so it runs before Elementor's DOMContentLoaded.
            );
        }

        // Add inline CSS for customization.
        $this->add_inline_styles();
    }
    
    /**
     * Add inline CSS for custom colors.
     */
    private function add_inline_styles() {
        $primary_color = get_option('mbr_cc_primary_color', '#0073aa');
        $accept_color = get_option('mbr_cc_accept_button_color', '#00a32a');
        $reject_color = get_option('mbr_cc_reject_button_color', '#d63638');
        $text_color = get_option('mbr_cc_text_color', '#ffffff');
        $revisit_text_color = get_option('mbr_cc_revisit_button_text_color', '#000000');
        
        $custom_css = "
            /* ── Banner ───────────────────────────────────────────── */
            .mbr-cc-banner {
                background-color: {$primary_color} !important;
                color: {$text_color} !important;
            }

            /* Accept buttons — banner and modal footer */
            .mbr-cc-banner .mbr-cc-btn-accept,
            .mbr-cc-banner .mbr-cc-btn-accept:hover,
            .mbr-cc-banner .mbr-cc-btn-accept:focus,
            .mbr-cc-modal .mbr-cc-btn-accept,
            .mbr-cc-modal .mbr-cc-btn-accept:hover,
            .mbr-cc-modal .mbr-cc-btn-accept:focus {
                background-color: {$accept_color} !important;
                background: {$accept_color} !important;
                color: #ffffff !important;
            }

            /* Reject buttons — banner and modal footer */
            .mbr-cc-banner .mbr-cc-btn-reject,
            .mbr-cc-banner .mbr-cc-btn-reject:hover,
            .mbr-cc-banner .mbr-cc-btn-reject:focus,
            .mbr-cc-modal .mbr-cc-btn-reject,
            .mbr-cc-modal .mbr-cc-btn-reject:hover,
            .mbr-cc-modal .mbr-cc-btn-reject:focus {
                background-color: {$reject_color} !important;
                background: {$reject_color} !important;
                color: #ffffff !important;
            }

            /* Customise button on banner */
            .mbr-cc-banner .mbr-cc-btn-customize {
                border-color: {$text_color} !important;
                color: {$text_color} !important;
            }

            /* Banner close X — inherits banner text colour */
            .mbr-cc-banner .mbr-cc-close {
                color: {$text_color} !important;
            }

            /* ── Revisit button ───────────────────────────────────── */
            .mbr-cc-revisit-consent {
                background-color: {$primary_color} !important;
                color: {$revisit_text_color} !important;
            }
            .mbr-cc-revisit-consent span {
                color: {$revisit_text_color} !important;
            }
            .mbr-cc-revisit-consent svg {
                stroke: {$revisit_text_color} !important;
            }
        ";
        
        wp_add_inline_style('mbr-cc-banner', $custom_css);
    }
    
    /**
     * Render the consent banner.
     */
    public function render_banner() {
        // Don't show on admin pages.
        if (is_admin()) {
            return;
        }
        
        // Check if banner should be shown on this page.
        if (!MBR_CC_Enhanced_Customization::should_show_banner()) {
            return;
        }
        
        // Get i18n instance for translations.
        $i18n = MBR_CC_I18n_Accessibility::get_instance();
        
        $position = get_option('mbr_cc_banner_position', 'bottom');
        $layout = get_option('mbr_cc_banner_layout', 'bar');
        
        $heading = $i18n::get_translated_string('banner_heading', 
            get_option('mbr_cc_banner_heading', 'We value your privacy'));
        $description = $i18n::get_translated_string('banner_description',
            get_option('mbr_cc_banner_description', 'We use cookies to enhance your browsing experience, serve personalized content, and analyze our traffic. By clicking "Accept All", you consent to our use of cookies.'));
        
        $accept_text = $i18n::get_translated_string('accept_button',
            get_option('mbr_cc_accept_button_text', 'Accept All'));
        $reject_text = $i18n::get_translated_string('reject_button',
            get_option('mbr_cc_reject_button_text', 'Reject All'));
        $customize_text = $i18n::get_translated_string('customize_button',
            get_option('mbr_cc_customize_button_text', 'Customize'));
        
        // Get base configuration from options
        $base_config = array(
            'show_reject_button' => get_option('mbr_cc_show_reject_button', true),
            'show_customize_button' => get_option('mbr_cc_show_customize_button', true),
            'enable_ccpa' => get_option('mbr_cc_enable_ccpa', false),
        );
        
        // Apply geolocation-based regional overrides
        $config = apply_filters('mbr_cc_banner_config', $base_config);
        
        // Use filtered values
        $show_reject = $config['show_reject_button'];
        $show_customize = $config['show_customize_button'];
        $enable_ccpa = $config['enable_ccpa'];
        
        $ccpa_text = $i18n::get_translated_string('ccpa_link',
            get_option('mbr_cc_ccpa_link_text', 'Do Not Sell or Share My Personal Information'));
        
        $classes = array('mbr-cc-banner', 'mbr-cc-banner--' . $position, 'mbr-cc-banner--' . $layout);
        ?>
        
        <!-- Popup Overlay (for popup layout) -->
        <?php if ($layout === 'popup') : ?>
            <div id="mbr-cc-popup-overlay" class="mbr-cc-popup-overlay" style="display: none;" aria-hidden="true"></div>
        <?php endif; ?>
        
        <!-- Cookie Consent Banner -->
        <div id="mbr-cc-banner" 
             class="<?php echo esc_attr(implode(' ', $classes)); ?>" 
             style="display: none;"
             role="dialog"
             aria-labelledby="mbr-cc-banner-heading"
             aria-describedby="mbr-cc-banner-description"
             aria-modal="true">
            <?php if (get_option('mbr_cc_show_close_button', false)) : ?>
                <button type="button" 
                        class="mbr-cc-close" 
                        id="mbr-cc-close" 
                        aria-label="<?php esc_attr_e('Close', 'mbr-cookie-consent'); ?>">×</button>
            <?php endif; ?>
            
            <div class="mbr-cc-banner__container">
                <?php if (get_option('mbr_cc_banner_logo_url')) : ?>
                    <div class="mbr-cc-banner__logo" role="img" aria-label="<?php esc_attr_e('Company logo', 'mbr-cookie-consent'); ?>">
                        <img src="<?php echo esc_url(get_option('mbr_cc_banner_logo_url')); ?>" alt="">
                    </div>
                <?php endif; ?>
                
                <div class="mbr-cc-banner__content">
                    <h3 id="mbr-cc-banner-heading" class="mbr-cc-banner__heading"><?php echo esc_html($heading); ?></h3>
                    <p id="mbr-cc-banner-description" class="mbr-cc-banner__description"><?php echo esc_html($description); ?></p>
                    
                    <?php if (get_option('mbr_cc_show_privacy_policy_link', false) || get_option('mbr_cc_show_cookie_policy_link', false)) : ?>
                        <p class="mbr-cc-banner__policy-links">
                            <?php if (get_option('mbr_cc_show_privacy_policy_link', false) && get_option('mbr_cc_privacy_policy_url')) : ?>
                                <a href="<?php echo esc_url(get_option('mbr_cc_privacy_policy_url')); ?>" target="_blank" rel="noopener noreferrer">
                                    <?php echo esc_html(get_option('mbr_cc_privacy_policy_text', 'Privacy Policy')); ?>
                                </a>
                            <?php endif; ?>
                            
                            <?php if (get_option('mbr_cc_show_privacy_policy_link', false) && get_option('mbr_cc_show_cookie_policy_link', false) && get_option('mbr_cc_privacy_policy_url') && get_option('mbr_cc_cookie_policy_url')) : ?>
                                <span class="mbr-cc-separator"> | </span>
                            <?php endif; ?>
                            
                            <?php if (get_option('mbr_cc_show_cookie_policy_link', false) && get_option('mbr_cc_cookie_policy_url')) : ?>
                                <a href="<?php echo esc_url(get_option('mbr_cc_cookie_policy_url')); ?>" target="_blank" rel="noopener noreferrer">
                                    <?php echo esc_html(get_option('mbr_cc_cookie_policy_text', 'Cookie Policy')); ?>
                                </a>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                    
                    <?php if ($enable_ccpa) : ?>
                        <p class="mbr-cc-banner__ccpa">
                            <button type="button" class="mbr-cc-ccpa-link" id="mbr-cc-ccpa-optout">
                                <?php echo esc_html($ccpa_text); ?>
                            </button>
                        </p>
                    <?php endif; ?>
                </div>
                
                <div class="mbr-cc-banner__buttons">
                    <button type="button" class="mbr-cc-btn mbr-cc-btn-accept" id="mbr-cc-accept-all">
                        <?php echo esc_html($accept_text); ?>
                    </button>
                    
                    <?php if ($show_reject) : ?>
                        <button type="button" class="mbr-cc-btn mbr-cc-btn-reject" id="mbr-cc-reject-all">
                            <?php echo esc_html($reject_text); ?>
                        </button>
                    <?php endif; ?>
                    
                    <?php if ($show_customize) : ?>
                        <button type="button" class="mbr-cc-btn mbr-cc-btn-customize" id="mbr-cc-customize">
                            <?php echo esc_html($customize_text); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Preference Center Modal -->
        <div id="mbr-cc-modal" 
             class="mbr-cc-modal" 
             style="display: none;"
             role="dialog"
             aria-labelledby="mbr-cc-modal-heading"
             aria-modal="true">
            <div class="mbr-cc-modal__overlay" aria-hidden="true"></div>
            <div class="mbr-cc-modal__content">
                <div class="mbr-cc-modal__header">
                    <h3 id="mbr-cc-modal-heading"><?php esc_html_e('Manage Cookie Preferences', 'mbr-cookie-consent'); ?></h3>
                    <button type="button" 
                            class="mbr-cc-modal__close" 
                            id="mbr-cc-modal-close"
                            aria-label="<?php esc_attr_e('Close preferences dialog', 'mbr-cookie-consent'); ?>">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                
                <div class="mbr-cc-modal__body">
                    <p><?php esc_html_e('We use cookies to enhance your experience. You can choose which types of cookies to allow.', 'mbr-cookie-consent'); ?></p>
                    
                    <div class="mbr-cc-categories" id="mbr-cc-categories" role="group" aria-label="<?php esc_attr_e('Cookie categories', 'mbr-cookie-consent'); ?>">
                        <?php $this->render_categories(); ?>
                    </div>
                </div>
                
                <div class="mbr-cc-modal__footer">
                    <button type="button" class="mbr-cc-btn mbr-cc-btn-accept" id="mbr-cc-save-preferences">
                        <?php esc_html_e('Save Preferences', 'mbr-cookie-consent'); ?>
                    </button>
                    <button type="button" class="mbr-cc-btn mbr-cc-btn-reject" id="mbr-cc-reject-all-modal">
                        <?php echo esc_html($reject_text); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Revisit Consent Button -->
        <?php if (get_option('mbr_cc_revisit_consent_enabled', true)) : ?>
            <button type="button" class="mbr-cc-revisit-consent" id="mbr-cc-revisit" style="display: none;" data-version="1.0.3">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <!-- Cookie icon - outer circle -->
                    <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="1.5"/>
                    <!-- Chocolate chips -->
                    <circle cx="8" cy="9" r="1.5" fill="currentColor"/>
                    <circle cx="16" cy="10" r="1.5" fill="currentColor"/>
                    <circle cx="12" cy="14" r="1.5" fill="currentColor"/>
                    <circle cx="7" cy="15" r="1" fill="currentColor"/>
                    <circle cx="16" cy="16" r="1" fill="currentColor"/>
                    <circle cx="10" cy="18" r="0.8" fill="currentColor"/>
                </svg>
                <span><?php echo esc_html(get_option('mbr_cc_revisit_consent_text', 'Cookie Settings')); ?></span>
            </button>
        <?php endif; ?>
        
        <?php
    }
    
    /**
     * Render cookie categories.
     */
    private function render_categories() {
        $categories = MBR_CC_Consent_Manager::get_instance()->get_categories();
        
        if (empty($categories)) {
            echo '<p>' . esc_html__('No cookie categories found.', 'mbr-cookie-consent') . '</p>';
            return;
        }
        
        foreach ($categories as $slug => $category) {
            $name = isset($category['name']) ? $category['name'] : ucfirst($slug);
            $description = isset($category['description']) ? $category['description'] : '';
            $required = isset($category['required']) && $category['required'];
            
            ?>
            <div class="mbr-cc-category">
                <div class="mbr-cc-category__header">
                    <label class="mbr-cc-category__label">
                        <input 
                            type="checkbox" 
                            name="mbr_cc_category[]" 
                            value="<?php echo esc_attr($slug); ?>"
                            class="mbr-cc-category__checkbox"
                            <?php checked($required); ?>
                            <?php disabled($required); ?>
                        >
                        <span class="mbr-cc-category__name"><?php echo esc_html($name); ?></span>
                        <?php if ($required) : ?>
                            <span class="mbr-cc-category__badge"><?php esc_html_e('Always Active', 'mbr-cookie-consent'); ?></span>
                        <?php endif; ?>
                    </label>
                </div>
                
                <?php if (!empty($description)) : ?>
                    <div class="mbr-cc-category__description">
                        <p><?php echo esc_html($description); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            <?php
        }
    }
}
