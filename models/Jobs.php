<?php

namespace li3_gearman\models;

use lithium\analysis\Logger;
use ErrorException;
use InvalidArgumentException;
//use MongoDate; //can be safely commented if using MySql and required if using Mongo
use lithium\data\Connections;

class Jobs extends \lithium\data\Model {
  /**
   * The maxium number of attempts a job will be retried before it is considered completely failed.
   */
  const MAX_ATTEMPTS = 25;
  
  /**
   * The maxium length of time to let a job be locked out before it is retried.
   */
  const MAX_RUN_TIME = '4 hours';
  
  /**
   * @var bool
   */
  public static $destroyFailedJobs = false;
  
  /**
   *
   */
  protected $entity;
  
  /**
   * @var int
   */
  public static $minPriority = null;
  
  /**
   * @var int
   */
  public static $maxPriority = null;
  
  /**
   * @var string
   */
  public $workerName;
  
  /**
   *@var string
   */
  protected static $dataSourceType;
  /*
   *@var bool
   *@description whether or not to delete the queued objects after completion from the main table
   */
  public static $storeObject = false;
  
  /*
   *@var string
   *@description The default data store id. Use _id for Mongo or id for MySQL, it is set automatically look at the setDataSourceType method to change the auto setting
   */
  public static $keyID = 'id';

  protected $_meta = array(
    'name' => null,
    'title' => null,
    'class' => null,
    'source' => 'delayed_jobs',
    'connection' => 'default',
    'initialized' => false
  );
  
  public function __construct()
  {
    //set the datasourcetype if not already set
    if(empty(self::$dataSourceType))
    {
      self::setDataSourceType();
    }
    
    $this->workerName = 'host:'.gethostname().' pid:'.getmypid();
  }
  
  public function setDataSourceType($table = null)
  {
    if(!empty($table))
    {
      $this->_meta['source'] = $table;
    }
    $config = Connections::get($this->_meta['connection'], array('config' => true));
    
    if($config['type']=='database')
    {
      if($config['adapter']=='MySql')
      {
        self::$dataSourceType = 'Database';
        self::$keyID = 'id';
        return;
      }else
      {
        throw new \ErrorException('Database adapter '.$config['adapter'].' is not currently supported.');
      }
    }
    
    throw new \ErrorException('Only database data source and mysql are supported.');
  }
  
