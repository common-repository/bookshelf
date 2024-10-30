<?php
/*
 * Paypal IPN script
 */

$req = 'cmd=_notify-validate';

foreach( $_POST as $key=>$value ) {
	$value = urlencode( stripslashes( $value ) );
	$req .= "&$key=$value";
}

$header .= "POST /cgi-bin/webscr HTTP/1.0\r\n";
$header .= "Content-Type: application/x-www-form-urlencode\r\n";
$header .= "Content-Length: ".strlen($req)."\r\n\r\n";

$fp = fsockopen("www.paypal.com", 80, $errno ,$errstr ,30);

$item_name           = $_POST['item_name'];
$item_number         = $_POST['item_number'];
$payment_amount      = $_POST['mc_gross'];
$payment_status      = $_POST['payment_status'];
$payment_currency    = $_POST['mc_currency'];
$txn_id              = $_POST['txn_id'];
$txn_type            = $_POST['txn_type'];
$receiver_email      = $_POST['receiver_email'];
$first_name          = $_POST['first_name'];
$last_name           = $_POST['last_name'];
$payer_email         = $_POST['payer_email'];
$residence_country   = $_POST['residence_country'];
$date                = $_POST['payment_date'];

global $information, $transaction;

$information = array();
$information['item_number'] = $item_number;
$information['name'] = $first_name.' '.$last_name;
$information['email'] = $payer_email;
$information['country'] = $residence_country;
$information['date'] = $date;
$information['amount'] = $payment_currency." ".$payment_amount;
$information['currency'] = $payment_currency;

$transaction = array();
$transaction['Book'] = $item_name;
$transaction['Amount'] = $payment_amount;
$transaction['Currency'] = $payment_currency;
$transaction['Buyer'] = $information['name'];
$transaction['E-mail'] = $information['email'];
$transaction['Transaction ID'] = $txn_id;
$transaction['Status'] = $payment_status;

if( !$fp ) {
	// Unable to contact paypal
	log_bookshelf_invoice( 'HTTP/Firewall error', $txn_id, $payer_email );
} else {
	fputs( $fp, $header . $req );
	while( !feof($fp) ) {
		$res[] = fgets($fp, 1024);
	}
	fclose( $fp );

	if( in_array( 'VERIFIED', $res ) && isUniquetxnID( $txn_id ) ) {
		if( $payment_status == 'Completed' ) {
			// Only log completed transactions
			log_bookshelf_invoice( 'completed', $txn_id, $payer_email );
		}

		send_bookshelf_invoice( $payer_email, $receiver_email, strtolower( $payment_status ) );
	} else {
		send_bookshelf_invoice( $payer_email, $receiver_email, 'Invalid' );
	}
}

/*
 * Helper functions
 *
 */

function isUniquetxnID( $txn_id ) {
	if( !$txn_id )
		return false;

	global $wpdb;
	$_txn_id = $wpdb->get_var( $wpdb->prepare( 'SELECT txn_ID from ' .BOOKSHELF_SALES_TABLE. ' WHERE txn_id = %s AND status="completed"', $txn_id ) );

	return ( empty( $_txn_id ) ) ? true : false;
}

function log_bookshelf_invoice( $status, $txn_id ) {
	if( !$status || !$txn_id )
		return false;

	global $wpdb, $information;

	$wpdb->insert( BOOKSHELF_SALES_TABLE, array( 'txn_id' => $txn_id, 'status' => $status, 'details' => serialize( $information ) ), array( '%s', '%s', '%s' ) );
}

function send_bookshelf_invoice( $buyer, $seller, $status ) {
	global $transaction;
	$custom_message = get_option('bookshelf_order_'.$status.'_message');
	$subject = get_option('bookshelf_order_'.$status.'_subject');

	if( !$subject )
		$subject = 'Billing details';

	$message = '';
	$message = $custom_message;
	$message .= "\n-----------------------------------------------------------------------------------------------------------\n";
	$message .= "\t\t Billing Information\t\t\n";
	$message .= _build_transaction_details( $transaction );
	$message .= "\n-----------------------------------------------------------------------------------------------------------\n";

	$message2 = $message;

	$message = 'Download link for your purchase: ' .get_bloginfo('url'). '?download='. rawurlencode( $transaction['Transaction ID'] ) . " \n" . $message;

	$message2 = 'If the payment satus is not complete, you must send the user book manually confirming his payment details.' . $message2;

	$headers = 'MIME-Version: 1.0' . "\r\n" . 'Content-Type: text/plain; charset=UTF-8' . "\r\n";
	$headers .= "From: billing@".$_SERVER['SERVER_NAME'];

	wp_mail( $buyer, $subject, $message, $headers );
	wp_mail( $seller, 'Bookshelf billing copy - Payment status: '.$status , $message2, $headers );
}

function _build_transaction_details( $transaction ) {
	$str = '';

	foreach( $transaction as $key => $value ) {
		$str .= $key .' : '. $value . "\n";
	}
	return $str;
}

?>
