<?php
/**
 * Social Sites 0.2.2

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

$plugins->add_hook("postbit", "socialsites_postbit");
$plugins->add_hook("postbit_prev", "socialsites_postbit");
$plugins->add_hook("postbit_pm", "socialsites_postbit");
$plugins->add_hook("postbit_announcement", "socialsites_postbit");
$plugins->add_hook("member_profile_end", "socialsites_profile");
$plugins->add_hook("member_register_start", "socialsites_register");
$plugins->add_hook("member_do_register_end", "socialsites_do_register");
$plugins->add_hook('usercp_menu_built', 'socialsites_navoption', -10);
$plugins->add_hook('usercp_start', 'socialsites_usercp');
$plugins->add_hook("fetch_wol_activity_end", "socialsites_friendly_wol");
$plugins->add_hook("build_friendly_wol_location_end", "socialsites_build_wol");
$plugins->add_hook("admin_config_menu", "socialsites_admin_config_menu");
$plugins->add_hook("admin_config_action_handler", "socialsites_admin_config_action_handler");
$plugins->add_hook("admin_config_permissions", "socialsites_admin_config_permissions");

function socialsites_info()
{
	return array(
		"name" => "Social Sites",
		"description" => "Add which social sites you're a member of so you can find eachother on social networking sites.",
		"website" => "http://mattrogowski.co.uk/mybb/plugins/plugin/social-sites",
		"author" => "MattRogowski",
		"authorsite" => "http://mattrogowski.co.uk/mybb/",
		"version" => "0.2.2",
		"compatibility" => "16*",
		"guid" => "971ebb71c48a235e6dba1dedf7f0be25"
	);
}

function socialsites_install()
{
	global $db, $socialsites_uninstall_confirm_override;
	
	// this is so we override the confirmation when trying to uninstall, so we can just run the uninstall code
	$socialsites_uninstall_confirm_override = true;
	socialsites_uninstall();
	
	if(!$db->field_exists("socialsites", "users"))
	{
		$db->add_column("users", "socialsites", "TEXT NOT NULL");
	}
	
	if(!$db->table_exists("socialsites"))
	{
		$db->write_query("
			CREATE TABLE  " . TABLE_PREFIX . "socialsites (
				`ssid` SMALLINT(5) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`nicename` VARCHAR(255) NOT NULL ,
				`name` VARCHAR(255) NOT NULL ,
				`url` VARCHAR(255) NOT NULL,
				`image_small` VARCHAR(255) NOT NULL,
				`image_large` VARCHAR(255) NOT NULL
			) ENGINE = MYISAM ;
		");
	}
	
	$sites = array();
	$sites[] = array(
		"name" => "Twitter",
		"nicename" => "twitter",
		"url" => "http://twitter.com/{username}",
		"image_small" => "twitter_small.png",
		"image_large" => "twitter_large.png"
	);
	$sites[] = array(
		"name" => "Facebook",
		"nicename" => "facebook",
		"url" => "http://facebook.com/{username}",
		"image_small" => "facebook_small.png",
		"image_large" => "facebook_large.png"
	);
	$sites[] = array(
		"name" => "Bebo",
		"nicename" => "bebo",
		"url" => "http://bebo.com/{username}",
		"image_small" => "bebo_small.png",
		"image_large" => "bebo_large.png"
	);
	$sites[] = array(
		"name" => "MySpace",
		"nicename" => "myspace",
		"url" => "http://myspace.com/{username}",
		"image_small" => "myspace_small.png",
		"image_large" => "myspace_large.png"
	);
	$sites[] = array(
		"name" => "LinkedIn",
		"nicename" => "linkedin",
		"url" => "http://www.linkedin.com/profile/view?id={username}",
		"image_small" => "linkedin_small.png",
		"image_large" => "linkedin_large.png"
	);
	$sites[] = array(
		"name" => "Flickr",
		"nicename" => "flickr",
		"url" => "http://www.flickr.com/photos/{username}",
		"image_small" => "flickr_small.png",
		"image_large" => "flickr_large.png"
	);
	$sites[] = array(
		"name" => "YouTube",
		"nicename" => "youtube",
		"url" => "http://www.youtube.com/user/{username}",
		"image_small" => "youtube_small.png",
		"image_large" => "youtube_large.png"
	);
	$sites[] = array(
		"name" => "Last.fm",
		"nicename" => "last.fm",
		"url" => "http://www.last.fm/user/{username}",
		"image_small" => "lastfm_small.png",
		"image_large" => "lastfm_large.png"
	);
	
	foreach($sites as $site)
	{
		$insert = array(
			"name" => $db->escape_string($site['name']),
			"nicename" => $db->escape_string($site['nicename']),
			"url" => $db->escape_string($site['url']),
			"image_small" => $db->escape_string($site['image_small']),
			"image_large" => $db->escape_string($site['image_large'])
		);
		
		$db->insert_query("socialsites", $insert);
	}
	
	socialsites_cache_sites();
	
	change_admin_permission("config", "socialsites", 1);
}

function socialsites_is_installed()
{
	global $db;
	
	return $db->table_exists("socialsites");
}

function socialsites_uninstall()
{
	global $mybb, $db, $socialsites_uninstall_confirm_override;
	
	// this is a check to make sure we want to uninstall
	// if 'No' was chosen on the confirmation screen, redirect back to the plugins page
	if($mybb->input['no'])
	{
		admin_redirect("index.php?module=config-plugins");
	}
	else
	{
		// there's a post request so we submitted the form and selected yes
		// or the confirmation is being overridden by the installation function; this is for when socialsites_uninstall() is called at the start of socialsites_install(), we just want to execute the uninstall code at this point
		if($mybb->request_method == "post" || $socialsites_uninstall_confirm_override === true || $mybb->input['action'] == "delete")
		{
			if($db->field_exists("socialsites", "users"))
			{
				$db->drop_column("users", "socialsites");
			}
			
			if($db->table_exists("socialsites"))
			{
				$db->drop_table("socialsites");
			}
		}
		// need to show the confirmation
		else
		{
			global $lang, $page;
			
			$lang->load("config_socialsites");
			
			$page->output_confirm_action("index.php?module=config-plugins&action=deactivate&uninstall=1&plugin=socialsites&my_post_key={$mybb->post_code}", $lang->socialsites_uninstall_warning);
		}
	}
}

function socialsites_activate()
{
	global $db;
	
	require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
	
	socialsites_deactivate();
	
	$settings_group = array(
		"name" => "socialsites",
		"title" => "Social Sites Settings",
		"description" => "Settings for the social sites plugin.",
		"disporder" => "28",
		"isdefault" => 0
	);
	$db->insert_query("settinggroups", $settings_group);
	$gid = $db->insert_id();
	
	$settings = array();
	$settings[] = array(
		"name" => "socialsites_display",
		"title" => "Social Sites Display Locations",
		"description" => "Where do you want to show what social sites users are a member of??",
		"optionscode" => "select
none=Nowhere (disabled)
postbit=Postbit
profile=Profile
all=Postbit and Profile",
		"value" => "all"
	);
	$settings[] = array(
		"name" => "socialsites_registration",
		"title" => "Social Sites on Registration",
		"description" => "Show the list of social sites on the registration form??",
		"optionscode" => "yesno",
		"value" => "1"
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
			"disporder" => $i,
			"gid" => intval($gid),
		);
		$db->insert_query("settings", $insert);
		$i++;
	}
	
	rebuild_settings();
	
	$templates = array();
	$templates[] = array(
		"title" => "socialsites_site",
		"template" => "<a href=\"{\$url}\" target=\"_blank\"><img src=\"{\$mybb->settings['bburl']}/images/socialicons/{\$image}\" alt=\"{\$alt}\" title=\"{\$title}\" /></a>"
	);
	$templates[] = array(
		"title" => "socialsites_profile",
		"template" => "<br />
<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
<tr>
	<td class=\"thead\"><strong>{\$lang->socialsites}</strong></td>
</tr>
<tr>
	<td class=\"trow1\">{\$sites}</td>
</tr>
</table>"
	);
	$templates[] = array(
		"title" => "socialsites_usercp_sites",
		"template" => "<html>
<head>
<title>{\$mybb->settings['bbname']} - {\$lang->socialsites}</title>
{\$headerinclude}
</head>
<body>
	{\$header}
	<table width=\"100%\" border=\"0\" align=\"center\">
		<tr>
			{\$usercpnav}
			<td align=\"center\" valign=\"top\">
				<form method=\"post\" action=\"usercp.php?action=do_socialsites\">
				<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
					<tr>
						<td class=\"thead\" colspan=\"3\"><strong>{\$lang->socialsites}</strong></td>
					</tr>
					<tr>
						<td class=\"tcat\" align=\"center\" width=\"10%\"><strong>{\$lang->socialsites_icon}</strong></td>
						<td class=\"tcat\" align=\"center\" width=\"20%\"><strong>{\$lang->socialsites_site}</strong></td>
						<td class=\"tcat\" align=\"center\" width=\"70%\"><strong>{\$lang->socialsites_details}</strong></td>
					</tr>
					{\$sites}
				</table>
				<br />
				<input type=\"hidden\" name=\"my_post_key\" value=\"{\$mybb->post_code}\" />
				<input type=\"submit\" value=\"{\$lang->socialsites_submit}\" />
				</form>
			</td>
		</tr>
	</table>
	{\$footer}
</body>
</html>"
	);
	$templates[] = array(
		"title" => "socialsites_register_sites",
		"template" => "<br />
<fieldset class=\"trow2\">
<legend><strong>{\$lang->socialsites}</strong></legend>
<a href=\"#\" id=\"collapse_expand_1\" onclick=\"socialsitesToggle();\"></a>
<div id=\"socialsites\" style=\"display: '';\">
	<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
		<tr>
			<td class=\"tcat\" align=\"center\" width=\"10%\"><strong>{\$lang->socialsites_icon}</strong></td>
			<td class=\"tcat\" align=\"center\" width=\"20%\"><strong>{\$lang->socialsites_site}</strong></td>
			<td class=\"tcat\" align=\"center\" width=\"70%\"><strong>{\$lang->socialsites_details}</strong></td>
		</tr>
		{\$sites}
	</table>
	<a href=\"#\" id=\"collapse_expand_2\" onclick=\"socialsitesToggle();\"></a>
</div>
</fieldset>
<script type=\"text/javascript\">
<!--
var socialsites = document.getElementById('socialsites');
var collapse_expand_1 = document.getElementById('collapse_expand_1');
var collapse_expand_2 = document.getElementById('collapse_expand_2');

var expand = '{\$lang->socialsites_expand}';
var collapse = '{\$lang->socialsites_collapse}';

socialsites.style.display = 'none';
collapse_expand_1.innerHTML = expand;
collapse_expand_2.innerHTML = collapse;

function socialsitesToggle()
{
	if(socialsites.style.display == 'none')
	{
		socialsites.style.display = '';
		collapse_expand_1.innerHTML = collapse;
	}
	else
	{
		socialsites.style.display = 'none';
		collapse_expand_1.innerHTML = expand;
	}
}
// -->
</script>"
	);
	$templates[] = array(
		"title" => "socialsites_sites_site",
		"template" => "<tr>
	<td class=\"{\$bgcolor}\" align=\"center\"><img src=\"{\$mybb->settings['bburl']}/images/socialicons/{\$site['image_large']}\" alt=\"{\$site['name']}\" title=\"{\$site['name']}\" /></td>
	<td class=\"{\$bgcolor}\" align=\"center\">{\$site['name']}</td>
	<td class=\"{\$bgcolor}\" align=\"center\">{\$url}</td>
</tr>"
	);
	$templates[] = array(
		"title" => "socialsites_usercp_nav_option",
		"template" => "<tr><td class=\"trow1 smalltext\"><a href=\"{\$mybb->settings['bburl']}/usercp.php?action=socialsites\" class=\"usercp_nav_item usercp_nav_socialsites\">{\$lang->socialsites}</a></td></tr>"
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
	
	socialsites_cache_sites();
	
	find_replace_templatesets("postbit_author_user", "#".preg_quote('{$post[\'warninglevel\']}')."#i", '{$post[\'warninglevel\']}{socialsites}');
	find_replace_templatesets("member_profile", "#".preg_quote('{$profilefields}')."#i", '{$profilefields}{$socialsites}');
	find_replace_templatesets("usercp_nav_misc", "#".preg_quote('{$lang->ucp_nav_forum_subscriptions}</a></td></tr>')."#i", '{$lang->ucp_nav_forum_subscriptions}</a></td></tr>{socialsites_nav_option}');
	find_replace_templatesets("member_register", "#".preg_quote('{$requiredfields}')."#i", '{$requiredfields}{$socialsites}');
}

function socialsites_deactivate()
{
	global $db;
	
	require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
	
	$db->delete_query("settinggroups", "name = 'socialsites'");
	
	$settings = array(
		"socialsites_display",
		"socialsites_registration"
	);
	$settings = "'" . implode("','", $settings) . "'";
	$db->delete_query("settings", "name IN ({$settings})");
	
	rebuild_settings();
	
	$templates = array(
		"socialsites_site",
		"socialsites_profile",
		"socialsites_usercp_sites",
		"socialsites_register_sites",
		"socialsites_sites_site",
		"socialsites_usercp_nav_option"
	);
	$templates = "'" . implode("','", $templates) . "'";
	$db->delete_query("templates", "title IN ({$templates})");
	
	find_replace_templatesets("postbit_author_user", "#".preg_quote('{socialsites}')."#i", '', 0);
	find_replace_templatesets("member_profile", "#".preg_quote('{$socialsites}')."#i", '', 0);
	find_replace_templatesets("usercp_nav_misc", "#".preg_quote('{socialsites_nav_option}')."#i", '', 0);
	find_replace_templatesets("member_register", "#".preg_quote('{$socialsites}')."#i", '', 0);
}

function socialsites_postbit(&$post)
{
	global $mybb;
	
	if($mybb->settings['socialsites_display'] == "none")
	{
		return;
	}
	
	if($post['uid'] == 0)
	{
		return;
	}
	
	if($mybb->settings['socialsites_display'] == "postbit" || $mybb->settings['socialsites_display'] == "all")
	{
		$info = unserialize($post['socialsites']);
		
		$sites = socialsites_build_icon_list($info, $post['username'], "small");
		
		if(!empty($sites))
		{
			$sites = "<br />" . $sites;
		}
		
		$post['user_details'] = str_replace("{socialsites}", $sites, $post['user_details']);
	}
}

function socialsites_profile()
{
	global $mybb;
	
	if($mybb->settings['socialsites_display'] == "none")
	{
		return;
	}
	
	global $lang, $theme, $templates, $memprofile, $socialsites;
	
	$lang->load("socialsites");
	
	if($mybb->settings['socialsites_display'] == "profile" || $mybb->settings['socialsites_display'] == "all")
	{
		$info = unserialize($memprofile['socialsites']);
		
		if(!empty($info))
		{
			$sites = socialsites_build_icon_list($info, $memprofile['username'], "large");
			
			eval("\$socialsites .= \"".$templates->get('socialsites_profile')."\";");
		}
	}
}

function socialsites_register()
{
	global $mybb;
	
	if($mybb->settings['socialsites_display'] == "none" || $mybb->settings['socialsites_registration'] != 1)
	{
		return;
	}
	
	global $db, $cache, $lang, $theme, $templates, $socialsites;
	
	$lang->load("socialsites");
	
	$query = $db->simple_select("socialsites", "*", "", array("order_by" => "nicename", "order_dir" => "ASC"));
	$social_sites = array();
	while($socialsite = $db->fetch_array($query))
	{
		$social_sites[$socialsite['nicename']] = $socialsite;
	}
	$sites = "";
	
	foreach($social_sites as $site)
	{
		$bgcolor = alt_trow();
		
		$input = "<input type=\"text\" name=\"socialsites[{$site['nicename']}]\" value=\"{$mybb->input['socialsites'][$site['nicename']]}\" />";
		$url = str_replace("{username}", $input, $site['url']);
		
		eval("\$sites .= \"".$templates->get('socialsites_sites_site')."\";");
	}
	
	eval("\$socialsites = \"".$templates->get('socialsites_register_sites')."\";");
}

function socialsites_do_register()
{
	global $mybb, $user_info;
	
	socialsites_update_user_sites($mybb->input['socialsites'], $user_info['uid']);
}

function socialsites_navoption()
{
	global $mybb;
	
	if($mybb->settings['socialsites_display'] == "none")
	{
		global $usercpnav;
		
		// if the plugin is turned off, we need to replace these with nothing otherwise they'll show up in the menu
		$usercpnav = str_replace("{socialsites_nav_option}", "", $usercpnav);
		
		return;
	}
	
	global $lang, $templates, $usercpnav, $socialsites_nav_option;
	
	$lang->load("socialsites");
	
	$socialsites_nav_option = "";
	eval("\$socialsites_nav_option = \"".$templates->get("socialsites_usercp_nav_option")."\";");
	$usercpnav = str_replace("{socialsites_nav_option}", $socialsites_nav_option, $usercpnav);
}

function socialsites_usercp()
{
	global $mybb;
	
	if($mybb->settings['socialsites_display'] == "none")
	{
		return;
	}
	
	global $db, $cache, $lang, $theme, $templates, $headerinclude, $header, $footer, $usercpnav;
	
	$lang->load("socialsites");
	
	if($mybb->input['action'] == "do_socialsites")
	{
		verify_post_check($mybb->input['my_post_key']);
		
		socialsites_update_user_sites($mybb->input['socialsites'], $mybb->user['uid']);
		
		redirect("usercp.php?action=socialsites", $lang->socialsites_updated);
	}
	elseif($mybb->input['action'] == "socialsites")
	{
		add_breadcrumb($lang->nav_usercp, "usercp.php");
		add_breadcrumb($lang->socialsites, "usercp.php?action=socialsites");
		
		$mybb->user['socialsites'] = unserialize($mybb->user['socialsites']);
		$query = $db->simple_select("socialsites", "*", "", array("order_by" => "nicename", "order_dir" => "ASC"));
		$social_sites = array();
		while($socialsite = $db->fetch_array($query))
		{
			$social_sites[$socialsite['nicename']] = $socialsite;
		}
		$sites = "";
		
		foreach($social_sites as $site)
		{
			$bgcolor = alt_trow();
			
			$input = "<input type=\"text\" name=\"socialsites[{$site['nicename']}]\" value=\"{$mybb->user['socialsites'][$site['nicename']]}\" />";
			$url = str_replace("{username}", $input, $site['url']);
			
			eval("\$sites .= \"".$templates->get('socialsites_sites_site')."\";");
		}
		
		eval("\$socialsites_usercp_sites = \"".$templates->get('socialsites_usercp_sites')."\";");
		output_page($socialsites_usercp_sites);
	}
}

function socialsites_build_icon_list($info, $username, $size)
{
	global $mybb, $cache, $lang, $templates;
	
	$lang->load("socialsites");
	
	$socialsites = $cache->read("socialsites");
	$sites = "";
	$i = 1;
	
	if(!empty($info))
	{
		foreach($info as $site => $id)
		{
			if(!array_key_exists($site, $socialsites))
			{
				continue;
			}
			
			$alt = htmlspecialchars_uni($socialsites[$site]['name']);
			$title = $lang->sprintf($lang->socialsites_user_on, htmlspecialchars_uni($username), htmlspecialchars_uni($socialsites[$site]['name']));
			$image = $socialsites[$site]['image_' . $size];
			$url = str_replace("{username}", urlencode($id), $socialsites[$site]['url']);
			eval("\$sites .= \"".$templates->get('socialsites_site')."\";");
			
			if($i == 6 && $mybb->settings['postlayout'] == "classic" && $size == "small")
			{
				$sites .= "<br />";
				$i = 0;
			}
			$i++;
		}
	}
	
	return $sites;
}

function socialsites_update_user_sites($sites, $uid)
{
	global $db, $cache;
	
	$uid = intval($uid);
	
	$query = $db->simple_select("socialsites", "*", "", array("order_by" => "nicename", "order_dir" => "ASC"));
	$social_sites = array();
	while($socialsite = $db->fetch_array($query))
	{
		$social_sites[$socialsite['nicename']] = $socialsite;
	}
	$socialsites = array();
	foreach($sites as $site => $id)
	{
		if(!array_key_exists($site, $social_sites) || empty($id))
		{
			continue;
		}
		$socialsites[$site] = $id;
	}
	
	ksort($socialsites);
	$socialsites = serialize($socialsites);
	
	$update = array(
		"socialsites" => $db->escape_string($socialsites)
	);
	$db->update_query("users", $update, "uid = '{$uid}'");
}

function socialsites_cache_sites()
{
	global $db, $cache;
	
	$query = $db->simple_select("socialsites", "*", "", array("order_by" => "nicename", "order_dir" => "ASC"));
	$socialsites = array();
	while($socialsite = $db->fetch_array($query))
	{
		$socialsites[$socialsite['nicename']] = $socialsite;
	}
	
	$cache->update("socialsites", $socialsites);
}

function socialsites_admin_config_menu($sub_menu)
{
	global $lang;
	
	$lang->load("config_socialsites");
	
	$sub_menu[] = array("id" => "socialsites", "title" => $lang->socialsites, "link" => "index.php?module=config-socialsites");
	
	return $sub_menu;
}

function socialsites_admin_config_action_handler($actions)
{
	$actions['socialsites'] = array(
		"active" => "socialsites",
		"file" => "socialsites.php"
	);
	
	return $actions;
}

function socialsites_admin_config_permissions($admin_permissions)
{
	global $lang;
	
	$lang->load("config_socialsites");
	
	$admin_permissions['socialsites'] = $lang->can_manage_socialsites;
	
	return $admin_permissions;
}

function socialsites_friendly_wol(&$user_activity)
{
	global $user;
	
	if(my_strpos($user['location'], "usercp.php?action=socialsites") !== false)
	{
		$user_activity['activity'] = "usercp_socialsites";
	}
}

function socialsites_build_wol(&$plugin_array)
{
	global $lang;
	
	$lang->load("socialsites");
	
	if($plugin_array['user_activity']['activity'] == "usercp_socialsites")
	{
		$plugin_array['location_name'] = $lang->socialsites_wol;
	}
}
?>