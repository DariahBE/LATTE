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
    //$annotationArray['relations']['testOverlap'] = array('start'=> 1000, 'stop'=>1123);
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

  public function generateAnnotatedText(){
    $prevAnnot = 0;
    $inAnnotationMode = False; 
    $annotatedText = ''; 
    $oneAnnotation = ''; 
    foreach($this->identified as $key=> $value){
      if($inAnnotationMode){

      }
      if(!(array_key_exists($key, $this->breakpoints))){
        $annotatedText.=$value;
        $inAnnotationMode = False; 
      }else{
        //assign to prevAnnot: 
        $inAnnotationMode = True; 
        $prevAnnot = $value; 
        $keyList = implode(',', $key); 
        if (!($inAnnotationMode)){
          $oneAnnotation .='<annotation id="'.$keyList.'">'; 
          $inAnnotationMode = True;
        }

      }
    }
    return $annotatedText; 
  }

  public function outputAnnotations(){

  }



}



?>