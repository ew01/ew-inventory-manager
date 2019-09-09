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
//todo: More items can be moved out of the switch


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
	$ewim_gform_field_creator= new ewim_gform_field_creator();

	$ewim_current_user= wp_get_current_user();

	$ewim_userID= $ewim_current_user->ID;
	$ewim_activeInventoryID= get_user_meta($ewim_userID, 'active_inventory', true);
	//endregion

	//region Debug: ewim_CreateFieldsFormStart
	if($ewim_debug_settings->ewim_CreateFieldsFormStart == 1){
		echo "<h1>Create Fields Form Start</h1>";
		echo "<pre style='color:red;'>";
		print_r($ewim_oForm);
		echo "</pre>";
		exit;
	}
	//endregion

	//region Loop Fields. Get any template fields, and find id fields for conditional logic
	$ewim_fieldCount= 0;//This count is for the array element of the fields in the form array
	foreach ($ewim_oForm['fields'] as &$ewim_oField){
		switch ($ewim_oField->adminLabel){
			case 'single_line_text_template'://Single Line Text Field : SLTF
				$ewim_oSingleLineTextFieldTemplate= clone $ewim_oField;
				break;
			case 'radio_template'://Radio Field : RF
				$ewim_oRadioFieldTemplate= clone $ewim_oField;
				break;
			case 'number_template'://Number Field : NF
				$ewim_oNumberFieldTemplate= clone $ewim_oField;
				break;
			case 'drop_down_template'://Drop Down Field : DDF
				$ewim_oDropDownFieldTemplate= clone $ewim_oField;
				break;
			case 'checkbox_template'://Checkbox Field : CBF
				$ewim_oCheckboxFieldTemplate= clone $ewim_oField;
				break;
		}

		switch ($ewim_oField->type){
			case 'section'://Section Field : SF
				$ewim_oSectionFieldTemplate= $ewim_oField;
				break;
		}
		$ewim_fieldCount++;
	}
	//endregion

	//region Get record ID if passed. If we get a record id we get the record so we can fill out the form
	if(isset($_REQUEST['record_id']) and $_REQUEST['record_id'] != '') {
		//Record ID being Passed
		$ewim_recordID= $_REQUEST['record_id'];

		//region Get Records
		$ewim_aInventory= $wpdb->get_row( "SELECT * FROM $ewim_tables->ewim_games WHERE id = '$ewim_recordID'", ARRAY_A );
		$ewim_aItem= $wpdb->get_row( "SELECT * FROM $ewim_tables->ewim_items WHERE id = '$ewim_recordID'", ARRAY_A );
		//endregion

		//region Check for Inventory Record, Handle
		if(isset($ewim_aInventory['id'])){
			$ewim_aInventory['inventory_currencies']= json_decode($ewim_aInventory['inventory_currencies'], true);
		}
		//endregion

		//region Check for Item Record, Handle
		if(isset($ewim_aItem['id'])){
			$ewim_aItem['item_meta']= json_decode($ewim_aItem['item_meta'], true);
			switch ($ewim_aItem['category']){
				case 'Product':
					$ewim_aItem['design_details']= json_decode($ewim_aItem['design_details'], true);
					$ewim_productID= $ewim_aItem['id'];
					break;
				case 'Component':
					$ewim_aItem['design_details']= json_decode($ewim_aItem['design_details'], true);
					$ewim_productID= $ewim_aItem['id'];
					break;
				case 'Design Copy':
					$ewim_productID= $ewim_aItem['item_meta']['product_id'];
					$ewim_aProduct= $wpdb->get_row( "SELECT * FROM $ewim_tables->ewim_items WHERE id = '$ewim_productID'", ARRAY_A );
					$ewim_aItem['design_details']= json_decode($ewim_aProduct['design_details'], true);
					break;
				case 'Raw Resource':
					$ewim_aItem['design_details']= json_decode($ewim_aItem['design_details'], true);
					break;
			}
		}
		//endregion
	}
	//endregion

	//region Set starting values for variables changed/increased by loop
	$ewim_fieldCSS= 'gf_left_half';//Sets the starting CSS Class for new fields
	$ewim_itemCount= 1;
	$ewim_fieldID= 1000;
	//endregion

	//region Step 2: Switch to processor based on Form ID
	switch ($ewim_oForm['id']){
		case $ewim_getOptions->ewim_inventoryFormID:
			//region Inventory Name; Single Line Text Field
			$ewim_oNewSLTF= clone $ewim_oSingleLineTextFieldTemplate;
			$ewim_oNewSLTF->label= 'Inventory Name';//Display Label
			$ewim_oNewSLTF->adminLabel= 'inventory_name';//Backend Label
			$ewim_oNewSLTF->inputName= 'inventory_name';//Pre Populate Label
			$ewim_oNewSLTF->visibility= 'visible';
			$ewim_oNewSLTF->id= $ewim_fieldID;
			$ewim_oNewSLTF->isRequired= 1;
			$ewim_oNewSLTF->cssClass= 'ewim_dbField';
			$ewim_oNewSLTF->defaultValue= (isset($ewim_aInventory['inventory_name']) ? $ewim_aInventory['inventory_name'] : '');

			//Push our new field object into the form object
			$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewSLTF;

			//Increase Counts
			$ewim_fieldCount++;
			$ewim_fieldID++;
			//endregion

			//region Inventory Currency System; Drop Down Field
			$ewim_oNewDDF= clone $ewim_oDropDownFieldTemplate;
			$ewim_oNewDDF->label= 'Currency System';
			$ewim_oNewDDF->adminLabel= 'inventory_currency_system';
			$ewim_oNewDDF->inputName= 'inventory_currency_system';
			$ewim_oNewDDF->visibility= 'visible';
			$ewim_oNewDDF->id= $ewim_inventoryCurrencyStyleFieldID= $ewim_fieldID;
			$ewim_oNewDDF->isRequired= 1;
			$ewim_oNewDDF->cssClass= 'ewim_dbField';
			$ewim_oNewDDF->placeholder= 'Select One';

			$ewim_choicesCount= 0;
			$ewim_aInventoryCurrencyStyles= ewim_get_meta_value('default_currency_styles');
			foreach($ewim_aInventoryCurrencyStyles as $ewim_currencyStyleKey => $ewim_currencyStyleValue){
				$ewim_oNewDDF->choices[$ewim_choicesCount]= array(
					'text'  => $ewim_currencyStyleKey,
					'value' => $ewim_currencyStyleKey
				);
				$ewim_choicesCount++;
			}

			$ewim_oNewDDF->defaultValue= (isset($ewim_aInventory['inventory_currency_system']) ? $ewim_aInventory['inventory_currency_system'] : '');

			//Place New Field into Form
			$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewDDF;

			//Increase Counters
			$ewim_fieldCount++;
			$ewim_itemCount++;
			$ewim_fieldID++;

			//endregion

			//region Single Currency System
			//region Single Currency System; Section
			$ewim_oNewSection= clone $ewim_oSectionFieldTemplate;
			$ewim_oNewSection->label= 'Please Label your Currency';
			$ewim_oNewSection->visibility= 'visible';
			$ewim_oNewSection->id= $ewim_fieldID;
			$ewim_oNewSection->description= $ewim_aInventoryCurrencyStyles['Single Currency System'];
			$ewim_oNewSection->conditionalLogic= array(
				'actionType'    => 'show',
				'logicType'     => 'any',
				'rules'         => array(
					array(
						'fieldId'   => $ewim_inventoryCurrencyStyleFieldID,
						'operator'  => 'is',
						'value'     => 'Single Currency System'
					)
				)
			);
			$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewSection;//Push our new field object into the form object
			$ewim_fieldCount++;
			$ewim_fieldID++;
			//endregion

			//region Single Currency System; Single Line Text Field
			$ewim_oNewSLTF= clone $ewim_oSingleLineTextFieldTemplate;
			$ewim_oNewSLTF->label= 'Currency Name';//Display Label
			$ewim_oNewSLTF->adminLabel= 'inventory_currency';//Backend Label
			$ewim_oNewSLTF->inputName= 'inventory_currency';//Pre Populate Label
			$ewim_oNewSLTF->visibility= 'visible';
			$ewim_oNewSLTF->id= $ewim_fieldID;
			$ewim_oNewSLTF->isRequired= 1;
			$ewim_oNewSLTF->cssClass= 'currency';
			$ewim_oNewSLTF->conditionalLogic= array(
				'actionType'    => 'show',
				'logicType'     => 'any',
				'rules'         => array(
					array(
						'fieldId'   => $ewim_inventoryCurrencyStyleFieldID,
						'operator'  => 'is',
						'value'     => 'Single Currency System'
					)
				)
			);

			$ewim_oNewSLTF->defaultValue= (isset($ewim_aInventory['inventory_currencies']['inventory_currency']) ? $ewim_aInventory['inventory_currencies']['inventory_currency'] : '');

			//Push our new field object into the form object
			$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewSLTF;

			//Increase Counts
			$ewim_fieldCount++;
			$ewim_fieldID++;
			//endregion
			//endregion

			//region Triple Currency System
			//region Triple Currency System; Section
			$ewim_oNewSection= clone $ewim_oSectionFieldTemplate;
			$ewim_oNewSection->label= 'Please Label your Currency';
			$ewim_oNewSection->visibility= 'visible';
			$ewim_oNewSection->id= $ewim_fieldID;
			$ewim_oNewSection->description= $ewim_aInventoryCurrencyStyles['Triple Currency System'];
			$ewim_oNewSection->conditionalLogic= array(
				'actionType'    => 'show',
				'logicType'     => 'any',
				'rules'         => array(
					array(
						'fieldId'   => $ewim_inventoryCurrencyStyleFieldID,
						'operator'  => 'is',
						'value'     => 'Triple Currency System'
					)
				)
			);
			$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewSection;//Push our new field object into the form object
			$ewim_fieldCount++;
			$ewim_fieldID++;
			//endregion

			//region Triple Currency System; Single Line Text Field

			//region First Currency
			$ewim_oNewSLTF= clone $ewim_oSingleLineTextFieldTemplate;
			$ewim_oNewSLTF->label= 'First Currency Name';//Display Label
			$ewim_oNewSLTF->adminLabel= 'tc_first_currency';//Backend Label
			$ewim_oNewSLTF->inputName= 'tc_first_currency';//Pre Populate Label
			$ewim_oNewSLTF->visibility= 'visible';
			$ewim_oNewSLTF->id= $ewim_fieldID;
			$ewim_oNewSLTF->isRequired= 1;
			$ewim_oNewSLTF->cssClass= 'currency';
			$ewim_oNewSLTF->conditionalLogic= array(
				'actionType'    => 'show',
				'logicType'     => 'any',
				'rules'         => array(
					array(
						'fieldId'   => $ewim_inventoryCurrencyStyleFieldID,
						'operator'  => 'is',
						'value'     => 'Triple Currency System'
					)
				)
			);
			$ewim_oNewSLTF->defaultValue= (isset($ewim_aInventory['inventory_currencies']['tc_first_currency']) ? $ewim_aInventory['inventory_currencies']['tc_first_currency'] : '');
			//Push our new field object into the form object
			$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewSLTF;

			//Increase Counts
			$ewim_fieldCount++;
			$ewim_fieldID++;
			//endregion

			//region Second Currency
			$ewim_oNewSLTF= clone $ewim_oSingleLineTextFieldTemplate;
			$ewim_oNewSLTF->label= 'Second Currency Name';//Display Label
			$ewim_oNewSLTF->adminLabel= 'tc_second_currency';//Backend Label
			$ewim_oNewSLTF->inputName= 'tc_second_currency';//Pre Populate Label
			$ewim_oNewSLTF->visibility= 'visible';
			$ewim_oNewSLTF->id= $ewim_fieldID;
			$ewim_oNewSLTF->isRequired= 1;
			$ewim_oNewSLTF->cssClass= 'currency';
			$ewim_oNewSLTF->conditionalLogic= array(
				'actionType'    => 'show',
				'logicType'     => 'any',
				'rules'         => array(
					array(
						'fieldId'   => $ewim_inventoryCurrencyStyleFieldID,
						'operator'  => 'is',
						'value'     => 'Triple Currency System'
					)
				)
			);
			$ewim_oNewSLTF->defaultValue= (isset($ewim_aInventory['inventory_currencies']['tc_second_currency']) ? $ewim_aInventory['inventory_currencies']['tc_second_currency'] : '');

			//Push our new field object into the form object
			$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewSLTF;

			//Increase Counts
			$ewim_fieldCount++;
			$ewim_fieldID++;
			//endregion

			//region Third Currency
			$ewim_oNewSLTF= clone $ewim_oSingleLineTextFieldTemplate;
			$ewim_oNewSLTF->label= 'Third Currency Name';//Display Label
			$ewim_oNewSLTF->adminLabel= 'tc_third_currency';//Backend Label
			$ewim_oNewSLTF->inputName= 'tc_third_currency';//Pre Populate Label
			$ewim_oNewSLTF->visibility= 'visible';
			$ewim_oNewSLTF->id= $ewim_fieldID;
			$ewim_oNewSLTF->isRequired= 1;
			$ewim_oNewSLTF->cssClass= 'currency';
			$ewim_oNewSLTF->conditionalLogic= array(
				'actionType'    => 'show',
				'logicType'     => 'any',
				'rules'         => array(
					array(
						'fieldId'   => $ewim_inventoryCurrencyStyleFieldID,
						'operator'  => 'is',
						'value'     => 'Triple Currency System'
					)
				)
			);
			$ewim_oNewSLTF->defaultValue= (isset($ewim_aInventory['inventory_currencies']['tc_third_currency']) ? $ewim_aInventory['inventory_currencies']['tc_third_currency'] : '');

			//Push our new field object into the form object
			$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewSLTF;

			//Increase Counts
			$ewim_fieldCount++;
			$ewim_fieldID++;
			//endregion

			//endregion
			//endregion
			break;
		case $ewim_getOptions->ewim_itemFormID:
			//region Get Options
			$ewim_categoryList= 'default_item_categories';
			$ewim_aCategories= ewim_get_meta_value($ewim_categoryList);
			//endregion

			//region Get the game system, set game system variables
			$ewim_aGame= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_games WHERE id = $ewim_activeInventoryID",ARRAY_A);
			//endregion

			//region Get Records, Decode
			$ewim_aRefinedResources= $wpdb->get_results("SELECT * FROM $ewim_tables->ewim_items WHERE inventory_id = $ewim_activeInventoryID AND category = 'Refined Resource' ORDER BY item_name",ARRAY_A);
			$ewim_aComponents= $wpdb->get_results("SELECT * FROM $ewim_tables->ewim_items WHERE inventory_id = $ewim_activeInventoryID AND category = 'Component' ORDER BY item_name",ARRAY_A);
			//endregion

			//region Create and Label Fields

			//region Item Name; Single Line Text Field
			$ewim_oNewSLTF= clone $ewim_oSingleLineTextFieldTemplate;
			$ewim_oNewSLTF->label= 'Name';//Display Label
			$ewim_oNewSLTF->adminLabel= 'item_name';//Backend Label
			$ewim_oNewSLTF->inputName= 'item_name';//Pre Populate Label
			$ewim_oNewSLTF->defaultValue= (isset($ewim_aItem['item_name']) ? $ewim_aItem['item_name'] : '');
			$ewim_oNewSLTF->visibility= 'visible';
			$ewim_oNewSLTF->id= $ewim_fieldID;
			$ewim_oNewSLTF->isRequired= 1;
			$ewim_oNewSLTF->cssClass= 'ewim_dbField';

			//Push our new field object into the form object
			$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewSLTF;

			//Increase Counts
			$ewim_fieldCount++;
			$ewim_fieldID++;
			//endregion

			//region Item Category; Drop Down Field
			$ewim_oNewDDF= clone $ewim_oDropDownFieldTemplate;
			$ewim_oNewDDF->label= 'Category';
			$ewim_oNewDDF->adminLabel= 'category';
			$ewim_oNewDDF->inputName= 'category';
			$ewim_oNewDDF->visibility= 'visible';
			$ewim_oNewDDF->id= $ewim_categoryFieldID= $ewim_fieldID;
			$ewim_oNewDDF->isRequired= 1;
			$ewim_oNewDDF->cssClass= 'ewim_dbField';
			$ewim_oNewDDF->placeholder= 'Select One';

			$ewim_choicesCount= 0;
			foreach($ewim_aCategories as $ewim_aCategory){
				if($ewim_aCategory != 'Design Copy'){
					$ewim_oNewDDF->choices[$ewim_choicesCount]= array(
						'text'  => $ewim_aCategory,
						'value'  => $ewim_aCategory
					);
					$ewim_choicesCount++;
				}
			}

			$ewim_oNewDDF->defaultValue= (isset($ewim_aItem['category']) ? $ewim_aItem['category'] : '');

			//Place New Field into Form
			$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewDDF;

			//Increase Counters
			$ewim_fieldCount++;
			$ewim_itemCount++;
			$ewim_fieldID++;

			//endregion

			//region Category Refined Resource Empty

			//Nothing to do for minerals, yet.

			//endregion

			//region Category Raw Resource; Section
			$ewim_oNewSF= clone $ewim_oSectionFieldTemplate;
			$ewim_oNewSF->label= 'Choose what Refined Resources are contained in Raw Resource';
			$ewim_oNewSF->visibility= 'visible';
			$ewim_oNewSF->id= $ewim_fieldID;
			$ewim_oNewSF->conditionalLogic= array(
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
			$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewSF;//Push our new field object into the form object
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
			$ewim_oNewField->label=             '';//Label that displays
			$ewim_oNewField->allowsPrepopulate= 1;//Makes it usable by pre populate functions
			$ewim_oNewField->inputName=         'design_items';//Label used for pre populate
			$ewim_oNewField->adminLabel=        'design_items';//Backend label to access field
			$ewim_oNewField->visibility=        'visible';//Make it Visible
			$ewim_oNewField->cssClass=          'gf_list_3col design_items';//Add in the alternating CSS
			$ewim_refinedResourceCBFieldID= $ewim_oNewField->id= $ewim_fieldID;//Give it an id
			//$ewim_oNewField->isRequired= 1;

			$ewim_rsC= 0;
			foreach($ewim_aRefinedResources as $ewim_aRefinedResource){
				//skipping index that are multiples of 10 (multiples of 10 create problems as the input IDs)
				if ( $ewim_rsC != 0 && $ewim_rsC % 10 == 0 ) {
					$ewim_rsC++;
				}

				//Check for used minerals since standard populate values is not working for dynamic checkboxes ATM
				if(isset($ewim_aItem['id'])){
					foreach($ewim_aItem['design_details'] as $ewim_designItemID => $ewim_aDesignItem){
						if($ewim_aRefinedResource['id'] == $ewim_designItemID){
							$ewim_isSelected= 'Yes';
							break;
						}
						else{
							$ewim_isSelected= '';
						}
					}
				}

				$ewim_oNewField->choices[$ewim_rsC]= array(
					'text'  => $ewim_aRefinedResource['item_name'],
					'value' => $ewim_aRefinedResource['id'],
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

			//region Refined Resource Counts
			$ewim_rsC= 0;
			foreach($ewim_aRefinedResources as $ewim_aRefinedResource){
				$ewim_oNewNumberField= clone $ewim_oNumberFieldTemplate;//Clone the Field Object
				$ewim_oNewNumberField->label=               $ewim_aRefinedResource['item_name'].' Count';//Label that displays
				$ewim_oNewNumberField->adminLabel=          $ewim_aRefinedResource['item_name'].'_'.$ewim_aRefinedResource['id'];//Backend label to access field
				$ewim_oNewNumberField->allowsPrepopulate=   1;//Makes it usable by pre populate functions
				$ewim_oNewNumberField->inputName=           $ewim_aRefinedResource['item_name'].'_'.$ewim_aRefinedResource['id'];//Label used for pre populate
				$ewim_oNewNumberField->visibility=          'visible';//Make it Visible
				$ewim_oNewNumberField->cssClass=            'design_details '.$ewim_fieldCSS;//Add in the alternating CSS
				$ewim_oNewNumberField->id=                  $ewim_fieldID;//Give it an id
				$ewim_oNewNumberField->conditionalLogic=    array(
					'actionType'    => 'show',
					'logicType'     => 'all',
					'rules'         => array(
						array(
							'fieldId'   => $ewim_refinedResourceCBFieldID,
							'operator'  => 'is',
							'value'     => $ewim_aRefinedResource['id']
						),
						array(
							'fieldId'   => $ewim_categoryFieldID,
							'operator'  => 'is',
							'value'     => 'Product'
						)
					)
				);
				//$ewim_oNewField->isRequired= 1;
				$ewim_oNewNumberField->defaultValue= $ewim_aItem['design_details'][$ewim_aRefinedResource['id']]['amount'];


				$ewim_oForm['fields'][$ewim_fieldCount]= $ewim_oNewNumberField;//Push our new field object into the form object
				$ewim_rsC++;
				//Increase our counters, alternate our strings
				$ewim_fieldCount++;
				$ewim_itemCount++;
				$ewim_fieldID++;
				$ewim_fieldCSS= ($ewim_fieldCount / 2 ? 'gf_right_half' : 'gf_left_half');
			}
			//endregion

			//region Refined Resource Counts
			$ewim_rsC= 0;
			foreach($ewim_aRefinedResources as $ewim_aRefinedResource){
				$ewim_oNewNumberField= clone $ewim_oNumberFieldTemplate;//Clone the Field Object
				$ewim_oNewNumberField->label=               $ewim_aRefinedResource['item_name'].' Count';//Label that displays
				$ewim_oNewNumberField->adminLabel=          $ewim_aRefinedResource['item_name'].'_'.$ewim_aRefinedResource['id'];//Backend label to access field
				$ewim_oNewNumberField->allowsPrepopulate=   1;//Makes it usable by pre populate functions
				$ewim_oNewNumberField->inputName=           $ewim_aRefinedResource['item_name'].'_'.$ewim_aRefinedResource['id'];//Label used for pre populate
				$ewim_oNewNumberField->visibility=          'visible';//Make it Visible
				$ewim_oNewNumberField->cssClass=            'design_details '.$ewim_fieldCSS;//Add in the alternating CSS
				$ewim_oNewNumberField->id=                  $ewim_fieldID;//Give it an id
				$ewim_oNewNumberField->conditionalLogic=    array(
					'actionType'    => 'show',
					'logicType'     => 'all',
					'rules'         => array(
						array(
							'fieldId'   => $ewim_refinedResourceCBFieldID,
							'operator'  => 'is',
							'value'     => $ewim_aRefinedResource['id']
						),
						array(
							'fieldId'   => $ewim_categoryFieldID,
							'operator'  => 'is',
							'value'     => 'Component'
						)
					)
				);
				//$ewim_oNewField->isRequired= 1;
				$ewim_oNewNumberField->defaultValue= $ewim_aItem['design_details'][$ewim_aRefinedResource['id']]['amount'];


				$ewim_oForm['fields'][$ewim_fieldCount]= $ewim_oNewNumberField;//Push our new field object into the form object
				$ewim_rsC++;
				//Increase our counters, alternate our strings
				$ewim_fieldCount++;
				$ewim_itemCount++;
				$ewim_fieldID++;
				$ewim_fieldCSS= ($ewim_fieldCount / 2 ? 'gf_right_half' : 'gf_left_half');
			}
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
			$ewim_oNewField->label=             '';//Label that displays
			$ewim_oNewField->allowsPrepopulate= 1;//Makes it usable by pre populate functions
			$ewim_oNewField->inputName=         'design_components';//Label used for pre populate
			$ewim_oNewField->adminLabel=        'design_components';//Backend label to access field
			$ewim_oNewField->visibility=        'visible';//Make it Visible
			$ewim_oNewField->cssClass=          'gf_list_3col';//Add in the alternating CSS
			$ewim_componentCBFieldID= $ewim_oNewField->id=                $ewim_fieldID;//Give it an id
			//$ewim_oNewField->isRequired= 1;

			$ewim_rsC= 0;
			foreach($ewim_aComponents as $ewim_aComponent){
				//skipping index that are multiples of 10 (multiples of 10 create problems as the input IDs)
				if ( $ewim_rsC != 0 && $ewim_rsC % 10 == 0 ) {
					$ewim_rsC++;
				}

				//Check for used minerals since standard populate values is not working for dynamic checkboxes ATM
				if(isset($ewim_aItem['id'])) {
					foreach ( $ewim_aItem['design_details'] as $ewim_designItemID => $ewim_aDesignItem ) {
						if ( $ewim_aComponent['id'] == $ewim_designItemID ) {
							$ewim_isSelected = 'Yes';
							break;
						}
						else {
							$ewim_isSelected = '';
						}
					}
				}

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

			//region Components Counts
			$ewim_rsC= 0;
			foreach($ewim_aComponents as $ewim_aComponent){
				$ewim_oNewNumberField= clone $ewim_oNumberFieldTemplate;//Clone the Field Object
				$ewim_oNewNumberField->label=               $ewim_aComponent['item_name'].' Count';//Label that displays
				$ewim_oNewNumberField->adminLabel=          $ewim_aComponent['item_name'].'_'.$ewim_aComponent['id'];//Backend label to access field
				$ewim_oNewNumberField->allowsPrepopulate=   1;//Makes it usable by pre populate functions
				$ewim_oNewNumberField->inputName=           $ewim_aComponent['item_name'].'_'.$ewim_aComponent['id'];//Label used for pre populate
				$ewim_oNewNumberField->visibility=          'visible';//Make it Visible
				$ewim_oNewNumberField->cssClass=            'design_details '.$ewim_fieldCSS;//Add in the alternating CSS
				$ewim_oNewNumberField->id=                  $ewim_fieldID;//Give it an id
				$ewim_oNewNumberField->isRequired=          0;
				$ewim_oNewNumberField->conditionalLogic=    array(
					'actionType'    => 'show',
					'logicType'     => 'any',
					'rules'         => array(
						array(
							'fieldId'   => $ewim_componentCBFieldID,
							'operator'  => 'is',
							'value'     => $ewim_aComponent['item_name'].'_'.$ewim_aComponent['id']
						)
					)
				);
				$ewim_oNewNumberField->defaultValue=        $ewim_aItem['design_details'][$ewim_aComponent['item_name']]['amount'];

				$ewim_oForm['fields'][$ewim_fieldCount]= $ewim_oNewNumberField;//Push our new field object into the form object
				$ewim_rsC++;
				//Increase our counters, alternate our strings
				$ewim_fieldCount++;
				$ewim_itemCount++;
				$ewim_fieldID++;
				$ewim_fieldCSS= ($ewim_fieldCount / 2 ? 'gf_right_half' : 'gf_left_half');
			}




			//endregion

			//endregion

			break;
		case $ewim_getOptions->ewim_itemTransactionFormID:
			//todo consume action
			//todo percentage for sells
			//todo posting option, fee field, figure way to calculate fee when item sales, new table field?

			//region Currency Handler
			$ewim_aInventory= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_games WHERE id = $ewim_activeInventoryID",ARRAY_A);
			$ewim_aInventory['inventory_currencies']= json_decode($ewim_aInventory['inventory_currencies'], true);

			switch ($ewim_aInventory['inventory_currency_system']){
				case "Singe Currency System":
					$ewim_aInventoryCurrencies= array(
						$ewim_aInventory['inventory_currencies']['inventory_currency']
					);
					break;
				case "Triple Currency System":
					$ewim_aInventoryCurrencies[0]= $ewim_aInventory['inventory_currencies']['tc_first_currency'];
					$ewim_aInventoryCurrencies[1]= $ewim_aInventory['inventory_currencies']['tc_second_currency'];
					$ewim_aInventoryCurrencies[2]= $ewim_aInventory['inventory_currencies']['tc_third_currency'];
					break;
			}
			//endregion

			//region Appropriate Action List; Drop Down
			switch ($ewim_aItem['category']){
				case "Product":
					$ewim_aActions= ewim_get_meta_value('product_actions');
					break;
				case "Refined Resource":
					$ewim_aActions= ewim_get_meta_value('refined_resource_actions');
					break;
				case "Raw Resource":
					$ewim_aActions= ewim_get_meta_value('raw_resource_actions');
					break;
				case "Design Copy":
					$ewim_aActions= ewim_get_meta_value('design_copy_actions');
					break;
				case "Component":
					$ewim_aActions= ewim_get_meta_value('component_actions');
					break;
				default:
					$ewim_aActions= ewim_get_meta_value('default_item_actions');
					break;
			}

			$ewim_oNewField= clone $ewim_oDropDownFieldTemplate;
			$ewim_oNewField->label= 'Action';
			$ewim_oNewField->adminLabel= 'action';
			$ewim_oNewField->visibility= 'visible';
			$ewim_oNewField->id= $ewim_actionFieldID= $ewim_fieldID;
			$ewim_oNewField->isRequired= 0;
			$ewim_oNewField->choices= $ewim_aActions;
			$ewim_oNewField->defaultValue= 0;
			//Place New Field into Form
			$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewField;
			//Increase Counters
			$ewim_fieldCount++;
			$ewim_itemCount++;
			$ewim_fieldID++;
			//endregion

			//region Item Amount Fields Associated with Action : Number Field
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
				if($ewim_action['text'] == 'Sell' || $ewim_action['text'] == 'Post' || $ewim_action['text'] == 'Manufacture' && $ewim_aItem['category'] == 'Design Copy' ){
					$ewim_oNewField->rangeMin= 0;
					$ewim_oNewField->rangeMax= $ewim_aItem['item_inventory_quantity'];
				}
				//endregion

				//Rest of new field
				$ewim_oNewField->adminLabel= 'amount_'.$ewim_action['value'];
				$ewim_oNewField->visibility= 'visible';
				$ewim_oNewField->cssClass= $ewim_fieldCSS;

				if($ewim_action['value'] == 'manufacture'){
					$ewim_manufactureAmountFieldID= $ewim_fieldID;
					//todo Set max amount that can be manufactured based on resources used
					$ewim_oNewField->rangeMin=      0;
					$ewim_oNewField->rangeMax=      determine_max_production($ewim_productID);
				}
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
				//Min Max for Numbers
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

			//region Coin Fields : Number Fields
			switch ($ewim_aInventory['inventory_currency_system']){
				case "Single Currency System":
					$ewim_aFieldDetails= array(
						'label'             => 'Total '.$ewim_aInventory['inventory_currencies']['inventory_currency'],
						'adminLabel'        => 'inventory_currency',
						'visibility'        => 'visible',
						'cssClass'          => $ewim_fieldCSS,
						'id'                => $ewim_fieldID,
						'conditionalLogic'  => array(
							'actionType'    => 'show',
							'logicType'     => 'any',
							'rules'         => array(
								array(
									'fieldId'   => $ewim_actionFieldID,
									'operator'  => 'is',
									'value'     => 'buy'
								),
								array(
									'fieldId'   => $ewim_actionFieldID,
									'operator'  => 'is',
									'value'     => 'sell'
								)
							)
						),
						'isRequired'        => 0,
						'defaultValue'      => 0
					);

					$ewim_oNewNumberField= $ewim_gform_field_creator->number_field($ewim_aFieldDetails, $ewim_oNumberFieldTemplate);

					$ewim_oForm['fields'][$ewim_fieldCount]= $ewim_oNewNumberField;

					//Increase Counters
					$ewim_fieldCount++;
					$ewim_itemCount++;
					$ewim_fieldID++;

					//Alternate to next CSS Class
					$ewim_fieldCSS= ($ewim_fieldCount / 2 ? 'gf_right_half' : 'gf_left_half');

					break;
				case "Triple Currency System":
					//region First Currency
					$ewim_aFieldDetails= array(
						'label'             => 'Total '.$ewim_aInventory['inventory_currencies']['tc_first_currency'],
						'adminLabel'        => 'tc_first_currency',
						'visibility'        => 'visible',
						'cssClass'          => $ewim_fieldCSS,
						'id'                => $ewim_fieldID,
						'conditionalLogic'  => array(
							'actionType'    => 'show',
							'logicType'     => 'any',
							'rules'         => array(
								array(
									'fieldId'   => $ewim_actionFieldID,
									'operator'  => 'is',
									'value'     => 'buy'
								),
								array(
									'fieldId'   => $ewim_actionFieldID,
									'operator'  => 'is',
									'value'     => 'sell'
								)
							)
						),
						'isRequired'        => 0,
						'defaultValue'      => 0
					);

					$ewim_oNewNumberField= $ewim_gform_field_creator->number_field($ewim_aFieldDetails, $ewim_oNumberFieldTemplate);

					$ewim_oForm['fields'][$ewim_fieldCount]= $ewim_oNewNumberField;

					//Increase Counters
					$ewim_fieldCount++;
					$ewim_itemCount++;
					$ewim_fieldID++;

					//Alternate to next CSS Class
					$ewim_fieldCSS= ($ewim_fieldCount / 2 ? 'gf_right_half' : 'gf_left_half');
					//endregion

					//region Second Currency
					$ewim_aFieldDetails= array(
						'label'             => 'Total '.$ewim_aInventory['inventory_currencies']['tc_second_currency'],
						'adminLabel'        => 'tc_second_currency',
						'visibility'        => 'visible',
						'cssClass'          => $ewim_fieldCSS,
						'id'                => $ewim_fieldID,
						'conditionalLogic'  => array(
							'actionType'    => 'show',
							'logicType'     => 'any',
							'rules'         => array(
								array(
									'fieldId'   => $ewim_actionFieldID,
									'operator'  => 'is',
									'value'     => 'buy'
								),
								array(
									'fieldId'   => $ewim_actionFieldID,
									'operator'  => 'is',
									'value'     => 'sell'
								)
							)
						),
						'isRequired'        => 0,
						'defaultValue'      => 0
					);

					$ewim_oNewNumberField= $ewim_gform_field_creator->number_field($ewim_aFieldDetails, $ewim_oNumberFieldTemplate);

					$ewim_oForm['fields'][$ewim_fieldCount]= $ewim_oNewNumberField;

					//Increase Counters
					$ewim_fieldCount++;
					$ewim_itemCount++;
					$ewim_fieldID++;

					//Alternate to next CSS Class
					$ewim_fieldCSS= ($ewim_fieldCount / 2 ? 'gf_right_half' : 'gf_left_half');
					//endregion

					//region Third Currency
					$ewim_aFieldDetails= array(
						'label'             => 'Total '.$ewim_aInventory['inventory_currencies']['tc_third_currency'],
						'adminLabel'        => 'tc_third_currency',
						'visibility'        => 'visible',
						'cssClass'          => $ewim_fieldCSS,
						'id'                => $ewim_fieldID,
						'conditionalLogic'  => array(
							'actionType'    => 'show',
							'logicType'     => 'any',
							'rules'         => array(
								array(
									'fieldId'   => $ewim_actionFieldID,
									'operator'  => 'is',
									'value'     => 'buy'
								),
								array(
									'fieldId'   => $ewim_actionFieldID,
									'operator'  => 'is',
									'value'     => 'sell'
								)
							)
						),
						'isRequired'        => 0,
						'defaultValue'      => 0
					);

					$ewim_oNewNumberField= $ewim_gform_field_creator->number_field($ewim_aFieldDetails, $ewim_oNumberFieldTemplate);

					$ewim_oForm['fields'][$ewim_fieldCount]= $ewim_oNewNumberField;

					//Increase Counters
					$ewim_fieldCount++;
					$ewim_itemCount++;
					$ewim_fieldID++;

					//Alternate to next CSS Class
					$ewim_fieldCSS= ($ewim_fieldCount / 2 ? 'gf_right_half' : 'gf_left_half');
					//endregion
					break;
			}
			//endregion

			//region Create and Label Sales Tax Field
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
						'value'     => 'sell'
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

			//region Broker Fee Field
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
						'value'     => 'post'
					),
					array(
						'fieldId'   => $ewim_actionFieldID,
						'operator'  => 'is',
						'value'     => 'sell'
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

			//region Posted Price field
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
						'value'     => 'post'
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

			//region Manufacture Cost
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
						'value'     => 'manufacture'
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

			//region Copy Cost
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
						'value'     => 'copy_design'
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

			//region Processing Section

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
						'value'     => 'process'
					)
				)
			);
			$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewSection;//Push our new field object into the form object
			$ewim_fieldCount++;
			$ewim_fieldID++;
			//endregion

			if($ewim_aItem['design_details'] != NULL){
				$ewim_fieldCSS= 'gf_left_half';
				foreach($ewim_aItem['design_details'] as $ewim_designItemID){
					$ewim_aIngredientItem= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_items WHERE id = $ewim_designItemID",ARRAY_A);

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
								'value'     => 'process'
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
								'value'     => 'process'
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

			//region Manufacturing Section
//todo override field for resource amounts
			//region Manufacturing Refined Resources Section Label
			$ewim_oNewSection= clone $ewim_oSectionFieldTemplate;
			$ewim_oNewSection->label= 'Total Refined Resources &amp; Components Used during Manufacturing';
			$ewim_oNewSection->visibility= 'visible';
			$ewim_oNewSection->id= $ewim_fieldID;
			$ewim_oNewSection->conditionalLogic= array(
				'actionType'    => 'show',
				'logicType'     => 'any',
				'rules'         => array(
					array(
						'fieldId'   => $ewim_actionFieldID,
						'operator'  => 'is',
						'value'     => 'manufacture'
					)
				)
			);

			$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewSection;//Push our new field object into the form object
			$ewim_fieldCount++;
			$ewim_fieldID++;
			//endregion

			//region Use Override Values : Drop Down
			$ewim_aFieldDetails= array(
				'label'             => 'Use Override Values',
				'adminLabel'        => 'use_override_values',
				'visibility'        => 'visible',
				'cssClass'          => 'gf_left_half',
				'id'                => $ewim_fieldID,
				'conditionalLogic'  => array(
					'actionType'    => 'show',
					'logicType'     => 'any',
					'rules'         => array(
						array(
							'fieldId'   => $ewim_actionFieldID,
							'operator'  => 'is',
							'value'     => 'manufacture'
						)
					)
				),
				'choices'           => array(
					array(
						'text'  => 'No',
						'value' => 'no'
					),
					array(
						'text'  => 'Yes',
						'value' => 'yes'
					)
				),
				'isRequired'        => 0,
				'defaultValue'      => 'no'
			);
			$ewim_newDropDownField= $ewim_gform_field_creator->default_field($ewim_aFieldDetails,$ewim_oDropDownFieldTemplate);
			//$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_newDropDownField;//Push our new field object into the form object

			$ewim_fieldCount++;
			$ewim_fieldID++;
			//endregion



			if($ewim_aItem['design_details'] != NULL){
				$ewim_fieldCSS= 'gf_left_half';
				$ewim_fieldCSS2= 'gf_first_quarter';
				$ewim_fieldCSS3= 'gf_second_quarter';
				foreach($ewim_aItem['design_details'] as $ewim_designItemID => $ewim_aDesignItemDetails){
					//Get Design Item full details
					$ewim_aIngredientItem= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_items WHERE id = $ewim_designItemID",ARRAY_A);

					//region Field with Calculation
					$ewim_aFieldDetails= array(
						'label'                 => $ewim_aIngredientItem['item_name'],
						'adminLabel'            => "manufacture_".$ewim_aIngredientItem['item_name'].'_'.$ewim_aIngredientItem['id'],
						'visibility'            => 'visible',
						'cssClass'              => $ewim_fieldCSS2,
						'id'                    => $ewim_fieldID,
						'conditionalLogic'      => array(
							'actionType'    => 'show',
							'logicType'     => 'any',
							'rules'         => array(
								array(
									'fieldId'   => $ewim_actionFieldID,
									'operator'  => 'is',
									'value'     => 'Manufacture'
								)
							)
						),
						'isRequired'            => 0,
						'enableCalculation'     => 1,
						'calculationFormula'    => $ewim_aDesignItemDetails['amount']." * {amount_manufacture:$ewim_manufactureAmountFieldID}"
					);
					/*
					if($ewim_aItem['category'] == 'Product' || $ewim_aItem['category'] == 'Component' || $ewim_aItem['category'] == 'Design Copy'){
						$ewim_aFieldDetails['enableCalculation']= 1;
						//$ewim_aDesign
						$ewim_aFieldDetails['calculationFormula']= $ewim_aDesignItemDetails['amount']." * {amount_manufacture:$ewim_manufactureAmountFieldID}";
					}
					*/
					$ewim_oNewNumberField= $ewim_gform_field_creator->default_field($ewim_aFieldDetails,$ewim_oNumberFieldTemplate);

					/*
					//Create New field
					$ewim_oNewField= clone $ewim_oNumberFieldTemplate;
					$ewim_oNewField->label=         $ewim_aIngredientItem['item_name'];
					$ewim_oNewField->adminLabel=    "manufacture_".$ewim_aIngredientItem['item_name'].'_'.$ewim_aIngredientItem['id'];
					$ewim_oNewField->visibility=    'visible';
					$ewim_oNewField->cssClass=      $ewim_fieldCSS;
					$ewim_oNewField->id=            $ewim_fieldID;
					$ewim_oNewField->defaultValue=  0;
					$ewim_oNewField->isRequired=    0;
					$ewim_oNewField->defaultValue=  0;
					$ewim_oNewField->rangeMin=      0;
					$ewim_oNewField->rangeMax=      $ewim_aIngredientItem['item_inventory_quantity'];
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
					if($ewim_aItem['category'] == 'Product' || $ewim_aItem['category'] == 'Component' || $ewim_aItem['category'] == 'Design Copy'){
						$ewim_oNewField->enableCalculation= 1;
						//$ewim_aDesign
						$ewim_oNewField->calculationFormula= $ewim_aDesignItemDetails['amount']." * {amount_manufacture:$ewim_manufactureAmountFieldID}";
					}
					*/


					//Place New Field into Form
					$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewNumberField;

					//Increase Counters
					$ewim_fieldCount++;
					$ewim_itemCount++;
					$ewim_fieldID++;
					//endregion

					//region Override field
					$ewim_aFieldDetails= array(
						'label'             => $ewim_aIngredientItem['item_name'].' Override',
						'adminLabel'        => "manufacture_override_".$ewim_aIngredientItem['item_name'].'_'.$ewim_aIngredientItem['id'],
						'visibility'        => 'visible',
						'cssClass'          => $ewim_fieldCSS3,
						'id'                => $ewim_fieldID,
						'conditionalLogic'  => array(
							'actionType'    => 'show',
							'logicType'     => 'any',
							'rules'         => array(
								array(
									'fieldId'   => $ewim_actionFieldID,
									'operator'  => 'is',
									'value'     => 'Manufacture'
								)
							)
						),
						'isRequired'        => 0,
						'rangeMax'          => $ewim_aIngredientItem['item_inventory_quantity']
					);
					$ewim_oNewNumberField= $ewim_gform_field_creator->default_field($ewim_aFieldDetails,$ewim_oNumberFieldTemplate);

					//Place New Field into Form
					$ewim_oForm['fields'][$ewim_fieldCount]=$ewim_oNewNumberField;

					//Increase Counters
					$ewim_fieldCount++;
					$ewim_itemCount++;
					$ewim_fieldID++;
					//endregion

					//Alternate to next CSS Class
					$ewim_fieldCSS2= ($ewim_fieldCSS2 == 'gf_first_quarter' ? 'gf_third_quarter' : 'gf_first_quarter');
					$ewim_fieldCSS3= ($ewim_fieldCSS2 == 'gf_second_quarter' ? 'gf_fourth_quarter' : 'gf_second_quarter');
				}
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
			$ewim_aGame= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_games WHERE id = $ewim_activeInventoryID",ARRAY_A);
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
							'value'     => $ewim_action['value']
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
									'value'     => 'sell'
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
	//if($ewim_debug_settings->ewim_CreateFieldsFormEnd == 1){
	if(1 == 0){
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
 * Reqs: Field must be a drop down select, and admin label must be inventory_id
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
	//$ewim_activeInventoryID= get_user_meta($ewim_userID, 'active_game', true);
	$ewim_activeGameSystem= get_user_meta($ewim_userID, 'active_game_system', true);
	//endregion

	//todo Create a new debug setting for this section

	foreach ($ewim_oForm['fields'] as &$ewim_aField){
		$ewim_cssClass= explode(" ", $ewim_aField['cssClass']);

		//region Users Game List
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
		//endregion

		//region Currency Style List
		if(in_array('currency_style_list', $ewim_cssClass)){//todo finish this
			$ewim_aCurrencyStylesMeta= $wpdb->get_row("SELECT * FROM $ewim_tables->ewim_meta_data WHERE meta_key = 'default_currency_styles'",ARRAY_A);
			$ewim_aCurrencyStyles= explode(",",$ewim_aCurrencyStylesMeta['meta_value']);
			foreach($ewim_aCurrencyStyles as $ewim_currencyStyle){
				$choices[]= array(
					'text'  => $ewim_currencyStyle,
					'value' => $ewim_currencyStyle
				);
			}
			$ewim_aField->placeholder= 'Select One';
			$ewim_aField->choices= $choices;
		}
		//endregion

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