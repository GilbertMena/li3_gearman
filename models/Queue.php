<?php

namespace li3_gearman\models;

use lithium\analysis\Logger;
use ErrorException;
use InvalidArgumentException;
//use MongoDate; //can be safely commented if using MySql and required if using Mongo
use lithium\data\Connections;

class Queue extends \li3_gearman\models\Jobs {
	
	protected $_meta = array(
    'name' => null,
    'title' => null,
    'class' => null,
    'source' => 'queue',
    'connection' => 'default',
    'initialized' => false
  );
	
	public static $storeObject = true;

  /**
   * Add a job to the queue
   *
   * @param $job stdClass
   * @param $priority int
   * @param $runAt MongoDate|string
   * @return bool
   * @throws ErrorException
   */
  public static function enqueue($object, $priority = 0, $runAt = null, $table = null) {
    
    $data = array(
      'originating_system_id' => $object->_originatingSystemID,
	  'originating_action_id' => $object->_originatingActionID, 
      'request_object' => serialize($object),
      
    );
	if(!method_exists($object, 'perform')) {
      throw new ErrorException('Cannot enqueue items which do not respond to perform');
    }
    
    //need to instantiate the object first so that we can access the _meta instance property
    $job = Queue::create($data);
    
    
    
    return $job->save();
  }
  
  public static function runWithLock($job,$maxRunTime, $workerName = null) {
    $workerName = $workerName ? $workerName : $job->id;  
    Logger::info('* [JOB] started running'.$job->id);

    try {
      $time_start = microtime(true);
      $result = $job->perform();
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