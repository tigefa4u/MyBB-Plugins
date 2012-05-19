<?php
/**
 * Plugin Uploader 1.1 - Admin File

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

/**
 * ***** IMPORTANT *****
 * This plugin allows you to import a plugin and have the files extracted to their proper locations. However, it is impossible for the uploader to check whether the files in the zip are an actual plugin, or malicious files intended to do harm to your forum. Because of this, it is physically possible for someone who gains admin access to upload potentially malicious files to your server via the plugin uploader. This is not a flaw in the plugin uploader itself, but merely an unavoidable flaw in the concept of allowing plugin files to be uploaded and imported via the Admin Control Panel rather than via FTP. For this reason, a password is required to be entered when uploading a plugin. This is so that even if somebody gains access to an admin account that has the ability to upload plugins, they will not be able to upload a plugin as they will not know the password.
 * It is possible to disable this password check by changing false below to true
 * THIS SHOULD _ONLY_ BE USED ON DEVELOPMENT/LOCALHOST FORUMS
 * THIS SHOULD _NOT_ BE USED ON LIVE FORUMS
 * If you disable the password on a live forum, and someone gains access to your admin account and then uploads files to your file system and hacks your forum or server, it will be your own fault, please do not come crying to me.
 * The password is there to try and offer you some degree of protection, if you choose to disable it, on your head be it.
**/
define("DISABLE_PLUGINUPLOADER_PASSWORD", false);

global $pluginuploader, $admin_session;

$page->add_breadcrumb_item($lang->pluginuploader, "index.php?module=config-plugins&action=pluginuploader");

