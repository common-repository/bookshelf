<?php
/*
 * Displays and saves metaboxes.
 */

function bookshelf_metabox( ) {
	add_meta_box('bookshelf-books','PDF file','display_bookshelf_upload','post','normal','high');
	add_meta_box('bookshelf-books','PDF file','display_bookshelf_upload','page','normal','high');
}
add_action( 'add_meta_boxes', 'bookshelf_metabox' ); // 3.0+ only

function display_bookshelf_upload( ) {
	/* Thanks to http://core.trac.wordpress.org/ticket/13601, while fixing it I found a better way to calculate upload max size */
	$upload_size_unit = $max_upload_size = wp_max_upload_size();
	$sizes = array( 'KB', 'MB', 'GB' );
	for ( $u = -1; $upload_size_unit > 1024 && $u < count( $sizes ) - 1; $u++ )
		$upload_size_unit /= 1024;
	if ( $u < 0 ) {
		$upload_size_unit = 0;
		$u = 0;
	} else {
		$upload_size_unit = (int) $upload_size_unit;
	}
?>
	<input type="hidden" name="_nonce_bookshelf" id="_nonce_bookshelf" value="<?php echo wp_create_nonce( plugin_basename(__FILE__) ) ?>" />
	<script type="text/javascript">
		jQuery( function($) {
			$( '#upload_pdf_file' ).makeAsyncUploader({
				upload_url: '<?php echo plugins_url( 'upload-pdf.php', __FILE__ ); ?>',
				flash_url: '<?php echo plugins_url( 'js/jquery-asyncUpload-0.1/swfupload.swf', __FILE__ ); ?>',
				button_image_url: '<?php echo plugins_url( 'js/jquery-asyncUpload-0.1/blankButton.png', __FILE__ ); ?>',
				file_size_limit : "<?php echo $max_upload_size; ?>b",
				post_params: {
					upload_dir: '<?php echo str_replace('\\', '/', BOOKSHELF_UPLOAD_DIR  ); ?>'
				}
			});
		} );
	</script>

	<style type="text/css">
		div.ProgressBar { width: 100px; padding: 0; border: 1px solid black; margin-right: 1em; height:.75em; margin-left:1em; display:-moz-inline-stack; display:inline-block; zoom:1; *display:inline; }
		div.ProgressBar DIV { background-color: Green; font-size: 1pt; height:100%; float:left; }
		span.asyncUploader OBJECT { position: relative; top: 5px; left: 10px; }
	</style>
	<?php global $post; ?>
	<table class="form-table">
		<tr>
			<th scope="row"><h2><label for="upload_pdf_file_filename"><?php _e('Upload PDF file', 'bookshelf'); ?></label></h2></th>

			<td>
			<strong>Current file:</strong>
			<input type="text" class="regular-text" id="upload_pdf_file_filename" value="<?php echo get_post_meta($post->ID, '_bookshelf_pdf_file', true); ?>" name="upload_pdf_file_filename" /><br />
			<input type="file" name="upload_pdf_file" id="upload_pdf_file" value="Upload" /><br />
			</td>
		</tr>

		<tr>
			<td colspan="2"><hr /></td>
		</tr>

		<tr>	
			<th scope="row"><h2><label for="bookshelf_price"><?php _e( 'Product Price', 'bookshelf' ); ?></label></h2></th>
			
			<td>
				<input type="text" name="bookshelf_price" value="<?php echo get_post_meta( $post->ID, 'price', true ); ?>" id="bookshelf_price" /> <?php echo get_option( 'bookshelf_currency' ); ?>
				<p class="howot"><?php _e( 'Enter the price of the product without curreny symbols', 'bookshelf' ); ?></p>
			</td>
		</tr>
	</table>

<?php
}

function bookshelf_save_meta( $post_id ) {
	if( !isset( $_POST['_nonce_bookshelf'] ) || !wp_verify_nonce( $_POST['_nonce_bookshelf'], plugin_basename(__FILE__) ) )
		return $post_id;

	if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		return $post_id;

	if( 'page' == $_POST['post_type'] ) {
		if( ! current_user_can( 'edit_page', $post_id ) )
			return $post_id;
	} else {
		if( ! current_user_can( 'edit_post', $post_id ) )
			return $post_id;
	}

	$pdf_file = $_POST['upload_pdf_file_filename'];
	if( $pdf_file ) {
		update_post_meta( $post_id, '_bookshelf_pdf_file', esc_attr( $pdf_file ) );
	} else {
		delete_post_meta( $post_id, '_bookshelf_pdf_file' );
	}

	$price = $_POST['bookshelf_price'];
	if ( $price ) {
		update_post_meta( $post_id, 'price', esc_attr( $price ) );
	} else {
		delete_post_meta( $post_id, 'price' );
	}

	return $post_id;
}
add_action( 'save_post', 'bookshelf_save_meta' );

function bookshelf_required_js( ) {
	global $current_screen;

	if( isset( $current_screen->post_type ) && in_array( $current_screen->post_type, array('post', 'page') ) && is_admin() ) {
		wp_enqueue_script( 'jquery' ); // Just to make sure
		wp_enqueue_script( 'swfupload' ); // Also to make sure
		wp_enqueue_script( 'jquery-asyncUpload-0.1', plugins_url('js/jquery-asyncUpload-0.1/jquery-asyncUpload-0.1.js', __FILE__) ); //Required file
	}
}
add_action( 'wp_print_scripts', 'bookshelf_required_js' );

?>
