<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// Forumdisplay class

/**
* Handles various functionality for Forumdisplay
*/
class DBSEO_Script_Forumdisplay
{
	/**
	 * Checks for and redirects to proper URLs if needed
	 *
	 * @param string $url
	 * @param boolean $fileExists
	 * @param boolean $fileExistsDeep
	 * 
	 * @return mixed
	 */
	public static function redirectUrl(&$url, &$fileExists, &$fileExistsDeep)
	{
		$_redirectUrl = $_urlFormat = '';
		
		if (!DBSEO::$config['dbtech_dbseo_rewrite_forum'] OR in_array($_GET['do'], array('doenterpwd', 'markread')))
		{
			// We're not rewriting this
			return false;
		}

		$_forumId = intval($_GET['f'] ? $_GET['f'] : $_GET['forumid']);

		$paramsToStrip = array('f', 'forumid', 'page');
		if ($_forumId == 'home')
		{
			// Forum home
			$_urlFormat = '';
		}
		else if (preg_match('#^\d+$#', $_forumId))
		{
			// Grab our forum cache
			$forumcache = DBSEO::$db->fetchForumCache();

			if (!$_GET['daysprune'] OR $forumcache[$_forumId]['daysprune'] == $_GET['daysprune'])
			{
				// Strip daysprune as it's default
				$paramsToStrip[] = 'daysprune';
			}

			if (!$_GET['sort'] OR $forumcache[$_forumId]['defaultsortfield'] == $_GET['sort'])
			{
				// Strip lastpost
				$paramsToStrip[] = 'sort';
			}

			if (!$_GET['order'] OR $forumcache[$_forumId]['defaultsortorder'] == $_GET['order'])
			{
				// Strip lastpost
				$paramsToStrip[] = 'order';
			}

			if (!$_GET['pp'] OR DBSEO::$config['maxthreads'] == $_GET['pp'])
			{
				// Strip lastpost
				$paramsToStrip[] = 'pp';
			}

			if ($_GET['prefixid'])
			{
				// Set ze format
				$_urlFormat = 'Forum_Forum_Prefix' . ($_GET['page'] > 1 ? '_Page' : '');
			}
			else
			{
				// Set ze format
				$_urlFormat = 'Forum_Forum' . ($_GET['page'] > 1 ? '_Page' : '');
			}

			// Always strip this now
			$paramsToStrip[] = 'prefixid';
		}

		if ($_urlFormat AND $_redirectUrl = DBSEO_Url_Create::create($_urlFormat, $_GET))
		{
			// Git to it
			DBSEO::safeRedirect($_redirectUrl, $paramsToStrip);
		}
		else if (!$_redirectUrl OR strpos($_redirectUrl, 'forumdisplay.php') !== false)
		{
			return 'forumdisplay.php';
		}

		return true;
	}

