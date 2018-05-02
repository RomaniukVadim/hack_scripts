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

// ####################### SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);

// #################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'dbseoadmin');
define('IN_DBSEO', true);
define('DBSEO_ADMIN', true);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array(
	'dbtech_dbseo', 'cphome', 'logging', 'threadmanage',
	'banning', 'cpuser', 'cpoption', 'cppermission', 'user', 'search'
);

// get special data templates from the datastore
require('../dbtech/dbseo/includes/specialtemplates.php');
$specialtemplates = $extracache;

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/dbtech/dbseo/includes/functions.php');

if (!class_exists('DBSEO'))
{
	if (!$vbulletin->products['dbtech_dbseo'])
	{
		// This either wasn't installed or deactivated
		print_cp_message($vbphrase['dbtech_dbseo_deactivated']);
	}

	// Set important constants
	define('DBSEO_CWD', 	getcwd());
	define('DBSEO_TIMENOW', time());
	define('IN_DBSEO', 		true);

	// Make sure we nab this class
	include_once(DBSEO_CWD . '/dbtech/dbseo/includes/class_core.php');

	// Initialise our configuration
	DBSEO::init();
}

// ######################## CHECK ADMIN PERMISSIONS ######################
if (!is_array($vbulletin->userinfo['permissions']))
{
	// No idea why this would happen but there you go
	cache_permissions($vbulletin->userinfo);
}

$needPassword = $authorised = false;
if (!($vbulletin->userinfo['permissions']['dbtech_dbseopermissions'] & $vbulletin->bf_ugp_dbtech_dbseopermissions['canadmindbseo']))
{
	if ($vbulletin->options['dbtech_dbseo_password'])
	{
		// We need a password
		$needPassword = true;
	}
}
else
{
	// We are authorised to be here
	$authorised = true;

	if (trim($vbulletin->options['dbtech_dbseo_password']) AND $vbulletin->options['dbtech_dbseo_password_mode'])
	{
		// We need a password
		$needPassword = true;

		// Lol nvm we aren't auth'd
		$authorised = false;
	}
}

if ($needPassword)
{
	$vbulletin->input->clean_array_gpc('p', array(
		'cppassword' => TYPE_STR,
	));
	$vbulletin->input->clean_array_gpc('c', array(
		COOKIE_PREFIX . 'dbtseocppassword'      => TYPE_STR,
	));

	if ($vbulletin->GPC['cppassword'] == $vbulletin->options['dbtech_dbseo_password'])
	{
		// Valid password
		$vbulletin->GPC[COOKIE_PREFIX . 'dbtseocppassword'] = md5(md5(md5($vbulletin->options['dbtech_dbseo_password']) . md5(COOKIE_SALT)));
		vbsetcookie('dbtseocppassword', $vbulletin->GPC[COOKIE_PREFIX . 'dbtseocppassword']);
	}

	if (!$vbulletin->GPC[COOKIE_PREFIX . 'dbtseocppassword'] OR
		$vbulletin->GPC[COOKIE_PREFIX . 'dbtseocppassword'] != md5(md5(md5($vbulletin->options['dbtech_dbseo_password']) . md5(COOKIE_SALT)))
	)
	{
		print_cp_header($vbphrase['dbtech_dbseo_welcome_to_the_admin_control_panel']);
		print_form_header('index', 'frames');
		print_table_header($vbphrase['password']);
		print_input_row($vbphrase['password'], 'cppassword');
		print_submit_row($vbphrase['submit'], 0);
		print_cp_footer();
	}
	else
	{
		// We've got a valid pass
		$authorised = true;
	}
}

if (!$authorised)
{
	print_cp_message($vbphrase['dbtech_dbseo_nopermission_cp']);
}

// ############################# LOG ACTION ##############################
if (empty($_REQUEST['do']))
{
	log_admin_action(iif($_REQUEST['action'] != '', 'action = ' . $_REQUEST['action']));
}

// #############################################################################

$vbulletin->input->clean_array_gpc('r', array(
	'redirect' => TYPE_STR,
	'nojs' 		=> TYPE_BOOL,
));

// #############################################################################
// ################################## REDIRECTOR ###############################
// #############################################################################

