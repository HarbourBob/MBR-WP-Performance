<?php
/**
 * Preloading Settings Tab
 *
 * @package MBRPE
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$options = get_option( 'mbrpe_options', array() );
$preload_options = isset( $options['preloading'] ) ? $options['preloading'] : array();
?>

<div class="mbr-section">
    <h2><?php esc_html_e( 'Preloading Settings', 'mbr-performance' ); ?></h2>
    
    <table class="form-table">
        <tbody>
            <!-- Preload Critical Images -->
            <tr>
                <th scope="row">
                    <label for="preload_critical_images_count">
                        <?php esc_html_e( 'Preload Critical Images', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Number of images to preload (usually above the fold). 0 = disabled.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <select name="mbrpe_options[preloading][preload_critical_images_count]" id="preload_critical_images_count">
                        <?php
                        $count = isset( $preload_options['preload_critical_images_count'] ) ? $preload_options['preload_critical_images_count'] : 0;
                        for ( $i = 0; $i <= 5; $i++ ) {
                            printf(
                                '<option value="%d"%s>%s</option>',
                                esc_attr( $i ),
                                selected( $count, $i, false ),
                                esc_html( $i === 0 ? '0 (Default)' : $i )
                            );
                        }
                        ?>
                    </select>
                    
                    <div id="preload-images-list" style="margin-top: 15px;">
                        <?php
                        $preload_images = isset( $preload_options['preload_images'] ) ? $preload_options['preload_images'] : array();
                        if ( ! empty( $preload_images ) ) {
                            foreach ( $preload_images as $index => $image ) {
                                ?>
                                <div class="preload-image-row" style="margin-bottom: 10px;">
                                    <input type="text" name="mbrpe_options[preloading][preload_images][]" value="<?php echo esc_attr( $image ); ?>" placeholder="https://example.com/image.jpg" class="regular-text">
                                    <button type="button" class="button remove-preload-image">Remove</button>
                                </div>
                                <?php
                            }
                        }
                        ?>
                    </div>
                    <button type="button" class="button" id="add-preload-image"><?php esc_html_e( 'Add New', 'mbr-performance' ); ?></button>
                    <p class="description"><?php esc_html_e( 'Enter image URLs to preload (LCP images, hero images, etc.)', 'mbr-performance' ); ?></p>
                </td>
            </tr>
            
            <!-- Cloudflare Early Hints -->
            <tr>
                <th scope="row">
                    <label for="cloudflare_early_hints">
                        <?php esc_html_e( 'Cloudflare Early Hints', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Send 103 Early Hints header for faster resource loading (requires Cloudflare)', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[preloading][cloudflare_early_hints]" id="cloudflare_early_hints" value="1" <?php checked( isset( $preload_options['cloudflare_early_hints'] ) && $preload_options['cloudflare_early_hints'] ); ?>>
                    <p class="description"><?php esc_html_e( 'Enables HTTP 103 Early Hints for Cloudflare-hosted sites.', 'mbr-performance' ); ?></p>
                </td>
            </tr>
            
            <!-- Fetch Priority -->
            <tr>
                <th scope="row">
                    <label for="fetch_priority">
                        <?php esc_html_e( 'Fetch Priority', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Add fetchpriority="high" to critical images for faster LCP', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[preloading][fetch_priority]" id="fetch_priority" value="1" <?php checked( isset( $preload_options['fetch_priority'] ) && $preload_options['fetch_priority'] ); ?>>
                    <p class="description"><?php esc_html_e( 'Automatically adds fetchpriority="high" to the first image on each page.', 'mbr-performance' ); ?></p>
                    
                    <div style="margin-top: 15px;">
                        <label><?php esc_html_e( 'Custom Fetch Priority Selectors:', 'mbr-performance' ); ?></label>
                        <div id="fetch-priority-list">
                            <?php
                            $fetch_priority_selectors = isset( $preload_options['fetch_priority_selectors'] ) ? $preload_options['fetch_priority_selectors'] : array();
                            if ( ! empty( $fetch_priority_selectors ) ) {
                                foreach ( $fetch_priority_selectors as $selector ) {
                                    ?>
                                    <div class="fetch-priority-row" style="margin-bottom: 10px;">
                                        <input type="text" name="mbrpe_options[preloading][fetch_priority_selectors][]" value="<?php echo esc_attr( $selector ); ?>" placeholder=".hero-image, #main-banner img" class="regular-text">
                                        <button type="button" class="button remove-fetch-priority">Remove</button>
                                    </div>
                                    <?php
                                }
                            }
                            ?>
                        </div>
                        <button type="button" class="button" id="add-fetch-priority"><?php esc_html_e( 'Add New', 'mbr-performance' ); ?></button>
                        <p class="description"><?php esc_html_e( 'CSS selectors for images that should have high fetch priority.', 'mbr-performance' ); ?></p>
                    </div>
                </td>
            </tr>
            
            <!-- Disable Core Fetch Priority -->
            <tr>
                <th scope="row">
                    <label for="disable_core_fetch_priority">
                        <?php esc_html_e( 'Disable Core Fetch Priority', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Disable WordPress core\'s automatic fetchpriority attribute', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[preloading][disable_core_fetch_priority]" id="disable_core_fetch_priority" value="1" <?php checked( isset( $preload_options['disable_core_fetch_priority'] ) && $preload_options['disable_core_fetch_priority'] ); ?>>
                    <p class="description"><?php esc_html_e( 'Removes WordPress automatic fetchpriority so you have full control.', 'mbr-performance' ); ?></p>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<div class="mbr-section">
    <h2><?php esc_html_e( 'Speculative Loading', 'mbr-performance' ); ?></h2>
    
    <table class="form-table">
        <tbody>
            <!-- Speculative Loading Mode -->
            <tr>
                <th scope="row">
                    <label for="speculative_mode">
                        <?php esc_html_e( 'Mode', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Prefetch = fetch next page, Prerender = fully render next page in background', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <select name="mbrpe_options[preloading][speculative_mode]" id="speculative_mode">
                        <?php
                        $mode = isset( $preload_options['speculative_mode'] ) ? $preload_options['speculative_mode'] : 'auto';
                        $modes = array(
                            'auto' => __( 'Auto (Default)', 'mbr-performance' ),
                            'prefetch' => __( 'Prefetch', 'mbr-performance' ),
                            'prerender' => __( 'Prerender', 'mbr-performance' ),
                            'disabled' => __( 'Disabled', 'mbr-performance' ),
                        );
                        foreach ( $modes as $value => $label ) {
                            printf(
                                '<option value="%s"%s>%s</option>',
                                esc_attr( $value ),
                                selected( $mode, $value, false ),
                                esc_html( $label )
                            );
                        }
                        ?>
                    </select>
                    <p class="description"><?php esc_html_e( 'Speculative loading prefetches/prerenders links users are likely to click.', 'mbr-performance' ); ?></p>
                </td>
            </tr>
            
            <!-- Eagerness -->
            <tr>
                <th scope="row">
                    <label for="speculative_eagerness">
                        <?php esc_html_e( 'Eagerness', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Conservative = on hover, Moderate = on mouse down, Eager = immediately', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <select name="mbrpe_options[preloading][speculative_eagerness]" id="speculative_eagerness">
                        <?php
                        $eagerness = isset( $preload_options['speculative_eagerness'] ) ? $preload_options['speculative_eagerness'] : 'auto';
                        $eagerness_options = array(
                            'auto' => __( 'Auto (Default)', 'mbr-performance' ),
                            'conservative' => __( 'Conservative', 'mbr-performance' ),
                            'moderate' => __( 'Moderate', 'mbr-performance' ),
                            'eager' => __( 'Eager', 'mbr-performance' ),
                        );
                        foreach ( $eagerness_options as $value => $label ) {
                            printf(
                                '<option value="%s"%s>%s</option>',
                                esc_attr( $value ),
                                selected( $eagerness, $value, false ),
                                esc_html( $label )
                            );
                        }
                        ?>
                    </select>
                    <p class="description"><?php esc_html_e( 'How aggressively to prefetch/prerender links.', 'mbr-performance' ); ?></p>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<div class="mbr-section">
    <h2><?php esc_html_e( 'Connection Optimization', 'mbr-performance' ); ?></h2>
    
    <table class="form-table">
        <tbody>
            <!-- Preconnect -->
            <tr>
                <th scope="row">
                    <label for="preconnect_domains">
                        <?php esc_html_e( 'Preconnect Domains', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Establish early connections to important third-party domains', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <div id="preconnect-list">
                        <?php
                        $preconnect = isset( $preload_options['preconnect_domains'] ) ? $preload_options['preconnect_domains'] : array();
                        if ( ! empty( $preconnect ) ) {
                            foreach ( $preconnect as $domain ) {
                                ?>
                                <div class="preconnect-row" style="margin-bottom: 10px;">
                                    <input type="text" name="mbrpe_options[preloading][preconnect_domains][]" value="<?php echo esc_attr( $domain ); ?>" placeholder="https://fonts.googleapis.com" class="regular-text">
                                    <button type="button" class="button remove-preconnect">Remove</button>
                                </div>
                                <?php
                            }
                        }
                        ?>
                    </div>
                    <button type="button" class="button" id="add-preconnect"><?php esc_html_e( 'Add New', 'mbr-performance' ); ?></button>
                    <p class="description"><?php esc_html_e( 'Enter domains to preconnect (e.g., https://fonts.googleapis.com)', 'mbr-performance' ); ?></p>
                </td>
            </tr>
            
            <!-- DNS Prefetch -->
            <tr>
                <th scope="row">
                    <label for="dns_prefetch_domains">
                        <?php esc_html_e( 'DNS Prefetch Domains', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Resolve DNS early for external domains', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <textarea name="mbrpe_options[preloading][dns_prefetch_domains]" id="dns_prefetch_domains" rows="5" class="large-text code" placeholder="//fonts.googleapis.com&#10;//www.google-analytics.com&#10;//cdn.example.com"><?php echo isset( $preload_options['dns_prefetch_domains'] ) ? esc_textarea( $preload_options['dns_prefetch_domains'] ) : ''; ?></textarea>
                    <p class="description"><?php esc_html_e( 'Enter one domain per line (can use // or https://)', 'mbr-performance' ); ?></p>
                </td>
            </tr>

            <!-- Hover Prefetch (instant.page) — v1.12.0 -->
            <tr>
                <th scope="row">
                    <label for="hover_prefetch">
                        <?php esc_html_e( 'Hover Prefetch (instant.page)', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Prefetches the destination page when a visitor hovers over a link, making the click feel instant. Honours the Save-Data header.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[preloading][hover_prefetch]" id="hover_prefetch" value="1" <?php checked( ! empty( $preload_options['hover_prefetch'] ) ); ?>>
                    <p class="description"><?php esc_html_e( 'New in v1.12.0. Uses the open-source instant.page runtime (MIT).', 'mbr-performance' ); ?></p>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<?php
ob_start();
?>
jQuery(document).ready(function($) {
    // Preload Images
    $('#add-preload-image').on('click', function() {
        $('#preload-images-list').append(`
            <div class="preload-image-row" style="margin-bottom: 10px;">
                <input type="text" name="mbrpe_options[preloading][preload_images][]" value="" placeholder="https://example.com/image.jpg" class="regular-text">
                <button type="button" class="button remove-preload-image">Remove</button>
            </div>
        `);
    });
    
    $(document).on('click', '.remove-preload-image', function() {
        $(this).closest('.preload-image-row').remove();
    });
    
    // Fetch Priority
    $('#add-fetch-priority').on('click', function() {
        $('#fetch-priority-list').append(`
            <div class="fetch-priority-row" style="margin-bottom: 10px;">
                <input type="text" name="mbrpe_options[preloading][fetch_priority_selectors][]" value="" placeholder=".hero-image, #main-banner img" class="regular-text">
                <button type="button" class="button remove-fetch-priority">Remove</button>
            </div>
        `);
    });
    
    $(document).on('click', '.remove-fetch-priority', function() {
        $(this).closest('.fetch-priority-row').remove();
    });
    
    // Preconnect
    $('#add-preconnect').on('click', function() {
        $('#preconnect-list').append(`
            <div class="preconnect-row" style="margin-bottom: 10px;">
                <input type="text" name="mbrpe_options[preloading][preconnect_domains][]" value="" placeholder="https://fonts.googleapis.com" class="regular-text">
                <button type="button" class="button remove-preconnect">Remove</button>
            </div>
        `);
    });
    
    $(document).on('click', '.remove-preconnect', function() {
        $(this).closest('.preconnect-row').remove();
    });
});
<?php
$mbr_preload_js = ob_get_clean();
wp_add_inline_script( 'mbr-performance-admin', $mbr_preload_js );
