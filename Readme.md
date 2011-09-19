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

## Usage

Create a job in `/app/extensions/job` that extends `\li3_gearman\extensions\service\gearman\Job`.

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