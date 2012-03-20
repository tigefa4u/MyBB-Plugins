<?php
/**
 * Go to Full Reply/Edit 0.1.1

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

$plugins->add_hook("global_start", "gotofull_reply");
$plugins->add_hook("showthread_start", "gotofull_reply_button");
$plugins->add_hook("editpost_end", "gotofull_edit");
$plugins->add_hook("xmlhttp", "gotofull_edit_button");

function gotofull_info()
{
	return array(
		"name" => "Go to Full Reply/Edit",
		"description" => "Allows you to quickly go from quick reply to full reply or quick edit to full edit whilst saving any text you had previously entered.",
		"website" => "http://mattrogowski.co.uk",
		"author" => "MattRogowski",
		"authorsite" => "http://mattrogowski.co.uk",
		"version" => "0.1.1",
		"compatibility" => "16*",
		"guid" => "43a080234dcf1acbb6e38dc0dd843279"
	);
}

function gotofull_activate()
{
	global $db;
	
	require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
	
	gotofull_deactivate();
	
	$templates = array();
	$templates[] = array(
		"title" => "gotofull_reply",
		"template" => "<input type=\"submit\" class=\"button\" name=\"gotofullreply\" value=\"{\$lang->gotofull_reply}\" tabindex=\"3\" />"
	);
	$templates[] = array(
		"title" => "gotofull_edit",
		"template" => "<input type=\"hidden\" name=\"pid\" value=\"{\$post['pid']}\" />
<input type=\"hidden\" name=\"message\" id=\"gotoquickedit_message\" value=\"\" />
<input type=\"submit\" name=\"gotofulledit\" value=\"{\$lang->gotofull_edit}\" onclick=\"form = \$('pid_{\$post['pid']}').childElements()[0];form.action = 'editpost.php';form.method = 'post';\$('gotoquickedit_message').value = \$('quickedit_{\$post['pid']}').value;\" />"
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
	
	find_replace_templatesets("showthread_quickreply", "#".preg_quote('<input type="submit" class="button" name="previewpost" value="{$lang->preview_post}" tabindex="3" />')."#i", '<input type="submit" class="button" name="previewpost" value="{$lang->preview_post}" tabindex="3" />{$gotofull_reply}');
	find_replace_templatesets("xmlhttp_inline_post_editor", "#".preg_quote('<input type="button" class="button" onclick="Thread.quickEditCancel({$post[\'pid\']});" value="{$lang->cancel_edit}" tabindex="1001" />')."#i", '<input type="button" class="button" onclick="Thread.quickEditCancel({$post[\'pid\']});" value="{$lang->cancel_edit}" tabindex="1001" />{$gotofull_edit}');
}

function gotofull_deactivate()
{
	global $db;
	
	require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
	
	$templates = array(
		"gotofull_reply",
		"gotofull_edit"
	);
	$templates = "'" . implode("','", $templates) . "'";
	$db->delete_query("templates", "title IN ({$templates})");
	
	find_replace_templatesets("showthread_quickreply", "#".preg_quote('{$gotofull_reply}')."#i", '', 0);
	find_replace_templatesets("xmlhttp_inline_post_editor", "#".preg_quote('{$gotofull_edit}')."#i", '', 0);
}

function gotofull_reply_button()
{
	global $lang, $templates, $gotofull_reply;
	
	$lang->load("gotofull");
	
	eval("\$gotofull_reply = \"".$templates->get('gotofull_reply')."\";");
}

function gotofull_reply()
{
	global $mybb;
	
	if(THIS_SCRIPT == "newreply.php" && $mybb->input['action'] == "do_newreply" && $mybb->input['method'] == "quickreply" && $mybb->input['gotofullreply'])
	{
		$mybb->input['action'] = "newreply";
	}
}

function gotofull_edit_button()
{
	global $mybb, $lang, $templates, $gotofull_edit;
	
	if($mybb->input['action'] == "edit_post")
	{
		$lang->load("gotofull");
		
		$post = get_post($mybb->input['pid']);
		
		eval("\$gotofull_edit = \"".$templates->get('gotofull_edit')."\";");
	}
}

function gotofull_edit()
{
	global $mybb, $message;
	
	if($mybb->input['gotofulledit'])
	{
		$message = $mybb->input['message'];
	}
}
?>