<?php

namespace Amiss;

interface Relator
{
	function getRelated($source, $relationName, $criteria=null);
}
