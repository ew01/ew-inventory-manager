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
/**
 * Name: Database Configuration
 * @param null $ewim_dbVersion
 */
function ewim_install_database($ewim_dbVersion=NULL){
	//region Global Variables, Local Variables, Classes
	global $wpdb;
	$ewim_tables = new ewim_tables();
	$charset_collate = $wpdb->get_charset_collate();
	//endregion

	//region Include WP file that lets us use DB Delta
	require_once(ABSPATH.'wp-admin/includes/upgrade.php');
	//endregion

	//region Create Meta Data Table
	$ewim_sql= "
		CREATE TABLE $ewim_tables->ewim_meta_data(
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			meta_key text,
			meta_value text,
			meta_sub_key text,
			unique key id (id),
			primary key id (id)
		)
		$charset_collate;
	";
	dbDelta($ewim_sql);
	//endregion

	//region Create Games Table
	$ewim_sql="
		CREATE TABLE $ewim_tables->ewim_games (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			user_id mediumint(9),
			game_name text,
			game_system text,
			input_fields text,
	        UNIQUE KEY id (id),
	        PRIMARY KEY id (id)
	    )
	    $charset_collate;
	";
	dbDelta($ewim_sql);
	//endregion

	//region Create Ledger Table
	$ewim_sql="
		CREATE TABLE $ewim_tables->ewim_ledger (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			user_id mediumint(9),
			game_id mediumint(9),			
			item_id mediumint(9),
			transaction_type text,
			item_amount text,
			average_production_cost text,
			total_production_cost text,
			average_sbpm_cost text,
			total_sbpm_cost text,
			broker_fees text,
			sales_tax text,
			difference text,
	        UNIQUE KEY id (id),
	        PRIMARY KEY id (id)
	    )
	    $charset_collate;
	";
	dbDelta($ewim_sql);
	//endregion

	//region Create Items Table
	$ewim_sql="
		CREATE TABLE $ewim_tables->ewim_items (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			user_id mediumint(9),
			game_id mediumint(9),
			item_name text,
			category text,
			item_meta text,
			item_recipe_ingredients text,
			item_inventory_quantity mediumint(9),
			cost text,		
	        UNIQUE KEY id (id),
	        PRIMARY KEY id (id)
	    )
	    $charset_collate;
	";
	dbDelta($ewim_sql);
	//endregion

	//region Create Posted Table
	$ewim_sql="
		CREATE TABLE $ewim_tables->ewim_posted (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			user_id mediumint(9),
			game_id mediumint(9),			
			item_id mediumint(9),
			amount text,
			broker_fee TEXT,
			post_price TEXT,
			average TEXT,
			status TEXT,
			UNIQUE KEY id (id),
	        PRIMARY KEY id (id)
			
	    )
	    $charset_collate;
	";
	dbDelta($ewim_sql);
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