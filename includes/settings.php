<?php
/*
 * Bookshelf settings page
 */

function bookshelf_create_menu( ) {
	if( function_exists( 'add_object_page' ) )
		add_object_page('Bookshelf','Bookshelf','administrator','bookshelf-settings','bookshelf_settings_page');
	else
		add_menu_page('Bookshelf','Bookshelf','administrator','bookshelf-settings','bookshelf_settings_page');

	add_submenu_page('bookshelf-settings','Settings','Settings','administrator','bookshelf-settings','bookshelf_settings_page');
	add_submenu_page('bookshelf-settings','Sales','Sales','administrator','bookshelf-sales','bookshelf_sales');
	add_submenu_page('bookshelf-settings','Troubleshoot','Troubleshoot','administrator','bookshelf-troubleshoot','bookshelf_troubleshoot');
}
add_action( 'admin_menu', 'bookshelf_create_menu' );

function bookshelf_register_options( ) {
	register_setting('bookshelf-options','bookshelf_paypal_email');
	register_setting('bookshelf-options','bookshelf_display_price');

	register_setting('bookshelf-options','bookshelf_order_completed_message');
	register_setting('bookshelf-options','bookshelf_order_pending_message');

	register_setting('bookshelf-options','bookshelf_currency');

	register_setting('bookshelf-options','bookshelf_order_completed_subject');
	register_setting('bookshelf-options','bookshelf_order_pending_subject');
}
add_action('admin_init','bookshelf_register_options');

function bookshelf_troubleshoot() {
?>
	<div class="wrap">
		<h2>Bookshelf Troubleshoot</h2>
		<p class="howto">Enter the page/post ID to which Bookshelf is attached and click Run Tests.</p>
		<?php if( isset( $_POST['runtests'] ) ): ?>
			<div class="updated" id="message" style="font-weight: bold;">
				<?php
					$email = get_option( 'bookshelf_paypal_email' );
					$pageID = intval( $_POST['page_id'] );
					$page = get_post( $pageID );
					
					if( ! $email ) {
						echo '<p style="color: red; font-weight: bold;">PayPal email not found.</p>';
					} else {
						echo "<p><em>PayPal Email</em>: $email</p>";
					}
					
					if( ! $page ) {
						echo '<p style="color: red">Invalid page id</p>';
					} else {
						$price = get_post_meta( $pageID, 'price', true );
						$currency = get_option( 'bookshelf_currency' );
						if( ! $price ) {
							echo '<p style="color: red">Custom field price not found</p>';
						} else {
							echo "<p><em>Price:</em> $currency $price</p>";
						}
						
						$pdf_file = get_post_meta( $pageID, '_bookshelf_pdf_file', true );
						if( ! $pdf_file ) {
							echo '<p style="color: red">No file attached</p>';
						} else {
							echo "<p><em>Attached File:</em> $pdf_file</p>";
						}
					}
				?>
			</div>
		<?php endif; ?>
		<form method="post">			
			<p><input type="text" name="page_id" size="4" />
			<input type="hidden" name="runtests" value="all" />
			<input type="submit" value="Run Tests" class="button-primary" /></p>
		</form>
	</div>
<?php
}

function bookshelf_settings_page( ) {
?>
	<div class="wrap">
		<h2>Bookshelf settings</h2>
		<?php
			if(!file_exists(BOOKSHELF_UPLOAD_DIR.'/.htaccess') ) {
			?>
			<div class="error">.htaccess file does not exist. Please add the following code to .htaccess file in <?php echo BOOKSHELF_UPLOAD_DIR; ?> directory<br /><strong>deny from all</strong></div>
			<?php
			} else {
				$ht = file_get_contents(BOOKSHELF_UPLOAD_DIR.'/.htaccess');
				if( !preg_match( "/deny from all/", $ht ) ) {
				?>
				<div class="error">.htaccess files does not contain proper configuration.<br />Add the following line to your .htaccess file in <?php echo BOOKSHELF_UPLOAD_DIR ?> directory<br /><strong>deny from all</strong></div>
				<?php
				}
			}

			$currency_codes = array("AUD","CAD","CZK","DKK","EUR","HKD","HUF","ILS","JPY","MXN","NOK","NZD","PHP","PLN","GBP","SGD","SEK","CHF","TWD","THB","USD");
		?>
		<form method="post" action="options.php">
			<?php settings_fields('bookshelf-options'); ?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row">Paypal email address</th>
					<td><input type="text" name="bookshelf_paypal_email" value="<?php echo get_option('bookshelf_paypal_email'); ?>" /></td>
				</tr>

				<tr valign="top">
					<th scope="row">Currency</th>
					<td>
						<select name="bookshelf_currency">
						<?php
							$ccode = get_option('bookshelf_currency');
							foreach( $currency_codes as $c ) {
								$sel = '';
								if( $c == $ccode )
									$sel = 'selected="selected"';
								echo '<option value="' .$c. '" ' .$sel. '>' .$c. '</option>';
							}
						?>
						</select>
					</td>
				</tr>

				<tr>
					<th scope="row">Display price in title</th>
					<td><input type="checkbox" name="bookshelf_display_price" <?php if( get_option('bookshelf_display_price') ){ echo 'checked="checked"';} ?> /></td>
				</tr>

				<tr>
					<th scope="row">Successful order subject</th>
					<td><input type="text" name="bookshelf_order_completed_subject" value="<?php echo get_option('bookshelf_order_completed_subject'); ?>" /></td>
				</tr>

				<tr>
					<th scope="row">Pending order subject</th>
					<td><input type="text" name="bookshelf_order_pending_subject" value="<?php echo get_option('bookshelf_order_pending_subject'); ?>" /></td>
				</tr>

				<tr>
					<th scope="row">Successful order message</th>
					<td><textarea name="bookshelf_order_completed_message" cols="55" rows="5"><?php echo get_option('bookshelf_order_completed_message'); ?></textarea></td>
				</tr>

				<tr>
					<th scope="row">Pending order message</th>
					<td><textarea cols="55" rows="5" name="bookshelf_order_pending_message"><?php echo get_option('bookshelf_order_pending_message'); ?></textarea></td>
				</tr>

			</table>
			<p><input class="button-primary" type="submit" value="Submit" /></p>
		</form>
		<p>Developed by <a href="http://www.revood.com">Reuben Gunday, co-owner of revood.com</a></p>
	</div>
<?php
}

function bookshelf_check_config( ) {
	$message = '';
	if( get_option('bookshelf_paypal_email') == '' )
		$message .= "Bookshelf not yet configured! Go to Bookshelf&rarr;settings to configure.";

	if( !file_exists(BOOKSHELF_UPLOAD_DIR) && !@mkdir(BOOKSHELF_UPLOAD_DIR) )
		$message .= "<br />Upload directory does not exist. Please create bookshelf_files directory in ".$pdf_files." directory";

	if($message)
		echo '<div id="error" class="error">'.$message.'</div>';

	return false;
}
add_action( 'admin_notices', 'bookshelf_check_config' );
?>
