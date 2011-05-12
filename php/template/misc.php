<?php
   ///////////////////////////////////////////////////////////////////////////
   // This handles miscellaneous pages.
   class Misc extends DIV
   {
      ///////////////////////////////////////////////////////////////////////
      // Constructor.
      function __construct($head = NULL)
      {
      	if (!$head) return;

         parent::__construct("misc");
         $head->addCSS("css/misc.css");

         // First we have a full-width masthead.
         $this->add(FN::getTemplate("masthead", $head));
            
         // Now an inner panel to apply margins etc.
         $this->add($main = new DIV("main"));

			$content = isset($_REQUEST['content']) ? $_REQUEST['content'] : NULL;
         $main->add(new DIV(NULL, NULL, FN::get("misc", $content)));
         
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
