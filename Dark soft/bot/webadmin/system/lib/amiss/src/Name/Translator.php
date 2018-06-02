<?php

namespace Amiss\Name;

/**
 * Interface for bi-directional name mapping
 */
interface Translator
{
	function translate(array $names);
	function untranslate(array $names); 
}
