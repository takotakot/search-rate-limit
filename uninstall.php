<?php
/**
 * Uninstall procedure
 *
 * @package SearchRateLimit
 */

/** Check whether uninstalling or not */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

define( 'SEARCHRATELIMIT__PLUGIN_DIR_UNINSTALL', plugin_dir_path( __FILE__ ) );

require_once SEARCHRATELIMIT__PLUGIN_DIR_UNINSTALL . 'class-searchratelimit.php';
require_once SEARCHRATELIMIT__PLUGIN_DIR_UNINSTALL . 'class-searchratelimitconfig.php';

$search_rate_limit_config = \SearchRateLimitConfig::get_instance();
$search_rate_limit_config->delete_option();

\SearchRateLimit::uninstall();
