<?php
	///////////////////////////////////////////////////////////////////////////
	// This is the welcome page.
	class Welcome extends DIV
	{
		///////////////////////////////////////////////////////////////////////
		// Constructor.
		function __construct()
		{
			parent::__construct("welcome");
			addCSS("css/welcome.css");
			
			$this->add(new DIV(NULL, NULL, "Welcome to the Tabs main application page."));
			$this->add(new DIV());
			$this->add(new DIV(NULL, NULL, "You may prefer to return to the
				Profile of your application and click the Tabs tag again."));
		}
	}
?>