<?php
/*
  annotation class:
    - Create annotation
        * Annotation = Node ==> Three + n edges:
          1: Aser that created the record
          2: Text that references the annotation
          3: Entity that is referenced
          n: Users that edited the annotation.
    - Read annotation
    - Update annotation
    - Delete annotation
*/
//return null;

class Annotation{
  private $client;
  private $protectedKeys = array(ANNOSTART, ANNOSTOP, 'uid', 'creator');
  function __construct($client){
    $this->client = $client;
  }

    //transaction management.
    public $tsx;
    public function startTransaction(){
      $this->tsx = $this->client->beginTransaction();
    }
    public function rollbackTransaction(){
      $this->tsx->rollback();
    }
    public function commitTransaction(){
      $this->tsx->commit();
    }

    //describe the model used for Automatic_annotation nodes. This should match the structure as per NODESMODEL constant. 
    public $auto_model = [
      'Automatic_annotation' => [
        "start" => ["AnnotionStart", "int", false, false, false],
        "stop" => ["AnnotationEnd", "int", false, false, false],
      ]
    ]; 
    //TODO: implement transactional model in every implementation of the ANNOTATION class. 

  public function isProtectedKey($key){
    //protected Keys are immutable.
    return in_array($key, $this->protectedKeys);
  }

  private function getAnnotationModel(){
    return NODES[ANNONODE];
  }

  public function fetchAutomaticAnnotationById($neoId){
    $query = 'MATCH (a:Annotation_auto) WHERE id(a) = $neo RETURN a; '; 
    $result = $this->client->run($query, array('neo'=>(int)$neoId)); 
    return $result; 
  }

  public function countPersonalAnnotations($userid){
    $query = ('MATCH (a:'.ANNONODE.')<-[r:priv_created]-(u:priv_user) WHERE u.userid = $userid RETURN COUNT(a) as annotationcount');
    $queryPrivate = ('MATCH (a:'.ANNONODE.')<-[r:priv_created]-(u:priv_user) WHERE u.userid = $userid AND a.private=True RETURN COUNT(a) as annotationcount');
    $data = ['userid'=>$userid]; 
    $result = $this->client->run($query, $data);
    $resultPrivate = $this->client->run($queryPrivate, $data); 
    //var_dump($resultPrivate);
    return(array('public' => $result[0]['annotationcount'], 'private' => $resultPrivate[0]['annotationcount']));
  }

  public function getAnnotationInfo($nodeId){
    //takes the apoc created uid of a node with label Annotation and generates all information about it.
    //1: Information about the author of the annotation:
    $queryAuthor = 'MATCH (a:'.ANNONODE.')<-[r:priv_created]-(u:priv_user) WHERE id(a) = $ego RETURN u.role AS role, u.name AS name';
    $resultAuthor = $this->client->run($queryAuthor, ['ego'=>$nodeId]);
    if(boolval(count($resultAuthor))){
      $resultAuthor = $resultAuthor[0];
    }else{
      $resultAuthor = false;
    }
    //////
    $queryEntity = 'MATCH (a:'.ANNONODE.')-[r:references]-(b) WHERE id(a) = $ego RETURN id(b) as et_neo_id, b, a';
    $resultEntity = $this->client->run($queryEntity, ['ego'=>$nodeId]);
    //////

    return array(
      'author'=>$resultAuthor,
      'annotation' =>$resultEntity[0]->get('a'),
      'annotationModel' => $this->getAnnotationModel(),
      'entity'=>$resultEntity[0]->get('b'),
      'entity_neo_id'=>$resultEntity[0]->get('et_neo_id'),
    ); 
  }

