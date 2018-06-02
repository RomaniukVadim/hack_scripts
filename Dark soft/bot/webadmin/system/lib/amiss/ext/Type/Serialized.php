<?php namespace AmissExt\Type;

class Serialized implements \Amiss\Type\Handler{

    function prepareValueForDb($value, $object, array $fieldInfo){
        return serialize($value);
    }

    function handleValueFromDb($value, $object, array $fieldInfo, $row){
        return unserialize($value);
    }

    function createColumnType($engine){
        return "BLOB";
    }
}
