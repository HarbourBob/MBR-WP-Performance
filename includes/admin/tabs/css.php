<?php
/**
 * CSS Tab
 *
 * @package MBR_WP_Performance
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$css_options = isset( $options['css'] ) ? $options['css'] : array();
?>

<div class="mbr-wp-performance-tab-content">
    
    <!-- Critical CSS Section -->
    <div class="mbr-wp-performance-section">
        <h2><?php esc_html_e( 'Critical CSS', 'mbr-wp-performance' ); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="inline_critical_css">
                        <?php esc_html_e( 'Inline Critical CSS', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Embeds above-the-fold CSS directly in HTML for faster rendering', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[css][inline_critical_css]" id="inline_critical_css" value="1" <?php checked( isset( $css_options['inline_critical_css'] ) && $css_options['inline_critical_css'] ); ?>>
                </td>
            </tr>
            
            <tr class="mbr-wp-performance-child-row">
                <th scope="row">
                    <label for="critical_css">
                        <?php esc_html_e( 'Critical CSS Code', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Paste your critical CSS here, or use the generator below', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <textarea name="mbr_wp_performance_options[css][critical_css]" id="critical_css" rows="10" class="large-text code"><?php echo isset( $css_options['critical_css'] ) ? esc_textarea( $css_options['critical_css'] ) : ''; ?></textarea>
                    <p class="description">
                        <button type="button" class="button" id="generate-critical-css"><?php esc_html_e( 'Auto-Generate Critical CSS', 'mbr-wp-performance' ); ?></button>
                        <span id="critical-css-status"></span>
                    </p>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- CSS Loading Strategy Section -->
    <div class="mbr-wp-performance-section">
        <h2><?php esc_html_e( 'CSS Loading Strategy', 'mbr-wp-performance' ); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="async_css">
                        <?php esc_html_e( 'Load CSS Asynchronously', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Non-blocking CSS loading - prevents render blocking', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[css][async_css]" id="async_css" value="1" <?php checked( isset( $css_options['async_css'] ) && $css_options['async_css'] ); ?>>
                </td>
            </tr>
            
            <tr class="mbr-wp-performance-child-row">
                <th scope="row">
                    <label for="exclude_async">
                        <?php esc_html_e( 'Exclude Stylesheets from Async', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Critical stylesheets that should load normally, one per line', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <textarea name="mbr_wp_performance_options[css][exclude_async]" id="exclude_async" rows="4" class="large-text code"><?php echo isset( $css_options['exclude_async'] ) ? esc_textarea( $css_options['exclude_async'] ) : ''; ?></textarea>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- File Optimization Section -->
    <div class="mbr-wp-performance-section">
        <h2><?php esc_html_e( 'File Optimization', 'mbr-wp-performance' ); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="minify_css">
                        <?php esc_html_e( 'Minify CSS', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Removes whitespace and comments to reduce file size', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[css][minify_css]" id="minify_css" value="1" <?php checked( isset( $css_options['minify_css'] ) && $css_options['minify_css'] ); ?>>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="combine_css">
                        <?php esc_html_e( 'Combine CSS Files', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'WARNING: Merges multiple stylesheets - May affect load order', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[css][combine_css]" id="combine_css" value="1" <?php checked( isset( $css_options['combine_css'] ) && $css_options['combine_css'] ); ?>>
                </td>
            </tr>
            
            <tr class="mbr-wp-performance-child-row">
                <th scope="row">
                    <label for="exclude_optimization">
                        <?php esc_html_e( 'Exclude from Minification/Combination', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Stylesheet handles or URLs to exclude, one per line', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <textarea name="mbr_wp_performance_options[css][exclude_optimization]" id="exclude_optimization" rows="4" class="large-text code"><?php echo isset( $css_options['exclude_optimization'] ) ? esc_textarea( $css_options['exclude_optimization'] ) : ''; ?></textarea>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Unused CSS Removal Section -->
    <div class="mbr-wp-performance-section">
        <h2><?php esc_html_e( 'Unused CSS Removal', 'mbr-wp-performance' ); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="remove_unused_css">
                        <?php esc_html_e( 'Remove Unused CSS', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'EXPERIMENTAL: Removes CSS not used on your pages. Always test thoroughly on a staging site first.', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[css][remove_unused_css]" id="remove_unused_css" value="1" <?php checked( isset( $css_options['remove_unused_css'] ) && $css_options['remove_unused_css'] ); ?>>
                    <p class="description" style="color: #d63638;">
                        <strong><?php esc_html_e( '⚠️ WARNING:', 'mbr-wp-performance' ); ?></strong>
                        <?php esc_html_e( 'This feature is experimental. May break responsive designs, interactive elements, or plugin styles.', 'mbr-wp-performance' ); ?>
                    </p>
                    <p>
                        <button type="button" class="button" id="scan-css"><?php esc_html_e( 'Scan Site for Used CSS', 'mbr-wp-performance' ); ?></button>
                        <button type="button" class="button" id="clear-scan-data"><?php esc_html_e( 'Clear Scan Data', 'mbr-wp-performance' ); ?></button>
                    </p>
                    <p id="scan-status" class="description"></p>
                </td>
            </tr>
        </table>
    </div>
    
    
    <!-- Block Editor Styles Section -->
    <div class="mbr-wp-performance-section">
        <h2><?php esc_html_e( 'Block Editor Styles', 'mbr-wp-performance' ); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="remove_global_styles">
                        <?php esc_html_e( 'Remove Global Styles', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Removes inline CSS added by FSE/block themes', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[css][remove_global_styles]" id="remove_global_styles" value="1" <?php checked( isset( $css_options['remove_global_styles'] ) && $css_options['remove_global_styles'] ); ?>>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="load_block_styles_conditionally">
                        <?php esc_html_e( 'Load Block Styles Conditionally', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Only loads block CSS when specific blocks are present', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[css][load_block_styles_conditionally]" id="load_block_styles_conditionally" value="1" <?php checked( isset( $css_options['load_block_styles_conditionally'] ) && $css_options['load_block_styles_conditionally'] ); ?>>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Advanced Options Section -->
    <div class="mbr-wp-performance-section">
        <h2><?php esc_html_e( 'Advanced Options', 'mbr-wp-performance' ); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="remove_css_versions">
                        <?php esc_html_e( 'Remove CSS Versions', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Removes ?ver= from stylesheet URLs for better caching', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[css][remove_css_versions]" id="remove_css_versions" value="1" <?php checked( isset( $css_options['remove_css_versions'] ) && $css_options['remove_css_versions'] ); ?>>
                </td>
            </tr>
            
            <?php if ( class_exists( 'WooCommerce' ) ) : ?>
            <tr>
                <th scope="row">
                    <label for="disable_woocommerce_css">
                        <?php esc_html_e( 'Disable WooCommerce Styles on Non-Shop Pages', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Only loads WooCommerce CSS on shop-related pages', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[css][disable_woocommerce_css]" id="disable_woocommerce_css" value="1" <?php checked( isset( $css_options['disable_woocommerce_css'] ) && $css_options['disable_woocommerce_css'] ); ?>>
                </td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    
</div>
