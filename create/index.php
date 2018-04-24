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
	<h2>".__("Group", 'lang_group')."</h2>"
	.get_notification()
	."<div id='poststuff'>
		<form action='#' method='post' class='mf_form mf_settings'>
			<div id='post-body' class='columns-2'>
				<div id='post-body-content'>
					<div class='postbox'>
						<h3 class='hndle'><span>".($obj_group->id > 0 ? __("Update", 'lang_group') : __("Add", 'lang_group'))."</span></h3>
						<div class='inside'>"
							.show_textfield(array('name' => "strGroupName", 'text' => __("Name", 'lang_group'), 'value' => $obj_group->name, 'xtra' => "autofocus"));

							if(!($obj_group->id > 0))
							{
								$result = $wpdb->get_results("SELECT ID, post_title FROM ".$wpdb->posts." WHERE post_type = 'mf_group' AND post_status = 'publish'".$obj_group->query_where." ORDER BY post_title ASC");

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
							.show_select(array('data' => get_yes_no_for_select(), 'name' => 'strGroupAcceptanceEmail', 'text' => __("Send before adding to a group", 'lang_group'), 'value' => $obj_group->acceptance_email));

							if($obj_group->acceptance_email == 'yes')
							{
								echo show_textfield(array('name' => 'strGroupAcceptanceSubject', 'text' => __("Subject", 'lang_group'), 'value' => $obj_group->acceptance_subject, 'placeholder' => sprintf(__("Accept subscription to %s", 'lang_group'), $obj_group->name)))
								.show_wp_editor(array('name' => 'strGroupAcceptanceText', 'value' => $obj_group->acceptance_text, 'description' => __("Example", 'lang_group').": ".sprintf(__("You have been added to the group %s but will not get any messages until you have accepted this subscription by clicking the link below.", 'lang_group'), $obj_group->name))); //, 'text' => __("Message", 'lang_group')
							}

						echo "</div>
					</div>";

					if(is_plugin_active("mf_email/index.php"))
					{
						echo "<div class='postbox'>
							<h3 class='hndle'><span>".__("About the Group", 'lang_group')."</span></h3>
							<div class='inside'>";

								$obj_email = new mf_email();
								//$arr_data_incoming = $obj_email->get_from_for_select(array('type' => 'incoming'));
								$arr_data_email = $obj_email->get_from_for_select();
								$arr_data_abuse = $obj_email->get_from_for_select(array('type' => 'abuse'));

								$arr_data_page = array();
								get_post_children(array('add_choose_here' => true), $arr_data_page);

								/*echo show_select(array('data' => $arr_data_incoming, 'name' => 'intGroupUnsubscribeEmail', 'text' => __("E-mail to Unsubscribe to", 'lang_group'), 'value' => $obj_group->unsubscribe_email))
								.show_select(array('data' => $arr_data_incoming, 'name' => 'intGroupSubscribeEmail', 'text' => __("E-mail to Subscribe to", 'lang_group'), 'value' => $obj_group->subscribe_email));*/
								echo show_select(array('data' => $arr_data_email, 'name' => 'intGroupOwnerEmail', 'text' => __("Owner", 'lang_group'), 'value' => $obj_group->owner_email))
								.show_select(array('data' => $arr_data_abuse, 'name' => 'intGroupAbuseEmail', 'text' => __("Abuse", 'lang_group'), 'description' => sprintf(__("You should have setup both %s and %s because these addresses are usually used for other servers when sending notices about spam. This is a great way of receiving and handling possible issues within your own domain", 'lang_group'), "abuse@domain.com", "postmaster@domain.com")))
								.show_select(array('data' => $arr_data_page, 'name' => 'intGroupHelpPage', 'text' => __("Help Page", 'lang_group'), 'value' => $obj_group->help_page))
								.show_select(array('data' => $arr_data_page, 'name' => 'intGroupArchivePage', 'text' => __("Archive Page", 'lang_group'), 'value' => $obj_group->archive_page));

							echo "</div>
						</div>
					</div>";
				}

				echo "<div id='postbox-container-1'>
					<div class='postbox'>
						<div class='inside'>"
							.show_button(array('name' => 'btnGroupSave', 'text' => __("Save", 'lang_group')))
							.input_hidden(array('name' => 'intGroupID', 'value' => $obj_group->id))
							.wp_nonce_field('group_save_'.$obj_group->id, '_wpnonce', true, false)
						."</div>
					</div>
					<div class='postbox'>
						<h3 class='hndle'><span>".__("Settings", 'lang_group')."</span></h3>
						<div class='inside'>"
							.show_select(array('data' => get_yes_no_for_select(), 'name' => 'strGroupAllowRegistration', 'text' => __("Allow Registration", 'lang_group'), 'value' => $obj_group->allow_registration));
							//.show_select(array('data' => $obj_group->get_post_status_for_select(), 'name' => 'strGroupPublic', 'text' => __("Status", 'lang_group'), 'value' => $obj_group->public, 'description' => ($obj_group->id > 0 ? get_permalink($obj_group->id) : "")));

							if($obj_group->allow_registration == 'yes')
							{
								echo show_select(array('data' => get_yes_no_for_select(), 'name' => 'strGroupVerifyAddress', 'text' => __("Verify that address is in Address book", 'lang_group'), 'value' => $obj_group->verify_address));

								if($obj_group->verify_address == "yes")
								{
									$arr_data = array();
									get_post_children(array('add_choose_here' => true), $arr_data);

									echo show_select(array('data' => $arr_data, 'name' => 'intGroupContactPage', 'text' => __("Contact Page", 'lang_group'), 'value' => $obj_group->contact_page));
								}

								$arr_data = array(
									'name' => __("Name", 'lang_group'),
									'address' => __("Address", 'lang_group'),
									'zip' => __("Zip Code", 'lang_group'),
									'city' => __("City", 'lang_group'),
									'phone' => __("Phone Number", 'lang_group'),
									'email' => __("E-mail", 'lang_group'),
									'extra' => get_option_or_default('setting_address_extra', __("Extra", 'lang_group')),
								);

								echo show_select(array('data' => $arr_data, 'name' => 'arrGroupRegistrationFields[]', 'text' => __("Registration Fields", 'lang_group'), 'value' => $obj_group->registration_fields));
							}

							echo show_select(array('data' => get_yes_no_for_select(), 'name' => 'strGroupVerifyLink', 'text' => __("Add Verify Link", 'lang_group'), 'value' => $obj_group->verify_link, 'description' => __("In every message a hidden image/link is placed to see if the recepient has opened the message. This increases the risk of being classified as spam", 'lang_group')));

							if(!($obj_group->id > 0) || $obj_group->sync_users == 'yes')
							{
								echo show_select(array('data' => get_yes_no_for_select(), 'name' => 'strGroupSyncUsers', 'text' => __("Synchronize Users", 'lang_group'), 'value' => $obj_group->sync_users, 'description' => __("This will automatically add/remove users and their information to this group", 'lang_group')));
							}

							if($obj_group->allow_registration == 'no' && $obj_group->sync_users == 'no' && $obj_group->amount_in_group() > 0)
							{
								echo show_button(array('name' => 'btnGroupRemoveRecepients', 'text' => __("Remove all recepients", 'lang_group'), 'class' => "button delete"))
								.show_checkbox(array('name' => 'intGroupRemoveRecepientsConfirm', 'text' => __("Are you really sure?", 'lang_group'), 'value' => 1, 'description' => __("This will remove all recepients from this group and it is not possible to undo this action", 'lang_group')))
								.wp_nonce_field('group_remove_recepients_'.$obj_group->id, '_wpnonce', true, false);
							}

						echo "</div>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>";