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

$plugins->add_hook("postbit", "karmastars_postbit");
$plugins->add_hook("member_profile_end", "karmastars_profile");
$plugins->add_hook("misc_start", "karmastars_list");
$plugins->add_hook("global_start", "karmastars_footer");
$plugins->add_hook("fetch_wol_activity_end", "karmastars_friendly_wol");
$plugins->add_hook("build_friendly_wol_location_end", "karmastars_build_wol");
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
	
	karmastars_uninstall();
	
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
		$karmastars = array(
			array(
				'image' => 'images/karmastars/1_small_silver.gif',
				'posts' => '20',
				'name' => 'One Small Silver Star'
			),
			array(
				'image' => 'images/karmastars/2_small_silver.gif',
				'posts' => '50',
				'name' => 'Two Small Silver Stars'
			),
			array(
				'image' => 'images/karmastars/3_small_silver.gif',
				'posts' => '125',
				'name' => 'Three Small Silver Stars'
			),
			array(
				'image' => 'images/karmastars/4_small_silver.gif',
				'posts' => '250',
				'name' => 'Four Small Silver Stars'
			),
			array(
				'image' => 'images/karmastars/1_small_gold.gif',
				'posts' => '550',
				'name' => 'One Small Gold Star'
			),
			array(
				'image' => 'images/karmastars/2_small_gold.gif',
				'posts' => '1000',
				'name' => 'Two Small Gold Stars'
			),
			array(
				'image' => 'images/karmastars/3_small_gold.gif',
				'posts' => '1500',
				'name' => 'Three Small Gold Stars'
			),
			array(
				'image' => 'images/karmastars/4_small_gold.gif',
				'posts' => '2200',
				'name' => 'Four Small Gold Stars'
			),
			array(
				'image' => 'images/karmastars/1_med_silver.gif',
				'posts' => '3000',
				'name' => 'One Medium Silver Star'
			),
			array(
				'image' => 'images/karmastars/2_med_silver.gif',
				'posts' => '4000',
				'name' => 'Two Medium Silver Stars'
			),
			array(
				'image' => 'images/karmastars/1_med_gold.gif',
				'posts' => '5500',
				'name' => 'One Medium Gold Star'
			),
			array(
				'image' => 'images/karmastars/2_med_gold.gif',
				'posts' => '7500',
				'name' => 'Two Medium Gold Stars'
			),
			array(
				'image' => 'images/karmastars/1_large_silver.gif',
				'posts' => '10000',
				'name' => 'One Large Silver Star'
			),
			array(
				'image' => 'images/karmastars/1_large_gold.gif',
				'posts' => '12500',
				'name' => 'One Large Gold Star'
			),
			array(
				'image' => 'images/karmastars/1_large_silver_sparkling.gif',
				'posts' => '15000',
				'name' => 'One Large Silver Sparkling Star'
			),
			array(
				'image' => 'images/karmastars/1_large_gold_sparkling.gif',
				'posts' => '17500',
				'name' => 'One Large Gold Sparkling Star'
			),
			array(
				'image' => 'images/karmastars/1_large_platinum_spinning.gif',
				'posts' => '20000',
				'name' => 'One Large Platinum Spinning Star'
			),
			array(
				'image' => 'images/karmastars/1_large_flashing.gif',
				'posts' => '40000',
				'name' => 'One Large Flashing Star'
			)
		);
		foreach($karmastars as $karmastar)
		{
			$insert = array(
				'karmastar_image' => $db->escape_string($karmastar['image']),
				'karmastar_posts' => $db->escape_string($karmastar['posts']),
				'karmastar_name' => $db->escape_string($karmastar['name'])
			);
			$db->insert_query('karmastars', $insert);
		}
		karmastars_cache();
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
	
	$db->delete_query('datacache', 'title = \'karmastars\'');
}

