<?php
/**
 * Created by PhpStorm.
 * Author: David
 * Date: 9/4/2018
 * Time: 07:52
 * Name:
 * Desc:
 */





$ewim_current_user= wp_get_current_user();//Must be Called HERE. Tried calling in a file included by this function, and it still failed. Some how, it must be called directly in the function
$ewim_userID= $ewim_current_user->ID;