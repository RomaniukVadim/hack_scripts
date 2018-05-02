<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// Announcement class

/**
* Handles various functionality for Announcement
*/
class DBSEO_Script_Announcement
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
		
		if (!DBSEO::$config['dbtech_dbseo_rewrite_announcement'])
		{
			// We're not rewriting this
			return false;
		}

		if (isset($_GET['do']))
		{
			// Not rewriting anything with do in it
			return false;
		}

		// We need to sort these out atm
		$_forumId 			= intval($_GET['f'] ? $_GET['f'] : $_GET['forumid']);
		$_announcementId 	= intval($_GET['a'] ? $_GET['a'] : $_GET['announcementid']);

		if (!$_forumId AND $_announcementId)
		{
			// Single announcement
			$_urlFormat = 'Announcement_Announcement';
		}
		else
		{
			// All announcements in a forum
			$_urlFormat = 'Announcement_Announcement_Multiple';
		}

		if ($_urlFormat AND $_redirectUrl = DBSEO_Url_Create::create($_urlFormat, $_GET))
		{
			// Git to it
			DBSEO::safeRedirect($_redirectUrl, array('f', 'forumid', 'a', 'announcementid'));
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

		if (!DBSEO::$config['dbtech_dbseo_rewrite_announcement'])
		{
			// We're not rewriting this
			return false;
		}

		// Ensure we have the forum ID
		$_seoParameters['forumid'] = $_seoParameters['f'] ? $_seoParameters['f'] : $_seoParameters['forumid'];

		if (!$_seoParameters['forumid'] AND $_seoParameters['announcementid'])
		{
			// Single announcement
			$_urlFormat = 'Announcement_Announcement';
		}
		else
		{
			// All announcements in a forum
			$_urlFormat = 'Announcement_Announcement_Multiple';
		}

		if ($_urlFormat)
		{
			// Git to it
			$newUrl = DBSEO_Url_Create::create($_urlFormat, $_seoParameters);
		}

		if ($newUrl)
		{
			// We had a valid URL
			$_urlScript = $newUrl;
			$_removeAllParameters = true; 
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

		if (!DBSEO::$config['dbtech_dbseo_rewrite_announcement'])
		{
			// We're not rewriting this
			return false;
		}

		// Ensure we have the forum ID
		$_seoParameters['forumid'] = $_seoParameters['f'] ? $_seoParameters['f'] : $_seoParameters['forumid'];

		if (!$_seoParameters['forumid'] AND $_seoParameters['announcementid'])
		{
			// Single announcement
			$_urlFormat = 'Announcement_Announcement';
		}
		else
		{
			// All announcements in a forum
			$_urlFormat = 'Announcement_Announcement_Multiple';
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