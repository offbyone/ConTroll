<?php
require_once "../lib/base.php";

$need_login = google_init("page");
$page = "reports";

if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}


$con = get_conf("con");
$conid=$con['id'];

if(!isset($_GET) || !isset($_GET['artid'])) {
    echo "Artist #, Item #, Title, Number Sold, Item Price, Total"
    . "\n";
    exit();
} else {
    $artid = sql_safe($_GET['artid']);
}

$nameQuery = "SELECT concat_ws('_', P.first_name, P.last_name) FROM artshow as S JOIN artist as A on A.id=S.artid JOIN perinfo as P on P.id=A.artist WHERE S.id=$artid;";

$nameR = fetch_safe_array(dbQuery($nameQuery));
$name=$nameR[0];

header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="checkout_'.$name.'.csv"');

$query = "SELECT A.art_key, I.item_key, I.title"
    . ", CASE I.quantity < I.original_qty"
        . " WHEN true THEN I.original_qty - I.quantity"
        . " ELSE 1"
        . " END as number_sold"
    . ", CASE I.type"
        . " WHEN 'art' THEN I.final_price"
        . " ELSE I.sale_price"
        . " END as item_price"
    . " FROM artItems as I"
        . " JOIN artshow as A on A.id=I.artshow"
    . " WHERE I.artshow = $artid"
    . " AND (I.quantity < I.original_qty OR I.final_price IS NOT null OR status='Sold Bid Sheet');";

//echo $query; exit();

echo "Artist #, Item #, Title, Number Sold, Item Price, Total"
    . "\n";

$reportR = dbQuery($query);
$total = 0;
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", html_entity_decode($reportL[$i], ENT_QUOTES | ENT_HTML401));
    }
    printf("\"%s\"", $reportL[3]*$reportL[4] );
    $total = $total + ($reportL[3]*$reportL[4]);
    echo "\n";
}
echo ",,$name TOTAL,,,$total\n";

$query = "SELECT A.art_key, I.item_key, I.title"
    . ", I.quantity"
    . " FROM artItems as I"
        . " JOIN artshow as A on A.id=I.artshow"
    . " WHERE I.artshow = $artid"
    . " AND ((I.type = 'print' AND I.quantity >0)"
    . " OR (I.type='art' AND I.status='Checked In')"
    . " OR I.type='nfs');";


echo "\n\n\n";

echo "Artist #, Item #, Title, Number Returned" . "\n";
$reportR = dbQuery($query);
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", html_entity_decode($reportL[$i], ENT_QUOTES | ENT_HTML401));
    }
    echo "\n";
}
echo ",,$name RETURNED\n";

//echo $query; exit();

?>