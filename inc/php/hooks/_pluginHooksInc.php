<?php
/**
 * Created by PhpStorm.
 * Author: David
 * Date: 8/3/2018
 * Time: 14:43
 * Name:
 * Desc:
 */





global $pagenow;
if($pagenow != 'post.php'){
	//Include Hooks
	include_once ( __DIR__ . "/gravityForms/_gravityFormsHooksInc.php" );
}
