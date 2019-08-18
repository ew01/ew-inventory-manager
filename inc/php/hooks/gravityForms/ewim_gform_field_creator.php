<?php
/**
 * Created by PhpStorm.
 * Author: David
 * Date: 7/16/2019
 * Time: 16:58
 * Name:
 * Desc:
 */





/*
 *
 */
class ewim_gform_field_creator {

	public function number_field($ewim_aFieldDetails, $ewim_oNumberFieldTemplate){
		$ewim_oNewField= clone $ewim_oNumberFieldTemplate;
		$ewim_oNewField->label= $ewim_aFieldDetails['label'];
		$ewim_oNewField->adminLabel= $ewim_aFieldDetails['adminLabel'];
		$ewim_oNewField->visibility= $ewim_aFieldDetails['visibility'];
		$ewim_oNewField->cssClass= $ewim_aFieldDetails['cssClass'];
		$ewim_oNewField->id= $ewim_aFieldDetails['id'];
		$ewim_oNewField->conditionalLogic= $ewim_aFieldDetails['conditionalLogic'];
		$ewim_oNewField->isRequired= $ewim_aFieldDetails['isRequired'];
		$ewim_oNewField->defaultValue= $ewim_aFieldDetails['defaultValue'];

		return $ewim_oNewField;
	}
}