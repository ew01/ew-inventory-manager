<?php
/**
 * Created by PhpStorm.
 * Author: David
 * Date: 3/1/2019
 * Time: 11:39
 * Name:
 * Desc:
 */





//region Global Variables, Classes, Class Variables, Local Variables
global $wpdb;

$ewim_tables= new ewim_tables();
$ewim_get_options= new ewim_get_options();
$ewim_debug_settings= new ewim_debug_settings();

$ewim_totalDifference= 0;
//endregion

//region Get Ledger Records
$ewim_activeInventoryID= get_user_meta($ewim_userID, 'active_inventory', true);
$ewim_aLedgerRecords= $wpdb->get_results("SELECT * FROM $ewim_tables->ewim_ledger WHERE user_id = '$ewim_userID' AND inventory_id= '$ewim_activeInventoryID' ORDER BY id DESC",ARRAY_A);
$ewim_aInventory= $wpdb->get_row( "SELECT * FROM $ewim_tables->ewim_games WHERE id = $ewim_activeInventoryID", ARRAY_A );//Get Inventory
$ewim_aInventoryCurrencies= json_decode($ewim_aInventory['inventory_currencies'], true);
//endregion

//region Loop Records
$ewim_ledgerRecordCount= count($ewim_aLedgerRecords);
foreach($ewim_aLedgerRecords as &$ewim_aLedgerRecord){
	switch ($ewim_aInventory['inventory_currency_system']){
		case 'Single Currency System':
			//Currency Label
			$ewim_currency= $ewim_aInventoryCurrencies['inventory_currency'];

			//Get Item Record for Item Name
			$ewim_itemID= $ewim_aLedgerRecord['item_id'];
			$ewim_aItem= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_items WHERE id = $ewim_itemID",ARRAY_A);
			$ewim_aLedgerRecord['item_name']= $ewim_aItem['item_name'];

			$ewim_aLedgerRecord['difference']= number_format($ewim_aLedgerRecord['difference'],2,'.',',');

			switch($ewim_aLedgerRecord['transaction_type']){
				case 'Buy':
					$ewim_aLedgerRecord['total_cost']= '-'.number_format($ewim_aLedgerRecord['total_cost'],2,'.',',').' '.$ewim_currency;
					$ewim_aLedgerRecord['average_cost']= '-'.number_format($ewim_aLedgerRecord['average_cost'],2,'.',',').' '.$ewim_currency;

					$ewim_aLedgerRecord['total_production_cost']= 'N/A';
					$ewim_aLedgerRecord['average_production_cost']= 'N/A';

					$ewim_aLedgerRecord['broker_fees']= 'N/A';
					$ewim_aLedgerRecord['sales_tax']= 'N/A';
					break;
				case 'Sell':
					$ewim_aLedgerRecord['total_cost']= '+'.number_format($ewim_aLedgerRecord['total_cost'],2,'.',',').' '.$ewim_currency;
					$ewim_aLedgerRecord['average_cost']= '+'.number_format($ewim_aLedgerRecord['average_cost'],2,'.',',').' '.$ewim_currency;

					$ewim_aLedgerRecord['total_production_cost']= number_format($ewim_aLedgerRecord['total_production_cost'],2,'.',',').' '.$ewim_currency;
					$ewim_aLedgerRecord['average_production_cost']= number_format($ewim_aLedgerRecord['average_production_cost'],2,'.',',').' '.$ewim_currency;

					$ewim_aLedgerRecord['broker_fees']= number_format($ewim_aLedgerRecord['broker_fees'],2,'.',',').' '.$ewim_currency;
					$ewim_aLedgerRecord['sales_tax']= number_format($ewim_aLedgerRecord['sales_tax'],2,'.',',').' '.$ewim_currency;
					break;
				case 'Manufacture':
					$ewim_aLedgerRecord['total_cost']= '-'.number_format($ewim_aLedgerRecord['total_cost'],2,'.',',').' '.$ewim_currency;
					$ewim_aLedgerRecord['average_cost']= '-'.number_format($ewim_aLedgerRecord['average_cost'],2,'.',',').' '.$ewim_currency;

					$ewim_aLedgerRecord['total_production_cost']= number_format($ewim_aLedgerRecord['total_production_cost'],2,'.',',').' '.$ewim_currency;
					$ewim_aLedgerRecord['average_production_cost']= number_format($ewim_aLedgerRecord['average_production_cost'],2,'.',',').' '.$ewim_currency;

					$ewim_aLedgerRecord['broker_fees']= 'N/A';
					$ewim_aLedgerRecord['sales_tax']= 'N/A';
					break;
				case 'Process':
					$ewim_aLedgerRecord['total_cost']= '-'.number_format($ewim_aLedgerRecord['total_cost'],2,'.',',').' '.$ewim_currency;
					$ewim_aLedgerRecord['average_cost']= 'N/A';

					$ewim_aLedgerRecord['total_production_cost']= number_format($ewim_aLedgerRecord['total_production_cost'],2,'.',',').' '.$ewim_currency;
					$ewim_aLedgerRecord['average_production_cost']= 'N/A';

					$ewim_aLedgerRecord['broker_fees']= 'N/A';
					$ewim_aLedgerRecord['sales_tax']= 'N/A';
					break;
				case 'Copy':
					$ewim_aLedgerRecord['total_cost']= '-'.number_format($ewim_aLedgerRecord['total_cost'],2,'.',',').' '.$ewim_currency;
					$ewim_aLedgerRecord['average_cost']= '-'.number_format($ewim_aLedgerRecord['average_cost'],2,'.',',').' '.$ewim_currency;

					$ewim_aLedgerRecord['total_production_cost']= 'N/A';
					$ewim_aLedgerRecord['average_production_cost']= 'N/A';

					$ewim_aLedgerRecord['broker_fees']= 'N/A';
					$ewim_aLedgerRecord['sales_tax']= 'N/A';
					break;
			}

			//Calculate total Profit Loss
			$ewim_totalDifference = $ewim_totalDifference + $ewim_aLedgerRecord['total_cost'];
			break;
		case 'Triple Currency System':
			break;
	}

	$ewim_aLedgerRecord['id']= $ewim_ledgerRecordCount;
	$ewim_ledgerRecordCount--;
}
//endregion

