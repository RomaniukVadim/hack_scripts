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

$memoryLimit = ini_get('memory_limit');
$last = strtolower($memoryLimit[strlen(trim($memoryLimit)) - 1]);
switch($last)
{
	// The 'G' modifier is available since PHP 5.1.0
	case 'g':
		$memoryLimit *= 1024;
	case 'm':
		$memoryLimit *= 1024;
	case 'k':
		$memoryLimit *= 1024;
}

if ($memoryLimit < 134217728)
{
	// Set memory limit to 128M
	@ini_set('memory_limit', '128M');
}

require_once(DIR . '/dbtech/dbseo/includes/class_sitemap.php');

if (!function_exists('print_form_auto_submit'))
{
/**
* Prints JavaScript to automatically submit the named form. Primarily used
* for automatic redirects via POST.
*
* @param	string	Form name (in HTML)
*/
function print_form_auto_submit($form_name)
{
	$form_name = preg_replace('#[^a-z0-9_]#i', '', $form_name);

	?>
	<script type="text/javascript">
	<!--
	if (document.<?php echo $form_name; ?>)
	{
		function send_submit()
		{
			var submits = YAHOO.util.Dom.getElementsBy(
				function(element) { return (element.type == "submit") },
				"input", this
			);
			var submit_button;

			for (var i = 0; i < submits.length; i++)
			{
				submit_button = submits[i];
				submit_button.disabled = true;
				setTimeout(function() { submit_button.disabled = false; }, 10000);
			}

			return false;
		}

		YAHOO.util.Event.on(document.<?php echo $form_name; ?>, 'submit', send_submit);
		send_submit.call(document.<?php echo $form_name; ?>);
		document.<?php echo $form_name; ?>.submit();
	}
	// -->
	</script>
	<?php
}
}

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

print_cp_header($vbphrase['dbtech_dbseo_xml_sitemap_manager']);

// #############################################################################
if ($_REQUEST['action'] == 'buildsitemap' OR empty($_REQUEST['action']))
{
	$vbulletin->input->clean_array_gpc('r', array(
		'success' => TYPE_BOOL
	));

	if ($vbulletin->GPC['success'])
	{
		print_table_start();
		print_description_row($vbphrase['dbtech_dbseo_sitemap_built_successfully_view_here'], false, 2, '', 'center');
		print_table_footer();
	}

	$runner = new DBSEO_SiteMapRunner_Admin($vbulletin);

	$status = $runner->check_environment();
	if ($status['error'])
	{
		$sitemap_session = $runner->fetch_session();
		if ($sitemap_session['state'] != 'start')
		{
			print_table_start();
			print_description_row('<a href="index.php?do=removesession">' . $vbphrase['dbtech_dbseo_remove_sitemap_session'] . '</a>', false, 2, '', 'center');
			print_table_footer();
		}

		print_stop_message($status['error'], $vbulletin->options['dbtech_dbseo_cp_folder']);
	}

	// Manual Sitemap Build
	print_form_header('index', 'buildsitemap');
	construct_hidden_code('action', 'dobuildsitemap');
	print_table_header($vbphrase['dbtech_dbseo_build_sitemap']);
	print_description_row($vbphrase['dbtech_dbseo_use_to_build_sitemap']);
	print_submit_row($vbphrase['dbtech_dbseo_build_sitemap'], null);
}

// ########################################################################
if ($_POST['action'] == 'dobuildsitemap')
{
	$runner = new DBSEO_SiteMapRunner_Admin($vbulletin);

	$status = $runner->check_environment();
	if ($status['error'])
	{
		print_stop_message($status['error'], $vbulletin->options['dbtech_dbseo_cp_folder']);
	}

	echo '<div>' . construct_phrase($vbphrase['processing_x'], '...') . '</div>';
	vbflush();

	$runner->generate();

	if ($runner->is_finished)
	{
		print_cp_redirect('index.php?do=buildsitemap&success=1');
	}
	else
	{
		echo '<div>' . construct_phrase($vbphrase['processing_x'], $runner->written_filename) . '</div>';

		print_form_header('index', 'buildsitemap', false, true, 'cpform_dobuildsitemap');
		construct_hidden_code('action', 'dobuildsitemap');		
		print_submit_row($vbphrase['next_page'], 0);
		print_form_auto_submit('cpform_dobuildsitemap');
	}
}

print_cp_footer();