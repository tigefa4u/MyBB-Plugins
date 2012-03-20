<?php
/**
 * Social Sites 0.2.2 - Admin File

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

$page->add_breadcrumb_item($lang->socialsites, "index.php?module=config-socialsites");

if($mybb->input['action'] == "do_add" || $mybb->input['action'] == "do_edit")
{
	if(!verify_post_check($mybb->input['my_post_key']))
	{
		flash_message($lang->invalid_post_verify_key2, 'error');
		admin_redirect("index.php?module=config-socialsites");
	}
	
	$add = false;
	$edit = false;
	if($mybb->input['action'] == "do_add")
	{
		$add = true;
		$redirect_end = "&amp;action=add";
	}
	if($mybb->input['action'] == "do_edit")
	{
		$edit = true;
		$ssid = intval($mybb->input['ssid']);
		$query = $db->simple_select("socialsites", "*", "ssid = '{$ssid}'");
		$site = $db->fetch_array($query);
		$redirect_end = "&amp;action=edit&amp;ssid={$ssid}";
	}
	
	$name = trim($mybb->input['name']);
	if(empty($name))
	{
		flash_message($lang->socialsites_error_name, 'error');
		admin_redirect("index.php?module=config-socialsites{$redirect_end}");
	}
	$query = $db->simple_select("socialsites", "ssid", "LOWER(name) = '" . $db->escape_string(strtolower($name)) . "'");
	if($db->num_rows($query) != 0 && !($edit && $name == $site['name']))
	{
		flash_message($lang->socialsites_error_name_exists, 'error');
		admin_redirect("index.php?module=config-socialsites{$redirect_end}");
	}
	$nicename = str_replace(" ", "_", strtolower($name));
	
	$url = $mybb->input['url'];
	if(!strlen(trim($url)))
	{
		flash_message($lang->socialsites_error_url, 'error');
		admin_redirect("index.php?module=config-socialsites{$redirect_end}");
	}
	if(!strpos($url, "{username}"))
	{
		if(substr($url, -1) != "/")
		{
			$url .= "/";
		}
		$url .= "{username}";
	}
	
	if($add && (empty($_FILES['image_large']['name']) || empty($_FILES['image_small']['name'])))
	{
		flash_message($lang->socialsites_error_noimage, 'error');
		admin_redirect("index.php?module=config-socialsites{$redirect_end}");
	}
	
	foreach(array("image_large", "image_small") as $size)
	{
		if($_FILES[$size]['name'])
		{
			$icon = socialicons_upload_icon($size);
			if($icon['error'])
			{
				flash_message($icon['error'], 'error');
				admin_redirect("index.php?module=config-socialsites{$redirect_end}");
			}
			else
			{
				$icons[$size] = $icon['filename'];
			}
		}
	}
	
	if($edit)
	{
		if(empty($icons['image_large']))
		{
			$icons['image_large'] = $site['image_large'];
		}
		if(empty($icons['image_small']))
		{
			$icons['image_small'] = $site['image_small'];
		}
	}
	
	$insert_update_array = array(
		"name" => $db->escape_string($name),
		"nicename" => $db->escape_string($nicename),
		"url" => $db->escape_string($url),
		"image_large" => $db->escape_string($icons['image_large']),
		"image_small" => $db->escape_string($icons['image_small'])
	);
	
	if($edit)
	{
		$db->update_query("socialsites", $insert_update_array, "ssid = '{$ssid}'");
		
		if($nicename != $site['nicename'])
		{
			$query = $db->simple_select("users", "uid, socialsites", "socialsites != ''");
			while($user = $db->fetch_array($query))
			{
				$socialsites = unserialize($user['socialsites']);
				if(array_key_exists($site['nicename'], $socialsites))
				{
					$val = $socialsites[$site['nicename']];
					unset($socialsites[$site['nicename']]);
					$socialsites[$nicename] = $val;
				}
				ksort($socialsites);
				$socialsites = serialize($socialsites);
				
				$update = array(
					"socialsites" => $db->escape_string($socialsites)
				);
				$db->update_query("users", $update, "uid = '" . intval($user['uid']) . "'");
			}
		}
		
		$redirect = $lang->socialsites_success_edit;
	}
	else
	{
		$db->insert_query("socialsites", $insert_update_array);
		$redirect = $lang->socialsites_success_add;
	}
	
	socialsites_cache_sites();
	
	flash_message($redirect, 'success');
	admin_redirect("index.php?module=config-socialsites");
}
elseif($mybb->input['action'] == "add" || $mybb->input['action'] == "edit")
{
	$add = false;
	$edit = false;
	if($mybb->input['action'] == "add")
	{
		$add = true;
		$site = array();
		$page->add_breadcrumb_item($lang->socialsites_add, "index.php?module=config-socialsites&action=add");
	}
	if($mybb->input['action'] == "edit")
	{
		$edit = true;
		$ssid = intval($mybb->input['ssid']);
		$query = $db->simple_select("socialsites", "*", "ssid = '{$ssid}'");
		$page->add_breadcrumb_item($lang->socialsites_edit, "index.php?module=config-socialsites&action=edit&ssid={$ssid}");
		$site = $db->fetch_array($query);
	}
	
	$page->output_header($lang->socialsites);
	
	$sub_tabs = array();
	$sub_tabs['socialsites'] = array(
		'title' => $lang->socialsites,
		'link' => "index.php?module=config-socialsites",
		'description' => $lang->socialsites_nav
	);
	if($edit)
	{
		$sub_tabs['socialsites_edit'] = array(
			'title' => $lang->socialsites_edit,
			'link' => "index.php?module=config-socialsites&amp;action=edit&amp;ssid={$ssid}",
			'description' => $lang->socialsites_edit_nav
		);
		
		$page->output_nav_tabs($sub_tabs, "socialsites_edit");
	}
	else
	{
		$sub_tabs['socialsites_add'] = array(
			'title' => $lang->socialsites_add,
			'link' => "index.php?module=config-socialsites&amp;action=add",
			'description' => $lang->socialsites_add_nav
		);
		
		$page->output_nav_tabs($sub_tabs, "socialsites_add");
	}
	
	if($edit)
	{
		$form = new Form("index.php?module=config-socialsites&amp;action=do_edit", "post", "", 1);
		$form_container = new FormContainer($lang->socialsites_edit);
	}
	else
	{
		$form = new Form("index.php?module=config-socialsites&amp;action=do_add", "post", "", 1);
		$form_container = new FormContainer($lang->socialsites_add);
	}
	
	$table = new Table;
	
	$form_container->output_row($lang->socialsites_name . " <em>*</em>", $lang->socialsites_name_desc, $form->generate_text_box("name", $site['name']));
	$form_container->output_row($lang->socialsites_url . " <em>*</em>", $lang->socialsites_url_desc, $form->generate_text_box("url", $site['url']));
	$form_container->output_row($lang->socialsites_image_large . " <em>*</em>", $lang->socialsites_image_large_desc, $form->generate_file_upload_box("image_large"));
	$form_container->output_row($lang->socialsites_image_small . " <em>*</em>", $lang->socialsites_image_small_desc, $form->generate_file_upload_box("image_small"));
	
	$form_container->end();
	
	if($edit)
	{
		echo $form->generate_hidden_field("ssid", $ssid);
	}
	
	$buttons[] = $form->generate_submit_button($lang->submit);
	$form->output_submit_wrapper($buttons);
	$form->end();
	
	$page->output_footer();
}
elseif($mybb->input['action'] == "do_delete")
{
	if($mybb->input['no'])
	{
		admin_redirect("index.php?module=config-socialsites");
	}
	else
	{
		if(!verify_post_check($mybb->input['my_post_key']))
		{
			flash_message($lang->invalid_post_verify_key2, 'error');
			admin_redirect("index.php?module=config-socialsites");
		}
		
		$ssid = intval($mybb->input['ssid']);
		$query = $db->simple_select("socialsites", "*", "ssid = '{$ssid}'");
		$site = $db->fetch_array($query);
		
		if($site['image_large'])
		{
			if(file_exists(MYBB_ROOT . "images/socialicons/" . $site['image_large']))
			{
				@unlink(MYBB_ROOT . "images/socialicons/" . $site['image_large']);
			}
		}
		if($site['image_small'])
		{
			if(file_exists(MYBB_ROOT . "images/socialicons/" . $site['image_small']))
			{
				@unlink(MYBB_ROOT . "images/socialicons/" . $site['image_small']);
			}
		}
		
		$query = $db->simple_select("users", "uid, socialsites", "socialsites != ''");
		while($user = $db->fetch_array($query))
		{
			$socialsites = unserialize($user['socialsites']);
			if(array_key_exists($site['nicename'], $socialsites))
			{
				unset($socialsites[$site['nicename']]);
			}
			$socialsites = serialize($socialsites);
			
			$update = array(
				"socialsites" => $db->escape_string($socialsites)
			);
			$db->update_query("users", $update, "uid = '" . intval($user['uid']) . "'");
		}
		
		$db->delete_query("socialsites", "ssid = '{$ssid}'");
		
		socialsites_cache_sites();
		
		flash_message($lang->socialsites_success_delete, 'success');
		admin_redirect("index.php?module=config-socialsites");
	}
}
elseif($mybb->input['action'] == "delete")
{
	$page->output_confirm_action("index.php?module=config-socialsites&action=do_delete&ssid={$mybb->input['ssid']}&my_post_key={$mybb->post_code}", $lang->socialsites_site_delete);
}
else
{
	$page->output_header($lang->socialsites);
	
	$sub_tabs = array();
	$sub_tabs['socialsites'] = array(
		'title' => $lang->socialsites,
		'link' => "index.php?module=config-socialsites",
		'description' => $lang->socialsites_nav
	);
	$sub_tabs['socialsites_add'] = array(
		'title' => $lang->socialsites_add,
		'link' => "index.php?module=config-socialsites&amp;action=add",
		'description' => $lang->socialsites_add_nav
	);
	
	$page->output_nav_tabs($sub_tabs, "socialsites");
	
	$query = $db->simple_select("socialsites", "*", "", array("order_by" => "nicename", "order_dir" => "ASC"));
	$sites = array();
	while($socialsite = $db->fetch_array($query))
	{
		$sites[$socialsite['nicename']] = $socialsite;
	}
	
	if(!empty($sites))
	{
		$table = new Table;
		
		$table->construct_header($lang->socialsites_icon, array("width" => "10%", 'class' => 'align_center'));
		$table->construct_header($lang->socialsites_site, array("width" => "20%", 'class' => 'align_center'));
		$table->construct_header($lang->socialsites_details, array("width" => "40%", 'class' => 'align_center'));
		$table->construct_header($lang->controls, array("width" => "30%", "colspan" => 2, 'class' => 'align_center'));
		
		foreach($sites as $site)
		{
			$table->construct_cell("<img src=\"{$mybb->settings['bburl']}/images/socialicons/{$site['image_large']}\" alt=\"{$site['name']}\" title=\"{$site['title']}\" />", array('class' => 'align_center'));
			$table->construct_cell($site['name'], array('class' => 'align_center'));
			$table->construct_cell($site['url'], array('class' => 'align_center'));
			$table->construct_cell("<a href=\"index.php?module=config-socialsites&amp;action=edit&amp;ssid={$site['ssid']}&amp;my_post_key={$mybb->post_code}\">{$lang->edit}</a>", array('class' => 'align_center', 'width' => '15%'));
			$table->construct_cell("<a href=\"index.php?module=config-socialsites&amp;action=delete&amp;ssid={$site['ssid']}\">{$lang->delete}</a>", array('class' => 'align_center', 'width' => '15%'));
			$table->construct_row();
		}
		
		$table->output($lang->socialsites);
	}
	
	$page->output_footer();
}

function socialicons_upload_icon($size)
{
	global $db, $mybb, $lang;
	
	$icon = $_FILES[$size];
	$upload_path = MYBB_ROOT . "images/socialicons/";
	
	if(!is_uploaded_file($icon['tmp_name']))
	{
		$ret['error'] = $lang->socialsites_error_icon_upload_failed . "1";
		return $ret;
	}
	
	$ext = get_extension(my_strtolower($icon['name']));
	if(!preg_match("#^(gif|jpg|jpeg|jpe|bmp|png)$#i", $ext)) 
	{
		$ret['error'] = $lang->socialsites_error_icon_invalid_type;
		return $ret;
	}
	
	$filename = preg_replace("#/$#", "", $icon['name']);
	$moved = @move_uploaded_file($icon['tmp_name'], $upload_path . $filename);
	if(!$moved)
	{
		@unlink($upload_path . $filename);
		$ret['error'] = $lang->socialsites_error_icon_upload_failed . "2";
		return $ret;
	}
	@my_chmod($upload_path . $filename, '0644');
	
	if(!file_exists($upload_path . $filename))
	{
		@unlink($upload_path . $filename);
		$ret['error'] = $lang->socialsites_error_icon_upload_failed . "3";
		return $ret;
	}
	
	$img_dimensions = @getimagesize($upload_path . $filename);
	if(!is_array($img_dimensions))
	{
		@unlink($upload_path . $filename);
		$ret['error'] = $lang->socialsites_error_icon_upload_failed . "4";
		return $ret;
	}
	
	switch(my_strtolower($icon['type']))
	{
		case "image/gif":
			$img_type =  1;
			break;
		case "image/jpeg":
		case "image/x-jpg":
		case "image/x-jpeg":
		case "image/pjpeg":
		case "image/jpg":
			$img_type = 2;
			break;
		case "image/png":
		case "image/x-png":
			$img_type = 3;
			break;
		default:
			$img_type = 0;
	}
	
	if($img_dimensions[2] != $img_type || $img_type == 0)
	{
		@unlink($upload_path . $filename);
		$ret['error'] = $lang->socialicons_error_icon_upload_failed . "5";
		return $ret;
	}
	
	$ret['filename'] = $filename;
	return $ret;
}
?>