<?php
define('XHPROF_DEBUG', false);

if (!isset($_SERVER['REQUEST_TIME_FLOAT']))
{
	// We need this
	$_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);
}

if (
	isset($_SERVER['SERVER_ADDR']) AND
	$_SERVER['SERVER_ADDR'] == '192.168.0.20' AND
	$_SERVER['SERVER_NAME'] == 'development' AND
	function_exists('xhprof_enable') AND
	XHPROF_DEBUG === true
)
{
	// Enable debugging
	xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
}

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

// #################### DEFINE IMPORTANT CONSTANTS #######################
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);

if (!class_exists('DBSEO'))
{
	// Set important constants
	define('DBSEO_CWD', 	getcwd());
	define('DBSEO_TIMENOW', time());
	define('IN_DBSEO', 		true);

	// Make sure we nab this class
	require_once(DBSEO_CWD . '/dbtech/dbseo/includes/class_core.php');
}

// Initialise our configuration
DBSEO::init();

switch (DBSEO_URL_QUERY_FILE)
{
	case 'dbseo.php':
	case 'vbseo.php': # compatibility purposes
		die();
	break;

	case 'dbseositemap.php':
	case 'cron.php':
	case 'cron.html':
		include(DBSEO_CWD . '/' . DBSEO_URL_QUERY_FILE);
		die();
	break;
}

if (DBSEO_URL_QUERY_FILE == 'redirect-to/')
{
	//$redirectUrl = str_replace(array('"', ' '), array('&quot;', ''), preg_replace('#&(?![a-z0-9\#]+;)#si', '&amp;', $_GET['redirect']));
	$redirectUrl = $_GET['redirect'];

	if (strpos($redirectUrl, '://') !== false AND !preg_match('#["<>]#', $redirectUrl))
	{
		// We're redirecting
		header('Location: ' . $redirectUrl);
		die();
	}
}

switch (DBSEO_URL_QUERY_FILE)
{
	case 'sitemap.xml':
		// We're moving on to the actual location
		DBSEO::safeRedirect('dbseositemap.php');
		break;

	case 'archive/index.php':
		if (DBSEO::$config['archiveenabled'])
		{
			// Archive is enabled
			break;
		}

		if (!preg_match('#(t|f)-(\d+)\.html#i', DBSEO_URL, $matches))
		{
			// Archive index
			DBSEO::safeRedirect(DBSEO::$config['forumhome'] . '.php');
		}
		else if ($matches[1] == 'f')
		{
			// Forum display
			DBSEO::safeRedirect('forumdisplay.php?f=' . $matches[2]);
		}
		else if ($matches[1] == 't')
		{
			// Thread
			DBSEO::safeRedirect('showthread.php?t=' . $matches[2]);
		}
		break;
}

if (DBSEO_RELPATH)
{
	$_fulldir = getcwd() . '/' . DBSEO_RELPATH;
	if (
		strpos(DBSEO_RELPATH, '%') !== false
		OR substr(DBSEO_RELPATH, 0, 1) == '/'
		OR strpos(DBSEO_RELPATH, './../') !== false
		OR (
			is_writable($_fulldir)
			AND !is_writable(getcwd())
			AND (fileperms($_fulldir) & 0755) != 0755
		)
	)
	{
		// This shouldn't happen
		DBSEO::handle404();
	}
	else
	{
		// Just do a normal chdir
		chdir($_fulldir);
	}
}

if (!DBSEO::securityCheck(DBSEO_URL_QUERY_FILE))
{
	// We failed a security check
	DBSEO::handle404('', true);
}

// Define some important stuff
$_queryFile 		= DBSEO_URL_QUERY_FILE;
$_fileExists 		= ((@file_exists($_queryFile) OR (@file_exists(basename($_queryFile)) AND strpos($_queryFile, '.php') !== false)) AND substr($_queryFile, -1) != '/');
$_fileExistsDeep 	= (@file_exists($_queryFile) AND strpos($_queryFile, '/') !== false);
$_gotUrl 			= false;
$_fileName 			= '';
$_suggestedURL 		= '';

