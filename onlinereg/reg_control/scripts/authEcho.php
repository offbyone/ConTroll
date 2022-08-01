<?php

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "overview";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

ajaxSuccess($response);
?>
