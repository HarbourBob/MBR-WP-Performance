<?php
/**
 * JavaScript Tab
 *
 * @package MBR_WP_Performance
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$js_options = isset( $options['javascript'] ) ? $options['javascript'] : array();
?>

<div class="mbr-wp-performance-tab-content">
    
    <!-- Script Loading Strategy Section -->
    <div class="mbr-wp-performance-section">
        <h2><?php esc_html_e( 'Script Loading Strategy', 'mbr-wp-performance' ); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="defer_javascript">
                        <?php esc_html_e( 'Defer JavaScript', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Delays script execution until HTML is fully parsed - Recommended for better performance', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[javascript][defer_javascript]" id="defer_javascript" value="1" <?php checked( isset( $js_options['defer_javascript'] ) && $js_options['defer_javascript'] ); ?>>
                </td>
            </tr>
            
            <tr class="mbr-wp-performance-child-row">
                <th scope="row">
                    <label for="exclude_defer">
                        <?php esc_html_e( 'Exclude Scripts from Defer', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Enter script URLs or handles to exclude from defer, one per line.', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <textarea name="mbr_wp_performance_options[javascript][exclude_defer]" id="exclude_defer" rows="4" class="large-text code" placeholder="jquery-core&#10;/path/to/script.js"><?php echo isset( $js_options['exclude_defer'] ) ? esc_textarea( $js_options['exclude_defer'] ) : ''; ?></textarea>
                    <p class="description"><?php esc_html_e( 'Enter script URLs or handles, one per line. e.g., jquery-core, /path/to/script.js', 'mbr-wp-performance' ); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="move_scripts_footer">
                        <?php esc_html_e( 'Move Scripts to Footer', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Prevents render-blocking - Recommended for faster initial page load', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[javascript][move_scripts_footer]" id="move_scripts_footer" value="1" <?php checked( isset( $js_options['move_scripts_footer'] ) && $js_options['move_scripts_footer'] ); ?>>
                </td>
            </tr>
            
            <tr class="mbr-wp-performance-child-row">
                <th scope="row">
                    <label for="exclude_footer">
                        <?php esc_html_e( 'Exclude Scripts from Footer', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Scripts that must stay in head, one per line', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <textarea name="mbr_wp_performance_options[javascript][exclude_footer]" id="exclude_footer" rows="4" class="large-text code"><?php echo isset( $js_options['exclude_footer'] ) ? esc_textarea( $js_options['exclude_footer'] ) : ''; ?></textarea>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- jQuery Optimization Section -->
    <div class="mbr-wp-performance-section">
        <h2><?php esc_html_e( 'jQuery Optimization', 'mbr-wp-performance' ); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="defer_jquery">
                        <?php esc_html_e( 'Defer jQuery', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Apply defer to jQuery specifically', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[javascript][defer_jquery]" id="defer_jquery" value="1" <?php checked( isset( $js_options['defer_jquery'] ) && $js_options['defer_jquery'] ); ?>>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="remove_jquery">
                        <?php esc_html_e( 'Remove jQuery', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'WARNING: Will break most plugins and themes. Use test mode to test safely.', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[javascript][remove_jquery]" id="remove_jquery" value="1" <?php checked( isset( $js_options['remove_jquery'] ) && $js_options['remove_jquery'] ); ?>>
                </td>
            </tr>
            
            <tr class="mbr-wp-performance-child-row">
                <th scope="row">
                    <label for="jquery_test_mode">
                        <?php esc_html_e( 'Test Mode', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Only removes jQuery for logged-out users - test safely before full deployment', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[javascript][jquery_test_mode]" id="jquery_test_mode" value="1" <?php checked( isset( $js_options['jquery_test_mode'] ) && $js_options['jquery_test_mode'] ); ?>>
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
                    <label for="minify_javascript">
                        <?php esc_html_e( 'Minify JavaScript', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Removes whitespace and comments to reduce file size', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[javascript][minify_javascript]" id="minify_javascript" value="1" <?php checked( isset( $js_options['minify_javascript'] ) && $js_options['minify_javascript'] ); ?>>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="combine_javascript">
                        <?php esc_html_e( 'Combine JavaScript Files', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'WARNING: Merges multiple JS files - May cause conflicts', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[javascript][combine_javascript]" id="combine_javascript" value="1" <?php checked( isset( $js_options['combine_javascript'] ) && $js_options['combine_javascript'] ); ?>>
                </td>
            </tr>
            
            <tr class="mbr-wp-performance-child-row">
                <th scope="row">
                    <label for="exclude_optimization">
                        <?php esc_html_e( 'Exclude from Minification/Combination', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Script handles or URLs to exclude from optimization, one per line', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <textarea name="mbr_wp_performance_options[javascript][exclude_optimization]" id="exclude_optimization" rows="4" class="large-text code"><?php echo isset( $js_options['exclude_optimization'] ) ? esc_textarea( $js_options['exclude_optimization'] ) : ''; ?></textarea>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Delayed Script Loading Section -->
    <div class="mbr-wp-performance-section">
        <h2><?php esc_html_e( 'Delayed Script Loading', 'mbr-wp-performance' ); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="delay_javascript">
                        <?php esc_html_e( 'Delay JavaScript Execution', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Delays non-critical scripts until user interaction (click, scroll, touch, or mouse move). Improves initial page load significantly.', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[javascript][delay_javascript]" id="delay_javascript" value="1" <?php checked( isset( $js_options['delay_javascript'] ) && $js_options['delay_javascript'] ); ?>>
                </td>
            </tr>
            
            <tr class="mbr-wp-performance-child-row">
                <th scope="row">
                    <label for="delay_timeout">
                        <?php esc_html_e( 'Delay Timeout', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Auto-execute delayed scripts after timeout, even without interaction', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <select name="mbr_wp_performance_options[javascript][delay_timeout]" id="delay_timeout">
                        <option value="3" <?php selected( isset( $js_options['delay_timeout'] ) ? $js_options['delay_timeout'] : 3, 3 ); ?>><?php esc_html_e( '3 Seconds (Recommended)', 'mbr-wp-performance' ); ?></option>
                        <option value="5" <?php selected( isset( $js_options['delay_timeout'] ) ? $js_options['delay_timeout'] : 3, 5 ); ?>><?php esc_html_e( '5 Seconds', 'mbr-wp-performance' ); ?></option>
                        <option value="10" <?php selected( isset( $js_options['delay_timeout'] ) ? $js_options['delay_timeout'] : 3, 10 ); ?>><?php esc_html_e( '10 Seconds', 'mbr-wp-performance' ); ?></option>
                        <option value="0" <?php selected( isset( $js_options['delay_timeout'] ) ? $js_options['delay_timeout'] : 3, 0 ); ?>><?php esc_html_e( 'No Timeout (Wait for interaction)', 'mbr-wp-performance' ); ?></option>
                    </select>
                </td>
            </tr>
            
            <tr class="mbr-wp-performance-child-row">
                <th scope="row">
                    <label for="delay_scripts">
                        <?php esc_html_e( 'Scripts to Delay', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Common analytics and tracking scripts. Add one script identifier per line. These scripts will load after user interaction.', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <textarea name="mbr_wp_performance_options[javascript][delay_scripts]" id="delay_scripts" rows="8" class="large-text code" placeholder="gtag&#10;fbevents&#10;google-analytics&#10;analytics.js&#10;gtm.js&#10;_gaq&#10;ga.js"><?php echo isset( $js_options['delay_scripts'] ) ? esc_textarea( $js_options['delay_scripts'] ) : "gtag\nfbevents\ngoogle-analytics\nanalytics.js\ngtm.js\n_gaq\nga.js"; ?></textarea>
                    <p class="description"><?php esc_html_e( 'Add one script identifier per line. These scripts will load after user interaction.', 'mbr-wp-performance' ); ?></p>
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
                    <label for="disable_concatenation">
                        <?php esc_html_e( 'Disable Concatenation', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Prevents WordPress from combining scripts - may improve compatibility', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[javascript][disable_concatenation]" id="disable_concatenation" value="1" <?php checked( isset( $js_options['disable_concatenation'] ) && $js_options['disable_concatenation'] ); ?>>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="remove_script_versions">
                        <?php esc_html_e( 'Remove Script Versions', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Removes ?ver= from script URLs for better caching', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[javascript][remove_script_versions]" id="remove_script_versions" value="1" <?php checked( isset( $js_options['remove_script_versions'] ) && $js_options['remove_script_versions'] ); ?>>
                </td>
            </tr>
        </table>
    </div>
    
</div>
