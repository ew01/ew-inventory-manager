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
 * Pages
 * @property mixed|void ewim_inventoriesPage
 * @property mixed|void ewim_inventoryFormPage
 * @property mixed|void ewim_inventoryFormID
 *
 * @property mixed|void ewim_itemsPage
 * @property mixed|void ewim_itemFormPage
 * @property mixed|void ewim_itemFormID
 *
 * @property mixed|void ewim_itemPage
 * @property mixed|void ewim_itemTransactionFormID
 *
 * @property mixed|void ewim_postedTransactionFormID
 *
 * @property mixed|void ewim_postPage
 */


class ewim_get_options {
	public function __construct() {
		$this->ewim_inventoriesPage= get_option('ewim_inventoriesPage');
		$this->ewim_inventoryFormPage= get_option('ewim_inventoryFormPage');
		$this->ewim_inventoryFormID= get_option('ewim_inventoryFormID');

		$this->ewim_itemsPage= get_option('ewim_itemsPage');
		$this->ewim_itemFormPage= get_option('ewim_itemFormPage');
		$this->ewim_itemFormID= get_option('ewim_itemFormID');

		$this->ewim_itemPage= get_option('ewim_itemPage');
		$this->ewim_itemTransactionFormID= get_option('ewim_itemTransactionFormID');



		$this->ewim_postedTransactionFormID= get_option('ewim_postedTransactionFormID');
		$this->ewim_postPage= get_option('ewim_postPage');
	}
}