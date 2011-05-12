<?php
	///////////////////////////////////////////////////////////////////////////
	// This is the main application page.
	class Blog extends DIV
	{
		///////////////////////////////////////////////////////////////////////
		// Constructor.
		function __construct($head)
		{
			parent::__construct("blog");
			$head->addCSS("css/blog.css");
			$head->addScript("scripts/blog.js");
			
			// Prime the database.
			new dbBlog();
			
			// Read the database.
			$date = NULL;
			$title = NULL;
			$text = NULL;
			$where = NULL;
			$pid = getPID();
			$uid = FN::getRequest("uid", getUID());
			$id = FN::getRequest("id");
			if (isset($_REQUEST["help"]))
			{
				$blogger = DB::selectValue("bloggers", "id", "WHERE uid='0'");
				if ($blogger) $where = "WHERE blogger=$blogger";
			}
			else
			{
				if ($id)
					$where = "WHERE id=$id";
				else
				{
					$blogger = DB::selectValue("bloggers", "id", "WHERE uid='$uid'");
					if ($blogger) $where = "WHERE blogger=$blogger ORDER BY date DESC LIMIT 1";
				}
			}
			if ($where)
			{
				$row = DB::selectRow("blogs", "*", $where);
				if ($row)
				{
					$id = $row->id;
					$date = $row->date;
					$title = stripslashes($row->title);
					$text = stripslashes($row->text);
				}
			}
			
			$this->add($linkFrame = new DIV("linkFrame"));
			$linkFrame->add($welcome = new DIV("welcome", NULL, "Welcome, "));
			if (isAdmin()/*$uid == '813646163'*/)
					$welcome->add(new A(NULL, NULL, getUserName(), array(
						"template"=>"blog",
						"admin"=>1
						)));
			else $welcome->add(getUserName());
			$linkFrame->add($links = new DIV("links"));
			$linkFrame->add(new DIV(NULL, "clearboth"));
			$this->add($inner = new DIV("blogInner"));

			// Decode the request and add the necessary content.
			$showPage = FALSE;
			if (isset($_REQUEST["bloggers"]))
			{
				$inner->add(new Bloggers());
			}
			elseif (isset($_REQUEST["list"]))
			{
				$inner->add(new PageList());
			}
			elseif (isset($_REQUEST["new"]))
			{
				$head->addScript("scripts/date.js");
				$inner->add(new SCRIPT("http://js.nicedit.com/nicEdit-latest.js"));
				$inner->add(new Edit());
			}
			elseif (isset($_REQUEST["edit"]))
			{
				$head->addScript("scripts/date.js");
				$inner->add(new SCRIPT("http://js.nicedit.com/nicEdit-latest.js"));
				$inner->add(new Edit($id));
			}
			elseif (isset($_REQUEST["save"]))
			{
				new Save($id);
				$row = DB::selectRow("blogs", "*", "WHERE id=$id");
				$date = $row->date;
				$title = stripslashes($row->title);
				$text = stripslashes($row->text);
				$showPage = TRUE;
			}
			elseif (isset($_REQUEST["delete"]))
			{
				new Delete($id);
				$row = DB::selectRow("blogs", "*", "ORDER BY id DESC LIMIT 1");
				$date = $row->date;
				$title = stripslashes($row->title);
				$text = stripslashes($row->text);
				$showPage = TRUE;
			}
			else $showPage = TRUE;
			
			// Get the owner of the current blog page.
			$blogger = $id ? DB::selectValue("blogs", "blogger", "WHERE id=$id") : 0;
			if ($blogger)
			{
				$uid = DB::selectValue("bloggers", "uid", "WHERE id=$blogger");
			}
			$myid = getUID();

			// Build the links.
			$links->add(new DIV(NULL, "inline",
				new A(NULL, NULL, "Bloggers", array(
					"template"=>"blog",
					"id"=>$id,
					"bloggers"=>TRUE
					), array("title"=>"See the list of bloggers"))));
			$links->add(new DIV(NULL, "inline",
				new A(NULL, NULL, "Pages", array(
					"template"=>"blog",
					"uid"=>$uid,
					"list"=>TRUE
					), array("title"=>"List of blog pages"))));
			$links->add(new DIV(NULL, "inline",
				new A(NULL, NULL, "New", array(
					"template"=>"blog",
					"id"=>$id,
					"new"=>TRUE
					), array("title"=>"Write a new blog page"))));
			if ($myid == $uid || isAdmin())
			{
				$links->add(new DIV(NULL, "inline",
					new A(NULL, NULL, "Edit", array(
						"template"=>"blog",
						"id"=>$id,
						"edit"=>TRUE
						), array("title"=>"Edit the blog page you are viewing"))));
			}
			if ($uid == $myid)
			{
				$links->add(new DIV(NULL, "inline",
					new A(NULL, NULL, "Delete", array(
						"template"=>"blog",
						"id"=>$id,
						"delete"=>TRUE
						), array("title"=>"Delete the blog page you are viewing"))));
			}
			$links->add(new DIV(NULL, "inline",
				new A(NULL, NULL, "Help", array(
					"template"=>"blog",
					"help"=>TRUE
					), array("title"=>"Go to the Help page"))));
			if ($showPage)
			{
				$inner->add($div = new DIV("title"));
				$div->add(new DIV("text", NULL, $title));
				$div->add(new DIV("date", NULL, dateFormat($date)));
				$inner->add(new DIV(NULL, NULL, $text));
			}
		}
	}

	///////////////////////////////////////////////////////////////////////////
	// This is the blog list.
	class PageList extends DIV
	{
		///////////////////////////////////////////////////////////////////////
		// Constructor.
		function __construct()
		{
			parent::__construct("edit");

			$pid = getPID();
			$uid = FN::getRequest("uid", FN::getSessionValue("uid"));
			$fullName = getFullName($uid);
			if (!$fullName) $fullName = "(user)";
			$this->add(new H2(NULL, NULL, "Index of pages from $fullName"));
			$this->add($text = new DIV("blogText"));
			$blogger = DB::selectValue("bloggers", "id", "WHERE pid=$pid AND uid=$uid");
			if ($blogger)
			{
				// Read the database.
				$result = DB::select("blogs", "*",
					"WHERE blogger=$blogger ORDER BY date DESC");
				while ($row = DB::fetchRow($result))
				{
					$id = $row->id;
					$date = $row->date;
					$title = $row->title;
					$text->add(new DIV("date", NULL,
						new A(NULL, NULL, dateFormat($date), array(
							"template"=>"blog",
							"id"=>$id
							))));
				}
				DB::freeResult($result);
			}
		}

		/////////////////////////////////////////////////////////////////////////
		// Delete a blog.
		private function delete()
		{
			$id = $_REQUEST['id'];
			DB::delete("blogs", "WHERE id=$id");
		}
	}

	////////////////////////////////////////////////////////////////////////////
	// List the bloggers.
	class Bloggers extends DIV
	{
		/////////////////////////////////////////////////////////////////////////
		// Constructor.
		function __construct()
		{
			parent::__construct("edit");
			
			$this->add(new H2(NULL, NULL, "List of bloggers:"));
			$pid = getPID();
			$result = DB::select("bloggers", "*", "WHERE pid='$pid' AND uid!=0");
			while ($row = DB::fetchRow($result))
			{
				$uid = $row->uid;
				$fullName = getFullName($uid);
				if (!$fullName) $fullName = "(user)";
				$this->add($user = new DIV(NULL, NULL,
					new A(NULL, NULL, $fullName, array(
						"template"=>"blog",
						"uid"=>$uid,
						"list"=>TRUE
						))));
				$user->add("&nbsp;&nbsp;".$row->title);
			}
			DB::freeResult($result);
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
	// Delete a page.
	class Delete
	{
		/////////////////////////////////////////////////////////////////////////
		// Constructor.
		function __construct($id)
		{
			$id = FN::getRequest("id");
			DB::delete("blogs", "WHERE id=$id");
		}
	}

	////////////////////////////////////////////////////////////////////////////
	// This is the blog manager.
	class dbBlog extends Base
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
				"bloggers",
				"blogs"
				);
		}

		/////////////////////////////////////////////////////////////////////////
		// Get a field list.
		protected function getFieldList($name)
		{
			switch ($name)
			{
				case "bloggers":
					return array(
						"pid"=>"INT",
						"uid"=>"BIGINT",
						"timestamp"=>"INT",
						"title"=>"TEXT"
						);
				case "blogs":
					return array(
						"blogger"=>"INT",
						"timestamp"=>"INT",
						"lastEdit"=>"INT",
						"date"=>"CHAR(8)",
						"title"=>"TEXT",
						"text"=>"TEXT"
						);
			}
		}
	}
?>