// vBSEO defined variable is used by certain scripts to avoid vBSEO processing.
//	Unsure if I need it here, but doesn't hurt
if (DBSEO::$config['dbtech_dbseo_active'])
{
	// Check if we need to redirect to another URL
	$_fileName = DBSEO_Url::redirect($_queryFile, $_fileExists, $_fileExistsDeep);
	if (!$_fileName)
	{
		// Resolve the URLs
		list($_gotUrl, $_fileName, $_suggestedURL) = DBSEO_Url::lookup($_queryFile, $_fileExists, $_fileExistsDeep);

		if (
			(isset($_POST['mergethreadurl']) AND $_POST['mergethreadurl']) OR
			(isset($_POST['dealurl']) AND $_POST['dealurl'])
		)
		{
			// Set this
			$postParam = isset($_POST['mergethreadurl']) ? 'mergethreadurl' : 'dealurl';

			// Parse the URL
			$_parsedUrl = @parse_url($_POST[$postParam]);
			$_parsedUrl = urldecode(substr($_parsedUrl['path'], strlen(DBSEO_URL_SCRIPT_PATH)));

			if ($_urlInfo = DBSEO_Url_Check::check('Thread_Thread_GoToPost', $_parsedUrl))
			{
				// Use post ID
				$_POST[$postParam] = 'showthread.php?p=' . $_urlInfo['post_id'];
			}
			else
			{
				foreach (array(
					'Thread_Thread',
					'Thread_Thread_Page',
				) as $_urlFormat)
				{
					if (!$_urlInfo = DBSEO_Url_Check::check($_urlFormat, $_parsedUrl))
					{
						// Wrong URL
						continue;
					}

					// Set thread ID
					$_POST[$postParam] = 'showthread.php?t=' . $_urlInfo['thread_id'];
				}
			}

			if (strpos($_POST[$postParam], ':') === false)
			{
				// Ensure we add the bburl
				$_POST[$postParam] = DBSEO::$config['_bburl'] . '/' . $_POST[$postParam];
			}

			// Overwrite
			$_REQUEST[$postParam] = $_POST[$postParam];
		}

		if (isset($_POST['usercss']) AND is_array($_POST['usercss']))
		{
			foreach($_POST['usercss'] as $cssind=>$csspart)
			{
				foreach ($csspart as $name => $imgurl)
				{
					if (strpos($name, '_image') === false)
					{
						// Only worry about image URLs
						continue;
					}

					// Parse the image URL
					$_parsedUrl = @parse_url($imgurl);
					$_parsedUrl = urldecode(substr($_parsedUrl['path'], strlen(DBSEO_URL_SCRIPT_PATH)));

					if (!$_parsedUrl OR !$_urlInfo = DBSEO_Url_Check::check('Album_AlbumPictureFile', $_parsedUrl))
					{
						// Incorrect URL
						continue;
					}

					if (empty($_urlInfo['user_id']) AND isset($_urlInfo['user_name']))
					{
						// We need to reverse lookup username
						$_urlInfo['user_id'] = DBSEO_Filter::reverseUsername($_urlInfo['user_name']);
					}

					if (empty($_urlInfo['album_id']) AND isset($_urlInfo['album_title']))
					{
						// We need to reverse lookup album
						$_urlInfo['album_id'] = DBSEO_Filter::reverseObject('album', $_urlInfo['album_title'], $_urlInfo['user_id']);
					}

					// Set the user CSS
					$_POST['usercss'][$cssind][$name] = DBSEO::$config['_bburl'] . '/' . DBSEO::$config['_picturescript'] . '.php?albumid=' . $_urlInfo['album_id'] . '&' . DBSEO::$config['_pictureid'] . '=' . $_urlInfo['picture_id'];
				}
			}
		}

		if (isset($_POST['pictureurls']) AND $_POST['pictureurls'])
		{
			// Split the picture URLs
			$albumUrls = preg_split('#[\r\n]+#', $_POST['pictureurls']);

			$_changedUrl = false;
			$albumUrls2 = array();
			foreach($albumUrls as $albumUrl)
			{
				// Parse the album URL
				$_parsedUrl = @parse_url($albumUrl);
				$_parsedUrl = urldecode(substr($_parsedUrl['path'], strlen(DBSEO_URL_SCRIPT_PATH)));

				// Init this
				$albumUrl2 = '';
				foreach (array(
					'Album_AlbumPicture',
					'Album_AlbumPicture_Page',
					'Album_AlbumPictureFile',
				) as $_urlFormat)
				{
					if (!$_urlInfo = DBSEO_Url_Check::check($_urlFormat, $_parsedUrl))
					{
						// Wrong URL
						continue;
					}

					// Was a member picture
					$albumUrl2 = 'album.php?' . DBSEO::$config['_pictureid'] . '=' . $_urlInfo['picture_id'];
				}

				if ($albumUrl2)
				{
					// Overwrite the new URL
					$albumUrl = DBSEO::$config['_bburl'] . '/' . $albumUrl2;
					$_changedUrl = true;
				}

				// Add the new URL to the array
				$albumUrls2[] = $albumUrl;
			}

			if ($_changedUrl)
			{
				// We had a changed URL, so set it again
				$_POST['pictureurls'] = $_REQUEST['pictureurls'] = implode("\n", $albumUrls2);
			}
		}
	}
	else
	{
		// We found something in the redirect function
		$_gotUrl = true;
	}
}

