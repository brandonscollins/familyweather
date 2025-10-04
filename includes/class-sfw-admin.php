<?php
namespace Strategicli\FamilyWeather;

class Admin {
    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function add_plugin_page() {
        add_menu_page(
            __( 'Strategicli Family Weather Settings', 'strategicli-family-weather' ), // Page title
            __( 'Family Weather', 'strategicli-family-weather' ), // Menu title
            'manage_options', // Capability
            'sfw-weather-settings', // Menu slug
            array( $this, 'create_admin_page' ), // Callback function
            'dashicons-cloud', // Icon URL
            99 // Position
        );
    }

    public function create_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Strategicli Family Weather Settings', 'strategicli-family-weather' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'sfw_weather_settings_group' );
                do_settings_sections( 'sfw-weather-settings' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function register_settings() {
        register_setting(
            'sfw_weather_settings_group',
            'sfw_weather_settings',
            array( $this, 'sanitize_settings' )
        );

        add_settings_section(
            'sfw_api_section',
            __( 'API Settings', 'strategicli-family-weather' ),
            array( $this, 'api_section_callback' ),
            'sfw-weather-settings'
        );

        add_settings_field(
            'api_key',
            __( 'OpenWeatherMap API Key', 'strategicli-family-weather' ),
            array( $this, 'api_key_callback' ),
            'sfw-weather-settings',
            'sfw_api_section'
        );

        add_settings_field(
            'location',
            __( 'Default Location (e.g., Waupun, WI, US or 53963)', 'strategicli-family-weather' ),
            array( $this, 'location_callback' ),
            'sfw-weather-settings',
            'sfw_api_section'
        );

        add_settings_field(
            'units',
            __( 'Units', 'strategicli-family-weather' ),
            array( $this, 'units_callback' ),
            'sfw-weather-settings',
            'sfw_api_section'
        );

        add_settings_field(
            'cache_duration',
            __( 'Cache Duration (minutes)', 'strategicli-family-weather' ),
            array( $this, 'cache_duration_callback' ),
            'sfw-weather-settings',
            'sfw_api_section'
        );

        add_settings_field(
            'forecast_days',
            __( 'Number of Forecast Days (1-7)', 'strategicli-family-weather' ),
            array( $this, 'forecast_days_callback' ),
            'sfw-weather-settings',
            'sfw_api_section'
        );

        // SECTION FOR DISPLAY OPTIONS
        add_settings_field(
            'auto_refresh_interval',
            __( 'Auto-Refresh Interval (minutes)', 'strategicli-family-weather' ),
            array( $this, 'auto_refresh_interval_callback' ),
            'sfw-weather-settings',
            'sfw_api_section'
        );

        add_settings_section(
            'sfw_display_section',
            __( 'Display Options', 'strategicli-family-weather' ),
            array( $this, 'display_section_callback' ),
            'sfw-weather-settings'
        );

        add_settings_field(
            'hide_location_name',
            __( 'Hide Location Name (Current)', 'strategicli-family-weather' ),
            array( $this, 'hide_location_name_callback' ),
            'sfw-weather-settings',
            'sfw_display_section'
        );

        add_settings_field(
            'hide_current_time',
            __( 'Hide Current Time (Current)', 'strategicli-family-weather' ),
            array( $this, 'hide_current_time_callback' ),
            'sfw-weather-settings',
            'sfw_display_section'
        );

        // NEW FIELD FOR HIDE TEMPERATURE UNIT LETTER
        add_settings_field(
            'hide_temp_unit_letter',
            __( 'Hide Temperature Unit Letter (e.g., "F" or "C")', 'strategicli-family-weather' ),
            array( $this, 'hide_temp_unit_letter_callback' ),
            'sfw-weather-settings',
            'sfw_display_section'
        );

        add_settings_field(
            'hide_current_description',
            __( 'Hide Description (Current)', 'strategicli-family-weather' ),
            array( $this, 'hide_current_description_callback' ),
            'sfw-weather-settings',
            'sfw_display_section'
        );

        add_settings_field(
            'hide_feels_like',
            __( 'Hide "Feels like" Temp', 'strategicli-family-weather' ),
            array( $this, 'hide_feels_like_callback' ),
            'sfw-weather-settings',
            'sfw_display_section'
        );

        add_settings_field(
            'hide_humidity',
            __( 'Hide Humidity', 'strategicli-family-weather' ),
            array( $this, 'hide_humidity_callback' ),
            'sfw-weather-settings',
            'sfw_display_section'
        );

        add_settings_field(
            'hide_wind_speed',
            __( 'Hide Wind Speed', 'strategicli-family-weather' ),
            array( $this, 'hide_wind_speed_callback' ),
            'sfw-weather-settings',
            'sfw_display_section'
        );

        add_settings_field(
            'hide_forecast_description',
            __( 'Hide Description (Forecast)', 'strategicli-family-weather' ),
            array( $this, 'hide_forecast_description_callback' ),
            'sfw-weather-settings',
            'sfw_display_section'
        );

        // EXISTING SHORTCODE SECTION
        add_settings_section(
            'sfw_shortcode_section',
            __( 'Shortcode Usage', 'strategicli-family-weather' ),
            array( $this, 'shortcode_section_callback' ),
            'sfw-weather-settings'
        );

        add_settings_field(
            'shortcode_info',
            __( 'How to Use', 'strategicli-family-weather' ),
            array( $this, 'shortcode_info_callback' ),
            'sfw-weather-settings',
            'sfw_shortcode_section'
        );
    }

    public function api_section_callback() {
        esc_html_e( 'Enter your OpenWeatherMap API key and default weather display settings.', 'strategicli-family-weather' );
    }

    public function api_key_callback() {
        $settings = get_option( 'sfw_weather_settings' );
        $api_key = isset( $settings['api_key'] ) ? $settings['api_key'] : '';
        ?>
        <input type="text" name="sfw_weather_settings[api_key]" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Your OpenWeatherMap API Key', 'strategicli-family-weather' ); ?>" />
        <p class="description"><?php esc_html_e( 'Get your API key from OpenWeatherMap.org. (It may take a few hours for the key to become active.)', 'strategicli-family-weather' ); ?></p>
        <?php
    }

            public function auto_refresh_interval_callback() {
        $settings = get_option( 'sfw_weather_settings' );
        $auto_refresh_interval = isset( $settings['auto_refresh_interval'] ) ? $settings['auto_refresh_interval'] : 30;
        ?>
        <input type="number" name="sfw_weather_settings[auto_refresh_interval]" value="<?php echo esc_attr( $auto_refresh_interval ); ?>" min="0" max="1440" class="small-text" />
        <p class="description"><?php esc_html_e( 'How often the weather widget should auto-refresh, in minutes (0 = disabled, max 1440 = 24 hours).', 'strategicli-family-weather' ); ?></p>
        <?php
    }
    public function location_callback() {
        $settings = get_option( 'sfw_weather_settings' );
        $location = isset( $settings['location'] ) ? $settings['location'] : 'Waupun, WI';
        ?>
        <input type="text" name="sfw_weather_settings[location]" value="<?php echo esc_attr( $location ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., Waupun, WI or 53963', 'strategicli-family-weather' ); ?>" />
        <p class="description"><?php esc_html_e( 'Enter a city name, city,state, or zip code (e.g., "Waupun, WI" or "53963").', 'strategicli-family-weather' ); ?></p>
        <?php
    }

    public function units_callback() {
        $settings = get_option( 'sfw_weather_settings' );
        $units = isset( $settings['units'] ) ? $settings['units'] : 'imperial';
        ?>
        <label>
            <input type="radio" name="sfw_weather_settings[units]" value="imperial" <?php checked( $units, 'imperial' ); ?> />
            <?php esc_html_e( 'Fahrenheit / Miles per hour (Imperial)', 'strategicli-family-weather' ); ?>
        </label><br>
        <label>
            <input type="radio" name="sfw_weather_settings[units]" value="metric" <?php checked( $units, 'metric' ); ?> />
            <?php esc_html_e( 'Celsius / Meters per second (Metric)', 'strategicli-family-weather' ); ?>
        </label>
        <?php
    }

    public function forecast_days_callback() {
        $settings = get_option( 'sfw_weather_settings' );
        $forecast_days = isset( $settings['forecast_days'] ) ? $settings['forecast_days'] : 5;
        ?>
        <select name="sfw_weather_settings[forecast_days]">
            <?php for ( $i = 1; $i <= 7; $i++ ) : ?>
                <option value="<?php echo esc_attr( $i ); ?>" <?php selected( $forecast_days, $i ); ?>><?php echo esc_html( $i ); ?></option>
            <?php endfor; ?>
        </select>
        <p class="description"><?php esc_html_e( 'Number of forecast days to display (1 for current conditions only, up to 7).', 'strategicli-family-weather' ); ?></p>
        <?php
    }

    public function cache_duration_callback() {
        $settings = get_option( 'sfw_weather_settings' );
        $cache_duration = isset( $settings['cache_duration'] ) ? $settings['cache_duration'] : 30; // In minutes
        ?>
        <input type="number" name="sfw_weather_settings[cache_duration]" value="<?php echo esc_attr( $cache_duration ); ?>" min="5" max="1440" class="small-text" />
        <p class="description"><?php esc_html_e( 'How long to cache weather data, in minutes (min 5, max 1440 - 24 hours).', 'strategicli-family-weather' ); ?></p>
        <?php
    }
	
	public function shortcode_section_callback() {
        esc_html_e( 'Use the following shortcodes to display the weather widget on your posts or pages.', 'strategicli-family-weather' );
    }
	
	/**
     * Callback for the Display Options section.
     */
    public function display_section_callback() {
        esc_html_e( 'Customize which weather information is displayed in the widget.', 'strategicli-family-weather' );
    }

    // EXISTING CALLBACKS FOR DISPLAY OPTIONS
    public function hide_location_name_callback() {
        $options = get_option( 'sfw_weather_settings' );
        // Default to not hidden (i.e., display it)
        $checked = isset( $options['hide_location_name'] ) ? checked( 1, $options['hide_location_name'], false ) : '';
        echo '<input type="checkbox" name="sfw_weather_settings[hide_location_name]" value="1"' . $checked . '>';
    }

    public function hide_current_time_callback() {
        $options = get_option( 'sfw_weather_settings' );
        // Default to not hidden (i.e., display it)
        $checked = isset( $options['hide_current_time'] ) ? checked( 1, $options['hide_current_time'], false ) : '';
        echo '<input type="checkbox" name="sfw_weather_settings[hide_current_time]" value="1"' . $checked . '>';
    }

    // NEW CALLBACK FOR HIDE TEMPERATURE UNIT LETTER
    public function hide_temp_unit_letter_callback() {
        $options = get_option( 'sfw_weather_settings' );
        $checked = isset( $options['hide_temp_unit_letter'] ) ? checked( 1, $options['hide_temp_unit_letter'], false ) : '';
        echo '<input type="checkbox" name="sfw_weather_settings[hide_temp_unit_letter]" value="1"' . $checked . '>';
    }

    public function hide_current_description_callback() {
        $options = get_option( 'sfw_weather_settings' );
        $checked = isset( $options['hide_current_description'] ) ? checked( 1, $options['hide_current_description'], false ) : '';
        echo '<input type="checkbox" name="sfw_weather_settings[hide_current_description]" value="1"' . $checked . '>';
    }

    public function hide_feels_like_callback() {
        $options = get_option( 'sfw_weather_settings' );
        $checked = isset( $options['hide_feels_like'] ) ? checked( 1, $options['hide_feels_like'], false ) : '';
        echo '<input type="checkbox" name="sfw_weather_settings[hide_feels_like]" value="1"' . $checked . '>';
    }

    public function hide_humidity_callback() {
        $options = get_option( 'sfw_weather_settings' );
        $checked = isset( $options['hide_humidity'] ) ? checked( 1, $options['hide_humidity'], false ) : '';
        echo '<input type="checkbox" name="sfw_weather_settings[hide_humidity]" value="1"' . $checked . '>';
    }

    public function hide_wind_speed_callback() {
        $options = get_option( 'sfw_weather_settings' );
        $checked = isset( $options['hide_wind_speed'] ) ? checked( 1, $options['hide_wind_speed'], false ) : '';
        echo '<input type="checkbox" name="sfw_weather_settings[hide_wind_speed]" value="1"' . $checked . '>';
    }

    public function hide_forecast_description_callback() {
        $options = get_option( 'sfw_weather_settings' );
        $checked = isset( $options['hide_forecast_description'] ) ? checked( 1, $options['hide_forecast_description'], false ) : '';
        echo '<input type="checkbox" name="sfw_weather_settings[hide_forecast_description]" value="1"' . $checked . '>';
    }

	
	public function shortcode_info_callback() {
        ?>
        <p><?php esc_html_e( 'Use the shortcode [sfw_weather] to display the weather widget on your pages or posts.', 'strategicli-family-weather' ); ?></p>
        <p><?php esc_html_e( 'You can customize its behavior with the following attributes:', 'strategicli-family-weather' ); ?></p>
        <ul>
            <li><code>location</code>: <?php esc_html_e( 'Override the default location. E.g., [sfw_weather location="New York, US"]', 'strategicli-family-weather' ); ?></li>
            <li><code>units</code>: <?php esc_html_e( 'Override the default units. Use "imperial" (Fahrenheit, mph) or "metric" (Celsius, m/s). E.g., [sfw_weather units="metric"]', 'strategicli-family-weather' ); ?></li>
            <li><code>forecast_days</code>: <?php esc_html_e( 'Override the default number of forecast days (1-7). E.g., [sfw_weather forecast_days="3"]', 'strategicli-family-weather' ); ?></li>
            <li><code>dark_mode</code>: <?php esc_html_e( 'Enable dark mode for the widget. Use "yes". E.g., [sfw_weather dark_mode="yes"]', 'strategicli-family-weather' ); ?></li> </ul>
        <p><?php esc_html_e( 'Example: [sfw_weather location="Waupun, WI, US" units="imperial" forecast_days="5" dark_mode="yes"]', 'strategicli-family-weather' ); ?></p>
        <?php
    }

    /**
     * Sanitize settings.
     * Add new fields to the sanitization process.
     */
    public function sanitize_settings( $input ) {
        $sanitized_input = array();

        if ( isset( $input['api_key'] ) ) {
            $sanitized_input['api_key'] = sanitize_text_field( $input['api_key'] );
        }
        if ( isset( $input['location'] ) ) {
            $sanitized_input['location'] = sanitize_text_field( $input['location'] );
        }
        if ( isset( $input['units'] ) && in_array( $input['units'], array( 'imperial', 'metric' ) ) ) {
            $sanitized_input['units'] = sanitize_text_field( $input['units'] );
        } else {
            $sanitized_input['units'] = 'imperial'; // Default
        }
        if ( isset( $input['cache_duration'] ) ) {
            $sanitized_input['cache_duration'] = absint( $input['cache_duration'] );
        } else {
            $sanitized_input['cache_duration'] = 30; // Default 30 minutes
        }
        if ( isset( $input['forecast_days'] ) ) {
            $days = absint( $input['forecast_days'] );
            $sanitized_input['forecast_days'] = ( $days >= 1 && $days <= 7 ) ? $days : 5; // Default 5 days
        } else {
            $sanitized_input['forecast_days'] = 5; // Default 5 days
        }

        // SANITIZE DISPLAY OPTIONS
        $sanitized_input['hide_location_name'] = isset( $input['hide_location_name'] ) ? 1 : 0;
        $sanitized_input['hide_current_time'] = isset( $input['hide_current_time'] ) ? 1 : 0;
        $sanitized_input['hide_temp_unit_letter'] = isset( $input['hide_temp_unit_letter'] ) ? 1 : 0; // NEW: Sanitize hide_temp_unit_letter
        $sanitized_input['hide_current_description'] = isset( $input['hide_current_description'] ) ? 1 : 0;
        $sanitized_input['hide_feels_like'] = isset( $input['hide_feels_like'] ) ? 1 : 0;
        $sanitized_input['hide_humidity'] = isset( $input['hide_humidity'] ) ? 1 : 0;
        $sanitized_input['hide_wind_speed'] = isset( $input['hide_wind_speed'] ) ? 1 : 0;
        $sanitized_input['hide_forecast_description'] = isset( $input['hide_forecast_description'] ) ? 1 : 0;

            // SANITIZE AUTO-REFRESH INTERVAL
            if ( isset( $input['auto_refresh_interval'] ) ) {
                $interval = absint( $input['auto_refresh_interval'] );
                $sanitized_input['auto_refresh_interval'] = ( $interval >= 0 && $interval <= 1440 ) ? $interval : 30;
            } else {
                $sanitized_input['auto_refresh_interval'] = 30;
            }

        return $sanitized_input;
    }
}