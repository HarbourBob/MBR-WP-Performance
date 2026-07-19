<?php
/**
 * Form Integration Admin View
 *
 * @package MBR_Cookie_Consent
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$enabled         = MBR_CC_Form_Integration::is_enabled();
$detected        = MBR_CC_Form_Integration::detect_form_plugins();
$message         = get_option( 'mbr_cc_form_integration_message', __( 'Please accept cookies before submitting this form.', 'mbr-cookie-consent' ) );
$required_cat    = get_option( 'mbr_cc_form_required_category', 'necessary' );
$categories      = MBR_CC_Consent_Manager::get_instance()->get_categories();
?>

<div class="wrap mbr-cc-admin-wrap">
    <h1><?php esc_html_e( 'Form Builder Integration', 'mbr-cookie-consent' ); ?></h1>

    <!-- Detected plugins -->
    <div class="mbr-cc-settings-section">
        <h2><?php esc_html_e( 'Detected Form Plugins', 'mbr-cookie-consent' ); ?></h2>
        <p><?php esc_html_e( 'The following supported form builders were checked on this installation:', 'mbr-cookie-consent' ); ?></p>
        <ul>
            <?php foreach ( $detected as $plugin ) : ?>
                <li>
                    <?php if ( $plugin['active'] ) : ?>
                        <span style="color:#16a34a;">&#10003;</span>
                    <?php else : ?>
                        <span style="color:#9ca3af;">&#8212;</span>
                    <?php endif; ?>
                    &nbsp;<?php echo esc_html( $plugin['name'] ); ?>
                    <?php if ( ! $plugin['active'] ) : ?>
                        <span style="color:#9ca3af;font-size:12px;"><?php esc_html_e( '(not active)', 'mbr-cookie-consent' ); ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Settings -->
    <div class="mbr-cc-settings-section">
        <h2><?php esc_html_e( 'Settings', 'mbr-cookie-consent' ); ?></h2>

        <table class="form-table">
            <tr>
                <th><?php esc_html_e( 'Enable Form Blocking', 'mbr-cookie-consent' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" id="mbr-cc-form-enabled" <?php checked( $enabled ); ?> />
                        <?php esc_html_e( 'Block form submissions until cookie consent is granted', 'mbr-cookie-consent' ); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e( 'When enabled, visitors who have not granted the required cookie consent will see an error message instead of a successful form submission.', 'mbr-cookie-consent' ); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="mbr-cc-form-required-category">
                        <?php esc_html_e( 'Required Consent Category', 'mbr-cookie-consent' ); ?>
                    </label>
                </th>
                <td>
                    <select id="mbr-cc-form-required-category">
                        <?php foreach ( $categories as $slug => $cat ) : ?>
                            <option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $required_cat, $slug ); ?>>
                                <?php echo esc_html( $cat['name'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php esc_html_e( 'The visitor must have accepted this category before form submission is permitted. "Necessary" effectively means any consent interaction — even Reject All — will unblock forms.', 'mbr-cookie-consent' ); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="mbr-cc-form-message">
                        <?php esc_html_e( 'Blocked Submission Message', 'mbr-cookie-consent' ); ?>
                    </label>
                </th>
                <td>
                    <input type="text"
                           id="mbr-cc-form-message"
                           value="<?php echo esc_attr( $message ); ?>"
                           class="large-text" />
                    <p class="description">
                        <?php esc_html_e( 'This message is displayed by the form builder when a submission is blocked. Displayed inline within the form.', 'mbr-cookie-consent' ); ?>
                    </p>
                </td>
            </tr>
        </table>

        <button type="button" id="mbr-cc-form-save" class="button button-primary">
            <?php esc_html_e( 'Save Settings', 'mbr-cookie-consent' ); ?>
        </button>
    </div>
</div>
