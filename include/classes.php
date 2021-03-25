<?php

class mf_group
{
	function __construct($data = array())
	{
		if(!isset($data['id'])){	$data['id'] = 0;}
		if(!isset($data['type'])){	$data['type'] = '';}

		if($data['id'] > 0)
		{
			$this->id = $data['id'];
		}

		else
		{
			$this->id = check_var('intGroupID');
		}

		$this->type = $data['type'];

		$this->post_type = 'mf_group';
		$this->meta_prefix = $this->post_type.'_';
		$this->lang_key = 'lang_group';
	}

	function get_for_select($data = array())
	{
		if(!isset($data['add_choose_here'])){		$data['add_choose_here'] = true;}

		$tbl_group = new mf_group_table();

		$tbl_group->select_data(array(
			'select' => "ID, post_title",
		));

		$arr_data = array();

		if(count($tbl_group->data) > 0)
		{
			if($data['add_choose_here'])
			{
				$arr_data[''] = "-- ".__("Choose Here", $this->lang_key)." --";
			}

			foreach($tbl_group->data as $r)
			{
				$amount = $this->amount_in_group(array('id' => $r['ID']));

				$arr_data[$r['ID']] = array(
					'name' => $r['post_title']." (".$amount.")",
					'attributes' => array(
						'amount' => $amount,
					),
				);
			}
		}

		return $arr_data;
	}

	function get_groups($data = array())
	{
		global $wpdb;

		if(!isset($data['where'])){		$data['where'] = "";}
		if(!isset($data['order'])){		$data['order'] = "post_status ASC, post_title ASC";}
		if(!isset($data['limit'])){		$data['limit'] = "";}
		if(!isset($data['amount'])){	$data['amount'] = "";}

		if(!IS_EDITOR)
		{
			$data['where'] .= " AND post_author = '".get_current_user_id()."'";
		}

		return $wpdb->get_results($wpdb->prepare(
			"SELECT ID, post_status, post_name, post_title, post_modified, post_author FROM ".$wpdb->posts." WHERE post_type = %s".$data['where']." ORDER BY ".$data['order']
			.($data['limit'] != '' && $data['amount'] != '' ? " LIMIT ".$data['limit'].", ".$data['amount'] : "")
		, $this->post_type));
	}

	function get_group_url($data)
	{
		if(!isset($data['message_id'])){	$data['message_id'] = 0;}
		if(!isset($data['queue_id'])){		$data['queue_id'] = 0;}

		$base_url = get_permalink($data['group_id']);
		$base_url .= (preg_match("/\?/", $base_url) ? "&" : "?");

		$out = "";

		switch($data['type'])
		{
			case 'view_in_browser':
				$out .= $base_url
					.$data['type']."=".md5((defined('NONCE_SALT') ? NONCE_SALT : '').$data['group_id'].$data['email'].$data['message_id'])
					."&gid=".$data['group_id']
					."&aem=".$data['email']
					."&mid=".$data['message_id'];
			break;

			case 'subscribe':
			case 'unsubscribe':
			case 'verify':
				$out .= $base_url
					.$data['type']."=".md5((defined('NONCE_SALT') ? NONCE_SALT : '').$data['group_id'].$data['email'])
					."&gid=".$data['group_id']
					."&aem=".$data['email'];

				if($data['queue_id'] > 0)
				{
					$out .= "&qid=".$data['queue_id'];
				}
			break;
		}

		return $out;
	}

