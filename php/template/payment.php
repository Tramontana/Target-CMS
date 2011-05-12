<?php
	///////////////////////////////////////////////////////////////////////////
	// This is the payment page.
	class Payment extends BLOCK
	{
		///////////////////////////////////////////////////////////////////////
		// Get the payment button.
		static function getPaymentButton()
		{
			$item = $_REQUEST["item"];
			$amount = $_REQUEST["amount"];
			$div = new DIV("payment", NULL, FN::replace(FN::get("customer", "paynow"), array(
				"/AMOUNT/"=>$amount
				)));
			$div->add($form = new FORM(NULL, NULL, NULL,
				"https://www.paypal.com/cgi-bin/webscr", NULL, TRUE));
			$form->add(new HIDDEN("cmd", "_xclick"));
			$form->add(new HIDDEN("business", "accounts@targetcms.com"));
			$form->add(new HIDDEN("lc", "GB"));
			$form->add(new HIDDEN("item_name", $item));
			$form->add(new HIDDEN("amount", $amount));
			$form->add(new HIDDEN("currency_code", "EUR"));
			$form->add(new HIDDEN("button_subtype", "products"));
			$form->add(new HIDDEN("bn", "PP-BuyNowBF:btn_buynowCC_LG.gif:NonHostedGuest"));
			$form->add(new HIDDEN("image_url", "http://targetcms.com/upload/ppbanner.jpg"));
			$form->add(new IMAGE(NULL, NULL, "submit",
				array("border"=>0,
					"src"=>"https://www.paypal.com/en_GB/i/btn/btn_paynow_LG.gif",
					"alt"=>"PayPal - The safer, easier way to pay online!")));
			$form->add(new IMG(NULL, NULL, "https://www.paypal.com/en_GB/i/scr/pixel.gif",
				NULL, array("border"=>0, "width"=>1, "height"=>1)));
			return $div;
		}
	}
?>
