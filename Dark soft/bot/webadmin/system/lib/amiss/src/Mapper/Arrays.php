<?php

namespace Amiss\Mapper;

/**
 * @package Mapper
 */
class Arrays extends Base
{
	public $arrayMap;
	public $inherit = false;
	public $defaultPrimaryType = 'autoinc';
	
	public function __construct($arrayMap=array())
	{
		parent::__construct();
		
		$this->arrayMap = $arrayMap;
	}
	
	protected function createMeta($class)
	{
		if (!isset($this->arrayMap[$class]))
			throw new \InvalidArgumentException("Unknown class $class");
		
		$array = $this->arrayMap[$class];
		$parent = null;
		if ($this->inherit) {
			$parentClass = get_parent_class($class);
			if ($parentClass) $parent = $this->getMeta($parentClass);
		}
		
		$table = null;
		if (isset($array['table']))
			$table = $array['table'];
		else
			$table = $this->getDefaultTable($class);
		
		$fields = array();
		if (isset($array['fields'])) {
			foreach ($array['fields'] as $id=>$field) {
				if ( !($id == 0 && $id !== 0)) { // it's a numeric index, not a string
					$id = $field; 
					$field = array();
				}
				if (!isset($field['type'])) $field['type'] = null;
				$fields[$id] = $field;
			}
		}
		$array['fields'] = $fields;
		
		if (isset($array['primary'])) {
			if (!is_array($array['primary']))
				$array['primary'] = array($array['primary']);
			
			foreach ($array['primary'] as $v) {
				if (!isset($array['fields'][$v]) || !isset($array['fields'][$v]['type']))
					$array['fields'][$v] = array('type'=>$this->defaultPrimaryType);
			}
		}
		
		$array['fields'] = $this->resolveUnnamedFields($array['fields']);
		
		$meta = new \Amiss\Meta($class, $table, $array, $parent);
		
		return $meta;
	}
}
