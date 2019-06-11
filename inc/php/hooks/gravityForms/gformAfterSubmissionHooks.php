<?php
/**
 * Created by PhpStorm.
 * Author: David
 * Date: 3/8/2019
 * Time: 17:03
 * Name:
 * Desc:
 */





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