if($mybb->input['action2'] == "do_upload")
{
	// put it this way round so the code is in the correct order
	if($mybb->input['do'] != "import")
	{
		if(!verify_post_check($mybb->input['my_post_key']))
		{
			flash_message($lang->invalid_post_verify_key2, 'error');
			admin_redirect("index.php?module=config-plugins&action=pluginuploader");
		}
		// we're importing a plugin that's already active, so we've had to deactivate it
		if($mybb->input['do_deactivate'] == 1)
		{
			if($mybb->input['deactivate'] == 1)
			{
				$path = $mybb->input['path'];
				$root = $mybb->input['root'];
				$plugin_file = $mybb->input['plugin_file'];
				$plugin_name = $mybb->input['plugin_name'];
				$plugin_temp_name = $mybb->input['plugin_temp_name'];
				
				$plugins_cache = $cache->read("plugins");
				$active_plugins = $plugins_cache['active'];
				
				$deactivate_function = $plugin_name . "_deactivate";
				if(function_exists($deactivate_function))
				{
					$deactivate_function();
				}
				
				unset($active_plugins[$plugin_name]);
				$plugins_cache['active'] = $active_plugins;
				$cache->update("plugins", $plugins_cache);
				
				// need to put this in the URL as we need to reload the page so this plugin won't be loaded meaning we can include the new version of the file
				// base64 it just so it's a bit neater
				admin_redirect("index.php?module=config-plugins&action=pluginuploader&action2=do_upload&skip_search=1&path=" . base64_encode($path) . "&root=" . base64_encode($root) . "&plugin_file=" . base64_encode($plugin_file) . "&plugin_name=" . base64_encode($plugin_name) . "&plugin_temp_name=" . base64_encode($plugin_temp_name) . "&my_post_key=" . $mybb->post_code);
			}
			else
			{
				admin_redirect("index.php?module=config-plugins");
			}
		}
		// if we've just deactivated, we already have the main information we need, so we can get it from the URL and continue as normal
		elseif($mybb->input['skip_search'] == 1)
		{
			$path = base64_decode($mybb->input['path']);
			$root = base64_decode($mybb->input['root']);
			$plugin_file = base64_decode($mybb->input['plugin_file']);
			$plugin_name = base64_decode($mybb->input['plugin_name']);
			$plugin_temp_name = base64_decode($mybb->input['plugin_temp_name']);
		}
		else
		{
			if(!DISABLE_PLUGINUPLOADER_PASSWORD && $mybb->input['from_mods_site'] != 1)
			{
				$query = $db->simple_select("pluginuploader", "version AS salt, files AS password", "name = '_password'");
				$password = $db->fetch_array($query);
				
				if($password['password'])
				{
					$password_passed = false;
					
					if($mybb->cookies['mybb_pluginuploader_key'] && $mybb->cookies['mybb_pluginuploader_key'] == $mybb->user['uid']."_".$mybb->user['pluginuploader_key'])
					{
						$password_passed = true;
					}
					elseif(md5(md5($mybb->input['password']) . md5($password['salt'])) == $password['password'])
					{
						if($mybb->input['password_remember'] == 1)
						{
							if(!$db->field_exists("pluginuploader_key", "users"))
							{
								$db->add_column("users", "pluginuploader_key", "VARCHAR(120)");
							}
							
							$pluginuploader_key = random_str(32);
							$update = array(
								"pluginuploader_key" => $db->escape_string($pluginuploader_key)
							);
							$db->update_query("users", $update, "uid = '" . $mybb->user['uid'] . "'");
							
							my_setcookie("mybb_pluginuploader_key", $mybb->user['uid']."_".$pluginuploader_key);
						}
						$password_passed = true;
					}
					
					if(!$password_passed)
					{
						flash_message($lang->pluginuploader_password_incorrect, 'error');
						admin_redirect("index.php?module=config-plugins&action=pluginuploader");
					}
				}
				else
				{
					// if we've got here but there's no password, go back to the plugin uploader page to show the warning
					admin_redirect("index.php?module=config-plugins&action=pluginuploader&action2=password");
				}
			}
			
			if($mybb->input['from_mods_site'] == 1)
			{
				$plugin_temp_name = $mybb->input['plugin_name'];
				$path = MYBB_ROOT . "inc/plugins/temp/" . $plugin_temp_name;
				$pathinfo = array('extension' => 'zip');
				$file_path = MYBB_ROOT . "inc/plugins/temp/" . $plugin_temp_name . ".zip";
			}
			else
			{
				if(isset($mybb->input['send_usage_stats']) && $mybb->input['send_usage_stats'] == 1)
				{
					my_setcookie('mybb_pluginuploader_send_usage_stats', 'yes');
				}
				else
				{
					my_setcookie('mybb_pluginuploader_send_usage_stats', 'no');
				}
				
				if(!empty($mybb->input['plugin_url']))
				{
					update_admin_session('pluginuploader_import_source', 'url');
					
					// check if it's a Mods Site URL
					$is_mods_site = preg_match('#mods.(mybb.com|mybboard.net)/(view|download)#', $mybb->input['plugin_url']);
					if($is_mods_site)
					{
						// if it is, get the name
						preg_match('#mods.(mybb.com|mybboard.net)/(view|download)/([a-zA-Z0-9\-]+)#', $mybb->input['plugin_url'], $plugin_url_info);
						if($plugin_url_info[3])
						{
							if($plugin_url_info[3] == "plugin-uploader")
							{
								flash_message($lang->pluginuploader_error_plugin_pluginuploader, 'error');
								admin_redirect("index.php?module=config-plugins&action=pluginuploader");
							}
							else
							{
								// go to the import page
								admin_redirect("index.php?module=config-plugins&action=pluginuploader&action2=install&plugin=".$plugin_url_info[3]."&my_post_key={$mybb->post_code}");
							}
						}
						// if there's not a valid name, strip out everything to just leave the name that was given and put it into the error message
						else
						{
							$plugin_name = str_replace(array('http://', '/'), '', preg_replace('#mods.(mybb.com|mybboard.net)/(view|download)#', '', $mybb->input['plugin_url']));
							flash_message($lang->sprintf($lang->pluginuploader_download_from_mods_invalid, $plugin_name), 'error');
							admin_redirect("index.php?module=config-plugins&action=pluginuploader");
						}
					}
					// if it's not, fetch the URL and save the output to a file
					else
					{
						$is_mybb_forum = false;
						$mybb_forum_url_info = pathinfo($mybb->input['plugin_url']);
						//echo '<pre>';print_r($mybb_forum_url_info);echo '</pre>';
						$mybb_forum_url = $mybb_forum_url_info['dirname'];
						if(substr($mybb_forum_url, -1) != '/')
						{
							$mybb_forum_url .= '/';
						}
						$is_mybb_forum_check = fetch_remote_file($mybb_forum_url.'?intcheck=1');
						// &#077;&#089;&#066;&#066; == MYBB
						if($is_mybb_forum_check == '&#077;&#089;&#066;&#066;')
						{
							$is_mybb_forum = true;
						}
						
						if($is_mybb_forum && $mybb->input['has_site_login'])
						{
							if(empty($mybb->input['site_login_username']) || empty($mybb->input['site_login_password']))
							{
								flash_message($lang->pluginuploader_from_url_site_login, 'error');
								admin_redirect("index.php?module=config-plugins&action=pluginuploader&get_site_login=1&plugin_url=".base64_encode($mybb->input['plugin_url']));
							}
							
							$fields = array(
								'username' => urlencode($mybb->input['site_login_username']),
								'password' => urlencode($mybb->input['site_login_password']),
								'action' => 'do_login'
							);
							foreach($fields as $key=>$value)
							{
								$fields_string .= $key.'='.$value.'&';
							}
							rtrim($fields_string, '&');
							$ch = curl_init();
							curl_setopt($ch,CURLOPT_URL,$mybb_forum_url.'/member.php');
							curl_setopt($ch,CURLOPT_HEADER,1);
							curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
							curl_setopt($ch,CURLOPT_POST,count($fields));
							curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);
							$result = curl_exec($ch);
							curl_close($ch);
							preg_match('#Set-Cookie: mybbuser=(.*?);#', $result, $mybbuser);
							$mybbuser = $mybbuser[1];
							if(!$mybbuser)
							{
								flash_message($lang->pluginuploader_from_url_site_login_error, 'error');
								admin_redirect("index.php?module=config-plugins&action=pluginuploader&get_site_login=1&plugin_url=".base64_encode($mybb->input['plugin_url']));
							}
						}
						
						global $request_headers;
						$request_headers = '';
						$ch = curl_init();
						curl_setopt($ch,CURLOPT_URL,$mybb->input['plugin_url']);
						curl_setopt($ch,CURLOPT_HEADER,1);
						curl_setopt($ch,CURLOPT_HEADERFUNCTION,'pluginuploader_get_headers');
						if($is_mybb_forum && $mybb->input['has_site_login'])
						{
							curl_setopt($ch,CURLOPT_COOKIE,'mybbuser='.$mybbuser);
						}
						curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
						@curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true);
						$result = curl_exec($ch);
						$info = curl_getinfo($ch);
						curl_close($ch);
						$result = str_replace($request_headers, '', $result);
						
						//echo '<pre>';print_r($request_headers);echo '</pre>';
						//echo '<pre>';print_r(explode("\n", htmlspecialchars($result)));echo '</pre>';
						//echo '<pre>';print_r($info);echo '</pre>';
						//exit;
						
						if($info['http_code'] == 200 && in_array($info['content_type'], array('application/force-download', 'application/zip')) && !empty($result))
						{
							$file_type = 'zip';
							if($info['content_type'] = 'application/force-download')
							{
								$exploded_result = explode("\n", trim($result));
								if(trim($exploded_result[0]) == '<?php')
								{
									$file_type = 'php';
								}
							}
							$pathinfo = array('extension' => $file_type);
							if($file_type == 'php')
							{
								preg_match('#filename="([a-zA-Z0-9]+).php"#', $request_headers, $filename);
								$plugin_temp_name = $filename[1];
								//echo '<pre>';print_r($filename);echo '</pre>';exit;
							}
							else
							{
								$plugin_temp_name = md5($mybb->input['plugin_url']);
							}
							$file_path = MYBB_ROOT . "inc/plugins/temp/" . $plugin_temp_name . ".".$file_type;
							$path = MYBB_ROOT . "inc/plugins/temp/" . $plugin_temp_name;
							@file_put_contents($file_path, $result);
						}
						else
						{
							if($is_mybb_forum)
							{
								$get_site_login = '';
								if(!$mybb->input['has_site_login'])
								{
									$get_site_login = '&get_site_login=1';
								}
								
								flash_message($lang->pluginuploader_from_url_login_required, 'error');
								admin_redirect("index.php?module=config-plugins&action=pluginuploader".$get_site_login."&plugin_url=".base64_encode($mybb->input['plugin_url']));
							}
							else
							{
								flash_message($lang->pluginuploader_from_url_no_file, 'error');
								admin_redirect("index.php?module=config-plugins&action=pluginuploader&plugin_url=".base64_encode($mybb->input['plugin_url']));
							}
						}
					}
				}
				else
				{
					$plugin_temp_name = $_FILES['plugin_file']['name'];
					$plugin_temp_name = substr($plugin_temp_name, 0, -4);
					$path = MYBB_ROOT . "inc/plugins/temp/" . $plugin_temp_name;
					$pathinfo = pathinfo($_FILES['plugin_file']['name']);
					$file_path = $_FILES['plugin_file']['tmp_name'];
				}
			}
			
			switch($pathinfo['extension'])
			{
				case "zip":
					if(!class_exists("ZipArchive"))
					{
						flash_message($lang->pluginuploader_error_no_ziparchive_class, 'error');
						admin_redirect("index.php?module=config-plugins&action=pluginuploader");
					}
					
					$zip = new ZipArchive;
					
					// try to open the zip
					if(!@$zip->open($file_path))
					{
						flash_message($lang->pluginuploader_error_upload, 'error');
						admin_redirect("index.php?module=config-plugins&action=pluginuploader");
					}
					
					// try to create a temporary directory for the files
					if(!pluginuploader_create_temp_dir($plugin_temp_name))
					{
						flash_message($lang->pluginuploader_error_temp_dir, 'error');
						admin_redirect("index.php?module=config-plugins&action=pluginuploader");
					}
					
					// try to extract the files to the temp directory
					if(!@$zip->extractTo($path))
					{
						flash_message($lang->pluginuploader_error_extract, 'error');
						admin_redirect("index.php?module=config-plugins&action=pluginuploader");
					}
					
					$zip->close();
					break;
				case "php":
					// try to create a temporary directory for the file
					if(!pluginuploader_create_temp_dir($plugin_temp_name))
					{
						flash_message($lang->pluginuploader_error_temp_dir, 'error');
						admin_redirect("index.php?module=config-plugins&action=pluginuploader");
					}
					
					if(!empty($mybb->input['plugin_url']))
					{
						if(!@copy($file_path, $path.'/'.$plugin_temp_name.'.php'))
						{
							flash_message($lang->pluginuploader_error_move_single_file, 'error');
							admin_redirect("index.php?module=config-plugins&action=pluginuploader");
						}
					}
					else
					{
						if(!@move_uploaded_file($file_path, $path."/".$_FILES['plugin_file']['name']))
						{
							flash_message($lang->pluginuploader_error_move_single_file, 'error');
							admin_redirect("index.php?module=config-plugins&action=pluginuploader");
						}
					}
					break;
				default:
					flash_message($lang->pluginuploader_invalid_type, 'error');
					admin_redirect("index.php?module=config-plugins&action=pluginuploader");
			}
			
			// find the file root for the actual plugin files
			$root = pluginuploader_find_root($path);
			//echo $root . "<br />";
			
			// if it couldn't work out where the plugin files were, show an error saying it can't import the plugin
			if($root == -1)
			{
				flash_message($lang->pluginuploader_error_path, 'error');
				admin_redirect("index.php?module=config-plugins&action=pluginuploader");
			}
			
			$plugin_file = pluginuploader_find_pluginfile($root);
			if(!$plugin_file)
			{
				flash_message($lang->pluginuploader_error_plugin_file, 'error');
				admin_redirect("index.php?module=config-plugins&action=pluginuploader");
			}
			
			$plugin_name = str_replace(array("inc/plugins/", ".php"), "", $plugin_file);
			// trying to upload this plugin; that's just not going to work
			if($plugin_name == "pluginuploader")
			{
				flash_message($lang->pluginuploader_error_plugin_pluginuploader, 'error');
				admin_redirect("index.php?module=config-plugins&action=pluginuploader");
			}
			$plugins_cache = $cache->read("plugins");
			// uh-oh, the plugin we're uploading is already activated and running
			// we need to have it deactivated because we need to include the new plugin file, and can't have the same functions defined twice
			if(in_array($plugin_name, $plugins_cache['active']))
			{
				$page->output_header($lang->pluginuploader);
				
				$form = new Form("index.php?module=config-plugins&action=pluginuploader&amp;action2=do_upload&do_deactivate=1", "post");
				$form_container = new FormContainer($lang->pluginuploader_upload_plugin);
				
				$form_container->output_row("", "", $lang->pluginuploader_deactivate_desc);
				$form_container->output_row($lang->pluginuploader_deactivate, "", $form->generate_yes_no_radio("deactivate", 1, true, array("checked" => 1)));
				$form_container->end();
				
				echo $form->generate_hidden_field("path", $path);
				echo $form->generate_hidden_field("root", $root);
				echo $form->generate_hidden_field("plugin_file", $plugin_file);
				echo $form->generate_hidden_field("plugin_name", $plugin_name);
				echo $form->generate_hidden_field("plugin_temp_name", $plugin_temp_name);
				
				$buttons[] = $form->generate_submit_button($lang->submit, array("id" => "submit"));
				$form->output_submit_wrapper($buttons);
				$form->end();
				
				$page->output_footer();
				exit;
			}
		}
		
		//echo $plugin_file . "<br />";
		//echo $plugin_name . "<br />";
		
		// the reason we need to do this is because we're going to be including the plugin file, which will run the default code to add hooks
		// if one of those hooks is run during the execution of the following code in _this_ plugin (e.g. something in the form/table generation), it's going to try and run the plugin we're including, which may break things, or give errors if language files don't exist etc
		// instead, we re-create the plugins class with a modified version of the add_hook() method, so when we include this plugin file and it runs $plugins->add_hook(), it'll do nothing
		// don't worry though, this only has an effect for this specific page (showing information on the plugin), nowhere else
		unset($plugins);
		// used to have this at the bottom, but there was a report of an error saying the class didn't exist... weird, but I'll just put it here instead
		class MyPluginSystem extends pluginSystem
		{
			function add_hook($hook, $function, $priority = 10, $file = '')
			{
				
			}
		}
		$plugins = new MyPluginSystem;
		
		// reluctantly, this has to go before the plugin file is included, in case the plugin file loads a language file in the file directly, and not in a function
		/*if(is_dir($root . "/inc/languages/english/"))
		{
			chdir($root . "/inc/languages/english/");
			$lang_files = @$pluginuploader->glob("*.lang.php");
			if(!empty($lang_files))
			{
				foreach($lang_files as $lang_file)
				{
					@$pluginuploader->copy($root . "/inc/languages/english/" . $lang_file, MYBB_ROOT . "inc/languages/english/" . $lang_file);
				}
			}
			
			if(is_dir($root . "/inc/languages/english/admin/"))
			{
				chdir($root . "/inc/languages/english/admin/");
				$admin_lang_files = @$pluginuploader->glob("*.lang.php");
				if(!empty($admin_lang_files))
				{
					foreach($admin_lang_files as $admin_lang_file)
					{
						@$pluginuploader->copy($root . "/inc/languages/english/admin/" . $admin_lang_file, MYBB_ROOT . "inc/languages/english/admin/" . $admin_lang_file);
					}
				}
			}
		}*/
		
		//var_dump(pluginuploader_has_external_files($root));
		// OK, we have the info function, we're definitely looking at the right file
		// check if this plugin adds it's own folder to the ./inc/plugins/ folder
		// this will most likely include other files which will be necessary for the info function to load
		if(pluginuploader_has_external_files($root))
		{
			//echo "<pre>";print_r($root);echo "</pre>";
			$external_files = pluginuploader_load_external_files($root . "/inc", $root);
			//echo "<pre>";print_r($external_files);echo "</pre>";
			
			if(!empty($external_files))
			{
				$errors = array();
				if(!empty($external_files['folders']))
				{
					foreach($external_files['folders'] as $external_folder)
					{
						if(!@$pluginuploader->mkdir(MYBB_ROOT . $external_folder['path']))
						{
							$errors[] = "./" . $external_folder['path'];
						}
					}
				}
				if(!empty($external_files['files']))
				{
					foreach($external_files['files'] as $external_file)
					{
						if(!@$pluginuploader->copy($external_file['full'], MYBB_ROOT . $external_file['relative']))
						{
							$errors[] = "./" . $external_file['relative'];
						}
					}
				}
				if(!empty($errors))
				{
					$errors = "<li>" . str_replace("MYBB_ADMIN_DIR", $mybb->config['admin_dir'], implode("</li><li>", $errors)) . "</li>";
					flash_message($lang->sprintf($lang->pluginuploader_error_move_external_files, $errors), 'error');
					admin_redirect("index.php?module=config-plugins&action=pluginuploader");
				}
			}
		}
		
		//echo "<pre>";print_r($root . "/" . $plugin_file);echo "</pre>";
		if(file_exists($root . "/" . $plugin_file))
		{
			require_once $root . "/" . $plugin_file;
		}
		
		$info_func = $plugin_name . "_info";
		if(!function_exists($info_func))
		{
			flash_message($lang->pluginuploader_error_plugin_file, 'error');
			admin_redirect("index.php?module=config-plugins&action=pluginuploader");
		}
		
		//echo "<pre>";print_r($external_files);echo "</pre>";exit;
		$info = $info_func();
		//echo "<pre>";print_r($info);echo "</pre>";
		
		$compatible = plugin_is_compatible($plugin_name, $root . "/" . $plugin_file);
		
		// only delete these files if the plugin didn't already exist; just in case they abort, it may stop fatal errors due to missing files
		if(!file_exists(MYBB_ROOT.'inc/plugins/'.$plugin_name.'.php'))
		{
			if(!empty($external_files))
			{
				if(!empty($external_files['folders']))
				{
					foreach($external_files['folders'] as $external_folder)
					{
						@$pluginuploader->rmdir(MYBB_ROOT . $external_folder['path']);
					}
				}
				if(!empty($external_files['files']))
				{
					foreach($external_files['files'] as $external_file)
					{
						@$pluginuploader->unlink(MYBB_ROOT . $external_file['relative']);
					}
				}
			}
			
			/*if(!empty($lang_files))
			{
				foreach($lang_files as $lang_files)
				{
					if(file_exists(MYBB_ROOT . "inc/languages/english/" . $lang_file))
					{
						@$pluginuploader->unlink(MYBB_ROOT . "inc/languages/english/" . $lang_file);
					}
				}
			}
			if(!empty($admin_lang_files))
			{
				foreach($admin_lang_files as $admin_lang_file)
				{
					if(file_exists(MYBB_ROOT . "inc/languages/english/admin/" . $admin_lang_file))
					{
						@$pluginuploader->unlink(MYBB_ROOT . "inc/languages/english/admin/" . $admin_lang_file);
					}
				}
			}*/
		}
		
		// we have to redirect if it isn't compatible after we've removed all the other files we moved before
		if(!$compatible)
		{
			flash_message($lang->sprintf($lang->plugin_incompatible, $mybb->version_code), 'error');
			admin_redirect("index.php?module=config-plugins&action=pluginuploader");
		}
		
		// plugin is from the MyBB Mods site, check the version
		if(array_key_exists("guid", $info))
		{
			$mods_site_version = array();
			$url = "http://mods.mybb.com/version_check.php?info[]=" . urlencode($info['guid']);
			
			$contents = @fetch_remote_file($url);
			
			if($contents)
			{
				require_once MYBB_ROOT."inc/class_xml.php";
				$parser = new XMLParser($contents);
				$tree = $parser->get_tree();
				
				if($tree)
				{
					if($tree['plugins']['plugin']['version']['value'])
					{
						if(version_compare($info['version'], $tree['plugins']['plugin']['version']['value']) == -1)
						{
							$mods_site_version = array(
								"version" => $tree['plugins']['plugin']['version']['value'],
								"download" => $tree['plugins']['plugin']['download_url']['value']
							);
						}
					}
				}
			}
		}
		
		$new_version;
		if(!empty($mods_site_version))
		{
			$table = new Table;
			
			$table->construct_cell($lang->sprintf($lang->pluginuploader_new_version_warning, $info['name'], $info['version'], $mods_site_version['version'], $mods_site_version['download'], $mybb->post_code));
			$table->construct_row();
			
			$new_version = $table->output("", 1, "general", true);
		}
		
		$has_non_php_root_files = false;
		$php = $pluginuploader->glob($root . "/*.php");
		$dirs = array_filter($pluginuploader->glob($root . "/*"), "is_dir");
		$php_dirs = array_merge($php, $dirs);
		$all = $pluginuploader->glob($root . "/*");
		if(count($all) > count($php_dirs) && is_dir($root . "/inc"))
		{
			$has_non_php_root_files = true;
		}
		
		$screenshots = pluginuploader_load_screenshots($path);
		if(!empty($screenshots))
		{
			foreach($screenshots as &$screenshot)
			{
				$screenshot = str_replace(MYBB_ROOT, "", $screenshot);
			}
		}
		
		$page->output_header($lang->pluginuploader);
		
		$form = new Form("index.php?module=config-plugins&action=pluginuploader&amp;action2=do_upload&amp;do=import", "post", "", 1, "", "", "submit = document.getElementById('submit'); submit.style.color = '#CCCCCC'; submit.style.border = '3px double #CCCCCC'; submit.disabled = 'disabled';");
		$form_container = new FormContainer($lang->pluginuploader_upload_plugin);
		
		// does this file already exist?
		if(file_exists(MYBB_ROOT . "inc/plugins/" . $plugin_name . ".php"))
		{
			// this plugin already exists, we'll just make sure the user wants to re-import it
			$type = "upgrade";
			$plugin_version_extra = "";
			$plugin_exists_message = $lang->sprintf($lang->pluginuploader_plugin_exists);
			$query = $db->simple_select("pluginuploader", "*", "name = '" . $db->escape_string($plugin_name) . "'");
			if($db->num_rows($query) == 1)
			{
				$plugin = $db->fetch_array($query);
				if($plugin['version'])
				{
					// uploading the same version of the plugin
					if($plugin['version'] == $info['version'])
					{
						$plugin_exists_message .= " " . $lang->pluginuploader_plugin_same_version;
					}
					// uploading a new version of the plugin
					elseif(version_compare($info['version'], $plugin['version']) == 1)
					{
						$plugin_exists_message .= " " . $lang->pluginuploader_plugin_new_version;
						$plugin_version_extra = " - <em>Current Version: " . $plugin['version'] . "</em>";
					}
					// uploading an older version of the plugin
					elseif(version_compare($info['version'], $plugin['version']) == -1)
					{
						$plugin_exists_message .= " " . $lang->pluginuploader_plugin_old_version;
						$plugin_version_extra = " - <em>Current Version: " . $plugin['version'] . "</em> " . $lang->pluginuploader_plugin_old_version_2;
					}
				}
			}
			
			if(!empty($new_version))
			{
				echo $new_version;
			}
			
			$plugin_exists_message .= " " . $lang->pluginuploader_plugin_upgrade_warning;
			
			$form_container->output_row("", "", $plugin_exists_message);
			$form_container->output_row($lang->pluginuploader_plugin_name, "", $info['name']);
			$form_container->output_row($lang->pluginuploader_plugin_version, "", $info['version'] . $plugin_version_extra);
			$form_container->output_row($lang->pluginuploader_plugin_description, "", $info['description']);
			if(!empty($screenshots))
			{
				pluginuploader_show_screenshots($screenshots, $form_container);
			}
			if($has_non_php_root_files)
			{
				$form_container->output_row($lang->pluginuploader_import_non_php_root_files, $lang->pluginuploader_import_non_php_root_files_desc, $form->generate_yes_no_radio("import_non_php_root_files", 0, true));
			}
			$form_container->output_row($lang->pluginuploader_activate, $lang->pluginuploader_activate_desc, $form->generate_yes_no_radio("activate", 1, true));
			$form_container->end();
		}
		else
		{
			// this is a new plugin, ask if they want to activate/install
			$type = "new";
			
			if(!empty($new_version))
			{
				echo $new_version;
			}
			
			$form_container->output_row($lang->pluginuploader_plugin_name, "", $info['name']);
			$form_container->output_row($lang->pluginuploader_plugin_version, "", $info['version']);
			$form_container->output_row($lang->pluginuploader_plugin_description, "", $info['description']);
			if(!empty($screenshots))
			{
				pluginuploader_show_screenshots($screenshots, $form_container);
			}
			if($has_non_php_root_files)
			{
				$form_container->output_row($lang->pluginuploader_import_non_php_root_files, $lang->pluginuploader_import_non_php_root_files_desc, $form->generate_yes_no_radio("import_non_php_root_files", 0, true));
			}
			$form_container->output_row($lang->pluginuploader_install_activate, $lang->pluginuploader_install_activate_desc, $form->generate_yes_no_radio("activate", 1, true));
			$form_container->end();
		}
		
		echo $form->generate_hidden_field("root", $root);
		echo $form->generate_hidden_field("plugin_file", $plugin_file);
		echo $form->generate_hidden_field("plugin_name", $plugin_name);
		echo $form->generate_hidden_field("plugin_temp_name", $plugin_temp_name);
		echo $form->generate_hidden_field("plugin_version", $info['version']);
		echo $form->generate_hidden_field("type", $type);
		
		$buttons[] = $form->generate_submit_button($lang->pluginuploader_import_plugin, array("id" => "submit"));
		$form->output_submit_wrapper($buttons);
		$form->end();
		
		$page->output_footer();
	}
	// we can either slip right into this if it's a new plugin, or come here after a confirmation if this is a pre-existing plugin
	if($mybb->input['do'] == "import")
	{
		if(!verify_post_check($mybb->input['my_post_key']))
		{
			flash_message($lang->invalid_post_verify_key2, 'error');
			admin_redirect("index.php?module=config-plugins&action=pluginuploader");
		}
		
		$plugin_name = $mybb->input['plugin_name'];
		$plugin_temp_name = $mybb->input['plugin_temp_name'];
		$import_non_php_root_files = $mybb->input['import_non_php_root_files'];
		
		$root = $mybb->input['root'];
		$type = $mybb->input['type'];
		
		$current_files = array();
		$query = $db->simple_select("pluginuploader", "files", "name = '" . $db->escape_string($plugin_name) . "'");
		if($db->num_rows($query) == 1)
		{
			$files = $db->fetch_field($query, "files");
			if(!empty($files))
			{
				$files = unserialize($files);
				foreach($files as $file)
				{
					$current_files[$file] = $file;
				}
			}
		}
		//echo "<pre>";print_r($current_files);echo "</pre>";
		//exit;
		$files = pluginuploader_move_files($root, $type, $current_files, $import_non_php_root_files);
		//echo "<pre>";print_r($files);echo "</pre>";
		//exit;
		
		$files['files'] = serialize($files['files']);
		
		$replace = array(
			"name" => $db->escape_string($plugin_name),
			"version" => $db->escape_string($mybb->input['plugin_version']),
			"files" => $db->escape_string($files['files'])
		);
		$db->replace_query("pluginuploader", $replace);
		
		@$pluginuploader->rmdir(MYBB_ROOT . "inc/plugins/temp/" . $plugin_temp_name);
		@$pluginuploader->unlink(MYBB_ROOT . "inc/plugins/temp/" . $plugin_temp_name.'.zip');
		
		if(!empty($files['errors']))
		{
			$errors = "<li>" . str_replace("MYBB_ADMIN_DIR", $mybb->config['admin_dir'], implode("</li><li>", $files['errors'])) . "</li>";
			if($files['no_user'])
			{
				$lang->pluginuploader_error_move_files .= $lang->pluginuploader_error_no_user;
			}
			flash_message($lang->sprintf($lang->pluginuploader_error_move_files, $errors), 'error');
			admin_redirect("index.php?module=config-plugins&action=pluginuploader");
		}
		if($mybb->cookies['mybb_pluginuploader_send_usage_stats'] != 'no')
		{
			$import_source = $admin_session['data']['pluginuploader_import_source'];
			pluginuploader_send_usage_stats($plugin_name, $import_source);
		}
		// this is the same whether it's a new plugin or an upgrade
		if($mybb->input['activate'] == 1)
		{
			admin_redirect("index.php?module=config-plugins&action=activate&plugin=" . urlencode($plugin_name) . "&my_post_key={$mybb->post_code}");
		}
		else
		{
			flash_message($lang->pluginuploader_success, 'success');
			admin_redirect("index.php?module=config-plugins");
		}
	}
}
elseif($mybb->input['action2'] == "do_install")
{
	if(!verify_post_check($mybb->input['my_post_key']))
	{
		flash_message($lang->invalid_post_verify_key2, 'error');
		admin_redirect("index.php?module=config-plugins&action=pluginuploader");
	}
	
	$plugin_name = $mybb->input['plugin'];
	
	if($plugin_name == "plugin-uploader")
	{
		flash_message($lang->pluginuploader_error_plugin_pluginuploader, 'error');
		admin_redirect("index.php?module=config-plugins&action=pluginuploader");
	}
	
	$url = 'http://mods.mybb.com/download';
	$fields = array(
		'friendly_name' => urlencode($plugin_name),
		'agree' => urlencode('I Agree')
	);
	
	foreach($fields as $key=>$value)
	{
		$fields_string .= $key.'='.$value.'&';
	}
	rtrim($fields_string, '&');
	
	// we need to try to do this because apparently CURLOPT_FOLLOWLOCATION can't be set if either of these are enabled
	@ini_set('safe_mode', 0);
	@ini_set('open_basedir', '');
	
	$mods_site_method = pluginuploader_can_use_mods_site(true);
	if($mods_site_method == 'cURL')
	{
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_POST,count($fields));
		curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		// thanks to Booher and Tim B for this next line!!
		// errors are suppressed here as if safe_mode or open_basedir are enabled, you'll get an error
		@curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true);
		$result = curl_exec($ch);
		curl_close($ch);
	}
	elseif($mods_site_method == 'stream')
	{
		$params = array(
			'http' => array(
				'method' => 'POST',
				'content' => $fields_string
			)
		);
		$scc = @stream_context_create($params);
		$fp = @fopen($url, 'rb', false, $scc);
		$result = @stream_get_contents($fp);
	}
	
	if(!empty($result) && @file_put_contents(MYBB_ROOT.'inc/plugins/temp/'.$plugin_name.'.zip', $result))
	{
		update_admin_session('pluginuploader_import_source', 'modssite');
		
		flash_message($lang->pluginuploader_downloaded_from_mods, 'success');
		admin_redirect("index.php?module=config-plugins&action=pluginuploader&action2=do_upload&from_mods_site=1&plugin_name=".$plugin_name."&my_post_key={$mybb->post_code}");
	}
	else
	{
		flash_message($lang->sprintf($lang->pluginuploader_error_downloading_from_mods, $plugin_name).'<br /><br />'.$lang->pluginuploader_error_downloading_from_mods_unknown_error, 'error');
		admin_redirect("index.php?module=config-plugins&action=pluginuploader");
	}
}
elseif($mybb->input['action2'] == "install")
{
	$plugin = $mybb->input['plugin'];
	
	if($plugin == "plugin-uploader")
	{
		flash_message($lang->pluginuploader_error_plugin_pluginuploader, 'error');
		admin_redirect("index.php?module=config-plugins&action=pluginuploader");
	}
	
	if(!pluginuploader_can_use_mods_site())
	{
		$error_message = $lang->pluginuploader_error_downloading_from_mods_error_ini;
		if(version_compare(PHP_VERSION, '5.3.4', '<'))
		{
			$error_message .= ' '.$lang->pluginuploader_error_downloading_from_mods_error_php_version;
		}
		$error_message .= ' '.$lang->pluginuploader_error_downloading_from_mods_contact_host;
		
		flash_message($lang->sprintf($lang->pluginuploader_error_downloading_from_mods, $plugin).'<br /><br />'.$error_message, 'error');
		admin_redirect("index.php?module=config-plugins&action=pluginuploader");
	}
	
	$info_div = fetch_remote_file("http://mods.mybb.com/view/" . $plugin);
	preg_match_all("#<h3>(.*?)</h3>#s", $info_div, $info);
	$plugin_name = $info[1][3];
	
	$licence_div = fetch_remote_file("http://mods.mybb.com/download/" . $plugin);
	preg_match("#<div id=\"page\">(.*?)</div>#s", $licence_div, $licence);
	preg_match("#<p>(.*?)</p>#s", $licence[0], $content);
	$licence_content = $content[0];
	
	if(strpos($licence_content, 'The download you are attempting to download appears to be invalid.') !== false)
	{
		flash_message($lang->sprintf($lang->pluginuploader_download_from_mods_invalid, $plugin), 'error');
		admin_redirect("index.php?module=config-plugins&action=pluginuploader");
	}
	
	// check if PHP will be able to move the files, and if it can't, see if we have an FTP connection; if we don't, redirect to the FTP details page
	if(!$pluginuploader->pluginuploader_copy_test() && !$pluginuploader->ftp_connect())
	{
		update_admin_session('pluginuploader_mods_site_plugin', $plugin);
		flash_message($lang->pluginuploader_ftp_required_desc.$lang->sprintf($lang->pluginuploader_error_downloading_from_mods_ftp_desc, $plugin_name), 'error');
		admin_redirect("index.php?module=config-plugins&action=pluginuploader&action2=ftp_details");
	}
	
	if(md5($licence_content) == 'd97623d172f087d9640da9acd38830ff')
	{
		admin_redirect("index.php?module=config-plugins&action=pluginuploader&action2=do_install&plugin=".$plugin."&my_post_key={$mybb->post_code}");
	}
	
	$page->output_header($lang->pluginuploader);
	
	$lang->load("config_plugins");
	
	$sub_tabs['plugins'] = array(
		'title' => $lang->plugins,
		'link' => "index.php?module=config-plugins",
		'description' => $lang->plugins_desc
	);
	$sub_tabs['update_plugins'] = array(
		'title' => $lang->plugin_updates,
		'link' => "index.php?module=config-plugins&amp;action=check",
		'description' => $lang->plugin_updates_desc
	);
	
	$sub_tabs['browse_plugins'] = array(
		'title' => $lang->browse_plugins,
		'link' => "index.php?module=config-plugins&amp;action=browse",
		'description' => $lang->browse_plugins_desc
	);
	
	$plugins->run_hooks_by_ref("admin_config_plugins_tabs", $sub_tabs);
	
	$page->output_nav_tabs($sub_tabs, 'upload_plugin');
	
	$form = new Form("index.php?module=config-plugins&action=pluginuploader&amp;action2=do_install", "post", "", 1, "", "", "submit = document.getElementById('submit'); submit.style.color = '#CCCCCC'; submit.style.border = '3px double #CCCCCC'; submit.disabled = 'disabled';");
	$form_container = new FormContainer($lang->sprintf($lang->pluginuploader_licence, $plugin_name));
	
	$form_container->output_row($lang->pluginuploader_licence_desc, '', $licence_content);
	
	echo $form->generate_hidden_field("plugin", $plugin);
	
	$form_container->end();
	
	$buttons[] = $form->generate_submit_button($lang->pluginuploader_agree_and_download, array("id" => "submit"));
	$form->output_submit_wrapper($buttons);
	$form->end();
	
	$page->output_footer();
}
elseif($mybb->input['action2'] == "ftp_details")
{
	if(!$mybb->config['pluginuploader_ftp_key'])
	{
		flash_message($lang->pluginuploader_ftp_message_missing_config_flash, 'error');
		admin_redirect("index.php?module=config-plugins&action=pluginuploader");
	}
	
	// doing this in here rather than in a separate condition block like with passwords above as I want to pass the POST values back to the form to pre-fill the values
	if($mybb->request_method == 'post')
	{
		if(!verify_post_check($mybb->input['my_post_key']))
		{
			flash_message($lang->invalid_post_verify_key2, 'error');
			admin_redirect("index.php?module=config-plugins&action=pluginuploader");
		}
		
		$has_error = false;
		if(!strlen(trim($mybb->input['ftp_host'])))
		{
			flash_message($lang->pluginuploader_ftp_host_missing, 'error');
			$has_error = true;
		}
		elseif(!strlen(trim($mybb->input['ftp_user'])))
		{
			flash_message($lang->pluginuploader_ftp_user_missing, 'error');
			$has_error = true;
		}
		elseif(!strlen(trim($mybb->input['ftp_password'])))
		{
			flash_message($lang->pluginuploader_ftp_password_missing, 'error');
			$has_error = true;
		}
		elseif(!strlen(trim($mybb->input['ftp_storage_location'])))
		{
			flash_message($lang->pluginuploader_ftp_storage_location_missing, 'error');
			$has_error = true;
		}
		elseif(!strlen(trim($mybb->input['ftp_cookie_expiry'])) && $mybb->input['ftp_storage_location'] == 'cookie')
		{
			flash_message($lang->pluginuploader_ftp_cookie_expiry_missing, 'error');
			$has_error = true;
		}
		
		if(!$has_error)
		{
			$pluginuploader->set_ftp_details($mybb->input['ftp_host'], $mybb->input['ftp_user'], $mybb->input['ftp_password']);
			if(!$pluginuploader->ftp_connect())
			{
				flash_message($lang->pluginuploader_ftp_test_connection_fail, 'error');
			}
			else
			{
				$ftp_details = array(
					'ftp_host' => $pluginuploader->encrypt($mybb->input['ftp_host']),
					'ftp_user' => $pluginuploader->encrypt($mybb->input['ftp_user']),
					'ftp_password' => $pluginuploader->encrypt($mybb->input['ftp_password'])
				);
				$ftp_details = base64_encode(serialize($ftp_details));
				$ftp_details_test = base64_encode($pluginuploader->encrypt('test'));
				
				$pluginuploader->clear_ftp_details();
				
				if($mybb->input['ftp_storage_location'] == 'cookie')
				{
					switch($mybb->input['ftp_cookie_expiry'])
					{
						case 'close':
							$expiry = -1;
							break;
						case 'day':
							$expiry = 60*60*24;
							break;
						case 'week':
							$expiry = 60*60*24*7;
							break;
						case 'month':
							$expiry = 60*60*24*28;
							break;
						case 'forever':
						default:
							$expiry = null;
							break;
					}
					
					my_setcookie("mybb_pluginuploader_ftp", $ftp_details, $expiry, true);
					my_setcookie("mybb_pluginuploader_ftp_test", $ftp_details_test, $expiry, true);
				}
				elseif($mybb->input['ftp_storage_location'] == 'database')
				{
					$replace = array(
						"name" => "_ftp",
						"version" => '',
						"files" => $db->escape_string($ftp_details)
					);
					$db->replace_query("pluginuploader", $replace);
					
					$replace = array(
						"name" => "_ftp_test",
						"version" => '',
						"files" => $db->escape_string($ftp_details_test)
					);
					$db->replace_query("pluginuploader", $replace);
				}
				
				$mods_site_plugin = $admin_session['data']['pluginuploader_mods_site_plugin'];
				if(!empty($mods_site_plugin))
				{
					$url = "index.php?module=config-plugins&action=pluginuploader&action2=install&plugin=".$mods_site_plugin;
					$lang->pluginuploader_ftp_details_added .= $lang->pluginuploader_error_downloading_from_mods_ftp_added_extra;
					update_admin_session('pluginuploader_mods_site_plugin', '');
				}
				else
				{
					$url = "index.php?module=config-plugins&action=pluginuploader";
				}
				
				flash_message($lang->pluginuploader_ftp_details_added, 'success');
				admin_redirect($url);
			}
		}
	}
	
	$page->add_breadcrumb_item($lang->pluginuploader_ftp_details);
	$page->output_header($lang->pluginuploader);
	
	$lang->load("config_plugins");
	
	$form = new Form("index.php?module=config-plugins&amp;action=pluginuploader&amp;action2=ftp_details", "post");
	$form_container = new FormContainer($lang->pluginuploader_ftp_details);
	
	$hide_ftp_cookie_expiry = '';
	if($mybb->input['ftp_storage_location'] != 'cookie')
	{
		$hide_ftp_cookie_expiry = "$('ftp_cookie_expiry').hide();";
	}
	echo "<script type=\"text/javascript\">
	document.observe(\"dom:loaded\", function() {
		{$hide_ftp_cookie_expiry}
		$('ftp_storage_location').observe('change', function() {
			if(this.value == 'cookie')
			{
				$('ftp_cookie_expiry').show();
			}
			else
			{
				$('ftp_cookie_expiry').hide();
			}
		});
	});
	</script>";
	
	$storage_location_options = array(
		'' => '',
		'cookie' => $lang->pluginuploader_ftp_storage_location_cookie,
		'database' => $lang->pluginuploader_ftp_storage_location_database
	);
	$cookie_expiry_options = array(
		'' => '',
		'close' => $lang->pluginuploader_ftp_cookie_expiry_close,
		'day' => $lang->pluginuploader_ftp_cookie_expiry_day,
		'week' => $lang->pluginuploader_ftp_cookie_expiry_week,
		'month' => $lang->pluginuploader_ftp_cookie_expiry_month,
		'forever' => $lang->pluginuploader_ftp_cookie_expiry_forever
	);
	
	$form_container->output_row($lang->pluginuploader_ftp_host . " <em>*</em>", $lang->pluginuploader_ftp_host_desc, $form->generate_text_box("ftp_host", $mybb->input['ftp_host'], array('id' => 'ftp_host')));
	$form_container->output_row($lang->pluginuploader_ftp_user . " <em>*</em>", $lang->pluginuploader_ftp_user_desc, $form->generate_text_box("ftp_user", $mybb->input['ftp_user'], array('id' => 'ftp_user')));
	$form_container->output_row($lang->pluginuploader_ftp_password . " <em>*</em>", $lang->pluginuploader_ftp_password_desc, $form->generate_password_box("ftp_password", $mybb->input['ftp_password']));
	$form_container->output_row($lang->pluginuploader_ftp_storage_location . " <em>*</em>", $lang->pluginuploader_ftp_storage_location_desc, $form->generate_select_box("ftp_storage_location", $storage_location_options, $mybb->input['ftp_storage_location'], array('id' => 'ftp_storage_location')));
	$form_container->output_row($lang->pluginuploader_ftp_cookie_expiry . " <em>*</em>", $lang->pluginuploader_ftp_cookie_expiry_desc, $form->generate_select_box("ftp_cookie_expiry", $cookie_expiry_options, $mybb->input['ftp_cookie_expiry']), '', array('id' => 'ftp_cookie_expiry'));
	$form_container->end();
	
	$buttons[] = $form->generate_submit_button($lang->pluginuploader_ftp_test_connection_save, array("id" => "submit"));
	$form->output_submit_wrapper($buttons);
	$form->end();
	
	$page->output_footer();
}
elseif($mybb->input['action2'] == "clear_ftp_details")
{
	if(!verify_post_check($mybb->input['my_post_key']))
	{
		flash_message($lang->invalid_post_verify_key2, 'error');
		admin_redirect("index.php?module=config-plugins&action=pluginuploader");
	}
	
	$pluginuploader->clear_ftp_details();
	
	flash_message($lang->pluginuploader_ftp_details_cleared, 'success');
	admin_redirect("index.php?module=config-plugins&action=pluginuploader");
}
elseif($mybb->input['action2'] == "use_ftp")
{
	if($mybb->input['use_ftp'] == 1)
	{
		my_setcookie("mybb_pluginuploader_use_ftp", 1, -1);
	}
	else
	{
		my_unsetcookie("mybb_pluginuploader_use_ftp");
	}
	admin_redirect("index.php?module=config-plugins&action=pluginuploader");
}
elseif($mybb->input['action2'] == "do_password")
{
	if(!is_super_admin($mybb->user['uid']))
	{
		flash_message($lang->pluginuploader_password_not_super_admin, 'error');
		admin_redirect("index.php?module=config-plugins&action=pluginuploader");
	}
	
	if(!verify_post_check($mybb->input['my_post_key']))
	{
		flash_message($lang->invalid_post_verify_key2, 'error');
		admin_redirect("index.php?module=config-plugins&action=pluginuploader");
	}
	
	$query = $db->simple_select("pluginuploader", "version AS salt, files AS password", "name = '_password'");
	$password = $db->fetch_array($query);
	
	// we only need to validate the current password if one's already been set
	if($password['password'])
	{
		if(md5(md5($mybb->input['password_current']) . md5($password['salt'])) != $password['password'])
		{
			flash_message($lang->pluginuploader_password_current_incorrect, 'error');
			admin_redirect("index.php?module=config-plugins&action=pluginuploader&action2=password");
		}
	}
	
	if(!strlen(trim($mybb->input['password1'])) || !strlen(trim($mybb->input['password2'])))
	{
		flash_message($lang->pluginuploader_password_empty, 'error');
		admin_redirect("index.php?module=config-plugins&action=pluginuploader&action2=password");
	}
	
	if($mybb->input['password1'] != $mybb->input['password2'])
	{
		flash_message($lang->pluginuploader_password_not_same, 'error');
		admin_redirect("index.php?module=config-plugins&action=pluginuploader&action2=password");
	}
	
	$salt = random_str(10);
	$stored_pass = md5(md5($mybb->input['password1']) . md5($salt));
	
	$replace = array(
		"name" => "_password",
		"version" => $db->escape_string($salt),
		"files" => $db->escape_string($stored_pass)
	);
	$db->replace_query("pluginuploader", $replace);
	
	$update = array(
		"pluginuploader_key" => ''
	);
	$db->update_query("users", $update);
	
	my_unsetcookie("mybb_pluginuploader_key");
	
	flash_message($lang->pluginuploader_password_updated, 'success');
	admin_redirect("index.php?module=config-plugins&action=pluginuploader");
}
elseif($mybb->input['action2'] == "password")
{
	if(!is_super_admin($mybb->user['uid']))
	{
		flash_message($lang->pluginuploader_password_not_super_admin, 'error');
		admin_redirect("index.php?module=config-plugins&action=pluginuploader");
	}
	
	$page->add_breadcrumb_item($lang->pluginuploader_password_change_title);
	$page->output_header($lang->pluginuploader);
	
	$lang->load("config_plugins");
	
	$query = $db->simple_select("pluginuploader", "version AS salt, files AS password", "name = '_password'");
	$password = $db->fetch_array($query);
	
	$form = new Form("index.php?module=config-plugins&amp;action=pluginuploader&amp;action2=do_password", "post");
	$form_container = new FormContainer($lang->pluginuploader_password_change_title);
	
	$form_container->output_row("", "", $lang->pluginuploader_install_password_message);
	if($password['password'])
	{
		$form_container->output_row($lang->pluginuploader_password_current . " <em>*</em>", $lang->pluginuploader_password_current_desc, $form->generate_password_box("password_current"));
	}
	$form_container->output_row($lang->pluginuploader_password . " <em>*</em>", $lang->pluginuploader_password_desc, $form->generate_password_box("password1"));
	$form_container->output_row($lang->pluginuploader_password_confirm . " <em>*</em>", $lang->pluginuploader_password_confirm_desc, $form->generate_password_box("password2"));
	$form_container->end();
	
	$buttons[] = $form->generate_submit_button($lang->submit, array("id" => "submit"));
	$form->output_submit_wrapper($buttons);
	$form->end();
	
	$page->output_footer();
}
elseif($mybb->input['action2'] == "clear_password")
{
	if(!verify_post_check($mybb->input['my_post_key']))
	{
		flash_message($lang->invalid_post_verify_key2, 'error');
		admin_redirect("index.php?module=config-plugins&action=pluginuploader");
	}
	
	my_unsetcookie("mybb_pluginuploader_key");
	
	flash_message($lang->pluginuploader_password_cleared, 'success');
	admin_redirect("index.php?module=config-plugins&action=pluginuploader");
}
elseif($mybb->input['action2'] == 'mods_site_integration')
{
	$page->add_breadcrumb_item($lang->pluginuploader_mods_site_title);
	$page->output_header($lang->pluginuploader);
	
	$table = new Table;
	
	$table->construct_cell($lang->pluginuploader_mods_site_how_it_works);
	$table->construct_row();
	$server_table = '<table border="1" cellspacing="0" cellpadding="0">
	<tr>
		<td></td>
		<td>'.$lang->pluginuploader_mods_site_server_table_php_534_lower.'</td>
		<td>'.$lang->pluginuploader_mods_site_server_table_php_534_higher.'</td>
	</tr>
	<tr>
		<td>'.$lang->pluginuploader_mods_site_server_table_ini_on.'</td>
		<td><span style="color: red; font-weight: bold;">'.$lang->pluginuploader_mods_site_server_table_wont_work.'</span></td>
		<td><span style="color: green; font-weight: bold;">'.$lang->pluginuploader_mods_site_server_table_will_work.'</span></td>
	</tr>
	<tr>
		<td>'.$lang->pluginuploader_mods_site_server_table_ini_off.'</td>
		<td><span style="color: green; font-weight: bold;">'.$lang->pluginuploader_mods_site_server_table_will_work.'</span></td>
		<td><span style="color: green; font-weight: bold;">'.$lang->pluginuploader_mods_site_server_table_will_work.'</span></td>
	</tr>
</table>';
	if(@ini_get('safe_mode') == 1 || strtolower(@ini_get('safe_mode')) == 'on')
	{
		$safe_mode = $lang->pluginuploader_mods_site_server_info_enabled;
	}
	else
	{
		$safe_mode = $lang->pluginuploader_mods_site_server_info_disabled;
	}
	if(strlen(@ini_get('open_basedir')))
	{
		$open_basedir = $lang->pluginuploader_mods_site_server_info_enabled;
	}
	else
	{
		$open_basedir = $lang->pluginuploader_mods_site_server_info_disabled;
	}
	if(pluginuploader_can_use_mods_site())
	{
		$will_it_work = $lang->pluginuploader_mods_site_server_info_will_it_work_yes;
		$what_next = '';
	}
	else
	{
		$will_it_work = $lang->pluginuploader_mods_site_server_info_will_it_work_no;
		$what_next = '<br /><br />'.$lang->pluginuploader_mods_site_server_info_what_next.'<br />';
		if(@ini_get('safe_mode') == 1 || strtolower(@ini_get('safe_mode')) == 'on')
		{
			$what_next .= $lang->pluginuploader_mods_site_server_info_what_next_disable_safe_mode.'<br />';
		}
		if(strlen(@ini_get('open_basedir')))
		{
			$what_next .= $lang->pluginuploader_mods_site_server_info_what_next_disable_open_basedir.'<br />';
		}
		$what_next .= $lang->pluginuploader_mods_site_server_info_what_next_or.'<br />'.$lang->pluginuploader_mods_site_server_info_what_next_upgrade_php.'<br /><br />'.$lang->pluginuploader_mods_site_server_info_what_next_contact_host;
	}
	$table->construct_cell($lang->pluginuploader_mods_site_why_it_wont_work.'<br /><br />'.$server_table.'<br />'.$lang->pluginuploader_mods_site_server_info.'<br />'.$lang->pluginuploader_mods_site_server_info_safe_mode.' '.$safe_mode.'<br />'.$lang->pluginuploader_mods_site_server_info_open_basedir.' '.$open_basedir.'<br />'.$lang->pluginuploader_mods_site_server_info_php_version.' '.PHP_VERSION.'<br />'.$lang->pluginuploader_mods_site_server_info_will_it_work.' '.$will_it_work.$what_next);
	$table->construct_row();
	
	echo $table->output($lang->pluginuploader_mods_site_title);
	
	$page->output_footer();
}
else
{
	// if you have a cookie for the FTP details, check if there are also details in the database
	// if you use multiple computers or have multiple admins, and have chosen to save the FTP details to the database, some other computers may still have a cookie stored with the details in, which may be out of date, but will be used over the values stored in the database
	// the cookie on your computer is cleared when you choose to store them in the database but it can't clear cookies on other computers; instead, it's done whenever someone with a cookie set loads the page
	// this check needs to be right at the top of the page because we can't do anything with cookies once output has been sent to the browser
	if($mybb->cookies['mybb_pluginuploader_ftp'])
	{
		$query = $db->simple_select("pluginuploader", "files AS ftp_details", "name = '_ftp'");
		if($db->num_rows($query) == 1)
		{
			$pluginuploader->clear_ftp_details('cookie');
		}
	}
	
	update_admin_session('pluginuploader_import_source', 'upload');
	
	$page->output_header($lang->pluginuploader);
	
	$lang->load("config_plugins");
	
	$sub_tabs['plugins'] = array(
		'title' => $lang->plugins,
		'link' => "index.php?module=config-plugins",
		'description' => $lang->plugins_desc
	);
	$sub_tabs['update_plugins'] = array(
		'title' => $lang->plugin_updates,
		'link' => "index.php?module=config-plugins&amp;action=check",
		'description' => $lang->plugin_updates_desc
	);
	
	$sub_tabs['browse_plugins'] = array(
		'title' => $lang->browse_plugins,
		'link' => "index.php?module=config-plugins&amp;action=browse",
		'description' => $lang->browse_plugins_desc
	);
	
	$plugins->run_hooks_by_ref("admin_config_plugins_tabs", $sub_tabs);
	
	$page->output_nav_tabs($sub_tabs, 'upload_plugin');
	
	if(!DISABLE_PLUGINUPLOADER_PASSWORD)
	{
		$query = $db->simple_select("pluginuploader", "files AS password", "name = '_password'");
		$password = $db->fetch_field($query, "password");
	}
	// uh oh, we don't have a password
	// either something's happened to it, or we've not set one yet
	// explain what this is all about and show the form to set a password
	if(!$password && !DISABLE_PLUGINUPLOADER_PASSWORD)
	{
		// quick hack
		// this will remove access to the plugin uploader from all admins
		// originally it should have been disabled by default but it was set to be enabled, this will sort that out
		// this will only run if there's no password so should only run when the plugin uploader is loaded for the first time after upgrading to the version with password protection, or for new installations
		$query = $db->simple_select("adminoptions");
		while($admin = $db->fetch_array($query))
		{
			$perms = unserialize($admin['permissions']);
			$perms['config']['pluginuploader'] = 0;
			$perms = serialize($perms);
			
			$update = array(
				"permissions" => $db->escape_string($perms)
			);
			$db->update_query("adminoptions", $update, "uid = '" . $db->escape_string($admin['uid']) . "'");
		}
		
		$form = new Form("index.php?module=config-plugins&amp;action=pluginuploader&amp;action2=do_password", "post");
		$form_container = new FormContainer($lang->pluginuploader_install_password_message_title);
		
		$form_container->output_row("", "", "<span style=\"font-size: 32px; font-weight: bolder;\">" . $lang->pluginuploader_install_password_message_title . "</span><br /><br />" . $lang->pluginuploader_install_password_message);
		if(is_super_admin($mybb->user['uid']))
		{
			$form_container->output_row($lang->pluginuploader_password . " <em>*</em>", $lang->pluginuploader_password_desc, $form->generate_password_box("password1"));
			$form_container->output_row($lang->pluginuploader_password_confirm . " <em>*</em>", $lang->pluginuploader_password_confirm_desc, $form->generate_password_box("password2"));
		}
		else
		{
			$form_container->output_row("", "", $lang->pluginuploader_install_password_message_not_super_admin);
		}
		$form_container->end();
		
		$buttons[] = $form->generate_submit_button($lang->submit, array("id" => "submit"));
		$form->output_submit_wrapper($buttons);
		$form->end();
	}
	else
	{
		// have to have a class here because DefaultForm::generate_password_box() has no style option available and this is easier than extending the class with a new method that includes one
		echo "<style type=\"text/css\">
		.input_100_wide {
			width: 100px !important;
		}
		.form_row {
			margin: 10px 0px;
		}
		</style>
		
		<script type=\"text/javascript\">
		document.observe(\"dom:loaded\", function() {
			$('url_site_needs_login_checkbox').observe('change', function() {
				$('url_site_needs_login').hide();
				$('url_site_login').show();
				$('has_site_login').value = 1;
			});
			$('url_site_doesnt_need_login').observe('click', function() {
				$('url_site_needs_login').show();
				$('url_site_login').hide();
				$('has_site_login').value = 0;
				$('url_site_needs_login_checkbox').checked = false;
			});
			if($('use_ftp_checkbox') != undefined)
			{
				$('use_ftp_checkbox').observe('click', function() {
					if($('use_ftp_checkbox').checked)
					{
						use_ftp = 1;
					}
					else
					{
						use_ftp = 0;
					}
					window.location = 'index.php?module=config-plugins&action=pluginuploader&action2=use_ftp&use_ftp='+use_ftp;
				});
			}
			$('send_usage_stats_more').observe('click', function() {
				if(this.text == '".$lang->pluginuploader_stats_less."')
				{
					this.update('".$lang->pluginuploader_stats_more."');
					$('send_usage_stats_more_info').hide();
				}
				else
				{
					this.update('".$lang->pluginuploader_stats_less."');
					$('send_usage_stats_more_info').show();
				}
			});
		});
		</script>";
		
		$form = new Form("index.php?module=config-plugins&amp;action=pluginuploader&amp;action2=do_upload", "post", "", 1, "", "", "submit = document.getElementById('submit'); submit.style.color = '#CCCCCC'; submit.style.border = '3px double #CCCCCC'; submit.disabled = 'disabled';");
		$form_container = new FormContainer($lang->pluginuploader_upload_plugin);
		
		$plugin_url = '';
		$has_site_login_value = 0;
		$url_site_login_style = ' style="display: none;"';
		$url_site_needs_login_style = '';
		if($mybb->input['plugin_url'])
		{
			$plugin_url = base64_decode($mybb->input['plugin_url']);
		}
		if($mybb->input['get_site_login'])
		{
			$has_site_login_value = 1;
			$url_site_login_style = '';
			$url_site_needs_login_style = ' style="display: none;"';
		}
		
		$form_container->output_row(
			$lang->pluginuploader_plugin . " <em>*</em>", '',
			'<fieldset><legend>'.$lang->pluginuploader_plugin_file.'</legend>'.$lang->pluginuploader_plugin_file_desc.'<br />'.$form->generate_file_upload_box("plugin_file").'</fieldset>'.
			'<fieldset><legend>'.$lang->pluginuploader_plugin_url.'</legend>'.$lang->pluginuploader_plugin_url_desc.'<br />'.$form->generate_text_box("plugin_url", $plugin_url).
				$form->generate_hidden_field('has_site_login', $has_site_login_value, array('id' => 'has_site_login')).
				' <span id="url_site_needs_login"'.$url_site_needs_login_style.'>'.
					'<label for="url_site_needs_login_checkbox" style="font-weight: normal;">'.$lang->pluginuploader_from_url_site_needs_login.'</label> '.
					$form->generate_check_box('url_site_needs_login', 1, '', array('id' => 'url_site_needs_login_checkbox')).
				'</span>'.
				' <span id="url_site_login"'.$url_site_login_style.'>'.
					$lang->pluginuploader_from_url_site_login_username.' '.$form->generate_text_box('site_login_username', '', array('class' => 'input_100_wide')).' '.
					$lang->pluginuploader_from_url_site_login_password.' '.$form->generate_password_box('site_login_password', '', array('class' => 'input_100_wide')).
					' <small><a href="javascript:void(0)" id="url_site_doesnt_need_login">'.$lang->pluginuploader_from_url_site_doesnt_need_login.'</a></small>'.
				'</span>
			</fieldset>'.
			$lang->pluginuploader_plugin_desc_warning.'<br /><br />'.
			'<fieldset><legend>'.$lang->pluginuploader_plugin_mods_site.'</legend>'.(pluginuploader_can_use_mods_site()?$lang->pluginuploader_plugin_mods_site_desc:$lang->pluginuploader_plugin_mods_site_unavailable_desc).'</fieldset>'
		);
		
		$form_container->end();
		$form_container = new FormContainer();
		
		if(!DISABLE_PLUGINUPLOADER_PASSWORD)
		{
			$password_links = '';
			if($mybb->cookies['mybb_pluginuploader_key'] && $mybb->user['uid']."_".$mybb->user['pluginuploader_key'] == $mybb->cookies['mybb_pluginuploader_key'])
			{
				if(is_super_admin($mybb->user['uid']))
				{
					$password_links .= $lang->pluginuploader_password_change.$lang->pluginuploader_password_change_clear_or;
				}
				$password_links .= $lang->sprintf($lang->pluginuploader_password_clear, $mybb->post_code).'.';
				
				$form_container->output_row($lang->pluginuploader_password, '', $lang->pluginuploader_password_stored_cookie."<br /><br >".$password_links);
			}
			else
			{
				if(is_super_admin($mybb->user['uid']))
				{
					$password_links .= '<br /><br />'.$lang->pluginuploader_password_change.'.';
				}
				$form_container->output_row($lang->pluginuploader_password . " <em>*</em>", $lang->pluginuploader_password_upload_desc, $form->generate_password_box("password") . "<br /><br />" . $form->generate_check_box("password_remember", 1, $lang->pluginuploader_password_remember).$password_links);
			}
		}
		$copy_test = $pluginuploader->pluginuploader_copy_test();
		//var_dump($copy_test);
		$ftp_connect_check = $pluginuploader->ftp_connect(true);
		$ftp_message = "pluginuploader_ftp_message_";
		$ftp_details_stored_location = '';
		$pluginuploader_ftp_desc_links = '';
		$config_code = '';
		$br = '<br /><br />';
		
		if($ftp_connect_check === true)
		{
			$ftp_message .= "success";
			$ftp_message_colour = "green";
			if($mybb->cookies['mybb_pluginuploader_ftp'])
			{
				$ftp_details_stored_location = $lang->pluginuploader_ftp_details_stored_cookie.' ';
			}
			if($ftp_details_stored_location == '')
			{
				$query = $db->simple_select("pluginuploader", "files AS ftp_details", "name = '_ftp'");
				if($db->num_rows($query) == 1)
				{
					$ftp_details_stored_location = $lang->pluginuploader_ftp_details_stored_database.' ';
				}
			}
			$pluginuploader_ftp_desc_links = $lang->sprintf($lang->pluginuploader_ftp_desc_link_set, $lang->sprintf($lang->pluginuploader_ftp_desc_link_clear, $mybb->post_code));
		}
		else
		{
			if($ftp_connect_check == 'missing_config')// || $ftp_connect_check == 'config_wrong')
			{
				$key = random_str(32);
				$config_code = "<pre style=\"margin: 0;\">".str_replace(array("&lt;?php&nbsp;", "?&gt;"), "", highlight_string("<?php \$config['pluginuploader_ftp_key'] = '{$key}'; ?>", true))."</pre>";
				$br = '';
			}
			$ftp_message .= $ftp_connect_check;
			$ftp_message_colour = "red";
			if($mybb->config['pluginuploader_ftp_key'])
			{
				$pluginuploader_ftp_desc_links = $lang->sprintf($lang->pluginuploader_ftp_desc_link_set, '');
			}
		}
		
		if(!$copy_test)
		{
			$pluginuploader_ftp_title = $lang->pluginuploader_ftp_required;
			$pluginuploader_ftp_desc = $lang->pluginuploader_ftp_required_desc;
			if(!@file_exists(MYBB_ROOT."inc/plugins/temp/test.php"))
			{
				$ftp_message = 'pluginuploader_ftp_missing_test_file';
			}
			$ftp_message = "<span style=\"color: {$ftp_message_colour}; font-weight: bold;\">".$lang->$ftp_message.$config_code.$br."</span>";
			$ftp_content = $pluginuploader_ftp_desc.$ftp_message.$ftp_details_stored_location.$pluginuploader_ftp_desc_links.$lang->pluginuploader_ftp_desc_extra;
		}
		else
		{
			$pluginuploader_ftp_title = $lang->pluginuploader_ftp_optional;
			$pluginuploader_ftp_desc = $lang->pluginuploader_ftp_optional_desc;
			$ftp_message = "<span style=\"color: {$ftp_message_colour};\">".$lang->$ftp_message.$config_code.$br."</span>";
			if(!$mybb->cookies['mybb_pluginuploader_use_ftp'])
			{
				$ftp_content_style = ' style="display: none;"';
				$use_ftp_checked = false;
			}
			else
			{
				$ftp_content_style = '';
				$use_ftp_checked = true;
			}
			$ftp_content = $pluginuploader_ftp_desc.'<label for="use_ftp_checkbox" style="font-weight: normal;">'.$lang->pluginuploader_use_ftp.'</label>'.$form->generate_check_box('use_ftp_checkbox', 1, '', array('id' => 'use_ftp_checkbox', 'checked' => $use_ftp_checked)).'<br /><br /><span'.$ftp_content_style.'>'.$ftp_message.$ftp_details_stored_location.$pluginuploader_ftp_desc_links.'</span>'.$lang->pluginuploader_ftp_desc_extra;
		}
		$form_container->output_row($pluginuploader_ftp_title, "", $ftp_content);
		
		$checked = true;
		$send_usage_stats = $mybb->cookies['mybb_pluginuploader_send_usage_stats'];
		if($send_usage_stats == 'no')
		{
			$checked = false;
		}
		$form_container->output_row('', '', $form->generate_check_box("send_usage_stats", 1, '', array('checked' => $checked)).$lang->pluginuploader_stats."<br /><br />".$lang->pluginuploader_stats_desc.' <a href="javascript:void(0)" id="send_usage_stats_more">'.$lang->pluginuploader_stats_more.'</a><div id="send_usage_stats_more_info" style="display: none; font-style: italic;"><br />'.$lang->pluginuploader_stats_more_info.'</div>');
		
		$form_container->end();
		
		$buttons[] = $form->generate_submit_button($lang->submit, array("id" => "submit"));
		$form->output_submit_wrapper($buttons);
		$form->end();
	}
	//var_dump($pluginuploader->use_ftp);
	$page->output_footer();
}

