<?php


//main simple security plugin class
class Simple_Security_Plugin{


	//plugin version number
	private $version = "1.1";
	
	//holds the currently installed db version
	private $installed_db_version;
	
	//current db version for the plugin
	private $current_db_version = "1.0";
	
	
	
	//holds simple security settings page class
	private $settings_page;
	
	//holds simple security access log class
	private $access_log;
	
	//holds simple security utilities class
	private $utilities;
	
	//holds simple security tools class
	private $tools;
	
	
	
	//options are: edit, upload, link-manager, pages, comments, themes, plugins, users, tools, options-general
	private $page_icon = "users"; 	
	
	//settings page title, to be displayed in menu and page headline
	private $plugin_title = "Simple Security";
	
	//page name
	private $plugin_name = "simple-security";
	
	//will be used as option name to save all options
	private $setting_name = "simple-security-settings";
	
	//database table name used to store access log info
	//construct function should add the table prefix to this variable
	private $db_table = 'simple_security_access_log';
		


	
	//holds plugin options
	private $opt = array();




	//initialize the plugin class
	public function __construct() {
	
		global $wpdb;
		
		$this->db_table = $wpdb->prefix . $this->db_table;
		
		$this->opt = get_option($this->setting_name);
		
		$this->installed_db_version = isset($this->opt['installed_db_version']) ? $this->opt['installed_db_version'] : 0;
		
	
	
		//setup plugin utilities, create db table, remove db table, etc
		$this->utilities_init();
				
		//setup the access logs logging functions
		$this->tools_init();
		
		// setup the access log Table View
		$this->access_log_init();
			
		//setup the last login tracking	
		$this->last_login_init();
			
		
		
		//enable the admin dashboard access log widget
		add_action( 'init', array(&$this, 'setup_admin_dash') );		
		
		//initialize plugin settings
        add_action( 'admin_init', array(&$this, 'settings_page_init') );
		
		//create menu in wp admin menu
        add_action( 'admin_menu', array(&$this, 'admin_menu') );
		
		//add help menu to settings page
		add_filter( 'contextual_help', array(&$this,'admin_help'), 10, 3);	
		
		// add plugin "Settings" action on plugin list
		add_action('plugin_action_links_' . plugin_basename(SSec_LOADER), array(&$this, 'add_plugin_actions'));
		
		// add links for plugin help, donations,...
		add_filter('plugin_row_meta', array(&$this, 'add_plugin_links'), 10, 2);

    }
	
	
	
	
	//setup access log last login system
	private function last_login_init(){
	
		//setup custom column in user list to display last login
		if($this->opt['basic_settings']['enable_last_login_column']){
		
			$access_log_last_login = new Access_Log_Last_Login();
			$access_log_last_login->init();

		}
	}
	
	
	//setup access log table
	private function access_log_init(){
		if($this->opt['basic_settings']['enable_access_log']){
			global $access_log;
			$access_log = new Simple_Security_Access_Log();
			$this->access_log = $access_log;
			$this->access_log->db_table = $this->db_table;
			$this->access_log->opt_name = $this->setting_name;
			
			$this->access_log->init();
		}
	}
	
	
	//setup access log tools
	private function tools_init(){
		if($this->opt['basic_settings']['enable_access_log']){
			$this->tools = new Simple_Security_Tools;
			$this->tools->db_table = $this->db_table;
			$this->tools->opt_name = $this->setting_name;
					
			$this->tools->init();
			
		}
	}
	
	
	//setup plugin utilities, create db if necessary, etc
	private function utilities_init(){
	
		$this->utilities = new Simple_Security_Utilities;
		
		$this->utilities->setting_name 				= $this->setting_name;
		$this->utilities->db_table 					= $this->db_table;
		$this->utilities->installed_db_version 		= $this->installed_db_version;
		$this->utilities->current_db_version 		= $this->current_db_version;
		
		$this->utilities->init();
	
	}


	//setup the plugin settings page
	public function settings_page_init() {

		$this->settings_page  = new Simple_Security_Settings_Page( $this->setting_name );
		
        //set the settings
        $this->settings_page->set_sections( $this->get_settings_sections() );
        $this->settings_page->set_fields( $this->get_settings_fields() );
		$this->settings_page->set_sidebar( $this->get_settings_sidebar() );

        //initialize settings
        $this->settings_page->init();
    }








	
	public function setup_admin_dash(){
		if($this->opt['basic_settings']['enable_admin_widget']){
			if(is_admin() && current_user_can('administrator')){
				$admin_widget = new Access_Log_Admin_Widget();
				add_action('wp_dashboard_setup', array($admin_widget, 'add_admin_widget') );
			}
		}
	}
	
	
	


