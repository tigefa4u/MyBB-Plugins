<?php
/**
 * Plugin Git Sync - Admin Language File

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

$l['plugingitsync'] = 'Plugin Git Sync';
$l['plugingitsync_description'] = 'The form below lists all the files that are in your repositories. A file will be ticked if the forum copy and Git copy are not the same. Selected files will be copied from your forum to the appropriate Git repository.';
$l['plugingitsync_warning'] = '<span class="smalltext"><strong>Note:</strong> This tool does not perform any diffs or merge conflict checks on the files when copying them between your forum and your repositories. If the repository may have changes from an exernal source, sync the files from the forum to the repository <em>first</em>, <strong>before</strong> updating the repository, and let GIT deal with any conflicts when the repository is updated. Then you can sync the files from the repository to the forum to get the external changes, and your changes will be included.</span>';
$l['plugingitsync_sync_direction'] = 'Sync direction: ';
$l['plugingitsync_sync_direction_to_repo'] = '<strong>Forum -&gt; Repository</strong> <a href="index.php?module=tools-plugingitsync&amp;sync_direction=to_forum{1}">Switch sync direction to repository -&gt; forum</a>';
$l['plugingitsync_sync_direction_to_forum'] = '<strong>Repository -&gt; Forum</strong> <a href="index.php?module=tools-plugingitsync{1}">Switch sync direction to forum -&gt; repository</a>';
$l['plugingitsync_changes_overview'] = 'There are changes to <a href="index.php?module=tools-plugingitsync{5}&show=files_with_changes"><strong>{1}</strong> {2}</a> in <a href="index.php?module=tools-plugingitsync{5}&show=plugins_with_changes"><strong>{3}</strong> {4}</a>.';
$l['plugingitsync_changes_overview_files_single'] = 'file';
$l['plugingitsync_changes_overview_files_plural'] = 'files';
$l['plugingitsync_changes_overview_plugins_single'] = 'plugin';
$l['plugingitsync_changes_overview_plugins_plural'] = 'plugins';
$l['plugingitsync_show'] = 'Show: ';
$l['plugingitsync_show_everything'] = 'Everything';
$l['plugingitsync_show_plugins_with_changes'] = 'Plugins with changes';
$l['plugingitsync_show_files_with_changes'] = 'Files with changes';
$l['plugingitsync_show_no_results'] = 'There are no changes matching your filter.';
$l['plugingitsync_confirm_sync_to_forum'] = 'Confirm repository to forum sync';
$l['plugingitsync_confirm_sync_to_forum_desc'] = 'This action will perform a direct copy of the files from the repository to your forum. Any unsynced changes to the files in your forum will be overwritten and lost. <a href="index.php?module=tools-plugingitsync">Switch to forum -&gt; repository sync mode</a> if you have any unsynced changes and sync them to the repository first.';
$l['plugingitsync_confirm_sync_to_forum_confirm'] = 'Yes, I understand the above';
$l['plugingitsync_confirm_sync_to_forum_confirm_missing'] = 'Please confirm you have read the warning for syncing from the repository to the forum.';
$l['plugingitsync_not_in_repo'] = 'This file does not exist in the repository.';
$l['plugingitsync_not_in_forum'] = 'This file does not exist in your forum file system.';
$l['plugingitsync_submit_to_repo'] = 'Sync forum files to GIT repositories';
$l['plugingitsync_submit_to_forum'] = 'Sync GIT repositories to forum files';
$l['plugingitsync_synced_to_repo'] = 'The files were successfully synced to the repository.';
$l['plugingitsync_synced_to_forum'] = 'The files were successfully synced to the forum.';
$l['plugingitsync_synced_to_repo_error'] = 'The following files could not be synced to the repository.<br /><ul>{1}</ul>';
$l['plugingitsync_synced_to_forum_error'] = 'The following files could not be synced to the forum.<br /><ul>{1}</ul>';
$l['plugingitsync_sync_no_files'] = 'Please select some files to sync.';
$l['plugingitsync_manage_plugins'] = 'Manage Plugins';
$l['plugingitsync_manage_plugins_desc'] = 'Manage your plugins and repositories here, including configuring the repository URL and list of files for each plugin.';
$l['plugingitsync_manage_plugins_view_existing'] = 'View Existing Plugins';
$l['plugingitsync_manage_plugins_add_new'] = 'Add New Plugin';
$l['plugingitsync_manage_plugins_add_name'] = 'Plugin Name';
$l['plugingitsync_manage_plugins_add_name_desc'] = 'The name of the plugin.';
$l['plugingitsync_manage_plugins_add_codename'] = 'Plugin Codename';
$l['plugingitsync_manage_plugins_add_codename_desc'] = 'The \'codename\' of the plugin is the same as the name of the main plugin file, for example, if the plugin file was called myplugin.php, the codename would be \'myplugin\'.';
$l['plugingitsync_manage_plugins_add_repo_name'] = 'Repository Name';
$l['plugingitsync_manage_plugins_add_repo_name_desc'] = 'This is the name of the repository inside \'<em>{1}</em>\'';
$l['plugingitsync_manage_plugins_add_repo_url'] = 'Remote Repository URL';
$l['plugingitsync_manage_plugins_add_repo_url_desc'] = 'The URL of the remote repository.';
$l['plugingitsync_manage_plugins_add_files'] = 'Plugin Files';
$l['plugingitsync_manage_plugins_add_files_desc'] = 'A list of files that this plugin includes. The files are relative to the root of your MyBB installation. You do not need to include a leading forward slash. Put one file on each line.';
$l['plugingitsync_manage_plugins_add_edit_error_no_name'] = 'Please enter the name of the plugin.';
$l['plugingitsync_manage_plugins_add_edit_error_no_codename'] = 'Please enter the codename of the plugin.';
$l['plugingitsync_manage_plugins_add_edit_error_no_repo_name'] = 'Please enter the repository name of the plugin.';
$l['plugingitsync_manage_plugins_add_edit_error_invalid_repo'] = 'The specified repository (\'{1}\') is not a valid GIT repository. Please check the repository name and try again.';
$l['plugingitsync_manage_plugins_add_edit_error_no_files'] = 'Please specify the path of at least one file this plugin includes.';
$l['plugingitsync_manage_plugins_add_edit_error_plugin_name_exists'] = 'A plugin with this name already exists.';
$l['plugingitsync_manage_plugins_add_edit_error_plugin_codename_exists'] = 'A plugin with this codename already exists.';
$l['plugingitsync_manage_plugins_add_edit_error_plugin_repo_name_exists'] = 'A plugin using this repository already exists.';
$l['plugingitsync_manage_plugins_add_success'] = 'The plugin was added successfully.';
$l['plugingitsync_manage_plugins_edit_existing'] = 'Edit Existing Plugin';
$l['plugingitsync_manage_plugins_edit_success'] = 'The plugin was edited successfully.';
$l['plugingitsync_manage_plugins_add_existing'] = 'Add Existing Plugin';
$l['plugingitsync_manage_plugins_add_existing_error_none'] = 'There are no existing plugins that have not already been imported.';
$l['plugingitsync_manage_plugins_edit_existing_no_exist'] = 'The specified plugin does not exist, you can add it instead.';
$l['plugingitsync_manage_plugins_edit_readme_invalid_plugin'] = 'The specified plugin does not exist.';
$l['plugingitsync_manage_plugins_edit_readme_no_readme'] = 'This plugin doesn\'t appear to have a readme file.';
$l['plugingitsync_manage_plugins_edit_readme_error_loading'] = 'There was an error loading the readme.';
$l['plugingitsync_manage_plugins_edit_readme_error_empty'] = 'Please enter some content.';
$l['plugingitsync_manage_plugins_edit_readme_error_writing'] = 'Could not write to the readme file.';
$l['plugingitsync_manage_plugins_edit_readme_success'] = 'The readme was successfully updated.';
$l['plugingitsync_manage_plugins_import_config'] = 'Import Config File Plugins';
$l['plugingitsync_manage_plugins_import_config_error_none'] = 'There are no plugins in the config file to import.';
$l['plugingitsync_manage_plugins_import_config_error_none_not_import'] = 'There are no plugins in the config file that have not already been imported. You can now remove the $plugins_info array from the config file.';
$l['plugingitsync_manage_plugins_import_config_import'] = 'Import Plugins';
$l['plugingitsync_manage_plugins_import_config_success'] = 'The plugins were successfully imported from the config file. You can now remove the $plugins_info array from the config file.<br /><br />You may want to <a href="index.php?module=tools-plugingitsync&action=view_existing">edit the imported plugins</a> to specify the name of each plugin.';
$l['plugingitsync_manage_plugins_copy_to_global'] = 'Copy Files to Global Repo';
$l['plugingitsync_manage_plugins_copy_to_global_error_not_set'] = 'Please set a value for GLOBAL_REPO_NAME in ./inc/plugins/plugingitsync/config.php';
$l['plugingitsync_manage_plugins_copy_to_global_error_files'] = 'The following files could not be moved:<ul>{1}</ul>';
$l['plugingitsync_manage_plugins_copy_to_global_success'] = 'All plugin files were copied to the global respoitory.';
$l['plugingitsync_manage_plugins_plugin_name'] = 'Plugin Name';
$l['plugingitsync_manage_plugins_plugin_files'] = 'Plugin Files';
$l['plugingitsync_manage_plugins_controls'] = 'Controls';
$l['plugingitsync_manage_plugins_add'] = 'Add';
$l['plugingitsync_manage_plugins_edit'] = 'Edit Settings';
$l['plugingitsync_manage_plugins_submit'] = 'Submit';
$l['plugingitsync_manage_plugins_edit_readme'] = 'Edit Readme';
$l['plugingitsync_manage_plugins_view_repo'] = 'View Repo';
$l['plugingitsync_manage_plugins_export_zip_files'] = 'Export Zip (Files)';
$l['plugingitsync_manage_plugins_export_zip_all'] = 'Export Zip (Everything)';
?>