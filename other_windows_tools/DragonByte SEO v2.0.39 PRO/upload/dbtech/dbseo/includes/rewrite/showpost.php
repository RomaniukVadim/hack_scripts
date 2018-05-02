<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// DBSEO "ShowPost URL" class

/**
* Lets you construct & lookup custom URLs
*/
class DBSEO_Rewrite_ShowPost
{
	public static $format = 'ShowPost_ShowPost';
	public static $structure = 'showpost.php?p=%d&post_count=%d';

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
		return sprintf((is_null($structure) ? self::$structure : $structure), $urlInfo['post_id'], $urlInfo['post_count']);
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

		$data['postid'] 	= intval($data['postid'] 		? $data['postid'] 		: (isset($data['p']) ? $data['p'] : 0));
		$data['post_count'] = intval($data['post_count'] 	? $data['post_count'] 	: $data['postcount']);

		if ($data['postid'] AND !$data['threadid'])
		{
			// We need to extract thread info from post info
			$postInfo = DBSEO::getThreadPostInfo($data['postid']);
			$data['threadid'] = $postInfo['threadid'];
		}

		if ($data['threadid'])
		{
			// Get our thread info
			$threadInfo = DBSEO_Rewrite_Thread::getInfo($data['threadid']);
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
		$replace['%forum_id%'] 		= $forumInfo['forumid'];
		$replace['%forum_title%'] 	= DBSEO_Rewrite_Forum::rewriteUrl($forumInfo, $rawFormat);

		// Handle userid and username
		$replace['%post_id%'] 		= intval($data['postid']);
		$replace['%post_count%'] 	= intval($data['post_count']);

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