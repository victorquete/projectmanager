<?php
/**
Template page for the searchform

The following variables are usable:
	
	$search: holds the search request
	
	You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
*/
?>
<form class='search-form alignright' action='' method='post'>
<div>
	<input type='text' class='search-input' name='search_string_<?php echo $project_id ?>' value='<?php echo $search ?>' />
	<input type='hidden' name='form_field' value='0' />
	<?php $projectmanager->printSearchFormHiddenFields() ?>
	
	<input type="hidden" name="project_id" value="<?php echo $project_id ?>" />
	<input type='submit' value='<?php _e('Search', 'projectmanager') ?> &raquo;' class='button' />
</div>
</form>