function karmastars_activate()
{
	global $db;
	
	karmastars_deactivate();
	
	require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';
	
	find_replace_templatesets("postbit", "#".preg_quote('{$post[\'onlinestatus\']}')."#i", '{$post[\'karmastar\']}{$post[\'onlinestatus\']}');
	find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'onlinestatus\']}')."#i", '{$post[\'karmastar\']}{$post[\'onlinestatus\']}');
	find_replace_templatesets("member_profile", "#".preg_quote('<span class="largetext"><strong>{$formattedname}</strong></span><br />')."#i", '<span class="largetext"><strong>{$formattedname}</strong></span>{$memprofile[\'karmastar\']}<br />');
	find_replace_templatesets("footer", "#".preg_quote('{$lang->bottomlinks_syndication}</a></span>')."#i", '{$lang->bottomlinks_syndication}</a> | <a href="{$mybb->settings[\'bburl\']}/misc.php?action=karmastars">{$lang->karmastars}</a></span>');
	
	$template_group = array(
		"prefix" => "karmastars",
		"title" => "<lang:karmastars>"
	);
	$db->insert_query("templategroups", $template_group);
	
	$templates = array();
	$templates[] = array(
		"title" => "karmastars_postbit",
		"template" => "<a href=\"{\$mybb->settings['bburl']}/misc.php?action=karmastars\" target=\"_blank\"><img src=\"{\$mybb->settings['bburl']}/{\$karmastar['karmastar_image']}\" alt=\"{\$karmastar['karmastar_name']}\" title=\"{\$karmastar['karmastar_name']}\" /></a>"
	);
	$templates[] = array(
		"title" => "karmastars_list",
		"template" => "<html>
<head>
<title>{\$lang->karmastars}</title>
{\$headerinclude}
</head>
<body>
{\$header}
<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
	<tr>
		<td class=\"thead\" colspan=\"3\">
			<strong>{\$lang->karmastars}</strong>
		</td>
	</tr>
	<tr>
		<td class=\"tcat\" align=\"center\">
			<strong>{\$lang->karmastars_image}</strong>
		</td>
		<td class=\"tcat\" align=\"center\">
			<strong>{\$lang->karmastars_posts}</strong>
		</td>
		<td class=\"tcat\">
			<strong>{\$lang->karmastars_name}</strong>
		</td>
	</tr>
	{\$karmastars_list}
</table>
{\$footer}
</body>
</html>"
	);
	$templates[] = array(
		"title" => "karmastars_list_row",
		"template" => "<tr{\$selected}>
	<td class=\"{\$trow}\" align=\"center\">
		<img src=\"{\$mybb->settings['bburl']}/{\$karmastar['karmastar_image']}\" alt=\"{\$karmastar['karmastar_name']}\" title=\"{\$karmastar['karmastar_name']}\" />
	</td>
	<td class=\"{\$trow}\" align=\"center\">
		{\$karmastar['karmastar_posts']}
	</td>
	<td class=\"{\$trow}\">
		{\$karmastar['karmastar_name']}
	</td>
</tr>
{\$karmastars_list_row_percentage}"
	);
	$templates[] = array(
		"title" => "karmastars_list_row_percentage",
		"template" => "<tr>
	<td class=\"{\$trow}\" style=\"padding: 0;\" colspan=\"3\" align=\"center\">
		<div style=\"width: 100%; position: relative; text-align: center; padding: 5px 0px;\">
			<div style=\"width: {\$percentage_done}%; height: 100%; position: absolute; left: 0; top: 0; background: #D6ECA6;\"></div>
			<div style=\"position: relative;\">{\$percentage_left}</div>
		</div>
	</td>
</tr>"
	);
	
	foreach($templates as $template)
	{
		$insert = array(
			"title" => $db->escape_string($template['title']),
			"template" => $db->escape_string($template['template']),
			"sid" => "-1",
			"version" => "1600",
			"status" => "",
			"dateline" => TIME_NOW
		);
		
		$db->insert_query("templates", $insert);
	}
}

function karmastars_deactivate()
{
	global $db;
	
	require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';
	
	find_replace_templatesets("postbit", "#".preg_quote('{$post[\'karmastar\']}')."#i", '', 0);
	find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'karmastar\']}')."#i", '', 0);
	find_replace_templatesets("member_profile", "#".preg_quote('{$memprofile[\'karmastar\']}')."#i", '', 0);
	find_replace_templatesets("footer", "#".preg_quote(' | <a href="{$mybb->settings[\'bburl\']}/misc.php?action=karmastars">{$lang->karmastars}</a>')."#i", '', 0);
	
	$db->delete_query("templategroups", "prefix = 'karmastars'");
	$db->delete_query("templates", "title IN ('karmastars_postbit','karmastars_list','karmastars_list_row','karmastars_list_row_percentage')");
}

