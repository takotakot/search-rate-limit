<?php
/**
 * Plugin Name: SearchRateLimit
 * Plugin URI: https://github.com/takotakot/search_rate_limit
 * Description: Search rate limit allows you to limit the rate of search access.
 * Version: 0.0.1
 * Author: takotakot
 * Author URI: https://github.com/takotakot/
 * License: MIT/X
 * Text Domain: searchratelimit
 *
 * @package SearchRateLimit
 */

// Make sure we don't expose any info if called directly.
if ( ! function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit();
}

define( 'SEARCHRATELIMIT_VERSION', '0.0.1' );
define( 'SEARCHRATELIMIT__PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SEARCHRATELIMIT__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once SEARCHRATELIMIT__PLUGIN_DIR . 'class-searchratelimit.php';
require_once SEARCHRATELIMIT__PLUGIN_DIR . 'class-searchratelimitconfig.php';

// phpcs:disable
// $srl_instance = new SearchRateLimit();
// phpcs:enable

add_action( 'init', array( 'SearchRateLimit', 'init' ) );

// phpcs:disable
// add_action( 'daily_search_rate_limit_update', 'daily_search_rate_limit_update_action' );
// phpcs:enable

// Register activation and deactivation hooks.
if ( function_exists( 'register_activation_hook' ) ) {
	register_activation_hook( __FILE__, array( 'SearchRateLimit', 'activation' ) );
}

// phpcs:disable
if ( function_exists( 'register_deactivation_hook' ) ) {}
// phpcs:enable
