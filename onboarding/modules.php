<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
	<script src="/JS/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="/CSS/style_entities.css">
    <link rel="stylesheet" href="/CSS/stylePublic.css">
    <link rel="stylesheet" href="/CSS/overlaystyling.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Onboarding - Module check</title>
</head>
<body>
    <div class="container">
		<div class="p-2 text-2xl">Modules!</div>

		<div class="p-2">This will check if certain Apache modules and extensions are enabled. If any of the below modules are marked as problematic, resolve their configuration and restart the onboarding process.</div>
        <div class="p-2 text-xl">Extensions</div>
		<?php
            $carry_on = true;
            $required_extensions = array(
                'gd' => array('The GD extension is missing', 'GD'),
                'pdo_sqlite' => array('The pdo_sqlite extension is missing', 'PDO_SQLITE'),
                'sqlite3' =>array('The SQLite3 extension is missing', 'SQLITE'),
                'mbstring' => array('The mbstring extension is missing', 'MBSTRING'),
                'curl' => array('The curl extension is missing', 'CURL'),
                //'xmlwriter' => array('The xmlwriter extension is missing', 'XMLWRITER'),
                //'xmlreader' => array('The xmlreader extension is missing', 'XMLREADER'),
                //'SimpleXML' => array('The simpleXML extension is missing', 'SIMPLEXML'),
                'xml' => array('The xml extension is missing', 'XML'),
                'session' => array('The session extension is missing', 'SESSION'),
                'json' => array('The cujsonrl extension is missing', 'JSON'),
                'hash' => array('The hash extension is missing', 'HASH'),
            );
            $loaded = get_loaded_extensions();
            foreach ($required_extensions as $key => $value) {
                if(in_array($key, $loaded)){
                    echo '    <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-500 text-white flex items-center justify-center rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <p class="ml-4 text-gray-700 text-lg">'.$value[1].' detected.</p>
                </div>';
                }else{
                    $carry_on = false;
                    echo '<div class="flex items-center">
                    <!-- Circular div for the cross -->
                    <div class="w-12 h-12 bg-red-500 text-white flex items-center justify-center rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <p class="ml-4 text-gray-700 text-lg">Error! '.$value[0].'</p>
                </div>';
                }
            }
        ?>
        <div class="p-2 text-xl">Modules</div>
        <?php
        $loaded_modules = apache_get_modules();
        $required_modules = array(
            'mod_headers' => array('The headers module is missing','Headers'),
            'mod_include' => array('The include module is missing', 'Include'),
            'mod_rewrite' => array('The rewrite module is missing', 'Rewrite'),
            'mod_ssl' => array('The SSL module is missing','SSL')
        );
        foreach ($required_modules as $key => $value){
            if(in_array($key, $loaded_modules)){
                echo '    <div class="flex items-center">
                <div class="w-12 h-12 bg-green-500 text-white flex items-center justify-center rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <p class="ml-4 text-gray-700 text-lg">'.$value[1].' detected.</p>
            </div>';
            }else{
                $carry_on = false;
                echo '<div class="flex items-center">
                <!-- Circular div for the cross -->
                <div class="w-12 h-12 bg-red-500 text-white flex items-center justify-center rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <p class="ml-4 text-gray-700 text-lg">Error! '.$value[0].'</p>
            </div>';
            }
        }
        ?>
        <div class="p-2 text-xl">Version</div>
        <div class='p-2 text'>PHP version 8.2 or higher is recommended</div> 
        <?php
            $version_error = False;
            $detected_version = explode('.', phpversion()); 
            $major = $detected_version[0];
            $minor = $detected_version[1];
            if ($major < 8){
                $version_error = True;
            }
            if ($major == 8 && $minor < 2){
                $version_error = True;
            }
            if(!($version_error)){
                echo '<div class="flex items-center">
                <div class="w-12 h-12 bg-green-500 text-white flex items-center justify-center rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <p class="ml-4 text-gray-700 text-lg">Valid PHP version detected.</p>
            </div>'; 
            }else{
                $carry_on = false;
                echo '<div class="flex items-center">
                <!-- Circular div for the cross -->
                <div class="w-12 h-12 bg-red-500 text-white flex items-center justify-center rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <p class="ml-4 text-gray-700 text-lg">Error! Unsupported version of PHP detected.</p>
            </div>';
            }
        
        if($carry_on){
            echo"<div class='p-2'><a href='set_admin.php'><button class='bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded'>Next >></button></a></div>"; 
        }else{
            echo"<div class='p-2 notice'>This system didn't pass the readyness checks. Resolve these first before continuing.</div>"; 
        }
        ?>
	</div>
    
</body>
</html>