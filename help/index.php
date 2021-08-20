<?php

$obj_group = new mf_group();

echo "<div class='wrap'>
	<h2>".__("Help", 'lang_group')."</h2>
	<div id='poststuff'>
		<div id='post-body' class='columns-2'>
			<div id='post-body-content'>
				<div class='postbox'>
					<h3 class='hndle'><span>".__("Create a group", 'lang_group')."</span></h3>
					<div class='inside'>
						<ol>
							<li>".sprintf(__("Go to %sGroups%s -> %sAdd New%s", 'lang_group'), "<a href='".admin_url("admin.php?page=mf_group/list/index.php")."'>", "</a>", "<a href='".admin_url("admin.php?page=mf_group/create/index.php")."'>", "</a>")."</li>
							<li>".__("Enter the name of the group and press Save", 'lang_group')."</li>
						</ol>
					</div>
				</div>
				<div class='postbox'>
					<h3 class='hndle'><span>".__("Add addresses to a group", 'lang_group')."</span></h3>
					<div class='inside'>
						<ol>
							<li>".sprintf(__("Go to %sGroups%s", 'lang_group'), "<a href='".admin_url("admin.php?page=mf_group/list/index.php")."'>", "</a>")."</li>
							<li>".__("Press the number or the list icon in the Amount column", 'lang_group')."</li>
							<li>".__("Search for the address that you want to add or remove from the group", 'lang_group')."</li>
							<li>".sprintf(__("In the column %s it appears a %s if the address is already in the group and %s if it is not", 'lang_group'), "<i class='fa fa-plus-square'></i> / <i class='fa fa-minus-square'></i>", "<i class='fa fa-minus-square'></i>", "<i class='fa fa-plus-square'></i>")."</li>
							<li>".sprintf(__("Press the %s icon if you want to add the address", 'lang_group'), "<i class='fa fa-plus-square'></i>")."</li>
						</ol>
					</div>
				</div>
				<div class='postbox'>
					<h3 class='hndle'><span>".__("Remove from the Address Book", 'lang_group')."</span></h3>
					<div class='inside'>
						<ol>
							<li>".sprintf(__("Go to %sAddress Book%s", 'lang_group'), "<a href='".admin_url("admin.php?page=mf_address/list/index.php")."'>", "</a>")."</li>
							<li>".__("Search for the address that you want to remove", 'lang_group')."</li>
							<li>".__("Press Delete inte the Name column and it will be moved to the Trash and removed from any groups that it has been part of", 'lang_group')."</li>
						</ol>
					</div>
				</div>
			</div>";

			/*echo "<div id='postbox-container-1'>
				<div class='postbox'>
					<h3 class='hndle'><span></span></h3>
					<div class='inside'></div>
				</div>
			</div>";*/

		echo "</div>
	</div>
</div>";