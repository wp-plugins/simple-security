<?php

//if( ! class_exists( 'WP_List_Table' ) ) {
	//require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
//}

		
//class Access_Log_Admin_Widget extends WP_List_Table {
class Access_Log_Admin_Widget  {
			
	//function __construct(){
		// $this->prepare_items();	 
	//}

	function add_admin_widget() {
		wp_add_dashboard_widget('simple_security_admin_widget', 'Simple Security Access Log', array($this, 'display_admin_widget') );	
	} 

	function display_admin_widget() {
		$this->build_admin_widget_table();
	} 
	
	
	
	
	function build_admin_widget_table(){
		
		global $access_log, $wpdb;
		
		echo "<h3>Recent Login Attempts</h3>";
		
	
		$sql = "SELECT * FROM {$access_log->db_table} ORDER BY time DESC LIMIT 5";
		$result = $wpdb->get_results($sql, ARRAY_A );
		
		
		$this->build_html_table($result);
		
		
	}
	
	
	
	function build_html_table($result){
		
		$i =1;
		
		$columns = array(
			'user_login'    => 'Username',
			'login_result'	=> 'Login Result',
			'ip' 			=> 'IP Address',
			'time'      	=> 'Date/Time'
		);
		
		
		echo "<table class='wp-list-table widefat fixed'>";
			
			printf("<tr><th>%s</th><th>%s</th><th>%s</th><th>%s</th></tr>", $columns['user_login'], $columns['login_result'], $columns['ip'], $columns['time']);
		
			foreach($result as $row){
				
				$alt = $i%2 ? "class='alternate'" : "";
				
				$login_result = $row['login_result'] ? "Success" : "Failed";
				
				printf("<tr %s><td>%s</td><td>%s</td><td nowrap>%s</td><td>%s</td></tr>", $alt, $row['user_login'], $login_result, $row['ip'], $row['time']);
			
				$i++;
			}
		
		echo "</table>";
		
		
		echo '<p><a href="'.admin_url('users.php?page=access_log').'">View Full Access Log</a></p>';
		
	}
	
	
	
	
	


	/**


	function build_admin_widget_table(){
		$parent_args = array(
            'singular'  => 'login',    //singular name of the listed records
            'plural'    => 'logins',   	//plural name of the listed records
            'ajax'      => false        //does this table support ajax?
    	);
		
		
		
		//parent::__construct($parent_args);
		//$this->extra_tablenav('bottom');
		echo "<style>
			span.failure {color:red; font-weight: bold;}
			span.success {color:green; font-weight: bold;}
			table.logins tbody tr:hover {background-color: rgba(255,255,0, 0.1);}
		</style>";
	
		//echo '<a href="'.get_option('siteurl').'/wp-admin/users.php?page=access_log"><p class="sub">Recent Logins</p></a>'; 
		
	  	//$this->display(); 
							
	}


	function extra_tablenav($which){
		if ( 'bottom' == $which ){
			$link = "<div class='alignleft actions'>";
			$link .= '<a href="'.get_option('siteurl').'/wp-admin/users.php?page=access_log">View Full Access Log</a>';
			$link .= "</div>";
			echo $link;
		}
		
		if ( 'top' == $which ){
			$link = "<div class='alignleft actions'>";
			$link .= '<a href="'.get_option('siteurl').'/wp-admin/users.php?page=access_log">View Full Access Log</a>';
			$link .= "</div>";
			echo $link;
		}
		
		
	}
	
	
	function get_widget_data(){
		global $access_log, $wpdb;
		$sql = "SELECT * FROM {$access_log->db_table} ORDER BY time DESC";
		$result = $wpdb->get_results($sql, ARRAY_A );
		return $result;
	}


	function get_columns(){
		
	  	return $columns;
	}
	


	function prepare_items() {
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = array();
		$this->_column_headers = array($columns, $hidden, $sortable);
		$this->items = $this->get_widget_data();
		

		$per_page = 5;
		$current_page = $this->get_pagenum();
		$total_items = count($this->items);
		
		$this->found_data = array_slice($this->items,(($current_page-1)*$per_page),$per_page);
		$this->set_pagination_args( array(
			'total_items' => $total_items,                  //WE have to calculate the total number of items
			'per_page'    => $per_page                     //WE have to determine how many items to show on a page
		) );
		$this->items = $this->found_data;
		


	}


	function column_default( $item, $column_name ) {
	  switch( $column_name ) { 
		case 'ip':
			return $item[ $column_name ];
			break;
			
		case 'user_login':
			//$id=$item['uid'];
			$name = $item[ $column_name ];
			$link = get_option('siteurl').'/wp-admin/users.php?page=access_log&filter='.$name;
			return "<a href='$link'>$name</a>";
			break;
			
		case 'login_result':
			return (1 == $item[ $column_name ]) ? '<span class="success">Successful</span>' : '<span class="failure">Failed</span>';
			break;
			
		case 'time':
		  return $item[ $column_name ];
		  break;
		  
	  }
	}
	
	
	**/

}

?>
