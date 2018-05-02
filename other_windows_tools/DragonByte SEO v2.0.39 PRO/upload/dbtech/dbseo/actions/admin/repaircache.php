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

// Rebuild the cache
DBSEO_CACHE::buildAll();

define('CP_REDIRECT', 'index.php?do=home');
print_stop_message('dbtech_dbseo_x_y', $vbphrase['dbtech_dbseo_cache'], $vbphrase['dbtech_dbseo_repaired']);