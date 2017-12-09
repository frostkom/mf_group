<?php

if(!defined('ABSPATH'))
{
	header('Content-Type: application/json');

	$folder = str_replace("/wp-content/plugins/mf_group/include", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
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