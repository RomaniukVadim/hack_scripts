<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// Memberlist class

/**
* Handles various functionality for Memberlist
*/
class DBSEO_Script_Memberlist
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
		
		if (!DBSEO::$config['dbtech_dbseo_rewrite_memberlist'])
		{
			// We're not rewriting this
			return false;
		}

		if (in_array($_REQUEST['do'], array('search', 'process')))
		{
			// Don't rewrite these URLs
			return false;
		}

		if ($_GET['ltr'])
		{
			// Browsing by letter
			$_urlFormat = 'MemberList_MemberList_Letter';
		}
		else
		{
			// Normal / paginated
			$_urlFormat = 'MemberList_MemberList' . ($_GET['page'] > 1 ? '_Page' : '');
		}

		if ($_urlFormat AND $_redirectUrl = DBSEO_Url_Create::create($_urlFormat, $_GET))
		{
			$paramsToStrip = array('ltr', 'do', 'page');
			
			if ($_GET['sort'] == 'username')
			{
				// No need to include this if we're sorting by username
				$paramsToStrip[] = 'sort';
			}

			if ($_GET['order'] == 'asc')
			{
				// No need to include this if we're sorting in ascending order
				$paramsToStrip[] = 'order';
			}

			// We had a redirect URL, so get to it!							
			DBSEO::safeRedirect($_redirectUrl, $paramsToStrip);
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
		
		if (!DBSEO::$config['dbtech_dbseo_rewrite_memberlist'])
		{
			// We're not rewriting this
			return $newUrl;
		}

		if (isset($_seoParameters['do']))
		{
			// We're not rewriting this
			return $newUrl;
		}

		if (isset($_seoParameters['ltr']))
		{
			// Browsing by letter
			$_urlFormat = 'MemberList_MemberList_Letter';
		}
		else
		{
			// Normal / paginated
			$_urlFormat = 'MemberList_MemberList' . ($_seoParameters['page'] > 1 ? '_Page' : '');
		}

		if ($_urlFormat AND $newUrl = DBSEO_Url_Create::create($_urlFormat, $_seoParameters))
		{
			// We had a valid URL			
			$_urlScript = $newUrl;

			// Back this up
			$_seoParameters2 = $_seoParameters;

			foreach(array('ltr', 'pp', 'sort', 'order', 'do', 'page') as $parameter)
			{
				// Unset from the backup
				unset($_seoParameters2[$parameter]);
			}

			if (
				(!isset($_seoParameters['sort']) OR $_seoParameters['sort'] == 'username') AND 
				(!isset($_seoParameters['order']) OR stripos($_seoParameters['order'], 'asc') !== false) AND 
				!count($_seoParameters2)
			)
			{
				// Just toss all of em
				$_removeAllParameters = true;
			}
			else
			{
				// Toss a few select ones
				unset($_seoParameters['ltr'], $_seoParameters['do'], $_seoParameters['page']);
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
		
		if (!DBSEO::$config['dbtech_dbseo_rewrite_memberlist'])
		{
			// We're not rewriting this
			return $newUrl;
		}

		if (isset($_seoParameters['do']))
		{
			// We're not rewriting this
			return $newUrl;
		}

		if (isset($_seoParameters['ltr']))
		{
			// Browsing by letter
			$_urlFormat = 'MemberList_MemberList_Letter';
		}
		else
		{
			// Normal / paginated
			$_urlFormat = 'MemberList_MemberList' . ($_seoParameters['page'] > 1 ? '_Page' : '');
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