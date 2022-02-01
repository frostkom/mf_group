<?php

if(!defined('ABSPATH'))
{
	header('Content-Type: application/json');

	$folder = str_replace("/wp-content/plugins/mf_group/include/api", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

if(!isset($obj_group))
{
	$obj_group = new mf_group();
}

$json_output = array();

$type = check_var('type', 'char');

switch($type)
{
	case 'sync':
		$remote_server_ip = get_current_visitor_ip();
		$arr_allowed_server_ips = array(get_option('setting_server_ip'));

		if($remote_server_ip != '' && !in_array($remote_server_ip, $arr_allowed_server_ips))
		{
			header("Status: 503 Unknown IP-address");

			if(count($arr_allowed_server_ips) > 0)
			{
				$log_message = sprintf(__("The IP address (%s) are not amongst the accepted ones", 'lang_group'), $remote_server_ip);
			}

			else
			{
				$log_message = __("There are no accepted IP addresses", 'lang_group');
			}

			$json_output['success'] = false;
			$json_output['message'] = $log_message;
		}

		else
		{
			$intGroupID = check_var('group_id');

			$result = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".get_address_table_prefix()."address INNER JOIN ".$wpdb->prefix."address2group USING (addressID) WHERE groupID = '%d' AND addressPublic = '1' AND addressDeleted = '0' GROUP BY addressID ORDER BY addressPublic ASC, addressSurName ASC, addressFirstName ASC", $intGroupID));

			if($wpdb->num_rows > 0)
			{
				$json_output['success'] = true;
				$json_output['data'] = array();

				foreach($result as $r)
				{
					$json_output['data'][] = array(
						'addressBirthDate' => $r->addressBirthDate,
						'addressFirstName' => $r->addressFirstName,
						'addressSurName' => $r->addressSurName,
						'addressAddress' => $r->addressAddress,
						'addressCo' => $r->addressCo,
						'addressZipCode' => $r->addressZipCode,
						'addressCity' => $r->addressCity,
						'addressCountry' => $r->addressCountry,
						'addressTelNo' => $r->addressTelNo,
						'addressCellNo' => $r->addressCellNo,
						'addressWorkNo' => $r->addressWorkNo,
						'addressEmail' => $r->addressEmail,
					);
				}
			}

			else
			{
				$json_output['success'] = true;
				$json_output['message'] = __("There are no addresses in the group that you requested", 'lang_group');
			}
		}
	break;

	case 'table_search':
		if(is_user_logged_in())
		{
			$strSearch = check_var('s', 'char');

			$result = $obj_group->get_groups(array('where' => " AND (post_title LIKE '%".esc_sql($strSearch)."%')", 'limit' => 0, 'amount' => 10));

			if($wpdb->num_rows > 0)
			{
				foreach($result as $r)
				{
					$json_output[] = $r->post_title;
				}
			}
		}
	break;
}

echo json_encode($json_output);