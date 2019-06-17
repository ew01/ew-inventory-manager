<?php
/**
 * Created by PhpStorm.
 * Author: David
 * Date: 6/13/2019
 * Time: 08:56
 * Name:
 * Desc:
 */





//add_action( 'show_user_profile', 'extra_user_profile_fields' );//User Level
add_action( 'edit_user_profile', 'extra_user_profile_fields' );//Admin Level

function extra_user_profile_fields( $user ) {
	?>
	<h3><?php _e("Gaming Inventory Data", "blank"); ?></h3>

	<table class="form-table">
		<!--Max Games Allowed-->
		<tr>
			<th><label for="max_games"><?php _e("Max Games Allowed"); ?></label></th>
			<td>
				<input id="max_games" name="max_games" type="text" value="<?php echo esc_attr( get_the_author_meta( 'max_games', $user->ID ) ); ?>" class="regular-text" /><br />
				<!--<span class="description">< ?php _e("Please enter your address."); ?></span>-->
			</td>
		</tr>

		<!--
		<tr>
			<th><label for="city">< ?php _e("City"); ?></label></th>
			<td>
				<input type="text" name="city" id="city" value="< ?php echo esc_attr( get_the_author_meta( 'city', $user->ID ) ); ?>" class="regular-text" /><br />
				<span class="description">< ?php _e("Please enter your city."); ?></span>
			</td>
		</tr>
		<tr>
			<th><label for="postalcode">< ?php _e("Postal Code"); ?></label></th>
			<td>
				<input type="text" name="postalcode" id="postalcode" value="< ?php echo esc_attr( get_the_author_meta( 'postalcode', $user->ID ) ); ?>" class="regular-text" /><br />
				<span class="description">< ?php _e("Please enter your postal code."); ?></span>
			</td>
		</tr>
		-->

	</table>
<?php
}

//add_action( 'personal_options_update', 'save_extra_user_profile_fields' );//User Level
add_action( 'edit_user_profile_update', 'save_extra_user_profile_fields' );//Admin Level

function save_extra_user_profile_fields( $user_id ) {
	if ( !current_user_can( 'edit_user', $user_id ) ) {
		return false;
	}
	update_user_meta( $user_id, 'max_games', $_POST['max_games'] );

	/*
	update_user_meta( $user_id, 'city', $_POST['city'] );
	update_user_meta( $user_id, 'postalcode', $_POST['postalcode'] );
	*/
	return '';
}