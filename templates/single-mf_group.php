<?php

if(isset($_REQUEST['redirect']))
{
	$strViewHash = check_var('redirect', 'char');
	$intQueueID = check_var('qid', 'int');
	$intLinkID = check_var('lid', 'int');

	if($intQueueID > 0)
	{
		$result = $wpdb->get_results($wpdb->prepare("SELECT groupID, messageID, addressEmail FROM ".$wpdb->prefix."address INNER JOIN ".$wpdb->prefix."group_queue USING (addressID) INNER JOIN ".$wpdb->prefix."group_message USING (messageID) WHERE queueID = '%d'", $intQueueID));

		foreach($result as $r)
		{
			$intGroupID = $r->groupID;
			$intMessageID = $r->messageID;
			$strAddressEmail = $r->addressEmail;
		}
	}

	else
	{
		$intGroupID = check_var('gid', 'int');
		$intMessageID = check_var('mid', 'int');
		$strAddressEmail = check_var('aem', 'char');
	}

	$hash_temp = md5((defined('NONCE_SALT') ? NONCE_SALT : '').$intGroupID.$strAddressEmail.$intMessageID);

	if($strViewHash == $hash_temp)
	{
		$strLinkUrl = $wpdb->get_var($wpdb->prepare("SELECT linkUrl FROM ".$wpdb->prefix."group_message_link WHERE linkID = '%d'", $intLinkID));

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."group_message_link SET linkUsed = NOW() WHERE linkID = '%d'", $intLinkID));
		$obj_group->set_received(array('queue_id' => $intQueueID));

		mf_redirect($strLinkUrl);
	}

	else
	{
		$error_text = __("Something went wrong. Please contact an admin if the problem persists", 'lang_group');
	}
}