    /**
     * Returns all of the settings sections
     *
     * @return array settings sections
     */
    public function get_settings_sections() {
	
		$settings_sections = array(
			array(
				'id' => 'basic_settings',
				'title' => __( 'Basic Settings', $this->plugin_name )
			)
		);

								
        return $settings_sections;
    }


    /**
     * Returns all of the settings fields
     *
     * @return array settings fields
     */
    public function get_settings_fields() {
		$settings_fields = array(
			'basic_settings' => array(
				array(
                    'name' => 'enable_access_log',
                    'label' => __( 'Access Log', $this->plugin_name ),
                    'desc' => 'Enable and Display WordPress Login <a href="'.site_url().'/wp-admin/users.php?page=access_log" >Access Log</a>',
                    'type' => 'radio',
					'default' => 'true',
                    'options' => array(
                        'true' => 'Enabled',
                        'false' => 'Disabled'
                    )
                ),
				array(
                    'name' => 'enable_admin_widget',
                    'label' => __( 'Dashboard Widget', $this->plugin_name ),
                    'desc' => 'Enable and Display Access Log Widget on <a href="'.site_url().'/wp-admin/">Admin Dashboard</a>',
                    'type' => 'radio',
					'default' => 'true',
                    'options' => array(
                        'true' => 'Enabled',
                        'false' => 'Disabled'
                    )
                ),
				array(
                    'name' => 'enable_last_login_column',
                    'label' => __( 'Track Last Login', $this->plugin_name ),
                    'desc' => 'Enable and Display Last Login Tracking Column on <a href="'.site_url().'/wp-admin/users.php">Users List</a>',
                   	'type' => 'radio',
					'default' => 'true',
                    'options' => array(
                        'true' => 'Enabled',
                        'false' => 'Disabled'
                    )
                ),
				array(
					'name' => 'enable_ip_autoblock',
					'label' => __( 'IP Auto Block', $this->plugin_name ),
					'desc' => __( 'Enable Automatic IP Address Blocking', $this->plugin_name ),
					'type' => 'radio',
					'default' => 'true',
					'options' => array(
                        'true' => 'Enabled',
                        'false' => 'Disabled'
					)
                ),
				array(
                    'name' => 'enable_ip_blacklist',
                    'label' => __( 'IP Blacklist', $this->plugin_name ),
                    'desc' => 'Enable <a href="'.site_url().'/wp-admin/users.php?page=access_log">IP Address Blacklist</a>',
                    'type' => 'radio',
					'default' => 'true',
                    'options' => array(
                        'true' => 'Enabled',
                        'false' => 'Disabled'
                    )
                ),
				array(
                    'name' => 'auto_blacklist_count',
                    'label' => __( 'Allowed Attempts', $this->plugin_name ),
                    'desc' => __( 'Number of unsuccessful login attempts allowed before IP address is blocked.', $this->plugin_name ),
                    'type' => 'text',
					'default' => '5',
                    
                ),
				array(
                    'name' => 'blacklist_message',
                    'label' => __( 'Access Denied Message', $this->plugin_name ),
                    'desc' => __( 'Message to be displayed to users who have a blocked IP address.', $this->plugin_name ),
                    'type' => 'text',
					'default' => 'Access Denied '
                ),
			)
		);
		
        return $settings_fields;
    }



	

	//plugin settings page template
	public function plugin_settings_page(){
	
		echo "<style> 
		.form-table{ clear:left; } 
		.nav-tab-wrapper{ margin-bottom:0px; }
		</style>";
		
		echo $this->display_social_media(); 
		
        echo '<div class="wrap" >';
		
			echo '<div id="icon-'.$this->page_icon.'" class="icon32"><br /></div>';
			
			echo "<h2>".$this->plugin_title." Plugin Settings</h2>";
			
			$this->show_access_log_link();
			
			$this->settings_page->show_tab_nav();
			
			echo '<div id="poststuff" class="metabox-holder has-right-sidebar">';
			
				echo '<div class="inner-sidebar">';
					echo '<div id="side-sortables" class="meta-box-sortabless ui-sortable" style="position:relative;">';
					
						$this->settings_page->show_sidebar();
					
					echo '</div>';
				echo '</div>';
			
				echo '<div class="has-sidebar" >';			
					echo '<div id="post-body-content" class="has-sidebar-content">';
						
						$this->settings_page->show_settings_forms();
						
					echo '</div>';
				echo '</div>';
				
			echo '</div>';
			
        echo '</div>';
		
    }





