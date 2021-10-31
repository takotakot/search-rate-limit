<?php
/**
 * SearchRateLimit
 *
 * Main class for SearchRateLimit
 *
 * @package SearchRateLimit
 */

/**
 * SearchRateLimit
 *
 * SearchRateLimit class.
 */
class SearchRateLimit {

	/**
	 * For IP mode.
	 *
	 * @var array
	 */
	public static $ip_mode_items = array( '0', '1', '2', '3' );

	/**
	 * Initialization status.
	 *
	 * @var bool
	 */
	private static $initialized = false;

	/**
	 * Check this access is search or not and checked once.
	 *
	 * @var bool
	 */
	private static $is_search_checked = false;

	/**
	 * Current date and time.
	 *
	 * @var string|null
	 */
	private static $current_datetime = null;

	/**
	 * Table name constant.
	 *
	 * @var string
	 */
	const ACCESS_COUNT_TABLE = 'srl_search_access_counts';

	/**
	 * Initialization function.
	 */
	public static function init() {
		if ( ! self::$initialized ) {
			self::init_hooks();
		}
	}

	/**
	 * Initializes WordPress hooks.
	 */
	private static function init_hooks() {
		self::$initialized      = true;
		self::$current_datetime = current_time( 'mysql' );
		add_action( 'parse_query', array( 'SearchRateLimit', 'hook_parse_query' ) );
	}

	/**
	 * Get saved datetime value.
	 *
	 * @return string
	 */
	public static function get_saved_datetime() {
		if ( is_null( self::$current_datetime ) ) {
			self::$current_datetime = current_time( 'mysql' );
		}
		return self::$current_datetime;
	}

	/**
	 * Run when this plugin is activated.
	 */
	public static function activation() {
		self::create_access_count_table();
	}

	/**
	 * Run when this plugin is uninstalled.
	 *
	 * TODO: To be implemented.
	 */
	public static function uninstall() {
	}

	/**
	 * TODO: table deletion for removal
	 */

	/**
	 * Create access count table.
	 */
	public static function create_access_count_table() {
		global $wpdb;

		$table_search_rate_limit = $wpdb->prefix . self::ACCESS_COUNT_TABLE;
		$charset_collate         = $wpdb->get_charset_collate();

		require_once ABSPATH . '/wp-admin/includes/upgrade.php';
		$sql = "
			CREATE TABLE `{$table_search_rate_limit}` (
			 `ip` varchar(39) NOT NULL,
			 `accessed_time` datetime NOT NULL,
			 `count` int NOT NULL DEFAULT 0,
			 PRIMARY KEY  (`ip`, `accessed_time`),
			 KEY `accessed_time` (`accessed_time`)
			) {$charset_collate};";
		dbDelta( $sql );
	}

	/**
	 * Update access count.
	 *
	 * @param string $ip IP.
	 * @param string $time time.
	 */
	public static function update_access_count( $ip, $time ) {
		global $wpdb;

		$table_search_rate_limit = $wpdb->prefix . self::ACCESS_COUNT_TABLE;

		// Insert new record with 1.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql_insert_one = $wpdb->prepare(
			"
			INSERT INTO {$table_search_rate_limit} (ip, accessed_time, count)
			VALUES (%s, %s, 1);
			",
			$ip,
			$time
		);

		// Update.
		$sql_update = $wpdb->prepare(
			"
			UPDATE {$table_search_rate_limit} AS srl
			SET count = count + 1
			 WHERE
			 ip = %s
			AND
			 accessed_time = %s
			;
			",
			$ip,
			$time
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$result = $wpdb->query( $sql_insert_one );
		if ( false === $result ) {
			// Error.
			return;
		} elseif ( 0 === $result ) {
			// Update when insert failed.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( $sql_update );
		}
	}

