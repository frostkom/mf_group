<?php

$obj_group = new mf_group(array('type' => 'message'));
$obj_group->fetch_request();
echo $obj_group->save_data();

echo "<div class='wrap'>
	<h2>".__("Message", 'lang_group')."</h2>"
	.get_notification();

	$tbl_group = new mf_group_message_table();

	$tbl_group->select_data(array(
		'select' => "addressID, addressFirstName, addressSurName, messageType, addressEmail, addressCellNo, queueSent, queueStatus, queueStatusMessage, queueSentTime, queueViewed",
		'join' => " INNER JOIN ".$wpdb->prefix."address USING (addressID) INNER JOIN ".$wpdb->prefix."group_message ON ".$wpdb->prefix."group_queue.messageID = ".$wpdb->prefix."group_message.messageID",
		//'debug' => true,
	));

	$tbl_group->do_display();

echo "</div>";