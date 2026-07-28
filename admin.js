jQuery(document).ready(function($) {

    // Initialise colour pickers.
    //
    // wpColorPicker()/Iris renders at zero width if its field is hidden at the
    // moment it initialises. The gradient fields live in a row that is hidden
    // until "Gradient" is chosen, so initialising them eagerly leaves them
    // broken when later revealed. Instead we initialise each picker lazily the
    // first time its field is visible, and again whenever the background type
    // changes and reveals new fields.
    function initColorPicker($field) {
        if (!$field.data('mbrCpDone')) {
            $field.data('mbrCpDone', true).wpColorPicker();
        }
    }

    function initVisibleColorPickers() {
        $('.color-picker:visible').each(function() {
            initColorPicker($(this));
        });
    }

    initVisibleColorPickers();

    // Redirect rules repeater (progressive enhancement; the form works without
    // it, one rule per save).
    var $redirectRows = $('#mbr-redirect-rows');
    if ($redirectRows.length) {
        var redirectIdx = $redirectRows.find('.mbr-redirect-row').length;
        $('#mbr-add-redirect').on('click', function(e) {
            e.preventDefault();
            var $row = $redirectRows.find('.mbr-redirect-row').first().clone();
            $row.find('input').val('');
            $row.find('select').prop('selectedIndex', 0);
            $row.find('select, input').each(function() {
                var name = $(this).attr('name');
                if (name) {
                    $(this).attr('name', name.replace(/\[\d+\]/, '[' + redirectIdx + ']'));
                }
            });
            $redirectRows.append($row);
            redirectIdx++;
        });
        $redirectRows.on('click', '.mbr-remove-redirect', function(e) {
            e.preventDefault();
            if ($redirectRows.find('.mbr-redirect-row').length > 1) {
                $(this).closest('.mbr-redirect-row').remove();
            } else {
                $(this).closest('.mbr-redirect-row').find('input').val('');
            }
        });
    }
    
    // Media uploader for logo
    var logoUploader;
    $('#upload_logo_button').on('click', function(e) {
        e.preventDefault();
        
        // If the uploader object has already been created, reopen the dialog
        if (logoUploader) {
            logoUploader.open();
            return;
        }
        
        // Create the media uploader
        logoUploader = wp.media({
            title: 'Choose Logo',
            button: {
                text: 'Use this logo'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });
        
        // When an image is selected, update the field and preview
        logoUploader.on('select', function() {
            var attachment = logoUploader.state().get('selection').first().toJSON();
            $('#mbr_custom_login_logo').val(attachment.url);
            
            // Update preview
            var preview = '<img src="' + attachment.url + '" style="max-width: 320px; max-height: 100px; display: block; margin-bottom: 10px;">';
            $('#logo-preview').html(preview);
            $('#remove_logo_button').show();
        });
        
        // Open the uploader dialog
        logoUploader.open();
    });
    
    // Remove logo button
    $('#remove_logo_button').on('click', function(e) {
        e.preventDefault();
        $('#mbr_custom_login_logo').val('');
        $('#logo-preview').html('');
        $(this).hide();
    });
    
    // Media uploader for background image
    var bgImageUploader;
    $('#upload_bg_image_button').on('click', function(e) {
        e.preventDefault();
        
        // If the uploader object has already been created, reopen the dialog
        if (bgImageUploader) {
            bgImageUploader.open();
            return;
        }
        
        // Create the media uploader
        bgImageUploader = wp.media({
            title: 'Choose Background Image',
            button: {
                text: 'Use this image'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });
        
        // When an image is selected, update the field and preview
        bgImageUploader.on('select', function() {
            var attachment = bgImageUploader.state().get('selection').first().toJSON();
            $('#mbr_custom_login_bg_image').val(attachment.url);
            
            // Update preview
            var preview = '<img src="' + attachment.url + '" style="max-width: 400px; max-height: 200px; display: block; margin-bottom: 10px;">';
            $('#bg-image-preview').html(preview);
            $('#remove_bg_image_button').show();
        });
        
        // Open the uploader dialog
        bgImageUploader.open();
    });
    
    // Remove background image button
    $('#remove_bg_image_button').on('click', function(e) {
        e.preventDefault();
        $('#mbr_custom_login_bg_image').val('');
        $('#bg-image-preview').html('');
        $(this).hide();
    });
    
    // Background type radio button handler
    $('.bg-type-radio').on('change', function() {
        var selectedType = $(this).val();
        
        // Hide all background option rows
        $('.bg-option').hide();
        
        // Show relevant rows based on selected type
        if (selectedType === 'color') {
            $('.bg-color').show();
        } else if (selectedType === 'gradient') {
            $('.bg-gradient').show();
        } else if (selectedType === 'image') {
            $('.bg-image').show();
        }

        // Now that new rows may be visible, initialise any colour pickers in
        // them (e.g. the gradient start/end fields on first reveal).
        initVisibleColorPickers();
    });
    
    // Generate emergency key button handler
    $(document).on('click', 'button[onclick*="mbr_custom_login_emergency_key"]', function(e) {
        // This is handled inline in the PHP, but we can enhance it
        setTimeout(function() {
            alert('New emergency key generated! Make sure to save your settings.');
        }, 100);
    });
});
