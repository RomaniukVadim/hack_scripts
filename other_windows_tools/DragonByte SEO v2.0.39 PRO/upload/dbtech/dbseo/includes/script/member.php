<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// Member class

/**
* Handles various functionality for Member
*/
class DBSEO_Script_Member
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

		if ($_GET['find'] AND $_GET['find'] != 'lastposter')
		{
			// Don't rewrite any other url than these
			return false;
		}

		switch ($_GET['find'])
		{
			case 'lastposter':

				// Store object IDs
				DBSEO::$cache['_objectIds']['forum_last'] = array($_GET['f']);

				// Normal member profile URL
				$_urlFormat = 'MemberProfile_MemberProfile';

				if ($_urlFormat AND $_redirectUrl = DBSEO_Url_Create::create($_urlFormat, $_GET))
				{
					// Pop round to the new URL
					DBSEO::safeRedirect($_redirectUrl, array_diff(array('f', 'find', 't')));
				}
				break;

			default:
				$_userId = intval($_GET['u'] ? $_GET['u'] : $_GET['userid']);
				if (!$_userId AND $_GET['username'])
				{
					// Grab our userid if we can
					$_userId = DBSEO_Filter::reverseUsername($_GET['username']);
				}

				if (!$_userId)
				{
					// Wrong user id
					break;
				}

				if ($_GET['vmid'])
				{
					if (($totalComments = DBSEO::$datastore->fetch('totalcomments.' . intval($_userId) . '.' . intval($vmid))) === false)
					{
						// Fetch total comments
						$totalComments = DBSEO::$db->generalQuery('
							SELECT COUNT(*) AS comments
							FROM $visitormessage AS visitormessage
							WHERE userid = ' . intval($_userId) . '
								AND state = \'visible\'
								AND dateline >= (SELECT dateline FROM $visitormessage WHERE vmid = ' . intval($vmid) . ')
						');

						// Build the cache
						DBSEO::$datastore->build('totalcomments.' . intval($_userId) . '.' . intval($vmid), $totalComments);
					}

					// Set perpage
					$perpage = intval(DBSEO::$config['vm_perpage']);

					// Now fetch the page
					$_GET['page'] = $perpage ? ceil($totalComments['comments'] / $perpage) : 1;
					$_GET['tab'] = 'visitor_messaging';
				}

				if ($_GET['tab'] == 'visitor_messaging' AND $_GET['page'] > 1)
				{
					// We're doing visitor messaging
					$_urlFormat = 'MemberProfile_VisitorMessage_Page';
				}
				else if ($_GET['tab'] == 'friends' AND $_GET['page'] > 1)
				{
					// Friends list
					$_urlFormat = 'MemberProfile_FriendsList_Page';
				}
				else if (!$_GET['action'] OR ($_GET['action'] == 'getinfo'))
				{
					// Normal member profile URL
					$_urlFormat = 'MemberProfile_MemberProfile';
				}

				if ($_urlFormat AND $_redirectUrl = DBSEO_Url_Create::create($_urlFormat, $_GET))
				{
					// Begin list of unset params
					$unsetParams = array('u', 'action', 'userid', 'username', 'page', 'pp', 'vmid');

					if ($_GET['tab'])
					{
						if ($_GET['tab'] != 'visitor_messaging')
						{
							// We should preserve this
							$_redirectUrl .= (strpos($_redirectUrl, '?') === false ? '?' : '&') . 'tab=' . $_GET['tab'];
						}
						else
						{
							// Don't bother including visitor_messaging
							$unsetParams[] = 'tab';
						}
					}

					// Pop round to the new URL
					DBSEO::safeRedirect($_redirectUrl, $unsetParams);
				}
				break;
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
			return $newUrl;
		}

		if (!isset($_seoParameters['u']) AND isset($_seoParameters['userid']))
		{
			$_seoParameters['u'] = $_seoParameters['userid'];
		}

		if (isset($_seoParameters['find']) AND $_seoParameters['find'] == 'lastposter')
		{
			if ($_seoParameters['f'])
			{
				// Userid comes from forum
				$_seoParameters['u'] = $_seoParameters['userid'] = DBSEO::$cache['forum'][$_seoParameters['f']]['lastposter'] ? DBSEO::$cache['forum'][$_seoParameters['f']]['lastposter'] : $inTag;
			}
			else
			{
				// Userid comes from thread
				$_seoParameters['u'] = $_seoParameters['userid'] = DBSEO::$cache['thread'][$_seoParameters['t']]['lastposter'] ? DBSEO::$cache['thread'][$_seoParameters['t']]['lastposter'] : $inTag;
			}

			// Normal member profile URL
			$newUrl = DBSEO_Url_Create::create('MemberProfile_MemberProfile', $_seoParameters);

			// Toss everything
			$_removeAllParameters = true;
		}
		else if (isset($_seoParameters['username']))
		{
			// Set user ID from user name
			$_seoParameters['u'] = $_seoParameters['userid'] = DBSEO_Filter::reverseUsername($_seoParameters['username']);

			// Normal member profile URL
			$newUrl = DBSEO_Url_Create::create('MemberProfile_MemberProfile', $_seoParameters);

			// Toss everything
			$_removeAllParameters = true;
		}
		else if ($_seoParameters['tab'] == 'visitor_messaging' AND $_seoParameters['page'] > 1)
		{
			// Visitor Message URL
			$newUrl = DBSEO_Url_Create::create('MemberProfile_VisitorMessage_Page', $_seoParameters);

			// Toss everything
			$_removeAllParameters = true;
		}
		else if ($_seoParameters['tab'] == 'friends' AND $_seoParameters['page'] > 1)
		{
			// Friends list URL
			$newUrl = DBSEO_Url_Create::create('MemberProfile_FriendsList_Page', $_seoParameters);
			
			// Toss everything
			$_removeAllParameters = true;
		}
		else if (isset($_seoParameters['username']))
		{
			// Set user ID from user name
			$_seoParameters['u'] = $_seoParameters['userid'] = DBSEO_Filter::reverseUsername($_seoParameters['username']);

			// Normal member profile URL
			$newUrl = DBSEO_Url_Create::create('MemberProfile_MemberProfile', $_seoParameters);
		}		
		else if (
			isset($_seoParameters['u']) AND 
			!isset($_seoParameters['do']) AND 
			!isset($_seoParameters['simple']) AND 
			!isset($_seoParameters['dozoints']) AND 
			!isset($_seoParameters['sort']) AND 
			!isset($_seoParameters['showignored']) AND (
				!isset($_seoParameters['action']) OR $_seoParameters['action'] == 'getinfo'
			)
		)
		{
			// Normal member profile URL
			$newUrl = DBSEO_Url_Create::create('MemberProfile_MemberProfile', $_seoParameters);

			if ($_seoParameters['tab'] AND !$_urlPlace)
			{
				// Set URL place
				$_urlPlace = $_seoParameters['tab'];
			}
		}

		if ($newUrl)
		{
			// Set the URL script
			$_urlScript = $newUrl;

			// Toss a few things
			unset($_seoParameters['u'], $_seoParameters['userid']);
		}
		else
		{
			// Get rid of this
			$_removeAllParameters = false;
			return false;
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

		if (!DBSEO::$config['dbtech_dbseo_rewrite_memberprofile'])
		{
			return $newUrl;
		}

		if (!isset($_seoParameters['u']) AND isset($_seoParameters['userid']))
		{
			$_seoParameters['u'] = $_seoParameters['userid'];
		}

		if (isset($_seoParameters['find']) AND $_seoParameters['find'] == 'lastposter')
		{
			if ($_seoParameters['f'])
			{
				// Userid comes from forum
				$_seoParameters['u'] = $_seoParameters['userid'] = DBSEO::$cache['forum'][$_seoParameters['f']]['lastposter'] ? DBSEO::$cache['forum'][$_seoParameters['f']]['lastposter'] : $inTag;
			}
			else
			{
				// Userid comes from thread
				$_seoParameters['u'] = $_seoParameters['userid'] = DBSEO::$cache['thread'][$_seoParameters['t']]['lastposter'] ? DBSEO::$cache['thread'][$_seoParameters['t']]['lastposter'] : $inTag;
			}

			// Normal member profile URL
			$newUrl = DBSEO_Url_Create::create('MemberProfile_MemberProfile', $_seoParameters);
		}
		else if (isset($_seoParameters['username']))
		{
			// Set user ID from user name
			$_seoParameters['u'] = $_seoParameters['userid'] = DBSEO_Filter::reverseUsername($_seoParameters['username']);

			// Normal member profile URL
			$newUrl = DBSEO_Url_Create::create('MemberProfile_MemberProfile', $_seoParameters);
		}
		else if ($_seoParameters['tab'] == 'visitor_messaging' AND $_seoParameters['page'] > 1)
		{
			// Visitor Message URL
			$newUrl = DBSEO_Url_Create::create('MemberProfile_VisitorMessage_Page', $_seoParameters);
		}
		else if ($_seoParameters['tab'] == 'friends' AND $_seoParameters['page'] > 1)
		{
			// Friends list URL
			$newUrl = DBSEO_Url_Create::create('MemberProfile_FriendsList_Page', $_seoParameters);
		}
		else if (
			isset($_seoParameters['u']) AND !isset($_seoParameters['do']) AND !isset($_seoParameters['simple'])	AND 
			!isset($_seoParameters['dozoints']) AND !isset($_seoParameters['sort']) AND 
			!isset($_seoParameters['showignored']) AND (
				!isset($_seoParameters['action']) OR $_seoParameters['action'] == 'getinfo'
			)
		)
		{
			// Normal member profile URL
			$newUrl = DBSEO_Url_Create::create('MemberProfile_MemberProfile', $_seoParameters);
		}

		return $newUrl;
	}
}
?>