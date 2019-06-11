<?php
/**
 * Created by PhpStorm.
 * Author: David
 * Date: 3/8/2019
 * Time: 20:50
 * Name:
 * Desc:
 */





/**
 * Name: EWIM WPDB Edit
 * Desc: Uses $WPDB to write to the database. Can be called anywhere in the plugin
 *
 * @param $ewim_action
 * @param $ewim_table
 * @param $ewim_aInsert
 * @param null $ewim_recordID
 *
 * @return array
 */
function ewim_wpdb_edit($ewim_action, $ewim_table, $ewim_aInsert, $ewim_recordID= NULL){
	//Global Variables and Classes
	global $wpdb;

	//Switch to the requested action
	switch ($ewim_action){
		case "insert":
			$ewim_wpdbEditResult= $wpdb->insert(
				$ewim_table,
				$ewim_aInsert
			);


			break;
		case "update":
			$ewim_wpdbEditResult= $wpdb->update(
				$ewim_table,
				$ewim_aInsert,
				array(
					'id'    => $ewim_recordID//Customer ID in the form
				)
			);


			break;
		default:
			$ewim_aReturn['error']= 'Error';
			$ewim_aReturn['errorMessage']= 'No Action';
			return $ewim_aReturn;
			break;
	}

	if($ewim_wpdbEditResult === false){
		//It did not Work
		$ewim_aReturn['error']= 'Error';
		$ewim_aReturn['errorMessage']= $wpdb->last_error;
		return $ewim_aReturn;


		/*
			echo "<h1>Insert</h1>";
			$wpdb->show_errors();
			$wpdb->print_error();
			echo "<p>Table: $ewim_table</p>";
			echo "<pre>";
			print_r($ewim_aInsert);
			echo "</pre>";
			exit;
		*/
	}
	else{
		$ewim_aReturn['error']= 'No';
		if($ewim_action == 'insert'){
			$ewim_aReturn['record_id']= $wpdb->insert_id;
		}
		return $ewim_aReturn;
	}
}