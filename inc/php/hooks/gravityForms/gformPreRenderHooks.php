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

	//region Debug
	if($ewim_debug_settings->ewim_formEntry == 1){
		echo "<pre style='color: white;'>";
		print_r($ewim_oForm);
		echo "</pre>";
		exit;
	}
	//endregion

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
					$ewim_aCategoryMeta= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_meta_data WHERE meta_key = 'eve_item_categories'",ARRAY_A);
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
					$ewim_aMethodsRecord= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_meta_data WHERE meta_key = 'eve_item_actions'",ARRAY_A);
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
 * Name: Populate Item Text Inputs
 * Desc: This will create all of the text inputs for the items so that a recipe can be made. Will also Call item_related_form_populate_field_values to populate with record info if an edit action
 */
//region Filters
add_filter( "gform_pre_render", 'create_input_fields' );
add_filter( "gform_pre_validation", 'create_input_fields' );
add_filter( "gform_pre_submission_filter", 'create_input_fields' );
//endregion
function create_input_fields($ewim_oForm){
	//region Global Variables, Local Variables, Classes
	global $wpdb;
	$ewim_tables= new ewim_tables();
	$ewim_current_user= wp_get_current_user();
	$ewim_userID= $ewim_current_user->ID;
	$ewim_debug_settings= new ewim_debug_settings();
	$ewim_activeGameID= get_user_meta($ewim_userID, 'active_game', true);
	//endregion

	//region Debug
	if($ewim_debug_settings->ewim_formEntry == 1){
		echo "<pre style='color:white;'>";
		print_r($ewim_oForm);
		echo "</pre>";
		exit;
	}
	//endregion

	//region Step 1: Get the form name
	$ewim_formName= $ewim_oForm['title'];
	//endregion

	//region Step 2: Switch to processor based on Form Title
	switch ($ewim_formName){
		case "Item Form":
			//region Item Form Step 1: Get the game system
			$ewim_aGame= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_games WHERE id = $ewim_activeGameID",ARRAY_A);
			//endregion

			//region Item form Step 2: Switch to the correct game system processor
			switch ($ewim_aGame['game_system']){
				case "DnD":
					//region DnD Step 1: Loop fields and find a type number template
					$ewim_fieldCount= 0;
					foreach ($ewim_oForm['fields'] as &$ewim_oField){
						if($ewim_oField->adminLabel == 'number_template'){
							$ewim_oNumberFieldTemplate= clone $ewim_oField;
						}
						$ewim_fieldCount++;
					}
					//endregion

					//region DnD Step 2: Create and Label fields for the Items
					$ewim_aItems= $wpdb->get_results("SELECT * FROM $ewim_tables->ewim_items WHERE user_id = $ewim_userID and game_id = $ewim_activeGameID",ARRAY_A);
					$ewim_fieldCSS= 'item_recipe_ingredients gf_left_half';
					
					$ewim_fieldID= 1000;
					foreach($ewim_aItems as $ewim_aItem){
						$ewim_oNewField= clone $ewim_oNumberFieldTemplate;
						//$ewim_oNewFields[$ewim_count]= array( 'label' => $ewim_aItem['name'], 'admin_label' => $ewim_aItem['name'] );
						$ewim_oNewField->label= $ewim_aItem['item_name'];
						$ewim_oNewField->inputName= $ewim_aItem['item_name'].'_'.$ewim_aItem['id'];
						$ewim_oNewField->adminLabel= $ewim_aItem['item_name'].'_'.$ewim_aItem['id'];
						$ewim_oNewField->visibility= 'visible';
						$ewim_oNewField->cssClass= $ewim_fieldCSS;
						$ewim_oNewField->id= $ewim_fieldID;
						$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewField;
						$ewim_fieldCount++;
						
						$ewim_fieldID++;
						$ewim_fieldCSS= ($ewim_fieldCount / 2 ? 'item_recipe_ingredients gf_right_half' : 'item_recipe_ingredients gf_left_half');
					}
					//endregion

					//region DnD Step 3: If the item id is set, populate the fields with the record info
					if(isset($_REQUEST['item_id']) and $_REQUEST['item_id'] != '') {
						add_filter( 'gform_field_value', 'item_related_form_populate_field_values', 10, 3 );//Placed here so it only calls when the edit intercept is called
					}
					//endregion
					break;
				case "Eve":
					//region Eve Step 1: Loop Fields. Get any template fields, and find id fields for conditional logic
					$ewim_fieldCount= 0;
					foreach ($ewim_oForm['fields'] as &$ewim_oField){
						if($ewim_oField->adminLabel == 'radio_template'){
							$ewim_oRadioFieldTemplate= clone $ewim_oField;
						}
						if($ewim_oField->type == 'section'){
							$ewim_oSectionFieldTemplate= $ewim_oField;
						}
						if($ewim_oField->adminLabel == 'drop_down_template'){
							$ewim_oDropDownFieldTemplate= clone $ewim_oField;
						}

						if($ewim_oField->adminLabel == 'category'){
							$ewim_categoryFieldID= $ewim_oField->id;
						}

						$ewim_fieldCount++;
					}
					//endregion

					//region Eve Ste 2: Set starting values for variables changed/increased by loop
					$ewim_fieldCSS= 'item_recipe_ingredients gf_left_half';
					$ewim_itemCount= 1;
					$ewim_fieldID= 1000;
					//endregion

					//region Eve Step 3: Create and Label Fields that Fall into the Categories for the item being created

					//region Mineral Category
					//Nothing to do for minerals, yet.
					//endregion

					//region Ore Category

					//region Section Label for Ore
					$ewim_oNewSection= clone $ewim_oSectionFieldTemplate;
					$ewim_oNewSection->label= 'Choose what Minerals are contained in Ore';
					$ewim_oNewSection->visibility= 'visible';
					$ewim_oNewSection->id= $ewim_fieldID;
					$ewim_oNewSection->conditionalLogic= array(
						'actionType'    => 'show',
						'logicType'     => 'any',
						'rules'         => array(
							array(
								'fieldId'   => $ewim_categoryFieldID,
								'operator'  => 'is',
								'value'     => 'Ore'
							)
						)
					);
					$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewSection;//Push our new field object into the form object
					$ewim_fieldCount++;
					$ewim_fieldID++;
					//endregion

					//region Mineral Radios
					$ewim_aMinerals= $wpdb->get_results("SELECT * FROM $ewim_tables->ewim_items WHERE game_id = $ewim_activeGameID AND category = 'Mineral' ORDER BY item_name",ARRAY_A);
					foreach($ewim_aMinerals as $ewim_aMineral){
						$ewim_oNewField= clone $ewim_oRadioFieldTemplate;//Clone the Field Object
						$ewim_oNewField->label= $ewim_aMineral['item_name'];//Label that displays
						$ewim_oNewField->allowsPrepopulate= 1;//Makes it usable by pre populate functions
						$ewim_oNewField->inputName= $ewim_aMineral['item_name'].'_'.$ewim_aMineral['id'];//Label used for pre populate
						$ewim_oNewField->adminLabel= $ewim_aMineral['item_name'].'_'.$ewim_aMineral['id'];//Backend label to access field
						$ewim_oNewField->visibility= 'visible';//Make it Visible
						$ewim_oNewField->cssClass= $ewim_fieldCSS;//Add in the alternating CSS
						$ewim_oNewField->id= $ewim_fieldID;//Give it an id
						$ewim_oNewField->choices= array(
							array(
								'text'  => 'Yes',
								'value' => 'Yes'
							),
							array(
								'text' =>'No',
								'value' =>'No'
							)
						);
						$ewim_oNewField->isRequired= 1;

						$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewField;//Push our new field object into the form object

						//Increase our counters, alternate our strings
						$ewim_fieldCount++;
						$ewim_itemCount++;
						$ewim_fieldID++;
						$ewim_fieldCSS= ($ewim_fieldCount / 2 ? 'item_recipe_ingredients gf_right_half' : 'item_recipe_ingredients gf_left_half');
					}
					//endregion

					//endregion

					//region Product Category
					//endregion

					//region Blueprint Original Category

					//region Section Label Product
					$ewim_oNewSection= clone $ewim_oSectionFieldTemplate;
					$ewim_oNewSection->label= 'Choose Item this BP creates';
					$ewim_oNewSection->visibility= 'visible';
					$ewim_oNewSection->id= $ewim_fieldID;
					$ewim_oNewSection->conditionalLogic= array(
						'actionType'    => 'show',
						'logicType'     => 'any',
						'rules'         => array(
							array(
								'fieldId'   => $ewim_categoryFieldID,
								'operator'  => 'is',
								'value'     => 'Blueprint'
							)
						)
					);
					$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewSection;//Push our new field object into the form object
					$ewim_fieldCount++;
					$ewim_fieldID++;
					//endregion

					//region List of Items
					$ewim_aProducts= $wpdb->get_results("SELECT * FROM $ewim_tables->ewim_items WHERE game_id = $ewim_activeGameID AND category = 'Product' or category = 'Component' ORDER BY item_name",ARRAY_A);

					$ewim_oNewField= clone $ewim_oDropDownFieldTemplate;//Clone the Field Object
					$ewim_oNewField->label= 'Items';//Label that displays
					$ewim_oNewField->allowsPrepopulate= 1;//Makes it usable by pre populate functions
					//$ewim_oNewField->inputName= $ewim_aBlueprint['item_name'].'_'.$ewim_aBlueprint['id'];//Label used for pre populate
					$ewim_oNewField->adminLabel= 'Product';//Backend label to access field
					$ewim_oNewField->inputName= 'Product';//Label used for pre populate
					$ewim_oNewField->visibility= 'visible';//Make it Visible
					$ewim_oNewField->cssClass= 'gf_left_half product';//Add in the alternating CSS
					$ewim_oNewField->id= $ewim_fieldID;//Give it an id
					$ewim_oNewField->placeholder= 'Choose One';//Give it an id
					$ewim_oNewField->isRequired= 1;

					$ewim_aBPOs= array();

					foreach($ewim_aProducts as $ewim_aProduct){
						array_push(
							$ewim_aBPOs,
							array(
								'text'  => $ewim_aProduct['item_name'],
								'value' => $ewim_aProduct['id']
							)
						);
					}

					$ewim_oNewField->choices= $ewim_aBPOs;

					$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewField;//Push our new field object into the form object

					//Increase our counters, alternate our strings
					$ewim_fieldCount++;
					$ewim_itemCount++;
					$ewim_fieldID++;
					//endregion

					//region Section Label Minerals
					$ewim_oNewSection= clone $ewim_oSectionFieldTemplate;
					$ewim_oNewSection->label= 'Minerals Used';
					$ewim_oNewSection->visibility= 'visible';
					$ewim_oNewSection->id= $ewim_fieldID;
					$ewim_oNewSection->conditionalLogic= array(
						'actionType'    => 'show',
						'logicType'     => 'any',
						'rules'         => array(
							array(
								'fieldId'   => $ewim_categoryFieldID,
								'operator'  => 'is',
								'value'     => 'Blueprint'
							)
						)
					);
					$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewSection;//Push our new field object into the form object
					$ewim_fieldCount++;
					$ewim_fieldID++;
					//endregion

					//region Mineral Radios
					$ewim_fieldCSS= 'item_recipe_ingredients gf_left_half';
					$ewim_aMinerals= $wpdb->get_results("SELECT * FROM $ewim_tables->ewim_items WHERE game_id = $ewim_activeGameID AND category = 'Mineral' ORDER BY item_name",ARRAY_A);
					foreach($ewim_aMinerals as $ewim_aMineral){
						$ewim_oNewField= clone $ewim_oRadioFieldTemplate;//Clone the Field Object
						$ewim_oNewField->label= $ewim_aMineral['item_name'];//Label that displays
						$ewim_oNewField->allowsPrepopulate= 1;//Makes it usable by pre populate functions
						$ewim_oNewField->inputName= $ewim_aMineral['item_name'].'_'.$ewim_aMineral['id'];//Label used for pre populate
						$ewim_oNewField->adminLabel= $ewim_aMineral['item_name'].'_'.$ewim_aMineral['id'];//Backend label to access field
						$ewim_oNewField->visibility= 'visible';//Make it Visible
						$ewim_oNewField->cssClass= $ewim_fieldCSS;//Add in the alternating CSS
						$ewim_oNewField->id= $ewim_fieldID;//Give it an id
						$ewim_oNewField->choices= array(
							array(
								'text'  => 'Yes',
								'value' => 'Yes'
							),
							array(
								'text' =>'No',
								'value' =>'No'
							)
						);
						$ewim_oNewField->isRequired= 1;

						$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewField;//Push our new field object into the form object

						//Increase our counters, alternate our strings
						$ewim_fieldCount++;
						$ewim_itemCount++;
						$ewim_fieldID++;
						$ewim_fieldCSS= ($ewim_fieldCount / 2 ? 'item_recipe_ingredients gf_right_half' : 'item_recipe_ingredients gf_left_half');
					}
					//endregion

					//region Section Label Components
					$ewim_oNewSection= clone $ewim_oSectionFieldTemplate;
					$ewim_oNewSection->label= 'Components Used';
					$ewim_oNewSection->visibility= 'visible';
					$ewim_oNewSection->id= $ewim_fieldID;
					$ewim_oNewSection->conditionalLogic= array(
						'actionType'    => 'show',
						'logicType'     => 'any',
						'rules'         => array(
							array(
								'fieldId'   => $ewim_categoryFieldID,
								'operator'  => 'is',
								'value'     => 'Blueprint'
							)
						)
					);
					$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewSection;//Push our new field object into the form object
					$ewim_fieldCount++;
					$ewim_fieldID++;
					//endregion

					//region Component Radios
					$ewim_fieldCSS= 'item_recipe_ingredients gf_left_half';
					$ewim_aComponents= $wpdb->get_results("SELECT * FROM $ewim_tables->ewim_items WHERE game_id = $ewim_activeGameID AND category = 'Component' ORDER BY item_name",ARRAY_A);
					foreach($ewim_aComponents as $ewim_aComponent){
						$ewim_oNewField= clone $ewim_oRadioFieldTemplate;//Clone the Field Object
						$ewim_oNewField->label= $ewim_aComponent['item_name'];//Label that displays
						$ewim_oNewField->allowsPrepopulate= 1;//Makes it usable by pre populate functions
						$ewim_oNewField->inputName= $ewim_aComponent['item_name'].'_'.$ewim_aComponent['id'];//Label used for pre populate
						$ewim_oNewField->adminLabel= $ewim_aComponent['item_name'].'_'.$ewim_aComponent['id'];//Backend label to access field
						$ewim_oNewField->visibility= 'visible';//Make it Visible
						$ewim_oNewField->cssClass= $ewim_fieldCSS;//Add in the alternating CSS
						$ewim_oNewField->id= $ewim_fieldID;//Give it an id
						$ewim_oNewField->choices= array(
							array(
								'text'  => 'Yes',
								'value' => 'Yes'
							),
							array(
								'text' =>'No',
								'value' =>'No'
							)
						);
						$ewim_oNewField->isRequired= 1;

						$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewField;//Push our new field object into the form object

						//Increase our counters, alternate our strings
						$ewim_fieldCount++;
						$ewim_itemCount++;
						$ewim_fieldID++;
						$ewim_fieldCSS= ($ewim_fieldCount / 2 ? 'item_recipe_ingredients gf_right_half' : 'item_recipe_ingredients gf_left_half');
					}
					//endregion

					//endregion

					//region Blueprint Copy Category

					//region Section Label Blueprint Copy Instructions
					$ewim_oNewSection= clone $ewim_oSectionFieldTemplate;
					$ewim_oNewSection->label= 'Choose BPO';
					$ewim_oNewSection->visibility= 'visible';
					$ewim_oNewSection->id= $ewim_fieldID;
					$ewim_oNewSection->conditionalLogic= array(
						'actionType'    => 'show',
						'logicType'     => 'any',
						'rules'         => array(
							array(
								'fieldId'   => $ewim_categoryFieldID,
								'operator'  => 'is',
								'value'     => 'Blueprint Copy'
							)
						)
					);
					$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewSection;//Push our new field object into the form object
					$ewim_fieldCount++;
					$ewim_fieldID++;
					//endregion

					//region List of BPOs
					$ewim_aBlueprints= $wpdb->get_results("SELECT * FROM $ewim_tables->ewim_items WHERE game_id = $ewim_activeGameID AND category = 'Blueprint' ORDER BY item_name",ARRAY_A);

					$ewim_oNewField= clone $ewim_oDropDownFieldTemplate;//Clone the Field Object
					$ewim_oNewField->label= 'Blueprints';//Label that displays
					$ewim_oNewField->allowsPrepopulate= 1;//Makes it usable by pre populate functions
					$ewim_oNewField->inputName= 'BPO';//Label used for pre populate
					$ewim_oNewField->adminLabel= 'BPO';//Backend label to access field
					$ewim_oNewField->visibility= 'visible';//Make it Visible
					$ewim_oNewField->cssClass= 'gf_left_half bpo';//Add in the alternating CSS
					$ewim_oNewField->id= $ewim_fieldID;//Give it an id
					$ewim_oNewField->placeholder= 'Choose One';//Give it an id
					$ewim_oNewField->isRequired= 1;

					$ewim_aBPOs= array();

					foreach($ewim_aBlueprints as $ewim_aBlueprint){
						array_push(
							$ewim_aBPOs,
							array(
								'text'  => $ewim_aBlueprint['item_name'],
								'value' => $ewim_aBlueprint['id']
							)
						);
					}

					$ewim_oNewField->choices= $ewim_aBPOs;

					$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewField;//Push our new field object into the form object

					//endregion

					//region Component Category
					//endregion

					//endregion

					//region Eve Step 4: If the item id is set, populate the fields with the record info
					if(isset($_REQUEST['record_id']) and $_REQUEST['record_id'] != '') {
						add_filter( 'gform_field_value', 'item_related_form_populate_field_values', 10, 3 );//Placed here so it only calls when the edit intercept is called
					}
					//endregion

					//endregion

					break;
			}
			//endregion
			break;
		case "Item Transaction Form":
			//region Global Variables, Classes, Local Variables
			$ewim_fieldCSS= 'gf_left_half';//Sets the starting CSS Class for new fields
			$ewim_itemCount= 1;
			$ewim_fieldID= 1000;//Sets the Starting Field ID for New fields
			//endregion

			//region Item Adjust Form Step 1: Get the game system
			$ewim_aGame= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_games WHERE id = $ewim_activeGameID",ARRAY_A);
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
				if($ewim_oField->type == 'section'){
					$ewim_oSectionFieldTemplate= $ewim_oField;
				}
				$ewim_fieldCount++;
			}
			//endregion

			//region Item Adjust Form Step 3: Get Current Item and its Recipe, create Appropriate Action Drop Down
			$ewim_itemID= $_REQUEST['item_id'];
			$ewim_aItem= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_items WHERE id = $ewim_itemID",ARRAY_A);

			//Get Item Category, create Action Array
			switch ($ewim_aGame['game_system']){
				case "DnD":
					$ewim_aActionsRecord= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_meta_data WHERE meta_key = 'item_actions'",ARRAY_A);
					$ewim_aActions= explode(",", $ewim_aActionsRecord['meta_value']);
					break;
				case "Eve":
					switch ($ewim_aItem['category']){
						case "Mineral":
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
						case "Ore":
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
								)
							);
							break;
						case "Blueprint":
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
								),
								array(
									'text'  => 'Copy',
									'value' => 'Copy'
								)
							);
							break;
						case "Blueprint Copy":
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
								)
							);
							break;
						default:
							$ewim_aActionsRecord= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_meta_data WHERE meta_key = 'eve_item_actions'",ARRAY_A);
							$ewim_aActions= explode(",", $ewim_aActionsRecord['meta_value']);
							break;
					}
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

			//region Item Adjust Form Step 4: Create Amount Fields Associated with Action
			//Create Amount Field for Each possible Action
			foreach ($ewim_aActions as $ewim_action){
				$ewim_oNewField= clone $ewim_oNumberFieldTemplate;

				switch ($ewim_action['text']){
					case "Copy":
						$ewim_oNewField->label= 'Total Products that can be produced from all copies being made';
						break;
					default:
						$ewim_oNewField->label= 'Total Amount of Product to '.$ewim_action['text'];
						break;
				}


				if($ewim_action['text'] == 'Sell' || $ewim_action['text'] == 'Post' || $ewim_action['text'] == 'Manufacture' && $ewim_aItem['category'] == 'Blueprint Copy' ){
					$ewim_oNewField->rangeMin= 0;
					$ewim_oNewField->rangeMax= $ewim_aItem['item_inventory_quantity'];
				}

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

			//region Item Adjust Form Step 3: Add Fields need for the Game System
			switch ($ewim_aGame['game_system']){
				case "DnD":
					//region DnD Step 1: Create and Label Coinage Field(s)
					$ewim_aCoinage= array(
						'Copper',
						'Silver',
						'Gold'
					);
					foreach($ewim_aCoinage as $ewim_coin){
						$ewim_oNewField= clone $ewim_oNumberFieldTemplate;
						$ewim_oNewField->label= "Cost Per Item in: ".$ewim_coin;
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
				case "Eve":
					//region Eve Step 1: Create and Label Coinage Field(s)
					$ewim_aCoinage= array(
						'ISK'
					);
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

					if($ewim_aItem['item_recipe_ingredients'] != NULL){
						$ewim_aItemRecipe= json_decode($ewim_aItem['item_recipe_ingredients'],true);
						$ewim_fieldCSS= 'gf_left_half';
						foreach($ewim_aItemRecipe as $ewim_ingredientName => $ewim_ingredientID){
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
					$ewim_oNewSection->label= 'Total Minerals Used during Manufacturing';
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

					if($ewim_aItem['item_recipe_ingredients'] != NULL){
						$ewim_aItemRecipe= json_decode($ewim_aItem['item_recipe_ingredients'],true);
						$ewim_fieldCSS= 'gf_left_half';
						foreach($ewim_aItemRecipe as $ewim_ingredientName => $ewim_ingredientID){
							$ewim_aIngredientItem= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_items WHERE id = $ewim_ingredientID",ARRAY_A);
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

					//region Default Step 1: Create and Label Coinage Field(s)
					$ewim_aCoinage= array(
						'Credit'
					);
					foreach($ewim_aCoinage as $ewim_coin){
						$ewim_oNewField= clone $ewim_oNumberFieldTemplate;
						$ewim_oNewField->label= "Cost Per Item in: ".$ewim_coin;
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

					break;
			}
			//endregion

			break;
		case "Posted Transaction Form":
			//region Global Variables, Classes, Local Variables
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

	//region Debug
	if($ewim_debug_settings->ewim_formExit == 1){
		echo "<pre style='color:white;'>";
		print_r($ewim_oForm);
		echo "</pre>";
		exit;
	}
	//endregion

	return $ewim_oForm;
}

/**
 * Name: Item Related Form Pre Render
 * Desc: Can be called by any form that pulls data from the item table, as long as item_id has been passed
 */
//region Filters
add_filter( "gform_pre_render_$ewim_acquireFormID", 'item_related_form_pre_render' );
add_filter( "gform_pre_render_$ewim_removeFormID", 'item_related_form_pre_render' );
//endregion
function item_related_form_pre_render($ewim_oForm){
	add_filter( 'gform_field_value', 'item_related_form_populate_field_values', 10, 3 );
	return $ewim_oForm;
}

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
	//region Global Variables, Local Variables, Classes
	global $wpdb;
	$ewim_tables= new ewim_tables();
	$ewim_current_user= wp_get_current_user();
	$ewim_aValues= array();
	$ewim_itemID= $_REQUEST['record_id'];

	$ewim_userID= $ewim_current_user->ID;
	//$ewim_debug_settings= new ewim_debug_settings();
	$ewim_activeGameID= get_user_meta($ewim_userID, 'active_game', true);
	//endregion

	//region Step 1: Get Item Information and Decode where needed
	$ewim_aItem= $wpdb->get_row( "SELECT * FROM $ewim_tables->ewim_items WHERE id = '$ewim_itemID'", ARRAY_A );//Find customer by location ID
	$ewim_aItem['item_recipe_ingredients']= json_decode($ewim_aItem['item_recipe_ingredients'], true);
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
					else{
						foreach($ewim_value as $ewim_k => $ewim_v){
							$ewim_aValues[$ewim_k]= $ewim_v;
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

	return isset( $ewim_aValues[ $name ] ) ? $ewim_aValues[ $name ] : $value;
}