<?php
/**
 * Created by PhpStorm.
 * Author: David
 * Date: 3/8/2019
 * Time: 16:22
 * Name:
 * Desc:
 */

class ewim_debug_settings {
	public function __construct() {
		$this->ewim_wpdbSelect= 0;
		$this->ewim_wpdbEdit=   0;
		$this->ewim_wpdbInsert= 0;

		$this->ewim_formEntry=  0;
		$this->ewim_formExit=   0;

		$this->ewim_wpdbIngredientEdit= 0;
	}
}