	function set_message_sent($intQueueID)
	{
		global $wpdb;

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."group_queue SET queueSent = '1', queueSentTime = NOW() WHERE queueID = '%d'", $intQueueID));
	}

	function show_group_registration_form($data)
	{
		global $wpdb, $obj_address, $done_text, $error_text;

		if(!isset($data['text'])){											$data['text'] = '';}
		if(!isset($data['button_text']) || $data['button_text'] == ''){		$data['button_text'] = __("Join", $this->lang_key);}

		$out = "";

		if(!($data['id'] > 0))
		{
			$error_text = __("I could not find any group ID to display a form for", $this->lang_key);
		}

		$arrGroupRegistrationFields = get_post_meta($data['id'], $this->meta_prefix.'registration_fields', true);

		$strAddressName = check_var('strAddressName');
		$intAddressZipCode = check_var('intAddressZipCode');
		$strAddressCity = check_var('strAddressCity');
		$intAddressCountry = check_var('intAddressCountry');
		$strAddressAddress = check_var('strAddressAddress');
		$strAddressTelNo = check_var('strAddressTelNo');
		$strAddressEmail = check_var('strAddressEmail');
		$strAddressExtra = check_var('strAddressExtra');

		if(isset($_POST['btnGroupJoin']))
		{
			$strGroupVerifyAddress = get_post_meta($data['id'], $this->meta_prefix.'verify_address', true);

			$query_where = $query_set = "";

			if(is_array($arrGroupRegistrationFields) && count($arrGroupRegistrationFields) > 0)
			{
				if(in_array("name", $arrGroupRegistrationFields))
				{
					@list($strAddressFirstName, $strAddressSurName) = explode(" ", $strAddressName, 2);

					$query_where .= ($query_where != '' ? " AND " : "")."addressFirstName = '".$strAddressFirstName."' AND addressSurName = '".$strAddressSurName."'";
					$query_set .= ", addressFirstName = '".esc_sql($strAddressFirstName)."', addressSurName = '".esc_sql($strAddressSurName)."'";
				}

				if(in_array("address", $arrGroupRegistrationFields))
				{
					$query_where .= ($query_where != '' ? " AND " : "")."addressAddress = '".$strAddressAddress."'";
					$query_set .= ", addressAddress = '".esc_sql($strAddressAddress)."'";
				}

				if(in_array("zip", $arrGroupRegistrationFields))
				{
					$query_where .= ($query_where != '' ? " AND " : "")."addressZipCode = '".$intAddressZipCode."'";
					$query_set .= ", addressZipCode = '".esc_sql($intAddressZipCode)."'";
				}

				if(in_array("city", $arrGroupRegistrationFields))
				{
					$query_where .= ($query_where != '' ? " AND " : "")."addressCity = '".$strAddressCity."'";
					$query_set .= ", addressCity = '".esc_sql($strAddressCity)."'";
				}

				if(in_array("country", $arrGroupRegistrationFields))
				{
					$query_where .= ($query_where != '' ? " AND " : "")."addressCountry = '".$intAddressCountry."'";
					$query_set .= ", addressCountry = '".esc_sql($intAddressCountry)."'";
				}

				if(in_array("phone", $arrGroupRegistrationFields))
				{
					$query_where .= ($query_where != '' ? " AND " : "")."addressTelNo = '".$strAddressTelNo."'";
					$query_set .= ", addressTelNo = '".esc_sql($strAddressTelNo)."'";
				}

				if(in_array("email", $arrGroupRegistrationFields))
				{
					$query_where .= ($query_where != '' ? " AND " : "")."addressEmail = '".$strAddressEmail."'";
					$query_set .= ", addressEmail = '".esc_sql($strAddressEmail)."'";
				}

				if(in_array("extra", $arrGroupRegistrationFields))
				{
					$query_where .= ($query_where != '' ? " AND " : "")."addressExtra = '".$strAddressExtra."'";
					$query_set .= ", addressExtra = '".esc_sql($strAddressExtra)."'";
				}
			}

			else
			{
				$query_where .= ($query_where != '' ? " AND " : "")."addressEmail = '".$strAddressEmail."'";
				$query_set .= ", addressEmail = '".esc_sql($strAddressEmail)."'";
			}

			$intAddressID = $this->check_if_address_exists($query_where);

			if($strGroupVerifyAddress == "yes" && !($intAddressID > 0))
			{
				$out .= "<p>".__("The information that you entered is not in our register. Please contact the admin of this page to get your information submitted.", $this->lang_key)."</p>";

				$intGroupContactPage = get_post_meta($data['id'], $this->meta_prefix.'contact_page', true);

				if($intGroupContactPage > 0)
				{
					$post_url = get_permalink($intGroupContactPage);
					$post_title = get_the_title($intGroupContactPage);

					if($strAddressEmail != '')
					{
						global $obj_form;

						if(!isset($obj_form))
						{
							$obj_form = new mf_form();
						}

						$obj_form->get_form_id_from_post_content($intGroupContactPage);

						if($obj_form->id > 0)
						{
							$form_field_id = $obj_form->get_post_info()."_".$obj_form->get_form_email_field();

							if($form_field_id != '')
							{
								$post_url .= "?".$form_field_id."=".$strAddressEmail;
							}
						}
					}

					$out .= "<p><a href='".$post_url."'>".$post_title."</a></p>";
				}
			}

			else
			{
				if(!($intAddressID > 0))
				{
					$wpdb->query("INSERT INTO ".get_address_table_prefix()."address SET addressPublic = '1'".$query_set.", addressCreated = NOW()");

					$intAddressID = $wpdb->insert_id;
				}

				if($intAddressID > 0)
				{
					$this->add_address(array('address_id' => $intAddressID, 'group_id' => $data['id']));

					$done_text = __("Thank you for showing your interest. You have been added to the group", $this->lang_key);
				}
			}
		}

		if(isset($done_text) && $done_text != '' || isset($error_text) && $error_text != '')
		{
			$out .= get_notification();
		}

		else
		{
			$out .= "<form action='' method='post' class='mf_form'>";

				if($data['text'] != '')
				{
					$out .= apply_filters('the_content', $data['text']);
				}

				if(is_array($arrGroupRegistrationFields) && count($arrGroupRegistrationFields) > 0)
				{
					if(in_array("name", $arrGroupRegistrationFields))
					{
						$out .= show_textfield(array('name' => 'strAddressName', 'text' => __("Name", $this->lang_key), 'value' => $strAddressName, 'required' => true));
					}

					if(in_array("address", $arrGroupRegistrationFields))
					{
						$out .= show_textfield(array('name' => 'strAddressAddress', 'text' => __("Address", $this->lang_key), 'value' => $strAddressAddress, 'required' => true));
					}

					if(in_array("zip", $arrGroupRegistrationFields))
					{
						$out .= show_textfield(array('name' => 'intAddressZipCode', 'text' => __("Zip Code", $this->lang_key), 'value' => $intAddressZipCode, 'type' => 'number', 'required' => true));
					}

					if(in_array("city", $arrGroupRegistrationFields))
					{
						$out .= show_textfield(array('name' => 'strAddressCity', 'text' => __("City", $this->lang_key), 'value' => $strAddressCity, 'required' => true));
					}

					if(in_array("country", $arrGroupRegistrationFields))
					{
						if(!isset($obj_address))
						{
							$obj_address = new mf_address();
						}

						$out .= show_select(array('data' => $obj_address->get_countries_for_select(), 'name' => 'intAddressCountry', 'text' => __("Country", $this->lang_key), 'value' => $intAddressCountry, 'required' => true));
					}

					if(in_array("phone", $arrGroupRegistrationFields))
					{
						$out .= show_textfield(array('name' => 'strAddressTelNo', 'text' => __("Phone Number", $this->lang_key), 'value' => $strAddressTelNo, 'required' => true));
					}

					if(in_array("email", $arrGroupRegistrationFields))
					{
						$out .= show_textfield(array('name' => 'strAddressEmail', 'text' => __("E-mail", $this->lang_key), 'value' => $strAddressEmail, 'required' => true));
					}

					if(in_array("extra", $arrGroupRegistrationFields))
					{
						$out .= show_textfield(array('name' => 'strAddressExtra', 'text' => get_option_or_default('setting_address_extra', __("Extra", $this->lang_key)), 'value' => $strAddressExtra, 'required' => true));
					}

					$out .= "<div class='form_button'>"
						.show_checkbox(array('name' => 'intGroupConsent', 'text' => __("I consent to having this website store my submitted information, so that they can contact me as part of this newsletter", $this->lang_key), 'value' => 1, 'required' => true))
						.show_button(array('name' => 'btnGroupJoin', 'text' => $data['button_text']))
					."</div>";
				}

				else
				{
					$out .= "<div class='flex_form'>"
						.show_textfield(array('name' => 'strAddressEmail', 'placeholder' => __("Your Email Address", $this->lang_key), 'value' => $strAddressEmail, 'required' => true))
						."<div class='form_button'>"
							.show_button(array('name' => 'btnGroupJoin', 'text' => $data['button_text']))
						."</div>
					</div>"
					.show_checkbox(array('name' => 'intGroupConsent', 'text' => __("I consent to having this website store my submitted information, so that they can contact me as part of this newsletter", $this->lang_key), 'value' => 1, 'required' => true));
				}

			$out .= "</form>";
		}

		return $out;
	}

	function get_email_address_from_id($id)
	{
		global $wpdb;

		return $wpdb->get_var($wpdb->prepare("SELECT emailAddress FROM ".$wpdb->base_prefix."email WHERE emailID = '%d'", $id));
	}

	function check_if_exists($data)
	{
		global $wpdb;

		if(!isset($data['birthdate'])){		$data['birthdate'] = '';}
		if(!isset($data['email'])){			$data['email'] = '';}

		if($data['birthdate'] != '')
		{
			$query_where = "(addressBirthDate = '".esc_sql($data['birthdate'])."')";
		}

		else
		{
			$query_where = "1 = 2";
		}

		$result = $wpdb->get_results("SELECT addressID FROM ".get_address_table_prefix()."address WHERE addressDeleted = '0' AND ".$query_where);
		$rows = $wpdb->num_rows;

		if($rows == 0)
		{
			if($data['email'] != '')
			{
				$query_where = "(addressEmail = '".esc_sql($data['email'])."')";
			}

			else
			{
				$query_where = "1 = 2";
			}

			$result = $wpdb->get_results("SELECT addressID FROM ".get_address_table_prefix()."address WHERE addressDeleted = '0' AND ".$query_where);
			$rows = $wpdb->num_rows;
		}

		return $result;
	}

	function cron_base()
	{
		global $wpdb, $error_text;

		$obj_cron = new mf_cron();
		$obj_cron->start(__CLASS__);

		if($obj_cron->is_running == false)
		{
			/* Send group messages */
			#############################
			$setting_group_outgoing_text = get_option('setting_group_outgoing_text');

			$result = $wpdb->get_results("SELECT groupID FROM ".$wpdb->prefix."group_queue INNER JOIN ".$wpdb->prefix."group_message USING (messageID) WHERE queueSent = '0' AND messageDeleted = '0' AND (messageSchedule IS NULL OR messageSchedule < NOW()) GROUP BY groupID ORDER BY RAND()");

			foreach($result as $r)
			{
				$intGroupID = $r->groupID;

				$strGroupVerifyLink = get_post_meta($intGroupID, $this->meta_prefix.'verify_link', true);

				$intGroupOwnerEmail = get_post_meta($intGroupID, $this->meta_prefix.'owner_email', true);
				$intGroupHelpPage = get_post_meta($intGroupID, $this->meta_prefix.'help_page', true);
				$intGroupArchivePage = get_post_meta($intGroupID, $this->meta_prefix.'archive_page', true);

				$group_url = get_permalink($intGroupID);

				$intMessageID_temp = 0;

				$resultAddresses = $wpdb->get_results($wpdb->prepare("SELECT queueID, messageID, addressEmail, addressCellNo FROM ".get_address_table_prefix()."address INNER JOIN ".$wpdb->prefix."group_queue USING (addressID) INNER JOIN ".$wpdb->prefix."group_message USING (messageID) WHERE groupID = '%d' AND messageDeleted = '0' AND queueSent = '0' ORDER BY messageType ASC, queueCreated ASC".$this->get_message_query_limit(), $intGroupID));

				foreach($resultAddresses as $r)
				{
					$intQueueID = $r->queueID;
					$intMessageID = $r->messageID;
					$strAddressEmail = trim($r->addressEmail);
					$strAddressCellNo = $r->addressCellNo;

					if($intMessageID != $intMessageID_temp)
					{
						$resultMessage = $wpdb->get_results($wpdb->prepare("SELECT messageType, messageFrom, messageName, messageText, messageAttachment, userID FROM ".$wpdb->prefix."group_message WHERE messageID = '%d'", $intMessageID));

						foreach($resultMessage as $r)
						{
							$strMessageType = $r->messageType;
							$strMessageFrom = $r->messageFrom;
							$strMessageName = $r->messageName;
							$strMessageText = $r->messageText;
							$strMessageAttachment = $r->messageAttachment;
							$intUserID = $r->userID;
						}

						$intMessageID_temp = $intMessageID;

						switch($strMessageType)
						{
							case 'email':
								$strMessageFromName = "";

								if(preg_match("/\|/", $strMessageFrom))
								{
									list($strMessageFromName, $strMessageFrom) = explode("|", $strMessageFrom);
								}

								else
								{
									$strMessageFromName = $strMessageFrom;
								}

								$strMessageText = stripslashes(apply_filters('the_content', $strMessageText));

								if($setting_group_outgoing_text != '')
								{
									$strMessageText .= apply_filters('the_content', $setting_group_outgoing_text);
								}

								do_action('group_init_message', array('group_id' => $intGroupID, 'message_id' => $intMessageID, 'from_name' => $strMessageFromName, 'from' => $strMessageFrom, 'subject' => $strMessageName, 'content' => $strMessageText, 'alt_content' => $strMessageText));
							break;

							default:
								do_action('group_init_other');
							break;

							/*case 'sms':
								//Must be here to make sure that send_sms() is loaded
								##################
								require_once(ABSPATH."wp-admin/includes/plugin.php");

								if(is_plugin_active("mf_sms/index.php"))
								{
									require_once(ABSPATH."wp-content/plugins/mf_sms/include/classes.php");
								}
								##################

								$obj_sms = new mf_sms();
							break;*/
						}
					}

					switch($strMessageType)
					{
						case 'email':
							if($strAddressEmail != '' && is_domain_valid($strAddressEmail))
							{
								$send = true;
								$send = apply_filters('group_before_send', $send, array('group_id' => $intGroupID, 'to' => $strAddressEmail, 'message_id' => $intMessageID, 'queue_id' => $intQueueID));

								if($send == true)
								{
									if(apply_filters('get_emails_left_to_send', 0, $strMessageFrom, 'group') > 0)
									{
										$view_in_browser_url = $this->get_group_url(array('type' => 'view_in_browser', 'group_id' => $intGroupID, 'message_id' => $intMessageID, 'email' => $strAddressEmail));
										$unsubscribe_url = $this->get_group_url(array('type' => 'unsubscribe', 'group_id' => $intGroupID, 'email' => $strAddressEmail, 'queue_id' => $intQueueID));

										$mail_headers = "From: ".$strMessageFromName." <".$strMessageFrom.">\r\n";
										$mail_headers .= "List-Unsubscribe: <".$unsubscribe_url.">\r\n";
										//$mail_headers .= "List-Subscribe: ".$subscribe_email."<".$group_url.">\r\n";

										if($intGroupOwnerEmail > 0)
										{
											$mail_headers .= "List-Owner: <mailto:".$this->get_email_address_from_id($intGroupOwnerEmail).">\r\n";
										}

										if($intGroupHelpPage > 0)
										{
											$mail_headers .= "List-Help: <".get_permalink($intGroupHelpPage).">\r\n";
										}

										else
										{
											$mail_headers .= "List-Help: <".$group_url.">\r\n";
										}

										if($intGroupArchivePage > 0)
										{
											$mail_headers .= "List-Archive: <".get_permalink($intGroupArchivePage).">\r\n";
										}

										$mail_to = $strAddressEmail;
										$mail_subject = $strMessageName;

										$arr_exclude = array("[view_in_browser_link]", "[message_name]", "[unsubscribe_link]");
										$arr_include = array($view_in_browser_url, $strMessageName, $unsubscribe_url);

										$mail_content = str_replace($arr_exclude, $arr_include, $strMessageText);

										if($strGroupVerifyLink == 'yes')
										{
											$mail_content .= "<img src='".$this->get_group_url(array('type' => 'verify', 'group_id' => $intGroupID, 'email' => $strAddressEmail, 'queue_id' => $intQueueID))."' style='height: 0; visibility: hidden; width: 0'>";
										}

										list($mail_attachment, $rest) = get_attachment_to_send($strMessageAttachment);

										$wpdb->get_results($wpdb->prepare("SELECT queueID FROM ".$wpdb->prefix."group_queue WHERE queueSent = '1' AND queueID = '%d'", $intQueueID));
										$rows = $wpdb->num_rows;

										if($rows == 0)
										{
											$setting_email_log = get_option('setting_email_log');

											$sent = send_email(array(
												'from' => $strMessageFrom,
												'from_name' => $strMessageFromName,
												'to' => $mail_to,
												'subject' => $mail_subject,
												'content' => $mail_content,
												'headers' => $mail_headers,
												'attachment' => $mail_attachment,
												'save_log' => (is_array($setting_email_log) && in_array('group', $setting_email_log)),
												'save_log_type' => 'group',
											));

											if($sent)
											{
												$this->set_message_sent($intQueueID);
											}
										}
									}

									else
									{
										$hourly_release_time = apply_filters('get_hourly_release_time', '', $strMessageFrom);
										$mins = time_between_dates(array('start' => $hourly_release_time, 'end' => date("Y-m-d H:i:s"), 'type' => 'round', 'return' => 'minutes'));

										do_log("E-mails from ".$strMessageFrom." were rejected. Wait for ".$mins." mins (".$wpdb->last_query.")");
									}
								}
							}

							else
							{
								$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."group_queue WHERE queueID = '%d'", $intQueueID));
							}
						break;

						default:
							$success = apply_filters('group_send_other', array(
								'from' => $strMessageFrom,
								'to' => $strAddressCellNo,
								'message' => $strMessageText,
								'user_id' => $intUserID,
							));

							if($success == true)
							{
								$this->set_message_sent($intQueueID);

								do_log("Not sent to ".$strAddressCellNo, 'trash');
							}

							else
							{
								do_log("Not sent to ".$strAddressCellNo.", ".shorten_text(array('string' => htmlspecialchars($strMessageText), 'limit' => 10)));
							}
						break;

						/*case 'sms':
							$sent = $obj_sms->send_sms(array('from' => $strMessageFrom, 'to' => $strAddressCellNo, 'text' => $strMessageText, 'user_id' => $intUserID));

							if($sent)
							{
								$this->set_message_sent($intQueueID);

								do_log("Not sent to ".$strAddressCellNo, 'trash');
							}

							else
							{
								do_log("Not sent to ".$strAddressCellNo.", ".shorten_text(array('string' => htmlspecialchars($strMessageText), 'limit' => 10)));
							}
						break;*/
					}
				}

				switch($strMessageType)
				{
					case 'email':
						do_action('group_after_send');
					break;
				}
			}
			#############################

			/* Add users to groups that are set to synchronize */
			#############################
			$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = %s AND post_status = %s AND meta_key = %s AND meta_value = %s", $this->post_type, 'publish', $this->meta_prefix.'sync_users', 'yes'));

			foreach($result as $r)
			{
				$intGroupID = $r->ID;

				$this->remove_all_address($intGroupID);

				$users = get_users(array('fields' => 'all'));

				foreach($users as $user)
				{
					$strUserLogin = $user->user_login;
					$strUserFirstName = $user->first_name;
					$strUserSurName = $user->last_name;
					$strUserEmail = $user->user_email;

					$intAddressCountry = get_the_author_meta('profile_country', $user->ID);

					if($strUserFirstName == '' || $strUserSurName == '')
					{
						@list($strUserFirstName, $strUserSurName) = explode(" ", $user->display_name, 2);
					}

					$intAddressID = $wpdb->get_var($wpdb->prepare("SELECT addressID FROM ".get_address_table_prefix()."address WHERE addressExtra = %s", $strUserLogin));

					if($intAddressID > 0)
					{
						$wpdb->query($wpdb->prepare("UPDATE ".get_address_table_prefix()."address SET addressFirstName = %s, addressSurName = %s, addressEmail = %s, addressCountry = '%d', addressExtra = %s WHERE addressID = '%d'", $strUserFirstName, $strUserSurName, $strUserEmail, $intAddressCountry, $strUserLogin, $intAddressID));
					}

					else
					{
						$wpdb->query($wpdb->prepare("INSERT INTO ".get_address_table_prefix()."address SET addressFirstName = %s, addressSurName = %s, addressEmail = %s, addressExtra = %s, addressCreated = NOW()", $strUserFirstName, $strUserSurName, $strUserEmail, $strUserLogin));

						$intAddressID = $wpdb->insert_id;
					}

					if($intAddressID > 0)
					{
						$this->add_address(array('address_id' => $intAddressID, 'group_id' => $intGroupID));
					}
				}
			}
			#############################

			/* Synchronize with API */
			#############################
			$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, meta_value FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = %s AND post_status = %s AND meta_key = %s AND meta_value != ''", $this->post_type, 'publish', $this->meta_prefix.'api'));
			$rows = $wpdb->num_rows;

			if($rows > 0)
			{
				foreach($result as $r)
				{
					$post_id = $r->ID;
					$post_title = $r->post_title;
					$post_meta_api = $r->meta_value;

					$post_meta_api_filter = get_post_meta($post_id, $this->meta_prefix.'api_filter', true);

					$arr_post_meta_api = explode("\n", $post_meta_api);

					if(get_option('setting_group_debug') == 'yes')
					{
						do_log("Group API - ".$post_title." - Sync: ".var_export($arr_post_meta_api, true));
					}

					$count_incoming = $count_found = $count_found_error = $count_inserted = $count_inserted_error = $count_added = $count_exists_in_group = $count_exists_in_array = $count_removed = 0;

					$arr_addresses = array();

					foreach($arr_post_meta_api as $post_meta_api)
					{
						// Empty spaces might appear when exploding the string
						$post_meta_api = trim($post_meta_api);

						list($content, $headers) = get_url_content(array(
							'url' => htmlspecialchars_decode($post_meta_api),
							'catch_head' => true,
						));

						$log_message = __("I could not get a successful result from the Group API", $this->lang_key);

						switch($headers['http_code'])
						{
							case 200:
								$json = json_decode($content, true);

								$log_message_2 = __("The status was wrong in the Group API", $this->lang_key);

								switch($json['status'])
								{
									case true:
									case 'true':
										$count_incoming_temp = count($json['data']);
										$count_incoming += $count_incoming_temp;

										if(isset($json['data']) && $count_incoming_temp > 0)
										{
											if(get_option('setting_group_debug') == 'yes')
											{
												do_log("Group API - ".$post_title." - Returned: ".$post_meta_api." -> ".htmlspecialchars(var_export($json['data'], true)));
											}

											// Insert or update in group
											##################################
											foreach($json['data'] as $item)
											{
												if(isset($item['memberSSN']) || isset($item['email']))
												{
													$intAddressPublic = 1;

													$strAddressBirthDate = $item['memberSSN'];
													$strAddressFirstName = $item['firstname'];
													$strAddressSurName = $item['lastname'];
													$strAddressAddress = $item['cb_adress'];
													$intAddressZipCode = $item['cb_postnr'];
													$strAddressCity = $item['cb_postadress'];
													$strAddressCellNo = $item['cb_telmob'];
													$strAddressEmail = $item['email'];
													$strAddressCo = $intAddressCountry = $strAddressTelNo = $strAddressWorkNo = '';
												}

												else if(isset($item['addressBirthDate']) || isset($item['addressEmail']))
												{
													$intAddressPublic = 1;

													$strAddressBirthDate = $item['addressBirthDate'];
													$strAddressFirstName = $item['addressFirstName'];
													$strAddressSurName = $item['addressSurName'];
													$strAddressAddress = $item['addressAddress'];
													$strAddressCo = $item['addressCo'];
													$intAddressZipCode = $item['addressZipCode'];
													$strAddressCity = $item['addressCity'];
													$intAddressCountry = $item['addressCountry'];
													$strAddressTelNo = $item['addressTelNo'];
													$strAddressCellNo = $item['addressCellNo'];
													$strAddressWorkNo = $item['addressWorkNo'];
													$strAddressEmail = $item['addressEmail'];
												}

												else
												{
													do_log("Group API Error - ".$post_title." - Wrong format in ".$post_meta_api." -> ".htmlspecialchars(var_export($item, true)));

													// If it has been set in a previous loop
													unset($strAddressBirthDate);
												}

												$do_save = true;

												if($post_meta_api_filter != '')
												{
													if(get_option('setting_group_debug') == 'yes')
													{
														do_log("Group API - ".$post_title." - Filter Type: ".$post_meta_api_filter);
													}

													list($filter_type, $filter_rest) = explode(":", $post_meta_api_filter);
													list($filter_field, $filter_values) = explode("=", $filter_rest);
													$arr_filter_values = explode(",", str_replace(array("[", "]"), "", $filter_values));

													switch($filter_type)
													{
														case 'include':
															if(!isset($item[$filter_field]) || !in_array($item[$filter_field], $arr_filter_values))
															{
																if(get_option('setting_group_debug') == 'yes')
																{
																	do_log("Group API - ".$post_title." - Filter Include: ".$filter_field." != ".var_export($arr_filter_values, true));
																}

																$do_save = false;
															}
														break;

														case 'exclude':
															if(!isset($item[$filter_field]) || in_array($item[$filter_field], $arr_filter_values))
															{
																if(get_option('setting_group_debug') == 'yes')
																{
																	do_log("Group API - ".$post_title." - Filter Exclude: ".$filter_field." == ".var_export($arr_filter_values, true));
																}

																$do_save = false;
															}
														break;

														default:
															do_log("Group API Error - Unknown Filter Type: ".$filter_type);
														break;
													}
												}

												if($do_save == true)
												{
													if(isset($strAddressBirthDate) && $strAddressBirthDate != '' || isset($strAddressEmail) && $strAddressEmail != '')
													{
														$result = $this->check_if_exists(array('birthdate' => $strAddressBirthDate, 'email' => $strAddressEmail));
														$rows = $wpdb->num_rows;

														if($rows == 0)
														{
															$wpdb->query($wpdb->prepare("INSERT INTO ".get_address_table_prefix()."address SET addressPublic = '%d', addressBirthDate = %s, addressFirstName = %s, addressSurName = %s, addressZipCode = %s, addressCity = %s, addressCountry = '%d', addressAddress = %s, addressCo = %s, addressTelNo = %s, addressCellNo = %s, addressWorkNo = %s, addressEmail = %s, addressCreated = NOW(), userID = '%d'", $intAddressPublic, $strAddressBirthDate, $strAddressFirstName, $strAddressSurName, $intAddressZipCode, $strAddressCity, $intAddressCountry, $strAddressAddress, $strAddressCo, $strAddressTelNo, $strAddressCellNo, $strAddressWorkNo, $strAddressEmail, get_current_user_id()));

															if($wpdb->rows_affected > 0)
															{
																$intAddressID_temp = $wpdb->insert_id;

																if(get_option('setting_group_debug') == 'yes')
																{
																	do_log("Group API - ".$post_title." - Insert address: ".$intAddressID_temp." (".$strAddressFirstName." ".$strAddressSurName.")");
																}

																$result = $this->check_if_exists(array('birthdate' => $strAddressBirthDate, 'email' => $strAddressEmail));
																$rows = $wpdb->num_rows;

																$count_inserted++;
															}

															else
															{
																do_log("Group API - ".$post_title." - No address was created: ".$wpdb->last_query);

																$count_inserted_error++;
															}
														}

														if($rows > 0)
														{
															foreach($result as $r)
															{
																$intAddressID = $r->addressID;

																if(!in_array($intAddressID, $arr_addresses))
																{
																	$wpdb->query($wpdb->prepare("UPDATE ".get_address_table_prefix()."address SET addressBirthDate = %s, addressFirstName = %s, addressSurName = %s, addressZipCode = %s, addressCity = %s, addressCountry = '%d', addressAddress = %s, addressCo = %s, addressTelNo = %s, addressCellNo = %s, addressWorkNo = %s, addressEmail = %s WHERE addressID = '%d'", $strAddressBirthDate, $strAddressFirstName, $strAddressSurName, $intAddressZipCode, $strAddressCity, $intAddressCountry, $strAddressAddress, $strAddressCo, $strAddressTelNo, $strAddressCellNo, $strAddressWorkNo, $strAddressEmail, $intAddressID));

																	if($this->has_address(array('address_id' => $intAddressID, 'group_id' => $post_id)) == false)
																	{
																		$this->add_address(array('address_id' => $intAddressID, 'group_id' => $post_id));

																		if(get_option('setting_group_debug') == 'yes')
																		{
																			do_log("Group API - ".$post_title." - Add to group: ".$intAddressID." (".$strAddressFirstName." ".$strAddressSurName.")");
																		}

																		$count_added++;
																	}

																	else
																	{
																		$count_exists_in_group++;
																	}

																	$arr_addresses[] = $intAddressID;
																}

																else
																{
																	$count_exists_in_array++;
																}
															}

															$count_found += $rows;
														}

														else// if(get_option('setting_group_debug') == 'yes')
														{
															do_log("Group API Error - ".$post_title." - No rows found with Birthdate or E-mail: ".$wpdb->last_query);

															$count_found_error++;
														}
													}

													else
													{
														do_log("Group API Error - ".$post_title." - No birthdate or email in ".$post_meta_api." -> ".htmlspecialchars(var_export($item, true)));
													}
												}
											}
											##################################
										}

										do_log($log_message_2, 'trash');
									break;

									default:
										do_log($log_message_2.": ".$post_meta_api." -> ".htmlspecialchars(var_export($json, true)));
									break;
								}

								do_log($log_message, 'trash');
							break;

							default:
								do_log($log_message." (".$headers['http_code'].")");
							break;
						}
					}

					// Remove not in group anymore
					##################################
					$count_addresses = count($arr_addresses);

					if($count_addresses > 0)
					{
						$result = $wpdb->get_results($wpdb->prepare("SELECT addressID FROM ".$wpdb->prefix."address2group WHERE groupID = '%d' AND addressID NOT IN('".implode("','", $arr_addresses)."')", $post_id));
						$rows = $wpdb->num_rows;

						if($rows > 0)
						{
							foreach($result as $r)
							{
								$intAddressID = $r->addressID;

								if($this->has_address(array('address_id' => $intAddressID, 'group_id' => $post_id)) == true)
								{
									if(get_option('setting_group_debug') == 'yes')
									{
										do_log("Group API - Remove from group: ".$intAddressID." (".$strAddressFirstName." ".$strAddressSurName.") from ".$post_title);
									}

									$this->remove_address(array('address_id' => $intAddressID, 'group_id' => $post_id));

									$count_removed++;
								}
							}
						}

						else if(get_option('setting_group_debug') == 'yes')
						{
							do_log("Group API - ".$post_title." - No rows found with to remove (Address array): ".$wpdb->last_query);
						}
					}
					##################################

					// Check if correct amount is in group after sync
					##################################
					if($count_addresses > 0)
					{
						$count_in_group = $this->amount_in_group(array('id' => $post_id));

						if(get_option('setting_group_debug') == 'yes' && ($count_in_group + $count_exists_in_array) != $count_incoming)
						{
							do_log("Group API Error: Wrong amount in group (<a href='".admin_url("admin.php?page=mf_group/create/index.php&intGroupID=".$post_id)."'>".$post_title."</a>) after sync (".$count_in_group." + ".$count_exists_in_array." != ".$count_incoming.")");
						}
					}
					##################################

					if(get_option('setting_group_debug') == 'yes')
					{
						do_log("Group API - ".$post_title." - Report: ".$count_found."/".$count_incoming." found with ".$count_found_error." errors. ".$count_inserted." inserted with ".$count_inserted_error." errors. ".$count_added." added, ".$count_exists_in_group." (+".$count_exists_in_array." duplicates) exists and ".$count_removed." removed");
					}
				}
			}
			#############################
		}

		$obj_cron->end();
	}

	function init()
	{
		$labels = array(
			'name' => _x(__("Group", $this->lang_key), 'post type general name'),
			'singular_name' => _x(__("Group", $this->lang_key), 'post type singular name'),
			'menu_name' => __("Group", $this->lang_key)
		);

		$args = array(
			'labels' => $labels,
			'public' => true,
			'show_in_menu' => false,
			'exclude_from_search' => true,
			'rewrite' => array(
				'slug' => __("group", $this->lang_key),
			),
		);

		register_post_type($this->post_type, $args);
	}

	function settings_group()
	{
		$options_area = __FUNCTION__;

		add_settings_section($options_area, "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

		$arr_settings = array(
			'setting_emails_per_hour' => __("Outgoing e-mails per hour", $this->lang_key),
			'setting_group_see_other_roles' => __("See groups created by other roles", $this->lang_key),
			'setting_group_outgoing_text' => __("Outgoing Text", $this->lang_key),
		);

		if(count($this->get_for_select()) > 0)
		{
			$arr_settings['setting_group_import'] = __("Add all imported to this group", $this->lang_key);
		}

		$arr_settings['setting_group_debug'] = __("Debug", $this->lang_key);

		show_settings_fields(array('area' => $options_area, 'object' => $this, 'settings' => $arr_settings));
	}

	function settings_group_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);

		echo settings_header($setting_key, __("Groups", $this->lang_key));
	}

	function setting_emails_per_hour_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		settings_save_site_wide($setting_key);
		$option = get_site_option($setting_key, get_option($setting_key, 200));

		echo show_textfield(array('type' => 'number', 'name' => $setting_key, 'value' => $option, 'suffix' => __("0 or empty means infinte", $this->lang_key)));
	}

	function setting_group_see_other_roles_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key, 'yes');

		echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
	}

	function setting_group_outgoing_text_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		echo show_wp_editor(array('name' => $setting_key, 'value' => $option, 'editor_height' => 200, 'description' => __("This text will be appended to all outgoing e-mails", $this->lang_key)));
	}

	function setting_group_import_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		echo show_select(array('data' => $this->get_for_select(), 'name' => $setting_key, 'value' => $option, 'suffix' => "<a href='".admin_url("admin.php?page=mf_group/create/index.php")."'><i class='fa fa-plus-circle fa-lg'></i></a>"));
	}

	function setting_group_debug_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key, 'no');

		echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));

		setting_time_limit(array('key' => $setting_key, 'value' => $option));
	}

	function count_unsent_group($id = 0)
	{
		global $wpdb;

		$count_message = "";

		if(does_table_exist($wpdb->prefix."group_message"))
		{
			$rows = $wpdb->get_var("SELECT COUNT(queueID) FROM ".$wpdb->prefix."group_message INNER JOIN ".$wpdb->prefix."group_queue USING (messageID) WHERE queueSent = '0' AND messageDeleted = '0'".($id > 0 ? " AND groupID = '".esc_sql($id)."'" : ""));

			if($rows > 0)
			{
				$count_message = "&nbsp;<i class='fa fa-spinner fa-spin'></i>";
			}
		}

		return $count_message;
	}

	function admin_init()
	{
		if(function_exists('wp_add_privacy_policy_content'))
		{
			if($this->has_allow_registration_post())
			{
				$content = __("We collect personal information when a subscription is begun by entering at least an e-mail address. This makes it possible for us to send the wanted e-mails to the correct recipient.", $this->lang_key);

				wp_add_privacy_policy_content(__("Group", $this->lang_key), $content);
			}
		}
	}

	function admin_menu()
	{
		$menu_root = 'mf_group/';
		$menu_start = $menu_root.'list/index.php';
		$menu_capability = override_capability(array('page' => $menu_start, 'default' => 'edit_posts'));

		$menu_title = __("Groups", $this->lang_key);
		add_menu_page("", $menu_title.$this->count_unsent_group(), $menu_capability, $menu_start, '', 'dashicons-groups', 99);

		$menu_title = __("List", $this->lang_key);
		add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, $menu_start);

		$menu_title = __("Add New", $this->lang_key);
		add_submenu_page($menu_start, $menu_title, " - ".$menu_title, $menu_capability, $menu_root."create/index.php");

		$menu_title = __("Send Message", $this->lang_key);
		add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, $menu_root."send/index.php");

		$menu_title = __("Sent", $this->lang_key);
		add_submenu_page($menu_root, $menu_title, $menu_title, $menu_capability, $menu_root."sent/index.php");

		$menu_title = __("Import", $this->lang_key);
		add_submenu_page($menu_root, $menu_title, $menu_title, $menu_capability, $menu_root."import/index.php");
	}

	function has_allow_registration_post()
	{
		global $wpdb;

		$post_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND ".$wpdb->postmeta.".meta_key = '".$this->meta_prefix."allow_registration' WHERE post_type = %s AND meta_value = %s", $this->post_type, 'yes'));

		return ($post_id > 0);
	}

	/*function add_policy($content)
	{
		if($this->has_allow_registration_post())
		{
			$content .= "<h3>".__("Group", $this->lang_key)."</h3>
			<p>"
				.__("We collect personal information when a subscription is begun by entering at least an e-mail address. This makes it possible for us to send the wanted e-mails to the correct recipient.", $this->lang_key)
			."</p>";
		}

		return $content;
	}*/

	function admin_notices()
	{
		global $wpdb, $error_text;

		if(IS_ADMIN && does_table_exist($wpdb->prefix."group_message"))
		{
			$result = $wpdb->get_results("SELECT messageType, addressID, addressFirstName, addressSurName, addressEmail, addressCellNo FROM ".$wpdb->prefix."group_message INNER JOIN ".$wpdb->prefix."group_queue USING (messageID) INNER JOIN ".get_address_table_prefix()."address USING (addressID) WHERE queueSent = '0' AND queueCreated < DATE_SUB(NOW(), INTERVAL 3 HOUR) LIMIT 0, 6");
			$rows = $wpdb->num_rows;

			if($rows > 0)
			{
				$unsent_links = "";

				$i = 0;

				foreach($result as $r)
				{
					if($i < 5)
					{
						$strMessageType = $r->messageType;
						$intAddressID = $r->addressID;
						$strAddressFirstName = $r->addressFirstName;
						$strAddressSurName = $r->addressSurName;
						$emlAddressEmail = $r->addressEmail;
						$strAddressCellNo = $r->addressCellNo;

						$strAddressName = ($strAddressFirstName != '' ? $strAddressFirstName." " : "")
							.($strAddressSurName != '' ? $strAddressSurName." " : "")
							."&lt;".($strMessageType == "email" ? $emlAddressEmail : $strAddressCellNo)."&gt;";

						$unsent_links .= ($unsent_links != '' ? ", " : "")."<a href='".admin_url("admin.php?page=mf_address/create/index.php&intAddressID=".$intAddressID)."'>".$strAddressName."</a>";

						$i++;
					}
				}

				$error_text = __("There were unsent messages", $this->lang_key)." (".$unsent_links.($rows == 6 ? "&hellip;" : "").")";

				echo get_notification();
			}
		}
	}

	function wp_trash_post($post_id)
	{
		global $wpdb;

		if(get_post_type($post_id) == $this->post_type)
		{
			//do_log("Delete postID (#".$post_id.") from ".$wpdb->prefix."group_message etc.");

			$result = $wpdb->get_results($wpdb->prepare("SELECT messageID FROM ".$wpdb->prefix."group_message WHERE groupID = '%d'", $post_id));

			foreach($result as $r)
			{
				$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."group_queue WHERE messageID = '%d'", $r->messageID));
			}

			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."group_message SET messageDeleted = '1', messageDeletedDate = NOW(), messageDeletedID = '%d' WHERE groupID = '%d'", get_current_user_id(), $post_id));

			$this->remove_all_address($post_id);
		}
	}

	function deleted_user($user_id)
	{
		global $wpdb;

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."group_message SET userID = '%d' WHERE userID = '%d'", get_current_user_id(), $user_id));
	}

	function merge_address($id_prev, $id)
	{
		global $wpdb;

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."group_queue SET addressID = '%d' WHERE addressID = '%d'", $id, $id_prev));
		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."address2group SET addressID = '%d' WHERE addressID = '%d'", $id, $id_prev));
	}

	function get_groups_to_send_to($arr_data)
	{
		return $this->get_for_select(array('add_choose_here' => false));
	}

	function get_group_addresses($data)
	{
		global $wpdb;

		if(!isset($data['type'])){		$data['type'] = 'all';}

		$query_where = "";

		switch($data['type'])
		{
			case 'address':
				$query_where = " AND addressAddress != ''";
			break;
		}

		$result = $wpdb->get_results($wpdb->prepare("SELECT addressID FROM ".get_address_table_prefix()."address INNER JOIN ".$wpdb->prefix."address2group USING (addressID) WHERE addressDeleted = '0' AND groupAccepted = '%d' AND groupUnsubscribed = '%d' AND groupID IN ('".implode("','", $data['group_ids'])."')".$query_where." GROUP BY addressID", 1, 0));

		foreach($result as $r)
		{
			$data['addresses'][] = $r->addressID;
		}

		return $data;
	}

	function count_shortcode_button($count)
	{
		if($count == 0)
		{
			$tbl_group = new mf_group_table();

			$tbl_group->select_data(array(
				'select' => "ID",
				'limit' => 0, 'amount' => 1,
			));

			if(count($tbl_group->data) > 0)
			{
				$count++;
			}
		}

		return $count;
	}

	function get_shortcode_output($out)
	{
		$arr_data = $this->get_for_select();

		if(count($arr_data) > 1)
		{
			$out .= "<h3>".__("Choose a Group", $this->lang_key)."</h3>"
			.show_select(array('data' => $arr_data, 'xtra' => "rel='".$this->post_type."'"));
		}

		return $out;
	}

	function get_shortcode_list($data)
	{
		$post_id = $data[0];
		$content_list = $data[1];

		if($post_id > 0)
		{
			$post_content = mf_get_post_content($post_id);

			$group_id = get_match("/\[mf_group id=(.*?)\]/", $post_content, false);

			if($group_id > 0)
			{
				$content_list .= "<li><a href='".admin_url("admin.php?page=mf_group/create/index.php&intGroupID=".$group_id)."'>".get_post_title($group_id)."</a> <span class='grey'>[mf_group id=".$group_id."]</span></li>";
			}
		}

		return array($post_id, $content_list);
	}

	function filter_is_file_used($arr_used)
	{
		global $wpdb;

		$result = $wpdb->get_results($wpdb->prepare("SELECT groupID, messageID FROM ".$wpdb->prefix."group_message WHERE messageDeleted = '0' AND (messageText LIKE %s OR messageAttachment LIKE %s)", "%".$arr_used['file_url']."%", "%".$arr_used['file_url']."%"));
		$rows = $wpdb->num_rows;

		if($rows > 0)
		{
			$arr_used['amount'] += $rows;

			foreach($result as $r)
			{
				if($arr_used['example'] != '')
				{
					break;
				}

				$arr_used['example'] = admin_url("admin.php?page=mf_group/sent/index.php&intGroupID=".$r->groupID."&intMessageID=".$r->messageID);
			}
		}

		return $arr_used;
	}

	function wp_sitemaps_post_types($post_types)
	{
		unset($post_types[$this->post_type]);

		return $post_types;
	}

	function wp_head()
	{
		$plugin_include_url = plugin_dir_url(__FILE__);
		$plugin_version = get_plugin_version(__FILE__);

		mf_enqueue_style('style_group', $plugin_include_url."style.css", $plugin_version);
	}

	function get_emails_left_to_send($amount, $email, $type = '')
	{
		global $wpdb;

		if($type != '' && isset($this->emails_left_to_send[$type][$email]))
		{
			//do_log("Group - ".$email." - Got from this: ".$this->emails_left_to_send[$type][$email]);

			$amount_temp = $this->emails_left_to_send[$type][$email];
		}

		else
		{
			$amount_temp = 0;
			$query_where = "";

			if($email == '')
			{
				$emails_per_hour = get_option_or_default('setting_emails_per_hour');

				if($emails_per_hour > 0)
				{
					$amount_temp += $emails_per_hour;
				}

				else if($amount == 0)
				{
					$amount_temp += 10000;
				}
			}

			else
			{
				if($amount == 0)
				{
					$amount_temp += 10000;
				}

				$query_where = " AND messageFrom LIKE '%".esc_sql($email)."'";
			}

			$wpdb->get_results("SELECT queueID FROM ".$wpdb->prefix."group_message INNER JOIN ".$wpdb->prefix."group_queue USING (messageID) WHERE queueSent = '1' AND queueSentTime > DATE_SUB(NOW(), INTERVAL 1 HOUR)".$query_where);
			$amount_temp -= $wpdb->num_rows;

			if($type != '')
			{
				$this->emails_left_to_send[$type][$email] = $amount_temp;

				//do_log("Group - ".$email." - Got from DB: ".$this->emails_left_to_send[$type][$email]." (".(isset($emails_per_hour) ? $emails_per_hour : '').", ".$wpdb->last_query." -> ".$wpdb->num_rows.")");
			}
		}

		if($type != '')
		{
			$this->emails_left_to_send[$type][$email]--;
		}

		return ($amount + $amount_temp);
	}

	function get_hourly_release_time($datetime, $email)
	{
		global $wpdb;

		if($datetime == '')
		{
			$datetime = date("Y-m-d H:i:s");
		}

		$query_where = "";

		if($email != '')
		{
			$query_where = " AND messageFrom LIKE '%".esc_sql($email)."'";
		}

		$datetime_temp = $wpdb->get_var("SELECT queueSentTime FROM ".$wpdb->prefix."group_message INNER JOIN ".$wpdb->prefix."group_queue USING (messageID) WHERE queueSent = '1' AND queueSentTime > DATE_SUB(NOW(), INTERVAL 1 HOUR)".$query_where." ORDER BY queueSentTime ASC LIMIT 0, 1");

		if($datetime_temp > DEFAULT_DATE && $datetime_temp < $datetime)
		{
			$datetime = $datetime_temp;
		}

		return $datetime;
	}

	function shortcode_group($atts)
	{
		extract(shortcode_atts(array(
			'id' => ''
		), $atts));

		return $this->show_group_registration_form(array('id' => $id));
	}

	function widgets_init()
	{
		register_widget('widget_group');
	}

	function fetch_request()
	{
		switch($this->type)
		{
			case 'form':
				$this->message_type = check_var('type', 'char', true, 'email');
				$this->group_id = check_var('intGroupID');
				$this->arr_group_id = check_var('arrGroupID');
				$this->message_id = check_var('intMessageID');
				$this->message_from = check_var('strMessageFrom', 'char', true, $this->get_from_last());
				$this->message_name = check_var('strMessageName');
				$this->message_text = check_var('strMessageText', 'raw');
				$this->message_schedule_date = check_var('dteMessageScheduleDate');
				$this->message_schedule_time = check_var('dteMessageScheduleTime');
				$this->message_text_source = check_var('intEmailTextSource');
				$this->message_attachment = check_var('strMessageAttachment');

				if($this->group_id > 0 && !in_array($this->group_id, $this->arr_group_id))
				{
					$this->arr_group_id[] = $this->group_id;
				}
			break;

			case 'create':
				$this->name = check_var('strGroupName');

				$this->acceptance_email = check_var('strGroupAcceptanceEmail', 'char', true, 'no');
				$this->acceptance_subject = check_var('strGroupAcceptanceSubject');
				$this->acceptance_text = check_var('strGroupAcceptanceText');
				$this->reminder_subject = check_var('strGroupReminderSubject');
				$this->reminder_text = check_var('strGroupReminderText');

				$this->owner_email = check_var('intGroupOwnerEmail');
				$this->help_page = check_var('intGroupHelpPage');
				$this->archive_page = check_var('intGroupArchivePage');

				$this->allow_registration = check_var('strGroupAllowRegistration', 'char', true, 'no');

				$this->verify_address = check_var('strGroupVerifyAddress');
				$this->contact_page = check_var('intGroupContactPage');
				$this->registration_fields = check_var('arrGroupRegistrationFields');
				$this->verify_link = check_var('strGroupVerifyLink', 'char', true, 'no');
				$this->sync_users = check_var('strGroupSyncUsers', 'char', true, 'no');

				$this->id_copy = check_var('intGroupID_copy');
				$this->api = check_var('strGroupAPI');
				$this->api_filter = check_var('strGroupAPIFilter');
			break;

			case 'sent':
				$this->message_id = check_var('intMessageID');
			break;
		}
	}

	function get_add_view_in_browser_code()
	{
		return "<p class='view_in_browser_link' style='text-align: right'><a href='[view_in_browser_link]' style='color: #999; font-size: .8em; text-decoration: none'>".sprintf(__("View %s in browser", $this->lang_key), "[message_name]")."</a></p>";
	}

	function get_unsubscribe_code()
	{
		return "<p><a href='[unsubscribe_link]'>".__("If you do not want to get these messages in the future click this link to unsubscribe", $this->lang_key)."</a></p>";
	}

	function save_data()
	{
		global $wpdb, $error_text, $done_text;

		$out = "";

		switch($this->type)
		{
			case 'table':
				if(isset($_REQUEST['btnGroupDelete']) && $this->id > 0 && wp_verify_nonce($_REQUEST['_wpnonce_group_delete'], 'group_delete_'.$this->id))
				{
					if(wp_trash_post($this->id))
					{
						$this->remove_all_address($this->id);

						$done_text = __("The information was deleted", $this->lang_key);
					}
				}

				else if(isset($_REQUEST['btnGroupActivate']) && $this->id > 0 && wp_verify_nonce($_REQUEST['_wpnonce_group_activate'], 'group_activate_'.$this->id))
				{
					$post_data = array(
						'ID' => $this->id,
						'post_status' => 'draft',
						'post_modified' => date("Y-m-d H:i:s"),
					);

					if(wp_update_post($post_data) > 0)
					{
						$done_text = __("The group was activated", $this->lang_key);
					}
				}

				else if(isset($_REQUEST['btnGroupInactivate']) && $this->id > 0 && wp_verify_nonce($_REQUEST['_wpnonce_group_inactivate'], 'group_inactivate_'.$this->id))
				{
					$post_data = array(
						'ID' => $this->id,
						'post_status' => 'ignore',
						'post_modified' => date("Y-m-d H:i:s"),
					);

					if(wp_update_post($post_data) > 0)
					{
						$done_text = __("The group was inactivated", $this->lang_key);
					}
				}

				else if(isset($_GET['btnGroupResend']) && $this->id > 0 && wp_verify_nonce($_REQUEST['_wpnonce_group_resend'], 'group_resend_'.$this->id))
				{
					$result = $wpdb->get_results($wpdb->prepare("SELECT addressID FROM ".get_address_table_prefix()."address INNER JOIN ".$wpdb->prefix."address2group USING (addressID) WHERE addressDeleted = '0' AND groupID = '%d' AND groupAccepted = '0' AND groupUnsubscribed = '0' AND (groupAcceptanceSent IS null OR groupAcceptanceSent <= '%s') ORDER BY groupAcceptanceSent ASC".$this->get_message_query_limit(), $this->id, date("Y-m-d H:i:s", strtotime("-1 week"))));

					$success = $fail = 0;

					foreach($result as $r)
					{
						if($this->send_acceptance_message(array('type' => 'reminder', 'address_id' => $r->addressID, 'group_id' => $this->id)))
						{
							$success++;
						}

						else
						{
							$fail++;
						}

						if(($success + $fail) % 20 == 0)
						{
							sleep(0.1);
							set_time_limit(60);
						}
					}

					if($fail > 0)
					{
						$error_text = sprintf(__("%d messages were successful and %d failed", $this->lang_key), $success, $fail);
					}

					else
					{
						$done_text = sprintf(__("%d messages were sent", $this->lang_key), $success);
					}
				}

				else if(isset($_GET['sent']))
				{
					$done_text = __("The information was sent", $this->lang_key);
				}

				else if(isset($_GET['created']))
				{
					$done_text = __("The group was created", $this->lang_key);
				}

				else if(isset($_GET['updated']))
				{
					$done_text = __("The group was updated", $this->lang_key);
				}

				$obj_export = new mf_group_export();
			break;

			case 'form':
				if(isset($_POST['btnGroupSend']) && count($this->arr_group_id) > 0 && wp_verify_nonce($_POST['_wpnonce_group_send'], 'group_send_'.$this->message_type))
				{
					if($this->message_text == '')
					{
						$error_text = __("You have to enter a text to send", $this->lang_key);
					}

					else if($this->message_type == 'email' || $this->message_type == 'sms')
					{
						$attachments_size = 0;
						$attachments_size_limit = 5 * pow(1024, 2);

						if($this->message_attachment != '')
						{
							list($mail_attachment, $rest) = get_attachment_to_send($this->message_attachment);

							foreach($mail_attachment as $file)
							{
								$attachments_size += filesize($file);
							}
						}

						if($attachments_size > $attachments_size_limit)
						{
							$error_text = sprintf(__("You are trying to send attachments of a total of %s. I suggest that you send the attachments as inline links instead of attachments. This way I do not have to send too much data which might slow down the server or make it timeout due to memory limits and it also makes the recipients not have to recieve that much in their inboxes.", $this->lang_key), show_final_size($attachments_size));
						}

						else
						{
							$query_where = "";

							if(!IS_EDITOR)
							{
								$query_where .= " AND post_author = '".get_current_user_id()."'";
							}

							$arr_recipients = array();

							foreach($this->arr_group_id as $this->group_id)
							{
								$wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND ID = '%d'".$query_where." LIMIT 0, 1", $this->post_type, $this->group_id));
								$rows = $wpdb->num_rows;

								if($rows > 0)
								{
									$dteMessageSchedule = $this->message_schedule_date != '' && $this->message_schedule_time != '' ? $this->message_schedule_date." ".$this->message_schedule_time : '';

									$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."group_message SET groupID = '%d', messageType = %s, messageFrom = %s, messageName = %s, messageText = %s, messageAttachment = %s, messageSchedule = %s, messageCreated = NOW(), userID = '%d'", $this->group_id, $this->message_type, $this->message_from, $this->message_name, $this->message_text, $this->message_attachment, $dteMessageSchedule, get_current_user_id()));

									$this->message_id = $wpdb->insert_id;

									if($this->message_id > 0)
									{
										$result = $wpdb->get_results($wpdb->prepare("SELECT addressID, addressEmail, addressCellNo FROM ".get_address_table_prefix()."address INNER JOIN ".$wpdb->prefix."address2group USING (addressID) WHERE groupID = '%d' AND addressDeleted = '0' AND groupAccepted = '1' AND groupUnsubscribed = '0'", $this->group_id));

										foreach($result as $r)
										{
											$intAddressID = $r->addressID;
											$strAddressEmail = $r->addressEmail;
											$strAddressCellNo = $r->addressCellNo;

											if(!in_array($intAddressID, $arr_recipients) && ($this->message_type == "email" && $strAddressEmail != "" || $this->message_type == "sms" && $strAddressCellNo != ""))
											{
												$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."group_queue SET addressID = '%d', messageID = '%d', queueCreated = NOW()", $intAddressID, $this->message_id));

												if($wpdb->rows_affected > 0)
												{
													$arr_recipients[] = $intAddressID;
												}
											}
										}
									}

									else
									{
										$error_text = __("There was an error when saving the message", $this->lang_key);
									}
								}
							}

							if(count($arr_recipients) > 0)
							{
								mf_redirect(admin_url("admin.php?page=mf_group/list/index.php&sent"));
							}

							else
							{
								$error_text = __("The message was not sent to anybody", $this->lang_key);
							}
						}
					}
				}

				else if(isset($_POST['btnGroupAddViewInBrowser']))
				{
					if($this->message_type == 'email')
					{
						$this->message_text = $this->get_add_view_in_browser_code().$this->message_text;
					}
				}

				else if(isset($_POST['btnGroupAddUnsubscribe']))
				{
					if($this->message_type == 'email')
					{
						$this->message_text .= $this->get_unsubscribe_code();
					}
				}

				else if($this->message_text_source > 0)
				{
					$this->message_text = $wpdb->get_var($wpdb->prepare("SELECT post_content FROM ".$wpdb->posts." WHERE post_type = 'page' AND post_status = 'publish' AND ID = '%d'", $this->message_text_source));

					$this->message_text = str_replace("[name]", get_user_info(), $this->message_text);

					if(is_plugin_active("mf_email/index.php"))
					{
						if(!isset($obj_email))
						{
							$obj_email = new mf_email();
						}

						$this->message_text = $obj_email->convert_characters($this->message_text);
					}

					// Code to remove if it has been pasted from external source
					$this->message_text = preg_replace("/ class=[\"\'](.*?)[\"\']/", "", $this->message_text);
					$this->message_text = str_replace(array("<header>", "</header>", "<!-- wp:paragraph -->", "<!-- /wp:paragraph -->"), "", $this->message_text);
					$this->message_text = preg_replace(array("/<div>[\n|\r]<div>/", "/<\/div>[\n|\r]<\/div>/"), array("<div>", "</div>"), $this->message_text);
				}

				else if($this->message_id > 0)
				{
					$result = $wpdb->get_results($wpdb->prepare("SELECT messageFrom, messageName, messageText FROM ".$wpdb->prefix."group_message WHERE messageID = '%d'", $this->message_id));

					foreach($result as $r)
					{
						$this->message_from = $r->messageFrom;
						$this->message_name = $r->messageName;
						$this->message_text = stripslashes($r->messageText);
					}
				}
			break;

			case 'create':
				if(isset($_POST['btnGroupSave']) && wp_verify_nonce($_POST['_wpnonce_group_save'], 'group_save_'.$this->id))
				{
					$post_data = array(
						'post_type' => $this->post_type,
						//'post_status' => $this->public,
						'post_status' => 'publish',
						'post_title' => $this->name,
						'meta_input' => array(
							$this->meta_prefix.'api' => $this->api,
							$this->meta_prefix.'api_filter' => $this->api_filter,
							$this->meta_prefix.'acceptance_email' => $this->acceptance_email,
							$this->meta_prefix.'acceptance_subject' => $this->acceptance_subject,
							$this->meta_prefix.'acceptance_text' => $this->acceptance_text,
							$this->meta_prefix.'allow_registration' => $this->allow_registration,
							$this->meta_prefix.'verify_address' => $this->verify_address,
							$this->meta_prefix.'contact_page' => $this->contact_page,
							$this->meta_prefix.'registration_fields' => $this->registration_fields,
							$this->meta_prefix.'verify_link' => $this->verify_link,
							$this->meta_prefix.'sync_users' => $this->sync_users,
							$this->meta_prefix.'owner_email' => $this->owner_email,
							$this->meta_prefix.'help_page' => $this->help_page,
							$this->meta_prefix.'archive_page' => $this->archive_page,
						),
					);

					if($this->id > 0)
					{
						$post_data['ID'] = $this->id;
						$post_data['post_modified'] = date("Y-m-d H:i:s");

						if(wp_update_post($post_data) > 0)
						{
							mf_redirect(admin_url("admin.php?page=mf_group/list/index.php&updated"));
						}

						else
						{
							$error_text = __("The information was not submitted, contact an admin if this persists", $this->lang_key);
						}
					}

					else
					{
						$this->id = wp_insert_post($post_data);

						if($this->id > 0)
						{
							if($this->id_copy > 0)
							{
								$result = $wpdb->get_results($wpdb->prepare("SELECT addressID FROM ".get_address_table_prefix()."address INNER JOIN ".$wpdb->prefix."address2group USING (addressID) WHERE groupID = '%d' AND addressDeleted = '0'", $this->id_copy));

								foreach($result as $r)
								{
									$intAddressID = $r->addressID;

									$this->add_address(array('address_id' => $intAddressID, 'group_id' => $this->id));
								}
							}

							mf_redirect(admin_url("admin.php?page=mf_group/list/index.php&created"));
						}

						else
						{
							$error_text = __("The information was not submitted, contact an admin if this persists", $this->lang_key);
						}
					}
				}

				else if(isset($_POST['btnGroupRemoveRecipients']) && $this->id > 0 && wp_verify_nonce($_POST['_wpnonce_group_remove_recipients'], 'group_remove_recipients_'.$this->id))
				{
					if(isset($_POST['intGroupRemoveRecipientsConfirm']) && $_POST['intGroupRemoveRecipientsConfirm'] == 1)
					{
						$this->remove_all_address($this->id);

						if($wpdb->rows_affected > 0)
						{
							$done_text = __("I removed all the recipients from this group", $this->lang_key);
						}

						else
						{
							$error_text = __("I could not remove the recipients from this group", $this->lang_key);
						}
					}
				}
			break;

			case 'sent':
				if(isset($_REQUEST['btnMessageDelete']) && $this->message_id > 0 && wp_verify_nonce($_REQUEST['_wpnonce_message_delete'], 'message_delete_'.$this->message_id))
				{
					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."group_message SET messageDeleted = '1', messageDeletedDate = NOW(), messageDeletedID = '%d' WHERE messageID = '%d'", get_current_user_id(), $this->message_id));

					$done_text = __("The message was deleted", $this->lang_key);
				}

				if(isset($_REQUEST['btnMessageAbort']) && $this->message_id > 0 && wp_verify_nonce($_REQUEST['_wpnonce_message_abort'], 'message_abort_'.$this->message_id))
				{
					$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."group_queue WHERE messageID = '%d' AND queueSent = '0'", $this->message_id));

					$done_text = __("The message was aborted", $this->lang_key);
				}
			break;
		}

		return $out;
	}

	function get_from_db()
	{
		global $wpdb;

		switch($this->type)
		{
			case 'create':
				if($this->id > 0)
				{
					$result = $wpdb->get_results($wpdb->prepare("SELECT post_status, post_title FROM ".$wpdb->posts." WHERE post_type = %s AND ID = '%d'".$this->query_where, $this->post_type, $this->id));

					foreach($result as $r)
					{
						$this->public = $r->post_status;
						$this->name = $r->post_title;

						$this->api = get_post_meta($this->id, $this->meta_prefix.'api', true);
						$this->api_filter = get_post_meta($this->id, $this->meta_prefix.'api_filter', true);

						$this->acceptance_email = get_post_meta_or_default($this->id, $this->meta_prefix.'acceptance_email', true, 'no');
						$this->acceptance_subject = get_post_meta($this->id, $this->meta_prefix.'acceptance_subject', true);
						$this->acceptance_text = get_post_meta($this->id, $this->meta_prefix.'acceptance_text', true);

						$this->allow_registration = get_post_meta_or_default($this->id, $this->meta_prefix.'allow_registration', true, 'no');

						$this->verify_address = get_post_meta_or_default($this->id, $this->meta_prefix.'verify_address', true, 'no');
						$this->contact_page = get_post_meta($this->id, $this->meta_prefix.'contact_page', true);
						$this->registration_fields = get_post_meta($this->id, $this->meta_prefix.'registration_fields', true);
						$this->verify_link = get_post_meta($this->id, $this->meta_prefix.'verify_link', true);
						$this->sync_users = get_post_meta($this->id, $this->meta_prefix.'sync_users', true);

						$this->owner_email = get_post_meta($this->id, $this->meta_prefix.'owner_email', true);
						$this->help_page = get_post_meta($this->id, $this->meta_prefix.'help_page', true);
						$this->archive_page = get_post_meta($this->id, $this->meta_prefix.'archive_page', true);

						if($this->public == 'trash')
						{
							$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->posts." SET post_status = 'publish' WHERE ID = '%d' AND post_type = %s".$this->query_where, $this->id, $this->post_type));
						}
					}
				}
			break;
		}
	}

	function get_from_last()
	{
		global $wpdb;

		return $wpdb->get_var($wpdb->prepare("SELECT messageFrom FROM ".$wpdb->prefix."group_message INNER JOIN ".$wpdb->posts." ON ".$wpdb->prefix."group_message.groupID = ".$wpdb->posts.".ID AND post_type = %s AND messageDeleted = '0' ORDER BY messageCreated DESC LIMIT 0, 1", $this->post_type));
	}

	function get_name($data = array())
	{
		global $wpdb;

		if(!isset($data['id'])){	$data['id'] = $this->id;}

		return $wpdb->get_var($wpdb->prepare("SELECT post_title FROM ".$wpdb->posts." WHERE post_type = %s AND ID = '%d'", $this->post_type, $data['id']));
	}

	function check_if_address_exists($query_where)
	{
		global $wpdb;

		if($query_where != '')
		{
			$intAddressID = $wpdb->get_var("SELECT addressID FROM ".get_address_table_prefix()."address WHERE addressDeleted = '0' AND ".$query_where);

			return $intAddressID;
		}
	}

	function get_message_query_limit()
	{
		$query_limit = "";

		$emails_left_to_send = apply_filters('get_emails_left_to_send', 0, '');

		if($emails_left_to_send > -1)
		{
			$query_limit = " LIMIT 0, ".$emails_left_to_send;
		}

		return $query_limit;
	}

	function is_allowed2send_reminder($data)
	{
		global $wpdb;

		$dteGroupAcceptanceSent = $wpdb->get_var($wpdb->prepare("SELECT groupAcceptanceSent FROM ".$wpdb->prefix."address2group WHERE addressID = '%d' AND groupID = '%d'", $data['address_id'], $data['group_id']));

		return ($dteGroupAcceptanceSent < date("Y-m-d H:i:s", strtotime("-1 week")));
	}

	function send_acceptance_message($data)
	{
		global $wpdb, $obj_address;

		if(!isset($data['type'])){		$data['type'] = 'acceptance';}

		switch($data['type'])
		{
			case 'acceptance':
			default:
				$meta_key_subject = $this->meta_prefix.'acceptance_subject';
				$meta_key_text = $this->meta_prefix.'acceptance_text';
			break;

			case 'reminder':
				$meta_key_subject = 'group_reminder_subject';
				$meta_key_text = 'group_reminder_text';
			break;
		}

		$strGroupAcceptanceSubject = get_post_meta_or_default($data['group_id'], $meta_key_subject, true, __("Accept subscription to %s", $this->lang_key));
		$strGroupAcceptanceText = get_post_meta_or_default($data['group_id'], $meta_key_text, true, __("You have been added to the group %s but will not get any messages until you have accepted this subscription by clicking the link below.", $this->lang_key));

		if(!isset($obj_address))
		{
			$obj_address = new mf_address();
		}

		$strAddressEmail = $obj_address->get_address($data['address_id']);
		$strGroupName = $this->get_name(array('id' => $data['group_id']));

		$mail_to = $strAddressEmail;
		$mail_subject = sprintf($strGroupAcceptanceSubject, $strGroupName);
		$mail_content = apply_filters('the_content', sprintf($strGroupAcceptanceText, $strGroupName));

		$mail_content .= "<p><a href='".$this->get_group_url(array('type' => 'subscribe', 'group_id' => $data['group_id'], 'email' => $strAddressEmail))."'>".__("Accept Link", $this->lang_key)."</a></p>";

		$sent = send_email(array('to' => $mail_to, 'subject' => $mail_subject, 'content' => $mail_content));

		if($sent)
		{
			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."address2group SET groupAcceptanceSent = NOW() WHERE addressID = '%d' AND groupID = '%d'", $data['address_id'], $data['group_id']));
		}

		return $sent;
	}

	function accept_address($data)
	{
		global $wpdb;

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."address2group SET groupAccepted = '1' WHERE addressID = '%d' AND groupID = '%d'", $data['address_id'], $data['group_id']));

		return ($wpdb->rows_affected > 0);
	}

	function has_address($data)
	{
		global $wpdb;

		$intAddressID = $wpdb->get_var($wpdb->prepare("SELECT addressID FROM ".$wpdb->prefix."address2group WHERE addressID = '%d' AND groupID = '%d' LIMIT 0, 1", $data['address_id'], $data['group_id']));

		return ($intAddressID > 0);
	}

	function add_address($data)
	{
		global $wpdb;

		if($data['address_id'] > 0 && $data['group_id'] > 0)
		{
			if($this->has_address($data) == false)
			{
				$post_meta_api = get_post_meta($data['group_id'], $this->meta_prefix.'api', true);

				if($post_meta_api != '')
				{
					$post_meta_acceptance_email = 'no';
				}

				else
				{
					$post_meta_acceptance_email = get_post_meta($data['group_id'], $this->meta_prefix.'acceptance_email', true);
				}

				$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."address2group SET addressID = '%d', groupID = '%d', groupAccepted = '%d'", $data['address_id'], $data['group_id'], ($post_meta_acceptance_email == 'yes' ? 0 : 1)));

				if($post_meta_acceptance_email == 'yes')
				{
					$this->send_acceptance_message($data);
				}

				//$from_email = get_bloginfo('admin_email');
				$from_email = $wpdb->get_var($wpdb->prepare("SELECT messageFrom FROM ".$wpdb->prefix."group_message WHERE groupID = '%d' AND messageDeleted = '0' ORDER BY messageCreated DESC LIMIT 0, 1", $data['group_id']));

				do_action('group_after_add_address', array('address_id' => $data['address_id'], 'from' => $from_email));
			}
		}
	}

	function remove_address($data)
	{
		global $wpdb;

		if(!isset($data['group_id'])){		$data['group_id'] = 0;}

		//do_log("Deleted (AID: ".$data['address_id'].", GID: ".$data['group_id'].")");

		$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."address2group WHERE addressID = '%d'".($data['group_id'] > 0 ? " AND groupID = '".$data['group_id']."'" : ""), $data['address_id']));
	}

	function remove_all_address($group_id)
	{
		global $wpdb;

		$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."address2group WHERE groupID = '%d'", $group_id));
	}

	function unsubscribe_address($address_id, $group_id)
	{
		global $wpdb;

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."address2group SET groupUnsubscribed = '1' WHERE groupID = '%d' AND addressID = '%d'", $group_id, $address_id));

		return ($wpdb->rows_affected == 1);
	}

	function amount_in_group($data = array())
	{
		global $wpdb;

		if(does_table_exist($wpdb->prefix."address2group"))
		{
			if(!isset($data['id'])){				$data['id'] = $this->id;}
			if(!isset($data['accepted'])){			$data['accepted'] = 1;}
			if(!isset($data['acceptance_sent'])){	$data['acceptance_sent'] = '';}
			if(!isset($data['unsubscribed'])){		$data['unsubscribed'] = 0;}
			if(!isset($data['deleted'])){			$data['deleted'] = 0;}

			$query_where = "";

			if($data['id'] > 0)
			{
				$query_where .= " AND groupID = '".esc_sql($data['id'])."'";
			}

			if($data['acceptance_sent'] > DEFAULT_DATE)
			{
				$query_where .= " AND (groupAcceptanceSent IS null OR groupAcceptanceSent <= '".esc_sql($data['acceptance_sent'])."')";
			}

			return $wpdb->get_var($wpdb->prepare("SELECT COUNT(addressID) FROM ".get_address_table_prefix()."address INNER JOIN ".$wpdb->prefix."address2group USING (addressID) WHERE addressDeleted = '%d' AND groupAccepted = '%d' AND groupUnsubscribed = '%d'".$query_where, $data['deleted'], $data['accepted'], $data['unsubscribed']));
		}

		else
		{
			return 0;
		}
	}
}

