<?php namespace AmissExt\Type;

class StupidSQLarray implements \Amiss\Type\Handler{

    function prepareValueForDb($value, $object, array $fieldInfo){
        $arr = array_map(function($v){
            return strtr($v, "\x01", "\x02"); # replace the special character (WHY??)
        }, (array)$value);
        return "\x01".implode("\x01", $arr)."\x01";
    }

    function handleValueFromDb($value, $object, array $fieldInfo, $row){
        $arr = array_filter(explode("\x01", $value));
        $arr = array_map(function($v){
            return strtr($v, "\x02", "\x01"); # replace the special character (WHY??)
        }, $arr);
        return $arr;
    }

    function createColumnType($engine){
        return "TEXT";
    }
}
