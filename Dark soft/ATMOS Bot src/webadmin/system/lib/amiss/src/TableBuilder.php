<?php

namespace Amiss;

use Amiss\Exception,
	Amiss\Connector
;

class TableBuilder
{
	/**
	 * @var Amiss\Meta
	 */
	private $meta;
	
	/**
	 * @var Amiss\Manager
	 */
	private $manager;
	
	private $class;
	
	public function __construct($manager, $class)
	{
		$this->manager = $manager;
		$this->class = $class;
		$this->meta = $manager->getMeta($class);
	}
	
	public function getClass()
	{
		return $this->meta->class;
	}
	
	public function createTable()
	{
		$connector = $this->manager->getConnector();
		
		if (!($connector instanceof Connector))
			throw new Exception("Can't create tables if not using Amiss\Connector");
		
		$sql = $this->buildCreateTableSql();
		
		$connector->exec($sql);
	}
	
	protected function buildFields()
	{
		$engine = $this->manager->getConnector()->engine;
		$primary = $this->meta->primary;
		
		$default = $this->meta->getDefaultFieldType();
		if (!$default) {
			$default = $engine == 'sqlite' ? 'STRING NULL' : 'VARCHAR(255) NULL';
		} 
		$f = array();
		$found = array();
		
		$fields = $this->meta->getFields();
		
		// make sure the primary key ends up first
		if ($this->meta->primary) {
			$pFields = array();
			foreach ($this->meta->primary as $p) {
				$primaryField = $fields[$p];
				unset($fields[$p]);
				$pFields[$p] = $primaryField;
			}
			$fields = array_merge($pFields, $fields);
		}
		
		foreach ($fields as $id=>$info) {
			$current = "`{$info['name']}` ";
			
			$type = null;
			if ($info['type'])
				$type = $info['type'];
			else
				$type = $default;
			
			$handler = $this->manager->mapper->determineTypeHandler($type);
			if ($handler) {
				$new = $handler->createColumnType($engine);
				if ($new) $type = $new;
			}
			
			$current .= $type;
			$f[] = $current;
			$found[] = $id;
		}
		
		return $f;
	}
	
	protected function buildTableConstraints()
	{
		$engine = $this->manager->getConnector()->engine;
		
		$idx = array();
		if ($engine == 'mysql') {
			foreach ($this->meta->relations as $k=>$details) {
				if ($details[0] == 'one' || $details[0] == 'many') {
					$cols = array();
					if (is_string($details['on'])) {
						$cols[] = $details['on'];
					}
					elseif ($details['on']) {
						foreach ($details['on'] as $l=>$r) {
							if (is_numeric($l)) $l = $r;
							$cols[] = $l;
						}
					}
					if ($details[0] == 'one')
						$idx[] = "KEY `idx_$k` (`".implode('`, `', $cols).'`)';
				}
			}
		}
		return $idx;
	}
	
	public function buildCreateTableSql()
	{
		$fields = $this->meta->getFields();
		
		if (!$fields)
			throw new Exception("Tried to create table for object {$this->meta->class} but it doesn't declare fields");
		
		$table = '`'.str_replace('`', '', $this->meta->table).'`';
		$connector = $this->manager->getConnector();
		$engine = $connector->engine;
		
		$primary = $this->meta->primary;
		$fields = static::buildFields();
		if (is_array($fields))
			$fields = implode(",\n  ", $fields);
		
		$query = "CREATE TABLE $table (\n  ";
		$query .= $fields;
		
		$indexes = $this->buildTableConstraints();
		if ($indexes) {
			$query .= ",\n  ".implode(",\n  ", $indexes);
		}
		
		$query .= "\n)";
		if ($engine == 'mysql') {
			$query .= ' ENGINE=InnoDB';
		}
		
		return $query;
	}
}