if(class_exists('mf_list_table'))
{
	class mf_group_table extends mf_list_table
	{
		function set_default()
		{
			$this->post_type = "mf_group";

			$this->arr_settings['query_select_id'] = "ID";
			$this->orderby_default = "post_title";

			$this->arr_settings['has_autocomplete'] = true;
			$this->arr_settings['plugin_name'] = 'mf_group';
		}

		function init_fetch()
		{
			global $obj_group;

			if(!isset($obj_group))
			{
				$obj_group = new mf_group();
			}

			if($this->search != '')
			{
				$this->query_where .= ($this->query_where != '' ? " AND " : "")."(post_title LIKE '%".$this->search."%' OR SOUNDEX(post_title) = SOUNDEX('".$this->search."'))";
			}

			if(!IS_EDITOR)
			{
				$this->query_where .= ($this->query_where != '' ? " AND " : "")."post_author = '".get_current_user_id()."'";
			}

			else
			{
				$option = get_option('setting_group_see_other_roles', 'yes');

				if($option == 'no')
				{
					$user_role = get_current_user_role();

					$users = get_users(array(
						'role' => $user_role,
						'fields' => array('ID'),
					));

					$arr_users = array();

					foreach($users as $user)
					{
						$arr_users[] = $user->ID;
					}

					$this->query_where .= ($this->query_where != '' ? " AND " : "")."post_author IN('".implode("','", $arr_users)."')";
				}
			}

			$this->set_views(array(
				'db_field' => 'post_status',
				'types' => array(
					'all' => __("All", $obj_group->lang_key),
					'publish' => __("Public", $obj_group->lang_key),
					'draft' => __("Not Public", $obj_group->lang_key),
					'ignore' => __("Inactive", $obj_group->lang_key),
					'trash' => __("Trash", $obj_group->lang_key)
				),
			));

			$rowsAddressesNotAccepted = $obj_group->amount_in_group(array('id' => 0, 'accepted' => 0));

			$arr_columns = array(
				'cb' => '<input type="checkbox">',
				'post_title' => __("Name", $obj_group->lang_key),
				'post_status' => "",
				'amount' => __("Amount", $obj_group->lang_key),
			);

			if($rowsAddressesNotAccepted > 0)
			{
				$arr_columns['not_accepted'] = __("Not Accepted", $obj_group->lang_key);
			}

			$arr_columns['unsubscribed'] = __("Unsubscribed", $obj_group->lang_key);
			$arr_columns['post_author'] = shorten_text(array('string' => __("User", $obj_group->lang_key), 'limit' => 4));
			$arr_columns['sent'] = __("Sent", $obj_group->lang_key);

			$this->set_columns($arr_columns);

			$this->set_sortable_columns(array(
				'post_title',
				//'amount',
				'post_author',
			));
		}

		function column_default($item, $column_name)
		{
			global $wpdb;

			$out = "";

			$post_id = $item['ID'];
			$post_status = $item['post_status'];

			$list_url = admin_url("admin.php?page=mf_group/list/index.php&intGroupID=".$post_id."&post_status=".check_var('post_status'));

			$obj_group = new mf_group(array('id' => $post_id));

			switch($column_name)
			{
				case 'post_title':
					$post_edit_url = "#";
					$post_author = $item['post_author'];

					$actions = array();

					if($post_status != 'trash')
					{
						$post_edit_url = admin_url("admin.php?page=mf_group/create/index.php&intGroupID=".$post_id);

						$actions['edit'] = "<a href='".$post_edit_url."'>".__("Edit", $obj_group->lang_key)."</a>";

						if($post_author == get_current_user_id() || IS_ADMIN)
						{
							$actions['delete'] = "<a href='".wp_nonce_url($list_url."&btnGroupDelete", 'group_delete_'.$post_id, '_wpnonce_group_delete')."'>".__("Delete", $obj_group->lang_key)."</a>";
						}

						if($post_status == 'ignore')
						{
							$actions['activate'] = "<a href='".wp_nonce_url($list_url."&btnGroupActivate", 'group_activate_'.$post_id, '_wpnonce_group_activate')."'>".__("Activate", $obj_group->lang_key)."</a>";
						}

						else
						{
							$actions['inactivate'] = "<a href='".wp_nonce_url($list_url."&btnGroupInactivate", 'group_inactivate_'.$post_id, '_wpnonce_group_inactivate')."'>".__("Inactivate", $obj_group->lang_key)."</a>";
						}
					}

					else
					{
						$actions['recover'] = "<a href='".admin_url("admin.php?page=mf_group/create/index.php&intGroupID=".$post_id."&recover")."'>".__("Recover", $obj_group->lang_key)."</a>";
					}

					$out .= "<a href='".$post_edit_url."'>".$item['post_title']."</a>"
					.$this->row_actions($actions);
				break;

				case 'post_status':
					$arr_statuses = array(
						'allow_registration' => array(
							'type' => 'bool',
							'name' => __("Allow Registration", $obj_group->lang_key),
							'icon' => 'fa fa-globe',
							'single' => true,
							'link' => get_permalink($post_id),
						),
						'api' => array(
							'type' => 'empty',
							'name' => __("API Link", $obj_group->lang_key),
							'icon' => 'fas fa-network-wired',
							'single' => true,
						),
						'api_filter' => array(
							'type' => 'empty',
							'name' => __("Filter API", $obj_group->lang_key),
							'icon' => 'fas fa-plus',
							'single' => true,
						),
						'sync_users' => array(
							'type' => 'bool',
							'name' => __("Synchronize Users", $obj_group->lang_key),
							'icon' => 'fas fa-users',
							'single' => true,
						),
						'verify_link' => array(
							'type' => 'bool',
							'name' => __("Add Verify Link", $obj_group->lang_key),
							'icon' => 'fas fa-link',
							'single' => true,
						),
						'acceptance_email' => array(
							'type' => 'bool',
							'name' => __("Send before adding to a group", $obj_group->lang_key),
							'icon' => 'fa fa-paper-plane',
							'single' => true,
						),
						'owner_email' => array(
							'type' => 'empty',
							'name' => __("Owner", $obj_group->lang_key),
							'icon' => 'fas fa-user-tie',
							'single' => true,
						),
						'help_page' => array(
							'type' => 'empty',
							'name' => __("Help Page", $obj_group->lang_key),
							'icon' => 'far fa-question-circle',
							'single' => true,
						),
						'archive_page' => array(
							'type' => 'empty',
							'name' => __("Archive Page", $obj_group->lang_key),
							'icon' => 'fas fa-archive',
							'single' => true,
						),
					);

					$i = 0;

					foreach($arr_statuses as $key => $arr_value)
					{
						$post_meta = get_post_meta($post_id, $obj_group->meta_prefix.$key, $arr_value['single']);

						$do_display = false;

						switch($arr_value['type'])
						{
							case 'bool':
								if($post_meta == 'yes')
								{
									$do_display = true;
								}
							break;

							case 'empty':
								if($post_meta != '')
								{
									$do_display = true;
								}
							break;
						}

						if($do_display == true)
						{
							if($i > 0)
							{
								$out .= " ";
							}

							if(isset($arr_value['link']) && $arr_value['link'] != '')
							{
								$out .= "<a href='".$arr_value['link']."'>";
							}

								$out .= "<i class='".$arr_value['icon']." fa-lg blue' title='".$arr_value['name']."'></i>";

							if(isset($arr_value['link']) && $arr_value['link'] != '')
							{
								$out .= "</a>";
							}

							$i++;
						}
					}
				break;

				case 'amount':
					$amount = $obj_group->amount_in_group(array('id' => $post_id));

					$actions = array();

					if($post_status == 'publish')
					{
						if($amount > 0)
						{
							$actions['send_email'] = "<a href='".admin_url("admin.php?page=mf_group/send/index.php&intGroupID=".$post_id."&type=email")."' title='".__("Send e-mail to everyone in the group", $obj_group->lang_key)."'><i class='fa fa-paper-plane fa-lg'></i></a>";

							$actions = apply_filters('add_group_list_amount_actions', $actions, $post_id);
						}

						$post_meta_api = get_post_meta($post_id, $obj_group->meta_prefix.'api', true);

						if($post_meta_api == '')
						{
							$actions['addnremove'] = "<a href='".admin_url("admin.php?page=mf_address/list/index.php&intGroupID=".$post_id."&strFilterIsMember&strFilterAccepted&strFilterUnsubscribed")."' title='".__("Add or remove", $obj_group->lang_key)."'><i class='fas fa-tasks fa-lg'></i></a>";

							$actions['import'] = "<a href='".admin_url("admin.php?page=mf_group/import/index.php&intGroupID=".$post_id)."' title='".__("Import Addresses", $obj_group->lang_key)."'><i class='fas fa-cloud-upload-alt fa-lg'></i></a>";

							if($amount > 0)
							{
								//$actions['export_csv'] = "<a href='".wp_nonce_url(admin_url("admin.php?page=mf_group/list/index.php&btnExportRun&intExportType=".$post_id."&strExportFormat=csv"), 'export_run', '_wpnonce_export_run')."' title='".__("Export", $obj_group->lang_key)." CSV'><i class='fas fa-file-csv fa-lg'></i></a>";
								$actions['export_csv'] = "<a href='".wp_nonce_url($list_url."&btnExportRun&intExportType=".$post_id."&strExportFormat=csv", 'export_run', '_wpnonce_export_run')."' title='".__("Export", $obj_group->lang_key)." CSV'><i class='fas fa-file-csv fa-lg'></i></a>";

								if(is_plugin_active("mf_phpexcel/index.php"))
								{
									//$actions['export_xls'] = "<a href='".wp_nonce_url(admin_url("admin.php?page=mf_group/list/index.php&btnExportRun&intExportType=".$post_id."&strExportFormat=xls"), 'export_run', '_wpnonce_export_run')."' title='".__("Export", $obj_group->lang_key)." XLS'><i class='fas fa-file-excel fa-lg'></i></a>";
									$actions['export_xls'] = "<a href='".wp_nonce_url($list_url."&btnExportRun&intExportType=".$post_id."&strExportFormat=xls", 'export_run', '_wpnonce_export_run')."' title='".__("Export", $obj_group->lang_key)." XLS'><i class='fas fa-file-excel fa-lg'></i></a>";
								}
							}
						}
					}

					$out .= "<a href='".admin_url("admin.php?page=mf_address/list/index.php&intGroupID=".$post_id."&strFilterIsMember=yes&strFilterAccepted=yes&strFilterUnsubscribed=no")."'>"
						.$amount;

						$amount_deleted = $obj_group->amount_in_group(array('id' => $post_id, 'deleted' => 1));

						if($amount_deleted > 0)
						{
							$out .= " <span class='grey' title='".__("Deleted", $obj_group->lang_key)."'>+ ".$amount_deleted."</span>";
						}

					$out .= "</a>"
					.$this->row_actions($actions);
				break;

				case 'not_accepted':
					$rowsAddressesNotAccepted = $obj_group->amount_in_group(array('id' => $post_id, 'accepted' => 0));

					if($rowsAddressesNotAccepted > 0)
					{
						$out .= "<a href='".admin_url("admin.php?page=mf_address/list/index.php&intGroupID=".$post_id."&strFilterIsMember=yes&strFilterAccepted=no&strFilterUnsubscribed")."'>".$rowsAddressesNotAccepted."</a>";

						$rowsAddresses2Remind = $obj_group->amount_in_group(array('id' => $post_id, 'accepted' => 0, 'acceptance_sent' => date("Y-m-d H:i:s", strtotime("-1 week"))));

						if($rowsAddresses2Remind > 0)
						{
							$out .= "<div class='row-actions'>
								<a href='".wp_nonce_url($list_url."&btnGroupResend", 'group_resend_'.$post_id, '_wpnonce_group_resend')."' rel='confirm'>
									<i class='fa fa-recycle fa-lg' title='".sprintf(__("There are %d subscribers that can be reminded again. Do you want to do that?", $obj_group->lang_key), $rowsAddresses2Remind)."'></i>
								</a>
							</div>";
						}
					}
				break;

				case 'unsubscribed':
					$rowsAddressesUnsubscribed = $obj_group->amount_in_group(array('id' => $post_id, 'unsubscribed' => 1));

					$dteMessageCreated = $wpdb->get_var($wpdb->prepare("SELECT messageCreated FROM ".$wpdb->prefix."group_message WHERE groupID = '%d' AND messageDeleted = '0' ORDER BY messageCreated DESC", $post_id));

					if($rowsAddressesUnsubscribed > 0)
					{
						$out .= "<a href='".admin_url("admin.php?page=mf_address/list/index.php&intGroupID=".$post_id."&strFilterIsMember=yes&strFilterAccepted=yes&strFilterUnsubscribed=yes")."'>".$rowsAddressesUnsubscribed."</a>";
					}

					if($post_status == 'publish' || $dteMessageCreated != '')
					{
						//$current_user = wp_get_current_user();
						$user_data = get_userdata(get_current_user_id());

						$user_email = $user_data->user_email;

						$out .= "<div class='row-actions'>
							<a href='".$obj_group->get_group_url(array('type' => 'unsubscribe', 'group_id' => $post_id, 'email' => $user_email))."' rel='confirm'>".__("Test", $obj_group->lang_key)."</a>
						</div>";
					}
				break;

				case 'post_author':
					$out .= get_user_info(array('id' => $item['post_author'], 'type' => 'short_name'));
				break;

				case 'sent':
					$dteMessageCreated = $wpdb->get_var($wpdb->prepare("SELECT messageCreated FROM ".$wpdb->prefix."group_message WHERE groupID = '%d' AND messageDeleted = '0' ORDER BY messageCreated DESC LIMIT 0, 1", $post_id));

					if($dteMessageCreated > DEFAULT_DATE)
					{
						$actions = array();

						$out .= format_date($dteMessageCreated);

						$actions['sent'] = "<a href='".admin_url("admin.php?page=mf_group/sent/index.php&intGroupID=".$post_id)."'>".__("Sent", $obj_group->lang_key)."</a>";

						$intMessageID = $wpdb->get_var($wpdb->prepare("SELECT messageID FROM ".$wpdb->prefix."group_message INNER JOIN ".$wpdb->prefix."group_queue USING (messageID) WHERE groupID = '%d' AND messageDeleted = '0' AND queueSent = '0' GROUP BY messageID ORDER BY messageCreated DESC LIMIT 0, 1", $post_id));

						if($intMessageID > 0)
						{
							$intMessageSent = $wpdb->get_var($wpdb->prepare("SELECT COUNT(queueID) FROM ".$wpdb->prefix."group_queue WHERE messageID = '%d' AND queueSent = '1'", $intMessageID));
							$intMessageNotSent = $wpdb->get_var($wpdb->prepare("SELECT COUNT(queueID) FROM ".$wpdb->prefix."group_queue WHERE messageID = '%d' AND queueSent = '0'", $intMessageID));

							if($intMessageSent == 0)
							{
								if($intMessageNotSent == 0)
								{
									$out .= "<i class='set_tr_color' rel='red'></i>";
								}

								else
								{
									$out .= "<div><i class='fa fa-spinner fa-spin fa-lg'></i> ".sprintf(__("Will be sent %s", $obj_group->lang_key), get_next_cron())."</div>"
									."<i class='set_tr_color' rel='yellow'></i>";
								}
							}

							else if($intMessageSent < ($intMessageSent + $intMessageNotSent))
							{
								$out .= "&nbsp;<i class='fa fa-spinner fa-spin fa-lg'></i> ".__("Is sending", $obj_group->lang_key)
								."<i class='set_tr_color' rel='yellow'></i>";
							}
						}

						$out .= $this->row_actions($actions);
					}
				break;

				default:
					if(isset($item[$column_name]))
					{
						$out .= $item[$column_name];
					}
				break;
			}

			return $out;
		}
	}

	class mf_group_sent_table extends mf_list_table
	{
		function set_default()
		{
			global $wpdb;

			$this->arr_settings['query_from'] = $wpdb->prefix."group_message";
			$this->post_type = '';

			$this->arr_settings['query_select_id'] = "messageID";
			$this->arr_settings['query_all_id'] = "0";
			$this->arr_settings['query_trash_id'] = "1";
			$this->orderby_default = "messageCreated";
			$this->orderby_default_order = "DESC";

			$this->arr_settings['intGroupID'] = check_var('intGroupID');

			$this->arr_settings['page_vars'] = array('intGroupID' => $this->arr_settings['intGroupID']);
		}

		function init_fetch()
		{
			global $wpdb, $obj_group;

			$this->query_where .= "groupID = '".esc_sql($this->arr_settings['intGroupID'])."'";

			if($this->search != '')
			{
				$this->query_where .= ($this->query_where != '' ? " AND " : "")."(messageFrom LIKE '%".$this->search."%' OR messageName LIKE '%".$this->search."%' OR messageText LIKE '%".$this->search."%' OR messageCreated LIKE '%".$this->search."%' OR SOUNDEX(messageFrom) = SOUNDEX('".$this->search."') OR SOUNDEX(messageName) = SOUNDEX('".$this->search."') OR SOUNDEX(messageText) = SOUNDEX('".$this->search."'))";
			}

			$this->set_views(array(
				'db_field' => 'messageDeleted',
				'types' => array(
					'0' => __("All", $obj_group->lang_key),
					'1' => __("Trash", $obj_group->lang_key)
				),
			));

			$arr_columns = array(
				//'cb' => '<input type="checkbox">',
				'messageType' => __("Type", $obj_group->lang_key),
				'messageFrom' => __("From", $obj_group->lang_key),
				'messageName' => __("Content", $obj_group->lang_key),
				'messageSchedule' => __("Scheduled", $obj_group->lang_key),
				'sent' => __("Sent", $obj_group->lang_key),
				'messageCreated' => __("Created", $obj_group->lang_key),
			);

			$this->set_columns($arr_columns);

			$this->set_sortable_columns(array(
				'messageType',
				'messageFrom',
				'messageName',
				'messageSchedule',
				'messageCreated',
			));
		}

		function column_default($item, $column_name)
		{
			global $wpdb, $obj_group;

			$out = "";

			$intMessageID2 = $item['messageID'];

			switch($column_name)
			{
				case 'messageType':
					$actions = array(
						'view_data' => "<i class='fa fa-eye fa-lg' title='".__("View Content", $obj_group->lang_key)."'></i>",
						'send_to_group' => "<a href='".admin_url("admin.php?page=mf_group/send/index.php&intGroupID=".$this->arr_settings['intGroupID']."&intMessageID=".$intMessageID2)."'><i class='fa fa-users fa-lg' title='".__("Send to group again", $obj_group->lang_key)."'></i></a>",
						'send_email' => "<a href='".admin_url("admin.php?page=mf_email/send/index.php&intGroupMessageID=".$intMessageID2)."'><i class='fa fa-envelope fa-lg' title='".__("Send to e-mail", $obj_group->lang_key)."'></i></a>",
					);

					switch($item['messageType'])
					{
						case 'email':
							$strMessageType = __("E-mail", $obj_group->lang_key);
						break;

						default:
							$strMessageType = apply_filters('get_group_message_type', $item['messageType']);
						break;

						/*case 'sms':
							$strMessageType = __("SMS", $obj_group->lang_key);
						break;*/
					}

					$out .= $strMessageType
					.$this->row_actions($actions);
				break;

				case 'messageFrom':
					$strMessageFrom = $item['messageFrom'];

					$actions = array();

					$strMessageFromName = "";

					if(preg_match("/\|/", $strMessageFrom))
					{
						list($strMessageFromName, $strMessageFrom) = explode("|", $strMessageFrom);

						$actions['from'] = $strMessageFromName;
					}

					$out .= $strMessageFrom
					.$this->row_actions($actions);
				break;

				case 'messageName':
					if($item['messageName'] != '')
					{
						$out .= $item['messageName'];
					}

					else
					{
						$out .= shorten_text(array('string' => $item['messageText'], 'limit' => 20));
					}

					$actions = array();

					if(IS_ADMIN || $item['userID'] == get_current_user_id())
					{
						if($item['messageDeleted'] == 0)
						{
							$actions['delete'] = "<a href='".wp_nonce_url(admin_url("admin.php?page=mf_group/sent/index.php&btnMessageDelete&intGroupID=".$this->arr_settings['intGroupID']."&intMessageID=".$intMessageID2), 'message_delete_'.$intMessageID2, '_wpnonce_message_delete')."'>".__("Delete", $obj_group->lang_key)."</a>";
						}
					}

					$out .= $this->row_actions($actions);
				break;

				case 'messageSchedule':
					if($item['messageSchedule'] > DEFAULT_DATE)
					{
						$out .= format_date($item['messageSchedule']);
					}
				break;

				case 'sent':
					$intMessageSent = $wpdb->get_var($wpdb->prepare("SELECT COUNT(queueID) FROM ".$wpdb->prefix."group_queue WHERE messageID = '%d' AND queueSent = '1'", $intMessageID2));
					$intMessageNotSent = $wpdb->get_var($wpdb->prepare("SELECT COUNT(queueID) FROM ".$wpdb->prefix."group_queue WHERE messageID = '%d' AND queueSent = '0'", $intMessageID2));

					$intMessageTotal = $intMessageSent + $intMessageNotSent;

					$class = '';

					if($intMessageSent == 0)
					{
						$class = ($intMessageNotSent == 0 ? "red" : "yellow");
					}

					else if($intMessageSent < $intMessageTotal)
					{
						$class = "yellow";
					}

					$actions = array();

					if($intMessageSent > 0)
					{
						if($item['messageCreated'] > date("Y-m-d H:i:s", strtotime("-1 month")))
						{
							$dteQueueSentTime_first = $wpdb->get_var($wpdb->prepare("SELECT MIN(queueSentTime) FROM ".$wpdb->prefix."group_queue WHERE messageID = '%d' AND queueSent = '1'", $intMessageID2));

							if($dteQueueSentTime_first > DEFAULT_DATE)
							{
								$is_same_day = (date("Y-m-d", strtotime($item['messageCreated'])) == date("Y-m-d", strtotime($dteQueueSentTime_first)));

								if($is_same_day)
								{
									$actions['sent'] = date("H:i", strtotime($dteQueueSentTime_first));
								}

								else
								{
									$actions['sent'] = format_date($dteQueueSentTime_first);
								}

								$dteQueueSentTime_last = $wpdb->get_var($wpdb->prepare("SELECT MAX(queueSentTime) FROM ".$wpdb->prefix."group_queue WHERE messageID = '%d' AND queueSent = '1'", $intMessageID2));

								if($is_same_day)
								{
									if(date("H:i", strtotime($dteQueueSentTime_last)) != $actions['sent'])
									{
										$actions['sent'] .= " - ".date("H:i", strtotime($dteQueueSentTime_last));
									}
								}

								else if($dteQueueSentTime_last > $dteQueueSentTime_first && format_date($dteQueueSentTime_last) != format_date($dteQueueSentTime_first))
								{
									$actions['sent'] .= " - ".format_date($dteQueueSentTime_last);
								}
							}
						}

						$intMessageErrors = $wpdb->get_var($wpdb->prepare("SELECT COUNT(queueID) FROM ".$wpdb->prefix."group_queue WHERE messageID = '%d' AND queueReceived = '-1'", $intMessageID2));
						$intMessageReceived = $wpdb->get_var($wpdb->prepare("SELECT COUNT(queueID) FROM ".$wpdb->prefix."group_queue WHERE messageID = '%d' AND queueReceived = '1'", $intMessageID2));

						if($intMessageErrors > 0)
						{
							$actions['errors'] = mf_format_number($intMessageErrors / $intMessageSent * 100, 1)."% ".__("Errors", $obj_group->lang_key);
						}

						if($intMessageReceived > 0)
						{
							$actions['read'] = "<i class='fa fa-check green'></i> ".$intMessageReceived." ".__("Read", $obj_group->lang_key);
						}
					}

					if($intMessageNotSent > 0)
					{
						$actions['abort'] = "<a href='".wp_nonce_url(admin_url("admin.php?page=mf_group/sent/index.php&btnMessageAbort&intGroupID=".$this->arr_settings['intGroupID']."&intMessageID=".$intMessageID2), 'message_abort_'.$intMessageID2, '_wpnonce_message_abort')."'>".__("Abort", $obj_group->lang_key)."</a>";
					}

					$out .= $intMessageSent." / ".$intMessageTotal
					.$this->row_actions($actions);

					if($class != '')
					{
						$out .= "<i class='set_tr_color' rel='".$class."'></i>";
					}
				break;

				case 'messageCreated':
					$out .= format_date($item['messageCreated'])
					."</td></tr><tr class='hide'><td colspan='".count($this->columns)."'>";

					$out .= stripslashes(apply_filters('the_content', $item['messageText']));

					if($item['messageAttachment'] != '')
					{
						$out .= "<p>".get_media_button(array('name' => 'strMessageAttachment', 'value' => $item['messageAttachment'], 'show_add_button' => false))."</p>";
					}

					$result_sent = $wpdb->get_results($wpdb->prepare("SELECT addressFirstName, addressSurName, addressEmail, addressCellNo, queueSent FROM ".$wpdb->prefix."group_queue INNER JOIN ".get_address_table_prefix()."address USING (addressID) WHERE messageID = '%d' ORDER BY queueID ASC LIMIT 0, 100", $intMessageID2));

					if($wpdb->num_rows > 0)
					{
						$out .= "<ol>";

							foreach($result_sent as $r)
							{
								$strAddressFirstName = $r->addressFirstName;
								$strAddressSurName = $r->addressSurName;
								$strAddressEmail = $r->addressEmail;
								$strAddressCellNo = $r->addressCellNo;
								$intQueueSent = $r->queueSent;

								$out .= "<li>"
									."<i class='".($intQueueSent == 1 ? "fa fa-check green" : "fa fa-times red")."'></i> ";

									if($strAddressFirstName != '' || $strAddressSurName != '')
									{
										$out .= $strAddressFirstName." ".$strAddressSurName;
									}

									else
									{
										switch($item['messageType'])
										{
											case 'email':
												$out .= $strAddressEmail;
											break;

											default:
												//
											break;

											case 'sms':
												$out .= $strAddressCellNo;
											break;
										}
									}
								
								$out .= "</li>";
							}

						$out .= "</ol>";
					}
				break;

				default:
					if(isset($item[$column_name]))
					{
						$out .= $item[$column_name];
					}
				break;
			}

			return $out;
		}
	}
}

