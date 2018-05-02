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

// identify where we are
define('VB_AREA', 'ModCP');
define('VB_ENTRY', 1);
define('IN_CONTROL_PANEL', true);

if (!isset($phrasegroups) OR !is_array($phrasegroups))
{
	$phrasegroups = array();
}
$phrasegroups[] = 'cpglobal';

if (!isset($specialtemplates) OR !is_array($specialtemplates))
{
	$specialtemplates = array();
}
$specialtemplates[] = 'mailqueue';
$specialtemplates[] = 'pluginlistadmin';

// ###################### Start functions #######################
chdir('./../');
define('CWD', (($getcwd = getcwd()) ? $getcwd : '.'));

require_once(CWD . '/includes/init.php');
require_once(DIR . '/includes/adminfunctions.php');

// ###################### Start headers (send no-cache) #######################
exec_nocache_headers();

if ($vbulletin->userinfo['cssprefs'] != '')
{
	$vbulletin->options['cpstylefolder'] = $vbulletin->userinfo['cssprefs'];
}

// ###################### Get date / time info #######################
// override date/time settings if specified
fetch_options_overrides($vbulletin->userinfo);
fetch_time_data();

// ############################################ LANGUAGE STUFF ####################################
// initialize $vbphrase and set language constants
$vbphrase = init_language();
if (intval($vbulletin->versionnumber) == 4 AND $stylestuff = $vbulletin->db->query_first_slave("
	SELECT styleid, dateline, title
	FROM " . TABLE_PREFIX . "style
	WHERE styleid = " . $vbulletin->options['styleid'] . "
	ORDER BY styleid " . ($styleid > $vbulletin->options['styleid'] ? 'DESC' : 'ASC') . "
	LIMIT 1
"))
{
	fetch_stylevars($stylestuff, $vbulletin->userinfo);
}
else
{
	$_tmp = NULL;
	$stylevar = fetch_stylevars($_tmp, $vbulletin->userinfo);
}

($hook = vBulletinHook::fetch_hook('admin_global')) ? eval($hook) : false;

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 17:07, Thu Oct 30th 2014
|| # CVS: $RCSfile$ - $Revision: 77605 $
|| ####################################################################
\*======================================================================*/
?>