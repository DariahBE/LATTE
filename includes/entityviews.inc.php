<?php
/*
  how to process queryresults according to queryType.
*/
die('ENTITYVIEWS NEEDS TO BE DELETED.'); 
//TODO URGENT!: you have to get completely rid of this!
// STATUS/ technically this file is completely osbolete. There are no class implementations any more!
// TODO: Test test test!!
/**
 *
 */
class View {
  private $viewtype;
  private $data;
  public $header;
  public $variants;
  public $relatedText;
  public $datasilos;


  function __construct($type, $data){
    $this->dataSilos = null;
    $this->variants = null;
    $this->relatedText = null;
    $this->viewtype = $type;
    $this->data = $data;
    switch ($this->viewtype) {
      case 'Place':
        $this->buildPlace();
        break;
      case 'Person':
        $this->buildPerson();
        break;
      case 'Annotation':
        $this->buildAnnotation();
        break;
      case 'Event':
        $this->buildEvent();
        break;
      case 'Silos':
        $this->buildSilos();
        break;
      default:
        $this->buildFallback();
        break;
    }
  }

  function buildFallback(){
    $fallBackDataModel = array();
    $primaryKey = $this->data["egoNode"]["coreID"];
    //$nodeProperties = $this->data["egoNode"]["model"];
    //BUG in some cases the model is empty! It should have the NODEMODEL constant here. 
    $nodeProperties = NODEMODEL['Variant']; 
    var_dump($this->data["egoNode"]["labels"]); 
    die();
    var_dump($this->data["egoNode"]);
    var_dump($nodeProperties);
    $fallBackDataModel["id"] = $primaryKey;
    $fallBackDataModel["properties"] = array();//$nodeProperties;
    $fallBackDataModel["relations"] = array();
    foreach ($nodeProperties as $key => $prop){
      try {
        $fallBackDataModel["properties"][$prop] = $this->data["egoNode"]["data"][0][0]["node"]["properties"][$prop];
      } catch (\Exception $e) {
        $fallBackDataModel["properties"][$prop] = NULL;
      }
    }
    //for every neighbour: find the associated PK of uid-value!
    foreach($this->data["neighbours"] as $key => $record){
      $relationType = $record["r"];
      $relatedNode = $record["t"];
      if(!array_key_exists($relationType["type"], $fallBackDataModel["relations"])){
        $fallBackDataModel["relations"][$relationType["type"]]=array();
      }
      $primaryKeyProp = helper_extractPrimary($relatedNode["labels"][0]);
      $modelview = NODEMODEL[$relatedNode["labels"][0]];
      $arr = array();
      foreach ($modelview as $key => $value) {
        //look for the public label:
        $publicLabel = $value[0];
        $arr[$publicLabel] = isset($relatedNode['properties'][$key]) ? $relatedNode['properties'][$key] : NULL;
      }
      $arr['primarykey'] = isset($relatedNode['properties'][$primaryKeyProp]) ? $relatedNode['properties'][$primaryKeyProp] : NULL;
      $fallBackDataModel["relations"][$relationType["type"]][$relatedNode["labels"][0]][] = $arr;
    }
    echo json_encode($fallBackDataModel);
    die();
  }

  function buildSilos(){
    $siloData = array();
    foreach ($this->data as $record) {
      $row = array();
      foreach (NODES["See_Also"] as $p){
        try{
          $v = $record->get("t")->getProperty($p);
        }catch(e){
          $v = null;
        }
        $row[$p] = $v;
      }
      $siloData[] = $row;
    }
    $this->datasilos = $siloData;
  }

  function buildAnnotation(){
    $annotationData = array();
    $ego = $this->data['egoNode']['coreID'];
    //var_dump($ego);

    echo json_encode($this->data);
    die();
    //throw new \Exception("method not implemented", 1);

  }

  function buildPlace(){
    $this->makeHeader($this->data["egoNode"]);
    $this->relatedVariants(true);
    $this->relatedDataSiloEntries(true);
    $this->relatedAnnotations(true);
    $this->showDataInNetwork();
  }

  function buildPerson(){
    throw new \Exception("method not implemented", 1);


  }

  function buildEvent(){
    throw new \Exception("method not implemented", 1);

  }

  function makeTable($keyValuePairs, $alternatingStyle=true){
    $table = "<table class='table-auto w-full m-8 p-8'><thead class='font-bold bg-slate-300'><tr><td>Property</td><td>Value</td></tr></thead>";
    for($i=0; $i<count($keyValuePairs); $i++){
      $rowData = $keyValuePairs[$i];
      $key = $rowData[0];
      $value = $rowData[1];
      $table .= '<tr class="odd:bg-slate-200 even:bg-slate-100"><td class="font-bold">'.$key.'</td><td>'.$value.'</td></tr>';
    }
    $table .= '<table>';
    return $table;
  }

