<?php

namespace Amiss\Type;

interface Handler
{
	function prepareValueForDb($value, $object, array $fieldInfo);
	
	/**
	 * Handle a value retrieved from the database.
	 * 
	 * This value should be returned after you transform it.
	 * 
	 * Population of the handled value happens outside the type handler, 
	 * though you're free to mess with the object in any other way.
	 * 
	 * @param mixed $value The value retrieved from the database
	 * @param object $object The object being populated.
	 * @param array $fieldInfo The field's metadata.
	 * @param array $row The row, exactly as retrieved from the database.
	 */
	function handleValueFromDb($value, $object, array $fieldInfo, $row);
	
	/**
	 * It's ok to return nothing from this - the default column type
	 * will be used.
	 */
	function createColumnType($engine);
}
