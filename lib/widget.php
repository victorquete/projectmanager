<?php
/** Widget class for the WordPress plugin ProjectManager
* 
* @author 	Kolja Schleich
* @package	ProjectManager
* @copyright 	Copyright 2008-2009
*/

class ProjectManagerWidget
{
	/**
	 * prefix of widget
	 * 
	 * @var string
	 */
	var $prefix = 'projectmanager-widget';
	
	
	/**
	 * initialize
	 *
	 * @param none
	 * @return void
	 */
	function __construct()
	{
	}
	function ProjectManagerWidget()
	{
		$this->__construct();
	}
	
	
	/**
	 * register widget
	 *
	 * @param none
	 */
	function register()
	{
		if (!function_exists('wp_register_sidebar_widget')) 
			return;
			
		$options = get_option('projectmanager_widget');

		$name = __('Project', 'projectmanager');
		$widget_ops = array('classname' => 'widget_projectmanager', 'description' => __('Display datasets from ProjectManager', 'projectmanager') );
		$control_ops = array('width' => 200, 'height' => 200, 'id_base' => $this->prefix);
		
		
		if ( !empty($options)) {
			foreach(array_keys($options) AS $widget_number) {
				wp_register_sidebar_widget($this->prefix.'-'.$widget_number, $name, array(&$this, 'display'), $widget_ops, array('number' => $widget_number));
				wp_register_widget_control($this->prefix.'-'.$widget_number, $name, array(&$this, 'control'), $control_ops, array('number' => $widget_number));
			}
		} else {
			wp_register_sidebar_widget($this->prefix.'-1', $name, array(&$this, 'display'), $widget_ops, array('number' => -1));
			wp_register_widget_control($this->prefix.'-1', $name, array(&$this, 'control'), $control_ops, array('number' => -1));
		}
	}
	
		
	/**
	 * displays widget
	 *
	 * @param array $args
	 * @param array $widget_args
	 *
	 */
	function display( $args, $widget_args = 1 )
	{
		global $wpdb, $projectmanager;

		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract($widget_args, EXTR_SKIP);

		$options = get_option( 'projectmanager_widget' );
		$options = $options[$number];

		$project_id = $options['project_id'];
		$projectmanager->initialize($project_id);
		
		$project = $projectmanager->getCurrentProject();
		
		$defaults = array(
			'before_widget' => '<li id="projectmanager-'.$number.'" class="widget '.get_class($this).'_'.__FUNCTION__.'">',
			'after_widget' => '</li>',
			'before_title' => '<h2 class="widgettitle">',
			'after_title' => '</h2>',
			'widget_number' => $number,
			'widget_title' => $project->title,
			'limit' => $options['limit'],
			'slideshow' => ( 1 == $options['slideshow']['show'] ) ? true : false,
			'slideshow_opts' => array( 'width' => $options['slideshow']['width'], 'height' => $options['slideshow']['height'], 'effect' => $options['slideshow']['fade'], 'time' => $options['slideshow']['time'], 'order' => $options['slideshow']['order']) 
		);
		$args = array_merge( $defaults, $args );
		extract( $args, EXTR_SKIP );
		
		$limit = ( 0 != $limit ) ? "LIMIT 0,".$limit : '';
		$datasets = $wpdb->get_results( "SELECT `id`, `name`, `image` FROM {$wpdb->projectmanager_dataset} WHERE `project_id` = {$project_id} ORDER BY `id` DESC ".$limit." " ); 

		if ( $slideshow ) {
		?>
		<script type='text/javascript'>
		//<![CDATA[
		jQuery(document).ready(function(){
		   jQuery('#projectmanager_slideshow_<?php echo $project_id ?>').cycle({
			   fx: '<?php echo $slideshow_opts['effect'] ?>',
			   timeout: <?php echo $slideshow_opts['time']*1000; ?>,
			   random: <?php echo $slideshow_opts['order'] ?>,
			   pause: 1
		   });
		});
		//]]>
		</script>
		<style type="text/css">
			div#projectmanager_slideshow_<?php echo $project_id ?> div {
				width: <?php echo $slideshow_opts['width'] ?>px;
				height: <?php echo $slideshow_opts['height'] ?>px;
			}
		</style>
		<?php
		}
		
		echo $before_widget;
		
		if ( !empty($widget_title) ) echo $before_title . $widget_title . $after_title;

		if ( $slideshow )
			echo '<div id="projectmanager_slideshow_'.$project_id.'" class="projectmanager_slideshow">';
		else
			echo "<ul class='projectmanager_widget'>";
				
