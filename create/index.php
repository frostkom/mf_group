<?php

$obj_group = new mf_group();

$intGroupID = check_var('intGroupID');

$query_xtra = "";

if(!IS_EDITOR)
{
	$query_xtra .= " AND post_author = '".get_current_user_id()."'";
}

$intGroupID = check_var('intGroupID');
$strGroupName = check_var('strGroupName');

$strGroupAcceptanceEmail = check_var('strGroupAcceptanceEmail');
$strGroupAcceptanceSubject = check_var('strGroupAcceptanceSubject');
$strGroupAcceptanceText = check_var('strGroupAcceptanceText');

/*$intGroupUnsubscribeEmail = check_var('intGroupUnsubscribeEmail');
$intGroupSubscribeEmail = check_var('intGroupSubscribeEmail');*/
$intGroupOwnerEmail = check_var('intGroupOwnerEmail');
$intGroupHelpPage = check_var('intGroupHelpPage');
$intGroupArchivePage = check_var('intGroupArchivePage');

$strGroupPublic = check_var('strGroupPublic', 'char', true, 'draft');
$strGroupVerifyAddress = check_var('strGroupVerifyAddress');
$intGroupContactPage = check_var('intGroupContactPage');
$intGroupVerifyLink = check_var('intGroupVerifyLink', 'char', true, 'no');
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
			update_post_meta($intGroupID, 'group_acceptance_email', $strGroupAcceptanceEmail);
			update_post_meta($intGroupID, 'group_acceptance_subject', $strGroupAcceptanceSubject);
			update_post_meta($intGroupID, 'group_acceptance_text', $strGroupAcceptanceText);

			update_post_meta($intGroupID, 'group_verify_address', $strGroupVerifyAddress);
			update_post_meta($intGroupID, 'group_contact_page', $intGroupContactPage);
			update_post_meta($intGroupID, 'group_registration_fields', $arrGroupRegistrationFields);
			update_post_meta($intGroupID, $obj_group->meta_prefix.'verify_link', $intGroupVerifyLink);

			/*update_post_meta($intGroupID, $obj_group->meta_prefix.'unsubscribe_email', $intGroupUnsubscribeEmail);
			update_post_meta($intGroupID, $obj_group->meta_prefix.'subscribe_email', $intGroupSubscribeEmail);*/
			update_post_meta($intGroupID, $obj_group->meta_prefix.'owner_email', $intGroupOwnerEmail);
			update_post_meta($intGroupID, $obj_group->meta_prefix.'help_page', $intGroupHelpPage);
			update_post_meta($intGroupID, $obj_group->meta_prefix.'archive_page', $intGroupArchivePage);

			mf_redirect(admin_url("admin.php?page=mf_group/list/index.php&updated"));
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
				$result = $wpdb->get_results($wpdb->prepare("SELECT addressID FROM ".get_address_table_prefix()."address INNER JOIN ".$wpdb->prefix."address2group USING (addressID) WHERE groupID = '%d' AND addressDeleted = '0'", $intGroupID_copy));

				foreach($result as $r)
				{
					$intAddressID = $r->addressID;

					$obj_group->add_address(array('address_id' => $intAddressID, 'group_id' => $intGroupID));
				}
			}

			mf_redirect(admin_url("admin.php?page=mf_group/list/index.php&created"));
		}

		else
		{
			$error_text = __("The information was not submitted, contact an admin if this persists", 'lang_group');
		}
	}
}

if($intGroupID > 0)
{
	$result = $wpdb->get_results($wpdb->prepare("SELECT post_status, post_title FROM ".$wpdb->posts." WHERE post_type = 'mf_group' AND ID = '%d'".$query_xtra, $intGroupID));

	foreach($result as $r)
	{
		$strGroupPublic = $r->post_status;
		$strGroupName = $r->post_title;

		$strGroupAcceptanceEmail = get_post_meta_or_default($intGroupID, 'group_acceptance_email', true, 'no');
		$strGroupAcceptanceSubject = get_post_meta($intGroupID, 'group_acceptance_subject', true);
		$strGroupAcceptanceText = get_post_meta($intGroupID, 'group_acceptance_text', true);

		$strGroupVerifyAddress = get_post_meta_or_default($intGroupID, 'group_verify_address', true, 'no');
		$intGroupContactPage = get_post_meta($intGroupID, 'group_contact_page', true);
		$arrGroupRegistrationFields = get_post_meta($intGroupID, 'group_registration_fields', true);
		$intGroupVerifyLink = get_post_meta($intGroupID, $obj_group->meta_prefix.'verify_link', true);

		/*$intGroupUnsubscribeEmail = get_post_meta($intGroupID, $obj_group->meta_prefix.'unsubscribe_email', true);
		$intGroupSubscribeEmail = get_post_meta($intGroupID, $obj_group->meta_prefix.'subscribe_email', true);*/
		$intGroupOwnerEmail = get_post_meta($intGroupID, $obj_group->meta_prefix.'owner_email', true);
		$intGroupHelpPage = get_post_meta($intGroupID, $obj_group->meta_prefix.'help_page', true);
		$intGroupArchivePage = get_post_meta($intGroupID, $obj_group->meta_prefix.'archive_page', true);

		if($strGroupPublic == "trash")
		{
			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->posts." SET post_status = 'publish' WHERE ID = '%d' AND post_type = 'mf_group'".$query_xtra, $intGroupID));
		}
	}
}

