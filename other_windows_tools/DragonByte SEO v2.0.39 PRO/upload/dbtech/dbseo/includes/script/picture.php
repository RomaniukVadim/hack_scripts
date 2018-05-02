<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// Picture class

/**
* Handles various functionality for Picture
*/
class DBSEO_Script_Picture
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
		
		if (!DBSEO::$config['dbtech_dbseo_rewrite_album'] AND !DBSEO::$config['dbtech_dbseo_rewrite_socialgroup'])
		{
			// We're not rewriting this
			return false;
		}

		if (isset($_GET['do']))
		{
			// Not rewriting anything with do in it
			return false;
		}

		if (!isset($_GET[DBSEO::$config['_pictureid']]))
		{
			// We need picture ID
			return false;
		}

		// Store object ID
		DBSEO::$cache['_objectIds'][DBSEO::$config['_picturestorage']][] = $_GET[DBSEO::$config['_pictureid']];

		if (DBSEO::$config['dbtech_dbseo_rewrite_album'] AND isset($_GET['albumid']))
		{
			// Album picture file
			$_urlFormat = 'Album_AlbumPictureFile';

			// Store object ID
			DBSEO::$cache['_objectIds']['album'][] = $_GET['albumid'];
		}
		else if (DBSEO::$config['dbtech_dbseo_rewrite_socialgroup'] AND isset($_GET['groupid']))
		{
			// Social group picture file
			$_urlFormat = 'SocialGroup_SocialGroupPictureFile';
			
			// Store object ID
			DBSEO::$cache['_objectIds']['groups'][] = $_GET['groupid'];
		}

		if ($_urlFormat AND $_redirectUrl = DBSEO_Url_Create::create($_urlFormat, $_GET))
		{
			// Git to it
			DBSEO::safeRedirect($_redirectUrl, array(), true);
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

		if (!DBSEO::$config['dbtech_dbseo_rewrite_album'] AND !DBSEO::$config['dbtech_dbseo_rewrite_socialgroup'])
		{
			// We're not rewriting this
			return $newUrl;
		}

		if (isset($_seoParameters['do']))
		{
			// Not rewriting anything with do in it
			return $newUrl;
		}

		if (!isset($_seoParameters[DBSEO::$config['_pictureid']]))
		{
			// We need picture ID
			return $newUrl;
		}

		if (DBSEO::$config['dbtech_dbseo_rewrite_album'] AND isset($_seoParameters['albumid']))
		{
			// Album picture file
			$_urlFormat = 'Album_AlbumPictureFile';
		}
		else if (DBSEO::$config['dbtech_dbseo_rewrite_socialgroup'] AND isset($_seoParameters['groupid']))
		{
			// Social group picture file
			$_urlFormat = 'SocialGroup_SocialGroupPictureFile';
		}

		if ($_urlFormat AND $newUrl = DBSEO_Url_Create::create($_urlFormat, $_seoParameters))
		{
			// We're all set!
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

		if (!DBSEO::$config['dbtech_dbseo_rewrite_album'] AND !DBSEO::$config['dbtech_dbseo_rewrite_socialgroup'])
		{
			// We're not rewriting this
			return $newUrl;
		}

		if (isset($_seoParameters['do']))
		{
			// Not rewriting anything with do in it
			return $newUrl;
		}

		if (!isset($_seoParameters[DBSEO::$config['_pictureid']]))
		{
			// We need picture ID
			return $newUrl;
		}

		if (DBSEO::$config['dbtech_dbseo_rewrite_album'] AND isset($_seoParameters['albumid']))
		{
			// Album picture file
			$_urlFormat = 'Album_AlbumPictureFile';
		}
		else if (DBSEO::$config['dbtech_dbseo_rewrite_socialgroup'] AND isset($_seoParameters['groupid']))
		{
			// Social group picture file
			$_urlFormat = 'SocialGroup_SocialGroupPictureFile';
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