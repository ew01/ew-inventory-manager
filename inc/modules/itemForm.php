<?php
/**
 * Created by PhpStorm.
 * Author: David
 * Date: 8/29/2019
 * Time: 11:51
 * Name:
 * Desc:
 */





//region Global Variables, Classes, Local Variables, Get Options
global $wpdb;

$ewim_tables= new ewim_tables();
$ewim_get_options= new ewim_get_options();
$ewim_debug_settings= new ewim_debug_settings();

$ewim_activeInventoryID= get_user_meta($ewim_userID, 'active_inventory', true);
//endregion

//region Get Item, Check Owner
//todo check inventory belongs
if(isset($_REQUEST['record_id'])){
	$ewim_itemID= $_REQUEST['record_id'];
	$ewim_aItem= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_items WHERE id = $ewim_itemID AND user_id = $ewim_userID AND inventory_id = $ewim_activeInventoryID",ARRAY_A);
	if($ewim_aItem == ''){
		$ewim_content.= "Item requested does not exist, is not associated with active inventory, or does not belong to you. Please try again.";
		return;
	}
}
else{
	$ewim_aInventory= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_games WHERE id = $ewim_activeInventoryID AND user_id = $ewim_userID",ARRAY_A);
	if($ewim_aInventory == ''){
		$ewim_content.= "Item requested does not exist, is not associated with active inventory, or does not belong to you. Please try again.";
		return;
	}
}
//endregion

$ewim_form= do_shortcode('[gravityform id="'.$ewim_get_options->ewim_itemFormID.'" title="true" description="false"]');

$ewim_content.=<<<EOV
$ewim_form
EOV;
