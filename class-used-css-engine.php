<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
/**
 * Used CSS Engine (prototype).
 *
 * Given a page's rendered HTML and its stylesheets, works out which CSS rules
 * are actually used by the delivered DOM and produces (a) a slimmed "used CSS"
 * string and (b) a report of what was kept, dropped, or kept-for-safety.
 *
 * This is a static analyser: it sees the DOM as delivered by the server, not
 * after JavaScript runs. The "dropped" list is therefore the thing to review —
 * anything your interactions add at runtime will show up there.
 *
 * Members are `protected` rather than `private` so Mode B
 * (MBRPE_Used_CSS_Engine_B) can subclass this and layer a cross-page selector
 * union on top of the same matching logic. Mode A's own behaviour is unchanged
 * by that visibility — nothing outside the class hierarchy can reach them.
 *
 * @package MBR_Used_CSS_Lab
 */

// Vendored CSS parser + selector engine are autoloaded separately.

use Sabberworm\CSS\Parser as CssParser;
use Sabberworm\CSS\Settings as CssSettings;
use Sabberworm\CSS\CSSList\Document as CssDocument;
use Sabberworm\CSS\CSSList\AtRuleBlockList;
use Sabberworm\CSS\CSSList\KeyFrame;
use Sabberworm\CSS\RuleSet\DeclarationBlock;
use Sabberworm\CSS\RuleSet\AtRuleSet;
use Sabberworm\CSS\Property\Import;
use Sabberworm\CSS\Property\Charset;
use Symfony\Component\CssSelector\CssSelectorConverter;

class MBRPE_Used_CSS_Engine {

	/**
	 * Pseudo-classes that represent interaction or form state. We can't resolve
	 * these against a static DOM, so we strip them to test the base element.
	 *
	 * @var string[]
	 */
	protected $state_pseudo_classes = array(
		'hover', 'focus', 'focus-within', 'focus-visible', 'active', 'visited',
		'target', 'target-within', 'checked', 'disabled', 'enabled', 'required',
		'optional', 'valid', 'invalid', 'in-range', 'out-of-range',
		'placeholder-shown', 'read-only', 'read-write', 'default',
		'indeterminate', 'autofill', 'link', 'any-link', 'user-invalid',
		'user-valid', 'current', 'past', 'future', 'playing', 'paused',
		'modal', 'popover-open', 'fullscreen',
	);

	/**
	 * Default safelist of class tokens / prefixes that are commonly toggled by
	 * JavaScript and so should be kept even when absent from the static DOM.
	 * Prefixes end with '*'.
	 *
	 * @var string[]
	 */
	protected $safelist = array(
		'is-*', 'has-*', 'js-*', 'active', 'open', 'opened', 'show', 'shown',
		'hide', 'hidden', 'in', 'out', 'fade', 'fade-in', 'fade-out', 'collapse',
		'collapsing', 'collapsed', 'expanded', 'selected', 'current', 'toggled',
		'menu-open', 'modal-open', 'nav-open', 'sticky', 'stuck', 'fixed',
		'loading', 'loaded', 'error', 'success', 'disabled', 'visible',
		'slick-*', 'swiper-*', 'owl-*', 'lg-*', 'mfp-*', 'admin-bar',
	);

	/**
	 * @var CssSelectorConverter
	 */
	protected $converter;

	/**
	 * @var \DOMXPath
	 */
	protected $xpath;

	/**
	 * Cache of selector => bool DOM match results.
	 *
	 * @var array<string,bool>
	 */
	protected $match_cache = array();

	/**
	 * Add extra safelist entries (e.g. from a user-supplied list).
	 *
	 * @param string[] $entries
	 */
	public function add_safelist( array $entries ) {
		foreach ( $entries as $entry ) {
			$entry = trim( $entry );
			if ( '' !== $entry ) {
				$this->safelist[] = $entry;
			}
		}
	}

	/**
	 * Load a page's HTML into a DOM and prepare the selector matcher.
	 *
	 * @param string $html
	 */
	public function load_html( $html ) {
		$this->converter = new CssSelectorConverter( true );

		$dom = new \DOMDocument();
		$previous = libxml_use_internal_errors( true );
		// Force UTF-8 handling and parse tolerantly.
		$dom->loadHTML( '<?xml encoding="UTF-8">' . $html, LIBXML_NOWARNING | LIBXML_NOERROR );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		$this->xpath       = new \DOMXPath( $dom );
		$this->match_cache = array();
	}