  public function createAnnotation($texid, $start, $stop, $user, $targetNode, $hidden=false){
    //todo: actually considere deleting this. It is not used at all!
    die("redo this, do not rely on static properties!!! (starts, texid.... bad idea)");
    //keep the $texid even though it is implied as part of the edge target!
    // bad idea: Id() is actually stable as long as it is not deleted!!
    //    You can use it for short-lived transaction.
    //    You shouldn't use it for stable identifiers. 
    //DO NOT rely on id(): https://stackoverflow.com/questions/64796146/how-stable-are-the-neo4j-ids
    //BUG: go back to id()-dependency
    //   : make passtrough link to entities optional 
    //   : add a property to annotation which indicates it was auto-recognized. 
    $query = 'CREATE (a:'.ANNONODE.' {uid: apoc.create.uuid(), '.ANNOSTART.': $start, '.ANNOSTOP.': $stop}) RETURN a.uid as uid;';
    $result = $this->client->run($query, ['start'=>$start, 'stop'=>$stop, 'user'=>$user]);
    if(boolval(count($result))){
      $uqid = $result[0]['uid'];
      //connect creator to node:
      $connectCreatorToAnnotation = 'MATCH (a:priv_user), (b:'.ANNONODE.') WHERE a.userid = $uid_person AND b.uid = $uid_anno CREATE (a)-[r:created]->(b)';
      $result = $this->client->run($connectCreatorToAnnotation, ['uid_person'=>$user, 'uid_anno'=>$uqid]);
      //connect annotation to targetNode:
      $connectToTargetNode = 'MATCH (a:'.ANNONODE.' {uid:$anno_uid}), (n{uid:$entity_uid}) CREATE (a)-[r:references]->(n) RETURN a,r,n';
      $result = $this->client->run($connectToTargetNode, ['anno_uid'=>$uqid, 'entity_uid'=>$targetNode]);
      //connect annotation to containing text:
      $connectToContainingText = 'MATCH (a:'.ANNONODE.' {uid:$anno_uid}), (t:T'.TEXNODE.'{texid:$texid}) CREATE (t)-[r:contains]->(a)';
      $result = $this->client->run($connectToContainingText, ['anno_uid'=>$uqid, 'texid'=>$texid]);
    }
  }

  public function fetchVariants($ofEntityByNeoid){
    $result = array();
    $query2 = 'match(v)-[r:same_as]-(n) where id(n) = $entityid return v, id(v) as neoid' ;
    $data2 = $this->client->run($query2, ['entityid'=> (int)$ofEntityByNeoid]);
    foreach($data2 as $labelvariant){
      $variantRow = $labelvariant['v'];
      //$neoID = (int)$labelvariant['neoid']; 
      $rowProperties = $variantRow['properties']; 
      $result['labelVariants'][] = ['DOMstring'=>'Label', 'value'=>$rowProperties['variant'], 'uid'=>$rowProperties['uid']]; 
    }
    return $result;
  }

  public function createAnnotationWithExistingEt($neoIDText, $neoIDEt, $user, $start, $end){
    //OK; static properties! OK
    // TODO: test required from connect.php 
    $constraintTwo = False;
    $userNeo = $user->neoId;
    $userAppId = $user->myId;  
    //put a constraint on the label of t: ensure that this is the text!
    $query = 'MATCH (t:'.TEXNODE.') WHERE id(t) = $texid RETURN t';        
    $result = $this->client->run($query, ['texid'=>$neoIDText]);
    $constraintOne = boolval(count($result)); 
    //put a constraint on the lable of e: ensure that this is an entity! The label should be used in CORENODES
    $query2 = 'MATCH (e) WHERE id(e) = $etid RETURN labels(e) AS labels';
    $result2 = $this->client->run($query2, ['etid'=>$neoIDEt]); 
    if(boolval(count($result2))){
      if(array_key_exists($result2[0]['labels'][0], CORENODES)){
        $constraintTwo = True;
      }
    }
    if($constraintOne && $constraintTwo){
      //both constraints are met; connect;
      #Write a cypher query that creates a new Node with label 'Annotation'.
      #Assign an automatically created UUIDV4 to it. 
      $query = 'MATCH (t:'.TEXNODE.'),(e)
      WHERE id(t) = $texid AND id(e) = $etid 
      CREATE
        (a:'.ANNONODE.' {'.ANNOSTART.': $startnumb, '.ANNOSTOP.': $endnumb, uid: apoc.create.uuid() } ),
        (a)<-[r1:contains]-(t),
        (a)-[r2:references]->(e)
      RETURN a,t,e,r1,r2,id(a)';
      $annotdata = $this->client->run($query, [
        'texid' => $neoIDText,
        'etid' => $neoIDEt, 
        'startnumb' => $start,
        'endnumb' => $end
      ]); 

      //connect (a) to $user
      $annotationNeoID = $annotdata[0]['id(a)']; 
      $query2 = 'MATCH (u:priv_user), (a:'.ANNONODE.')
      WHERE id(u) = $userid AND id(a) = $annotationid
      CREATE (u)-[r:priv_created]->(a)
      RETURN u,r'; 
      $userdata = $this->client->run($query2, [
        'userid' => $userNeo, 
        'annotationid' => $annotationNeoID
      ]);
      return array(
        'success'=>true,
        'msg'=>'Annotation created succesfully.', 
        'data'=>$annotdata->getResults(), 
        'user'=>$userdata->getResults()
      ); 
    }else{
      return array(
        'success'=>false, 
        'msg'=>'One or more constraints failed.'
      );
    }
  }

