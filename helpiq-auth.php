<?php
session_start();
require_once('lib/Helpiq_SSO_Support.php');

//enter your API key here
$helpiq_api_key = '9d1e2693fe4fc477cf26bc0df3372985';
//enter your site URL here
$helpiq_site_url = 'mysite.helpdocsonline.com';
// parameter site_access: this defines which sites are allowed, separate multiple sites by comma
// If no value is set and the parameter is not included then we allow access to all sites. 
// If the parameter is set, then we only allow access to whatever sites are included
// If the user tries to go to a site they do not have permission we just take them to a site they do have permission
$helpiq_site_access = '';
$helpiq_sso_support = new Helpiq_SSO_Support($helpiq_api_key, $helpiq_site_url);
$helpiq_sso_support->do_helpiq_authorization($helpiq_site_access);
?>