	/**
	 * Analyse one stylesheet against the loaded DOM.
	 *
	 * @param string $css    Raw stylesheet text.
	 * @param string $source Label for the report (e.g. the file URL).
	 * @return array { used_css:string, stats:array }
	 */
	public function analyse( $css, $source ) {
		$settings = CssSettings::create()->withMultibyteSupport( false );
		$document = ( new CssParser( $css, $settings ) )->parse();

		$stats = array(
			'source'         => $source,
			'bytes_original' => strlen( $css ),
			'rules_total'    => 0,
			'rules_kept'     => 0,
			'rules_safety'   => 0,
			'rules_dropped'  => 0,
			'dropped_sample' => array(),
			'safety_sample'  => array(),
		);

		$used_names = array(
			'animations' => array(),
			'fonts'      => array(),
		);

		// Pass 1: prune declaration blocks (incl. nested in @media/@supports),
		// collecting referenced @keyframes and @font-face names as we go.
		$this->process_list( $document, $stats, $used_names );

		// Pass 2: drop @keyframes / @font-face that nothing kept references.
		$this->filter_at_resources( $document, $used_names );

		$used_css = $document->render( \Sabberworm\CSS\OutputFormat::createCompact() );

		$stats['bytes_used']    = strlen( $used_css );
		$stats['bytes_saved']   = max( 0, $stats['bytes_original'] - $stats['bytes_used'] );
		$stats['percent_saved'] = $stats['bytes_original'] > 0
			? round( ( $stats['bytes_saved'] / $stats['bytes_original'] ) * 100, 1 )
			: 0.0;

		return array(
			'used_css' => $used_css,
			'stats'    => $stats,
		);
	}

	/**
	 * Recursively process a CSS list: prune declaration blocks, recurse into
	 * @media/@supports, and remove anything that ends up empty.
	 *
	 * @param \Sabberworm\CSS\CSSList\CSSList $list
	 * @param array                           $stats
	 * @param array                           $used_names
	 */
	protected function process_list( $list, array &$stats, array &$used_names ) {
		$kept = array();

		foreach ( $list->getContents() as $item ) {

			if ( $item instanceof DeclarationBlock ) {
				$stats['rules_total']++;
				$verdict = $this->prune_block( $item, $stats );
				if ( $verdict ) {
					$this->collect_references( $item, $used_names );
					$kept[] = $item;
				}
				continue;
			}

			if ( $item instanceof AtRuleBlockList ) {
				// @media / @supports — recurse; keep wrapper only if non-empty.
				$this->process_list( $item, $stats, $used_names );
				if ( count( $item->getContents() ) > 0 ) {
					$kept[] = $item;
				}
				continue;
			}

			// @charset is invalid inside an inline <style>; @import must precede
			// all other rules and would be invalid once concatenated mid-document.
			// Drop both — any imported CSS still arrives via the deferred full sheet.
			if ( $item instanceof Import || $item instanceof Charset ) {
				continue;
			}

			// @keyframes, @font-face, @namespace, @page:
			// keep for now; pass 2 filters keyframes/font-face by reference.
			$kept[] = $item;
		}

		$list->setContents( $kept );
	}

