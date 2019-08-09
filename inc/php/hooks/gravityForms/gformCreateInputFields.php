<?php
/**
 * Created by PhpStorm.
 * Author: David
 * Date: 7/22/2019
 * Time: 15:26
 * Name:
 * Desc:
 */





/**
 * Class ewim_gform_create_input_fields
 *
 * @property string ewim_oNewField
 * @property int    ewim_fieldID
 * @property string ewim_fieldCSS
 * @property array  ewim_aItem
 * @property array  ewim_aAction
 * @property int    ewim_actionFieldID
 * @property int    ewim_fieldCount
 *
 */
class ewim_gform_create_input_fields {
	public function __construct($ewim_oNewField,$ewim_fieldID,$ewim_fieldCSS,$ewim_aItem,$ewim_aAction= NULL,$ewim_actionFieldID= NULL,$ewim_fieldCount=NULL) {

		$this->ewim_oNewField=      $ewim_oNewField;
		$this->ewim_fieldID=        $ewim_fieldID;
		$this->ewim_fieldCSS=       $ewim_fieldCSS;
		$this->ewim_aItem=          $ewim_aItem;
		$this->ewim_aAction=        $ewim_aAction;
		$this->ewim_actionFieldID=  $ewim_actionFieldID;
		$this->ewim_fieldCount=     $ewim_fieldCount;

	}

	//Create Item Amount Fields Associated with Action
	public function actionAmountField(){

		//region Based on the Action, different display text may be needed.
		switch ($this->ewim_aAction['text']){
			case "Copy Design":
				$this->ewim_oNewField->label= 'Total Products that can be produced from all copies being made';
				break;
			default:
				$this->ewim_oNewField->label= 'Total Amount of Product to '.$this->ewim_aAction['text'];
				break;
		}
		//endregion

		//region Set min max for amount based on some params
		if($this->ewim_aAction['text'] == 'Sell' || $this->ewim_aAction['text'] == 'Post' || $this->ewim_aAction['text'] == 'Manufacture' && $this->ewim_aItem['category'] == 'Design Copy' ){
			$this->ewim_oNewField->rangeMin= 0;
			$this->ewim_oNewField->rangeMax= $this->ewim_aItem['item_inventory_quantity'];
		}
		//endregion

		//Rest of new field
		$this->ewim_oNewField->adminLabel= 'amount_'.$this->ewim_aAction['value'];
		$this->ewim_oNewField->visibility= 'visible';
		$this->ewim_oNewField->cssClass= $this->ewim_fieldCSS;
		$this->ewim_oNewField->id= $this->ewim_fieldID;
		$this->ewim_oNewField->conditionalLogic= array(
			'actionType'    => 'show',
			'logicType'     => 'any',
			'rules'         => array(
				array(
					'fieldId'   => $this->ewim_actionFieldID,
					'operator'  => 'is',
					'value'     => $this->ewim_aAction['value']
				)
			)
		);
		$this->ewim_oNewField->isRequired= 0;
		$this->ewim_oNewField->defaultValue= 0;
		switch ($this->ewim_aAction['text']){
			case "Sell":
				$this->ewim_oNewField->rangeMin= 0;
				$this->ewim_oNewField->rangeMax= $this->ewim_aItem['item_inventory_quantity'];
				break;
			case "Craft":
				$ewim_craftAmountFieldCount= $this->ewim_fieldCount;
				break;
			case "Process":
				$this->ewim_oNewField->rangeMin= 0;
				$this->ewim_oNewField->rangeMax= $this->ewim_aItem['item_inventory_quantity'];
				break;
			case "Post":
				$this->ewim_oNewField->rangeMin= 0;
				$this->ewim_oNewField->rangeMax= $this->ewim_aItem['item_inventory_quantity'];
				break;
		}
	}
}