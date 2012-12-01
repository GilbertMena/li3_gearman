<?php

namespace li3_gearman\extensions\service\gearman;

use li3_gearman\extensions\Gearman;

abstract class Job extends \lithium\core\Object {
	/**
	 * @var GearmanJob 
	 */
	protected $order;

	abstract protected function _work();
	
	/**
	 * Work, work, work
	 *
	 * @return mixed
	 */
	public final static function execute(\GearmanJob $order) {
		try {
			$class = get_called_class();
			$job = new $class();
			$job->order($order);
			if($job->work()) {
				$order->sendComplete();
			} else {
				throw new \Exception('Order not completed');
			}
			
		} catch (\Exception $e) {
			$order->sendFail();
		}
	}
	
	public function getWorkLoad() {
		//take our serialized string and remove the id prepended by our mysql trigger
		if(preg_match('/(?P<id>\d+)\|\|\|(?P<object>.*)/',$this->order->workload(),$matches))
		{
			$id = $matches['id'];
			$object = unserialize($matches['object']);
			$object->id = $id;
			return $object;
		}
		//our default action for other methods
		return json_decode($this->order->workload());
	}
	
	/**
	 * This is called before _work().  This is a good spot
	 * to bootstrap anything needed for this job
	 */
	protected function _init() {
	}
	
	public function order(\GearmanJob $order) {
		$this->order = $order;
	}
	
	/**
	 * This is called after _work(). If you have any open connections
	 * they would be closed down here. Otherwise you can destruct any
	 * other objects you might have here.
	 */  
	protected function _shutdown() {
	
	}
	
	public final function work() {
		$this->_init();
		$this->_work();
		$this->_shutdown();
	}
	
	/**
	 * Determines the name of the job based on the class name.
	 *
	 * @return string
	 */
	public static function name() {
		$class = get_called_class();
		$name = substr($class, strrpos($class,'\\')+1);
		return $name;
	}
}