		if ( $datasets ) {
			$url = get_permalink($options['page_id']);
			foreach ( $datasets AS $dataset ) {
				$url = add_query_arg('show', $dataset->id, $url);
				$name = ($projectmanager->hasDetails()) ? '<a href="'.$url.'"><img src="'.$projectmanager->getFileURL($dataset->image).'" alt="'.$dataset->name.'" title="'.$dataset->name.'" /></a>' : '<img src="'.$projectmanager->getFileURL($dataset->image).'" alt="'.$dataset->name.'" title="'.$dataset->name.'" />';
				
				if ( $slideshow ) {
					if ( $dataset->image != '' )
						echo "<div>".$name."</div>";
				} else
					echo "<li>".$name."</li>";
			}
		}
		if ( $slideshow )
			echo "</div>";
		else
			echo "</ul>";
		echo $after_widget;
	}
	
	
	/**
	 * widget control panel
	 *
	 * @param int|array $widget_args
	 */
	function control( $widget_args = 1 )
	{
		global $wp_registered_widgets;
		static $updated = false;

		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract($widget_args, EXTR_SKIP);

		$options = get_option( 'projectmanager_widget' );
		if(empty($options)) $options = array();
		
		if( !$updated && !empty($_POST['sidebar']) ) {
			// Tells us what sidebar to put the data in
			$sidebar = (string) $_POST['sidebar'];

			$sidebars_widgets = wp_get_sidebars_widgets();
			if ( isset($sidebars_widgets[$sidebar]) )
				$this_sidebar =& $sidebars_widgets[$sidebar];
			else
				$this_sidebar = array();

			// search unused options
			foreach ( $this_sidebar as $_widget_id ) {
				if(preg_match('/'.$this->prefix.'-([0-9]+)/i', $_widget_id, $match)){
					$widget_number = $match[1];
 
					// $_POST['widget-id'] contain current widgets set for current sidebar
					// $this_sidebar is not updated yet, so we can determine which was deleted
					if(!in_array($match[0], $_POST['widget-id']))
						unset($options[$widget_number]);
				}
			}


			foreach($_POST[$this->prefix] as $widget_number => $values){
				if(empty($values) && isset($options[$widget_number])) // user clicked cancel
					continue;
			
				$options[$widget_number] = $values;	
			}
			update_option('projectmanager_widget', $options);
			$updated = true;
		}

		/* $number - is dynamic number for multi widget, given by WP
		 * by default $number = -1 (if no widgets activated). In this case we should use %i% for inputs
		 * to allow WP generate number automatically
		 */
		if ( $number == -1 ) $number = '%i%';
 
		// now we can output control
		$opts = @$options[$number];
		
		echo '<div class="projectmanager_widget_control">';
		echo '<p><label for="'.$this->prefix.'_'.$number.'_project_id">'.__('Project', 'projectmanager').'</label>'.$this->getProjectsDropdown($opts['project_id'], $number).'</p>';
		echo '<p><label for="'.$this->prefix.'_'.$number.'_limit">'.__('Display', 'projectmanager').'</label><select style="margin-top: 0;" size="1" name="'.$this->prefix.'['.$number.'][limit]" id="'.$this->prefix.'_'.$number.'_limit">';
		$selected['show_all'] = ( $opts['limit'] == 0 ) ? " selected='selected'" : '';
		echo '<option value="0"'.$selected['show_all'].'>'.__('All','projectmanager').'</option>';
		for ( $i = 1; $i <= 10; $i++ ) {
		        $selected = ( $opts['limit'] == $i ) ? " selected='selected'" : '';
			echo '<option value="'.$i.'"'.$selected.'>'.$i.'</option>';
		}
		echo '</select></p>';
		echo '<p><label for="'.$this->prefix.'_'.$number.'_page_id">'.__('Page','projectmanager').'</label>'.wp_dropdown_pages(array('name' => $this->prefix.'['.$number.'][page_id]', 'selected' => $opts['page_id'], 'echo' => 0)).'</p>';
		echo '<fieldset class="slideshow_control"><legend>'.__('Slideshow','projectmanager').'</legend>';
		$checked = ($opts['slideshow']['show'] == 1) ? ' checked="checked"' : '';
		echo '<p><input type="checkbox" name="'.$this->prefix.'['.$number.'][slideshow][show]" id="'.$this->prefix.'_'.$number.'_slideshow_show" value="1"'.$checked.' style="margin-left: 0.5em;" />&#160;<label for="'.$this->prefix.'_'.$number.'_slideshow_show" class="right">'.__( 'Use Slideshow', 'projectmanager' ).'</label></p>';
		echo '<p><label for="'.$this->prefix.'_'.$number.'_slideshow_width]">'.__( 'Width', 'projectmanager' ).'</label><input type="text" size="3" name="'.$this->prefix.'['.$number.'][slideshow][width]" id="'.$this->prefix.'_'.$number.'_slideshow_width" value="'.$opts['slideshow']['width'].'" /> px</p>';
		echo '<p><label for="'.$this->prefix.'_'.$number.'_slideshow_height">'.__( 'Height', 'projectmanager' ).'</label><input type="text" size="3" name="'.$this->prefix.'['.$number.'][slideshow][height]" id="'.$this->prefix.'_'.$number.'_slideshow_height" value="'.$opts['slideshow']['height'].'" /> px</p>';
		echo '<p><label for="'.$this->prefix.'_'.$number.'_slideshow_time">'.__( 'Time', 'projectmanager' ).'</label><input type="text" name="'.$this->prefix.'['.$number.'][slideshow][time]" id="'.$this->prefix.'_'.$number.'_slideshow_time" size="1" value="'.$opts['slideshow']['time'].'" /> '.__( 'seconds','projectmanager').'</p>';
		echo '<p><label for="'.$this->prefix.'_'.$number.'_slideshow_fade">'.__( 'Fade Effect', 'projectmanager' ).'</label>'.$this->getSlideshowFadeEffects($opts['slideshow']['fade'], $number).'</p>';
		echo '<p><label for="'.$this->prefix.'_'.$number.'_slideshow_order">'.__('Order','projectmanager').'</label>'.$this->getSlideshowOrder($opts['slideshow']['order'], $number).'</p>';
		echo '</fieldset>';
		echo '</div>';
	}
	
	
	/**
	* dropdown list of available fade effects
	*
	* @param string $selected
	* @param int $number
	* @return string
	*/
	function getSlideshowFadeEffects( $selected, $number )
	{
		
		$effects = array(__('Blind X','projectmanager') => 'blindX', __('Blind Y','projectmanager') => 'blindY', __('Blind Z','projectmanager') => 'blindZ', __('Cover','projectmanager') => 'cover', __('Curtain X','projectmanager') => 'curtainX', __('Curtain Y','projectmanager') => 'curtain>', __('Fade','projectmanager') => 'fade', __('Fade Zoom','projectmanager') => 'fadeZoom', __('Scroll Up','projectmanager') => 'scrollUp', __('Scroll Left','projectmanager') => 'scrollLeft', __('Scroll Right','projectmanager') => 'scrollRight', __('Scroll Down','projectmanager') => 'scrollDown', __('Scroll Horizontal', 'projectmanager') => 'scrollHorz', __('Scroll Vertical', 'projectmanager') => 'scrotllVert', __('Shuffle','projectmanager') => 'shuffle', __('Slide X','projectmanager') => 'slideX', __('Slide Y','projectmanager') => 'slideY', __('Toss','projectmanager') => 'toss', __('Turn Up','projectmanager') => 'turnUp', __('Turn Down','projectmanager') => 'turnDown', __('Turn Left','projectmanager') => 'turnLeft', __('Turn Right','projectmanager') => 'turnRight', __('Uncover','projectmanager') => 'uncover', __('Wipe','projectmanager') => 'wipe', __( 'Zoom','projectmanager') => 'zoom', __('Grow X','projectmanager') => 'growX', __('Grow Y','projectmanager') => 'growY', __('Random','projectmanager') => 'all');

		$out = '<select size="1" name="'.$this->prefix.'['.$number.'][slideshow][fade]" id="'.$this->prefix.'_'.$number.'_slideshow_fade">';
		foreach ( $effects AS $name => $effect ) {
			$checked =  ( $selected == $effect ) ? " selected='selected'" : '';
			$out .= '<option value="'.$effect.'"'.$checked.'>'.$name.'</option>';
		}
		$out .= '</select>';
		return $out;
	}
	
	
	/**
	 * dropdown list of Order possibilites
	 *
	 * @param string $selected
	 * @param int $number
	 * @return string
	 */
	function getSlideshowOrder( $selected, $number )
	{
		$order = array(__('Ordered','projectmanager') => '0', __('Random','projectmanager') => '1');
		$out = '<select size="1" name="'.$this->prefix.'['.$number.'][slideshow][order]" id="'.$this->prefix.'_'.$number.'_slideshow_order">';
		foreach ( $order AS $name => $value ) {
			$checked =  ( $selected == $value ) ? " selected='selected'" : '';
			$out .= '<option value="'.$value.'"'.$checked.'>'.$name.'</option>';
		}
		$out .= '</select>';
		return $out;
	}
	
	
	/**
	 * gets all projects as dropdown menu
	 *
	 * @param int $current
	 * @param int $number
	 * @return array
	 */
	function getProjectsDropdown($current, $number)
	{
		global $projectmanager;
		$projects = $projectmanager->getProjects();
		
		$out = "<select size='1' name='".$this->prefix."[".$number."][project_id]' id='".$this->prefix."_".$number."_project_id'>";
		foreach ( $projects AS $project ) {
			$selected = ( $current == $project->id ) ? " selected='selected'" : '';
			$out .= "<option value='".$project->id."'".$selected.">".$project->title."</option>";
		}
		$out .= "</select>";
		return $out;
	}
}
?>
