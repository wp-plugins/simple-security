<?php

class Simple_Security_Admin extends Simple_Security {
	/**
	 * Error messages to diplay
	 *
	 * @var array
	 */
	private $_messages = array();
	
	public $access_log;
	
	
	/**
	 * Class constructor
	 *
	 */
	public function __construct() {
		global $wpdb, $access_log;
        $this->table = $wpdb->prefix . $this->table;
		
		$this->_plugin_dir   = DIRECTORY_SEPARATOR . str_replace(basename(__FILE__), null, plugin_basename(__FILE__));
		$this->_settings_url = 'options-general.php?page=' . plugin_basename(__FILE__);
		
		$this->installed_ver = get_option( "simple_security_db_version" );
		
		$allowed_options = array();	
	
	
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
		
		
		// register installer function
		register_activation_hook(SSec_LOADER, array(&$this, 'activateSimpleSecurity'));
	
		// add plugin "Settings" action on plugin list
		add_action('plugin_action_links_' . plugin_basename(SSec_LOADER), array(&$this, 'add_plugin_actions'));
		
		// add links for plugin help, donations,...
		add_filter('plugin_row_meta', array(&$this, 'add_plugin_links'), 10, 2);
		
		// push options page link, when generating admin menu
		add_action('admin_menu', array(&$this, 'adminMenu'));
		
		$ss_options = get_option('simple_security_plugin'); 
		
		if($ss_options['enable_access_log']){
		//setup logging functions
			add_action( 'init', array(&$this, 'init_login_actions') );
			add_action( 'init', array(&$this, 'security_check') );
			add_action( 'init', array(&$this, 'blacklist_check') );
			
			//accecss access log table
			$access_log = new Simple_Security_Access_Log();
			$this->access_log = $access_log;
			add_action( 'admin_head', array($access_log, 'admin_header') );
			add_action( 'admin_head', array($access_log, 'screen_options') );
			add_action( 'admin_menu', array($access_log, 'simple_security_admin_menu') );
		}
		
		
		//setup custom column in user list to display last login
		if($ss_options['enable_last_login_column']){
			$user_last_login = new User_Last_Login();
			add_filter( 'manage_users_columns', array($user_last_login, 'add_last_login_column') );
			add_action( 'manage_users_custom_column', array($user_last_login, 'add_last_login_column_value'), 10, 3 );
			add_action( 'wp_login', array($user_last_login, 'insert_last_login') );
		}
		
		//enable the admin dashboard access log widget
		if($ss_options['enable_admin_widget']){
			add_action( 'init', array($this, 'setup_admin_dash') );
		}	
		
		
	}
	
	
	function setup_admin_dash(){
		$admin_widget = new Access_Log_Admin_Widget();
		
		add_action('wp_dashboard_setup', array($admin_widget, 'add_admin_widget') );
	}
	
	
	
	function security_check(){
			
		global $wpdb;
		
		$today = date('Y-m-d');
		$sql = "SELECT ip FROM " . $this->table . " WHERE login_result = 0 and time LIKE '$today%' GROUP BY ip HAVING COUNT(ip) > 5";
		$results = $wpdb->get_results($sql);
		
		if($results){
			$blacklist = get_option('simple_security_ip_blacklist');
			foreach($results as $result){
				$blacklist[] = $result->ip;
			}
			update_option('simple_security_ip_blacklist', $blacklist);
			
		}
	
	}
	

	function blacklist_check(){
		if ( isset( $_SERVER["HTTP_X_FORWARDED_FOR"] ) )
			$ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
		else
			$ip = $_SERVER["REMOTE_ADDR"];
		
		if($blocked_ip_addresses = get_option('simple_security_ip_blacklist')){
			if( in_array($ip, $blocked_ip_addresses) ){
				die('Access Denied');
			}
		}
	}
	



	
	
