<?php
/**
 * A/B Testing Admin View
 *
 * @package MBR_Cookie_Consent
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$enabled = MBR_CC_AB_Testing::is_enabled();
$stats   = MBR_CC_AB_Testing::get_stats();
$winner  = MBR_CC_AB_Testing::get_winner();
$labels  = MBR_CC_AB_Testing::VARIANT_LABELS;
$total_impressions = array_sum( array_column( $stats, 'impressions' ) );
?>

<div class="wrap mbr-cc-admin-wrap">
    <h1><?php esc_html_e( 'A/B Testing', 'mbr-cookie-consent' ); ?></h1>

    <!-- Enable / disable toggle -->
    <div class="mbr-cc-settings-section">
        <h2><?php esc_html_e( 'Test Configuration', 'mbr-cookie-consent' ); ?></h2>
        <p><?php esc_html_e( 'When enabled, visitors are randomly assigned to one of three banner position variants. The variant with the highest accept-all rate is the winner.', 'mbr-cookie-consent' ); ?></p>

        <table class="form-table">
            <tr>
                <th><?php esc_html_e( 'Enable A/B Testing', 'mbr-cookie-consent' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               id="mbr-cc-ab-enabled"
                               <?php checked( $enabled ); ?> />
                        <?php esc_html_e( 'Randomly assign visitors to banner position variants', 'mbr-cookie-consent' ); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e( 'Variant A: Bottom bar &nbsp;|&nbsp; Variant B: Popup &nbsp;|&nbsp; Variant C: Box left', 'mbr-cookie-consent' ); ?>
                    </p>
                </td>
            </tr>
        </table>

        <button type="button" id="mbr-cc-ab-save-enabled" class="button button-primary">
            <?php esc_html_e( 'Save', 'mbr-cookie-consent' ); ?>
        </button>
    </div>

    <!-- Results table -->
    <div class="mbr-cc-settings-section">
        <h2><?php esc_html_e( 'Results', 'mbr-cookie-consent' ); ?></h2>

        <?php if ( $total_impressions === 0 ) : ?>
            <p><?php esc_html_e( 'No data yet. Enable A/B testing and wait for visitors.', 'mbr-cookie-consent' ); ?></p>
        <?php else : ?>
            <table class="widefat" style="max-width:640px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Variant', 'mbr-cookie-consent' ); ?></th>
                        <th><?php esc_html_e( 'Position', 'mbr-cookie-consent' ); ?></th>
                        <th><?php esc_html_e( 'Impressions', 'mbr-cookie-consent' ); ?></th>
                        <th><?php esc_html_e( 'Accept-All', 'mbr-cookie-consent' ); ?></th>
                        <th><?php esc_html_e( 'Accept Rate', 'mbr-cookie-consent' ); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( MBR_CC_AB_Testing::VARIANTS as $key => $position ) :
                        $data        = $stats[ $key ];
                        $impressions = (int) $data['impressions'];
                        $conversions = (int) $data['conversions'];
                        $rate        = $impressions > 0 ? round( ( $conversions / $impressions ) * 100, 1 ) : 0;
                        $is_winner   = ( $winner === $key );
                    ?>
                        <tr<?php echo $is_winner ? ' style="background:#f0fff4;"' : ''; ?>>
                            <td>
                                <strong><?php echo esc_html( $labels[ $key ] ); ?></strong>
                                <?php if ( $is_winner ) : ?>
                                    <span style="color:#16a34a;margin-left:6px;">&#9733; <?php esc_html_e( 'Winner', 'mbr-cookie-consent' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><code><?php echo esc_html( $position ); ?></code></td>
                            <td><?php echo number_format( $impressions ); ?></td>
                            <td><?php echo number_format( $conversions ); ?></td>
                            <td>
                                <strong><?php echo esc_html( $rate ); ?>%</strong>
                                <?php if ( $impressions > 0 ) : ?>
                                    <div style="background:#e5e7eb;border-radius:3px;height:6px;margin-top:4px;width:120px;">
                                        <div style="background:<?php echo $is_winner ? '#16a34a' : '#0073aa'; ?>;border-radius:3px;height:6px;width:<?php echo esc_attr( min( $rate, 100 ) ); ?>%;"></div>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ( $is_winner && $impressions >= 10 ) : ?>
                                    <button type="button"
                                            class="button button-primary mbr-cc-ab-promote"
                                            data-variant="<?php echo esc_attr( $key ); ?>"
                                            data-label="<?php echo esc_attr( $labels[ $key ] ); ?>">
                                        <?php esc_html_e( 'Promote to Live', 'mbr-cookie-consent' ); ?>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p style="margin-top:12px;color:#6b7280;font-size:13px;">
                <?php printf(
                    /* translators: %s: total number of banner impressions. */
                    esc_html__( 'Total impressions: %s. A winner requires at least 10 impressions per variant.', 'mbr-cookie-consent' ),
                    number_format( $total_impressions )
                ); ?>
            </p>
        <?php endif; ?>

        <button type="button" id="mbr-cc-ab-reset" class="button button-secondary" style="margin-top:12px;">
            <?php esc_html_e( 'Reset Stats', 'mbr-cookie-consent' ); ?>
        </button>
    </div>
</div>
