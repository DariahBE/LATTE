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
    $query = 'MATCH (u:priv_user {mail:$email}) return u.password, u.logon_attempts limit 1';
    $result = $this->client->run($query, array('email', $email));
    // if 0 records returned = NO user with this email:

    //if 1 record returned: User exists;
    //check max login attempts:

    //Check hash to pass:
    $match = password_verify($password, );

    //matching hash == reset max login to 0 & set session

    //NO matching hash: increment max_login
    var_dump($result);


  }







}



?>
