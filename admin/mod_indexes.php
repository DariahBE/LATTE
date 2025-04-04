<?php
 //only allow admins here: 


  //base imports
  include_once($_SERVER["DOCUMENT_ROOT"]."/config/config.inc.php");
  include_once(ROOT_DIR."/includes/client.inc.php");
  include_once(ROOT_DIR."/includes/user.inc.php");
  include_once(ROOT_DIR."/includes/navbar.inc.php");
  include_once(ROOT_DIR."/includes/integrity.inc.php");
  include_once(ROOT_DIR."/includes/csrf.inc.php");
  include_once(ROOT_DIR."/includes/getnode.inc.php");
  //there must be a logged in  user; if no session is active, make them log in and redirect back here. 
  if(isset($_SESSION["userid"])){
    $user = new User($client);
  }else{
    header("Location: /user/login.php?redir=/admin/index.php");
    die("redir required"); 
  }
  //only allow admins here; 
  if($user->myRole !== "Admin"){
    header("HTTP/1.0 403 Forbidden");
    die("Insufficient rights, forbidden access");
  }

    //only allow admins here; 
    $adminMode = False;
    if($user->myRole !== "Admin"){
      header("HTTP/1.0 403 Forbidden");
      die("Insufficient rights, forbidden access");
    }else{
      $adminMode = True;
    }

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title><?php echo PROJECTNAME ?></title>
    <script src="/JS/jquery-3.6.0.min.js"></script>
    <script src="/admin/JS/validation.js"></script>
    <script src="/admin/JS/idx_handles.js"></script>
    <link rel="stylesheet" href="/CSS/stylePublic.css">
    <link rel="stylesheet" href="/CSS/overlaystyling.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  </head>
  <body class="bg-neutral-200 w-full">
  <?php
    $navbar = new Navbar($adminMode); 
    $node = new Node($client); 
    echo $navbar->getNav();
    $integrity = new Integrity($client);
  ?>


  
  <?php
    //$navbar = new Navbar($adminMode); 
    //echo $navbar->getNav();
    include_once('admin_tasks.php');
  ?>

    <p class="container w-1/2 itemx-center m-auto">Attributes can be indexed to speed up often-recurring search operations. A green icon indicates the attribute has been indexed, a red icon implies there's no index on the given attribute. Clicking the icon acts as a toggle to add or remove database indexes. </p>

    <div class= '2xl:w-1/2 xl:w-2/3 items-center m-auto grid grid-cols-1 md:grid-cols-2 gap-4'>
  <?php
    $indexed_columns = $node->getIndexes(); 
    foreach(NODEMODEL as $label => $properties){
      echo '<div class="align-top">
          <h4 class= "font-bold size-lg">'.$label.'</h4>'; 
          echo '<table>';
          echo '<tr>';
            echo '<th>Property</th>';
            echo '<th>Index</th>';
          echo '</tr>';
          foreach ($properties as $propName => $propparameters){
            echo '<tr>';
            if( (is_array($indexed_columns[$label])) && (array_key_exists($propName, $indexed_columns[$label]))){
              echo '<td>'.$propName.'</td>';
              echo '<td>'.'<span data_nodeLabel="'.$label.'" data_nodeProp="'.$propName.'" data_idxname = "'.$indexed_columns[$label][$propName].'" class="idxBolt hasIndex m-1 p-1 bg-green-200 cursor-pointer"> &#9889;</span>'.'</td>'; 
            }else{
              echo '<td>'.$propName.'</td>';
              echo '<td>'.'<span data_nodeLabel="'.$label.'" data_nodeProp="'.$propName.'" class="idxBolt noIndex m-1 p-1 bg-red-200 cursor-pointer"> &#9889;</span>'.'</td>'; 
            }
            echo '</tr>';
          }
          echo '</table>';
      echo '</div>'; 
    }
    ?>
    </div>

<script>
  attach_dom_idx_triggers(); 
</script>>

</body>
