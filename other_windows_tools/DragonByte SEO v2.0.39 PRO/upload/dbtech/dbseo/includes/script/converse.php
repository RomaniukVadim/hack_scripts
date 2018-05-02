<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// Converse class

/**
* Handles various functionality for Converse
*/
class DBSEO_Script_Converse
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
		
		if (!DBSEO::$config['dbtech_dbseo_rewrite_memberprofile'])
		{
			// We're not rewriting this
			return false;
		}

		$_urlFormat = 'MemberProfile_VisitorMessage_Conversation' . ($_GET['page'] > 1 ? '_Page' : '');
		if ($_urlFormat AND $_redirectUrl = DBSEO_Url_Create::create($_urlFormat, $_GET))
		{
			// Git to it
			DBSEO::safeRedirect($_redirectUrl, array('u', 'u2', 'page', 'vmid'));
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

		if (!DBSEO::$config['dbtech_dbseo_rewrite_memberprofile'])
		{
			// We're not rewriting this
			return $newUrl;
		}

		if (count($_seoParameters))
		{
			// We're not rewriting this
			return $newUrl;
		}

		if ($newUrl = DBSEO_Url_Create::create('MemberProfile_VisitorMessage_Conversation' . ($_seoParameters['page'] > 1 ? '_Page' : ''), $_seoParameters))
		{
			$_urlScript = $newUrl;
			unset($_seoParameters['u'], $_seoParameters['u2'], $_seoParameters['page']);
		}

		return $newUrl;
	}

	/**
	 * Create URL
	 *
	 * @param string $urlPrefix
	 * 
	 * @return string
	 */
	public static function createUrl($_seoParameters)
	{
		$newUrl = $_urlFormat = '';

		if (!DBSEO::$config['dbtech_dbseo_rewrite_memberprofile'])
		{
			// We're not rewriting this
			return $newUrl;
		}

		if (count($_seoParameters))
		{
			// We're not rewriting this
			return $newUrl;
		}

		return DBSEO_Url_Create::create('MemberProfile_VisitorMessage_Conversation' . ($_seoParameters['page'] > 1 ? '_Page' : ''), $_seoParameters);
	}
}
?>