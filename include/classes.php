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

		$this->meta_prefix = "mf_group_";
	}

	function wp_head()
	{
		$plugin_include_url = plugin_dir_url(__FILE__);
		$plugin_version = get_plugin_version(__FILE__);

		mf_enqueue_style('style_group', $plugin_include_url."style.css", $plugin_version);
	}

	function fetch_request()
	{
		switch($this->type)
		{
			case 'table':
				if(isset($_SESSION['intGroupID'])){			unset($_SESSION['intGroupID']);}
				if(isset($_SESSION['is_part_of_group'])){	unset($_SESSION['is_part_of_group']);}
			break;

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
				$this->message_unsubscribe_link = check_var('strMessageUnsubscribeLink', 'char', true, 'yes');

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

				/*$this->unsubscribe_email = check_var('intGroupUnsubscribeEmail');
				$this->subscribe_email = check_var('intGroupSubscribeEmail');*/
				$this->owner_email = check_var('intGroupOwnerEmail');
				$this->help_page = check_var('intGroupHelpPage');
				$this->archive_page = check_var('intGroupArchivePage');

				$this->allow_registration = check_var('strGroupAllowRegistration', 'char', true, 'no');

				//$this->public = check_var('strGroupPublic', 'char', true, 'draft');
				$this->verify_address = check_var('strGroupVerifyAddress');
				$this->contact_page = check_var('intGroupContactPage');
				$this->registration_fields = check_var('arrGroupRegistrationFields');
				$this->verify_link = check_var('strGroupVerifyLink', 'char', true, 'no');
				$this->sync_users = check_var('strGroupSyncUsers', 'char', true, 'no');

				$this->id_copy = check_var('intGroupID_copy');
			break;
		}
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
							$error_text = sprintf(__("You are trying to send attachments of a total of %s. I suggest that you send the attachments as inline links instead of attachments. This way I don't have to send too much data which might slow down the server or make it timeout due to memory limits and it also makes the recipients not have to recieve that much in their inboxes.", 'lang_group'), show_final_size($attachments_size));
						}

						else
						{
							$query_where = "";

							if(!IS_EDITOR)
							{
								$query_where .= " AND post_author = '".get_current_user_id()."'";
							}

							$arr_recepients = array();

							foreach($this->arr_group_id as $this->group_id)
							{
								$wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = 'mf_group' AND ID = '%d'".$query_where." LIMIT 0, 1", $this->group_id));

								if($wpdb->num_rows > 0)
								{
									if($this->message_type == 'email' && $this->message_unsubscribe_link == 'yes')
									{
										$this->message_text .= "<p>&nbsp;</p><p><a href='[unsubscribe_link]'>".__("If you don't want to get these messages in the future click this link to unsubscribe", 'lang_group')."</a></p>";
									}

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

											if(!in_array($intAddressID, $arr_recepients) && ($this->message_type == "email" && $strAddressEmail != "" || $this->message_type == "sms" && $strAddressCellNo != ""))
											{
												$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."group_queue SET addressID = '%d', messageID = '%d', queueCreated = NOW()", $intAddressID, $this->message_id));

												if($wpdb->rows_affected > 0)
												{
													$arr_recepients[] = $intAddressID;
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

							if(count($arr_recepients) > 0)
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

				else if($this->message_text_source > 0)
				{
					$this->message_text = $wpdb->get_var($wpdb->prepare("SELECT post_content FROM ".$wpdb->posts." WHERE post_type = 'page' AND post_status = 'publish' AND ID = '%d'", $this->message_text_source));

					$user_data = get_userdata(get_current_user_id());

					$this->message_text = str_replace("[name]", $user_data->display_name, $this->message_text);
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
						'post_type' => 'mf_group',
						//'post_status' => $this->public,
						'post_status' => 'publish',
						'post_title' => $this->name,
					);

					if($this->id > 0)
					{
						$post_data['ID'] = $this->id;
						$post_data['post_modified'] = date("Y-m-d H:i:s");

						if(wp_update_post($post_data) > 0)
						{
							update_post_meta($this->id, 'group_acceptance_email', $this->acceptance_email);
							update_post_meta($this->id, 'group_acceptance_subject', $this->acceptance_subject);
							update_post_meta($this->id, 'group_acceptance_text', $this->acceptance_text);

							update_post_meta($this->id, $this->meta_prefix.'allow_registration', $this->allow_registration);

							update_post_meta($this->id, 'group_verify_address', $this->verify_address);
							update_post_meta($this->id, 'group_contact_page', $this->contact_page);
							update_post_meta($this->id, 'group_registration_fields', $this->registration_fields);
							update_post_meta($this->id, $this->meta_prefix.'verify_link', $this->verify_link);
							update_post_meta($this->id, $this->meta_prefix.'sync_users', $this->sync_users);

							/*update_post_meta($this->id, $this->meta_prefix.'unsubscribe_email', $this->unsubscribe_email);
							update_post_meta($this->id, $this->meta_prefix.'subscribe_email', $this->subscribe_email);*/
							update_post_meta($this->id, $this->meta_prefix.'owner_email', $this->owner_email);
							update_post_meta($this->id, $this->meta_prefix.'help_page', $this->help_page);
							update_post_meta($this->id, $this->meta_prefix.'archive_page', $this->archive_page);

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
							$error_text = __("The information was not submitted, contact an admin if this persists", 'lang_group');
						}
					}
				}

				else if(isset($_POST['btnGroupRemoveRecepients']) && $this->id > 0 && wp_verify_nonce($_POST['_wpnonce_group_remove_recepients'], 'group_remove_recepients_'.$this->id))
				{
					if(isset($_POST['intGroupRemoveRecepientsConfirm']) && $_POST['intGroupRemoveRecepientsConfirm'] == 1)
					{
						$this->remove_all_address($this->id);

						if($wpdb->rows_affected > 0)
						{
							$done_text = __("I removed all the recepients from this group", 'lang_group');
						}

						else
						{
							$error_text = __("I could not remove the recepients from this group", 'lang_group');
						}
					}
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
					$result = $wpdb->get_results($wpdb->prepare("SELECT post_status, post_title FROM ".$wpdb->posts." WHERE post_type = 'mf_group' AND ID = '%d'".$this->query_where, $this->id));

					foreach($result as $r)
					{
						$this->public = $r->post_status;
						$this->name = $r->post_title;

						$this->acceptance_email = get_post_meta_or_default($this->id, 'group_acceptance_email', true, 'no');
						$this->acceptance_subject = get_post_meta($this->id, 'group_acceptance_subject', true);
						$this->acceptance_text = get_post_meta($this->id, 'group_acceptance_text', true);

						$this->allow_registration = get_post_meta_or_default($this->id, $this->meta_prefix.'allow_registration', true, 'no');

						$this->verify_address = get_post_meta_or_default($this->id, 'group_verify_address', true, 'no');
						$this->contact_page = get_post_meta($this->id, 'group_contact_page', true);
						$this->registration_fields = get_post_meta($this->id, 'group_registration_fields', true);
						$this->verify_link = get_post_meta($this->id, $this->meta_prefix.'verify_link', true);
						$this->sync_users = get_post_meta($this->id, $this->meta_prefix.'sync_users', true);

						/*$this->unsubscribe_email = get_post_meta($this->id, $this->meta_prefix.'unsubscribe_email', true);
						$this->subscribe_email = get_post_meta($this->id, $this->meta_prefix.'subscribe_email', true);*/
						$this->owner_email = get_post_meta($this->id, $this->meta_prefix.'owner_email', true);
						$this->help_page = get_post_meta($this->id, $this->meta_prefix.'help_page', true);
						$this->archive_page = get_post_meta($this->id, $this->meta_prefix.'archive_page', true);

						if($this->public == 'trash')
						{
							$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->posts." SET post_status = 'publish' WHERE ID = '%d' AND post_type = 'mf_group'".$this->query_where, $this->id));
						}
					}
				}
			break;
		}
	}

	/*function get_post_status_for_select()
	{
		return array(
			'publish' => __("Public", 'lang_group'),
			'draft' => __("Not Public", 'lang_group'),
			'ignore' => __("Inactive", 'lang_group'),
		);
	}*/

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
			"SELECT ID, post_status, post_name, post_title, post_modified, post_author FROM ".$wpdb->posts." WHERE post_type = 'mf_group'".$data['where']." ORDER BY ".$data['order']
			.($data['limit'] != '' && $data['amount'] != '' ? " LIMIT ".$data['limit'].", ".$data['amount'] : "")
		);
	}

	function get_from_last()
	{
		global $wpdb;

		return $wpdb->get_var("SELECT messageFrom FROM ".$wpdb->prefix."group_message INNER JOIN ".$wpdb->posts." ON ".$wpdb->prefix."group_message.groupID = ".$wpdb->posts.".ID AND post_type = 'mf_group' ORDER BY messageCreated DESC LIMIT 0, 1");
	}

	function get_name($id = 0)
	{
		global $wpdb;

		if($id > 0)
		{
			$this->id = $id;
		}

		return $wpdb->get_var($wpdb->prepare("SELECT post_title FROM ".$wpdb->posts." WHERE post_type = 'mf_group' AND ID = '%d'", $this->id));
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

	function get_emails_left_to_send()
	{
		global $wpdb;

		$query_limit = "";

		$setting_emails_per_hour = get_option_or_default('setting_emails_per_hour');

		if($setting_emails_per_hour > 0)
		{
			$emails_left_to_send = $setting_emails_per_hour;

			if($emails_left_to_send > 0)
			{
				$wpdb->get_results("SELECT messageID FROM ".$wpdb->base_prefix."email_message WHERE messageFrom = '' AND messageCreated > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
				$emails_left_to_send -= $wpdb->num_rows;
			}

			if($emails_left_to_send > 0)
			{
				$wpdb->get_results("SELECT queueID FROM ".$wpdb->prefix."group_queue WHERE queueSent = '1' AND queueSentTime > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
				$emails_left_to_send -= $wpdb->num_rows;
			}

			$query_limit = " LIMIT 0, ".$emails_left_to_send;
		}

		return $query_limit;
	}

	function send_acceptance_message($data)
	{
		$strGroupAcceptanceSubject = get_post_meta_or_default($data['group_id'], 'group_acceptance_subject', true, __("Accept subscription to %s", 'lang_group'));
		$strGroupAcceptanceText = get_post_meta_or_default($data['group_id'], 'group_acceptance_text', true, __("You have been added to the group %s but will not get any messages until you have accepted this subscription by clicking the link below.", 'lang_group'));

		$obj_address = new mf_address();

		$strAddressEmail = $obj_address->get_address($data['address_id']);
		$strGroupName = $this->get_name($data['group_id']);

		$mail_to = $strAddressEmail;
		$mail_subject = sprintf($strGroupAcceptanceSubject, $strGroupName);
		$mail_content = apply_filters('the_content', sprintf($strGroupAcceptanceText, $strGroupName));

		$mail_content .= "<p><a href='".get_group_url(array('type' => 'subscribe', 'group_id' => $data['group_id'], 'email' => $strAddressEmail))."'>".__("Accept Link", 'lang_group')."</a></p>";

		return send_email(array('to' => $mail_to, 'subject' => $mail_subject, 'content' => $mail_content));
	}

	function accept_address($data)
	{
		global $wpdb;

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."address2group SET groupAccepted = '1' WHERE addressID = '%d' AND groupID = '%d'", $data['address_id'], $data['group_id']));

		return ($wpdb->rows_affected > 0);
	}

	function add_address($data)
	{
		global $wpdb;

		if($data['address_id'] > 0 && $data['group_id'] > 0)
		{
			$wpdb->get_var($wpdb->prepare("SELECT addressID FROM ".$wpdb->prefix."address2group WHERE addressID = '%d' AND groupID = '%d' LIMIT 0, 1", $data['address_id'], $data['group_id']));

			if($wpdb->num_rows == 0)
			{
				$meta_group_acceptance_email = get_post_meta($data['group_id'], 'group_acceptance_email', true);

				$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."address2group SET addressID = '%d', groupID = '%d', groupAccepted = '%d'", $data['address_id'], $data['group_id'], ($meta_group_acceptance_email == 'yes' ? 0 : 1)));

				if($meta_group_acceptance_email == 'yes')
				{
					$this->send_acceptance_message($data);
				}
			}
		}
	}

	function remove_address($address_id = 0, $group_id = 0)
	{
		global $wpdb;

		//do_log("Deleted (AID: ".$address_id.", GID: ".$group_id.")");

		$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."address2group WHERE addressID = '%d'".($group_id > 0 ? " AND groupID = '".$group_id."'" : ""), $address_id));
	}

	function remove_all_address($group_id = 0)
	{
		global $wpdb;

		/*$user_data = get_userdata(get_current_user_id());
		do_log("Deleted all (GID: ".$group_id." (".$this->get_name($group_id)."), User: ".$user_data->display_name.")");*/

		$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."address2group WHERE groupID = '%d'", $group_id));
	}

	function amount_in_group($data = array())
	{
		global $wpdb;

		if(!isset($data['id'])){				$data['id'] = $this->id;}
		if(!isset($data['accepted'])){			$data['accepted'] = 1;}
		if(!isset($data['unsubscribed'])){		$data['unsubscribed'] = 0;}

		$query_where = "";

		if($data['id'] > 0)
		{
			$query_where .= " AND groupID = '".esc_sql($data['id'])."'";
		}

		return $wpdb->get_var($wpdb->prepare("SELECT COUNT(addressID) FROM ".get_address_table_prefix()."address INNER JOIN ".$wpdb->prefix."address2group USING (addressID) WHERE addressDeleted = '0' AND groupAccepted = '%d' AND groupUnsubscribed = '%d'".$query_where, $data['accepted'], $data['unsubscribed']));
	}
}

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
		global $wpdb;

		if($this->search != '')
		{
			$this->query_where .= ($this->query_where != '' ? " AND " : "")."(post_title LIKE '%".$this->search."%')";
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

		$obj_group = new mf_group();
		$rowsAddressesNotAccepted = $obj_group->amount_in_group(array('id' => 0, 'accepted' => 0));

		$arr_columns = array(
			'cb' => '<input type="checkbox">',
			'post_title' => __("Name", 'lang_group'),
			'post_status' => "",
			'amount' => __("Amount", 'lang_group'),
		);

		if($rowsAddressesNotAccepted > 0)
		{
			$arr_columns['not_accepted'] = __("Not Accepted", 'lang_group');
		}

		$arr_columns['unsubscribed'] = __("Unsubscribed", 'lang_group');
		$arr_columns['post_author'] = shorten_text(array('text' => __("User", 'lang_group'), 'limit' => 4));
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
		global $wpdb;

		$out = "";

		$post_id = $item['ID'];
		$post_status = $item['post_status'];

		$obj_group = new mf_group(array('id' => $post_id));

		switch($column_name)
		{
			case 'post_title':
				$post_edit_url = "#";
				$post_author = $item['post_author'];

				$actions = array();

				if($post_status != "trash")
				{
					$post_edit_url = admin_url("admin.php?page=mf_group/create/index.php&intGroupID=".$post_id);

					$actions['edit'] = "<a href='".$post_edit_url."'>".__("Edit", 'lang_group')."</a>";

					if($post_author == get_current_user_id() || IS_ADMIN)
					{
						$actions['delete'] = "<a href='".wp_nonce_url(admin_url("admin.php?page=mf_group/list/index.php&btnGroupDelete&intGroupID=".$post_id), 'group_delete_'.$post_id, '_wpnonce_group_delete')."'>".__("Delete", 'lang_group')."</a>";
					}

					$actions['view'] = "<a href='".get_permalink($post_id)."'>".__("View", 'lang_group')."</a>";

					$actions['addnremove'] = "<a href='".admin_url("admin.php?page=mf_address/list/index.php&intGroupID=".$post_id)."'>".__("Add or remove", 'lang_group')."</a>";
					$actions['import'] = "<a href='".admin_url("admin.php?page=mf_group/import/index.php&intGroupID=".$post_id)."'>".__("Import", 'lang_group')."</a>";

					$amount = $obj_group->amount_in_group();

					if($amount > 0)
					{
						$actions['export_csv'] = "<a href='".wp_nonce_url(admin_url("admin.php?page=mf_group/list/index.php&btnExportRun&intExportType=".$post_id."&strExportFormat=csv"), 'export_run', '_wpnonce_export_run')."'>".__("CSV", 'lang_group')."</a>";

						if(is_plugin_active("mf_phpexcel/index.php"))
						{
							$actions['export_xls'] = "<a href='".wp_nonce_url(admin_url("admin.php?page=mf_group/list/index.php&btnExportRun&intExportType=".$post_id."&strExportFormat=xls"), 'export_run', '_wpnonce_export_run')."'>".__("XLS", 'lang_group')."</a>";
						}
					}
				}

				else
				{
					$actions['recover'] = "<a href='".admin_url("admin.php?page=mf_group/create/index.php&intGroupID=".$post_id."&recover")."'>".__("Recover", 'lang_group')."</a>";
				}

				$out .= "<a href='".$post_edit_url."'>".$item[$column_name]."</a>"
				.$this->row_actions($actions);
			break;

			case 'post_status':
				$post_allow_registration = get_post_meta($post_id, $obj_group->meta_prefix.'allow_registration', true);

				if($post_allow_registration == 'yes')
				{
					$out .= "<i class='fa fa-globe green' title='".__("This group is open for registration", 'lang_group')."'></i>";
				}

				/*if($item[$column_name] == "publish")
				{
					$post_url = get_permalink($post_id);

					$out .= "<i class='fa fa-globe green'></i>
					<div class='row-actions'>
						<a href='".$post_url."'><i class='fa fa-link'></i></a>
					</div>";
				}*/
			break;

			case 'amount':
				$amount = $obj_group->amount_in_group();

				$actions = array();

				if($post_status != "trash")
				{
					if($amount > 0)
					{
						$actions['send_email'] = "<a href='".admin_url("admin.php?page=mf_group/send/index.php&intGroupID=".$post_id."&type=email")."'><i class='fa fa-lg fa-envelope-o'></i></a>";

						if(is_plugin_active("mf_sms/index.php") && sms_is_active())
						{
							$actions['send_sms'] = "<a href='".admin_url("admin.php?page=mf_group/send/index.php&intGroupID=".$post_id."&type=sms")."'><i class='fa fa-lg fa-mobile'></i></a>";
						}
					}
				}

				$out .= "<a href='".admin_url("admin.php?page=mf_address/list/index.php&intGroupID=".$post_id."&no_ses&is_part_of_group=1")."'>".$amount."</a>"
				.$this->row_actions($actions);
			break;

			case 'not_accepted':
				$rowsAddressesNotAccepted = $obj_group->amount_in_group(array('accepted' => 0));

				$out .= $rowsAddressesNotAccepted;
			break;

			case 'unsubscribed':
				$rowsAddressesUnsubscribed = $wpdb->get_var($wpdb->prepare("SELECT COUNT(addressID) FROM ".get_address_table_prefix()."address INNER JOIN ".$wpdb->prefix."address2group USING (addressID) WHERE groupID = '%d' AND addressDeleted = '0' AND groupUnsubscribed = '1'", $post_id));

				$dteMessageCreated = $wpdb->get_var($wpdb->prepare("SELECT messageCreated FROM ".$wpdb->prefix."group_message WHERE groupID = '%d' ORDER BY messageCreated DESC", $post_id));

				$out .= $rowsAddressesUnsubscribed;

				if($post_status == 'publish' || $dteMessageCreated != '')
				{
					$current_user = wp_get_current_user();
					$user_email = $current_user->user_email;

					$out .= "<div class='row-actions'>
						<a href='".get_group_url(array('type' => 'unsubscribe', 'group_id' => $post_id, 'email' => $user_email))."' rel='confirm'>".__("Test", 'lang_group')."</a>
					</div>";
				}
			break;

			case 'post_author':
				$out .= get_user_info(array('id' => $item[$column_name], 'type' => 'short_name'));
			break;

			case 'sent':
				$result = $wpdb->get_results($wpdb->prepare("SELECT messageID, messageCreated FROM ".$wpdb->prefix."group_message WHERE groupID = '%d' ORDER BY messageCreated DESC LIMIT 0, 1", $post_id));

				foreach($result as $r)
				{
					$intMessageID = $r->messageID;
					$dteMessageCreated = $r->messageCreated;

					if($dteMessageCreated != '')
					{
						$actions['sent'] = "<a href='".admin_url("admin.php?page=mf_group/sent/index.php&intGroupID=".$post_id)."'>".__("Sent", 'lang_group')."</a>";

						$intMessageSent = $wpdb->get_var($wpdb->prepare("SELECT COUNT(queueID) FROM ".$wpdb->prefix."group_queue WHERE messageID = '%d' AND queueSent = '1'", $intMessageID));
						$intMessageNotSent = $wpdb->get_var($wpdb->prepare("SELECT COUNT(queueID) FROM ".$wpdb->prefix."group_queue WHERE messageID = '%d' AND queueSent = '0'", $intMessageID));

						if($intMessageSent == 0)
						{
							if($intMessageNotSent == 0)
							{
								$out .= format_date($dteMessageCreated)
								."<i class='set_tr_color' rel='red'></i>";
							}

							else
							{
								$out .= "<i class='fa fa-spinner fa-spin fa-lg'></i> ".sprintf(__("Will be sent %s", 'lang_group'), get_next_cron())
								."<i class='set_tr_color' rel='yellow'></i>";
							}
						}

						else if($intMessageSent < ($intMessageSent + $intMessageNotSent))
						{
							$out .= "<i class='fa fa-spinner fa-spin fa-lg'></i> ".__("Is sending", 'lang_group')
							."<i class='set_tr_color' rel='yellow'></i>";
						}

						else
						{
							$out .= format_date($dteMessageCreated);
						}

						$out .= $this->row_actions($actions);
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

class mf_group_sent_table extends mf_list_table
{
	function set_default()
	{
		global $wpdb;

		$this->arr_settings['query_from'] = $wpdb->prefix."group_message";
		$this->post_type = "";

		$this->arr_settings['query_select_id'] = "messageID";
		//$this->arr_settings['query_all_id'] = "0";
		//$this->arr_settings['query_trash_id'] = "1";
		$this->orderby_default = "messageCreated";
		$this->orderby_default_order = "DESC";

		$this->arr_settings['intGroupID'] = check_var('intGroupID');

		$this->query_where .= "groupID = '".esc_sql($this->arr_settings['intGroupID'])."'";

		if($this->search != '')
		{
			$this->query_where .= ($this->query_where != '' ? " AND " : "")."(messageFrom LIKE '%".$this->search."%' OR messageName LIKE '%".$this->search."%' OR messageText LIKE '%".$this->search."%' OR messageCreated LIKE '%".$this->search."%')";
		}

		/*$this->set_views(array(
			'db_field' => 'proposalDeleted',
			'types' => array(
				'0' => __("All", 'lang_group'),
				'1' => __("Trash", 'lang_group')
			),
		));*/

		$arr_columns = array(
			//'cb' => '<input type="checkbox">',
			'messageType' => __("Type", 'lang_group'),
			'messageFrom' => __("From", 'lang_group'),
			'messageName' => __("Subject", 'lang_group'),
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
		global $wpdb;

		$out = "";

		$intMessageID2 = $item['messageID'];

		switch($column_name)
		{
			case 'messageType':
				$actions = array(
					'view_data' => "<i class='fa fa-lg fa-eye' title='".__("View Content", 'lang_group')."'></i>",
					//'view' => "<a href='".admin_url("admin.php?page=mf_group/sent/index.php&intGroupID=".$this->arr_settings['intGroupID']."&intMessageID=".$intMessageID2."#message_".$intMessageID2)."'><i class='fa fa-lg fa-eye' title='".__("View Content", 'lang_group')."'></i></a>",
					'send_to_group' => "<a href='".admin_url("admin.php?page=mf_group/send/index.php&intGroupID=".$this->arr_settings['intGroupID']."&intMessageID=".$intMessageID2)."'><i class='fa fa-lg fa-users' title='".__("Send to group again", 'lang_group')."'></i></a>",
					'send_email' => "<a href='".admin_url("admin.php?page=mf_email/send/index.php&intGroupMessageID=".$intMessageID2)."'><i class='fa fa-lg fa-envelope-o' title='".__("Send to e-mail", 'lang_group')."'></i></a>",
				);

				switch($item[$column_name])
				{
					default:
					case 'email':
						$strMessageType = __("E-mail", 'lang_group');
					break;

					case 'sms':
						$strMessageType = __("SMS", 'lang_group');
					break;
				}

				$out .= $strMessageType
				.$this->row_actions($actions);
			break;

			case 'messageFrom':
				$strMessageFrom = $item[$column_name];

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

			case 'messageSchedule':
				if($item[$column_name] > DEFAULT_DATE)
				{
					$out .= format_date($item[$column_name]);
				}
			break;

			case 'sent':
				$intMessageSent = $wpdb->get_var($wpdb->prepare("SELECT COUNT(queueID) FROM ".$wpdb->prefix."group_queue WHERE messageID = '%d' AND queueSent = '1'", $intMessageID2));
				$intMessageNotSent = $wpdb->get_var($wpdb->prepare("SELECT COUNT(queueID) FROM ".$wpdb->prefix."group_queue WHERE messageID = '%d' AND queueSent = '0'", $intMessageID2));

				$intMessageTotal = $intMessageSent + $intMessageNotSent;

				$class = '';

				if($intMessageSent == 0)
				{
					if($intMessageNotSent == 0)
					{
						$class = "red";
					}

					else
					{
						$class = "yellow";
					}
				}

				else if($intMessageSent < $intMessageTotal)
				{
					$class = "yellow";
				}

				$actions = array();

				if($intMessageNotSent > 0)
				{
					$actions['abort'] = "<a href='".wp_nonce_url(admin_url("admin.php?page=mf_group/sent/index.php&btnMessageAbort&intGroupID=".$this->arr_settings['intGroupID']."&intMessageID=".$intMessageID2), 'message_abort_'.$intMessageID2, '_wpnonce_message_abort')."'>".__("Abort", 'lang_group')."</a>";
				}

				else
				{
					if($item['messageCreated'] > date('Y-m-d H:i:s', strtotime("-1 week")))
					{
						$dteQueueSentTime_first = $wpdb->get_var($wpdb->prepare("SELECT MIN(queueSentTime) FROM ".$wpdb->prefix."group_queue WHERE messageID = '%d' AND queueSent = '1'", $intMessageID2));

						if($dteQueueSentTime_first > DEFAULT_DATE)
						{
							$actions['sent'] = format_date($dteQueueSentTime_first);

							$dteQueueSentTime_last = $wpdb->get_var($wpdb->prepare("SELECT MAX(queueSentTime) FROM ".$wpdb->prefix."group_queue WHERE messageID = '%d' AND queueSent = '1'", $intMessageID2));

							if($dteQueueSentTime_last > $dteQueueSentTime_first && format_date($dteQueueSentTime_last) != format_date($dteQueueSentTime_first))
							{
								$actions['sent'] .= " - ".format_date($dteQueueSentTime_last);
							}
						}
					}

					$intMessageErrors = $wpdb->get_var($wpdb->prepare("SELECT COUNT(queueID) FROM ".$wpdb->prefix."group_queue WHERE messageID = '%d' AND queueReceived = '-1'", $intMessageID2));
					$intMessageReceived = $wpdb->get_var($wpdb->prepare("SELECT COUNT(queueID) FROM ".$wpdb->prefix."group_queue WHERE messageID = '%d' AND queueReceived = '1'", $intMessageID2));

					if($intMessageErrors > 0)
					{
						$actions['errors'] = mf_format_number($intMessageErrors / $intMessageSent * 100, 1)."% ".__("Errors", 'lang_group');
					}

					if($intMessageReceived > 0)
					{
						$actions['read'] = "<i class='fa fa-check green'></i> ".$intMessageReceived." ".__("Read", 'lang_group');
					}
				}

				$out .= $intMessageSent." / ".$intMessageTotal
				.$this->row_actions($actions);

				if($class != '')
				{
					$out .= "<i class='set_tr_color' rel='".$class."'></i>";
				}
			break;

			case 'messageCreated':
				$out .= format_date($item[$column_name])
				."</td></tr><tr class='hide'><td colspan='".count($this->columns)."'>";

				$out .= "<p>".stripslashes(apply_filters('the_content', $item['messageText']))."</p>";

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

class mf_group_export extends mf_export
{
	function get_defaults()
	{
		$this->plugin = "mf_group";
	}

	function get_export_data()
	{
		global $wpdb;

		$obj_group = new mf_group();
		$this->name = $obj_group->get_name($this->type);

		$obj_address = new mf_address();
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

class widget_group extends WP_Widget
{
	function __construct()
	{
		$widget_ops = array(
			'classname' => 'group',
			'description' => __("Display a group registration form", 'lang_group')
		);

		$this->arr_default = array(
			'group_heading' => '',
			'group_text' => '',
			'group_id' => '',
			'group_button_text' => '',
		);

		parent::__construct('group-widget', __("Group", 'lang_group')." / ".__("Newsletter", 'lang_group'), $widget_ops);
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
					echo $before_title
						.$instance['group_heading']
					.$after_title;
				}

				echo "<div class='section'>"
					.show_group_registration_form(array('id' => $instance['group_id'], 'text' => $instance['group_text'], 'button_text' => $instance['group_button_text']))
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
		global $wpdb;

		$instance = wp_parse_args((array)$instance, $this->arr_default);

		$arr_data = array();
		get_post_children(array('add_choose_here' => true, 'post_type' => 'mf_group', 'post_status' => '', 'where' => "post_status != 'trash'".(IS_EDITOR ? "" : " AND post_author = '".get_current_user_id()."'")), $arr_data);

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('group_heading'), 'text' => __("Heading", 'lang_group'), 'value' => $instance['group_heading'], 'xtra' => " id='group-title'"))
			.show_textarea(array('name' => $this->get_field_name('group_text'), 'text' => __("Text", 'lang_group'), 'value' => $instance['group_text']))
			.show_select(array('data' => $arr_data, 'name' => $this->get_field_name('group_id'), 'text' => __("Group", 'lang_group'), 'value' => $instance['group_id']))
			.show_textfield(array('name' => $this->get_field_name('group_button_text'), 'text' => __("Button Text", 'lang_group'), 'value' => $instance['group_button_text'], 'placeholder' => __("Join", 'lang_group')))
		."</div>";
	}
}