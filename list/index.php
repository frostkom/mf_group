<?php

$obj_group = new mf_group();
$obj_group->fetch_request();
echo $obj_group->save_data();

echo "<div class='wrap'>
	<h2>"
		.__("Group", 'lang_group')
		."<a href='?page=mf_group/create/index.php' class='add-new-h2'>".__("Add New", 'lang_group')."</a>"
	."</h2>"
	.get_notification();

	$tbl_group = new mf_group_table();

	$tbl_group->select_data(array(
		'select' => "ID, post_status, post_name, post_title, post_modified, post_author", //, COUNT(addressID) AS amount
		//'join' => " LEFT JOIN ".$wpdb->base_prefix."address2group ON ".$wpdb->posts.".ID = ".$wpdb->base_prefix."address2group.groupID",
	));

	$tbl_group->do_display();

echo "</div>";