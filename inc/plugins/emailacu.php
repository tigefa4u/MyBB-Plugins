<?php
/**
 * Email Admin-Created User 0.1

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

$plugins->add_hook("admin_user_users_add_commit", "emailacu");

function emailacu_info()
{
	return array(
		"name" => "Email Admin-Created User",
		"description" => "If you create a user in the Admin CP, this will email them their account details so they can login.",
		"website" => "http://mattrogowski.co.uk",
		"author" => "MattRogowski",
		"authorsite" => "http://mattrogowski.co.uk",
		"version" => "0.1",
		"compatibility" => "16*",
		"guid" => "a1988a3c204338b20a8d243552a1e284"
	);
}

function emailacu_activate()
{
	
}

function emailacu_deactivate()
{
	
}

function emailacu()
{
	global $mybb, $lang, $user_info;
	
	$lang->load("user_emailacu");
	
	$subject = $lang->sprintf($lang->emailacu_subject, $mybb->settings['bbname']);
	$message = $lang->sprintf($lang->emailacu_message, $mybb->settings['bbname'], $user_info['username'], $user_info['password'], $mybb->settings['bburl']);
	
	my_mail($user_info['email'], $subject, $message);
}
?>