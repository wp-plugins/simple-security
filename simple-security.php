<?php

class Simple_Security {
	
	public $version       	= '1.0.1';
	
	public $db_version 		= "1.0";
	
	public $table 			= 'simple_security_access_log';
    
    public $opt_name 		= 'simple_security';
	
	public $login_success 	= 0;
	
	public $data_labels 	= array();
	
	
	
	
	/**
	 * Array with default options
	 *
	 * @var array
	 */
	protected $_options = array(
		'simple_security_plugin' => array(
			
		)
	);
	
	/**
	 * Plugin work path
	 *
	 * @var string
	 */
	protected $_plugin_dir          = null;
	
	/**
	 * Settings url
	 *
	 * @var string
	 */
	protected $_settings_url        = null;



	    function save_data($values, $format){
	
        global $wpdb;

        $wpdb->insert( $this->table, $values, $format );
    }
	
	
	
  	
	

	
	public function uninstall_db(){
	
		global $wpdb;
		
		//Delete table
		$sql = "DROP TABLE " . $this->table;
		$wpdb->query($sql);
		
		//Delete options
		delete_option('simple_security_db_version');
		
	}
	
	
	
	
	
	public function install_db(){
		global $wpdb;
		
		if( $this->installed_ver != $this->db_version ){
		
			//if table does't exist, create a new one
			if( !$wpdb->get_row("SHOW TABLES LIKE '{$this->table}'") ){
			
				$sql = "CREATE TABLE  " . $this->table . "(
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
		
				update_option( "simple_security_db_version", $this->db_version );
			}
		}
	}
	
	
	
	
	function init_login_actions(){
		add_action( 'wp_login', array(&$this, 'login_success') ); 
		add_action( 'wp_login_failed', array(&$this, 'login_failed') );
	}
	
	
	
	function login_success( $user_login ){
		$this->login_success = 1;
		$this->login_action( $user_login );
	}



	function login_failed( $user_login ){
		$this->login_success = 0;
		$this->login_action( $user_login );
	}
	
	
	
	
	
	

	
	/**
	 * Get option by setting name with default value if option is unexistent
	 *
	 * @param string $setting
	 * @return mixed
	 */
	protected function get_option($setting) {
	    if(is_array($this->_options[$setting])) {
	        $options = array_merge($this->_options[$setting], get_option($setting));
	    } else {
	        $options = get_option($setting, $this->_options[$setting]);
	    }

	    return $options;
	}
	
	/**
	 * Get array with options
	 *
	 * @return array
	 */
	private function get_options() {
		$options = array();
		
		// loop through default options and get user defined options
		foreach($this->_options as $option => $value) {
			$options[$option] = $this->get_option($option);
		}
		
		return $options;
	}
	
	/**
	 * Merge configuration array with the default one
	 *
	 * @param array $default
	 * @param array $opt
	 * @return array
	 */
	private function mergeConfArray($default, $opt) {
		foreach($default as $option => $values)	{
			if(!empty($opt[$option])) {
				$default[$option] = is_array($values) ? array_merge($values, $opt[$option]) : $opt[$option];
				$default[$option] = is_array($values) ? array_intersect_key($default[$option], $values) : $opt[$option];
			}
		}

		return $default;
    }
	
	/**
	 * Plugin installation method
	 */
	public function activateSimpleSecurity() {
		// record install time
		add_option('simple_security_installed', time(), null, 'no');
				
		// loop through default options and add them into DB
		foreach($this->_options as $option => $value) {
			add_option($option, $value, null, 'no');	
		}
	}
	
	

	
	
}

?>