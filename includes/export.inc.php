<?php

/**
 *  class to generate exports of single text file.
 *    Should be able to set headers of creation page; 
 *    Should be able to verify modes
 *    Should be able to fetch annotations and encode them according to the mode. 
 */

class Exporter {
  protected $client; 
  private $allowedModes; 
  private $mode; 
  private $rawtext; 
  private $identified; 
  private $annotations; 
  private $breakpoints; 
  private $XMLTaggedText;
  function __construct($client, $mode){
    $this->allowedModes = array('json', 'xml'); 
    $this->client = $client; 
    if(in_array($mode, $this->allowedModes)){
      $this->mode = $mode; 
    }else{
      die(); //reject the request. 
    }
  }
  
  public function setText($text){
    $this->rawtext = $text; 
  }

  public function setIdentifiedText($text){
    $this->identified = $text; 
  }

  public function setAnnotations($annotationArray){
    $this->annotations = $annotationArray; 
    $breakpoints = array(); 
    $annotations = $annotationArray['relations']; 
    foreach($annotations as $key=> $value){
      for($i = $value['start']; $i <= $value['stop']; $i++){
        if(!(array_key_exists($i, $breakpoints))){
          $breakpoints[$i] = array(); 
        }
          $breakpoints[$i][] = $key; 
      }
    }
    $this->breakpoints = $breakpoints; 
  }

  public function outputHeaders(){
    if ($this->mode == 'xml'){
      return header('Content-Type: text/xml');
    }else if($this->mode == 'json'){
      return header('Content-Type: application/json; charset=utf-8');
    }
  }

