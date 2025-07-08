/**
 * School Manager Pro - Admin JavaScript
 * Handles UI interactions for the admin area
 */

jQuery(document).ready(function($) {
    'use strict';

    // Debug logging to verify script loading
    console.log('SMP Admin JS loaded');
    
    // Handle the "New" button click in the students list
    $(document).on('click', '.page-title-action', function(e) {
        // Check if this is the Add New button for students
        if (window.location.href.indexOf('page=school-manager-students') > -1) {
            console.log('Add New student button clicked');
            e.preventDefault(); // Prevent the default page reload
            
            // Get the href attribute to extract action
            var href = $(this).attr('href');
            
            // If this is the Add New button
            if (href && href.indexOf('action=add') > -1) {
                console.log('Handling Add New student action via AJAX');
                
                // Show loading indicator
                $('body').append('<div id="smp-loading" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,0.8);z-index:9999;display:flex;justify-content:center;align-items:center;"><div style="text-align:center;"><span class="spinner is-active" style="float:none;width:20px;height:20px;margin:0 auto 10px;"></span><p>Loading form...</p></div></div>');
                
                // Make AJAX request to load the form
                $.ajax({
                    url: href,
                    type: 'GET',
                    success: function(response) {
                        console.log('Successfully retrieved student form');
                        
                        // Debug the response
                        console.log('Response length:', response.length);
                        
                        // Create a temporary div to parse the HTML response
                        var $tempDiv = $('<div></div>').html(response);
                        
                        // Try to find the form content using different selectors
                        var $content = $tempDiv.find('.wrap');
                        
                        if ($content.length) {
                            console.log('Found .wrap container in response');
                        } else {
                            console.log('No .wrap container found, trying different selectors');
                            
                            // Try other common selectors that might contain the form
                            $content = $tempDiv.find('form');
                            
                            if (!$content.length) {
                                $content = $tempDiv.find('#student-form');
                            }
                            
                            if (!$content.length) {
                                // Just take the whole body content
                                $content = $tempDiv.find('body');
                                if (!$content.length) {
                                    $content = $tempDiv; // Use everything
                                }
                            }
                        }
                        
                        // Replace the current content with the form
                        var contentHtml = $content.html();
                        
                        if (contentHtml) {
                            console.log('Found content to insert');
                            $('.wrap').html(contentHtml);
                            
                            // Re-initialize any scripts or handlers on the new content
                            // (if needed)
                            
                            // Update browser history without reloading
                            if (window.history && window.history.pushState) {
                                window.history.pushState({page: 'add_student'}, 'Add New Student', href);
                            }
                        } else {
                            console.error('No content found in response');
                            
                            // Fallback: Just show the raw response (for debugging)
                            $('.wrap').html('<div class="notice notice-error"><p>Error parsing response. Please reload the page and try again.</p><pre style="max-height:300px;overflow:auto">' + 
                                          $('<div/>').text(response).html() + '</pre></div>');
                        }
                        
                        // Remove loading indicator
                        $('#smp-loading').remove();
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading student form:', error);
                        
                        // Show error message
                        alert('Error loading the form. Please try again.');
                        
                        // Remove loading indicator
                        $('#smp-loading').remove();
                    }
                });
            }
        }
    });
    
    // Handle browser back button
    $(window).on('popstate', function(event) {
        if (window.location.href.indexOf('page=school-manager-students') > -1) {
            location.reload();
        }
    });
});
