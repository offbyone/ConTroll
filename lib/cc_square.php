<?php
//  square.php - library of modules to add the square php payment API to onlinereg
// uses config variables:
// [cc]
// type=square - selects that reg is to use square for credit cards
// appid=[APPID] - appliction ID from the square developer portal, be it sandbox or production
// token=[TOKEN] - auth token from the square developer portal
// location=[LOCATION] - location id from the square developer portal
// does not currently use any other config sections for credit card other than [cc]


// draw_cc_html - exposed function to draw the credit card HTML window
//      $cc = array of [cc] section of ini file
//      $postal_code = postal code to default for form, optional
//

function draw_cc_html($cc, $postal_code = "--") {
?>
  <script src="<?php echo $cc['webpaysdk']; ?>"></script>
<!-- Configure the Web Payments SDK and Card payment method -->
  <script type="text/javascript">
      ;
      var payments = null;
    
      async function startCCPay() {
          const appId = '<?php echo $cc['appid']; ?>';
          const locationId = '<?php echo $cc['location']; ?>';
          const payments = Square.payments(appId, locationId);
          const card = await payments.card({
              <?php 
    if ($postal_code != "--") { 
        echo "'postalCode': '$postal_code',\n";
    }
              ?>           
              "style": {
                  ".input-container": {
                      "borderColor": "blue",
                      "borderWidth": "2px",
                      "borderRadius": "12px",
                  },
                  "input": {
                      "color": "blue",
                      "fontSize": '12px',
                  },
                  "@media screen and (max-width: 600px)": {
                      "input": {
                          "fontSize": "16px",
                      }
                  }
              }
          });
          document.getElementById("card-button").removeAttribute("hidden");
          await card.attach('#card-container');

          async function eventHandler(event) {
              event.preventDefault();

              try {
                  const result = await card.tokenize();
                  if (result.status === 'OK') {
                      //console.log(`Payment token is ${result.token}`);
                      makePurchase(result.token, "card-button");
                  }
              } catch (e) {
                  console.error(e);
              }
          };
          const cardButton = document.getElementById('card-button');
          cardButton.addEventListener('click', eventHandler);
      }

      document.addEventListener('DOMContentLoaded', async function () {
         if (!window.Square) {
            throw new Error('Square.js failed to load properly');
          }    
          
          startCCPay();
      });
  </script>
    <form id="payment-form">
        <div class="container-fluid overflow-hidden" id="card-container"></div>
        <button id="card-button" type="button">Purchase</button>
    </form>
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

use Square\SquareClient;
use Square\Exceptions\ApiException;
use Square\Http\ApiResponse;
use Square\Models\CreateOrderRequest;
use Square\Models\CreateOrderResponse;
use Square\Models\Order;
use Square\Models\OrderSource;
use Square\Models\OrderLineItem;
use Square\Models\Currency;
use Square\Models\Money;
use Square\Models\CreatePaymentRequest;
use Square\Models\CreatePaymentResponse;


function cc_charge_purchase($results, $ccauth) {
    $cc = get_conf('cc');
    $con = get_conf('con');
    $client = new SquareClient([
        'accessToken' => $cc['token'],
        'squareVersion' => '2022-02-16',
        'environment' => $cc['env'],
    ]);

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