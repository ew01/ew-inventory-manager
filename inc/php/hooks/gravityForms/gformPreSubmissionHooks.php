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
	//region Classes, Global Variables, Local Variables
	$ewim_tables= new ewim_tables();
	//$ewim_get_options= new ewim_get_options();
	$ewim_debug_settings= new ewim_debug_settings();
	$ewim_current_user= wp_get_current_user();
	$ewim_userID= $ewim_current_user->ID;
	$ewim_activeGameID= get_user_meta($ewim_userID, 'active_game', true);

	global $wpdb;

	$ewim_aGame= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_games WHERE id = $ewim_activeGameID",ARRAY_A);
	//endregion

	//region Form Object Debug
	if($ewim_debug_settings->ewim_formEntry == 1){
		echo "<h1>Form Entry</h1>";
		echo "<pre>";
		print_r($ewim_oForm);
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
			//endregion

			//region Default Step 1: Loop the Fields
			foreach($ewim_oForm['fields'] as $ewim_aField){
				switch ($ewim_aField['type']){
					case "hidden":
						switch ($ewim_aField['label']){
							case "processor":
								break;
							case "table":
								$ewim_editTableName= $ewim_tables->$_POST['input_'.$ewim_aField['id']];
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
					default:
						$ewim_aCssClass= explode(" ",$ewim_aField['cssClass']);
						if(in_array("ewim_dbField", $ewim_aCssClass)){
							$ewim_aInsert[$ewim_aField['adminLabel']]= $_POST['input_'.$ewim_aField['id']];
						}
						elseif(in_array("item_recipe_ingredients", $ewim_aCssClass)){
							switch ($ewim_aGame['game_system']){
								case "DnD":
									if($_POST['input_'.$ewim_aField['id']] > 0){
										$ewim_aIngredients[$ewim_aField['adminLabel']]= $_POST['input_'.$ewim_aField['id']];
									}
									break;
								case "Eve":
									if(in_array("blueprint", $ewim_aCssClass)){
										$ewim_aIngredient= explode('_',$ewim_aField['adminLabel']);
										$ewim_aIngredients[$ewim_aIngredient[0]]= array(
											'id'        => $ewim_aIngredient[1],
											'amount'    => $_POST['input_'.$ewim_aField['id']]
										);
									}
									else{
										if($_POST['input_'.$ewim_aField['id']] == "Yes"){
											$ewim_aIngredient= explode('_',$ewim_aField['adminLabel']);
											$ewim_aIngredients[$ewim_aIngredient[0]]= $ewim_aIngredient[1];
										}
									}

									break;
							}
						}
						elseif(in_array("bpo", $ewim_aCssClass)){
							$ewim_aItemMeta['BPO']= $_POST['input_'.$ewim_aField['id']];
						}
						elseif(in_array("product", $ewim_aCssClass)){
							$ewim_aItemMeta['Product']= $_POST['input_'.$ewim_aField['id']];
						}
						elseif(in_array("template", $ewim_aCssClass)){
							//Do nothing
						}
						else{
							$ewim_aInputFields[$ewim_aField['adminLabel']]= $_POST['input_'.$ewim_aField['id']];
						}
						break;
				}
			}
			//endregion

			//region Default Step 2: Compile insert array for sql command
			//region If BPC, get info from BPO
			if($ewim_aItemMeta['BPO'] > 0){
				$ewim_bpoID= $ewim_aItemMeta['BPO'];
				$ewim_aBPO= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_items WHERE id = $ewim_bpoID",ARRAY_A);
				$ewim_aIngredients= json_decode($ewim_aBPO['item_recipe_ingredients'], true);
				$ewim_bpoMeta= json_decode($ewim_aBPO['item_meta'], true);
				$ewim_aItemMeta['Product']= $ewim_bpoMeta['Product'];
			}
			//endregion
			if(!empty($ewim_aInputFields)){
				$ewim_aInsert['input_fields']= json_encode($ewim_aInputFields);
			}

			if(!empty($ewim_aIngredients)){
				$ewim_aInsert['item_recipe_ingredients']= json_encode($ewim_aIngredients);
			}

			if(!empty($ewim_aItemMeta)){
				$ewim_aInsert['item_meta']= json_encode($ewim_aItemMeta);
			}

			//endregion

			//region Default Step 3: Call the SQL Edit Function, check for errors
			/** @noinspection PhpUndefinedVariableInspection */
			$ewim_action      = ( $ewim_recordID == 0 ? 'insert' : 'update');
			$ewim_aEditResult = ewim_wpdb_edit($ewim_action,$ewim_editTableName,$ewim_aInsert,$ewim_recordID);
			if($ewim_aEditResult['error'] == 'Error'){
				//todo echo friendly error
				if($ewim_debug_settings->ewim_wpdbEdit == 1){
					echo "<h1>Default Process Result</h1>";
					echo "Action: " . $ewim_action . "<br />";
					echo "Table: ". $ewim_editTableName . "<br />";
					echo "<pre>";
					print_r($ewim_aEditResult['errorMessage']);
					echo "</pre>";
					echo "<pre>";
					print_r($ewim_aInsert);
					echo "</pre>";

					exit;
				}
			}
			else{
				//Place id into form for confirmation results.
				if($ewim_action == 'insert'){
					$_POST['input_'.$ewim_recordIDFieldID]= $ewim_aEditResult['record_id'];
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
			$amount_Sell= 0;
			$amount_Buy= 0;
			$amount_Harvest= 0;
			//endregion

			//region DnD Dynamic Variable Declarations
			$Silver= 0;
			$Gold= 0;
			$Copper= 0;
			$amount_Craft= 0;
			//endregion

			//region EVE Dynamic Variable Declarations
			$ISK= 0;
			$broker_fee= 0;
			$sales_tax= 0;
			$amount_Process= 0;
			$amount_Manufacture= 0;
			$manufacturing_cost= 0;
			$amount_Copy= 0;
			$copy_cost= 0;
			$amount_Post= 0;
			$posted_price= 0;
			//endregion

			//region Item Transaction Step 1: Loop the Fields, Assign to Variables
			foreach($ewim_oForm['fields'] as $ewim_aField){
				switch ($ewim_aField['type']){
					case "hidden":
						switch ($ewim_aField['label']){
							case "processor":
								break;
							case "table":
								$ewim_editTableName= $ewim_tables->$_POST['input_'.$ewim_aField['id']];
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
								$$ewim_aField['adminLabel']= $_POST['input_'.$ewim_aField['id']];
								break;
						}
						break;
				}
			}
			//endregion

			//region Item Transaction Step 2: Filter to Correct Action, Make Calculations
			$ewim_aItem= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_items WHERE id = $ewim_recordID", ARRAY_A);
			switch ($ewim_aGame['game_system']){
				case "DnD":
					switch ($ewim_action){
						case "Buy":
							//Cost Totals
							$ewim_currentTotalCost= $ewim_aItem['item_inventory_quantity'] * $ewim_aItem['cost'];
							$ewim_silver= $Silver + ($Gold * 10);
							$ewim_copper= $Copper + ($ewim_silver * 10);
							$ewim_buyTotalCost= $amount_Buy * $ewim_copper;
							$ewim_newTotalCost= $ewim_currentTotalCost + $ewim_buyTotalCost;

							$ewim_aInsert['item_inventory_quantity']= $amount_Buy + $ewim_aItem['item_inventory_quantity'];
							$ewim_aInsert['cost']= $ewim_newTotalCost / $ewim_aInsert['item_inventory_quantity'];

							//Insert to DB
							$ewim_updateResult= ewim_wpdb_edit('update',$ewim_editTableName,$ewim_aInsert,$ewim_recordID);
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
							else{
								//Assemble and write to ledger
								$ewim_aLedgerInsert=array(
									'user_id'           => $ewim_userID,
									'game_id'           => $ewim_aItem['game_id'],
									'item_id'           => $ewim_aItem['id'],
									'amount'            => $amount_Buy,
									'transaction_type'  => 'Buy',
									'average_cost'      => $ewim_copper,
									'total_cost'        => $ewim_buyTotalCost,
								);
								$ewim_insertResult= ewim_wpdb_edit('insert',$ewim_tables->ewim_ledger,$ewim_aLedgerInsert);

							}

							break;
						case "Sell":
							//Cost Totals
							$ewim_silver= $Silver + ($Gold * 10);
							$ewim_copper= $Copper + ($ewim_silver * 10);
							$ewim_sellTotalCost= $amount_Sell * $ewim_copper;

							$ewim_aInsert['item_inventory_quantity']= $ewim_aItem['item_inventory_quantity'] - $amount_Sell;

							//Insert to DB
							$ewim_updateResult= ewim_wpdb_edit('update',$ewim_editTableName,$ewim_aInsert,$ewim_recordID);
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
							else{
								//Assemble and write to ledger
								$ewim_aLedgerInsert=array(
									'user_id'           => $ewim_userID,
									'game_id'           => $ewim_aItem['game_id'],
									'item_id'           => $ewim_aItem['id'],
									'amount'            => $amount_Sell,
									'transaction_type'  => 'Sell',
									'average_cost'      => $ewim_copper,
									'total_cost'        => $ewim_sellTotalCost,
								);
								$ewim_insertResult= ewim_wpdb_edit('insert',$ewim_tables->ewim_ledger,$ewim_aLedgerInsert);
							}

							break;
						case "Harvest":
							$ewim_currentTotalCost= $ewim_aItem['item_inventory_quantity'] * $ewim_aItem['cost'];
							$ewim_aInsert['item_inventory_quantity']= $amount_Harvest + $ewim_aItem['item_inventory_quantity'];
							$ewim_aInsert['cost']= $ewim_currentTotalCost / $ewim_aInsert['item_inventory_quantity'];

							//Insert to DB
							$ewim_updateResult= ewim_wpdb_edit('update',$ewim_editTableName,$ewim_aInsert,$ewim_recordID);
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
							else{
								//$ewim_successMessage= '';
							}

							break;
						case "Craft":
							$ewim_craftingCost= 0;

							$ewim_aItemRecipe= json_decode($ewim_aItem['item_recipe_ingredients'],true);
							foreach($ewim_aItemRecipe as $ewim_ingredientNameID => $ewim_ingredientAmount){
								$ewim_aInsert= NULL;

								$ewim_aIngredientItemNameID= explode("_",$ewim_ingredientNameID);
								$ewim_ingredientID= $ewim_aIngredientItemNameID[1];

								$ewim_aIngredientItem= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_items WHERE id = $ewim_ingredientID",ARRAY_A);
								$ewim_craftingCost= $ewim_craftingCost + ($ewim_aIngredientItem['cost'] * $ewim_ingredientAmount);

								$ewim_aInsert['item_inventory_quantity']= $ewim_aIngredientItem['item_inventory_quantity'] - $ewim_ingredientAmount;

								//Remove Ingredients
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
								else{
									//$ewim_successMessage= '';
								}
							}

							//Add Crafted Amount
							$ewim_aInsert= NULL;

							$ewim_currentTotalCost= $ewim_aItem['item_inventory_quantity'] * $ewim_aItem['cost'];
							$ewim_newTotalCost= $ewim_craftingCost + $ewim_currentTotalCost;

							$ewim_aInsert['item_inventory_quantity']= $amount_Craft + $ewim_aItem['item_inventory_quantity'];
							$ewim_aInsert['cost']= $ewim_newTotalCost / $ewim_aInsert['item_inventory_quantity'];


							//Insert to DB
							$ewim_updateResult= ewim_wpdb_edit('update',$ewim_editTableName,$ewim_aInsert,$ewim_recordID);
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
							else{
								//$ewim_successMessage= '';
							}

							break;
					}
					break;
				case "Eve":
					switch ($ewim_action){
						case "Buy":
							//region Calculations: Costs, Inventory Amounts
							$ewim_aInsert['cost']= $ewim_aItem['cost'] + $ISK;
							$ewim_aInsert['item_inventory_quantity']= $amount_Buy + $ewim_aItem['item_inventory_quantity'];
							//endregion

							//region Insert to DB
							$ewim_updateResult= ewim_wpdb_edit('update',$ewim_editTableName,$ewim_aInsert,$ewim_recordID);
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
							else{
								//region Assemble and write to ledger
								$ewim_difference= 0 - $ISK;
								$ewim_averageBuyCost= $ISK / $amount_Buy;
								$ewim_aLedgerInsert=array(
									'user_id'                   => $ewim_userID,
									'game_id'                   => $ewim_aItem['game_id'],
									'item_id'                   => $ewim_aItem['id'],
									'transaction_type'          => 'Buy',
									'item_amount'               => $amount_Buy,
									'average_production_cost'   => 0,
									'total_production_cost'     => 0,
									'average_sbpm_cost'          => $ewim_averageBuyCost,
									'total_sbpm_cost'            => $ISK,
									'broker_fees'               => 0,
									'sales_tax'                 => 0,
									'difference'                => $ewim_difference
								);
								$ewim_insertResult= ewim_wpdb_edit('insert',$ewim_tables->ewim_ledger,$ewim_aLedgerInsert);
								//endregion
							}
							//endregion

							break;
						case "Sell":
							//region Calculations: Costs, Inventory Amounts
							$ewim_itemAmount= $amount_Sell;
							$ewim_totalSellCost= $ISK;
							$ewim_averageSellCost= $ewim_totalSellCost / $ewim_itemAmount;
							$ewim_brokerFees= $broker_fee;
							$ewim_salesTax= $sales_tax;
							$ewim_averageProductionCost= $ewim_aItem['cost'] / $ewim_aItem['item_inventory_quantity'];
							$ewim_totalProductionCost= $ewim_itemAmount * $ewim_averageProductionCost;


							$ewim_aInsert['item_inventory_quantity']= $ewim_aItem['item_inventory_quantity'] - $ewim_itemAmount;


							$ewim_difference= $ewim_totalSellCost - ($ewim_salesTax + $ewim_brokerFees + $ewim_totalProductionCost);

							if($ewim_difference < 0){
								$ewim_aInsert['cost']= $ewim_aItem['cost'] - ($ewim_totalSellCost - ($ewim_salesTax + $ewim_brokerFees));
							}
							else{
								$ewim_aInsert['cost']= $ewim_aItem['cost'] - $ewim_totalProductionCost;
							}
							//endregion

							//region Debug
							if($ewim_debug_settings->ewim_wpdbEdit == 1){
								echo "<h1>Insert</h1>";
								echo "<p>Difference: $ewim_difference</p>";
								echo "<p>Sale Cost: $ewim_totalSellCost</p>";
								echo "<p>Tax: $ewim_salesTax</p>";
								echo "<p>Broker: $ewim_brokerFees</p>";
								echo "<pre>";
								print_r($ewim_aInsert);
								echo "</pre>";
								exit;
							}
							//endregion

							//Insert to DB
							$ewim_updateResult= ewim_wpdb_edit('update',$ewim_editTableName,$ewim_aInsert,$ewim_recordID);
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
							else{
								//region Assemble and write to ledger
								$ewim_aLedgerInsert=array(
									'user_id'                   => $ewim_userID,
									'game_id'                   => $ewim_aItem['game_id'],
									'item_id'                   => $ewim_aItem['id'],
									'transaction_type'          => 'Sell',
									'item_amount'               => $ewim_itemAmount,
									'average_production_cost'   => $ewim_averageProductionCost,
									'total_production_cost'     => $ewim_totalProductionCost,
									'average_sbpm_cost'          => $ewim_averageSellCost,
									'total_sbpm_cost'            => $ewim_totalSellCost,
									'broker_fees'               => $ewim_brokerFees,
									'sales_tax'                 => $ewim_salesTax,
									'difference'                => $ewim_difference
								);
								$ewim_insertResult= ewim_wpdb_edit('insert',$ewim_tables->ewim_ledger,$ewim_aLedgerInsert);
								//endregion
							}

							break;
						case "Process":
							//region Get Minerals
							$ewim_aMinerals= json_decode($ewim_aItem['item_recipe_ingredients'],true);
							$ewim_difference= 0;
							//endregion

							//region Loop Minerals, insert amount gained and new cost
							foreach($ewim_aMinerals as $ewim_mineralName => $ewim_mineralID){
								//reset insert array
								$ewim_aInsert= NULL;
								//Get mineral Record
								$ewim_aIngredientItem= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_items WHERE id = $ewim_mineralID",ARRAY_A);
								//Assemble dynamic field names
								$ewim_mineralFieldAdminLabel= "process_".$ewim_mineralName.'_'.$ewim_mineralID;
								$ewim_mineralProcessingCostFieldAdminLabel= "process_cost_".$ewim_mineralName.'_'.$ewim_mineralID;


								$ewim_aInsert['item_inventory_quantity']= $ewim_aIngredientItem['item_inventory_quantity'] + $$ewim_mineralFieldAdminLabel;
								$ewim_aInsert['cost']= $$ewim_mineralProcessingCostFieldAdminLabel + $ewim_aIngredientItem['cost'];

								$ewim_mineralUpdateResult= ewim_wpdb_edit('update',$ewim_tables->ewim_items,$ewim_aInsert,$ewim_mineralID);

								$ewim_difference= $ewim_difference - $$ewim_mineralProcessingCostFieldAdminLabel;
							}
							//endregion

							//region Remove Processed Amount
							//Reset Insert Array
							$ewim_aInsert= NULL;
							$ewim_aInsert['item_inventory_quantity']= $ewim_aItem['item_inventory_quantity'] - $amount_Process;

							//Insert to DB
							$ewim_updateResult= ewim_wpdb_edit('update',$ewim_tables->ewim_items,$ewim_aInsert,$ewim_recordID);
							if($ewim_updateResult['error'] == 'Error'){
								//todo echo friendly error
								if($ewim_debug_settings->ewim_wpdbEdit == 1){
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
								//region Assemble and write to ledger
								$ewim_aLedgerInsert=array(
									'user_id'                   => $ewim_userID,
									'game_id'                   => $ewim_aItem['game_id'],
									'item_id'                   => $ewim_aItem['id'],
									'transaction_type'          => 'Process',
									'item_amount'               => $amount_Process,
									'average_production_cost'   => 0,
									'total_production_cost'     => 0,
									'average_sbpm_cost'          => 0,
									'total_sbpm_cost'            => 0,
									'broker_fees'               => 0,
									'sales_tax'                 => 0,
									'difference'                => $ewim_difference
								);
								$ewim_insertResult= ewim_wpdb_edit('insert',$ewim_tables->ewim_ledger,$ewim_aLedgerInsert);
								//endregion
							}
							//endregion

							break;
						case "Manufacture":
							//region Initial Variable Declaration
							$ewim_bpcTotalRunCost= 0;
							//endregion
							$ewim_amountManufactured= $amount_Manufacture;
							$ewim_manufacturingStationCost= $ewim_manufacturingCost= $manufacturing_cost;

							//region Get some Basic info on the item
							$ewim_itemCategory= $ewim_aItem['category'];
							$ewim_aItemMeta= json_decode($ewim_aItem['item_meta'], true);
							$ewim_aItemRecipe= json_decode($ewim_aItem['item_recipe_ingredients'],true);//Blueprint Recipe
							$ewim_productID= $ewim_aItemMeta['Product'];//Product ID
							$ewim_aProduct= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_items WHERE id = $ewim_productID", ARRAY_A);//Product, needed to calculate new prices and quantities
							//endregion

							//region Switch on Item Category
							if($ewim_itemCategory == 'Blueprint Copy'){
								$ewim_bpcRunCost= $ewim_aItem['cost'] / $ewim_aItem['item_inventory_quantity'];
								$ewim_bpcTotalRunCost= $ewim_bpcRunCost * $ewim_amountManufactured;
							}
							//endregion

							//region Loop and adjust ingredients used
							foreach($ewim_aItemRecipe as $ewim_ingredientName => $ewim_ingredientID){
								//reset insert array
								$ewim_aInsert= NULL;
								//Get the Item
								$ewim_aIngredientItem= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_items WHERE id = $ewim_ingredientID",ARRAY_A);

								$ewim_ingredientFieldAdminLabel= "manufacture_".$ewim_ingredientName.'_'.$ewim_ingredientID;

								$ewim_manufacturingCost= $ewim_manufacturingCost + (($ewim_aIngredientItem['cost'] / $ewim_aIngredientItem['item_inventory_quantity']) * $$ewim_ingredientFieldAdminLabel);

								$ewim_aInsert['item_inventory_quantity']= $ewim_aIngredientItem['item_inventory_quantity'] - $$ewim_ingredientFieldAdminLabel;
								$ewim_aInsert['cost']= $ewim_aIngredientItem['cost'] - (($ewim_aIngredientItem['cost'] / $ewim_aIngredientItem['item_inventory_quantity']) * $$ewim_ingredientFieldAdminLabel);

								//region Remove Ingredients
								$ewim_updateResult= ewim_wpdb_edit('update',$ewim_editTableName,$ewim_aInsert,$ewim_ingredientID);
								//endregion
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
							}
							//endregion

							//region Add Crafted Amount
							//Reset Insert Array
							$ewim_aInsert= NULL;
							//Final Costs and Amounts
							$ewim_aInsert['cost']= $ewim_aProduct['cost'] + $ewim_manufacturingCost + $ewim_bpcTotalRunCost;
							$ewim_aInsert['item_inventory_quantity']= $ewim_amountManufactured + $ewim_aProduct['item_inventory_quantity'];

							//Insert to DB
							$ewim_updateResult= ewim_wpdb_edit('update',$ewim_editTableName,$ewim_aInsert,$ewim_productID);

							//region Debug
							if($ewim_debug_settings->ewim_wpdbEdit == 1){
								echo "<h1>Edit Result</h1>";
								echo "Action: " . $ewim_action . "<br />";
								echo "Table: ". $ewim_editTableName . "<br />";
								echo "ID: ".$ewim_productID."<br />";
								echo "<pre>";
								print_r($ewim_updateResult['errorMessage']);
								print_r($ewim_aItemMeta);
								echo "</pre>";
								exit;
							}
							//endregion

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
							else{
								//region Assemble and write to ledger
								$ewim_difference= 0 - $ewim_manufacturingStationCost;
								$ewim_aLedgerInsert=array(
									'user_id'                   => $ewim_userID,
									'game_id'                   => $ewim_aItem['game_id'],
									'item_id'                   => $ewim_aItem['id'],
									'transaction_type'          => 'Manufacture',
									'item_amount'               => $ewim_amountManufactured,

									'average_production_cost'   => $ewim_manufacturingCost / $ewim_amountManufactured,
									'total_production_cost'     => $ewim_manufacturingCost,
									'average_sbpm_cost'          => $ewim_manufacturingStationCost / $ewim_amountManufactured,
									'total_sbpm_cost'            => $ewim_manufacturingStationCost,
									'broker_fees'               => 0,
									'sales_tax'                 => 0,
									'difference'                => $ewim_difference
								);
								$ewim_insertResult= ewim_wpdb_edit('insert',$ewim_tables->ewim_ledger,$ewim_aLedgerInsert);
								//endregion

								//region Update BPC Totals
								if($ewim_itemCategory == 'Blueprint Copy') {
									//Reset Insert Array
									$ewim_aInsert                            = null;
									$ewim_aInsert['cost']                    = $ewim_aItem['cost'] - $ewim_bpcTotalRunCost;
									$ewim_aInsert['item_inventory_quantity'] = $ewim_aItem['item_inventory_quantity'] - $ewim_amountManufactured;
									$ewim_updateResult                       = ewim_wpdb_edit( 'update', $ewim_editTableName, $ewim_aInsert, $ewim_recordID );
								}
								//endregion
							}
							//endregion
							break;
						case "Copy":
							//region Initialize Variables
							$ewim_aBPC= array();
							$ewim_bpcID= 0;
							//endregion
							$ewim_amountCopy= $amount_Copy;
							$ewim_copyCost= $copy_cost;

							//region Get BPCs, loop and find correct BPC
							$ewim_aBPCs= $wpdb->get_results("SELECT * FROM $ewim_tables->ewim_items WHERE category = 'Blueprint Copy'", ARRAY_A);

							foreach($ewim_aBPCs as $ewim_aBPCData){
								$ewim_aBPCMeta= json_decode($ewim_aBPCData['item_meta'], true);
								if($ewim_aBPCMeta['BPO'] == $ewim_recordID){
									$ewim_aBPC= $ewim_aBPCData;
									$ewim_bpcID= $ewim_aBPC['id'];
									break;
								}
							}
							//endregion

							//region Add Crafted Amount
							//Reset Insert Array
							$ewim_aInsert= NULL;
							//Final Costs and Amounts
							$ewim_aInsert['cost']= $ewim_aBPC['cost'] + $ewim_copyCost;
							$ewim_aInsert['item_inventory_quantity']= $ewim_aBPC['item_inventory_quantity'] + $ewim_amountCopy;

							//Insert to DB
							$ewim_updateResult= ewim_wpdb_edit('update',$ewim_editTableName,$ewim_aInsert,$ewim_bpcID);

							//region Debug
							if($ewim_debug_settings->ewim_wpdbEdit == 1){
								echo "<h1>Edit Result</h1>";
								echo "Action: " . $ewim_action . "<br />";
								echo "Table: ". $ewim_editTableName . "<br />";
								echo "ID: ".$ewim_bpcID."<br />";
								echo "<pre>";
								print_r($ewim_updateResult['errorMessage']);
								echo "</pre>";
								exit;
							}
							//endregion

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
							else{
								//region Assemble and write to ledger
								$ewim_difference= 0 - $ewim_copyCost;
								$ewim_aLedgerInsert=array(
									'user_id'                   => $ewim_userID,
									'game_id'                   => $ewim_aItem['game_id'],
									'item_id'                   => $ewim_aItem['id'],
									'transaction_type'          => 'Copy',
									'item_amount'               => $ewim_copyCost,
									'average_production_cost'   => $ewim_copyCost / $ewim_amountCopy,
									'total_production_cost'     => $ewim_copyCost,
									'average_sbpm_cost'         => $ewim_copyCost / $ewim_amountCopy,
									'total_sbpm_cost'           => $ewim_copyCost,
									'broker_fees'               => 0,
									'sales_tax'                 => 0,
									'difference'                => $ewim_difference
								);
								$ewim_insertResult= ewim_wpdb_edit('insert',$ewim_tables->ewim_ledger,$ewim_aLedgerInsert);
								//endregion
							}
							//endregion
							break;
						case "Harvest":
							$ewim_amountHarvested= $amount_Harvest;

							$ewim_aInsert['item_inventory_quantity']= $ewim_amountHarvested + $ewim_aItem['item_inventory_quantity'];

							//Insert to DB
							$ewim_updateResult= ewim_wpdb_edit('update',$ewim_editTableName,$ewim_aInsert,$ewim_recordID);
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
								'game_id'       => $ewim_activeGameID,
								'item_id'       => $ewim_recordID,
								'amount'        => $ewim_amountPosted,
								'broker_fee'    => $ewim_brokerFee,
								'status'        => 'Posted',
								'post_price'    => $ewim_postPrice,
								'average'       => $ewim_averageProductionCostCost
							);
							$ewim_insertPostedResult= ewim_wpdb_edit('insert',$ewim_tables->ewim_posted,$ewim_aInsertPosted);

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
									'game_id'                   => $ewim_aItem['game_id'],
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
								$ewim_insertResult= ewim_wpdb_edit('insert',$ewim_tables->ewim_ledger,$ewim_aLedgerInsert);
								//endregion
							}
							//endregion

							//endregion
							break;
					}
					break;
			}

			//endregion
						break;
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
								//$ewim_editTableName= $ewim_tables->$_POST['input_'.$ewim_aField['id']];
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
								$$ewim_aField['adminLabel']= $_POST['input_'.$ewim_aField['id']];
								break;
						}
						break;
				}
			}
			//endregion

			switch ($ewim_action){
				case 'Sell':
					//region Sell Posted Step 1
					$ewim_aPost= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_posted WHERE id = $ewim_recordID",ARRAY_A);//Get Items

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

					$ewim_insertPostedResult= ewim_wpdb_edit('update',$ewim_tables->ewim_posted,$ewim_aInsertPosted,$ewim_recordID);
					//endregion

					//region Write to Ledger
					$ewim_totalProductionCost= $ewim_aPost['average'] * $ewim_amountSold;
					$ewim_totalSellCost= $ewim_aPost['post_price'] * $ewim_amountSold;
					$ewim_brokerFees= ($ewim_aPost['broker_fee'] / $ewim_aPost['amount']) * $ewim_amountSold;
					$ewim_aLedgerInsert=array(
						'user_id'           => $ewim_userID,
						'game_id'           => $ewim_aPost['game_id'],
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
					$ewim_insertResult= ewim_wpdb_edit('insert',$ewim_tables->ewim_ledger,$ewim_aLedgerInsert);
					//endregion

					break;
				case 'Remove':
					//region Remove Posted Step 1
					$ewim_aPost= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_posted WHERE id = $ewim_recordID",ARRAY_A);//Get Items

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

					$ewim_insertPostedResult= ewim_wpdb_edit('update',$ewim_tables->ewim_posted,$ewim_aInsertPosted,$ewim_recordID);
					//endregion

					//region Update Item Record
					$ewim_itemID= $ewim_aPost['item_id'];
					$ewim_aItem= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_items WHERE id = $ewim_itemID",ARRAY_A);//Get Items

					$ewim_totalProductionCost= $ewim_aPost['average'] * $ewim_amountRemoved;
					$ewim_brokerFees= ($ewim_aPost['broker_fee'] / $ewim_aPost['amount']) * $ewim_amountRemoved;
					$ewim_newTotalProductionCost= $ewim_totalProductionCost + $ewim_brokerFees;

					$ewim_aInsert['cost']= $ewim_aItem['cost'] + $ewim_newTotalProductionCost;

					$ewim_updateResult= ewim_wpdb_edit('update',$ewim_tables->ewim_items,$ewim_aInsert,$ewim_itemID);
					//endregion

					break;
			}




			break;
		default:
			break;
	}
	//endregion
}