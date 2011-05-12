<?php
   ///////////////////////////////////////////////////////////////////////////
   // This is the blog template.
   class Blog extends DIV
   {
      ///////////////////////////////////////////////////////////////////////
      // Constructor.
      function __construct($head)
      {
         parent::__construct("blog");
         $head->addCSS("css/blog.css");

         // First we have a full-width masthead.
         $this->add(FN::getTemplate("masthead", $head));
            
         // Now an inner panel to apply margins etc.
         $this->add($main = new DIV("main"));
         
         // Prime the database.
         new dbBlog();

         // Do the left-hand panel.
         $main->add(new BlogLeft($head));
        
         // Do the right-hand panel with the index.
         $main->add(new BlogRight($head));
         
         $main->add(new DIV(NULL, "clearboth"));
         
         $this->add(new DIV("gohome", NULL, FN::get("blog", "return home")));
      }

      ///////////////////////////////////////////////////////////////////////
      // Format a date.
      static function dateFormat($date)
      {
         $year = substr($date, 0, 4);
         $month = (int)substr($date, 4, 2);
         $day = (int)substr($date, 6, 2);

         $month = FN::getMonthName($month);
         switch ($day)
         {
         	case 1:
         	case 21:
         	case 31:
         		$suffix = "st"; break;
         	case 2:
         	case 22:
         		 $suffix = "nd"; break;
         	case 3:
         	case 23:
         		$suffix = "rd"; break;
         	default:
         		$suffix = "th"; break;
         }
         $day .= $suffix;
         return "$month $day, $year";
      }
   }

   ///////////////////////////////////////////////////////////////////////////
   // This is the left-hand panel.
   class BlogLeft extends DIV
   {
      ///////////////////////////////////////////////////////////////////////
      // Constructor.
      function __construct($head)
      {
         parent::__construct("blogLeft");
         
         $this->add($inner = new DIV("blogInner"));
         
         // Read the database.
         if (isset($_REQUEST['id']))
         	$where = "WHERE id=" . $_REQUEST['id'];
         else
         	$where = "ORDER BY date DESC LIMIT 1";
         $row = DB::selectRow("blog", "*", $where);
         if ($row)
         {
	         $date = $row->date;
	         $title = stripslashes($row->title);
	         $text = stripslashes($row->text);
         }
         else
         {
         	$date = NULL;
         	$title = NULL;
         	$text = NULL;
         }
                  
         $inner->add($div = new DIV("title"));
         $div->add(new DIV("text", NULL, $title));
         $div->add(new DIV("date", NULL, Blog::dateFormat($date)));
         
         $inner->add(new DIV(NULL, NULL, $text));
      }
   }

   ///////////////////////////////////////////////////////////////////////////
   // This is the right-hand panel.
   class BlogRight extends DIV
   {
      ///////////////////////////////////////////////////////////////////////
      // Constructor.
      function __construct($head)
      {
         parent::__construct("blogRight");
         
         $this->add($inner = new DIV("blogInner"));
         $inner->add($title = new DIV("title", NULL, "Index of blogs"));
         $title->add(new A(NULL, NULL, new IMG(NULL, NULL, "upload/rss.gif",
         	"Subscribe to this feed"), "http://targetcms.com/blog.rss"));
         
         // Read the database.
         $result = DB::select("blog", "*", "ORDER BY date DESC");
         while ($row = DB::fetchRow($result))
         {
         	$id = $row->id;
         	$date = $row->date;
         	$title = $row->title;
         	if ($title)
	         	$inner->add(new DIV("date", NULL,
	         		new A(NULL, NULL, Blog::dateFormat($date), array(
	         			"template"=>"blog",
	         			"id"=>$id
	         			))));
         }
      }
   }

	////////////////////////////////////////////////////////////////////////////
	// The blog manager.
	class BlogManager extends DIV
	{
		private $head;

		function __construct($head)
		{
			parent::__construct("blogManager");
			$this->head = $head;
         $head->addCSS("css/blog.css");
			
			new dbBlog();
			
			$session = FN::getProperty("session");
			$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : NULL;
			switch ($action)
			{
				case "addBlog":
					DB::insert("blog", array(
						"timestamp"=>time(),
						"date"=>date("Ymd"),
						"language"=>1,
						"title"=>NULL,
						"text"=>NULL
						));
						$this->doRSS();
					break;
				case "edit":
					$this->add($this->edit());
					return;
				case "Save blog":
					$this->save();
					$this->add($this->edit());
					return;
				case "delete":
					$this->delete();
					break;
				default:
					break;
			}
			$this->add(new H1(NULL, NULL, "Blog Manager"));
			$this->add(new DIV(NULL, "navigator", new A(NULL, NULL,
				"Add new blog", array(
				"admin"=>1,
				"extra"=>1,
				"section"=>"blog",
				"action"=>"addBlog"
				))));
			$this->add(new DIV(NULL, "navigator", new A(NULL, NULL,
				"Back to main menu", "?admin")));
			$this->add(new DIV());

			$this->add($dropdowns = new DIV("dropdowns"));
			$dropdowns->add($form = new FORM(NULL, NULL, "template"));
			$dropdowns->add(new DIV(NULL, "clearboth20"));

			// Get the language selector.
			$language = isset($_REQUEST['language']) ? $_REQUEST['language'] : 0;
			$_SESSION["$session-language"] = $language;
			$form->add($languages = new DIV("languages", NULL, "Select a language:"));
			$languages->add($select = new SELECT(NULL, NULL, "language", array(
				"onchange"=>"onChangeTemplate();"
				)));
			$list = array();
			$result = DB::select("language", array("id", "code"));
			while ($row = DB::fetchRow($result)) $list[$row->code] = $row->id;
			DB::freeResult($result);
			ksort($list);
			$select->add(new OPTION(NULL, NULL, 0, NULL, $language == 0));
			foreach ($list as $lname=>$lid)
				$select->add(new OPTION(NULL, NULL, $lid, $lname, $lid == $language));
			
			// Show the pages for the given language.
			$this->add(new H2(NULL, NULL, "Blog list"));
			$this->add($items = new TABLE("items"));
			$where = NULL;
			if ($language) $where = "WHERE language=$language";
			$result = DB::select("blog", "*", $where);
			$alternate = 0;
			while ($row = DB::fetchRow($result))
			{
				$id = $row->id;
				$timestamp = $row->timestamp;
				$date = $row->date;
				$language = $row->language;
				$title = stripslashes($row->title);
				$text = stripslashes($row->text);
				$code = DB::selectValue("language", "code", "WHERE id=$language");
				
				$items->addRow($tr = new TR(NULL, "row$alternate"));
				$tr->addCell(new TD(NULL, "code", $code));
				$tr->addCell(new TD(NULL, "date", Blog::dateformat($date)));
				$tr->addCell(new TD(NULL, "title", $title));
				$tr->addCell(new TD(NULL, "button", new A(NULL, NULL,
					new IMG(NULL, NULL, "images/system/edit.gif", "Edit"),
						array(
							"admin"=>1,
							"extra"=>1,
							"section"=>"blog",
							"action"=>"edit",
							"id"=>$id
							))));
				$tr->addCell(new TD(NULL, "button", new A(NULL, NULL,
					new IMG(NULL, NULL, "images/system/cut.gif", "Delete"), "#",
						array(
							"onclick"=>"doGeneric(
								'Do you want to delete this blog?',
								'index.php?admin=1&extra=1&section=blog&action=delete&id=$id'
								);"
							))));
				$alternate = $alternate ? 0 : 1;
			}
			DB::freeResult($result);
		}

		/////////////////////////////////////////////////////////////////////////
		// Edit a single text string.
		private function edit()
		{
			$head = $this->head;
      	$head->addScript("scripts/date.js");
			$id = $_REQUEST['id'];
			$row = DB::selectRow("blog", "*", "WHERE id=$id");
			$timestamp = $row->timestamp;
			$date = $row->date;
			$language = $row->language;
			$category = stripslashes($row->category);
			$title = stripslashes($row->title);
			$text = stripslashes($row->text);
			$year = substr($date, 0, 4);
			$month = substr($date, 4, 2);
			$day = substr($date, 6, 2);
			$head->setOnLoad("populate($day, $month, $year)");
			$langcode = DB::selectValue("language", "code", "WHERE id=$language");

			// Get the language selector.
			$lselect = new SELECT(NULL, NULL, "language");
			$list = array();
			$result = DB::select("language", array("id", "code"));
			while ($row = DB::fetchRow($result)) $list[$row->code] = $row->id;
			DB::freeResult($result);
			ksort($list);
			foreach ($list as $lname=>$lid)
				$lselect->add(new OPTION(NULL, NULL, $lid, $lname, $lid == $language));

			$form = new FORM("editBlog", NULL, "date");
			$form->add(new H1(NULL, NULL, "Blog editor"));
			$form->add($table = new TABLE());

			$table->addRow($tr = new TR());
			$tr->addCell(new TD(NULL, "label",
				new LABEL(NULL, NULL, "Language:", "language")));
			$tr->addCell(new TD(NULL, "tatd", $lselect));

			$table->addRow($tr = new TR());
			$tr->addCell(new TD(NULL, "label",
				new LABEL(NULL, NULL, "Date:", "date")));
			$month = new SELECT(NULL, NULL, "month",
				array("onchange"=>"populate2()"));
			$month->add(new OPTION(NULL, NULL, "01", "January"));
			$month->add(new OPTION(NULL, NULL, "02", "February"));
			$month->add(new OPTION(NULL, NULL, "03", "March"));
			$month->add(new OPTION(NULL, NULL, "04", "April"));
			$month->add(new OPTION(NULL, NULL, "05", "May"));
			$month->add(new OPTION(NULL, NULL, "06", "June"));
			$month->add(new OPTION(NULL, NULL, "07", "July"));
			$month->add(new OPTION(NULL, NULL, "08", "August"));
			$month->add(new OPTION(NULL, NULL, "09", "September"));
			$month->add(new OPTION(NULL, NULL, "10", "October"));
			$month->add(new OPTION(NULL, NULL, "11", "November"));
			$month->add(new OPTION(NULL, NULL, "12", "December"));
			$tr->addCell($td = new TD(NULL, "tatd"));
			$td->add(new SELECT(NULL, NULL, "day"));
			$td->add($month);
			$td->add(new SELECT(NULL, NULL, "year"));

			$table->addRow($tr = new TR());
			$tr->addCell(new TD(NULL, "label",
				new LABEL(NULL, NULL, "Category:", "category")));
			$tr->addCell(new TD(NULL, "tatd",
				new TEXT(NULL, NULL, "category", $category)));

			$table->addRow($tr = new TR());
			$tr->addCell(new TD(NULL, "label",
				new LABEL(NULL, NULL, "Title:", "title")));
			$tr->addCell(new TD(NULL, "tatd",
				new TEXT(NULL, NULL, "title", $title)));

			$table->addRow($tr = new TR());
			$tr->addCell(new TD(NULL, "label", new LABEL(NULL, NULL,
				"Text:", "text")));
			$tr->addCell($td = new TD(NULL, "tatd",
				new TEXTAREA(NULL, NULL, "text", $text)));
			$td->add(new SCRIPT(NULL, "CKEDITOR.replace('text')"));
			
			$table->addRow($tr = new TR());
			$tr->addCell();
			$tr->addCell($td = new TD());
			$td->add(new HIDDEN("admin", 1));
			$td->add(new HIDDEN("extra", 1));
			$td->add(new HIDDEN("section", "blog"));
			$td->add(new HIDDEN("id", $id));
			$td->add(new HIDDEN("timestamp", $timestamp));
			$td->add(new SUBMIT(NULL, NULL, "action", "Save blog"));
			$td->add(new SUBMIT(NULL, NULL, "action", "OK"));

			return $form;
		}

		/////////////////////////////////////////////////////////////////////////
		// Save the text.
		private function save()
		{
			$id = $_REQUEST['id'];
			$timestamp = $_REQUEST['timestamp'];
			$day = $_REQUEST['day'];
			$month = $_REQUEST['month'];
			$year = $_REQUEST['year'];
			$category = $_REQUEST['category'];
			$title = $_REQUEST['title'];
			$text = $_REQUEST['text'];
			$language = isset($_REQUEST['language']) ? $_REQUEST['language'] : NULL;
			if (!$language) $language = isset($_SESSION['language'])
				? $_SESSION['language'] : NULL;
			if ($day < 10) $day = "0$day";
			$date = $year.$month.$day;
			DB::update("blog", array(
				"timestamp"=>$timestamp,
				"date"=>$date,
				"language"=>$language,
				"category"=>$category,
				"title"=>$title,
				"text"=>$text
				), "WHERE id=$id");
		}

		/////////////////////////////////////////////////////////////////////////
		// Delete a blog.
		private function delete()
		{
			$id = $_REQUEST['id'];
			DB::delete("blog", "WHERE id=$id");
			$this->doRSS();
		}

		////////////////////////////////////////////////////////////////////////////
		// Regenerate the RSS feed.
		public function doRSS()
		{
			$rss = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>
<rss version=\"0.91\">

	<channel>
		<title>Target CMS</title>
		<link>http://targetcms.com/</link>
		<description>Target CMS blog</description>
		<language>en-uk</language>

		<image>
		<title>Target CMS</title>
		<url>http://targetcms.com/upload/rsslogo.gif</url>
		<link>http://targetcms.com/</link>
		<width>110</width>
		<height>89</height>
		</image>

/ITEMS/
	</channel>
</rss>";
			
			$item = "	<item>
		<title>/TITLE/</title>
		<link>/LINK/</link>
		<description>
			/DESCRIPTION/
		</description>
	</item>\n";

			$items = NULL;
			$result = DB::select("blog", "*", "ORDER BY id DESC LIMIT 25");
			if (!DB::nRows($result)) return;
			while ($row = DB::fetchRow($result))
			{
				$id = $row->id;
				$date = $row->date;
				$title = $row->title;
				$text = $row->text;
				$link = "http://targetcms.com/index.php?template=blog&id=$id";
				$date = Blog::dateFormat($date);
				$description = "Blog date: $date\n".substr($text, 0, 60)."...";
				$items .= FN::replace($item, array(
					"/TITLE/"=>$this->escapeChars($title),
					"/LINK/"=>$this->escapeChars($link),
					"/DESCRIPTION/"=>$this->escapeChars($description)
					));
			}
			DB::freeResult($result);

			$fileName = "blog.rss";
			$file = fopen($fileName, "w") or die("Can't open file: $fileName<br>");
			fwrite($file, FN::replace($rss, array(
				"/ITEMS/"=>$items
				)));
			fclose($file);
		}

		//////////////////////////////////////////////////////////////////////
		// Escape special characters.
		function escapeChars($text)
		{
			return str_replace(
				array("&",     '"',      "'",      "<",    ">",),
				array("&amp;", "&quot;", "&apos;", "&lt;", "&gt;"),
				$text);
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
				"blog"
				);
		}

		/////////////////////////////////////////////////////////////////////////
		// Get a field list.
		protected function getFieldList($name)
		{
			switch ($name)
			{
				case "blog":
					return array(
						"timestamp"=>"INT",
						"date"=>"CHAR(8)",
						"language"=>"INT",
						"category"=>"TEXT",
						"title"=>"TEXT",
						"text"=>"TEXT"
						);
			}
		}
	}
?>