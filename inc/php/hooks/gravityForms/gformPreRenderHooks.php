<?php
/**
 * Created by PhpStorm.
 * Author: David
 * Date: 8/3/2018
 * Time: 13:55
 * Name: Form Populate Hooks
 * Desc: The hooks here will populate Gravity Forms with data from the WP DB
 */
/** @noinspection PhpUndefinedVariableInspection */
//todo: Look into turning all of this into a class



/**
 * Name: Create Input Fields
 * Desc: Creates all the input fields for each item related form.
 */
//region Filters
add_filter( "gform_pre_render", 'create_input_fields', 1 );
add_filter( "gform_pre_validation", 'create_input_fields', 1 );
add_filter( "gform_pre_submission_filter", 'create_input_fields', 1 );
//endregion
function create_input_fields($ewim_oForm){
	//region Global Variables, Classes, Class Variables, Local Variables
	global $wpdb;

	$ewim_tables= new ewim_tables();
	$ewim_debug_settings= new ewim_debug_settings();
	$ewim_getOptions= new ewim_get_options();
	$ewim_current_user= wp_get_current_user();

	$ewim_userID= $ewim_current_user->ID;
	$ewim_activeGameID= get_user_meta($ewim_userID, 'active_game', true);
	//endregion

	//region Debug: Form Start
	if($ewim_debug_settings->ewim_CreateFieldsFormStart == 1){
		echo "<h1>Create Fields Form Start</h1>";
		echo "<pre style='color:red;'>";
		print_r($ewim_oForm);
		echo "</pre>";
		exit;
	}
	//endregion

	//region Step 1: Get record ID if passed. If we get a record id we get the record so we can fill out the form
	if(isset($_REQUEST['record_id']) and $_REQUEST['record_id'] != '') {
		$ewim_itemID= $_REQUEST['record_id'];
		$ewim_aItem= $wpdb->get_row( "SELECT * FROM $ewim_tables->ewim_items WHERE id = '$ewim_itemID'", ARRAY_A );
	}
	//endregion

	//region Step 2: Switch to processor based on Form ID
	switch ($ewim_oForm['id']){
		case $ewim_getOptions->ewim_itemFormID:
			//region Get the game system, set game system variables
			$ewim_aGame= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_games WHERE id = $ewim_activeGameID",ARRAY_A);

			switch ($ewim_aGame['game_system']){
				case 'EVE':
					$ewim_categoryList= 'default_item_categories';
					$ewim_aCategories= ewim_get_meta_value($ewim_categoryList);
					break;
				case 'DND':
					$ewim_categoryList= 'default_item_categories';
					$ewim_aCategories= ewim_get_meta_value($ewim_categoryList);
					break;
				default:
					$ewim_categoryList= 'default_item_categories';
					$ewim_aCategories= ewim_get_meta_value($ewim_categoryList);
					break;
			}
			//endregion

			//region Loop Fields. Get any template fields, and find id fields for conditional logic
			$ewim_fieldCount= 0;//This count is for the array element of the fields in the form array
			foreach ($ewim_oForm['fields'] as &$ewim_oField){
				if($ewim_oField->adminLabel == 'single_line_text_template'){
					$ewim_oSingleLineTextFieldTemplate= clone $ewim_oField;
				}
				if($ewim_oField->adminLabel == 'radio_template'){
					$ewim_oRadioFieldTemplate= clone $ewim_oField;
				}
				//todo this may get used for recipe amounts
				/*if($ewim_oField->adminLabel == 'number_template'){
					$ewim_oNumberFieldTemplate= clone $ewim_oField;
				}*/
				if($ewim_oField->type == 'section'){
					$ewim_oSectionFieldTemplate= $ewim_oField;
				}
				if($ewim_oField->adminLabel == 'drop_down_template'){
					$ewim_oDropDownFieldTemplate= clone $ewim_oField;
				}
				if($ewim_oField->adminLabel == 'checkbox_template'){
					$ewim_oCheckboxFieldTemplate= clone $ewim_oField;
				}


				$ewim_fieldCount++;
			}
			//endregion

			//region Set starting values for variables changed/increased by loop
			$ewim_itemCount= 1;
			$ewim_fieldID= 1000;
			//endregion

			//region Create and Label Fields

			//region Item Name; Single Line Text Field
			$ewim_oNewField= clone $ewim_oSingleLineTextFieldTemplate;
			$ewim_oNewField->label= 'Name';//Display Label
			$ewim_oNewField->adminLabel= 'item_name';//Backend Label
			$ewim_oNewField->inputName= 'item_name';//Pre Populate Label
			$ewim_oNewField->defaultValue= (isset($ewim_itemID) ? $ewim_aItem['item_name'] : '');
			$ewim_oNewField->visibility= 'visible';
			$ewim_oNewField->id= $ewim_fieldID;
			$ewim_oNewField->isRequired= 1;
			$ewim_oNewField->cssClass= 'ewim_dbField';

			//Push our new field object into the form object
			$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewField;

			//Increase Counts
			$ewim_fieldCount++;
			$ewim_fieldID++;
			//endregion

			//region Item Category; Drop Down Field
			$ewim_oNewField= clone $ewim_oDropDownFieldTemplate;
			$ewim_oNewField->label= 'Category';
			$ewim_oNewField->adminLabel= 'category';
			$ewim_oNewField->inputName= 'category';
			$ewim_oNewField->visibility= 'visible';
			$ewim_oNewField->id= $ewim_categoryFieldID= $ewim_fieldID;
			$ewim_oNewField->isRequired= 1;
			$ewim_oNewField->cssClass= 'ewim_dbField';
			$ewim_oNewField->placeholder= 'Select One';

			$ewim_choicesCount= 0;
			foreach($ewim_aCategories as $ewim_aCategory){
				$ewim_oNewField->choices[$ewim_choicesCount]= array(
					'text'  => $ewim_aCategory,
					'value'  => $ewim_aCategory
				);
				$ewim_choicesCount++;
			}

			$ewim_oNewField->defaultValue= (isset($ewim_itemID) ? $ewim_aItem['category'] : '');

			//Place New Field into Form
			$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewField;

			//Increase Counters
			$ewim_fieldCount++;
			$ewim_itemCount++;
			$ewim_fieldID++;

			//endregion

			//region Category Refined Resource Empty

			//Nothing to do for minerals, yet.

			//endregion

			//region Category Raw Resource; Section
			$ewim_oNewField= clone $ewim_oSectionFieldTemplate;
			$ewim_oNewField->label= 'Choose what Refined Resources are contained in Raw Resource';
			$ewim_oNewField->visibility= 'visible';
			$ewim_oNewField->id= $ewim_fieldID;
			$ewim_oNewField->conditionalLogic= array(
				'actionType'    => 'show',
				'logicType'     => 'any',
				'rules'         => array(
					array(
						'fieldId'   => $ewim_categoryFieldID,
						'operator'  => 'is',
						'value'     => 'Raw Resource'
					)
				)
			);
			$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewField;//Push our new field object into the form object
			$ewim_fieldCount++;
			$ewim_fieldID++;
			//endregion

			//region Category Product; Section
			$ewim_oNewSection= clone $ewim_oSectionFieldTemplate;
			$ewim_oNewSection->label= 'Select Refined Resources and Components Used to create Product';
			$ewim_oNewSection->visibility= 'visible';
			$ewim_oNewSection->id= $ewim_fieldID;
			$ewim_oNewSection->conditionalLogic= array(
				'actionType'    => 'show',
				'logicType'     => 'any',
				'rules'         => array(
					array(
						'fieldId'   => $ewim_categoryFieldID,
						'operator'  => 'is',
						'value'     => 'Product'
					)
				)
			);
			$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewSection;//Push our new field object into the form object
			$ewim_fieldCount++;
			$ewim_fieldID++;
			//endregion

			//region Category Component; Section
			$ewim_oNewSection= clone $ewim_oSectionFieldTemplate;
			$ewim_oNewSection->label= 'Select Refined Resources Used to create Component';
			$ewim_oNewSection->visibility= 'visible';
			$ewim_oNewSection->id= $ewim_fieldID;
			$ewim_oNewSection->conditionalLogic= array(
				'actionType'    => 'show',
				'logicType'     => 'any',
				'rules'         => array(
					array(
						'fieldId'   => $ewim_categoryFieldID,
						'operator'  => 'is',
						'value'     => 'Component'
					)
				)
			);
			$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewSection;//Push our new field object into the form object
			$ewim_fieldCount++;
			$ewim_fieldID++;
			//endregion

			//region CategoryBPC //todo Don't think we need this anymore

			//endregion

			//region Refined Resources List; Section
			$ewim_oNewSection= clone $ewim_oSectionFieldTemplate;
			$ewim_oNewSection->label= 'Refined Resources';
			$ewim_oNewSection->visibility= 'visible';
			$ewim_oNewSection->id= $ewim_fieldID;
			$ewim_oNewSection->conditionalLogic= array(
				'actionType'    => 'show',
				'logicType'     => 'any',
				'rules'         => array(
					array(
						'fieldId'   => $ewim_categoryFieldID,
						'operator'  => 'is',
						'value'     => 'Design'
					),
					array(
						'fieldId'   => $ewim_categoryFieldID,
						'operator'  => 'is',
						'value'     => 'Product'
					),
					array(
						'fieldId'   => $ewim_categoryFieldID,
						'operator'  => 'is',
						'value'     => 'Raw Resource'
					),
					array(
						'fieldId'   => $ewim_categoryFieldID,
						'operator'  => 'is',
						'value'     => 'Component'
					)
				)
			);
			$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewSection;//Push our new field object into the form object
			$ewim_fieldCount++;
			$ewim_fieldID++;
			//endregion

			//region Refined Resources List; Checkboxes
			$ewim_oNewField= clone $ewim_oCheckboxFieldTemplate;//Clone the Field Object
			$ewim_oNewField->label= '';//Label that displays
			$ewim_oNewField->allowsPrepopulate= 1;//Makes it usable by pre populate functions
			$ewim_oNewField->inputName= 'design_details';//Label used for pre populate
			$ewim_oNewField->adminLabel= 'design_details';//Backend label to access field
			$ewim_oNewField->visibility= 'visible';//Make it Visible
			$ewim_oNewField->cssClass= 'gf_list_3col design_details';//Add in the alternating CSS
			$ewim_oNewField->id= $ewim_fieldID;//Give it an id
			//$ewim_oNewField->isRequired= 1;

			$ewim_aRefinedResources= $wpdb->get_results("SELECT * FROM $ewim_tables->ewim_items WHERE game_id = $ewim_activeGameID AND category = 'Refined Resource' ORDER BY item_name",ARRAY_A);

			$ewim_aDesign= (isset($ewim_itemID) ? explode(',',$ewim_aItem['design_details']) : array());

			$ewim_rsC= 0;
			foreach($ewim_aRefinedResources as $ewim_aRefinedResource){
				//skipping index that are multiples of 10 (multiples of 10 create problems as the input IDs)
				if ( $ewim_rsC != 0 && $ewim_rsC % 10 == 0 ) {
					$ewim_rsC++;
				}

				//Check for used minerals since standard populate values is not working for dynamic checkboxes ATM
				$ewim_isSelected= (in_array($ewim_aRefinedResource['item_name'].'_'.$ewim_aRefinedResource['id'],$ewim_aDesign) ? 'Yes' : 0);

				$ewim_oNewField->choices[$ewim_rsC]= array(
					'text'  => $ewim_aRefinedResource['item_name'],
					'value' => $ewim_aRefinedResource['item_name'].'_'.$ewim_aRefinedResource['id'],
					//'value' => $ewim_aRefinedResource['id'],
					'isSelected' => $ewim_isSelected,
					'price' => ''
				);

				$ewim_oNewField->inputs[$ewim_rsC]= array(
					'id'  => $ewim_fieldID.".".$ewim_rsC,
					'label' => $ewim_aRefinedResource['item_name'].'_'.$ewim_aRefinedResource['id'],
					'name' => ''
				);

				$ewim_rsC++;
			}

			$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewField;//Push our new field object into the form object

			//Increase our counters, alternate our strings
			$ewim_fieldCount++;
			$ewim_itemCount++;
			$ewim_fieldID++;
			$ewim_fieldCSS= ($ewim_fieldCount / 2 ? 'item_recipe_ingredients gf_right_half' : 'item_recipe_ingredients gf_left_half');
			//endregion

			//region Components List; Section
			$ewim_oNewSection= clone $ewim_oSectionFieldTemplate;
			$ewim_oNewSection->label= 'Components';
			$ewim_oNewSection->visibility= 'visible';
			$ewim_oNewSection->id= $ewim_fieldID;
			$ewim_oNewSection->conditionalLogic= array(
				'actionType'    => 'show',
				'logicType'     => 'any',
				'rules'         => array(
					array(
						'fieldId'   => $ewim_categoryFieldID,
						'operator'  => 'is',
						'value'     => 'Design'
					),
					array(
						'fieldId'   => $ewim_categoryFieldID,
						'operator'  => 'is',
						'value'     => 'Product'
					)
				)
			);
			$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewSection;//Push our new field object into the form object
			$ewim_fieldCount++;
			$ewim_fieldID++;
			//endregion

			//region Components List; Checkboxes
			$ewim_oNewField= clone $ewim_oCheckboxFieldTemplate;//Clone the Field Object
			$ewim_oNewField->label= '';//Label that displays
			$ewim_oNewField->allowsPrepopulate= 1;//Makes it usable by pre populate functions
			$ewim_oNewField->inputName= 'design_details';//Label used for pre populate
			$ewim_oNewField->adminLabel= 'design_details';//Backend label to access field
			$ewim_oNewField->visibility= 'visible';//Make it Visible
			$ewim_oNewField->cssClass= 'gf_list_3col design_details';//Add in the alternating CSS
			$ewim_oNewField->id= $ewim_fieldID;//Give it an id
			//$ewim_oNewField->isRequired= 1;

			$ewim_aComponents= $wpdb->get_results("SELECT * FROM $ewim_tables->ewim_items WHERE game_id = $ewim_activeGameID AND category = 'Component' ORDER BY item_name",ARRAY_A);

			$ewim_aDesign= (isset($ewim_itemID) ? explode(',',$ewim_aItem['design_details']) : array());

			$ewim_rsC= 0;
			foreach($ewim_aComponents as $ewim_aComponent){
				//skipping index that are multiples of 10 (multiples of 10 create problems as the input IDs)
				if ( $ewim_rsC != 0 && $ewim_rsC % 10 == 0 ) {
					$ewim_rsC++;
				}

				//Check for used minerals since standard populate values is not working for dynamic checkboxes ATM
				$ewim_isSelected= (in_array($ewim_aComponent['item_name'].'_'.$ewim_aComponent['id'],$ewim_aDesign) ? 'Yes' : 0);

				$ewim_oNewField->choices[$ewim_rsC]= array(
					'text'  => $ewim_aComponent['item_name'],
					'value' => $ewim_aComponent['item_name'].'_'.$ewim_aComponent['id'],
					//'value' => $ewim_aComponent['id'],
					'isSelected' => $ewim_isSelected,
					'price' => ''
				);

				$ewim_oNewField->inputs[$ewim_rsC]= array(
					'id'  => $ewim_fieldID.".".$ewim_rsC,
					'label' => $ewim_aComponent['item_name'].'_'.$ewim_aComponent['id'],
					'name' => ''
				);

				$ewim_rsC++;
			}

			$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewField;//Push our new field object into the form object

			//Increase our counters, alternate our strings
			$ewim_fieldCount++;
			$ewim_itemCount++;
			$ewim_fieldID++;
			$ewim_fieldCSS= ($ewim_fieldCount / 2 ? 'item_recipe_ingredients gf_right_half' : 'item_recipe_ingredients gf_left_half');
			//endregion

			//endregion
			break;
		case $ewim_getOptions->ewim_itemTransactionFormID:
			//region Global Variables, Classes, Class Variables, Local Variables
			$ewim_fieldCSS= 'gf_left_half';//Sets the starting CSS Class for new fields
			$ewim_itemCount= 1;
			$ewim_fieldID= 1000;//Sets the Starting Field ID for New fields
			//endregion

			//todo consume action
			//todo percentage for sells
			//todo posting option, fee field, figure way to calculate fee when item sales, new table field?

			//region Item Adjust Form Step 1: Get the game system, set game system variables
			$ewim_aGame= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_games WHERE id = $ewim_activeGameID",ARRAY_A);

			switch ($ewim_aGame['game_system']){
				case 'EVE':
					//$ewim_categoryList= 'default_item_categories';
					//$ewim_aCategories= ewim_get_meta_value($ewim_categoryList);
					//$ewim_itemActionList= 'default_item_actions';
					//$ewim_aCategories= ewim_get_meta_value($ewim_categoryList);
					$ewim_aCoinage= array(
						'ISK'
					);
					break;
				case 'DND':
					//$ewim_categoryList= 'default_item_categories';
					//$ewim_aCategories= ewim_get_meta_value($ewim_categoryList);
					//$ewim_itemActionList= 'default_item_actions';
					//$ewim_aCategories= ewim_get_meta_value($ewim_categoryList);
					$ewim_aCoinage= array(
						'Copper',
						'Silver',
						'Gold'
					);
					break;
				default:
					//$ewim_categoryList= 'default_item_categories';
					//$ewim_aCategories= ewim_get_meta_value($ewim_categoryList);
					//$ewim_itemActionList= 'default_item_actions';
					//$ewim_aCategories= ewim_get_meta_value($ewim_categoryList);
					$ewim_aCoinage= array(
						'Credit'
					);
					break;
			}
			//endregion

			//region Item Adjust Form Step 2: Loop Fields. Get any template fields, and find id fields for conditional logic
			$ewim_fieldCount= 0;//This is the field array index in the field array of the form object
			foreach ($ewim_oForm['fields'] as &$ewim_oField){
				if($ewim_oField->adminLabel == 'number_template'){
					$ewim_oNumberFieldTemplate= clone $ewim_oField;
				}
				if($ewim_oField->adminLabel == 'drop_down_template'){
					$ewim_oDropDownFieldTemplate= clone $ewim_oField;
				}
				/*
				if($ewim_oField->adminLabel == 'action'){
					$ewim_actionFieldID= $ewim_oField->id;
				}
				*/
				if($ewim_oField->type == 'section'){
					$ewim_oSectionFieldTemplate= $ewim_oField;
				}
				$ewim_fieldCount++;
			}
			//endregion

			//region Item Adjust Form Step 3: Get Current Item and its Design, create Appropriate Action Drop Down
			$ewim_itemID= $_REQUEST['item_id'];
			$ewim_aItem= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_items WHERE id = $ewim_itemID",ARRAY_A);

			//Get Item Category, create Action Array
			//todo add more meta values to db, get and add like we do categories
			switch ($ewim_aItem['category']){
				case "Product":
					$ewim_aActions= $ewim_aActions= array(
						array(
							'text'  => 'Buy',
							'value' => 'Buy'
						),
						array(
							'text'  => 'Post',
							'value' => 'Post'
						),
						array(
							'text'  => 'Sell',
							'value' => 'Sell'
						),
						array(
							'text'  => 'Manufacture',
							'value' => 'Manufacture'
						),
						array(
							'text'  => 'Copy Design',
							'value' => 'Copy'
						)
					);
					break;
				case "Refined Resource":
					$ewim_aActions= array(
						array(
							'text'  => 'Buy',
							'value' => 'Buy'
						),
						array(
							'text'  => 'Post',
							'value' => 'Post'
						),
						array(
							'text'  => 'Sell',
							'value' => 'Sell'
						)
					);
					break;
				case "Raw Resource":
					$ewim_aActions= array(
						array(
							'text'  => 'Harvest',
							'value' => 'Harvest'
						),
						array(
							'text'  => 'Process',
							'value' => 'Process'
						),
						array(
							'text'  => 'Buy',
							'value' => 'Buy'
						),
						array(
							'text'  => 'Post',
							'value' => 'Post'
						),
						array(
							'text'  => 'Sell',
							'value' => 'Sell'
						)
					);
					break;
				case "Design Copy":
					$ewim_aActions= $ewim_aActions= array(
						array(
							'text'  => 'Manufacture',
							'value' => 'Manufacture'
						),
						array(
							'text'  => 'Buy',
							'value' => 'Buy'
						),
						array(
							'text'  => 'Post',
							'value' => 'Post'
						),
						array(
							'text'  => 'Sell',
							'value' => 'Sell'
						)
					);
					break;
				case "Component":
					$ewim_aActions= $ewim_aActions= array(
						array(
							'text'  => 'Buy',
							'value' => 'Buy'
						),
						array(
							'text'  => 'Post',
							'value' => 'Post'
						),
						array(
							'text'  => 'Sell',
							'value' => 'Sell'
						),
						array(
							'text'  => 'Manufacture',
							'value' => 'Manufacture'
						),
						array(
							'text'  => 'Copy Design',
							'value' => 'Copy'
						)
					);
					break;
				default:
					$ewim_aActionsRecord= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_meta_data WHERE meta_key = 'eve_item_actions'",ARRAY_A);
					$ewim_aActions= explode(",", $ewim_aActionsRecord['meta_value']);
					break;
			}

			$ewim_oNewField= clone $ewim_oDropDownFieldTemplate;
			$ewim_oNewField->label= 'Action';
			$ewim_oNewField->adminLabel= 'action';
			$ewim_oNewField->visibility= 'visible';
			$ewim_oNewField->id= $ewim_actionFieldID= $ewim_fieldID;
			$ewim_oNewField->choices= $ewim_aActions;
			$ewim_oNewField->isRequired= 0;
			$ewim_oNewField->defaultValue= 0;
			//Place New Field into Form
			$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewField;
			//Increase Counters
			$ewim_fieldCount++;
			$ewim_itemCount++;
			$ewim_fieldID++;
			//endregion

			//region Item Adjust Form Step 4: Create Item Amount Fields Associated with Action
			//Create Amount Field for Each possible Action
			foreach ($ewim_aActions as $ewim_action){
				$ewim_oNewField= clone $ewim_oNumberFieldTemplate;

				//region Based on the Action, different display text may be needed.
				switch ($ewim_action['text']){
					case "Copy Design":
						$ewim_oNewField->label= 'Total Products that can be produced from all copies being made';
						break;
					default:
						$ewim_oNewField->label= 'Total Amount of Product to '.$ewim_action['text'];
						break;
				}
				//endregion

				//region Set min max for amount based on some params
				if($ewim_action['text'] == 'Sell' || $ewim_action['text'] == 'Post' || $ewim_action['text'] == 'Manufacture' && $ewim_aItem['category'] == 'Blueprint Copy' ){
					$ewim_oNewField->rangeMin= 0;
					$ewim_oNewField->rangeMax= $ewim_aItem['item_inventory_quantity'];
				}
				//endregion

				//Rest of new field
				$ewim_oNewField->adminLabel= 'amount_'.$ewim_action['value'];
				$ewim_oNewField->visibility= 'visible';
				$ewim_oNewField->cssClass= $ewim_fieldCSS;
				$ewim_oNewField->id= $ewim_fieldID;
				$ewim_oNewField->conditionalLogic= array(
					'actionType'    => 'show',
					'logicType'     => 'any',
					'rules'         => array(
						array(
							'fieldId'   => $ewim_actionFieldID,
							'operator'  => 'is',
							'value'     => $ewim_action['value']
						)
					)
				);
				$ewim_oNewField->isRequired= 0;
				$ewim_oNewField->defaultValue= 0;
				switch ($ewim_action['text']){
					case "Sell":
						$ewim_oNewField->rangeMin= 0;
						$ewim_oNewField->rangeMax= $ewim_aItem['item_inventory_quantity'];
						break;
					case "Craft":
						$ewim_craftAmountFieldCount= $ewim_fieldCount;
						break;
					case "Process":
						$ewim_oNewField->rangeMin= 0;
						$ewim_oNewField->rangeMax= $ewim_aItem['item_inventory_quantity'];
						break;
					case "Post":
						$ewim_oNewField->rangeMin= 0;
						$ewim_oNewField->rangeMax= $ewim_aItem['item_inventory_quantity'];
						break;
				}

				//Place New Field into Form
				$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewField;

				//Increase Counters
				$ewim_fieldCount++;
				$ewim_itemCount++;
				$ewim_fieldID++;

				//Alternate to next CSS Class
				$ewim_fieldCSS= ($ewim_fieldCount / 2 ? 'gf_right_half' : 'gf_left_half');
			}
			//endregion

			//region Item Adjust Form Step 5: Create Coin Fields
			foreach($ewim_aCoinage as $ewim_coin){
				$ewim_oNewField= clone $ewim_oNumberFieldTemplate;
				$ewim_oNewField->label= "Total ".$ewim_coin;
				$ewim_oNewField->adminLabel= $ewim_coin;
				$ewim_oNewField->visibility= 'visible';
				$ewim_oNewField->cssClass= $ewim_fieldCSS;
				$ewim_oNewField->id= $ewim_fieldID;
				$ewim_oNewField->conditionalLogic= array(
					'actionType'    => 'show',
					'logicType'     => 'any',
					'rules'         => array(
						array(
							'fieldId'   => $ewim_actionFieldID,
							'operator'  => 'is',
							'value'     => 'Buy'
						),
						array(
							'fieldId'   => $ewim_actionFieldID,
							'operator'  => 'is',
							'value'     => 'Sell'
						)
					)
				);
				$ewim_oNewField->isRequired= 0;
				$ewim_oNewField->defaultValue= 0;

				//Place New Field into Form
				$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewField;

				//Increase Counters
				$ewim_fieldCount++;
				$ewim_itemCount++;
				$ewim_fieldID++;

				//Alternate to next CSS Class
				$ewim_fieldCSS= ($ewim_fieldCount / 2 ? 'gf_right_half' : 'gf_left_half');
			}
			//endregion

			//region Item Adjust Form Step 6: Add Fields need for the Game System
			switch ($ewim_aGame['game_system']){
				case "DnD":
					//region DnD Step 2: Modify the craft amount to limit range
					if($ewim_aItem['item_recipe_ingredients'] != NULL){
						$ewim_aItemRecipe= json_decode($ewim_aItem['item_recipe_ingredients'],true);
						$ewim_c= 0;
						foreach($ewim_aItemRecipe as $ewim_ingredientNameID => $ewim_ingredientAmount){
							$ewim_aIngredientItemNameID= explode("_",$ewim_ingredientNameID);
							$ewim_ingredientID= $ewim_aIngredientItemNameID[1];
							$ewim_aIngredientItem= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_items WHERE id = $ewim_ingredientID",ARRAY_A);

							$ewim_aMaxCraft[$ewim_c]= $ewim_aIngredientItem['item_inventory_quantity'] / $ewim_ingredientAmount;
						}
					}

					$ewim_maxCraft= max($ewim_aMaxCraft);

					$ewim_oForm['fields'][$ewim_craftAmountFieldCount]->rangeMin= 0;
					$ewim_oForm['fields'][$ewim_craftAmountFieldCount]->rangeMax= $ewim_maxCraft;
					//endregion

					break;
				case "EVE":
					//region Eve Step 2: Create and Label Sales Tax Field
					$ewim_oNewField= clone $ewim_oNumberFieldTemplate;
					$ewim_oNewField->label= 'Sales Tax';
					$ewim_oNewField->adminLabel= "sales_tax";
					$ewim_oNewField->visibility= 'visible';
					$ewim_oNewField->cssClass= 'gf_left_half';
					$ewim_oNewField->id= $ewim_fieldID;
					$ewim_oNewField->defaultValue= 0;
					$ewim_oNewField->conditionalLogic= array(
						'actionType'    => 'show',
						'logicType'     => 'any',
						'rules'         => array(
							array(
								'fieldId'   => $ewim_actionFieldID,
								'operator'  => 'is',
								'value'     => 'Sell'
							)
						)
					);
					$ewim_oNewField->isRequired= 0;
					$ewim_oNewField->defaultValue= 0;

					//Place New Field into Form
					$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewField;

					//Increase Counters
					$ewim_fieldCount++;
					$ewim_itemCount++;
					$ewim_fieldID++;
					//endregion

					//region Eve Step 3: Broker Fee Field
					$ewim_oNewField= clone $ewim_oNumberFieldTemplate;
					$ewim_oNewField->label= 'Broker Fee';
					$ewim_oNewField->adminLabel= "broker_fee";
					$ewim_oNewField->visibility= 'visible';
					$ewim_oNewField->cssClass= 'gf_right_half';
					$ewim_oNewField->id= $ewim_fieldID;
					$ewim_oNewField->defaultValue= 0;
					$ewim_oNewField->conditionalLogic= array(
						'actionType'    => 'show',
						'logicType'     => 'any',
						'rules'         => array(
							array(
								'fieldId'   => $ewim_actionFieldID,
								'operator'  => 'is',
								'value'     => 'Post'
							),
							array(
								'fieldId'   => $ewim_actionFieldID,
								'operator'  => 'is',
								'value'     => 'Sell'
							)
						)
					);
					$ewim_oNewField->isRequired= 0;
					$ewim_oNewField->defaultValue= 0;

					//Place New Field into Form
					$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewField;

					//Increase Counters
					$ewim_fieldCount++;
					$ewim_itemCount++;
					$ewim_fieldID++;
					//endregion

					//region Eve Step 4: Posted Price field
					$ewim_oNewField= clone $ewim_oNumberFieldTemplate;
					$ewim_oNewField->label= 'Post Price';
					$ewim_oNewField->adminLabel= "posted_price";
					$ewim_oNewField->visibility= 'visible';
					$ewim_oNewField->cssClass= 'gf_left_half';
					$ewim_oNewField->id= $ewim_fieldID;
					$ewim_oNewField->defaultValue= 0;
					$ewim_oNewField->conditionalLogic= array(
						'actionType'    => 'show',
						'logicType'     => 'any',
						'rules'         => array(
							array(
								'fieldId'   => $ewim_actionFieldID,
								'operator'  => 'is',
								'value'     => 'Post'
							)
						)
					);
					$ewim_oNewField->isRequired= 0;
					$ewim_oNewField->defaultValue= 0;

					//Place New Field into Form
					$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewField;

					//Increase Counters
					$ewim_fieldCount++;
					$ewim_itemCount++;
					$ewim_fieldID++;
					//endregion

					//region Eve Step 5: Manufacture Cost
					$ewim_oNewField= clone $ewim_oNumberFieldTemplate;
					$ewim_oNewField->label= "Manufacturing Cost";
					$ewim_oNewField->adminLabel= "manufacturing_cost";
					$ewim_oNewField->visibility= 'visible';
					$ewim_oNewField->cssClass= 'gf_right_half';
					$ewim_oNewField->id= $ewim_fieldID;
					$ewim_oNewField->conditionalLogic= array(
						'actionType'    => 'show',
						'logicType'     => 'any',
						'rules'         => array(
							array(
								'fieldId'   => $ewim_actionFieldID,
								'operator'  => 'is',
								'value'     => 'Manufacture'
							)
						)
					);
					$ewim_oNewField->isRequired= 0;
					$ewim_oNewField->defaultValue= 0;

					//Place New Field into Form
					$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewField;

					//Increase Counters
					$ewim_fieldCount++;
					$ewim_itemCount++;
					$ewim_fieldID++;
					//endregion

					//region Eve Step 6: Copy Cost
					$ewim_oNewField= clone $ewim_oNumberFieldTemplate;
					$ewim_oNewField->label= "Copy Cost";
					$ewim_oNewField->adminLabel= "copy_cost";
					$ewim_oNewField->visibility= 'visible';
					$ewim_oNewField->cssClass= 'gf_right_half';
					$ewim_oNewField->id= $ewim_fieldID;
					$ewim_oNewField->conditionalLogic= array(
						'actionType'    => 'show',
						'logicType'     => 'any',
						'rules'         => array(
							array(
								'fieldId'   => $ewim_actionFieldID,
								'operator'  => 'is',
								'value'     => 'Copy'
							)
						)
					);
					$ewim_oNewField->isRequired= 0;
					$ewim_oNewField->defaultValue= 0;

					//Place New Field into Form
					$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewField;

					//Increase Counters
					$ewim_fieldCount++;
					$ewim_itemCount++;
					$ewim_fieldID++;
					//endregion

					//region Eve Step 7: Processing Section

					//region Processing Section Label
					$ewim_oNewSection= clone $ewim_oSectionFieldTemplate;
					$ewim_oNewSection->label= 'Minerals Received from Processing';
					$ewim_oNewSection->visibility= 'visible';
					$ewim_oNewSection->id= $ewim_fieldID;
					$ewim_oNewSection->conditionalLogic= array(
						'actionType'    => 'show',
						'logicType'     => 'any',
						'rules'         => array(
							array(
								'fieldId'   => $ewim_actionFieldID,
								'operator'  => 'is',
								'value'     => 'Process'
							)
						)
					);
					$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewSection;//Push our new field object into the form object
					$ewim_fieldCount++;
					$ewim_fieldID++;
					//endregion

					if($ewim_aItem['design_details'] != NULL){
						$ewim_aDesignDetails= explode(',',$ewim_aItem['design_details']);
						$ewim_fieldCSS= 'gf_left_half';

						foreach($ewim_aDesignDetails as $ewim_aDesignDetail){
							$ewim_aDesignDetailItem= explode('_', $ewim_aDesignDetail);

							$ewim_ingredientID= $ewim_aDesignDetailItem[1];

							$ewim_aIngredientItem= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_items WHERE id = $ewim_ingredientID",ARRAY_A);

							//region Create Mineral Fields
							$ewim_oNewField= clone $ewim_oNumberFieldTemplate;
							$ewim_oNewField->label= $ewim_aIngredientItem['item_name'].' Gained';
							$ewim_oNewField->adminLabel= "process_".$ewim_aIngredientItem['item_name'].'_'.$ewim_aIngredientItem['id'];
							$ewim_oNewField->visibility= 'visible';
							$ewim_oNewField->cssClass= $ewim_fieldCSS;
							$ewim_oNewField->id= $ewim_fieldID;
							$ewim_oNewField->defaultValue= 0;
							$ewim_oNewField->conditionalLogic= array(
								'actionType'    => 'show',
								'logicType'     => 'any',
								'rules'         => array(
									array(
										'fieldId'   => $ewim_actionFieldID,
										'operator'  => 'is',
										'value'     => 'Process'
									)
								)
							);
							$ewim_oNewField->isRequired= 0;
							$ewim_oNewField->defaultValue= 0;

							//Place New Field into Form
							$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewField;

							//Increase Counters
							$ewim_fieldCount++;
							$ewim_itemCount++;
							$ewim_fieldID++;

							//Alternate to next CSS Class
							$ewim_fieldCSS= ($ewim_fieldCount / 2 ? 'gf_right_half' : 'gf_left_half');
							//endregion

							//region Create Price Field
							$ewim_oNewField= clone $ewim_oNumberFieldTemplate;
							$ewim_oNewField->label= $ewim_aIngredientItem['item_name'].' ISK Cost';
							$ewim_oNewField->adminLabel= "process_cost_".$ewim_aIngredientItem['item_name'].'_'.$ewim_aIngredientItem['id'];
							$ewim_oNewField->visibility= 'visible';
							$ewim_oNewField->cssClass= $ewim_fieldCSS;
							$ewim_oNewField->id= $ewim_fieldID;
							$ewim_oNewField->defaultValue= 0;
							$ewim_oNewField->conditionalLogic= array(
								'actionType'    => 'show',
								'logicType'     => 'any',
								'rules'         => array(
									array(
										'fieldId'   => $ewim_actionFieldID,
										'operator'  => 'is',
										'value'     => 'Process'
									)
								)
							);
							$ewim_oNewField->isRequired= 0;
							$ewim_oNewField->defaultValue= 0;

							//Place New Field into Form
							$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewField;

							//Increase Counters
							$ewim_fieldCount++;
							$ewim_itemCount++;
							$ewim_fieldID++;

							//Alternate to next CSS Class
							$ewim_fieldCSS= ($ewim_fieldCount / 2 ? 'gf_right_half' : 'gf_left_half');
							//endregion
						}
					}
					//endregion

					//region Eve Step 8: Manufacturing Section

					//region Manufacturing Mineral Section Label
					$ewim_oNewSection= clone $ewim_oSectionFieldTemplate;
					$ewim_oNewSection->label= 'Total Refined Resources Used during Manufacturing';
					$ewim_oNewSection->visibility= 'visible';
					$ewim_oNewSection->id= $ewim_fieldID;
					$ewim_oNewSection->conditionalLogic= array(
						'actionType'    => 'show',
						'logicType'     => 'any',
						'rules'         => array(
							array(
								'fieldId'   => $ewim_actionFieldID,
								'operator'  => 'is',
								'value'     => 'Manufacture'
							)
						)
					);
					$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewSection;//Push our new field object into the form object
					$ewim_fieldCount++;
					$ewim_fieldID++;
					//endregion

					if($ewim_aItem['design_details'] != NULL){
						$ewim_aDesignDetails= explode(',',$ewim_aItem['design_details']);
						$ewim_fieldCSS= 'gf_left_half';

						foreach($ewim_aDesignDetails as $ewim_aDesignDetail){
							//Set up Design Item Details for DB Query for Full details
							$ewim_aDesignDetailItem= explode('_', $ewim_aDesignDetail);
							$ewim_ingredientID= $ewim_aDesignDetailItem[1];

							//Get Design Item full details
							$ewim_aIngredientItem= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_items WHERE id = $ewim_ingredientID",ARRAY_A);

							//Create New field
							$ewim_oNewField= clone $ewim_oNumberFieldTemplate;
							$ewim_oNewField->label= $ewim_aIngredientItem['item_name'];
							$ewim_oNewField->adminLabel= "manufacture_".$ewim_aIngredientItem['item_name'].'_'.$ewim_aIngredientItem['id'];
							$ewim_oNewField->visibility= 'visible';
							$ewim_oNewField->cssClass= $ewim_fieldCSS;
							$ewim_oNewField->id= $ewim_fieldID;
							$ewim_oNewField->defaultValue= 0;
							$ewim_oNewField->conditionalLogic= array(
								'actionType'    => 'show',
								'logicType'     => 'any',
								'rules'         => array(
									array(
										'fieldId'   => $ewim_actionFieldID,
										'operator'  => 'is',
										'value'     => 'Manufacture'
									)
								)
							);
							$ewim_oNewField->isRequired= 0;
							$ewim_oNewField->defaultValue= 0;
							$ewim_oNewField->rangeMin= 0;
							$ewim_oNewField->rangeMax= $ewim_aIngredientItem['item_inventory_quantity'];

							//Place New Field into Form
							$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewField;

							//Increase Counters
							$ewim_fieldCount++;
							$ewim_itemCount++;
							$ewim_fieldID++;

							//Alternate to next CSS Class
							$ewim_fieldCSS= ($ewim_fieldCount / 2 ? 'gf_right_half' : 'gf_left_half');
						}
					}
					//endregion

					break;
				default:
					break;
			}
			//endregion

			break;
		case $ewim_getOptions->ewim_postedTransactionFormID:
			//region Global Variables, Classes, Class Variables, Local Variables
			$ewim_fieldCSS= 'gf_left_half';//Sets the starting CSS Class for new fields
			$ewim_itemCount= 1;
			$ewim_fieldID= 1000;//Sets the Starting Field ID for New fields
			//endregion

			//region Posted Adjust Form Step 1: Get the game system
			$ewim_aGame= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_games WHERE id = $ewim_activeGameID",ARRAY_A);
			//endregion

			//region Posted Adjust Form Step 2: Loop Fields. Get any template fields, and find id fields for conditional logic
			$ewim_fieldCount= 0;//This is the field array index in the field array of the form object
			foreach ($ewim_oForm['fields'] as &$ewim_oField){
				if($ewim_oField->adminLabel == 'number_template'){
					$ewim_oNumberFieldTemplate= clone $ewim_oField;
				}
				if($ewim_oField->adminLabel == 'drop_down_template'){
					$ewim_oDropDownFieldTemplate= clone $ewim_oField;
				}
				/*
				if($ewim_oField->adminLabel == 'action'){
					$ewim_actionFieldID= $ewim_oField->id;
				}
				if($ewim_oField->type == 'section'){
					$ewim_oSectionFieldTemplate= $ewim_oField;
				}
				*/
				$ewim_fieldCount++;
			}
			//endregion

			//region Posted Adjust Form Step 3: Create Action Drop down
			$ewim_recordID= $_REQUEST['record_id'];
			$ewim_aPost= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_posted WHERE id = $ewim_recordID",ARRAY_A);

			//Get Item Category, create Action Array
			switch ($ewim_aGame['game_system']){
				case "Eve":
					$ewim_aActions= array(
						array(
							'text'  => 'Sell',
							'value' => 'Sell'
						),
						array(
							'text'  => 'Remove',
							'value' => 'Remove'
						)
					);
					break;
			}

			$ewim_oNewField= clone $ewim_oDropDownFieldTemplate;
			$ewim_oNewField->label= 'Action';
			$ewim_oNewField->adminLabel= 'action';
			$ewim_oNewField->visibility= 'visible';
			$ewim_oNewField->id= $ewim_actionFieldID= $ewim_fieldID;
			$ewim_oNewField->choices= $ewim_aActions;
			$ewim_oNewField->isRequired= 0;
			$ewim_oNewField->placeholder= 'Select One';
			//Place New Field into Form
			$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewField;
			//Increase Counters
			$ewim_fieldCount++;
			$ewim_itemCount++;
			$ewim_fieldID++;
			//endregion

			//region Item Adjust Form Step 4: Create Amount Fields Associated with Action
			//Create Amount Field for Each possible Action
			foreach ($ewim_aActions as $ewim_action){
				$ewim_oNewField= clone $ewim_oNumberFieldTemplate;
				$ewim_oNewField->label= 'Amount to '.$ewim_action['text'];
				$ewim_oNewField->adminLabel= 'amount_'.$ewim_action['text'];
				$ewim_oNewField->visibility= 'visible';
				$ewim_oNewField->cssClass= $ewim_fieldCSS;
				$ewim_oNewField->id= $ewim_fieldID;
				$ewim_oNewField->conditionalLogic= array(
					'actionType'    => 'show',
					'logicType'     => 'any',
					'rules'         => array(
						array(
							'fieldId'   => $ewim_actionFieldID,
							'operator'  => 'is',
							'value'     => $ewim_action['text']
						)
					)
				);
				$ewim_oNewField->isRequired= 0;
				$ewim_oNewField->defaultValue= 0;
				switch ($ewim_action['text']){
					case "Sell":
						$ewim_oNewField->rangeMin= 0;
						$ewim_oNewField->rangeMax= $ewim_aPost['amount'];
						break;
				}

				//Place New Field into Form
				$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewField;

				//Increase Counters
				$ewim_fieldCount++;
				$ewim_itemCount++;
				$ewim_fieldID++;

				//Alternate to next CSS Class
				$ewim_fieldCSS= ($ewim_fieldCount / 2 ? 'gf_right_half' : 'gf_left_half');
			}
			//endregion

			//region Item Adjust Form Step 3: Add Fields need for the Game System
			switch ($ewim_aGame['game_system']){
				case "Eve":

					//region Eve Step 1: Create and Label Tax Field
					$ewim_aCoinage= array(
						'ISK'
					);
					/** @noinspection PhpUnusedLocalVariableInspection */
					foreach($ewim_aCoinage as $ewim_coin){
						$ewim_oNewField= clone $ewim_oNumberFieldTemplate;
						$ewim_oNewField->label= "Taxes Paid";
						$ewim_oNewField->adminLabel= 'taxes_paid';
						$ewim_oNewField->visibility= 'visible';
						$ewim_oNewField->cssClass= $ewim_fieldCSS;
						$ewim_oNewField->id= $ewim_fieldID;
						$ewim_oNewField->conditionalLogic= array(
							'actionType'    => 'show',
							'logicType'     => 'any',
							'rules'         => array(
								array(
									'fieldId'   => $ewim_actionFieldID,
									'operator'  => 'is',
									'value'     => 'Sell'
								)
							)
						);
						$ewim_oNewField->isRequired= 0;
						$ewim_oNewField->defaultValue= 0;

						//Place New Field into Form
						$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewField;

						//Increase Counters
						$ewim_fieldCount++;
						$ewim_itemCount++;
						$ewim_fieldID++;

						//Alternate to next CSS Class
						$ewim_fieldCSS= ($ewim_fieldCount / 2 ? 'gf_right_half' : 'gf_left_half');
					}
					//endregion

					break;
			}
			//endregion

			break;
	}
	//endregion

	//region Debug: Form End
	if($ewim_debug_settings->ewim_CreateFieldsFormEnd == 1){
		echo "<pre style='color:white;'>";
		print_r($ewim_oForm);
		echo "</pre>";
		exit;
	}
	//endregion

	return $ewim_oForm;
}

