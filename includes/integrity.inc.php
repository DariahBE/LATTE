<?php

class Integrity{
	protected $client;
	function __construct($client) {
		$this->client = $client;
	}

	function checkNodesNotMatchingModel(){
		$result = array();
		$query = 'CALL db.labels()';
		$data =  $this->client->run($query);
		foreach($data as $key => $value){
			$label = $value['label'];
			$applicationDrivenNodes = array('priv_user');

			if((!in_array($label, array_keys(NODEMODEL))) && (!in_array($label, $applicationDrivenNodes))){
				//echo $label.'<br>';
				//do a count of nodes for the offending label: 
				$countQuery = 'MATCH (n:'.$label.') RETURN count(n) AS count';
				$countData = $this->client->run($countQuery)[0]['count'];
				$result[$label] = $countData;
				//var_dump($countData);
			}
			//var_dump($value['label']);
		}
		return $result;
		
	}

	function deleteNodesNotMatchingModel(){

	}

	function checkNodesWithoutUUID(){

	}

	function asignUUIDToNodes(){

	}

    


}

?>