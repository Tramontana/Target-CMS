<?php
	///////////////////////////////////////////////////////////////////////////
	// This is one of the items on the TCMS home page.
	class HomeItem extends DIV
	{
		///////////////////////////////////////////////////////////////////////
		// Constructor.
		function __construct($head, $item)
		{
			parent::__construct($item, "homeItem");
			
			$this->add($block = new DIV(NULL, "block"));

			if ($item == "customer" || $item == "editor" || $item == "technical")
				$block->add(new DIV(NULL, "text", new A(NULL, NULL,
					FN::get("homeItem", "$item text"),
					FN::getPlain("homeItem", "$item link"))));
			else
				$block->add(new DIV(NULL, "boxtext",
					FN::get("homeItem", "$item")));
		}
	}
?>
