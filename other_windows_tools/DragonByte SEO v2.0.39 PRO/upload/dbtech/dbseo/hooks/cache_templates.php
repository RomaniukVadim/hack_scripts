<?php
if (!isset($cache))
{
	$cache  = array();
}

/*DBTECH_PRO_START*/
if (THIS_SCRIPT == 'postings')
{
	$cache[] = 'dbtech_dbseo_perthread';
}
/*DBTECH_PRO_END*/

// Add the main shoutbox template to the cache
$cache = array_merge($cache, array(
	'dbtech_dbseo_socialshare_horizontal_count',
	'dbtech_dbseo_socialshare_horizontal_big',
	'dbtech_dbseo_socialshare_horizontal_small',
	'dbtech_dbseo_socialshare_vertical_count',
	'dbtech_dbseo_socialshare_vertical_big',
	'dbtech_dbseo_socialshare_vertical_small',
	'dbtech_dbseo_navbit_link',
	'dbtech_dbseo_sitelink_search',
));
		
if (intval($vbulletin->versionnumber) == 3)
{
	$globaltemplates = array_merge($globaltemplates, $cache);
}
?>