	/**
	 * Decide which selectors of a declaration block survive, rewrite the block
	 * to just those, and report. Returns false if the whole block is dropped.
	 *
	 * @param DeclarationBlock $block
	 * @param array            $stats
	 * @return bool Whether the block is kept.
	 */
	protected function prune_block( DeclarationBlock $block, array &$stats ) {
		// A rule that declares custom properties cascades unpredictably — keep.
		if ( $this->declares_custom_property( $block ) ) {
			$stats['rules_kept']++;
			return true;
		}

		$kept_selectors = array();
		$reason         = 'dropped';

		foreach ( $block->getSelectors() as $selector_obj ) {
			$selector = $selector_obj->getSelector();
			$result   = $this->classify_selector( $selector );
			if ( $result['keep'] ) {
				$kept_selectors[] = $selector;
				$reason           = $result['reason'];
			}
		}

		if ( empty( $kept_selectors ) ) {
			$stats['rules_dropped']++;
			if ( count( $stats['dropped_sample'] ) < 40 ) {
				$stats['dropped_sample'][] = $this->selectors_text( $block );
			}
			return false;
		}

		// Rewrite to the surviving selectors only (better size estimate).
		$block->setSelectors( implode( ', ', $kept_selectors ) );

		if ( 'matched' === $reason ) {
			$stats['rules_kept']++;
		} else {
			$stats['rules_safety']++;
			if ( count( $stats['safety_sample'] ) < 40 ) {
				$stats['safety_sample'][] = array( 'sel' => implode( ', ', $kept_selectors ), 'why' => $reason );
			}
		}
		return true;
	}

	/**
	 * Classify a single selector: keep (matched / safety reason) or drop.
	 *
	 * @param string $selector
	 * @return array { keep:bool, reason:string }
	 */
	protected function classify_selector( $selector ) {
		$selector = trim( $selector );
		if ( '' === $selector ) {
			return array( 'keep' => true, 'reason' => 'empty' );
		}

		// :root and html — variable/cascade roots.
		if ( preg_match( '/(^|\s|,)(:root|html)\b/i', $selector ) ) {
			return array( 'keep' => true, 'reason' => 'root' );
		}

		// Safelist (JS-toggled classes etc.).
		if ( $this->matches_safelist( $selector ) ) {
			return array( 'keep' => true, 'reason' => 'safelist' );
		}

		// aria-*/data-* attribute selectors are frequently toggled by JS.
		if ( preg_match( '/\[\s*(aria-|data-)[a-z0-9_-]+/i', $selector ) ) {
			return array( 'keep' => true, 'reason' => 'attr-state' );
		}

		// Strip pseudo-elements and interaction-state pseudo-classes to get a
		// base selector that describes the element the rule targets.
		$base = $this->base_selector( $selector );
		if ( '' === $base ) {
			// Was purely a pseudo (e.g. "::selection") — can't localise; keep.
			return array( 'keep' => true, 'reason' => 'pseudo' );
		}

		if ( $this->dom_matches( $base ) ) {
			return array( 'keep' => true, 'reason' => 'matched' );
		}

		return array( 'keep' => false, 'reason' => 'dropped' );
	}

	/**
	 * Reduce a selector to a structural base by removing pseudo-elements and
	 * interaction-state pseudo-classes (leaving structural pseudos like
	 * :nth-child / :not for the matcher to handle).
	 *
	 * @param string $selector
	 * @return string
	 */
	protected function base_selector( $selector ) {
		// Remove pseudo-elements: ::foo and legacy :before/:after/:first-line/:first-letter.
		$selector = preg_replace( '/::[a-z-]+(\([^)]*\))?/i', '', $selector );
		$selector = preg_replace( '/:(before|after|first-line|first-letter|selection|placeholder|marker|backdrop)\b(\([^)]*\))?/i', '', $selector );

		// Remove interaction-state pseudo-classes.
		$states = implode( '|', array_map( 'preg_quote', $this->state_pseudo_classes ) );
		$selector = preg_replace( '/:(' . $states . ')\b(\([^)]*\))?/i', '', $selector );

		return trim( $selector );
	}

	/**
	 * Does the base selector match any node in the DOM?
	 *
	 * @param string $base
	 * @return bool
	 */
	protected function dom_matches( $base ) {
		if ( isset( $this->match_cache[ $base ] ) ) {
			return $this->match_cache[ $base ];
		}

		$matched = true; // Default to keep if we can't evaluate.
		try {
			$xpath_expr = $this->converter->toXPath( $base );
			$nodes      = $this->xpath->query( $xpath_expr );
			$matched    = ( false !== $nodes && $nodes->length > 0 );
		} catch ( \Throwable $e ) {
			// Selector Symfony can't translate (e.g. :has, :is on 5.4) — keep.
			$matched = true;
		}

		$this->match_cache[ $base ] = $matched;
		return $matched;
	}

