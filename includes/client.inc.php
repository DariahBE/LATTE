<?php
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
require ROOT_DIR.'/vendor/autoload.php';


use Laudis\Neo4j\ClientBuilder;
use Laudis\Neo4j\Authentication\Authenticate;
use Laudis\Neo4j\Contracts\TransactionInterface;

$user = 'neo4j';
$pw = 'password';

/**
 *      BUG
 * something going on with the authentication token. 
 * problem can be worked around, by disabling authentication, but that drops all security measures!! so nono
 * It's something about a scheme cannot be none, but there's no docs about what the scheme should be!
 * 
 */
//->withDriver('bolt', 'bolt+s://'.$user.':'.$pw.'@localhost', null) // creates a bolt driver

$auth = Authenticate::basic($user, $pw);
$client = ClientBuilder::create()
    ->withDriver('bolt', 'bolt://'.$user.':'.$pw.'@localhost') // creates a bolt driver
    ->withDriver('neo4j', 'neo4j://localhost:7687?database='.DBNAME, $auth) // creates an auto routed driver
    ->withDefaultDriver('neo4j')
    ->build();