$_gotUrlBackup = $_gotUrl;
if (!$_gotUrl AND !$_fileExists AND DBSEO_REDIRURL)
{
	if ($_suggestedURL)
	{
		// Redirect to the suggested URL
		DBSEO::safeRedirect(DBSEO_RELPATH . $_suggestedURL);
	}

	// Grab the main file from the query param
	list($_queryFile, ) = explode('?', DBSEO_REDIRURL);

	// Ensure it's not URL encoded
	$_queryFile = urldecode($_queryFile);

	if (!DBSEO::securityCheck($_queryFile))
	{
		// We failed a security check
		DBSEO::handle404('', true);
	}

	// Check if this file exists
	$_fileExists = @file_exists($_queryFile) AND substr($_queryFile, -1) != '/';

	if ($_fileExists)
	{
		// The redirect file exists
		$_fileName = $_queryFile;
		$_gotUrl = true;
	}
}

if (!$_gotUrl)
{
	// We don't have a URL yet
	$_fileName = DBSEO_BASEURL;

	if (@is_dir($_queryFile) OR !$_queryFile)
	{
		// Directory, try to list it
		$_queryFile .= 'index.php';
		$_fileName = 'index.php';
	}

	// Try to find the root directory and set a default file name if none was found
	$rootDir = dirname($_queryFile);
	$_fileName = $_fileName ? $_fileName : 'index.php';

	if (
		@is_file($_queryFile) AND (
			!$rootDir OR
			$rootDir == '.' OR
			@is_dir($rootDir)
		)
	)
	{
		if ($rootDir AND @is_dir($rootDir))
		{
			// Change the directory
			DBSEO::changeDir($rootDir);
		}

		// Update our environment
		DBSEO::updateEnvironment($_SERVER['REQUEST_URI']);

		// We have found a page
		$_gotUrl = true;
	}
	else
	{
		// Try another method of detecting where we are
		$rootDir = dirname($_queryFile);
		$rootDir2 = basename($rootDir);

		if (@file_exists($_fileName) OR @file_exists($rootDir2 . '/' . $_fileName))
		{
			if (!@file_exists($_fileName))
			{
				// This is a directory
				DBSEO::changeDir($rootDir2);

				// Update the filename
				$_fileName = $rootDir2 . '/' . $_fileName;
			}

			// Parse the URL
			$parsedUrl = @parse_url($_SERVER['REQUEST_URI']);

			if ($_POST)
			{
				// Ensure we don't 404
				$_gotUrl = true;
			}
			else
			{
				// Perform a 301-safe redirect
				DBSEO::safeRedirect($_fileName . ($parsedUrl['query'] ? '?' . $parsedUrl['query'] : ''), array(), true);
			}
		}
	}
}