  public function outputContent(){
    $date = date("d-m-Y H:i:s");
    $exportURL = $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
    if($this->mode == 'xml'){
      $dom = new DOMDocument();
      $dom->encoding = 'utf-8';
      $dom->xmlVersion = '1.0';
      $dom->formatOutput = true;
      $root = $dom->createElement('Export');
      $metaNode = $dom->createElement('metadata');
      $texNode = $dom->createElement('text'); 
      $annoNode = $dom->createElement('annotatedText'); 
      $linkAnnoNode = $dom->createElement('annotations'); 
      $linkEntityNode = $dom->createElement('entities'); 
      // assign fields to metadata: 
      $metaTimeStamp = $dom->createElement('requestTime', $date); 
      $metaSourceStamp = $dom->createElement( 'requestURI', htmlspecialchars($exportURL, ENT_XML1, 'UTF-8'));
      $metaNode->appendChild($metaTimeStamp); 
      $metaNode->appendChild($metaSourceStamp); 
      // assign data to text: 
      $rawText = $dom->createElement('rawText', htmlspecialchars($this->rawtext)); 
      $texNode->appendChild($rawText);
      foreach($this->XMLTaggedText as $key => $value){
        $e = $this->XMLTaggedText[$key]; 
        if ($e[1]=='annotation'){
          $elem = $dom->createElement('annotation', htmlspecialchars($e[2]));
          $elemAtr = new DOMAttr('id', $e[3]); 
          $elem->setAttributeNode($elemAtr); 
        }else{
          $elem = $dom->createElement('unmarkedText', htmlspecialchars($e[2]));
        }
        $annoNode->appendChild($elem);
      }
      //adding annotationLinks: 
      foreach($this->annotationToEt as $key => $value){
        $annotationReference = $dom->createElement('annotation'); 
        $annotationID = new DOMAttr('id', $key); 
        $annotationReference->appendChild($annotationID);
        $referencedNode = $dom->createElement('references');
        $referencedNodeLabel = new DOMAttr('label', $value[0]);
        $referencedNodePrimary = new DOMAttr('label', $value[1]);
        $referencedNode->appendChild($referencedNodeLabel);
        $referencedNode->appendChild($referencedNodePrimary);
        $annotationReference->appendChild($referencedNode);
        $linkAnnoNode->appendChild($annotationReference);

      }
      //adding entityLinks: 
      // missing NEO id in entitydict. 
      foreach($this->entityDict as $annotkey => $value){
        $oneEt = $dom->createElement('entity');
        $etAtrid = new DOMAttr('id', $value['primaryKey']['value']); 
        $etURI = new DOMAttr('uri', $value['primaryKey']['URI']); 
        $etLabel = new DOMAttr('label', $value['label']);
        $oneEt->appendChild($etLabel);
        $oneEt->appendChild($etAtrid);
        $oneEt->appendChild($etURI);
        $etLabel = $dom->createElement('URI', $value['primaryKey']['URI']);
        $oneEt->appendChild($etLabel); 
        $etkey = $value['et_neo']; 
        foreach($value['properties'] as $key => $subvalue){
          $prop = $dom->createElement('property');
          $propAtr = new DOMAttr('name', $subvalue['name']); 
          $prop->appendChild($propAtr); 
          $propValue = $dom->createElement('value', $subvalue['value']); 
          $prop->appendChild($propValue); 
          $oneEt->appendChild($prop);
        }
        if (array_key_exists($etkey, $this->varspellings)){
          $variantBox = $dom->createElement('spelling_variants'); 
          if(array_key_exists('labelVariants', $this->varspellings[$etkey])){
            foreach($this->varspellings[$etkey]['labelVariants'] as $showkey => $showvalue){
              $variantRow = $dom->createElement('variant'); 
              $varValue = new DOMAttr('string', $showvalue['value']);
              $varUID = new DOMAttr('uuid', $showvalue['uid']); 
              $variantRow->appendChild($varValue);
              $variantRow->appendChild($varUID);
              $variantBox->appendChild($variantRow); 
            }
          }
          $oneEt->appendChild($variantBox); 
        }
        $linkEntityNode->appendChild($oneEt); 
      }
      $root->appendChild($metaNode);
      $root->appendChild($texNode);
      $root->appendChild($annoNode);
      $root->appendChild($linkAnnoNode);
      $root->appendChild($linkEntityNode);
      $dom->appendChild($root);
      return $dom->saveXML();
    }else if ($this->mode == 'json'){
      //var_dump($this->varspellings[3202]['labelVariants']); 
      foreach($this->varspellings as $subkey =>$subvalue){
        var_dump($subkey); 
        if(array_key_exists('labelVariants', $subvalue)){
          foreach($subvalue['labelVariants'] as $showkey => $showvalue){
            //var_dump($showvalue['value']); 
            //var_dump($showvalue['uid']);
          }
        }
      }
      $taggedTextArray = array(); 
      foreach($this->XMLTaggedText as $key => $value){
        $taggedTextArray[] = array(
          'type'=>$value[1],
          'content'=>$value[2],
          'id'=>$value[3]
        );
      }
      $annotationLinks = array();
      //var_dump($this->annotations);
      foreach($this->annotationToEt as $key => $value){
        $annotationLinks[] = array(
          'annotationId' => $key,
          'referencesLabel' => $value[1]
        );
      }
      $entityLinks = array(); 
      foreach($this->entityDict as $key => $value){
        //$entityLinks[$key] = $value;
        //var_dump($key);
        $properties = array(); 
        foreach($value['properties'] as $propkey =>$propvalue){
          $properties[]=$propvalue;
        }
        $entityLinks[]  =  array(
          'label' => $value['label'],
          'pk' => $value['primaryKey']['value'],
          'uri' => $value['primaryKey']['URI'],
          'properties' => $properties
        );
      }
      $root = array(
        'metadata' => array(
          'requestTime' => $date,
          'requestURI' => $exportURL
        ), 
        'text' => array(
          'rawText' => $this->rawtext
        ),
        'annotatedText' => $taggedTextArray, 
        'annotations' => $annotationLinks, 
        'entities' => $entityLinks
      ); 
      echo json_encode($root); 
    }
  }

