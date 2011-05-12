<?php
	////////////////////////////////////////////////////////////////////////////
	// A general exception class.
	class GeneralException extends Exception
	{
		public function __construct($message, $code=0)
		{
			parent::__construct($message, $code);
		}

		////////////////////////////////////////////////////////////////////////////
		// Show the exception trace.
		public function showExceptionInfo($html = FALSE)
		{
			$trace = $this->getTraceAsString();
			$content = "Exception: " . $this->getMessage()
				."\n" . $this->getFile() . "(" . $this->getLine() . ")"
				."\n". $trace
				."\n\nPlease report this message to our admin team.";
			if ($html) $content = str_replace("\n", "<br />\n", $content);
			return $content;
		}
	}
?>