$arr_data_public = array(
	'publish' => __("Public", 'lang_group'),
	'draft' => __("Not Public", 'lang_group'),
	'ignore' => __("Inactive", 'lang_group'),
);

echo "<div class='wrap'>
	<h2>".__("Group", 'lang_group')."</h2>"
	.get_notification()
	."<div id='poststuff'>
		<form action='#' method='post' class='mf_form mf_settings'>
			<div id='post-body' class='columns-2'>
				<div id='post-body-content'>
					<div class='postbox'>
						<h3 class='hndle'><span>".($intGroupID > 0 ? __("Update", 'lang_group') : __("Add", 'lang_group'))."</span></h3>
						<div class='inside'>"
							.show_textfield(array('name' => "strGroupName", 'text' => __("Name", 'lang_group'), 'value' => $strGroupName, 'xtra' => "autofocus"));

							if(!($intGroupID > 0))
							{
								$result = $wpdb->get_results("SELECT ID, post_title FROM ".$wpdb->posts." WHERE post_type = 'mf_group' AND post_status = 'publish'".$query_xtra." ORDER BY post_title ASC");

								if($wpdb->num_rows > 0)
								{
									$arr_data = array(
										'' => "-- ".__("Choose here", 'lang_group')." --"
									);

									foreach($result as $r)
									{
										$arr_data[$r->ID] = $r->post_title;
									}

									echo show_select(array('data' => $arr_data, 'name' => 'intGroupID_copy', 'text' => __("Copy addresses from", 'lang_group')));
								}
							}

						echo "</div>
					</div>
					<div class='postbox'>
						<h3 class='hndle'><span>".__("Acceptance e-mail", 'lang_group')."</span></h3>
						<div class='inside'>"
							.show_select(array('data' => get_yes_no_for_select(), 'name' => 'strGroupAcceptanceEmail', 'text' => __("Send before adding to a group", 'lang_group'), 'value' => $strGroupAcceptanceEmail));

							if($strGroupAcceptanceEmail == 'yes')
							{
								echo show_textfield(array('name' => 'strGroupAcceptanceSubject', 'text' => __("Subject", 'lang_group'), 'value' => $strGroupAcceptanceSubject, 'placeholder' => sprintf(__("Accept subscription to %s", 'lang_group'), $strGroupName)))
								.show_wp_editor(array('name' => 'strGroupAcceptanceText', 'value' => $strGroupAcceptanceText, 'description' => __("Example", 'lang_group').": ".sprintf(__("You have been added to the group %s but will not get any messages until you have accepted this subscription by clicking the link below.", 'lang_group'), $strGroupName))); //, 'text' => __("Message", 'lang_group')
							}

						echo "</div>
					</div>
					<div class='postbox'>
						<h3 class='hndle'><span>".__("About the Group", 'lang_group')."</span></h3>
						<div class='inside'>";

							if(is_plugin_active("mf_email/index.php"))
							{
								$obj_email = new mf_email();
								//$arr_data_incoming = $obj_email->get_from_for_select(array('type' => 'incoming'));
								$arr_data_email = $obj_email->get_from_for_select();
								
								$arr_data_page = array();
								get_post_children(array('add_choose_here' => true), $arr_data_page);

								/*echo show_select(array('data' => $arr_data_incoming, 'name' => 'intGroupUnsubscribeEmail', 'text' => __("E-mail to Unsubscribe to", 'lang_group'), 'value' => $intGroupUnsubscribeEmail))
								.show_select(array('data' => $arr_data_incoming, 'name' => 'intGroupSubscribeEmail', 'text' => __("E-mail to Subscribe to", 'lang_group'), 'value' => $intGroupSubscribeEmail));*/
								echo show_select(array('data' => $arr_data_email, 'name' => 'intGroupOwnerEmail', 'text' => __("E-mail Owner", 'lang_group'), 'value' => $intGroupOwnerEmail))
								.show_select(array('data' => $arr_data_page, 'name' => 'intGroupHelpPage', 'text' => __("Help Page", 'lang_group'), 'value' => $intGroupHelpPage))
								.show_select(array('data' => $arr_data_page, 'name' => 'intGroupArchivePage', 'text' => __("Archive Page", 'lang_group'), 'value' => $intGroupArchivePage));
							}

						echo "</div>
					</div>
				</div>
				<div id='postbox-container-1'>
					<div class='postbox'>
						<div class='inside'>"
							.show_button(array('name' => "btnGroupCreate", 'text' => __("Save", 'lang_group')))
							.input_hidden(array('name' => "intGroupID", 'value' => $intGroupID))
						."</div>
					</div>
					<div class='postbox'>
						<h3 class='hndle'><span>".__("Settings", 'lang_group')."</span></h3>
						<div class='inside'>"
							.show_select(array('data' => $arr_data_public, 'name' => 'strGroupPublic', 'text' => __("Status", 'lang_group'), 'value' => $strGroupPublic, 'description' => ($intGroupID > 0 ? get_permalink($intGroupID) : "")));

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

							echo show_select(array('data' => get_yes_no_for_select(), 'name' => 'intGroupVerifyLink', 'text' => __("Add Verify Link", 'lang_group'), 'value' => $intGroupVerifyLink, 'description' => __("In every message a hidden image/link is placed to see if the recepient has opened the message. This increases the risk of being classified as spam", 'lang_group')))
						."</div>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>";