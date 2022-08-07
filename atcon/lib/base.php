<?php
session_start();
## Pull INI for variables
$ini = parse_ini_file(__DIR__ . "/../../config/reg_conf.ini", true);
date_default_timezone_set("America/New_York");

function get_conf($type) {
    global $ini;
    return array_key_exists($type, $ini) ? $ini[$type] : null;
}

function isWebRequest()
{
return isset($_SERVER) && isset($_SERVER['HTTP_USER_AGENT']);
}

function ageDialog($con)
{
    $ageListR = callHome("ageList.php", "POST", "con=" . $con['id']);
    $ageList = json_decode($ageListR,true);
    //echo "<div>"; var_dump($ageList) ; echo "</div>";
?>
  <script>
    var prices = { 
        <?php foreach($ageList as $age) {
            echo $age['memAge'] . " : " . $age['price'] . ", ";
        } ?> 
        all : 0}
  </script>
  <div id='getAge' class='dialog'>
    <form id='getAgeForm' action='javascript:void(0);'>
        <input type='hidden' id='getAgeBadgeId'></input>
        <input type='hidden' id='getAgeBadgeWhich'></input>
        <input type='hidden' id='getAgeAction'></input>
        <select id='getAgeSelect'>
            <?php foreach($ageList as $age) {
                echo "<option value='" . $age['memAge'] . "'>" 
                    . $age['label'] . " ($" . $age['price'] . ")</option>\n";
            } ?>
        </select>
        <input type='submit' id='getAgeSubmit' value='Set Age' 
            onClick='addBadgeAddon($("#getAgeAction").val(),
                                   $("#getAgeBadgeId").val(), 
                                   $("#getAgeBadgeWhich").val(),
                                   $("#getAgeSelect").val(), true); 
                     $("#getAge").dialog("close");
                     return false;'></input>
    </form>
  </div>
<?php }

function page_init($title, $css, $js) {
    $con = get_conf('con');
    $label = $con['label'];
    if(isWebRequest()) { 
    ?>
<!doctype html>
<html>
<head>
    <title><?php echo $title . ' -- ' . $label; ?> Reg</title>
    <?php
    if(isset($css) && $css != null) { foreach ($css as $sheet) {
        ?><link href='<?php echo $sheet; ?>' 
                rel=stylesheet type='text/css' /><?php
    }}
    if(isset($js) && $js != null) { foreach ($js as $script) {
        ?><script src='<?php echo $script; ?>' 
                type='text/javascript'></script><?php
    }}
    ?>
</head>
<body>
    <?php
    page_head($title);
    //con_info();
    ?>
    <table> <tr>
        <td><a href='checkin.php'>Reg Check In</a></td>
        <td><a href='register.php'>Reg Cashier</a></td>
<?php // <td><a href='artsales.php'>Artshow Cashier</a></td> ?>
	<td><a href='printform.php'>Printform</a></td>
        <td><a href='admin.php'>Administration<a/></td>
    </tr></table>
  <?php
  } else {
    page_head($title);
  }
}

function page_head($title) {
    $con=get_conf('con');
    $label = $con['label'];
?>
    <div id='titlebar'>
        <h1 class='title'>
	    <?php echo $label; ?> Registration <?php echo $title; ?> page
        </h1>
    </div>
<?php
}

function con_info() {
    $con = get_conf("con");
##        $count_res = dbQuery("select count(*) from reg where conid='".$con['id']."';");
##        $badgeCount = fetch_safe_array($count_res);
##        $count_res = dbQuery("select count(*) from reg where conid='".$con['id']."' AND locked='N';");
##        $unlockCount = fetch_safe_array($count_res);
  
        ?>
    <div id='regInfo'>
        <span id='regInfoCon' class='left'>
        Con: <span class='blocktitle'> <?php echo $con['label']; ?> </span>
        <small><?php echo "con_info() doesn't work yet"; 
##           echo $badgeCount[0] . " Badges (" . 
##                $unlockCount[0] . " Ready)";
        ?></small>
        </span>
    </div>
        <?php
}