  /**
   * Deserializes a string to an object.  If the 'perform' method doesn't exist, it throws an ErrorException
   *
   * @param $source string
   * @return object
   * @throws ErrorException
   */
  public static function deserialize($source) {
    $handler = unserialize(base64_decode($source));
    //$handler = json_decode($source);
    if(method_exists($handler, 'perform')) {
      return $handler;
    }
    
    throw new \ErrorException('Job failed to load: Unknown handler. Try to manually require the appropiate file.');
  }
  
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
      'attempts' => 0, 
      'handler' => base64_encode(serialize($object)),
      //'handler' => json_encode($object),
      'priority' => $priority, 
      //'run_at' => $runAt, 
      'completed_at' => null,
      'failed_at' => null, 
      'locked_at' => null, 
      'locked_by' => null,
      'last_error' => null, 
    );
    
    //need to instantiate the object first so that we can access the _meta instance property
    $job = Jobs::create($data);
    
    //set the datasourcetype if not already set
    if(empty(self::$dataSourceType))
    {
      $job->setDataSourceType($table);
    }
    
    if(!method_exists($object, 'perform')) {
      throw new ErrorException('Cannot enqueue items which do not respond to perform');
    }
    
    
    if($runAt==null)
    {
      $runAt = date('Y-m-d H:i:s') ;
    }else
    {
        //check the passed in datetime string for formatting 
      $pattern = '(\d{2}|\d{4})(?:\-)?([0]{1}\d{1}|[1]{1}[0-2]{1})(?:\-)?([0-2]{1}\d{1}|[3]{1}[0-1]{1})(?:\s)?([0-1]{1}\d{1}|[2]{1}[0-3]{1})(?::)?([0-5]{1}\d{1})(?::)?([0-5]{1}\d{1})';
      $patternValidation = preg_match($pattern,$runAt);
      if(!$patternValidation||!DateTime::createFromFormat('Y-m-d H:i:s', $runAt))
      {
        throw new ErrorException('The runAt parameter does not comform to mysql DATETIME Y-m-d H:i:s or is not a valid date');
      }
      
      
    }
    
    $data['run_at'] = $runAt;
    
    //hate to do it this way but need to for lack of a better option right now, im recreating the object so we have the correctn run_at
    $job = Jobs::create($data);
    
    return $job->save();
  }
  
  
  
  /**
   * Find and lock a job ready to be run
   *
   * @return bool|\lithium\data\entity\Document
   */
  public static function findJob($id, $maxRunTime = self::MAX_RUN_TIME, $table = null)
  {
    
    //set the datasourcetype if not already set
    if(empty(self::$dataSourceType))
    {
      self::setDataSourceType($table);
    }
    
    
      $conditions = array(
        'id'    => $id,
      );
      
      $limit = 1;
    

    return Jobs::all(compact('conditions', 'limit'));
  }
  
  /**
   * @param $message string
   */
  public static function reschedule($message,$id,$table=null) {
    
    //set the datasourcetype if not already set
    if(empty(self::$dataSourceType))
    {
      self::setDataSourceType($table);
    }

    $idKey = self::$keyID;
    $job = Jobs::findJob($id);
    if($job->attempts < self::MAX_ATTEMPTS) {
      $job->attempts += 1;
      $job->run_at = $time;
      $job->last_error = $message;
      $job->unlock();
      $job->entity->save();
    } else {
      Logger::info('* [JOB] PERMANENTLY removing '.$job->name.' because of '.$job->attempts.' consequetive failures.');
      if(Jobs::destroyFailedJobs) {
        Jobs::delete(array($idKey => $job->id));
      } else {
        $job->failed_at = date('Y-m-d H:i:s');
        $job->entity->save();
      }
    }
  }
  
  /**
   * Run the next job we can get an exclusive lock on.
   * If no jobs are left we return -1
   *
   * @return int
   */
  public static function reserveAndRunOneJob($job,$maxRunTime = self::MAX_RUN_TIME) {
      $t = static::runWithLock($job,$maxRunTime);
      if(!is_null($t)) {
        return $t;
      }
    
    
    return null;
  }
  
  public static function runWithLock($job,$maxRunTime, $workerName = null) {
    $workerName = $workerName ? $workerName : $job->id;  
    Logger::info('* [JOB] started running'.$job->id);

    try {
      $time_start = microtime(true);
      $result = $job->perform();
      $idKey = self::$keyID;
      if(self::$storeObject)
      {
        $time_now = date('Y-m-d H:i:s');

        
        $complete = Jobs::update(array('completed_at' => $time_now), array($idKey => $job->id));
      }else
      {
        static::remove(array($idKey => $job->id));
      }
      
      
      $time_end = microtime(true);
      $runtime = $time_end - $time_start;
      
      Logger::info(sprintf('* [JOB] '.$job->id.' completed after %.4f', $runtime));
      return true;
    } catch(Exception $e) {
      static::reschedule($e->getMessage(),$id);
      static::logException($e);
      return false;
    }
  }
  
  
  /**
   * Do num jobs and return stats on success/failure.
   *
   * @param $num    int
   * @return array
   */
  public static function workOff($job) {
    $success = $failure = 0;
    
    
      $result = static::reserveAndRunOneJob($job);
      
      if($result === true) {
        $success++;
      } else {
        $failure++;
      }
    
    
    return compact('success', 'failure');
  }
  
  public static function logException($e) {
    print_r($e);
    Logger::error('* [JOB] ',$this->name.' failed with '.$e->message());
    Logger::error($e);
  }
}