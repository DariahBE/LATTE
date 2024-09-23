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
			// do not remove priv_user or Annotation_auto nodes!
			$applicationDrivenNodes = array('priv_user', 'Annotation_auto');
			if((!in_array($label, array_keys(NODEMODEL))) && (!in_array($label, $applicationDrivenNodes))){
				//do a count of nodes for the offending label: 
				$countQuery = 'MATCH (n:'.$label.') RETURN count(n) AS count';
				$countData = $this->client->run($countQuery)[0]['count'];
				$result[$label] = $countData;
			}
		}
		return $result;
	}

	function deleteNodesNotMatchingModel($label){
		//WILL delete all nodes with the $label (str) as label. 
		//TODO: Implement
		$badLabels = 'MATCH (n:'.$label.') DETACH DELETE (n)';
		$data =  $this->client->run($badLabels);
		var_dump($data); 
	}

	function checkNodesWithoutUUID(){
		//Returns the amount of nodes that have no built in UID propert!
		// UID is an app logic controlled property, should be there. 
		// all labels should have a built in UID property. 
		$find_nouid_nodes_query = "MATCH (n) WHERE (NOT EXISTS (n.uid) AND NOT n:priv_user) RETURN count(n) AS badnodes; ";
		$data =  $this->client->run($find_nouid_nodes_query);
		return $data->first()['badnodes'];
	}

	function asignUUIDToNodes(){
		//TODO: Implement
		//ASSIGNS a UUID to all nodes where the UUID is missing. 
		$create_uuid_query = "MATCH (n) WHERE (NOT EXISTS (n.uid) AND NOT n:priv_user) SET n.uid = apoc.create.uuid() RETURN n; ";
		$data =  $this->client->run($create_uuid_query);
		return $data;
	}

    


}

?>