<?php
/*
 * Misc functions or filters
 */

function bookshelf_buy_button( $content ) {
	global $post;

	$price = get_post_meta( $post->ID, 'price', true );
	$pdf_file = get_post_meta( $post->ID, '_bookshelf_pdf_file', true );
	$currency = get_option( 'bookshelf_currency' );

	// Reversed the logic from previous version, this one sounds faster
	if( !$price || !file_exists(BOOKSHELF_UPLOAD_DIR.'/'.$pdf_file) || !$pdf_file || !$currency )
		return $content;

	$paypal_form = '<form action="https://www.paypal.com/cgi-bin/webscr" method="post">';
	$paypal_form .= '<input type="hidden" name="cmd" value="_xclick" />';
	$paypal_form .= '<input type="hidden" name="item_name" value="'.$post->post_title.'" />';
	$paypal_form .= '<input type="hidden" name="item_number" value="'.$post->ID.'" />';
	$paypal_form .= '<input type="hidden" name="amount" value="'.sprintf('%.2f',$price).'" />';
	$paypal_form .= '<input type="hidden" name="currency_code" value="'.$currency.'" />';
	$paypal_form .= '<input type="hidden" name="business" value="'.get_option('bookshelf_paypal_email').'" />';
	$paypal_form .= '<input type="hidden" name="no_shipping" value="1" />';
	$paypal_form .= '<input type="hidden" name="notify_url" value="'.get_bloginfo('url').'/?_bookshelf=ipn" />';
	$paypal_form .= '<input type="hidden" name="cancel_return" value="'.get_bloginfo('url').'/?_bookshelf=cancel" />';
	$paypal_form .= '<input type="hidden" name="return" value="'.get_bloginfo('url').'/?_bookshelf=success" />';
	$paypal_form .= '<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_buynowCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">';
	$paypal_form .= '<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">';
	$paypal_form .= '</form>';

	return $content.$paypal_form;
}

add_filter( 'the_content', 'bookshelf_buy_button' );
add_filter( 'the_excerpt', 'bookshelf_buy_button' );

function bookshelf_title_price( $title ) {
	if( !in_the_loop() )
		return $title;

	global $post;
	$price = get_post_meta( $post->ID, 'price', true );
	if( get_option( 'bookshelf_display_price' ) && $price )
		return $title.' &mdash; '.get_option('bookshelf_currency')." ".sprintf('%.2f',$price);

	return $title;
}
add_filter( 'the_title', 'bookshelf_title_price' );
?>
