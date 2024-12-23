<?php
//everywhere the user class is needed; will require sessionscope: so load it from here. 
session_start();
/**
 *  USER DATA IS SPLIT INTO TWO DATABASES:
 *    sensitive information is kept in the sqlite database and should never be distributed
 *    Non-sensitive information is kept in the NEO4J database. No passwords/mails are stored here. 
 * 
 * The SQLITE database is the canonical database and contains:
 *           descr      type      column sql        property neo4J
 *    - a Primary Key   integer   (SQL: id      =   NEO4J: user_sqlid )
 *    - a UUID          STR       (SQL: uuid    =   NEO4J: user_uuid )
 *    - username        STR       (SQL: username=   NEO4J: username)
 * 
 * //// USER ROLES: 
 *    admin     project_lead    researcher    contributor
 */
class User{
  protected $sqlite; 
  protected $client;
  protected $path_to_sqlite; 
  public $neoId;
  public $myRole;
  public $myName;
  public $myId;
  public $setToken; 
  protected $application_roles; 
  function __construct($client)  {
    $this->path_to_sqlite = ROOT_DIR."/user/protected/users.sqlite";
    $this->sqlite = new PDO('sqlite:' . $this->path_to_sqlite);
    $this->sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $this->client = $client;    //delete (keep until SQLITE migration is completed.)
    $this->myRole = isset($_SESSION['userrole']) ? $_SESSION['userrole'] : False;
    $this->myName = isset($_SESSION['username']) ? $_SESSION['username'] : False;
    $this->myId = isset($_SESSION['userid']) ? $_SESSION['userid'] : False;
    $this->neoId = isset($_SESSION['neoid']) ? $_SESSION['neoid'] : False;
    $this->application_roles = array('contributor', 'researcher', 'projectlead', 'admin'); 
    $this->setToken = False;
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
    header("Location: $redir");
    die(); 
  }
}

