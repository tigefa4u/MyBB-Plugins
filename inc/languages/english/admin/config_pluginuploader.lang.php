<?php
/**
 * Plugin Uploader 1.1 - Admin Language File

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

$l['pluginuploader'] = "Plugin Uploader";
$l['pluginuploader_upload_plugin'] = "Upload Plugin";
$l['pluginuploader_upload_plugin_desc'] = "Upload a plugin .zip archive or .php file.";
$l['pluginuploader_plugin'] = "Plugin";
$l['pluginuploader_plugin_file'] = "Browse for the plugin to upload. It must be a .zip archive or a single .php plugin file.";
$l['pluginuploader_plugin_url'] = "Or, enter the URL of a plugin on the MyBB Mods site or the direct URL to a zip/attachment for a plugin.";
$l['pluginuploader_plugin_desc_warning'] = "<strong>Note: <span style=\"color: red;\">This will not be able to upload every plugin .zip package.</span></strong> If the files inside the .zip are not organised in a 'sensible' or logical way it will not be possible to put the files in the correct locations.";
$l['pluginuploader_plugin_name'] = "Plugin Name";
$l['pluginuploader_plugin_version'] = "Plugin Version";
$l['pluginuploader_plugin_description'] = "Plugin Description";
$l['pluginuploader_plugin_screenshots'] = "Plugin Screenshots";
$l['pluginuploader_plugin_screenshots_desc'] = "Click to enlarge.";
$l['pluginuploader_install_activate'] = "Install and/or Activate?";
$l['pluginuploader_install_activate_desc'] = "Do you want to automatically install and/or activate this plugin after it has been uploaded?";
$l['pluginuploader_activate'] = "Activate plugin?";
$l['pluginuploader_activate_desc'] = "Do you want to automatically activate this plugin?";
$l['pluginuploader_deactivate'] = "Deactivate Plugin?";
$l['pluginuploader_deactivate_desc'] = "<strong><span style=\"color: red;\">The plugin you're uploading is already activated and running on your forum.</span></strong> In order to continue, it needs to be deactivated.<br /><br /><strong>Note: This could cause some data loss.</strong> Depending on how this plugin is written, deactivating may remove any settings/templates the plugin added, but re-activating will add them back (with their default values/contents). Deactivating may also undo any template edits the plugin made, but re-activating should make the change again. You should not lose any information stored in the database by doing this. You will be able to re-activate the plugin after it has been re-imported.<br /><br />If you choose not to deactivate, you will not be able to continue importing the plugin.<br ><br /><strong><span style=\"color: red;\">NOTE:</span> If you continue, you must follow the process through to the end.</strong> Some of the new files may be moved into your file system before the full import has been completed, and some may be deleted. If you abort the import after the next step, you may experience problems if you reactivate the plugin as it may have newer versions of some files, and some files may be missing. You will, however, be able to reimport the older version to resolve any potential problems.";
$l['pluginuploader_import_non_php_root_files'] = "Import non PHP root files?";
$l['pluginuploader_import_non_php_root_files_desc'] = "This plugin comes with files in the root folder that are not PHP files. These are probably documentation/example files that are not used by the plugin and won't affect it working, so it is recommended not to import these files. However, if these files are needed for the plugin to work, you can upload this plugin again and set this option to Yes to import them.";
$l['can_upload_plugins'] = "Can upload plugins?";

$l['pluginuploader_error_no_ziparchive_class'] = "Sorry, your host does not have the ability to extract .zip archives.";
$l['pluginuploader_error_upload'] = "Could not open .zip archive.";
$l['pluginuploader_error_temp_dir'] = "Could not create a temporary plugin folder in ./inc/plugins/temp/. Please make sure it exists and is CHMOD to 777.";
$l['pluginuploader_error_extract'] = "Could not extract archive to temporary plugin folder. Please try again.";
$l['pluginuploader_error_move_single_file'] = "Could not move plugin file to temporary plugin folder. Please try again.";
$l['pluginuploader_invalid_type'] = "You have uploaded an invalid plugin or specified an invalid URL. Please upload either a .zip archive or single .php plugin file or specify a URL to a valid attachment or file.";
$l['pluginuploader_error_path'] = "Could not work out plugin file structure.";
$l['pluginuploader_error_plugin_file'] = "Could not find main plugin file.";
$l['pluginuploader_error_plugin_pluginuploader'] = "Hold on a minute, you can't use this plugin uploader to try and upload a copy of this plugin. That's even worse than trying to divide by zero. Move along now, nothing to see here. You're going to have to upload the files for this plugin the good old fashioned way, sorry, but well done for trying.";
$l['pluginuploader_plugin_exists'] = "<span style=\"color: red;\"><strong>Note: This plugin is already uploaded to your forum.</strong></span>";
$l['pluginuploader_new_version_warning'] = "<span style=\"color: red;\"><strong>A newer version of {1} is available.</strong></span><br /><br />It has been detected that a newer version of {1} is available on the MyBB Mods site.<br /><br />Your version: {2}<br />Available version: {3}<br /><br />You can import the new version here: <a href=\"index.php?module=config-plugins&action=pluginuploader&action2=install&plugin={4}&my_post_key={5}\"><strong>Install {1} {3}</strong></a><br /><br />Alternatively, you can download {1} {3} <a href=\"http://mods.mybb.com/view/{4}\" target=\"_blank\"><strong>here</strong></a> and import the zip afterwards.";
$l['pluginuploader_plugin_same_version'] = "You are uploading the same version of the plugin that you have currently got installed.";
$l['pluginuploader_plugin_new_version'] = "You are uploading a newer version of the plugin than the version you have currently got installed.";
$l['pluginuploader_plugin_old_version'] = "<strong>You are uploading an <span style=\"color: red;\">older version</span> of the plugin than the version you have currently got installed.</strong>";
$l['pluginuploader_plugin_old_version_2'] = "<span style=\"color: red;\"><strong>Warning: This is an older version of the plugin than the version you have currently got installed.</strong></span>";
$l['pluginuploader_plugin_upgrade_warning'] = "If you continue, it will reimport <strong>all</strong> the files for this plugin, which will overwrite any edits you have made to them. It will also add any new/missing files.";
$l['pluginuploader_error_move_files'] = "There was an error importing this plugin. The following files could not be moved to their proper locations:<br /><ul>{1}</ul>You can either try importing the plugin again, or uploading these files manually.";
$l['pluginuploader_error_move_external_files'] = "There was an error importing this plugin. This plugin has extra files, including language files, that may need to be included in order to run the info function. These files could not be moved to their proper locations:<br /><ul>{1}</ul>You can either try importing the plugin again, or uploading these files manually.";
$l['pluginuploader_error_no_user'] = "<br /><br />One or more of these files already existed on your server, but has an owner of 'nobody'. Please contact your host to resolve this issue before trying to upload this plugin again, as currently the file(s) that already exist on your server may not be able to be replaced with the new copy.";
$l['pluginuploader_delete_warning'] = "<span style=\"color: red;\"><strong>WARNING</strong></span><br /><br />If you continue, this process will <strong>deactivate</strong> and (if applicable) <strong>uninstall</strong> this plugin, and <strong>delete all files</strong> for it.<br /><br /><strong>It will (try to) completely remove all traces of it from your forum.</strong><br /><br />The following files will be deleted:<br /><ul>{1}</ul>If any of these files are core files, please make sure to upload them again from a fresh download of your version of MyBB.<br /><br />Are you sure you wish to continue?";
$l['pluginuploader_delete_warning_no_files'] = "<span style=\"color: red;\"><strong>WARNING</strong></span><br /><br />If you continue, this process will <strong>deactivate</strong> and (if applicable) <strong>uninstall</strong> this plugin.<br /><br />As the files for this plugin were not uploaded by the plugin uploader, only the main plugin file can be deleted. Other files for this plugin will be left where they are.<br /><br />If you would like to delete all the files for this plugin, you can import the plugin again via the plugin uploader, and then choose to delete it from the plugin list again.<br /><br />Are you sure you wish to continue?";
$l['pluginuploader_delete_invalid_plugin'] = "Could not delete plugin. Invalid plugin specified.";
$l['pluginuploader_delete_errors'] = "The following files could not be deleted:<br /><ul>{1}</ul>";
$l['pluginuploader_success'] = "Plugin uploaded successfully. You can now install and/or activate it below.";
$l['pluginuploader_delete_success'] = "Plugin deleted successfully.";

$l['delete'] = "Delete";
$l['submit'] = "Submit";
$l['pluginuploader_import_plugin'] = "Import Plugin";
$l['pluginuploader_activated'] = "activated";
$l['pluginuploader_deactivated'] = "deactivated";
$l['pluginuploader_install'] = 'Install';
$l['pluginuploader_reimport'] = 'Reimport from MyBB Mods Site';
$l['pluginuploader_upgrade'] = 'Upgrade';
$l['pluginuploader_mods_site_unavailable'] = 'Plugin Uploader Mods Site integration is unavailable (<a href="index.php?module=config-plugins&action=pluginuploader&action2=mods_site_integration">Why?</a>)';
$l['pluginuploader_agree_and_download'] = 'Agree to Licence and Install';
$l['pluginuploader_licence'] = 'Licence for {1}';
$l['pluginuploader_licence_desc'] = 'The following licence applies to this download.';
$l['pluginuploader_downloaded_from_mods'] = 'This plugin has been successfully downloaded from the MyBB Mods site and is now ready to be imported.';
$l['pluginuploader_download_from_mods_invalid'] = 'This plugin could not be found on the MyBB Mods Site. Please check <a href="http://mods.mybb.com/view/{1}" target="_blank">the URL</a>, and see if you can use it to view the plugin on the MyBB Mods Site; the URL may be incorrect, or the plugin may currently be awaiting validation.';
$l['pluginuploader_error_downloading_from_mods'] = 'There was an error downloading this plugin from the MyBB Mods site. This may be because the MyBB Mods site is down. To check, please try to download this plugin manually <a href="http://mods.mybb.com/download/{1}" target="_blank"><strong>here</strong></a> and import it using the form below if you are able to download it. If you can access the MyBB Mods site and download the plugin manually, then the issue with not being able to download it automatically lies with your server.';
$l['pluginuploader_error_downloading_from_mods_error_ini'] = 'It has been detected that safe_mode and/or open_basedir is enabled in your PHP configuration.';
$l['pluginuploader_error_downloading_from_mods_error_php_version'] = 'As well as this, it has been detected that your PHP version is less that PHP 5.3.4. Disabling safe_mode and open_basedir or upgrading to PHP 5.3.4 or higher would allow you to download plugins from the MyBB Mods site.';
$l['pluginuploader_error_downloading_from_mods_contact_host'] = 'Please contact your host with this information to see if they are able to help you. <a href="index.php?module=config-plugins&action=pluginuploader&action2=mods_site_integration">More information can be found here.</a>';
$l['pluginuploader_error_downloading_from_mods_unknown_error'] = 'Please contact me on <a href="mailto:matt.rogowski@mybb.com"><strong>matt.rogowski@mybb.com</strong></a> to help debug this issue further.';
$l['pluginuploader_error_downloading_from_mods_ftp_desc'] = 'Please use the form below to enter your FTP details and then you will be redirected back to the page to install {1}.';
$l['pluginuploader_error_downloading_from_mods_ftp_added_extra'] = '<br /><br />You can now install this plugin.';

$l['pluginuploader_from_url_login_required'] = 'It has been detected that this URL may be an attachment on a MyBB forum, but requires login details to access. Please enter your login details for this forum below. These details will not be stored anywhere.';
$l['pluginuploader_from_url_site_needs_login'] = 'Will this require login details to access? (MyBB Powered forums only)';
$l['pluginuploader_from_url_site_doesnt_need_login'] = 'This won\'t need login details';
$l['pluginuploader_from_url_site_login'] = 'Enter both your username and password.';
$l['pluginuploader_from_url_site_login_username'] = 'Username:';
$l['pluginuploader_from_url_site_login_password'] = 'Password:';
$l['pluginuploader_from_url_site_login_error'] = 'Could not log in with the provided login details';
$l['pluginuploader_from_url_no_file'] = 'Could not find a file at that URL. Please check the URL is correct and try again. Alternatively, if you think this URL may require you to login before you can access the download, tick the checkbox next to the URL box and enter your login details for that site.';

$l['pluginuploader_install_password_message_title'] = "WARNING - IMPORTANT - PLEASE READ THIS TO THE END";
$l['pluginuploader_install_password_message'] = "This plugin allows you to import a plugin and have the files extracted to their proper locations. However, it is impossible for the uploader to check whether the files in the zip are an actual plugin, or malicious files intended to do harm to your forum. Because of this, it is physically possible for someone who gains admin access to upload potentially malicious files to your server via the plugin uploader. This is not a flaw in the plugin uploader itself, but merely an unavoidable flaw in the concept of allowing plugin files to be uploaded and imported via the Admin Control Panel rather than via FTP.<br /><br />For this reason, by default, other admins will not have the ability to upload plugins, and you should only give other admin access to it if you absolutely trust them and they absolutely need it.<br /><br />A password is also required to be entered when uploading a plugin. This is so that even if somebody gains access to an admin account that has the ability to upload plugins, they will not be able to upload a plugin as they will not know the password.<br /><br />The password you create <strong>SHOULD NOT</strong> be the same as your admin password, and it <strong>SHOULD NOT</strong> be the same as the database password. You <strong>SHOULD NOT</strong> post this password anywhere. This includes the Administrator Notes in the ACP, your Personal Notepad in your User CP, or any forums, even if they are private forums. <strong>Remember, this password is to stop people uploading malicious files to your server, and should be kept as secret as your database details and FTP details.</strong><br /><br />Please enter the password you want to use below, and it will be stored in the database. Any Super Administrator will be able to change this password at any time, as long as they know the current password. This password will also be required when uninstalling the plugin.";
$l['pluginuploader_install_password_message_not_super_admin'] = "As you are not a super admin, you are not able to set a password for the plugin uploader. Please contact a super administrator.";
$l['pluginuploader_password'] = "Password";
$l['pluginuploader_password_desc'] = "Enter the password you want to use for the plugin uploader.";
$l['pluginuploader_password_confirm'] = "Confirm Password";
$l['pluginuploader_password_confirm_desc'] = "Enter the password again.";
$l['pluginuploader_password_current'] = "Current Password";
$l['pluginuploader_password_current_desc'] = "Enter the current password.";
$l['pluginuploader_password_upload_desc'] = "Enter the plugin uploader password to be able to upload a plugin.";
$l['pluginuploader_password_stored_cookie'] = "The password for the Plugin Uploader is currently stored as a cookie in your browser.";
$l['pluginuploader_password_not_super_admin'] = "You cannot change the password as you are not a super admin.";
$l['pluginuploader_password_incorrect'] = "The password you have entered is incorrect. Please try again.";
$l['pluginuploader_password_current_incorrect'] = "The current password you have entered is incorrect. Please try again.";
$l['pluginuploader_password_not_same'] = "The passwords you have entered do not match. Please try again.";
$l['pluginuploader_password_empty'] = "You cannot enter an empty password. Please try again.";
$l['pluginuploader_password_updated'] = "Password updated successfully.";
$l['pluginuploader_password_change'] = "<a href=\"index.php?module=config-plugins&action=pluginuploader&action2=password\">Change password</a>";
$l['pluginuploader_password_clear'] = "<a href=\"index.php?module=config-plugins&action=pluginuploader&action2=clear_password&my_post_key={1}\">Clear password cookie</a>";
$l['pluginuploader_password_change_clear_or'] = ' or ';
$l['pluginuploader_password_change_title'] = "Change Plugin Uploader Password";
$l['pluginuploader_password_cleared'] = "Password cleared successfully.";
$l['pluginuploader_password_remember'] = "Remember password? <span class=\"smalltext\">This will mean you won't have to enter the password every time you upload a plugin, when logged in with this account, on this computer, using this browser.</span>";

$l['pluginuploader_ftp_required'] = "FTP Connection Required";
$l['pluginuploader_ftp_required_desc'] = "It has been detected that your server may not be able to move the plugin files to their proper locations using standard PHP functions. Instead, the Plugin Uploader may need to connect via FTP to move the files.<br /><br />";
$l['pluginuploader_ftp_optional'] = 'FTP Connection Optional';
$l['pluginuploader_ftp_optional_desc'] = 'The Plugin Uploader should be able to move the files using standard PHP functions. However, you may want to move them via FTP instead. This will mean the ownerships of the files will be that of your FTP user instead of Apache, which means you will be able to edit/update/delete files that get uploaded, whereas you would not be able to do that if the owner was Apache.<br /><br />';
$l['pluginuploader_ftp_desc_link_set'] = "<a href=\"index.php?module=config-plugins&action=pluginuploader&action2=ftp_details\">Set/edit FTP details</a>{1}.<br /><br />";
$l['pluginuploader_ftp_desc_link_clear'] = " or <a href=\"index.php?module=config-plugins&action=pluginuploader&action2=clear_ftp_details&my_post_key={1}\">clear FTP details</a>";
$l['pluginuploader_ftp_desc_extra'] = "<span class=\"smalltext\">More information on the Plugin Uploader connecting via FTP can be found here: <a href=\"http://mattrogowski.co.uk/mybb/thread-197.html\" target=\"_blank\">http://mattrogowski.co.uk/mybb/thread-197.html</a></span>";
$l['pluginuploader_ftp_missing_test_file'] = "<span style=\"color: red; font-weight: bold;\">It has been detected that ./inc/plugins/temp/test.php does not exist; this is the file used to check if an FTP connection is required. Please create this file (just an empty file called test.php in ./inc/plugins/temp/), come back to this page, and see if it still says an FTP connection is required.</span>";
$l['pluginuploader_ftp_details_stored_cookie'] = 'Your FTP details are currently stored in a cookie in your browser.';
$l['pluginuploader_ftp_details_stored_database'] = 'Your FTP details are currently stored in the database.';
$l['pluginuploader_ftp_message_success'] = "A successful FTP connection has been established; the plugin uploader should function correctly.";
$l['pluginuploader_ftp_message_no_ftp'] = "The FTP PHP module is not available. This is required to connect via FTP.";
$l['pluginuploader_ftp_message_missing_config'] = "Before you can enter your FTP details, you must add the following code into <em>./inc/config.php</em>, before the ?&gt; at the end of the file.";
$l['pluginuploader_ftp_message_missing_config_flash'] = "Before you can enter your FTP details, you must add the encryption key to <em>./inc/config.php</em>. See the error below for more information on this.";
$l['pluginuploader_ftp_message_config_wrong'] = "The encryption key has changed since your FTP details were encrypted. Due to this it will not be possible to recover your encrypted details, so you will need to enter your details again.";
$l['pluginuploader_ftp_message_missing_details'] = "Please enter your FTP details";
$l['pluginuploader_ftp_details'] = "Enter FTP Details";
$l['pluginuploader_ftp_host'] = "FTP Host";
$l['pluginuploader_ftp_host_desc'] = "Enter the name of the host you want to connect to. This can be a domain or an IP address.";
$l['pluginuploader_ftp_host_missing'] = "Please enter your FTP host.";
$l['pluginuploader_ftp_user'] = "FTP User";
$l['pluginuploader_ftp_user_desc'] = "Enter the user you use to connect to FTP with.";
$l['pluginuploader_ftp_user_missing'] = "Please enter your FTP user.";
$l['pluginuploader_ftp_password'] = "FTP Password";
$l['pluginuploader_ftp_password_desc'] = "Enter the password for this FTP user.";
$l['pluginuploader_ftp_password_missing'] = "Please enter your FTP password.";
$l['pluginuploader_ftp_storage_location'] = "FTP Details Storage Location";
$l['pluginuploader_ftp_storage_location_desc'] = "You can choose to store your FTP details either in a cookie in your browser, or in the database.<br /><br /><strong>Browser Cookie</strong><br />Storing in a cookie is more secure as it is only stored in the browser you are currently using, and can only be accessed when you view this site in this browser. However, if you use multiple computers or multiple admins need to use the Plugin Uploader, you/they would need to enter the FTP details individually as they are stored locally on the computer being used.<br /><br /><strong>Database</strong><br />Storing in the database means you won't need to enter the FTP details more than once if you use multiple computers or multiple admins need to use the Plugin Uploader; they will be stored in a central place and will always be available. However, storing them in the database is less secure than storing in a cookie, as if someone gains access to your database and file system they would be able to access and unencrypt the FTP details. If the details are stored in a cookie, you would have to access a malicious script for the FTP details to be accessed.<br /><br /><strong>The FTP details are encrypted whether they are stored in a cookie or the database.</strong> If you are unsure on which setting to choose, choose Browser Cookie.";
$l['pluginuploader_ftp_storage_location_missing'] = "Please select where you would like your FTP details stored.";
$l['pluginuploader_ftp_storage_location_cookie'] = 'Browser Cookie';
$l['pluginuploader_ftp_storage_location_database'] = 'Database';
$l['pluginuploader_ftp_cookie_expiry'] = "Cookie Expiry";
$l['pluginuploader_ftp_cookie_expiry_desc'] = "How long would you like the cookie to be stored for? The sooner it expires, the more secure it is, but you will have to re-enter the FTP details more often if the cookie expires quickly.";
$l['pluginuploader_ftp_cookie_expiry_missing'] = "Please select a cookie expiry time.";
$l['pluginuploader_ftp_cookie_expiry_close'] = 'When I close my browser';
$l['pluginuploader_ftp_cookie_expiry_day'] = '1 Day';
$l['pluginuploader_ftp_cookie_expiry_week'] = '1 Week';
$l['pluginuploader_ftp_cookie_expiry_month'] = '1 Month';
$l['pluginuploader_ftp_cookie_expiry_forever'] = 'Forever';
$l['pluginuploader_ftp_message_failed_login'] = "An FTP connection could not be made with the specified login details. Please make sure your FTP details are correct.";
$l['pluginuploader_ftp_message_failed_host'] = "An FTP connection could not be made to the specified host. Please make sure your FTP details are correct.";
$l['pluginuploader_uninstall_message_title'] = "Uninstall the Plugin Uploader";
$l['pluginuploader_uninstall_warning'] = "To be able to uninstall the Plugin Uploader, you must enter the password that you use to upload plugins. This is to prevent people uninstalling and reinstalling the plugin to reset the password.";
$l['pluginuploader_uninstall_password_incorrect'] = "The plugin could not be uninstalled as you entered an incorrect password. Please try again.";
$l['pluginuploader_use_ftp'] = 'Use FTP to connect?';
$l['pluginuploader_ftp_details_added'] = "A successful FTP connection was made, and the details have been saved.";
$l['pluginuploader_ftp_test_connection_save'] = "Test FTP Connection and Save";
$l['pluginuploader_ftp_test_connection_fail'] = "An FTP connection could not be made with the specified FTP details. Please check they are correct and try again.";
$l['pluginuploader_ftp_details_cleared'] = "Your FTP details have been cleared successfully.";
$l['pluginuploader_stats'] = 'Send anonymous usage stats';
$l['pluginuploader_stats_desc'] = 'This will log anonymous usage and server information to help debug and improve the Plugin Uploader.';
$l['pluginuploader_stats_more'] = 'More information';
$l['pluginuploader_stats_less'] = 'Less information';
$l['pluginuploader_stats_more_info'] = 'The following information is sent to my server for debugging purposes:<ul><li>MD5 hash of your forum URL</li><li>MyBB version</li><li>PHP version</li><li>If safe_mode is enabled</li><li>If open_basedir is enabled</li><li>Plugin Uploader version</li><li>If PHP is able to move files</li><li>Whether FTP is being used</li><li>FTP details storage location (cookie or database)</li><li>Codename of plugin installed</li><li>Import Source (Upload, URL, MyBB Mods Site)</li><li>Whether plugins can be downloaded from the MyBB Mods Site</ul>This information is sent whenever a plugin is successfully imported. None of the information can be used to track where the information came from; the forum URL is sent so I\'m able to analyse information that came from the same site, however the actual URL cannot be retrieved (an MD5 hash can\'t be decrypted). This information is used to see what setups people use the Plugin Uploader on, which features are being used, and what could be improved.';
$l['pluginuploader_mods_site_title'] = 'MyBB Mods Site Integration';
$l['pluginuploader_mods_site_how_it_works'] = '<h2>How does it work?</h2>When you try and import a plugin from the Mods Site, it will first fetch the licence for you to accept, like when you use the Mods Site normally. This will then submit the form that is usually submitted when you accept the licence and the plugin will be imported as if you uploaded the .zip via the Plugin Uploader. The password for the Plugin Uploader isn\'t required here as the files are coming from a trusted source, rather than allowing arbitrary files to be uploaded.';
$l['pluginuploader_mods_site_why_it_wont_work'] = '<h2>It doesn\'t work/isn\'t available for me!</h2>There are various server configuration options that may prevent plugins to be imported from the Mods Site. It isn\'t possible to download the plugin zip directly; the name of the zip is made up of the ID of the plugin, the UNIX timestamp of when it was uploaded and the name of the original zip, and none of this information is available other than from the name of the downloaded zip, and we haven\'t got that yet! Normally when you agree to the licence on the Mods Site, it submits a form that then serves up the zip and offers it for download in your browser. It is possible to replicate this with PHP, and save the returned zip to the filesystem, but it has certain server requirements. If safe_mode or open_basedir is enabled on your server, the standard method (cURL) will not work in the way required for importing from the Mods site, due to the way it works. There is a secondary method that can be used instead, however this is only available if you are using PHP 5.3.4 or higher. The below table shows which setups will work and which won\'t.';
$l['pluginuploader_mods_site_server_table_php_534_lower'] = 'Lower than PHP 5.3.4';
$l['pluginuploader_mods_site_server_table_php_534_higher'] = 'PHP 5.3.4 or higher';
$l['pluginuploader_mods_site_server_table_ini_on'] = 'safe_mode <strong>and/or</strong> open_basedir <strong>enabled</strong>';
$l['pluginuploader_mods_site_server_table_ini_off'] = 'safe_mode <strong>and</strong> open_basedir <strong>disabled</strong>';
$l['pluginuploader_mods_site_server_table_will_work'] = 'Will work';
$l['pluginuploader_mods_site_server_table_wont_work'] = 'Won\'t work';
$l['pluginuploader_mods_site_server_info'] = '<strong>On your server:</strong>';
$l['pluginuploader_mods_site_server_info_safe_mode'] = 'safe_mode:';
$l['pluginuploader_mods_site_server_info_open_basedir'] = 'open_basedir:';
$l['pluginuploader_mods_site_server_info_enabled'] = 'Enabled';
$l['pluginuploader_mods_site_server_info_disabled'] = 'Disabled';
$l['pluginuploader_mods_site_server_info_php_version'] = 'PHP Version:';
$l['pluginuploader_mods_site_server_info_will_it_work'] = 'Will it work?';
$l['pluginuploader_mods_site_server_info_will_it_work_yes'] = '<span style="color: green; font-weight: bold;">Yes</span>';
$l['pluginuploader_mods_site_server_info_will_it_work_no'] = '<span style="color: red; font-weight: bold;">No</span>';
$l['pluginuploader_mods_site_server_info_what_next'] = '<strong>What next?</strong>';
$l['pluginuploader_mods_site_server_info_what_next_disable_safe_mode'] = 'Disable safe_mode';
$l['pluginuploader_mods_site_server_info_what_next_disable_open_basedir'] = 'Disable open_basedir';
$l['pluginuploader_mods_site_server_info_what_next_or'] = '<strong>or</strong>';
$l['pluginuploader_mods_site_server_info_what_next_upgrade_php'] = 'Upgrade to PHP 5.3.4 or higher';
$l['pluginuploader_mods_site_server_info_what_next_contact_host'] = 'Contact your host to see if either of these options are possible.';
?>