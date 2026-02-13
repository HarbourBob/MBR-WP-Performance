/**
 * MBR WP Performance Admin JavaScript - TEST VERSION
 */

console.log('TEST VERSION LOADED');

jQuery(document).ready(function($) {
    console.log('TEST: Document ready');
    
    var $clearButton = $('#clear-font-cache');
    console.log('TEST: Clear button exists?', $clearButton.length);
    console.log('TEST: Button element:', $clearButton[0]);
    
    // Try multiple binding methods
    
    // Method 1: Direct click
    $clearButton.on('click', function(e) {
        console.log('TEST: Method 1 - Click detected!');
        handleClearCache(e, this);
    });
    
    // Method 2: Event delegation from document
    $(document).on('click', '#clear-font-cache', function(e) {
        console.log('TEST: Method 2 - Delegated click detected!');
        handleClearCache(e, this);
    });
    
    // Method 3: Native addEventListener
    if ($clearButton[0]) {
        $clearButton[0].addEventListener('click', function(e) {
            console.log('TEST: Method 3 - Native click detected!');
            handleClearCache(e, this);
        });
    }
    
    function handleClearCache(e, button) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('TEST: handleClearCache called');
        alert('Button clicked! Check console for details.');
        
        var $button = $(button);
        var originalText = $button.text();
        
        $button.text('Clearing...').prop('disabled', true);
        
        $.post(mbrWpPerformance.ajaxUrl, {
            action: 'mbr_wp_performance_clear_font_cache',
            nonce: mbrWpPerformance.nonce
        }, function(response) {
            console.log('TEST: Response received', response);
            $button.text(originalText).prop('disabled', false);
            
            if (response.success) {
                alert('Success: ' + response.data.message);
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                alert('Error: ' + (response.data.message || 'Unknown error'));
            }
        }).fail(function(xhr, status, error) {
            console.error('TEST: AJAX failed', status, error);
            $button.text(originalText).prop('disabled', false);
            alert('AJAX Error: ' + error);
        });
    }
});
