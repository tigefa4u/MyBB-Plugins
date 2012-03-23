<?php
/**
 * Karma Stars 0.1

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

$plugins->add_hook("admin_user_menu", "karmastars_admin_user_menu");
$plugins->add_hook("admin_user_action_handler", "karmastars_admin_user_action_handler");
$plugins->add_hook("admin_user_permissions", "karmastars_admin_user_permissions");

function karmastars_info()
{
	return array(
		"name" => "Karma Stars",
		"description" => "Earn 'karma' and collect stars for posting.",
		"website" => "http://mattrogowski.co.uk/mybb/",
		"author" => "MattRogowski",
		"authorsite" => "http://mattrogowski.co.uk/mybb/",
		"version" => "0.1",
		"compatibility" => "16*",
		"guid" => ""
	);
}

function karmastars_install()
{
	global $db;
	
	plugingitsync_uninstall();
	
	if(!$db->table_exists('karmastars'))
	{
		$db->write_query("
			CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "karmastars` (
				`karmastar_id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`karmastar_posts` INT( 5 ) NOT NULL ,
				`karmastar_name` VARCHAR( 255 ) NOT NULL ,
				`karmastar_image` VARCHAR( 255 ) NOT NULL
			) ENGINE = MYISAM ;
		");
	}
}

function karmastars_is_installed()
{
	global $db;
	
	return $db->table_exists('karmastars');
}

function karmastars_uninstall()
{
	global $db;
	
	if($db->table_exists('karmastars'))
	{
		$db->drop_table('karmastars');
	}
}

function karmastars_activate()
{
	
}

function karmastars_deactivate()
{
	
}

function karmastars_admin_user_menu($sub_menu)
{
	global $lang;
	
	$lang->load("user_karmastars");
	
	$sub_menu[] = array("id" => "karmastars", "title" => $lang->karmastars, "link" => "index.php?module=user-karmastars");
	
	return $sub_menu;
}

function karmastars_admin_user_action_handler($actions)
{
	$actions['karmastars'] = array(
		"active" => "karmastars",
		"file" => "karmastars.php"
	);
	
	return $actions;
}

function karmastars_admin_user_permissions($admin_permissions)
{
	global $lang;
	
	$lang->load("user_karmastars");
	
	$admin_permissions['karmastars'] = $lang->can_manage_karmastars;
	
	return $admin_permissions;
}
?>