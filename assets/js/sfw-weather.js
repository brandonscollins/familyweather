jQuery(document).ready(function($) {
    console.log("sfw-weather.js: Document ready.");

    function refreshWeather(button) {
        console.log("sfw-weather.js: refreshWeather function called.");
        var wrapper = button.closest('.sfw-wrapper');
        var location = wrapper.data('location');
        var units = wrapper.data('units');
        var forecastDays = wrapper.data('forecast-days');
        var darkMode = wrapper.data('dark-mode');

        // Gather display settings from data attributes
        var hideLocationName = wrapper.data('hide-location-name');
        var hideCurrentTime = wrapper.data('hide-current-time');
        var hideTempUnitLetter = wrapper.data('hide-temp-unit-letter'); // NEW: Get hide_temp_unit_letter state
        var hideCurrentDescription = wrapper.data('hide-current-description');
        var hideFeelsLike = wrapper.data('hide-feels-like');
        var hideHumidity = wrapper.data('hide-humidity');
        var hideWindSpeed = wrapper.data('hide-wind-speed');
        var hideForecastDescription = wrapper.data('hide-forecast-description');

        console.log("sfw-weather.js: Data attributes from wrapper:", {
            location: location,
            units: units,
            forecastDays: forecastDays,
            darkMode: darkMode,
            hideLocationName: hideLocationName,
            hideCurrentTime: hideCurrentTime,
            hideTempUnitLetter: hideTempUnitLetter,
            hideCurrentDescription: hideCurrentDescription,
            hideFeelsLike: hideFeelsLike,
            hideHumidity: hideHumidity,
            hideWindSpeed: hideWindSpeed,
            hideForecastDescription: hideForecastDescription
        });

        var contentDiv = wrapper.find('.sfw-content');
        var loadingOverlay = wrapper.find('.sfw-loading-overlay');

        loadingOverlay.fadeIn(200); // Show loading overlay
        console.log("sfw-weather.js: Loading overlay faded in.");

        // Check if sfw_weather_ajax is indeed available before calling $.ajax
        if (typeof sfw_weather_ajax === 'undefined' || !sfw_weather_ajax.ajax_url || !sfw_weather_ajax.nonce) {
            console.error("sfw-weather.js: sfw_weather_ajax object or its properties are missing. Cannot make AJAX call.");
            contentDiv.html('<p class="sfw-error-message">Configuration error: Weather data cannot be fetched.</p>');
            loadingOverlay.fadeOut(200);
            return; // Exit function if configuration is missing
        }

        console.log("sfw-weather.js: Attempting AJAX call for weather...");

        $.ajax({
            url: sfw_weather_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sfw_refresh_weather',
                nonce: sfw_weather_ajax.nonce,
                location: location,
                units: units,
                forecast_days: forecastDays,
                // Pass display settings to AJAX
                hide_location_name: hideLocationName,
                hide_current_time: hideCurrentTime,
                hide_temp_unit_letter: hideTempUnitLetter, // NEW: Pass hide_temp_unit_letter to AJAX
                hide_current_description: hideCurrentDescription,
                hide_feels_like: hideFeelsLike,
                hide_humidity: hideHumidity,
                hide_wind_speed: hideWindSpeed,
                hide_forecast_description: hideForecastDescription,
                dark_mode: darkMode,
            },
            success: function(response) {
                console.log("sfw-weather.js: AJAX call success. Response:", response);
                if (response.success) {
                    contentDiv.html(response.data.content); // Replace content

                    // Reapply the correct wrapper class based on AJAX response
                    wrapper.removeClass('sfw-dark-mode'); // Remove any existing
                    if (response.data.wrapper_class.includes('sfw-dark-mode')) {
                         wrapper.addClass('sfw-dark-mode');
                    }
                } else {
                    console.error("sfw-weather.js: AJAX call success, but server reported error:", response.data.message);
                    contentDiv.html('<p class="sfw-error-message">' + response.data.message + '</p>');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("sfw-weather.js: AJAX call error:", textStatus, errorThrown, jqXHR);
                contentDiv.html('<p class="sfw-error-message">Error refreshing weather data. ' + (errorThrown ? errorThrown : 'Unknown error') + '</p>');
            },
            complete: function() {
                console.log("sfw-weather.js: AJAX call complete.");
                loadingOverlay.fadeOut(200); // Hide loading overlay
            }
        });
    }


    // Initial load, manual refresh, and auto-refresh
    $('.sfw-wrapper').each(function() {
        var wrapper = $(this);
        var contentDiv = wrapper.find('.sfw-content');
        var needsInitialRefresh = contentDiv.find('.sfw-error-message').length || !contentDiv.children('.sfw-main-display-row').length;

        console.log("sfw-weather.js: Initial load check for wrapper:", wrapper[0]);
        console.log("sfw-weather.js: Needs initial refresh:", needsInitialRefresh);

        if (needsInitialRefresh) {
            console.log('sfw-weather.js: Triggering initial AJAX refresh for widget.');
            refreshWeather(wrapper.find('.sfw-refresh-button'));
        } else {
            console.log('sfw-weather.js: Widget content already rendered by PHP, no initial AJAX refresh needed.');
            wrapper.find('.sfw-loading-overlay').hide(); // Ensure overlay is hidden if not refreshing
        }

        // Auto-refresh logic
        var autoRefreshInterval = parseInt(wrapper.data('auto-refresh-interval'), 10);
        if (!isNaN(autoRefreshInterval) && autoRefreshInterval > 0) {
            console.log('sfw-weather.js: Setting up auto-refresh every', autoRefreshInterval, 'minutes.');
            setInterval(function() {
                console.log('sfw-weather.js: Auto-refresh triggered.');
                refreshWeather(wrapper.find('.sfw-refresh-button'));
            }, autoRefreshInterval * 60 * 1000);
        } else {
            console.log('sfw-weather.js: Auto-refresh disabled or not set.');
        }
    });

    // Bind click event for refresh button (delegated as content is replaced)
    $(document).on('click', '.sfw-refresh-button', function() {
        console.log('sfw-weather.js: Manual refresh button clicked.');
        refreshWeather($(this));
    });
});