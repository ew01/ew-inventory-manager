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
function ewim_install_database_v_1_0_5($ewim_dbVersion=NULL){
	//region Global Variables, Local Variables, Classes
	global $wpdb;
	$ewim_tables= new ewim_tables();
	//endregion

	//region Make Alterations to tables if needed, must be done before dbDelta file is included.

	//region Change Table Field Names
	$wpdb->query("ALTER TABLE `$ewim_tables->ewim_items` CHANGE `item_recipe_ingredients` `design_details` text;" );
	//endregion

	//region Modify the Minerals to Refined Resources
	$ewim_aItems= $wpdb->get_results( "SELECT * FROM $ewim_tables->ewim_items WHERE category = 'Mineral'", ARRAY_A );//Find customer by location ID
	$ewim_aInsert= array();
	foreach($ewim_aItems as $ewim_aItem){
		$ewim_aInsert= array(
			'category'  =>  'Refined Resource'
		);
		ewim_wpdb_edit('update',$ewim_tables->ewim_items,$ewim_aInsert,$ewim_aItem['id']);
	}
	unset($ewim_aInsert);
	//endregion

	//region Modify the Ores to Raw Resources
	$ewim_aItems= $wpdb->get_results( "SELECT * FROM $ewim_tables->ewim_items WHERE category = 'Ore'", ARRAY_A );//Find customer by location ID
	$ewim_aInsert= array();
	foreach($ewim_aItems as $ewim_aItem){
		$ewim_aInsert= array(
			'category'  =>  'Raw Resource'
		);
		ewim_wpdb_edit('update',$ewim_tables->ewim_items,$ewim_aInsert,$ewim_aItem['id']);
	}
	unset($ewim_aInsert);
	//endregion

	//region Take Blueprint Data and insert into the Product as a comma separated value
	$ewim_aItems= $wpdb->get_results( "SELECT * FROM $ewim_tables->ewim_items WHERE category = 'Blueprint'", ARRAY_A );//Find customer by location ID
	$ewim_aInsert= array();
	foreach($ewim_aItems as $ewim_aItem){
		$ewim_aItemDesignDetails= json_decode($ewim_aItem['design_details'],true);
		$ewim_aItemMeta= json_decode($ewim_aItem['item_meta'],true);

		if(is_array($ewim_aItemDesignDetails)){
			$ewim_productID = $ewim_aItemMeta['Product'];
			foreach ( $ewim_aItemDesignDetails as $ewim_key => $ewim_value ) {
				$ewim_aInsert['design_details'].= $ewim_key . "_" . $ewim_value . ",";
			}
			ewim_wpdb_edit( 'update', $ewim_tables->ewim_items, $ewim_aInsert, $ewim_productID );
		}
		unset($ewim_aItemDesignDetails);
		unset($ewim_aInsert);
	}
	unset($ewim_aInsert);
	//endregion

	//region Take Blueprint Copy Data and change it to comma separated value
	$ewim_aItems= $wpdb->get_results( "SELECT * FROM $ewim_tables->ewim_items WHERE category = 'Blueprint Copy'", ARRAY_A );//Find customer by location ID
	$ewim_aInsert= array();
	foreach($ewim_aItems as $ewim_aItem){
			$ewim_aItemDesignDetails= json_decode($ewim_aItem['design_details'],true);
			if(is_array($ewim_aItemDesignDetails)){
				echo "<h1>Here</h1>";
				echo $ewim_aItemDesignDetails;
				print_r( $ewim_aItemDesignDetails );

				foreach ( $ewim_aItemDesignDetails as $ewim_key => $ewim_value ) {
					$ewim_aInsert['design_details'] .= $ewim_key . "_" . $ewim_value . ",";
				}
				ewim_wpdb_edit( 'update', $ewim_tables->ewim_items, $ewim_aInsert, $ewim_aItem['id'] );
			}
		unset($ewim_aItemDesignDetails);
		unset($ewim_aInsert);
	}
	unset($ewim_aInsert);
	//endregion

	//region Change the Meta Data from EVE named, to Default
	$ewim_aMetaData= $wpdb->get_row( "SELECT * FROM $ewim_tables->ewim_meta_data WHERE meta_key = 'eve_item_actions'", ARRAY_A );//Find customer by location ID
	$ewim_aInsert= array();
	$ewim_aInsert['meta_key']= 'default_item_actions';
	ewim_wpdb_edit('update',$ewim_tables->ewim_meta_data,$ewim_aInsert,$ewim_aMetaData['id']);
	unset($ewim_aInsert);

	$ewim_aMetaData= $wpdb->get_row( "SELECT * FROM $ewim_tables->ewim_meta_data WHERE meta_key = 'eve_item_categories'", ARRAY_A );//Find customer by location ID
	$ewim_aInsert= array();
	$ewim_aInsert['meta_key']= 'default_item_categories';
	ewim_wpdb_edit('update',$ewim_tables->ewim_meta_data,$ewim_aInsert,$ewim_aMetaData['id']);
	unset($ewim_aInsert);
	//endregion

	//endregion

	update_option( 'ewim_db_version', $ewim_dbVersion );
}
//calling the db install only on activation
register_activation_hook( __FILE__, 'ewim_install_database' );

//Calls install if db version has changed.
function ewim_update_db_check_v_1_0_5() {
	$ewim_dbVersion = '1.0.0';//db.table.field
	if ( get_option( 'ewim_db_version' ) < $ewim_dbVersion ) {
		ewim_install_database_v_1_0_5($ewim_dbVersion);
	}
}
//Run the db update check
add_action( 'plugins_loaded', 'ewim_update_db_check_v_1_0_5' );