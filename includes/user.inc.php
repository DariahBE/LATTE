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
  /**
   *  creates a UUIDV4 id. Either by a built in function or
   * openssl fallback if needed.  
   */
  if (function_exists('com_create_guid') === true){
      return trim(com_create_guid(), '{}');
  }
  $data = openssl_random_pseudo_bytes(16);
  $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
  $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
  return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

private function getHash($l){
  /**
   *  Generates a random hash a-z + 0-9
   *  of $l (int) length.
   */
  $hashSymbols = 'abcdefghijklmnopqrstuvwxyz0123456789'; 
  $hash = '';
  for ($i = 0; $i < $l; $i++){
    $randomIndex = random_int(0, strlen($hashSymbols) - 1);
    $hash .= $hashSymbols[$randomIndex];
  }
  return $hash; 
}

public function checkForSession($redir="/user/mypage.php"){
  /**
   * CHecks if a request is part of a SESSION object, 
   * if not redirect the user to $redir (str). If $redir
   * is not supplied, the user will be redirected to 
   * a loginpage. 
   */
  if(boolval($this->myName)){
    header("Location: $redir");
    die(); 
  }
}

public function getMailFromUUID($uuid){
  /**
   * Takes a users $UUID and returns the mail adress of the user. 
   * If no matching UUID is found, a die() statement is triggered. 
   * 
   * ==> Required in the admin portal when updates to userdata are performed. 
   */
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
    /**
     * Destroys a session: logs a user out. 
     */
    session_destroy();
    if (filter_var(WEBURL, FILTER_VALIDATE_URL) !== FALSE) {
      header('Location: '.WEBURL);
      die(); 
    }
  } 

  public function checkAccess($ispublic){
    /*
      Checks if a request grants access to a specific type of data by checking the 
      global settings and the existence of a user session. 

      When data is set to 'not-public viewable', any logged in user can still access 
      the data. They need to be logged in. 

    */
    if($ispublic){return $ispublic;}
    if(boolval($this->checkSession())){
      return True;
    }else{
      header("Location: /user/mypage.php");
      die(); 
    }
  }


  public function login($email, $password){
    /**
     * Performs a login of a user by checking $email and $password hash;
     * If the user exists and the password is correct, a session is created.-
     * 
     * A user can only log in IF:
     *  1) a maximum of 5 wrong attempts were made
     *  2) A usr is not blocked
     *  3) A user has completed the registration procedure.
     * 
     * Every wrong logon attempt increments the counter by 1. When the user logs in 
     * with the correct password, the counter is reset. 
     * When a login is successful, reset a pending token to NULL - 
     */
    $query = "SELECT * FROM userdata WHERE userdata.mail = ? AND userdata.logon_attempts <= 5 AND userdata.completed = 1 AND userdata.blocked = 0";
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
          //Good login: set attempt counter back to 0 and revoke any token that was still active (a user might remember their password)
          $update_query = "UPDATE userdata SET token = NULL, logon_attempts = 0 WHERE id = ? ";
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
    * create a priv_user in neo4J for a given user that's in SQLITE.
   */

    $query = 'CREATE (n:priv_user {user_uuid: $uuid, name: $username, user_sqlid: $sqlite_id})';
    $this->client->run($query, ['uuid'=>$uuid, 'username'=>$name, 'sqlite_id'=>$sql_id]);
    return 1; 
  }



  public function createUser($mail, $name, $role, $pw=NULL, $make_token=False, $completed = 0, $confirmation_phase = False){
    //is $mail a valid adress: backend validation.
    /**
     * Creates a user in SQLITE if it passes all checks. 
     *  */ 
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
    } try {
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
  public function requestPasswordReset($mail, $by_admin = False){
    /**
     * Takes $mail (mailadress of account to reset)
     * Bool (flag that says the action was triggered by an Admin user)
     * 
     * Checks if the user exists, generates a hash, resets the accestoken to the hash
     * and contacts the end user 
     */
    $hash = $this->getHash(32); 
    $this->setToken = $hash; 
    //$checkQuery = 'MATCH (n:priv_user) WHERE n.mail = $email SET n.resethash = $resetcode';
    $checkQuery = "UPDATE userdata SET token = ? WHERE userdata.mail = ? AND userdata.blocked = 0 AND userdata.completed = 1"; 
    $checkData = array($hash, $mail); 
    //$usr = $this->client->run($checkQuery, ['email'=>$mail, 'resetcode'=>$hash]);
    $stmt = $this->sqlite->prepare($checkQuery);
    $stmt->execute($checkData);
    $result = $stmt->rowCount();    
    if(boolval($result)){
      //get username from the updated row: 
      $userQuery = "SELECT username, uuid, mail, token FROM userdata WHERE mail = ? AND blocked = 0 AND completed = 1 LIMIT 1";
      $userStmt = $this->sqlite->prepare($userQuery);
      $userStmt->execute(array($mail));
      $row = $userStmt->fetchAll(PDO::FETCH_ASSOC)[0];
      // Fetch the row fields. 
      $username = $row['username'];
      $uuid = $row['uuid'];
      $mail = $row['mail'];
      $token = $row['token'];
      //TODO pending tests
      var_dump($username, $uuid, $mail, $token); 
      $reset_link = WEBURL."/user/pwresetform.php?uid=$uuid&token=$token&mail=$mail";
      var_dump($reset_link);
      die(); //TODO: remove
      $msg = "Hello $username.<br> A password reset for your account on ".PROJECTNAME." was asked. Click the link below to set a new password for you account. If you did not ask for this, you can ignore this mail and keep logging in with your current password."; 
      $msg .= "<br><br><a href= '".$reset_link."'>Reset</a>"; 
      $msg .= "<br>If the above link does not work; copy-paste the following: <br> $reset_link"; 
      $mail_interface = new Mail(); 
      $mail_interface->setSubjectOfMail('Your '.PROJECTNAME.' password resetcode.'); 
      $mail_interface->setRecipient($mail);
      $mail_interface->setMessageContent($msg, True); 
      var_dump($mail_interface);     //BUG: mail_interface can't ve reached. 
      $mail_interface->send(); 
      //if there is one user affected by the query: you need to initiate the mail option!
      return array(1, 'A reset token has been created for the associated mailaccount.'); 
    }
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
    //destroy the session if the user has been blocked. 
    if(isset($_SESSION['user_uuid'])){
      $checkQuery = "SELECT blocked FROM userdata WHERE userdata.uuid = ? "; 
      $checkData = array($_SESSION['user_uuid']); 
      $stmt = $this->sqlite->prepare($checkQuery);
      $stmt->execute($checkData);
      $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
      if($result[0]['blocked'] === 1){
        $this->logout(); 
      }
    }
    return $this->myId; /*
    if(isset($_SESSION['user_uuid'])){
      return $_SESSION['user_uuid'];
    }else{
      return false;
    }*/
  }


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

  public function prereset_checks($mail, $uuid, $token){
    $sql_query = "SELECT * FROM userdata WHERE blocked = 0 AND token = ? AND uuid = ? AND mail = ?; ";
    $sql_data = array($token, $uuid, $mail); 
    $stmt = $this->sqlite->prepare($sql_query);
    $stmt->execute($sql_data);
    $result = $stmt->fetchAll();
    return count($result);
  }

  
  public function setBlockTo($user, $toval){
    $sql_query = "UPDATE userdata SET `blocked` = ? WHERE userdata.uuid = ?; ";
    $data = array((int)$toval, $user);
    $stmt = $this->sqlite->prepare($sql_query);
    $stmt->execute($data);
    $result = $stmt->rowCount();
    return $result;
  }


  public function resetPassword($uuid, $password) {
    $hashedPassword = password_hash($password, PASSWORD_DEFUALT);
    
    $stmt = $this->db->prepare("UPDATE userdata SET password = ?, token = '' WHERE uuid = ?");
    $stmt->bind_param("ss", $hashedPassword, $uuid);
    
    if ($stmt->execute()) {
        return true;
    } else {
        return "Error: " . $stmt->error;
    }
  }

  public function passwordPolicyCheck($password) {
    // Checks the password and see if it follows the password policy. 
    //TODO implement everywhere the POST is read. 
    $minlength = 8;
    $minCriteria = 2;
    // Check password length
    if (strlen($password) < $minlength) {
      //8 is a hard requirement.
        return false;
    }
    // Define criteria
    $hasLowercase = preg_match('/[a-z]/', $password);
    $hasUppercase = preg_match('/[A-Z]/', $password);
    $hasNumber = preg_match('/[0-9]/', $password);
    $hasSpecial = preg_match('/[^a-zA-Z0-9]/', $password);
    // Count how many criteria are met
    $criteriaMet = $hasLowercase + $hasUppercase + $hasNumber + $hasSpecial;
    return $criteriaMet >= $minCriteria;
  }



}



?>
