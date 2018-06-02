<?php

namespace Amiss\Type;

class AutoGuid implements Handler, Identity
{
	function handleDbGeneratedValue($value)
	{
		// return nothing - we don't care about a DB generated value if we 
		// are generating one ourselves
	}
	
	// FIXME: this class should derive from an IdGenerator base.
	function prepareValueForDb($value, $object, array $fieldInfo)
	{
		$name = $fieldInfo['name'];
		$getter = isset($fieldInfo['getter']);
		
		if (!$value) {
			$value = $this->generate();
			if ($getter)
				$object->{$fieldInfo['setter']}($value);
			else
				$object->$name = $value;
		}
		
		return $value;
	}
	
	function handleValueFromDb($value, $object, array $fieldInfo, $row)
	{
		return $value;
	}
	
	function createColumnType($engine)
	{
		return "CHAR(36)";
	}
	
	function generate()
	{
		// From http://stackoverflow.com/questions/2040240/php-function-to-generate-v4-uuid
	    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
	        // 32 bits for "time_low"
	        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
	
	        // 16 bits for "time_mid"
	        mt_rand( 0, 0xffff ),
	
	        // 16 bits for "time_hi_and_version",
	        // four most significant bits holds version number 4
	        mt_rand( 0, 0x0fff ) | 0x4000,
	
	        // 16 bits, 8 bits for "clk_seq_hi_res",
	        // 8 bits for "clk_seq_low",
	        // two most significant bits holds zero and one for variant DCE1.1
	        mt_rand( 0, 0x3fff ) | 0x8000,
	
	        // 48 bits for "node"
	        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
	    );
	}
}
