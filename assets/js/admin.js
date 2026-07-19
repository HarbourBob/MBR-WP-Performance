(function($) {
    'use strict';
    
    var MbrCcAdmin = {
        
        init: function() {
            this.initColorPickers();
            this.bindEvents();
        },
        
        initColorPickers: function() {
            if ($.fn.wpColorPicker) {
                $('.mbr-cc-color-picker').wpColorPicker();
            }
        },
        
        bindEvents: function() {
            var self = this;
            
            // Generate cookie policy
            $('#mbr-cc-generate-policy').on('click', function(e) {
                e.preventDefault();
                self.generatePolicy();
            });
            
            // Generate privacy policy
            $('#mbr-cc-generate-privacy-policy').on('click', function(e) {
                e.preventDefault();
                self.generatePrivacyPolicy();
            });
            
            // Save settings
            $('#mbr-cc-save-settings').on('click', function(e) {
                e.preventDefault();
                self.saveSettings();
            });
            
            // Toggle scan type options
            $('input[name="scan_type"]').on('change', function() {
                if ($(this).val() === 'site-wide') {
                    $('#mbr-cc-single-scan-options').hide();
                    $('#mbr-cc-site-wide-info').show();
                } else {
                    $('#mbr-cc-single-scan-options').show();
                    $('#mbr-cc-site-wide-info').hide();
                }
            });
            
            // Start cookie scan
            $('#mbr-cc-start-scan').on('click', function(e) {
                e.preventDefault();
                self.startScan();
            });
            
            // Add scanned script to blocked list
            $(document).on('click', '.mbr-cc-add-script', function(e) {
                e.preventDefault();
                var data = $(this).data();
                self.addBlockedScript(data);
            });
            
            // Add custom blocked script
            $('#mbr-cc-add-blocked-script-form').on('submit', function(e) {
                e.preventDefault();
                self.addCustomScript();
            });
            
            // Remove blocked script
            $(document).on('click', '.mbr-cc-remove-script', function(e) {
                e.preventDefault();
                if (confirm(mbrCcAdmin.confirmDelete)) {
                    var index = $(this).data('index');
                    var $item = $(this).closest('.mbr-cc-script-item');
                    self.removeBlockedScript(index, $item);
                }
            });
            
            // Generate policy page
            $('#mbr-cc-generate-policy').on('click', function(e) {
                e.preventDefault();
                self.generatePolicy();
            });
            
            // Export logs
            $('#mbr-cc-export-logs').on('click', function(e) {
                e.preventDefault();
                self.exportLogs();
            });
            
            // Delete old logs
            $('#mbr-cc-delete-old-logs').on('click', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to delete old logs?')) {
                    self.deleteOldLogs();
                }
            });

            // Form integration — save settings
            $('#mbr-cc-form-save').on('click', function(e) {
                e.preventDefault();
                self.saveFormSettings();
            });

            // A/B testing — save enabled state
            $('#mbr-cc-ab-save-enabled').on('click', function(e) {
                e.preventDefault();
                self.saveAbEnabled();
            });

            // A/B testing — promote winner
            $(document).on('click', '.mbr-cc-ab-promote', function(e) {
                e.preventDefault();
                var label = $(this).data('label');
                if (confirm('Promote ' + label + ' to the live banner position? A/B testing will be disabled.')) {
                    self.promoteAbWinner();
                }
            });

            // A/B testing — reset stats
            $('#mbr-cc-ab-reset').on('click', function(e) {
                e.preventDefault();
                if (confirm('Reset all A/B test stats? This cannot be undone.')) {
                    self.resetAbStats();
                }
            });
            
            // Update categories
            $('#mbr-cc-save-categories').on('click', function(e) {
                e.preventDefault();
                self.saveCategories();
            });
        },
        
        saveSettings: function() {
            var self = this;
            var $button = $('#mbr-cc-save-settings');
            var settings = {};
            
            // Gather all settings
            $('[name^="mbr_cc_"]').each(function() {
                var $field = $(this);
                var name = $field.attr('name').replace('mbr_cc_', '');
                var value;
                
                if ($field.is(':checkbox')) {
                    value = $field.is(':checked');
                } else {
                    value = $field.val();
                }
                
                settings[name] = value;
            });
            
            $button.prop('disabled', true).text('Saving...');
            
            $.ajax({
                url: mbrCcAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mbr_cc_save_settings',
                    nonce: mbrCcAdmin.nonce,
                    settings: settings
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        var $notice = $('<div class="notice notice-success is-dismissible"><p>Settings saved successfully. Reloading...</p></div>');
                        $('.wrap > h1').after($notice);
                        
                        // Reload page after 1 second
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        var $notice = $('<div class="notice notice-error is-dismissible"><p>Failed to save settings.</p></div>');
                        $('.wrap > h1').after($notice);
                        $button.prop('disabled', false).text('Save Settings');
                    }
                },
                error: function() {
                    var $notice = $('<div class="notice notice-error is-dismissible"><p>An error occurred.</p></div>');
                    $('.wrap > h1').after($notice);
                    $button.prop('disabled', false).text('Save Settings');
                }
            });
        },
        
        startScan: function() {
            var self = this;
            var $button = $('#mbr-cc-start-scan');
            var $results = $('#mbr-cc-scan-results');
            var $progress = $('#mbr-cc-scan-progress');
            var scanType = $('input[name="scan_type"]:checked').val();
            var url = $('#mbr-cc-scan-url').val() || window.location.origin;
            
            $button.prop('disabled', true).text('Scanning...');
            $results.html('');
            
            if (scanType === 'site-wide') {
                $progress.show();
                $('#mbr-cc-progress-text').text('Scanning your website...');
                $('#mbr-cc-progress-bar').css('width', '10%');
            } else {
                $results.html('<p>Scanning page...</p>');
            }
            
            $.ajax({
                url: mbrCcAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mbr_cc_scan_cookies',
                    nonce: mbrCcAdmin.nonce,
                    scan_type: scanType,
                    url: url
                },
                success: function(response) {
                    if (response.success && response.data) {
                        if (scanType === 'site-wide') {
                            $('#mbr-cc-progress-bar').css('width', '100%');
                            $('#mbr-cc-progress-text').text('Scan complete!');
                            setTimeout(function() {
                                $progress.hide();
                                self.displayCategorizedResults(response.data);
                            }, 500);
                        } else {
                            self.displaySinglePageResults(response.data);
                        }
                    } else {
                        var errorMsg = response.data && response.data.message ? response.data.message : 'Scan failed. Please try again.';
                        $results.html('<p class="error">' + errorMsg + '</p>');
                        $progress.hide();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Scanner error:', xhr.responseText);
                    $results.html('<p class="error">Scan failed. Error: ' + error + '. Please check the browser console for details.</p>');
                    $progress.hide();
                },
                complete: function() {
                    $button.prop('disabled', false).text('Start Scan');
                }
            });
        },
        
        displaySinglePageResults: function(data) {
            var $results = $('#mbr-cc-scan-results');
            var html = '<h3>Scan Results (' + data.count + ' items found)</h3>';
            
            if (data.scripts && data.scripts.length > 0) {
                html += '<h4>Scripts</h4><table class="widefat"><thead><tr><th>Name</th><th>Type</th><th>Category</th><th>Action</th></tr></thead><tbody>';
                
                $.each(data.scripts, function(i, script) {
                    html += '<tr>';
                    html += '<td>' + script.name + '</td>';
                    html += '<td>' + script.type + '</td>';
                    html += '<td>' + script.category + '</td>';
                    html += '<td><button class="button mbr-cc-add-script" data-name="' + script.name + '" data-identifier="' + script.identifier + '" data-type="' + script.type + '" data-category="' + script.category + '">Add to Blocked List</button></td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
            }
            
            if (data.iframes && data.iframes.length > 0) {
                html += '<h4>Iframes</h4><table class="widefat"><thead><tr><th>Name</th><th>Category</th><th>Action</th></tr></thead><tbody>';
                
                $.each(data.iframes, function(i, iframe) {
                    html += '<tr>';
                    html += '<td>' + iframe.name + '</td>';
                    html += '<td>' + iframe.category + '</td>';
                    html += '<td><button class="button mbr-cc-add-script" data-name="' + iframe.name + '" data-identifier="' + iframe.identifier + '" data-type="iframe" data-category="' + iframe.category + '">Add to Blocked List</button></td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
            }
            
            if (data.count === 0) {
                html += '<p>No scripts or iframes found on the scanned page.</p>';
            }
            
            $results.html(html);
        },
        
        displayCategorizedResults: function(data) {
            var $results = $('#mbr-cc-scan-results');
            var html = '<div class="mbr-cc-scan-summary" style="background: #fff; padding: 20px; margin: 20px 0; border-left: 4px solid #00a32a;">';
            html += '<h3>✓ Site-Wide Scan Complete</h3>';
            html += '<p><strong>' + data.count + ' unique scripts/iframes found</strong> across ' + data.pages_scanned + ' pages</p>';
            html += '</div>';
            
            var categories = data.by_category;
            var categoryNames = {
                'necessary': 'Necessary',
                'analytics': 'Analytics', 
                'marketing': 'Marketing',
                'preferences': 'Preferences'
            };
            
            var categoryColors = {
                'necessary': '#00a32a',
                'analytics': '#0073aa',
                'marketing': '#d63638',
                'preferences': '#f0a500'
            };
            
            $.each(categoryNames, function(slug, name) {
                if (!categories[slug] || categories[slug].length === 0) {
                    return;
                }
                
                var color = categoryColors[slug];
                var items = categories[slug];
                
                html += '<div class="mbr-cc-category-results" style="margin: 20px 0; border-left: 4px solid ' + color + '; background: #fff; padding: 15px;">';
                html += '<h4 style="margin-top: 0; color: ' + color + ';">' + name + ' (' + items.length + ')</h4>';
                html += '<table class="widefat"><thead><tr><th style="width: 30%;">Name</th><th style="width: 15%;">Type</th><th style="width: 35%;">Found On</th><th style="width: 20%;">Action</th></tr></thead><tbody>';
                
                $.each(items, function(i, item) {
                    var foundOnText = item.found_on ? item.found_on.length + ' page(s)' : '1 page';
                    var foundOnTitle = item.found_on ? item.found_on.slice(0, 5).join('\n') : '';
                    if (item.found_on && item.found_on.length > 5) {
                        foundOnTitle += '\n... and ' + (item.found_on.length - 5) + ' more';
                    }
                    
                    html += '<tr>';
                    html += '<td><strong>' + item.name + '</strong><br><small style="color: #666;">' + item.identifier.substring(0, 50) + (item.identifier.length > 50 ? '...' : '') + '</small></td>';
                    html += '<td>' + item.type + '</td>';
                    html += '<td title="' + foundOnTitle + '">' + foundOnText + '</td>';
                    html += '<td><button class="button button-small mbr-cc-add-script" data-name="' + item.name + '" data-identifier="' + item.identifier + '" data-type="' + item.type + '" data-category="' + slug + '">Add to Blocked</button></td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table></div>';
            });
            
            if (data.count === 0) {
                html += '<p>No scripts or iframes found on your website.</p>';
            }
            
            $results.html(html);
        },
        
        addBlockedScript: function(data) {
            var self = this;
            var $button = $('.mbr-cc-add-script[data-identifier="' + data.identifier + '"]');
            
            // Disable button and show loading state
            $button.prop('disabled', true).text('Adding...');
            
            $.ajax({
                url: mbrCcAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mbr_cc_add_blocked_script',
                    nonce: mbrCcAdmin.nonce,
                    name: data.name,
                    identifier: data.identifier,
                    type: data.type,
                    category: data.category
                },
                success: function(response) {
                    if (response.success) {
                        // Change button to success state
                        $button.text('✓ Added').css({
                            'background': '#00a32a',
                            'color': '#fff',
                            'border-color': '#00a32a'
                        });
                        
                        // Add to blocked scripts list
                        self.addToBlockedList(data);
                        
                        // Show success notice at top
                        var $notice = $('<div class="notice notice-success is-dismissible"><p><strong>' + data.name + '</strong> has been added to the blocked scripts list.</p></div>');
                        $('.wrap > h1').after($notice);
                        
                        // Auto-dismiss notice after 3 seconds
                        setTimeout(function() {
                            $notice.fadeOut(function() {
                                $(this).remove();
                            });
                        }, 3000);
                    } else {
                        // Show error
                        $button.prop('disabled', false).text('Add to Blocked');
                        var $notice = $('<div class="notice notice-error is-dismissible"><p>Failed to add script: ' + (response.data ? response.data.message : 'Unknown error') + '</p></div>');
                        $('.wrap > h1').after($notice);
                    }
                },
                error: function() {
                    $button.prop('disabled', false).text('Add to Blocked');
                    var $notice = $('<div class="notice notice-error is-dismissible"><p>An error occurred while adding the script.</p></div>');
                    $('.wrap > h1').after($notice);
                }
            });
        },
        
        addToBlockedList: function(script) {
            console.log('addToBlockedList called with:', script);
            
            // Find the blocked scripts section - try multiple selectors
            var $blockedSection = $('.mbr-cc-blocked-scripts');
            console.log('Found blocked section:', $blockedSection.length);
            
            if ($blockedSection.length === 0) {
                console.log('Blocked section not found, creating it...');
                
                // Section doesn't exist, create it after the manual add form
                var sectionHtml = '<div class="mbr-cc-settings-section mbr-cc-blocked-scripts">';
                sectionHtml += '<h2>Currently Blocked Scripts</h2>';
                sectionHtml += '</div>';
                
                // Find the manual add script section and insert after it
                var $manualSection = $('#mbr-cc-add-blocked-script-form').closest('.mbr-cc-settings-section');
                if ($manualSection.length > 0) {
                    $manualSection.after(sectionHtml);
                } else {
                    // Fallback: add at the end
                    $('.mbr-cc-admin-wrap').append(sectionHtml);
                }
                
                $blockedSection = $('.mbr-cc-blocked-scripts');
                console.log('Created blocked section:', $blockedSection.length);
            }
            
            // Get the current number of blocked scripts to use as index
            var currentCount = $blockedSection.find('.mbr-cc-script-item').length;
            console.log('Current blocked script count:', currentCount);
            
            // Create new script item HTML
            var scriptHtml = '<div class="mbr-cc-script-item" data-index="' + currentCount + '" data-identifier="' + script.identifier + '">';
            scriptHtml += '<div class="mbr-cc-script-info">';
            scriptHtml += '<h4>' + script.name + '</h4>';
            scriptHtml += '<p><strong>Type:</strong> ' + script.type + '</p>';
            scriptHtml += '<p><strong>Category:</strong> ' + script.category + '</p>';
            scriptHtml += '<p class="mbr-cc-script-meta"><code>' + script.identifier.substring(0, 80);
            if (script.identifier.length > 80) {
                scriptHtml += '...';
            }
            scriptHtml += '</code></p>';
            if (script.description) {
                scriptHtml += '<p>' + script.description + '</p>';
            }
            scriptHtml += '</div>';
            scriptHtml += '<div class="mbr-cc-script-actions">';
            scriptHtml += '<button type="button" class="button mbr-cc-remove-script" data-index="' + currentCount + '">Remove</button>';
            scriptHtml += '</div>';
            scriptHtml += '</div>';
            
            console.log('Creating new script item HTML');
            
            // Add highlight animation
            var $newItem = $(scriptHtml);
            $newItem.css({
                'background': '#d7ffd9',
                'transition': 'background 2s ease'
            });
            
            // Append to blocked scripts section
            $blockedSection.append($newItem);
            console.log('Appended new item to blocked section');
            
            // Fade out highlight after a moment
            setTimeout(function() {
                $newItem.css('background', '#fff');
            }, 500);
            
            // Scroll to the new item
            $('html, body').animate({
                scrollTop: $newItem.offset().top - 100
            }, 500);
            
            console.log('addToBlockedList complete');
        },
        
        addCustomScript: function() {
            var self = this;
            var $form = $('#mbr-cc-add-blocked-script-form');
            
            var data = {
                action: 'mbr_cc_add_blocked_script',
                nonce: mbrCcAdmin.nonce,
                name: $form.find('[name="script_name"]').val(),
                identifier: $form.find('[name="script_identifier"]').val(),
                type: $form.find('[name="script_type"]').val(),
                category: $form.find('[name="script_category"]').val(),
                description: $form.find('[name="script_description"]').val()
            };
            
            $.ajax({
                url: mbrCcAdmin.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        self.showNotice('Script added successfully.', 'success');
                        $form[0].reset();
                        location.reload();
                    } else {
                        self.showNotice(response.data.message, 'error');
                    }
                }
            });
        },
        
        removeBlockedScript: function(index, $item) {
            var self = this;
            
            $.ajax({
                url: mbrCcAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mbr_cc_remove_blocked_script',
                    nonce: mbrCcAdmin.nonce,
                    index: index
                },
                success: function(response) {
                    if (response.success) {
                        // Fade out and remove the item
                        $item.fadeOut(300, function() {
                            $(this).remove();
                            
                            var $blockedSection = $('.mbr-cc-blocked-scripts');
                            var $remaining = $blockedSection.find('.mbr-cc-script-item');

                            if ($remaining.length === 0) {
                                // No scripts left — remove the whole section.
                                $blockedSection.remove();
                            } else {
                                // Re-index remaining remove buttons to match the
                                // server-side array_values() re-index after removal.
                                $remaining.each(function(newIndex) {
                                    $(this).find('.mbr-cc-remove-script').data('index', newIndex);
                                });
                            }
                        });
                        
                        // Show success notice
                        var $notice = $('<div class="notice notice-success is-dismissible"><p>Script removed from blocked list.</p></div>');
                        $('.wrap > h1').after($notice);
                        
                        setTimeout(function() {
                            $notice.fadeOut(function() {
                                $(this).remove();
                            });
                        }, 3000);
                    } else {
                        var $notice = $('<div class="notice notice-error is-dismissible"><p>Failed to remove script.</p></div>');
                        $('.wrap > h1').after($notice);
                    }
                }
            });
        },
        
        generatePolicy: function() {
            var self = this;
            var $button = $('#mbr-cc-generate-policy');
            
            $button.prop('disabled', true).text('Generating...');
            
            $.ajax({
                url: mbrCcAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mbr_cc_generate_policy',
                    nonce: mbrCcAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice('Cookie policy page created! <a href="' + response.data.edit_link + '">Edit page</a>', 'success');
                    } else {
                        self.showNotice(response.data.message, 'error');
                    }
                },
                complete: function() {
                    $button.prop('disabled', false).text('Generate Cookie Policy Page');
                }
            });
        },
        
        exportLogs: function() {
            var dateFrom = $('#mbr-cc-export-date-from').val();
            var dateTo = $('#mbr-cc-export-date-to').val();
            
            var url = mbrCcAdmin.ajaxUrl + '?action=mbr_cc_export_logs&nonce=' + mbrCcAdmin.nonce;
            
            if (dateFrom) {
                url += '&date_from=' + dateFrom;
            }
            
            if (dateTo) {
                url += '&date_to=' + dateTo;
            }
            
            window.location.href = url;
        },
        
        deleteOldLogs: function() {
            var self = this;
            var days = parseInt($('#mbr-cc-delete-logs-days').val(), 10);
            
            // Guard: '0' is a truthy string, so a plain `val() || 365`
            // would let 0 through and delete EVERY log. Require >= 1.
            if (isNaN(days) || days < 1) {
                self.showNotice('Please enter a number of days (1 or more).', 'error');
                return;
            }
            
            if (!window.confirm('Permanently delete all consent logs older than ' + days + ' days? This cannot be undone.')) {
                return;
            }
            
            $.ajax({
                url: mbrCcAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mbr_cc_delete_logs',
                    nonce: mbrCcAdmin.nonce,
                    days: days
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice(response.data.message, 'success');
                        location.reload();
                    } else {
                        self.showNotice(response.data.message, 'error');
                    }
                }
            });
        },

        saveFormSettings: function() {
            var self = this;
            $.ajax({
                url: mbrCcAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mbr_cc_save_form_settings',
                    nonce: mbrCcAdmin.nonce,
                    enabled: $('#mbr-cc-form-enabled').is(':checked') ? 1 : 0,
                    message: $('#mbr-cc-form-message').val(),
                    category: $('#mbr-cc-form-required-category').val()
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice(response.data.message, 'success');
                    } else {
                        self.showNotice(response.data.message, 'error');
                    }
                }
            });
        },

        saveAbEnabled: function() {
            var self = this;
            $.ajax({
                url: mbrCcAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mbr_cc_save_ab_enabled',
                    nonce: mbrCcAdmin.nonce,
                    enabled: $('#mbr-cc-ab-enabled').is(':checked') ? 1 : 0
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice(response.data.message, 'success');
                    } else {
                        self.showNotice(response.data.message, 'error');
                    }
                }
            });
        },

        promoteAbWinner: function() {
            var self = this;
            $.ajax({
                url: mbrCcAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mbr_cc_ab_promote_winner',
                    nonce: mbrCcAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice(response.data.message, 'success');
                        setTimeout(function() { location.reload(); }, 1500);
                    } else {
                        self.showNotice(response.data.message, 'error');
                    }
                }
            });
        },

        resetAbStats: function() {
            var self = this;
            $.ajax({
                url: mbrCcAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mbr_cc_ab_reset_stats',
                    nonce: mbrCcAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice(response.data.message, 'success');
                        setTimeout(function() { location.reload(); }, 1000);
                    } else {
                        self.showNotice(response.data.message, 'error');
                    }
                }
            });
        },
        
        saveCategories: function() {
            var self = this;
            var categories = {};
            
            $('.mbr-cc-category-item').each(function() {
                var $item = $(this);
                var slug = $item.data('slug');
                
                categories[slug] = {
                    name: $item.find('.category-name').val(),
                    description: $item.find('.category-description').val(),
                    required: $item.find('.category-required').is(':checked'),
                    enabled: $item.find('.category-enabled').is(':checked')
                };
            });
            
            $.ajax({
                url: mbrCcAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mbr_cc_update_categories',
                    nonce: mbrCcAdmin.nonce,
                    categories: categories
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice('Categories updated successfully.', 'success');
                    } else {
                        self.showNotice(response.data.message, 'error');
                    }
                }
            });
        },
        
        
        generatePolicy: function() {
            var self = this;
            
            if (!confirm('This will create a new Cookie Policy page in draft status. Continue?')) {
                return;
            }
            
            $.ajax({
                url: mbrCcAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mbr_cc_generate_policy',
                    nonce: mbrCcAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice('Cookie Policy page created successfully!', 'success');
                        setTimeout(function() {
                            window.location.href = response.data.edit_link;
                        }, 1500);
                    } else {
                        self.showNotice(response.data.message, 'error');
                    }
                }
            });
        },
        
        generatePrivacyPolicy: function() {
            var self = this;
            
            if (!confirm('This will create a comprehensive Privacy Policy page based on your site configuration. The page will be created in draft status for you to review. Continue?')) {
                return;
            }
            
            $.ajax({
                url: mbrCcAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mbr_cc_generate_privacy_policy',
                    nonce: mbrCcAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice('Privacy Policy page created successfully! Please review before publishing.', 'success');
                        setTimeout(function() {
                            window.location.href = response.data.edit_link;
                        }, 1500);
                    } else {
                        self.showNotice(response.data.message, 'error');
                    }
                }
            });
        },
        
        showNotice: function(message, type) {
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.wrap > h1').after($notice);
            
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        MbrCcAdmin.init();
    });
    
})(jQuery);
