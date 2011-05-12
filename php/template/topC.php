<?php
	require_once "top.php";

	///////////////////////////////////////////////////////////////////////////
	// This is the top-level design template for the Customer section.
	class TopC extends Top
	{
		///////////////////////////////////////////////////////////////////////
		// Constructor.
		function __construct($head)
		{
			parent::__construct($head, "customer", "menuC");
		}
	}
?>
