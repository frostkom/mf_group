<?php

$query_xtra = "";

if(!IS_EDITOR)
{
	$query_xtra .= " AND post_author = '".get_current_user_id()."'";
}

$intGroupID = check_var('intGroupID');
$intMessageID = check_var('intMessageID');

if(isset($_REQUEST['btnMessageAbort']) && $intMessageID > 0 && wp_verify_nonce($_REQUEST['_wpnonce'], 'message_abort_'.$intMessageID))
{
	$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."group_queue WHERE messageID = '%d' AND queueSent = '0'", $intMessageID));

	$done_text = __("The message was aborted", 'lang_group');
}

echo "<div class='wrap'>
	<h2>".__("Sent", 'lang_group')."</h2>"
	.get_notification();

	$tbl_group = new mf_group_sent_table();

	$tbl_group->select_data(array(
		'select' => "messageID, messageType, messageFrom, messageName, messageText, messageAttachment, messageSchedule, messageCreated",
		//'debug' => true,
	));

	$tbl_group->do_display();

echo "</div>";