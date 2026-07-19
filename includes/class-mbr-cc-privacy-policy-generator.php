<?php
/**
 * Privacy Policy Generator
 *
 * @package MBR_Cookie_Consent
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Privacy Policy Generator class.
 */
class MBR_CC_Privacy_Policy_Generator {
    
    /**
     * Single instance.
     *
     * @var MBR_CC_Privacy_Policy_Generator
     */
    private static $instance = null;
    
    /**
     * Get instance.
     *
     * @return MBR_CC_Privacy_Policy_Generator
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
        // AJAX handler for generating privacy policy.
        add_action('wp_ajax_mbr_cc_generate_privacy_policy', array($this, 'ajax_generate_privacy_policy'));
    }
    
    /**
     * AJAX: Generate privacy policy page.
     */
    public function ajax_generate_privacy_policy() {
        check_ajax_referer('mbr_cc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
        }
        
        $page_id = $this->create_privacy_policy_page();
        
        if (is_wp_error($page_id)) {
            wp_send_json_error(array('message' => $page_id->get_error_message()));
        }
        
        wp_send_json_success(array(
            'page_id' => $page_id,
            'edit_link' => get_edit_post_link($page_id, 'raw'),
            'view_link' => get_permalink($page_id),
        ));
    }
    
    /**
     * Create privacy policy page.
     *
     * @return int|WP_Error Page ID or error.
     */
    public function create_privacy_policy_page() {
        // Check if page already exists.
        $existing_page_id = get_option('mbr_cc_privacy_policy_page_id');
        if ($existing_page_id && get_post_status($existing_page_id) !== false) {
            return new WP_Error('page_exists', 'Privacy policy page already exists.');
        }
        
        $content = $this->generate_privacy_policy_content();
        
        $page_data = array(
            'post_title' => 'Privacy Policy',
            'post_content' => $content,
            'post_status' => 'draft',
            'post_type' => 'page',
            'post_author' => get_current_user_id(),
        );
        
        $page_id = wp_insert_post($page_data);
        
        if (is_wp_error($page_id)) {
            return $page_id;
        }
        
        // Save page ID.
        update_option('mbr_cc_privacy_policy_page_id', $page_id);
        
        return $page_id;
    }
    
    /**
     * Generate privacy policy content.
     *
     * @return string Privacy policy HTML content.
     */
    public function generate_privacy_policy_content() {
        $site_name = get_bloginfo('name');
        $site_url = get_bloginfo('url');
        $admin_email = get_bloginfo('admin_email');
        $last_updated = gmdate('F j, Y');
        
        // Detect what features are being used.
        $features = $this->detect_site_features();
        
        $content = '';
        
        // Introduction
        $content .= $this->section_introduction($site_name, $last_updated);
        
        // Information We Collect
        $content .= $this->section_information_collected($features);
        
        // How We Use Your Information
        $content .= $this->section_how_we_use_information($features);
        
        // Cookies and Tracking
        $content .= $this->section_cookies_tracking($features);
        
        // Data Sharing
        $content .= $this->section_data_sharing($features);
        
        // Your Rights
        $content .= $this->section_your_rights($features);
        
        // Data Security
        $content .= $this->section_data_security();
        
        // Third-Party Services
        if (!empty($features['third_party_services'])) {
            $content .= $this->section_third_party_services($features);
        }
        
        // E-commerce specific
        if ($features['ecommerce']) {
            $content .= $this->section_ecommerce($features);
        }
        
        // Email/Newsletter
        if ($features['email_marketing']) {
            $content .= $this->section_email_marketing();
        }
        
        // Children's Privacy
        $content .= $this->section_childrens_privacy();
        
        // International Users
        if ($features['international']) {
            $content .= $this->section_international_users();
        }
        
        // California Privacy Rights (CCPA)
        if ($features['ccpa']) {
            $content .= $this->section_ccpa();
        }
        
        // GDPR Rights
        if ($features['gdpr']) {
            $content .= $this->section_gdpr();
        }
        
        // IAB TCF
        if ($features['iab_tcf']) {
            $content .= $this->section_iab_tcf();
        }
        
        // Changes to Policy
        $content .= $this->section_changes_to_policy();
        
        // Contact Information
        $content .= $this->section_contact($site_name, $admin_email);
        
        return $content;
    }
    
