<?php
/**
 * Geolocation AJAX Handlers
 *
 * @package MBR_Cookie_Consent
 * @version 2.1.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test geolocation for a specific country / region.
 *
 * Accepts an optional 'region' POST parameter (ISO 3166-2 sub-national code,
 * e.g. "QC" for Quebec) so admins can verify Quebec-specific behaviour.
 */
function mbr_cc_ajax_test_geolocation() {
    check_ajax_referer('mbr_cc_geo_test', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
    }
    
    $country = isset($_POST['country']) ? strtoupper(sanitize_text_field($_POST['country'])) : '';
    $region_code = isset($_POST['region']) ? strtoupper(sanitize_text_field($_POST['region'])) : '';
    
    if (strlen($country) !== 2) {
        wp_send_json_error('Invalid country code');
    }
    
    // Resolve region using the same logic as live detection so the test
    // always matches what real visitors would experience.
    if (!function_exists('mbr_cc_geolocation') || !function_exists('mbr_cc_region_config')) {
        wp_send_json_error('Geolocation services not available');
    }
    
    $geo = mbr_cc_geolocation();
    
    // Use reflection to invoke the private determine_region() method without
    // duplicating the country/region mapping table here.
    try {
        $ref = new ReflectionMethod($geo, 'determine_region');
        $ref->setAccessible(true);
        $region = $ref->invoke($geo, $country, $region_code ?: null);
    } catch (Exception $e) {
        wp_send_json_error('Region resolution failed: ' . $e->getMessage());
    }
    
    // Pull the friendly name and config flags from the canonical sources.
    $names = array(
        'eu_gdpr'    => 'EU/EEA (GDPR / ePrivacy Directive)',
        'uk_duaa'    => 'United Kingdom (UK GDPR + DUAA 2025)',
        'us_multi'   => 'United States (CCPA + 20 State Laws / GPC)',
        'ca_quebec'  => 'Canada — Quebec (Law 25)',
        'pipeda'     => 'Canada (PIPEDA / CASL)',
        'ch_nfadp'   => 'Switzerland (revFADP / nFADP)',
        'au_privacy' => 'Australia (Privacy Act 1988, as amended)',
        'lgpd'       => 'Brazil (LGPD)',
        'india_dpdp' => 'India (DPDP Act 2023, Rules 2025)',
        'default'    => 'Rest of World',
    );
    $region_name = isset($names[$region]) ? $names[$region] : 'Rest of World';
    
    // Regions where opt-in / express consent is the regime.
    $consent_required_regions = array(
        'eu_gdpr', 'uk_duaa', 'ca_quebec', 'ch_nfadp', 'au_privacy',
        'lgpd', 'pipeda', 'india_dpdp',
    );
    
    // Regions where the banner shows a prominent equally-weighted reject.
    $show_reject_regions = array(
        'eu_gdpr', 'uk_duaa', 'ca_quebec', 'ch_nfadp', 'au_privacy',
        'lgpd', 'india_dpdp',
    );
    
    $requires_consent = in_array($region, $consent_required_regions, true);
    $show_reject      = in_array($region, $show_reject_regions, true);
    $enable_ccpa      = ($region === 'us_multi');
    $gpc_enabled      = ($region === 'us_multi');
    $duaa_exempt      = ($region === 'uk_duaa');
    
    wp_send_json_success(array(
        'country' => $country,
        'region_input' => $region_code,
        'region' => $region,
        'region_name' => $region_name,
        'requires_consent' => $requires_consent,
        'show_reject' => $show_reject,
        'enable_ccpa' => $enable_ccpa,
        'gpc_enabled' => $gpc_enabled,
        'duaa_exempt' => $duaa_exempt,
    ));
}

/**
 * Clear geolocation cache
 */
function mbr_cc_ajax_clear_geo_cache() {
    check_ajax_referer('mbr_cc_geo_cache', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
    }
    
    global $wpdb;
    
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_mbr_cc_geo_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_mbr_cc_geo_%'");
    
    wp_send_json_success('Cache cleared');
}

// Register AJAX handlers
add_action('wp_ajax_mbr_cc_test_geolocation', 'mbr_cc_ajax_test_geolocation');
add_action('wp_ajax_mbr_cc_clear_geo_cache', 'mbr_cc_ajax_clear_geo_cache');
