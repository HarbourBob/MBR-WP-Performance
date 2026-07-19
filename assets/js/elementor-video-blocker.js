/**
 * MBR Cookie Consent — Elementor Video Blocker
 *
 * Intercepts Elementor's video widgets (YouTube, Vimeo, etc.) before
 * Elementor's own JavaScript initialises them. When the relevant consent
 * category has not been granted we:
 *   1. Remove the data-settings attribute so Elementor cannot read the URL.
 *   2. Inject a branded placeholder in its place.
 *   3. Store the original settings on the element so we can restore the
 *      video when consent is later granted via the cookie banner.
 *
 * Service → consent-category mapping mirrors the PHP blocker's built-in list.
 */
( function () {
    'use strict';

    // ── Consent helpers ───────────────────────────────────────────────────

    function getConsent() {
        try {
            var raw = document.cookie
                .split( '; ' )
                .find( function ( row ) { return row.startsWith( 'mbr_cc_consent=' ); } );
            if ( ! raw ) { return {}; }
            return JSON.parse( decodeURIComponent( raw.split( '=' ).slice( 1 ).join( '=' ) ) );
        } catch ( e ) {
            return {};
        }
    }

    function categoryAllowed( category, consent ) {
        if ( consent.all === true ) { return true; }
        return consent[ category ] === true;
    }

    // ── Service detection ─────────────────────────────────────────────────
    // Maps URL patterns to consent categories.

    var serviceMap = [
        { pattern: /youtube\.com|youtu\.be|youtube-nocookie\.com/, category: 'marketing',   name: 'YouTube'     },
        { pattern: /vimeo\.com/,                                    category: 'preferences', name: 'Vimeo'       },
        { pattern: /maps\.google\.com|maps\.googleapis\.com/,       category: 'preferences', name: 'Google Maps' },
        { pattern: /dailymotion\.com/,                              category: 'marketing',   name: 'Dailymotion' },
        { pattern: /twitch\.tv/,                                    category: 'marketing',   name: 'Twitch'      },
    ];

    function detectService( url ) {
        for ( var i = 0; i < serviceMap.length; i++ ) {
            if ( serviceMap[ i ].pattern.test( url ) ) {
                return serviceMap[ i ];
            }
        }
        return { category: 'marketing', name: 'Video' };
    }

    // ── Placeholder builder ───────────────────────────────────────────────

    function buildPlaceholder( service, widgetEl ) {
        // Inherit dimensions from the widget element.
        var rect   = widgetEl.getBoundingClientRect();
        var width  = widgetEl.offsetWidth  || 560;
        var height = widgetEl.offsetHeight || 315;
        if ( height < 80 ) { height = Math.round( width * 9 / 16 ); }

        // Try to pull accent colour from the consent banner CSS variable,
        // falling back to a sensible default.
        var accent = '#c8102e';
        try {
            var bannerAccent = getComputedStyle( document.documentElement )
                .getPropertyValue( '--mbr-cc-primary' ).trim();
            if ( bannerAccent ) { accent = bannerAccent; }
        } catch ( e ) {}

        var wrapper = document.createElement( 'div' );
        wrapper.className = 'mbr-cc-elementor-placeholder';
        wrapper.setAttribute( 'data-mbr-cc-blocked', 'true' );
        wrapper.setAttribute( 'data-mbr-cc-category', service.category );
        wrapper.style.cssText = [
            'display:flex',
            'flex-direction:column',
            'align-items:center',
            'justify-content:center',
            'width:100%',
            'min-height:' + height + 'px',
            'background:#1a1a1a',
            'background-image:repeating-linear-gradient(' +
                '45deg,transparent,transparent 10px,' +
                'rgba(255,255,255,.03) 10px,rgba(255,255,255,.03) 20px)',
            'border-radius:4px',
            'padding:2rem',
            'box-sizing:border-box',
            'text-align:center',
            'font-family:inherit',
        ].join( ';' );

        // Icon (play button shape).
        var icon = document.createElement( 'div' );
        icon.style.cssText = [
            'width:64px', 'height:64px',
            'background:' + accent,
            'border-radius:50%',
            'display:flex', 'align-items:center', 'justify-content:center',
            'margin-bottom:1rem',
            'flex-shrink:0',
        ].join( ';' );
        icon.innerHTML = '<svg width="24" height="24" viewBox="0 0 24 24" fill="white">' +
            '<path d="M8 5v14l11-7z"/></svg>';

        var title = document.createElement( 'p' );
        title.style.cssText = 'color:#fff;font-size:1rem;font-weight:600;margin:0 0 .5rem;';
        title.textContent = service.name + ' video blocked';

        var msg = document.createElement( 'p' );
        msg.style.cssText = 'color:#aaa;font-size:.85rem;margin:0 0 1.25rem;max-width:320px;';
        msg.textContent = 'This video is blocked because ' + service.category +
            ' cookies have not been accepted.';

        var btn = document.createElement( 'button' );
        btn.className = 'mbr-cc-elementor-consent-btn';
        btn.textContent = 'Accept cookies & play video';
        btn.style.cssText = [
            'background:' + accent,
            'color:#fff',
            'border:none',
            'border-radius:4px',
            'padding:.65rem 1.4rem',
            'font-size:.9rem',
            'font-weight:600',
            'cursor:pointer',
            'transition:opacity .2s',
        ].join( ';' );
        btn.addEventListener( 'mouseover', function () { btn.style.opacity = '.85'; } );
        btn.addEventListener( 'mouseout',  function () { btn.style.opacity = '1'; } );

        // Clicking the button opens the consent preferences panel.
        btn.addEventListener( 'click', function () {
            if ( window.MbrCookieBanner && typeof window.MbrCookieBanner.showPreferences === 'function' ) {
                window.MbrCookieBanner.showPreferences();
            } else {
                // Fallback: look for the banner and trigger its customize button.
                var customize = document.querySelector( '#mbr-cc-customize, .mbr-cc-customize-btn' );
                if ( customize ) { customize.click(); }
            }
        } );

        wrapper.appendChild( icon );
        wrapper.appendChild( title );
        wrapper.appendChild( msg );
        wrapper.appendChild( btn );

        return wrapper;
    }

    // ── Core blocker ──────────────────────────────────────────────────────

    function blockElementorVideos() {
        var consent = getConsent();

        // Target Elementor's video elements. Elementor uses different structures
        // depending on version:
        //   - Older: .elementor-widget-video > [data-settings]
        //   - Newer: .e-youtube-base, .e-vimeo-base, .e-hosted-video (data-settings on the element itself)
        // We query ALL [data-settings] elements and filter by URL content.
        var allSettingsEls = document.querySelectorAll(
            '.e-youtube-base, .e-vimeo-base, .e-hosted-video, ' +
            '.elementor-widget-video [data-settings], ' +
            '[data-widget_type="video.default"] [data-settings], ' +
            '[data-widget_type="video"] [data-settings]'
        );

        // Also catch any [data-settings] containing a video URL directly.
        var allDataSettings = document.querySelectorAll( '[data-settings]' );
        var seen = new Set();
        var combined = [];
        allSettingsEls.forEach( function(el) { if(!seen.has(el)){ seen.add(el); combined.push(el); } });
        allDataSettings.forEach( function(el) {
            if ( ! seen.has(el) ) {
                var s = el.getAttribute('data-settings');
                if ( s && ( s.indexOf('youtube') !== -1 || s.indexOf('vimeo') !== -1 || s.indexOf('youtu.be') !== -1 ) ) {
                    seen.add(el); combined.push(el);
                }
            }
        });

        combined.forEach( function ( settingsEl ) {
            var rawSettings = settingsEl.getAttribute( 'data-settings' );
            if ( ! rawSettings ) { return; }
            // widget is the closest ancestor we'll use for sizing/placeholder insertion
            var widget = settingsEl.closest( '.elementor-widget-video, .elementor-widget, .e-widget' ) || settingsEl.parentElement || settingsEl;

            var settings;
            try {
                settings = JSON.parse( rawSettings );
            } catch ( e ) {
                return;
            }

            // Extract the video URL — Elementor uses 'source' in newer versions.
            var url = settings.source || settings.youtube_url || settings.vimeo_url || '';
            if ( ! url ) { return; }

            var service = detectService( url );

            // Check consent for this service's category.
            if ( categoryAllowed( service.category, consent ) ) { return; }

            // Skip if already blocked.
            if ( settingsEl.getAttribute( 'data-mbr-cc-blocked' ) === 'true' ) { return; }

            // Store original settings on the settings element for restoration.
            settingsEl.setAttribute( 'data-mbr-cc-blocked', 'true' );
            settingsEl.setAttribute( 'data-mbr-cc-category', service.category );
            settingsEl.setAttribute( 'data-mbr-cc-original-settings', rawSettings );

            // Remove data-settings so Elementor cannot read the URL.
            settingsEl.removeAttribute( 'data-settings' );

            // Hide the entire element so the blank/broken player doesn't show.
            settingsEl.style.display = 'none';

            // Find best container to inject the placeholder into.
            var videoContainer = settingsEl.parentElement || widget;

            // Inject our placeholder before the hidden element.
            var placeholder = buildPlaceholder( service, videoContainer );
            placeholder.setAttribute( 'data-mbr-cc-placeholder-for', 'true' );
            videoContainer.insertBefore( placeholder, settingsEl );
        } );
    }

    // ── Restore on consent ────────────────────────────────────────────────

    function restoreElementorVideos( consent ) {
        // Find all blocked settings elements.
        var allBlocked = document.querySelectorAll( '[data-mbr-cc-blocked="true"]' );

        allBlocked.forEach( function ( settingsEl ) {
            var category = settingsEl.getAttribute( 'data-mbr-cc-category' );
            if ( ! category || ! categoryAllowed( category, consent ) ) { return; }

            var originalSettings = settingsEl.getAttribute( 'data-mbr-cc-original-settings' );
            if ( ! originalSettings ) { return; }

            // Restore data-settings so Elementor can re-initialise.
            settingsEl.setAttribute( 'data-settings', originalSettings );
            settingsEl.style.display = '';

            // Remove the placeholder that was injected before this element.
            var placeholder = settingsEl.previousElementSibling;
            if ( placeholder && placeholder.getAttribute( 'data-mbr-cc-placeholder-for' ) ) {
                placeholder.remove();
            }
            // Also sweep any placeholders inside the parent.
            var parent = settingsEl.parentElement;
            if ( parent ) {
                parent.querySelectorAll( '.mbr-cc-elementor-placeholder' ).forEach( function(p){ p.remove(); } );
            }

            // Clean up markers.
            settingsEl.removeAttribute( 'data-mbr-cc-blocked' );
            settingsEl.removeAttribute( 'data-mbr-cc-category' );
            settingsEl.removeAttribute( 'data-mbr-cc-original-settings' );

            // Re-initialise Elementor if available.
            if ( window.elementorFrontend ) {
                try {
                    // Trigger Elementor to re-run handlers on the element.
                    var $el = window.jQuery ? jQuery( settingsEl.closest( '[data-widget_type]' ) || settingsEl ) : null;
                    if ( $el && window.elementorFrontend.hooks ) {
                        window.elementorFrontend.hooks.doAction( 'frontend/element_ready/global', $el, jQuery );
                    }
                    // For e-youtube-base: dispatch a custom event Elementor listens for.
                    settingsEl.dispatchEvent( new CustomEvent( 'elementor/video/init', { bubbles: true } ) );
                } catch(e) {}
            }

            // Last resort: reload just this element by re-cloning it.
            // This forces Elementor's MutationObserver / init to re-fire.
            try {
                var clone = settingsEl.cloneNode( true );
                settingsEl.parentNode.replaceChild( clone, settingsEl );
            } catch(e) {}
        } );
    }

    // ── Timing: run as early as possible ─────────────────────────────────
    // We need to run BEFORE elementorFrontend.init() which fires on DOMContentLoaded.
    // Using 'interactive' readyState check covers the case where this script
    // loads after parsing but before DOMContentLoaded handlers.

    function init() {
        blockElementorVideos();
    }

    if ( document.readyState === 'loading' ) {
        // Still parsing — run as soon as DOM is ready, before other listeners.
        document.addEventListener( 'DOMContentLoaded', init, true ); // capture phase = runs first
    } else {
        // DOM already ready.
        init();
    }

    // ── Listen for consent changes from the banner ────────────────────────
    // If any blocked videos exist on the page, reload after consent is saved
    // so Elementor can fully re-initialise them cleanly.

    function onConsentSaved( consent ) {
        var hasBlocked = document.querySelector( '[data-mbr-cc-blocked="true"]' );
        if ( hasBlocked ) {
            // Small delay so the banner can finish its own save/close animation.
            setTimeout( function () {
                window.location.reload();
            }, 400 );
        }
    }

    document.addEventListener( 'mbr_cc_consent_saved', function ( e ) {
        var consent = ( e.detail && e.detail[0] ) ? e.detail[0] : getConsent();
        onConsentSaved( consent );
    } );

    if ( window.jQuery ) {
        jQuery( document ).on( 'mbr_cc_consent_saved', function ( e, consent ) {
            onConsentSaved( consent );
        } );
    } else {
        document.addEventListener( 'DOMContentLoaded', function () {
            if ( window.jQuery ) {
                jQuery( document ).on( 'mbr_cc_consent_saved', function ( e, consent ) {
                    onConsentSaved( consent );
                } );
            }
        } );
    }

} )();
