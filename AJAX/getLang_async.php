<?php
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
header('Content-Type: application/json; charset=utf-8');
$x = $_GET;
if (isset($x['node'])){
  $nodeid = (int)$x['node'];

  $json_body = array(
    "nodeid"=> $nodeid,
    "uri"=> URI, 
	"extractor" => LANGEXTRACTOR,
    "username"=> USERNAME, 
    "password"=> PASSWORD,
    "database"=> DBNAME,
    "textlabel"=> TEXNODE,
    "textproperty"=> TEXNODETEXT
  ); 
  $method_name = 'detect_language';    //detect_language for detection endpoint. (JSON body is different too!!!!)
  $gunicorn_servlet = CONNECTORENDPOINT;
  
  $url = $gunicorn_servlet.rtrim('/').'/'.$method_name;
  
  // Convert the PHP array to a JSON string
  $json_data = json_encode($json_body);
  
  // Initialize cURL
  $ch = curl_init($url);
  
  
  // Set cURL options
  curl_setopt($ch, CURLOPT_POST, true); // Specify this is a POST request
  curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data); // Attach the JSON data
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the response instead of printing it
  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json', // Set the content type to JSON
    'Content-Length: ' . strlen($json_data) // Set the content length
  ));
  
  // Execute the request
  $response = curl_exec($ch);
  
  // // Check for errors
  // if (curl_errno($ch)) {
    //     echo 'cURL Error: ' . curl_error($ch);
    // } else {
      //     // Print the response from the server
      //     echo "Response: " . $response;
      // }
      
      // Close the cURL session
      curl_close($ch);
      /////////////////
      
      //$parsedResult = json_decode($response);

      echo $response;
}else{
  die(json_encode("invalid request."));
}
?>
