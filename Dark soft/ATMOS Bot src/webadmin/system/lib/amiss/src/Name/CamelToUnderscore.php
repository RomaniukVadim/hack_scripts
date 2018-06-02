<?php

namespace Amiss\Name;

class CamelToUnderscore implements Translator
{
	public $strict = true;
	
	function translate(array $names)
	{
		$trans = array();
		
		foreach ($names as $name) {
			if ($this->strict) {
				$pos = strpos($name, '_');
				
				// it's ok if the property has a leading underscore - it will be stripped
				if ($pos !== false && $pos > 0) {
					throw new \InvalidArgumentException("Property $name contains underscores - this will not successfully map bi-directionally. If you insist on using this name, you should declare the field name explicitly.");
				}
			}
			
			$trans[$name] = trim(
				strtolower(preg_replace_callback(
					'/[A-Z]/', 
					function($match) {
						return '_'.$match[0];
					}, 
					$name
				)), 
				'_'
			);
		}
		
		return $trans;
	}
	
	function untranslate(array $names)
	{
		$trans = array();
		
		foreach ($names as $name) {
			$trans[$name] = trim(
				preg_replace_callback(
					'/_(.)/', 
					function($match) {
						return strtoupper($match[1]);
					}, 
					$name
				), 
				'_'
			);
		}
		
		return $trans;
	}
}
