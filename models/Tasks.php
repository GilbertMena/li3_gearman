<?php

namespace li3_gearman\models;

use lithium\analysis\Logger;
use ErrorException;
use InvalidArgumentException;
//use MongoDate; //can be safely commented if using MySql and required if using Mongo
use lithium\data\Connections;

class Tasks extends \li3_gearman\models\Jobs {
	
	protected $_meta = array(
    'name' => null,
    'title' => null,
    'class' => null,
    'source' => 'tasks',
    'connection' => 'default',
    'initialized' => false
  );

  public static function runWithLock($job,$maxRunTime, $workerName = null) {
    $workerName = $workerName ? $workerName : $job->id;  
    Logger::info('* [JOB] started running'.$job->id);

    try {
      $time_start = microtime(true);
      $result = $job->pushObject();
	  
	  //here we must check the result from the perform method and update the table accordingly
	  //var_dump($result); exit;
	  
      $idKey = static::$keyID;
	  $time_now = date('Y-m-d H:i:s');
      if(static::$storeObject)
      {
        

        
        $complete = static::update(array('task_list_date' => $time_now,'task_list_created'=>true), array($idKey => $job->id));
      }else
      {
		$complete = static::update(array('task_list_date' => $time_now,'task_list_created'=>true), array($idKey => $job->id));
        static::remove(array($idKey => $job->id));
      }
      
      
      $time_end = microtime(true);
      $runtime = $time_end - $time_start;
      
      Logger::info(sprintf('* [JOB] '.$job->id.' completed after %.4f', $runtime));
      return true;
    } catch(Exception $e) {
      Queue::reschedule($e->getMessage(),$id);
      Queue::logException($e);
      return false;
    }
  }
  
  
}