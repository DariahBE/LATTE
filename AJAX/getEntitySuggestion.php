<?php
header('Content-Type: application/json; charset=utf-8');

include_once("../config/config.inc.php");
include_once(ROOT_DIR."\includes\getnode.inc.php");
$graph = new Node($client);

$approvedEntities = array('Person', 'Place');
$caseSensitive = isset($_GET['casesensitive'])? $_GET['casesensitive']: false;
$caseSensitive = (strtolower($caseSensitive)=='true')? true : false;
$findEntityByType = $_GET['type'];
$findEntityByValue = $_GET['value'];
if(in_array($findEntityByType, $approvedEntities)){
  $data = $graph->getEntities($findEntityByType,$findEntityByValue,$caseSensitive);
  echo json_encode($data);
}else{
  die(json_encode('Invalid request'));
}

//get place by name:
//      MATCH (n:Place)-[r:same_as]-(p:Variant) where n.name = 'Abdera' RETURN n,r,p

//get place by name variant:
//      MATCH (n:Place)-[r:same_as]-(p:Variant) where p.variant = 'Ἄβδερα' RETURN n,r,p LIMIT 25

//merge the two above:
//      MATCH (q:Variant)-[s]-(n:Place)-[r]-(v:See_Also) where (n.name = 'Abdera' or q.variant='abdera')  return n,s,q,r,v LIMIT 100

//////HOWEVER ==> BUG: this is not able to find n places, where there's no linked q:variant.
//  E.g.=: match(p:Place) where p.name = "Test" return p limit 1;

//// THIS Works for cases where n exists (Abdera and Test), but not for variants only Ἄβδερα:
/*
  OPTIONAL MATCH(n:Place)
    WHERE (n.name='Test')
  OPTIONAL MATCH(v:Variant)-[r1:same_as]-(n)
    WHERE (v.variant='Test')
  OPTIONAL MATCH(s:See_Also)-[r2:see_also]-(n)
  RETURN n,s,r1,r2,v LIMIT 100
*/

///// THIS WORKS FOR ALL CASES:
/*
  - Only one p:Place with name x no related nodes:        Test
  - One or more nodes p:Place with name x and related nodes:      Abdera
  - One or more nodes v:Variant with name x and related nodes:    Adra

    OPTIONAL MATCH (p:Place {name: 'Avdira'})
    OPTIONAL MATCH (v:Variant {variant: 'Avdira'})-[r1:same_as]-(q:Place)
    OPTIONAL MATCH (p)-[r2:see_also]->(i:See_Also)
    OPTIONAL MATCH (q)-[r3:see_also]->(j:See_Also)
    return p,v,q,r1,r2,r3,i,j
    limit 100

  -HOWEVER: this query is still not case insensitive. // fixed it with regex


*/

die();
?>
