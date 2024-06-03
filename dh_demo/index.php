<?php
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/nodes_extend_cud.inc.php');
$node = new CUDNode($client);
$node->startTransaction();


//for demo purposes only
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Connect to SQLite database
$pdo = new PDO('sqlite:sampledatabase.db');

// Set error mode to exceptions
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Fetch all records from the table
$stmt = $pdo->query('SELECT * FROM exampledata'); 

// Display records in an HTML table


$i=0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $article = $row['article'];
    $title = $row['title'];
    $newspaper = $row['newspaper'];
    $date = $row['date'];
    $language = $row['language'];
    $url = $row['digitized url']; 
    $data = array(
        'text'=>$article, 
        'title'=>$title,
        'publication'=>$newspaper, 
        'date'=>$date,
        'language'=>$language, 
        'url'=>$url, 
        'texid'=>$i++
    ); 
    
  
    $node->createNewNode("Text", $data, true);
}
try {

    $node->commitTransaction();

    
} catch (PDOException $e) {
    $node->rollbackTransaction();
    echo "Error: " . $e->getMessage();
}

?>