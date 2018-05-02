<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// DBSEO "Poll URL" class

/**
* Lets you construct & lookup custom URLs
*/
class DBSEO_Rewrite_Poll
{
	public static $format = 'Poll_Poll';
	public static $structure = 'poll.php?do=showresults&pollid=%d';

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
		return sprintf((is_null($structure) ? self::$structure : $structure), $urlInfo['poll_id']);
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

		$data['pollid'] = intval($data['pollid']);
		if ($data['pollid'])
		{
			// Grab thread info from DB by pollid
			$pollInfo = self::getInfo($data['pollid']);
		}
		
		if (!$pollInfo['question'])
		{
			// Forum didn't exist
			return '';
		}

		// Handle userid and username
		$replace['%poll_id%'] 	= $pollInfo['pollid'];
		$replace['%poll_title%'] = DBSEO_Filter::filterText(strip_tags($pollInfo['question']));

		$data['forumid'] = $pollInfo['forumid'];
		if ($data['forumid'])
		{
			// Grab our forum cache
			$forumcache = DBSEO::$db->fetchForumCache();

			// Grab our forum info
			$forumInfo = DBSEO::$db->cache['forumcache'][$data['forumid']];
		}
		
		if (!$forumInfo['forumid'])
		{
			// Forum didn't exist
			return '';
		}

		// Handle userid and username
		$replace['%forum_id%'] 	= $forumInfo['forumid'];
		$replace['%forum_title%'] = DBSEO_Rewrite_Forum::rewriteUrl($forumInfo, $rawFormat);
		$replace['%forum_path%'] = $forumInfo['seopath'];

		// Handle the replacements
		$newUrl = str_replace(array_keys($replace), $replace, $rawFormat);
		
		/*DBTECH_PRO_START*/
		if (DBSEO::$config['dbtech_dbseo_custom_forum'] AND strpos($newUrl, '://') === false)
		{
			// Use a custom forum domain
			$newUrl = DBSEO::$config['dbtech_dbseo_custom_forum'] . $newUrl;
		}
		/*DBTECH_PRO_END*/

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
	 * @param mixed $pollIds
	 * 
	 * @return array
	 */
	public static function getInfo($pollIds)
	{
		$pollInfo = array();

		if (!is_array($pollIds))
		{
			// Ensure this is an array
			$pollIds = array($pollIds);
		}

		foreach ($pollIds as $key => $id)
		{
			if (($info = DBSEO::$datastore->fetch('pollinfo.' . $id)) === false)
			{
				// We don't have this cached
				continue;
			}

			// We had this cached, cache it internally too
			DBSEO::$cache['poll'][$id] = $info;
		}

		$queryList = array();
		foreach ($pollIds as $key => $pollId)
		{
			if (!isset(DBSEO::$cache['poll'][$pollId]))
			{
				// Ensure this is done
				$queryList[$key] = intval($pollId);
			}
		}

		if (count($queryList))
		{
			$info = DBSEO::$db->generalQuery('
				SELECT thread.forumid, poll.pollid, poll.question
				FROM $thread AS thread
				LEFT JOIN $poll AS poll USING(pollid)
				WHERE pollid IN (' . implode(',', $queryList) . ')
			', false);
			foreach ($info as $arr)
			{
				// Build the cache
				DBSEO::$datastore->build('pollinfo.' . $arr['pollid'], $arr);

				// Cache this info
				DBSEO::$cache['poll'][$arr['pollid']] = $arr;
			}
		}

		if (count($pollIds) == 1)
		{
			// We have only one, return only one
			$pollInfo = DBSEO::$cache['poll'][$pollIds[0]];
		}
		else
		{
			foreach ($pollIds as $key => $pollId)
			{
				// Create this array
				$pollInfo[$pollId] = DBSEO::$cache['poll'][$pollId];
			}
		}

		return $pollInfo;
	}
}