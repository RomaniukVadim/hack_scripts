<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// Blog class

/**
* Handles various functionality for Image
*/
class DBSEO_Script_Group
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

		if (!DBSEO::$config['dbtech_dbseo_rewrite_socialgroup'])
		{
			// We're not rewriting this
			return false;
		}

		if (!$_GET['do'] AND ($_GET['gmid'] OR $_GET['discussionid']))
		{
			$_GET['do'] = 'discuss';
		}
		else if (!$_GET['do'] AND ($_GET['groupid']))
		{
			$_GET['do'] = 'view';
		}
		else if ($_GET['cat'])
		{
			$_GET['do'] = 'grouplist';
		}
		/*
		else if (!$_GET['do'])
		{
			$_GET['do'] = 'overview';
		}
		*/

		$noClear = false;
		$unsetParams = array();
		if ($_GET['gmid'] AND !isset($_GET['do']))
		{
			$_GET['page'] = DBSEO::getGroupPage($_GET['groupid'], $_GET['gmid']);
		}
		else if ($_GET['gmid'] AND $_GET['do'] == 'discuss')
		{
			if ($_GET['pp'] == DBSEO::$config['gm_perpage'])
			{
				// We don't need perpage if it's default
				$unsetParams = array_merge($unsetParams, array('pp'));
			}

			// Set the page
			$_GET['page'] = DBSEO::getGroupMessagePage($_GET['discussionid'], $_GET['gmid']);

			if ($_redirectUrl = DBSEO_Url_Create::create('SocialGroup_SocialGroupDiscussion' . ($_GET['page'] > 1 ? '_Page' : ''), $_GET))
			{
				// Get rid of some 
				$unsetParams = array_merge($unsetParams, array('gmid', 'do', 'page'));
			}
		}
		else if (
			$_GET['do'] == 'grouplist' AND (
				$_GET['sort'] == 'lastpost' OR !$_GET['sort']
			) AND (
				!$_GET['order'] OR $_GET['order'] == 'desc'
			)
		)
		{
			// Get rid of some params we don't need
			$unsetParams = array_merge($unsetParams, array('sort', 'order'));
		}

		if (!$_redirectUrl)
		{
			if ($_GET['do'] == 'grouplist' AND !$_GET['cat'])
			{
				if ($_GET['pp'] == DBSEO::$config['sg_perpage'])
				{
					// We don't need perpage if it's default
					$unsetParams = array_merge($unsetParams, array('pp'));
				}

				if ($_redirectUrl = DBSEO_Url_Create::create('SocialGroup_SocialGroupList' . ($_GET['page'] > 1 ? '_Page' : ''), $_GET))
				{
					// Get rid of a few params
					$unsetParams = array_merge($unsetParams, array('page', 'do'));
					
					// Don't get rid of everything
					$noClear = true;
				}
			}
			else if ($_GET['do'] == 'discuss' AND !$_GET['gmid'])
			{
				if ($_GET['pp'] == DBSEO::$config['gm_perpage'])
				{
					// We don't need perpage if it's default
					$unsetParams = array_merge($unsetParams, array('pp'));
				}

				if ($_redirectUrl = DBSEO_Url_Create::create('SocialGroup_SocialGroupDiscussion' . ($_GET['page'] > 1 ? '_Page' : ''), $_GET))
				{
					// Get rid of a few params
					$unsetParams = array_merge($unsetParams, array('page', 'do', 'group', 'discussionid'));
					
					// Don't get rid of everything
					$noClear = true;
				}
			}
			else if ($_GET['do'] == 'categorylist')
			{
				if ($_redirectUrl = DBSEO_Url_Create::create('SocialGroup_SocialGroupCategoryList' . ($_GET['page'] > 1 ? '_Page' : ''), $_GET))
				{
					// Get rid of a few params
					$unsetParams = array_merge($unsetParams, array('page', 'do'));
					
					// Don't get rid of everything
					$noClear = true;
				}
			}
			else if ($_GET['cat'] AND (count($_GET) == 1 OR $_GET['page'] OR $_GET['do'] == 'grouplist'))
			{
				if ($_GET['pp'] == DBSEO::$config['sg_perpage'])
				{
					// We don't need perpage if it's default
					$unsetParams = array_merge($unsetParams, array('pp'));
				}

				if ($_redirectUrl = DBSEO_Url_Create::create('SocialGroup_SocialGroupCategory' . ($_GET['page'] > 1 ? '_Page' : ''), $_GET))
				{
					// Get rid of a few params
					$unsetParams = array_merge($unsetParams, array('cat', 'do', 'page'));

					if ($_GET['dofilter'] == 1)
					{
						// Also get rid of dofilter
						$unsetParams = array_merge($unsetParams, array('dofilter'));
					}
					
					// Don't get rid of everything
					$noClear = true;
				}
			}
			else if (!count($_GET))
			{
				if ($_redirectUrl = DBSEO_Url_Create::create('SocialGroup_SocialGroupHome', $_GET))
				{
					// Get rid of a few params
					$unsetParams = array_merge($unsetParams, array('do'));
					
					// Don't get rid of everything
					$noClear = true;
				}
			}
			else if ($_GET['do'] == 'viewmembers')
			{
				$_redirectUrl = DBSEO_Url_Create::create('SocialGroup_SocialGroupMembers' . ($_GET['page'] > 1 ? '_Page' : ''), $_GET);
			}
			else if ($_GET['do'] == 'grouppictures')
			{
				$_redirectUrl = DBSEO_Url_Create::create('SocialGroup_SocialGroupPictures' . ($_GET['page'] > 1 ? '_Page' : ''), $_GET);
			}
			else if ($_GET['do'] == 'picture')
			{
				if (isset($_GET['commentid']))
				{
					// Get the picture page
					$_GET['page'] = DBSEO::getPicturePage($_GET[DBSEO::$config['_pictureid']], $_GET['commentid']);
					
					// Store URL place
					$_urlPlace = 'picturecomment_' . $_GET['commentid'];
				}

				// Now finally grab the social group picture
				$_redirectUrl = DBSEO_Url_Create::create('SocialGroup_SocialGroupPicture' . ($_GET['page'] > 1 ? '_Page' : ''), $_GET);
			}
			else if ($_GET['groupid'] AND (!$_GET['do'] OR $_GET['do'] == 'view'))
			{
				if ($_GET['pp'] == DBSEO::$config['sgd_perpage'])
				{
					// We don't need perpage if it's default
					$unsetParams = array_merge($unsetParams, array('pp'));
				}

				// Just a social group
				$_redirectUrl = DBSEO_Url_Create::create('SocialGroup_SocialGroup' . ($_GET['page'] > 1 ? '_Page' : ''), $_GET);

				// Get rid of a few params
				$unsetParams = array_merge($unsetParams, array('do', 'page', 'groupid'));

				// Don't get rid of everything
				$noClear = true;
			}
		}

		if ($_redirectUrl)
		{
			// Pop round to the new URL
			DBSEO::safeRedirect($_redirectUrl, $unsetParams, !$noClear);
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
		
		if (!DBSEO::$config['dbtech_dbseo_rewrite_socialgroup'])
		{
			// We're not rewriting this
			return false;
		}

		if (!$_seoParameters['do'] AND ($_seoParameters['gmid'] OR $_seoParameters['discussionid']))
		{
			$_seoParameters['do'] = 'discuss';
		}
		else if (!$_seoParameters['do'] AND ($_seoParameters['groupid']))
		{
			$_seoParameters['do'] = 'view';
		}
		else if ($_seoParameters['cat'])
		{
			$_seoParameters['do'] = 'grouplist';
		}
		/*
		else if (!$_seoParameters['do'])
		{
			$_seoParameters['do'] = 'overview';
		}
		*/

		if (isset($_seoParameters['page']) AND $_seoParameters['page'] < 2)
		{
			// We don't want to display page if it's 1
			unset($_seoParameters['page']);
		}

		$noClear = false;
		if ($_seoParameters['gmid'] AND !isset($_seoParameters['do']))
		{
			$_seoParameters['page'] = DBSEO::getGroupPage($_seoParameters['groupid'], $_seoParameters['gmid']);
			$_urlPlace = 'gmessage' . $_seoParameters['gmid'];
		}
		else if ($_seoParameters['gmid'] AND $_seoParameters['do'] == 'discuss')
		{
			if ($_seoParameters['pp'] == DBSEO::$config['gm_perpage'])
			{
				// We don't need perpage if it's default
				unset($_seoParameters['pp']);
			}

			// Set the page
			$_seoParameters['page'] = DBSEO::getGroupMessagePage($_seoParameters['discussionid'], $_seoParameters['gmid']);

			if ($newUrl = DBSEO_Url_Create::create('SocialGroup_SocialGroupDiscussion' . ($_seoParameters['page'] > 1 ? '_Page' : ''), $_seoParameters))
			{
				$_urlPlace = 'gmessage' . $_seoParameters['gmid'];

				// Get rid of some 
				unset($_seoParameters['gmid'], $_seoParameters['do'], $_seoParameters['page']);
			}
		}
		else if (
			$_seoParameters['do'] == 'grouplist' AND (
				$_seoParameters['sort'] == 'lastpost' OR !$_seoParameters['sort']
			) AND (
				!$_seoParameters['order'] OR $_seoParameters['order'] == 'desc'
			)
		)
		{
			// Get rid of some params we don't need
			unset($_seoParameters['sort'], $_seoParameters['order']);
		}

		if (!$newUrl)
		{
			if ($_seoParameters['do'] == 'grouplist' AND !$_seoParameters['cat'])
			{
				if ($_seoParameters['pp'] == DBSEO::$config['sg_perpage'])
				{
					// We don't need perpage if it's default
					unset($_seoParameters['pp']);
				}

				if ($newUrl = DBSEO_Url_Create::create('SocialGroup_SocialGroupList' . ($_seoParameters['page'] > 1 ? '_Page' : ''), $_seoParameters))
				{
					// Get rid of a few params
					unset($_seoParameters['page'], $_seoParameters['do']);
					
					// Don't get rid of everything
					$noClear = true;
				}
			}
			else if ($_seoParameters['do'] == 'discuss' AND !$_seoParameters['gmid'])
			{
				if ($_seoParameters['pp'] == DBSEO::$config['gm_perpage'])
				{
					// We don't need perpage if it's default
					unset($_seoParameters['pp']);
				}

				if ($newUrl = DBSEO_Url_Create::create('SocialGroup_SocialGroupDiscussion' . ($_seoParameters['page'] > 1 ? '_Page' : ''), $_seoParameters))
				{
					// Get rid of a few params
					unset($_seoParameters['page'], $_seoParameters['do'], $_seoParameters['group'], $_seoParameters['discussionid']);
					
					// Don't get rid of everything
					$noClear = true;
				}
			}
			else if ($_seoParameters['do'] == 'categorylist')
			{
				if ($newUrl = DBSEO_Url_Create::create('SocialGroup_SocialGroupCategoryList' . ($_seoParameters['page'] > 1 ? '_Page' : ''), $_seoParameters))
				{
					// Get rid of a few params
					unset($_seoParameters['page'], $_seoParameters['do']);
					
					// Don't get rid of everything
					$noClear = true;
				}
			}
			else if ($_seoParameters['cat'] AND (count($_seoParameters) == 1 OR $_seoParameters['page'] OR $_seoParameters['do'] == 'grouplist'))
			{
				if ($_seoParameters['pp'] == DBSEO::$config['sg_perpage'])
				{
					// We don't need perpage if it's default
					unset($_seoParameters['pp']);
				}

				if ($newUrl = DBSEO_Url_Create::create('SocialGroup_SocialGroupCategory' . ($_seoParameters['page'] > 1 ? '_Page' : ''), $_seoParameters))
				{
					// Get rid of a few params
					unset($_seoParameters['cat'], $_seoParameters['do'], $_seoParameters['page']);

					if ($_seoParameters['dofilter'] == 1)
					{
						// Also get rid of dofilter
						unset($_seoParameters['dofilter']);
					}
					
					// Don't get rid of everything
					$noClear = true;
				}
			}
			else if (!count($_seoParameters))
			{
				if ($newUrl = DBSEO_Url_Create::create('SocialGroup_SocialGroupHome', $_seoParameters))
				{
					// Get rid of a few params
					unset($_seoParameters['do']);
					
					// Don't get rid of everything
					$noClear = true;
				}
			}
			else if ($_seoParameters['do'] == 'viewmembers')
			{
				$newUrl = DBSEO_Url_Create::create('SocialGroup_SocialGroupMembers' . ($_seoParameters['page'] > 1 ? '_Page' : ''), $_seoParameters);
			}
			else if ($_seoParameters['do'] == 'grouppictures')
			{
				$newUrl = DBSEO_Url_Create::create('SocialGroup_SocialGroupPictures' . ($_seoParameters['page'] > 1 ? '_Page' : ''), $_seoParameters);
			}
			else if ($_seoParameters['do'] == 'picture')
			{
				if (isset($_seoParameters['commentid']))
				{
					// Get the picture page
					$_seoParameters['page'] = DBSEO::getPicturePage($_seoParameters[DBSEO::$config['_pictureid']], $_seoParameters['commentid']);
					
					// Store URL place
					$_urlPlace = 'picturecomment_' . $_seoParameters['commentid'];
				}

				// Now finally grab the social group picture
				$newUrl = DBSEO_Url_Create::create('SocialGroup_SocialGroupPicture' . ($_seoParameters['page'] > 1 ? '_Page' : ''), $_seoParameters);
			}
			else if ($_seoParameters['groupid'] AND (!$_seoParameters['do'] OR $_seoParameters['do'] == 'view'))
			{
				if ($_seoParameters['pp'] == DBSEO::$config['sgd_perpage'])
				{
					// We don't need perpage if it's default
					unset($_seoParameters['pp']);
				}

				// Just a social group
				$newUrl = DBSEO_Url_Create::create('SocialGroup_SocialGroup' . ($_seoParameters['page'] > 1 ? '_Page' : ''), $_seoParameters);

				// Get rid of a few params
				unset($_seoParameters['do'], $_seoParameters['page'], $_seoParameters['groupid']);

				// Don't get rid of everything
				$noClear = true;
			}
		}

		if ($newUrl)
		{
			$_urlScript = $newUrl;
			if (!$noClear)
			{
				// We're not clearing
				$_removeAllParameters = true;
			}
		}
		else
		{
			// We didn't have a URL :(
			$_preventProcessing = true;
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
		
		if (!DBSEO::$config['dbtech_dbseo_rewrite_socialgroup'])
		{
			// We're not rewriting this
			return false;
		}

		if (!$_seoParameters['do'] AND ($_seoParameters['gmid'] OR $_seoParameters['discussionid']))
		{
			$_seoParameters['do'] = 'discuss';
		}
		else if (!$_seoParameters['do'] AND ($_seoParameters['groupid']))
		{
			$_seoParameters['do'] = 'view';
		}
		else if ($_seoParameters['cat'])
		{
			$_seoParameters['do'] = 'grouplist';
		}
		/*
		else if (!$_seoParameters['do'])
		{
			$_seoParameters['do'] = 'overview';
		}
		*/

		if ($_seoParameters['gmid'] AND !isset($_seoParameters['do']))
		{
			$_seoParameters['page'] = DBSEO::getGroupPage($_seoParameters['groupid'], $_seoParameters['gmid']);
		}

		if ($_seoParameters['pp'] == DBSEO::$config['vm_perpage'])
		{
			// We don't need perpage if it's default
			unset($_seoParameters['pp']);
		}

		if (isset($_seoParameters['page']) AND $_seoParameters['page'] < 2)
		{
			// We don't want to display page if it's 1
			unset($_seoParameters['page']);
		}

		if ($_seoParameters['gmid'] AND $_seoParameters['do'] == 'discuss')
		{
			// Set the page
			$_seoParameters['page'] = DBSEO::getGroupMessagePage($_seoParameters['discussionid'], $_seoParameters['gmid']);

			// Set the format
			$_newFormat = 'SocialGroup_SocialGroupDiscussion' . ($_seoParameters['page'] > 1 ? '_Page' : '');
		}
		else if ($_seoParameters['do'] == 'grouplist' AND !$_seoParameters['cat'])
		{
			$_newFormat = 'SocialGroup_SocialGroupList' . ($_seoParameters['page'] > 1 ? '_Page' : '');
		}
		else if ($_seoParameters['do'] == 'discuss' AND !$_seoParameters['gmid'])
		{
			$_newFormat = 'SocialGroup_SocialGroupDiscussion' . ($_seoParameters['page'] > 1 ? '_Page' : '');
		}
		else if ($_seoParameters['do'] == 'categorylist')
		{
			$_newFormat = 'SocialGroup_SocialGroupCategoryList' . ($_seoParameters['page'] > 1 ? '_Page' : '');
		}
		else if ($_seoParameters['cat'] AND (count($_seoParameters) == 1 OR $_seoParameters['page'] OR $_seoParameters['do'] == 'grouplist'))
		{
			$_urlFormat = 'SocialGroup_SocialGroupCategory' . ($_seoParameters['page'] > 1 ? '_Page' : '');
		}
		else if (!count($_seoParameters))
		{
			$_urlFormat = 'SocialGroup_SocialGroupHome';
		}
		else if ($_seoParameters['do'] == 'viewmembers')
		{
			$_urlFormat = 'SocialGroup_SocialGroupMembers' . ($_seoParameters['page'] > 1 ? '_Page' : '');
		}
		else if ($_seoParameters['do'] == 'grouppictures')
		{
			$_urlFormat = 'SocialGroup_SocialGroupPictures' . ($_seoParameters['page'] > 1 ? '_Page' : '');
		}
		else if ($_seoParameters['do'] == 'picture')
		{
			if (isset($_seoParameters['commentid']))
			{
				// Get the picture page
				$_seoParameters['page'] = DBSEO::getPicturePage($_seoParameters[DBSEO::$config['_pictureid']], $_seoParameters['commentid']);
			}

			// Now finally grab the social group picture
			$_urlFormat = 'SocialGroup_SocialGroupPicture' . ($_seoParameters['page'] > 1 ? '_Page' : '');
		}
		else if ($_seoParameters['groupid'] AND (!$_seoParameters['do'] OR $_seoParameters['do'] == 'view'))
		{
			// Just a social group
			$_urlFormat = 'SocialGroup_SocialGroup' . ($_seoParameters['page'] > 1 ? '_Page' : '');
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