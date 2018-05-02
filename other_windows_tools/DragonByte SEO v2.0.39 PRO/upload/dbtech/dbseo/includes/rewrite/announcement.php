<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// DBSEO "Announcement URL" class

/**
* Lets you construct & lookup Announcement URLs
*/
class DBSEO_Rewrite_Announcement
{
	public static $format = 'Announcement_Announcement';
	public static $structure = 'announcement.php?a=%d';

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
		
		if (!isset($urlInfo['forum_id']) OR !$urlInfo['forum_id'])
		{
			// We need this
			return '';
		}

		if (!isset($urlInfo['announcement_id']))
		{
			// Grab our announcements
			self::getInfo($urlInfo['forum_id']);
			$announcements = DBSEO::$db->cache['forumcache'][$data['forumid']]['announcement'];
			
			foreach ((array)$announcements as $announcementid => $announcement)
			{
				if (
					DBSEO_Filter::filterText($announcement, NULL, false, true, true) == $urlInfo['announcement_title'] OR
					DBSEO_Filter::filterText($announcement, NULL, false, false, true) == $urlInfo['announcement_title']
				)
				{
					// We found our announcement
					$urlInfo['announcement_id'] = $announcementid;
					break;
				}
			}
		}

		if (!isset($urlInfo['announcement_id']))
		{
			// We need this
			return '';
		}

		return sprintf((is_null($structure) ? self::$structure : $structure), $urlInfo['announcement_id']);
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

		$data['forumid'] = intval($data['forumid'] ? $data['forumid'] : $data['f']);
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

		$data['announcementid'] = intval($data['announcementid'] ? $data['announcementid'] : $data['a']);
		if ($data['announcementid'])
		{
			// Grab our forum cache
			$announcementInfo = self::getInfo($forumInfo['forumid'], $data['announcementid']);
		}

		// Handle userid and username
		$replace['%announcement_id%'] 	= $announcementInfo['announcementid'];
		$replace['%announcement_title%'] = DBSEO_Filter::filterText($announcementInfo['title'], NULL, !(strpos($rawFormat, 'announcement_id') === false), (strpos($rawFormat, 'announcement_id') === false), true);

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
	 * Gets announcements for a specific forum
	 *
	 * @param string $uri
	 * @param boolean $force404
	 * 
	 * @return boolean
	 */
	public static function getInfo($forumIds, $announcementIds = array())
	{
		// Ensure this is an array
		$forumIds = is_array($forumIds) ? $forumIds : array($forumIds);

		// Grab our forum cache
		$forumcache = DBSEO::$db->fetchForumCache();

		$forumIdList = '-1';
		foreach ($forumIds as $forumId)
		{
			$forumIdList .= ',' . intval($forumId);
			if ($parentList = $forumcache[$forumId]['parentlist'])
			{
				// Add our parents too
				$forumIdList .= ',' . $parentList;
			}
		}

		$info = DBSEO::$db->generalQuery('
			SELECT forumid, announcementid, title
			FROM $announcement AS announcement
			WHERE ' . ($announcementIds ? 'announcementid IN(' . implode(',', $announcementIds) . ')' : 'startdate <= ' . (time() - DBSEO::$config['hourdiff'])) . '
				AND enddate >= ' . (time() - DBSEO::$config['hourdiff']) . '
				AND forumid IN (' . $forumIdList . ')
			ORDER BY startdate DESC
		', false);
		foreach ($info as $arr)
		{
			if ($announcementIds)
			{
				// We queried specific announcements
				$forumIds = array($arr['forumid']);
			}

			// Shorthand
			$forumId = $arr['forumid'];

			foreach ($forumIds as $fId)
			{
				if (!isset(DBSEO::$db->cache['forumcache'][$fId]))
				{
					continue;
				}

				$forum =& DBSEO::$db->cache['forumcache'][$fId];
				if ($forumId == -1 OR $fId == $forumId OR preg_match('#\b' . $forumId . '\b#', $forum['parentlist']))
				{
					$forum['announcement'][$arr['announcementid']] = $arr['title'];
				}
			}
		}

		return $info;
	}
}