function callHome($script, $method, $data) {
#print("<br/>" . $script ." :" . strtoupper($method) . " :'". $data . "'<br/>");
    $access = get_conf('user');
    $url = $access['server'] . "/" . $script;
    #error_log($url);
#print("<br/>server: " . $url . "<br/>");
    $ch = curl_init($url);
    
    curl_setopt($ch, CURLOPT_USERPWD, $access['user'] . ":" . $access['passwd']);
    if(strtoupper($method)=='POST') {
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Length: ' . strlen($data))
    );     

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);

    $result = curl_exec($ch);
    if($result === false) {
        print("CURL Error: " . curl_error($ch) . " (" . curl_errno($ch) . ")<br>");
    }

    curl_close($ch);

    return($result);
}

function page_foot($title) {
    ?>
</body>
</html>
<?php
}

function paymentDialogs() {
  $con = get_conf('con');
  $taxRate = array_key_exists('taxRate', $con) ? $con['taxRate'] : 0;
  ?>
  <script>
    $(function() {
    $('#getAge').dialog({
        autoOpen: false,
        width: 400,
        height: 310,
        title: "Set Age"
    })
    $('#cashPayment').dialog({
      autoOpen: false,
      width: 325,
      height: 310,
      modal: true,
      title: "Cash Payment Window"
    })
    $('#offline').dialog({
      autoOpen: false,
      width: 325,
      height: 310,
      modal: true,
      title: "Credit Card Payment Window"
    })
    $('#creditPayment').dialog({
      autoOpen: false,
      width: 325,
      height: 310,
      modal: true,
      title: "Creditcard Payment Window"
    })
    $('#checkPayment').dialog({
      autoOpen: false,
      width: 325,
      height: 310,
      modal: true,
      title: "Check Payment Window"
    });
    $('#discountPayment').dialog({
      autoOpen: false,
      width: 325,
      height: 310,
      modal: true,
      title: "Discount Window"
    });
    $('#signature').dialog({
      autoOpen: false,
      width: 300,
      height: 500,
      modal: true,
      title: "Receipt"
    });
    $('#receipt').dialog({
      autoOpen: false,
      width: 300,
      height: 500,
      modal: true,
      title: "Receipt"
    });
    });
  </script>
  <style>
    ui-dialog { padding: .3em; }
  </style>
  <?php ageDialog($con); ?>
  <div id='signature' class='dialog'>
    <div id='signatureHolder'>
    </div>
    <button id='signaturePrint' class='bigButton' 
        onclick='checkSignature($("#signatureHolder").data("transid"),
                                $("#signatureHolder").data("payment"),
                                false);'>
        Reprint Signature Form
    </button>
    <button id='signatureComplete' class='bigButton' 
        onclick='checkReceipt($("#signatureHolder").data("transid"));
                 $("#signature").dialog("close");'>
        Print Receipt
    </button>
  </div>
  <div id='receipt' class='dialog'>
    <div id='receiptHolder'>
    </div>
    <button id='receiptPrint' class='bigButton' 
        onclick='checkReceipt($("#receiptHolder").data("transid"));'>
        Reprint Receipt
    </button>
    <button id='receiptComplete' class='bigButton' 
        onclick='completeTransaction("transactionForm");
                 $("#receipt").dialog("close");'>
        Complete Transaction
        </button>
  </div>
  <div id='discountPayment' class='dialog'>
    <form id='discountPaymentForm' action='javascript:void(0);'>
      TransactionID: <span id='discountTransactionId'></span>
      <hr/>
      <table class='center'>
        <tr>
          <td>SubTotal</td>
          <td width=50></td>
          <td id='discountPaymentSub' class='right' ></td>
        </tr>
        <tr style='border-bottom: 1px solid black;'>
          <td>+ <?php echo $taxRate; ?>% Tax</td>
          <td width=50></td>
          <td id='discountPaymentTax' class='right'></td>
        </tr>
        <tr>
          <td>Total</td>
          <td width=50></td>
          <td id='discountPaymentTotal' class='right'></td>
        </tr>
      </table>
      <div>
        <input required='required' class='right' type='text' size=10 name='amt' id='discountAmt'></input>Amount
      </div>
      <div>
        <input required='required' class='right' type='text' size=20 name='notes' id='discountDesc'></input>Note
      </div>
      <input id='discountPay' class='payBtn' type='submit' value='Pay' onClick='testValid("#discountPaymentForm") && makePayment("discount");'></input>
    </form>
  </div>
  <div id='checkPayment' class='dialog'>
    <form id='checkPaymentForm' action='javascript:void(0);'>
      TransactionID: <span id='checkTransactionId'></span>
      <hr/>
      <table class='center'>
        <tr>
          <td>SubTotal</td>
          <td width=50></td>
          <td id='checkPaymentSub' class='right' ></td>
        </tr>
        <tr style='border-bottom: 1px solid black;'>
          <td>+ <?php echo $taxRate; ?>% Tax</td>
          <td width=50></td>
          <td id='checkPaymentTax' class='right'></td>
        </tr>
        <tr>
          <td>Total</td>
          <td width=50></td>
          <td id='checkPaymentTotal' class='right'></td>
        </tr>
      </table>
      <div><input required='required' class='right' type='text' size=10 id='checkNo'></input>
      Check #</div>
      <div>
        <input required='required' class='right' type='text' size=10 name='amt' id='checkAmt'></input>Amount
      </div>
      <div>
        <input class='right' type='text' size=20 name='notes' id='checkDesc'></input>Note
      </div>
      <input id='checkPay' class='payBtn' type='submit' value='Pay' onClick='testValid("#checkPaymentForm") && makePayment("check");'></input>
    </form>
  </div>
  <div id='cashPayment' class='dialog'>
    <form id='cashPaymentForm' action='javascript:void(0);'>
      TransactionID: <span id='cashTransactionId'></span>
      <hr/>
      <table class='center'>
        <tr>
          <td>SubTotal</td>
          <td width=50></td>
          <td id='cashPaymentSub' class='right' ></td>
        </tr>
        <tr style='border-bottom: 1px solid black;'>
          <td>+ <?php echo $taxRate; ?>% Tax</td>
          <td width=50></td>
          <td id='cashPaymentTax' class='right'></td>
        </tr>
        <tr>
          <td>Total</td>
          <td width=50></td>
          <td id='cashPaymentTotal' class='right'></td>
        </tr>
      </table>
      <div>
        <input required='required' class='right' type='text' size=10 name='amt' id='cashAmt'></input>Amount
      </div>
      <div>
        <input class='right' type='text' size=20 name='notes' id='cashDesc'></input>Note
      </div>
      <input id='cashPay' class='payBtn' type='submit' value='Pay' onClick='testValid("#cashPaymentForm") && makePayment("cash");'></input>
    </form>
  </div>
  <div id='offline' class='dialog'>
    <form id='offlinePaymentForm' action='javascript:void(0);'>
      TransactionID: <span id='creditTransactionId'></span>
      <hr/>
      <table class='center'>
        <tr>
          <td>SubTotal</td>
          <td width=50></td>
          <td id='offlinePaymentSub' class='right' ></td>
        </tr>
        <tr style='border-bottom: 1px solid black;'>
          <td>+ <?php echo $taxRate; ?>% Tax</td>
          <td width=50></td>
          <td id='offlinePaymentTax' class='right'></td>
        </tr>
        <tr>
          <td>Total</td>
          <td width=50></td>
          <td id='offlinePaymentTotal' class='right'></td>
        </tr>
      </table>
      <input type='hidden' name='amt' id='offlineAmt'></input>
      <div><input disabled='disabled' class='right' type='text' size=10 name='view' id='offlineView'></input>Amount</div>
      <div><input required='optional' class='right' type='text' size=10 name='cc_approval_code' id='offlineCode' autocomplete='off'></input>Approval Code</div>
      <input id='offlinePay' class='payBtn' type='submit' value='Pay' onClick='testValid("#offlinePaymentForm") && makePayment("offline");'></input>
      </div>
  </div>
  <div id='creditPayment' class='dialog'>
    <form id='creditPaymentForm' action='javascript:void(0);'>
      TransactionID: <span id='creditTransactionId'></span>
      <hr/>
      <table class='center'>
        <tr>
          <td>SubTotal</td>
          <td width=50></td>
          <td id='creditPaymentSub' class='right' ></td>
        </tr>
        <tr style='border-bottom: 1px solid black;'>
          <td>+ <?php echo $taxRate; ?>% Tax</td>
          <td width=50></td>
          <td id='creditPaymentTax' class='right'></td>
        </tr>
        <tr>
          <td>Total</td>
          <td width=50></td>
          <td id='creditPaymentTotal' class='right'></td>
        </tr>
      </table>
      <input type='hidden' name='amt' id='creditAmt'></input>
      <div><input disabled='disabled' class='right' type='text' size=10 name='view' id='creditView'></input>Amount</div>
      <div><input required='required' class='right' type='password' size=4 name='track' id='creditTrack' autocomplete='off'></input>CC</div>
      <input id='creditPay' class='payBtn' type='submit' value='Pay' onClick='testValid("#creditPaymentForm") && makePayment("credit");'></input>
      </div>
    </form>
  </div>
