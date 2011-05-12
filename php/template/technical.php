<?php
	////////////////////////////////////////////////////////////////////////////
	// The main content area for the TCMS Tech section.
	class Technical extends DIV
	{
		private $breadcrumbs;

		/////////////////////////////////////////////////////////////////////////
		// Constructor.
		function __construct($head)
		{
			parent::__construct("content");
			$head->addCSS("css/content.css");
			
			// If the content is specified, use it. Otherwise default to home.
			$content = isset($_REQUEST['content']) ? $_REQUEST['content'] : "home";
			
			// Place the content inside another DIV to enable a margin to be set.
			$this->add($inner = new DIV("content-inner"));
			
			// Check if the contact form is being asked for.
			if ($content == "contact")
			{
				$head->addCSS("css/contact.css");
				require_once "contact.php";
				$inner->add(Contact::doContact("topT", "technical"));
			}
			else
			{
				// Get regular content
				$inner->add(FN::get("technical", $content));
			}
			$link = new A(NULL, NULL, "Technical", "?template=topT&content=intro");
			$this->breadcrumbs = $link->getHTML() . " -> $content";
		}

		///////////////////////////////////////////////////////////////////////
		// Get the name of this module for the breadcrumbs trail.
		function getName()
		{
			return $this->breadcrumbs;
		}

		///////////////////////////////////////////////////////////////////////
		// Get the parent of this module for the breadcrumbs trail.
		function getParent()
		{
			require_once "home.php";
			return new Home();
		}
	}
?>
