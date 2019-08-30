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
$ewim_aPostedItems= $wpdb->get_results("SELECT * FROM $ewim_tables->ewim_posted WHERE user_id = $ewim_userID AND game_id = $ewim_activeGameID",ARRAY_A);//Get Items

foreach ($ewim_aPostedItems as &$ewim_aPostedItem){
	$ewim_aPostedItem['average']= number_format($ewim_aPostedItem['average'], 2, '.', ',');
	$ewim_itemID= $ewim_aPostedItem['item_id'];
	$ewim_aItem= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_items WHERE id = $ewim_itemID AND user_id = $ewim_userID",ARRAY_A);
	$ewim_aPostedItem['item_name']= $ewim_aItem['item_name'];
}

$ewim_jsonData= json_encode($ewim_aPostedItems);//Encode to json
//endregion

//region Step 2: Create links and buttons
//View Item Button
$ewim_sellPosted= get_permalink(get_page_by_title($ewim_get_options->ewim_postPage));
$ewim_viewItemButton="
<form action='$ewim_sellPosted' method='post' style='display: inline'>
	<input type='hidden' name='record_id' value='{{ item.id }}'>
	<button style='border:0;padding:0;display:inline;background:none;color:#55cbff;'>View Post</button>
</form>
";
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
$ewim_defaultOrderBy= 'name';
$ewim_usePagination= 0;
require_once( __DIR__."/../js/angular.php" );
$ewim_content.= <<<EOV
    <div id='itemList' ng-app='listApp' ng-controller='listController' class='default-padding'>
        <h1>$ewim_activeGameName Items <a href='$ewim_itemFormPageURL?game_id=$ewim_activeGameID'><i class='fa fa-plus-circle' aria-hidden='true'></i></a></h1>     
        <h4>{{data.length}} Items</h4>
            <table id='table-ewim-no-border table-elements-ewim-no-border' class='table-ewim-no-border table-zebra font-bold'>
            	<thead>
            		<!--Name-->
                    <th>
                    	<a href="#" ng-click="orderByField='name'; reverseSort=!reverseSort">
                    		Name
                    		<span ng-show="orderByField == 'name'">
                    			<span ng-show="!reverseSort">^</span>
                    			<span ng-show="reverseSort">v</span>
                    		</span>
                    	</a>
                    	<br />
                    	<input type='text' class='ewim-w-35p' placeholder='Filter' ng-model='byName'>
                    </th>
                    <!--Average Production Cost-->
                    <th>
                    	<a href="#" ng-click="orderByField='average'; reverseSort=!reverseSort">
                    		Average Production Cost
                    		<span ng-show="orderByField == 'average'">
                    			<span ng-show="!reverseSort">^</span>
                    			<span ng-show="reverseSort">v</span>
                    		</span>
                    	</a>
                    </th>
                    <!--Amount -->
                    <th>
                    	<a href="#" ng-click="orderByField='amount'; reverseSort=!reverseSort">
                    		Amount
                    		<span ng-show="orderByField == 'amount'">
                    			<span ng-show="!reverseSort">^</span>
                    			<span ng-show="reverseSort">v</span>
                    		</span>
                    	</a>
                    </th>
                    <!--Broker Fee-->
                    <th>
                    	<a href="#" ng-click="orderByField='broker_fee'; reverseSort=!reverseSort">
                    		Broker Fee
                    		<span ng-show="orderByField == 'broker_fee'">
                    			<span ng-show="!reverseSort">^</span>
                    			<span ng-show="reverseSort">v</span>
                    		</span>
                    	</a>
                    </th>
                    <!--Status-->
                    <th>
                    	<a href="#" ng-click="orderByField='status'; reverseSort=!reverseSort">
                    		Status
                    		<span ng-show="orderByField == 'status'">
                    			<span ng-show="!reverseSort">^</span>
                    			<span ng-show="reverseSort">v</span>
                    		</span>
                    	</a>
                    	<br />
                    	<input type='text' class='ewim-w-35p' placeholder='Filter' ng-model='byStatus'>
                    </th>
                    <!--Posted At-->
                    <th>
                    	<a href="#" ng-click="orderByField='post_price'; reverseSort=!reverseSort">
                    		Posted At
                    		<span ng-show="orderByField == 'post_price'">
                    			<span ng-show="!reverseSort">^</span>
                    			<span ng-show="reverseSort">v</span>
                    		</span>
                    	</a>
                    	
                    </th>
                    <!--View-->
                    <th>
                    	
                    </th>
                </thead>
                <tbody>
                	<!--<tr ng-repeat='item in filteredData | filter:searchList | orderBy:orderByField:reverseSort'> <!--Pagination Version-->
                    <tr ng-repeat="item in data | filter:searchList | filter:{item_name:byName} | filter:{status:byStatus} | orderBy:'item_name' | orderBy:orderByField:reverseSort">
                        <td>{{ item.item_name }}</td>                        
                        <td>{{ item.average }} $ewim_currency</td>
                        <td>{{ item.amount}}</td>
                        <td>{{ item.broker_fee }}</td>
                        <td>{{ item.status }}</td>
                        <td>{{ item.post_price }}</td>
                        <td><a href='$ewim_sellPosted?record_id={{ item.id }}' >View Post</a></td>                   
                    </tr>
                </tbody>
            </table>
            <!--<pagination ng-model="currentPage" total-items="data.length" max-size="maxSize" boundary-links="true"></pagination>-->
        </div>
EOV;
//endregion