<?php

require_once(__DIR__ . "/../../lib/db_functions.php");

function redirect_https() {
    $ini = get_conf('reg');
    if ($ini['https'] <> 0) {
        if(!isset($_SERVER['HTTPS']) or $_SERVER["HTTPS"] != "on") {
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
            exit();
        }
    }
    db_connect();
    return $ini;
}

function ol_page_init($title) {
echo <<<EOF
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>$title</title>
    <link href='css/style.css' rel='stylesheet' type='text/css' />
    <link href='css/jquery-ui-1.13.1.css' rel='stylesheet' type='text/css' />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script type='text/javascript' src='javascript/jquery-min-3.60.js'></script>
    <script type='text/javascript' src='javascript/jquery-ui.min-1.13.1.js'></script>
    <script type='text/javascript' src='javascript/store.js'></script>
</head>
EOF;
}

?>