<?php
}

$perms = array();
function check_atcon($user, $passwd, $method) {
    global $perms;
    if(isset($_SESSION['user']) && $user==$_SESSION['user'] &&
        isset($_SESSION['passwd']) && $passwd==$_SESSION['passwd'] &&
        in_array($method, $perms)) { return true; }
    
    #error_log($user); error_log($passwd); error_log($method);
    $access = callHome("login.php", "POST", "user=".$user."&passwd=".$passwd);
    #var_error_log($access);
    $access = json_decode($access,true);
    #echo var_dump($access);
    if($access === false || $access['success']==0) { return false; }
    $perms = $access['auth'];
    return in_array($method, $perms);
}

function initReceipt() {
  $con = get_conf('con');
  $width = 30;
  $pad = floor($width/2 + strlen("Receipt")/2);
  $return = "\n" . sprintf("%${pad}s", "Receipt") . "\n";
  $pad = floor($width/2 + strlen($con['label'])/2);
  $return = "\n" . sprintf("%${pad}s", $con['label']) . "\n";

  date_default_timezone_set("America/New_York");
  $date = date("M j, Y H:m:s");
  $pad =  floor($width/2 + strlen($date)/2);
  $return .= sprintf("%${pad}s", $date) . "\n";

  $return .= "\n" . str_repeat('-',$width) . "\n";

    return $return;
}