  public function createRecognizedAnnotation(int $texid, array $connections){
    /*
      Function called whenever the external NER-tool finds an entity and wants to save it in the project
      marks a part of the text as an annotation, without linking it to an entity. 
      for lower overhead you should allow to process multiple annotations at once. 
      
      TODO: this has to be documented!!! 
      Beware: Annotation_auto is a hardcoded labelname with hardcoded properties (start, stop and uid.)
     */
     foreach($connections as $connection){
        $start = $connection[0];
        $stop = $connection[1]; 
        $cypher = '
        MATCH (n) WHERE id(n) = $texid
        OPTIONAL MATCH (a:Annotation_auto) WHERE (n)--(a) AND a.start = $start AND a.stop = $stop
        MERGE (n)-[:contains]->(newA:Annotation_auto {start: $start, stop: $stop, uid: apoc.create.uuid()})
        ';
       
        $this->client->run($cypher, [
          'start' => $start,
          'stop' => $stop,
          'texid' => $texid
          ]);
        }
  }


  public function getUnlinkedAnnotationsInText($neoid){
    /*
      Annotations which are made by the NER-tool are returned without a matching ET. 
    */
    //{"annotation":"fc26e2c3-915c-46ce-a418-f14d9a7498d5","creator":false,"private":false,"start":138,"stop":141,"type":"Organization","neoid":7517} 
    $query = 'MATCH (t:'.TEXNODE.')-[r:contains]->(a:Annotation_auto) WHERE id (t)=$neoid RETURN a,r'; 
    $result = $this->client->run($query, ['neoid'=>$neoid]); 
    $data = array(); 
    foreach($result as $row => $value){
      $node = $value["a"]; 
      //var_dump($node); 
      $annotation = array();
      $annotation['annotation'] = $node->getProperty('uid'); 
      $annotation['start'] = $node->getProperty('start');
      $annotation['stop'] = $node->getProperty('stop');
      $data[]=$annotation; 


    }

    return $data;
  }

  
  public function getExistingAnnotationsInText($neoid, $user = false){
    //when user is false ==> only show public annotations.
    // when user is set to a matching priv_user.userid ==> show all public annotation + private annotations by $user
    //user parameter to determine if a node is private or not
    $query = 'MATCH (t:'.TEXNODE.')-[r:contains]->(a:'.ANNONODE.')-[l:references]->(p) where id(t)=$neoid return t,a,p;';

    //patch: consider returning the property and extracting that; by default cypher will nullify non-existing properties.
    $result = $this->client->run($query, ['neoid'=>$neoid]);
    $data = array();
    $data['user'] = $user;
    $annotationData = array();


    function controlledReply($object, $propertyName, $controlledOutput){
      /*
        The getProperty() method does not return null when a property is not set!!!
        In other words it must exist for the code to work, patched for now by putting
        it in a try catch block; with default output given as function argument.
      */
      try {
        return $object->getProperty($propertyName);
      } catch (\Exception $e) {
        return $controlledOutput;
      }
    }// endof controlledReply.

    foreach ($result as $key => $annotationRecord) {
      $targetNodeType = $annotationRecord['p']->getLabels()[0];
        foreach($annotationRecord as $subkey => $node){
          if($node->labels()[0] === ANNONODE){
            $anno_uuid = $node->getProperty('uid');
            $isPrivate = controlledReply($node, 'priv_private', False);
            $creator_uuid = controlledReply($node, 'priv_creator', False);
            $annotationStart = $node->getProperty(ANNOSTART);
            $annotationStop = $node->getProperty(ANNOSTOP);
            $neoID = $node['id']; 
            $map = array(
              'annotation' => $anno_uuid,
              'creator' => $creator_uuid,
              'private' => $isPrivate,
              'start' => $annotationStart,
              'stop' => $annotationStop,
              'type' => $targetNodeType,
              'neoid' => $neoID
            );
            if($isPrivate){
              if($user!==false and $creator_uuid === $user){
                $annotationData[$anno_uuid] = $map;
              }
            }else{
              $annotationData[$anno_uuid] = $map;
            }
          }
        }
    }
    $data['relations'] = $annotationData;
    return $data;
  }
}
