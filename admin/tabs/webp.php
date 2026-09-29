<?php
/**
 * WebP Converter Settings Tab
 *
 * @package MBRPE
 * @since   1.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$options      = get_option( 'mbrpe_options', array() );
$webp_options = isset( $options['webp'] ) ? $options['webp'] : array();
$dim_options  = isset( $options['image_dimensions'] ) ? $options['image_dimensions'] : array();
$diagnostics  = MBRPE_WebP_Converter::get_diagnostics();
$upload_base  = wp_upload_dir()['baseurl'];

$dim_defaults = array(
    'max_dimension' => class_exists( 'MBRPE_Image_Dimensions' ) ? MBRPE_Image_Dimensions::DEFAULT_MAX_DIMENSION : 2560,
    'min'           => class_exists( 'MBRPE_Image_Dimensions' ) ? MBRPE_Image_Dimensions::MIN_MAX_DIMENSION : 100,
    'max'           => class_exists( 'MBRPE_Image_Dimensions' ) ? MBRPE_Image_Dimensions::MAX_MAX_DIMENSION : 10000,
);
?>

<!-- Settings Section -->
<div class="mbr-performance-section">
    <h2><?php esc_html_e( 'WebP Settings', 'mbr-performance' ); ?></h2>

    <table class="form-table">
        <tbody>
            <!-- Auto Convert -->
            <tr>
                <th scope="row">
                    <label for="webp_auto_convert">
                        <?php esc_html_e( 'Automatic Conversion', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Automatically convert new images to WebP when they are uploaded via the Media Library.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[webp][auto_convert]" id="webp_auto_convert" value="1" <?php checked( ! empty( $webp_options['auto_convert'] ) ); ?>>
                    <p class="description"><?php esc_html_e( 'Automatically convert new JPG, JPEG and PNG images to WebP upon upload.', 'mbr-performance' ); ?></p>
                </td>
            </tr>

            <!-- Compression Level -->
            <tr>
                <th scope="row">
                    <label for="webp_compression_level">
                        <?php esc_html_e( 'Compression Level', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Quality level for WebP output. 1 = smallest file, lowest quality. 100 = largest file, best quality.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="number" name="mbrpe_options[webp][compression_level]" id="webp_compression_level" value="<?php echo esc_attr( isset( $webp_options['compression_level'] ) ? $webp_options['compression_level'] : 75 ); ?>" min="1" max="100" class="small-text">
                    <p class="description"><?php esc_html_e( '1 (low quality, small file) to 100 (best quality, larger file). Default: 75.', 'mbr-performance' ); ?></p>
                </td>
            </tr>

            <!-- Picture Tags -->
            <tr>
                <th scope="row">
                    <label for="webp_picture_tags">
                        <?php esc_html_e( 'HTML <picture> Tags', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Wrap images in <picture> elements with a WebP <source>. Works on any server (Apache, Nginx, CDN) and falls back to the original format for browsers without WebP support.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[webp][picture_tags]" id="webp_picture_tags" value="1" <?php checked( ! empty( $webp_options['picture_tags'] ) ); ?>>
                    <p class="description"><?php esc_html_e( 'Serve WebP via HTML <picture> tags with automatic fallback. Works with Elementor, Gutenberg, and classic content.', 'mbr-performance' ); ?></p>
                </td>
            </tr>

            <!-- .htaccess Rules -->
            <tr>
                <th scope="row">
                    <label for="webp_htaccess_rules">
                        <?php esc_html_e( '.htaccess Rewrite Rules', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Add Apache/LiteSpeed rewrite rules to serve WebP files transparently. Does not apply on Nginx.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[webp][htaccess_rules]" id="webp_htaccess_rules" value="1" <?php checked( ! empty( $webp_options['htaccess_rules'] ) ); ?>>
                    <p class="description"><?php esc_html_e( 'Add server-level rewrite rules for Apache/LiteSpeed. Not needed if using <picture> tags above.', 'mbr-performance' ); ?></p>
                    <?php
                    if ( isset( $_SERVER['SERVER_SOFTWARE'] ) ) {
                        $srv = strtolower( sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) );
                        if ( strpos( $srv, 'nginx' ) !== false ) {
                            echo '<p class="description" style="color: var(--mbr-warning);">';
                            esc_html_e( 'You appear to be running Nginx. .htaccess rules will not apply — use the <picture> tag option instead, or add equivalent Nginx server rules.', 'mbr-performance' );
                            echo '</p>';
                        }
                    }
                    ?>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<!-- AVIF Section (v1.12.0) -->
<?php
$avif_diag      = class_exists( 'MBRPE_AVIF_Converter' ) ? MBRPE_AVIF_Converter::get_diagnostics() : array();
$avif_supported = ! empty( $avif_diag['any_avif'] );
?>
<div class="mbr-performance-section">
    <h2><?php esc_html_e( 'AVIF', 'mbr-performance' ); ?> <span style="font-size:12px;font-weight:normal;color:#666;">— <?php esc_html_e( 'New in v1.12.0', 'mbr-performance' ); ?></span></h2>
    <p class="description" style="margin-bottom: 16px;">
        <?php esc_html_e( 'AVIF is typically 20-30% smaller than WebP at equivalent perceived quality. When both are enabled, the <picture> wrapper emits AVIF first, then WebP, then the JPEG/PNG fallback — browsers automatically pick the first format they support.', 'mbr-performance' ); ?>
    </p>

    <?php if ( $avif_supported ) : ?>
        <div class="notice notice-info inline" style="margin:0 0 16px;">
            <p>
                <strong><?php esc_html_e( 'Server AVIF support detected.', 'mbr-performance' ); ?></strong>
                <?php esc_html_e( 'New uploads will be converted to .avif alongside the WebP variant.', 'mbr-performance' ); ?>
            </p>
            <p>
                GD AVIF: <code><?php echo ! empty( $avif_diag['gd_avif'] ) ? '✓' : '✗'; ?></code> &nbsp;
                Imagick AVIF: <code><?php echo ! empty( $avif_diag['imagick_avif'] ) ? '✓' : '✗'; ?></code>
            </p>
        </div>
    <?php else : ?>
        <div class="notice notice-warning inline" style="margin:0 0 16px;">
            <p><?php esc_html_e( 'Server-side AVIF encoding is not available. Requires PHP 8.1+ with GD built against libavif (check phpinfo for gd_info()[\'AVIF Support\']), or Imagick built against libheif/libde265. The presence of imageavif() in PHP 8.1+ is not enough on its own — many shared hosts ship the function symbol without the underlying encoder, in which case calls fail silently and no .avif files are produced.', 'mbr-performance' ); ?></p>
            <p>
                GD AVIF: <code><?php echo ! empty( $avif_diag['gd_avif'] ) ? '✓' : '✗'; ?></code> &nbsp;
                Imagick AVIF: <code><?php echo ! empty( $avif_diag['imagick_avif'] ) ? '✓' : '✗'; ?></code>
            </p>
        </div>
    <?php endif; ?>

    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row">
                    <label for="webp_avif_enabled">
                        <?php esc_html_e( 'Enable AVIF Conversion', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'On upload, generate an .avif sibling alongside the WebP. The <picture> wrapper will emit the AVIF source first so capable browsers (Chrome 85+, Firefox 93+, Safari 16.4+) use it.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[webp][avif_enabled]" id="webp_avif_enabled" value="1" <?php checked( ! empty( $webp_options['avif_enabled'] ) ); ?> <?php disabled( ! $avif_supported ); ?>>
                    <p class="description"><?php esc_html_e( 'Requires Automatic Conversion + <picture> Tags to be enabled above for end-to-end delivery.', 'mbr-performance' ); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="webp_avif_quality">
                        <?php esc_html_e( 'AVIF Quality', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'AVIF tolerates lower numbers than WebP. 60 is a sensible default — perceptually equivalent to WebP at ~75. Lower = smaller file.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="number" name="mbrpe_options[webp][avif_quality]" id="webp_avif_quality" value="<?php echo esc_attr( isset( $webp_options['avif_quality'] ) ? $webp_options['avif_quality'] : 60 ); ?>" min="1" max="100" class="small-text">
                    <p class="description"><?php esc_html_e( '1 (lowest, smallest) to 100 (highest, largest). Default: 60.', 'mbr-performance' ); ?></p>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<!-- Image Sizing & Dimensions Section -->
<div class="mbr-performance-section">
    <h2><?php esc_html_e( 'Image Sizing &amp; Dimensions', 'mbr-performance' ); ?></h2>
    <p class="description" style="margin-bottom: 16px;">
        <?php esc_html_e( 'Help eliminate common PageSpeed Insights warnings by resizing oversized uploads and ensuring every image has explicit width and height attributes. Reduces layout shift (CLS) and prevents browsers from downloading images far larger than needed.', 'mbr-performance' ); ?>
    </p>

    <table class="form-table">
        <tbody>
            <!-- Resize on upload -->
            <tr>
                <th scope="row">
                    <label for="image_dimensions_resize_on_upload">
                        <?php esc_html_e( 'Resize Large Uploads', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Downscale newly-uploaded images so their longer edge never exceeds the maximum dimension below. Helps fix the "Properly size images" warning in Google PageSpeed Insights. Only affects new uploads — existing images are not touched.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[image_dimensions][resize_on_upload]" id="image_dimensions_resize_on_upload" value="1" <?php checked( ! empty( $dim_options['resize_on_upload'] ) ); ?>>
                    <p class="description"><?php esc_html_e( 'Automatically downscale new uploads that exceed the maximum dimension, preserving aspect ratio. Uses the WordPress core scaling pipeline.', 'mbr-performance' ); ?></p>
                </td>
            </tr>

            <!-- Max dimension -->
            <tr>
                <th scope="row">
                    <label for="image_dimensions_max_dimension">
                        <?php esc_html_e( 'Maximum Dimension (px)', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'The longer edge of any newly-uploaded image will be scaled down to this value. Applies to both width and height — whichever is larger. Default: 2560.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="number"
                           name="mbrpe_options[image_dimensions][max_dimension]"
                           id="image_dimensions_max_dimension"
                           value="<?php echo esc_attr( isset( $dim_options['max_dimension'] ) ? $dim_options['max_dimension'] : $dim_defaults['max_dimension'] ); ?>"
                           min="<?php echo esc_attr( $dim_defaults['min'] ); ?>"
                           max="<?php echo esc_attr( $dim_defaults['max'] ); ?>"
                           step="1"
                           class="small-text">
                    <p class="description">
                        <?php
                        printf(
                            /* translators: 1: minimum dimension in px, 2: maximum dimension in px, 3: default value. */
                            esc_html__( 'Allowed range: %1$d–%2$d pixels. Default: %3$d.', 'mbr-performance' ),
                            (int) $dim_defaults['min'],
                            (int) $dim_defaults['max'],
                            (int) $dim_defaults['max_dimension']
                        );
                        ?>
                    </p>
                </td>
            </tr>

            <!-- Add missing width/height -->
            <tr>
                <th scope="row">
                    <label for="image_dimensions_add_missing">
                        <?php esc_html_e( 'Add Missing Width &amp; Height', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Scan front-end content and automatically inject the correct width and height attributes on any image missing them. Helps fix the "Ensure images have explicit width and height" warning and reduces Cumulative Layout Shift (CLS).', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[image_dimensions][add_missing_dimensions]" id="image_dimensions_add_missing" value="1" <?php checked( ! empty( $dim_options['add_missing_dimensions'] ) ); ?>>
                    <p class="description"><?php esc_html_e( 'Works on post content, Gutenberg blocks, Elementor widgets, attachment images and post thumbnails. Only local images are measured — external URLs, SVGs and data URIs are skipped. Results are cached for a week.', 'mbr-performance' ); ?></p>
                </td>
            </tr>

            <!-- decoding="async" (v1.12.0) -->
            <tr>
                <th scope="row">
                    <label for="image_decoding_async">
                        <?php esc_html_e( 'Add decoding="async" to Images', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Lets the browser decode images off the main thread, improving INP/responsiveness on image-heavy pages. The LCP candidate (fetchpriority="high") is automatically skipped so it can still decode synchronously.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[image_dimensions][decoding_async]" id="image_decoding_async" value="1" <?php checked( ! empty( $dim_options['decoding_async'] ) ); ?>>
                    <p class="description"><?php esc_html_e( 'New in v1.12.0. Skips images already carrying a decoding or fetchpriority="high" attribute.', 'mbr-performance' ); ?></p>
                </td>
            </tr>

            <!-- Strip EXIF on upload (v1.12.0) -->
            <tr>
                <th scope="row">
                    <label for="image_strip_exif">
                        <?php esc_html_e( 'Strip EXIF Metadata on Upload', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Removes EXIF, IPTC and XMP metadata (camera serial, GPS coordinates, embedded thumbnails) from newly uploaded JPEGs. ICC colour profiles are preserved. Privacy win plus typically a 5-30% file size reduction with zero visible quality loss.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[image_dimensions][strip_exif]" id="image_strip_exif" value="1" <?php checked( ! empty( $dim_options['strip_exif'] ) ); ?>>
                    <p class="description"><?php esc_html_e( 'New in v1.12.0. Only affects new uploads. Existing images are unchanged. Requires Imagick (preferred) or GD.', 'mbr-performance' ); ?></p>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Bulk Resize Existing Images -->
    <h3 style="color: var(--mbr-text-primary); font-size: 14px; font-weight: 600; margin-top: 24px; margin-bottom: 8px;">
        <?php esc_html_e( 'Bulk Resize Existing Images', 'mbr-performance' ); ?>
    </h3>
    <p class="description" style="margin-bottom: 12px;">
        <?php
        printf(
            /* translators: %d: current maximum dimension in pixels. */
            esc_html__( 'Scan the Media Library for JPEG and PNG images larger than %dpx on their longer edge and downscale them in place. Sub-sizes are regenerated automatically afterwards.', 'mbr-performance' ),
            (int) ( isset( $dim_options['max_dimension'] ) ? $dim_options['max_dimension'] : $dim_defaults['max_dimension'] )
        );
        ?>
    </p>
    <p class="description" style="color: var(--mbr-warning); font-weight: 600; margin-bottom: 16px;">
        <?php esc_html_e( '⚠ This permanently overwrites the original files on disk. Take a full backup before proceeding — there is no automatic undo.', 'mbr-performance' ); ?>
    </p>

    <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 16px;">
        <button type="button" id="mbr-imgdim-scan" class="button button-secondary" <?php disabled( empty( $diagnostics['gd_installed'] ) ); ?>>
            <?php esc_html_e( 'Scan Media Library', 'mbr-performance' ); ?>
        </button>
        <button type="button" id="mbr-imgdim-start" class="button button-primary" disabled>
            <?php esc_html_e( 'Start Resize', 'mbr-performance' ); ?>
        </button>
    </div>

    <!-- Progress Bar -->
    <div id="mbr-imgdim-progress-container" style="display: none; margin-bottom: 12px;">
        <div style="background: var(--mbr-bg-input); border: 1px solid var(--mbr-border); border-radius: var(--mbr-radius-sm); overflow: hidden; height: 28px;">
            <div id="mbr-imgdim-progress-bar" style="height: 100%; width: 0%; background: var(--mbr-accent); color: #fff; text-align: center; line-height: 28px; font-size: 12px; font-weight: 600; transition: width 0.3s ease;">0%</div>
        </div>
    </div>

    <!-- Status -->
    <div id="mbr-imgdim-status" style="margin-bottom: 12px; font-size: 13px; color: var(--mbr-text-secondary);"></div>

    <!-- Live Log -->
    <div id="mbr-imgdim-log-wrapper" style="display: none;">
        <h4 style="color: var(--mbr-text-primary); font-size: 13px; font-weight: 600; margin-bottom: 8px;">
            <?php esc_html_e( 'Resize Log', 'mbr-performance' ); ?>
        </h4>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'File', 'mbr-performance' ); ?></th>
                    <th style="width: 130px;"><?php esc_html_e( 'Before', 'mbr-performance' ); ?></th>
                    <th style="width: 130px;"><?php esc_html_e( 'After', 'mbr-performance' ); ?></th>
                    <th style="width: 110px;"><?php esc_html_e( 'Saved', 'mbr-performance' ); ?></th>
                </tr>
            </thead>
            <tbody id="mbr-imgdim-log"></tbody>
        </table>
    </div>
</div>

<!-- Server Diagnostics Section -->
<div class="mbr-performance-section">
    <h2><?php esc_html_e( 'Server Diagnostics', 'mbr-performance' ); ?></h2>

    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row"><?php esc_html_e( 'GD Library', 'mbr-performance' ); ?></th>
                <td>
                    <?php if ( $diagnostics['gd_installed'] ) : ?>
                        <span style="color: var(--mbr-success);">&#10004; <?php esc_html_e( 'Installed', 'mbr-performance' ); ?></span>
                    <?php else : ?>
                        <span style="color: var(--mbr-danger);">&#10008; <?php esc_html_e( 'Not installed — contact your host.', 'mbr-performance' ); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'WebP Support in GD', 'mbr-performance' ); ?></th>
                <td>
                    <?php if ( $diagnostics['webp_support'] ) : ?>
                        <span style="color: var(--mbr-success);">&#10004; <?php esc_html_e( 'Supported', 'mbr-performance' ); ?></span>
                    <?php else : ?>
                        <span style="color: var(--mbr-danger);">&#10008; <?php esc_html_e( 'Not supported — contact your host.', 'mbr-performance' ); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Uploads Folder', 'mbr-performance' ); ?></th>
                <td>
                    <?php if ( $diagnostics['uploads_writable'] ) : ?>
                        <span style="color: var(--mbr-success);">&#10004; <?php esc_html_e( 'Writable', 'mbr-performance' ); ?></span>
                    <?php else : ?>
                        <span style="color: var(--mbr-danger);">&#10008; <?php esc_html_e( 'Not writable — contact your host.', 'mbr-performance' ); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
        </tbody>
    </table>

    <?php if ( ! $diagnostics['all_ok'] ) : ?>
        <p style="color: var(--mbr-danger); font-weight: 600; margin-top: 12px;"><?php esc_html_e( 'Action Required: Resolve the error(s) above before converting images.', 'mbr-performance' ); ?></p>
    <?php else : ?>
        <p style="color: var(--mbr-success); margin-top: 12px;"><?php esc_html_e( 'Your server configuration looks good!', 'mbr-performance' ); ?></p>
    <?php endif; ?>
</div>

<!-- Bulk Converter Section -->
<div class="mbr-performance-section">
    <h2><?php esc_html_e( 'Bulk Converter', 'mbr-performance' ); ?></h2>
    <p class="description" style="margin-bottom: 16px;"><?php esc_html_e( 'Convert existing images in your Media Library that were uploaded before this feature was enabled.', 'mbr-performance' ); ?></p>

    <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 16px;">
        <button type="button" id="mbr-webp-start-conversion" class="button button-primary" <?php disabled( ! $diagnostics['all_ok'] ); ?>>
            <?php esc_html_e( 'Start Conversion', 'mbr-performance' ); ?>
        </button>
        <button type="button" id="mbr-webp-clear-history" class="button button-secondary">
            <?php esc_html_e( 'Clear All History', 'mbr-performance' ); ?>
        </button>
        <button type="button" id="mbr-webp-revert-all" class="button button-secondary" style="border-color: var(--mbr-danger) !important; color: var(--mbr-danger) !important;">
            <?php esc_html_e( 'Revert All WebP Files', 'mbr-performance' ); ?>
        </button>
    </div>
    <p class="description" style="margin-top: -8px; margin-bottom: 16px;"><?php esc_html_e( '"Revert All" deletes every WebP file this plugin created and clears the conversion history. Original images are never touched. Files uploaded as WebP are left intact.', 'mbr-performance' ); ?></p>

    <!-- Progress Bar -->
    <div id="mbr-webp-progress-container" style="display: none; margin-bottom: 16px;">
        <div style="background: var(--mbr-bg-input); border: 1px solid var(--mbr-border); border-radius: var(--mbr-radius-sm); overflow: hidden; height: 28px;">
            <div id="mbr-webp-progress-bar" style="height: 100%; width: 0%; background: var(--mbr-accent); color: #fff; text-align: center; line-height: 28px; font-size: 12px; font-weight: 600; transition: width 0.3s ease;">0%</div>
        </div>
    </div>

    <!-- Status -->
    <div id="mbr-webp-status" style="margin-bottom: 16px; font-size: 13px; color: var(--mbr-text-secondary);"></div>

    <?php if ( $avif_supported ) : ?>
    <!-- AVIF Bulk Converter (only renders when the server can actually encode AVIF) -->
    <h3 style="color: var(--mbr-text-primary); font-size: 14px; font-weight: 600; margin: 24px 0 12px;">
        <?php esc_html_e( 'AVIF Bulk Converter', 'mbr-performance' ); ?>
    </h3>
    <p class="description" style="margin-bottom: 12px;">
        <?php esc_html_e( 'Convert existing JPG/PNG images in the Media Library to AVIF alongside any WebP variant. The picture wrapper will then serve AVIF to capable browsers and fall back to WebP / the original elsewhere.', 'mbr-performance' ); ?>
    </p>

    <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 16px;">
        <button type="button" id="mbr-avif-start-conversion" class="button button-primary">
            <?php esc_html_e( 'Start AVIF Conversion', 'mbr-performance' ); ?>
        </button>
        <button type="button" id="mbr-avif-clear-history" class="button button-secondary">
            <?php esc_html_e( 'Clear AVIF History', 'mbr-performance' ); ?>
        </button>
        <button type="button" id="mbr-avif-revert-all" class="button button-secondary" style="border-color: var(--mbr-danger) !important; color: var(--mbr-danger) !important;">
            <?php esc_html_e( 'Revert All AVIF Files', 'mbr-performance' ); ?>
        </button>
    </div>
    <p class="description" style="margin-top: -8px; margin-bottom: 16px;">
        <?php esc_html_e( '"Revert All AVIF Files" deletes every .avif this plugin created and clears AVIF history. Originals and WebP variants are not touched.', 'mbr-performance' ); ?>
    </p>

    <div id="mbr-avif-progress-container" style="display: none; margin-bottom: 16px;">
        <div style="background: var(--mbr-bg-input); border: 1px solid var(--mbr-border); border-radius: var(--mbr-radius-sm); overflow: hidden; height: 28px;">
            <div id="mbr-avif-progress-bar" style="height: 100%; width: 0%; background: var(--mbr-accent); color: #fff; text-align: center; line-height: 28px; font-size: 12px; font-weight: 600; transition: width 0.3s ease;">0%</div>
        </div>
    </div>

    <div id="mbr-avif-status" style="margin-bottom: 16px; font-size: 13px; color: var(--mbr-text-secondary);"></div>
    <?php endif; ?>

    <!-- Converted Images History -->
    <h3 style="color: var(--mbr-text-primary); font-size: 14px; font-weight: 600; margin-bottom: 12px;"><?php esc_html_e( 'Conversion History', 'mbr-performance' ); ?></h3>

    <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 12px;">
        <select id="mbr-webp-bulk-action">
            <option value="-1"><?php esc_html_e( 'Bulk Actions', 'mbr-performance' ); ?></option>
            <option value="delete"><?php esc_html_e( 'Remove from History', 'mbr-performance' ); ?></option>
        </select>
        <button type="button" id="mbr-webp-apply-bulk" class="button"><?php esc_html_e( 'Apply', 'mbr-performance' ); ?></button>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <td class="manage-column column-cb check-column" style="width: 40px;">
                    <input id="mbr-webp-select-all" type="checkbox">
                </td>
                <th><?php esc_html_e( 'Original Image', 'mbr-performance' ); ?></th>
                <th style="width: 110px;"><?php esc_html_e( 'Original Size', 'mbr-performance' ); ?></th>
                <th style="width: 110px;"><?php esc_html_e( 'WebP Size', 'mbr-performance' ); ?></th>
                <th style="width: 110px;"><?php esc_html_e( 'AVIF Size', 'mbr-performance' ); ?></th>
                <th style="width: 140px;"><?php esc_html_e( 'Compression', 'mbr-performance' ); ?></th>
            </tr>
        </thead>
        <tbody id="mbr-webp-history-list">
            <?php
            // Merge WebP and AVIF history options into a single map keyed
            // by original path so each image is one row with whichever
            // format data exists. WebP data is read with the same legacy
            // fallback the previous version used (itwc_converted_images
            // from the standalone WebP plugin).
            $webp_history = get_option( 'mbrpe_webp_converted_images', array() );
            if ( empty( $webp_history ) ) {
                $webp_history = get_option( 'itwc_converted_images', array() );
            }
            $avif_history = get_option( 'mbrpe_avif_converted_images', array() );

            $merged = array();
            if ( is_array( $webp_history ) ) {
                foreach ( $webp_history as $w ) {
                    if ( empty( $w['original_path'] ) ) {
                        continue;
                    }
                    $merged[ $w['original_path'] ] = array(
                        'original_path' => $w['original_path'],
                        'original_size' => isset( $w['original_size'] ) ? (int) $w['original_size'] : 0,
                        'webp_size'     => isset( $w['webp_size'] ) ? (int) $w['webp_size'] : null,
                        'avif_size'     => null,
                    );
                }
            }
            if ( is_array( $avif_history ) ) {
                foreach ( $avif_history as $a ) {
                    if ( empty( $a['original_path'] ) ) {
                        continue;
                    }
                    $path = $a['original_path'];
                    if ( ! isset( $merged[ $path ] ) ) {
                        $merged[ $path ] = array(
                            'original_path' => $path,
                            'original_size' => isset( $a['original_size'] ) ? (int) $a['original_size'] : 0,
                            'webp_size'     => null,
                            'avif_size'     => isset( $a['avif_size'] ) ? (int) $a['avif_size'] : null,
                        );
                    } else {
                        $merged[ $path ]['avif_size'] = isset( $a['avif_size'] ) ? (int) $a['avif_size'] : null;
                        if ( empty( $merged[ $path ]['original_size'] ) && ! empty( $a['original_size'] ) ) {
                            $merged[ $path ]['original_size'] = (int) $a['original_size'];
                        }
                    }
                }
            }

            if ( ! empty( $merged ) ) :
                foreach ( array_reverse( $merged ) as $row ) :
                    $filename = basename( $row['original_path'] );
                    $full_url = $upload_base . '/' . $row['original_path'];

                    // Compression metric reflects whichever format is the
                    // smallest available (AVIF when present, since it's
                    // smaller; otherwise WebP). Falls back to N/A if
                    // neither has been recorded against this entry.
                    $best = null;
                    if ( null !== $row['avif_size'] && null !== $row['webp_size'] ) {
                        $best = min( $row['avif_size'], $row['webp_size'] );
                    } elseif ( null !== $row['avif_size'] ) {
                        $best = $row['avif_size'];
                    } elseif ( null !== $row['webp_size'] ) {
                        $best = $row['webp_size'];
                    }
                    $compression_text = 'N/A';
                    if ( null !== $best && $row['original_size'] > 0 ) {
                        $compression      = ( ( $row['original_size'] - $best ) / $row['original_size'] ) * 100;
                        $compression_text = number_format( $compression, 2 ) . '%';
                    }
                    ?>
                    <tr>
                        <th scope="row" class="check-column">
                            <input type="checkbox" class="mbr-webp-item-checkbox" value="<?php echo esc_attr( $row['original_path'] ); ?>">
                        </th>
                        <td><a href="<?php echo esc_url( $full_url ); ?>" target="_blank"><?php echo esc_html( $filename ); ?></a></td>
                        <td><?php echo esc_html( size_format( $row['original_size'], 2 ) ); ?></td>
                        <td><?php echo null !== $row['webp_size'] ? esc_html( size_format( $row['webp_size'], 2 ) ) : '—'; ?></td>
                        <td><?php echo null !== $row['avif_size'] ? esc_html( size_format( $row['avif_size'], 2 ) ) : '—'; ?></td>
                        <td><?php echo esc_html( $compression_text ); ?></td>
                    </tr>
                    <?php
                endforeach;
            else :
                ?>
                <tr><td colspan="6"><?php esc_html_e( 'No images converted yet.', 'mbr-performance' ); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
