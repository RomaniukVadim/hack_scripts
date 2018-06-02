<?php

namespace Amiss\Note;

class Parser
{
	public function parseClass(\ReflectionClass $class)
	{
		$info = new \stdClass;
		$info->notes = null;
		
		$doc = $class->getDocComment();
		if ($doc) {
			$info->notes = $this->parseComment($doc);
		}
		
		$info->methods = $this->parseReflectors($class->getMethods());
		$info->properties = $this->parseReflectors($class->getProperties());
		
		return $info;
	}
	
	public function parseReflectors($reflectors)
	{
		$notes = array();
		foreach ($reflectors as $r) {
			$comment = $r->getDocComment();
			$name = $r->name;
			if ($comment) {
				$notes[$name] = $this->parseComment($comment);
			}
		}
		return $notes;
	}
	
	public function parseComment($docComment)
	{
		// docblock start
		$docComment = preg_replace('@\s*/\*+@', '', $docComment);
		
		// docblock end
		$docComment = preg_replace('@\*+/\s*$@', '', $docComment);
		
		// docblock margin
		$docComment = preg_replace('@^\s*\*\s*@mx', '', $docComment);
		
		$notes = array();
		$lines = preg_split('@\n@', $docComment, null, PREG_SPLIT_NO_EMPTY);
		foreach ($lines as $l) {
			$l = trim($l);
			if ($l && $l[0] == '@') {
				$l = substr($l, 1);
				$d = explode(' ', $l, 2);
				
				if (isset($d[1]))
					$notes[$d[0]] = $d[1];
				else
					$notes[$d[0]] = true;
			}
		}
		return $notes;
	}
	
	public function parseComplexValue($noteValue)
	{
		$qs = trim(preg_replace('/\s*([=&])\s*/', '$1', str_replace(';', '&', $noteValue)));
		parse_str($qs, $data);
		return $data;
	}
}