	/**
	 * Replace urls
	 *
	 * @param string $urlPrefix
	 * @param string $url
	 * @param string $urlAttributes
	 * @param string $urlSuffix
	 * @param string $inTag
	 * @param string $closeTag
	 * 
	 * @return string
	 */
	public static function replaceUrls(&$_preventProcessing, &$_seoParameters, &$urlPrefix, &$url, &$urlSuffix, &$inTag, &$_urlScript, &$_urlPlace, &$_urlParameters, &$_removeAllParameters, &$_cmsUrlAppend, &$nofollow, &$follow)
	{
		$newUrl = $_urlFormat = '';

		if (!DBSEO::$config['dbtech_dbseo_rewrite_forum'] OR in_array($_seoParameters['do'], array('doenterpwd', 'markread')))
		{
			// We're not rewriting this
			return $newUrl;
		}

		// Ensure we have the forum ID
		$_seoParameters['forumid'] = $_seoParameters['f'] ? $_seoParameters['f'] : $_seoParameters['forumid'];

		if ($_seoParameters['forumid'])
		{
			if ($_seoParameters['prefixid'])
			{
				// Set ze format
				$_urlFormat = 'Forum_Forum_Prefix' . ((isset($_seoParameters['page']) AND $_seoParameters['page'] > 1) ? '_Page' : '');
			}
			else
			{
				// Set ze format
				$_urlFormat = 'Forum_Forum' . ((isset($_seoParameters['page']) AND $_seoParameters['page'] > 1) ? '_Page' : '');
			}
		}

		if ($_urlFormat)
		{
			// Git to it
			$newUrl = DBSEO_Url_Create::create($_urlFormat, $_seoParameters);
		}
		else
		{
			// We had no URL
			$_preventProcessing = true;
		}

		// Grab our forum info
		$forumInfo = DBSEO::$db->cache['forumcache'][$_seoParameters['forumid']];

		if (!$_seoParameters['daysprune'] OR $forumInfo['daysprune'] == $_seoParameters['daysprune'])
		{
			// Strip daysprune as it's default
			unset($_seoParameters['daysprune']);
		}

		if (!$_seoParameters['sort'] OR $forumInfo['defaultsortfield'] == $_seoParameters['sort'])
		{
			// Strip lastpost
			unset($_seoParameters['sort']);
		}

		if (!$_seoParameters['order'] OR $forumInfo['defaultsortorder'] == $_seoParameters['order'])
		{
			// Strip lastpost
			unset($_seoParameters['order']);
		}

		/*
		if (
			(!isset($_seoParameters['sort']) OR $_seoParameters['sort'] == ($forumInfo['defaultsortfield'] ? $forumInfo['defaultsortfield'] : 'lastpost')) AND 
			(!isset($_seoParameters['order']) OR $_seoParameters['order'] == ($forumInfo['defaultsortorder'] ? $forumInfo['defaultsortorder'] : 'desc')) AND 
			!isset($_seoParameters['do'])
		)
		{
			// Get rid of these parameters
			unset($_seoParameters['order'], $_seoParameters['do'], $_seoParameters['sort']);
		}
		*/

		if ($forumInfo['link'])
		{
			// Extract the HTTP host from the link
			preg_match('#(([^\.]+\.)?[^\.]+)$#', DBSEO_HTTP_HOST, $matches);

			if (!preg_match('#^[^/]*://[^/]*' . preg_quote($matches[1], '#') . '#', $_urlScript))
			{
				// Sort out external link tracking
				DBSEO::trackExternalLink($urlPrefix, $_urlScript, $urlSuffix);
			}

			// Set this
			$newUrl = $forumInfo['link'];

			// We've now properly processed things
			$_preventProcessing = true;
			$_urlParameters = '';
		}

		if ($newUrl)
		{
			// Set the URL script
			$_urlScript = $newUrl;

			if (
				isset($_seoParameters['daysprune']) AND 
				$forumInfo['daysprune'] == $_seoParameters['daysprune'] AND 
				$GLOBALS['vbulletin']->userinfo['daysprune'] == $_seoParameters['daysprune']
			)
			{
				// We don't need daysprune anymore
				unset($_seoParameters['daysprune']);
			}

			// Get rid of the 
			unset($_seoParameters['pp'], $_seoParameters['f'], $_seoParameters['forumid'], $_seoParameters['page'], $_seoParameters['prefixid']);
		}

		return $newUrl;
	}

	/**
	 * Create URL
	 *
	 * @param string $_seoParameters
	 * 
	 * @return string
	 */
	public static function createUrl($_seoParameters)
	{
		$newUrl = $_urlFormat = '';

		if (!DBSEO::$config['dbtech_dbseo_rewrite_forum'])
		{
			// We're not rewriting this
			return $newUrl;
		}

		// Ensure we have the forum ID
		$_seoParameters['forumid'] = $_seoParameters['f'] ? $_seoParameters['f'] : $_seoParameters['forumid'];

		if ($_seoParameters['forumid'])
		{
			if ($_seoParameters['prefixid'])
			{
				// Set ze format
				$_urlFormat = 'Forum_Forum_Prefix' . ((isset($_seoParameters['page']) AND $_seoParameters['page'] > 1) ? '_Page' : '');
			}
			else
			{
				// Set ze format
				$_urlFormat = 'Forum_Forum' . ((isset($_seoParameters['page']) AND $_seoParameters['page'] > 1) ? '_Page' : '');
			}
		}

		if (!$_urlFormat)
		{
			// We're not rewriting this
			return $newUrl;
		}

		return DBSEO_Url_Create::create($_urlFormat, $_seoParameters);
	}
}
?>