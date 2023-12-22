<?php

include_once('../ParamSigner.php');

$authenticatedParam = ParamSigner::paramAuthenticate($_GET, 'XYZ12345');
if (!$authenticatedParam) {
    die("Data tampering detected or offer expired.");
}
echo '<br><font color="red"><b>Your transaction has been declined.</b></font>';
echo '<br><b>Transaction ID: </b>' . $_REQUEST['trans_id'];
echo '<br><b>Transaction Type: </b>' . $_REQUEST['trans_type'];
echo '<br><b>Amount: </b>' . $_REQUEST['amount'];