<?php
	///////////////////////////////////////////////////////////////////////////
	// This is the main application page.
	class Tab extends DIV
	{
		///////////////////////////////////////////////////////////////////////
		// Constructor.
		function __construct($head)
		{
			parent::__construct("tab");
			$head->addCSS("css/tab.css");
			
			// Prime the database.
			new dbTab();
			
			$pid = getPID();
			$uid = FN::getRequest("uid", getUID());
			if ($uid)
				$this->add(FN::get("content", isLiked() ? "liked" : "notliked"));
			else
				$this->add("qwerty");
			
			if (isAdmin())
				$this->add(new DIV(NULL, NULL,
					new A(NULL, NULL, "Admin", array(
						"template"=>"tab",
						"admin"=>TRUE
						))));
		}
	}

	////////////////////////////////////////////////////////////////////////////
	// This is the tab manager.
	class dbTab extends Base
	{
		/////////////////////////////////////////////////////////////////////////
		// Constructor.
		function __construct()
		{
			parent::__construct();
		}

		/////////////////////////////////////////////////////////////////////////
		// Get the table list.
		protected function getTableList()
		{
			return array(
				"tab"
				);
		}

		/////////////////////////////////////////////////////////////////////////
		// Get a field list.
		protected function getFieldList($name)
		{
			switch ($name)
			{
				case "tab":
					return array(
						"pid"=>"INT",
						"timestamp"=>"INT",
						"html"=>"TEXT"
						);
			}
		}
	}
?>