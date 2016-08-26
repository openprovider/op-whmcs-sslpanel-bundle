<?php

require(dirname(__FILE__) . "/../../../dbconnect.php");
require(ROOTDIR . "/includes/functions.php");
require(ROOTDIR . "/includes/registrarfunctions.php");
require(dirname(__FILE__) . "/openprovider.php");

require_once(dirname(__FILE__) . '/opApiTools.php');

$cronreport = "Openprovider Domain Sync Report<br>\n---------------------------------------------<br>\n";
$params = getRegistrarConfigOptions("openprovider");
$params['runFromCronScript'] = true;
$cronreport .= opApiTools::updateDomainData($params);
$alert = isset($params['failed']) ? ' - FAILED' : '';

echo "\n\n" . $cronreport;
logActivity("Openprovider Domain Sync Run");
sendAdminNotification("system", "WHMCS Openprovider Domain Synchronization Report$alert", $cronreport);

?>
