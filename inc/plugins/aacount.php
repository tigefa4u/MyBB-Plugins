<?php
/**
 * Awaiting Activation Count 1.6.4

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
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("global_start", "aacount");
$plugins->add_hook("member_do_register_end", "aacount_register");
$plugins->add_hook("admin_user_users_inline", "aacount_mass_activate");
$plugins->add_hook("admin_user_users_edit", "aacount_edit_usergroup");
$plugins->add_hook("member_activate_accountactivated", "aacount_recount_awaitingactivation");
$plugins->add_hook("admin_user_users_coppa_activate_commit", "aacount_recount_awaitingactivation");
$plugins->add_hook("admin_user_users_delete_commit", "aacount_recount_awaitingactivation");
$plugins->add_hook("admin_user_users_merge_commit", "aacount_recount_awaitingactivation");
$plugins->add_hook("admin_user_users_add_commit", "aacount_recount_awaitingactivation");
$plugins->add_hook("admin_tools_cache_start", "aacount_edit_datacache_class");
$plugins->add_hook("admin_tools_cache_rebuild", "aacount_edit_datacache_class");

function aacount_info()
{
	return array(
		"name" => "Awaiting Activation Count",
		"description" => "Shows the number of users awaiting activation.",
		"website" => "http://mattrogowski.co.uk",
		"author" => "MattRogowski",
		"authorsite" => "http://mattrogowski.co.uk",
		"version" => "1.6.4",
		"compatibility" => "16*",
		"guid" => "6abe584301586273d034ab20ef7474f9"
	);
}

function aacount_activate()
{
	global $db;
	
	require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
	
	aacount_deactivate();
	
	$templates = array();
	$templates[] = array(
		"title" => "aacount",
		"template" => "<table border=\"0\" cellspacing=\"1\" cellpadding=\"4\" class=\"tborder\">
	<tr>
		<td class=\"trow1\" align=\"right\"><span class=\"smalltext\"><a href=\"{\$mybb->settings[\'bburl\']}/{\$config[\'admin_dir\']}/index.php?module=user/users&action=search&results=1&conditions=a%3A1%3A%7Bs%3A9%3A%22usergroup%22%3Bs%3A1%3A%225%22%3B%7D&from=home\" target=\"_blank\">{\$aacount_message}</a></span></td>
	</tr>
</table>
<br />"
	);
	foreach($templates as $template)
	{
		$insert = array(
			"title" => $template['title'],
			"template" => $template['template'],
			"sid" => "-1",
			"version" => "1600",
			"dateline" => TIME_NOW
		);
		$db->insert_query("templates", $insert);
	}
	
	find_replace_templatesets("header", "#".preg_quote('{$unreadreports}')."#i", '{$unreadreports}{$aacount}');
	
	// rebuild the cache
	aacount_recount_awaitingactivation();
}

function aacount_deactivate()
{
	global $db;
	
	require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
	
	$templates = array(
		"aacount"
	);
	$templates = "'" . implode("','", $templates) . "'";
	$db->delete_query("templates", "title IN ({$templates})");
	
	find_replace_templatesets("header", "#".preg_quote('{$aacount}')."#i", '', 0);
}

function aacount()
{
	global $mybb, $db, $cache, $lang, $config, $templates, $aacount;
	
	$lang->load("aacount");
	
	if($mybb->usergroup['cancp'] == 1)
	{
		$awaitingactivation = $cache->read("awaitingactivation");
		
		if($awaitingactivation != 0)
		{
			if($mybb->settings['regtype'] == "admin" || $mybb->settings['regtype'] == "verify")
			{
				if($awaitingactivation == 1)
				{
					$aacount_message = $lang->aacount_message_single;
				}
				else
				{
					$aacount_message = $lang->sprintf($lang->aacount_message_multiple, $awaitingactivation);
				}
				eval("\$aacount = \"".$templates->get('aacount')."\";");
			}
		}
	}
}

function aacount_register()
{
	global $mybb;
	
	if($mybb->settings['regtype'] == "admin" || $mybb->settings['regtype'] == "verify")
	{
		// if this user requires activation, rebuild the cache
		aacount_recount_awaitingactivation();
	}
}

function aacount_mass_activate()
{
	global $mybb, $db, $lang;
	
	if($mybb->input['inline_action'] == "multiactivate")
	{
		// because of the location of the hook here, we need to copy the default code to activate the users, and then call the function to recount the number awaiting activation
		
		$ids = explode("|", $mybb->cookies['inlinemod_useracp']);
		foreach($ids as $id)
		{
			if($id != '')
			{
				$selected[] = intval($id);
			}
		}
		
		if(!verify_post_check($mybb->input['my_post_key']))
		{
			flash_message($lang->invalid_post_verify_key2, 'error');
			admin_redirect("index.php?module=user-user");
		}
		
		if(!is_array($selected))
		{
			flash_message($lang->error_inline_no_users_selected, 'error');
			admin_redirect("index.php?module=user-users");
		}
		
		if(is_array($selected))
		{
			$sql_array = implode(",", $selected);
			$query = $db->simple_select("users", "uid", "usergroup = '5' AND uid IN (".$sql_array.")");
			while($user = $db->fetch_array($query))
			{
				$to_update[] = $user['uid'];
			}
		}
		
		if(is_array($to_update))
		{
			$sql_array = implode(",", $to_update);
			$db->write_query("UPDATE ".TABLE_PREFIX."users SET usergroup = '2' WHERE uid IN (".$sql_array.")");
			
			aacount_recount_awaitingactivation();
			
			$to_update_count = count($to_update);
			$lang->inline_activated = $lang->sprintf($lang->inline_activated, my_number_format($to_update_count));
			
			if($to_update_count != count($selected))
			{
				$not_updated_count = count($selected) - $to_update_count;
				$lang->inline_activated_more = $lang->sprintf($lang->inline_activated_more, my_number_format($not_updated_count));
				$lang->inline_activated = $lang->inline_activated."<br />".$lang->inline_activated_more;
			}
			
			$mybb->input['action'] = "inline_activated";
			log_admin_action($to_update_count);
			my_unsetcookie("inlinemod_useracp");
			
			flash_message($lang->inline_activated, 'success');
			admin_redirect("index.php?module=user-users");
		}
		else
		{
			flash_message($lang->inline_activated_failed, 'error');
			admin_redirect("index.php?module=user-users");
		}
	}
}

function aacount_edit_usergroup()
{
	global $mybb, $db, $lang;
	
	if($mybb->request_method == "post")
	{
		$uid = intval($mybb->input['uid']);
		$user = get_user($uid);
		
		if(!$user['uid'])
		{
			flash_message($lang->error_invalid_user, 'error');
			admin_redirect("index.php?module=user-users");
		}
		
		if($user['usergroup'] == 5 && $mybb->input['usergroup'] != 5)
		{
			$update = array(
				"usergroup" => intval($mybb->input['usergroup'])
			);
			$db->update_query("users", $update, "uid = '{$uid}'");
			
			aacount_recount_awaitingactivation();
		}
	}
	
}

function aacount_recount_awaitingactivation()
{
	global $mybb, $db, $cache;
	
	// rebuild the cache
	if($mybb->settings['regtype'] == "admin" || $mybb->settings['regtype'] == "verify")
	{
		$query = $db->simple_select("users", "COUNT(*) AS awaitingactivation", "usergroup = '5'");
	}
	else
	{
		return;
	}
	$awaitingactivation = $db->fetch_field($query, "awaitingactivation");
	$cache->update("awaitingactivation", $awaitingactivation);
}

function aacount_edit_datacache_class()
{
	global $cache;
	
	class MyDatacache extends datacache
	{
		function update_awaitingactivation()
		{
			aacount_recount_awaitingactivation();
		}
	}
	
	$cache = null;
	$cache = new MyDatacache;
}
?>