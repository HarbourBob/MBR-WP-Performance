<?php
/**
 * CSS Tab
 *
 * @package MBRPE
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$css_options = isset( $options['css'] ) ? $options['css'] : array();
?>

<div class="mbr-performance-tab-content">
    
    <!-- CSS Loading Strategy Section -->
    <div class="mbr-performance-section">
        <h2><?php esc_html_e( 'CSS Loading Strategy', 'mbr-performance' ); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="async_css">
                        <?php esc_html_e( 'Load CSS Asynchronously', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Non-blocking CSS loading. The first 2 stylesheets stay render-blocking to prevent a flash of unstyled content; the rest load asynchronously.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[css][async_css]" id="async_css" value="1" <?php checked( isset( $css_options['async_css'] ) && $css_options['async_css'] ); ?>>
                    <p class="description">
                        <?php esc_html_e( 'The first 2 stylesheets remain render-blocking as a safety net to prevent a flash of unstyled content; all remaining stylesheets load asynchronously.', 'mbr-performance' ); ?>
                    </p>
                </td>
            </tr>
            
            <tr class="mbr-performance-child-row">
                <th scope="row">
                    <label for="exclude_async">
                        <?php esc_html_e( 'Exclude Stylesheets from Async', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Critical stylesheets that should load normally, one per line', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <textarea name="mbrpe_options[css][exclude_async]" id="exclude_async" rows="4" class="large-text code"><?php echo isset( $css_options['exclude_async'] ) ? esc_textarea( $css_options['exclude_async'] ) : ''; ?></textarea>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- File Optimization Section -->
    <div class="mbr-performance-section">
        <h2><?php esc_html_e( 'File Optimization', 'mbr-performance' ); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="minify_css">
                        <?php esc_html_e( 'Minify CSS', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Removes whitespace and comments to reduce file size', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[css][minify_css]" id="minify_css" value="1" <?php checked( isset( $css_options['minify_css'] ) && $css_options['minify_css'] ); ?>>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="combine_css">
                        <?php esc_html_e( 'Combine CSS Files', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'WARNING: Merges multiple stylesheets - May affect load order', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[css][combine_css]" id="combine_css" value="1" <?php checked( isset( $css_options['combine_css'] ) && $css_options['combine_css'] ); ?>>
                </td>
            </tr>
            
            <tr class="mbr-performance-child-row">
                <th scope="row">
                    <label for="exclude_optimization">
                        <?php esc_html_e( 'Exclude from Minification/Combination', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Stylesheet handles or URLs to exclude, one per line', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <textarea name="mbrpe_options[css][exclude_optimization]" id="exclude_optimization" rows="4" class="large-text code"><?php echo isset( $css_options['exclude_optimization'] ) ? esc_textarea( $css_options['exclude_optimization'] ) : ''; ?></textarea>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Unused CSS Removal Section -->
    <div class="mbr-performance-section">
        <h2><?php esc_html_e( 'Unused CSS Removal', 'mbr-performance' ); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="remove_unused_css">
                        <?php esc_html_e( 'Remove Unused CSS', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'EXPERIMENTAL: Removes CSS not used on your pages. Always test thoroughly on a staging site first.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[css][remove_unused_css]" id="remove_unused_css" value="1" <?php checked( isset( $css_options['remove_unused_css'] ) && $css_options['remove_unused_css'] ); ?>>
                    <p class="description" style="color: #d63638;">
                        <strong><?php esc_html_e( '⚠️ WARNING:', 'mbr-performance' ); ?></strong>
                        <?php esc_html_e( 'This feature is experimental. May break responsive designs, interactive elements, or plugin styles.', 'mbr-performance' ); ?>
                    </p>
                    <p>
                        <button type="button" class="button" id="scan-css"><?php esc_html_e( 'Scan Site for Used CSS', 'mbr-performance' ); ?></button>
                        <button type="button" class="button" id="clear-scan-data"><?php esc_html_e( 'Clear Scan Data', 'mbr-performance' ); ?></button>
                    </p>
                    <p id="scan-status" class="description"></p>
                </td>
            </tr>
        </table>
    </div>
    
    
    <!-- Block Editor Styles Section -->
    <div class="mbr-performance-section">
        <h2><?php esc_html_e( 'Block Editor Styles', 'mbr-performance' ); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="load_block_styles_conditionally">
                        <?php esc_html_e( 'Load Block Styles Conditionally', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Only loads block CSS when specific blocks are present', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[css][load_block_styles_conditionally]" id="load_block_styles_conditionally" value="1" <?php checked( isset( $css_options['load_block_styles_conditionally'] ) && $css_options['load_block_styles_conditionally'] ); ?>>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Advanced Options Section -->
    <div class="mbr-performance-section">
        <h2><?php esc_html_e( 'Advanced Options', 'mbr-performance' ); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="remove_css_versions">
                        <?php esc_html_e( 'Remove CSS Versions', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Removes ?ver= from stylesheet URLs for better caching', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[css][remove_css_versions]" id="remove_css_versions" value="1" <?php checked( isset( $css_options['remove_css_versions'] ) && $css_options['remove_css_versions'] ); ?>>
                </td>
            </tr>
            
            <?php if ( class_exists( 'WooCommerce' ) ) : ?>
            <tr>
                <th scope="row">
                    <label for="disable_woocommerce_css">
                        <?php esc_html_e( 'Disable WooCommerce Styles on Non-Shop Pages', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Only loads WooCommerce CSS on shop-related pages', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[css][disable_woocommerce_css]" id="disable_woocommerce_css" value="1" <?php checked( isset( $css_options['disable_woocommerce_css'] ) && $css_options['disable_woocommerce_css'] ); ?>>
                </td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    
</div>
