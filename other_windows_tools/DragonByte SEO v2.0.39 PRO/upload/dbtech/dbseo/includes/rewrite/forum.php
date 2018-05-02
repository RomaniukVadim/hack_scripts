<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// DBSEO "Forum URL" class

/**
* Lets you construct & lookup custom URLs
*/
class DBSEO_Rewrite_Forum
{
	public static $format = 'Forum_Forum';
	public static $structure = 'forumdisplay.php?f=%d';

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
		if (!isset($urlInfo['forum_page']))
		{
			// Ensure this is set
			$urlInfo['forum_page'] = 1;
		}

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

		return sprintf((is_null($structure) ? self::$structure : $structure), $urlInfo['forum_id'], $urlInfo['forum_page']);
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
		$replace['%forum_title%'] = self::rewriteUrl($forumInfo, $rawFormat);
		$replace['%forum_path%'] = $forumInfo['seopath'];

		if ($data['page'])
		{
			// We had a paged blog
			$replace['%forum_page%'] = $data['page'];
		}

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
	 * SEO's a Forum URL
	 *
	 * @param array $forum
	 *
	 * @return string
	 */
	public static function rewriteUrl($forum, $rawFormat = '')
	{
		if (isset(DBSEO::$db->cache['forumcache'][$forum['forumid']]))
		{
			// Shorthand
			$forum =& DBSEO::$db->cache['forumcache'][$forum['forumid']];
		}

		if (DBSEO::$config['dbtech_dbseo_legacyforumurl'])
		{
			// Bleh.
			$forum['seotitle'] = DBSEO_Filter::filterText(strip_tags($forum['title_clean']), NULL, true, false, false, false);
			return $forum['seotitle'];
		}

		if (!$rawFormat)
		{
			foreach (array('forum_id', '') as $rawFormat)
			{
				// Check whether we have reversable formats
				$reversable = (strpos($rawFormat, 'forum_id') === false);

				// Shorthand
				$key = 'seotitle' . ($reversable ? '_reversable' : '');

				if (!isset($forum[$key]) OR !$forum[$key])
				{
					// Filter the text
					$forum[$key] = DBSEO_Filter::filterText(strip_tags($forum['title_clean']), NULL, !$reversable, $reversable, true);
				}
			}
		}
		else
		{
			// Check whether we have reversable formats
			$reversable = (strpos($rawFormat, 'forum_id') === false);

			// Shorthand
			$key = 'seotitle' . ($reversable ? '_reversable' : '');

			if (!isset($forum[$key]) OR !$forum[$key])
			{
				// Filter the text
				$forum[$key] = DBSEO_Filter::filterText(strip_tags($forum['title_clean']), NULL, !$reversable, $reversable, true);
			}
		}

		return $forum[$key];
	}

	/**
	 * Creates a forum path
	 *
	 * @param array $forum
	 *
	 * @return string
	 */
	public static function createPath($forum)
	{
		// Shorthand
		$forum =& DBSEO::$db->cache['forumcache'][$forum['forumid']];

		if (!isset($forum['seopath']))
		{
			// Grab an array of parents in structured order
			$parentList = array_reverse(explode(',', $forum['parentlist']));

			// Init this
			$forum['seopath'] = array();

			foreach ($parentList as $forumId)
			{
				if (!isset(DBSEO::$db->cache['forumcache'][$forumId]))
				{
					// Skip this
					continue;
				}

				// Init the replacement array
				$replace = array(
					'%forum_id%' 	=> $forumId,
					'%forum_title%' => self::rewriteUrl(DBSEO::$db->cache['forumcache'][$forumId], DBSEO::$config['dbtech_dbseo_rewrite_rule_forumpath']),
				);

				// Add to the SEO Path
				$forum['seopath'][] = str_replace(array_keys($replace), $replace, DBSEO::$config['dbtech_dbseo_rewrite_rule_forumpath']);
			}

			// Store the path
			$forum['seopath'] = @implode('/', $forum['seopath']);
		}

		return $forum['seopath'];
	}
}