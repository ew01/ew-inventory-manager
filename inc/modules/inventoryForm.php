<?php
/**
 * Created by PhpStorm.
 * Author: David
 * Date: 8/29/2019
 * Time: 11:49
 * Name:
 * Desc:
 */





//region Global Variables, Classes, Local Variables, Get Options
global $wpdb;

$ewim_tables= new ewim_tables();
$ewim_get_options= new ewim_get_options();
$ewim_debug_settings= new ewim_debug_settings();
//endregion

//region Get Item, Check Owner
if(isset($_REQUEST['record_id'])){
	$ewim_inventoryID= $_REQUEST['record_id'];
	$ewim_aInventory= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_games WHERE id = $ewim_inventoryID AND user_id = $ewim_userID",ARRAY_A);
	if($ewim_aInventory == ''){
		$ewim_content.= "The requested Inventory does not exist, or does not belong to you. Please try again.";
		return;
	}
}
//endregion

$ewim_form= do_shortcode('[gravityform id="'.$ewim_get_options->ewim_inventoryFormID.'" title="true" description="true"]');

$ewim_content.=<<<EOV
$ewim_form
EOV;
