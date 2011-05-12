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
		function __construct($head)
		{
			$head->addCSS("css/admin.css");
			$head->addScript("scripts/admin.js");

			// Create the tables if needed.
			new dbEditor();

			$message = NULL;
			$session = FN::getProperty("session");
			if (isset($_REQUEST['section']))
			{
				$section = FN::sanitise($_REQUEST['section']);
				switch ($section)
				{
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
							"content", "template"
							)))
						{
							parent::__construct("editor");
							$manager = $section . "Manager";
							$this->add(new $manager());
							return;
						}
						if (FN::fileExists($section))
						{
							parent::__construct("editor");
							require_once "$section.php";
							$manager = $section . "Manager";
							$this->add(new $manager($head));
							return;
						}
						$message = "Unknown manager '$section'";
						break;
				}
			}
			parent::__construct("editor", NULL,
				new H1(NULL, NULL, "Main admin menu"));
			// Do the main menu. First the main items.
			foreach (array("Content", "Template") as $title)
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
			// Finally the special items.
			$this->add(new DIV(NULL, "menuItem",
				new A(NULL, NULL, "Export the tables",
					array(
						"admin"=>1,
						"section"=>"export"
						))));
			$this->add(new DIV(NULL, "menuItem",
				new A(NULL, NULL, "Show daily hits",
					array(
						"admin"=>1,
						"section"=>"hits"
						))));
			$this->add(new DIV(NULL, "menuItem",
				new A(NULL, NULL, "Return to the home page", "index.php")));
			$this->add(new DIV(NULL, "clearboth"));
			if ($message) $this->add(new DIV("message", NULL, $message));
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
				"Back to main menu", "?admin")));
			$this->add(new DIV(NULL, "navigator", new A(NULL, NULL,
				"Add new content", array(
				"admin"=>1,
				"section"=>"content",
				"action"=>"addContent"
				))));

			$this->add($dropdowns = new DIV("dropdowns"));
			$dropdowns->add($form = new FORM(NULL, NULL, "template"));
			//$dropdowns->add(new DIV(NULL, "clear"));

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

			// Show the pages for the given template.
			$name = $template ? DB::selectValue("template", "name",
				"WHERE id=$template") : NULL;
			$this->add(new H2(NULL, NULL, "Content for $name"));
			$this->add($items = new TABLE("items"));
			$where = "WHERE (template='$template' OR template=0)";
			$where .= " ORDER BY name";
			$result = DB::select("content", "*", $where);
			$alternate = 0;
			while ($row = DB::fetchRow($result))
			{
				$id = $row->id;
				$name = $row->name;
				$note = stripslashes($row->note);
				
				$items->addRow($tr = new TR(NULL, "row$alternate"));
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
			$this->add(new SCRIPT("http://js.nicedit.com/nicEdit-latest.js"));

			$id = (int)$_REQUEST['id'];
			$row = DB::selectRow("content", "*", "WHERE id=$id");
			$name = $row->name;
			$language = $row->language;
			$note = htmlentities(stripslashes($row->note));
			$value = stripslashes($row->value);
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

			$form = new FORM("editContent");
			$form->add(new H1(NULL, NULL, "Text editor"));
			$form->add($table = new TABLE());

			$table->addRow($tr = new TR());
			$tr->addCell(new TD(NULL, "label",
				new LABEL(NULL, NULL, "Template:", "template")));
			$tr->addCell($td = new TD(NULL, "tatd", $tselect));

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
			$tr->addCell(new TD(NULL, "tatd",
				new TEXTAREA(NULL, NULL, "value", $value)));
			
			$table->addRow($tr = new TR());
			$tr->addCell();
			$tr->addCell($td = new TD());
			$td->add(new HIDDEN("admin", 1));
			$td->add(new HIDDEN("text", 1));
			$td->add(new HIDDEN("section", "content"));
			$td->add(new HIDDEN("id", $id));
			$td->add(new SUBMIT(NULL, NULL, "action", "Save text"));
			$td->add(new SUBMIT(NULL, NULL, "action", "OK"));

			$this->add(new SCRIPT(NULL, "bkLib.onDomLoaded(nicEditors.allTextAreas);"));
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
			$value = $_REQUEST['value'];
			DB::update("content", array(
				"template"=>$template,
				"name"=>$name,
				"note"=>$note,
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
				"Back to main menu",  "?admin")));
			$form->add(new DIV(NULL, "navigator", new A(NULL, NULL,
				"Add a new template",
				"?admin&section=template&action=addTemplate")));

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
				"template"
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
			}
		}
	}
?>
