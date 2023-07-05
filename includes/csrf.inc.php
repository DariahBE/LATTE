<?php
class CsrfTokenManager {
    private $token;

    public function generateToken() {
        $this->token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $this->token;
        return $this->token;
    }

    public function checkToken($submittedToken) {
        return hash_equals($this->getTokenFromSession(), $submittedToken);
    }

    public function outputToken() {
        echo '<input type="hidden" name="csrf_token" value="' . $this->getTokenFromSession() . '">';
    }

    public function renewToken() {
        $this->token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $this->token;
        return $this->token;
    }

    public function revokeToken() {
        unset($_SESSION['csrf_token']);
        $this->token = null;
    }

    private function getTokenFromSession() {
        if (isset($_SESSION['csrf_token'])) {
            return $_SESSION['csrf_token'];
        } else {
            return null;
        }
    }
}

?>