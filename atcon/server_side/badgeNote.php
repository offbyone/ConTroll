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


$user = 'regadmin@bsfs.org';
$response['user'] = $user;
$userQ = "SELECT id FROM user WHERE email='$user';";
$userR = fetch_safe_assoc(dbQuery($userQ));
$userid = $userR['id'];
$con = get_conf('con');
$conid=$con['id'];

 $atconIdQ = "SELECT id FROM atcon WHERE conid=$conid AND transid=" .
    sql_safe($_POST['transid']) . ";";
  $atconId = fetch_safe_assoc(dbQuery($atconIdQ));

  $attachQ = "INSERT IGNORE INTO atcon_badge (atconId, badgeId, action, comment)  VALUES (" .
    $atconId['id'] . ", " . sql_safe($_POST['badgeId']) .
    ", '" . sql_safe($_POST['type']) . "', '" . $user . ": " . sql_safe($_POST['content']) . "');";
  $attachR = dbInsert($attachQ);

  $atconQ = "SELECT B.date, A.atcon_key, B.action, B.comment FROM atcon_badge as B, atcon as A WHERE A.id=B.atconId AND badgeId='"
    . sql_safe($_POST['badgeId']) . "' AND action != 'attach';";

  $atconR = dbQuery($atconQ);
  $actions = array();
  if($atconR->num_rows > 0) while($act = fetch_safe_assoc($atconR)) {
    array_push($actions, $act);
  }
$response['actions'] = $actions;


ajaxSuccess($response);
?>