function pluginuploader_create_temp_dir($name)
{
	global $pluginuploader;
	
	if(!is_dir(MYBB_ROOT . "inc/plugins/temp/"))
	{
		@mkdir(MYBB_ROOT . "inc/plugins/temp/");
		
		if(!is_dir(MYBB_ROOT . "inc/plugins/temp/"))
		{
			return false;
		}
		
		@my_chmod(MYBB_ROOT . "inc/plugins/temp/", 0777);
	}
	
	$path = MYBB_ROOT . "inc/plugins/temp/" . $name;
	
	// if the folder already exists, remove it
	if(is_dir($path))
	{
		@$pluginuploader->rmdir($path);
	}
	
	// try to make the folder
	if(!@$pluginuploader->mkdir($path))
	{
		return false;
	}
	
	// if it's not been made, return false
	if(!is_dir($path))
	{
		return false;
	}
	
	// try and CHMOD the folder
	@my_chmod($path, 0777);
	
	return true;
}

function pluginuploader_find_root($path)
{
	global $pluginuploader;
	
	// change the current working directory
	chdir($path);
	
	// cycle through a list of possible folders the plugin author could have put the files in
	$file_roots = array(
		"files",
		"upload",
		"Upload"
	);
	foreach($file_roots as $file_root)
	{
		if(is_dir($path . "/" . $file_root))
		{
			// if this folder exists, change the working directory and return this path, this is the root
			chdir($path . "/" . $file_root);
			return $path . "/" . $file_root;
		}
	}
	
	// if we have PHP files here, this is the root
	if(count(@$pluginuploader->glob("*.php")) > 0)
	{
		return $path;
	}
	
	// if we have an inc folder here, this is the root
	if(is_dir($path . "/inc"))
	{
		return $path;
	}
	
	// still going, so go through any folders we have
	// this could be another folder in the zip archive before the plugin file root
	$dirs = array_filter(@$pluginuploader->glob('*'), 'is_dir');
	
	foreach($dirs as $key => $dir)
	{
		// if there's a __MACOSX folder, get rid of it, we don't want to deal with that
		if($dir == "__MACOSX")
		{
			unset($dirs[$key]);
		}
	}
	
	// if there's more than one folder, we don't know where we're supposed to go; if this is the case, exit the function and we'll show an error to the user, plugin isn't packaged very well
	if(count($dirs) > 1)
	{
		return -1;
	}
	else
	{
		// get the last element of the array - there's only 1 value in it, but the key could be 0 or 1, this will get the value of the new folder regardless
		$new_dir = end($dirs);
		
		// set the new path
		$path .= "/" . $new_dir;
		// go again with the new path
		return pluginuploader_find_root($path);
	}
}

