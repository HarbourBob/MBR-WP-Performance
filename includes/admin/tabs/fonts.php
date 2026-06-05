<?php
/**
 * Fonts Tab
 *
 * @package MBRPE
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$font_options = isset( $options['fonts'] ) ? $options['fonts'] : array();
?>

<div class="mbr-performance-tab-content">
    
    <!-- Font Preloading Section -->
    <div class="mbr-performance-section">
        <h2><?php esc_html_e( 'Font Preloading', 'mbr-performance' ); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="preload_fonts">
                        <?php esc_html_e( 'Preload Critical Fonts', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Loads essential fonts early in page load - Recommended', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[fonts][preload_fonts]" id="preload_fonts" value="1" <?php checked( isset( $font_options['preload_fonts'] ) && $font_options['preload_fonts'] ); ?>>
                </td>
            </tr>
            
            <tr class="mbr-performance-child-row">
                <th scope="row">
                    <label for="preload_font_urls">
                        <?php esc_html_e( 'Font URLs to Preload', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Enter font file URLs, one per line. Use WOFF2 format for best performance.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <textarea name="mbrpe_options[fonts][preload_font_urls]" id="preload_font_urls" rows="4" class="large-text code" placeholder="/wp-content/themes/your-theme/fonts/primary-font.woff2"><?php echo isset( $font_options['preload_font_urls'] ) ? esc_textarea( $font_options['preload_font_urls'] ) : ''; ?></textarea>
                    <p class="description"><?php esc_html_e( 'Example: /wp-content/themes/your-theme/fonts/primary-font.woff2', 'mbr-performance' ); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="font_display">
                        <?php esc_html_e( 'Font Display Strategy', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Global font display behavior', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <select name="mbrpe_options[fonts][font_display]" id="font_display">
                        <option value="swap" <?php selected( isset( $font_options['font_display'] ) ? $font_options['font_display'] : 'swap', 'swap' ); ?>><?php esc_html_e( 'swap (Recommended - Shows text immediately with fallback)', 'mbr-performance' ); ?></option>
                        <option value="block" <?php selected( isset( $font_options['font_display'] ) ? $font_options['font_display'] : 'swap', 'block' ); ?>><?php esc_html_e( 'block (Invisible text up to 3s while font loads)', 'mbr-performance' ); ?></option>
                        <option value="fallback" <?php selected( isset( $font_options['font_display'] ) ? $font_options['font_display'] : 'swap', 'fallback' ); ?>><?php esc_html_e( 'fallback (Brief invisible period, then fallback)', 'mbr-performance' ); ?></option>
                        <option value="optional" <?php selected( isset( $font_options['font_display'] ) ? $font_options['font_display'] : 'swap', 'optional' ); ?>><?php esc_html_e( 'optional (Only use font if already cached)', 'mbr-performance' ); ?></option>
                        <option value="auto" <?php selected( isset( $font_options['font_display'] ) ? $font_options['font_display'] : 'swap', 'auto' ); ?>><?php esc_html_e( 'auto (Browser decides)', 'mbr-performance' ); ?></option>
                    </select>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Google Fonts Section -->
    <div class="mbr-performance-section">
        <h2><?php esc_html_e( 'Google Fonts', 'mbr-performance' ); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="self_host_google_fonts">
                        <?php esc_html_e( 'Self-Host Google Fonts', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Downloads Google Fonts and serves them locally for better performance and privacy', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[fonts][self_host_google_fonts]" id="self_host_google_fonts" value="1" <?php checked( isset( $font_options['self_host_google_fonts'] ) && $font_options['self_host_google_fonts'] ); ?>>
                    
                    <?php
                    $local_fonts = get_option( 'mbrpe_local_fonts', array() );
                    if ( ! empty( $local_fonts ) ) {
                        echo '<p class="description" style="margin-top: 15px;"><strong>' . esc_html__( 'Currently Downloaded Fonts:', 'mbr-performance' ) . '</strong><br>';
                        foreach ( $local_fonts as $family => $variants ) {
                            echo '• ' . esc_html( $family ) . ' (' . esc_html( implode( ', ', $variants ) ) . ')<br>';
                        }
                        echo '</p>';
                    }
                    ?>
                    
                    <hr>
                    
                    <p><strong><?php esc_html_e( 'Add Fonts Manually', 'mbr-performance' ); ?></strong></p>
                    <p class="description">
                        <?php esc_html_e( 'Enter the Google Fonts you want to download and serve locally. One font per line.', 'mbr-performance' ); ?>
                        <br>
                        <strong><?php esc_html_e( 'Format:', 'mbr-performance' ); ?></strong> <code>Font Family:weights</code>
                        <br>
                        <strong><?php esc_html_e( 'Examples:', 'mbr-performance' ); ?></strong>
                        <br>&bull; <code>Open Sans:400,700</code> <em>(<?php esc_html_e( 'regular and bold', 'mbr-performance' ); ?>)</em>
                        <br>&bull; <code>Roboto:300,400,500</code> <em>(<?php esc_html_e( 'light, regular, and medium', 'mbr-performance' ); ?>)</em>
                        <br>&bull; <code>Poppins</code> <em>(<?php esc_html_e( 'regular weight only', 'mbr-performance' ); ?>)</em>
                        <br><span style="color: #00a32a;">✓ <?php esc_html_e( 'Font names are case-insensitive', 'mbr-performance' ); ?></span>
                    </p>
                    <textarea name="mbrpe_options[fonts][manual_fonts]" id="manual_fonts" rows="5" class="large-text code" placeholder="Open Sans:400,700&#10;Roboto:300,400,500&#10;Poppins"><?php echo isset( $font_options['manual_fonts'] ) ? esc_textarea( $font_options['manual_fonts'] ) : ''; ?></textarea>
                    <p>
                        <button type="button" class="button button-primary" id="download-manual-fonts"><?php esc_html_e( 'Download Fonts', 'mbr-performance' ); ?></button>
                    </p>
                    <p id="manual-font-status" class="description"></p>
                    
                    <hr>
                    
                    <p><strong style="color: #d63638;"><?php esc_html_e( 'Clear Font Cache', 'mbr-performance' ); ?></strong></p>
                    <p class="description">
                        <?php esc_html_e( 'Delete all downloaded font files and reset font configuration. Use this to start fresh if you have unwanted fonts loading.', 'mbr-performance' ); ?>
                    </p>
                    <p>
                        <button type="button" class="button button-secondary" id="clear-font-cache" style="color: #d63638; border-color: #d63638;">
                            <?php esc_html_e( 'Clear All Fonts', 'mbr-performance' ); ?>
                        </button>
                    </p>
                    <p id="clear-font-status" class="description"></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="preload_local_fonts">
                        <?php esc_html_e( 'Preload Local Fonts', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Preloads self-hosted font files for faster rendering. Recommended for critical fonts used above the fold.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[fonts][preload_local_fonts]" id="preload_local_fonts" value="1" <?php checked( isset( $font_options['preload_local_fonts'] ) && $font_options['preload_local_fonts'] ); ?>>
                    <p class="description"><?php esc_html_e( 'Adds &lt;link rel="preload"&gt; tags for all self-hosted fonts. This speeds up initial page load significantly.', 'mbr-performance' ); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="disable_google_fonts">
                        <?php esc_html_e( 'Disable Google Fonts', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Removes all Google Fonts from your site', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[fonts][disable_google_fonts]" id="disable_google_fonts" value="1" <?php checked( isset( $font_options['disable_google_fonts'] ) && $font_options['disable_google_fonts'] ); ?>>
                </td>
            </tr>
            
            <tr class="mbr-performance-child-row">
                <th scope="row">
                    <label for="fallback_fonts">
                        <?php esc_html_e( 'Fallback Font Stack', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Used if Google Fonts fail to load or are disabled', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="text" name="mbrpe_options[fonts][fallback_fonts]" id="fallback_fonts" class="large-text" value="<?php echo isset( $font_options['fallback_fonts'] ) ? esc_attr( $font_options['fallback_fonts'] ) : '-apple-system, BlinkMacSystemFont, &quot;Segoe UI&quot;, Roboto, Oxygen-Sans, Ubuntu, Cantarell, &quot;Helvetica Neue&quot;, sans-serif'; ?>">
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Google Fonts Optimization Section -->
    <div class="mbr-performance-section">
        <h2><?php esc_html_e( 'Google Fonts Optimization', 'mbr-performance' ); ?></h2>

        <table class="form-table">
            <?php if ( defined( 'ELEMENTOR_VERSION' ) ) : ?>
            <tr>
                <th scope="row">
                    <label for="disable_elementor_fonts">
                        <?php esc_html_e( 'Disable Elementor Google Fonts', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Prevents Elementor from loading Google Fonts', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[fonts][disable_elementor_fonts]" id="disable_elementor_fonts" value="1" <?php checked( isset( $font_options['disable_elementor_fonts'] ) && $font_options['disable_elementor_fonts'] ); ?>>
                </td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    
    <!-- Font Subsetting Section -->
    <div class="mbr-performance-section">
        <h2><?php esc_html_e( 'Font Subsetting', 'mbr-performance' ); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="enable_font_subsetting">
                        <?php esc_html_e( 'Enable Font Subsetting', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Loads only required characters to reduce file size', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[fonts][enable_font_subsetting]" id="enable_font_subsetting" value="1" <?php checked( isset( $font_options['enable_font_subsetting'] ) && $font_options['enable_font_subsetting'] ); ?>>
                    <p class="description"><?php esc_html_e( 'Only applies to self-hosted Google Fonts. Smaller subsets = faster loading.', 'mbr-performance' ); ?></p>
                </td>
            </tr>
            
            <tr class="mbr-performance-child-row">
                <th scope="row">
                    <?php esc_html_e( 'Character Sets to Include', 'mbr-performance' ); ?>
                    <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Select which character sets to include in fonts', 'mbr-performance' ); ?>">?</span>
                </th>
                <td>
                    <?php
                    $character_sets = array(
                        'latin' => __( 'Latin (Western European)', 'mbr-performance' ),
                        'latin-ext' => __( 'Latin Extended (Central/Eastern European)', 'mbr-performance' ),
                        'cyrillic' => __( 'Cyrillic (Russian, Ukrainian, etc.)', 'mbr-performance' ),
                        'greek' => __( 'Greek', 'mbr-performance' ),
                        'vietnamese' => __( 'Vietnamese', 'mbr-performance' ),
                        'arabic' => __( 'Arabic', 'mbr-performance' ),
                        'hebrew' => __( 'Hebrew', 'mbr-performance' ),
                    );
                    
                    $selected_sets = isset( $font_options['character_sets'] ) ? $font_options['character_sets'] : array( 'latin' );
                    
                    foreach ( $character_sets as $key => $label ) {
                        $checked = in_array( $key, (array) $selected_sets ) ? 'checked' : '';
                        printf(
                            '<label><input type="checkbox" name="mbrpe_options[fonts][character_sets][]" value="%s" %s> %s</label><br>',
                            esc_attr( $key ),
                            esc_attr( $checked ),
                            esc_html( $label )
                        );
                    }
                    ?>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- External Font Optimization Section -->
    <div class="mbr-performance-section">
        <h2><?php esc_html_e( 'External Font Optimization', 'mbr-performance' ); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="preconnect_domains">
                        <?php esc_html_e( 'Preconnect to Font Domains', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Establishes early connections to external font servers', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[fonts][preconnect_domains]" id="preconnect_domains" value="1" <?php checked( isset( $font_options['preconnect_domains'] ) && $font_options['preconnect_domains'] ); ?>>
                </td>
            </tr>
            
            <tr class="mbr-performance-child-row">
                <th scope="row">
                    <label for="font_domains">
                        <?php esc_html_e( 'Font Domains', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'One domain per line', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <textarea name="mbrpe_options[fonts][font_domains]" id="font_domains" rows="4" class="large-text code" placeholder="fonts.googleapis.com&#10;fonts.gstatic.com&#10;use.typekit.net"><?php echo isset( $font_options['font_domains'] ) ? esc_textarea( $font_options['font_domains'] ) : ''; ?></textarea>
                    <p class="description"><?php esc_html_e( 'Example: fonts.googleapis.com', 'mbr-performance' ); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="dns_prefetch">
                        <?php esc_html_e( 'DNS Prefetch for Font Domains', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Resolves DNS for font domains early. Automatically enables for domains listed above.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[fonts][dns_prefetch]" id="dns_prefetch" value="1" <?php checked( isset( $font_options['dns_prefetch'] ) && $font_options['dns_prefetch'] ); ?>>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Icon Fonts Section -->
    <div class="mbr-performance-section">
        <h2><?php esc_html_e( 'Icon Fonts', 'mbr-performance' ); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="disable_font_awesome">
                        <?php esc_html_e( 'Disable Font Awesome', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Only if Font Awesome is not used on your site', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[fonts][disable_font_awesome]" id="disable_font_awesome" value="1" <?php checked( isset( $font_options['disable_font_awesome'] ) && $font_options['disable_font_awesome'] ); ?>>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="async_font_awesome">
                        <?php esc_html_e( 'Load Font Awesome Asynchronously', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Non-blocking Font Awesome loading', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[fonts][async_font_awesome]" id="async_font_awesome" value="1" <?php checked( isset( $font_options['async_font_awesome'] ) && $font_options['async_font_awesome'] ); ?>>
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
                    <label for="disable_local_fallback">
                        <?php esc_html_e( 'Disable Local Font Fallback', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Prevents browsers from using local font versions', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[fonts][disable_local_fallback]" id="disable_local_fallback" value="1" <?php checked( isset( $font_options['disable_local_fallback'] ) && $font_options['disable_local_fallback'] ); ?>>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="remove_font_display">
                        <?php esc_html_e( 'Remove Font Display Attribute', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Removes font-display if causing issues', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[fonts][remove_font_display]" id="remove_font_display" value="1" <?php checked( isset( $font_options['remove_font_display'] ) && $font_options['remove_font_display'] ); ?>>
                </td>
            </tr>
        </table>
    </div>
    
</div>
