<?php
/**
 * Created by PhpStorm.
 * Author: David
 * Date: 3/8/2019
 * Time: 20:50
 * Name:
 * Desc:
 */
//todo Function for find profit




/**
 * Name: EWIM WPDB Edit
 * Desc: Uses $WPDB to write to the database. Can be called anywhere in the plugin
 *
 * @param $ewim_action
 * @param $ewim_table
 * @param $ewim_aInsert
 * @param null $ewim_recordID
 *
 * @return array
 */
function ewim_wpdb_edit($ewim_action, $ewim_table, $ewim_aInsert= NULL, $ewim_recordID= NULL){
	//Global Variables and Classes
	global $wpdb;

	//Switch to the requested action
	switch ($ewim_action){
		case "insert":
			$ewim_wpdbEditResult= $wpdb->insert(
				$ewim_table,
				$ewim_aInsert
			);
			break;
		case "update":
			$ewim_wpdbEditResult= $wpdb->update(
				$ewim_table,
				$ewim_aInsert,
				array(
					'id'    => $ewim_recordID//Customer ID in the form
				)
			);
			break;
		case "delete":
			$ewim_wpdbEditResult= $wpdb->delete(
				$ewim_table,
				array(
					'id'    => $ewim_recordID//Customer ID in the form
				)
			);
			break;
		default:
			$ewim_aReturn['error']= 'Error';
			$ewim_aReturn['errorMessage']= 'No Action';
			return $ewim_aReturn;
			break;
	}

	if($ewim_wpdbEditResult === false){
		//It did not Work
		$ewim_aReturn['error']= 'Error';
		$ewim_aReturn['errorMessage']= $wpdb->last_error;
		return $ewim_aReturn;
		/*
			echo "<h1>Insert</h1>";
			$wpdb->show_errors();
			$wpdb->print_error();
			echo "<p>Table: $ewim_table</p>";
			echo "<pre>";
			print_r($ewim_aInsert);
			echo "</pre>";
			exit;
		*/
	}
	else{
		$ewim_aReturn['error']= 'No';
		if($ewim_action == 'insert'){
			$ewim_aReturn['record_id']= $wpdb->insert_id;
		}
		return $ewim_aReturn;
	}
}

/**
 * Name: EWIM Get Meta Value
 * Desc:
 * @param $ewim_metaKey
 * @return array
 */
function ewim_get_meta_value($ewim_metaKey){
	//region Global Variables, Classes, Class Variables, Local Variables
	global $wpdb;

	$ewim_tables= new ewim_tables();

	//endregion


	$ewim_aMetaRecord= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_meta_data WHERE meta_key = '$ewim_metaKey'", ARRAY_A);
	switch ($ewim_aMetaRecord['meta_value_type']){
		case 'json':
			$ewim_aMetaValues= json_decode($ewim_aMetaRecord['meta_value'], true);
			break;
		default:
			$ewim_aMetaValues= explode(',', $ewim_aMetaRecord['meta_value']);
			break;
	}


	return $ewim_aMetaValues;
}

/**
 * Name: Do Math
 *
 * @param $ewim_operation
 * @param $ewim_valueOne
 * @param $ewim_valueTwo
 *
 * @return int
 */
function ewim_do_math($ewim_operation, $ewim_valueOne, $ewim_valueTwo){
	switch ($ewim_operation){
		case "+":
			$ewim_result= $ewim_valueOne;
			foreach($ewim_valueTwo as $ewim_value){
				$ewim_result= $ewim_result + $ewim_value;
			}
			break;
		case "-":
			$ewim_result= $ewim_valueOne;
			foreach($ewim_valueTwo as $ewim_value){
				$ewim_result= $ewim_result - $ewim_value;
			}
			break;
		case '*':
			if($ewim_valueOne > 0 && $ewim_valueTwo > 0){
				$ewim_result= $ewim_valueOne * $ewim_valueTwo;
			}
			/*
			elseif($ewim_valueOne > 0){
				$ewim_result= $ewim_valueOne;
			}
			elseif($ewim_valueTwo > 0){
				$ewim_result= $ewim_valueTwo;
			}
			*/
			else{
				$ewim_result= 0;
			}
			break;
		case "/":
			if($ewim_valueOne > 0 && $ewim_valueTwo > 0){
				$ewim_result= $ewim_valueOne / $ewim_valueTwo;
			}
			else{
				$ewim_result= 0;
			}
			break;
	}
	return $ewim_result;
}

/**
 * Name: Undo Record
 *
 * @param $ewim_table_name
 * @param $ewim_recordID
 *
 * @return null
 */
