<?php
//****************************************//
//api_duplicateProperty.php
//
//Description:
//    duplicates all files from passed start property into passed end property in media server filesystem
//
//params:
//           clientID: integer: id of the client of the property to duplicate
//    propertyIDstart: integer: id of the source property
//      propertyIDend: integer: id of the destination property
//returns:
//  xml response: result: OK/NOK and description if failed
//****************************************//

// Clean GET data in order to avoid SQL injections
$search = array("'", "\"");
$replace = array("", "");

foreach ($_GET as $key => $value) {
    $GLOBALS["RS_GET"][$key] = str_replace($search, $replace, $value);
}

require_once "../utilities/RStools.php";
require_once "../utilities/RSconfiguration.php";
require_once "../utilities/RSsecurityCheck.php";

isset($GLOBALS["RS_POST"]["clientID"       ]) ? $clientID   =               $GLOBALS["RS_POST"]["clientID"       ]  : dieWithError(400);
isset($GLOBALS["RS_POST"]["propertyIDstart"]) ? $propertyIDstart =          $GLOBALS["RS_POST"]["propertyIDstart"]  : dieWithError(400);
isset($GLOBALS["RS_POST"]["propertyIDend"  ]) ? $propertyIDend =            $GLOBALS["RS_POST"]["propertyIDend"  ]  : dieWithError(400);

$startDirectory = $RSfilePath . "/" . $clientID . "/" . $propertyIDstart;
$endDirectory   = $RSfilePath . "/" . $clientID . "/" . $propertyIDend;

$results['result'] = "OK";

//Check start folder exists
if(is_dir($startDirectory)){

    // Check if end folder exists
    if (!file_exists($endDirectory)) {
        mkdir($endDirectory, 0775, true);
    }

    //check for files in start folder
    $files = scandir($startDirectory);
    foreach ($files as $file) {
        // Copy files
        if ($file != "." && $file != "..") {
            if (!copy("$startDirectory/$file", "$endDirectory/$file")) {
                // Error al copiar
                $results['result'] = "NOK";
                $results['description'] = "CANT DUPLICATE FILE IN PROPERTY FOLDER";
                exit;
            }
        }
    }
}

RSReturnArrayResults($results, false);
?>
