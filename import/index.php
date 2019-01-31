<?php

$intGroupID = check_var('intGroupID');
$strGroupImport = check_var('strGroupImport');

$query_xtra = "";

if(!IS_EDITOR)
{
	$query_xtra .= " AND post_author = '".get_current_user_id()."'";
}

$obj_group = new mf_group();

if(isset($_POST['btnGroupImport']) && $strGroupImport != '' && wp_verify_nonce($_POST['_wpnonce_group_import'], 'group_import_'.$intGroupID))
{
	$rows = 0;

	$arr_import = array_map('trim', explode("\n", $strGroupImport));

	$wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND ID = '%d'".$query_xtra." LIMIT 0, 1", $obj_group->post_type, $intGroupID));

	if($wpdb->num_rows > 0)
	{
		foreach($arr_import as $address_row)
		{
			$is_email = preg_match("/\@/", $address_row);

			$query_where = "";

			if($is_email)
			{
				$address_row = check_var($address_row, 'email', false);

				$query_where = "addressEmail = '%s'";
			}

			else
			{
				$address_row = check_var($address_row, 'char', false);

				$query_where = "addressBirthDate = '%s'";
			}

			if($address_row != '')
			{
				$intAddressID = $wpdb->get_var($wpdb->prepare("SELECT addressID FROM ".get_address_table_prefix()."address WHERE ".$query_where." LIMIT 0, 1", $address_row));

				if($intAddressID > 0)
				{
					$wpdb->get_results($wpdb->prepare("SELECT addressID FROM ".$wpdb->prefix."address2group WHERE addressID = '%d' AND groupID = '%d' LIMIT 0, 1", $intAddressID, $intGroupID));

					if($wpdb->num_rows == 0)
					{
						$obj_group->add_address(array('address_id' => $intAddressID, 'group_id' => $intGroupID));
					}

					$rows++;
				}

				else if($is_email)
				{
					$obj_address = new mf_address();
					$intAddressID = $obj_address->insert(array('email' => $address_row));

					if($intAddressID > 0)
					{
						$obj_group->add_address(array('address_id' => $intAddressID, 'group_id' => $intGroupID));

						$rows++;
					}
				}

				else
				{
					$error_text = __("An address with that information did not exist", 'lang_group')." (".$address_row.")";
				}
			}
		}
	}

	else
	{
		$error_text = __("There doesn't seam to be a group to import to", 'lang_group');
	}

	if($rows > 0)
	{
		$strGroupImport = "";

		$done_text = sprintf(__("The group was updated with %d addresses", 'lang_group'), $rows);
		$error_text = "";
	}

	else if(!isset($error_text) || $error_text == '')
	{
		$error_text = __("The group wasn't updated with any addresses", 'lang_group');
	}
}

echo "<div class='wrap'>
	<h2>".__("Import", 'lang_group')."</h2>"
	.get_notification()
	."<div id='poststuff' class='postbox'>
		<h3 class='hndle'>".__("Add", 'lang_group')."</h3>
		<div class='inside'>
			<form action='#' method='post' class='mf_form mf_settings'>"
				.show_textarea(array('name' => 'strGroupImport', 'text' => __("Text", 'lang_group'), 'value' => $strGroupImport, 'xtra' => "autofocus", 'placeholder' => __("Enter social security numbers or e-mail addresses on separate rows for import", 'lang_group')))
				.show_button(array('name' => 'btnGroupImport', 'text' => __("Import", 'lang_group')))
				.input_hidden(array('name' => 'intGroupID', 'value' => $intGroupID))
				.wp_nonce_field('group_import_'.$intGroupID, '_wpnonce_group_import', true, false)
			."</form>
		</div>
	</div>
</div>";