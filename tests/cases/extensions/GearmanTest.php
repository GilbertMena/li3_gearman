<?php

namespace li3_gearman\tests\cases\extensions;

use li3_gearman\extensions\Gearman;
use lithium\data\Connections;

class GearmanTest extends \lithium\test\Integration {
  public function setup() {}
  
  public function testDefaultConnections() {
    Connections::add('gearman', array(
      'type' => 'li3_gearman\extensions\Gearman',
    ));
    
    $expected = array(
      'type' => 'li3_gearman\extensions\Gearman',
      'adapter' => null,
      'login' => '',
      'password' => '',
      'filters' => array(),
      'host' => '127.0.0.1',
      'port' => 4730
    );
    $result = Connections::get('gearman');
    
    $this->assertEqual($expected, Gearman::config());
  }
  
  public function testMultipleEnvironments() {
    Connections::add('gearman', array(
      'development' => array(
        'type' => 'li3_gearman\extensions\Gearman',
        'host' => 'localhost'
      ),
      'test' => array(
        'type' => 'li3_gearman\extensions\Gearman',
        'host' => '10.0.0.1'
      ),
    ));
    
    $expected = array(
      'type' => 'li3_gearman\extensions\Gearman',
      'adapter' => null,
      'filters' => array(),
      'host' => '10.0.0.1',
      'port' => 4730
    );

    $result = Connections::get('gearman');

    $this->assertEqual($expected, Gearman::config());
  }
}