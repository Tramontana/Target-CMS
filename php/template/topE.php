<?php
	require_once "top.php";

	///////////////////////////////////////////////////////////////////////////
	// This is the top-level design template for the Editor section.
	class TopE extends Top
	{
		///////////////////////////////////////////////////////////////////////
		// Constructor.
		function __construct($head)
		{
			parent::__construct($head, "editor", "menuE");
		}
	}
?>
