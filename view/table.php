<?php
/**
Template page for dataset list

The following variables are usable:

	$title: holds a subtitle (h3) of the page
	$datasets: contains all datasets for current selection
	$pagination: contains the pagination
	
	You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
*/
?>
<?php echo $title ?>

<?php if ( isset($_GET['show']) ) : ?>
	<?php do_action('projectmanager_dataset', array('id' => $_GET['show'], 'echo' => 1), true) ?>
<?php else: ?>

<?php if ( $project['tablenav'] ) do_action('projectmanager_tablenav'); ?>

<?php if ( $datasets ) : ?>

<table class='projectmanager'>
<tr>
	<th scope='col'><?php _e( 'Name', 'projectmanager' ) ?></th>
	<?php $projectmanager->printTableHeader(); ?>
</tr>

<?php foreach ( $datasets AS $dataset ) : ?>
	<tr class='<?php echo $dataset->class ?>'>
		<td><?php echo $dataset->nameURL ?></td>
		<?php $projectmanager->printDatasetMetaData( $dataset, 'td' ); ?>
	</tr>
<?php endforeach ; ?>

</table>

<p class='page-numbers'><?php echo $pagination ?></p>

<?php else : ?>
<p class='error'><?php _e( 'Nothing found', 'projectmanager') ?></p>
<?php endif; ?>

<?php endif; ?>
