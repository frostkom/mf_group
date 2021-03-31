<?php

$obj_group = new mf_group();
//$obj_group->fetch_request();
//echo $obj_group->save_data();

echo "<div class='wrap'>
	<h2>".__("History", $obj_group->lang_key)."</h2>"
	.get_notification()
	."<div id='poststuff'>";

		$arr_data = $obj_group->get_for_select();

		if(count($arr_data) > 1)
		{
			echo "<div id='post-body' class='columns-2'>
				<div id='post-body-content'>
					<div class='postbox'>
						<h3 class='hndle'><span>".__("Versions", $obj_group->lang_key)."</span></h3>
						<div class='inside'>";

							if($obj_group->id > 0)
							{
								if(!isset($obj_address))
								{
									$obj_address = new mf_address();
								}

								$result = $wpdb->get_results($wpdb->prepare("SELECT addressID, versionType, versionCreated, userID FROM ".$wpdb->prefix."group_version WHERE groupID = '%d' GROUP BY addressID, versionType, versionCreated, userID ORDER BY versionCreated DESC, versionID DESC", $obj_group->id));

								if(count($result) > 0)
								{
									echo "<ul class='timeline'>";

										$year_temp = $month_temp = $date_temp = "";

										foreach($result as $r)
										{
											$intAddressID = $r->addressID;
											$strVersionType = $r->versionType;
											$dteVersionCreated = $r->versionCreated;
											$intUserID = $r->userID;

											$date_this = date("Y-m-d", strtotime($dteVersionCreated));
											$time_this = date("H:i:s", strtotime($dteVersionCreated));

											if($date_this != $date_temp)
											{
												echo "<li><h2>".$date_this."</h2></li>";
											}

											$strAddressName = "";

											if($intAddressID > 0)
											{
												$result_address = $wpdb->get_results($wpdb->prepare("SELECT addressFirstName, addressSurName, addressEmail FROM ".get_address_table_prefix()."address WHERE addressID = '%d'", $intAddressID));

												foreach($result_address as $r)
												{
													$strAddressFirstName = $r->addressFirstName;
													$strAddressSurName = $r->addressSurName;
													$emlAddressEmail = $r->addressEmail;

													if($strAddressFirstName != '' || $strAddressSurName != '')
													{
														$strAddressName = $strAddressFirstName;

														if($strAddressSurName != '')
														{
															$strAddressName .= " ".$strAddressSurName;
														}
													}

													else
													{
														$strAddressName = $emlAddressEmail;
													}
												}
											}

											switch($strVersionType)
											{
												case 'accept':
													$icon_class = "fas fa-user-check fa-2x green";
													$icon_title = __("The address was accepted to the group", $obj_group->lang_key);
												break;

												case 'acceptance_sent':
													$icon_class = "fas fa-paper-plane fa-3x blue";
													$icon_title = __("The address was sent a message to be accepted to the group", $obj_group->lang_key);
												break;

												case 'add':
													$icon_class = "fa fa-plus-circle fa-3x green";
													$icon_title = __("The address was added to the group", $obj_group->lang_key);
												break;

												case 'merge':
													$icon_class = "fas fa-share-alt fa-3x blue";
													$icon_title = __("The address was merged in the group", $obj_group->lang_key);
												break;

												case 'remove':
													$icon_class = "fa fa-times fa-3x red";
													$icon_title = __("The address was removed from the group", $obj_group->lang_key);
												break;

												case 'remove_all':
													$icon_class = "fas fa-ban fa-3x";
													$icon_title = __("All addresses were removed from the group", $obj_group->lang_key);
												break;

												case 'unsubscribe':
													$icon_class = "fas fa-user-slash fa-3x";
													$icon_title = __("The address was unsubscribed from the group", $obj_group->lang_key);
												break;

												//$icon_class = "fa fa-eye yellow";
												//$icon_class = "fa fa-check green";

												default:
													$icon_class = "fa fa-question-circle fa-3x blue";
													$icon_title = __("An unknown status was saved", $obj_group->lang_key);
												break;
											}

											if($intUserID > 0)
											{
												$user_name = get_user_info(array('id' => $intUserID));
											}

											else
											{
												$user_name = "<em>(".__("unknown", $obj_group->lang_key).")</em>";
											}

											//$post_edit_url = admin_url("admin.php?page=mf_address/create/index.php&intAddressID=".$intAddressID."&intGroupID=".$obj_group->id);
											$post_edit_url = "#";

											echo "<li class='".$strVersionType."'>
												<i class='".$icon_class."' title='".$icon_title."'></i>
												<div class='content'>
													<h3><a href='".$post_edit_url."'>".$strAddressName."</a></h3>"
													.$time_this
													."<span class='grey'>".$user_name." <em>#".$intAddressID."</em></span>
												</div>
												<div>&nbsp;</div>
											</li>";

											$date_temp = $date_this;
										}

									echo "</ul>";
								}

								else
								{
									echo "<em>".__("I could not find any old versions of this group", $obj_group->lang_key)."</em>";
								}
							}

							else
							{
								echo "<em>".__("Choose a group to the right and all the previous versions will magically appear here", $obj_group->lang_key)."</em>";
							}

						echo "</div>
					</div>
				</div>
				<div id='postbox-container-1'>
					<div class='postbox'>
						<h3 class='hndle'><span>".__("Groups", $obj_group->lang_key)."</span></h3>
						<div class='inside'>
							<form method='get' action='' class='mf_form mf_settings'>"
								.show_select(array('data' => $arr_data, 'name' => 'intGroupID', 'text' => __("Group", $obj_group->lang_key), 'value' => $obj_group->id, 'xtra' => " rel='submit_change'"))
								.show_submit(array('text' => __("Change", $obj_group->lang_key)))
								.input_hidden(array('name' => 'page', 'value' => check_var('page')))
							."</form>
						</div>
					</div>
				</div>
			</div>";
		}

	echo "</div>
</div>";