<?php
/**
 * Core class for the WordPress plugin ProjectManager
 * 
 * @author 	Kolja Schleich
 * @package	ProjectManager
 * @copyright 	Copyright 2008-2009
*/
class ProjectManager extends ProjectManagerLoader
{
	/**
	 * ID of current project
	 *
	 * @var int
	 */
	var $project_id;
	
	
	/**
	 * current project object
	 *
	 * @var object
	 */
	var $project;
	
	
	/**
	 * selected category
	 *
	 * @var int
	 */
	var $cat_id;
		
		
	/**
	 * number of dataset per page
	 *
	 * @var int
	 */
	var $per_page;
		

	/**
	 * error handling
	 *
	 * @param boolean
	 */
	var $error = false;
	
	
	/**
	 * error message
	 *
	 * @param string
	 */
	var $message = '';
	
	
	/**
	 * order of datasets
	 *
	 * @var string
	 */
	var $order = 'ASC';
	
	
	/**
	 * datafield datasets are ordered by
	 *
	 * @var string
	 */
	var $orderby = 'name';
	
	
	/**
	 * Initialize project settings
	 *
	 * @param int $project_id ID of selected project. false if none is selected
	 * @return void
	 */
	function __construct( $project_id )
	{
		global $wpdb;
			
		//Save selected group. NULL if none is selected
		$this->setCatID();

		if ( $project_id ) $this->initialize($project_id);

		$this->admin = parent::getAdminPanel();
		return;
	}
	/**
	 *  Wrapper function to sustain downward compatibility to PHP 4.
	 *
	 * Wrapper function which calls constructor of class
	 *
	 * @param int $project_id
	 * @return none
	 */
	function ProjectManager( $project_id )
	{
		$this->__construct( $project_id );
	}
	
	
	/**
	 * Initialize project settings
	 *
	 * @param int $project_id
	 * @return void
	 */
	function initialize( $project_id )
	{
		$this->setProjectID($project_id);
		$this->project = $this->getProject($this->getProjectID());

		$this->setPerPage();

		$this->num_items = $this->getNumDatasets($this->getProjectID());
		$this->num_max_pages = ( 0 == $this->getPerPage() || $this->isSearch() ) ? 1 : ceil( $this->num_items/$this->getPerPage() );
	}
	
	
	/**
	 * retrieve current page
	 *
	 * @param none
	 * @return int
	 */
	function getCurrentPage()
	{
		global $wp;
		if (isset($wp->query_vars['paged']))
			$this->current_page = max(1, intval($wp->query_vars['paged']));
		elseif (isset($_GET['paged']))
			$this->current_page = (int)$_GET['paged'];
		else
			$this->current_page = 1;

		return $this->current_page;
	}
	
	
	/**
	 * retrieve number of pages
	 *
	 * @param none
	 * @return int
	 */
	function getNumPages()
	{
		return $this->num_max_pages;
	}

	
	/**
	 * sets number of objects per page
	 *
	 * @param int|false
	 * @return void
	 */
	function setPerPage( $per_page = false )
	{
		if ( $per_page )
			$this->per_page = $per_page;
		else
			$this->per_page = ( isset($this->project->per_page) && !empty($this->project->per_page) ) ? $this->project->per_page : 15;
	}


	/**
	 * gets object limit per page
	 *
	 * @param none
	 * @return int
	 */
	function getPerPage()
	{
		return $this->per_page;
	}
		
		
	/**
	 * save current project ID
	 *
	 * @param int
	 * @return void
	 */
	function setProjectID( $id )
	{
		$this->project_id = $id;
	}


	/**
	 * gets project ID
	 *
	 * @param none
	 * @return int
	 */
	function getProjectID()
	{
		return $this->project_id;
	}
	
	
	/**
	 * gets current project object
	 *
	 * @param none
	 * @return object
	 */
	function getCurrentProject()
	{
		return $this->project;
	}


	/**
	 * gets project title
	 *
	 * @param none
	 * @return string
	 */
	function getProjectTitle( )
	{
		return $this->project->title;
	}


	/**
 	 * sets current category
	 *
	 * @param int $cat_id
	 * @return void
	 */
	function setCatID( $cat_id = false )
	{
		if ( $cat_id ) {
			$this->cat_id = $cat_id;
		} elseif ( isset($_POST['cat_id']) ) {
			$this->cat_id = (int)$_POST['cat_id'];
			$this->query_args['cat_id'] = $this->cat_id;
		} elseif ( isset($_GET['cat_id']) ) {
			$this->cat_id = (int)$_GET['cat_id'];
		} else {
			$this->cat_id = null;
		}
			
		return;
	}
	
	
	/**
	 * gets current category
	 * 
	 * @param none
	 * @return int
	 */
	function getCatID()
	{
		return $this->cat_id;
	}
		
	
	/**
	 *  gets category title
	 *
	 * @param int $cat_id
	 * @return string
	 */
	function getCatTitle( $cat_id = false )
	{
		if ( !$cat_id ) $cat_id = $this->getCatID();
		$c = get_category($cat_id);
		return $c->name;
	}
	
	
	/**
	 * check if category is selected
	 * 
	 * @param none
	 * @return boolean
	 */
	function isCategory()
	{
		if ( null != $this->getCatID() )
			return true;
		
		return false;
	}


	/**
	 * display pagination
	 *
	 * @param none
	 * @return string
	 */
	function getPageLinks()
	{
		$page_links = paginate_links( array(
			'base' => add_query_arg( 'paged', '%#%' ),
			'format' => '',
			'prev_text' => '&#9668;',
			'next_text' => '&#9658;',
			'total' => $this->getNumPages(),
			'current' => $this->getCurrentPage(),
			'add_args' => $this->query_args
		));
		return $page_links;
	}
	

