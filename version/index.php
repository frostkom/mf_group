<?php

$obj_group = new mf_group(array('type' => 'version'));
$obj_group->fetch_request();
//echo $obj_group->save_data();

echo "<div class='wrap'>
	<h2>".__("History", 'lang_group')."</h2>"
	.get_notification()
	."<div id='poststuff'>";

		$arr_data_groups = $obj_group->get_for_select(array('include_amount' => false));

		if(count($arr_data_groups) > 1)
		{
			echo "<div id='post-body' class='columns-2'>
				<div id='post-body-content'>
					<div class='postbox'>
						<h3 class='hndle'><span>".__("Versions", 'lang_group')."</span></h3>
						<div class='inside'>";

							if($obj_group->id > 0)
							{
								$arr_data_months = $obj_group->get_version_months_for_select();

								if(!isset($obj_address))
								{
									$obj_address = new mf_address();
								}

								$query_where = "";

								if($obj_group->group_month != 'all' && $obj_group->group_month != '')
								{
									$query_where = " AND versionCreated LIKE '".esc_sql($obj_group->group_month)."%'";
								}

								$result = $wpdb->get_results($wpdb->prepare("SELECT addressID, versionType, versionCreated, userID FROM ".$wpdb->prefix."group_version WHERE groupID = '%d'".$query_where." GROUP BY addressID, versionType, versionCreated, userID ORDER BY versionCreated DESC, versionID DESC", $obj_group->id));

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

											$strAddressName = $content_class = "";

											if($intAddressID > 0)
											{
												$strAddressName = $obj_address->get_name(array('address_id' => $intAddressID));

												if($strAddressName == __("unknown", 'lang_group'))
												{
													$intAddressID = 0;

													$content_class .= " deleted";
												}
											}

											$icon_link = "";

											switch($strVersionType)
											{
												case 'accept':
													$icon_class = "fas fa-user-check fa-2x green";
													$icon_title = __("The address was accepted to the group", 'lang_group');
												break;

												case 'acceptance_sent':
													$icon_class = "fas fa-paper-plane fa-3x blue";
													$icon_title = __("The address was sent a message to be accepted to the group", 'lang_group');
												break;

												case 'add':
													if($obj_group->has_address(array('address_id' => $intAddressID, 'group_id' => $obj_group->id)) == false)
													{
														if($intAddressID > 0)
														{
															$icon_link = wp_nonce_url(admin_url("admin.php?page=mf_address/list/index.php&intAddressID=".$intAddressID."&intGroupID=".$obj_group->id."&btnAddressAdd"), 'address_add_'.$intAddressID.'_'.$obj_group->id, '_wpnonce_address_add');

															$icon_class = "fa fa-plus-circle fa-3x blue";
															$icon_title = __("The address was added to the group but is not part of the group anymore. Add again?", 'lang_group');
														}

														else
														{
															$icon_class = "fa fa-plus-circle fa-3x grey";
															$icon_title = __("The address was added to the group but is not part of the group anymore", 'lang_group');
														}
													}

													else
													{
														$icon_class = "fa fa-plus-circle fa-3x green";
														$icon_title = __("The address was added to the group", 'lang_group');
													}
												break;

												case 'merge':
													$icon_class = "fas fa-share-alt fa-3x blue";
													$icon_title = __("The address was merged in the group", 'lang_group');
												break;

												case 'remove':
													$icon_class = "fa fa-times fa-3x red";
													$icon_title = __("The address was removed from the group", 'lang_group');
												break;

												case 'remove_all':
													$icon_class = "fas fa-ban fa-3x";
													$icon_title = __("All addresses were removed from the group", 'lang_group');
												break;

												case 'unsubscribe':
													$icon_class = "fas fa-user-slash fa-3x red";
													$icon_title = __("The address was unsubscribed from the group", 'lang_group');
												break;

												default:
													$icon_class = "fa fa-question-circle fa-3x blue";
													$icon_title = __("An unknown status was saved", 'lang_group');
												break;
											}

											if($intUserID > 0)
											{
												$user_name = get_user_info(array('id' => $intUserID));
											}

											else
											{
												$user_name = "<em>(".__("unknown", 'lang_group').")</em>";
											}

											echo "<li class='".$strVersionType."'>";

												if($icon_link != '')
												{
													echo "<a href='".$icon_link."'>";
												}

													echo "<i class='".$icon_class."' title='".$icon_title."'></i>";

												if($icon_link != '')
												{
													echo "</a>";
												}

												echo "<div class='content".$content_class."'>
													<h3>"
														.$strAddressName;

														if($intAddressID > 0)
														{
															echo "<a href='".admin_url("admin.php?page=mf_address/create/index.php&intAddressID=".$intAddressID)."'><i class='fa fa-wrench'></i></a>"
															."<a href='".admin_url("admin.php?page=mf_address/list/index.php&s=".$strAddressName)."'><i class='fa fa-search'></i></a>";
														}

													echo "</h3>"
													.$time_this
													."<span class='grey'>".sprintf(__("by %s", 'lang_group'), $user_name)."</span>" // <em>#".$intAddressID."</em>
												."</div>
												<div>&nbsp;</div>
											</li>";

											$date_temp = $date_this;
										}

									echo "</ul>";
								}

								else
								{
									echo "<em>".__("I could not find any old versions of this group", 'lang_group')."</em>";
								}
							}

							else
							{
								echo "<em>".__("Choose a group to the right and all the previous versions will magically appear here", 'lang_group')."</em>";
							}

						echo "</div>
					</div>
				</div>
				<div id='postbox-container-1'>
					<div class='postbox'>
						<h3 class='hndle'><span>".__("Groups", 'lang_group')."</span></h3>
						<div class='inside'>
							<form method='get' action='' class='mf_form mf_settings'>";

								$group_label = __("Group", 'lang_group');

								if($obj_group->id > 0)
								{
									$group_label .= "<span>
										<a href='".admin_url("post.php?post=".$obj_group->id."&action=edit")."' title='".__("Edit", 'lang_group')."'><i class='fa fa-wrench fa-lg'></i></a> "
										."<a href='".admin_url("admin.php?page=mf_address/list/index.php&intGroupID=".$obj_group->id."&strFilterIsMember=yes&strFilterAccepted=yes&strFilterUnsubscribed=no")."' title='".__("Add or remove", 'lang_group')."'><i class='fas fa-tasks fa-lg'></i></a>
									</span>";
								}

								echo show_select(array('data' => $arr_data_groups, 'name' => 'intGroupID', 'text' => $group_label, 'value' => $obj_group->id, 'xtra' => " rel='submit_change'"));

								if($obj_group->id > 0)
								{
									echo show_select(array('data' => $arr_data_months, 'name' => 'strGroupMonth', 'text' => __("Month", 'lang_group'), 'value' => $obj_group->group_month, 'xtra' => " rel='submit_change'"));
								}

								echo show_button(array('text' => __("Change", 'lang_group')))
								.input_hidden(array('name' => 'page', 'value' => check_var('page')))
							."</form>
						</div>
					</div>
				</div>
			</div>";
		}

	echo "</div>
</div>";