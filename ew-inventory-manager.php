<?php
/**
 * Plugin Name: EW Inventory Manager
 * Plugin URI: 
 * Description: Manage your inventory and average item cost
 * Version: 1.1.0
 * Author: David Ellenburg II
 * Author URI: http://www.ellenburgweb.com
 * License:
 * Form Versions: 2
 */



//todo: Can we make one function to render the GForm instead of writing that in each form hook? | Partially done
//todo: Apply security to pages, if passed Record ID is not associated with the User ID of the logged in User, deny access | Partial
//todo: Move math functions to a function, so we can call it and let it handle 0s once instead of coding it multiple times | Partial
//todo: Upon review, we need to bring back the equivalent of the BPO, new item type of Design
//todo: add ability to make a transaction that may not involve users items
//todo: Restrict debug pages from showing debug if user is not a admin
//todo: We can call gforms from code, do this everywhere so we can check to be sure item being edited belongs to the user
//todo: Once gform called from code is done, convert all buttons and links to divi button.

//region Includes
include_once ( __DIR__ . "/inc/inc.php" );
//endregion

//region Check for updates to the plugin.
/** @noinspection PhpUndefinedClassInspection */
$ewim_updateChecker = Puc_v4_Factory::buildUpdateChecker(
	'http://ellenburgweb.host/3_Plugins/ew-inventory-manager/ew-inventory-manager.json',
	__FILE__,
	'ew-inventory-manager'
);
//$ewim_updateChecker->checkForUpdates();
//endregion

//region Register and Add Assets
//region JavaScript Register Function
function ewim_javascript(){
	wp_enqueue_script('angularJS', '//ajax.googleapis.com/ajax/libs/angularjs/1.7.2/angular.min.js');
	wp_enqueue_script('ui-bootstrap', 'http://angular-ui.github.io/bootstrap/ui-bootstrap-tpls-0.12.1.min.js');
}
//Add JavaScript
add_action('wp_enqueue_scripts','ewim_javascript');
//endregion

//region CSS Register Function
function ewim_css(){
	wp_enqueue_style( 'plugin-css', plugins_url('ew-inventory-manager/inc/css/main.css'));//Plugin CSS
	wp_enqueue_style( 'ui-bootstrap','//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css' );//UI Bootstrap//Somehow messes with the WP css that does the drop down menu icons
	wp_enqueue_style( 'font-awesome', '//use.fontawesome.com/releases/v5.3.1/css/all.css', array(), '4.3.0' );//Font Awesome
}
//Add CSS
add_action( 'wp_enqueue_scripts', 'ewim_css' );
//endregion
//endregion

//region Admin Pages
//Info Page
function ewim_information() {

}
//Form Settings
function ewim_form_settings() {
	require_once (__DIR__."/inc/admin/formSettings.php");//Include Global Variables
}
//Page Settings
function ewim_page_settings() {
	require_once ( __DIR__ . "/inc/admin/pageSettings.php" );//Include Global Variables
}
//Page Settings
function ewim_debug_settings() {
	require_once ( __DIR__ . "/inc/admin/debugSettings.php" );//Include Global Variables
}
//Create menu pages
function ewim_admin_actions() {
	add_menu_page( 'EW Inventory Manager', 'Inventory Manager', 'manage_options', 'ewim', 'ewim_information');
	add_submenu_page( 'ewim', 'Form Settings', 'Form Settings', 'manage_options', 'ewimFS', 'ewim_form_settings');
	add_submenu_page( 'ewim', 'Page Settings', 'Page Settings', 'manage_options', 'ewimPS', 'ewim_page_settings');
	add_submenu_page( 'ewim', 'Debug Settings', 'Debug Settings', 'manage_options', 'ewimDS', 'ewim_debug_settings');
}
//Create the Menus
add_action('admin_menu', 'ewim_admin_actions');

//User Profile Additions

//endregion

//region Shortcode Function
function ewim_page($ewim_parameters){
	//region Global Variables, Classes, Local Variables
	$ewim_content= '';
	//endregion

	//region Check if user is logged in
	if(is_user_logged_in()){
		//User is Logged in
		require_once ( __DIR__ . "/inc/php/wp/user_data.php" );
		//$ewim_current_user= wp_get_current_user();

		//region Get Page Name and Module Name
		$ewim_pageName= (isset($ewim_parameters['page']) ? $ewim_parameters['page'] : 'gameList');
		$ewim_moduleName= (isset($ewim_parameters['module']) ? $ewim_parameters['module'] : '');
		//endregion

		//region Include the requested page
		if($ewim_moduleName != ''){
			/** @noinspection PhpIncludeInspection */
			include_once( __DIR__ . "/inc/modules/$ewim_moduleName/$ewim_pageName.php" );
		}
		else{
			/** @noinspection PhpIncludeInspection */
			include_once( __DIR__ . "/inc/modules/$ewim_pageName.php" );
		}
		return $ewim_content;
		//endregion
	}
	else{
		//User is not logged in
		$ewim_content= "<p>Please log in to view this page</p>";
		return $ewim_content;
	}
	//endregion
}

//Shortcode [ewim module='' page=''] or [ewim page='']
add_shortcode( 'ewim', 'ewim_page');
//endregion

//region Version Shortcode
function ewim_version(){
	return "v1.0.6";
}
//Shortcode [ewim module='' page=''] or [ewim page='']
add_shortcode( 'ewim_version', 'ewim_version');
//endregion

//region Redirect Based on User Role
function login_redirect( /** @noinspection PhpUnusedParameterInspection */	$redirect_to, $request, $user ) {
	//region Classes, Class Variables, Local Variables
	$ewim_get_options= new ewim_get_options();
	$ewim_current_user= wp_get_current_user();

	$ewim_userID= $ewim_current_user->ID;
	$ewim_activeGameID= get_user_meta($ewim_userID, 'active_game', true);
	//endregion

	$ewim_inventoriesPageURL= get_permalink(get_page_by_title($ewim_get_options->ewim_inventoriesPage));

	return $ewim_inventoriesPageURL;
	/*
	//is there a user to check?
	global $user;
	if ( isset( $user->roles ) && is_array( $user->roles ) ) {
		//check for admins
		if ( in_array( 'administrator', $user->roles ) ) {
			// redirect them to the default place
			return $redirect_to;
		} else {
			return home_url()."/shop";
		}
	}
	else {
		return $redirect_to;
	}
	*/
}
//Add Redirect Filter
add_filter( 'login_redirect', 'login_redirect', 10, 3 );
//endregion

//region No admin Bar For non admin users
function ewim_remove_admin_bar() {
	if (!current_user_can('administrator') && !is_admin()) {
		show_admin_bar(false);
	}
}
add_action('after_setup_theme', 'ewim_remove_admin_bar');
//endregion