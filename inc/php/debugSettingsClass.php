<?php
/**
 * Created by PhpStorm.
 * Author: David
 * Date: 3/8/2019
 * Time: 16:22
 * Name:
 * Desc:
 */
//todo create an admin page to turn this on and off instead of having to edit code.




/**
 * Class ewim_debug_settings
 *
 * @property int ewim_wpdbSelect
 * @property int ewim_wpdbEdit
 * @property int ewim_wpdbError
 *
 * @property int ewim_CreateFieldsFormStart
 * @property int ewim_CreateFieldsFormEnd
 *
 */
class ewim_debug_settings {
	public function __construct() {

		//region Database
		$this->ewim_wpdbSelect= get_option('ewim_wpdbSelect');
		$this->ewim_wpdbEdit=   get_option('ewim_wpdbEdit');
		$this->ewim_wpdbError=   get_option('ewim_wpdbError');
		//endregion

		//region Forms
		$this->ewim_CreateFieldsFormStart= get_option('ewim_CreateFieldsFormStart');
		$this->ewim_CreateFieldsFormEnd= get_option('ewim_CreateFieldsFormEnd');



		//endregion
	}
}