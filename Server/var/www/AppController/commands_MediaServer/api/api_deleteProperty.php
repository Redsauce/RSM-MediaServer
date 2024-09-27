<?php
//****************************************//
//api_deleteProperty.php
//
//Description:
//    delete all files from passed property in media server filesystem
//
//params:
//      clientID: integer: id of the client of the property to delete
//    propertyID: integer: id of the property to delete
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

isset($GLOBALS["RS_POST"]["clientID"  ]) ? $clientID   =               $GLOBALS["RS_POST"]["clientID"  ]  : dieWithError(400);
isset($GLOBALS["RS_POST"]["propertyID"]) ? $propertyID =               $GLOBALS["RS_POST"]["propertyID"]  : dieWithError(400);

$directory = $RSfilePath . "/" . $clientID . "/" . $propertyID;

$results['result'] = "OK";

//Check folder exists
if(is_dir($directory)){
    //check for files in property folder
    $nombres_archivo = glob($directory . "/*");

    if (count($nombres_archivo) > 0) {
        // There are files for property, delete them
        foreach($nombres_archivo as $nombre_archivo){
            if(!unlink($nombre_archivo)) {
                // Error al borrar
                $results['result'] = "NOK";
            	$results['description'] = "CANT DELETE FILE IN PROPERTY FOLDER";
                exit;
            }
        }
    }

    if ($results['result'] != "NOK") {
        // Delete the folder
        if(!rmdir($directory)) {
            // Error al borrar
            $results['result'] = "NOK";
        	$results['description'] = "CANT DELETE THE PROPERTY FOLDER";
            exit;
        }
    }
}

RSReturnArrayResults($results, false);
?>