	private function show_access_log_link(){
		if(isset($this->opt['basic_settings']['enable_access_log']) && 'true' === $this->opt['basic_settings']['enable_access_log']){
			echo "<p float='left'><a  href='".get_option('siteurl')."/wp-admin/users.php?page=access_log' >View Simple Security Access Log</a></p>";
		}
	}



   	public function admin_menu() {
		
        $this->page_menu = add_options_page( $this->plugin_title, $this->plugin_title, 'manage_options',  $this->setting_name, array($this, 'plugin_settings_page') );
    }


	public function admin_help($contextual_help, $screen_id, $screen){
	
		global $simple_security_access_log_page;
		
		if ($screen_id == $this->page_menu || $screen_id == $simple_security_access_log_page) {
				
			$support_the_dev = $this->display_support_us();
			$screen->add_help_tab(array(
				'id' => 'developer-support',
				'title' => "Support the Developer",
				'content' => "<h2>Support the Developer</h2><p>".$support_the_dev."</p>"
			));
			
			$screen->add_help_tab(array(
				'id' => 'plugin-support',
				'title' => "Plugin Support",
				'content' => "<h2>{$this->plugin_title} Support</h2><p>For {$this->plugin_title} Plugin Support please visit <a href='http://mywebsiteadvisor.com/support/' target='_blank'>MyWebsiteAdvisor.com</a></p>"
			));
			
			

			$screen->set_help_sidebar("<p>Please Visit us online for more Free WordPress Plugins!</p><p><a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/' target='_blank'>MyWebsiteAdvisor.com</a></p><br>");
			
		}
			
		

	}
	
	
	
	private function do_diagnostic_sidebar(){
	
		ob_start();
		
			echo "<p>Plugin Version: ".$this->version."</p>";
			
			echo "<p>Server OS: ".PHP_OS."</p>";
			
			echo "<p>Required PHP Version: 5.3+<br>";
			echo "Current PHP Version: " . phpversion() . "</p>";
				
			echo "<p>Memory Use: " . number_format(memory_get_usage()/1024/1024, 1) . " / " . ini_get('memory_limit') . "</p>";
			
			echo "<p>Peak Memory Use: " . number_format(memory_get_peak_usage()/1024/1024, 1) . " / " . ini_get('memory_limit') . "</p>";
			
			if(function_exists('sys_getloadavg')){
				$lav = sys_getloadavg();
				echo "<p>Server Load Average: ".$lav[0].", ".$lav[1].", ".$lav[2]."</p>";
			}
		
		return ob_get_clean();
				
	}
	
	
	
	
	
	
	private function get_settings_sidebar(){
	
		$plugin_resources = "<p><a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/simple-security/' target='_blank'>Plugin Homepage</a></p>
			<p><a href='http://mywebsiteadvisor.com/contact-us/'  target='_blank'>Plugin Support</a></p>
			<p><a href='http://mywebsiteadvisor.com/contact-us/'  target='_blank'>Contact Us</a></p>
			<p><a href='http://wordpress.org/support/view/plugin-reviews/simple-security?rate=5#postform'  target='_blank'>Rate and Review This Plugin</a></p>";
	
	
		$more_plugins = "<p><a href='http://mywebsiteadvisor.com/tools/premium-wordpress-plugins/'  target='_blank'>Premium WordPress Plugins!</a></p>
			<p><a href='http://profiles.wordpress.org/MyWebsiteAdvisor/'  target='_blank'>Free Plugins on Wordpress.org!</a></p>
			<p><a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/'  target='_blank'>Free Plugins on MyWebsiteAdvisor.com!</a></p>";
	
		$follow_us = "<p><a href='http://facebook.com/MyWebsiteAdvisor/'  target='_blank'>Follow us on Facebook!</a></p>
			<p><a href='http://twitter.com/MWebsiteAdvisor/'  target='_blank'>Follow us on Twitter!</a></p>
			<p><a href='http://www.youtube.com/mywebsiteadvisor'  target='_blank'>Watch us on YouTube!</a></p>
			<p><a href='http://MyWebsiteAdvisor.com/'  target='_blank'>Visit our Website!</a></p>";
	
		$sidebar_info = array(
			array(
				'id' => 'diagnostic',
				'title' => 'Plugin Diagnostic Check',
				'content' => $this->do_diagnostic_sidebar()		
			),
			array(
				'id' => 'resources',
				'title' => 'Plugin Resources',
				'content' => $plugin_resources	
			),
			array(
				'id' => 'more_plugins',
				'title' => 'More Plugins',
				'content' => $more_plugins	
			),
			array(
				'id' => 'follow_us',
				'title' => 'Follow MyWebsiteAdvisor',
				'content' => $follow_us	
			)
		);
		
		return $sidebar_info;

	}






	
 






