<?php
/**
 * Third-Party Scripts tab
 *
 * @package MBR_WP_Performance
 * @since   1.12.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$tp       = isset( $options['third_party'] ) ? $options['third_party'] : array();
$instance = MBR_WP_Performance_Third_Party_Scripts::instance();
$catalog  = $instance->get_catalog();
$last     = MBR_WP_Performance_Third_Party_Scripts::get_last_refresh();
?>
<div class="mbr-wp-performance-tab-content">

    <div class="mbr-wp-performance-section">
        <h2><?php esc_html_e( 'Self-Hosted Third-Party Scripts', 'mbr-wp-performance' ); ?></h2>
        <p class="description">
            <?php esc_html_e( 'Caches Google Analytics, Tag Manager and Facebook Pixel locally and rewrites <script src=> URLs so the browser does not need to fetch them from third-party servers on every page load. Files refresh daily.', 'mbr-wp-performance' ); ?>
        </p>

        <table class="form-table">
            <?php foreach ( $catalog as $key => $entry ) : ?>
            <tr>
                <th scope="row">
                    <label for="<?php echo esc_attr( $key ); ?>">
                        <?php echo esc_html( $entry['label'] ); ?>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[third_party][<?php echo esc_attr( $key ); ?>]" id="<?php echo esc_attr( $key ); ?>" value="1" <?php checked( ! empty( $tp[ $key ] ) ); ?>>
                    <p class="description">
                        <?php
                        /* translators: %s = remote URL */
                        printf( esc_html__( 'Source: %s', 'mbr-wp-performance' ), '<code>' . esc_html( $entry['remote'] ) . '</code>' );
                        ?>
                    </p>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="mbr-wp-performance-section">
        <h2><?php esc_html_e( 'Cache Status', 'mbr-wp-performance' ); ?></h2>
        <p>
            <button type="button" class="button button-secondary" id="mbr-refresh-third-party"><?php esc_html_e( 'Refresh Now', 'mbr-wp-performance' ); ?></button>
            <span id="mbr-tp-refresh-status" style="margin-left:.6em;"></span>
        </p>
        <?php if ( ! empty( $last['time'] ) ) : ?>
        <p class="description">
            <?php
            /* translators: %s = human-readable time */
            printf( esc_html__( 'Last refresh: %s ago', 'mbr-wp-performance' ), esc_html( human_time_diff( $last['time'], time() ) ) );
            ?>
        </p>
        <?php if ( ! empty( $last['results'] ) ) : ?>
        <table class="widefat striped" style="max-width:600px;">
            <thead><tr><th><?php esc_html_e( 'Script', 'mbr-wp-performance' ); ?></th><th><?php esc_html_e( 'Status', 'mbr-wp-performance' ); ?></th></tr></thead>
            <tbody>
            <?php foreach ( $last['results'] as $key => $status ) : ?>
                <tr>
                    <td><?php echo esc_html( isset( $catalog[ $key ] ) ? $catalog[ $key ]['label'] : $key ); ?></td>
                    <td><code><?php echo esc_html( $status ); ?></code></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        <?php else : ?>
        <p class="description"><?php esc_html_e( 'No refresh has run yet. Enable a script above and save, then click Refresh Now.', 'mbr-wp-performance' ); ?></p>
        <?php endif; ?>
    </div>

</div>

<script>
(function(){
    var btn=document.getElementById('mbr-refresh-third-party');
    if(!btn) return;
    btn.addEventListener('click',function(){
        var s=document.getElementById('mbr-tp-refresh-status');
        s.textContent='<?php echo esc_js( __( 'Refreshing…', 'mbr-wp-performance' ) ); ?>';
        var d=new FormData();
        d.append('action','mbr_wp_performance_third_party_refresh');
        d.append('nonce','<?php echo esc_js( wp_create_nonce( 'mbr_wp_performance_nonce' ) ); ?>');
        fetch(ajaxurl,{method:'POST',body:d,credentials:'same-origin'})
            .then(function(r){return r.json();})
            .then(function(j){
                if(j&&j.success){
                    s.textContent='<?php echo esc_js( __( 'Done. Reloading…', 'mbr-wp-performance' ) ); ?>';
                    setTimeout(function(){location.reload();},700);
                } else {
                    s.textContent='<?php echo esc_js( __( 'Refresh failed.', 'mbr-wp-performance' ) ); ?>';
                }
            })
            .catch(function(){ s.textContent='<?php echo esc_js( __( 'Refresh failed.', 'mbr-wp-performance' ) ); ?>'; });
    });
})();
</script>
