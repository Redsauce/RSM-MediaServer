<?php
//***************************************************
//RSsecurityCheck.php
//***************************************************
//Description:
//	checks if the client has privileges to work with the system
//***************************************************

require_once "../utilities/RSconfiguration.php";
require_once "../utilities/RStools.php";

//check client ip is allowed
if (!in_array($_SERVER['REMOTE_ADDR'],$RSMserversAllowed)){
    error_log("Forbidden access from server: ".$_SERVER['REMOTE_ADDR']);
    dieWithError(403);
}
?>
