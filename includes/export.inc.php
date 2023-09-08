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
      foreach($this->entityDict as $key => $value){
        $oneEt = $dom->createElement('entity');
        $etAtrid = new DOMAttr('id', $value['primaryKey']['value']); 
        $etURI = new DOMAttr('uri', $value['primaryKey']['URI']); 
        $etLabel = new DOMAttr('label', $value['label']);
        $oneEt->appendChild($etLabel);
        $oneEt->appendChild($etAtrid);
        $oneEt->appendChild($etURI);
        $etLabel = $dom->createElement('URI', $value['primaryKey']['URI']);
        $oneEt->appendChild($etLabel); 
        foreach($value['properties'] as $key => $subvalue){
          $prop = $dom->createElement('property');
          $propAtr = new DOMAttr('name', $subvalue['name']); 
          $prop->appendChild($propAtr); 
          $propValue = $dom->createElement('value', $subvalue['value']); 
          $prop->appendChild($propValue); 
          $oneEt->appendChild($prop);
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

  public function outputAnnotations($db){
    $neoKeys = array_unique(array_column($this->annotations['relations'],'neoid'));
    $entities = array(); 
    $doneEts = array(); 
    $this->annotationToEt = array();
    foreach($this->annotations['relations'] as $keyUID => $valueArr){
      $neoID = $valueArr['neoid']; 
      $data = $db->getAnnotationInfo($neoID);
      $entityLabel = $data['entity']['labels'][0];
      $modelOfEntity = NODEMODEL[$entityLabel];
      $entityPrimaryKey = helper_extractPrimary($entityLabel);
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
        $doneEts[]=$entityLabel.$primaryKeyValue;
      }

    }
    $this->entityDict = $entities; 
  }



}



?>