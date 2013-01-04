<?php
namespace li3_gearman\extensions\data;

class Model extends \lithium\data\Model {
    /*
    public static function find($type, array $options = array()) {
        switch ($options['source'])
        {
            case 'order':
                parent::meta('connection','unicity');
                break;
            case 'orders':
                $market = substr(strtolower($options['conditions']['market']),0,3);
                if($market=='mys'||$market=='idn'||$market=='phl'||$market=='sgp')
                {
                    parent::meta('connection','xdbcCS1');
                }
                
                if($market=='usa'||$market=='can'||$market=='pri'||$market=='ven')
                {
                    parent::meta('connection','xdbcAMR');
                }
                
                if($market=='tha')
                {
                    parent::meta('connection','xdbcTH');
                }
                break;
        }
        return parent::find($type,$options);
	}
*/
}