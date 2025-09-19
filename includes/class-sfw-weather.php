<?php
namespace Strategicli\FamilyWeather;

class Weather {
    private static $instance = null;
    private $api;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->api = Api::get_instance();
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
        add_shortcode( 'sfw_current', array( $this, 'current_weather_shortcode' ) );
        add_shortcode( 'sfw_forecast', array( $this, 'forecast_weather_shortcode' ) );
        add_shortcode( 'sfw_weather', array( $this, 'combined_weather_shortcode' ) );

        // Add AJAX handler for manual refresh
        add_action( 'wp_ajax_sfw_refresh_weather', array( $this, 'ajax_refresh_weather' ) );
        add_action( 'wp_ajax_nopriv_sfw_refresh_weather', array( $this, 'ajax_refresh_weather' ) );
    }

    public function enqueue_frontend_scripts() {
        wp_enqueue_style( 'sfw-frontend-css', SFW_PLUGIN_URL . 'assets/css/sfw-frontend.css', array(), '1.0.0' );
        wp_enqueue_script( 'sfw-weather-js', SFW_PLUGIN_URL . 'assets/js/sfw-weather.js', array( 'jquery' ), '1.0.0', true );

        // Pass AJAX URL and nonce to frontend script
        wp_localize_script(
            'sfw-weather-js',
            'sfw_weather_ajax',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'sfw-weather-refresh-nonce' ),
            )
        );
    }

    /**
     * Renders the weather display.
     * @param array $atts Shortcode attributes.
     * @param bool $show_forecast Whether to show forecast in combined view.
     * @return string HTML output.
     */
    private function render_weather_display( $atts, $show_forecast = true ) {
        $settings = get_option( 'sfw_weather_settings' );
        $default_location = isset( $settings['location'] ) ? $settings['location'] : 'Waupun, WI';
        $default_units = isset( $settings['units'] ) ? $settings['units'] : 'imperial';
        $default_forecast_days = isset( $settings['forecast_days'] ) ? $settings['forecast_days'] : 5;

        // Get display settings
        $hide_location_name        = isset( $settings['hide_location_name'] ) ? (bool)$settings['hide_location_name'] : false;
        $hide_current_time         = isset( $settings['hide_current_time'] ) ? (bool)$settings['hide_current_time'] : false;
        $hide_temp_unit_letter     = isset( $settings['hide_temp_unit_letter'] ) ? (bool)$settings['hide_temp_unit_letter'] : false; // NEW: Get hide_temp_unit_letter
        $hide_current_description  = isset( $settings['hide_current_description'] ) ? (bool)$settings['hide_current_description'] : false;
        $hide_feels_like           = isset( $settings['hide_feels_like'] ) ? (bool)$settings['hide_feels_like'] : false;
        $hide_humidity             = isset( $settings['hide_humidity'] ) ? (bool)$settings['hide_humidity'] : false;
        $hide_wind_speed           = isset( $settings['hide_wind_speed'] ) ? (bool)$settings['hide_wind_speed'] : false;
        $hide_forecast_description = isset( $settings['hide_forecast_description'] ) ? (bool)$settings['hide_forecast_description'] : false;


        $atts = shortcode_atts(
            array(
                'location'      => $default_location,
                'units'         => $default_units,
                'forecast_days' => $default_forecast_days,
                'dark_mode'     => 'no',
            ),
            $atts,
            'sfw_weather'
        );

        $location = sanitize_text_field( $atts['location'] );
        $units = in_array( $atts['units'], array( 'metric', 'imperial' ) ) ? $atts['units'] : $default_units;
        $forecast_days = absint( $atts['forecast_days'] );
        if ( $forecast_days < 1 || $forecast_days > 7 ) {
            $forecast_days = $default_forecast_days;
        }

        // Add dark mode class if requested
        $wrapper_class = 'sfw-wrapper';
        if ( 'yes' === $atts['dark_mode'] ) {
            $wrapper_class .= ' sfw-dark-mode';
        }

        ob_start();
        ?>
        <div class="<?php echo esc_attr( $wrapper_class ); ?>" data-location="<?php echo esc_attr( $location ); ?>" data-units="<?php echo esc_attr( $units ); ?>" data-forecast-days="<?php echo esc_attr( $forecast_days ); ?>"
             data-hide-location-name="<?php echo (int)$hide_location_name; ?>"
             data-hide-current-time="<?php echo (int)$hide_current_time; ?>"
             data-hide-temp-unit-letter="<?php echo (int)$hide_temp_unit_letter; ?>" data-hide-current-description="<?php echo (int)$hide_current_description; ?>"
             data-hide-feels-like="<?php echo (int)$hide_feels_like; ?>"
             data-hide-humidity="<?php echo (int)$hide_humidity; ?>"
             data-hide-wind-speed="<?php echo (int)$hide_wind_speed; ?>"
             data-hide-forecast-description="<?php echo (int)$hide_forecast_description; ?>"
             data-dark-mode="<?php echo esc_attr( $atts['dark_mode'] ); ?>"
        >
            <div class="sfw-loading-overlay"><div class="sfw-spinner"></div></div>
            <div class="sfw-content">
                <button class="sfw-refresh-button" title="<?php esc_attr_e( 'Refresh Weather', 'strategicli-family-weather' ); ?>"><span class="dashicons dashicons-update"></span></button>
                <?php

                $weather_data = $this->api->get_weather_data( $location, $units, $forecast_days );

                if ( is_wp_error( $weather_data ) ) {
                    ?>
                    <p class="sfw-error-message"><?php echo esc_html( $weather_data->get_error_message() ); ?></p>
                    <?php
                } else {
                    $parsed_data = $this->api->parse_weather_response( $weather_data, $forecast_days );
                    ?>
                    <div class="sfw-main-display-row"> <?php if ( ! $hide_current_time ) : ?>
                            <div class="sfw-current-time-display">
                                <span class="sfw-time"><?php echo esc_html( date_i18n('g:i A') ); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php
                            $this->display_current_conditions( $parsed_data['current'], $units, $parsed_data['location_name'], $settings );
                        ?>
                    </div>
                    <?php
                    if ( $show_forecast && ! empty( $parsed_data['forecast'] ) ) {
                        $this->display_forecast( $parsed_data['forecast'], $units, $settings );
                    }
                }
                ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // MODIFIED: display_current_conditions method logic
    private function display_current_conditions( $current, $units, $location_name, $settings ) {
        if ( empty( $current ) ) {
            return;
        }

        // Determine temperature unit display
        $temp_unit_letter = ( $units === 'imperial' ) ? 'F' : 'C';
        $hide_temp_unit_letter = isset( $settings['hide_temp_unit_letter'] ) ? (bool)$settings['hide_temp_unit_letter'] : false; // NEW: Use hide_temp_unit_letter

        // Construct the temperature unit string based on setting
        $temp_unit = '°'; // Always include degree symbol
        if ( ! $hide_temp_unit_letter ) {
            $temp_unit .= $temp_unit_letter;
        }

        $speed_unit = ( $units === 'imperial' ) ? 'mph' : 'm/s';

        // Get other display settings
        $hide_location_name        = isset( $settings['hide_location_name'] ) ? (bool)$settings['hide_location_name'] : false;
        // $hide_current_time is handled outside this function now
        $hide_current_description  = isset( $settings['hide_current_description'] ) ? (bool)$settings['hide_current_description'] : false;
        $hide_feels_like           = isset( $settings['hide_feels_like'] ) ? (bool)$settings['hide_feels_like'] : false;
        $hide_humidity             = isset( $settings['hide_humidity'] ) ? (bool)$settings['hide_humidity'] : false;
        $hide_wind_speed           = isset( $settings['hide_wind_speed'] ) ? (bool)$settings['hide_wind_speed'] : false;

        ?>
        <div class="sfw-current-conditions">
            <?php if ( ! $hide_location_name ) : ?>
                <h2 class="sfw-location-name"><?php echo esc_html( $location_name ); ?></h2>
            <?php endif; ?>
            <div class="sfw-main-temp">
                <?php
                $icon_url = 'https://openweathermap.org/img/wn/' . esc_attr( $current['icon'] ) . '@2x.png';
                ?>
                <img src="<?php echo esc_url( $icon_url ); ?>" alt="<?php echo esc_attr( $current['description'] ); ?>" class="sfw-weather-icon">
                <span class="sfw-temperature"><?php echo esc_html( $current['temp'] ); ?><?php echo $temp_unit; ?></span>
            </div>
            <?php if ( ! $hide_current_description ) : ?>
                <p class="sfw-description"><?php echo esc_html( $current['description'] ); ?></p>
            <?php endif; ?>
            <ul class="sfw-details">
                <?php if ( ! $hide_feels_like ) : ?>
                    <li><?php esc_html_e( 'Feels like:', 'strategicli-family-weather' ); ?> <span><?php echo esc_html( $current['feels_like'] ); ?><?php echo $temp_unit; ?></span></li>
                <?php endif; ?>
                <?php if ( ! $hide_humidity ) : ?>
                    <li><?php esc_html_e( 'Humidity:', 'strategicli-family-weather' ); ?> <span><?php echo esc_html( $current['humidity'] ); ?>%</span></li>
                <?php endif; ?>
                <?php if ( ! $hide_wind_speed ) : ?>
                    <li><?php esc_html_e( 'Wind:', 'strategicli-family-weather' ); ?> <span><?php echo esc_html( $current['wind_speed'] ); ?> <?php echo $speed_unit; ?></span></li>
                <?php endif; ?>
            </ul>
        </div>
        <?php
    }

    // MODIFIED: display_forecast method logic
    private function display_forecast( $forecast, $units, $settings ) {
        if ( empty( $forecast ) ) {
            return;
        }

        // Determine temperature unit display for forecast
        $temp_unit_letter = ( $units === 'imperial' ) ? 'F' : 'C';
        $hide_temp_unit_letter = isset( $settings['hide_temp_unit_letter'] ) ? (bool)$settings['hide_temp_unit_letter'] : false; // NEW: Use hide_temp_unit_letter

        // Construct the temperature unit string based on setting
        $temp_unit = '°'; // Always include degree symbol
        if ( ! $hide_temp_unit_letter ) {
            $temp_unit .= $temp_unit_letter;
        }

        // Get display settings
        $hide_forecast_description = isset( $settings['hide_forecast_description'] ) ? (bool)$settings['hide_forecast_description'] : false;

        ?>
        <div class="sfw-forecast">
            <h3><?php esc_html_e( 'Forecast', 'strategicli-family-weather' ); ?></h3>
            <div class="sfw-forecast-grid">
                <?php foreach ( $forecast as $day ) : ?>
                    <div class="sfw-forecast-day">
                        <span class="sfw-day-name"><?php echo esc_html( date_i18n( 'D', $day['date'] ) ); ?></span>
                        <?php
                        $icon_url = 'https://openweathermap.org/img/wn/' . esc_attr( $day['icon'] ) . '@2x.png';
                        ?>
                        <img src="<?php echo esc_url( $icon_url ); ?>" alt="<?php echo esc_attr( $day['description'] ); ?>" class="sfw-weather-icon-small">
                        <span class="sfw-temp-range">
                            <span class="sfw-temp-high"><?php echo esc_html( $day['temp_day'] ); ?><?php echo $temp_unit; ?></span><span class="sfw-temp-separator">&nbsp;/&nbsp;</span><span class="sfw-temp-low"><?php echo esc_html( $day['temp_night'] ); ?><?php echo $temp_unit; ?></span>
                        </span>
                        <?php if ( ! $hide_forecast_description ) : ?>
                            <p class="sfw-forecast-description"><?php echo esc_html( $day['description'] ); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    public function current_weather_shortcode( $atts ) {
        return $this->render_weather_display( $atts, false ); // Only current conditions
    }

    public function forecast_weather_shortcode( $atts ) {
        return '<p class="sfw-info-message">' . esc_html__( 'The [sfw_forecast] shortcode is typically used as part of the combined [sfw_weather] shortcode to display daily forecast.', 'strategicli-family-weather' ) . '</p>';
    }

    public function combined_weather_shortcode( $atts ) {
        return $this->render_weather_display( $atts, true ); // Combined current and forecast
    }

    /**
     * AJAX handler for manual weather refresh.
     */
    public function ajax_refresh_weather() {
        check_ajax_referer( 'sfw-weather-refresh-nonce', 'nonce' );

        $location      = isset( $_POST['location'] ) ? sanitize_text_field( $_POST['location'] ) : '';
        $units         = isset( $_POST['units'] ) ? sanitize_text_field( $_POST['units'] ) : '';
        $forecast_days = isset( $_POST['forecast_days'] ) ? absint( $_POST['forecast_days'] ) : 1;
        $dark_mode     = isset( $_POST['dark_mode'] ) ? sanitize_text_field( $_POST['dark_mode'] ) : 'no';

        // Get display settings from POST data (sent from JS)
        $hide_location_name        = isset( $_POST['hide_location_name'] ) ? (bool)$_POST['hide_location_name'] : false;
        $hide_current_time         = isset( $_POST['hide_current_time'] ) ? (bool)$_POST['hide_current_time'] : false;
        $hide_temp_unit_letter     = isset( $_POST['hide_temp_unit_letter'] ) ? (bool)$_POST['hide_temp_unit_letter'] : false; // NEW: Get hide_temp_unit_letter from AJAX POST
        $hide_current_description  = isset( $_POST['hide_current_description'] ) ? (bool)$_POST['hide_current_description'] : false;
        $hide_feels_like           = isset( $_POST['hide_feels_like'] ) ? (bool)$_POST['hide_feels_like'] : false;
        $hide_humidity             = isset( $_POST['hide_humidity'] ) ? (bool)$_POST['hide_humidity'] : false;
        $hide_wind_speed           = isset( $_POST['hide_wind_speed'] ) ? (bool)$_POST['hide_wind_speed'] : false;
        $hide_forecast_description = isset( $_POST['hide_forecast_description'] ) ? (bool)$_POST['hide_forecast_description'] : false;

        $display_settings = array(
            'hide_location_name'        => $hide_location_name,
            'hide_current_time'         => $hide_current_time,
            'hide_temp_unit_letter'     => $hide_temp_unit_letter, // NEW: Pass hide_temp_unit_letter to display functions
            'hide_current_description'  => $hide_current_description,
            'hide_feels_like'           => $hide_feels_like,
            'hide_humidity'             => $hide_humidity,
            'hide_wind_speed'           => $hide_wind_speed,
            'hide_forecast_description' => $hide_forecast_description,
        );

        if ( empty( $location ) || empty( $units ) ) {
            wp_send_json_error( array( 'message' => __( 'Missing required data for refresh.', 'strategicli-family-weather' ) ) );
        }

        // Delete the existing transient to force a fresh API call.
        $cache_key = 'sfw_weather_data_' . md5( $location . $units . $forecast_days );
        delete_transient( $cache_key );

        $weather_data = $this->api->get_weather_data( $location, $units, $forecast_days );

        if ( is_wp_error( $weather_data ) ) {
            wp_send_json_error( array( 'message' => $weather_data->get_error_message() ) );
        } else {
            $parsed_data = $this->api->parse_weather_response( $weather_data, $forecast_days );
            ob_start();
            ?>
            <div class="sfw-main-display-row"> <?php if ( ! $hide_current_time ) : ?>
                    <div class="sfw-current-time-display">
                        <span class="sfw-time"><?php echo esc_html( date_i18n('g:i A') ); ?></span>
                    </div>
                <?php endif; ?>
                <?php
                    $this->display_current_conditions( $parsed_data['current'], $units, $parsed_data['location_name'], $display_settings );
                ?>
            </div>
            <?php
            if ( $forecast_days > 1 && ! empty( $parsed_data['forecast'] ) ) {
                $this->display_forecast( $parsed_data['forecast'], $units, $display_settings );
            }
            $content = ob_get_clean();

            // Need to pass the dark_mode state back to the frontend to ensure the correct class is reapplied
            $wrapper_class = 'sfw-wrapper';
            if ( 'yes' === $dark_mode ) {
                $wrapper_class .= ' sfw-dark-mode';
            }

            wp_send_json_success( array(
                'content' => $content,
                'wrapper_class' => $wrapper_class,
            ) );
        }
    }
}