    /**
     * Detect site features to customize privacy policy.
     *
     * @return array Features detected.
     */
    private function detect_site_features() {
        $features = array(
            'ecommerce' => false,
            'comments' => false,
            'registration' => false,
            'email_marketing' => false,
            'analytics' => false,
            'advertising' => false,
            'social_media' => false,
            'contact_forms' => false,
            'newsletter' => false,
            'membership' => false,
            'gdpr' => false,
            'ccpa' => false,
            'iab_tcf' => false,
            'google_consent_mode' => false,
            'international' => false,
            'third_party_services' => array(),
        );
        
        // E-commerce detection
        $features['ecommerce'] = class_exists('WooCommerce') || class_exists('Easy_Digital_Downloads');
        
        // Comments
        $features['comments'] = comments_open();
        
        // User registration
        $features['registration'] = get_option('users_can_register');
        
        // Analytics detection
        $features['analytics'] = $this->has_google_analytics() || get_option('mbr_cc_google_consent_mode', false);
        
        // Advertising detection
        $features['advertising'] = $this->has_advertising();
        
        // Email marketing
        $features['email_marketing'] = $this->has_email_marketing();
        
        // Contact forms
        $features['contact_forms'] = $this->has_contact_forms();
        
        // Social media
        $features['social_media'] = $this->has_social_media();
        
        // GDPR (enabled by plugin features)
        $features['gdpr'] = true; // Always include GDPR section
        
        // CCPA
        $features['ccpa'] = get_option('mbr_cc_enable_ccpa', false);
        
        // IAB TCF
        $features['iab_tcf'] = get_option('mbr_cc_iab_tcf_enabled', false);
        
        // Google Consent Mode
        $features['google_consent_mode'] = get_option('mbr_cc_google_consent_mode', false);
        
        // International
        $features['international'] = get_option('mbr_cc_auto_translate', false);
        
        // Third-party services
        $features['third_party_services'] = $this->detect_third_party_services();
        
        return $features;
    }
    
