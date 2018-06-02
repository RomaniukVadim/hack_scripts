<?php

/** Response parsing: http://www.iplocation.net/
 * @example:
 *      $data = file_get_contents('http://www.iplocation.net/index.php?query='.$ip);
 *      $whois = geolocation_parse($data);
 *
 * @return array array(geoloc, hostname) || array(geoloc, null) || array( "E: error string", null)
 */
function geolocation_parse($response){
    $data = null;

    // Parse
    $p1 = strpos($response, '>Your IP Address');
    if ($p1 === FALSE)
        $p1 = strpos($response, '<form name="lookup"');

    $p2 = strpos($response, '<tr class="error">');

    if ($p1 !== FALSE && $p2 !== FALSE)
        $data = substr($response, $p1, $p2-$p1);

    # Check anchor presence
    if (is_null($data)){
        return array('E: Parse error #1', null);
    }

    # Parse the GEOdata
    $p1 = strpos($data, "<td width='80'>");
    $p2 = strpos($data, "<td", $p1+1);
    $p3 = strpos($data, '</tr', $p1+1);
    if (FALSE === $p1 || FALSE === $p2 || FALSE === $p3)
        return array('E: Parse error #2', null);
    $chunk = substr($data, $p2, $p3-$p2 );

    $geoloc = preg_replace('~(\s*<[^>]+>\s*)+~iS', ' , ', $chunk);
    $geoloc = trim($geoloc, " \t,");
    $hostname = null; # not provided
    return array($geoloc, $hostname);
}