function pluginuploader_find_pluginfile($root)
{
	global $pluginuploader;
	
	// now we have to try and find the main plugin file
	if(is_dir($root . "/inc"))
	{
		$php = @$pluginuploader->glob("inc/plugins/*.php");
		if(count($php) == 1)
		{
			return $php[0];
		}
		else
		{
			return false;
		}
	}
	else
	{
		// the files are directly in this folder
		// get all the PHP files in this directory
		$php = @$pluginuploader->glob("*.php");
		// there's just one PHP file, we'll take that as the plugin file
		if(count($php) == 1)
		{
			return $php[0];
		}
		// two PHP files? check if one's a plugin file and one's a language file
		elseif(count($php) == 2)
		{
			foreach($php as $key => $file)
			{
				if(substr($file, -9) == ".lang.php")
				{
					$lang_file = $key;
				}
				else
				{
					$plugin_file = $key;
				}
			}
			
			if(!isset($lang_file))
			{
				// return false if no language file was found; we have no way of knowing where this second file is supposed to go if it isn't a language file
				return false;
			}
			
			$plugin_file = $php[$plugin_file];
			$lang_file = $php[$lang_file];
			$lang_file = str_replace(".lang", "", $lang_file);
			
			if($plugin_file == $lang_file)
			{
				// the names of the files are the same once .lang is removed from the language file, return this as the plugin file
				return $plugin_file;
			}
			
			// if we're still going on, something weird has happened, return false
			return false;
		}
		// 
		else
		{
			return false;
		}
	}
}

