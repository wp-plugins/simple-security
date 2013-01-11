<?php



class Simple_Security_Tools{

	
	public $db_table;
    
    public $opt_name;
	
	public $login_success 	= 0;
	
	
	public $data_labels = array();
	


	public function __construct(){
		global $wpdb;
		
		$this->data_labels = array(
			'Successful'        => __('Successful', 'simple_security'),
			'Failed'            => __('Failed', 'simple_security'),
			'Login'             => __('Login', 'simple_security'),
			'User Agent'        => __('User Agent', 'simple_security'),
			'Login Redirect'    => __('Login Redirect', 'simple_security'),
			'id'                => __('#', 'simple_security'),
			'uid'               => __('User ID', 'simple_security'),
			'user_login'        => __('Username', 'simple_security'),
			'user_role'         => __('User Role', 'simple_security'),
			'time'              => __('Time', 'simple_security'),
			'ip'                => __('IP Address', 'simple_security'),
			'login_result'      => __('Login Result', 'simple_security'),
			'data'              => __('Data', 'simple_security')
		);
	
	}


	public function init(){
		add_action( 'init', array($this, 'init_login_actions') );
		add_action( 'init', array($this, 'security_check') );
		add_action( 'init', array($this, 'blacklist_check') );
	}



	public function security_check(){
			
		global $wpdb;
		$ss_plugin = get_option($this->opt_name);
		
		$today = date_i18n('Y-m-d');
		$sql = "SELECT ip FROM " . $this->db_table . " WHERE login_result = 0 and time LIKE '$today%' GROUP BY ip HAVING COUNT(ip) >= ".$ss_plugin['basic_settings']['auto_blacklist_count'];
		$results = $wpdb->get_results($sql);
		
		if($results){
			if(!$blacklist = get_option('simple-security-ip-blacklist')){
				$blacklist = array();
			}
			
			foreach($results as $result){
				if( !in_array($result->ip, $blacklist)){
					$blacklist[] = $result->ip;
				}
			}
				
			update_option('simple-security-ip-blacklist', $blacklist);
			
		}
	}
	

	public function blacklist_check(){
		if ( isset( $_SERVER["HTTP_X_FORWARDED_FOR"] ) )
			$ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
		else
			$ip = $_SERVER["REMOTE_ADDR"];
		
		if($blocked_ip_addresses = get_option('simple-security-ip-blacklist')){
			if( in_array($ip, $blocked_ip_addresses) ){
				$ss_plugin = get_option($this->opt_name);
				$message = $ss_plugin['basic_settings']['blacklist_message'];
				die($message);
			}
		}
	}
	



	

	
	
	
	
	public function init_login_actions(){
		add_action( 'wp_login', array(&$this, 'login_success') ); 
		add_action( 'wp_login_failed', array(&$this, 'login_failed') );
	}
	
	
	
	public function login_success( $user_login ){
		$this->login_success = 1;
		$this->login_action( $user_login );
	}



	public function login_failed( $user_login ){
		$this->login_success = 0;
		$this->login_action( $user_login );
	}
	
	
		
	private function login_action($user_login){

        $userdata = get_user_by('login', $user_login);

        $uid = ($userdata && $userdata->ID) ? $userdata->ID : 0;

        $data[$this->data_labels['Login']] = ( 1 == $this->login_success ) ? $this->data_labels['Successful'] : $this->data_labels['Failed'];
        $data[$this->data_labels['User Agent']] = $_SERVER['HTTP_USER_AGENT'];
		if ( isset( $_REQUEST['redirect_to'] ) ) { $data[$this->data_labels['Login Redirect']] = $_REQUEST['redirect_to']; }
	
        $serialized_data = serialize($data);

        //get user role
        $user_role = '';
        if( $uid ){
            $user = new WP_User( $uid );
            if ( !empty( $user->roles ) && is_array( $user->roles ) ) {
                $user_role = implode(', ', $user->roles);
            }
        }


        $values = array(
            'uid'           => $uid,
            'user_login'    => $user_login,
            'user_role'     => $user_role,
            'time'          => current_time('mysql'),
            'ip'            => $_SERVER['REMOTE_ADDR'],
            'login_result'  => $this->login_success,
            'data'          => $serialized_data,
		);

        $format = array('%d', '%s', '%s', '%s', '%s', '%s', '%s');

        $this->save_data($values, $format);
    }
	




	private function save_data($values, $format){
	
        global $wpdb;

        $wpdb->insert( $this->db_table, $values, $format );
		
    }

	
	
	
}

?>