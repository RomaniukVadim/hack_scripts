<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// Blog_tag class

/**
* Handles various functionality for Blog_tag
*/
class DBSEO_Script_Blog_tag
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
		
		if (!DBSEO::$config['dbtech_dbseo_rewrite_blogtag'])
		{
			// We're not rewriting this
			return false;
		}

		if (!count($_GET))
		{
			// We should only redirect if we don't have any GET params
			DBSEO::safeRedirect(DBSEO_Url_Create::create('Blog_BlogTags', $_GET));
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

		if (!DBSEO::$config['dbtech_dbseo_rewrite_blog'])
		{
			// We're not rewriting this
			return $newUrl;
		}

		if (!DBSEO::$config['dbtech_dbseo_rewrite_blogtag'])
		{
			// We're not rewriting this
			return $newUrl;
		}

		if (count($_seoParameters))
		{
			// We're not rewriting this
			return $newUrl;
		}
		
		if ($newUrl = DBSEO_Url_Create::create('Blog_BlogTags', $_seoParameters))
		{
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

		if (!DBSEO::$config['dbtech_dbseo_rewrite_blog'])
		{
			// We're not rewriting this
			return $newUrl;
		}

		if (!DBSEO::$config['dbtech_dbseo_rewrite_blogtag'])
		{
			// We're not rewriting this
			return $newUrl;
		}

		if (count($_seoParameters))
		{
			// We're not rewriting this
			return $newUrl;
		}

		return DBSEO_Url_Create::create('Blog_BlogTags', $_seoParameters);
	}
}
?>