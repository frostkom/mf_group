<?php

$obj_group = new mf_group(array('type' => 'sent'));
$obj_group->fetch_request();
echo $obj_group->save_data();

echo "<div class='wrap'>
	<h2>".__("Sent", 'lang_group')."</h2>"
	.get_notification();

	$tbl_group = new mf_group_sent_table();

	$tbl_group->select_data(array(
		'select' => "messageID, messageType, messageFrom, messageName, messageText, messageAttachment, messageSchedule, messageCreated, userID, messageDeleted",
	));

	$tbl_group->do_display();

echo "</div>";