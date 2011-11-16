<?php

namespace li3_gearman\extensions\service\gearman;

use li3_gearman\extensions\Gearman;
use lithium\data\Connection;

class Worker extends \lithium\core\StaticObject {
	public static function work() {
		$worker = new \GearmanWorker();
		
		$gearman = Connection::get('gearman');
		$config = Gearman::config();
		$worker->addServer($config['host'], $config['port']);
		
		$jobs = \lithium\core\Libraries::locate('job');
		foreach($jobs as $job) {
			$worker->addFunction($job::name(), array($job, 'execute'));
		}
		
		while ($worker->work()) {
			if($worker->returnCode() != GEARMAN_SUCCESS) {
				echo 'Worker failed: '.$worker->error().PHP_EOL;
			}
		}
	}
}