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

	function get_group_url($data)
	{
		global $wpdb;

		$out = "";

		$base_url = get_permalink($data['group_id']);

		$out .= $base_url
			.(preg_match("/\?/", $base_url) ? "&" : "?")
			.$data['type']."=".md5((defined('NONCE_SALT') ? NONCE_SALT : '').$data['group_id'].$data['email'])
			."&gid=".$data['group_id']
			."&aem=".$data['email'];

		if(isset($data['queue_id']) && $data['queue_id'] > 0)
		{
			$out .= "&qid=".$data['queue_id'];
		}

		return $out;
	}

	function show_group_registration_form($data)
	{
		global $wpdb, $done_text, $error_text;

		if(!isset($data['text'])){											$data['text'] = '';}
		if(!isset($data['button_text']) || $data['button_text'] == ''){		$data['button_text'] = __("Join", 'lang_group');}

		$out = "";

		//$obj_group = new mf_group();

		$arrGroupRegistrationFields = get_post_meta($data['id'], 'group_registration_fields', true);

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
			$strGroupVerifyAddress = get_post_meta($data['id'], 'group_verify_address', true);

			$query_where = $query_set = "";

			if(is_array($arrGroupRegistrationFields) && count($arrGroupRegistrationFields) > 0)
			{
				if(in_array("name", $arrGroupRegistrationFields))
				{
					list($strAddressFirstName, $strAddressSurName) = explode(" ", $strAddressName);

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

				$intGroupContactPage = get_post_meta($data['id'], 'group_contact_page', true);

				if($intGroupContactPage > 0)
				{
					$post_url = get_permalink($intGroupContactPage);
					$post_title = get_the_title($intGroupContactPage);

					if($strAddressEmail != '')
					{
						$obj_form = new mf_form();

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
					if(in_array("name", $arrGroupRegistrationFields))
					{
						$out .= show_textfield(array('name' => "strAddressName", 'text' => __("Name", 'lang_group'), 'value' => $strAddressName, 'required' => true));
					}

					if(in_array("address", $arrGroupRegistrationFields))
					{
						$out .= show_textfield(array('name' => "strAddressAddress", 'text' => __("Address", 'lang_group'), 'value' => $strAddressAddress, 'required' => true));
					}

					if(in_array("zip", $arrGroupRegistrationFields))
					{
						$out .= show_textfield(array('name' => "intAddressZipCode", 'text' => __("Zip Code", 'lang_group'), 'value' => $intAddressZipCode, 'type' => 'number', 'required' => true));
					}

					if(in_array("city", $arrGroupRegistrationFields))
					{
						$out .= show_textfield(array('name' => "strAddressCity", 'text' => __("City", 'lang_group'), 'value' => $strAddressCity, 'required' => true));
					}

					if(in_array("country", $arrGroupRegistrationFields))
					{
						$obj_address = new mf_address();

						$out .= show_select(array('data' => $obj_address->get_countries_for_select(), 'name' => "intAddressCountry", 'text' => __("Country", 'lang_group'), 'value' => $intAddressCountry, 'required' => true));
					}

					if(in_array("phone", $arrGroupRegistrationFields))
					{
						$out .= show_textfield(array('name' => "strAddressTelNo", 'text' => __("Phone Number", 'lang_group'), 'value' => $strAddressTelNo, 'required' => true));
					}

					if(in_array("email", $arrGroupRegistrationFields))
					{
						$out .= show_textfield(array('name' => "strAddressEmail", 'text' => __("E-mail", 'lang_group'), 'value' => $strAddressEmail, 'required' => true));
					}

					if(in_array("extra", $arrGroupRegistrationFields))
					{
						$out .= show_textfield(array('name' => "strAddressExtra", 'text' => get_option_or_default('setting_address_extra', __("Extra", 'lang_group')), 'value' => $strAddressExtra, 'required' => true));
					}

					$out .= "<div class='form_button'>"
						.show_checkbox(array('text' => __("I consent to having this website store my submitted information, so that they can contact me as part of this newsletter", 'lang_group'), 'value' => 1, 'required' => true))
						.show_button(array('name' => "btnGroupJoin", 'text' => $data['button_text']))
					."</div>";
				}

				else
				{
					$out .= "<div class='flex_form'>"
						.show_textfield(array('name' => "strAddressEmail", 'placeholder' => __("Your Email Address", 'lang_group'), 'value' => $strAddressEmail, 'required' => true))
						."<div class='form_button'>"
							.show_button(array('name' => "btnGroupJoin", 'text' => $data['button_text']))
						."</div>
					</div>"
					.show_checkbox(array('text' => __("I consent to having this website store my submitted information, so that they can contact me as part of this newsletter", 'lang_group'), 'value' => 1, 'required' => true));
				}

			$out .= "</form>";
		}

		return $out;
	}

	function run_cron()
	{
		global $wpdb, $error_text;

		$obj_cron = new mf_cron();
		$obj_cron->start(__FUNCTION__);

		if($obj_cron->is_running == false)
		{
			$setting_group_outgoing_text = get_option('setting_group_outgoing_text');

			/* Send group messages */
			$result = $wpdb->get_results("SELECT groupID FROM ".$wpdb->prefix."group_queue INNER JOIN ".$wpdb->prefix."group_message USING (messageID) WHERE queueSent = '0' AND (messageSchedule IS NULL OR messageSchedule < NOW()) GROUP BY groupID ORDER BY RAND()");

			foreach($result as $r)
			{
				$intGroupID = $r->groupID;

				$strGroupVerifyLink = get_post_meta($intGroupID, $this->meta_prefix.'verify_link', true);

				$intGroupUnsubscribeEmail = get_post_meta($intGroupID, $this->meta_prefix.'unsubscribe_email', true);
				$intGroupSubscribeEmail = get_post_meta($intGroupID, $this->meta_prefix.'subscribe_email', true);
				$intGroupOwnerEmail = get_post_meta($intGroupID, $this->meta_prefix.'owner_email', true);
				$intGroupHelpPage = get_post_meta($intGroupID, $this->meta_prefix.'help_page', true);
				$intGroupArchivePage = get_post_meta($intGroupID, $this->meta_prefix.'archive_page', true);

				$group_url = get_permalink($intGroupID);

				$intMessageID_temp = 0;

				$resultAddresses = $wpdb->get_results($wpdb->prepare("SELECT queueID, messageID, addressEmail, addressCellNo FROM ".get_address_table_prefix()."address INNER JOIN ".$wpdb->prefix."group_queue USING (addressID) INNER JOIN ".$wpdb->prefix."group_message USING (messageID) WHERE groupID = '%d' AND queueSent = '0' ORDER BY messageType ASC, queueCreated ASC".$this->get_emails_left_to_send(), $intGroupID));

				foreach($resultAddresses as $r)
				{
					if($obj_cron->has_expired(array('margin' => .9)))
					{
						break;
					}

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
					}

					switch($strMessageType)
					{
						case 'email':
							if($strAddressEmail != '' && is_domain_valid($strAddressEmail))
							{
								$strMessageFromName = "";

								if(preg_match("/\|/", $strMessageFrom))
								{
									list($strMessageFromName, $strMessageFrom) = explode("|", $strMessageFrom);
								}

								else
								{
									$strMessageFromName = $strMessageFrom;
								}

								$unsubscribe_url = $this->get_group_url(array('type' => "unsubscribe", 'group_id' => $intGroupID, 'email' => $strAddressEmail, 'queue_id' => $intQueueID));

								$mail_headers = "From: ".$strMessageFromName." <".$strMessageFrom.">\r\n";

								$unsubscribe_email = $subscribe_email = "";

								/*if($intGroupUnsubscribeEmail > 0)
								{
									$unsubscribe_email .= "<mailto:".$."?subject=Unsubscribe>, ";
								}*/

								$mail_headers .= "List-Unsubscribe: <".$unsubscribe_url.">\r\n";

								/*if($intGroupSubscribeEmail > 0)
								{
									$subscribe_email = "<mailto:".$."?subject=Subscribe>, ";
								}*/

								$mail_headers .= "List-Subscribe: ".$subscribe_email."<".$group_url.">\r\n";

								if($intGroupOwnerEmail > 0)
								{
									$mail_headers .= "List-Owner: <mailto:".get_email_address_from_id($intGroupOwnerEmail).">\r\n";
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
								$mail_content = stripslashes(apply_filters('the_content', $strMessageText));
								$mail_content = str_replace("[unsubscribe_link]", $unsubscribe_url, $mail_content);

								if($strGroupVerifyLink == 'yes')
								{
									$mail_content .= "<img src='".$this->get_group_url(array('type' => "verify", 'group_id' => $intGroupID, 'email' => $strAddressEmail, 'queue_id' => $intQueueID))."' style='height: 0; visibility: hidden; width: 0'>";
								}

								list($mail_attachment, $rest) = get_attachment_to_send($strMessageAttachment);

								$wpdb->get_results($wpdb->prepare("SELECT queueID FROM ".$wpdb->prefix."group_queue WHERE queueSent = '1' AND queueID = '%d'", $intQueueID));

								if($wpdb->num_rows == 0)
								{
									if($setting_group_outgoing_text != '')
									{
										$mail_content .= apply_filters('the_content', $setting_group_outgoing_text);
									}

									$sent = send_email(array('to' => $mail_to, 'subject' => $mail_subject, 'content' => $mail_content, 'headers' => $mail_headers, 'attachment' => $mail_attachment, 'save_log' => false));

									if($sent)
									{
										$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."group_queue SET queueSent = '1', queueSentTime = NOW() WHERE queueID = '%d'", $intQueueID));
									}
								}
							}

							else
							{
								$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."group_queue WHERE queueID = '%d'", $intQueueID));
							}
						break;

						case 'sms':
							//Must be here to make sure that send_sms() is loaded
							##################
							require_once(ABSPATH."wp-admin/includes/plugin.php");

							if(is_plugin_active("mf_sms/index.php"))
							{
								require_once(ABSPATH."wp-content/plugins/mf_sms/include/functions.php");
							}
							##################

							$sent = send_sms(array('from' => $strMessageFrom, 'to' => $strAddressCellNo, 'text' => $strMessageText, 'user_id' => $intUserID));

							if($sent == "OK")
							{
								$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."group_queue SET queueSent = '1', queueSentTime = NOW() WHERE queueID = '%d'", $intQueueID));

								do_log("Not sent to ".$strAddressCellNo, 'trash');
							}

							else
							{
								do_log("Not sent to ".$strAddressCellNo.", ".shorten_text(array('string' => htmlspecialchars($strMessageText), 'limit' => 10)));
							}
						break;
					}
				}
			}

			/* Add users to groups that are set to synchronize */
			$result = $wpdb->get_results("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = 'mf_group' AND meta_key = '".$this->meta_prefix."sync_users' AND meta_value = 'yes'");

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
		}

		$obj_cron->end();
	}

	function init()
	{
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

		register_post_type('mf_group', $args);
	}

	function settings_group()
	{
		$options_area = __FUNCTION__;

		add_settings_section($options_area, "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

		$arr_settings = array(
			'setting_emails_per_hour' => __("Outgoing e-mails per hour", 'lang_group'),
			'setting_group_see_other_roles' => __("See groups created by other roles", 'lang_group'),
			'setting_group_outgoing_text' => __("Outgoing Text", 'lang_group'),
			'setting_group_import' => __("Add all imported to this group", 'lang_group'),
		);

		show_settings_fields(array('area' => $options_area, 'object' => $this, 'settings' => $arr_settings));
	}

	function settings_group_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);

		echo settings_header($setting_key, __("Group", 'lang_group'));
	}

	function setting_emails_per_hour_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key, 200);

		echo show_textfield(array('type' => 'number', 'name' => $setting_key, 'value' => $option, 'suffix' => __("0 or empty means infinte", 'lang_group')));
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

		echo show_wp_editor(array('name' => $setting_key, 'value' => $option, 'description' => __("This text will be appended to all outgoing e-mails", 'lang_group')));
	}

	function setting_group_import_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		$arr_data = array(
			'' => "-- ".__("Choose Here", 'lang_group')." --"
		);

		$result = $this->get_groups(array('where' => " AND post_status != 'trash'", 'order' => "post_title ASC"));

		foreach($result as $r)
		{
			$arr_data[$r->ID] = $r->post_title;
		}

		echo show_select(array('data' => $arr_data, 'name' => $setting_key, 'value' => $option, 'suffix' => "<a href='".admin_url("admin.php?page=mf_group/create/index.php")."'><i class='fa fa-plus fa-lg'></i></a>"));
	}

	function count_unsent_group($id = 0)
	{
		global $wpdb;

		$count_message = "";

		$rows = $wpdb->get_var("SELECT COUNT(queueID) FROM ".$wpdb->prefix."group_message INNER JOIN ".$wpdb->prefix."group_queue USING (messageID) WHERE queueSent = '0'".($id > 0 ? " AND groupID = '".esc_sql($id)."'" : ""));

		if($rows > 0)
		{
			$count_message = "&nbsp;<i class='fa fa-spinner fa-spin'></i>";
		}

		return $count_message;
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
		add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, $menu_root."create/index.php");

		$menu_title = __("Send New", 'lang_group');
		add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, $menu_root."send/index.php");

		$menu_title = __("Sent", 'lang_group');
		add_submenu_page($menu_root, $menu_title, $menu_title, $menu_capability, $menu_root."sent/index.php");

		$menu_title = __("Import", 'lang_group');
		add_submenu_page($menu_root, $menu_title, $menu_title, $menu_capability, $menu_root."import/index.php");
	}

	function has_allow_registration_post()
	{
		global $wpdb;

		$post_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND ".$wpdb->postmeta.".meta_key = '".$this->meta_prefix."allow_registration' WHERE post_type = 'mf_group' AND meta_value = %s", 'yes'));

		return ($post_id > 0);
	}

	function add_policy($content)
	{
		if($this->has_allow_registration_post())
		{
			$content .= "<h3>".__("Group", 'lang_group')."</h3>
			<p>"
				.__("We collect personal information when a subscription is begun by entering at least an e-mail address. This makes it possible for us to send the wanted e-mails to the correct recipient.", 'lang_group')
			."</p>";
		}

		return $content;
	}

	function admin_notices()
	{
		global $wpdb, $error_text;

		if(IS_ADMIN)
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

				$error_text = __("There were unsent messages", 'lang_group')." (".$unsent_links.($rows == 6 ? "&hellip;" : "").")";

				echo get_notification();
			}
		}
	}

	function delete_post($post_id)
	{
		global $post_type;

		if($post_type == 'mf_group')
		{
			do_log("Delete postID (#".$post_id.") from ".$wpdb->prefix."group_message etc.");

			/*$result = $wpdb->get_results($wpdb->prepare("SELECT messageID FROM ".$wpdb->prefix."group_message WHERE groupID = '%d'", $post_id));

			foreach($result as $r)
			{
				$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."group_queue WHERE messageID = '%d'", $r->messageID));
			}

			$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."group_message WHERE groupID = '%d'", $post_id));

			//$obj_group = new mf_group();
			$this->remove_all_address($post_id);*/
		}
	}

	function deleted_user($user_id)
	{
		global $wpdb;

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."group_message SET userID = '%d' WHERE userID = '%d'", get_current_user_id(), $user_id));
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
		$tbl_group = new mf_group_table();

		$tbl_group->select_data(array(
			'select' => "ID, post_title",
		));

		if(count($tbl_group->data) > 0)
		{
			$arr_data = array(
				'' => "-- ".__("Choose Here", 'lang_group')." --",
			);

			foreach($tbl_group->data as $template)
			{
				$arr_data[$template['ID']] = $template['post_title'];
			}

			$out .= "<h3>".__("Choose a Group", 'lang_group')."</h3>"
			.show_select(array('data' => $arr_data, 'xtra' => "rel='mf_group'"));
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

	function wp_head()
	{
		$plugin_include_url = plugin_dir_url(__FILE__);
		$plugin_version = get_plugin_version(__FILE__);

		mf_enqueue_style('style_group', $plugin_include_url."style.css", $plugin_version);
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

							$arr_recipients = array();

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

		$mail_content .= "<p><a href='".$this->get_group_url(array('type' => 'subscribe', 'group_id' => $data['group_id'], 'email' => $strAddressEmail))."'>".__("Accept Link", 'lang_group')."</a></p>";

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

				if($post_status != 'trash')
				{
					$post_edit_url = admin_url("admin.php?page=mf_group/create/index.php&intGroupID=".$post_id);

					$actions['edit'] = "<a href='".$post_edit_url."'>".__("Edit", 'lang_group')."</a>";

					if($post_author == get_current_user_id() || IS_ADMIN)
					{
						$actions['delete'] = "<a href='".wp_nonce_url(admin_url("admin.php?page=mf_group/list/index.php&btnGroupDelete&intGroupID=".$post_id), 'group_delete_'.$post_id, '_wpnonce_group_delete')."'>".__("Delete", 'lang_group')."</a>";
					}

					if($post_status == 'ignore')
					{
						$actions['activate'] = "<a href='".wp_nonce_url(admin_url("admin.php?page=mf_group/list/index.php&btnGroupActivate&intGroupID=".$post_id), 'group_activate_'.$post_id, '_wpnonce_group_activate')."'>".__("Activate", 'lang_group')."</a>";
					}

					else
					{
						$actions['inactivate'] = "<a href='".wp_nonce_url(admin_url("admin.php?page=mf_group/list/index.php&btnGroupInactivate&intGroupID=".$post_id), 'group_inactivate_'.$post_id, '_wpnonce_group_inactivate')."'>".__("Inactivate", 'lang_group')."</a>";
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
						<a href='".$post_url."'><i class='fas fa-link'></i></a>
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
						$actions['send_email'] = "<a href='".admin_url("admin.php?page=mf_group/send/index.php&intGroupID=".$post_id."&type=email")."'><i class='far fa-envelope fa-lg'></i></a>";

						if(is_plugin_active("mf_sms/index.php") && sms_is_active())
						{
							$actions['send_sms'] = "<a href='".admin_url("admin.php?page=mf_group/send/index.php&intGroupID=".$post_id."&type=sms")."'><i class='fas fa-mobile-alt fa-lg'></i></a>";
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
						<a href='".$obj_group->get_group_url(array('type' => 'unsubscribe', 'group_id' => $post_id, 'email' => $user_email))."' rel='confirm'>".__("Test", 'lang_group')."</a>
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
					'view_data' => "<i class='far fa-eye fa-lg' title='".__("View Content", 'lang_group')."'></i>",
					//'view' => "<a href='".admin_url("admin.php?page=mf_group/sent/index.php&intGroupID=".$this->arr_settings['intGroupID']."&intMessageID=".$intMessageID2."#message_".$intMessageID2)."'><i class='far fa-eye fa-lg' title='".__("View Content", 'lang_group')."'></i></a>",
					'send_to_group' => "<a href='".admin_url("admin.php?page=mf_group/send/index.php&intGroupID=".$this->arr_settings['intGroupID']."&intMessageID=".$intMessageID2)."'><i class='far fa-users fa-lg' title='".__("Send to group again", 'lang_group')."'></i></a>",
					'send_email' => "<a href='".admin_url("admin.php?page=mf_email/send/index.php&intGroupMessageID=".$intMessageID2)."'><i class='far fa-envelope fa-lg' title='".__("Send to e-mail", 'lang_group')."'></i></a>",
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
			$obj_group = new mf_group();

			echo $before_widget;

				if($instance['group_heading'] != '')
				{
					echo $before_title
						.$instance['group_heading']
					.$after_title;
				}

				echo "<div class='section'>"
					.$obj_group->show_group_registration_form(array('id' => $instance['group_id'], 'text' => $instance['group_text'], 'button_text' => $instance['group_button_text']))
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