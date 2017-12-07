<?php

function show_final_size($in)
{
	$arr_suffix = array("B", "kB", "MB", "GB", "TB");

	$count_temp = count($arr_suffix);

	for($i = 0; ($in > 1024 || $i < 1) && $i < $count_temp; $i++) //Forces at least kB
	{
		$in /= 1024;
	}

	$out = strlen(round($in)) < 3 ? round($in, 1) : round($in); //If less than 3 digits, show one decimal aswell

	return $out."&nbsp;".$arr_suffix[$i];
}

function count_shortcode_button_group($count)
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

function get_shortcode_output_group($out)
{
	$tbl_group = new mf_group_table();

	$tbl_group->select_data(array(
		'select' => "ID, post_title",
	));

	if(count($tbl_group->data) > 0)
	{
		$arr_data = array(
			'' => "-- ".__("Choose here", 'lang_group')." --",
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

function get_shortcode_list_group($data)
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

function delete_group($post_id)
{
	global $post_type;

	if($post_type == 'mf_group')
	{
		do_log("Delete postID (#".$post_id.") from ".$wpdb->base_prefix."group_message etc.");

		/*$result = $wpdb->get_results($wpdb->prepare("SELECT messageID FROM ".$wpdb->base_prefix."group_message WHERE groupID = '%d'", $post_id));

		foreach($result as $r)
		{
			$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."group_queue WHERE messageID = '%d'", $r->messageID));
		}

		$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."group_message WHERE groupID = '%d'", $post_id));

		$obj_group = new mf_group();
		$obj_group->remove_all_address($post_id);*/
	}
}

function deleted_user_group($user_id)
{
	global $wpdb;

	$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."group_message SET userID = '%d' WHERE userID = '%d'", get_current_user_id(), $user_id));
}

function notices_group()
{
	global $wpdb, $error_text;

	if(IS_ADMIN)
	{
		$result = $wpdb->get_results("SELECT messageType, addressID, addressFirstName, addressSurName, addressEmail, addressCellNo FROM ".$wpdb->base_prefix."group_message INNER JOIN ".$wpdb->base_prefix."group_queue USING (messageID) INNER JOIN ".$wpdb->base_prefix."address USING (addressID) WHERE queueSent = '0' AND queueCreated < DATE_SUB(NOW(), INTERVAL 3 HOUR) LIMIT 0, 6");

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

					$strAddressName = $strAddressFirstName." ".$strAddressSurName." &lt;".($strMessageType == "email" ? $emlAddressEmail : $strAddressCellNo)."&gt;";

					$unsent_links .= ($unsent_links != '' ? ", " : "")."<a href='".admin_url("admin.php?page=mf_address/create/index.php&intAddressID=".$intAddressID)."'>".$strAddressName."</a>";

					$i++;
				}
			}

			$error_text = __("There were unsent messages", 'lang_group')." (".$unsent_links.($rows == 6 ? "&hellip;" : "").")";

			echo get_notification();
		}
	}
}

function init_group()
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

	if(!is_admin())
	{
		$plugin_include_url = plugin_dir_url(__FILE__);
		$plugin_version = get_plugin_version(__FILE__);

		mf_enqueue_style('style_group', $plugin_include_url."style.css", $plugin_version);
	}
}

function widgets_group()
{
	register_widget('widget_group');
}

function settings_group()
{
	$options_area = __FUNCTION__;

	add_settings_section($options_area, "", $options_area."_callback", BASE_OPTIONS_PAGE);

	$arr_settings = array(
		'setting_emails_per_hour' => __("Outgoing e-mails per hour", 'lang_group'),
		'setting_group_see_other_roles' => __("See groups created by other roles", 'lang_group'),
		'setting_group_import' => __("Add all imported to this group", 'lang_group'),
	);

	show_settings_fields(array('area' => $options_area, 'settings' => $arr_settings));
}

function settings_group_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);

	echo settings_header($setting_key, __("Group", 'lang_group'));
}

function setting_emails_per_hour_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'type' => 'number', 'suffix' => __("0 or empty means infinte", 'lang_group')));
}

function setting_group_see_other_roles_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key, 'yes');

	echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
}

