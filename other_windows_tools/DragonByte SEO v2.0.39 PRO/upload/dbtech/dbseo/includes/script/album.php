<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// Album class

/**
* Handles various functionality for Album
*/
class DBSEO_Script_Album
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
		
		if (!DBSEO::$config['dbtech_dbseo_rewrite_album'])
		{
			// We're not rewriting this
			return false;
		}

		switch ($_GET['do'])
		{
			case 'latest':
			case 'overview':
				// We need paginated URLs
				$_urlFormat = 'Album_MemberAlbums' . ($_GET['page'] ? '_Page' : '');
				break;

			case 'picture':
				// Album picture
				$_urlFormat = 'Album_AlbumPicture' . ($_GET['page'] > 1 ? '_Page' : '');

				// Store object ID
				DBSEO::$cache['_objectIds'][DBSEO::$config['_picturestorage']][] = $_GET[DBSEO::$config['_pictureid']];
				break;

			case 'user':
				// Album list
				$_urlFormat = 'Album_AlbumList' . ($_GET['page'] > 1 ? '_Page' : '');
				break;

			default:
				if (isset($_GET['commentid']))
				{
					// Grab our page number
					$_GET['page'] = DBSEO::getPicturePage($_GET[DBSEO::$config['_pictureid']], $_GET['commentid']);
				}

				if (isset($_GET[DBSEO::$config['_pictureid']]))
				{
					// Album picture
					$_urlFormat = 'Album_AlbumPicture' . ($_GET['page'] > 1 ? '_Page' : '');

					// Store object ID
					DBSEO::$cache['_objectIds'][DBSEO::$config['_picturestorage']][] = $_GET[DBSEO::$config['_pictureid']];
				}
				else if (isset($_GET['albumid']) AND (count($_GET) == 1 OR $_GET['page']))
				{
					// Member album
					$_urlFormat = 'Album_Album' . ($_GET['page'] > 1 ? '_Page' : '');

					// Store object ID
					DBSEO::$cache['_objectIds']['album'][] = $_GET['albumid'];
				}
				else if (isset($_GET['u']) AND (count($_GET) == 1 OR $_GET['page']))
				{
					// Album list
					$_urlFormat = 'Album_AlbumList' . ($_GET['page'] > 1 ? '_Page' : '');
				}
				else if (count($_GET) == 0)
				{
					// All albums
					$_urlFormat = 'Album_MemberAlbums';
				}
				break;
		}

		if ($_urlFormat AND $_redirectUrl = DBSEO_Url_Create::create($_urlFormat, $_GET))
		{
			// Git to it
			DBSEO::safeRedirect($_redirectUrl, array('u', 'userid', 'do', 'albumid', DBSEO::$config['_pictureid'], 'commentid', 'page'));
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

		if (!DBSEO::$config['dbtech_dbseo_rewrite_album'])
		{
			// We're not rewriting this
			return $newUrl;
		}

		switch ($_seoParameters['do'])
		{
			case 'latest':
			case 'overview':
				// We need paginated URLs
				$_urlFormat = 'Album_MemberAlbums' . ($_seoParameters['page'] ? '_Page' : '');
				break;

			case 'picture':
				// Album picture
				$_urlFormat = 'Album_AlbumPicture' . ($_seoParameters['page'] > 1 ? '_Page' : '');

				// Store object ID
				DBSEO::$cache['_objectIds'][DBSEO::$config['_picturestorage']][] = $_seoParameters[DBSEO::$config['_pictureid']];
				break;

			case 'user':
				// Album list
				$_urlFormat = 'Album_AlbumList' . ($_seoParameters['page'] > 1 ? '_Page' : '');
				break;

			default:
				if (isset($_seoParameters['commentid']))
				{
					// We had a comment ID, find out what page we're on
					$_seoParameters['page'] = DBSEO::getPicturePage($_seoParameters[DBSEO::$config['_pictureid']], $_seoParameters['commentid']);
					$_urlPlace = 'picturecomment_' . $_seoParameters['commentid'];
					unset($_seoParameters['commentid']);
				}

				if (isset($_seoParameters[DBSEO::$config['_pictureid']]))
				{
					// Album picture
					$_urlFormat = 'Album_AlbumPicture' . ($_seoParameters['page'] > 1 ? '_Page' : '');

					// Store object ID
					DBSEO::$cache['_objectIds'][DBSEO::$config['_picturestorage']][] = $_seoParameters[DBSEO::$config['_pictureid']];
				}
				else if (isset($_seoParameters['albumid']) AND (count($_seoParameters) == 1 OR $_seoParameters['page']))
				{
					// Member album
					$_urlFormat = 'Album_Album' . ($_seoParameters['page'] > 1 ? '_Page' : '');

					// Store object ID
					DBSEO::$cache['_objectIds']['album'][] = $_seoParameters['albumid'];
				}
				else if (isset($_seoParameters['u']) AND (count($_seoParameters) == 1 OR $_seoParameters['page']))
				{
					// Album list
					$_urlFormat = 'Album_AlbumList' . ($_seoParameters['page'] > 1 ? '_Page' : '');
				}
				else if (count($_seoParameters) == 0)
				{
					// All albums
					$_urlFormat = 'Album_MemberAlbums';
				}
				break;
		}

		if ($_urlFormat AND $newUrl = DBSEO_Url_Create::create($_urlFormat, $_seoParameters))
		{
			// Git to it
			$_urlScript = $newUrl;
			unset($_seoParameters[DBSEO::$config['_pictureid']], $_seoParameters['albumid'], $_seoParameters['u'], $_seoParameters['userid'], $_seoParameters['page']);
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

		if (!DBSEO::$config['dbtech_dbseo_rewrite_album'])
		{
			// We're not rewriting this
			return $newUrl;
		}

		switch ($_seoParameters['do'])
		{
			case 'latest':
			case 'overview':
				// We need paginated URLs
				$_urlFormat = 'Album_MemberAlbums' . ($_seoParameters['page'] ? '_Page' : '');
				break;

			case 'picture':
				// Album picture
				$_urlFormat = 'Album_AlbumPicture' . ($_seoParameters['page'] > 1 ? '_Page' : '');

				// Store object ID
				DBSEO::$cache['_objectIds'][DBSEO::$config['_picturestorage']][] = $_seoParameters[DBSEO::$config['_pictureid']];
				break;

			case 'user':
				// Album list
				$_urlFormat = 'Album_AlbumList' . ($_seoParameters['page'] > 1 ? '_Page' : '');
				break;

			default:
				if (isset($_seoParameters['commentid']))
				{
					// We had a comment ID, find out what page we're on
					$_seoParameters['page'] = DBSEO::getPicturePage($_seoParameters[DBSEO::$config['_pictureid']], $_seoParameters['commentid']);
				}

				if (isset($_seoParameters[DBSEO::$config['_pictureid']]))
				{
					// Album picture
					$_urlFormat = 'Album_AlbumPicture' . ($_seoParameters['page'] > 1 ? '_Page' : '');
				}
				else if (isset($_seoParameters['albumid']) AND (count($_seoParameters) == 1 OR $_seoParameters['page']))
				{
					// Member album
					$_urlFormat = 'Album_Album' . ($_seoParameters['page'] > 1 ? '_Page' : '');
				}
				else if (isset($_seoParameters['u']) AND (count($_seoParameters) == 1 OR $_seoParameters['page']))
				{
					// Album list
					$_urlFormat = 'Album_AlbumList' . ($_seoParameters['page'] > 1 ? '_Page' : '');
				}
				else if (count($_seoParameters) == 0)
				{
					// All albums
					$_urlFormat = 'Album_MemberAlbums';
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