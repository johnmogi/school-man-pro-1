/**
 * Promo Codes Admin JS
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Initialize datepicker
        $('.datepicker').datepicker({
            dateFormat: smpPromoCodes.dateFormat,
            minDate: 0
        });

        // Toggle amount field based on discount type
        $('#discount_type').on('change', function() {
            var $amount = $('#amount');
            var $symbol = $('.discount-type-symbol');
            
            if ($(this).val() === 'percent') {
                var currentVal = $amount.val();
                $amount.attr('max', '100');
                $symbol.text('%');
                
                // If value is over 100, cap it at 100
                if (parseFloat(currentVal) > 100) {
                    $amount.val('100');
                }
            } else {
                $amount.removeAttr('max');
                $symbol.text($symbol.data('currency'));
            }
        });

        // Handle form submission
        $('form.smp-form').on('submit', function(e) {
            var $form = $(this);
            var $code = $('#code', $form);
            var $amount = $('#amount', $form);
            var isValid = true;

            // Reset error states
            $('.form-field').removeClass('form-invalid');
            $('.error-message').remove();

            // Validate required fields
            if (!$code.val().trim()) {
                showError($code, smpPromoCodes.i18n.codeRequired);
                isValid = false;
            }

            if (!$amount.val() || parseFloat($amount.val()) <= 0) {
                showError($amount, smpPromoCodes.i18n.amountRequired);
                isValid = false;
            } else if ($('#discount_type').val() === 'percent' && parseFloat($amount.val()) > 100) {
                showError($amount, smpPromoCodes.i18n.amountMax);
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: $('.form-field.form-invalid').first().offset().top - 100
                }, 500);
            }

            return isValid;
        });

        // Show error message for a field
        function showError($field, message) {
            var $row = $field.closest('tr');
            $row.addClass('form-invalid');
            $row.append('<div class="error-message" style="color: #dc3232; font-style: italic; margin-top: 5px;">' + message + '</div>');
        }

        // Handle bulk actions
        $('body').on('click', '.bulk-export', function(e) {
            e.preventDefault();
            
            var $form = $('#posts-filter');
            var selected = [];
            
            $('input[name="promo_code_ids[]"]:checked').each(function() {
                selected.push($(this).val());
            });
            
            if (selected.length === 0) {
                alert(smpPromoCodes.i18n.selectItems);
                return false;
            }
            
            // Add selected IDs to form and submit
            $form.append('<input type="hidden" name="export_ids" value="' + selected.join(',') + '">');
            $form.attr('action', $(this).attr('href')).submit();
            $form.find('input[name="export_ids"]').remove();
            
            return false;
        });

        // Toggle all checkboxes
        $('body').on('click', '#cb-select-all-1, #cb-select-all-2', function() {
            var isChecked = $(this).is(':checked');
            $('input[name="promo_code_ids[]"]').prop('checked', isChecked);
        });
    });

})(jQuery);
