<?php
/**
 * Created by PhpStorm.
 * Author: David
 * Date: 7/11/2019
 * Time: 11:41
 * Name: Debug Settings
 * Desc: Toggle error messages and array print outs.
 */





global $screen, $pagenow;

//region Process the Form
if($_POST['ewim_submit'] == 'Y'){
	//Form data sent
	$ewim_displayMessage= "block";

	//region Database
	$ewim_wpdbSelect= $_POST['ewim_wpdbSelect'];
	update_option('ewim_wpdbSelect', $ewim_wpdbSelect);

	$ewim_wpdbEdit= $_POST['ewim_wpdbEdit'];
	update_option('ewim_wpdbEdit', $ewim_wpdbEdit);

	$ewim_wpdbError= $_POST['ewim_wpdbError'];
	update_option('ewim_wpdbError', $ewim_wpdbError);
    //endregion

    //region Forms

	$ewim_CreateFieldsFormStart= $_POST['ewim_CreateFieldsFormStart'];
	update_option('ewim_CreateFieldsFormStart', $ewim_CreateFieldsFormStart);

	$ewim_formProcessEntry= $_POST['ewim_formProcessEntry'];
	update_option('ewim_formProcessEntry', $ewim_formProcessEntry);

	$ewim_formPopulateRecordStart= $_POST['ewim_formPopulateRecordStart'];
	update_option('ewim_formPopulateRecordStart', $ewim_formPopulateRecordStart);

	$ewim_formPopulateRecordEnd= $_POST['ewim_formPopulateRecordEnd'];
	update_option('ewim_formPopulateRecordEnd', $ewim_formPopulateRecordEnd);






	$ewim_formEntry= $_POST['ewim_formEntry'];
	update_option('ewim_formEntry', $ewim_formEntry);

	$ewim_formExit= $_POST['ewim_formExit'];
	update_option('ewim_formExit', $ewim_formExit);

	$ewim_wpdbIngredientEdit= $_POST['ewim_wpdbIngredientEdit'];
	update_option('ewim_wpdbIngredientEdit', $ewim_wpdbIngredientEdit);
	//endregion
}
//endregion

else{
	// Normal Page display
	$ewim_displayMessage=    "none";
}

//region Database
if(get_option('ewim_wpdbSelect') == 1){
	$ewim_wpdbSelectOn= 'checked';
}
else{
	$ewim_wpdbSelectOff= 'checked';
}

if(get_option('ewim_wpdbEdit') == 1){
	$ewim_wpdbEditOn= 'checked';
}
else{
	$ewim_wpdbEditOff= 'checked';
}

if(get_option('ewim_wpdbError') == 1){
	$ewim_wpdbErrorOn= 'checked';
}
else{
	$ewim_wpdbErrorOff= 'checked';
}
//endregion

//region Forms
if(get_option('ewim_CreateFieldsFormStart') == 1){
	$ewim_CreateFieldsFormStartOn= 'checked';
}
else{
	$ewim_CreateFieldsFormStartOff= 'checked';
}

if(get_option('ewim_formEntry') == 1){
	$ewim_formEntryOn= 'checked';
}
else{
	$ewim_formEntryOff= 'checked';
}

if(get_option('ewim_formExit') == 1){
	$ewim_formExitOn= 'checked';
}
else{
	$ewim_formExitOff= 'checked';
}



if(get_option('ewim_formProcessEntry') == 1){
	$ewim_formProcessEntryOn= 'checked';
}
else{
	$ewim_formProcessEntryOff= 'checked';
}

if(get_option('ewim_formPopulateRecordStart') == 1){
	$ewim_formPopulateRecordStartOn= 'checked';
}
else{
	$ewim_formPopulateRecordStartOff= 'checked';
}

if(get_option('ewim_formPopulateRecordEnd') == 1){
	$ewim_formPopulateRecordEndOn= 'checked';
}
else{
	$ewim_formPopulateRecordEndOff= 'checked';
}

//endregion

?>

