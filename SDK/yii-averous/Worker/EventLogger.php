<?php
/**
* Worker_EventLogger is a worker class that consumes
* the event from the defined queue name and logs them to specific distenation 
* @author Milad Alshomary
*/

class Worker_EventLogger {

	protected $client = null;
	protected $host   = "localhost";
	protected $port   = 8090;

	public function setup() {
		$this->client = curl_init();
		curl_setopt($this->client, CURLOPT_URL,  $this->host);
  	curl_setopt($this->client, CURLOPT_PORT, $this->port);
  	curl_setopt($this->client, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));  	
	}

  public function perform() {
  	
  	$event_fields = $this->args['fields'];
  	$json = json_encode($event_fields);

    curl_setopt($this->client, CURLOPT_POSTFIELDS, $json); 
		curl_exec($this->client);
		$info = curl_getinfo($this->client);
		if(empty($info['http_code']) || $info['http_code'] != 200) {
			//faild
			echo 'Evnt faild to be posted to the dest, it will be reschedule to the faild queue!!';
			throw new Exception("Failed to connect to the Distenation");
		} else {
			//success
			echo $info['http_code'];
			echo 'Evnt posted to the dest!!';
		}

  }

}
