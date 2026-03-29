<?php
/**
 * WebP Converter Settings Tab
 *
 * @package MBR_WP_Performance
 * @since   1.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$options      = get_option( 'mbr_wp_performance_options', array() );
$webp_options = isset( $options['webp'] ) ? $options['webp'] : array();
$diagnostics  = MBR_WP_Performance_WebP_Converter::get_diagnostics();
$upload_base  = wp_upload_dir()['baseurl'];
?>

<!-- Settings Section -->
<div class="mbr-wp-performance-section">
    <h2><?php esc_html_e( 'WebP Settings', 'mbr-wp-performance' ); ?></h2>

    <table class="form-table">
        <tbody>
            <!-- Auto Convert -->
            <tr>
                <th scope="row">
                    <label for="webp_auto_convert">
                        <?php esc_html_e( 'Automatic Conversion', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Automatically convert new images to WebP when they are uploaded via the Media Library.', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[webp][auto_convert]" id="webp_auto_convert" value="1" <?php checked( ! empty( $webp_options['auto_convert'] ) ); ?>>
                    <p class="description"><?php esc_html_e( 'Automatically convert new JPG, JPEG and PNG images to WebP upon upload.', 'mbr-wp-performance' ); ?></p>
                </td>
            </tr>

            <!-- Compression Level -->
            <tr>
                <th scope="row">
                    <label for="webp_compression_level">
                        <?php esc_html_e( 'Compression Level', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Quality level for WebP output. 1 = smallest file, lowest quality. 100 = largest file, best quality.', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="number" name="mbr_wp_performance_options[webp][compression_level]" id="webp_compression_level" value="<?php echo esc_attr( isset( $webp_options['compression_level'] ) ? $webp_options['compression_level'] : 75 ); ?>" min="1" max="100" class="small-text">
                    <p class="description"><?php esc_html_e( '1 (low quality, small file) to 100 (best quality, larger file). Default: 75.', 'mbr-wp-performance' ); ?></p>
                </td>
            </tr>

            <!-- Picture Tags -->
            <tr>
                <th scope="row">
                    <label for="webp_picture_tags">
                        <?php esc_html_e( 'HTML <picture> Tags', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Wrap images in <picture> elements with a WebP <source>. Works on any server (Apache, Nginx, CDN) and falls back to the original format for browsers without WebP support.', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[webp][picture_tags]" id="webp_picture_tags" value="1" <?php checked( ! empty( $webp_options['picture_tags'] ) ); ?>>
                    <p class="description"><?php esc_html_e( 'Serve WebP via HTML <picture> tags with automatic fallback. Works with Elementor, Gutenberg, and classic content.', 'mbr-wp-performance' ); ?></p>
                </td>
            </tr>

            <!-- .htaccess Rules -->
            <tr>
                <th scope="row">
                    <label for="webp_htaccess_rules">
                        <?php esc_html_e( '.htaccess Rewrite Rules', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Add Apache/LiteSpeed rewrite rules to serve WebP files transparently. Does not apply on Nginx.', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[webp][htaccess_rules]" id="webp_htaccess_rules" value="1" <?php checked( ! empty( $webp_options['htaccess_rules'] ) ); ?>>
                    <p class="description"><?php esc_html_e( 'Add server-level rewrite rules for Apache/LiteSpeed. Not needed if using <picture> tags above.', 'mbr-wp-performance' ); ?></p>
                    <?php
                    if ( isset( $_SERVER['SERVER_SOFTWARE'] ) ) {
                        $srv = strtolower( sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) );
                        if ( strpos( $srv, 'nginx' ) !== false ) {
                            echo '<p class="description" style="color: var(--mbr-warning);">';
                            esc_html_e( 'You appear to be running Nginx. .htaccess rules will not apply — use the <picture> tag option instead, or add equivalent Nginx server rules.', 'mbr-wp-performance' );
                            echo '</p>';
                        }
                    }
                    ?>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<!-- Server Diagnostics Section -->
<div class="mbr-wp-performance-section">
    <h2><?php esc_html_e( 'Server Diagnostics', 'mbr-wp-performance' ); ?></h2>

    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row"><?php esc_html_e( 'GD Library', 'mbr-wp-performance' ); ?></th>
                <td>
                    <?php if ( $diagnostics['gd_installed'] ) : ?>
                        <span style="color: var(--mbr-success);">&#10004; <?php esc_html_e( 'Installed', 'mbr-wp-performance' ); ?></span>
                    <?php else : ?>
                        <span style="color: var(--mbr-danger);">&#10008; <?php esc_html_e( 'Not installed — contact your host.', 'mbr-wp-performance' ); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'WebP Support in GD', 'mbr-wp-performance' ); ?></th>
                <td>
                    <?php if ( $diagnostics['webp_support'] ) : ?>
                        <span style="color: var(--mbr-success);">&#10004; <?php esc_html_e( 'Supported', 'mbr-wp-performance' ); ?></span>
                    <?php else : ?>
                        <span style="color: var(--mbr-danger);">&#10008; <?php esc_html_e( 'Not supported — contact your host.', 'mbr-wp-performance' ); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Uploads Folder', 'mbr-wp-performance' ); ?></th>
                <td>
                    <?php if ( $diagnostics['uploads_writable'] ) : ?>
                        <span style="color: var(--mbr-success);">&#10004; <?php esc_html_e( 'Writable', 'mbr-wp-performance' ); ?></span>
                    <?php else : ?>
                        <span style="color: var(--mbr-danger);">&#10008; <?php esc_html_e( 'Not writable — contact your host.', 'mbr-wp-performance' ); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
        </tbody>
    </table>

    <?php if ( ! $diagnostics['all_ok'] ) : ?>
        <p style="color: var(--mbr-danger); font-weight: 600; margin-top: 12px;"><?php esc_html_e( 'Action Required: Resolve the error(s) above before converting images.', 'mbr-wp-performance' ); ?></p>
    <?php else : ?>
        <p style="color: var(--mbr-success); margin-top: 12px;"><?php esc_html_e( 'Your server configuration looks good!', 'mbr-wp-performance' ); ?></p>
    <?php endif; ?>
</div>

<!-- Bulk Converter Section -->
<div class="mbr-wp-performance-section">
    <h2><?php esc_html_e( 'Bulk Converter', 'mbr-wp-performance' ); ?></h2>
    <p class="description" style="margin-bottom: 16px;"><?php esc_html_e( 'Convert existing images in your Media Library that were uploaded before this feature was enabled.', 'mbr-wp-performance' ); ?></p>

    <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 16px;">
        <button type="button" id="mbr-webp-start-conversion" class="button button-primary" <?php disabled( ! $diagnostics['all_ok'] ); ?>>
            <?php esc_html_e( 'Start Conversion', 'mbr-wp-performance' ); ?>
        </button>
        <button type="button" id="mbr-webp-clear-history" class="button button-secondary">
            <?php esc_html_e( 'Clear All History', 'mbr-wp-performance' ); ?>
        </button>
        <button type="button" id="mbr-webp-revert-all" class="button button-secondary" style="border-color: var(--mbr-danger) !important; color: var(--mbr-danger) !important;">
            <?php esc_html_e( 'Revert All WebP Files', 'mbr-wp-performance' ); ?>
        </button>
    </div>
    <p class="description" style="margin-top: -8px; margin-bottom: 16px;"><?php esc_html_e( '"Revert All" deletes every WebP file this plugin created and clears the conversion history. Original images are never touched. Files uploaded as WebP are left intact.', 'mbr-wp-performance' ); ?></p>

    <!-- Progress Bar -->
    <div id="mbr-webp-progress-container" style="display: none; margin-bottom: 16px;">
        <div style="background: var(--mbr-bg-input); border: 1px solid var(--mbr-border); border-radius: var(--mbr-radius-sm); overflow: hidden; height: 28px;">
            <div id="mbr-webp-progress-bar" style="height: 100%; width: 0%; background: var(--mbr-accent); color: #fff; text-align: center; line-height: 28px; font-size: 12px; font-weight: 600; transition: width 0.3s ease;">0%</div>
        </div>
    </div>

    <!-- Status -->
    <div id="mbr-webp-status" style="margin-bottom: 16px; font-size: 13px; color: var(--mbr-text-secondary);"></div>

    <!-- Converted Images History -->
    <h3 style="color: var(--mbr-text-primary); font-size: 14px; font-weight: 600; margin-bottom: 12px;"><?php esc_html_e( 'Conversion History', 'mbr-wp-performance' ); ?></h3>

    <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 12px;">
        <select id="mbr-webp-bulk-action">
            <option value="-1"><?php esc_html_e( 'Bulk Actions', 'mbr-wp-performance' ); ?></option>
            <option value="delete"><?php esc_html_e( 'Remove from History', 'mbr-wp-performance' ); ?></option>
        </select>
        <button type="button" id="mbr-webp-apply-bulk" class="button"><?php esc_html_e( 'Apply', 'mbr-wp-performance' ); ?></button>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <td class="manage-column column-cb check-column" style="width: 40px;">
                    <input id="mbr-webp-select-all" type="checkbox">
                </td>
                <th><?php esc_html_e( 'Original Image', 'mbr-wp-performance' ); ?></th>
                <th style="width: 120px;"><?php esc_html_e( 'Original Size', 'mbr-wp-performance' ); ?></th>
                <th style="width: 120px;"><?php esc_html_e( 'WebP Size', 'mbr-wp-performance' ); ?></th>
                <th style="width: 120px;"><?php esc_html_e( 'Compression', 'mbr-wp-performance' ); ?></th>
            </tr>
        </thead>
        <tbody id="mbr-webp-history-list">
            <?php
            $converted_images = get_option( 'mbr_webp_converted_images', array() );

            // Also check old standalone plugin data.
            if ( empty( $converted_images ) ) {
                $converted_images = get_option( 'itwc_converted_images', array() );
            }

            if ( is_array( $converted_images ) && ! empty( $converted_images ) ) :
                foreach ( array_reverse( $converted_images ) as $image ) :
                    $filename = basename( $image['original_path'] );
                    $full_url = $upload_base . '/' . $image['original_path'];
                    $compression_text = 'N/A';
                    if ( isset( $image['original_size'] ) && $image['original_size'] > 0 ) {
                        $compression      = ( ( $image['original_size'] - $image['webp_size'] ) / $image['original_size'] ) * 100;
                        $compression_text = number_format( $compression, 2 ) . '%';
                    }
                    ?>
                    <tr>
                        <th scope="row" class="check-column">
                            <input type="checkbox" class="mbr-webp-item-checkbox" value="<?php echo esc_attr( $image['original_path'] ); ?>">
                        </th>
                        <td><a href="<?php echo esc_url( $full_url ); ?>" target="_blank"><?php echo esc_html( $filename ); ?></a></td>
                        <td><?php echo esc_html( size_format( $image['original_size'], 2 ) ); ?></td>
                        <td><?php echo esc_html( size_format( $image['webp_size'], 2 ) ); ?></td>
                        <td><?php echo esc_html( $compression_text ); ?></td>
                    </tr>
                    <?php
                endforeach;
            else :
                ?>
                <tr><td colspan="5"><?php esc_html_e( 'No images converted yet.', 'mbr-wp-performance' ); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
