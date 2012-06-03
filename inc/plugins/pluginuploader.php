<?php
/**
 * Plugin Uploader 1.1.2

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

$plugins->add_hook("admin_config_plugins_activate_commit", "pluginuploader_admin_config_plugins_activate_commit");
$plugins->add_hook("admin_config_permissions", "pluginuploader_admin_config_permissions");
$plugins->add_hook("admin_config_plugins_tabs", "pluginuploader_add_pluginuploader_tab");
$plugins->add_hook("admin_page_output_nav_tabs_start", "pluginuploader_add_pluginuploader_tab");
$plugins->add_hook("admin_config_plugins_begin", "pluginuploader_admin_config_plugins_begin");
$plugins->add_hook("admin_config_plugins_plugin_list", "pluginuploader_admin_config_plugins_plugin_list");
$plugins->add_hook("admin_config_plugins_plugin_list_plugin", "pluginuploader_admin_config_plugins_plugin_list_plugin");
$plugins->add_hook("admin_config_plugins_plugin_updates_plugin", "pluginuploader_admin_config_plugins_plugin_updates_plugin");
$plugins->add_hook("admin_config_plugins_browse_plugins_plugin", "pluginuploader_admin_config_plugins_browse_plugins_plugin");

function pluginuploader_info()
{
	return array(
		'name' => 'Plugin Uploader',
		'description' => 'Allows you to import .zip plugin archives directly and have the files extracted to their correct locations automatically.',
		'website' => 'http://mattrogowski.co.uk/mybb/plugins/plugin/plugin-uploader',
		'author' => 'MattRogowski',
		'authorsite' => 'http://mattrogowski.co.uk/mybb/',
		'version' => '1.1.2',
		'compatibility' => '16*',
		'guid' => 'bf2f8440a92b2c8dc841ec7dc1929ff4'
	);
}

function pluginuploader_install()
{
	global $db, $plugins, $pluginuploader_uninstall_confirm_override;

	// this is so we override the confirmation when trying to uninstall, so we can just run the uninstall code
	$pluginuploader_uninstall_confirm_override = true;
	pluginuploader_uninstall();

	if(!$db->table_exists("pluginuploader"))
	{
		$db->write_query("
			CREATE TABLE  " . TABLE_PREFIX . "pluginuploader (
				`pid` SMALLINT(5) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`name` VARCHAR(255) NOT NULL UNIQUE KEY,
				`version` VARCHAR(25) NOT NULL ,
				`files` TEXT NOT NULL
			) ENGINE = MYISAM ;
		");
	}

	if(!$db->field_exists("pluginuploader_key", "users"))
	{
		$db->add_column("users", "pluginuploader_key", "VARCHAR(120)");
	}

	chdir(MYBB_ROOT . "inc/plugins/");
	$plugin_files = glob("*.php");

	if(!empty($plugin_files))
	{
		foreach($plugin_files as $plugin_file)
		{
			$plugin_name = substr($plugin_file, 0, -4);
			$info_func = $plugin_name . "_info";

			if(!function_exists($info_func))
			{
				require_once MYBB_ROOT . "inc/plugins/" . $plugin_file;
			}
			if(!function_exists($info_func))
			{
				continue;
			}

			$info = $info_func();

			$insert = array(
				"name" => $db->escape_string($plugin_name),
				"version" => $db->escape_string($info['version'])
			);
			$db->insert_query("pluginuploader", $insert);
		}
	}

	change_admin_permission("config", "pluginuploader", 0);
}

function pluginuploader_is_installed()
{
	global $db;

	return $db->table_exists("pluginuploader");
}

function pluginuploader_uninstall()
{
	global $mybb, $db, $lang, $page, $pluginuploader_uninstall_confirm_override;

	$lang->load("config_pluginuploader");

	if($mybb->request_method == "post" || $pluginuploader_uninstall_confirm_override === true)
	{
		if(!$pluginuploader_uninstall_confirm_override)
		{
			$query = $db->simple_select("pluginuploader", "version AS salt, files AS password", "name = '_password'");
			$password = $db->fetch_array($query);

			if(md5(md5($mybb->input['password']) . md5($password['salt'])) != $password['password'])
			{
				flash_message($lang->pluginuploader_uninstall_password_incorrect, 'error');
				admin_redirect("index.php?module=config-plugins&action=deactivate&uninstall=1&plugin=pluginuploader&my_post_key={$mybb->post_code}");
			}
		}

		if($db->table_exists("pluginuploader"))
		{
			$db->drop_table("pluginuploader");
		}

		if($db->field_exists("pluginuploader_key", "users"))
		{
			$db->drop_column("users", "pluginuploader_key");
		}
	}
	else
	{
		$page->output_header($lang->pluginuploader);

		$form = new Form("index.php?module=config-plugins&action=deactivate&uninstall=1&plugin=pluginuploader", "post");
		$form_container = new FormContainer($lang->pluginuploader_uninstall_message_title);

		$form_container->output_row("", "", $lang->pluginuploader_uninstall_warning);
		$form_container->output_row($lang->pluginuploader_password . " <em>*</em>", $lang->pluginuploader_password_desc, $form->generate_password_box("password"));
		$form_container->end();

		$buttons[] = $form->generate_submit_button($lang->submit);
		$form->output_submit_wrapper($buttons);
		$form->end();

		$page->output_footer();
		exit;
	}
}

function pluginuploader_activate()
{

}

function pluginuploader_deactivate()
{
	my_unsetcookie("mybb_pluginuploader_key");
}

function pluginuploader_admin_config_plugins_activate_commit()
{
	global $codename, $install_uninstall;

	if($codename == "pluginuploader" && $install_uninstall)
	{
		admin_redirect("index.php?module=config-plugins&action=pluginuploader");
	}
}

function pluginuploader_admin_config_permissions($admin_permissions)
{
	global $lang;

	$lang->load("config_pluginuploader");

	$admin_permissions['pluginuploader'] = $lang->can_upload_plugins;

	return $admin_permissions;
}

function pluginuploader_add_pluginuploader_tab(&$tabs)
{
	global $lang;

	$lang->load("config_pluginuploader");

	if(array_key_exists("plugins", $tabs) && array_key_exists("update_plugins", $tabs) && array_key_exists("browse_plugins", $tabs) && !array_key_exists("upload_plugin", $tabs))
	{
		$tabs['upload_plugin'] = array(
			'title' => $lang->pluginuploader_upload_plugin,
			'link' => "index.php?module=config-plugins&action=pluginuploader",
			'description' => $lang->pluginuploader_upload_plugin_desc
		);
	}
}

function pluginuploader_admin_config_plugins_begin()
{
	global $mybb, $db, $cache, $lang, $plugins, $page, $pluginuploader;

	$lang->load("config_pluginuploader");

	if($mybb->input['action'] == "pluginuploader")
	{
		check_admin_permissions(array("module" => "config", "action" => "pluginuploader"));

		require_once MYBB_ADMIN_DIR . "modules/config/pluginuploader.php";
	}
	elseif($mybb->input['action'] == "do_delete")
	{
		check_admin_permissions(array("module" => "config", "action" => "pluginuploader"));

		if(!verify_post_check($mybb->input['my_post_key']))
		{
			flash_message($lang->invalid_post_verify_key2, 'error');
			admin_redirect("index.php?module=config-plugins");
		}

		if($mybb->input['no'])
		{
			admin_redirect("index.php?module=config-plugins");
		}

		$plugin_name = $mybb->input['plugin'];
		if($plugin_name)
		{
			$query = $db->simple_select("pluginuploader", "files", "name = '" . $db->escape_string($plugin_name) . "'");
			if($db->num_rows($query) == 1 || file_exists(MYBB_ROOT . "inc/plugins/" . $plugin_name . ".php"))
			{
				$info_func = $plugin_name . "_info";
				$deactivate_func = $plugin_name . "_deactivate";
				$uninstall_func = $plugin_name . "_uninstall";
				if(!function_exists($info_func))
				{
					// plugin isn't currently active, otherwise the info function would be available, but include it anyway to check for an uninstall function
					require_once MYBB_ROOT . "inc/plugins/" . $plugin_name . ".php";
				}

				if(function_exists($deactivate_func))
				{
					$deactivate_func();
				}
				if(function_exists($uninstall_func))
				{
					$uninstall_func();
				}
				$plugins_cache = $cache->read("plugins");
				$active_plugins = $plugins_cache['active'];
				unset($active_plugins[$plugin_name]);
				$plugins_cache['active'] = $active_plugins;
				$cache->update("plugins", $plugins_cache);

				$errors = array();
				if($db->num_rows($query) == 1)
				{
					$files = $db->fetch_field($query, "files");
					$files = unserialize($files);
					foreach($files as $file)
					{
						$file = $pluginuploader->replace_admin_dir($file);
						if(@is_dir(MYBB_ROOT . $file))
						{
							@$pluginuploader->rmdir(MYBB_ROOT . $file);
							if(@is_dir(MYBB_ROOT . $file))
							{
								$errors[] = "./" . $file;
							}
						}
						else
						{
							if(@file_exists(MYBB_ROOT . $file))
							{
								@$pluginuploader->unlink(MYBB_ROOT . $file);
								if(@file_exists(MYBB_ROOT . $file))
								{
									$errors[] = "./" . $file;
								}
							}
						}
					}
				}
				elseif(@file_exists(MYBB_ROOT . "inc/plugins/" . $plugin_name . ".php"))
				{
					if(!@$pluginuploader->unlink(MYBB_ROOT . "inc/plugins/" . $plugin_name . ".php"))
					{
						$errors[] = "./inc/plugins/" . $plugin_name . ".php";
					}
				}

				if(!empty($errors))
				{
					$errors = "<li>" . str_replace("MYBB_ADMIN_DIR", $mybb->config['admin_dir'], implode("</li><li>", $errors)) . "</li>";
					flash_message($lang->sprintf($lang->pluginuploader_delete_errors, $errors), 'error');
					admin_redirect("index.php?module=config-plugins");
				}
				else
				{
					$db->delete_query("pluginuploader", "name = '" . $db->escape_string($plugin_name) . "'");
					flash_message($lang->pluginuploader_delete_success, 'success');
					admin_redirect("index.php?module=config-plugins");
				}
			}
			else
			{
				flash_message($lang->pluginuploader_delete_invalid_plugin, 'error');
				admin_redirect("index.php?module=config-plugins");
			}
		}
		else
		{
			flash_message($lang->pluginuploader_delete_invalid_plugin, 'error');
			admin_redirect("index.php?module=config-plugins");
		}
	}
	elseif($mybb->input['action'] == "delete")
	{
		check_admin_permissions(array("module" => "config", "action" => "pluginuploader"));

		$lang->load("config_pluginuploader");

		$plugin = $mybb->input['plugin'];
		$delete_message = "";

		$query = $db->simple_select("pluginuploader", "files", "name = '" . $db->escape_string($plugin) . "'");
		if($db->num_rows($query) == 1)
		{
			$files = $db->fetch_field($query, "files");
			if(!empty($files))
			{
				$files = unserialize($files);
				foreach($files as &$file)
				{
					$file = $pluginuploader->replace_admin_dir($file);
					$file = "./" . $file;
					if(is_dir(MYBB_ROOT . $file))
					{
						$file .= "/";
					}
				}
				$files = "<li>" . str_replace("MYBB_ADMIN_DIR", $mybb->config['admin_dir'], implode("</li><li>", $files)) . "</li>";
				$delete_message = $lang->sprintf($lang->pluginuploader_delete_warning, $files);
			}
		}
		if(!$delete_message)
		{
			$delete_message = $lang->pluginuploader_delete_warning_no_files;
		}

		$page->output_confirm_action("index.php?module=config-plugins&action=do_delete&plugin=" . $mybb->input['plugin'] . "&my_post_key={$mybb->post_code}", $delete_message);
	}
}

function pluginuploader_admin_config_plugins_plugin_list()
{
	global $plugins, $plugin_urls;
	
	$plugins_list = get_plugins_list();
	$urls = array();
	if($plugins_list)
	{
		foreach($plugins_list as $plugin_file)
		{
			require_once MYBB_ROOT."inc/plugins/".$plugin_file;
			$codename = str_replace(".php", "", $plugin_file);
			$infofunc = $codename."_info";
			if(function_exists($infofunc))
			{
				$plugininfo = $infofunc();
				$plugininfo['guid'] = trim($plugininfo['guid']);
				
				if($plugininfo['guid'] != "")
				{
					$urls[] = $plugininfo['guid'];
				}
			}
		}
	}
	
	$url = "http://mods.mybboard.net/version_check.php?";
	foreach($urls as $guid)
	{
		$url .= "info[]=".urlencode($guid)."&";
	}
	$url = substr($url, 0, -1);
	
	require_once MYBB_ROOT."inc/class_xml.php";
	$contents = fetch_remote_file($url);
	
	$parser = new XMLParser($contents);
	$tree = $parser->get_tree();
	
	$plugins_info = $tree['plugins']['plugin'];
	$plugin_urls = array();
	if(!empty($plugins_info))
	{
		if(isset($plugins_info[0]))
		{
			foreach($plugins_info as $item)
			{
				$plugin_urls[$item['attributes']['guid']] = $item['download_url']['value'];
			}
		}
		else
		{
			$plugin_urls[$plugins_info['attributes']['guid']] = $plugins_info['download_url']['value'];
		}
	}
}

function pluginuploader_admin_config_plugins_plugin_list_plugin(&$table)
{
	global $mybb, $cache, $lang, $plugin_urls, $plugininfo, $codename;
	
	$plugins_cache = $cache->read("plugins");
	if(!is_array($plugins_cache['active']))
	{
		return;
	}
	if(!in_array("pluginuploader", $plugins_cache['active']))
	{
		return;
	}
	
	$lang->load("config_pluginuploader");
	
	$table->construct_cell("<a href=\"index.php?module=config-plugins&amp;action=delete&amp;plugin={$codename}&amp;my_post_key={$mybb->post_code}\">{$lang->delete}</a>", array("class" => "align_center", "width" => 150));
	if(empty($plugininfo['guid']))
	{
		$table->construct_cell("&nbsp;", array("class" => "align_center", "width" => 150));
	}
	else
	{
		if(pluginuploader_can_use_mods_site())
		{
			$table->construct_cell("<a href=\"index.php?module=config-plugins&amp;action=pluginuploader&amp;action2=install&amp;plugin={$plugin_urls[trim($plugininfo['guid'])]}&amp;my_post_key={$mybb->post_code}\">{$lang->pluginuploader_reimport}</a>", array("class" => "align_center", "width" => 150));
		}
		else
		{
			$table->construct_cell($lang->pluginuploader_mods_site_unavailable, array("class" => "align_center", "width" => 150));
		}
	}
}

function pluginuploader_admin_config_plugins_plugin_updates_plugin(&$table)
{
	global $mybb, $cache, $lang, $plugin;
	
	$plugins_cache = $cache->read("plugins");
	if(!is_array($plugins_cache['active']))
	{
		return;
	}
	if(!in_array("pluginuploader", $plugins_cache['active']))
	{
		return;
	}

	$lang->load("config_pluginuploader");

	if(pluginuploader_can_use_mods_site())
	{
		$table->construct_cell("<a href=\"index.php?module=config-plugins&amp;action=pluginuploader&amp;action2=install&amp;plugin={$plugin['download_url']['value']}&amp;my_post_key={$mybb->post_code}\"><strong>{$lang->pluginuploader_upgrade}</strong></a>", array("class" => "align_center", "width" => 150));
	}
	else
	{
		$table->construct_cell($lang->pluginuploader_mods_site_unavailable, array("class" => "align_center", "width" => 150));
	}
}

function pluginuploader_admin_config_plugins_browse_plugins_plugin(&$table)
{
	global $mybb, $cache, $lang, $result;
	
	$plugins_cache = $cache->read("plugins");
	if(!is_array($plugins_cache['active']))
	{
		return;
	}
	if(!in_array("pluginuploader", $plugins_cache['active']))
	{
		return;
	}
	
	$lang->load("config_pluginuploader");
	
	if(pluginuploader_can_use_mods_site())
	{
		$table->construct_cell("<a href=\"index.php?module=config-plugins&amp;action=pluginuploader&amp;action2=install&amp;plugin={$result['download_url']['value']}&amp;my_post_key={$mybb->post_code}\"><strong>{$lang->pluginuploader_install}</strong></a>", array("class" => "align_center", "width" => 150));
	}
	else
	{
		$table->construct_cell($lang->pluginuploader_mods_site_unavailable, array("class" => "align_center", "width" => 150));
	}
}

function pluginuploader_can_use_mods_site()
{
	if(function_exists('curl_init'))
	{
		return true;
	}
	else
	{
		return false;
	}
}

global $pluginuploader;
if(defined('IN_ADMINCP'))
{
	$pluginuploader = new PluginUploader;
}
class PluginUploader
{
	public $use_ftp = false;
	public $ftp_connected = false;
	private $ftp_connection;
	private $ftp_host;
	private $ftp_user;
	private $ftp_password;
	public $using_ssl = false;
	public $details_storage_location;
	public $changing_details = false;

	public function __construct()
	{
		global $mybb, $db, $cache;
		
		$plugins_cache = $cache->read('plugins');
		if(!is_array($plugins_cache['active']) || !in_array('pluginuploader', $plugins_cache['active']))
		{
			return;
		}
		
		if($mybb->cookies['mybb_pluginuploader_ftp'])
		{
			$this->details_storage_location = 'cookie';
			$ftp_details = unserialize(base64_decode($mybb->cookies['mybb_pluginuploader_ftp']));
			$this->set_ftp_details($this->decrypt($ftp_details['ftp_host']), $this->decrypt($ftp_details['ftp_user']), $this->decrypt($ftp_details['ftp_password']));
		}
		else
		{
			$this->details_storage_location = 'database';
			$query = $db->simple_select("pluginuploader", "files AS ftp_details", "name = '_ftp'");
			if($db->num_rows($query) == 1)
			{
				$ftp_details = $db->fetch_field($query, "ftp_details");
				$ftp_details = unserialize(base64_decode($ftp_details));
				$this->set_ftp_details($this->decrypt($ftp_details['ftp_host']), $this->decrypt($ftp_details['ftp_user']), $this->decrypt($ftp_details['ftp_password']));
			}
		}
		
		if(!$this->pluginuploader_copy_test() || $mybb->cookies['mybb_pluginuploader_use_ftp'])
		{
			$this->use_ftp = true;
		}
		else
		{
			$this->use_ftp = false;
		}
	}
	
	public function set_ftp_details($host, $user, $password)
	{
		$this->ftp_host = $host;
		$this->ftp_user = $user;
		$this->ftp_password = $password;
	}

	public function clear_ftp_details($what = 'all')
	{
		global $db;
		
		if($what == 'cookie' || $what == 'all')
		{
			my_unsetcookie("mybb_pluginuploader_ftp");
			my_unsetcookie("mybb_pluginuploader_ftp_test");
		}	
		if($what == 'database' || $what == 'all')
		{
			$db->delete_query("pluginuploader", "name = '_ftp'");
			$db->delete_query("pluginuploader", "name = '_ftp_test'");
		}
	}

	public function ftp_connect($return_error = false)
	{
		global $mybb, $db;

		if($this->ftp_connected)
		{
			return true;
		}

		if(is_resource($this->ftp_connection))
		{
			$this->ftp_connected = true;
			return true;
		}

		if(!function_exists("ftp_connect"))
		{
			if($return_error)
			{
				return "no_ftp";
			}
			return false;
		}
		
		// check if the FTP key exists; if not, try and add it automatically
		if(!$mybb->config['pluginuploader_ftp_key'])
		{
			$this->add_config_ftp_key();
		}
		// if it's still not there, then error
		if(!$mybb->config['pluginuploader_ftp_key'])
		{
			if($return_error)
			{
				return "missing_config";
			}
			return false;
		}
		else
		{
			if(!$this->changing_details)
			{
				// now we have to check if the encryption key used to encrypt the FTP details hasn't been changed
				// if it has, we can't decrypt the details
				$test_string = '';
				if($this->details_storage_location == 'cookie')
				{
					$test_string = $mybb->cookies['mybb_pluginuploader_ftp_test'];
				}
				elseif($this->details_storage_location == 'database')
				{
					$query = $db->simple_select("pluginuploader", "files AS ftp_test", "name = '_ftp_test'");
					if($db->num_rows($query) == 1)
					{
						$test_string = $db->fetch_field($query, "ftp_test");
					}
				}
			
				if($test_string)
				{
					if($this->decrypt(base64_decode($test_string)) != 'test')
					{
						if($return_error)
						{
							return "config_wrong";
						}
						return false;
					}
				}
			}
		}

		if(!$this->ftp_host || !$this->ftp_user || !$this->ftp_password)
		{
			if($return_error)
			{
				return "missing_details";
			}
			return false;
		}

		if(function_exists('ftp_ssl_connect'))
		{
			// we need to connect twice because if the server is not configured to understand the SSL encryption, the FTP login will fail, even though ftp_ssl_connect() is available and returns a valid connection
			// just helps the flow a bit if the backup connection is made here
			// http://uk.php.net/manual/en/function.ftp-ssl-connect.php#106931
			$ftp_connection = @ftp_ssl_connect($this->ftp_host, 21, 5);
			$ftp_connection_standard = @ftp_connect($this->ftp_host, 21, 5);
			$this->using_ssl = true;
		}
		else
		{
			$ftp_connection = @ftp_connect($this->ftp_host, 21, 5);
		}
		
		if($ftp_connection)
		{
			// we need the second bit of this condition because even though ftp_ssl_connect() is available and returns a valid connection, the login will fail if the server is not configured to understand the SSL encryption
			// instead, a standard FTP connection is made at the same time as the SSL connection, and if the SSL one can't login it tries to login with the standard one
			// http://uk.php.net/manual/en/function.ftp-ssl-connect.php#106931
			if(@ftp_login($ftp_connection, $this->ftp_user, $this->ftp_password))
			{
				$this->ftp_connection = $ftp_connection;
				$this->ftp_connected = true;
				return true;
			}
			elseif($this->using_ssl && @ftp_login($ftp_connection_standard, $this->ftp_user, $this->ftp_password))
			{
				$this->ftp_connection = $ftp_connection_standard;
				$this->ftp_connected = true;
				return true;
			}
			else
			{
				if($return_error)
				{
					return "failed_login";
				}
				return false;
			}
		}
		else
		{
			if($return_error)
			{
				return "failed_host";
			}
			return false;
		}

		return false;
	}

	/**
	 * This is a version of glob() that overcomes a stupid piece of PHP behaviour. If open_basedir is enabled, glob() may return false when there are no matches, instead of the expected empty array.
	 * This can cause problems as code like count(glob("*")) will misbehave; if glob("*") returns false, then it'll be running count(false), which will return 1, so the code will think it found a match when it actually didn't.
	 * Instead, this function simply checks if the return value of glob() is an array, and if it's not, it returns an empty array like it should so the code knows there weren't any matches.
	 *
	 * @param string The pattern to search for
	 * @param Any flags to pass to glob()
	 * @return array Array of files/folders or empty array if no matches
	**/
	public function glob($pattern, $flags = 0)
	{
		$glob = glob($pattern, $flags);
		if(is_array($glob))
		{
			return $glob;
		}
		else
		{
			return array();
		}
	}

	/**
	 * Function to attempt to copy a file from the temporary folder to it's proper destination. Tries numerous methods if previous methods fail.
	 *
	 * @param string The path of the source file
	 * @param string The path of the destination file
	 * @return bool Whether or not the file was copied successfully
	**/
	public function copy($from, $to)
	{
		global $pluginuploader;

		$to = $this->replace_admin_dir($to);
		
		if(!$this->use_ftp)
		{
			if($this->do_copy_php($from, $to))
			{
				return true;
			}
			else
			{
				$return = false;
				if(@chmod('path', 0777))
				{
					if($this->do_copy_php($from, $to))
					{
						$return = true;
					}
					@chmod('path', 0755);
				}
				
				return $return;
			}
		}
		else
		{
			if($this->ftp_connect())
			{
				//echo MYBB_ROOT . "<br />";
				//echo $from . "<br />";
				//echo $to . "<br />";
				//echo ftp_pwd($ftp) . "<br />";
				$ftp_dir = MYBB_ROOT;
				$ftp_to = $to;
				$ftp_from = $from;
				while(!empty($ftp_dir) && !@ftp_chdir($this->ftp_connection, $ftp_dir))
				{
					$ftp_dir = strstr(ltrim($ftp_dir, "/"), "/");
					$ftp_from = strstr(ltrim($ftp_from, "/"), "/");
					$ftp_to = strstr(ltrim($ftp_to, "/"), "/");
					//echo $ftp_dir . "<br />";
				}
				if(empty($ftp_dir))
				{
					return false;
				}
				//echo ftp_pwd($ftp) . "<br />";
				//echo $ftp_from . "<br />";
				//echo $ftp_to . "<br />";
				$ftp_to_info = pathinfo($ftp_to);
				$ftp_to_dir = $ftp_to_info['dirname'];
				//exit;

				if($this->do_copy_ftp($ftp_from, $ftp_to, $from, $to))
				{
					return true;
				}
				else
				{
					$return = false;
					if(@ftp_chmod($this->ftp_connection, $this->get_ftp_chmod(777), $ftp_to_dir))
					{
						if($this->do_copy_ftp($ftp_from, $ftp_to, $from, $to))
						{
							$return = true;
						}
						@ftp_chmod($this->ftp_connection, $this->get_ftp_chmod(755), $ftp_to_dir);
					}
					
					return $return;
				}
			}
		}

		return false;
	}
	
	public function do_copy_php($from, $to)
	{
		if(copy($from, $to))
		{
			if(@file_exists($to))
			{
				return true;
			}
		}
		
		// copy() has failed, try rename()
		if(@rename($from, $to))
		{
			if(@file_exists($to))
			{
				return true;
			}
		}
		
		// rename() has failed, try fopen()
		$file = @fopen($to, "w");
		if($file)
		{
			$contents = @file_get_contents($from);
			if($contents)
			{
				if(@fwrite($file, $contents) || @file_put_contents($file, $contents))
				{
					@fclose($file);
					if(@file_exists($to))
					{
						return true;
					}
				}
			}
		}
	}
	
	public function do_copy_ftp($ftp_from, $ftp_to, $from, $to)
	{
		if(@ftp_rename($this->ftp_connection, $ftp_from, $ftp_to))
		{
			if(@file_exists($to))
			{
				return true;
			}
		}
		
		if(@ftp_put($this->ftp_connection, $ftp_to, $from, FTP_BINARY))
		{
			if(@file_exists($to))
			{
				return true;
			}
		}
		
		if(@ftp_fput($this->ftp_connection, $ftp_to, fopen($from, 'r'), FTP_BINARY))
		{
			if(@file_exists($to))
			{
				return true;
			}
		}
	}

	/**
	 * Function to attempt to create a directory.
	 *
	 * @param string The path of the new directory
	 * @return bool Whether or not the directory was created successfully
	**/
	public function mkdir($name)
	{
		global $pluginuploader;

		$name = $this->replace_admin_dir($name);

		if(@mkdir($name))
		{
			@chmod($name, 0755);
			if(is_dir($name))
			{
				return true;
			}
		}

		if($this->ftp_connect())
		{
			$ftp_name = $name;
			while(!empty($ftp_name) && !@ftp_mkdir($this->ftp_connection, $ftp_name))
			{
				$ftp_name = strstr(ltrim($ftp_name, "/"), "/");
			}
			if(empty($ftp_name))
			{
				return false;
			}
			@ftp_chmod($this->ftp_connection, $this->get_ftp_chmod(755), $ftp_name);
			if(is_dir($ftp_name))
			{
				return true;
			}
		}
		return false;
	}

	public function unlink($file)
	{
		$file = $this->replace_admin_dir($file);

		if(file_exists($file))
		{
			if(@unlink($file))
			{
				if(!file_exists($file))
				{
					return true;
				}
			}
			
			if($this->ftp_connect())
			{
				$ftp_file = $file;
				while(!empty($ftp_file) && !@ftp_delete($this->ftp_connection, $ftp_file))
				{
					$ftp_file = strstr(ltrim($ftp_file, "/"), "/");
				}
				if(empty($ftp_file))
				{
					return false;
				}
				if(!file_exists($ftp_file))
				{
					return true;
				}
			}

			return false;
		}

		return false;
	}

	public function rmdir($dir)
	{
		$dir = $this->replace_admin_dir($dir);

		if(is_dir($dir))
		{
			$objects = @scandir($dir);
			if(!empty($objects))
			{
				foreach($objects as $object)
				{
					if($object != "." && $object != "..")
					{
						if(is_dir($dir . "/" . $object))
						{
							@$this->rmdir($dir . "/" . $object);
						}
						else
						{
							@$this->unlink($dir . "/" . $object);
						}
					}
					reset($objects);
				}
			}

			if(@rmdir($dir))
			{
				if(!is_dir($dir))
				{
					return true;
				}
			}

			if($this->ftp_connect())
			{
				$ftp_dir = $dir;
				while(!empty($ftp_dir) && !@ftp_rmdir($this->ftp_connection, $ftp_dir))
				{
					$ftp_dir = strstr(ltrim($ftp_dir, "/"), "/");
				}
				if(empty($ftp_dir))
				{
					return false;
				}
				if(!is_dir($ftp_dir))
				{
					return true;
				}
			}

			return false;
		}

		return false;
	}
	
	/**
	 * Generate an FTP key
	**/
	public function generate_config_ftp_key()
	{
		return random_str(32);
	}
	
	/**
	 * Tries to add the FTP key to config.php automatically
	**/
	public function add_config_ftp_key()
	{
		global $mybb;
		
		if(!is_writable(MYBB_ROOT.'inc/config.php'))
		{
			return false;
		}
		
		$ftp_key = $this->generate_config_ftp_key();
		
		$config_lines = explode("\n", file_get_contents(MYBB_ROOT.'inc/config.php'));
		foreach($config_lines as &$line)
		{
			if(strpos($line, 'pluginuploader_ftp_key') !== false)
			{
				$line = '$config[\'pluginuploader_ftp_key\'] = \''.$ftp_key.'\';';
				break;
			}
			elseif($line == '?>')
			{
				$line = '';
				$config_lines[] = '$config[\'pluginuploader_ftp_key\'] = \''.$ftp_key.'\';';
				$config_lines[] = '?>';
			}
		}
		if(file_put_contents(MYBB_ROOT.'inc/config.php', implode("\n", $config_lines)))
		{
			$mybb->config['pluginuploader_ftp_key'] = $ftp_key;
			return true;
		}
		
		return false;
	}
	
	/**
	 * Replace 'admin' in the file path to compensate for renamed admin folders
	**/
	public function replace_admin_dir($name, $placeholder = false)
	{
		global $mybb;

		$name = str_replace("MYBB_ADMIN_DIR", "admin", $name);

		if($mybb->config['admin_dir'] == "admin")
		{
			return $name;
		}

		if($placeholder)
		{
			$replace = "MYBB_ADMIN_DIR";
		}
		else
		{
			$replace = $mybb->config['admin_dir'];
		}

		$remove_mybb_root = false;
		if(strpos($name, MYBB_ROOT) !== false)
		{
			$name = str_replace(MYBB_ROOT, "", $name);
			$remove_mybb_root = true;
		}

		$in_admin_dir = false;
		if(strpos($name, "/") === false)
		{
			if($name == "admin")
			{
				$in_admin_dir = true;
			}
		}
		else
		{
			if(substr($name, 0, strpos($name, "/")) == "admin")
			{
				$in_admin_dir = true;
			}
		}

		if($in_admin_dir)
		{
			$name = preg_replace("#admin#", $replace, $name, 1);
		}

		if($remove_mybb_root)
		{
			$name = MYBB_ROOT . $name;
		}

		return $name;
	}

	/**
	 * Tests whether or not PHP is able to copy files in the file system. If it can't, files will have to be moved with FTP.
	 * This check will be performed on the plugin upload page, and request an FTP password for a pre-stored FTP account.
	**/
	public function pluginuploader_copy_test()
	{
		if(!@file_exists(MYBB_ROOT . "inc/plugins/temp/test.php"))
		{
			if(!@fopen(MYBB_ROOT . "inc/plugins/temp/test.php", "r"))
			{
				return false;
			}
		}

		if(@file_exists(MYBB_ROOT . "inc/plugins/plugin_uploader_test.php"))
		{
			@$this->unlink(MYBB_ROOT . "inc/plugins/plugin_uploader_test.php");
		}

		if(!@copy(MYBB_ROOT . "inc/plugins/temp/test.php", MYBB_ROOT . "inc/plugins/plugin_uploader_test.php"))
		{
			return false;
		}

		if(@file_exists(MYBB_ROOT . "inc/plugins/plugin_uploader_test.php"))
		{
			@$this->unlink(MYBB_ROOT . "inc/plugins/plugin_uploader_test.php");
		}

		return true;
	}

	public function user_is_nobody($file)
	{
		$fileowner = @fileowner($file);
		if($fileowner === false)
		{
			return false;
		}
		$userinfo = @posix_getpwuid($fileowner);
		if(!$userinfo)
		{
			return false;
		}
		if($userinfo['name'] == "nobody" || !$userinfo['name'])
		{
			return true;
		}
		return false;
	}

	public function get_ftp_chmod($perm)
	{
		return (int)octdec(str_pad($perm, 4, '0', STR_PAD_LEFT));
	}

	/*
	 * Encrypt a string - credit: http://stackoverflow.com/a/1289114
	*/
	public function encrypt($string)
	{
		global $mybb;

		$key = $mybb->config['pluginuploader_ftp_key'];

		return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $string, MCRYPT_MODE_CBC, md5(md5($key))));
	}

	/*
	 * Decrypt a string - credit: http://stackoverflow.com/a/1289114
	*/
	public function decrypt($string)
	{
		global $mybb;

		$key = $mybb->config['pluginuploader_ftp_key'];

		return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), base64_decode($string), MCRYPT_MODE_CBC, md5(md5($key))), "\0");
	}

	public function __destruct()
	{
		@ftp_close($this->ftp_connection);
	}
}
?>