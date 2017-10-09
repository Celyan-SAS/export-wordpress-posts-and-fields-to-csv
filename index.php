<?php
/**
 *	@package Export WordPress posts and fields to CSV
 *	@author Celyan
 *	@version 0.0.1
 */
/*
 Plugin Name: Export WordPress posts and fields to CSV
 Plugin URI: https://github.com/Celyan-SAS/export-wordpress-posts-and-fields-to-csv
 Description: WordPress plugin to export WordPress posts and their postmeta to CSV with support for ACF
 Version: 0.0.1
 Author: Yann Dubois
 Author URI: http://www.yann.com/
 License: GPL2
 */

include_once( dirname(__FILE__) . '/inc/main.php' );

new wpExportPFCSV();