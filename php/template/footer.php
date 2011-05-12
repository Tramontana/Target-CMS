<?php
	///////////////////////////////////////////////////////////////////////////
	// This is the footer.
	class Footer extends BLOCK
	{
		///////////////////////////////////////////////////////////////////////
		// Constructor.
		function __construct($head)
		{
			parent::__construct("footer");
			$head->addCSS("css/footer.css");

			// Add our contact details.
         $this->add(new DIV("contact", NULL,
            FN::get("home", "contact")));
         
			$this->add(new SCRIPT(NULL,
				"\nvar gaJsHost = ((\"https:\" == document.location.protocol)"
				." ? \"https://ssl.\" : \"http://www.\");"
				."\ndocument.write(unescape(\"%3Cscript src='\""
				." + gaJsHost + \"google-analytics.com/ga.js'"
				." type='text/javascript'%3E%3C/script%3E\"));\n"
				));
			$this->add(new SCRIPT(NULL,
				"\ntry {"
				."\nvar pageTracker = _gat._getTracker(\"UA-15442926-1\");"
				."\npageTracker._trackPageview();"
				."\n} catch(err) {}\n"
				));
		}
	}
?>
