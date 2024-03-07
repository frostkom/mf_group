<?php

$obj_group = new mf_group(array('type' => 'create'));

if(!IS_EDITOR)
{
	$obj_group->query_where .= " AND post_author = '".get_current_user_id()."'";
}

$obj_group->fetch_request();
echo $obj_group->save_data();
$obj_group->get_from_db();

$arr_data_page = array();
get_post_children(array('add_choose_here' => true), $arr_data_page);

echo "<div class='wrap'>
	<h2>".__("Group", 'lang_group')."</h2>"
	.get_notification()
	."<div id='poststuff'>
		<form action='#' method='post' class='mf_form mf_settings'>
			<div id='post-body' class='columns-2'>
				<div id='post-body-content'>
					<div class='postbox'>
						<h3 class='hndle'><span>".($obj_group->id > 0 ? __("Update", 'lang_group') : __("Add", 'lang_group'))."</span></h3>
						<div class='inside'>"
							.show_textfield(array('name' => 'strGroupName', 'text' => __("Name", 'lang_group'), 'value' => $obj_group->name, 'xtra' => "autofocus"));

							if($obj_group->id == 0)
							{
								echo show_select(array('data' => $obj_group->get_for_select(), 'name' => 'intGroupID_copy', 'text' => __("Copy addresses from", 'lang_group')));
							}

							echo show_textarea(array('name' => 'strGroupAPI', 'text' => __("API Link", 'lang_group'), 'value' => $obj_group->api))
							.show_textarea(array('name' => 'strGroupAPIFilter', 'text' => __("Filter API", 'lang_group'), 'value' => $obj_group->api_filter, 'placeholder' => "include:field=[value1,value2]"))
						."</div>
					</div>";

					if($obj_group->api == '')
					{
						echo "<div class='postbox'>
							<h3 class='hndle'><span>".__("Acceptance e-mail", 'lang_group')."</span></h3>
							<div class='inside'>"
								.show_select(array('data' => get_yes_no_for_select(), 'name' => 'strGroupAcceptanceEmail', 'text' => __("Send before adding to a group", 'lang_group'), 'value' => $obj_group->acceptance_email))
								."<div class='display_acceptance_message'>"
									.show_textfield(array('name' => 'strGroupAcceptanceSubject', 'text' => __("Subject", 'lang_group'), 'value' => $obj_group->acceptance_subject, 'placeholder' => sprintf(__("Accept subscription to %s", 'lang_group'), $obj_group->name)))
									.show_wp_editor(array('name' => 'strGroupAcceptanceText', 'value' => $obj_group->acceptance_text, 'description' => __("Example", 'lang_group').": ".sprintf(__("You have been added to the group %s but will not get any messages until you have accepted this subscription by clicking the link below.", 'lang_group'), $obj_group->name)))
								."</div>
								<div class='display_reminder_message'>
									<h3>".__("Reminder if the recipient has not yet accepted", 'lang_group')."</h3>"
									.show_textfield(array('name' => 'strGroupReminderSubject', 'text' => __("Subject", 'lang_group'), 'value' => $obj_group->reminder_subject, 'placeholder' => sprintf(__("Accept subscription to %s", 'lang_group'), $obj_group->name)))
									.show_wp_editor(array('name' => 'strGroupReminderText', 'value' => $obj_group->reminder_text, 'description' => __("Example", 'lang_group').": ".sprintf(__("You have been added to the group %s but will not get any messages until you have accepted this subscription by clicking the link below.", 'lang_group'), $obj_group->name)))
								."</div>
							</div>
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

						echo "<div class='postbox'>
							<h3 class='hndle'><span>".__("About the Group", 'lang_group')."</span></h3>
							<div class='inside'>";

								if(count($arr_data_email) > 1)
								{
									echo show_select(array('data' => $arr_data_email, 'name' => 'intGroupOwnerEmail', 'text' => __("Owner", 'lang_group'), 'value' => $obj_group->owner_email));
								}

								if(count($arr_data_abuse) > 1)
								{
									echo show_select(array('data' => $arr_data_abuse, 'name' => 'intGroupAbuseEmail', 'text' => __("Abuse", 'lang_group'), 'description' => sprintf(__("You should have setup both %s and %s because these addresses are usually used for other servers when sending notices about spam. This is a great way of receiving and handling possible issues within your own domain", 'lang_group'), "abuse@domain.com", "postmaster@domain.com")));
								}

								echo show_select(array('data' => $arr_data_page, 'name' => 'intGroupHelpPage', 'text' => __("Help Page", 'lang_group'), 'value' => $obj_group->help_page))
								.show_select(array('data' => $arr_data_page, 'name' => 'intGroupArchivePage', 'text' => __("Archive Page", 'lang_group'), 'value' => $obj_group->archive_page))
							."</div>
						</div>";
					}

				echo "</div>
				<div id='postbox-container-1'>
					<div class='postbox'>
						<div class='inside'>"
							.show_button(array('name' => 'btnGroupSave', 'text' => __("Save", 'lang_group')))
							.input_hidden(array('name' => 'intGroupID', 'value' => $obj_group->id))
							.wp_nonce_field('group_save_'.$obj_group->id, '_wpnonce_group_save', true, false);

							if($obj_group->id > 0)
							{
								$result = $wpdb->get_results($wpdb->prepare("SELECT post_date, post_modified, post_author FROM ".$wpdb->posts." WHERE ID = '%d'", $obj_group->id));

								foreach($result as $r)
								{
									$post_date = $r->post_date;
									$post_modified = $r->post_modified;
									$post_author = $r->post_author;

									echo "<br><em>".sprintf(__("Created %s by %s", 'lang_group'), format_date($post_date), get_user_info(array('id' => $post_author)))."</em>";

									if($post_modified > $post_date)
									{
										echo "<br><em>".sprintf(__("Updated %s", 'lang_group'), format_date($post_date))."</em>";
									}
								}
							}

						echo "</div>
					</div>
					<div class='postbox'>
						<h3 class='hndle'><span>".__("Settings", 'lang_group')."</span></h3>
						<div class='inside'>";

							$description = "";

							if($obj_group->group_type == 'stop')
							{
								$description = "<i class='fa fa-exclamation-triangle yellow'></i> ".__("This will prevent messages to all recipients in this group regardless which group that you are sending to.", 'lang_group');
							}

							echo show_select(array('data' => $obj_group->get_types_for_select(), 'name' => 'strGroupType', 'text' => __("Type", 'lang_group'), 'value' => $obj_group->group_type, 'description' => $description))
							.show_select(array('data' => get_yes_no_for_select(), 'name' => 'strGroupAllowRegistration', 'text' => __("Allow Registration", 'lang_group'), 'value' => $obj_group->allow_registration))
							."<div class='display_registration_fields'>"
								.show_select(array('data' => get_yes_no_for_select(), 'name' => 'strGroupVerifyAddress', 'text' => __("Verify that address is in Address book", 'lang_group'), 'value' => $obj_group->verify_address))
								.show_select(array('data' => $arr_data_page, 'name' => 'intGroupContactPage', 'text' => __("Contact Page", 'lang_group'), 'value' => $obj_group->contact_page))
								.show_select(array('data' => $obj_group->get_registration_fields_for_select(), 'name' => 'arrGroupRegistrationFields[]', 'text' => __("Registration Fields", 'lang_group'), 'value' => $obj_group->registration_fields))
							."</div>"
							.show_select(array('data' => get_yes_no_for_select(), 'name' => 'strGroupVerifyLink', 'text' => __("Add Verify Link", 'lang_group'), 'value' => $obj_group->verify_link, 'description' => __("In every message a hidden image/link is placed to see if the recipient has opened the message. This increases the risk of being classified as spam", 'lang_group')));

							$amount_in_group = $obj_group->amount_in_group();

							if(!($obj_group->id > 0) || $obj_group->sync_users == 'yes' || $amount_in_group == 0)
							{
								//$arr_data = get_yes_no_for_select();
								$arr_data = array(
									'no' => __("No", 'lang_group'),
									'yes' => __("Users", 'lang_group'),
								);

								$arr_data = apply_filters('get_group_sync_type', $arr_data);

								echo show_select(array('data' => $arr_data, 'name' => 'strGroupSyncUsers', 'text' => __("Synchronize", 'lang_group'), 'value' => $obj_group->sync_users, 'description' => __("This will automatically add/remove addresses and their information to this group", 'lang_group')));
							}

							if($obj_group->id > 0 && $obj_group->api == '' && $obj_group->allow_registration == 'no' && $obj_group->sync_users == 'no' && $amount_in_group > 0)
							{
								echo show_button(array('name' => 'btnGroupRemoveRecipients', 'text' => __("Remove all recipients", 'lang_group'), 'class' => "button delete"))
								.show_checkbox(array('name' => 'intGroupRemoveRecipientsConfirm', 'text' => __("Are you really sure?", 'lang_group'), 'value' => 1, 'description' => __("This will remove all recipients from this group and it is not possible to undo this action", 'lang_group')))
								.wp_nonce_field('group_remove_recipients_'.$obj_group->id, '_wpnonce_group_remove_recipients', true, false);
							}

						echo "</div>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>";