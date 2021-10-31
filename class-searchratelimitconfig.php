<?php
/**
 * SearchRateLimit
 *
 * SearchRateLimit
 *
 * @package SearchRateLimit
 */

/**
 * SearchRateLimitConfig
 *
 * SearchRateLimitConfig singleton class.
 * See: https://qiita.com/buntafujikawa/items/2e8e8f13b6eb2f2f9e64
 */
class SearchRateLimitConfig {

	/**
	 * Singleton instance variable.
	 *
	 * @var SearchRateLimitConfig
	 */
	private static $instance;

	/**
	 * Config
	 *
	 * @var misc
	 */
	protected $config;  // TODO: private.

	/**
	 * Autoload setting
	 *
	 * @var boolean
	 */
	protected $autoload = false;

	/**
	 * Constructor
	 */
	final private function __construct() {  }

	/**
	 * Get instance for singleton class.
	 */
	public static function get_instance(): SearchRateLimitConfig {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new static();
		}
		self::$instance->load_config();

		return self::$instance;
	}

	/**
	 * Prohibit the execution of __clone.
	 *
	 * @throws \Exception Exception.
	 */
	final public function __clone() {
		throw new \Exception( sprintf( 'Clone is not allowed with class: %s .', get_class( $this ) ) );
	}

	/**
	 * Config setter.
	 *
	 * @param string $key   config key.
	 * @param misc   $value config value.
	 */
	public function set( $key, $value ) {
		$this->config[ $key ] = $value;
	}

	/**
	 * Config getter.
	 *
	 * @param string $key config key.
	 * @return misc
	 */
	public function get( $key ) {
		return isset( $this->config[ $key ] ) ? $this->config[ $key ] : '';
	}

	/**
	 * Save config from DB.
	 */
	public function save_config() {
		update_option( 'search_rate_limit_config', $this->config, $autoload );
	}

	/**
	 * Load config from DB or cache.
	 */
	public function load_config() {
		if ( ! isset( $this->config ) ) {
			$this->config = get_option( 'search_rate_limit_config' );
		}
	}
}
