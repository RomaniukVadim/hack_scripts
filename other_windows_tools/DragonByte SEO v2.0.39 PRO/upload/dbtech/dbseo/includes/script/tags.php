<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// Blog class

/**
* Handles various functionality for Image
*/
class DBSEO_Script_Tags
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

		if (!DBSEO::$config['dbtech_dbseo_rewrite_tags'])
		{
			// We're not rewriting this
			return false;
		}

		if ($_GET['tag'])
		{
			// Single tag
			$_urlFormat = 'Tags_Tag_Single' . ($_GET['page'] > 1 ? '_Page' : '');
		}
		else if (!count($_GET))
		{
			// List of all tags
			$_urlFormat = 'Tags_TagList';
		}

		if ($_urlFormat AND $_redirectUrl = DBSEO_Url_Create::create($_urlFormat, $_GET))
		{
			// We had a redirect URL, so get to it!							
			DBSEO::safeRedirect($_redirectUrl, array('', 'tag', 'page'));
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

		if (!DBSEO::$config['dbtech_dbseo_rewrite_tags'])
		{
			// We're not rewriting this
			return $newUrl;
		}

		if ($_seoParameters['tag'])
		{
			// Single tag
			$_urlFormat = 'Tags_Tag_Single' . ($_seoParameters['page'] > 1 ? '_Page' : '');
		}
		else if (!count($_seoParameters))
		{
			// List of all tags
			$_urlFormat = 'Tags_TagList';
		}

		if ($_urlFormat AND $newUrl = DBSEO_Url_Create::create($_urlFormat, $_seoParameters))
		{
			// And we're done here
			$_urlScript = $newUrl;
			unset($_seoParameters['tag'], $_seoParameters['page']);
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

		if (!DBSEO::$config['dbtech_dbseo_rewrite_tags'])
		{
			// We're not rewriting this
			return $newUrl;
		}

		if ($_seoParameters['tag'])
		{
			// Single tag
			$_urlFormat = 'Tags_Tag_Single' . ($_seoParameters['page'] > 1 ? '_Page' : '');
		}
		else if (!count($_seoParameters))
		{
			// List of all tags
			$_urlFormat = 'Tags_TagList';
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