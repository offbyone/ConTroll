<?php

// library AJAX Processor: artpos_findPeson.php
// ConTroll Registration System
// Author: Syd Weinstein
// Retrieve perinfo art bidder records for a perid

require_once('../lib/base.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$con = get_conf('con');
$conid = $con['id'];
$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'findRecord') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

if (!(check_atcon('artsales', $conid))) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

// findRecord:
// load all perinfo/reg records matching the search string or unpaid if that flag is passed
$find_type = $_POST['find_type'];
$name_search = $_POST['name_search'];

$response['find_type'] = $find_type;
$response['name_search'] = $name_search;

if (is_numeric($name_search)) {
//
// this is perid
//
    $findPersonQ = <<<EOS
SELECT p.id, first_name, middle_name, last_name, suffix, badge_name, email_addr, address, addr_2, city, state, zip, country, phone
FROM perinfo p
WHERE p.id=?;
EOS;
    $response['findPersonQ'] = $findPersonQ;
    $personR = dbSafeQuery($findPersonQ, 'i', array($name_search));
    $response['num_rows'] = $personR->num_rows;
    if($personR->num_rows == 0) {
        $response['status'] = "error";
        $response['error'] = "No Person Found";
    } else if($personR->num_rows == 1) {
        $response['person'] = $personR->fetch_assoc();
        $response['status'] = 'success';
        // now find any art for which is final and they are the high bidder
        $perid = $response['person']['id'];
        $findArtQ = <<<EOS
SELECT a.id, a.item_key, a.title, a.type, a.status, a.location, a.quantity, a.original_qty, a.min_price, a.sale_price, a.final_price, a.artshow, a.material,
       exRY.exhibitorNumber, ex.exhibitorName
FROM artItems a
JOIN exhibitorRegionYears exRY ON a.exhibitorRegionYearId = exRY.id
JOIN exhibitorYears exY ON exRY.exhibitorYearId = exY.id
JOIN exhibitors ex ON exY.exhibitorId = ex.id
WHERE a.bidder = ? AND a.conid = ?;
EOS;
        $findArtR = dbSafeQuery($findArtQ, 'ii', array($perid, $conid));
        $art = [];
        while ($findArtL = $findArtR->fetch_assoc()) {
            $art[] = $findArtL;
        }
        $response['art'] = $art;
        $response['message'] = 'One Person Found, ' . $findArtR->num_rows . ' art piece' . ($findArtR->num_rows == 1 ? '' : 's') . ' found';
    } else { // id -is key, numrows can only be zero or 1.
        $response['error'] = $personR->num_rows . " People Found, seek assistance.";
    }
} else {
//
// this is the string search portion as the field is alphanumeric
//
    $response['error'] = "Alphanumeric Search Not Implemented here!";
}

ajaxSuccess($response);
