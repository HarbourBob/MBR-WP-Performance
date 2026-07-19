<?php
/**
 * Internationalization and Accessibility Handler
 *
 * @package MBR_Cookie_Consent
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * I18n and Accessibility class.
 */
class MBR_CC_I18n_Accessibility {
    
    /**
     * Single instance.
     *
     * @var MBR_CC_I18n_Accessibility
     */
    private static $instance = null;
    
    /**
     * Supported languages with their native names.
     *
     * @var array
     */
    private static $supported_languages = array(
        'en' => 'English',
        'es' => 'Español',
        'fr' => 'Français',
        'de' => 'Deutsch',
        'it' => 'Italiano',
        'pt' => 'Português',
        'nl' => 'Nederlands',
        'pl' => 'Polski',
        'ru' => 'Русский',
        'ja' => '日本語',
        'zh' => '中文',
        'ko' => '한국어',
        'ar' => 'العربية',
        'tr' => 'Türkçe',
        'sv' => 'Svenska',
        'da' => 'Dansk',
        'fi' => 'Suomi',
        'no' => 'Norsk',
        'cs' => 'Čeština',
        'hu' => 'Magyar',
        'ro' => 'Română',
        'el' => 'Ελληνικά',
        'bg' => 'Български',
        'uk' => 'Українська',
        'hr' => 'Hrvatski',
        'sk' => 'Slovenčina',
        'sl' => 'Slovenščina',
        'et' => 'Eesti',
        'lv' => 'Latviešu',
        'lt' => 'Lietuvių',
        'th' => 'ไทย',
        'vi' => 'Tiếng Việt',
        'id' => 'Bahasa Indonesia',
        'ms' => 'Bahasa Melayu',
        'he' => 'עברית',
        'hi' => 'हिन्दी',
        'bn' => 'বাংলা',
        'fa' => 'فارسی',
        'ca' => 'Català',
        'sr' => 'Српски',
        'af' => 'Afrikaans',
        'sq' => 'Shqip',
        'is' => 'Íslenska',
        'ga' => 'Gaeilge',
    );
    
    /**
     * Get instance.
     *
     * @return MBR_CC_I18n_Accessibility
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
        // Auto-translation hooks.
        add_filter('mbr_cc_banner_text', array($this, 'translate_banner_text'), 10, 2);
        
        // WPML/Polylang compatibility.
        add_action('init', array($this, 'register_multilingual_strings'));
        
        // Accessibility enhancements.
        add_action('wp_footer', array($this, 'add_accessibility_announcements'), 5);
    }
    
    /**
     * Detect user's language from browser.
     *
     * @return string Language code.
     */
    public function detect_browser_language() {
        $auto_translate = get_option('mbr_cc_auto_translate', false);
        
        if (!$auto_translate) {
            return 'en';
        }
        
        // Check for WPML language.
        if (function_exists('icl_get_current_language')) {
            return icl_get_current_language();
        }
        
        // Check for Polylang language.
        if (function_exists('pll_current_language')) {
            return pll_current_language();
        }
        
        // Detect from browser.
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $browser_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
            if (array_key_exists($browser_lang, self::$supported_languages)) {
                return $browser_lang;
            }
        }
        
