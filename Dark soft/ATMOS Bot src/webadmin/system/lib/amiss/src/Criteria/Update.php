<?php

namespace Amiss\Criteria;

/**
 * @package Criteria
 */
class Update extends Query
{
	public $set=array();
	
	public function buildSet($meta)
	{
		$params = array();
		$clause = array();
		
		$fields = $meta ? $meta->getFields() : null;
		$named = $this->paramsAreNamed();
		
		foreach ($this->set as $name=>$value) {
			if (is_numeric($name)) {
				// this allows arrays of manual "set"s, i.e. array('foo=foo+10', 'bar=baz')
				$clause[] = $value;
			}
			else {
				$field = (isset($fields[$name]) ? $fields[$name]['name'] : $name);
				
				if ($named) {
					$param = ':set_'.$name;
					$clause[] = '`'.$field.'`='.$param;
					$params[$param] = $value;
				}
				else {
					$clause[] = '`'.$field.'`=?';
					$params[] = $value;
				}
			}
		}
		
		$clause = implode(', ', $clause);
		
		return array($clause, $params);
	}
	
	public function buildQuery($meta)
	{
		$table = $meta->table;
		
		list ($setClause,   $setParams)   = $this->buildSet($meta);
		list ($whereClause, $whereParams) = $this->buildClause($meta);
		
		$params = array_merge($setParams, $whereParams);
		if (count($params) != count($setParams) + count($whereParams)) {
			$intersection = array_intersect(array_keys($setParams), array_keys($whereParams));
			throw new Exception("Param overlap between set and where clause. Duplicated keys: ".implode(', ', $intersection));
		}
		
		if (!$whereClause)
			throw new \InvalidArgumentException("No where clause specified for table update. Explicitly specify 1=1 as the clause if you meant to do this.");
		
		$sql = "UPDATE $table SET $setClause WHERE $whereClause";
		
		return array($sql, $params);
	}
}
