<?php
   ///////////////////////////////////////////////////////////////////////////
   // This is the forum template.
   class Forum extends DIV
   {
		// Set the number of results to be displayed on every page.
		const ROWS_PER_PAGE = 5;
		// Set the number of pages visible in the pager.
		const PAGER_SIZE = 20;
   	
		///////////////////////////////////////////////////////////////////////
		// Constructor.
		function __construct($head)
		{
			parent::__construct("forum");
			$head->addCSS("css/forum.css");

			// First we have a full-width masthead.
			$this->add(FN::getTemplate("masthead", $head));

			// Now an inner panel to apply margins etc.
			$this->add($main = new DIV("forum2"));

			// Prime the database.
			new dbForum();
			$action = isset($_REQUEST["action"]) ? $_REQUEST["action"] : NULL;
			switch ($action)
			{
				case "post":
					$main->add($this->postNewMessage());
					return;
				case "addMessage":
					$main->add($this->addMessage());
					return;
				case "delete":
					$main->add($this->deleteMessage());
					return;
				case "delete":
					$main->add($this->deleteMessage());
					return;
				case "reply":
					$main->add($this->reply());
					return;
				case "showReplies":
					$main->add($this->showReplies());
					return;
				case "gopage":
					$main->add($this->getMessageList($_REQUEST["parent"], $_REQUEST["p"]));
					return;
			}

			// Show the message list.
			$main->add($this->getMessageList());
		}

		/////////////////////////////////////////////////////////////////////////
		// Show the forum page with a selection of messages.
		// $parent The parent message ID.
		// $page The page (base zero) of a multi-page result.
		// $useSession If TRUE, use the session search key.
		function getMessageList($parent = 0, $page = 0, $useSession = FALSE)
		{
			$block = new BLOCK("Forum message list");
			$topLevel = FALSE;
			// If $useSession is set it's a request to list messages
			// with the keyword given by the session SESSION_TOPIC field.
			$topic = NULL;
			if ($useSession)
			{
				if (isset($_SESSION['FORUM_TOPIC']))
					$topic = str_replace("'", "\\'", $_SESSION['FORUM_TOPIC']);
			}

			$original = NULL;
			$rowCount = 0;
			if ($topic)
			{
				$title = "Messages having topic $topic";
				$result = DB::select("forum", array("id"),
					" WHERE keyword1='$topic' OR keyword2='$topic' OR keyword3='$topic'
						ORDER BY id DESC");
			}
			else if ($parent)
			{
				// If there's a parent we're listing replies.
				// We need to show the parent message too.
				$title = "Replies";
				$row = DB::selectRow("forum", "*", "WHERE id=$parent");
				$original = $row->id;
				// Now list the replies.
				$result = DB::select("forum", array("id"),
					"WHERE parent=$parent ORDER BY id DESC");
			}
			else
			{
				$topLevel = TRUE;
				$title = "Recent messages";
				// Get all messages that have no parent,
				$result = DB::select("forum", "*", "WHERE parent='' ORDER BY id DESC");
			}
			// Get one page (or less) of messages.
			$list = array();
			$target = $page * self::ROWS_PER_PAGE;
			if (DB::nRows($result)) DB::seek($result, $target);
			while ($row = DB::fetchRow($result))
			{
				if ($rowCount < self::ROWS_PER_PAGE) $list[] = $row->id;
				$rowCount++;
			}
			$rowCount += $target;

			// Create the pager based on the number of messages.
			$pager = new DIV(NULL, "pager");
			$nPages = (int)(($rowCount - 1) / self::ROWS_PER_PAGE) + 1;
			$last = $page + self::PAGER_SIZE / 2;
			if ($last > $nPages) $last = $nPages;
			$first = $last - self::PAGER_SIZE;
			if ($first < 0) $first = 0;
			$fields = array(
				"template"=>"forum",
				"action"=>"gopage",
				"parent"=>$parent
				);
			if ($nPages > 1)
			{
				if ($page > 0)
				{
					$fields["p"] = $page - 1;
					if ($useSession != NULL) $fields[] = "&us=$useSession";
					$pager->add(new A(NULL, NULL, "&lt;&lt", $fields));
				}
				$pager->add("Page");
				for ($n = $first; $n < $last; $n++)
				{
					if ($n == $page)
					{
						$pager->add(new SPAN(NULL, "pagerBold", $n + 1));
					}
					else
					{
						$fields["p"] = $n;
						if ($useSession != NULL) $fields[] = "&us=$useSession";
						$pager->add(new A(NULL, NULL, $n + 1, $fields));
					}
				}
				if ($page < $nPages - 1)
				{
					$fields["p"] = $page + 1;
					if ($useSession != NULL) $fields[] = "&us=$useSession";
					$pager->add(new A(NULL, NULL, "&gt;&gt", $fields));
				}
				$pager->add("&nbsp;($nPages pages)");
			}

			$block->add($pager);

			// Build the table.
			$block->add($table = new TABLE());
			if ($original)
			{
				$message = $this->getMessage($original, TRUE, $topLevel);
				$table->addRow($tr = new TR());
				$tr->addHeaderCell("Original message");
				$table->addRow($tr = new TR());
				$tr->addCell($message);
				$table->addRow($tr = new TR());
				$tr->addHeaderCell("<br>Replies (most recent first)");
			}
			else
			{
				$table->addRow($tr = new TR());
				$tr->addCell($td = new TD());
				$td->add(new H2(NULL, NULL, "Target CMS Forum"));
				$td->add(new B("Most recent messages"));
			}

			// List the messages.
			$oddEven = FALSE;
			foreach ($list as $id)
			{
				$message = $this->getMessage($id, FALSE, $topLevel, $oddEven);
				$table->addRow($tr = new TR());
				$tr->addCell($message);
				$oddEven = !$oddEven;
			}

			$block->add($pager);
			$block->add(new DIV());

			$replies = count($list);
			if ($topLevel)
				$block->add(new DIV(NULL, NULL,
					new A(NULL, NULL, "Post a new message",
						array(
							"template"=>"forum",
							"action"=>"post"
							))));
			else
				$block->add(new DIV(NULL, NULL,
					new A(NULL, NULL,
						" Add ".($replies ? "another" : "a")." reply", array(
							"template"=>"forum",
							"action"=>"reply",
							"id"=>$parent
							))));

			$block->add(new DIV());
			$block->add(new DIV(NULL, NULL,
				new A(NULL, NULL, "Search for messages",
					array(
						"template"=>"forum",
						"action"=>"search"
						))));
			if (!$topLevel)
			{
				$block->add(new DIV(NULL, NULL,
					new A(NULL, NULL, "Show all messages",
						array(
							"template"=>"forum",
							"action"=>"showAll"
							))));
			}
			return $block;
		}

		/////////////////////////////////////////////////////////////////////////
		// Show the replies to a message.
		function showReplies($index = NULL)
		{
			if (isset($_REQUEST['index'])) $index = $_REQUEST['index'];
			$content = $this->getMessageList($index);
			return $content;
		}

		/////////////////////////////////////////////////////////////////////////
		// Go to a specific result page.
		function goPage()
		{
			$us = FALSE;
			if (isset($_REQUEST['us'])) $us = TRUE;
			return $this->getMessageList($_REQUEST['parent'], $_REQUEST['p'], $us);
		}

		//////////////////////////////////////////////////////////////////////////
		// Get a message entry.
		function getMessage($id, $original, $topLevel, $oddEven = FALSE)
		{
			$nReplies = DB::countRows("forum", "WHERE parent=$id");
			$replies = "($nReplies repl" .(($nReplies == 1) ? "y)" : "ies)");
			$row = DB::selectRow("forum", "*", "WHERE id=$id");
			$actions = new SPAN();
			if ($topLevel)
			{
				if ($nReplies)
					$actions->add(new A(NULL, NULL, "Read the replies",
						array(
							"template"=>"forum",
							"action"=>"showReplies",
							"index"=>$id
							)));
				else
					$actions->add(new A(NULL, NULL, "Add the first reply", array(
						"template"=>"forum",
						"action"=>"reply",
						"id"=>$id
						)));
			}
			// Admin only functions.
			$loggedIn = FN::getSessionValue("loggedIn", 0);
			if ($loggedIn == 1)
			{
				$actions->add(new A(NULL, NULL,
					new IMG(NULL, NULL, "images/system/cut.gif", "Delete this message"),
						array(
							"template"=>"forum",
							"action"=>"delete",
							"id"=>$id
							)));
			}
			$div = new DIV(NULL, $original ? "original" : ($oddEven ? "odd" : "even"));
			$div->add($info = new DIV(NULL, "info"));
			$info->add("Posted ");
			$info->add(date("M d Y \a\\t H:i:s", $row->timestamp));
			$info->add(" by " . $row->name);
			if ($topLevel) $info->add($replies);
			$info->add($actions);
			$div->add($row->message);
			return $div;
		}

		/////////////////////////////////////////////////////////////////////////
		// Delete a message.
		function deleteMessage()
		{
			$id = $_REQUEST['id'];
			// Count the replies.
			if (!DB::countRows("forum", "WHERE parent=$id")) DB::delete("forum", "WHERE id=$id");
			return $this->showReplies(DB::selectValue("forum", "parent", "WHERE id=$id"));
		}

		/////////////////////////////////////////////////////////////////////////
		// Set up a search for messages.
		function searchFor()
		{
			$content = "This is a list of all topics given for messages in this Forum.<br>
				Click any one to see all the messages having that topic,<br>
				or click \"Select all\" to see all messages.<br><br>";
			$content .= $this->getKeywords();
			$content .= "<br><br>";
			$content .= HTML::getSubmitButton("forum", "Select all");
			return HTML::getFormPage("Select messages by topic", $content);
		}

		/////////////////////////////////////////////////////////////////////////
		// Show all messages with a given keyword.
		function selectKeyword()
		{
			$_SESSION['FORUM_TOPIC'] = $_REQUEST['keyword'];
			return $this->showMessageList(0, 0, TRUE);
		}

		/////////////////////////////////////////////////////////////////////////
		// Show all messages.
		function selectAll()
		{
			return $this->showMessageList(0, 0, FALSE);
		}

		/////////////////////////////////////////////////////////////////////////
		// Get the list of keywords.
		function getKeywords()
		{
			$kk = array();
			$result = DB::selectDistinct("forum",
				array("keyword1", "keyword2", "keyword3"));
			while ($row = DB::fetchRow($result))
			{
				$k1 = str_replace(" ", "&nbsp;", $row->keyword1);
				$k2 = str_replace(" ", "&nbsp;", $row->keyword2);
				$k3 = str_replace(" ", "&nbsp;", $row->keyword3);
				if ($k1 && !in_array($k1, $kk)) $kk[] = $k1;
				if ($k2 && !in_array($k2, $kk)) $kk[] = $k2;
				if ($k3 && !in_array($k3, $kk)) $kk[] = $k3;
			}
			natcasesort($kk);
			$keywords = new SPAN();
			$flag = FALSE;
			foreach ($kk as $keyword)
			{
				if ($flag) $keywords->add(" - ");
				$keywords->add(new A(NULL, NULL, $keyword, array(
					"module"=>"forum",
					"page"=>"selectKeyword",
					"keyword"=>str_replace("&nbsp;", " ", $keyword)
					)));
				$flag = TRUE;
			}
			return $keywords;
		}

		/////////////////////////////////////////////////////////////////////////
		// Reply to a forum message.
		function reply()
		{
			$id = $_REQUEST['id'];
			$row = DB::selectRow("forum", "*", "WHERE id=$id");
			return $this->postNewMessage($row->id, NULL, NULL, NULL,
				$row->keyword1, $row->keyword2, $row->keyword3);
		}

		/////////////////////////////////////////////////////////////////////////
		// Post a new forum message.
		function postNewMessage($parent = 0, $message = NULL, $text = NULL,
			$name = NULL, $keyword1 = NULL, $keyword2 = NULL, $keyword3 = NULL)
		{
			$form = new FORM("post");
			$form->add(new DIV(NULL, NULL, new LABEL(NULL, NULL, "Your message")));
			$form->add(new TEXTAREA(NULL, NULL, "text", $text, 60, 10));
			$form->add($div = new DIV());
			$div->add(new SPAN(NULL, NULL, new Label(NULL, NULL, "Your name: ")));
			$div->add(new TEXT(NULL, NULL, "name", $name, 30));
			$form->add($div = new DIV());
			$div->add(new SPAN(NULL, NULL, new Label(NULL, NULL, "Keywords: ")));
			$div->add(new TEXT(NULL, NULL, "keyword1", $keyword1, 15));
			$div->add(new TEXT(NULL, NULL, "keyword2", $keyword2, 15));
			$div->add(new TEXT(NULL, NULL, "keyword3", $keyword3, 15));

			$e = base64_encode(pack("h*", sha1(mt_rand())));
			$captcha = str_replace('l', '2', str_replace('O', '3',
				str_replace('0', '4', str_replace('1', '5', str_replace('o', '6',
				str_replace('I', '7', substr(strtr($e, "+/=", "xyz"), 0, 4)))))));
			$_SESSION['captcha'] = $captcha;
			$source = "captcha/captcha.php?captcha=$captcha";
			$form->add($capdiv = new DIV("captcha"));
			$capdiv->add($capright = new DIV("capright"));
			$capright->add($code = new DIV("code", NULL,
				FN::getPlain("contact", "captcha")));
			$code->add(new DIV(NULL, NULL, new TEXT(NULL, NULL, "captcha")));
			$capright->add(new DIV("image", NULL,
				new IMG(NULL, NULL, $source, "CAPTCHA image",
					array("width"=>140, "height"=>90))));
			$capdiv->add(new DIV(NULL, "clearboth"));

			$form->add(new HIDDEN("parent", $parent));
			$form->add(new HIDDEN("template", "forum"));
			$form->add(new HIDDEN("action", "addMessage"));
			$form->add(new SUBMIT(NULL, NULL, "submit", "Add Message"));
			// Get the text of the page.
			$keywords = $this->getKeywords();
			$text = FN::replace(FN::get("forum", "post"), array(
				"/MESSAGE/"=>$message,
				"/FORM/"=>$form->getHTML(),
				"/KEYWORDS/"=>$keywords->getHTML()
				));
			return $text;
		}

		/////////////////////////////////////////////////////////////////////////
		// Add a new forum message.
		function addMessage()
		{
			$parent = stripslashes($_REQUEST['parent']);
			$text = $_REQUEST['text'];
			$name = $_REQUEST['name'];
			$keyword1 = isset($_REQUEST['keyword1']) ? $_REQUEST['keyword1'] : NULL;
			$keyword2 = isset($_REQUEST['keyword2']) ? $_REQUEST['keyword2'] : NULL;
			$keyword3 = isset($_REQUEST['keyword3']) ? $_REQUEST['keyword3'] : NULL;
			$flags = isset($_REQUEST['mail']) ? "E" : NULL;

			$keywords = $this->getKeywords();
			if (!$text || !$name || !$keyword1)
				return $this->postNewMessage($parent,
					"Please fill in all fields including your name and at least one keyword",
					$text, $name, $keyword1, $keyword2, $keyword3);

         require_once "captcha/captcha.php";
         if (captcha_validate())
         {
				$keyword1 = strtolower($keyword1);
				$keyword2 = strtolower($keyword2);
				$keyword3 = strtolower($keyword3);
				$insertID = DB::insert("forum", array(
					"timestamp"=>time(),
					"parent"=>$parent,
					"message"=>$text,
					"name"=>$name,
					"keyword1"=>$keyword1,
					"keyword2"=>$keyword2,
					"keyword3"=>$keyword3
					));
            $ipaddr = $_SERVER['REMOTE_ADDR'];
            $to = FN::getPlain("contact", "email");
            $subject = "New TCMS forum message";
            $body = "Posted by: ".$name."\nIP: ".$ipaddr."\n\n"
               .$text;
            $headers = "From: forum@targetcms.com\n";
            mail($to,$subject,$body,$headers);
				return $this->getMessageList($parent);
         }
         unset($_REQUEST['send']);
			return $this->postNewMessage($parent,
				"The security code does not match. Please try again",
				$text, $name, $keyword1, $keyword2, $keyword3);
		}

		/////////////////////////////////////////////////////////////////////////
		// Go back.
		function back()
		{
			$parent = $_REQUEST['parent'];
			return $this->doForum($parent);
		}
	}

	////////////////////////////////////////////////////////////////////////////
	// The forum manager.
	class ForumManager extends DIV
	{
		private $head;

		function __construct($head)
		{
			parent::__construct("forumManager");
			$this->head = $head;
         $head->addCSS("css/forum.css");

			new dbForum();

			$this->add(new H1(NULL, NULL, "Forum Manager"));
			$this->add("The forum has no management features.");
			$this->add(new DIV());
			$this->add(new DIV(NULL, "navigator", new A(NULL, NULL,
				"Back to main menu", "?admin")));
		}
	}

	////////////////////////////////////////////////////////////////////////////
	// This is the forum database manager.
	class dbForum extends Base
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
				"forum"
				);
		}

		/////////////////////////////////////////////////////////////////////////
		// Get a field list.
		protected function getFieldList($name)
		{
			switch ($name)
			{
				case "forum":
					return array(
						"parent"=>"INT",
						"user"=>"INT",
						"timestamp"=>"INT",
						"name"=>"TEXT",
						"message"=>"TEXT",
						"keyword1"=>"CHAR(20)",
						"keyword2"=>"CHAR(20)",
						"keyword3"=>"CHAR(20)",
						"flags"=>"TEXT"
						);
			}
		}
	}
?>
