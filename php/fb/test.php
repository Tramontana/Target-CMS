<?php

require_once('facebook.php');
require_once('config.php');

$app_id = '145431908857122';
$secret = '399e17aec55ca34c4ded9c96f7a8c957';

$facebook = new Facebook(array(
  'appId'  => $app_id,
  'secret' => $secret,
  'cookie' => true,
));

$session = $facebook->getSession();

$url = 'https://graph.facebook.com/oauth/access_token';
$params=array(
        "type" => "client_cred",
        "client_id" => $app_id,
        "client_secret" => $secret);
$ch = curl_init(); 
curl_setopt($ch, CURLOPT_URL, $url); 
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params, null, '&') );
$data = curl_exec($ch); 
curl_close($ch);
   
$access_token = str_replace('access_token=', '', $data);

$attachment = array('access_token' => $access_token);

$request = $facebook->api("/$app_id/accounts/test-users?installed=false&permissions=", 'POST', $attachment);

echo 'Test user ID: '.$request[id].'<br>';
echo 'Log in as this test user: '.$request[login_url];

?>