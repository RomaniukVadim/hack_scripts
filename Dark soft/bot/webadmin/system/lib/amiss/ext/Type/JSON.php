<?php namespace AmissExt\Type;

class JSON implements \Amiss\Type\Handler{

    function prepareValueForDb($value, $object, array $fieldInfo){
        return json_encode($value);
    }

    function handleValueFromDb($value, $object, array $fieldInfo, $row){
        return json_decode($value);
    }

    function createColumnType($engine){
        return "BLOB";
    }
}
