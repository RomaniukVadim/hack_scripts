<?php namespace AmissExt\Type;

class ReportReference implements \Amiss\Type\Handler{

    function prepareValueForDb($value, $object, array $fieldInfo){
        if (is_array($value))
            return implode(':', $value);
        return $value;
    }

    function handleValueFromDb($value, $object, array $fieldInfo, $row){
        return is_null($value)? null : explode(':', $value);
    }

    function createColumnType($engine){
        return "VARCHAR(20)";
    }
}
