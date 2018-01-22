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
	$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."group_queue WHERE messageID = '%d' AND queueSent = '0'", $intMessageID));

	$done_text = __("The message was aborted", 'lang_group');
}

echo "<div class='wrap'>
	<h2>".__("Sent", 'lang_group')."</h2>"
	.get_notification();

	$result = $wpdb->get_results($wpdb->prepare("SELECT messageID, messageType, messageFrom, messageName, messageText, messageAttachment, messageCreated FROM ".$wpdb->base_prefix."group_message WHERE groupID = '%d' ORDER BY messageCreated DESC", $intGroupID));

	echo "<table class='widefat striped'>";

		$arr_header[] = __("Type", 'lang_group');
		$arr_header[] = __("From", 'lang_group');
		$arr_header[] = __("Subject", 'lang_group');
		$arr_header[] = __("Sent", 'lang_group');
		$arr_header[] = __("Created", 'lang_group');

		echo show_table_header($arr_header)
		."<tbody>";

			if(count($result) == 0)
			{
				echo "<tr><td colspan='".count($arr_header)."'>".__("There is nothing to show", 'lang_group')."</td></tr>";
			}

			else
			{
				foreach($result as $r)
				{
					$intMessageID2 = $r->messageID;
					$strMessageType = $r->messageType;
					$strMessageFrom = $r->messageFrom;
					$strMessageName = $r->messageName;
					$strMessageText = $r->messageText;
					$strMessageAttachment = $r->messageAttachment;
					$dteMessageCreated = $r->messageCreated;

					$strMessageFromName = "";

					if(preg_match("/\|/", $strMessageFrom))
					{
						list($strMessageFromName, $strMessageFrom) = explode("|", $strMessageFrom);
					}

					$intMessageSent = $wpdb->get_var($wpdb->prepare("SELECT COUNT(queueID) FROM ".$wpdb->base_prefix."group_queue WHERE messageID = '%d' AND queueSent = '1'", $intMessageID2));
					$intMessageNotSent = $wpdb->get_var($wpdb->prepare("SELECT COUNT(queueID) FROM ".$wpdb->base_prefix."group_queue WHERE messageID = '%d' AND queueSent = '0'", $intMessageID2));

					$intMessageErrors = $wpdb->get_var($wpdb->prepare("SELECT COUNT(queueID) FROM ".$wpdb->base_prefix."group_queue WHERE messageID = '%d' AND queueReceived = '-1'", $intMessageID2));
					$intMessageReceived = $wpdb->get_var($wpdb->prepare("SELECT COUNT(queueID) FROM ".$wpdb->base_prefix."group_queue WHERE messageID = '%d' AND queueReceived = '1'", $intMessageID2));

					$class = "";

					if($intMessageSent == 0)
					{
						if($intMessageNotSent == 0)
						{
							$class = "red";
						}

						else
						{
							$class = "yellow";
						}
					}

					echo "<tr id='message_".$intMessageID2."'".($class != '' ? " class='".$class."'" : "").">
						<td>"
							.$strMessageType
							."<div class='row-actions'>
								<a href='?page=mf_group/sent/index.php&intGroupID=".$intGroupID."&intMessageID=".$intMessageID2."#message_".$intMessageID2."'><i class='fa fa-lg fa-eye' title='".__("View content", 'lang_group')."'></i></a> | 
								<a href='?page=mf_group/send/index.php&intGroupID=".$intGroupID."&intMessageID=".$intMessageID2."'><i class='fa fa-lg fa-users' title='".__("Send to group again", 'lang_group')."'></i></a> | 
								<a href='?page=mf_email/send/index.php&intGroupMessageID=".$intMessageID2."'><i class='fa fa-lg fa-envelope-o' title='".__("Send to e-mail", 'lang_group')."'></i></a>
							</div>"
						."</td>
						<td>"
							.$strMessageFrom;

							if($strMessageFromName != '' && $strMessageFromName != $strMessageFrom)
							{
								echo "<div class='row-actions'>"
									.$strMessageFromName
								."</div>";
							}

						echo "</td>
						<td>".$strMessageName."</td>
						<td>"
							.$intMessageSent." / ".($intMessageSent + $intMessageNotSent);

							if($intMessageNotSent > 0)
							{
								echo "<div class='row-actions'>
									<a href='".wp_nonce_url("?page=mf_group/sent/index.php&btnMessageAbort&intGroupID=".$intGroupID."&intMessageID=".$intMessageID2, 'message_abort_'.$intMessageID2)."'>".__("Abort", 'lang_group')."</a>
								</div>";
							}

							else if($intMessageErrors > 0 || $intMessageReceived > 0)
							{
								echo "<div class='row-actions'>";

									if($intMessageErrors > 0)
									{
										echo mf_format_number($intMessageErrors / $intMessageSent * 100, 1)."% ".__("Errors", 'lang_group');
									}

									if($intMessageReceived > 0)
									{
										echo ($intMessageErrors > 0 ? " | " : "")."<i class='fa fa-check green'></i> ".$intMessageReceived." ".__("Read", 'lang_group');
									}

								echo "</div>";
							}

						echo "</td>
						<td>".format_date($dteMessageCreated)."</td>
					</tr>";

					if($intMessageID == $intMessageID2)
					{
						$strMessageText = stripslashes(apply_filters('the_content', $strMessageText));

						echo "<tr><td colspan='".count($arr_header)."'>".$strMessageText."</td></tr>";

						if($strMessageAttachment != '')
						{
							echo "<tr><td colspan='".count($arr_header)."'>".get_media_button(array('name' => 'strMessageAttachment', 'value' => $strMessageAttachment, 'show_add_button' => false))."</td></tr>";
						}
					}
				}
			}

		echo "</tbody>
	</table>
</div>";