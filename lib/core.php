<?php
/**
 * Core class for the WordPress plugin ProjectManager
 * 
 * @author 	Kolja Schleich
 * @package	ProjectManager
 * @copyright Copyright 2008-2015
*/
class ProjectManager extends ProjectManagerLoader
{
	/**
	 * ID of current project
	 *
	 * @var int
	 */
	var $project_id = null;
	
	
	/**
	 * current project object
	 *
	 * @var object
	 */
	var $project = null;
	
	
	/**
	 * selected category
	 *
	 * @var int
	 */
	var $cat_id = null;
		
		
	/**
	 * number of dataset per page
	 *
	 * @var int
	 */
	var $per_page = null;
		

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
	var $order = 'asc';
	
	
	/**
	 * datafield datasets are ordered by
	 *
	 * @var string
	 */
	var $orderby = 'name';
	
	
	/**
	 * query arguments
	 *
	 * @var array
	 */
	var $query_args = array();
	
	
	/**
	 * admin panel object
	 *
	 * @var object
	 */
	var $admin = null;
	
	
	/**
	 * formfield id to filter datasets for
	 *
	 * @var id or array
	 */
	var $meta_key = null;
	
	
	/**
	 * value for formfield id
	 *
	 * @var string
	 */
	var $meta_value;
	
	
	/**
	 * Initialize project settings
	 *
	 * @param int $project_id ID of selected project. false if none is selected
	 * @return void
	 */
	function __construct( $project_id = false )
	{
		global $wpdb;
			
		//Save selected group. NULL if none is selected
		$this->setCatID();

		if ( $project_id ) $this->init(intval($project_id));

		$this->admin = parent::getAdminPanel();
		
		// cleanup captchas, which are older than 2 hours
		wp_mkdir_p( $this->getCaptchaPath() );
		$this->cleanupOldFiles($this->getCaptchaPath(), 2);
		
		// cleanup old captcha options
		$options = get_option('projectmanager');
		if (isset($options['captcha']) && count($options['captcha']) > 0) {
			$now = time();
			foreach ($options['captcha'] AS $key => $dat) {
				if (!file_exists($this->getCaptchaPath($key))) {
					unset($options['captcha'][$key]);
				}
			}
		}
		if (isset($options['captcha']) && count($options['captcha']) == 0)
			unset($options['captcha']);

		update_option('projectmanager', $options);
		
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
	function ProjectManager( $project_id = false )
	{
		$this->__construct( $project_id );
	}
	
	
	/**
	 * Initialize project settings
	 *
	 * @param int $project_id
	 * @return void
	 */
	function init( $project_id )
	{
		$this->setProjectID(intval($project_id));
		$this->setProject($this->getProject($this->getProjectID()));

		$this->setQueryArg('project_id', $this->getProjectID());

		$this->setPerPage();

		//$this->num_items = $this->getNumDatasets($this->getProjectID());
		$this->setNumDatasets($this->getNumDatasets($this->getProjectID()));
		//$this->num_max_pages = $this->setNumPages(); //( 'NaN' == $this->getPerPage() || 0 == $this->getPerPage() || $this->isSearch() ) ? 1 : ceil( $this->num_items/$this->getPerPage() );
		$this->setNumPages();
	}
	
	
	/**
	 * set query args
	 *
	 * @param string $key
	 * @param $value
	 */
	function setQueryArg($key, $value)
	{
		$this->query_args[$key] = $value;
	}
	
	
	/**
	 * retrieve current page
	 *
	 * @param none
	 * @return int
	 */
	function getCurrentPage($project_id = false)
	{
		global $wp;
		
		if ($project_id) $this->setProjectID(intval($project_id));
		
		$key = "paged_".$this->getProjectID();
		if (isset($_GET['paged'])) // && isset($_GET['project_id']) && $_GET['project_id'] == $this->getProjectID())
			$this->current_page = intval($_GET['paged']);
		elseif (isset($wp->query_vars['paged']))
			$this->current_page = max(1, intval($wp->query_vars['paged']));
		elseif (isset($_GET[$key]))
			$this->current_page = intval($_GET[$key]);
		elseif (isset($wp->query_vars[$key]))
			$this->current_page = max(1, intval($wp->query_vars[$key]));
		else
			$this->current_page = 1;

		return intval($this->current_page);
	}
	
	
	/**
	 * retrieve number of pages
	 *
	 * @param none
	 * @return int
	 */
	function getNumPages()
	{
		if (isset($this->num_max_pages))
			return intval($this->num_max_pages);
		else
			return 1;
	}

	
	/**
	 * set number of pages
	 *
	 * @param int $num_max_pages
	 * @return none
	 */
	function setNumPages()
	{
		$this->num_max_pages = ( 'NaN' == $this->getPerPage() || 0 == $this->getPerPage() || $this->isSearch() ) ? 1 : ceil( $this->num_items/$this->getPerPage() );
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
			$this->per_page = intval($per_page);
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
	 * set number of items
	 *
	 * @param int $num_datasets
	 * @return none
	 */
	function setNumDatasets($num_datasets)
	{
		$this->num_items = intval($num_datasets);
	}
	
	
	/**
	 * save current project ID
	 *
	 * @param int
	 * @return void
	 */
	function setProjectID( $id )
	{
		$this->project_id = intval($id);
	}


	/**
	 * gets project ID
	 *
	 * @param none
	 * @return int
	 */
	function getProjectID()
	{
		return intval($this->project_id);
	}
	
	
	/**
	 * set project
	 *
	 * @param object $project
	 * @return void
	 */
	function setProject($project)
	{
		$this->project = $project;
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
		return stripslashes($this->project->title);
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
			$this->cat_id = intval($cat_id);
		} elseif ( isset($_POST['cat_id']) ) {
			$this->cat_id = intval($_POST['cat_id']);
		} elseif ( isset($_GET['cat_id']) ) {
			$this->cat_id = intval($_GET['cat_id']);
			//$this->query_args['cat_id'] = $this->cat_id;
		} else {
			$this->cat_id = null;
		}
		$this->setQueryArg('cat_id', $this->cat_id);
			
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
		if ($this->cat_id == null)
			return null;
		else
			return intval($this->cat_id);
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
		$cat_id = intval($cat_id);
		$c = get_category($cat_id);
		
		if ( isset($c->name) )
			return stripslashes($c->name);
			
		return false;
	}
	
	
	/**
	 * check if category is selected
	 * 
	 * @param none
	 * @return boolean
	 */
	function isCategory()
	{
		if ( null != $this->getCatID() && $this->getCatID() != 0 )
			return true;
		
		return false;
	}


	/**
	 * display pagination
	 *
	 * @param int $current_page
	 * @param string $base
	 * @return string
	 */
	function getPageLinks($current_page = false, $base = 'paged')
	{
		if (!$current_page) $current_page = $this->getCurrentPage();
		
		if (is_admin()) $query_args = array('project_id' => $this->getProjectID());
		else $query_args = (isset($this->query_args)) ? $this->query_args : array();
		
		if (isset($_POST['orderby'])) {
			$query_args['orderby'] = htmlspecialchars($_POST['orderby']);
		}
		if (isset($_POST['order'])) {
			$query_args['order'] = htmlspecialchars($_POST['order']);
		}
		
		$page_links = paginate_links( array(
			'base' => add_query_arg( $base, '%#%' ),
			'format' => '',
			'prev_text' => '&#9668;',
			'next_text' => '&#9658;',
			'total' => $this->getNumPages(),
			'current' => $current_page,
			'add_args' => $query_args
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
			$orderby = explode('_', htmlspecialchars($_POST['orderby']));
			$this->orderby = ( $_POST['orderby'] != '' ) ? htmlspecialchars($_POST['orderby']) : 'name';
			$formfield_id = isset($orderby[1]) ? $orderby[1] : false;
			$this->order = ( $_POST['order'] != '' ) ? htmlspecialchars($_POST['order']) : 'ASC';

			$this->setQueryArg('order', $this->order);
			$this->setQueryArg('orderby', $this->orderby);

			$this->override_order = true;
		}
		// Selection in Frontend
		elseif ( isset($_GET['orderby']) && isset($_GET['order']) && isset($_GET['project_id']) && $_GET['project_id'] == $this->getProjectID() ) {
			$orderby = explode('_', htmlspecialchars($_GET['orderby']));
			$this->orderby = ( $_GET['orderby'] != '' ) ? htmlspecialchars($_GET['orderby']) : 'name';
			$formfield_id = isset($orderby[1]) ? $orderby[1] : "";
			$this->order = ( $_GET['order'] != '' ) ? htmlspecialchars($_GET['order']) : 'ASC';
			
			$this->override_order = true;
		}
		// Selection in Frontend
		elseif ( isset($_GET['orderby_'.$this->getProjectID()]) && isset($_GET['order_'.$this->getProjectID()])) {
			$orderby = explode('_', htmlspecialchars($_GET['orderby_'.$this->getProjectID()]));
			$this->orderby = ( $_GET['orderby_'.$this->getProjectID()] != '' ) ? htmlspecialchars($_GET['orderby_'.$this->getProjectID()]) : 'name';
			$formfield_id = isset($orderby[1]) ? $orderby[1] : "";
			$this->order = ( $_GET['order_'.$this->getProjectID()] != '' ) ? htmlspecialchars($_GET['order_'.$this->getProjectID()]) : 'ASC';
			
			$this->override_order = true;
		}
		// Shortcode Attributes
		elseif ( $orderby || $order ) {
			if ( $orderby ) {
				$tmp = explode("_",$orderby);
				//$this->orderby = $tmp[0];
				$this->orderby = $orderby;
				$formfield_id = isset($tmp[1]) ? $tmp[1] : "";
			}
			if ( $order ) $this->order = $order;

			$this->override_order = true;
		}
		// Project Settings
		elseif ( isset($project->dataset_orderby) && !empty($project->dataset_orderby) ) {
			$this->orderby = $project->dataset_orderby;
			$this->order = (isset($project->dataset_order) && !empty($project->dataset_order)) ? $project->dataset_order : 'ASC';
		}
		// Default
		else {
			$this->orderby = 'name';
			$this->order = 'asc';
		}
		return $formfield_id;
	}
	
	/**
	 * get dataset order
	 *
	 * @param none
	 * @return string
	 */
	function getDatasetOrder()
	{
		//global $wpdb;
		//return $wpdb->prepare("%s %s", $this->orderby, $this->order);
		
		return $this->order;
	}
	
	/**
	 * get dataset order
	 *
	 * @param none
	 * @return string
	 */
	function getDatasetOrderBy()
	{
		return $this->orderby;
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
		$form_field_types = array( 'text' => __('Text', 'projectmanager'), 'textfield' => __('Textfield', 'projectmanager'), 'tinymce' => __('TinyMCE Editor', 'projectmanager'), 'email' => __('E-Mail', 'projectmanager'), 'date' => __('Date', 'projectmanager'), 'uri' => __('URL', 'projectmanager'), 'select' => __('Selection', 'projectmanager'), 'checkbox' => __( 'Checkbox List', 'projectmanager'), 'radio' => __( 'Radio List', 'projectmanager'), 'file' => __('File', 'projectmanager'), 'image' => __( 'Image', 'projectmanager' ), 'video' => __('Video', 'projectmanager'), 'numeric' => __( 'Numeric', 'projectmanager' ), 'currency' => __('Currency', 'projectmanager'), 'country' => __('Country', 'projectmanager'), 'project' => __( 'Internal Link', 'projectmanager' ), 'time' => __('Time', 'projectmanager'), 'wp_user' => __( 'WP User', 'projectmanager' ) );
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
	 * sort array with umlaute
	 * 
	 * @param array to sort
	 * @return sorted array
	 */
	function sortArray($tArray) {
		$aOriginal = $tArray;
		if (count($aOriginal) == 0) { return $aOriginal; }
		$aModified = array();
		$aReturn   = array();
		$aSearch   = array("Ä","ä","Ö","ö","Ü","ü","ß","-");
		$aReplace  = array("Ae","ae","Oe","oe","Ue","ue","ss"," ");
		foreach($aOriginal as $key => $val) {
			$aModified[$key] = str_replace($aSearch, $aReplace, $val);
		}
		natcasesort($aModified);
		foreach($aModified as $key => $val) {
			$aReturn[$key] = $aOriginal[$key];
		}
		return $aReturn;
	}

	
	/**
	 * retrieve countries from mysql database
	 *
	 * @param none
	 * @return array
	 */
	function getCountries()
	{
		global $wpdb;
		$countries = $wpdb->get_results( "SELECT `code`, `name`, `id` FROM {$wpdb->projectmanager_countries} ORDER BY `name` ASC" );
		
		/*
		* re-sort contries based on translated names
		*/
		$to_sort = array();
		foreach ($countries AS $country) {
			$to_sort[] = __(stripslashes($country->name), 'projectmanager');
		}
		$sorted = $this->sortArray($to_sort);
		
		$c = array();
		foreach ($sorted AS $key => $name) {
			$c[] = (object) array("code" => $countries[$key]->code, "name" => stripslashes($name), "id" => $countries[$key]->id);
		}
		
		return $c;
	}
	
	
	/**
	 * get country name
	 *
	 * @param $code
	 * @return string
	 */
	function getCountryName($code)
	{
		global $wpdb;
		
		if ($code == "")
			return "";
		
		$country = $wpdb->get_results( $wpdb->prepare( "SELECT `code`, `name`, `id` FROM {$wpdb->projectmanager_countries} WHERE `code` = '%s'", $code ) );
		return __(stripslashes($country[0]->name), 'projectmanager');
	}
	
	
	/**
	 * get country code
	 *
	 * @param $name
	 * @return string
	 */
	function getCountryCode($name)
	{
		global $wpdb;
		
		if ($name == "")
			return "";
		
		$country = $wpdb->get_results( $wpdb->prepare( "SELECT `code`, `name`, `id` FROM {$wpdb->projectmanager_countries} WHERE `name` = '%s'", $name ) );
		return $country[0]->code;
	}
	
	
	/**
	 * get only `country`-type formfields
	 *
	 * @param none
	 * @return array
	 */
	function getCountryFormFields()
	{
		global $wpdb;
		$sql = "SELECT `label`, `type`, `id` FROM {$wpdb->projectmanager_projectmeta} WHERE `project_id` = '%d' AND `type` = 'country' ORDER BY `order` ASC";
		$formfields = $wpdb->get_results( $wpdb->prepare($sql, $this->getProjectID()) );
		foreach ($formfields AS $i => $formfield) {
			$formfields[$i]->label = stripslashes($formfield->label);
		}
		return $formfields;
	}
	
	
	/**
	 * check if country formfield type is active
	 *
	 * @param none
	 * @return boolean
	 */
	function hasCountryFormField()
	{
		global $wpdb;
		$sql = "SELECT COUNT(ID) FROM {$wpdb->projectmanager_projectmeta} WHERE `project_id` = '%d' and `type` = 'country'";
		$num = $wpdb->get_var($wpdb->prepare($sql, $this->getProjectID()));
		
		if ($num > 0)
			return true;
		/*$formfields = $this->getFormFields(false, true);
		
		foreach ($formfields AS $formfield) {
			if ($formfield->type == "country")
				return true;
		}*/
		
		return false;
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
			if ($message != "") $this->error = true;
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
		if ( $this->error && $this->message['error'] != "" )
			echo "<div class='error'><p>".$this->message['error']."</p></div>";
		elseif ($this->message['success'] != "")
			echo "<div id='message' class='updated fade'><p><strong>".$this->message['success']."</strong></p></div>";
	}
	
	
	/**
	 * return path to captcha directory
	 *
	 * @param none
	 * @return string
	 */
	function getCaptchaPath( $file = false )
	{
		if ( $file )
			return $this->getFilePath("captchas/".$file, true);
		else
			return $this->getFilePath("captchas", true);
	}
	
	
	/**
	 * return url to captcha directory
	 *
	 * @param none
	 * @return string
	 */
	function getCaptchaURL( $file = false )
	{
		if ( $file )
			return $this->getFileURL("captchas/".$file, true);
		else
			return $this->getFileURL("captchas", true);
	}
	
	
	/**
	 * returns upload directory
	 *
	 * @param string | false $file
	 * @param boolean $root
	 * @return string upload path
	 */
	function getFilePath( $file = false, $root = false )
	{
		if ($root || $this->getProjectID() == 0)
			$base = WP_CONTENT_DIR.'/uploads/projects';
		else
			$base = WP_CONTENT_DIR.'/uploads/projects/Project-'.$this->getProjectID();
			
		if ( $file )
			return $base .'/'. $file;
		else
			return $base;
	}
	
	
	/**
	 * returns url of upload directory
	 *
	 * @param string | false $file image file
	 * @param boolean $root
	 * @return string upload url
	 */
	function getFileURL( $file = false, $root = false )
	{
		if ($root || $this->getProjectID() == 0)
			$base = WP_CONTENT_URL.'/uploads/projects';
		else
			$base = WP_CONTENT_URL.'/uploads/projects/Project-'.$this->getProjectID();
			
		if ( $file )
			return $base .'/'. $file;
		else
			return $base;
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
		$out = PROJECTMANAGER_URL . "/admin/icons/files/";

		switch ( $type ) {
			case 'ods':
			case 'doc':
			case 'docx':
				$out .= "document_word.png";
				break;
			case 'xls':
			case 'ods':
				$out .= "document_excel.png";
				break;
			case 'csv':
				$out .= "document_excel_csv.png";
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
				$out .= "document_zipper.png";
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
		if ( isset($_POST['search_string']) && isset($_POST['project_id']) && $_POST['project_id'] == $this->getProjectID() )
			return true;
		
		$search_string_ind = "search_string_".$this->getProjectID();
		if ( isset($_POST[$search_string_ind]) )
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
		if ( $this->isSearch() ) {
			$search_string_ind = "search_string_".$this->getProjectID();
			if (isset($_POST['search_string'])) return htmlspecialchars($_POST['search_string']);
			elseif (isset($_POST[$search_string_ind])) return htmlspecialchars($_POST[$search_string_ind]);
		}
		
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
		if ( $this->isSearch() ) {
			$search_option_ind = "search_option_".$this->getProjectID();
			if (isset($_POST['search_option']))
				return intval($_POST['search_option']);
			elseif (isset($_POST[$search_option_ind]))
				return intval($_POST[$search_option_ind]);
		}
		
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
				
		return intval($num_projects);
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
			$projects[$i] = $this->getDefaultProjectSettings($projects[$i]);
			$projects[$i]->title = stripslashes($project->title);
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
		$project = $wpdb->get_results( $wpdb->prepare("SELECT `title`, `settings`, `id` FROM {$wpdb->projectmanager_projects} WHERE `id` = '%d' ORDER BY `id` ASC", intval($project_id)) );
		$project = $project[0];
		$project = (object) array_merge( (array)$project, (array)maybe_unserialize($project->settings) );
		unset($project->settings);
		$project = $this->getDefaultProjectSettings($project);
		$project->title = stripslashes($project->title);

		$this->project = $project;
		return $project;
	}
	
	
	/**
	 * get default project settings
	 *
	 * @param object $project
	 */
	function getDefaultProjectSettings( $project )
	{
		if (!isset($project->per_page)) $project->per_page = "";
		if (!isset($project->category)) $project->category = "";
		if (!isset($project->dataset_orderby)) $project->dataset_orderby = "name";
		if (!isset($project->dataset_order)) $project->dataset_order = "ASC";
		if (!isset($project->navi_link)) $project->navi_link = 0;
		if (!isset($project->profile_hook)) $project->profile_hook = 0;
		if (!isset($project->menu_icon)) $project->menu_icon = "databases.png";
		if (!isset($project->gallery_num_cols)) $project->gallery_num_cols = "";
		if (!isset($project->show_image)) $project->show_image = 0;
		if (!isset($project->image_mandatory)) $project->image_mandatory = 0;
		if (!isset($project->default_image)) $project->default_image = "";
		if (!isset($project->tiny_size)) $project->tiny_size = array("width" => 80, "height" => 50);
		if (!isset($project->thumb_size)) $project->thumb_size = array("width" => 100, "height" => 100);
		if (!isset($project->medium_size)) $project->medium_size = array("width" => 300, "height" => 300);
		if (!isset($project->chmod)) $project->chmod = "755";
		
		return $project;
	}
	
	
	/**
	 * gets form fields for project
	 *
	 * @param int|false $id ID of formfield
	 * @return array
	 */
	function getFormFields( $id = false, $all = false )
	{
		global $wpdb;
	
		if ( $id )
			$search = "`id` = ".intval($id);
		else
			$search = "`project_id` = ".intval($this->getProjectID()); 

		// Only get private formfields in admin interface
		if (!is_admin() && !$all && !$id) {
			$search .= " AND `private` = 0";
		}
		
		$sql = "SELECT `label`, `type`, `order`, `order_by`, `mandatory`, `unique`, `private`, `show_on_startpage`, `show_in_profile`, `options`, `id` FROM {$wpdb->projectmanager_projectmeta} WHERE $search ORDER BY `order` ASC;";
		$formfields = $wpdb->get_results( $sql );
	
		$i = 0;
		foreach ($formfields AS $formfield) {
			$formfields[$i]->label = stripslashes($formfield->label);
			$formfields[$i]->options = stripslashes($formfield->options);
			$i++;
		}
		
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
	
		$num_form_fields = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(ID) FROM {$wpdb->projectmanager_projectmeta} WHERE `project_id` = '%d'", $this->getProjectID() ));
		return intval($num_form_fields);
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
		
		$datasets = $wpdb->get_results( $wpdb->prepare("SELECT `id`, `cat_ids` FROM {$wpdb->projectmanager_dataset} WHERE `project_id` = '%d' ORDER BY `name` ASC", intval($this->getProjectID())) );
								
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

		$project_id = intval($project_id);
		$sql = "SELECT COUNT(ID) FROM {$wpdb->projectmanager_dataset} WHERE `project_id` = {$project_id}";
		if ( $all ) {
			return intval($wpdb->get_var( $sql ));
		} elseif ( $this->isSearch() ) {
			if (isset($this->datasets)) return count($this->datasets);
			else return 0;
		} else {
			if ( $this->isCategory() )
				$sql .= $this->getCategorySearchString();

			// country filters could lead to multiple selections
			$meta_key = $this->meta_key;
			$meta_value = $this->meta_value;
			
			if ($meta_key && is_array($meta_key)) {
				foreach ($meta_key AS $key => $value) {
					$sql .= $wpdb->prepare(" AND `id` IN ( SELECT `dataset_id` FROM {$wpdb->projectmanager_datasetmeta} AS meta WHERE meta.form_id = '%d' AND meta.value LIKE '%s' )", intval($key), $value);
				}
			} else {
				if ( $meta_key && !empty($meta_value) ) {
					if ( $meta_key != 'name' )
						$sql .= $wpdb->prepare(" AND `id` IN ( SELECT `dataset_id` FROM {$wpdb->projectmanager_datasetmeta} AS meta WHERE meta.form_id = '%d' AND meta.value LIKE '%s' )", intval($meta_key), $meta_value);
					else
						$sql .= $wpdb->prepare(" AND `name` = '%s'", $meta_value);
				}
			}
		
			return intval($wpdb->get_var( $sql ));
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
		$defaults = array( 'limit' => false, 'orderby' => false, 'order' => false, 'random' => false, 'meta_key' => false, 'meta_value' => '' );
		$args = array_merge($defaults, $args);
		extract($args, EXTR_SKIP);
		
		$project = $this->getCurrentProject();

		/*
		* get country selection
		*/
		$country_filter = array();
		foreach ( $matches = preg_grep("/country_\d+/", array_keys($_GET)) AS $key ) {
			$x = explode("_", $key);
			if (!empty($_GET[$key]))
				$country_filter[$x[1]] = $_GET[$key];
		}
			
		foreach ( $matches = preg_grep("/country_\d+/", array_keys($_POST)) AS $key ) {
			$x = explode("_", $key);
			if (!empty($_POST[$key]))
				$country_filter[$x[1]] = $_POST[$key];
		}
	
		if (count($country_filter) > 0)
			$meta_key = $country_filter;
		
		$this->meta_key = $meta_key;
		$this->meta_value = $meta_value;
		
		// Start basic MySQL Query
		$sql = "SELECT dataset.`id` AS id, dataset.`name` AS name, dataset.`image` AS image, `cat_ids`, `user_id` FROM {$wpdb->projectmanager_dataset} AS dataset  WHERE dataset.`project_id` = '".intval($this->getProjectID())."'";

		// country filters could lead to multiple selections
		if ($meta_key && is_array($meta_key)) {
			foreach ($meta_key AS $key => $value) {
				$sql .= $wpdb->prepare(" AND `id` IN ( SELECT `dataset_id` FROM {$wpdb->projectmanager_datasetmeta} AS meta WHERE meta.form_id = '%d' AND meta.value LIKE '%s' )", intval($key), $value);
			}
		} else {
			if ( $meta_key && !empty($meta_value) ) {
				if ( $meta_key != 'name' )
					$sql .= $wpdb->prepare(" AND `id` IN ( SELECT `dataset_id` FROM {$wpdb->projectmanager_datasetmeta} AS meta WHERE meta.form_id = '%d' AND meta.value LIKE '%s' )", intval($meta_key), $meta_value);
				else
					$sql .= $wpdb->prepare(" AND `name` = '%s'", $meta_value);
			}
		}
		
		if ( $random ) {
			// get all datasets of project
			$results = $wpdb->get_results($sql);
			$datasets = array();
			
			if (count($results) > 0) {
				$all = array();
				foreach ( $results AS $result ) {
					$all[] = $result;
				}

				// Simply return all datasets if there are less or equal number of datasets than the number to get
				if (count($all) <= intval($limit)) {
					$datasets = $all;
				} else {
					while ( count($datasets) < intval($limit) ) {
						$id = mt_rand(0, count($all)-1);
						if ( $all[$id] && !array_key_exists($all[$id]->id, $datasets) )
							$datasets[$all[$id]->id] = $all[$id];
					}
				}
				
				$datasets = array_values($datasets);
			}
		} else {
			// Set ordering
			$formfield_id = $this->setDatasetOrder($orderby, $order);
			$orderby = $this->orderby;
			$order = $this->order;
			// get MySQL Ordering String
			$tmp = explode("_",$orderby);
			$orderby = $tmp[0];
			if ( $orderby && $orderby != 'formfields' ) {
				//$sql_order = "`$orderby` $order";
				$sql_order = "$orderby $order";
			} else {
				$sql_order = ( $this->orderby != 'name' && $this->orderby != 'id' && $this->orderby != 'order' ) ? '`name` '.$this->order : "$this->orderby $this->order";
			}

			if (!isset($current_page)) $current_page = $this->getCurrentPage();
			if ( $limit && $this->getPerPage() != 'NaN' ) $offset = ( $current_page - 1 ) * $this->getPerPage();
			else $offset = 0;

			if ( $this->isCategory() )
				$sql .= $this->getCategorySearchString();
		
			$sql .=  " ORDER BY ".$sql_order;

			/*
			* Determine whether to sort by formfields or not
			* Selection Menus and Shortcode Attributes override Project Settings
			*/
			if ( (isset($project->dataset_orderby) && $project->dataset_orderby == 'formfields' && !$this->override_order) || $formfield_id )
				$orderby_formfields = true;
			else
				$orderby_formfields = false;
	
			/*
			* If datasets are ordered by formfields first get all
			*/
			if (!$orderby_formfields) {
				if ( is_numeric($limit) && $limit > 0 ) 
					$sql .= " LIMIT 0, ".intval($limit).";";
				elseif ( $limit && $this->getPerPage() != 'NaN' )
					$sql .= " LIMIT ".intval($offset).",".$this->getPerPage().";";
				else
					$sql .= ";";
			} else {
				$sql .= ";";
			}
			
			$datasets = $wpdb->get_results($sql);
			
			if (is_numeric($limit) && $limit > 0)
				$number = intval($limit);
			else
				$number = $this->getPerPage();
			
			if ($number == 0 || $number == "NaN") $number = NULL;
			
			if ( $orderby_formfields )
				$datasets = $this->orderDatasetsByFormFields($datasets, $formfield_id, $offset, $number);
		}

		$i = 0;
		if ( $datasets ) {
			foreach ( $datasets AS $dataset ) {
				$datasets[$i]->name = stripslashes($dataset->name);
				
				$meta = $this->getDatasetMeta($dataset->id);
				if ( $meta ) {
					foreach ( $meta AS $m ) {
						$key = sanitize_title($m->label);
						if ( !empty($key) ) {
							$value = empty($m->value) ? '' : $m->value;
							//$datasets[$i]->{$key} = stripslashes($m->value);
							$datasets[$i]->{$key} = $m->value;
						}
					}
				}
				$i++;
			}
		}

		$this->setNumDatasets($this->getNumDatasets($this->getProjectID()));
		$this->setNumPages();
		
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
		$dataset = $wpdb->get_results( $wpdb->prepare("SELECT `id`, `name`, `image`, `cat_ids`, `user_id`, `project_id` FROM {$wpdb->projectmanager_dataset} WHERE `id` = '%d'", intval($dataset_id)) );
		$dataset = $dataset[0];
		$dataset->name = stripslashes($dataset->name);
		
		$meta = $this->getDatasetMeta($dataset->id);
		if ( $meta ) {
			foreach ( $meta AS $m ) {
				$key = sanitize_title($m->label);
				if ($key != "") $dataset->{$key} = $m->value;
			}
		}
					
		return $dataset;
	}
	

	/**
	 * get dropdown of datasets
	 *
	 * @param array $args
	 */
	function getDatasetDropdown( $args )
	{
		global $wpdb;
		$defaults = array(
			"project_id" => false,
			"name" => 'show',
			"selected" => ""
		);
			
		$args = array_merge( $defaults, $args );
		extract( $args, EXTR_SKIP );
		
		if (!$project_id) $project_id = $this->getProjectID();
		$project_id = intval($project_id);
		$selected = intval($selected);
		
		$project = $this->getProject($project_id);
		
		$datasets = $wpdb->get_results( $wpdb->prepare("SELECT `id`, `name` FROM {$wpdb->projectmanager_dataset} WHERE `project_id` = '%d'", intval($project->id)) );
		$out = "<select name='".$name."' id='".$name."'>";
		foreach ($datasets AS $dataset) {
			$out .= "<option value='".$dataset->id."' ".selected($selected,$dataset->id,false).">".stripslashes($dataset->name)."</option>";
		}
		$out .= "</select>";
		return $out;
	}
	function printDatasetDropdown( $args )
	{
		echo $this->getDatasetDropdown( $args );
	}
	
	
	/**
	 * order datasets by chosen form fields
	 *
	 * @param array $datasets
	 * @param int|false $form_field_id
	 * @param int $offset
	 * @param int $limit
	 * @return array
	 */
	function orderDatasetsByFormFields( $datasets, $form_field_id = false, $offset = 0, $limit = false )
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
					
					// Data is retried via callback function. Most likely a special field from LeagueManager
					if ( !empty($meta->type) && is_array($this->getFormFieldTypes($meta->type)) ) {
						$field = $this->getFormFieldTypes($meta->type);
						$args = array( 'dataset' => array( 'id' => $dataset->id, 'name' => $dataset->name ) );
						$field['args'] = array_merge( $args, $field['args'] );
						$meta_value = call_user_func_array($field['callback'], $field['args']);
					} else {
						$meta_value = $meta->value;
					}

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
				
			$datasets = $datasets_ordered;
		}
		
		// return only part of datasets corresponding to current offset and number of datasets
		$datasets = array_slice($datasets, $offset, $limit);
		
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
		
		if ( 'NaN' == $this->getPerPage() )
			return 1;
		
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
	 * @param array $args extra arguments possible keys are 'meta_id', 'type' or 'label' to get meta data
	 * @return array
	 */
	function getDatasetMeta( $dataset_id, $args = array() )
	{
	 	global $wpdb;
		$sql = "SELECT form.id AS form_field_id, form.label AS label, form.options AS formfield_options, form.private AS is_private, form.unique AS is_unique, form.mandatory AS is_mandatory, data.value AS value, form.type AS type, form.show_on_startpage AS show_on_startpage FROM {$wpdb->projectmanager_datasetmeta} AS data LEFT JOIN {$wpdb->projectmanager_projectmeta} AS form ON form.id = data.form_id WHERE data.dataset_id = '".intval($dataset_id)."'";

		if ( !empty($args) ) {
			if ( isset($args['meta_id']) && is_numeric($args['meta_id']) ) $sql .= " AND form.`id` = '".intval($args['meta_id'])."'";
			elseif ( isset($args['type']) && is_string($args['type']) ) $sql .= " AND form.type = '".htmlspecialchars($args['type'])."'";
			elseif ( isset($args['label']) && is_string($args['label']) ) $sql .= " AND form.label = '".htmlspecialchars($args['label'])."'";
		}

		$sql .= " ORDER BY form.order ASC";
		$meta = $wpdb->get_results( $sql );
		$i = 0;
		foreach ( $meta AS $item ) {
			$meta[$i]->value = stripslashes_deep(maybe_unserialize($item->value));
			if ($meta[$i]->form_field_id == "") unset($meta[$i]);
			$i++;
		}

		if ( !empty($args) )
			return $meta[0];

		return $meta;
	}
		

	/**
	 * gets form field labels as table header
	 *
	 * @param none
	 * @return string
	 */
	function getTableHeader( $args = array() )
	{
		$defaults = array(
				"exclude" => '',
				"include" => '',
			);
			
		$args = array_merge( $defaults, $args );
		extract( $args, EXTR_SKIP );
		
		if ( !empty($exclude) ) $exclude = explode(',', $exclude);
		if ( !empty($include) ) $include = explode(',', $include);

		$out = '';
		if ( $form_fields = $this->getFormFields() ) {
			foreach ( $form_fields AS $form_field ) {
				if ( (empty($exclude) && empty($include)) || ( empty($include) && !empty($exclude) && !in_array($form_field->type, $exclude) && !in_array($form_field->id, $exclude) ) || ( !empty($include) && (in_array($form_field->type, $include) || in_array($form_field->id, $include)) ) ) {
					if ( 1 == $form_field->show_on_startpage )
					$out .= "\n\t<th scope='col' class='tableheader ".$form_field->type."'>".stripslashes($form_field->label)."</th>";
				}
			}
		}
		return $out;
	}
	function printTableHeader( $args = array() )
	{
		echo $this->getTableHeader( $args );
	}
	
		 
	/**
	 * gets dataset meta data. Output types are list items or table columns
	 *
	 * @param object $dataset
	 * @param array $args additional arguments
	 * @return string
	 */
	function getDatasetMetaData( $dataset, $args = array() )
	{
		global $projectmanager;

		$defaults = array(
				"exclude" => '',
				"include" => '',
				"output" => "td",
				"show_all" => false,
				"class" => "",
				"image" => "tiny",
			);

		$args = array_merge( $defaults, $args );
		extract( $args, EXTR_SKIP );
		
		$img_size = empty($image) ? '' : $image . '.';

		$exclude = !empty($exclude) ? (array) explode(',', $exclude) : array();
		$include = !empty($include) ? (array) explode(',', $include) : array();

		$locale = get_locale();

		$out = '';
		if ( $dataset_meta = $this->getDatasetMeta( $dataset->id ) ) {

		foreach ( (array)$dataset_meta AS $meta ) {
			if ( (empty($exclude) && empty($include)) || ( empty($include) && !empty($exclude) && !in_array($meta->type, $exclude) && !in_array($meta->form_field_id, $exclude) ) || ( !empty($include) && in_array($meta->type, $include) || in_array($meta->form_field_id, $include) ) ) {
				$meta->label = stripslashes($meta->label);
				$meta_value = ( is_string($meta->value) && 'tinymce' != $meta->type ) ? htmlspecialchars( $meta->value, ENT_QUOTES ) : $meta->value;
				
				$custom = false;
				// Custom Formfield without callback function
				//var_dump($meta->type);
				if ( is_array($this->getFormFieldTypes($meta->type)) ) {
					$field = $this->getFormFieldTypes($meta->type);
					//var_dump($field);
					if ( !isset($field['callback']) ) {
						$custom = $meta->type;
						$meta->type = $field['html_type'];
					}
				}

				// Do some parsing on array datasets
				if ( 'checkbox' == $meta->type || 'project' == $meta->type ) {
					$list = "<ul class='".$meta->type."' id='form_field_".$meta->form_field_id."'>";
					foreach ( (array)$meta_value AS $item ) {
						if ( 'project' == $meta->type && is_numeric($item) ) { 
							$item = $projectmanager->getDataset($item);
							if ( is_admin() ) {
								if ( $_GET['page'] == 'projectmanager' )
									$url_pattern = "<a href='admin.php?page=projectmanager&subpage=dataset&edit=".$item->id."&project_id=".$item->project_id."'>%s</a>";
								else
									$url_pattern = "<a href='admin.php?page=project-dataset_".$item->project_id."&edit=".$item->id."&project_id=".$item->project_id."'>%s</a>";
							} else {
								if ( $this->getDatasetMeta($item->id) ) {
									$url = get_permalink();
									$url = add_query_arg('show', $item->id, $url);
									$url = $this->isCategory() ? add_query_arg('cat_id', $this->getCatID(), $url) : $url;
									$url_pattern = "<a href='".$url."'>%s</a>";
								} else {
									$url_pattern = "%s";
								}
							}
							$item = sprintf($url_pattern, $item->name);
						}
						$list .= "<li>".$item."</li>";
					}
					$list .= "</ul>";
					$meta_value = $list;
				}
			
				// get formfield options
				//$formfield = $this->getFormFields($meta->form_field_id);
				$formfield_options = explode(";", $meta->formfield_options);
			
				$pattern = is_admin() ? "<span id='datafield".$meta->form_field_id."_".$dataset->id."'>%s</span>" : "%s";
				if ( 'text' == $meta->type || 'select' == $meta->type || 'checkbox' == $meta->type || 'radio' == $meta->type || 'project' == $meta->type ) {
					$meta_value = apply_filters( 'projectmanager_text', $meta_value );
					$meta_value = sprintf($pattern, $meta_value, $dataset);
				} elseif ( 'textfield' == $meta->type || 'tinymce' == $meta->type ) {
					$match = array_values(preg_grep("/limit:/", $formfield_options));
					if (count($match) == 1) {
						$str_limit = explode(":", $match[0]);
						$str_limit = $str_limit[1];
					} else {
						$str_limit = 100;
					}
					
					if ( strlen($meta_value) > $str_limit && !$show_all && empty($include) ) {
						$meta_value = substr($meta_value, 0, $str_limit)." ...";
						
						if (!is_admin())
							$meta_value = $meta_value . " <a href='".get_permalink()."?show_".$this->getProjectID()."=".$dataset->id."&order_".$this->getProjectID()."=".$this->getDatasetOrder()."&orderby_".$this->getProjectID()."=".$this->getDatasetOrderBy()."'>".__('More', 'projectmanager')."</a>";
					}
					  if (!is_admin()) $meta_value = nl2br($meta_value);
						
					$meta_value = apply_filters( 'projectmanager_textfield', $meta_value );
					$meta_value = sprintf($pattern, $meta_value, $dataset);
				} elseif ( 'email' == $meta->type && !empty($meta_value) ) {
					$meta_value = "<a href='mailto:".$this->extractURL($meta_value, 'url')."' class='projectmanager_email'>".$this->extractURL($meta_value, 'title')."</a>";
					$meta_value = apply_filters( 'projectmanager_email', $meta_value, $dataset );
					$meta_value = sprintf($pattern, $meta_value);
				} elseif ( 'date' == $meta->type ) {
					$meta_value = ( $meta_value == '0000-00-00' ) ? '' : $meta_value;
					$meta_value = ( $meta_value != '') ? mysql2date(get_option('date_format'), $meta_value ) : $meta_value;
					$meta_value = apply_filters( 'projectmanager_date', $meta_value, $dataset );
					$meta_value = sprintf($pattern, $meta_value);
				} elseif ( 'time' == $meta->type ) {
					$meta_value = mysql2date(get_option('time_format'), $meta_value);
					$meta_value = apply_filters( 'projectmanager_time', $meta_value, $dataset );
					$meta_value = sprintf($pattern, $meta_value);
				} elseif ( 'country' == $meta->type ) {
					$meta_value = __($this->getCountryName($meta_value), 'projectmanager');
				} elseif ( 'uri' == $meta->type && !empty($meta_value) ) {
					$meta_value = "<a class='projectmanager_url' href='http://".$this->extractURL($meta_value, 'url')."' target='_blank' title='".$this->extractURL($meta_value, 'title')."'>".$this->extractURL($meta_value, 'title')."</a>";
					$meta_value = apply_filters( 'projectmanager_uri', $meta_value, $dataset );
					$meta_value = sprintf($pattern, $meta_value);
				} elseif( 'image' == $meta->type && !empty($meta_value) ) {
					$meta_value = "<img class='projectmanager_image' src='".$this->getFileURL($img_size . $meta_value)."' alt='".$meta_value."' />";
					$meta_value = apply_filters( 'projectmanager_image', $meta_value, $dataset );
					$meta_value = sprintf($pattern, $meta_value);
				} elseif ( ( 'file' == $meta->type || 'video' == $meta->type ) && !empty($meta_value) ) {
					$meta_value = "<img id='fileimage".$meta->form_field_id."_".$dataset->id."' src='".$this->getFileImage($meta_value)."' alt='' />&#160;" . sprintf($pattern, "<a class='projectmanager_file ".$this->getFileType($meta_value)."' href='".$this->getFileURL($meta_value)."' target='_blank'>".$meta_value."</a>");
					$meta_value = apply_filters( 'projectmanager_file', $meta_value, $dataset );
				} elseif ( 'numeric' == $meta->type && !empty($meta_value) ) {
					$meta_value = apply_filters( 'projectmanager_numeric', $meta_value, $dataset );
					$meta_value = sprintf($pattern, $meta_value);
				} elseif ( 'currency' == $meta->type && !empty($meta_value) ) {
					if (function_exists("money_format"))
						$meta_value = money_format('%i', $meta_value);

					$meta_value = apply_filters( 'projectmanager_currency', $meta_value, $dataset );
					$meta_value = sprintf($pattern, $meta_value);
				} elseif ( 'wp_user' == $meta->type && !empty($meta_value) ) {
					$userdata = get_userdata($meta_value);
					$meta_value = $userdata->display_name;
					$meta_value = sprintf($pattern, $meta_value);
				} elseif ( !empty($meta->type) && is_array($this->getFormFieldTypes($meta->type)) ) {
					// Data is retried via callback function. Most likely a special field from LeagueManager
					$field = $this->getFormFieldTypes($meta->type);
					$args = array( 'dataset' => array( 'id' => $dataset->id, 'name' => $dataset->name ) );
					$field['args'] = array_merge( $args, $field['args'] );
					$meta_value = call_user_func_array($field['callback'], $field['args']);
				}
				

				// Generate the output
				if ( 1 == $meta->show_on_startpage || $show_all || !empty($include) ) {
					if ( $custom ) $meta->type = $custom; // restore original meta data for Thickbox
					if ( is_admin() ) {
						if (empty($meta_value))
							$meta_value = sprintf($pattern, '');

						$out .= "\n\t<td class='".$meta->type." ".$class."'>";
						//$out .= $this->getThickbox( $dataset, $meta );
						$out .= "\n\t\t".$meta_value;
						//$out .= $this->getThickboxLink( $dataset, $meta, sprintf(__('%s of %s','projectmanager'), $meta->label, $dataset->name) );
						//$out .= "<span id='loading_".$meta->form_field_id."_".$dataset->id."'></span>";
						$out .= "\n\t</td>";
					} else {
						// Don't show 'private' formfields in frontend
						if (0 == $meta->is_private) {
							if ( 'dl' == $output && !empty($meta_value) ) {
								$out .= "\n\t<dt>".$meta->label."</dt><dd>".$meta_value."</dd>";
							} elseif ( 'li' == $output && !empty($meta_value) ) {
								$out .= "\n\t<li class='".$meta->type." ".$class."'><span class='dataset_label'>".$meta->label."</span>:&#160;".$meta_value."</li>";
							} else {
								if ( !empty($output) ) $out .= "<$output class='".$meta->type." ".$class."'>";
								$out .= $meta_value;
								
								if ( !empty($output) ) $out .= "</$output>";
							}
						}
					}
				}
			}
		}}
		return $out;
	}
	function printDatasetMetaData( $dataset, $args = array() )
	{
		echo $this->getDatasetMetaData( $dataset, $args );
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
	
		$project = $this->getProject();
		$meta_value = maybe_unserialize($meta->value);

		$out = '';
		if ( is_admin() && ( ( current_user_can('edit_datasets') && $current_user->ID == $dataset->user_id ) || ( current_user_can('edit_other_datasets') ) ) ) {
			$dims = array('width' => '300', 'height' => '100');

			// Custom Formfield
			if ( is_array($this->getFormFieldTypes($meta->type)) ) {
				$field = $this->getFormFieldTypes($meta->type);
				if ( isset($field['html_type']) )
					$meta->type = $field['html_type'];
			}


			if ( 'textfield' == $meta->type || 'tinymce' == $meta->type )
				$dims = array('width' => '400', 'height' => '300');
			if ( 'checkbox' == $meta->type || 'radio' == $meta->type || 'project' == $meta->type )
				$dims = array('width' => '300', 'height' => '150');

			if ( 'file' != $meta->type && 'video' != $meta->type && 'image' != $meta->type )
				$out .= "&#160;<a class='thickbox' id='thickboxlink".$meta->form_field_id."_".$dataset->id."' href='#TB_inline&height=".$dims['height']."&width=".$dims['width']."&inlineId=datafieldwrap".$meta->form_field_id."_".$dataset->id."' title='".$title."'><img src='".PROJECTMANAGER_URL."/admin/icons/edit.gif' border='0' alt='".__('Edit')."' /></a>";
			if ( 'file' == $meta->type && 'video' != $meta->type && 'image' != $meta->type ) {
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

		$options = get_option('projectmanager');

		$value = stripslashes_deep($meta->value);
		if ( is_string($value) ) $value = htmlspecialchars($value, ENT_QUOTES);

		$out = '';
		if ( is_admin() && ( ( current_user_can('edit_datasets') && $current_user->ID == $dataset->user_id ) || ( current_user_can('edit_other_datasets') ) ) ) {
			$out .= "\n\t\t<div id='datafieldwrap".$meta->form_field_id."_".$dataset->id."' style='overfow:auto;display:none;'>";
			$out .= "\n\t\t<div class='thickbox_content'>";
			$out .= "\n\t\t\t<form name='form_field_".$meta->form_field_id."_".$dataset->id."'>";
			if ( 'text' == $meta->type || 'email' == $meta->type || 'uri' == $meta->type || 'image' == $meta->type || 'numeric' == $meta->type || 'currency' == $meta->type ) {
				$out .= "\n\t\t\t<input type='text' name='form_field_".$meta->form_field_id."_".$dataset->id."' id='form_field_".$meta->form_field_id."_".$dataset->id."' value=\"".$value."\" size='30' />";
			} elseif ( 'textfield' == $meta->type || 'tinymce' == $meta->type ) {
				$out .= "\n\t\t\t<textarea name='form_field_".$meta->form_fiield_id."_".$dataset->id."' id='form_field_".$meta->form_field_id."_".$dataset->id."' rows='10' cols='40'>".$value."</textarea>";
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
			} elseif ( 'time' == $meta->type ) {
				$out .= "\n\t\t\t<select size='1' name='form_field_".$meta->form_field_id."_".$dataset->id."_hour' id='form_field_".$meta->form_field_id."_".$dataset->id."_hour'>";
				for ( $hour = 0; $hour <= 23; $hour++ ) {
					$selected = ( $hour == substr($value, 0, 2) ) ? ' selected="selected"' : '';
					$out .= "\n\t\t\t<option value='".str_pad($hour, 2, 0, STR_PAD_LEFT)."'".$selected.">".str_pad($hour, 2, 0, STR_PAD_LEFT)."</option>";
				}
				$out .= "\n\t\t\t</select>";
				$out .= "\n\t\t\t<select size='1' name='form_field_".$meta->form_field_id."_".$dataset->id."_minute' id='form_field_".$meta->form_field_id."_".$dataset->id."_minute'>";
				for ( $minute = 0; $minute <= 59; $minute++ ) {
					$selected = ( $minute == substr($value, 3, 2) ) ? ' selected="selected"' : '';
					$out .= "\n\t\t\t<option value='".str_pad($minute, 2, 0, STR_PAD_LEFT)."'".$selected.">".str_pad($minute, 2, 0, STR_PAD_LEFT)."</option>";
				}
				$out .= "\n\t\t\t\</select>";
			}elseif ( 'wp_user' == $meta->type ) {
				$out .= wp_dropdown_users(array('name' => 'form_field_'.$meta->form_field_id.'_'.$dataset->id, 'echo' => 0, 'selected' => $value));
			} elseif ( 'project' == $meta->type ) {
				$out .= $this->getDatasetCheckboxList($options['form_field_options'][$meta->form_field_id], 'form_field_'.$meta->form_field_id."_".$dataset->id, $value);
			} elseif ( 'select' == $meta->type ) {
				$out .= $this->printFormFieldDropDown($meta->form_field_id, $value, $dataset->id, "form_field_".$meta->form_field_id."_".$dataset->id, false);
			} elseif ( 'checkbox' == $meta->type ) {
				$out .= $this->printFormFieldCheckboxList($meta->form_field_id, $value, 0, "form_field_".$meta->form_field_id."_".$dataset->id, false);
			} elseif ( 'radio' == $meta->type ) {
				$out .= $this->printFormFieldRadioList($meta->form_field_id, $value, 0, "form_field_".$meta->form_field_id."_".$dataset->id, false);
			} elseif ( is_array($this->getFormFieldTypes($meta->type)) ) {
				$field = $this->getFormFieldTypes($meta->type);
				if ( isset($field['ajax_input_callback']) ) {
					$meta->type = $field['html_type'];
					$args = array( 'dataset' => &$dataset, 'meta' => &$meta, 'value' => $value, 'name' => 'form_field_'.$meta->form_field_id.'_'.$dataset->id );
					$field['args'] = array_merge( $args, (array)$field['args'] );
					$out .= call_user_func_array($field['ajax_input_callback'], $field['args']);
				} else {
					$out .= __( 'This field does not provide AJAX editing functionality.', 'projectmanager' );
				}
			}

			if ( $meta->type != 'imageupload' ) {
				$out .= "\n\t\t\t<div style='clear: both; text-align:center; margin-top: 1em;'><input type='button' value='".__('Save')."' class='button-secondary' onclick='ProjectManager.ajaxSaveDataField(".$dataset->id.",".$meta->form_field_id.",\"".$meta->type."\"); return false;' />&#160;<input type='button' value='".__('Cancel')."' class='button' onclick='tb_remove();' /></div>";
			}

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
	 * get dataset checkbox list
	 *
	 * @param int $project_id
	 * @param string $name
	 * @param array $selected
	 * @return string
	 */
	function getDatasetCheckboxList( $project_id, $name, $selected )
	{
		global $wpdb, $projectmanager;

		$project = $this->getProject(intval($project_id));
		$name = htmlspecialchars($name);
		$datasets = $wpdb->get_results( $wpdb->prepare("SELECT `id`, `name` FROM {$wpdb->projectmanager_dataset} WHERE `project_id` = '%d' ORDER BY `name` ASC", intval($project_id)) );
		
		if ($datasets) {
			$out = "<ul class='checkboxlist'>";
			foreach ( $datasets AS $dataset ) {
				$out .= "<li><input type='checkbox' name='".$name."' id='".$name."_".$dataset->id."' value='".$dataset->id."'";
				if ( is_array($selected) && in_array($dataset->id, $selected) ) $out .= " checked='checked'";
				$out .= "/><label for='".$name."_".$dataset->id."'>".stripslashes($dataset->name)."</label>";
			}
			$out .= "</ul>";
		} else {
			$out = sprintf(__('No datasets found in Project %s', 'projectmanager'), $project->title)."</li>";
		}
		
		return $out;
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
		
		$form_id = intval($form_id);
		$name = htmlspecialchars($name);
		$formfield = $this->getFormFields($form_id);
		$options = explode(";", $formfield->options);
		
		$out = '';
		if ( count($options) > 1 ) {
			$out .= "<select size='1' class='form-input' name='".$name."' id='form_field_".$form_id."_".$dataset_id."'>";
			foreach ( $options AS $option_name ) {
				if ( $option_name == $selected )
					$out .= "<option value=\"".$option_name."\" selected='selected'>".$option_name."</option>";
				else
					$out .= "<option value=\"".$option_name."\">".$option_name."</option>"; 
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
		$form_id = intval($form_id);
		$name = htmlspecialchars($name);
		$formfield = $this->getFormFields($form_id);
		$options = explode(";", $formfield->options);
	
		if ( !is_array($selected) ) $selected = explode("|", $selected);
		$out = '';
		if ( $options != "" && count($options) > 1 ) {
			$out .= "<ul class='checkboxlist'>";
			foreach ( $options AS $id => $option_name ) {
				if ( count($selected) > 0 && in_array($option_name, $selected) )
					$out .= "<li><input type='checkbox' name='".$name."' checked='checked' value=\"".$option_name."\" id='".$name."_".$form_id."_".$id."'><label for='".$name."_".$form_id."_".$id."'> ".$option_name."</label></li>";
				else
					$out .= "<li><input type='checkbox' name='".$name."' value=\"".$option_name."\" id='".$name."_".$form_id."_".$id."'><label for='".$name."_".$form_id."_".$id."'> ".$option_name."</label></li>";
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
		$form_id = intval($form_id);
		$name = htmlspecialchars($name);
		$formfield = $this->getFormFields($form_id);
		$options = explode(";", $formfield->options);
		
		$out = '';
		if ( count($options) > 1 ) {
			$out .= "<ul class='radiolist'>";
			foreach ( $options AS $id => $option_name ) {
				if ( $option_name == $selected )
					$out .= "<li><input type='radio' name=\"".$name."\" value=\"".$option_name."\" checked='checked'  id='".$name."_".$form_id."_".$id."'><label for='".$name."_".$form_id."_".$id."'> ".$option_name."</label></li>";
				else
					$out .= "<li><input type='radio' name=\"".$name."\" value=\"".$option_name."\" id='".$name."_".$form_id."_".$id."'><label for='".$name."_".$form_id."_".$id."'> ".$option_name."</label></li>";
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
		
		$num_form_fields = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(ID) FROM {$wpdb->projectmanager_projectmeta} WHERE `project_id` = '%d' AND `show_on_startpage` = 0 AND `private` = 0", intval($this->getProjectID())) );
			
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
			$datasets = $wpdb->get_results($wpdb->prepare("SELECT `id`, `name`, `image`, `cat_ids`, `user_id` FROM {$wpdb->projectmanager_dataset} WHERE `project_id` = '%d' AND `name` REGEXP CONVERT( _utf8 '%s' USING latin1 ) ORDER BY `name` ASC", intval($this->getProjectID()), $search) );
		} elseif ( -1 == $option ) {
			$categories = explode(",", $search);
			$cat_ids = array();
			foreach ( $categories AS $category ) {
				$c = $wpdb->get_results( $wpdb->prepare ( "SELECT `term_id` FROM $wpdb->terms WHERE `name` = '%s'", trim($category) ) );
				$cat_ids[] = $c[0]->term_id;;
			}
			$sql = "SELECT `id`, `name`, `image`, `cat_ids`, `user_id` FROM {$wpdb->projectmanager_dataset} WHERE `project_id` = '".intval($this->getProjectID())."'";
				
			foreach ( $cat_ids AS $cat_id ) {
				$this->setCatID($cat_id);
				$sql .= $this->getCategorySearchString();
			}
			$this->cat_id = null;
			
			$sql .=  " ORDER BY `name` ASC;";

			$datasets = $wpdb->get_results($sql);
		} else {
			
			// Try to get country code from search string
			$formfield = $this->getFormFields(intval($option));
			if ('country' == $formfield->type) {
				$country = $wpdb->get_results($wpdb->prepare("SELECT `id`, `code`, `name` FROM {$wpdb->projectmanager_countries} WHERE `name` REGEXP CONVERT( _utf8 '%s' USING latin1 ) ORDER BY `name` ASC", $search) );
				if (count($country) > 0) {
					$search = $country[0]->code;
				} else {
					$search = "";
				}
			}
			if (empty($search)) {
				$datasets = array();
			} else {
				$sql = "SELECT t1.dataset_id AS id,
						t2.name,
						t2.image,
						t2.cat_ids
					FROM {$wpdb->projectmanager_datasetmeta} AS t1, {$wpdb->projectmanager_dataset} AS t2
					WHERE t1.value REGEXP CONVERT( _utf8 '%s' USING latin1 )
						AND t1.form_id = '%d'
						AND t1.dataset_id = t2.id
					ORDER BY t1.dataset_id ASC";
				$datasets = $wpdb->get_results($wpdb->prepare($sql, $search, intval($option)) );
			}
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
		//return ProjectManagerImage::getSupportedImageTypes();	
		return array( "jpg", "jpeg", "png", "gif" );
	}
	
	
	/**
	 * read in contents from directory, optionally recursively
	 *
	 * @param string/array $dir
	 * @param boolean $recursive
	 * @param array $files
	 * @return array of files
	 */
	function readFolder( $dir, $recursive = false, $files = array() )
	{
		if (!is_array($dir)) $dir = array($dir);

		foreach ( $dir AS $d ) {
			if ($handle = opendir($d)) {
				while (false !== ($file = readdir($handle))) {
					if ( $file != '.' && $file != '..' ) {
						if ($recursive) $file = $d."/".$file;
						
						if (is_dir($file) && $recursive) {
							$files = $this->readFolder($file, true, $files);
						} else {
							$files[] = $file;
						}
					}
				}
				closedir($handle);
			}
		}
		
		return $files;
	}


	/**
	 * load TinyMCE Editor
	 *
	 * @param none
	 * @return void
	 */
	function loadTinyMCE()
	{
		global $tinymce_version;
		
		$baseurl = includes_url('js/tinymce');

		$mce_locale = ( '' == get_locale() ) ? 'en' : strtolower( substr(get_locale(), 0, 2) ); // only ISO 639-1
		$language = $mce_locale;

		$version = apply_filters('tiny_mce_version', '');
		$version = 'ver=' . $tinymce_version . $version;

		$mce_buttons = array('bold', 'italic', 'strikethrough', '|', 'bullist', 'numlist', 'blockquote', '|', 'justifyleft', 'justifycenter', 'justifyright', '|', 'link', 'unlink',  '|', 'spellchecker', 'fullscreen', 'wp_adv' );
		$mce_buttons = implode($mce_buttons, ',');

		$mce_buttons_2 = array('formatselect', 'underline', 'justifyfull', 'forecolor', '|', 'pastetext', 'pasteword', 'removeformat', '|', 'media', 'image', 'charmap', '|', 'outdent', 'indent', '|', 'undo', 'redo', 'wp_help' );
		$mce_buttons_2 = implode($mce_buttons_2, ',');

		$plugins = array( 'safari', 'inlinepopups', 'spellchecker', 'paste', 'wordpress', 'media', 'fullscreen', 'wpeditimage', 'tabfocus' );
		$plugins = implode(",", $plugins);

		//if ( 'en' != $language )
		//include_once(ABSPATH . WPINC . '/js/tinymce/langs/wp-langs.php');
?>
		<script type="text/javascript" src="<?php bloginfo('url') ?>/wp-includes/js/tinymce/tiny_mce.js"></script>
		<script type="text/javascript">
			tinyMCE.init({
			mode: "specific_textareas",
			editor_selector: "theEditor",
			theme: "advanced",
//			skin: "wp_theme",
			theme_advanced_buttons1: "<?php echo $mce_buttons ?>",
			theme_advanced_buttons2: "<?php echo $mce_buttons_2 ?>",
			theme_advanced_buttons3: "",
			theme_advanced_buttons4: "",
			plugins: "<?php echo $plugins ?>",
			language: "<?php echo $mce_locale ?>",
	
			// Theme Options
			theme_advanced_buttons3: "",
			theme_advanced_buttons4: "",
			theme_advanced_toolbar_location: "top",
			theme_advanced_toolbar_align: "left",
			theme_advanced_statusbar_location: "bottom",
			theme_advanced_resizing: true,
			theme_advanced_resize_horizontal: "",

			relative_urls: false,
			convert_urls: false,
		});
		</script>
	<?php
		if ( 'en' != $language && isset($lang) )
			echo "<script type='text/javascript'>\n$lang\n</script>";
		else
			echo "<script type='text/javascript' src='$baseurl/langs/wp-langs-en.js?$version'></script>\n";
	
	}


	/**
	 * registrer new user
	 *
	 * @param int $user_id
	 * @return void
	 */
	function registerUser( $user_id )
	{
		require_once( PROJECTMANAGER_PATH.'/admin/admin.php' );
		$admin = new ProjectManagerAdminPanel();

		$user = new WP_User($user_id);
		if ( $user->has_cap('projectmanager_user') ) {
			foreach ( $this->getProjects() AS $project ) {
				if ( 1 == $project->profile_hook ) 
					$admin->addDataset( $project->id, $user->first_name, array(), false, $user_id );
			}
		}
	}
	
	/**
	 * print hidden fields for selection form
	 *
	 * @param none
	 * @return none
	 */
	function printSelectionFormHiddenFields()
	{
		foreach ( $matches = preg_grep("/cat_id_\d+/", array_keys($_GET)) AS $key )
			echo '<input type="hidden" name="'.$key.'" value="'.$_GET[$key].'" />';
		foreach ( $matches = preg_grep("/paged_\d+/", array_keys($_GET)) AS $key )
			echo '<input type="hidden" name="'.$key.'" value="'.$_GET[$key].'" />';
		foreach ( $matches = preg_grep("/orderby_\d+/", array_keys($_GET)) AS $key )
			echo '<input type="hidden" name="'.$key.'" value="'.$_GET[$key].'" />';
		foreach ( $matches = preg_grep("/order_\d+/", array_keys($_GET)) AS $key )
			echo '<input type="hidden" name="'.$key.'" value="'.$_GET[$key].'" />';
	}
	
	
	/**
	 * print hidden fields for search form
	 *
	 * @param none
	 * @return none
	 */
	function printSearchFormHiddenFields()
	{
		foreach ( $matches = preg_grep("/search_string_\d+/", array_keys($_POST)) AS $key )
			echo '<input type="hidden" name="'.$key.'" value="'.$_POST[$key].'" />';
		foreach ( $matches = preg_grep("/search_option_\d+/", array_keys($_POST)) AS $key )
			echo '<input type="hidden" name="'.$key.'" value="'.$_POST[$key].'" />';
	}
	
	
	/**
	 * Generate Captcha
	 *
	 * @param none
	 * @return string
	 */
	function generateCaptcha($strlen = 6, $width = 200, $height = 50, $nlines = 5, $ndots = 500)
	{
		wp_mkdir_p( $this->getCaptchaPath() );
		
		// initalize black image
		$image = imagecreatetruecolor($width, $height);
		// make white background color
		$background_color = imagecolorallocate($image, 255, 255, 255);
		imagefilledrectangle($image,0,0,$width,$height,$background_color);
		
		// generate some random lines
		$line_color = imagecolorallocate($image, 64,64,64); 
		for($i = 0; $i < $nlines; $i++) {
			imageline($image,0,rand()%$height,$width,rand()%$height,$line_color);
		}
		
		// generate some random dots
		$pixel_color = imagecolorallocate($image, 0,0,255);
		for($i = 0; $i < $ndots; $i++) {
			imagesetpixel($image,rand()%$width,rand()%$height,$pixel_color);
		} 

		// Letters and text color for captcha string
		$letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890';
		$len = strlen($letters);
		$text_color = imagecolorallocate($image, 0,0,0);
		
		// Generate random captcha string
		$code = "";
		for ($i = 0; $i < $strlen; $i++) {
			$letter = $letters[rand(0, $len-1)];
			imagestring($image, 5,  5+($i*30), rand()%($height/2), $letter, $text_color);
			$code .= $letter;
		}
		
		// generate unique captcha name
		$filename = uniqid(rand(), true) . ".png";
		
		// save captcha data in options with unique filename as key
		$options = get_option('projectmanager');
		$options['captcha'][$filename] = array('code' => $code, 'time' => time());
		update_option('projectmanager', $options);
		
		// generate png and save it
		imagepng($image, $this->getCaptchaPath($filename));
		
		return $filename;
	}
	
	
	/**
	 * clean up old files
	 *
	 * @param string $dir
	 * @param int $time time in h for captcha removal
	 * @return void
	 */
	function cleanupOldFiles($dir, $time = 24)
	{
		//$files = $this->readFolder($dir);
		$files = array_diff(scandir($dir), array('.','..'));
		
		// get current time in seconds
		$now = time();
		foreach ($files AS $file) {
			$file = $dir."/".basename($file);
			// get file modification time as unix timestamp in seconds
			$filetime = filemtime($file);
			// get difference between current time and file modification time in hours
			$diff = ($now-$filetime)/(60*60);

			// remove file if it is older than $time
			if ($diff > $time)
				@unlink($file);
		}
	}
	
	
	/**
	 * archive files in zip format
	 *
	 * @param array $files
	 * @param string $destination
	 * @param boolean $overwrite
	 */
	function createZip($files, $destination, $overwrite = true)
	{
		//if the zip file already exists and overwrite is false, return false
		if(file_exists($destination) && !$overwrite)
			return false;
	
		if (!file_exists($destination))
			$overwrite = false;
		
		$valid_files = array();
		
		// make sure that files are an array
		if (!is_array($files)) $files = array($files);
		
		foreach ($files AS $file) {
			if (file_exists($file))
				$valid_files[] = $file;
		}
		
		// Only proceed if we have valid files
		if (count($valid_files)) {
			// create zip Archive
			$zip = new ZipArchive();
			$res = $zip->open($destination, $overwrite ? ZIPARCHIVE::OVERWRITE : ZipArchive::CREATE);

			if ($res == TRUE) {
				foreach ($valid_files AS $file) {
					$zip->addFile($file, basename($file));
				}

				$zip->close();
			} else {
				return false;
			}
			
			return file_exists($destination);
		} else {
			return false;
		}
	}
	
	/**
	 * unzip media files to upload folder
	 *
	 * @param string $zip_file
	 */
	function unzipFiles($zip_file, $destination = NULL)
	{
		$zip = new ZipArchive();
		$res = $zip->open($zip_file);
		
		if (is_null($destination)) $destination = $this->getFilePath();
		
		if ($res == TRUE) {
			$zip->extractTo($destination);
			$zip->close();
			
			return true;
		} else {
			return false;
		}
	}
}
?>