  public function generateAnnotatedText(){
    $prevtype = False;
    $baseString='';
    $this->XMLTaggedText = []; 
    $blockKey = 0; 
    $forceSwap = False; 
    $prevAnnotationKey = ''; 
    foreach($this->identified as $index=> $character){
      if(array_key_exists($index, $this->breakpoints)){
        //annotation: if $index exists as a breakpoint!
        $type = "annotation";
        $currentAnnotationKey = implode(',', $this->breakpoints[$index]);
        //if two annotation follow each other, or have an overlap, detect it like this:
        if ($prevAnnotationKey != '' && $prevAnnotationKey != $currentAnnotationKey){
          $forceSwap = True;
        }
      }else{
        //text: 
        $type = "text"; 
        $currentAnnotationKey = ''; 
      }
      //when it switches between types or adjacent/overlapping breakpoints: 
      if($prevtype != $type || $forceSwap){
        if($baseString != ''){
          $forceSwap=False;
          $this->XMLTaggedText[$blockKey] = array($blockKey, $prevtype, $baseString, $prevAnnotationKey); 
          $baseString = '';
          $blockKey = $blockKey+1;  
        }
      }
      //always do: 
      $baseString.=$character;
      $prevtype = $type; 
      $prevAnnotationKey = $currentAnnotationKey; 
    }
    //append the very last item!
    $this->XMLTaggedText[$blockKey] = array($blockKey, $prevtype, $baseString, $prevAnnotationKey); 
  }

/*  //If you want to add variants to the export. It needs to happen here!
  $variants = $db->findVariants($neoID); 
  $entities['variants'] = array(); 
  $entities['variants'] = $variants; 
*/
  public function outputAnnotations($db){
    $neoKeys = array_unique(array_column($this->annotations['relations'],'neoid'));
    $entities = array(); 
    $doneEts = array(); 
    $this->annotationToEt = array();
    $this->varspellings = array(); 
    foreach($this->annotations['relations'] as $keyUID => $valueArr){
      $neoID = $valueArr['neoid']; 
      //you sent the wrong NEO ID to the fetchvariants method!
      
      $data = $db->getAnnotationInfo($neoID);
      $entityLabel = $data['entity']['labels'][0];
      $modelOfEntity = NODEMODEL[$entityLabel];
      $entityPrimaryKey = helper_extractPrimary($entityLabel);
      $entityNeoId = $data['entity_neo_id']; 
      $variants = $db->fetchVariants($entityNeoId); 
      //echo die($entityNeoId); 
      $primaryKeyValue = $data['entity']['properties'][$entityPrimaryKey];
      $this->annotationToEt[$keyUID] = array($entityLabel, $primaryKeyValue);
      if (!(in_array($entityLabel.$primaryKeyValue, $doneEts))){
        $stableLink = $_SERVER['SERVER_NAME'].'/URI/'.$entityLabel.'/'.$primaryKeyValue;
        //store in array: 
        $entities[$neoID] = array();
        $entities[$neoID]['label'] = $entityLabel;
        $entities[$neoID]['linkedByAnnotation'] = $keyUID;
        //primary key: 
        $entities[$neoID]['primaryKey'] = array();
        $entities[$neoID]['primaryKey']['name'] = $entityPrimaryKey;
        $entities[$neoID]['primaryKey']['value'] = $primaryKeyValue;
        $entities[$neoID]['primaryKey']['URI'] = $stableLink;
        //other properties: 
        $entities[$neoID]['properties'] = array(); 
        foreach($data['entity']['properties'] as $key => $value){
          if(array_key_exists($key, $modelOfEntity)){
            $entities[$neoID]['properties'][$key] = array(); 
            $entities[$neoID]['properties'][$key]['name'] = $modelOfEntity[$key][0];
            $entities[$neoID]['properties'][$key]['value'] = $value; 
          }
        }
        //update this to fit the actual neo id key . 
        $entities[$neoID]['et_neo'] = $entityNeoId;
        $this->varspellings[$entityNeoId] = $variants; 
        //$entities[$neoID]['variants'] = array(); 
        //$entities[$neoID]['variants'] = $variants;       
        $doneEts[]=$entityLabel.$primaryKeyValue;
      }

    }
    $this->entityDict = $entities; 
  }



}



?>