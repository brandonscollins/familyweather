<?php
namespace Strategicli\FamilyWeather;

if (!defined('ABSPATH')) {
    exit;
}

class Widget extends \\WP_Widget {
    private $api;

    public function __construct($api = null) {
        $this->api = $api ?: new API();

        parent::__construct(
            'sfw_weather_widget',
            __('Strategicli Weather', 'strategicli-family-weather'),
            array(
                'description' => __('Display weather information', 'strategicli-family-weather'),
                'customize_selective_refresh' => true,
            )
        );
    }

    public function widget($args, $instance) {
        echo $args['before_widget'];

        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }

        $type = !empty($instance['type']) ? $instance['type'] : 'full';
        $theme = !empty($instance['theme']) ? $instance['theme'] : 'light';

        echo $this->render_weather_widget($type, $theme);

        echo $args['after_widget'];
    }

    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '';
        $type = !empty($instance['type']) ? $instance['type'] : 'full';
        $theme = !empty($instance['theme']) ? $instance['theme'] : 'light';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                <?php _e('Title:', 'strategicli-family-weather'); ?>
            </label>
            <input class="widefat"
                   id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>"
                   type="text"
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('type')); ?>">
                <?php _e('Display Type:', 'strategicli-family-weather'); ?>
            </label>
            <select class="widefat"
                    id="<?php echo esc_attr($this->get_field_id('type')); ?>"
                    name="<?php echo esc_attr($this->get_field_name('type')); ?>">
                <option value="full" <?php selected($type, 'full'); ?>>
                    <?php _e('Full Weather', 'strategicli-family-weather'); ?>
                </option>
                <option value="current" <?php selected($type, 'current'); ?>>
                    <?php _e('Current Only', 'strategicli-family-weather'); ?>
                </option>
                <option value="forecast" <?php selected($type, 'forecast'); ?>>
                    <?php _e('Forecast Only', 'strategicli-family-weather'); ?>
                </option>
            </select>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('theme')); ?>">
                <?php _e('Theme:', 'strategicli-family-weather'); ?>
            </label>
            <select class="widefat"
                    id="<?php echo esc_attr($this->get_field_id('theme')); ?>"
                    name="<?php echo esc_attr($this->get_field_name('theme')); ?>">
                <option value="light" <?php selected($theme, 'light'); ?>>
                    <?php _e('Light', 'strategicli-family-weather'); ?>
                </option>
                <option value="dark" <?php selected($theme, 'dark'); ?>>
                    <?php _e('Dark', 'strategicli-family-weather'); ?>
                </option>
            </select>
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['type'] = (!empty($new_instance['type'])) ? sanitize_text_field($new_instance['type']) : 'full';
        $instance['theme'] = (!empty($new_instance['theme'])) ? sanitize_text_field($new_instance['theme']) : 'light';
        return $instance;
    }

    // Shortcode handlers
    public function render_current_weather($atts) {
        $atts = shortcode_atts(array(
            'theme' => 'light'
        ), $atts, 'sfw_current');

        return $this->render_weather_widget('current', $atts['theme']);
    }

    public function render_forecast($atts) {
        $atts = shortcode_atts(array(
            'theme' => 'light'
        ), $atts, 'sfw_forecast');

        return $this->render_weather_widget('forecast', $atts['theme']);
    }

    public function render_full_weather($atts, $content = '', $return_html = false) {
        $atts = shortcode_atts(array(
            'theme' => 'light'
        ), $atts, 'sfw_weather');

        return $this->render_weather_widget('full', $atts['theme']);
    }

    // Block render callback
    public function render_block($attributes) {
        $type = $attributes['type'] ?? 'full';
        $theme = $attributes['theme'] ?? 'light';

        return $this->render_weather_widget($type, $theme);
    }

    private function render_weather_widget($type = 'full', $theme = 'light') {
        $weather_data = $this->api->get_weather_data();

        if (is_wp_error($weather_data)) {
            return $this->render_error($weather_data->get_error_message());
        }

        $theme_class = $theme === 'dark' ? 'sfw-dark-mode' : '';

        ob_start();
        ?>
        <div class="sfw-wrapper <?php echo esc_attr($theme_class); ?>">
            <div class="sfw-container">
                <?php if ($type === 'current' || $type === 'full'): ?>
                    <?php echo $this->render_current_section($weather_data); ?>
                <?php endif; ?>

                <?php if ($type === 'forecast' || $type === 'full'): ?>
                    <?php echo $this->render_forecast_section($weather_data); ?>
                <?php endif; ?>

                <div class="sfw-footer">
                    <span class="sfw-last-updated">
                        <?php
                        printf(
                            __('Updated %s', 'strategicli-family-weather'),
                            human_time_diff($weather_data['last_updated'], current_time('timestamp'))
                        );
                        ?>
                    </span>
                    <button class="sfw-refresh-btn" data-sfw-refresh>
                        <span class="sfw-refresh-icon"><?php echo $this->get_icon('refresh'); ?></span>
                        <?php _e('Refresh', 'strategicli-family-weather'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_current_section($weather_data) {
        $current = $weather_data['current'];
        $units = $weather_data['units'];
        $temp_unit = $units === 'imperial' ? '°F' : '°C';
        $wind_unit = $units === 'imperial' ? 'mph' : 'm/s';

        ob_start();
        ?>
        <div class="sfw-current">
            <div class="sfw-location">
                <h3><?php echo esc_html($current['city'] . ', ' . $current['country']); ?></h3>
            </div>

            <div class="sfw-main-weather">
                <div class="sfw-temp-container">
                    <span class="sfw-temp"><?php echo esc_html($current['temp']); ?></span>
                    <span class="sfw-unit"><?php echo esc_html($temp_unit); ?></span>
                </div>
                <div class="sfw-weather-icon">
                    <?php echo $this->get_weather_icon($current['icon']); ?>
                </div>
            </div>

            <div class="sfw-description">
                <?php echo esc_html($current['description']); ?>
            </div>

            <div class="sfw-details">
                <div class="sfw-detail-item">
                    <span class="sfw-detail-label"><?php _e('Feels like', 'strategicli-family-weather'); ?></span>
                    <span class="sfw-detail-value">
                        <?php echo esc_html($current['feels_like'] . $temp_unit); ?>
                    </span>
                </div>
                <div class="sfw-detail-item">
                    <span class="sfw-detail-label"><?php _e('Humidity', 'strategicli-family-weather'); ?></span>
                    <span class="sfw-detail-value"><?php echo esc_html($current['humidity']); ?>%</span>
                </div>
                <div class="sfw-detail-item">
                    <span class="sfw-detail-label"><?php _e('Wind', 'strategicli-family-weather'); ?></span>
                    <span class="sfw-detail-value">
                        <?php echo esc_html($current['wind_speed'] . ' ' . $wind_unit); ?>
                    </span>
                </div>
            </div>

            <div class="sfw-sun-times">
                <div class="sfw-sunrise">
                    <?php echo $this->get_icon('sunrise'); ?>
                    <span><?php echo date_i18n('g:i a', $current['sunrise']); ?></span>
                </div>
                <div class="sfw-sunset">
                    <?php echo $this->get_icon('sunset'); ?>
                    <span><?php echo date_i18n('g:i a', $current['sunset']); ?></span>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_forecast_section($weather_data) {
        $forecast = $weather_data['forecast'];
        $units = $weather_data['units'];
        $temp_unit = $units === 'imperial' ? '°F' : '°C';

        ob_start();
        ?>
        <div class="sfw-forecast">
            <h4 class="sfw-forecast-title"><?php _e('Forecast', 'strategicli-family-weather'); ?></h4>
            <div class="sfw-forecast-days">
                <?php foreach ($forecast as $day): ?>
                    <div class="sfw-forecast-day">
                        <div class="sfw-forecast-date">
                            <?php echo date_i18n('D', $day['date']); ?>
                        </div>
                        <div class="sfw-forecast-icon">
                            <?php echo $this->get_weather_icon($day['icon']); ?>
                        </div>
                        <div class="sfw-forecast-temps">
                            <span class="sfw-temp-high">
                                <?php echo esc_html($day['temp_max'] . '°'); ?>
                            </span>
                            <span class="sfw-temp-low">
                                <?php echo esc_html($day['temp_min'] . '°'); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_error($message) {
        ob_start();
        ?>
        <div class="sfw-wrapper sfw-error">
            <div class="sfw-container">
                <div class="sfw-error-message">
                    <?php echo $this->get_icon('error'); ?>
                    <p><?php echo esc_html($message); ?></p>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_weather_icon($icon_code) {
        // Map OpenWeatherMap icon codes to our SVG icons
        $icon_map = array(
            '01d' => 'sun',
            '01n' => 'moon',
            '02d' => 'cloud-sun',
            '02n' => 'cloud-moon',
            '03d' => 'cloud',
            '03n' => 'cloud',
            '04d' => 'clouds',
            '04n' => 'clouds',
            '09d' => 'rain',
            '09n' => 'rain',
            '10d' => 'rain-sun',
            '10n' => 'rain-moon',
            '11d' => 'storm',
            '11n' => 'storm',
            '13d' => 'snow',
            '13n' => 'snow',
            '50d' => 'mist',
            '50n' => 'mist'
        );

        $icon_name = isset($icon_map[$icon_code]) ? $icon_map[$icon_code] : 'sun';

        return $this->get_icon($icon_name);
    }

    private function get_icon($name) {
        // Return inline SVG icons
        $icons = array(
            'sun' => '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="5"/><path d="M12 1v6M12 17v6M4.22 4.22l4.24 4.24M15.54 15.54l4.24 4.24M1 12h6M17 12h6M4.22 19.78l4.24-4.24M15.54 8.46l4.24-4.24"/></svg>',
            'moon' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>',
            'cloud' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/></svg>',
            'cloud-sun' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/><path d="M15.3 10H9a7 7 0 0 0 0 14h9a4 4 0 0 0 .7-7.93 5.5 5.5 0 0 0-10.4-2.6"/></svg>',
            'rain' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/><path d="M7 19v2M11 19v2M15 19v2"/></svg>',
            'storm' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 16.9A5 5 0 0 0 18 7h-1.26a8 8 0 1 0-11.62 9"/><path d="M13 11l-4 6h6l-4 6"/></svg>',
            'snow' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/><circle cx="8" cy="18" r="1"/><circle cx="12" cy="18" r="1"/><circle cx="16" cy="18" r="1"/></svg>',
            'mist' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M5 12h14M5 8h14M5 16h14"/></svg>',
            'refresh' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 4v6h6M23 20v-6h-6"/><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"/></svg>',
            'sunrise' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v6M4.93 10.93l1.41 1.41M2 18h2M20 18h2M19.07 10.93l-1.41 1.41M22 22H2M8 6l4-4 4 4M16 18a4 4 0 0 0-8 0"/></svg>',
            'sunset' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 10V2M4.93 10.93l1.41 1.41M2 18h2M20 18h2M19.07 10.93l-1.41 1.41M22 22H2M16 5l-4 4-4-4M16 18a4 4 0 0 0-8 0"/></svg>',
            'error' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>'
        );

        return isset($icons[$name]) ? $icons[$name] : $icons['sun'];
    }
}