public function getMailFromUUID($uuid){
  $stmt = $this->sqlite->prepare("SELECT mail FROM userdata WHERE uuid = :uuid LIMIT 1");
  $stmt->bindParam(':uuid', $uuid);
  $stmt->execute(); 
  $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
  if(boolval($result)){
    return $result[0]['mail'];
  }else{
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
    $query = "SELECT * FROM userdata WHERE userdata.mail = ? AND userdata.logon_attempts <= 5 AND userdata.token IS NULL AND userdata.completed = 1 AND userdata.blocked = 0";
    $stmt = $this->sqlite->prepare($query);
    $stmt->execute(array($email));
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if(count($result) === 0){
      // if 0 records returned = NO user with this email:
        return array(0, false);
    }else{
      $user_nodeId = $result[0]['id'];              //PK used in SQLITE
      $hash = $result[0]['password'];
      $attempts = $result[0]['logon_attempts'];
      $user_uuid = $result[0]['uuid'];                 //UUIDV4 generated for SQLITE
      $name = $result[0]['username'];
      $role = $result[0]['role'];
      //if 1 record returned: User exists;
      //check max login attempts. Only try to vallidate the login if the >limit has not been exceeded:
      if($attempts <= 5){
        //Check hash to pass:
        $match = password_verify($password, $hash);
        if($match){
          //matching hash == reset max login to 0 & set session
          $result_from_neo = $this->client->run('MATCH (u:priv_user) WHERE u.user_sqlid = $sqlreference return id(u) as user_neo_id; ', ['sqlreference'=> $user_nodeId]);
          if($result_from_neo->count() === 0){
            //user exists in SQLITE, not in NEO4J!
            //SO align the user to the NEO4J database if needed: 
            $this->fixAlignment($result);
            //requery only if you did an update using fixAlignment to get the updated result: 
            $result_from_neo = $this->client->run('MATCH (u:priv_user) WHERE u.user_sqlid = $sqlreference return id(u) as user_neo_id; ', ['sqlreference'=> $user_nodeId]);
          }
          $user_neo_id = $result_from_neo[0]['user_neo_id']; 
          $update_query = "UPDATE userdata SET logon_attempts = 0 WHERE id = ? ";
          $update_data = array($user_nodeId); 
          $stmt = $this->sqlite->prepare($update_query); 
          $stmt->execute($update_data); 
          $this->myName = $name;
          $this->myRole = $role;
          $_SESSION['username'] = $name;
          $_SESSION['userrole'] = $role;
          $_SESSION['userid'] = $user_nodeId;   //priv_user. ==> SQL ID
          $_SESSION['user_uuid'] = $user_uuid;  //V4 uuuid
          $_SESSION['neoid'] = $user_neo_id;    //NEO ID of the user node!
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

  public function hasEditRights($role){
    /*
      0 = Deny all
      1 = Create New
      2 = Create and update
      3 = Create, update and delete
      4 = SuperUser: allow all.
    */
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
      //  ==> nodes that are created by this user can be edited by extra
      // check done in determinerightset method. 
      return 2;
    }
    // ownership check is moved elsewhere
    // obsolete code. 
    // if($isOwner){
    //   //if you own the record, you can edit and update. - even when restricted to the contributor role.
    //   return 2;
    // }
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

  public function createUserInNeo($uuid, $name, $sql_id){
    /*
    *    -  (SQL: id      =   NEO4J: user_sqlid )
    *    -  (SQL: uuid    =   NEO4J: user_uuid )
   */

    $query = 'CREATE (n:priv_user {user_uuid: $uuid, name: $username, user_sqlid: $sqlite_id})';
    $this->client->run($query, ['uuid'=>$uuid, 'username'=>$name, 'sqlite_id'=>$sql_id]);
    return 1; 
  }



  public function createUser($mail, $name, $role, $pw=NULL, $make_token=False, $completed = 0, $confirmation_phase = False){
    //is $mail a valid adress: backend validation. 
    if ((!filter_var($mail, FILTER_VALIDATE_EMAIL))) {
      return (array('error', 'Invalid mail')); 
    }
    $token = NULL; 
    if($make_token){
      $token = $this->getHash(64);
      $this->setToken = $token; 
    }
    
    //check if user with mail already exists:
    if (!($confirmation_phase)){
      //SKIP check if POLICY 1 applies and the user is completing their invite. 
      $checkQuery = 'SELECT COUNT(mail) AS count FROM userdata WHERE userdata.mail = ?';
      $stmt = $this->sqlite->prepare($checkQuery);
      $stmt->execute(array($mail));
      $exists = $stmt->fetchAll(PDO::FETCH_ASSOC);
      if ($exists[0]['count'] > 0){
        return array('error', 'user already exists.');
      }
      //INSERT QUERY FOR NON CONFIRMATION PHASE
      $uuid = $this->guidv4();
      $query = "INSERT INTO userdata (uuid, logon_attempts, mail, username, password, role, token, completed) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?); ";
      $query_data = array($uuid, 0, $mail, $name, $pw, $role, $token, $completed);
      $sql_id = 0; 
    }else{
      $query = "UPDATE userdata set password = ?, token = NULL, completed = 1, blocked = 0 WHERE userdata.mail = ? ";
      $query_data = array($pw, $mail);
    }

      try {
        $stmt = $this->sqlite->prepare($query);
        $stmt->execute($query_data);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if(!($confirmation_phase)){
          //OK: lastInsertId is an alies for the row where a primary key is used. 
          $sql_id = (int)$this->sqlite->lastInsertId(); 
          $this->createUserInNeo($uuid, $name ,$sql_id); 
        }
        //duplicate node into NEO4J-database. Only store the essential data in there!!
        //$query = 'CREATE (n:priv_user {userid: $uuid, name: $username, sqlite_id: $sqlite_id})';
        //$this->client->run($query, ['uuid'=>$uuid, 'username'=>$name, 'sqlite_id'=>$sql_id]);
        return array('ok', 'user created');
      } catch (\Throwable $th) {
        return (array('error', 'User could not be added.'));
      }
    
  }


  //Converted To SQLITE == TRUE
  public function requestPasswordReset($mail){
    $hash = $this->getHash(32); 
    $this->setToken = $hash; 
    //$checkQuery = 'MATCH (n:priv_user) WHERE n.mail = $email SET n.resethash = $resetcode';
    $checkQuery = "UPDATE userdata SET token = ? WHERE userdata.mail = ? AND userdata.blocked = 0"; 
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

  public function checkTokenRequest($mail, $token){
    $query = "SELECT * FROM userdata WHERE mail = ? AND  token = ? AND userdata.token IS NOT NULL AND userdata.blocked = 0 AND userdata.completed = 0";  
    $parameters = array($mail, $token);
    $stmt = $this->sqlite->prepare($query);
    $stmt->execute($parameters);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $result; 
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
    //READ only (okay)
    /**
     * Prevents re-use of emailadress in the sqlite database. 
     */
    if($mail){
      $checkQuery = "SELECT mail FROM userdata WHERE userdata.mail = ? "; 
      $checkData = array($mail); 
      $stmt = $this->sqlite->prepare($checkQuery);
      $stmt->execute($checkData);
      $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    if(boolval(count($result))){
      //already exists 
      return false; 
    }else{
      //not taken yet!
      return true;
    }
  }


  // //Converted To SQLITE == FALSE
  // public function autoIncrementControllableUserId(){
  //   // not required for SQLITE (PK == AI anyway!)
  //   /**                     OK
  //    *  looks for all priv_user nodes that already have an exisint
  //    *  userid property, it finds the highest and returns that +1
  //    *  if no users in the database exist: the query return NULL.
  //    *  In this case the method will return 1 as next to be used 
  //    *  userid
  //    */
  //   $query = "MATCH (n:priv_user)
  //         WHERE exists(n.sqlid) 
  //         WITH n ORDER BY n.sqlid DESC LIMIT 1
  //         RETURN n.sqlid AS user_sqlid";
  //   $result = $this->client->run($query);

  //   if (boolval(count($result))){
  //     $highestExistingId = $result[0]['user_sqlid']; 
  //   }else{
  //     $highestExistingId = 0; 
  //   }  
  //   return $highestExistingId +=1; 
  // }

  public function checkAlignment(){
    /*  Checks if the SQLITE database required for the user management is in sync
    * with the nodes present in NEO4J. If there are users in the SQLITE system
    * which are missing in the NEO4J database, the method will flag it as a 
    * misaligned user (error). 
    */

        // Query SQL database to select all users
        $sqlQuery = "SELECT * FROM userdata";
        $stmt = $this->sqlite->prepare($sqlQuery);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        // Initialize array to store users with matching 'priv_user' in Neo4j
        $usersWithoutNeo4j = [];
    
        // Iterate through each user
        foreach ($users as $user) {
            $userId = (int)$user['id'];
    
            // Check if there is a 'priv_user' node with the same 'user_id' in Neo4j
            $neo4jQuery = "MATCH (n:priv_user {user_sqlid: $userId}) RETURN n";
            $neo4jResult = $this->client->run($neo4jQuery, ['userId' => $userId]);
    
            // If there is a matching node, add the user to the result array
            if (!($neo4jResult->count())) {
                $usersWithoutNeo4j[] = $user;
            }
        }
        return $usersWithoutNeo4j;
  }

  public function fixAlignment($problemUsers = False){
    /**
     * When called will create user nodes in the NEO4J database to match the SQLITE database.
     * This is an extra feature in case the standard creating procedure throws an error and the user
     * is not created automatically.  
     * 
     * $problemUsers can be passed down as an argument coming from the checklogin() method
     * for users that passed the login stage in the SQLITE database but do not exist yet in the
     * NEO4J database. (backup in case the admin does not align users when prompted to do so!)
     */
    if(!($problemUsers)){
      $problemUsers = $this->checkAlignment(); 
    }
      foreach($problemUsers as $problem){
        $uuid = $problem['uuid']; 
        $name = $problem['username']; 
        $sql_id = (int)$problem['id']; 
        $this->createUserInNeo($uuid, $name, $sql_id);
      }
    }

  public function listAllUsers(){
    $sql_query = 'SELECT id, uuid, mail, username, role, completed, blocked FROM userdata ORDER BY id ASC'; 
    $stmt = $this->sqlite->prepare($sql_query);
    //$stmt->execute($checkData); ??
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $result;
  }

  public function promoteUser($user_uuid, $newRole){
    if(in_array(strtolower($newRole), $this->application_roles)){
      $sql_query = 'UPDATE userdata SET `role` = ? WHERE userdata.uuid = ?; '; 
      $stmt = $this->sqlite->prepare($sql_query);
      $stmt->execute(array($newRole, $user_uuid));
      $result = $stmt->rowCount();
      return $result;
    }else{
      return array('msg'=> 'request rejected.'); 
    }
  }


  
  public function setBlockTo($user, $toval){
    $sql_query = "UPDATE userdata SET `blocked` = ? WHERE userdata.uuid = ?; ";
    $data = array((int)$toval, $user);
    $stmt = $this->sqlite->prepare($sql_query);
    $stmt->execute($data);
    $result = $stmt->rowCount();
    return $result;
  }


}



?>
