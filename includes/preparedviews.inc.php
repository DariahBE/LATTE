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
    $table = "<div class='overflow-x-auto rounded-xl border border-slate-200 shadow-sm bg-white'><table class='w-full border-collapse text-sm divide-y divide-slate-200'><thead class='bg-slate-50 text-slate-700 font-semibold text-xs uppercase tracking-wider'><tr><th class='py-3 px-4 text-left'>Property</th><th class='py-3 px-4 text-left'>Value</th></tr></thead><tbody class='divide-y divide-slate-100'>";
    for($i=0; $i<count($keyValuePairs); $i++){
      $rowData = $keyValuePairs[$i];
      $key = $rowData[0];
      $value = $rowData[1];
      $keyStr = is_scalar($key) ? (string)$key : json_encode($key);
      $valStr = is_bool($value) ? ($value ? 'true' : 'false') : (is_scalar($value) ? (string)$value : json_encode($value));
      $table .= '<tr class="odd:bg-white even:bg-slate-50/50 hover:bg-slate-100/60 transition-colors"><td class="py-2.5 px-4 font-semibold text-slate-800 whitespace-nowrap">'.htmlspecialchars($keyStr, ENT_QUOTES, 'UTF-8').'</td><td class="py-2.5 px-4 text-slate-600 break-all">'.htmlspecialchars($valStr, ENT_QUOTES, 'UTF-8').'</td></tr>';
    }
    $table .= '</tbody></table></div>';
    return $table;
  }

  //subroutines: build smaller DOM components.
  function makeIDBox($ego, $useKey='uid'){
    //header is the top element that is shared by all views 
    //the headers shows the information related to the EGOnode:
    try{
      $egoID = $ego['data'][0]->first()['node']['properties'][$useKey];
    }catch(\Throwable $e){
      throw new \Exception("Property ".htmlspecialchars($useKey, ENT_QUOTES, 'UTF-8').' not defined.', 1);
    }
    // Box where metadata attributes are shown:
    //iterate over the properties in the ego node:
    $dataPairsForTable = [];
    $dataPairsForTable[] = ['Primary Key', $egoID];
    $egoLabel = $ego['data'][0]->first()['node']->getLabels()[0]; 
    foreach ($ego['data'][0]->first()['node']['properties'] as $key => $value) {
      if(array_key_exists($key, NODEMODEL[$egoLabel])){
        $keyTranslation = NODEMODEL[$egoLabel][$key][0];
        $dataPairsForTable[] = [$keyTranslation, $value];
      }
    }
    $boxOne = "<div class='w-full'>".$this->makeTable($dataPairsForTable)."</div>"; //Box with metadata attributes.

    //Box where the sharelink is generated - including event triggers.
    //iconsource: https://heroicons.com/
    $fingerprintIcon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4" />
      </svg>';
    $clipBoardIcon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
      </svg>';

    $API_icon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 0 0 2.25-2.25V6.75a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 6.75v10.5a2.25 2.25 0 0 0 2.25 2.25Zm.75-12h9v9h-9v-9Z" />
      </svg>';

    $baseUrl = defined('BASE_URL') ? BASE_URL : '';
    $serverName = htmlspecialchars($_SERVER['SERVER_NAME'] ?? 'localhost', ENT_QUOTES, 'UTF-8');
    $stableLink = $serverName . $baseUrl . '/URI/' . htmlspecialchars((string)$this->viewtype, ENT_QUOTES, 'UTF-8') . '/' . htmlspecialchars((string)$egoID, ENT_QUOTES, 'UTF-8');
    $apiLink = $serverName . $baseUrl . '/API/' . htmlspecialchars((string)$this->viewtype, ENT_QUOTES, 'UTF-8') . '/' . htmlspecialchars((string)$egoID, ENT_QUOTES, 'UTF-8');
    $copy2clipboard = "<div onclick=\"clippy('headerURIContent', 'temp_copy_ok')\" class='flex items-center justify-between p-3 rounded-lg bg-slate-50 border border-slate-200 hover:bg-slate-100 cursor-pointer transition-colors group'>
      <div class='flex items-center gap-2.5 overflow-hidden'>
        <span class='text-slate-400 group-hover:text-indigo-600 transition-colors'>{$clipBoardIcon}</span>
        <span class='text-xs font-mono text-slate-700 truncate' id='headerURIContent'>{$stableLink}</span>
      </div>
      <span id='temp_copy_ok' class='text-xs font-medium text-emerald-600 whitespace-nowrap ml-2 hidden'></span>
    </div>";

    $copyAPI = "<div onclick=\"clippy('headerAPIContent', 'temp_copy_API_ok')\" class='flex items-center justify-between p-3 rounded-lg bg-slate-50 border border-slate-200 hover:bg-slate-100 cursor-pointer transition-colors group'>
      <div class='flex items-center gap-2.5 overflow-hidden'>
        <span class='text-slate-400 group-hover:text-indigo-600 transition-colors'>{$API_icon}</span>
        <span class='text-xs font-mono text-slate-700 truncate' id='headerAPIContent'>{$apiLink}</span>
      </div>
      <span id='temp_copy_API_ok' class='text-xs font-medium text-emerald-600 whitespace-nowrap ml-2 hidden'></span>
    </div>";

    $boxTwo = "<div class='bg-white border border-slate-200 rounded-xl shadow-sm p-6 flex flex-col justify-between'>
                  <div>
                    <div class='flex items-center gap-2.5 mb-3'>
                      <div class='w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold'>
                        {$fingerprintIcon}
                      </div>
                      <div>
                        <h3 class='text-base font-semibold text-slate-900'>Persistent identifier</h3>
                        <p class='text-xs text-slate-500'>Shareable URI & API endpoint</p>
                      </div>
                    </div>
                    <p class='text-xs text-slate-600 leading-relaxed mb-4'>
                      This node has a persistent identifier. Anyone with this link can resolve this entity and inspect connected components.
                    </p>
                  </div>
                  <div class='space-y-3'>
                    {$copy2clipboard}
                    {$copyAPI}
                  </div>
                </div>";
    return "{$boxOne}{$boxTwo}";
  }


}



?>
