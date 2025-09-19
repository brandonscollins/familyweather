<?php
/**
 * Plugin Name: Strategicli Family Weather
 * Description: A simple, elegant weather widget for displaying current conditions and forecasts.
 * Version: 1.0.0
 * Author: Strategicli
 * Text Domain: strategicli-family-weather
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define constants if needed, e.g., plugin path, URL.
if ( ! defined( 'SFW_PLUGIN_DIR' ) ) {
    define( 'SFW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'SFW_PLUGIN_URL' ) ) {
    define( 'SFW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Require necessary files.
require_once SFW_PLUGIN_DIR . 'includes/class-sfw-weather.php';
require_once SFW_PLUGIN_DIR . 'includes/class-sfw-admin.php';
require_once SFW_PLUGIN_DIR . 'includes/class-sfw-api.php';
// Potentially require class-sfw-widget.php if you're making a traditional WordPress widget as well as shortcodes.

// Initialize the plugin classes.
// Using the singleton pattern as per your best practices.
add_action( 'plugins_loaded', 'sfw_run_weather_plugin' );
function sfw_run_weather_plugin() {
    \Strategicli\FamilyWeather\Weather::get_instance();
    \Strategicli\FamilyWeather\Admin::get_instance();
    // The API class will likely be instantiated by the Weather class when needed.
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-sfw-activator.php
 */
function activate_sfw_weather() {
    // Any activation tasks, e.g., setting default options.
}
register_activation_hook( __FILE__, 'activate_sfw_weather' );

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-sfw-deactivator.php
 */
function deactivate_sfw_weather() {
    // Any deactivation tasks, e.g., cleaning up transients.
}
register_deactivation_hook( __FILE__, 'deactivate_sfw_weather' );

/**
 * The code that runs during plugin uninstallation.
 * This file should be placed directly in the plugin's top-level directory.
 */
// For uninstallation, we'll create an uninstall.php file as per best practices.
// No direct uninstallation hook needed here, just the file itself.
?>