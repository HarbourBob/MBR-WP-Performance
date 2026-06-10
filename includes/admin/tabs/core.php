<?php
/**
 * Core Features Tab
 *
 * @package MBRPE
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$core_options = isset( $options['core'] ) ? $options['core'] : array();
?>

<div class="mbr-performance-tab-content">
    
    <!-- Scripts & Styles Section -->
    <div class="mbr-performance-section">
        <h2><?php esc_html_e( 'Scripts & Styles', 'mbr-performance' ); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="disable_emojis">
                        <?php esc_html_e( 'Disable Emojis', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Removes emoji detection script and inline styles. Safe to disable if not using emojis.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[core][disable_emojis]" id="disable_emojis" value="1" <?php checked( isset( $core_options['disable_emojis'] ) && $core_options['disable_emojis'] ); ?>>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="disable_dashicons">
                        <?php esc_html_e( 'Disable Dashicons', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Only disable if not using Dashicons in frontend. May affect admin bar icons.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[core][disable_dashicons]" id="disable_dashicons" value="1" <?php checked( isset( $core_options['disable_dashicons'] ) && $core_options['disable_dashicons'] ); ?>>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="disable_embeds">
                        <?php esc_html_e( 'Disable Embeds', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Removes oEmbed functionality. Disables automatic embedding of YouTube, Twitter, etc.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[core][disable_embeds]" id="disable_embeds" value="1" <?php checked( isset( $core_options['disable_embeds'] ) && $core_options['disable_embeds'] ); ?>>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="remove_jquery_migrate">
                        <?php esc_html_e( 'Remove jQuery Migrate', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Only disable if no plugin conflicts. jQuery Migrate helps older plugins work with newer jQuery versions.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[core][remove_jquery_migrate]" id="remove_jquery_migrate" value="1" <?php checked( isset( $core_options['remove_jquery_migrate'] ) && $core_options['remove_jquery_migrate'] ); ?>>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="remove_global_styles">
                        <?php esc_html_e( 'Remove Global Styles', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'WARNING: Will break Full Site Editing (block) themes. Only enable on classic themes. Block themes such as Twenty Twenty-Two onwards rely on this inline CSS to render their colours, fonts and layout on the front end. This setting is auto-skipped when a block theme is active for safety.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[core][remove_global_styles]" id="remove_global_styles" value="1" <?php checked( isset( $core_options['remove_global_styles'] ) && $core_options['remove_global_styles'] ); ?>>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="separate_block_styles">
                        <?php esc_html_e( 'Load Separate Block Styles', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Only loads block CSS when blocks are actually used on the page.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[core][separate_block_styles]" id="separate_block_styles" value="1" <?php checked( isset( $core_options['separate_block_styles'] ) && $core_options['separate_block_styles'] ); ?>>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- WordPress Features Section -->
    <div class="mbr-performance-section">
        <h2><?php esc_html_e( 'WordPress Features', 'mbr-performance' ); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="disable_xmlrpc">
                        <?php esc_html_e( 'Disable XML-RPC', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Improves security, may break some integrations like Jetpack or mobile apps.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[core][disable_xmlrpc]" id="disable_xmlrpc" value="1" <?php checked( isset( $core_options['disable_xmlrpc'] ) && $core_options['disable_xmlrpc'] ); ?>>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="hide_wp_version">
                        <?php esc_html_e( 'Hide WP Version', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Removes WordPress version number from head and feeds for better security.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[core][hide_wp_version]" id="hide_wp_version" value="1" <?php checked( isset( $core_options['hide_wp_version'] ) && $core_options['hide_wp_version'] ); ?>>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="remove_rsd_link">
                        <?php esc_html_e( 'Remove RSD Link', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Really Simple Discovery - rarely needed unless using external blog clients.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[core][remove_rsd_link]" id="remove_rsd_link" value="1" <?php checked( isset( $core_options['remove_rsd_link'] ) && $core_options['remove_rsd_link'] ); ?>>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="remove_shortlink">
                        <?php esc_html_e( 'Remove Shortlink', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Removes wp.me style short URLs from head.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[core][remove_shortlink]" id="remove_shortlink" value="1" <?php checked( isset( $core_options['remove_shortlink'] ) && $core_options['remove_shortlink'] ); ?>>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="disable_rss_feeds">
                        <?php esc_html_e( 'Disable RSS Feeds', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Completely disables all RSS feeds on your site.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[core][disable_rss_feeds]" id="disable_rss_feeds" value="1" <?php checked( isset( $core_options['disable_rss_feeds'] ) && $core_options['disable_rss_feeds'] ); ?>>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="remove_rss_feed_links">
                        <?php esc_html_e( 'Remove RSS Feed Links', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Hides feed discovery links from head but keeps feeds functional.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[core][remove_rss_feed_links]" id="remove_rss_feed_links" value="1" <?php checked( isset( $core_options['remove_rss_feed_links'] ) && $core_options['remove_rss_feed_links'] ); ?>>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="disable_self_pingbacks">
                        <?php esc_html_e( 'Disable Self Pingbacks', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Prevents your own posts from pinging themselves when you link internally.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[core][disable_self_pingbacks]" id="disable_self_pingbacks" value="1" <?php checked( isset( $core_options['disable_self_pingbacks'] ) && $core_options['disable_self_pingbacks'] ); ?>>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="rest_api_mode">
                        <?php esc_html_e( 'Disable REST API', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Controls REST API access. Choose based on your security and functionality needs.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <label><input type="radio" name="mbrpe_options[core][rest_api_mode]" value="default" <?php checked( isset( $core_options['rest_api_mode'] ) ? $core_options['rest_api_mode'] : 'default', 'default' ); ?>> <?php esc_html_e( 'Default (Enabled)', 'mbr-performance' ); ?></label><br>
                    <label><input type="radio" name="mbrpe_options[core][rest_api_mode]" value="disable_non_admin" <?php checked( isset( $core_options['rest_api_mode'] ) ? $core_options['rest_api_mode'] : '', 'disable_non_admin' ); ?>> <?php esc_html_e( 'Disable for Non-Admins', 'mbr-performance' ); ?></label><br>
                    <label><input type="radio" name="mbrpe_options[core][rest_api_mode]" value="disable_logged_out" <?php checked( isset( $core_options['rest_api_mode'] ) ? $core_options['rest_api_mode'] : '', 'disable_logged_out' ); ?>> <?php esc_html_e( 'Disable When Logged Out', 'mbr-performance' ); ?></label>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="rest_api_allowed_namespaces">
                        <?php esc_html_e( 'Allowed REST Namespaces', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Namespaces listed here remain accessible even when REST API access is restricted above. One per line. Use this for plugins that legitimately expose public REST endpoints (chat widgets, contact forms, store APIs).', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <textarea
                        name="mbrpe_options[core][rest_api_allowed_namespaces]"
                        id="rest_api_allowed_namespaces"
                        rows="4"
                        cols="40"
                        class="large-text code"
                        placeholder="mbr-isa/v1&#10;contact-form-7/v1&#10;wc/store/v1"><?php echo esc_textarea( isset( $core_options['rest_api_allowed_namespaces'] ) ? $core_options['rest_api_allowed_namespaces'] : '' ); ?></textarea>
                    <p class="description">
                        <?php esc_html_e( 'One namespace per line, e.g. mbr-isa/v1. Only applies when "Disable REST API" above is set to a non-default mode. Common public namespaces: mbr-isa/v1 (chat assistant), contact-form-7/v1, wc/store/v1 (WooCommerce).', 'mbr-performance' ); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="remove_rest_api_links">
                        <?php esc_html_e( 'Remove REST API Links', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Removes REST API discovery links from head but keeps API functional.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[core][remove_rest_api_links]" id="remove_rest_api_links" value="1" <?php checked( isset( $core_options['remove_rest_api_links'] ) && $core_options['remove_rest_api_links'] ); ?>>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="disable_ai_support">
                        <?php esc_html_e( 'Disable AI Features (WordPress 7.0+)', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'WordPress 7.0 adds a built-in AI Client, the Abilities API and a Settings -> Connectors screen. These stay dormant until you connect a provider, so the front-end impact is small. Enable this to switch the whole subsystem off using core\'s native kill switch (the wp_supports_ai filter), preventing it from bootstrapping at all. Has no effect on WordPress below 7.0.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[core][disable_ai_support]" id="disable_ai_support" value="1" <?php checked( isset( $core_options['disable_ai_support'] ) && $core_options['disable_ai_support'] ); ?>>
                    <p class="description"><?php esc_html_e( 'Stops the WordPress 7.0 AI Client and Abilities API from loading. Leave off if you intend to use AI-powered plugins that rely on core connectors.', 'mbr-performance' ); ?></p>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Third-Party Scripts Section -->
    <div class="mbr-performance-section">
        <h2><?php esc_html_e( 'Third-Party Scripts', 'mbr-performance' ); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="disable_google_maps">
                        <?php esc_html_e( 'Disable Google Maps', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'WARNING: Removes Google Maps API. Will break maps on your site.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[core][disable_google_maps]" id="disable_google_maps" value="1" <?php checked( isset( $core_options['disable_google_maps'] ) && $core_options['disable_google_maps'] ); ?>>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="disable_password_strength">
                        <?php esc_html_e( 'Disable Password Strength Meter', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Removes zxcvbn.js from login and registration pages.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[core][disable_password_strength]" id="disable_password_strength" value="1" <?php checked( isset( $core_options['disable_password_strength'] ) && $core_options['disable_password_strength'] ); ?>>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Comments Section -->
    <div class="mbr-performance-section">
        <h2><?php esc_html_e( 'Comments', 'mbr-performance' ); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="disable_comments">
                        <?php esc_html_e( 'Disable Comments', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Globally disables comment functionality across your entire site.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[core][disable_comments]" id="disable_comments" value="1" <?php checked( isset( $core_options['disable_comments'] ) && $core_options['disable_comments'] ); ?>>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="remove_comment_urls">
                        <?php esc_html_e( 'Remove Comment URLs', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Removes the website field from comment forms to reduce spam.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[core][remove_comment_urls]" id="remove_comment_urls" value="1" <?php checked( isset( $core_options['remove_comment_urls'] ) && $core_options['remove_comment_urls'] ); ?>>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Editor & Content Management Section -->
    <div class="mbr-performance-section">
        <h2><?php esc_html_e( 'Editor & Content Management', 'mbr-performance' ); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="heartbeat_mode">
                        <?php esc_html_e( 'Heartbeat Control', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Controls WordPress Heartbeat API which handles autosaves and notifications.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <label><input type="radio" name="mbrpe_options[core][heartbeat_mode]" value="default" <?php checked( isset( $core_options['heartbeat_mode'] ) ? $core_options['heartbeat_mode'] : 'default', 'default' ); ?>> <?php esc_html_e( 'Default (Enabled)', 'mbr-performance' ); ?></label><br>
                    <label><input type="radio" name="mbrpe_options[core][heartbeat_mode]" value="disable" <?php checked( isset( $core_options['heartbeat_mode'] ) ? $core_options['heartbeat_mode'] : '', 'disable' ); ?>> <?php esc_html_e( 'Disable Everywhere', 'mbr-performance' ); ?></label><br>
                    <label><input type="radio" name="mbrpe_options[core][heartbeat_mode]" value="allow_posts" <?php checked( isset( $core_options['heartbeat_mode'] ) ? $core_options['heartbeat_mode'] : '', 'allow_posts' ); ?>> <?php esc_html_e( 'Only Allow When Editing Posts/Pages', 'mbr-performance' ); ?></label>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="heartbeat_frequency">
                        <?php esc_html_e( 'Heartbeat Frequency', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Lower = less server load, slower autosave. Higher values reduce server requests.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <select name="mbrpe_options[core][heartbeat_frequency]" id="heartbeat_frequency">
                        <option value="15" <?php selected( isset( $core_options['heartbeat_frequency'] ) ? $core_options['heartbeat_frequency'] : 15, 15 ); ?>><?php esc_html_e( '15 Seconds (Default)', 'mbr-performance' ); ?></option>
                        <option value="30" <?php selected( isset( $core_options['heartbeat_frequency'] ) ? $core_options['heartbeat_frequency'] : 15, 30 ); ?>><?php esc_html_e( '30 Seconds', 'mbr-performance' ); ?></option>
                        <option value="45" <?php selected( isset( $core_options['heartbeat_frequency'] ) ? $core_options['heartbeat_frequency'] : 15, 45 ); ?>><?php esc_html_e( '45 Seconds', 'mbr-performance' ); ?></option>
                        <option value="60" <?php selected( isset( $core_options['heartbeat_frequency'] ) ? $core_options['heartbeat_frequency'] : 15, 60 ); ?>><?php esc_html_e( '60 Seconds', 'mbr-performance' ); ?></option>
                        <option value="120" <?php selected( isset( $core_options['heartbeat_frequency'] ) ? $core_options['heartbeat_frequency'] : 15, 120 ); ?>><?php esc_html_e( '120 Seconds', 'mbr-performance' ); ?></option>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="post_revisions">
                        <?php esc_html_e( 'Post Revisions Limit', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Limits the number of post revisions stored. Reduces database bloat.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <select name="mbrpe_options[core][post_revisions]" id="post_revisions">
                        <option value="default" <?php selected( isset( $core_options['post_revisions'] ) ? $core_options['post_revisions'] : 'default', 'default' ); ?>><?php esc_html_e( 'Default (Unlimited)', 'mbr-performance' ); ?></option>
                        <option value="disable" <?php selected( isset( $core_options['post_revisions'] ) ? $core_options['post_revisions'] : 'default', 'disable' ); ?>><?php esc_html_e( 'Disable Post Revisions', 'mbr-performance' ); ?></option>
                        <option value="3" <?php selected( isset( $core_options['post_revisions'] ) ? $core_options['post_revisions'] : 'default', '3' ); ?>>3</option>
                        <option value="5" <?php selected( isset( $core_options['post_revisions'] ) ? $core_options['post_revisions'] : 'default', '5' ); ?>>5</option>
                        <option value="10" <?php selected( isset( $core_options['post_revisions'] ) ? $core_options['post_revisions'] : 'default', '10' ); ?>>10</option>
                        <option value="20" <?php selected( isset( $core_options['post_revisions'] ) ? $core_options['post_revisions'] : 'default', '20' ); ?>>20</option>
                        <option value="30" <?php selected( isset( $core_options['post_revisions'] ) ? $core_options['post_revisions'] : 'default', '30' ); ?>>30</option>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="autosave_interval">
                        <?php esc_html_e( 'Autosave Interval', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'How often the editor auto-saves drafts. Longer intervals reduce server load.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <select name="mbrpe_options[core][autosave_interval]" id="autosave_interval">
                        <option value="60" <?php selected( isset( $core_options['autosave_interval'] ) ? $core_options['autosave_interval'] : 60, 60 ); ?>><?php esc_html_e( '1 Minute (Default)', 'mbr-performance' ); ?></option>
                        <option value="0" <?php selected( isset( $core_options['autosave_interval'] ) ? $core_options['autosave_interval'] : 60, 0 ); ?>><?php esc_html_e( 'Disable Autosave', 'mbr-performance' ); ?></option>
                        <option value="120" <?php selected( isset( $core_options['autosave_interval'] ) ? $core_options['autosave_interval'] : 60, 120 ); ?>><?php esc_html_e( '2 Minutes', 'mbr-performance' ); ?></option>
                        <option value="180" <?php selected( isset( $core_options['autosave_interval'] ) ? $core_options['autosave_interval'] : 60, 180 ); ?>><?php esc_html_e( '3 Minutes', 'mbr-performance' ); ?></option>
                        <option value="300" <?php selected( isset( $core_options['autosave_interval'] ) ? $core_options['autosave_interval'] : 60, 300 ); ?>><?php esc_html_e( '5 Minutes', 'mbr-performance' ); ?></option>
                        <option value="600" <?php selected( isset( $core_options['autosave_interval'] ) ? $core_options['autosave_interval'] : 60, 600 ); ?>><?php esc_html_e( '10 Minutes', 'mbr-performance' ); ?></option>
                    </select>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Advanced Performance Section -->
    <div class="mbr-performance-section">
        <h2><?php esc_html_e( 'Advanced Performance', 'mbr-performance' ); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="minify_html">
                        <?php esc_html_e( 'Minify HTML', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Strips HTML comments and collapses whitespace in the page output. Typically saves 5-15% of HTML transfer size. The contents of script, style, pre, textarea and svg blocks are preserved exactly, IE conditional comments are kept, and pages embedding a complete nested HTML document (e.g. a full landing page inside a page-builder HTML widget) are skipped automatically. AMP, REST, AJAX and feed responses are never minified.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[core][minify_html]" id="minify_html" value="1" <?php checked( isset( $core_options['minify_html'] ) && $core_options['minify_html'] ); ?>>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="lazy_load_mode">
                        <?php esc_html_e( 'Lazy Load Images', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Controls how images are lazy loaded. Enhanced mode loads images earlier for better UX.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <label><input type="radio" name="mbrpe_options[core][lazy_load_mode]" value="default" <?php checked( isset( $core_options['lazy_load_mode'] ) ? $core_options['lazy_load_mode'] : 'default', 'default' ); ?>> <?php esc_html_e( 'WordPress Default', 'mbr-performance' ); ?></label><br>
                    <label><input type="radio" name="mbrpe_options[core][lazy_load_mode]" value="enhanced" <?php checked( isset( $core_options['lazy_load_mode'] ) ? $core_options['lazy_load_mode'] : 'default', 'enhanced' ); ?>> <?php esc_html_e( 'Enhanced (Earlier threshold)', 'mbr-performance' ); ?></label><br>
                    <label><input type="radio" name="mbrpe_options[core][lazy_load_mode]" value="disable" <?php checked( isset( $core_options['lazy_load_mode'] ) ? $core_options['lazy_load_mode'] : 'default', 'disable' ); ?>> <?php esc_html_e( 'Disable', 'mbr-performance' ); ?></label>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="remove_query_strings">
                        <?php esc_html_e( 'Remove Query Strings', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Removes ?ver= from static resources for better caching by CDNs and proxies.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[core][remove_query_strings]" id="remove_query_strings" value="1" <?php checked( isset( $core_options['remove_query_strings'] ) && $core_options['remove_query_strings'] ); ?>>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="preload_resources">
                        <?php esc_html_e( 'Preload Critical Resources', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Enter URLs of critical fonts, CSS, or JS files to preload. One URL per line.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <textarea name="mbrpe_options[core][preload_resources]" id="preload_resources" rows="4" class="large-text code"><?php echo isset( $core_options['preload_resources'] ) ? esc_textarea( $core_options['preload_resources'] ) : ''; ?></textarea>
                    <p class="description"><?php esc_html_e( 'Example: /wp-content/themes/your-theme/fonts/primary.woff2', 'mbr-performance' ); ?></p>
                </td>
            </tr>
            
            <?php if ( class_exists( 'WooCommerce' ) ) : ?>
            <tr>
                <th scope="row">
                    <label for="disable_woocommerce_scripts">
                        <?php esc_html_e( 'Disable WooCommerce Scripts on Non-Shop Pages', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Only loads WooCommerce scripts on shop, product, cart, and checkout pages.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbrpe_options[core][disable_woocommerce_scripts]" id="disable_woocommerce_scripts" value="1" <?php checked( isset( $core_options['disable_woocommerce_scripts'] ) && $core_options['disable_woocommerce_scripts'] ); ?>>
                </td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    
</div>
