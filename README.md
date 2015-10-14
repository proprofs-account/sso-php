
# sso-php


Log in to your account (http://www.proprofs.com/knowledgebase/login) as an Settingsistrator and click Settings.
Click Private Sites.

Check Turn On Private Sites.
Select the sites you want to make private.
Then check Turn On Single Sign-On.
The following settings also appear.
API Key - In order for SSO to work, the API key must be passed to ProProfs. The API is a shared secret between you and ProProfs. It must never be publicized. Copy this API key to add to your SSO script. You can also change the API key at any time by clicking Regenerate. This will generate a new API key and invalidate the old one.
End users can only view the site they log in to. Their access to your other sites is blocked. Having this checked means if a user logs in from site1 and then went directly to your other site2, they will be redirected back to site1. NOTE: you can also use the site_access parameter to define which sites the end user can access.
Auth URL - This is the URL that a user is redirected to whenever there was an attempt to view a page on a private site but there's no valid ProProfs viewer session yet. In this event, we always pass the site and page that was being accessed using query parameters site and return_page. This is handy because the tendency is to redirect back the customer to the previous page after being granted access by your scripts. 
Logout URL - This field is optional. If a Logout URL is entered, the Logout link in the help site ends the viewer session on ProProfs and then redirects the user to this url. 
Save - To save the settings.
Cancel - Click Cancel to return to the Settings page without saving these settings.

The sample SSO scripts are at work whenever a user without a valid ProProfs viewer-only session visits a page on a private site with SSO enabled. The script will do a check to see if the user is already logged into your web app and will allow the user to view your ProProfs site or redirect them to your login page. The redirects are browser-based and does not require ProProfs to access your database, network, or authentication system directly. 

In a nutshell, what the system is doing is triggering a remote url in our end along with your private API key to sign it to initiate a valid viewer-only session for your help site. This sample implementation assumes that the desired behavior is that if a user logs in, you want to give that user access to the help site. Your developer can be creative and alter this criteria based on how you do your authentication.


First, let's take a look at ProProfs-auth.php 

This file is merely initiating an object of class ProProfs_SSO_Support. You can set your site parameters in this file. It calls the do_ProProfs_authorization() method which does several things in this demo app. It's explained in further detail through the class file.
 
require_once('lib/ProProfs_SSO_Support.php');
//enter your API key here
$ProProfs_api_key = '9d1e2693fe4fc477cf26bc0df3372985';
//enter your site URL here
$ProProfs_site_url = 'mysite.helpdocsonline.com';
// parameter site_access: this defines which sites are allowed, separate multiple sites by comma
// If no value is set and the parameter is not included then we allow access to all sites.
// If the parameter is set, then we only allow access to whatever sites are included
// If the user tries to go to a site they do not have permission we just take them to a site they do have permission
$ProProfs_site_access = '';
$ProProfs_sso_support = new ProProfs_SSO_Support($ProProfs_api_key, $ProProfs_site_url);
$ProProfs_sso_support->do_ProProfs_authorization($ProProfs_site_access);
 
Next, lets look at lib/ProProfs_SSO_Support.php

This file contains the ProProfs_SSO_Support class. This object is responsible for checking whether a user is logged in your web application, and controls the redirection to urls that are needed for different use cases.

The methods are:
ProProfs_check_local_session() which assumes you identify that a user is logged in on your app if there's a user_id key set in your PHP $_SESSION variable.
 
// Upon log in of your application or website a session is established for the user.
// This code will check the users session to determine if they are logged in.
// You can replace 'user_id' with whatever you want such as username, email, etc.
// All the system is doing here is checking to see it there is a value. If there is no value require user to log in.
// If there is a value pass the site parameters to  http://www.helpdocsonline.com/access/remote/ and establish a session on ProProfs.
public function ProProfs_check_local_session() {
return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}
ProProfs_destroy_local_session() which assumes that unsetting the user_id key in PHP $_SESSION variable terminates your web app user's session
 
//please destroy your local session data here
public function ProProfs_destroy_local_session() {
unset($_SESSION['user_id']);
}
logout_ProProfs() which terminates the web app user session logs out and immediately after, terminates the ProProfs session. After the session is terminated in ProProfs, behind the scene, it redirects to your Auth URL but passes as parameter action which is sets to action.
 
//logout the end-user from ProProfs, and it will redirect to your remote auth url
public function logout_ProProfs(){
$this->ProProfs_destroy_local_session();
$logout_url = $this->ProProfs_remote_url.'logout/?site='.$this->custom_ProProfs_site;
header('location:'.$logout_url);
exit;
}
do_ProProfs_authorization() does the heavy work and runs everytime a page is accessed on a private site but no valid ProProfs session is available. This is the case in our sample implementation as it's being called in the Auth URL.
 
public function do_ProProfs_authorization($site_access = '') {
  //If Remote logout URL is entered in ProProfs the 'log-out' link can destroy the end-users session in ProProfs and the session on your web application.
  $action = isset($_REQUEST['action']) ? (string)$_REQUEST['action'] : 'login';
  $redirect_url = $this->default_login_url;
  //When the Remote logout URL is empty, end-user logged out from ProProfs site,
  //it will pass the logged_out parameter to tell customer's web app don't to give the end-user access again, just redirect to local login page
  $logged_out = isset($_REQUEST['logged_out']) ? $_REQUEST['logged_out'] : false;
  if ('logout' == $action || 'custom_logout' == $action) {
   $this->ProProfs_destroy_local_session();
   $redirect_url = $this->default_login_url;
  } else {
   //your ProProfs site URL
   $site = (string)$_REQUEST['site'];
   //return_page is passed by ProProfs, it will redirect the end-user to a specific page ProProfs
   $return_page = (string)$_REQUEST['return_page'];
   // please check your end-user has logged in here
   $url_params = 'site='.$site.'&return_page='.$return_page;
   if (!$logged_out && $this->ProProfs_check_local_session()) {
    // if the end-user has logged in the customer's website/web application, call ProProfs to estbalish a session
    $redirect_url = $this->ProProfs_remote_url.'?hash='.md5($this->ProProfs_api_key).'&'.$url_params;
    if(!empty($site_access)) {
     $redirect_url .= '&site_access='.$site_access;
    }
   } else {
    // the end-user does not log in, redirect to error/log in page
    if (isset($_REQUEST['contextual']) && $_REQUEST['contextual']) {
     //if the refer page is a contextual help(lightbox/tooltip), redirect to show permission limit    
     $redirect_url = $this->ProProfs_remote_url.'permission_limit/?login=false&'.$url_params;
    } else {
     //redirect to your local application login page
     $redirect_url = $this->default_login_url.'?'.$url_params;
    }
   }
  }
  header('location:'.$redirect_url);
}
}
​Now let's look at login.php closely.
​In this section:
 
 session_start();
