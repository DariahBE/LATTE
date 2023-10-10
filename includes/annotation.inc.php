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
  private $protectedKeys = array('starts', 'stops', 'uid', 'creator');
  function __construct($client){
    $this->client = $client;
  }

  public function isProtectedKey($key){
    //protected Keys are immutable.
    return in_array($key, $this->protectedKeys);
  }

  private function getAnnotationModel(){
    return NODES['Annotation'];
  }

  public function countPersonalAnnotations($userid){
    $query = ('MATCH (a:Annotation)<-[r:priv_created]-(u:priv_user) WHERE u.userid = $userid RETURN COUNT(a) as annotationcount');
    $queryPrivate = ('MATCH (a:Annotation)<-[r:priv_created]-(u:priv_user) WHERE u.userid = $userid AND a.private=True RETURN COUNT(a) as annotationcount');
    $data = ['userid'=>$userid]; 
    $result = $this->client->run($query, $data);
    $resultPrivate = $this->client->run($queryPrivate, $data); 
    //var_dump($resultPrivate);
    return(array('public' => $result[0]['annotationcount'], 'private' => $resultPrivate[0]['annotationcount']));
  }

  public function getAnnotationInfo($nodeId){
    //takes the apoc created uid of a node with label Annotation and generates all information about it.
    //1: Information about the author of the annotation:
    $queryAuthor = 'MATCH (a:Annotation)<-[r:priv_created]-(u:priv_user) WHERE id(a) = $ego RETURN u.role AS role, u.name AS name';
    $resultAuthor = $this->client->run($queryAuthor, ['ego'=>$nodeId]);
    if(boolval(count($resultAuthor))){
      $resultAuthor = $resultAuthor[0];
    }else{
      $resultAuthor = false;
    }
    //////
    $queryEntity = 'MATCH (a:Annotation)-[r:references]-(b) WHERE id(a) = $ego RETURN b, a';
    $resultEntity = $this->client->run($queryEntity, ['ego'=>$nodeId]);
    //////

    return array(
      'author'=>$resultAuthor,
      'annotation' =>$resultEntity[0]->get('a'),
      'annotationModel' => $this->getAnnotationModel(),
      'entity'=>$resultEntity[0]->get('b'),
    );
  }

  public function createAnnotation($texid, $start, $stop, $user, $targetNode, $hidden=false){
    //todo: 
    die("redo this, do not rely on static properties!!! (starts, texid.... bad idea)");
    //keep the $texid even though it is implied as part of the edge target!
    //DO NOT rely on id(): https://stackoverflow.com/questions/64796146/how-stable-are-the-neo4j-ids
    $query = 'CREATE (a:Annotation {uid: apoc.create.uuid(), starts: $start, stops: $stop, creator: $user, private:$hidden}) RETURN a.uid as uid;';
    $result = $this->client->run($query, ['start'=>$start, 'stop'=>$stop, 'user'=>$user, 'hidden'=>$hidden]);
    if(boolval(count($result))){
      $uqid = $result[0]['uid'];
      //connect creator to node:
      $connectCreatorToAnnotation = 'MATCH (a:priv_user), (b:Annotation) WHERE a.userid = $uid_person AND b.uid = $uid_anno CREATE (a)-[r:created]->(b)';
      $result = $this->client->run($connectCreatorToAnnotation, ['uid_person'=>$user, 'uid_anno'=>$uqid]);
      //connect annotation to targetNode:
      $connectToTargetNode = 'MATCH (a:Annotation {uid:$anno_uid}), (n{uid:$entity_uid}) CREATE (a)-[r:references]->(n) RETURN a,r,n';
      $result = $this->client->run($connectToTargetNode, ['anno_uid'=>$uqid, 'entity_uid'=>$targetNode]);
      //connect annotation to containing text:
      $connectToContainingText = 'MATCH (a:Annotation {uid:$anno_uid}), (t:T'.TEXNODE.'{texid:$texid}) CREATE (t)-[r:contains]->(a)';
      $result = $this->client->run($connectToContainingText, ['anno_uid'=>$uqid, 'texid'=>$texid]);
    }
  }

  public function createAnnotationWithExistingEt($neoIDText, $neoIDEt, $user, $start, $end){
    //todo; static properties!
    die("redo this, do not rely on static properties!!! (starts, texid.... bad idea)");
    $constraintTwo = False;
    $userNeo = $user->neoId;
    $userAppId = $user->myId;  
    //var_dump($user->neoId); 
    //die(); 
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
        (a:Annotation {starts: $startnumb, stops: $endnumb, uid: apoc.create.uuid(), priv_creator: $creatorid } ),
        (a)<-[r1:contains]-(t),
        (a)-[r2:references]->(e)
      RETURN a,t,e,r1,r2,id(a)';
      $annotdata = $this->client->run($query, [
        'texid' => $neoIDText,
        'etid' => $neoIDEt, 
        'startnumb' => $start,
        'endnumb' => $end, 
        'creatorid' => $userAppId, 
      ]); 

      //connect (a) to $user
      $annotationNeoID = $annotdata[0]['id(a)']; 
      $query2 = 'MATCH (u:priv_user), (a:Annotation)
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



  
  public function getExistingAnnotationsInText($neoid, $user = false){
    //when user is false ==> only show public annotations.
    // when user is set to a matching priv_user.userid ==> show all public annotation + private annotations by $user
    //user parameter to determine if a node is private or not
    $query = 'MATCH (t:'.TEXNODE.')-[r:contains]->(a:Annotation)-[l:references]->(p) where id(t)=$neoid return t,a,p;';

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
          if($node->labels()[0] === 'Annotation'){
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
