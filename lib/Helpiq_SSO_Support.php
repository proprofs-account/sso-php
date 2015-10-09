<?php

class Helpiq_SSO_Support {
	
	private $helpiq_api_key = '';

	// your local login page
	private $default_login_url = 'login.php';

	//This is the remote authenication URL to call helpIQ. Do not change.
	private $helpiq_remote_url = 'http://www.helpdocsonline.com/access/remote/';

	private $custom_helpiq_site = '';

	public function __construct($api_key, $site_url) {
		$current_url = explode('?', $_SERVER['REQUEST_URI']);
		$current_url = explode('/', $current_url[0]);
		array_pop($current_url);
		$current_url = 'http' . ( !empty($_SERVER['HTTPS']) ? 's' : '') .'://' . $_SERVER['HTTP_HOST'].implode('/', $current_url);
		$this->default_login_url = $current_url.'/'.$this->default_login_url;
		$this->helpiq_api_key = $api_key;
		$this->custom_helpiq_site = $site_url;
	}

	// Upon log in of your application or website a session is established for the user.
	// This code will check the users session to determine if they are logged in. 
	// You can replace 'user_id' with whatever you want such as username, email, etc.
	// All the system is doing here is checking to see it there is a value. If there is no value require user to log in. 
	// If there is a value pass the site parameters to  http://www.helpdocsonline.com/access/remote/ and establish a session on HelpIQ. 
	public function helpiq_check_local_session() {
		return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
	}

	//please destroy your local session data here
	public function helpiq_destroy_local_session() {
		unset($_SESSION['user_id']);
	}

	//logout the end-user from helpIQ, and it will redirect to your auth url
	public function logout_helpiq(){
		$this->helpiq_destroy_local_session();
		$logout_url = $this->helpiq_remote_url.'logout/?site='.$this->custom_helpiq_site;
		header('location:'.$logout_url);
		exit;
	}

	/* do helpiq authorization
	 * parameter site_access: this defines which sites are allowed, separate multiple sites by comma
	 * If no value is set and the parameter is not included then we allow access to all sites. 
	 * If the parameter is set, then we only allow access to whatever sites are included
	 * If the user tries to go to a site they do not have permission we just take them to a site they do have permission
	 */
	public function do_helpiq_authorization($site_access = '', $sso_params = array()) {
		//If Logout URL is entered in HelpIQ the 'log-out' link can destroy the end-users session in HelpIQ and the session on your web application. 
		$action = isset($_REQUEST['action']) ? (string)$_REQUEST['action'] : 'login';
		$redirect_url = $this->default_login_url;
		//When the Logout URL is empty, end-user logged out from HelpIQ site, 
		//it will pass the logged_out parameter to tell customer's web app don't to give the end-user access again, just redirect to local login page
		$logged_out = isset($_REQUEST['logged_out']) ? $_REQUEST['logged_out'] : false;
		if ('logout' == $action || 'custom_logout' == $action) {
			$this->helpiq_destroy_local_session();
			$redirect_url = $this->default_login_url;
		} else {
			//your helpIQ site URL
			$site = (string)$_REQUEST['site'];
			//return_page is passed by helpIQ, it will redirect the end-user to a specific page HelpIQ
			$return_page = (string)$_REQUEST['return_page'];
			// please check your end-user has logged in here
			$url_params = array('site' => $site, 'return_page' => $return_page);
			$url_params = array_merge($url_params, $sso_params);
			$url_params = http_build_query($url_params);
			if (!$logged_out && $this->helpiq_check_local_session()) {
				// if the end-user has logged in the customer's website/web application, call HelpIQ to estbalish a session
				$redirect_url = $this->helpiq_remote_url.'?api_key='.md5($this->helpiq_api_key).'&'.$url_params;
				if(!empty($site_access)) {
					$redirect_url .= '&site_access='.$site_access;
				}
			} else {
				// the end-user does not log in, redirect to error/log in page
				if (isset($_REQUEST['contextual']) && $_REQUEST['contextual']) {
					//if the refer page is a contextual help(lightbox/tooltip), redirect to show permission limit					
					$redirect_url = $this->helpiq_remote_url.'permission_limit/?login=false&'.$url_params;
				} else {
					//redirect to your local application login page
					$redirect_url = $this->default_login_url.'?'.$url_params;
				}
			}
		}
		header('location:'.$redirect_url);
	}
}
?>