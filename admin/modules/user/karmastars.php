<?php
/**
 * Karma Stars 1.0 - Admin File

 * Copyright 2012 Matthew Rogowski

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

if($mybb->request_method == 'post' && !isset($_POST['karmastar_rows_submit']))
{
	//echo '<pre>';print_r($mybb->input['karmastars']);echo '</pre>';exit;
	$errors = array();
	$errors_count = 0;
	$inserts = array();
	for($i = 0; $i < count($mybb->input['karmastars']); $i++)
	{
		if(empty($mybb->input['karmastars'][$i]['image']) && empty($mybb->input['karmastars'][$i]['posts']) && empty($mybb->input['karmastars'][$i]['name']))
		{
			continue;
		}
		$errors[$i] = array();
		if(empty($mybb->input['karmastars'][$i]['image']))
		{
			$errors[$i][] = 'image';
			$errors_count++;
		}
		elseif(!@file_exists(MYBB_ROOT.$mybb->input['karmastars'][$i]['image']))
		{
			$errors[$i][] = 'image_missing';
			$errors_count++;
		}
		if(empty($mybb->input['karmastars'][$i]['posts']))
		{
			$errors[$i][] = 'posts';
			$errors_count++;
		}
		if(empty($mybb->input['karmastars'][$i]['name']))
		{
			$errors[$i][] = 'name';
			$errors_count++;
		}
		if(empty($errors[$i]))
		{
			$inserts[] = array(
				'karmastar_image' => $db->escape_string($mybb->input['karmastars'][$i]['image']),
				'karmastar_posts' => $db->escape_string($mybb->input['karmastars'][$i]['posts']),
				'karmastar_name' => $db->escape_string($mybb->input['karmastars'][$i]['name'])
			);
		}
	}
	
	if($errors_count > 0)
	{
		//echo '<pre>';print_r($errors);echo '</pre>';
		flash_message($lang->karmastars_update_errors, 'error');
	}
	else
	{
		$db->delete_query('karmastars');
		foreach($inserts as $insert)
		{
			$db->insert_query('karmastars', $insert);
		}
		karmastars_cache();
		flash_message($lang->karmastars_update_success, 'success');
		admin_redirect('index.php?module=user-karmastars');
	}
}

$page->add_breadcrumb_item($lang->karmastars);
$page->output_header($lang->karmastars);

$form = new Form("index.php?module=user-karmastars", "post");
$form_container = new FormContainer($lang->karmastars);
$form_container->output_row_header($lang->karmastars_image, array("class" => "align_center"));
$form_container->output_row_header($lang->karmastars_posts, array("class" => "align_center"));
$form_container->output_row_header($lang->karmastars_name, array("class" => "align_center"));
$form_container->output_row_header($lang->karmastars_image_path, array("class" => "align_center"));

$query = $db->simple_select('karmastars', '*', 'karmastar_posts != \'0\'', array('order_by' => 'karmastar_posts', 'order_dir' => 'ASC'));
$karmastars = array();
while($karmastar = $db->fetch_array($query))
{
	$karmastars[] = $karmastar;
}
//echo '<pre>';print_r($karmastars);echo '</pre>';
$karmastar_rows = 20;
if(isset($_POST['karmastar_rows']) && $_POST['karmastar_rows'] > count($karmastars))
{
	$karmastar_rows = $_POST['karmastar_rows'];
}
elseif(count($karmastars) > 20)
{
	$karmastar_rows = count($karmastars);
}
$form_container->output_cell($lang->karmastars_rows.$form->generate_text_box('karmastar_rows', $karmastar_rows, array("style" => "width: 20px;")).' '.$form->generate_submit_button($lang->karmastars_update, array('name' => 'karmastar_rows_submit')), array("class" => "align_center", "colspan" => 4));
$form_container->construct_row();

for($i = 0; $i < $karmastar_rows; $i++)
{
	if(isset($_POST['karmastars'][$i]['image']))
	{
		$karmastar_image = $_POST['karmastars'][$i]['image'];
	}
	elseif(isset($karmastars[$i]['karmastar_image']))
	{
		$karmastar_image = $karmastars[$i]['karmastar_image'];
	}
	else
	{
		$karmastar_image = '';
	}
	if(isset($_POST['karmastars'][$i]['posts']))
	{
		$karmastar_posts = $_POST['karmastars'][$i]['posts'];
	}
	elseif(isset($karmastars[$i]['karmastar_posts']))
	{
		$karmastar_posts = $karmastars[$i]['karmastar_posts'];
	}
	else
	{
		$karmastar_posts = '';
	}
	if(isset($_POST['karmastars'][$i]['name']))
	{
		$karmastar_name = $_POST['karmastars'][$i]['name'];
	}
	elseif(isset($karmastars[$i]['karmastar_name']))
	{
		$karmastar_name = $karmastars[$i]['karmastar_name'];
	}
	else
	{
		$karmastar_name = '';
	}
	if(empty($karmastar_image))
	{
		$karmastar_image_image = '-';
	}
	else
	{
		$karmastar_image_image = '<img src="'.$mybb->settings['bburl'].'/'.$karmastar_image.'" alt="'.$karmastar_name.'" title="'.$karmastar_name.'" />';
	}
	$posts_error = '';
	if(isset($errors[$i]) && !empty($errors[$i]) && in_array('posts', $errors[$i]))
	{
		$posts_error = '<br /><span style="color: red;">'.$lang->karmastars_update_error_posts.'</span>';
	}
	$name_error = '';
	if(isset($errors[$i]) && !empty($errors[$i]) && in_array('name', $errors[$i]))
	{
		$name_error = '<br /><span style="color: red;">'.$lang->karmastars_update_error_name.'</span>';
	}
	$image_error = '';
	if(isset($errors[$i]) && !empty($errors[$i]) && in_array('image', $errors[$i]))
	{
		$image_error = '<br /><span style="color: red;">'.$lang->karmastars_update_error_image.'</span>';
	}
	elseif(isset($errors[$i]) && !empty($errors[$i]) && in_array('image_missing', $errors[$i]))
	{
		$image_error = '<br /><span style="color: red;">'.$lang->karmastars_update_error_image_missing.'</span>';
	}
	$form_container->output_cell($karmastar_image_image, array("class" => "align_center"));
	$form_container->output_cell($form->generate_text_box('karmastars['.$i.'][posts]', $karmastar_posts, array('style' => 'width: 100px;')).$posts_error, array("class" => "align_center"));
	$form_container->output_cell($form->generate_text_box('karmastars['.$i.'][name]', $karmastar_name).$name_error, array("class" => "align_center"));
	$form_container->output_cell($form->generate_text_box('karmastars['.$i.'][image]', $karmastar_image).$image_error, array("class" => "align_center"));
	$form_container->construct_row();
}

$form_container->end();

$buttons[] = $form->generate_submit_button($lang->karmastars_submit);
$form->output_submit_wrapper($buttons);
$form->end();

$page->output_footer();
?>