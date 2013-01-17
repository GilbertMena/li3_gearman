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
  
  
}