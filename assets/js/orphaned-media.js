/* global jQuery, mbrpeData, mbrpeOrphanedImages */
(function($) {
    'use strict';

    var i18n = (mbrpeOrphanedImages && mbrpeOrphanedImages.i18n) ? mbrpeOrphanedImages.i18n : {};

    var state = {
        scanning: false,
        currentPage: 1,
        currentFilter: '',
        currentMediaType: '',
        currentOrderby: 'file_size',
        selected: {} // attachment_id -> true
    };

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    function ajax(action, data) {
        return $.post(mbrpeData.ajaxUrl, $.extend({
            action: 'mbrpe_orphan_' + action,
            nonce: mbrpeData.nonce
        }, data || {}));
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function setProgress(pct, text) {
        $('#mbr-orphan-scan-progress-fill').css('width', Math.min(100, Math.max(0, pct)) + '%');
        $('#mbr-orphan-scan-progress-text').text(text || '');
    }

    function updateBulkButton() {
        var count = Object.keys(state.selected).length;
        $('#mbr-orphan-bulk-count').text('(' + count + ')');
        $('#mbr-orphan-bulk-delete').prop('disabled', count === 0);
    }

    function clearSelection() {
        state.selected = {};
        $('#mbr-orphan-select-all').prop('checked', false);
        updateBulkButton();
    }

    // -----------------------------------------------------------------
    // Scan flow
    // -----------------------------------------------------------------

    function runScan() {
        if (state.scanning) return;
        state.scanning = true;

        var $btn = $('#mbr-orphan-scan');
        $btn.prop('disabled', true).text(i18n.scanning || 'Scanning…');
        $('#mbr-orphan-scan-progress').show();
        setProgress(0, '');
        clearSelection();

        ajax('scan_init').done(function(resp) {
            if (!resp || !resp.success) {
                handleScanFailure(resp);
                return;
            }

            var data = resp.data;
            var ids = data.ids || [];
            var batchSize = data.batch_size || 50;
            var total = data.total || 0;

            if (total === 0) {
                $('#mbr-orphan-scan-status').text(i18n.noResults || 'No images to scan.');
                finishScan();
                return;
            }

            processBatches(ids, batchSize, total);
        }).fail(function() {
            handleScanFailure();
        });
    }

    function processBatches(ids, batchSize, total) {
        var processed = 0;
        var orphansFound = 0;

        function next() {
            if (ids.length === 0) {
                $('#mbr-orphan-scan-status').text(
                    (i18n.scanComplete || 'Scan complete.') +
                    ' ' + orphansFound + ' candidate(s) found.'
                );
                setProgress(100, processed + ' / ' + total);
                finishScan();
                loadCandidates();
                return;
            }

            var batch = ids.splice(0, batchSize);
            ajax('scan_batch', { ids: batch }).done(function(resp) {
                if (resp && resp.success && resp.data) {
                    processed = resp.data.total_processed || (processed + batch.length);
                    orphansFound += (resp.data.orphans_in_batch || 0);
                } else {
                    processed += batch.length;
                }
                var pct = total > 0 ? Math.round((processed / total) * 100) : 100;
                setProgress(pct, processed + ' / ' + total + ' — ' + orphansFound + ' orphans found');
                // Yield briefly so the UI can paint and the user sees movement.
                setTimeout(next, 50);
            }).fail(function() {
                // On batch failure, skip and continue rather than abort entirely.
                processed += batch.length;
                setTimeout(next, 50);
            });
        }

        next();
    }

    function handleScanFailure(resp) {
        var msg = i18n.scanFailed || 'Scan failed.';
        if (resp && resp.data) {
            msg += ' ' + (typeof resp.data === 'string' ? resp.data : '');
        }
        $('#mbr-orphan-scan-status').text(msg);
        finishScan();
    }

    function finishScan() {
        state.scanning = false;
        $('#mbr-orphan-scan').prop('disabled', false).text('Run Scan');
        // Hide progress bar after a short delay so the "100%" lands.
        setTimeout(function() {
            $('#mbr-orphan-scan-progress').hide();
        }, 1500);
    }

    // -----------------------------------------------------------------
    // Candidate list
    // -----------------------------------------------------------------

    function loadCandidates() {
        ajax('get_candidates', {
            confidence: state.currentFilter,
            media_type: state.currentMediaType,
            orderby: state.currentOrderby,
            order: 'DESC',
            page: state.currentPage,
            per_page: 25
        }).done(function(resp) {
            if (!resp || !resp.success) {
                renderTableError();
                return;
            }
            renderCandidates(resp.data);
            renderStats(resp.data.stats);
        }).fail(renderTableError);
    }

    var TYPE_ICONS = {
        images:    '🖼️',
        videos:    '🎬',
        audio:     '🎵',
        documents: '📄',
        archives:  '📦',
        other:     '📁'
    };

    var TYPE_LABELS = {
        images:    'Images',
        videos:    'Videos',
        audio:     'Audio',
        documents: 'Documents',
        archives:  'Archives',
        other:     'Other'
    };

    function renderStats(stats) {
        if (!stats) return;
        $('#mbr-orphan-stat-high').text(stats.high.count);
        $('#mbr-orphan-stat-high-bytes').text(formatBytes(stats.high.bytes));
        $('#mbr-orphan-stat-review').text(stats.review.count);
        $('#mbr-orphan-stat-review-bytes').text(formatBytes(stats.review.bytes));
        $('#mbr-orphan-stat-total-bytes').text(formatBytes(stats.total_bytes));

        // Per-type breakdown — refresh existing cards if present, no-op
        // for any type with zero candidates so the row stays compact.
        if (stats.by_type) {
            var $breakdown = $('#mbr-orphan-type-breakdown');
            if ($breakdown.length) {
                var html = '';
                Object.keys(TYPE_LABELS).forEach(function(key) {
                    if (key === 'other') return;
                    var t = stats.by_type[key];
                    if (!t || !t.count) return;
                    html += '<div class="mbr-orphan-type-card" data-type="' + key + '"' +
                            ' style="background: #1f2227; padding: 8px 14px; border-radius: 4px; font-size: 0.9em;">' +
                            '<span style="margin-right: 6px;">' + TYPE_ICONS[key] + '</span>' +
                            '<strong>' + escapeHtml(TYPE_LABELS[key]) + ':</strong> ' +
                            '<span class="mbr-orphan-type-count">' + t.count + '</span> ' +
                            '<span style="color: #9ca3af;">·</span> ' +
                            '<span class="mbr-orphan-type-bytes">' + formatBytes(t.bytes) + '</span>' +
                            '</div>';
                });
                $breakdown.html(html).toggle(html.length > 0);
            }
        }
    }

    function formatBytes(bytes) {
        bytes = parseInt(bytes, 10) || 0;
        if (bytes === 0) return '0 B';
        var units = ['B', 'KB', 'MB', 'GB', 'TB'];
        var i = Math.floor(Math.log(bytes) / Math.log(1024));
        return (bytes / Math.pow(1024, i)).toFixed(2) + ' ' + units[i];
    }

    function renderCandidates(data) {
        var $tbody = $('#mbr-orphan-tbody');
        $tbody.empty();

        var items = data.items || [];
        if (items.length === 0) {
            $tbody.append(
                '<tr><td colspan="7" style="text-align: center; padding: 24px;"><em>' +
                escapeHtml(i18n.noResults || 'No orphaned media found.') +
                '</em></td></tr>'
            );
            renderPagination(0, 25);
            return;
        }

        items.forEach(function(item) {
            $tbody.append(buildRow(item));
        });

        renderPagination(data.total || 0, 25);
    }

    function buildRow(item) {
        var mediaType = item.media_type || 'other';
        var typeIcon = TYPE_ICONS[mediaType] || TYPE_ICONS.other;
        var typeLabel = TYPE_LABELS[mediaType] || TYPE_LABELS.other;

        // Preview: image thumbnail for images, large category icon for everything else.
        var thumbHtml;
        if (item.thumb_url) {
            thumbHtml = '<img src="' + escapeHtml(item.thumb_url) + '" alt="" style="max-width: 60px; max-height: 60px;" loading="lazy" />';
        } else {
            thumbHtml = '<span style="font-size: 32px; color: #6c7080;" title="' + escapeHtml(typeLabel) + '">' + typeIcon + '</span>';
        }

        var fileName = item.file_path ? item.file_path.split('/').pop() : '(unknown)';
        var fileTitle = item.file_url || item.file_path || '';

        var matches = Array.isArray(item.matches) ? item.matches : [];
        var matchHtml = matches.length > 0
            ? '<div style="font-size: 0.85em; color: #f6c177; margin-top: 4px;">Matched in: ' + matches.map(escapeHtml).join(', ') + '</div>'
            : '';

        var confLabel = item.confidence === 'high'
            ? '<span style="background: #1f5e3d; color: #a8e0bd; padding: 3px 8px; border-radius: 3px; font-size: 0.85em;">High</span>'
            : '<span style="background: #6b4f1d; color: #f6c177; padding: 3px 8px; border-radius: 3px; font-size: 0.85em;">Review</span>';

        var typeCell = '<span title="' + escapeHtml(typeLabel) + '" style="font-size: 0.9em;">' +
                       '<span style="margin-right: 4px;">' + typeIcon + '</span>' +
                       escapeHtml(typeLabel) + '</span>';

        var checkboxDisabled = item.confidence !== 'high' ? ' disabled title="' + escapeHtml(i18n.reviewBlocked || '') + '"' : '';
        var checked = state.selected[item.attachment_id] ? ' checked' : '';

        return '' +
            '<tr data-id="' + item.attachment_id + '" data-confidence="' + escapeHtml(item.confidence) + '" data-media-type="' + escapeHtml(mediaType) + '">' +
                '<td><input type="checkbox" class="mbr-orphan-row-cb" value="' + item.attachment_id + '"' + checkboxDisabled + checked + ' /></td>' +
                '<td>' + thumbHtml + '</td>' +
                '<td>' +
                    '<strong>' + escapeHtml(fileName) + '</strong>' +
                    '<div style="font-size: 0.85em; color: #9ca3af;">ID: ' + item.attachment_id + ' · <a href="' + escapeHtml(item.edit_link) + '" target="_blank">Edit</a></div>' +
                    '<div style="font-size: 0.8em; color: #6c7080; margin-top: 2px;" title="' + escapeHtml(fileTitle) + '">' + escapeHtml(fileTitle) + '</div>' +
                    matchHtml +
                '</td>' +
                '<td>' + typeCell + '</td>' +
                '<td>' + escapeHtml(item.file_size_h || '') + '</td>' +
                '<td>' + confLabel + '</td>' +
                '<td>' +
                    '<button type="button" class="button button-small mbr-orphan-delete-one">Delete</button> ' +
                    '<button type="button" class="button button-small mbr-orphan-exclude-one" title="Add to exclusions">Keep</button>' +
                '</td>' +
            '</tr>';
    }

    function renderTableError() {
        $('#mbr-orphan-tbody').html(
            '<tr><td colspan="7" style="text-align: center; padding: 24px; color: #f87171;"><em>' +
            escapeHtml(i18n.genericError || 'An unexpected error occurred.') +
            '</em></td></tr>'
        );
    }

    function renderPagination(total, perPage) {
        var $pag = $('#mbr-orphan-pagination');
        $pag.empty();
        var pages = Math.ceil(total / perPage);
        if (pages <= 1) return;

        var html = '';
        var prev = state.currentPage > 1 ? state.currentPage - 1 : 0;
        var next = state.currentPage < pages ? state.currentPage + 1 : 0;

        html += '<button type="button" class="button button-small mbr-orphan-page" data-page="' + prev + '"' + (prev ? '' : ' disabled') + '>&laquo; Prev</button> ';
        html += ' <span>Page ' + state.currentPage + ' of ' + pages + ' (' + total + ' items)</span> ';
        html += '<button type="button" class="button button-small mbr-orphan-page" data-page="' + next + '"' + (next ? '' : ' disabled') + '>Next &raquo;</button>';

        $pag.html(html);
    }

    // -----------------------------------------------------------------
    // Single + bulk delete
    // -----------------------------------------------------------------

    function deleteOne(id, $row) {
        if (!confirm(i18n.confirmDelete || 'Delete?')) return;

        $row.css('opacity', '0.5');
        $row.find('button').prop('disabled', true);

        ajax('delete', { attachment_id: id }).done(function(resp) {
            if (resp && resp.success) {
                $row.fadeOut(200, function() {
                    $(this).remove();
                });
                delete state.selected[id];
                updateBulkButton();
                // Reload stats and staged list.
                loadCandidates();
                loadStaged();
            } else {
                var msg = (resp && resp.data && resp.data.message) ? resp.data.message : (i18n.genericError || 'Error');
                alert(msg);
                $row.css('opacity', '');
                $row.find('button').prop('disabled', false);
            }
        }).fail(function() {
            alert(i18n.genericError || 'Network error');
            $row.css('opacity', '');
            $row.find('button').prop('disabled', false);
        });
    }

    function bulkDelete() {
        var ids = Object.keys(state.selected).map(function(k) { return parseInt(k, 10); }).filter(Boolean);
        if (ids.length === 0) {
            alert(i18n.noSelection || 'Select at least one item.');
            return;
        }

        // Defensive: confirm only HIGH-confidence rows are selected. The
        // checkbox is disabled for review rows but a determined user could
        // bypass via dev tools — server reverifies anyway, but reject early.
        var blocked = false;
        ids.forEach(function(id) {
            var $row = $('#mbr-orphan-table tr[data-id="' + id + '"]');
            if ($row.data('confidence') !== 'high') {
                blocked = true;
            }
        });
        if (blocked) {
            alert(i18n.reviewBlocked || 'Review items must be deleted individually.');
            return;
        }

        if (!confirm((i18n.confirmBulkDel || 'Delete selected?') + '\n\n' + ids.length + ' attachment(s)')) return;

        $('#mbr-orphan-bulk-delete').prop('disabled', true);

        // Sequential to avoid hammering the server / spawning too many DB writes at once.
        var index = 0;
        function next() {
            if (index >= ids.length) {
                clearSelection();
                loadCandidates();
                loadStaged();
                return;
            }
            var id = ids[index++];
            var $row = $('#mbr-orphan-table tr[data-id="' + id + '"]');
            $row.css('opacity', '0.5');

            ajax('delete', { attachment_id: id }).always(function() {
                $row.fadeOut(150, function() { $(this).remove(); });
                next();
            });
        }
        next();
    }

    function excludeOne(id, $row) {
        ajax('exclude', { attachment_id: id }).done(function(resp) {
            if (resp && resp.success) {
                $row.fadeOut(200, function() { $(this).remove(); });
                delete state.selected[id];
                updateBulkButton();
                loadCandidates();
            } else {
                alert(i18n.genericError || 'Error');
            }
        });
    }

    // -----------------------------------------------------------------
    // Staged / restore list
    // -----------------------------------------------------------------

    function loadStaged() {
        ajax('get_staged', { per_page: 25, page: 1 }).done(function(resp) {
            if (!resp || !resp.success) return;
            renderStaged(resp.data);
        });
    }

    function renderStaged(data) {
        var $tbody = $('#mbr-orphan-staged-tbody');
        $tbody.empty();

        var items = data.items || [];
        $('.mbr-orphan-staged-count').text('(' + (data.total || 0) + ')');

        if (items.length === 0) {
            $tbody.append('<tr><td colspan="5" style="text-align: center; padding: 18px;"><em>Nothing in the restore queue.</em></td></tr>');
            return;
        }

        items.forEach(function(item) {
            var fileName = item.file_path ? item.file_path.split('/').pop() : '(unknown)';
            $tbody.append('' +
                '<tr data-row-id="' + item.id + '">' +
                    '<td><strong>' + escapeHtml(fileName) + '</strong>' +
                        '<div style="font-size: 0.8em; color: #6c7080;">ID was: ' + item.attachment_id + '</div></td>' +
                    '<td>' + escapeHtml(item.file_size_h || '') + '</td>' +
                    '<td>' + escapeHtml(item.deleted_at || '—') + '</td>' +
                    '<td>' + escapeHtml(item.purge_after || 'Never') + '</td>' +
                    '<td><button type="button" class="button button-small mbr-orphan-restore">Restore Record</button></td>' +
                '</tr>'
            );
        });
    }

    function restoreOne(rowId, $row) {
        if (!confirm(i18n.confirmRestore || 'Restore?')) return;

        $row.css('opacity', '0.5');
        $row.find('button').prop('disabled', true);

        ajax('restore', { row_id: rowId }).done(function(resp) {
            if (resp && resp.success) {
                $row.fadeOut(200, function() { $(this).remove(); });
                loadStaged();
                // Inform user that record is back but file isn't.
                if (resp.data && resp.data.message) {
                    alert(resp.data.message);
                }
            } else {
                var msg = (resp && resp.data && resp.data.message) ? resp.data.message : (i18n.genericError || 'Error');
                alert(msg);
                $row.css('opacity', '');
                $row.find('button').prop('disabled', false);
            }
        }).fail(function() {
            alert(i18n.genericError || 'Network error');
            $row.css('opacity', '');
            $row.find('button').prop('disabled', false);
        });
    }

    // -----------------------------------------------------------------
    // Event bindings
    // -----------------------------------------------------------------

    $(document).on('click', '#mbr-orphan-scan', runScan);

    $(document).on('change', '#mbr-orphan-filter', function() {
        state.currentFilter = $(this).val();
        state.currentPage = 1;
        clearSelection();
        loadCandidates();
    });

    $(document).on('change', '#mbr-orphan-type-filter', function() {
        state.currentMediaType = $(this).val();
        state.currentPage = 1;
        clearSelection();
        loadCandidates();
    });

    $(document).on('change', '#mbr-orphan-orderby', function() {
        state.currentOrderby = $(this).val();
        state.currentPage = 1;
        loadCandidates();
    });

    $(document).on('click', '.mbr-orphan-page', function() {
        var page = parseInt($(this).data('page'), 10);
        if (page > 0) {
            state.currentPage = page;
            clearSelection();
            loadCandidates();
        }
    });

    $(document).on('change', '#mbr-orphan-select-all', function() {
        var checked = $(this).is(':checked');
        $('#mbr-orphan-tbody .mbr-orphan-row-cb:not(:disabled)').each(function() {
            var id = parseInt($(this).val(), 10);
            $(this).prop('checked', checked);
            if (checked) {
                state.selected[id] = true;
            } else {
                delete state.selected[id];
            }
        });
        updateBulkButton();
    });

    $(document).on('change', '.mbr-orphan-row-cb', function() {
        var id = parseInt($(this).val(), 10);
        if ($(this).is(':checked')) {
            state.selected[id] = true;
        } else {
            delete state.selected[id];
        }
        updateBulkButton();
    });

    $(document).on('click', '.mbr-orphan-delete-one', function() {
        var $row = $(this).closest('tr');
        deleteOne(parseInt($row.data('id'), 10), $row);
    });

    $(document).on('click', '.mbr-orphan-exclude-one', function() {
        var $row = $(this).closest('tr');
        excludeOne(parseInt($row.data('id'), 10), $row);
    });

    $(document).on('click', '#mbr-orphan-bulk-delete', bulkDelete);

    $(document).on('click', '.mbr-orphan-restore', function() {
        var $row = $(this).closest('tr');
        restoreOne(parseInt($row.data('row-id'), 10), $row);
    });

    // -----------------------------------------------------------------
    // Initial load
    // -----------------------------------------------------------------

    $(function() {
        if ($('#mbr-orphan-table').length) {
            loadCandidates();
            loadStaged();
        }
    });

})(jQuery);
