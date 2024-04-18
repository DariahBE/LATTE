<?php
//TODO check conflicting implementation in AJAX/Crud/delete.php

include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'\includes\getnode.inc.php');
include_once(ROOT_DIR.'\includes\user.inc.php');
include_once(ROOT_DIR.'\includes\csrf.inc.php');
include_once(ROOT_DIR.'\includes\nodes_extend_cud.inc.php');
include_once(ROOT_DIR.'/includes/navbar.inc.php');

if(!isset($_GET['id'])){
    die();
}else{
    $id = (int)$_GET['id']; 
}

$user = new User($client);
$user_uuid = $user->checkSession();
if($user_uuid === false){
    die('login required');
}



$graph = new CUDNode($client); 
$graph->deleteText($id); 

$tokenManager = new CsrfTokenManager();
$token = $tokenManager->generateToken(); 


/*
$tokenManager = new CsrfTokenManager(); 
$validToken = $tokenManager->checkToken($token); 

*/
/*
$crudNode = new CUDNode($client); 


$crudNode->startTransaction();
try{
    $data = $crudNode->delete((int)$_GET['id'], true);
} catch(\Throwable $th) {
    $crudNode->rollbackTransaction();
    echo json_encode(array('msg'=>'Node could not be deleted'));   
    die(); 
}
$crudNode->commitTransaction();
echo json_encode($data);
*/

//get all data of the node: 
//  EGO info: 
$egoLabel = $graph->fetchLabelById($id); 
//  data: 
$egoData = $graph->fetchEtById($id);
//  model: 
$egoModel = $graph->fetchModelByLabel($egoLabel); 
//

//delete endpoint can delete the following nodetypes: 
//  TEXNODE
//  ENTITYNODE
//  ANNOTATIONNODE

//if egolabel is an entitynode! ==> Look for connected annotations
if($egoLabel == TEXNODE ){
    $nodetype_string = 'a Text node'; 
    $ntype = 'text'; 
    //deleting a text:
}elseif (($egoLabel == ANNONODE ) || ($egoLabel == 'Annotation_auto') ) {
    $nodetype_string = 'an Annotation node'; 
    $ntype = 'anno'; 

    //deleting an annotation
}elseif(array_key_exists($egoLabel, CORENODES)){
    $nodetype_string = 'an Entity node'; 
    $ntype = 'entity'; 
    //corenodes includes text and annonodes, but these cases are captured already

    //deleting an entity
}else{
    //not allowed 
    die();
}


//  connection info: 

function generateFirstBox(){
    global $ntype;
    global $graph; 
    global $id;
    $repl = ''; 
    if($ntype == 'text'){
        $repl .= '<h4 class="w-full text-lg font-bold text-center m-1 p-1 mt-2 pt-2">Annotations & Entities.</h4>'; 
        $connectedAnnotations = $graph->distinctAnnotationsInText($id);
        $connectedEntities = $graph->distinctEntitiesInText($id); 
        $annos = count($connectedAnnotations); 
        $ets = count($connectedEntities); 
        $repl .= "<p>This text holds {$annos} annotation". ($annos != 1 ? "s" : "")." and {$ets} distinct ". ($ets != 1 ? "entities" : "entity")."</p>";
        $repl .= "<p>Deleting this text will delete: </p>" ;
        $repl .= "<ul><li>All connected annotations</li><li>All related <b>links to entities</b></li><li>All <b>uniquely connected entities</b> to this text </li><li>All <b>Knowledgebase and Variant connections</b> to those connected entities.</li></ul>"; 
    

    }elseif($ntype == 'anno'){

    }elseif($ntype == 'entity'){

    }
    return $repl; 
}

function generateSecondBox(){
    global $ntype;
    global $graph; 
    global $id;
    $repl = ''; 
    if($ntype == 'text'){
        $repl .= '<h4 class="w-full text-lg font-bold text-center m-1 p-1 mt-2 pt-2">Knowledgebase connections.</h4>'; 
        $siloConnections = $graph->distinctSilosForText($id);
        //var_dump(count($siloConnections));
        $silos = count($siloConnections);
        $repl .= "<p>This text holds {$silos} connection".($silos != 1 ? "s" : "")." to external resources. 
        Deleting this text will delete all links and will remove all of the nodes which are uniquely connected to this text.</p>";

    }elseif($ntype == 'anno'){

    }elseif($ntype == 'entity'){

    }
    return $repl; 
}

function generateThirdBox(){
    global $ntype;
    $repl = ''; 
    if($ntype == 'text'){

    }elseif($ntype == 'anno'){

    }elseif($ntype == 'entity'){

    }
    return $repl; 
}


?>


<html>
    <head>
    <title><?php echo PROJECTNAME ?></title>
    <script src="/JS/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="/CSS/stylePublic.css">

    </head>
    <body class="bg-neutral-200 w-full">
        <?php
            $navbar = new Navbar(); 
            echo $navbar->getNav();
        ?>
        <div >
            <div class="top">
                <h2 class="text-xl">Delete node</h2>
                <p>You're about to delete <?php echo $nodetype_string;  ?> from your knowledgebase. This action cannot be undone; review the implications below and press confirm to continue deleting the node.</p>
            </div>
            <hr>
            <div class='flex flex-row m-1 p-1'>
                <div class="md:w-1/3">
                    <?php
                       echo generateFirstBox(); 
                    ?>

                </div>
                <div class="md:w-1/3 m-1 p-1">
                    <?php
                       echo generateSecondBox(); 
                    ?>
                </div>
                <div class="md:w-1/3 m-1 p-1">
                    <?php
                       echo generateThirdBox(); 
                    ?>
                </div>

                    
            </div>

        </div>
        <div class="2xl:w-1/2 xl:w-2/3 items-center m-auto">
        <form action="delete_action.php" method="post">
            <input type="hidden" name="csrf" value=<?php {$token;} ?>>
            <input type="hidden" name="ID" value=<?php {$id;} ?>>
            <button type="submit" name="deleteButton">Delete</button>
        </form>

        </div>
    </body>
</html>