    /**
     * Detect if Google Analytics is installed.
     *
     * @return bool Has Google Analytics.
     */
    private function has_google_analytics() {
        // Check for common GA plugins or scripts
        $scripts = get_option('mbr_cc_blocked_scripts', array());
        foreach ($scripts as $script) {
            if (stripos($script['identifier'], 'google-analytics') !== false || 
                stripos($script['identifier'], 'gtag') !== false) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Detect if advertising is used.
     *
     * @return bool Has advertising.
     */
    private function has_advertising() {
        $scripts = get_option('mbr_cc_blocked_scripts', array());
        foreach ($scripts as $script) {
            if (stripos($script['identifier'], 'googlesyndication') !== false || 
                stripos($script['identifier'], 'doubleclick') !== false ||
                stripos($script['identifier'], 'adsense') !== false) {
                return true;
            }
        }
        return get_option('mbr_cc_google_acm_enabled', false) || get_option('mbr_cc_iab_tcf_enabled', false);
    }
    
    /**
     * Detect email marketing tools.
     *
     * @return bool Has email marketing.
     */
    private function has_email_marketing() {
        return class_exists('Newsletter') || 
               class_exists('MailPoet') || 
               function_exists('mailchimp_sf') ||
               class_exists('WYSIJA');
    }
    
    /**
     * Detect contact forms.
     *
     * @return bool Has contact forms.
     */
    private function has_contact_forms() {
        return class_exists('WPCF7') || // Contact Form 7
               class_exists('GFForms') || // Gravity Forms
               class_exists('Ninja_Forms') ||
               class_exists('Formidable');
    }
    
    /**
     * Detect social media integrations.
     *
     * @return bool Has social media.
     */
    private function has_social_media() {
        $scripts = get_option('mbr_cc_blocked_scripts', array());
        foreach ($scripts as $script) {
            if (stripos($script['identifier'], 'facebook') !== false || 
                stripos($script['identifier'], 'twitter') !== false ||
                stripos($script['identifier'], 'linkedin') !== false) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Detect third-party services.
     *
     * @return array Third-party services.
     */
    private function detect_third_party_services() {
        $services = array();
        
        if ($this->has_google_analytics()) {
            $services[] = 'Google Analytics';
        }
        
        if (get_option('mbr_cc_google_consent_mode', false)) {
            $services[] = 'Google Ads';
        }
        
        if (get_option('mbr_cc_google_acm_enabled', false)) {
            $services[] = 'Google Ad Manager';
        }
        
        if ($this->has_social_media()) {
            $services[] = 'Social Media Platforms';
        }
        
        if (class_exists('WooCommerce')) {
            $services[] = 'Payment Processors';
        }
        
        if ($this->has_email_marketing()) {
            $services[] = 'Email Service Providers';
        }
        
        return $services;
    }
    
    /**
     * Section: Introduction
     */
    private function section_introduction($site_name, $last_updated) {
        return '<p><strong>Last Updated:</strong> ' . $last_updated . '</p>

<p>Welcome to ' . $site_name . '. We respect your privacy and are committed to protecting your personal data. This privacy policy explains how we collect, use, and share information about you when you use our website.</p>

<p>Please read this privacy policy carefully. By using our website, you agree to the collection and use of information in accordance with this policy.</p>

';
    }
    
    /**
     * Section: Information We Collect
     */
    private function section_information_collected($features) {
        $content = '<h2>1. Information We Collect</h2>

<p>We collect several types of information from and about users of our website:</p>

<h3>1.1 Information You Provide Directly</h3>
<ul>';
        
        if ($features['registration']) {
            $content .= '<li><strong>Account Information:</strong> When you create an account, we collect your name, email address, username, and password.</li>';
        }
        
        if ($features['contact_forms']) {
            $content .= '<li><strong>Contact Information:</strong> When you contact us through forms, we collect your name, email address, and any information you choose to provide in your message.</li>';
        }
        
        if ($features['comments']) {
            $content .= '<li><strong>Comments:</strong> When you leave comments, we collect your name, email address, and the content of your comment.</li>';
        }
        
        if ($features['ecommerce']) {
            $content .= '<li><strong>Purchase Information:</strong> When you make a purchase, we collect billing information, shipping address, and payment details (processed securely through our payment processors).</li>';
        }
        
        if ($features['email_marketing']) {
            $content .= '<li><strong>Newsletter Subscriptions:</strong> When you subscribe to our newsletter, we collect your email address and optionally your name.</li>';
        }
        
        $content .= '</ul>

<h3>1.2 Information Collected Automatically</h3>
<ul>
<li><strong>Device Information:</strong> We collect information about the device you use to access our website, including IP address, browser type, operating system, and device identifiers.</li>
<li><strong>Usage Information:</strong> We collect information about your interactions with our website, including pages viewed, time spent on pages, links clicked, and navigation paths.</li>
<li><strong>Location Information:</strong> We may collect general location information based on your IP address.</li>';
        
        if ($features['analytics']) {
            $content .= '<li><strong>Analytics Data:</strong> We use analytics services to collect data about how you use our website, including referral sources, search terms, and browsing behavior.</li>';
        }
        
        $content .= '</ul>

<h3>1.3 Information from Cookies and Similar Technologies</h3>
<p>We use cookies and similar tracking technologies to collect information about your browsing activities. For detailed information about our use of cookies, please see our <a href="#">Cookie Policy</a>.</p>

';
        
        return $content;
    }
    
    /**
     * Section: How We Use Your Information
     */
    private function section_how_we_use_information($features) {
        $content = '<h2>2. How We Use Your Information</h2>

<p>We use the information we collect for the following purposes:</p>

<ul>
<li><strong>To Provide Our Services:</strong> To operate and maintain our website, process your requests, and provide customer support.</li>';
        
        if ($features['ecommerce']) {
            $content .= '<li><strong>To Process Transactions:</strong> To process your orders, handle payments, and deliver products or services you purchase.</li>';
        }
        
        if ($features['registration']) {
            $content .= '<li><strong>To Manage Your Account:</strong> To create and manage your user account and provide you with account-related services.</li>';
        }
        
        $content .= '<li><strong>To Communicate With You:</strong> To respond to your inquiries, send important notices, and provide you with information you request.</li>';
        
        if ($features['email_marketing']) {
            $content .= '<li><strong>To Send Marketing Communications:</strong> To send you newsletters, promotional materials, and other information that may interest you (you can opt out at any time).</li>';
        }
        
        if ($features['analytics']) {
            $content .= '<li><strong>To Improve Our Services:</strong> To analyze how our website is used, identify trends, and improve our content and functionality.</li>';
        }
        
        if ($features['advertising']) {
            $content .= '<li><strong>To Deliver Advertising:</strong> To show you relevant advertisements based on your interests and browsing behavior.</li>';
        }
        
        $content .= '<li><strong>To Ensure Security:</strong> To detect, prevent, and address technical issues, fraud, and other harmful activities.</li>
<li><strong>To Comply With Legal Obligations:</strong> To comply with applicable laws, regulations, and legal processes.</li>
</ul>

';
        
        return $content;
    }
    
    /**
     * Section: Cookies and Tracking
     */
    private function section_cookies_tracking($features) {
        $content = '<h2>3. Cookies and Tracking Technologies</h2>

<p>We use cookies and similar tracking technologies to collect and store information about your preferences and browsing activities.</p>

<h3>Types of Cookies We Use:</h3>
<ul>
<li><strong>Necessary Cookies:</strong> Essential for the website to function properly. These cannot be disabled.</li>';
        
        if ($features['analytics']) {
            $content .= '<li><strong>Analytics Cookies:</strong> Help us understand how visitors interact with our website by collecting and reporting information anonymously.</li>';
        }
        
        if ($features['advertising']) {
            $content .= '<li><strong>Marketing Cookies:</strong> Used to track visitors across websites to display relevant and engaging advertisements.</li>';
        }
        
        $content .= '<li><strong>Preference Cookies:</strong> Remember your preferences and settings to provide a personalized experience.</li>
</ul>

<p>You can control cookies through our cookie consent banner and your browser settings. For more detailed information, please see our <a href="#">Cookie Policy</a>.</p>

';
        
        if ($features['google_consent_mode']) {
            $content .= '<h3>Google Consent Mode</h3>
<p>We use Google Consent Mode, which adjusts how Google tags behave based on your consent choices. When you deny consent, Google tags operate in a limited mode that doesn\'t use cookies for advertising or personalization.</p>

';
        }
        
        return $content;
    }
    
    /**
     * Section: Data Sharing
     */
    private function section_data_sharing($features) {
        $content = '<h2>4. How We Share Your Information</h2>

<p>We do not sell your personal information. We may share your information in the following circumstances:</p>

<ul>
<li><strong>Service Providers:</strong> We share information with third-party service providers who perform services on our behalf, such as hosting, analytics, payment processing, and customer support.</li>';
        
        if ($features['ecommerce']) {
            $content .= '<li><strong>Payment Processors:</strong> When you make a purchase, we share necessary payment information with our payment processors to complete the transaction securely.</li>';
        }
        
        if ($features['advertising']) {
            $content .= '<li><strong>Advertising Partners:</strong> We may share information with advertising partners to deliver relevant ads to you on our website and other sites.</li>';
        }
        
        $content .= '<li><strong>Legal Requirements:</strong> We may disclose information if required by law or in response to valid legal requests from authorities.</li>
<li><strong>Business Transfers:</strong> In the event of a merger, acquisition, or sale of assets, your information may be transferred to the acquiring entity.</li>
<li><strong>With Your Consent:</strong> We may share information with third parties when you give us permission to do so.</li>
</ul>

';
        
        return $content;
    }
    
    /**
     * Section: Your Rights
     */
    private function section_your_rights($features) {
        $admin_email = get_bloginfo('admin_email');
        
        return '<h2>5. Your Privacy Rights</h2>

<p>Depending on your location, you may have certain rights regarding your personal information:</p>

<ul>
<li><strong>Access:</strong> You can request access to the personal information we hold about you.</li>
<li><strong>Correction:</strong> You can request that we correct inaccurate or incomplete information.</li>
<li><strong>Deletion:</strong> You can request that we delete your personal information (subject to certain exceptions).</li>
<li><strong>Portability:</strong> You can request a copy of your information in a structured, commonly used format.</li>
<li><strong>Objection:</strong> You can object to our processing of your information for certain purposes.</li>
<li><strong>Restriction:</strong> You can request that we restrict processing of your information in certain circumstances.</li>
<li><strong>Withdraw Consent:</strong> Where we rely on consent, you can withdraw it at any time through our cookie consent banner or by contacting us.</li>
</ul>

<p>To exercise these rights, please contact us at <a href="mailto:' . $admin_email . '">' . $admin_email . '</a>.</p>

';
    }
    
    /**
     * Section: Data Security
     */
    private function section_data_security() {
        return '<h2>6. Data Security</h2>

<p>We implement appropriate technical and organizational measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p>

<p>These measures include:</p>
<ul>
<li>Encryption of data in transit using SSL/TLS</li>
<li>Secure server infrastructure</li>
<li>Regular security assessments</li>
<li>Access controls and authentication</li>
<li>Employee training on data protection</li>
</ul>

<p>However, no method of transmission over the internet or electronic storage is 100% secure. While we strive to protect your personal information, we cannot guarantee its absolute security.</p>

';
    }
    
    /**
     * Section: Third-Party Services
     */
    private function section_third_party_services($features) {
        $services = $features['third_party_services'];
        $services_list = implode(', ', $services);
        
        return '<h2>7. Third-Party Services</h2>

<p>We use the following third-party services that may collect information about you:</p>

<ul>'
        . implode('', array_map(function($service) {
            return '<li>' . $service . '</li>';
        }, $services)) .
'</ul>

<p>These third-party services have their own privacy policies. We encourage you to review their policies to understand how they collect and use your information.</p>

';
    }
    
    /**
     * Section: E-commerce
     */
    private function section_ecommerce($features) {
        return '<h2>8. Online Purchases and Payment Processing</h2>

<p>When you make a purchase through our website:</p>

<ul>
<li>We collect billing and shipping information necessary to process and fulfill your order.</li>
<li>Payment information is processed securely through third-party payment processors. We do not store complete credit card numbers.</li>
<li>We retain transaction records for accounting, tax, and legal purposes.</li>
<li>Order information may be shared with shipping carriers to deliver your purchase.</li>
</ul>

<p>We retain your purchase history to provide customer service, process returns, and improve our services.</p>

';
    }
    
    /**
     * Section: Email Marketing
     */
    private function section_email_marketing() {
        return '<h2>9. Email Communications and Marketing</h2>

<p>If you subscribe to our newsletter or marketing emails:</p>

<ul>
<li>We will use your email address to send you updates, promotions, and information about our services.</li>
<li>You can unsubscribe at any time by clicking the "unsubscribe" link in any email or contacting us directly.</li>
<li>We may track email opens and clicks to improve our communications.</li>
<li>Your email address will not be sold or shared with third parties for their marketing purposes.</li>
</ul>

';
    }
    
    /**
     * Section: Children's Privacy
     */
    private function section_childrens_privacy() {
        return '<h2>10. Children\'s Privacy</h2>

<p>Our website is not intended for children under the age of 16. We do not knowingly collect personal information from children under 16. If you are a parent or guardian and believe your child has provided us with personal information, please contact us, and we will delete such information.</p>

';
    }
    
    /**
     * Section: International Users
     */
    private function section_international_users() {
        return '<h2>11. International Data Transfers</h2>

<p>Your information may be transferred to and processed in countries other than your country of residence. These countries may have data protection laws that differ from your country.</p>

<p>When we transfer information internationally, we ensure appropriate safeguards are in place to protect your information in accordance with applicable data protection laws.</p>

';
    }
    
    /**
     * Section: CCPA
     */
    private function section_ccpa() {
        $admin_email = get_bloginfo('admin_email');
        
        return '<h2>12. California Privacy Rights (CCPA)</h2>

<p>If you are a California resident, you have additional rights under the California Consumer Privacy Act (CCPA):</p>

<h3>Your Rights:</h3>
<ul>
<li><strong>Right to Know:</strong> You can request information about the personal information we collect, use, and disclose.</li>
<li><strong>Right to Delete:</strong> You can request deletion of your personal information.</li>
<li><strong>Right to Opt-Out:</strong> You can opt-out of the "sale" of your personal information (we do not sell personal information).</li>
<li><strong>Right to Non-Discrimination:</strong> We will not discriminate against you for exercising your CCPA rights.</li>
</ul>

<h3>Do Not Sell My Personal Information</h3>
<p>We do not sell personal information as defined by the CCPA. However, you can manage how your information is used for advertising through our cookie consent banner.</p>

<p>To exercise your CCPA rights, contact us at <a href="mailto:' . $admin_email . '">' . $admin_email . '</a> or click "Do Not Sell or Share My Personal Information" in our website footer.</p>

';
    }
    
    /**
     * Section: GDPR
     */
    private function section_gdpr() {
        $admin_email = get_bloginfo('admin_email');
        
        return '<h2>13. EU/EEA Privacy Rights (GDPR)</h2>

<p>If you are in the European Union or European Economic Area, you have rights under the General Data Protection Regulation (GDPR):</p>

<h3>Legal Basis for Processing</h3>
<p>We process your personal data based on:</p>
<ul>
<li><strong>Consent:</strong> When you give us permission (e.g., cookies, marketing emails)</li>
<li><strong>Contract:</strong> When necessary to fulfill our agreement with you (e.g., processing orders)</li>
<li><strong>Legitimate Interests:</strong> When we have legitimate business reasons (e.g., fraud prevention)</li>
<li><strong>Legal Obligation:</strong> When required by law</li>
</ul>

<h3>Your GDPR Rights</h3>
<ul>
<li>Right to access your data</li>
<li>Right to rectification</li>
<li>Right to erasure ("right to be forgotten")</li>
<li>Right to restrict processing</li>
<li>Right to data portability</li>
<li>Right to object to processing</li>
<li>Right to withdraw consent</li>
<li>Right to lodge a complaint with a supervisory authority</li>
</ul>

<h3>Data Retention</h3>
<p>We retain your personal data only for as long as necessary for the purposes outlined in this policy or as required by law. When data is no longer needed, we securely delete or anonymize it.</p>

<p>To exercise your GDPR rights, contact us at <a href="mailto:' . $admin_email . '">' . $admin_email . '</a>.</p>

';
    }
    
    /**
     * Section: IAB TCF
     */
    private function section_iab_tcf() {
        return '<h2>14. IAB Transparency & Consent Framework</h2>

<p>We participate in the IAB Europe Transparency & Consent Framework (TCF) to manage consent for digital advertising.</p>

<h3>What This Means:</h3>
<ul>
<li>We use the TCF to collect and communicate your consent choices to advertising vendors.</li>
<li>Vendors registered with the IAB receive standardized consent signals.</li>
<li>You can manage your consent preferences through our cookie banner.</li>
<li>Consent information is stored in a standard format (TC String) in your browser.</li>
</ul>

<h3>Your TCF Rights:</h3>
<ul>
<li>View the list of vendors we work with</li>
<li>Give or withdraw consent for specific purposes</li>
<li>Object to processing based on legitimate interest</li>
<li>Manage special features like geolocation use</li>
</ul>

<p>For more information about the TCF, visit <a href="https://iabeurope.eu/transparency-consent-framework/" target="_blank">IAB Europe\'s website</a>.</p>

';
    }
    
    /**
     * Section: Changes to Policy
     */
    private function section_changes_to_policy() {
        return '<h2>15. Changes to This Privacy Policy</h2>

<p>We may update this privacy policy from time to time to reflect changes in our practices or for legal, regulatory, or operational reasons.</p>

<p>When we make changes, we will update the "Last Updated" date at the top of this policy. We encourage you to review this policy periodically.</p>

<p>If we make material changes, we will provide notice through our website or by other means as appropriate.</p>

';
    }
    
    /**
     * Section: Contact
     */
    private function section_contact($site_name, $admin_email) {
        return '<h2>16. Contact Us</h2>

<p>If you have questions about this privacy policy or our privacy practices, please contact us:</p>

<ul>
<li><strong>Website:</strong> ' . $site_name . '</li>
<li><strong>Email:</strong> <a href="mailto:' . $admin_email . '">' . $admin_email . '</a></li>
</ul>

<p>We will respond to your inquiry as soon as possible, typically within 30 days.</p>

<hr>

<p><em>This privacy policy was generated by MBR Cookie Consent plugin and should be reviewed by legal counsel before publication.</em></p>

';
    }
}
