<?php
   ////////////////////////////////////////////////////////////////////////////
   // Menu manager.
   // The menu contents are taken from the database using the name of the menu.
   // If the item is currently being shown the menu text is highlighted.
   //
   // This menu manager caters for two-level menus. Second-level menu items
   // are identified by their name having a prefix being the name of the parent
   // menu item and an underscore.
   class Menu extends DIV
   {
      /////////////////////////////////////////////////////////////////////////
      // Constructor.
      function __construct($head, $menuName)
      {
         parent::__construct("menu");
         $head->addCSS("css/menu.css");

         // Get the current item. If it's not set, choose the home page.
         $curItem = isset($_REQUEST['content']) ? $_REQUEST['content'] : "home";

         // Get the menu.
         $menulist = FN::getMenu($menuName);
         $menuarray = explode("\n", $menulist);

         // Parse the menu to find any submenus.
         $submenu = array();
         foreach ($menuarray as $menuitem)
         {
            $item = explode(",", $menuitem);
            if (count($item) == 2)
            {
               $item = explode("_", $item[1]);
               if (count($item) == 2) $submenu[$item[0]] = TRUE;
            }
         }

         // Parse the menu again and create the HTML.
         $this->add($menu = new UL());
         foreach ($menuarray as $menuitem)
         {
            $item = explode(",", $menuitem);
            if (count($item) > 1)
            {
               $template = $item[0];
               $name = $item[1];
               $text = FN::get($menuName, $name);
               // Check if this is a submenu item .
               $item = explode("_", $name);
               if (count($item) == 2)
               {
                  $menu2->add($this->getMenuItem($template, $name, $text, $curItem));
               }
               else
               {
                  $menu->add($item =
                     $this->getMenuItem($template, $name, $text, $curItem));
                  // If the item has a second-level menu, start it now.
                  if (isset($submenu[$name]))
                  {
                     $cur = $curItem;
                     $cur = explode("_", $cur);
                     $cur = (count($cur) == 2) ? $cur[0] : $curItem;
                     $item->add($menu2 = new UL(NULL,
                        $cur == $name ? "submenu" : "menuhide"));
                  }
               }
            }
         }
      }

      /////////////////////////////////////////////////////////////////////////
      // Get a menu item.
      function getMenuItem($template, $name, $text, $curItem)
      {
         // If the current item matches the menu item, set a style for it.
         // Create a LI object and return it.
         return new LI(NULL, $name == $curItem ? "menuSelect" : "menuNoselect",
            new A(NULL, NULL, $text,
               array(
                  "template"=>$template,
                  "content"=>$name
                  )));
      }
   }
?>
