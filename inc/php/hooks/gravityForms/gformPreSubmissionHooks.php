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
	$ewim_activeGameID= get_user_meta($ewim_userID, 'active_game', true);



	$ewim_aGame= $wpdb->get_row("SELECT * FROM $ewim_cTables->ewim_games WHERE id = $ewim_activeGameID",ARRAY_A);
	//endregion

	//region Form Object Debug
	if($ewim_debug_settings->ewim_formProcessEntry == 1){
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
			//endregion

			//region Default Step 1: Loop the Fields
			foreach($ewim_oForm['fields'] as $ewim_aField){
				$ewim_aCssClass= explode(" ",$ewim_aField['cssClass']);
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

			//Ensure Game ID is set
			$ewim_aInsert['game_id']= $ewim_activeGameID;
			//endregion

			//region Debug; Insert Array
			//todo add if statement
/*
			echo "<pre>";
			print_r($ewim_aInsert);
			echo "</pre>";
			echo "<pre>";
			print_r($_POST);
			echo "</pre>";
			exit;
*/
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
			$amount_Harvest= 0;
			$amount_Process= 0;
			$amount_Sell= 0;
			$amount_Buy= 0;
			$amount_Manufacture= 0;
			$manufacturing_cost= 0;
			$amount_Copy= 0;
			$copy_cost= 0;
			//endregion

			//region DnD Dynamic Variable Declarations
			$Silver= 0;
			$Gold= 0;
			$Copper= 0;
			//endregion

			//region EVE Dynamic Variable Declarations
			$ISK= 0;
			$broker_fee= 0;
			$sales_tax= 0;

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

			//region Catch the Currencies and break them down for use
			switch ($ewim_aGame['game_system']){
				case 'EVE':
					$ewim_formMoney= $ISK;
					break;
				case 'DnD':
					//todo Break silver and gold down to copper, add to copper.
					break;
			}
			//endregion

			//region Item Transaction Step 2: Filter to Correct Action, Make Calculations

			//Get the Item being Manipulated
			$ewim_aItem= $wpdb->get_row("SELECT * FROM $ewim_cTables->ewim_items WHERE id = $ewim_recordID", ARRAY_A);

			switch ($ewim_action){
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
					//endregion

					//region Loop Minerals, insert amount gained and new cost
					foreach($ewim_aDesignDetails as $ewim_aDesignDetail){
						//reset insert array
						$ewim_aInsert= NULL;

						//Get the Design Items basic info into variables to use for full details query
						$ewim_aDesignDetailItem= explode('_', $ewim_aDesignDetail);
						$ewim_refinedResourceName= $ewim_aDesignDetailItem[0];
						$ewim_refinedResourceID= $ewim_aDesignDetailItem[1];

						//Get Refined Resource Record
						$ewim_aRefinedResource= $wpdb->get_row("SELECT * FROM $ewim_cTables->ewim_items WHERE id = $ewim_refinedResourceID",ARRAY_A);

						//Assemble dynamic field names
						$ewim_refinedResourceFieldAdminLabel= "process_".$ewim_refinedResourceName.'_'.$ewim_refinedResourceID;//Dynamic Var with amount of item
						$ewim_refinedResourceProcessingCostFieldAdminLabel= "process_cost_".$ewim_refinedResourceName.'_'.$ewim_refinedResourceID;//Dynamic Var with Cost

						$ewim_aInsert['item_inventory_quantity']= $ewim_aRefinedResource['item_inventory_quantity'] + $$ewim_refinedResourceFieldAdminLabel;
						$ewim_aInsert['cost']= $ewim_aRefinedResource['cost'] + $$ewim_refinedResourceProcessingCostFieldAdminLabel;

						//region Update the Refined Resource Record with new Cost and Total Items
						//region Debug
						if($ewim_debug_settings->ewim_wpdbEdit == 1){
							echo "<h1>Update Refined Resource</h1>";
							echo "Refined Resource: " . $ewim_refinedResourceName . "<br />";
							echo "ID: " . $ewim_refinedResourceID . "<br />";
							echo "Amount: " .$$ewim_refinedResourceFieldAdminLabel. "<br />";
							echo "Cost: " .$$ewim_refinedResourceProcessingCostFieldAdminLabel. "<br />";
							echo "<pre>";
							print_r($ewim_aInsert);
							echo "</pre>";

							exit;
						}
						//endregion
						$ewim_mineralUpdateResult= ewim_wpdb_edit('update',$ewim_cTables->ewim_items,$ewim_aInsert,$ewim_refinedResourceID);
						//endregion

						//Calculate new Difference for transaction
						$ewim_difference= $ewim_difference - $$ewim_refinedResourceProcessingCostFieldAdminLabel;
					}
					//endregion

					//region Remove Processed Amount
					//Reset Insert Array
					$ewim_aInsert= NULL;
					$ewim_aInsert['item_inventory_quantity']= $ewim_aItem['item_inventory_quantity'] - $amount_Process;

					//Insert to DB
					$ewim_updateResult= ewim_wpdb_edit('update',$ewim_cTables->ewim_items,$ewim_aInsert,$ewim_recordID);
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
						$ewim_insertResult= ewim_wpdb_edit('insert',$ewim_cTables->ewim_ledger,$ewim_aLedgerInsert);
						//endregion
					}
					//endregion

					break;
				case "Manufacture":

					//region Initial Variable Declaration
					$ewim_bpcTotalRunCost= 0;
					$ewim_amountManufactured= $amount_Manufacture;
					$ewim_manufacturingStationCost= $ewim_manufacturingCost= $manufacturing_cost;
					//endregion

					//region Get some Basic info on the item
					$ewim_itemCategory= $ewim_aItem['category'];
					$ewim_aItemMeta= json_decode($ewim_aItem['item_meta'], true);
					$ewim_aDesignDetails= explode(',',$ewim_aItem['design_details']);//Blueprint Recipe
					//endregion

					//region Switch on Item Category
					switch ($ewim_aItem['category']){
						case 'Product':
							$ewim_productID= $ewim_aItem['id'];//Product ID
							break;
						case 'Design Copy':
							$ewim_bpcRunCost= $ewim_aItem['cost'] / $ewim_aItem['item_inventory_quantity'];
							$ewim_bpcTotalRunCost= $ewim_bpcRunCost * $ewim_amountManufactured;
							$ewim_productID= $ewim_aItemMeta['product_id'];//Product ID
							break;
					}
					//endregion

					$ewim_aProduct= $wpdb->get_row("SELECT * FROM $ewim_cTables->ewim_items WHERE id = $ewim_productID", ARRAY_A);//Product, needed to calculate new prices and quantities

					//region Loop and adjust ingredients used
					foreach($ewim_aDesignDetails as $ewim_aDesignDetail){
						//Configure Design Detail Item Variables
						$ewim_designDetailItem= explode('_', $ewim_aDesignDetail);
						$ewim_ingredientName= $ewim_designDetailItem[0];
						$ewim_ingredientID= $ewim_designDetailItem[1];

						//reset insert array
						$ewim_aInsert= NULL;

						//Get the Item
						$ewim_aIngredientItem= $wpdb->get_row("SELECT * FROM $ewim_cTables->ewim_items WHERE id = $ewim_ingredientID",ARRAY_A);

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
					unset($ewim_aInsert);

					//Final Costs and Amounts
					$ewim_aInsert['cost']= $ewim_aProduct['cost'] + $ewim_manufacturingCost + $ewim_bpcTotalRunCost;
					$ewim_aInsert['item_inventory_quantity']= $ewim_amountManufactured + $ewim_aProduct['item_inventory_quantity'];

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
						$ewim_insertResult= ewim_wpdb_edit('insert',$ewim_cTables->ewim_ledger,$ewim_aLedgerInsert);
						//endregion

						//region Update BPC Totals
						if($ewim_itemCategory == 'Design Copy') {
							//Reset Insert Array
							unset($ewim_aInsert);
							$ewim_aInsert['cost']= $ewim_aItem['cost'] - $ewim_bpcTotalRunCost;
							$ewim_aInsert['item_inventory_quantity']= $ewim_aItem['item_inventory_quantity'] - $ewim_amountManufactured;
							$ewim_updateResult= ewim_wpdb_edit( 'update', $ewim_editTableName, $ewim_aInsert, $ewim_recordID );
						}
						//endregion
					}
					//endregion

					break;
				case "Buy":

					//region Calculations: Costs, Inventory Amounts
					$ewim_aInsert['cost']= $ewim_aItem['cost'] + $ewim_formMoney;
					$ewim_aInsert['item_inventory_quantity']= $amount_Buy + $ewim_aItem['item_inventory_quantity'];
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
						$ewim_difference= 0 - $ewim_formMoney;
						$ewim_averageBuyCost= $ewim_formMoney / $amount_Buy;
						$ewim_aLedgerInsert=array(
							'user_id'                   => $ewim_userID,
							'game_id'                   => $ewim_aItem['game_id'],
							'item_id'                   => $ewim_aItem['id'],
							'transaction_type'          => 'Buy',
							'item_amount'               => $amount_Buy,
							'average_production_cost'   => 0,
							'total_production_cost'     => 0,
							'average_sbpm_cost'          => $ewim_averageBuyCost,
							'total_sbpm_cost'            => $ewim_formMoney,
							'broker_fees'               => 0,
							'sales_tax'                 => 0,
							'difference'                => $ewim_difference
						);
						$ewim_insertResult= ewim_wpdb_edit('insert',$ewim_cTables->ewim_ledger,$ewim_aLedgerInsert);
						//endregion
					}
					//endregion

					break;
				case "Sell":

					//region Calculations: Costs, Inventory Amounts
					$ewim_itemAmount= $amount_Sell;
					$ewim_totalSellCost= $ewim_formMoney;
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

					//region Insert to DB
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
						$ewim_insertResult= ewim_wpdb_edit('insert',$ewim_cTables->ewim_ledger,$ewim_aLedgerInsert);
						//endregion
					}
					//endregion
					break;
				case "Copy":

					//region Initialize Variables
					$ewim_amountCopy= $amount_Copy;
					$ewim_copyCost= $copy_cost;
					//endregion

					//region Item Details, Existing Copy : Create Copy
					$ewim_aItem= $wpdb->get_row("SELECT * FROM $ewim_cTables->ewim_items WHERE id = '$ewim_recordID'", ARRAY_A);
					$ewim_aItem['item_meta']= json_decode($ewim_aItem['item_meta'], true);

					//Check for Design
					if($ewim_aItem['item_meta']['design_copy_id'] > 0){
						$ewim_designCopyID= $ewim_aItem['item_meta']['design_copy_id'];
						$ewim_aDesignCopy= $wpdb->get_row("SELECT * FROM $ewim_cTables->ewim_items WHERE id = '$ewim_designCopyID'", ARRAY_A);
					}
					else{
						$ewim_itemName= $ewim_aItem['item_name'];
						$ewim_designCopyItemMeta= array(
							'product_id'   => $ewim_aItem['id']
						);
						$ewim_jsDesignCopyItemMeta= json_encode($ewim_designCopyItemMeta);
						$ewim_aInsert= array(
							'user_id'                   => $ewim_aItem['user_id'],
							'game_id'                   => $ewim_aItem['game_id'],
							'item_name'                 => $ewim_itemName.' Copy',
							'category'                  => 'Design Copy',
							'item_meta'                 => $ewim_jsDesignCopyItemMeta,
							'design_details'            => $ewim_aItem['design_details'],
							'item_inventory_quantity'   => 0,
							'cost'                      => 0
						);

						//Execute Insert
						$ewim_aResult= ewim_wpdb_edit('insert',$ewim_cTables->ewim_items,$ewim_aInsert);
						if($ewim_aResult['error'] == 'No'){
							$ewim_designCopyID= $ewim_aResult['record_id'];

							unset($ewim_aResult);
							unset($ewim_aInsert);

							$ewim_aItemMeta['design_copy_id']= $ewim_designCopyID;
							$ewim_aInsert['item_meta']= json_encode($ewim_aItemMeta);

							$ewim_aResult= ewim_wpdb_edit('update',$ewim_cTables->ewim_items,$ewim_aInsert,$ewim_recordID);
							if($ewim_aResult['error'] == 'Error'){
								//todo Create Friendly Message
								if($ewim_debug_settings->ewim_wpdbError == 1){
									echo "<h1>Copy Error Message</h1>";
									echo "<h3>Create Design Copy Record</h3>";
									echo "<h4>Table: </h4>";

									echo "<h4>Result</h4>";
									echo "<pre>";
									print_r($ewim_aResult);
									echo "</pre>";

									echo "<h4>Insert</h4>";
									echo "<pre>";
									print_r($ewim_aInsert);
									echo "</pre>";
									exit;
								}
							}
						}
						else{
							//todo Create Friendly Message
							if($ewim_debug_settings->ewim_wpdbError == 1){
								echo "<h1>Copy Error Message</h1>";
								echo "<h3>Create Design Copy Record</h3>";
								echo "<h4>Table: </h4>";

								echo "<h4>Result</h4>";
								echo "<pre>";
								print_r($ewim_aResult);
								echo "</pre>";

								echo "<h4>Insert</h4>";
								echo "<pre>";
								print_r($ewim_aInsert);
								echo "</pre>";
								exit;
							}
						}
					}
					//endregion


					//region Add Crafted Amount
					//Reset Insert Array
					unset($ewim_aInsert);
					//Final Costs and Amounts
					$ewim_aInsert['cost']= $ewim_aDesignCopy['cost'] + $ewim_copyCost;
					$ewim_aInsert['item_inventory_quantity']= $ewim_aDesignCopy['item_inventory_quantity'] + $ewim_amountCopy;

					//Insert to DB

					//region Debug
					if($ewim_debug_settings->ewim_wpdbEdit == 1){
						echo "<h1>Edit Arrays</h1>";
						echo "Action: " . $ewim_action . "<br />";
						echo "Table: ". $ewim_editTableName . "<br />";
						echo "ID: ".$ewim_designCopyID."<br />";
						echo $ewim_aDesignCopy['cost'] .' ';
						echo "<br />";
						echo $ewim_aDesignCopy['item_inventory_quantity'] .' '. $ewim_amountCopy;
						echo "<pre>";
						print_r($ewim_aInsert);
						echo "</pre>";
						exit;
					}
					//endregion

					$ewim_updateResult= ewim_wpdb_edit('update',$ewim_editTableName,$ewim_aInsert,$ewim_designCopyID);


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
						$ewim_difference= 0 - $ewim_copyCost;
						$ewim_aLedgerInsert=array(
							'user_id'                   => $ewim_userID,
							'game_id'                   => $ewim_aItem['game_id'],
							'item_id'                   => $ewim_aItem['id'],
							'transaction_type'          => 'Copy',
							'item_amount'               => $ewim_amountCopy,
							'average_production_cost'   => $ewim_copyCost / $ewim_amountCopy,
							'total_production_cost'     => $ewim_copyCost,
							'average_sbpm_cost'         => $ewim_copyCost / $ewim_amountCopy,
							'total_sbpm_cost'           => $ewim_copyCost,
							'broker_fees'               => 0,
							'sales_tax'                 => 0,
							'difference'                => $ewim_difference
						);
						$ewim_insertResult= ewim_wpdb_edit('insert',$ewim_cTables->ewim_ledger,$ewim_aLedgerInsert);
						//endregion
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
						'game_id'       => $ewim_activeGameID,
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