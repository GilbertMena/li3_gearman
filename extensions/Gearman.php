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
			static::$_configurations = $config + static::$_defaults;
			return;
		}
		
		return static::$_configurations + static::$_defaults;
	}
	
	public static function paths($path = null) {
		if (empty($path)) {
			return static::$_paths;
		}
		if (is_string($path)) {
			return isset(static::$_paths[$path]) ? static::$_paths[$path] : null;
		}
		static::$_paths = array_filter(array_merge(static::$_paths, (array) $path));
	}
}

?>
