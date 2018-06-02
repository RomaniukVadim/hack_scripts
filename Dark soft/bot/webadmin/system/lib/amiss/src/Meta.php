<?php

namespace Amiss;

class Meta
{
	public $class;
	public $table;
	public $primary;
	
	/**
	 * Array of relation arrays, hashed by property name
	 * 
	 * Relation arrays *must* contain at least a type at index 0. All other
	 * values in the array are defined by the relator. The meta only cares
	 * about the type.
	 * 
	 * For e.g.
	 * $meta->relations = array(
	 *     // the 'of' and 'on' keys are required by Amiss\Relator\OneMany
	 *     'foo'=>array('one', 'of'=>'Artist', 'on'=>'artistId'),
	 *     
	 *     // the blahblah relator has different ideas
	 *     'bar'=>array('blahblah', 'fee'=>'fi', 'fo'=>'fum'),
	 * );
	 */
	public $relations;
	
	/**
	 * Additional metadata found but not explicitly handled by the mapper
	 */
	public $extra;
	
	protected $fields;
	protected $allFields;
	protected $parent;
	protected $defaultFieldType;
	protected $columnToPropertyMap;
	
	public function __construct($class, $table, array $info, Meta $parent=null)
	{
		$this->class = $class;
		$this->parent = $parent;
		$this->table = $table;
		$this->primary = isset($info['primary']) ? $info['primary'] : array();
		
		if ($this->primary && !is_array($this->primary))
			$this->primary = array($this->primary);
		
		$this->fields = isset($info['fields']) ? $info['fields'] : array();
		$this->extra = isset($info['extra']) ? $info['extra'] : array();
		$this->relations = isset($info['relations']) ? $info['relations'] : array();
		$this->defaultFieldType = isset($info['defaultFieldType']) ? $info['defaultFieldType'] : null;
	}
	
	public function getFields()
	{
		if ($this->allFields===null) {
			$fields = $this->fields;
			
			$current = $this;
			while ($current->parent) {
				$fields = array_merge($current->parent->getFields(), $fields);
				$current = $current->parent;
			}
			
			$this->allFields = $fields ?: array();
		}
		
		return $this->allFields;
	}

	public function getColumnToPropertyMap()
	{
		if ($this->columnToPropertyMap===null) {
			$map = array();
			foreach ($this->getFields() as $prop=>$f) {
				$map[$f['name']] = $prop;
			}
			$this->columnToPropertyMap = $map;
		}
		
		return $this->columnToPropertyMap;
	}
	
	function getField($field)
	{
		if (!$this->allFields)
			$this->getFields();
		
		if (isset($this->allFields[$field])) {
			return $this->allFields[$field];
		}
	}
	
	function getDefaultFieldType()
	{
		if ($this->defaultFieldType===null && $this->parent) {
			$this->defaultFieldType = $this->parent->getDefaultFieldType();
		}
		return $this->defaultFieldType;
	}
	
	function getPrimaryValue($object)
	{
		$foundValue = false;
		
		if (!$this->primary)
			throw new Exception("Class {$this->class} doesn't define primary key(s)");
		
		$prival = array();
		foreach ($this->primary as $p) {
			$field = $this->getField($p);
			$value = !isset($field['getter']) ? $object->{$p} : call_user_func(array($object, $field['getter']));
			if ($value)
				$foundValue = true;
			
			$prival[$p] = $value;
		}
		
		if ($foundValue)
			return $prival;
	}
	
	function getValue($object, $property)
	{
		$field = $this->getField($property);
		$value = !isset($field['getter']) ? $object->{$p} : call_user_func(array($object, $field['getter']));
		return $value;
	}
	
	function __sleep()
	{
		// precache this stuff before serialization
		$this->getFields();
		$this->getDefaultFieldType();
		$this->getColumnToPropertyMap();
		
		return array('class', 'table', 'primary', 'relations', 'fields', 'allFields', 'parent', 'defaultFieldType', 'columnToPropertyMap'); 
	}
}