	/**
	 * get dataset order
	 *
	 * @param boolean $orderby
	 * @param boolean $order
	 * @return boolean|int false if ordered by name or ID otherwise formfield ID to order by
	 */
	function setDatasetOrder( $orderby = false, $order = false )
	{	
		$project = $this->getCurrentProject();

		$formfield_id = $this->override_order = false;
		// Selection in Admin Panel
		if ( isset($_POST['orderby']) && isset($_POST['order']) && !isset($_POST['doaction']) ) {
			$orderby = explode('_', $_POST['orderby']);
			$this->orderby = ( $_POST['orderby'] != '' ) ? $_POST['orderby'] : 'name';
			$formfield_id = $orderby[1];
			$this->order = ( $_POST['order'] != '' ) ? $_POST['order'] : 'ASC';

			$this->query_args['order'] = $this->order;
			$this->query_args['orderby'] = $this->orderby;

			$this->override_order = true;
		}
		// Selection in Frontend
		elseif ( isset($_GET['orderby']) && isset($_GET['order']) ) {
			$orderby = explode('_', $_GET['orderby']);
			$this->orderby = ( $_GET['orderby'] != '' ) ? $_GET['orderby'] : 'name';
			$formfield_id = $orderby[1];
			$this->order = ( $_GET['order'] != '' ) ? $_GET['order'] : 'ASC';
			
			$this->override_order = true;
		}
		// Shortcode Attributes
		elseif ( $orderby || $order ) {
			if ( $orderby ) {
				$tmp = explode("-",$orderby);
				$this->orderby = $tmp[0];
				$formfield_id = $tmp[1];
			}
			if ( $order ) $this->order = $order;

			$this->override_order = true;
		}
		// Project Settings
		elseif ( isset($project->dataset_orderby) && $project->dataset_orderby != 'formfields' && !empty($project->dataset_orderby) ) {
			$this->orderby = $project->dataset_orderby;
			$this->order = (isset($project->dataset_order) && !empty($project->dataset_order)) ? $project->dataset_order : 'ASC';
		}
		// Default
		else {
			$this->orderby = 'name';
			$this->order = 'ASC';
		}
		return $formfield_id;
	}
	

	/**
	 * get SQL order
	 *
	 * @param none
	 * @return string
	 */
	function getDatasetOrder()
	{
		return "`{$this->orderby}` {$this->order}";
	}


	/**
	 * returns array of form field types
	 *
	 * The Form Field Types can be extended via Wordpress filter <em>projectmanager_formfields</em>
	 *
	 * @param mixed $index
	 * @return array
	 */
	function getFormFieldTypes($index = false)
	{
		$form_field_types = array( 'text' => __('Text', 'projectmanager'), 'textfield' => __('Textfield', 'projectmanager'), 'email' => __('E-Mail', 'projectmanager'), 'date' => __('Date', 'projectmanager'), 'uri' => __('URL', 'projectmanager'), 'image' => __( 'Image', 'projectmanager' ), 'select' => __('Selection', 'projectmanager'), 'checkbox' => __( 'Checkbox List', 'projectmanager'), 'radio' => __( 'Radio List', 'projectmanager'), 'fileupload' => __('File Upload', 'projectmanager'), 'numeric' => __( 'Numeric', 'projectmanager' ), 'currency' => __('Currency', 'projectmanager') );
		
		$form_field_types = apply_filters( 'projectmanager_formfields', $form_field_types );
		
		if ( $index )
			return $form_field_types[$index];
			
		return $form_field_types;
	}
	

	/**
	 * returns array of months in appropriate language depending on Wordpress locale
	 *
	 * @param none
	 * @return array
	 */
	function getMonths()
	{
		$locale = !defined('WPLANG_WIN') ? get_locale() : WPLANG_WIN;
		setlocale(LC_TIME, $locale);
		$months = array();
		for ( $month = 1; $month <= 12; $month++ )
			$months[$month] = htmlentities( strftime( "%B", mktime( 0,0,0, $month, date("m"), date("Y") ) ) );
		
		return $months;
	}
	
	
	/**
	 * set a message
	 *
	 * @param string $message
	 * @param boolean $error
	 * @return none
	 */
	function setMessage( $message, $error = false )
	{
		$type = 'success';
		if ( $error ) {
			$this->error = true;
			$type = 'error';
		}
		$this->message[$type] = $message;
	}
	
	
	/**
	 * print formatted success or error message
	 *
	 * @param none
	 */
	function printMessage()
	{
		if ( $this->error )
			echo "<div class='error'><p>".$this->message['error']."</p></div>";
		else
			echo "<div id='message' class='updated fade'><p><strong>".$this->message['success']."</strong></p></div>";
	}
	
	
	/**
	 * returns upload directory
	 *
	 * @param string | false $file
	 * @return string upload path
	 */
	function getFilePath( $file = false )
	{
		if ( $file )
			return WP_CONTENT_DIR.'/uploads/projects/'.$file;
		else
			return WP_CONTENT_DIR.'/uploads/projects';
	}
	
	
	/**
	 * returns url of upload directory
	 *
	 * @param string | false $file image file
	 * @return string upload url
	 */
	function getFileURL( $file = false )
	{
		if ( $file )
			return WP_CONTENT_URL.'/uploads/projects/'.$file;
		else
			return WP_CONTENT_URL.'/uploads/projects';
	}
	
	
	/**
	 * get file type
	 * 
	 * @param string $filename
	 * @return string file type
	 */
	function getFileType( $filename )
	{
		$file = $this->getFilePath($filename);
		$file_info = pathinfo($file);
		return strtolower($file_info['extension']);
	}
	
	
	/**
	 * get file image depending on filetype
	 * 
	 * @param string $filename
	 * @return string image name
	 */
	function getFileImage( $filename )
	{
		$type = $this->getFileType($filename);
		$out .= PROJECTMANAGER_URL . "/admin/icons/files/";

		switch ( $type ) {
			case'ods':
			case 'doc':
			case'docx':
				$out .= "document_word.png";
				break;
			case 'xls':
			case 'ods':
				$out .= "document_excel.png";
				break;
			case 'csv':
				$out .= "document_excel_csv";
				break;
			case 'ppt':
			case 'odp':
			case 'pptx':
				$out .= "document_powerpoint.png";
				break;
			case 'zip':
			case 'rar':
			case 'tar':
			case 'gzip':
			case 'tar.gz':
			case 'bzip2':
			case 'tar.bz2':
				$out .= "document_zipper";
				break;
			case 'divx':
			case 'mpg':
			case 'mp4':
				$out .= "film.png";
				break;
			case 'mp3':
			case 'ogg':
				$out .= "document_music.png";
				break;
			case 'gif':
			case 'png':
			case 'jpg':
			case 'jpeg':
				$out .= "image.png";
				break;
			case 'html':
			case 'htm':
			case 'php':
				$out .= "globe.png";
				break;
			case 'txt':
				$out .= "document_text.png";
				break;
			case 'pdf':
				$out .= "pdf.png";
				break;
			default:
				$out .= "document.png";
				break;
		}

		return $out;
	}
	
	
	

