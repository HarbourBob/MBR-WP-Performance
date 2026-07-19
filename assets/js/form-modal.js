/**
 * MBR Cookie Consent — Elementor Form Consent Modal
 *
 * When an Elementor form submission is blocked server-side (no consent),
 * the PHP sends a bare JSON response with mbr_cc_consent_required: true.
 * This script intercepts that response — via both fetch() and XHR —
 * and shows a clean consent modal. Nothing is delegated to Elementor's
 * own error rendering.
 *
 * After the user accepts cookies, the pending form re-submits automatically.
 *
 * @package MBR_Cookie_Consent
 * @since   1.9.1
 */
( function ( $ ) {
    'use strict';

    var $pendingForm      = null;
    var pendingRequestBody = null;  // Raw request body to replay after consent.
    var modalOpen         = false;
    var ajaxUrl           = ( window.mbrCcFormModal && window.mbrCcFormModal.ajaxUrl )
                            ? window.mbrCcFormModal.ajaxUrl
                            : '/wp-admin/admin-ajax.php';

    var blockedMsg = ( window.mbrCcFormModal && window.mbrCcFormModal.message )
        ? window.mbrCcFormModal.message
        : 'Please accept cookies before submitting this form.';

    // ── Accent colour ─────────────────────────────────────────────────

    function getAccent() {
        try {
            var styles = getComputedStyle( document.documentElement );
            return styles.getPropertyValue( '--mbr-cc-primary' ).trim() || '#0073aa';
        } catch ( e ) { return '#0073aa'; }
    }

    // ── Modal ─────────────────────────────────────────────────────────

    function showModal( message ) {
        if ( modalOpen ) { return; }
        modalOpen = true;

        var accent = getAccent();

        var lockSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"'
            + ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
            + '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>'
            + '<path d="M7 11V7a5 5 0 0 1 10 0v4"/>'
            + '</svg>';

        var $overlay = $( '<div>', {
            'class':      'mbr-cc-form-modal-overlay',
            'role':       'dialog',
            'aria-modal': 'true',
            'aria-label': 'Cookie consent required',
        } );

        var $card = $( '<div class="mbr-cc-form-modal">' );
        $card.append(
            $( '<button class="mbr-cc-form-modal__close" aria-label="Close">&times;</button>' ),
            $( '<div class="mbr-cc-form-modal__icon">' ).css( 'background', accent ).html( lockSvg ),
            $( '<p class="mbr-cc-form-modal__title">' ).text( 'Cookies required' ),
            $( '<p class="mbr-cc-form-modal__message">' ).text( message || blockedMsg ),
            $( '<div class="mbr-cc-form-modal__actions">' ).append(
                $( '<button class="mbr-cc-form-modal__btn-accept">' ).css( 'background', accent ).text( 'Accept cookies' ),
                $( '<button class="mbr-cc-form-modal__btn-dismiss">' ).text( 'Not now' )
            )
        );

        $overlay.append( $card );
        $( 'body' ).append( $overlay );
        $card.find( '.mbr-cc-form-modal__btn-accept' ).focus();

        function closeModal() {
            modalOpen = false;
            $( document ).off( 'keydown.mbrFormModal' );
            $overlay.fadeOut( 200, function () { $overlay.remove(); } );
        }

        $overlay.on( 'click', '.mbr-cc-form-modal__close, .mbr-cc-form-modal__btn-dismiss', closeModal );
        $overlay.on( 'click', function ( e ) {
            if ( $( e.target ).is( $overlay ) ) { closeModal(); }
        } );
        $( document ).on( 'keydown.mbrFormModal', function ( e ) {
            if ( e.key === 'Escape' ) { closeModal(); }
        } );

        $overlay.on( 'click', '.mbr-cc-form-modal__btn-accept', function () {
            closeModal();
            window._mbrCcSuppressReload = true;

            if ( window.MbrCookieBanner && typeof window.MbrCookieBanner.showPreferences === 'function' ) {
                window.MbrCookieBanner.showPreferences();
            } else {
                $( '#mbr-cc-banner' ).fadeIn( 300 );
            }

            $( document ).one( 'mbr_cc_consent_saved', function () {
                window._mbrCcSuppressReload = false;
                // Replay the captured request body directly to admin-ajax.php.
                // This bypasses Elementor's JS entirely — no form reset cycle.
                if ( pendingRequestBody ) {
                    var body = pendingRequestBody;
                    pendingRequestBody = null;
                    $pendingForm = null;

                    fetch( ajaxUrl, { method: 'POST', body: body } )
                        .then( function ( r ) { return r.json(); } )
                        .then( function ( json ) {
                            if ( json && json.success ) {
                                // Show Elementor's success message if the form is still in DOM.
                                var $form = $( '.elementor-form' ).first();
                                $form.find( '.elementor-form-fields-wrapper' ).hide();
                                $form.find( '.elementor-message.elementor-message-success' ).show();
                                // If there's no success element, just show a simple notice.
                                if ( ! $form.find( '.elementor-message-success' ).length ) {
                                    $form.prepend(
                                        $( '<div class="elementor-message elementor-message-success">' )
                                            .text( 'Your message has been sent.' )
                                    );
                                }
                            }
                        } )
                        .catch( function () {} );
                }
            } );
        } );
    }

    // ── Captured submission data ─────────────────────────────────────
    // We intercept the raw request body at send time so we can replay
    // it exactly after consent — no need to re-read the DOM at all.

    // ── Response checker ──────────────────────────────────────────────

    function checkJson( json, formEl ) {
        if ( ! json ) { return false; }

        // Our PHP sends: { success: false, data: { mbr_cc_consent_required: true, mbr_cc_message: '...' } }
        if ( json.success === false
             && json.data
             && json.data.mbr_cc_consent_required === true ) {

            if ( formEl && ! $pendingForm ) {
                $pendingForm = $( formEl );
            }
            showModal( json.data.mbr_cc_message || blockedMsg );
            return true;
        }
        return false;
    }

    // ── Intercept fetch() ─────────────────────────────────────────────
    // Elementor Pro uses fetch() for form submissions in recent versions.

    if ( window.fetch ) {
        var _fetch = window.fetch;
        window.fetch = function ( resource, init ) {
            var req = _fetch.apply( this, arguments );

            var url = typeof resource === 'string' ? resource
                    : ( resource instanceof Request ? resource.url : '' );

            if ( url.indexOf( 'admin-ajax' ) === -1 ) {
                return req;
            }

            // Find the Elementor form that is currently being submitted.
            var activeForm = document.activeElement
                ? document.activeElement.closest( '.elementor-form' )
                : null;
            if ( ! activeForm ) {
                activeForm = document.querySelector( '.elementor-form' );
            }

            return req.then( function ( response ) {
                var clone = response.clone();
                clone.json().then( function ( json ) {
                    if ( checkJson( json, activeForm ) ) {
                        // Store the original request body for replay after consent.
                        pendingRequestBody = ( init && init.body ) ? init.body : null;
                    }
                } ).catch( function () {} );
                return response;
            } );
        };
    }

    // ── Intercept XMLHttpRequest ───────────────────────────────────────
    // Covers jQuery $.ajax and any XHR-based Elementor fallback.

    var _XHROpen = XMLHttpRequest.prototype.open;
    var _XHRSend = XMLHttpRequest.prototype.send;

    XMLHttpRequest.prototype.open = function ( method, url ) {
        this._mbrUrl = url || '';
        return _XHROpen.apply( this, arguments );
    };

    XMLHttpRequest.prototype.send = function ( body ) {
        var xhr = this;

        if ( xhr._mbrUrl && xhr._mbrUrl.indexOf( 'admin-ajax' ) !== -1 ) {
            var activeForm = document.activeElement
                ? document.activeElement.closest( '.elementor-form' )
                : null;
            var sentBody = body;

            xhr.addEventListener( 'load', function () {
                if ( xhr.status !== 200 ) { return; }
                var json;
                try { json = JSON.parse( xhr.responseText ); }
                catch ( e ) { return; }
                if ( checkJson( json, activeForm || document.querySelector( '.elementor-form' ) ) ) {
                    // Store body for replay — convert string to FormData if needed.
                    if ( sentBody instanceof FormData ) {
                        pendingRequestBody = sentBody;
                    } else if ( typeof sentBody === 'string' ) {
                        // URLEncoded string — convert to FormData.
                        var fd = new FormData();
                        sentBody.split( '&' ).forEach( function ( pair ) {
                            var parts = pair.split( '=' );
                            if ( parts.length === 2 ) {
                                fd.append( decodeURIComponent( parts[0] ), decodeURIComponent( parts[1].replace( /\+/g, ' ' ) ) );
                            }
                        } );
                        pendingRequestBody = fd;
                    }
                }
            } );
        }

        return _XHRSend.apply( this, arguments );
    };

} )( jQuery );
