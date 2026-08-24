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
                    <?php if ( ! empty( $css_options['modeb_enabled'] ) ) : ?>
                        <p class="description" style="color: var(--mbr-warning, #d6a700);">
                            <?php esc_html_e( 'Currently stood down: Mode B is enabled below, and it removes the stylesheets that Mode A would defer. The two are alternatives — enable one or the other, never both. Mode A is paused while Mode B is on.', 'mbr-performance' ); ?>
                        </p>
                    <?php endif; ?>
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

    <!-- Used CSS (Mode B) Section -->
    <div class="mbr-performance-section">
        <h2><?php esc_html_e( 'Used CSS — Mode B (aggressive)', 'mbr-performance' ); ?></h2>

        <p class="description">
            <?php esc_html_e( 'Mode B analyses each page template rather than each page, and it genuinely removes the stylesheets it has analysed instead of loading them behind the inlined CSS. That makes it the faster of the two — the bytes stop being downloaded at all — and the riskier, because a rule it wrongly drops has no full stylesheet arriving behind it to put things right. Enable it on staging first and click through every template before you use it on a live site.', 'mbr-performance' ); ?>
        </p>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="modeb_enabled">
                        <?php esc_html_e( 'Enable Mode B', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Per-template critical CSS that removes the analysed stylesheets. Replaces Mode A when enabled.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[css][modeb_enabled]" id="modeb_enabled" value="1" <?php checked( isset( $css_options['modeb_enabled'] ) && $css_options['modeb_enabled'] ); ?>>
                    <p class="description">
                        <?php esc_html_e( 'Each template — front page, single post, page, archive, shop, product — is learned from the first few URLs visited, and every later visit to that template is served the inlined result with the analysed stylesheets removed. A site with ten thousand posts keeps one cache entry for "single posts", not ten thousand.', 'mbr-performance' ); ?>
                    </p>
                    <p class="description">
                        <?php esc_html_e( 'A stylesheet is only ever removed from a page if that exact file was analysed while learning. Anything else — a plugin stylesheet that only appears on some pages, a sheet containing @import, print and narrow-media sheets, excluded sheets, external sheets — is left loading exactly as it does now.', 'mbr-performance' ); ?>
                    </p>
                </td>
            </tr>

            <tr class="mbr-performance-child-row">
                <th scope="row">
                    <label for="modeb_samples">
                        <?php esc_html_e( 'URLs sampled per template', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'How many distinct URLs teach each template before it is considered learned.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="number" name="mbrpe_options[css][modeb_samples]" id="modeb_samples" min="1" max="10" step="1" class="small-text" value="<?php echo esc_attr( isset( $css_options['modeb_samples'] ) ? (int) $css_options['modeb_samples'] : 3 ); ?>">
                    <p class="description">
                        <?php esc_html_e( 'Mode B keeps the sum of what every sampled URL used, so a rule that only one of your posts needs still survives for all of them. Raising this makes each template safer and slower to settle; lowering it does the reverse. Three suits most sites. Those first few visits per template render normally, with nothing removed.', 'mbr-performance' ); ?>
                    </p>
                </td>
            </tr>

            <tr class="mbr-performance-child-row">
                <th scope="row">
                    <label for="modeb_safelist">
                        <?php esc_html_e( 'Selector safelist', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Class names or prefixes always kept, even when absent from the page as delivered. One per line; end with * for a prefix.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <textarea name="mbrpe_options[css][modeb_safelist]" id="modeb_safelist" rows="4" class="large-text code" placeholder="cookie-banner&#10;cart-drawer&#10;mymodal-*"><?php echo isset( $css_options['modeb_safelist'] ) ? esc_textarea( $css_options['modeb_safelist'] ) : ''; ?></textarea>
                    <p class="description">
                        <?php esc_html_e( 'This is the guard rail. The analysis reads the page as your server delivers it, so anything JavaScript adds afterwards — a consent banner, a cart drawer, a modal, a lightbox — is invisible to it and its CSS looks unused. List those class names here, one per line, and end an entry with * to match a prefix. Common framework prefixes are already handled.', 'mbr-performance' ); ?>
                    </p>
                </td>
            </tr>

            <tr class="mbr-performance-child-row">
                <th scope="row">
                    <label for="modeb_keep_sheets">
                        <?php esc_html_e( 'Never remove these stylesheets', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Stylesheet URL fragments that stay loaded in full, one per line.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <textarea name="mbrpe_options[css][modeb_keep_sheets]" id="modeb_keep_sheets" rows="3" class="large-text code" placeholder="/plugins/my-slider/&#10;icons.css"><?php echo isset( $css_options['modeb_keep_sheets'] ) ? esc_textarea( $css_options['modeb_keep_sheets'] ) : ''; ?></textarea>
                    <p class="description">
                        <?php esc_html_e( 'Any stylesheet whose URL contains one of these fragments is left completely alone — not analysed, not removed. Use it for a stylesheet you would rather load in full than reason about. The exclusion list further up this tab is honoured here as well, so there is no need to repeat an entry.', 'mbr-performance' ); ?>
                    </p>
                </td>
            </tr>

            <tr class="mbr-performance-child-row">
                <th scope="row"><?php esc_html_e( 'Template cache', 'mbr-performance' ); ?></th>
                <td>
                    <?php
                    $mbr_b_rows  = class_exists( 'MBRPE_Used_CSS_Mode_B' ) ? MBRPE_Used_CSS_Mode_B::cache_status() : array();
                    $mbr_b_stats = class_exists( 'MBRPE_Used_CSS_Mode_B' )
                        ? MBRPE_Used_CSS_Mode_B::cache_stats()
                        : array( 'templates' => 0, 'learned' => 0, 'bytes' => 0 );
                    ?>
                    <?php if ( empty( $mbr_b_rows ) ) : ?>
                        <p class="description" id="mbr-modeb-empty">
                            <?php esc_html_e( 'No templates learned yet. Enable Mode B, save, then visit your site logged out — each template learns from the first few URLs you open, and starts being served optimised once it has enough samples.', 'mbr-performance' ); ?>
                        </p>
                    <?php else : ?>
                        <table class="widefat striped" id="mbr-modeb-table" style="max-width:760px;margin-bottom:10px;">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Template', 'mbr-performance' ); ?></th>
                                    <th><?php esc_html_e( 'Samples', 'mbr-performance' ); ?></th>
                                    <th><?php esc_html_e( 'Sheets replaced', 'mbr-performance' ); ?></th>
                                    <th><?php esc_html_e( 'Inlined size', 'mbr-performance' ); ?></th>
                                    <th><?php esc_html_e( 'Status', 'mbr-performance' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $mbr_b_rows as $mbr_b_row ) : ?>
                                    <tr>
                                        <td><strong><?php echo esc_html( $mbr_b_row['label'] ); ?></strong></td>
                                        <td><?php echo esc_html( sprintf( '%1$d / %2$d', $mbr_b_row['samples'], $mbr_b_row['target'] ) ); ?></td>
                                        <td><?php echo esc_html( number_format_i18n( $mbr_b_row['sheets'] ) ); ?></td>
                                        <td><?php echo esc_html( $mbr_b_row['bytes'] > 0 ? size_format( $mbr_b_row['bytes'] ) : '0 B' ); ?></td>
                                        <td>
                                            <?php if ( $mbr_b_row['samples'] >= $mbr_b_row['target'] ) : ?>
                                                <?php esc_html_e( 'Learned — serving', 'mbr-performance' ); ?>
                                            <?php else : ?>
                                                <?php esc_html_e( 'Still learning', 'mbr-performance' ); ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p class="description" style="margin-bottom:8px;">
                            <?php
                            printf(
                                /* translators: 1: templates learned, 2: templates seen, 3: total inlined size */
                                esc_html__( '%1$d of %2$d templates fully learned (%3$s inlined in total).', 'mbr-performance' ),
                                (int) $mbr_b_stats['learned'],
                                (int) $mbr_b_stats['templates'],
                                esc_html( $mbr_b_stats['bytes'] > 0 ? size_format( $mbr_b_stats['bytes'] ) : '0 B' )
                            );
                            ?>
                        </p>
                    <?php endif; ?>
                    <button type="button" class="button" id="mbr-clear-used-css-b"><?php esc_html_e( 'Clear template cache', 'mbr-performance' ); ?></button>
                    <span id="mbr-modeb-status"></span>
                    <p class="description">
                        <?php esc_html_e( 'The cache also clears itself whenever content is saved, the theme changes, or a plugin is updated, installed or removed — a template that has removed stylesheets must never outlive the markup it was measured against.', 'mbr-performance' ); ?>
                    </p>
                    <p class="description">
                        <?php esc_html_e( 'To compare a page against its original stylesheets, add ?mbrpe_modeb=off to its URL — that single request is served untouched.', 'mbr-performance' ); ?>
                    </p>
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
