<?php
/**
 * Cookie Policy Generator.
 *
 * @package MBR_Cookie_Consent
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Policy Generator class.
 */
class MBR_CC_Policy_Generator {
    
    /**
     * Single instance.
     *
     * @var MBR_CC_Policy_Generator
     */
    private static $instance = null;
    
    /**
     * Get instance.
     *
     * @return MBR_CC_Policy_Generator
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
        // AJAX handler for generating policy.
        add_action('wp_ajax_mbr_cc_generate_policy', array($this, 'ajax_generate_policy'));
    }
    
    /**
     * AJAX: Generate cookie policy page.
     */
    public function ajax_generate_policy() {
        check_ajax_referer('mbr_cc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
        }
        
        $page_id = $this->create_cookie_policy_page();
        
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
     * Create cookie policy page.
     *
     * @return int|WP_Error Page ID or error.
     */
    public function create_cookie_policy_page() {
        // Check if page already exists.
        $existing_page_id = get_option('mbr_cc_policy_page_id');
        if ($existing_page_id && get_post_status($existing_page_id) !== false) {
            return new WP_Error('page_exists', 'Cookie policy page already exists.');
        }
        
        $content = $this->generate_policy_content();
        
        $page_data = array(
            'post_title' => 'Cookie Policy',
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
        update_option('mbr_cc_policy_page_id', $page_id);
        
        return $page_id;
    }
    
    /**
     * Generate cookie policy content.
     *
     * @return string Policy HTML content.
     */
    private function generate_policy_content() {
        $site_name = get_bloginfo('name');
        $site_url = home_url();
        $contact_email = get_option('admin_email');
        $categories = MBR_CC_Consent_Manager::get_instance()->get_categories();
        
        ob_start();
        ?>
<!-- wp:heading -->
<h2>Cookie Policy for <?php echo esc_html($site_name); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><strong>Last Updated:</strong> <?php echo esc_html(gmdate('F j, Y')); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>What Are Cookies?</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Cookies are small text files that are placed on your device when you visit our website. They help us provide you with a better experience by remembering your preferences and understanding how you use our site.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>How We Use Cookies</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>We use cookies for the following purposes:</p>
<!-- /wp:paragraph -->

<?php foreach ($categories as $slug => $category) : ?>
<!-- wp:heading {"level":4} -->
<h4><?php echo esc_html($category['name']); ?> Cookies</h4>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html($category['description']); ?></p>
<!-- /wp:paragraph -->

<?php if (isset($category['required']) && $category['required']) : ?>
<!-- wp:paragraph -->
<p><em>These cookies are essential and cannot be disabled.</em></p>
<!-- /wp:paragraph -->
<?php endif; ?>

<?php endforeach; ?>

<!-- wp:heading {"level":3} -->
<h3>Your Cookie Choices</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>You have the right to decide whether to accept or reject cookies. You can exercise your cookie preferences by clicking on the cookie settings button on our website.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>You can also set or amend your web browser controls to accept or refuse cookies. If you choose to reject cookies, you may still use our website, though your access to some functionality and areas may be restricted.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>Third-Party Cookies</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>In addition to our own cookies, we may also use various third-party cookies to report usage statistics of our website and deliver advertisements on and through the website.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>Updates to This Policy</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>We may update this Cookie Policy from time to time to reflect changes to the cookies we use or for other operational, legal, or regulatory reasons. Please revisit this Cookie Policy regularly to stay informed about our use of cookies.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>Contact Us</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>If you have any questions about our use of cookies, please contact us at:</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><strong>Email:</strong> <?php echo esc_html($contact_email); ?><br>
<strong>Website:</strong> <a href="<?php echo esc_url($site_url); ?>"><?php echo esc_html($site_url); ?></a></p>
<!-- /wp:paragraph -->

<!-- wp:separator -->
<hr class="wp-block-separator"/>
<!-- /wp:separator -->

<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size"><em><strong>Legal Disclaimer:</strong> This Cookie Policy is provided as a template and may require customization to meet your specific legal requirements. We recommend consulting with a legal professional to ensure compliance with applicable laws and regulations in your jurisdiction.</em></p>
<!-- /wp:paragraph -->
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get cookie policy page ID.
     *
     * @return int|false Page ID or false.
     */
    public function get_policy_page_id() {
        return get_option('mbr_cc_policy_page_id', false);
    }
    
    /**
     * Delete cookie policy page.
     *
     * @return bool Success.
     */
    public function delete_policy_page() {
        $page_id = $this->get_policy_page_id();
        
        if (!$page_id) {
            return false;
        }
        
        $result = wp_delete_post($page_id, true);
        
        if ($result) {
            delete_option('mbr_cc_policy_page_id');
            return true;
        }
        
        return false;
    }
}
