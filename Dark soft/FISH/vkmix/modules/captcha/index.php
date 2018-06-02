<?php
$root = $_SERVER['DOCUMENT_ROOT'];
$page_name = 'secure';
 
require($root.'/inc/classes/db.php');
include($root.'/inc/system/redis.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
include($root.'/inc/system/profile.php');
require($root.'/inc/classes/sessions.php');

// функция создания линии
function imageLineThick($image, $x1, $y1, $x2, $y2, $color, $thick = 1) {
 if($thick == 1) return imageline($image, $x1, $y1, $x2, $y2, $color);

 $t = $thick / 2 - 0.5;

 $k = ($y2 - $y1) / ($x2 - $x1);
 $a = $t / sqrt(1 + pow($k, 2));
 $points = array(
 round($x1 - (1+$k)*$a), round($y1 + (1-$k)*$a),
 round($x1 - (1-$k)*$a), round($y1 - (1+$k)*$a),
 round($x2 + (1+$k)*$a), round($y2 - (1-$k)*$a),
 round($x2 + (1-$k)*$a), round($y2 + (1+$k)*$a),
 );
 imagefilledpolygon($image, $points, 4, $color);
 return imagepolygon($image, $points, 4, $color);
}

// параметры
$width = 130; // ширина
$height = 51; // высота
$fontsize = 14; // размер шрифта
$symbols = '1234567890ABCDEFGKIJKLMNOPQRSTUVWXYZabcdefgkijklmnopqrstuvwxyz'; // символы
$caplen = 6; // количество символов для кода
$font = 'font/tahoma/tahoma.ttf'; // путь к шрифту
$captcha_key = $_GET['captcha_key'];

//создание изображения
$im = imagecreatetruecolor($width, $height);
$white = imagecolorallocate($im, 255, 255, 255);
$bg = imagecolorallocate($im, 233, 236, 241);
$colorLine = imagecolorallocate($im, 199, 205, 216);
imagefilledrectangle($im, 0, 0, 200, 51, $bg);
imageLineThick($im, 0, rand(0, 51), 270, rand(0, 51), $colorLine, 5);

for($i = 0; $i < $caplen; $i++) {
 $captcha_code .= $symbols[rand(0, mb_strlen($symbols, 'UTF-8') - 1)];
 $x = ($width - 20) / $caplen * $i + 10;
 $x = rand($x, $x + rand(5, 15));
 $y = $height - (($height - $fontsize) / 2);
 $angle = rand(-10, 40);
 imagettftext($im, 21, $angle, $x + 2, $y + 2, $white, $font, $captcha_code[$i]);
 imagettftext($im, 21, $angle, $x, $y, imagecolorallocate($im, rand(70, 108), 131, rand(100, 155)), $font, $captcha_code[$i]);
}

// сессия
if($captcha_key) {
 $captcha_code_get = $session->get('captcha_code', $captcha_key);
 if(!$captcha_code_get) {
  $session->add('captcha_code', $captcha_code, $captcha_key);
 }
}
 	
//prevent caching on client side:
header("Expires: Wed, 1 Jan 1997 00:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
 
header("Content-type: image/jpeg");
imagejpeg($im);
imagedestroy($im);
?>