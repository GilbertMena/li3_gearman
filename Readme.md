# Gearman Plugin for the  Lithium framework.

## Installation

Checkout the code to either of your library directories:

    cd libraries
    git clone git@github.com:cgarvis/li3_gearman.git

Include the library in in your `/app/config/bootstrap/libraries.php`

    Libraries::add('li3_gearman');
    
Install gearman and the pear gearman packages

	apt-get install gearman php-pear
	pear install gearman-0.8.0
    
## Configuration

The plugin has default configurations, but you can change these if you wish like so:

	\li3_gearman\extensions\Gearman::config(array(
		'host' => '127.0.0.1',
		'port' => '4730',	
	));

## Basic Usage

Create a job at `/app/extensions/job/HelloWorld.php` that extends `\li3_gearman\extensions\service\gearman\Job`.

	namespace app\extensions\job;
	
	class HelloWorld extends \li3_gearman\extensions\service\gearman\Job {
		protected function _work() {
			echo 'Hello World!';
		}
	}
	
Then in the console you can start a worker

	li3 gearman work
	
You can manually queue jobs via the console

	li3 gearman queue HelloWorld
	
Or you can queue jobs in your application

	\li3_gearman\extensions\service\gearman\Client::queue('HelloWorld');
	
## Workload Usage

If you have the job `/app/extensions/job/Hello.php`:

	namespace app\extensions\job;

	class Hello extends \li3_gearman\extensions\service\gearman\Job {
	    protected function _work() {
	    	$workload = $this->getWorkLoad();
	    	
	    	$subject = $workload->subject ?: 'World';
	        echo 'Hello '.$subject.'!'.PHP_EOL;
	    }
	}

Can you manually queue the job with a payload like so:

	li3 gearman queue Hello '{"subject":"Lithium"}'
	
Or in your application

	\li3_gearman\extensions\service\gearman\Client::queue('Hello', array('subject' => 'Lithium'));
        
## MySQL UDF Usage

Install MySQL UDF

Create MySQL TABLES and TRIGGERS

<pre>


--
-- Table structure for table `delayed_jobs`
--

DROP TABLE IF EXISTS `archived_delayed_jobs`;
CREATE TABLE IF NOT EXISTS `archived_delayed_jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `priority` int(11) NOT NULL DEFAULT '0',
  `attempts` int(11) NOT NULL DEFAULT '0',
  `handler` mediumtext NOT NULL,
  `last_error` text,
  `run_at` datetime NOT NULL,
  `locked_at` datetime DEFAULT NULL,
  `failed_at` datetime DEFAULT NULL,
  `locked_by` varchar(50) DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS `delayed_jobs`;
CREATE TABLE IF NOT EXISTS `delayed_jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `priority` int(11) NOT NULL DEFAULT '0',
  `attempts` int(11) NOT NULL DEFAULT '0',
  `handler` mediumtext NOT NULL,
  `last_error` text,
  `run_at` datetime NOT NULL,
  `locked_at` datetime DEFAULT NULL,
  `failed_at` datetime DEFAULT NULL,
  `locked_by` varchar(50) DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Triggers `delayed_jobs`
--
DROP TRIGGER IF EXISTS `add_to_gearman`;
DROP TRIGGER IF EXISTS `archive_gearman_jobs`;
DELIMITER //
CREATE TRIGGER `add_to_gearman` AFTER INSERT ON `delayed_jobs`
 FOR EACH ROW BEGIN
SET @gearman_var = (SELECT gman_do_background("Process",CONCAT(concat(NEW.id,'|||'),NEW.handler)));

    END
//


    CREATE 
	TRIGGER `archive_gearman_jobs` BEFORE DELETE 
	ON `delayed_jobs` FOR EACH ROW BEGIN
        INSERT INTO `archived_delayed_jobs`  (priority, attempts, handler, last_error,run_at,locked_at,failed_at,locked_by,completed_at)
        VALUES (OLD.priority,OLD.attempts,OLD.handler,OLD.last_error,OLD.run_at,OLD.locked_at,OLD.failed_at,OLD.locked_by,NOW()); 
        END  
//
DELIMITER ;

</pre>

Create the following file `/app/extensions/job/Process.php`... don't forget to add `<?php` at the beginning

> This file is the worker that must be running and will process the serialized object stored in the mysql table

<pre>

namespace app\extensions\job;
use li3_gearman\models\Jobs; //the jobs model

class Process extends \li3_gearman\extensions\service\gearman\Job {
    protected function _work() {
        $workload = $this->getWorkLoad();

        $subject = $workload->id.' is the id' ?: ' id not there';
        echo $subject.'!'.PHP_EOL;
        Jobs::workOff($workload->id);
    }
}

</pre>

The way we add things to the queue with MySQL UDF is different than the examples above (Client, etc).
We add a serialized object that has a perform method with the logic that needs to be executed when the Gearman worker is called.  In other words,
instead of creating many different workers with different logic, we create a single worker whose job is to receive a serialized object
from a MySQL table that contains the object's state and logic.  This enables us to create objects dynamically, store them in MySQL and leverage
MySQL's triggers to begin the Gearman process without having to query the database until completion (either successful or failed completion).

The idea here is to have permanent storage of your queue that doesn't dissapear after completion (unless you want it to).  By default, the data
is deleted from the `delayed_jobs` table and moved to an archiving table that happens to look exactly like the `delayed_jobs` table but without
any triggers.  This archive table can be used for auditing or "replaying" events back into the queue.  The reason we move the objects is to keep
our `delayed_jobs` table lean and mean.

If you don't want this archiving functionality when the row is deleted from the `delayed_jobs` table, run the following:

`DROP TRIGGER IF EXISTS archive_gearman_jobs;`

#### Usage

###### Start the worker
Call `li3 gearman work` in your lithium app directory which will create the worker.

###### Enter an object into table for processing
Enter at the beginning of the file where you want store something in the MySQL UDF queue

`use li3_gearman\models\Jobs; `

In the same file, where you want to queue an object:

<pre>
//assuming a class called HelloWorld
$job = 'HelloWorld';

//the priority for the gearman job.  Support still missing
$priority = 0;

//there is no support for this yet.
$runAt = null;

//the full qualified name for the class
$className = '\\li3_gearman\\tests\\mocks\\data\\job\\'.$job;

//instantiate the object that you wish to store, make sure that the object has a perform method
$job = new $className($testID);

//store the object in the table
Jobs::enqueue($job, $priority, $runAt);

</pre>

This should call the user defined function for Gearman thanks to the trigger we created when setting up our tables.
If MySQL UDF, the Gearman Job Server and the Gearman workers are running, everything should work.


