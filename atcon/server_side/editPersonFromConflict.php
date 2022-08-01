<?php
if(!isset($_SERVER['HTTPS']) or $_SERVER["HTTPS"] != "on") {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
    exit();
}

require_once "lib/base.php";
require_once "lib/ajax_functions.php";

$perm="data_entry";
$con = get_con();
$conid=$con['id'];
$response = array("post" => $_POST, "get" => $_GET);
$check_auth=false;
if(isset($_POST) && isset($_POST['user']) && isset($_POST['passwd'])) {
    $check_auth = check_atcon($_POST['user'], $_POST['passwd'], $perm, $conid);
}

if($check_auth == false) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if(!isset($_POST) || !isset($_POST['newID'])) {
    $response['error'] = "No Data";
    ajaxSuccess($response);
    exit();
}

$changeLog = "Atcon Edit $user: ". date(DATE_ATOM) 
    . ": updating from conflict " . sql_safe($_POST['newID']) . "=>" 
    . sql_safe($_POST['oldID']) . ": ";
$change = false;

$newData = dbQuery("SELECT * FROM newperson WHERE id='" .
                        sql_safe($_POST['newID']) ."';")->fetch_assoc();

dbQuery("UPDATE newperson SET perid='" .
            sql_safe($_POST['oldID']) . "' WHERE id='" .
            sql_safe($_POST['newID']) . "';");

$query = "UPDATE perinfo SET ";
if(isset($_POST['conflictFormName'])) {
  $change = true;
  $changeLog .= "first_name, ";
  $query .= "first_name='" . sql_safe($newData['first_name']) . "'";
  if($change) { $query .= ", "; }
  $changeLog .= "middle_name, ";
  $query .= "middle_name='" . sql_safe($newData['middle_name']) . "'";
  if($change) { $query .= ", "; }
  $changeLog .= "last_name, ";
  $query .= "last_name='" . sql_safe($newData['last_name']) . "'";
  if($change) { $query .= ", "; }
  $changeLog .= "suffix, ";
  $query .= "suffix='" . sql_safe($newData['suffix']) . "'";
}
if(isset($_POST['conflictFormEmail'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "email_addr, ";
  $query .= "email_addr='" . sql_safe($newData['email_addr']) . "'";
}
if(isset($_POST['conflictFormPhone'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "phone, ";
  $query .= "phone='" . sql_safe($newData['phone']) . "'";
}
if(isset($_POST['conflictFormBadge'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "badge_name, ";
  $query .= "badge_name='" . sql_safe($newData['badge_name']) . "'";
}
if(isset($_POST['conflictFormAddr'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "address, ";
  $query .= "address='" . sql_safe($newData['address']) . "'";
  if($change) { $query .= ", "; }
  $changeLog .= "addr_2, ";
  $query .= "addr_2='" . sql_safe($newData['addr_2']) . "'";
  if($change) { $query .= ", "; }
  $changeLog .= "city, ";
  $query .= "city='" . sql_safe($newData['city']) . "'";
  if($change) { $query .= ", "; }
  $changeLog .= "state, ";
  $query .= "state='" . sql_safe($newData['state']) . "'";
  if($change) { $query .= ", "; }
  $changeLog .= "zip, ";
  $query .= "zip='" . sql_safe($newData['zip']) . "'";
  if($change) { $query .= ", "; }
  $changeLog .= "country, ";
  $query .= "country='" . sql_safe($newData['country']) . "'";
}
if($change) {
  if($change) { $query .= ", "; }
  $changeLog .= "active, ";
  $query .= "active='Y'";
}

if($change) {
  $query .= " WHERE id='" . sql_safe($_POST['oldID']) . "';";

  $res = dbQuery($query);
  $query2 = "UPDATE perinfo SET change_notes=CONCAT(change_notes, '<br/>$changeLog') WHERE id='".sql_safe($_POST['oldID'])."';";
  $res = dbQuery($query2);
}

$setQ = "UPDATE reg SET " .
    "perid='".sql_safe($_POST['oldID']) . "' WHERE " .
    "newperid='".sql_safe($_POST['newID']) . "';";

$setR = dbQuery($setQ);
$setQ = "UPDATE transaction SET " .
    "perid='".sql_safe($_POST['oldID']) . "' WHERE " .
    "newperid='".sql_safe($_POST['newID']) . "';";

$setR = dbQuery($setQ);

$response['id'] = $_POST['oldID'];
$response['changeLog'] = $changeLog;
$response['change'] = $change;

ajaxSuccess($response);
?>
