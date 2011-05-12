<?php //
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

	/////////////////////////////////////////////////////////////////////////////
	// Object-oriented container hierarchy for HTML.
	//
	// This set of classes allows a page to be built up as a series of objects,
	// each contained within parent objects. The hierarchy can be simply set up
	// by drawing a plan of the page where each element is a labelled box.
	/////////////////////////////////////////////////////////////////////////////

	/////////////////////////////////////////////////////////////////////////////
	// This is the document HEAD.
	class HEAD
	{
		private $title;
		private $icon = "favicon.ico";
		private $type = "image/gif";
		private $css;
		private $scripts;
		private $onLoad;
		private $onUnload;

		//////////////////////////////////////////////////////////////////////////
		// Constructor.
		function __construct($title = NULL)
		{
			$this->title = $title;
			$icon = NULL;
			$css = array();
			$scripts = array();
		}

		//////////////////////////////////////////////////////////////////////////
		// Set the title.
		public function setTitle($title)
		{
			$this->title = $title;
		}

		//////////////////////////////////////////////////////////////////////////
		// Add a CSS script.
		public function addCSS($path)
		{
			$this->css[] = $path;
		}

		//////////////////////////////////////////////////////////////////////////
		// Add javascript.
		public function addScript($path)
		{
			$this->scripts[] = $path;
		}

		//////////////////////////////////////////////////////////////////////////
		// Set the icon.
		public function setIcon($icon, $type = "image/gif")
		{
			$this->icon = $icon;
			$this->type = $type;
		}

		//////////////////////////////////////////////////////////////////////////
		// Get the HTML.
		public function getHTML()
		{
			$content = "<head>\n"
				."   <meta http-equiv=\"Content-Style-Type\" content=\"text/css\"/>"
				."   <meta http-equiv=\"content-type\""
				." content=\"text/html; charset=utf-8\" />\n"
				."   <title>".$this->title."</title>\n";
			$icon = $this->icon;
			$type = $this->type;
			$content .= "   <link rel=\"shortcut icon\" type=\"$type\" href=\"$icon\" />\n";
			if (count($this->css))
			{
				foreach ($this->css as $css)
					$content .= "   <link rel=\"stylesheet\" type=\"text/css\" href=\"$css\" />\n";
			}
			if (count($this->scripts))
			{
				foreach ($this->scripts as $script)
					$content .= "   <script type=\"text/javascript\" src=\"".$script
						."\"></script>\n";
			}
			$content .= "</head>";
			return $content;
		}

		//////////////////////////////////////////////////////////////////////////
		// Set the onload function.
		public function setOnLoad($onLoad)
		{
			$this->onLoad = $onLoad;
		}

		//////////////////////////////////////////////////////////////////////////
		// Set the onunload function.
		public function setOnUnload($onUnload)
		{
			$this->onUnload = $onUnload;
		}

		//////////////////////////////////////////////////////////////////////////
		// Get the onload function.
		public function getOnLoad()
		{
			return $this->onLoad;
		}

		//////////////////////////////////////////////////////////////////////////
		// Get the onunload function.
		public function getOnUnload()
		{
			return $this->onUnload;
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// This is the document BODY.
	class BODY
	{
		private $body;
		private $onLoad;
		private $onUnload = NULL;

		//////////////////////////////////////////////////////////////////////////
		// Constructor.
		function __construct($body, $onLoad = NULL)
		{
			$this->body = $body;
			$this->onLoad = $onLoad;
		}

		//////////////////////////////////////////////////////////////////////////
		// Set the onload function.
		public function setOnLoad($onLoad, $fromHead = FALSE)
		{
			if ($fromHead && $this->onLoad) return;
			$this->onLoad = $onLoad;
		}

		//////////////////////////////////////////////////////////////////////////
		// Set the onunload function.
		public function setOnUnload($onUnload, $fromHead = FALSE)
		{
			if ($fromHead && $this->onUnload) return;
			$this->onUnload = $onUnload;
		}

		//////////////////////////////////////////////////////////////////////////
		// Get the HTML.
		public function getHTML()
		{
			$content = "<body";
			if ($this->onLoad) $content .= " onload=\"".$this->onLoad."\"";
			if ($this->onUnload) $content .= " onunload=\"".$this->onUnload."\"";
			$content .= ">\n";
			$content .= $this->body->getHTML(1);
			$content .= "\n</body>";
			return $content;
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Here's the base class for all the HTML tags.
	abstract class TAG
	{
		private $tagname = NULL;
		private $id;
		private $class;
		private $singleton;
		private $compress;

		protected $attrs;

		//////////////////////////////////////////////////////////////////////////
		// Constructor.
		function __construct($tagname = NULL, $id = NULL, $class = NULL,
			$attrs = NULL, $singleton = TRUE)
		{
			$this->tagname = $tagname;
			$this->id = $id;
			$this->class = $class;
			$this->attrs = $attrs;
			$this->singleton = $singleton;
		}

		//////////////////////////////////////////////////////////////////////////
		// Get the name of this module.
		// This function is for use by TCMS template classes.
		// It's used mainly by the breadcrumbs system.
		public function getName()
		{
			return NULL;
		}

		//////////////////////////////////////////////////////////////////////////
		// Get the parent.
		// This function is for use by TCMS template classes.
		// It's used mainly by the breadcrumbs system.
		public function getParent()
		{
			return NULL;
		}

		//////////////////////////////////////////////////////////////////////////
		// Get the URL.
		// This function is for use by TCMS template classes.
		// It's used mainly by the breadcrumbs system.
		public function getURL()
		{
			return NULL;
		}

		//////////////////////////////////////////////////////////////////////////
		// Set the compress flag to force the closing tag onto the same line.
		public function compressClosingTag()
		{
			$this->compress = TRUE;
		}

		//////////////////////////////////////////////////////////////////////////
		// Get the HTML for this tag and all its content.
		// The function automatically pretty-prints the HTML it outputs.
		public function getHTML($indent = 0)
		{
			// Count the indent spaces required.
			$spaces = str_repeat("   ", $indent);
			// Get the tag header.
			$html = $spaces."<".$this->tagname;
			$id = $this->id;
			if ($id)
			{
				// If the ID starts with a # it's an embedded style.
				if (strpos($id, "#") === 0)
				{
					$style = substr($id, 1);
					$html .= " style=\"$style\"";
				}
				else $html .= " id=\"$id\"";
			}
			if ($this->class) $html .= " class=\"".$this->class."\"";
			// Get the attributes of this tag (if any).
			if ($this->attrs)
			{
				if (is_array($this->attrs))
					foreach ($this->attrs as $name=>$value)
						$html .= " ".$name."=\"".$value."\"";
 				else $html .= " ".$this->attrs;
			}
			// Get the specific content of this tag (if any).
			$content = $this->getContent($indent);
			if ($content)
			{
				$html .= ">".$content;
				// Get the tag footer.
				if (!$this->compress) $html .= "\n$spaces";
				$html .= "</".$this->tagname.">";
			}
			else
			{
				if (DOCUMENT::XHTML)
				{
					if ($this->singleton) $html .= " />";
					else $html .= "></".$this->tagname.">";
				}
				else $html .= ">";
			}
			return $html;
		}

		//////////////////////////////////////////////////////////////////////////
		// Get the content.
		protected function getContent($indent) { return NULL; }
	}

	/////////////////////////////////////////////////////////////////////////////
	// This class represents a piece of plain text.
	class STRING extends TAG
	{
		private $content;

		//////////////////////////////////////////////////////////////////////////
		function __construct($content = NULL)
		{
			parent::__construct();
			$this->content = $content;
		}

		//////////////////////////////////////////////////////////////////////////
		// Get the HTML for this tag.
		// This one is special as it just returns the plain text without any tag.
		public function getHTML($indent = 0)
		{
			return str_repeat("   ", $indent).$this->content;
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// This class is the base of any simple container such as a DIV or a SPAN.
	abstract class CONTAINER extends TAG
	{
		const DEBUG = FALSE;

		private $tags;

		//////////////////////////////////////////////////////////////////////////
		function __construct($name, $id, $class, $content = NULL, $attrs = NULL,
			$singleton = TRUE)
		{
			if (self::DEBUG) echo "new ".get_class($this)."<br>";
			parent::__construct($name, $id, $class, $attrs, $singleton);
			$this->tags = array();
			if ($content) $this->add($content);
		}

		//////////////////////////////////////////////////////////////////////////
		// Add some content to this object.
		// The function tests the type of the data passed to it
		public function add($content)
		{
			if (is_object($content))
			{
				if (self::DEBUG) echo "Adding ".get_class($content)."<br>";
				if ($content instanceof TAG) $this->tags[] = $content;
				else die("Can't add a ".get_class($content)." to a CONTAINER");
			}
			else
			{
				if (self::DEBUG) echo "Adding ".$content."<br>";
				$this->tags[] = new STRING($content);
			}
		}

		//////////////////////////////////////////////////////////////////////////
		// Add a DIV to the array held for this object.
		public function addDIV($id, $class, $content)
		{
			$this->tags[] = new DIV($id, $class, $content);
		}

		//////////////////////////////////////////////////////////////////////////
		// Get the content of this object.
		protected function getContent($indent)
		{
			$html = NULL;
			foreach ($this->tags as $item)
			{
				$tag = $item->getHTML($indent + 1);
				$html .= "\n".$tag;
			}
			if (!$html) $html = "&nbsp;";
			return $html;
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create a block component that does not generate any HTML.
	class BLOCK extends CONTAINER
	{
		private $name;

		//////////////////////////////////////////////////////////////////////////
		function __construct($name = NULL)
		{
			parent::__construct("block", NULL, NULL);
			$this->name = $name;
		}

		//////////////////////////////////////////////////////////////////////////
		// Get the HTML for this tag.
		// In this special case it's just the content.
		public function getHTML($indent = 0)
		{
			$name1 = $this->name;
			if ($name1)
			{
				$name2 = "/$name1";
			}
			else
			{
				$name1 = "<div>";
				$name2 = "</div>";
			}
			$spaces = str_repeat("   ", $indent);
			$content = "$spaces<!-- $name1 -->";
			$content .= $this->getContent($indent);
			$content .= "\n$spaces<!-- $name2 -->";
			return $content;
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create an H1 header.
	class H1 extends CONTAINER
	{
		//////////////////////////////////////////////////////////////////////////
		function __construct($id = NULL, $class = NULL, $content = NULL, $attrs = NULL)
		{
			parent::__construct("h1", $id, $class, $content, $attrs);
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create an H2 header.
	class H2 extends CONTAINER
	{
		//////////////////////////////////////////////////////////////////////////
		function __construct($id = NULL, $class = NULL, $content = NULL, $attrs = NULL)
		{
			parent::__construct("h2", $id, $class, $content, $attrs);
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create an H3 header.
	class H3 extends CONTAINER
	{
		//////////////////////////////////////////////////////////////////////////
		function __construct($id = NULL, $class = NULL, $content = NULL, $attrs = NULL)
		{
			parent::__construct("h3", $id, $class, $content, $attrs);
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create an H4 header.
	class H4 extends CONTAINER
	{
		//////////////////////////////////////////////////////////////////////////
		function __construct($id = NULL, $class = NULL, $content = NULL, $attrs = NULL)
		{
			parent::__construct("h4", $id, $class, $content, $attrs);
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create an H5 header.
	class H5 extends CONTAINER
	{
		//////////////////////////////////////////////////////////////////////////
		function __construct($id = NULL, $class = NULL, $content = NULL, $attrs = NULL)
		{
			parent::__construct("h5", $id, $class, $content, $attrs);
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create an H6 header.
	class H6 extends CONTAINER
	{
		//////////////////////////////////////////////////////////////////////////
		function __construct($id = NULL, $class = NULL, $content = NULL, $attrs = NULL)
		{
			parent::__construct("h6", $id, $class, $content, $attrs);
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create a line break.
	class BR extends TAG
	{
		//////////////////////////////////////////////////////////////////////////
		function __construct($id = NULL, $class = NULL)
		{
			parent::__construct("br", $id, $class, NULL, TRUE);
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create a paragraph.
	class P extends CONTAINER
	{
		//////////////////////////////////////////////////////////////////////////
		function __construct($id = NULL, $class = NULL, $content = NULL, $attrs = NULL)
		{
			parent::__construct("p", $id, $class, $content, $attrs);
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create a bold section.
	class B extends CONTAINER
	{
		//////////////////////////////////////////////////////////////////////////
		function __construct($content)
		{
			parent::__construct("b", NULL, NULL, $content);
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create a DIV.
	class DIV extends CONTAINER
	{
		//////////////////////////////////////////////////////////////////////////
		function __construct($id = NULL, $class = NULL, $content = NULL, $attrs = NULL)
		{
			parent::__construct("div", $id, $class, $content, $attrs);
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create a SPAN.
	class SPAN extends CONTAINER
	{
		//////////////////////////////////////////////////////////////////////////
		function __construct($id = NULL, $class = NULL, $content = NULL, $attrs = NULL)
		{
			parent::__construct("span", $id, $class, $content, $attrs);
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create a hyperlink.
	class A extends CONTAINER
	{
		//////////////////////////////////////////////////////////////////////////
		// Constructor.
		// $id the CSS ID or NULL.
		// $class the CSS class or NULL.
		// $content the text or object of the link.
		// $url a URL or an array of component parts.
		// $attrs an array of attributes, or NULL.
		function __construct($id, $class, $content = NULL, $url = "#", $attrs = NULL)
		{
			if (!$attrs) $attrs = array();
			if (is_array($url))
			{
				$u = NULL;
				foreach ($url as $name=>$value)
				{
					$u .= $u ? "&" : "?";
					$u .= $name."=".$value;
				}
				$url = $u;
				$attrs['href'] = $url;
			}
			else $attrs['href'] = $url;
			parent::__construct("a", $id, $class,
				is_object($content) ? NULL : $content, $attrs);
			if (is_object($content)) $this->add($content);
			$this->compressClosingTag();
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create an unordered list.
	class UL extends CONTAINER
	{
		//////////////////////////////////////////////////////////////////////////
		function __construct($id = NULL, $class = NULL)
		{
			parent::__construct("ul", $id, $class);
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create a list item.
	class LI extends CONTAINER
	{
		//////////////////////////////////////////////////////////////////////////
		function __construct($id = NULL, $class = NULL, $content = NULL)
		{
			parent::__construct("li", $id, $class, $content);
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create an image.
	class IMG extends TAG
	{
		//////////////////////////////////////////////////////////////////////////
		function __construct($id, $class, $src, $title = NULL, $attrs = NULL, $alt = NULL)
		{
			if (!$attrs) $attrs = array();
			$attrs['src'] = $src;
			$attrs['alt'] = $alt;
			$attrs['border'] = 0;
			if ($title) $attrs['title'] = $title;
			parent::__construct("img", $id, $class, $attrs, FALSE);
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create a horizontal rule.
	class HR extends TAG
	{
		//////////////////////////////////////////////////////////////////////////
		function __construct($id = NULL, $class = NULL, $width = "100%", $height = 1)
		{
			parent::__construct("hr", $id, $class, array(
				"width"=>$width,
				"height"=>$height
				));
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create a table.
	class TABLE extends CONTAINER
	{
		//////////////////////////////////////////////////////////////////////////
		function __construct($id = NULL, $class = NULL, $attrs = NULL)
		{
			parent::__construct("table", $id, $class, NULL, $attrs);
		}

		function addRow($row) { $this->add($row); }
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create a table row.
	class TR extends CONTAINER
	{
		//////////////////////////////////////////////////////////////////////////
		function __construct($id = NULL, $class = NULL)
		{
			parent::__construct("tr", $id, $class);
		}

		function addCell($data = "&nbsp;", $align = NULL)
		{
			if (!is_object($data) || !($data instanceof TABLECELL))
				$data = new TD(NULL, $align, $data);
			$this->add($data);
		}

		function addHeaderCell($data = "&nbsp;", $align = NULL)
		{
			if (!is_object($data) || !($data instanceof TABLECELL))
				$data = new TH(NULL, $align, $data);
			$this->add($data);
		}

		function addEmptyCells($count)
		{
			for ($n = 0; $n < $count; $n++) $this->add(new TD());
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create a table cell.
	abstract class TABLECELL extends CONTAINER
	{
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create a table header cell.
	class TH extends TABLECELL
	{
		//////////////////////////////////////////////////////////////////////////
		function __construct($id = NULL, $class = NULL, $content = NULL)
		{
			parent::__construct("th", $id, $class, $content);
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create a table cell.
	class TD extends TABLECELL
	{
		//////////////////////////////////////////////////////////////////////////
		function __construct($id = NULL, $class = NULL, $content = NULL, $attrs = NULL)
		{
			parent::__construct("td", $id, $class, $content, $attrs);
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create a form.
	class FORM extends CONTAINER
	{
		//////////////////////////////////////////////////////////////////////////
		function __construct($id = NULL, $class = NULL, $name = NULL,
			$action = "index.php", $upload = FALSE, $newWindow = FALSE)
		{
			$attrs = array(
				"method"=>"post"
				);
			if ($upload) $attrs['enctype'] = "multipart/form-data";
			if ($action) $attrs['action'] = $action;
			if ($newWindow) $attrs['target'] = "_blank";
			if ($name) $attrs['name'] = $name;
			parent::__construct("form", $id, $class, NULL, $attrs);
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create a FIELDSET.
	class FIELDSET extends CONTAINER
	{
		//////////////////////////////////////////////////////////////////////////
		function __construct($id = NULL, $class = NULL)
		{
			parent::__construct("fieldset", $id, $class);
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create a LEGEND.
	class LEGEND extends CONTAINER
	{
		//////////////////////////////////////////////////////////////////////////
		function __construct($id, $class, $content, $attrs = NULL)
		{
			parent::__construct("legend", $id, $class, $content, $attrs);
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create a LABEL.
	class LABEL extends CONTAINER
	{
		//////////////////////////////////////////////////////////////////////////
		function __construct($id, $class, $content, $for = NULL, $attrs = NULL)
		{
			if (!$attrs) $attrs = array();
			if ($for) $attrs["for"] = $for;
			parent::__construct("label", $id, $class, $content, $attrs);
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create an input field.
	class INPUT extends TAG
	{
		//////////////////////////////////////////////////////////////////////////
		function __construct($id, $class, $name, $value = NULL, $size = NULL,
			$attrs = NULL)
		{
			if (!$attrs) $attrs = array();
			$attrs['name'] = $name;
			$attrs['value'] = $value;
			if ($size) $attrs['size'] = $size;
			parent::__construct("input", $id, $class, $attrs);
		}

		//////////////////////////////////////////////////////////////////////////
		// Get the attributes of this object.
		protected function getAttributes()
		{
			$text = " name=\"".$this->name."\" value=\"".$this->value."\"";
			if ($this->size) $text .= " size=\"".$this->size."\"";
			return $text;
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create a text field.
	class TEXT extends INPUT
	{
		//////////////////////////////////////////////////////////////////////////
		function __construct($id, $class, $name, $value = NULL, $size = NULL,
			$attrs = NULL)
		{
			if (!$attrs) $attrs = array();
			$attrs['type'] = "text";
			parent::__construct($id, $class, $name, $value, $size, $attrs);
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create a password field.
	class PASSWORD extends INPUT
	{
		//////////////////////////////////////////////////////////////////////////
		function __construct($id, $class, $name, $value=NULL, $size = NULL)
		{
			parent::__construct($id, $class, $name, $value, $size,
				array("type"=>"password"));
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create a hidden field.
	class HIDDEN extends INPUT
	{
		//////////////////////////////////////////////////////////////////////////
		function __construct($name, $value)
		{
			parent::__construct(NULL, NULL, $name, $value, NULL,
				array("type"=>"hidden"));
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create a file field.
	class FILE extends INPUT
	{
		//////////////////////////////////////////////////////////////////////////
		function __construct($id, $class, $name, $value = NULL, $size = NULL)
		{
			parent::__construct($id, $class, $name, $value, $size,
				array("type"=>"file"));
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create a radio button.
	class RADIO extends INPUT
	{
		function __construct($id, $class, $name, $value, $checked = FALSE, $attrs = NULL)
		{
			if (!$attrs) $attrs = array();
			$attrs["type"] = "radio";
			if ($checked) $attrs['checked'] = TRUE;
			parent::__construct($id, $class, $name, $value, NULL, $attrs);
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create a checkbox.
	class CHECKBOX extends INPUT
	{
		function __construct($id, $class, $name, $value, $checked = FALSE, $attrs = NULL)
		{
			if (!$attrs) $attrs = array();
			$attrs["type"] = "checkbox";
			if ($checked) $attrs['checked'] = TRUE;
			parent::__construct($id, $class, $name, $value, NULL, $attrs);
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create an image. Use this with a value of "submit" for a submit button.
	class IMAGE extends INPUT
	{
		//////////////////////////////////////////////////////////////////////////
		function __construct($id, $class = NULL, $name = NULL, $attrs = NULL)
		{
			if (!$attrs) $attrs = array();
			$attrs["type"] = "image";
			$attrs["name"] = $name;
			parent::__construct($id, $class, NULL, NULL, NULL, $attrs);
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create a submit button.
	class SUBMIT extends INPUT
	{
		//////////////////////////////////////////////////////////////////////////
		function __construct($id, $class, $name, $value, $attrs = NULL)
		{
			if (!$attrs) $attrs = array();
			$attrs['type'] = "submit";
			parent::__construct($id, $class, $name, $value, NULL, $attrs);
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create a selector.
	class SELECT extends CONTAINER
	{
		function __construct($id, $class, $name, $attrs = NULL)
		{
			if (!$attrs) $attrs = array();
			$attrs['name'] = $name;
			parent::__construct("select", $id, $class, NULL, $attrs);
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create an option.
	class OPTION extends CONTAINER
	{
		function __construct($id, $class, $value, $text = NULL, $selected = FALSE)
		{
			if (!$text)
			{
				$text = $value;
				$value = NULL;
				$attrs = array();
			}
			else $attrs = array("value"=>$value);
			if ($selected) $attrs['selected'] = TRUE;
			parent::__construct("option", $id, $class, $text, $attrs);
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create a text area.
	class TEXTAREA extends TAG
	{
		private $id;
		private $class;
		private $name;
		private $value;
		private $cols;
		private $rows;

		//////////////////////////////////////////////////////////////////////////
		function __construct($id, $class, $name, $value = NULL,
			$cols = 25, $rows = 10, $attrs = NULL)
		{
			$this->id = $id;
			$this->class = $class;
			$this->name = $name;
			$this->value = $value;
			$this->cols = $cols;
			$this->rows = $rows;
			$this->attrs = $attrs;
		}

		//////////////////////////////////////////////////////////////////////////
		// Get the HTML for this tag and all its content.
		// The function automatically pretty-prints the HTML it outputs.
		public function getHTML($indent = 0)
		{
			// Count the indent spaces required.
			$spaces = str_repeat("   ", $indent);
			// Get the tag header.
			$html = $spaces."<textarea";
			if ($this->id) $html .= " id=\"".$this->id."\"";
			if ($this->class) $html .= " class=\"".$this->class."\"";
			$html .= " name=\"".$this->name."\"";
			$html .= " cols=\"".$this->cols."\"";
			$html .= " rows=\"".$this->rows."\"";
			// Get the attributes of this tag (if any).
			if ($this->attrs)
			{
				if (is_array($this->attrs))
					foreach ($this->attrs as $name=>$value)
						$html .= " ".$name."=\"".$value."\"";
 				else $html .= " ".$this->attrs;
			}
			$html .= ">";
			if ($this->value) $html .= $this->value;
			$html .= "</textarea>";
			return $html;
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create a script, e.g. for Google analytics.
	class SCRIPT extends TAG
	{
		private $src;
		private $type;
		private $content;

		//////////////////////////////////////////////////////////////////////////
		function __construct($src = NULL, $content = NULL, $type = "text/javascript")
		{
			$this->src = $src;
			$this->type = $type;
			$this->content = $content;
		}

		//////////////////////////////////////////////////////////////////////////
		// Get the HTML for this tag and all its content.
		// The function automatically pretty-prints the HTML it outputs.
		public function getHTML($indent = 0)
		{
			// Count the indent spaces required.
			$spaces = str_repeat("   ", $indent);
			// Get the tag header.
			$html = $spaces."<script";
			if ($this->src) $html .= " src=\"".$this->src."\"";
			$html .= " type=\"".$this->type."\"";
			$html .= ">";
			if ($this->content) $html .= $this->content;
			$html .= "</script>";
			return $html;
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// Create an IFRAME.
	class IFRAME extends CONTAINER
	{
		//////////////////////////////////////////////////////////////////////////
		function __construct($width, $height, $src)
		{
			$attrs = array();
			$attrs['width'] = $width;
			$attrs['height'] = $height;
			$attrs['frameborder'] = 0;
			$attrs['marginwidth'] = 0;
			$attrs['marginheight'] = 0;
			$attrs['src'] = $src;
			parent::__construct("iframe", NULL, NULL, NULL, $attrs, FALSE);
		}
	}

	/////////////////////////////////////////////////////////////////////////////
	// This represents the entire document.
	class DOCUMENT
	{
		const XHTML = TRUE;

		private $head;
		private $body;

		private static $redirectTo;
		private static $debugList = array();

		//////////////////////////////////////////////////////////////////////////
		// Constructor.
		function __construct($head, $body)
		{
			$this->head = $head;
			$this->body = $body;
		}

		//////////////////////////////////////////////////////////////////////////
		// Force a redirect.
		public static function redirect($to)
		{
			self::$redirectTo = $to;
		}

		//////////////////////////////////////////////////////////////////////////
		// Force a restart of this site.
		public static function restart()
		{
			self::$redirectTo = FN::getProperty("host");
		}

		//////////////////////////////////////////////////////////////////////////
		// Add a debug statement.
		public static function debug($item)
		{
			self::$debugList[] = $item;
		}

		//////////////////////////////////////////////////////////////////////////
		// Get the HTML.
		public function getHTML()
		{
			if (self::$redirectTo) header ("Location: ".self::$redirectTo);
			else
			{
				$this->body->setOnLoad($this->head->getOnLoad(), TRUE);
				$this->body->setOnUnload($this->head->getOnUnload(), TRUE);
				$content = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\""
					."\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n"
					."<html lang=\"en-US\" xml:lang=\"en-US\""
					."xmlns=\"http://www.w3.org/1999/xhtml\">\n"
					.$this->head->getHTML()."\n"
					.$this->body->getHTML();
				if (count(self::$debugList))
				{
					$content .= "<br />";
					foreach (self::$debugList as $item) $content .= "$item<br />\n";
				}
				$content .= "\n</html>";
				print $content;
			}
		}
	}
?>
