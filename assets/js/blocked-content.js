/**
 * MBR Cookie Consent — Blocked Content Button Handler
 *
 * Uses event delegation to handle clicks on any number of "Cookie Settings"
 * buttons injected into blocked-content placeholders by PHP.
 *
 * Hooks into the existing MbrCookieBanner jQuery object that banner.js
 * exposes, with graceful fallbacks for edge cases.
 *
 * No dependencies beyond jQuery (already loaded by banner.js).
 *
 * @package MBR_Cookie_Consent
 * @since   1.7.0
 */
( function ( $ ) {
    'use strict';

    /**
     * Open the cookie consent settings / preferences panel.
     *
     * Tries four methods in order:
     *   1. MbrCookieBanner.showPreferences() — the jQuery banner object.
     *   2. Click the revisit / settings button if it exists in the DOM.
     *   3. Re-show the main consent banner.
     *   4. No-op (banner JS not yet loaded — user can reload).
     */
    function openCookieSettings() {

        // 1. Primary — use banner.js public API.
        if ( typeof MbrCookieBanner !== 'undefined' &&
             typeof MbrCookieBanner.showPreferences === 'function' ) {
            MbrCookieBanner.showPreferences();
            return;
        }

        // 2. Fallback — click the floating revisit/settings button.
        var $revisit = $( '#mbr-cc-revisit, .mbr-cc-revisit-btn, [data-mbr-cc-settings]' ).first();
        if ( $revisit.length ) {
            $revisit.trigger( 'click' );
            return;
        }

        // 3. Fallback — re-show the banner directly.
        var $banner = $( '#mbr-cc-banner, #mbr-cookie-consent-banner' ).first();
        if ( $banner.length ) {
            $banner.removeClass( 'mbr-cc-hidden mbr-cc-banner--hidden' )
                   .removeAttr( 'hidden' )
                   .attr( 'aria-hidden', 'false' )
                   .show();
        }
    }

    // ── Delegated click listener ─────────────────────────────────────────
    // Works for all .mbr-cc-blocked-btn elements, including those added
    // dynamically (e.g. after an AJAX page load).
    $( document ).on( 'click', '.mbr-cc-blocked-btn', function ( e ) {
        e.preventDefault();
        openCookieSettings();
    } );

} )( jQuery );
