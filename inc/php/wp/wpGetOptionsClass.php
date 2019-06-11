<?php
/**
 * Created by PhpStorm.
 * Author: David
 * Date: 3/8/2019
 * Time: 16:08
 * Name:
 * Desc:
 */

class ewim_get_options {
	public function __construct() {
		$this->ewim_itemFormID= get_option('ewim_itemFormID');
		$this->ewim_acquireFormID= get_option('ewim_acquireFormID');
		$this->ewim_gameFormID= get_option('ewim_gameFormID');

		$this->ewim_itemListPage= get_option('ewim_itemListPage');
		$this->ewim_itemFormPage= get_option('ewim_itemFormPage');
		$this->ewim_itemPage= get_option('ewim_itemPage');

		$this->ewim_gameFormPage= get_option('ewim_gameFormPage');

		$this->ewim_postPage= get_option('ewim_postPage');
	}
}