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
        <ul class="flex flex-row items-center mt-0 mr-6 space-x-8 text-sm font-medium">
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
          $this->linkToMerge().
          $this->linkToAdmin($admin).
          $this->sessiontoggle().
          $this->userInitialsBadge().'
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

  /**
   * Build a circular initials badge for the currently logged-in user.
   */
  function userInitialsBadge(){
    if(empty($_SESSION['userid'])){
      return '';
    }
    $base = defined('BASE_URL') ? BASE_URL : '';
    $username = isset($_SESSION['username']) && is_string($_SESSION['username']) ? trim($_SESSION['username']) : '';
    $initials = $this->extractInitials($username);
    $label = $username !== '' ? 'Logged in as '.$username : 'Active session';
    $escapedLabel = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
    $escapedInitials = htmlspecialchars($initials, ENT_QUOTES, 'UTF-8');

    return '<li>
      <a href="'.$base.'/user/mypage.php" title="'.$escapedLabel.'" aria-label="'.$escapedLabel.'" class="relative inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-600 text-white text-xs font-semibold tracking-wide hover:bg-indigo-700 transition-colors">
        '.$escapedInitials.'
        <span class="absolute -bottom-0.5 -right-0.5 block h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-gray-50 dark:ring-gray-700" aria-hidden="true"></span>
      </a>
    </li>';
  }

  /**
   * Extract up to two uppercase initials from a username or full name.
   */
  private function extractInitials($username){
    if($username === ''){
      return 'U';
    }
    $parts = preg_split('/[\s._-]+/u', $username, -1, PREG_SPLIT_NO_EMPTY);
    $upper = function($str){
      return function_exists('mb_strtoupper') ? mb_strtoupper($str, 'UTF-8') : strtoupper($str);
    };
    $firstChar = function($str){
      return function_exists('mb_substr') ? mb_substr($str, 0, 1, 'UTF-8') : substr($str, 0, 1);
    };

    if(is_array($parts) && count($parts) >= 2){
      return $upper($firstChar($parts[0]).$firstChar($parts[count($parts) - 1]));
    }
    $single = is_array($parts) && count($parts) === 1 ? $parts[0] : $username;
    $twoChars = function_exists('mb_substr') ? mb_substr($single, 0, 2, 'UTF-8') : substr($single, 0, 2);
    return $upper($twoChars);
  }

  function linkToMerge(){
    $base = defined('BASE_URL') ? BASE_URL : '';
    $role = isset($_SESSION['userrole']) && is_string($_SESSION['userrole']) ? strtolower($_SESSION['userrole']) : '';
    if ($role === 'admin' || $role === 'projectlead') {
      return '<li><a href="'.$base.'/merge_entities.php" class="text-gray-900 dark:text-white hover:underline">Merge</a></li>';
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
