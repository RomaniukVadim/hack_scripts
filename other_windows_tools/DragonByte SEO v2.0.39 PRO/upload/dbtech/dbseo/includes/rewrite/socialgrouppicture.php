<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// DBSEO "SocialGroupPicture URL" class

/**
* Lets you construct & lookup custom URLs
*/
class DBSEO_Rewrite_SocialGroupPicture
{
	public static $format = 'SocialGroup_SocialGroupPicture';
	public static $structure = 'group.php?do=picture&groupid=%d&%s=%d';

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
		if ($urlInfo['group_name'] AND !$urlInfo['group_id'])
		{
			// Reverse group name
			$urlInfo['group_id'] = DBSEO_Filter::reverseObject('group', $urlInfo['group_name']);
		}

		// Return the constructed URL
		return sprintf((is_null($structure) ? self::$structure : $structure), $urlInfo['group_id'], DBSEO::$config['_pictureid'], $urlInfo['picture_id'], $urlInfo['page']);
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

		if (!$groupPictureInfo[DBSEO::$config['_pictureid']])
		{
			// group picture didn't exist
			return '';
		}

		// Handle picture id and picture title
		$replace['%picture_id%'] 	= $groupPictureInfo[DBSEO::$config['_pictureid']];
		$replace['%picture_title%'] = DBSEO_Filter::filterText($groupPictureInfo['caption'], NULL, !(strpos($rawFormat, 'group_id') === false), (strpos($rawFormat, 'picture_id') === false));

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