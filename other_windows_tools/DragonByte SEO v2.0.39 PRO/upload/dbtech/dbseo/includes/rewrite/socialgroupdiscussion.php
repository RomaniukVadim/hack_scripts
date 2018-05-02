<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// DBSEO "SocialGroupDiscussion URL" class

/**
* Lets you construct & lookup custom URLs
*/
class DBSEO_Rewrite_SocialGroupDiscussion
{
	public static $format = 'SocialGroup_SocialGroupDiscussion';
	public static $structure = 'group.php?discussionid=%d&do=discuss';

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
		// Return the constructed URL
		return sprintf((is_null($structure) ? self::$structure : $structure), $urlInfo['discussion_id'], $urlInfo['page']);
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
		$data['discussionid'] = $data['discussionid'] ? $data['discussionid'] : $data['d'];

		if ($data['gmid'] AND !$data['discussionid'])
		{
			// Get group info here
			$groupMessage = self::getGroupMessageInfo($data['gmid']);
			$data['discussionid'] = $groupMessage['discussionid'];
		}

		if (!$data['discussionid'])
		{
			// discussionid didn't exist
			return '';
		}

		// Get group info here
		$discussionInfo = self::getInfo($data['discussionid']);

		if (!$discussionInfo['discussionid'])
		{
			// Groupid didn't exist
			return '';
		}

		// Handle group info
		$replace['%discussion_id%'] 	= $discussionInfo['discussionid'];
		$replace['%discussion_name%'] 	= DBSEO_Filter::filterText($discussionInfo['title'], NULL, !(strpos($rawFormat, 'group_id') === false), (strpos($rawFormat, 'discussion_id') === false));

		// Shorthand
		$data['groupid'] = $discussionInfo['groupid'];
		if (!$data['groupid'])
		{
			// Groupid didn't exist
			return '';
		}

		// Get group info here
		$groupInfo = DBSEO_Rewrite_SocialGroup::getInfo($data['groupid']);

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

	/**
	 * Gets any extra information needed
	 *
	 * @param mixed $discussionIds
	 * 
	 * @return array
	 */
	public static function getInfo($discussionIds)
	{
		$discussionInfo = array();

		if (!is_array($discussionIds))
		{
			// Ensure this is an array
			$discussionIds = array($discussionIds);
		}

		foreach ($discussionIds as $key => $id)
		{
			if (($info = DBSEO::$datastore->fetch('sgdiscinfo.' . $id)) === false)
			{
				// We don't have this cached
				continue;
			}

			// We had this cached, cache it internally too
			DBSEO::$cache['socialgroupdiscussion'][$id] = $info;
		}

		$queryList = array();
		foreach ($discussionIds as $key => $discussionId)
		{
			if (!isset(DBSEO::$cache['socialgroupdiscussion'][$discussionId]))
			{
				// Ensure this is done
				$queryList[$key] = intval($discussionId);
			}
		}

		if (count($queryList))
		{
			$info = DBSEO::$db->generalQuery('
				SELECT discussion.*, groupmessage.title
				FROM $discussion AS discussion
				LEFT JOIN $groupmessage AS groupmessage ON(groupmessage.gmid = discussion.firstpostid)
				WHERE discussion.discussionid IN(' . implode(',', $queryList) . ')
			', false);
			foreach ($info as $arr)
			{
				// Build the cache
				DBSEO::$datastore->build('sgdiscinfo.' . $arr['discussionid'], $arr);

				// Cache this info
				DBSEO::$cache['socialgroupdiscussion'][$arr['discussionid']] = $arr;
			}
		}

		if (count($discussionIds) == 1)
		{
			// We have only one, return only one
			$discussionInfo = DBSEO::$cache['socialgroupdiscussion'][$discussionIds[0]];
		}
		else
		{
			foreach ($discussionIds as $key => $discussionId)
			{
				// Create this array
				$discussionInfo[$discussionId] = DBSEO::$cache['socialgroupdiscussion'][$discussionId];
			}
		}

		return $discussionInfo;
	}

	/**
	 * Gets any extra information needed
	 *
	 * @param mixed $groupMessageIds
	 * 
	 * @return array
	 */
	public static function getGroupMessageInfo($groupMessageIds)
	{
		$groupMessageInfo = array();

		if (!is_array($groupMessageIds))
		{
			// Ensure this is an array
			$groupMessageIds = array($groupMessageIds);
		}

		foreach ($groupMessageIds as $key => $id)
		{
			if (($info = DBSEO::$datastore->fetch('gminfo.' . $id)) === false)
			{
				// We don't have this cached
				continue;
			}

			// We had this cached, cache it internally too
			DBSEO::$cache['groupmessage'][$id] = $info;
		}

		$queryList = array();
		foreach ($groupMessageIds as $key => $groupMessageId)
		{
			if (!isset(DBSEO::$cache['groupmessage'][$groupMessageId]))
			{
				// Ensure this is done
				$queryList[$key] = intval($groupMessageId);
			}
		}

		if (count($queryList))
		{
			$info = DBSEO::$db->generalQuery('
				SELECT *
				FROM $groupmessage AS groupmessage
				WHERE gmid IN(' . implode(',', $queryList) . ')
			', false);
			foreach ($info as $arr)
			{
				// Build the cache
				DBSEO::$datastore->build('gminfo.' . $arr['gmid'], $arr);

				// Cache this info
				DBSEO::$cache['groupmessage'][$arr['gmid']] = $arr;
			}
		}

		if (count($groupMessageIds) == 1)
		{
			// We have only one, return only one
			$groupMessageInfo = DBSEO::$cache['groupmessage'][$groupMessageIds[0]];
		}
		else
		{
			foreach ($groupMessageIds as $key => $groupMessageId)
			{
				// Create this array
				$groupMessageInfo[$groupMessageId] = DBSEO::$cache['groupmessage'][$groupMessageId];
			}
		}

		return $groupMessageInfo;
	}
}