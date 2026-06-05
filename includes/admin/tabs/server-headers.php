<?php
/**
 * Server tab — browser cache + compression .htaccess rules
 *
 * @package MBRPE
 * @since   1.12.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$srv = isset( $options['server_headers'] ) ? $options['server_headers'] : array();
$server = MBRPE_Server_Headers::detect_server();
$is_apache_like = in_array( $server, array( 'apache', 'litespeed' ), true );
?>
<div class="mbr-performance-tab-content">

    <div class="mbr-performance-section">
        <h2><?php esc_html_e( 'Server', 'mbr-performance' ); ?></h2>
        <p class="description">
            <?php
            printf(
                /* translators: %s = detected server name */
                esc_html__( 'Detected web server: %s', 'mbr-performance' ),
                '<code>' . esc_html( $server ) . '</code>'
            );
            ?>
        </p>
        <?php if ( ! $is_apache_like ) : ?>
            <div class="notice notice-info inline" style="margin:1em 0;">
                <p><?php esc_html_e( 'These rules write to .htaccess, which only Apache and LiteSpeed read. On Nginx or IIS you will need to apply the equivalent server config manually — a copy-ready snippet is shown below.', 'mbr-performance' ); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <div class="mbr-performance-section">
        <h2><?php esc_html_e( 'Browser Cache Headers', 'mbr-performance' ); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="browser_cache">
                        <?php esc_html_e( 'Enable Browser Cache Headers', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Adds Expires + Cache-Control headers so browsers cache static assets aggressively. 1 year for images/fonts, 30 days for CSS/JS.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[server_headers][browser_cache]" id="browser_cache" value="1" <?php checked( ! empty( $srv['browser_cache'] ) ); ?>>
                    <p class="description"><?php esc_html_e( 'Fixes the PageSpeed "Serve static assets with an efficient cache policy" warning.', 'mbr-performance' ); ?></p>
                </td>
            </tr>
        </table>
    </div>

    <div class="mbr-performance-section">
        <h2><?php esc_html_e( 'Text Compression', 'mbr-performance' ); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="gzip_compression">
                        <?php esc_html_e( 'Enable Brotli / Gzip Compression', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Adds AddOutputFilterByType rules for mod_brotli and mod_deflate covering HTML, CSS, JS, JSON, SVG and font files.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[server_headers][gzip_compression]" id="gzip_compression" value="1" <?php checked( ! empty( $srv['gzip_compression'] ) ); ?>>
                    <p class="description"><?php esc_html_e( 'Fixes the PageSpeed "Enable text compression" warning. Brotli (where available) is preferred over Gzip.', 'mbr-performance' ); ?></p>
                </td>
            </tr>
        </table>
    </div>

    <?php if ( 'nginx' === $server ) : ?>
    <div class="mbr-performance-section">
        <h2><?php esc_html_e( 'Equivalent Nginx Configuration', 'mbr-performance' ); ?></h2>
        <p class="description"><?php esc_html_e( 'Copy this into your site\'s Nginx server block (typically /etc/nginx/sites-available/...). Brotli requires the ngx_brotli module; otherwise leave gzip enabled.', 'mbr-performance' ); ?></p>
        <textarea readonly rows="14" style="width:100%;font-family:monospace;font-size:12px;"><?php echo esc_textarea( MBRPE_Server_Headers::nginx_snippet() ); ?></textarea>
    </div>
    <?php endif; ?>

</div>
