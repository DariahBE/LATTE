<?php
use PHPMailer\PHPMailer\PHPMailer;
// Load PHPMailer via Composer autoloader or bundled fallback.
if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    require_once __DIR__ . '/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/src/SMTP.php';
}


class Mail{
    protected $server;
    protected $user;
    protected $password;
    protected $mailOrigin;
    protected $smtp;
    public $contactAddress;
    public $message; 
    public $subject; 
    public $messageIsHtml;

    function __construct()  {
    
        $this->server = SMTPSERVERADR;
        $this->port = SMTPPORT;
        $this->user = SMTPUSER; 
        $this->password = SMTPPASSWORD; 
        $this->smtp = PROTOCOL;
        if(filter_var(SERVERORIGMAIL, FILTER_VALIDATE_EMAIL)){
            $this->mailOrigin = SERVERORIGMAIL; 
        }else{
            throw new Exception("Invalid origin email");
            die(); 
        }
    }

    function setMessageContent($message, $isHtml=false){
        $this->messageIsHtml = $isHtml;
        $this->message = $message;
        return true;
    }

    function setSubjectOfMail($subject){
        $this->subject = $subject;
        return true; 
    }

    function setRecipient($recipient){
        if(filter_var($recipient, FILTER_VALIDATE_EMAIL)){
            $this->contactAddress = $recipient;
            return true;
        }else{
            throw new Exception('Invalid e-mail provided.');
        }
    }

    function send(){
        //TODO test on server
        //include("class.smtp.php"); // optional, gets called from within class.phpmailer.php if not already loaded
        $mail = new PHPMailer();
        if($this->smtp === 'SMTP'){
            $mail->IsSMTP(); // telling the class to use SMTP
        }
        $mail->SMTPDebug  = 1;                     // Disables SMTP debug information (for testing)
                                                // 1 = errors and messages
                                                // 2 = messages only
        //$mail->SMTPAuth   = SMTPREQUIRESAUTH;                  // enable SMTP authentication
        //$mail->SMTPSecure = "ssl";                      // sets the prefix to the server
        if(SMTPPATCH){
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
        }
        $mail->Host       = SMTPSERVERADR;              // sets SMTPSERVERADR as the SMTP server
        $mail->Port       = SMTPPORT;                   // set the SMTP port for the SMTPSERVERADR server
        if(boolval(SMTPUSER) && boolval(SMTPPASSWORD)){
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTPUSER;
            $mail->Password   = SMTPPASSWORD;
        }else{
            $mail->SMTPAuth = false;
        }
        $mail->CharSet    = "UTF-8";


        $mail->SetFrom(SERVERORIGMAIL);

        $mail->Subject = strval($this->subject);

        $mail->Body = strval($this->message);

        $mail->AddAddress($this->contactAddress);

        //is html: 
        if(boolval($this->messageIsHtml)){
            $mail->IsHTML(true);
        }else{
            $mail->IsHTML(false);
        }

        if(!$mail->Send()) {
            echo "Mailer Error: " . $mail->ErrorInfo;
        } else {
            echo "Message sent!";
        }

    }
}
?>