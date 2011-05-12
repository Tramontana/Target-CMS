<?php // 1006628
/*
	Copyright (c) 2010, TCMS
	All rights reserved.
	
	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:
	    * Redistributions of source code must retain the above copyright
	      notice, this list of conditions and the following disclaimer.
	    * Redistributions in binary form must reproduce the above copyright
	      notice, this list of conditions and the following disclaimer in the
	      documentation and/or other materials provided with the distribution.
	    * Neither the name of the <organization> nor the
	      names of its contributors may be used to endorse or promote products
	      derived from this software without specific prior written permission.
	
	THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
	ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
	WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
	DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
	DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
	(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
	LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
	ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
	(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
	SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

////////////////////////////////////////////////////////////////////////////
	// This is the admin package.
	class Admin extends DIV
	{
		function __construct($head, $props)
		{
			$head->addCSS("css/default.css");
			$head->addCSS("css/admin/admin.css");
			$head->addScript("scripts/admin.js");
			$head->addScript("ckeditor/ckeditor.js");

			// Connect to the database.
			$sqlhost = FN::getProperty('sqlhost');
			$sqluser = FN::getProperty('sqluser');
			$sqlpassword = FN::getProperty('sqlpassword');
			$sqldatabase = FN::getProperty('sqldatabase');
			DB::connect($sqlhost, $sqluser, $sqlpassword, $sqldatabase);
			
			// Create the tables if needed.
			new dbEditor();

			$message = NULL;
			$loginFlag = FALSE;
			$session = FN::getProperty("session");
			if (isset($_REQUEST['section']))
			{
				$section = FN::sanitise($_REQUEST['section']);
				if ($section == "Login")
				{
					$user = FN::sanitise($_REQUEST['user']);
					$password = FN::sanitise($_REQUEST['password']);
					if ($user == $props['user']
						&& $password == $props['password'])
							$loggedIn = 1;
					else if ($user == $props['user2']
						&& $password == $props['password2'])
							$loggedIn = 2;
					else $loggedIn = 0;
					if ($loggedIn)
					{						
						setcookie($session, 1, time() + 60 * 60 * 24);
						$loginFlag = TRUE;
						if (file_exists("install")) unlink("install");
						FN::setSessionValue("loggedIn", $loggedIn);
					}
					else
						$this->add(new DIV(NULL, NULL, "Bad user or password"));
				}
				else
				{
					if (!FN::checkSessionValue("loggedIn"))
					{
						setcookie($session, NULL, time()-3600);
						unset($_COOKIE[$session]);
						$section = NULL;
					}
					else switch ($section)
					{
						case "logout":
							setcookie($session, NULL, time()-3600);
							unset($_COOKIE[$session]);
							FN::unsetSessionValue("loggedIn");
							break;
						case "export":
							$module = new dbEditor();
							$module->export();
				      	$sections = FN::getProperty("sections");
				      	if ($sections)
				      	{
					      	$sections = explode(",", $sections);
					      	foreach ($sections as $section)
					      	{
					      		$section = $this->lcfirst($section);
					      		require_once "$section.php";
					      		$name = "db$section";
					      		$module = new $name();
					      		$module->export();
					      	}
				      	}
							$message = "All data has been exported";
							// Now backup the current file set.
							$backup = FN::getProperty("backup");
							if ($backup)
							{
								$message .= "<br />Backing up using $backup";
								exec($backup);
							}
							else $message .= "<br />No backup specified";
							break;
						case "import":
							$module = new dbEditor();
							$module->import();
				      	$sections = FN::getProperty("sections");
				      	if ($sections)
				      	{
					      	$sections = explode(",", $sections);
					      	foreach ($sections as $section)
					      	{
					      		$section = $this->lcfirst($section);
					      		require_once "$section.php";
					      		$name = "db$section";
					      		$module = new $name();
					      		$module->import();
					      	}
				      	}
							$message = "All data has been imported";
							break;
						case "hits":
							parent::__construct("editor");
							require_once "statistics.php";
							$statistics = new Statistics();
							$this->add($statistics->daily($head));
							return;
						default:
							if (in_array($section, array(
								"content", "template", "language",
								"menu", "image", "php", "style"
								)))
							{
								parent::__construct("editor");
								setcookie($session, 1, time() + 60 * 60 * 24);
	      					$manager = $section . "Manager";
								$this->add(new $manager($head));
								return;
							}
							if (FN::fileExists($section))
							{
								parent::__construct("editor");
								setcookie($session, 1, time() + 60 * 60 * 24);
								require_once "$section.php";
	      					$manager = $section . "Manager";
	      					$this->add(new $manager($head));
	      					return;
							}
							$message = "Unknown manager '$section'";
					}
				}
			}
			parent::__construct("editor", NULL,
				new H1(NULL, NULL, "Main admin menu"));
			// Check if a cookie is present. If not, show the login screen.
			if ($loginFlag || isset($_COOKIE[$session]))
			{
				// Refresh the cookie.
				setcookie($session, 1, time() + 60 * 60 * 24);
				
				// Do the main menu. First the main items.
				foreach (array("Content", "Template", "Language",
					"Menu", "Image") as $title)
					$this->add(new DIV(NULL, "menuItem",
						new A(NULL, NULL, "$title manager", array(
							"admin"=>1,
							"section"=>strtolower($title),
							"action"=>"list"
							))));
				// Next the extensions, if any.
	      	$sections = FN::getProperty("sections");
	      	if ($sections)
	      	{
		      	$sections = explode(",", $sections);
		      	foreach ($sections as $section)
		      	{
		      		$title = $section;
		      		$section = strtolower($section);
						$this->add(new DIV(NULL, "menuItem",
							new A(NULL, NULL, "$title manager", array(
								"admin"=>1,
								"extra"=>1,
								"section"=>strtolower($title)
								))));
		      	}
				}
				// Finally the special superuser items.
				$loggedIn = FN::getSessionValue("loggedIn", 0);
				if ($loggedIn == 1)
				{
					foreach (array("PHP", "Style") as $title)
						$this->add(new DIV(NULL, "menuItem",
							new A(NULL, NULL, "$title manager", array(
								"admin"=>1,
								"section"=>strtolower($title),
								"action"=>"list"
								))));
					$this->add(new DIV(NULL, "menuItem",
						new A(NULL, NULL, "Export the tables to www/export",
							array(
								"admin"=>1,
								"section"=>"export"
								))));
//					$this->add(new DIV(NULL, "menuItem",
//						new A(NULL, NULL, "Import the tables from www/export",
//							array(
//								"admin"=>1,
//								"section"=>"import"
//								))));
					$this->add(new DIV(NULL, "menuItem",
						new A(NULL, NULL, "Show daily hits",
							array(
								"admin"=>1,
								"section"=>"hits"
								))));
				}
				$this->add(new DIV(NULL, "menuItem",
					new A(NULL, NULL, "Log out",
						array(
							"admin"=>1,
							"section"=>"logout"
							))));
				$this->add(new DIV(NULL, "menuItem",
					new A(NULL, NULL, "Return to the site home page", "index.php")));
				$this->add(new DIV(NULL, "clearboth"));
				if ($message) $this->add(new DIV("message", NULL, $message));
			}
			else
			{
				// Show the login screen.
				$this->add($form = new FORM(NULL, NULL, "login"));
				$form->add(new DIV(NULL, NULL, "user:"));
				$form->add(new TEXT(NULL, NULL, "user"));
				$form->add(new DIV(NULL, NULL, "password:"));
				$form->add(new PASSWORD(NULL, NULL, "password"));
				$form->add(new DIV());
				$form->add(new HIDDEN("admin", 1));
				$form->add(new SUBMIT(NULL, NULL, "section", "Login"));
				$this->add(new DIV());
				$this->add(new DIV(NULL, NULL,
					new A(NULL, NULL, "Return to the site home page", "index.php")));
			}
		}

		/////////////////////////////////////////////////////////////////////////
		// Convert the first character of a string to lower case.
		function lcfirst($str)
		{
			return strtolower(substr($str, 0, 1)) . substr($str, 1);
		}
	}

	////////////////////////////////////////////////////////////////////////////
	// The content manager.
	class ContentManager extends DIV
	{
		function __construct()
		{
			parent::__construct("contentManager");
			
			$session = FN::getProperty("session");
			$action = isset($_REQUEST['action']) ? FN::sanitise($_REQUEST['action']) : NULL;
			switch ($action)
			{
				case "addContent":
					DB::insert("content", array(
						"name"=>NULL,
						"note"=>NULL,
						"template"=>0,
						"language"=>1
						));
					break;
				case "edit":
					$this->add($this->edit());
					return;
				case "Save text":
					$this->save();
					$this->add($this->edit());
					return;
				case "delete":
					$this->delete();
					break;
				default:
					break;
			}
			$this->add(new H1(NULL, NULL, "Content Manager"));
			$this->add(new DIV(NULL, "navigator", new A(NULL, NULL,
				"Add new content", array(
				"admin"=>1,
				"section"=>"content",
				"action"=>"addContent"
				))));
			$this->add(new DIV(NULL, "navigator", new A(NULL, NULL,
				"Back to main menu", "?admin")));

			$this->add($dropdowns = new DIV("dropdowns"));
			$dropdowns->add($form = new FORM(NULL, NULL, "template"));
			$dropdowns->add(new DIV(NULL, "clear"));

			// Get the language selector.
			$language = isset($_REQUEST['language']) ? (int)$_REQUEST['language'] : 0;
			FN::setSessionValue("language", $language);
			$form->add($languages = new DIV("languages", NULL, "Select a language:"));
			$languages->add($select = new SELECT(NULL, NULL, "language", array(
				"onchange"=>"onChangeTemplate();"
				)));
			$list = array();
			$result = DB::select("language", array("id", "code"));
			while ($row = DB::fetchRow($result)) $list[$row->code] = $row->id;
			DB::freeResult($result);
			ksort($list);
			$select->add(new OPTION(NULL, NULL, 0, "&nbsp;", $language == 0));
			foreach ($list as $lname=>$lid)
				$select->add(new OPTION(NULL, NULL, $lid, $lname, $lid == $language));
			
			// Get the template selector.
			$template = isset($_REQUEST['template']) ? (int)$_REQUEST['template'] : 0;
			if (!$template && FN::checkSessionValue("template"))
				$template = FN::getSessionValue("template");
			if (!$template) $template = 1;
			FN::setSessionValue("template", $template);
			$form->add($templates = new DIV("templates", NULL, "Select a template:"));
			$templates->add($select = new SELECT(NULL, NULL, "template", array(
				"onchange"=>"onChangeTemplate();"
				)));
			//$select->add(new OPTION(NULL, NULL, 0, NULL));
			$result = DB::select("template", "*", "ORDER BY name");
			while ($row = DB::fetchRow($result))
			{
				$tpid = $row->id;
				$tp = $row->name;
				$select->add(new OPTION(NULL, NULL, $tpid, $tp, $tpid == $template));
			}
			DB::freeResult($result);

			// Show the pages for the given template and language.
			$name = $template ? DB::selectValue("template", "name",
				"WHERE id=$template") : NULL;
			$this->add(new H2(NULL, NULL, "Content for $name"));
			$this->add($items = new TABLE("items"));
			$where = "WHERE (template='$template' OR template=0)";
			if ($language) $where .= " AND language=$language";
			$where .= " ORDER BY name";
			$result = DB::select("content", "*", $where);
			$alternate = 0;
			while ($row = DB::fetchRow($result))
			{
				$id = $row->id;
				$name = $row->name;
				$note = stripslashes($row->note);
				$language = $row->language;
				$code = DB::selectValue("language", "code", "WHERE id=$language");
				
				$items->addRow($tr = new TR(NULL, "row$alternate"));
				$tr->addCell(new TD(NULL, "code", $code));
				$tr->addCell(new TD(NULL, "name", $name));
				$tr->addCell(new TD(NULL, NULL, $note));
				$tr->addCell(new TD(NULL, "button", new A(NULL, NULL,
					new IMG(NULL, NULL, "images/system/edit.gif", "Edit"),
						array(
							"admin"=>1,
							"section"=>"content",
							"action"=>"edit",
							"id"=>$id
							))));
				$tr->addCell(new TD(NULL, "button", new A(NULL, NULL,
					new IMG(NULL, NULL, "images/system/cut.gif", "Delete"), "#",
						array(
							"onclick"=>"deleteItem('content', $id);"
							))));
				$alternate = $alternate ? 0 : 1;
			}
			DB::freeResult($result);
		}

		/////////////////////////////////////////////////////////////////////////
		// Edit a single text string.
		private function edit()
		{
			$id = (int)$_REQUEST['id'];
			$row = DB::selectRow("content", "*", "WHERE id=$id");
			$name = $row->name;
			$language = $row->language;
			$note = htmlentities(stripslashes($row->note));
			$value = stripslashes($row->value);
			$langcode = DB::selectValue("language", "code", "WHERE id=$language");
			// Check if a parameter was passed.
			if (isset($_REQUEST['template']))
			{
				$template = (int)$_REQUEST['template'];
			}
			else $template = $row->template;
			$session = FN::getProperty("session");
			if (!$template) $template = FN::getSessionValue("template");
			
			// Get the template selector.
			$tselect = new SELECT("template", NULL, "template");
			$list = array();
			$result = DB::select("template", array("id", "name"));
			while ($row = DB::fetchRow($result)) $list[$row->name] = $row->id;
			DB::freeResult($result);
			ksort($list);
			foreach ($list as $tname=>$tid)
				$tselect->add(new OPTION(NULL, NULL, $tid, $tname, $tid == $template));

			// Get the language selector.
			$lselect = new SELECT(NULL, NULL, "language");
			$list = array();
			$result = DB::select("language", array("id", "code"));
			while ($row = DB::fetchRow($result)) $list[$row->code] = $row->id;
			DB::freeResult($result);
			ksort($list);
			foreach ($list as $lname=>$lid)
				$lselect->add(new OPTION(NULL, NULL, $lid, $lname, $lid == $language));

			$form = new FORM("editContent");
			$form->add(new H1(NULL, NULL, "Text editor"));
			$form->add($table = new TABLE());

			$table->addRow($tr = new TR());
			$tr->addCell(new TD(NULL, "label",
				new LABEL(NULL, NULL, "Template:", "template")));
			$tr->addCell($td = new TD(NULL, "tatd", $tselect));

			$td->add(new LABEL(NULL, NULL, "Language:", "language"));
			$td->add($lselect);

			$table->addRow($tr = new TR());
			$tr->addCell(new TD(NULL, "label",
				new LABEL(NULL, NULL, "Name:", "name")));
			$tr->addCell(new TD(NULL, "tatd",
				new TEXT(NULL, NULL, "name", $name)));

			$table->addRow($tr = new TR());
			$tr->addCell(new TD(NULL, "label",
				new LABEL(NULL, NULL, "Note:", "note")));
			$tr->addCell(new TD(NULL, "tatd",
				new TEXT(NULL, NULL, "note", $note)));

			$table->addRow($tr = new TR());
			$tr->addCell(new TD(NULL, "label", new LABEL(NULL, NULL,
				"Value:", "Value")));
			$tr->addCell($td = new TD(NULL, "tatd",
				new TEXTAREA(NULL, NULL, "value", $value)));
			$td->add(new SCRIPT(NULL, "CKEDITOR.replace('value')"));
			
			$table->addRow($tr = new TR());
			$tr->addCell();
			$tr->addCell($td = new TD());
			$td->add(new HIDDEN("admin", 1));
			$td->add(new HIDDEN("text", 1));
			$td->add(new HIDDEN("section", "content"));
			$td->add(new HIDDEN("id", $id));
			$td->add(new SUBMIT(NULL, NULL, "action", "Save text"));
			$td->add(new SUBMIT(NULL, NULL, "action", "OK"));

			return $form;
		}

		/////////////////////////////////////////////////////////////////////////
		// Save the text.
		private function save()
		{
			$id = (int)$_REQUEST['id'];
			$template = (int)$_REQUEST['template'];
			$name = FN::sanitise($_REQUEST['name']);
			$note = $_REQUEST['note'];
			$language = isset($_REQUEST['language']) ? (int)$_REQUEST['language'] : NULL;
			$value = $_REQUEST['value'];
			if (!$language) $language = FN::getSessionValue("language");
			DB::update("content", array(
				"template"=>$template,
				"name"=>$name,
				"note"=>$note,
				"language"=>$language,
				"value"=>$value,
				"user"=>0,
				"timestamp"=>time(),
				"attributes"=>NULL
				), "WHERE id=$id");
		}

		/////////////////////////////////////////////////////////////////////////
		// Delete a text item.
		private function delete()
		{
			$id = (int)$_REQUEST['id'];
			DB::delete("content", "WHERE id=$id");
		}
	}

	////////////////////////////////////////////////////////////////////////////
	// The template manager.
	class TemplateManager extends DIV
	{
		function __construct()
		{
			parent::__construct("templateManager");

			$action = isset($_REQUEST['action']) ? FN::sanitise($_REQUEST['action']) : NULL;
			switch ($action)
			{
				case "addTemplate":
					DB::insert("template", array(
						"name"=>NULL,
						"note"=>NULL
						));
					break;
				case "save":
					$this->save();
					break;
				case "delete":
					$this->delete();
					unset($_REQUEST['id']);
					break;
				default:
					break;
			}
			$this->add($form = new FORM(NULL, NULL, "template"));
			$form->add(new H1(NULL, NULL, "Template Manager"));
			$form->add(new DIV(NULL, "navigator", new A(NULL, NULL,
				"Add a new template",
				"?admin&section=template&action=addTemplate")));
			$form->add(new DIV(NULL, "navigator", new A(NULL, NULL,
				"Back to main menu",  "?admin")));
			$form->add(new DIV());

			// Show all the templates.
			$form->add($items = new DIV("items"));
			$items ->add(new H2(NULL, NULL, "Template list"));
			$items->add($tr = new DIV());
			$tr->add($fields = new DIV(NULL, "fields"));
			$fields->add(new DIV("notelabel", NULL, "Note"));
			$fields->add(new DIV("namelabel", NULL, "Name"));
			$tr->add(new DIV(NULL, "clearboth"));

			$result = DB::select("template", "*", "ORDER BY name");
			$alternate = FALSE;
			while ($row = DB::fetchRow($result))
			{
				$id = $row->id;
				$name = $row->name;
				$note = $row->note;
				$items->add($tr = new DIV());
				$tr->add($buttons = new DIV(NULL, "buttons"));
				$buttons->add(new A(NULL, NULL,
					new IMG(NULL, NULL, "images/system/view.gif",
					"View the content of this template"), "#",
						array(
							"onclick"=>"viewTemplateContent($id);"
							)));
				$buttons->add(new A(NULL, NULL,
					new IMG(NULL, NULL, "images/system/save.gif",
						"Save this template"), "#",
						array(
							"onclick"=>"saveTemplate($id);"
							)));
				$buttons->add(new A(NULL, NULL,
					new IMG(NULL, NULL, "images/system/cut.gif",
						"Delete this template"), "#",
						array(
							"onclick"=>"deleteItem('template', $id);"
							)));
				$tr->add($fields = new DIV(NULL, "fields"));
				$fields->add(new TEXT("name$id", "name", "name", $name));
				$fields->add(new TEXT("note$id", "note", "note", $note));
				$tr->add(new DIV(NULL, "clearboth"));
				$alternate = !$alternate;
			}
			DB::freeResult($result);
		}

		/////////////////////////////////////////////////////////////////////////
		// Save.
		private function save()
		{
			$id = (int)$_REQUEST['id'];
			$name = FN::sanitise($_REQUEST['name']);
			$note = FN::sanitise($_REQUEST['note']);
			DB::update("template", array(
				"name"=>$name,
				"note"=>$note
				), "WHERE id=$id");
		}

		/////////////////////////////////////////////////////////////////////////
		// Delete.
		private function delete()
		{
			$id = (int)$_REQUEST['id'];
			DB::delete("template", "WHERE id=$id");
		}
	}

	////////////////////////////////////////////////////////////////////////////
	// The language manager.
	class LanguageManager extends DIV
	{
		function __construct()
		{
			parent::__construct("languageManager");

			$action = isset($_REQUEST['action']) ? FN::sanitise($_REQUEST['action']) : NULL;
			switch ($action)
			{
				case "addLanguage":
					DB::insert("language", array(
						"code"=>NULL,
						"note"=>NULL
						));
					break;
				case "save":
					$this->save();
					break;
				case "delete":
					$this->delete();
					unset($_REQUEST['id']);
					break;
				default:
					break;
			}
			$this->add($form = new FORM(NULL, NULL, "language"));
			$form->add(new H1(NULL, NULL, "Language Manager"));
			$form->add(new DIV(NULL, "navigator", new A(NULL, NULL,
				"Add a new language", array(
				"admin"=>1,
				"section"=>"language",
				"action"=>"addLanguage"))));
			$form->add(new DIV(NULL, "navigator", new A(NULL, NULL,
				"Back to main menu",  "?admin")));
			$form->add(new DIV());

			// Show all the languages.
			$form->add($items = new DIV("items"));
			$items ->add(new H2(NULL, NULL, "Languages"));
			$items->add($tr = new DIV());
			$tr->add($fields = new DIV(NULL, "fields"));
			$fields->add(new DIV("notelabel", NULL, "Note"));
			$fields->add(new DIV("namelabel", NULL, "Code"));
			$tr->add(new DIV(NULL, "clearboth"));

			$result = DB::select("language", "*", "ORDER BY code");
			$alternate = FALSE;
			while ($row = DB::fetchRow($result))
			{
				$id = $row->id;
				$code = $row->code;
				$note = $row->note;
				$items->add($tr = new DIV());
				$tr->add($buttons = new DIV(NULL, "buttons"));
				$buttons->add(new A(NULL, NULL,
					new IMG(NULL, NULL, "images/system/save.gif", "Save"), "#",
						array(
							"onclick"=>"saveLanguage($id);"
							)));
				$buttons->add(new A(NULL, NULL,
					new IMG(NULL, NULL, "images/system/cut.gif", "Delete"), "#",
						array(
							"onclick"=>"deleteItem('language', $id);"
							)));
				$tr->add($fields = new DIV(NULL, "fields"));
				$fields->add(new TEXT("name$id", "name", "code", $code));
				$fields->add(new TEXT("note$id", "note", "note", $note));
				$tr->add(new DIV(NULL, "clearboth"));
				$alternate = !$alternate;
			}
			DB::freeResult($result);
		}

		/////////////////////////////////////////////////////////////////////////
		// Save.
		private function save()
		{
			$id = (int)$_REQUEST['id'];
			$code = FN::sanitise($_REQUEST['code']);
			$note = FN::sanitise($_REQUEST['note']);
			DB::update("language", array(
				"code"=>$code,
				"note"=>$note
				), "WHERE id=$id");
		}

		/////////////////////////////////////////////////////////////////////////
		// Delete.
		private function delete()
		{
			$id = (int)$_REQUEST['id'];
			DB::delete("language", "WHERE id=$id");
		}
	}

	///////////////////////////////////////////////////////////////////////////
	// The menu manager.
	class MenuManager extends DIV
	{
		function __construct()
		{
			parent::__construct("menuManager");

			$action = isset($_REQUEST['action']) ? FN::sanitise($_REQUEST['action']) : NULL;
			switch ($action)
			{
				case "addMenu":
					DB::insert("menu", array(
						"name"=>NULL,
						"value"=>","
						));
					break;
				case "edit":
					$this->add($this->editMenu());
					return;
				case "Add a menu item":
					$this->addMenuItem();
					$this->add($this->editMenu());
					return;
				case "Save menu":
					$this->saveMenu();
					$this->add($this->editMenu());
					return;
				case "delete":
					$this->deleteMenu();
					break;
				default:
					break;
			}
			$this->add(new H1(NULL, NULL, "Menu Manager"));
			$this->add(new DIV(NULL, "navigator", new A(NULL, NULL,
				"Add a new menu",
				"?admin&section=menu&action=addMenu")));
			$this->add(new DIV(NULL, "navigator", new A(NULL, NULL,
				"Back to main menu",  "?admin")));
			$this->add(new DIV());

			// Show the menus.
			$this->add($items = new DIV("items"));
			$items ->add(new H2(NULL, NULL, "Menus"));
			$result = DB::select("menu", "*", "ORDER BY name");
			$alternate = FALSE;
			while ($row = DB::fetchRow($result))
			{
				$id = $row->id;
				$name = $row->name;
				$items->add($row = new DIV(NULL, "row".($alternate ? "1": "0")));
				$row->add(new DIV(NULL, "item", $name));
				$row->add($div = new DIV(NULL, "editbutton",
					new A(NULL, NULL,
						new IMG(NULL, NULL, "images/system/edit.gif", "Edit"), array(
						"admin"=>1,
						"section"=>"menu",
						"action"=>"edit",
						"id"=>$id
						))));
				$div->add(new A(NULL, NULL,
						new IMG(NULL, NULL, "images/system/cut.gif", "Delete"), "#",
							array(
								"onclick"=>"deleteItem('menu', $id);"
								)));
				$alternate = !$alternate;
			}
			DB::freeResult($result);
		}

		/////////////////////////////////////////////////////////////////////////
		// Edit a single menu.
		private function editMenu()
		{
			$id = (int)$_REQUEST['id'];
			$row = DB::selectRow("menu", "*", "WHERE id=$id");
			$name = stripslashes($row->name);
			$value = stripslashes($row->value);

			$form = new FORM("editMenu");
			$form->add(new H1(NULL, NULL, "Menu editor"));
			$form->add($table = new TABLE("menuItems"));

			$table->addRow($tr = new TR());
			$tr->addCell(new TD(NULL, "menuBold",
				new LABEL(NULL, NULL, "Menu name:")));
			$table->addRow($tr = new TR());
			$tr->addCell(new TD(NULL, "tatd",
				new TEXT(NULL, NULL, "name", $name)));

			$table->addRow($tr = new TR());
			$tr->addCell(new TD(NULL, "menuBold",
				new LABEL(NULL, NULL,
					"Items in this menu (template and name):")));

			// Put all the items in one cell.
			$table->addRow($tr = new TR());
			$tr->addCell($td = new TD());
			// Parse the value into a menu structure.
			$menuarray = explode("\n", $value);
			$index = 0;
			foreach ($menuarray as $menuitem)
			{
				$td->add($div0 = new DIV(NULL, "clearboth"));
				$div0->add($div1 = new DIV(NULL, "menuItemText"));
				$item = explode(",", $menuitem);
				$item0 = $item[0];
				$item1 = isset($item[1]) ? $item[1] : NULL;
				$div1->add($div = new DIV(NULL, "menuTemplate"));
				$div->add(new TEXT(NULL, NULL, "template$index", $item0));
				$div1->add($div = new DIV(NULL, "menuItem"));
				$div->add(new TEXT(NULL, NULL, "item$index", $item1));
				$div0->add($div = new DIV(NULL, "menuItemControls"));
				if ($index > 0)
					$div->add(new A(NULL, NULL,
						new IMG(NULL, NULL, "images/system/arrow-up.png", "Move up"),
							"#", array(
								"onclick"=>"moveUp($index);"
								)));
				else $div->add(new IMG(NULL, NULL, "images/system/blank16.png"));
				if ($index < count($menuarray) - 1)
					$div->add(new A(NULL, NULL,
						new IMG(NULL, NULL, "images/system/arrow-down.png", "Move down"),
							"#", array(
								"onclick"=>"moveDown($index);"
								)));
				else $div->add(new IMG(NULL, NULL, "images/system/blank16.png"));
				$div->add(new A(NULL, NULL,
					new IMG(NULL, NULL, "images/system/cut.gif", "Delete this item"),
						"#", array(
							"onclick"=>"deleteMenuItem($index);"
							)));
				$index++;
			}

			$table->addRow($tr = new TR());
			$tr->addCell($td = new TD());
			$td->add(new HIDDEN("admin", "1"));
			$td->add(new HIDDEN("section", "menu"));
			$td->add(new HIDDEN("id", $id));
			$td->add(new SUBMIT(NULL, NULL, "action", "Add a menu item"));
			$td->add(new SUBMIT(NULL, NULL, "action", "Save menu"));
			$td->add(new SUBMIT(NULL, NULL, "action", "OK"));

			return $form;
		}

		/////////////////////////////////////////////////////////////////////////
		// Add a new item to the selected menu.
		private function addMenuItem()
		{
			$this->saveMenu();
			$id = (int)$_REQUEST['id'];
			$value = DB::selectValue("menu", "value", "WHERE id=$id");
			$value .= "\n";
			DB::update("menu", array(
				"value"=>$value
				), "WHERE id=$id");
		}

		/////////////////////////////////////////////////////////////////////////
		// Save the menu.
		private function saveMenu()
		{
			$id = (int)$_REQUEST['id'];
			$name = FN::sanitise($_REQUEST['name']);
			$value = NULL;
			$index = 0;
			while (TRUE)
			{
				if (isset($_REQUEST["item$index"]))
				{
					if (isset($_REQUEST['toDelete']) && $_REQUEST['toDelete'] == $index)
					{
						$index++;
						continue;
					}
					$template = FN::sanitise($_REQUEST["template$index"]);
					$item = FN::sanitise($_REQUEST["item$index"]);
					if ($value) $value .= "\n";
					$value .= "$template,$item";
					$index++;
				}
				else break;
			}
			DB::update("menu", array(
				"name"=>$name,
				"value"=>$value,
				"user"=>0,
				"timestamp"=>time(),
				"attributes"=>NULL
				), "WHERE id=$id");
		}

		/////////////////////////////////////////////////////////////////////////
		// Delete a menu.
		private function deleteMenu()
		{
			$id = (int)$_REQUEST['id'];
			DB::delete("menu", "WHERE id=$id");
		}
	}

	////////////////////////////////////////////////////////////////////////////
	// The image manager.
	class ImageManager extends DIV
	{
		function __construct()
		{
			parent::__construct("imageManager");

			$action = isset($_REQUEST['action']) ? FN::sanitise($_REQUEST['action']) : NULL;
			switch ($action)
			{
				case "addImage":
					$this->add($this->addImage());
					return;
				case "show":
					$this->add($this->showImage());
					return;
				case "Upload image":
					$this->uploadImage();
					$this->add($this->showImage());
					return;
				case "Delete image":
					$this->deleteImage();
					break;
				case "open":
					$this->openDirectory();
					break;
				default:
					break;
			}
			$this->add(new H1(NULL, NULL, "Image Manager"));
			$this->add(new DIV(NULL, "navigator", new A(NULL, NULL,
				"Add a new image",
				"?admin&section=image&action=addImage")));
			$this->add(new DIV(NULL, "navigator", new A(NULL, NULL,
				"Back to main menu",
				"?admin")));
			$this->add(new DIV());

			$root = "upload";
			$directory = FN::getSessionValue("img-dir");
			if ($directory == NULL || !file_exists($directory))
			{
				$directory = $root;
				if (!file_exists($directory)) mkdir($directory);
				FN::setSessionValue("img-dir", $directory);
			}
			
			// Show the image names.
			$dirs = array();
			$files = array();
			$dh = opendir($directory);
			if ($dh)
			{
			   while ($name = readdir($dh))
			   {
			      if ($name != '.')
			      {
			      	if (is_dir("$directory/$name")) $dirs[] = $name;
			      	else $files[] = $name;
			      }
			   }
			   // close the directory
			   closedir($dh);
			}
			
			$this->add(
				$items = new DIV("items", NULL, "Current directory: $directory"));
			$alternate = FALSE;
			sort($dirs);
			sort($files);
			foreach ($dirs as $name)
			{
				$items->add($row = new DIV(NULL, "row" . ($alternate ? 0 : 1)));
				$row->add(new IMG(NULL, NULL, "images/system/folder.png",
					NULL, array("align"=>"left")));
				$row->add(new A(NULL, NULL, $name,
						array(
							"admin"=>1,
							"section"=>"image",
							"action"=>"open",
							"directory"=>$directory,
							"name"=>$name
							)));
				$alternate = !$alternate;
			}
			foreach ($files as $name)
			{
				$items->add($row = new DIV(NULL, "row" . ($alternate ? 0 : 1)));
				$row->add(new IMG(NULL, NULL, "images/system/graphics.png",
					NULL, array("align"=>"left")));
				$row->add(new A(NULL, NULL, $name,
						array(
							"admin"=>1,
							"section"=>"image",
							"action"=>"show",
							"directory"=>$directory,
							"name"=>$name
							)));
				$alternate = !$alternate;
			}
		}

		/////////////////////////////////////////////////////////////////////////
		// Show an image.
		private function showImage()
		{
			$directory = FN::getSessionValue("img-dir");
			$name = isset($_REQUEST['name']) ? FN::sanitise($_REQUEST['name']) : NULL;
			
			$form = new FORM("editImage", NULL, "editImage", "index.php", TRUE);
			$form->add(new H1(NULL, NULL, "Image viewer"));

			$form->add(new DIV(NULL, NULL, "$directory/$name"));
			$form->add(new DIV(NULL, NULL, new IMG(NULL, NULL, "$directory/$name")));

			$form->add($div = new DIV());
			$div->add(new HIDDEN("admin", "1"));
			$div->add(new HIDDEN("section", "image"));
			$div->add(new HIDDEN("directory", $directory));
			$div->add(new HIDDEN("name", $name));
			$div->add(new SUBMIT(NULL, NULL, "action", "Delete image"));
			$div->add(new SUBMIT(NULL, NULL, "action", "OK"));

			return $form;
		}

		/////////////////////////////////////////////////////////////////////////
		// Add an image.
		private function addImage()
		{
			$directory = FN::getSessionValue("img-dir");
			$form = new FORM("editImage", NULL, "editImage", "index.php", TRUE);
			$form->add(new H1(NULL, NULL, "Image uploader"));

			$form->add($table = new TABLE());

			$table->addRow($tr = new TR());
			$tr->addCell(new FILE("image", NULL, "name"));

			$table->addRow($tr = new TR());
			$tr->addCell($td = new TD());
			$td->add(new HIDDEN("admin", "1"));
			$td->add(new HIDDEN("section", "image"));
			$td->add(new HIDDEN("directory", $directory));
			$td->add(new SUBMIT(NULL, NULL, "action", "Upload image"));
			$td->add(new SUBMIT(NULL, NULL, "action", "Cancel"));

			return $form;
		}

		/////////////////////////////////////////////////////////////////////////
		// Upload the image.
		private function uploadImage()
		{
			// Get the uploaded image, if any.
			if (is_uploaded_file($_FILES['name']['tmp_name']))
			{
				$directory = FN::getSessionValue("img-dir");
				$name = $_FILES['name']['name'];
				$fileName = "$directory/$name";
				move_uploaded_file($_FILES['name']['tmp_name'], $fileName);
				chmod($fileName, 0666);
				$_REQUEST['name'] = $name;
			}
		}

		/////////////////////////////////////////////////////////////////////////
		// Delete an image.
		private function deleteImage()
		{
			$directory = FN::getSessionValue("img-dir");
			$name = FN::sanitise($_REQUEST['name']);
			unlink("$directory/$name");
		}

		/////////////////////////////////////////////////////////////////////////
		// Open a directory.
		private function openDirectory()
		{
			$directory = FN::getSessionValue("img-dir");
			$name = FN::sanitise($_REQUEST['name']);
			if ($name == "..")
			{
				$pos = strrpos($directory, "/");
				if (!$pos) return;
				$directory = substr($directory, 0, $pos);
				FN::setSessionValue("img-dir", $directory);
			}
			else FN::setSessionValue("img-dir", "$directory/$name");
		}
	}

	////////////////////////////////////////////////////////////////////////////
	// The php manager.
	class PHPManager extends DIV
	{
		function __construct($head)
		{
			parent::__construct("phpManager");

			$action = isset($_REQUEST['action']) ? FN::sanitise($_REQUEST['action']) : NULL;
			switch ($action)
			{
				case "editPHP":
					$this->add($this->editPHP($head));
					return;
				case "Save PHP":
					$this->savePHP();
					$this->add($this->editPHP($head));
					return;
				case "deletePHP":
					$this->deletePHP();
					break;
				default:
					break;
			}
			$this->add($form = new FORM(NULL, NULL, "template"));
			$form->add(new H1(NULL, NULL, "PHP Manager"));
			$form->add(new DIV(NULL, "navigator", new A(NULL, NULL,
				"Back to main menu",
				"?admin")));

			// Show all the PHP files.
			$form->add($items = new DIV("items"));
			$items ->add(new H2(NULL, NULL, "PHP file list"));

			$items->add($php = new TABLE("phpFiles"));

			// Read all the files.
			$files = array();
			$dh = opendir("../php/template");
			if ($dh)
			{
			   while ($name = readdir($dh))
			   {
			      if ($name != '.' && $name != '..' && !is_dir("../php/$name"))
			      	$files[] = $name;
			   }
			   // close the directory
			   closedir($dh);
			}
			
			$alternate = FALSE;
			sort($files);
			foreach ($files as $name)
			{
				$php->addRow($tr = new TR(NULL, "row" . ($alternate ? 0 : 1)));
				$tr->addCell(new TD(NULL, NULL, new A(NULL, NULL, $name,
						array(
							"admin"=>1,
							"section"=>"php",
							"action"=>"editPHP",
							"name"=>$name
							))));
				$alternate = !$alternate;
			}
		}

		/////////////////////////////////////////////////////////////////////////
		// Edit a PHP file.
		private function editPHP($head)
		{
			$head->addScript("edit_area/edit_area_full.js");
			$head->addScript("scripts/editphp.js");
			
			$name = FN::sanitise($_REQUEST['name']);
			$content = FN::getFile("../php/template/$name");

			$form = new FORM(NULL, NULL, "editPHP");
			$form->add(new H1(NULL, NULL, "PHP editor"));

			$form->add(new DIV(NULL, "navigator", new A(NULL, NULL,
				"Back to PHP files", array(
				"admin"=>1,
				"section"=>"php",
				"action"=>"list"
				))));
			$form->add(new DIV(NULL, "navigator", new A(NULL, NULL,
				"Back to main menu",
				"?admin")));

			$form->add(new TEXTAREA("phpedit", NULL, "content", $content));
				
			$form->add($submit = new DIV());
			$submit->add(new HIDDEN("admin", "1"));
			$submit->add(new HIDDEN("section", "php"));
			$submit->add(new HIDDEN("name", $name));
			$submit->add(new SUBMIT(NULL, NULL, "action", "Save PHP"));
			return $form;
		}

		/////////////////////////////////////////////////////////////////////////
		// Save a PHP file.
		private function savePHP()
		{
			$name = FN::sanitise($_REQUEST['name']);
			$content = stripslashes($_REQUEST['content']);
			$fh = fopen("../php/template/$name", "w+");
			fwrite($fh, $content);
			fclose($fh);
		}
	}

	////////////////////////////////////////////////////////////////////////////
	// The style manager.
	class StyleManager extends DIV
	{
		function __construct($head)
		{
			parent::__construct("styleManager");

			$action = isset($_REQUEST['action']) ? FN::sanitise($_REQUEST['action']) : NULL;
			switch ($action)
			{
				case "editStylesheet":
					$this->add($this->editStylesheet($head));
					return;
				case "Save stylesheet":
					$this->saveStylesheet();
					$this->add($this->editStylesheet($head));
					return;
				default:
					break;
			}
			$this->add($form = new FORM(NULL, NULL, "template"));
			$form->add(new H1(NULL, NULL, "Style Manager"));
			$form->add(new DIV(NULL, "navigator", new A(NULL, NULL,
				"Back to main menu",
				"?admin")));

			// Show all the stylesheets.
			$form->add($items = new DIV("items"));
			$items ->add(new H2(NULL, NULL, "Stylesheet list"));

			$items->add($styles = new TABLE("stylesheets"));

			// Read all the files.
			$files = array();
			$dh = opendir("css");
			if ($dh)
			{
			   while ($name = readdir($dh))
			   {
			      if ($name != '.' && $name != '..' && !is_dir("css/$name"))
			      	$files[] = $name;
			   }
			   closedir($dh);
			}
			
			$alternate = FALSE;
			sort($files);
			foreach($files as $name)
			{
				$styles->addRow($tr = new TR(NULL, "row" . ($alternate ? 0 : 1)));
				$tr->addCell(new A(NULL, NULL, $name,
						array(
							"admin"=>1,
							"section"=>"style",
							"action"=>"editStylesheet",
							"name"=>$name
							)));
				$alternate = !$alternate;
			}
		}

		/////////////////////////////////////////////////////////////////////////
		// Edit a stylesheet.
		private function editStylesheet($head)
		{
			$head->addScript("edit_area/edit_area_full.js");
			$head->addScript("scripts/editcss.js");
			
			$name = FN::sanitise($_REQUEST['name']);
			$content = FN::getFile("css/$name");
			
			$form = new FORM(NULL, NULL, "editStylesheet");
			$form->add(new H1(NULL, NULL, "Stylesheet editor"));

			$form->add(new DIV(NULL, "navigator", new A(NULL, NULL,
				"Back to stylesheets", array(
				"admin"=>1,
				"section"=>"style",
				"action"=>"list"
				))));
			$form->add(new DIV(NULL, "navigator", new A(NULL, NULL,
				"Back to main menu",
				"?admin")));
			
			$form->add(new DIV(NULL, NULL, "css/$name"));

			$form->add($table = new TABLE("edit"));

			$form->add(new TEXTAREA("cssedit", NULL, "content", $content));
				
			$form->add($submit = new DIV());
			$submit->add(new HIDDEN("admin", "1"));
			$submit->add(new HIDDEN("section", "style"));
			$submit->add(new HIDDEN("name", $name));
			$submit->add(new SUBMIT(NULL, NULL, "action", "Save stylesheet"));
			return $form;
		}

		/////////////////////////////////////////////////////////////////////////
		// Save a stylesheet.
		private function saveStylesheet()
		{
			$name = FN::sanitise($_REQUEST['name']);
			$fh = fopen("css/$name", "w+");
			fwrite($fh, $_REQUEST['content']);
			fclose($fh);
		}
	}

	////////////////////////////////////////////////////////////////////////////
	// This is the database manager.
	class dbEditor extends Base
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
				"content",
				"template",
				"language",
				"menu"
				);
		}

		/////////////////////////////////////////////////////////////////////////
		// Get a field list.
		protected function getFieldList($name)
		{
			switch ($name)
			{
				case "content":
					return array(
						"name"=>"TEXT",
						"template"=>"INT",
						"language"=>"INT",
						"value"=>"TEXT",
						"note"=>"TEXT",
						"user"=>"INT",
						"timestamp"=>"INT",
						"attributes"=>"TEXT"
						);
				case "template":
					return array(
						"name"=>"TEXT",
						"note"=>"TEXT"
						);
				case "language":
					return array(
						"code"=>"CHAR(2)",
						"note"=>"TEXT"
						);
				case "text":
					return array(
						"content"=>"INT",
						"note"=>"TEXT",
						"language"=>"INT",
						"value"=>"TEXT",
						"user"=>"INT",
						"timestamp"=>"INT",
						"attributes"=>"TEXT"
						);
				case "menu":
					return array(
						"name"=>"TEXT",
						"language"=>"CHAR(2)",
						"value"=>"TEXT",
						"user"=>"INT",
						"timestamp"=>"INT",
						"attributes"=>"TEXT"
						);
			}
		}
	}
?>
