<?php
/**
 * Diagnostics tab — plugin conflicts, OPcache, autoload audit, cron viewer
 *
 * @package MBRPE
 * @since   1.12.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$autoload_total      = MBRPE_Autoload_Audit::total_autoloaded_size();
$autoload_count      = MBRPE_Autoload_Audit::total_autoloaded_count();
$autoload_top        = MBRPE_Autoload_Audit::top_autoloaded( 30 );
$cron_events         = MBRPE_Cron_Viewer::get_events();
$cron_orphaned_count = count( MBRPE_Cron_Viewer::get_orphaned() );
$cron_disabled       = MBRPE_Cron_Viewer::wp_cron_disabled();
$conflicts           = MBRPE_Conflict_Detector::get_active_conflicts();
?>
<div class="mbr-performance-tab-content">

    <div class="mbr-performance-section">
        <h2><?php esc_html_e( 'Caching Plugin Conflicts', 'mbr-performance' ); ?></h2>
        <?php if ( empty( $conflicts ) ) : ?>
            <p><?php esc_html_e( 'No known caching plugins detected. Good to go.', 'mbr-performance' ); ?></p>
        <?php else : ?>
            <?php foreach ( $conflicts as $entry ) :
                $hits = MBRPE_Conflict_Detector::active_overlaps( $entry );
            ?>
                <h3><?php echo esc_html( $entry['label'] ); ?> <?php esc_html_e( 'is active', 'mbr-performance' ); ?></h3>
                <?php if ( empty( $hits ) ) : ?>
                    <p class="description"><?php esc_html_e( 'No overlapping settings enabled in MBR Performance. You\'re fine.', 'mbr-performance' ); ?></p>
                <?php else : ?>
                    <p><?php esc_html_e( 'The following options overlap with this plugin. Disable them either here or in the other plugin — not both:', 'mbr-performance' ); ?></p>
                    <ul style="list-style:disc;margin-left:1.5em;">
                    <?php foreach ( $hits as $label ) : ?>
                        <li><?php echo esc_html( $label ); ?></li>
                    <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php
    // ------------------------------------------------------------------
    // OPcache (2.1.0). Server-wide, so on multisite only super admins see
    // the detail and the flush control.
    // ------------------------------------------------------------------
    $mbrpe_oc_visible = ! is_multisite() || is_super_admin();
    if ( $mbrpe_oc_visible && class_exists( 'MBRPE_OPcache' ) ) :
        $oc          = MBRPE_OPcache::status();
        $oc_findings = MBRPE_OPcache::findings( $oc );
        $oc_levels   = array(
            'error'   => 'notice-error',
            'warning' => 'notice-warning',
            'info'    => 'notice-info',
            'success' => 'notice-success',
        );
        $oc_unknown  = __( 'Unknown', 'mbr-performance' );
    ?>
    <div class="mbr-performance-section mbr-opcache">
        <h2><?php esc_html_e( 'OPcache', 'mbr-performance' ); ?></h2>
        <p class="description">
            <?php esc_html_e( 'OPcache keeps compiled PHP in memory so WordPress is not recompiled on every request. It is configured by your host in php.ini, so there are no switches for it here — this panel reports what the server is doing, and lets you flush it.', 'mbr-performance' ); ?>
        </p>

        <?php foreach ( $oc_findings as $finding ) : ?>
            <div class="notice inline <?php echo esc_attr( $oc_levels[ $finding['level'] ] ); ?>" style="margin:1em 0;">
                <p><?php echo esc_html( $finding['message'] ); ?></p>
            </div>
        <?php endforeach; ?>

        <?php if ( in_array( $oc['state'], array( 'file-cache-only', 'restricted', 'hidden' ), true ) ) : ?>
            <table class="widefat striped" style="max-width:720px;">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Mode', 'mbr-performance' ); ?></th>
                        <td>
                            <?php
                            if ( 'file-cache-only' === $oc['state'] ) {
                                esc_html_e( 'File cache only (compiled PHP stored on disk)', 'mbr-performance' );
                            } else {
                                esc_html_e( 'Running; statistics not available to plugins', 'mbr-performance' );
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Checks for changed files', 'mbr-performance' ); ?></th>
                        <td>
                            <?php
                            if ( null === $oc['validate_timestamps'] ) {
                                echo esc_html( $oc_unknown );
                            } elseif ( ! $oc['validate_timestamps'] ) {
                                esc_html_e( 'No (validate_timestamps = 0)', 'mbr-performance' );
                            } elseif ( null !== $oc['revalidate_freq'] ) {
                                printf(
                                    /* translators: %s: number of seconds */
                                    esc_html( _n( 'Yes, at most every %s second', 'Yes, at most every %s seconds', max( 1, (int) $oc['revalidate_freq'] ), 'mbr-performance' ) ),
                                    esc_html( number_format_i18n( (int) $oc['revalidate_freq'] ) )
                                );
                            } else {
                                esc_html_e( 'Yes', 'mbr-performance' );
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Plugin files refreshed on write', 'mbr-performance' ); ?></th>
                        <td>
                            <?php
                            echo esc_html(
                                $oc['can_invalidate']
                                    ? __( 'Yes', 'mbr-performance' )
                                    : __( 'No — the host does not allow it', 'mbr-performance' )
                            );
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if ( 'available' === $oc['state'] ) : ?>
            <?php
            $oc_mem_pct = ( null !== $oc['memory_total'] && $oc['memory_total'] > 0 )
                ? min( 100, round( ( $oc['memory_used'] + $oc['memory_wasted'] ) / $oc['memory_total'] * 100 ) )
                : null;
            $oc_bar     = ( null === $oc_mem_pct ) ? '' : ( $oc_mem_pct >= 90 ? '#d63638' : ( $oc_mem_pct >= 75 ? '#dba617' : '#00a32a' ) );
            ?>
            <table class="widefat striped" style="max-width:720px;">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Hit rate', 'mbr-performance' ); ?></th>
                        <td>
                            <?php
                            echo esc_html(
                                null === $oc['hit_rate']
                                    ? $oc_unknown
                                    /* translators: %s: percentage */
                                    : sprintf( __( '%s%%', 'mbr-performance' ), number_format_i18n( $oc['hit_rate'], 2 ) )
                            );
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Memory', 'mbr-performance' ); ?></th>
                        <td>
                            <?php if ( null === $oc_mem_pct ) : ?>
                                <?php echo esc_html( $oc_unknown ); ?>
                            <?php else : ?>
                                <div style="background:#dcdcde;border-radius:3px;height:10px;max-width:320px;overflow:hidden;margin:4px 0 6px;" role="img" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: percentage */ __( '%d%% of OPcache memory in use', 'mbr-performance' ), $oc_mem_pct ) ); ?>">
                                    <div style="background:<?php echo esc_attr( $oc_bar ); ?>;height:100%;width:<?php echo (int) $oc_mem_pct; ?>%;"></div>
                                </div>
                                <?php
                                printf(
                                    /* translators: 1: used, 2: wasted, 3: free, 4: total — all human-readable sizes */
                                    esc_html__( '%1$s used, %2$s wasted, %3$s free of %4$s', 'mbr-performance' ),
                                    esc_html( size_format( $oc['memory_used'], 1 ) ),
                                    esc_html( size_format( $oc['memory_wasted'], 1 ) ),
                                    esc_html( size_format( $oc['memory_free'], 1 ) ),
                                    esc_html( size_format( $oc['memory_total'], 1 ) )
                                );
                                ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Cached files', 'mbr-performance' ); ?></th>
                        <td>
                            <?php
                            if ( null === $oc['cached_scripts'] ) {
                                echo esc_html( $oc_unknown );
                            } elseif ( null !== $oc['max_keys'] && null !== $oc['cached_keys'] ) {
                                printf(
                                    /* translators: 1: cached scripts, 2: keys used, 3: key limit */
                                    esc_html__( '%1$s scripts (%2$s of %3$s file slots used)', 'mbr-performance' ),
                                    esc_html( number_format_i18n( $oc['cached_scripts'] ) ),
                                    esc_html( number_format_i18n( $oc['cached_keys'] ) ),
                                    esc_html( number_format_i18n( $oc['max_keys'] ) )
                                );
                            } else {
                                echo esc_html( number_format_i18n( $oc['cached_scripts'] ) );
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Restarts', 'mbr-performance' ); ?></th>
                        <td>
                            <?php
                            if ( null === $oc['oom_restarts'] ) {
                                echo esc_html( $oc_unknown );
                            } else {
                                printf(
                                    /* translators: 1: out-of-memory restarts, 2: file-slot restarts, 3: manual restarts */
                                    esc_html__( '%1$s out of memory · %2$s out of file slots · %3$s manual', 'mbr-performance' ),
                                    esc_html( number_format_i18n( (int) $oc['oom_restarts'] ) ),
                                    esc_html( number_format_i18n( (int) $oc['hash_restarts'] ) ),
                                    esc_html( number_format_i18n( (int) $oc['manual_restarts'] ) )
                                );
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Checks for changed files', 'mbr-performance' ); ?></th>
                        <td>
                            <?php
                            if ( null === $oc['validate_timestamps'] ) {
                                echo esc_html( $oc_unknown );
                            } elseif ( ! $oc['validate_timestamps'] ) {
                                esc_html_e( 'No (validate_timestamps = 0)', 'mbr-performance' );
                            } elseif ( null !== $oc['revalidate_freq'] ) {
                                printf(
                                    /* translators: %s: number of seconds */
                                    esc_html( _n( 'Yes, at most every %s second', 'Yes, at most every %s seconds', max( 1, (int) $oc['revalidate_freq'] ), 'mbr-performance' ) ),
                                    esc_html( number_format_i18n( (int) $oc['revalidate_freq'] ) )
                                );
                            } else {
                                esc_html_e( 'Yes', 'mbr-performance' );
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Last restart', 'mbr-performance' ); ?></th>
                        <td>
                            <?php
                            $oc_since = ( ! empty( $oc['last_restart_time'] ) ) ? $oc['last_restart_time'] : $oc['start_time'];
                            if ( empty( $oc_since ) ) {
                                echo esc_html( $oc_unknown );
                            } else {
                                printf(
                                    /* translators: %s: human-readable time difference, e.g. "3 hours" */
                                    esc_html__( '%s ago', 'mbr-performance' ),
                                    esc_html( human_time_diff( (int) $oc_since, time() ) )
                                );
                            }
                            if ( ! empty( $oc['last_manual_reset']['time'] ) ) {
                                echo ' &middot; ';
                                printf(
                                    /* translators: %s: human-readable time difference */
                                    esc_html__( 'last flushed from this plugin %s ago', 'mbr-performance' ),
                                    esc_html( human_time_diff( (int) $oc['last_manual_reset']['time'], time() ) )
                                );
                            }
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <?php if ( $oc['restart_pending'] ) : ?>
                <p class="description"><?php esc_html_e( 'A flush has been requested and will complete on the next request.', 'mbr-performance' ); ?></p>
            <?php endif; ?>

            <?php if ( $oc['can_reset'] && MBRPE_OPcache::current_user_can_flush() ) : ?>
                <p style="margin-top:1em;">
                    <a href="<?php echo esc_url( MBRPE_OPcache::reset_url() ); ?>"
                       class="button button-secondary"
                       onclick="return window.confirm('<?php echo esc_js( __( 'Flush OPcache? Compiled PHP for this server\'s PHP pool is cleared and rebuilt as pages are requested. On shared hosting that may include other sites in the same pool. Nothing is lost; the next few requests are slightly slower.', 'mbr-performance' ) ); ?>');">
                        <?php esc_html_e( 'Flush OPcache', 'mbr-performance' ); ?>
                    </a>
                </p>
                <p class="description">
                    <?php esc_html_e( 'Updates made through WordPress, and the files this plugin writes itself, are refreshed automatically. Use this after editing or uploading PHP files any other way — SFTP, a file manager, a Git deploy.', 'mbr-performance' ); ?>
                </p>
            <?php elseif ( ! $oc['can_reset'] ) : ?>
                <p class="description"><?php esc_html_e( 'Your host has disabled flushing from PHP. Its control panel usually offers the same action.', 'mbr-performance' ); ?></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="mbr-performance-section">
        <h2><?php esc_html_e( 'Autoloaded Options Audit', 'mbr-performance' ); ?></h2>
        <p class="description">
            <?php
            printf(
                /* translators: 1 = byte size, 2 = count */
                esc_html__( 'Total autoloaded: %1$s across %2$d options. Anything over 1MB is worth reviewing — every autoloaded option is read on every page load.', 'mbr-performance' ),
                '<strong>' . esc_html( size_format( $autoload_total ) ) . '</strong>',
                (int) $autoload_count
            );
            ?>
        </p>

        <table class="widefat striped" style="max-width:900px;">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Option Name', 'mbr-performance' ); ?></th>
                    <th><?php esc_html_e( 'Size', 'mbr-performance' ); ?></th>
                    <th><?php esc_html_e( 'Notes', 'mbr-performance' ); ?></th>
                    <th><?php esc_html_e( 'Action', 'mbr-performance' ); ?></th>
                </tr>
            </thead>
            <tbody id="mbr-autoload-tbody">
            <?php foreach ( $autoload_top as $row ) : ?>
                <tr data-name="<?php echo esc_attr( $row->option_name ); ?>">
                    <td><code style="word-break:break-all;"><?php echo esc_html( $row->option_name ); ?></code></td>
                    <td><?php echo esc_html( $row->size_h ); ?></td>
                    <td>
                        <?php if ( $row->is_protected ) : ?>
                            <em><?php esc_html_e( 'Core (protected)', 'mbr-performance' ); ?></em>
                        <?php elseif ( $row->is_transient ) : ?>
                            <em><?php esc_html_e( 'Transient (should not autoload)', 'mbr-performance' ); ?></em>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ( ! $row->is_protected ) : ?>
                            <button type="button" class="button button-small mbr-autoload-toggle"><?php esc_html_e( 'Disable autoload', 'mbr-performance' ); ?></button>
                        <?php else : ?>
                            <span class="description">&mdash;</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="mbr-performance-section">
        <h2><?php esc_html_e( 'WP-Cron Viewer', 'mbr-performance' ); ?></h2>

        <?php if ( $cron_disabled ) : ?>
            <p><strong><?php esc_html_e( 'WP-Cron is disabled.', 'mbr-performance' ); ?></strong> <?php esc_html_e( 'Good — you\'re running real cron.', 'mbr-performance' ); ?></p>
        <?php else : ?>
            <p>
                <?php esc_html_e( 'WP-Cron currently runs on every page hit. For high-traffic sites or low-traffic sites with delayed events, replacing it with a real system cron is faster and more reliable.', 'mbr-performance' ); ?>
            </p>
            <p><?php esc_html_e( 'Step 1: add this to wp-config.php:', 'mbr-performance' ); ?></p>
            <pre style="background:#f6f7f7;padding:.6em .8em;border-left:3px solid #c3c4c7;"><code>define( 'DISABLE_WP_CRON', true );</code></pre>
            <p><?php esc_html_e( 'Step 2: add a system crontab line:', 'mbr-performance' ); ?></p>
            <pre style="background:#f6f7f7;padding:.6em .8em;border-left:3px solid #c3c4c7;"><code><?php echo esc_html( MBRPE_Cron_Viewer::real_cron_snippet() ); ?></code></pre>
        <?php endif; ?>

        <p class="description" style="margin-top:1em;">
            <?php
            printf(
                /* translators: %d = orphaned event count */
                esc_html__( '%d events scheduled. Events marked "orphaned" have no PHP callback registered — usually left over from deactivated plugins and safe to remove.', 'mbr-performance' ),
                (int) $cron_orphaned_count
            );
            ?>
        </p>

        <table class="widefat striped" style="max-width:1000px;">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Hook', 'mbr-performance' ); ?></th>
                    <th><?php esc_html_e( 'Next Run', 'mbr-performance' ); ?></th>
                    <th><?php esc_html_e( 'Schedule', 'mbr-performance' ); ?></th>
                    <th><?php esc_html_e( 'Callback?', 'mbr-performance' ); ?></th>
                    <th><?php esc_html_e( 'Action', 'mbr-performance' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $cron_events as $event ) : ?>
                <tr data-hook="<?php echo esc_attr( $event['hook'] ); ?>" data-timestamp="<?php echo esc_attr( $event['timestamp'] ); ?>" data-args="<?php echo esc_attr( wp_json_encode( $event['args'] ) ); ?>">
                    <td><code><?php echo esc_html( $event['hook'] ); ?></code></td>
                    <td><?php echo esc_html( $event['next_run_h'] ); ?></td>
                    <td><?php echo esc_html( $event['schedule'] ?: 'one-off' ); ?></td>
                    <td><?php echo $event['has_callback'] ? '✓' : '<span style="color:#d63638;">orphan</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
                    <td>
                        <?php if ( ! $event['has_callback'] ) : ?>
                            <button type="button" class="button button-small mbr-cron-unschedule"><?php esc_html_e( 'Delete', 'mbr-performance' ); ?></button>
                        <?php else : ?>
                            <span class="description">&mdash;</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<?php
ob_start();
?>
(function(){
    var nonce=mbrpeData.nonce;

    function post(action,data,cb){
        var fd=new FormData();
        fd.append('action',action);
        fd.append('nonce',nonce);
        Object.keys(data).forEach(function(k){ fd.append(k,data[k]); });
        fetch(mbrpeData.ajaxUrl,{method:'POST',body:fd,credentials:'same-origin'})
            .then(function(r){return r.json();})
            .then(function(j){ cb(j); })
            .catch(function(){ cb({success:false}); });
    }

    document.querySelectorAll('.mbr-autoload-toggle').forEach(function(btn){
        btn.addEventListener('click',function(){
            var row=btn.closest('tr');
            var name=row.dataset.name;
            btn.disabled=true;
            btn.textContent='<?php echo esc_js( __( 'Working…', 'mbr-performance' ) ); ?>';
            post('mbrpe_autoload_toggle',{option_name:name,autoload:''},function(j){
                if(j.success){
                    btn.textContent='<?php echo esc_js( __( 'Disabled', 'mbr-performance' ) ); ?>';
                    row.style.opacity='.5';
                } else {
                    btn.disabled=false;
                    btn.textContent='<?php echo esc_js( __( 'Failed', 'mbr-performance' ) ); ?>';
                }
            });
        });
    });

    document.querySelectorAll('.mbr-cron-unschedule').forEach(function(btn){
        btn.addEventListener('click',function(){
            var row=btn.closest('tr');
            btn.disabled=true;
            btn.textContent='<?php echo esc_js( __( 'Working…', 'mbr-performance' ) ); ?>';
            post('mbrpe_cron_unschedule',{hook:row.dataset.hook,timestamp:row.dataset.timestamp,args:row.dataset.args},function(j){
                if(j.success){
                    row.parentNode.removeChild(row);
                } else {
                    btn.disabled=false;
                    btn.textContent='<?php echo esc_js( __( 'Failed', 'mbr-performance' ) ); ?>';
                }
            });
        });
    });
})();
<?php
$mbr_diag_js = ob_get_clean();
wp_add_inline_script( 'mbr-performance-admin', $mbr_diag_js );
