<?php

$query_xtra = "";

if(!IS_EDITOR)
{
	$query_xtra .= " AND post_author = '".get_current_user_id()."'";
}

$type = check_var('type', 'char', true, 'email');
$intGroupID = check_var('intGroupID');
$arrGroupID = check_var('arrGroupID');
$intMessageID = check_var('intMessageID');
$strMessageFrom = check_var('strMessageFrom');
$strMessageName = check_var('strMessageName');
$strMessageText_orig = $strMessageText = check_var('strMessageText', 'raw');
$strMessageAttachment = check_var('strMessageAttachment');
$intEmailTextSource = check_var('intEmailTextSource');

if($intGroupID > 0 && !in_array($intGroupID, $arrGroupID))
{
	$arrGroupID[] = $intGroupID;
}

if(isset($_POST['btnGroupSend']) && count($arrGroupID) > 0 && wp_verify_nonce($_POST['_wpnonce'], 'group_send_'.$type))
{
	if($strMessageText == '')
	{
		$error_text = __("You have to enter a text to send", 'lang_group');
	}

	else if($type == "email" || $type == "sms")
	{
		$attachments_size = 0;
		$attachments_size_limit = 10 * pow(1024, 2);

		if($strMessageAttachment != '')
		{
			list($mail_attachment, $rest) = get_attachment_to_send($strMessageAttachment);

			foreach($mail_attachment as $file)
			{
				$attachments_size += filesize($file);
			}
		}

		if($attachments_size > $attachments_size_limit)
		{
			$error_text = sprintf(__("You are trying to send attachments of a total of %s. I suggest that you send the attachments as inline links instead of attachments. This way I don't have to send too much data which might slow down the server or make it timeout due to memory limits and it also makes the recipients not have to recieve that much in their inboxes.", 'lang_group'), show_final_size($attachments_size));
		}

		else
		{
			$message_recepients = 0;

			foreach($arrGroupID as $intGroupID)
			{
				$wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = 'mf_group' AND ID = '%d'".$query_xtra." LIMIT 0, 1", $intGroupID));

				if($wpdb->num_rows > 0)
				{
					$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."group_message SET groupID = '%d', messageType = %s, messageFrom = %s, messageName = %s, messageText = %s, messageAttachment = %s, messageCreated = NOW(), userID = '%d'", $intGroupID, $type, $strMessageFrom, $strMessageName, $strMessageText, $strMessageAttachment, get_current_user_id()));

					$intMessageID = $wpdb->insert_id;

					if($intMessageID > 0)
					{
						$result = $wpdb->get_results($wpdb->prepare("SELECT addressID, addressEmail, addressCellNo FROM ".$wpdb->base_prefix."address INNER JOIN ".$wpdb->base_prefix."address2group USING (addressID) WHERE groupID = '%d' AND addressDeleted = '0' AND groupUnsubscribed = '0'", $intGroupID));

						foreach($result as $r)
						{
							$intAddressID = $r->addressID;
							$strAddressEmail = $r->addressEmail;
							$strAddressCellNo = $r->addressCellNo;

							if($type == "email" && $strAddressEmail != "" || $type == "sms" && $strAddressCellNo != "")
							{
								$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."group_queue SET addressID = '%d', messageID = '%d', queueCreated = NOW()", $intAddressID, $intMessageID));

								if($wpdb->rows_affected > 0)
								{
									$message_recepients++;
								}
							}
						}
					}

					else
					{
						$error_text = __("There was an error when saving the message", 'lang_group');
					}
				}
			}

			if($message_recepients > 0)
			{
				mf_redirect("/wp-admin/admin.php?page=mf_group/list/index.php&sent");
			}

			else
			{
				$error_text = __("The message was not sent to anybody", 'lang_group');
			}
		}
	}
}

else if($intEmailTextSource > 0)
{
	$strMessageText = $wpdb->get_var($wpdb->prepare("SELECT post_content FROM ".$wpdb->posts." WHERE post_type = 'page' AND post_status = 'publish' AND ID = '%d'", $intEmailTextSource));
}

else if($intMessageID > 0)
{
	$result = $wpdb->get_results($wpdb->prepare("SELECT messageFrom, messageName, messageText FROM ".$wpdb->base_prefix."group_message WHERE messageID = '%d'", $intMessageID));

	foreach($result as $r)
	{
		$strMessageFrom = $r->messageFrom;
		$strMessageName = $r->messageName;
		$strMessageText = stripslashes($r->messageText);
	}
}

