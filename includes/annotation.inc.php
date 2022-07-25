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
  function __construct($client){
    $this->client = $client;
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


  public function getExistingAnnotationsInText($texid){
    $query = 'MATCH (t:Text {texid: $texid})-[r]-(a:Annotation)-[l]-(p) return t,a,r,l,p;';
    $result = $this->client->run($query, ['texid'=>$texid]);
    if(boolval(count($result))){
      foreach ($result as $key => $value) {
        //var_dump($value);
      }
    }
  }

}
