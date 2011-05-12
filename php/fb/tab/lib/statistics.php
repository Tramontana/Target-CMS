<?php
	// This is the statistics module.
	class Statistics extends Base
	{
		private $GOOGLE = "66.249.";
		private $MSN = "65.55.";
		private $COUNT = 5;
		private $LIMIT = 600;
		private $LOAD_LIMIT = 4.0;
		private $NOREF_LIMIT = 20;
		private $NOREF_INTERVAL = 14400; //60 * 60 * 4
		private $UNBLOCK = 14400; //60 * 60 * 4
		private $UNBLOCK_SHORT = 1800; //60 * 30

		/* Major search engines match either $SPIDER_FOOTPRINT or $SPIDER_IP. */
		/* If no match can be made, popular browsers are sorted out and the */
		/* request is supposed to be from a smaller search engine. */

		private $SPIDER_FOOTPRINT = array("cooter", "lurp", "rawler", "pider", "obot", "eek",
			"oogle", "canner", "rachnoidea", "ulliver", "arvest", "ireball", "idewinder");

		private $SPIDER_IP = array("204.123.", "204.74.103.", "203.108.10.", "195.4.183.",
			"195.242.46.", "198.3.97.", "204.62.245.", "193.189.227.", "209.1.12.",
			"204.162.96.", "204.162.98.", "194.121.108.", "128.182.72.", "207.77.91.",
			"206.79.171.", "207.77.90.", "208.213.76.", "194.124.202.", "193.114.89.",
			"193.131.74.", "131.84.1.", "208.219.77.", "206.64.113.", "195.186.1.",
			"195.3.97.", "194.191.121.", "139.175.250.", "209.73.233.", "194.191.121.",
			"198.49.220.", "204.62.245.", "198.3.99.", "198.2.101.", "204.192.112.",
			"206.181.238", "208.215.47.", "171.64.75.", "204.162.98.", "204.162.96.",
			"204.123.9.52", "204.123.2.44", "204.74.103.39", "204.123.9.53", "204.62.245.",
			"206.64.113.", "194.100.28.20", "204.138.115.", "94.22.130.", "164.195.64.1",
			"205.181.75.169", "129.170.24.57", "204.162.96.", "204.162.96.", "204.162.98.",
			"204.162.96.", "207.77.90.", "207.77.91.", "208.200.146.", "204.123.9.20",
			"204.138.115.", "209.1.32.", "209.1.12.", "192.216.46.49", "192.216.46.31",
			"192.216.46.30", "203.9.252.2");

		private $BROWSER_FOOTPRINT = array("95", "98", "MSIE", "NT", "Opera", "16", "32",
			"MAC", "Mac", "X11", "WebTV", "OS", "Lynx", "IBrowse", "IWENG", "PRODIGY",
			"Mosaic", "InterGO", "Gold", "zzZ", "Mozzarella", "Vampire", "Cache", "libwww",
			"TURBO", "WebCompass", "Konqueror", "Firefox", "Wget", "Amiga");
			/* 'Mozilla' is not included, for many spiders pretend to be a Mozilla */
			/* browser. Regular Mozilla browsers include almost always one of the */
			/* above footprints additionally. */

		/////////////////////////////////////////////////////////////////////////
		// Constructor.
		function __construct()
		{
			parent::__construct();
		}

		////////////////////////////////////////////////////////////////////////////
		// Get the list of tables.
		protected function getTableList()
		{
			return array("google", "msn", "spider", "ipstats");
		}

		////////////////////////////////////////////////////////////////////////////
		// Get the field list for a table.
		public function getFieldList($name)
		{
			switch ($name)
			{
				case "google":
					return array(
						"timestamp"=>"INT",
						"page"=>"TEXT"
						);

				case "msn":
					return array(
						"timestamp"=>"INT",
						"page"=>"TEXT"
						);

				case "spider":
					return array(
						"timestamp"=>"INT",
						"page"=>"TEXT",
						"host"=>"TEXT"
						);

				case "ipstats":
					return array(
						"user"=>"INT",
						"ipaddr"=>"CHAR(15)",
						"count"=>"INT",
						"loading"=>"FLOAT",
						"timestamp"=>"INT",		// the last event
						"timelimit"=>"INT",		// limit for counting hits
						"unblock"=>"INT",			// when to unblock
						"nBlocks"=>"INT",			// # of blocks from this IP
						"noRefCount"=>"INT"		// the number of consecutive noRefs
						);
			}
		}

		////////////////////////////////////////////////////////////////////////////
		// Get extra table information.
		protected function getExtraTableInfo($name)
		{
			switch ($name)
			{
				case "ipstats":
					return array(
						"ALTER TABLE ipstats ADD KEY (ipaddr)",
						"ALTER TABLE ipstats ADD KEY (timestamp)");
			}
			return NULL;
		}
		
		////////////////////////////////////////////////////////////////////////////
		// Count a hit and check for bots.
		// This algorithm does not at present do anything with the hit count;
		// instead the loading is used as a better measure.
		public function countHit()
		{
			// If an AJAX call, don't do anything.
			if (isset($_REQUEST["ajax"])) return NULL;

			$timestamp = time();
			$agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : NULL;
			$ipaddr = $_SERVER['REMOTE_ADDR'];
			$page = $_SERVER['REQUEST_URI'];
			// Check if it's Google or MSN
			$flag = FALSE;
			if (strpos($ipaddr, $this->GOOGLE) === 0)
			{
				DB::insert("google", array("timestamp"=>$timestamp, "page"=>$page));
				return NULL;
			}
			if (strpos($ipaddr, $this->MSN) === 0)
			{
				DB::insert("msn", array("timestamp"=>$timestamp, "page"=>$page));
				return NULL;
			}

			/**** spider or not? ****/
			$isSpider = 0;

			$i = 0;
			while ($i < (count($this->SPIDER_FOOTPRINT)))
			{
				if (strstr($agent, $this->SPIDER_FOOTPRINT[$i]))
				{
					$isSpider = 1;
					break;
				}
				$i++;
			}

			if ($isSpider != 1)
			{
				$i = 0;
				while ($i < (count($this->SPIDER_IP)))
				{
					if (strstr($ipaddr, $this->SPIDER_IP[$i]))
					{
						$isSpider = 1;
						break;
					}
					$i++;
				}
			}

			if ($isSpider != 1)
			{
				$isSpider = 1; /* when in doubt, it's logged */
				$i = 0;
				while ($i < (count($this->BROWSER_FOOTPRINT)))
				{
					if (strpos($agent, $this->BROWSER_FOOTPRINT[$i]))
					{
						$isSpider = 0;
						break;
					}
					$i++;
				}
			}

			/*** log the spider access ***/
			if ($isSpider == 1)
			{
				$hostName = getHostByAddr($ipaddr);
				DB::insert("spider", array(
					"timestamp"=>$timestamp,
					"page"=>$page,
					"host"=>$hostName
					));
				return NULL;
			}

			// If not...
			$referrer = NULL;
			if (isset($_SERVER['HTTP_REFERER'])) $referrer = $_SERVER['HTTP_REFERER'];
			// Deal with bots and DOS attacks.
			// The strategy is to count the number of successive hits
			// that have no referring address. This means either the user
			// is using bookmarks or is typing URLs into the address bar.
			$response = NULL;
			$row = DB::selectRow("ipstats", "*", "WHERE ipaddr='$ipaddr'");
			if ($row)
			{
				$id = $row->id;
				$count = $row->count + 1;
				$lastTime = $row->timestamp;
				$limit = $row->timelimit;
				$unblock = $row->unblock;
				$noRefCount = $row->noRefCount;
				$nBlocks = $row->nBlocks;
				$interval = 0.0 + $timestamp - $lastTime;
				if ($interval > $this->NOREF_INTERVAL) $noRefCount = 0;
				if ($interval < 1.0) $interval = 1.0;
				$loading = $row->loading * 0.8 + 1.0 / $interval;
				//echo '<font color="white">Count '.$count
				//	.', loading '.$loading.'</font><br>';
				// Check if we're blocked.
				if ($unblock)
				{
					// If the block has expired, reset it.
					if ($timestamp > $unblock)
					{
						$unblock = 0;
						$limit = $timestamp + $this->LIMIT;
						$loading = 0.0;
						FN::unsetSessionValue("block");
					}
					else
					{
						// Otherwise pick a suitable file to return.
						if (FN::checkSessionValue("block"))
							$fileName = FN::getSessionValue("block");
						else $fileName = "bots/blocked.html";
						FN::setSessionValue("block", $fileName);
						$response = FN::getFile($fileName);
					}
				}
				else
				{
					// Now count hits with no referring address.
					if ($referrer || strpos($page, "page=home") > 0
						|| strpos(strrev($page), "moc.") === 0)
					{
						$noRefCount = 0;
						$nBlocks = 0;
						$count = 1;
					}
					else
					{
						$noRefCount++;
						if ($noRefCount > $this->NOREF_LIMIT)
						{
							$nBlocks++;
							$unblock = $timestamp + $this->UNBLOCK_SHORT * $nBlocks;
							$fileName = "bots/blocked.html";
							FN::setSessionValue("block", $fileName);
							$response = FN::getFile($fileName);
							$this->sendBlockedEmail($ipaddr, $timestamp, $unblock);
						}
					}
					if (!$unblock)
					{
						// Now check the loading. If the usage is excessive, block the user.
						// The algorithm takes a percentage of the existing load and adds
						// the reciprocal of the interval (in seconds) since the last hit.
						// If the new load is greater than a fixed limit, block the user.
						//echo "New loading is $loading<br>";
						if ($loading > $this->LOAD_LIMIT)
						{
							$unblock = $timestamp + $this->UNBLOCK_SHORT;
							$response = FN::getFile("bots/blocked2.html");
							FN::setSessionValue("block", "bots/blocked2.html");
							$this->sendBlockedEmail($ipaddr, $timestamp, $unblock);
						}
					}
				}
				// Update the record.
				DB::update("ipstats", array(
					"count"=>$count,
					"loading"=>$loading,
					"timestamp"=>$timestamp,
					"timelimit"=>$limit,
					"unblock"=>$unblock,
					"nBlocks"=>$nBlocks,
					"noRefCount"=>$noRefCount
					), "WHERE id=$id");
			}
			else
			{
				// New record needed here.
				$limit = $timestamp + $this->LIMIT;
				DB::insert("ipstats", array(
					"ipaddr"=>$ipaddr,
					"count"=>1,
					"loading"=>0.0,
					"timestamp"=>$timestamp,
					"timelimit"=>$limit,
					"unblock"=>0,
					"nBlocks"=>0
					));
			}
			if ($response) return $response;

			// If no block was needed, count the hit.
			$date = date("G:i \o\\n F j, Y", $timestamp);
			$hostName = getHostByAddr($ipaddr);
			if (!file_exists("hits")) mkdir("hits");
			$file = "hits/".date("Y", $timestamp);
			if (!file_exists($file)) mkdir($file);
			$file.= "/".date("Ymd", $timestamp);
			$fp = fopen($file, "a+") or die("Can't open $file");
			fwrite($fp, "timestamp=$timestamp ($date)\n");
			fwrite($fp, "ipaddr=$ipaddr ($hostName)\n");
			fwrite($fp, "referrer=$referrer\n");
			fwrite($fp, "page=$page\n");
			fwrite($fp, "\n");
			fclose($fp);
			
			return NULL;
		}

		/////////////////////////////////////////////////////////////////////////
		// Send an email to the admin to report a blocked IP.
		public function sendBlockedEmail($ipaddr, $timestamp)
		{
			$dateString = date("M d Y H:i:s", $timestamp);
			$to = "gt@pobox.com";
			$subject = "IP block";
			$body = "Address $ipaddr blocked at $timestamp ($dateString)";
			$headers = "From The Business Card Club Admin\n";
			mail($to, $subject, $body, $headers);
		}

		////////////////////////////////////////////////////////////////////////////
		// Get the command list for this module.
		protected function getCommandList()
		{
			return array(
				'Select date' => 'setDate'
				);
		}
		
		/////////////////////////////////////////////////////////////////////////
		// List daily hits.
		public function daily($head)
		{
			$year = isset($_REQUEST['year']) ? $_REQUEST['year'] : 0;
			$month = isset($_REQUEST['month']) ? $_REQUEST['month'] : 0;
			$day = isset($_REQUEST['day']) ? $_REQUEST['day'] : 0;

			$head->addScript("scripts/date.js");
			$head->setOnLoad("populate($day, $month, $year)");
			
			$content = new FORM(NULL, NULL, "date");
			$content->add(new H1(NULL, NULL, "Daily hits"));
			$content->add(new DIV(NULL, NULL, new A(NULL, NULL,
				"Back to main menu", "?admin")));
			$content->add(new DIV());
			$m = new SELECT(NULL, NULL, "month",
				array("onchange"=>"populate2()"));
			$m->add(new OPTION(NULL, NULL, "01", "January"));
			$m->add(new OPTION(NULL, NULL, "02", "February"));
			$m->add(new OPTION(NULL, NULL, "03", "March"));
			$m->add(new OPTION(NULL, NULL, "04", "April"));
			$m->add(new OPTION(NULL, NULL, "05", "May"));
			$m->add(new OPTION(NULL, NULL, "06", "June"));
			$m->add(new OPTION(NULL, NULL, "07", "July"));
			$m->add(new OPTION(NULL, NULL, "08", "August"));
			$m->add(new OPTION(NULL, NULL, "09", "September"));
			$m->add(new OPTION(NULL, NULL, "10", "October"));
			$m->add(new OPTION(NULL, NULL, "11", "November"));
			$m->add(new OPTION(NULL, NULL, "12", "December"));
			$content->add($div = new DIV());
			$div->add(new SELECT(NULL, NULL, "day"));
			$div->add($m);
			$div->add(new SELECT(NULL, NULL, "year"));
			$content->add(new HIDDEN("admin", "1"));
			$content->add(new HIDDEN("section", "hits"));
			$content->add(new DIV(NULL, NULL,
				new SUBMIT(NULL, NULL, "action", "Select date")));
			
			$content->add($hits = new DIV("hits"));
			$time = mktime(0, 0, 0, $month, $day, $year);
			$ipaddresses = array();
			
			$count = 0;
			// Find the correct file and process it.
			$fileName = "hits/".date("Y", $time)."/".date("Ymd", $time);
			if (!file_exists($fileName)) return $content;
			$fp = fopen($fileName, "r");
			while (!feof($fp))
			{
				$timestamp = NULL;
				$ipaddr = NULL;
				$referrer = NULL;
				$page = NULL;
				$host = NULL;
				while ($line = trim(fgets($fp)))
				{
					$items = explode("=", $line, 2);
					if (count($items) < 2) break;
					$name = $items[0];
					$value = $items[1];
					switch ($name)
					{
						case "timestamp":
							$timestamp = $value;
							break;
						case "ipaddr":
							$ipaddr = $value;
							break;
						case "referrer":
							$referrer = $value;
							break;
						case "page":
							$page = $value;
							break;
					}
				}
				if ($timestamp)
				{
					// Count the hits per IP address.
					if (array_key_exists($ipaddr, $ipaddresses)) $ipaddresses[$ipaddr]++;
					else $ipaddresses[$ipaddr] = 1;
					$page = basename($page);
					if (!$page) $page = "index.php";
					if (strlen($page) > 80) $page = substr($page, 0, 80)."...";
					if (strlen($referrer) > 120) $referrer = substr($referrer, 0, 120)."...";
					$text = "$timestamp&nbsp;&nbsp;$ipaddr&nbsp;$host<br />"
						."&nbsp;&nbsp;".$page;
					if ($referrer) $text .= "<br />&nbsp;&nbsp;$referrer";
					$hits->add(new DIV(NULL, NULL, $text));
					$count++;
				}
			}
			fclose($fp);

			$hits->add(new DIV());
			arsort($ipaddresses);
			foreach ($ipaddresses as $ipaddr=>$hit)
			{
				$hits->add(new DIV(NULL, NULL,
					"$ipaddr&nbsp;&nbsp($hit hit".($hit > 1 ? "s" : NULL).")"));
			}
			$hits->add(new DIV());
			$hits->add(new DIV(NULL, NULL, "Total: $count hits"));
			return $content;
		}

		/////////////////////////////////////////////////////////////////////////
		// Show blocked IP addresses.
		protected function blocks()
		{
			$content = "<h1>Blocked IP addresses</h1>\n";
			$content .= '<div style="text-align: left;">';
			$count = 0;
			$timestamp = time();
			$startTime = $timestamp - 60 * 60 * 24 * 7;
			$result = DB::select("ipstats", "*", "WHERE unblock>0 ORDER BY timestamp DESC");
			while ($row = DB::fetchRow($result))
			{
				$ipaddr = $row->ipaddr;
				$red = FALSE;
				if ($row->unblock > $timestamp)
				{
					$content .= '<span style="color: rgb(204, 0, 0);">';
					$red = TRUE;
					$count++;
				}
				$content .= HTML::getLink($ipaddr, array(
					"module"=>"statistics",
					"page"=>"userPath",
					"ipaddr"=>$ipaddr,
					"time1"=>$startTime,
					"time2"=>$timestamp
					));
				$content .= " - ".$row->count." hits. Blocked on "
				.date("F d Y H:i:s", $row->timestamp)
					.", unblock at ".date("H:i:s", $row->unblock)."<br>";
				if ($red) $content .= "</span>";
			}
			DB::freeResult($result);
			$content .= "<br>$count address".(($count == 1) ? NULL : "es");
			$content .= " currently blocked.";
			$content .= '</div>';
			$content .= '<div style="text-align: center;">';
			$content .= HTML::getLink("Return to index", array("page"=>"admin3"));
			$content .= '</div>';
			return $content;
		}
	}
?>
