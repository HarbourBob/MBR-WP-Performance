<?php
/**
 * Lazy Loading Settings Tab
 *
 * @package MBR_WP_Performance
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$options = get_option( 'mbr_wp_performance_options', array() );
$lazy_options = isset( $options['lazy_loading'] ) ? $options['lazy_loading'] : array();
?>

<div class="mbr-section">
    <h2><?php esc_html_e( 'Lazy Loading', 'mbr-wp-performance' ); ?></h2>
    
    <table class="form-table">
        <tbody>
            <!-- Lazy Load Images -->
            <tr>
                <th scope="row">
                    <label for="lazy_load_images">
                        <?php esc_html_e( 'Lazy Load Images', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Load images only when they enter the viewport', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[lazy_loading][lazy_load_images]" id="lazy_load_images" value="1" <?php checked( isset( $lazy_options['lazy_load_images'] ) && $lazy_options['lazy_load_images'] ); ?>>
                    <p class="description"><?php esc_html_e( 'Adds native lazy loading to all images.', 'mbr-wp-performance' ); ?></p>
                </td>
            </tr>
            
            <!-- Lazy Load iFrames and Videos -->
            <tr>
                <th scope="row">
                    <label for="lazy_load_iframes">
                        <?php esc_html_e( 'Lazy Load iFrames and Videos', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Load YouTube, Vimeo, and other iframes only when visible', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[lazy_loading][lazy_load_iframes]" id="lazy_load_iframes" value="1" <?php checked( isset( $lazy_options['lazy_load_iframes'] ) && $lazy_options['lazy_load_iframes'] ); ?>>
                    <p class="description"><?php esc_html_e( 'Defers loading of iframes and embedded videos.', 'mbr-wp-performance' ); ?></p>
                </td>
            </tr>
            
            <!-- Exclude from Lazy Loading -->
            <tr>
                <th scope="row">
                    <label for="exclude_lazy_load">
                        <?php esc_html_e( 'Exclude from Lazy Loading', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'CSS selectors or keywords to exclude from lazy loading', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <textarea name="mbr_wp_performance_options[lazy_loading][exclude_lazy_load]" id="exclude_lazy_load" rows="5" class="large-text code" placeholder=".no-lazy&#10;skip-lazy&#10;data-no-lazy&#10;#hero-image"><?php echo isset( $lazy_options['exclude_lazy_load'] ) ? esc_textarea( $lazy_options['exclude_lazy_load'] ) : ''; ?></textarea>
                    <p class="description"><?php esc_html_e( 'One per line. Can be: class names, IDs, data attributes, or keywords in src/class.', 'mbr-wp-performance' ); ?></p>
                </td>
            </tr>
            
            <!-- Exclude by Parent Selector -->
            <tr>
                <th scope="row">
                    <label for="exclude_parent_selector">
                        <?php esc_html_e( 'Exclude by Parent Selector', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Exclude images within specific parent elements', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <textarea name="mbr_wp_performance_options[lazy_loading][exclude_parent_selector]" id="exclude_parent_selector" rows="3" class="large-text code" placeholder=".hero-section&#10;#main-banner&#10;.above-fold"><?php echo isset( $lazy_options['exclude_parent_selector'] ) ? esc_textarea( $lazy_options['exclude_parent_selector'] ) : ''; ?></textarea>
                    <p class="description"><?php esc_html_e( 'Exclude all images within these parent containers (e.g., hero sections).', 'mbr-wp-performance' ); ?></p>
                </td>
            </tr>
            
            <!-- Threshold -->
            <tr>
                <th scope="row">
                    <label for="lazy_threshold">
                        <?php esc_html_e( 'Threshold', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Start loading images X pixels before they enter viewport', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="number" name="mbr_wp_performance_options[lazy_loading][lazy_threshold]" id="lazy_threshold" value="<?php echo isset( $lazy_options['lazy_threshold'] ) ? esc_attr( $lazy_options['lazy_threshold'] ) : '200'; ?>" min="0" max="2000" step="50" class="small-text">
                    <span><?php esc_html_e( 'pixels', 'mbr-wp-performance' ); ?></span>
                    <p class="description"><?php esc_html_e( 'Default: 200px. Higher = earlier loading, lower = later loading.', 'mbr-wp-performance' ); ?></p>
                </td>
            </tr>
            
            <!-- DOM Monitoring -->
            <tr>
                <th scope="row">
                    <label for="dom_monitoring">
                        <?php esc_html_e( 'DOM Monitoring', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Monitor for dynamically added images (AJAX, sliders, etc.)', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[lazy_loading][dom_monitoring]" id="dom_monitoring" value="1" <?php checked( isset( $lazy_options['dom_monitoring'] ) && $lazy_options['dom_monitoring'] ); ?>>
                    <p class="description"><?php esc_html_e( 'Automatically lazy load images added after page load (carousels, infinite scroll, etc.).', 'mbr-wp-performance' ); ?></p>
                </td>
            </tr>
            
            <!-- Add Missing Dimensions -->
            <tr>
                <th scope="row">
                    <label for="add_missing_dimensions">
                        <?php esc_html_e( 'Add Missing Image Dimensions', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Automatically add width/height attributes to prevent layout shift', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[lazy_loading][add_missing_dimensions]" id="add_missing_dimensions" value="1" <?php checked( isset( $lazy_options['add_missing_dimensions'] ) && $lazy_options['add_missing_dimensions'] ); ?>>
                    <p class="description"><?php esc_html_e( 'Improves CLS (Cumulative Layout Shift) by adding missing width/height attributes.', 'mbr-wp-performance' ); ?></p>
                </td>
            </tr>
            
            <!-- Fade In -->
            <tr>
                <th scope="row">
                    <label for="lazy_fade_in">
                        <?php esc_html_e( 'Fade In Effect', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Smooth fade-in animation when lazy loaded images appear', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[lazy_loading][lazy_fade_in]" id="lazy_fade_in" value="1" <?php checked( isset( $lazy_options['lazy_fade_in'] ) && $lazy_options['lazy_fade_in'] ); ?>>
                    <label for="lazy_fade_duration" style="margin-left: 20px;"><?php esc_html_e( 'Duration:', 'mbr-wp-performance' ); ?></label>
                    <input type="number" name="mbr_wp_performance_options[lazy_loading][lazy_fade_duration]" id="lazy_fade_duration" value="<?php echo isset( $lazy_options['lazy_fade_duration'] ) ? esc_attr( $lazy_options['lazy_fade_duration'] ) : '300'; ?>" min="100" max="2000" step="100" class="small-text">
                    <span><?php esc_html_e( 'ms', 'mbr-wp-performance' ); ?></span>
                    <p class="description"><?php esc_html_e( 'Adds a smooth fade-in effect when images load. Default: 300ms.', 'mbr-wp-performance' ); ?></p>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<div class="mbr-section">
    <h2><?php esc_html_e( 'CSS Background Images', 'mbr-wp-performance' ); ?></h2>
    
    <table class="form-table">
        <tbody>
            <!-- Lazy Load Background Images -->
            <tr>
                <th scope="row">
                    <label for="lazy_background_images">
                        <?php esc_html_e( 'Lazy Load Background Images', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Lazy load CSS background images', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[lazy_loading][lazy_background_images]" id="lazy_background_images" value="1" <?php checked( isset( $lazy_options['lazy_background_images'] ) && $lazy_options['lazy_background_images'] ); ?>>
                    <p class="description"><?php esc_html_e( 'Defers loading of background images set via CSS.', 'mbr-wp-performance' ); ?></p>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<div class="mbr-section">
    <h2><?php esc_html_e( 'Custom Lazy Elements', 'mbr-wp-performance' ); ?></h2>
    
    <table class="form-table">
        <tbody>
            <!-- Custom Elements to Lazy Load -->
            <tr>
                <th scope="row">
                    <label for="lazy_elements">
                        <?php esc_html_e( 'Custom Elements', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'CSS selectors for custom elements to lazy load', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <textarea name="mbr_wp_performance_options[lazy_loading][lazy_elements]" id="lazy_elements" rows="5" class="large-text code" placeholder=".custom-widget&#10;.social-feed&#10;.comments-section"><?php echo isset( $lazy_options['lazy_elements'] ) ? esc_textarea( $lazy_options['lazy_elements'] ) : ''; ?></textarea>
                    <p class="description"><?php esc_html_e( 'CSS selectors for custom elements to lazy load (widgets, social feeds, etc.).', 'mbr-wp-performance' ); ?></p>
                </td>
            </tr>
        </tbody>
    </table>
</div>
