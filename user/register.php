<?php
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/csrf.inc.php');

if (REGISTRATIONPOLICY === 0){
  header('Location: /index.html');
  die('Policy violation: no registrations allowed');
}
//initiatie the Node class when registrationpolicy <> 0; 
$graph = new Node($client);
$user = new User($client); 
//make a new CSRF token manager: 
$tokenManager = new CsrfTokenManager();
$csrf_name = 'registrationtoken';
$sessiontoken = $tokenManager->generateToken($csrf_name);

//KILL if regpolicy === 1 and mail/invitetoken are missing: 
if(!(isset($_GET['invitetoken']) && isset($_GET['mail'])) && REGISTRATIONPOLICY === 1){
  header('Location: /');          
  die('Policy violation: invalid token');
}


if(REGISTRATIONPOLICY === 1 || (REGISTRATIONPOLICY === 2 && (isset($_GET['invitetoken']) && isset($_GET['mail']))) ){
  $usermail = $_GET['mail'];
  if(isset($_GET['invitetoken']) && isset($_GET['mail'])){
    //$query = 'MATCH (n:priv_user) WHERE n.invitationcode = $token AND n.mail = $mail RETURN n'; 
    // $query = "SELECT * FROM userdata WHERE token = ? AND mail = ? ";  
    // $parameters = array($_GET['invitetoken'], $_GET['mail']);
    $matchingTokens = $user->checkTokenRequest($_GET['mail'], $_GET['invitetoken']);
    // $data = $graph->executionOfParameterizedQuery($query, $parameters); 
    if (count($matchingTokens) === 1){
      //valid user found with correct token and mail combo!
      $username = $matchingTokens[0]['username'];
      foreach($data as $row){
        $node = $row->get('n');
        $usermail = $node->getProperty('mail');
      }

    }else{
      //if one of the two parameters is missing: enforce redirect to the homeage!
      header('Location: /');          
      die('Policy violation: invalid token');
    }
  }else{ 
    //if one of the two parameters is missing: enforce redirect to the homeage!
    header('Location: /');          
    die('Policy violation: invalid token');
  }

}

if(REGISTRATIONPOLICY === 2 && !(isset($_GET['invitetoken']) && isset($_GET['mail']))){
  $usermail = NULL;
}


