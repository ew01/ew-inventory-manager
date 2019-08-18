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

	//region Step 1: Get the processor name
	$ewim_formProcessor= $ewim_oForm['fields'][0]['defaultValue'];
	//endregion

	//region Step 2: Choose the Processor
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

			//region Default Step 1: Loop the Fields
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
						if(in_array("design_details", $ewim_aCssClass)){
							$ewim_checkCount= 1;
							$ewim_totalCheckCount= count($ewim_aField['choices']);
							while($ewim_checkCount <= $ewim_totalCheckCount){
								if($_POST['input_'.$ewim_aField['id'].'_'.$ewim_checkCount] != ''){
									if(empty($ewim_designDetails)){
										$ewim_designDetails= $_POST['input_'.$ewim_aField['id'].'_'.$ewim_checkCount].',';
									}
									else{
										$ewim_designDetails.= $_POST['input_'.$ewim_aField['id'].'_'.$ewim_checkCount].',';
									}
								}
								$ewim_checkCount++;
							}
							$ewim_designDetails= rtrim($ewim_designDetails, ',');
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
						elseif(in_array("template", $ewim_aCssClass)){
						}
						else{
							$ewim_aInputFields[$ewim_aField['adminLabel']]= $_POST['input_'.$ewim_aField['id']];
						}
						break;
				}
			}
			//endregion

			//region Default Step 2: Compile insert array for sql command
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

			if(!empty($ewim_designDetails)){
				$ewim_aInsert['design_details']= $ewim_designDetails;
			}

			if(!empty($ewim_aItemMeta)){
				$ewim_aInsert['item_meta']= json_encode($ewim_aItemMeta);
			}

			//Ensure Inventory ID is set
			if($ewim_tableName != "ewim_games"){
				$ewim_aInsert['inventory_id']= $ewim_activeInventoryID;
			}
			//endregion

			//region Default Step 3: Call the SQL Edit Function, check for errors
			/** @noinspection PhpUndefinedVariableInspection */
			$ewim_action= ( $ewim_recordID == 0 ? 'insert' : 'update');
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

			break;//End Default Processor
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

			$amount_Buy= 0;
			$amount_Harvest= 0;
			$amount_Process= 0;

			$amount_Manufacture= 0;
			$manufacturing_cost= 0;

			$amount_Sell= 0;
			$sales_tax= 0;
			$broker_fee= 0;

			$copy_cost= 0;
			$amount_Copy= 0;
			//endregion

			//region Item Transaction Step 1: Loop the Fields, Assign to Variables
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
			//endregion

			switch ($ewim_action){
				case "Buy":
					//region Calculations: Costs, Inventory Amounts
					switch ($ewim_aInventory['inventory_currency_system']){
						case 'Single Currency System':
							$ewim_valueOne= $ewim_aItem['cost'];
							$ewim_aValues= array(
								$inventory_currency
							);
							$ewim_totalCost= 0;
							foreach($ewim_aValues as $ewim_value){
								$ewim_totalCost= $ewim_totalCost + $ewim_value;
							}

							$ewim_difference= 0 - $ewim_totalCost;
							$ewim_averageBuyCost= $ewim_totalCost / $amount_Buy;
							break;
					}

					$ewim_aInsert['cost']= ewim_do_math('+', $ewim_valueOne, $ewim_aValues);

					$ewim_aInsert['item_inventory_quantity']= $ewim_aItem['item_inventory_quantity'] + $amount_Buy;
					//endregion

					//region Insert to DB

					//region Debug
					if($ewim_debug_settings->ewim_wpdbEdit == 1){
						echo "<h1>Edit Result</h1>";
						echo "Action: " . $ewim_action . "<br />";
						echo "Table: ". $ewim_editTableName . "<br />";
						echo "<pre>";
						print_r($ewim_aInsert);
						echo "</pre>";
						exit;
					}
					//endregion

					$ewim_updateResult= ewim_wpdb_edit('update',$ewim_editTableName,$ewim_aInsert,$ewim_recordID);
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
					else{
						//region Assemble and write to ledger
						$ewim_aLedgerInsert=array(
							'user_id'                   => $ewim_userID,
							'inventory_id'                   => $ewim_aItem['inventory_id'],
							'item_id'                   => $ewim_aItem['id'],
							'transaction_type'          => 'Buy',
							'item_amount'               => $amount_Buy,
							'average_production_cost'   => 0,
							'total_production_cost'     => 0,
							'average_cost'              => $ewim_averageBuyCost,
							'total_cost'                => $ewim_totalCost,
							'broker_fees'               => 0,
							'sales_tax'                 => 0,
							'difference'                => $ewim_difference
						);
						$ewim_insertResult= ewim_wpdb_edit('insert',$ewim_cTables->ewim_ledger,$ewim_aLedgerInsert);
						if($ewim_insertResult['error'] == 'Error'){
							//todo echo friendly error
							if($ewim_debug_settings->ewim_wpdbError == 1){
								echo "<h1>Edit Result</h1>";
								echo "Action: Insert<br />";
								echo "Table: Ledger<br />";
								echo "<pre>";
								print_r($ewim_insertResult['errorMessage']);
								echo "</pre>";

								echo "<h1>Insert Array</h1>";
								echo "<pre>";
								print_r($ewim_aLedgerInsert);
								echo "</pre>";
								exit;
							}
						}
						//endregion
					}
					//endregion

					break;
				case "Harvest":
					//Amount being Harvested
					$ewim_amountHarvested= $amount_Harvest;

					//Take current item number and add harvested amount
					$ewim_aInsert['item_inventory_quantity']= $ewim_amountHarvested + $ewim_aItem['item_inventory_quantity'];

					//region Insert to DB
					//region Debug
					if($ewim_debug_settings->ewim_wpdbEdit == 1){
						echo "<h1>Edit Result</h1>";
						echo "Action: " . $ewim_action . "<br />";
						echo "Table: ". $ewim_editTableName . "<br />";
						echo "<pre>";
						print_r($ewim_aInsert);
						echo "</pre>";
						exit;
					}
					//endregion
					$ewim_updateResult= ewim_wpdb_edit('update',$ewim_editTableName,$ewim_aInsert,$ewim_recordID);
					//endregion

					//Error check
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

					break;
				case "Process":
					//region Get Refined Resources
					$ewim_aDesignDetails= explode(',',$ewim_aItem['design_details']);
					$ewim_difference= 0;
					$ewim_totalCost= 0;
					$ewim_totalProductionCost= 0;
					//endregion

					//region Raw Resource Cost
					switch ($ewim_aInventory['inventory_currency_system']){
						case 'Single Currency System':
							if($ewim_aItem['cost'] > 0){
								$ewim_averageItemCost= $ewim_aItem['cost'] / $ewim_aItem['item_inventory_quantity'];
								$ewim_rawResourceCost= $ewim_averageItemCost * $amount_Process;
								$ewim_refinedResourceCount= count($ewim_aDesignDetails);
								$ewim_rawResourceAverageCost= $ewim_rawResourceCost / $ewim_refinedResourceCount;
							}
							else{
								$ewim_rawResourceCost= 0;
								$ewim_rawResourceAverageCost= 0;
							}
							$ewim_aInsert['cost']= $ewim_aItem['cost'] - $ewim_rawResourceCost;
							break;
					}
					//endregion

					//region Loop Refined Resources, insert amount gained and new cost
					foreach($ewim_aDesignDetails as $ewim_aDesignDetail){
						//reset insert array
						$ewim_aInsertRefinedResource= NULL;

						//Get the Design Items basic info into variables to use for full details query
						$ewim_aDesignDetailItem= explode('_', $ewim_aDesignDetail);
						$ewim_refinedResourceName= $ewim_aDesignDetailItem[0];
						$ewim_refinedResourceID= $ewim_aDesignDetailItem[1];

						//Get Refined Resource Record
						$ewim_aRefinedResource= $wpdb->get_row("SELECT * FROM $ewim_cTables->ewim_items WHERE id = $ewim_refinedResourceID",ARRAY_A);

						//Inventory Quantity
						$ewim_refinedResourceFieldAdminLabel= "process_".$ewim_refinedResourceName.'_'.$ewim_refinedResourceID;//Dynamic Var with amount of item
						$ewim_aInsertRefinedResource['item_inventory_quantity']= $ewim_aRefinedResource['item_inventory_quantity'] + $$ewim_refinedResourceFieldAdminLabel;

						//Cost Calculations
						switch ($ewim_aInventory['inventory_currency_system']){
							case 'Single Currency System':
								$ewim_refinedResourceProcessingCostFieldAdminLabel= "process_cost_".$ewim_refinedResourceName.'_'.$ewim_refinedResourceID;//Dynamic Var with Cost
								$ewim_aInsertRefinedResource['cost']= $ewim_aRefinedResource['cost'] + $$ewim_refinedResourceProcessingCostFieldAdminLabel + $ewim_rawResourceAverageCost;

								//Calculate Difference and Cost
								$ewim_difference= $ewim_difference - $$ewim_refinedResourceProcessingCostFieldAdminLabel;
								$ewim_totalCost= $ewim_totalCost + $$ewim_refinedResourceProcessingCostFieldAdminLabel;
								$ewim_totalProductionCost= $ewim_totalProductionCost + $$ewim_refinedResourceProcessingCostFieldAdminLabel + $ewim_rawResourceAverageCost;
								break;
						}

						//region Update the Refined Resource Record with new Cost and Total Items
						//region Debug
						if($ewim_debug_settings->ewim_wpdbEdit == 1){
							echo "<h1>Update Refined Resource</h1>";
							echo "Refined Resource: " . $ewim_refinedResourceName . "<br />";
							echo "ID: " . $ewim_refinedResourceID . "<br />";
							echo "Amount: " .$$ewim_refinedResourceFieldAdminLabel. "<br />";
							echo "Cost: " .$$ewim_refinedResourceProcessingCostFieldAdminLabel. "<br />";
							echo "<pre>";
							print_r($ewim_aInsertRefinedResource);
							echo "</pre>";

							exit;
						}
						//endregion
						$ewim_refinedResourceUpdateResult= ewim_wpdb_edit('update',$ewim_cTables->ewim_items,$ewim_aInsertRefinedResource,$ewim_refinedResourceID);
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
					$ewim_aInsert['item_inventory_quantity']= $ewim_aItem['item_inventory_quantity'] - $amount_Process;

					//Insert to DB
					$ewim_updateResult= ewim_wpdb_edit('update',$ewim_cTables->ewim_items,$ewim_aInsert,$ewim_recordID);
					if($ewim_updateResult['error'] == 'Error'){
						//todo echo friendly error
						if($ewim_debug_settings->ewim_wpdbError == 1){
							echo "<h1>Edit Result</h1>";
							echo "Action: " . $ewim_action . "<br />";
							echo "Table: " . $ewim_editTableName . "<br />";
							echo "Amount: " .$amount_Process. "<br />";
							echo "<pre>";
							print_r($ewim_updateResult['errorMessage']);
							echo "</pre>";

							exit;
						}
					}
					else{

					}
					//endregion

					//region Ledger Record Insert
					$ewim_aLedgerInsert=array(
						'user_id'                   => $ewim_userID,
						'inventory_id'              => $ewim_aItem['inventory_id'],
						'item_id'                   => $ewim_aItem['id'],
						'transaction_type'          => 'Process',
						'item_amount'               => $amount_Process,
						'total_cost'                => $ewim_totalCost,
						'average_cost'              => 0,
						'total_production_cost'     => $ewim_totalProductionCost,
						'average_production_cost'   => 0,
						'difference'                => $ewim_difference
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
				case "Manufacture":
					//region Calculations: Costs, Inventory Amounts
					switch ($ewim_aInventory['inventory_currency_system']){
						case 'Single Currency System':
							$ewim_valueOne= $ewim_aItem['cost'];
							$ewim_aValues= array(
								$manufacturing_cost
							);
							break;
					}

					$ewim_aInsert['cost']= ewim_do_math('+', $ewim_valueOne, $ewim_aValues);
					//endregion

					//region Initial Variable Declaration
					$ewim_bpcTotalRunCost= 0;
					$ewim_manufacturingCostTotal= $manufacturing_cost;
					//endregion

					//region Get some Basic info on the item
					$ewim_itemCategory= $ewim_aItem['category'];
					$ewim_aItemMeta= json_decode($ewim_aItem['item_meta'], true);
					$ewim_aDesignDetails= explode(',',$ewim_aItem['design_details']);//Blueprint Recipe
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
									$ewim_bpcTotalRunCost= $ewim_bpcRunCost * $amount_Manufacture;
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
					foreach($ewim_aDesignDetails as $ewim_aDesignDetail){
						//reset insert array
						$ewim_aInsert= NULL;

						//region Split DesignDetail into parts; DesignDetail= itemName_itemID
						$ewim_designDetailItem= explode('_', $ewim_aDesignDetail);
						$ewim_ingredientName= $ewim_designDetailItem[0];
						$ewim_ingredientID= $ewim_designDetailItem[1];
						//endregion

						//region Get the Item record
						$ewim_aIngredientItem= $wpdb->get_row("SELECT * FROM $ewim_cTables->ewim_items WHERE id = $ewim_ingredientID",ARRAY_A);
						//endregion

						//region Dynamic Variable for Admin Label
						$ewim_ingredientFieldAdminLabel= "manufacture_".$ewim_ingredientName.'_'.$ewim_ingredientID;
						//endregion

						//region Handle Currency Systems
						switch ($ewim_aInventory['inventory_currency_system']){
							case "Single Currency System":
								$ewim_manufacturingCostTotal= $ewim_manufacturingCostTotal + (($ewim_aIngredientItem['cost'] / $ewim_aIngredientItem['item_inventory_quantity']) * $$ewim_ingredientFieldAdminLabel);

								$ewim_aInsert['item_inventory_quantity']= $ewim_aIngredientItem['item_inventory_quantity'] - $$ewim_ingredientFieldAdminLabel;
								$ewim_aInsert['cost']= $ewim_aIngredientItem['cost'] - (($ewim_aIngredientItem['cost'] / $ewim_aIngredientItem['item_inventory_quantity']) * $$ewim_ingredientFieldAdminLabel);
								break;
						}
						//endregion

						//region Remove Ingredients
						$ewim_updateResult= ewim_wpdb_edit('update',$ewim_editTableName,$ewim_aInsert,$ewim_ingredientID);
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
					$ewim_aInsert['cost']= $ewim_aProduct['cost'] + $ewim_manufacturingCostTotal;
					$ewim_aInsert['item_inventory_quantity']= $ewim_aProduct['item_inventory_quantity'] + $amount_Manufacture;

					//region Debug
					if($ewim_debug_settings->ewim_wpdbEdit == 1){
						echo "<h1>Edit Result</h1>";
						echo "Action: " . $ewim_action . "<br />";
						echo "Table: ". $ewim_editTableName . "<br />";
						echo "ID: ".$ewim_productID."<br />";
						echo "<pre>";
						print_r($ewim_updateResult['errorMessage']);
						print_r($ewim_aItemMeta);
						print_r($ewim_aDesignDetails);
						echo "</pre>";
						exit;
					}
					//endregion

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
					}
					else{
						//region Ledger Entry
						$ewim_aLedgerInsert=array(
							'user_id'                   => $ewim_userID,
							'inventory_id'              => $ewim_aProduct['inventory_id'],
							'item_id'                   => $ewim_aProduct['id'],
							'transaction_type'          => 'Manufacture',
							'item_amount'               => $amount_Manufacture,
							'total_cost'                => $manufacturing_cost,
							'average_cost'              => $manufacturing_cost / $amount_Manufacture,
							'total_production_cost'     => $ewim_manufacturingCostTotal,
							'average_production_cost'   => $ewim_manufacturingCostTotal / $amount_Manufacture,

							'difference'                => $ewim_difference= 0 - $manufacturing_cost
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
							$ewim_aInsert['item_inventory_quantity']= $ewim_aItem['item_inventory_quantity'] - $amount_Manufacture;
							$ewim_updateResult= ewim_wpdb_edit( 'update', $ewim_editTableName, $ewim_aInsert, $ewim_recordID );
						}
						//endregion
					}
					//endregion

					break;
				case "Sell":
					//region Calculations: Costs, Inventory Amounts
					switch ($ewim_aInventory['inventory_currency_system']){
						case 'Single Currency System':
							$ewim_valueOne= $ewim_aItem['cost'];
							$ewim_aValues= array(
								$inventory_currency
							);

							$ewim_brokerFees= $broker_fee;
							$ewim_salesTax= $sales_tax;

							$ewim_totalSellCost= $inventory_currency;
							$ewim_averageSellCost= $inventory_currency / $amount_Sell;

							$ewim_averageProductionCost= $ewim_aItem['cost'] / $ewim_aItem['item_inventory_quantity'];
							$ewim_totalProductionCost= $amount_Sell * $ewim_averageProductionCost;

							$ewim_difference= $ewim_totalSellCost - ($ewim_salesTax + $ewim_brokerFees + $ewim_totalProductionCost);

							$ewim_doMathResult= ewim_do_math('-', $ewim_valueOne, $ewim_aValues);
							$ewim_aInsert['cost']= (ewim_do_math('-', $ewim_valueOne, $ewim_aValues) > 0 ? $ewim_doMathResult : 0);
							break;
					}

					$ewim_aInsert['item_inventory_quantity']= $ewim_aItem['item_inventory_quantity'] - $amount_Sell;
					//endregion

					//region Insert to DB
					$ewim_updateResult= ewim_wpdb_edit('update',$ewim_editTableName,$ewim_aInsert,$ewim_recordID);
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
					//endregion

					//region Ledger Record Insert
					$ewim_aLedgerInsert=array(
						'user_id'                   => $ewim_userID,
						'inventory_id'              => $ewim_aItem['inventory_id'],
						'item_id'                   => $ewim_aItem['id'],
						'transaction_type'          => 'Sell',
						'item_amount'               => $amount_Sell,
						'average_production_cost'   => $ewim_averageProductionCost,
						'total_production_cost'     => $ewim_totalProductionCost,
						'average_cost'              => $ewim_averageSellCost,
						'total_cost'                => $ewim_totalSellCost,
						'broker_fees'               => $ewim_brokerFees,
						'sales_tax'                 => $ewim_salesTax,
						'difference'                => $ewim_difference
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
				case "Copy":
					//region Calculations: Costs, Inventory Amounts
					switch ($ewim_aInventory['inventory_currency_system']){
						case 'Single Currency System':
							$ewim_averageCopyCost= $copy_cost / $amount_Copy;
							$ewim_difference= 0 - $copy_cost;
							break;
					}
					//endregion

					//region Item Details, Existing Copy : Create Copy
					$ewim_aItem['item_meta']= json_decode($ewim_aItem['item_meta'], true);

					//Check for Design Copy
					if($ewim_aItem['item_meta']['design_copy_id'] > 0){//Design Copy Exists
						$ewim_designCopyID= $ewim_aItem['item_meta']['design_copy_id'];
						$ewim_aDesignCopy= $wpdb->get_row("SELECT * FROM $ewim_cTables->ewim_items WHERE id = '$ewim_designCopyID'", ARRAY_A);

						//Handle Currency
						switch ($ewim_aInventory['inventory_currency_system']){
							case 'Single Currency System':
								$ewim_totalCost= $ewim_aDesignCopy['cost'] + $copy_cost;
								break;
						}

						//Handle Inventory Quantity
						$ewim_totalAmount= $ewim_aDesignCopy['item_inventory_quantity'] + $amount_Copy;

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
					else{//Design Copy needs to be created
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
							'item_inventory_quantity'   => $amount_Copy,
							'cost'                      => $copy_cost
						);

						//Execute Insert
						$ewim_aDesignCopyInsertResult= ewim_wpdb_edit('insert',$ewim_cTables->ewim_items,$ewim_aInsertDesignCopy);
						if($ewim_aDesignCopyInsertResult['error'] == 'Error'){
							//todo Create Friendly Message
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
						}

						//Update Product with DC ID
						$ewim_aItem['item_meta']['design_copy_id']= $ewim_aDesignCopyInsertResult['record_id'];
						$ewim_aUpdateItem['item_meta']= json_encode($ewim_aItem['item_meta']);
						$ewim_aUpdateItemResult= ewim_wpdb_edit('update',$ewim_cTables->ewim_items,$ewim_aUpdateItem,$ewim_aItem['id']);
						if($ewim_aUpdateItemResult['error'] == 'Error'){
							//todo Create Friendly Message
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
						}
					}
					//endregion

					//region Ledger Record Insert
					$ewim_aLedgerInsert=array(
						'user_id'                   => $ewim_userID,
						'inventory_id'              => $ewim_aItem['inventory_id'],
						'item_id'                   => $ewim_aItem['id'],
						'transaction_type'          => 'Copy',
						'item_amount'               => $amount_Copy,
						'total_cost'                => $copy_cost,
						'average_cost'              => $ewim_averageCopyCost,
						'difference'                => $ewim_difference
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

				case "Post":
					$ewim_amountPosted= $amount_Post;
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
						$ewim_difference= 0 - $ewim_brokerFee;
						$ewim_aLedgerInsert=array(
							'user_id'                   => $ewim_userID,
							'inventory_id'                   => $ewim_aItem['inventory_id'],
							'item_id'                   => $ewim_aItem['id'],
							'transaction_type'          => 'Post',
							'item_amount'               => $ewim_amountPosted,
							'average_production_cost'   => $ewim_averageProductionCostCost,
							'total_production_cost'     => $ewim_averageProductionCostCost * $ewim_amountPosted,
							'average_sbpm_cost'         => $ewim_brokerFee / $ewim_amountPosted,
							'total_sbpm_cost'           => $ewim_brokerFee,
							'broker_fees'               => $ewim_brokerFee,
							'sales_tax'                 => 0,
							'difference'                => $ewim_difference
						);
						$ewim_insertResult= ewim_wpdb_edit('insert',$ewim_cTables->ewim_ledger,$ewim_aLedgerInsert);
						//endregion
					}
					//endregion

					//endregion
					break;

			}
			//endregion
			break;
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
						'item_amount'            => $ewim_amountSold,
						'average_production_cost'   => $ewim_aPost['average'],
						'total_production_cost'     => $ewim_totalProductionCost,
						'average_sbpm_cost'         => $ewim_aPost['post_price'],
						'total_sbpm_cost'           => $ewim_totalSellCost,
						'broker_fees'               => $ewim_brokerFees,
						'sales_tax'                 => $ewim_taxesPaid,
						'difference'                => $ewim_totalSellCost - $ewim_taxesPaid - $ewim_brokerFees - $ewim_totalProductionCost
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




			break;
		default:
			break;
	}
	//endregion
}