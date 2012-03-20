<?php
/**
 * Header Warning Level 1.6

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

$plugins->add_hook("global_start", "headerwarnlevel");

function headerwarnlevel_info()
{
	return array(
		"name" => "Header Warning Level",
		"description" => "Shows a user's warning level in the welcomeblock.",
		"website" => "http://mattrogowski.co.uk",
		"author" => "MattRogowski",
		"authorsite" => "http://mattrogowski.co.uk",
		"version" => "1.6",
		"compatibility" => "16*",
		"guid" => "1bd50cf48ebe822c4297cf97d9971071"
	);
}

function headerwarnlevel_activate()
{
	global $db;
	
	require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
	
	headerwarnlevel_deactivate();
	
	$settings_group = array(
		"name" => "headerwarnlevel",
		"title" => "Header Warning Level Settings",
		"description" => "Settings for the header warning level plugin.",
		"disporder" => "28",
		"isdefault" => 0
	);
	$db->insert_query("settinggroups", $settings_group);
	$gid = $db->insert_id();
	
	$settings = array();
	$settings[] = array(
		"name" => "headerwarnlevel",
		"title" => "Show the warning level in header??",
		"description" => "Display the user\'s warning level in header??",
		"optionscode" => "onoff",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "headerwarnlevelcutoff",
		"title" => "Cut-off warn level",
		"description" => "What cut-off warn level do you want?? For example, to only show warnings that are 50% or above, put 50 in the setting. Use 0 or leave blank for no cut-off.",
		"optionscode" => "text",
		"value" => "0"
	);
	$settings[] = array(
		"name" => "headerwarnlevelshowifnone",
		"title" => "Show if 0%??",
		"description" => "Do you want it to show even if the user has 0% warning??",
		"optionscode" => "yesno",
		"value" => "0"
	);
	$settings[] = array(
		"name" => "headerwarnlevelgroups",
		"title" => "Groups to show to",
		"description" => "What usergroups should this show to?? Enter the GIDs and separate with a comma.",
		"optionscode" => "text",
		"value" => "2"
	);
	$i = 1;
	foreach($settings as $setting)
	{
		$insert = array(
			"name" => $setting['name'],
			"title" => $setting['title'],
			"description" => $setting['description'],
			"optionscode" => $setting['optionscode'],
			"value" => $setting['value'],
			"disporder" => $i,
			"gid" => intval($gid),
		);
		$db->insert_query("settings", $insert);
		$i++;
	}
	
	rebuild_settings();
	
	$templates = array();
	$templates[] = array(
		"title" => "headerwarnlevel",
		"template" => " &mdash; {\$lang->postbit_warning_level} <a href=\"{\$mybb->settings[\'bburl\']}/usercp.php\"><strong>{\$warninglevel}</strong></a>"
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
	
	find_replace_templatesets("header_welcomeblock_member", "#".preg_quote('{$lang->welcome_open_buddy_list}</a>')."#i", '{$lang->welcome_open_buddy_list}</a>{$headerwarnlevel}');
}

function headerwarnlevel_deactivate()
{
	global $db;
	
	require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
	
	$db->delete_query("settinggroups", "name = 'headerwarnlevel'");
	
	$settings = array(
		"headerwarnlevel",
		"headerwarnlevelcutoff",
		"headerwarnlevelshowifnone",
		"headerwarnlevelgroups"
	);
	$settings = "'" . implode("','", $settings) . "'";
	$db->delete_query("settings", "name IN ({$settings})");
	
	rebuild_settings();
	
	$templates = array(
		"headerwarnlevel"
	);
	$templates = "'" . implode("','", $templates) . "'";
	$db->delete_query("templates", "title IN ({$templates})");
	
	find_replace_templatesets("header_welcomeblock_member", "#".preg_quote('{$headerwarnlevel}')."#i", '', 0);
}

function headerwarnlevel()
{
	global $mybb, $db, $lang, $templates, $headerwarnlevel;
	
	$warningpoints = round($mybb->user['warningpoints'] / $mybb->settings['maxwarningpoints'] * 100);
	if($warningpoints > 100)
	{
		$warningpoints = 100;
	}
	$warninglevel = get_colored_warning_level($warningpoints);
	
	$headerwarnlevelgroups = explode(",", str_replace(" ", "", $mybb->settings['headerwarnlevelgroups']));
	
	if($mybb->settings['headerwarnlevel'] == 1 && in_array($mybb->user['usergroup'], $headerwarnlevelgroups))
	{
		// either they've got no warning and it's been set to show with 0%, or they've got warning and it's more than or equal to the limit
		if(($warningpoints == 0 && $mybb->settings['headerwarnlevelshowifnone'] == 1) || ($warningpoints >= $mybb->settings['headerwarnlevelcutoff'] && $warningpoints != 0))
		{
			eval("\$headerwarnlevel = \"".$templates->get('headerwarnlevel')."\";");
		}
	}
}
?>