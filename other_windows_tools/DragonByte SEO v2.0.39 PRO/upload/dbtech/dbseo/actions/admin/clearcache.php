<?php
/*======================================================================*\
|| #################################################################### ||
|| # ---------------------------------------------------------------- # ||
|| # Copyright ©2013 Fillip Hannisdal AKA Revan/NeoRevan/Belazor 	  # ||
|| # All Rights Reserved. 											  # ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------------------------------------------------------- # ||
|| # You are not allowed to use this on your server unless the files  # ||
|| # you downloaded were done so with permission.					  # ||
|| # ---------------------------------------------------------------- # ||
|| #################################################################### ||
\*======================================================================*/

@set_time_limit(0);
ignore_user_abort(1);

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

// Nuke all the resolved URLs juuuust to be sure
$vbulletin->db->query_write("TRUNCATE TABLE " . TABLE_PREFIX . "dbtech_dbseo_resolvedurl");

// Rebuild the cache
DBSEO::$datastore->flush();

// Clear this too
build_datastore('dbtech_dbseo_ga_cache', trim(serialize(array())), 1);
build_datastore('dbtech_dbseo_gwt_cache', trim(serialize(array())), 1);

define('CP_REDIRECT', 'index.php?do=home');
print_stop_message('dbtech_dbseo_x_y', $vbphrase['dbtech_dbseo_cache'], $vbphrase['dbtech_dbseo_flushed']);