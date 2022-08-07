<?php
if(!isset($_SERVER['HTTPS']) or $_SERVER["HTTPS"] != "on") {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
    exit();
}

require_once "lib/base.php";
require_once "lib/ajax_functions.php";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

$perm="data_entry";
$con = get_con();
$conid=$con['id'];
$check_auth=false;
if(isset($_POST) && isset($_POST['user']) && isset($_POST['passwd'])) {
    $check_auth = check_atcon($_POST['user'], $_POST['passwd'], $perm, $conid);
}

if($check_auth == false) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}


if(!isset($_POST)) {
    $response['error'] = "No Data";
    ajaxSuccess($response);
    exit();
}

$query = "INSERT INTO newperson (last_name, first_name, middle_name, suffix, email_addr, phone, badge_name, address, addr_2, city, state, zip, country) VALUES (";


$query .= "'" . sql_safe($_POST['lname']) . "', '" .
    sql_safe($_POST['fname']) . "', '" .
    sql_safe($_POST['mname']) . "', '" .
    sql_safe($_POST['suffix']) . "', ";
$query .= "'" . sql_safe($_POST['email']) . "', '" .
    sql_safe($_POST['phone']) . "', ";
if(isset($_POST['badge']) && $_POST['badge'] != '') {
  $query .= "'" . sql_safe($_POST['badge']) . "', ";
} else {
  $query .= "NULL, ";
}
$query .= "'" . sql_safe($_POST['address']) . "', '" .
    sql_safe($_POST['addr2']) . "', '" .
    sql_safe($_POST['city']) . "', '" .
    sql_safe($_POST['state']) . "', '" .
    sql_safe($_POST['zip']) . "', '" .
    sql_safe($_POST['country']) . "');";

$id = dbInsert($query);

$response['id'] = $id;

ajaxSuccess($response);
?>