<?php
global $db_ini;

require_once '../lib/base.php';
$check_auth = google_init('ajax');
$perm = 'vendor';

$response = array('post' => $_POST, 'get' => $_GET, 'perm' => $perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

if (!(array_key_exists('portalType', $_POST) && array_key_exists('portalname', $_POST))) {
    $response['error'] = 'Calling Sequence Error';
    ajaxSuccess($response);
    exit();
}

$con = get_con();
$conid = $con['id'];


// get all exhibitors that are not set up for this year

$pastQ = <<<EOS
WITH maxcid AS (
    SELECT max(conid) as maxConid, exhibitorId
    FROM exhibitorYears
    GROUP BY exhibitorId
)
SELECT e.*, ey.*
FROM exhibitors e
LEFT OUTER JOIN maxcid ON e.id = maxcid.exhibitorId
LEFT OUTER JOIN exhibitorYears ey ON e.id = ey.exhibitorId AND maxcid.maxConid = ey.conid
LEFT OUTER JOIN exhibitorYears cey ON e.id = cey.exhibitorId and cey.conid = ?
WHERE cey.id IS NULL;
EOS;

$pastR = dbSafeQuery($pastQ,'i',array($conid));
$past = array(); // forward array, id -> data

while ($pastL = $pastR->fetch_assoc()) {
    $past[] = $pastL;
}
$pastR->free();

$respomnse['past'] = past;
ajaxSuccess($response);
?>
