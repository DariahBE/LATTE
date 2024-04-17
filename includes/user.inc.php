<?php
//everywhere the user class is needed; will require sessionscope: so load it from here. 
session_start();
/**
 *
 */
class User{
  protected $sqlite; 
  protected $client;
  protected $path_to_sqlite; 
  public $neoId;
  function __construct($client)  {
    $this->path_to_sqlite = ROOT_DIR."/user/protected/users.sqlite";
    $this->sqlite = new PDO('sqlite:' . $this->path_to_sqlite);
    $this->sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $this->client = $client;    //delete (keep until SQLITE migration is completed.)
    $this->myRole = isset($_SESSION['userrole']) ? $_SESSION['userrole'] : False;
    $this->myName = isset($_SESSION['username']) ? $_SESSION['username'] : False;
    $this->myId = isset($_SESSION['userid']) ? $_SESSION['userid'] : False;
    $this->neoId = isset($_SESSION['neoid']) ? $_SESSION['neoid'] : False;

  }

private function guidv4(){
  if (function_exists('com_create_guid') === true){
      return trim(com_create_guid(), '{}');
  }
  $data = openssl_random_pseudo_bytes(16);
  $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
  $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
  return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

private function getHash($l){
  $hashSymbols = 'abcdefghijklmnopqrstuvwxyz0123456789'; 
  $hash = '';
  for ($i = 0; $i < $l; $i++){
    $randomIndex = random_int(0, strlen($hashSymbols) - 1);
    $hash .= $hashSymbols[$randomIndex];
  }
  return $hash; 
}

public function checkForSession($redir="/user/mypage.php"){
  if(boolval($this->myName)){
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

  public function checkAccess($ispublic){
    if($ispublic){return $ispublic;}
    if(boolval($this->checkSession())){
      return True;
    }else{
      header("Location: /user/mypage.php");
      die(); 
    }
  }

  //Converted To SQLITE == OK
  //tested == OK
  public function login($email, $password){
    //perform a cypher statement: user data is stored in the same database as the researchdata.
    //protected userdata is prepended with priv_
    //$query = 'MATCH (u:priv_user {mail:$email}) return u.password as pw, u.logon_attempts as att, id(u) as nodeid, u.userid as uid, u.role as role, u.name as name limit 1';
    //$result = $this->client->run($query, array('email'=>$email));
    //var_dump($this->sqlite);
    $query = "SELECT * FROM userdata WHERE userdata.mail = ? AND userdata.logon_attempts <= 5 AND userdata.token IS NULL AND userdata.completed = 1";
    $stmt = $this->sqlite->prepare($query);
    $stmt->execute(array($email));
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if(count($result) === 0){
      // if 0 records returned = NO user with this email:
        return array(0, false);
    }else{
      $user_nodeId = $result[0]['id'];
      $hash = $result[0]['password'];
      $attempts = $result[0]['logon_attempts'];
      $userid = $result[0]['uuid'];
      $name = $result[0]['username'];
      $role = $result[0]['role'];
      //if 1 record returned: User exists;
      //check max login attempts. Only try to vallidate the login if the limit has not been exceeded:
      if($attempts <= 5){
        //Check hash to pass:
        $match = password_verify($password, $hash);
        if($match){
          //matching hash == reset max login to 0 & set session
          $result_from_neo = $this->client->run('MATCH (u:priv_user) WHERE u.userid = $userid return id(u) as user_neo_id; ', ['userid'=> $user_nodeId]);
          $user_neo_id = $result_from_neo[0]['user_neo_id']; 
          $update_query = "UPDATE userdata SET logon_attempts = 0 WHERE id = ? ";
          $update_data = array($user_nodeId); 
          $stmt = $this->sqlite->prepare($update_query); 
          $stmt->execute($update_data); 
          $this->myName = $name;
          $this->myRole = $role;
          $_SESSION['username'] = $name;
          $_SESSION['userrole'] = $role;
          $_SESSION['userid'] = $user_nodeId;
          $_SESSION['user_uuid'] = $userid;
          $_SESSION['neoid'] = $user_neo_id;
          return array(1, $user_nodeId);
        }else{
          //NO matching hash: increment max_login
          $update_query = "UPDATE userdata SET logon_attempts = ? where id = ? ";
          $update_data = array($attempts+=1,$user_nodeId); 
          $stmt = $this->sqlite->prepare($update_query); 
          $stmt->execute($update_data); 
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
    //TODO ownership check!!!!
    $role = strtolower($role); 
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
      //allowed to read content, not create content!
      return 0;
    }
  }

  //Converted To SQLITE == TRUE
  // tested = OK
  public function createUser($mail, $name, $role){
    //check if user with mail already exists:
    //$checkQuery = 'MATCH (n:priv_user) WHERE n.mail = $email RETURN count(n) as count';
    //$exists = $this->client->run($checkQuery, ['email'=>$mail]);
    $checkQuery = 'SELECT COUNT(mail) AS count FROM userdata WHERE userdata.mail = ?';
    $stmt = $this->sqlite->prepare($checkQuery);
    $stmt->execute(array($mail));
    $exists = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($exists[0]['count'] > 0){
      return array('error', 'user already exists.');
    }else{
      $uuid = $this->guidv4(); 
      $token = $this->getHash(64);
      $query = "INSERT INTO userdata (uuid, logon_attempts, mail, username, password, role, token, completed) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?); ";
      $insert_query_data = array($uuid, 0, $mail, $name, NULL, $role, $token, 0);

      $sql_id = 0; 
      try {
        $stmt = $this->sqlite->prepare($query);
        $stmt->execute($insert_query_data);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        //var_dump($result); 
        $sql_id = (int)$this->sqlite->lastInsertId(); 
        //duplicate node into NEO4J-database. Only store the essential data in there!!
        $query = 'CREATE (n:priv_user {userid: $uuid, name: $username, sqlite_id: $sqlite_id})';
        $this->client->run($query, ['uuid'=>$uuid, 'username'=>$name, 'sqlite_id'=>$sql_id]);
        return array('ok', 'user created');
      } catch (\Throwable $th) {
        return (array('error', 'User could not be added.')); 
      }
    }
  }


  //Converted To SQLITE == TRUE
  public function requestPasswordReset($mail){
    $hash = $this->getHash(32); 
    //$checkQuery = 'MATCH (n:priv_user) WHERE n.mail = $email SET n.resethash = $resetcode';
    $checkQuery = "UPDATE userdata SET token = ? WHERE userdata.mail = ? "; 
    $checkData = array($hash, $mail); 
    //$usr = $this->client->run($checkQuery, ['email'=>$mail, 'resetcode'=>$hash]);
    $stmt = $this->sqlite->prepare($checkQuery);
    $stmt->execute($checkData);
    $result = $stmt->rowCount();

    if(boolval($result)){
      //if there is one user affected by the query: you need to initiate the mail option!
      //TODO > make a mailer class
      //TODO > connect userclass to mailhash-method
      //TODO > hash is single-use only: 
      //          needs to be deleted upon every login
      //          AND 
      //          Upon actual reset of a new password!
    }
    return array(1, 'A reset token has been created for the associated mailaccount.'); 
  }



  public function checkSession(){
    return $this->myId; /*
    if(isset($_SESSION['user_uuid'])){
      return $_SESSION['user_uuid'];
    }else{
      return false;
    }*/
  }


  //Converted To SQLITE == FALSE
  public function checkUniqueness($mail){
    if($mail){
      $checkQuery = "SELECT count() AS count FROM userdata WHERE userdata.mail = ? "; 
      $checkData = array($mail); 
      $stmt = $this->sqlite->prepare($checkQuery);
      $stmt->execute($checkData);
      $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
      var_dump($result); 
      //$result = $this->client->run('MATCH (n:priv_user) WHERE n.mail= $mail RETURN n',['mail'=>$mail]);
    }
    if(boolval(count($result))){
      //already exists 
      return false; 
    }else{
      //not taken yet!
      return true;
    }
  }


  //Converted To SQLITE == FALSE
  public function autoIncrementControllableUserId(){
    // not required for SQLITE (PK == AI anyway!)
    /**                     OK
     *  looks for all priv_user nodes that already have an exisint
     *  userid property, it finds the highest and returns that +1
     *  if no users in the database exist: the query return NULL.
     *  In this case the method will return 1 as next to be used 
     *  userid
     */
    $query = "MATCH (n:priv_user)
          WHERE exists(n.userid) 
          WITH n ORDER BY n.userid DESC LIMIT 1
          RETURN n.userid AS userid";
    $result = $this->client->run($query);

    if (boolval(count($result))){
      $highestExistingId = $result[0]['userid']; 
    }else{
      $highestExistingId = 0; 
    }  
    return $highestExistingId +=1; 
  }

}



?>
