<?php
/**
 * Created by PhpStorm.
 * Author: David
 * Date: 3/8/2019
 * Time: 17:04
 * Name:
 * Desc:
 */
/** @noinspection PhpUnusedLocalVariableInspection */
/** @noinspection PhpStatementHasEmptyBodyInspection */
/*
 * Initials: Field Admin Label=FAL;
 */


/**
 * Pre Submission Hooks
 */

/**
 * Name: Master Pre Submission
 */
//region Filters
add_action("gform_pre_submission", 'ewim_master_pre_submission' );
//endregion
function ewim_master_pre_submission($ewim_oForm){
	//region Global Variables, Classes, Class Variables, Local Variables
	global $wpdb;

	$ewim_cTables= new ewim_tables();
	//$ewim_get_options= new ewim_get_options();
	$ewim_debug_settings= new ewim_debug_settings();
	$ewim_current_user= wp_get_current_user();
	$ewim_userID= $ewim_current_user->ID;

	$ewim_activeInventoryID= get_user_meta($ewim_userID, 'active_inventory', true);
	//endregion

	//region Get Records, Decode Json
	$ewim_aInventory= $wpdb->get_row("SELECT * FROM $ewim_cTables->ewim_games WHERE id = $ewim_activeInventoryID",ARRAY_A);
	$ewim_aInventory['inventory_currencies']= json_decode($ewim_aInventory['inventory_currencies'], true);
	//endregion

	//region Form Object Debug
	if(1 == 0){
		echo "<h1>Form Process | Form Entry</h1>";
		echo "<pre>";
		print_r($ewim_oForm);
		echo "</pre>";

		echo "<pre>";
		print_r($_POST);
		echo "</pre>";
		exit;
	}
	//endregion

	//region Get the processor name
	$ewim_formProcessor= $ewim_oForm['fields'][0]['defaultValue'];
	//endregion

	//region Choose the Processor
	switch ($ewim_formProcessor){
		case "default":
			//region Initial Variable Declaration
			$ewim_aItemMeta= array();
			$ewim_aInputFields= array();
			$ewim_aIngredients= array();
			$ewim_recordID= 0;
			$ewim_recordIDFieldID= 0;
			$ewim_editTableName= '';
			$ewim_aInsert= array();
			$ewim_aCurrencies= array();
			//endregion

			//region Loop the Fields
			foreach($ewim_oForm['fields'] as $ewim_aField){
				$ewim_aCssClass= explode(" ",$ewim_aField['cssClass']);
				//$ewim_aCssClass= $ewim_aField['cssClass'];
				switch ($ewim_aField['type']){
					case "hidden":
						switch ($ewim_aField['label']){
							case "hidden_template":
								break;
							case "processor":
								break;
							case "table":
								$ewim_tableName= $_POST['input_'.$ewim_aField['id']];
								$ewim_editTableName= $ewim_cTables->$ewim_tableName;
								break;
							case "record_id":
								$ewim_recordID= $_POST['input_'.$ewim_aField['id']];
								$ewim_recordIDFieldID= $ewim_aField['id'];
								break;
							default:
								$ewim_aInsert[$ewim_aField['label']]= $_POST['input_'.$ewim_aField['id']];
								break;
						}
						break;
					case "checkbox":
						if(in_array("design_items", $ewim_aCssClass)){
							$ewim_checkCount= 1;
							$ewim_designItemCount= 0;
							$ewim_totalCheckCount= count($ewim_aField['choices']);
							while($ewim_checkCount <= $ewim_totalCheckCount){
								if($_POST['input_'.$ewim_aField['id'].'_'.$ewim_checkCount] != ''){
									$ewim_aDesignItems[$ewim_designItemCount]= $_POST['input_'.$ewim_aField['id'].'_'.$ewim_checkCount];
									//array_push($ewim_aDesignItems,$_POST['input_'.$ewim_aField['id'].'_'.$ewim_checkCount]);
									//$ewim_aDesignItems[$ewim_checkCount]= $_POST['input_'.$ewim_aField['id'].'_'.$ewim_checkCount];
									/*
									if(empty($ewim_designDetails)){
										$ewim_designDetails= $_POST['input_'.$ewim_aField['id'].'_'.$ewim_checkCount].',';
									}
									else{
										$ewim_designDetails.= $_POST['input_'.$ewim_aField['id'].'_'.$ewim_checkCount].',';
									}
									*/
									$ewim_designItemCount++;
								}
								$ewim_checkCount++;
							}
						}
						break;
					default:
						if(in_array("ewim_dbField", $ewim_aCssClass)){
							$ewim_aInsert[$ewim_aField['adminLabel']]= $_POST['input_'.$ewim_aField['id']];
						}
						elseif(in_array("currency", $ewim_aCssClass)){
							$ewim_encodeCurrency= 'Yes';
							$ewim_aCurrencies[$ewim_aField['adminLabel']]= $_POST['input_'.$ewim_aField['id']];
						}
						elseif(in_array("design_details", $ewim_aCssClass)){
							if($_POST['input_'.$ewim_aField['id']] != ''){
								$ewim_aDesignItem= explode("_", $ewim_aField['adminLabel']);

								//$ewim_aDesignDetails[$ewim_aDesignItem[1]]['name']= $ewim_aDesignItem[0];
								$ewim_aDesignDetails[$ewim_aDesignItem[1]]['amount']= $_POST['input_'.$ewim_aField['id']];
								//$ewim_aDesignDetails[$ewim_aDesignItem[1]]['category']= $_POST['input_'.$ewim_aField['id']];
							}
						}
						elseif(in_array("template", $ewim_aCssClass)){
						}
						else{
							$ewim_aInputFields[$ewim_aField['adminLabel']]= $_POST['input_'.$ewim_aField['id']];
						}
						break;
				}
			}
			//endregion

			//region Compile insert array for sql command
			if($ewim_encodeCurrency == 'Yes'){
				$ewim_aInsert['inventory_currencies']= json_encode($ewim_aCurrencies);
			}
			//Deprecated
			//If BPC, get info from BPO-Not needed, or get form product now
			if($ewim_aItemMeta['BPO'] > 0){
				$ewim_bpoID= $ewim_aItemMeta['BPO'];
				$ewim_aBPO= $wpdb->get_row("SELECT * FROM $ewim_cTables->ewim_items WHERE id = $ewim_bpoID",ARRAY_A);
				$ewim_aIngredients= json_decode($ewim_aBPO['item_recipe_ingredients'], true);
				$ewim_bpoMeta= json_decode($ewim_aBPO['item_meta'], true);
				$ewim_aItemMeta['Product']= $ewim_bpoMeta['Product'];
			}

			//Deprecated
			if(!empty($ewim_aInputFields)){
				//$ewim_aInsert['input_fields']= json_encode($ewim_aInputFields);
			}

			if(!empty($ewim_aDesignDetails)){
				$ewim_aInsert['design_details']= json_encode($ewim_aDesignDetails);
			}
			elseif(!empty($ewim_aDesignItems)){
				$ewim_aInsert['design_details']= json_encode($ewim_aDesignItems);
			}

			if(!empty($ewim_aItemMeta)){
				$ewim_aInsert['item_meta']= json_encode($ewim_aItemMeta);
			}

			//Ensure Inventory ID is set
			if($ewim_tableName != "ewim_games"){
				$ewim_aInsert['inventory_id']= $ewim_activeInventoryID;
			}
			//endregion

			//region Call the SQL Edit Function, check for errors
			/** @noinspection PhpUndefinedVariableInspection */
			$ewim_action= ( $ewim_recordID == 0 ? 'insert' : 'update');

			//region Debug
			if($ewim_debug_settings->ewim_wpdbEdit == 1){
				echo "<h1>Default Process Result</h1>";
				echo "Action: " . $ewim_action . "<br />";
				echo "Table: ". $ewim_editTableName . "<br />";

				echo "<h1>Insert Array</h1>";
				echo "<pre>";
				print_r($ewim_aInsert);
				echo "</pre>";

				echo "<h1>Posted Variables</h1>";
				echo "<pre>";
				print_r($_POST);
				echo "</pre>";
				exit;
			}
			//endregion

			$ewim_aEditResult= ewim_wpdb_edit($ewim_action,$ewim_editTableName,$ewim_aInsert,$ewim_recordID);
			if($ewim_aEditResult['error'] == 'Error'){
				//todo echo friendly error
				if($ewim_debug_settings->ewim_wpdbEdit == 1){
					echo "<h1>Default Process Result</h1>";
					echo "Action: " . $ewim_action . "<br />";
					echo "Table: ". $ewim_editTableName . "<br />";

					echo "<h1>Error</h1>";
					echo "<pre>";
					print_r($ewim_aEditResult['errorMessage']);
					echo "</pre>";

					echo "<h1>Insert Array</h1>";
					echo "<pre>";
					print_r($ewim_aInsert);
					echo "</pre>";

					echo "<h1>Posted Variables</h1>";
					echo "<pre>";
					print_r($_POST);
					echo "</pre>";
					exit;
				}
			}
			else{
				//Place id into form for confirmation results.
				if($ewim_action == 'insert'){
					$_POST['input_'.$ewim_recordIDFieldID]= $ewim_aEditResult['record_id'];
				}

				if($ewim_debug_settings->ewim_wpdbEdit == 1){
					echo "<h1>Default Process Result</h1>";
					echo "Action: " . $ewim_action . "<br />";
					echo "Table: ". $ewim_editTableName . "<br />";

					echo "<h1>Error</h1>";
					echo "<pre>";
					print_r($ewim_aEditResult['errorMessage']);
					echo "</pre>";

					echo "<h1>Insert Array</h1>";
					echo "<pre>";
					print_r($ewim_aInsert);
					echo "</pre>";

					echo "<h1>Posted Variables</h1>";
					echo "<pre>";
					print_r($_POST);
					echo "</pre>";
					exit;
				}
			}
			//endregion

			break;//End Default
		case "item_transaction":
			//region Initialize Variables
			$ewim_recordID= 0;
			$ewim_action= '';
			$ewim_editTableName= '';
			//endregion

			//region General Dynamic Variable Declarations
			switch ($ewim_aInventory['inventory_currency_system']){
				case 'Single Currency System':
					$inventory_currency= 0;
					break;
				case 'Triple Currency System':
					$tc_first_currency= 0;
					$tc_second_currency= 0;
					$tc_third_currency= 0;
					break;
			}

			$amount_buy= 0;
			$amount_harvest= 0;
			$amount_process= 0;

			$amount_manufacture= 0;
			$manufacturing_cost= 0;

			$amount_sell= 0;
			$sales_tax= 0;
			$broker_fee= 0;

			$amount_write_off= 0;

			$copy_cost= 0;
			$amount_copy_design= 0;
			//endregion

			//region Loop the Fields, Assign to Variables
			foreach($ewim_oForm['fields'] as $ewim_aField){
				switch ($ewim_aField['type']){
					case "hidden":
						switch ($ewim_aField['label']){
							case "processor":
								break;
							case "table":
								$ewim_tableName= $_POST['input_'.$ewim_aField['id']];
								$ewim_editTableName= $ewim_cTables->$ewim_tableName;
								break;
							case "record_id":
								$ewim_recordID= $_POST['input_'.$ewim_aField['id']];
								//$ewim_recordIDFieldID= $ewim_aField['id'];
								break;
							case "form_message":
								$ewim_formMessageFieldID= $ewim_aField['id'];
								break;
							default:
								$ewim_aInsert[$ewim_aField['label']]= $_POST['input_'.$ewim_aField['id']];
								break;
						}
						break;
					default:
						switch ($ewim_aField['adminLabel']){
							case "action":
								$ewim_action= $_POST['input_'.$ewim_aField['id']];
								break;
							default:
								$ewim_fieldAdminLabel= $ewim_aField['adminLabel'];
								$$ewim_fieldAdminLabel= $_POST['input_'.$ewim_aField['id']];
								break;
						}
						break;
				}
			}
			//endregion

			//region Item Transaction Step 2: Filter to Correct Action, Make Calculations

			//region Get Records, Decode Json
			$ewim_aItem= $wpdb->get_row("SELECT * FROM $ewim_cTables->ewim_items WHERE id = $ewim_recordID", ARRAY_A);
			$ewim_aDesignDetails= json_decode($ewim_aItem['design_details'], true);
			//endregion

			switch ($ewim_action){
				case "buy":
					//region Calculations: Costs, Inventory Amounts
					switch ($ewim_aInventory['inventory_currency_system']){
						case 'Single Currency System':
							//Secondary Values Array
							$ewim_aValues= array(
								$inventory_currency
							);

							//Transaction Total
							$ewim_transactionCurrencyAmount= ewim_do_math('+', 0, $ewim_aValues);

							//Credit Debit
							$ewim_transactionCreditDebit= ewim_do_math('-', 0, $ewim_aValues);

							//New Item Cost
							$ewim_aInsert['cost']= ewim_do_math('+', $ewim_aItem['cost'], $ewim_aValues);
							break;
					}

					//New Item Inventory Quantity
					$ewim_aInsert['item_inventory_quantity']= $ewim_aItem['item_inventory_quantity'] + $amount_buy;
					//endregion

					//region Insert to DB
					$ewim_updateResult= ewim_wpdb_edit('update',$ewim_editTableName,$ewim_aInsert,$ewim_recordID);
					if($ewim_updateResult['error'] == 'Error'){
						if($ewim_debug_settings->ewim_wpdbError == 1){
							echo "<h1>Edit Result</h1>";
							echo "Action: " . $ewim_action . "<br />";
							echo "Table: ". $ewim_editTableName . "<br />";
							echo "<pre>";
							print_r($ewim_updateResult['errorMessage']);
							echo "</pre>";
							exit;
						}
						//$ewim_formMessageFieldID
						$_POST['input_'.$ewim_formMessageFieldID]= "There was an error creating this record. Please return to the item and try again. An email has been sent to the Admin with the error details.";
						//todo Add email to admin when error happens, this needs to be in version 1.2.0
						return;
					}
					//endregion

					//region Assemble and write to ledger
					$ewim_aLedgerInsert=array(
						'user_id'                       => $ewim_userID,
						'inventory_id'                  => $ewim_aItem['inventory_id'],
						'item_id'                       => $ewim_aItem['id'],
						'transaction_type'              => 'Buy',
						'transaction_item_amount'                   => $amount_buy,
						'transaction_currency_amount'   => $ewim_transactionCurrencyAmount,
						'transaction_credit_debit'                    => $ewim_transactionCreditDebit
					);
					$ewim_aLedgerInsertResult= ewim_wpdb_edit('insert',$ewim_cTables->ewim_ledger,$ewim_aLedgerInsert);
					if($ewim_aLedgerInsertResult['error'] == 'Error'){
						//todo echo friendly error
						if($ewim_debug_settings->ewim_wpdbError == 1){
							echo "<h1>Edit Result</h1>";
							echo "Action: Insert<br />";
							echo "Table: Ledger<br />";
							echo "<pre>";
							print_r($ewim_aLedgerInsertResult['errorMessage']);
							echo "</pre>";

							echo "<h1>Insert Array</h1>";
							echo "<pre>";
							print_r($ewim_aLedgerInsert);
							echo "</pre>";
							exit;
						}
					}
					//endregion
					break;
				case "sell":
					//region Calculations: Costs, Inventory Amounts
					switch ($ewim_aInventory['inventory_currency_system']){
						case 'Single Currency System':
							//Total Credit after Tax & Broker
							$ewim_aValues= array(
								$broker_fee,
								$sales_tax
							);
							$ewim_totalCredit= ewim_do_math('-',$inventory_currency, $ewim_aValues);

							//Production Calculations
							$ewim_averageProductionCost= ewim_do_math('/',$ewim_aItem['cost'],$ewim_aItem['item_inventory_quantity']);
							$ewim_productionCostSoldTotal= $amount_sell * $ewim_averageProductionCost;

							if($ewim_totalCredit > 0){
								//Item Cost
								$ewim_aItemUpdate['cost']= $ewim_aItem['cost'] - $ewim_productionCostSoldTotal;
							}
							else{
								//Item Cost
								//$ewim_aItemUpdate['cost']= $ewim_aItem['cost'] - $ewim_productionCostSoldTotal;
							}



							//Transaction Total
							$ewim_transactionCurrencyAmount= $inventory_currency;

							//Difference
							$ewim_transactionCreditDebit= $ewim_transactionCurrencyAmount - ($sales_tax + $broker_fee + $ewim_productionCostSoldTotal);
							break;
					}

					$ewim_aItemUpdate['item_inventory_quantity']= $ewim_aItem['item_inventory_quantity'] - $amount_sell;
					//endregion

					//region Insert to DB
					$ewim_updateResult= ewim_wpdb_edit('update',$ewim_editTableName,$ewim_aItemUpdate,$ewim_recordID);
					if($ewim_updateResult['error'] == 'Error'){
						if($ewim_debug_settings->ewim_wpdbError == 1){
							echo "<h1>Edit Result</h1>";
							echo "Action: " . $ewim_action . "<br />";
							echo "Table: ". $ewim_editTableName . "<br />";
							echo "<pre>";
							print_r($ewim_updateResult['errorMessage']);
							echo "</pre>";

							exit;
						}
						$_POST['input_'.$ewim_formMessageFieldID]= "There was an error creating this record. Please return to the item and try again. An email has been sent to the Admin with the error details.";
					}
					//endregion

					//region Ledger Record Insert
					$ewim_aLedgerInsert=array(
						'user_id'                   => $ewim_userID,
						'inventory_id'              => $ewim_aItem['inventory_id'],
						'item_id'                   => $ewim_aItem['id'],
						'transaction_type'          => 'Sell',
						'transaction_item_amount'               => $amount_sell,
						'transaction_currency_amount'                => $ewim_transactionCurrencyAmount,
						'total_production_cost'     => $ewim_productionCostSoldTotal,
						'sales_tax'                 => $sales_tax,
						'broker_fees'               => $broker_fee,
						'transaction_credit_debit'                => $ewim_transactionCreditDebit
					);
					$ewim_ledgerInsertResult= ewim_wpdb_edit('insert',$ewim_cTables->ewim_ledger,$ewim_aLedgerInsert);
					if($ewim_ledgerInsertResult['error'] == 'Error'){
						//todo echo friendly error
						if($ewim_debug_settings->ewim_wpdbError == 1){
							echo "<h1>Edit Result</h1>";
							echo "Action: Insert<br />";
							echo "Table: Ledger<br />";
							echo "<pre>";
							print_r($ewim_ledgerInsertResult['errorMessage']);
							echo "</pre>";

							echo "<h1>Insert Array</h1>";
							echo "<pre>";
							print_r($ewim_aLedgerInsert);
							echo "</pre>";
							exit;
						}
					}
					//endregion
					break;
				case "process":
					//region Get Refined Resources
					$ewim_transactionCreditDebit= 0;
					$ewim_totalCost= 0;
					$ewim_totalProductionCost= 0;
					//endregion

					//region Calculations: Costs, Inventory Amounts
					switch ($ewim_aInventory['inventory_currency_system']){
						case 'Single Currency System':
							$ewim_rawResourceAverageCost= ewim_do_math('/',$ewim_aItem['cost'],$ewim_aItem['item_inventory_quantity']);

							$ewim_rawResourceUsedCost= $ewim_rawResourceAverageCost * $amount_process;

							$ewim_refinedResourceCount= count($ewim_aDesignDetails);

							$ewim_rawResourceUsedCostPerRefinedResource= ewim_do_math('/',$ewim_rawResourceUsedCost,$ewim_refinedResourceCount);

							$ewim_aRawResourceUpdate['cost']= $ewim_aItem['cost'] - $ewim_rawResourceUsedCost;
							break;
					}
					//endregion

					//region Loop Refined Resources, insert amount gained and new cost
					foreach($ewim_aDesignDetails as $ewim_designItemID => $ewim_aDesignItemDetails){
						//reset insert array
						$ewim_aInsertRefinedResource= NULL;

						//Get Refined Resource Record
						$ewim_aRefinedResource= $wpdb->get_row("SELECT * FROM $ewim_cTables->ewim_items WHERE id = $ewim_designItemID",ARRAY_A);

						//Inventory Quantity
						$ewim_refinedResourceFieldAdminLabel= "process_".$ewim_aRefinedResource['item_name'].'_'.$ewim_designItemID;//Dynamic Var with amount of item
						$ewim_aInsertRefinedResource['item_inventory_quantity']= $ewim_aRefinedResource['item_inventory_quantity'] + $$ewim_refinedResourceFieldAdminLabel;

						//Cost Calculations
						switch ($ewim_aInventory['inventory_currency_system']){
							case 'Single Currency System':
								$ewim_refinedResourceProcessingCostFieldAdminLabel= "process_cost_".$ewim_aRefinedResource['item_name'].'_'.$ewim_designItemID;//Dynamic Var with Cost
								$ewim_aInsertRefinedResource['cost']= $ewim_aRefinedResource['cost'] + $$ewim_refinedResourceProcessingCostFieldAdminLabel + $ewim_rawResourceAverageCost;

								//Calculate Difference and Cost
								$ewim_transactionCreditDebit= $ewim_transactionCreditDebit - $$ewim_refinedResourceProcessingCostFieldAdminLabel;
								$ewim_totalCost= $ewim_totalCost + $$ewim_refinedResourceProcessingCostFieldAdminLabel;
								$ewim_totalProductionCost= $ewim_totalProductionCost + $$ewim_refinedResourceProcessingCostFieldAdminLabel + $ewim_rawResourceAverageCost;
								break;
						}

						//region Debug
						if($ewim_debug_settings->ewim_wpdbEdit == 1){
							echo "<h1>Edit Result</h1>";
							echo "Action: " . $ewim_action . "<br />";
							echo "Table: ". $ewim_editTableName . "<br />";
							echo "ewim_refinedResourceFieldAdminLabel: $ewim_refinedResourceFieldAdminLabel <br />";
							echo "Dynamic ewim_refinedResourceFieldAdminLabel: ". $$ewim_refinedResourceFieldAdminLabel ."<br />";
							echo "<pre>";
							print_r($ewim_aInsertRefinedResource);
							echo "</pre>";
							exit;
						}
						//endregion

						//region Update the Refined Resource Record with new Cost and Total Items
						$ewim_refinedResourceUpdateResult= ewim_wpdb_edit('update',$ewim_cTables->ewim_items,$ewim_aInsertRefinedResource,$ewim_designItemID);
						if($ewim_refinedResourceUpdateResult['error'] == 'Error'){
							//todo echo friendly error
							if($ewim_debug_settings->ewim_wpdbError == 1){
								echo "<h1>Edit Result</h1>";
								echo "Action: " . $ewim_action . "<br />";
								echo "Table: ". $ewim_editTableName . "<br />";
								echo "<pre>";
								print_r($ewim_refinedResourceUpdateResult['errorMessage']);
								echo "</pre>";
								exit;
							}
						}
						//endregion
					}
					//endregion

					//region Remove Processed Amount
					$ewim_aRawResourceUpdate['item_inventory_quantity']= $ewim_aItem['item_inventory_quantity'] - $amount_process;
					//Insert to DB
					$ewim_updateResult= ewim_wpdb_edit('update',$ewim_cTables->ewim_items,$ewim_aRawResourceUpdate,$ewim_recordID);
					if($ewim_updateResult['error'] == 'Error'){
						if($ewim_debug_settings->ewim_wpdbError == 1){
							echo "<h1>Edit Result</h1>";
							echo "Action: " . $ewim_action . "<br />";
							echo "Table: " . $ewim_editTableName . "<br />";
							echo "Amount: " .$amount_process. "<br />";
							echo "<pre>";
							print_r($ewim_updateResult['errorMessage']);
							echo "</pre>";

							exit;
						}
						$_POST['input_'.$ewim_formMessageFieldID]= "There was an error creating this record. Please return to the item and try again. An email has been sent to the Admin with the error details.";
					}
					//endregion

					//region Ledger Record Insert
					$ewim_aLedgerInsert=array(
						'user_id'                   => $ewim_userID,
						'inventory_id'              => $ewim_aItem['inventory_id'],
						'item_id'                   => $ewim_aItem['id'],
						'transaction_type'          => 'Process',
						'transaction_item_amount'               => $amount_process,
						'transaction_currency_amount'                => $ewim_totalCost,
						'average_cost'              => 0,
						'total_production_cost'     => $ewim_totalProductionCost,
						'average_production_cost'   => 0,
						'transaction_credit_debit'                => $ewim_transactionCreditDebit
					);
					$ewim_ledgerInsertResult= ewim_wpdb_edit('insert',$ewim_cTables->ewim_ledger,$ewim_aLedgerInsert);
					if($ewim_ledgerInsertResult['error'] == 'Error'){
						if($ewim_debug_settings->ewim_wpdbError == 1){
							echo "<h1>Ledger</h1>";

							echo "<h4>Result</h4>";
							echo "<pre>";
							print_r($ewim_ledgerInsertResult['errorMessage']);
							echo "</pre>";

							echo "<h4>Insert</h4>";
							echo "<pre>";
							print_r($ewim_aLedgerInsert);
							echo "</pre>";
							exit;
						}
						$_POST['input_'.$ewim_formMessageFieldID]= "There was an error creating this record. Please return to the item and try again. An email has been sent to the Admin with the error details.";
					}
					//endregion

					break;
				case "manufacture":
					//region Initial Variable Declaration
					$ewim_bpcTotalRunCost= 0;
					$ewim_manufacturingCostTotal= $manufacturing_cost;
					//endregion

					//region Calculations: Costs, Inventory Amounts
					switch ($ewim_aInventory['inventory_currency_system']){
						case 'Single Currency System':
							$ewim_valueOne= $ewim_aItem['cost'];
							$ewim_aValues= array(
								$manufacturing_cost
							);
							break;
					}

					//$ewim_aInsert['cost']= ewim_do_math('+', $ewim_valueOne, $ewim_aValues);
					//endregion

					//region Get some Basic info on the item
					$ewim_itemCategory= $ewim_aItem['category'];
					$ewim_aItemMeta= json_decode($ewim_aItem['item_meta'], true);
					//$ewim_aDesignDetails= explode(',',$ewim_aItem['design_details']);//Blueprint Recipe
					//endregion

					//region Product or Copy
					switch ($ewim_aItem['category']){
						case 'Product':
							$ewim_productID= $ewim_aItem['id'];//Product ID
							break;
						case 'Design Copy':
							switch ($ewim_aInventory['inventory_currency_system']){
								case 'Single Currency System':
									$ewim_bpcRunCost= $ewim_aItem['cost'] / $ewim_aItem['item_inventory_quantity'];
									$ewim_bpcTotalRunCost= $ewim_bpcRunCost * $amount_manufacture;
									$ewim_productID= $ewim_aItemMeta['product_id'];//Product ID
									$ewim_manufacturingCostTotal= $ewim_manufacturingCostTotal + $ewim_bpcTotalRunCost;
									break;
							}
							break;
					}
					//endregion

					//region Get Product Record
					$ewim_aProduct= $wpdb->get_row("SELECT * FROM $ewim_cTables->ewim_items WHERE id = $ewim_productID", ARRAY_A);//Product, needed to calculate new prices and quantities
					//endregion

					//region Ingredients
					foreach($ewim_aDesignDetails as $ewim_designItemID => $ewim_aDesignItemDetails){
						//reset insert array
						$ewim_aDesignItemUpdate= NULL;

						//region Get the Item record
						$ewim_aDesignItem= $wpdb->get_row("SELECT * FROM $ewim_cTables->ewim_items WHERE id = $ewim_designItemID",ARRAY_A);
						//endregion

						//region Dynamic Variable for Field Admin Label
						$ewim_designItemAmountFAL= "manufacture_".$ewim_aDesignItem['item_name'].'_'.$ewim_designItemID;
						//endregion

						//region Calculations: Costs, Inventory Amounts
						switch ($ewim_aInventory['inventory_currency_system']){
							case "Single Currency System":
								$ewim_designItemAverageCost= ewim_do_math('/',$ewim_aDesignItem['cost'], $ewim_aDesignItem['item_inventory_quantity']);
								$ewim_designItemUsedTotalCost= $ewim_designItemAverageCost * $$ewim_designItemAmountFAL;
								$ewim_manufacturingCostTotal= $ewim_manufacturingCostTotal + $ewim_designItemUsedTotalCost;
								$ewim_aDesignItemUpdate['cost']= $ewim_aDesignItem['cost'] - $ewim_designItemUsedTotalCost;

								//$ewim_manufacturingCostTotal= $ewim_manufacturingCostTotal + (($ewim_aDesignItem['cost'] / $ewim_aDesignItem['item_inventory_quantity']) * $$ewim_designItemAmountFAL);
								//$ewim_aDesignItemUpdate['cost']= $ewim_aDesignItem['cost'] - (($ewim_aDesignItem['cost'] / $ewim_aDesignItem['item_inventory_quantity']) * $$ewim_designItemAmountFAL);
								break;
						}

						$ewim_aDesignItemUpdate['item_inventory_quantity']= $ewim_aDesignItem['item_inventory_quantity'] - $$ewim_designItemAmountFAL;
						//endregion

						//region Remove Ingredients
						$ewim_updateResult= ewim_wpdb_edit('update',$ewim_editTableName,$ewim_aDesignItemUpdate,$ewim_designItemID);
						if($ewim_updateResult['error'] == 'Error'){
							//todo echo friendly error
							if($ewim_debug_settings->ewim_wpdbEdit == 1){
								echo "<h1>Edit Result</h1>";
								echo "Action: " . $ewim_action . "<br />";
								echo "Table: ". $ewim_editTableName . "<br />";
								echo "<pre>";
								print_r($ewim_updateResult['errorMessage']);
								echo "</pre>";
								exit;
							}
						}
						//endregion
					}
					//endregion

					//region Add Crafted Amount

					//Reset Insert Array
					unset($ewim_aInsert);

					//Final Costs and Amounts
					switch ($ewim_aInventory['inventory_currency_system']){
						case 'Single Currency System':
							$ewim_valueOne= $ewim_aItem['cost'];
							$ewim_aValues= array(
								$manufacturing_cost
							);
							break;
					}



					$ewim_aInsert['cost']= $ewim_aProduct['cost'] + $ewim_manufacturingCostTotal;
					$ewim_aInsert['item_inventory_quantity']= $ewim_aProduct['item_inventory_quantity'] + $amount_manufacture;

					//Insert to DB
					$ewim_updateResult= ewim_wpdb_edit('update',$ewim_editTableName,$ewim_aInsert,$ewim_productID);
					if($ewim_updateResult['error'] == 'Error'){
						//todo echo friendly error
						if($ewim_debug_settings->ewim_wpdbError == 1){
							echo "<h1>Edit Result</h1>";
							echo "Action: " . $ewim_action . "<br />";
							echo "Table: ". $ewim_editTableName . "<br />";
							echo "<pre>";
							print_r($ewim_updateResult['errorMessage']);
							echo "</pre>";
							exit;
						}
						$_POST['input_'.$ewim_formMessageFieldID]= "There was an error creating this record. Please return to the item and try again. An email has been sent to the Admin with the error details.";
					}
					else{
						//region Ledger Entry
						$ewim_aLedgerInsert=array(
							'user_id'                   => $ewim_userID,
							'inventory_id'              => $ewim_aProduct['inventory_id'],
							'item_id'                   => $ewim_aProduct['id'],
							'transaction_type'          => 'Manufacture',
							'transaction_item_amount'               => $amount_manufacture,
							'transaction_currency_amount'                => $manufacturing_cost,
							'average_cost'              => $manufacturing_cost / $amount_manufacture,
							'total_production_cost'     => $ewim_manufacturingCostTotal,
							'average_production_cost'   => $ewim_manufacturingCostTotal / $amount_manufacture,

							'transaction_credit_debit'                => $ewim_transactionCreditDebit= 0 - $manufacturing_cost
						);
						$ewim_insertResult= ewim_wpdb_edit('insert',$ewim_cTables->ewim_ledger,$ewim_aLedgerInsert);
						if($ewim_insertResult['error'] == 'Error'){
							//todo echo friendly error
							if($ewim_debug_settings->ewim_wpdbError == 1){
								echo "<h1>Ledger Result</h1>";
								echo "Action: " . $ewim_action . "<br />";
								echo "Table: ". $ewim_editTableName . "<br />";
								echo "<pre>";
								print_r($ewim_insertResult['errorMessage']);
								echo "</pre>";
								exit;
							}
						}
						//endregion

						//region Update BPC Totals
						if($ewim_itemCategory == 'Design Copy') {
							//Reset Insert Array
							unset($ewim_aInsert);
							$ewim_aInsert['cost']= $ewim_aItem['cost'] - $ewim_bpcTotalRunCost;
							$ewim_aInsert['item_inventory_quantity']= $ewim_aItem['item_inventory_quantity'] - $amount_manufacture;
							$ewim_updateResult= ewim_wpdb_edit( 'update', $ewim_editTableName, $ewim_aInsert, $ewim_recordID );
						}
						//endregion
					}
					//endregion

					break;
				case "copy_design":
					//region Calculations: Costs, Inventory Amounts
					switch ($ewim_aInventory['inventory_currency_system']){
						case 'Single Currency System':
							$ewim_averageCopyCost= ewim_do_math('/', $copy_cost, $amount_copy_design);
							//$ewim_averageCopyCost= $copy_cost / $amount_copy;
							$ewim_transactionCreditDebit= 0 - $copy_cost;
							break;
					}
					//endregion

					//region Item Details, Existing Copy : Create Copy
					$ewim_aItem['item_meta']= json_decode($ewim_aItem['item_meta'], true);

					//Check for Design Copy
					//Design Copy Exists
					if($ewim_aItem['item_meta']['design_copy_id'] > 0){
						$ewim_designCopyID= $ewim_aItem['item_meta']['design_copy_id'];
						$ewim_aDesignCopy= $wpdb->get_row("SELECT * FROM $ewim_cTables->ewim_items WHERE id = '$ewim_designCopyID'", ARRAY_A);

						//Handle Currency
						switch ($ewim_aInventory['inventory_currency_system']){
							case 'Single Currency System':
								$ewim_totalCost= $ewim_aDesignCopy['cost'] + $copy_cost;
								break;
						}

						//Handle Inventory Quantity
						$ewim_totalAmount= $ewim_aDesignCopy['item_inventory_quantity'] + $amount_copy_design;

						//Insert Array
						$ewim_aInsertDesignCopy= array(
							'item_inventory_quantity'   => $ewim_totalAmount,
							'cost'                      => $ewim_totalCost
						);

						//Update the Design Copy
						$ewim_updateResult= ewim_wpdb_edit('update',$ewim_editTableName,$ewim_aInsertDesignCopy,$ewim_designCopyID);
						if($ewim_updateResult['error'] == 'Error'){
							//todo echo friendly error
							if($ewim_debug_settings->ewim_wpdbError == 1){
								echo "<h1>Edit Result</h1>";
								echo "Action: " . $ewim_action . "<br />";
								echo "Table: ". $ewim_editTableName . "<br />";
								echo "<pre>";
								print_r($ewim_updateResult['errorMessage']);
								echo "</pre>";
								exit;
							}
						}
					}
					//Design Copy needs to be created
					else{
						$ewim_itemName= $ewim_aItem['item_name'];
						$ewim_designCopyItemMeta= array(
							'product_id'   => $ewim_aItem['id'],
							'product_name'  => $ewim_aItem['item_name']
						);
						$ewim_jsDesignCopyItemMeta= json_encode($ewim_designCopyItemMeta);
						$ewim_aInsertDesignCopy= array(
							'user_id'                   => $ewim_aItem['user_id'],
							'inventory_id'              => $ewim_aItem['inventory_id'],
							'item_name'                 => $ewim_itemName.' Design Copy',
							'category'                  => 'Design Copy',
							'item_meta'                 => $ewim_jsDesignCopyItemMeta,
							'design_details'            => $ewim_aItem['design_details'],
							'item_inventory_quantity'   => $amount_copy_design,
							'cost'                      => $copy_cost
						);

						//Execute Insert
						$ewim_aDesignCopyInsertResult= ewim_wpdb_edit('insert',$ewim_cTables->ewim_items,$ewim_aInsertDesignCopy);
						if($ewim_aDesignCopyInsertResult['error'] == 'Error'){
							if($ewim_debug_settings->ewim_wpdbError == 1){
								echo "<h1>Copy Error Message</h1>";
								echo "<h3>Create Design Copy Record</h3>";
								echo "<h4>Table: </h4>";

								echo "<h4>Result</h4>";
								echo "<pre>";
								print_r($ewim_aDesignCopyInsertResult['errorMessage']);
								echo "</pre>";

								echo "<h4>Insert</h4>";
								echo "<pre>";
								print_r($ewim_aInsertDesignCopy);
								echo "</pre>";
								exit;
							}
							$_POST['input_'.$ewim_formMessageFieldID]= "There was an error creating this record. Please return to the item and try again. An email has been sent to the Admin with the error details.";
						}

						//Update Product with DC ID
						$ewim_aItem['item_meta']['design_copy_id']= $ewim_aDesignCopyInsertResult['record_id'];
						$ewim_aUpdateItem['item_meta']= json_encode($ewim_aItem['item_meta']);
						$ewim_aUpdateItemResult= ewim_wpdb_edit('update',$ewim_cTables->ewim_items,$ewim_aUpdateItem,$ewim_aItem['id']);
						if($ewim_aUpdateItemResult['error'] == 'Error'){
							if($ewim_debug_settings->ewim_wpdbError == 1){
								echo "<h1>Copy Error Message</h1>";
								echo "<h3>Create Design Copy Record</h3>";
								echo "<h4>Table: </h4>";

								echo "<h4>Result</h4>";
								echo "<pre>";
								print_r($ewim_aDesignCopyInsertResult['errorMessage']);
								echo "</pre>";

								echo "<h4>Insert</h4>";
								echo "<pre>";
								print_r($ewim_aInsertDesignCopy);
								echo "</pre>";
								exit;
							}
							$_POST['input_'.$ewim_formMessageFieldID]= "There was an error creating this record. Please return to the item and try again. An email has been sent to the Admin with the error details.";
						}
					}
					//endregion

					//region Ledger Record Insert
					$ewim_aLedgerInsert=array(
						'user_id'                   => $ewim_userID,
						'inventory_id'              => $ewim_aItem['inventory_id'],
						'item_id'                   => $ewim_aItem['id'],
						'transaction_type'          => 'Copy Design',
						'transaction_item_amount'               => $amount_copy_design,
						'transaction_currency_amount'                => $copy_cost,
						'average_cost'              => $ewim_averageCopyCost,
						'transaction_credit_debit'                => $ewim_transactionCreditDebit
					);
					$ewim_ledgerInsertResult= ewim_wpdb_edit('insert',$ewim_cTables->ewim_ledger,$ewim_aLedgerInsert);
					if($ewim_ledgerInsertResult['error'] == 'Error'){
						//todo Create Friendly Message
						if($ewim_debug_settings->ewim_wpdbError == 1){
							echo "<h1>Ledger</h1>";

							echo "<h4>Result</h4>";
							echo "<pre>";
							print_r($ewim_ledgerInsertResult['errorMessage']);
							echo "</pre>";

							echo "<h4>Insert</h4>";
							echo "<pre>";
							print_r($ewim_aLedgerInsert);
							echo "</pre>";
							exit;
						}
					}
					//endregion
					break;
				case "harvest":
					//Amount being Harvested
					$ewim_amountHarvested= $amount_harvest;

					//Take current item number and add harvested amount
					$ewim_aInsert['item_inventory_quantity']= $ewim_amountHarvested + $ewim_aItem['item_inventory_quantity'];

					//region Insert to DB
					$ewim_updateResult= ewim_wpdb_edit('update',$ewim_editTableName,$ewim_aInsert,$ewim_recordID);
					//Error check
					if($ewim_updateResult['error'] == 'Error'){
						if($ewim_debug_settings->ewim_wpdbError == 1){
							echo "<h1>Edit Result</h1>";
							echo "Action: " . $ewim_action . "<br />";
							echo "Table: ". $ewim_editTableName . "<br />";
							echo "<pre>";
							print_r($ewim_updateResult['errorMessage']);
							echo "</pre>";
							exit;
						}
						$_POST['input_'.$ewim_formMessageFieldID]= "There was an error creating this record. Please return to the item and try again. An email has been sent to the Admin with the error details.";
					}
					//endregion

					break;
				case "write_off":
					//region Calculations: Costs, Inventory Amounts
					switch ($ewim_aInventory['inventory_currency_system']){
						case 'Single Currency System':
							//Production Calculations
							$ewim_averageProductionCost= ewim_do_math('/',$ewim_aItem['cost'],$ewim_aItem['item_inventory_quantity']);
							$ewim_productionCostSoldTotal= $amount_write_off * $ewim_averageProductionCost;

							//Item Cost
							$ewim_aItemUpdate['cost']= $ewim_aItem['cost'] - $ewim_productionCostSoldTotal;

							//Difference
							$ewim_transactionCreditDebit= $ewim_productionCostSoldTotal;
							break;
					}

					$ewim_aItemUpdate['item_inventory_quantity']= $ewim_aItem['item_inventory_quantity'] - $amount_write_off;
					//endregion

					//region Insert to DB
					$ewim_updateResult= ewim_wpdb_edit('update',$ewim_editTableName,$ewim_aItemUpdate,$ewim_recordID);
					if($ewim_updateResult['error'] == 'Error'){
						if($ewim_debug_settings->ewim_wpdbError == 1){
							echo "<h1>Edit Result</h1>";
							echo "Action: " . $ewim_action . "<br />";
							echo "Table: ". $ewim_editTableName . "<br />";
							echo "<pre>";
							print_r($ewim_updateResult['errorMessage']);
							echo "</pre>";

							exit;
						}
						$_POST['input_'.$ewim_formMessageFieldID]= "There was an error creating this record. Please return to the item and try again. An email has been sent to the Admin with the error details.";
					}
					//endregion

					//region Ledger Record Insert
					$ewim_aLedgerInsert=array(
						'user_id'                   => $ewim_userID,
						'inventory_id'              => $ewim_aItem['inventory_id'],
						'item_id'                   => $ewim_aItem['id'],
						'transaction_type'          => 'Write Off',
						'transaction_item_amount'               => $amount_write_off,
						'transaction_currency_amount'                => 0,
						'total_production_cost'     => $ewim_productionCostSoldTotal,
						'transaction_credit_debit'                => $ewim_transactionCreditDebit
					);
					$ewim_ledgerInsertResult= ewim_wpdb_edit('insert',$ewim_cTables->ewim_ledger,$ewim_aLedgerInsert);
					if($ewim_ledgerInsertResult['error'] == 'Error'){
						//todo echo friendly error
						if($ewim_debug_settings->ewim_wpdbError == 1){
							echo "<h1>Edit Result</h1>";
							echo "Action: Insert<br />";
							echo "Table: Ledger<br />";
							echo "<pre>";
							print_r($ewim_ledgerInsertResult['errorMessage']);
							echo "</pre>";

							echo "<h1>Insert Array</h1>";
							echo "<pre>";
							print_r($ewim_aLedgerInsert);
							echo "</pre>";
							exit;
						}
					}
					//endregion
					break;
				/*case "post":
					$ewim_amountPosted= $amount_post;
					$ewim_brokerFee= $broker_fee;
					$ewim_postPrice= $posted_price;

					//region Move amount posted out of Inventory
					$ewim_averageProductionCostCost= $ewim_aItem['cost'] / $ewim_aItem['item_inventory_quantity'];

					$ewim_aInsert['item_inventory_quantity']= $ewim_aItem['item_inventory_quantity'] - $ewim_amountPosted;
					$ewim_aInsert['cost']= $ewim_averageProductionCostCost * $ewim_aInsert['item_inventory_quantity'];

					//Remove from Inventory
					$ewim_updateResult= ewim_wpdb_edit('update',$ewim_editTableName,$ewim_aInsert,$ewim_recordID);
					//endregion

					//region Move amount posted into Posted
					$ewim_aInsertPosted= array(
						'user_id'       => $ewim_userID,
						'inventory_id'       => $ewim_activeInventoryID,
						'item_id'       => $ewim_recordID,
						'amount'        => $ewim_amountPosted,
						'broker_fee'    => $ewim_brokerFee,
						'status'        => 'Posted',
						'post_price'    => $ewim_postPrice,
						'average'       => $ewim_averageProductionCostCost
					);
					$ewim_insertPostedResult= ewim_wpdb_edit('insert',$ewim_cTables->ewim_posted,$ewim_aInsertPosted);

					//region Error Check, Ledger Write
					if($ewim_insertPostedResult['error'] == 'Error'){
						//todo echo friendly error
						if($ewim_debug_settings->ewim_wpdbEdit == 1){
							echo "<h1>Edit Result</h1>";
							echo "<pre>";
							print_r($ewim_insertPostedResult['errorMessage']);
							echo "</pre>";

							exit;
						}
					}
					else{
						//region Assemble and write to ledger
						$ewim_transactionCreditDebit= 0 - $ewim_brokerFee;
						$ewim_aLedgerInsert=array(
							'user_id'                   => $ewim_userID,
							'inventory_id'                   => $ewim_aItem['inventory_id'],
							'item_id'                   => $ewim_aItem['id'],
							'transaction_type'          => 'Post',
							'transaction_item_amount'               => $ewim_amountPosted,
							'average_production_cost'   => $ewim_averageProductionCostCost,
							'total_production_cost'     => $ewim_averageProductionCostCost * $ewim_amountPosted,
							'average_sbpm_cost'         => $ewim_brokerFee / $ewim_amountPosted,
							'total_sbpm_cost'           => $ewim_brokerFee,
							'broker_fees'               => $ewim_brokerFee,
							'sales_tax'                 => 0,
							'transaction_credit_debit'                => $ewim_transactionCreditDebit
						);
						$ewim_insertResult= ewim_wpdb_edit('insert',$ewim_cTables->ewim_ledger,$ewim_aLedgerInsert);
						//endregion
					}
					//endregion

					//endregion
					break;
					*/
			}
			//endregion
			break;//End Item Transaction
		case "sell_posted":
			//region General Dynamic Variable Declarations
			$amount_Sell= 0;
			$taxes_paid= 0;
			$amount_Remove= 0;
			$ewim_action= '';
			$ewim_recordID= 0;
			//endregion

			//region Sell Posted Step 1: Loop the Fields, Assign to Variables
			foreach($ewim_oForm['fields'] as $ewim_aField){
				switch ($ewim_aField['type']){
					case "hidden":
						switch ($ewim_aField['label']){
							case "processor":
								break;
							case "table":
								//$ewim_editTableName= $ewim_cTables->$_POST['input_'.$ewim_aField['id']];
								break;
							case "record_id":
								$ewim_recordID= $_POST['input_'.$ewim_aField['id']];
								//$ewim_recordIDFieldID= $ewim_aField['id'];
								break;
							default:
								$ewim_aInsert[$ewim_aField['label']]= $_POST['input_'.$ewim_aField['id']];
								break;
						}
						break;
					default:
						switch ($ewim_aField['adminLabel']){
							case "action":
								$ewim_action= $_POST['input_'.$ewim_aField['id']];
								break;
							default:
								$ewim_fieldAdminLabel= $ewim_aField['adminLabel'];
								$$ewim_fieldAdminLabel= $_POST['input_'.$ewim_aField['id']];
								break;
						}
						break;
				}
			}
			//endregion

			switch ($ewim_action){
				case 'Sell':
					//region Sell Posted Step 1
					$ewim_aPost= $wpdb->get_row("SELECT * FROM $ewim_cTables->ewim_posted WHERE id = $ewim_recordID",ARRAY_A);//Get Items

					$ewim_amountSold= $amount_Sell;
					$ewim_taxesPaid= $taxes_paid;

					$ewim_newAmount= $ewim_aPost['amount']- $ewim_amountSold;
					$ewim_remainBrokerFee= $ewim_aPost['broker_fee'] - (($ewim_aPost['broker_fee'] / $ewim_aPost['amount']) * $ewim_amountSold);
					$ewim_newStatus= ($ewim_newAmount > 0 ? 'Posted' : 'Sold');

					$ewim_aInsertPosted= array(
						'amount'        => $ewim_newAmount,
						'broker_fee'    => $ewim_remainBrokerFee,
						'status'        => $ewim_newStatus
					);

					//region Debug
					if($ewim_debug_settings->ewim_wpdbInsert == 1){
						echo "<h1>Insert</h1>";
						echo "<pre>";
						echo "Amount Sold: $ewim_amountSold<br />";
						print_r($ewim_aInsertPosted);
						echo "</pre>";
						exit;
					}
					//endregion

					$ewim_insertPostedResult= ewim_wpdb_edit('update',$ewim_cTables->ewim_posted,$ewim_aInsertPosted,$ewim_recordID);
					//endregion

					//region Write to Ledger
					$ewim_totalProductionCost= $ewim_aPost['average'] * $ewim_amountSold;
					$ewim_totalSellCost= $ewim_aPost['post_price'] * $ewim_amountSold;
					$ewim_brokerFees= ($ewim_aPost['broker_fee'] / $ewim_aPost['amount']) * $ewim_amountSold;
					$ewim_aLedgerInsert=array(
						'user_id'           => $ewim_userID,
						'inventory_id'           => $ewim_aPost['inventory_id'],
						'item_id'           => $ewim_aPost['item_id'],
						'transaction_type'  => 'Sell',
						'transaction_item_amount'            => $ewim_amountSold,
						'average_production_cost'   => $ewim_aPost['average'],
						'total_production_cost'     => $ewim_totalProductionCost,
						'average_sbpm_cost'         => $ewim_aPost['post_price'],
						'total_sbpm_cost'           => $ewim_totalSellCost,
						'broker_fees'               => $ewim_brokerFees,
						'sales_tax'                 => $ewim_taxesPaid,
						'transaction_credit_debit'                => $ewim_totalSellCost - $ewim_taxesPaid - $ewim_brokerFees - $ewim_totalProductionCost
					);
					$ewim_insertResult= ewim_wpdb_edit('insert',$ewim_cTables->ewim_ledger,$ewim_aLedgerInsert);
					//endregion

					break;
				case 'Remove':
					//region Remove Posted Step 1
					$ewim_aPost= $wpdb->get_row("SELECT * FROM $ewim_cTables->ewim_posted WHERE id = $ewim_recordID",ARRAY_A);//Get Items

					$ewim_amountRemoved= $amount_Remove;
					//$ewim_taxesPaid= $taxes_paid;

					$ewim_newAmount= $ewim_aPost['amount']- $ewim_amountRemoved;
					$ewim_remainBrokerFee= $ewim_aPost['broker_fee'] - (($ewim_aPost['broker_fee'] / $ewim_aPost['amount']) * $ewim_amountRemoved);
					$ewim_newStatus= ($ewim_newAmount > 0 ? 'Posted' : 'Removed');

					$ewim_aInsertPosted= array(
						'amount'        => $ewim_newAmount,
						'broker_fee'    => $ewim_remainBrokerFee,
						'status'        => $ewim_newStatus
					);

					//region Debug
					if($ewim_debug_settings->ewim_wpdbInsert == 1){
						echo "<h1>Insert</h1>";
						echo "<pre>";
						echo "Amount Sold: $ewim_amountRemoved<br />";
						print_r($ewim_aInsertPosted);
						echo "</pre>";
						exit;
					}
					//endregion

					$ewim_insertPostedResult= ewim_wpdb_edit('update',$ewim_cTables->ewim_posted,$ewim_aInsertPosted,$ewim_recordID);
					//endregion

					//region Update Item Record
					$ewim_itemID= $ewim_aPost['item_id'];
					$ewim_aItem= $wpdb->get_row("SELECT * FROM $ewim_cTables->ewim_items WHERE id = $ewim_itemID",ARRAY_A);//Get Items

					$ewim_totalProductionCost= $ewim_aPost['average'] * $ewim_amountRemoved;
					$ewim_brokerFees= ($ewim_aPost['broker_fee'] / $ewim_aPost['amount']) * $ewim_amountRemoved;
					$ewim_newTotalProductionCost= $ewim_totalProductionCost + $ewim_brokerFees;

					$ewim_aInsert['cost']= $ewim_aItem['cost'] + $ewim_newTotalProductionCost;

					$ewim_updateResult= ewim_wpdb_edit('update',$ewim_cTables->ewim_items,$ewim_aInsert,$ewim_itemID);
					//endregion

					break;
			}




			break;//End Sell Posted
		default:
			break;
	}
	//endregion
}