if(class_exists('mf_export'))
{
	class mf_group_export extends mf_export
	{
		function get_defaults()
		{
			$this->plugin = "mf_group";
		}

		function get_export_data()
		{
			global $wpdb, $obj_address, $obj_group;

			if(!isset($obj_group))
			{
				$obj_group = new mf_group();
			}

			$this->name = $obj_group->get_name(array('id' => $this->type));

			if(!isset($obj_address))
			{
				$obj_address = new mf_address();
			}

			$arr_countries = $obj_address->get_countries_for_select();

			$result = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".get_address_table_prefix()."address INNER JOIN ".$wpdb->prefix."address2group USING (addressID) WHERE groupID = '%d' AND addressDeleted = '0' GROUP BY addressID ORDER BY addressPublic ASC, addressSurName ASC, addressFirstName ASC", $this->type));

			foreach($result as $r)
			{
				$this->data[] = array(
					$r->addressMemberID,
					$r->addressBirthDate,
					$r->addressFirstName,
					$r->addressSurName,
					$r->addressAddress,
					$r->addressCo,
					$r->addressZipCode,
					$r->addressCity,
					($r->addressCountry > 0 && isset($arr_countries[$r->addressCountry]) ? $arr_countries[$r->addressCountry] : ''),
					$r->addressTelNo,
					$r->addressCellNo,
					$r->addressWorkNo,
					$r->addressEmail,
					$r->addressExtra,
				);
			}
		}
	}
}

