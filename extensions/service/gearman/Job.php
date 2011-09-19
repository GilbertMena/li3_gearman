<?php

namespace li3_gearman\extensions\service\gearman;

use li3_gearman\extensions\Gearman;

abstract class Job extends \lithium\core\Object {
	/**
	 * Work, work, work
	 *
	 * @return mixed
	 */
	public final static function execute() {
		$class = get_called_class();
		$job = new $class();
		return $job->work();
	}
	
	public final function work() {
		$this->_work();
	}
	
	public static function name() {
		$class = get_called_class();
		$name = substr($class, strrpos($class,'\\')+1);
		return $name;
	}
}