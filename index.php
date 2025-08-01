<?php
/*
Plugin Name: MF Group
Plugin URI: https://github.com/frostkom/mf_group
Description:
Version: 5.13.5
Licence: GPLv2 or later
Author: Martin Fors
Author URI: https://martinfors.se
Text Domain: lang_group
Domain Path: /lang

Requires Plugins: meta-box
*/

if(!function_exists('is_plugin_active') || function_exists('is_plugin_active') && is_plugin_active("mf_base/index.php") && is_plugin_active("mf_address/index.php"))
{
	include_once("include/classes.php");

	$obj_group = new mf_group();

	add_action('cron_base', 'activate_group', mt_rand(1, 10));
	add_action('cron_base', array($obj_group, 'cron_base'), mt_rand(1, 10));

	add_action('enqueue_block_editor_assets', array($obj_group, 'enqueue_block_editor_assets'));
	add_action('init', array($obj_group, 'init'), 1);

	if(is_admin())
	{
		register_activation_hook(__FILE__, 'activate_group');
		register_uninstall_hook(__FILE__, 'uninstall_group');

		add_action('admin_init', array($obj_group, 'settings_group'));
		add_action('admin_init', array($obj_group, 'admin_init'), 0);
		add_action('admin_menu', array($obj_group, 'admin_menu'));

		add_filter('filter_sites_table_pages', array($obj_group, 'filter_sites_table_pages'));

		add_action('admin_notices', array($obj_group, 'admin_notices'));

		add_filter('manage_'.$obj_group->post_type.'_posts_columns', array($obj_group, 'column_header'), 5);
		add_action('manage_'.$obj_group->post_type.'_posts_custom_column', array($obj_group, 'column_cell'), 5, 2);

		add_action('wp_trash_post', array($obj_group, 'wp_trash_post'));
		add_action('deleted_user', array($obj_group, 'deleted_user'));

		add_action('merge_address', array($obj_group, 'merge_address'), 10, 2);

		add_filter('get_groups_to_send_to', array($obj_group, 'get_groups_to_send_to'));
		add_filter('get_group_addresses', array($obj_group, 'get_group_addresses'));

		add_action('rwmb_meta_boxes', array($obj_group, 'rwmb_meta_boxes'));
	}

	else
	{
		add_filter('wp_sitemaps_post_types', array($obj_group, 'wp_sitemaps_post_types'));
	}

	add_filter('get_emails_left_to_send', array($obj_group, 'get_emails_left_to_send'), 10, 4);
	add_filter('get_hourly_release_time', array($obj_group, 'get_hourly_release_time'), 10, 3);

	add_action('widgets_init', array($obj_group, 'widgets_init'));

	add_filter('single_template', 'custom_templates_group');

	add_filter('filter_is_file_used', array($obj_group, 'filter_is_file_used'));

	//add_action('wp_ajax_api_group_table_search', array($obj_group, 'api_group_table_search'));

	function activate_group()
	{
		global $wpdb, $obj_group;

		if(is_admin() && function_exists('is_plugin_active') && !is_plugin_active("mf_address/index.php"))
		{
			deactivate_plugins(plugin_basename(__FILE__));
			wp_die(sprintf(__("You need to install the plugin %s first", 'lang_group'), "MF Address Book"));
		}

		$default_charset = (DB_CHARSET != '' ? DB_CHARSET : 'utf8');

		$arr_add_column = $arr_update_column = $arr_add_index = [];

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

		$arr_add_column[$wpdb->prefix."group_message"] = array(
			//'' => "ALTER TABLE [table] ADD [column]  AFTER ",
		);

		$arr_update_column[$wpdb->base_prefix."group_message"] = array(
			//'' => "ALTER TABLE [table] CHANGE [column] [column] )",
		);

		$arr_add_index[$wpdb->prefix."group_message"] = array(
			//'' => "ALTER TABLE [table] ADD INDEX [column] ([column])",
		);

		$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."group_message_link (
			linkID INT UNSIGNED NOT NULL AUTO_INCREMENT,
			linkUrl VARCHAR(255),
			linkUsed DATETIME,
			PRIMARY KEY (linkID),
			KEY linkUrl (linkUrl)
		) DEFAULT CHARSET=".$default_charset);

		$arr_add_column[$wpdb->prefix."group_message_link"] = array(
			//'linkUsed' => "ALTER TABLE [table] ADD [column] DATETIME AFTER linkUrl",
		);

		$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."group_queue (
			queueID INT UNSIGNED NOT NULL AUTO_INCREMENT,
			addressID INT UNSIGNED NOT NULL DEFAULT '0',
			messageID INT UNSIGNED NOT NULL DEFAULT '0',
			queueSent ENUM('0','1') NOT NULL DEFAULT '0',"
			//."queueReceived ENUM('-1', '0','1') NOT NULL DEFAULT '0',"
			."queueStatus VARCHAR(20) NOT NULL DEFAULT '',
			queueStatusMessage TEXT,
			queueCreated DATETIME NOT NULL,
			queueSentTime DATETIME NOT NULL,
			queueViewed DATETIME NOT NULL,
			PRIMARY KEY (queueID),
			KEY messageID (messageID),
			KEY queueSent (queueSent)
		) DEFAULT CHARSET=".$default_charset);

		$arr_add_column[$wpdb->prefix."group_queue"] = array(
			//'queueViewed' => "ALTER TABLE [table] ADD [column] DATETIME NOT NULL AFTER queueSentTime",
			'queueStatus' => "ALTER TABLE [table] ADD [column] VARCHAR(20) NOT NULL DEFAULT '' AFTER queueSent",
			//'queueStatusMessage' => "ALTER TABLE [table] ADD [column] TEXT AFTER queueStatus",
		);

		$arr_update_column[$wpdb->prefix."group_queue"]['queueReceived'] = "ALTER TABLE [table] DROP COLUMN [column]";

		$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."address2group (
			addressID INT UNSIGNED NOT NULL,
			groupID INT UNSIGNED NOT NULL,
			addressAdded DATETIME DEFAULT NULL,
			groupAccepted ENUM('0', '1') NOT NULL DEFAULT '1',
			groupAcceptanceSent DATETIME DEFAULT NULL,
			groupUnsubscribed ENUM('0', '1') NOT NULL DEFAULT '0',
			KEY addressID (addressID),
			KEY groupID (groupID)
		) DEFAULT CHARSET=".$default_charset);

		$arr_add_column[$wpdb->prefix."address2group"] = array(
			'addressAdded' => "ALTER TABLE [table] ADD [column] DATETIME DEFAULT NULL AFTER groupID",
		);

		$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."group_version (
			versionID INT UNSIGNED NOT NULL AUTO_INCREMENT,
			versionType VARCHAR(20),
			addressID INT UNSIGNED NOT NULL,
			groupID INT UNSIGNED NOT NULL,
			versionCreated DATETIME DEFAULT NULL,
			userID INT UNSIGNED DEFAULT NULL,
			PRIMARY KEY (versionID),
			KEY addressID (addressID),
			KEY groupID (groupID)
		) DEFAULT CHARSET=".$default_charset);

		update_columns($arr_update_column);
		add_columns($arr_add_column);
		add_index($arr_add_index);
	}

	function custom_templates_group($single_template)
	{
		global $post, $obj_group;

		if($post->post_type == $obj_group->post_type)
		{
			// Get HTML from a generic page instead

			$single_template = plugin_dir_path(__FILE__)."templates/single-".$post->post_type.".php";
		}

		return $single_template;
	}
}

function uninstall_group()
{
	include_once("include/classes.php");

	$obj_group = new mf_group();

	mf_uninstall_plugin(array(
		'uploads' => $obj_group->post_type,
		'options' => array('setting_emails_per_hour', 'setting_group_see_other_roles', 'setting_group_trace_links', 'setting_group_outgoing_text', 'setting_group_import', 'setting_group_debug', 'setting_group_log_file', 'option_group_synced'),
		'post_types' => array($obj_group->post_type),
		'tables' => array('group_message', 'group_message_link', 'group_queue', 'address2group', 'group_version'),
	));
}