if (!empty($vbulletin->GPC['redirect']))
{
	require_once(DIR . '/includes/functions_login.php');
	$redirect = htmlspecialchars_uni(fetch_replaced_session_url($vbulletin->GPC['redirect']));
	$redirect = create_full_url($redirect);
	$redirect = preg_replace(
		array('/&#0*59;?/', '/&#x0*3B;?/i', '#;#'),
		'%3B',
		$redirect
	);
	$redirect = preg_replace('#&amp%3B#i', '&amp;', $redirect);

	print_cp_header($vbphrase['redirecting_please_wait'], '', "<meta http-equiv=\"Refresh\" content=\"0; URL=$redirect\" />");
	echo "<p>&nbsp;</p><blockquote><p>$vbphrase[redirecting_please_wait]</p></blockquote>";
	print_cp_footer();
	exit;
}

$vbulletin->input->clean_array_gpc('r', array(
	'file' 		=> TYPE_NOHTML
));

// #############################################################################
// ################################# SAVE NOTES ################################
// #############################################################################

if ($_POST['do'] == 'notes')
{
	$vbulletin->input->clean_array_gpc('p', array('notes' => TYPE_STR));

	$db->query_write("
		UPDATE " . TABLE_PREFIX . "datastore
		SET data = " . $db->sql_prepare(htmlspecialchars_uni($vbulletin->GPC['notes'])) . "
		WHERE title = 'dbtech_dbseo_adminnote'
	");

	DBSEO::$cache['adminnote'] = htmlspecialchars_uni($vbulletin->GPC['notes']);
	$_REQUEST['do'] = 'home';
}

// #############################################################################
// ################################# HEADER FRAME ##############################
// #############################################################################

if ($_REQUEST['do'] == 'head')
{
	if (intval($vbulletin->versionnumber) > 3)
	{
		$stylevar['right'] = vB_Template_Runtime::fetchStyleVar('right');
		$stylevar['left'] = vB_Template_Runtime::fetchStyleVar('left');
	}

	ignore_user_abort(true);

	define('IS_NAV_PANEL', true);
	print_cp_header('', '');

	?>
	<table border="0" width="100%" height="100%">
	<tr align="center" valign="top">
		<td style="text-align:<?php echo $stylevar['left']; ?>"><b><?php echo $vbphrase['admin_control_panel']; ?></b> (DragonByte Tech: DragonByte SEO v2.0.39)</td>
		<td style="white-space:nowrap; text-align:<?php echo $stylevar['right']; ?>; font-weight:bold">
			<a href="<?php echo $vbulletin->options['bburl'] . '/' . $vbulletin->options['forumhome']; ?>.php<?php echo $vbulletin->session->vars['sessionurl_q']; ?>" target="_blank"><?php echo $vbphrase['forum_home_page']; ?></a>
		</td>
	</tr>
	</table>
	<?php

	define('NO_CP_COPYRIGHT', true);
	unset($DEVDEBUG);
	print_cp_footer();

}

// ################################ NAVIGATION FRAME #############################

if ($_REQUEST['do'] == 'nav')
{
	require_once(DIR . '/includes/adminfunctions_navpanel.php');
	print_cp_header();

	echo "\n<div>";
	?><img src="../cpstyles/<?php echo $vbulletin->options['cpstylefolder']; ?>/cp_logo.gif" title="<?php echo $vbphrase['admin_control_panel']; ?>" alt="" border="0" hspace="4" vspace="4" /><?php
	echo "</div>\n\n<div style=\"width:168px; padding: 4px\">\n";

	// cache nav prefs
	can_administer();
	construct_nav_spacer();

	echo "<div align=\"center\"><a href=\"index.php?" . $vbulletin->session->vars['sessionurl'] . "do=home\">$vbphrase[control_panel_home]</a></div>";
	echo "<div align=\"center\"><a href=\"../" . $vbulletin->config['Misc']['admincpdir'] . "\">$vbphrase[dbtech_dbseo_vbulletin_admin_control_panel]</a></div>";

	// Include navigation
	require(DIR . '/dbtech/dbseo/includes/cpnav.php');

	echo $_NAV;

	echo "</div>\n";
	// *************************************************

	define('NO_CP_COPYRIGHT', true);
	unset($DEVDEBUG);
	print_cp_footer();

}

// #############################################################################
// ################################ BUILD FRAMESET #############################
// #############################################################################

if ($_REQUEST['do'] == 'frames' OR empty($_REQUEST['do']))
{
	$vbulletin->input->clean_array_gpc('r', array(
		'loc' 		=> TYPE_NOHTML
	));

	if (!empty($vbulletin->GPC['loc']))
	{
		// Strip invalid characters
		$action = preg_replace('/[^\w-]/i', '', $vbulletin->GPC['loc']);

		if (!file_exists(DIR . '/dbtech/dbseo/actions/admin/' . $action . '.php'))
		{
			if (!file_exists(DIR . '/dbtech/dbseo_pro/actions/admin/' . $action . '.php'))
			{
				// Throw error from invalid action
				print_cp_message(
					$vbphrase['dbtech_dbseo_invalid_action'] . '<br />
					<strong>Not Found:</strong> <em>' . DIR . '/dbtech/dbseo/actions/admin/' . $action . '.php' . '</em><br />' . '
					<strong>Not Found:</strong> <em>' . DIR . '/dbtech/dbseo_pro/actions/admin/' . $action . '.php' . '</em>'
				);
			}
			else
			{
				// Include the selected file
				$loc = DIR . '/dbtech/dbseo_pro/actions/admin/' . $action . '.php';
			}
		}
		else
		{
			// Include the selected file
			$loc = DIR . '/dbtech/dbseo/actions/admin/' . $action . '.php';
		}

	}

	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
	<head>
	<script type="text/javascript">
	<!--
	// get out of any containing frameset
	if (self.parent.frames.length != 0)
	{
		self.parent.location.replace(document.location.href);
	}
	// -->
	</script>
	<title><?php echo $vbulletin->options['bbtitle'] . ' ' . $vbphrase['admin_control_panel']; ?></title>
	</head>

        <frameset cols="195,*"  framespacing="0" border="0" frameborder="0">
            <frame src="index.php?<?php echo $vbulletin->session->vars['sessionurl']; ?>do=nav<?php echo iif($vbulletin->GPC['nojs'], '&amp;nojs=1'); ?>" name="nav" scrolling="yes" frameborder="0" marginwidth="0" marginheight="0" border="no" />
            <frameset rows="20,*"  framespacing="0" border="0" frameborder="0">
                <frame src="index.php?<?php echo $vbulletin->session->vars['sessionurl']; ?>do=head" name="head" scrolling="no" noresize="noresize" frameborder="0" marginwidth="10" marginheight="0" border="no" />
                <frame src="<?php echo iif(!empty($vbulletin->GPC['loc']), $loc, "index.php?" . $vbulletin->session->vars['sessionurl'] . "do=home"); ?>" name="main" scrolling="yes" frameborder="0" marginwidth="10" marginheight="10" border="no" />
           </frameset>
      	</frameset>

	<noframes>
		<body>
			<p><?php echo $vbphrase['no_frames_support']; ?></p>
		</body>
	</noframes>
	</html>
	<?php
	die();
}

// ################################ MAIN FRAME #############################

if ($_REQUEST['do'] == 'home')
{
	$isIncluded = true;

	if (
		$vbulletin->config['Datastore']['class'] == 'vB_Datastore_XCache' AND
		@ini_get('xcache.admin.enable_auth') == 'On' AND (
			!$vbulletin->config['xcache']['user'] OR
			!$vbulletin->config['xcache']['pass']
		)
	)
	{
		// *************************************
		// XCache warning


	}

	print_cp_header($vbphrase['dbtech_dbseo_welcome_to_the_admin_control_panel']);

	$news_rows = array();

	// let's look for any messages that we need to display to the admin
	$adminmessages_result = $db->query_read("
		SELECT *
		FROM " . TABLE_PREFIX . "dbtech_dbseo_adminmessage
		WHERE status = 'undone'
		ORDER BY dateline
	");
	if ($db->num_rows($adminmessages_result))
	{
		require_once(DIR . '/includes/functions_misc.php');

		ob_start();
		while ($adminmessage = $db->fetch_array($adminmessages_result))
		{
			$doContinue = false;
			if ($adminmessage['script'])
			{
				require(DIR . '/dbtech/dbseo/includes/adminmessage/' . preg_replace('/[^\w-]/i', '', $adminmessage['script']) . '.php');
			}

			if ($doContinue)
			{
				// Skip this
				continue;
			}

			$buttons = '';
			if ($adminmessage['execurl'])
			{
				$buttons .= '<input type="submit" name="address[' . $adminmessage['adminmessageid'] .']" value="' . $vbphrase['address'] . '" class="button" />';
			}
			if ($adminmessage['dismissable'])
			{
				$buttons .= ' <input type="submit" name="dismiss[' . $adminmessage['adminmessageid'] .']" value="' . $vbphrase['dismiss'] . '" class="button" />';
			}

			$args = @unserialize($adminmessage['args']);
			$args = is_array($args) ? $args : array();
			array_unshift($args, fetch_phrase($adminmessage['varname'], 'error'));

			print_description_row("<div style=\"float: right\">$buttons</div><div>" . $vbphrase['admin_attention_required'] . "</div>", false, 2, 'thead');
			print_description_row(
				'<div class="smallfont">' . stripslashes(call_user_func_array('construct_phrase', $args)) . "</div>"
			);
		}
		$news_rows['admin_messages'] = ob_get_clean();

		if (!$news_rows['admin_messages'])
		{
			unset($news_rows['admin_messages']);
		}
	}

	echo '<div id="admin_news"' . (empty($news_rows) ? ' style="display: none;"' : '') . '>';
	if (!empty($news_rows))
	{
		print_form_header('index', 'handlemessage', false, true, 'news');

		print_table_header($vbphrase['news_header_string']);
		echo $news_rows['new_version'];
		echo $news_rows['admin_messages'];

		print_table_footer();
	}
	else
	{
		print_form_header('index', 'handlemessage', false, true, 'news');

		print_table_footer();
	}
	echo '</div>'; // end of <div id="admin_news">


	// *************************************
	// System Statistics
	include_once(DIR . '/dbtech/dbseo/actions/admin/info.php');

	// *************************************
	// Administrator Notes

	print_form_header('index', 'notes');
	print_table_header($vbphrase['administrator_notes'], 1);
	print_description_row("<textarea name=\"notes\" style=\"width: 90%\" rows=\"9\" tabindex=\"1\">" . DBSEO::$cache['adminnote'] . "</textarea>", false, 1, '', 'center');
	print_submit_row($vbphrase['save'], 0, 1);

	// *************************************
	// Credits
	print_table_start();
	print_table_header($vbphrase['dbtech_dbseo_developers_and_contributors']);
	print_column_style_code(array('white-space: nowrap', ''));
	print_label_row('<b>' . 'NULLED by' . '</b>', '
		<a href="http://vbsupport.org/forum" target="dbtech">vBSupport.org</a>
	', '', 'top', NULL, false);
	print_label_row('<b>' . $vbphrase['dbtech_dbseo_software_developed_by'] . '</b>', '
		<a href="https://www.dragonbyte-tech.com/" target="dbtech">DragonByte Tech</a>
	', '', 'top', NULL, false);
	print_label_row('<b>' . $vbphrase['dbtech_dbseo_business_product_development'] . '</b>', '
		<a href="https://www.dragonbyte-tech.com/member.ph' . 'p?u=3" target="dbtech">Iain Kidd</a>,
		<a href="http://www.seovb.com" target="seovb">SEOvB</a>
	', '', 'top', NULL, false);
	print_label_row('<b>' . $vbphrase['dbtech_dbseo_engineering'] . '</b>', '
		<a href="https://www.dragonbyte-tech.com/member.ph' . 'p?u=1" target="dbtech">Fillip Hannisdal</a>,
		<a href="http://www.seovb.com" target="seovb">SEOvB</a>
	', '', 'top', NULL, false);
	print_label_row('<b>' . $vbphrase['dbtech_dbseo_qa'] . '</b>', '
		<a href="https://www.dragonbyte-tech.com/member.ph' . 'p?u=1" target="dbtech">Fillip Hannisdal</a>,
		<a href="https://www.dragonbyte-tech.com/member.ph' . 'p?u=3" target="dbtech">Iain Kidd</a>,
		<a href="http://www.seovb.com" target="seovb">SEOvB</a>
	', '', 'top', NULL, false);

	print_label_row('<b>' . $vbphrase['dbtech_dbseo_support'] . '</b>', '
		<a href="https://www.dragonbyte-tech.com/member.ph' . 'p?u=1" target="dbtech">Fillip Hannisdal</a>
	', '', 'top', NULL, false);

	/*
	print_label_row('<b>' . $vbphrase['special_thanks_and_contributions'] . '</b>', '
		<a href="http://www.darkhandofvalor.com/" target="dbtech">Troy Jones</a>,
		Ciandi Patry,
		<a href="http://www.halforums.com/">David Nihsen</a>
	', '', 'top', NULL, false);
	*/
	/*
	print_label_row('<b>' . $vbphrase['copyright_enforcement_by'] . '</b>', '
		<a href="http://www.vbulletin.com/" target="dbtech">vBulletin Solutions, Inc.</a>
	', '', 'top', NULL, false);
	*/
	print_table_footer();

	print_cp_footer();

}

// ################################ HANDLE ADMIN MESSAGES #############################
if ($_POST['do'] == 'handlemessage')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'address' => TYPE_ARRAY_KEYS_INT,
		'dismiss' => TYPE_ARRAY_KEYS_INT,
		'acpnews' => TYPE_ARRAY_KEYS_INT
	));

	print_cp_header($vbphrase['dbtech_dbseo_welcome_to_the_admin_control_panel']);

	if ($vbulletin->GPC['address'])
	{
		// chosen to address the issue -- redirect to the appropriate page
		$adminmessageid = intval($vbulletin->GPC['address'][0]);
		$adminmessage = $db->query_first("
			SELECT * FROM " . TABLE_PREFIX . "dbtech_dbseo_adminmessage
			WHERE adminmessageid = $adminmessageid
		");

		if (!empty($adminmessage) AND empty($adminmessage['script']))
		{
			// set the issue as addressed
			$db->query_write("
				UPDATE " . TABLE_PREFIX . "dbtech_dbseo_adminmessage
				SET status = 'done', statususerid = " . $vbulletin->userinfo['userid'] . "
				WHERE adminmessageid = $adminmessageid
			");
		}

		if (!empty($adminmessage) AND !empty($adminmessage['execurl']))
		{
			if ($adminmessage['method'] == 'get')
			{
				// get redirect -- can use the url basically as is
				if (!strpos($adminmessage['execurl'], '?'))
				{
					$adminmessage['execurl'] .= '?';
				}
				print_cp_redirect($adminmessage['execurl'] . $vbulletin->session->vars['sessionurl_js']);
			}
			else
			{
				// post redirect -- need to seperate into <file>?<querystring> first
				if (preg_match('#^(.+)\?(.*)$#siU', $adminmessage['execurl'], $match))
				{
					$script = $match[1];
					$arguments = explode('&', $match[2]);
				}
				else
				{
					$script = $adminmessage['execurl'];
					$arguments = array();
				}

				echo '
					<form action="' . htmlspecialchars_uni($script) . '" method="post" id="postform">
				';

				foreach ($arguments AS $argument)
				{
					// now take each element in the query string into <name>=<value>
					// and stuff it into hidden form elements
					if (preg_match('#^(.*)=(.*)$#siU', $argument, $match))
					{
						$name = $match[1];
						$value = $match[2];
					}
					else
					{
						$name = $argument;
						$value = '';
					}
					echo '
						<input type="hidden" name="' . htmlspecialchars_uni(urldecode($name)) . '" value="' . htmlspecialchars_uni(urldecode($value)) . '" />
					';
				}

				// and submit the form automatically
				echo '
					</form>
					<script type="text/javascript">
					<!--
					fetch_object(\'postform\').submit();
					// -->
					</script>
				';
			}

			print_cp_footer();
		}
	}
	else if ($vbulletin->GPC['dismiss'])
	{
		// choosing to forget about the issue
		$adminmessageid = intval($vbulletin->GPC['dismiss'][0]);

		$db->query_write("
			UPDATE " . TABLE_PREFIX . "dbtech_dbseo_adminmessage
			SET status = 'dismissed', statususerid = " . $vbulletin->userinfo['userid'] . "
			WHERE adminmessageid = $adminmessageid
		");
	}
	print_cp_redirect('index.php?do=home' . $vbulletin->session->vars['sessionurl_js']);
}

if (!empty($_REQUEST['do']))
{
	// Strip invalid characters
	$action = preg_replace('/[^\w-]/i', '', $_REQUEST['do']);

	if (!file_exists(DIR . '/dbtech/dbseo/actions/admin/' . $action . '.php'))
	{
		if (!file_exists(DIR . '/dbtech/dbseo_pro/actions/admin/' . $action . '.php'))
		{
			// Throw error from invalid action
			print_cp_message(
				$vbphrase['dbtech_dbseo_invalid_action'] . '<br />
				<strong>Not Found:</strong> <em>' . DIR . '/dbtech/dbseo/actions/admin/' . $action . '.php' . '</em><br />' . '
				<strong>Not Found:</strong> <em>' . DIR . '/dbtech/dbseo_pro/actions/admin/' . $action . '.php' . '</em>'
			);
		}
		else
		{
			// Include the selected file
			include(DIR . '/dbtech/dbseo_pro/actions/admin/' . $action . '.php');
		}
	}
	else
	{
		// Include the selected file
		include(DIR . '/dbtech/dbseo/actions/admin/' . $action . '.php');
	}
	print_cp_footer();
}