	/**
	 * Add "Settings" action on installed plugin list
	 */
	public function add_plugin_actions($links) {
		array_unshift($links, '<a href="options-general.php?page=' . $this->setting_name . '">' . __('Settings') . '</a>');
		
		return $links;
	}
	
	/**
	 * Add links on installed plugin list
	 */
	public function add_plugin_links($links, $file) {
		if($file == plugin_basename(SSec_LOADER)) {
			$rate_url = 'http://wordpress.org/support/view/plugin-reviews/' . basename(dirname(__FILE__)) . '?rate=5#postform';
			$links[] = '<a href="'.$rate_url.'" target="_blank" title="Click Here to Rate and Review this Plugin on WordPress.org">Rate This Plugin</a>';
		}
		
		return $links;
	}
	
	
	public function display_support_us(){
				
		$string = '<p><b>Thank You for using the '.$this->plugin_title.' Plugin for WordPress!</b></p>';
		$string .= "<p>Please take a moment to <b>Support the Developer</b> by doing some of the following items:</p>";
		
		$rate_url = 'http://wordpress.org/support/view/plugin-reviews/' . basename(dirname(__FILE__)) . '?rate=5#postform';
		$string .= "<li><a href='$rate_url' target='_blank' title='Click Here to Rate and Review this Plugin on WordPress.org'>Click Here</a> to Rate and Review this Plugin on WordPress.org!</li>";
		
		$string .= "<li><a href='http://facebook.com/MyWebsiteAdvisor' target='_blank' title='Click Here to Follow us on Facebook'>Click Here</a> to Follow MyWebsiteAdvisor on Facebook!</li>";
		$string .= "<li><a href='http://twitter.com/MWebsiteAdvisor' target='_blank' title='Click Here to Follow us on Twitter'>Click Here</a> to Follow MyWebsiteAdvisor on Twitter!</li>";
		$string .= "<li><a href='http://mywebsiteadvisor.com/tools/premium-wordpress-plugins/' target='_blank' title='Click Here to Purchase one of our Premium WordPress Plugins'>Click Here</a> to Purchase Premium WordPress Plugins!</li>";
	
		return $string;
	}
	
	
	
	
	
	public function display_social_media(){
	
		$social = '<style>
	
		.fb_edge_widget_with_comment {
			position: absolute;
			top: 0px;
			right: 200px;
		}
		
		</style>
		
		<div  style="height:20px; vertical-align:top; width:25%; float:right; text-align:right; margin-top:5px; padding-right:16px; position:relative;">
		
			<div id="fb-root"></div>
			<script>(function(d, s, id) {
			  var js, fjs = d.getElementsByTagName(s)[0];
			  if (d.getElementById(id)) return;
			  js = d.createElement(s); js.id = id;
			  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId=253053091425708";
			  fjs.parentNode.insertBefore(js, fjs);
			}(document, "script", "facebook-jssdk"));</script>
			
			<div class="fb-like" data-href="http://www.facebook.com/MyWebsiteAdvisor" data-send="true" data-layout="button_count" data-width="450" data-show-faces="false"></div>
			
			
			<a href="https://twitter.com/MWebsiteAdvisor" class="twitter-follow-button" data-show-count="false"  >Follow @MWebsiteAdvisor</a>
			<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
		
		
		</div>';
		
		return $social;

	}	

	
	


}
 
?>