/**
 * List Population Hooks
 */

/**
 * Name: Populate Lists
 * Desc: Populates the list of games belonging to the logged in user into a drop down list
 * Reqs: Field must be a drop down select, and admin label must be game_id
 */
//region Filters
add_filter( 'gform_pre_render', 'ewim_gf_populate_lists' );
add_filter( 'gform_pre_validation', 'ewim_gf_populate_lists' );
add_filter( 'gform_pre_submission_filter', 'ewim_gf_populate_lists' );
//endregion
function ewim_gf_populate_lists($ewim_oForm){
	///region Global Variables, Local Variables, Classes
	global $wpdb;
	$ewim_tables= new ewim_tables();
	$ewim_current_user= wp_get_current_user();
	$ewim_userID= $ewim_current_user->ID;
	$ewim_debug_settings= new ewim_debug_settings();
	//$ewim_activeGameID= get_user_meta($ewim_userID, 'active_game', true);
	$ewim_activeGameSystem= get_user_meta($ewim_userID, 'active_game_system', true);
	//endregion

	//todo Create a new debug setting for this section

	foreach ($ewim_oForm['fields'] as &$ewim_aField){
		$ewim_cssClass= explode(" ", $ewim_aField['cssClass']);
		if(in_array('game_list', $ewim_cssClass)){
			$ewim_aGames= $wpdb->get_results("SELECT * FROM $ewim_tables->ewim_games WHERE user_id = $ewim_userID",ARRAY_A);
			if($ewim_aGames != ''){
				foreach($ewim_aGames as $ewim_aGame){
					$choices[]= array(
						'text'  => $ewim_aGame['game_name'],
						'value' => $ewim_aGame['id']
					);
				}
				//$ewim_aField->placeholder = 'Select the Weather';
				$ewim_aField->choices = $choices;
			}
		}
		//region Item Category Lists
		if(in_array('item_category_list', $ewim_cssClass)){
			switch ($ewim_activeGameSystem){
				case "Eve":
					$ewim_aCategoryMeta= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_meta_data WHERE meta_key = 'default_item_categories'",ARRAY_A);
					$ewim_aCategories= explode(",",$ewim_aCategoryMeta['meta_value']);
					foreach($ewim_aCategories as $ewim_category){
						$choices[]= array(
							'text'  => $ewim_category,
							'value' => $ewim_category
						);
					}
					$ewim_aField->placeholder= 'Select One';
					$ewim_aField->choices= $choices;
					break;
				case "DnD":
					break;
			}
		}
		//endregion

		//region Item Actions Lists
		if(in_array('item_action_list', $ewim_cssClass)){
			switch ($ewim_activeGameSystem){
				case "Eve":
					$ewim_aMethodsRecord= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_meta_data WHERE meta_key = 'default_item_actions'",ARRAY_A);
					if($ewim_aMethodsRecord != ''){
						$ewim_aMethods= explode(",", $ewim_aMethodsRecord['meta_value']);
						foreach($ewim_aMethods as $ewim_metaValue){
							$ewim_aChoices[]= array(
								'text'  => $ewim_metaValue,
								'value' => $ewim_metaValue
							);
						}
						$ewim_aField->placeholder = 'Select One';
						$ewim_aField->choices = $ewim_aChoices;
					}
					break;
				case "DnD":
					$ewim_aMethodsRecord= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_meta_data WHERE meta_key = 'item_actions'",ARRAY_A);
					if($ewim_aMethodsRecord != ''){
						$ewim_aMethods= explode(",", $ewim_aMethodsRecord['meta_value']);
						foreach($ewim_aMethods as $ewim_metaValue){
							$ewim_aChoices[]= array(
								'text'  => $ewim_metaValue,
								'value' => $ewim_metaValue
							);
						}
						$ewim_aField->placeholder = 'Select One';
						$ewim_aField->choices = $ewim_aChoices;
					}
					break;
			}
		}
		//endregion
	}

	return $ewim_oForm;
}












