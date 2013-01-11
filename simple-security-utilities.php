<?php




class Simple_Security_Utilities{

	public $setting_name;
	public $db_table;
	public $installed_db_version;
	public $current_db_version;	
	
	
	public function init(){
	
		//update the settings system
		register_activation_hook( SSec_LOADER, array(&$this, 'migrate_options') );	
		
		//add db table
		register_activation_hook( SSec_LOADER, array(&$this, 'install_db') );	
		
		//drop db table
		register_uninstall_hook( SSec_LOADER, array(&$this, 'uninstall_db') );
		//register_deactivation_hook( SSec_LOADER, array(&$this, 'uninstall_db') );	
		
		//cleanup options
		register_uninstall_hook( SSec_LOADER, array(&$this, 'uninstall_options') );	
		//register_deactivation_hook( SSec_LOADER, array(&$this, 'uninstall_options') );		
	
	}
	
	
	
	
	public function migrate_options(){
	
		//no longer used
		delete_option('simple_security_installed');
	
	
		//migrate ip blacklist
		$old_blacklist = get_option('simple_security_ip_blacklist', array());
		$new_blacklist = get_option('simple-security-ip-blacklist', array());
		update_option('simple-security-ip-blacklist', array_merge($new_blacklist, $old_blacklist));
		delete_option('simple_security_ip_blacklist');
	
	
	
	
		//migrate simple security db version
 		$old_db_ver = get_option('simple_security_db_version');
		$options = get_option($this->setting_name);
		$options['installed_db_version'] = $old_db_ver;
		update_option( $this->setting_name, $options );
		delete_option('simple_security_db_version');
		
		
		
		
		//migrate simple security plugin settings
 		$old_settings = get_option('simple_security_plugin');
		$updated_settings = array();
		foreach($old_settings as $key=>$val){
			$updated_settings[$key] = (true === $val) ? "true" : "false";
		}
		
		$options = get_option($this->setting_name);
		$options['basic_settings'] = $updated_settings;
		update_option( $this->setting_name, $options );
		delete_option('simple_security_plugin');
		
		
	}
	
	

	public function uninstall_db(){
	
		global $wpdb;
		
		//Delete table
		$sql = "DROP TABLE " . $this->db_table;
		$wpdb->query($sql);
	
	}
	
	
	public function uninstall_options(){
			
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
	
	
	
	public function install_db(){
		global $wpdb;
		
		if( $this->installed_db_version <> $this->current_db_version ){
		
			//if table does't exist, create a new one
			if( !$wpdb->get_row("SHOW TABLES LIKE '{$this->db_table}'") ){
			
				$sql = "CREATE TABLE  " . $this->db_table . "(
						id INT( 11 ) NOT NULL AUTO_INCREMENT ,
						uid INT( 11 ) NOT NULL ,
						ip VARCHAR( 100 ) NOT NULL ,
						login_result VARCHAR (1) ,
						user_login VARCHAR( 60 ) NOT NULL ,
						user_role VARCHAR( 30 ) NOT NULL ,
						time DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL ,
						data LONGTEXT NOT NULL ,
						PRIMARY KEY ( id ) ,
						INDEX ( uid, ip, login_result )
					);";
		
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql);
		
		
				$options = get_option($this->setting_name);
				$options['installed_db_version'] = $this->current_db_version;
				
				update_option( $this->setting_name, $options );
				
			}
		}
	}



}


?>
