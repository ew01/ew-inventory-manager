<?php /** @noinspection PhpStatementHasEmptyBodyInspection */
/**
 * Created by PhpStorm.
 * Author: David
 * Date: 3/1/2019
 * Time: 09:06
 * Name:
 * Desc:
 */





/**
 * Name: EWIM Install Database Version 2.0.0
 * Desc: Runs on update of plugin
 * @param $ewim_dbVersion
 */
function ewim_install_database_v_1_1_0($ewim_dbVersion){
	//region Global Variables, Local Variables, Classes
	global $wpdb;
	$ewim_tables = new ewim_tables();
	$charset_collate = $wpdb->get_charset_collate();
	//endregion

	//region Make Alterations to tables if needed, must be done before dbDelta file is included.
	$wpdb->query("ALTER TABLE `$ewim_tables->ewim_games` CHANGE `game_system` `inventory_currency_system` text;" );
	$wpdb->query("ALTER TABLE `$ewim_tables->ewim_games` CHANGE `game_name` `inventory_name` text;" );
	$wpdb->query("ALTER TABLE `$ewim_tables->ewim_items` CHANGE `game_id` `inventory_id` text;" );
	$wpdb->query("ALTER TABLE `$ewim_tables->ewim_ledger` CHANGE `average_sbpm_cost` `average_cost` text;" );
	$wpdb->query("ALTER TABLE `$ewim_tables->ewim_ledger` CHANGE `total_sbpm_cost` `total_cost` text;" );
	$wpdb->query("ALTER TABLE `$ewim_tables->ewim_ledger` CHANGE `game_id` `inventory_id` text;" );
	//endregion

	//region Include WP file that lets us use DB Delta
	require_once(ABSPATH.'wp-admin/includes/upgrade.php');
	//endregion

	//region Add Field to Meta Data Table
	$ewim_sql="
		CREATE TABLE $ewim_tables->ewim_meta_data (
			meta_sub_value text,
			meta_value_type text
	    )
	    $charset_collate;
	";
	dbDelta($ewim_sql);
	//endregion

	//region Insert Data for Meta Data Table
	$ewim_aCurrencyStyles= array(
		'Single Currency System'    => 'Credits, Dollars, ISK, ETC',
		//'Triple Currency System'    => 'Copper-Silver-Gold' //todo Move to next version
	);
	$ewim_jCurrencyStyles= json_encode($ewim_aCurrencyStyles);
	$wpdb->insert(
		$ewim_tables->ewim_meta_data,
		array(
			'meta_key'          => 'default_currency_styles',
			'meta_value'        => $ewim_jCurrencyStyles,
			'meta_value_type'   => 'json'
		)
	);

	//endregion

	//region Add Field to Games Table
	$ewim_sql="
		CREATE TABLE $ewim_tables->ewim_games (
			inventory_currencies text
	    )
	    $charset_collate;
	";
	dbDelta($ewim_sql);
	//endregion

	//region Change Game system to Inventory Currency Style
	$ewim_aGames= $wpdb->get_results( "SELECT * FROM $ewim_tables->ewim_games WHERE inventory_currency_system = 'EVE'", ARRAY_A );
	$ewim_aInsert= array();
	foreach($ewim_aGames as $ewim_aGame){
		$ewim_aInsert= array(
			'inventory_currency_system'  =>  'Single Currency System'
		);
		ewim_wpdb_edit('update',$ewim_tables->ewim_games,$ewim_aInsert,$ewim_aGame['id']);
	}
	unset($ewim_aInsert);
	//endregion

	//region Update Meta Data Values
	$ewim_aMetaData= $wpdb->get_row( "SELECT * FROM $ewim_tables->ewim_games WHERE meta_key = 'default_item_actions'", ARRAY_A );
	$ewim_aInsertMetaData= array(
		'meta_value'    => "Buy,Sell,Harvest,Process,Manufacture,Copy Design"
	);
	ewim_wpdb_edit('update',$ewim_tables->ewim_games,$ewim_aInsertMetaData,$ewim_aMetaData['id']);

	$ewim_aMetaData= $wpdb->get_row( "SELECT * FROM $ewim_tables->ewim_games WHERE meta_key = 'default_item_categories'", ARRAY_A );
	$ewim_aInsertMetaData= array(
		'meta_value'    => "Refined Resource,Raw Resource,Product,Design Copy,Component"
	);
	ewim_wpdb_edit('update',$ewim_tables->ewim_games,$ewim_aInsertMetaData,$ewim_aMetaData['id']);
	//endregion

	update_option( 'ewim_db_version', $ewim_dbVersion );
}


//region Calls install if db version has changed.
function ewim_update_db_check_v_1_1_0() {
	$ewim_dbVersion = '1.1.0';//db.table.field
	if ( get_option( 'ewim_db_version' ) < $ewim_dbVersion ) {
		ewim_install_database_v_1_1_0($ewim_dbVersion);
	}
}
//Run the db update check
add_action( 'plugins_loaded', 'ewim_update_db_check_v_1_1_0' );
//endregion