<?php

get_header();

	if(have_posts())
	{
		echo "<article>";

			while(have_posts())
			{
				the_post();

				$post_id = $post->ID;
				$post_status = $post->post_status;
				$post_title = $post->post_title;

				$intGroupSentAmount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(groupID) FROM ".$wpdb->base_prefix."group_message WHERE groupID = '%d'", $post_id));

				if($post_status == 'publish' || $intGroupSentAmount > 0)
				{
					$out = "";

					if($intGroupSentAmount > 0)
					{
						$strUnsubscribeHash = check_var('unsubscribe', 'char');
						$strVerifyHash = check_var('verify', 'char');

						$intGroupID = check_var('gid', 'int');
						$strAddressEmail = check_var('aem', 'char');
						$intQueueID = check_var('qid', 'int');

						$hash_temp = md5(NONCE_SALT.$intGroupID.$strAddressEmail);

						if($strUnsubscribeHash != '')
						{
							if($strUnsubscribeHash == $hash_temp)
							{
								$result = $wpdb->get_results($wpdb->prepare("SELECT addressID FROM ".$wpdb->base_prefix."address INNER JOIN ".$wpdb->base_prefix."address2group USING (addressID) WHERE groupID = '%d' AND addressEmail = %s AND groupUnsubscribed = '0' LIMIT 0, 1", $intGroupID, $strAddressEmail));

								if($wpdb->num_rows > 0)
								{
									if(isset($_POST['btnUnsubscribe']))
									{
										foreach($result as $r)
										{
											$intAddressID = $r->addressID;

											$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."address2group SET groupUnsubscribed = '1' WHERE groupID = '%d' AND addressID = '%d'", $intGroupID, $intAddressID));
										}

										$out .= __("You have been successfully unsubscribed", 'lang_group');
									}

									else
									{
										$out .= "<form method='post' action='' class='mf_form'>
											<p>".__("Are you sure that you want to unsubscribe?", 'lang_group')."</p>
											<div class='form_button'>"
												.show_submit(array('name' => "btnUnsubscribe", 'text' => __("Unsubscribe", 'lang_group')))
											."</div>"
											.input_hidden(array('name' => 'unsubscribe', 'value' => $strUnsubscribeHash))
											.input_hidden(array('name' => 'gid', 'value' => $intGroupID))
											.input_hidden(array('name' => 'aem', 'value' => $strAddressEmail))
										."</form>";
									}
								}

								else
								{
									$out .= __("Either you're not part of the group or you've already unsubscribed from it", 'lang_group');
								}
							}

							else
							{
								$out .= __("Something went wrong. Please contact an admin if the problem persists", 'lang_group');
							}
						}

						if($strUnsubscribeHash != '' && $strUnsubscribeHash == $hash_temp || $strVerifyHash != '' && $strVerifyHash == $hash_temp)
						{
							if($intQueueID > 0)
							{
								$result = $wpdb->get_results($wpdb->prepare("SELECT addressID FROM ".$wpdb->base_prefix."address INNER JOIN ".$wpdb->base_prefix."group_queue USING (addressID) WHERE addressEmail = %s AND queueID = '%d'", $strAddressEmail, $intQueueID));

								foreach($result as $r)
								{
									$intAddressID = $r->addressID;

									$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."group_queue SET queueReceived = '1' WHERE queueID = '%d' AND addressID = '%d'", $intQueueID, $intAddressID));

									$obj_address = new mf_address($intAddressID);
									$obj_address->update_errors(array('action' => 'reset'));
								}
							}
						}
					}

					echo "<h1>".$post_title."</h1>
					<section>";

						if($out != '')
						{
							echo $out;
						}

						else if($post_status == 'publish')
						{
							echo show_group_registration_form($post_id);
						}

					echo "</section>";
				}

				else
				{
					if(is_user_logged_in() && IS_ADMIN)
					{
						echo $post_status.", ".$intGroupSentAmount;
					}

					//wp_redirect("/404/");
				}
			}

		echo "</article>";
	}

get_footer();