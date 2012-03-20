<?php
/**
 * Return to top button 1.6

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

$plugins->add_hook("postbit", "returntotop");

function returntotop_info()
{
	return array(
		"name" => "Return to top postbit button",
		"description" => "Adds a 'Return to Top' button to the postbit.",
		"website" => "http://mattrogowski.co.uk",
		"author" => "MattRogowski",
		"authorsite" => "http://mattrogowski.co.uk",
		"version" => "1.6",
		"compatibility" => "16*",
		"guid" => "e17bff92da3df398d9d45a467afdd8f1"
	);
}

function returntotop_activate()
{
	global $db;
	
	require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
	
	returntotop_deactivate();
	
	$templates = array();
	$templates[] = array(
		"title" => "returntotop",
		"template" => "<a href=\"#top\"><img src=\"{\$mybb->settings[\'bburl\']}/{\$theme[\'imgdir\']}/{\$lang->language}/postbit_top.gif\" border=\"0\" alt=\"Return to top\" /></a>"
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
	
	find_replace_templatesets("postbit", '#'.preg_quote('{$post[\'button_report\']}').'#', '{$post[\'button_report\']}{$post[\'returntotop\']}');
	find_replace_templatesets("postbit_classic", '#'.preg_quote('{$post[\'button_report\']}').'#', '{$post[\'button_report\']}{$post[\'returntotop\']}');
}

function returntotop_deactivate()
{
	global $db;
	
	require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
	
	$templates = array(
		"returntotop"
	);
	$templates = "'" . implode("','", $templates) . "'";
	$db->delete_query("templates", "title IN ({$templates})");
	
	find_replace_templatesets("postbit", '#'.preg_quote('{$post[\'returntotop\']}').'#', '', 0);
	find_replace_templatesets("postbit_classic", '#'.preg_quote('{$post[\'returntotop\']}').'#', '', 0);
}

function returntotop(&$post) 
{	
	global $mybb, $lang, $theme, $templates;
	eval("\$post['returntotop'] = \"".$templates->get('returntotop')."\";");
}
?>