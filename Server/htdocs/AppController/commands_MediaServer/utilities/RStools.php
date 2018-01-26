<?php
//***************************************************//
// RStools.php
//***************************************************//
// Description:
//	diverse utility functions.
//***************************************************//

// Clean POST data in order to avoid SQL injections
$search  = array("'", "\"");
$replace = array("&rsquo;" , "&quot;");
foreach ($_POST as $key => $value) $GLOBALS['RS_POST'][$key] = str_replace($search, $replace, $value);


function dieWithError($code, $message = null) {

    switch ($code) {

        case 400 :
            $errorString = "400 Bad Request";
            header("HTTP/1.1 " . $errorString, true, 400);
            break;

        case 403 :
            $errorString = "403 Forbidden";
            header("HTTP/1.1 " . $errorString, true, 403);
            break;

        case 404 :
            $errorString = "404 Page not found";
            header("HTTP/1.1 " . $errorString, true, 404);
            break;

        case 500 :
            $errorString = "500 Internal Server Error";
            header("HTTP/1.1 " . $errorString, true, 500);
            break;

        default :
            $errorString = "400 Bad Request";
            header("HTTP/1.1 " . $errorString, true, 400);
            break;
    }

	// Si hay info extra la mostramos por la salida de error
	if($message != null) {
		RSError("dieWithError: " . $errorString . ". " . $message);
	}

    die($errorString);
}

// Converts the passed database results to XML
function RSReturnArrayResults($array, $compressed = true) {
    global $RSallowUncompressed;

    // this function uses to return few data and, overall, the number of concatenations is always small... so it's better using
    // the string concatenation method
    $theFile = '';
    $theFile .= "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>";
    $theFile .= "<RSRecordset>";
    $theFile .= "<rows>";
    $theFile .= "<row>";

    if (is_array($array))
        foreach ($array as $name => $value)
            $theFile .= "<column name=\"" . $name . "\"><![CDATA[" . str_replace("]]>", "]]]]><![CDATA[>", $value) . "]]></column>";

    $theFile .= "</row>";
    $theFile .= "</rows>";
    $theFile .= "</RSRecordset>";

    $compress = ((isset($GLOBALS['RS_POST']['RSsendUncompressed']) || !$compressed) && ($RSallowUncompressed))? FALSE : TRUE;

    header("Content-type: text/xml");
    if ($compress) {
        $theFile = gzCompress($theFile, 9);
        Header('Content-type: application/x-gzip');
    }
    Header('Content-Length: ' . strlen($theFile));
    echo ($theFile);

    // Terminate PHP execution
    exit;
}
?>