echo "<div class='wrap'>
	<h2>".__("Send message", 'lang_group')."</h2>" //." ".get_group_name($intGroupID)
	.get_notification()
	."<div id='poststuff'>
		<form action='#' method='post' class='mf_form mf_settings'>
			<div id='post-body' class='columns-2'>
				<div id='post-body-content'>
					<div class='postbox'>
						<h3 class='hndle'>".__("Message", 'lang_group')."</h3>
						<div class='inside'>";

							$arr_data_to = array();

							$obj_group = new mf_group();

							$result = $obj_group->get_groups();

							foreach($result as $r)
							{
								$arr_data_to[$r->ID] = $r->post_title;
							}

							if($type == "email")
							{
								if($type == "email" && $strMessageText_orig == '')
								{
									$strMessageText .= "<p>&nbsp;</p><p><a href='[unsubscribe_link]'>".__("If you don't want to get these messages in the future click this link to unsubscribe", 'lang_group')."</a></p>";
								}

								$current_user = wp_get_current_user();

								$user_name = $current_user->display_name;
								$user_email = $current_user->user_email;
								$admin_name = get_bloginfo('name');
								$admin_email = get_bloginfo('admin_email');

								$arr_data_from = array();

								$arr_data_from[''] = "-- ".__("Choose here", 'lang_group')." --";

								if($user_email != '')
								{
									$arr_data_from[$user_name."|".$user_email] = $user_name." (".$user_email.")";
								}

								if($admin_email != '' && $admin_email != $user_email)
								{
									$arr_data_from[$admin_name."|".$admin_email] = $admin_name." (".$admin_email.")";
								}

								if(is_plugin_active("mf_email/index.php"))
								{
									$result = $wpdb->get_results("SELECT emailAddress, emailName FROM ".$wpdb->base_prefix."email_users RIGHT JOIN ".$wpdb->base_prefix."email USING (emailID) WHERE (emailPublic = '1' OR emailRoles LIKE '%".get_current_user_role()."%' OR ".$wpdb->base_prefix."email.userID = '".get_current_user_id()."' OR ".$wpdb->base_prefix."email_users.userID = '".get_current_user_id()."') AND emailDeleted = '0' ORDER BY emailUsername ASC"); // AND emailVerified = '1'

									foreach($result as $r)
									{
										$strEmailAddress = $r->emailAddress;
										$strEmailName = $r->emailName;

										if($strEmailAddress != $user_email && $strEmailAddress != $admin_email)
										{
											$arr_data_from[$strEmailName."|".$strEmailAddress] = $strEmailName." (".$strEmailAddress.")";
										}
									}
								}

								echo "<div class='flex_flow'>
									<div>"
										.show_select(array('data' => $arr_data_from, 'name' => 'strMessageFrom', 'text' => __("From", 'lang_group'), 'value' => $strMessageFrom, 'required' => true))
										.show_textfield(array('name' => "strMessageName", 'text' => __("Subject", 'lang_group'), 'value' => $strMessageName, 'required' => true))
									."</div>"
									.show_select(array('data' => $arr_data_to, 'name' => 'arrGroupID[]', 'text' => __("To", 'lang_group'), 'value' => $arrGroupID, 'maxsize' => 5))
								."</div>"
								.show_wp_editor(array('name' => 'strMessageText', 'value' => $strMessageText));
							}

							else if($type == "sms")
							{
								$sms_senders = get_option('setting_sms_senders');
								$sms_phone = get_user_meta(get_current_user_id(), 'mf_sms_phone', true);

								$arr_data_from = array();

								$arr_data_from[''] = "-- ".__("Choose here", 'lang_group')." --";

								foreach(explode(",", $sms_senders) as $sender)
								{
									if($sender != '')
									{
										$arr_data_from[$sender] = $sender;
									}
								}

								if($sms_phone != '')
								{
									$arr_data_from[$sms_phone] = $sms_phone;
								}

								echo show_select(array('data' => $arr_data_from, 'name' => 'strMessageFrom', 'text' => __("From", 'lang_group'), 'value' => $strMessageFrom, 'required' => true))
								.show_select(array('data' => $arr_data_to, 'name' => 'arrGroupID[]', 'text' => __("To", 'lang_group'), 'value' => $arrGroupID, 'maxsize' => 5))
								.show_textarea(array('name' => "strMessageText", 'text' => __("Message", 'lang_group'), 'value' => $strMessageText, 'required' => true));
							}

						echo "</div>
					</div>
				</div>
				<div id='postbox-container-1'>
					<div class='postbox'>
						<h3 class='hndle'>".__("Send", 'lang_group')."</h3>
						<div class='inside'>";

							if($type == "email")
							{
								$arr_data_source = array();
								get_post_children(array('add_choose_here' => true), $arr_data_source);

								echo show_select(array('data' => $arr_data_source, 'name' => 'intEmailTextSource', 'text' => __("Text Source", 'lang_group'), 'xtra' => "rel='submit_change'"))
								.get_media_button(array('name' => "strMessageAttachment", 'value' => $strMessageAttachment));
							}

							echo show_button(array('name' => "btnGroupSend", 'text' => __("Send", 'lang_group')))
							.wp_nonce_field('group_send_'.$type, '_wpnonce', true, false);

							if($type == "sms")
							{
								echo " <span id='chars_left'></span> (<span id='sms_amount'>1</span>)";
							}

							echo input_hidden(array('name' => "type", 'value' => $type))
						."</div>
					</div>
				</div>
			<div>
		</form>
	</div>
</div>";