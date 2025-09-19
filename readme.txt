=== Strategicli Family Weather ===
Contributors: strategicli
Tags: weather, widget, forecast, openweathermap, dashboard
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: <https://www.gnu.org/licenses/gpl-2.0.html>

A simple, standalone weather widget for WordPress that displays current conditions and forecasts using OpenWeatherMap API.

== Description ==

Strategicli Family Weather is part of the Strategicli Family suite of dashboard widgets. This plugin works independently and doesn't require any other plugins to function.

Display beautiful, real-time weather information on your WordPress site with customizable widgets and shortcodes.

**Features:**

* Current weather conditions with temperature, humidity, and wind
* Multi-day forecast (1-7 days)
* Multiple display options (full, current only, forecast only)
* Light and dark themes
* Automatic updates with customizable intervals
* Caching to minimize API calls
* Responsive design
* Works with all major page builders
* No external dependencies

**Shortcodes:**

* `[sfw_weather]` - Displays full weather widget
* `[sfw_current]` - Shows current weather only
* `[sfw_forecast]` - Shows forecast only
* `[sfw_weather theme="dark"]` - Dark theme variant

**Requirements:**

* Free OpenWeatherMap API key
* WordPress 5.8 or higher
* PHP 7.4 or higher

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Get your free API key from [OpenWeatherMap](<https://openweathermap.org/api>)
4. Go to Settings > Strategicli Weather and enter your API key and location
5. Add weather to any page using the shortcodes or widget

== Frequently Asked Questions ==

= How do I get an OpenWeatherMap API key? =

1. Visit [OpenWeatherMap](<https://openweathermap.org/api>)
2. Sign up for a free account
3. Generate an API key in your account dashboard
4. Copy the key to the plugin settings

= What location formats are supported? =

You can use:
* City name with country code (e.g., "London, UK")
* US zip codes (e.g., "10001")
* City name alone (e.g., "Paris")

= How often does the weather update? =

By default, weather data is cached for 30 minutes and the page auto-refreshes every 30 minutes. You can adjust both settings in the plugin options.

= Can I style the weather widget? =

Yes! The widget uses CSS custom properties (variables) that you can override in your theme. All classes are prefixed with `sfw-` for easy targeting.

= Does this work with page builders? =

Yes, the plugin is tested with popular page builders including Elementor, Divi, and Gutenberg. Just use the provided shortcodes.

== Screenshots ==

1. Weather widget showing current conditions and forecast
2. Dark theme variant
3. Settings page
4. Widget configuration options

== Changelog ==

= 1.0.0 =
* Initial release
* Current weather display
* 1-7 day forecast
* Light and dark themes
* Automatic refresh functionality
* Caching system
* Widget and shortcode support

== Upgrade Notice ==

= 1.0.0 =
Initial release of Strategicli Family Weather plugin.
