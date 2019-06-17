<?php
/**
 * Created by PhpStorm.
 * Author: David
 * Date: 3/1/2019
 * Time: 10:44
 * Name:
 * Desc:
 */





//region declare needed class objects
global $wpdb;
$ewim_tables= new ewim_tables();
$ewim_get_options= new ewim_get_options();
$ewim_debug_settings= new ewim_debug_settings();
//endregion

//region Step 1: Get the games for the user from the database, mark the active one before json encode
$ewim_aGames= $wpdb->get_results("SELECT * FROM $ewim_tables->ewim_games WHERE user_id = $ewim_userID",ARRAY_A);//Get Games

//region Get Max Games Allowed, display add game accordingly
$ewim_userMaxGames= (get_user_meta($ewim_userID, 'max_games', true) == '' ? 2 : get_user_meta($ewim_userID, 'max_games', true));
if($wpdb->num_rows < $ewim_userMaxGames){
	$ewim_newGameLink= "<a href='$ewim_gameFormPageURL'><i class='fa fa-plus-circle' aria-hidden='true'></i></a>";
}
//endregion

//region Step 1.1: Set up the Active game
if($_REQUEST['action'] == 'activate'){
	//$ewim_current_user
	update_user_meta($ewim_userID, 'active_game', $_REQUEST['game_id']);
	$ewim_activeGameID= $_REQUEST['game_id'];
}
else{
	$ewim_activeGameID= get_user_meta($ewim_userID, 'active_game', true);
}

foreach($ewim_aGames as &$ewim_aGame){
	if($ewim_activeGameID == $ewim_aGame['id']){
		$ewim_aGame['active']= 'Yes';
		update_user_meta($ewim_userID, 'active_game_name', $ewim_aGame['game_name']);
		update_user_meta($ewim_userID, 'active_game_system', $ewim_aGame['game_system']);
	}
	else{
		$ewim_aGame['active']= 'No';
	}
}
//endregion

//region Step 1.2: Encode to Json
$ewim_usePagination= 1;
$ewim_jsonData= json_encode($ewim_aGames);
//endregion

if($ewim_debug_settings->ewim_wpdbSelect == 1){
	echo "<h1>Games List</h1>";
	echo "User ID: " . $ewim_userID . "<br />";
	echo "<pre>";
	print_r($ewim_aGames);
	echo "</pre>";
	exit;
}
//endregion

//region Start Step 2: Create links and buttons
//View Item Button
$ewim_gamePageURL= get_permalink(get_page_by_title($ewim_itemPage));
$ewim_activateGameButton="
<form action='' method='post' style='display: inline'>
	<input type='hidden' name='action' value='activate' />
	<input type='hidden' name='game_id' value='{{ game.id }}' />
	<button style='border:0;padding:0;display:inline;background:none;color:#55cbff;'>Activate</button>
</form>
";

$ewim_viewItemsLink= get_permalink(get_page_by_title($ewim_get_options->ewim_itemListPage));;
$test= $ewim_get_options->ewim_itemListPage;

//New Item Link
$ewim_gameFormPageURL= get_permalink(get_page_by_title($ewim_get_options->ewim_gameFormPage));
//endregion

//Start Task 3: Angular Set Up
$ewim_defaultOrderBy= 'name';
require_once( __DIR__."/../js/angular.php" );
$ewim_content.= <<<EOV
    <div id='gameList' ng-app='listApp' ng-controller='listController' class='default-padding'>
        <h1>Games $ewim_newGameLink</h1>     
        <h4>{{data.length}} Games</h4>
        <p><a href="$ewim_viewItemsLink">View Items for Active Game</a></p>
            <p style='padding-bottom: 0;'>Text Filter</p>
            <input type='text' class='w-35p' placeholder='Filter Text' ng-model='searchList'>
            <br />
            <table id='table-no-border table-elements-no-border' class='table-no-border table-zebra font-bold'>
                <thead>
                    <th>
                    	<a href="#" ng-click="orderByField='name'; reverseSort=!reverseSort">
                    		Name
                    		<span ng-show="orderByField == 'name'">
                    			<span ng-show="!reverseSort">^</span>
                    			<span ng-show="reverseSort">v</span>
                    		</span>
                    	</a>
                    </th>
                    <th>
                    	<a href="#" ng-click="orderByField='item_recipe'; reverseSort=!reverseSort">
                    		Game System
                    		<span ng-show="orderByField == 'item_recipe'">
                    			<span ng-show="!reverseSort">^</span>
                    			<span ng-show="reverseSort">v</span>
                    		</span>
                    	</a>
                    </th>
                    <th>
                    	Active
					</th>
                    <th></th>
                </thead>
                <tbody>
                	<!--<tr ng-repeat='item in filteredData | filter:searchList | orderBy:orderByField:reverseSort'> <!--Pagination Version-->
                    <tr ng-repeat='game in filteredData | filter:searchList | orderBy:orderByField:reverseSort | filter:game_name'><!--No Pagination Version-->
                        <td>{{ game.game_name }}</td>
                        <td>{{ game.game_system }}</td>
                        <td>{{ game.active }}</td>
                        <td>$ewim_activateGameButton</td>
                    </tr>
                </tbody>
            </table>
            <pagination ng-model="currentPage" total-items="data.length" max-size="maxSize" boundary-links="true"></pagination>
        </div>
EOV;
//End Task 3