function pluginuploader_has_external_files($root)
{
	global $pluginuploader;
	
	chdir($root);
	
	if(!is_dir("inc"))
	{
		return false;
	}
	
	$dirs = array_filter(@$pluginuploader->glob('inc/plugins/*'), 'is_dir');
	
	if(!empty($dirs) || is_dir('inc/languages'))
	{
		return true;
	}
	
	return false;
}

// I don't know why $root has to be a parameter, used to globalise it, but then it suddenly wasn't getting globalised... if you can think why, I'd love to hear it
function pluginuploader_load_external_files($path, $root)
{
	global $pluginuploader;
	
	static $ret = array();
	
	chdir($path);
	
	// if the current folder is ./inc/plugins/ we want to ignore any PHP files as it'll be the plugin file itself
	if(str_replace($root, '', $path) == '/inc/plugins')
	{
		$php = array();
	}
	else
	{
		$php = @$pluginuploader->glob("*.php");
	}
	
	$dirs = array_filter(@$pluginuploader->glob("*"), "is_dir");
	$objects = array_merge($php, $dirs);
	
	foreach($objects as $object)
	{
		if(is_dir($path . "/" . $object))
		{
			if(!is_dir(MYBB_ROOT . str_replace($root . "/", "", $path . "/" . $object)))
			{
				$ret['folders'][] = array(
					"path" => str_replace($root . "/", "", $path . "/" . $object)
				);
			}
			pluginuploader_load_external_files($path . "/" . $object, $root);
		}
		else
		{
			$ret['files'][] = array(
				"relative" => str_replace($root . "/", "", $path . "/" . $object),
				"full" => $path . "/" . $object
			);
		}
	}
	
	return $ret;
}

