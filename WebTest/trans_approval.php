<?php

include_once('../ParamSigner.php');

$authenticatedParam = ParamSigner::paramAuthenticate($_GET, 'XYZ12345');
if (!$authenticatedParam) {
    die("Data tampering detected or offer expired.");
}
echo '<br><font color="red"><b>Your transaction has been approved.</b></font>';
echo '<br><b>Order ID: </b>' . $_REQUEST['order_id'];
echo '<br><b>Transaction ID: </b>' . $_REQUEST['trans_id'];
echo '<br><b>Transaction Type: </b>' . $_REQUEST['trans_type'];
echo '<br><b>Item ID: </b>' . $_REQUEST['item_id'];
echo '<br><b>Amount: </b>' . $_REQUEST['amount'];