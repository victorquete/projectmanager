<?php
/**
Template page for testimonials

The following variables are usable:

	$project: contains data for the project
	$datasets: contains all datasets for current selection
	$pagination: contains the pagination
	
	You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
*/
?>

<style type="text/css">
/*--- Testimonials ---*/
div.testimonials {
	clear: both;
	margin-bottom: 2em;
	padding: 0;
}
div.testimonials ul.testimonials {
	margin-left: 0;
	padding: 0;
	list-style-type: none;
}
div.testimonials ul.testimonials li {
	border-bottom: 1px solid #efefef;
	padding: 0.5em 0em;;
	margin: 0em 0.3em 1em 0;
	clear: both;
	width: 100%;
	text-align: left;
}
div.testimonials ul.testimonials li p {
	margin: 0.2em;
}
div.testimonials ul.testimonials li p.comment {
	font-style: italic;
}
div.testimonials p.cite {
	text-align: right;
}
div.testimonials img {
	float: left;
	border-radius: 200px;
}
</style>

<?php if ( isset($project->selections) && $project->selections ) do_action('projectmanager_selections'); ?>

<div class="testimonials">

<?php if ( $datasets ) : $i = 0; ?>
	<ul class="testimonials">
	<?php foreach ( $datasets AS $dataset ) : $i++; ?>
		<li>
			<div class="testimonial">
				<?php if ($project->show_image == 1 && !empty($dataset->image)) : ?>
				<img src="<?php echo $projectmanager->getFileURL('tiny.'.$dataset->image)?>" />
				<?php endif; ?>
				<p class='comment'>&ldquo;<?php echo $dataset->comment ?>&rdquo;</p>
				<p class='cite'><?php echo $dataset->name ?> - <?php echo $dataset->city ?>, <?php echo $dataset->country ?></p>
			</div>
		</li>
	<?php endforeach; ?>
	</ul>
	
	<p class='page-numbers'><?php echo $pagination ?></p>
<?php else : ?>
<p class='error'><?php _e( 'Nothing found', 'projectmanager') ?></p>
<?php endif; ?>

</div>