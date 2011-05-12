<?php
	require_once "top.php";

	///////////////////////////////////////////////////////////////////////////
	// This is the top-level design template for the Technical section.
	class TopT extends Top
	{
		///////////////////////////////////////////////////////////////////////
		// Constructor.
		function __construct($head)
		{
			parent::__construct($head, "technical", "menuT");
		}
	}
?>
