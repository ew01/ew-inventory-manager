<?php
/**
 * Created by PhpStorm.
 * Author: David
 * Date: 9/20/2018
 * Time: 11:37
 * Name:
 * Desc:
 */





//region Global Variables, Classes, Local Variables, Get Options
global $wpdb;

$ewim_tables= new ewim_tables();
$ewim_get_options= new ewim_get_options();
$ewim_debug_settings= new ewim_debug_settings();

$ewim_itemID= $_REQUEST['record_id'];
$ewim_formMessage= $_REQUEST['form_message'];

$ewim_activeInventoryID= get_user_meta($ewim_userID, 'active_inventory', true);
//endregion

//region Get Item, Check Owner
$ewim_aItem= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_items WHERE id = $ewim_itemID AND user_id = $ewim_userID AND inventory_id = $ewim_activeInventoryID",ARRAY_A);
if($ewim_aItem == ''){
	$ewim_content.= "Item requested does not exist, is not associated with active inventory, or does not belong to you. Please try again.";
	return;
}
//endregion

//region Get extra Records, Decode, Explode
//Inventory Related
$ewim_aInventory= $wpdb->get_row( "SELECT * FROM $ewim_tables->ewim_games WHERE id = $ewim_activeInventoryID", ARRAY_A );
$ewim_aInventory['inventory_currencies']= json_decode($ewim_aInventory['inventory_currencies'], true);

//Item Related
$ewim_aItem['item_meta']= json_decode($ewim_aItem['item_meta'], true);

//Double Decode, current encode is messed up some how.
$ewim_aItem['design_details']= json_decode($ewim_aItem['design_details'], true);
//endregion

//region Assign Variables From Records
$ewim_recipeCount= count($ewim_aItem['design_details']);
$ewim_itemName= $ewim_aItem['item_name'];
$ewim_itemInventory= number_format($ewim_aItem['item_inventory_quantity']);

//region Handle Currency
switch ($ewim_aInventory['inventory_currency_system']){
	case 'Single Currency System':
		//Currency Label
		$ewim_currency= $ewim_aInventory['inventory_currencies']['inventory_currency'];

		//Total Cost
		$ewim_itemTotalCost= number_format(round($ewim_aItem['cost'],2),2,'.',',');
		//Average Item Cost
		$ewim_itemAverageCost= ($ewim_aItem['item_inventory_quantity'] > 0 ? number_format($ewim_aItem['cost'] / $ewim_aItem['item_inventory_quantity'],2,'.',',') : '0.00');

		break;
	case 'Triple Currency System':
		break;
}
//endregion