class widget_group extends WP_Widget
{
	function __construct()
	{
		$this->obj_group = new mf_group();

		$this->widget_ops = array(
			'classname' => 'widget_group',
			'description' => __("Display a group registration form", $this->obj_group->lang_key)
		);

		$this->arr_default = array(
			'group_heading' => '',
			'group_text' => '',
			'group_id' => '',
			'group_button_text' => '',
		);

		parent::__construct('group-widget', __("Group", $this->obj_group->lang_key)." / ".__("Newsletter", $this->obj_group->lang_key), $this->widget_ops);

	}

	function widget($args, $instance)
	{
		extract($args);
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		if($instance['group_id'] > 0)
		{
			echo $before_widget;

				if($instance['group_heading'] != '')
				{
					$instance['group_heading'] = apply_filters('widget_title', $instance['group_heading'], $instance, $this->id_base);

					echo $before_title
						.$instance['group_heading']
					.$after_title;
				}

				echo "<div class='section'>"
					.$this->obj_group->show_group_registration_form(array('id' => $instance['group_id'], 'text' => $instance['group_text'], 'button_text' => $instance['group_button_text']))
				."</div>"
			.$after_widget;
		}
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;
		$new_instance = wp_parse_args((array)$new_instance, $this->arr_default);

		$instance['group_heading'] = sanitize_text_field($new_instance['group_heading']);
		$instance['group_text'] = sanitize_text_field($new_instance['group_text']);
		$instance['group_id'] = sanitize_text_field($new_instance['group_id']);
		$instance['group_button_text'] = sanitize_text_field($new_instance['group_button_text']);

		return $instance;
	}

	function form($instance)
	{
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		$arr_data = array();
		get_post_children(array('add_choose_here' => true, 'post_type' => $this->obj_group->post_type, 'post_status' => '', 'where' => "post_status != 'trash'".(IS_EDITOR ? "" : " AND post_author = '".get_current_user_id()."'")), $arr_data);

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('group_heading'), 'text' => __("Heading", $this->obj_group->lang_key), 'value' => $instance['group_heading'], 'xtra' => " id='".$this->widget_ops['classname']."-title'"))
			.show_textarea(array('name' => $this->get_field_name('group_text'), 'text' => __("Text", $this->obj_group->lang_key), 'value' => $instance['group_text']))
			.show_select(array('data' => $arr_data, 'name' => $this->get_field_name('group_id'), 'text' => __("Group", $this->obj_group->lang_key), 'value' => $instance['group_id']))
			.show_textfield(array('name' => $this->get_field_name('group_button_text'), 'text' => __("Button Text", $this->obj_group->lang_key), 'value' => $instance['group_button_text'], 'placeholder' => __("Join", $this->obj_group->lang_key)))
		."</div>";
	}
}