<?php

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


class Simple_Security_Access_Log_List_Table extends WP_List_Table{

	public $data_labels;

    function __construct(){
	
        global $access_log, $_wp_column_headers;

        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'user',     //singular name of the listed records
            'plural'    => 'users',    //plural name of the listed records
            'ajax'      => true        //does this table support ajax?
        ) );

        //$this->data_labels = $access_log->data_labels;


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
	


    function column_default($item, $column_name){
        $item = apply_filters('simple-security-output-data', $item);

        //unset existing filter and pagination
        $args = wp_parse_args( parse_url($_SERVER["REQUEST_URI"], PHP_URL_QUERY) );
        unset($args['filter']);
        unset($args['paged']);

        switch($column_name){
            case 'id':
            case 'uid':
            case 'time':
            case 'ip':
                return $item[$column_name];
            case 'user_login':
                return "<a href='" . add_query_arg( array('filter' => $item[$column_name]), menu_page_url('access_log', false) ) . "' title='" . __('Filter log by this name', 'simple-security') . "'>{$item[$column_name]}</a>";
            
			case 'name';
                $user_info = get_userdata($item['uid']);
                return ( is_object($user_info) ) ? $user_info->first_name .  " " . $user_info->last_name : false;
				
            case 'login_result':
                if ( '' == $item[$column_name]) return '';
				
                if ( '1' === $item[$column_name] ) {
					 return '<div class="login-successful" title="'.$this->data_labels['Successful'].'">' . $this->data_labels['Successful'] . '</div>';
					 
				}elseif ( '0' === $item[$column_name] ) { 
					return '<div class="login-failed" title="'.$this->data_labels['Failed'].'">' . $this->data_labels['Failed'] . '</div>';
				}else{
				 	return '<div class="login-failed" title="'.$this->data_labels['Failed'].'">' . $this->data_labels['Failed'] . '</div>';
				 }
				 
            case 'user_role':
                if( !$item['uid'] )
                    return;

                $user = new WP_User( $item['uid'] );
                if ( !empty( $user->roles ) && is_array( $user->roles ) ) {
                    foreach($user->roles as $role){
                        $roles[] = "<a href='" . add_query_arg( array('user_role' => $role), menu_page_url('access_log', false) ) . "' title='" . __('Filter log by User Role', 'simple-security') . "'>{$role}</a>";
                    }
                    return implode(', ', $roles);
                }
                break;
            case 'data':
                $data = unserialize($item[$column_name]);
                if(is_array($data)){
                    $output = '';
                    foreach($data as $k => $v) {
                        $output .= $k .': '. $v .'<br />';
                    }
					$full_output = str_replace("<br />", "\n", $output);
                    $output = ( isset($_GET['mode']) && 'excerpt' == $_GET['mode'] ) ? $output : substr($output, 0, 60) . '...';

                    if( isset($data[$this->data_labels['Login']]) && $data[$this->data_labels['Login']] == $this->data_labels['Failed'] ){
                        return '<div class="login-failed" title="'.$full_output.'">' . $output . '</div>';
                    }else{
                    	return '<div class="login-successful" title="'.$full_output.'">' . $output . '</div>';
					}
                }
                break;
            default:
                return $item[$column_name];
        }
    }


    function get_columns(){
	
        global $status;
        $columns = array(
            'id'            => __('#', 'simple-security'),
            'uid'           => __('User ID', 'simple-security'),
            'user_login'    => __('Username', 'simple-security'),
            'user_role'     => __('User Role', 'simple-security'),
            'time'          => __('Time', 'simple-security'),
            'ip'            => __('IP Address', 'simple-security'),
            'login_result'  => __('Login Result', 'simple-security'),
            'data'          => __('Data', 'simple-security'),
        );
        return $columns;
    }


    function get_sortable_columns(){
	
        $sortable_columns = array(
            'uid'           => array('uid',false),
            'user_login'    => array('user_login', false),
            'time'          => array('time',true),
            'ip'            => array('ip', false),
			'login_result'	=> array('login_result', false),
			'user_role'	=> array('user_role', false)
        );
        return $sortable_columns;
    }


    function get_views(){
	
        //creating class="current" variables
        if( !isset($_GET['result']) ){
            $all = 'class="current"';
            $success = '';
            $failed = '';
        }else{
            $all = '';
            $success = ( '1' == $_GET['result'] ) ? 'class="current"' : '';
            $failed = ( '0' == $_GET['result'] ) ? 'class="current"' : '';
        }

        //get number of successful and failed logins so we can display them in parentheces for each view
        global $wpdb, $access_log;

        //building a WHERE SQL query for each view
        $where = $access_log->make_where_query();
        //we only need the date filter, everything else need to be unset
        if( is_array($where) && isset($where['datefilter']) ){
            $where = array( 'datefilter' =>  $where['datefilter'] );
        }else{
            $where = false;
        }

        $where3 = $where2 = $where1 = $where;
        $where2['login_result'] = "login_result = '1'";
        $where3['login_result'] = "login_result = '0'";

        if(is_array($where1) && !empty($where1)){
            $where1 = 'WHERE ' . implode(' AND ', $where1);
        }
        $where2 = 'WHERE ' . implode(' AND ', $where2);
        $where3 = 'WHERE ' . implode(' AND ', $where3);

        $sql1 = "SELECT * FROM {$access_log->db_table} {$where1}";
        $a = $wpdb->query($sql1);
        $sql2 = "SELECT * FROM {$access_log->db_table} {$where2}";
        $s = $wpdb->query($sql2);
        $sql3 = "SELECT * FROM {$access_log->db_table} {$where3}";
        $f = $wpdb->query($sql3);

        //if date filter is set, adjust views label to reflect the date
        $date_label = false;
        if( isset($_GET['datefilter']) && !empty($_GET['datefilter']) && is_numeric($_GET['datefilter']) ){
            $year = substr($_GET['datefilter'], 0, 4);
            $month = substr($_GET['datefilter'], -2);
            $timestamp = mktime(0, 0, 0, $month, 1, $year);
            $date_label = date('F', $timestamp) . ' ' . $year . ' ';
        }

        //get args from the URL
        $args = wp_parse_args( parse_url($_SERVER["REQUEST_URI"], PHP_URL_QUERY) );
		
        //the only arguments we can pass are mode and datefilter
        $param = false;
        if( isset($args['mode']) )
            $param['mode'] = $args['mode'];

        if( isset($args['datefilter']) && is_numeric($args['datefilter']) ){
            $param['datefilter'] = $args['datefilter'];


		}
		
		
        //creating base url for the views links
        $menu_page_url = menu_page_url('access_log', false);
        ( is_array($param) && !empty($param) ) ? $url = add_query_arg( $param, $menu_page_url) : $url = $menu_page_url;

        //definition for views array
        $views = array(
            'all' => $date_label . __('Login Results', 'simple-security') . ': <a ' . $all . ' href="' . $url . '">' . __('All', 'simple-security') . '</a>' . '(' .$a . ')',
            'success' => '<a ' . $success . ' href="' . $url . '&result=1">' . __('Successful', 'simple-security') . '</a> (' . $s . ')',
            'failed' => '<a ' . $failed . ' href="' . $url . '&result=0">' . __('Failed', 'simple-security') . '</a>' . '(' . $f . ')',
        );

        return $views;
    }


    function prepare_items(){
	
        $screen = get_current_screen();

        /**
         * setup pagination default number per page
         */
        $per_page_option = $screen->id . '_per_page';
        $per_page = get_option($per_page_option, 20);
        $per_page = ($per_page != false) ? $per_page : 20;


        /**
         * Define column headers. This includes a complete
         * array of columns to be displayed (slugs & titles), a list of columns
         * to keep hidden, and a list of columns that are sortable. Each of these
         * can be defined in another method (as we've done here) before being
         * used to build the value for our _column_headers property.
         */
        $columns = $this->get_columns();
        $hidden_cols = get_user_option( 'manage' . $screen->id . 'columnshidden' );
        $hidden = ( $hidden_cols ) ? $hidden_cols : array();
        $sortable = $this->get_sortable_columns();


        /**
         * Build an array to be used by the class for column
         * headers. The $this->_column_headers property takes an array which contains
         * 3 other arrays. One for all columns, one for hidden columns, and one
         * for sortable columns.
         */
        $this->_column_headers = array($columns, $hidden, $sortable);
        $columns = get_column_headers( $screen );


        /**
         * Fetch the data for use in this table. 
         */
        $data = $this->items;


        /**
         * This checks for sorting input and sorts the data in our array accordingly.
         */
        function usort_reorder($a,$b){
            $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'time'; //If no sort, default to title
            $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'desc'; //If no order, default to asc
            $result = strcmp($a[$orderby], $b[$orderby]); //Determine sort order
            return ($order==='asc') ? $result : -$result; //Send final sort direction to usort
        }
        usort($data, 'usort_reorder');


        /**
         * Figure out what page the user is currently looking at. 
         */
        $current_page = $this->get_pagenum();


        /**
         * REQUIRED for pagination. Let's check how many items are in our data array.
         * In real-world use, this would be the total number of items in your database,
         * without filtering. We'll need this later, so you should always include it
         * in your own package classes.
         */
        $total_items = count($data);


        /**
         * manual pagination
         */
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);



        /**
         * Add our *sorted* data to the items property
         */
        $this->items = $data;


        /**
         * Register our pagination options & calculations.
         */
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //calculate the total number of items
            'per_page'    => $per_page,                     //determine how many items to show on a page
            'total_pages' => ceil($total_items/$per_page)   //calculate the total number of pages
        ) );

    }

}

?>