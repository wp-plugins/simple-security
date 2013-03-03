<?php




class Simple_Security_Utilities{

	public $setting_name;
	public $db_table;
	public $installed_db_version;
	public $current_db_version;	
	
	
	public function init(){
			
		//add db table
		register_activation_hook( SSec_LOADER, array(&$this, 'install_db') );	
		
		//drop db table
		register_uninstall_hook( SSec_LOADER, array(&$this, 'uninstall_db') );
		
	}
	
	

	

	public function uninstall_db(){
	
		global $wpdb;
		
		//Delete table
		$sql = "DROP TABLE " . $this->db_table;
		$wpdb->query($sql);
	
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
