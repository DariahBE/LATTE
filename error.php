<?php
$title = 'An unspecified error occurred.';
$record = 0;
$fullErrMsg = '';
if (isset($_GET['type'])){
  switch ($_GET['type']){
    case '':
      $title = 'An unspecified error occurred.';
      break;
    case 'text':
      $title = 'No matching text found.';
      $fullErrMsg = 'There was no text found matching to your search criteria.';
      break;
    case 'textmissing':
      $title = 'No text identifier has been provided.';
      $fullErrMsg = 'Your search request did not contain a valid text identifier. The link you followed may have been broken.';
      break;
    case 'node':
      $title = 'Invalid nodetype.';
      $fullErrMsg = 'Your request does not contain a valid node identifier. This nodetype probably never existed. The link you followed may bave been broken.';
      break;
    case 'id':
      $title = 'Invalid identifier.';
      $fullErrMsg = 'The provided ID does not seem to have a maching record. The node is probably deleted or never existed.';
      break;
    case 'conn': 
      $title = 'Database unreachable.'; 
      $fullErrMsg = 'The desired database could not be reached. Either the configured details are wrong, or the database is down.'; 
    case 'notexts': 
      $title = 'No texts in database.';
      $fullErrMsg = 'The database is reachable but there are no texts in it. The database is probably empty or the config file is not set up correctly.';
    default:
      break;
  }
} 
?>



<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="/CSS/style_entities.css">
    <!--<link rel="stylesheet" href="/CSS/styling.css"> -->
    <link rel="stylesheet" href="/CSS/stylePublic.css">
    <link rel="stylesheet" href="/CSS/overlaystyling.css">
  </head>
  <body class="container bg-gray-300 h-screen flex">
    <div class=" m-auto">
      <div class="max-w-sm w-full lg:max-w-full lg:flex">
        <div class="h-48 lg:h-auto lg:w-48 flex-none bg-cover text-center overflow-hidden" style="background-image: url('/images/error.jpg')" title="Error">
        </div>
        <div class="p-4 flex flex-col justify-between leading-normal">
          <div class="mb-8">
            <div class="text-gray-900 font-bold text-xl mb-2">Something went not quite according to plan.</div>
            <p class="text-gray-700 text-base"><?php echo $fullErrMsg; ?></p>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>
