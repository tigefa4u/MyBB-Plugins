<?php
/**
 * Custom User Permissions 0.2.2 - Admin File

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

$page->add_breadcrumb_item($lang->customuserperms, "index.php?module=user-customuserperms");

if($mybb->input['action'] == "do_edit")
{
	if(!verify_post_check($mybb->input['my_post_key']))
	{
		flash_message($lang->invalid_post_verify_key2, 'error');
		admin_redirect("index.php?module=user-customuserperms");
	}
	
	$cupid = intval($mybb->input['cupid']);
	$fid = intval($mybb->input['fid']);
	
	$query = $db->simple_select("customuserperms", "customperms", "cupid = '{$cupid}'");
	if($db->num_rows($query) == 0)
	{
		flash_message($lang->customuserperms_invalid, 'error');
		admin_redirect("index.php?module=user-customuserperms");
	}
	
	$perms = $db->fetch_field($query, "customperms");
	$perms = unserialize($perms);
	
	if($mybb->input['do'] == "do_general")
	{
		$perms['general'] = array();
		foreach($mybb->input['perms'] as $perm => $value)
		{
			if(should_add_customperms($mybb->input['perms'], $perm, $value))
			{
				$perms['general'][$perm] = $value;
			}
		}
		$do = "general";
	}
	if($mybb->input['do'] == "do_forums")
	{
		$perms['forums'][$fid] = array();
		foreach($mybb->input['perms'] as $perm => $value)
		{
			if(should_add_customperms($mybb->input['perms'], $perm, $value))
			{
				$perms['forums'][$fid][$perm] = $value;
			}
		}
		$perms['forums'] = reorder_forums($perms['forums']);
		$do = "forums";
		if($fid == -1)
		{
			$fid_url = "&amp;fid=-1";
		}
	}
	
	$perms = serialize($perms);
	
	$update = array(
		"customperms" => $db->escape_string($perms)
	);
	$db->update_query("customuserperms", $update, "cupid = '{$cupid}'");
	
	flash_message($lang->customuserperms_updated, 'success');
	admin_redirect("index.php?module=user-customuserperms&action=edit&do={$do}&cupid={$cupid}{$fid_url}");
}
elseif($mybb->input['action'] == "edit")
{
	$cupid = intval($mybb->input['cupid']);
	
	$query = $db->simple_select("customuserperms", "uid, customperms", "cupid = '{$cupid}'");
	if($db->num_rows($query) == 0)
	{
		flash_message($lang->customuserperms_invalid, 'error');
		admin_redirect("index.php?module=user-customuserperms");
	}
	
	$customuserperms = $db->fetch_array($query);
	$uid = $customuserperms['uid'];
	$user = get_user($uid);
	$username = $user['username'];
	$user_perms = unserialize($customuserperms['customperms']);
	
	$page->add_breadcrumb_item($lang->customuserperms_edit, "index.php?module=user-customuserperms&amp;action=edit");
	
	$sub_tabs = array();
	$sub_tabs['customuserperms'] = array(
		'title' => $lang->home,
		'link' => "index.php?module=user-customuserperms",
		'description' => $lang->customuserperms_nav
	);
	$sub_tabs['customuserperms_edit_general'] = array(
		'title' => $lang->customuserperms_edit_general,
		'link' => "index.php?module=user-customuserperms&amp;action=edit&amp;do=general&amp;cupid={$cupid}",
		'description' => $lang->customuserperms_edit_general_nav
	);
	$sub_tabs['customuserperms_edit_global_forums'] = array(
		'title' => $lang->customuserperms_edit_global_forums,
		'link' => "index.php?module=user-customuserperms&amp;action=edit&amp;do=forums&amp;cupid={$cupid}&amp;fid=-1",
		'description' => $lang->customuserperms_edit_global_forums_nav
	);
	$sub_tabs['customuserperms_edit_specific_forums'] = array(
		'title' => $lang->customuserperms_edit_specific_forums,
		'link' => "index.php?module=user-customuserperms&amp;action=edit&amp;do=forums&amp;cupid={$cupid}",
		'description' => $lang->customuserperms_edit_specific_forums_nav
	);
	
	$set_all_js = "<script type=\"text/javascript\">
	document.observe(\"dom:loaded\", function() {
		$('set_all_inherit').observe('click', function() {
			set_all('-1');
		});
		$('set_all_yes').observe('click', function() {
			set_all('1');
		});
		$('set_all_no').observe('click', function() {
			set_all('0');
		});
	});
	function set_all(what) {
		$$('input').each(function(input) {
			input.checked = false;
		});
		$$('input[value='+what+']').each(function(input) {
			input.checked = true;
		});
	}
	</script>";
	
	if($mybb->input['do'] == "forums")
	{
		$fid = intval($mybb->input['fid']);
		if($fid == -1)
		{
			$page->add_breadcrumb_item($lang->customuserperms_edit_global_forums, "index.php?module=user-customuserperms&amp;action=edit&amp;do=forums");
			$page->output_header($lang->customuserperms);
			$page->output_nav_tabs($sub_tabs, "customuserperms_edit_global_forums");
		}
		else
		{
			$page->add_breadcrumb_item($lang->customuserperms_edit_specific_forums, "index.php?module=user-customuserperms&amp;action=edit&amp;do=forums");
			$page->output_header($lang->customuserperms);
			$page->output_nav_tabs($sub_tabs, "customuserperms_edit_specific_forums");
		}
		
		echo $set_all_js;
		
		if($fid)
		{
			$forums = $cache->read("forums");
			$forum = $forums[$fid]['name'];
			
			$permissions = array(
				"group_viewing" => array(
					"canview" => array(
						"lang" => "viewing_field_canview",
						"type" => "yesno"
					),
					"canviewthreads" => array(
						"lang" => "viewing_field_canviewthreads",
						"type" => "yesno"
					),
					"canonlyviewownthreads" => array(
						"lang" => "viewing_field_canonlyviewownthreads",
						"type" => "yesno"
					),
					"candlattachments" => array(
						"lang" => "viewing_field_candlattachments",
						"type" => "yesno"
					)
				),
				"group_posting_rating" => array(
					"canpostthreads" => array(
						"lang" => "posting_rating_field_canpostthreads",
						"type" => "yesno"
					),
					"canpostreplys" => array(
						"lang" => "posting_rating_field_canpostreplys",
						"type" => "yesno"
					),
					"canpostattachments" => array(
						"lang" => "posting_rating_field_canpostattachments",
						"type" => "yesno"
					),
					"canratethreads" => array(
						"lang" => "posting_rating_field_canratethreads",
						"type" => "yesno"
					)
				),
				"group_editing" => array(
					"caneditposts" => array(
						"lang" => "editing_field_caneditposts",
						"type" => "yesno"
					),
					"candeleteposts" => array(
						"lang" => "editing_field_candeleteposts",
						"type" => "yesno"
					),
					"candeletethreads" => array(
						"lang" => "editing_field_candeletethreads",
						"type" => "yesno"
					),
					"caneditattachments" => array(
						"lang" => "editing_field_caneditattachments",
						"type" => "yesno"
					)
				),
				"group_polls" => array(
					"canpostpolls" => array(
						"lang" => "polls_field_canpostpolls",
						"type" => "yesno"
					),
					"canvotepolls" => array(
						"lang" => "polls_field_canvotepolls",
						"type" => "yesno"
					)
				)
			);
			if($fid != -1)
			{
				$permissions['group_misc'] = array(
					'cansearch' => array(
						'lang' => 'misc_field_cansearch',
						'type' => 'yesno'
					)
				);
			}
			
			$lang->load("forum_management");
			
			$form = new Form("index.php?module=user-customuserperms&amp;action=do_edit", "post");
			if($fid == -1)
			{
				$form_container = new FormContainer($lang->sprintf($lang->customuserperms_edit_global_forums_user, $username));
			}
			else
			{
				$form_container = new FormContainer($lang->sprintf($lang->customuserperms_edit_specific_forums_user_forum, $username, $forum));
			}
			$form_container->output_row_header($lang->permission, array("class" => "align_center", 'style' => 'width: 30%'));
			$form_container->output_row_header($lang->controls, array("class" => "align_center", "colspan" => 3));
			
			generate_permissions($permissions, $user_perms['forums'][$fid]);
			
			$form_container->end();
			
			echo $form->generate_hidden_field("cupid", $cupid);
			echo $form->generate_hidden_field("fid", $fid);
			echo $form->generate_hidden_field("do", "do_forums");
			
			$buttons[] = $form->generate_submit_button($lang->submit);
			$form->output_submit_wrapper($buttons);
			$form->end();
		}
		else
		{
			if(!is_array($user_perms['forums']))
			{
				$user_perms['forums'] = array();
			}
			if(array_key_exists(-1, $user_perms['forums']))
			{
				unset($user_perms['forums'][-1]);
			}
			if(count($user_perms['forums']) > 0)
			{
				$table = new Table;
				
				$table->construct_header($lang->forum);
				$table->construct_header($lang->custom_perms_overview);
				$table->construct_header($lang->controls, array("colspan" => 2, 'class' => 'align_center'));
				
				$forums = $cache->read("forums");
				foreach($user_perms['forums'] as $forum => $perms)
				{
					if(!array_key_exists($forum, $forums))
					{
						customuserperms_delete_forum_perms($cupid, $forum);
						continue;
					}
					$custom_perms_overview = "";
					$perm_count = 0;
					foreach($perms as $key => $val)
					{
						if($val != -1)
						{
							$perm_count++;
						}
					}
					if(!empty($perm_count))
					{
						$custom_perms_overview .= $lang->sprintf($lang->customperms_forums, $perm_count);
					}
					if(empty($perm_count))
					{
						$custom_perms_overview = $lang->none;
					}
					
					$table->construct_cell($forums[$forum]['name'], array('width' => '20%'));
					$table->construct_cell($custom_perms_overview, array('width' => '35%'));
					$table->construct_cell("<a href=\"index.php?module=user-customuserperms&amp;action=edit&amp;do=forums&amp;cupid={$cupid}&amp;fid={$forum}\">{$lang->view_edit}</a>", array('class' => 'align_center', 'width' => '15%'));
					$table->construct_cell("<a href=\"index.php?module=user-customuserperms&amp;action=delete&amp;cupid={$cupid}&amp;fid={$forum}\">{$lang->delete}</a>", array('class' => 'align_center', 'width' => '15%'));
					$table->construct_row();
				}
				
				$table->output($lang->sprintf($lang->customuserperms_current_forums, $username));
				
				$forum_names[$forum] = $forums[$forum]['name'];
			}
			
			$form = new Form("index.php?module=user-customuserperms&amp;action=do_add_forum", "post");
			$form_container = new FormContainer($lang->customuserperms_add_forum);
			$table = new Table;
			
			$form_container->output_row($lang->customuserperms_choose_forum, "", $form->generate_forum_select('fid', "", array('multiple' => false, 'size' => 5)));
			
			$form_container->end();
			
			echo $form->generate_hidden_field("cupid", $cupid);
			
			$buttons[] = $form->generate_submit_button($lang->submit);
			$form->output_submit_wrapper($buttons);
			$form->end();
		}
		
		$page->output_footer();
	}
	else
	{
		$page->add_breadcrumb_item($lang->customuserperms_edit_general, "index.php?module=user-customuserperms&amp;action=edit&amp;do=do_general");
		$page->output_header($lang->customuserperms);
		$page->output_nav_tabs($sub_tabs, "customuserperms_edit_general");
		
		echo $set_all_js;
		
		$permissions = array(
			"viewing_options" => array(
				"cansearch" => array(
					"lang" => "can_search_forums",
					"type" => "yesno"
				),
				"canviewprofiles" => array(
					"lang" => "can_view_profiles",
					"type" => "yesno"
				)
			),
			"attachment_options" => array(
				"attachquota" => array(
					"lang" => "attach_quota",
					"type" => "text",
					"size" => 50
				)
			),
			"account_management" => array(
				"canusercp" => array(
					"lang" => "can_access_usercp",
					"type" => "yesno"
				),
				"canchangename" => array(
					"lang" => "can_change_username",
					"type" => "yesno"
				),
				"cancustomtitle" => array(
					"lang" => "can_use_usertitles",
					"type" => "yesno"
				),
				"canuploadavatars" => array(
					"lang" => "can_upload_avatars",
					"type" => "yesno"
				)
			),
			"reputation_system" => array(
				"usereputationsystem" => array(
					"lang" => "show_reputations",
					"type" => "yesno"
				),
				"cangivereputations" => array(
					"lang" => "can_give_reputation",
					"type" => "yesno"
				),
				"reputationpower" => array(
					"lang" => "points_to_award_take",
					"type" => "text",
					"size" => 50
				),
				"maxreputationsday" => array(
					"lang" => "max_reputations_daily",
					"type" => "text",
					"size" => 50
				)
			),
			"private_messaging" => array(
				"canusepms" => array(
					"lang" => "can_use_pms",
					"type" => "yesno"
				),
				"cansendpms" => array(
					"lang" => "can_send_pms",
					"type" => "yesno"
				)
			),
			"calendar" => array(
				"canviewcalendar" => array(
					"lang" => "can_view_calendar",
					"type" => "yesno"
				),
				"canaddevents" => array(
					"lang" => "can_post_events",
					"type" => "yesno"
				)
				,
				"canbypasseventmod" => array(
					"lang" => "can_bypass_event_moderation",
					"type" => "yesno"
				)
				,
				"canmoderateevents" => array(
					"lang" => "can_moderate_events",
					"type" => "yesno"
				)
			),
			"whos_online" => array(
				"canviewonline" => array(
					"lang" => "can_view_whos_online",
					"type" => "yesno"
				),
				"canviewwolinvis" => array(
					"lang" => "can_view_invisible",
					"type" => "yesno"
				)
			),
			"misc" => array(
				"canviewmemberlist" => array(
					"lang" => "can_view_member_list",
					"type" => "yesno"
				),
				"cansendemail" => array(
					"lang" => "can_email_users",
					"type" => "yesno"
				),
				"maxemails" => array(
					"lang" => "max_emails_per_day",
					"type" => "text",
					"size" => 50
				)
			)
		);
		
		$lang->load("user_groups");
		
		$form = new Form("index.php?module=user-customuserperms&amp;action=do_edit", "post");
		$form_container = new FormContainer($lang->sprintf($lang->customuserperms_edit_general_user, $username));
		$form_container->output_row_header($lang->permission, array("class" => "align_center", 'style' => 'width: 30%'));
		$form_container->output_row_header($lang->controls, array("class" => "align_center", "colspan" => 3));
		
		generate_permissions($permissions, $user_perms['general']);
		
		$form_container->end();
		
		echo $form->generate_hidden_field("cupid", $cupid);
		echo $form->generate_hidden_field("do", "do_general");
		
		$buttons[] = $form->generate_submit_button($lang->submit);
		$form->output_submit_wrapper($buttons);
		$form->end();
		
		$page->output_footer();
	}
}
elseif($mybb->input['action'] == "do_add_user")
{
	if(!verify_post_check($mybb->input['my_post_key']))
	{
		flash_message($lang->invalid_post_verify_key2, 'error');
		admin_redirect("index.php?module=user-customuserperms");
	}
	
	if(!username_exists($mybb->input['username']))
	{
		flash_message($lang->customuserperms_invalid_username, 'error');
		admin_redirect("index.php?module=user-customuserperms");
	}
	else
	{
		$query = $db->simple_select("users", "uid, hascustomperms", "username = '" . $db->escape_string($mybb->input['username']) . "'", array("limit" => 1));
		$user = $db->fetch_array($query);
		
		if($user['hascustomperms'] == 1)
		{
			flash_message($lang->customuserperms_duplicate_user, 'error');
			admin_redirect("index.php?module=user-customuserperms");
		}
		
		$insert = array(
			"uid" => intval($user['uid']),
			"active" => 1
		);
		$db->insert_query("customuserperms", $insert);
		
		$update = array(
			"hascustomperms" => 1
		);
		$db->update_query("users", $update, "uid = '" . intval($user['uid']) . "'");
		
		flash_message($lang->customuserperms_user_added, 'success');
		admin_redirect("index.php?module=user-customuserperms");
	}
}
elseif($mybb->input['action'] =="do_add_forum")
{
	if(!verify_post_check($mybb->input['my_post_key']))
	{
		flash_message($lang->invalid_post_verify_key2, 'error');
		admin_redirect("index.php?module=user-customuserperms");
	}
	
	$fid = intval($mybb->input['fid']);
	$cupid = intval($mybb->input['cupid']);
	
	$forums = $cache->read("forums");
	if(!$forums[$fid])
	{
		flash_message($lang->customuserperms_invalid_forum, 'error');
		admin_redirect("index.php?module=user-customuserperms&action=edit&do=forums&cupid={$cupid}");
	}
	
	$query = $db->simple_select("customuserperms", "customperms", "cupid = '{$cupid}'");
	$customperms = $db->fetch_field($query, "customperms");
	$customperms = unserialize($customperms);
	
	if(is_array($customperms['forums'][$fid]))
	{
		flash_message($lang->customuserperms_duplicate_forum, 'error');
		admin_redirect("index.php?module=user-customuserperms&action=edit&do=forums&cupid={$cupid}");
	}
	else
	{
		$customperms['forums'][$fid] = array();
		$customperms['forums'] = reorder_forums($customperms['forums']);
		$customperms = serialize($customperms);
		
		$update = array(
			"customperms" => $db->escape_string($customperms)
		);
		$db->update_query("customuserperms", $update, "cupid = '{$cupid}'");
		
		flash_message($lang->customuserperms_forum_added, 'success');
		admin_redirect("index.php?module=user-customuserperms&action=edit&do=forums&cupid={$cupid}");
	}
}
elseif($mybb->input['action'] == "do_delete")
{
	$cupid = intval($mybb->input['cupid']);
	$fid = intval($mybb->input['fid']);
	
	if($mybb->input['no'])
	{
		if($fid)
		{
			admin_redirect("index.php?module=user-customuserperms&action=edit&do=forums&cupid={$cupid}");
		}
		else
		{
			admin_redirect("index.php?module=user-customuserperms");
		}
	}
	else
	{
		if(!verify_post_check($mybb->input['my_post_key']))
		{
			flash_message($lang->invalid_post_verify_key2, 'error');
			admin_redirect("index.php?module=user-customuserperms");
		}
		
		$query = $db->simple_select("customuserperms", "uid, customperms", "cupid = '{$cupid}'");
		$customuserperms = $db->fetch_array($query);
		
		if(!$customuserperms['uid'])
		{
			flash_message($lang->customuserperms_invalid, 'error');
			admin_redirect("index.php?module=user-customuserperms");
		}
		else
		{
			$uid = intval($customuserperms['uid']);
			$user = get_user($uid);
			$username = $user['username'];
			
			if($fid)
			{
				$forum = get_forum($fid);
				
				$customperms = $customuserperms['customperms'];
				$customperms = unserialize($customperms);
				unset($customperms['forums'][$fid]);
				$customperms = serialize($customperms);
				
				$update = array(
					"customperms" => $db->escape_string($customperms)
				);
				$db->update_query("customuserperms", $update, "cupid = '{$cupid}'");
				
				flash_message($lang->sprintf($lang->customuserperms_deleted_forum, $username, $forum['name']), 'success');
				admin_redirect("index.php?module=user-customuserperms&action=edit&do=forums&cupid={$cupid}");
			}
			else
			{
				$db->delete_query("customuserperms", "cupid = '{$cupid}'");
				$update = array(
					"hascustomperms" => 0
				);
				$db->update_query("users", $update, "uid = '{$uid}'");
				
				flash_message($lang->sprintf($lang->customuserperms_deleted, $username), 'success');
				admin_redirect("index.php?module=user-customuserperms");
			}
		}
	}
}
elseif($mybb->input['action'] == "delete")
{
	if($mybb->input['fid'])
	{
		$fid = "&amp;fid=" . intval($mybb->input['fid']);
		$message = $lang->customuserperms_delete_forum;
	}
	else
	{
		$fid = "";
		$message = $lang->customuserperms_delete;
	}
	
	$page->output_confirm_action("index.php?module=user-customuserperms&action=do_delete&cupid={$mybb->input['cupid']}{$fid}&my_post_key={$mybb->post_code}", $message);
}
elseif($mybb->input['action'] == "status")
{
	if(!verify_post_check($mybb->input['my_post_key']))
	{
		flash_message($lang->invalid_post_verify_key2, 'error');
		admin_redirect("index.php?module=user-customuserperms");
	}
	
	$cupid = intval($mybb->input['cupid']);
	
	$query = $db->write_query("
		SELECT p.active, u.username, u.uid
		FROM " . TABLE_PREFIX . "customuserperms p
		LEFT JOIN " . TABLE_PREFIX . "users u
		ON p.uid = u.uid
		WHERE p.cupid = '{$cupid}'
	");
	
	if($db->num_rows($query) == 0)
	{
		flash_message($lang->customuserperms_invalid, 'error');
		admin_redirect("index.php?module=user-customuserperms");
	}
	
	$perm = $db->fetch_array($query);
	
	if($perm['active'] == 1)
	{
		$active = 0;
		$flash_message = $lang->sprintf($lang->custom_user_perms_deactivated, $perm['username']);
	}
	else
	{
		$active = 1;
		$flash_message = $lang->sprintf($lang->custom_user_perms_activated, $perm['username']);
	}
	
	$update = array(
		"active" => $active
	);
	$db->update_query("customuserperms", $update, "cupid = '{$cupid}'");
	
	$update = array(
		"hascustomperms" => $active
	);
	$db->update_query("users", $update, "uid = '" . intval($perm['uid']) . "'");
	
	flash_message($flash_message, 'success');
	admin_redirect("index.php?module=user-customuserperms");
}
else
{
	$page->output_header($lang->customuserperms);
	
	$sub_tabs = array();
	$sub_tabs['customuserperms'] = array(
		'title' => $lang->customuserperms,
		'link' => "index.php?module=user-customuserperms",
		'description' => $lang->customuserperms_nav
	);
	
	$page->output_nav_tabs($sub_tabs, "customuserperms");
	
	$query = $db->write_query("
		SELECT p.*, u.username
		FROM " . TABLE_PREFIX . "customuserperms p
		LEFT JOIN " . TABLE_PREFIX . "users u
		ON (p.uid = u.uid)
		ORDER BY p.cupid ASC
	");
	//SELECT p.*, u.username FROM mybb_customuserperms p LEFT JOIN mybb_users u ON (p.uid = u.uid) ORDER BY p.cupid ASC
	if($db->num_rows($query) > 0)
	{
		$table = new Table;
		
		$table->construct_header($lang->username);
		$table->construct_header($lang->custom_perms_overview);
		$table->construct_header($lang->controls, array("colspan" => 3, 'class' => 'align_center'));
		
		while($perm = $db->fetch_array($query))
		{
			if($perm['active'] == 1)
			{
				$status = $lang->deactivate;
			}
			else
			{
				$status = $lang->activate;
			}
			
			$customperms = unserialize($perm['customperms']);
			$custom_perms_overview = "";
			if(!empty($customperms['general']))
			{
				$count = count($customperms['general']);
				foreach($customperms['general'] as $permission => $value)
				{
					if(strpos($permission, "_value"))
					{
						--$count;
					}
				}
				$custom_perms_overview .= $lang->sprintf($lang->customperms_general, $count);
			}
			if(!empty($customperms['forums']))
			{
				if(array_key_exists(-1, $customperms['forums']) && empty($customperms['forums'][-1]))
				{
					unset($customperms['forums'][-1]);
				}
				if(!empty($customperms['forums']))
				{
					if(!empty($custom_perms_overview))
					{
						$custom_perms_overview .= ", ";
					}
					$custom_perms_overview .= $lang->sprintf($lang->customperms_forums, count($customperms['forums']));
				}
			}
			if(empty($custom_perms_overview))
			{
				$custom_perms_overview = $lang->none;
			}
			
			$table->construct_cell($perm['username'], array('width' => '20%'));
			$table->construct_cell($custom_perms_overview, array('width' => '35%'));
			$table->construct_cell("<a href=\"index.php?module=user-customuserperms&amp;action=edit&amp;cupid={$perm['cupid']}\">{$lang->view_edit}</a>", array('class' => 'align_center', 'width' => '15%'));
			$table->construct_cell("<a href=\"index.php?module=user-customuserperms&amp;action=status&amp;cupid={$perm['cupid']}&amp;my_post_key={$mybb->post_code}\">{$status}</a>", array('class' => 'align_center', 'width' => '15%'));
			$table->construct_cell("<a href=\"index.php?module=user-customuserperms&amp;action=delete&amp;cupid={$perm['cupid']}\">{$lang->delete}</a>", array('class' => 'align_center', 'width' => '15%'));
			$table->construct_row();
		}
		
		$table->output($lang->customuserperms_current);
	}
	
	$form = new Form("index.php?module=user-customuserperms&amp;action=do_add_user", "post");
	$form_container = new FormContainer($lang->customuserperms_add_user);
	
	$customuserperms_add_user_name = $form->generate_text_box("username");
	$form_container->output_row($lang->username . " <em>*</em>", '', $customuserperms_add_user_name);
	
	$form_container->end();
	
	$buttons[] = $form->generate_submit_button($lang->submit);
	$form->output_submit_wrapper($buttons);
	$form->end();
	
	echo "<br />";
	
	echo "<fieldset>
	<legend>{$lang->customuserperms_explained}</legend>
	<dl>
		<dt>
			<strong>{$lang->customuserperms_edit_general}</strong>
		</dt>
		<dd>
			{$lang->customuserperms_edit_general_key}
		</dd>
		<dt>
			<strong>{$lang->customuserperms_edit_global_forums}</strong>
		</dt>
		<dd>
			{$lang->customuserperms_edit_global_forums_key}
		</dd>
		<dt>
			<strong>{$lang->customuserperms_edit_specific_forums}</strong>
		</dt>
		<dd>
			{$lang->customuserperms_edit_specific_forums_key}
		</dd>
	</dl>
</fieldset>";
	
	$page->output_footer();
}

function check_radio_button($perms, $perm, $value)
{
	// either this permission is set and the value we're checking is what it's set to, or it's not set and we're checking the 'inherit' option
	if((isset($perms[$perm]) && $perms[$perm] == $value) || (!isset($perms[$perm]) && $value == -1))
	{
		return 1;
	}
	else
	{
		return 0;
	}
}

function should_add_customperms($perms, $perm, $value)
{
	// if this is a numerical value option, store it if any value is set, even if it's not set to be used
	if(substr($perm, -6) == "_value")
	{
		if(!empty($value))
		{
			return true;
		}
	}
	// if this is the option to choose whether to use a numerical value, store it as enabled if it's set to 1 and the actual value isn't empty
	elseif(array_key_exists($perm . "_value", $perms))
	{
		if($value == 1 && !empty($perms[$perm . "_value"]))
		{
			return true;
		}
	}
	// else it's just a standard option, if it's not set to inherit, store it
	elseif($value != -1)
	{
		return true;
	}
	// don't store it, it's set to inherit
	else
	{
		return false;
	}
}

function reorder_forums($perms)
{
	global $cache;
	
	$ordered_perms = array();
	$forums = array_keys($cache->read("forums"));
	if(array_key_exists(-1, $perms))
	{
		$ordered_perms[-1] = $perms[-1];
	}
	foreach($forums as $forum)
	{
		if(array_key_exists($forum, $perms))
		{
			$ordered_perms[$forum] = $perms[$forum];
		}
	}
	
	return $ordered_perms;
}

function generate_permissions($permissions, $user_perms)
{
	global $lang, $form, $form_container;
	
	$done_groups = 0;
	foreach($permissions as $group => $perms)
	{
		if($done_groups == 0)
		{
			$form_container->output_cell("<strong>" . $lang->$group . "</strong>");
			$form_container->output_cell("<a href='javascript:void(0)' class='set_all' id='set_all_inherit'>" . $lang->inherit_all . "</a>", array('style' => 'text-align: center;'));
			$form_container->output_cell("<a href='javascript:void(0)' class='set_all' id='set_all_yes'>" . $lang->yes_all . "</a>", array('style' => 'text-align: center;'));
			$form_container->output_cell("<a href='javascript:void(0)' class='set_all' id='set_all_no'>" . $lang->no_all . "</a>", array('style' => 'text-align: center;'));
			$form_container->construct_row();
		}
		else
		{
			$form_container->output_cell("<strong>" . $lang->$group . "</strong>", array("colspan" => 4));
			$form_container->construct_row();
		}
		foreach($perms as $perm => $info)
		{
			$info['description'] = "";
			if($info['type'] == "text")
			{
				$description = $info['lang'] . "_desc";
				$info['description'] = "<br /><small class=\"input\">" . $lang->$description . "</small> ";
			}
			$form_container->output_cell($lang->$info['lang'] . $info['description']);
			$form_container->output_cell($form->generate_radio_button("perms[{$perm}]", -1, $lang->inherit, array("checked" => check_radio_button($user_perms, $perm, -1))), array("class" => "align_center"));
			if($info['type'] == "yesno")
			{
				$form_container->output_cell($form->generate_radio_button("perms[{$perm}]", 1, $lang->yes, array("checked" => check_radio_button($user_perms, $perm, 1))), array("class" => "align_center"));
				$form_container->output_cell($form->generate_radio_button("perms[{$perm}]", 0, $lang->no, array("checked" => check_radio_button($user_perms, $perm, 0))), array("class" => "align_center"));
			}
			elseif($info['type'] == "text")
			{
				$form_container->output_cell($form->generate_radio_button("perms[{$perm}]", 1, $form->generate_text_box("perms[{$perm}_value]", $user_perms[$perm . "_value"], array("style" => "width: {$info['size']}px;")), array("checked" => check_radio_button($user_perms, $perm, 1))), array("class" => "align_center", "colspan" => 2));
			}
			$form_container->construct_row();
		}
		$done_groups++;
	}
}

function customuserperms_delete_forum_perms($cupid, $forum)
{
	global $db;
	
	$query = $db->simple_select('customuserperms', '*', 'cupid = \''.$db->escape_string($cupid).'\'');
	$perms = $db->fetch_array($query);
	$customperms = unserialize($perms['customperms']);
	unset($customperms['forums'][$forum]);
	$perms['customperms'] = serialize($customperms);
	$db->update_query('customuserperms', $perms, 'cupid = \''.$db->escape_string($cupid).'\'');
}
?>