<?php

function delete_group($post_id)
{
	global $post_type;

	if($post_type == 'mf_group')
	{
		$mail_to = "martin.fors@frostkom.se";
		$mail_headers = "From: ".get_bloginfo('name')." <".get_bloginfo('admin_email').">\r\n";
		$mail_subject = "Delete postID (#".$post_id.") from ".$wpdb->base_prefix."group_message etc.";
		$mail_content = $mail_subject;

		wp_mail($mail_to, $mail_subject, $mail_content, $mail_headers);

		/*$result = $wpdb->get_results($wpdb->prepare("SELECT messageID FROM ".$wpdb->base_prefix."group_message WHERE groupID = '%d'", $post_id));

		foreach($result as $r)
		{
			$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."group_queue WHERE messageID = '%d'", $r->messageID));
		}

		$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."group_message WHERE groupID = '%d'", $post_id));
		$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."address2group WHERE groupID = '%d'", $post_id));*/
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
		$result = $wpdb->get_results("SELECT messageType, addressID, addressFirstName, addressSurName, addressEmail, addressCellNo FROM ".$wpdb->base_prefix."group_message INNER JOIN ".$wpdb->base_prefix."group_queue USING (messageID) INNER JOIN ".$wpdb->base_prefix."address USING (addressID) WHERE queueSent = '0' AND queueCreated < DATE_SUB(NOW(), INTERVAL 3 HOUR) LIMIT 0, 5");

		if($wpdb->num_rows > 0)
		{
			$unsent_links = "";

			foreach($result as $r)
			{
				$strMessageType = $r->messageType;
				$intAddressID = $r->addressID;
				$strAddressFirstName = $r->addressFirstName;
				$strAddressSurName = $r->addressSurName;
				$emlAddressEmail = $r->addressEmail;
				$strAddressCellNo = $r->addressCellNo;

				$strAddressName = $strAddressFirstName." ".$strAddressSurName." &lt;".($strMessageType == "email" ? $emlAddressEmail : $strAddressCellNo)."&gt;";

				$unsent_links .= ($unsent_links != '' ? ", " : "")."<a href='".admin_url("admin.php?page=mf_address/create/index.php&intAddressID=".$intAddressID)."'>".$strAddressName."</a>";
			}

			$error_text = __("There were unsent messages", 'lang_group')." (".$unsent_links.")";

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
		'rewrite' => array(
			'slug' => __("group", 'lang_group'),
		),
	);

	register_post_type('mf_group', $args);
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
		"setting_emails_per_hour" => __("Outgoing e-mails per hour", 'lang_group'),
		"setting_group_see_other_roles" => __("See groups created by other roles", 'lang_group'),
		"setting_group_import" => __("Add all imported to this group", 'lang_group'),
	);

	foreach($arr_settings as $handle => $text)
	{
		add_settings_field($handle, $text, $handle."_callback", BASE_OPTIONS_PAGE, $options_area);

		register_setting(BASE_OPTIONS_PAGE, $handle);
	}
}

function settings_group_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);

	echo settings_header($setting_key, __("Group", 'lang_group'));
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

	echo show_select(array('data' => $arr_data, 'name' => $setting_key, 'compare' => $option, 'suffix' => "<a href='".admin_url("admin.php?page=mf_group/create/index.php")."'><i class='fa fa-lg fa-plus'></i></a>"));
}

function setting_group_see_other_roles_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key, 'yes');

	echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'compare' => $option));
}

function setting_emails_per_hour_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'type' => 'number'));
}

