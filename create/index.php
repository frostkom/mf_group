<?php

$intGroupID = check_var('intGroupID');

$query_xtra = "";

if(!IS_EDITOR)
{
	$query_xtra .= " AND post_author = '".get_current_user_id()."'";
}

$intGroupID = check_var('intGroupID');
$strGroupPublic = check_var('strGroupPublic', 'char', true, 'draft');
$strGroupVerifyAddress = check_var('strGroupVerifyAddress');
$intGroupContactPage = check_var('intGroupContactPage');
$strGroupName = check_var('strGroupName');
$arrGroupRegistrationFields = check_var('arrGroupRegistrationFields');
$intGroupID_copy = check_var('intGroupID_copy');

$obj_group = new mf_group();

if(isset($_POST['btnGroupCreate']))
{
	$post_data = array(
		'post_type' => 'mf_group',
		'post_status' => $strGroupPublic,
		'post_title' => $strGroupName,
	);

	if($intGroupID > 0)
	{
		$post_data['ID'] = $intGroupID;
		$post_data['post_modified'] = date("Y-m-d H:i:s");

		if(wp_update_post($post_data) > 0)
		{
			update_post_meta($intGroupID, 'group_registration_fields', $arrGroupRegistrationFields);
			update_post_meta($intGroupID, 'group_verify_address', $strGroupVerifyAddress);
			update_post_meta($intGroupID, 'group_contact_page', $intGroupContactPage);

			mf_redirect("/wp-admin/admin.php?page=mf_group/list/index.php&updated");
		}

		else
		{
			$error_text = __("The information was not submitted, contact an admin if this persists", 'lang_group');
		}
	}

	else
	{
		$intGroupID = wp_insert_post($post_data);

		if($intGroupID > 0)
		{
			if($intGroupID_copy > 0)
			{
				$result = $wpdb->get_results($wpdb->prepare("SELECT addressID FROM ".$wpdb->base_prefix."address INNER JOIN ".$wpdb->base_prefix."address2group USING (addressID) WHERE groupID = '%d' AND addressDeleted = '0'", $intGroupID_copy));

				foreach($result as $r)
				{
					$intAddressID = $r->addressID;

					$obj_group->add_address(array('address_id' => $intAddressID, 'group_id' => $intGroupID));
				}
			}

			mf_redirect("/wp-admin/admin.php?page=mf_group/list/index.php&created");
		}

		else
		{
			$error_text = __("The information was not submitted, contact an admin if this persists", 'lang_group');
		}
	}
}

echo "<div class='wrap'>
	<h2>".__("Group", 'lang_group')."</h2>"
	.get_notification();

	if($intGroupID > 0)
	{
		$result = $wpdb->get_results($wpdb->prepare("SELECT post_status, post_title FROM ".$wpdb->posts." WHERE post_type = 'mf_group' AND ID = '%d'".$query_xtra, $intGroupID));

		foreach($result as $r)
		{
			$strGroupPublic = $r->post_status;
			$strGroupName = $r->post_title;

			$arrGroupRegistrationFields = get_post_meta($intGroupID, 'group_registration_fields', true);
			$strGroupVerifyAddress = get_post_meta($intGroupID, 'group_verify_address', true);
			$intGroupContactPage = get_post_meta($intGroupID, 'group_contact_page', true);

			if($strGroupVerifyAddress == "")
			{
				$strGroupVerifyAddress = "no";
			}

			if($strGroupPublic == "trash")
			{
				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->posts." SET post_status = 'publish' WHERE ID = '%d' AND post_type = 'mf_group'".$query_xtra, $intGroupID));
			}
		}
	}

	echo "<div id='poststuff' class='postbox'>
		<h3 class='hndle'>".($intGroupID > 0 ? __("Update", 'lang_group') : __("Add", 'lang_group'))."</h3>
		<div class='inside'>
			<form action='#' method='post' class='mf_form mf_settings'>";

				if($intGroupID > 0)
				{
					echo "<div class='flex_flow'>";

						$arr_data = array(
							'publish' => __("Public", 'lang_group'),
							'draft' => __("Not Public", 'lang_group'),
							'ignore' => __("Inactive", 'lang_group'),
						);

						echo show_select(array('data' => $arr_data, 'name' => 'strGroupPublic', 'text' => __("Status", 'lang_group'), 'value' => $strGroupPublic));

						if($strGroupPublic == "publish")
						{
							echo show_select(array('data' => get_yes_no_for_select(), 'name' => 'strGroupVerifyAddress', 'text' => __("Verify that address is in Address book", 'lang_group'), 'value' => $strGroupVerifyAddress));

							if($strGroupVerifyAddress == "yes")
							{
								$arr_data = array();
								get_post_children(array('add_choose_here' => true), $arr_data);

								echo show_select(array('data' => $arr_data, 'name' => 'intGroupContactPage', 'text' => __("Contact Page", 'lang_group'), 'value' => $intGroupContactPage));
							}
						}

					echo "</div>";
				}

				echo show_textfield(array('name' => "strGroupName", 'text' => __("Name", 'lang_group'), 'value' => $strGroupName, 'xtra' => "autofocus"));

				if($strGroupPublic == "publish")
				{
					$arr_data = array(
						'name' => __("Name", 'lang_group'),
						'address' => __("Address", 'lang_group'),
						'zip' => __("Zip Code", 'lang_group'),
						'city' => __("City", 'lang_group'),
						'phone' => __("Phone Number", 'lang_group'),
						'email' => __("E-mail", 'lang_group'),
						'extra' => get_option_or_default('setting_address_extra', __("Extra", 'lang_group')),
					);

					echo show_select(array('data' => $arr_data, 'name' => 'arrGroupRegistrationFields[]', 'text' => __("Registration Fields", 'lang_group'), 'value' => $arrGroupRegistrationFields));
				}

				if(!($intGroupID > 0))
				{
					$result = $wpdb->get_results("SELECT ID, post_title FROM ".$wpdb->posts." WHERE post_type = 'mf_group' AND post_status = 'publish'".$query_xtra." ORDER BY post_title ASC");

					if($wpdb->num_rows > 0)
					{
						$arr_data = array();

						$arr_data[''] = "-- ".__("Choose here", 'lang_group')." --";

						foreach($result as $r)
						{
							$arr_data[$r->ID] = $r->post_title;
						}

						echo show_select(array('data' => $arr_data, 'name' => 'intGroupID_copy', 'text' => __("Copy addresses from", 'lang_group')));
					}
				}

				echo show_button(array('name' => "btnGroupCreate", 'text' => __("Save", 'lang_group')))
				.input_hidden(array('name' => "intGroupID", 'value' => $intGroupID))
			."</form>
		</div>
	</div>
</div>";