function closeReceipt($info) {
  $width=30;

  $type=$info['type'];
  $sub = $info['price'];
  $tax = $info['tax'];
  $total=$info['withtax'];
  $amt = $info['amount'];
  $change = $info['change_due'];
  $desc = $info['description'];
  $cc_num = $info['cc'];
  $cc_code = $info['cc_approval_code'];

  $return = "\n";
  if($sub>0) {
  $subStr = sprintf("%01.2f", $sub);
  $pad = $width - strlen("Subtotal:");
  $return .= "Subtotal:" . sprintf("%${pad}s",$subStr) . "\n";

  $subTax = sprintf("%01.2f", $tax);
  $pad = $width - strlen("+ Tax:");
  $return .= "+ Tax:" . sprintf("%${pad}s",$subTax) . "\n";
  }

  $subTotal = sprintf("%01.2f", $total);
  $pad = $width - strlen("Total:");
  $return .= "Total:" . sprintf("%${pad}s",$subTotal) . "\n";

  $subAmt = sprintf("%01.2f", $amt);
  $pad = $width - strlen("Payment Received: $type");
  $return .= "Payment Received" . sprintf("%${pad}s",$subAmt) . "\n";
  if($type == "check") {
    $return .= "  - Check #$desc\n";
  }
  if($type == "credit") {
    $return .= "  - Card: " . $cc_num . "\n";
    $return .= "  - Auth: " . $cc_code . "\n";
  }
  $return .= "\n";

  $subCng = sprintf("%01.2f", $change);
  $pad = $width - strlen("Change:");
  $return .= "Change:" . sprintf("%${pad}s",$subCng) . "\n";
  $return .= "\n\n\n\n\n";

  return $return;
}

function passwdForm() {
?>
    <div id='passwordWrap'>
        <button id='logout' class='right' 
            onclick='window.location.href=window.location.pathname+"?action=logout"'>
           Logout</input>
        </button>
        <span class='blocktitle' onclick='$("#chpw").toggle()'>Change Password</span>
        <div id='chpw'>
            <form action='javascript:void(0)' id='chpwForm'>
                Current Password: <input type='password' name='passwd'>
                                  </input><br/>
                New Password: <input type='password' name='newpasswd' id='newpw1'>
                              </input><br/>
                New PW again: <input type='password' id='newpw2'></input><br/>
                <input type='submit' onclick='pw_script("#chpwForm");'></input>
            </form>
        </div>
    </div>
<?php
}

// Function var_error_log()
// $object = object to be dumped to the PHP error log
// the object is walked and written to the PHP error log using var_dump and a redirect of the output buffer.
function var_error_log( $object=null ){
    ob_start();                    // start buffer capture
    var_dump( $object );           // dump the values
    $contents = ob_get_contents(); // put the buffer into a variable
    ob_end_clean();                // end capture
    error_log( $contents );        // log contents of the result of var_dump( $object )
}
?>