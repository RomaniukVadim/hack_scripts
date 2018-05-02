<?php
if (preg_match('#^dbseo\_(usergroup|forum)s?(:([0-9]+|all|none))?$#', $oldsetting['optioncode']))
{
	// serialize the array of usergroup inputs
	if (!is_array($settings["$oldsetting[varname]"]))
	{
		 $settings["$oldsetting[varname]"] = array();
	}
	$settings["$oldsetting[varname]"] = array_map('intval', $settings["$oldsetting[varname]"]);
	$settings["$oldsetting[varname]"] = serialize($settings["$oldsetting[varname]"]);
}
?>