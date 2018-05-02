<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// DBSEO "Custom URL" class

/**
* Lets you construct & lookup custom URLs
*/
class DBSEO_Rewrite_Thread
{
	public static $format = 'Thread_Thread';
	public static $structure = 'showthread.php?t=%d';

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
		$redirect = false;

		if (DBSEO::checkHighlight(true) OR DBSEO::checkMode(true))
		{
			// We need to redirect
			DBSEO::safeRedirect($_SERVER['REQUEST_URI'], array('highlight', 'mode'));
		}

		// Retrieve highlight params
		DBSEO::checkHighlight(false);

		// Retrieve mode params
		DBSEO::checkMode(false);

		if (!$urlInfo['thread_id'])
		{
			// We're missing a thread ID

			if (!isset($urlInfo['forum_id']) AND isset($urlInfo['forum_path']))
			{
				// Grab our forum cache
				$forumcache = DBSEO::$db->fetchForumCache();

				foreach ($forumcache as $forumid => $forum)
				{
					if ($forum['seopath'] == $urlInfo['forum_path'] OR html_entity_decode($forum['seopath'], ENT_COMPAT | ENT_HTML401, 'UTF-8') == urldecode($urlInfo['forum_path']))
					{
						// Discovered the right forum
						$urlInfo['forum_id'] = $forumid;
						break;
					}
				}
			}

			if (!isset($urlInfo['forum_id']) AND isset($urlInfo['forum_title']))
			{
				// Reverse forum title
				$urlInfo['forum_id'] = DBSEO_Filter::reverseForumTitle($urlInfo);
			}

			// Reverse lookup thread ID
			$urlInfo['thread_id'] = DBSEO_Filter::reverseObject('thread', $urlInfo['thread_title'], $urlInfo['forum_id']);
		}

		return sprintf((is_null($structure) ? self::$structure : $structure), $urlInfo['thread_id'], $urlInfo['thread_page']);
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

		$data['postid'] = intval($data['postid'] ? $data['postid'] : (isset($data['p']) ? $data['p'] : 0));
		$data['threadid'] = intval($data['threadid'] ? $data['threadid'] : $data['t']);

		if ($data['postid'])
		{
			// We had a paged blog
			$replace['%post_id%'] = $data['postid'];

			if (!$data['threadid'])
			{
				// We need to extract thread info from post info
				$postInfo = DBSEO::getThreadPostInfo($data['postid']);
				$data['threadid'] = $postInfo['threadid'];
			}
		}

		if ($data['threadid'])
		{
			// Get our thread info
			$threadInfo = self::getInfo($data['threadid']);
		}

		if (!$threadInfo['threadid'])
		{
			// Forum didn't exist
			return '';
		}

		// Handle userid and username
		$replace['%thread_id%'] 	= $threadInfo['threadid'];
		$replace['%thread_title%'] 	= $threadInfo['seotitle'];
		$replace['%prefix_id%'] 	= $threadInfo['prefixid'] ? $threadInfo['prefixid'] : DBSEO::$config['dbtech_dbseo_rewrite_noprefix'];

		$data['forumid'] = intval($threadInfo['forumid']);
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

		if ($data['page'])
		{
			// We had a paged blog
			$replace['%thread_page%'] = $data['page'];
		}

		// Handle the replacements
		$newUrl = str_replace(array_keys($replace), $replace, $rawFormat);

		//if (strpos($newUrl, '%') !== false)
		//{
			// We should not return true if any single URL remains
			//return '';
		//}

		// Ensure we don't double down on this
		$newUrl = str_replace(
			DBSEO::$config['dbtech_dbseo_rewrite_separator'] . DBSEO::$config['dbtech_dbseo_rewrite_separator'],
			DBSEO::$config['dbtech_dbseo_rewrite_separator'],
			$newUrl
		);

