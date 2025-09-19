<?php
// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete plugin options from the database.
delete_option( 'sfw_weather_settings' );
// You might also delete any transients associated with weather data caching.
global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_sfw_weather_cache_%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_sfw_weather_cache_%'" );

// Add any other cleanup tasks here, e.g., deleting custom database tables if you had any.