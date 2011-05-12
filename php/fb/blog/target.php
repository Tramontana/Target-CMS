<?php
	require_once "facebook.php";

	$app_id = "145431908857122";
	$app_secret = "399e17aec55ca34c4ded9c96f7a8c957";
	
	$facebook = new Facebook(array(
        "appId" => $app_id,
        "secret" => $app_secret,
        "cookie" => true
		));

	$session = $facebook->getSession();
	$uid = $facebook->getUser();
	$fbme = null;

	// Session based graph API call.
	if (!$session && !isset($_REQUEST["admin"]))
	{
		try
		{  
			$fbme = $facebook->api('/me');  
		}
		catch (FacebookApiException $e)
		{
			error_log($e);
		}  
		if (!$fbme)
		{
			$login_url = $facebook->getLoginUrl
			(
				array
				(
					"canvas" => true,
					"fbconnect" => false,
//					"next" => "http://targetcms.com/apps/blog/index-welcome.php"
					"next" => "http://www.facebook.com/pages/Target-Test/136502313089548?sk=app_145431908857122"
				)
			);
			echo "<script type='text/javascript'>location.href = '"
				 . $login_url. "';</script>";
			exit;
		}
	}

	set_include_path("."
		. PATH_SEPARATOR . "../../../php/fb"
		. PATH_SEPARATOR . "../../../php/fb/$appName"
		. PATH_SEPARATOR . "../../../php/fb/$appName/common"
		. PATH_SEPARATOR . "../../../php/fb/$appName/lib"
		. PATH_SEPARATOR . get_include_path());

	// Start the session.
	session_start();

	error_reporting(E_ALL|E_STRICT);
	ini_set('display_errors', 1);
	date_default_timezone_set('Europe/Monaco');

	// Set the error and exception handlers.
	set_error_handler("my_error_handler");
	set_exception_handler('defaultException');

	require_once "fn.php";
	require_once "html.php";
	require_once "db.php";
	require_once "base.php";
	
	// The 'host' file contains the MySQL hostname and other things.
	$props = FN::getProperties("$base/host", FALSE);
	FN::setProperties($props);
	
	// Get the Page ID.
	// This allows us to restrict usage to just people on this Page.
	$signed_request = $facebook->getSignedRequest();
	$pid = $signed_request["page"]["id"];
	$admin = $signed_request["page"]["admin"];
	if ($pid) FN::setSessionValue("pid", $pid);
	if ($admin) FN::setSessionValue("admin", TRUE);
	
	// Get the user details.
	$user = $facebook->api("/".$uid);
	$name = isset($user["first-name"]) ? $user["first_name"] : NULL;
	$surname = isset($user["last-name"]) ? $user["last_name"] : NULL;

	// Connect to the database.
	DB::connect(
		$props["sqlhost"],
		$props["sqluser"],
		$props["sqlpassword"],
		$props["sqldatabase"]);
	
	// Deal with AJAX.
	if (isset($_REQUEST["ajax"]))
	{
		switch (FN::getRequest("function"))
		{
			case "countClick";
				require_once "page.php";
				Page::countClick();
				break;
		}
		exit;
	}
		
	if (isset($_REQUEST['admin']))
	{
		require_once "admin.php";
		$head = new HEAD();
		$document = new DOCUMENT($head, new BODY(new Admin($head)));
		echo $document->getHTML();
	}
	else
	{
		// Build the page content.
		$template = FN::getRequest("template", FN::getProperty("template"));
		require_once "$template.php";
		$template = ucfirst($template);
		// Create the HEAD.
		$head = new HEAD();
		$body = new $template($head);
		$body->add(new DIV("fb-root"));
		$body->add(new SCRIPT("scripts/fb-async.js"));
		$body->add(new SCRIPT("scripts/fb-api.js"));
		// Create a document object and send it back.
		$document = new DOCUMENT($head, new BODY($body));
		echo $document->getHTML();
	}
	exit;

	/////////////////////////////////////////////////////////////////////////
	// Get the Page ID.
	function getPID()
	{
		return FN::getSessionValue("pid");
	}

	/////////////////////////////////////////////////////////////////////////
	// Get the user ID.
	function getUID()
	{
		global $uid;
		return $uid;
	}

	/////////////////////////////////////////////////////////////////////////
	// Test if Admin.
	function isAdmin()
	{
		return FN::getSessionValue("admin");
	}

	/////////////////////////////////////////////////////////////////////////
	// Get the user's name.
	function getUserName($id = 0)
	{
		global $facebook;
		if ($id) $uid = $id;
		else global $uid;
		$user = $facebook->api("/".$uid);
		$name = $user["first_name"];
		return $name;
	}

	/////////////////////////////////////////////////////////////////////////
	// Get the user's surname.
	function getUserSurname($id = 0)
	{
		global $facebook;
		if ($id) $uid = $id;
		else global $uid;
		$user = $facebook->api("/".$uid);
		$name = $user["last_name"];
		return $name;
	}

	/////////////////////////////////////////////////////////////////////////
	// Get the user's full name.
	function getFullName($uid = 0)
	{
		$name = getUserName($uid);
		$surname = getUserSurname($uid);
		if ($surname) $name .= " $surname";
		return $name;
	}

	//////////////////////////////////////////////////////////////////////////
	// Format a date.
	function dateFormat($date)
	{
		$year = substr($date, 0, 4);
		$month = (int)substr($date, 4, 2);
		$day = (int)substr($date, 6, 2);

		$month = FN::getMonthName($month);
		switch ($day)
		{
			case 1:
			case 21:
			case 31:
				$suffix = "st"; break;
			case 2:
			case 22:
				$suffix = "nd"; break;
			case 3:
			case 23:
				$suffix = "rd"; break;
			default:
				$suffix = "th"; break;
		}
		$day .= $suffix;
		return "$month $day, $year";
	}

	////////////////////////////////////////////////////////////////////////////
	// Our custom error handler.
	function my_error_handler ($errno, $errstr, $errfile, $errline, $errcontent)
	{
		$to = "admin@targetcms.com";
		echo "<font color='red'><b>An Error Occured!</b></font><br />";
		echo "<font color='black'>";
		echo "<b>Error In File:</b> $errfile<br />";
		echo "<b>Error On Line:</b> $errline<br />";
		echo "<b>Error Number:</b> $errno<br />";
		echo "<b>Error Description:</b> $errstr<br /><br />";
		echo "This error has been reported to $to";
		echo "</font>";

		$ipaddr = $_SERVER['REMOTE_ADDR'];
		$hostName = getHostByAddr($ipaddr);
		$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : NULL;
		$page = $_SERVER['REQUEST_URI'];
		$request = print_r($_REQUEST, TRUE);
		$server = print_r($_SERVER, TRUE);


		$subject = "Target CMS - Error message";
		$message = "IP Address: $ipaddr ($hostName)\n\n"
			."Error in file: $errfile\n"
			."Error on line: $errline\n"
			."Error number: $errno\n"
			."Error description: $errstr\n\n"
			."Page requested: $page\n"
			.($referrer ? "Referrer: $referrer\n" : NULL)
			."\nRequest:\n$request\n"
			."\nServer:\n$server\n";
		$headers = "From:$to\n\n";
		mail($to, $subject, $message, $headers);
		exit;
	}

	////////////////////////////////////////////////////////////////////////////
	// Catch any exceptions that weren't handled anywhere else.
	function defaultException($e)
	{
		header('HTTP/1.x 555 Program Error');
		echo "<b>Exception:</b> " , $e->getMessage();
		exit;
	}
	
	
function base64_url_decode($input) {
  return base64_decode(strtr($input, '-_', '+/'));
}

?>