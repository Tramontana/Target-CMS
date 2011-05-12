<?php
   ////////////////////////////////////////////////////////////////////////////
   // The Contact us page.
   class Contact extends DIV
   {
      /////////////////////////////////////////////////////////////////////////
      // Do the Contact Us web form.
      public static function doContact($template, $content,
         $prompt = NULL, $name = NULL, $email = NULL, $message = NULL)
      {
         if (isset($_REQUEST['send']))
            return self::sendMessage($template, $content);
         else
         {
            $div = new DIV(NULL, NULL, FN::get("contact", "contact"));
            $div->add(new HR());
            $div->add(new DIV());

            $e = base64_encode(pack("h*", sha1(mt_rand())));
            $captcha = str_replace('l', '2', str_replace('O', '3', 
               str_replace('0', '4', str_replace('1', '5', str_replace('o', '6', 
               str_replace('I', '7', substr(strtr($e, "+/=", "xyz"), 0, 4)))))));
            $_SESSION['captcha'] = $captcha;
            $source = "captcha/captcha.php?captcha=$captcha";
            
            $div->add($form = new FORM(NULL, NULL, "contact"));
   
            $form->add($table = new TABLE("contact"));
            if ($prompt)
            {
               $table->addRow($tr = new TR());
               $tr->addCell();
               $tr->addCell($td = new TD("prompt", NULL, $prompt));
            }
            
            $table->addRow($tr = new TR());
            $tr->addCell(new TD(NULL, "label",
               new LABEL(NULL, NULL, FN::get("contact", "namelabel"), "name")));
            $tr->addCell(new TD(NULL, "tatd",
               new TEXT(NULL, NULL, "name", $name)));
   
            $table->addRow($tr = new TR());
            $tr->addCell(new TD(NULL, "label",
               new LABEL(NULL, NULL, FN::get("contact", "emaillabel"), "email")));
            $tr->addCell(new TD(NULL, "tatd",
               new TEXT(NULL, NULL, "email", $email)));
   
            $table->addRow($tr = new TR());
            $tr->addCell(new TD(NULL, "label",
               new LABEL(NULL, NULL, FN::get("contact", "messagelabel"), "message")));
            $tr->addCell(new TD(NULL, "tatd",
               new TEXTAREA(NULL, NULL, "message", $message)));
               
            $table->addRow($tr = new TR());
            $tr->addCell();
            $tr->addCell($td = new TD());
            $td->add($code = new DIV("code", NULL,
            	FN::getPlain("contact", "captcha")));
            $code->add(new TEXT(NULL, NULL, "captcha"));
            $td->add(new DIV("image", NULL,
               new IMG(NULL, NULL, $source, "CAPTCHA image",
                  array("width"=>140, "height"=>90))));
               
            $table->addRow($tr = new TR());
            $tr->addCell();
            $tr->addCell($td = new TD());
            $td->add(new HIDDEN("template", $template));
            $td->add(new HIDDEN("content", "contact"));
            $td->add(new SUBMIT(NULL, NULL, "send",
               FN::getPlain("contact", "sendMessage")));
            
            return $div;
         }
      }

      ////////////////////////////////////////////////////////////////////////////
      // Here when a user sends us a message from the web form.
      private static function sendMessage($template, $content)
      {
         $name = $_REQUEST['name'];
         $email = $_REQUEST['email'];
         $message = stripslashes($_REQUEST['message']);
         if (!$name || !$email || !$message)
         {
         	unset($_REQUEST['send']);
	         return self::doContact($template, $content,
	         	FN::get("contact", "allfields"), $name, $email, $message);
         }
         require_once "captcha/captcha.php";
         if (captcha_validate())
         {
            $ipaddr = $_SERVER['REMOTE_ADDR'];
            $to = FN::getPlain("contact", "email");
            $subject = FN::getPlain("contact", "subject");
            $body = "Name: ".$name."\nEmail: ".$email."\nIP: ".$ipaddr."\n\n"
               .$message;
            $headers = "From: ".$email."\n";
            mail($to,$subject,$body,$headers);
            return new DIV(NULL, NULL, FN::get("contact", "messageSent"));
         }
         unset($_REQUEST['send']);
         return self::doContact($template, $content, FN::get("contact", "nomatch"),
            $name, $email, $message);
      }
   }
?>