function setting_group_import_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	$arr_data = array();
	$arr_data[''] = "-- ".__("Choose here", 'lang_group')." --";

	$obj_group = new mf_group();
	$result = $obj_group->get_groups(array('where' => " AND post_status != 'trash'", 'order' => "post_title ASC"));

	foreach($result as $r)
	{
		$arr_data[$r->ID] = $r->post_title;
	}

	echo show_select(array('data' => $arr_data, 'name' => $setting_key, 'value' => $option, 'suffix' => "<a href='".admin_url("admin.php?page=mf_group/create/index.php")."'><i class='fa fa-lg fa-plus'></i></a>"));
}

function count_unsent_group($id = 0)
{
	global $wpdb;

	$count_message = "";

	$rows = $wpdb->get_var("SELECT COUNT(queueID) FROM ".$wpdb->base_prefix."group_message INNER JOIN ".$wpdb->base_prefix."group_queue USING (messageID) WHERE queueSent = '0'".($id > 0 ? " AND groupID = '".esc_sql($id)."'" : ""));

	if($rows > 0)
	{
		$count_message = "&nbsp;<i class='fa fa-spinner fa-spin'></i>";
	}

	return $count_message;
}

function menu_group()
{
	global $wpdb;

	$menu_root = 'mf_group/';
	$menu_start = $menu_root.'list/index.php';
	$menu_capability = "edit_posts";

	$count_message = count_unsent_group();

	$menu_title = __("Groups", 'lang_group');
	add_menu_page("", $menu_title.$count_message, $menu_capability, $menu_start, '', 'dashicons-groups');

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

function get_email_link($data)
{
	global $wpdb;

	$out = "";

	$base_url = get_permalink($data['group_id']);

	$out .= $base_url
		.(preg_match("/\?/", $base_url) ? "&" : "?")
		.$data['type']."=".md5(NONCE_SALT.$data['group_id'].$data['email'])
		."&gid=".$data['group_id']
		."&aem=".$data['email'];

	if(isset($data['queue_id']) && $data['queue_id'] > 0)
	{
		$out .= "&qid=".$data['queue_id'];
	}

	return $out;
}

function cron_group()
{
	global $wpdb, $error_text;

	$obj_cron = new mf_cron();
	$obj_group = new mf_group();

	$mail_sent = $sms_sent = 0;

	$query_limit = "";

	$setting_emails_per_hour = get_option_or_default('setting_emails_per_hour');

	if($setting_emails_per_hour > 0)
	{
		$emails_left_to_send = $setting_emails_per_hour;

		$wpdb->get_results("SELECT messageID FROM ".$wpdb->base_prefix."email_message WHERE messageFrom = '' AND messageCreated > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
		$emails_left_to_send -= $wpdb->num_rows;

		$wpdb->get_results("SELECT queueID FROM ".$wpdb->base_prefix."group_queue WHERE queueSent = '1' AND queueSentTime > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
		$emails_left_to_send -= $wpdb->num_rows;

		$query_limit = " LIMIT 0, ".$emails_left_to_send;
	}

	$i = 0;

	$result = $wpdb->get_results("SELECT groupID FROM ".$wpdb->base_prefix."group_queue INNER JOIN ".$wpdb->base_prefix."group_message USING (messageID) WHERE queueSent = '0' GROUP BY groupID ORDER BY RAND()");

	foreach($result as $r)
	{
		$intGroupID = $r->groupID;

		$intGroupVerifyLink = get_post_meta($intGroupID, $obj_group->meta_prefix.'verify_link', true);

		$resultMessages = $wpdb->get_results($wpdb->prepare("SELECT addressEmail, addressCellNo, queueID, messageType, messageFrom, messageName, messageText, messageAttachment, ".$wpdb->base_prefix."group_message.userID FROM ".$wpdb->base_prefix."address INNER JOIN ".$wpdb->base_prefix."group_queue USING (addressID) INNER JOIN ".$wpdb->base_prefix."group_message USING (messageID) WHERE groupID = '%d' AND queueSent = '0' ORDER BY messageType ASC, queueCreated ASC".$query_limit, $intGroupID));

		foreach($resultMessages as $r)
		{
			if($obj_cron->has_expired(array('margin' => .9)))
			{
				break;
			}

			$intQueueID = $r->queueID;
			$strAddressEmail = trim($r->addressEmail);
			$strAddressCellNo = $r->addressCellNo;
			$strMessageType = $r->messageType;
			$strMessageFrom = $r->messageFrom;
			$strMessageName = $r->messageName;
			$strMessageText = $r->messageText;
			$strMessageAttachment = $r->messageAttachment;
			$intUserID = $r->userID;

			if($strMessageType == "email")
			{
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

					$mail_headers = "From: ".$strMessageFromName." <".$strMessageFrom.">\r\n";

					$mail_to = $strAddressEmail;
					$mail_subject = $strMessageName;
					$mail_content = stripslashes(apply_filters('the_content', $strMessageText));

					$unsubscribe_link = get_email_link(array('type' => "unsubscribe", 'group_id' => $intGroupID, 'email' => $strAddressEmail, 'queue_id' => $intQueueID));
					$mail_content = str_replace("[unsubscribe_link]", $unsubscribe_link, $mail_content);

					if($intGroupVerifyLink == 'yes')
					{
						$verify_link = get_email_link(array('type' => "verify", 'group_id' => $intGroupID, 'email' => $strAddressEmail, 'queue_id' => $intQueueID));
						$mail_content .= "<img src='".$verify_link."' style='height: 0; visibility: hidden; width: 0'>";
					}

					list($mail_attachment, $rest) = get_attachment_to_send($strMessageAttachment);

					$sent = send_email(array('to' => $mail_to, 'subject' => $mail_subject, 'content' => $mail_content, 'headers' => $mail_headers, 'attachment' => $mail_attachment, 'save_log' => false));

					if($sent)
					{
						$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."group_queue SET queueSent = '1', queueSentTime = NOW() WHERE queueID = '%d'", $intQueueID));

						$mail_sent++;
					}

					else
					{
						if($error_text == '')
						{
							$error_text = sprintf(__("The message from %s in the group %s could not be sent", 'lang_group'), $strMessageFrom, $obj_group->get_name($intGroupID));
						}

						do_log($error_text);
					}
				}

				else
				{
					$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."group_queue WHERE queueID = '%d'", $intQueueID));
				}
			}

			else if($strMessageType == "sms")
			{
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
					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."group_queue SET queueSent = '1', queueSentTime = NOW() WHERE queueID = '%d'", $intQueueID));

					$sms_sent++;

					do_log("Not sent to ".$strAddressCellNo, 'trash');
				}

				else
				{
					do_log("Not sent to ".$strAddressCellNo.", ".shorten_text(array('string' => htmlspecialchars($strMessageText), 'limit' => 10)));
				}
			}

			$i++;
		}
	}
}

function show_group_registration_form($data) //$post_id
{
	global $wpdb, $done_text, $error_text;

	if(!isset($data['text'])){											$data['text'] = '';}
	if(!isset($data['button_text']) || $data['button_text'] == ''){		$data['button_text'] = __("Join", 'lang_group');}

	$out = "";

	$obj_group = new mf_group();

	$arrGroupRegistrationFields = get_post_meta($data['id'], 'group_registration_fields', true);

	$strAddressName = check_var('strAddressName');
	$intAddressZipCode = check_var('intAddressZipCode');
	$strAddressCity = check_var('strAddressCity');
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

		$intAddressID = $obj_group->check_if_address_exists($query_where);

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
				$wpdb->query("INSERT INTO ".$wpdb->base_prefix."address SET addressPublic = '1'".$query_set.", addressCreated = NOW()");

				$intAddressID = $wpdb->insert_id;
			}

			if($intAddressID > 0)
			{
				$obj_group->add_address(array('address_id' => $intAddressID, 'group_id' => $data['id']));

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
				</div>";
			}

		$out .= "</form>";
	}

	return $out;
}

function shortcode_group($atts)
{
	extract(shortcode_atts(array(
		'id' => ''
	), $atts));

	return show_group_registration_form(array('id' => $id));
}