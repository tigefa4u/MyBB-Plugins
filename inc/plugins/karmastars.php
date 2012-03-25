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
$plugins->add_hook("misc_start", "karmastars_list");
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
	
	$db->delete_query("templategroups", "prefix = 'karmastars'");
	$db->delete_query("templates", "title IN ('karmastars_postbit','karmastars_list','karmastars_list_row')");
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
	global $cache;
	
	$posts = intval($posts);
	
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
	$karmastar = karmastars_get_karma(str_replace(',', '', $post['postnum']));
	if($karmastar)
	{
		eval("\$post['karmastar'] = \"".$templates->get('karmastars_postbit')."\";");
	}
}

function karmastars_list()
{
	global $mybb, $cache, $lang, $templates, $theme, $header, $headerinclude, $footer, $karmastars_list;
	
	if($mybb->input['action'] == 'karmastars')
	{
		$lang->load('karmastars');
		
		$karmastars = $cache->read('karmastars');
		foreach($karmastars as $karmastar)
		{
			$trow = alt_trow();
			$selected = '';
			if($mybb->user['uid'])
			{
				$user_karmastar = karmastars_get_karma($mybb->user['postnum']);
				if($user_karmastar['karmastar_id'] == $karmastar['karmastar_id'])
				{
					$selected = ' class="trow_selected"';
				}
			}
			eval("\$karmastars_list .= \"".$templates->get('karmastars_list_row')."\";");
		}
		
		eval("\$karmastars_page = \"".$templates->get('karmastars_list')."\";");
		output_page($karmastars_page);
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