<?php
class CsrfTokenManager {
    private $token;

    public function generateToken($name = 'csrf_token') {
        $this->token = bin2hex(random_bytes(32));
        $_SESSION[$name] = $this->token;
        return $this->token;
    }

    public function checkToken($submittedToken) {
        if(is_null($this->getTokenFromSession())){
            return false;
        }
        if ($submittedToken === null){
            return False;
        }
        //var_dump($this->getTokenFromSession()); 
        //var_dump($submittedToken);
        return hash_equals($this->getTokenFromSession(), $submittedToken);
    }

    public function outputToken() {
        echo '<input type="hidden" name="csrf_token" value="' . $this->getTokenFromSession() . '">';
    }

    public function revokeToken($name = 'csrf_token') {
        unset($_SESSION[$name]);
        $this->token = null;
    }

    public function getTokenFromSession($name = 'csrf_token') {
        if (isset($_SESSION[$name])) {
            return $_SESSION[$name];
        } else {
            return null;
        }
    }
}

?>