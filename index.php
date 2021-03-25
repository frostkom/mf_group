<?php
/*
Plugin Name: MF Group
Plugin URI: https://github.com/frostkom/mf_group
Description: 
Version: 5.8.10
Licence: GPLv2 or later
Author: Martin Fors
Author URI: https://frostkom.se
Text Domain: lang_group
Domain Path: /lang

Depends: MF Base, MF Address Book
GitHub Plugin URI: frostkom/mf_group
*/

if(is_plugin_active("mf_base/index.php"))
{
	include_once("include/classes.php");

	load_plugin_textdomain('lang_group', false, dirname(plugin_basename(__FILE__))."/lang/");

	$obj_group = new mf_group();

	add_action('cron_base', 'activate_group', mt_rand(1, 10));
	add_action('cron_base', array($obj_group, 'cron_base'), mt_rand(1, 10));

	add_action('init', array($obj_group, 'init'), 1);

	if(is_admin())
	{
		register_activation_hook(__FILE__, 'activate_group');
		register_uninstall_hook(__FILE__, 'uninstall_group');

		add_action('admin_init', array($obj_group, 'settings_group'));
		add_action('admin_init', array($obj_group, 'admin_init'), 0);
		add_action('admin_menu', array($obj_group, 'admin_menu'));

		//add_filter('wp_get_default_privacy_policy_content', array($obj_group, 'add_policy'));

		add_action('admin_notices', array($obj_group, 'admin_notices'));

		add_action('wp_trash_post', array($obj_group, 'wp_trash_post'));
		add_action('deleted_user', array($obj_group, 'deleted_user'));

		add_action('merge_address', array($obj_group, 'merge_address'), 10, 2);

		add_filter('get_groups_to_send_to', array($obj_group, 'get_groups_to_send_to'));
		add_filter('get_group_addresses', array($obj_group, 'get_group_addresses'));

		add_filter('count_shortcode_button', array($obj_group, 'count_shortcode_button'));
		add_filter('get_shortcode_output', array($obj_group, 'get_shortcode_output'));
		add_filter('get_shortcode_list', array($obj_group, 'get_shortcode_list'));
	}

	else
	{
		add_filter('wp_sitemaps_post_types', array($obj_group, 'wp_sitemaps_post_types'));

		add_action('wp_head', array($obj_group, 'wp_head'), 0);
	}

	add_filter('get_emails_left_to_send', array($obj_group, 'get_emails_left_to_send'), 10, 4);
	add_filter('get_hourly_release_time', array($obj_group, 'get_hourly_release_time'), 10, 3);

	add_shortcode('mf_group', array($obj_group, 'shortcode_group'));

	add_action('widgets_init', array($obj_group, 'widgets_init'));

	add_filter('single_template', 'custom_templates_group');

	add_filter('filter_is_file_used', array($obj_group, 'filter_is_file_used'));

	function activate_group()
	{
		global $wpdb, $obj_group;

		require_plugin("mf_address/index.php", "MF Address Book");

		$default_charset = DB_CHARSET != '' ? DB_CHARSET : "utf8";

		$arr_add_column = $arr_update_column = $arr_add_index = array();

		$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."group_message (
			messageID INT UNSIGNED NOT NULL AUTO_INCREMENT,
			groupID INT UNSIGNED NOT NULL DEFAULT '0',
			messageType VARCHAR(10),
			messageFrom VARCHAR(255),
			messageName VARCHAR(200),
			messageText TEXT,
			messageAttachment TEXT,
			messageSchedule DATETIME DEFAULT NULL,
			messageCreated DATETIME,
			userID INT UNSIGNED DEFAULT NULL,
			messageDeleted ENUM('0', '1') NOT NULL DEFAULT '0',
			messageDeletedDate DATETIME DEFAULT NULL,
			messageDeletedID INT UNSIGNED DEFAULT NULL,
			PRIMARY KEY (messageID),
			KEY groupID (groupID)
		) DEFAULT CHARSET=".$default_charset);

		/*$arr_add_column[$wpdb->prefix."group_message"] = array(
			'' => "ALTER TABLE [table] ADD [column]  AFTER ",
		);*/

		$arr_update_column[$wpdb->base_prefix."group_message"] = array(
			'messageName' => "ALTER TABLE [table] CHANGE [column] [column] VARCHAR(200)",
		);

		/*$arr_add_index[$wpdb->prefix."group_message"] = array(
			'' => "ALTER TABLE [table] ADD INDEX [column] ([column])",
		);*/

		$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."group_queue (
			queueID INT UNSIGNED NOT NULL AUTO_INCREMENT,
			addressID INT UNSIGNED NOT NULL DEFAULT '0',
			messageID INT UNSIGNED NOT NULL DEFAULT '0',
			queueSent ENUM('0','1') NOT NULL DEFAULT '0',
			queueReceived ENUM('-1', '0','1') NOT NULL DEFAULT '0',
			queueCreated DATETIME NOT NULL,
			queueSentTime DATETIME NOT NULL,
			PRIMARY KEY (queueID),
			KEY messageID (messageID),
			KEY queueSent (queueSent)
		) DEFAULT CHARSET=".$default_charset);

		$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."address2group (
			addressID INT UNSIGNED NOT NULL,
			groupID INT UNSIGNED NOT NULL,
			groupAccepted ENUM('0', '1') NOT NULL DEFAULT '1',
			groupAcceptanceSent DATETIME DEFAULT NULL,
			groupUnsubscribed ENUM('0', '1') NOT NULL DEFAULT '0',
			KEY addressID (addressID),
			KEY groupID (groupID)
		) DEFAULT CHARSET=".$default_charset);

		update_columns($arr_update_column);
		add_columns($arr_add_column);
		add_index($arr_add_index);

		delete_base(array(
			'table' => "group_message",
			'field_prefix' => "message",
			'child_tables' => array(
				'group_queue' => array(
					'action' => 'delete',
					'field_prefix' => "message",
				),
			),
		));

		replace_post_meta(array('old' => 'group_acceptance_email', 'new' => $obj_group->meta_prefix.'acceptance_email'));
		replace_post_meta(array('old' => 'group_acceptance_subject', 'new' => $obj_group->meta_prefix.'acceptance_subject'));
		replace_post_meta(array('old' => 'group_acceptance_text', 'new' => $obj_group->meta_prefix.'acceptance_text'));
		replace_post_meta(array('old' => 'group_verify_address', 'new' => $obj_group->meta_prefix.'verify_address'));
		replace_post_meta(array('old' => 'group_contact_page', 'new' => $obj_group->meta_prefix.'contact_page'));
		replace_post_meta(array('old' => 'group_registration_fields', 'new' => $obj_group->meta_prefix.'registration_fields'));
	}

	function uninstall_group()
	{
		mf_uninstall_plugin(array(
			'uploads' => 'mf_group',
			'options' => array('setting_emails_per_hour', 'setting_group_see_other_roles', 'setting_group_outgoing_text', 'setting_group_import', 'setting_group_debug'),
			'post_types' => array('mf_group'),
			'tables' => array('group_message', 'group_queue', 'address2group'),
		));
	}

	function custom_templates_group($single_template)
	{
		global $post;

		if(in_array($post->post_type, array("mf_group")))
		{
			$single_template = plugin_dir_path(__FILE__)."templates/single-".$post->post_type.".php";
		}

		return $single_template;
	}
}