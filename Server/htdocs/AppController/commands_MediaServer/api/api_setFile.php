<?php
//****************************************//
//api_setFile.php
//
//Description:
//    store a file in media server filesystem (overwrite if already exists)
//
//params:
//      clientID: integer: id of the client of the file to store
//        itemID: integer: id of the item containing the file to store
//    propertyID: integer: id of the property of the item that contains the file
//          data:  string: picture binary stream
//          name:  string: name of the file
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
isset($GLOBALS["RS_POST"]["data"      ]) ? $data       = base64_decode($GLOBALS["RS_POST"]["data"      ]) : dieWithError(400);
isset($GLOBALS["RS_POST"]["name"      ]) ? $name       = base64_decode($GLOBALS["RS_POST"]["name"      ]) : dieWithError(400);

$directory = $RSfilePath . "/" . $clientID . "/" . $propertyID . "/";
$file_path = $directory . $itemID;

$results['result'] = "OK";

//check file exists
$nombres_archivo = glob($file_path . "_*");

if (count($nombres_archivo) > 0) {
    // The file(s) already exists, delete it before storing new version
    foreach($nombres_archivo as $nombre_archivo){
        if(!unlink($nombre_archivo)) {
            // Error al borrar
            $results['result'] = "NOK";
        	$results['description'] = "CANT DELETE PREVIOUS FILE";
            exit;
        }
    }
}

if($results['result'] != "NOK") {
    $extension  = pathinfo($name, PATHINFO_EXTENSION);
    $results = saveFile($data, $file_path, $name, $extension);
}

RSReturnArrayResults($results, false);

/**
 * Save file in cache directory
 */
function saveFile($file_original, $path, $name, $extension) {
    global $directory;

    $res['result'] = "OK";

    // Check if directory exists
    if (!file_exists($directory)) {
        mkdir($directory, 0775, true);
    }

    $file = $path . "_" . rawurlencode(base64_encode($name)) . "." . $extension;

    // Check folder exists or create it otherwise
    $dirname = dirname($file);

    if (!is_dir($dirname)) {
        if (!mkdir($dirname, 0755, true)) {
            $res['result'] = "NOK";
        	$res['description'] = "CANT CREATE DIRECTORY ".$dirname;
            return $res;
        }
    }

    $fh = fopen($file, "w");
    if ($fh) {
        fwrite($fh, $file_original);
        fclose($fh);
    } else {
        $res['result'] = "NOK";
    	$res['description'] = "CANT CREATE FILE";
        return $res;
    }

    return $res;
}
?>
