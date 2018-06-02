<?php

namespace Amiss\Relator;

abstract class Base implements \Amiss\Relator
{
	public function __construct($manager)
	{
		$this->manager = $manager;
	}
	
	protected function indexSource($source, $on, $lFields, $rFields)
	{
		$resultIndex = array();
		$ids = array();
		foreach ($source as $idx=>$object) {
			$key = array();
			foreach ($on as $l=>$r) {
				$lField = $lFields[$l];
				$lValue = !isset($lField['getter']) ? $object->$l : call_user_func(array($object, $lField['getter']));
				
				$key[] = $lValue;
				
				if (!isset($rFields[$r]))
					throw new \Exception("Field $r does not exist against relation for ".get_class($object));
				
				if (!isset($ids[$l])) {
					$ids[$l] = array(
						'values'=>array(), 
						'rField'=>$rFields[$r], 
						'param'=>preg_replace('/[^A-z0-9_]/', '', $rFields[$r]['name'])
					);
				}
				
				$ids[$l]['values'][$lValue] = true;
			}
			
			$key = !isset($key[1]) ? $key[0] : implode('|', $key);
			
			if (!isset($resultIndex[$key]))
				$resultIndex[$key] = array();
			
			$resultIndex[$key][$idx] = $object;
		}
		
		return array($ids, $resultIndex);
	}
}
