<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';


class Mail{
    protected $server;
    protected $user;
    protected $password;
    protected $mailOrigin;
    protected $smtp;
    public $contactAdress;
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
        }
    }

    function setMessageContent($message, $isHtml=false){
        $this->messageIsHtml = $isHtml;
        $this->message = $message;
        if($isHtml){

        }
        return true;
    }

    function setSubjectOfMail($subject){
        $this->subject = $subject;
        return true; 
    }

    function setRecipient($recipient){
        if(filter_var($recipient, FILTER_VALIDATE_EMAIL)){
            $this->contactAdress = $recipient;
            return true;
        }else{
            throw new Exception('Invalid e-mail provided.');
        }
    }

    /*
    function generateSalt($length=64){
        //TODO: required?
        $hash = ''; 
        $codes = 'abcdefghijklmnopqrstuvwxyz0123456789';
    }*/

    function send(){
        //include("class.smtp.php"); // optional, gets called from within class.phpmailer.php if not already loaded
        $mail             = new PHPMailer();
        if($this->smtp === 'SMTP'){
            $mail->IsSMTP(); // telling the class to use SMTP
        }
        $mail->SMTPDebug  = 1;                     // enables SMTP debug information (for testing)
                                                // 1 = errors and messages
                                                // 2 = messages only
        //$mail->SMTPAuth   = SMTPREQUIRESAUTH;                  // enable SMTP authentication
        $mail->SMTPSecure = "ssl";                      // sets the prefix to the server
        $mail->Host       = SMTPSERVERADR;              // sets SMTPSERVERADR as the SMTP server
        $mail->Port       = SMTPPORT;                   // set the SMTP port for the SMTPSERVERADR server
        if(SMTPUSER && SMTPPASSWORD){
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

        $mail->AddAddress($this->contactAdress);

        if(!$mail->Send()) {
        echo "Mailer Error: " . $mail->ErrorInfo;
        } else {
        echo "Message sent!";
        }

    }
}

?>