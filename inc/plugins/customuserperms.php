<?php
/**
 * Custom User Permissions 0.2.2

 * Copyright 2010 Matthew Rogowski

 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at

 ** http://www.apache.org/licenses/LICENSE-2.0

 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
**/

if(!defined("IN_MYBB"))
{
	header("HTTP/1.0 404 Not Found");
	exit;
}

// set the priority to -1000000 to make sure it is always the first plugin run - we'll be editing the core permission arrays; if it's loaded right at the start, all core code and plugins will use the custom permissions
$plugins->add_hook("global_start", "customuserperms_load", -1000000);
$plugins->add_hook("archive_start", "customuserperms_load", -1000000);
$plugins->add_hook("admin_user_menu", "customuserperms_admin_user_menu");
$plugins->add_hook("admin_user_action_handler", "customuserperms_admin_user_action_handler");
$plugins->add_hook("admin_user_permissions", "customuserperms_admin_user_permissions");

function customuserperms_info()
{
	return array(
		'name' => 'Custom User Permissions',
		'description' => 'Apply permissions to specific users instead of just usergroups.',
		'website' => 'http://mattrogowski.co.uk',
		'author' => 'MattRogowski',
		'authorsite' => 'http://mattrogowski.co.uk',
		'version' => '0.2.2',
		'compatibility' => '16*',
		'guid' => 'ddb7c7d4833f3ba2cb91c2bc1d8f4c73'
	);
}

