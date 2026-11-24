<?php
class CsrfTokenManager {
    private $token;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function generateToken($name = 'csrf_token') {
        $this->token = bin2hex(random_bytes(32));
        $_SESSION[$name] = $this->token;
        return $this->token;
    }

    public function getToken($name = 'csrf_token') {
        $existing = $this->getTokenFromSession($name);
        if ($existing !== null) {
            return $existing;
        }
        return $this->generateToken($name);
    }

    public function checkToken($submittedToken, $name = 'csrf_token') {
        $sessionToken = $this->getTokenFromSession($name);
        if (is_null($sessionToken) || !$submittedToken) {
            return false;
        }
        $submittedToken = trim((string)$submittedToken, '"');
        return hash_equals($sessionToken, $submittedToken);
    }

    public function outputToken($name = 'csrf_token') {
        $val = htmlspecialchars($this->getToken($name), ENT_QUOTES, 'UTF-8');
        echo '<input type="hidden" name="csrf_token" value="' . $val . '">';
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