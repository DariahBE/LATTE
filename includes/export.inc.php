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
    $webURI = 'still working on this'; 
    if($this->mode == 'xml'){
      $dom = new DOMDocument();
      $dom->encoding = 'utf-8';
      $dom->xmlVersion = '1.0';
      $dom->formatOutput = true;
      $root = $dom->createElement('Export');
      $metaNode = $dom->createElement('metadata');
      $texNode = $dom->createElement('text'); 
      $annoNode = $dom->createElement('annotatedText'); 
      // assign fields to metadata: 
      $metaTimeStamp = $dom->createElement('requestTime', $date); 
      $metaSourceStamp = $dom->createElement( 'requestURI', htmlspecialchars($exportURL, ENT_XML1, 'UTF-8'));
      $webView = $dom->createElement('stableURI', htmlspecialchars($webURI, ENT_XML1, 'UTF-8')); 
      $metaNode->appendChild($metaTimeStamp); 
      $metaNode->appendChild($metaSourceStamp); 
      $metaNode->appendChild($webView); 
      // assign data to text: 
      $rawText = $dom->createElement('rawText', htmlspecialchars($this->rawtext)); 
      $texNode->appendChild($rawText); 
      foreach($this->XMLTaggedText as $key => $value){
        $e = $this->XMLTaggedText[$key]; 
        if ($e[1]=='annotation'){
          $elem = $dom->createElement('annotation', $e[2]);
          $elemAtr = new DOMAttr('id', $e[3]); 
          $elem->setAttributeNode($elemAtr); 
        }else{
          $elem = $dom->createElement('unmarkedText', $e[2]);
        }
        $annoNode->appendChild($elem);
      }
      $root->appendChild($metaNode);
      $root->appendChild($texNode);
      $root->appendChild($annoNode);
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
    //var_dump($this->annotations);
    //var_dump($db);
    echo "<pre>";
    print_r($this->annotations['relations']);
    echo "</pre>";
    $keys = array_keys($this->annotations['relations']);
    foreach($this->annotations['relations'] as $key =>$value){
      $internalNeoId = $value['neoid'];
      $data = $db->getAnnotationInfo($internalNeoId);
      echo "<pre>";
      $relatedMode = 'zoeken in config.';
      $entityLabel = $data['entity']['labels'][0];
      $entityProperties = $data['entity']['properties']; 
      var_dump($entityProperties);
      //print_r($data['entity']['labels']);
      //print_r($data);

      echo "</pre>";
      echo "data done";
    }
    die();
    if($this->mode == 'xml'){
      //get annotationDetails for each annotation based on id!


    }else if($this->mode == 'json'){

    }
  }



}



?>