/**
 * Name: Item Related Form Pre Render
 * Desc: Can be called by any form that pulls data from the item table, as long as item_id has been passed
 */
/*
//region Filters
add_filter( "gform_pre_render_$ewim_acquireFormID", 'item_related_form_pre_render' );
add_filter( "gform_pre_render_$ewim_removeFormID", 'item_related_form_pre_render' );
//endregion
function item_related_form_pre_render($ewim_oForm){
	add_filter( 'gform_field_value', 'item_related_form_populate_field_values', 10, 3 );
	return $ewim_oForm;
}
*/




/**
 * Name: Item Related Form Populate Field Values
 * Desc: Called by the Item Related Form Pre Render. Will populate the fields that have dynamic enabled with the corresponding field from the database. Must have item id in post or request
 *
 * @param $value
 * @param $field
 * @param $name
 *
 * @return mixed
 */
function item_related_form_populate_field_values( $value, /** @noinspection PhpUnusedParameterInspection */$field, $name ) {
	//region Global Variables, Classes, Class Variables, Local Variables
	global $wpdb;

	$ewim_debug_settings= new ewim_debug_settings();
	$ewim_tables= new ewim_tables();
	$ewim_current_user= wp_get_current_user();
	$ewim_aValues= array();
	$ewim_itemID= $_REQUEST['record_id'];

	$ewim_userID= $ewim_current_user->ID;
	//$ewim_debug_settings= new ewim_debug_settings();
	$ewim_activeGameID= get_user_meta($ewim_userID, 'active_game', true);
	//endregion

	//region Debug
	if($ewim_debug_settings->ewim_formPopulateRecordStart == 1 && $field['adminLabel'] == 'design'){
		echo "<h1>Populate Record Start</h1>";
		echo "<pre style='color:red;'>";
		print_r($field);
		echo "</pre>";
		exit;
	}
	//endregion

	//region Step 1: Get Item Information and Decode where needed
	$ewim_aItem= $wpdb->get_row( "SELECT * FROM $ewim_tables->ewim_items WHERE id = '$ewim_itemID'", ARRAY_A );//Find customer by location ID
	//$ewim_aItem['item_recipe_ingredients']= json_decode($ewim_aItem['item_recipe_ingredients'], true);
	$ewim_aItem['design']= json_decode($ewim_aItem['item_recipe_ingredients'], true);

	$ewim_aItem['item_meta']= json_decode($ewim_aItem['item_meta'], true);
	$ewim_aGame= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_games WHERE id = $ewim_activeGameID",ARRAY_A);
	//endregion

	//region Step 2: Loop and assign record data to fields
	$ewim_aValues['record_id']= $ewim_aItem['id'];
	foreach($ewim_aItem as $ewim_key => $ewim_value){
		switch ($ewim_aGame['game_system']){
			case "DnD":
				if(is_array($ewim_value)){
					foreach($ewim_value as $ewim_k => $ewim_v){
						$ewim_aValues[$ewim_k]= $ewim_v;
					}
				}
				else{
					$ewim_aValues[$ewim_key]= $ewim_value;
				}
				break;
			case "Eve":
				if(is_array($ewim_value)){
					if($ewim_key == 'item_recipe_ingredients'){
						foreach($ewim_value as $ewim_k => $ewim_v){
							$ewim_aValues[$ewim_k.'_'.$ewim_v]= 'Yes';
						}
					}
					elseif($ewim_key == 'design'){
						foreach($ewim_value as $ewim_k => $ewim_v){
							$ewim_aValues[$ewim_key]= "No Choices";
							//echo "We are running. $name | $value <br />";
						}
					}
					else{
						foreach($ewim_value as $ewim_k => $ewim_v){
							$ewim_aValues[$ewim_k]= $ewim_k;
						}
					}
				}
				else{
					$ewim_aValues[$ewim_key]= $ewim_value;
				}
				break;
		}

	}
	//endregion

	$test= isset( $ewim_aValues[ $name ] ) ? $ewim_aValues[ $name ] : $value;
	//echo "We are running. $name | $test <br />";

	//region Debug
	//if($ewim_debug_settings->ewim_formPopulateRecordEnd == 1 && $field['adminLabel'] == 'item_recipe_ingredients'){
	if($ewim_debug_settings->ewim_formPopulateRecordEnd == 1 && $name == 'design'){
		echo "<h1>Edit Record | Form End | $name | $ewim_key | $value</h1>";
		echo "<pre style='color:red;'>";
		print_r($ewim_aValues[$name]);
		echo "</pre>";
		//exit;
	}
	//endregion

	return isset( $ewim_aValues[ $name ] ) ? $ewim_aValues[ $name ] : $value;

}



