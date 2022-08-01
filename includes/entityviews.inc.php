<?php
/*
  how to process queryresults according to queryType.
*/

/**
 *
 */
class View {
  private $viewtype;
  private $data;
  public $header;


  function __construct($type, $data){

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
      default:
        throw new \Exception("The requested view is not implemented. Quitting.", 1);
    }
  }

  function buildAnnotation(){

  }

  function buildPlace(){
    $this->makeHeader($this->data['egoNode']);

  }

  function buildPerson(){

  }

  function buildEvent(){
    throw new \Exception("method not implemented", 1);

  }


  //subroutines: build smaller DOM components.
  function makeHeader($ego, $useKey='uid'){
    //header is the top element that is shared by all views
    //the headers shows the information related to the EGOnode:
    //iconsource: https://heroicons.com/
    //useKey = is an extra variable; it'll read the UID key in the $ego node unless otherwise specified
    $fingerprintIcon = '<div class="rounded-full border-4 border-violet-800 border-solid m-2 p-2 absolute flex flex-shrink"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4" />
      </svg></div>';
    $clipBoardIcon = '<div><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
      <path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
      </svg></div>';
    $sharingIcon = '<div><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
      </svg></div>';

    $copy2clipboard = "<div onclick=clippy('headerURIContent') class='flex flex-row'>{$clipBoardIcon}<p id='headerURIContent'>test</p><pid='temp_copy_ok'></p></div>";
    $share2socials = "<div id='socialsBox'></div>";
    $shareBox = "<div class='relative'>{$fingerprintIcon}</div>";
    $boxOne = "<div class='w-1/2'></div>"; //Box with metadata attributes.
    $boxTwo = "<div class='w-1/2 rounded-md border-2 border-violet-800 border-solid flex-shrink justify-center justify-content'><h3 class='text-lg'>Stable link</h3><p class='text-sm'>This node has a stable identifier; you can use it to share it with your peers, as long as this node exists, anyone with this link will be able to identify public enitities by its UUID and see connected components.</p>{$shareBox}{$copy2clipboard}{$share2socials}</div>"; //stable ID box with sharing integrated.

    $this->header = "<div class='flex flex-row'>{$boxOne}<hr class='vertical'>{$boxTwo}</div>";

    var_dump($ego);
  }

  function makeTable(){

  }


  public function outputHeader(){
    echo $this->header;
  }

}



?>
