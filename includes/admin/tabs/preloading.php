<?php
/**
 * Preloading Settings Tab
 *
 * @package MBR_WP_Performance
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$options = get_option( 'mbr_wp_performance_options', array() );
$preload_options = isset( $options['preloading'] ) ? $options['preloading'] : array();
?>

<div class="mbr-section">
    <h2><?php esc_html_e( 'Preloading Settings', 'mbr-wp-performance' ); ?></h2>
    
    <table class="form-table">
        <tbody>
            <!-- Preload Critical Images -->
            <tr>
                <th scope="row">
                    <label for="preload_critical_images_count">
                        <?php esc_html_e( 'Preload Critical Images', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Number of images to preload (usually above the fold). 0 = disabled.', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <select name="mbr_wp_performance_options[preloading][preload_critical_images_count]" id="preload_critical_images_count">
                        <?php
                        $count = isset( $preload_options['preload_critical_images_count'] ) ? $preload_options['preload_critical_images_count'] : 0;
                        for ( $i = 0; $i <= 5; $i++ ) {
                            printf(
                                '<option value="%d"%s>%s</option>',
                                $i,
                                selected( $count, $i, false ),
                                $i === 0 ? '0 (Default)' : $i
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
                                    <input type="text" name="mbr_wp_performance_options[preloading][preload_images][]" value="<?php echo esc_attr( $image ); ?>" placeholder="https://example.com/image.jpg" class="regular-text">
                                    <button type="button" class="button remove-preload-image">Remove</button>
                                </div>
                                <?php
                            }
                        }
                        ?>
                    </div>
                    <button type="button" class="button" id="add-preload-image"><?php esc_html_e( 'Add New', 'mbr-wp-performance' ); ?></button>
                    <p class="description"><?php esc_html_e( 'Enter image URLs to preload (LCP images, hero images, etc.)', 'mbr-wp-performance' ); ?></p>
                </td>
            </tr>
            
            <!-- Cloudflare Early Hints -->
            <tr>
                <th scope="row">
                    <label for="cloudflare_early_hints">
                        <?php esc_html_e( 'Cloudflare Early Hints', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Send 103 Early Hints header for faster resource loading (requires Cloudflare)', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[preloading][cloudflare_early_hints]" id="cloudflare_early_hints" value="1" <?php checked( isset( $preload_options['cloudflare_early_hints'] ) && $preload_options['cloudflare_early_hints'] ); ?>>
                    <p class="description"><?php esc_html_e( 'Enables HTTP 103 Early Hints for Cloudflare-hosted sites.', 'mbr-wp-performance' ); ?></p>
                </td>
            </tr>
            
            <!-- Fetch Priority -->
            <tr>
                <th scope="row">
                    <label for="fetch_priority">
                        <?php esc_html_e( 'Fetch Priority', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Add fetchpriority="high" to critical images for faster LCP', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[preloading][fetch_priority]" id="fetch_priority" value="1" <?php checked( isset( $preload_options['fetch_priority'] ) && $preload_options['fetch_priority'] ); ?>>
                    <p class="description"><?php esc_html_e( 'Automatically adds fetchpriority="high" to the first image on each page.', 'mbr-wp-performance' ); ?></p>
                    
                    <div style="margin-top: 15px;">
                        <label><?php esc_html_e( 'Custom Fetch Priority Selectors:', 'mbr-wp-performance' ); ?></label>
                        <div id="fetch-priority-list">
                            <?php
                            $fetch_priority_selectors = isset( $preload_options['fetch_priority_selectors'] ) ? $preload_options['fetch_priority_selectors'] : array();
                            if ( ! empty( $fetch_priority_selectors ) ) {
                                foreach ( $fetch_priority_selectors as $selector ) {
                                    ?>
                                    <div class="fetch-priority-row" style="margin-bottom: 10px;">
                                        <input type="text" name="mbr_wp_performance_options[preloading][fetch_priority_selectors][]" value="<?php echo esc_attr( $selector ); ?>" placeholder=".hero-image, #main-banner img" class="regular-text">
                                        <button type="button" class="button remove-fetch-priority">Remove</button>
                                    </div>
                                    <?php
                                }
                            }
                            ?>
                        </div>
                        <button type="button" class="button" id="add-fetch-priority"><?php esc_html_e( 'Add New', 'mbr-wp-performance' ); ?></button>
                        <p class="description"><?php esc_html_e( 'CSS selectors for images that should have high fetch priority.', 'mbr-wp-performance' ); ?></p>
                    </div>
                </td>
            </tr>
            
            <!-- Disable Core Fetch Priority -->
            <tr>
                <th scope="row">
                    <label for="disable_core_fetch_priority">
                        <?php esc_html_e( 'Disable Core Fetch Priority', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Disable WordPress core\'s automatic fetchpriority attribute', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[preloading][disable_core_fetch_priority]" id="disable_core_fetch_priority" value="1" <?php checked( isset( $preload_options['disable_core_fetch_priority'] ) && $preload_options['disable_core_fetch_priority'] ); ?>>
                    <p class="description"><?php esc_html_e( 'Removes WordPress automatic fetchpriority so you have full control.', 'mbr-wp-performance' ); ?></p>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<div class="mbr-section">
    <h2><?php esc_html_e( 'Speculative Loading', 'mbr-wp-performance' ); ?></h2>
    
    <table class="form-table">
        <tbody>
            <!-- Speculative Loading Mode -->
            <tr>
                <th scope="row">
                    <label for="speculative_mode">
                        <?php esc_html_e( 'Mode', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Prefetch = fetch next page, Prerender = fully render next page in background', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <select name="mbr_wp_performance_options[preloading][speculative_mode]" id="speculative_mode">
                        <?php
                        $mode = isset( $preload_options['speculative_mode'] ) ? $preload_options['speculative_mode'] : 'auto';
                        $modes = array(
                            'auto' => __( 'Auto (Default)', 'mbr-wp-performance' ),
                            'prefetch' => __( 'Prefetch', 'mbr-wp-performance' ),
                            'prerender' => __( 'Prerender', 'mbr-wp-performance' ),
                            'disabled' => __( 'Disabled', 'mbr-wp-performance' ),
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
                    <p class="description"><?php esc_html_e( 'Speculative loading prefetches/prerenders links users are likely to click.', 'mbr-wp-performance' ); ?></p>
                </td>
            </tr>
            
            <!-- Eagerness -->
            <tr>
                <th scope="row">
                    <label for="speculative_eagerness">
                        <?php esc_html_e( 'Eagerness', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Conservative = on hover, Moderate = on mouse down, Eager = immediately', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <select name="mbr_wp_performance_options[preloading][speculative_eagerness]" id="speculative_eagerness">
                        <?php
                        $eagerness = isset( $preload_options['speculative_eagerness'] ) ? $preload_options['speculative_eagerness'] : 'auto';
                        $eagerness_options = array(
                            'auto' => __( 'Auto (Default)', 'mbr-wp-performance' ),
                            'conservative' => __( 'Conservative', 'mbr-wp-performance' ),
                            'moderate' => __( 'Moderate', 'mbr-wp-performance' ),
                            'eager' => __( 'Eager', 'mbr-wp-performance' ),
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
                    <p class="description"><?php esc_html_e( 'How aggressively to prefetch/prerender links.', 'mbr-wp-performance' ); ?></p>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<div class="mbr-section">
    <h2><?php esc_html_e( 'Connection Optimization', 'mbr-wp-performance' ); ?></h2>
    
    <table class="form-table">
        <tbody>
            <!-- Preconnect -->
            <tr>
                <th scope="row">
                    <label for="preconnect_domains">
                        <?php esc_html_e( 'Preconnect Domains', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Establish early connections to important third-party domains', 'mbr-wp-performance' ); ?>">?</span>
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
                                    <input type="text" name="mbr_wp_performance_options[preloading][preconnect_domains][]" value="<?php echo esc_attr( $domain ); ?>" placeholder="https://fonts.googleapis.com" class="regular-text">
                                    <button type="button" class="button remove-preconnect">Remove</button>
                                </div>
                                <?php
                            }
                        }
                        ?>
                    </div>
                    <button type="button" class="button" id="add-preconnect"><?php esc_html_e( 'Add New', 'mbr-wp-performance' ); ?></button>
                    <p class="description"><?php esc_html_e( 'Enter domains to preconnect (e.g., https://fonts.googleapis.com)', 'mbr-wp-performance' ); ?></p>
                </td>
            </tr>
            
            <!-- DNS Prefetch -->
            <tr>
                <th scope="row">
                    <label for="dns_prefetch_domains">
                        <?php esc_html_e( 'DNS Prefetch Domains', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Resolve DNS early for external domains', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <textarea name="mbr_wp_performance_options[preloading][dns_prefetch_domains]" id="dns_prefetch_domains" rows="5" class="large-text code" placeholder="//fonts.googleapis.com&#10;//www.google-analytics.com&#10;//cdn.example.com"><?php echo isset( $preload_options['dns_prefetch_domains'] ) ? esc_textarea( $preload_options['dns_prefetch_domains'] ) : ''; ?></textarea>
                    <p class="description"><?php esc_html_e( 'Enter one domain per line (can use // or https://)', 'mbr-wp-performance' ); ?></p>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<script>
jQuery(document).ready(function($) {
    // Preload Images
    $('#add-preload-image').on('click', function() {
        $('#preload-images-list').append(`
            <div class="preload-image-row" style="margin-bottom: 10px;">
                <input type="text" name="mbr_wp_performance_options[preloading][preload_images][]" value="" placeholder="https://example.com/image.jpg" class="regular-text">
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
                <input type="text" name="mbr_wp_performance_options[preloading][fetch_priority_selectors][]" value="" placeholder=".hero-image, #main-banner img" class="regular-text">
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
                <input type="text" name="mbr_wp_performance_options[preloading][preconnect_domains][]" value="" placeholder="https://fonts.googleapis.com" class="regular-text">
                <button type="button" class="button remove-preconnect">Remove</button>
            </div>
        `);
    });
    
    $(document).on('click', '.remove-preconnect', function() {
        $(this).closest('.preconnect-row').remove();
    });
});
</script>
