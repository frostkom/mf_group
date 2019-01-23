<?php

get_header();

	if(have_posts())
	{
		$obj_group = new mf_group();

		echo "<article>";

			while(have_posts())
			{
				the_post();

				$post_id = $post->ID;
				//$post_status = $post->post_status;
				$post_title = $post->post_title;

				$post_allow_registration = get_post_meta_or_default($post_id, $obj_group->meta_prefix.'allow_registration', true, 'no');

				$post_content = "";

				$done_text = $error_text = "";

				if(isset($_REQUEST['subscribe']))
				{
					$strSubscribeHash = check_var('subscribe', 'char');
					//$strVerifyHash = check_var('verify', 'char');

					$intGroupID = check_var('gid', 'int');
					$strAddressEmail = check_var('aem', 'char');

					$hash_temp = md5((defined('NONCE_SALT') ? NONCE_SALT : '').$intGroupID.$strAddressEmail);

					if($strSubscribeHash == $hash_temp)
					{
						$intAddressID = $wpdb->get_var($wpdb->prepare("SELECT addressID FROM ".get_address_table_prefix()."address INNER JOIN ".$wpdb->prefix."address2group USING (addressID) WHERE groupID = '%d' AND addressEmail = %s AND addressDeleted = '0' AND groupAccepted = '0' LIMIT 0, 1", $intGroupID, $strAddressEmail));

						if($intAddressID > 0)
						{
							if($obj_group->accept_address(array('address_id' => $intAddressID, 'group_id' => $intGroupID)))
							{
								$done_text = __("You have successfully subscribed", 'lang_group');
							}

							else
							{
								$error_text = __("You've already accepted to be a part of the group", 'lang_group');
							}
						}

						else
						{
							$error_text = __("Either you're not part of the group or you've already accepted to be a part of the group", 'lang_group');
						}
					}

					else
					{
						$error_text = __("Something went wrong. Please contact an admin if the problem persists", 'lang_group');
					}
				}

				else if(isset($_REQUEST['unsubscribe']) || isset($_REQUEST['verify']))
				{
					$intGroupSentAmount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(groupID) FROM ".$wpdb->prefix."group_message WHERE groupID = '%d' AND messageDeleted = '0'", $post_id));

					if($post_allow_registration == 'yes' || $intGroupSentAmount > 0)
					{
						$strUnsubscribeHash = check_var('unsubscribe', 'char');
						$strVerifyHash = check_var('verify', 'char');

						$intGroupID = check_var('gid', 'int');
						$strAddressEmail = check_var('aem', 'char');
						$intQueueID = check_var('qid', 'int');

						$hash_temp = md5((defined('NONCE_SALT') ? NONCE_SALT : '').$intGroupID.$strAddressEmail);

						if($strUnsubscribeHash != '')
						{
							if($strUnsubscribeHash == $hash_temp)
							{
								$intAddressID = $wpdb->get_var($wpdb->prepare("SELECT addressID FROM ".get_address_table_prefix()."address INNER JOIN ".$wpdb->prefix."address2group USING (addressID) WHERE groupID = '%d' AND addressEmail = %s AND addressDeleted = '0' AND groupUnsubscribed = '0' LIMIT 0, 1", $intGroupID, $strAddressEmail));

								if($intAddressID > 0)
								{
									if(isset($_POST['btnUnsubscribe']))
									{
										$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."address2group SET groupUnsubscribed = '1' WHERE groupID = '%d' AND addressID = '%d'", $intGroupID, $intAddressID));

										$done_text = __("You have been successfully unsubscribed", 'lang_group');
									}

									else
									{
										$post_content .= "<form method='post' action='' class='mf_form'>
											<p>".__("Are you sure that you want to unsubscribe?", 'lang_group')."</p>
											<div class='form_button'>"
												.show_button(array('name' => 'btnUnsubscribe', 'text' => __("Unsubscribe", 'lang_group')))
											."</div>"
											.input_hidden(array('name' => 'unsubscribe', 'value' => $strUnsubscribeHash))
											.input_hidden(array('name' => 'gid', 'value' => $intGroupID))
											.input_hidden(array('name' => 'aem', 'value' => $strAddressEmail))
										."</form>";
									}
								}

								else
								{
									$error_text = __("Either you're not part of the group or you've already unsubscribed from it", 'lang_group');
								}
							}

							else
							{
								$error_text = __("Something went wrong. Please contact an admin if the problem persists", 'lang_group');
							}
						}

						if($strUnsubscribeHash != '' && $strUnsubscribeHash == $hash_temp || $strVerifyHash != '' && $strVerifyHash == $hash_temp)
						{
							if($intQueueID > 0)
							{
								$result = $wpdb->get_results($wpdb->prepare("SELECT addressID FROM ".get_address_table_prefix()."address INNER JOIN ".$wpdb->prefix."group_queue USING (addressID) WHERE addressEmail = %s AND queueID = '%d'", $strAddressEmail, $intQueueID));

								foreach($result as $r)
								{
									$intAddressID = $r->addressID;

									$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."group_queue SET queueReceived = '1' WHERE queueID = '%d' AND addressID = '%d'", $intQueueID, $intAddressID));

									$obj_address = new mf_address(array('id' => $intAddressID));
									$obj_address->update_errors(array('action' => 'reset'));
								}
							}
						}
					}
				}

				else if(isset($_REQUEST['view_in_browser']))
				{
					$strViewHash = check_var('view_in_browser', 'char');

					$intGroupID = check_var('gid', 'int');
					$strAddressEmail = check_var('aem', 'char');
					$intMessageID = check_var('mid', 'int');

					$hash_temp = md5((defined('NONCE_SALT') ? NONCE_SALT : '').$intGroupID.$strAddressEmail.$intMessageID);

					if($strViewHash == $hash_temp)
					{
						$result = $wpdb->get_results($wpdb->prepare("SELECT messageName, messageText FROM ".$wpdb->prefix."group_message WHERE messageID = '%d'", $intMessageID));

						foreach($result as $r)
						{
							$strMessageName = $r->messageName;
							$strMessageText = stripslashes(apply_filters('the_content', $r->messageText));

							$view_in_browser_url = $obj_group->get_group_url(array('type' => 'view_in_browser', 'group_id' => $intGroupID, 'message_id' => $intMessageID, 'email' => $strAddressEmail));
							$unsubscribe_url = $obj_group->get_group_url(array('type' => 'unsubscribe', 'group_id' => $intGroupID, 'email' => $strAddressEmail));

							$arr_exclude = array("[view_in_browser_link]", "[message_name]", "[unsubscribe_link]");
							$arr_include = array($view_in_browser_url, $strMessageName, $unsubscribe_url);

							$post_title = $strMessageName;
							$post_content = str_replace($arr_exclude, $arr_include, $strMessageText);
						}
					}

					else
					{
						$error_text = __("Something went wrong. Please contact an admin if the problem persists", 'lang_group');
					}
				}

				$post_content .= get_notification();

				/*if($post_content != '' || $post_allow_registration == 'yes')
				{*/
					echo "<h1>".$post_title."</h1>
					<section>";

						if($post_content != '')
						{
							echo $post_content;
						}

						else if($post_allow_registration == 'yes')
						{
							echo $obj_group->show_group_registration_form(array('id' => $post_id));
						}

						else
						{
							echo apply_filters('the_content', __("This group is closed for registration", 'lang_group'));
						}

					echo "</section>";
				/*}

				else
				{
					wp_redirect("/404/");
				}*/
			}

		echo "</article>";
	}

get_footer();