function pluginuploader_move_files($path, $type, $current_files = array(), $import_non_php_root_files = false)
{
	global $mybb, $pluginuploader, $root, $ret, $all_files_list;
	
	if(!is_array($ret))
	{
		$ret = array(
			"files" => $current_files
		);
	}
	
	if(!is_array($all_files_list))
	{
		$all_files_list = $current_files;
	}
	
	chdir($path);
	
	if(!$root)
	{
		$root = $path;
	}
	
	if(is_dir($path))
	{
		if(is_dir($root . "/inc"))
		{
			$objects = scandir($path);
			foreach($objects as $object)
			{
				if(!in_array($object, array(".", "..", "__MACOSX", ".DS_Store", "thumbs.db", ".svn")))
				{
					$from = $path . "/" . $object;
					$to = substr(MYBB_ROOT, 0, -1) . str_replace($root, "", $path) . "/" . $object;
					$friendly_file_name = str_replace(MYBB_ROOT, "", $to);
					$to = $pluginuploader->replace_admin_dir($to);
					$friendly_file_name = $pluginuploader->replace_admin_dir($friendly_file_name, true);
					//echo $friendly_file_name . "<br />";
					//echo $from . "<br />";
					//echo $to . "<br />";
					if($path == $root && !$import_non_php_root_files)
					{
						if(!(is_dir($from) || substr($object, -4) == ".php"))
						{
							if(!is_dir($from))
							{
								$all_files_list[$friendly_file_name] = $friendly_file_name;
								$ret['files'] = $all_files_list;
							}
							continue;
						}
					}
					
					if(is_dir($from))
					{
						if(!is_dir($to) || in_array($friendly_file_name, $current_files))
						{
							$all_files_list[$friendly_file_name] = $friendly_file_name;
							$ret['files'] = $all_files_list;
							if(!is_dir($to))
							{
								if(!@$pluginuploader->mkdir($to))
								{
									$ret['errors'][] = "./" . $friendly_file_name;
								}
							}
						}
						pluginuploader_move_files($from, $type, $current_files, $import_non_php_root_files);
					}
					else
					{
						if(!file_exists($to) || in_array($friendly_file_name, $current_files) || (empty($current_files) && $type == "upgrade"))
						{
							$all_files_list[$friendly_file_name] = $friendly_file_name;
							$ret['files'] = $all_files_list;
							if($type == "upgrade")
							{
								if(@file_exists($to))
								{
									if($pluginuploader->user_is_nobody($to))
									{
										$ret['errors'][] = "./" . $friendly_file_name;
										$ret['no_user'] = true;
										continue;
									}
								}
							}
							if(!@$pluginuploader->copy($from, $to))
							{
								$ret['errors'][] = "./" . $friendly_file_name;
							}
						}
					}
				}
			}
		}
		else
		{
			$php = @$pluginuploader->glob("*.php");
			
			// there's just one PHP file, this is the plugin file
			if(count($php) == 1)
			{
				$all_files_list["inc/plugins/" . $php[0]] = "inc/plugins/" . $php[0];
				$ret['files'] = $all_files_list;
				if(!@$pluginuploader->copy($path . "/" . $php[0], MYBB_ROOT . "inc/plugins/" . $php[0]))
				{
					$ret['errors'][] = "./" . "inc/plugins/" . $php[0];
				}
			}
			// two PHP files? check if one's a plugin file and one's a language file
			elseif(count($php) == 2)
			{
				foreach($php as $key => $file)
				{
					if(substr($file, -9) == ".lang.php")
					{
						$all_files_list["inc/languages/english/" . $file] = "inc/languages/english/" . $file;
						$ret['files'] = $all_files_list;
						if(!@$pluginuploader->copy($path . "/" . $file, MYBB_ROOT . "inc/languages/english/" . $file))
						{
							$ret['errors'][] = "./" . "inc/languages/english/" . $file;
						}
					}
					else
					{
						$all_files_list["inc/plugins/" . $file] = "inc/plugins/" . $file;
						$ret['files'] = $all_files_list;
						if(!@$pluginuploader->copy($path . "/" . $file, MYBB_ROOT . "inc/plugins/" . $file))
						{
							$ret['errors'][] = "./" . "inc/plugins/" . $file;
						}
					}
				}
			}
			
			$all = $pluginuploader->glob("*");
			// we have other files here that aren't PHP files, try and do something with them
			if(count($all) > count($php))
			{
				// do we have any javascript files?
				$js = @$pluginuploader->glob("*.js");
				if(count($js) > 0)
				{
					foreach($js as $key => $file)
					{
						$all_files_list["jscripts/" . $file] = "jscripts/" . $file;
						$ret['files'] = $all_files_list;
						if(!@$pluginuploader->copy($path . "/" . $file, MYBB_ROOT . "jscripts/" . $file))
						{
							$ret['errors'][] = "./" . "jscripts/" . $file;
						}
					}
				}
				
				// do we have any images?
				$images = @$pluginuploader->glob("{*.gif,*.GIF,*.jpg,*.JPG,*.jpeg,*.JPEG,*.png,*.PNG}", GLOB_BRACE);
				if(count($images) > 0)
				{
					foreach($images as $key => $file)
					{
						$all_files_list["images/" . $file] = "images/" . $file;
						$ret['files'] = $all_files_list;
						if(!@$pluginuploader->copy($path . "/" . $file, MYBB_ROOT . "images/" . $file))
						{
							$ret['errors'][] = "./" . "images/" . $file;
						}
					}
				}
			}
		}
	}
	
	return $ret;
}

