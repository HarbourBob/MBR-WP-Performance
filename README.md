# MBR Performance

Granular, transparent WordPress performance optimisation — full page caching, plus individual
control over core features, scripts, styles, fonts, images, preloading, the database, WooCommerce
and multisite.

Free, GPL-licensed, self-hosted. No telemetry, no CDN dependency for the plugin's own assets, and
no account to sign up for.

**Current version:** 2.1.5
**Requires:** WordPress 5.9+ · PHP 7.4+
**Tested to:** WordPress 7.0
**Licence:** GPLv2 or later

---

## What it does

Most performance plugins offer a single "optimise" button and hope for the best. MBR Performance
does the opposite: several dozen individual optimisations, each clearly labelled, each off by
default, grouped into tabs so you can enable exactly what your site needs and nothing it doesn't.

If something changes unexpectedly, you always know which switch caused it.

### Full page caching (new in 2.0.0)

The finished HTML of each page is written to disk and served to subsequent visitors instead of
being rebuilt. Three serving tiers, fastest first:

| Tier | What runs | Typical cost |
|---|---|---|
| Static (`.htaccess`) | Nothing — Apache or LiteSpeed reads the file off disk | Microseconds |
| Drop-in (`advanced-cache.php`) | PHP starts, WordPress does not | A few milliseconds |
| In-WordPress fallback | WordPress loads, theme and main query do not | Slower, but still a large win |

Each tier degrades to the next. No configuration produces an error page rather than a cache miss.

- **Targeted purging.** Editing a post clears that post, the front page, the blog index, every term
  across every taxonomy, the post type archive, the author archive, the date archives and the
  adjacent posts — plus the first twenty paginated pages of each. Not the whole cache.
- **Query-string normalisation.** Around twenty analytics and ad-click parameters (`utm_*`,
  `fbclid`, `gclid`, `mc_cid`, `msclkid`…) are stripped before lookup, so a campaign link hits the
  same entry as the clean URL. This is usually the difference between a 90% hit rate and a 20% one.
- **Pre-compressed gzip copies** written alongside each page, so the server never compresses
  identical bytes twice.
- **`X-MBR-Cache` debug headers** reporting `HIT`, `MISS` or `BYPASS` — and for a bypass,
  `X-MBR-Cache-Reason` naming the specific rule responsible.
- **Never caches** logged-in visitors, any page that emits a `Set-Cookie` header, password-protected
  posts, previews, search results, the WooCommerce cart/checkout/account pages, or anything marked
  with `DONOTCACHEPAGE`.
- **Refuses to run** alongside another page cache, naming the plugin responsible.

### Everything else

- **Core** — emojis, embeds, dashicons, heartbeat, revisions, REST hardening, HTML minification
- **JavaScript** — defer/async, footer moves, jQuery control, minify, combine, delayed execution,
  Script Modules and Interactivity API support (WP 6.5+)
- **CSS** — Used CSS Mode A (per page, originals deferred) and Mode B (per template, analysed
  sheets removed), async loading, minify, combine, paste-in Critical CSS
- **Fonts** — self-hosting with auto-download, subsetting, preloading, `font-display` strategies
- **Images** — WebP and AVIF conversion with bulk converter, automatic dimensions, lazy loading
- **Preloading** — DNS prefetch, preconnect, speculative loading, hover prefetch
- **Database** — revisions, transients, spam, orphaned metadata, table optimisation
- **Diagnostics** — Performance Doctor, self-hosted Real User Monitoring (LCP/CLS/INP), conflict
  detection, orphaned media, OPcache status and flushing (new in 2.1.0)

---

## Installation

Download the latest ZIP from [littlewebshack.com](https://littlewebshack.com/mbr-performance/),
then in WordPress: **Plugins → Add New → Upload Plugin**.

Or clone into `wp-content/plugins/`:

```bash
git clone https://github.com/HarbourBob/mbr-performance.git
```

Settings live under **MBR Performance** in the admin toolbar. A 45-page user guide (PDF) is bundled
in the ZIP.

### Enabling page caching

Tick **Enable Page Caching** on the Cache tab and save. That installs `advanced-cache.php` and adds
`WP_CACHE` to `wp-config.php` for you; both are removed again when you switch caching off or
deactivate the plugin.

`wp-config.php` is copied to `wp-config.php.mbrpe-backup` before it is first touched, and the edited
file is syntax-checked before being written — if it doesn't parse, the original is left alone. The
same applies to `.htaccess`.

If your host makes `wp-config.php` read-only, the Cache tab says so and shows the one line to add by
hand. Until then caching still works, just on the slower tier.

---

## Updates

Self-hosted. The plugin checks a manifest at
`raw.githubusercontent.com/HarbourBob/mbr-updates/main/mbr-performance.json` and downloads releases
from littlewebshack.com. Every release ZIP is SHA-256 checksummed in the manifest and verified
before installation.

---

## Development

```
mbr-performance.php              bootstrap, constants, update checker
includes/
  mbrpe-bootstrap.php            module loading, defaults, migrations, activation
  class-admin.php                settings screen, tab dispatch, sanitisation
  class-page-cache.php           cache engine — cacheability, buffering, storage
  class-page-cache-dropin.php    drop-in installer, WP_CACHE, compiled config
  class-page-cache-purge.php     purge events and related-URL resolution
  class-page-cache-rules.php     .htaccess rules and Nginx snippet
  class-opcache.php              OPcache status, flush and invalidation helpers
  dropins/advanced-cache.php     the drop-in template (self-contained)
  admin/tabs/                    one file per settings tab
```

### Conventions

- Singleton modules with `instance()`, instantiated from `MBRPE_Bootstrap::init_optimizations()`
- Settings stored in one option, `mbrpe_options[section][key]`
- Each section gets a tab file and a `sanitize_<section>_options()` method
- Version-keyed migrations in `maybe_upgrade()`, stamped once all have run
- Text domain `mbr-performance`, UK English in user-facing strings

### A note on the cache key

`mbrpe_cache_file_path()` in `dropins/advanced-cache.php` and
`MBRPE_Page_Cache::current_file_path()` are deliberate near-duplicates. They can't share code — the
drop-in runs before any autoloader, plugin API or options table exists.

Both carry a version marker (`MBRPE_CACHE_KEY_ALGO` / `MBRPE_Page_Cache::KEY_ALGO`). **Bump both
together whenever the key shape changes.** A mismatch produces a silent 100% miss rate rather than a
wrong page, which is safe but very easy not to notice. `tools/keytest.php` compares the two
implementations across a matrix of request shapes and exits non-zero on any disagreement.

---

## Contributing

Issues and pull requests welcome. For anything touching the cache engine, please run
`php tools/keytest.php` and include the result.

## Licence

GPLv2 or later. See [LICENSE](LICENSE).

Built by [Robert Palmer](https://littlewebshack.com/about/) — Little Web Shack.