	/**
	 * Whether any class token in the selector is on the safelist.
	 *
	 * @param string $selector
	 * @return bool
	 */
	protected function matches_safelist( $selector ) {
		if ( ! preg_match_all( '/\.([A-Za-z0-9_-]+)/', $selector, $m ) ) {
			return false;
		}
		foreach ( $m[1] as $class ) {
			foreach ( $this->safelist as $entry ) {
				if ( '*' === substr( $entry, -1 ) ) {
					$prefix = substr( $entry, 0, -1 );
					if ( '' !== $prefix && 0 === strpos( $class, $prefix ) ) {
						return true;
					}
				} elseif ( $class === $entry ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Does a declaration block declare any custom property (--x)?
	 *
	 * @param DeclarationBlock $block
	 * @return bool
	 */
	protected function declares_custom_property( DeclarationBlock $block ) {
		foreach ( $block->getRules() as $rule ) {
			if ( 0 === strpos( $rule->getRule(), '--' ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Collect animation-name and font-family references from a kept block, so
	 * we know which @keyframes / @font-face to retain.
	 *
	 * @param DeclarationBlock $block
	 * @param array            $used_names
	 */
	protected function collect_references( DeclarationBlock $block, array &$used_names ) {
		foreach ( $block->getRules() as $rule ) {
			$prop  = strtolower( $rule->getRule() );
			$value = $this->rule_value_text( $rule );

			if ( 'animation' === $prop || 'animation-name' === $prop ) {
				foreach ( preg_split( '/[\s,]+/', $value ) as $token ) {
					$token = trim( $token, " \t\"'" );
					if ( '' !== $token && ! is_numeric( $token ) ) {
						$used_names['animations'][ $token ] = true;
					}
				}
			}

			if ( 'font-family' === $prop || 'font' === $prop ) {
				foreach ( explode( ',', $value ) as $token ) {
					$token = trim( $token, " \t\"'" );
					if ( '' !== $token ) {
						$used_names['fonts'][ strtolower( $token ) ] = true;
					}
				}
			}
		}
	}

	/**
	 * Remove @keyframes / @font-face blocks that no kept rule references.
	 *
	 * @param \Sabberworm\CSS\CSSList\CSSList $list
	 * @param array                           $used_names
	 */
	protected function filter_at_resources( $list, array $used_names ) {
		$kept = array();
		foreach ( $list->getContents() as $item ) {

			if ( $item instanceof KeyFrame ) {
				$name = trim( (string) $item->getAnimationName(), " \t\"'" );
				if ( isset( $used_names['animations'][ $name ] ) ) {
					$kept[] = $item;
				}
				continue;
			}

			if ( $item instanceof AtRuleSet && 'font-face' === strtolower( $item->atRuleName() ) ) {
				$family = '';
				foreach ( $item->getRules( 'font-family' ) as $rule ) {
					$family = strtolower( trim( $this->rule_value_text( $rule ), " \t\"'" ) );
				}
				if ( '' !== $family && isset( $used_names['fonts'][ $family ] ) ) {
					$kept[] = $item;
				}
				continue;
			}

			if ( $item instanceof AtRuleBlockList ) {
				$this->filter_at_resources( $item, $used_names );
				if ( count( $item->getContents() ) > 0 ) {
					$kept[] = $item;
				}
				continue;
			}

			$kept[] = $item;
		}
		$list->setContents( $kept );
	}

	/**
	 * Flatten a rule's value to a string for keyword scanning.
	 *
	 * @param \Sabberworm\CSS\Rule\Rule $rule
	 * @return string
	 */
	protected function rule_value_text( $rule ) {
		$value = $rule->getValue();
		if ( is_object( $value ) && method_exists( $value, 'render' ) ) {
			return $value->render( \Sabberworm\CSS\OutputFormat::create() );
		}
		return (string) $value;
	}

	/**
	 * Human-readable selector list for a block.
	 *
	 * @param DeclarationBlock $block
	 * @return string
	 */
	protected function selectors_text( DeclarationBlock $block ) {
		$parts = array();
		foreach ( $block->getSelectors() as $sel ) {
			$parts[] = $sel->getSelector();
		}
		return implode( ', ', $parts );
	}
}
