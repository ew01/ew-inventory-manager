<?php
/**
 * Created by PhpStorm.
 * Author: David
 * Date: 3/1/2019
 * Time: 09:06
 * Name:
 * Desc:
 */





//Runs on install or update of plugin
//Database Configuration
function ewim_install_database($ewim_dbVersion=NULL){
	//region Global Variables, Local Variables, Classes
	global $wpdb;
	$ewim_tables = new ewim_tables();
	$charset_collate = $wpdb->get_charset_collate();
	//endregion

	//region Include WP file that lets us use DB Delta
	require_once(ABSPATH.'wp-admin/includes/upgrade.php');
	//endregion


	//region Create Items Table
	$pkc_sql="
		CREATE TABLE $ewim_tables->ewim_items (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			user_id mediumint(9),
			game_id mediumint(9),
			item_name text,
			item_type text,/*Old, find method to remove*/
			item_recipe text,
			item_recipe_ingredients text,
			item_inventory_quantity mediumint(9),
			item_average_cost text,		
	        UNIQUE KEY id (id),
	        PRIMARY KEY id (id)
	    )
	    $charset_collate;
	";
	dbDelta($pkc_sql);
	//endregion

	update_option( 'ewim_db_version', $ewim_dbVersion );
}
//calling the db install only on activation
register_activation_hook( __FILE__, 'ewim_install_database' );

//Calls install if db version has changed.
function ewim_update_db_check() {
	$ewim_dbVersion = '1.0.0';//db.table.field
	if ( get_option( 'ewim_db_version' ) < $ewim_dbVersion ) {
		ewim_install_database($ewim_dbVersion);
	}
}
//Run the db update check
add_action( 'plugins_loaded', 'ewim_update_db_check' );