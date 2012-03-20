<?php
/**
 * Goodbye Spammer 1.0

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

$plugins->add_hook("misc_start", "goodbyespammer");
$plugins->add_hook("member_profile_end", "goodbyespammer_profile");

function goodbyespammer_info()
{
	return array(
		"name" => "Goodbye Spammer",
		"description" => "Makes it easy to delete all traces of a spammer from your forum.",
		"website" => "http://mattrogowski.co.uk",
		"author" => "MattRogowski",
		"authorsite" => "http://mattrogowski.co.uk",
		"version" => "1.0",
		"compatibility" => "16*",
		"guid" => "9ec5cdfaf770be01b3364fac9916e573"
	);
}

function goodbyespammer_activate()
{
	global $db;
	
	require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
	
	goodbyespammer_deactivate();
	
	$settings_group = array(
		"name" => "goodbyespammer",
		"title" => "Goodbye Spammer Settings",
		"description" => "Settings for the goodbye spammer plugin.",
		"disporder" => "28",
		"isdefault" => 0
	);
	$db->insert_query("settinggroups", $settings_group);
	$gid = $db->insert_id();
	
	$settings = array();
	$settings[] = array(
		"name" => "goodbyespammergroups",
		"title" => "Allowed Usergroups",
		"description" => "Enter the ID of the usergroups who are allowed to use Goodbye Spammer.",
		"optionscode" => "text",
		"value" => "3,4,6"
	);
	$settings[] = array(
		"name" => "goodbyespammerpostlimit",
		"title" => "Post Limit",
		"description" => "This setting stops this tool being used on users who have more than a certain amount of posts, to prevent it being used on active members. Setting the value to 0 will disable the post check, however this is not recommended.",
		"optionscode" => "text",
		"value" => "10"
	);
	$settings[] = array(
		"name" => "goodbyespammerbandelete",
		"title" => "Ban or Delete Spammers",
		"description" => "Do you want to ban or delete spammers with this tool?? It will still be optional on the Goodbye Spammer page.",
		"optionscode" => "radio
ban=Ban (Permanent)
delete=Delete",
		"value" => "ban"
	);
	$settings[] = array(
		"name" => "goodbyespammerbangroup",
		"title" => "Ban Usergroup",
		"description" => "Enter the ID of the usergroup (not the name) to put users into when they get banned. Defaults to 7, the default Banned usergroup. The above setting must be set to \'Ban\' for this to take effect.",
		"optionscode" => "text",
		"value" => "7"
	);
	$settings[] = array(
		"name" => "goodbyespammerapikey",
		"title" => "Stop Forum Spam API Key",
		"description" => "In order to be able to submit information on a spammer to the Stop Forum Spam database, you need an API key. You can get one of these <a href=\"http://stopforumspam.com/signup\" target=\"_blank\">here</a>. When you have your key, paste it into the box below.",
		"optionscode" => "text",
		"value" => ""
	);
	$i = 1;
	foreach($settings as $setting)
	{
		$insert = array(
			"name" => $setting['name'],
			"title" => $setting['title'],
			"description" => $setting['description'],
			"optionscode" => $setting['optionscode'],
			"value" => $setting['value'],
			"disporder" => $i,
			"gid" => intval($gid),
		);
		$db->insert_query("settings", $insert);
		$i++;
	}
	
	rebuild_settings();
	
	$templates = array();
	$templates[] = array(
		"title" => "goodbyespammer",
		"template" => "<html>
<head>
<title>{\$lang->goodbyespammer}</title>
{\$headerinclude}
</head>
<body>
{\$header}
<form method=\"post\" action=\"misc.php\">
<input type=\"hidden\" name=\"action\" value=\"do_goodbyespammer\" />
<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
<tr>
<td class=\"thead\" colspan=\"2\"><strong>{\$lang->goodbyespammer_actionstotake}</strong></td>
</tr>
<tr>
<td class=\"tcat\" colspan=\"2\">{\$lang->goodbyespammer_desc}</td>
</tr>
{\$options}
<tr>
<td class=\"tfoot\" colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"{\$lang->goodbyespammer_submit}\" /></td>
</tr>
</table>
<input type=\"hidden\" name=\"uid\" value=\"{\$mybb->input['uid']}\" />
<input type=\"hidden\" name=\"my_post_key\" value=\"{\$mybb->post_code}\" />
</form>
{\$footer}
</body>
</html>"
	);
	$templates[] = array(
		"title" => "goodbyespammer_option_checkbox",
		"template" => "<tr>
	<td class=\"{\$altbg}\" width=\"99%\"><label for=\"actions_{\$action}\"><strong>{\$title}</strong></label><br />{\$description}</td>
	<td class=\"{\$altbg}\" width=\"1%\" align=\"center\"><input type=\"checkbox\" name=\"actions[{\$action}]\" id=\"actions_{\$action}\" value=\"1\"{\$checked}{\$disabled} /></td>
</tr>"
	);
	$templates[] = array(
		"title" => "goodbyespammer_option_textbox",
		"template" => "<tr>
		<td class=\"{\$altbg}\" width=\"100%\" colspan=\"2\"><label for=\"actions_{\$action}\"><strong>{\$title}</strong></label><br />{\$description}<br /><input type=\"text\" name=\"actions[{\$action}]\" id=\"actions_{\$action}\" value=\"{\$text}\" /></td>
</tr>"
	);
	$templates[] = array(
		"title" => "goodbyespammer_profile_link",
		"template" => "<tr>
<td class=\"trow1\">
<a href=\"{\$mybb->settings['bburl']}/misc.php?action=goodbyespammer&amp;uid={\$memprofile['uid']}\">{\$lang->goodbyespammer_profile}</a>
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
			"dateline" => TIME_NOW
		);
		$db->insert_query("templates", $insert);
	}
	
	find_replace_templatesets("member_profile_modoptions", "#".preg_quote('</table>')."#i", '{goodbyespammer}</table>');
}

function goodbyespammer_deactivate()
{
	global $db;
	
	require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
	
	$db->delete_query("settinggroups", "name = 'goodbyespammer'");
	
	$settings = array(
		"goodbyespammergroups",
		"goodbyespammerpostlimit",
		"goodbyespammerbandelete",
		"goodbyespammerbangroup",
		"goodbyespammerapikey"
	);
	$settings = "'" . implode("','", $settings) . "'";
	$db->delete_query("settings", "name IN ({$settings})");
	
	rebuild_settings();
	
	$templates = array(
		"goodbyespammer",
		"goodbyespammer_option_checkbox",
		"goodbyespammer_option_textbox",
		"goodbyespammer_profile_link"
	);
	$templates = "'" . implode("','", $templates) . "'";
	$db->delete_query("templates", "title IN ({$templates})");
	
	find_replace_templatesets("member_profile_modoptions", "#".preg_quote('{goodbyespammer}')."#i", '', 0);
}

function goodbyespammer()
{
	global $mybb, $db, $cache, $lang, $theme, $templates, $header, $headerinclude, $footer;
	
	if($mybb->input['action'] == "do_goodbyespammer" || $mybb->input['action'] == "goodbyespammer")
	{
		$lang->load("goodbyespammer");
		$lang->load("member");
		
		$groups = explode(",", $mybb->settings['goodbyespammergroups']);
		if(!in_array($mybb->user['usergroup'], $groups))
		{
			error_no_permission();
		}
		
		$uid = intval($mybb->input['uid']);
		$user = get_user($uid);
		if(!$user['uid'])
		{
			error($lang->goodbyespammer_invalid_user);
		}
		// this is to stop this tool being used on regular members
		if($user['postnum'] > $mybb->settings['goodbyespammerpostlimit'])
		{
			error($lang->sprintf($lang->goodbyespammer_posts_too_high, $mybb->settings['goodbyespammerpostlimit']));
		}
	}
	
	if($mybb->input['action'] == "do_goodbyespammer")
	{
		verify_post_check($mybb->input['my_post_key']);
		
		$user_deleted = false;
		
		require_once MYBB_ROOT . "inc/class_moderation.php";
		$moderation = new Moderation;
		
		// loop through what was submitted
		foreach($mybb->input['actions'] as $action => $value)
		{
			switch($action)
			{
				case "deletethreads":
					$query = $db->simple_select("threads", "tid", "uid = '{$uid}'");
					while($tid = $db->fetch_field($query, "tid"))
					{
						$moderation->delete_thread($tid);
					}
					break;
				case "deleteposts":
					$query = $db->simple_select("posts", "pid", "uid = '{$uid}'");
					while($pid = $db->fetch_field($query, "pid"))
					{
						$moderation->delete_post($pid);
					}
					break;
				case "removesig":
					$update = array(
						"signature" => ""
					);
					$db->update_query("users", $update, "uid = '{$uid}'");
					break;
				case "removeavatar":
					$update = array(
						"avatar" => ""
					);
					$db->update_query("users", $update, "uid = '{$uid}'");
					break;
				case "clearprofile":
					$db->delete_query("userfields", "ufid = '{$uid}'");
					$update = array(
						"website" => "",
						"birthday" => "",
						"icq" => "",
						"aim" => "",
						"yahoo" => "",
						"msn" => ""
					);
					$db->update_query("users", $update, "uid = '{$uid}'");
					break;
				case "deletepms":
					$query = $db->simple_select("privatemessages", "pmid, uid, toid", "uid = '{$uid}' OR fromid = '{$uid}'");
					$pms = array();
					$users = array();
					while($pm = $db->fetch_array($query))
					{
						$pms[] = $pm['pmid'];
						$users[$pm['uid']] = $pm['uid'];
						$users[$pm['toid']] = $pm['toid'];
					}
					$pms = implode(",", array_map("intval", $pms));
					$db->delete_query("privatemessages", "pmid IN (" . $db->escape_string($pms) . ")");
					require_once MYBB_ROOT . "inc/functions_user.php";
					foreach($users as $user_id)
					{
						update_pm_count($user_id);
					}
					break;
				case "deletereps":
					$query = $db->simple_select("reputation", "rid, uid", "uid = '{$uid}' OR adduid = '{$uid}'");
					$reps = array();
					$users = array();
					while($rep = $db->fetch_array($query))
					{
						$reps[] = $rep['rid'];
						$users[] = $rep['uid'];
					}
					$reps = implode(",", array_map("intval", $reps));
					$db->delete_query("reputation", "rid IN (" . $db->escape_string($reps) . ")");
					foreach($users as $user_id)
					{
						$query = $db->simple_select("reputation", "SUM(reputation) AS reputation_count", "uid = '" . intval($user_id) . "'");
						$reputation_count = $db->fetch_field($query, "reputation_count");
						$update = array(
							"reputation" => intval($reputation_count)
						);
						$db->update_query("users", $update, "uid = '" . intval($user_id) . "'");
					}
					break;
				case "deletereportedposts":
					$db->delete_query("reportedposts", "uid = '{$uid}'");
					break;
				case "deleteevents":
					$db->delete_query("events", "uid = '{$uid}'");
					break;
				case "bandelete":
					if($mybb->settings['goodbyespammerbandelete'] == "ban")
					{
						$query = $db->simple_select("banned", "uid", "uid = '{$uid}'");
						if($db->num_rows($query) > 0)
						{
							$update = array(
								"reason" => $db->escape_string($mybb->input['actions']['banreason'])
							);
							$db->update_query('banned', $update, "uid = '{$uid}'");
						}
						else
						{
							$insert = array(
								"uid" => $uid,
								"gid" => intval($mybb->settings['goodbyespammerbangroup']),
								"oldgroup" => 2,
								"oldadditionalgroups" => "",
								"olddisplaygroup" => 0,
								"admin" => intval($mybb->user['uid']),
								"dateline" => TIME_NOW,
								"bantime" => "---",
								"lifted" => 0,
								"reason" => $db->escape_string($mybb->input['actions']['banreason'])
							);
							$db->insert_query('banned', $insert);
						}
						
						foreach(array($user['regip'], $user['lastip']) as $ip)
						{
							$query = $db->simple_select("banfilters", "*", "type = '1' AND filter = '".$db->escape_string($ip)."'");
							if($db->num_rows($query) == 0)
							{
								$insert = array(
									"filter" => $db->escape_string($ip),
									"type" => 1,
									"dateline" => TIME_NOW
								);
								$db->insert_query("banfilters", $insert);
							}
						}
						
						$update = array(
							"usergroup" => intval($mybb->settings['goodbyespammerbangroup']),
							"additionalgroups" => "",
							"displaygroup" => 0
						);
						$db->update_query("users", $update, "uid = '{$uid}'");
						
						$cache->update_banned();
						$cache->update_bannedips();
					}
					elseif($mybb->settings['goodbyespammerbandelete'] == "delete")
					{
						$db->delete_query("forumsubscriptions", "uid = '{$uid}'");
						$db->delete_query("threadsubscriptions", "uid = '{$uid}'");
						$db->delete_query("sessions", "uid = '{$uid}'");
						$db->delete_query("banned", "uid = '{$uid}'");
						$db->delete_query("threadratings", "uid = '{$uid}'");
						$db->delete_query("users", "uid = '{$uid}'");
						$db->delete_query("joinrequests", "uid = '{$uid}'");
						$db->delete_query("warnings", "uid = '{$uid}'");
						$db->delete_query("awaitingactivation", "uid='{$uid}'");
						
						update_stats(array('numusers' => '-1'));
						
						if($user['avatartype'] == "upload")
						{
							// Removes the ./ at the beginning the timestamp on the end...
							@unlink("../".substr($user['avatar'], 2, -20));
						}
						
						$user_deleted = true;
					}
					break;
				case "stopforumspam":
					$sfs = @fetch_remote_file("http://stopforumspam.com/add.php?username=" . urlencode($user['username']) . "&ip_addr=" . urlencode($user['lastip']) . "&email=" . urlencode($user['email']) . "&api_key=" . urlencode($mybb->settings['goodbyespammerapikey']));
					break;
			}
		}
		
		$cache->update_reportedposts();
		
		log_moderator_action(array(), $lang->sprintf($lang->goodbyespammer_modlog, htmlspecialchars_uni($user['username'])));
		
		if($user_deleted)
		{
			redirect($mybb->settings['bburl'], $lang->goodbyespammer_success);
		}
		else
		{
			redirect(get_profile_link($uid), $lang->goodbyespammer_success);
		}
	}
	elseif($mybb->input['action'] == "goodbyespammer")
	{
		$options = "";
		$actions = array(
			"deletethreads",
			"deleteposts",
			"removesig",
			"removeavatar",
			"clearprofile",
			"deletepms",
			"deletereps",
			"deletereportedposts",
			"deleteevents",
			"bandelete",
			"stopforumspam"
		);
		foreach($actions as $action)
		{
			$checked = false;
			$disabled = false;
			$title_var = "goodbyespammer_" . $action;
			$description_var = $title_var . "_desc";
			$title = $lang->$title_var;
			$description = $lang->$description_var;
			
			switch($action)
			{
				case "deletethreads":
					$query = $db->simple_select("threads", "COUNT(*) AS threads", "uid = '{$uid}'");
					$threads = $db->fetch_field($query, "threads");
					if($threads > 0)
					{
						$checked = " checked=\"checked\"";
						$title .= " (" . $threads . ")";
					}
					else
					{
						$disabled = " disabled =\"disabled\"";
					}
					$altbg = alt_trow();
					eval("\$options .= \"".$templates->get('goodbyespammer_option_checkbox')."\";");
					break;
				case "deleteposts":
					$query = $db->simple_select("posts", "COUNT(*) AS posts", "uid = '{$uid}'");
					$posts = $db->fetch_field($query, "posts");
					if($threads > 0)
					{
						$posts -= $threads;
					}
					if($posts > 0)
					{
						$checked = " checked=\"checked\"";
						$title .= " (" . $posts . ")";
					}
					else
					{
						$disabled = " disabled =\"disabled\"";
					}
					$altbg = alt_trow();
					eval("\$options .= \"".$templates->get('goodbyespammer_option_checkbox')."\";");
					break;
				case "removesig":
					if(!empty($user['signature']))
					{
						$checked = " checked=\"checked\"";
					}
					else
					{
						$disabled = " disabled =\"disabled\"";
					}
					$altbg = alt_trow();
					eval("\$options .= \"".$templates->get('goodbyespammer_option_checkbox')."\";");
					break;
				case "removeavatar":
					if(!empty($user['avatar']))
					{
						$checked = " checked=\"checked\"";
					}
					else
					{
						$disabled = " disabled =\"disabled\"";
					}
					$altbg = alt_trow();
					eval("\$options .= \"".$templates->get('goodbyespammer_option_checkbox')."\";");
					break;
				case "clearprofile":
					$query = $db->simple_select("profilefields", "fid");
					$profilefields = array();
					while($fid = $db->fetch_field($query, "fid"))
					{
						$profilefields[] = "fid" . intval($fid);
					}
					$profilefields_string = implode(", ", $profilefields);
					if(!empty($profilefields_string))
					{
						$query = $db->simple_select("userfields", $profilefields_string, "ufid = '{$uid}'");
						$userfields = $db->fetch_array($query);
					}
					if($userfields)
					{
						foreach($userfields as $userfield)
						{
							if(!empty($userfield))
							{
								$checked = " checked=\"checked\"";
							}
						}
					}
					if(!$checked)
					{
						if(!empty($user['website']) || !empty($user['birthday']))
						{
							$checked = " checked=\"checked\"";
						}
					}
					if(!$checked)
					{
						$disabled = " disabled =\"disabled\"";
					}
					$altbg = alt_trow();
					eval("\$options .= \"".$templates->get('goodbyespammer_option_checkbox')."\";");
					break;
				case "deletepms":
					$query = $db->simple_select("privatemessages", "COUNT(*) AS pms", "uid = '{$uid}' OR fromid = '{$uid}'");
					$pms = $db->fetch_field($query, "pms");
					if($pms > 0)
					{
						$checked = " checked=\"checked\"";
						$title .= " (" . $pms . ")";
					}
					else
					{
						$disabled = " disabled =\"disabled\"";
					}
					$altbg = alt_trow();
					eval("\$options .= \"".$templates->get('goodbyespammer_option_checkbox')."\";");
					break;
				case "deletereps":
					$query = $db->simple_select("reputation", "COUNT(*) AS reps", "uid = '{$uid}' OR adduid = '{$uid}'");
					$reps = $db->fetch_field($query, "reps");
					if($reps > 0)
					{
						$checked = " checked=\"checked\"";
						$title .= " (" . $reps . ")";
					}
					else
					{
						$disabled = " disabled =\"disabled\"";
					}
					$altbg = alt_trow();
					eval("\$options .= \"".$templates->get('goodbyespammer_option_checkbox')."\";");
					break;
				case "deletereportedposts":
					$query = $db->simple_select("reportedposts", "COUNT(*) AS reportedposts", "uid = '{$uid}'");
					$reportedposts = $db->fetch_field($query, "reportedposts");
					if($reportedposts > 0)
					{
						$checked = " checked=\"checked\"";
						$title .= " (" . $reportedposts . ")";
					}
					else
					{
						$disabled = " disabled =\"disabled\"";
					}
					$altbg = alt_trow();
					eval("\$options .= \"".$templates->get('goodbyespammer_option_checkbox')."\";");
					break;
				case "deleteevents":
					$query = $db->simple_select("events", "COUNT(*) AS events", "uid = '{$uid}'");
					$events = $db->fetch_field($query, "events");
					if($events > 0)
					{
						$checked = " checked=\"checked\"";
						$title .= " (" . $events . ")";
					}
					else
					{
						$disabled = " disabled =\"disabled\"";
					}
					$altbg = alt_trow();
					eval("\$options .= \"".$templates->get('goodbyespammer_option_checkbox')."\";");
					break;
				case "bandelete":
					if($mybb->settings['goodbyespammerbandelete'] == "delete")
					{
						$title = $lang->goodbyespammer_delete;
						$description = $lang->goodbyespammer_delete_desc;
					}
					else
					{
						$title = $lang->goodbyespammer_ban;
						$description = $lang->goodbyespammer_ban_desc;
					}
					$checked = " checked=\"checked\"";
					$altbg = alt_trow();
					eval("\$options .= \"".$templates->get('goodbyespammer_option_checkbox')."\";");
					if($mybb->settings['goodbyespammerbandelete'] == "ban")
					{
						$title = $lang->goodbyespammer_ban_reason;
						$description = $lang->goodbyespammer_ban_reason_desc;
						$text = $lang->goodbyespammer_ban_reason_reason;
						$action = "banreason";
						$altbg = alt_trow();
						eval("\$options .= \"".$templates->get('goodbyespammer_option_textbox')."\";");
					}
					break;
				case "stopforumspam":
					if(!empty($mybb->settings['goodbyespammerapikey']))
					{
						$checked = " checked=\"checked\"";
						$altbg = alt_trow();
						eval("\$options .= \"".$templates->get('goodbyespammer_option_checkbox')."\";");
					}
					break;
			}
		}
		
		add_breadcrumb($lang->sprintf($lang->nav_profile, $user['username']), get_profile_link($uid));
		add_breadcrumb($lang->goodbyespammer);
		$lang->goodbyespammer_actionstotake = $lang->sprintf($lang->goodbyespammer_actionstotake, $user['username']);
		eval("\$goodbyespammer .= \"".$templates->get('goodbyespammer')."\";");
		output_page($goodbyespammer);
	}
}

function goodbyespammer_profile()
{
	global $mybb, $lang, $cache, $templates, $memprofile, $modoptions;
	
	// only show this if the current user has permission to use it and the profile we're on has less than the post limit for using this tool
	$groups = explode(",", $mybb->settings['goodbyespammergroups']);
	$bangroup = $mybb->settings['goodbyespammerbangroup'];
	$usergroups = $cache->read('usergroups');
	
	if(in_array($mybb->user['usergroup'], $groups) && (str_replace($mybb->settings['thousandssep'], '', $memprofile['postnum']) <= $mybb->settings['goodbyespammerpostlimit'] || $mybb->settings['goodbyespammerpostlimit'] == 0) && $memprofile['usergroup'] != $bangroup && $usergroups[$memprofile['usergroup']]['isbannedgroup'] != 1)
	{
		$lang->load("goodbyespammer");
		eval("\$goodbyespammer = \"".$templates->get('goodbyespammer_profile_link')."\";");
		$modoptions = str_replace("{goodbyespammer}", $goodbyespammer, $modoptions);
	}
	else
	{
		$modoptions = str_replace("{goodbyespammer}", "", $modoptions);
	}
}
?>