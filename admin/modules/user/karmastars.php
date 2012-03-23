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
$karmastar_count = 0;
while($karmastar = $db->fetch_array($query))
{
	$form_container->output_cell('<img src="'.$karmastar['karmastar_image'].'" alt="'.$karmastar['karmastar_name'].'" title="'.$karmastar['karmastar_name'].'" />');
	$form_container->output_cell($karmastar['karmastar_posts']);
	$form_container->output_cell($karmastar['karmastar_name']);
	$form_container->output_cell($karmastar['karmastar_image']);
	$form_container->construct_row();
	$karmastar_count++;
}
for($i = $karmastar_count; $i < 20; $i++)
{
	$form_container->output_cell('-', array("class" => "align_center"));
	$form_container->output_cell($form->generate_text_box('karmastar_posts[]', '', array('style' => 'width: 100px;')), array("class" => "align_center"));
	$form_container->output_cell($form->generate_text_box('karmastar_names[]', ''));
	$form_container->output_cell($form->generate_text_box('karmastar_images[]', ''));
	$form_container->construct_row();
}

$form_container->end();

echo $form->generate_hidden_field("cupid", $cupid);
echo $form->generate_hidden_field("fid", $fid);
echo $form->generate_hidden_field("do", "do_forums");

$buttons[] = $form->generate_submit_button($lang->submit);
$form->output_submit_wrapper($buttons);
$form->end();

$page->output_footer();
?>