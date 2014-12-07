<?php
/**
* Report is a class for logging events into the event system
* using yii-resque component 
* @author Milad Alshomary
*/
class Averous extends CApplicationComponent {


  /**
  * @var array contains redis configuration  ( server, port, database, password, prefix)
  */
  public $redis = array(
            'server' => 'localhost',
            'port' => '6379',
            'database' => 0,
            'password' => '',           
        );

  /**
  * @var string queue name
  */
  public $queue_name = 'events_queue';

  /**
  * @var string worker_name the name of the
  * woker that will do the logging
  */
  public $worker_name = 'WorkerClass';

  /**
  * @var timestamp_format string the format of the
  * event time stamp
  */
  public $timestamp_format = "Y-m-d\TG:i:s\Z";


  /**
  * Initializes the connection.
  */
  public function init() {
    parent::init();  
    if(!class_exists('RResqueAutoloader', false)) {
      # Turn off our amazing library autoload
      spl_autoload_unregister(array('YiiBase','autoload'));
      # Include Autoloader library
      include(dirname(__FILE__) . '/RResqueAutoloader.php');
      # Run request autoloader
      RResqueAutoloader::register();
      # Give back the power to Yii
      spl_autoload_register(array('YiiBase','autoload'));
    }
    
    Resque::setBackend($this->redis['server'] . ':' . $this->redis['port'], $this->redis['database'], $this->redis['password']);
    if (isset($this->redis['prefix'])) {
      Resque::redis()->prefix($this->redis['prefix']);    
    }

  }

  /**
  * To log the event into redis using resque, it takes the current time as a time stamp
  * @param $event_name string the event name
  * @param $params array of key => value refer to fields in the event
  */
  public function log($event_type, $params) {
    if($this->isBot()) {
      return;
    }

    $now = date($this->timestamp_format);
    $all_params = array_merge($params, ['time' => $now, 'event_type' => $event_type]);
    Resque::enqueue($this->queue_name, $this->worker_name, ['fields' => $all_params], false);
  }

  /**
  * To check if the event is being triggered from a bot,
  * @return true if the event is triggered from a bot
  */
  protected static function isBot() {
    if ( !isset($_SERVER['HTTP_USER_AGENT']) || $_SERVER['HTTP_USER_AGENT']=='-' ) {
      return true;
    }
    
    if (preg_match('/bot|index|spider|crawl|slurp|wget|Mediapartners-Google/i', $_SERVER['HTTP_USER_AGENT'])){
      return true;
    }

    return false;
  }

}
?>