  //subroutines: build smaller DOM components.
  function makeHeader($ego, $useKey='uid'){
    //header is the top element that is shared by all views
    //the headers shows the information related to the EGOnode:
    try{
      $egoID = $ego['data'][0]->first()['node']['properties'][$useKey];
    }catch(e){
      throw new \Exception("Property ".htmlspecialchars($useKey, ENT_QUOTES, 'UTF-8').' not defined.', 1);
    }
    // Box where metadata attributes are shown:
    //iterate over the properties in the ego node:
    //var_dump($ego['data'][0]->first()['node']['properties']);
    $dataPairsForTable = [];
    $dataPairsForTable[] = ['Primary Key', $egoID];

    foreach ($ego['data'][0]->first()['node']['properties'] as $key => $value) {
      //metadata only shows keys that hold translations:
      if(array_key_exists($key, NODEMODEL)){
        $keyTranslation = NODEMODEL[$key][0];
        $dataPairsForTable[] = [$keyTranslation, $value];
      }/*
      if(array_key_exists($key, NODEKEYSTRANSLATIONS[$this->viewtype])){
        $keyTranslation = NODEKEYSTRANSLATIONS[$this->viewtype][$key];
        $dataPairsForTable[] = [$keyTranslation, $value];
      }*/
    }
    $boxOne = "<div class='w-full'>".$this->makeTable($dataPairsForTable)."</div>"; //Box with metadata attributes.

    //Box where the sharelink is generated - including event triggers.

    //iconsource: https://heroicons.com/
    $fingerprintIcon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4" />
      </svg>';
    $clipBoardIcon = '<div><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
      <path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
      </svg></div>';
    $sharingIcon = '<div><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
      </svg></div>';
    $stableLink = $_SERVER['SERVER_NAME'].'/'.$this->viewtype.'/'.$egoID;
    $copy2clipboard = "<div onclick=\"clippy('headerURIContent', 'temp_copy_ok')\" class='flex flex-row'>{$clipBoardIcon}<p id='headerURIContent' class='text-sm'>{$stableLink}</p><p id='temp_copy_ok' class='hidden text-sm'></p></div>";
    $boxTwo = "<div class='rounded-md border-2 border-violet-800 border-solid flex-shrink justify-center justify-content'><div class='flex flex-row w-full justify-center'>{$fingerprintIcon}<h3 class='text-lg'>Stable link</h3></div><p class='text-xs'>This node has a stable identifier; you can use it to share it with your peers, as long as this node exists, anyone with this link will be able to identify public enitities by its UUID and see connected components.</p>{$copy2clipboard}</div>"; //stable ID box with sharing integrated.
    $this->header = "<div class='container row mx-auto px-4 columns-2 gap-4'>{$boxOne}<hr class='vertical'>{$boxTwo}</div>";
  }


  function showDataInNetwork(){
//    var_dump($this->data);
  //DO NOT DUMP THE DATA STRAIGHT AWAY: CLEAN SENSITIVE DATA FROM IT (querystring, connectorsettings....)
    //echo json_encode($this->data);

  }

  public function relatedDataSiloEntries($useNEO=true){
    $data = $this->data['neighbours'];
    $siloData = [];
    foreach ($data as $record) {
      $label = $record->get('t')['labels'][0];
      if($label === 'See_Also'){
        $props = $record->get('t');
        $recordFormatted = [];
        foreach(NODES[$label] as $p){
          try {
              $v = $props->getProperty($p);
          }
          catch (Exception $e) {
              $v = null;
          }
          $recordFormatted[$p] = $v;
        }
        if($useNEO){
          $recordFormatted['neoID'] = $record->get('t')['id'];
        }
        $siloData[] = $recordFormatted;
      }
    }
    $this->datasilos = $siloData;
  }

  public function relatedVariants($useNEO=true){
    /*
      function reads the data set during class initiation; parses all neigbhouring nodes
      if they have the Variant label set, they are parsed for all keys defined in the central config file.
    */
    //a variant is only present in one of the related records:
    $data = $this->data['neighbours'];
    //var_dump($data);
    $variantData = [];
    foreach ($data as $record) {
      $label = $record->get('t')['labels'][0];
      if ($label === 'Variant'){
        //echo $record->get('t')['id'];
        $props = $record->get('t');
        //var_dump(array($props));
        $recordFormatted = ['uuid'=>$props->getProperty('uid')];
        foreach(NODES[$label] as $p){
          //getProperty LAUDIS/Method does not handle non-existing properties: do it here with try-catch.
          try {
              $v = $props->getProperty($p);
          }
          catch (Exception $e) {
              $v = null;
          }
          $recordFormatted[$p] = $v;

        }
        if($useNEO){
          $recordFormatted['neoID'] = $record->get('t')['id'];
        }
        $variantData[] = $recordFormatted;
      }
    }
    $this->variants = $variantData;
  }

  public function relatedAnnotations($useNEO = true){
    //annotation is non-configurable node. DO NOT read user definitions for this.
    $data = $this->data['neighbours'];
    $relatedAnnotations = array();
    foreach($data as $record){
      $row = array();
      //var_dump($record->get('t')['labels']);
    }

  }

  /*
  public function textsMentioningEntity($useNEO = true){
    $data = $this->data['relatedTexts'];
    //var_dump($data);
    $relatedTexts = array();
    foreach ($data as $record) {
      $row = array();
      foreach(NODES['Text'] as $textproperty){
        //var_dump($textproperty);
        try {
            $v = $record->get('t')->getProperty($textproperty);
        }
        catch (Exception $e) {
            $v = null;
        }
        $row[$textproperty] = $v;
      }
      if($useNEO){
        $row['neoID'] = $record->get('t')['id'];
      }
      $relatedTexts[] = $row;
    }
    $relatedTexts = array_unique($relatedTexts, SORT_REGULAR);
    $this->relatedText = $relatedTexts;
  }*/

  /*
  public function generateJSONOnly($withNeoID){
    $this->relatedVariants($withNeoID);
    $this->relatedDataSiloEntries($withNeoID);
    //$this->relatedAnnotations($withNeoID);  // discuss: Is this even required?
    $this->textsMentioningEntity($withNeoID);
  }


  public function outputHeader(){
    echo $this->header;
  }*/

}



?>
