<?php
global $db_ini;

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "registration";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

$sub = $check_auth['sub'];
$email = $check_auth['email'];

$user = get_user($sub);
$userid = $user;
$con = get_con();
$conid = $con['id'];

$transid = sql_safe($_GET['id']);

$totalPrice = 0;
$badgeQ = <<<EOS
SELECT DISTINCT R.id, M.label, R.price, R.paid, P.badge_name
FROM atcon A
JOIN atcon_badge B ON (B.atconId = A.id and action='attach')
JOIN reg R ON(R.id = B.badgeId)
JOIN memLabel as M ON M.id=R.memId
JOIN perinfo as P on P.id=R.perid
WHERE A.transid = ?;
EOS;

$badgeRes = dbSafeQuery($badgeQ, 'i', array($transid));
$badges = array();
if($badgeRes) {
  while($badge = fetch_safe_assoc($badgeRes)) {
    $totalPrice += $badge['price']-$badge['paid'];
    array_push($badges, $badge);
  }
} else { $resp["error"].="No Badges!<br/>"; }
$response['price'] = $totalPrice;
$response['badges'] = $badges;


$totalPaid = 0;
$paymentRes = dbSafeQuery("SELECT amount FROM payments WHERE transid=?", 'i', array($transid));
if($paymentRes) {
  while($payment = fetch_safe_array($paymentRes)) {
    $totalPaid += $payment[0];
  }
}
$response['paid'] = $totalPaid;

if($totalPrice < $totalPaid) {
  $response["error"] = "Over Payment by $".($totalPaid-$totalPrice)."<br/>";
}

if($totalPrice <= $totalPaid) {
  $query0 = "UPDATE transaction SET price=?, paid=?, complete_date=current_timestamp(), userid=? WHERE id=?;";
  $query1 = <<<EOS
UPDATE reg as R
JOIN atcon_badge B ON (R.id=B.badgeId)
JOIN atcon A ON (A.id = B.atconId)
SET R.paid=R.price WHERE A.transid=?;
EOS;

  dbSafeCmd($query0, 'ddii', array($totalPrice, $totalPaid, $userid, $transid));
  dbSafeCmd($query1, 'i', array($transid));
  $response['success']='true';
}

ajaxSuccess($response);
?>
