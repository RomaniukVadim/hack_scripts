<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// Printthread class

/**
* Handles various functionality for Printthread
*/
class DBSEO_Script_Printthread
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
		
		if (!DBSEO::$config['dbtech_dbseo_rewrite_printthread'])
		{
			// We're not rewriting this
			return false;
		}

		if (!isset($_REQUEST['do']))
		{
			if (isset($_GET['page']) AND $_GET['page'] > 1)
			{
				// Paginated
				$_urlFormat = 'Thread_PrintThread_Page';
			}
			else
			{
				// Normal
				$urlFormat = 'Thread_PrintThread';
			}
		}

		if ($_urlFormat AND $_redirectUrl = DBSEO_Url_Create::create($_urlFormat, $_GET))
		{
			// We had a redirect URL, so get to it!							
			DBSEO::safeRedirect($_redirectUrl, array('', 't', 'threadid', 'postid', 'page', 'viewfull', ($_GET['pp'] == DBSEO::$config['maxposts']) ? 'pp' : ''));
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
		
		if (!DBSEO::$config['dbtech_dbseo_rewrite_printthread'])
		{
			// We're not rewriting this
			return $newUrl;
		}

		if (isset($_seoParameters['do']))
		{
			// We're not rewriting this
			return $newUrl;
		}

		if (isset($_seoParameters['page']) AND $_seoParameters['page'] > 1)
		{
			// Paginated
			$_urlFormat = 'Thread_PrintThread_Page';
		}
		else
		{
			// Normal
			$urlFormat = 'Thread_PrintThread';
		}

		if ($_urlFormat AND $newUrl = DBSEO_Url_Create::create($_urlFormat, $_seoParameters))
		{
			// We got it!
			$_urlScript = $newUrl;

			// Get rid of some common elements
			unset($_seoParameters['t'], $_seoParameters['page']);

			if ($_seoParameters['pp'] == DBSEO::$config['maxposts'])
			{
				// We can get rid of perpage too
				unset($_seoParameters['pp']);
			}

			if (DBSEO::$config['dbtech_dbseo_rewrite_printthread_nofollow'])
			{
				// We're adding nofollow
				$nofollow = true; 
			}
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
		
		if (!DBSEO::$config['dbtech_dbseo_rewrite_printthread'])
		{
			// We're not rewriting this
			return $newUrl;
		}

		if (isset($_seoParameters['do']))
		{
			// We're not rewriting this
			return $newUrl;
		}

		if (isset($_seoParameters['page']) AND $_seoParameters['page'] > 1)
		{
			// Paginated
			$_urlFormat = 'Thread_PrintThread_Page';
		}
		else
		{
			// Normal
			$urlFormat = 'Thread_PrintThread';
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