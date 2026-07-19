/**
 * Settings Page JavaScript
 * Handles tab switching and layout selection
 */

(function($) {
    'use strict';
    
    
    $(document).ready(function() {
        
        
        // Tab switching
        $('.mbr-cc-tab-button').on('click', function(e) {
            e.preventDefault();
            var tab = $(this).data('tab');
            
            // Update buttons
            $('.mbr-cc-tab-button').removeClass('active');
            $(this).addClass('active');
            
            // Update content
            $('.mbr-cc-tab-content').removeClass('active');
            $('#tab-' + tab).addClass('active');
            
            // Remember the active tab so a settings save (which reloads the
            // page) returns the user to the tab they were on, not tab 1.
            try {
                sessionStorage.setItem('mbrCcActiveTab', tab);
            } catch (err) {
                // sessionStorage unavailable (private mode etc.) — degrade
                // gracefully; the page will simply reopen on the first tab.
            }
        });
        
        // Restore the last active tab after a save/reload. Falls back to the
        // default (first) tab if the stored tab no longer exists.
        try {
            var storedTab = sessionStorage.getItem('mbrCcActiveTab');
            if (storedTab) {
                var $btn = $('.mbr-cc-tab-button[data-tab="' + storedTab + '"]');
                if ($btn.length && $('#tab-' + storedTab).length) {
                    $('.mbr-cc-tab-button').removeClass('active');
                    $btn.addClass('active');
                    $('.mbr-cc-tab-content').removeClass('active');
                    $('#tab-' + storedTab).addClass('active');
                }
            }
        } catch (err) {
            // Ignore — default tab remains active.
        }
        
        // Layout selection
        $('input[name="mbr_cc_layout_option"]').on('change', function() {
            var value = $(this).val();
            var parts = value.split('-');
            
            if (value === 'popup') {
                $('#banner_layout').val('popup');
                $('#banner_position').val('bottom');
            } else if (value.startsWith('bar-')) {
                $('#banner_layout').val('bar');
                $('#banner_position').val(parts[1]);
            } else {
                $('#banner_layout').val(value);
                $('#banner_position').val('bottom');
            }
            
            // Update visual selection
            $('.mbr-cc-layout-card').removeClass('selected');
            $(this).closest('.mbr-cc-layout-card').addClass('selected');
        });
        
        // Logo upload
        $('.mbr-cc-upload-logo').on('click', function(e) {
            e.preventDefault();
            
            if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
                alert('WordPress media library not loaded.');
                return;
            }
            
            var mediaUploader = wp.media({
                title: 'Select Logo',
                button: {
                    text: 'Use this logo'
                },
                multiple: false
            });
            
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#banner_logo_url').val(attachment.url);
                
                if ($('.mbr-cc-logo-preview').length) {
                    $('.mbr-cc-logo-preview img').attr('src', attachment.url);
                } else {
                    $('#banner_logo_url').after('<div class="mbr-cc-logo-preview"><img src="' + attachment.url + '" alt="Logo Preview"></div>');
                }
            });
            
            mediaUploader.open();
        });
        
    });
    
})(jQuery);
