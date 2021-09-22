<?php
/**
 * Plugin Name:       Imageplus Site Version Control
 * Description:       Attempts to notify the developer of any version differences if setup correctly
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Harry Hindson <Imageplus>
 */

define('SITE_VERSION_CONTROL_ENTRY', dirname(__FILE__));

require SITE_VERSION_CONTROL_ENTRY . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'RestManager.php';
require SITE_VERSION_CONTROL_ENTRY . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'DataGenerator.php';
require SITE_VERSION_CONTROL_ENTRY . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'FrontendManager.php';
require SITE_VERSION_CONTROL_ENTRY . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'SiteUpdateManager.php';


global $svc_handlers;

//initialise and store all data handlers
$svc_handlers = [
    'rest_manager'     => new RestManager(), //adds the details for the rest api
    'frontend_manager' => new FrontendManager(), //adds the frontend
    'update_manager'   => new SiteUpdateManager() //adds the functions for the version manager
];