function populate_category($ewim_value, $ewim_aField, $ewim_fieldName){
	echo "<h1>Category Called</h1>";
	return "Product";
}
function populate_design( $value, $field, $ewim_fieldName ) {
	echo "<h1>Design called with $ewim_fieldName <br /></h1>";
	// Use the POSTed data if it's there
	//return isset( $_POST[ $name ] ) ? $_POST[ $name ] : $value;
	return "Refined Resource 1_32,Refined Resource 2_33";
}

function populate_name( $value, $field, $ewim_fieldName ) {
	echo "<h1>Name called with $ewim_fieldName <br /></h1>";
	// Use the POSTed data if it's there
	//return isset( $_POST[ $name ] ) ? $_POST[ $name ] : $value;
	return "Product";
}


function item_related_form_populate_field_values_two( $ewim_value, /** @noinspection PhpUnusedParameterInspection */$ewim_aField, $ewim_fieldName ) {
	//region Global Variables, Classes, Class Variables, Local Variables
	global $wpdb;

	$ewim_debug_settings= new ewim_debug_settings();
	$ewim_tables= new ewim_tables();
	$ewim_current_user= wp_get_current_user();
	$ewim_aValues= array();
	$ewim_itemID= $_REQUEST['record_id'];

	$ewim_userID= $ewim_current_user->ID;
	//$ewim_debug_settings= new ewim_debug_settings();
	$ewim_activeGameID= get_user_meta($ewim_userID, 'active_game', true);
	//endregion

	//region Debug
	if($ewim_debug_settings->ewim_formPopulateRecordStart == 1 && $field['adminLabel'] == 'design'){
		echo "<h1>Populate Record Start</h1>";
		echo "<pre style='color:red;'>";
		print_r($field);
		echo "</pre>";
		exit;
	}
	//endregion

	//region Step 1: Get Item Information and Decode where needed
	$ewim_aItem= $wpdb->get_row( "SELECT * FROM $ewim_tables->ewim_items WHERE id = '$ewim_itemID'", ARRAY_A );//Find customer by location ID
	//$ewim_aItem['item_recipe_ingredients']= json_decode($ewim_aItem['item_recipe_ingredients'], true);

	$ewim_aItem['item_meta']= json_decode($ewim_aItem['item_meta'], true);
	$ewim_aGame= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_games WHERE id = $ewim_activeGameID",ARRAY_A);
	//endregion

	//region Step 2: Loop and assign record data to fields
	$ewim_aCssClass= explode(" ",$ewim_aField['cssClass']);

	switch ($ewim_aField['type']){
		case "hidden":
			switch ($ewim_fieldName){
				case "record_id":
					$ewim_recordValue= $ewim_aItem['id'];
					break;
				case "game_id":
					$ewim_recordValue= $ewim_aItem[$ewim_fieldName];
					break;
				default:
					break;
			}
			break;
		default:
			switch ($ewim_fieldName){
				case "design_details":
					//todo: Hope to one day figure out what is going on with checkboxes, spent nearly 8 hours trying to figure out.
					//todo: If I set hidden fields and the dynamic checkbox, works fine.
					//todo: If I set hidden fields, dynamic checkbox and standard checkbox, works fine
					//todo: If I set hidden fields, dynamic checkbox, standard checkbox, and visible fields, dynamic checkboxes do not work.
					//add_filter('gform_field_value_design', 'populate_design', 10, 3);
					$ewim_recordValue= $ewim_aItem['design_details'];
					//return "Refined Resource 1_32";
					break;
				default:
					//todo: Keep an eye on, may need array handler
					if(in_array("template", $ewim_aCssClass)){
						//Do nothing
					}else{
						$ewim_recordValue= $ewim_aItem[$ewim_fieldName];
					}
					break;
			}
			break;
	}
	//endregion



	//region Debug
	if($ewim_debug_settings->ewim_formPopulateRecordEnd == 1 && $ewim_fieldName == 'design_details'){
	//if($ewim_debug_settings->ewim_formPopulateRecordEnd == 1){
		echo "<h1>Populate Record End | $ewim_itemID | $ewim_fieldName</h1>";
		echo "<pre style='color:red;'>";
		print_r($ewim_recordValue);
		echo "</pre>";
		//exit;
	}
	//endregion

	return isset( $ewim_recordValue ) ? $ewim_recordValue : $ewim_value;

}

