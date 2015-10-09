<?php
    session_start();
	$current_url = explode('?', $_SERVER['REQUEST_URI']);
	$current_url = explode('/', $current_url[0]);
	array_pop($current_url);
	$current_url = 'http' . ( !empty($_SERVER['HTTPS']) ? 's' : '') .'://' . $_SERVER['HTTP_HOST'].implode('/', $current_url);
	$login_url = $current_url.'/login.php';
	if (isset($_REQUEST['submit'])) {
		$username = trim($_REQUEST['username']);
		$password = trim($_REQUEST['password']);
		if('demo'   == $username && 'demo!' 	== $password){
			//establish the local loggedin session
			$_SESSION['user_id'] = 1;
			if (!empty($_REQUEST['site'])) {
				//if site parameters is not empty, redirect to remote log in URL, to establish the helpIQ session
				header('location:'.$current_url.'/helpiq-auth.php?site='.$_REQUEST['site'].'&return_page='.$_REQUEST['return_page']);
				exit;
			} else {
				//redirect to local app
				header('location:'.$current_url.'/test.php');
				exit;
			}
		}
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Log in to Sample Application</title>
</head>
<body>
<div style="width:600px; margin:0 auto; padding-top:100px">
  <form action="" method="post">
    <input type="hidden" name="submit" value="submit"/>
    <input type="hidden" name="site" value="<?php if (isset($_REQUEST['site'])) { echo $_REQUEST['site'];} ?>"/>
    <input type="hidden" name="return_page" value="<?php if (isset($_REQUEST['return_page'])) { echo $_REQUEST['return_page'];} ?>"/>
    <table width="100%">
      <tr>
        <td width="21%">username:</td>
        <td width="79%"><input type="text" name="username"/></td>
      </tr>
      <tr>
        <td>password:</td>
        <td><input type="password" name="password"/></td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td><input type="submit" value="Log in"/></td>
      </tr>
    </table>
  </form>
</div>
</body>
</html>
