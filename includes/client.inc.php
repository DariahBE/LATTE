<?php
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
require ROOT_DIR.'/vendor/autoload.php';
use Laudis\Neo4j\Authentication\Authenticate;
use Laudis\Neo4j\ClientBuilder;
use Laudis\Neo4j\Contracts\TransactionInterface;

$client = ClientBuilder::create()
    ->withDriver('bolt', 'bolt+s://user:password@localhost') // creates a bolt driver
    ->withDriver('neo4j', 'neo4j://localhost:7687?database=people.db', Authenticate::kerberos('token')) // creates an auto routed driver
    ->withDefaultDriver('neo4j')
    ->build();

?>
