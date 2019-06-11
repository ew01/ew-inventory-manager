<?php
/**
 * Created by PhpStorm.
 * Author: David
 * Date: 9/20/2018
 * Time: 11:37
 * Name:
 * Desc:
 */





//region Global Variables, Classes, Local Variables
global $wpdb;

$ewim_tables= new ewim_tables();
$ewim_get_options= new ewim_get_options();
$ewim_debug_settings= new ewim_debug_settings();

$ewim_recordID= $_REQUEST['record_id'];
$ewim_formMessage= $_REQUEST['form_message'];
$ewim_activeGameSystem= get_user_meta($ewim_userID, 'active_game_system', true);

//endregion

//region Step 1: Get Item Information, decode where needed
$ewim_aPost= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_posted WHERE id = $ewim_recordID",ARRAY_A);
$ewim_itemID= $ewim_aPost['item_id'];
$ewim_aItem= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_items WHERE id = $ewim_itemID",ARRAY_A);
//endregion

//region Step 2: Organize the Item Information into usable Variables
$ewim_itemName= $ewim_aItem['item_name'];
$ewim_numberPosted= $ewim_aPost['amount'];
$ewim_postedPrice= number_format($ewim_aPost['post_price'],2,'.',',');
$ewim_itemAverageCost= number_format($ewim_aPost['average'],2,'.',',');
$ewim_brokerFee= number_format($ewim_aPost['broker_fee'],2,'.',',');

//endregion

//region Step 3: Create Border less buttons for safe Posting
//Edit Button
$ewim_itemFormPageURL= get_permalink(get_page_by_title($ewim_get_options->ewim_itemFormPage));
$ewim_editItemButton="
<form action='$ewim_itemFormPageURL' method='post' style='display: inline'>
	<input type='hidden' name='record_id' value='$ewim_itemID'>
	<button style='border:0;padding:0;display:inline;background:none;color:#55cbff;'>Edit</button>
</form>
";
//endregion

//region Step 4: Game System Variables
switch ($ewim_activeGameSystem){
	case "Eve":
		$ewim_currency= "ISK";
		break;
}
//endregion

//region Last Step: Display Data
$ewim_content=<<<EOV
$ewim_formMessage
<h1>$ewim_itemName</h1>
<p style="display: inline;">$ewim_editItemButton</p>
<table class="no-border">
	<thead>
		<th>
			Amount Posted
		</th>
		<th>
			Posted Price
		</th>
		<th>
			Average Production Price
		</th>
		<th>
			Broker Fee
		</th>		
	</thead>
	<tbody>
		<tr>
			<td>
				$ewim_numberPosted
			</td>
			<td>
				$ewim_postedPrice $ewim_currency
			</td>
			<td>
				$ewim_itemAverageCost
			</td>
			<td>
				$ewim_brokerFee
			</td>			
		</tr>
	</tbody>
</table>
EOV;
//endregion