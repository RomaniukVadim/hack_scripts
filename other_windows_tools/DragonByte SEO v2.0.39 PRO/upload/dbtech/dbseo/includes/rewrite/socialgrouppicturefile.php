<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// DBSEO "SocialGroupPictureFile URL" class

/**
* Lets you construct & lookup custom URLs
*/
class DBSEO_Rewrite_SocialGroupPictureFile
{
	public static $format = 'SocialGroup_SocialGroupPictureFile';
	public static $structure = '%s.php?%s=%d%s';

	/**
	 * Creates a SEO'd URL based on the URL fed
	 *
	 * @param string $url
	 * @param array $data
	 * 
	 * @return string
	 */
	public static function resolveUrl($urlInfo = array(), $structure = NULL)
	{
		// Check whether this is a thumbnail
		preg_match('#^(\d+)(d\d+)?(t)?#', $urlInfo['picture_id'], $match);

		if (intval(DBSEO::$config['templateversion']) == 3)
		{
			// vB3 has no legacy
			$pictureId = $match[1];
		}
		else if (($pictureId = DBSEO::$datastore->fetch('picture.' . $match[1])) === false)
		{
			// Test if we have a direct username match
			if (!$pictureLegacy = DBSEO::$db->generalQuery('
				SELECT attachmentid 
				FROM $picturelegacy 
				WHERE pictureid = \'' . $match[1] . '\'
				LIMIT 1
			'))
			{
				// We didn't have legacy
				$pictureLegacy['attachmentid'] = $match[1];
			}

			// Store this
			$pictureId = $pictureLegacy['attachmentid'];

			// Build the cache
			DBSEO::$datastore->build('picture.' . $match[1], $pictureId);			
		}

		$moreInfo = '';
		if (intval(DBSEO::$config['templateversion']) == 3)
		{
			if ($urlInfo['group_name'] AND !$urlInfo['group_id'])
			{
				// Reverse group name
				$urlInfo['group_id'] = DBSEO_Filter::reverseObject('group', $urlInfo['group_name']);
			}

			// Set group ID
			$moreInfo .= '&groupid=' . $urlInfo['group_id'] . '&dl=' . substr($match[2], 1);
		}
		else
		{
			// Set dateline
			$moreInfo .= '&d=' . substr($match[2], 1);
		}

		// Set thumb if we have it
		$moreInfo .= (isset($match[3]) ? '&thumb=1' : '');

		// Return the constructed URL
		return sprintf((is_null($structure) ? self::$structure : $structure), DBSEO::$config['_picturescript'], DBSEO::$config['_pictureid'], $pictureId, $moreInfo);
	}

	/**
	 * Creates a SEO'd URL based on the URL fed
	 *
	 * @param string $url
	 * @param array $data
	 * 
	 * @return string
	 */
	public static function createUrl($data = array(), $format = NULL)
	{
		if (!count(DBSEO::$cache['rawurls']))
		{
			// Ensure we got this kickstarted
			DBSEO::initUrlCache();
		}

		// Prepare the regexp format
		$format 		= explode('_', (is_null($format) ? self::$format : $format), 2);
		$rawFormat 		= DBSEO::$cache['rawurls'][strtolower($format[0])][$format[1]];

		// Init this
		$replace = array();

		if ($data[DBSEO::$config['_pictureid']])
		{
			// Grab group picture info
			$groupPictureInfo = DBSEO::getObjectInfo(DBSEO::$config['_picturestorage'], $data[DBSEO::$config['_pictureid']]);
		}

		if (!$groupPictureInfo['idfield'])
		{
			// group picture didn't exist
			return '';
		}

		// Handle picture id and picture title
		$replace['%picture_id%'] 	= $groupPictureInfo[DBSEO::$config['_pictureid']];
		$replace['%picture_title%'] = DBSEO_Filter::filterText($groupPictureInfo['caption'], NULL, !(strpos($rawFormat, 'group_id') === false), (strpos($rawFormat, 'picture_id') === false));
		
		if (!$groupPictureInfo['extension'])
		{
			// Ensure we have extension
			$groupPictureInfo['extension'] = pathinfo($groupPictureInfo['filename'], PATHINFO_EXTENSION);
		}

		// Handle extension
		$replace['%original_ext%'] = $groupPictureInfo['extension'];
		if ($data['thumb'])
		{
			// We're requesting a thumbnail
			$replace['%picture_id%'] .= 't';
		}

		$data['groupid'] = ($data['groupid'] ? $data['groupid'] : DBSEO::getContentId($groupPictureInfo));
		if ($data['groupid'])
		{
			// Get group info here
			$groupInfo = DBSEO_Rewrite_SocialGroup::getInfo($data['groupid']);
		}

		if (!$groupInfo['groupid'])
		{
			// Groupid didn't exist
			return '';
		}

		if (!isset($groupInfo['seotitle']))
		{
			$groupInfo['seotitle'] = DBSEO_Filter::filterText($groupInfo['name'], NULL, !(strpos($rawFormat, 'group_id') === false), (strpos($rawFormat, 'group_id') === false));

			// Set this
			DBSEO::$cache['socialgroup'][$groupInfo['groupid']]['seotitle'] = $groupInfo['seotitle'];
		}

		// Handle group info
		$replace['%group_id%'] 		= $groupInfo['groupid'];
		$replace['%group_name%'] 	= $groupInfo['seotitle'];

		if ($data['page'])
		{
			// We had a paged group
			$replace['%page%'] = $data['page'];
		}

		// Handle the replacements
		$newUrl = str_replace(array_keys($replace), $replace, $rawFormat);

		//if (strpos($newUrl, '%') !== false)
		//{
			// We should not return true if any single URL remains
			//return '';
		//}

		// Return the new URL
		return $newUrl;
	}
}