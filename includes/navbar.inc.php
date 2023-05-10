<?php
  class Navbar{
    function __construct($admin=False){
    $this->nav = '
<nav class="fixed w-full bg-gray-50 dark:bg-gray-700 z-50">
  <div class="flex flex-wrap">
    <div class="justify-between items-center mx-auto max-w-screen-xl px-4 md:px-6 py-2.5">
      <a href="/" class="flex items-center">
        <img src="/images/logo.png" class="h-6 mr-3 sm:h-9" alt="App Logo" />
        <span class="self-center text-xl font-semibold whitespace-nowrap dark:text-white">'.PROJECTNAME.'</span>
      </a>
    </div>
    <div class="max-w-screen-xl px-4 py-3 mx-auto md:px-6">
      <div class="flex items-center">
        <ul class="flex flex-row mt-0 mr-6 space-x-8 text-sm font-medium">
          <li>
            <a href="/" class="text-gray-900 dark:text-white hover:underline" aria-current="page">Home</a>
          </li>
          <li>
            <a href="/user/login.php" class="text-gray-900 dark:text-white hover:underline">Userpage</a>
          </li>
          <li>
            <a href="/create.php" class="text-gray-900 dark:text-white hover:underline">Create</a>
          </li>
          <li>
            <a href="/search.php" class="text-gray-900 dark:text-white hover:underline">Search</a>
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
    $logout = '<li><a href="user/logout.php" class="text-gray-900 dark:text-white hover:underline">Logout</a></li>'; 
    if(isset($_SESSION['userid'])){
      return $logout; 
    }
     return '';
  }

  function linkToAdmin($showAdmin){
    $admin = '<li>
    <a href="/admin/index.php" class="text-gray-900 dark:text-white hover:underline">Admin</a>
  </li>'; 
  if($showAdmin){
    return $admin; 
  }else{
    return ''; 
  }
  }

}





?>