	/**
	 * Get access count list
	 *
	 * @param string $ip IP.
	 * @param string $time time.
	 * @param int    $range_second Time range in seconds.
	 *
	 * @return array
	 */
	public static function get_access_count_list( $ip, $time, $range_second = 1800 ) {
		global $wpdb;

		$table_search_rate_limit = $wpdb->prefix . self::ACCESS_COUNT_TABLE;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql_count = $wpdb->prepare(
			"
			SELECT accessed_time, count FROM {$table_search_rate_limit} AS srl
			 WHERE
			 ip = %s
			AND
			 accessed_time BETWEEN DATE_SUB(%s, INTERVAL %d SECOND) AND %s
			;
			",
			$ip,
			$time,
			$range_second,
			$time
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$result = $wpdb->get_results( $sql_count, ARRAY_A );
		return $result;
	}

	/**
	 * Get access count sums
	 *
	 * TODO: debug
	 *
	 * @param string $ip IP.
	 * @param string $time time.
	 * @param int    $ranges_second Time ranges in seconds.
	 *
	 * @return array
	 */
	public static function get_access_count_sums( $ip, $time, $ranges_second = array( 1800 ) ) {
		global $wpdb;

		if ( ! is_array( $ranges_second ) ) {
			$ranges_second = array( intval( $ranges_second ) );
		}

		$table_search_rate_limit = $wpdb->prefix . self::ACCESS_COUNT_TABLE;

		$values_ranges = array();

		/* phpcs:ignore Squiz.PHP.CommentedOutCode.Found
		// Keep lines for the future.
		foreach ( $ranges_second as $sec ) {
			$values_ranges[] = sprintf( '(%d)', $sec );
		}
		$values_ranges = ' VALUES ' . implode( ', ', $values_ranges );
		*/
		foreach ( $ranges_second as $sec ) {
			$values_ranges[] = sprintf( '%d', $sec );
		}
		$values_ranges = 'SELECT ' . implode( ' UNION SELECT ', $values_ranges );

		$sql_count = "
			SELECT
				  r.1
				, (
						SELECT sum(count)
						  FROM {$table_search_rate_limit} AS srl
							WHERE
							  srl.ip = %s
							AND
								srl.accessed_time BETWEEN DATE_SUB(%s, INTERVAL r.1 SECOND) AND %s
					) AS count
			  FROM ({$values_ranges}) AS r
		";

		$sql_count = $wpdb->prepare(
			$sql_count,  // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$ip,
			$time,
			$time
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$result = $wpdb->get_results( $sql_count, ARRAY_A );
		return $result;
	}

	/**
	 * Delete old access counts
	 *
	 * @param string $time time.
	 * @param int    $range_second Time range in seconds.
	 */
	public static function delete_access_count( $time, $range_second = 7200 ) {
		global $wpdb;

		$table_search_rate_limit = $wpdb->prefix . self::ACCESS_COUNT_TABLE;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql_delete = $wpdb->prepare(
			"
			DELETE FROM {$table_search_rate_limit}
				WHERE
					accessed_time < DATE_SUB(%s, INTERVAL %d SECOND)
			;
			",
			$time,
			$range_second
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( $sql_delete );
	}

	/**
	 * Registered and called by 'parse_query'.
	 *
	 * @param  WP_QUERY $query object.
	 * @return WP_QUERY
	 */
	public static function hook_parse_query( $query ) {
		if ( true === self::$is_search_checked ) {
			return $query;
		}
		self::$is_search_checked = true;

		if ( $query->is_search() ) {
			self::rate_limit( $query );
		}

		return $query;
	}

	/**
	 * Public helper function for determining specific IP is private IP or not.
	 *
	 * @param string $ipv4 Input IPv4 string.
	 * @return bool
	 */
	public static function is_private_ipv4( $ipv4 ) {
		$private_ips = array(
			array( '10.0.0.0', '10.255.255.255' ),
			array( '172.16.0.0', '172.31.255.255' ),
			array( '192.168.0.0', '192.168.255.255' ),
		);

		/** See: https://www.php.net/manual/en/function.ip2long.php */
		$ip_long = ip2long( $ipv4 );
		if ( -1 !== $ip_long && false !== $ip_long ) {
			$ip_long = sprintf( '%u', $ip_long );
			foreach ( $private_ips as $private_ip ) {
				$long_start = sprintf( '%u', ip2long( $private_ip[0] ) );
				$long_end   = sprintf( '%u', ip2long( $private_ip[1] ) );
				if ( $long_start <= $ip_long && $ip_long <= $long_end ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Get remote IP considering config and HTTP_X_FORWARDED_FOR
	 *
	 * @return string $ip
	 */
	public static function get_ip() {
		$search_rate_limit_config = \SearchRateLimitConfig::get_instance();
		$ip_mode_num              = intval( $search_rate_limit_config->get( 'ip_mode' ) );
		if ( $ip_mode_num < 0 ) {
			$ip_mode_num = 0;
			$search_rate_limit_config->set( 'ip_mode', '0' );
			$search_rate_limit_config->update();
		}
		$localhost = '127.0.0.1';

		/**
		 * If $_SERVER['REMOTE_ADDR'] is not set or is not valid, return localhost.
		 */
		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$remote_addr = $_SERVER['REMOTE_ADDR'];  // phpcs:ignore
			if ( ! filter_var( $remote_addr, FILTER_VALIDATE_IP ) ) {
				return $localhost;
			}
		} else {
			return $localhost;
		}
		if ( 0 === $ip_mode_num ) {
			return $remote_addr;
		}
		if ( empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			return $remote_addr;
		}

		$http_x_forwarded_for = $_SERVER['HTTP_X_FORWARDED_FOR'];  // phpcs:ignore
		$ips                  = explode( ',', $http_x_forwarded_for );
		$count                = count( $ips );
		$index                = $count - $ip_mode_num;
		if ( $index < 0 ) {
			return $remote_addr;
		}
		$ip = $ips[ $index ];
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return $remote_addr;
		}
		return $ip;
	}

	/**
	 * Rate limit for current remote IP.
	 *
	 * @param WP_QUERY $query object.
	 */
	private static function rate_limit( $query ) {
		$ip = self::get_ip();

		if ( self::is_private_ipv4( $ip ) ) {
			return;
		}

		self::update_access_count( $ip, self::get_saved_datetime() );

		if ( self::too_many_access( $ip ) ) {
			self::return_429_or_404( $query );
		}

		self::delete_access_count( self::get_saved_datetime() );
	}

	/**
	 * Check rate limit status with access count for $ip.
	 *
	 * @param  string $ip IP.
	 * @return bool
	 */
	private static function too_many_access( $ip ) {
		$access_count_limit       = array();
		$access_count_limit[60]   = 10;
		$access_count_limit[1800] = 50;

		$access_count_sums = self::get_access_count_sums( $ip, self::get_saved_datetime(), array_keys( $access_count_limit ) );

		$return_too_many_access = false;
		foreach ( $access_count_sums as $sum ) {
			if ( ! is_null( $sum[1] ) && ! is_null( $access_count_limit[ intval( $sum[1] ) ] ) ) {
				if ( $access_count_limit[ intval( $sum[1] ) ] < intval( $sum['count'] ) ) {
					$return_too_many_access = true;
				}
			}
		}

		return $return_too_many_access;
	}

	/**
	 * Display 429 "Too Many Requests" and exit or set 404.
	 *
	 * Display 429 error.
	 * TODO: Add 404 mode.
	 *
	 * @param WP_QUERY $query object.
	 */
	private static function return_429_or_404( $query ) {
		status_header( 429 );
		nocache_headers();

		$query->set( 'error', 429 );

		$display_404 = false;

		if ( $display_404 ) {
			$query->set_404();
		} else {
			echo 'Too Many Requests';
			exit;
		}
	}
}
