<?php
header('Content-type: text/plain');

$json = array(
 array(
 'country' => 'RU', 
 'country_name' => 'Россия',
 'providers' => array(
  array('code' => 'beeline', 'name' => 'Билайн'),
  array('code' => 'megafon', 'name' => 'Мегафон'),
  array('code' => 'mts', 'name' => 'МТС'),
  array('code' => 'akos', 'name' => 'АКОС'),
  array('code' => 'motiv', 'name' => 'МОТИВ'),
  array('code' => 'skylink', 'name' => 'SkyLink')
 )),
 array(
 'country' => 'UA', 
 'country_name' => 'Украина',
 'providers' => array(
  array('code' => 'kyivstar', 'name' => 'Kyivstar'),
  array('code' => '3mob', 'name' => '3mob'),
  array('code' => 'life', 'name' => 'Life'),
  array('code' => 'mts', 'name' => 'MTS')
 )),
 array(
 'country' => 'BY', 
 'country_name' => 'Беларусь',
 'providers' => array(
 
 )),
);

echo json_encode($json);
?>