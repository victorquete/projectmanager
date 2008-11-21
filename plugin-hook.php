<?php
/*
Plugin Name: ProjectManager
Description: This Plugin can be used to manage several different types of projects with redundant data. This could be athlet portraits, DVD database, architect projects. You can define different form field types and groups to sort your project entries.
Plugin URI: http://wordpress.org/extend/plugins/projectmanager/
Version: 1.1
Author: Kolja Schleich


Copyright 2007-2008  Kolja Schleich  (email : kolja.schleich@googlemail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
if ( !defined( 'WP_CONTENT_URL' ) )
	define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
if ( !defined( 'WP_PLUGIN_URL' ) )
	define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
if ( !defined( 'WP_CONTENT_DIR' ) )
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
if ( !defined( 'WP_PLUGIN_DIR' ) )
	define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
	
define( 'PROJECTMANAGER_VERSION', '1.0.2' );
define( 'PROJECTMANAGER_URL', WP_PLUGIN_URL.'/projectmanager' );

include_once( 'projectmanager.php' );
include_once( 'lib/pagination.inc.php' );
include_once( 'lib/thumbnail.inc.php' );

$projectmanager = new WP_ProjectManager();

// Load textdomain for translation
load_plugin_textdomain( 'projectmanager', $path = PLUGINDIR.'/projectmanager/languages' );

register_activation_hook(__FILE__, array(&$projectmanager, 'init') );

// Actions
//add_action( 'admin_head', 'ob_start');
//add_action( 'admin_footer', 'ob_end_flush');
add_action( 'admin_head', array(&$projectmanager, 'addHeaderCode') );
add_action( 'wp_head', array(&$projectmanager, 'addHeaderCode') );
add_action( 'admin_menu', array(&$projectmanager, 'addAdminMenu') );
add_action( 'widgets_init', array(&$projectmanager, 'initWidget') );
	
// Filters
add_filter( 'the_content', array(&$projectmanager, 'insert') );

// TinyMCE Buttons
add_action( 'init', array(&$projectmanager, 'addTinyMCEButton') );
// Modify the version when tinyMCE plugins are changed.
add_filter('tiny_mce_version', array(&$projectmanager, 'changeTinyMCEVersion') );


if ( function_exists('register_uninstall_hook') )
	register_uninstall_hook(__FILE__, array(&$leaguemanager, 'uninstall'));

// Uninstall Plugin
if ( version_compare($wp_version, '2.7-hemorrhage', '<') && isset( $_GET['projectmanager']) AND 'uninstall' ==  $_GET['projectmanager'] AND ( isset($_GET['delete_plugin']) AND 1 == $_GET['delete_plugin'] ) )
	$leaguemanager->uninstall();
?>
