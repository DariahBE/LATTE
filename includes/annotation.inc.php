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
  function __construct($client, $hook=false){
    /**
     * If the transaction needs to be shared between two or more classes
     * pass False as the $client argument. For the $hook argument use 
     * the transaction object which was created in the other class: 
     * e.g.: $a = Annotation(False, $node->tsx); 
     * 
     * Transactions will need sharing if the an action is chained over multiple classes.
     */
    if (!($client)){
      $this->tsx = $hook; 
    }else{
      $this->client = $client;
    }
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

    //describe the model used for Automatic_annotation nodes.
    // the properties used by the automatic model are the same
    // as the properties used by the controlled model!
    public $auto_model = [
      'Automatic_annotation' => [
        ANNOSTART => ["Annotation Start", "int", false, false, false],
        ANNOSTOP => ["Annotation End", "int", false, false, false],
      ]
    ]; 

  public function isProtectedKey($key){
    //protected Keys are immutable.
    return in_array($key, $this->protectedKeys);
  }

  private function getAnnotationModel(){
    return NODES[ANNONODE];
  }

  public function convertAutomaticAnnotationToConfirmedAnnotation($neoId, $data){
    /**
     * CONVERTS THE AUTOMATICALLY GENERATED ANNOTATION FROM Annotation_auto to ANNONODE. 
     * The OLD UID stays!! This is best to accomodate references/URI's that were generated
     * in old XML/JSON/API exports/links.
     * The label changes from Annotation_auto to the chosen label for ANNONODEs
     * The properties of the label change according to the defined model
     *    - new fields become available
     * The connection to a new/existing entity gets made
     */

     //PATCH OK: if annotation_auto uses different properties to indicate the start and end of 
     // an annotations, this property has to be rewritten!
     /*
     conversion code no longer needed; selectionstart and selectionend properties are
     the same for automatic and manual annotations!!
    $convertStartStop = ''; 
    if (ANNOSTART !== 'starts'){
      $convertStartStop .= ' n.'.ANNOSTART.' = n.starts ' ;
    }
    if (ANNOSTOP !== 'stops'){
      $convertStartStop .= ' n.'.ANNOSTOP.' = n.stops ' ;
    }*/
    
    $subset = array(); 
    $data_iter = array('neo'=>(int)$neoId); 
    $iter = 0; 
    foreach ($data as $key => $value) {
      //requires the use of setToTypeByModel() for data values!
      $cast_data = $this->setToTypeByModel(ANNONODE, $key, $value); 
      if($cast_data[1]){
        $iter+=1; 
        $ph_name = "var_ref_".strval($iter);
        $subset[] = "n.$key=$$ph_name";       //double $ for query syntax
        $data_iter[$ph_name] = $cast_data[0]; 
      }
    }
    $annotation_properties = implode(', ',$subset); 
    if (count($subset)>0){
      $annotation_properties = ', '.$annotation_properties; 
    }
    $query = 'MATCH (n:Annotation_auto) WHERE id(n) = $neo
    REMOVE n:Annotation_auto
    SET n:'.ANNONODE.''.$annotation_properties.';';  
    $result = $this->tsx->run($query, $data_iter); 
    return $neoId;
  }

  public function fetchAnnotationUUID($neoId){
    $query = 'MATCH (a) where id(a) = $neo RETURN a.uid as uid;'; 
    $result = $this->tsx->run($query, array('neo'=>(int)$neoId)); 
    return $result[0]['uid']; 
  }

  /* 
  //obsolete!
  public function fetchAutomaticAnnotationById($neoId){
    $query = 'MATCH (a:Annotation_auto) WHERE id(a) = $neo RETURN a; '; 
    $result = $this->tsx->run($query, array('neo'=>(int)$neoId)); 
    return $result; 
  }*/

  public function countPersonalAnnotations($userid){
    $query = ('MATCH (a:'.ANNONODE.')<-[r:priv_created]-(u:priv_user) WHERE u.user_sqlid = $userid RETURN COUNT(a) as annotationcount');
    $queryPrivate = ('MATCH (a:'.ANNONODE.')<-[r:priv_created]-(u:priv_user) WHERE u.user_sqlid = $userid AND a.private=True RETURN COUNT(a) as annotationcount');
    $data = ['userid'=>$userid]; 
    $result = $this->tsx->run($query, $data);
    $resultPrivate = $this->tsx->run($queryPrivate, $data); 
    return(array('public' => $result[0]['annotationcount'], 'private' => $resultPrivate[0]['annotationcount']));
  }

  public function getAnnotationInfo($nodeId){
    //takes the apoc created uid of a node with label Annotation and generates all information about it.
    //1: Information about the author of the annotation:
    $queryAuthor = 'MATCH (a:'.ANNONODE.')<-[r:priv_created]-(u:priv_user) WHERE id(a) = $ego RETURN u.role AS role, u.name AS name';
    $resultAuthor = $this->tsx->run($queryAuthor, ['ego'=>$nodeId]);
    if(boolval(count($resultAuthor))){
      $resultAuthor = $resultAuthor[0];
    }else{
      $resultAuthor = false;
    }
    //////
    $queryEntity = 'MATCH (a:'.ANNONODE.')-[r:references]-(b) WHERE id(a) = $ego RETURN id(b) as et_neo_id, b, a';
    $resultEntity = $this->tsx->run($queryEntity, ['ego'=>$nodeId]);
    //////

    return array(
      'author'=>$resultAuthor,
      'annotation' =>$resultEntity[0]->get('a'),
      'annotationModel' => $this->getAnnotationModel(),
      'entity'=>$resultEntity[0]->get('b'),
      'entity_neo_id'=>$resultEntity[0]->get('et_neo_id'),
    ); 
  }

  public function fetchVariants($ofEntityByNeoid){
    $result = array();
    $query2 = 'match(v)-[r:same_as]-(n) where id(n) = $entityid return v, id(v) as neoid' ;
    $data2 = $this->tsx->run($query2, ['entityid'=> (int)$ofEntityByNeoid]);
    foreach($data2 as $labelvariant){
      $variantRow = $labelvariant['v'];
      //$neoID = (int)$labelvariant['neoid']; 
      $rowProperties = $variantRow['properties']; 
      $result['labelVariants'][] = ['DOMstring'=>'Label', 'value'=>$rowProperties['variant'], 'uid'=>$rowProperties['uid']]; 
    }
    return $result;
  }

  private function setToTypeByModel($entity, $property, $value){
    /* helper function which converts the $value to the expected type
      by looking up which type is declared in the NODEMODEL. 
      function returns a list of two elements. The first element is the value
      cast to the correct type; the second value is the boolval result of the $value
      or overridden to true where it makes sense. If this second values returns False
      the value will be rejected an NOT stored in the database.
      !!!!!!!!!!!
      Empty strings or values which can be interpreted as empty values are not 
      stored in the database if the second value of the return object is false!
      !!!!!!!!!!!!
    */
    $expectedType = NODEMODEL[$entity][$property][1]; 
    switch ($expectedType) {
      case 'string':
        //boolval of '' = false!
        $value = strval($value); 
        $value = trim($value); 
        return [strval($value), boolval($value)]; 
        break;
      case 'float':
        if ($value === ''){
          return[false, false]; 
        }
        return [floatval($value), true]; 
        break; 
      case 'wikidata': 
        $validity_of_datapoint =  preg_match('/^Q\d+$/', $value) === 1;
        return [strval($value), $validity_of_datapoint];
        break;
      case 'int': 
        if ($value === ''){
          return[false, false]; 
        }
        return [(int)$value, true]; 
        break;
      case 'bool': 
        //bugpatch!
        $value = strtolower($value) === 'true' ? true : false;
        return [boolval($value), true];
        break;
      case 'uri': 
        $valid = filter_var($value, FILTER_VALIDATE_URL); 
        return [strval($value), boolval($valid)];
        break;
      default:
        return $value; 
        break;
    }
  }

  public function createAnnotationWithExistingEt($neoIDText, $neoIDEt, $user, $start, $end, $extra){
    //OK; static properties! OK
    $constraintTwo = False;
    $userNeo = $user->neoId;
    $userAppId = $user->myId;
    //put a constraint on the label of t: ensure that this is the text!
    $query = 'MATCH (t:'.TEXNODE.') WHERE id(t) = $texid RETURN t';
    $result = $this->tsx->run($query, ['texid'=>$neoIDText]);
    $constraintOne = boolval(count($result));
    //put a constraint on the lable of e: ensure that this is an entity! The label should be used in CORENODES
    $query2 = 'MATCH (e) WHERE id(e) = $etid RETURN labels(e) AS labels';
    $result2 = $this->tsx->run($query2, ['etid'=>$neoIDEt]); 
    if(boolval(count($result2))){
      if(array_key_exists($result2[0]['labels'][0], CORENODES)){
        $constraintTwo = True;
      }
    }

    $phval = 0;
    $querydata = [
      'texid' => $neoIDText,
      'etid' => $neoIDEt, 
      'startnumb' => $start,
      'endnumb' => $end
    ]; 
    $queryparameters = [ANNOSTART.': $startnumb', ANNOSTOP.': $endnumb', 'uid: apoc.create.uuid()']; 
    foreach ($extra as $key => $value) {
      $cast_data = $this->setToTypeByModel(ANNONODE, $key, $value); 
      if($cast_data[1]){
        $phval = $phval+1; 
        $phstr = 'ph_'.strval($phval); 
        $queryparameters[] = $key.': '.'$'.$phstr;
        $querydata[$phstr] = $cast_data[0];
      }
    }
    if($constraintOne && $constraintTwo){
      //both constraints are met; connect;
      #Write a cypher query that creates a new Node with label 'Annotation'.
      #Assign an automatically created UUIDV4 to it. 
      $query = 'MATCH (t:'.TEXNODE.'),(e)
      WHERE id(t) = $texid AND id(e) = $etid 
      CREATE
        (a:'.ANNONODE.' { '.implode(', ', $queryparameters).' } ),
        (a)<-[r1:contains]-(t),
        (a)-[r2:references]->(e)
      RETURN a,t,e,r1,r2,id(a)';
      $annotdata = $this->tsx->run($query, $querydata); 

      //connect (a) to $user
      $annotationNeoID = $annotdata[0]['id(a)']; 
      $query2 = 'MATCH (u:priv_user), (a:'.ANNONODE.')
      WHERE id(u) = $userid AND id(a) = $annotationid
      CREATE (u)-[r:priv_created]->(a)
      RETURN u,r'; 
      $userdata = $this->tsx->run($query2, [
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
      
      */
      $uuid_list = array(); 
      foreach($connections as $connection){
        $start = $connection[0];
        $stop = $connection[1]; 
        //contains a bug. patch below prevents the creation of doubles!
        /*$query = ' 
        MATCH (n:'.TEXNODE.') WHERE id(n) = $texid
        OPTIONAL MATCH (a:Annotation_auto) WHERE (n:'.TEXNODE.')-[:contains]-(a) AND a.starts = $start AND a.stops = $stop
        MERGE (n)-[:contains]->(newA:Annotation_auto {starts: $start, stops: $stop, uid: apoc.create.uuid()})
        RETURN newA.uid AS uuid
        ';*/
        //Bugpatch: 
        $query = 'MATCH (n:'.TEXNODE.') 
        WHERE id(n) = $texid
        OPTIONAL MATCH (n)-[:contains]-(a:Annotation_auto) 
        WHERE a.'.ANNOSTART.' = $start AND a.'.ANNOSTOP.' = $stop
        WITH n, COUNT(a) AS annotationCount
        WHERE annotationCount = 0
        MERGE (n)-[:contains]->(newA:Annotation_auto {'.ANNOSTART.': $start, '.ANNOSTOP.': $stop, uid: apoc.create.uuid()})
        RETURN newA.uid AS uuid'; 
        $rowResult = $this->tsx->run($query, [
          'start' => $start,
          'stop' => $stop,
          'texid' => $texid
        ]);
        foreach ($rowResult as $record) {
            $uuid_list[] = $record->get('uuid');
        }

      }
      return $uuid_list; 
  }


  public function getUnlinkedAnnotationsInText($neoid){
    /*
      Annotations which are made by the NER-tool are returned without a matching ET. 
    */
    $query = 'MATCH (t:'.TEXNODE.')-[r:contains]->(a:Annotation_auto) WHERE id (t)=$neoid RETURN a,r'; 
    $result = $this->tsx->run($query, ['neoid'=>$neoid]); 
    $data = array(); 
    foreach($result as $row => $value){
      $node = $value["a"]; 
      $annotation = array();
      $annotation['annotation'] = $node->getProperty('uid'); 
      $annotation['start'] = $node->getProperty(ANNOSTART);
      $annotation['stop'] = $node->getProperty(ANNOSTOP);
      $data[]=$annotation; 
    }
    return $data;
  }

  public function extractboolval($value){
    $value = strtolower(trim($value));
    if($value == 'true'){return true;}
    if($value == 'false'){return false;}
    if($value === null){return false;}
    return boolval($value); 
  }

  
  public function getExistingAnnotationsInText($neoid, $user_sql_id_int = false){
    //$user_sql_id_int = 5;//   TEST PASSED: private nodes do not show when missmatch between owner(u.user_sqlid) and viewer(user_sql_id_int)
    //when user is false ==> only show public annotations.
    // when user is set to a matching priv_user.userid ==> show all public annotation + private annotations by $user
    //user parameter to determine if a node is private or not
    // this should be a patch for the bug further down. 
    //$query = 'MATCH (t:'.TEXNODE.')-[r:contains]->(a:'.ANNONODE.')-[l:references]->(p) where id(t)=$neoid return t,a,p;';
    /*$query = 'MATCH (t:'.TEXNODE.')-[r:contains]->(a:'.ANNONODE.')-[l:references]->(p) 
    WHERE id(t)=$neoid AND (NOT exists(a.private) OR a.private <> true)
    OPTIONAL MATCH (t:'.TEXNODE.')-[r:contains]->(a:'.ANNONODE.')<-[pc:priv_created]-(u:priv_user) 
    WHERE id(t)=$neoid and u.user_sqlid = $usersqlid
    RETURN id(a) as annoid, u.user_sqlid as userid, a.private as annoprivacyflag, t,a,p,u;';
  */

    $query = '
    // First part of the query: annotations that do not have private=true
    MATCH (t:'.TEXNODE.')-[r:contains]->(a:'.ANNONODE.')-[l:references]->(p)
    WHERE id(t) = $neoid AND (NOT exists(a.private) OR a.private <> true)
    RETURN id(a) as annoid, null as userid, a.private as annoprivacyflag, t, a, null as u, p
    
    UNION ALL
    
    // Second part of the query: annotations that have private=true and match the user condition
    MATCH (t:'.TEXNODE.')-[r:contains]->(a:'.ANNONODE.')<-[pc:priv_created]-(u:priv_user), (a)-[l:references]->(p)
    WHERE id(t) = $neoid AND u.user_sqlid = $usersqlid AND a.private = true
    RETURN id(a) as annoid, u.user_sqlid as userid, a.private as annoprivacyflag, t, a, u, p
    ';
    //patch: consider returning the property and extracting that; by default cypher will nullify non-existing properties.
    $result = $this->tsx->run($query, [
      'neoid'=>$neoid, 
      'usersqlid'=>$user_sql_id_int
    ]);
    $data = array();
    $data['user'] = $user_sql_id_int;
    $annotationData = array();
    //$anno_to_user = []; 

    //OBSOLETE CODE SINCE UPDATE
        // function controlledReply($object, $propertyName, $controlledOutput){
        //   /*
        //     The getProperty() method does not return null when a property is not set!!!
        //     In other words it must exist for the code to work, patched for now by putting
        //     it in a try catch block; with default output given as function argument.
        //   */
        //   try {
        //     return $object->getProperty($propertyName);
        //   } catch (\Exception $e) {
        //     return $controlledOutput;
        //   }
        // }// endof controlledReply.
    // END OF OBSOLETE CODE

    foreach ($result as $key => $annotationRecord) {
      $targetNodeType = $annotationRecord['p']->getLabels()[0];
      if($this->extractboolval($annotationRecord->get('annoprivacyflag'))){
        $isPrivate = True;
        //$anno_to_user[$annotationRecord->get('annoid')] = $annotationRecord->get('userid'); 
      }else{
        $isPrivate = False; 
      }
      $node = $annotationRecord->get('a');
       // foreach($annotationRecord as $subkey => $node){
          // var_dump($node);
          if($node->labels()[0] === ANNONODE){
            $anno_uuid = $node->getProperty('uid');
            //$isPrivate = controlledReply($node, 'priv_private', False);
            //$creator_uuid has to be replaced by a boolean that checks if the 
            // user is connected to this node. 
            // should be done in the root of this foreach loop to minimize addition queries. 
            // should only be done if $isPrivate === True!!
            $creator_sqlid = $annotationRecord->get('userid');
            $annotationStart = $node->getProperty(ANNOSTART);
            $annotationStop = $node->getProperty(ANNOSTOP);
            $neoID = $node['id']; 
            $map = array(
              'annotation' => $anno_uuid,
              'creator' => $creator_sqlid,
              'private' => $isPrivate,
              'start' => $annotationStart,
              'stop' => $annotationStop,
              'type' => $targetNodeType,
              'neoid' => $neoID
            );
            if($isPrivate){
              if($user_sql_id_int !== false AND $creator_sqlid === $user_sql_id_int){
                $annotationData[$anno_uuid] = $map;
              }
            }else{
              $annotationData[$anno_uuid] = $map;
            }
          }
        //}
    }
    $data['relations'] = $annotationData;
    return $data;
  }
}
