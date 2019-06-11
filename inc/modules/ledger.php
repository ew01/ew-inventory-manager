<?php
/**
 * Created by PhpStorm.
 * Author: David
 * Date: 3/1/2019
 * Time: 11:39
 * Name:
 * Desc:
 */





//region declare needed class objects
global $wpdb;
$ewim_tables= new ewim_tables();
$ewim_get_options= new ewim_get_options();
$ewim_debug_settings= new ewim_debug_settings();
//endregion

//region Step 1: Get the records from the Ledger and display the transactions
$ewim_activeGameID= get_user_meta($ewim_userID, 'active_game', true);
$ewim_aLedgerRecords= $wpdb->get_results("SELECT * FROM $ewim_tables->ewim_ledger WHERE user_id = $ewim_userID AND game_id= $ewim_activeGameID ORDER BY id DESC",ARRAY_A);

foreach($ewim_aLedgerRecords as &$ewim_aLedgerRecord){

	$ewim_totalDifference= $ewim_totalDifference + $ewim_aLedgerRecord['difference'];

	$ewim_itemID= $ewim_aLedgerRecord['item_id'];
	$ewim_aItem= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_items WHERE id = $ewim_itemID",ARRAY_A);
	$ewim_aLedgerRecord['item_name']= $ewim_aItem['item_name'];
	$ewim_aLedgerRecord['average_production_cost']= number_format($ewim_aLedgerRecord['average_production_cost'],2,'.',',');
	$ewim_aLedgerRecord['total_production_cost']= number_format($ewim_aLedgerRecord['total_production_cost'],2,'.',',');
	$ewim_aLedgerRecord['average_sbpm_cost']= number_format($ewim_aLedgerRecord['average_sbpm_cost'],2,'.',',');
	$ewim_aLedgerRecord['total_sbpm_cost']= number_format($ewim_aLedgerRecord['total_sbpm_cost'],2,'.',',');
	@$ewim_aLedgerRecord['broker_fees']= number_format($ewim_aLedgerRecord['broker_fees'],2,'.',',');
	$ewim_aLedgerRecord['sales_tax']= number_format($ewim_aLedgerRecord['sales_tax'],2,'.',',');
	$ewim_aLedgerRecord['difference']= number_format($ewim_aLedgerRecord['difference'],2,'.',',');
}

$ewim_totalDifference= number_format($ewim_totalDifference,2,'.',',');

$ewim_jsonData= json_encode($ewim_aLedgerRecords);//Encode to json

//Debug Setting
$ewim_gamesDebug= 0;
if($ewim_gamesDebug == 1){
	echo "<h1>Games Array</h1>";
	echo "<pre>";
	print_r($ewim_aLedger);
	echo "</pre>";
	exit;
}
//endregion

//Start Task 2: Angular Set Up
//$ewim_defaultOrderBy= 'id';
$ewim_usePagination= 1;
//$ewim_ajsResultsPerPage= 20;
require_once( __DIR__."/../js/angular.php" );
$ewim_content.= <<<EOV
    <div id='ledgerList' ng-app='listApp' ng-controller='listController' class='default-padding'>
        <h1>Ledger</h1>     
        <h4>{{data.length}} Records</h4>
        <h4>Profit/Loss: $ewim_totalDifference</h4>
        <h4>Costs are reduced to smallest coin. Full Coinage in future update</h4>
            <p style='padding-bottom: 0;'>Text Filter</p>
            <input type='text' class='w-35p' placeholder='Filter Text' ng-model='searchList'>
            <br />
            <table id='table-no-border table-elements-no-border' class='table-no-border table-zebra font-bold'>
                <thead>
                	<th>Record Number</th>
                    <th>
                    	Item
                    </th>
                    <th>
                    	Transaction
                    </th>
                    <th>
                    	Item Amount
					</th>
                    <th>
                    	Average Production Cost
                    </th>
                    <th>
                    	Total Production Cost
                    </th>
                    <th>
                    	Average Sale
					</th>
					<th>
                    	Total Sale
					</th>
					<th>
                    	Broker Fees
					</th>
					<th>
                    	Sales Tax
					</th>
                    <th>
                    	Difference
					</th>
                </thead>
                <tbody>
                	<tr ng-repeat='record in filteredData | filter:searchList'><!--Pagination Version-->
                    <!--<tr ng-repeat='record in data | filter:searchList | orderBy:orderByField:true'><!--No Pagination Version-->
                    	<td>{{ record.id }}</td>
                        <td>{{ record.item_name }}</td>
                        <td>{{ record.transaction_type }}</td>
                        <td>{{ record.item_amount }}</td>
                        <td>{{ record.average_production_cost }}</td>
                        <td>{{ record.total_production_cost }}</td>
                        <td>{{ record.average_sbpm_cost }}</td>
                        <td>{{ record.total_sbpm_cost }}</td>
                        <td>{{ record.broker_fees }}</td>
                        <td>{{ record.sales_tax }}</td>
                        <td>{{ record.difference }}</td>
                    </tr>
                </tbody>
            </table>
            <pagination ng-model="currentPage" total-items="data.length" max-size="maxSize" boundary-links="true"></pagination><!--Pagination Version-->
        </div>
EOV;
//End Task 2