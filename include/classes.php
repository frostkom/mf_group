<?php

class mf_group
{
	var $id;
	var $type;
	var $post_type = 'mf_group';
	var $meta_prefix;
	var $arr_stop_list_groups = array();
	var $arr_stop_list_recipients = array();
	var $group_month;
	var $message_type;
	var $group_id;
	var $arr_group_id;
	var $message_id;
	var $message_from;
	var $message_name;
	var $message_text;
	var $message_schedule_date;
	var $message_schedule_time;
	var $message_text_source;
	var $message_attachment;
	var $public;
	var $name;
	var $acceptance_email;
	var $acceptance_subject;
	var $acceptance_text;
	var $reminder_subject;
	var $reminder_text;
	var $owner_email;
	var $help_page;
	var $archive_page;
	var $group_type;
	var $allow_registration;
	var $verify_address;
	var $contact_page;
	var $registration_fields;
	var $verify_link;
	var $sync_users;
	var $id_copy;
	var $api;
	var $api_filter;
	var $query_where = "";

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

		$this->meta_prefix = $this->post_type.'_';
	}

	function is_synced($group_id)
	{
		return (get_post_meta($group_id, $this->meta_prefix.'api', true) != '' || get_post_meta($group_id, $this->meta_prefix.'sync_users', true) != 'no');
	}

	function get_for_select($data = array())
	{
		if(!isset($data['add_choose_here'])){		$data['add_choose_here'] = true;}
		if(!isset($data['include_amount'])){		$data['include_amount'] = true;}
		if(!isset($data['return_to_metabox'])){		$data['return_to_metabox'] = true;}

		$tbl_group = new mf_group_table();

		$tbl_group->select_data(array(
			'select' => "ID, post_title",
		));

		$arr_data = array();

		if(count($tbl_group->data) > 0)
		{
			if($data['add_choose_here'])
			{
				$arr_data[''] = "-- ".__("Choose Here", 'lang_group')." --";
			}

			foreach($tbl_group->data as $r)
			{
				if($data['include_amount'] == true)
				{
					$amount = $this->amount_in_group(array('id' => $r['ID']));

					if($data['return_to_metabox'] == true)
					{
						$arr_data[$r['ID']] = $r['post_title']." (".$amount.")";
					}

					else
					{
						$arr_data[$r['ID']] = array(
							'name' => $r['post_title']." (".$amount.")",
							'attributes' => array(
								'amount' => $amount,
							),
						);
					}
				}

				else
				{
					if($data['return_to_metabox'] == true)
					{
						$arr_data[$r['ID']] = $r['post_title'];
					}

					else
					{
						$arr_data[$r['ID']] = array(
							'name' => $r['post_title'],
						);
					}
				}
			}
		}

		return $arr_data;
	}

	function get_version_months_for_select()
	{
		global $wpdb;

		$arr_data = array();

		$result = $wpdb->get_results($wpdb->prepare("SELECT SUBSTRING(versionCreated, 1, 7) AS versionMonth FROM ".$wpdb->prefix."group_version WHERE groupID = '%d' GROUP BY versionMonth ORDER BY versionMonth DESC", $this->id));

		$year_temp = 0;

		foreach($result as $r)
		{
			if($this->group_month == '')
			{
				$this->group_month = $r->versionMonth;
			}

			list($year, $month) = explode("-", $r->versionMonth);

			if($year != $year_temp)
			{
				if($year_temp > 0)
				{
					$arr_data["opt_end_".$year_temp] = "";
				}

				$year_temp = $year;

				$arr_data["opt_start_".$year_temp] = $year_temp;
			}

			$arr_data[$r->versionMonth] = month_name($month);
		}

		if($year_temp > 0)
		{
			$arr_data["opt_end_".$year_temp] = "";
		}

		$arr_data['all'] = __("All", 'lang_group');

		return $arr_data;
	}

	function get_types_for_select($data = array())
	{
		return array(
			'normal' => __("Normal", 'lang_group'),
			'stop' => __("Stop List", 'lang_group'),
		);
	}

	function get_registration_fields_for_select()
	{
		return array(
			'name' => __("Name", 'lang_group'),
			'address' => __("Address", 'lang_group'),
			'zip' => __("Zip Code", 'lang_group'),
			'city' => __("City", 'lang_group'),
			'phone' => __("Phone Number", 'lang_group'),
			'email' => __("E-mail", 'lang_group'),
			'extra' => get_option_or_default('setting_address_extra', __("Extra", 'lang_group')),
		);
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

		return $wpdb->get_results(
			"SELECT ID, post_status, post_name, post_title, post_modified, post_author FROM ".$wpdb->posts." WHERE post_type = '".esc_sql($this->post_type)."'"
			.$data['where']
			." ORDER BY ".$data['order']
			.($data['limit'] != '' && $data['amount'] != '' ? " LIMIT ".$data['limit'].", ".$data['amount'] : "")
		);
	}

	function set_received($data)
	{
		global $wpdb;

		if($data['queue_id'] > 0)
		{
			$result = $wpdb->get_results($wpdb->prepare("SELECT addressID FROM ".$wpdb->prefix."address INNER JOIN ".$wpdb->prefix."group_queue USING (addressID) WHERE queueID = '%d'", $data['queue_id']));

			foreach($result as $r)
			{
				$intAddressID = $r->addressID;

				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."group_queue SET queueStatus = 'viewed', queueViewed = NOW() WHERE queueID = '%d'", $data['queue_id']));

				$obj_address = new mf_address(array('id' => $intAddressID));
				$obj_address->update_errors(array('action' => 'reset'));
			}
		}
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
			case 'redirect':
			case 'view_in_browser':
				$out .= $base_url.$data['type']."=".md5((defined('NONCE_SALT') ? NONCE_SALT : '').$data['group_id'].$data['email'].$data['message_id']);

				if($data['queue_id'] > 0 || $data['queue_id'] == "[queue_id]")
				{
					$out .= "&qid=".$data['queue_id'];
				}

				else
				{
					$out .= "&gid=".$data['group_id']
					."&mid=".$data['message_id']
					."&aem=".$data['email'];
				}
			break;

			case 'subscribe':
			case 'unsubscribe':
			case 'verify':
				$out .= $base_url.$data['type']."=".md5((defined('NONCE_SALT') ? NONCE_SALT : '').$data['group_id'].$data['email']);

				if($data['queue_id'] > 0)
				{
					$out .= "&qid=".$data['queue_id'];
				}

				else
				{
					$out .= "&gid=".$data['group_id']
					."&aem=".$data['email'];
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

		$result = $wpdb->get_results("SELECT addressID FROM ".$wpdb->prefix."address WHERE addressDeleted = '0' AND ".$query_where);
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

			$result = $wpdb->get_results("SELECT addressID FROM ".$wpdb->prefix."address WHERE addressDeleted = '0' AND ".$query_where);
			$rows = $wpdb->num_rows;
		}

		return $result;
	}

	function convert_links($data)
	{
		global $wpdb;

		$arr_links = get_match_all('#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $data['message_text']);

		foreach($arr_links as $link)
		{
			// Ignore images/files
			if(strpos($link, "/wp-content/") !== false)
			{
				$intLinkID = $wpdb->get_var($wpdb->prepare("SELECT linkID FROM ".$wpdb->prefix."group_message_link WHERE linkUrl = %s", $link));

				if($intLinkID > 0)
				{
					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."group_message_link SET linkUsed = NOW() WHERE linkID = '%d'", $intLinkID));
				}

				else
				{
					$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."group_message_link SET linkUrl = %s, linkUsed = NOW()", $link));

					$intLinkID = $wpdb->insert_id;
				}

				$data['message_text'] = str_replace($link, "[redirect]&lid=".$intLinkID, $data['message_text']);
			}
		}

		return $data['message_text'];
	}

	function cron_base()
	{
		global $wpdb, $obj_address, $error_text;

		$obj_cron = new mf_cron();
		$obj_cron->start(__CLASS__);

		if($obj_cron->is_running == false)
		{
			// Make sure that it has been created in activate_group because this might be a new site that has just been created
			if(does_table_exist($wpdb->prefix."group_message"))
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

					$resultAddresses = $wpdb->get_results($wpdb->prepare("SELECT queueID, messageID, addressEmail, addressCellNo FROM ".$wpdb->prefix."address INNER JOIN ".$wpdb->prefix."group_queue USING (addressID) INNER JOIN ".$wpdb->prefix."group_message USING (messageID) WHERE groupID = '%d' AND messageDeleted = '0' AND queueSent = '0' ORDER BY messageType ASC, queueCreated ASC".$this->get_message_query_limit(), $intGroupID));

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

								if($strMessageType == 'email' && get_option('setting_group_trace_links', 'yes') == 'yes')
								{
									$strMessageText = $this->convert_links(array('message_text' => $strMessageText));
								}
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

									$strMessageName = stripslashes(stripslashes($strMessageName));
									$strMessageText = stripslashes(apply_filters('the_content', $strMessageText));

									if($setting_group_outgoing_text != '')
									{
										$strMessageText .= apply_filters('the_content', $setting_group_outgoing_text);
									}

									if(get_option('setting_group_debug') == 'yes')
									{
										do_log("Group Init Message: About to send message (".$intGroupID.", ".$strMessageFrom.", ".$strMessageName.")");
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
											$view_in_browser_url = $this->get_group_url(array('type' => 'view_in_browser', 'group_id' => $intGroupID, 'message_id' => $intMessageID, 'email' => $strAddressEmail, 'queue_id' => $intQueueID));
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

											if(strpos($mail_content, "[redirect]"))
											{
												$mail_content = str_replace("[redirect]", $this->get_group_url(array('type' => 'redirect', 'group_id' => $intGroupID, 'email' => $strAddressEmail, 'message_id' => $intMessageID, 'queue_id' => $intQueueID)), $mail_content);
											}

											list($mail_attachment, $rest) = get_attachment_to_send($strMessageAttachment);

											$wpdb->get_results($wpdb->prepare("SELECT queueID FROM ".$wpdb->prefix."group_queue WHERE queueSent = '1' AND queueID = '%d'", $intQueueID));
											$rows = $wpdb->num_rows;

											if($rows == 0)
											{
												$setting_email_log = get_site_option('setting_email_log');

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
								list($sent, $message) = $obj_sms->send_sms(array('from' => $strMessageFrom, 'to' => $strAddressCellNo, 'text' => $strMessageText, 'user_id' => $intUserID));

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
				$result = $wpdb->get_results($wpdb->prepare("SELECT ID, meta_value FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = %s AND post_status = %s AND meta_key = %s AND meta_value != %s", $this->post_type, 'publish', $this->meta_prefix.'sync_users', 'no'));

				foreach($result as $r)
				{
					$intGroupID = $r->ID;
					$sync_type = $r->meta_value;

					$arr_addresses = array();

					switch($sync_type)
					{
						case 'yes':
							$users = get_users(array('fields' => 'all'));

							foreach($users as $user)
							{
								$strUserFirstName = $user->first_name;
								$strUserSurName = $user->last_name;
								$strUserEmail = $user->user_email;

								if($strUserFirstName == '' || $strUserSurName == '')
								{
									@list($strUserFirstName, $strUserSurName) = explode(" ", $user->display_name, 2);
								}

								$arr_addresses[] = array(
									'email' => $strUserEmail,
									'first_name' => $strUserFirstName,
									'sur_name' => $strUserSurName,
								);
							}
						break;

						default:
							$arr_addresses = apply_filters('get_group_sync_addresses', $arr_addresses, $sync_type);
						break;
					}

					$arr_address_ids = array();

					foreach($arr_addresses as $arr_address)
					{
						$intAddressID = $wpdb->get_var($wpdb->prepare("SELECT addressID FROM ".$wpdb->prefix."address WHERE addressEmail = %s", $arr_address['email']));

						if($intAddressID > 0)
						{
							$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."address SET addressFirstName = %s, addressSurName = %s WHERE addressID = '%d'", $arr_address['first_name'], $arr_address['sur_name'], $intAddressID));
						}

						else
						{
							$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."address SET addressFirstName = %s, addressSurName = %s, addressEmail = %s, addressCreated = NOW()", $arr_address['first_name'], $arr_address['sur_name'], $arr_address['email']));

							$intAddressID = $wpdb->insert_id;
						}

						if($intAddressID > 0)
						{
							$this->add_address(array('address_id' => $intAddressID, 'group_id' => $intGroupID));

							$arr_address_ids[] = $intAddressID;
						}
					}

					$result_addresses = $wpdb->get_results($wpdb->prepare("SELECT addressID FROM ".$wpdb->prefix."address2group WHERE groupID = '%d' AND addressID NOT IN ('".implode("','", $arr_address_ids)."')", $intGroupID));

					foreach($result_addresses as $r)
					{
						$this->remove_address(array('address_id' => $r->addressID, 'group_id' => $intGroupID));
					}
				}
				#############################

				/* Synchronize with API */
				#############################
				$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, meta_value FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = %s AND post_status = %s AND meta_key = %s AND meta_value != ''", $this->post_type, 'publish', $this->meta_prefix.'api'));

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

					$count_incoming = $count_found = $count_found_error = $count_inserted = $count_inserted_error = $count_added = $count_exists_in_group = $count_exists_in_array = $count_removed = $count_removed_error = 0;

					$arr_addresses = array();

					foreach($arr_post_meta_api as $post_meta_api)
					{
						// Empty spaces might appear when exploding the string
						$post_meta_api = trim($post_meta_api);

						list($content, $headers) = get_url_content(array(
							'url' => htmlspecialchars_decode($post_meta_api),
							'catch_head' => true,
						));

						$log_message = __("I could not get a successful result from the Group API", 'lang_group');

						switch($headers['http_code'])
						{
							case 200:
								$json = json_decode($content, true);

								$log_message_2 = __("The status was wrong in the Group API", 'lang_group');

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
													$strAddressExtra = $item['associationName'];
													$strAddressCo = $intAddressCountry = $strAddressTelNo = $strAddressWorkNo = "";
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
													$strAddressExtra = "";
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
														if(!isset($obj_address))
														{
															$obj_address = new mf_address();
														}

														$result = $this->check_if_exists(array('birthdate' => $strAddressBirthDate, 'email' => $strAddressEmail));
														$rows = $wpdb->num_rows;

														if($rows == 0)
														{
															$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."address SET addressPublic = '%d', addressBirthDate = %s, addressFirstName = %s, addressSurName = %s, addressZipCode = %s, addressCity = %s, addressCountry = '%d', addressAddress = %s, addressCo = %s, addressTelNo = %s, addressCellNo = %s, addressWorkNo = %s, addressEmail = %s, addressExtra = %s, addressCreated = NOW(), userID = '%d'", $intAddressPublic, $strAddressBirthDate, $strAddressFirstName, $strAddressSurName, $intAddressZipCode, $strAddressCity, $intAddressCountry, $strAddressAddress, $strAddressCo, $strAddressTelNo, $strAddressCellNo, $strAddressWorkNo, $strAddressEmail, $strAddressExtra, get_current_user_id()));

															if($wpdb->rows_affected > 0)
															{
																$intAddressID_temp = $wpdb->insert_id;

																$obj_address->save_sync_date(array('address_id' => $intAddressID_temp));

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
																	$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."address SET addressBirthDate = %s, addressFirstName = %s, addressSurName = %s, addressZipCode = %s, addressCity = %s, addressCountry = '%d', addressAddress = %s, addressCo = %s, addressTelNo = %s, addressCellNo = %s, addressWorkNo = %s, addressEmail = %s, addressExtra = %s WHERE addressID = '%d'", $strAddressBirthDate, $strAddressFirstName, $strAddressSurName, $intAddressZipCode, $strAddressCity, $intAddressCountry, $strAddressAddress, $strAddressCo, $strAddressTelNo, $strAddressCellNo, $strAddressWorkNo, $strAddressEmail, $strAddressExtra, $intAddressID));

																	$obj_address->save_sync_date(array('address_id' => $intAddressID));

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

									if($this->remove_address(array('address_id' => $intAddressID, 'group_id' => $post_id)))
									{
										$count_removed++;
									}

									else
									{
										$count_removed_error++;
									}
								}
							}
						}

						else if(get_option('setting_group_debug') == 'yes')
						{
							do_log("Group API - ".$post_title." - No rows found to remove (Address array): ".$wpdb->last_query);
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
						do_log("Group API - ".$post_title." - Report: ".$count_found."/".$count_incoming." found with ".$count_found_error." errors. ".$count_inserted." inserted with ".$count_inserted_error." errors. ".$count_added." added, ".$count_exists_in_group." (+".$count_exists_in_array." duplicates) exists and ".$count_removed." removed with ".$count_removed_error." errors");
					}
				}
				#############################

				// Get error log
				#############################
				$setting_group_log_file = get_option('setting_group_log_file');

				if($setting_group_log_file != '' && file_exists($setting_group_log_file))
				{
					$error_limit = (MB_IN_BYTES * 5);

					if(filesize($setting_group_log_file) < $error_limit)
					{
						$arr_file = file($setting_group_log_file);

						if(is_array($arr_file))
						{
							$arr_file = array_unique($arr_file);

							foreach($arr_file as $value)
							{
								$value_date = get_match("/^(.*?) www/is", $value, false);
								$value_type = get_match("/postfix\/(.*?)\[/is", $value, false);
								$value_email = get_match("/to\=\<(.*?)\>/is", $value, false);
								$value_status = get_match("/status\=(.*?) /is", $value, false);
								$value_message = get_match("/ \((.*?)\)$/is", $value, false);

								$value_date = date("Y-m-d H:i:s", strtotime($value_date));

								$result = $wpdb->get_results($wpdb->prepare("SELECT queueID, queueSentTime FROM ".$wpdb->prefix."group_queue INNER JOIN ".$wpdb->prefix."address USING (addressID) WHERE addressEmail = %s AND queueStatus IN('not_received', 'not_viewed') AND queueSentTime < %s ORDER BY queueSentTime DESC LIMIT 0, 1", $value_email, $value_date));

								foreach($result as $r)
								{
									$intQueueID = $r->queueID;
									$dteQueueSentTime = $r->queueSentTime;

									$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."group_queue SET queueStatus = %s, queueStatusMessage = %s WHERE queueID = '%d'", $value_status, $value_message, $intQueueID));
								}
							}
						}

						if(file_exists($setting_group_log_file))
						{
							unlink($setting_group_log_file);
						}
					}

					else
					{
						do_log(sprintf("%s was too large so it should be deleted", basename($setting_group_log_file)));

						/*if(file_exists($setting_group_log_file))
						{
							unlink($setting_group_log_file);
						}*/
					}
				}
				#############################

				/* Remove old links */
				#############################
				$wpdb->query("DELETE FROM ".$wpdb->prefix."group_message_link WHERE linkUsed < DATE_SUB(NOW(), INTERVAL 1 YEAR)");
				#############################
			}

			// Delete old uploads
			#######################
			list($upload_path, $upload_url) = get_uploads_folder($this->post_type);

			get_file_info(array('path' => $upload_path, 'callback' => 'delete_files_callback', 'time_limit' => WEEK_IN_SECONDS));
			get_file_info(array('path' => $upload_path, 'folder_callback' => 'delete_empty_folder_callback'));
			#######################
		}

		$obj_cron->end();
	}

	function show_group_registration_form($data)
	{
		global $wpdb, $obj_address, $done_text, $error_text;

		if(!isset($data['text'])){											$data['text'] = '';}
		if(!isset($data['label_type'])){									$data['label_type'] = '';}
		if(!isset($data['display_consent'])){								$data['display_consent'] = 'yes';}
		if(!isset($data['button_text']) || $data['button_text'] == ''){		$data['button_text'] = __("Join", 'lang_group');}
		if(!isset($data['button_icon'])){									$data['button_icon'] = '';}

		$out = "";

		if(!($data['id'] > 0))
		{
			$error_text = __("I could not find any group ID to display a form for", 'lang_group');
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
				$out .= "<p>".__("The information that you entered is not in our register. Please contact the admin of this page to get your information submitted.", 'lang_group')."</p>";

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
					$wpdb->query("INSERT INTO ".$wpdb->prefix."address SET addressPublic = '1'".$query_set.", addressCreated = NOW()");

					$intAddressID = $wpdb->insert_id;
				}

				if($intAddressID > 0)
				{
					$this->add_address(array('address_id' => $intAddressID, 'group_id' => $data['id']));

					$done_text = __("Thank you for showing your interest. You have been added to the group", 'lang_group');
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
					switch($data['label_type'])
					{
						default:
						case 'label':
							$label_type = 'text';
						break;

						case 'placeholder':
							$label_type = 'placeholder';
						break;
					}

					if(in_array("name", $arrGroupRegistrationFields))
					{
						$out .= show_textfield(array('name' => 'strAddressName', $label_type => __("Name", 'lang_group'), 'value' => $strAddressName, 'required' => true));
					}

					if(in_array("address", $arrGroupRegistrationFields))
					{
						$out .= show_textfield(array('name' => 'strAddressAddress', $label_type => __("Address", 'lang_group'), 'value' => $strAddressAddress, 'required' => true));
					}

					if(in_array("zip", $arrGroupRegistrationFields))
					{
						$out .= show_textfield(array('type' => 'number', 'name' => 'intAddressZipCode', $label_type => __("Zip Code", 'lang_group'), 'value' => $intAddressZipCode, 'required' => true));
					}

					if(in_array("city", $arrGroupRegistrationFields))
					{
						$out .= show_textfield(array('name' => 'strAddressCity', $label_type => __("City", 'lang_group'), 'value' => $strAddressCity, 'required' => true));
					}

					if(in_array("country", $arrGroupRegistrationFields))
					{
						if(!isset($obj_address))
						{
							$obj_address = new mf_address();
						}

						switch($data['label_type'])
						{
							default:
							case 'label':
								$out .= show_select(array('data' => $obj_address->get_countries_for_select(), 'name' => 'intAddressCountry', 'text' => __("Country", 'lang_group'), 'value' => $intAddressCountry, 'required' => true));
							break;

							case 'placeholder':
								$out .= show_select(array('data' => $obj_address->get_countries_for_select(array('choose_here_text' => __("Choose Country Here", 'lang_group'))), 'name' => 'intAddressCountry', 'value' => $intAddressCountry, 'required' => true));
							break;
						}
					}

					if(in_array("phone", $arrGroupRegistrationFields))
					{
						$out .= show_textfield(array('name' => 'strAddressTelNo', $label_type => __("Phone Number", 'lang_group'), 'value' => $strAddressTelNo, 'required' => true));
					}

					if(in_array("email", $arrGroupRegistrationFields))
					{
						$out .= show_textfield(array('name' => 'strAddressEmail', $label_type => __("E-mail", 'lang_group'), 'value' => $strAddressEmail, 'required' => true));
					}

					if(in_array("extra", $arrGroupRegistrationFields))
					{
						$out .= show_textfield(array('name' => 'strAddressExtra', $label_type => get_option_or_default('setting_address_extra', __("Extra", 'lang_group')), 'value' => $strAddressExtra, 'required' => true));
					}

					$out .= "<div class='form_button'>";

						if($data['display_consent'] == 'yes')
						{
							$out .= show_checkbox(array('name' => 'intGroupConsent', 'text' => __("I consent to having this website store my submitted information, so that they can contact me as part of this group", 'lang_group'), 'value' => 1, 'required' => true));
						}

						$out .= show_button(array('name' => 'btnGroupJoin', 'text' => $data['button_text'].($data['button_icon'] != '' ? " <i class='".$data['button_icon']."'></i>" : "")))
					."</div>";
				}

				else
				{
					$out .= "<div class='flex_form'>"
						.show_textfield(array('name' => 'strAddressEmail', 'placeholder' => __("Your Email Address", 'lang_group'), 'value' => $strAddressEmail, 'required' => true))
						."<div class='form_button'>"
							.show_button(array('name' => 'btnGroupJoin', 'text' => $data['button_text'].($data['button_icon'] != '' ? " <i class='".$data['button_icon']."'></i>" : "")))
						."</div>
					</div>";

					if($data['display_consent'] == 'yes')
					{
						$out .= show_checkbox(array('name' => 'intGroupConsent', 'text' => __("I consent to having this website store my submitted information, so that they can contact me as part of this group", 'lang_group'), 'value' => 1, 'required' => true));
					}
				}

			$out .= "</form>";
		}

		return $out;
	}

	function block_render_callback($attributes)
	{
		if(!isset($attributes['group_id'])){				$attributes['group_id'] = 0;}
		if(!isset($attributes['group_heading'])){			$attributes['group_heading'] = '';}
		if(!isset($attributes['group_text'])){				$attributes['group_text'] = '';}
		if(!isset($attributes['group_label_type'])){		$attributes['group_label_type'] = '';}
		if(!isset($attributes['group_display_consent'])){	$attributes['group_display_consent'] = 'yes';}
		if(!isset($attributes['group_button_text'])){		$attributes['group_button_text'] = '';}
		if(!isset($attributes['group_button_icon'])){		$attributes['group_button_icon'] = '';}

		$out = "";

		if($attributes['group_id'] > 0)
		{
			$out .= "<div class='widget widget_group'>";

				if($attributes['group_heading'] != '')
				{
					$out .= "<h3>".$attributes['group_heading']."</h3>";
				}

				$out .= "<div class='section'>"
					.$this->show_group_registration_form(array('id' => $attributes['group_id'], 'text' => $attributes['group_text'], 'label_type' => $attributes['group_label_type'], 'display_consent' => $attributes['group_display_consent'], 'button_text' => $attributes['group_button_text'], 'button_icon' => $attributes['group_button_icon']))
				."</div>"
			."</div>";
		}

		return $out;
	}

	function get_label_types_for_select()
	{
		return array(
			'label' => __("Label", 'lang_group'),
			'placeholder' => __("Placeholder", 'lang_group'),
		);
	}

	function init()
	{
		// Post Types
		#######################
		$labels = array(
			'name' => _x(__("Group", 'lang_group'), 'post type general name'),
			'singular_name' => _x(__("Group", 'lang_group'), 'post type singular name'),
			'menu_name' => __("Group", 'lang_group')
		);

		$args = array(
			'labels' => $labels,
			'public' => true,
			'show_in_menu' => false,
			'exclude_from_search' => true,
			'rewrite' => array(
				'slug' => __("group", 'lang_group'),
			),
		);

		register_post_type($this->post_type, $args);
		#######################

		// Blocks
		#######################
		global $obj_base;

		if(!isset($obj_base))
		{
			$obj_base = new mf_base();
		}

		$plugin_include_url = plugin_dir_url(__FILE__);
		$plugin_version = get_plugin_version(__FILE__);

		wp_register_script('script_group_block_wp', $plugin_include_url."block/script_wp.js", array('wp-blocks', 'wp-i18n', 'wp-element', 'wp-components', 'wp-editor'), $plugin_version);

		$arr_data = array();
		get_post_children(array('add_choose_here' => true, 'post_type' => $this->post_type, 'post_status' => '', 'where' => "post_status != 'trash'".(IS_EDITOR ? "" : " AND post_author = '".get_current_user_id()."'")), $arr_data);

		wp_localize_script('script_group_block_wp', 'script_group_block_wp', array('group_id' => $arr_data, 'group_label_type' => $this->get_label_types_for_select(), 'group_display_consent' => get_yes_no_for_select(), 'group_button_icon' => $obj_base->get_icons_for_select()));

		register_block_type('mf/group', array(
			'editor_script' => 'script_group_block_wp',
			'editor_style' => 'style_base_block_wp',
			'render_callback' => array($this, 'block_render_callback'),
			//'style' => 'style_base_block_wp',
		));
		#######################
	}

	function settings_group()
	{
		$options_area = __FUNCTION__;

		add_settings_section($options_area, "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

		$arr_settings = array(
			'setting_emails_per_hour' => __("Outgoing e-mails per hour", 'lang_group'),
			'setting_group_see_other_roles' => __("See groups created by other roles", 'lang_group'),
			'setting_group_trace_links' => __("Trace links", 'lang_group'),
			'setting_group_outgoing_text' => __("Outgoing Text", 'lang_group'),
		);

		if(count($this->get_for_select()) > 0)
		{
			$arr_settings['setting_group_import'] = __("Add all imported to this group", 'lang_group');
		}

		$arr_settings['setting_group_debug'] = __("Debug", 'lang_group');
		$arr_settings['setting_group_log_file'] = __("Log File", 'lang_group');

		show_settings_fields(array('area' => $options_area, 'object' => $this, 'settings' => $arr_settings));
	}

	function settings_group_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);

		echo settings_header($setting_key, __("Groups", 'lang_group'));
	}

	function setting_emails_per_hour_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		settings_save_site_wide($setting_key);
		$option = get_site_option($setting_key, get_option($setting_key, 200));

		echo show_textfield(array('type' => 'number', 'name' => $setting_key, 'value' => $option, 'suffix' => __("0 or empty means infinte", 'lang_group')));
	}

	function setting_group_trace_links_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key, 'yes');

		echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option, 'description' => __("This will replace links with an internal URL so that you can see which recepients have opened the messages", 'lang_group')));
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

		echo show_wp_editor(array('name' => $setting_key, 'value' => $option, 'editor_height' => 200, 'description' => __("This text will be appended to all outgoing e-mails", 'lang_group')));
	}

	function setting_group_import_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		echo show_select(array('data' => $this->get_for_select(array('return_to_metabox' => false)), 'name' => $setting_key, 'value' => $option, 'suffix' => "<a href='".admin_url("admin.php?page=mf_group/create/index.php")."'><i class='fa fa-plus-circle fa-lg'></i></a>"));
	}

	function setting_group_debug_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key, 'no');

		list($option, $description) = setting_time_limit(array('key' => $setting_key, 'value' => $option, 'return' => 'array'));

		echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option, 'description' => $description));
	}

	function setting_group_log_file_callback()
	{
		global $wpdb;

		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		$description = "";

		if($option != '')
		{
			if(file_exists($option))
			{
				if(!is_readable($option))
				{
					$description .= "<em><i class='fa fa-exclamation-triangle yellow'></i> ".__("The file is not readable", 'lang_group')."</em>";
				}
			}

			else
			{
				$description .= "<em><i class='fa fa-times red'></i> ".__("The file does not exist", 'lang_group')."</em>";
			}
		}

		else
		{
			$description .= "<em>mail.log = ".ABSPATH."wp-content/mail.log</em>";
		}

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => ABSPATH."wp-content/mail.log", 'description' => $description));
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
		global $pagenow;

		$plugin_include_url = plugin_dir_url(__FILE__);
		$plugin_version = get_plugin_version(__FILE__);

		if($pagenow == 'admin.php')
		{
			switch(check_var('page'))
			{
				case "mf_group/create/index.php":
					mf_enqueue_script('script_group_wp', $plugin_include_url."script_wp.js", $plugin_version);
				break;

				case "mf_group/version/index.php":
					mf_enqueue_style('style_group_timeline', $plugin_include_url."style_timeline.css", $plugin_version);
				break;
			}
		}

		if(function_exists('wp_add_privacy_policy_content'))
		{
			if($this->has_allow_registration_post())
			{
				$content = __("We collect personal information when a subscription is begun by entering at least an e-mail address. This makes it possible for us to send the wanted e-mails to the correct recipient.", 'lang_group');

				wp_add_privacy_policy_content(__("Group", 'lang_group'), $content);
			}
		}
	}

	function admin_menu()
	{
		$menu_root = 'mf_group/';
		$menu_start = $menu_root.'list/index.php';
		$menu_capability = override_capability(array('page' => $menu_start, 'default' => 'edit_posts'));

		$menu_title = __("Groups", 'lang_group');
		add_menu_page("", $menu_title.$this->count_unsent_group(), $menu_capability, $menu_start, '', 'dashicons-groups', 99);

		$menu_title = __("List", 'lang_group');
		add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, $menu_start);

		$menu_title = __("Add New", 'lang_group');
		add_submenu_page($menu_start, $menu_title, " - ".$menu_title, $menu_capability, $menu_root."create/index.php");

		$arr_data_temp = $this->get_for_select(array('add_choose_here' => false));

		if(count($arr_data_temp) > 0)
		{
			$menu_title = __("Send Message", 'lang_group');
			add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, $menu_root."send/index.php");

			$menu_title = __("Sent", 'lang_group');
			add_submenu_page($menu_root, $menu_title, $menu_title, $menu_capability, $menu_root."sent/index.php");

			$menu_title = __("Message", 'lang_group');
			add_submenu_page($menu_root, $menu_title, $menu_title, $menu_capability, $menu_root."message/index.php");

			$menu_title = __("Import", 'lang_group');
			add_submenu_page($menu_root, $menu_title, $menu_title, $menu_capability, $menu_root."import/index.php");

			$menu_title = __("History", 'lang_group');
			add_submenu_page($menu_root, $menu_title, $menu_title, $menu_capability, $menu_root.'version/index.php');
		}

		/*$menu_title = __("Help", 'lang_group');
		add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, $menu_root.'help/index.php');*/

		$menu_title = __("Settings", 'lang_group');
		add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, admin_url("options-general.php?page=settings_mf_base#settings_group"));
	}

	function filter_sites_table_pages($arr_pages)
	{
		$arr_pages[$this->post_type] = array(
			'icon' => "fas fa-users",
			'title' => __("Groups", 'lang_group'),
		);

		return $arr_pages;
	}

	function has_allow_registration_post()
	{
		global $wpdb;

		$post_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND ".$wpdb->postmeta.".meta_key = '".$this->meta_prefix."allow_registration' WHERE post_type = %s AND meta_value = %s", $this->post_type, 'yes'));

		return ($post_id > 0);
	}

	function admin_notices()
	{
		global $wpdb, $error_text;

		if(IS_ADMINISTRATOR && does_table_exist($wpdb->prefix."group_message"))
		{
			$result = $wpdb->get_results("SELECT messageType, addressID, addressFirstName, addressSurName, addressEmail, addressCellNo FROM ".$wpdb->prefix."group_message INNER JOIN ".$wpdb->prefix."group_queue USING (messageID) INNER JOIN ".$wpdb->prefix."address USING (addressID) WHERE queueSent = '0' AND queueCreated < DATE_SUB(NOW(), INTERVAL 3 HOUR) LIMIT 0, 6");
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

				$error_text = __("There were unsent messages", 'lang_group')." (".$unsent_links.($rows == 6 ? "&hellip;" : "").")";

				echo get_notification();
			}
		}
	}

	function wp_trash_post($post_id)
	{
		global $wpdb;

		if(get_post_type($post_id) == $this->post_type)
		{
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

	function add_version($data)
	{
		global $wpdb;

		if(!isset($data['group_id'])){		$data['group_id'] = 0;}
		if(!isset($data['address_id'])){	$data['address_id'] = 0;}

		$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."group_version SET versionType = %s, groupID = %s, addressID = %s, versionCreated = NOW(), userID = '%d'", $data['type'], $data['group_id'], $data['address_id'], get_current_user_id()));
	}

	function merge_address($id_prev, $id)
	{
		global $wpdb;

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."group_queue SET addressID = '%d' WHERE addressID = '%d'", $id, $id_prev));
		//$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."address2group SET addressID = '%d' WHERE addressID = '%d'", $id, $id_prev));

		$result = $wpdb->get_results($wpdb->prepare("SELECT groupID FROM ".$wpdb->prefix."address2group WHERE addressID = '%d'", $id_prev));

		foreach($result as $r)
		{
			$intGroupID = $r->groupID;

			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."address2group SET addressID = '%d' WHERE addressID = '%d' AND groupID = '%d'", $id, $id_prev, $intGroupID));

			if($wpdb->rows_affected > 0)
			{
				$this->add_version(array('type' => 'merge', 'address_id' => $id, 'group_id' => $intGroupID));
			}
		}
	}

	function get_groups_to_send_to($arr_data)
	{
		$arr_data_temp = $this->get_for_select(array('add_choose_here' => false));

		if(count($arr_data_temp) > 0)
		{
			$arr_data = array_merge($arr_data, $arr_data_temp);
		}

		return $arr_data;
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

		$result = $wpdb->get_results($wpdb->prepare("SELECT addressID FROM ".$wpdb->prefix."address INNER JOIN ".$wpdb->prefix."address2group USING (addressID) WHERE addressDeleted = '0' AND groupAccepted = '%d' AND groupUnsubscribed = '%d' AND groupID IN ('".implode("','", $data['group_ids'])."')".$query_where." GROUP BY addressID", 1, 0));

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
		$arr_data = $this->get_for_select(array('return_to_metabox' => false));

		if(count($arr_data) > 1)
		{
			$out .= "<h3>".__("Choose a Group", 'lang_group')."</h3>"
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

		$result = $wpdb->get_results($wpdb->prepare("SELECT groupID, messageID FROM ".$wpdb->prefix."group_message WHERE messageDeleted = '0' AND (messageText LIKE %s OR messageText LIKE %s OR messageAttachment LIKE %s OR messageAttachment LIKE %s)", "%".$arr_used['file_url']."%", "%".$arr_used['file_thumb_url']."%", "%".$arr_used['file_url']."%", "%".$arr_used['file_thumb_url']."%"));
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

				// Remove empty space between HTML tags and then add <br> on linebreak
				$this->message_text = preg_replace('/\>\s+\</m', '><', $this->message_text);
				$this->message_text = nl2br($this->message_text);

				if($this->group_id > 0 && !in_array($this->group_id, $this->arr_group_id) && !isset($_POST['btnGroupSend']))
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

				$this->group_type = check_var('strGroupType');
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

			case 'message':
				$this->queue_id = check_var('intQueueID');
			break;

			case 'version':
				$this->group_month = check_var('strGroupMonth');
			break;
		}
	}

	function get_add_view_in_browser_code()
	{
		return "<p class='view_in_browser_link' style='text-align: right'><a href='[view_in_browser_link]' style='color: #999; font-size: .8em; text-decoration: none'>".sprintf(__("View %s in browser", 'lang_group'), "[message_name]")."</a></p>";
	}

	function get_unsubscribe_code()
	{
		return "<p><a href='[unsubscribe_link]'>".__("If you do not want to get these messages in the future click this link to unsubscribe", 'lang_group')."</a></p>";
	}

	function get_stop_list_recipients()
	{
		global $wpdb;

		if(count($this->arr_stop_list_recipients) == 0)
		{
			$this->arr_stop_list_groups = array();

			$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = %s AND post_status = %s AND ".$wpdb->postmeta.".meta_key = %s AND ".$wpdb->postmeta.".meta_value = %s", $this->post_type, 'publish', $this->meta_prefix.'group_type', 'stop'));

			foreach($result as $r)
			{
				$this->arr_stop_list_groups[] = $r->ID;

				$result_address = $wpdb->get_results($wpdb->prepare("SELECT addressID FROM ".$wpdb->prefix."address2group WHERE groupID = '%d'", $r->ID));

				foreach($result_address as $r)
				{
					$this->arr_stop_list_recipients[] = $r->addressID;
				}
			}
		}

		return $this->arr_stop_list_recipients;
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

						$done_text = __("The information was deleted", 'lang_group');
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
						$done_text = __("The group was activated", 'lang_group');
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
						$done_text = __("The group was inactivated", 'lang_group');
					}
				}

				else if(isset($_GET['btnGroupResend']) && $this->id > 0 && wp_verify_nonce($_REQUEST['_wpnonce_group_resend'], 'group_resend_'.$this->id))
				{
					$result = $wpdb->get_results($wpdb->prepare("SELECT addressID FROM ".$wpdb->prefix."address INNER JOIN ".$wpdb->prefix."address2group USING (addressID) WHERE addressDeleted = '0' AND groupID = '%d' AND groupAccepted = '0' AND groupUnsubscribed = '0' AND (groupAcceptanceSent IS null OR groupAcceptanceSent <= '%s') ORDER BY groupAcceptanceSent ASC".$this->get_message_query_limit(), $this->id, date("Y-m-d H:i:s", strtotime("-1 week"))));

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
							sleep(1);
							set_time_limit(60);
						}
					}

					if($fail > 0)
					{
						$error_text = sprintf(__("%d messages were successful and %d failed", 'lang_group'), $success, $fail);
					}

					else
					{
						$done_text = sprintf(__("%d messages were sent", 'lang_group'), $success);
					}
				}

				else if(isset($_GET['sent']))
				{
					$done_text = __("The information was sent", 'lang_group');
				}

				else if(isset($_GET['created']))
				{
					$done_text = __("The group was created", 'lang_group');
				}

				else if(isset($_GET['updated']))
				{
					$done_text = __("The group was updated", 'lang_group');
				}

				$obj_export = new mf_group_export();
			break;

			case 'form':
				if(isset($_POST['btnGroupSend']) && count($this->arr_group_id) > 0 && wp_verify_nonce($_POST['_wpnonce_group_send'], 'group_send_'.$this->message_type))
				{
					if($this->message_text == '')
					{
						$error_text = __("You have to enter a text to send", 'lang_group');
					}

					else if($this->message_type == 'email' || $this->message_type == 'sms')
					{
						$attachments_size = 0;
						$attachments_size_limit = (5 * MB_IN_BYTES);

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
							$error_text = sprintf(__("You are trying to send attachments of a total of %s. I suggest that you send the attachments as inline links instead of attachments. This way I do not have to send too much data which might slow down the server or make it timeout due to memory limits and it also makes the recipients not have to recieve that much in their inboxes.", 'lang_group'), show_final_size($attachments_size));
						}

						else
						{
							$query_where = "";

							if(!IS_EDITOR)
							{
								$query_where .= " AND post_author = '".get_current_user_id()."'";
							}

							$arr_recipients = array();
							$arr_stop_list_recipients = $this->get_stop_list_recipients();

							foreach($this->arr_group_id as $this->group_id)
							{
								$wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND ID = '%d'".$query_where." LIMIT 0, 1", $this->post_type, $this->group_id));
								$rows = $wpdb->num_rows;

								if($rows > 0)
								{
									$dteMessageSchedule = ($this->message_schedule_date != '' && $this->message_schedule_time != '' ? $this->message_schedule_date." ".$this->message_schedule_time : '');

									$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."group_message SET groupID = '%d', messageType = %s, messageFrom = %s, messageName = %s, messageText = %s, messageAttachment = %s, messageSchedule = %s, messageCreated = NOW(), userID = '%d'", $this->group_id, $this->message_type, $this->message_from, $this->message_name, $this->message_text, $this->message_attachment, $dteMessageSchedule, get_current_user_id()));

									$this->message_id = $wpdb->insert_id;

									if($this->message_id > 0)
									{
										$result = $wpdb->get_results($wpdb->prepare("SELECT addressID, addressEmail, addressCellNo FROM ".$wpdb->prefix."address INNER JOIN ".$wpdb->prefix."address2group USING (addressID) WHERE groupID = '%d' AND addressDeleted = '0' AND groupAccepted = '1' AND groupUnsubscribed = '0'", $this->group_id));

										foreach($result as $r)
										{
											$intAddressID = $r->addressID;
											$strAddressEmail = $r->addressEmail;
											$strAddressCellNo = $r->addressCellNo;

											if(!in_array($intAddressID, $arr_recipients) && !in_array($intAddressID, $arr_stop_list_recipients) && ($this->message_type == "email" && $strAddressEmail != "" || $this->message_type == "sms" && $strAddressCellNo != ""))
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
										$error_text = __("There was an error when saving the message", 'lang_group');
									}
								}
							}

							if(count($arr_recipients) > 0)
							{
								mf_redirect(admin_url("admin.php?page=mf_group/list/index.php&sent"));
							}

							else
							{
								$error_text = __("The message was not sent to anybody", 'lang_group');
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
					$this->message_text = $wpdb->get_var($wpdb->prepare("SELECT post_content FROM ".$wpdb->posts." WHERE post_type = %s AND post_status = %s AND ID = '%d'", 'page', 'publish', $this->message_text_source));

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
						$this->message_name = stripslashes($r->messageName);
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
							$this->meta_prefix.'group_type' => $this->group_type,
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
							$error_text = __("The information was not submitted, contact an admin if this persists", 'lang_group');
						}
					}

					else
					{
						$this->id = wp_insert_post($post_data);

						if($this->id > 0)
						{
							if($this->id_copy > 0)
							{
								$result = $wpdb->get_results($wpdb->prepare("SELECT addressID FROM ".$wpdb->prefix."address INNER JOIN ".$wpdb->prefix."address2group USING (addressID) WHERE groupID = '%d' AND addressDeleted = '0'", $this->id_copy));

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
							$error_text = __("The information was not submitted, contact an admin if this persists", 'lang_group');
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
							$done_text = __("I removed all the recipients from this group", 'lang_group');
						}

						else
						{
							$error_text = __("I could not remove the recipients from this group", 'lang_group');
						}
					}
				}
			break;

			case 'sent':
				if(isset($_REQUEST['btnMessageDelete']) && $this->message_id > 0 && wp_verify_nonce($_REQUEST['_wpnonce_message_delete'], 'message_delete_'.$this->message_id))
				{
					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."group_message SET messageDeleted = '1', messageDeletedDate = NOW(), messageDeletedID = '%d' WHERE messageID = '%d'", get_current_user_id(), $this->message_id));

					$done_text = __("The message was deleted", 'lang_group');
				}

				if(isset($_REQUEST['btnMessageAbort']) && $this->message_id > 0 && wp_verify_nonce($_REQUEST['_wpnonce_message_abort'], 'message_abort_'.$this->message_id))
				{
					$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."group_queue WHERE messageID = '%d' AND queueSent = '0'", $this->message_id));

					$done_text = __("The message was aborted", 'lang_group');
				}
			break;

			case 'message':

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

						$this->group_type = get_post_meta($this->id, $this->meta_prefix.'group_type', true);
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
							$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->posts." SET post_status = %s WHERE ID = '%d' AND post_type = %s".$this->query_where, 'publish', $this->id, $this->post_type));
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
			$intAddressID = $wpdb->get_var("SELECT addressID FROM ".$wpdb->prefix."address WHERE addressDeleted = '0' AND ".$query_where);

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

	function get_acceptance_sent($data)
	{
		global $wpdb;

		return $wpdb->get_var($wpdb->prepare("SELECT groupAcceptanceSent FROM ".$wpdb->prefix."address2group WHERE addressID = '%d' AND groupID = '%d'", $data['address_id'], $data['group_id']));
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

		$strGroupAcceptanceSubject = get_post_meta_or_default($data['group_id'], $meta_key_subject, true, __("Accept subscription to %s", 'lang_group'));
		$strGroupAcceptanceText = get_post_meta_or_default($data['group_id'], $meta_key_text, true, __("You have been added to the group %s but will not get any messages until you have accepted this subscription by clicking the link below.", 'lang_group'));

		if(!isset($obj_address))
		{
			$obj_address = new mf_address();
		}

		$strAddressEmail = $obj_address->get_address($data['address_id']);
		$strGroupName = $this->get_name(array('id' => $data['group_id']));

		$mail_to = $strAddressEmail;
		$mail_subject = sprintf($strGroupAcceptanceSubject, $strGroupName);
		$mail_content = apply_filters('the_content', sprintf($strGroupAcceptanceText, $strGroupName));

		$mail_content .= "<p><a href='".$this->get_group_url(array('type' => 'subscribe', 'group_id' => $data['group_id'], 'email' => $strAddressEmail))."'>".__("Accept Link", 'lang_group')."</a></p>";

		$sent = send_email(array('to' => $mail_to, 'subject' => $mail_subject, 'content' => $mail_content));

		if($sent)
		{
			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."address2group SET groupAcceptanceSent = NOW() WHERE addressID = '%d' AND groupID = '%d'", $data['address_id'], $data['group_id']));

			if($wpdb->rows_affected > 0)
			{
				$data['type'] = 'acceptance_sent';

				$this->add_version($data);
			}
		}

		return $sent;
	}

	function accept_address($data)
	{
		global $wpdb;

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."address2group SET groupAccepted = '1' WHERE addressID = '%d' AND groupID = '%d'", $data['address_id'], $data['group_id']));

		if($wpdb->rows_affected > 0)
		{
			$data['type'] = 'accept';

			$this->add_version($data);

			return true;
		}

		else
		{
			return false;
		}
	}

	function has_address($data)
	{
		global $wpdb;

		$wpdb->get_results($wpdb->prepare("SELECT addressID FROM ".$wpdb->prefix."address2group WHERE addressID = '%d' AND groupID = '%d' LIMIT 0, 1", $data['address_id'], $data['group_id']));

		return ($wpdb->num_rows > 0);
	}

	function add_address($data)
	{
		global $wpdb;

		if($data['address_id'] > 0 && $data['group_id'] > 0 && $this->has_address($data) == false)
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

			$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."address2group SET addressID = '%d', groupID = '%d', addressAdded = NOW(), groupAccepted = '%d'", $data['address_id'], $data['group_id'], ($post_meta_acceptance_email == 'yes' ? 0 : 1)));

			if($wpdb->rows_affected > 0)
			{
				$data['type'] = 'add';

				$this->add_version($data);
			}

			if($post_meta_acceptance_email == 'yes')
			{
				$this->send_acceptance_message($data);
			}

			//$from_email = get_bloginfo('admin_email');
			$from_email = $wpdb->get_var($wpdb->prepare("SELECT messageFrom FROM ".$wpdb->prefix."group_message WHERE groupID = '%d' AND messageDeleted = '0' ORDER BY messageCreated DESC LIMIT 0, 1", $data['group_id']));

			do_action('group_after_add_address', array('address_id' => $data['address_id'], 'from' => $from_email));
		}
	}

	function remove_address($data)
	{
		global $wpdb;

		if(!isset($data['group_id'])){		$data['group_id'] = 0;}

		$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."address2group WHERE addressID = '%d'".($data['group_id'] > 0 ? " AND groupID = '".$data['group_id']."'" : ""), $data['address_id']));

		if($wpdb->rows_affected > 0)
		{
			$data['type'] = 'remove';

			$this->add_version($data);

			return true;
		}

		else
		{
			return false;
		}
	}

	function remove_all_address($group_id)
	{
		global $wpdb;

		$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."address2group WHERE groupID = '%d'", $group_id));

		if($wpdb->rows_affected > 0)
		{
			$this->add_version(array('type' => 'remove_all', 'group_id' => $group_id));

			return true;
		}

		else
		{
			return false;
		}
	}

	function unsubscribe_address($data)
	{
		global $wpdb;

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."address2group SET groupUnsubscribed = '1' WHERE groupID = '%d' AND addressID = '%d'", $data['group_id'], $data['address_id']));

		if($wpdb->rows_affected > 0)
		{
			$data['type'] = 'unsubscribe';

			$this->add_version($data);

			return true;
		}

		else
		{
			return false;
		}
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

			/* Display filtered number of addresses after Stop list has been accounted for */
			$arr_stop_list_recipients = $this->get_stop_list_recipients();

			if(!in_array($data['id'], $this->arr_stop_list_groups) && count($arr_stop_list_recipients) > 0)
			{
				$query_where .= " AND addressID NOT IN('".implode("','", $arr_stop_list_recipients)."')";
			}

			return $wpdb->get_var($wpdb->prepare("SELECT COUNT(addressID) FROM ".$wpdb->prefix."address INNER JOIN ".$wpdb->prefix."address2group USING (addressID) WHERE addressDeleted = '%d' AND groupAccepted = '%d' AND groupUnsubscribed = '%d'".$query_where, $data['deleted'], $data['accepted'], $data['unsubscribed']));
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
			global $obj_group;

			if(!isset($obj_group))
			{
				$obj_group = new mf_group();
			}

			$this->post_type = $obj_group->post_type;

			$this->arr_settings['query_select_id'] = "ID";
			$this->orderby_default = "post_title";

			$this->arr_settings['has_autocomplete'] = true;
			$this->arr_settings['plugin_name'] = $this->post_type;
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
				$this->query_where .= ($this->query_where != '' ? " AND " : "")."(post_title LIKE '".$this->filter_search_before_like($this->search)."' OR SOUNDEX(post_title) = SOUNDEX('".$this->search."'))";
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
					'all' => __("All", 'lang_group'),
					'publish' => __("Public", 'lang_group'),
					'draft' => __("Not Public", 'lang_group'),
					'ignore' => __("Inactive", 'lang_group'),
					'trash' => __("Trash", 'lang_group')
				),
			));

			$rowsAddressesNotAccepted = $obj_group->amount_in_group(array('id' => 0, 'accepted' => 0));

			$arr_columns = array(
				'cb' => '<input type="checkbox">',
				'post_title' => __("Name", 'lang_group'),
				'post_status' => "",
				'amount' => __("Amount", 'lang_group'),
				'versioning' => __("History", 'lang_group'),
			);

			if($rowsAddressesNotAccepted > 0)
			{
				$arr_columns['not_accepted'] = __("Not Accepted", 'lang_group');
			}

			$arr_columns['unsubscribed'] = __("Unsubscribed", 'lang_group');
			$arr_columns['post_author'] = shorten_text(array('string' => __("User", 'lang_group'), 'limit' => 4));
			$arr_columns['sent'] = __("Sent", 'lang_group');

			$this->set_columns($arr_columns);

			$this->set_sortable_columns(array(
				'post_title',
				//'amount',
				'post_author',
			));
		}

		function column_default($item, $column_name)
		{
			global $wpdb, $obj_group;

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

						$actions['edit'] = "<a href='".$post_edit_url."'>".__("Edit", 'lang_group')."</a>";

						if($post_author == get_current_user_id() || IS_ADMINISTRATOR)
						{
							$actions['delete'] = "<a href='".wp_nonce_url($list_url."&btnGroupDelete", 'group_delete_'.$post_id, '_wpnonce_group_delete')."'>".__("Delete", 'lang_group')."</a>";
						}

						if($post_status == 'ignore')
						{
							$actions['activate'] = "<a href='".wp_nonce_url($list_url."&btnGroupActivate", 'group_activate_'.$post_id, '_wpnonce_group_activate')."'>".__("Activate", 'lang_group')."</a>";
						}

						else
						{
							$actions['inactivate'] = "<a href='".wp_nonce_url($list_url."&btnGroupInactivate", 'group_inactivate_'.$post_id, '_wpnonce_group_inactivate')."'>".__("Inactivate", 'lang_group')."</a>";
						}
					}

					else
					{
						$actions['recover'] = "<a href='".admin_url("admin.php?page=mf_group/create/index.php&intGroupID=".$post_id."&recover")."'>".__("Recover", 'lang_group')."</a>";
					}

					if(get_post_meta($post_id, $obj_group->meta_prefix.'group_type', true) == 'stop')
					{
						$out .= "<i class='fa fa-exclamation-triangle yellow' title='".__("This will prevent messages to all recipients in this group regardless which group that you are sending to.", 'lang_group')."'></i> ";

						$out .= "<i class='set_tr_color' rel='red'></i>";
					}

					$out .= "<a href='".$post_edit_url."'>".$item['post_title']."</a>"
					.$this->row_actions($actions);
				break;

				case 'post_status':
					$arr_statuses = array(
						'allow_registration' => array(
							'type' => 'bool',
							'name' => __("Allow Registration", 'lang_group'),
							'icon' => 'fa fa-globe',
							'single' => true,
							'link' => get_permalink($post_id),
						),
						'api' => array(
							'type' => 'empty',
							'name' => __("API Link", 'lang_group'),
							'icon' => 'fas fa-network-wired',
							'single' => true,
						),
						'api_filter' => array(
							'type' => 'empty',
							'name' => __("Filter API", 'lang_group'),
							'icon' => 'fas fa-plus',
							'single' => true,
						),
						'sync_users' => array(
							'type' => 'bool',
							'name' => __("Synchronize", 'lang_group'),
							'icon' => 'fas fa-users',
							'single' => true,
						),
						'verify_link' => array(
							'type' => 'bool',
							'name' => __("Add Verify Link", 'lang_group'),
							'icon' => 'fas fa-link',
							'single' => true,
						),
						'acceptance_email' => array(
							'type' => 'bool',
							'name' => __("Send before adding to a group", 'lang_group'),
							'icon' => 'fa fa-paper-plane',
							'single' => true,
						),
						'owner_email' => array(
							'type' => 'empty',
							'name' => __("Owner", 'lang_group'),
							'icon' => 'fas fa-user-tie',
							'single' => true,
						),
						'help_page' => array(
							'type' => 'empty',
							'name' => __("Help Page", 'lang_group'),
							'icon' => 'far fa-question-circle',
							'single' => true,
						),
						'archive_page' => array(
							'type' => 'empty',
							'name' => __("Archive Page", 'lang_group'),
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
								if($post_meta != 'no' && $post_meta != '')
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
						$group_type = get_post_meta($post_id, $obj_group->meta_prefix.'group_type', true);

						if($amount > 0 && $group_type != 'stop')
						{
							$actions['send_email'] = "<a href='".admin_url("admin.php?page=mf_group/send/index.php&intGroupID=".$post_id."&type=email")."' title='".__("Send e-mail to everyone in the group", 'lang_group')."'><i class='fa fa-paper-plane fa-lg'></i></a>";

							$actions = apply_filters('add_group_list_amount_actions', $actions, $post_id);
						}

						if($obj_group->is_synced($post_id) == false)
						{
							$actions['addnremove'] = "<a href='".admin_url("admin.php?page=mf_address/list/index.php&intGroupID=".$post_id."&strFilterIsMember&strFilterAccepted&strFilterUnsubscribed")."' title='".__("Add or remove", 'lang_group')."'><i class='fas fa-tasks fa-lg'></i></a>";

							$actions['import'] = "<a href='".admin_url("admin.php?page=mf_group/import/index.php&intGroupID=".$post_id)."' title='".__("Import Addresses", 'lang_group')."'><i class='fas fa-cloud-upload-alt fa-lg'></i></a>";
						}

						if($amount > 0)
						{
							$actions['export_csv'] = "<a href='".wp_nonce_url($list_url."&btnExportRun&intExportType=".$post_id."&strExportFormat=csv", 'export_run', '_wpnonce_export_run')."' title='".__("Export", 'lang_group')." CSV'><i class='fas fa-file-csv fa-lg'></i></a>";

							if(is_plugin_active("mf_phpexcel/index.php"))
							{
								$actions['export_xls'] = "<a href='".wp_nonce_url($list_url."&btnExportRun&intExportType=".$post_id."&strExportFormat=xls", 'export_run', '_wpnonce_export_run')."' title='".__("Export", 'lang_group')." XLS'><i class='fas fa-file-excel fa-lg'></i></a>";
							}
						}
					}

					$out .= "<a href='".admin_url("admin.php?page=mf_address/list/index.php&intGroupID=".$post_id."&strFilterIsMember=yes&strFilterAccepted=yes&strFilterUnsubscribed=no")."'>"
						.$amount;

						$amount_deleted = $obj_group->amount_in_group(array('id' => $post_id, 'deleted' => 1));

						if($amount_deleted > 0)
						{
							$out .= " <span class='grey' title='".__("Deleted", 'lang_group')."'>+ ".$amount_deleted."</span>";
						}

					$out .= "</a>"
					.$this->row_actions($actions);
				break;

				case 'versioning':
					$version_amount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(versionID) FROM ".$wpdb->prefix."group_version WHERE groupID = '%d' LIMIT 0, 21", $post_id));

					if($version_amount > 0)
					{
						$out .= "<a href='".admin_url("admin.php?page=mf_group/version/index.php&intGroupID=".$post_id)."'>".($version_amount > 20 ? "20+" : $version_amount)."</a>";
					}
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
									<i class='fa fa-recycle fa-lg' title='".sprintf(__("There are %d subscribers that can be reminded again. Do you want to do that?", 'lang_group'), $rowsAddresses2Remind)."'></i>
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
						$user_data = get_userdata(get_current_user_id());

						$user_email = $user_data->user_email;

						$out .= "<div class='row-actions'>
							<a href='".$obj_group->get_group_url(array('type' => 'unsubscribe', 'group_id' => $post_id, 'email' => $user_email))."' rel='confirm'>".__("Test", 'lang_group')."</a>
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

						$actions['sent'] = "<a href='".admin_url("admin.php?page=mf_group/sent/index.php&intGroupID=".$post_id)."'>".__("Sent", 'lang_group')."</a>";

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
									$out .= "<div><i class='fa fa-spinner fa-spin fa-lg'></i> ".sprintf(__("Will be sent %s", 'lang_group'), get_next_cron())."</div>"
									."<i class='set_tr_color' rel='yellow'></i>";
								}
							}

							else if($intMessageSent < ($intMessageSent + $intMessageNotSent))
							{
								$out .= "&nbsp;<i class='fa fa-spinner fa-spin fa-lg'></i> ".__("Is sending", 'lang_group')
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
				$this->query_where .= ($this->query_where != '' ? " AND " : "")."(messageFrom LIKE '".$this->filter_search_before_like($this->search)."' OR messageName LIKE '".$this->filter_search_before_like($this->search)."' OR messageText LIKE '".$this->filter_search_before_like($this->search)."' OR messageCreated LIKE '".$this->filter_search_before_like($this->search)."' OR SOUNDEX(messageFrom) = SOUNDEX('".$this->search."') OR SOUNDEX(messageName) = SOUNDEX('".$this->search."') OR SOUNDEX(messageText) = SOUNDEX('".$this->search."'))";
			}

			$this->set_views(array(
				'db_field' => 'messageDeleted',
				'types' => array(
					'0' => __("All", 'lang_group'),
					'1' => __("Trash", 'lang_group')
				),
			));

			$arr_columns = array(
				//'cb' => '<input type="checkbox">',
				'messageType' => __("Type", 'lang_group'),
				'messageFrom' => __("From", 'lang_group'),
				'messageName' => __("Content", 'lang_group'),
				'messageSchedule' => __("Scheduled", 'lang_group'),
				'sent' => __("Sent", 'lang_group'),
				'messageCreated' => __("Created", 'lang_group'),
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
						'view_data' => "<i class='fa fa-eye fa-lg' title='".__("View Content", 'lang_group')."'></i>",
						'send_to_group' => "<a href='".admin_url("admin.php?page=mf_group/send/index.php&intGroupID=".$this->arr_settings['intGroupID']."&intMessageID=".$intMessageID2)."'><i class='fa fa-users fa-lg' title='".__("Send to group again", 'lang_group')."'></i></a>",
						'send_email' => "<a href='".admin_url("admin.php?page=mf_email/send/index.php&intGroupMessageID=".$intMessageID2)."'><i class='fa fa-envelope fa-lg' title='".__("Send to e-mail", 'lang_group')."'></i></a>",
					);

					switch($item['messageType'])
					{
						case 'email':
							$strMessageType = __("E-mail", 'lang_group');
						break;

						default:
							$strMessageType = apply_filters('get_group_message_type', $item['messageType']);
						break;

						/*case 'sms':
							$strMessageType = __("SMS", 'lang_group');
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
						$out .= stripslashes(stripslashes($item['messageName']));
					}

					else
					{
						$out .= shorten_text(array('string' => $item['messageText'], 'limit' => 20));
					}

					$actions = array();

					if(IS_ADMINISTRATOR || $item['userID'] == get_current_user_id())
					{
						if($item['messageDeleted'] == 0)
						{
							$actions['delete'] = "<a href='".wp_nonce_url(admin_url("admin.php?page=mf_group/sent/index.php&btnMessageDelete&intGroupID=".$this->arr_settings['intGroupID']."&intMessageID=".$intMessageID2), 'message_delete_'.$intMessageID2, '_wpnonce_message_delete')."'>".__("Delete", 'lang_group')."</a>";
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

					$intMessageTotal = ($intMessageSent + $intMessageNotSent);

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
							$actions['message'] = "<a href='".admin_url("admin.php?page=mf_group/message/index.php&intMessageID=".$intMessageID2)."'>".__("View", 'lang_group')."</a>";

							$dteQueueSentTime_first = $wpdb->get_var($wpdb->prepare("SELECT MIN(queueSentTime) FROM ".$wpdb->prefix."group_queue WHERE messageID = '%d' AND queueSent = '1'", $intMessageID2));

							if($dteQueueSentTime_first > DEFAULT_DATE)
							{
								$is_same_day = ($item['messageCreated'] < date("Y-m-d H:i:s", strtotime("-7 day")) && date("Y-m-d", strtotime($item['messageCreated'])) == date("Y-m-d", strtotime($dteQueueSentTime_first)));

								if($is_same_day)
								{
									$actions['sent'] = date("G:i", strtotime($dteQueueSentTime_first));
								}

								else
								{
									$actions['sent'] = format_date($dteQueueSentTime_first);
								}

								$dteQueueSentTime_last = $wpdb->get_var($wpdb->prepare("SELECT MAX(queueSentTime) FROM ".$wpdb->prefix."group_queue WHERE messageID = '%d' AND queueSent = '1'", $intMessageID2));

								if($is_same_day)
								{
									if(date("G:i", strtotime($dteQueueSentTime_last)) != $actions['sent'])
									{
										$actions['sent'] .= " - ".date("G:i", strtotime($dteQueueSentTime_last));
									}
								}

								else if($dteQueueSentTime_last > $dteQueueSentTime_first && format_date($dteQueueSentTime_last) != format_date($dteQueueSentTime_first))
								{
									$actions['sent'] .= " - ".format_date($dteQueueSentTime_last);
								}
							}
						}

						$intMessageNotReceived = $wpdb->get_var($wpdb->prepare("SELECT COUNT(queueID) FROM ".$wpdb->prefix."group_queue WHERE messageID = '%d' AND queueStatus = 'not_received'", $intMessageID2));
						$intMessageDeferred = $wpdb->get_var($wpdb->prepare("SELECT COUNT(queueID) FROM ".$wpdb->prefix."group_queue WHERE messageID = '%d' AND queueStatus = 'deferred'", $intMessageID2));
						$intMessageViewed = $wpdb->get_var($wpdb->prepare("SELECT COUNT(queueID) FROM ".$wpdb->prefix."group_queue WHERE messageID = '%d' AND queueStatus = 'viewed'", $intMessageID2));

						if($intMessageNotReceived > 0)
						{
							$actions['not_received'] = "<i class='fa fa-times red'></i> ".mf_format_number($intMessageNotReceived / $intMessageSent * 100, 1)."% ".__("Not Received", 'lang_group');
						}

						if($intMessageDeferred > 0)
						{
							$actions['deferred'] = "<i class='fa fa-times red'></i> ".mf_format_number($intMessageDeferred / $intMessageSent * 100, 1)."% ".__("Deferred", 'lang_group');
						}

						if($intMessageViewed > 0 && get_option('setting_group_trace_links') == 'yes')
						{
							$actions['read'] = "<i class='fa fa-check green'></i> ".mf_format_number($intMessageViewed / $intMessageSent * 100, 1)."% ".__("Read", 'lang_group');
						}
					}

					if($intMessageNotSent > 0)
					{
						$actions['abort'] = "<a href='".wp_nonce_url(admin_url("admin.php?page=mf_group/sent/index.php&btnMessageAbort&intGroupID=".$this->arr_settings['intGroupID']."&intMessageID=".$intMessageID2), 'message_abort_'.$intMessageID2, '_wpnonce_message_abort')."'>".__("Abort", 'lang_group')."</a>";
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

	class mf_group_message_table extends mf_list_table
	{
		function set_default()
		{
			global $wpdb;

			$this->arr_settings['query_from'] = $wpdb->prefix."group_queue";
			$this->post_type = '';

			$this->arr_settings['query_select_id'] = "queueID";
			//$this->arr_settings['query_all_id'] = "0";
			//$this->arr_settings['query_trash_id'] = "1";
			$this->orderby_default = "queueSentTime ASC, queueID";
			$this->orderby_default_order = "ASC";

			$this->arr_settings['intMessageID'] = check_var('intMessageID');

			$this->arr_settings['page_vars'] = array('intMessageID' => $this->arr_settings['intMessageID']);

			$this->arr_settings['sent_date_temp'] = $this->arr_settings['sent_datetime_temp'] = "";
		}

		function init_fetch()
		{
			global $wpdb; //, $obj_group

			$this->query_where .= "wp_group_queue.messageID = '".esc_sql($this->arr_settings['intMessageID'])."'";

			if($this->search != '')
			{
				$this->query_where .= ($this->query_where != '' ? " AND " : "")."("
				."CONCAT(addressFirstName, ' ', addressSurName) LIKE '".$this->filter_search_before_like($this->search)."'"
				." OR addressAddress LIKE '".$this->filter_search_before_like($this->search)."'"
				." OR addressCellNo LIKE '".$this->filter_search_before_like($this->search)."'"
				." OR addressEmail LIKE '".$this->filter_search_before_like($this->search)."'"
				." OR SOUNDEX(CONCAT(addressFirstName, ' ', addressSurName)) = SOUNDEX('".$this->search."')"
			.")";
			}

			/*$this->set_views(array(
				'db_field' => 'messageDeleted',
				'types' => array(
					'0' => __("All", 'lang_group'),
					'1' => __("Trash", 'lang_group')
				),
			));*/

			$arr_columns = array(
				//'cb' => '<input type="checkbox">',
				'addressID' => __("Address", 'lang_group'),
				'queueStatus' => __("Status", 'lang_group'),
				//'queueCreated' => __("Created", 'lang_group'),
				'queueSentTime' => __("Sent", 'lang_group'),
			);

			$this->set_columns($arr_columns);

			$this->set_sortable_columns(array(
				'queueStatus',
				'queueSentTime',
			));
		}

		function column_default($item, $column_name)
		{
			global $wpdb, $obj_group;

			$out = "";

			//$intMessageID2 = $item['messageID'];

			switch($column_name)
			{
				case 'addressID':
					if($item['addressFirstName'] != '' || $item['addressSurName'] != '')
					{
						$out .= $item['addressFirstName']." ".$item['addressSurName'];
					}

					else
					{
						switch($item['messageType'])
						{
							case 'email':
								$out .= $item['addressEmail'];
							break;

							default:
								//
							break;

							case 'sms':
								$out .= $item['addressCellNo'];
							break;
						}
					}
				break;

				case 'queueStatus':
					if($item['queueSent'] == 1)
					{
						$out .= "<i class='fa fa-check green' title='".__("Sent", 'lang_group')."'></i> ";

						if($item[$column_name] != '')
						{
							switch($item[$column_name])
							{
								case 'not_received':
									$out .= "<i class='fa fa-eye-slash red' title='".__("Not Received", 'lang_group')."'></i> ";
								break;

								default:
								case 'not_viewed':
									$out .= "<i class='fa fa-eye-slash grey' title='".__("Not Viewed", 'lang_group')."'></i> ";
								break;

								case 'deferred':
									$out .= "<i class='fa fa-times red' title='".__("Deferred", 'lang_group')." (".$item['queueStatusMessage'].")'></i> ";
								break;

								case 'viewed':
									if($item['queueViewed'] > DEFAULT_DATE)
									{
										$out .= "<i class='fa fa-eye green' title='".sprintf(__("Viewed %s", 'lang_group'), format_date($item['queueViewed']))."'></i> ";
									}

									else
									{
										$out .= "<i class='fa fa-eye green' title='".__("Viewed", 'lang_group')."'></i> ";
									}
								break;
							}
						}
					}

					else
					{
						$out .= "<i class='fa fa-times red' title='".__("Not Sent", 'lang_group')."'></i> ";
					}
				break;

				/*case 'queueCreated':
					if($item[$column_name] > DEFAULT_DATE)
					{
						$out .= format_date($item[$column_name]);
					}
				break;*/

				case 'queueSentTime':
					if($item[$column_name] > DEFAULT_DATE)
					{
						if($item['queueSent'] == 1) // && $item[$column_name] > $this->arr_settings['sent_datetime_temp']
						{
							$sent_date = date("Y-m-d", strtotime($item[$column_name]));

							if($sent_date != $this->arr_settings['sent_date_temp'])
							{
								$out .= $item[$column_name];
							}

							else
							{
								$out .= date("G:i:s", strtotime($item[$column_name]));
							}

							$this->arr_settings['sent_date_temp'] = $sent_date;
							$this->arr_settings['sent_datetime_temp'] = $item[$column_name];
						}
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

			if(!isset($obj_address))
			{
				$obj_address = new mf_address();
			}

			if(!isset($obj_group))
			{
				$obj_group = new mf_group();
			}

			$this->name = $obj_group->get_name(array('id' => $this->type));

			$arr_countries = $obj_address->get_countries_for_select();

			$does_data_exist = array(
				'addressMemberID' => false,
				'addressBirthDate' => false,
				'addressFirstName' => false,
				'addressSurName' => false,
				'addressAddress' => false,
				'addressCo' => false,
				'addressZipCode' => false,
				'addressCity' => false,
				'addressCountry' => false,
				'addressTelNo' => false,
				'addressCellNo' => false,
				'addressWorkNo' => false,
				'addressEmail' => false,
				'addressExtra' => false,
			);

			$result = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."address INNER JOIN ".$wpdb->prefix."address2group USING (addressID) WHERE groupID = '%d' AND addressDeleted = '0' GROUP BY addressID ORDER BY addressPublic ASC, addressSurName ASC, addressFirstName ASC", $this->type), ARRAY_A);

			foreach($result as $r)
			{
				foreach($does_data_exist as $key => $value)
				{
					if($value == false && $r[$key] != '' && $r[$key] != '0' && $r[$key] != '-')
					{
						$does_data_exist[$key] = true;
					}
				}
			}

			foreach($result as $r)
			{
				$arr_data = array();

				foreach($does_data_exist as $key => $value)
				{
					if($value == true)
					{
						$arr_data[] = $r[$key];
					}
				}

				$this->data[] = $arr_data;
			}
		}
	}
}

