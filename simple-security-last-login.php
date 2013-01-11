<?php


//class creates an extra column in the users list to display last login time and date
class Access_Log_Last_Login{

	public function init(){
	
		add_filter( 'manage_users_columns', array($this, 'add_last_login_column') );
		add_action( 'manage_users_custom_column', array($this, 'add_last_login_column_value'), 10, 3 );
		add_action( 'wp_login', array($this, 'insert_last_login') );
		
	}

	// insert the last login date for each user
	public function insert_last_login( $login ) {
		global $user_id;
		//$user = get_userdatabylogin( $login );
		$user = get_user_by('login', $login);
		//update_user_meta( $user->ID, 'last_login', date( 'Y-m-d H:i:s' ) );
		update_user_meta( $user->ID, 'last_login', date_i18n('Y-m-d H:i:s') );
		//get_the_date()
	}
	

	// add a new "Last Login" user column
	public function add_last_login_column( $columns ) {
		$columns['last_login'] = __( 'Last Login', 'last_login' );
		return $columns;
	}
	

	// add the "Last Login" user data to the new column
	public function add_last_login_column_value( $value, $column_name, $user_id ) {
		$user = get_userdata( $user_id );
		if ( 'last_login' == $column_name && $user->last_login ){
			$value = date( 'm/d/Y g:ia', strtotime( $user->last_login ) );
			
			$access_log_url = get_option('siteurl')."/wp-admin/users.php?page=access_log&filter=".$user->user_login;
			
			$value = "<a href='" . $access_log_url . "'>".$value."</a>";
		}	
		return $value;
	}
	
}


?>