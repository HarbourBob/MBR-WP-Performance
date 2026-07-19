<?php
/**
 * Settings View - Complete Tabbed Interface with ALL Features
 *
 * @package MBR_Cookie_Consent
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap mbr-cc-admin-wrap">
    <h1><?php esc_html_e('Cookie Consent Settings', 'mbr-cookie-consent'); ?></h1>
    
    <!-- Tab Navigation -->
    <nav class="mbr-cc-tab-nav">
        <button type="button" class="mbr-cc-tab-button active" data-tab="banner">
            <span class="dashicons dashicons-admin-appearance"></span>
            <?php esc_html_e('Banner Settings', 'mbr-cookie-consent'); ?>
        </button>
        <button type="button" class="mbr-cc-tab-button" data-tab="behavior">
            <span class="dashicons dashicons-admin-settings"></span>
            <?php esc_html_e('Banner Behavior', 'mbr-cookie-consent'); ?>
        </button>
        <button type="button" class="mbr-cc-tab-button" data-tab="consent-mode">
            <span class="dashicons dashicons-google"></span>
            <?php esc_html_e('Consent Mode', 'mbr-cookie-consent'); ?>
        </button>
        <button type="button" class="mbr-cc-tab-button" data-tab="i18n">
            <span class="dashicons dashicons-translation"></span>
            <?php esc_html_e('i18n & Accessibility', 'mbr-cookie-consent'); ?>
        </button>
        <button type="button" class="mbr-cc-tab-button" data-tab="advanced-consent">
            <span class="dashicons dashicons-shield"></span>
            <?php esc_html_e('Advanced Consent', 'mbr-cookie-consent'); ?>
        </button>
        <button type="button" class="mbr-cc-tab-button" data-tab="customization">
            <span class="dashicons dashicons-admin-tools"></span>
            <?php esc_html_e('Customization', 'mbr-cookie-consent'); ?>
        </button>
    </nav>
    
    <form method="post" id="mbr-cc-settings-form">
        
