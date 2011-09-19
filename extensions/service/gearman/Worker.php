<?php

namespace li3_gearman\extensions\service\gearman;

use li3_gearman\extensions\Gearman;

class Worker extends \lithium\core\StaticObject {
	public static function work() {
		$worker = new \GearmanWorker();
		
		$config = Gearman::config();
		
		$worker->addServer($config['host'], $config['port']);
		
		while ($worker->work()) {
			if($worker->returnCode() != GEARMAN_SUCCESS) {
				echo 'Worker failed: '.$worker->error().PHP_EOL;
			}
		}
	}
}