<div class='wrap'>
	<h1><?= __("Inventory Manager Debug Settings")?></h1>
	<form name="" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
		<input type="hidden" name="ewim_submit" value="Y">

		<div id="message" class="updated" style="display: <?= $ewim_displayMessage?>">
			<p>
				<?= __("Settings have been updated.")?>
			</p>
		</div>

		<div style="float: left;width: 50%">
			<h3><?=__("Form IDs")?></h3>
			<table class="table-form">
				<tbody>
                    <!--WPDB Select-->
                    <tr>
                        <th scope="row" align="left">
                            <label for="ewim_wpdbSelect" style="width:200px;"><?= __("WPDB Select Messages: ")?></label>
                        </th>
                        <td>
                            <input id="ewim_wpdbSelect" name="ewim_wpdbSelect" type="radio" value="1" <?= $ewim_wpdbSelectOn; ?> /><?= __("On")?>
                            &nbsp;
                            <input id="ewim_wpdbSelect" name="ewim_wpdbSelect" type="radio" value="0" <?= $ewim_wpdbSelectOff; ?> /><?= __("Off")?>
                        </td>
                    </tr>
                    <!--WPDB Edit-->
                    <tr>
                        <th scope="row" align="left">
                            <label for="ewim_wpdbEdit" style="width:200px;"><?= __("WPDB Edit Messages: ")?></label>
                        </th>
                        <td>
                            <input id="ewim_wpdbEdit" name="ewim_wpdbEdit" type="radio" value="1" <?= $ewim_wpdbEditOn; ?> /><?= __("On")?>
                            &nbsp;
                            <input id="ewim_wpdbEdit" name="ewim_wpdbEdit" type="radio" value="0" <?= $ewim_wpdbEditOff; ?> /><?= __("Off")?>
                        </td>
                    </tr>
                    <!--WPDB Error-->
                    <tr>
                        <th scope="row" align="left">
                            <label for="ewim_wpdbError" style="width:200px;"><?= __("WPDB Error Messages: ")?></label>
                        </th>
                        <td>
                            <input id="ewim_wpdbError" name="ewim_wpdbError" type="radio" value="1" <?= $ewim_wpdbErrorOn; ?> /><?= __("On")?>
                            &nbsp;
                            <input id="ewim_wpdbError" name="ewim_wpdbError" type="radio" value="0" <?= $ewim_wpdbErrorOff; ?> /><?= __("Off")?>
                        </td>
                    </tr>

                    <!--Create Fields Form Start-->
                    <tr>
                        <th scope="row" align="left">
                            <label for="ewim_CreateFieldsFormStart" style="width:200px;"><?= __("Create Fields Form Start: ")?></label>
                        </th>
                        <td>
                            <input id="ewim_CreateFieldsFormStart" name="ewim_CreateFieldsFormStart" type="radio" value="1" <?= $ewim_CreateFieldsFormStartOn; ?> /><?= __("On")?>
                            &nbsp;
                            <input id="ewim_CreateFieldsFormStart" name="ewim_CreateFieldsFormStart" type="radio" value="0" <?= $ewim_CreateFieldsFormStartOff; ?> /><?= __("Off")?>
                        </td>
                    </tr>

                    <!--Form Entry-->
					<tr>
						<th scope="row" align="left">
							<label for="ewim_formEntry" style="width:200px;"><?= __("Form Entry Array: ")?></label>
						</th>
						<td>
							<input id="ewim_formEntry" name="ewim_formEntry" type="radio" value="1" <?= $ewim_formEntryOn; ?> /><?= __("On")?>
							&nbsp;
							<input id="ewim_formEntry" name="ewim_formEntry" type="radio" value="0" <?= $ewim_formEntryOff; ?> /><?= __("Off")?>
						</td>
					</tr>
					<!--Form Exit-->
					<tr>
						<th scope="row" align="left">
							<label for="ewim_formExit" style="width:200px;"><?= __("Form Exit Array: ")?></label>
						</th>
						<td>
							<input id="ewim_formExit" name="ewim_formExit" type="radio" value="1" <?= $ewim_formExitOn; ?> /><?= __("On")?>
							&nbsp;
							<input id="ewim_formExit" name="ewim_formExit" type="radio" value="0" <?= $ewim_formExitOff; ?> /><?= __("Off")?>
						</td>
					</tr>

					<!--Form Entry Process-->
					<tr>
						<th scope="row" align="left">
							<label for="ewim_formProcessEntry" style="width:200px;"><?= __("Form Process Entry Array: ")?></label>
						</th>
						<td>
							<input id="ewim_formProcessEntry" name="ewim_formProcessEntry" type="radio" value="1" <?= $ewim_formProcessEntryOn; ?> /><?= __("On")?>
							&nbsp;
							<input id="ewim_formProcessEntry" name="ewim_formProcessEntry" type="radio" value="0" <?= $ewim_formProcessEntryOff; ?> /><?= __("Off")?>
						</td>
					</tr>
					<!--Form Populate Record Start-->
					<tr>
						<th scope="row" align="left">
							<label for="ewim_formPopulateRecordStart" style="width:200px;"><?= __("Form Populate Record Start Array: ")?></label>
						</th>
						<td>
							<input id="ewim_formPopulateRecordStart" name="ewim_formPopulateRecordStart" type="radio" value="1" <?= $ewim_formPopulateRecordStartOn; ?> /><?= __("On")?>
							&nbsp;
							<input id="ewim_formPopulateRecordStart" name="ewim_formPopulateRecordStart" type="radio" value="0" <?= $ewim_formPopulateRecordStartOff; ?> /><?= __("Off")?>
						</td>
					</tr>
					<!--Form Populate Record End-->
					<tr>
						<th scope="row" align="left">
							<label for="ewim_formPopulateRecordEnd" style="width:200px;"><?= __("Form Populate Record End Array: ")?></label>
						</th>
						<td>
							<input id="ewim_formPopulateRecordEnd" name="ewim_formPopulateRecordEnd" type="radio" value="1" <?= $ewim_formPopulateRecordEndOn; ?> /><?= __("On")?>
							&nbsp;
							<input id="ewim_formPopulateRecordEnd" name="ewim_formPopulateRecordEnd" type="radio" value="0" <?= $ewim_formPopulateRecordEndOff; ?> /><?= __("Off")?>
						</td>
					</tr>

				</tbody>
			</table>
		</div>

		<br clear="all"/>

		<p class="submit"><input class="button-primary" type="submit" name="Submit" value="<?= __("Update Settings")?>" /> </p>

	</form>
</div>