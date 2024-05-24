<?php
/*
  how to process queryresults according to queryType.
*/
/**
 *
 */
class Blockfactory {
  private $viewtype;
  private $data;
  public $variants;     //drop
  public $relatedText;  //drop
  public $datasilos;    //drop


  function __construct($type){
    $this->dataSilos = null;    //drop
    $this->variants = null;     //drop
    $this->relatedText = null;  //drop
    $this->viewtype = $type;
  }
  //helper function (leave it here. )
  function makeTable($keyValuePairs, $alternatingStyle=true){
    $table = "<table class='table-auto w-full p-8'><thead class='font-bold bg-slate-300'><tr><td>Property</td><td>Value</td></tr></thead>";
    for($i=0; $i<count($keyValuePairs); $i++){
      $rowData = $keyValuePairs[$i];
      $key = $rowData[0];
      $value = $rowData[1];
      $table .= '<tr class="odd:bg-slate-200 even:bg-slate-100"><td class="font-bold">'.htmlspecialchars($key, ENT_QUOTES, 'UTF-8').'</td><td>'.htmlspecialchars($value, ENT_QUOTES, 'UTF-8').'</td></tr>';
    }
    $table .= '</table>';
    return $table;
  }

  //subroutines: build smaller DOM components.
  function makeIDBox($ego, $useKey='uid'){
    //header is the top element that is shared by all views
    //the headers shows the information related to the EGOnode:
    try{
      $egoID = $ego['data'][0]->first()['node']['properties'][$useKey];
    }catch(e){
      throw new \Exception("Property ".htmlspecialchars($useKey, ENT_QUOTES, 'UTF-8').' not defined.', 1);
    }
    // Box where metadata attributes are shown:
    //iterate over the properties in the ego node:
    $dataPairsForTable = [];
    $dataPairsForTable[] = ['Primary Key', $egoID];
    
    foreach ($ego['data'][0]->first()['node']['properties'] as $key => $value) {
      if(array_key_exists($key, NODEMODEL)){
        $keyTranslation = NODEMODEL[$key][0];
        $dataPairsForTable[] = [$keyTranslation, $value];
      }
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
    $stableLink = $_SERVER['SERVER_NAME'].'/URI/'.$this->viewtype.'/'.htmlspecialchars($egoID, ENT_QUOTES, 'UTF-8');
    $copy2clipboard = "<div onclick=\"clippy('headerURIContent', 'temp_copy_ok')\" class='flex flex-row'>{$clipBoardIcon}<p id='headerURIContent' class='text-sm'>{$stableLink}</p><p id='temp_copy_ok' class='hidden text-sm'></p></div>";
    $boxTwo = "<div class='break-inside-avoid-column rounded-md border-2 border-violet-800 border-solid sm:w-full w-4/5 justify-center justify-content'><div class='flex flex-row w-full justify-center'>{$fingerprintIcon}<h3 class='text-lg'>Stable link</h3></div><p class='text-xs'>This node has a stable identifier; you can use it to share it with your peers, as long as this node exists, anyone with this link will be able to identify public enitities by its UUID and see connected components.</p>{$copy2clipboard}</div>"; //stable ID box with sharing integrated.
    return "{$boxOne}{$boxTwo}";
  }


}



?>
