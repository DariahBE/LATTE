<?php
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/navbar.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title><?php echo PROJECTNAME;?></title>
    <link rel="stylesheet" href="/CSS/stylePublic.css">
    <script src="/JS/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="JS/homepage.js"></script>
  </head>
  <body class="bg-amber-200">
    <div>
      <?php
        $navbar = new Navbar(); 
        echo $navbar->getNav(); 
      ?>

    </div>
    <div class="relative scroll-smooth">
        <div class="sticky top-0 h-screen flex flex-col items-center justify-center bg-amber-200">
            <h1 class="text-4xl"><?php echo PROJECTNAME;?></h1>
            <h2 class="text-2xl">Powered by CLARIAH-VL</h2>
            <img class="w-1/2" src="images/clariah_vl_logo.png"/>
            <!-- scroll down: cta -->
            <div class="absolute bottom-10 ">
              <svg class="animate-bounce flex-no-shrink flex-shrink-0 fill-current w-12 h-12" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 122.88 120.64" style="enable-background:new 0 0 122.88 120.64" xml:space="preserve"><g><path d="M108.91,54.03c1.63-1.55,3.74-2.31,5.85-2.28c2.11,0.03,4.2,0.84,5.79,2.44l0.12,0.12c1.5,1.58,2.23,3.6,2.2,5.61 c-0.03,2.01-0.82,4.02-2.37,5.55c-17.66,17.66-35.61,35.13-53.4,52.68c-0.05,0.07-0.1,0.13-0.16,0.19 c-1.63,1.55-3.76,2.31-5.87,2.28c-2.11-0.03-4.21-0.85-5.8-2.45l-0.26-0.27C37.47,100.43,19.87,82.98,2.36,65.46 C0.82,63.93,0.03,61.93,0,59.92c-0.03-2.01,0.7-4.03,2.21-5.61l0.15-0.15c1.58-1.57,3.66-2.38,5.76-2.41 c2.1-0.03,4.22,0.73,5.85,2.28l47.27,47.22L108.91,54.03L108.91,54.03z M106.91,2.26c1.62-1.54,3.73-2.29,5.83-2.26 c2.11,0.03,4.2,0.84,5.79,2.44l0.12,0.12c1.5,1.57,2.23,3.6,2.21,5.61c-0.03,2.01-0.82,4.02-2.37,5.55 C101.2,31.01,84.2,48.87,67.12,66.39c-0.05,0.07-0.11,0.14-0.17,0.21c-1.63,1.55-3.76,2.31-5.87,2.28 c-2.11-0.03-4.21-0.85-5.8-2.45C38.33,48.94,21.44,31.36,4.51,13.83l-0.13-0.12c-1.54-1.53-2.32-3.53-2.35-5.54 C2,6.16,2.73,4.14,4.23,2.56l0.15-0.15C5.96,0.84,8.05,0.03,10.14,0c2.1-0.03,4.22,0.73,5.85,2.28l45.24,47.18L106.91,2.26 L106.91,2.26z"/></g>
              </svg>
            </div>
          <!-- CTA End -->
        </div>
        <!-- second panel: showing content of the DB -->
        <div class="sticky top-0 h-screen flex flex-col items-center justify-center bg-green-200">
            <h2 class="text-2xl">Statistics</h2>
            <!-- all statistics cards: -->
            <h3 class="text-xl pt-2 mt-2 pb-1 mb-1">Nodes:</h3>
            <div class="grid grid-cols-4 gap-4 w-4/5 justify-center" id='nodesCounter'>
            </div>
            <script>
              $(document).ready(function(){
                showcounters('nodes');
              });
            </script>
            <h3 class="text-xl pt-2 mt-2 pb-1 mb-1">Edges:</h3>
            <div class="grid grid-cols-4 gap-4 w-4/5" id='edgesCounter'>
            </div>
            <script>
              $(document).ready(function(){
                showcounters('edges');
              });
            </script>
            </div>
        </div>
        <div class="sticky top-0 h-screen flex flex-col items-center justify-center bg-sky-200">
            <h2 class="text-2xl">About</h2>
            <p>Scroll Down</p>
        </div>
        <div class="sticky top-0 h-screen flex flex-col items-center justify-center bg-neutral-800 text-white">
            <h2 class="text-2xl">Start</h2>
            <div class="flex flex-col">
              <div class="">
                <ul>
                  <li><a href="users/login.php">Log in</a></li>
                  <li><a href="search.php">search for a node</a></li>
                </ul>
              </div>
              <div class="">
              </div>

            </div>
        </div>
    </div>
  </body>
</html>
