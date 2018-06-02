<?php

$base = dirname(__FILE__);
require_once($base.'/../inc/require.php');

	$length = 8;
$string = '';
  $characters = "0123456789ABCDEFGHIJKLMNOPQRSTUVWZYZ".
        "abcdefghijklmnopqrstuvwxyz";
  $real_string_length = strlen($characters) - 1;
  for($p=0;$p<$length;$p++)
  { $string .= $characters[mt_rand(0, $real_string_length)]; }
  

  
//add to session array so other scripts can access it for the comparison
$_SESSION['image_text']= $string;

	$img=imagecreatefromjpeg("images/texture.jpg");
 
        $red=rand(100,255);
	$green=rand(100,255);
	$blue=rand(100,255);
 
	$text_color=imagecolorallocate($img,255-$red,255-$green,255-$blue);
 
	$text=imagettftext($img, 16, rand(-10,10), rand(10,30), rand(25,35), $text_color,
                 $base."/fonts/courbd.ttf", $string);

	header("Content-type:image/jpeg");
	header("Content-Disposition:inline ; filename=secure.jpg");
	imagejpeg($img);
	
	?>