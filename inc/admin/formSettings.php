<?php
/**
 * Created by PhpStorm.
 * Author: David
 * Date: 8/3/2018
 * Time: 10:55
 * Name: Form Settings
 * Desc:
 */




//todo update this page to use new systems
//todo I forgot what I meant by new systems, was it something to do with get options being in classes, does not seem like that is really needed for this page

global $screen, $pagenow;

if($_POST['ewim_submit'] == 'Y'){
	//Form data sent
	$ewim_displayMessage= "block";

	//region Form IDs
	$ewim_itemFormID= $_POST['ewim_itemFormID'];
	update_option('ewim_itemFormID', $ewim_itemFormID);

	$ewim_itemTransactionFormID= $_POST['ewim_itemTransactionFormID'];
	update_option('ewim_itemTransactionFormID', $ewim_itemTransactionFormID);

	$ewim_postedTransactionFormID= $_POST['ewim_postedTransactionFormID'];
	update_option('ewim_postedTransactionFormID', $ewim_postedTransactionFormID);

	$ewim_recipeFormID= $_POST['ewim_recipeFormID'];
	update_option('ewim_recipeFormID', $ewim_recipeFormID);

	$ewim_gameFormID= $_POST['ewim_gameFormID'];
	update_option('ewim_gameFormID', $ewim_gameFormID);
    //endregion

	//region Form Pages
	$ewim_itemFormPage= $_POST['ewim_itemFormPage'];
	update_option('ewim_itemFormPage', $ewim_itemFormPage);

	$ewim_sellPostedFormPage= $_POST['ewim_sellPostedFormPage'];
	update_option('ewim_sellPostedFormPage', $ewim_sellPostedFormPage);

	$ewim_gameFormPage= $_POST['ewim_gameFormPage'];
	update_option('ewim_gameFormPage', $ewim_gameFormPage);
	//endregion
}
else{
	// Normal Page display
	$ewim_displayMessage=    "none";

	//region Form IDs
	$ewim_itemFormID=                   get_option('ewim_itemFormID');
	$ewim_itemTransactionFormID=        get_option('ewim_itemTransactionFormID');
	$ewim_postedTransactionFormID=      get_option('ewim_postedTransactionFormID');

	$ewim_gameFormPage=                 get_option('ewim_gameFormPage');
	$ewim_gameFormID=                   get_option('ewim_gameFormID');
	//endregion

    //region Form Pages
	$ewim_itemFormPage=                 get_option('ewim_itemFormPage');
	$ewim_gameFormPage=                 get_option('ewim_gameFormPage');
	//endregion

}

?>

<div class='wrap'>
	<h1><?= __("Inventory Manager Form Settings")?></h1>
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
                <!--Item Form-->
                <tr>
                    <th scope="row" align="left">
                        <label for="ewim_itemFormID" style="width:200px;"><?= __("Item Form ID: ")?></label>
                    </th>
                    <td>
                        <input id="ewim_itemFormID" name="ewim_itemFormID" type="text" value="<?= $ewim_itemFormID;?>" /><?= __("Example: 5")?>
                    </td>
                </tr>
                <!--Item Transaction Form-->
                <tr>
                    <th scope="row" align="left">
                        <label for="ewim_itemTransactionFormID" style="width:200px;"><?= __("Item Transaction Form ID: ")?></label>
                    </th>
                    <td>
                        <input id="ewim_itemTransactionFormID" name="ewim_itemTransactionFormID" type="text" value="<?= $ewim_itemTransactionFormID;?>" /><?= __("Example: 6")?>
                    </td>
                </tr>
                <!--Posted Transaction Form-->
                <tr>
                    <th scope="row" align="left">
                        <label for="ewim_postedTransactionFormID" style="width:200px;"><?= __("Posted Transaction Form ID: ")?></label>
                    </th>
                    <td>
                        <input id="ewim_postedTransactionFormID" name="ewim_postedTransactionFormID" type="text" value="<?= $ewim_postedTransactionFormID;?>" /><?= __("Example: 9")?>
                    </td>
                </tr>
                <!--Game Form-->
                <tr>
                    <th scope="row" align="left">
                        <label for="ewim_gameFormID" style="width:200px;"><?= __("Remove Form ID: ")?></label>
                    </th>
                    <td>
                        <input id="ewim_gameFormID" name="ewim_gameFormID" type="text" value="<?= $ewim_gameFormID;?>" /><?= __("Example: 1")?>
                    </td>
                </tr>
                </tbody>
            </table>

			<h3><?=__("Form Pages")?></h3>
            <h4><?=__("Input Page Names")?></h4>
			<table class="table-form">
				<tbody>
				<!--Item Form-->
				<tr>
					<th scope="row" align="left">
						<label for="ewim_itemFormPage" style="width:200px;"><?= __("Item Form Page: ")?></label>
					</th>
					<td>
						<input id="ewim_itemFormPage" name="ewim_itemFormPage" type="text" value="<?= $ewim_itemFormPage;?>" /><?= __("Example: Item Form")?>
					</td>
				</tr>
                <!--Sell Form-->
                <tr>
                    <th scope="row" align="left">
                        <label for="ewim_sellPostedFormPage" style="width:200px;"><?= __("Sell Posted Page: ")?></label>
                    </th>
                    <td>
                        <input id="ewim_sellPostedFormPage" name="ewim_sellPostedFormPage" type="text" value="<?= $ewim_sellPostedFormPage;?>" /><?= __("Example: Sell Posted")?>
                    </td>
                </tr>
                <!--Game Form-->
                <tr>
                    <th scope="row" align="left">
                        <label for="ewim_gameFormPage" style="width:200px;"><?= __("Item Form Page: ")?></label>
                    </th>
                    <td>
                        <input id="ewim_gameFormPage" name="ewim_gameFormPage" type="text" value="<?= $ewim_gameFormPage;?>" /><?= __("Example: Game Form")?>
                    </td>
                </tr>
				</tbody>
			</table>


		</div>

		<br clear="all"/>

		<p class="submit"><input class="button-primary" type="submit" name="Submit" value="<?= __("Update Settings")?>" /> </p>

	</form>
</div>