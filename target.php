<?php // 2010/06/28
/*
	Copyright (c) 2010, TCMS
	All rights reserved.
	
	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:
	    * Redistributions of source code must retain the above copyright
	      notice, this list of conditions and the following disclaimer.
	    * Redistributions in binary form must reproduce the above copyright
	      notice, this list of conditions and the following disclaimer in the
	      documentation and/or other materials provided with the distribution.
	    * Neither the name of the <organization> nor the
	      names of its contributors may be used to endorse or promote products
	      derived from this software without specific prior written permission.
	
	THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
	ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
	WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
	DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
	DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
	(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
	LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
	ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
	(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
	SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

	// This is not the website entry point; you'll find that
	// at www/index.php, which just calls this file.
	// The reason is to keep this file inaccessible to browsers.
        
	// Most of this file is to do with setting up the environment.
	// All the actual page content is generated elsewhere.

	error_reporting(E_ALL|E_STRICT);
	ini_set('display_errors', 1);
	date_default_timezone_set('Europe/Monaco');

	// Set the error and exception handlers.
	set_error_handler("my_error_handler");
	set_exception_handler('defaultException');

	// Set up the path.
	// All PHP files are kept where browsers can't access them.
	set_include_path('.'
		. PATH_SEPARATOR . ".."
		. PATH_SEPARATOR . "../php"
		. PATH_SEPARATOR . "../php/common"
		. PATH_SEPARATOR . "../php/lib"
		. PATH_SEPARATOR . "../php/template"
		. PATH_SEPARATOR . get_include_path());

	// Start the session.
	session_start();

	// Support files that will be needed everywhere.
	include_once "fn.php";
	include_once "db.php";
	include_once "html.php";
	include_once "base.php";
	include_once "exception.php";

	// The 'host' file contains the MySQL hostname and other things.
	$props = FN::getProperties("../host", FALSE);
	FN::setProperties($props);
	$title = $props['title'];
	$template = $props['template'];
	$language = $props['language'];
	DB::setTablePrefix($props['prefix']);

	//print_r($_REQUEST); echo"<br>";

	// Check if this is a call to admin.
	// Also, if this is the first time we've run there will be
	// a file called "install" which forces a trip to the admin.
	if (file_exists("install") || isset($_REQUEST['admin']))
	{
		require_once "admin.php";
		$head = new HEAD("$title Admin");
		$body = new DIV("pagecentre", NULL, new Admin($head, $props));
	}
	else
	{
		// Connect to the database.
		DB::connect($props['sqlhost'], $props['sqluser'], $props['sqlpassword'],
			$props['sqldatabase']);
		
		// Count hits.
		require_once "statistics.php";
		$statistics = new Statistics();
		$statistics->countHit();
			
      // Check if a language selection was made.
      if (isset($_REQUEST['language']))
      {
      	$language = DB::selectValue("language", "id",
            "WHERE code='".$_REQUEST['language']."'");
         FN::setSessionValue("language", $language);
      }
      else
      {
			// Set the default language.
			if (!FN::checkSessionValue("language"))
			{
				$id = DB::selectValue("language", "id", "WHERE code='$language'");
				if (!$id) $id = 1;
         	FN::setSessionValue("language", $id);
			}
      }

		// Check the language ID is a number; if not, force it.
		// This should never be the case but it causes problems...
		if (!is_numeric(FN::getSessionValue("language")))
			FN::setSessionValue("language", 1);

		// If a template is given, use it.
		if (isset($_REQUEST['template'])) $template = $_REQUEST['template'];
		require_once "$template.php";

		// Create the HEAD.
		$head = new HEAD($title);
		// Add the default CSS file.
		$head->addCSS("css/default.css");
		
		// This is where we build the page content.
		$body = new $template($head);
	}

	// Create a document object and send it back.
	$document = new DOCUMENT($head, new BODY($body));
	echo $document->getHTML();
	// end

	////////////////////////////////////////////////////////////////////////////
	// Our custom error handler.
	function my_error_handler ($errno, $errstr, $errfile, $errline, $errcontent)
	{
		echo "<font color='red'><b>An Error Occured!</b></font><br />";
		echo "<font color='#000'>";
		echo "<b>Error In File:</b> $errfile<br />";
		echo "<b>Error On Line:</b> $errline<br />";
		echo "<b>Error Number:</b> $errno<br />";
		echo "<b>Error Description:</b> $errstr<br /><br />";
		echo "This error has been reported to admin@targetcms.com";
		echo "</font>";

		$ipaddr = $_SERVER['REMOTE_ADDR'];
		$hostName = getHostByAddr($ipaddr);
		$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : NULL;
		$page = $_SERVER['REQUEST_URI'];

		$to = "admin@targetcms.com";
		$subject = "Target CMS - Error message";
		$message = "IP Address: $ipaddr ($hostName)\n\n"
			."Error in file: $errfile\n"
			."Error on line: $errline\n"
			."Error number: $errno\n"
			."Error description: $errstr\n\n"
			."Page requested: $page\n"
			.($referrer ? "Referrer: $referrer\n" : NULL);
		$headers = "From:noreply@targetcms.com\n\n";
		mail($to, $subject, $message, $headers);
		exit;
	}

	////////////////////////////////////////////////////////////////////////////
	// Catch any exceptions that weren't handled anywhere else.
	function defaultException($e)
	{
		header('HTTP/1.x 555 Program Error');
		print $e->showExceptionInfo(TRUE);
		exit;
	}
?>
