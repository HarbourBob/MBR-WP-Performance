<?php
/**
 * Categories View
 *
 * @package MBR_Cookie_Consent
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

$manager = MBR_CC_Consent_Manager::get_instance();
$categories = $manager->get_categories();
?>

<div class="wrap mbr-cc-admin-wrap">
    <h1><?php esc_html_e('Cookie Categories', 'mbr-cookie-consent'); ?></h1>
    
    <p><?php esc_html_e('Customize the cookie categories displayed to your users.', 'mbr-cookie-consent'); ?></p>
    
    <form method="post" id="mbr-cc-categories-form">
        <?php foreach ($categories as $slug => $category) : ?>
            <div class="mbr-cc-category-item" data-slug="<?php echo esc_attr($slug); ?>">
                <h3><?php echo esc_html(ucfirst($slug)); ?></h3>
                
                <table class="form-table">
                    <tr>
                        <th><label><?php esc_html_e('Name', 'mbr-cookie-consent'); ?></label></th>
                        <td>
                            <input type="text" name="categories[<?php echo esc_attr($slug); ?>][name]" class="category-name regular-text" value="<?php echo esc_attr($category['name']); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('Description', 'mbr-cookie-consent'); ?></label></th>
                        <td>
                            <textarea name="categories[<?php echo esc_attr($slug); ?>][description]" class="category-description large-text" rows="3"><?php echo esc_textarea($category['description']); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('Settings', 'mbr-cookie-consent'); ?></label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="categories[<?php echo esc_attr($slug); ?>][required]" class="category-required" value="1" <?php checked(!empty($category['required'])); ?> <?php disabled($slug === 'necessary'); ?>>
                                <?php esc_html_e('Always Required', 'mbr-cookie-consent'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('If checked, users cannot disable this category.', 'mbr-cookie-consent'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        <?php endforeach; ?>
        
        <p class="submit">
            <button type="button" id="mbr-cc-save-categories" class="button button-primary">
                <?php esc_html_e('Save Categories', 'mbr-cookie-consent'); ?>
            </button>
        </p>
    </form>
</div>