function pluginuploader_load_screenshots($path, $get_files = false)
{
	global $pluginuploader;
	
	chdir($path);
	
	// do we have any images with 'screenshot' in the name? this is to catch any in the folder we're in now
	$screenshots = @$pluginuploader->glob($path . "/*screenshot*{.gif,.GIF,.jpg,.JPG,.jpeg,.JPEG,.png,.PNG}", GLOB_BRACE);
	if(!empty($screenshots))
	{
		return $screenshots;
	}
	
	// we're loading all images in this folder, as we're pretty sure it only contains screenshots)
	if($get_files)
	{
		$screenshots = @$pluginuploader->glob($path . "/{*.gif,*.GIF,*.jpg,*.JPG,*.jpeg,*.JPEG,*.png,*.PNG}", GLOB_BRACE);
		if(!empty($screenshots))
		{
			return $screenshots;
		}
		else
		{
			return false;
		}
	}
	
	// go through any folders that could contain screenshots
	$roots = array(
		"screenshots",
		"extra",
		"extras",
		"docs"
	);
	foreach($roots as $root)
	{
		if(is_dir($path . "/" . $root))
		{
			// if this folder exists, go into it and just load any images inside it
			return pluginuploader_load_screenshots($path . "/" . $root, true);
		}
	}
	
	// if we're here, we've not found any screenshots, and haven't found any folders that could contain them
	// if the folder we're in has an inc folder or PHP files in it, we've most likely gone past where the screenshots are and couldn't find them, if there even were any
	// so to make sure we don't show any images that aren't screenshots, return false now
	if(is_dir($path . "/inc") || count($pluginuploader->glob("*.php")) > 0)
	{
		return false;
	}
	
	// still going, so go through any folders we have
	// this could be another folder in the zip archive before the main file root
	$dirs = array_filter(@$pluginuploader->glob('*'), 'is_dir');
	foreach($dirs as $key => $dir)
	{
		// if there's a __MACOSX folder, get rid of it, we don't want to deal with that
		if($dir == "__MACOSX")
		{
			unset($dirs[$key]);
		}
	}
	
	if(count($dirs) > 1)
	{
		return false;
	}
	else
	{
		// we have another directory to go into
		$new_dir = end($dirs);
		$path .= "/" . $new_dir;
		return pluginuploader_load_screenshots($path);
	}
}

