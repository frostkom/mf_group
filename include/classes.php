<?php

class mf_group
{
	function __construct($id = 0)
	{
		if($id > 0)
		{
			$this->id = $id;
		}

		else
		{
			$this->id = check_var('intGroupID');
		}

		$this->meta_prefix = "mf_group_";
	}

	function fetch_request()
	{
		if(isset($_SESSION['intGroupID'])){			unset($_SESSION['intGroupID']);}
		if(isset($_SESSION['is_part_of_group'])){	unset($_SESSION['is_part_of_group']);}
	}

	function save_data()
	{
		global $wpdb, $error_text, $done_text;

		$out = "";

		if(isset($_REQUEST['btnGroupDelete']) && $this->id > 0 && wp_verify_nonce($_REQUEST['_wpnonce'], 'group_delete_'.$this->id))
		{
			if(wp_trash_post($this->id))
			{
				$obj_group = new mf_group();
				$obj_group->remove_all_address($this->id);

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

		return $out;
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

		return $wpdb->get_var("SELECT messageFrom FROM ".$wpdb->base_prefix."group_message INNER JOIN ".$wpdb->posts." ON ".$wpdb->base_prefix."group_message.groupID = ".$wpdb->posts.".ID AND post_type = 'mf_group' ORDER BY messageCreated DESC LIMIT 0, 1");
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
			$intAddressID = $wpdb->get_var("SELECT addressID FROM ".$wpdb->base_prefix."address WHERE addressDeleted = '0' AND ".$query_where);

			return $intAddressID;
		}
	}

	function add_address($data)
	{
		global $wpdb;

		if($data['address_id'] > 0 && $data['group_id'] > 0)
		{
			$wpdb->get_var($wpdb->prepare("SELECT addressID FROM ".$wpdb->base_prefix."address2group WHERE addressID = '%d' AND groupID = '%d' LIMIT 0, 1", $data['address_id'], $data['group_id']));

			if($wpdb->num_rows == 0)
			{
				$setting_group_acceptance_email = get_post_meta($data['group_id'], 'group_acceptance_email', true);

				$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."address2group SET addressID = '%d', groupID = '%d', groupAccepted = '%d'", $data['address_id'], $data['group_id'], ($setting_group_acceptance_email == 'yes' ? 0 : 1)));

				if($setting_group_acceptance_email == 'yes')
				{
					$strGroupAcceptanceSubject = get_post_meta_or_default($data['group_id'], 'group_acceptance_subject', true, __("Accept subscription to %s", 'lang_group'));
					$strGroupAcceptanceText = get_post_meta_or_default($data['group_id'], 'group_acceptance_text', true, __("You have been added to the group %s but will not get any messages until you have accepted this subscription by clicking the link below.", 'lang_group'));

					$obj_address = new mf_address();

					$strAddressEmail = $obj_address->get_address($data['address_id']);
					$strGroupName = $this->get_name($data['group_id']);

					$mail_to = $strAddressEmail;
					$mail_subject = sprintf($strGroupAcceptanceSubject, $strGroupName);
					$mail_content = sprintf($strGroupAcceptanceText, $strGroupName);

					$subscribe_link = get_email_link(array('type' => "subscribe", 'group_id' => $data['group_id'], 'email' => $strAddressEmail));
					$mail_content .= "<p>&nbsp;</p><p><a href='".$subscribe_link."'>".__("Accept Link", 'lang_group')."</a></p>";

					$sent = send_email(array('to' => $mail_to, 'subject' => $mail_subject, 'content' => $mail_content));
				}
			}
		}
	}

	function remove_address($address_id = 0, $group_id = 0)
	{
		global $wpdb;

		//error_log("Deleted (AID: ".$address_id.", GID: ".$group_id.")");

		$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."address2group WHERE addressID = '%d'".($group_id > 0 ? " AND groupID = '".$group_id."'" : ""), $address_id));
	}

	function remove_all_address($group_id = 0)
	{
		global $wpdb;

		$user_data = get_userdata(get_current_user_id());

		error_log("Deleted all (GID: ".$group_id.", User: ".$user_data->display_name.")");

		$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."address2group WHERE groupID = '%d'", $group_id));
	}

	function amount_in_group()
	{
		global $wpdb;

		return $wpdb->get_var($wpdb->prepare("SELECT COUNT(addressID) FROM ".$wpdb->base_prefix."address INNER JOIN ".$wpdb->base_prefix."address2group USING (addressID) WHERE groupID = '%d' AND addressDeleted = '0' AND groupAccepted = '1' AND groupUnsubscribed = '0'", $this->id));
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

		$result = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->base_prefix."address INNER JOIN ".$wpdb->base_prefix."address2group USING (addressID) WHERE groupID = '%d' AND addressDeleted = '0' GROUP BY addressID ORDER BY addressPublic ASC, addressSurName ASC, addressFirstName ASC", $this->type));

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
			.show_textfield(array('name' => $this->get_field_name('group_heading'), 'text' => __("Heading", 'lang_group'), 'value' => $instance['group_heading']))
			.show_textarea(array('name' => $this->get_field_name('group_text'), 'text' => __("Text", 'lang_group'), 'value' => $instance['group_text']))
			.show_select(array('data' => $arr_data, 'name' => $this->get_field_name('group_id'), 'text' => __("Group", 'lang_group'), 'value' => $instance['group_id']))
			.show_textfield(array('name' => $this->get_field_name('group_button_text'), 'text' => __("Button Text", 'lang_group'), 'value' => $instance['group_button_text'], 'placeholder' => __("Join", 'lang_group')))
		."</div>";
	}
}

