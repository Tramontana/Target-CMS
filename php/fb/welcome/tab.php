<?php
	///////////////////////////////////////////////////////////////////////////
	// This is the main application page.
	class Tab extends DIV
	{
		///////////////////////////////////////////////////////////////////////
		// Constructor.
		function __construct()
		{
			parent::__construct("blog");
			addCSS("css/blog.css");
			addScript("scripts/blog.js");
			
			// Prime the database.
			new dbTab();
			
		}
	}

	////////////////////////////////////////////////////////////////////////////
	// This is the editor.
	class Edit extends DIV
	{
		/////////////////////////////////////////////////////////////////////////
		// Constructor.
		function __construct($id = 0)
		{
			parent::__construct("edit");
			
			// If no ID is given, create a new blog page for this user.
			if (!$id)
			{
				// Add the user to the list of bloggers.
				$pid = getPID();
				$uid = getUID();
				if (DB::countRows("bloggers", "WHERE pid=$pid AND uid=$uid") == 0)
				{
					$blogger = DB::insert("bloggers", array(
						"pid"=>getPID(),
						"uid"=>$uid,
						"timestamp"=>time(),
						"title"=>"Information not yet provided"
						));
				}
				else $blogger = DB::selectValue("bloggers", "id", "WHERE uid=$uid");
				// Add the blogger's first blog page.
				$id = DB::insert("blogs", array(
					"blogger"=>$blogger,
					"timestamp"=>time(),
					"lastEdit"=>0,
					"date"=>date("Ymd"),
					"title"=>"This is the title of my new blog page",
					"text"=>"This is my new blog page"
					));
			}

			// Start editing.
			$row = DB::selectRow("blogs", "*", "WHERE id=$id");
			$timestamp = $row->timestamp;
			$date = $row->date;
			$title = stripslashes($row->title);
			$text = stripslashes($row->text);
			$year = substr($date, 0, 4);
			$month = substr($date, 4, 2);
			$day = substr($date, 6, 2);

			$this->add($form = new FORM("editBlog", NULL, "date"));
			$form->add(new H1(NULL, NULL, "Blog editor"));
			$form->add($table = new TABLE());

			$table->addRow($tr = new TR());
			$tr->addCell(new TD(NULL, "label",
				new LABEL(NULL, NULL, "Date:", "date")));
			$msel = new SELECT(NULL, NULL, "month",
				array("onchange"=>"populate2()"));
			$msel->add(new OPTION(NULL, NULL, "01", "January"));
			$msel->add(new OPTION(NULL, NULL, "02", "February"));
			$msel->add(new OPTION(NULL, NULL, "03", "March"));
			$msel->add(new OPTION(NULL, NULL, "04", "April"));
			$msel->add(new OPTION(NULL, NULL, "05", "May"));
			$msel->add(new OPTION(NULL, NULL, "06", "June"));
			$msel->add(new OPTION(NULL, NULL, "07", "July"));
			$msel->add(new OPTION(NULL, NULL, "08", "August"));
			$msel->add(new OPTION(NULL, NULL, "09", "September"));
			$msel->add(new OPTION(NULL, NULL, "10", "October"));
			$msel->add(new OPTION(NULL, NULL, "11", "November"));
			$msel->add(new OPTION(NULL, NULL, "12", "December"));
			$tr->addCell($td = new TD(NULL, "tatd"));
			$td->add(new SELECT(NULL, NULL, "day"));
			$td->add($msel);
			$td->add(new SELECT(NULL, NULL, "year"));

			$table->addRow($tr = new TR());
			$tr->addCell(new TD(NULL, "label",
				new LABEL(NULL, NULL, "Title:", "title")));
			$tr->addCell(new TD(NULL, "tatd",
				new TEXT(NULL, NULL, "title", $title)));

			$table->addRow($tr = new TR());
			$tr->addCell(new TD(NULL, "label", new LABEL(NULL, NULL,
				"Text:", "text")));
			$tr->addCell(new TD(NULL, "tatd",
				new TEXTAREA(NULL, NULL, "text", $text)));

			$table->addRow($tr = new TR());
			$tr->addCell();
			$tr->addCell($td = new TD());
			if (!$timestamp) $timestamp = time();
			if (!$title) $title = "New blog page";
			$td->add(new HIDDEN("template", "blog"));
			$td->add(new HIDDEN("id", $id));
			$td->add(new HIDDEN("save", TRUE));
			$td->add(new SUBMIT(NULL, NULL, "action", "Save blog"));
			$td->add(new SUBMIT(NULL, NULL, "action", "OK"));
			
			$this->add(new SCRIPT(NULL, "populate($day, $month, $year);"));
			$this->add(new SCRIPT(NULL, "bkLib.onDomLoaded(nicEditors.allTextAreas);"));
		}
	}

	////////////////////////////////////////////////////////////////////////////
	// Save changes.
	class Save
	{
		/////////////////////////////////////////////////////////////////////////
		// Constructor.
		function __construct($id)
		{
			if (FN::getRequest("action") == "OK") return;

			$id = FN::getRequest("id");
			$day = $_REQUEST['day'];
			$month = $_REQUEST['month'];
			$year = $_REQUEST['year'];
			$title = $_REQUEST['title'];
			$text = $_REQUEST['text'];
			if ($day < 10) $day = "0$day";
			$date = $year.$month.$day;
			DB::update("blogs", array(
				"date"=>$date,
				"title"=>$title,
				"text"=>$text
				), "WHERE id=$id");
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
						"pid"=>"BIGINT",
						"timestamp"=>"INT",
						"html"=>"TEXT"
						);
			}
		}
	}
?>