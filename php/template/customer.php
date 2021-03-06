<?php
	////////////////////////////////////////////////////////////////////////////
	// The main customer content area for the TCMS Customer section.
	class Customer extends DIV
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
				$inner->add(Contact::doContact("topC", "customer"));
			}
			else if ($content == "payment")
			{
				require_once "payment.php";
				$inner->add(Payment::getPaymentButton());
			}
			else
			{
				// Get regular content
				$inner->add(FN::get("customer", $content));
			}
			$link = new A(NULL, NULL, "Customers", "?template=topC&content=intro");
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
