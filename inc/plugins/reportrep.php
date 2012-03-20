<?php
/**
 * Report Reputation 0.3.1

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

$plugins->add_hook("global_start", "reportrep_notice");
$plugins->add_hook("reputation_start", "reportrep");
$plugins->add_hook("reputation_end", "reportrep_report_link");
$plugins->add_hook("modcp_start", "reportrep_navoption", -10);
$plugins->add_hook("modcp_start", "reportrep_reports");
$plugins->add_hook("fetch_wol_activity_end", "reportrep_friendly_wol");
$plugins->add_hook("build_friendly_wol_location_end", "reportrep_build_wol");

function reportrep_info()
{
	return array(
		"name" => "Report Reputation",
		"description" => "Allows you to report reputation if it is spam/abuse etc.",
		"website" => "http://mattrogowski.co.uk",
		"author" => "MattRogowski",
		"authorsite" => "http://mattrogowski.co.uk",
		"version" => "0.3.1",
		"compatibility" => "16*",
		"guid" => "aeb8f9ee5dcae6293069cc9ba96fc062"
	);
}

function reportrep_install()
{
	global $db, $reportrep_uninstall_confirm_override;
	
	$reportrep_uninstall_confirm_override = true;
	reportrep_uninstall();
	
	if(!$db->table_exists("reportedreps"))
	{
		$db->write_query("
			CREATE TABLE  " . TABLE_PREFIX . "reportedreps (
				`rrid` SMALLINT(5) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`rid` INT(10) NOT NULL ,
				`uid` INT(10) NOT NULL ,
				`ruid` INT(10) NOT NULL ,
				`reason` VARCHAR(255) NOT NULL ,
				`time` INT(11) NOT NULL ,
				`active` INT(1) NOT NULL
			) ENGINE = MYISAM ;
		");
	}
}

function reportrep_is_installed()
{
	global $db;
	
	return $db->table_exists("reportedreps");
}

function reportrep_uninstall()
{
	global $mybb, $db, $reportrep_uninstall_confirm_override;
	
	// this is a check to make sure we want to uninstall
	// if 'No' was chosen on the confirmation screen, redirect back to the plugins page
	if($mybb->input['no'])
	{
		admin_redirect("index.php?module=config-plugins");
	}
	else
	{
		// there's a post request so we submitted the form and selected yes
		// or the confirmation is being overridden by the installation function; this is for when reportrep_uninstall() is called at the start of reportrep_install(), we just want to execute the uninstall code at this point
		if($mybb->request_method == "post" || $reportrep_uninstall_confirm_override === true || $mybb->input['action'] == "delete")
		{
			if($db->table_exists("reportedreps"))
			{
				$db->drop_table("reportedreps");
			}
			
			$db->delete_query("datacache", "title = 'reportedreps'");
		}
		// need to show the confirmation
		else
		{
			global $lang, $page;
			
			$lang->load("reportrep");
			
			$page->output_confirm_action("index.php?module=config-plugins&action=deactivate&uninstall=1&plugin=reportrep&my_post_key={$mybb->post_code}", $lang->reportrep_uninstall_warning);
		}
	}
}

function reportrep_activate()
{
	global $db;
	
	require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
	
	reportrep_deactivate();
	
	$templates = array();
	$templates[] = array(
		"title" => "reportrep_report_link",
		"template" => "[<a href=\"#\" onclick=\"MyBB.popupWindow('{\$mybb->settings['bburl']}/reputation.php?action=report&amp;rid={\$rid}&amp;uid={\$mybb->input['uid']}', 'reportRating', 350, 350);\">{\$lang->report_rating}</a>]{\$reported_rep_image}"
	);
	$templates[] = array(
		"title" => "reportrep_report",
		"template" => "<html>
<head>
<title>{\$mybb->settings['bbname']} - {\$lang->reputation}</title>
{\$headerinclude}
</head>
<body>
<br />
<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
	<tr>
		<td class=\"trow1\" style=\"padding: 20px\">
			<strong>{\$lang->report_rep}</strong><br />{\$lang->report_rep_desc}
			<br /><br />
			<form action=\"reputation.php\" method=\"post\">
				<input type=\"hidden\" name=\"my_post_key\" value=\"{\$mybb->post_code}\" />
				<input type=\"hidden\" name=\"action\" value=\"do_report\" />
				<input type=\"hidden\" name=\"rid\" value=\"{\$rid}\" />
				<input type=\"hidden\" name=\"uid\" value=\"{\$uid}\" />
				<span class=\"smalltext\">{\$lang->report_rep_reason}</span>
				<br />
				<input type=\"text\" class=\"textbox\" name=\"reason\" size=\"35\" maxlength=\"255\" style=\"width: 95%\" />
				<br /><br />
				<div style=\"text-align: center;\">
					<input type=\"submit\" class=\"button\" value=\"{\$lang->submit}\" />
				</div>
			</form>
		</td>
	</tr>
</table>
</body>
</html>"
	);
	$templates[] = array(
			"title" => "reportrep_notice",
			"template" => "<div class=\"red_alert\"><a href=\"modcp.php?action=reportedreps\">{\$lang->reported_reps}</a></div>
	<br />"
	);
	$templates[] = array(
		"title" => "reportrep_reports",
		"template" => "<html>
<head>
	<title>{\$mybb->settings['bbname']} - {\$lang->reported_posts}</title>
	{\$headerinclude}
</head>
<body>
	{\$header}
	<form action=\"modcp.php\" method=\"post\">
		<input type=\"hidden\" name=\"my_post_key\" value=\"{\$mybb->post_code}\" />
		<input type=\"hidden\" name=\"action\" value=\"do_reportedreps\" />
		<table width=\"100%\" border=\"0\" align=\"center\">
			<tr>
				{\$modcp_nav}
				<td valign=\"top\">
					<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
						<tr>
							<td class=\"thead\" colspan=\"5\"><strong>{\$lang->reported_reputations}</strong></td>
						</tr>
						<tr>
							<td class=\"tcat\" align=\"center\" width=\"10%\"><span class=\"smalltext\"><strong>{\$lang->rep_id}</strong></span></td>
							<td class=\"tcat\" align=\"center\" width=\"15%\"><span class=\"smalltext\"><strong>{\$lang->user}</strong></span></td>
							<td class=\"tcat\" align=\"center\" width=\"15%\"><span class=\"smalltext\"><strong>{\$lang->reporter}</strong></span></td>
							<td class=\"tcat\" align=\"center\" width=\"25%\"><span class=\"smalltext\"><strong>{\$lang->report_reason}</strong></span></td>
							<td class=\"tcat\" align=\"center\" width=\"10%\"><span class=\"smalltext\"><strong>{\$lang->report_time}</strong></span></td>
						</tr>
						{\$reports}
						<tr>
						<td class=\"tfoot\" colspan=\"6\" align=\"right\"><span class=\"smalltext\"><strong><a href=\"modcp.php?action=reportedreps{\$all_link}\">{\$all_text}</a></strong></span></td>
						</tr>
					</table>
					<br />
					<div align=\"center\">
						{\$submit_button}
					</div>
				</td>
			</tr>
		</table>
	</form>
	{\$footer}
</body>
</html>"
	);
	$templates[] = array(
			"title" => "reportrep_reports_noreports",
			"template" => "<tr>
	<td class=\"trow1\" align=\"center\" colspan=\"5\">{\$lang->no_rep_reports}</td>
</tr>"
	);
	$templates[] = array(
			"title" => "reportrep_reports_report",
			"template" => "<tr>
	<td class=\"{\$trow}\" align=\"center\">{\$checkbox}<a href=\"{\$mybb->settings['bburl']}/reputation.php?uid={\$report['uid']}#rep_{\$report['rid']}\" target=\"_blank\">{\$report['rid']}</a></label></td>
	<td class=\"{\$trow}\" align=\"center\"><a href=\"{\$report['userlink']}\" target=\"_blank\">{\$report['username']}</a></td>
	<td class=\"{\$trow}\" align=\"center\"><a href=\"{\$report['reporterlink']}\" target=\"_blank\">{\$report['reportername']}</a></td>
	<td class=\"{\$trow}\">{\$report['reason']}</td>
	<td class=\"{\$trow}\" align=\"center\" style=\"white-space: nowrap\"><span class=\"smalltext\">{\$report['date']}<br />{\$report['time']}</td>
</tr>"
	);
	$templates[] = array(
			"title" => "reportrep_reports_report_checkbox",
			"template" => "<label for=\"reports_{\$report['rrid']}\"><input type=\"checkbox\" class=\"checkbox\" name=\"reports[]\" id=\"reports_{\$report['rrid']}\" value=\"{\$report['rrid']}\" />"
	);
	$templates[] = array(
		"title" => "reportrep_modcp_nav_option",
		"template" => "<tr><td class=\"trow1 smalltext\"><a href=\"{\$mybb->settings['bburl']}/modcp.php?action=reportedreps\" class=\"modcp_nav_item modcp_nav_reportrep\">{\$lang->reported_reputations}</a></td></tr>"
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
	
	find_replace_templatesets("header", "#".preg_quote('{$unreadreports}')."#i", '{$unreadreports}{$reportedreps}');
	find_replace_templatesets("reputation_vote", "#".preg_quote('<td')."#i", '<td id="rep_{$reputation_vote[\'rid\']}"');
	find_replace_templatesets("reputation_vote", "#".preg_quote('{$delete_link}')."#i", '{$delete_link}<report_link_{$reputation_vote[\'rid\']}>');
	find_replace_templatesets("modcp_nav", "#".preg_quote('{$lang->mcp_nav_editprofile}</a></td></tr>')."#i", '{$lang->mcp_nav_editprofile}</a></td></tr>{reportrep_nav_option}');
}

function reportrep_deactivate()
{
	global $db;
	
	require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
	
	$templates = array(
		"reportrep_report_link",
		"reportrep_report",
		"reportrep_notice",
		"reportrep_reports",
		"reportrep_reports_noreports",
		"reportrep_reports_report",
		"reportrep_reports_report_checkbox",
		"reportrep_modcp_nav_option"
	);
	$templates = "'" . implode("','", $templates) . "'";
	$db->delete_query("templates", "title IN ({$templates})");
	
	find_replace_templatesets("header", "#".preg_quote('{$reportedreps}')."#i", '', 0);
	find_replace_templatesets("reputation_vote", "#".preg_quote('id="rep_{$reputation_vote[\'rid\']}" ')."#i", '', 0);
	find_replace_templatesets("reputation_vote", "#".preg_quote('<report_link_{$reputation_vote[\'rid\']}>')."#i", '', 0);
	find_replace_templatesets("modcp_nav", "#".preg_quote('{reportrep_nav_option}')."#i", '', 0);
}

function reportrep()
{
	global $mybb, $db, $lang, $templates, $theme, $headerinclude;
	
	$lang->load("reportrep");
	
	if($mybb->input['action'] == "do_report")
	{
		verify_post_check($mybb->input['my_post_key']);
		
		if($mybb->user['uid'] == 0)
		{
			$message = $lang->report_rep_no_permission;
			eval("\$error = \"".$templates->get("reputation_add_error")."\";");
			output_page($error);
			exit;
		}
		
		$rid = intval($mybb->input['rid']);
		$uid = intval($mybb->input['uid']);
		
		$query = $db->simple_select("reputation", "*", "rid = '{$rid}'");
		if($db->num_rows($query) != 1)
		{
			$show_back = 0;
			$message = $lang->report_rep_invalid;
			eval("\$error = \"".$templates->get("reputation_add_error")."\";");
			output_page($error);
			exit;
		}
		
		if(!strlen(trim($mybb->input['reason'])))
		{
			$show_back = 1;
			$message = $lang->report_rep_invalid_reason;
			eval("\$error = \"".$templates->get("reputation_add_error")."\";");
			output_page($error);
			exit;
		}
		
		$insert = array(
			"rid" => intval($mybb->input['rid']),
			"uid" => intval($mybb->input['uid']),
			"ruid" => intval($mybb->user['uid']),
			"reason" => $db->escape_string($mybb->input['reason']),
			"time" => TIME_NOW,
			"active" => 1
		);
		$db->insert_query("reportedreps", $insert);
		
		reportrep_cache_count();
		
		$lang->vote_added = $lang->report_rep_success;
		$lang->vote_added_message = $lang->report_rep_success_desc;
		eval("\$error = \"".$templates->get("reputation_added")."\";");
		output_page($error);
		exit;
	}
	elseif($mybb->input['action'] == "report")
	{
		if($mybb->user['uid'] == 0)
		{
			$message = $lang->report_rep_no_permission;
			eval("\$error = \"".$templates->get("reputation_add_error")."\";");
			output_page($error);
			exit;
		}
		
		$rid = intval($mybb->input['rid']);
		$uid = intval($mybb->input['uid']);
		
		$query = $db->simple_select("reportedreps", "rrid", "rid = '{$rid}' AND active = '1'");
		if($db->num_rows($query) != 0)
		{
			$show_back = 0;
			$message = $lang->report_rep_already_reported;
			eval("\$error = \"".$templates->get("reputation_add_error")."\";");
			output_page($error);
			exit;
		}
		
		$query = $db->simple_select("reputation", "*", "rid = '{$rid}'");
		if($db->num_rows($query) != 1)
		{
			$show_back = 0;
			$message = $lang->report_rep_invalid;
			eval("\$error = \"".$templates->get("reputation_add_error")."\";");
			output_page($error);
			exit;
		}
		
		eval("\$reportrep_report = \"".$templates->get('reportrep_report')."\";");
		output_page($reportrep_report);
	}
	elseif($mybb->input['action'] == "delete")
	{
		// this mainly consists of the default code; there's no hooks in here, and I need to run code after the rep's been deleted
		
		$uid = intval($mybb->input['uid']);
		
		// Verify incoming POST request
		verify_post_check($mybb->input['my_post_key']);
		
		// Fetch the existing reputation for this user given by our current user if there is one.
		$query = $db->simple_select("reputation", "*", "rid='".$mybb->input['rid']."'");
		$existing_reputation = $db->fetch_array($query);
		
		// Only administrators, super moderators, as well as users who gave a specifc vote can delete one.
		if($mybb->usergroup['cancp'] != 1 && $mybb->usergroup['issupermod'] != 1 && $existing_reputation['adduid'] != $mybb->user['uid'])
		{
			error_no_permission();
		}
		
		// Delete the specified reputation
		$db->delete_query("reputation", "uid='{$uid}' AND rid='".$mybb->input['rid']."'");
		
		// Recount the reputation of this user - keep it in sync.
		$query = $db->simple_select("reputation", "SUM(reputation) AS reputation_count", "uid='{$uid}'");
		$reputation_value = $db->fetch_field($query, "reputation_count");
		
		$db->update_query("users", array('reputation' => intval($reputation_value)), "uid='{$uid}'");
		
		$db->delete_query("reportedreps", "rid = '{$mybb->input['rid']}'");
		reportrep_cache_count();
		
		redirect("reputation.php?uid={$uid}", $lang->vote_deleted_message);
	}
}

function reportrep_report_link()
{
	global $mybb, $db, $lang, $theme, $templates, $reputation_votes;
	
	$lang->load("reportrep");
	
	if($mybb->user['uid'] == 0)
	{
		return;
	}
	
	$query = $db->simple_select("reportedreps", "rid", "active = '1'");
	$reported_reps = array();
	while($rid = $db->fetch_field($query, "rid"))
	{
		$reported_reps[] = intval($rid);
	}
	
	preg_match_all("#report_link_[0-9]{1,10}#", $reputation_votes, $matches);
	foreach($matches[0] as $match)
	{
		$rid = str_replace("report_link_", "", $match);
		$reported_rep_image = "";
		if(in_array($rid, $reported_reps))
		{
			$reported_rep_image = " <img src=\"{$mybb->settings['bburl']}/{$theme['imgdir']}/error.gif\" alt=\"{$lang->report_rep_reported}\" title=\"{$lang->report_rep_reported}\" />";
		}
		eval("\$report_link = \"".$templates->get('reportrep_report_link')."\";");
		$reputation_votes = str_replace("<report_link_{$rid}>", $report_link, $reputation_votes);
	}
}

function reportrep_notice()
{
	global $mybb, $cache, $lang, $templates, $reportedreps;
	
	$lang->load("reportrep");
	
	if($mybb->usergroup['cancp'] == 1 || $mybb->usergroup['issupermod'] == 1)
	{
		$reported_reps = $cache->read("reportedreps");
		
		if($reported_reps > 0)
		{
			if($reported_reps == 1)
			{
				$lang->reported_reps = $lang->reported_rep;
			}
			else
			{
				$lang->reported_reps = $lang->sprintf($lang->reported_reps, $reported_reps);
			}
			eval("\$reportedreps = \"".$templates->get("reportrep_notice")."\";");
		}
	}
}

function reportrep_reports()
{
	global $mybb, $db, $lang, $templates, $theme, $reportrep_reports, $header, $headerinclude, $footer, $modcp_nav;
	
	if($mybb->input['action'] == "do_reportedreps")
	{
		verify_post_check($mybb->input['my_post_key']);
		
		if(!is_array($mybb->input['reports']))
		{
			error($lang->error_noselected_rep_reports);
		}
		
		$reports = implode(",", array_map("intval", $mybb->input['reports']));
		
		$update = array(
			"active" => 0
		);
		$db->update_query("reportedreps", $update, "rrid IN (" . $db->escape_string($reports) . ")");
		
		reportrep_cache_count();
		
		redirect("modcp.php?action=reportedreps", $lang->redirect_rep_reports_marked);
	}
	elseif($mybb->input['action'] == "reportedreps")
	{
		add_breadcrumb($lang->nav_modcp, "modcp.php");
		add_breadcrumb($lang->reported_reputations, "modcp.php?action=reportedreps");
		
		$where_sql = "";
		if($mybb->input['all'] != 1)
		{
			$where_sql = "WHERE active = '1'";
		}
		$query = $db->write_query("
			SELECT r.*, u1.username, u2.username AS reportername
			FROM " . TABLE_PREFIX . "reportedreps r
			LEFT JOIN " . TABLE_PREFIX . "users u1 ON (u1.uid = r.uid)
			LEFT JOIN " . TABLE_PREFIX . "users u2 ON (u2.uid = r.ruid)
			{$where_sql}
			ORDER BY rrid DESC
		");
		if($db->num_rows($query) == 0)
		{
			eval("\$reports = \"".$templates->get("reportrep_reports_noreports")."\";");
		}
		else
		{
			while($report = $db->fetch_array($query))
			{
				$trow = alt_trow();
				if($mybb->input['all'] == 1 && $report['active'] == 1)
				{
					$trow = "trow_shaded";
				}
				
				if($mybb->input['all'] != 1)
				{
					eval("\$checkbox = \"".$templates->get("reportrep_reports_report_checkbox")."\";");
				}
				
				$report['userlink'] = get_profile_link($report['uid']);
				$report['reporterlink'] = get_profile_link($report['ruid']);
				
				$report['date'] = my_date($mybb->settings['dateformat'], $report['time']);
				$report['time'] = my_date($mybb->settings['timeformat'], $report['time']);
				
				eval("\$reports .= \"".$templates->get("reportrep_reports_report")."\";");
			}
		}
		
		if($mybb->input['all'] != 1)
		{
			$all_link = "&amp;all=1";
			$all_text = $lang->view_all_reported_reps;
			if($db->num_rows($query) != 0)
			{
				$submit_button = "<input type=\"submit\" class=\"button\" value=\"{$lang->reported_reputations_read}\" />";
			}
		}
		else
		{
			$all_link = "";
			$all_text = $lang->view_unread_reported_reps;
		}
		
		eval("\$reportrep_reports = \"".$templates->get("reportrep_reports")."\";");
		output_page($reportrep_reports);
	}
}

function reportrep_navoption()
{
	global $mybb, $lang, $templates, $modcp_nav, $reportrep_nav_option;
	
	$lang->load("reportrep");
	
	$reportrep_nav_option = "";
	eval("\$reportrep_nav_option = \"".$templates->get("reportrep_modcp_nav_option")."\";");
	$modcp_nav = str_replace("{reportrep_nav_option}", $reportrep_nav_option, $modcp_nav);
}

function reportrep_friendly_wol(&$user_activity)
{
	global $user;
	
	if(my_strpos($user['location'], "modcp.php?action=reportedreps") !== false)
	{
		$user_activity['activity'] = "modcp_reportedreps";
	}
	elseif(my_strpos($user['location'], "reputation.php?action=report") !== false)
	{
		$user_activity['activity'] = "reputation_reporting";
	}
}

function reportrep_build_wol(&$plugin_array)
{
	global $lang;
	
	$lang->load("reportrep");
	
	if($plugin_array['user_activity']['activity'] == "modcp_reportedreps")
	{
		$plugin_array['location_name'] = $lang->reportedreps_viewing_list_wol;
	}
	elseif($plugin_array['user_activity']['activity'] == "reputation_reporting")
	{
		$plugin_array['location_name'] = $lang->reportedreps_reporting_wol;
	}
}

function reportrep_cache_count()
{
	global $db, $cache;
	
	$query = $db->simple_select("reportedreps", "COUNT(*) AS reportedreps", "active = '1'");
	$reportedreps = $db->fetch_field($query, "reportedreps");
	
	$cache->update("reportedreps", $reportedreps);
}
?>