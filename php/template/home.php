<?php
	///////////////////////////////////////////////////////////////////////////
	// This is the TCMS home page template.
	class Home extends DIV
	{
		///////////////////////////////////////////////////////////////////////
		// Constructor.
		function __construct($head = NULL)
		{
			if (!$head) return;

			// Construct the screen using its CSS id.
			parent::__construct("home");
			$head->addCSS("css/home.css");

			// First we have a full-width masthead.
			$this->add(FN::getTemplate("masthead", $head));

			// Now an inner panel to apply margins etc.
			$this->add($main = new DIV("main"));

			// This template contains three similar sections.
			$main->add($left = new DIV("left", NULL,
				FN::getTemplate("homeItem", $head, "customer")));
			$main->add($right = new DIV("right"));
			$right->add(FN::getTemplate("homeItem", $head, "editor"));
			$right->add(FN::getTemplate("homeItem", $head, "technical"));
			$right->add(FN::getTemplate("homeItem", $head, "cms"));
			$right->add(FN::getTemplate("homeItem", $head, "blog"));
			$right->add(FN::getTemplate("homeItem", $head, "endorse"));
			$main->add(new DIV(NULL, "clearboth"));
			$left->add(new DIV("examples", NULL, FN::get("homeitem", "examples")));

			require_once "footer.php";
			$this->add(new Footer($head));
		}

		///////////////////////////////////////////////////////////////////////
		// Get the name of this module for the breadcrumbs trail.
		function getName()
		{
			return "Home";
		}

		///////////////////////////////////////////////////////////////////////
		// Get the URL of this module for the breadcrumbs trail.
		function getURL()
		{
			return "?template=home&content=home";
		}
	}
?>
