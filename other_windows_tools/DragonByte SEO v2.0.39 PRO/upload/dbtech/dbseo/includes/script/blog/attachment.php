<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// Blog_attachment class

/**
* Handles various functionality for Blog_attachment
*/
class DBSEO_Script_Blog_attachment
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
		
		if (!DBSEO::$config['dbtech_dbseo_rewrite_blogattachment'])
		{
			// We're not rewriting this
			return false;
		}

		if (!$_REQUEST['attachmentid'])
		{
			// We're not trying to get any attachments
			return false;
		}

		if ($_redirectUrl = DBSEO_Url_Create::create('Attachment_BlogAttachment', $_GET))
		{
			// Redirect to the correct URL
			DBSEO_Url::adjust($_redirectUrl, array('attachmentid', 'd', 'thumb', 'stc'), false);
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

		if (!DBSEO::$config['dbtech_dbseo_rewrite_blogattachment'])
		{
			// We're not rewriting this
			return $newUrl;
		}

		if (!$_seoParameters['attachmentid'])
		{
			// We're not rewriting this
			return $newUrl;
		}

		if ($newUrl = DBSEO_Url_Create::create('Attachment_BlogAttachment', $_seoParameters))
		{
			// Git to it
			$_urlScript = $newUrl;
			unset($_seoParameters['attachmentid'], $_seoParameters['stc'], $_seoParameters['d'], $_seoParameters['thumb']);
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

		if (!DBSEO::$config['dbtech_dbseo_rewrite_blogattachment'])
		{
			// We're not rewriting this
			return $newUrl;
		}

		if (!$_seoParameters['attachmentid'])
		{
			// We're not rewriting this
			return $newUrl;
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