//region Format Total Difference
$ewim_totalDifference= number_format($ewim_totalDifference,2,'.',',');
//endregion

//region Angular Set Up
$ewim_jsonData= json_encode($ewim_aLedgerRecords);//Encode to json
//$ewim_defaultOrderBy= 'id';
$ewim_usePagination= 1;
//$ewim_ajsResultsPerPage= 20;
require_once( __DIR__."/../js/angular.php" );
//endregion

//region Display
$ewim_content.= <<<EOV
    <div id='ledgerList' ng-app='listApp' ng-controller='listController' class='default-padding'>
        <h1>Ledger</h1>     
        <h4>{{data.length}} Records</h4>
        <h4>Profit/Loss: $ewim_totalDifference</h4>
        <br />
        <table id='' class='ewim-zebra-base ewim-text-center'>
                <thead>
	                <tr style="background: black">
	                    <th class="ewim-text-center ewim-w-5p">Rec #</th>
	                    <th class="ewim-text-center ewim-w-5p">Type</th>
	                    <th class="ewim-text-center ewim-w-14p">Item</th>
	                    <th class="ewim-text-center ewim-w-5p">Quantity</th>
						<th class="ewim-text-center ewim-w-14p">Debit/Credit</th>
	                    <th class="ewim-text-center ewim-w-14p ewim-ledgerDetails-tooltip">
	                    	Production Cost*
	                    	<span class="ewim-ledgerDetails-tooltip-text">Resources Cost + Manufacturing Cost</span>
	                    </th>
						<th class="ewim-text-center ewim-w-14p">Broker Fees</th>
						<th class="ewim-text-center ewim-w-14p">Sales Tax</th>
	                    <th class="ewim-text-center ewim-w-14p ewim-ledgerDetails-tooltip">
	                    	Difference*
	                    	<span class="ewim-ledgerDetails-tooltip-text" style="margin-top: -3.3%;">Debit/Credit - Production Cost - Broker Fees - Sales Tax</span>
	                    </th>
					</tr>
                </thead>
                <tbody>
                	<tr ng-repeat='record in filteredData | filter:searchList'><!--Pagination Version-->
                    <!--<tr ng-repeat='record in data | filter:searchList | orderBy:orderByField:true'><!--No Pagination Version-->
                    	<td>{{ record.id }}</td>
                        <td>{{ record.transaction_type }}</td>
                        <td>{{ record.item_name }}</td>                       
                        <td>{{ record.item_amount }}</td>
                        <td class="ewim-ledgerDetails-tooltip">
                        	{{ record.total_cost }}*
                        	<span class="ewim-ledgerDetails-tooltip-text">Average: {{ record.average_cost }}</span>
                        </td>
                        <td class="ewim-ledgerDetails-tooltip">
                        	{{ record.total_production_cost }}*
                        	<span class="ewim-ledgerDetails-tooltip-text">Average: {{ record.average_production_cost }}</span>
                        </td>
                        <td>{{ record.broker_fees }}</td>
                        <td>{{ record.sales_tax }}</td>
                        <td>{{ record.difference }}</td>
                    </tr>
                </tbody>
            </table>
            <pagination ng-model="currentPage" total-items="data.length" max-size="maxSize" boundary-links="true"></pagination><!--Pagination Version-->
            <p>*Popup window available</p>
        </div>
EOV;
//endregion