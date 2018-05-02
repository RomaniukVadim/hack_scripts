<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// DBSEO "Forum URL" class

/**
* Lets you construct & lookup custom URLs
*/
class DBSEO_Rewrite_Forum_Prefix
{
	public static $format = 'Forum_Forum_Prefix';
	public static $structure = 'forumdisplay.php?f=%d&prefixid=%s';

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

		return sprintf((is_null($structure) ? self::$structure : $structure), $urlInfo['forum_id'], $urlInfo['prefix_title'], $urlInfo['forum_page']);
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

		if (!$data['prefixid'])
		{
			// Forum didn't exist
			return '';
		}

		// Handle userid and username
		$replace['%forum_id%'] 	= $forumInfo['forumid'];
		$replace['%forum_title%'] = DBSEO_Rewrite_Forum::rewriteUrl($forumInfo, $rawFormat);
		$replace['%forum_path%'] = $forumInfo['seopath'];
		$replace['%prefix_title%'] = $data['prefixid'];

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
}