        return 'en';
    }
    
    /**
     * Translate banner text.
     *
     * @param string $text Text to translate.
     * @param string $context Context (heading, description, button, etc.).
     * @return string Translated text.
     */
    public function translate_banner_text($text, $context = '') {
        $lang = $this->detect_browser_language();
        
        if ($lang === 'en') {
            return $text;
        }
        
        // Get translations.
        $translations = $this->get_translations($lang);
        
        // If WPML/Polylang is active, they handle translation.
        if (function_exists('icl_t') || function_exists('pll__')) {
            return $text; // Let WPML/Polylang handle it
        }
        
        // Auto-translate based on context.
        if (isset($translations[$context])) {
            return $translations[$context];
        }
        
        return $text;
    }
    
    /**
     * Get translations for a language.
     *
     * @param string $lang Language code.
     * @return array Translations.
     */
    private function get_translations($lang) {
        $translations = include MBR_CC_PLUGIN_DIR . 'languages/translations.php';
        return isset($translations[$lang]) ? $translations[$lang] : array();
    }
    
    /**
     * Register strings with WPML/Polylang.
     */
    public function register_multilingual_strings() {
        // Only register if WPML or Polylang is active.
        if (!function_exists('icl_register_string') && !function_exists('pll_register_string')) {
            return;
        }
        
        $strings = array(
            'banner_heading' => get_option('mbr_cc_banner_heading', 'We value your privacy'),
            'banner_description' => get_option('mbr_cc_banner_description', ''),
            'accept_button' => get_option('mbr_cc_accept_button_text', 'Accept All'),
            'reject_button' => get_option('mbr_cc_reject_button_text', 'Reject All'),
            'customize_button' => get_option('mbr_cc_customize_button_text', 'Customize'),
            'revisit_button' => get_option('mbr_cc_revisit_consent_text', 'Cookie Settings'),
            'ccpa_link' => get_option('mbr_cc_ccpa_link_text', 'Do Not Sell or Share My Personal Information'),
            'privacy_policy_text' => get_option('mbr_cc_privacy_policy_text', 'Privacy Policy'),
            'cookie_policy_text' => get_option('mbr_cc_cookie_policy_text', 'Cookie Policy'),
        );
        
        // Register with WPML.
        if (function_exists('icl_register_string')) {
            foreach ($strings as $name => $value) {
                icl_register_string('mbr-cookie-consent', $name, $value);
            }
        }
        
        // Register with Polylang.
        if (function_exists('pll_register_string')) {
            foreach ($strings as $name => $value) {
                pll_register_string($name, $value, 'MBR Cookie Consent');
            }
        }
        
        // Register category strings.
        $categories = get_option('mbr_cc_cookie_categories', array());
        foreach ($categories as $slug => $category) {
            $cat_name = "category_{$slug}_name";
            $cat_desc = "category_{$slug}_description";
            
            if (function_exists('icl_register_string')) {
                icl_register_string('mbr-cookie-consent', $cat_name, $category['name']);
                icl_register_string('mbr-cookie-consent', $cat_desc, $category['description']);
            }
            
            if (function_exists('pll_register_string')) {
                pll_register_string($cat_name, $category['name'], 'MBR Cookie Consent');
                pll_register_string($cat_desc, $category['description'], 'MBR Cookie Consent');
            }
        }
    }
    
    /**
     * Get translated string (WPML/Polylang compatible).
     *
     * @param string $name String name.
     * @param string $original Original text.
     * @return string Translated text.
     */
    public static function get_translated_string($name, $original) {
        // WPML.
        if (function_exists('icl_t')) {
            return icl_t('mbr-cookie-consent', $name, $original);
        }
        
        // Polylang.
        if (function_exists('pll__')) {
            return pll__($original);
        }
        
        return $original;
    }
    
    /**
     * Add ARIA live region for screen reader announcements.
     */
    public function add_accessibility_announcements() {
        if (!get_option('mbr_cc_wcag_compliance', true)) {
            return;
        }
        
        ?>
        <!-- MBR Cookie Consent - Screen Reader Announcements -->
        <div id="mbr-cc-sr-announce" class="mbr-cc-sr-only" aria-live="polite" aria-atomic="true"></div>
        <?php
    }
    
    /**
     * Get supported languages.
     *
     * @return array Languages.
     */
    public static function get_supported_languages() {
        return self::$supported_languages;
    }
    
    /**
     * Check if multilingual plugin is active.
     *
     * @return string|false Plugin name or false.
     */
    public static function get_active_multilingual_plugin() {
        if (function_exists('icl_get_current_language')) {
            return 'WPML';
        }
        
        if (function_exists('pll_current_language')) {
            return 'Polylang';
        }
        
        return false;
    }
}