class mf_group_table extends mf_list_table
{
	function set_default()
	{
		global $wpdb;

		$this->post_type = "mf_group";

		$this->arr_settings['query_select_id'] = "ID";

		$this->arr_settings['has_autocomplete'] = true;
		$this->arr_settings['plugin_name'] = 'mf_group';
		$this->orderby_default = "post_title";

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

		$rowsAddressesNotAccepted = $wpdb->get_var("SELECT COUNT(addressID) FROM ".$wpdb->base_prefix."address INNER JOIN ".$wpdb->base_prefix."address2group USING (addressID) WHERE addressDeleted = '0' AND groupAccepted = '0'");

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

		switch($column_name)
		{
			case 'post_title':
				$post_edit_url = "#";
				$post_author = $item['post_author'];

				$actions = array();

				if($post_status != "trash")
				{
					$post_edit_url = "?page=mf_group/create/index.php&intGroupID=".$post_id;

					$actions['edit'] = "<a href='".$post_edit_url."'>".__("Edit", 'lang_group')."</a>";

					if($post_author == get_current_user_id() || IS_ADMIN)
					{
						$actions['delete'] = "<a href='".wp_nonce_url("?page=mf_group/list/index.php&btnGroupDelete&intGroupID=".$post_id, 'group_delete_'.$post_id)."'>".__("Delete", 'lang_group')."</a>";
					}

					$actions['addnremove'] = "<a href='?page=mf_address/list/index.php&intGroupID=".$post_id."'>".__("Add or remove", 'lang_group')."</a>";
					$actions['import'] = "<a href='?page=mf_group/import/index.php&intGroupID=".$post_id."'>".__("Import", 'lang_group')."</a>";

					$obj_group = new mf_group($post_id);
					$amount = $obj_group->amount_in_group();

					if($amount > 0)
					{
						$actions['export_csv'] = "<a href='".wp_nonce_url("?page=mf_group/list/index.php&btnExportRun&intExportType=".$post_id."&strExportAction=csv", 'export_run')."'>".__("CSV", 'lang_group')."</a>";

						if(is_plugin_active("mf_phpexcel/index.php"))
						{
							$actions['export_xls'] = "<a href='".wp_nonce_url("?page=mf_group/list/index.php&btnExportRun&intExportType=".$post_id."&strExportAction=xls", 'export_run')."'>".__("XLS", 'lang_group')."</a>";
						}
					}
				}

				else
				{
					$actions['recover'] = "<a href='?page=mf_group/create/index.php&intGroupID=".$post_id."&recover'>".__("Recover", 'lang_group')."</a>";
				}

				$out .= "<a href='".$post_edit_url."'>".$item[$column_name]."</a>"
				.$this->row_actions($actions);
			break;

			case 'post_status':
				if($item[$column_name] == "publish")
				{
					$post_url = get_permalink($post_id);

					$out .= "<i class='fa fa-globe green'></i>
					<div class='row-actions'>
						<a href='".$post_url."'><i class='fa fa-link'></i></a>
					</div>";
				}
			break;

			case 'amount':
				$obj_group = new mf_group($post_id);
				$amount = $obj_group->amount_in_group();

				$actions = array();

				if($post_status != "trash")
				{
					if($amount > 0)
					{
						$actions['send_email'] = "<a href='?page=mf_group/send/index.php&intGroupID=".$post_id."&type=email'><i class='fa fa-lg fa-envelope-o'></i></a>";

						if(is_plugin_active("mf_sms/index.php") && sms_is_active())
						{
							$actions['send_sms'] = "<a href='?page=mf_group/send/index.php&intGroupID=".$post_id."&type=sms'><i class='fa fa-lg fa-mobile'></i></a>";
						}
					}
				}

				$out .= "<a href='?page=mf_address/list/index.php&intGroupID=".$post_id."&no_ses&is_part_of_group=1'>".$amount."</a>"
				.$this->row_actions($actions);
			break;

			case 'not_accepted':
				$rowsAddressesNotAccepted = $wpdb->get_var($wpdb->prepare("SELECT COUNT(addressID) FROM ".$wpdb->base_prefix."address INNER JOIN ".$wpdb->base_prefix."address2group USING (addressID) WHERE groupID = '%d' AND addressDeleted = '0' AND groupAccepted = '0'", $post_id));

				$out .= $rowsAddressesNotAccepted;
			break;

			case 'unsubscribed':
				$rowsAddressesUnsubscribed = $wpdb->get_var($wpdb->prepare("SELECT COUNT(addressID) FROM ".$wpdb->base_prefix."address INNER JOIN ".$wpdb->base_prefix."address2group USING (addressID) WHERE groupID = '%d' AND addressDeleted = '0' AND groupUnsubscribed = '1'", $post_id));

				$dteMessageCreated = $wpdb->get_var($wpdb->prepare("SELECT messageCreated FROM ".$wpdb->base_prefix."group_message WHERE groupID = '%d' ORDER BY messageCreated DESC", $post_id));

				$out .= $rowsAddressesUnsubscribed;

				if($post_status == 'publish' || $dteMessageCreated != '')
				{
					$current_user = wp_get_current_user();
					$user_email = $current_user->user_email;

					$out .= "<div class='row-actions'>
						<a href='".get_email_link(array('type' => "unsubscribe", 'group_id' => $post_id, 'email' => $user_email))."'>".__("Test", 'lang_group')."</a>
					</div>";
				}
			break;

			case 'post_author':
				$out .= get_user_info(array('id' => $item[$column_name], 'type' => 'short_name'));
			break;

			case 'sent':
				$result = $wpdb->get_results($wpdb->prepare("SELECT messageID, messageCreated FROM ".$wpdb->base_prefix."group_message WHERE groupID = '%d' ORDER BY messageCreated DESC LIMIT 0, 1", $post_id));

				foreach($result as $r)
				{
					$intMessageID = $r->messageID;
					$dteMessageCreated = $r->messageCreated;

					if($dteMessageCreated != '')
					{
						$actions['sent'] = "<a href='?page=mf_group/sent/index.php&intGroupID=".$post_id."'>".__("Sent", 'lang_group')."</a>";

						$intMessageSent = $wpdb->get_var($wpdb->prepare("SELECT COUNT(queueID) FROM ".$wpdb->base_prefix."group_queue WHERE messageID = '%d' AND queueSent = '1'", $intMessageID));
						$intMessageNotSent = $wpdb->get_var($wpdb->prepare("SELECT COUNT(queueID) FROM ".$wpdb->base_prefix."group_queue WHERE messageID = '%d' AND queueSent = '0'", $intMessageID));

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