<?php
/*
Plugin Name: Simple Security
Plugin URI: http://MyWebsiteAdvisor.com/tools/wordpress-plugins/simple-security/
Description: Access Log to track Logins and Failed Login Attempts
Version: 1.1.6
Author: MyWebsiteAdvisor
Author URI: http://MyWebsiteAdvisor.com
*/

register_activation_hook(__FILE__, 'simple_security_activate');
register_deactivation_hook(__FILE__, "simple_security_deactivate");
register_uninstall_hook(__FILE__, "simple_security_uninstall");


function simple_security_deactivate(){		
		
		//delete plugin settings
		delete_option('simple-security-settings');
		delete_option('simple-security-ip-blacklist');
	
}


function simple_security_uninstall(){
	
		//Delete old options
		delete_option('simple_security_db_version');
		delete_option('simple_security_plugin');
		delete_option('simple_security');
		delete_option('simple_security_installed');
		delete_option('simple_security_ip_blacklist');
		
		delete_transient( 'simple_security_nag' );
		
		
		//delete new options
		delete_option('simple-security-settings');
		delete_option('simple-security-ip-blacklist');
	
}



function simple_security_activate() {

	// display error message to users
	if ($_GET['action'] == 'error_scrape') {                                                                                                   
		die("Sorry, Simple Security Plugin requires PHP 5.0 or higher. Please deactivate Simple Security Plugin.");                                 
	}
	
	if ( version_compare( phpversion(), '5.0', '<' ) ) {
		trigger_error('', E_USER_ERROR);
	}
	
}



// require Plugin if PHP 5 installed
if ( version_compare( phpversion(), '5.0', '>=') ) {

	define('SSec_LOADER', __FILE__);

	//security tools
	require_once(dirname(__FILE__) . '/simple-security-tools.php');
	
	//creates access log widget in admin dashboard
	require_once(dirname(__FILE__) . '/simple-security-admin-widget.php');
	
	//tracks last login for each user
	require_once(dirname(__FILE__) . '/simple-security-last-login.php');
	
	//setup access log
	require_once(dirname(__FILE__) . '/simple-security-access-log.php');
	require_once(dirname(__FILE__) . '/simple-security-access-log-table.php');
	
	//setup ip blacklist
	require_once(dirname(__FILE__) . '/simple-security-ip-blacklist.php');
	
	//settings page template
	require_once(dirname(__FILE__) . '/simple-security-settings-page.php');
	
	//plugin utility functions: 
	//install and remove db table, options, etc...
	require_once(dirname(__FILE__) . '/simple-security-utilities.php');
		
	//main plugin class
	require_once(dirname(__FILE__) . '/simple-security-plugin.php');
	
	
	
	if( class_exists( 'Simple_Security_Plugin' ) ){
	
		$simple_security = new Simple_Security_Plugin();
		
	}
	
}

?>
