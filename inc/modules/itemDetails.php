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

$ewim_itemID= $_REQUEST['item_id'];
$ewim_formMessage= $_REQUEST['form_message'];
$ewim_activeGameSystem= get_user_meta($ewim_userID, 'active_game_system', true);

//endregion

//region Step 1: Get Item Information, decode where needed
$ewim_aItem= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_items WHERE id = $ewim_itemID AND user_id = $ewim_userID",ARRAY_A);
$ewim_aItem['item_recipe_ingredients']= json_decode($ewim_aItem['item_recipe_ingredients'], true);
$ewim_recipeCount= count($ewim_aItem['item_recipe_ingredients']);
//endregion

//region Step 2: Organize the Item Information into usable Variables
$ewim_itemName= $ewim_aItem['item_name'];
$ewim_itemInventory= number_format($ewim_aItem['item_inventory_quantity']);
$ewim_itemTotalCost= number_format(round($ewim_aItem['cost'],2),2,'.',',');

$ewim_itemAverageCost= ($ewim_aItem['item_inventory_quantity'] > 0 ? number_format($ewim_aItem['cost'] / $ewim_aItem['item_inventory_quantity'],20,'.',',') : '0.00');

$ewim_itemCategory= $ewim_aItem['category'];

//region Switch Creates the correct inventory count title
switch ($ewim_aItem['category']){
	case "Blueprint Copy":
		$ewim_inventoryCountTitle= "Total Products that can be Manufactured";
		break;
	default:
		$ewim_inventoryCountTitle= 'Inventory Count';
		break;
}
//endregion

//region Switch Create the Correct Recipe Title
switch ($ewim_aItem['category']){
	case "Mineral":
		$ewim_recipeOrMineralsTitle= "<th>Found in</th>";
		break;
	case "Ore":
		$ewim_recipeOrMineralsTitle= "<th>Contained Minerals</th>";
		break;
	case "Product":
		$ewim_recipeOrMineralsTitle= "<th></th>";
		break;
	case "Blueprint":
		$ewim_recipeOrMineralsTitle= "<th>Recipe</th>";
		break;
	case "Blueprint Copy":
		$ewim_recipeOrMineralsTitle= "<th>Recipe</th>";
		break;
}
//endregion

$ewim_recipeOrMinerals= "<td>";

if($ewim_aItem['item_recipe_ingredients'] != ''){
	$ewim_itemDetailsPage= get_permalink(get_page_by_title($ewim_get_options->ewim_itemPage));

	foreach($ewim_aItem['item_recipe_ingredients'] as $ewim_recipeItem => $ewim_recipeItemID){
		//todo get recipe items average cost, and display it. want to use other things to, possible calculate cost of item being made as well
		/*
		if($ewim_aItem['category'] == 'Blueprint'){
			$ewim_aRecipeItem= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_items WHERE id = $ewim_recipeItemID",ARRAY_A);
			//$ewim_recipeItem
		}
		*/


		$ewim_recipeOrMinerals.= "<a href='$ewim_itemDetailsPage?item_id=$ewim_recipeItemID'>$ewim_recipeItem</a>, ";
	}
	$ewim_recipeOrMinerals= substr($ewim_recipeOrMinerals, 0, -2);
	$ewim_recipeOrMinerals.= "</td>";
}
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
			$ewim_inventoryCountTitle
		</th>
		<th>
			Total Cost
		</th>
		<th>
			Average Cost
		</th>
		<th>
			Category
		</th>		
		$ewim_recipeOrMineralsTitle
		
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
			$ewim_recipeOrMinerals
		</tr>
	</tbody>
</table>
EOV;
//endregion