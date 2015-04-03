<?php
/*
Plugin Name: Woocommerce Product Wise Orders Report
Description: Shows all orders of Selected product
Author: Muhammad Bilal
Version: 1.0
Author URI: http://skills2earn.blogspot.com
*/

add_action('admin_menu', 'woocommerce_product_wise_orders_report',100);
function woocommerce_product_wise_orders_report() {
	$role = get_role( 'administrator' );
	if (!$role->has_cap('view_orders_report')) {
		$role->add_cap( 'view_orders_report');
	}
	add_menu_page(
	'Orders Report'
	, 'Orders Report'
	, 'view_orders_report'
	, 'orders-report'
	, 'woocommerce_product_wise_orders_report_content'
	);
}

function woocommerce_product_wise_orders_report_content() {

	wp_enqueue_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION );
	wp_enqueue_script( 'woocommerce_admin' );
	wp_enqueue_script( 'ajax-chosen' );
	wp_enqueue_script( 'chosen' );
	wp_enqueue_script( 'jquery-ui-autocomplete' );	
	wp_enqueue_script( 'woocommerce_admin_meta_boxes' );

	$params = array(
		'ajax_url' 						=> admin_url('admin-ajax.php'),
		'search_products_nonce' 		=> wp_create_nonce("search-products"),
	);

	wp_localize_script( 'woocommerce_admin_meta_boxes', 'woocommerce_admin_meta_boxes', $params ); 
	?>
	<h1>Orders Report</h1>
	<form name="product_status_frm" method="post" action="">
	<b>Select Deal:</b> <select name="deal_id" class="ajax_chosen_select_products" multiple="multiple" data-placeholder="<?php _e( 'Search for a product&hellip;', 'woocommerce' ); ?>" style="width: 400px"></select>
	<input type="submit" name="submit" value="Search">
	</form>
	<?php
	if (isset($_REQUEST['deal_id']))
	{
		global $wpdb;
		if (is_array($_REQUEST['deal_id']))
			$product_id = $_REQUEST['deal_id'][0];
		else
			$product_id = $_REQUEST['deal_id'];
// 		$product = new WC_Product($product_id);
		$product = get_product($product_id);
// 		echo "<pre>";
// 		print_r($product);		 
		$order_ids = $wpdb->get_results("SELECT oi.order_id,oi.order_item_id FROM wp_woocommerce_order_items oi RIGHT JOIN wp_woocommerce_order_itemmeta oim ON oi.order_item_id=oim.order_item_id WHERE oim.meta_key='_product_id' AND oim.meta_value=".$product_id." ORDER BY oim.meta_id desc",OBJECT);
		$data=array();
		$data[] = array('S.No','Customer Name','Order ID','Quantity','Status','Payment Tag','Shipment Tag','Comment','Variations');
		?>
		<h2 style="margin-bottom:0px;"><?php echo $product->post->post_title; ?></h2><br>
		<div style="display: block; float:right; width: 100px; margin-bottom:10px;"><button id="export_csv">Export CSV</button></div>
		<table class="widefat">
			<thead>
			    <tr>
			        <th>S.No</th>
			        <th>Customer Name</th>       
			        <th>Order ID</th>
			        <th>Quantity</th>
			        <th>Status</th>       
			        <th>Payment Tag</th>
			        <th>Shipment Tag</th>
			        <th>Comment</th>
			        <?php if ( $product->product_type == "variation" || $product->product_type == "variable" || $product->product_type == "bundle" ) {?>
			        <th>Variations</th>
			        <?php }?>
			    </tr>
			</thead>
			<tfoot>
			    <tr>
			        <th>S.No</th>
			        <th>Customer Name</th>       
			        <th>Order ID</th>
			        <th>Quantity</th>
			        <th>Status</th>       
			        <th>Payment Tag</th>
			        <th>Shipment Tag</th>
			        <th>Comment</th>
			        <?php if ( $product->product_type == "variation" || $product->product_type == "variable" || $product->product_type == "bundle" ) {?>
			        <th>Variations</th>
			        <?php }?>
			    </tr>
			</tfoot>
			<tbody>
			<?php
			$count=1;
			foreach ($order_ids as $order_id)
			{
				unset($order,$order_status,$order_meta_arr,$comments);
				$order = new WC_Order($order_id->order_id);
				if (empty($order->id))
					continue;
				$order_status = (array) $order;
				$order_meta_arr = get_post_meta($order->id);
				$order_items = $order->get_items( );
				$comments = $wpdb->get_results("select comment_content from wp_comments where comment_post_ID = ".$order->id." AND comment_content LIKE '%order status%' order by comment_ID desc LIMIT 1",OBJECT);
// 				echo "<pre>";			
// 				print_r($order_items[$order_id->order_item_id]);
			?>
			   <tr<?php if ($count%2 != 0){?> bgcolor="#ddd"<?php }?>>
			        <td><?php echo $count; ?></td>
			        <td><?php echo $order->billing_first_name." ".$order->billing_last_name; ?></td>       
			        <td><?php echo $order->id; ?></td>
			        <td><?php echo $order_items[$order_id->order_item_id]['qty']; ?></td>
			        <td><?php echo $order_status['status']; ?></td>
			        <td><?php echo $order_meta_arr['_order_tag'][0]; ?></td>
			        <td><?php echo $order_meta_arr['_order_shipping_tag'][0]; ?></td>
			        <td><?php echo $comments[0]->comment_content; ?></td>
			        <?php if ( $product->product_type == "variation" || $product->product_type == "variable" || $product->product_type == "bundle" ) {
			        ?>
			        <td><?php
			        $variations = '';
			        if ($product->product_type == "bundle")
			        {
			        	$stamp = unserialize($order_items[$order_id->order_item_id]['stamp']);
			        	if (!empty($stamp))
				        	foreach ($stamp as $item)
				        	{
				        		foreach ($item['attributes'] as $name => $value)
				        		{
				        			echo ucfirst(str_replace('attribute_','',$name)).": ".$value."<br>";
				        			$variations .= "##".ucfirst(str_replace('attribute_','',$name)).": ".$value;
				        		}
// 				        		echo "<pre>";
// 				        		print_r($item['attributes']);
				        	}
			        }
			        else 
			        {
			        	$product_attributes = array_keys($product->get_variation_attributes( ));       		
						foreach ($product_attributes as $attribute){
							if (isset($order_items[$order_id->order_item_id][strtolower($attribute)]))
								$att_value = $order_items[$order_id->order_item_id][strtolower($attribute)];
							else
								$att_value = $order_items[$order_id->order_item_id][strtolower(trim($attribute,':'))];
				        	echo $attribute.": ".$att_value."<br>";
				        	$variations .= "##".$attribute.": ".$att_value;
				        }
			        } 
			        ?></td>
			        <?php }?>
			    </tr>
			<?php 
			$data[] = array(
						$count
						,$order->billing_first_name." ".$order->billing_last_name
						,$order->id
						,$order_items[$order_id->order_item_id]['qty']
						,$order_status['status']
						,$order_meta_arr['_order_tag'][0]
						,$order_meta_arr['_order_shipping_tag'][0]
						,$comments[0]->comment_content
						,$variations);
			$count++;
			}?>
			</tbody>
		</table>
		<?php
	}
	?>
	<a id="download"></a>
	<script>
	jQuery(document).ready(function(){
	    jQuery('#export_csv').click(function(){
	    	var data = JSON.parse('<?php echo addslashes( json_encode($data) ) ?>');
	        if(data == '')
	            return;
	        downloadCsv(data);
	    });
	});
	function downloadCsv(content){
		var finalVal='';
		for (var i = 0; i < content.length; i++) {
			 content[i].forEach(function(entry) {
				 finalVal += '"'+entry+'",';
			 });
	         
	         finalVal += '\n';
	    }

		var download = document.getElementById('download');
		download.setAttribute('href', 'data:text/csv;charset=utf-8,' + encodeURIComponent(finalVal));
		download.setAttribute('download', 'StatusReport-('+content.length+').csv');
		download.click();
	}

	</script>
	<?php
}
