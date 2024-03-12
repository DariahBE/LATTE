<?php
  include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
  include_once(ROOT_DIR.'/includes/user.inc.php');
  $user = new User(false);
  if(isset($_GET['redir'])){
    $redir = $_GET['redir']; 
    $path = filter_var($redir, FILTER_SANITIZE_URL);
    $user->checkForSession(htmlspecialchars($path)); 
  }else{
    $user->checkForSession(); 
  }
  $user = null;

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Login</title>
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
                <div class="md:w-6/12 px-4 md:px-0 sm:w-full">
                  <div id='loginsquare' class="md:p-12 md:mx-6">
                    <div class="" id='status'>

                    </div>
                    <div class="text-center">
                      <h4 class="text-xl font-semibold mt-1 mb-12 pb-1">Welcome to <?php echo PROJECTNAME; ?></h4>
                    </div>
                    <form>
                      <p class="mb-4">Please login to your account</p>
                      <div class="mb-4">
                        <input
                          type="text"
                          class="form-control block w-full px-3 py-1.5 text-base font-normal text-gray-700 bg-white bg-clip-padding border border-solid border-gray-300 rounded transition ease-in-out m-0 focus:text-gray-700 focus:bg-white focus:border-blue-600 focus:outline-none"
                          placeholder="e-mail"
                          name="mail"
                          id="mailfield"
                        />
                      </div>
                      <div class="mb-4">
                        <input
                          type="password"
                          class="form-control block w-full px-3 py-1.5 text-base font-normal text-gray-700 bg-white bg-clip-padding border border-solid border-gray-300 rounded transition ease-in-out m-0 focus:text-gray-700 focus:bg-white focus:border-blue-600 focus:outline-none"
                          placeholder="Password"
                          name="password"
                          id="pwfield"
                        />
                      </div>
                      <div class="text-center pt-1 mb-12 pb-1">
                        <button
                          class="inline-block px-6 py-2.5 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-blue-700 hover:shadow-lg focus:shadow-lg focus:outline-none focus:ring-0 active:shadow-lg transition duration-150 ease-in-out w-full mb-3"
                          type="button"
                          data-mdb-ripple="true"
                          data-mdb-ripple-color="light"
                          id='loginbutton'
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
                          Log in
                        </button>
                        <a class="text-gray-500" href="#!">Forgot password? <a href="pwreset.php">Perform a reset.</a>
                      </div>
                      <div class="flex items-center justify-between pb-6">
                        <?php if (REGISTRATIONPOLICY === 2) {?>
                          <p class="mb-0 mr-2">Don't have an account? Then <a href='register.php'>register for one</a>.</p>
                        <?php }?>
                        <script>
                          function run_login(){
                            var logindata = {
                              mail: document.getElementById('mailfield').value,
                              password: document.getElementById('pwfield').value
                            };
                            //console.log(logindata)
                            $.ajax({
                              type: 'POST',
                              url: 'runlogin.php',
                              data: logindata,
                              success: function(data, status, xhttp){
                                if ( data ){
                                  $("#status").text(data['msg']);
                                  if(data['status'] == 1){
                                    $("#loginsquare").fadeOut();
                                    $("#loginsquare").promise().done(function(){
                                      //forces reload of logn page ==> will redirect to account page if session is valid or follow the redir parameter if provided!
                                      var url=window.location.href;   
                                      window.location.href=url;
                                    });
                                  }
                                }else{ // if false, show some sort of message with errors
                                  $("#status").text('Something went wrong.');
                                }
                            }
                            });
                          }
                          var elem = document.getElementById('loginbutton');
                          elem.addEventListener('click', function(){run_login();});
                          var elem2 = document.getElementById('pwfield'); 
                          elem2.addEventListener("keypress", function(event){
                            if (event.key === "Enter"){
                              elem.click();
                            }
                          })
                        </script>

                      </div>

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
