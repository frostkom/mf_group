<?php

$obj_group = new mf_group(array('type' => 'form'));
$obj_group->fetch_request();
echo $obj_group->save_data();

echo "<div class='wrap'>
	<h2>".__("Send Message", $obj_group->lang_key)."</h2>"
	.get_notification()
	."<div id='poststuff'>
		<form action='#' method='post' class='mf_form mf_settings'>
			<div id='post-body' class='columns-2'>
				<div id='post-body-content'>
					<div class='postbox'>
						<h3 class='hndle'>".__("Message", $obj_group->lang_key)."</h3>
						<div class='inside'>";

							switch($obj_group->message_type)
							{
								case 'email':
									if(is_plugin_active("mf_email/index.php"))
									{
										if(!isset($obj_email))
										{
											$obj_email = new mf_email();
										}

										$arr_data_from = $obj_email->get_from_for_select(array('index' => 'address'));
									}

									else
									{
										$user_data = get_userdata(get_current_user_id());

										$user_name = $user_data->display_name;
										$user_email = $user_data->user_email;
										$admin_name = get_bloginfo('name');
										$admin_email = get_bloginfo('admin_email');

										$arr_data_from = array();
										$arr_data_from[''] = "-- ".__("Choose Here", $obj_group->lang_key)." --";

										if($user_email != '')
										{
											$arr_data_from[$user_name."|".$user_email] = $user_name." (".$user_email.")";
										}

										if($admin_email != '' && $admin_email != $user_email)
										{
											$arr_data_from[$admin_name."|".$admin_email] = $admin_name." (".$admin_email.")";
										}
									}

									echo "<div class='flex_flow'>
										<div>"
											.show_select(array('data' => $arr_data_from, 'name' => 'strMessageFrom', 'text' => __("From", $obj_group->lang_key), 'value' => $obj_group->message_from, 'required' => true))
											.show_textfield(array('name' => 'strMessageName', 'text' => __("Subject", $obj_group->lang_key), 'value' => $obj_group->message_name, 'required' => true, 'maxlength' => 200))
										."</div>"
										.show_select(array('data' => $obj_group->get_for_select(array('add_choose_here' => false)), 'name' => 'arrGroupID[]', 'text' => __("To", $obj_group->lang_key), 'value' => $obj_group->arr_group_id, 'maxsize' => 6, 'required' => true))
									."</div>"
									.show_wp_editor(array('name' => 'strMessageText', 'value' => $obj_group->message_text));
								break;

								default:
									$result = apply_filters('get_group_message_form_fields', array(
										'type' => $obj_group->message_type,
										'from_value' => $obj_group->message_from,
										'to_select' => $obj_group->get_for_select(array('add_choose_here' => false)),
										'to_value' => $obj_group->arr_group_id,
										'message' => $obj_group->message_text,
										'html' => "",
									));

									if($result['html'] != '')
									{
										echo $result['html'];
									}
								break;
							}

						echo "</div>
					</div>
				</div>
				<div id='postbox-container-1'>
					<div class='postbox'>"
						//."<h3 class='hndle'>".__("Send", $obj_group->lang_key)."</h3>"
						."<div class='inside'>"
							.show_button(array('name' => 'btnGroupSend', 'text' => __("Send", $obj_group->lang_key)));

							$result = apply_filters('get_group_message_send_fields', array(
								'type' => $obj_group->message_type,
								'html' => "",
							));

							if($result['html'] != '')
							{
								echo $result['html'];
							}

							echo "<div class='flex_flow'>"
								.show_textfield(array('type' => 'date', 'name' => 'dteMessageScheduleDate', 'text' => __("Schedule", $obj_group->lang_key), 'value' => $obj_group->message_schedule_date, 'placeholder' => date("Y-m-d")))
								.show_textfield(array('type' => 'time', 'name' => 'dteMessageScheduleTime', 'text' => "&nbsp;", 'value' => $obj_group->message_schedule_time, 'placeholder' => date("H:i")))
							."</div>
							<p class='description'>".__("Choose date and time to send the message", $obj_group->lang_key)."</p>"
							.wp_nonce_field('group_send_'.$obj_group->message_type, '_wpnonce_group_send', true, false)
							.input_hidden(array('name' => 'type', 'value' => $obj_group->message_type))
						."</div>
					</div>";

					if($obj_group->message_type == "email")
					{
						$arr_data_source = array();
						get_post_children(array('add_choose_here' => true), $arr_data_source);

						echo "<div class='postbox'>
							<h3 class='hndle'>".__("Advanced", $obj_group->lang_key)."</h3>
							<div class='inside'>";

								echo show_select(array('data' => $arr_data_source, 'name' => 'intEmailTextSource', 'text' => __("Text Source", $obj_group->lang_key), 'xtra' => "rel='submit_change' class='is_disabled' disabled"))
								.get_media_button(array('name' => 'strMessageAttachment', 'value' => $obj_group->message_attachment));

								if($obj_group->message_text == '' || $obj_group->message_text != '' && !preg_match("/\[view_in_browser_link\]/", $obj_group->message_text))
								{
									echo show_button(array('name' => 'btnGroupAddViewInBrowser', 'text' => __("Add Link to View in Browser", $obj_group->lang_key), 'class' => "button"));

									$shortcode = $obj_group->get_add_view_in_browser_code();

									echo show_textfield(array('text' => __("Shortcode", $obj_group->lang_key), 'value' => $shortcode, 'xtra_class' => "display_on_hover", 'xtra' => "readonly onclick='this.select()'"));
								}

								if($obj_group->message_text == '' || $obj_group->message_text != '' && !preg_match("/\[unsubscribe_link\]/", $obj_group->message_text))
								{
									echo show_button(array('name' => 'btnGroupAddUnsubscribe', 'text' => __("Add Unsubscribe Link", $obj_group->lang_key), 'class' => "button"));

									$shortcode = $obj_group->get_unsubscribe_code();

									echo show_textfield(array('text' => __("Shortcode", $obj_group->lang_key), 'value' => $shortcode, 'xtra_class' => "display_on_hover", 'xtra' => "readonly onclick='this.select()'"));
								}

							echo "</div>
						</div>";
					}

				echo "</div>
			<div>
		</form>
	</div>
</div>";