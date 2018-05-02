<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// DBSEO "SocialGroup URL" class

/**
* Lets you construct & lookup custom URLs
*/
class DBSEO_Rewrite_SocialGroup
{
	public static $format = 'SocialGroup_SocialGroup';
	public static $structure = 'group.php?groupid=%d';

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
		return sprintf((is_null($structure) ? self::$structure : $structure), $urlInfo['group_id'], $urlInfo['page']);
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

		// Shorthand
		$data['groupid'] = $data['groupid'] ? $data['groupid'] : $data['g'];
		if (!$data['groupid'])
		{
			// Groupid didn't exist
			return '';
		}

		// Get group info here
		$groupInfo = self::getInfo($data['groupid']);

		if (!$groupInfo['groupid'])
		{
			// Groupid didn't exist
			return '';
		}

		if (!isset($groupInfo['seotitle']))
		{
			$groupInfo['seotitle'] = DBSEO_Filter::filterText($groupInfo['name'], NULL, !(strpos($rawFormat, 'group_id') === false), (strpos($rawFormat, 'group_id') === false), true);

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

	/**
	 * Gets any extra information needed
	 *
	 * @param mixed $socialGroupIds
	 * 
	 * @return array
	 */
	public static function getInfo($socialGroupIds)
	{
		$socialGroupInfo = array();

		if (!is_array($socialGroupIds))
		{
			// Ensure this is an array
			$socialGroupIds = array($socialGroupIds);
		}

		foreach ($socialGroupIds as $key => $id)
		{
			if (($info = DBSEO::$datastore->fetch('sginfo.' . $id)) === false)
			{
				// We don't have this cached
				continue;
			}

			// We had this cached, cache it internally too
			DBSEO::$cache['socialgroup'][$id] = $info;
		}

		$queryList = array();
		foreach ($socialGroupIds as $key => $socialGroupId)
		{
			if (!isset(DBSEO::$cache['socialgroup'][$socialGroupId]))
			{
				// Ensure this is done
				$queryList[$key] = intval($socialGroupId);
			}
		}

		if (count($queryList))
		{
			$info = DBSEO::$db->generalQuery('
				SELECT *
				FROM $socialgroup
				WHERE groupid IN(' . implode(',', $queryList) . ')
			', false);
			foreach ($info as $arr)
			{
				// Build the cache
				DBSEO::$datastore->build('sginfo.' . $arr['groupid'], $arr);

				// Cache this info
				DBSEO::$cache['socialgroup'][$arr['groupid']] = $arr;
			}
		}

		if (count($socialGroupIds) == 1)
		{
			// We have only one, return only one
			$socialGroupInfo = DBSEO::$cache['socialgroup'][$socialGroupIds[0]];
		}
		else
		{
			foreach ($socialGroupIds as $key => $socialGroupId)
			{
				// Create this array
				$socialGroupInfo[$socialGroupId] = DBSEO::$cache['socialgroup'][$socialGroupId];
			}
		}

		return $socialGroupInfo;
	}
}