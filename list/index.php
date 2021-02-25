<?php

$obj_group = new mf_group(array('type' => 'table'));
$obj_group->fetch_request();
echo $obj_group->save_data();

echo "<div class='wrap'>
	<h2>"
		.__("Group", $obj_group->lang_key)
		."<a href='".admin_url("admin.php?page=mf_group/create/index.php")."' class='add-new-h2'>".__("Add New", $obj_group->lang_key)."</a>"
	."</h2>"
	.get_notification();

	$tbl_group = new mf_group_table(array(
		'remember_search' => true,
	));

	$tbl_group->select_data(array(
		'select' => "ID, post_status, post_name, post_title, post_modified, post_author",
	));

	$tbl_group->do_display();

echo "</div>";