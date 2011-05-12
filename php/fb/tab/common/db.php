<?php
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
	// This class handles all database functions.
	// One fundamental assumption is made, that all tables have a single
	// auto-incrementing integer primary key.
	class DB
	{
		private static $prefix;
		private static $tab;
		private static $page;
		private static $tabPageList = array();

		/////////////////////////////////////////////////////////////////////////
		// Set a table prefix.
		public static function setTablePrefix($prefix)
		{
			self::$prefix = $prefix;
		}

		/////////////////////////////////////////////////////////////////////////
		// Set the Facebook locale.
		public static function setLocale($tab, $page = NULL)
		{
			if (!$page) $page = FN::getSessionValue("pid");
			self::$tab = $tab;
			self::$page = $page;
		}

		/////////////////////////////////////////////////////////////////////////
		// Set the list of tables that must be qualified by tab and page.
		public static function setTabPageList($tabPageList)
		{
			self::$tabPageList = $tabPageList;
		}

		/////////////////////////////////////////////////////////////////////////
		// Get the tab ID.
		public static function getTab()
		{
			return self::$tab;
		}

		/////////////////////////////////////////////////////////////////////////
		// Get the page ID.
		public static function getPage()
		{
			return self::$page;
		}

		/////////////////////////////////////////////////////////////////////////
		// Connect to the database.
		public static function connect($host, $user, $password, $database)
		{
			mysql_connect($host, $user, $password)
				or die('Could not connect: '.mysql_error());
			mysql_select_db($database);
		}

		/////////////////////////////////////////////////////////////////////////
		// Create a table.
		public static function createTable($table, $fields)
		{
			if (!$fields) return;
			$table = self::$prefix.$table;
			$sql = "CREATE TABLE $table
			(
				id INT NOT NULL AUTO_INCREMENT,
				PRIMARY KEY(id),";
				$list = NULL;
				foreach ($fields as $key=>$value)
				{
					if ($list) $list .= ", ";
					$list .= " $key $value";
				}
			$sql .= "$list)";
			//echo "$sql<br>";
			mysql_query($sql) or die("Error: ".mysql_error());
			return "Table '$table' created.";
		}

		/////////////////////////////////////////////////////////////////////////
		// Drop a table.
		public static function dropTable($table)
		{
			$table = self::$prefix.$table;
			mysql_query("DROP TABLE $table");
		}

		/////////////////////////////////////////////////////////////////////////
		// Get the column metadata for a table.
		private static function getColumnMetadata($table)
		{
			$result = mysql_query("select * from $table");
			if (!$result) {
				die('Query failed: ' . mysql_error());
			}
			/* get column metadata */
			$columns = array();
			$column = 0;
			while ($column < mysql_num_fields($result))
			{
				$meta = mysql_fetch_field($result, $column);
				$columns[] = array(
					"blob"=>$meta->blob,
					"max_length"=>$meta->max_length,
					"multiple_key"=>$meta->multiple_key,
					"name"=>$meta->name,
					"not_null"=>$meta->not_null,
					"numeric"=>$meta->numeric,
					"primary_key"=>$meta->primary_key,
					"table"=>$meta->table,
					"type"=>$meta->type,
					"default"=>$meta->def,
					"unique_key"=>$meta->unique_key,
					"unsigned"=>$meta->unsigned,
					"zerofill"=>$meta->zerofill);
				$column++;
			}
			mysql_free_result($result);
			return $columns;
		}

		/////////////////////////////////////////////////////////////////////////
		// Report if a table exists.
		public static function tableExists($table)
		{
			$table = self::$prefix.$table;
			$tables = array();
			$result = mysql_query("SHOW TABLES LIKE '$table'");
			if (!$result)
			{
				echo "DB Error, could not list tables.<br>";
				echo 'MySQL Error: ' . mysql_error();
				exit;
			}
			$value = ($row = mysql_fetch_row($result));
			mysql_free_result($result);
			return $value;
		}

		/////////////////////////////////////////////////////////////////////////
		// Export a complete table's data.
		public static function export($table)
		{
			$tab = self::$tab;
			$page = self::$page;
			$fileName = "export/$table.txt";
			$fullTable = self::$prefix.$table;
			$meta = self::getColumnMetadata($table);
			$file = fopen($fileName, "w") or die("Can't open file:$fileName<br>");
			if (in_array($table, self::$tabPageList))
				$sql = "SELECT * FROM $fullTable WHERE tab=$tab AND page='$page' ORDER BY id";
			else
				$sql = "SELECT * FROM $fullTable ORDER BY id";
			$result = mysql_query($sql);
			while ($row = mysql_fetch_array($result))
			{
				// Iterate the columns
				foreach ($meta as $column)
				{
					$name = $column['name'];
					$numeric = $column['numeric'];
					// Get the data value
					$value = $row[$name];
					if (!$numeric) $value = urlencode($value);
					fwrite($file, "$name=$value\n");
				}
				fwrite($file, "\n");
			}
			fclose($file);
			return "Table '$table' exported.";
		}

		////////////////////////////////////////////////////////////////////////////
		// Import a complete table's data.
		public static function import($table)
		{
			$tab = self::$tab;
			$page = self::$page;
			$fileName = "export/$table.txt";
			$meta = self::getColumnMetadata(self::$prefix.$table);
			if (!file_exists($fileName)) return;
			$file = fopen($fileName, "r") or die("Can't open file:$fileName<br>");
			while (!feof($file))
			{
				$read = array();
				while (TRUE)
				{
					$item = trim(fgets($file));
					if (!$item) break;
					$index = strpos($item, "=");
					$name = substr($item, 0, $index);
					$value = substr($item, $index + 1);
					$read[$name] = $value;
				}
				if (!count($read)) return;
				// Now we have a record, but it might be in an old format.
				// So check if all the fields are present.
				$record = array();
				foreach ($meta as $column)
				{
					$name = $column['name'];
					$numeric = $column['numeric'];
					// See if the data is present for this column.
					if (isset($read[$name])) $value = $read[$name];
					else $value = NULL;
					if ($name == "id")
					{
						$id = $value;
						continue;
					}
					if (!$numeric) $value = urldecode($value);
					$record[$name] = $value;
				}
				// Now we have all the fields and their data, so write a record.
				// Set the inserted ID to be the same as the one in the imported record.
				$record["tab"] = $tab;
				$record["page"] = $page;
				$insertID = self::insert($table, $record);
				//echo "Record $insertID inserted into $table<br>";
				self::update($table, array("id"=>$id), "WHERE id=$insertID");
			}
			return "Table $table imported<br>";
		}

		/////////////////////////////////////////////////////////////////////////
		// Do an SQL INSERT.
		// $table is the name of the table.
		// $row is an associative array of field names and values.
		public static function insert($table, $row)
		{
			$table = self::$prefix.$table;
			$sql = "INSERT INTO $table (";
			$list = NULL;
			if (in_array($table, self::$tabPageList))
			{
				$row["tab"] = self::$tab;
				$row["page"] = self::$page;
			}
			foreach ($row as $name=>$value)
			{
				if ($list) $list .= ", ";
				$list .= $name;
			}
			$sql .= "$list) VALUES (";
			$list = NULL;
			foreach ($row as $name=>$value)
			{
				if ($list) $list .= ", ";
				$list .= "'".mysql_real_escape_string($value)."'";
			}
			$sql .= "$list)";
			//echo "$sql<br>";
			mysql_query($sql) or die('Error: '.mysql_error());
			return mysql_insert_id();
		}

		/////////////////////////////////////////////////////////////////////////
		// Do an SQL SELECT and return a single row of data.
		// $table is the name of the table.
		// $columns is an array of column names, or "*".
		// $more is any further SQL such as WHERE and ORDER BY clauses.
		public static function selectRow($table, $columns, $more = NULL)
		{
			$result = self::_select($table, FALSE, $columns, $more);
			if (mysql_num_rows($result))
			{
				$row = mysql_fetch_object($result);
				mysql_free_result($result);
				return $row;
			}
			return NULL;
		}

		/////////////////////////////////////////////////////////////////////////
		// Do an SQL SELECT and return the data.
		// $table is the name of the table.
		// $columns is an array of column names, or "*".
		// $more is any further SQL such as WHERE and ORDER BY clauses.
		public static function select($table, $columns, $more = NULL)
		{
			return self::_select($table, FALSE, $columns, $more);
		}

		/////////////////////////////////////////////////////////////////////////
		// Do an SQL SELECT DISTINCT and return the data.
		// $table is the name of the table.
		// $columns is an array of column names, or "*".
		// $more is any further SQL such as WHERE and ORDER BY clauses.
		public static function selectDistinct($table, $columns, $more = NULL)
		{
			return self::_select($table, TRUE, $columns, $more);
		}

		/////////////////////////////////////////////////////////////////////////
		// Count the number of rows returned by a query.
		// $table is the name of the table.
		// $more is any further SQL such as WHERE and ORDER BY clauses.
		public static function countRows($table, $more = NULL)
		{
			$result = self::_select($table, FALSE, array('id'), $more);
			$count = mysql_num_rows($result);
			DB::freeResult($result);
			return $count;
		}

		/////////////////////////////////////////////////////////////////////////
		// Count the number of rows returned by a query.
		//$result is the result of the query.
		public static function nRows($result)
		{
			return mysql_num_rows($result);
		}

		/////////////////////////////////////////////////////////////////////////
		//Seek to a given position.
		public static function seek($result, $position)
		{
			mysql_data_seek($result, $position);
		}

		/////////////////////////////////////////////////////////////////////////
		// Do a generic SQL SELECT and return the data.
		// $table is the name of the table.
		// If $distinct is TRUE add the DISTINCT modifier.
		// $columns is an array of column names, or "*".
		// $more is any further SQL such as WHERE and ORDER BY clauses.
		private static function _select($table, $distinct, $columns, $more)
		{
			// Fix $more to select by tab and page.
			if (in_array($table, self::$tabPageList))
			{
				$tab = self::$tab;
				$page = self::$page;
				$index = stripos($more, "WHERE ");
				if ($index === FALSE)
					$more = "WHERE tab=$tab AND page='$page' $more";
				else $more = substr($more, 0, 6)
					."tab=$tab AND page='$page' AND ".substr($more,6);
			}
			$table = self::$prefix.$table;
			$sql = "SELECT ";
			if ($distinct) $sql .= "DISTINCT ";
			if ($columns == "*") $sql .= "*";
			else
			{
				$list = NULL;
				foreach ($columns as $column)
				{
					if ($list) $list .= ",";
					$list .= $column;
				}
				$sql .= $list;
			}
			$sql .= " FROM $table";
			if ($more) $sql .= " $more";
			//echo "$sql<br>";
			$result = mysql_query($sql) or die("Could not run query: ".mysql_error());
			return $result;
		}

		/////////////////////////////////////////////////////////////////////////
		// Fetch a result row.
		public static function fetchRow($result)
		{
			return mysql_fetch_object($result);
		}

		/////////////////////////////////////////////////////////////////////////
		// Fetch a single particular value.
		public static function selectValue($table, $key, $more = NULL)
		{
			$row = self::selectRow($table, array($key), $more);
			return $row ? $row->$key: NULL;
		}

		/////////////////////////////////////////////////////////////////////////
		// Fetch a result row.
		public static function fetchObject($result)
		{
			return mysql_fetch_object($result);
		}

		/////////////////////////////////////////////////////////////////////////
		// Free the result row.
		public static function freeResult($result)
		{
			mysql_free_result($result);
		}

		/////////////////////////////////////////////////////////////////////////
		// Do an SQL UPDATE.
		// $table is the name of the table.
		// $row is an associative array of field names and values.
		// $where is the WHERE clause.
		public static function update($table, $row, $where = NULL)
		{
			if (in_array($table, self::$tabPageList))
			{
				// Fix $where to qualify by tab and page.
				$tab = self::$tab;
				$page = self::$page;
				$index = stripos($where, "WHERE ");
				if ($index === FALSE)
					$where = "WHERE tab=$tab AND page='$page' $where";
				else $where = substr($where, 0, 6)
					."tab=$tab AND page='$page' AND ".substr($where, 6);
			}
			$table = self::$prefix.$table;
			$sql = "UPDATE $table SET ";
			$list = NULL;
			foreach ($row as $name=>$value)
			{
				if ($list) $list .= ", ";
				$list .= "$name='".mysql_real_escape_string($value)."'";
			}
			$sql .= "$list $where";
			//echo "$sql<br>";
			mysql_query($sql) or die('Error: '.mysql_error());
			return mysql_insert_id();
		}

		/////////////////////////////////////////////////////////////////////////
		// Do an SQL DELETE.
		// $table is the name of the table.
		// $data is an associative array of field names and values.
		// $where is the WHERE clause.
		public static function delete($table, $where = NULL)
		{
			if (in_array($table, self::$tabPageList))
			{
				// Fix $where to qualify by tab and page.
				$tab = self::$tab;
				$page = self::$page;
				$index = stripos($where, "WHERE ");
				if ($index === FALSE)
					$where = "WHERE tab=$tab AND page='$page' $where";
				else $where = substr($where, 0, 6)
					."tab=$tab AND page='$page' AND ".substr($where,6);
			}
			$table = self::$prefix.$table;
			$sql = "DELETE FROM $table $where";
			//echo "$sql<br>";
			mysql_query($sql) or die('Error: '.mysql_error());
		}

		/////////////////////////////////////////////////////////////////////////
		// Do an arbitrary SQL query
		// $sql is the SQL query to execute.
		public static function query($sql)
		{
			//echo "$sql<br>";
			mysql_query($sql) or die('Error: '.mysql_error());
		}
	}
?>