get_header();

	if(have_posts())
	{
		$obj_group = new mf_group();

		echo "<article".(IS_ADMINISTRATOR ? " class='single-mf_group'" : "").">";

			while(have_posts())
			{
				the_post();

				$post_id = $post->ID;
				$post_title = $post->post_title;

				$post_allow_registration = get_post_meta_or_default($post_id, $obj_group->meta_prefix.'allow_registration', true, 'no');

				$post_content = "";

				$done_text = $error_text = "";

				if(isset($_REQUEST['subscribe']))
				{
					$strSubscribeHash = check_var('subscribe', 'char');

					$intGroupID = check_var('gid', 'int');
					$strAddressEmail = check_var('aem', 'char');

					$hash_temp = md5((defined('NONCE_SALT') ? NONCE_SALT : '').$intGroupID.$strAddressEmail);

					if($strSubscribeHash == $hash_temp)
					{
						$intAddressID = $wpdb->get_var($wpdb->prepare("SELECT addressID FROM ".$wpdb->prefix."address INNER JOIN ".$wpdb->prefix."address2group USING (addressID) WHERE groupID = '%d' AND addressEmail = %s AND addressDeleted = '0' AND groupAccepted = '0' LIMIT 0, 1", $intGroupID, $strAddressEmail));

						if($intAddressID > 0)
						{
							if($obj_group->accept_address(array('address_id' => $intAddressID, 'group_id' => $intGroupID)))
							{
								$done_text = __("You have successfully subscribed", 'lang_group');
							}

							else
							{
								$error_text = __("You have already accepted to be a part of the group", 'lang_group');
							}
						}

						else
						{
							$error_text = __("Either you are not part of the group or you have already accepted to be a part of the group", 'lang_group');
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
						$intQueueID = check_var('qid', 'int');

						$intGroupID = check_var('gid', 'int');
						$strAddressEmail = check_var('aem', 'char');

						if($intQueueID > 0)
						{
							$result = $wpdb->get_results($wpdb->prepare("SELECT groupID, addressEmail FROM ".$wpdb->prefix."address INNER JOIN ".$wpdb->prefix."group_queue USING (addressID) INNER JOIN ".$wpdb->prefix."group_message USING (messageID) WHERE queueID = '%d'", $intQueueID));

							foreach($result as $r)
							{
								$intGroupID = $r->groupID;
								$strAddressEmail = $r->addressEmail;
							}
						}

						$hash_temp = md5((defined('NONCE_SALT') ? NONCE_SALT : '').$intGroupID.$strAddressEmail);

						if($strUnsubscribeHash != '')
						{
							if($strUnsubscribeHash == $hash_temp)
							{
								$intAddressID = $wpdb->get_var($wpdb->prepare("SELECT addressID FROM ".$wpdb->prefix."address INNER JOIN ".$wpdb->prefix."address2group USING (addressID) WHERE groupID = '%d' AND addressEmail = %s AND addressDeleted = '0' AND groupUnsubscribed = '0' LIMIT 0, 1", $intGroupID, $strAddressEmail));

								if($intAddressID > 0)
								{
									if(isset($_POST['btnUnsubscribe']))
									{
										if($obj_group->unsubscribe_address(array('address_id' => $intAddressID, 'group_id' => $intGroupID)))
										{
											$done_text = __("You have been successfully unsubscribed", 'lang_group');
										}

										else
										{
											do_log("Unsubscribe Error: ".$wpdb->last_query);

											$done_text = __("I could not unsubscribe you. An admin has been notified about this", 'lang_group');
										}
									}

									else
									{
										$post_content .= "<form method='post' action='' class='mf_form'>
											<p>".__("Are you sure that you want to unsubscribe?", 'lang_group')."</p>
											<div".get_form_button_classes().">"
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
									$error_text = __("Either you are not part of the group or you have already unsubscribed from it", 'lang_group');
								}
							}

							else
							{
								$error_text = __("Something went wrong. Please contact an admin if the problem persists", 'lang_group');
							}
						}

						if($strUnsubscribeHash != '' && $strUnsubscribeHash == $hash_temp || $strVerifyHash != '' && $strVerifyHash == $hash_temp)
						{
							$obj_group->set_received(array('queue_id' => $intQueueID));
						}
					}
				}

				else if(isset($_REQUEST['view_in_browser']))
				{
					$strViewHash = check_var('view_in_browser', 'char');
					$intQueueID = check_var('qid', 'int');

					if($intQueueID > 0)
					{
						$result = $wpdb->get_results($wpdb->prepare("SELECT groupID, messageID, addressEmail FROM ".$wpdb->prefix."address INNER JOIN ".$wpdb->prefix."group_queue USING (addressID) INNER JOIN ".$wpdb->prefix."group_message USING (messageID) WHERE queueID = '%d'", $intQueueID));

						foreach($result as $r)
						{
							$intGroupID = $r->groupID;
							$intMessageID = $r->messageID;
							$strAddressEmail = $r->addressEmail;
						}
					}

					else
					{
						$intGroupID = check_var('gid', 'int');
						$intMessageID = check_var('mid', 'int');
						$strAddressEmail = check_var('aem', 'char');
					}

					$hash_temp = md5((defined('NONCE_SALT') ? NONCE_SALT : '').$intGroupID.$strAddressEmail.$intMessageID);

					if($strViewHash == $hash_temp)
					{
						$result = $wpdb->get_results($wpdb->prepare("SELECT messageName, messageText FROM ".$wpdb->prefix."group_message WHERE messageID = '%d'", $intMessageID));

						foreach($result as $r)
						{
							$strMessageName = $r->messageName;
							$strMessageText = stripslashes(apply_filters('the_content', $r->messageText));

							$view_in_browser_url = $obj_group->get_group_url(array('type' => 'view_in_browser', 'group_id' => $intGroupID, 'message_id' => $intMessageID, 'email' => $strAddressEmail, 'queue_id' => $intQueueID));
							$unsubscribe_url = $obj_group->get_group_url(array('type' => 'unsubscribe', 'group_id' => $intGroupID, 'email' => $strAddressEmail, 'queue_id' => $intQueueID));

							$arr_exclude = array("[view_in_browser_link]", "[message_name]", "[unsubscribe_link]");
							$arr_include = array($view_in_browser_url, $strMessageName, $unsubscribe_url);

							$post_title = $strMessageName;
							$post_content = str_replace($arr_exclude, $arr_include, $strMessageText);

							$obj_group->set_received(array('queue_id' => $intQueueID));
						}
					}

					else
					{
						$error_text = __("Something went wrong. Please contact an admin if the problem persists", 'lang_group');
					}
				}

				$post_content .= get_notification();

				echo "<h1>".$post_title."</h1>
				<section>";

					if($post_content != '')
					{
						echo $post_content;
					}

					else if($post_allow_registration == 'yes')
					{
						// Maybe redirect to page if any has the block?

						echo apply_filters('the_content', __("This is an old link and not in use anymore. An admin has been notified by this", 'lang_group'));

						do_log("single-mf_group.php: Add a block instead (<a href='".admin_url("post.php?post=".$post_id."&action=edit")."'>#".$post_id."</a>)", 'publish', false);
					}

					else
					{
						echo apply_filters('the_content', __("This group is closed for registration", 'lang_group'));
					}

				echo "</section>";
			}

		echo "</article>";
	}

get_footer();