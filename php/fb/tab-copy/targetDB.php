<?php
	////////////////////////////////////////////////////////////////////////////
	// This is the database manager.
	class dbTarget extends Base
	{
		/////////////////////////////////////////////////////////////////////////
		// Constructor.
		function __construct($tab_id)
		{
			parent::__construct();
			if (!DB::countRows("app"))
			{
				echo "Please provide the following app information:<br>";
				$head = new HEAD();
				$body = new FORM(NULL, NULL, "appdata", "index$tab_id.php");
				$body->add(new HIDDEN("setup", TRUE));
				$body->add($table = new TABLE("#width: 100%"));
				$table->addRow($tr = new TR());
				$tr->addCell(new TD("#width: 1px; white-space: nowrap;", NULL, "App ID:"));
				$tr->addCell(new TEXT("#width: 100%", NULL, "app_id"));
				$table->addRow($tr = new TR());
				$tr->addCell(new TD("#width: 1px; white-space: nowrap;", NULL, "App secret:"));
				$tr->addCell(new TEXT("#width: 100%", NULL, "app_secret"));
				$table->addRow($tr = new TR());
				$tr->addCell();
				$tr->addCell(new SUBMIT(NULL, NULL, "action", "Submit"));
				$document = new DOCUMENT($head, new BODY($body));
				echo $document->getHTML();
				exit;
			}
		}

		/////////////////////////////////////////////////////////////////////////
		// Get the table list.
		protected function getTableList()
		{
			return array(
				"app"
				);
		}

		/////////////////////////////////////////////////////////////////////////
		// Get a field list.
		protected function getFieldList($name)
		{
			switch ($name)
			{
				case "app":
					return array(
						"tab"=>"INT",
						"app_id"=>"TEXT",
						"app_secret"=>"TEXT",
						);
			}
		}
	}
?>