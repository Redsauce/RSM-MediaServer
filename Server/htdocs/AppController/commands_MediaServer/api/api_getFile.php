<?php
//****************************************//
//api_getFile.php
//
//Description:
//    returns a file from the media server filesystem
//
//params:
//      clientID: integer: id of the client of the file to retrieve
//        itemID: integer: id of the item containing the file to retrieve
//    propertyID: integer: id of the property of the item that contains the file
//returns:
//    string: picture binary stream
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

isset($GLOBALS["RS_POST"]["clientID"  ]) ? $clientID   = $GLOBALS["RS_POST"]["clientID"  ] : dieWithError(400);
isset($GLOBALS["RS_POST"]["itemID"    ]) ? $itemID     = $GLOBALS["RS_POST"]["itemID"    ] : dieWithError(400);
isset($GLOBALS["RS_POST"]["propertyID"]) ? $propertyID = $GLOBALS["RS_POST"]["propertyID"] : dieWithError(400);

$directory = $RSfilePath . "/" . $clientID . "/" . $propertyID . "/";
$file_path = $directory . $itemID;

//check file in cache
$nombres_archivo = glob($file_path . "_*");

// Allow to request this document from JS libraries
header('Access-Control-Allow-Origin: *');

if (count($nombres_archivo) > 0) {

    // The file exists
    $nombre_archivo = $nombres_archivo[0];

    $parts = explode(".", basename($nombre_archivo));
    $nombreSinExtension = $parts[0];
    $extension = $parts[1];
    $nombreSinExtension = explode("_", $nombreSinExtension);
    // Original file name is in the string after the last "_" so decode it
    $nombre_descarga = base64_decode(rawurldecode(end($nombreSinExtension)));

    // Return the file
    if (strtolower($extension) == "apk"){
        header('Content-type: application/vnd.android.package-archive');
    } else {
        header("Content-type: application/" . $extension);
    }
    header('Content-Disposition: attachment; filename="' . $nombre_descarga . '"');
    readfile($nombre_archivo);
    exit;
} else {
    //file not exists
    dieWithError(404);
}
