<?php
/*
Plugin Name: Simple Security
Plugin URI: http://MyWebsiteAdvisor.com/tools/wordpress-plugins/simple-security/
Description: Access Log to track Logins and Failed Login Attempts
Version: 1.0.1
Author: MyWebsiteAdvisor
Author URI: http://MyWebsiteAdvisor.com
*/

register_activation_hook(__FILE__, 'simple_security_activate');

// display error message to users

function simple_security_activate() {

	if ($_GET['action'] == 'error_scrape') {                                                                                                   
		die("Sorry, Simple Security Plugin requires PHP 5.0 or higher. Please deactivate Simple Security Plugin.");                                 
	}
	
	if ( version_compare( phpversion(), '5.0', '<' ) ) {
		trigger_error('', E_USER_ERROR);
	}
	
}

// require simple optimizer Plugin if PHP 5 installed
if ( version_compare( phpversion(), '5.0', '>=') ) {
	define('SSec_LOADER', __FILE__);

	error_reporting(E_ALL); 
	
	require_once(dirname(__FILE__) . '/access-log-admin-widget.class.php');
	require_once(dirname(__FILE__) . '/user-last-login.class.php');
	require_once(dirname(__FILE__) . '/access-log.class.php');
	require_once(dirname(__FILE__) . '/custom-list-table.class.php');
	
	require_once(dirname(__FILE__) . '/simple-security.php');
	require_once(dirname(__FILE__) . '/plugin-admin.php');
	
	if( class_exists( 'Simple_Security_Admin' ) ){
	
		$simple_security = new Simple_Security_Admin();
		
		//add db table
		register_activation_hook( __FILE__, array(&$simple_security, 'install_db') );	
		
		//drop db table, and cleanup options
		register_deactivation_hook( __FILE__, array(&$simple_security, 'uninstall_db') );	
			
	
	}

}
?>