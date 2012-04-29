<?php
/**
 * Automatic Subscriptions 1.1

 * Copyright 2011 Matthew Rogowski

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

$plugins->add_hook("member_register_start", "automaticsubscriptions_option");
$plugins->add_hook("usercp_options_start", "automaticsubscriptions_option");
$plugins->add_hook("member_do_register_end", "automaticsubscriptions_do_option");
$plugins->add_hook("usercp_do_options_end", "automaticsubscriptions_do_option");
$plugins->add_hook("datahandler_post_insert_thread", "automaticsubscriptions_forum");
$plugins->add_hook("datahandler_post_insert_thread_post", "automaticsubscriptions_thread");
$plugins->add_hook("datahandler_post_insert_post", "automaticsubscriptions_thread");
$plugins->add_hook("admin_formcontainer_output_row", "automaticsubscriptions_admin_formcontainer_output_row");
$plugins->add_hook("admin_user_users_edit_commit", "automaticsubscriptions_admin_user_users_edit_commit");

function automaticsubscriptions_info()
{
	return array(
		"name" => "Automatic Subscriptions",
		"description" => "Allows you to automatically subscribe to all new threads and replies without having to manually subscribe to all forums and threads.",
		"website" => "http://mattrogowski.co.uk/mybb/plugins/plugin/automatic-subscriptions",
		"author" => "MattRogowski",
		"authorsite" => "http://mattrogowski.co.uk/mybb/",
		"version" => "1.1",
		"compatibility" => "16*",
		"guid" => "643337d2e48f9d677a42bb5767e7b5ae"
	);
}

function automaticsubscriptions_install()
{
	global $db;
	
	if(!$db->field_exists("automaticsubscriptions", "users"))
	{
		$db->add_column("users", "automaticsubscriptions", "SMALLINT(1) NOT NULL DEFAULT '0'");
	}
}

function automaticsubscriptions_is_installed()
{
	global $db;
	
	return $db->field_exists("automaticsubscriptions", "users");
}

function automaticsubscriptions_uninstall()
{
	global $db;
	
	if($db->field_exists("automaticsubscriptions", "users"))
	{
		$db->drop_column("users", "automaticsubscriptions");
	}
}

function automaticsubscriptions_activate()
{
	global $db;
	
	require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
	
	automaticsubscriptions_deactivate();
	
	$templates = array();
	$templates[] = array(
		"title" => "automaticsubscriptions",
		"template" => "<tr>
	<td colspan=\"2\"><span class=\"smalltext\"><label for=\"automaticsubscriptions\">{\$lang->automaticsubscriptions_desc}</label></span></td>
</tr>
<tr>
<td colspan=\"2\">
	<select name=\"automaticsubscriptions\" id=\"automaticsubscriptions\">
		<option value=\"0\"{\$automaticsubscriptions_off_selected}>{\$lang->automaticsubscriptions_off}</option>
		<option value=\"1\"{\$automaticsubscriptions_threads_selected}>{\$lang->automaticsubscriptions_threads}</option>
		<option value=\"2\"{\$automaticsubscriptions_threads_posts_selected}>{\$lang->automaticsubscriptions_threads_posts}</option>
		<option value=\"3\"{\$automaticsubscriptions_threads_forum_selected}>{\$lang->automaticsubscriptions_threads_forum}</option>
		<option value=\"4\"{\$automaticsubscriptions_threads_posts_forum_selected}>{\$lang->automaticsubscriptions_threads_posts_forum}</option>
	</select>
</td>
</tr>"
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
}

function automaticsubscriptions_deactivate()
{
	global $db;
	
	require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
	
	$templates = array(
		"automaticsubscriptions"
	);
	$templates = "'" . implode("','", $templates) . "'";
	$db->delete_query("templates", "title IN ({$templates})");
	
	find_replace_templatesets("member_register", "#".preg_quote('{$automaticsubscriptions}')."#i", '', 0);
	find_replace_templatesets("usercp_options", "#".preg_quote('{$automaticsubscriptions}')."#i", '', 0);
}

function automaticsubscriptions_option()
{
	global $mybb, $lang, $templates, $automaticsubscriptions;
	
	$lang->load("automaticsubscriptions");
	
	if(THIS_SCRIPT == "usercp.php" && $mybb->input['action'] == "options")
	{
		$automaticsubscriptions_off_selected = $automaticsubscriptions_threads_selected = $automaticsubscriptions_threads_posts_selected = $automaticsubscriptions_threads_forum_selected = $automaticsubscriptions_threads_posts_forum_selected = "";
		if($mybb->user['automaticsubscriptions'] == 4)
		{
			$automaticsubscriptions_threads_posts_forum_selected = " selected=\"selected\"";
		}
		elseif($mybb->user['automaticsubscriptions'] == 3)
		{
			$automaticsubscriptions_threads_forum_selected = " selected=\"selected\"";
		}
		elseif($mybb->user['automaticsubscriptions'] == 2)
		{
			$automaticsubscriptions_threads_posts_selected = " selected=\"selected\"";
		}
		elseif($mybb->user['automaticsubscriptions'] == 1)
		{
			$automaticsubscriptions_threads_selected = " selected=\"selected\"";
		}
		else
		{
			$automaticsubscriptions_off_selected = " selected=\"selected\"";
		}
	}
	
	eval("\$automaticsubscriptions = \"".$templates->get('automaticsubscriptions')."\";");
}

function automaticsubscriptions_do_option()
{
	global $mybb, $db, $user_info;
	
	$uid = 0;
	// registration form
	if($user_info['uid'])
	{
		$uid = $user_info['uid'];
	}
	else
	{
		$uid = $mybb->user['uid'];
	}
	
	$update = array(
		"automaticsubscriptions" => intval($mybb->input['automaticsubscriptions'])
	);
	$db->update_query("users", $update, "uid = '" . intval($uid) . "'");
}

function automaticsubscriptions_forum(&$data)
{
	global $db, $draft_check;
	
	if($draft_check)
	{
		return;
	}
	
	// select all users who want to subscribe to this forum and aren't already subscribed
	// then subscribe them to the forum so they'll get an email about this new thread
	$fid = intval($data->thread_insert_data['fid']);
	$query = $db->query("
		SELECT u.uid, u.automaticsubscriptions
		FROM " . TABLE_PREFIX . "users u
		LEFT JOIN " . TABLE_PREFIX . "forumsubscriptions f
		ON (u.uid = f.uid AND f.fid = '{$fid}')
		WHERE ISNULL(f.fsid)
		AND u.automaticsubscriptions != '0'
	");
	require_once MYBB_ROOT . "inc/functions_user.php";
	while($user = $db->fetch_array($query))
	{
		$forumpermissions = forum_permissions($fid);
		if($forumpermissions['canview'] == 0 || $forumpermissions['canviewthreads'] == 0)
		{
			continue;
		}
		
		if($user['automaticsubscriptions'] == 3 || $user['automaticsubscriptions'] == 4)
		{
			$query2 = $db->simple_select("forumsubscriptions", "*", "fid='".intval($fid)."' AND uid='".intval($user['uid'])."'", array('limit' => 1));
			$fsubscription = $db->fetch_array($query2);
			if(!$fsubscription['fsid'])
			{
				continue;
			}
		}
		
		add_subscribed_forum($fid, $user['uid']);
	}
}

function automaticsubscriptions_thread(&$data)
{
	global $db, $draft_check;
	
	if($draft_check)
	{
		return;
	}
	
	// select all users who want to subscribe to this thread and aren't already subscribed
	// then subscribe them to the thread so they'll get an email about this new reply
	$tid = intval($data->post_insert_data['tid']);
	$thread = get_thread($tid);
	$query = $db->query("
		SELECT u.uid, u.subscriptionmethod, u.automaticsubscriptions
		FROM " . TABLE_PREFIX . "users u
		LEFT JOIN " . TABLE_PREFIX . "threadsubscriptions t
		ON (u.uid = t.uid AND t.tid = '{$tid}')
		WHERE ISNULL(t.sid)
		AND (u.automaticsubscriptions = '2' OR u.automaticsubscriptions = '4')
	");
	require_once MYBB_ROOT . "inc/functions_user.php";
	while($user = $db->fetch_array($query))
	{
		$forumpermissions = forum_permissions($thread['fid']);
		if($forumpermissions['canview'] == 0 || $forumpermissions['canviewthreads'] == 0)
		{
			continue;
		}
		
		if($user['automaticsubscriptions'] == 4)
		{
			$query2 = $db->simple_select("forumsubscriptions", "*", "fid='".intval($thread['fid'])."' AND uid='".intval($user['uid'])."'", array('limit' => 1));
			$fsubscription = $db->fetch_array($query2);
			if(!$fsubscription['fsid'])
			{
				continue;
			}
		}
		
		add_subscribed_thread($tid, $user['subscriptionmethod'], $user['uid']);
	}
}

function automaticsubscriptions_admin_formcontainer_output_row($pluginargs)
{
	global $mybb, $lang, $form;
	
	if(!empty($lang->messaging_and_notification) && $pluginargs['title'] == $lang->messaging_and_notification)
	{
		$lang->load('automaticsubscriptions');
		
		$pluginargs['content'] .= "<div class=\"user_settings_bit\"><label for=\"automaticsubscriptions\">{$lang->automaticsubscriptions_desc}</label><br />".$form->generate_select_box("automaticsubscriptions", array($lang->automaticsubscriptions_off, $lang->automaticsubscriptions_threads, $lang->automaticsubscriptions_threads_posts, $lang->automaticsubscriptions_threads_forum, $lang->automaticsubscriptions_threads_posts_forum), $mybb->input['automaticsubscriptions'], array('id' => 'automaticsubscriptions')).'</div>';
	}
}

function automaticsubscriptions_admin_user_users_edit_commit()
{
	global $mybb, $db, $user;
	
	$update = array(
		'automaticsubscriptions' => intval($mybb->input['automaticsubscriptions'])
	);
	$db->update_query('users', $update, 'uid = \''.intval($user['uid']).'\'');
}
?>