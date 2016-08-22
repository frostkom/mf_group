<?php

$wp_root = '../../../..';

if(file_exists($wp_root.'/wp-load.php'))
{
	require_once($wp_root.'/wp-load.php');
}

else
{
	require_once($wp_root.'/wp-config.php');
}

require_once("classes.php");
require_once("functions.php");

$json_output = array();

$type = check_var('type', 'char');

if(get_current_user_id() > 0)
{
	if($type == "table_search")
	{
		$strSearch = check_var('s', 'char');

		$obj_group = new mf_group();

		$result = $obj_group->get_groups(array('where' => " AND (post_title LIKE '%".$strSearch."%')", 'limit' => 0, 'amount' => 10));

		foreach($result as $r)
		{
			$json_output[] = $r->post_title;
		}
	}
}

echo json_encode($json_output);