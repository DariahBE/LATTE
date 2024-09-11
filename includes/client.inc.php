<?php
error_reporting(E_ALL ^ (E_NOTICE | E_WARNING | E_DEPRECATED));
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
require ROOT_DIR.'/vendor/autoload.php';


use Laudis\Neo4j\ClientBuilder;
use Laudis\Neo4j\Authentication\Authenticate;
use Laudis\Neo4j\Contracts\TransactionInterface;


$user = USERNAME;
$pw = PASSWORD;

$auth = Authenticate::basic($user, $pw);
$client = ClientBuilder::create()
    ->withDriver('bolt', 'bolt://'.HOSTNAME.':'.HOSTPORT.'?database='.DBNAME, $auth) // creates a bolt driver
    ->withDriver('neo4j', 'neo4j://'.HOSTNAME.':'.HOSTPORT.'?database='.DBNAME, $auth) // creates an auto routed driver
    ->withDefaultDriver('bolt')
    ->build();