function customuserperms_install()
{
	global $db, $customuserperms_uninstall_confirm_override;
	
	// this is so we override the confirmation when trying to uninstall, so we can just run the uninstall code
	$customuserperms_uninstall_confirm_override = true;
	customuserperms_uninstall();
	
	if(!$db->field_exists("hascustomperms", "users"))
	{
		$db->add_column("users", "hascustomperms", "INT (1) NOT NULL DEFAULT 0");
	}
	
	if(!$db->table_exists("customuserperms"))
	{
		$db->write_query("
			CREATE TABLE  " . TABLE_PREFIX . "customuserperms (
				`cupid` SMALLINT(5) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`uid` INT(10) NOT NULL ,
				`customperms` TEXT NOT NULL ,
				`active` INT(1) NOT NULL DEFAULT 0
			) ENGINE = MYISAM ;
		");
	}
	
	change_admin_permission("user", "customuserperms", 1);
}

function customuserperms_is_installed()
{
	global $db;
	
	return $db->table_exists("customuserperms");
}

function customuserperms_uninstall()
{
	global $mybb, $db, $customuserperms_uninstall_confirm_override;
	
	// this is a check to make sure we want to uninstall
	// if 'No' was chosen on the confirmation screen, redirect back to the plugins page
	if($mybb->input['no'])
	{
		admin_redirect("index.php?module=config-plugins");
	}
	else
	{
		// there's a post request so we submitted the form and selected yes
		// or the confirmation is being overridden by the installation function; this is for when customuserperms_uninstall() is called at the start of customuserperms_install(), we just want to execute the uninstall code at this point
		if($mybb->request_method == "post" || $customuserperms_uninstall_confirm_override === true || $mybb->input['action'] == "delete")
		{
			if($db->field_exists("hascustomperms", "users"))
			{
				$db->drop_column("users", "hascustomperms");
			}
			
			if($db->table_exists("customuserperms"))
			{
				$db->drop_table("customuserperms");
			}
		}
		// need to show the confirmation
		else
		{
			global $lang, $page;
			
			$lang->load("user_customuserperms");
			
			$query = $db->simple_select("customuserperms", "COUNT(*) AS custompermusers");
			$custompermusers = $db->fetch_field($query, "custompermusers");
			if($custompermusers > 0)
			{
				$lang->customuserperms_uninstall_warning .= " " . $lang->sprintf($lang->customuserperms_uninstall_warning_count, $custompermusers);
			}
			
			$page->output_confirm_action("index.php?module=config-plugins&action=deactivate&uninstall=1&plugin=customuserperms&my_post_key={$mybb->post_code}", $lang->customuserperms_uninstall_warning);
		}
	}
}

function customuserperms_activate()
{
	
}

function customuserperms_deactivate()
{
	
}

function customuserperms_load()
{
	global $mybb;
	
	if($mybb->user['hascustomperms'] != 1)
	{
		return;
	}
	
	global $db, $cache, $cached_forum_permissions, $cached_forum_permissions_permissions;
	
	$query = $db->simple_select("customuserperms", "customperms", "uid = '" . intval($mybb->user['uid']) . "' AND active = '1'");
	if($db->num_rows($query) != 1)
	{
		return;
	}
	$customperms = $db->fetch_field($query, "customperms");
	$customperms = unserialize($customperms);
	
	if(!empty($customperms['general']))
	{
		foreach($customperms['general'] as $perm => $value)
		{
			// if it's set to inherit the default usergroup setting, skip it
			if($value == -1)
			{
				continue;
			}
			// if this option has a numeric value, this is the option saying whether to use it or not, so skip this
			if(isset($customperms['general'][$perm . "_value"]))
			{
				continue;
			}
			// if this is something that also has a numerical value, check if the non numerical value (i.e. on/off option) isn't set to inherit
			if(substr($perm, -6) == "_value" && $customperms['general'][str_replace("_value", "", $perm)] == 1)
			{
				$perm = str_replace("_value", "", $perm);
			}
			$mybb->usergroup[$perm] = $value;
		}
	}
	if(!empty($customperms['forums']))
	{
		$gid = $mybb->user['usergroup'] . "," . $mybb->user['additionalgroups'];
		$forums = $cache->read("forums");
		
		// load the global forum permissions
		forum_permissions();
		// set the global forum permissions first
		if(array_key_exists(-1, $customperms['forums']))
		{
			foreach($forums as $forum => $info)
			{
				forum_permissions($forum);
				foreach($customperms['forums'][-1] as $perm => $value)
				{
					// update the global and specific permissions; if forum_permissions() is called in the default code with an fid, it'll use the second array; with no fid, it uses the first; permission needs to be updated in both
					$cached_forum_permissions[$gid][$forum][$perm] = $value;
					$cached_forum_permissions_permissions[$gid][$forum][$perm] = $value;
				}
			}
		}
		// go through each forum that has custom permissions set
		foreach($customperms['forums'] as $forum_id => $permissions)
		{
			if($forum_id == -1)
			{
				continue;
			}
			$forums_list = array($forum_id);
			foreach($forums as $fid => $info)
			{
				$parentlist = $info['parentlist'];
				if(strpos("," . $parentlist . ",", "," . $forum_id . ",") !== false && !in_array($fid, $forums_list))
				{
					$forums_list[] = $fid;
				}
			}
			
			foreach($forums_list as $forum)
			{
				// load the specific forum permissions
				forum_permissions($forum);
				// go through each permission
				foreach($permissions as $perm => $value)
				{
					// update the global and specific permissions; if forum_permissions() is called in the default code with an fid, it'll use the second array; with no fid, it uses the first; permission needs to be updated in both
					$cached_forum_permissions[$gid][$forum][$perm] = $value;
					$cached_forum_permissions_permissions[$gid][$forum][$perm] = $value;
				}
			}
		}
	}
}

function customuserperms_admin_user_menu($sub_menu)
{
	global $lang;
	
	$lang->load("user_customuserperms");
	
	$sub_menu[] = array("id" => "customuserperms", "title" => $lang->customuserperms, "link" => "index.php?module=user-customuserperms");
	
	return $sub_menu;
}

function customuserperms_admin_user_action_handler($actions)
{
	$actions['customuserperms'] = array(
		"active" => "customuserperms",
		"file" => "customuserperms.php"
	);
	
	return $actions;
}

function customuserperms_admin_user_permissions($admin_permissions)
{
	global $lang;
	
	$lang->load("user_customuserperms");
	
	$admin_permissions['customuserperms'] = $lang->can_manage_customuserperms;
	
	return $admin_permissions;
}
?>