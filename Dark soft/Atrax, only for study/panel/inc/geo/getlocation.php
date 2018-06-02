<?php
  include('inc/geo/geoipcity.inc');
  
  $gi = geoip_open('inc/geo/geolitecity.dat',GEOIP_STANDARD);
  $record = geoip_record_by_addr($gi, $_SERVER['REMOTE_ADDR']);
  $code = $record->country_code;
  $name = $record->country_name;
  $region = $GEOIP_REGION_NAME[$record->country_code][$record->region];
  $city = $record->city;
  $code = empty($code) ? '00' : strtolower($code);
  $name = empty($name) ? 'Unknown' : $name;
  $region = empty($region) ? 'Unknown' : $region;
  $city = empty($city) ? 'Unknown' : $city;
  geoip_close($gi);