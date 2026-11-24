<?php
  class Navbar{
    public $nav; 
    function __construct($admin=False){
    $base = defined('BASE_URL') ? BASE_URL : '';
    $userpageHref = !empty($_SESSION['userid']) ? $base.'/user/mypage.php' : $base.'/user/login.php';
    $this->nav = '
<nav class="fixed w-full bg-gray-50 dark:bg-gray-700 z-50">
  <div class="flex flex-wrap">
    <div class="justify-between items-center mx-auto max-w-screen-xl px-4 md:px-6 py-2.5">
      <a href="'.$base.'/" class="flex items-center">
        <img src="'.$base.'/images/logo.png" class="h-6 mr-3 sm:h-9" alt="App Logo" />
        <span class="self-center text-xl font-semibold whitespace-nowrap dark:text-white">'.PROJECTNAME.'</span>
      </a>
    </div>
    <div class="max-w-screen-xl px-4 py-3 mx-auto md:px-6">
      <div class="flex items-center">
        <ul class="flex flex-row mt-0 mr-6 space-x-8 text-sm font-medium">
          <li>
            <a href="'.$base.'/" class="text-gray-900 dark:text-white hover:underline" aria-current="page">Home</a>
          </li>
          <li>
            <a href="'.$base.'/text/-1" class="text-gray-900 dark:text-white hover:underline" aria-current="page">Texts</a>
          </li>
          <li>
            <a href="'.$base.'/collections.php" class="text-gray-900 dark:text-white hover:underline">Collections</a>
          </li>
          <li>
            <a href="'.$userpageHref.'" class="text-gray-900 dark:text-white hover:underline">Userpage</a>
          </li>
          <li>
            <a href="'.$base.'/create.php" class="text-gray-900 dark:text-white hover:underline">Create</a>
          </li>
          <li>
            <a href="'.$base.'/search.php" class="text-gray-900 dark:text-white hover:underline">Search</a>
          </li>
          '.
          $this->linkToAdmin($admin).
          $this->sessiontoggle().'
        </ul>
      </div>
    </div>
  </div>
</nav>
<div id="navbufferHeight" class= "relative h-8 sm:h-12 py-12 sm:py-8 w-full z-10"></div>
'; 

  }

  function sessiontoggle(){
    $base = defined('BASE_URL') ? BASE_URL : '';
    $logout = '<li><a href="'.$base.'/user/logout.php" class="text-gray-900 dark:text-white hover:underline">Logout</a></li>'; 
    if(isset($_SESSION['userid'])){
      return $logout; 
    }
     return '';
  }

  function linkToAdmin($showAdmin){
    $base = defined('BASE_URL') ? BASE_URL : '';
    $admin = '<li>
    <a href="'.$base.'/admin/index.php" class="text-gray-900 dark:text-white hover:underline">Admin</a>
  </li>'; 
  if($showAdmin){
    return $admin; 
  }else{
    return ''; 
  }
  }


  public function getNav(){
    return $this->nav; 
  }

}





?>
