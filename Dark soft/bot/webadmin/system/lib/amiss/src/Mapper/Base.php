<?php

namespace Amiss\Mapper;

/**
 * @package Mapper
 */
abstract class Base implements \Amiss\Mapper
{
	public $unnamedPropertyTranslator;
	
	public $defaultTableNameTranslator;
	
	public $convertUnknownTableNames = true;
	
	public $typeHandlers = array();
	
	public $objectNamespace;
	
	private $typeHandlerMap = array();
	
	public function __construct()
	{
		$this->typeHandlers['autoinc'] = new \Amiss\Type\Autoinc;
	}
	
	public function getMeta($class)
	{
		if (!isset($this->meta[$class])) {
			$resolved = $this->resolveObjectname($class);
			$this->meta[$class] = $this->createMeta($resolved);
		}
		return $this->meta[$class];
	}
	
	abstract protected function createMeta($class);
	
	public function addTypeHandler($handler, $types)
	{
		if (!is_array($types)) $types = array($types);
		
		foreach ($types as $type) {
			$type = strtolower($type);
			$this->typeHandlers[$type] = $handler;
		}
	}
	
	public function createObject($meta, $row, $args=null)
	{
		$object = null;
		if ($args) {
			$rc = new \ReflectionClass($meta->class);
			$object = $rc->newInstanceArgs($args);
		}
		else {
			$cname = $meta->class;
			$object = new $cname;
		}
		return $object;
	}
	
	public function populateObject($meta, $object, $row)
	{
		$defaultType = $meta->getDefaultFieldType();
		
		$fields = $meta->getFields();
		$map = $meta->getColumnToPropertyMap();
		foreach ($row as $col=>$value) {
			if (!isset($map[$col]))
				continue; // throw exception?
			
			$prop = $map[$col];
			$field = $fields[$prop];
			$type = $field['type'] ?: $defaultType;
			
			if ($type) {
				if (!isset($this->typeHandlerMap[$type])) {
					$this->typeHandlerMap[$type] = $this->determineTypeHandler($type);
				}
				if ($this->typeHandlerMap[$type]) {
					$value = $this->typeHandlerMap[$type]->handleValueFromDb($value, $object, $field, $row);
				}
			}
			
			if (!isset($field['setter']))
				$object->{$prop} = $value;
			else
				call_user_func(array($object, $field['setter']), $value);
		}
	}
	
	public function exportRow($meta, $object)
	{
		$row = array();
		
		$defaultType = $meta->getDefaultFieldType();
		
		foreach ($meta->getFields() as $prop=>$field) {
			if (!isset($field['getter']))
				$value = $object->$prop;
			else
				$value = call_user_func(array($object, $field['getter']));
			
			$type = $field['type'] ?: $defaultType;
			
			if ($type) {
				if (!isset($this->typeHandlerMap[$type])) {
					$this->typeHandlerMap[$type] = $this->determineTypeHandler($type);
				}
				if ($this->typeHandlerMap[$type]) {
					$value = $this->typeHandlerMap[$type]->prepareValueForDb($value, $object, $field);
				}
			}
			
			// don't allow array_merging. it breaks mongo compatibility and is pretty 
			// confused anyway.
			$row[$field['name']] = $value;
		}
		
		return $row;
	}
	
	public function determineTypeHandler($type)
	{
		// this splits off any extra crap that you may have defined
		// in the field's definition, i.e. "varchar(80) not null etc etc"
		// becomes "varchar"
		$x = preg_split('@[^A-z0-9\-\_]@', trim($type), 2);
		$id = strtolower($x[0]);
		
		return isset($this->typeHandlers[$id]) ? $this->typeHandlers[$id] : false;
	}
	
	/**
	 * Assumes that any name that contains a backslash is already resolved.
	 * This allows you to use fully qualified class names that are outside
	 * the mapped namespace.
	 */
	protected function resolveObjectName($name)
	{
		return ($this->objectNamespace && strpos($name, '\\')===false ? $this->objectNamespace . '\\' : '').$name;
	}
	
	protected function getDefaultTable($class)
	{
		$table = null;
		if ($this->defaultTableNameTranslator) {
			if ($this->defaultTableNameTranslator instanceof \Amiss\Name\Translator) 
				$table = $this->defaultTableNameTranslator->translate($class);
			else
				$table = call_user_func($this->defaultTableNameTranslator, $class);
		}
		
		if ($table === null) {
			$table = $class;
			if ($this->convertUnknownTableNames) {
				$table = '`'.$this->convertUnknownTableName($class).'`';
			}
		}
		
		return $table;
	}
	
	public function convertUnknownTableName($class)
	{
		$table = $class;
		
		if ($pos = strrpos($table, '\\')) $table = substr($table, $pos+1);
				
		$table = trim(preg_replace_callback('/[A-Z]/', function($match) {
			return "_".strtolower($match[0]);
		}, str_replace('_', '', $table)), '_');
		
		return $table;
	}
	
	protected function resolveUnnamedFields($fields)
	{
		$unnamed = array();
		foreach ($fields as $prop=>$f) {
			if (!isset($f['name']) || !$f['name']) $unnamed[$prop] = $prop;
		}
		
		if ($unnamed) {
			if ($this->unnamedPropertyTranslator)
				$unnamed = $this->unnamedPropertyTranslator->translate($unnamed);
			
			foreach ($unnamed as $name=>$field) {
				$fields[$name]['name'] = $field;
			}
		}
		
		return $fields;
	}
	
	public static function normaliseCache($cache)
	{
		if (is_object($cache)) {
			$cache = array(
				function($key) use ($cache) { return $cache->get($key); },
				function($key, $value) use ($cache) { return $cache->set($key, $value); },
			);
		}
		elseif ($cache == 'apc') {
			$cache = array(
				function($key) use ($cache) { return apc_fetch($key); },
				function($key, $value) use ($cache) { return apc_store($key, $value, 86400); },
			);
		}
		return $cache;
	}
}
