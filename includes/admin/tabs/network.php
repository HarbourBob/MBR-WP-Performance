<?php
/**
 * Network Admin Settings Tab
 *
 * Renders the super-admin settings page for managing
 * network-wide defaults and pushing settings to sites.
 *
 * @package MBRPE
 * @since   1.5.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$network_options = MBRPE_Multisite::get_network_options();
$allow_overrides = MBRPE_Multisite::allow_site_overrides();
$sites           = get_sites( array( 'number' => 200 ) );
?>

<div class="mbr-performance-network-settings">

    <!-- Overview card -->
    <div class="card" style="max-width:800px;margin-bottom:20px;">
        <h2><?php esc_html_e( 'Network Overview', 'mbr-performance' ); ?></h2>
        <p class="description">
            <?php esc_html_e( 'Manage default performance settings for every site on this network. You can push these defaults to all sites at once, or allow individual site admins to override them.', 'mbr-performance' ); ?>
        </p>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e( 'Sites on network', 'mbr-performance' ); ?></th>
                <td><strong><?php echo count( $sites ); ?></strong></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Network defaults saved', 'mbr-performance' ); ?></th>
                <td>
                    <?php if ( ! empty( $network_options ) ) : ?>
                        <span style="color:#00a32a;font-weight:600;"><?php esc_html_e( 'Yes', 'mbr-performance' ); ?></span>
                    <?php else : ?>
                        <span style="color:#d63638;font-weight:600;"><?php esc_html_e( 'Not yet', 'mbr-performance' ); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>

    <!-- Per-site override toggle -->
    <div class="card" style="max-width:800px;margin-bottom:20px;">
        <h2><?php esc_html_e( 'Per-Site Overrides', 'mbr-performance' ); ?></h2>
        <p class="description">
            <?php esc_html_e( 'When enabled, individual site admins can customise performance settings for their own site instead of using the network defaults.', 'mbr-performance' ); ?>
        </p>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="mbr-allow-overrides"><?php esc_html_e( 'Allow per-site overrides', 'mbr-performance' ); ?></label>
                </th>
                <td>
                    <label class="mbr-toggle">
                        <input type="checkbox"
                               id="mbr-allow-overrides"
                               name="allow_site_overrides"
                               value="1"
                               <?php checked( $allow_overrides ); ?>>
                        <span class="mbr-toggle-slider"></span>
                    </label>
                    <p class="description">
                        <?php esc_html_e( 'If disabled, all sites will use the network defaults and the settings page will be read-only for site admins.', 'mbr-performance' ); ?>
                    </p>
                </td>
            </tr>
        </table>
    </div>

    <!-- Import / set network defaults -->
    <div class="card" style="max-width:800px;margin-bottom:20px;">
        <h2><?php esc_html_e( 'Network Default Settings', 'mbr-performance' ); ?></h2>
        <p class="description">
            <?php esc_html_e( 'Import settings from an existing site to use as the network defaults, or configure them from scratch on any site and then push them here.', 'mbr-performance' ); ?>
        </p>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="mbr-import-site"><?php esc_html_e( 'Import from site', 'mbr-performance' ); ?></label>
                </th>
                <td>
                    <select id="mbr-import-site">
                        <option value=""><?php esc_html_e( '— Select a site —', 'mbr-performance' ); ?></option>
                        <?php foreach ( $sites as $site ) : ?>
                            <option value="<?php echo esc_attr( $site->blog_id ); ?>">
                                <?php echo esc_html( $site->domain . $site->path ); ?> (ID&nbsp;<?php echo esc_html( $site->blog_id ); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button"
                            class="button button-secondary"
                            id="mbr-import-from-site">
                        <?php esc_html_e( 'Import Settings', 'mbr-performance' ); ?>
                    </button>
                    <span class="spinner" id="mbr-import-spinner" style="float:none;"></span>
                    <p class="description">
                        <?php esc_html_e( 'Copies the selected site\'s current MBR Performance settings and saves them as the network defaults.', 'mbr-performance' ); ?>
                    </p>
                </td>
            </tr>
        </table>
    </div>

    <!-- Push to sites -->
    <div class="card" style="max-width:800px;margin-bottom:20px;">
        <h2><?php esc_html_e( 'Push Settings to Sites', 'mbr-performance' ); ?></h2>
        <p class="description">
            <?php esc_html_e( 'Push the current network defaults to sites on the network. This will overwrite each site\'s existing settings.', 'mbr-performance' ); ?>
        </p>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e( 'Target', 'mbr-performance' ); ?></th>
                <td>
                    <fieldset>
                        <label>
                            <input type="radio" name="push_mode" value="all" checked>
                            <?php esc_html_e( 'All sites', 'mbr-performance' ); ?>
                        </label>
                        <br>
                        <label>
                            <input type="radio" name="push_mode" value="selected">
                            <?php esc_html_e( 'Selected sites only', 'mbr-performance' ); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>
            <tr id="mbr-site-selection-row" style="display:none;">
                <th scope="row">
                    <label><?php esc_html_e( 'Select sites', 'mbr-performance' ); ?></label>
                </th>
                <td>
                    <div style="max-height:200px;overflow-y:auto;border:1px solid #c3c4c7;padding:8px;background:#fff;">
                        <?php foreach ( $sites as $site ) : ?>
                            <label style="display:block;margin-bottom:4px;">
                                <input type="checkbox" name="push_site_ids[]" value="<?php echo esc_attr( $site->blog_id ); ?>">
                                <?php echo esc_html( $site->domain . $site->path ); ?> (ID&nbsp;<?php echo esc_html( $site->blog_id ); ?>)
                            </label>
                        <?php endforeach; ?>
                    </div>
                </td>
            </tr>
        </table>

        <p>
            <button type="button"
                    class="button button-primary"
                    id="mbr-push-to-sites">
                <?php esc_html_e( 'Push Network Defaults', 'mbr-performance' ); ?>
            </button>
            <span class="spinner" id="mbr-push-spinner" style="float:none;"></span>
        </p>
        <div id="mbr-push-result" style="margin-top:10px;"></div>
    </div>

</div>

<?php ob_start(); ?>
jQuery( function( $ ) {
    // Toggle site selection list.
    $( 'input[name="push_mode"]' ).on( 'change', function() {
        $( '#mbr-site-selection-row' ).toggle( $( this ).val() === 'selected' );
    });

    // Import from site.
    $( '#mbr-import-from-site' ).on( 'click', function() {
        var siteId = $( '#mbr-import-site' ).val();
        if ( ! siteId ) {
            alert( '<?php echo esc_js( __( 'Please select a site first.', 'mbr-performance' ) ); ?>' );
            return;
        }

        if ( ! confirm( '<?php echo esc_js( __( 'This will overwrite the current network defaults with the settings from the selected site. Continue?', 'mbr-performance' ) ); ?>' ) ) {
            return;
        }

        var $spinner = $( '#mbr-import-spinner' ).addClass( 'is-active' );

        $.post( mbrpeData.ajaxUrl, {
            action: 'mbrpe_import_site_settings',
            nonce:  mbrpeData.nonce,
            site_id: siteId
        }, function( response ) {
            $spinner.removeClass( 'is-active' );
            alert( response.data.message );
            if ( response.success ) {
                location.reload();
            }
        }).fail( function() {
            $spinner.removeClass( 'is-active' );
            alert( '<?php echo esc_js( __( 'Request failed. Please try again.', 'mbr-performance' ) ); ?>' );
        });
    });

    // Save overrides toggle.
    $( '#mbr-allow-overrides' ).on( 'change', function() {
        $.post( mbrpeData.ajaxUrl, {
            action: 'mbrpe_save_network_settings',
            nonce:  mbrpeData.nonce,
            options: {},
            allow_site_overrides: $( this ).is( ':checked' ) ? 1 : 0
        }, function( response ) {
            if ( response.success ) {
                // Silently saved.
            }
        });
    });

    // Push to sites.
    $( '#mbr-push-to-sites' ).on( 'click', function() {
        var mode = $( 'input[name="push_mode"]:checked' ).val();
        var siteIds = [];

        if ( mode === 'selected' ) {
            $( 'input[name="push_site_ids[]"]:checked' ).each( function() {
                siteIds.push( $( this ).val() );
            });

            if ( siteIds.length === 0 ) {
                alert( '<?php echo esc_js( __( 'Please select at least one site.', 'mbr-performance' ) ); ?>' );
                return;
            }
        }

        var target = mode === 'all'
            ? '<?php echo esc_js( __( 'ALL sites', 'mbr-performance' ) ); ?>'
            : siteIds.length + ' <?php echo esc_js( __( 'selected site(s)', 'mbr-performance' ) ); ?>';

        if ( ! confirm( '<?php echo esc_js( __( 'This will overwrite settings on ', 'mbr-performance' ) ); ?>' + target + '. <?php echo esc_js( __( 'Continue?', 'mbr-performance' ) ); ?>' ) ) {
            return;
        }

        var $spinner = $( '#mbr-push-spinner' ).addClass( 'is-active' );
        var $result  = $( '#mbr-push-result' );

        $.post( mbrpeData.ajaxUrl, {
            action:   'mbrpe_push_to_sites',
            nonce:    mbrpeData.nonce,
            push_mode: mode,
            site_ids:  siteIds
        }, function( response ) {
            $spinner.removeClass( 'is-active' );

            if ( response.success ) {
                $result.html( '<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>' );
            } else {
                $result.html( '<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>' );
            }
        }).fail( function() {
            $spinner.removeClass( 'is-active' );
            $result.html( '<div class="notice notice-error inline"><p><?php echo esc_js( __( 'Request failed. Please try again.', 'mbr-performance' ) ); ?></p></div>' );
        });
    });
});
<?php
$mbr_network_js = ob_get_clean();
wp_add_inline_script( 'mbr-performance-network-admin', $mbr_network_js );
?>
