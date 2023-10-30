<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="/JS/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="/CSS/style_entities.css">
    <link rel="stylesheet" href="/CSS/stylePublic.css">
    <link rel="stylesheet" href="/CSS/overlaystyling.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <title>Onboarding - Register admin user. </title>
</head>
    <body class="container mx-auto">
    
<?php
    //var_dump($_POST);
        if( isset($_POST['username'])
            && isset($_POST['email'])
            && isset($_POST['password'])
            && isset($_POST['password_repeat'])
            && isset($_POST['token'])
        ){
            function guidv4(){
                if (function_exists('com_create_guid') === true){
                    return trim(com_create_guid(), '{}');
                }
                $data = openssl_random_pseudo_bytes(16);
                $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
                $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
                return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
            }

            $admin_name = $_POST['username']; 
            $admin_mail = $_POST['email'];
            $admin_pw = $_POST['password'];
            $admin_pw_repeat = $_POST['password_repeat'];
            $token = $_POST['token']; 

            //validate request: inspect the token. 
            



            //if all the input is valid => create a .sqlite file in /user/protected/
            $path_to_sqlite="../user/protected/users.sqlite";
            try{
                $db = new SQLite3($path_to_sqlite);
            }catch(Exception $e){
                die('The onboarding process could not be completed. Either you are not allowed to create an sqlite folder, or executables are missing.');
            }
            try{
                //connect to sqlite using PDO: 
                $pdo = new PDO('sqlite:' . $path_to_sqlite);
                // Enable exceptions for error handling
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // SQL query to create a table
                $sql = "CREATE TABLE IF NOT EXISTS userdata (
                        id INTEGER PRIMARY KEY,
                        uuid TEXT,
                        logon_attempts INTEGER default 0,
                        mail TEXT,
                        username TEXT,
                        password TEXT,
                        role TEXT,
                        wd_property_preferences	TEXT, 
                        token TEXT default Null
                    )";

                // Execute the query
                $pdo->exec($sql);
            }catch(Exception $e){
                die("Table couldn't be completed, onboarding process failed."); 
            }
            //save the new user in the database. 
            // ONLY IF THERE IS NO EXISTING ADMIN USER!!!
            $preflight_check_query = "SELECT count(*) AS count FROM userdata WHERE userdata.role='aa'"; 
            $result = $pdo->query($preflight_check_query)->fetch(PDO::FETCH_ASSOC);
            $count = $result['count'];
            if(!(boolval($count))){
                try{
                    $query_load_admin = "INSERT INTO 
                        userdata (mail, username, password, role, wd_property_preferences, uuid)
                        VALUES 
                        (:mail, :username, :password, 'Admin', '', :uuid) "; 
                    $data_admin = array(
                        'mail' => $_POST['email'], 
                        'username' => $_POST['username'], 
                        'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                        'uuid' => guidv4()
                    );
                    //echo "performing insert!"; 
                    $insert_admin = $pdo->prepare($query_load_admin);
                    $insert_admin->execute($data_admin); 
                    if($insert_admin->rowCount()===1){
                        echo "<div><p>The administrative account was added succesfully.</p></div>"; 
                        echo "<div><a href='completed_onboarding.php'><button class='bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded'> Final steps </button></a></div>";
                    }else{
                        echo "<div><p>ERROR: The administrative account could not be added. The Onboarding phase did not complete.</p></div>";
                        echo "<div><a href='set_admin.php'><button class='bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded'> Return </button></a></div>";
                    }
                }catch(Exception $e){
                    die("Admin user could not be registered, onboarding process failed."); 
                }
            }else{
                throw new Exception("One or more Admin users are already in the database; the onboarding process does not allow to create more than one! Log in using the existing Admin-account and add an Admin-user manually.");                
            }
            
            die(); 
        }else{
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $token = '';
            for ($i = 0; $i < 64; $i++) {
                $token .= $characters[rand(0, strlen($characters) - 1)];
            }
            $_SESSION['onboardingtoken'] = $token;

        }

    ?>

    
    <div class="">
        <div class="p-2 text-2xl">User registration.</div>
        <div class="p-2">Creates one admin-user for the given installation.</div>
    </div>



    <div class="bg-gray-200 p-4 rounded-lg">
        <form action="set_admin.php" method="post" id="create_admin_form">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="mail">
                    E-Mail: 
                </label>
                <input class="ipgroup shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" name="email" id="mail" type="text" placeholder="someone@example.com">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="username">
                    Username: 
                </label>
                <input class="ipgroup shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" name="username" id="username" type="text" placeholder="Username (Three or more symbols)">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="pw_1">
                    Password
                </label>
                <input class="ipgroup shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" name="password" id="pw_1" type="password" placeholder="********** (Minimum of six characters)">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="pw_2">
                    Password (repeat)
                </label>
                <input class="ipgroup shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" name="password_repeat" id="pw_2" type="password" placeholder="**********">
            </div>
            <div class="mb-4 ">
                <input class="ipgroup shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" name="token" id="token" type="hidden" value="<?php echo $token; ?>" readonly >
            </div>
            <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="button" onclick=formvalidation()>
                Submit
            </button>
        </form>
        <script>
            function warn_input(id){
                let p1 = document.getElementById(id);
                p1.value = '';
                p1.classList.add('bg-red-100', 'border', 'border-red-400', 'text-red-700');
                valid_form = false; 
            }
            let valid_form;
            function formvalidation(){
                valid_form = true; 
                //clear current warning: 
                let alerts = document.getElementsByClassName('ipgroup'); 
                for (let i = 0; i < alerts.length; i++){
                    alerts[i].classList.remove('bg-red-100', 'border', 'border-red-400', 'text-red-700'); 
                }
                //email: 
                let mail = document.getElementById('mail').value.trim();
                //password1 && //password2:
                let pw1 = document.getElementById('pw_1').value.trim();
                let pw2 = document.getElementById('pw_2').value;
                //username:
                let name = document.getElementById('username').value.trim();
                //token 
                let token = document.getElementById('token').value.trim();
                //password1 && //password2 checks: Equality, length. No point in checking complexity (https://xkcd.com/936/)
                if (!(pw1 == pw2)){warn_input('pw_1');warn_input('pw_2');}
                if(pw1.length < 6){warn_input('pw_1');warn_input('pw_2');}
                //check mail: 
                let mailregex = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/;
                if (!(mail.match(mailregex))){warn_input('mail');}
                //check name; should be at least three characters.
                if(name.length < 3){warn_input('username');}
                //with all checks done: submit if there are no errors: 
                if(valid_form){
                    document.getElementById("create_admin_form").submit();
                }
            }

        </script>
    </div>


    
    
</body>
</html>




</body>
</html>