?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Sign Up</title>
    <script src="/JS/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="/CSS/style_entities.css">
    <link rel="stylesheet" href="/CSS/stylePublic.css">
    <link rel="stylesheet" href="/CSS/overlaystyling.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  </head>
  <body>
    <div class="">
  <div class="">
    <section class="h-full gradient-form bg-gray-200 md:h-screen">
      <div class="container py-12 px-6 h-full">
        <div class="flex justify-center items-center flex-wrap h-full g-6 text-gray-800">
          <div class="xl:w-10/12">
            <div class="block bg-white shadow-lg rounded-lg">
              <div class="lg:flex lg:flex-wrap g-0">
                <div class="lg:w-6/12 px-4 md:px-0">
                  <div id='signupsquare' class="md:p-12 md:mx-6">
                    <div class="" id='status'>

                    </div>
                    <div class="text-center">
                      <h4 class="text-xl font-semibold mt-1 mb-12 pb-1">Sign Up for <?php echo PROJECTNAME; ?></h4>
                    </div>
                    <form>
                      <p class="mb-4">Please fill out the registration form</p>
                      <div class="mb-4">

                        <?php 
                          //MAIL (conditionally editable according to policy. )
                          if(REGISTRATIONPOLICY === 2 && !(isset($_GET['invitetoken']) && isset($_GET['mail']))){
                            echo '
                            <input
                              type="text"
                              class="form-control block w-full px-3 py-1.5 text-base font-normal text-gray-700 bg-white bg-clip-padding border border-solid border-gray-300 rounded transition ease-in-out m-0 focus:text-gray-700 focus:bg-white focus:border-blue-600 focus:outline-none"
                              placeholder="Email"
                              name="email"
                              id="emailfield"
                            />';
                          }else{
                            echo '
                            <input
                              disabled
                              type="text"
                              value="'.$usermail.'"
                              class="form-control block w-full px-3 py-1.5 text-base font-normal text-gray-400 bg-white bg-clip-padding rounded transition ease-in-out m-0 focus:text-gray-700 focus:bg-white focus:border-blue-600 focus:outline-none"
                              name="email"
                              id="emailfield"
                            />
                            ';
                          }
                        ?>

                      </div>
                          <?php
                          //invitetoken is safe cause it passed a check against the database. 
                          //just to be sure ==> escape it. 
                            if(REGISTRATIONPOLICY === 1){
                              echo '
                              <input
                                disabled
                                type="hidden"
                                value="'.htmlspecialchars($_GET['invitetoken'], ENT_QUOTES, 'UTF-8').'"
                                class="form-control block w-full px-3 py-1.5 text-base font-normal text-gray-400 bg-white bg-clip-padding rounded transition ease-in-out m-0 focus:text-gray-700 focus:bg-white focus:border-blue-600 focus:outline-none"
                                name="invite"
                                id="invitecode"
                              />
                              ';
                            }
                          ?>

                      <div class="mb-4">
                        <?php 
                        //NAME should be conditionally editable. only open on policy == 2; non-editable on policy == 1
                        if(REGISTRATIONPOLICY === 2 && !(isset($_GET['invitetoken']) && isset($_GET['mail']))){
                          echo '
                          <input
                          type="text"
                          class="form-control block w-full px-3 py-1.5 text-base font-normal text-gray-700 bg-white bg-clip-padding border border-solid border-gray-300 rounded transition ease-in-out m-0 focus:text-gray-700 focus:bg-white focus:border-blue-600 focus:outline-none"
                          placeholder="Full Name"
                          name="fullname"
                          id="fullnamefield"
                          />';
                        } else {
                          echo '
                           <input
                           disabled
                          type="text"
                          class="form-control block w-full px-3 py-1.5 text-base font-normal text-gray-400 bg-white bg-clip-padding rounded transition ease-in-out m-0 focus:text-gray-700 focus:bg-white focus:border-blue-600 focus:outline-none"
                          value= "'.$username.'"
                          name="fullname"
                          id="fullnamefield"
                          />
                          ';
                        }
                        ?>
                      </div>

                      <div class="mb-4">
                        <input
                          type="password"
                          class="form-control block w-full px-3 py-1.5 text-base font-normal text-gray-700 bg-white bg-clip-padding border border-solid border-gray-300 rounded transition ease-in-out m-0 focus:text-gray-700 focus:bg-white focus:border-blue-600 focus:outline-none"
                          placeholder="Password"
                          name="password"
                          id="passwordfield"
                        />
                      </div>
                      <div class="mb-4">
                        <input
                          type="password"
                          class="form-control block w-full px-3 py-1.5 text-base font-normal text-gray-700 bg-white bg-clip-padding border border-solid border-gray-300 rounded transition ease-in-out m-0 focus:text-gray-700 focus:bg-white focus:border-blue-600 focus:outline-none"
                          placeholder="Confirm Password"
                          name="password_confirmation"
                          id="confirmPasswordfield"
                        />
                      </div>
                      <!-- captcha image -->
                      <img src='/captcha/image.php'>


                      <!-- captcha solution --> 
                      <div class="mb-4">
                        <input
                          type="input"
                          class="form-control block w-full px-3 py-1.5 text-base font-normal text-gray-700 bg-white bg-clip-padding border border-solid border-gray-300 rounded transition ease-in-out m-0 focus:text-gray-700 focus:bg-white focus:border-blue-600 focus:outline-none"
                          placeholder="Type captcha solution of six characters"
                          name="captchaSolution"
                          id="captchaSolution"
                        />
                      </div>

                      <input
                        type="hidden"
                        disabled
                        value="<?php echo $sessiontoken;  ?>"
                        name="token"
                        id="csrfToken"
                      />
                      <div class="text-center pt-1 mb-12 pb-1">
                        <button
                          class="inline-block px-6 py-2.5 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-blue-700 hover:shadow-lg focus:shadow-lg focus:outline-none focus:ring-0 active:shadow-lg transition duration-150 ease-in-out w-full mb-3"
                          type="button"
                          data-mdb-ripple="true"
                          data-mdb-ripple-color="light"
                          id='signupbutton'
                          style="
                            background: linear-gradient(
                              to right,
                              #ee7724,
                              #d8363a,
                              #dd3675,
                              #b44593
                            );
                          "
                        >
                          Sign Up
                        </button>
                        <p class="text-gray-500">Already have an account? <a href='login.php'>Log in here</a></p>
                      </div>
                      <?php if (REGISTRATIONPOLICY === 1) {?>
                        <script>
                          let invitecode = document.getElementById('invitecode').value;
                        </script>
                      <?php } ?>
                      <?php if (REGISTRATIONPOLICY === 2) {?>
                        <script>
                          let invitecode = null;
                        </script>
                      <?php } ?>
                      <?php if (REGISTRATIONPOLICY !== 0) {?>
                      <script>
                        var elem = document.getElementById('signupbutton');
                        elem.addEventListener('click', function(){
                          var signupData = {
                            fullname: document.getElementById('fullnamefield').value,
                            email: document.getElementById('emailfield').value,
                            password: document.getElementById('passwordfield').value,
                            password_confirmation: document.getElementById('confirmPasswordfield').value, 
                            token: document.getElementById('csrfToken').value,
                            captcha: document.getElementById('captchaSolution').value,
                            invitetoken: invitecode
                          };
                          $.ajax({
                            type: 'POST',
                            url: 'runsignup.php',
                            data: signupData,
                            success: function(data, status, xhttp){
                              if ( data ){
                                $("#status").text(data['msg']);
                                if(data['status'] == 1){
                                  $("#signupsquare").fadeOut();
                                  $("#signupsquare").promise().done(function(){
                                    //forces reload of login page or follow the redir parameter if provided
                                    var url=window.location.href;   
                                    window.location.href=url;
                                  });
                                }
                              } else { // if false, show some sort of message with errors
                                $("#status").text('Something went wrong.');
                              }
                            }
                          });
                        });
                      </script>
                      <?php }?>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</div>
  </body>
</html>
