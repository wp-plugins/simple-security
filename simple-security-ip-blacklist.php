<?php


class Simple_Security_IP_Blacklist{


	public $opt_name;
	

	public function __construct() {
	
		global $wpdb;

	}
	
	
	public function init(){
	
		add_action( 'admin_menu', array($this, 'simple_security_admin_menu') );
		
	}
	
	
	public function simple_security_admin_menu(){
		
	   	$ss_options = get_option($this->opt_name);
		if($ss_options['basic_settings']['enable_ip_blacklist']){

	  	 	global $simple_security_ip_blacklist;
	  	 	$simple_security_ip_blacklist = add_submenu_page( 'users.php', __('IP Address Blacklist', 'simple_security'), __('IP Blacklist', 'simple_security'), 'list_users', 'ip_blacklist', array(&$this, 'ip_blacklist') );


			global $wp_version;
	
			if($simple_security_ip_blacklist && version_compare($wp_version, '3.3', '>=')){
				add_action("load-". $simple_security_ip_blacklist, array('Simple_Security_Plugin', 'admin_help'));	
			}

	   }
	   
    }




	function ip_blacklist(){
	
	
		$blacklist = array();		
		if(isset($_POST['action']) && "add_blacklist_ip" == $_POST['action'] && is_admin()){
			foreach($_POST['simple_security_ip_blacklist'] as $ip){
				if(!empty($ip) && filter_var($ip, FILTER_VALIDATE_IP)){
					$blacklist[] = $ip;
				}
			}
			update_option('simple-security-ip-blacklist', $blacklist);
		} 
	
		$i = 0;
		$blacklist = get_option('simple-security-ip-blacklist');
		
		
		echo "<style> 
		.form-table{ clear:left; } 
		.nav-tab-wrapper{ margin-bottom:0px; }
		</style>";
		
		echo Simple_Security_Plugin::display_social_media(); 
		
		
		echo '<div class="wrap">';
		
		
			echo '<div id="icon-users" class="icon32"><br /></div>';
			
			echo "<h2>Blocked IP Addresses</h2>";
			
			$this->show_tab_nav();
			
			
			echo "<form method='post'>";
			echo '<div id="poststuff" class="metabox-holder has-right-sidebar">';
			
				echo '<div class="inner-sidebar">';
					echo '<div id="side-sortables" class="meta-box-sortabless ui-sortable" style="position:relative;">';
					
						$sidebar = Simple_Security_Plugin::get_settings_sidebar();
						Simple_Security_Settings_Page::set_sidebar($sidebar);
						Simple_Security_Settings_Page::show_sidebar();
					
					echo '</div>';
				echo '</div>';
				
				
				
				
				echo '<div class="has-sidebar" >';			
					echo '<div id="post-body-content" class="has-sidebar-content">';
				
			
			
						echo "<div id='blacklist_form' class='postbox'>\n";
								
							echo "<h3 class='hndle'><span>Add IP Address to Blacklist</span></h3>\n";
								
							echo "<div class='inside'>\n";
						
								echo '<input type="hidden" name="page" value="access_log" />';
								echo '<input type="hidden" name="action" value="add_blacklist_ip" />';
								echo "<p>";
								echo "Add New IP Address: <input type='text' name='simple_security_ip_blacklist[]' value=''> ";
								echo "</p>";
								
								echo '<div style="padding-left: 1.5em; margin-left:5px;">';
								echo "<p class='submit'><input type='submit' class='button-primary'  value='Add IP Address To Blacklist'></p>";
								echo "</div>";
							
							echo "</div>";
						echo "</div>";
						
						
						
						echo "<div id='ip_blacklist' class='postbox'>\n";
								
							echo "<h3 class='hndle'><span>IP Address Blacklist</span></h3>\n";
								
							echo "<div class='inside'>\n";
							
								if($blacklist = get_option('simple-security-ip-blacklist')){
									foreach($blacklist as $ip){
										echo "<p>Blocked IP Address: <input type='text' name='simple_security_ip_blacklist[ $i ]' value='$ip'></p>";
										$i++;
									}
								}		
							
								echo '<div style="padding-left: 1.5em; margin-left:5px;">';
									echo "<p class='submit'><input type='submit' class='button-primary'  value='Update IP Address Blacklist'></p>";	
								echo "</div>";
								
							echo "</div>";
						echo "</div>";
						
						
						
					echo "</div>";	
				echo "</div>";
			echo "</div>";
					
					
					
				
			echo "</form>";
		
		echo '</div>';
	
	
	}



	private function show_tab_nav(){
	
		$tabs = array(
			array('id' => 'basic_settings', 'title' => 'Basic Settings', 'link' => admin_url().'options-general.php?page=simple-security-settings&tab=basic_settings'),
			array('id' => 'plugin_tutorial', 'title' => 'Plugin Tutorial Video', 'link' => admin_url().'options-general.php?page=simple-security-settings&tab=plugin_tutorial'),
			array('id' => 'plugin_tutorial', 'title' => 'Plugin Upgrades', 'link' => admin_url().'options-general.php?page=simple-security-settings&tab=upgrade_plugin'),	
			array('id' => 'ip_blacklist', 'title' => 'IP Address Blacklist', 'link' => 'users.php?page=ip_blacklist'),			
			array('id' => 'access_log', 'title' => 'Access Log', 'link' => 'users.php?page=access_log'),
		);
			
	
		echo '<h3 class="nav-tab-wrapper">';
		
		foreach( $tabs as $tab ){
			$class = ( $tab['id'] == $_GET['page'] ) ? ' nav-tab-active' : '';
			echo "<a class='nav-tab$class' href='".$tab['link']."'>".$tab['title']."</a>";
		}
		
		echo '</h3>';
		
	}
	
	

}

?>