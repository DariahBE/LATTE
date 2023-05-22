<?php
//everywhere the user class is needed; will require sessionscope: so load it
session_start();
/**
 *
 */
class User{
  protected $client;
  public $neoId;
  function __construct($client)  {
    $this->client = $client;
    $this->myRole = isset($_SESSION['userrole']) ? $_SESSION['userrole'] : False;
    $this->myName = isset($_SESSION['username']) ? $_SESSION['username'] : False;
    $this->myId = isset($_SESSION['userid']) ? $_SESSION['userid'] : False;
    $this->neoId = isset($_SESSION['neoid']) ? $_SESSION['neoid'] : False;
  }

public function checkForSession($redir="/user/mypage.php"){
  if($this->myName){
    //var_dump($redir); 
    header("Location: $redir");
    die(); 
  }
}

  public function logout(){
    session_destroy();
    if (filter_var(WEBURL, FILTER_VALIDATE_URL) !== FALSE) {
      header('Location: '.WEBURL);
      die(); 
    }
    //die("Log out completed.");
  }


  public function login($email, $password){
    //perform a cypher statement: user data is stored in the same database as the researchdata.
    //protected userdata is prepended with priv_
    $query = 'MATCH (u:priv_user {mail:$email}) return u.password as pw, u.logon_attempts as att, id(u) as nodeid, u.userid as uid, u.role as role, u.name as name limit 1';
    $result = $this->client->run($query, array('email'=>$email));
    if(count($result) === 0){
      // if 0 records returned = NO user with this email:
        return array(0, false);
    }else{
      $nodeId = $result[0]['nodeid'];
      $hash = $result[0]['pw'];
      $attempts = $result[0]['att'];
      $userid = $result[0]['uid'];
      $name = $result[0]['name'];
      $role = $result[0]['role'];
      //if 1 record returned: User exists;
      //check max login attempts. Only try to vallidate the login if the limit has not been exceeded:
      if($attempts <= 5){
        //Check hash to pass:
        $match = password_verify($password, $hash);
        if($match){
          //matching hash == reset max login to 0 & set session
          $this->client->run("MATCH (u:priv_user) WHERE id(u)= $nodeId SET u.logon_attempts = 0; ");
          $this->myName = $name;
          $this->myRole = $role;
          $_SESSION['username'] = $name;
          $_SESSION['userrole'] = $role;
          $_SESSION['userid'] = $userid;
          $_SESSION['neoid'] = $nodeId;
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

  public function hasEditRights($role, $isOwner){
    /*
      0 = Deny all
      1 = Create New
      2 = Create and update
      3 = Create, update and delete
      4 = SuperUser: allow all.
    */

    if($role === 'admin'){
      //if you're admin, you can edit it.
      return 4;
    }
    if($role === 'projectlead'){
      return 3;
    }
    if($role === 'researcher'){
      // you can edit nodes and edges.
      return 2;
    }
    if($isOwner){
      //if you own the record, you can edit and update. - even when restricted to the contributor role.
      return 2;
    }
    if($role === 'contributor'){
      //user can add, but can not edit
      return 1;
    }
    else{
      return 0;
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
    return $this->myId; /*
    if(isset($_SESSION['user_uuid'])){
      return $_SESSION['user_uuid'];
    }else{
      return false;
    }*/
  }

  public function checkUniqueness($mail, $username){
    if($mail){
      $result = $this->client->run('MATCH (n:priv_user) WHERE n.mail= $mail',['mail'=>$mail]);
    }
    if($username){
      $result = $this->client->run('MATCH (n:priv_user) WHERE n.username= $username', ['username'=>$username]);
    }
    if(boolval(count($result))){
      //already exists 
      return false; 
    }else{
      //not taken yet!
      return true;
    }
  }

}



?>
