<?php
/**
 * Created by PhpStorm.
 * Author: David
 * Date: 8/27/2019
 * Time: 15:54
 * Name:
 * Desc:
 */





/**
 * Name: EWIM Install Database Version 1.2.0
 * Desc: Runs on update of plugin
 * @param $ewim_dbVersion
 */
function ewim_install_database_v_1_2_0($ewim_dbVersion){
	//region Global Variables, Local Variables, Classes
	global $wpdb;
	$ewim_tables = new ewim_tables();
	$charset_collate = $wpdb->get_charset_collate();
	//endregion

	//region Make Alterations to tables if needed, must be done before dbDelta file is included.
	$wpdb->query("ALTER TABLE `$ewim_tables->ewim_ledger` CHANGE `total_cost` `transaction_currency_amount` text;" );
	$wpdb->query("ALTER TABLE `$ewim_tables->ewim_ledger` CHANGE `difference` `transaction_credit_debit` text;" );
	$wpdb->query("ALTER TABLE `$ewim_tables->ewim_ledger` CHANGE `item_amount` `transaction_item_amount` text;" );
	//endregion

	//region Include WP file that lets us use DB Delta
	require_once(ABSPATH.'wp-admin/includes/upgrade.php');
	//endregion

	//region Change Design Details to Json
	$ewim_aItems= $wpdb->get_results( "SELECT * FROM $ewim_tables->ewim_items WHERE design_details IS NOT NULL ", ARRAY_A );
	$ewim_aInsert= array();
	foreach($ewim_aItems as $ewim_aItem) {
		json_decode( $ewim_aItem['design_details'] );
		if (json_last_error() != JSON_ERROR_NONE) {
			$ewim_aDesignItems = explode( ',', $ewim_aItem['design_details'] );

			foreach($ewim_aDesignItems as $ewim_aDesignItem){
				$ewim_aDesignItemTwo                                    = explode( "_", $ewim_aDesignItem );
				//$ewim_aDesignDetails[$ewim_aDesignItemTwo[0]]['id']     = $ewim_aDesignItemTwo[1];
				$ewim_aDesignDetails[$ewim_aDesignItemTwo[1]]['amount'] = 0;
			}

			$ewim_aInsert['design_details'] = json_encode( $ewim_aDesignDetails );

			ewim_wpdb_edit( 'update', $ewim_tables->ewim_items, $ewim_aInsert, $ewim_aItem['id'] );
		}
	}
	unset($ewim_aInsert);
	//endregion

	//region Change Default Item actions to Json
	$ewim_aItemActionMeta= $wpdb->get_row( "SELECT * FROM $ewim_tables->ewim_meta_data WHERE meta_key = 'default_item_actions' ", ARRAY_A );
	$ewim_aMetaData= array(
		array(
			'text'  => 'Buy',
			'value' => 'buy'
		),
		array(
			'text'  => 'Sell',
			'value' => 'sell'
		),
		array(
			'text'  => 'Harvest',
			'value' => 'harvest'
		),
		array(
			'text'  => 'Process',
			'value' => 'process'
		),
		array(
			'text'  => 'Manufacture',
			'value' => 'manufacture'
		),
		array(
			'text'  => 'Copy Design',
			'value' => 'copy_design'
		),
		array(
			'text'  => 'Write Off',
			'value' => 'write_off'
		)
	);
	$ewim_jsMetaData= json_encode($ewim_aMetaData);
	$ewim_aInsert= array(
		'meta_value'        => $ewim_jsMetaData,
		'meta_value_type'   => 'json'
	);
	ewim_wpdb_edit( 'update', $ewim_tables->ewim_meta_data, $ewim_aInsert, $ewim_aItemActionMeta['id'] );

	unset($ewim_aInsert);
	//endregion

	//region Change Default Item Categories to Json
	//Refined Resource,Raw Resource,Product,Design Copy,Component
	$ewim_aItemCategoryMeta= $wpdb->get_row( "SELECT * FROM $ewim_tables->ewim_meta_data WHERE meta_key = 'default_item_categories' ", ARRAY_A );
	$ewim_aMetaData= array(
		'Refined Resource',
		'Raw Resource',
		'Product',
		'Design Copy',
		'Component'
	);
	$ewim_jsMetaData= json_encode($ewim_aMetaData);
	$ewim_aInsert= array(
		'meta_value'        => $ewim_jsMetaData,
		'meta_value_type'   => 'json'
	);
	ewim_wpdb_edit( 'update', $ewim_tables->ewim_meta_data, $ewim_aInsert, $ewim_aItemCategoryMeta['id'] );

	unset($ewim_aInsert);
	//endregion

	//region Insert Data for Meta Data Table; Item Actions

	//region Product Actions
	$ewim_aProductActions= array(
		array(
			'text'  => 'Buy',
			'value' => 'buy'
		),
		array(
			'text'  => 'Sell',
			'value' => 'sell'
		),
		array(
			'text'  => 'Manufacture',
			'value' => 'manufacture'
		),
		array(
			'text'  => 'Copy Design',
			'value' => 'copy_design'
		),
		array(
			'text'  => 'Write Off',
			'value' => 'write_off'
		),
		/*
		array(
			'text'  => 'Post',
			'value' => 'Post'
		),*/
	);
	$ewim_jsProductActions= json_encode($ewim_aProductActions);
	$ewim_aProductActionsMetaInsert= array(
		'meta_key'          => 'product_actions',
		'meta_value'        => $ewim_jsProductActions,
		'meta_value_type'   => 'json'
	);
	/*$wpdb->insert(
		$ewim_tables->ewim_meta_data,
		$ewim_aProductActionsMetaInsert
	);*/
	//endregion

	//region Refined Resource
	$ewim_aRefinedResourceActions= array(
		array(
			'text'  => 'Buy',
			'value' => 'buy'
		),
		array(
			'text'  => 'Sell',
			'value' => 'sell'
		),
		array(
			'text'  => 'Write Off',
			'value' => 'write_off'
		),
		/*array(
			'text'  => 'Post',
			'value' => 'Post'
		),*/
	);
	$ewim_jsRefinedResourceActions= json_encode($ewim_aRefinedResourceActions);
	$ewim_aRefinedResourceActionsMetaInsert= array(
		'meta_key'          => 'refined_resource_actions',
		'meta_value'        => $ewim_jsRefinedResourceActions,
		'meta_value_type'   => 'json'
	);
	/*$wpdb->insert(
		$ewim_tables->ewim_meta_data,
		$ewim_aRefinedResourceActionsMetaInsert
	);*/
	//endregion

	//region Raw Resource
	$ewim_aRawResourceActions= array(
		array(
			'text'  => 'Harvest',
			'value' => 'harvest'
		),
		array(
			'text'  => 'Process',
			'value' => 'process'
		),
		array(
			'text'  => 'Buy',
			'value' => 'buy'
		),
		array(
			'text'  => 'Sell',
			'value' => 'sell'
		),
		array(
			'text'  => 'Write Off',
			'value' => 'write_off'
		),
		/*array(
			'text'  => 'Post',
			'value' => 'Post'
		),*/
	);
	$ewim_jsRawResourceActions= json_encode($ewim_aRawResourceActions);
	$ewim_aRawResourceActionsMetaInsert= array(
		'meta_key'          => 'raw_resource_actions',
		'meta_value'        => $ewim_jsRawResourceActions,
		'meta_value_type'   => 'json'
	);
	/*$wpdb->insert(
		$ewim_tables->ewim_meta_data,
		$ewim_aRawResourceActionsMetaInsert
	);*/
	//endregion

	//region Design Copy
	$ewim_aDesignCopyActions= array(
		array(
			'text'  => 'Manufacture',
			'value' => 'manufacture'
		),
		array(
			'text'  => 'Buy',
			'value' => 'buy'
		),
		array(
			'text'  => 'Sell',
			'value' => 'sell'
		),
		array(
			'text'  => 'Write Off',
			'value' => 'write_off'
		),
		/*array(
			'text'  => 'Post',
			'value' => 'Post'
		),*/
	);
	$ewim_jsDesignCopyActions= json_encode($ewim_aDesignCopyActions);
	$ewim_aDesignCopyActionsMetaInsert= array(
		'meta_key'          => 'design_copy_actions',
		'meta_value'        => $ewim_jsDesignCopyActions,
		'meta_value_type'   => 'json'
	);
	/*$wpdb->insert(
		$ewim_tables->ewim_meta_data,
		$ewim_aDesignCopyActionsMetaInsert
	);*/
	//endregion

	//region Component
	$ewim_aComponentActions= array(
		array(
			'text'  => 'Buy',
			'value' => 'buy'
		),
		array(
			'text'  => 'Sell',
			'value' => 'sell'
		),
		array(
			'text'  => 'Manufacture',
			'value' => 'manufacture'
		),
		array(
			'text'  => 'Copy Design',
			'value' => 'copy_design'
		),
		array(
			'text'  => 'Write Off',
			'value' => 'write_off'
		),
		/*array(
			'text'  => 'Post',
			'value' => 'Post'
		),*/
	);
	$ewim_jsComponentActions= json_encode($ewim_aComponentActions);
	$ewim_aComponentActionsMetaInsert= array(
		'meta_key'          => 'component_actions',
		'meta_value'        => $ewim_jsComponentActions,
		'meta_value_type'   => 'json'
	);
	/*$wpdb->insert(
		$ewim_tables->ewim_meta_data,
		$ewim_aComponentActionsMetaInsert
	);*/
	//endregion

	//endregion

	update_option( 'ewim_db_version', $ewim_dbVersion );
}


//region Calls install if db version has changed.
function ewim_update_db_check_v_1_2_0() {
	$ewim_dbVersion = '1.2.0';//major.minor.simple
	if ( get_option( 'ewim_db_version' ) < $ewim_dbVersion ) {
		ewim_install_database_v_1_2_0($ewim_dbVersion);
	}
}
//Run the db update check
add_action( 'plugins_loaded', 'ewim_update_db_check_v_1_2_0' );
//endregion