function ewim_undo_ledger_entry($ewim_table_name,$ewim_recordID){
	//region Global Variables, Classes, Class Variables, Local Variables
	global $wpdb;

	$ewim_oTables= new ewim_tables();
	$ewim_debug_settings= new ewim_debug_settings();
	//endregion

	//region Get the Record
	$ewim_ledgerTable= $ewim_oTables->$ewim_table_name;
	$ewim_aLedgerRecord= $wpdb->get_row("SELECT * FROM $ewim_ledgerTable WHERE id = '$ewim_recordID'",ARRAY_A);
	//endregion

	//region Return items to other records
	switch ($ewim_aLedgerRecord['transaction_type']){
		case "Buy":
			//region Adjust the Item
			$ewim_itemID= $ewim_aLedgerRecord['item_id'];
			$ewim_aItem= $wpdb->get_row("SELECT * FROM $ewim_oTables->ewim_items WHERE id = '$ewim_itemID'",ARRAY_A);
			$ewim_aItemUpdate['item_inventory_quantity']= $ewim_aItem['item_inventory_quantity'] - $ewim_aLedgerRecord['item_amount'];
			$ewim_aItemUpdate['cost']= $ewim_aItem['cost'] - abs($ewim_aLedgerRecord['total_cost']);
			$ewim_updateItemResult= ewim_wpdb_edit('update',$ewim_oTables->ewim_items,$ewim_aItemUpdate,$ewim_itemID);
			if($ewim_updateItemResult['error'] == 'Error'){
				if($ewim_debug_settings->ewim_wpdbError == 1){
					echo "<h1>Edit Result</h1>";
					echo "Action: Insert<br />";
					echo "Table: Ledger<br />";
					echo "<pre>";
					print_r($ewim_updateItemResult['errorMessage']);
					echo "</pre>";

					echo "<h1>Insert Array</h1>";
					echo "<pre>";
					print_r($ewim_updateItemResult);
					echo "</pre>";
					exit;
				}
			}
			//endregion
			break;
		case "Sell":
			//region Adjust the Item
			$ewim_itemID= $ewim_aLedgerRecord['item_id'];
			$ewim_aItem= $wpdb->get_row("SELECT * FROM $ewim_oTables->ewim_items WHERE id = '$ewim_itemID'",ARRAY_A);
			$ewim_aItemUpdate['item_inventory_quantity']= $ewim_aItem['item_inventory_quantity'] + $ewim_aLedgerRecord['item_amount'];
			$ewim_aItemUpdate['cost']= $ewim_aItem['cost'] + abs($ewim_aLedgerRecord['total_production_cost']);
			$ewim_updateItemResult= ewim_wpdb_edit('update',$ewim_oTables->ewim_items,$ewim_aItemUpdate,$ewim_itemID);
			if($ewim_updateItemResult['error'] == 'Error'){
				if($ewim_debug_settings->ewim_wpdbError == 1){
					echo "<h1>Edit Result</h1>";
					echo "Action: Insert<br />";
					echo "Table: Ledger<br />";
					echo "<pre>";
					print_r($ewim_updateItemResult['errorMessage']);
					echo "</pre>";

					echo "<h1>Insert Array</h1>";
					echo "<pre>";
					print_r($ewim_updateItemResult);
					echo "</pre>";
					exit;
				}
			}
			//endregion
			break;
	}
	//endregion

	//region Delete Ledger Record
	$ewim_deleteResult= ewim_wpdb_edit('delete',$ewim_ledgerTable, NULL, $ewim_recordID);
	if($ewim_deleteResult['error'] == 'Error'){
		if($ewim_debug_settings->ewim_wpdbError == 1){
			echo "<h1>Edit Result</h1>";
			echo "Action: Insert<br />";
			echo "Table: Ledger<br />";
			echo "<pre>";
			print_r($ewim_deleteResult['errorMessage']);
			echo "</pre>";

			echo "<h1>Insert Array</h1>";
			echo "<pre>";
			print_r($ewim_deleteResult);
			echo "</pre>";
			exit;
		}
	}
	//endregion
}

/**
 * Name: Determine Max Production
 *
 * @param $ewim_itemID
 *
 * @return integer
 */
function determine_max_production($ewim_itemID){
	//region Global Variables, Classes, Class Variables, Local Variables
	global $wpdb;

	$ewim_tables= new ewim_tables();

	$ewim_productionLimit= 'First Loop';
	//endregion

	//region Get Item Record, Decode Json
	$ewim_aItem= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_items WHERE id = '$ewim_itemID'", ARRAY_A);
	$ewim_aDesignDetails= json_decode($ewim_aItem['design_details'], true);
	//endregion

	//region Loop Design Items, check values
	foreach($ewim_aDesignDetails as $ewim_designItemID => $ewim_aDesignItem){
		//Get Item
		$ewim_aDesignItemDetails= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_items WHERE id = '$ewim_designItemID'", ARRAY_A);

		$ewim_newProductionLimit= ewim_do_math('/',$ewim_aDesignItemDetails['item_inventory_quantity'],$ewim_aDesignItem['amount']);

		if($ewim_productionLimit > $ewim_newProductionLimit || $ewim_productionLimit == 'First Loop'){
			$ewim_productionLimit= $ewim_newProductionLimit;
		}
	}
	//endregion

	return $ewim_productionLimit;
}

/**
 * Name: Determine Production Cost
 */
function determine_production_cost(){

}

/**
 * Name: Determine Credit Debit
 */