function pluginuploader_show_screenshots($screenshots, &$form_container)
{
	global $mybb, $lang;
	
	$images = "";
	foreach($screenshots as $screenshot)
	{
		$images .= "<a href=\"{$mybb->settings['bburl']}/{$screenshot}\" target=\"_blank\"><img src=\"{$mybb->settings['bburl']}/{$screenshot}\" alt=\"\" width=\"150px;\" height=\"100px\" style=\"border: 3px double #0F5C8E;\" /></a>&nbsp;";
	}
	
	$form_container->output_row($lang->pluginuploader_plugin_screenshots, $lang->pluginuploader_plugin_screenshots_desc, $images);
}

/*
 * This is a copf of pluginSystem::is_compatible but instead of passing a plugin name to look for in the ./inc/plugins/ folder, pass it the path of the ne plugin being uploaded
*/
function plugin_is_compatible($plugin, $path)
{
	global $mybb;

	// Ignore potentially missing plugins.
	if(!file_exists($path))
	{
		return true;
	}

	require_once $path;

	$info_func = "{$plugin}_info";
	if(!function_exists($info_func))
	{
		return false;
	}
	$plugin_info = $info_func();
	
	// No compatibility set or compatibility = * - assume compatible
	if(!$plugin_info['compatibility'] || $plugin_info['compatibility'] == "*")
	{
		return true;
	}
	$compatibility = explode(",", $plugin_info['compatibility']);
	foreach($compatibility as $version)
	{
		$version = trim($version);
		$version = str_replace("*", ".+", preg_quote($version));
		$version = str_replace("\.+", ".+", $version);
		if(preg_match("#{$version}#i", $mybb->version_code))
		{
			return true;
		}
	}

	// Nothing matches
	return false;
}

function pluginuploader_get_headers($curl, $headers)
{
	global $request_headers;
	
	$request_headers .= $headers;
	
	return strlen($headers);
}

function pluginuploader_send_usage_stats($plugin_codename = '', $import_source = '')
{
	global $mybb, $pluginuploader;
	
	$pluginuploader_info = pluginuploader_info();
	$pluginuploader->ftp_connect();
	
	$stats = array();
	$stats['mybb_url'] = md5($mybb->settings['bburl']);
	$stats['mybb_version'] = $mybb->version_code;
	$stats['php_version'] = PHP_VERSION;
	$stats['safe_mode'] = (@ini_get('safe_mode')!=''&&@ini_get('safe_mode')!='off')?1:0;
	// don't need to know what it actually is, just if it's set
	$stats['open_basedir'] = strlen(@ini_get('open_basedir'))?1:0;
	$stats['pluginuploader_version'] = $pluginuploader_info['version'];
	$stats['copy_test'] = (int)$pluginuploader->pluginuploader_copy_test();
	$stats['use_ftp'] = (int)$pluginuploader->use_ftp;
	$stats['ftp_storage_location'] = $pluginuploader->details_storage_location;
	$stats['plugin_codename'] = $plugin_codename;
	$stats['import_source'] = $import_source;
	$stats['can_use_mods_site'] = pluginuploader_can_use_mods_site(true);
	
	fetch_remote_file('http://mattrogowski.co.uk/mybb/pluginuploader.php', $stats);
}
?>