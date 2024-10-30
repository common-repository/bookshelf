<?php
/*
 * Sales page
 */

if( !defined( 'ABSPATH' ) )
	exit( 'die()' );

function bookshelf_sales( ) {
	global $wpdb;
	$paged = ( isset( $_REQUEST['paged'] ) ) ? intval($_REQUEST['paged']) : 1;

	$total_results = $wpdb->get_var( "SELECT count(*) FROM ".BOOKSHELF_SALES_TABLE );
	$per_page = 10;

	$pages = ceil( $total_results/$per_page );

	$start = 0;
	if( $pages > 0 )
		$start = ($paged-1) * $per_page;

	$query = "SELECT * FROM " .BOOKSHELF_SALES_TABLE. " LIMIT {$start}, {$per_page}";
	$sales = $wpdb->get_results($query);
?>
	<div class="wrap">
		<h2>Sales</h2>

		<table class="widefat">
			<thead>
				<tr><th>Book</th> <th>Payment status</th> <th>Details</th></tr>
			</thead>

			<tbody>
				<?php
					if( !empty($sales) ) {
						$sales = array_reverse( $sales );
						foreach($sales as $sale){
							$txn_id = $sale->txn_ID;
							$status = $sale->status;
							$details = unserialize(html_entity_decode($sale->details, ENT_QUOTES));
				?>
							<tr>
								<td><?php echo get_the_title($details['item_number']); ?></td>
								<td><?php echo $status; ?></td>
								<td>
									<em>Name: </em><?php echo $details['name']; ?><br />
									<em>Email: </em><?php echo $details['payer_email']; ?><br />
									<em>Country:</em><?php echo $details['country']; ?><br />
									<em>Payment date:</em><?php echo $details['date']; ?><br />
									<em>Amount: </em><?php echo $details['amount']; ?><br />
									<em>Transaction ID:</em><?php echo $sale->txn_ID; ?>
								</td>
							</tr>
				<?php
						}
					}
				?>
			</tbody>
		</table>
		<p align="center">
		<?php
			$next = remove_query_arg( 'paged', $_SERVER['REQUEST_URI'] );
			$next = add_query_arg( 'paged', $paged+1, $next );

			$prev = remove_query_arg( 'paged', $_SERVER['REQUEST_URI'] );
			$prev = add_query_arg( 'paged', $paged-1, $next );
		?>

		<?php if ( $paged != 1  && $paged <= $pages ) : ?>
			<a href="<?php echo $prev; ?>">&laquo; Prev</a>
		<?php endif; ?>

		<?php if( ( $paged == 1 || $pages > $paged) && $pages > 1 ): ?>
			<a href="<?php echo $next; ?>">Next &raquo;</a>
		<?php endif; ?>

		</p>
	</div>
<?php
}
?>
