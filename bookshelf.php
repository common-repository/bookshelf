<?php
/*
Plugin name: Bookshelf
Plugin URI: http://wordpress.org/extend/plugins/bookshelf/
Version: 2.0.4
Author: Reuben Gunday
Author URI: http://www.revood.com
Description: This plugin allows you to sell ebooks. It works with PayPal and has multiple currency option.
Licence: GPLv2
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

$bookshelf_inc = dirname(__FILE__).'/includes';
$pdf_dir = wp_upload_dir();

define( 'BOOKSHELF_SALES_TABLE', 'bookshelf_sales' );
define( 'BOOKSHELF_UPLOAD_DIR', $pdf_dir['basedir'].'/bookshelf_files' );

require( $bookshelf_inc.'/functions.php' );

if( is_admin() ) {
	require( $bookshelf_inc.'/metabox.php' );
	require( $bookshelf_inc.'/sales.php' );
	require( $bookshelf_inc.'/settings.php' );
}

register_activation_hook(__FILE__, 'bookshelf_activate' );
function bookshelf_activate() {
	global  $wpdb;
	$wpdb->query( "SHOW TABLES LIKE '".BOOKSHELF_SALES_TABLE."'" );
	if( !get_option('bookshelf_table') || $wpdb->num_rows == 0 ) {
		global $wpdb;

		$sql = 'CREATE TABLE ' .BOOKSHELF_SALES_TABLE. '(
			txn_id varchar(255) NOT NULL PRIMARY KEY,
			status varchar(255) NOT NULL,
			details longtext NOT NULL
			);';

		require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	update_option('bookshelf_table', '2.0' );

	/* Attemp to create Bookshelf upload directory, if fails intstruct the user to create one */
	if( !file_exists( BOOKSHELF_UPLOAD_DIR ) ){
		if( !mkdir( BOOKSHELF_UPLOAD_DIR, 0700 ) ) {
			// TODO: Alert user on admin_notice??
		}
	} else {
		// Create a .htaccess
		$fh = @fopen( BOOKSHELF_UPLOAD_DIR . '/.htaccess', 'a+' );
		if( $fh ) {
			$htaccess = '';

			if( filesize( BOOKSHELF_UPLOAD_DIR . '/.htaccess' ) )
				$htaccess = fread( $fh, filesize( BOOKSHELF_UPLOAD_DIR . '/.htaccess' ) );

			if( !preg_match( '/deny from all/i', $htaccess ) )
				fwrite( $fh, 'deny from all' );

			fclose( $fh );

		}
	}
}

function process_paypal( ) {
	if( isset( $_REQUEST['_bookshelf'] ) ) {
		switch( $_REQUEST['_bookshelf'] ){
			case 'ipn':
				include( dirname(__FILE__).'/includes/gateways/ipn.php' );
				exit( 0 );
			break;

			case 'success':
				echo '<!doctype html><html>';
				echo '<head><meta http-equiv="refresh" content="5;url=' .home_url('/'). '"><title>'.get_bloginfo('name').'</title></head>';
				echo '<body style="text-align: center"><h1>Order successful. Please check your email for more details.</h1><h2>If you not redirect in 5 seconds <a href="'.home_url('/').'">click here</a></h2></body>';
				echo '</html>';
				exit( 0 );
			break;

			case 'cancel':
				echo '<!doctype html><html>';
				echo '<head><meta http-equiv="refresh" content="5;url=' .home_url('/'). '"><title>'.get_bloginfo('name').'</title></head>';
				echo '<body style="text-align: center"><h1>Order cancelled.</h1><h2>If you not redirect in 5 seconds <a href="'.home_url('/').'">click here</a></h2></body>';
				echo '</html>';
				exit( 0 );
			break;

			default:
			break;
		}
	}

	if( isset( $_REQUEST['download'] ) ) {
		global $wpdb;
		$details = $wpdb->get_var( $wpdb->prepare( "SELECT details FROM " .BOOKSHELF_SALES_TABLE. " WHERE txn_id = %s ", $_REQUEST['download'] ) );
		$details = unserialize( $details );
		$post_id = $details['item_number'];
		$filename = get_post_meta( $post_id ,'_bookshelf_pdf_file', true );

		if( !$filename || ( strtotime($details['date']) < strtotime('now-2days') ) ) {
			wp_die( 'The book is not available for download anymore.', '403 Forbidden', array( 'reponse' => 403 ) );
		}

		$uploads_dir = wp_upload_dir();
		$uploads_dir = $uploads_dir['basedir'].'/bookshelf_files/';
		$filename = $uploads_dir.$filename;

		if( !file_exists( $filename ) )
			wp_die( 'The book is not available for download anymore.', '404 Not Found', array( 'reponse' => 403 ) );

		if(ini_get('zlib.output_compression'))
			ini_set('zlib.output_compression', 'Off');

		header("Pragma: public"); // required
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: private",false); // required for certain browsers


		$pathinfo = pathinfo( $filename );

		$content_type = "Content-Type: application/pdf";
		if( strtolower($pathinfo["extension"]) != 'pdf' )
			$content_type = "Content-Type: application/octet-stream";

		header( $content_type );

		header("Content-Disposition: attachment; filename=\"".basename($filename)."\";" );
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: ".filesize($filename));
		@readfile($filename);

		exit( 0 );
	}
}
add_action( 'init', 'process_paypal' );

function bookshelf_powered_link( ) {
	echo '<p align="center">Bookshelf 2.0 developed by <a href="http://www.revood.com" title="wordpress services">revood.com</a></p>';
}
add_action( 'wp_footer' ,'bookshelf_powered_link' );

?>
