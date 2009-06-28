<script type="text/javascript">
document.forms[0].encoding = "multipart/form-data";
</script>

<table class="form-table">
	<?php if ( 1 == $project->show_image ) : ?>
	<tr valign="top">
		<th scope="row"><label for="projectmanager_image"><?php _e( 'Image', 'projectmanager' ) ?></label></th>
		<td>
			<?php if ( '' != $img_filename ) : ?>
				<img src="<?php echo $projectmanager->getFileURL('tiny.'.$img_filename)?>" class="alignright" />
			<?php endif; ?>
			<input type="file" name="projectmanager_image[<?php echo $dataset_id ?>]" id="projectmanager_image" size="45"/><p><?php _e( 'Supported file types', 'projectmanager' ) ?>: <?php echo implode( ',',$projectmanager->getSupportedImageTypes() ); ?></p>
			<?php if ( '' != $img_filename ) : ?>
				<p class="alignleft"><label for="overwrite_image"><?php _e( 'Overwrite existing image', 'projectmanager' ) ?></label><input type="checkbox" id="overwrite_image" name="overwrite_image[<?php echo $dataset_id ?>]" value="1" style="margin-left: 1em;" /></p>
				<input type="hidden" name="image_file[<?php echo $dataset_id ?>]" value="<?php echo $img_filename ?>" />
				<p class="alignright"><label for="del_old_image"><?php _e( 'Delete current image', 'projectmanager' ) ?></label><input type="checkbox" id="del_old_image" name="del_old_image[<?php echo $dataset_id ?>]" value="1" style="margin-left: 1em;" /></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php endif; ?>
	<?php if ( $form_fields = $projectmanager->getFormFields() ) : ?>
		<?php foreach ( $form_fields AS $form_field ) : ?>
		
		<?php if ( !$is_profile_page || ( $is_profile_page && $form_field->show_in_profile == 1 && !is_array($projectmanager->getFormFieldTypes($form_field->type)) ) ) : ?>
		<tr valign="top">
			<th scope="row"><label for="form_field_<?php echo $form_field->id ?>"><?php echo $form_field->label ?></label></th>
			<td>
				<?php if ( 'text' == $form_field->type || 'email' == $form_field->type || 'uri' == $form_field->type || 'numeric' == $form_field->type || 'currency' == $form_field->type ) : ?>
				<input type="text" name="form_field[<?php echo $dataset_id ?>][<?php echo $form_field->id ?>]" id="form_field_<?php echo $form_field->id ?>" value="<?php echo $meta_data[$form_field->id] ?>" size="45" />
				<?php elseif ( 'textfield' == $form_field->type ) : ?>
				<div style="width: 60%;">
					<textarea class="projectmanager_mceEditor" name="form_field[<?php echo $dataset_id ?>][<?php echo $form_field->id ?>]" id="form_field_<?php echo $form_field->id ?>" cols="70" rows="8"><?php echo $meta_data[$form_field->id] ?></textarea>
				</div>
				<?php elseif ( 'date' == $form_field->type ) : ?>
				<select size="1" name="form_field[<?php echo $dataset_id ?>][<?php echo $form_field->id ?>][day]">
					<option value="">Tag</option>
					<option value="">&#160;</option>
					<?php for ( $day = 1; $day <= 31; $day++ ) : ?>
						<option value="<?php echo $day ?>"<?php if ( $day == substr($meta_data[$form_field->id], 8, 2) ) echo ' selected="selected"'; ?>><?php echo $day ?></option>
					<?php endfor; ?>
				</select>
				<select size="1" name="form_field[<?php echo $dataset_id ?>][<?php echo $form_field->id ?>][month]">
					<option value="">Monat</option>
					<option value="">&#160;</option>
					<?php foreach ( $projectmanager->getMonths() AS $key => $month ) : ?>
						<option value="<?php echo $key ?>"<?php if ( $key == substr($meta_data[$form_field->id], 5, 2) ) echo ' selected="selected"'; ?>><?php echo $month ?></option>
					<?php endforeach; ?>
				</select>
				<select size="1" name="form_field[<?php echo $dataset_id ?>][<?php echo $form_field->id ?>][year]">
					<option value="0000">Jahr</option>
					<option value="0000">&#160;</option>
					<?php for ( $year = date('Y')-100; $year <= date('Y')+10; $year++ ) : ?>
						<option value="<?php echo $year ?>"<?php if ( $year == substr($meta_data[$form_field->id], 0, 4) ) echo ' selected="selected"' ?>><?php echo $year ?></option>
					<?php endfor; ?>
				</select>
				<?php elseif ( 'file' == $form_field->type || 'image' == $form_field->type || 'video' == $form_fiel->type ) : ?>
					<input type="file" name="form_field[<?php echo $dataset_id ?>][<?php echo $form_field->id ?>]" id="form_field_<?php echo $form_field->id ?>" size="40" />
					<input type="hidden" name="form_field[<?php echo $dataset_id ?>][<?php echo $form_field->id ?>][current]" value="<?php echo $meta_data[$form_field->id] ?>" />
					<?php if (!empty($meta_data[$form_field->id])) : ?>
					<p>
						<?php if ( 'file' == $form_field->type ) : ?>
							<?php _e( 'Current File', 'projectmanager' ) ?>: <a href="<?php echo $projectmanager->getFileURL($meta_data[$form_field->id]) ?>"><?php echo $meta_data[$form_field->id] ?></a>&#160;
						<?php elseif( 'image' == $form_field->type ) : ?>
							<img src="<?php echo $projectmanager->getFileURL('tiny.'.$meta_data[$form_field->id])?>" class="alignright" style="margin-top: -1em;" />
						<?php elseif ( 'video' == $form_field->type ) : ?>
							<embed src="<?php $projectmanager->getFileURL($meta_data[$form_field->id]) ?>" width="150" class="alignright" style="margin-top: -1em;" />
						<?php endif; ?>
						<input type="checkbox" name="form_field[<?php echo $dataset_id ?>][<?php echo $form_field->id ?>][del]" value="1" id="delete_file_<?php echo $form_field->id ?>">&#160;<label for="delete_file_<?php echo $form_field->id ?>"><strong><?php _e( 'Delete File', 'projectmanager' ) ?></strong></label>&#160;
						<input type="checkbox" name="form_field[<?php echo $dataset_id ?>][<?php echo $form_field->id ?>][overwrite]" value="1" id="overwrite_file_<?php echo $form_field->id ?>">&#160;<label for="overwrite_file_<?php echo $form_field->id ?>"><strong><?php _e( 'Overwrite File', 'projectmanager' ) ?></strong></label>
					</p>
					<?php endif; ?>
				<?php elseif ( 'select' == $form_field->type ) : $projectmanager->printFormFieldDropDown($form_field->id, $meta_data[$form_field->id], $dataset_id, "form_field[".$dataset_id."][".$form_field->id."]"); ?>
				<?php elseif ( 'checkbox' == $form_field->type ) : $projectmanager->printFormFieldCheckboxList($form_field->id, $meta_data[$form_field->id], $dataset_id, "form_field[".$dataset_id."][".$form_field->id."][]"); ?>
				<?php elseif ( 'radio' == $form_field->type ) : $projectmanager->printFormFieldRadioList($form_field->id, $meta_data[$form_field->id], $dataset_id, "form_field[".$dataset_id."][".$form_field->id."]"); ?>
				<?php elseif ( !empty($form_field->type) && is_array($projectmanager->getFormFieldTypes($form_field->type)) ) : ?>
					<input type="hidden" name="form_field[<?php echo $dataset_id ?>][<?php echo $form_field->id ?>]" id="form_field_<?php echo $form_field->id ?>" value="" />
					<p><?php _e( 'This Field has a callback attached which will get the data from somewhere else!', 'projectmanager' ) ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php endif; ?>
		
		<?php endforeach; ?>
	<?php endif; ?>
</table>