/*
 * Name: This is a good way to populate fields, I have a more efficient method currently, but this could come in handy
 * todo, move to a scrap project before launching version 2.0
 * https://resoundingechoes.net/development/gravity-forms-how-to-pre-populate-fields/
foreach($ewim_oForm['fields'] as $ewim_aFields){
	$ewim_fieldName= ($ewim_aFields['type'] != 'hidden' ? $ewim_aFields['adminLabel'] : $ewim_aFields['label']);

	//	add_filter("gform_field_value_$ewim_fieldName", "ewim_return_value");
}
function ewim_return_value($ewim_value){
	//region Global Variables, Local Variables, Classes
	global $wpdb;
	$ewim_tables= new ewim_tables();
	$ewim_current_user= wp_get_current_user();
	$ewim_aValues= array();
	$ewim_itemID= $_REQUEST['item_id'];

	$ewim_userID= $ewim_current_user->ID;
	$ewim_debug_settings= new ewim_debug_settings();
	$ewim_activeGameID= get_user_meta($ewim_userID, 'active_game', true);
	//endregion
	$ewim_currentFilter= current_filter();
	$ewim_field= str_replace('gform_field_value_', '', $ewim_currentFilter);
	$ewim_aItem= $wpdb->get_row( "SELECT * FROM $ewim_tables->ewim_items WHERE id = '$ewim_itemID'", ARRAY_A );
	$ewim_aItem['item_recipe_ingredients']= json_decode($ewim_aItem['item_recipe_ingredients'], true);
	foreach($ewim_aItem['item_recipe_ingredients'] as $ewim_key => $ewim_value){
		$ewim_aItem[$ewim_key]= $ewim_value;
	}

	return $ewim_aItem[$ewim_field];
}
*/