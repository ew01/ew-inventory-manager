<?php
/**
 * Created by PhpStorm.
 * Author: David
 * Date: 8/3/2018
 * Time: 10:55
 * Name: Form Settings
 * Desc:
 */





global $screen, $pagenow;

if($_POST['ewim_submit'] == 'Y'){
	//Form data sent
	$ewim_displayMessage= "block";

	//Item Detail Pages
	$ewim_itemPage= $_POST['ewim_itemPage'];
	update_option('ewim_itemPage', $ewim_itemPage);

	//Detail Pages
	$ewim_itemListPage= $_POST['ewim_itemListPage'];
	update_option('ewim_itemListPage', $ewim_itemListPage);

	//Post Details
	$ewim_postPage= $_POST['ewim_postPage'];
	update_option('ewim_postPage', $ewim_postPage);



}
else{
	// Normal Page display
	$ewim_displayMessage=    "none";

	//Detail Pages
	$ewim_itemPage=         get_option('ewim_itemPage');

	$ewim_itemListPage=         get_option('ewim_itemListPage');

}

?>

<div class='wrap'>
	<h1><?= __("Inventory Manager Page Settings")?></h1>
	<form name="" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
		<input type="hidden" name="ewim_submit" value="Y">
		<div id="message" class="updated" style="display: <?= $ewim_displayMessage?>">
			<p>
				<?= __("Settings have been updated.")?>
			</p>
		</div>

		<div style="float: left;width: 50%">
            <h3><?=__("Detail Pages")?></h3>
            <h4><?=__("Input Page Names")?></h4>
            <table class="table-form">
                <tbody>
                <!--Item Page-->
                <tr>
                    <th scope="row" align="left">
                        <label for="ewim_itemPage" style="width:200px;"><?= __("Item Details: ")?></label>
                    </th>
                    <td>
                        <input name="ewim_itemPage" type="text" value="<?= $ewim_itemPage;?>" /><?= __("Example: Item Details")?>
                    </td>
                </tr>

                <!--Post Page-->
                <tr>
                    <th scope="row" align="left">
                        <label for="ewim_postPage" style="width:200px;"><?= __("Post Details: ")?></label>
                    </th>
                    <td>
                        <input name="ewim_postPage" type="text" value="<?= $ewim_postPage;?>" /><?= __("Example: Post Details")?>
                    </td>
                </tr>

                <!--Item List Page-->
                <tr>
                    <th scope="row" align="left">
                        <label for="ewim_itemListPage" style="width:200px;"><?= __("Item List: ")?></label>
                    </th>
                    <td>
                        <input name="ewim_itemListPage" type="text" value="<?= $ewim_itemListPage;?>" /><?= __("Example: Item List")?>
                    </td>
                </tr>

                </tbody>
            </table>
		</div>



		<br clear="all"/>

		<p class="submit"><input class="button-primary" type="submit" name="Submit" value="<?= __("Update Settings")?>" /> </p>

	</form>
</div>