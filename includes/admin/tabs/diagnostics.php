<?php
/**
 * Diagnostics tab — autoload audit, cron viewer, plugin conflicts
 *
 * @package MBR_WP_Performance
 * @since   1.12.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$autoload_total      = MBR_WP_Performance_Autoload_Audit::total_autoloaded_size();
$autoload_count      = MBR_WP_Performance_Autoload_Audit::total_autoloaded_count();
$autoload_top        = MBR_WP_Performance_Autoload_Audit::top_autoloaded( 30 );
$cron_events         = MBR_WP_Performance_Cron_Viewer::get_events();
$cron_orphaned_count = count( MBR_WP_Performance_Cron_Viewer::get_orphaned() );
$cron_disabled       = MBR_WP_Performance_Cron_Viewer::wp_cron_disabled();
$conflicts           = MBR_WP_Performance_Conflict_Detector::get_active_conflicts();
?>
<div class="mbr-wp-performance-tab-content">

    <div class="mbr-wp-performance-section">
        <h2><?php esc_html_e( 'Caching Plugin Conflicts', 'mbr-wp-performance' ); ?></h2>
        <?php if ( empty( $conflicts ) ) : ?>
            <p><?php esc_html_e( 'No known caching plugins detected. Good to go.', 'mbr-wp-performance' ); ?></p>
        <?php else : ?>
            <?php foreach ( $conflicts as $entry ) :
                $hits = MBR_WP_Performance_Conflict_Detector::active_overlaps( $entry );
            ?>
                <h3><?php echo esc_html( $entry['label'] ); ?> <?php esc_html_e( 'is active', 'mbr-wp-performance' ); ?></h3>
                <?php if ( empty( $hits ) ) : ?>
                    <p class="description"><?php esc_html_e( 'No overlapping settings enabled in MBR WP Performance. You\'re fine.', 'mbr-wp-performance' ); ?></p>
                <?php else : ?>
                    <p><?php esc_html_e( 'The following options overlap with this plugin. Disable them either here or in the other plugin — not both:', 'mbr-wp-performance' ); ?></p>
                    <ul style="list-style:disc;margin-left:1.5em;">
                    <?php foreach ( $hits as $label ) : ?>
                        <li><?php echo esc_html( $label ); ?></li>
                    <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="mbr-wp-performance-section">
        <h2><?php esc_html_e( 'Autoloaded Options Audit', 'mbr-wp-performance' ); ?></h2>
        <p class="description">
            <?php
            /* translators: 1 = byte size, 2 = count */
            printf(
                esc_html__( 'Total autoloaded: %1$s across %2$d options. Anything over 1MB is worth reviewing — every autoloaded option is read on every page load.', 'mbr-wp-performance' ),
                '<strong>' . esc_html( size_format( $autoload_total ) ) . '</strong>',
                (int) $autoload_count
            );
            ?>
        </p>

        <table class="widefat striped" style="max-width:900px;">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Option Name', 'mbr-wp-performance' ); ?></th>
                    <th><?php esc_html_e( 'Size', 'mbr-wp-performance' ); ?></th>
                    <th><?php esc_html_e( 'Notes', 'mbr-wp-performance' ); ?></th>
                    <th><?php esc_html_e( 'Action', 'mbr-wp-performance' ); ?></th>
                </tr>
            </thead>
            <tbody id="mbr-autoload-tbody">
            <?php foreach ( $autoload_top as $row ) : ?>
                <tr data-name="<?php echo esc_attr( $row->option_name ); ?>">
                    <td><code style="word-break:break-all;"><?php echo esc_html( $row->option_name ); ?></code></td>
                    <td><?php echo esc_html( $row->size_h ); ?></td>
                    <td>
                        <?php if ( $row->is_protected ) : ?>
                            <em><?php esc_html_e( 'Core (protected)', 'mbr-wp-performance' ); ?></em>
                        <?php elseif ( $row->is_transient ) : ?>
                            <em><?php esc_html_e( 'Transient (should not autoload)', 'mbr-wp-performance' ); ?></em>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ( ! $row->is_protected ) : ?>
                            <button type="button" class="button button-small mbr-autoload-toggle"><?php esc_html_e( 'Disable autoload', 'mbr-wp-performance' ); ?></button>
                        <?php else : ?>
                            <span class="description">&mdash;</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="mbr-wp-performance-section">
        <h2><?php esc_html_e( 'WP-Cron Viewer', 'mbr-wp-performance' ); ?></h2>

        <?php if ( $cron_disabled ) : ?>
            <p><strong><?php esc_html_e( 'WP-Cron is disabled.', 'mbr-wp-performance' ); ?></strong> <?php esc_html_e( 'Good — you\'re running real cron.', 'mbr-wp-performance' ); ?></p>
        <?php else : ?>
            <p>
                <?php esc_html_e( 'WP-Cron currently runs on every page hit. For high-traffic sites or low-traffic sites with delayed events, replacing it with a real system cron is faster and more reliable.', 'mbr-wp-performance' ); ?>
            </p>
            <p><?php esc_html_e( 'Step 1: add this to wp-config.php:', 'mbr-wp-performance' ); ?></p>
            <pre style="background:#f6f7f7;padding:.6em .8em;border-left:3px solid #c3c4c7;"><code>define( 'DISABLE_WP_CRON', true );</code></pre>
            <p><?php esc_html_e( 'Step 2: add a system crontab line:', 'mbr-wp-performance' ); ?></p>
            <pre style="background:#f6f7f7;padding:.6em .8em;border-left:3px solid #c3c4c7;"><code><?php echo esc_html( MBR_WP_Performance_Cron_Viewer::real_cron_snippet() ); ?></code></pre>
        <?php endif; ?>

        <p class="description" style="margin-top:1em;">
            <?php
            /* translators: %d = orphaned event count */
            printf(
                esc_html__( '%d events scheduled. Events marked "orphaned" have no PHP callback registered — usually left over from deactivated plugins and safe to remove.', 'mbr-wp-performance' ),
                $cron_orphaned_count
            );
            ?>
        </p>

        <table class="widefat striped" style="max-width:1000px;">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Hook', 'mbr-wp-performance' ); ?></th>
                    <th><?php esc_html_e( 'Next Run', 'mbr-wp-performance' ); ?></th>
                    <th><?php esc_html_e( 'Schedule', 'mbr-wp-performance' ); ?></th>
                    <th><?php esc_html_e( 'Callback?', 'mbr-wp-performance' ); ?></th>
                    <th><?php esc_html_e( 'Action', 'mbr-wp-performance' ); ?></th>
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
                            <button type="button" class="button button-small mbr-cron-unschedule"><?php esc_html_e( 'Delete', 'mbr-wp-performance' ); ?></button>
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

