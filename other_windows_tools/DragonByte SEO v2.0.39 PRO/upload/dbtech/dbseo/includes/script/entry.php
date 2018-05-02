<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// Blog class

/**
* Handles various functionality for Blog
*/
class DBSEO_Script_Entry extends DBSEO_Script_Blog
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
		if (!$_GET['b'])
		{
			// Blog entry file without a blog ID
			return '';
		}

		return parent::redirectUrl($url, $fileExists, $fileExistsDeep);
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
		if (!$_seoParameters['b'])
		{
			// Blog entry file without a blog ID
			return '';
		}

		// Call the parent
		return parent::replaceUrls($_preventProcessing, $_seoParameters, $urlPrefix, $url, $urlSuffix, $inTag, $_urlScript, $_urlPlace, $_urlParameters, $_removeAllParameters, $_cmsUrlAppend, $nofollow, $follow);
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
		if (!$_seoParameters['b'])
		{
			// Blog entry file without a blog ID
			return '';
		}

		// Call the parent
		return parent::createUrl($_seoParameters);
	}
}
?>