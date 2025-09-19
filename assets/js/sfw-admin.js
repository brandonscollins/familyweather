/**
 * Strategicli Family Weather - Admin JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';

    // Test API functionality
    $('#sfw-test-api').on('click', function(e) {
        e.preventDefault();

        const $button = $(this);
        const $results = $('#sfw-test-results');
        const apiKey = $('#sfw_api_key').val();
        const location = $('#sfw_location').val();

        // Validate inputs
        if (!apiKey || !location) {
            $results
                .removeClass('success')
                .addClass('error')
                .html('<strong>Error:</strong> Please enter both API key and location.')
                .show();
            return;
        }

        // Show loading state
        $button.prop('disabled', true).text('Testing...');
        $results.removeClass('success error').html('Testing API connection...').show();

        // Make test request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'sfw_test_api',
                nonce: $('#_wpnonce').val(),
                api_key: apiKey,
                location: location
            },
            success: function(response) {
                if (response.success) {
                    $results
                        .removeClass('error')
                        .addClass('success')
                        .html('<strong>Success!</strong> ' + response.data.message);
                } else {
                    $results
                        .removeClass('success')
                        .addClass('error')
                        .html('<strong>Error:</strong> ' + response.data.message);
                }
            },
            error: function() {
                $results
                    .removeClass('success')
                    .addClass('error')
                    .html('<strong>Error:</strong> Failed to test API connection.');
            },
            complete: function() {
                $button.prop('disabled', false).text('Test Weather API');
            }
        });
    });

    // Location validation helper
    $('#sfw_location').on('blur', function() {
        const $input = $(this);
        const value = $input.val().trim();

        if (value) {
            // Remove any existing validation message
            $input.siblings('.sfw-validation-message').remove();

            // Basic validation
            const isZipCode = /^\\d{5}$/.test(value);
            const isCityName = /^[a-zA-Z\\s,]+$/.test(value);

            if (!isZipCode && !isCityName) {
                $input.after(
                    '<span class="sfw-validation-message" style="color: #d63638; font-size: 0.875em; margin-top: 0.25rem; display: block;">' +
                    'Please enter a valid city name or 5-digit zip code.' +
                    '</span>'
                );
            }
        }
    });

    // Cache duration slider
    const $cacheDuration = $('#sfw_cache_duration');
    if ($cacheDuration.length) {
        const $display = $('<span class="sfw-cache-display" style="margin-left: 10px; font-weight: 600;"></span>');
        $cacheDuration.after($display);

        function updateCacheDisplay() {
            const value = $cacheDuration.val();
            $display.text(value + ' minutes');
        }

        $cacheDuration.on('input', updateCacheDisplay);
        updateCacheDisplay();
    }

    // Update frequency slider
    const $updateFrequency = $('#sfw_update_frequency');
    if ($updateFrequency.length) {
        const $display = $('<span class="sfw-update-display" style="margin-left: 10px; font-weight: 600;"></span>');
        $updateFrequency.after($display);

        function updateFrequencyDisplay() {
            const value = $updateFrequency.val();
            if (value === '0') {
                $display.text('Disabled');
            } else {
                $display.text(value + ' minutes');
            }
        }

        $updateFrequency.on('input', updateFrequencyDisplay);
        updateFrequencyDisplay();
    }

    // Add warning when changing API key
    $('#sfw_api_key').on('change', function() {
        const $input = $(this);
        if ($input.data('original-value') && $input.val() !== $input.data('original-value')) {
            if (!$input.siblings('.sfw-api-warning').length) {
                $input.after(
                    '<p class="sfw-api-warning" style="color: #dba617; margin-top: 0.5rem;">' +
                    '<strong>Note:</strong> Changing the API key will clear the weather cache.' +
                    '</p>'
                );
            }
        }
    }).each(function() {
        $(this).data('original-value', $(this).val());
    });

    // Forecast days validation
    $('#sfw_forecast_days').on('change', function() {
        const value = parseInt($(this).val());
        if (value < 1) {
            $(this).val(1);
        } else if (value > 7) {
            $(this).val(7);
        }
    });

    // Add copy shortcode functionality
    $('.sfw-admin-box code').on('click', function() {
        const $code = $(this);
        const text = $code.text();

        // Create temporary input
        const $temp = $('<input>');
        $('body').append($temp);
        $temp.val(text).select();
        document.execCommand('copy');
        $temp.remove();

        // Show feedback
        const originalText = $code.text();
        $code.text('Copied!').css('background', '#4caf50').css('color', 'white');
        setTimeout(function() {
            $code.text(originalText).css('background', '').css('color', '');
        }, 1000);
    });
});

// Add test API action handler to admin class
jQuery(document).ready(function($) {
    // This would need to be added to the PHP admin class as well
    window.sfwTestAPI = function(apiKey, location) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: '<https://api.openweathermap.org/data/2.5/weather>',
                data: {
                    q: location,
                    appid: apiKey,
                    units: 'metric'
                },
                dataType: 'json',
                success: function(data) {
                    resolve({
                        success: true,
                        message: `Connected successfully! Weather data received for ${data.name}, ${data.sys.country}.`
                    });
                },
                error: function(xhr) {
                    let message = 'Failed to connect to OpenWeatherMap API.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    resolve({
                        success: false,
                        message: message
                    });
                }
            });
        });
    };
});
