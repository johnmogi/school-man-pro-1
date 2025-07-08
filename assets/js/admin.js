/**
 * School Manager Pro - Admin JavaScript
 */
(function($) {
    'use strict';

    // Document ready
    $(function() {
        // Initialize any datepickers
        $('.smp-datepicker').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true
        });

        // Handle form submissions
        $(document).on('submit', '.smp-form', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            const originalText = $submitBtn.text();
            
            // Show loading state
            $submitBtn.prop('disabled', true).text(smp_admin.saving_text || 'Saving...');
            
            // Submit form via AJAX
            $.ajax({
                url: smp_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'smp_handle_form',
                    nonce: smp_admin.nonce,
                    form_data: $form.serialize()
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        showNotice('success', response.data.message);
                        
                        // Redirect if needed
                        if (response.data.redirect) {
                            window.location.href = response.data.redirect;
                        }
                        
                        // Reload page if needed
                        if (response.data.reload) {
                            window.location.reload();
                        }
                    } else {
                        // Show error message
                        showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    showNotice('error', smp_admin.error_message || 'An error occurred. Please try again.');
                },
                complete: function() {
                    // Reset button state
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        });
        
        // Handle delete actions
        $(document).on('click', '.smp-delete', function(e) {
            e.preventDefault();
            
            if (confirm(smp_admin.confirm_delete || 'Are you sure you want to delete this item?')) {
                const $button = $(this);
                const itemId = $button.data('id');
                const itemType = $button.data('type');
                
                // Show loading state
                $button.prop('disabled', true).text(smp_admin.deleting_text || 'Deleting...');
                
                // Send delete request
                $.ajax({
                    url: smp_admin.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'smp_delete_item',
                        nonce: smp_admin.nonce,
                        id: itemId,
                        type: itemType
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotice('success', response.data.message);
                            // Remove the row from the table
                            $button.closest('tr').fadeOut(300, function() {
                                $(this).remove();
                            });
                        } else {
                            showNotice('error', response.data.message);
                        }
                    },
                    error: function() {
                        showNotice('error', smp_admin.error_message || 'An error occurred. Please try again.');
                    },
                    complete: function() {
                        $button.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span>');
                    }
                });
            }
        });
    });
    
    /**
     * Show a notice message
     */
    function showNotice(type, message) {
        // Remove any existing notices
        $('.smp-notice').remove();
        
        // Create notice element
        const noticeClass = 'notice-' + (type === 'error' ? 'error' : 'success');
        const $notice = $('<div>', {
            'class': 'notice ' + noticeClass + ' is-dismissible smp-notice',
            html: '<p>' + message + '</p>'
        });
        
        // Add dismiss button
        $notice.append('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>');
        
        // Insert after the first h1 or h2
        $('h1:first, h2:first').after($notice);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $notice.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
})(jQuery);