//region Column Titles and Values base on Category
$ewim_itemCategory= $ewim_aItem['category'];
switch ($ewim_itemCategory){
	case "Product":
		$ewim_columnOneTitle= "Inventory Count";

		//region Design
		$ewim_columnFiveTitle= "<th>Design Items</th>";
		if($ewim_aItem['design_details'] != ''){
			$ewim_columnFiveValue= "<td>";
			$ewim_itemDetailsPage= get_permalink(get_page_by_title($ewim_get_options->ewim_itemPage));

			//foreach($ewim_aItem['design_details'] as $ewim_recipeItem => $ewim_recipeItemID){
			foreach($ewim_aItem['design_details'] as $ewim_designItemID => $ewim_aDesignItem){
				$ewim_aDesignItemDetails= $wpdb->get_row( "SELECT * FROM $ewim_tables->ewim_items WHERE id = $ewim_designItemID", ARRAY_A );
				$ewim_designItemName= $ewim_aDesignItemDetails['item_name'];
				//todo get recipe items average cost, and display it. want to use other things to, possible calculate cost of item being made as well
				$ewim_columnFiveValue.= "<a href='$ewim_itemDetailsPage?record_id=".$ewim_designItemID."'>".$ewim_aDesignItem['amount']."x ".$ewim_designItemName."</a>, ";
			}
			$ewim_columnFiveValue= substr($ewim_columnFiveValue, 0, -2);
			$ewim_columnFiveValue.= "</td>";
		}
		//endregion

		//region Product Info and Links
		$ewim_productID= $ewim_aItem['item_meta']['design_copy_id'];
		/*todo check for Design copy, then place column
		$ewim_columnSixTitle= "<th>Design Copy</th>";
		$ewim_columnSixValue= "<td><a href='?record_id=$ewim_productID'>$ewim_itemName Design Copy</a></td>";
		*/
		//endregion
		break;
	case "Component":
		$ewim_columnOneTitle= "Inventory Count";

		//region Design
		$ewim_columnFiveTitle= "<th>Design Items</th>";
		if($ewim_aItem['design_details'] != ''){
			$ewim_columnFiveValue= "<td>";
			$ewim_itemDetailsPage= get_permalink(get_page_by_title($ewim_get_options->ewim_itemPage));

			//foreach($ewim_aItem['design_details'] as $ewim_recipeItem => $ewim_recipeItemID){
			foreach($ewim_aItem['design_details'] as $ewim_designItemID => $ewim_aDesignItem){
				$ewim_aDesignItemDetails= $wpdb->get_row( "SELECT * FROM $ewim_tables->ewim_items WHERE id = $ewim_designItemID", ARRAY_A );
				$ewim_designItemName= $ewim_aDesignItemDetails['item_name'];
				//todo get recipe items average cost, and display it. want to use other things to, possible calculate cost of item being made as well
				$ewim_columnFiveValue.= "<a href='$ewim_itemDetailsPage?record_id=".$ewim_designItemID."'>".$ewim_aDesignItem['amount']."x ".$ewim_designItemName."</a>, ";
			}
			$ewim_columnFiveValue= substr($ewim_columnFiveValue, 0, -2);
			$ewim_columnFiveValue.= "</td>";
		}
		//endregion

		//region Product Info and Links
		$ewim_productID= $ewim_aItem['item_meta']['design_copy_id'];
		/*todo check for Design copy, then place column
		$ewim_columnSixTitle= "<th>Design Copy</th>";
		$ewim_columnSixValue= "<td><a href='?record_id=$ewim_productID'>$ewim_itemName Design Copy</a></td>";
		*/
		//endregion
		break;
	case "Design Copy":
		$ewim_columnOneTitle= "Total Products that can be Manufactured";

		//region Design
		$ewim_columnFiveTitle= "<th>Design Items</th>";

		$ewim_productID= $ewim_aItem['item_meta']['product_id'];
		$ewim_aProduct= $wpdb->get_row( "SELECT * FROM $ewim_tables->ewim_items WHERE id = '$ewim_productID'", ARRAY_A );
		$ewim_aDesign= ($ewim_aProduct['design_details'] != '' ? json_decode($ewim_aProduct['design_details'], true) : '');

		if(is_array($ewim_aDesign)){
			$ewim_columnFiveValue= "<td>";
			$ewim_itemDetailsPage= get_permalink(get_page_by_title($ewim_get_options->ewim_itemPage));

			foreach($ewim_aDesign as $ewim_designItemID => $ewim_aDesignItem){
				$ewim_aDesignItemDetails= $wpdb->get_row( "SELECT * FROM $ewim_tables->ewim_items WHERE id = $ewim_designItemID", ARRAY_A );
				$ewim_designItemName= $ewim_aDesignItemDetails['item_name'];
				//todo get recipe items average cost, and display it. want to use other things to, possible calculate cost of item being made as well
				$ewim_columnFiveValue.= "<a href='$ewim_itemDetailsPage?record_id=".$ewim_designItemID."'>".$ewim_aDesignItem['amount']."x ".$ewim_designItemName."</a>, ";
			}
			$ewim_columnFiveValue= substr($ewim_columnFiveValue, 0, -2);
			$ewim_columnFiveValue.= "</td>";
		}
		//endregion

		//region Product Info and Links
		$ewim_productID= $ewim_aItem['item_meta']['product_id'];
		$ewim_productName= $ewim_aItem['item_meta']['product_name'];
		$ewim_columnSixTitle= "<th>Product</th>";
		$ewim_columnSixValue= "<td><a href='?record_id=$ewim_productID'>$ewim_productName</a></td>";
		//endregion
		break;
	case "Refined Resource":
		$ewim_columnOneTitle= 'Inventory Count';
		//$ewim_columnFiveTitle= "<th>Found In</th>";
		//$ewim_columnFiveTitle= "<th></th>";
		break;
	case "Raw Resource":
		$ewim_columnOneTitle= 'Inventory Count';

		//region Contents
		$ewim_columnFiveTitle= "<th>Contained Minerals</th>";
		if($ewim_aItem['design_details'] != ''){
			$ewim_columnFiveValue= "<td>";
			$ewim_itemDetailsPage= get_permalink(get_page_by_title($ewim_get_options->ewim_itemPage));

			//foreach($ewim_aItem['design_details'] as $ewim_recipeItem => $ewim_recipeItemID){
			foreach($ewim_aItem['design_details'] as $ewim_designItemID){
				$ewim_aDesignItemDetails= $wpdb->get_row( "SELECT * FROM $ewim_tables->ewim_items WHERE id = $ewim_designItemID", ARRAY_A );
				$ewim_designItemName= $ewim_aDesignItemDetails['item_name'];
				//todo get recipe items average cost, and display it. want to use other things to, possible calculate cost of item being made as well
				$ewim_columnFiveValue.= "<a href='$ewim_itemDetailsPage?record_id=".$ewim_designItemID."'>".$ewim_designItemName."</a>, ";
			}
			$ewim_columnFiveValue= substr($ewim_columnFiveValue, 0, -2);
			$ewim_columnFiveValue.= "</td>";
		}
		//endregion
		break;
	default:
		$ewim_columnOneTitle= 'Inventory Count';
		break;
}
//endregion

