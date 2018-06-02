<?php namespace AmissExt\Type;

use Amiss\Type\Date;

class IntTimestamp extends Date {
    function prepareValueForDb($value, $object, array $fieldInfo) {
        $str = parent::prepareValueForDb($value, $object, $fieldInfo);
        $int = strtotime($str);
        return $int;
    }

    function handleValueFromDb($value, $object, array $fieldInfo, $row) {
        $value = date('Y-m-d'.($this->withTime? ' H:i:s' : ''), $value);
        return parent::handleValueFromDb($value, $object, $fieldInfo, $row);
    }

    function createColumnType($engine) {
        return 'int unsigned not null';
    }
}
