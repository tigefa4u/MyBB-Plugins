<?php
/**
 * Plugin Git Sync

 * Copyright 2012 Matthew Rogowski

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

$plugins->add_hook('admin_tools_menu', 'plugingitsync_admin_tools_menu');
$plugins->add_hook('admin_tools_action_handler', 'plugingitsync_admin_tools_action_handler');
$plugins->add_hook('admin_config_plugins_activate_commit', 'plugingitsync_admin_config_plugins_activate_commit');

function plugingitsync_info()
{
	return array(
		'name' => 'Plugin Git Sync',
		'description' => 'Sync plugin files with Git repositories',
		'website' => 'http://localhost/mybb/mybb_16x_plugins',
		'author' => 'MattRogowski',
		'authorsite' => 'http://localhost/mybb/mybb_16x_plugins',
		'version' => '1.0',
		'compatibility' => '16*'
	);
}

function plugingitsync_activate()
{
	
}

function plugingitsync_deactivate()
{
	
}

function plugingitsync_install()
{
	global $db;
	
	plugingitsync_uninstall();
	
	if(!$db->table_exists('plugingitsync'))
	{
		$db->write_query("
			CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "plugingitsync` (
				`plugin_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				`plugin_name` varchar(255) NOT NULL,
				`plugin_codename` varchar(255) NOT NULL,
				`plugin_repo_name` varchar(255) NOT NULL,
				`plugin_repo_url` varchar(255) NOT NULL,
				`plugin_files` text NOT NULL,
				PRIMARY KEY (`plugin_id`)
			) ENGINE=MyISAM ;
		");
	}
}

function plugingitsync_is_installed()
{
	global $db;
	
	return $db->table_exists('plugingitsync');
}

function plugingitsync_uninstall()
{
	global $db;
	
	if($db->table_exists('plugingitsync'))
	{
		$db->drop_table('plugingitsync');
	}
}

function plugingitsync_admin_tools_menu($sub_menu)
{
	global $lang;
	
	$lang->load("tools_plugingitsync");
	
	$sub_menu[] = array("id" => "plugingitsync", "title" => $lang->plugingitsync, "link" => "index.php?module=tools-plugingitsync");
	
	return $sub_menu;
}

function plugingitsync_admin_tools_action_handler($actions)
{
	$actions['plugingitsync'] = array(
		"active" => "plugingitsync",
		"file" => "plugingitsync.php"
	);
	
	return $actions;
}

function plugingitsync_get_plugins_info_from_repos()
{
	global $_files;
	
	require_once MYBB_ROOT.'inc/plugins/plugingitsync/config.php';
	
	$plugins_info = array();
	
	$repos = array_filter(glob(GIT_REPO_ROOT.'*'), 'plugingitsync_is_git_repo');
	
	foreach($repos as $repo)
	{
		$repo_name = str_replace(GIT_REPO_ROOT, '', $repo);
		$path = $repo.REPO_FILE_ROOT;
		if(is_dir($path))
		{
			$files = plugingitsync_find_files($path);
			$_files = array();
			foreach($files as &$file)
			{
				$file = str_replace(GIT_REPO_ROOT.$repo_name.REPO_FILE_ROOT, '', $file);
			}
			$plugins_info[] = array(
				'repo_name' => $repo_name,
				'files' => $files
			);
		}
	}
	
	return $plugins_info;
}

function plugingitsync_get_plugins_info_from_database()
{
	global $db;
	
	$plugins_info = array();
	
	$query = $db->simple_select('plugingitsync', '*', '', array('order_by' => 'plugin_name', 'order_dir' => 'ASC'));
	while($plugin = $db->fetch_array($query))
	{
		$plugins_info[] = array(
			'codename' => $plugin['plugin_codename'],
			'repo_name' => $plugin['plugin_repo_name'],
			'repo_url' => $plugin['plugin_repo_url'],
			'files' => @unserialize($plugin['plugin_files'])
		);
	}
	
	return $plugins_info;
}

function plugingitsync_is_git_repo($path)
{
	if(!is_dir($path))
	{
		return false;
	}
	
	if(!is_dir($path.'/.git'))
	{
		return false;
	}
	
	return true;
}

function plugingitsync_find_files($path)
{
	global $_files;
	
	if(substr($path, -1) == '/')
	{
		$path = substr($path, 0, -1);
	}
	
	if(is_dir($path))
	{
		$objects = scandir($path);
		foreach($objects as $object)
		{
			if(!in_array($object, array(".", "..", "__MACOSX", ".DS_Store", "thumbs.db", ".svn", ".git")))
			{
				if(is_dir($path.'/'.$object))
				{
					plugingitsync_find_files($path.'/'.$object);
				}
				else
				{
					$_files[] = $path.'/'.$object;
				}
			}
		}
	}
	
	return $_files;
}

function plugingitsync_admin_config_plugins_activate_commit()
{
	global $codename;
	
	if($codename == 'plugingitsync')
	{
		admin_redirect('index.php?module=tools-plugingitsync');
	}
}

?>