<?php
/*
Plugin Name: MF Group
Plugin URI: https://github.com/frostkom/mf_group
Description: 
Version: 3.3.4
Author: Martin Fors
Author URI: http://frostkom.se
Text Domain: lang_group
Domain Path: /lang

GitHub Plugin URI: frostkom/mf_group
*/

include_once("include/classes.php");
include_once("include/functions.php");

add_action('cron_base', 'activate_group');
add_action('cron_base', 'cron_group');

add_action('init', 'init_group');
add_action('widgets_init', 'widgets_group');

if(is_admin())
{
	register_activation_hook(__FILE__, 'activate_group');
	register_uninstall_hook(__FILE__, 'uninstall_group');

	add_action('admin_init', 'settings_group');
	add_action('admin_menu', 'menu_group');
	add_action('admin_notices', 'notices_group');
	add_action('before_delete_post', 'delete_group');
	add_action('deleted_user', 'deleted_user_group');
}

add_filter('single_template', 'custom_templates_group');

load_plugin_textdomain('lang_group', false, dirname(plugin_basename(__FILE__)).'/lang/');

function activate_group()
{
	global $wpdb;

	require_plugin("mf_address/index.php", "MF Address");

	$default_charset = DB_CHARSET != '' ? DB_CHARSET : "utf8";

	$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."group_message (
		messageID INT unsigned NOT NULL AUTO_INCREMENT,
		groupID INT unsigned NOT NULL DEFAULT '0',
		messageType VARCHAR(10),
		messageFrom VARCHAR(255),
		messageName VARCHAR(60),
		messageText TEXT,
		messageAttachment TEXT,
		messageCreated DATETIME,
		userID INT unsigned NOT NULL DEFAULT '0',
		PRIMARY KEY (messageID)
	) DEFAULT CHARSET=".$default_charset);

	$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."group_queue (
		queueID INT unsigned NOT NULL AUTO_INCREMENT,
		addressID INT unsigned NOT NULL DEFAULT '0',
		messageID INT unsigned NOT NULL DEFAULT '0',
		queueSent ENUM('0','1') NOT NULL DEFAULT '0',
		queueReceived ENUM('-1', '0','1') NOT NULL DEFAULT '0',
		queueCreated DATETIME NOT NULL,
		queueSentTime DATETIME NOT NULL,
		PRIMARY KEY (queueID)
	) DEFAULT CHARSET=".$default_charset);

	$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."address2group (
		addressID INT unsigned NOT NULL,
		groupID INT unsigned NOT NULL,
		groupUnsubscribed ENUM('0', '1') NOT NULL DEFAULT '0'
	) DEFAULT CHARSET=".$default_charset);

	$arr_add_column = array();

	$arr_add_column[$wpdb->base_prefix."group_message"]['messageAttachment'] = "ALTER TABLE [table] ADD [column] TEXT AFTER messageText";
	$arr_add_column[$wpdb->base_prefix."address2group"]['groupUnsubscribed'] = "ALTER TABLE [table] ADD [column] ENUM('0', '1') NOT NULL DEFAULT '0' AFTER groupID";
	$arr_add_column[$wpdb->base_prefix."group_queue"]['queueReceived'] = "ALTER TABLE [table] ADD [column] ENUM('-1', '0','1') NOT NULL DEFAULT '0' AFTER queueSent";

	add_columns($arr_add_column);

	//Update DB with post author if not present
	###############################
	/*$result = $wpdb->get_results("SELECT ".$wpdb->posts.".ID AS post_id, post_author, display_name, post_content FROM ".$wpdb->posts." LEFT JOIN ".$wpdb->users." ON ".$wpdb->posts.".post_author = ".$wpdb->users.".ID WHERE post_type = 'mf_sms' AND post_author = '0'");

	$i = 0;

	foreach($result as $r)
	{
		$post_id = $r->post_id;
		$post_author = $r->post_author;
		$post_content = $r->post_content;

		if($post_author == 0 && $i < 10)
		{
			$intUserID = $wpdb->get_var($wpdb->prepare("SELECT userID FROM wp_group_message WHERE messageType = 'sms' AND messageText = %s", $post_content));

			if($intUserID > 0)
			{
				$query = $wpdb->prepare("UPDATE ".$wpdb->posts." SET post_author = '%d' WHERE ID = '%d'", $intUserID, $post_id);
				$wpdb->query($query);

				//do_log("SMS Update: ".$query);

				$i++;
			}
		}
	}*/
	###############################
}

function uninstall_group()
{
	mf_uninstall_plugin(array(
		'uploads' => 'mf_group',
		'options' => array('setting_emails_per_hour', 'setting_group_see_other_roles', 'setting_group_import'),
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