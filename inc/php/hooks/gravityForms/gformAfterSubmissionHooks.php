<?php
/**
 * Created by PhpStorm.
 * Author: David
 * Date: 3/8/2019
 * Time: 17:03
 * Name:
 * Desc:
 */
//todo create a error log table to submit to
//todo error logging table, and friendly message



/**
 *  After Submission Hooks
 */
/**
 * Name: Remove Form Entry
 * Desc: Remove the entry after it has been submitted
 */
add_action( "gform_after_submission_$ewim_itemFormID", 'remove_form_entry' );
add_action( "gform_after_submission_$ewim_acquireFormID", 'remove_form_entry' );
add_action( "gform_after_submission_$ewim_removeFormID", 'remove_form_entry' );
function remove_form_entry( $entry ) {
	GFAPI::delete_entry( $entry['id'] );
}

/**
 * Name: Process Donation
 */
add_action( 'gform_paypal_fulfillment', 'ewim_process_donation', 10, 4 );
function ewim_process_donation($ewim_entry, $ewim_feed, $ewim_transaction_id, $ewim_amount){
	$ewim_current_user= wp_get_current_user();
	$ewim_userID= $ewim_current_user->ID;
	$ewim_userID= rgar($ewim_entry, '4');
	$ewim_currentMax= get_user_meta($ewim_userID, 'max_games',true);
	$ewim_newMax= $ewim_currentMax + $ewim_amount;
	update_user_meta($ewim_userID,'max_games',$ewim_newMax);
	//update_user_meta(1,'test_field',$ewim_entry);
}