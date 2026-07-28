/**
 * MBR Performance — RUM capture wrapper.
 *
 * Reads real-user Core Web Vitals (LCP, CLS, INP) from the vendored web-vitals
 * attribution build (window.webVitals) and beacons each metric to the plugin's
 * first-party REST endpoint. sendBeacon is used so reports survive page unload,
 * which is when LCP/CLS/INP finalise.
 *
 * No cookies, no external calls, no PII. Config is injected via wp_localize_script
 * as window.mbrpeRum: { endpoint, sampleRate (0..1), template, path }.
 */
( function () {
	'use strict';

	var cfg = window.mbrpeRum;
	var wv = window.webVitals;

	if ( ! cfg || ! wv || typeof navigator.sendBeacon !== 'function' ) {
		return;
	}

	// Client-side sampling gate — avoids sending (and the server storing) every hit.
	if ( Math.random() > cfg.sampleRate ) {
		return;
	}

	// Coarse device class from viewport, sent as a hint (server also verifies).
	function deviceHint() {
		var w = window.innerWidth || document.documentElement.clientWidth || 0;
		if ( w > 0 && w < 768 ) {
			return 'mobile';
		}
		if ( w >= 768 && w < 1024 ) {
			return 'tablet';
		}
		return 'desktop';
	}

	// Coarse browser family hint.
	function browserHint() {
		var ua = ( navigator.userAgent || '' ).toLowerCase();
		if ( ua.indexOf( 'edg/' ) !== -1 || ua.indexOf( 'edge' ) !== -1 ) {
			return 'edge';
		}
		if ( ua.indexOf( 'firefox' ) !== -1 ) {
			return 'firefox';
		}
		if ( ua.indexOf( 'chrome' ) !== -1 || ua.indexOf( 'crios' ) !== -1 ) {
			return 'chrome';
		}
		if ( ua.indexOf( 'safari' ) !== -1 ) {
			return 'safari';
		}
		return 'other';
	}

	// Pull the useful attribution bits per metric, trimmed to server column widths.
	function attribution( metric ) {
		var a = metric.attribution || {};
		var target = '';
		var detail = '';

		switch ( metric.name ) {
			case 'LCP':
				// The element that was the LCP candidate, or its resource URL.
				target = a.element || a.url || '';
				detail = a.lcpResourceEntry ? 'resource' : ( a.lcpEntry ? 'render' : '' );
				break;
			case 'INP':
				// The element interacted with, plus the event type and phase.
				target = a.interactionTarget || '';
				detail = ( a.interactionType || '' ) + ( a.longAnimationFrameEntries && a.longAnimationFrameEntries.length ? ' (LoAF)' : '' );
				break;
			case 'CLS':
				// The element responsible for the largest single layout shift.
				target = a.largestShiftTarget || '';
				detail = a.largestShiftTime ? String( Math.round( a.largestShiftTime ) ) : '';
				break;
		}

		return {
			target: String( target ).slice( 0, 255 ),
			detail: String( detail ).slice( 0, 255 )
		};
	}

	var device = deviceHint();
	var browser = browserHint();

	function send( metric ) {
		var attr = attribution( metric );
		var body = JSON.stringify( {
			metric: metric.name,
			value: metric.value,
			rating: metric.rating, // 'good' | 'needs-improvement' | 'poor'
			template: cfg.template,
			path: cfg.path,
			device: device,
			browser: browser,
			target: attr.target,
			detail: attr.detail
		} );

		try {
			navigator.sendBeacon( cfg.endpoint, new Blob( [ body ], { type: 'application/json' } ) );
		} catch ( e ) {
			// Swallow — RUM must never affect the page it measures.
		}
	}

	// Register the three metrics. onCLS/onINP report their final value at
	// page-hide; onLCP reports once the LCP is settled.
	if ( typeof wv.onLCP === 'function' ) {
		wv.onLCP( send );
	}
	if ( typeof wv.onCLS === 'function' ) {
		wv.onCLS( send );
	}
	if ( typeof wv.onINP === 'function' ) {
		wv.onINP( send );
	}
}() );
