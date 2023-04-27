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
      $annoNode = $dom->createElement('annotation'); 
      // assign fields to metadata: 
      $metaTimeStamp = $dom->createElement('requestTime', $date); 
      $metaSourceStamp = $dom->createElement( 'requestURI', htmlspecialchars($exportURL, ENT_XML1, 'UTF-8'));
      $webView = $dom->createElement('stableURI', htmlspecialchars($webURI, ENT_XML1, 'UTF-8')); 
      $metaNode->appendChild($metaTimeStamp); 
      $metaNode->appendChild($metaSourceStamp); 
      $metaNode->appendChild($webView); 
      // assign data to text: 
      $rawText = $dom->createElement('rawText', htmlspecialchars($this->rawtext)); 
      $annotatedText = $dom->createElement('annotatedText', $this->XMLTaggedText);
      $texNode->appendChild($rawText); 
      $texNode->appendChild($annotatedText); 
      //$attr_date_field = new DOMAttr('requestTime', $date); 
      //$attr_movie_id = new DOMAttr('movie_id', '5467');
      /*$movie_node->setAttributeNode($attr_date_field);
      //$movie_node->setAttributeNode($attr_movie_id);
      $child_node_title = $dom->createElement('Title', 'The Campaign');
      $movie_node->appendChild($child_node_title);
      $child_node_year = $dom->createElement('Year', 2012);
      $movie_node->appendChild($child_node_year);
      $child_node_genre = $dom->createElement('Genre', 'The Campaign');
      $movie_node->appendChild($child_node_genre);
      $child_node_ratings = $dom->createElement('Ratings', 6.2);
      $movie_node->appendChild($child_node_ratings);*/
      $root->appendChild($metaNode);
      $root->appendChild($texNode);
      $root->appendChild($annoNode);
      $dom->appendChild($root);
      //$dom->save($xml_file_name);
      return $dom->saveXML(); //echo "$xml_file_name has been successfully created";
  
    }else if ($this->mode == 'json'){

    }
  }

  public function generateAnnotatedText(){
    // BUG: export is not okay with this, you need to make a DOM object here!!
    $prevAnnotKey = 0;
    $inAnnotationMode = False; 
    $annotatedText = ''; 
    $oneAnnotation = '';
    foreach($this->identified as $key=> $value){
      if(!(array_key_exists($key, $this->breakpoints))){
        //letter is not part of annotation and script was in annotationmode: close it off.
        if ($inAnnotationMode){
          $annotatedText .=$oneAnnotation.'</annotation>'; 
          //reset to defaults!
          $oneAnnotation = ''; 
          $prevAnnotKey = 0; 
          $inAnnotationMode = False; 
        }
        // letter is not part of an annotation;
        $annotatedText.=$value;

      }else{
        //letter is part of an annotation.
        //Cases
        //1) New annotation, 
        //2) adding to one annotation.
        //3) In annotationmode and creating new annotation!
        $keyList = implode(', ', $this->breakpoints[$key]); 
        $sameAsPrev = $keyList == $prevAnnotKey; 
        //CASE1: 
        if (!($inAnnotationMode)){
          $oneAnnotation .='<annotation id="'.$keyList.'">'.$value;
          $inAnnotationMode = True;

          $prevAnnotKey = $keyList; 
        }else{
          $prevAnnotKey = $keyList; 
          if ($sameAsPrev){
            $oneAnnotation.=$value;
          }else{
            $oneAnnotation .= '</annotation>'; 
            $annotatedText .=$oneAnnotation;
            $oneAnnotation = '<annotation id="'.$keyList.'">'.$value;
          }
        }
      }
    }
    if($oneAnnotation != ''){
      //append trailing annotation: 
      $oneAnnotation .= '</annotation>'; 
      $annotatedText .=$oneAnnotation;
    }
    $this->XMLTaggedText =  $annotatedText; 
  }

  public function outputAnnotations(){

  }



}



?>