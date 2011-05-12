<?php
   ////////////////////////////////////////////////////////////////////////////
   // This class handles all functions relating to the masthead.
   class Masthead extends DIV
   {
      /////////////////////////////////////////////////////////////////////////
      // Constructor.
      function __construct($head)
      {
         parent::__construct("masthead");
         $head->addCSS("css/masthead.css");
         
         $text = FN::get("masthead",
            (isset($_COOKIE[FN::getProperty("session")]) ? "editor" : "normal"))
            . FN::get("masthead", "mission");

         $this->add(new DIV("inner", NULL, new DIV("text", NULL, $text)));
         $this->add(new DIV("contents", NULL, FN::get("masthead", "contents")));
      }
   }
?>