if ($_gotUrlBackup AND !DBSEO_RELPATH)
{
	// Set preprocessed to true
	DBSEO::$config['_preprocessed'] = true;
}

if (
	$_fileName == 'external.php' OR
	$_fileName == 'blog_external.php' OR
	($_fileName == 'ajax.php' AND $_REQUEST['do'] == 'rss')
)
{
	// Set some important flags
	DBSEO::$config['dbtech_dbseo_rewrite_texturls'] =
	DBSEO::$config['dbtech_dbseo_rewrite_external'] =
	DBSEO::$config['_inAjax'] = true;

	ob_start();
	require($_fileName);
	if (!DBSEO::$config['_process'])
	{
		$content = DBSEO::outputHandler(ob_get_clean());
		echo DBSEO::processContent($content);
	}
	die();
}

if (!DBSEO::$config['_process'])
{
	if (
		($_fileName == 'ajax.php') OR (
			isset($_POST) AND (
				isset($_POST['ajax']) OR (
					isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'
				)
			) AND
			preg_match('#(newreply|profile|editpost|showpost|blog_post|blog_ajax|blog_tag|threadtag|group|attachment|visitormessage|picturecomment)\.php$#', $_fileName)
		)
	)
	{
		// We're loading an AJAX script
		DBSEO::$config['_inAjax'] = true;

		// Require the file
		require($_fileName);

		if (!DBSEO::$config['_outputHandled'])
		{
			// We also need to handle the output
			echo DBSEO::outputHandler(ob_get_clean(), false);
		}
		die();
	}
}

if (!$_gotUrl)
{
	if ($_suggestedURL)
	{
		// Redirect to the suggested URL
		DBSEO::safeRedirect(DBSEO_RELPATH . $_suggestedURL);
	}

	if (DBSEO::$config['dbtech_dbseo_notfound_chooser'] == 2)
	{
		// Outside the 404 function in order to ensure globals aren't an issue
		include(DBSEO::$config['dbtech_dbseo_notfound_custom']);
		die();
	}
	else
	{
		// We just flat out didn't find a file
		DBSEO::handle404();
	}
}
else
{
	if (preg_match('#\.(css|php\d?/?|p?html?|txt)$#', $_fileName, $fileType) AND strpos($_fileName, '://') === false)
	{
		if ($fileType[1] == 'css')
		{
			// CSS file, better flag the header
			header('Content-type: text/css');
		}

		if (preg_match('#^(.+)/([^/]+)$#', $_fileName, $match))
		{
			// Directory
			DBSEO::changeDir($match[1]);

			// Set new file name
			$_fileName = $match[2];
		}

		$_filePath = realpath(getcwd() . '/' . $_fileName);
		if (!in_array($_filePath, get_included_files()) AND @file_exists($_filePath))
		{
			if (substr($fileType[1], 0, 2) == 'ph' OR $fileType[1] == 'php')
			{
				// This was a PHP file
				require(getcwd() . '/' . $_fileName);
			}
			else
			{
				// Any other kind of file
				echo file_get_contents(getcwd() . '/' . $_fileName);
			}
		}
		else
		{
			// Avoid infinite loops
			DBSEO::handle404('', true);
		}
	}
	else
	{
		// Invalid file
		DBSEO::handle404('', true);

	}
}