<script>
(function(){
    var nonce='<?php echo esc_js( wp_create_nonce( 'mbr_wp_performance_nonce' ) ); ?>';

    function post(action,data,cb){
        var fd=new FormData();
        fd.append('action',action);
        fd.append('nonce',nonce);
        Object.keys(data).forEach(function(k){ fd.append(k,data[k]); });
        fetch(ajaxurl,{method:'POST',body:fd,credentials:'same-origin'})
            .then(function(r){return r.json();})
            .then(function(j){ cb(j); })
            .catch(function(){ cb({success:false}); });
    }

    document.querySelectorAll('.mbr-autoload-toggle').forEach(function(btn){
        btn.addEventListener('click',function(){
            var row=btn.closest('tr');
            var name=row.dataset.name;
            btn.disabled=true;
            btn.textContent='<?php echo esc_js( __( 'Working…', 'mbr-wp-performance' ) ); ?>';
            post('mbr_wp_performance_autoload_toggle',{option_name:name,autoload:''},function(j){
                if(j.success){
                    btn.textContent='<?php echo esc_js( __( 'Disabled', 'mbr-wp-performance' ) ); ?>';
                    row.style.opacity='.5';
                } else {
                    btn.disabled=false;
                    btn.textContent='<?php echo esc_js( __( 'Failed', 'mbr-wp-performance' ) ); ?>';
                }
            });
        });
    });

    document.querySelectorAll('.mbr-cron-unschedule').forEach(function(btn){
        btn.addEventListener('click',function(){
            var row=btn.closest('tr');
            btn.disabled=true;
            btn.textContent='<?php echo esc_js( __( 'Working…', 'mbr-wp-performance' ) ); ?>';
            post('mbr_wp_performance_cron_unschedule',{hook:row.dataset.hook,timestamp:row.dataset.timestamp,args:row.dataset.args},function(j){
                if(j.success){
                    row.parentNode.removeChild(row);
                } else {
                    btn.disabled=false;
                    btn.textContent='<?php echo esc_js( __( 'Failed', 'mbr-wp-performance' ) ); ?>';
                }
            });
        });
    });
})();
</script>
