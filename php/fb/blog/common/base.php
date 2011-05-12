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
	// This is the base class of all functional modules.
	abstract class Base
	{
		////////////////////////////////////////////////////////////////////////////
		// Constructor.
		function __construct()
		{
			// Get the table list
			$tables = $this->getTableList();
			foreach ($tables as $name)
			{
				if (!DB::tableExists($name))
					DB::createTable($name, $this->getFieldList($name));
			}
		}

		////////////////////////////////////////////////////////////////////////////
		// Export the table to a tab-delimited text file.
		public function export()
		{
			$tables = $this->getTableList();
			foreach ($tables as $name) DB::export($name);
		}

		////////////////////////////////////////////////////////////////////////////
		// Rebuild the table, taking care to preserve the original IDs.
		public function import($name = NULL)
		{
			if ($name)
			{
				DB::dropTable($name);
				$this->debug(DB::createTable($name, $this->getFieldList($name)));
				DB::import($name);
			}
			else
			{
				$tables = $this->getTableList();
				foreach ($tables as $name)
				{
					DB::dropTable($name);
					DB::createTable($name, $this->getFieldList($name));
					DB::import($name);
				}
			}
		}

		////////////////////////////////////////////////////////////////////////////
		// Functions that must be overridden by child classes.
		////////////////////////////////////////////////////////////////////////////

		////////////////////////////////////////////////////////////////////////////
		// Get the table list.
		abstract protected function getTableList();

		////////////////////////////////////////////////////////////////////////////
		// Get a field list.
		abstract protected function getFieldList($name);
	}
?>
