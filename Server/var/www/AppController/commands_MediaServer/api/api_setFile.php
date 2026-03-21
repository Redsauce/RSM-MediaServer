<?php
//    store a file in media server filesystem (safe replace)
//
// Description:
//    Stores a file for a given (clientID, itemID, propertyID).
//    The process ensures safe replacement of previous versions:
//
//      1. Uploads the file to a temporary path.
//      2. If the final file already exists, renames it to a unique backup path.
//      3. Renames the uploaded temporary file to the final destination.
//      4. If step 3 succeeds, deletes the backup file.
//      5. If step 3 fails, restores the backup file to its original name.
//      6. Deletes any previous files matching itemID_* in the same directory,
//         excluding the newly stored file.
//
//    This guarantees that an existing file is never removed unless the new one
//    has been successfully stored, without relying on OS-specific overwrite
//    behavior of rename().
//
//    File path:
//      {RSfilePath}/{clientID}/{propertyID}/
//
//    File name format:
//      {itemID}_{base64_encoded_original_name}.{extension}
//
//params:
//      clientID: integer
//        itemID: integer
//    propertyID: integer
//          data: binary file (multipart)
//          name: base64 encoded original filename
//
//returns:
//      XML: result = OK/NOK (+ optional description)
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

isset($GLOBALS["RS_POST"]["clientID"]) ? $clientID = $GLOBALS["RS_POST"]["clientID"] : dieWithError(400);
isset($GLOBALS["RS_POST"]["itemID"]) ? $itemID = $GLOBALS["RS_POST"]["itemID"] : dieWithError(400);
isset($GLOBALS["RS_POST"]["propertyID"]) ? $propertyID = $GLOBALS["RS_POST"]["propertyID"] : dieWithError(400);
isset($GLOBALS["RS_POST"]["name"]) ? $encodedName = $GLOBALS["RS_POST"]["name"] : dieWithError(400);

$name = base64_decode($encodedName, true);
if ($name === false) {
    dieWithError(400);
}

if (!isset($_FILES['data']['tmp_name']) || !is_uploaded_file($_FILES['data']['tmp_name'])) {
    dieWithError(400);
}

$results = array();
$results['result'] = "NOK";

$directory = $RSfilePath . "/" . $clientID . "/" . $propertyID . "/";
$file_path = $directory . $itemID;

$uploadedFilePath = $_FILES['data']['tmp_name'];

$extension = pathinfo($name, PATHINFO_EXTENSION);
$destinationFilePath = $file_path . "_" . rawurlencode(base64_encode($name)) . "." . $extension;

// Nombre temporal único dentro del mismo directorio
$tempDestinationFilePath = $file_path . "_uploading_" . uniqid("", true) . ".tmp";

// Posible backup del fichero final actual
$backupDestinationFilePath = $file_path . "_backup_" . uniqid("", true) . ".bak";

// Crear directorio si no existe
if (!is_dir($directory)) {
    if (!mkdir($directory, 0775, true) && !is_dir($directory)) {
        $results['description'] = "CANT CREATE DIRECTORY";
        RSReturnArrayResults($results, false);
        exit;
    }
}

// Subir primero a nombre temporal
if (!move_uploaded_file($uploadedFilePath, $tempDestinationFilePath)) {
    $results['description'] = "CANT MOVE UPLOADED FILE TO TEMPORARY PATH";
    RSReturnArrayResults($results, false);
    exit;
}

// Dar permisos al temporal
if (!chmod($tempDestinationFilePath, 0664)) {
    @unlink($tempDestinationFilePath);
    $results['description'] = "CANT CHMOD TEMPORARY FILE";
    RSReturnArrayResults($results, false);
    exit;
}

$hadPreviousFinalFile = false;

// Si ya existe el destino final, renombrarlo primero a backup único
if (is_file($destinationFilePath)) {
    if (!rename($destinationFilePath, $backupDestinationFilePath)) {
        @unlink($tempDestinationFilePath);
        $results['description'] = "CANT RENAME PREVIOUS FINAL FILE TO BACKUP FILE";
        RSReturnArrayResults($results, false);
        exit;
    }

    $hadPreviousFinalFile = true;
}

// Renombrar el temporal al nombre definitivo
if (!rename($tempDestinationFilePath, $destinationFilePath)) {
    @unlink($tempDestinationFilePath);

    if ($hadPreviousFinalFile) {
        @rename($backupDestinationFilePath, $destinationFilePath);
    }

    $results['description'] = "CANT RENAME TEMPORARY FILE TO FINAL FILE";
    RSReturnArrayResults($results, false);
    exit;
}

// Si todo fue bien, borrar el backup
if ($hadPreviousFinalFile) {
    if (is_file($backupDestinationFilePath) && !unlink($backupDestinationFilePath)) {
        $results['description'] = "FILE STORED BUT CANT DELETE BACKUP FILE";
        RSReturnArrayResults($results, false);
        exit;
    }
}

// Borrar archivos antiguos del mismo itemID, excluyendo el recién creado
$nombres_archivo = glob($file_path . "_*");

if ($nombres_archivo !== false && count($nombres_archivo) > 0) {
    foreach ($nombres_archivo as $nombre_archivo) {
        if ($nombre_archivo === $destinationFilePath) {
            continue;
        }

        if (is_file($nombre_archivo)) {
            if (!unlink($nombre_archivo)) {
                $results['description'] = "FILE STORED BUT CANT DELETE PREVIOUS FILE";
                RSReturnArrayResults($results, false);
                exit;
            }
        }
    }
}

// Asegurar permisos finales
if (!chmod($destinationFilePath, 0664)) {
    $results['description'] = "FILE STORED BUT CANT CHMOD FINAL FILE";
    RSReturnArrayResults($results, false);
    exit;
}

$results['result'] = "OK";
RSReturnArrayResults($results, false);