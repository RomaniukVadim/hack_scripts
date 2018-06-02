<?php

namespace Amiss\Relator;

use Amiss\Criteria;

class OneMany extends Base
{
	public function getRelated($source, $relationName, $criteria=null)
	{
		if (!$source) return;
		
		$sourceIsArray = is_array($source) || $source instanceof \Traversable;
		if (!$sourceIsArray) $source = array($source);
		
		$class = !is_object($source[0]) ? $source[0] : get_class($source[0]);
		$meta = $this->manager->getMeta($class);
		if (!isset($meta->relations[$relationName])) {
			throw new \Amiss\Exception("Unknown relation $relationName on $class");
		}
		
		$relation = $meta->relations[$relationName];
		$type = $relation[0];
		
		if ($type != 'one' && $type != 'many')
			throw new \InvalidArgumentException("This relator only works with 'one' or 'many' as the type");
		
		if ($type == 'one' && $criteria)
			throw new \InvalidArgumentException("There's no point passing criteria for a one-to-one relation.");
		
		$relatedMeta = $this->manager->getMeta($relation['of']);
		
		// prepare the relation's "on" field
		$on = null;
		if (isset($relation['on'])) {
			$on = $relation['on'];
		}
		else {
			if ('one'==$type)
				throw new \Amiss\Exception("One-to-one relation {$relationName} on class {$class} does not declare 'on' field");
			else {
				$on = array();
				foreach ($meta->primary as $p) {
					$on[$p] = $p;
				}
			}
		}
		
		$relatedFields = $relatedMeta->getFields();
		
		if (!is_array($on)) {
			if ($type == 'one' && count($relatedMeta->primary)==1)
				$rOn = $relatedMeta->primary[0];
			else $rOn = $on;
			
			$on = array($on=>$rOn);
		}
		
		// find query values in source object(s)
		$fields = $meta->getFields();
		
		list($ids, $resultIndex) = $this->indexSource($source, $on, $fields, $relatedFields);
		
		// build query
		$query = new Criteria\Select;
		$where = array();
		foreach ($ids as $l=>$idInfo) {
			$rName = $idInfo['rField']['name'];
			$query->params['r_'.$rName] = array_keys($idInfo['values']);
			$where[] = '`'.str_replace('`', '', $rName).'` IN(:r_'.$idInfo['param'].')';
		}
		$query->where = implode(' AND ', $where);
		
		if ($criteria) {
			list ($cWhere, $cParams) = $criteria->buildClause($relatedMeta);
			$query->params = array_merge($cParams, $query->params);
			$query->where .= ' AND ('.$cWhere.')';
		}
		
		$list = $this->manager->getList($relation['of'], $query);
		
		// prepare the result
		$result = null;
		if (!$sourceIsArray) {
			if ($type == 'one') {
				if ($list) $result = current($list);
			}
			else {
				$result = $list;
			}
		}
		else {
			$result = array();
			foreach ($list as $related) {
				$key = array();
				
				foreach ($on as $l=>$r) {
					$rField = $relatedFields[$r];
					$name = $rField['name'];
					$rValue = !isset($rField['getter']) ? $related->$name : call_user_func(array($related, $rField['getter']));
					$key[] = $rValue;
				}
				$key = !isset($key[1]) ? $key[0] : implode('|', $key);
				
				foreach ($resultIndex[$key] as $idx=>$lObj) {
					if ('one' == $type) {
						$result[$idx] = $related;
					}
					elseif ('many' == $type) {
						if (!isset($result[$idx])) $result[$idx] = array();
						$result[$idx][] = $related;
					}
				}
			}
		}
		
		return $result;
	}
}
