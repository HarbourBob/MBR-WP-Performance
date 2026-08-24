<?php
/**
 * MBR Performance Doctor.
 *
 * Analyses a rendered front-end page and recommends which MBR Performance
 * settings will actually help this site — in priority order — instead of
 * presenting a wall of switches. v1 focuses on the render-blocking CSS-vs-JS
 * split, the single most decisive diagnostic, and maps it to the relevant
 * toggles, skipping anything already enabled.
 *
 * Advisory only: it recommends and links, it never auto-applies.
 *
 * @package MBR_Performance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MBRPE_Performance_Doctor {

	/**
	 * Singleton instance.
	 *
	 * @var MBRPE_Performance_Doctor|null
	 */
	private static $instance = null;

	/**
	 * Script src keywords that flag a non-critical, delay-able script.
	 *
	 * @var string[]
	 */
	private $delay_keywords = array(
		'google-analytics', 'googletagmanager', 'gtag', 'gtm.js', 'analytics',
		'facebook.net', 'fbevents', 'connect.facebook', 'hotjar', 'clarity.ms',
		'recaptcha', 'gstatic.com/recaptcha', 'intercom', 'drift', 'tawk',
		'crisp.chat', 'livechat', 'zendesk', 'hubspot', 'mailchimp',
		'latepoint', 'booking', 'pinterest', 'tiktok', 'twitter', 'linkedin',
	);

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_ajax_mbrpe_run_doctor', array( $this, 'ajax_run' ) );
		add_action( 'wp_ajax_mbrpe_run_doctor_site', array( $this, 'ajax_run_site' ) );
	}

	/**
	 * Is this URL on the site's own front end? Guards against scanning
	 * arbitrary third-party origins.
	 *
	 * @param string $url Candidate URL.
	 * @return bool
	 */
	private function same_origin( $url ) {
		return ( 0 === strpos( $url, home_url() ) || 0 === strpos( $url, site_url() ) );
	}

	/**
	 * AJAX entry point.
	 */
	public function ajax_run() {
		check_ajax_referer( 'mbrpe_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-performance' ) ) );
		}

		$url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : home_url( '/' );
		if ( '' === $url ) {
			$url = home_url( '/' );
		}

		// Only ever analyse this site's own front end.
		if ( ! $this->same_origin( $url ) ) {
			$url = home_url( '/' );
		}

		$result = $this->run( $url );

		if ( empty( $result['ok'] ) ) {
			wp_send_json_error( array( 'message' => $result['message'] ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX entry point for a multi-template (site-level) scan.
	 */
	public function ajax_run_site() {
		check_ajax_referer( 'mbrpe_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-performance' ) ) );
		}

		// Caller may supply URLs; otherwise we auto-discover key templates.
		$templates = array();
		if ( ! empty( $_POST['urls'] ) && is_array( $_POST['urls'] ) ) {
			foreach ( wp_unslash( $_POST['urls'] ) as $raw ) {
				$u = esc_url_raw( $raw );
				if ( '' !== $u && $this->same_origin( $u ) ) {
					$templates[] = array(
						'label' => $this->label_for_url( $u ),
						'url'   => $u,
					);
				}
			}
		}
		if ( empty( $templates ) ) {
			$templates = $this->default_templates();
		}

		wp_send_json_success( $this->run_site( $templates ) );
	}

	/**
	 * Discover a representative set of front-end templates to sample. Keeps the
	 * set small (home, blog index, a post, a page, and WooCommerce shop/product
	 * where present) so one scan reflects the site rather than a single page.
	 *
	 * @return array List of array( 'label' => string, 'url' => string ).
	 */
	private function default_templates() {
		$set  = array();
		$seen = array();
		$add  = function ( $label, $url ) use ( &$set, &$seen ) {
			if ( ! is_string( $url ) || '' === $url ) {
				return;
			}
			$key = untrailingslashit( $url );
			if ( isset( $seen[ $key ] ) ) {
				return;
			}
			$seen[ $key ] = true;
			$set[]        = array(
				'label' => $label,
				'url'   => $url,
			);
		};

		$add( __( 'Home', 'mbr-performance' ), home_url( '/' ) );

		// Static front page setup → also sample the posts page.
		if ( 'page' === get_option( 'show_on_front' ) ) {
			$blog_id = (int) get_option( 'page_for_posts' );
			if ( $blog_id ) {
				$add( __( 'Blog index', 'mbr-performance' ), get_permalink( $blog_id ) );
			}
		}

		// Latest published post.
		$posts = get_posts(
			array(
				'numberposts' => 1,
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);
		if ( ! empty( $posts ) ) {
			$add( __( 'Latest post', 'mbr-performance' ), get_permalink( $posts[0] ) );
		}

		// A representative page that isn't the static front page.
		$front_id = (int) get_option( 'page_on_front' );
		$pages    = get_posts(
			array(
				'numberposts' => 1,
				'post_status' => 'publish',
				'post_type'   => 'page',
				'exclude'     => $front_id ? array( $front_id ) : array(),
				'orderby'     => 'menu_order title',
				'order'       => 'ASC',
			)
		);
		if ( ! empty( $pages ) ) {
			$add( __( 'Sample page', 'mbr-performance' ), get_permalink( $pages[0] ) );
		}

		// WooCommerce shop + a product, when the store is present.
		if ( function_exists( 'wc_get_page_permalink' ) ) {
			$add( __( 'Shop', 'mbr-performance' ), wc_get_page_permalink( 'shop' ) );
			$products = get_posts(
				array(
					'numberposts' => 1,
					'post_status' => 'publish',
					'post_type'   => 'product',
				)
			);
			if ( ! empty( $products ) ) {
				$add( __( 'Product', 'mbr-performance' ), get_permalink( $products[0] ) );
			}
		}

		return $set;
	}

	/**
	 * Human label for a caller-supplied URL (its path, or "Home" for the root).
	 *
	 * @param string $url URL.
	 * @return string
	 */
	private function label_for_url( $url ) {
		$path = wp_parse_url( $url, PHP_URL_PATH );
		$path = $path ? trim( $path, '/' ) : '';
		return '' === $path ? __( 'Home', 'mbr-performance' ) : $path;
	}

	/**
	 * Run the analysis across a set of templates and aggregate the findings.
	 *
	 * @param array $templates List of array( 'label', 'url' ).
	 * @return array Structured site result.
	 */
	private function run_site( $templates ) {
		$results = array();
		foreach ( $templates as $tpl ) {
			$r         = $this->run( $tpl['url'], false );
			$results[] = array(
				'label'           => $tpl['label'],
				'url'             => $tpl['url'],
				'ok'              => ! empty( $r['ok'] ),
				'message'         => isset( $r['message'] ) ? $r['message'] : '',
				'summary'         => isset( $r['summary'] ) ? $r['summary'] : null,
				'recommendations' => isset( $r['recommendations'] ) ? $r['recommendations'] : array(),
			);
		}

		return array(
			'ok'        => true,
			'templates' => $results,
			'site'      => $this->aggregate( $results ),
		);
	}

	/**
	 * Aggregate per-template results into a site verdict plus de-duplicated
	 * recommendations tagged with how many templates each affects.
	 *
	 * @param array $results Per-template results from run_site().
	 * @return array
	 */
	private function aggregate( $results ) {
		$ok    = array_values(
			array_filter(
				$results,
				function ( $r ) {
					return ! empty( $r['ok'] );
				}
			)
		);
		$total = count( $ok );

		// Tally the dominant render-blocking verdict across templates.
		$dom = array(
			'js'       => 0,
			'css'      => 0,
			'balanced' => 0,
			'none'     => 0,
		);
		foreach ( $ok as $r ) {
			$d = isset( $r['summary']['dominant'] ) ? $r['summary']['dominant'] : 'none';
			if ( isset( $dom[ $d ] ) ) {
				$dom[ $d ]++;
			}
		}
		arsort( $dom );
		$top = $total ? key( $dom ) : 'none';

		// Group actionable recommendations by title; track which templates hit
		// each and keep the strongest tier seen.
		$tier_rank = array(
			'high'   => 3,
			'medium' => 2,
			'low'    => 1,
			'info'   => 0,
		);
		$groups = array();
		foreach ( $ok as $r ) {
			foreach ( $r['recommendations'] as $rec ) {
				if ( empty( $rec['tier'] ) || 'info' === $rec['tier'] ) {
					continue; // Skip closing/contextual notes from the site roll-up.
				}
				$key = $rec['title'];
				if ( ! isset( $groups[ $key ] ) ) {
					$groups[ $key ] = array(
						'title'   => $rec['title'],
						'tier'    => $rec['tier'],
						'detail'  => $rec['detail'],
						'tab'     => isset( $rec['tab'] ) ? $rec['tab'] : '',
						'warning' => isset( $rec['warning'] ) ? $rec['warning'] : '',
						'labels'  => array(),
					);
				}
				if ( $tier_rank[ $rec['tier'] ] > $tier_rank[ $groups[ $key ]['tier'] ] ) {
					$groups[ $key ]['tier'] = $rec['tier'];
				}
				if ( ! in_array( $r['label'], $groups[ $key ]['labels'], true ) ) {
					$groups[ $key ]['labels'][] = $r['label'];
				}
			}
		}

		$site_recs = array();
		foreach ( $groups as $g ) {
			$coverage      = count( $g['labels'] );
			$g['coverage'] = $coverage;
			$g['total']    = $total;
			$g['scope']    = ( $total > 0 && $coverage >= $total ) ? 'site-wide' : 'partial';
			$site_recs[]   = $g;
		}

		usort(
			$site_recs,
			function ( $a, $b ) use ( $tier_rank ) {
				if ( $tier_rank[ $a['tier'] ] !== $tier_rank[ $b['tier'] ] ) {
					return $tier_rank[ $b['tier'] ] - $tier_rank[ $a['tier'] ];
				}
				if ( $a['coverage'] !== $b['coverage'] ) {
					return $b['coverage'] - $a['coverage'];
				}
				return strcmp( $a['title'], $b['title'] );
			}
		);

		// Real-user field data is site-wide rather than per-template, so it is
		// attached once here rather than being deduplicated across templates.
		// It is also added after the sort and the info-tier filter above, which
		// strips contextual notes from the roll-up — field status notes must
		// survive that filter, otherwise the scan reports "no recommendations"
		// while RUM is sitting on real data.
		$field = $this->field_recommendations();
		if ( ! empty( $field ) ) {
			foreach ( $field as &$f ) {
				$f['coverage'] = $total;
				$f['total']    = $total;
				$f['scope']    = 'site-wide';
				$f['labels']   = array();
			}
			unset( $f );
			$site_recs = array_merge( $field, $site_recs );
		}

		switch ( $top ) {
			case 'js':
				$verdict = __( 'JavaScript is the recurring render-blocking bottleneck across your templates.', 'mbr-performance' );
				break;
			case 'css':
				$verdict = __( 'CSS is the recurring render-blocking bottleneck across your templates.', 'mbr-performance' );
				break;
			case 'balanced':
				$verdict = __( 'CSS and JavaScript both block rendering across your templates.', 'mbr-performance' );
				break;
			default:
				$verdict = __( 'Render-blocking is clean across the templates sampled.', 'mbr-performance' );
		}

		return array(
			'templates_ok'    => $total,
			'verdict'         => $verdict,
			'dominant'        => $top,
			'recommendations' => $site_recs,
		);
	}

	/**
	 * Run the full analysis for a URL.
	 *
	 * @param string $url Front-end URL to analyse.
	 * @return array Structured result.
	 */
	public function run( $url, $include_field = true ) {
		$html = $this->capture( $url );
		if ( is_wp_error( $html ) ) {
			return array(
				'ok'      => false,
				'message' => $html->get_error_message(),
			);
		}

		$xpath    = $this->build_xpath( $html );
		$blocking = $this->find_render_blocking( $xpath );
		$images   = $this->find_images( $xpath );
		$modules  = $this->find_modules( $xpath );
		$options  = mbrpe()->get_options();

		$summary         = $this->summarise( $blocking, $images, $modules );
		$recommendations = $this->build_recommendations( $blocking, $images, $summary, $options );

		// Prepend real-user field data when RUM is collecting. Field beats
		// synthetic: these are what actual visitors experienced, and they
		// surface INP, which a static scan cannot see at all.
		//
		// Skipped during a site scan ($include_field = false) because field
		// data is site-wide, not per-template — the scan attaches it once at
		// the site level instead of repeating it under every template.
		if ( $include_field ) {
			$field = $this->field_recommendations();
			if ( ! empty( $field ) ) {
				$recommendations = array_merge( $field, $recommendations );
			}
		}

		return array(
			'ok'              => true,
			'url'             => $url,
			'summary'         => $summary,
			'recommendations' => $recommendations,
		);
	}

	/**
	 * Fetch the rendered page as a logged-out visitor, bypassing the page cache.
	 *
	 * @param string $url URL to fetch.
	 * @return string|WP_Error HTML body or error.
	 */
	private function capture( $url ) {
		$bust     = add_query_arg( 'mbrpe_doctor', (string) time(), $url );
		$response = wp_remote_get(
			$bust,
			array(
				'timeout'     => 20,
				'redirection' => 2,
				'sslverify'   => false,
				'user-agent'  => 'MBR Performance Doctor',
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'mbrpe_doctor_fetch', __( 'Could not fetch the page. Your host may block loopback requests.', 'mbr-performance' ) );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 400 ) {
			return new WP_Error(
				'mbrpe_doctor_http',
				/* translators: %d: HTTP status code */
				sprintf( __( 'The page returned HTTP %d.', 'mbr-performance' ), $code )
			);
		}

		$body = wp_remote_retrieve_body( $response );
		if ( '' === trim( $body ) ) {
			return new WP_Error( 'mbrpe_doctor_empty', __( 'The page returned no HTML.', 'mbr-performance' ) );
		}

		return $body;
	}

	/**
	 * Build a DOMXPath over the full document (warnings suppressed).
	 *
	 * @param string $html Page HTML.
	 * @return DOMXPath
	 */
	private function build_xpath( $html ) {
		$dom  = new DOMDocument();
		$prev = libxml_use_internal_errors( true );
		$dom->loadHTML( '<?xml encoding="utf-8"?>' . $html );
		libxml_clear_errors();
		libxml_use_internal_errors( $prev );
		return new DOMXPath( $dom );
	}

	/**
	 * Find render-blocking stylesheets and scripts across the whole document.
	 *
	 * @param DOMXPath $xpath Document xpath.
	 * @return array {
	 *     @type array $css Blocking stylesheets.
	 *     @type array $js  Blocking scripts.
	 * }
	 */
	private function find_render_blocking( $xpath ) {
		$css = array();
		$js  = array();

		// Render-blocking stylesheets: rel=stylesheet, not deferred to print.
		foreach ( $xpath->query( '//link[@rel="stylesheet"]' ) as $link ) {
			$href  = trim( (string) $link->getAttribute( 'href' ) );
			$media = strtolower( trim( (string) $link->getAttribute( 'media' ) ) );
			if ( '' === $href || 'print' === $media ) {
				continue;
			}
			$css[] = $this->describe_asset( $href );
		}

		// Render-blocking scripts: has src, no defer/async, not a module.
		// Scanned across head and body so a late-body blocker isn't missed.
		foreach ( $xpath->query( '//script[@src]' ) as $script ) {
			if ( $script->hasAttribute( 'defer' ) || $script->hasAttribute( 'async' ) ) {
				continue;
			}
			if ( 'module' === strtolower( (string) $script->getAttribute( 'type' ) ) ) {
				continue;
			}
			$src = trim( (string) $script->getAttribute( 'src' ) );
			if ( '' === $src ) {
				continue;
			}
			$asset           = $this->describe_asset( $src );
			$asset['jquery'] = ( false !== stripos( $src, '/jquery' ) && false === stripos( $src, 'jquery-migrate' ) );
			$asset['delay']  = $this->is_delay_candidate( $src );
			$js[]            = $asset;
		}

		return array(
			'css' => $css,
			'js'  => $js,
		);
	}

	/**
	 * Inspect the page's ES module layer: module script tags, the import map,
	 * and modulepreload hints — recording where in the document each appears.
	 *
	 * Position is the whole point here. WordPress prints the import map, the
	 * module tags and their preload hints in `wp_head` on a block theme and in
	 * `wp_footer` on a classic one, and the classic case is where hints stop
	 * being worth anything. This is also the only place we can see whether the
	 * plugin's own hoisted hints actually reached the head, since that depends
	 * on the learned map having been populated by an earlier visit.
	 *
	 * @param DOMXPath $xpath Document xpath.
	 * @return array {
	 *     @type int  $modules           Module script tags found.
	 *     @type int  $modules_in_head   How many of those are in <head>.
	 *     @type bool $importmap         An import map is printed.
	 *     @type bool $importmap_in_head The import map is in <head>.
	 *     @type int  $preloads          modulepreload hints found.
	 *     @type int  $preloads_in_head  How many of those are in <head>.
	 *     @type int  $hoisted           Hints emitted by this plugin.
	 * }
	 */
	private function find_modules( $xpath ) {
		$out = array(
			'modules'           => 0,
			'modules_in_head'   => 0,
			'importmap'         => false,
			'importmap_in_head' => false,
			'preloads'          => 0,
			'preloads_in_head'  => 0,
			'hoisted'           => 0,
		);

		// Attribute *values* are not normalised by the HTML parser, so match
		// case-insensitively in PHP rather than trusting an XPath equality test.
		foreach ( $xpath->query( '//script[@type]' ) as $node ) {
			$type = strtolower( trim( (string) $node->getAttribute( 'type' ) ) );

			if ( 'module' === $type ) {
				$out['modules']++;
				if ( $this->in_head( $node ) ) {
					$out['modules_in_head']++;
				}
				continue;
			}

			if ( 'importmap' === $type ) {
				$out['importmap'] = true;
				if ( $this->in_head( $node ) ) {
					$out['importmap_in_head'] = true;
				}
			}
		}

		foreach ( $xpath->query( '//link[@rel]' ) as $node ) {
			// rel is a space-separated token list; modulepreload is used alone
			// in practice, but tokenise rather than assume it.
			$rels = preg_split( '/\s+/', strtolower( trim( (string) $node->getAttribute( 'rel' ) ) ) );
			if ( ! in_array( 'modulepreload', (array) $rels, true ) ) {
				continue;
			}

			$out['preloads']++;
			if ( $this->in_head( $node ) ) {
				$out['preloads_in_head']++;
			}
			if ( $node->hasAttribute( 'data-mbrpe-module' ) ) {
				$out['hoisted']++;
			}
		}

		return $out;
	}

	/**
	 * Whether a parsed node sits inside <head>.
	 *
	 * The HTML parser closes <head> at the first piece of body content, so
	 * anything printed by wp_footer lands under <body> — which is exactly the
	 * distinction we need and cannot get from the raw markup alone.
	 *
	 * @param DOMNode $node Node to test.
	 * @return bool
	 */
	private function in_head( $node ) {
		for ( $parent = $node->parentNode; $parent instanceof DOMElement; $parent = $parent->parentNode ) {
			if ( 'head' === strtolower( $parent->nodeName ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Inspect <img> elements for common, fixable issues.
	 *
	 * @param DOMXPath $xpath Document xpath.
	 * @return array Counts: total, missing_dimensions, legacy_format, not_lazy.
	 */
	private function find_images( $xpath ) {
		$total       = 0;
		$missing_dim = 0;
		$legacy      = 0;
		$not_lazy    = 0;
		$index       = 0;

		foreach ( $xpath->query( '//img' ) as $img ) {
			$src      = trim( (string) $img->getAttribute( 'src' ) );
			$data_src = trim( (string) $img->getAttribute( 'data-src' ) );
			$probe    = '' !== $src ? $src : $data_src;
			if ( '' === $probe ) {
				continue;
			}

			// Skip tracking/spacer pixels.
			$w = trim( (string) $img->getAttribute( 'width' ) );
			$h = trim( (string) $img->getAttribute( 'height' ) );
			if ( ( '1' === $w && '1' === $h ) ) {
				continue;
			}

			$total++;
			$index++;

			if ( '' === $w || '' === $h ) {
				$missing_dim++;
			}

			$low = strtolower( $probe );
			if ( preg_match( '/\.(jpe?g|png)(\?|#|$)/', $low ) ) {
				$legacy++;
			}

			// Lazy: an image past the first couple (likely above the fold) that
			// is neither marked loading="lazy" nor already JS-lazy (data-src).
			$loading = strtolower( (string) $img->getAttribute( 'loading' ) );
			$is_lazy = ( 'lazy' === $loading ) || ( '' !== $data_src );
			if ( $index > 2 && ! $is_lazy ) {
				$not_lazy++;
			}
		}

		return array(
			'total'              => $total,
			'missing_dimensions' => $missing_dim,
			'legacy_format'      => $legacy,
			'not_lazy'           => $not_lazy,
		);
	}

	/**
	 * Build a size/locality descriptor for an asset URL.
	 *
	 * @param string $url Asset URL.
	 * @return array
	 */
	private function describe_asset( $url ) {
		$abs   = $this->absolutize( $url );
		$path  = $this->url_to_path( $abs );
		$bytes = 0;
		$ext   = true;
		if ( '' !== $path && is_file( $path ) ) {
			$bytes = (int) filesize( $path );
			$ext   = false;
		}
		return array(
			'url'      => $abs,
			'name'     => $this->friendly_name( $abs ),
			'bytes'    => $bytes,
			'external' => $ext,
		);
	}

	/**
	 * Summarise the blocking findings into a verdict.
	 *
	 * @param array $blocking Output of find_render_blocking().
	 * @return array
	 */
	private function summarise( $blocking, $images, $modules = array() ) {
		$css_bytes = 0;
		foreach ( $blocking['css'] as $a ) {
			$css_bytes += $a['bytes'];
		}
		$js_bytes = 0;
		foreach ( $blocking['js'] as $a ) {
			$js_bytes += $a['bytes'];
		}

		$css_count = count( $blocking['css'] );
		$js_count  = count( $blocking['js'] );

		$dominant = 'none';
		if ( $css_count || $js_count ) {
			if ( $js_bytes > $css_bytes * 1.2 ) {
				$dominant = 'js';
			} elseif ( $css_bytes > $js_bytes * 1.2 ) {
				$dominant = 'css';
			} else {
				$dominant = 'balanced';
			}
		}

		$has_image_issue = ( $images['missing_dimensions'] > 0 || $images['legacy_format'] > 0 || $images['not_lazy'] > 2 );

		switch ( $dominant ) {
			case 'js':
				$verdict = __( 'JavaScript is your main render-blocking bottleneck.', 'mbr-performance' );
				break;
			case 'css':
				$verdict = __( 'CSS is your main render-blocking bottleneck.', 'mbr-performance' );
				break;
			case 'balanced':
				$verdict = __( 'CSS and JavaScript are both blocking rendering roughly equally.', 'mbr-performance' );
				break;
			default:
				$verdict = $has_image_issue
					? __( 'Render-blocking is clean — your main opportunities are with images.', 'mbr-performance' )
					: __( 'No render-blocking resources detected — this page is already lean.', 'mbr-performance' );
		}

		return array(
			'verdict'         => $verdict,
			'dominant'        => $dominant,
			'css_bytes'       => $css_bytes,
			'css_count'       => $css_count,
			'js_bytes'        => $js_bytes,
			'js_count'        => $js_count,
			'css_bytes_human' => size_format( $css_bytes ),
			'js_bytes_human'  => size_format( $js_bytes ),
			'images'          => $images,
			'modules'         => $modules,
		);
	}

	/**
	 * Build recommendations from real-user field data (RUM), when available.
	 *
	 * Returns high/medium-tier recommendations naming the actual metric, its
	 * p75, and the most common culprit element/handler. Degrades to an empty
	 * array when RUM is off, missing, or has too few samples — so the Doctor
	 * falls back cleanly to synthetic-only.
	 *
	 * @return array[]
	 */
	private function field_recommendations() {
		if ( ! class_exists( 'MBRPE_RUM' ) ) {
			return array();
		}
		$rum = mbrpe()->get_options( 'rum' );
		if ( empty( $rum['enabled'] ) ) {
			return array();
		}

		// Roll any raw samples that have arrived since the last aggregation, so
		// the Doctor reflects traffic from minutes ago rather than waiting for
		// the nightly cron. Throttled internally.
		MBRPE_RUM::maybe_aggregate();

		$scores = MBRPE_RUM::scorecard();
		if ( empty( $scores ) ) {
			// RUM is on but nothing has been aggregated yet. Say so rather than
			// staying silent, which is indistinguishable from a broken feature.
			$pending = MBRPE_RUM::row_counts();
			return array(
				array(
					'tier'    => 'info',
					'title'   => __( 'Real-user data: still collecting', 'mbr-performance' ),
					'detail'  => $pending['raw'] > 0
						? sprintf(
							/* translators: %s: number of raw samples */
							__( 'RUM is enabled and has %s raw sample(s) recorded, but no daily aggregates yet. Field-based advice appears here once aggregation has run — open the RUM tab, which aggregates on demand.', 'mbr-performance' ),
							number_format_i18n( $pending['raw'] )
						)
						: __( 'RUM is enabled but no samples have arrived yet. Visit a few pages on the front end as a logged-out visitor, then re-run this scan.', 'mbr-performance' ),
					'tab'     => 'rum',
					'warning' => '',
				),
			);
		}

		$thr  = MBRPE_RUM::thresholds();
		$min  = MBRPE_RUM::MIN_SAMPLES;
		$recs = array();

		// INP — the metric a synthetic scan literally cannot measure.
		if ( isset( $scores['INP'] ) && (int) $scores['INP']['samples'] >= $min ) {
			$p75 = (float) $scores['INP']['p75'];
			if ( $p75 > $thr['INP']['good'] ) {
				$culprit = MBRPE_RUM::worst_target( 'INP' );
				$recs[]  = array(
					'tier'    => $p75 > $thr['INP']['poor'] ? 'high' : 'medium',
					'title'   => __( 'Real-user data: INP needs work', 'mbr-performance' ),
					'detail'  => sprintf(
						/* translators: 1: INP in ms, 2: optional culprit clause */
						__( 'Your real p75 INP is %1$dms%2$s. INP only exists when someone interacts, so a static scan cannot see it — this is field-only. Delaying non-critical JavaScript (and loading heavy widgets on interaction) is the lever.', 'mbr-performance' ),
						(int) round( $p75 ),
						$culprit ? sprintf(
							/* translators: %s: element/handler selector */
							__( ', most often on %s', 'mbr-performance' ),
							$culprit
						) : ''
					),
					'tab'     => 'javascript',
					'warning' => '',
				);
			}
		}

		// LCP.
		if ( isset( $scores['LCP'] ) && (int) $scores['LCP']['samples'] >= $min ) {
			$p75 = (float) $scores['LCP']['p75'];
			if ( $p75 > $thr['LCP']['good'] ) {
				$culprit = MBRPE_RUM::worst_target( 'LCP' );
				$recs[]  = array(
					'tier'    => $p75 > $thr['LCP']['poor'] ? 'high' : 'medium',
					'title'   => __( 'Real-user data: LCP needs work', 'mbr-performance' ),
					'detail'  => sprintf(
						/* translators: 1: LCP in seconds, 2: optional culprit clause */
						__( 'Your real p75 LCP is %1$ss%2$s. Preload the LCP image, serve it as AVIF/WebP, and set fetchpriority=high — those are the levers that move it.', 'mbr-performance' ),
						number_format( $p75 / 1000, 2 ),
						$culprit ? sprintf(
							/* translators: %s: element selector */
							__( ', and the LCP element is usually %s', 'mbr-performance' ),
							$culprit
						) : ''
					),
					'tab'     => 'preloading',
					'warning' => '',
				);
			}
		}

		// CLS.
		if ( isset( $scores['CLS'] ) && (int) $scores['CLS']['samples'] >= $min ) {
			$p75 = (float) $scores['CLS']['p75'];
			if ( $p75 > $thr['CLS']['good'] ) {
				$culprit = MBRPE_RUM::worst_target( 'CLS' );
				$recs[]  = array(
					'tier'    => $p75 > $thr['CLS']['poor'] ? 'high' : 'medium',
					'title'   => __( 'Real-user data: CLS needs work', 'mbr-performance' ),
					'detail'  => sprintf(
						/* translators: 1: CLS value, 2: optional culprit clause */
						__( 'Your real p75 CLS is %1$s%2$s. Adding width/height to images is the usual fix; a font swapping to different metrics is the other common cause.', 'mbr-performance' ),
						number_format( $p75, 3 ),
						$culprit ? sprintf(
							/* translators: %s: element selector */
							__( ', with the largest shift usually from %s', 'mbr-performance' ),
							$culprit
						) : ''
					),
					'tab'     => 'webp',
					'warning' => '',
				);
			}
		}

		// If aggregates exist but nothing actionable fired, explain why —
		// either the metrics are genuinely fine, or there aren't enough
		// samples yet to trust them.
		if ( empty( $recs ) ) {
			$thin = array();
			foreach ( $scores as $metric => $card ) {
				if ( (int) $card['samples'] < $min ) {
					$thin[] = $metric;
				}
			}

			if ( ! empty( $thin ) ) {
				// Show the actual readings — even provisional numbers are worth
				// seeing, they just aren't a basis for action yet.
				$readings = array();
				foreach ( array( 'LCP', 'INP', 'CLS' ) as $metric ) {
					if ( ! isset( $scores[ $metric ] ) ) {
						continue;
					}
					$val = (float) $scores[ $metric ]['p75'];
					if ( 'CLS' === $metric ) {
						$shown = number_format( $val, 3 );
					} elseif ( 'LCP' === $metric ) {
						$shown = number_format( $val / 1000, 2 ) . 's';
					} else {
						$shown = (int) round( $val ) . 'ms';
					}
					$readings[] = sprintf(
						/* translators: 1: metric name, 2: value, 3: sample count */
						__( '%1$s %2$s (%3$d samples)', 'mbr-performance' ),
						$metric,
						$shown,
						(int) $scores[ $metric ]['samples']
					);
				}

				$recs[] = array(
					'tier'    => 'info',
					'title'   => __( 'Real-user data: provisional', 'mbr-performance' ),
					'detail'  => sprintf(
						/* translators: 1: readings list, 2: minimum sample count */
						__( 'Field data so far — %1$s. That is under %2$d samples, so it is too thin to act on yet, but collection is working. Send more real traffic and these become actionable.', 'mbr-performance' ),
						implode( ', ', $readings ),
						$min
					),
					'tab'     => 'rum',
					'warning' => '',
				);
			} else {
				$recs[] = array(
					'tier'    => 'info',
					'title'   => __( 'Real-user data: Core Web Vitals are passing', 'mbr-performance' ),
					'detail'  => __( 'Your real visitors are seeing LCP, CLS and INP within the "good" thresholds. Anything below is synthetic analysis — worth doing, but your field data says the page is already performing well for actual users.', 'mbr-performance' ),
					'tab'     => 'rum',
					'warning' => '',
				);
			}
		}

		return $recs;
	}

	/**
	 * Map findings to prioritised recommendations against current settings.
	 *
	 * @param array $blocking Findings.
	 * @param array $summary  Summary.
	 * @param array $options  Current plugin options.
	 * @return array[]
	 */
	private function build_recommendations( $blocking, $images, $summary, $options ) {
		$css  = isset( $options['css'] ) ? $options['css'] : array();
		$js   = isset( $options['javascript'] ) ? $options['javascript'] : array();
		$imgd = isset( $options['image_dimensions'] ) ? $options['image_dimensions'] : array();
		$webp = isset( $options['webp'] ) ? $options['webp'] : array();
		$lazy = isset( $options['lazy_loading'] ) ? $options['lazy_loading'] : array();
		$on   = function ( $arr, $key ) {
			return ! empty( $arr[ $key ] );
		};

		$recs = array();

		// --- JavaScript ---
		if ( $summary['js_count'] > 0 ) {
			if ( ! $on( $js, 'defer_javascript' ) ) {
				$recs[] = array(
					'tier'    => 'high',
					'title'   => __( 'Defer JavaScript', 'mbr-performance' ),
					'detail'  => sprintf(
						/* translators: 1: number of scripts, 2: human file size */
						__( '%1$d scripts (~%2$s) are blocking first paint. Deferring them moves them out of the critical path.', 'mbr-performance' ),
						$summary['js_count'],
						$summary['js_bytes_human']
					),
					'tab'     => 'javascript',
					'warning' => '',
				);
			}

			$delay = array();
			$has_jquery = false;
			foreach ( $blocking['js'] as $a ) {
				if ( ! empty( $a['delay'] ) ) {
					$delay[] = $a['name'];
				}
				if ( ! empty( $a['jquery'] ) ) {
					$has_jquery = true;
				}
			}
			if ( ! empty( $delay ) && ! $on( $js, 'delay_javascript' ) ) {
				$recs[] = array(
					'tier'    => 'high',
					'title'   => __( 'Delay non-critical JavaScript', 'mbr-performance' ),
					'detail'  => sprintf(
						/* translators: %s: comma-separated script names */
						__( 'These scripts are not needed for first paint and can wait until interaction: %s', 'mbr-performance' ),
						implode( ', ', array_slice( array_unique( $delay ), 0, 8 ) )
					),
					'tab'     => 'javascript',
					'warning' => '',
				);
			}
			if ( $has_jquery && ! $on( $js, 'defer_jquery' ) ) {
				$recs[] = array(
					'tier'    => 'medium',
					'title'   => __( 'Consider deferring jQuery', 'mbr-performance' ),
					'detail'  => __( 'jQuery is render-blocking. Deferring it can give a real gain, but many themes and inline scripts assume it loads synchronously.', 'mbr-performance' ),
					'tab'     => 'javascript',
					'warning' => __( 'Risky — test sliders, menus and forms on a staging copy before enabling.', 'mbr-performance' ),
				);
			}
		}

		// --- Script Modules / Interactivity API ---
		//
		// Modules never enter the classic script queue, so none of the checks
		// above can see them. They are also the one asset class where the
		// plugin's job is mostly to hint rather than to rewrite: modules defer
		// by spec, and combining them would break the import map.
		$mods     = isset( $summary['modules'] ) && is_array( $summary['modules'] ) ? $summary['modules'] : array();
		$mod_opts = isset( $options['modules'] ) ? $options['modules'] : array();
		$mod_api  = class_exists( 'MBRPE_Module_Scripts' ) && MBRPE_Module_Scripts::api_available();
		$block_th = function_exists( 'wp_is_block_theme' ) && wp_is_block_theme();
		$mod_n    = isset( $mods['modules'] ) ? (int) $mods['modules'] : 0;

		if ( $mod_api && $mod_n > 0 ) {

			// Correctness, not speed: an import map must be parsed before any
			// module that relies on it. A module in the head with the map in
			// the footer means bare specifier imports cannot resolve — usually
			// a theme or plugin printing its own module tag directly into the
			// head, ahead of where WordPress puts the map on a classic theme.
			if ( ! empty( $mods['importmap'] ) && empty( $mods['importmap_in_head'] ) && ! empty( $mods['modules_in_head'] ) ) {
				$recs[] = array(
					'tier'    => 'high',
					'title'   => __( 'Module script runs before the import map', 'mbr-performance' ),
					'detail'  => sprintf(
						/* translators: %d: number of module scripts in the head */
						__( '%d module script(s) are printed in the head, but the import map that resolves their imports is printed in the footer. Any bare specifier import in those modules — anything importing @wordpress/interactivity, for example — will fail to resolve. This is a theme or plugin printing a module tag directly rather than registering it, so it needs fixing at source.', 'mbr-performance' ),
						(int) $mods['modules_in_head']
					),
					'tab'     => '',
					'warning' => __( 'Check the browser console on this page for module resolution errors.', 'mbr-performance' ),
				);
			}

			if ( $block_th ) {
				$recs[] = array(
					'tier'    => 'info',
					'title'   => __( 'Module preloading is already handled', 'mbr-performance' ),
					'detail'  => sprintf(
						/* translators: %d: number of module scripts */
						__( 'This page loads %d ES module script(s). You are on a block theme, so WordPress prints the import map and its preload hints in the head already — which is the right place. Nothing to change.', 'mbr-performance' ),
						$mod_n
					),
					'tab'     => '',
					'warning' => '',
				);
			} elseif ( empty( $mod_opts['preload_hoist'] ) ) {
				$recs[] = array(
					'tier'    => 'medium',
					'title'   => __( 'Hoist module preloads', 'mbr-performance' ),
					'detail'  => sprintf(
						/* translators: 1: number of module scripts, 2: number of preload hints found in the footer */
						__( 'This page loads %1$d ES module script(s) — Interactivity API or interactive block code. On a classic theme WordPress only discovers these while the body renders, so it prints all %2$d of their preload hints in the footer, alongside the scripts they were meant to front-run. Preload hoisting learns this URL\'s module set and emits the hints in the head instead, so the browser starts fetching the graph while it is still parsing the head.', 'mbr-performance' ),
						$mod_n,
						(int) ( isset( $mods['preloads'] ) ? $mods['preloads'] : 0 ) - (int) ( isset( $mods['preloads_in_head'] ) ? $mods['preloads_in_head'] : 0 )
					),
					'tab'     => 'javascript',
					'warning' => '',
				);
			} elseif ( empty( $mods['hoisted'] ) ) {
				$recs[] = array(
					'tier'    => 'info',
					'title'   => __( 'Module preloads are still learning this URL', 'mbr-performance' ),
					'detail'  => __( 'Preload hoisting is enabled but no hoisted hints appeared on this page, which means this URL has not been learned yet. The scan you just ran counts as the teaching visit, so re-run the Doctor on this page and the hints should show. If they still do not, the page may be served from a full-page cache that predates the setting — clear it and try again.', 'mbr-performance' ),
					'tab'     => 'javascript',
					'warning' => '',
				);
			} else {
				$recs[] = array(
					'tier'    => 'info',
					'title'   => __( 'Module preloads are being hoisted', 'mbr-performance' ),
					'detail'  => sprintf(
						/* translators: 1: number of hoisted hints, 2: number of module scripts */
						__( '%1$d module preload hint(s) are being emitted in the head for the %2$d module script(s) on this page. This is working as intended — nothing to do.', 'mbr-performance' ),
						(int) $mods['hoisted'],
						$mod_n
					),
					'tab'     => '',
					'warning' => '',
				);
			}
		}

		// --- CSS ---
		//
		// Mode A and Mode B are alternatives, so the Doctor recommends one or the
		// other and never both. When Mode B is on it owns CSS delivery outright,
		// and the interesting question stops being "should you optimise this?" and
		// becomes "is it actually working on this page?" — because Mode B removes
		// stylesheets, and a page where it silently did nothing looks identical to
		// a page it was never enabled for.
		$css_heavy = ( $summary['css_bytes'] > 30720 || $summary['css_count'] >= 3 );
		$modeb_on  = $on( $css, 'modeb_enabled' );

		if ( $modeb_on ) {
			if ( 0 === (int) $summary['css_count'] ) {
				$recs[] = array(
					'tier'    => 'info',
					'title'   => __( 'Used CSS Mode B is working here', 'mbr-performance' ),
					'detail'  => __( 'No render-blocking stylesheets are left on this page — Mode B has learned this template, inlined what it uses and removed the sheets it replaced. Nothing to do.', 'mbr-performance' ),
					'tab'     => '',
					'warning' => '',
				);
			} else {
				$recs[] = array(
					'tier'    => $css_heavy ? 'medium' : 'info',
					'title'   => __( 'Mode B is on, but this page still has render-blocking CSS', 'mbr-performance' ),
					'detail'  => sprintf(
						/* translators: 1: number of stylesheets, 2: human file size */
						__( '%1$d stylesheet(s) (~%2$s) are still blocking render here. Usually that means this template has not finished learning yet — the first few visits to each template are served untouched, so re-run this scan and the number should fall. If it does not, these are sheets Mode B deliberately leaves alone: ones carrying an @import, ones limited to a narrow media query, ones on your exclusion lists, and ones it never saw while learning. Check the template cache on the CSS tab to see which templates are learned.', 'mbr-performance' ),
						$summary['css_count'],
						$summary['css_bytes_human']
					),
					'tab'     => 'css',
					'warning' => '',
				);
			}
		} elseif ( $summary['css_count'] > 0 && $css_heavy ) {
			if ( ! $on( $css, 'remove_unused_css' ) ) {
				$recs[] = array(
					'tier'    => ( 'css' === $summary['dominant'] ) ? 'high' : 'medium',
					'title'   => __( 'Generate Used CSS (Mode A)', 'mbr-performance' ),
					'detail'  => sprintf(
						/* translators: 1: number of stylesheets, 2: human file size */
						__( '%1$d stylesheets (~%2$s) are render-blocking. Used CSS inlines the critical part and defers the rest. Combine CSS is a simpler, lighter-touch alternative, and Mode B is the aggressive one — it removes the sheets rather than deferring them, at the cost of needing testing. Enable one of the three, not several.', 'mbr-performance' ),
						$summary['css_count'],
						$summary['css_bytes_human']
					),
					'tab'     => 'css',
					'warning' => '',
				);
			}
		} elseif ( $summary['css_count'] > 0 && ! $css_heavy ) {
			$recs[] = array(
				'tier'    => 'info',
				'title'   => __( 'CSS is not your bottleneck', 'mbr-performance' ),
				'detail'  => __( 'Your render-blocking CSS is light, so Used CSS would add complexity for little gain here. You can leave it off.', 'mbr-performance' ),
				'tab'     => '',
				'warning' => '',
			);
		}

		// --- Images ---
		if ( $images['missing_dimensions'] > 0 && ! $on( $imgd, 'add_missing_dimensions' ) ) {
			$recs[] = array(
				'tier'    => 'medium',
				'title'   => __( 'Add image dimensions', 'mbr-performance' ),
				'detail'  => sprintf(
					/* translators: %d: number of images */
					__( '%d image(s) have no width/height, which causes layout shift (CLS) as they load. Adding dimensions reserves their space up front.', 'mbr-performance' ),
					$images['missing_dimensions']
				),
				'tab'     => 'webp',
				'warning' => '',
			);
		}
		if ( $images['legacy_format'] > 0 && ! $on( $webp, 'auto_convert' ) ) {
			$recs[] = array(
				'tier'    => 'medium',
				'title'   => __( 'Convert images to AVIF / WebP', 'mbr-performance' ),
				'detail'  => sprintf(
					/* translators: %d: number of images */
					__( '%d image(s) are JPEG or PNG. Next-gen formats typically cut their weight by half or more.', 'mbr-performance' ),
					$images['legacy_format']
				),
				'tab'     => 'webp',
				'warning' => '',
			);
		}
		if ( $images['not_lazy'] > 2 && ! $on( $lazy, 'lazy_load_images' ) ) {
			$recs[] = array(
				'tier'    => 'low',
				'title'   => __( 'Lazy-load images', 'mbr-performance' ),
				'detail'  => sprintf(
					/* translators: %d: number of images */
					__( '%d image(s) below the fold load up front. Lazy loading defers them until they are about to be seen.', 'mbr-performance' ),
					$images['not_lazy']
				),
				'tab'     => 'lazy-loading',
				'warning' => '',
			);
		}

		// --- Closing guidance ---
		$recs[] = array(
			'tier'    => 'info',
			'title'   => __( 'Confirm with a real measurement', 'mbr-performance' ),
			'detail'  => __( 'This is a static analysis, not a live timing. After applying changes, re-run PageSpeed Insights a few times and take the median — mobile scores vary run to run.', 'mbr-performance' ),
			'tab'     => '',
			'warning' => '',
		);

		return $recs;
	}

	/* --------------------------------------------------------------------- */

	private function is_delay_candidate( $src ) {
		$src = strtolower( $src );
		foreach ( $this->delay_keywords as $kw ) {
			if ( false !== strpos( $src, $kw ) ) {
				return true;
			}
		}
		return false;
	}

	private function absolutize( $url ) {
		if ( 0 === strpos( $url, '//' ) ) {
			return ( is_ssl() ? 'https:' : 'http:' ) . $url;
		}
		if ( 0 === strpos( $url, 'http' ) ) {
			return $url;
		}
		if ( 0 === strpos( $url, '/' ) ) {
			return rtrim( site_url(), '/' ) . $url;
		}
		return $url;
	}

	private function url_to_path( $url ) {
		if ( class_exists( 'MBRPE_CSS_Optimizations' ) && method_exists( 'MBRPE_CSS_Optimizations', 'url_to_path' ) ) {
			$p = MBRPE_CSS_Optimizations::url_to_path( $url );
			if ( '' !== $p ) {
				return $p;
			}
		}
		$site = site_url();
		if ( 0 === strpos( $url, $site ) ) {
			$rel  = ltrim( substr( $url, strlen( $site ) ), '/' );
			$rel  = preg_replace( '/\?.*$/', '', $rel );
			$path = ABSPATH . $rel;
			return is_file( $path ) ? $path : '';
		}
		return '';
	}

	private function friendly_name( $url ) {
		$low = strtolower( $url );
		$map = array(
			'googletagmanager' => 'Google Tag Manager',
			'gtag/js'          => 'Google Tag Manager',
			'gtm.js'           => 'Google Tag Manager',
			'google-analytics' => 'Google Analytics',
			'analytics.js'     => 'Google Analytics',
			'connect.facebook' => 'Facebook Pixel',
			'fbevents'         => 'Facebook Pixel',
			'hotjar'           => 'Hotjar',
			'clarity.ms'       => 'Microsoft Clarity',
			'recaptcha'        => 'reCAPTCHA',
			'jquery-migrate'   => 'jQuery Migrate',
		);
		foreach ( $map as $needle => $label ) {
			if ( false !== strpos( $low, $needle ) ) {
				return $label;
			}
		}
		if ( false !== strpos( $low, '/jquery' ) ) {
			return 'jQuery';
		}

		$base = $this->short_name( $url );
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( $host && 0 !== strpos( $url, site_url() ) ) {
			$generic = array( '', 'js', 'index.js', 'main.js', 'script.js' );
			if ( in_array( strtolower( $base ), $generic, true ) ) {
				return $host;
			}
			return $host . '/' . $base;
		}
		return $base;
	}

	private function short_name( $url ) {
		$path = wp_parse_url( $url, PHP_URL_PATH );
		if ( ! $path ) {
			return $url;
		}
		return basename( $path );
	}
}

MBRPE_Performance_Doctor::instance();