//endregion

//region Create Border less buttons for safe Posting
//Edit Button
$ewim_itemFormPageURL= get_permalink(get_page_by_title($ewim_get_options->ewim_itemFormPage));
$ewim_editItemButton="
<form action='$ewim_itemFormPageURL' style='display: inline'>
	<input type='hidden' name='record_id' value='$ewim_itemID'>
	<input type='hidden' name='inventoryID' value='$ewim_activeInventoryID'>
	<button style='border:0;padding:0;display:inline;background:none;color:#55cbff;'>Edit</button>
</form>
";
//endregion

//region Display
$ewim_form= do_shortcode('[gravityform id="'.$ewim_get_options->ewim_itemTransactionFormID.'" title="false" description="false"]');//todo update other pages to use this, and modify to handle form id passed in
$ewim_content.=<<<EOV
<p>$ewim_formMessage</p>
<h1>$ewim_itemName</h1>$count
<p style="display: inline;">$ewim_editItemButton</p>
<table class="ewim-zebra-base">
	<thead>
		<tr style="background: black">
			<th>
				$ewim_columnOneTitle
			</th>
			<th>
				Total Production Cost
			</th>
			<th>
				Average Cost
			</th>
			<th>
				Category
			</th>		
			$ewim_columnFiveTitle
			$ewim_columnSixTitle
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>
				$ewim_itemInventory
			</td>
			<td>
				$ewim_itemTotalCost $ewim_currency
			</td>
			<td>
				$ewim_itemAverageCost $ewim_currency
			</td>
			<td>
				$ewim_itemCategory
			</td>			
			$ewim_columnFiveValue
			$ewim_columnSixValue
		</tr>
	</tbody>
</table>
$ewim_form
EOV;
//endregion