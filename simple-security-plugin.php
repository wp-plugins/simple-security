<?php


//main simple security plugin class
class Simple_Security_Plugin{


	private $debug = false;

	//plugin version number
	private static $version = "1.1.6";
	
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
	private static $plugin_title = "Simple Security";
	
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
			
		//setup IP address blacklist	
		$this->blacklist_init();
		
		
		
		//enable the admin dashboard access log widget
		add_action( 'init', array(&$this, 'setup_admin_dash') );		
		
		//initialize plugin settings
        add_action( 'admin_init', array(&$this, 'settings_page_init') );
		
		//create menu in wp admin menu
        add_action( 'admin_menu', array(&$this, 'admin_menu') );
		
		//add help menu to settings page
		//add_filter( 'contextual_help', array(&$this,'admin_help'), 10, 3);	
		
		// add plugin "Settings" action on plugin list
		add_action('plugin_action_links_' . plugin_basename(SSec_LOADER), array(&$this, 'add_plugin_actions'));
		
		// add links for plugin help, donations,...
		add_filter('plugin_row_meta', array(&$this, 'add_plugin_links'), 10, 2);

    }
	
	
	
	
	//setup access log last login system
	private function last_login_init(){
	
		//setup custom column in user list to display last login
		if("true" == $this->opt['basic_settings']['enable_last_login_column']){
		
			$access_log_last_login = new Access_Log_Last_Login();
			$access_log_last_login->init();

		}
	}
	
	
	//setup access log table
	private function access_log_init(){
		if("true" == $this->opt['basic_settings']['enable_access_log']){
			global $access_log;
			$access_log = new Simple_Security_Access_Log();
			$this->access_log = $access_log;
			$this->access_log->db_table = $this->db_table;
			$this->access_log->opt_name = $this->setting_name;
			
			if(array_key_exists("clear_access_log", $_POST)){
				
				add_action('admin_init', array($this->access_log, 'clear_access_log'));
				//$this->access_log->clear_access_log();
			}
			
			if(array_key_exists("download_access_log", $_POST)){
				$this->access_log->download_access_log();
				die();
			}			
			
			$this->access_log->init();
		}
	}
	
	//setup access log tools
	private function blacklist_init(){
		if("true" == $this->opt['basic_settings']['enable_ip_blacklist']){
			$this->blacklist = new Simple_Security_IP_Blacklist;
			$this->blacklist->opt_name = $this->setting_name;
			
			$this->blacklist->init();
		}
	}
	
	
	//setup access log tools
	private function tools_init(){
		if("true" == $this->opt['basic_settings']['enable_access_log']){
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
		
		$this->settings_page->extra_tabs = array(
			array('id'=>'ip_blacklist', 'title'=>'IP Address Blacklist', 'link' => admin_url()."users.php?page=ip_blacklist"),		
			array('id'=>'access_log', 'title'=>'Access Log', 'link' => admin_url()."users.php?page=access_log")
		);
				
        //set the settings
        $this->settings_page->set_sections( $this->get_settings_sections() );
        $this->settings_page->set_fields( $this->get_settings_fields() );
		$this->settings_page->set_sidebar( $this->get_settings_sidebar() );

		$this->build_optional_tabs();

        //initialize settings
        $this->settings_page->init();
    }








	
	public function setup_admin_dash(){
		if('true' == $this->opt['basic_settings']['enable_admin_widget']){
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
					//'default' => 'true',
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
					//'default' => 'true',
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
					//'default' => 'true',
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
					//'default' => 'true',
					'options' => array(
                        'true' => 'Enabled',
                        'false' => 'Disabled'
					)
                ),
				array(
                    'name' => 'enable_ip_blacklist',
                    'label' => __( 'IP Blacklist', $this->plugin_name ),
                    'desc' => 'Enable <a href="'.site_url().'/wp-admin/users.php?page=ip_blacklist">IP Address Blacklist</a>',
                    'type' => 'radio',
					//'default' => 'true',
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
			
			echo "<h2>".self::$plugin_title." Plugin Settings</h2>";
			
			//$this->show_access_log_link();
			
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
		
        $this->page_menu = add_options_page( self::$plugin_title, self::$plugin_title, 'manage_options',  $this->setting_name, array($this, 'plugin_settings_page') );
		
		global $wp_version;

   		if($this->page_menu && version_compare($wp_version, '3.3', '>=')){
			add_action("load-". $this->page_menu, array($this, 'admin_help'));	
		}
    }


	//public function admin_help($contextual_help, $screen_id, $screen){
	public function admin_help(){
		
		$screen = get_current_screen();
		
		//global $simple_security_access_log_page;
		//global $simple_security_ip_blacklist;
		
	//	if ($screen_id == $this->page_menu || $screen_id == $simple_security_access_log_page || $screen_id == $simple_security_ip_blacklist) {
				
			$support_the_dev = self::display_support_us();
			$screen->add_help_tab(array(
				'id' => 'developer-support',
				'title' => "Support the Developer",
				'content' => "<h2>Support the Developer</h2><p>".$support_the_dev."</p>"
			));
			
			
			$video_code = "<style>
			.videoWrapper {
				position: relative;
				padding-bottom: 56.25%; /* 16:9 */
				padding-top: 25px;
				height: 0;
			}
			.videoWrapper iframe {
				position: absolute;
				top: 0;
				left: 0;
				width: 100%;
				height: 100%;
			}
			</style>";
		
			$video_id = "pMZ5oCUuX7k";
			$video_code .= '<div class="videoWrapper"><iframe width="640" height="360" src="http://www.youtube.com/embed/'.$video_id.'?rel=0&vq=hd720" frameborder="0" allowfullscreen></iframe></div>';
			$screen->add_help_tab(array(
				'id' => 'tutorial-video',
				'title' => "Tutorial Video",
				'content' => "<h2>".self::$plugin_title." Tutorial Video</h2><p>$video_code</p>"
			));
			
			
			$screen->add_help_tab(array(
				'id' => 'plugin-support',
				'title' => "Plugin Support",
				'content' => "<h2>".self::$plugin_title." Support</h2><p>For ".self::$plugin_title." Plugin Support please visit <a href='http://mywebsiteadvisor.com/support/' target='_blank'>MyWebsiteAdvisor.com</a></p>"
			));
			
			
			$screen->add_help_tab(array(
				'id' => 'upgrade_plugin',
				'title' => 'Plugin Upgrades', 
				'content' => self::get_plugin_upgrades()		
			));	

			$screen->set_help_sidebar("<p>Please Visit us online for more Free WordPress Plugins!</p><p><a href='http://mywebsiteadvisor.com/plugins/' target='_blank'>MyWebsiteAdvisor.com</a></p><br>");
			
		//}
			
		

	}
	
	
	
	private function do_diagnostic_sidebar(){
	
		ob_start();
		
			echo "<p>Plugin Version: ". self::get_version()  ."</p>";
			
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
	
	
	public function get_version(){
		return self::$version;
	}
	
	
	
	public function get_settings_sidebar(){
	
		$plugin_resources = "<p><a href='http://mywebsiteadvisor.com/plugins/simple-security/' target='_blank'>Plugin Homepage</a></p>
			<p><a href='http://mywebsiteadvisor.com/learning/video-tutorials/simple-security-tutorial/'  target='_blank'>Plugin Tutorial</a></p>
			<p><a href='http://mywebsiteadvisor.com/contact-us/'  target='_blank'>Plugin Support</a></p>
			<p><a href='http://mywebsiteadvisor.com/contact-us/'  target='_blank'>Contact Us</a></p>
			<p><a href='http://wordpress.org/support/view/plugin-reviews/simple-security?rate=5#postform'  target='_blank'>Rate and Review This Plugin</a></p>";
	
	
		$more_plugins = "<p><a href='http://mywebsiteadvisor.com/plugins/'  target='_blank'>Premium WordPress Plugins!</a></p>
			<p><a href='http://profiles.wordpress.org/MyWebsiteAdvisor/'  target='_blank'>Free Plugins on Wordpress.org!</a></p>
			<p><a href='http://mywebsiteadvisor.com/plugins/'  target='_blank'>Free Plugins on MyWebsiteAdvisor.com!</a></p>";
	
		$follow_us = "<p><a href='http://facebook.com/MyWebsiteAdvisor/'  target='_blank'>Follow us on Facebook!</a></p>
			<p><a href='http://twitter.com/MWebsiteAdvisor/'  target='_blank'>Follow us on Twitter!</a></p>
			<p><a href='http://www.youtube.com/mywebsiteadvisor'  target='_blank'>Watch us on YouTube!</a></p>
			<p><a href='http://MyWebsiteAdvisor.com/'  target='_blank'>Visit our Website!</a></p>";
	
	
		$upgrade = "<p>
			<a href='http://mywebsiteadvisor.com/plugins/simple-security/'  target='_blank'>Upgrade to Simple Security Ultra!</a><br />
			<br />
			<b>Features:</b><br />
			-Email Alert Notifications<br />
			-Blocked IP Address Alert<br />
			-Failed Login Attempt Alert<br />
			-Succecssful Login Attempt Alert<br />
			-Priority Support License</br>
			</p>";
			
		$sidebar_info = array(
			array(
				'id' => 'diagnostic',
				'title' => 'Plugin Diagnostic Check',
				'content' => self::do_diagnostic_sidebar()		
			),
			array(
				'id' => 'resources',
				'title' => 'Plugin Resources',
				'content' => $plugin_resources	
			),
			array(
				'id' => 'upgrade',
				'title' => 'Plugin Upgrades',
				'content' => $upgrade	
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






	//build optional tabs, using debug tools class worker methods as callbacks
	private function build_optional_tabs(){
		if(true === $this->debug){
		
			//general debug settings
			$plugin_debug = array(
				'id' => 'plugin_debug',
				'title' => __( 'Plugin Settings Debug', $this->plugin_name ),
				'callback' => array($this, 'show_plugin_settings')
			);
	
			$this->settings_page->add_section( $plugin_debug );
			
		}
		
		$plugin_tutorial = array(
			'id' => 'plugin_tutorial',
			'title' => __( 'Plugin Tutorial Video', $this->plugin_name ),
			'callback' => array(&$this, 'show_plugin_tutorual')
		);
		$this->settings_page->add_section( $plugin_tutorial );
		
		
		$upgrade_plugin = array(
			'id' => 'upgrade_plugin',
			'title' => __( 'Plugin Upgrades', $this->plugin_name ),
			'callback' => array(&$this, 'show_plugin_upgrades')
		);
		$this->settings_page->add_section( $upgrade_plugin );
	}
 
 
 
 
 
 		
	public function get_plugin_upgrades(){
		ob_start();
		self::show_plugin_upgrades();
		return ob_get_clean();	
	}
	
	
	public function show_plugin_upgrades(){
		
		$html = "<style>
			ul.upgrade_features li { list-style-type: disc; }
			ul.upgrade_features  { margin-left:30px;}
		</style>";
		
		$html .= "<script>
		
			function  simple_security_upgrade(){
        		window.open('http://mywebsiteadvisor.com/plugins/simple-security/');
        		return false;
			}
			
			
			
			function  try_simple_optimizer(){
        		window.open('http://wordpress.org/extend/plugins/simple-optimizer/');
        		return false;
			}
			
			function  try_simple_backup(){
        		window.open('http://wordpress.org/extend/plugins/simple-backup/');
        		return false;
			}	
			
			
			
			function  simple_backup_learn_more(){
        		window.open('http://mywebsiteadvisor.com/plugins/simple-backup/');
        		return false;
			}	
			
			function  simple_optimizer_learn_more(){
        		window.open('http://mywebsiteadvisor.com/plugins/simple-optimizer/');
        		return false;
			}				

			function  simple_security_learn_more(){
        		window.open('http://mywebsiteadvisor.com/plugins/simple-security/');
        		return false;
			}			
			
				
		</script>";
		

		$html .= "</form><h2>Upgrade to Simple Security Ultra Today!</h2>";
		
		$html .= "<p><b>Premium Features include:</b></p>";
		
		$html .= "<ul class='upgrade_features'>";	
		$html .= "<li>Configurable email alert notifications when selected conditions are met</li>";
		$html .= "<li>Receive an optional email alert when new IP addresses are added to Blacklist</li>";		
		$html .= "<li>Receive an optional email alert after a failed login attempt</li>";	
		$html .= "<li>Receive an optional email alert after a successful login</li>";			
		$html .= "<li>Lifetime Priority Support and Update License</li>";
		$html .= "</ul>";
		
		$html .=  '<div style="padding-left: 1.5em; margin-left:5px;">';
		$html .= "<p class='submit'>";
		$html .= "<input type='submit' class='button-primary' value='Upgrade to Simple Security Ultra &raquo;' onclick='return simple_security_upgrade()'>&nbsp;";
		$html .= "<input type='submit' class='button-secondary' value='Learn More &raquo;' onclick='return simple_security_learn_more()'>";
		$html .= "</p>";
		$html .=  "</div>";


		$html .= "<hr>";
		
		
		$html .= "<h2>Also Try Simple Optimizer!</h2>";
		$html .= "<p>Simple Optimizer can help keep your website running quickly and smoothly by cleaning up the WordPress Database of un-used and un-necessary information.</p>";
		
		$html .=  '<div style="padding-left: 1.5em; margin-left:5px;">';
		$html .= "<p class='submit'>";
		$html .= "<input type='submit' class='button-primary' value='Try Simple Optimizer &raquo;' onclick='return try_simple_optimizer()'>&nbsp;";
		$html .= "<input type='submit' class='button-secondary' value='Learn More &raquo;' onclick='return simple_optimizer_learn_more()'>";
		$html .= "</p>";	
		$html .=  "</div>";
	
	
		$html .= "<hr>";
		
		
		$html .= "<h2>Also Try Simple Backup!</h2>";
		$html .= "<p>Simple Backup can quickly and easily create a Full Backup of your WordPress Database and Website Files!</p>";
				
		$html .=  '<div style="padding-left: 1.5em; margin-left:5px;">';
		$html .= "<p class='submit'>";
		$html .= "<input type='submit' class='button-primary' value='Try Simple Backup &raquo;' onclick='return try_simple_backup()'>&nbsp;";
		$html .= "<input type='submit' class='button-secondary' value='Learn More &raquo;' onclick='return simple_backup_learn_more()'>";
		$html .= "</p>";	
		$html .=  "</div>";


		
		echo $html;
	}


 
 
 





	// displays the plugin options array
	public function show_plugin_settings(){
				
		echo "<pre>";
			print_r(get_option($this->setting_name));
		echo "</pre>";
			
	}


	public function show_plugin_tutorual(){
	
		echo "<style>
		.videoWrapper {
			position: relative;
			padding-bottom: 56.25%; /* 16:9 */
			padding-top: 25px;
			height: 0;
		}
		.videoWrapper iframe {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
		}
		</style>";

		$video_id = "pMZ5oCUuX7k";
		echo sprintf( '<div class="videoWrapper"><iframe width="640" height="360" src="http://www.youtube.com/embed/%1$s?rel=0&vq=hd720" frameborder="0" allowfullscreen ></iframe></div>', $video_id);
		
	
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
			$upgrade_url = 'http://mywebsiteadvisor.com/plugins/simple-security/';
			$links[] = '<a href="'.$upgrade_url.'" target="_blank" title="Click Here to Upgrade this Plugin!">Upgrade Plugin</a>';
			
			$tutorial_url = 'http://mywebsiteadvisor.com/learning/video-tutorials/simple-security-tutorial/';
			$links[] = '<a href="'.$tutorial_url.'" target="_blank" title="Click Here to View the Plugin Video Tutorial!">Tutorial Video</a>';
			
			$rate_url = 'http://wordpress.org/support/view/plugin-reviews/' . basename(dirname(__FILE__)) . '?rate=5#postform';
			$links[] = '<a href="'.$rate_url.'" target="_blank" title="Click Here to Rate and Review this Plugin on WordPress.org">Rate This Plugin</a>';
		}
		
		return $links;
	}
	
	
	public function display_support_us(){
				
		$string = '<p><b>Thank You for using the '.self::$plugin_title.' Plugin for WordPress!</b></p>';
		$string .= "<p>Please take a moment to <b>Support the Developer</b> by doing some of the following items:</p>";
		
		$rate_url = 'http://wordpress.org/support/view/plugin-reviews/' . basename(dirname(__FILE__)) . '?rate=5#postform';
		$string .= "<li><a href='$rate_url' target='_blank' title='Click Here to Rate and Review this Plugin on WordPress.org'>Click Here</a> to Rate and Review this Plugin on WordPress.org!</li>";
		
		$string .= "<li><a href='http://facebook.com/MyWebsiteAdvisor' target='_blank' title='Click Here to Follow us on Facebook'>Click Here</a> to Follow MyWebsiteAdvisor on Facebook!</li>";
		$string .= "<li><a href='http://twitter.com/MWebsiteAdvisor' target='_blank' title='Click Here to Follow us on Twitter'>Click Here</a> to Follow MyWebsiteAdvisor on Twitter!</li>";
		$string .= "<li><a href='http://mywebsiteadvisor.com/plugins/' target='_blank' title='Click Here to Purchase one of our Premium WordPress Plugins'>Click Here</a> to Purchase Premium WordPress Plugins!</li>";
	
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
