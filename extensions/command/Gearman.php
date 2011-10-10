<?php
/*
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */
 
namespace li3_gearman\extensions\command;

use li3_gearman\extensions\service\gearman\Client;
use li3_gearman\extensions\service\gearman\Worker;

/**
 * Gearman encapsulates the common pattern of asynchronously
 * executing longer tasks in the background.
 */
class Gearman extends \lithium\console\Command {
	/**
	 * Start a gearman worker.
	 *
	 * @param $quiet          bool
	 */
	public function work($quiet = false) {
		Worker::work();
	}
	
	/**
	 * Queues a job
	 *
	 * @param string $job The name of the job
	 * @param string $payload JSON Serialized string of the workload 
	 */
	public function queue($job, $payload = '') {
		$workload = array();
		
		if($payload) {
			$workload = json_decode($payload, true);
			
			if(json_last_error() != JSON_ERROR_NONE) {
				echo 'Job not queued. There was an error with your payload'.PHP_EOL;
				exit();
			}
		}
		
		Client::queue($job, $workload);
	}
}