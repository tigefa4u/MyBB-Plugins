<?php
/**
 * Date of Birth on Registration 0.4

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

$plugins->add_hook("member_register_start", "dbonreg_register");
$plugins->add_hook("member_do_register_start", "dobonreg");
$plugins->add_hook("usercp_do_profile_start", "dobonreg");
$plugins->add_hook("global_start", "dobonreg_ban_check");
$plugins->add_hook("member_register_start", "dobonreg_ban_check");

function dobonreg_info()
{
	return array(
		"name" => "Date of Birth on Registration",
		"description" => "Adds option to add your date of birth in the registration form.",
		"website" => "http://mattrogowski.co.uk",
		"author" => "MattRogowski",
		"authorsite" => "http://mattrogowski.co.uk",
		"version" => "0.4",
		"compatibility" => "16*",
		"guid" => "e212d0a10588d95c5b2c560d7f43e3ab"
	);
}

function dobonreg_activate()
{
	global $db;
	
	require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
	
	dobonreg_deactivate();
	
	$settings_group = array(
		"name" => "dobonreg",
		"title" => "Date of Birth on Registration Settings",
		"description" => "Settings for the date of birth on registration plugin.",
		"disporder" => "28",
		"isdefault" => 0
	);
	$db->insert_query("settinggroups", $settings_group);
	$gid = $db->insert_id();
	
	$settings = array();
	$settings[] = array(
		"name" => "dobonreg_type",
		"title" => "Date of Birth Requirement",
		"description" => "What do you require users to enter??",
		"optionscode" => "select
required_full=Required - Full (DD/MM/YYYY)
required_partial=Required - Partial (DD/MM)
optional=Optional",
		"value" => "optional"
	);
	$settings[] = array(
		"name" => "dobonreg_agelimit",
		"title" => "Age Limit",
		"description" => "Do you want to limit registrations to a certain age?? If you want to limit users under 13, it is recommended to use the COPPA compliance feature of MyBB instead. Set to 0 for no age limit. <strong>This will only take effect if the setting above is set to 'Required - Full'.</strong>",
		"optionscode" => "text",
		"value" => "0"
	);
	$settings[] = array(
		"name" => "dobonreg_underage_ban",
		"title" => "Underage Action",
		"description" => "What action do you want to take about someone who tries to register and is underage?? <strong>This will only apply to new registrations.</strong>",
		"optionscode" => "select
none=No action (can re-register with different date)
reg=Stop registration
global=Block from forum completely",
		"value" => "none"
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
	
	$templates = array();
	$templates[] = array(
		"title" => "dobonreg",
		"template" => "<br />
<fieldset class=\"trow2\">
<legend><strong>{\$lang->date_of_birth}</strong></legend>
<table cellspacing=\"0\" cellpadding=\"{\$theme['tablespace']}\">
<tr>
<td><span class=\"smalltext\"><label for=\"referrer\">{\$dobonreg_desc}</label></span></td>
</tr>
<tr>
<td>
{\$bday1s} {\$bday2s} <label for=\"bday3\" class=\"smalltext\">{\$lang->year}:</label> <input type=\"text\" name=\"bday3\" id=\"bday3\" class=\"textbox\" size=\"4\" maxlength=\"4\" value=\"{\$bday3}\" />
</td>
</tr></table>
</fieldset>"
	);
	foreach($templates as $template)
	{
		$insert = array(
			"title" => $db->escape_string($template['title']),
			"template" => $db->escape_string($template['template']),
			"sid" => "-1",
			"version" => "1600",
			"dateline" => TIME_NOW
		);
		$db->insert_query("templates", $insert);
	}
	
	find_replace_templatesets("member_register", "#".preg_quote('{$requiredfields}')."#i", '{$requiredfields}{$dobonreg}');
}

function dobonreg_deactivate()
{
	global $db;
	
	require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
	
	$db->delete_query("settinggroups", "name = 'dobonreg'");
	
	$settings = array(
		"dobonreg_type",
		"dobonreg_agelimit",
		"dobonreg_underage_ban"
	);
	$settings = "'" . implode("','", $settings) . "'";
	$db->delete_query("settings", "name IN ({$settings})");
	
	rebuild_settings();
	
	$templates = array(
		"dobonreg"
	);
	$templates = "'" . implode("','", $templates) . "'";
	$db->delete_query("templates", "title IN ({$templates})");
	
	find_replace_templatesets("member_register", "#".preg_quote('{$dobonreg}')."#i", '', 0);
}

function dobonreg()
{
	global $plugins;
	
	$plugins->add_hook("datahandler_user_validate", "dobonreg_check");
}

function dbonreg_register()
{
	global $mybb, $lang, $templates, $dobonreg;
	
	$lang->load("dobonreg");
	
	switch($mybb->settings['dobonreg_type'])
	{
		case "required_full":
			$dobonreg_desc = $lang->dobonreg_full_required;
			break;
		case "required_partial":
			$dobonreg_desc = $lang->dobonreg_partial_required;
			break;
		default:
			$dobonreg_desc = $lang->dobonreg_optional;
	}
	
	$bday1s = "";
	$bday1s .= "<label for=\"bday1\" class=\"smalltext\">{$lang->day}:</label> <select name=\"bday1\" id=\"bday1\">\n";
	$bday1s .= "<option value=\"\"></option>\n";
	for($i = 1; $i <= 31; $i++)
	{
		$bday1s .= "<option value=\"{$i}\">{$i}</option>\n";
	}
	$bday1s .= "</select>\n";
	
	$bday2s = "";
	$bday2s .= "<label for=\"bday2\" class=\"smalltext\">{$lang->month}:</label> <select name=\"bday2\" id=\"bday2\">\n";
	$bday2s .= "<option value=\"\"></option>\n";
	for($i = 1; $i <= 12; $i++)
	{
		$month = "month_" . $i;
		$bday2s .= "<option value=\"{$i}\">{$lang->$month}</option>\n";
	}
	$bday2s .= "</select>\n";
	
	eval("\$dobonreg = \"".$templates->get('dobonreg')."\";");
}

function dobonreg_check() 
{
	global $mybb, $lang, $userhandler;
	
	$lang->load("dobonreg");
	
	$bday1 = intval($mybb->input['bday1']);
	$bday2 = intval($mybb->input['bday2']);
	$bday3 = intval($mybb->input['bday3']);
	
	$birthday = array(
		"day" => $bday1,
		"month" => $bday2,
		"year" => $bday3
	);
	
	$userhandler->data['birthday'] = $birthday;
	if(!$userhandler->verify_birthday())
	{
		return;
	}
	// if it failed the default check, return that error, if it passed that, check the plugin requirements and show any errors for that
	dobonreg_validate_birthday($birthday);
}

function dobonreg_ban_check()
{
	global $mybb, $lang;
	
	$lang->load("dobonreg");
	
	if($mybb->settings['dobonreg_underage_ban'] == "none" || $mybb->settings['dobonreg_agelimit'] <= 0 || $mybb->cookies['dobonreg'] != 1)
	{
		return;
	}
	
	if($mybb->cookies['dobonreg'] == 1 && ($mybb->settings['dobonreg_underage_ban'] == "reg" && THIS_SCRIPT == "member.php" && $mybb->input['action'] == "register") || $mybb->settings['dobonreg_underage_ban'] == "global")
	{
		if($mybb->settings['dobonreg_underage_ban'] == "reg" && THIS_SCRIPT == "member.php" && $mybb->input['action'] == "register")
		{
			$error = $lang->invalid_dob_age_reg;
		}
		else
		{
			$error = $lang->invalid_dob_age_view;
		}
		error($lang->sprintf($error, $mybb->settings['dobonreg_agelimit']));
	}
}

function dobonreg_validate_birthday($birthday)
{
	global $mybb, $lang, $userhandler;
	
	$lang->load("dobonreg");
	
	if($mybb->settings['dobonreg_type'] != "optional")
	{
		if(!$birthday['day'] || !$birthday['month'] || ($mybb->settings['dobonreg_type'] == "required_full" && !$birthday['year']))
		{
			if($mybb->settings['dobonreg_type'] == "required_full")
			{
				$userhandler->set_error("invalid_dob_need_full");
			}
			else
			{
				$userhandler->set_error("invalid_dob_empty");
			}
			return false;
		}
	}
	
	if($mybb->settings['dobonreg_agelimit'] > 0 && $mybb->settings['dobonreg_type'] == "required_full")
	{
		$bday_time = @mktime(0, 0, 0, $birthday['month'], $birthday['day'], $birthday['year']);
		if($bday_time >= (mktime(0, 0, 0, my_date('n'), my_date('d'), my_date('Y') - intval($mybb->settings['dobonreg_agelimit']))))
		{
			if(THIS_SCRIPT == "member.php" && $mybb->input['action'] == "do_register")
			{
				my_setcookie("dobonreg", 1);
			}
			error($lang->sprintf($lang->invalid_dob_age_reg, $mybb->settings['dobonreg_agelimit']));
			return false;
		}
	}
	
	return true;
}
?>