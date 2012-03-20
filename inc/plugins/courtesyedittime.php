<?php
/**
 * Courtesy Edit Time 0.1

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

$plugins->add_hook("datahandler_post_update", "courtesyedittime_update_edittime", 10);
$plugins->add_hook("postbit", "courtesyedittime_postbit");
$plugins->add_hook("xmlhttp", "courtesyedittime_xmlhttp");

function courtesyedittime_info()
{
	return array(
		"name" => "Courtesy Edit Time",
		"description" => "Allow a courtesy edit time, whereby the 'edited by' message won't show up for a set amount of time.",
		"website" => "http://mattrogowski.co.uk/mybb/plugins/plugin/courtesy-edit-time",
		"author" => "MattRogowski",
		"authorsite" => "http://mattrogowski.co.uk/mybb/",
		"version" => "1.0",
		"compatibility" => "16*",
		"guid" => "e61aa4cc5226849bc351fbf8c80a751d"
	);
}

function courtesyedittime_install()
{
	global $db;
	
	if(!$db->field_exists("edittime2", "posts"))
	{
		$db->add_column("posts", "edittime2", "INT(10) NOT NULL DEFAULT '0'");
	}
	
	$db->write_query("UPDATE " . TABLE_PREFIX . "posts SET `edittime2` = `edittime`");
}

function courtesyedittime_is_installed()
{
	global $db;
	
	return $db->field_exists("edittime2", "posts");
}

function courtesyedittime_uninstall()
{
	global $db;
	
	if($db->field_exists("edittime2", "posts"))
	{
		$db->drop_column("posts", "edittime2");
	}
}

function courtesyedittime_activate()
{
	global $db;
	
	courtesyedittime_deactivate();
	
	$settings_group = array(
		"name" => "courtesyedittime",
		"title" => "Courtesy Edit Time Settings",
		"description" => "Settings for the courtesy edit time plugin.",
		"disporder" => "28",
		"isdefault" => 0
	);
	$db->insert_query("settinggroups", $settings_group);
	$gid = $db->insert_id();
	
	$settings = array();
	$settings[] = array(
		"name" => "courtesyedittime",
		"title" => "Courtesy Edit Time",
		"description" => "Enter the number of seconds that the 'edited by' message will not show for. As an example, if you put 60 in the box, users will be able to edit their posts for 60 seconds before the 'edited by' message will be added.",
		"optionscode" => "text",
		"value" => "60"
	);
	$i = 1;
	foreach($settings as $setting)
	{
		$insert = array(
			"name" => $db->escape_string($setting['name']),
			"title" => $db->escape_string($setting['title']),
			"description" => $db->escape_string($setting['description']),
			"optionscode" => $db->escape_string($setting['optionscode']),
			"value" => $db->escape_string($setting['value']),
			"disporder" => intval($i),
			"gid" => intval($gid),
		);
		$db->insert_query("settings", $insert);
		$i++;
	}
	
	rebuild_settings();
}

function courtesyedittime_deactivate()
{
	global $db;
	
	$db->delete_query("settinggroups", "name = 'courtesyedittime'");
	
	$settings = array(
		"courtesyedittime"
	);
	$settings = "'" . implode("','", $settings) . "'";
	$db->delete_query("settings", "name IN ({$settings})");
	
	rebuild_settings();
}

function courtesyedittime_update_edittime(&$data)
{
	$data->post_update_data['edittime2'] = intval($data->post_update_data['edittime']);
}

function courtesyedittime_postbit(&$post)
{
	global $mybb;
	
	if($mybb->settings['courtesyedittime'] > 0)
	{
		if($post['edittime2'])
		{
			$difference = $post['edittime2'] - $post['dateline'];
			if($difference <= $mybb->settings['courtesyedittime'])
			{
				$post['editedmsg'] = "";
			}
		}
	}
}

function courtesyedittime_xmlhttp()
{
	global $mybb, $plugins;
	
	if($mybb->input['action'] == "edit_post")
	{
		$plugins->add_hook("datahandler_post_update", "courtesyedittime_do_xmlhttp", 20);
	}
}

function courtesyedittime_do_xmlhttp(&$data)
{
	global $mybb;
	
	if($mybb->settings['courtesyedittime'] > 0)
	{
		if($data->post_update_data['edittime2'])
		{
			$post_info = get_post($data->pid);
			$difference = $data->post_update_data['edittime2'] - $post_info['dateline'];
			if($difference <= $mybb->settings['courtesyedittime'])
			{
				$mybb->settings['showeditedby'] = 0;
			}
		}
	}
}
?>