<?php
	///////////////////////////////////////////////////////////////////////////
	// This is the top-level design template.
	class Top extends DIV
	{
		///////////////////////////////////////////////////////////////////////
		// Constructor.
		function __construct($head, $section, $menu)
		{
			parent::__construct("top");
			$head->addCSS("css/top.css");

			// First we have a full-width masthead.
			$this->add(FN::getTemplate("masthead", $head));
			
			// Get the template for the requested section.
			$template = FN::getTemplate($section, $head);
			
			// Next the breadcrumbs.
			require_once "breadcrumbs.php";
			$this->add(new Breadcrumbs($head, $template));

			// The top level of this design is a menu on the left
			// and a content area on the right.
			$this->add($table = new TABLE());
			$table->addRow($tr = new TR());
			$tr->add(new TD("left", NULL,
				FN::getTemplate("menu", $head, $menu)));
			$tr->addCell(new TD(NULL, NULL, $template));
         
         require_once "footer.php";
         $this->add(new Footer($head));
		}
	}
?>
