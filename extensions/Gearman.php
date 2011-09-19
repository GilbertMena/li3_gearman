<?php

namespace li3_gearman\extensions;

class Gearman extends \lithium\core\StaticObject {
	/**
	 * Holds the configuration Options
	 * @var array
	 */
	protected static $_configurations = array();
	
	/**
	 * These are the class `defaults`
	 * @var array
	 */
	protected static $_defaults = array(
		'host' => '127.0.0.1',
		'port' => '4730',
	);
	
	public function __construct($config = array()) {
		if ($config){
			static::config($config);
		}
	}
	
	/**
	 * Sets configurations
	 *
	 * @param array $config Configurations, indexed by name.
	 * @return object `Collection` of configurations or void if setting configurations.
	 */
	public static function config($config = null) {
		if ($config && is_array($config)) {
			static::$_configurations = $config;
			return;
		}
		if ($config) {
			return static::_config($config);
		}
		$result = array();
		static::$_configurations = array_filter(static::$_configurations);

		foreach (array_keys(static::$_configurations) as $key) {
			$result[$key] = static::_config($key);
		}
		return $result;
	}
	
	/**
	 * Gets an array of settings for the given named configuration in the current
	 * environment.
	 *
	 * @see lithium\core\Environment
	 * @param string $name Named configuration.
	 * @return array Settings for the named configuration.
	 */
	protected static function _config($name) {
		if (!isset(static::$_configurations[$name])) {
			return null;
		}
		$settings = static::$_configurations[$name];


		$env = Environment::get();
		$config = isset($settings[$env]) ? $settings[$env] : $settings;

		if (isset($settings[$env]) && isset($settings[true])) {
			$config += $settings[true];
		}
		static::$_configurations[$name] += array($config + static::$_defaults);
		return static::$_configurations[$name][0];
	}
}

?>
