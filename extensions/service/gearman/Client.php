<?php

namespace li3_gearman\extensions\service\gearman;

use li3_gearman\extensions\Gearman;

class Client extends \lithium\core\StaticObject {
	/**
	 * Holds the GearmanClient as singleton
	 * @var GearmanClient
	 */
	public static $_instance = null;

	protected static function _init() {
		self::$_instance = new \GearmanClient();
		
		$config = Gearman::config();
		self::$_instance->addServer($config['host'], $config['port']);
	}

	public static function queue($job, array $workload = array()) {
		if(!self::$_instance) {
			self::_init();
		}
		
		self::$_instance->doBackground($job, json_encode($workload));
	}
}