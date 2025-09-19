<?php
namespace Strategicli\FamilyWeather;

class Api {
    private static $instance = null;
    private $api_key;
    private $cache_duration; // Default to 30 minutes

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $settings = get_option( 'sfw_weather_settings', array() );
        $this->api_key = isset( $settings['api_key'] ) ? sanitize_text_field( $settings['api_key'] ) : '';
        // Cache duration is stored in minutes in settings, convert to seconds here
        $this->cache_duration = isset( $settings['cache_duration'] ) ? absint( $settings['cache_duration'] ) * MINUTE_IN_SECONDS : 30 * MINUTE_IN_SECONDS;
    }

    /**
     * Fetches weather data (current and forecast) from OpenWeatherMap.
     * Uses two separate API calls: /weather for current, /forecast for daily.
     *
     * @param string $location Location string (city name, city,state, zip code).
     * @param string $units    'metric' or 'imperial'.
     * @param int    $forecast_days Number of forecast days (1-7).
     * @return array|WP_Error Weather data (current and forecast) or WP_Error on failure.
     */
    public function get_weather_data( $location, $units, $forecast_days ) {
        if ( empty( $this->api_key ) ) {
            return new \WP_Error( 'sfw_no_api_key', __( 'OpenWeatherMap API key is not set. Please configure it in plugin settings.', 'strategicli-family-weather' ) );
        }
        if ( empty( $location ) ) {
            return new \WP_Error( 'sfw_no_location', __( 'Location is not set. Please configure it in plugin settings or shortcode.', 'strategicli-family-weather' ) );
        }

        $location_hash = md5( $location . $units . $forecast_days );
        $cache_key = 'sfw_weather_data_' . $location_hash; // Unique cache key for combined data
        $cached_data = get_transient( $cache_key );

        if ( false !== $cached_data ) {
            return $cached_data; // Return cached data if available.
        }

        $current_weather_url = add_query_arg(
            array(
                'q'     => urlencode( $location ),
                'units' => $units,
                'appid' => $this->api_key,
            ),
            'https://api.openweathermap.org/data/2.5/weather'
        );

        $forecast_weather_url = add_query_arg(
            array(
                'q'     => urlencode( $location ),
                'units' => $units,
                'appid' => $this->api_key,
                'cnt'   => ( $forecast_days * 8 ) // OpenWeatherMap's 5-day / 3-hour forecast API gives 8 entries per day
            ),
            'https://api.openweathermap.org/data/2.5/forecast'
        );

        $response_current = wp_remote_get( $current_weather_url );
        if ( is_wp_error( $response_current ) ) {
            return new \WP_Error( 'sfw_current_api_error', __( 'Could not retrieve current weather data: ', 'strategicli-family-weather' ) . $response_current->get_error_message() );
        }
        $body_current = wp_remote_retrieve_body( $response_current );
        $data_current = json_decode( $body_current, true );

        // Check for OpenWeatherMap specific errors (e.g., 404 for location not found, 401 for invalid API key)
        if ( isset( $data_current['cod'] ) && $data_current['cod'] != 200 ) {
            return new \WP_Error( 'sfw_api_response_error', sprintf( __( 'Weather API Error (%s): %s', 'strategicli-family-weather' ), $data_current['cod'], $data_current['message'] ) );
        }

        $combined_data = array(
            'current' => $data_current,
            'forecast' => array()
        );

        // Fetch forecast only if forecast_days > 1
        if ( $forecast_days > 1 ) {
            $response_forecast = wp_remote_get( $forecast_weather_url );
            if ( is_wp_error( $response_forecast ) ) {
                // Log this error but don't stop current display if forecast fails
                error_log( 'Strategicli Weather: Forecast API Error - ' . $response_forecast->get_error_message() );
            } else {
                $body_forecast = wp_remote_retrieve_body( $response_forecast );
                $data_forecast = json_decode( $body_forecast, true );

                if ( isset( $data_forecast['cod'] ) && $data_forecast['cod'] != 200 ) {
                    error_log( 'Strategicli Weather: Forecast API Response Error - ' . $data_forecast['message'] );
                } else {
                    $combined_data['forecast'] = $data_forecast;
                }
            }
        }

        // Store combined data in transient cache.
        set_transient( $cache_key, $combined_data, $this->cache_duration );

        return $combined_data;
    }

    /**
     * Validates the OpenWeatherMap API key.
     *
     * @param string $api_key The API key to validate.
     * @return bool True if valid, false otherwise.
     */
    public function validate_api_key( $api_key ) {
        if ( empty( $api_key ) ) {
            return false;
        }
        // Use a simple city like London for validation against the current weather API
        $test_url = add_query_arg(
            array(
                'q'     => 'London',
                'appid' => $api_key,
                'units' => 'metric'
            ),
            'https://api.openweathermap.org/data/2.5/weather'
        );

        $response = wp_remote_get( $test_url, array( 'timeout' => 5 ) );

        if ( is_wp_error( $response ) ) {
            return false; // Connection error
        }

        $http_code = wp_remote_retrieve_response_code( $response );

        // A 200 OK means the key is likely valid. 401 is unauthorized.
        return ( $http_code === 200 );
    }

    /**
     * Parses the raw API response into a more usable format for display.
     *
     * @param array $raw_data The combined raw data from the OpenWeatherMap API ('current' and 'forecast').
     * @param int $forecast_days Number of forecast days requested.
     * @return array Structured weather data.
     */
    public function parse_weather_response( $raw_data, $forecast_days ) {
        $parsed = array(
            'current'  => array(),
            'forecast' => array(),
            'location_name' => isset( $raw_data['current']['name'] ) ? $raw_data['current']['name'] : 'Unknown Location',
        );

        // Current conditions
        if ( isset( $raw_data['current'] ) ) {
            $current = $raw_data['current'];
            $parsed['current'] = array(
                'temp'        => round( $current['main']['temp'] ),
                'feels_like'  => round( $current['main']['feels_like'] ),
                'description' => ucfirst( $current['weather'][0]['description'] ),
                'icon'        => $current['weather'][0]['icon'],
                'humidity'    => $current['main']['humidity'],
                'wind_speed'  => round( $current['wind']['speed'], 1 ),
                'pressure'    => $current['main']['pressure'],
                'visibility'  => isset( $current['visibility'] ) ? round( $current['visibility'] / 1000, 1 ) : null,
                'sunrise'     => $current['sys']['sunrise'],
                'sunset'      => $current['sys']['sunset'],
            );
        }

        // Forecast (daily, from 3-hour forecast)
        if ( isset( $raw_data['forecast']['list'] ) && $forecast_days > 1 ) {
            $daily_forecasts = [];
            $today = date('Y-m-d', current_time('timestamp'));

            foreach ($raw_data['forecast']['list'] as $item) {
                $item_date = date('Y-m-d', $item['dt']);

                // Skip current day's forecast
                if ($item_date === $today) {
                    continue;
                }

                if (!isset($daily_forecasts[$item_date])) {
                    $daily_forecasts[$item_date] = [
                        'date' => $item['dt'],
                        'temps' => [],
                        'icons' => [], // Store all icons for the day
                        'descriptions' => [], // Store all descriptions for the day
                        'humidity' => [],
                        'wind_speed' => [],
                        'pop' => [],
                    ];
                }
                $daily_forecasts[$item_date]['temps'][] = $item['main']['temp'];
                $daily_forecasts[$item_date]['icons'][] = $item['weather'][0]['icon'];
                $daily_forecasts[$item_date]['descriptions'][] = $item['weather'][0]['description'];
                $daily_forecasts[$item_date]['humidity'][] = $item['main']['humidity'];
                $daily_forecasts[$item_date]['wind_speed'][] = $item['wind']['speed'];
                $daily_forecasts[$item_date]['pop'][] = isset($item['pop']) ? $item['pop'] : 0;
            }

            $count = 0;
            foreach ($daily_forecasts as $date => $data) {
                if ($count >= ($forecast_days -1) ) { // -1 because current day is not in forecast
                    break;
                }

                // Determine the most frequent icon for the day
                $icon_counts = array_count_values($data['icons']);
                arsort($icon_counts); // Sort by count in descending order
                $most_frequent_icon = key($icon_counts); // Get the icon name (key) with the highest count

                // Determine the most frequent description for the day
                $description_counts = array_count_values($data['descriptions']);
                arsort($description_counts); // Sort by count in descending order
                $most_frequent_description = key($description_counts); // Get the description (key) with the highest count

                $parsed['forecast'][] = [
                    'date'        => $data['date'],
                    'temp_day'    => round(max($data['temps'])), // Max temp of the day
                    'temp_night'  => round(min($data['temps'])), // Min temp of the day
                    'description' => ucfirst($most_frequent_description), // Use the determined most frequent description
                    'icon'        => $most_frequent_icon, // Use the determined most frequent icon
                    'humidity'    => round(array_sum($data['humidity']) / count($data['humidity'])),
                    'wind_speed'  => round(array_sum($data['wind_speed']) / count($data['wind_speed']), 1),
                    'pop'         => round(max($data['pop']) * 100),
                ];
                $count++;
            }
        }

        return $parsed;
    }
}