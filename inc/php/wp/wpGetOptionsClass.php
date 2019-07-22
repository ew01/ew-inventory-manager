<?php
/**
 * Created by PhpStorm.
 * Author: David
 * Date: 3/8/2019
 * Time: 16:08
 * Name:
 * Desc:
 */

/**
 * Class ewim_get_options
 *
 * @property mixed|void ewim_itemFormID
 * @property mixed|void ewim_itemTransactionFormID
 * @property mixed|void ewim_postedTransactionFormID
 *
 * @property mixed|void ewim_postPage
 * @property mixed|void ewim_gameFormPage
 * @property mixed|void ewim_itemPage
 * @property mixed|void ewim_itemFormPage
 * @property mixed|void ewim_itemListPage
 * @property mixed|void ewim_gameFormID

 */


class ewim_get_options {
	public function __construct() {
		$this->ewim_itemFormID= get_option('ewim_itemFormID');
		$this->ewim_itemTransactionFormID= get_option('ewim_itemTransactionFormID');
		$this->ewim_postedTransactionFormID= get_option('ewim_postedTransactionFormID');

		$this->ewim_gameFormID= get_option('ewim_gameFormID');

		$this->ewim_itemListPage= get_option('ewim_itemListPage');
		$this->ewim_itemFormPage= get_option('ewim_itemFormPage');
		$this->ewim_itemPage= get_option('ewim_itemPage');

		$this->ewim_gameFormPage= get_option('ewim_gameFormPage');

		$this->ewim_postPage= get_option('ewim_postPage');
	}
}