<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// Blog class

/**
* Handles various functionality for Image
*/
class DBSEO_Script_Image
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
		if (!DBSEO::$config['dbtech_dbseo_rewrite_avatar'])
		{
			// We're not rewriting this
			return false;
		}

		if ($_GET['type'])
		{
			// We're not rewriting this
			return false;
		}

		if (!(isset($_GET['u']) AND (!isset($_GET['type']) OR $_GET['type'] != 'profile')))
		{
			// We're not rewriting this
			return false;
		}

		if ($_redirectUrl = DBSEO_Url_Create::create('Avatar_Avatar', $_GET))
		{
			// Pop round to the new URL
			DBSEO::safeRedirect($_redirectUrl, array('u'));
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

		if (!DBSEO::$config['dbtech_dbseo_rewrite_avatar'])
		{
			// We're not rewriting this
			return $newUrl;
		}

		if ($_seoParameters['type'])
		{
			// We're not rewriting this
			return $newUrl;
		}

		if (!(isset($_seoParameters['u']) AND (!isset($_seoParameters['type']) OR $_seoParameters['type'] != 'profile')))
		{
			// We're not rewriting this
			return $newUrl;
		}

		if ($newUrl = DBSEO_Url_Create::create('Avatar_Avatar', $_seoParameters))
		{
			$_urlScript = $newUrl;
			unset($_seoParameters['u']);
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

		if (!DBSEO::$config['dbtech_dbseo_rewrite_avatar'])
		{
			// We're not rewriting this
			return $newUrl;
		}

		if ($_seoParameters['type'])
		{
			// We're not rewriting this
			return $newUrl;
		}

		if (!(isset($_seoParameters['u']) AND (!isset($_seoParameters['type']) OR $_seoParameters['type'] != 'profile')))
		{
			// We're not rewriting this
			return $newUrl;
		}

		return DBSEO_Url_Create::create('Avatar_Avatar', $_seoParameters);
	}
}
?>