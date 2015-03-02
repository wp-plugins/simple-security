<?php

//simple security access log

class Simple_Security_Access_Log{


	public $db_table;
    
    public $opt_name;
	
	
	public $login_success = 0;
	
	public $data_labels = array();
	
	
	
	
	public function __construct() {
	
		global $wpdb;
		
		//For translation purposes
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
	
		add_action( 'admin_head', array($this, 'admin_header') );
		add_action( 'admin_head', array($this, 'screen_options') );
		
		add_action( 'admin_menu', array($this, 'simple_security_admin_menu') );
		
	}
	
	
	function simple_security_admin_menu(){
		global $simple_security_access_log_page;
		
       	$simple_security_access_log_page = add_submenu_page( 'users.php', __('Simple Access Log', 'simple_security'), __('Access Log', 'simple_security'), 'list_users', 'access_log', array(&$this, 'log_manager') );
	   
		global $wp_version;

   		if($simple_security_access_log_page && version_compare($wp_version, '3.3', '>=')){
			add_action("load-". $simple_security_access_log_page, array('Simple_Security_Plugin', 'admin_help'));	
		}	   
    }
	
	
	
	function log_manager(){

        $log_table = $this->log_table;

        $log_table->items = $this->log_get_data();
        $log_table->prepare_items();


		echo Simple_Security_Plugin::display_social_media(); 
		
		echo '<div class="wrap">';
		
			echo '<div id="icon-users" class="icon32"><br /></div>';
			
            echo '<h2>' . __('Simple Security Access Log', 'simple-security') . '</h2>';
			
			$this->show_tab_nav();
			
			//echo "<p><a href='".get_option('siteurl')."/wp-admin/options-general.php?page=simple-security-settings'>Simple Security Plugin Settings</a></p>";
			
            echo '<div class="tablenav top">';
                echo '<div class="alignleft actions">';
                    echo $this->date_filter();
                echo '</div>';

                $username = ( isset($_GET['filter']) ) ? esc_attr($_GET['filter']) : false;
                echo '<form method="get" class="alignright">';
                    echo '<p class="search-box">';
                        echo '<input type="hidden" name="page" value="access_log" />';
                        echo '<label>' . __('Username:', 'simple-security') . ' </label>';
						echo '<input type="text" name="filter" class="filter-username" value="' . $username . '" />';
						echo '<input class="button" type="submit" value="' . __('Filter User', 'simple-security') . '" />';
                        echo '<br />';
                    echo '</p>';
                echo '</form>';
            echo '</div>';
			
            echo '<div class="tablenav top">';
				echo '<div class="alignleft actions">';
						$log_table->views();
				echo '</div>';

                echo '<div class="alignright actions">';
                $mode = ( isset($_GET['mode']) ) ? esc_attr($_GET['mode']) : false;
                $log_table->view_switcher($mode);
                echo '</div>';
            echo '</div>';

	        $log_table->display();
			
			
            echo '<div class="tablenav bottom">';
				echo '<div class="alignleft actions">';
					echo '<form onsubmit="return confirm(\'Do you really want to Clear the Access Log?\r\nThis action can not be reversed!\');" method="post" style="display: inline;">';
						echo "<input type='submit' class='button' name='clear_access_log' value='Clear Access Log'>";
					echo "</form>";
					
					echo "&nbsp;";
					
					echo '<form method="post" style="display: inline;">';
						echo "<input type='submit' class='button' name='download_access_log' value='Download Access Log (CSV File)'>";
					echo "</form>";
					
						//$log_table->views();
				echo '</div>';

                echo '<div class="alignright actions">';
                //$mode = ( isset($_GET['mode']) ) ? esc_attr($_GET['mode']) : false;
               // $log_table->view_switcher($mode);
                echo '</div>';
            echo '</div>';			
			
			
			//add classes to table rows based on success or failure
			echo "<script>";
			echo "jQuery(document).ready(function(){ ";
				echo "jQuery('div.login-failed').parent().parent().addClass('login-failed'); ";
				echo "jQuery('div.login-successful').parent().parent().addClass('login-successful'); ";
			echo "})";
			echo "</script>";
			
			
			$ss_options = get_option($this->opt_name);
			if($ss_options['basic_settings']['enable_ip_blacklist']){
				//echo $this->ip_blacklist();
			}
			

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
	
	

	function screen_options(){

        //execute only on login_log page, othewise return null
        $page = ( isset($_GET['page']) ) ? esc_attr($_GET['page']) : false;
        if( 'access_log' != $page )
            return;

        $current_screen = get_current_screen();

        //define options
        $per_page_field = 'per_page';
        $per_page_option = $current_screen->id . '_' . $per_page_field;

        //Save options that were applied
        if( isset($_REQUEST['wp_screen_options']) && isset($_REQUEST['wp_screen_options']['value']) ){
            update_option( $per_page_option, esc_html($_REQUEST['wp_screen_options']['value']) );
        }

        //prepare options for display

        //if per page option is not set, use default
        $per_page_val = get_option($per_page_option, 20);
        $args = array('label' => __('Records', 'simple-security'), 'default' => $per_page_val );

        //display options
        add_screen_option($per_page_field, $args);
        $_per_page = get_option('users_page_access_log_per_page');

        //create custom list table class to display log data
        $this->log_table = new Simple_Security_Access_Log_List_Table;
    }


	function log_get_data(){
        global $wpdb;

        $where = '';

        $where = $this->make_where_query();

        if( is_array($where) && !empty($where) )
            $where = 'WHERE ' . implode(' AND ', $where);

        $sql = "SELECT * FROM $this->db_table $where ORDER BY time DESC";
        $data = $wpdb->get_results($sql, 'ARRAY_A');

        return $data;
    }
	
	
	/**
	function ip_blacklist(){
	
	
		$blacklist = array();		
		if(isset($_POST['action']) && "add_blacklist_ip" == $_POST['action'] && is_admin()){
			foreach($_POST['simple_security_ip_blacklist'] as $ip){
				if($ip != ''){
					$blacklist[] = $ip;
				}
			}
			update_option('simple-security-ip-blacklist', $blacklist);
		} 
	
		$i = 0;
		$blacklist = get_option('simple-security-ip-blacklist');
		
		echo "<h2>Blocked IP Addresses</h2>";
		echo "<a href='".get_option('siteurl')."/wp-admin/options-general.php?page=simple-security-settings'>IP Address Blacklist Settings</a>";
		echo "<form method='post'>";
		echo '<input type="hidden" name="page" value="access_log" />';
		echo '<input type="hidden" name="action" value="add_blacklist_ip" />';
		echo "<p>";
		echo "Add New IP Address: <input type='text' name='simple_security_ip_blacklist[]' value=''>";
		echo "<input type='submit' class='button'  value='Save'>";
		echo "</p>";
		echo "<hr>";
		
		if($blacklist = get_option('simple-security-ip-blacklist')){
			foreach($blacklist as $ip){
				echo "<p>Blocked IP Address: <input type='text' name='simple_security_ip_blacklist[ $i ]' value='$ip'></p>";
				$i++;
			}
		}		
		
		echo "</form>";
	
	}
	**/
	
	function make_where_query(){
        $where = false;
		
        if( isset($_GET['filter']) && '' != $_GET['filter'] ){
            $where['filter'] = "(user_login LIKE '%{$_GET['filter']}%' OR ip LIKE '%{$_GET['filter']}%')";
        }
		
        if( isset($_GET['user_role']) && '' != $_GET['user_role'] ){
            $where['user_role'] = "user_role = '{$_GET['user_role']}'";
        }
		
        if( isset($_GET['result']) && '' != $_GET['result'] ){
            $where['result'] = "login_result = '{$_GET['result']}'";
        }
		
        if( isset($_GET['datefilter']) && !empty( $_GET['datefilter'] ) && is_numeric($_GET['datefilter']) ){
            $year = substr($_GET['datefilter'], 0, 4);
            $month = substr($_GET['datefilter'], -2);
            $where['datefilter'] = "YEAR(time) = {$year} AND MONTH(time) = {$month}";
        }

        return $where;
    }	
	
	

	function date_filter(){
        global $wpdb;
        $sql = "SELECT DISTINCT YEAR(time) as year, MONTH(time)as month FROM {$this->db_table} ORDER BY YEAR(time), MONTH(time) desc";
        $results = $wpdb->get_results($sql);

        if(!$results)
            return;


        $option = '';
        foreach($results as $row){
            //represent month in double digits
            $timestamp = mktime(0, 0, 0, $row->month, 1, $row->year);
            $month = (strlen($row->month) == 1) ? '0' . $row->month : $row->month;
            $datefilter = ( isset($_GET['datefilter']) && !empty( $_GET['datefilter'] ) && is_numeric($_GET['datefilter']) ) ? $_GET['datefilter'] : false;
            $option .= '<option value="' . $row->year . $month . '" ' . selected($row->year . $month, $datefilter, false) . '>' . date('F', $timestamp) . ' ' . $row->year . '</option>';
        }

        $output = '<form method="get">';
        $output .= '<input type="hidden" name="page" value="access_log" />';
        $output .= '<select name="datefilter"><option value="">' . __('View All', 'simple-security') . '</option>' . $option . '</select>';
        $output .= '<input class="button" type="submit" value="' . __('Filter', 'simple-security') . '" />';
        $output .= '</form>';
        return $output;
    }




	function admin_header(){
	
        $page = ( isset($_GET['page']) ) ? esc_attr($_GET['page']) : false;
        if( 'access_log' != $page )
            return;

        echo '<style type="text/css">';
		
        echo '.wp-list-table .column-id { width: 5%; }';
        echo '.wp-list-table .column-uid { width: 10%; }';
        echo '.wp-list-table .column-user_login { width: 10%; }';
		echo '.wp-list-table .column-user_role { width: 10%; }';
        echo '.wp-list-table .column-time { width: 15%; }';
        echo '.wp-list-table .column-ip { width: 10%; }';
        echo '.wp-list-table .column-login_result { width: 15%; }';
		echo '.wp-list-table .column-data { width: 25%; }';
		
		echo "div.login-failed {color: red;}";
		echo "div.login-successful {color: green;}";
		
		echo 'table.wp-list-table tbody tr.login-failed { background-color: rgba(255, 0, 0, 0.1) }';
		echo 'table.wp-list-table tbody tr.login-failed.alternate { background-color: rgba(255, 0, 0, 0.05) }';
		
		echo 'table.wp-list-table tbody tr.login-successful { background-color: rgba(0, 255, 0, 0.1) }';
		echo 'table.wp-list-table tbody tr.login-successful.alternate { background-color: rgba(0, 255, 0, 0.05) }';
		
		echo 'table.wp-list-table tbody tr:hover { background-color: rgba(255, 255, 0, 0.1) }';
		echo 'table.wp-list-table tbody tr.alternate:hover { background-color: rgba(255, 255, 0, 0.1) }';
		
        echo '</style>';
    }
	
	
	
	public function clear_access_log(){
	
		global $wpdb;
		
		//get initial count
		$sql = "SELECT COUNT(*) FROM " . $this->db_table;
		$initial_count = $wpdb->get_var($sql);
		
		//clear all records from the access log
		$sql = "TRUNCATE TABLE " . $this->db_table;
		$wpdb->query($sql);
		
		//get final count
		$sql = "SELECT COUNT(*) FROM " . $this->db_table;
		$final_count = $wpdb->get_var($sql);
		
		$result_count = $initial_count - $final_count;
		
		echo "<div class='updated'><p>$result_count Access Log Record(s) Deleted!</p></div>";
	
	}
	
	
	
	public function download_access_log(){
		
		global $wpdb;
		
		$sql = "SELECT * FROM " . $this->db_table;
		$result = $wpdb->get_results($sql, ARRAY_A );
		
		$num_fields = count($result[0]);
		
		$headers = array();
		for ($i = 0; $i < $num_fields; $i++) {
			$headers[] = $wpdb->get_col_info('name' , $i);
		}
		
		$fp = fopen('php://output', 'w'); 
		if ($fp && $result) {     
			header('Content-Type: text/csv');
			header('Content-Disposition: attachment; filename="access_log_export.csv"');
			header('Pragma: no-cache');    
			header('Expires: 0');
			fputcsv($fp, $headers); 
			foreach($result as $row){
				fputcsv($fp, array_values($row)); 
			}

		} 
		
		
	}
	
}

?>