class widget_group extends WP_Widget
{
	var $obj_group;
	var $widget_ops;
	var $arr_default = array(
		'group_heading' => '',
		'group_text' => '',
		'group_id' => '',
		'group_label_type' => '',
		'group_display_consent' => 'yes',
		'group_button_text' => '',
		'group_button_icon' => '',
	);

	function __construct()
	{
		$this->obj_group = new mf_group();

		$this->widget_ops = array(
			'classname' => 'widget_group',
			'description' => __("Display a group registration form", 'lang_group'),
		);

		parent::__construct('group-widget', __("Group", 'lang_group')." / ".__("Newsletter", 'lang_group'), $this->widget_ops);

	}

	function widget($args, $instance)
	{
		extract($args);
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		if($instance['group_id'] > 0)
		{
			echo apply_filters('filter_before_widget', $before_widget);

				if($instance['group_heading'] != '')
				{
					$instance['group_heading'] = apply_filters('widget_title', $instance['group_heading'], $instance, $this->id_base);

					echo $before_title
						.$instance['group_heading']
					.$after_title;
				}

				echo "<div class='section'>"
					.$this->obj_group->show_group_registration_form(array('id' => $instance['group_id'], 'text' => $instance['group_text'], 'label_type' => $instance['group_label_type'], 'display_consent' => $instance['group_display_consent'], 'button_text' => $instance['group_button_text'], 'button_icon' => $instance['group_button_icon']))
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
		$instance['group_label_type'] = sanitize_text_field($new_instance['group_label_type']);
		$instance['group_display_consent'] = sanitize_text_field($new_instance['group_display_consent']);
		$instance['group_button_text'] = sanitize_text_field($new_instance['group_button_text']);
		$instance['group_button_icon'] = sanitize_text_field($new_instance['group_button_icon']);

		return $instance;
	}

	function get_label_types_for_select()
	{
		return array(
			'label' => __("Label", 'lang_group'),
			'placeholder' => __("Placeholder", 'lang_group'),
		);
	}

	function form($instance)
	{
		global $obj_base;

		if(!isset($obj_base))
		{
			$obj_base = new mf_base();
		}

		$instance = wp_parse_args((array)$instance, $this->arr_default);

		$arr_data = array();
		get_post_children(array('add_choose_here' => true, 'post_type' => $this->obj_group->post_type, 'post_status' => '', 'where' => "post_status != 'trash'".(IS_EDITOR ? "" : " AND post_author = '".get_current_user_id()."'")), $arr_data);

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('group_heading'), 'text' => __("Heading", 'lang_group'), 'value' => $instance['group_heading'], 'xtra' => " id='".$this->widget_ops['classname']."-title'"))
			.show_textarea(array('name' => $this->get_field_name('group_text'), 'text' => __("Text", 'lang_group'), 'value' => $instance['group_text']))
			.show_select(array('data' => $arr_data, 'name' => $this->get_field_name('group_id'), 'text' => __("Group", 'lang_group'), 'value' => $instance['group_id']))
			.show_select(array('data' => $this->get_label_types_for_select(), 'name' => $this->get_field_name('group_label_type'), 'text' => __("Display Input Label as", 'lang_group'), 'value' => $instance['group_label_type']))
			.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('group_display_consent'), 'text' => __("Display Consent", 'lang_group'), 'value' => $instance['group_display_consent']))
			.show_textfield(array('name' => $this->get_field_name('group_button_text'), 'text' => __("Button Text", 'lang_group'), 'value' => $instance['group_button_text'], 'placeholder' => __("Join", 'lang_group')))
			.show_select(array('data' => $obj_base->get_icons_for_select(), 'name' => $this->get_field_name('group_button_icon'), 'text' => __("Button Icon", 'lang_group'), 'value' => $instance['group_button_icon']))
		."</div>";
	}
}