function karmastars_cache()
{
	global $db, $cache;
	
	$query = $db->simple_select('karmastars', '*', '', array('order_by' => 'karmastar_posts', 'order_dir' => 'ASC'));
	$karmastars = array();
	while($karmastar = $db->fetch_array($query))
	{
		$karmastars[] = $karmastar;
	}
	$cache->update('karmastars', $karmastars);
}

function karmastars_get_karma($posts)
{
	global $mybb, $cache;
	
	$posts = intval(str_replace($mybb->settings['thousandssep'], '', $posts));
	
	$karmastars = $cache->read('karmastars');
	$karmastars = array_reverse($karmastars);
	
	foreach($karmastars as $karmastar)
	{
		if($posts >= $karmastar['karmastar_posts'])
		{
			return $karmastar;
		}
	}
	
	return false;
}

function karmastars_postbit(&$post)
{
	global $mybb, $templates;
	
	$post['karmastar'] = '';
	$karmastar = karmastars_get_karma($post['postnum']);
	if($karmastar)
	{
		eval("\$post['karmastar'] = \"".$templates->get('karmastars_postbit')."\";");
	}
}

function karmastars_profile()
{
	global $mybb, $templates, $memprofile;
	
	$memprofile['karmastar'] = '';
	$karmastar = karmastars_get_karma($memprofile['postnum']);
	if($karmastar)
	{
		eval("\$memprofile['karmastar'] = \"".$templates->get('karmastars_postbit')."\";");
	}
}

function karmastars_list()
{
	global $mybb, $cache, $lang, $templates, $theme, $header, $headerinclude, $footer, $karmastars_list;
	
	if($mybb->input['action'] == 'karmastars')
	{
		$lang->load('karmastars');
		
		$karmastars = $cache->read('karmastars');
		$next_karmastar_done = false;
		$do_next_karmastar = false;
		foreach($karmastars as $karmastar)
		{
			$trow = alt_trow();
			$selected = '';
			if($mybb->user['uid'])
			{
				$user_karmastar = karmastars_get_karma($mybb->user['postnum']);
				$next_karmastar = 0;
				$karmastars_list_row_percentage = '';
				if($user_karmastar['karmastar_id'] == $karmastar['karmastar_id'])
				{
					$selected = ' class="trow_selected"';
					$next_karmastar = $user_karmastar['karmastar_id'];
					$do_next_karmastar = true;
				}
				if(!$next_karmastar_done && !$user_karmastar)
				{
					$do_next_karmastar = true;
					$karmastar['karmastar_posts'] = 0;
				}
				if(!$next_karmastar_done && $do_next_karmastar && (($next_karmastar && array_key_exists($next_karmastar, $karmastars)) || !$next_karmastar))
				{
					$posts_difference = $karmastars[$next_karmastar]['karmastar_posts'] - $karmastar['karmastar_posts'];
					$posts_done = $mybb->user['postnum'] - $karmastar['karmastar_posts'];
					$posts_left = $karmastars[$next_karmastar]['karmastar_posts'] - $mybb->user['postnum'];
					$percentage_done = round(($posts_done / $posts_difference) * 100);
					$next_karmastar_done = true;
					$percentage_left = $lang->sprintf($lang->karmastars_next_level, $posts_left);
					eval("\$karmastars_list_row_percentage .= \"".$templates->get('karmastars_list_row_percentage')."\";");
				}
			}
			eval("\$karmastars_list .= \"".$templates->get('karmastars_list_row')."\";");
		}
		
		eval("\$karmastars_page = \"".$templates->get('karmastars_list')."\";");
		output_page($karmastars_page);
	}
}

function karmastars_footer()
{
	global $lang;
	
	$lang->load('karmastars');
}

function karmastars_friendly_wol(&$user_activity)
{
	global $user;
	
	if(my_strpos($user['location'], "misc.php?action=karmastars") !== false)
	{
		$user_activity['activity'] = "misc_karmastars";
	}
}

function karmastars_build_wol(&$plugin_array)
{
	global $lang;
	
	$lang->load("karmastars");
	
	if($plugin_array['user_activity']['activity'] == "misc_karmastars")
	{
		$plugin_array['location_name'] = $lang->karmastars_wol;
	}
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

function karmastars_list_images()
{
	$karmastars = opendir(MYBB_ROOT.'images/karmastars/');
	while(($image = readdir($karmastars)) !== false)
	{
		echo 'images/karmastars/'.$image.'<br />';
	}
}
?>