<?php

namespace Amiss\Criteria;

/**
 * @package Criteria
 */
class Select extends Query
{
	public $args=array();
	public $page;
	public $limit;
	public $offset=0;
	public $fields;
	public $order=array();
	
	public function getLimitOffset()
	{
		if ($this->limit) 
			return array($this->limit, $this->offset);
		else {
			return array($this->page[1], ($this->page[0] - 1) * $this->page[1]); 
		}
	}

	public function buildQuery($meta)
	{
		$table = $meta->table;
		
		list ($where, $params) = $this->buildClause($meta);
		$order = $this->buildOrder($meta);
		list ($limit, $offset) = $this->getLimitOffset();
		
		$query = "SELECT ".$this->buildFields($meta)." FROM $table "
			.($where  ? "WHERE $where "            : '').' '
			.($order  ? "ORDER BY $order "         : '').' '
			.($limit  ? "LIMIT  ".(int)$limit." "  : '').' '
			.($offset ? "OFFSET ".(int)$offset." " : '').' '
		;
		
		return array($query, $params);
	}
	
	public function buildFields($meta, $prefix=null)
	{
		$metaFields = $meta ? $meta->getFields() : null;
		
		$fields = $this->fields;
		if (!$fields) {
			$fields = $metaFields ? array_keys($metaFields) : '*';
		}
		
		if (is_array($fields)) {
			$fNames = array();
			foreach ($fields as $f) {
				$name = (isset($metaFields[$f]) ? $metaFields[$f]['name'] : $f);
				$fNames[] = ($prefix ? $prefix.'.' : '').'`'.$name.'`';
			}
			$fields = implode(', ', $fNames);
		}
		
		return $fields;
	}
	
	// damn, this is pretty much identical to the above.
	public function buildOrder($meta)
	{
		$metaFields = $meta ? $meta->getFields() : null;
		
		$order = $this->order;
		
		if ($order) {
			if (is_array($order)) {
				$oClauses = array();
				foreach ($order as $field=>$dir) {
					if (is_numeric($field)) {
						$field = $dir; $dir = 'asc';
					}
					
					$name = (isset($metaFields[$field]) ? $metaFields[$field]['name'] : $field);
					$oClauses[] = '`'.$name.'`'.($dir == 'asc' ? '' : ' desc');
				}
				$order = implode(', ', $oClauses);
			}
			else {
				if ($metaFields && strpos($order, '{')!==false)
					$order = $this->replaceFieldTokens($metaFields, $order);
			}
		}
		
		return $order;
	}
}