	function login_action($user_login){

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




	
	
	

		
	/**
	 * Add "Settings" action on installed plugin list
	 */
	public function add_plugin_actions($links) {
		array_unshift($links, '<a href="options-general.php?page=' . plugin_basename(__FILE__) . '">' . __('Settings') . '</a>');
		
		return $links;
	}
	
	/**
	 * Add links on installed plugin list
	 */
	public function add_plugin_links($links, $file) {
		if($file == plugin_basename(SSec_LOADER)) {
			$links[] = '<a href="http://MyWebsiteAdvisor.com/">Visit Us Online</a>';
		}
		
		return $links;
	}
	
	/**
	 * Add menu entry for Simple Optimizer settings and attach style and script include methods
	 */
	public function adminMenu() {		
		// add option in admin menu, for settings
		$plugin_page = add_options_page('Simple Security Plugin Options', 'Simple Security', 8, __FILE__, array(&$this, 'optionsPage'));

		add_action('admin_print_styles-' . $plugin_page,     array(&$this, 'installStyles'));
	}
	
	/**
	 * Include styles used by Simple Optimizer Plugin
	 */
	public function installStyles() {
		//wp_enqueue_style('simple-optimizer', WP_PLUGIN_URL . $this->_plugin_dir . 'style.css');
	}
	




	function HtmlPrintBoxHeader($id, $title, $right = false) {
		
		?>
		<div id="<?php echo $id; ?>" class="postbox">
			<h3 class="hndle"><span><?php echo $title ?></span></h3>
			<div class="inside">
		<?php	
	}
	
	function HtmlPrintBoxFooter( $right = false) {
		?>
			</div>
		</div>
		<?php
	}
	
	

	
	
	/**
	 * Display options page
	 */
	public function optionsPage() {
		// if user clicked "Save Changes" save them
		if(isset($_POST['Submit'])) {
			foreach($this->_options as $option => $value) {
				if(array_key_exists($option, $_POST)) {
					update_option($option, $_POST[$option]);
				} else {
					update_option($option, $value);
				}
			}

			$this->_messages['updated'][] = 'Options updated!';
		}


		
		
	
		foreach($this->_messages as $namespace => $messages) {
			foreach($messages as $message) {
?>
<div class="<?php echo $namespace; ?>">
	<p>
		<strong><?php echo $message; ?></strong>
	</p>
</div>
<?php
			}
		}
		
		
			
			
				
?>

	
									  
<script type="text/javascript">var wpurl = "<?php bloginfo('wpurl'); ?>";</script>

<style>

.fb_edge_widget_with_comment {
	position: absolute;
	top: 0px;
	right: 200px;
}

</style>

<div  style="height:20px; vertical-align:top; width:50%; float:right; text-align:right; margin-top:5px; padding-right:16px; position:relative;">

	<div id="fb-root"></div>
	<script>(function(d, s, id) {
	  var js, fjs = d.getElementsByTagName(s)[0];
	  if (d.getElementById(id)) return;
	  js = d.createElement(s); js.id = id;
	  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId=253053091425708";
	  fjs.parentNode.insertBefore(js, fjs);
	}(document, 'script', 'facebook-jssdk'));</script>
	
	<div class="fb-like" data-href="http://www.facebook.com/MyWebsiteAdvisor" data-send="true" data-layout="button_count" data-width="450" data-show-faces="false"></div>
	
	
	<a href="https://twitter.com/MWebsiteAdvisor" class="twitter-follow-button" data-show-count="false"  >Follow @MWebsiteAdvisor</a>
	<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>


</div>

<div class="wrap" id="sm_div">

	<div id="icon-options-general" class="icon32"><br /></div>
	<h2>Simple Security Plugin Settings</h2>
	
		
		
	<div id="poststuff" class="metabox-holder has-right-sidebar">
		<div class="inner-sidebar">
			<div id="side-sortables" class="meta-box-sortabless ui-sortable" style="position:relative;">
			
<?php $this->HtmlPrintBoxHeader('pl_diag',__('Plugin Diagnostic Check','diagnostic'),true); ?>

				<?php
				
				echo "<p>Server OS: ".PHP_OS."</p>";
				
				echo "<p>Required PHP Version: 5.0+<br>";
				echo "Current PHP Version: " . phpversion() . "</p>";
			
							
				echo "<p>Memory Use: " . number_format(memory_get_usage()/1024/1024, 1) . " / " . ini_get('memory_limit') . "</p>";
				
				echo "<p>Peak Memory Use: " . number_format(memory_get_peak_usage()/1024/1024, 1) . " / " . ini_get('memory_limit') . "</p>";
				
				$lav = sys_getloadavg();
				echo "<p>Server Load Average: ".$lav[0].", ".$lav[1].", ".$lav[2]."</p>";
				
				?>

<?php $this->HtmlPrintBoxFooter(true); ?>



<?php $this->HtmlPrintBoxHeader('pl_resources',__('Plugin Resources','resources'),true); ?>

	<p><a href='http://mywebsiteadvisor.com/wordpress-plugins/simple-security/' target='_blank'>Plugin Homepage</a></p>
	<p><a href='http://mywebsiteadvisor.com/support/'  target='_blank'>Plugin Support</a></p>
	<p><a href='http://mywebsiteadvisor.com/contact-us/'  target='_blank'>Suggest a Feature</a></p>
	<p><a href='http://mywebsiteadvisor.com/contact-us/'  target='_blank'>Contact Us</a></p>
	
<?php $this->HtmlPrintBoxFooter(true); ?>


<?php $this->HtmlPrintBoxHeader('more_plugins',__('More Plugins','more_plugins'),true); ?>
	
	<p><a href='http://mywebsiteadvisor.com/tools/premium-wordpress-plugins/'  target='_blank'>Premium WordPress Plugins!</a></p>
	<p><a href='http://profiles.wordpress.org/MyWebsiteAdvisor/'  target='_blank'>Free Plugins on Wordpress.org!</a></p>
	<p><a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/'  target='_blank'>Free Plugins on Our Website!</a></p>	
				
<?php $this->HtmlPrintBoxFooter(true); ?>


<?php $this->HtmlPrintBoxHeader('follow',__('Follow MyWebsiteAdvisor','follow'),true); ?>

	<p><a href='http://facebook.com/MyWebsiteAdvisor/'  target='_blank'>Follow us on Facebook!</a></p>
	<p><a href='http://twitter.com/MWebsiteAdvisor/'  target='_blank'>Follow us on Twitter!</a></p>
	<p><a href='http://www.youtube.com/mywebsiteadvisor'  target='_blank'>Watch us on YouTube!</a></p>
	<p><a href='http://MyWebsiteAdvisor.com/'  target='_blank'>Visit our Website!</a></p>	
	
<?php $this->HtmlPrintBoxFooter(true); ?>


</div>
</div>



	<div class="has-sidebar sm-padded" >			
		<div id="post-body-content" class="has-sidebar-content">
			<div class="meta-box-sortabless">
	
			<form method='post'>
	
				<?php $this->HtmlPrintBoxHeader('security-settings',__('Simple Security Plugin Settings','security-settings'),false); ?>	
			
				
		
					<?php $ss_options = get_option('simple_security_plugin'); ?>
					
					<?php $checked = $ss_options['enable_access_log'] ? 'checked="checked"' : ''; ?>
					<?php $link = $ss_options['enable_access_log'] ? ' - <a href="'.get_option('siteurl').'/wp-admin/users.php?page=access_log">View Access Log</a>' : ''; ?>
					<p><input type='checkbox' name='simple_security_plugin[enable_access_log]' <?php echo $checked; ?> /> Enable Access Logging <?php echo $link; ?></p>
					
					<?php $checked = $ss_options['enable_ip_blacklist'] ? 'checked="checked"' : ''; ?>
					<p><input type='checkbox' name='simple_security_plugin[enable_ip_blacklist]' <?php echo $checked; ?> /> Enable IP Address Blacklist</p>
					
					<?php $checked = $ss_options['enable_ip_autoblock'] ? 'checked="checked"' : ''; ?>
					<p><input type='checkbox' name='simple_security_plugin[enable_ip_autoblock]' <?php echo $checked; ?> /> Enable Automatic IP Address Blocking</p>
					
					<?php $checked = $ss_options['enable_admin_widget'] ? 'checked="checked"' : ''; ?>
					<p><input type='checkbox' name='simple_security_plugin[enable_admin_widget]' <?php echo $checked; ?> /> Enable Admin Dashboard Access Log Widget</p>
					
					<?php $checked = $ss_options['enable_last_login_column'] ? 'checked="checked"' : ''; ?>
					<p><input type='checkbox' name='simple_security_plugin[enable_last_login_column]' <?php echo $checked; ?> /> Enable Last Login Tracking and Display Column On Users List</p>
					
					
					<input type="submit" class='button' name='Submit' value='Save Settings' />
				
			
				<?php $this->HtmlPrintBoxFooter(false); ?>
				
				
				
				<?php if( $ss_options['enable_ip_autoblock'] ){ ?>
					<?php $this->HtmlPrintBoxHeader('security-settings',__('Automatic IP Address Blocking Settings','security-settings'),false); ?>
					
					<?php $fail_count = (strlen($ss_options['ip_autoblock_fail_count']) > 0) ? $ss_options['ip_autoblock_fail_count']: '10'; ?>
					<p>Each login failure is recorded in the access log.  If any IP Address has more than <?php echo $fail_count; ?> failed login attempts, they are blocked for the rest of that day.</p>
					
					<p>Number of failed login attempts per day before IP address is blocked:<br />
					<input type="text" name='simple_security_plugin[ip_autoblock_fail_count]' value='<?php echo $fail_count; ?>' /></p>
					
					<?php $message = (strlen($ss_options['ip_autoblock_message']) > 0) ? $ss_options['ip_autoblock_message']: 'Access Denied'; ?>
					<p>Message to display to blocked users:<br />
					<input type="text" class='widefat'  name='simple_security_plugin[ip_autoblock_message]' value='<?php echo $message; ?>'  /></p>
					
					<input type="submit" class='button' name='Submit' value='Save Settings' />
					<?php $this->HtmlPrintBoxFooter(false); ?>
				<?php } ?>
				
				
				
				
				<?php if( $ss_options['enable_ip_blacklist'] ){ ?>
					<?php $this->HtmlPrintBoxHeader('security-settings',__('IP Address Blacklist Settings','security-settings'),false); ?>
					
					<?php $message = (strlen($ss_options['ip_blacklist_message']) > 0) ? $ss_options['ip_blacklist_message']: 'Access Denied'; ?>
					
					Message to display to blocked users:<br />
					<input type="text" class='widefat'  name='simple_security_plugin[ip_blacklist_message]' value='<?php echo $message; ?>'  /><br />
					<br />
					
					<input type="submit" class='button' name='Submit' value='Save Settings' />
					<?php $this->HtmlPrintBoxFooter(false); ?>
					
					
					<?php $this->HtmlPrintBoxHeader('security-settings',__('IP Address Blacklist','security-settings'),false); ?>
						<?php $this->access_log->ip_blacklist(); ?>
					<?php $this->HtmlPrintBoxFooter(false); ?>
					
				<?php } ?>
			
			
			
			
				
			
			
			</form>
		
		
</div></div></div></div>

</div>


<?php
	}
	
}

?>