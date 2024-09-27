<?php
//****************************************//
//api_deleteFile.php
//
//Description:
//    delete a file from media server filesystem
//
//params:
//      clientID: integer: id of the client of the file to delete
//        itemID: integer: id of the item containing the file to delete
//    propertyID: integer: id of the property of the item that contains the file
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
isset($GLOBALS["RS_POST"]["itemID"    ]) ? $itemID     =               $GLOBALS["RS_POST"]["itemID"    ]  : dieWithError(400);
isset($GLOBALS["RS_POST"]["propertyID"]) ? $propertyID =               $GLOBALS["RS_POST"]["propertyID"]  : dieWithError(400);

$directory = $RSfilePath . "/" . $clientID . "/" . $propertyID . "/";
$file_path = $directory . $itemID;

$results['result'] = "OK";

//check file exists
$nombres_archivo = glob($file_path . "_*");

if (count($nombres_archivo) > 0) {
    // The file(s) exists, delete it
    foreach($nombres_archivo as $nombre_archivo){
        if(!unlink($nombre_archivo)) {
            // Error al borrar
            $results['result'] = "NOK";
        	$results['description'] = "CANT DELETE THE FILE";
            exit;
        }
    }
}

RSReturnArrayResults($results, false);
?>
