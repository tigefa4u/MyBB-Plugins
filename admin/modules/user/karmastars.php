<?php
/**
 * Karma Stars 0.1 - Admin File

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

$page->add_breadcrumb_item($lang->karmastars);
$page->output_header($lang->karmastars);

$form = new Form("index.php?module=user-karmastars", "post");
$form_container = new FormContainer($lang->karmastars);
$form_container->output_row_header($lang->karmastars_image, array("class" => "align_center"));
$form_container->output_row_header($lang->karmastars_posts, array("class" => "align_center"));
$form_container->output_row_header($lang->karmastars_name);
$form_container->output_row_header($lang->karmastars_image_path);

$query = $db->simple_select('karmastars', '*', '', array('order_by' => 'karmastar_posts', 'order_dir' => 'ASC'));
$karmastars = array();
while($karmastar = $db->fetch_array($query))
{
	$karmastars[] = $karmastar;
}
for($i = 0; $i < 20; $i++)
{
	if(isset($_POST['karmastar_images'][$i]))
	{
		$karmastar_image = $_POST['karmastar_images'][$i];
	}
	elseif(isset($karmastars[$i]['karmastar_image']))
	{
		$karmastar_image = $karmastars[$i]['karmastar_image'];
	}
	else
	{
		$karmastar_image = '';
	}
	if(isset($_POST['karmastar_posts'][$i]))
	{
		$karmastar_posts = $_POST['karmastar_posts'][$i];
	}
	elseif(isset($karmastars[$i]['karmastar_posts']))
	{
		$karmastar_posts = $karmastars[$i]['karmastar_posts'];
	}
	else
	{
		$karmastar_posts = '';
	}
	if(isset($_POST['karmastar_names'][$i]))
	{
		$karmastar_name = $_POST['karmastar_names'][$i];
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
		$karmastar_image_image = '<img src="'.$karmastar_image.'" alt="'.$karmastar_name.'" title="'.$karmastar_name.'" />';
	}
	$form_container->output_cell($karmastar_image_image, array("class" => "align_center"));
	$form_container->output_cell($form->generate_text_box('karmastar_posts[]', $karmastar_posts, array('style' => 'width: 100px;')), array("class" => "align_center"));
	$form_container->output_cell($form->generate_text_box('karmastar_names[]', $karmastar_name));
	$form_container->output_cell($form->generate_text_box('karmastar_images[]', $karmastar_image));
	$form_container->construct_row();
}

$form_container->end();

$buttons[] = $form->generate_submit_button($lang->submit);
$form->output_submit_wrapper($buttons);
$form->end();

$page->output_footer();
?>