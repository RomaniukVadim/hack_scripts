<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// DBSEO "NavBullet_Forum URL" class

/**
* Lets you construct & lookup custom URLs
*/
class DBSEO_Rewrite_NavBullet_Forum
{
	public static $format = 'NavBullet_NavBullet_Forum';

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
		$replace['%forum_id%'] 		= $forumInfo['forumid'];
		$replace['%forum_title%'] 	= DBSEO_Rewrite_Forum::rewriteUrl($forumInfo, $rawFormat);

		// Special case
		$rawFormat = str_replace('.gif', $data['currentDir'] . '.gif', $rawFormat);

		// Handle the replacements
		$newUrl = str_replace(array_keys($replace), $replace, $rawFormat);
		
		//if (strpos($newUrl, '%') !== false)
		//{
			// We should not return true if any single URL remains
			//return '';
		//}

		// Return the new URL
		return DBSEO::$config['dbtech_dbseo_navbullet_prefix'] . $newUrl;
	}
}