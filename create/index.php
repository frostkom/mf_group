<?php

$obj_group = new mf_group(array('type' => 'create'));

$obj_group->query_where = "";

if(!IS_EDITOR)
{
	$obj_group->query_where .= " AND post_author = '".get_current_user_id()."'";
}

$obj_group->fetch_request();
echo $obj_group->save_data();
$obj_group->get_from_db();

echo "<div class='wrap'>
	<h2>".__("Group", $obj_group->lang_key)."</h2>"
	.get_notification()
	."<div id='poststuff'>
		<form action='#' method='post' class='mf_form mf_settings'>
			<div id='post-body' class='columns-2'>
				<div id='post-body-content'>
					<div class='postbox'>
						<h3 class='hndle'><span>".($obj_group->id > 0 ? __("Update", $obj_group->lang_key) : __("Add", $obj_group->lang_key))."</span></h3>
						<div class='inside'>"
							.show_textfield(array('name' => 'strGroupName', 'text' => __("Name", $obj_group->lang_key), 'value' => $obj_group->name, 'xtra' => "autofocus"));

							if($obj_group->id > 0)
							{
								$group_api_description = "<a href='".get_site_url()."/wp-content/plugins/mf_group/include/api/?group_id=".$obj_group->id."'>".__("Use this link to synchronize all the addresses from this group to another site", $obj_group->lang_key)."</a>";
							}

							else
							{
								echo show_select(array('data' => $obj_group->get_for_select(), 'name' => 'intGroupID_copy', 'text' => __("Copy addresses from", $obj_group->lang_key)));

								$group_api_description = "";
							}

							echo show_textarea(array('name' => 'strGroupAPI', 'text' => __("API Link", $obj_group->lang_key), 'value' => $obj_group->api, 'description' => $group_api_description));

							if($obj_group->api != '')
							{
								echo show_textarea(array('name' => 'strGroupAPIFilter', 'text' => __("Filter API", $obj_group->lang_key), 'value' => $obj_group->api_filter, 'placeholder' => "include:field=[value1,value2]"));
							}

						echo "</div>
					</div>";

					if($obj_group->api == '')
					{
						echo "<div class='postbox'>
							<h3 class='hndle'><span>".__("Acceptance e-mail", $obj_group->lang_key)."</span></h3>
							<div class='inside'>"
								.show_select(array('data' => get_yes_no_for_select(), 'name' => 'strGroupAcceptanceEmail', 'text' => __("Send before adding to a group", $obj_group->lang_key), 'value' => $obj_group->acceptance_email));

								if($obj_group->acceptance_email == 'yes')
								{
									echo show_textfield(array('name' => 'strGroupAcceptanceSubject', 'text' => __("Subject", $obj_group->lang_key), 'value' => $obj_group->acceptance_subject, 'placeholder' => sprintf(__("Accept subscription to %s", $obj_group->lang_key), $obj_group->name)))
									.show_wp_editor(array('name' => 'strGroupAcceptanceText', 'value' => $obj_group->acceptance_text, 'description' => __("Example", $obj_group->lang_key).": ".sprintf(__("You have been added to the group %s but will not get any messages until you have accepted this subscription by clicking the link below.", $obj_group->lang_key), $obj_group->name)));

									if($obj_group->acceptance_subject != '' && $obj_group->acceptance_text != '')
									{
										echo "<h3>".__("Reminder if the recipient has not yet accepted", $obj_group->lang_key)."</h3>"
										.show_textfield(array('name' => 'strGroupReminderSubject', 'text' => __("Subject", $obj_group->lang_key), 'value' => $obj_group->reminder_subject, 'placeholder' => sprintf(__("Accept subscription to %s", $obj_group->lang_key), $obj_group->name)))
										.show_wp_editor(array('name' => 'strGroupReminderText', 'value' => $obj_group->reminder_text, 'description' => __("Example", $obj_group->lang_key).": ".sprintf(__("You have been added to the group %s but will not get any messages until you have accepted this subscription by clicking the link below.", $obj_group->lang_key), $obj_group->name)));
									}
								}

							echo "</div>
						</div>";
					}

					if(is_plugin_active("mf_email/index.php"))
					{
						if(!isset($obj_email))
						{
							$obj_email = new mf_email();
						}

						$arr_data_email = $obj_email->get_from_for_select();
						$arr_data_abuse = $obj_email->get_from_for_select(array('type' => 'abuse'));

						$arr_data_page = array();
						get_post_children(array('add_choose_here' => true), $arr_data_page);

						echo "<div class='postbox'>
							<h3 class='hndle'><span>".__("About the Group", $obj_group->lang_key)."</span></h3>
							<div class='inside'>";

								if(count($arr_data_email) > 1)
								{
									echo show_select(array('data' => $arr_data_email, 'name' => 'intGroupOwnerEmail', 'text' => __("Owner", $obj_group->lang_key), 'value' => $obj_group->owner_email));
								}

								if(count($arr_data_abuse) > 1)
								{
									echo show_select(array('data' => $arr_data_abuse, 'name' => 'intGroupAbuseEmail', 'text' => __("Abuse", $obj_group->lang_key), 'description' => sprintf(__("You should have setup both %s and %s because these addresses are usually used for other servers when sending notices about spam. This is a great way of receiving and handling possible issues within your own domain", $obj_group->lang_key), "abuse@domain.com", "postmaster@domain.com")));
								}

								echo show_select(array('data' => $arr_data_page, 'name' => 'intGroupHelpPage', 'text' => __("Help Page", $obj_group->lang_key), 'value' => $obj_group->help_page))
								.show_select(array('data' => $arr_data_page, 'name' => 'intGroupArchivePage', 'text' => __("Archive Page", $obj_group->lang_key), 'value' => $obj_group->archive_page))
							."</div>
						</div>";
					}

				echo "</div>";

				echo "<div id='postbox-container-1'>
					<div class='postbox'>
						<div class='inside'>"
							.show_button(array('name' => 'btnGroupSave', 'text' => __("Save", $obj_group->lang_key)))
							.input_hidden(array('name' => 'intGroupID', 'value' => $obj_group->id))
							.wp_nonce_field('group_save_'.$obj_group->id, '_wpnonce_group_save', true, false)
						."</div>
					</div>
					<div class='postbox'>
						<h3 class='hndle'><span>".__("Settings", $obj_group->lang_key)."</span></h3>
						<div class='inside'>";

							if($obj_group->api == '')
							{
								echo show_select(array('data' => get_yes_no_for_select(), 'name' => 'strGroupAllowRegistration', 'text' => __("Allow Registration", $obj_group->lang_key), 'value' => $obj_group->allow_registration));
							}

							if($obj_group->allow_registration == 'yes')
							{
								echo show_select(array('data' => get_yes_no_for_select(), 'name' => 'strGroupVerifyAddress', 'text' => __("Verify that address is in Address book", $obj_group->lang_key), 'value' => $obj_group->verify_address));

								if($obj_group->verify_address == "yes")
								{
									$arr_data = array();
									get_post_children(array('add_choose_here' => true), $arr_data);

									echo show_select(array('data' => $arr_data, 'name' => 'intGroupContactPage', 'text' => __("Contact Page", $obj_group->lang_key), 'value' => $obj_group->contact_page));
								}

								$arr_data = array(
									'name' => __("Name", $obj_group->lang_key),
									'address' => __("Address", $obj_group->lang_key),
									'zip' => __("Zip Code", $obj_group->lang_key),
									'city' => __("City", $obj_group->lang_key),
									'phone' => __("Phone Number", $obj_group->lang_key),
									'email' => __("E-mail", $obj_group->lang_key),
									'extra' => get_option_or_default('setting_address_extra', __("Extra", $obj_group->lang_key)),
								);

								echo show_select(array('data' => $arr_data, 'name' => 'arrGroupRegistrationFields[]', 'text' => __("Registration Fields", $obj_group->lang_key), 'value' => $obj_group->registration_fields));
							}

							echo show_select(array('data' => get_yes_no_for_select(), 'name' => 'strGroupVerifyLink', 'text' => __("Add Verify Link", $obj_group->lang_key), 'value' => $obj_group->verify_link, 'description' => __("In every message a hidden image/link is placed to see if the recipient has opened the message. This increases the risk of being classified as spam", $obj_group->lang_key)));

							$amount_in_group = $obj_group->amount_in_group();

							if(!($obj_group->id > 0) || $obj_group->sync_users == 'yes' || $amount_in_group == 0)
							{
								echo show_select(array('data' => get_yes_no_for_select(), 'name' => 'strGroupSyncUsers', 'text' => __("Synchronize Users", $obj_group->lang_key), 'value' => $obj_group->sync_users, 'description' => __("This will automatically add/remove users and their information to this group", $obj_group->lang_key)));
							}

							if($obj_group->id > 0 && $obj_group->api == '' && $obj_group->allow_registration == 'no' && $obj_group->sync_users == 'no' && $amount_in_group > 0)
							{
								echo show_button(array('name' => 'btnGroupRemoveRecipients', 'text' => __("Remove all recipients", $obj_group->lang_key), 'class' => "button delete"))
								.show_checkbox(array('name' => 'intGroupRemoveRecipientsConfirm', 'text' => __("Are you really sure?", $obj_group->lang_key), 'value' => 1, 'description' => __("This will remove all recipients from this group and it is not possible to undo this action", $obj_group->lang_key)))
								.wp_nonce_field('group_remove_recipients_'.$obj_group->id, '_wpnonce_group_remove_recipients', true, false);
							}

						echo "</div>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>";