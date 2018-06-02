<?php

namespace Amiss\Type;

interface Identity
{
	function handleDbGeneratedValue($value);
}
