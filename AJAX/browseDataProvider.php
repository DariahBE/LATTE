<?php

  //BUG (critical) : http://entitylinker.test/AJAX/browseDataProvider.php?value=7513 
  //  nodes that are somehow connected to a nodetype which isn't part of the datamodel trigger fatal errors. 
  //  You need to catch the fatal errors
  //  entities recognized by the NER-tool should be displayed!!! (parameter is hardcoded, so easy enough). 

  header('Content-Type: application/json; charset=utf-8');
  include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
  include_once(ROOT_DIR."\includes\getnode.inc.php");
  include_once(ROOT_DIR."\includes\user.inc.php");
  $node = new Node($client);

  $user = new User($client);

  $user_uuid = $user->checkSession();


  $value = $_GET['value'];    // DO NOT typecast, can be alfanumerical!
  //to identify a node use:
  if(isset($_GET['property'])){
    $keyname = $_GET['property'];
    //$value = 0;
    $subresult = $node->matchSingleNode(false, $keyname, $value);
    $value = $subresult['data'][0][0]->get('ID');
  }
  //code required to extend beyond the EGO node in the network
  $data = $node->getNeighbours((int)$value);
  $edges = array(); 
  $nodes = array(); 
  function addAsNode($nodeCypherMap){
    $nodeId = (int)$nodeCypherMap['id'];
    // the 'label' key in the output array is used by the vis.js tool to display in the node. 
    // if the config file has a setting to override the node's label by a value rather than the type
    // it should be done here. 
    // HOWEVER, the color of the node depends on the node type, so both values should be present in the output.
    $labels = $nodeCypherMap['labels'][0];
    $excludeNodesOfType = array('priv_user');

    if(!in_array($labels, $excludeNodesOfType)){
      $valueForLabel = $labels; 
      $found_key = array_search(true, array_column(NODEMODEL[$labels], 3), true);
      //array_search can return 0, but that's the index; don't use falsy statements!!
      if ($found_key !== false){
        $nodeKeyName  = array_keys(NODEMODEL[$labels])[$found_key];
        $valueForLabel = $nodeCypherMap['properties'][$nodeKeyName];
      }
      //get all node properties that have a translation in the config file and 
      //add them here in the graph on the nodelevel.
      $props = $nodeCypherMap['properties'];
      $propSettings = NODEMODEL[$labels]; 
      $showProperty= [];
      foreach($props as $key => $value){
        //var_dump($key, $propSettings[$key][0]);
        if (array_key_exists($key, $propSettings)){
          $showProperty[$propSettings[$key][0]][] = $value;
        }
      }
      
      return ['id'=>$nodeId, 'label'=>strval($valueForLabel), 'nodetype'=>$labels, 'properties'=>$showProperty];
    }

  }

  foreach($data as $key => $row){
    $leftNode = $row['n'];
    $edge = $row['r']; 
    $rightNode = $row['t'];
    $r = addAsNode($rightNode);
    if(boolval($r)){
      $nodes[$r['id']] = $r;
    }
    $l = addAsNode($leftNode);
    if(boolval($l)){
      $nodes[$l['id']] = $l;
    }
    //if there's a filter on the nodes, then don't return edges which connected to nodes that are missing from the graph.
    $startEdge = $edge['startNodeId'];
    $stopEdge = $edge['endNodeId'];
    if((in_array($startEdge, array_keys($nodes))&&in_array($stopEdge, array_keys($nodes)))){
      $edges[]= ['from'=>$startEdge, 'to'=>$stopEdge];
    }
  }

echo json_encode(array(
    'nodes' => array_values($nodes), 
    'edges' => array_values($edges)
  )
); 
?>