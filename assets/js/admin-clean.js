/**
 * MBR Performance Admin JavaScript - Clean Rebuild
 */

(function($) {
    'use strict';

    const MBRPE_Admin = {

        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind all events
         */
        bindEvents: function() {
            var self = this;

            // Reset to defaults
            $('.mbr-performance-reset').on('click', function(e) { self.resetSettings.call(this, e); });
            
            // Database operations
            $('#clean-revisions').on('click', function(e) { self.cleanRevisions.call(this, e); });
            $('#scan-post-meta').on('click', function(e) { self.scanPostMeta.call(this, e); });
            $('#delete-post-meta').on('click', function(e) { self.deletePostMeta.call(this, e); });
            $('#scan-comment-meta').on('click', function(e) { self.scanCommentMeta.call(this, e); });
            $('#delete-comment-meta').on('click', function(e) { self.deleteCommentMeta.call(this, e); });
            $('#scan-relationships').on('click', function(e) { self.scanRelationships.call(this, e); });
            $('#delete-relationships').on('click', function(e) { self.deleteRelationships.call(this, e); });
            $('#scan-term-meta').on('click', function(e) { self.scanTermMeta.call(this, e); });
            $('#delete-term-meta').on('click', function(e) { self.deleteTermMeta.call(this, e); });
            $('#get-transient-stats').on('click', function(e) { self.getTransientStats.call(this, e); });
            $('#delete-expired-transients').on('click', function(e) { self.deleteExpiredTransients.call(this, e); });
            $('#delete-all-transients').on('click', function(e) { self.deleteAllTransients.call(this, e); });
            $('#optimize-tables').on('click', function(e) { self.optimizeTables.call(this, e); });
            $('#convert-innodb').on('click', function(e) { self.convertToInnoDB.call(this, e); });
            $('#repair-tables').on('click', function(e) { self.repairTables.call(this, e); });
            $('#get-db-info').on('click', function(e) { self.getDatabaseInfo.call(this, e); });
            
            // CSS operations
            $('#scan-css').on('click', function(e) { self.scanCSS.call(this, e); });
            $('#clear-scan-data').on('click', function(e) { self.clearScanData.call(this, e); });

            // Combine cache operations (CSS + JS tabs)
            $('#mbr-clear-combine-css, #mbr-clear-combine-js').on('click', function(e) { self.clearCombineCache.call(this, e); });

            // Used CSS (Mode A) cache
            $('#mbr-clear-used-css').on('click', function(e) { self.clearUsedCss.call(this, e); });

            // Performance Doctor (delegated so it binds regardless of timing)
            $(document).on('click', '#mbr-run-doctor', function(e) { self.runDoctor.call(this, e); });
            $(document).on('click', '#mbr-run-doctor-site', function(e) { self.runDoctorSite.call(this, e); });
            $(document).on('click', '#mbr-doctor-report', function(e) { self.openDoctorReport.call(this, e); });
            $(document).on('click', '#mbr-doctor-nudge-dismiss', function(e) { self.dismissDoctorNudge.call(this, e); });

            // Persist dismissal of the caching-plugin conflict notice (per user,
            // until the overlap changes). WordPress core hides it client-side;
            // we record the dismissal so it doesn't return on reload. sendBeacon
            // is used so the request still completes even if the user clicks a
            // tab immediately after dismissing (a normal XHR would be cancelled
            // by the navigation, and the dismissal would never save).
            $(document).on('click', '.mbr-conflict-notice .notice-dismiss', function() {
                var hash = $(this).closest('.mbr-conflict-notice').data('mbr-conflict-hash');
                if (!hash) { return; }

                var sent = false;
                if (window.navigator && typeof navigator.sendBeacon === 'function') {
                    try {
                        var fd = new FormData();
                        fd.append('action', 'mbrpe_dismiss_conflict');
                        fd.append('nonce', mbrpeData.nonce);
                        fd.append('hash', hash);
                        sent = navigator.sendBeacon(mbrpeData.ajaxUrl, fd);
                    } catch (err) {
                        sent = false;
                    }
                }

                if (!sent) {
                    $.post(mbrpeData.ajaxUrl, {
                        action: 'mbrpe_dismiss_conflict',
                        nonce: mbrpeData.nonce,
                        hash: hash
                    });
                }
            });
            
            // Font operations
            $('#download-manual-fonts').on('click', function(e) { self.downloadManualFonts.call(this, e); });
            $('#clear-font-cache').on('click', function(e) { self.clearFontCache.call(this, e); });
            
            // WebP Converter operations
            $('#mbr-webp-start-conversion').on('click', function(e) { self.webpStartConversion.call(this, e); });
            $('#mbr-webp-clear-history').on('click', function(e) { self.webpClearHistory.call(this, e); });
            $('#mbr-webp-apply-bulk').on('click', function(e) { self.webpApplyBulk.call(this, e); });
            $('#mbr-webp-revert-all').on('click', function(e) { self.webpRevertAll.call(this, e); });
            $('#mbr-avif-start-conversion').on('click', function(e) { self.avifStartConversion.call(this, e); });
            $('#mbr-avif-clear-history').on('click', function(e) { self.avifClearHistory.call(this, e); });
            $('#mbr-avif-revert-all').on('click', function(e) { self.avifRevertAll.call(this, e); });
            $('#mbr-webp-select-all').on('change', function() {
                $('.mbr-webp-item-checkbox').prop('checked', $(this).prop('checked'));
            });

            // Image Dimensions bulk resize
            $('#mbr-imgdim-scan').on('click', function(e) { self.imgDimScan.call(this, e); });
            $('#mbr-imgdim-start').on('click', function(e) { self.imgDimStart.call(this, e); });

            // WooCommerce operations
            $('#wc-clear-expired-sessions').on('click', function(e) { self.wcClearSessions.call(this, e); });
            $('#wc-clear-transients').on('click', function(e) { self.wcClearTransients.call(this, e); });
            $('#wc-run-action-scheduler-cleanup').on('click', function(e) { self.wcCleanupActionScheduler.call(this, e); });
        },

        /**
         * Show loading spinner
         */
        showLoading: function($button) {
            $button.prop('disabled', true);
            $button.after('<span class="mbr-performance-loading"></span>');
        },

        /**
         * Hide loading spinner
         */
        hideLoading: function($button) {
            $button.prop('disabled', false);
            $button.next('.mbr-performance-loading').remove();
        },

        /**
         * Show message
         */
        showMessage: function($container, message, type) {
            type = type || 'success';
            var cssClass = type === 'success' ? 'notice-success' : 'notice-error';
            $container.html('<div class="notice ' + cssClass + ' inline"><p>' + message + '</p></div>');
        },

        /**
         * Performance Doctor — run analysis
         */
        runDoctor: function(e) {
            e.preventDefault();
            var self = MBRPE_Admin;
            var $btn = $('#mbr-run-doctor');
            var $out = $('#mbr-doctor-results');
            var url = $('#mbr-doctor-url').val() || '';

            $btn.prop('disabled', true).text(mbrpeData.i18n && mbrpeData.i18n.analysing ? mbrpeData.i18n.analysing : 'Analysing…');
            $out.prop('hidden', false).html('<p class="description">' + (mbrpeData.i18n && mbrpeData.i18n.analysing ? mbrpeData.i18n.analysing : 'Analysing…') + '</p>');

            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_run_doctor',
                nonce: mbrpeData.nonce,
                url: url
            }).done(function(response) {
                if (response && response.success && response.data) {
                    self.renderDoctorResults($out, response.data);
                } else {
                    var msg = (response && response.data && response.data.message) ? response.data.message : 'Analysis failed.';
                    $out.html('<div class="notice notice-error inline"><p></p></div>');
                    $out.find('p').text(msg);
                }
            }).fail(function() {
                $out.html('<div class="notice notice-error inline"><p>Request failed. Please try again.</p></div>');
            }).always(function() {
                $btn.prop('disabled', false).text(mbrpeData.i18n && mbrpeData.i18n.runAnalysis ? mbrpeData.i18n.runAnalysis : 'Run analysis');
            });
        },

        /**
         * Performance Doctor — build a single recommendation card.
         * coverage (optional) is shown for site-level recs, e.g. "Site-wide (4/4)".
         */
        buildDoctorRec: function(rec, coverage) {
            var tierLabels = { high: 'High impact', medium: 'Worth doing', low: 'Minor', info: 'Note' };
            var $card = $('<div class="mbr-performance-card mbr-doctor-rec mbr-doctor-tier-' + rec.tier + '"/>');
            var $head = $('<div class="mbr-doctor-rec-head"/>');
            $('<span class="mbr-doctor-badge mbr-doctor-badge-' + rec.tier + '"/>')
                .text(tierLabels[rec.tier] || rec.tier).appendTo($head);
            $('<strong/>').text(rec.title).appendTo($head);
            if (coverage) {
                $('<span class="mbr-doctor-coverage mbr-doctor-coverage-' + (rec.scope === 'site-wide' ? 'all' : 'some') + '"/>')
                    .text(coverage).appendTo($head);
            }
            $head.appendTo($card);

            $('<p/>').text(rec.detail).appendTo($card);
            if (rec.labels && rec.labels.length) {
                $('<p class="mbr-doctor-on"/>').text('On: ' + rec.labels.join(', ')).appendTo($card);
            }
            if (rec.warning) {
                $('<p class="mbr-doctor-warn"/>').text('⚠ ' + rec.warning).appendTo($card);
            }
            if (rec.tab) {
                var tabNames = {
                    'rum': 'RUM', 'css': 'CSS', 'javascript': 'JavaScript',
                    'webp': 'WebP / AVIF', 'lazy-loading': 'Lazy Loading',
                    'preloading': 'Preloading', 'fonts': 'Fonts',
                    'database': 'Database', 'core': 'Core Features'
                };
                $('<a class="button button-secondary"/>')
                    .attr('href', '?page=mbr-performance&tab=' + encodeURIComponent(rec.tab))
                    .text('Open ' + (tabNames[rec.tab] || rec.tab) + ' settings')
                    .appendTo($card);
            }
            return $card;
        },

        /**
         * Performance Doctor — render structured results
         */
        renderDoctorResults: function($out, data) {
            var self = MBRPE_Admin;
            var $wrap = $('<div/>');

            // Verdict.
            var $verdict = $('<div class="mbr-performance-card mbr-doctor-verdict"/>');
            $('<h3/>').text(data.summary.verdict).appendTo($verdict);
            var s = data.summary;
            $('<p class="description"/>').text(
                'Render-blocking CSS: ' + s.css_count + ' file(s), ' + s.css_bytes_human +
                '  •  Render-blocking JS: ' + s.js_count + ' file(s), ' + s.js_bytes_human
            ).appendTo($verdict);
            if (s.images && s.images.total > 0) {
                $('<p class="description"/>').text(
                    'Images: ' + s.images.total + ' total  •  ' +
                    s.images.missing_dimensions + ' missing size  •  ' +
                    s.images.legacy_format + ' JPEG/PNG  •  ' +
                    s.images.not_lazy + ' not lazy-loaded'
                ).appendTo($verdict);
            }
            $verdict.appendTo($wrap);

            // Recommendations.
            (data.recommendations || []).forEach(function(rec) {
                self.buildDoctorRec(rec).appendTo($wrap);
            });

            $out.prop('hidden', false).empty().append($wrap);
        },

        /**
         * Performance Doctor — multi-template (site) scan
         */
        runDoctorSite: function(e) {
            e.preventDefault();
            var self = MBRPE_Admin;
            var $btn = $('#mbr-run-doctor-site');
            var $out = $('#mbr-doctor-results');

            $btn.prop('disabled', true).text('Scanning templates…');
            $out.prop('hidden', false).html('<p class="description">Scanning your key templates — this fetches several pages, give it a moment…</p>');

            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_run_doctor_site',
                nonce: mbrpeData.nonce
            }).done(function(response) {
                if (response && response.success && response.data) {
                    self.renderDoctorSiteResults($out, response.data);
                    self.dismissDoctorNudge(); // They found the Doctor — stop nudging.
                } else {
                    var msg = (response && response.data && response.data.message) ? response.data.message : 'Scan failed.';
                    $out.html('<div class="notice notice-error inline"><p></p></div>');
                    $out.find('p').text(msg);
                }
            }).fail(function() {
                $out.html('<div class="notice notice-error inline"><p>Request failed. Please try again.</p></div>');
            }).always(function() {
                $btn.prop('disabled', false).text('Scan key templates');
            });
        },

        /**
         * Performance Doctor — render aggregated site results
         */
        renderDoctorSiteResults: function($out, data) {
            var self = MBRPE_Admin;
            self._lastSiteScan = data;
            var $wrap = $('<div/>');
            var site = data.site || {};

            // Site verdict + report action.
            var $verdict = $('<div class="mbr-performance-card mbr-doctor-verdict"/>');
            $('<h3/>').text(site.verdict || 'Site analysis').appendTo($verdict);
            $('<p class="description"/>').text((site.templates_ok || 0) + ' template(s) analysed.').appendTo($verdict);
            $('<button type="button" class="button button-primary" id="mbr-doctor-report"/>')
                .text('Download report (PDF)').appendTo($verdict);
            $verdict.appendTo($wrap);

            // Site-wide recommendations (de-duplicated, with coverage).
            // Info-tier entries are contextual notes — chiefly real-user field
            // data — and are not "recommendations", so they must not suppress
            // the all-clear card, nor be suppressed by it.
            var recs = site.recommendations || [];
            var actionable = recs.filter(function(r) { return r.tier !== 'info'; });
            if (!actionable.length) {
                $('<div class="mbr-performance-card"/>')
                    .append($('<p/>').text('No actionable recommendations — the templates sampled are already in good shape.'))
                    .appendTo($wrap);
            }
            if (recs.length) {
                $('<h3 class="mbr-doctor-section"/>')
                    .text(actionable.length ? 'Recommended across your site' : 'Real-user data')
                    .appendTo($wrap);
                recs.forEach(function(rec) {
                    var coverage = (rec.scope === 'site-wide')
                        ? ('Site-wide (' + rec.coverage + '/' + rec.total + ')')
                        : (rec.coverage + ' of ' + rec.total + ' templates');
                    self.buildDoctorRec(rec, coverage).appendTo($wrap);
                });
            }

            // Per-template breakdown.
            $('<h3 class="mbr-doctor-section"/>').text('By template').appendTo($wrap);
            (data.templates || []).forEach(function(t) {
                var $row = $('<div class="mbr-performance-card mbr-doctor-template"/>');
                var $h = $('<div class="mbr-doctor-template-head"/>');
                $('<strong/>').text(t.label).appendTo($h);
                $('<a class="mbr-doctor-template-url" target="_blank" rel="noopener"/>')
                    .attr('href', t.url).text(t.url).appendTo($h);
                $h.appendTo($row);
                if (!t.ok) {
                    $('<p class="mbr-doctor-warn"/>').text(t.message || 'Could not analyse this template.').appendTo($row);
                } else {
                    $('<p class="description"/>').text(t.summary.verdict).appendTo($row);
                    var titles = (t.recommendations || [])
                        .filter(function(r) { return r.tier !== 'info'; })
                        .map(function(r) { return r.title; });
                    $('<p/>').text(titles.length ? ('Suggests: ' + titles.join(', ')) : 'Nothing to flag here.').appendTo($row);
                }
                $row.appendTo($wrap);
            });

            $out.prop('hidden', false).empty().append($wrap);
        },

        /**
         * Performance Doctor — open a branded, print-ready report in a new
         * window. The user prints or "Saves as PDF" from there. No server-side
         * PDF engine, so it works on any host and adds no weight.
         */
        openDoctorReport: function(e) {
            e.preventDefault();
            var self = MBRPE_Admin;
            var data = self._lastSiteScan;
            if (!data) { return; }
            var ctx = (mbrpeData && mbrpeData.report) ? mbrpeData.report : { siteName: '', siteUrl: '', version: '' };
            var win = window.open('', '_blank');
            if (!win) {
                alert('Please allow pop-ups for this page to open the report.');
                return;
            }
            win.document.open();
            win.document.write(self.buildReportHTML(data, ctx));
            win.document.close();
        },

        /**
         * Escape text for safe insertion into the report markup.
         */
        escReport: function(s) {
            return String(s == null ? '' : s).replace(/[&<>"]/g, function(c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
            });
        },

        buildReportHTML: function(data, ctx) {
            var esc = MBRPE_Admin.escReport;
            var site = data.site || {};
            var tierLabels = { high: 'High impact', medium: 'Worth doing', low: 'Minor', info: 'Note' };
            var now = new Date();
            var dateStr = now.toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });

            // Site-wide recommendation rows.
            var rows = '';
            (site.recommendations || []).forEach(function(rec) {
                var scope = (rec.scope === 'site-wide')
                    ? ('Site-wide (' + rec.coverage + '/' + rec.total + ')')
                    : (rec.coverage + ' of ' + rec.total + ' templates');
                rows += '<tr class="tier-' + esc(rec.tier) + '">' +
                    '<td class="pri"><span class="badge badge-' + esc(rec.tier) + '">' + esc(tierLabels[rec.tier] || rec.tier) + '</span></td>' +
                    '<td><div class="rec-title">' + esc(rec.title) + '</div><div class="rec-detail">' + esc(rec.detail) + '</div>' +
                    (rec.warning ? '<div class="rec-warn">⚠ ' + esc(rec.warning) + '</div>' : '') + '</td>' +
                    '<td class="scope">' + esc(scope) + '</td>' +
                    '<td class="setting">' + (rec.tab ? esc(rec.tab) : '—') + '</td></tr>';
            });
            var recTable = rows
                ? '<table class="recs"><thead><tr><th>Priority</th><th>Recommendation</th><th>Scope</th><th>Setting</th></tr></thead><tbody>' + rows + '</tbody></table>'
                : '<p class="clean">No actionable recommendations — the templates sampled are already in good shape.</p>';

            // Per-template breakdown.
            var tpls = '';
            (data.templates || []).forEach(function(t) {
                var body;
                if (!t.ok) {
                    body = '<p class="tpl-warn">' + esc(t.message || 'Could not analyse this template.') + '</p>';
                } else {
                    var titles = (t.recommendations || []).filter(function(r) { return r.tier !== 'info'; })
                        .map(function(r) { return r.title; });
                    body = '<p class="tpl-verdict">' + esc(t.summary.verdict) + '</p>' +
                        '<p class="tpl-sugg">' + (titles.length ? 'Suggests: ' + esc(titles.join(', ')) : 'Nothing to flag here.') + '</p>';
                }
                tpls += '<div class="tpl"><div class="tpl-name">' + esc(t.label) + '</div>' +
                    '<div class="tpl-url">' + esc(t.url) + '</div>' + body + '</div>';
            });

            var css = '@page{size:A4;margin:16mm}' +
                '*{box-sizing:border-box}' +
                'body{margin:0;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;color:#1e293b;line-height:1.5;font-size:12px}' +
                // On screen the report is previewed in a browser window, where
                // @page margins do not apply — without a sheet it stretches to
                // the full window width. Mirror an A4 page instead: grey
                // backdrop, centred white sheet, page-width padding.
                '@media screen{body{background:#eef2f7;padding:24px 16px}' +
                '.sheet{width:210mm;max-width:100%;margin:0 auto;background:#fff;padding:16mm;' +
                'box-shadow:0 1px 3px rgba(15,23,42,.1),0 8px 24px rgba(15,23,42,.08);border-radius:2px}' +
                '.toolbar{width:210mm;max-width:100%;margin:0 auto 16px}}' +
                // In print the sheet must not double up on the @page margin.
                '@media print{body{background:#fff;padding:0}' +
                '.sheet{width:auto;max-width:none;margin:0;padding:0;box-shadow:none;border-radius:0}}' +
                '.head{display:flex;justify-content:space-between;align-items:flex-end;border-bottom:3px solid #2563eb;padding-bottom:12px;margin-bottom:18px}' +
                '.brand{font-size:22px;font-weight:800;letter-spacing:-.3px;color:#0f172a}' +
                '.brand span{color:#2563eb}' +
                '.kicker{font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color:#64748b;margin-top:2px}' +
                '.meta{text-align:right;font-size:11px;color:#475569}' +
                '.verdict{background:#f8fafc;border:1px solid #e2e8f0;border-left:4px solid #2563eb;border-radius:6px;padding:14px 16px;margin-bottom:20px}' +
                '.verdict h1{margin:0 0 4px;font-size:16px;color:#0f172a}' +
                '.verdict p{margin:0;color:#64748b;font-size:11px}' +
                'h2{font-size:13px;text-transform:uppercase;letter-spacing:.5px;color:#334155;border-bottom:1px solid #e2e8f0;padding-bottom:5px;margin:24px 0 12px}' +
                'table.recs{width:100%;border-collapse:collapse}' +
                'table.recs th{text-align:left;font-size:10px;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;padding:0 8px 6px;border-bottom:1px solid #e2e8f0}' +
                'table.recs td{padding:9px 8px;border-bottom:1px solid #eef2f7;vertical-align:top}' +
                '.badge{display:inline-block;padding:2px 8px;border-radius:999px;font-size:9px;font-weight:700;white-space:nowrap}' +
                '.badge-high{background:#fee2e2;color:#b91c1c}.badge-medium{background:#fef3c7;color:#a16207}.badge-low{background:#e2e8f0;color:#475569}' +
                '.badge-info{background:#dbeafe;color:#1d4ed8}' +
                '.rec-title{font-weight:600;color:#0f172a}.rec-detail{color:#475569;font-size:11px;margin-top:2px}' +
                '.rec-warn{color:#b45309;font-size:10px;margin-top:3px}' +
                '.scope{font-size:11px;color:#334155;white-space:nowrap}.setting{font-size:11px;color:#2563eb}' +
                '.pri{white-space:nowrap}.clean{color:#16a34a;font-weight:600}' +
                '.tpl{border:1px solid #e2e8f0;border-radius:6px;padding:10px 12px;margin-bottom:10px;page-break-inside:avoid}' +
                '.tpl-name{font-weight:700;color:#0f172a;font-size:12px}' +
                '.tpl-url{font-size:10px;color:#2563eb;word-break:break-all;margin-bottom:5px}' +
                '.tpl-verdict{margin:0;color:#334155;font-size:11px}.tpl-sugg{margin:3px 0 0;font-size:11px;color:#475569}.tpl-warn{color:#b91c1c;font-size:11px;margin:0}' +
                'footer{margin-top:24px;padding-top:10px;border-top:1px solid #e2e8f0;font-size:10px;color:#94a3b8;display:flex;justify-content:space-between}' +
                '@media print{.noprint{display:none}}' +
                'button{font:inherit;padding:8px 16px;background:#2563eb;color:#fff;border:0;border-radius:6px;cursor:pointer}';

            return '<!DOCTYPE html><html><head><meta charset="utf-8">' +
                '<title>MBR Performance Report — ' + esc(ctx.siteName) + '</title><style>' + css + '</style></head><body>' +
                '<div class="toolbar noprint"><button onclick="window.print()">Print / Save as PDF</button></div>' +
                '<div class="sheet">' +
                '<div class="head"><div><div class="brand">MBR<span>Performance</span></div><div class="kicker">Performance Report</div></div>' +
                '<div class="meta">' + esc(ctx.siteName) + '<br>' + esc(ctx.siteUrl) + '<br>' + esc(dateStr) + '</div></div>' +
                '<div class="verdict"><h1>' + esc(site.verdict || 'Site analysis') + '</h1><p>' + (site.templates_ok || 0) + ' template(s) analysed</p></div>' +
                '<h2>Recommended across your site</h2>' + recTable +
                '<h2>By template</h2>' + tpls +
                '<footer><span>Generated by MBR Performance v' + esc(ctx.version) + '</span><span>' + esc(ctx.siteUrl) + '</span></footer>' +
                '</div>' +
                '<script>window.onload=function(){setTimeout(function(){window.print();},300);};<\/script>' +
                '</body></html>';
        },

        /**
         * Dismiss the first-run Doctor nudge (also called automatically once a
         * site scan runs). Removes the banner and records the dismissal per-user.
         */
        dismissDoctorNudge: function(e) {
            if (e && e.preventDefault) { e.preventDefault(); }
            var $n = $('#mbr-doctor-nudge');
            if ($n.length) {
                $n.slideUp(150, function() { $(this).remove(); });
            }
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_dismiss_doctor_nudge',
                nonce: mbrpeData.nonce
            });
        },

        /**
         * Reset settings to defaults
         */
        resetSettings: function(e) {
            e.preventDefault();
            if (!confirm(mbrpeData.i18n.confirmReset)) {
                return;
            }

            var $button = $(this);
            MBRPE_Admin.showLoading($button);

            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_reset_settings',
                nonce: mbrpeData.nonce
            }).done(function(response) {
                if (response && response.success) {
                    // Reload so every tab reflects the freshly reset values.
                    window.location.reload();
                } else {
                    MBRPE_Admin.hideLoading($button);
                    alert((response && response.data && response.data.message) || 'Reset failed.');
                }
            }).fail(function() {
                MBRPE_Admin.hideLoading($button);
                alert('Reset failed.');
            });
        },

        /**
         * Clear font cache
         */
        clearFontCache: function(e) {
            e.preventDefault();
            e.stopPropagation();
            var self = MBRPE_Admin;
            var $button = $(this);
            var $status = $('#clear-font-status');

            if (!confirm('Are you sure you want to delete ALL downloaded fonts and reset the configuration? This cannot be undone.')) {
                return;
            }

            $status.html('');
            var originalText = $button.text();
            $button.text('Clearing...').prop('disabled', true);

            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_clear_font_cache',
                nonce: mbrpeData.nonce
            }, function(response) {
                $button.text(originalText).prop('disabled', false);
                if (response && response.success) {
                    self.showMessage($status, (response.data && response.data.message) || 'Font cache cleared.', 'success');
                } else {
                    self.showMessage($status, (response && response.data && response.data.message) || 'An error occurred.', 'error');
                }
            }).fail(function(xhr, status, error) {
                $button.text(originalText).prop('disabled', false);
                self.showMessage($status, 'Request failed: ' + error, 'error');
            });
        },

        /**
         * Download manual fonts
         */
        downloadManualFonts: function(e) {
            e.preventDefault();
            var $button = $(this);
            var $status = $('#manual-font-status');
            var manualFonts = $('#manual_fonts').val();
            
            if (!manualFonts) {
                MBRPE_Admin.showMessage($status, 'Please enter fonts to download', 'error');
                return;
            }
            
            MBRPE_Admin.showLoading($button);
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_download_manual_fonts',
                nonce: mbrpeData.nonce,
                manual_fonts: manualFonts
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * Clean revisions
         */
        cleanRevisions: function(e) {
            e.preventDefault();
            var $button = $(this);
            var $status = $('#revision-stats');
            
            if (!confirm('Are you sure you want to delete excess revisions?')) {
                return;
            }
            
            MBRPE_Admin.showLoading($button);
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_clean_revisions',
                nonce: mbrpeData.nonce,
                keep: $('#keep_revisions').val()
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * Scan post meta
         */
        scanPostMeta: function(e) {
            e.preventDefault();
            var $button = $(this);
            var $status = $('#post-meta-stats');
            var $deleteButton = $('#delete-post-meta');
            
            MBRPE_Admin.showLoading($button);
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_scan_post_meta',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, 'Found: ' + response.data.count + ' orphaned entries', 'success');
                    if (response.data.count > 0) {
                        $deleteButton.prop('disabled', false);
                    }
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * Delete post meta
         */
        deletePostMeta: function(e) {
            e.preventDefault();
            var $button = $(this);
            var $status = $('#post-meta-stats');
            
            if (!confirm('Are you sure you want to delete orphaned post meta?')) {
                return;
            }
            
            MBRPE_Admin.showLoading($button);
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_delete_post_meta',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, response.data.message, 'success');
                    $button.prop('disabled', true);
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * Scan comment meta
         */
        scanCommentMeta: function(e) {
            e.preventDefault();
            var $button = $(this);
            var $status = $('#comment-meta-stats');
            var $deleteButton = $('#delete-comment-meta');
            
            MBRPE_Admin.showLoading($button);
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_scan_comment_meta',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, 'Found: ' + response.data.count + ' orphaned entries', 'success');
                    if (response.data.count > 0) {
                        $deleteButton.prop('disabled', false);
                    }
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * Delete comment meta
         */
        deleteCommentMeta: function(e) {
            e.preventDefault();
            var $button = $(this);
            var $status = $('#comment-meta-stats');
            
            if (!confirm('Are you sure you want to delete orphaned comment meta?')) {
                return;
            }
            
            MBRPE_Admin.showLoading($button);
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_delete_comment_meta',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, response.data.message, 'success');
                    $button.prop('disabled', true);
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * Scan relationships
         */
        scanRelationships: function(e) {
            e.preventDefault();
            var $button = $(this);
            var $status = $('#relationship-stats');
            var $deleteButton = $('#delete-relationships');
            
            MBRPE_Admin.showLoading($button);
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_scan_relationships',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, 'Found: ' + response.data.count + ' orphaned entries', 'success');
                    if (response.data.count > 0) {
                        $deleteButton.prop('disabled', false);
                    }
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * Delete relationships
         */
        deleteRelationships: function(e) {
            e.preventDefault();
            var $button = $(this);
            var $status = $('#relationship-stats');
            
            if (!confirm('Are you sure you want to delete orphaned relationships?')) {
                return;
            }
            
            MBRPE_Admin.showLoading($button);
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_delete_relationships',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, response.data.message, 'success');
                    $button.prop('disabled', true);
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * Scan term meta
         */
        scanTermMeta: function(e) {
            e.preventDefault();
            var $button = $(this);
            var $status = $('#term-meta-stats');
            var $deleteButton = $('#delete-term-meta');
            
            MBRPE_Admin.showLoading($button);
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_scan_term_meta',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, 'Found: ' + response.data.count + ' orphaned entries', 'success');
                    if (response.data.count > 0) {
                        $deleteButton.prop('disabled', false);
                    }
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * Delete term meta
         */
        deleteTermMeta: function(e) {
            e.preventDefault();
            var $button = $(this);
            var $status = $('#term-meta-stats');
            
            if (!confirm('Are you sure you want to delete orphaned term meta?')) {
                return;
            }
            
            MBRPE_Admin.showLoading($button);
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_delete_term_meta',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, response.data.message, 'success');
                    $button.prop('disabled', true);
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * Get transient stats
         */
        getTransientStats: function(e) {
            e.preventDefault();
            var $button = $(this);
            var $status = $('#transient-stats');
            
            MBRPE_Admin.showLoading($button);
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_transient_stats',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * Delete expired transients
         */
        deleteExpiredTransients: function(e) {
            e.preventDefault();
            var $button = $(this);
            var $status = $('#transient-stats');
            
            MBRPE_Admin.showLoading($button);
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_delete_expired_transients',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * Delete all transients
         */
        deleteAllTransients: function(e) {
            e.preventDefault();
            var $button = $(this);
            var $status = $('#transient-stats');
            
            if (!confirm('Are you sure? This may cause temporary performance drop.')) {
                return;
            }
            
            MBRPE_Admin.showLoading($button);
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_delete_all_transients',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * Optimize tables
         */
        optimizeTables: function(e) {
            e.preventDefault();
            var $button = $(this);
            var $status = $('#optimization-status');
            
            MBRPE_Admin.showLoading($button);
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_optimize_tables',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * Convert to InnoDB
         */
        convertToInnoDB: function(e) {
            e.preventDefault();
            var $button = $(this);
            var $status = $('#innodb-status');
            
            if (!confirm('Are you sure you want to convert MyISAM tables to InnoDB? Test on a staging site first!')) {
                return;
            }
            
            MBRPE_Admin.showLoading($button);
            $status.html('');
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_convert_innodb',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message || 'An error occurred', 'error');
                }
            }).fail(function(xhr, status, error) {
                MBRPE_Admin.hideLoading($button);
                MBRPE_Admin.showMessage($status, 'AJAX Error: ' + error, 'error');
            });
        },

        /**
         * Repair tables
         */
        repairTables: function(e) {
            e.preventDefault();
            var $button = $(this);
            var $status = $('#repair-status');
            
            MBRPE_Admin.showLoading($button);
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_repair_tables',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * Get database info
         */
        getDatabaseInfo: function(e) {
            e.preventDefault();
            var $button = $(this);
            var $status = $('#db-info');
            
            MBRPE_Admin.showLoading($button);
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_db_info',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    $status.html(response.data.html);
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * Scan CSS
         */
        scanCSS: function(e) {
            e.preventDefault();
            var $button = $(this);
            var $status = $('#scan-status');
            
            MBRPE_Admin.showLoading($button);
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_scan_css',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    $status.html(response.data.html);
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * Clear scan data
         */
        clearScanData: function(e) {
            e.preventDefault();
            var $button = $(this);
            var $status = $('#scan-status');
            
            MBRPE_Admin.showLoading($button);
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_clear_scan_data',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * Clear the combined CSS/JS bundle cache (type read from the button).
         */
        clearCombineCache: function(e) {
            e.preventDefault();
            var $button = $(this);
            var type = $button.data('cache-type') || 'all';
            var $status = $('#mbr-combine-' + type + '-status');

            MBRPE_Admin.showLoading($button);

            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_clear_combine_cache',
                nonce: mbrpeData.nonce,
                cache_type: type
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response && response.success) {
                    $('#mbr-combine-' + type + '-count').text('0');
                    $('#mbr-combine-' + type + '-size').text('0 B');
                    MBRPE_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBRPE_Admin.showMessage($status, (response && response.data && response.data.message) || 'Failed to clear cache.', 'error');
                }
            }).fail(function() {
                MBRPE_Admin.hideLoading($button);
                MBRPE_Admin.showMessage($status, 'Request failed.', 'error');
            });
        },

        clearUsedCss: function(e) {
            e.preventDefault();
            var $button = $(this);
            var $status = $('#mbr-used-css-status');

            MBRPE_Admin.showLoading($button);

            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_clear_used_css',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response && response.success) {
                    $('#mbr-used-css-count').text('0');
                    $('#mbr-used-css-size').text('0 B');
                    MBRPE_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBRPE_Admin.showMessage($status, (response && response.data && response.data.message) || 'Failed to clear cache.', 'error');
                }
            }).fail(function() {
                MBRPE_Admin.hideLoading($button);
                MBRPE_Admin.showMessage($status, 'Request failed.', 'error');
            });
        },

        /* ==================================================================
         * WebP Converter
         * ================================================================ */

        /**
         * Start bulk WebP conversion
         */
        webpStartConversion: function(e) {
            e.preventDefault();
            var $startBtn   = $('#mbr-webp-start-conversion');
            var $clearBtn   = $('#mbr-webp-clear-history');
            var $bulkBtn    = $('#mbr-webp-apply-bulk');
            var $progress   = $('#mbr-webp-progress-container');
            var $bar        = $('#mbr-webp-progress-bar');
            var $status     = $('#mbr-webp-status');
            var $list       = $('#mbr-webp-history-list');

            $startBtn.prop('disabled', true).text('Processing…');
            $clearBtn.prop('disabled', true);
            $bulkBtn.prop('disabled', true);
            $status.text('Finding images to convert…');
            $progress.show();

            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_webp_get_images',
                nonce: mbrpeData.nonce
            }, function(response) {
                if (!response || !response.success || !Array.isArray(response.data)) {
                    $status.text('Failed to list images.');
                    $startBtn.prop('disabled', false).text('Start Conversion');
                    $clearBtn.prop('disabled', false);
                    $bulkBtn.prop('disabled', false);
                    return;
                }

                var allImages = response.data;
                if (allImages.length === 0) {
                    $status.text('No unconverted images found — everything is up to date.');
                    $startBtn.prop('disabled', false).text('Start Conversion');
                    $clearBtn.prop('disabled', false);
                    $bulkBtn.prop('disabled', false);
                    return;
                }

                var idx = 0;
                function updateProgress() {
                    var pct = Math.round((idx / allImages.length) * 100);
                    $bar.css('width', pct + '%').text(pct + '%');
                }
                function processNext() {
                    if (idx >= allImages.length) {
                        $status.text('Done — ' + allImages.length + ' image(s) processed.');
                        updateProgress();
                        $startBtn.prop('disabled', false).text('Start Conversion');
                        $clearBtn.prop('disabled', false);
                        $bulkBtn.prop('disabled', false);
                        return;
                    }
                    var imagePath = allImages[idx];
                    $.post(mbrpeData.ajaxUrl, {
                        action: 'mbrpe_webp_process_image',
                        nonce: mbrpeData.nonce,
                        image_path: imagePath
                    })
                    .done(function(resp) {
                        if (resp && resp.success) {
                            var d = resp.data || {};
                            var filename = (d.original_path || imagePath).split('/').pop();
                            var fullUrl  = mbrpeData.uploadUrl + '/' + (d.original_path || imagePath);
                            var row = '<tr>' +
                                '<th scope="row" class="check-column"><input type="checkbox" class="mbr-webp-item-checkbox" value="' + imagePath + '"></th>' +
                                '<td><a href="' + fullUrl + '" target="_blank">' + filename + '</a></td>' +
                                '<td>' + (d.original_size || '') + '</td>' +
                                '<td>' + (d.webp_size || '') + '</td>' +
                                '<td>—</td>' +
                                '<td>' + (d.compression || '') + '</td>' +
                                '</tr>';
                            $list.prepend(row);
                            $status.text('Converted: ' + (d.original_path || imagePath));
                        } else {
                            var msg = (resp && resp.data) ? resp.data : 'Unknown error';
                            $list.prepend('<tr><th scope="row" class="check-column"></th><td colspan="5" style="color: var(--mbr-danger);">Failed: ' + imagePath + ' — ' + msg + '</td></tr>');
                        }
                    })
                    .fail(function() {
                        $list.prepend('<tr><th scope="row" class="check-column"></th><td colspan="5" style="color: var(--mbr-danger);">AJAX error processing ' + imagePath + '</td></tr>');
                    })
                    .always(function() {
                        idx++;
                        updateProgress();
                        processNext();
                    });
                }
                updateProgress();
                processNext();
            }).fail(function() {
                $status.text('AJAX failed while listing images.');
                $startBtn.prop('disabled', false).text('Start Conversion');
                $clearBtn.prop('disabled', false);
                $bulkBtn.prop('disabled', false);
            });
        },

        /**
         * Clear all WebP conversion history
         */
        webpClearHistory: function(e) {
            e.preventDefault();
            if (!confirm('Are you sure you want to clear all conversion history? This cannot be undone.')) {
                return;
            }
            var $btn    = $('#mbr-webp-clear-history');
            var $status = $('#mbr-webp-status');

            $btn.prop('disabled', true).text('Clearing…');
            $('#mbr-webp-start-conversion, #mbr-webp-apply-bulk').prop('disabled', true);
            $status.text('Clearing history…');

            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_webp_clear_history',
                nonce: mbrpeData.nonce
            })
            .done(function(response) {
                if (response && response.success) {
                    $status.text(response.data || 'History cleared.');
                    $('#mbr-webp-history-list').html('<tr><td colspan="6">No images converted yet.</td></tr>');
                } else {
                    $status.text('Failed to clear history.');
                }
            })
            .fail(function() {
                $status.text('AJAX failed while clearing history.');
            })
            .always(function() {
                $btn.prop('disabled', false).text('Clear All History');
                $('#mbr-webp-start-conversion, #mbr-webp-apply-bulk').prop('disabled', false);
            });
        },

        /**
         * Apply bulk action on selected WebP history items
         */
        webpApplyBulk: function(e) {
            e.preventDefault();
            var action = $('#mbr-webp-bulk-action').val();
            if (action !== 'delete') {
                return;
            }

            var items = [];
            $('.mbr-webp-item-checkbox:checked').each(function() {
                items.push($(this).val());
            });

            if (items.length === 0) {
                alert('No items selected.');
                return;
            }

            var $status = $('#mbr-webp-status');

            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_webp_bulk_delete',
                nonce: mbrpeData.nonce,
                items: items
            })
            .done(function(response) {
                if (response && response.success) {
                    $status.text(response.data);
                    // Remove the rows from the table.
                    $('.mbr-webp-item-checkbox:checked').closest('tr').remove();
                    if ($('#mbr-webp-history-list tr').length === 0) {
                        $('#mbr-webp-history-list').html('<tr><td colspan="6">No images converted yet.</td></tr>');
                    }
                } else {
                    $status.text(response.data || 'Bulk action failed.');
                }
            })
            .fail(function() {
                $status.text('AJAX failed during bulk action.');
            });
        },

        /**
         * Revert all WebP files — delete every plugin-created WebP and clear history
         */
        webpRevertAll: function(e) {
            e.preventDefault();
            if (!confirm('This will DELETE every WebP file created by this plugin and clear all conversion history.\n\nOriginal images (JPG/PNG) are never touched.\nFiles that were uploaded as WebP are left intact.\n\nThis cannot be undone. Continue?')) {
                return;
            }

            var $btn    = $('#mbr-webp-revert-all');
            var $status = $('#mbr-webp-status');

            $btn.prop('disabled', true).text('Reverting…');
            $('#mbr-webp-start-conversion, #mbr-webp-clear-history, #mbr-webp-apply-bulk').prop('disabled', true);
            $status.text('Deleting WebP files and clearing history…');

            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_webp_revert_all',
                nonce: mbrpeData.nonce
            })
            .done(function(response) {
                if (response && response.success) {
                    $status.text(response.data || 'Revert complete.');
                    $('#mbr-webp-history-list').html('<tr><td colspan="6">No images converted yet.</td></tr>');
                } else {
                    var msg = (response && response.data && response.data.message) ? response.data.message : 'Revert failed.';
                    $status.text(msg);
                }
            })
            .fail(function() {
                $status.text('AJAX failed during revert.');
            })
            .always(function() {
                $btn.prop('disabled', false).text('Revert All WebP Files');
                $('#mbr-webp-start-conversion, #mbr-webp-clear-history, #mbr-webp-apply-bulk').prop('disabled', false);
            });
        },

        /* ================================================================
         * AVIF Bulk Converter
         *
         * Parallel to the WebP bulk converter above. The PHP side gates
         * registration on the server actually supporting AVIF, so these
         * handlers won't even be reachable on hosts where AVIF encoding
         * isn't available. The avif_process_image AJAX response includes
         * webp_size when the image already has a recorded WebP variant,
         * so each live-prepended row shows complete data rather than a
         * second AVIF-only row that would visually duplicate the WebP one.
         * ================================================================ */

        avifStartConversion: function(e) {
            e.preventDefault();
            var $startBtn = $('#mbr-avif-start-conversion');
            var $clearBtn = $('#mbr-avif-clear-history');
            var $revertBtn = $('#mbr-avif-revert-all');
            var $progress = $('#mbr-avif-progress-container');
            var $bar      = $('#mbr-avif-progress-bar');
            var $status   = $('#mbr-avif-status');
            var $list     = $('#mbr-webp-history-list');

            $startBtn.prop('disabled', true).text('Processing…');
            $clearBtn.prop('disabled', true);
            $revertBtn.prop('disabled', true);
            $status.text('Finding images to convert…');
            $progress.show();

            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_avif_get_images',
                nonce: mbrpeData.nonce
            }, function(response) {
                if (!response || !response.success || !Array.isArray(response.data)) {
                    var errMsg = (response && response.data) ? response.data : 'Failed to list images.';
                    $status.text(errMsg);
                    $startBtn.prop('disabled', false).text('Start AVIF Conversion');
                    $clearBtn.prop('disabled', false);
                    $revertBtn.prop('disabled', false);
                    return;
                }

                var allImages = response.data;
                if (allImages.length === 0) {
                    $status.text('No unconverted images found — every JPG/PNG already has an .avif sibling.');
                    $startBtn.prop('disabled', false).text('Start AVIF Conversion');
                    $clearBtn.prop('disabled', false);
                    $revertBtn.prop('disabled', false);
                    return;
                }

                var idx = 0;
                function updateProgress() {
                    var pct = Math.round((idx / allImages.length) * 100);
                    $bar.css('width', pct + '%').text(pct + '%');
                }
                function processNext() {
                    if (idx >= allImages.length) {
                        $status.text('Done — ' + allImages.length + ' image(s) processed.');
                        updateProgress();
                        $startBtn.prop('disabled', false).text('Start AVIF Conversion');
                        $clearBtn.prop('disabled', false);
                        $revertBtn.prop('disabled', false);
                        return;
                    }
                    var imagePath = allImages[idx];
                    $.post(mbrpeData.ajaxUrl, {
                        action: 'mbrpe_avif_process_image',
                        nonce: mbrpeData.nonce,
                        image_path: imagePath
                    })
                    .done(function(resp) {
                        if (resp && resp.success) {
                            var d = resp.data || {};
                            var filename = (d.original_path || imagePath).split('/').pop();
                            var fullUrl  = mbrpeData.uploadUrl + '/' + (d.original_path || imagePath);
                            var row = '<tr>' +
                                '<th scope="row" class="check-column"><input type="checkbox" class="mbr-webp-item-checkbox" value="' + imagePath + '"></th>' +
                                '<td><a href="' + fullUrl + '" target="_blank">' + filename + '</a></td>' +
                                '<td>' + (d.original_size || '') + '</td>' +
                                '<td>' + (d.webp_size || '—') + '</td>' +
                                '<td>' + (d.avif_size || '') + '</td>' +
                                '<td>' + (d.compression || '') + '</td>' +
                                '</tr>';
                            $list.prepend(row);
                            $status.text('Converted: ' + (d.original_path || imagePath));
                        } else {
                            var msg = (resp && resp.data) ? resp.data : 'Unknown error';
                            $list.prepend('<tr><th scope="row" class="check-column"></th><td colspan="5" style="color: var(--mbr-danger);">Failed: ' + imagePath + ' — ' + msg + '</td></tr>');
                        }
                    })
                    .fail(function() {
                        $list.prepend('<tr><th scope="row" class="check-column"></th><td colspan="5" style="color: var(--mbr-danger);">AJAX error processing ' + imagePath + '</td></tr>');
                    })
                    .always(function() {
                        idx++;
                        updateProgress();
                        processNext();
                    });
                }
                updateProgress();
                processNext();
            }).fail(function() {
                $status.text('AJAX failed while listing images.');
                $startBtn.prop('disabled', false).text('Start AVIF Conversion');
                $clearBtn.prop('disabled', false);
                $revertBtn.prop('disabled', false);
            });
        },

        avifClearHistory: function(e) {
            e.preventDefault();
            if (!confirm('Clear all AVIF conversion history records? AVIF files on disk are not affected — use "Revert All AVIF Files" if you want to delete those.')) {
                return;
            }
            var $btn    = $('#mbr-avif-clear-history');
            var $status = $('#mbr-avif-status');

            $btn.prop('disabled', true).text('Clearing…');
            $('#mbr-avif-start-conversion, #mbr-avif-revert-all').prop('disabled', true);
            $status.text('Clearing AVIF history…');

            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_avif_clear_history',
                nonce: mbrpeData.nonce
            })
            .done(function(response) {
                if (response && response.success) {
                    $status.text((response.data && response.data.message) || 'AVIF history cleared.');
                    // Reload so the merged history table rebuilds against
                    // current options — no point hand-patching DOM here.
                    setTimeout(function(){ location.reload(); }, 600);
                } else {
                    var msg = (response && response.data && response.data.message) ? response.data.message : 'Clear failed.';
                    $status.text(msg);
                    $btn.prop('disabled', false).text('Clear AVIF History');
                    $('#mbr-avif-start-conversion, #mbr-avif-revert-all').prop('disabled', false);
                }
            })
            .fail(function() {
                $status.text('AJAX failed.');
                $btn.prop('disabled', false).text('Clear AVIF History');
                $('#mbr-avif-start-conversion, #mbr-avif-revert-all').prop('disabled', false);
            });
        },

        avifRevertAll: function(e) {
            e.preventDefault();
            if (!confirm('This will DELETE every .avif file created by this plugin and clear AVIF history.\n\nOriginals and WebP variants are not touched.\n\nThis cannot be undone. Continue?')) {
                return;
            }
            var $btn    = $('#mbr-avif-revert-all');
            var $status = $('#mbr-avif-status');

            $btn.prop('disabled', true).text('Reverting…');
            $('#mbr-avif-start-conversion, #mbr-avif-clear-history').prop('disabled', true);
            $status.text('Deleting AVIF files and clearing history…');

            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_avif_revert_all',
                nonce: mbrpeData.nonce
            })
            .done(function(response) {
                if (response && response.success) {
                    $status.text(response.data || 'Revert complete.');
                    // Reload so the merged history table reflects only
                    // remaining WebP entries (if any).
                    setTimeout(function(){ location.reload(); }, 800);
                } else {
                    var msg = (response && response.data && response.data.message) ? response.data.message : 'Revert failed.';
                    $status.text(msg);
                    $btn.prop('disabled', false).text('Revert All AVIF Files');
                    $('#mbr-avif-start-conversion, #mbr-avif-clear-history').prop('disabled', false);
                }
            })
            .fail(function() {
                $status.text('AJAX failed during revert.');
                $btn.prop('disabled', false).text('Revert All AVIF Files');
                $('#mbr-avif-start-conversion, #mbr-avif-clear-history').prop('disabled', false);
            });
        },

        /* ================================================================
         * Image Dimensions — Bulk Resize
         * ================================================================ */

        /**
         * Scan the Media Library for oversized images.
         */
        imgDimScan: function(e) {
            e.preventDefault();
            var $scanBtn  = $('#mbr-imgdim-scan');
            var $startBtn = $('#mbr-imgdim-start');
            var $status   = $('#mbr-imgdim-status');

            $scanBtn.prop('disabled', true).text('Scanning…');
            $startBtn.prop('disabled', true);
            $status.text('Scanning Media Library — this may take a moment on large libraries…');

            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_image_dimensions_scan',
                nonce: mbrpeData.nonce
            })
            .done(function(response) {
                if (!response || !response.success || !response.data) {
                    var msg = (response && response.data) ? response.data : 'Scan failed.';
                    $status.text('Scan failed: ' + msg);
                    $startBtn.prop('disabled', true).removeData('imgdim-ids');
                    return;
                }

                var data = response.data;
                var ids = Array.isArray(data.ids) ? data.ids : [];
                $startBtn.data('imgdim-ids', ids);
                $startBtn.data('imgdim-max', data.max_dim || '');

                if (ids.length === 0) {
                    $status.text('No oversized images found — everything is within the configured maximum of ' + (data.max_dim || '?') + 'px.');
                    $startBtn.prop('disabled', true);
                } else {
                    $status.text('Found ' + ids.length + ' image(s) exceeding ' + (data.max_dim || '?') + 'px. Click "Start Resize" to process them.');
                    $startBtn.prop('disabled', false);
                }
            })
            .fail(function() {
                $status.text('AJAX failed during scan.');
                $startBtn.prop('disabled', true);
            })
            .always(function() {
                $scanBtn.prop('disabled', false).text('Scan Media Library');
            });
        },

        /**
         * Kick off bulk resize, one attachment at a time.
         */
        imgDimStart: function(e) {
            e.preventDefault();

            var $scanBtn     = $('#mbr-imgdim-scan');
            var $startBtn    = $('#mbr-imgdim-start');
            var $progress    = $('#mbr-imgdim-progress-container');
            var $bar         = $('#mbr-imgdim-progress-bar');
            var $status      = $('#mbr-imgdim-status');
            var $logWrapper  = $('#mbr-imgdim-log-wrapper');
            var $log         = $('#mbr-imgdim-log');

            var ids = $startBtn.data('imgdim-ids') || [];
            if (!ids.length) {
                $status.text('No images queued — click "Scan Media Library" first.');
                return;
            }

            if (!confirm('This will permanently overwrite ' + ids.length + ' image file(s) on disk to fit within the configured maximum dimension.\n\nThere is no automatic undo. Continue?')) {
                return;
            }

            $scanBtn.prop('disabled', true);
            $startBtn.prop('disabled', true).text('Resizing…');
            $progress.show();
            $logWrapper.show();
            $bar.css('width', '0%').text('0%');
            $log.empty();

            var total     = ids.length;
            var idx       = 0;
            var succeeded = 0;
            var skipped   = 0;
            var errored   = 0;
            var savedBytes = 0;

            function updateProgress() {
                var pct = Math.round((idx / total) * 100);
                $bar.css('width', pct + '%').text(pct + '%');
                $status.text('Resizing ' + Math.min(idx + 1, total) + ' of ' + total + ' — ' + succeeded + ' done, ' + skipped + ' skipped, ' + errored + ' error(s).');
            }

            function processNext() {
                if (idx >= total) {
                    $bar.css('width', '100%').text('100%');
                    var savedH = (savedBytes > 1048576) ? (savedBytes / 1048576).toFixed(2) + ' MB'
                               : (savedBytes > 1024) ? (savedBytes / 1024).toFixed(2) + ' KB'
                               : savedBytes + ' B';
                    $status.text('Done — resized ' + succeeded + ', skipped ' + skipped + ', errored ' + errored + '. Total saved: ' + savedH + '.');
                    $scanBtn.prop('disabled', false);
                    $startBtn.prop('disabled', true).text('Start Resize').removeData('imgdim-ids');
                    return;
                }

                var id = ids[idx];
                $.post(mbrpeData.ajaxUrl, {
                    action: 'mbrpe_image_dimensions_resize',
                    nonce: mbrpeData.nonce,
                    attachment_id: id
                })
                .done(function(resp) {
                    if (resp && resp.success && resp.data) {
                        var d = resp.data;
                        if (d.status === 'success') {
                            succeeded++;
                            savedBytes += (d.saved_bytes || 0);
                            $log.prepend(
                                '<tr>' +
                                '<td>' + (d.filename || ('#' + id)) + '</td>' +
                                '<td>' + (d.original_width || '?') + '×' + (d.original_height || '?') + ' (' + (d.original_size_h || '') + ')</td>' +
                                '<td>' + (d.new_width || '?') + '×' + (d.new_height || '?') + ' (' + (d.new_size_h || '') + ')</td>' +
                                '<td style="color: var(--mbr-success);">' + (d.saved_h || '0 B') + '</td>' +
                                '</tr>'
                            );
                        } else if (d.status === 'skipped') {
                            skipped++;
                            $log.prepend(
                                '<tr>' +
                                '<td>' + (d.filename || ('#' + id)) + '</td>' +
                                '<td colspan="3" style="color: var(--mbr-text-secondary);">Skipped — ' + (d.reason || 'already within limits') + '</td>' +
                                '</tr>'
                            );
                        }
                    } else {
                        errored++;
                        var msg = (resp && resp.data && resp.data.message) ? resp.data.message : 'Unknown error';
                        $log.prepend(
                            '<tr>' +
                            '<td>#' + id + '</td>' +
                            '<td colspan="3" style="color: var(--mbr-danger);">Error — ' + msg + '</td>' +
                            '</tr>'
                        );
                    }
                })
                .fail(function() {
                    errored++;
                    $log.prepend(
                        '<tr>' +
                        '<td>#' + id + '</td>' +
                        '<td colspan="3" style="color: var(--mbr-danger);">AJAX error</td>' +
                        '</tr>'
                    );
                })
                .always(function() {
                    idx++;
                    updateProgress();
                    processNext();
                });
            }

            updateProgress();
            processNext();
        },

        /**
         * WooCommerce: clear expired sessions
         */
        wcClearSessions: function(e) {
            e.preventDefault();
            var $button = $(this);
            var $status = $('#wc-session-stats');

            MBRPE_Admin.showLoading($button);

            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_wc_clear_sessions',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * WooCommerce: clear transients
         */
        wcClearTransients: function(e) {
            e.preventDefault();
            var $button = $(this);
            var $status = $('#wc-transient-stats');

            MBRPE_Admin.showLoading($button);

            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_wc_clear_transients',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * WooCommerce: run Action Scheduler cleanup
         */
        wcCleanupActionScheduler: function(e) {
            e.preventDefault();
            var $button = $(this);
            var $status = $('#wc-action-scheduler-stats');

            if (!confirm('Run Action Scheduler cleanup now? This removes completed and failed historical actions beyond the retention period.')) {
                return;
            }

            MBRPE_Admin.showLoading($button);

            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_wc_cleanup_as',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        MBRPE_Admin.init();
    });

})(jQuery);
