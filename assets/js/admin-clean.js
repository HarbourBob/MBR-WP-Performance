/**
 * MBR WP Performance Admin JavaScript - Clean Rebuild
 */

console.log('==========================================');
console.log('MBR WP Performance JS FILE IS LOADING...');
console.log('==========================================');

(function($) {
    'use strict';
    
    console.log('Inside IIFE - jQuery available:', typeof $ !== 'undefined');

    const MBR_WP_Performance_Admin = {
        
        /**
         * Initialize
         */
        init: function() {
            console.log('MBR_WP_Performance_Admin.init() called');
            this.bindEvents();
            console.log('MBR_WP_Performance_Admin.init() complete');
        },

        /**
         * Bind all events
         */
        bindEvents: function() {
            var self = this;
            
            // CRITICAL: Prevent form submission while debug message is showing
            $('.mbr-wp-performance-form').on('submit', function(e) {
                if (window.mbrDebugActive) {
                    e.preventDefault();
                    e.stopPropagation();
                    alert('Cannot save settings while debug message is displayed. Please read the message first.');
                    return false;
                }
            });
            
            // Reset to defaults
            $('.mbr-wp-performance-reset').on('click', function(e) { self.resetSettings.call(this, e); });
            
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
            $('#generate-critical-css').on('click', function(e) { self.generateCriticalCSS.call(this, e); });
            $('#scan-css').on('click', function(e) { self.scanCSS.call(this, e); });
            $('#clear-scan-data').on('click', function(e) { self.clearScanData.call(this, e); });
            
            // Font operations
            $('#download-manual-fonts').on('click', function(e) { self.downloadManualFonts.call(this, e); });
            $('#clear-font-cache').on('click', function(e) { self.clearFontCache.call(this, e); });
            
            // WebP Converter operations
            $('#mbr-webp-start-conversion').on('click', function(e) { self.webpStartConversion.call(this, e); });
            $('#mbr-webp-clear-history').on('click', function(e) { self.webpClearHistory.call(this, e); });
            $('#mbr-webp-apply-bulk').on('click', function(e) { self.webpApplyBulk.call(this, e); });
            $('#mbr-webp-revert-all').on('click', function(e) { self.webpRevertAll.call(this, e); });
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
            $button.after('<span class="mbr-wp-performance-loading"></span>');
        },

        /**
         * Hide loading spinner
         */
        hideLoading: function($button) {
            $button.prop('disabled', false);
            $button.next('.mbr-wp-performance-loading').remove();
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
         * Reset settings to defaults
         */
        resetSettings: function(e) {
            e.preventDefault();
            if (!confirm(mbrWpPerformance.i18n.confirmReset)) {
                return;
            }
            // Reset form - let WordPress handle this naturally
            window.location.href = window.location.href + '&reset=1';
        },

        /**
         * Clear font cache - TESTED AND WORKING
         */
        clearFontCache: function(e) {
            console.log('STEP 1: clearFontCache function called');
            e.preventDefault();
            e.stopPropagation();
            console.log('STEP 2: preventDefault called');
            
            // SET FLAG TO PREVENT FORM SUBMISSION
            window.mbrDebugActive = true;
            
            var $button = $(this);
            var $status = $('#clear-font-status');
            console.log('STEP 3: Button and status elements found');
            
            if (!confirm('Are you sure you want to delete ALL downloaded fonts and reset the configuration? This cannot be undone.')) {
                console.log('STEP 4: User cancelled');
                window.mbrDebugActive = false;
                return;
            }
            console.log('STEP 5: User confirmed, proceeding...');
            
            // PREVENT ANY PAGE RELOADS/NAVIGATION
            window.onbeforeunload = function() {
                console.log('BLOCKED: Page tried to reload/navigate!');
                return "Debug message is being displayed. Are you sure you want to leave?";
            };
            console.log('STEP 6: Reload blocker installed');
            
            $status.html('');
            var originalText = $button.text();
            $button.text('Clearing...').prop('disabled', true);
            console.log('STEP 7: Button updated, sending AJAX...');
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_clear_font_cache',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                console.log('STEP 8: AJAX response received');
                console.log('Response:', response);
                
                $button.text(originalText).prop('disabled', false);
                
                if (response.success) {
                    console.log('STEP 9: Success!');
                    console.log('=== CLEAR FONT CACHE DEBUG ===');
                    console.log(response.data.message);
                    console.log('=== END DEBUG ===');
                    
                    // Show in a BIG obvious div that can't be missed
                    $status.html('<div style="background: #d4edda; border: 3px solid #28a745; padding: 30px; margin: 20px 0; font-size: 14px;"><strong style="color: green; font-size: 18px;">✓ CACHE CLEARED!</strong><br><br><div style="font-family: monospace; white-space: pre-wrap; background: white; padding: 15px; border: 1px solid #ccc;">' + response.data.message + '</div><br><br><strong style="color: red; font-size: 16px;">⚠️ READ THE MESSAGE ABOVE - DO NOT RELOAD YET!</strong><br><br><button type="button" onclick="window.mbrDebugActive=false; window.onbeforeunload=null; location.reload();" class="button button-primary" style="font-size: 16px; padding: 10px 20px;">I Have Read It - Reload Page Now</button></div>');
                    
                    console.log('STEP 10: Message displayed, function complete');
                } else {
                    console.error('STEP 9: Error response:', response.data.message);
                    window.mbrDebugActive = false;
                    $status.html('<div style="background: #f8d7da; border: 2px solid #dc3545; padding: 20px;"><strong>Error:</strong> ' + (response.data.message || 'An error occurred') + '</div>');
                }
            }).fail(function(xhr, status, error) {
                console.error('STEP 8: AJAX FAILED');
                console.error('Status:', status);
                console.error('Error:', error);
                console.error('XHR:', xhr);
                
                window.mbrDebugActive = false;
                $button.text(originalText).prop('disabled', false);
                $status.html('<div style="background: #f8d7da; border: 2px solid #dc3545; padding: 20px;"><strong>AJAX Error:</strong> ' + error + '</div>');
            });
            
            console.log('STEP 11: AJAX call initiated, waiting for response...');
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
                MBR_WP_Performance_Admin.showMessage($status, 'Please enter fonts to download', 'error');
                return;
            }
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_download_manual_fonts',
                nonce: mbrWpPerformance.nonce,
                manual_fonts: manualFonts
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
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
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_clean_revisions',
                nonce: mbrWpPerformance.nonce,
                keep: $('#keep_revisions').val()
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
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
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_scan_post_meta',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, 'Found: ' + response.data.count + ' orphaned entries', 'success');
                    if (response.data.count > 0) {
                        $deleteButton.prop('disabled', false);
                    }
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
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
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_delete_post_meta',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'success');
                    $button.prop('disabled', true);
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
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
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_scan_comment_meta',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, 'Found: ' + response.data.count + ' orphaned entries', 'success');
                    if (response.data.count > 0) {
                        $deleteButton.prop('disabled', false);
                    }
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
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
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_delete_comment_meta',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'success');
                    $button.prop('disabled', true);
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
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
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_scan_relationships',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, 'Found: ' + response.data.count + ' orphaned entries', 'success');
                    if (response.data.count > 0) {
                        $deleteButton.prop('disabled', false);
                    }
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
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
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_delete_relationships',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'success');
                    $button.prop('disabled', true);
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
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
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_scan_term_meta',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, 'Found: ' + response.data.count + ' orphaned entries', 'success');
                    if (response.data.count > 0) {
                        $deleteButton.prop('disabled', false);
                    }
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
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
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_delete_term_meta',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'success');
                    $button.prop('disabled', true);
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
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
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_transient_stats',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
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
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_delete_expired_transients',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
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
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_delete_all_transients',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
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
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_optimize_tables',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
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
            
            MBR_WP_Performance_Admin.showLoading($button);
            $status.html('');
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_convert_innodb',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message || 'An error occurred', 'error');
                }
            }).fail(function(xhr, status, error) {
                MBR_WP_Performance_Admin.hideLoading($button);
                MBR_WP_Performance_Admin.showMessage($status, 'AJAX Error: ' + error, 'error');
            });
        },

        /**
         * Repair tables
         */
        repairTables: function(e) {
            e.preventDefault();
            var $button = $(this);
            var $status = $('#repair-status');
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_repair_tables',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
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
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_db_info',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    $status.html(response.data.html);
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * Generate critical CSS
         */
        generateCriticalCSS: function(e) {
            e.preventDefault();
            var $button = $(this);
            var $status = $('#critical-css-status');
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_generate_critical_css',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    $('#critical_css').val(response.data.css);
                    MBR_WP_Performance_Admin.showMessage($status, 'Critical CSS generated successfully', 'success');
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
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
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_scan_css',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    $status.html(response.data.html);
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
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
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_clear_scan_data',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
                }
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

            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_webp_get_images',
                nonce: mbrWpPerformance.nonce
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
                    $.post(mbrWpPerformance.ajaxUrl, {
                        action: 'mbr_wp_performance_webp_process_image',
                        nonce: mbrWpPerformance.nonce,
                        image_path: imagePath
                    })
                    .done(function(resp) {
                        if (resp && resp.success) {
                            var d = resp.data || {};
                            var filename = (d.original_path || imagePath).split('/').pop();
                            var fullUrl  = mbrWpPerformance.uploadUrl + '/' + (d.original_path || imagePath);
                            var row = '<tr>' +
                                '<th scope="row" class="check-column"><input type="checkbox" class="mbr-webp-item-checkbox" value="' + imagePath + '"></th>' +
                                '<td><a href="' + fullUrl + '" target="_blank">' + filename + '</a></td>' +
                                '<td>' + (d.original_size || '') + '</td>' +
                                '<td>' + (d.webp_size || '') + '</td>' +
                                '<td>' + (d.compression || '') + '</td>' +
                                '</tr>';
                            $list.prepend(row);
                            $status.text('Converted: ' + (d.original_path || imagePath));
                        } else {
                            var msg = (resp && resp.data) ? resp.data : 'Unknown error';
                            $list.prepend('<tr><th scope="row" class="check-column"></th><td colspan="4" style="color: var(--mbr-danger);">Failed: ' + imagePath + ' — ' + msg + '</td></tr>');
                        }
                    })
                    .fail(function() {
                        $list.prepend('<tr><th scope="row" class="check-column"></th><td colspan="4" style="color: var(--mbr-danger);">AJAX error processing ' + imagePath + '</td></tr>');
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

            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_webp_clear_history',
                nonce: mbrWpPerformance.nonce
            })
            .done(function(response) {
                if (response && response.success) {
                    $status.text(response.data || 'History cleared.');
                    $('#mbr-webp-history-list').html('<tr><td colspan="5">No images converted yet.</td></tr>');
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

            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_webp_bulk_delete',
                nonce: mbrWpPerformance.nonce,
                items: items
            })
            .done(function(response) {
                if (response && response.success) {
                    $status.text(response.data);
                    // Remove the rows from the table.
                    $('.mbr-webp-item-checkbox:checked').closest('tr').remove();
                    if ($('#mbr-webp-history-list tr').length === 0) {
                        $('#mbr-webp-history-list').html('<tr><td colspan="5">No images converted yet.</td></tr>');
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

            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_webp_revert_all',
                nonce: mbrWpPerformance.nonce
            })
            .done(function(response) {
                if (response && response.success) {
                    $status.text(response.data || 'Revert complete.');
                    $('#mbr-webp-history-list').html('<tr><td colspan="5">No images converted yet.</td></tr>');
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

            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_image_dimensions_scan',
                nonce: mbrWpPerformance.nonce
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
                $.post(mbrWpPerformance.ajaxUrl, {
                    action: 'mbr_wp_performance_image_dimensions_resize',
                    nonce: mbrWpPerformance.nonce,
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

            MBR_WP_Performance_Admin.showLoading($button);

            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_wc_clear_sessions',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
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

            MBR_WP_Performance_Admin.showLoading($button);

            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_wc_clear_transients',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
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

            MBR_WP_Performance_Admin.showLoading($button);

            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_wc_cleanup_as',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        }
    };

    // Initialize on document ready
    console.log('Registering document.ready handler...');
    $(document).ready(function() {
        console.log('DOCUMENT READY FIRED!');
        MBR_WP_Performance_Admin.init();
        console.log('After init call');
    });
    
    console.log('End of IIFE');

})(jQuery);

console.log('==========================================');
console.log('MBR WP Performance JS FILE LOADED!');
console.log('==========================================');
