<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// Blog class

/**
* Handles various functionality for Image
*/
class DBSEO_Script_Blog_external
{
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

		if (!DBSEO::$config['dbtech_dbseo_rewrite_blogfeed'])
		{
			// We're not rewriting this
			return $newUrl;
		}

		if ($_urlFormat AND $newUrl = DBSEO_Url_Create::create('Blog_BlogFeed' . ($_seoParameters['bloguserid'] ? 'User' : 'Global'), $_seoParameters))
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

		if (!DBSEO::$config['dbtech_dbseo_rewrite_blog'])
		{
			// We're not rewriting this
			return $newUrl;
		}

		if (!DBSEO::$config['dbtech_dbseo_rewrite_blogfeed'])
		{
			// We're not rewriting this
			return $newUrl;
		}

		return DBSEO_Url_Create::create('Blog_BlogFeed' . ($_seoParameters['bloguserid'] ? 'User' : 'Global'), $_seoParameters);
	}
}
?>