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
      //$this->neoid = (int)$neo;  
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
    $annotationArray['relations']['startTest'] = array('start'=> 0, 'stop'=>11);
    $annotationArray['relations']['testOverlap'] = array('start'=> 1000, 'stop'=>1123);
    $annotationArray['relations']['copyOverlap2'] = array('start'=> 1011, 'stop'=>1021);
    $annotationArray['relations']['endTest'] = array('start'=> 1812, 'stop'=>1816);
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
      //$xml_file_name = 'movies_list.xml'; 
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
        if ($e[0]=='Annotation'){
          $elem = $dom->createElement('annotation', $e[1]);
          $elemAtr = new DOMAttr('id', $key); 
          $elem->setAttributeNode($elemAtr); 
        }else{
          $elem = $dom->createElement('unmarkedText', $e[1]);
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
    $prevAnnotKey = 0;
    $inAnnotationMode = False; 
    $annotatedText = ''; 
    $oneAnnotation = '';
    $baseString = ''; 
    $xml = new DOMDocument('1.0', 'iso-8859-1');
    $this->XMLTaggedText = []; 
    $rawTexkey = 0; 
    $prevmode = False; 
    $prevkeyList = False; 
    $keyList = False;
    //failure to detect overlapping annoations; text is duplicated!!
    foreach($this->identified as $key=> $value){
      if(array_key_exists($key, $this->breakpoints)){
        $mode = 'annotation';
        $keyList = implode(',', $this->breakpoints[$key]); 
        if (!(array_key_exists($keyList, $this->XMLTaggedText))){
          $this->XMLTaggedText[$keyList] = array('Annotation', ''); 
        }
        $this->XMLTaggedText[$keyList][1].=$value;
      }else{
        $keyList = 'textBlock_'.$rawTexkey; 
        $mode = 'text'; 
        
        if ($prevmode != 'text'){
          //echo "switch";
          $rawTexkey =$rawTexkey+1; 
          $keyList = 'textBlock_'.$rawTexkey; 
          $this->XMLTaggedText[$keyList] = array('Textblock', ''); 
        }
        //$this->XMLTaggedText[$keyList] = array('Annotation', ''); 
        $this->XMLTaggedText[$keyList][1].=$value;

      }
      $prevmode = $mode;
    }
  }

  public function outputAnnotations(){

  }



}



?>