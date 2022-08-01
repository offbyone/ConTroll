<?php
//  test.php - library of modules to insert a stub payment mechanism
// uses config variables:
// [cc]
// env="sandbox" or it will fail
// [reg]
// test=1 or it will fail
// 
// 

// draw_cc_html - exposed function to draw the credit card HTML window
//      $cc = array of [cc] section of ini file
//      $postal_code = postal code to default for form, optional
//

function draw_cc_html($cc, $postal_code = "--") {
?>
<p>This is a test site, it doesn't really take credit cards</p>
Scenario: <select name='ccnum'>
	<option value=1>1 - Success</option>
	<option value=2>2 - Failure</option>
</select>
<input type="submit" id="purchase" onclick="makePurchase()" value="Purchase">
<?php
};

function guidv4($data = null) {
    // Generate 16 bytes (128 bits) of random data or use the data passed into the function.
    $data = $data ?? random_bytes(16);
    assert(strlen($data) == 16);

    // Set version to 0100
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    // Set bits 6-7 to 10
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    // Output the 36 character UUID.
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function cc_charge_purchase($results, $ccauth) {
    $cc = get_conf('cc');
    $con = get_conf('con');
    $reg = get_conf('reg');

    if(!isset($_POST['ccnum'])) {
		ajaxSuccess(array('status'=>'error','data'=>'missing CC information'));
		exit();
	}

	if(($cc['env'] != 'sandbox') || $reg['test'] != 1) {
		ajaxSuccess(array('status'=>'error','data'=>'Something thinks this is a real charge method'));
		exit();
	}
	switch($_POST['ccnum'][0]) {
		case '1': // success
			$rtn['amount'] = $results['total'];
			$rtn['txnfields'] =  array('transid','type','category','description', 'source','amount', 'txn_time', 'cc','cc_txn_id','cc_approval_code','receipt_id');
			$rtn['tnxtypes'] = array('i', 's', 's', 's', 's', 'd', 's', 's', 's', 's', 's');
			$rtn['tnxdata'] = array($results['transid'],'credit','reg', 'test registration', 'online', $results['total'], '00-00-00 00:00:00',$_POST['ccnum'][0],'txn id','000000','txn_id');
			return $rtn;
			break;
		default: 
			ajaxSuccess(array('status'=>'error','data'=>'bad CC number'));
			exit();
	}


    // square api steps
    // 1. create order record and price it
    //  a. create order top level container
    //  b. add line items
    //  c. pass order to order end point and get order id
    // 2. create payment
    //  a. create payment object with order id and payment amount plus credit card nonce
    //  b. pass payment to payment processor
    // 3. parse return results to return the proper information
    // failure fall through

    // base order
    $body = new CreateOrderRequest;
    $body->setIdempotencyKey(guidv4());
    $body->setOrder(new Order($cc['location']));
    $order = $body->getOrder();
    $order->setLocationId( $cc['location']);
    $order->setReferenceId($con['id'] . '-' . $results['transid']);
    $order->setSource(new OrderSource);
    $order->getSource()->setName($con['conname'] . 'OnLineReg');

    $custbadge = $results['badges'][0];
    $order->setCustomerId($con['id'] . '-' . $custbadge['badge']);
    $order_lineitems = [];

    // add order lines
    $lineid = 0;
    foreach ($results['badges'] as $badge) {
        $item = new OrderLineItem ('1');
        $item->setUid('badge' . ($lineid + 1));
        $item->setName($badge['age'] . ' Badge for ' .  trim($badge['fname'] . ' ' . $badge['mname']  . ' ' . $badge['lname']));
        $item->setBasePriceMoney(new Money);
        $item->getBasePriceMoney()->setAmount($badge['price'] * 100);
        $item->getBasePriceMoney()->setCurrency(Currency::USD);
        $order_lineitems[$lineid] = $item;
        $lineid++;
    }
    $order->setLineItems($order_lineitems);

    // pass order to square and get order id

    try {
        $ordersApi = $client->getOrdersApi();
        $apiResponse = $ordersApi->createOrder($body);
        if ($apiResponse->isSuccess()) {
            $crerateorderresponse = $apiResponse->getResult();
            //error_log("order: success");
            //var_error_log($crerateorderresponse);
        } else {
            $error = $apiResponse->getErrors()[0];
            error_log("Order returned non-success");
            var_error_log($error);
            ajaxSuccess(array('status'=>'error','data'=>"Order Error: " . $error->getCategory() . "/" . $error->getCode() . ": " . $error->getDetail() . "[" . $error->getField() . "]"));
            exit();
        }
    } catch (ApiException $e) {
        error_log("Order received error while calling Square: " . $e->getMessage());
        ajaxSuccess(array('status'=>'error','data'=>"Error: Error connecting to Square"));
        exit();
    }

    $corder = $crerateorderresponse->getOrder();

    $payuuid = guidv4();
    $pay_money = new Money;
    $pay_money->setAmount($results['total'] * 100);
    $pay_money->setCurrency(Currency::USD);

    $pbody = new CreatePaymentRequest(
        $results['nonce'],
        $payuuid,
        $pay_money
    );
    $pbody->setAutocomplete(true);
    $pbody->setOrderID($corder->getId());
    $pbody->setCustomerId($con['id'] . '-' . $custbadge['badge']);
    $pbody->setLocationId($cc['location']);
    $pbody->setReferenceId($con['id'] . '-' . $results['transid']);
    $pbody->setNote('On-Line Registration');

    try {
        $paymentsApi = $client->getPaymentsApi();
        $apiResponse = $paymentsApi->createPayment($pbody);

        if ($apiResponse->isSuccess()) {
            $createPaymentResponse = $apiResponse->getResult();
            //error_log("payment: success");
            //var_error_log($createPaymentResponse);
        } else {
            $error = $apiResponse->getErrors()[0];
            error_log("Payment returned non-success");
            var_error_log($error);
            ajaxSuccess(array('status'=>'error','data'=>"Payment Error: " . $error->getCategory() . "/" . $error->getCode() . ": " . $error->getDetail() . "[" . $error->getField() . "]"));
            exit();
        }
    } catch (ApiException $e) {
        error_log("Payment received error while calling Square: " . $e->getMessage());
        ajaxSuccess(array('status'=>'error','data'=>"Error: Error connecting to Square"));
        exit();
    }

    $payment = $createPaymentResponse->getPayment();
    $id = $payment->getId();
    $approved_amt = ($payment->getApprovedMoney()->getAmount()) / 100;
    $status = $payment->getStatus();
    $last4 = $payment->getCardDetails()->getCard()->getLast4();
    $receipt_url = $payment->getReceiptUrl();
    $auth = $payment->getCardDetails()->getAuthResultCode();
    $desc = 'Square: ' . $payment->getApplicationDetails()->getSquareProduct();
    $txtime = $payment->getCreatedAt();
    $receipt_number = $payment->getReceiptNumber();

    $rtn = array();
    $rtn['amount'] = $approved_amt;
    $rtn['txnfields'] = array('transid','type','category','description','source','amount',
        'txn_time', 'cc','nonce','cc_txn_id','cc_approval_code','receipt_url','status','receipt_id');
    $rtn['tnxtypes'] = array('i', 's', 's', 's', 's', 'd',
            's', 's', 's', 's', 's', 's', 's', 's');
    $rtn['tnxdata'] = array($results['transid'],'credit','reg',$desc,'online',$approved_amt,
        $txtime,$last4,$results['nonce'],$id,$auth,$receipt_url,$status,$receipt_number);
    $rtn['url'] = $receipt_url;
    $rtn['rid'] = $receipt_number;
    return $rtn;
};
?>
