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
$ewim_activeInventoryID= get_user_meta($ewim_userID, 'active_inventory', true);
//endregion

//region Check that the record user is trying to Undo is theirs
if($_REQUEST['action'] == 'undo'){
	$ewim_recordID= $_REQUEST['record_id'];
	$ewim_aItem= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_ledger WHERE id = $ewim_recordID AND user_id = $ewim_userID AND inventory_id = $ewim_activeInventoryID",ARRAY_A);
	if($ewim_aItem == ''){
		$ewim_content.= "Ledger Record requested does not exist, is not associated with active inventory, or does not belong to you. Please try again.";
		return;
	}
	else{
		ewim_undo_ledger_entry('ewim_ledger',$ewim_recordID);
		//$ewim_undoMessage= ewim_undo_ledger_entry('ewim_ledger',$ewim_recordID);
	}
}
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

			$ewim_totalDifference+= $ewim_aLedgerRecord['transaction_credit_debit'];
			$ewim_aLedgerRecord['transaction_credit_debit']= number_format($ewim_aLedgerRecord['transaction_credit_debit'],2,'.',',');

			switch($ewim_aLedgerRecord['transaction_type']){
				case 'Buy':
					//Calculate total Profit Loss
					//$ewim_totalDifference= $ewim_totalDifference - $ewim_aLedgerRecord['transaction_currency_amount'];

					$ewim_aLedgerRecord['average_cost']= '-'.number_format(ewim_do_math('/', $ewim_aLedgerRecord['transaction_currency_amount'],$ewim_aLedgerRecord['transaction_item_amount']),2,'.',',').' '.$ewim_currency;
					$ewim_aLedgerRecord['transaction_currency_amount']= '-'.number_format($ewim_aLedgerRecord['transaction_currency_amount'],2,'.',',').' '.$ewim_currency;

					$ewim_aLedgerRecord['total_production_cost']= 'N/A';
					$ewim_aLedgerRecord['average_production_cost']= 'N/A';

					$ewim_aLedgerRecord['broker_fees']= 'N/A';
					$ewim_aLedgerRecord['sales_tax']= 'N/A';

					if($ewim_aLedgerRecord['transaction_credit_debit'] < 0){
						$ewim_aLedgerRecord['average_difference']= '-'.number_format(ewim_do_math('/', abs($ewim_aLedgerRecord['transaction_credit_debit']), $ewim_aLedgerRecord['transaction_item_amount']),2,'.',',');
					}
					else {
						$ewim_aLedgerRecord['average_difference']= number_format(ewim_do_math('/', $ewim_aLedgerRecord['transaction_credit_debit'], $ewim_aLedgerRecord['transaction_item_amount']),2,'.',',');
					}

					$ewim_undoLink= "<a href='?action=undo&record_id={{record.id}}'>Undo</a>";

					break;
				case 'Sell':
					//Calculate total Profit Loss
					//$ewim_totalDifference = $ewim_totalDifference + $ewim_aLedgerRecord['transaction_currency_amount'] - $ewim_aLedgerRecord['total_production_cost'] - $ewim_aLedgerRecord['broker_fees'] -$ewim_aLedgerRecord['sales_tax'];

					$ewim_aLedgerRecord['average_cost']= '+'.number_format(ewim_do_math('/', $ewim_aLedgerRecord['transaction_currency_amount'],$ewim_aLedgerRecord['transaction_item_amount']),2,'.',',').' '.$ewim_currency;
					$ewim_aLedgerRecord['transaction_currency_amount']= '+'.number_format($ewim_aLedgerRecord['transaction_currency_amount'],2,'.',',').' '.$ewim_currency;

					$ewim_aLedgerRecord['average_production_cost']= number_format(ewim_do_math('/',$ewim_aLedgerRecord['total_production_cost'], $ewim_aLedgerRecord['transaction_item_amount']),2,'.',',').' '.$ewim_currency;
					$ewim_aLedgerRecord['total_production_cost']= number_format($ewim_aLedgerRecord['total_production_cost'],2,'.',',').' '.$ewim_currency;

					$ewim_aLedgerRecord['broker_fees']= number_format($ewim_aLedgerRecord['broker_fees'],2,'.',',').' '.$ewim_currency;
					$ewim_aLedgerRecord['sales_tax']= number_format($ewim_aLedgerRecord['sales_tax'],2,'.',',').' '.$ewim_currency;

					if($ewim_aLedgerRecord['transaction_credit_debit'] < 0){
						$ewim_aLedgerRecord['average_difference']= '-'.number_format(ewim_do_math('/', abs($ewim_aLedgerRecord['transaction_credit_debit']), $ewim_aLedgerRecord['transaction_item_amount']),2,'.',',');
					}
					else {
						$ewim_aLedgerRecord['average_difference']= number_format(ewim_do_math('/', $ewim_aLedgerRecord['transaction_credit_debit'], $ewim_aLedgerRecord['transaction_item_amount']),2,'.',',');
						$ewim_aLedgerRecord['average_difference']= ewim_do_math('/', $ewim_aLedgerRecord['transaction_credit_debit'], $ewim_aLedgerRecord['transaction_item_amount']);
					}

					$ewim_undoLink= "<a href='?action=undo&record_id={{record.id}}'>Undo</a>";

					break;
				case 'Manufacture':
					//Calculate total Profit Loss
					//$ewim_totalDifference = $ewim_totalDifference - $ewim_aLedgerRecord['transaction_currency_amount'];

					$ewim_aLedgerRecord['average_cost']= '-'.number_format(ewim_do_math('/', $ewim_aLedgerRecord['transaction_currency_amount'],$ewim_aLedgerRecord['transaction_item_amount']),2,'.',',').' '.$ewim_currency;
					$ewim_aLedgerRecord['transaction_currency_amount']= '-'.number_format($ewim_aLedgerRecord['transaction_currency_amount'],2,'.',',').' '.$ewim_currency;

					$ewim_aLedgerRecord['average_production_cost']= number_format(ewim_do_math('/',$ewim_aLedgerRecord['total_production_cost'], $ewim_aLedgerRecord['transaction_item_amount']),2,'.',',').' '.$ewim_currency;
					$ewim_aLedgerRecord['total_production_cost']= number_format($ewim_aLedgerRecord['total_production_cost'],2,'.',',').' '.$ewim_currency;

					$ewim_aLedgerRecord['broker_fees']= 'N/A';
					$ewim_aLedgerRecord['sales_tax']= 'N/A';

					if($ewim_aLedgerRecord['transaction_credit_debit'] < 0){
						$ewim_aLedgerRecord['average_difference']= '-'.number_format(ewim_do_math('/', abs($ewim_aLedgerRecord['transaction_credit_debit']), $ewim_aLedgerRecord['transaction_item_amount']),2,'.',',');
					}
					else {
						$ewim_aLedgerRecord['average_difference']= number_format(ewim_do_math('/', $ewim_aLedgerRecord['transaction_credit_debit'], $ewim_aLedgerRecord['transaction_item_amount']),2,'.',',');
					}

					$ewim_undoLink= "Coming in another Version";

					break;
				case 'Process':
					//Calculate total Profit Loss
					//$ewim_totalDifference = $ewim_totalDifference - $ewim_aLedgerRecord['transaction_currency_amount'];

					$ewim_aLedgerRecord['average_cost']= 'N/A';
					$ewim_aLedgerRecord['transaction_currency_amount']= '-'.number_format($ewim_aLedgerRecord['transaction_currency_amount'],2,'.',',').' '.$ewim_currency;

					$ewim_aLedgerRecord['total_production_cost']= number_format($ewim_aLedgerRecord['total_production_cost'],2,'.',',').' '.$ewim_currency;
					$ewim_aLedgerRecord['average_production_cost']= 'N/A';

					$ewim_aLedgerRecord['broker_fees']= 'N/A';
					$ewim_aLedgerRecord['sales_tax']= 'N/A';

					if($ewim_aLedgerRecord['transaction_credit_debit'] < 0){
						$ewim_aLedgerRecord['average_difference']= '-'.number_format(ewim_do_math('/', abs($ewim_aLedgerRecord['transaction_credit_debit']), $ewim_aLedgerRecord['transaction_item_amount']),2,'.',',');
					}
					else {
						$ewim_aLedgerRecord['average_difference']= number_format(ewim_do_math('/', $ewim_aLedgerRecord['transaction_credit_debit'], $ewim_aLedgerRecord['transaction_item_amount']),2,'.',',');
					}

					$ewim_undoLink= "Coming in another Version";

					break;
				case 'Copy Design':
					//Calculate total Profit Loss
					//$ewim_totalDifference = $ewim_totalDifference - $ewim_aLedgerRecord['transaction_currency_amount'];

					$ewim_aLedgerRecord['average_cost']= '-'.number_format(ewim_do_math('/', $ewim_aLedgerRecord['transaction_currency_amount'],$ewim_aLedgerRecord['transaction_item_amount']),2,'.',',').' '.$ewim_currency;
					$ewim_aLedgerRecord['transaction_currency_amount']= '-'.number_format($ewim_aLedgerRecord['transaction_currency_amount'],2,'.',',').' '.$ewim_currency;

					$ewim_aLedgerRecord['total_production_cost']= 'N/A';
					$ewim_aLedgerRecord['average_production_cost']= 'N/A';

					$ewim_aLedgerRecord['broker_fees']= 'N/A';
					$ewim_aLedgerRecord['sales_tax']= 'N/A';

					if($ewim_aLedgerRecord['transaction_credit_debit'] < 0){
						$ewim_aLedgerRecord['average_difference']= '-'.number_format(ewim_do_math('/', abs($ewim_aLedgerRecord['transaction_credit_debit']), $ewim_aLedgerRecord['transaction_item_amount']),2,'.',',');
					}
					else {
						$ewim_aLedgerRecord['average_difference']= number_format(ewim_do_math('/', $ewim_aLedgerRecord['transaction_credit_debit'], $ewim_aLedgerRecord['transaction_item_amount']),2,'.',',');
					}

					$ewim_undoLink= "WIP";

					break;
			}
			break;
		case 'Triple Currency System':
			break;
	}

	$ewim_aLedgerRecord['ledger_count']= $ewim_ledgerRecordCount;
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
        <h4>Profit/Loss: $ewim_totalDifference $ewim_currency</h4>
        <br />
        <table id='' class='ewim-zebra-base ewim-text-center'>
                <thead>
	                <tr style="background: black">
	                    <th class="ewim-text-center ewim-w-5p">Rec #</th>
	                    <th class="ewim-text-center ewim-w-5p">Type</th>
	                    <th class="ewim-text-center ewim-w-14p">Item</th>
	                    <th class="ewim-text-center ewim-w-5p">Quantity</th>
						<th class="ewim-text-center ewim-w-14p">Transaction Amount</th>
	                    <th class="ewim-text-center ewim-w-14p ewim-ledgerDetails-tooltip">
	                    	Production Cost*
	                    	<span class="ewim-ledgerDetails-tooltip-text">Resources Cost + Manufacturing Cost</span>
	                    </th>
						<th class="ewim-text-center ewim-w-14p">Broker Fees</th>
						<th class="ewim-text-center ewim-w-14p">Sales Tax</th>
	                    <th class="ewim-text-center ewim-w-14p ewim-ledgerDetails-tooltip">
	                    	Debit/Credit*
	                    	<span class="ewim-ledgerDetails-tooltip-text" style="margin-top: -3.3%;">Transaction Amount - Production Cost - Broker Fees - Sales Tax</span>
	                    </th>
	                    <th>
	                    	Actions
						</th>
					</tr>
                </thead>
                <tbody>
                	<tr ng-repeat='record in filteredData | filter:searchList'><!--Pagination Version-->
                    <!--<tr ng-repeat='record in data | filter:searchList | orderBy:orderByField:true'><!--No Pagination Version-->
                    	<td>{{ record.ledger_count }}</td>
                        <td>{{ record.transaction_type }}</td>
                        <td>{{ record.item_name }}</td>                       
                        <td>{{ record.transaction_item_amount }}</td>
                        <td class="ewim-ledgerDetails-tooltip">
                        	{{ record.transaction_currency_amount }}*
                        	<span class="ewim-ledgerDetails-tooltip-text">Average: {{ record.average_cost }}</span>
                        </td>
                        <td class="ewim-ledgerDetails-tooltip">
                        	{{ record.total_production_cost }}*
                        	<span class="ewim-ledgerDetails-tooltip-text">Average: {{ record.average_production_cost }}</span>
                        </td>
                        <td>{{ record.broker_fees }}</td>
                        <td>{{ record.sales_tax }}</td>
                        <td class="ewim-ledgerDetails-tooltip">
                        	{{ record.transaction_credit_debit }}*
                        	<span class="ewim-ledgerDetails-tooltip-text">Average: {{ record.average_difference }}</span>
                        </td>
                        <td>
                        	$ewim_undoLink
						</td>
                    </tr>
                </tbody>
            </table>
            <pagination ng-model="currentPage" total-items="data.length" max-size="maxSize" boundary-links="true"></pagination><!--Pagination Version-->
            <p>*Popup window available</p>
        </div>
EOV;
//endregion