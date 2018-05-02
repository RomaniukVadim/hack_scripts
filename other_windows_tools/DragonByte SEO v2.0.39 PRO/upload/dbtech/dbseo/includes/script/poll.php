<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// Blog class

/**
* Handles various functionality for Poll
*/
class DBSEO_Script_Poll
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
		if (!DBSEO::$config['dbtech_dbseo_rewrite_poll'])
		{
			// We're not rewriting this
			return false;
		}

		if (!isset($_GET['do']) OR $_GET['do'] != 'showresults')
		{
			// We're not rewriting this
			return false;
		}

		if ($_redirectUrl = DBSEO_Url_Create::create('Poll_Poll', $_GET))
		{
			// Pop round to the new URL
			DBSEO::safeRedirect($_redirectUrl, array());
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
		$newUrl = $_urlFormat = 'Poll_Poll';

		if (!DBSEO::$config['dbtech_dbseo_rewrite_poll'])
		{
			// We're not rewriting this
			return $newUrl;
		}

		if (!isset($_seoParameters['do']) OR $_seoParameters['do'] != 'showresults')
		{
			// We're not rewriting this
			return $newUrl;
		}

		if ($_urlFormat AND $newUrl = DBSEO_Url_Create::create($_urlFormat, $_seoParameters))
		{
			// We had a URL
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

		if (!DBSEO::$config['dbtech_dbseo_rewrite_poll'])
		{
			// We're not rewriting this
			return $newUrl;
		}

		if (!isset($_seoParameters['do']) OR $_seoParameters['do'] != 'showresults')
		{
			// We're not rewriting this
			return $newUrl;
		}

		return DBSEO_Url_Create::create('Poll_Poll', $_seoParameters);
	}
}
?>