	/**
	 * check if search was performed
	 *
	 * @param none
	 * @return boolean
	 */
	function isSearch()
	{
		if ( isset( $_POST['search_string'] ) )
			return true;
	
		return false;
	}
	
	
	/**
	 * returns search string
	 *
	 * @param none
	 * @return string
	 */
	function getSearchString()
	{
		if ( $this->isSearch() )
			return (string)$_POST['search_string'];

		return '';
	}
	
	
	/**
	 * gets form field ID of search request
	 *
	 * @param none
	 * @return int
	 */
	function getSearchOption()
	{
		if ( $this->isSearch() )
			return $_POST['search_option'];
		
		return 0;
	}
	
	
	/**
	 * get number of projects
	 *
	 * @param none
	 * @return int
	 */
	function getNumProjects()
	{
		global $wpdb;
		$num_projects = $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->projectmanager_projects}" );
				
		return $num_projects;
	}
	
	
	/**
	 * gets all projects from database
	 *
	 * @param int $project_id (optional)
	 * @return array
	 */
	function getProjects()
	{
		global $wpdb;
		$projects = $wpdb->get_results( "SELECT `title`, `settings`, `id` FROM {$wpdb->projectmanager_projects} ORDER BY `id` ASC" );
		$i = 0;
		foreach ( $projects AS $project ) {
			$projects[$i] = (object) array_merge( (array)$project, (array)maybe_unserialize($project->settings) );
			unset($projects[$i]->settings);
			$i++;
		}
		return $projects;
	}
	
	
	/**
	 * gets one project
	 *
	 * @param int $project_id
	 * @return array
	 */
	function getProject( $project_id = false )
	{
		global $wpdb;

		if ( !$project_id ) $project_id = $this->getProjectID();
		$project = $wpdb->get_results( "SELECT `title`, `settings`, `id` FROM {$wpdb->projectmanager_projects} WHERE `id` = {$project_id} ORDER BY `id` ASC" );
		$project = $project[0];
		$project = (object) array_merge( (array)$project, (array)maybe_unserialize($project->settings) );
		unset($project->settings);

		$this->project = $project;
		return $project;
	}
	
	
	/**
	 * gets form fields for project
	 *
	 * @param int|false $id ID of formfield
	 * @return array
	 */
	function getFormFields( $id = false )
	{
		global $wpdb;
	
		if ( $id )
			$search = "`id` = {$id}";
		else
			$search = "`project_id` = {$this->getProjectID()}"; 

		$sql = "SELECT `label`, `type`, `order`, `order_by`, `show_on_startpage`, `show_in_profile`, `id` FROM {$wpdb->projectmanager_projectmeta} WHERE $search ORDER BY `order` ASC;";
		$formfields = $wpdb->get_results( $sql );
		
		if ($id)
			return $formfields[0];
		else
			return $formfields;
	}
	
	
	/**
	* gets number of form fields for a specific project
	*
	* @param none
	* @return int
	*/
	function getNumFormFields( )
	{
		global $wpdb;
	
		$num_form_fields = $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->projectmanager_projectmeta} WHERE `project_id` = {$this->getProjectID()}" );
		return $num_form_fields;
	}
	

	/**
	 * get selected categories for dataset
	 *
	 * @param object $dataset
	 * @return array
	 */
	function getSelectedCategoryIDs( $dataset )
	{
		$cat_ids = maybe_unserialize($dataset->cat_ids);
		if ( !is_array($cat_ids) )
			$cat_ids = array($cat_ids);
		return $cat_ids;
	}
	
	
	/**
	 * get selected categories string
	 *
	 * @param array $cat_ids
	 * @return string
	 */
	function getSelectedCategoryTitles( $cat_ids )
	{
		$categories = array();
		foreach ( (array)$cat_ids AS $cat_id )
			$categories[] = $this->getCatTitle($cat_id);

		return implode(", ", $categories);
	}
	
	
	/**
	 * gets MySQL Search string for given group
	 *
	 * @param none
	 * @return array
	 */
	function getCategorySearchString( )
	{
		global $wpdb;
		
		$datasets = $wpdb->get_results( "SELECT `id`, `cat_ids` FROM {$wpdb->projectmanager_dataset} WHERE `project_id` = {$this->project_id} ORDER BY `name` ASC" );
								
		$selected_datasets = array();
		foreach ( (array)$datasets AS $dataset ) {
			if ( in_array($this->getCatID(), $this->getSelectedCategoryIDs($dataset)) )
				$selected_datasets[] = '`id` = '.$dataset->id;
		}

		if ( !empty($selected_datasets) ) {
			$sql = ' AND ('.implode(' OR ', $selected_datasets).')';
			return $sql;
		}

		return false;
	}
	
		
	/**
	 * gets number of datasets for specific project
	 *
	 * @param int $project_id
	 * @return int
	 */
	function getNumDatasets( $project_id, $all = false )
	{
		global $wpdb;

		$sql = "SELECT COUNT(ID) FROM {$wpdb->projectmanager_dataset} WHERE `project_id` = {$project_id}";
		if ( $all )
			return $wpdb->get_var( $sql );
		elseif ( $this->isSearch() )
			return count($this->datasets);
		else {
			if ( $this->isCategory() )
				$sql .= $this->getCategorySearchString();

			return $wpdb->get_var( $sql );
		}
	}
		
	
	/**
	 * gets all datasets for a project - BUGFIX with ordering
	 *
	 * @param array $args
	 * @return array
	 */
	function getDatasets( $args = array() )
	{
		global $wpdb;
		$defaults = array( 'limit' => false, 'orderby' => false, 'order' => false, 'random' => false );
		$args = array_merge($defaults, $args);
		extract($args, EXTR_SKIP);

		$project = $this->getCurrentProject();

		// Start basic MySQL Query
		$sql = "SELECT dataset.`id` AS id, dataset.`name` AS name, dataset.`image` AS image, `cat_ids`, `user_id` FROM {$wpdb->projectmanager_dataset} AS dataset  WHERE dataset.`project_id` = {$this->project_id}";

		if ( $random ) {
			// get all datasets of project
			$results = $wpdb->get_results($sql);
			$all = array();
			foreach ( $results AS $result ) {
				$all[] = $result;
			}

			$datasets = array();
			while ( count($datasets) < intval($limit) ) {
				$id = mt_rand(0, count($all)-1);
				if ( $all[$id] && !array_key_exists($all[$id]->id, $datasets) )
					$datasets[$all[$id]->id] = $all[$id];
			}
			
			$datasets = array_values($datasets);
		} else {
			// Set ordering
			$formfield_id = $this->setDatasetOrder($orderby, $order);

			// get MySQL Ordering String
			$tmp = explode("-",$orderby);
			$orderby = $tmp[0];
			if ( $orderby && $orderby != 'formfields' ) {
				$sql_order = "`$orderby` $order";
			} else {
				$sql_order = ( $this->orderby != 'name' && $this->orderby != 'id' && $this->orderby != 'order' ) ? '`name` '.$this->order : $this->getDatasetOrder();
			}

			if ( $limit && $this->per_page != 'NaN' ) $offset = ( $this->getCurrentPage() - 1 ) * $this->per_page;

			if( $meta_key && $meta_key != 'name' && !empty($meta_value) )
				$sql .= " AND `id` IN ( SELECT `dataset_id` FROM {$wpdb->projectmanager_datasetmeta} AS meta WHERE meta.form_id = '".intval($meta_key)."' AND meta.value = '".$meta_value."' )";

			if ( 'name' == $meta_key && !empty($meta_value) ) $sql .= " AND `name` = '".$meta_value."'";
		
			if ( $this->isCategory() )
				$sql .= $this->getCategorySearchString();
		
			$sql .=  " ORDER BY ".$sql_order;

			if ( is_numeric($limit) && $limit > 0 ) 
				$sql .= " LIMIT 0, ".$limit.";";
			elseif ( $limit && $this->per_page != 'NaN' )
				$sql .= " LIMIT ".$offset.",".$this->per_page.";";
			else
				$sql .= ";";	

			$datasets = $wpdb->get_results($sql);

			/*
			* Determine wether to sort by formfields or not
			* Selection Menus and Shortcode Attributes override Project Settings
			*/
			if ( ($project->dataset_orderby == 'formfields' && !$this->override_order) || $formfield_id )
				$orderby_formfields = true;
			else
				$orderby_formfields = false;
	
			if ( $orderby_formfields )
				$datasets = $this->orderDatasetsByFormFields($datasets, $formfield_id);
		}


		return $datasets;
	}
	
	
	/**
	 * gets single dataset
	 *
	 * @param int $dataset_id
	 * @return array
	 */
	function getDataset( $dataset_id )
	{
		global $wpdb;
		$dataset = $wpdb->get_results( "SELECT `id`, `name`, `image`, `cat_ids`, `user_id` FROM {$wpdb->projectmanager_dataset} WHERE `id` = {$dataset_id}" );
			
		return $dataset[0];
	}
	

	/**
	 * order datasets by chosen form fields
	 *
	 * @param array $datasets
	 * @param int|false $form_field_id
	 * @return array
	 */
	function orderDatasetsByFormFields( $datasets, $form_field_id = false )
	{
		global $wpdb;
	
		/*
		* Generate array of parameters to sort datasets by
		*/
		$to_sort = array();
		if ( !$form_field_id ) {
			foreach ( $this->getFormFields( ) AS $form_field )
				if ( 1 == $form_field->order_by )
					$to_sort[] = $form_field->id;
		} else {
			$to_sort[] = $form_field_id;
		}
	
		/*
		* Only process datasets if there is anything to do
		*/
		if ( $to_sort ) {
			/*
			* Generate array of dataset data to sort and indexed array of unsorted datasets
			*/
			$i = 0;
			$datasets_new = array();
			$dataset_meta = array();
			foreach ( $datasets AS $dataset ) {
				foreach ( $this->getDatasetMeta( $dataset->id ) AS $meta ) {
					$meta_value = $meta->value;
					$dataset_meta[$i][$meta->form_field_id] = $meta_value;
				}
				$dataset_meta[$i]['dataset_id'] = $dataset->id;
				
				$i++;
				
				$datasets_new[$dataset->id] = $dataset;
			}
				
			/*
			*  Generate order arrays
			*/
			$order = array();
			foreach ( $dataset_meta AS $key => $row ) {
				$i=0;
				foreach ( $to_sort AS $form_field_id ) {
					$order[$i][$key] = $row[$form_field_id];
					$i++;
				}
			}
			
			/*
			* Create array of arguments for array_multisort
			*/
			$func_args = array();
			foreach ( $order AS $key => $order_array ) {
				$sort = ( $this->order == 'DESC' || $this->order == 'desc' ) ? SORT_DESC : SORT_ASC;
				array_push( $func_args, $order_array );
				array_push( $func_args, $sort );
			}

			/*
			* sort datasets with array_multisort
			*/
			$eval = 'array_multisort(';
			for ( $i = 0; $i < count($func_args); $i++ )
				$eval .= "\$func_args[$i],";
			
			$eval .= "\$dataset_meta);";
			eval($eval);
			
			/*
			* Create sorted array of datasets
			*/
			$datasets_ordered = array();
			$x = 0;
			foreach ( $dataset_meta AS $key => $row ) {
				$datasets_ordered[$x] = $datasets_new[$row['dataset_id']];
				$x++;
			}
				
			return $datasets_ordered;
		}
		
		// simply return unsorted datasets
		return $datasets;
	}
	
	
	/**
	 * determine page dataset is on
	 *
	 * @param int $dataset_id
	 * @return int
	 */
	function getDatasetPage( $dataset_id )
	{
		if ( !$this->getPerPage() )
			return false;
			
		$datasets = $this->getDatasets();
		$offsets = array();
		foreach ( $datasets AS $o => $d ) {
			$offsets[$d->id] = $o;
		}
		$number = $offsets[$dataset_id] + 1;
		
		return ceil($number/$this->getPerPage());
	}
	
	
	/**
	 * gets meta data for dataset
	 *
	 * @param int $dataset_id
	 * @return array
	 */
	function getDatasetMeta( $dataset_id )
	{
	 	global $wpdb;
		$sql = "SELECT form.id AS form_field_id, form.label AS label, data.value AS value, form.type AS type, form.show_on_startpage AS show_on_startpage FROM {$wpdb->projectmanager_datasetmeta} AS data LEFT JOIN {$wpdb->projectmanager_projectmeta} AS form ON form.id = data.form_id WHERE data.dataset_id = {$dataset_id} ORDER BY form.order ASC";
		$meta = $wpdb->get_results( $sql );
		$i = 0;
		foreach ( $meta AS $item ) {
			$meta[$i]->value = stripslashes_deep($item->value);
			$i++;
		}
		return $meta;
	}
		
	
	/**
	 * gets form field labels as table header
	 *
	 * @param none
	 * @return string
	 */
	function getTableHeader()
	{
		$out = '';
		if ( $form_fields = $this->getFormFields() ) {
			foreach ( $form_fields AS $form_field ) {
				if ( 1 == $form_field->show_on_startpage )
				$out .= "\n\t<th scope='col' class='tableheader'>".stripslashes($form_field->label)."</th>";
			}
		}
		return $out;
	}
	function printTableHeader()
	{
		echo $this->getTableHeader( );
	}
	
		 
	/**
	 * gets dataset meta data. Output types are list items or table columns
	 *
	 * @param array $dataset
	 * @param string $output td | li | dl (default 'li')
	 * @param boolean $show_all
	 * @return string
	 */
	function getDatasetMetaData( $dataset, $output = 'td', $show_all = false )
	{
		$out = '';
		if ( $dataset_meta = $this->getDatasetMeta( $dataset->id ) ) {
			foreach ( (array)$dataset_meta AS $meta ) {
				$meta->label = stripslashes($meta->label);
				$meta_value = is_string($meta->value) ? htmlspecialchars( $meta->value, ENT_QUOTES ) : $meta->value;
				
				if ( 'text' == $meta->type || 'select' == $meta->type || 'checkbox' == $meta->type || 'radio' == $meta->type ) {
					$meta_value = "<span id='datafield".$meta->form_field_id."_".$dataset->id."'>".$meta_value."</span>";
				} elseif ( 'textfield' == $meta->type ) {
					if ( strlen($meta_value) > 150 && !$show_all )
						$meta_value = substr($meta_value, 0, 150)."...";
					$meta_value = nl2br($meta_value);
						
					$meta_value = "<span id='datafield".$meta->form_field_id."_".$dataset->id."'>".$meta_value."</span>";
				} elseif ( 'email' == $meta->type && !empty($meta_value) ) {
					$meta_value = "<span id='datafield".$meta->form_field_id."_".$dataset->id."'><a href='mailto:".$this->extractURL($meta_value, 'url')."' class='projectmanager_email'>".$this->extractURL($meta_value, 'title')."</a></span>";
				} elseif ( 'date' == $meta->type ) {
					$meta_value = ( $meta_value == '0000-00-00' ) ? '' : $meta_value;
					$meta_value = "<span id='datafield".$meta->form_field_id."_".$dataset->id."'>".mysql2date(get_option('date_format'), $meta_value )."</span>";
				} elseif ( 'uri' == $meta->type && !empty($meta_value) ) {
					$meta_value = "<span id='datafield".$meta->form_field_id."_".$dataset->id."'><a class='projectmanager_url' href='http://".$this->extractURL($meta_value, 'url')."' target='_blank' title='".$this->extractURL($meta_value, 'title')."'>".$this->extractURL($meta_value, 'title')."</a></span>";
				} elseif( 'image' == $meta->type && !empty($meta_value) ) {
					$meta_value = "<span id='datafield".$meta->form_field_id."_".$dataset->id."'><img class='projectmanager_image' src='".$meta_value."' alt='".__('Image', 'projectmanager')."' /></span>";
				} elseif ( 'fileupload' == $meta->type && !empty($meta_value) ) {
					$meta_value = "<img id='fileimage".$meta->form_field_id."_".$dataset->id."' src='".$this->getFileImage($meta_value)."' alt='' />&#160;<span id='datafield".$meta->form_field_id."_".$dataset->id."'><a class='projectmanager_file ".$this->getFileType($meta_value)."' href='".$this->getFileURL($meta_value)."' target='_blank'>".$meta_value."</a></span>";
				} elseif ( 'numeric' == $meta->type && !empty($meta_value) ) {
					if ( class_exists('NumberFormatter') ) {
						$fmt = new NumberFormatter( get_locale(), NumberFormatter::DECIMAL );
						$meta_value = $fmt->format($meta_value);
					} else {
						$meta_value = apply_filters( 'projectmanager_numeric', $meta_value );
					}
					$meta_value = "<span id='datafield".$meta->form_field_id."_".$dataset->id."'>".$meta_value."</span>";
				} elseif ( 'currency' == $meta->type && !empty($meta_value) ) {
					if ( class_exists('NumberFormatter') ) {
						$fmt = new NumberFormatter( get_locale(), NumberFormatter::CURRENCY );
						$meta_value = $fmt->format($meta_value);
					} else {
						$meta_value = money_format('%i', $meta_value);
						$meta_value = apply_filters( 'projectmanager_currency', $meta_value );
					}
					$meta_value = "<span id='datafield".$meta->form_field_id."_".$dataset->id."'>".$meta_value."</span>";
				} elseif ( !empty($meta->type) && is_array($this->getFormFieldTypes($meta->type)) ) {
					// Data is retried via callback function. Most likely a special field from LeagueManager
					$field = $this->getFormFieldTypes($meta->type);
					$args = array( 'dataset' => array( 'id' => $dataset->id, 'name' => $dataset->name ) );
					$field['args'] = array_merge( $args, $field['args'] );
					$meta_value = call_user_func_array($field['callback'], $field['args']);
				}
				
				// Generate the output
				if ( 1 == $meta->show_on_startpage || $show_all ) {
					if ( $meta_value != '' ) {
						if ( 'dl' == $output ) {
							$out .= "\n\t<dt>".$meta->label."</dt><dd>".$meta_value."</dd>";
						} elseif ( 'li' == $output ) {
							$out .= "\n\t<li class='".$meta->type."'><span class='dataset_label'>".$meta->label."</span>:&#160;".$meta_value."</li>";
						} else {
							$out .= "\n\t<td class='".$meta->type."'>";
							$out .= $this->getThickbox( $dataset, $meta );
							$out .= "\n\t\t".$meta_value;
							$out .= $this->getThickboxLink( $dataset, $meta, sprintf(__('%s of %s','projectmanager'), $meta->label, $dataset->name) );
							$out .= "\n\t</td>";
						}
					} elseif ( 'td' == $output ) {
						if (empty($meta_value))
							$meta_value = "<span id='datafield".$meta->form_field_id."_".$dataset->id."'>&#160;</span>";
							
						$out .= "\n\t<td class='".$meta->type."'>";
						$out .= $this->getThickbox( $dataset, $meta );
						$out .= $meta_value;
						$out .= $this->getThickboxLink( $dataset, $meta, sprintf(__('%s of %s','projectmanager'), $meta->label, $dataset->name) );
						$out .= "\n\t</td>";
					}
				}
			}
		}
		return $out;
	}
	function printDatasetMetaData( $dataset, $output = 'td', $show_all = false )
	{
		echo $this->getDatasetMetaData( $dataset, $output, $show_all );
	}
		 
		 
	/**
	 * get Thickbox Link for Ajax editing
	 *
	 * @param object $dataset
	 * @param object $meta
	 * @param string $title
	 * @return string
	 */
	function getThickboxLink( $dataset, $meta, $title )
	{
		global $current_user;
		
		$meta_value = maybe_unserialize($meta->value);

		$out = '';
		if ( is_admin() && ( ( current_user_can('edit_datasets') && $current_user->ID == $dataset->user_id ) || ( current_user_can('edit_other_datasets') ) ) ) {
			$dims = array('width' => '300', 'height' => '100');
			if ( 'textfield' == $meta->type )
				$dims = array('width' => '400', 'height' => '300');
			if ( 'checkbox' == $meta->type || 'radio' == $meta->type )
				$dims = array('width' => '300', 'height' => '300');

			if ( 'fileupload' != $meta->type )
				$out .= "&#160;<a class='thickbox' id='thickboxlink".$meta->form_field_id."_".$dataset->id."' href='#TB_inline&height=".$dims['height']."&width=".$dims['width']."&inlineId=datafieldwrap".$meta->form_field_id."_".$dataset->id."' title='".$title."'><img src='".PROJECTMANAGER_URL."/admin/icons/edit.gif' border='0' alt='".__('Edit')."' /></a>";
			if ( 'fileupload' == $meta->type ) {
				if ( !empty($meta_value) )
					$out .= "&#160;<a href='#' id='delfile".$meta->form_field_id."_".$dataset->id."' onClick='ProjectManager.AJAXdeleteFile(\"".$this->getFilePath($meta_value)."\", ".$dataset->id.", ".$meta->form_field_id.", \"".$meta->type."\")'><img src='".PROJECTMANAGER_URL."/admin/icons/cross.png' border='0' alt='".__('Delete')."' /></a>";
			}
		}
		return $out;
	}
	
	
	/**
	 *  get Ajax Thickbox
	 *
	 * @param object $dataset
	 * @param object $meta
	 * @return string
	 */
	function getThickbox( $dataset, $meta )
	{
		global $current_user;
		
		$value = htmlspecialchars(stripslashes_deep(maybe_unserialize($meta->value)), ENT_QUOTES);
		$out = '';
		if ( is_admin() && ( ( current_user_can('edit_datasets') && $current_user->ID == $dataset->user_id ) || ( current_user_can('edit_other_datasets') ) ) ) {
			
			$out .= "\n\t\t<div id='datafieldwrap".$meta->form_field_id."_".$dataset->id."' style='overfow:auto;display:none;'>";
			$out .= "\n\t\t<div id='datafieldbox".$meta->form_field_id."_".$dataset->id."' class='projectmanager_thickbox'>";
			$out .= "\n\t\t\t<form name='form_field_".$meta->form_field_id."_".$dataset->id."'>";
			if ( 'text' == $meta->type || 'email' == $meta->type || 'uri' == $meta->type || 'image' == $meta->type || 'numeric' == $meta->type || 'currency' == $meta->type ) {
				$out .= "\n\t\t\t<input type='text' name='form_field_".$meta->form_field_id."_".$dataset->id."' id='form_field_".$meta->form_field_id."_".$dataset->id."' value=\"".$value."\" size='30' />";
			} elseif ( 'textfield' == $meta->type ) {
				$out .= "\n\t\t\t<textarea name='form_field_".$meta->form_field_id."_".$dataset->id."' id='form_field_".$meta->form_field_id."_".$dataset->id."' rows='10' cols='40'>".$value."</textarea>";
			} elseif  ( 'date' == $meta->type ) {
				$out .= "\n\t\t\t<select size='1' name='form_field_".$meta->form_field_id."_".$dataset->id."_day' id='form_field_".$meta->form_field_id."_".$dataset->id."_day'>\n\t\t\t<option value=''>Tag</option>\n\t\t\t<option value=''>&#160;</option>";
				for ( $day = 1; $day <= 30; $day++ ) {
					$selected = ( $day == substr($value, 8, 2) ) ? ' selected="selected"' : '';
					$out .= "\n\t\t\t<option value='".str_pad($day, 2, 0, STR_PAD_LEFT)."'".$selected.">".$day."</option>";
				}
				$out .= "\n\t\t\t</select>";
				$out .= "\n\t\t\t<select size='1' name='form_field_".$meta->form_field_id."_".$dataset->id."_month' id='form_field_".$meta->form_field_id."_".$dataset->id."_month'>\n\t\t\t<option value=''>Monat</option>\n\t\t\t<option value=''>&#160;</option>";
				foreach ( $this->getMonths() AS $key => $month ) {
					$selected = ( $key == substr($value, 5, 2) ) ? ' selected="selected"' : '';
					$out .= "\n\t\t\t<option value='".str_pad($key, 2, 0, STR_PAD_LEFT)."'".$selected.">".$month."</option>";
				}
				$out .= "\n\t\t\t</select>";
				$out .= "\n\t\t\t<select size='1' name='form_field_".$meta->form_field_id."_".$dataset->id."_year' id='form_field_".$meta->form_field_id."_".$dataset->id."_year'>\n\t\t\t<option value=''>Jahr</option>\n\t\t\t<option value=''>&#160;</option>";
				for ( $year = date('Y')-50; $year <= date('Y')+10; $year++ ) {
					$selected = ( $year == substr($value, 0, 4) ) ? ' selected="selected"' : '';
					$out .= "\n\t\t\t<option value='".$year."'".$selected.">".$year."</option>";
				}
				$out .= "\n\t\t\t</select>";
			}
			elseif ( 'select' == $meta->type )
				$out .= $this->printFormFieldDropDown($meta->form_field_id, $value, $dataset->id, "form_field_".$meta->form_field_id."_".$dataset->id, false);
			elseif ( 'checkbox' == $meta->type )
				$out .= $this->printFormFieldCheckboxList($meta->form_field_id, $value, 0, "form_field_".$meta->form_field_id."_".$dataset->id, false);
			elseif ( 'radio' == $meta->type )
				$out .= $this->printFormFieldRadioList($meta->form_field_id, $value, 0, "form_field_".$meta->form_field_id."_".$dataset->id, false);
			
				
	
			$out .= "\n\t\t\t<div style='text-align:center; margin-top: 1em;'><input type='button' value='".__('Save')."' class='button-secondary' onclick='ProjectManager.ajaxSaveDataField(".$dataset->id.",".$meta->form_field_id.",\"".$meta->type."\"); return false;' />&#160;<input type='button' value='".__('Cancel')."' class='button' onclick='tb_remove();' /></div>";
			$out .= "\n\t\t\t</form>";
			$out .= "\n\t\t</div>";
			$out .= "\n\t\t</div>";
		}
		return $out;
	}
	

	/**
	 * Extract url or title from website field
	 * 
	 * @param string $url
	 * @param string $index
	 * @return string
	 */
	function extractURL($url, $index)
	{
		if ( strstr($url,'|') ) {
			$pos = strpos($url,'|');
			$uri = substr($url,0,$pos);
			$title = substr($url, $pos+1, strlen($url)-$pos);
		} else {
			$uri = $title = $url;
		}
		$data = array( 'url' => $uri, 'title' => $title );
		return $data[$index];
	}
	
	
	/**
	 * display Form Field options as dropdown
	 *
	 * @param int $form_id
	 * @param ing $selected
	 * @param boolean $echo default true
	 * @return string
	 */
	 function printFormFieldDropDown( $form_id, $selected, $dataset_id, $name, $echo = true )
	{
		$options = get_option('projectmanager');
		
		$out = '';
		if ( count($options['form_field_options'][$form_id]) > 1 ) {
			$out .= "<select size='1' name='".$name."' id='form_field_".$form_id."_".$dataset_id."'>";
			foreach ( $options['form_field_options'][$form_id] AS $option_name ) {
				if ( $option_name == $selected )
					$out .= "<option value='".$option_name."' selected='selected'>".$option_name."</option>";
				else
					$out .= "<option value='".$option_name."'>".$option_name."</option>"; 
			}
			$out .= "</select>";
		}
		
		if ( $echo )
			echo $out;
		else
			return $out;
	}
	
	
	/**
	 * display Form Field options as checkbox list
	 *
	 * @param int $form_id
	 * @param array $selected
	 * @param boolean $echo default true
	 * @return string
	 */
	function printFormFieldCheckboxList( $form_id, $selected=array(), $dataset_id, $name, $echo = true )
	{
		$options = get_option('projectmanager');
		
		$selected = explode(',', $selected);
		$out = '';
		if ( count($options['form_field_options'][$form_id]) > 1 ) {
			$out .= "<ul class='checkboxlist'>";
			foreach ( $options['form_field_options'][$form_id] AS $id => $option_name ) {
				if ( count($selected) > 0 && in_array($option_name, $selected) )
					$out .= "<li><input type='checkbox' name='".$name."' checked='checked' value='".$option_name."' id='checkbox_".$form_id."_".$id."'><label for='checkbox_".$form_id."_".$id."'> ".$option_name."</label></li>";
				else
					$out .= "<li><input type='checkbox' name='".$name."' value='".$option_name."' id='checkbox_".$form_id."_".$id."'><label for='checkbox_".$form_id."_".$id."'> ".$option_name."</label></li>";
			}
			$out .= "</ul>";
		}
		
		if ( $echo )
			echo $out;
		else
			return $out;
	}
	
	/**
	* display Form Field options as radio list
	*
	* @param int $form_id
	* @param int $selected
	* @param boolean $echo default true
	* @return string
	*/
	function printFormFieldRadioList( $form_id, $selected, $dataset_id, $name, $echo = true )
	{
		$options = get_option('projectmanager');
		
		$out = '';
		if ( count($options['form_field_options'][$form_id]) > 1 ) {
			$out .= "<ul class='radiolist'>";
			foreach ( $options['form_field_options'][$form_id] AS $id => $option_name ) {
				if ( $option_name == $selected )
					$out .= "<li><input type='radio' name='".$name."' value='".$option_name."' checked='checked'  id='radio_".$form_id."_".$id."'><label for='radio_".$form_id."_".$id."'> ".$option_name."</label></li>";
				else
					$out .= "<li><input type='radio' name='".$name."' value='".$option_name."' id='radio_".$form_id."_".$id."'><label for='radio_".$form_id."_".$id."'> ".$option_name."</label></li>";
			}
			$out .= "</ul>";
		}
		
		if ( $echo )
			echo $out;
		else
			return $out;
	}


	/**
	 * check if datasets have details
	 * 
	 * @param boolean $single
	 * @return boolean
	 */
	function hasDetails($single = true)
	{
		global $wpdb;
		
		if ( !$single ) return false;
		
		$num_form_fields = $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->projectmanager_projectmeta} WHERE `project_id` = {$this->project_id} AND `show_on_startpage` = 0" );
			
		if ( $num_form_fields > 0 )
			return true;
		
		return false;
	}

	
	/**
	 * gets search results
	 *
	 * @param none
	 * @return array
	 */
	function getSearchResults( )
	{
		global $wpdb;
		
		$search = $this->getSearchString();
		$option = $this->getSearchOption();
			
		if ( 0 == $option ) {
			$datasets = $wpdb->get_results( "SELECT `id`, `name`, `cat_ids` FROM {$wpdb->projectmanager_dataset} WHERE `project_id` = {$this->project_id} AND `name` REGEXP CONVERT( _utf8 '".$search."' USING latin1 ) ORDER BY `name` ASC" );
		} elseif ( -1 == $option ) {
			$categories = explode(",", $search);
			$cat_ids = array();
			foreach ( $categories AS $category ) {
				$c = $wpdb->get_results( $wpdb->prepare ( "SELECT `term_id` FROM $wpdb->terms WHERE `name` = '%s'", trim($category) ) );
				$cat_ids[] = $c[0]->term_id;;
			}
			$sql = "SELECT `id`, `name`, `image`, `cat_ids` FROM {$wpdb->projectmanager_dataset} WHERE `project_id` = {$this->project_id}";
				
			foreach ( $cat_ids AS $cat_id ) {
				$this->setCatID($cat_id);
				$sql .= $this->getCategorySearchString();
			}
			$this->cat_id = null;
			
			$sql .=  " ORDER BY `name` ASC;";

			$datasets = $wpdb->get_results($sql);
		} else {
			$sql = "SELECT  t1.dataset_id AS id,
					t2.name,
					t2.cat_ids
				FROM {$wpdb->projectmanager_datasetmeta} AS t1, {$wpdb->projectmanager_dataset} AS t2
				WHERE t1.value REGEXP CONVERT( _utf8 '".$search."' USING latin1 )
					AND t1.form_id = '".$option."'
					AND t1.dataset_id = t2.id
				ORDER BY t1.dataset_id ASC";
			$datasets = $wpdb->get_results( $sql );
		}
		
		$this->datasets = $datasets;
		return $datasets;
	}
	
	
	/**
	 * get supported image types
	 *
	 * @param none
	 * @return array of image types
	 */
	function getSupportedImageTypes()
	{
		return ProjectManagerImage::getSupportedImageTypes();	
	}
	
	
	/**
	 * read in contents from directory
	 *
	 * @param string/array $dir
	 * @return array of files
	 */
	function readFolder( $dir )
	{
		$files = array();
		
		if ( is_array($dir) ) {
			foreach ( $dir AS $d ) {
				if ($handle = opendir($d)) {
					while (false !== ($file = readdir($handle))) {
						if ( $file != '.' && $file != '..' && substr($file,0,1) != '.' )
							$files[] = $file;
					}
			
					closedir($handle);
				}
			}
		} else {
			if ($handle = opendir($dir)) {
				while (false !== ($file = readdir($handle))) {
					if ( $file != '.' && $file != '..' && substr($file,0,1) != '.' )
						$files[] = $file;
				}
			
				closedir($handle);
			}
		}
		
		return $files;
	}
}
?>
