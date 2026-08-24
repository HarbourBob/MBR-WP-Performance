<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
/**
 * Used CSS Engine — Mode B.
 *
 * Mode A analyses one page and keeps the original stylesheets as a safety net,
 * so a selector it wrongly drops is corrected a moment later when the full
 * sheet arrives. Mode B genuinely removes the sheets, which means a wrong drop
 * is permanent for that template — so the analysis has to be right, not merely
 * close.
 *
 * The difference this class makes is the *union*. Mode B does not analyse a
 * single page; it analyses several URLs of the same template and keeps the sum
 * of what they used. A selector kept on any sampled page of a template is kept
 * for the whole template. That is what makes per-template output safe enough to
 * remove sheets over: the archive page's pagination rules survive even though
 * the sampled post did not paginate, because a different sampled URL did.
 *
 * Mechanically it subclasses the Mode A engine so both modes share exactly one
 * copy of the selector-matching, safelist and at-rule logic; the only override
 * is the keep/drop decision, which now consults (and feeds) the union.
 *
 * @package MBR_Performance
 */

use Sabberworm\CSS\RuleSet\DeclarationBlock;

class MBRPE_Used_CSS_Engine_B extends MBRPE_Used_CSS_Engine {

	/**
	 * Hard ceiling on union entries.
	 *
	 * A union is a set of selector strings, so on a page-builder site with a
	 * large theme it can legitimately run to tens of thousands. The cap exists
	 * only so a pathological stylesheet cannot grow the sidecar without bound;
	 * hitting it is reported rather than silently truncating the analysis.
	 */
	const UNION_LIMIT = 60000;

	/**
	 * Selectors known to be used somewhere in this template.
	 *
	 * Keys are selector strings, values are always true — a set, so lookups
	 * and merges are O(1) rather than a scan of a growing list.
	 *
	 * @var array<string,bool>
	 */
	protected $union = array();

	/**
	 * Whether the union hit UNION_LIMIT during this run.
	 *
	 * @var bool
	 */
	protected $union_full = false;

	/**
	 * Seed the engine with the selectors earlier samples of this template kept.
	 *
	 * @param array<string,bool> $union Selector set from the template sidecar.
	 */
	public function set_union( array $union ) {
		$this->union = $union;
	}

	/**
	 * The union after analysis: everything the previous samples kept, plus
	 * everything this page added. Written back to the sidecar by the caller.
	 *
	 * @return array<string,bool>
	 */
	public function get_union() {
		return $this->union;
	}

	/**
	 * @return bool Whether the union stopped growing because it hit the cap.
	 */
	public function union_full() {
		return $this->union_full;
	}

	/**
	 * @return int Number of selectors currently in the union.
	 */
	public function union_size() {
		return count( $this->union );
	}

	/**
	 * Decide which selectors of a block survive.
	 *
	 * Identical to Mode A except for the union. A selector is kept if either:
	 *
	 *   - a previously sampled URL of this template kept it (union hit), or
	 *   - it matches this page's DOM / safelist / structural rules, exactly as
	 *     Mode A would judge it.
	 *
	 * Every kept selector is then recorded, so the next sampled URL inherits
	 * this page's findings. The union is what turns "used on the page I looked
	 * at" into "used by this template".
	 *
	 * @param DeclarationBlock $block Block being pruned (rewritten in place).
	 * @param array            $stats Running statistics for the report.
	 * @return bool Whether the block survives at all.
	 */
	protected function prune_block( DeclarationBlock $block, array &$stats ) {
		// Custom properties cascade unpredictably — Mode A keeps these blocks
		// wholesale and so must Mode B. The decision is deterministic from the
		// block itself, so it needs no union entry to survive a rebuild.
		if ( $this->declares_custom_property( $block ) ) {
			$stats['rules_kept']++;
			return true;
		}

		$kept_selectors = array();
		$reason         = 'dropped';

		foreach ( $block->getSelectors() as $selector_obj ) {
			$selector = $selector_obj->getSelector();
			$key      = trim( $selector );

			if ( '' !== $key && isset( $this->union[ $key ] ) ) {
				// Another URL of this template used it. Keep without re-testing:
				// this page's DOM has no say over a sibling page's markup.
				$kept_selectors[] = $selector;
				if ( 'dropped' === $reason ) {
					$reason = 'union';
				}
				continue;
			}

			$result = $this->classify_selector( $selector );
			if ( $result['keep'] ) {
				$kept_selectors[] = $selector;
				$reason           = $result['reason'];
				$this->remember( $key );
			}
		}

		if ( empty( $kept_selectors ) ) {
			$stats['rules_dropped']++;
			if ( count( $stats['dropped_sample'] ) < 40 ) {
				$stats['dropped_sample'][] = $this->selectors_text( $block );
			}
			return false;
		}

		$block->setSelectors( implode( ', ', $kept_selectors ) );

		if ( 'matched' === $reason ) {
			$stats['rules_kept']++;
		} elseif ( 'union' === $reason ) {
			if ( ! isset( $stats['rules_union'] ) ) {
				$stats['rules_union'] = 0;
			}
			$stats['rules_union']++;
		} else {
			$stats['rules_safety']++;
			if ( count( $stats['safety_sample'] ) < 40 ) {
				$stats['safety_sample'][] = array(
					'sel' => implode( ', ', $kept_selectors ),
					'why' => $reason,
				);
			}
		}

		return true;
	}

	/**
	 * Add a selector to the union, respecting the cap.
	 *
	 * @param string $key Trimmed selector string.
	 */
	protected function remember( $key ) {
		if ( '' === $key || isset( $this->union[ $key ] ) ) {
			return;
		}
		if ( count( $this->union ) >= self::UNION_LIMIT ) {
			$this->union_full = true;
			return;
		}
		$this->union[ $key ] = true;
	}
}
