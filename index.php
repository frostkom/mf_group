<?php
/*
Plugin Name: MF Group
Plugin URI: https://github.com/frostkom/mf_group
Description: 
Version: 4.2.12
Author: Martin Fors
Author URI: http://frostkom.se
Text Domain: lang_group
Domain Path: /lang

GitHub Plugin URI: frostkom/mf_group
*/

include_once("include/classes.php");
include_once("include/functions.php");

add_action('cron_base', 'activate_group', mt_rand(1, 10));
add_action('cron_base', 'cron_group', mt_rand(1, 10));

add_action('init', 'init_group', 1);
add_action('widgets_init', 'widgets_group');

if(is_admin())
{
	register_activation_hook(__FILE__, 'activate_group');
	register_deactivation_hook(__FILE__, 'deactivate_group');
	register_uninstall_hook(__FILE__, 'uninstall_group');

	add_action('admin_init', 'settings_group');
	add_action('admin_menu', 'menu_group');
	add_action('admin_notices', 'notices_group');
	add_action('delete_post', 'delete_group');
	add_action('deleted_user', 'deleted_user_group');

	add_filter('count_shortcode_button', 'count_shortcode_button_group');
	add_filter('get_shortcode_output', 'get_shortcode_output_group');
	add_filter('get_shortcode_list', 'get_shortcode_list_group');
}

add_shortcode('mf_group', 'shortcode_group');

add_filter('single_template', 'custom_templates_group');

load_plugin_textdomain('lang_group', false, dirname(plugin_basename(__FILE__)).'/lang/');

function activate_group()
{
	global $wpdb;

	require_plugin("mf_address/index.php", "MF Address");

	$default_charset = DB_CHARSET != '' ? DB_CHARSET : "utf8";

	$arr_add_column = $arr_add_index = array();

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
		PRIMARY KEY (messageID),
		KEY groupID (groupID)
	) DEFAULT CHARSET=".$default_charset);

	$arr_add_column[$wpdb->base_prefix."group_message"]['messageAttachment'] = "ALTER TABLE [table] ADD [column] TEXT AFTER messageText";

	$arr_add_index[$wpdb->base_prefix."group_message"] = array(
		'groupID' => "ALTER TABLE [table] ADD INDEX [column] ([column])",
	);

	$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."group_queue (
		queueID INT unsigned NOT NULL AUTO_INCREMENT,
		addressID INT unsigned NOT NULL DEFAULT '0',
		messageID INT unsigned NOT NULL DEFAULT '0',
		queueSent ENUM('0','1') NOT NULL DEFAULT '0',
		queueReceived ENUM('-1', '0','1') NOT NULL DEFAULT '0',
		queueCreated DATETIME NOT NULL,
		queueSentTime DATETIME NOT NULL,
		PRIMARY KEY (queueID),
		KEY messageID (messageID),
		KEY queueSent (queueSent)
	) DEFAULT CHARSET=".$default_charset);

	$arr_add_column[$wpdb->base_prefix."group_queue"]['queueReceived'] = "ALTER TABLE [table] ADD [column] ENUM('-1', '0','1') NOT NULL DEFAULT '0' AFTER queueSent";

	$arr_add_index[$wpdb->base_prefix."group_queue"] = array(
		'messageID' => "ALTER TABLE [table] ADD INDEX [column] ([column])",
		'queueSent' => "ALTER TABLE [table] ADD INDEX [column] ([column])",
	);

	$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."address2group (
		addressID INT unsigned NOT NULL,
		groupID INT unsigned NOT NULL,
		groupAccepted ENUM('0', '1') NOT NULL DEFAULT '1',
		groupUnsubscribed ENUM('0', '1') NOT NULL DEFAULT '0',
		KEY addressID (addressID),
		KEY groupID (groupID)
	) DEFAULT CHARSET=".$default_charset);

	$arr_add_column[$wpdb->base_prefix."address2group"] = array(
		'groupUnsubscribed' => "ALTER TABLE [table] ADD [column] ENUM('0', '1') NOT NULL DEFAULT '0' AFTER groupID",
		'groupAccepted' => "ALTER TABLE [table] ADD [column] ENUM('0', '1') NOT NULL DEFAULT '1' AFTER groupUnsubscribed",
	);

	$arr_add_index[$wpdb->base_prefix."address2group"] = array(
		'addressID' => "ALTER TABLE [table] ADD INDEX [column] ([column])",
		'groupID' => "ALTER TABLE [table] ADD INDEX [column] ([column])",
	);

	add_columns($arr_add_column);
	add_index($arr_add_index);
}

function deactivate_group()
{
	mf_uninstall_plugin(array(
		'options' => array('setting_group_acceptance_email'),
	));
}

function uninstall_group()
{
	mf_uninstall_plugin(array(
		'uploads' => 'mf_group',
		'options' => array('setting_emails_per_hour', 'setting_group_see_other_roles', 'setting_group_import'),
		'post_types' => array('mf_group'),
		'tables' => array('group_message', 'group_queue', 'address2group'),
	));
}

function shortcode_group($atts)
{
	extract(shortcode_atts(array(
		'id' => ''
	), $atts));

	return show_group_registration_form($id);
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