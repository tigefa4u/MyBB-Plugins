<?php
/**
 * Restrict Email Domains 1.0.1

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

$plugins->add_hook("datahandler_user_validate", "restrictemaildomains_check");

function restrictemaildomains_info()
{
	return array(
		"name" => "Restrict Email Domains",
		"description" => "Allows you to restrict which domains users can register with.",
		"website" => "http://mattrogowski.co.uk",
		"author" => "MattRogowski",
		"authorsite" => "http://mattrogowski.co.uk",
		"version" => "1.0.1",
		"compatibility" => "16*",
		"guid" => "5c22b24a308921559e56c7392b82579a"
	);
}

function restrictemaildomains_activate()
{
	global $db;
	
	restrictemaildomains_deactivate();
	
	$settings_group = array(
		"name" => "restrictemaildomains",
		"title" => "Restrict Email Domains Settings",
		"description" => "Settings for the restrict email domains plugin.",
		"disporder" => "28",
		"isdefault" => 0
	);
	$db->insert_query("settinggroups", $settings_group);
	$gid = $db->insert_id();
	
	$settings = array();
	$settings[] = array(
		"name" => "restrictemaildomains_enabled",
		"title" => "Enable email domain restriction??",
		"description" => "If you want to temporarily stop the restriction but don't want to lose the list of domains below, set this to No instead of deactivating the plugin.",
		"optionscode" => "yesno",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "restrictemaildomains_inadmin",
		"title" => "Enable email domain restriction in ACP??",
		"description" => "Do you want this check to be performed when editing a user in the ACP?? Select No if you want to be able to set any email address.",
		"optionscode" => "yesno",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "restrictemaildomains_domains",
		"title" => "Domains to allow",
		"description" => "Which domains would you like to allow?? Put one domain on a line. Examples: gmail.com, live.com, hotmail.com",
		"optionscode" => "textarea",
		"value" => ""
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

function restrictemaildomains_deactivate()
{
	global $db;
	
	$db->delete_query("settinggroups", "name = 'restrictemaildomains'");
	
	$settings = array(
		"restrictemaildomains_enabled",
		"restrictemaildomains_inadmin",
		"restrictemaildomains_domains"
	);
	$settings = "'" . implode("','", $settings) . "'";
	$db->delete_query("settings", "name IN ({$settings})");
	
	rebuild_settings();
}

function restrictemaildomains_check() 
{
	global $mybb, $lang, $userhandler;
	
	if($mybb->settings['restrictemaildomains_enabled'] != 1 || !((THIS_SCRIPT == "member.php" && $mybb->input['action'] == "do_register") || (THIS_SCRIPT == "usercp.php" && $mybb->input['action'] == "do_email")) || ($mybb->settings['restrictemaildomains_enabled'] == 1 && defined("IN_ADMINCP") && $mybb->settings['restrictemaildomains_inadmin'] != 1))
	{
		return;
	}
	
	$userhandler->data['email'] = $mybb->input['email'];
	
	if(!$userhandler->verify_email())
	{
		return;
	}
	
	$lang->load("restrictemaildomains");
	
	$is_allowed_email_domain = false;
	
	if(empty($mybb->settings['restrictemaildomains_domains']))
	{
		// needs to return true if no domains are specified, otherwise it won't allow any emails
		$is_allowed_email_domain = true;
	}
	else
	{
		// we just want to check the email domain itself here
		$allowed_email_domains = explode("\n", $mybb->settings['restrictemaildomains_domains']);
		// need to trim blank spaces off the email domains
		foreach($allowed_email_domains as $key => $email)
		{
			$allowed_email_domains[$key] = trim($email);
		}
		$exploded_email = explode("@", $mybb->input['email']);
		$email_domain = $exploded_email[1];
		if(in_array($email_domain, $allowed_email_domains))
		{
			$is_allowed_email_domain = true;
		}
		
		if(!$is_allowed_email_domain)
		{
			foreach($allowed_email_domains as $domain)
			{
				if(substr($domain, 0, 1) == '.')
				{
					if(substr($mybb->input['email'], -strlen($domain)) == $domain)
					{
						$is_allowed_email_domain = true;
					}
				}
			}
		}
	}
	
	if(!$is_allowed_email_domain)
	{
		$error = $lang->invalid_email_domain;
		
		$allowed_email_domains = implode(", ", $allowed_email_domains);
		
		if(!empty($allowed_email_domains))
		{
			$error .= $lang->sprintf($lang->valid_email_domains, $allowed_email_domains);
		}
		
		$userhandler->set_error($error);
	}
}
?>