<?php
/**
 * Created by PhpStorm.
 * Author: David
 * Date: 9/19/2018
 * Time: 09:49
 * Name:
 * Desc:
 */





//Include all files that have variables needed globally in the shortcode function
include_once ( __DIR__ . "/php/plugin-update-checker-4.2/plugin-update-checker.php" );//Plugin Updater | Only included here as this file is where the update is fired from
include_once ( __DIR__ . "/php/database/_databaseInc.php" );
include_once ( __DIR__ . "/php/hooks/_pluginHooksInc.php" );//Hooks | Only included here as this file is where all hooks will be fired from
include_once ( __DIR__ . "/php/wp/_wpIncludes.php" );//WP Get Options
include_once ( __DIR__ . "/php/debugSettingsClass.php" );
include_once ( __DIR__ . "/php/ewimFunctions.php" );