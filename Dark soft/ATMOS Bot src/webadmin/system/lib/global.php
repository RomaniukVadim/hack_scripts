<?php
/** Compile a wildcard to RegExp: #^...$#iS
 * wildcart => RegExp processor
 * - "*" => ".*"
 * - "?" => "."
 * - "*." => ".*\.?" - special subdomain matching technique
 * @param $wildcart
 * @return string
 */
function wildcart($wildcart){
    return '#^'.wildcart_body($wildcart, '#').'$#iS';
}

/** Wildcard to RegExp, withouth those stupid PHP delimiters
 * @param string $wildcart
 * @param string|null $delimiter
 * @return mixed
 */
function wildcart_body($wildcart, $delimiter = null){
    return str_replace(
        array('\\*\\.', '\\?', '\\*'),
        array('.*\\.?', '.', '.*'),
        preg_quote($wildcart, $delimiter)
    );
}

/** Compile an array of wildcards to a single RegExp: #^...|...|...$#iS
 * @param string[] $wildcards
 * @return string|null
 */
function wildcarts_or($wildcards){
    $rex = wildcarts_or_body($wildcards);
    if (!$rex) return null;
    return '#^(?:'.$rex.')$#iS';
}

/** Create a single OR'ed RegExp from an array of wildcard strings
 * @param string[] $wildcards
 * @return string|null
 */
function wildcarts_or_body($wildcards, $delimiter = null){
    $rex = array();
    foreach (array_filter(array_map('trim', $wildcards)) as $w)
        $rex[] = wildcart_body($w, $delimiter);
    return $rex? implode('|', $rex) : null;
}

/** Walk through $input and collect keys | properties of the underlying structures
 * @param string $key
 *      The key to gey
 * @param (array|object)[] $input
 *      The data source: an array of assoc arrays or objects
 * @return mixed[]
 */
function array_pluck($key, $input) {
    if (is_array($key) || !is_array($input))
        return array();
    $values = array();
    foreach($input as $v) {
        if(is_array($v) && array_key_exists($key, $v))
            $values[] = $v[$key];
        elseif (is_object($v) && property_exists($v, $key))
            $values[] = $v->$key;
    }
    return $values;
}
