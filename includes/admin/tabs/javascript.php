<?php
/**
 * JavaScript Tab
 *
 * @package MBRPE
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$js_options = isset( $options['javascript'] ) ? $options['javascript'] : array();
?>

<div class="mbr-performance-tab-content">
    
    <!-- Script Loading Strategy Section -->
    <div class="mbr-performance-section">
        <h2><?php esc_html_e( 'Script Loading Strategy', 'mbr-performance' ); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="defer_javascript">
                        <?php esc_html_e( 'Defer JavaScript', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Delays script execution until HTML is fully parsed - Recommended for better performance', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[javascript][defer_javascript]" id="defer_javascript" value="1" <?php checked( isset( $js_options['defer_javascript'] ) && $js_options['defer_javascript'] ); ?>>
                </td>
            </tr>
            
            <tr class="mbr-performance-child-row">
                <th scope="row">
                    <label for="exclude_defer">
                        <?php esc_html_e( 'Exclude Scripts from Defer', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Enter script URLs or handles to exclude from defer, one per line.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <textarea name="mbrpe_options[javascript][exclude_defer]" id="exclude_defer" rows="4" class="large-text code" placeholder="jquery-core&#10;/path/to/script.js"><?php echo isset( $js_options['exclude_defer'] ) ? esc_textarea( $js_options['exclude_defer'] ) : ''; ?></textarea>
                    <p class="description"><?php esc_html_e( 'Enter script URLs or handles, one per line. e.g., jquery-core, /path/to/script.js', 'mbr-performance' ); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="move_scripts_footer">
                        <?php esc_html_e( 'Move Scripts to Footer', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Prevents render-blocking - Recommended for faster initial page load', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[javascript][move_scripts_footer]" id="move_scripts_footer" value="1" <?php checked( isset( $js_options['move_scripts_footer'] ) && $js_options['move_scripts_footer'] ); ?>>
                </td>
            </tr>
            
            <tr class="mbr-performance-child-row">
                <th scope="row">
                    <label for="exclude_footer">
                        <?php esc_html_e( 'Exclude Scripts from Footer', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Scripts that must stay in head, one per line', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <textarea name="mbrpe_options[javascript][exclude_footer]" id="exclude_footer" rows="4" class="large-text code"><?php echo isset( $js_options['exclude_footer'] ) ? esc_textarea( $js_options['exclude_footer'] ) : ''; ?></textarea>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- jQuery Optimization Section -->
    <div class="mbr-performance-section">
        <h2><?php esc_html_e( 'jQuery Optimization', 'mbr-performance' ); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="defer_jquery">
                        <?php esc_html_e( 'Defer jQuery', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Apply defer to jQuery specifically', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[javascript][defer_jquery]" id="defer_jquery" value="1" <?php checked( isset( $js_options['defer_jquery'] ) && $js_options['defer_jquery'] ); ?>>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="remove_jquery">
                        <?php esc_html_e( 'Remove jQuery', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'WARNING: Will break most plugins and themes. Use test mode to test safely.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[javascript][remove_jquery]" id="remove_jquery" value="1" <?php checked( isset( $js_options['remove_jquery'] ) && $js_options['remove_jquery'] ); ?>>
                </td>
            </tr>
            
            <tr class="mbr-performance-child-row">
                <th scope="row">
                    <label for="jquery_test_mode">
                        <?php esc_html_e( 'Test Mode', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Only removes jQuery for logged-out users - test safely before full deployment', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[javascript][jquery_test_mode]" id="jquery_test_mode" value="1" <?php checked( isset( $js_options['jquery_test_mode'] ) && $js_options['jquery_test_mode'] ); ?>>
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
                    <label for="minify_javascript">
                        <?php esc_html_e( 'Minify JavaScript', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Removes whitespace and comments to reduce file size', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[javascript][minify_javascript]" id="minify_javascript" value="1" <?php checked( isset( $js_options['minify_javascript'] ) && $js_options['minify_javascript'] ); ?>>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="combine_javascript">
                        <?php esc_html_e( 'Combine JavaScript Files', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Merges adjacent local scripts into a single cached file to cut requests. Only scripts with no inline/localized data are combined; execution order is preserved.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[javascript][combine_javascript]" id="combine_javascript" value="1" <?php checked( isset( $js_options['combine_javascript'] ) && $js_options['combine_javascript'] ); ?>>
                    <p class="description">
                        <?php esc_html_e( 'Concatenates runs of adjacent same-group local scripts into one cached bundle under /uploads/mbr-performance-combine/. For safety, only "pure" scripts are merged — any script carrying inline or localized data (which often includes per-request nonces), an async/defer strategy, or that is external or excluded, breaks the run and is left untouched. Scripts in your Defer or Delay lists are also left alone so those features keep working.', 'mbr-performance' ); ?>
                    </p>
                </td>
            </tr>
            
            <tr class="mbr-performance-child-row">
                <th scope="row">
                    <label for="exclude_optimization">
                        <?php esc_html_e( 'Exclude from Minification/Combination', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Script handles or URLs to exclude from optimization, one per line', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <textarea name="mbrpe_options[javascript][exclude_optimization]" id="exclude_optimization" rows="4" class="large-text code"><?php echo isset( $js_options['exclude_optimization'] ) ? esc_textarea( $js_options['exclude_optimization'] ) : ''; ?></textarea>
                </td>
            </tr>

            <tr class="mbr-performance-child-row">
                <th scope="row"><?php esc_html_e( 'Combined JS cache', 'mbr-performance' ); ?></th>
                <td>
                    <?php
                    $mbr_combine_stats = class_exists( 'MBRPE_CSS_Optimizations' )
                        ? MBRPE_CSS_Optimizations::combine_cache_stats()
                        : array( 'js' => 0, 'js_bytes' => 0 );
                    $mbr_js_size = $mbr_combine_stats['js_bytes'] > 0 ? size_format( $mbr_combine_stats['js_bytes'] ) : '0 B';
                    ?>
                    <p class="description" style="margin-bottom:8px;">
                        <?php esc_html_e( 'Cached combined JS files:', 'mbr-performance' ); ?>
                        <strong id="mbr-combine-js-count"><?php echo esc_html( number_format_i18n( $mbr_combine_stats['js'] ) ); ?></strong>
                        (<span id="mbr-combine-js-size"><?php echo esc_html( $mbr_js_size ); ?></span>)
                    </p>
                    <button type="button" class="button" id="mbr-clear-combine-js" data-cache-type="js"><?php esc_html_e( 'Clear combined JS cache', 'mbr-performance' ); ?></button>
                    <span id="mbr-combine-js-status"></span>
                    <p class="description"><?php esc_html_e( 'Bundles also rebuild automatically when settings or scripts change.', 'mbr-performance' ); ?></p>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Delayed Script Loading Section -->
    <div class="mbr-performance-section">
        <h2><?php esc_html_e( 'Delayed Script Loading', 'mbr-performance' ); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="delay_javascript">
                        <?php esc_html_e( 'Delay JavaScript Execution', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Delays non-critical scripts until user interaction (click, scroll, touch, or mouse move). Improves initial page load significantly.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[javascript][delay_javascript]" id="delay_javascript" value="1" <?php checked( isset( $js_options['delay_javascript'] ) && $js_options['delay_javascript'] ); ?>>
                </td>
            </tr>
            
            <tr class="mbr-performance-child-row">
                <th scope="row">
                    <label for="delay_timeout">
                        <?php esc_html_e( 'Delay Timeout', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Auto-execute delayed scripts after timeout, even without interaction', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <select name="mbrpe_options[javascript][delay_timeout]" id="delay_timeout">
                        <option value="3" <?php selected( isset( $js_options['delay_timeout'] ) ? $js_options['delay_timeout'] : 3, 3 ); ?>><?php esc_html_e( '3 Seconds (Recommended)', 'mbr-performance' ); ?></option>
                        <option value="5" <?php selected( isset( $js_options['delay_timeout'] ) ? $js_options['delay_timeout'] : 3, 5 ); ?>><?php esc_html_e( '5 Seconds', 'mbr-performance' ); ?></option>
                        <option value="10" <?php selected( isset( $js_options['delay_timeout'] ) ? $js_options['delay_timeout'] : 3, 10 ); ?>><?php esc_html_e( '10 Seconds', 'mbr-performance' ); ?></option>
                        <option value="0" <?php selected( isset( $js_options['delay_timeout'] ) ? $js_options['delay_timeout'] : 3, 0 ); ?>><?php esc_html_e( 'No Timeout (Wait for interaction)', 'mbr-performance' ); ?></option>
                    </select>
                </td>
            </tr>
            
            <tr class="mbr-performance-child-row">
                <th scope="row">
                    <label for="delay_scripts">
                        <?php esc_html_e( 'Scripts to Delay', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Common analytics and tracking scripts. Add one script identifier per line. These scripts will load after user interaction.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <textarea name="mbrpe_options[javascript][delay_scripts]" id="delay_scripts" rows="8" class="large-text code" placeholder="gtag&#10;fbevents&#10;google-analytics&#10;analytics.js&#10;gtm.js&#10;_gaq&#10;ga.js"><?php echo isset( $js_options['delay_scripts'] ) ? esc_textarea( $js_options['delay_scripts'] ) : "gtag\nfbevents\ngoogle-analytics\nanalytics.js\ngtm.js\n_gaq\nga.js"; ?></textarea>
                    <p class="description"><?php esc_html_e( 'Add one script identifier per line. These scripts will load after user interaction.', 'mbr-performance' ); ?></p>
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
                    <label for="remove_script_versions">
                        <?php esc_html_e( 'Remove Script Versions', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Removes ?ver= from script URLs for better caching', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[javascript][remove_script_versions]" id="remove_script_versions" value="1" <?php checked( isset( $js_options['remove_script_versions'] ) && $js_options['remove_script_versions'] ); ?>>
                </td>
            </tr>
        </table>
    </div>
    
</div>