		/*DBTECH_PRO_START*/
		if (DBSEO::$config['dbtech_dbseo_custom_forum'] AND strpos($newUrl, '://') === false)
		{
			// Use a custom forum domain
			$newUrl = DBSEO::$config['dbtech_dbseo_custom_forum'] . $newUrl;
		}
		/*DBTECH_PRO_END*/

		// Return the new URL
		return $newUrl;
	}

	/**
	 * Gets any extra information needed
	 *
	 * @param mixed $threadIds
	 * @param boolean $force
	 *
	 * @return array
	 */
	public static function getInfo($threadIds, $force = false)
	{
		$threadInfo = array();

		if (!is_array($threadIds))
		{
			// Ensure this is an array
			$threadIds = array($threadIds);
		}

		if (!$force)
		{
			foreach ($threadIds as $key => $id)
			{
				if (($info = DBSEO::$datastore->fetch('threadinfo.' . $id)) === false)
				{
					// We don't have this cached
					continue;
				}

				// We had this cached, cache it internally too
				DBSEO::$cache['thread'][$id] = $info;
			}
		}

		$queryList = array();
		foreach ($threadIds as $key => $threadId)
		{
			if (!isset(DBSEO::$cache['thread'][$threadId]) OR $force)
			{
				// Ensure this is done
				$queryList[$key] = intval($threadId);
			}
			else
			{
				// Shorthand
				$thread =& DBSEO::$cache['thread'][$threadId];

				if (isset($thread['seotitle']) AND $thread['seotitle'])
				{
					// We had a SEO title
					continue;
				}

				/*DBTECH_PRO_START*/
				if (DBSEO::$config['dbtech_dbseo_rewrite_thread_smarttitle'])
				{
					if (!isset($thread['firstpostid']))
					{
						// Ensure this is done
						$queryList[$key] = intval($threadId);
						continue;
					}

					// Grab post info
					$postInfo = DBSEO::getThreadPostInfo($thread['firstpostid']);

					// Content filter
					$thread['seotitle'] = DBSEO_Filter::contentFilter($postInfo['pagetext']);
				}
				/*DBTECH_PRO_END*/

				if (!isset($thread['seotitle']) OR !$thread['seotitle'])
				{
					// Normal filter
					$thread['seotitle'] = DBSEO_Filter::filterText(
						$thread['threadtitle'] ?
						$thread['threadtitle'] :
						$thread['title']
					);
				}
			}
		}

		if (count($queryList))
		{
			$info = DBSEO::$db->generalQuery('
				SELECT *
				FROM $thread
				WHERE threadid IN (' . implode(',', $queryList) . ')
			', false);
			foreach ($info as $arr)
			{
				// Init this
				$arr['seotitle'] = '';

				/*DBTECH_PRO_START*/
				if (DBSEO::$config['dbtech_dbseo_rewrite_thread_smarttitle'])
				{
					// Grab post info
					$postInfo = DBSEO::getThreadPostInfo($arr['firstpostid']);

					// Content filter
					$arr['seotitle'] = DBSEO_Filter::contentFilter($postInfo['pagetext']);
				}
				/*DBTECH_PRO_END*/

				if (!$arr['seotitle'])
				{
					// Normal filter
					$arr['seotitle'] = DBSEO_Filter::filterText(
						$arr['threadtitle'] ?
						$arr['threadtitle'] :
						$arr['title']
					);
				}

				// Build the cache
				DBSEO::$datastore->build('threadinfo.' . $arr['threadid'], $arr);

				// Cache this info
				DBSEO::$cache['thread'][$arr['threadid']] = $arr;
			}
		}

		if (count($threadIds) == 1)
		{
			// We have only one, return only one
			$threadInfo = DBSEO::$cache['thread'][$threadIds[0]];
		}
		else
		{
			foreach ($threadIds as $key => $threadId)
			{
				// Create this array
				$threadInfo[$threadId] = DBSEO::$cache['thread'][$threadId];
			}
		}

		return $threadInfo;
	}
}