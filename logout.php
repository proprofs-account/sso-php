<?php
session_start();
include_once('lib/Helpiq_SSO_Support.php');
$helpiq_api_key = '9d1e2693fe4fc477cf26bc0df3372985';
$helpiq_site_url = 'b00017.helpdocsonline.com';
$helpiq_sso_support = new Helpiq_SSO_Support($helpiq_api_key, $helpiq_site_url);
$helpiq_sso_support->logout_helpiq();
?>