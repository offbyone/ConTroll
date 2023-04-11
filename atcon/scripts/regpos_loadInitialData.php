<?php

// library AJAX Processor: regpos_loadInitialData.php
// Balticon Registration System
// Author: Syd Weinstein
// Retrieve load the mapping tables and session information into the javascript side

require_once('../lib/base.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$method = 'cashier';
if ($_POST && array_key_exists('nopay', $_POST)) {
    if ($_POST['nopay'] == 'true') {
        $method = 'data_entry';
    }
}

$con = get_conf('con');
$conid = $con['id'];
$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'loadInitialData') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}
if (!check_atcon($method, $conid)) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}


// loadInitialData:
// Load all the mapping tables for the POS function

$response['label'] = $con['label'];
$response['conid'] = $conid;
$response['badgePrinter'] = $_SESSION['badgePrinter'][0] != 'None';
$response['receiptPrinter'] = $_SESSION['receiptPrinter'][0] != 'None';
$response['user_id'] = $_SESSION['user'];
$response['hasManager'] = check_atcon('manager', $conid);
// get the start and end dates, and adjust for the memLabels based on the real dates versus today.
$condatesSQL = <<<EOS
SELECT startdate, enddate
FROM conlist
WHERE id=?;
EOS;
$r = dbSafeQuery($condatesSQL, 'i', array($conid));
if ($r->num_rows == 1) {
    $l = fetch_safe_assoc($r);
    $startdate = $l['startdate'];
    $enddate = $l['enddate'];
    $response['startdate'] = $startdate;
    $response['enddate'] = $enddate;
} else {
    RenderErrorAjax('Current convention ($conid) not in the database.');
    exit();
}
mysqli_free_result($r);
// if now is pre or post con set search date to first day of con
//web_error_log("start = " . strtotime($startdate) . ", end = " . strtotime($enddate) . ", now = " . time());
if (time() < strtotime($startdate) || strtotime($enddate) +24*60*60 < time()) {
    $searchdate = $startdate;
} else {
    $searchdate = date('Y-m-d');
}
//web_error_log("Search date now $searchdate");

// get all the memLabels
$priceQ = <<<EOS
SELECT id, conid, memCategory, memType, memAge, memGroup, label, shortname, sort_order, price,
    CASE 
        WHEN (atcon != 'Y') THEN 0
        WHEN (startdate > ?) THEN 0
        WHEN (enddate <= ?) THEN 0
        ELSE 1 
    END AS canSell      
FROM memLabel
WHERE
    conid IN (?, ?)
ORDER BY sort_order, price DESC;
EOS;

$memarray = array();
$r = dbSafeQuery($priceQ, 'ssii', array($searchdate, $searchdate, $conid, $conid + 1));
while ($l = fetch_safe_assoc($r)) {
    $memarray[] = $l;
}
mysqli_free_result($r);
$response['memLabels'] = $memarray;

// memTypes
$memTypeSQL = <<<EOS
SELECT memType
FROM memTypes
WHERE active = 'Y'
ORDER BY sortorder;
EOS;

$typearray = array();
$r = dbQuery($memTypeSQL);
while ($l = fetch_safe_assoc($r)) {
    $typearray[] = $l['memType'];
}
mysqli_free_result($r);
$response['memTypes'] = $typearray;

// memCategories
$memCategorySQL = <<<EOS
SELECT memCategory
FROM memCategories
WHERE active = 'Y'
ORDER BY sortorder;
EOS;

$catarray = array();
$r = dbQuery($memCategorySQL);
while ($l = fetch_safe_assoc($r)) {
    $catarray[] = $l['memCategory'];
}
mysqli_free_result($r);
$response['memCategories'] = $catarray;

// ageList
$ageListSQL = <<<EOS
SELECT ageType, label, shortname
FROM ageList
WHERE conid = ?
ORDER BY sortorder;
EOS;

$agearray = array();
$r = dbSafeQuery($ageListSQL, 'i', array($conid));
while ($l = fetch_safe_assoc($r)) {
    $agearray[] = $l;
}
mysqli_free_result($r);
$response['ageList'] = $agearray;

ajaxSuccess($response);