<?php
	set_include_path("."
		. PATH_SEPARATOR . "../../../php/fb"
		. PATH_SEPARATOR . "../../../php/fb/tab"
		. PATH_SEPARATOR . "../../../php/fb/tab/common"
		. PATH_SEPARATOR . "../../../php/fb/tab/lib"
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
	
	// The 'host' file contains the MySQL hostname and other things.
	$props = FN::getProperties("$base/host", FALSE);
	FN::setProperties($props);
	DB::setTablePrefix(FN::getProperty("prefix"));
	DB::setLocale($tab_id);

	// Connect to the database.
	DB::connect(
		$props["sqlhost"],
		$props["sqluser"],
		$props["sqlpassword"],
		$props["sqldatabase"]);
	DB::setTabPageList(array("content", "template"));
	
	$content = new DIV(NULL, NULL, FN::replace(FN::get("content", "welcome"), array(
		"/PID/"=>FN::getSessionValue("pid")
		)));
	$head = new HEAD();
	$document = new DOCUMENT($head, new BODY($content));
	echo $document->getHTML();
	exit;

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
?>