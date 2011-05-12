<?php
   ///////////////////////////////////////////////////////////////////////////
   // This is the breadcrumbs module.
   class Breadcrumbs extends DIV
   {
      ///////////////////////////////////////////////////////////////////////
      // Constructor.
      function __construct($head, $template)
      {
         parent::__construct("breadcrumbs");
         $head->addCSS("css/breadcrumbs.css");

         $breadcrumbs = array($template->getName());
         while ($template = $template->getParent())
         {
         	$link = new A(NULL, NULL, $template->getName(),
         		$template->getURL());
         	$breadcrumbs[] = $link->getHTML();
         }
         $text = NULL;
         foreach ($breadcrumbs as $item)
         {
         	if ($text) $text = " -> $text";
         	$text = $item . $text;
         }
         $this->add($text);
      }
   }
?>
