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
$ewim_activeGameID= get_user_meta($ewim_userID, 'active_game', true);
$ewim_activeGameName= get_user_meta($ewim_userID, 'active_game_name', true);
$ewim_activeGameSystem= get_user_meta($ewim_userID, 'active_game_system', true);
//endregion

//region Step 1: Get All items belonging to currently active game. Loop and round Costs. Encode to json for display
$ewim_aItems= $wpdb->get_results("SELECT * FROM $ewim_tables->ewim_items WHERE user_id = $ewim_userID AND game_id = $ewim_activeGameID",ARRAY_A);//Get Items

foreach ($ewim_aItems as &$ewim_aItem){
	$ewim_aItem['average_cost']= ($ewim_aItem['item_inventory_quantity'] > 0 ? number_format($ewim_aItem['cost'] / $ewim_aItem['item_inventory_quantity'], 4, '.', ',') : 0.00);

	$ewim_aItem['cost']= number_format($ewim_aItem['cost'],2,'.',',');

	$ewim_aItem['item_inventory_quantity']= number_format($ewim_aItem['item_inventory_quantity'],0,'.',',');

	if($ewim_aItem['item_recipe_ingredients'] != ''){
		$ewim_aItem['recipe']= 'Yes, hover to see';
		$ewim_aItem['recipe_items'];

		$ewim_aItem['item_recipe_ingredients']= json_decode($ewim_aItem['item_recipe_ingredients'], true);
		foreach ($ewim_aItem['item_recipe_ingredients'] as $ewim_key => $ewim_value){
			$ewim_aItem['recipe_items'].= "$ewim_key, ";
		}
		$ewim_aItem['recipe_items']= substr($ewim_aItem['recipe_items'], 0, -2);
	}
	else{
		$ewim_aItem['recipe']= 'No';
	}
}

$ewim_jsonData= json_encode($ewim_aItems);//Encode to json
//endregion

//region Step 2: Create links and buttons
//View Item Button
$ewim_itemPageURL= get_permalink(get_page_by_title($ewim_get_options->ewim_itemPage));
//New Item Link
$ewim_itemFormPageURL= get_permalink(get_page_by_title($ewim_get_options->ewim_itemFormPage));
//endregion

//region Step 3: Game System Variables
switch ($ewim_activeGameSystem){
	case "Eve":
		$ewim_currency= "ISK";
		break;
}
//endregion

//region Final Step: Angular Set Up
$ewim_defaultOrderBy= 'item_name';
$ewim_usePagination= 0;
require_once( __DIR__."/../js/angular.php" );
$ewim_content.= <<<EOV
    <div id='itemList' ng-app='listApp' ng-controller='listController' class='default-padding'>
        <h1>$ewim_activeGameName Items <a href='$ewim_itemFormPageURL?game_id=$ewim_activeGameID'><i class='fa fa-plus-circle' aria-hidden='true'></i></a></h1>     
        <h4>{{data.length}} Items</h4>
            <table id='table-no-border table-elements-no-border' class='table-no-border table-zebra font-bold'>
            	<thead>
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
                    	<input type='text' class='w-35p' placeholder='Filter' ng-model='byName'>
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
                    	<input type='text' class='w-35p' placeholder='Filter' ng-model='by_category'>
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
                </thead>
                <tbody>
                	<!--<tr ng-repeat='item in filteredData | filter:searchList | orderBy:orderByField:reverseSort'> <!--Pagination Version-->
                    <tr ng-repeat="item in data | filter:searchList | filter:{item_name:byName} | filter:{category:by_category} | filter:{item_average_cost:by_averageCost} | filter:{item_inventory_quantity:by_inventory} | orderBy:orderByField:reverseSort">
                        <td><a href="$ewim_itemPageURL?item_id={{ item.id }}">{{ item.item_name }}</a></td>
                        <td>{{ item.category }}</td>
                        <td>{{ item.item_inventory_quantity}}</td>
                        <td>{{ item.cost }} $ewim_currency</td>
                        <td>{{ item.average_cost }} $ewim_currency</td>
                        <td class="recipe-tooltip">
                        	{{ item.recipe }}
                        	<span class="recipe-tooltip-text">{{ item.recipe_items }}</span>
                        </td>
                        <td><a href="$ewim_itemPageURL?item_id={{ item.id }}">View Item</a></td>                   
                    </tr>
                </tbody>
            </table>
            <!--<pagination ng-model="currentPage" total-items="data.length" max-size="maxSize" boundary-links="true"></pagination>-->
        </div>
EOV;
//endregion
