<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// Attachment class

/**
* Handles various functionality for Attachment
*/
class DBSEO_Script_Attachment
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
		
		if (!$_REQUEST['attachmentid'])
		{
			// Wrong attachment ID
			return false;
		}

		// Grab attachment info
		$attachmentInfo = DBSEO_Rewrite_Attachment::getInfo($_REQUEST['attachmentid']);

		switch (DBSEO::getContentType($attachmentInfo))
		{
			case 'album':
				if (DBSEO::$config['dbtech_dbseo_rewrite_album'])
				{
					// We're rewriting album URLs
					$_urlFormat = 'Album_AlbumPictureFile';
				}
				break;

			case 'group':
				if (DBSEO::$config['dbtech_dbseo_rewrite_socialgroup'])
				{
					// Social group file
					$_urlFormat = 'SocialGroup_SocialGroupPictureFile';
				}
				break;

			case 'blog':
				if (DBSEO::$config['dbtech_dbseo_rewrite_blogattachment'])
				{
					// We're rewriting blog attachments
					$_urlFormat = 'Attachment_BlogAttachment';
				}
				break;

			case 'cms_article':
				if (DBSEO::$config['dbtech_dbseo_rewrite_attachment'])
				{
					// CMS Attachments
					$_urlFormat = 'Attachment_CMSAttachments';
				}
				break;

			default:
				if (DBSEO::$config['dbtech_dbseo_rewrite_attachment'])
				{
					// Plain ol' attachment
					$_urlFormat = 'Attachment_Attachment';
				}
				break;
		}
		
		if ($_urlFormat AND $_redirectUrl = DBSEO_Url_Create::create($_urlFormat, $_GET))
		{
			// We had a redirect URL, so get to it!							
			DBSEO::safeRedirect($_redirectUrl, array('', 'attachmentid', 'thumb', 'd'));
		}

		return 'attachment.php';
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
		$newUrl = '';

		if (!$_seoParameters['attachmentid'])
		{
			// We're not rewriting this
			return $newUrl;
		}

		// Grab attachment info
		$attachmentInfo = DBSEO_Rewrite_Attachment::getInfo($_seoParameters['attachmentid']);

		switch (DBSEO::getContentType($attachmentInfo))
		{
			case 'album':
				if (DBSEO::$config['dbtech_dbseo_rewrite_album'])
				{
					// We're rewriting album URLs
					$_urlFormat = 'Album_AlbumPictureFile';
				}
				break;

			case 'group':
				if (DBSEO::$config['dbtech_dbseo_rewrite_socialgroup'])
				{
					// Social group file
					$_urlFormat = 'SocialGroup_SocialGroupPictureFile';
				}
				break;

			case 'blog':
				if (DBSEO::$config['dbtech_dbseo_rewrite_attachment_alt'] /*AND $_seoParameters['thumb']*/ AND ($newAlt = DBSEO_Url_Create::create('Attachment_Attachment_Alt', $_seoParameters)))
				{
					// Rewrite the alt attribute
					$urlSuffix = preg_replace('#(alt=)"[^"]*#is','$1"' . str_replace('"', '&quot;', $newAlt), $urlSuffix);
				}

				if (DBSEO::$config['dbtech_dbseo_rewrite_blogattachment'])
				{
					// We're rewriting blog attachments
					$_urlFormat = 'Attachment_BlogAttachment';
				}
				break;

			case 'cms_article':
				if (DBSEO::$config['dbtech_dbseo_rewrite_attachment_alt'] /*AND $_seoParameters['thumb']*/ AND ($newAlt = DBSEO_Url_Create::create('Attachment_Attachment_Alt', $_seoParameters)))
				{
					// Rewrite the alt attribute
					$urlSuffix = preg_replace('#(alt=)"[^"]*#is','$1"' . str_replace('"', '&quot;', $newAlt), $urlSuffix);
				}

				if (DBSEO::$config['dbtech_dbseo_rewrite_attachment'])
				{
					// CMS Attachments
					$_urlFormat = 'Attachment_CMSAttachments';
				}
				break;

			default:
				if (DBSEO::$config['dbtech_dbseo_rewrite_attachment_alt'] /*AND $_seoParameters['thumb']*/ AND ($newAlt = DBSEO_Url_Create::create('Attachment_Attachment_Alt', $_seoParameters)))
				{
					// Rewrite the alt attribute
					$urlSuffix = preg_replace('#(alt=)"[^"]*#is','$1"' . str_replace('"', '&quot;', $newAlt), $urlSuffix);
				}

				if (DBSEO::$config['dbtech_dbseo_rewrite_attachment'])
				{
					// Plain ol' attachment
					$_urlFormat = 'Attachment_Attachment';
				}
				break;
		}

		if ($_urlFormat AND $newUrl = DBSEO_Url_Create::create($_urlFormat, $_seoParameters))
		{
			// Rewrite the main attachment URL
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
		$newUrl = '';

		if (!$_seoParameters['attachmentid'])
		{
			// We're not rewriting this
			return $newUrl;
		}

		// Grab attachment info
		$attachmentInfo = DBSEO_Rewrite_Attachment::getInfo($_seoParameters['attachmentid']);

		switch (DBSEO::getContentType($attachmentInfo))
		{
			case 'album':
				if (DBSEO::$config['dbtech_dbseo_rewrite_album'])
				{
					// We're rewriting album URLs
					$_urlFormat = 'Album_AlbumPictureFile';
				}
				break;

			case 'group':
				if (DBSEO::$config['dbtech_dbseo_rewrite_socialgroup'])
				{
					// Social group file
					$_urlFormat = 'SocialGroup_SocialGroupPictureFile';
				}
				break;

			case 'blog':
				if (DBSEO::$config['dbtech_dbseo_rewrite_attachment_alt'] AND $_seoParameters['thumb'] AND ($newAlt = DBSEO_Url_Create::create('Attachment_Attachment_Alt', $_seoParameters)))
				{
					// Rewrite the alt attribute
					$urlSuffix = preg_replace('#(alt=)"[^"]*#is','$1"' . str_replace('"', '&quot;', $newAlt), $urlSuffix);
				}

				if (DBSEO::$config['dbtech_dbseo_rewrite_blogattachment'])
				{
					// We're rewriting blog attachments
					$_urlFormat = 'Attachment_Attachment_BlogAttachment';
				}
				break;

			case 'cms_article':
				if (DBSEO::$config['dbtech_dbseo_rewrite_attachment_alt'] AND $_seoParameters['thumb'] AND ($newAlt = DBSEO_Url_Create::create('Attachment_Attachment_Alt', $_seoParameters)))
				{
					// Rewrite the alt attribute
					$urlSuffix = preg_replace('#(alt=)"[^"]*#is','$1"' . str_replace('"', '&quot;', $newAlt), $urlSuffix);
				}

				if (DBSEO::$config['dbtech_dbseo_rewrite_attachment'])
				{
					// CMS Attachments
					$_urlFormat = 'Attachment_Attachment_CMSAttachments';
				}
				break;

			default:
				if (DBSEO::$config['dbtech_dbseo_rewrite_attachment_alt'] AND $_seoParameters['thumb'] AND ($newAlt = DBSEO_Url_Create::create('Attachment_Attachment_Alt', $_seoParameters)))
				{
					// Rewrite the alt attribute
					$urlSuffix = preg_replace('#(alt=)"[^"]*#is','$1"' . str_replace('"', '&quot;', $newAlt), $urlSuffix);
				}

				if (DBSEO::$config['dbtech_dbseo_rewrite_attachment'])
				{
					// Plain ol' attachment
					$_urlFormat = 'Attachment_Attachment';
				}
				break;
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