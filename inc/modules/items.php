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
//endregion

//region Set up the Active inventory
if($_REQUEST['action'] == 'activate'){
	//Update Meta Record with activated inventory ID
	update_user_meta($ewim_userID, 'active_inventory', $_REQUEST['inventory_id']);

	//Get Inventory Record
	$ewim_activeInventoryID= $_REQUEST['inventory_id'];
	$ewim_aInventory= $wpdb->get_row( "SELECT * FROM $ewim_tables->ewim_games WHERE id = $ewim_activeInventoryID", ARRAY_A );

	//Set Inventory Name
	$ewim_activeInventoryName= $ewim_aInventory['inventory_name'];
}
else{
	//Get Inventory Record
	$ewim_activeInventoryID= get_user_meta($ewim_userID, 'active_inventory', true);
	$ewim_aInventory= $wpdb->get_row( "SELECT * FROM $ewim_tables->ewim_games WHERE id = $ewim_activeInventoryID", ARRAY_A );

	//Set Inventory Name
	$ewim_activeInventoryName= $ewim_aInventory['inventory_name'];
}
//endregion

//Check for active Inventory
if($ewim_activeInventoryID == ''){
	$ewim_content.= "<p>Please activate an inventory from the Inventories page</p>";
	return;
}
else{
	//region Get Inventory Record and Items Record, decode
	$ewim_aItems = $wpdb->get_results( "SELECT * FROM $ewim_tables->ewim_items WHERE user_id = $ewim_userID AND inventory_id = $ewim_activeInventoryID", ARRAY_A );//Get Items
	$ewim_aInventoryCurrencies= json_decode($ewim_aInventory['inventory_currencies'], true);
	//endregion

	//region Arrange Items for Display
	foreach ($ewim_aItems as &$ewim_aItem){
		switch ($ewim_aInventory['inventory_currency_system']){
			case 'Single Currency System':
				//Currency Label
				$ewim_currency= $ewim_aInventoryCurrencies['inventory_currency'];

				//Average Item Cost
				$ewim_aItem['average_cost']= ewim_do_math('/',$ewim_aItem['cost'],$ewim_aItem['item_inventory_quantity']);
				$ewim_aItem['average_cost']= number_format( $ewim_aItem['average_cost'], 2, '.', ',' ).' '.$ewim_currency;

				//Total Cost
				$ewim_aItem['cost']= number_format( $ewim_aItem['cost'], 2, '.', ',' ).' '.$ewim_currency;
				break;
			case 'Triple Currency System':
				break;
		}

		//region Inventory Count
		$ewim_aItem['item_inventory_quantity'] = number_format( $ewim_aItem['item_inventory_quantity'], 0, '.', ',' );
		//endregion

		//region Design Details
		if($ewim_aItem['design_details'] != '') {
			$ewim_aItem['recipe'] = 'Yes, hover to see';
			$ewim_aItem['recipe_items'];

			$ewim_aItem['design_details'] = json_decode( $ewim_aItem['design_details'], true );

			if ($ewim_aItem['category'] != 'Raw Resource'){
				foreach ( $ewim_aItem['design_details'] as $ewim_designItemID => $ewim_aDesignItem ) {
					$ewim_aDesignItemDetails = $wpdb->get_row( "SELECT * FROM $ewim_tables->ewim_items WHERE id = $ewim_designItemID", ARRAY_A );//Get Items

					$ewim_aItem['recipe_items'] .= $ewim_aDesignItemDetails['item_name'] . ", ";
				}
			}
			else{
				foreach ( $ewim_aItem['design_details'] as $ewim_designItemID) {
					$ewim_aDesignItemDetails = $wpdb->get_row( "SELECT * FROM $ewim_tables->ewim_items WHERE id = $ewim_designItemID", ARRAY_A );//Get Items

					$ewim_aItem['recipe_items'] .= $ewim_aDesignItemDetails['item_name'] . ", ";
				}
			}
			$ewim_aItem['recipe_items'] = substr( $ewim_aItem['recipe_items'],0 ,-2 );

		}
		else{
			$ewim_aItem['recipe'] = 'No';
		}
		//endregion
	}
	//endregion

	//region Create links and buttons
	//View Item Button
	$ewim_itemPageURL = get_permalink( get_page_by_title( $ewim_get_options->ewim_itemPage ) );
	//New Item Link
	$ewim_itemFormPageURL = get_permalink( get_page_by_title( $ewim_get_options->ewim_itemFormPage ) );
	//endregion

	//region Angular Set Up
	$ewim_jsonData = json_encode( $ewim_aItems );//Encode to json
	$ewim_defaultOrderBy = 'item_name';
	$ewim_usePagination  = 0;
	require_once( __DIR__ . "/../js/angular.php" );
	//endregion

	//region Display
	$ewim_content .= <<<EOV
    <div id='itemList' ng-app='listApp' ng-controller='listController' class='default-padding'>
        <h1>Item List: $ewim_activeInventoryName </h1>     
        <p class="ewim-fs-16">{{data.length}} Items &nbsp; <a href='$ewim_itemFormPageURL?inventory_id=$ewim_activeInventoryID' title="Add Item"><i class='fa fa-plus-circle' aria-hidden='true'></i></a></p>
        <br />    
        <table id='' class='ewim-zebra-base'>
        	<thead>
            	<tr style="background: black">
	            	<!--Name-->
	                <th>
	                	<a href="#" ng-click="orderByField='item_name'; reverseSort=!reverseSort">
	                    	Name
	                        <span ng-show="orderByField == 'item_name'">
	                        	<span ng-show="!reverseSort">^</span>
	                            <span ng-show="reverseSort">v</span>
	                        </span>
	                    </a>
	                 	<br />
	                    <input type='text' class='ewim-w-35p' placeholder='Filter' ng-model='byName'>
	                </th>
	                <!--Category-->
	                <th>
	                	<a href="#" ng-click="orderByField='category'; reverseSort=!reverseSort">
	                    	Category
	                        <span ng-show="orderByField == 'category'">
	                        	<span ng-show="!reverseSort">^</span>
	                            <span ng-show="reverseSort">v</span>
	                        </span>
	                    </a>
	                    <br />
	                    <input type='text' class='ewim-w-35p' placeholder='Filter' ng-model='by_category'>
	                </th>
	                <!--Inventory-->
	                <th>
	                	<a href="#" ng-click="orderByField='item_inventory_quantity'; reverseSort=!reverseSort">
	                    	Inventory Quantity
	                        <span ng-show="orderByField == 'item_inventory_quantity'">
	                        	<span ng-show="!reverseSort">^</span>
	                            <span ng-show="reverseSort">v</span>
	                        </span>
	                    </a>
	                </th>
	                <!--Total Production Cost-->
	                <th>
	                	<a href="#" ng-click="orderByField='cost'; reverseSort=!reverseSort">
	                    	Total Production Cost
	                        <span ng-show="orderByField == 'cost'">
	                        	<span ng-show="!reverseSort">^</span>
	                            <span ng-show="reverseSort">v</span>
	                        </span>
	                    </a>
	                </th>
	                <!--Average Production Cost-->
	                <th>
	                	<a href="#" ng-click="orderByField='average_cost'; reverseSort=!reverseSort">
	                    	Average Production Cost
	                        <span ng-show="orderByField == 'average_cost'">
	                        	<span ng-show="!reverseSort">^</span>
	                            <span ng-show="reverseSort">v</span>
	                        </span>
	                    </a>
	                </th>
	                <!--Recipe-->
	                <th>
	                	<a href="#" ng-click="orderByField='recipe'; reverseSort=!reverseSort">
	                    	Recipe or Contains Minerals
	                    	<span ng-show="orderByField == 'recipe'">
	                        	<span ng-show="!reverseSort">^</span>
	                            <span ng-show="reverseSort">v</span>
	                        </span>
	                    </a>
	                </th>
	                <!--View-->
	                <th></th>
                </tr>
            </thead>
            <tbody>
            	<!--<tr ng-repeat='item in filteredData | filter:searchList | orderBy:orderByField:reverseSort'> <!--Pagination Version-->
                <tr ng-repeat="item in data | filter:searchList | filter:{item_name:byName} | filter:{category:by_category} | filter:{item_average_cost:by_averageCost} | filter:{item_inventory_quantity:by_inventory} | orderBy:orderByField:reverseSort">
                	<td><a href="$ewim_itemPageURL?record_id={{ item.id }}">{{ item.item_name }}</a></td>
                    <td>{{ item.category }}</td>
                    <td>{{ item.item_inventory_quantity}}</td>
                    <td>{{ item.cost }}</td>
                    <td>{{ item.average_cost }}</td>
                    <td class="ewim-recipe-tooltip">
                    	{{ item.recipe }}
                        <span class="ewim-recipe-tooltip-text">{{ item.recipe_items }}</span>
                    </td>
                    <td><a href="$ewim_itemPageURL?record_id={{ item.id }}">View Item</a></td>                   
                </tr>
            </tbody>
        </table>
        <!--<pagination ng-model="currentPage" total-items="data.length" max-size="maxSize" boundary-links="true"></pagination>-->
    </div>
EOV;
	//endregion
}