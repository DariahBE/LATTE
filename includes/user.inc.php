<?php

/**
 *
 */
class User{
  private $client;
  function __construct($client)  {
    $this->client = $client;
  }


  public function login($email, $password){
    //perform a cypher statement: user data is stored in the same database as the researchdata.
    //protected userdata is prepended with priv_
    $query = 'MATCH (u:priv_user {mail:$email}) return u.password as pw, u.logon_attempts as att, id(u) as nodeid, u.userid as uid limit 1';
    $result = $this->client->run($query, array('email'=>$email));
    if(count($result) === 0){
      // if 0 records returned = NO user with this email:
    }else{
      $nodeId = $result[0]['nodeid'];
      $hash = $result[0]['pw'];
      $attempts = $result[0]['att'];
      $userid = $result[0]['userid'];
      //if 1 record returned: User exists;
      //check max login attempts:
      if($attempts <= 5){
        //Check hash to pass:
        $match = password_verify($password, $hash);
        if($match){
          //matching hash == reset max login to 0 & set session
          $this->client->run("MATCH (u:priv_user) WHERE id(u)= $nodeId SET u.logon_attempts = 0; ");
          return array(1, $userid);
        }else{
          //NO matching hash: increment max_login
          $this->client->run("MATCH (u:priv_user) WHERE id(u)= $nodeId SET u.logon_attempts = u.logon_attempts+1; ");
          return array(2, false);
        }
      }else{
        //account suspended
        return array(3, false);
      }

    }
  }


  public function createUser($mail, $name, $role, $password){
    //check if user with mail already exists:
    $checkQuery = 'MATCH (n:priv_user) WHERE n.mail = $email RETURN count(n) as count';
    $exists = $this->client->run($checkQuery, ['email'=>$mail]);
    if ($exists[0]['count'] > 0){
      return array('error', 'user already exists.');
    }else{
      $query = 'CREATE (n:priv_user {userid: apoc.create.uuid(), mail: $email, name: $username, role: $role, logon_attempts: 0, password: $password})';
      $this->client->run($query, ['email'=>$mail, 'username'=>$name, 'role'=>$role, 'password'=>password_hash($password, PASSWORD_DEFAULT)]);
      return array('ok', 'user created');
    }
  }


  public function checkSession(){
    if(isset($_SESSION['user_uuid'])){
      return $_SESSION['user_uuid'];
    }else{
      return false;
    }
  }
  
}



?>
