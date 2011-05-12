<?php // 100628
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

	// This is a general-purpose function library.
	class FN
	{
		private static $props = array();

		////////////////////////////////////////////////////////////////////////////
		// Set all the properties.
		public static function setProperties($props)
		{
			self::$props = $props;
		}
		
		////////////////////////////////////////////////////////////////////////////
		// Set a property.
		public static function setProperty($name, $value)
		{
			self::$props[$name] = $value;
		}
		
		////////////////////////////////////////////////////////////////////////////
		// Get a property.
		public static function getProperty($name, $default = NULL)
		{
			return isset(self::$props[$name]) ? self::$props[$name] : $default;
		}
		
		////////////////////////////////////////////////////////////////////////////
		// Get the contents of a named file.
		public static function getFile($name)
		{
			$theFile = fopen($name, 'r') or die("File $name could not be opened");
			$content = fread($theFile, filesize($name))
				or die("File $name could not be read");
			fclose($theFile);
			return $content;
		}

		////////////////////////////////////////////////////////////////////////////
		// Get a request parameter.
		public static function getRequest($name, $default = NULL)
		{
			return isset($_REQUEST[$name]) ? $_REQUEST[$name] : $default;
		}

		////////////////////////////////////////////////////////////////////////////
		// Replace a set of parameters in a string.
		public static function replace($string, $params)
		{
			foreach ($params as $key=>$value)
			{
				$string = str_replace($key, $value, $string);
			}
			return $string;
		}

		////////////////////////////////////////////////////////////////////////////
		// Read a properties file into an array.
		public static function getProperties($fileName, $multiline = TRUE)
		{
			$properties = array();
			if (!file_exists($fileName)) return $properties;
			$file = fopen($fileName, "r") or die('Could not read properties file.');
			$key = NULL;
			while (!feof($file))
			{
				$ss = trim(fgets($file));
				if (!$ss || substr($ss, 0, 1) == "#") continue;
				$flag = FALSE;
				if ($multiline)
				{
					// Tilde means space.
					$ss = strtr($ss, "~", " ");
					// - at the start of a line means new line using <br>.
					if (strpos($ss, "-") === 0 && $key)
						$properties[$key] .= "<br>".substr($ss, 1);
					// + at the start of a line means new line using \n.
					elseif (strpos($ss, "+") === 0 && $key)
						$properties[$key] .= "\n".substr($ss, 1);
					else $flag = TRUE;
				}
				else $flag = TRUE;
				if ($flag)
				{
					$ss = explode("=", $ss, 2);
					if (isset($ss[1]))
					{
						$key = $ss[0];
						$properties[$key] = $ss[1];
					}
				}
			}
			fclose($file);
			return $properties;
		}
		
		////////////////////////////////////////////////////////////////////////////
		// Check if a file exists.
		public static function fileExists($name)
		{
			return file_exists("../php/template/$name.php");
		}

		////////////////////////////////////////////////////////////////////////////
		// Load a template. This is an HTML container such as a DIV.
		public static function getTemplate($name, $head, $params = NULL)
		{
			$template = "../php/template/$name.php";
			if (file_exists($template))
			{
				require_once($template);
				return new $name($head, $params);
			}
			else return new DIV(NULL, NULL, "Template '$name' is missing");
		}

		////////////////////////////////////////////////////////////////////////////
		// Set a session value.
		public static function setSessionValue($item, $value = TRUE)
		{
         $id = self::getProperty("session")."-$item";
         $_SESSION[$id] = $value;
		}

		////////////////////////////////////////////////////////////////////////////
		// Get a session value.
		public static function getSessionValue($item, $default = NULL)
		{
         $id = self::getProperty("session")."-$item";
         return isset($_SESSION[$id]) ? $_SESSION[$id] : $default;
		}

		////////////////////////////////////////////////////////////////////////////
		// Unset a session value.
		public static function unsetSessionValue($item)
		{
         $id = self::getProperty("session")."-$item";
         unset($_SESSION[$id]);
		}

		////////////////////////////////////////////////////////////////////////////
		// Check if a session value is set.
		public static function checkSessionValue($item)
		{
         $id = self::getProperty("session")."-$item";
         return isset($_SESSION[$id]);
		}

		////////////////////////////////////////////////////////////////////////////
		// Get the user ID. This is held in a cookie and a session value.
		public static function getUserID()
		{
			// If the caller requested a logout, return zero.
			if (isset($_REQUEST["logout"]))
			{
				$userID = self::getSessionValue("userID");
				if ($userID)
				{
					DB::update("user", array(
						"loggedIn"=>0
						), "WHERE id=$userID");
				}
				self::unsetSessionValue("userID");
				setcookie(self::getProperty("session")."-uid", "", time()-3600);
				return 0;
			}
			// If we already have a value, return it now.
			$id = self::getProperty("session")."-uid";
			$userID = 0;
			if (self::checkSessionValue("userID"))
			{
				$userID = self::getSessionValue("userID");
				setcookie($id, $userID, time()+60*60*24*30);
				DB::update("user", array(
					"loggedIn"=>0
					), "WHERE id=$userID");
				return $userID;
			}
			// If not, get the value from the cookie.
			if (isset($_COOKIE[$id]))
			{
				// Refresh the cookie.
				$userID = $_COOKIE[$id];
				self::setSessionValue("userID", $userID);
				setcookie($id, $userID, time()+60*60*24*30);
				DB::update("user", array(
					"loggedIn"=>0
					), "WHERE id=$userID");
			}
			return $userID;
		}

		///////////////////////////////////////////////////////////////////////
		// Set the user ID. This is held in a cookie. Set it for 30 days.
		public static function setUserID($userID)
		{
			self::setSessionValue("userID", $userID);
			setcookie(self::getProperty("session")."-uid", $userID, time()+60*60*24*30);
		}

		////////////////////////////////////////////////////////////////////////////
		// Get the two-letter code for the current language.
		public static function getLanguageCode()
		{
			$language = self::getSessionValue("language", 1);
         if (!$language) $language = 1;
         return DB::selectValue("language", "code", "WHERE id=$language");
		}

		////////////////////////////////////////////////////////////////////////////
		// Get a content element.
		public static function get($template, $name, $default = NULL)
		{
			$language = self::getSessionValue("language", 1);
         if (!$language) $language = 1;
			$t = DB::selectValue("template", "id", "WHERE name='$template'");
			if (!$t) $t = 0;
			$where = "WHERE template=$t AND name='$name'";
			if ($language) $where .= " AND language='$language'";
			$content = stripslashes(DB::selectValue("content", "value", $where));
			// If not found, try the default language.
			if (!$content)
			{
				$defLang = FN::getProperty("language");
				$language = DB::selectValue("language", "id", "WHERE code='$defLang'");
				$content = stripslashes(DB::selectValue("content", "value",
					"WHERE template=$t AND name='$name' AND language='$language'"));
			}
			return $content ? $content: $default;
		}

		////////////////////////////////////////////////////////////////////////////
		// Get a content element without any paragraph tags.
		public static function getPlain($template, $name, $default = NULL)
		{
			$content = self::get($template, $name, $default);
			return self::stripParagraphTags($content);
		}

		////////////////////////////////////////////////////////////////////////////
		// Strip paragraph tags.
		public static function stripParagraphTags($content)
		{
			if (strpos($content, "<p>") === 0)
			{
				$content = substr($content, 3);
				$index = strrpos($content, "</p>");
				$content = substr($content, 0, $index);
			}
			return trim($content);
		}

		////////////////////////////////////////////////////////////////////////////
		// Get a menu.
		public static function getMenu($name)
		{
			return stripslashes(DB::selectValue("menu", "value",
				"WHERE name='$name'"));
		}

		////////////////////////////////////////////////////////////////////////////
		// Get a month name.
		public static function getMonthName($month)
		{
	      switch ($month)
	      {
	         case 1: $month = "January"; break;
	         case 2: $month = "February"; break;
	         case 3: $month = "March"; break;
	         case 4: $month = "April"; break;
	         case 5: $month = "May"; break;
	         case 6: $month = "June"; break;
	         case 7: $month = "July"; break;
	         case 8: $month = "August"; break;
	         case 9: $month = "September"; break;
	         case 10: $month = "October"; break;
	         case 11: $month = "November"; break;
	         case 12: $month = "December"; break;
	      }
			return $month;
		}

		////////////////////////////////////////////////////////////////////////////
		// Sanitise a text argument.
		public static function sanitise($text)
		{
			return htmlentities(trim($text), ENT_NOQUOTES);
		}

		/////////////////////////////////////////////////////////////////////////
		// Create a thumbnail from an image.
		// The new algorithm produces a square image but keeps the whole
		// of the original, shrinking it as necessary.
		public static function createSquareImage($source, $dest, $thumbSize = 100)
		{
			$size = getimagesize($source);
			$width = $size[0];
			$height = $size[1];

			$x = 0;
			$y = 0;
			// If it's wider than it's tall, make its width the thumbnail size.
			if ($width > $height)
			{
				$twidth = $thumbSize;
				$theight = $twidth * $height / $width;
				$y = ceil(($twidth - $theight) / 2 );
			}
			else
			{
				$theight = $thumbSize;
				$twidth = $theight * $width / $height;
				$x = ceil(($theight - $twidth) / 2);
			}

			$new_im = ImageCreateTrueColor($thumbSize, $thumbSize);
			$white = ImageColorAllocate($new_im, 255, 255, 255);
			ImageFilledRectangle($new_im, 0, 0, $thumbSize, $thumbSize, $white);
			$type = strtolower(substr($source, strrpos($source, ".")));
			switch ($type)
			{
				case ".gif":
					$im = ImageCreateFromGIF($source);
					break;
				case ".jpg":
				case ".jpeg":
					$im = ImageCreateFromJPEG($source);
					break;
				case ".png":
					$im = ImageCreateFromPNG($source);
					break;
				default:
					return;
			}
			ImageFilledRectangle($new_im, 0, 0, $thumbSize - 1, $thumbSize - 1, $white);
			ImageCopyResampled($new_im, $im, $x, $y, 0, 0, $twidth, $theight,
				$width, $height);
			ImageJPEG($new_im, $dest, 90);
		}

		/////////////////////////////////////////////////////////////////////////
		// Encode a string so it can be decoded by Javascript unescape().
		public static function fullescape($in)
		{
			$out = '';
			for ($i=0; $i<strlen($in); $i++)
			{
				$hex = dechex(ord($in[$i]));
				if ($hex=='') $out = $out.urlencode($in[$i]);
				else $out = $out .'%'.((strlen($hex)==1) ? ('0'.strtoupper($hex)):(strtoupper($hex)));
			}
			$out = str_replace('+','%20',$out);
			$out = str_replace('_','%5F',$out);
			$out = str_replace('.','%2E',$out);
			$out = str_replace('-','%2D',$out);
			return $out;
		}

		////////////////////////////////////////////////////////////////////////////
		// Get an HTML file. Return it as an array each containing a single tag
		// or a string of content.
		public static function getHTML($name)
		{
			$file = fopen($name, "r") or die('Could not read file '.$name);
			$data = array();
			$item = NULL;
			$inTag = FALSE;
			while (!feof($file))
			{
				$char = fgetc($file);
				switch ($char)
				{
					case '<':
						if ($item)
						{
							$item = trim(str_replace("\x0a", "\x20", $item));
							if ($item) $data[] = $item;
						}
						$item = $char;
						$inTag = TRUE;
						break;
					case '>':
						$item .= $char;
						$item = trim(str_replace("\x0a", "\x20", $item));
						if ($item) $data[] = $item;
						$item = NULL;
						$inTag = FALSE;
						break;
					default:
						$item .= $char;
						break;
				}
			}
			fclose($file);
			return $data;
		}

		////////////////////////////////////////////////////////////////////////////
		// Get the body of an HTML file.
		public static function getBody($name)
		{
			$body = NULL;
			$flag = FALSE;
			$data = self::getHTML($name);
			foreach ($data as $item)
			{
				if ($item == "</body>") $flag = FALSE;
				if ($flag) $body .= "$item";
				if (substr_count(trim($item), "<body")) $flag = TRUE;
			}
			return $body;
		}

		/////////////////////////////////////////////////////////////////////////
		// Check that the referrer is from within this site.
		public static function internalReferrer()
		{
			$referrer = isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : NULL;
			return (substr_count($referrer, FN::getProperty("host")) == 1);
		}
	}
?>
