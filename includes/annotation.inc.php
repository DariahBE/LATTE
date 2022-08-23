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

  public function loadPersonalAnnotations($userid){
    $query = ('MATCH (a:Annotation)<-[r:created]-(u:priv_user) WHERE u.userid = $userid RETURN a');
    $result = $this->client->run($query, ['userid'=>$userid]);
    var_dump($result);
  }

  public function getAnnotationInfo($nodeId){
    //takes the nodeId of a node with label Annotation and generates all information about it.
    //1: Information about the author of the annotation:
    $queryAuthor = 'MATCH (a:Annotation)<-[r:created]-(u:priv_user) WHERE id(a) = $ego RETURN u.role AS role, u.name AS name';
    $resultAuthor = $this->client->run($queryAuthor, ['ego'=>(int)$nodeId]);
    if(boolval(count($resultAuthor))){
      $resultAuthor = $resultAuthor[0];
    }else{
      $resultAuthor = false;
    }
    //////
    $queryEntity = 'MATCH (a:Annotation)-[r:references]-(b) WHERE id(a)= $ego RETURN b, a';
    $resultEntity = $this->client->run($queryEntity, ['ego'=>(int)$nodeId]);
    //////

    return array(
      'author'=>$resultAuthor,
      'annotation' =>$resultEntity[0]->get('a'),
      'annotationModel' => $this->getAnnotationModel(),
      'entity'=>$resultEntity[0]->get('b'),
    );
  }

  public function createAnnotation($texid, $start, $stop, $user, $targetNode, $hidden=false){
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
      $connectToContainingText = 'MATCH (a:Annotation {uid:$anno_uid}), (t:Text{texid:$texid}) CREATE (t)-[r:contains]->(a)';
      $result = $this->client->run($connectToContainingText, ['anno_uid'=>$uqid, 'texid'=>$texid]);
    }
  }


  public function getExistingAnnotationsInText($texid, $user = false){
    //when user is false ==> only show public annotations.
    // when user is set to a matching priv_user.userid ==> show all public annotation + private annotations by $user
    //user parameter to determine if a node is private or not
    $query = 'MATCH (t:Text {texid: $texid})-[r]->(a:Annotation)-[l]->(p) return t,a,p;';
    $result = $this->client->run($query, ['texid'=>$texid]);
    $data = array();
    $data['user'] = $user;
    $annotationData = array();
    foreach ($result as $key => $annotationRecord) {
      $targetNodeType = $annotationRecord['p']->getLabels()[0];
        foreach($annotationRecord as $subkey => $node){
          if($node->labels()[0] === 'Annotation'){
            $anno_uuid = $node->getProperty('uid');
            $isPrivate = $node->getProperty('private');
            $creator_uuid = $node->getProperty('creator');
            $annotationStart = $node->getProperty('starts');
            $annotationStop = $node->getProperty('stops');
            $map = array(
              'annotation' => $anno_uuid,
              'creator' => $creator_uuid,
              'private' => $isPrivate,
              'start' => $annotationStart,
              'stop' => $annotationStop,
              'type' => $targetNodeType
            );
            if($isPrivate){
              if($user and $creator_uuid === $user){
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
