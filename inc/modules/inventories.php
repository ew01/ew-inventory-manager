<?php
/**
 * Created by PhpStorm.
 * Author: David
 * Date: 3/1/2019
 * Time: 10:44
 * Name:
 * Desc:
 */
//todo Add catch for not users inventory when activating




//region Global Variables, Classes, Class Variables, Local Variables
global $wpdb;

$ewim_tables= new ewim_tables();
$ewim_get_options= new ewim_get_options();
$ewim_debug_settings= new ewim_debug_settings();

$ewim_activeInventoryID= get_user_meta($ewim_userID, 'active_inventory', true);
//endregion

//region Get the inventories for the user from the database, mark the active one before json encode
$ewim_aInventories= $wpdb->get_results("SELECT * FROM $ewim_tables->ewim_games WHERE user_id = $ewim_userID ORDER BY id",ARRAY_A);//Get Games

//region Debug
if($ewim_debug_settings->ewim_wpdbSelect == 1){
	echo "<h1>Games List</h1>";
	echo "User ID: " . $ewim_userID . "<br />";
	echo "<pre>";
	print_r($ewim_aInventories);
	echo "</pre>";
	exit;
}
//endregion

//endregion

//region Create links and buttons

//region Permalinks
//Inventory Form Page
$ewim_inventoryFormPageURL= get_permalink(get_page_by_title($ewim_get_options->ewim_inventoryFormPage));
//Items Page
$ewim_viewItemsURL= get_permalink(get_page_by_title($ewim_get_options->ewim_itemsPage));
//endregion

//region Get Max Games Allowed, display add inventory accordingly
$ewim_userMaxInventories= (get_user_meta($ewim_userID, 'max_inventories', true) == '' ? 2 : get_user_meta($ewim_userID, 'max_inventories', true));
if($wpdb->num_rows < $ewim_userMaxInventories){
	//New Inventory Link
	$ewim_inventoryFormLink= "<a href='$ewim_inventoryFormPageURL' title='Add Inventory'><i class='fas fa-plus-circle' aria-hidden='false'></i></a>";
}
//endregion

$ewim_editInventoryButton= "<a class='et_pb_button et_pb_button_0 et_pb_bg_layout_dark ewim-et-button-one' href='$ewim_inventoryFormPageURL?record_id={{ inventory.id }}'>Edit</a>";
$ewim_activateViewButton= "<a class='et_pb_button et_pb_button_0 et_pb_bg_layout_dark ewim-et-button-one' href='$ewim_viewItemsURL?action=activate&inventory_id={{ inventory.id }}'>Activate and View Items</a>";
//endregion

//region Set up the Active inventory
foreach($ewim_aInventories as &$ewim_aInventory){
	if($ewim_activeInventoryID == $ewim_aInventory['id']){
		$ewim_aInventory['active']= 'Yes';
	}
	else{
		$ewim_aInventory['active']= 'No';
	}
}
//endregion

//region Angular Set Up
$ewim_usePagination= 1;
$ewim_jsonData= json_encode($ewim_aInventories);
//$ewim_defaultOrderBy= 'inventory_name';
require_once( __DIR__."/../js/angular.php" );
//endregion

//region Display
$ewim_content.= <<<EOV
    <div id='inventoryList' ng-app='listApp' ng-controller='listController' class='default-padding'>
        <h1>Inventories</h1>     
        <p class="ewim-fs-16">{{data.length}} Inventories &nbsp; $ewim_inventoryFormLink</p>
            <!--<p style='padding-bottom: 0;'>Text Filter</p>
            <input type='text' class='ewim-w-35p' placeholder='Filter Text' ng-model='searchList'>-->
            <br />
            <table id='' class='ewim-zebra-base'>
                <thead>
                	<tr style="background: black">
	                    <th>
	                        <a href="#" ng-click="orderByField='inventory_name'; reverseSort=!reverseSort">
	                            Name
	                            <span ng-show="orderByField == 'inventory_name'">
	                                <span ng-show="!reverseSort">^</span>
	                                <span ng-show="reverseSort">v</span>
	                            </span>
	                        </a>
	                    </th>
	                    <th>
	                        <a href="#" ng-click="orderByField='inventory_currency_system'; reverseSort=!reverseSort">
	                            Currency Style
	                            <span ng-show="orderByField == 'inventory_currency_system'">
	                                <span ng-show="!reverseSort">^</span>
	                                <span ng-show="reverseSort">v</span>
	                            </span>
	                        </a>
	                    </th>
	                    <th>
	                        Active
						</th>
	                    <th></th>
	                    <th></th>
                    </tr>
                </thead>
                <tbody>
                	<!--<tr ng-repeat='item in filteredData | filter:searchList | orderBy:orderByField:reverseSort'> <!--Pagination Version-->
                    <tr ng-repeat='inventory in filteredData | filter:searchList | orderBy:orderByField:reverseSort | filter:inventory_name'><!--No Pagination Version-->
                        <td>{{ inventory.inventory_name }}</td>
                        <td>{{ inventory.inventory_currency_system }}</td>
                        <td>{{ inventory.active }}</td>
                        <td>$ewim_editInventoryButton</td>
                        <td>$ewim_activateViewButton</td>
                    </tr>
                </tbody>
            </table>
            <pagination ng-model="currentPage" total-items="data.length" max-size="maxSize" boundary-links="true"></pagination>
        </div>
EOV;
//endregion End Task 3