<?php
/**
 * Region-Specific Banner Configuration
 * Adjusts banner behavior based on detected region
 *
 * @package MBR_Cookie_Consent
 * @version 2.1.2
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

class MBR_CC_Region_Config {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Geolocation instance
     */
    private $geo;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->geo = mbr_cc_geolocation();
        
        // Filter banner configuration based on region
        add_filter('mbr_cc_banner_config', array($this, 'apply_region_config'));
    }
    
    /**
     * Apply region-specific configuration
     */
    public function apply_region_config($config) {
        
        // Check constant first, then database option
        $geo_enabled = defined('MBR_CC_FORCE_GEOLOCATION') && MBR_CC_FORCE_GEOLOCATION;
        
        if (!$geo_enabled) {
            $geo_enabled = get_option('mbr_cc_geolocation_enabled', false);
        }
        
        if (!$geo_enabled) {
            return $config;
        }
        
        $region = $this->geo->get_region();
        
        // Map legacy region keys to new method names.
        $legacy_map = array(
            'eu_uk' => 'eu_gdpr',
            'ccpa'  => 'us_multi',
        );
        if (isset($legacy_map[$region])) {
            $region = $legacy_map[$region];
        }
        
        // Get region-specific overrides
        $method = "get_{$region}_config";
        if (method_exists($this, $method)) {
            $region_config = $this->$method();
            $config = array_merge($config, $region_config);
        }
        
        
        return $config;
    }
    
    /**
     * EU/EEA (GDPR / ePrivacy Directive) Configuration
     *
     * Applies to all 27 EU Member States plus the three EEA non-EU members
     * (Iceland, Liechtenstein, Norway), which apply GDPR via the EEA Agreement.
     *
     * Strict opt-in — no change from the original ePrivacy rules. The EU's
     * proposed ePrivacy Regulation that would have replaced the Directive was
     * formally withdrawn by the European Commission on 11 February 2026, so the
     * 2002/58/EC Directive (as amended) remains the controlling instrument.
     *
     * Digital Omnibus status (July 2026): the package split in two. The AI
     * Omnibus was adopted by the Council on 29 June 2026; the Data Omnibus
     * (GDPR/ePrivacy) remains in negotiation, and the Council's compromise
     * text of 21 May 2026 (doc 9547/26) DELETED the relocation of cookie
     * consent into GDPR Arts 88a/88b. Final adoption is not expected before
     * late 2026 at the earliest — the Directive regime stays operative.
     */
    private function get_eu_gdpr_config() {
        return array(
            // GDPR + ePrivacy requires explicit consent for all non-essential cookies
            'require_consent' => true,
            
            // Reject button must be equally prominent
            'show_reject_button' => true,
            'reject_button_prominence' => 'equal',
            
            // Show customize/preferences button
            'show_customize_button' => true,
            
            // Don't auto-accept on scroll/click
            'auto_accept_on_scroll' => false,
            'auto_accept_on_click' => false,
            
            // Show all cookie categories
            'show_categories' => true,
            
            // EU-specific text (falls back to legacy eu_uk option keys)
            'banner_heading' => get_option('mbr_cc_geolocation_eu_heading', get_option('mbr_cc_geolocation_eu_uk_heading', 'We value your privacy')),
            'banner_description' => get_option('mbr_cc_geolocation_eu_description', get_option('mbr_cc_geolocation_eu_uk_description',
                'We use cookies to enhance your experience. By clicking "Accept", you consent to our use of cookies. You can manage your preferences or reject non-essential cookies.'
            )),
            
            // No CCPA link for EU
            'enable_ccpa' => false,
        );
    }
    
    /**
     * UK (UK GDPR + Data Use and Access Act 2025) Configuration
     *
     * The DUAA received Royal Assent 19 June 2025 and PECR cookie amendments
     * came into force 5 February 2026. The ICO finalised its updated "Storage
     * and Access Technologies" guidance (the successor to the cookie guidance)
     * on 29 April 2026 following two consultations.
     *
     * Five categories of cookie/storage and access technology are now exempt
     * from the consent requirement under PECR Regulation 6:
     *   1. Communications transmission (technical strict-necessity)
     *   2. Information society service requested by the user (e.g. essential
     *      session, basket, login, security)
     *   3. Statistical / sole-purpose analytics (no cross-site tracking, no
     *      profiling, anonymised aggregate output)
     *   4. Service appearance / functionality (e.g. preferences, language)
     *   5. Automatic software updates / emergency assistance
     *
     * Advertising/marketing cookies STILL require explicit, opt-in consent.
     * Transparency and a "simple means of objecting" (ICO's term) are STILL
     * required for the exempt categories. PECR fines now match UK GDPR levels:
     * up to £17.5M or 4% of global annual turnover.
     *
     * The banner is still shown for transparency and to collect advertising
     * consent. Analytics, preferences, and security toggles default ON because
     * they fall within DUAA-exempt categories — a clear opt-out remains.
     */
    private function get_uk_duaa_config() {
        return array(
            // Advertising still requires explicit consent
            'require_consent' => true,
            
            // Reject button equally prominent (for advertising consent)
            'show_reject_button' => true,
            'reject_button_prominence' => 'equal',
            
            // Show customize button so users can opt out of exempt categories
            'show_customize_button' => true,
            
            // No auto-accept
            'auto_accept_on_scroll' => false,
            'auto_accept_on_click' => false,
            
            // Show categories — exempt categories default ON with easy opt-out
            'show_categories' => true,
            
            // DUAA-exempt categories per the ICO 29 April 2026 finalised guidance.
            // 'necessary' is always essential; the entries here are the
            // additional categories that no longer require prior consent under
            // the new PECR exceptions, provided they are used solely for the
            // exempt purpose (purpose limitation is mandatory).
            'duaa_exempt_categories' => array('analytics', 'preferences'),
            'duaa_consent_required_categories' => array('marketing'),
            
            // UK-specific text
            'banner_heading' => get_option('mbr_cc_geolocation_uk_heading', 'Your privacy choices'),
            'banner_description' => get_option('mbr_cc_geolocation_uk_description',
                'We use cookies and similar technologies to improve your experience. Analytics and preference cookies fall under PECR exemptions introduced by the Data (Use and Access) Act 2025, with a simple means of objecting. Advertising cookies require your consent. You can manage or withdraw your choices at any time.'
            ),
            
            'enable_ccpa' => false,
        );
    }
    
    /**
     * US Multi-State Configuration
     *
     * As of July 2026, 20 US states have comprehensive privacy laws in
     * effect — Indiana, Kentucky, and Rhode Island took effect on
     * 1 January 2026, joining Maryland (effective 1 October 2025) and 16
     * earlier laws. The 2026 session added four more enacted-but-not-yet-
     * effective laws (24 enacted total): Oklahoma SB 546 and the Louisiana
     * Data Privacy Act (both effective 1 Jan 2027), the Alabama Personal
     * Data Protection Act (2027 — effective date reported variously as
     * 1 Jan or 1 May), and the Vermont Data Privacy and Online Surveillance
     * Act (1 Jan 2028). All four follow the Virginia opt-out model, so no
     * banner behaviour change is required — opt-out link + GPC covers them.
     *
     * Key requirements across the landscape:
     *
     * - "Do Not Sell or Share My Personal Information" link (CCPA/CPRA).
     * - Universal opt-out / Global Privacy Control (GPC) signal must be
     *   honoured in California, Colorado, Connecticut, Delaware, Maryland,
     *   Minnesota, Montana, Nebraska, New Hampshire, New Jersey, Oregon,
     *   and Texas (as of 2026), with more states added each year.
     * - Opt-out based model (not opt-in) for most states.
     * - California (CCPA regs effective 1 January 2026) requires:
     *     - Visible confirmation when an opt-out request — including a GPC
     *       signal — is processed.
     *     - "Sensitive personal information" now includes neural data and
     *       data of consumers under 16 (with actual knowledge).
     *     - Updated dark-pattern examples explicitly prohibit creating a
     *       false sense of urgency in consent UX.
     * - Sensitive data processing requires opt-in consent in 16+ states.
     *
     * GPC signal handling is managed by class-mbr-cc-gpc-handler.php.
     */
    private function get_us_multi_config() {
        return array(
            // US is opt-out based
            'require_consent' => false,
            
            // Can use implied consent
            'auto_accept_on_scroll' => get_option('mbr_cc_ccpa_auto_accept', false),
            
            // Show "Do Not Sell or Share" link prominently (CCPA/CPRA mandate)
            'enable_ccpa' => true,
            'ccpa_link_text' => get_option('mbr_cc_ccpa_link_text', 'Do Not Sell or Share My Personal Information'),
            
            // Reject button not typically needed — "Do Not Sell" covers opt-out
            'show_reject_button' => false,
            
            // Show customize for granular control
            'show_customize_button' => true,
            
            // GPC support flag — the GPC handler reads this
            'gpc_enabled' => true,
            
            // US-specific text (falls back to legacy ccpa option keys)
            'banner_heading' => get_option('mbr_cc_geolocation_us_heading', get_option('mbr_cc_geolocation_ccpa_heading', 'Your Privacy Rights')),
            'banner_description' => get_option('mbr_cc_geolocation_us_description', get_option('mbr_cc_geolocation_ccpa_description',
                'We use cookies and similar technologies. You can opt out of the sale or sharing of your personal information by clicking "Do Not Sell or Share My Personal Information". We honour Global Privacy Control (GPC) signals automatically.'
            )),
        );
    }
    
    /**
     * LGPD (Brazil) Configuration
     */
    private function get_lgpd_config() {
        return array(
            // LGPD similar to GDPR
            'require_consent' => true,
            
            // Equal reject button
            'show_reject_button' => true,
            'reject_button_prominence' => 'equal',
            
            // Show customize button
            'show_customize_button' => true,
            
            // No auto-accept
            'auto_accept_on_scroll' => false,
            'auto_accept_on_click' => false,
            
            // LGPD-specific text
            'banner_heading' => get_option('mbr_cc_geolocation_lgpd_heading', 'Nós valorizamos sua privacidade'),
            'banner_description' => get_option('mbr_cc_geolocation_lgpd_description',
                'Usamos cookies para melhorar sua experiência. Ao clicar em "Aceitar", você concorda com o uso de cookies.'
            ),
            
            'enable_ccpa' => false,
        );
    }
    
    /**
     * PIPEDA (Canada) Configuration
     */
    private function get_pipeda_config() {
        return array(
            // PIPEDA requires meaningful consent
            'require_consent' => true,
            
            // Show reject button
            'show_reject_button' => true,
            
            // Show customize button
            'show_customize_button' => true,
            
            // Canada-specific text
            'banner_heading' => get_option('mbr_cc_geolocation_pipeda_heading', 'Your Privacy Matters'),
            'banner_description' => get_option('mbr_cc_geolocation_pipeda_description',
                'We use cookies to enhance your browsing experience. You can accept, reject, or customize your cookie preferences.'
            ),
            
            'enable_ccpa' => false,
        );
    }
    
    /**
     * India (DPDP Act 2023 + DPDP Rules 2025) Configuration
     *
     * The Digital Personal Data Protection Act 2023 received presidential
     * assent on 11 August 2023. The implementing DPDP Rules 2025 were
     * notified by MeitY on 13 November 2025 and gazetted on 14 November 2025,
     * making the framework operational on a phased basis:
     *
     * - 13 November 2025: Data Protection Board established; administrative
     *   provisions in force.
     * - 13 November 2026: Consent Manager registration opens (India-
     *   incorporated entities only).
     * - 13 May 2027: Full compliance mandatory for all Data Fiduciaries.
     *
     * Substantive requirements:
     * - Standalone privacy notice in clear, plain language.
     * - Granular consent with one-click withdrawal.
     * - Verifiable parental consent for minors.
     * - 72-hour personal data breach notification to the Board and to
     *   affected Data Principals.
     * - Automated deletion with proof, where applicable.
     *
     * The plugin provides the consent interface; registration as a Consent
     * Manager is the site owner's responsibility and requires an
     * India-incorporated entity per the Rules.
     */
    private function get_india_dpdp_config() {
        return array(
            // DPDP requires explicit consent
            'require_consent' => true,
            
            // Reject/withdraw must be as easy as giving consent
            'show_reject_button' => true,
            'reject_button_prominence' => 'equal',
            
            // Show granular categories
            'show_customize_button' => true,
            'show_categories' => true,
            
            // No auto-accept
            'auto_accept_on_scroll' => false,
            'auto_accept_on_click' => false,
            
            // India-specific text (English default; Hindi/regional can be set in admin)
            'banner_heading' => get_option('mbr_cc_geolocation_india_heading', 'Your Privacy Matters'),
            'banner_description' => get_option('mbr_cc_geolocation_india_description',
                'We use cookies and process personal data to improve your experience. Under India\'s Digital Personal Data Protection Act and the DPDP Rules 2025, we need your consent before processing non-essential data. You can withdraw consent at any time.'
            ),
            
            'enable_ccpa' => false,
        );
    }
    
    /**
     * Quebec (Law 25 — formerly Bill 64) Configuration
     *
     * Loi 25 modernised Quebec's Act respecting the protection of personal
     * information in the private sector. Phased provisions came into force
     * between September 2022 and September 2024. The Commission d'accès à
     * l'information du Québec (CAI) has confirmed that express, opt-in
     * consent is required for non-essential cookies — implied consent is not
     * acceptable. Penalties reach the higher of CA$25 million or 4% of
     * worldwide turnover.
     *
     * Key requirements:
     * - Express, opt-in consent for non-essential cookies and trackers.
     * - Banner and privacy information must be available in French (and any
     *   other language the site uses).
     * - Detailed records of consent must be retained.
     * - Consent withdrawal must be at least as easy as giving consent.
     * - Heightened protections for minors.
     */
    private function get_ca_quebec_config() {
        return array(
            // Express opt-in required (Law 25, like GDPR)
            'require_consent' => true,
            
            // Equal-prominence reject button
            'show_reject_button' => true,
            'reject_button_prominence' => 'equal',
            
            // Show customize for granular control
            'show_customize_button' => true,
            
            // No auto-accept under Law 25
            'auto_accept_on_scroll' => false,
            'auto_accept_on_click' => false,
            
            // Show all cookie categories
            'show_categories' => true,
            
            // French-default messaging — site owners can override.
            'banner_heading' => get_option('mbr_cc_geolocation_quebec_heading', 'Vos choix de confidentialité'),
            'banner_description' => get_option('mbr_cc_geolocation_quebec_description',
                'Nous utilisons des témoins (cookies) et technologies similaires. Conformément à la Loi 25, votre consentement est requis avant l\'activation des témoins non essentiels. Vous pouvez accepter, refuser ou personnaliser vos choix, et les retirer à tout moment.'
            ),
            
            'enable_ccpa' => false,
        );
    }
    
    /**
     * Vietnam (Personal Data Protection Law — PDPL) Configuration
     *
     * Law No. 91/2025/QH15 was passed by the National Assembly on 26 June 2025
     * and entered into force on 1 January 2026, elevating the earlier Decree
     * 13/2023/ND-CP (PDPD) to full statutory law. Decree 356/2025/ND-CP
     * (promulgated 31 December 2025) is the implementing decree.
     *
     * The PDPL is consent-centric and broadly GDPR-like, with some stricter
     * local features:
     *   - Consent must be voluntary, specific, fully informed and expressed in
     *     text or a verifiable electronic format.
     *   - Silence or non-response does NOT constitute consent.
     *   - Consent must be granular — obtained separately for each distinct
     *     processing purpose; bundled consent is prohibited.
     *   - Consent must be easily withdrawable at any time.
     *   - Refusing consent for non-essential processing must not deny basic
     *     services.
     *   - Heightened protection for children (representative + child consent
     *     for those over 7 where sensitive data is involved).
     *   - Applies extraterritorially to any organisation processing the data
     *     of Vietnamese residents, regardless of where it is based.
     *
     * A five-year grace period applies to some obligations (DPIA/TIA, DPO) for
     * small businesses and start-ups, but the core consent requirements for
     * non-essential cookies apply from day one. The plugin therefore serves an
     * opt-in banner to visitors detected in Vietnam.
     */
    private function get_vn_pdpl_config() {
        return array(
            // PDPL requires explicit, prior, opt-in consent for non-essential cookies
            'require_consent' => true,
            
            // Reject/withdraw must be at least as easy as giving consent
            'show_reject_button' => true,
            'reject_button_prominence' => 'equal',
            
            // Granular, per-purpose consent — show categories
            'show_customize_button' => true,
            'show_categories' => true,
            
            // Silence is not consent — never auto-accept
            'auto_accept_on_scroll' => false,
            'auto_accept_on_click' => false,
            
            // Vietnam-specific text. Vietnamese heading is a safe default; the
            // auto-translate layer / admin can localise the description.
            'banner_heading' => get_option('mbr_cc_geolocation_vietnam_heading', 'Chúng tôi tôn trọng quyền riêng tư của bạn'),
            'banner_description' => get_option('mbr_cc_geolocation_vietnam_description',
                'We use cookies and similar technologies. Under Vietnam\'s Personal Data Protection Law (Law 91/2025/QH15), we ask for your voluntary, specific consent before processing personal data through non-essential cookies. You can give or withdraw consent for each purpose at any time.'
            ),
            
            'enable_ccpa' => false,
        );
    }
    
    /**
     * Indonesia (UU PDP) Configuration
     *
     * Law No. 27 of 2022 on Personal Data Protection (UU PDP) has been fully
     * effective since 17 October 2024, after the two-year transition period.
     * In January 2026 the Constitutional Court upheld the law, dismissing a
     * challenge to its cross-border transfer provisions — it is settled law.
     *
     * GDPR-style and consent-centric: consent must be explicit, informed,
     * given for specific purposes, and withdrawable. Applies to any entity
     * processing Indonesian residents' data, including from abroad, so
     * visitors detected in Indonesia get an opt-in banner. Implementing
     * regulations and the independent supervisory authority are still being
     * stood up, so enforcement detail continues to develop.
     */
    private function get_id_pdp_config() {
        return array(
            // UU PDP requires explicit, prior, opt-in consent for
            // non-essential processing
            'require_consent' => true,
            
            // Withdrawal must be available; keep reject equally prominent
            'show_reject_button' => true,
            'reject_button_prominence' => 'equal',
            
            // Purpose-specific consent — show categories
            'show_customize_button' => true,
            'show_categories' => true,
            
            // Explicit consent only — never auto-accept
            'auto_accept_on_scroll' => false,
            'auto_accept_on_click' => false,
            
            // Indonesia-specific text. Indonesian heading is a safe default;
            // the auto-translate layer / admin can localise the description.
            'banner_heading' => get_option('mbr_cc_geolocation_indonesia_heading', 'Kami menghormati privasi Anda'),
            'banner_description' => get_option('mbr_cc_geolocation_indonesia_description',
                'We use cookies and similar technologies. Under Indonesia\'s Personal Data Protection Law (Law No. 27 of 2022), we ask for your explicit consent before processing personal data through non-essential cookies, for each specific purpose. You can withdraw your consent at any time.'
            ),
            
            'enable_ccpa' => false,
        );
    }
    
    /**
     * Switzerland (revFADP / nFADP) Configuration
     *
     * The revised Federal Act on Data Protection (revFADP, also known as the
     * new FADP / nFADP) entered into force on 1 September 2023. It is broadly
     * GDPR-equivalent in substance — Switzerland is recognised as providing
     * an adequate level of protection by the European Commission — though a
     * few aspects differ (e.g. no equivalent of the Article 6 lawful-basis
     * list; consent is one of several possible justifications).
     *
     * Cookies that process personal data trigger transparency and consent
     * obligations comparable to those under the EU regime; the Federal Data
     * Protection and Information Commissioner (FDPIC) has aligned its
     * guidance with EU practice.
     */
    private function get_ch_nfadp_config() {
        return array(
            // GDPR-equivalent — explicit consent for non-essential
            'require_consent' => true,
            
            // Reject button must be equally prominent
            'show_reject_button' => true,
            'reject_button_prominence' => 'equal',
            
            // Show customize/preferences button
            'show_customize_button' => true,
            
            // No auto-accept
            'auto_accept_on_scroll' => false,
            'auto_accept_on_click' => false,
            
            // Show all cookie categories
            'show_categories' => true,
            
            // Switzerland is multilingual (DE/FR/IT/RM); English is a safe default
            // and the auto-translate layer can localise based on Accept-Language.
            'banner_heading' => get_option('mbr_cc_geolocation_switzerland_heading', 'Your privacy choices'),
            'banner_description' => get_option('mbr_cc_geolocation_switzerland_description',
                'We use cookies and similar technologies. Under Switzerland\'s revised Federal Act on Data Protection (revFADP), we ask for your consent before processing personal data through non-essential cookies. You can manage your choices at any time.'
            ),
            
            'enable_ccpa' => false,
        );
    }
    
    /**
     * Australia (Privacy Act 1988, as amended) Configuration
     *
     * The Privacy and Other Legislation Amendment Act 2024 received Royal
     * Assent on 10 December 2024, with the bulk of provisions effective the
     * same day. Outstanding items include automated-decision-making
     * transparency obligations and the Children's Online Privacy Code, both
     * scheduled for 10 December 2026. A second tranche of reforms drawing on
     * the 2023 Privacy Act Review Report is expected during 2026.
     *
     * The Privacy Act doesn't mandate cookie banners explicitly, but where
     * cookies collect personal information (which most analytics, advertising,
     * and identifier-based cookies do under the OAIC's broad definition) the
     * Australian Privacy Principles require:
     *   - Notification at the point of collection (APP 5).
     *   - Reasonably necessary purpose limitation (APP 3).
     *   - A means for individuals to exercise choice where practicable.
     *   - Heightened protection for sensitive information (opt-in consent).
     *
     * The plugin presents an informed-consent banner with a clear opt-out
     * path, which satisfies APP transparency expectations and provides a
     * defensible basis under the OAIC's published guidance.
     */
    private function get_au_privacy_config() {
        return array(
            // APP-based — informed consent expected for personal-information cookies
            'require_consent' => true,
            
            // Reject available with clear prominence
            'show_reject_button' => true,
            
            // Show customize button for granular control
            'show_customize_button' => true,
            
            // No auto-accept
            'auto_accept_on_scroll' => false,
            'auto_accept_on_click' => false,
            
            // Show all cookie categories
            'show_categories' => true,
            
            'banner_heading' => get_option('mbr_cc_geolocation_australia_heading', 'Your privacy choices'),
            'banner_description' => get_option('mbr_cc_geolocation_australia_description',
                'We use cookies and similar technologies. Under the Australian Privacy Act and the Australian Privacy Principles, we let you know what we collect and give you control. You can accept, reject or customise your choices.'
            ),
            
            'enable_ccpa' => false,
        );
    }
    
    /**
     * Default (Rest of World) Configuration
     */
    private function get_default_config() {
        return array(
            // Lenient for other regions
            'require_consent' => get_option('mbr_cc_geolocation_default_require', false),
            
            // Can use implied consent
            'auto_accept_on_scroll' => get_option('mbr_cc_default_auto_accept', true),
            
            // Simpler banner
            'show_reject_button' => get_option('mbr_cc_default_show_reject', false),
            'show_customize_button' => get_option('mbr_cc_default_show_customize', true),
            
            // Default text
            'banner_heading' => get_option('mbr_cc_banner_heading', 'We use cookies'),
            'banner_description' => get_option('mbr_cc_banner_description',
                'We use cookies to enhance your browsing experience. By continuing to use this site, you accept our use of cookies.'
            ),
            
            'enable_ccpa' => false,
        );
    }
    
    /**
     * Get current region configuration
     */
    public function get_current_config() {
        $config = array();
        $region = $this->geo->get_region();
        
        // Map legacy keys
        $legacy_map = array(
            'eu_uk' => 'eu_gdpr',
            'ccpa'  => 'us_multi',
        );
        if (isset($legacy_map[$region])) {
            $region = $legacy_map[$region];
        }
        
        $method = "get_{$region}_config";
        if (method_exists($this, $method)) {
            $config = $this->$method();
        } else {
            $config = $this->get_default_config();
        }
        
        return $config;
    }
    
    /**
     * Get region compliance info
     */
    public function get_compliance_info($region = null) {
        if ($region === null) {
            $region = $this->geo->get_region();
        }
        
        $info = array(
            'eu_gdpr' => array(
                'name' => 'EU/EEA GDPR / ePrivacy',
                'law' => 'General Data Protection Regulation + ePrivacy Directive 2002/58/EC',
                'requires_consent' => true,
                'key_requirements' => array(
                    'Explicit opt-in consent required for all non-essential cookies',
                    'Reject button must be equally prominent as accept',
                    'Pre-ticked boxes not allowed',
                    'Cookie categories must be shown',
                    'Withdrawal of consent must be as easy as giving it',
                    'Applies in all 27 EU Member States plus Iceland, Liechtenstein and Norway (EEA)',
                    'Proposed ePrivacy Regulation withdrawn 11 February 2026 — Directive remains in force',
                    'Digital Omnibus: Council compromise text (21 May 2026) dropped the move of cookie rules into GDPR Arts 88a/88b — outcome uncertain, adoption not before late 2026',
                    'Single-click refusal, 6-month cooldown and low-risk exemptions remain proposals only — no action required',
                    'Browser-level consent signals not expected to be mandatory before ~2028 — monitor, no action required yet',
                ),
                'penalties' => 'Up to €20 million or 4% of annual global turnover',
            ),
            'uk_duaa' => array(
                'name' => 'UK GDPR + DUAA 2025',
                'law' => 'UK General Data Protection Regulation + Data Use and Access Act 2025',
                'requires_consent' => true,
                'key_requirements' => array(
                    'PECR amendments effective 5 February 2026',
                    'ICO Storage and Access Technologies guidance finalised 29 April 2026',
                    'Five exempt categories: communications transmission, requested service, statistical analytics, appearance/functionality, software updates/emergency assistance',
                    'Advertising/marketing cookies still require explicit consent',
                    'Clear information and a "simple means of objecting" required for exempt categories',
                    'PECR fines now match UK GDPR: up to £17.5M or 4% of turnover',
                    'Formal complaints procedure in force since 19 June 2026',
                ),
                'penalties' => 'Up to £17.5 million or 4% of annual global turnover',
            ),
            'us_multi' => array(
                'name' => 'US Multi-State (CCPA + 20 in effect, 24 enacted)',
                'law' => 'California Consumer Privacy Act/CPRA + 23 additional state privacy laws',
                'requires_consent' => false,
                'key_requirements' => array(
                    'Must provide "Do Not Sell or Share My Personal Information" link (CCPA)',
                    'Opt-out based model — not opt-in',
                    'Universal opt-out / GPC must be honoured in CA, CO, CT, DE, MD, MN, MT, NE, NH, NJ, OR, TX (and growing)',
                    'California (effective 1 Jan 2026): visible confirmation when GPC opt-out is processed',
                    'California: sensitive personal information now includes neural data and data of under-16s',
                    'California: dark patterns including false-urgency consent UX explicitly prohibited',
                    'California ADMT opt-out rights (automated decision-making) effective 1 January 2027',
                    'Sensitive data requires opt-in consent in 16+ states',
                    'Indiana, Kentucky and Rhode Island laws took effect 1 January 2026',
                    'Maryland MODPA effective 1 October 2025 with strict data-minimisation rules',
                    '2026 session: Oklahoma SB 546 and Louisiana DPA effective 1 Jan 2027, Alabama PDPA 2027, Vermont DPOSA 1 Jan 2028 — all Virginia-model opt-out (24 enacted total)',
                    'Virginia amendment restricting sale of precise geolocation data effective 1 July 2026',
                    'Connecticut amendments (enhanced sensitive-data + youth protections) effective 1 July 2026',
                    'Utah correction right + social-media portability effective July 2026',
                    'California Delete Act DROP platform: brokers must process deletions from 1 August 2026',
                ),
                'penalties' => 'Up to $7,988 per intentional violation (CA); varies by state',
            ),
            'ca_quebec' => array(
                'name' => 'Quebec Law 25',
                'law' => 'Loi 25 — An Act to modernize legislative provisions as regards the protection of personal information',
                'requires_consent' => true,
                'key_requirements' => array(
                    'Express, opt-in consent required for non-essential cookies (implied consent rejected by CAI)',
                    'Banner and privacy information must be available in French',
                    'Detailed consent records must be maintained',
                    'Withdrawal must be at least as easy as giving consent',
                    'Heightened protections for minors',
                    'Designated privacy officer required',
                ),
                'penalties' => 'Up to CA$25 million or 4% of worldwide turnover, whichever is higher',
            ),
            'pipeda' => array(
                'name' => 'Canada PIPEDA / CASL',
                'law' => 'Personal Information Protection and Electronic Documents Act + Canadian Anti-Spam Law',
                'requires_consent' => true,
                'key_requirements' => array(
                    'Meaningful consent required',
                    'Must identify purpose before collection',
                    'Implied consent allowed in some low-risk cases',
                    'CASL classifies cookies as "computer programs" requiring consent',
                    'Quebec has separate Law 25 regime — handled as a distinct region',
                    'Bill C-27 (CPPA) may introduce stricter rules — monitor progress',
                ),
                'penalties' => 'Up to $10 million CAD (PIPEDA); $10M per violation (CASL)',
            ),
            'ch_nfadp' => array(
                'name' => 'Switzerland revFADP / nFADP',
                'law' => 'Federal Act on Data Protection (revised), in force 1 September 2023',
                'requires_consent' => true,
                'key_requirements' => array(
                    'GDPR-equivalent consent expectations for non-essential cookies',
                    'Transparent notice at point of collection',
                    'Consent must be free, informed and unambiguous where required',
                    'Recognised as providing adequate protection by the EU Commission',
                    'Applies to organisations targeting Swiss residents regardless of where they are based',
                ),
                'penalties' => 'Personal fines up to CHF 250,000 against responsible individuals',
            ),
            'au_privacy' => array(
                'name' => 'Australia Privacy Act 1988 (as amended)',
                'law' => 'Privacy Act 1988 + Privacy and Other Legislation Amendment Act 2024',
                'requires_consent' => true,
                'key_requirements' => array(
                    'Australian Privacy Principles (APPs) apply where cookies collect personal information',
                    'APP 5 notification at point of collection',
                    'APP 3 limits collection to what is reasonably necessary',
                    'Sensitive information requires opt-in consent',
                    'Statutory tort for serious invasions of privacy in force from 10 June 2025',
                    'Automated-decision-making transparency obligations from 10 December 2026',
                    'Children\'s Online Privacy Code due by 10 December 2026',
                ),
                'penalties' => 'Up to AU$50 million, 30% of adjusted turnover, or 3x the benefit obtained',
            ),
            'lgpd' => array(
                'name' => 'Brazil LGPD',
                'law' => 'Lei Geral de Proteção de Dados',
                'requires_consent' => true,
                'key_requirements' => array(
                    'Clear and specific consent required',
                    'Must show legitimate purpose',
                    'Users can revoke consent',
                    'Data minimization required',
                    'Similar to GDPR requirements',
                ),
                'penalties' => 'Up to 2% of revenue (max R$50 million per violation)',
            ),
            'india_dpdp' => array(
                'name' => 'India DPDP Act + Rules 2025',
                'law' => 'Digital Personal Data Protection Act 2023 + DPDP Rules 2025 (notified 13 Nov 2025)',
                'requires_consent' => true,
                'key_requirements' => array(
                    'Standalone privacy notice in clear, plain language',
                    'Granular consent with one-click withdrawal',
                    'Verifiable parental consent for minors',
                    'Data Protection Board operational from 13 November 2025',
                    'Consent Manager registration opens 13 November 2026 (India-incorporated only)',
                    'Full compliance mandatory by 13 May 2027',
                    '72-hour personal data breach notification',
                    'Automated deletion with proof required',
                ),
                'penalties' => 'Up to ₹250 crore (approx. £25M) per violation',
            ),
            'vn_pdpl' => array(
                'name' => 'Vietnam PDPL (Law 91/2025)',
                'law' => 'Personal Data Protection Law 91/2025/QH15 + Decree 356/2025/ND-CP',
                'requires_consent' => true,
                'key_requirements' => array(
                    'In force 1 January 2026 (replaces Decree 13/2023 PDPD)',
                    'Consent must be voluntary, specific, informed and in a verifiable format',
                    'Silence or non-response does NOT constitute consent',
                    'Granular, per-purpose consent — bundled consent prohibited',
                    'Consent must be easily withdrawable at any time',
                    'Refusing non-essential processing must not deny basic services',
                    'Heightened protection for children (representative consent)',
                    'Applies extraterritorially to processors of Vietnamese residents\' data',
                    'Five-year grace period for DPIA/TIA and DPO obligations (SMEs/start-ups)',
                ),
                'penalties' => 'Up to 5% of prior-year revenue (cross-border); up to 10x unlawful data-trading gains; up to VND 3 billion for other violations',
            ),
            'id_pdp' => array(
                'name' => 'Indonesia UU PDP (Law 27/2022)',
                'law' => 'Personal Data Protection Law No. 27 of 2022 (UU PDP)',
                'requires_consent' => true,
                'key_requirements' => array(
                    'Fully effective since 17 October 2024 (two-year transition ended)',
                    'Upheld by the Constitutional Court in January 2026 — settled law',
                    'Explicit, informed consent for specific purposes required',
                    'Consent must be withdrawable; withdrawal must be honoured',
                    'Applies extraterritorially to processors of Indonesian residents\' data',
                    'Cross-border transfers require adequate protection or safeguards',
                    'Implementing regulations and supervisory authority still developing — monitor',
                ),
                'penalties' => 'Administrative fines up to 2% of annual revenue; criminal penalties up to IDR 6 billion and imprisonment for serious offences',
            ),
            'default' => array(
                'name' => 'General Best Practices',
                'law' => 'No specific regulation',
                'requires_consent' => false,
                'key_requirements' => array(
                    'Transparent about cookie usage',
                    'Provide privacy policy',
                    'Allow users to manage preferences',
                    'Respect user choices',
                ),
                'penalties' => 'Varies by jurisdiction',
            ),
            // Legacy keys — kept for backwards compatibility.
            'eu_uk' => array(
                'name' => 'EU/UK GDPR (Legacy)',
                'law' => 'General Data Protection Regulation',
                'requires_consent' => true,
                'key_requirements' => array(
                    'Explicit opt-in consent required',
                    'Reject button must be equally prominent',
                    'Pre-ticked boxes not allowed',
                    'Cookie categories must be shown',
                    'Withdrawal of consent must be easy',
                ),
                'penalties' => 'Up to €20 million or 4% of annual turnover',
            ),
            'ccpa' => array(
                'name' => 'California CCPA (Legacy)',
                'law' => 'California Consumer Privacy Act',
                'requires_consent' => false,
                'key_requirements' => array(
                    'Must provide "Do Not Sell" link',
                    'Opt-out based (not opt-in)',
                    'Must honor opt-out requests',
                    'Must disclose data collection practices',
                    'Users can request data deletion',
                ),
                'penalties' => 'Up to $7,500 per violation',
            ),
        );
        
        return isset($info[$region]) ? $info[$region] : $info['default'];
    }
}

// Initialize on plugins_loaded
add_action('plugins_loaded', function() {
    MBR_CC_Region_Config::get_instance();
}, 10);

// Helper function to get instance
function mbr_cc_region_config() {
    return MBR_CC_Region_Config::get_instance();
}
