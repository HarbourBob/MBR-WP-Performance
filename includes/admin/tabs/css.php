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
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Merges adjacent local stylesheets into a single cached file to cut requests. Cascade order is preserved; external, conditional and print stylesheets are left alone.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[css][combine_css]" id="combine_css" value="1" <?php checked( isset( $css_options['combine_css'] ) && $css_options['combine_css'] ); ?>>
                    <p class="description">
                        <?php esc_html_e( 'Concatenates runs of adjacent same-media local stylesheets into one cached bundle under /uploads/mbr-performance-combine/. Only same-origin files are merged; external, conditional, print and excluded stylesheets are left untouched so the cascade is preserved. If Minify CSS is also enabled, the bundle is minified.', 'mbr-performance' ); ?>
                    </p>
                    <?php if ( ! empty( $css_options['remove_unused_css'] ) ) : ?>
                        <p class="description" style="color: var(--mbr-warning, #d6a700);">
                            <?php esc_html_e( 'Currently stood down: Used CSS is enabled, and it already inlines the critical CSS and defers every stylesheet itself. Combine and Used CSS are alternatives — there is no benefit to running both, so Combine is paused while Used CSS is on.', 'mbr-performance' ); ?>
                        </p>
                    <?php endif; ?>
                </td>
            </tr>
            
            <tr class="mbr-performance-child-row">
                <th scope="row">
                    <label for="preload_combined_css">
                        <?php esc_html_e( 'Preload Combined CSS', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Adds an early preload hint so the browser fetches the combined stylesheet sooner. Ignored when Async CSS is enabled.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[css][preload_combined_css]" id="preload_combined_css" value="1" <?php checked( isset( $css_options['preload_combined_css'] ) && $css_options['preload_combined_css'] ); ?>>
                    <p class="description">
                        <?php esc_html_e( 'Emits a <link rel="preload" as="style"> hint in the document head for each combined bundle, so the browser starts downloading it before the parser reaches the stylesheet link. Has no effect unless Combine CSS is on, and is automatically skipped when Async CSS is enabled (which already preloads).', 'mbr-performance' ); ?>
                    </p>
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
            
            <tr class="mbr-performance-child-row">
                <th scope="row"><?php esc_html_e( 'Combined CSS cache', 'mbr-performance' ); ?></th>
                <td>
                    <?php
                    $mbr_combine_stats = class_exists( 'MBRPE_CSS_Optimizations' )
                        ? MBRPE_CSS_Optimizations::combine_cache_stats()
                        : array( 'css' => 0, 'css_bytes' => 0 );
                    $mbr_css_size = $mbr_combine_stats['css_bytes'] > 0 ? size_format( $mbr_combine_stats['css_bytes'] ) : '0 B';
                    ?>
                    <p class="description" style="margin-bottom:8px;">
                        <?php esc_html_e( 'Cached combined CSS files:', 'mbr-performance' ); ?>
                        <strong id="mbr-combine-css-count"><?php echo esc_html( number_format_i18n( $mbr_combine_stats['css'] ) ); ?></strong>
                        (<span id="mbr-combine-css-size"><?php echo esc_html( $mbr_css_size ); ?></span>)
                    </p>
                    <button type="button" class="button" id="mbr-clear-combine-css" data-cache-type="css"><?php esc_html_e( 'Clear combined CSS cache', 'mbr-performance' ); ?></button>
                    <span id="mbr-combine-css-status"></span>
                    <p class="description"><?php esc_html_e( 'Bundles also rebuild automatically when settings or stylesheets change.', 'mbr-performance' ); ?></p>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Used CSS (Mode A) Section -->
    <div class="mbr-performance-section">
        <h2><?php esc_html_e( 'Used CSS', 'mbr-performance' ); ?></h2>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="remove_unused_css">
                        <?php esc_html_e( 'Generate Used CSS', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Inlines only the CSS each page actually uses and loads the full stylesheets asynchronously as a fallback.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[css][remove_unused_css]" id="remove_unused_css" value="1" <?php checked( isset( $css_options['remove_unused_css'] ) && $css_options['remove_unused_css'] ); ?>>
                    <p class="description">
                        <?php esc_html_e( 'For each page, MBR Performance works out which CSS the delivered page actually uses, inlines just that in the head, and loads the full stylesheets asynchronously as a safety net — so unused CSS no longer blocks rendering, while anything added by JavaScript still applies a moment later. Used CSS is generated in the background after the first visit to each page, then served from cache.', 'mbr-performance' ); ?>
                    </p>
                    <p class="description">
                        <?php esc_html_e( 'Logged-in views are skipped. Test on a staging site first and check interactive elements (menus, sliders, popups). Use the exclusion list above to keep specific stylesheets loading normally.', 'mbr-performance' ); ?>
                    </p>
                </td>
            </tr>

            <tr class="mbr-performance-child-row">
                <th scope="row"><?php esc_html_e( 'Used CSS cache', 'mbr-performance' ); ?></th>
                <td>
                    <?php
                    $mbr_used_stats = class_exists( 'MBRPE_Used_CSS' )
                        ? MBRPE_Used_CSS::cache_stats()
                        : array( 'count' => 0, 'bytes' => 0 );
                    $mbr_used_size = $mbr_used_stats['bytes'] > 0 ? size_format( $mbr_used_stats['bytes'] ) : '0 B';
                    ?>
                    <p class="description" style="margin-bottom:8px;">
                        <?php esc_html_e( 'Cached used-CSS files:', 'mbr-performance' ); ?>
                        <strong id="mbr-used-css-count"><?php echo esc_html( number_format_i18n( $mbr_used_stats['count'] ) ); ?></strong>
                        (<span id="mbr-used-css-size"><?php echo esc_html( $mbr_used_size ); ?></span>)
                    </p>
                    <button type="button" class="button" id="mbr-clear-used-css"><?php esc_html_e( 'Clear used CSS cache', 'mbr-performance' ); ?></button>
                    <span id="mbr-used-css-status"></span>
                    <p class="description"><?php esc_html_e( 'Used CSS also regenerates automatically when settings change, a page is edited, or the theme/plugins update.', 'mbr-performance' ); ?></p>
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

    <?php
    // Critical CSS (XL). Rendered only when the module is present, so the lite
    // build (which omits the class) shows this tab unchanged.
    if ( class_exists( 'MBRPE_Critical_CSS' ) ) {
        MBRPE_Critical_CSS::render_settings( $css_options );
    }
    ?>

</div>
