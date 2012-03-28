<?php
/**
 * Plugin Git Sync - Admin File

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

require_once MYBB_ROOT.'inc/plugins/plugingitsync/config.php';
require_once MYBB_ROOT."inc/3rdparty/diff/Diff.php";
require_once MYBB_ROOT."inc/3rdparty/diff/Diff/Renderer/unified.php";

if(!$db->table_exists('plugingitsync'))
{
	admin_redirect('index.php?module=config-plugins&action=activate&plugin=plugingitsync&my_post_key='.$mybb->post_code);
}

if($mybb->input['action'] == 'view_existing')
{
	$page->add_breadcrumb_item($lang->plugingitsync, 'index.php?module=tools-plugingitsync');
	$page->add_breadcrumb_item($lang->plugingitsync_manage_plugins_view_existing);
	$page->output_header($lang->plugingitsync);
	
	$plugins_info = plugingitsync_get_plugins_info_from_database();
	
	$table = new Table;
	$table->construct_header($lang->plugingitsync_manage_plugins_plugin_name);
	$table->construct_header($lang->plugingitsync_manage_plugins_plugin_files);
	$table->construct_header($lang->plugingitsync_manage_plugins_controls, array('colspan' => 2, 'style' => 'text-align: center;'));
	foreach($plugins_info as $plugin)
	{
		if(!empty($plugin['repo_url']))
		{
			$plugin_repo_name = '<a href="'.$plugin['repo_url'].'" target="_blank">'.$plugin['repo_name'].'</a>';
		}
		else
		{
			$plugin_repo_name = $plugin['repo_name'];
		}
		$table->construct_cell($plugin_repo_name);
		$table->construct_cell(implode("<br />", $plugin['files']));
		$table->construct_cell('<a href="index.php?module=tools-plugingitsync&action=edit&plugin='.$plugin['codename'].'">'.$lang->plugingitsync_manage_plugins_edit.'</a>', array('style' => 'text-align: center;'));
		$readme_link = '-';
		if(@file_exists(GIT_REPO_ROOT.$plugin['repo_name'].REPO_README_PATH))
		{
			$readme_link = '<a href="index.php?module=tools-plugingitsync&action=edit_readme&plugin='.$plugin['codename'].'">'.$lang->plugingitsync_manage_plugins_edit_readme.'</a>';
		}
		$table->construct_cell($readme_link, array('style' => 'text-align: center;'));
		$table->construct_row();
	}
	$table->output($lang->plugingitsync_manage_plugins_view_existing);
	
	$page->output_footer();
}
elseif($mybb->input['action'] == 'add' || $mybb->input['action'] == 'edit')
{
	if($mybb->input['action'] == 'edit')
	{
		if(empty($mybb->input['plugin']))
		{
			admin_redirect('index.php?module=tools-plugingitsync&action=add');
		}
		$query = $db->simple_select('plugingitsync', '*', 'plugin_codename = \''.$db->escape_string($mybb->input['plugin']).'\'');
		$plugin = $db->fetch_array($query);
		if(empty($plugin))
		{
			flash_message($lang->plugingitsync_manage_plugins_edit_existing_no_exist, 'error');
			admin_redirect('index.php?module=tools-plugingitsync&action=add&plugin='.$mybb->input['plugin']);
		}
	}
	
	if($mybb->request_method == 'post')
	{
		$has_error = false;
		if(!strlen(trim($mybb->input['plugin_name'])))
		{
			flash_message($lang->plugingitsync_manage_plugins_add_edit_error_no_name, 'error');
			$has_error = true;
		}
		elseif(!strlen(trim($mybb->input['plugin_codename'])))
		{
			flash_message($lang->plugingitsync_manage_plugins_add_edit_error_no_codename, 'error');
			$has_error = true;
		}
		elseif(!strlen(trim($mybb->input['plugin_repo_name'])))
		{
			flash_message($lang->plugingitsync_manage_plugins_add_edit_error_no_repo_name, 'error');
			$has_error = true;
		}
		elseif(!is_dir(GIT_REPO_ROOT.$mybb->input['plugin_repo_name']) || !is_dir(GIT_REPO_ROOT.$mybb->input['plugin_repo_name'].'/.git'))
		{
			flash_message($lang->sprintf($lang->plugingitsync_manage_plugins_add_edit_error_invalid_repo, GIT_REPO_ROOT.$mybb->input['plugin_repo_name']), 'error');
			$has_error = true;
		}
		elseif(!strlen(trim($mybb->input['plugin_files'])))
		{
			flash_message($lang->plugingitsync_manage_plugins_add_edit_error_no_files, 'error');
			$has_error = true;
		}
		
		if(!$has_error)
		{
			$where = '';
			if($mybb->input['action'] == 'edit')
			{
				$where = ' AND plugin_id != \''.$db->escape_string($plugin['plugin_id']).'\'';
			}
			$query = $db->simple_select('plugingitsync', 'plugin_id', 'plugin_name = \''.$db->escape_string($mybb->input['plugin_name']).'\''.$where);
			if($db->num_rows($query) > 0)
			{
				flash_message($lang->plugingitsync_manage_plugins_add_edit_error_plugin_name_exists, 'error');
				$has_error = true;
			}
		}
		
		if(!$has_error)
		{
			$where = '';
			if($mybb->input['action'] == 'edit')
			{
				$where = ' AND plugin_id != \''.$db->escape_string($plugin['plugin_id']).'\'';
			}
			$query = $db->simple_select('plugingitsync', 'plugin_id', 'plugin_codename = \''.$db->escape_string($mybb->input['plugin_codename']).'\''.$where);
			if($db->num_rows($query) > 0)
			{
				flash_message($lang->plugingitsync_manage_plugins_add_edit_error_plugin_codename_exists, 'error');
				$has_error = true;
			}
		}
		
		if(!$has_error)
		{
			$where = '';
			if($mybb->input['action'] == 'edit')
			{
				$where = ' AND plugin_id != \''.$db->escape_string($plugin['plugin_id']).'\'';
			}
			$query = $db->simple_select('plugingitsync', 'plugin_id', 'plugin_repo_name = \''.$db->escape_string($mybb->input['plugin_repo_name']).'\''.$where);
			if($db->num_rows($query) > 0)
			{
				flash_message($lang->plugingitsync_manage_plugins_add_edit_error_plugin_repo_name_exists, 'error');
				$has_error = true;
			}
		}
		
		if(!$has_error)
		{
			$array = array(
				'plugin_name' => $db->escape_string($mybb->input['plugin_name']),
				'plugin_codename' => $db->escape_string($mybb->input['plugin_codename']),
				'plugin_repo_name' => $db->escape_string($mybb->input['plugin_repo_name']),
				'plugin_repo_url' => $db->escape_string($mybb->input['plugin_repo_url']),
				'plugin_files' => $db->escape_string(@serialize(array_map('trim', explode("\n", $mybb->input['plugin_files']))))
			);
			if($mybb->input['action'] == 'add')
			{
				$db->insert_query('plugingitsync', $array);
				flash_message($lang->plugingitsync_manage_plugins_add_success, 'success');
			}
			elseif($mybb->input['action'] == 'edit')
			{
				$db->update_query('plugingitsync', $array, 'plugin_id = \''.$db->escape_string($plugin['plugin_id']).'\'');
				flash_message($lang->plugingitsync_manage_plugins_edit_success, 'success');
			}
			admin_redirect('index.php?module=tools-plugingitsync');
		}
	}
	
	$page->add_breadcrumb_item($lang->plugingitsync, 'index.php?module=tools-plugingitsync');
	if($mybb->input['action'] == 'add')
	{
		$page->add_breadcrumb_item($lang->plugingitsync_manage_plugins_add_new);
	}
	elseif($mybb->input['action'] == 'edit')
	{
		$page->add_breadcrumb_item($lang->plugingitsync_manage_plugins_edit_existing);
	}
	$page->output_header($lang->plugingitsync);
	
	$form = new Form("index.php?module=tools-plugingitsync&amp;action=".$mybb->input['action'], "post");
	if($mybb->input['action'] == 'add')
	{
		$form_container = new FormContainer($lang->plugingitsync_manage_plugins_add_new);
	}
	elseif($mybb->input['action'] == 'edit')
	{
		$form_container = new FormContainer($lang->plugingitsync_manage_plugins_edit_existing);
	}
	
	$plugin_name_value = '';
	if($mybb->input['action'] == 'add')
	{
		if(empty($mybb->input['plugin_name']))
		{
			if(file_exists(MYBB_ROOT.'inc/plugins/'.$mybb->input['plugin'].'.php'))
			{
				require_once MYBB_ROOT.'inc/plugins/'.$mybb->input['plugin'].'.php';
				$info_func = $mybb->input['plugin'].'_info';
				if(function_exists($info_func))
				{
					$plugin_info = $info_func();
					$plugin_name_value = $plugin_info['name'];
				}
			}
		}
	}
	elseif($mybb->input['action'] == 'edit')
	{
		$plugin_name_value = $plugin['plugin_name'];
	}
	if(isset($mybb->input['plugin_name']))
	{
		$plugin_name_value = $mybb->input['plugin_name'];
	}
	$form_container->output_row($lang->plugingitsync_manage_plugins_add_name . " <em>*</em>", $lang->plugingitsync_manage_plugins_add_name_desc, $form->generate_text_box("plugin_name", $plugin_name_value));
	
	$plugin_codename_value = '';
	if($mybb->input['action'] == 'add')
	{
		if(empty($mybb->input['plugin_codename']))
		{
			$plugin_codename_value = $mybb->input['plugin'];
		}
	}
	elseif($mybb->input['action'] == 'edit')
	{
		$plugin_codename_value = $plugin['plugin_codename'];
	}
	if(isset($mybb->input['plugin_codename']))
	{
		$plugin_codename_value = $mybb->input['plugin_codename'];
	}
	$form_container->output_row($lang->plugingitsync_manage_plugins_add_codename . " <em>*</em>", $lang->plugingitsync_manage_plugins_add_codename_desc, $form->generate_text_box("plugin_codename", $plugin_codename_value));
	
	$plugin_repo_name_value = '';
	if($mybb->input['action'] == 'edit')
	{
		$plugin_repo_name_value = $plugin['plugin_repo_name'];
	}
	if(isset($mybb->input['plugin_repo_name']))
	{
		$plugin_repo_name_value = $mybb->input['plugin_repo_name'];
	}
	$form_container->output_row($lang->plugingitsync_manage_plugins_add_repo_name . " <em>*</em>", $lang->sprintf($lang->plugingitsync_manage_plugins_add_repo_name_desc, GIT_REPO_ROOT), $form->generate_text_box("plugin_repo_name", $plugin_repo_name_value));
	
	$plugin_repo_url_value = '';
	if($mybb->input['action'] == 'edit')
	{
		$plugin_repo_url_value = $plugin['plugin_repo_url'];
	}
	if(isset($mybb->input['plugin_repo_url']))
	{
		$plugin_repo_url_value = $mybb->input['plugin_repo_url'];
	}
	$form_container->output_row($lang->plugingitsync_manage_plugins_add_repo_url, $lang->plugingitsync_manage_plugins_add_repo_url_desc, $form->generate_text_box("plugin_repo_url", $plugin_repo_url_value));
	
	$plugin_files_value = '';
	if($mybb->input['action'] == 'add')
	{
		if(empty($mybb->input['plugin_files']) && file_exists(MYBB_ROOT.'inc/plugins/'.$mybb->input['plugin'].'.php'))
		{
			$plugin_files_value = 'inc/plugins/'.$mybb->input['plugin'].'.php'."\n";
		}
	}
	elseif($mybb->input['action'] == 'edit')
	{
		$plugin_files_value = implode("\n", @unserialize($plugin['plugin_files']));
	}
	if(isset($mybb->input['plugin_files']))
	{
		$plugin_files_value = $mybb->input['plugin_files'];
	}
	$form_container->output_row($lang->plugingitsync_manage_plugins_add_files . " <em>*</em>", $lang->plugingitsync_manage_plugins_add_files_desc, $form->generate_text_area("plugin_files", $plugin_files_value));
	
	if($mybb->input['action'] == 'edit')
	{
		echo $form->generate_hidden_field('plugin', $mybb->input['plugin']);
	}
	
	$form_container->end();
	$buttons[] = $form->generate_submit_button($lang->plugingitsync_manage_plugins_submit);
	$form->output_submit_wrapper($buttons);
	$form->end();
	
	$page->output_footer();
}
elseif($mybb->input['action'] == 'edit_readme')
{
	$query = $db->simple_select('plugingitsync', 'plugin_repo_name', 'plugin_codename = \''.$db->escape_string($mybb->input['plugin']).'\'');
	$plugin_repo_name = $db->fetch_field($query, 'plugin_repo_name');
	if(empty($plugin_repo_name))
	{
		flash_message($lang->plugingitsync_manage_plugins_edit_readme_invalid_plugin, 'error');
		admin_redirect('index.php?module=tools-plugingitsync');
	}
	
	if(!@file_exists(GIT_REPO_ROOT.$plugin_repo_name.REPO_README_PATH))
	{
		flash_message($lang->plugingitsync_manage_plugins_edit_readme_no_readme, 'error');
		admin_redirect('index.php?module=tools-plugingitsync');
	}
	
	if($mybb->request_method == 'post')
	{
		if(empty($mybb->input['plugin_readme']))
		{
			flash_message($lang->plugingitsync_manage_plugins_edit_readme_error_empty, 'error');
			admin_redirect('index.php?module=tools-plugingitsync&action=edit_readme&plugin='.$mybb->input['plugin']);
		}
		elseif(!@file_put_contents(GIT_REPO_ROOT.$plugin_repo_name.REPO_README_PATH, $mybb->input['plugin_readme']))
		{
			flash_message($lang->plugingitsync_manage_plugins_edit_readme_error_writing, 'error');
			admin_redirect('index.php?module=tools-plugingitsync&action=edit_readme&plugin='.$mybb->input['plugin']);
		}
		else
		{
			flash_message($lang->plugingitsync_manage_plugins_edit_readme_success, 'success');
			admin_redirect('index.php?module=tools-plugingitsync');
		}
	}
	
	$readme = @file_get_contents(GIT_REPO_ROOT.$plugin_repo_name.REPO_README_PATH);
	if(empty($readme))
	{
		flash_message($lang->plugingitsync_manage_plugins_edit_readme_error_loading, 'error');
		admin_redirect('index.php?module=tools-plugingitsync');
	}
	if(isset($mybb->input['plugin_readme']))
	{
		$readme = $mybb->input['plugin_readme'];
	}
	
	$page->add_breadcrumb_item($lang->plugingitsync, 'index.php?module=tools-plugingitsync');
	$page->add_breadcrumb_item($lang->plugingitsync_manage_plugins_edit_readme);
	$page->output_header($lang->plugingitsync);
	
	$form = new Form("index.php?module=tools-plugingitsync&amp;action=edit_readme&plugin=".$mybb->input['plugin'], "post");
	$form_container = new FormContainer($lang->plugingitsync_manage_plugins_edit_readme);
	
	$form_container->output_row('', '', $form->generate_text_area("plugin_readme", $readme, array('rows' => 28, 'style' => 'width: 100%')));
	
	echo $form->generate_hidden_field('plugin', $mybb->input['plugin']);
	
	$form_container->end();
	$buttons[] = $form->generate_submit_button($lang->plugingitsync_manage_plugins_submit);
	$form->output_submit_wrapper($buttons);
	$form->end();
	
	$page->output_footer();
}
elseif($mybb->input['action'] == 'add_existing')
{
	$page->add_breadcrumb_item($lang->plugingitsync, 'index.php?module=tools-plugingitsync');
	$page->add_breadcrumb_item($lang->plugingitsync_manage_plugins_add_existing);
	$page->output_header($lang->plugingitsync);
	
	$plugin_files = glob(MYBB_ROOT.'inc/plugins/*.php');
	foreach($plugin_files as &$file)
	{
		$file = str_replace(array(MYBB_ROOT.'inc/plugins/', '.php'), '', $file);
	}
	$query = $db->simple_select('plugingitsync', 'plugin_codename');
	$git_plugins = array();
	while($git_plugin = $db->fetch_field($query, 'plugin_codename'))
	{
		$git_plugins[] = $git_plugin;
	}
	$not_added = array_diff($plugin_files, $git_plugins);
	
	$table = new Table;
	$table->construct_header($lang->plugingitsync_manage_plugins_plugin_name);
	$table->construct_header($lang->plugingitsync_manage_plugins_controls, array('style' => 'text-align: center;'));
	if(empty($not_added))
	{
		$table->construct_cell($lang->plugingitsync_manage_plugins_add_existing_error_none, array('colspan' => 2));
		$table->construct_row();
	}
	else
	{
		foreach($not_added as $plugin)
		{
			if(file_exists(MYBB_ROOT.'inc/plugins/'.$plugin.'.php'))
			{
				require_once MYBB_ROOT.'inc/plugins/'.$plugin.'.php';
				$info_func = $plugin.'_info';
				if(function_exists($info_func))
				{
					$plugin_info = $info_func();
					$table->construct_cell($plugin_info['name']);
					$table->construct_cell('<a href="index.php?module=tools-plugingitsync&action=add&plugin='.$plugin.'">'.$lang->plugingitsync_manage_plugins_add.'</a>', array('style' => 'text-align: center;'));
					$table->construct_row();
				}
			}
		}
	}
	$table->output($lang->plugingitsync_manage_plugins_add_existing);
	
	$page->output_footer();
}
elseif($mybb->input['action'] == 'import_config')
{
	if(!isset($plugins_info) || !is_array($plugins_info) || empty($plugins_info))
	{
		flash_message($lang->plugingitsync_manage_plugins_import_config_error_none, 'error');
		admin_redirect('index.php?module=tools-plugingitsync');
	}
	
	$plugin_count = 0;
	foreach($plugins_info as $codename => $plugin)
	{
		$query = $db->simple_select('plugingitsync', 'plugin_id', 'plugin_codename = \''.$db->escape_string($codename).'\' OR plugin_repo_name = \''.$db->escape_string($plugin['repo_name']).'\'');
		if($db->num_rows($query) == 0)
		{
			$plugin_count++;
		}
	}
	if($plugin_count == 0)
	{
		flash_message($lang->plugingitsync_manage_plugins_import_config_error_none_not_import, 'error');
		admin_redirect('index.php?module=tools-plugingitsync');
	}
	
	if($mybb->request_method == 'post')
	{
		foreach($plugins_info as $codename => $plugin)
		{
			$query = $db->simple_select('plugingitsync', 'plugin_id', 'plugin_codename = \''.$db->escape_string($codename).'\' OR plugin_repo_name = \''.$db->escape_string($plugin['repo_name']).'\'');
			if($db->num_rows($query) > 0)
			{
				continue;
			}
			
			$insert = array(
				'plugin_codename' => $db->escape_string($codename),
				'plugin_repo_name' => $db->escape_string($plugin['repo_name']),
				'plugin_repo_url' => $db->escape_string($plugin['repo_url']),
				'plugin_files' => $db->escape_string(@serialize(array_map('trim', $plugin['files'])))
			);
			$db->insert_query('plugingitsync', $insert);
		}
		flash_message($lang->plugingitsync_manage_plugins_import_config_success, 'success');
		admin_redirect('index.php?module=tools-plugingitsync');
	}
	
	$page->add_breadcrumb_item($lang->plugingitsync, 'index.php?module=tools-plugingitsync');
	$page->add_breadcrumb_item($lang->plugingitsync_manage_plugins_import_config);
	$page->output_header($lang->plugingitsync);
	
	$form = new Form("index.php?module=tools-plugingitsync&amp;action=import_config", "post");
	$form_container = new FormContainer($lang->plugingitsync);
	
	$form_container->output_row_header($lang->plugingitsync_manage_plugins_plugin_name);
	$form_container->output_row_header($lang->plugingitsync_manage_plugins_plugin_files);
	foreach($plugins_info as $codename => $plugin)
	{
		if(!empty($plugin['repo_url']))
		{
			$plugin_repo_name = '<a href="'.$plugin['repo_url'].'" target="_blank">'.$plugin['repo_name'].'</a>';
		}
		else
		{
			$plugin_repo_name = $plugin['repo_name'];
		}
		$form_container->output_cell($plugin_repo_name);
		$form_container->output_cell(implode("<br />", $plugin['files']));
		$form_container->construct_row();
		$plugin_count++;
	}
	$form_container->end();
	
	$buttons[] = $form->generate_submit_button($lang->plugingitsync_manage_plugins_import_config_import);
	$form->output_submit_wrapper($buttons);
	$form->end();
	
	$page->output_footer();
}
elseif($mybb->input['action'] == 'export_zip')
{
	$plugin = $mybb->input['plugin'];
	$query = $db->simple_select('plugingitsync', '*', 'plugin_codename = \''.$db->escape_string($plugin).'\'');
	$plugin_info = $db->fetch_array($query);
	
	if($mybb->input['export'] == 'files')
	{
		$plugin_files = plugingitsync_find_files(GIT_REPO_ROOT.$plugin_info['plugin_repo_name'].REPO_FILE_ROOT);
	}
	else
	{
		$plugin_files = plugingitsync_find_files(GIT_REPO_ROOT.$plugin_info['plugin_repo_name']);
	}
	//echo '<pre>';print_r($plugin_files);echo '</pre>';exit;
	
	$tempfile = tempnam('.', '');
	$zip = new ZipArchive;
	$archive = $zip->open($tempfile, ZipArchive::CREATE);
	foreach($plugin_files as $file)
	{
		if(file_exists($file))
		{
			if($file == GIT_REPO_ROOT.$plugin_info['plugin_repo_name'].REPO_README_PATH)
			{
				$zip->addFile($file, str_replace(GIT_REPO_ROOT.$plugin_info['plugin_repo_name'].'/', '', $file));
			}
			else
			{
				$zip->addFile($file, str_replace(GIT_REPO_ROOT.$plugin_info['plugin_repo_name'].REPO_FILE_ROOT, '', $file));
			}
		}
	}
	$zip->close();
	
	header('Content-Type: application/zip');
	header('Content-Disposition: attachment; filename='.$plugin.'.zip'); 
	readfile($tempfile);
	@unlink($tempfile);
	exit;
}
elseif($mybb->input['action'] == 'copy_to_global')
{
	if(!defined('GLOBAL_REPO_NAME') || GLOBAL_REPO_NAME == '')
	{
		flash_message($lang->plugingitsync_manage_plugins_copy_to_global_error_not_set, 'error');
		flash_message('index.php?module=tools-plugingitsync');
	}
	
	$query = $db->simple_select('plugingitsync');
	while($plugin = $db->fetch_array($query))
	{
		if(!empty($plugin['plugin_files']))
		{
			$files = @unserialize($plugin['plugin_files']);
			foreach($files as $file)
			{
				$file_path = explode('/', $file);
				$file_name = array_pop($file_path);
				for($i = 0; $i < count($file_path); $i++)
				{
					$dir_path = '';
					foreach($file_path as $key => $piece)
					{
						if($key <= $i)
						{
							$dir_path .= $piece.'/';
						}
					}
					if(!is_dir(GIT_REPO_ROOT.GLOBAL_REPO_NAME.'/'.$dir_path))
					{
						@mkdir(GIT_REPO_ROOT.GLOBAL_REPO_NAME.'/'.$dir_path, 0777);
					}
				}
				if(!@copy(MYBB_ROOT.$file, GIT_REPO_ROOT.GLOBAL_REPO_NAME.'/'.$file))
				{
					$errors[] = $file;
				}
			}
		}
	}
	
	if(!empty($errors))
	{
		$errors = "<li>".implode("</li><li>", $errors)."</li>";
		flash_message($lang->sprintf($lang->plugingitsync_manage_plugins_copy_to_global_error_files, $errors), 'error');
		admin_redirect('index.php?module=tools-plugingitsync');
	}
	else
	{
		flash_message($lang->plugingitsync_manage_plugins_copy_to_global_success, 'success');
		admin_redirect('index.php?module=tools-plugingitsync');
	}
}
elseif($mybb->input['action'] == 'do_sync')
{
	//echo '<pre>';print_r($mybb->input['sync_direction']);echo '</pre>';
	//echo '<pre>';print_r($mybb->input['files']);echo '</pre>';
	
	if($mybb->input['sync_direction'] == 'to_forum')
	{
		$url_end = '&sync_direction=to_forum';
	}
	else
	{
		$url_end = '';
	}
	
	if(empty($mybb->input['files']))
	{
		flash_message($lang->plugingitsync_sync_no_files, 'error');
		admin_redirect('index.php?module=tools-plugingitsync'.$url_end);
	}
	
	if($mybb->input['sync_direction'] == 'to_forum')
	{
		if(!isset($mybb->input['plugingitsync_confirm_repo_to_forum_sync']))
		{
			flash_message($lang->plugingitsync_confirm_sync_to_forum_confirm_missing, 'error');
			admin_redirect('index.php?module=tools-plugingitsync&sync_direction=to_forum');
		}
	}
	
	$errors = array();
	
	foreach($mybb->input['files'] as $repo => $files)
	{
		$git_root = GIT_REPO_ROOT.$repo.REPO_FILE_ROOT;
		
		if($mybb->input['sync_direction'] == 'to_forum')
		{
			$from_root = $git_root;
			$to_root = MYBB_ROOT;
		}
		else
		{
			$from_root = MYBB_ROOT;
			$to_root = $git_root;
		}
		
		foreach($files as $file)
		{
			$file_path = explode('/', $file);
			$file_name = array_pop($file_path);
			for($i = 0; $i < count($file_path); $i++)
			{
				$dir_path = '';
				foreach($file_path as $key => $piece)
				{
					if($key <= $i)
					{
						$dir_path .= $piece.'/';
					}
				}
				if($mybb->input['sync_direction'] == 'to_forum')
				{
					if(!is_dir(MYBB_ROOT.$dir_path))
					{
						@mkdir(MYBB_ROOT.$dir_path, 0777);
					}
				}
				else
				{
					if(!is_dir($git_root.$dir_path))
					{
						@mkdir($git_root.$dir_path, 0777);
					}
				}
			}
			if(!@copy($from_root.$file, $to_root.$file))
			{
				$errors[] = $file;
			}
			if(defined('GLOBAL_REPO_NAME') && GLOBAL_REPO_NAME != '')
			{
				$file_path = explode('/', $file);
				$file_name = array_pop($file_path);
				for($i = 0; $i < count($file_path); $i++)
				{
					$dir_path = '';
					foreach($file_path as $key => $piece)
					{
						if($key <= $i)
						{
							$dir_path .= $piece.'/';
						}
					}
					if(!is_dir(GIT_REPO_ROOT.GLOBAL_REPO_NAME.'/'.$dir_path))
					{
						@mkdir(GIT_REPO_ROOT.GLOBAL_REPO_NAME.'/'.$dir_path, 0777);
					}
				}
				if(!@copy(MYBB_ROOT.$file, GIT_REPO_ROOT.GLOBAL_REPO_NAME.'/'.$file))
				{
					$errors[] = $file;
				}
			}
		}
	}
	
	if($mybb->input['sync_direction'] == 'to_forum')
	{
		$redirect_message = $lang->plugingitsync_synced_to_forum;
		$redirect_message_error = $lang->plugingitsync_synced_to_forum_error;
	}
	else
	{
		$redirect_message = $lang->plugingitsync_synced_to_repo;
		$redirect_message_error = $lang->plugingitsync_synced_to_repo_error;
	}
	
	if(!empty($errors))
	{
		$errors = "<li>".implode("</li><li>", $errors)."</li>";
		flash_message($lang->sprintf($redirect_message_error, $errors), 'error');
		admin_redirect('index.php?module=tools-plugingitsync'.$url_end);
	}
	else
	{
		flash_message($redirect_message, 'success');
		admin_redirect('index.php?module=tools-plugingitsync'.$url_end);
	}
}
else
{
	$page->add_breadcrumb_item($lang->plugingitsync);
	$page->output_header($lang->plugingitsync);
	
	echo "<script type=\"text/javascript\">
	document.observe(\"dom:loaded\", function() {
		$$('.diff_link').invoke('observe', 'click', function() {
			file_id = this.id.replace('diff_link_', '');
			if(this.text == 'View Diff')
			{
				$('diff_'+file_id).show();
				this.update('Hide Diff');
			}
			else
			{
				$('diff_'+file_id).hide();
				this.update('View Diff');
			}
		});
		
		$('show_dropdown').observe('change', function() {
			url = window.location.href.replace(/(&show=)(everything|plugins_with_changes|files_with_changes)/, '');
			window.location = url+'&show='+this.value;
		});
	});
	</script>
	<style type=\"text/css\">
	.popup_button {
		background: 0;
		border: 0;
		font-weight: normal;
	}
	.popup_menu {
		font-weight: normal;
		margin-left: 5px;
	}
	</style>";
	
	$form = new Form("index.php?module=tools-plugingitsync&amp;action=do_sync", "post");
	$form_container = new FormContainer($lang->plugingitsync);
	
	$show_string = '';
	if(!empty($mybb->input['show']))
	{
		$show_string = '&show='.$mybb->input['show'];
	}
	if($mybb->input['sync_direction'] == 'to_forum')
	{
		$sync_direction_description = $lang->sprintf($lang->plugingitsync_sync_direction_to_forum, $show_string);
	}
	else
	{
		$sync_direction_description = $lang->sprintf($lang->plugingitsync_sync_direction_to_repo, $show_string);
	}
	
	$show_options = array(
		'everything' => $lang->plugingitsync_show_everything,
		'plugins_with_changes' => $lang->plugingitsync_show_plugins_with_changes,
		'files_with_changes' => $lang->plugingitsync_show_files_with_changes
	);
	if($mybb->input['show'])
	{
		$show_selected = $mybb->input['show'];
	}
	else
	{
		$show_selected = 'everything';
	}
	
	if(isset($plugins_info))
	{
		$config_plugins_info = $plugins_info;
	}
	if($mybb->input['sync_direction'] == 'to_forum')
	{
		$plugins_info = plugingitsync_get_plugins_info_from_repos();
	}
	else
	{
		$plugins_info = plugingitsync_get_plugins_info_from_database();
	}
	
	$changed_plugins = 0;
	$changed_files = 0;
	foreach($plugins_info as $plugin => $info)
	{
		$plugin_has_changes = false;
		foreach($info['files'] as $file)
		{
			$md5_git_copy = '';
			$md5_working_copy = '';
			$file_has_changes = false;
			$git_file = GIT_REPO_ROOT.$info['repo_name'].REPO_FILE_ROOT.$file;
			$working_file = MYBB_ROOT.$file;
			if(file_exists($git_file))
			{
				$md5_git_copy = md5_file($git_file);
			}
			if(file_exists($working_file))
			{
				$md5_working_copy = md5_file($working_file);
			}
			if($md5_git_copy != $md5_working_copy)
			{
				$plugin_has_changes = true;
				$file_has_changes = true;
			}
			if($file_has_changes)
			{
				$changed_files++;
			}
		}
		if($plugin_has_changes)
		{
			$changed_plugins++;
		}
	}
	if($changed_files == 1)
	{
		$plugingitsync_changes_overview_files = $lang->plugingitsync_changes_overview_files_single;
	}
	else
	{
		$plugingitsync_changes_overview_files = $lang->plugingitsync_changes_overview_files_plural;
	}
	if($changed_plugins == 1)
	{
		$plugingitsync_changes_overview_plugins = $lang->plugingitsync_changes_overview_plugins_single;
	}
	else
	{
		$plugingitsync_changes_overview_plugins = $lang->plugingitsync_changes_overview_plugins_plural;
	}
	$sync_direction_string = '';
	if($mybb->input['sync_direction'] == 'to_forum')
	{
		$sync_direction_string = '&sync_direction=to_forum';
	}
	$plugingitsync_changes_overview = $lang->sprintf($lang->plugingitsync_changes_overview, $changed_files, $plugingitsync_changes_overview_files, $changed_plugins, $plugingitsync_changes_overview_plugins, $sync_direction_string);
	
	$form_container->output_row('', '', $lang->plugingitsync_sync_direction.$sync_direction_description.'<br /><br />'.$lang->plugingitsync_description.'<br /><br />'.$lang->plugingitsync_warning.'<br /><br />'.$plugingitsync_changes_overview.'<br /><br />'.$lang->plugingitsync_show.$form->generate_select_box('show', $show_options, $show_selected, array('id' => 'show_dropdown')));
	
	if($mybb->input['sync_direction'] != 'to_forum')
	{
		$plugingitsync_manage_plugins_add_links = '<a href="index.php?module=tools-plugingitsync&action=view_existing">'.$lang->plugingitsync_manage_plugins_view_existing.'</a> - <a href="index.php?module=tools-plugingitsync&action=add">'.$lang->plugingitsync_manage_plugins_add_new.'</a> - <a href="index.php?module=tools-plugingitsync&action=add_existing">'.$lang->plugingitsync_manage_plugins_add_existing.'</a>';
		if(isset($config_plugins_info) && !empty($config_plugins_info))
		{
			$plugingitsync_manage_plugins_add_links .= ' - <a href="index.php?module=tools-plugingitsync&action=import_config">'.$lang->plugingitsync_manage_plugins_import_config.'</a>';
		}
		if(defined('GLOBAL_REPO_NAME') && GLOBAL_REPO_NAME != '')
		{
			$plugingitsync_manage_plugins_add_links .= ' - <a href="index.php?module=tools-plugingitsync&action=copy_to_global">'.$lang->plugingitsync_manage_plugins_copy_to_global.'</a>';
		}
		$form_container->output_row($lang->plugingitsync_manage_plugins, $lang->plugingitsync_manage_plugins_desc, $plugingitsync_manage_plugins_add_links);
	}
	
	$rows = 0;
	foreach($plugins_info as $plugin => $info)
	{
		$files = '';
		$plugin_has_changes = false;
		$row_style = array();
		foreach($info['files'] as $file)
		{
			$md5_git_copy = '';
			$md5_working_copy = '';
			$contents_git_copy = '';
			$contents_working_copy = '';
			$error_git_copy = '';
			$error_working_copy = '';
			$file_has_changes = false;
			$checked = false;
			$git_file = GIT_REPO_ROOT.$info['repo_name'].REPO_FILE_ROOT.$file;
			$working_file = MYBB_ROOT.$file;
			$md5_file_name = md5($file);
			if(file_exists($git_file))
			{
				$md5_git_copy = md5_file($git_file);
				$contents_git_copy = file_get_contents($git_file);
			}
			else
			{
				$error_git_copy = '<span style="color: red;">'.$lang->plugingitsync_not_in_repo.'</span><br />';
			}
			if(file_exists($working_file))
			{
				$md5_working_copy = md5_file($working_file);
				$contents_working_copy = file_get_contents($working_file);
			}
			else
			{
				$error_git_copy = '';
				$error_working_copy = '<span style="color: red; font-weight: bold;">'.$lang->plugingitsync_not_in_forum.'</span><br />';
			}
			$diff = '';
			$diff_link = '';
			if($md5_git_copy != $md5_working_copy)
			{
				$checked = true;
				$plugin_has_changes = true;
				$file_has_changes = true;
				$diff = new Text_Diff('auto', array(explode("\n", $contents_git_copy), explode("\n", $contents_working_copy)));
				$renderer = new Text_Diff_Renderer_unified();
				$diff = explode("\n", htmlspecialchars($renderer->render($diff)));
				foreach($diff as &$line)
				{
					if(substr($line, 0, 2) == "@@")
					{
						$line = "<span style=\"background: #ADCBE7;\">" . $line . "</span>";
					}
					elseif(substr($line, 0, 1) == "+")
					{
						$line = "<span style=\"background: #D6ECA6;\">" . $line . "</span>";
					}
					elseif(substr($line, 0, 1) == "-")
					{
						$line = "<span style=\"background: #FBE3E4;\">" . $line . "</span>";
					}
				}
				$diff = '<pre class="differential" id="diff_'.$md5_file_name.'" style="display: none;">'.implode("\n", $diff).'</pre>';
				$diff_link = ' <a href="javascript:void(0)" class="diff_link" id="diff_link_'.$md5_file_name.'">View Diff</a>';
			}
			if(!($mybb->input['show'] == 'files_with_changes' && !$file_has_changes))
			{
				$files .= '<div class="file_row">'.$form->generate_check_box('files['.$info['repo_name'].'][]', $file, '', array('checked' => $checked, 'id' => 'file_'.$md5_file_name)).' <label for="file_'.$md5_file_name.'" style="font-weight: normal;">./'.$file.'</label>'.$diff_link.'<br />'.$error_git_copy.$error_working_copy.$diff.'</div>';
			}
		}
		
		if(($mybb->input['show'] == 'plugins_with_changes' || $mybb->input['show'] == 'files_with_changes') && !$plugin_has_changes)
		{
			continue;
		}
		if($plugin_has_changes && $mybb->input['show'] != 'plugins_with_changes' && $mybb->input['show'] != 'files_with_changes')
		{
			$row_style = array('style' => 'background: #D6ECA6;');
		}
		
		$popup = new PopupMenu("plugin_{$info['codename']}", $lang->plugingitsync_manage_plugins_controls);
		if($mybb->input['sync_direction'] != 'to_forum')
		{
			$popup->add_item($lang->plugingitsync_manage_plugins_edit, 'index.php?module=tools-plugingitsync&action=edit&plugin='.$info['codename']);
		}
		if(@file_exists(GIT_REPO_ROOT.$info['repo_name'].REPO_README_PATH))
		{
			$popup->add_item($lang->plugingitsync_manage_plugins_edit_readme, 'index.php?module=tools-plugingitsync&action=edit_readme&plugin='.$info['codename']);
		}
		if(!empty($info['repo_url']))
		{
			$popup->add_item($lang->plugingitsync_manage_plugins_view_repo, $info['repo_url'], 'window.open(\''.$info['repo_url'].'\'); return false;');
		}
		$popup->add_item($lang->plugingitsync_manage_plugins_export_zip_files, 'index.php?module=tools-plugingitsync&action=export_zip&export=files&plugin='.$info['codename']);
		$popup->add_item($lang->plugingitsync_manage_plugins_export_zip_all, 'index.php?module=tools-plugingitsync&action=export_zip&export=all&plugin='.$info['codename']);
		$popup = $popup->fetch();
		
		$form_container->output_row($info['repo_name'].$links.$popup, '', $files, '', $row_style);
		$rows++;
	}
	
	if($rows == 0 && ($mybb->input['show'] == 'plugins_with_changes' || $mybb->input['show'] == 'files_with_changes'))
	{
		$form_container->output_row('', '', $lang->plugingitsync_show_no_results);
	}
	
	if($rows > 0 && $mybb->input['sync_direction'] == 'to_forum')
	{
		$form_container->output_row('<span id="plugingitsync_confirm_repo_to_forum_sync_title">'.$lang->plugingitsync_confirm_sync_to_forum.'</span>', '', $lang->plugingitsync_confirm_sync_to_forum_desc.'<br /><br />'.$form->generate_check_box('plugingitsync_confirm_repo_to_forum_sync', 1, '', array('id' => 'plugingitsync_confirm_repo_to_forum_sync')).' <span id="plugingitsync_confirm_repo_to_forum_sync_label">'.$lang->plugingitsync_confirm_sync_to_forum_confirm.'</span>');
	}
	
	$form_container->end();
	if($mybb->input['sync_direction'] == 'to_forum')
	{
		echo $form->generate_hidden_field('sync_direction', 'to_forum');
		$plugingitsync_submit = $lang->plugingitsync_submit_to_forum;
	}
	else
	{
		echo $form->generate_hidden_field('sync_direction', 'to_repo');
		$plugingitsync_submit = $lang->plugingitsync_submit_to_repo;
	}
	if($rows > 0)
	{
		$buttons[] = $form->generate_submit_button($plugingitsync_submit, array('id' => 'plugingitsync_submit'));
		$form->output_submit_wrapper($buttons);
	}
	$form->end();
	
	if($rows > 0 && $mybb->input['sync_direction'] == 'to_forum')
	{
		echo "<script type=\"text/javascript\">
		document.observe(\"dom:loaded\", function() {
			$('plugingitsync_submit').observe('click', function(Event) {
				checkboxCheck(Event);
			});
			$('plugingitsync_confirm_repo_to_forum_sync').observe('change', function(Event) {
				checkboxCheck(Event);
			});
		});
		function checkboxCheck(Event)
		{
			$('plugingitsync_confirm_repo_to_forum_sync_title').style.color = '#000000';
			$('plugingitsync_confirm_repo_to_forum_sync_label').style.color = '#000000';
			if(!$('plugingitsync_confirm_repo_to_forum_sync').checked)
			{
				$('plugingitsync_confirm_repo_to_forum_sync_title').style.color = '#FF0000';
				$('plugingitsync_confirm_repo_to_forum_sync_label').style.color = '#FF0000';
				Event.stop();
			}
		}
		</script>";
	}
	
	$page->output_footer();
}
?>