$current_url = explode('?', $_SERVER['REQUEST_URI']);
$current_url = explode('/', $current_url[0]);
array_pop($current_url);
$current_url = 'http' . ( !empty($_SERVER['HTTPS']) ? 's' : '') .'://' . $_SERVER['HTTP_HOST'].implode('/', $current_url);
$login_url = $current_url.'/login.php';
if (isset($_REQUEST['submit'])) {
  $username = trim($_REQUEST['username']);
  $password = trim($_REQUEST['password']);
  if('demo'   == $username && 'demo!'  == $password){
   //establish the local loggedin session
   $_SESSION['user_id'] = 1;
   if (!empty($_REQUEST['site'])) {
    //if site parameters is not empty, redirect to remote log in URL, to establish the ProProfs session
    header('location:'.$current_url.'/ProProfs-auth.php?site='.$_REQUEST['site'].'&return_page='.$_REQUEST['return_page']);
    exit;
   } else {
    //redirect to local app
    header('location:'.$current_url.'/test.php');
    exit;
   }
  }
}
We made it so that if the user logs in with username: demo and password: demo!, they are assumed authenticated. We set $_SESSION['user_id'] = 1. Your authentication system will definitely be much more complex than this.
Also, in this file, we have the behavior that if the user successfully logs in, they will be redirected to a test.php file in your web app unless there's a url parameter site and an optional return_page which will used to redirect back the user whatever that is. 
The test.php file just shows a logout link which points to the logout.php script. This demonstrates how to effectively logout the web app user and then logout the ProProfs session too.
The logout.php script mainly calls the logout_ProProfs() method of the SSO class object which simply does the following:
Destroy the session for the web app user.

Note: We were assuming that you are merely using something like a $_SESSION['user_id'] to indicate whether a user is logged in or not, definitely your implementation will be much more complex than this and your developer needs to update it based on how your authentication system works.
Then terminate the viewer session in ProProfs.
Then we redirect back to the Auth URL but this time with query parameter action being set to logout which is catched by our sample script and just redirects the user to the local login page we have in the SSO class file.
If you need a script in another language, such as Java, you can follow the above examples and create your own. Please feel free to send us any examples.
