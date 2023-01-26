<?php
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');



?>


<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <script
        src="https://code.jquery.com/jquery-3.6.0.min.js"
        integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4="
        crossorigin="anonymous">
    </script>
        <link rel="stylesheet" href="/CSS/stylePublic.css">

    <title>Become a member</title>
  </head>
  <body>
    <div class="main flex flex-row">
      <div class="pamphlet w-1/2">
        <h2>Why become a member?</h2>
        <p></p>

      </div>
      <div class="formhost w-1/2">
        <div class="bg-grey-lighter min-h-screen flex flex-col">
            <div class="container max-w-sm mx-auto flex-1 flex flex-col items-center justify-center px-2">
                <div class="bg-white px-6 py-8 rounded shadow-md text-black w-full">
                    <h1 class="mb-8 text-3xl text-center">Sign up</h1>
                    <div id="report"></div>
                    <input
                        id='field_1'
                        type="text"
                        class="block border border-grey-light w-full p-3 rounded mb-4"
                        name="first name"
                        placeholder="First Name" />
                    <input
                        id='field_2'
                        type="text"
                        class="block border border-grey-light w-full p-3 rounded mb-4"
                        name="surname"
                        placeholder="Surname" />
                    <input
                        id='field_3'
                        type="text"
                        class="block border border-grey-light w-full p-3 rounded mb-4"
                        name="displayName"
                        placeholder="Username" />
                    <input
                        id='field_4'
                        type="text"
                        class="block border border-grey-light w-full p-3 rounded mb-4"
                        name="email"
                        placeholder="Email" />

                    <input
                        id='field_5A'
                        type="password"
                        class="block border border-grey-light w-full p-3 rounded mb-4"
                        name="password"
                        placeholder="Password" />
                    <input
                        id='field_5B'
                        type="password"
                        class="block border border-grey-light w-full p-3 rounded mb-4"
                        name="confirm_password"
                        placeholder="Confirm Password" />

                    <div>
                      <div class="col mb-3">
                        <div class="flex flex-row">
                          <img src="../captcha/image.php?12325" alt="CAPTCHA" id="image-captcha" class="w-1/2">
                          <a href="#" id="refresh-captcha" class="" title="refresh"><i class="w-1/2 right">refresh</i></a>
                        </div>
                          <input
                            id='field_6'
                            type="text"
                            class="block border border-grey-light w-full p-3 rounded mb-4"
                            name="solvedCaptcha"
                            placeholder="Please prove you're human."
                          >
                          </input>
                          <input
                            id='field_7'
                            type="text"
                            class="hidden"
                            name="vintage"
                          ></input>
                      </div>
                    </div>
                    <button
                        id="submitMe"
                        type="submit"
                        class="w-full text-center py-3 rounded bg-green-400 text-white hover:bg-green-600 focus:outline-none my-1"
                    > Create Account</button>

                    <div class="text-center text-sm text-grey-dark mt-4">
                        By signing up, you agree to the
                        <a class="no-underline border-b border-grey-dark text-grey-dark" href="#">
                            Terms of Service
                        </a> and
                        <a class="no-underline border-b border-grey-dark text-grey-dark" href="#">
                            Privacy Policy
                        </a>
                    </div>
                </div>

                <div class="text-grey-dark mt-6">
                    Already have an account?
                    <a class="no-underline border-b border-blue text-blue" href="../login/">
                        Log in
                    </a>.
                </div>
                <script>
                    var refreshButton = document.getElementById("refresh-captcha");
                    var captchaImage = document.getElementById("image-captcha");
                    refreshButton.onclick = function(event) {
                        event.preventDefault();
                        captchaImage.src = '../captcha/image.php?' + Date.now();
                    };

                    var submitButton = document.getElementById("submitMe");
                    submitButton.onclick = function(event){
                      var vintage = document.getElementById('field_7').value;
                      var captchaProvided = document.getElementById('field_6').value;
                      var pw1 = document.getElementById('field_5A').value;
                      var pw2 = document.getElementById('field_5B').value;
                      var email = document.getElementById('field_4').value;
                      var displayname = document.getElementById('field_3').value;
                      var surname = document.getElementById('field_2').value;
                      var firstname = document.getElementById('field_1').value;
                      var ok = true;
                      //check that password is at least 6 characters long:
                      if(pw1.length < 6){
                        document.getElementById('field_5A').style.color = 'red';
                        document.getElementById('field_5B').style.color = 'red';
                        document.getElementById('report').innerHTML = "<p>Your password is a bit short, make it a tad longer.</p>";
                        ok = false;
                      }
                      //check for password correctness.
                      if(pw1 != pw2){
                        document.getElementById('field_5A').style.color = 'red';
                        document.getElementById('field_5B').style.color = 'red';
                        document.getElementById('report').innerHTML = "<p>Your passwords do not match.</p>";
                        ok = false;
                      }else{
                        document.getElementById('field_5A').style.color = '';
                        document.getElementById('field_5B').style.color = '';
                      }
                      //check for email uniqueness;
                      fetch('AJAX/is_unique.php?mail='+email)
                      .then(response => response.json())
                      .then(data => {
                        if(data['doesExist'] == 1){
                          document.getElementById('field_4').style.color = 'red';
                          document.getElementById('report').innerHTML = "<p>This emailaddress is already used to register an account with. Try to log in, or reset your password.</p>"
                          ok = false;
                        }
                      });
                      //check for username uniqueness;
                      fetch('AJAX/is_unique.php?username='+displayname)
                      .then(response => response.json())
                      .then(data => {
                        if(data['doesExist'] == 1){
                          document.getElementById('field_3').style.color = 'red';
                          document.getElementById('report').innerHTML = "<p>That username sure looks awesome, unfortunately it is already in use.</p>"
                          ok = false;
                        }
                      });

                      if(ok){
                        var formdata={
                          'username': displayname,
                          'firstname': firstname,
                          'lastname': surname,
                          'pw1':pw1,
                          'pw2': pw2,
                          'email': email,
                          'captcha': captchaProvided,
                          'vintage': vintage
                        }
                        $.ajax({
                          type: 'POST',
                          url: 'runregistration.php',
                          data: formdata,
                          success: function(data, status, xhttp){
                            if ( data ){
                              $("#report").text(data['msg']);
                            }
                            else{ // if false, show some sort of message with errors
                                alert("OH NO!");
                            }
                        }
                        });
                      }

                    }
                </script>
            </div>
        </div>
      </div>
    </div>
  </body>
</html>
