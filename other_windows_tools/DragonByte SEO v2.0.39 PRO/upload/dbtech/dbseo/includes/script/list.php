<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// Blog class

/**
* Handles various functionality for Image
*/
class DBSEO_Script_List
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
		if (!DBSEO::$config['dbtech_dbseo_rewrite_cms'])
		{
			// We're not rewriting this
			return false;
		}

		if (preg_match('#^([^/]*?\.php)/(.+)$#', $url, $matches))
		{
			$_cmsUrlAppend = $matches[2];
		}		

		if ($_redirectUrl = DBSEO_Url_Create::createCMS($_GET[DBSEO::$config['route_requestvar']] ? $_GET[DBSEO::$config['route_requestvar']] : $_cmsUrlAppend, '', $_GET))
		{
			// Pop round to the new URL
			DBSEO::safeRedirect($_redirectUrl, array(DBSEO::$config['route_requestvar'], 'page'));
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

		if (!DBSEO::$config['dbtech_dbseo_rewrite_cms'])
		{
			// We're not rewriting this
			return $newUrl;
		}

		if ($newUrl = DBSEO_Url_Create::createCMS($_seoParameters[DBSEO::$config['route_requestvar']] ? $_seoParameters[DBSEO::$config['route_requestvar']] : $_cmsUrlAppend, '', $_seoParameters))
		{
			$_urlScript = $newUrl;
			unset($_seoParameters[DBSEO::$config['route_requestvar']], $_seoParameters['page']);
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

		if (!DBSEO::$config['dbtech_dbseo_rewrite_cms'])
		{
			// We're not rewriting this
			return $newUrl;
		}

		return DBSEO_Url_Create::createCMS($_seoParameters[DBSEO::$config['route_requestvar']] ? $_seoParameters[DBSEO::$config['route_requestvar']] : $_cmsUrlAppend, '', $_seoParameters);
	}
}
?>