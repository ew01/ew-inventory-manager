<?php
/**
 * Created by PhpStorm.
 * Author: David
 * Date: 3/9/2019
 * Time: 19:53
 * Name:
 * Desc:
 */





//region Insert Data
function ewim_insert_data_v_two($ewim_dbDataVersion){
	//Global Variables and Classes
	global $wpdb;
	$ewim_tables= new ewim_tables();

	//region Insert Data for Meta Data Table
	/*
	$wpdb->insert(
		$ewim_tables->ewim_meta_data,
		array(
			'meta_key'      => 'acquisitionMethods',
			'meta_value'    => 'Harvest,Mine,Craft,Trade,Buy,Sell'

		)
	);
	*/
	//endregion

	//region
	/*
	$wpdb->insert(
		$ewim_tables->ewim_meta_data,
		array(
			'meta_key'      => 'gameSystems',
			'meta_values'   => 'DnD, EVE'
		)
	);
	*/
	//endregion

	//region
	$ewim_aEveOres= array(
		"Arkonor",
		"Azure Plagioclase",
		"Bistot",
		"Bright Spodumain",
		"Concentrated Veldspar",
		"Condensed Scordite",
		"Crimson Arkonor",
		"Crokite",
		"Crystalline Crokite",
		"Dark Ochre",
		"Dense Veldspar",
		"Fiery Kernite",
		"Glazed Hedbergite",
		"Gleaming Spodumain",
		"Gneiss",
		"Golden Omber",
		"Hedbergite",
		"Hemorphite",
		"Iridescent Gneiss",
		"Jaspet",
		"Kernite",
		"Luminous Kernite",
		"Magma Mercoxit",
		"Massive Scordite",
		"Mercoxit",
		"Monoclinic Bistot",
		"Obsidian Ochre",
		"Omber",
		"Onyx Ochre",
		"Plagioclase",
		"Prime Arkonor",
		"Prismatic Gneiss",
		"Pristine Jaspet",
		"Pure Jaspet",
		"Pyroxeres",
		"Radiant Hemorphite",
		"Rich Plagioclase",
		"Scordite",
		"Sharp Crokite",
		"Silvery Omber",
		"Solid Pyroxeres",
		"Spodumain",
		"Triclinic Bistot",
		"Veldspar",
		"Viscous Pyroxeres",
		"Vitreous Mercoxit",
		"Vitric Hedbergite",
		"Vivid Hemorphite"
	);
	foreach($ewim_aEveOres as $ewim_eveOre){
		$wpdb->insert(
			$ewim_tables->ewim_items,
			array(
				'user_id'   => 1,
				'game_id'   => 7,
				'item_name' => $ewim_eveOre,
				'item_recipe'   => 'No',
			)
		);
	}
	//endregion

	update_option( 'ewim_db_data_version', $ewim_dbDataVersion );
}
//endregion

//region Calls the insert data function if the Data Version is too old
function ewim_insert_data_v_two_check() {
	$ewim_dbDataVersion = '1.0.5';//db.table.field
	if ( get_option( 'ewim_db_data_version' ) != $ewim_dbDataVersion ) {
		ewim_insert_data_v_two($ewim_dbDataVersion);
	}
}
//Run the db update check
add_action( 'plugins_loaded', 'ewim_insert_data_v_two_check' );
//endregion