function count_unsent_group($id = 0)
{
	global $wpdb;

	$count_message = "";

	$rows = $wpdb->get_var("SELECT COUNT(queueID) FROM ".$wpdb->base_prefix."group_message INNER JOIN ".$wpdb->base_prefix."group_queue USING (messageID) WHERE queueSent = '0'".($id > 0 ? " AND groupID = '".esc_sql($id)."'" : ""));

	if($rows > 0)
	{
		$count_message = "&nbsp;<i class='fa fa-spin fa-spinner'></i>";
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

	add_menu_page("", __("Groups", 'lang_group').$count_message, $menu_capability, $menu_start, '', 'dashicons-groups');

	add_submenu_page($menu_start, __("List", 'lang_group'), __("List", 'lang_group'), $menu_capability, $menu_start);
	add_submenu_page($menu_start, __("Add New", 'lang_group'), __("Add New", 'lang_group'), $menu_capability, $menu_root."create/index.php");
	add_submenu_page($menu_start, __("Send New", 'lang_group'), __("Send New", 'lang_group'), $menu_capability, $menu_root."send/index.php");
	add_submenu_page($menu_root, __("Sent", 'lang_group'), __("Sent", 'lang_group'), $menu_capability, $menu_root."sent/index.php");
	add_submenu_page($menu_root, __("Import", 'lang_group'), __("Import", 'lang_group'), $menu_capability, $menu_root."import/index.php");
}

function get_group_name($id)
{
	global $wpdb;

	return $wpdb->get_var($wpdb->prepare("SELECT post_title FROM ".$wpdb->posts." WHERE post_type = 'mf_group' AND ID = '%d'", $id));
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
	global $wpdb;

	$obj_cron = new mf_cron();

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

	$result = $wpdb->get_results("SELECT groupID, addressEmail, addressCellNo, queueID, messageType, messageFrom, messageName, messageText, messageAttachment FROM ".$wpdb->base_prefix."address INNER JOIN ".$wpdb->base_prefix."group_queue USING (addressID) INNER JOIN ".$wpdb->base_prefix."group_message USING (messageID) WHERE queueSent = '0' ORDER BY messageType ASC, queueCreated ASC".$query_limit);

	$i = 0;

	foreach($result as $r)
	{
		if($obj_cron->has_expired(array('margin' => .90)))
		{
			break;
		}

		$intGroupID = $r->groupID;
		$intQueueID = $r->queueID;
		$strAddressEmail = trim($r->addressEmail);
		$strAddressCellNo = $r->addressCellNo;
		$strMessageType = $r->messageType;
		$strMessageFrom = $r->messageFrom;
		$strMessageName = $r->messageName;
		$strMessageText = $r->messageText;
		$strMessageAttachment = $r->messageAttachment;

		//$resultUnsent = $wpdb->get_var($wpdb->prepare("SELECT queueSent FROM ".$wpdb->base_prefix."group_queue WHERE queueID = '%d' AND queueSent = '0' LIMIT 0, 1", $intQueueID));

		if($strMessageType == "email")
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

			if($strAddressEmail != '' && is_domain_valid($strAddressEmail))
			{
				$mail_to = $strAddressEmail;
				$mail_subject = $strMessageName;
				$mail_content = stripslashes(apply_filters('the_content', $strMessageText));

				$unsubscribe_link = get_email_link(array('type' => "unsubscribe", 'group_id' => $intGroupID, 'email' => $strAddressEmail, 'queue_id' => $intQueueID)); 
				$verify_link = get_email_link(array('type' => "verify", 'group_id' => $intGroupID, 'email' => $strAddressEmail, 'queue_id' => $intQueueID));

				$mail_content = str_replace("[unsubscribe_link]", $unsubscribe_link, $mail_content);

				$mail_content .= "<img src='".$verify_link."' style='height: 0; visibility: hidden; width: 0'>";

				$mail_attachment = get_attachment_to_send($strMessageAttachment);

				add_filter('wp_mail_content_type', 'set_html_content_type');

				$sent = wp_mail($mail_to, $mail_subject, $mail_content, $mail_headers, $mail_attachment);

				if($sent)
				{
					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."group_queue SET queueSent = '1', queueSentTime = NOW() WHERE queueID = '%d'", $intQueueID));

					$mail_sent++;

					do_log("Not sent to ".$mail_to, "trash");
				}

				else
				{
					do_log("Not sent to ".$mail_to.", ".$mail_subject.", ".substr(htmlspecialchars($mail_content), 0, 20)."..., ".htmlspecialchars($mail_headers).", ".var_export($mail_attachment, true));
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
			include_once(ABSPATH."wp-admin/includes/plugin.php");

			if(is_plugin_active("mf_sms/index.php"))
			{
				include_once(ABSPATH."wp-content/plugins/mf_sms/include/functions.php");
			}
			##################

			$sent = send_sms(array('from' => $strMessageFrom, 'to' => $strAddressCellNo, 'text' => $strMessageText));

			if($sent == "OK")
			{
				echo "OK...";

				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."group_queue SET queueSent = '1', queueSentTime = NOW() WHERE queueID = '%d'", $intQueueID));

				$sms_sent++;
			}

			else
			{
				do_log("Not sent to ".$strAddressCellNo.", ".substr(htmlspecialchars($strMessageText), 0, 20)."...");
			}
		}

		$i++;
	}
}

function show_group_registration_form($post_id)
{
	global $wpdb, $done_text, $error_text;

	$out = "";

	$obj_group = new mf_group();

	$arrGroupRegistrationFields = get_post_meta($post_id, 'group_registration_fields', true);

	$strAddressName = check_var('strAddressName');
	$intAddressZipCode = check_var('intAddressZipCode');
	$strAddressCity = check_var('strAddressCity');
	$strAddressAddress = check_var('strAddressAddress');
	$strAddressTelNo = check_var('strAddressTelNo');
	$strAddressEmail = check_var('strAddressEmail');
	$strAddressExtra = check_var('strAddressExtra');

	if(isset($_POST['btnGroupJoin']) && wp_verify_nonce($_POST['_wpnonce'], 'group_join'))
	{
		$strGroupVerifyAddress = get_post_meta($post_id, 'group_verify_address', true);

		$query_where = $query_set = "";

		if(is_array($arrGroupRegistrationFields))
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

			$intGroupContactPage = get_post_meta($post_id, 'group_contact_page', true);

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
						$form_field_id = $obj_form->get_form_email_field();

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
				$obj_group->add_address(array('address_id' => $intAddressID, 'group_id' => $post_id));

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

			if(is_array($arrGroupRegistrationFields))
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
			}

			else
			{
				$out .= show_textfield(array('name' => "strAddressEmail", 'text' => __("E-mail", 'lang_group'), 'value' => $strAddressEmail, 'required' => true));
			}

			$out .= "<div class='form_button'>"
				.show_submit(array('name' => "btnGroupJoin", 'text' => __("Join", 'lang_group')))
			."</div>"
			.wp_nonce_field('group_join', '_wpnonce', true, false)
		."</form>";
	}

	return $out;
}