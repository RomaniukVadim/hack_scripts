<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// DBSEO "Blog" classes

/**
* Lets you construct & lookup Blog Attachment URLs
*/
class DBSEO_Rewrite_Blog
{
	public static $format = 'Blog_Blog';
	public static $structure = 'blog.php?u=%d';

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
		if (empty($urlInfo['user_id']) AND isset($urlInfo['user_name']))
		{
			// We need to look up user ID
			$urlInfo['user_id'] = DBSEO_Filter::reverseUsername($urlInfo['user_name']);
		}

		if ($urlInfo['user_id'])
		{
			// We had the user ID!
			return sprintf((is_null($structure) ? self::$structure : $structure), $urlInfo['user_id'], $urlInfo['page']);
		}

		// Fail :(
		return '';
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

		$data['userid'] = intval($data['bloguserid'] ? $data['bloguserid'] : $data['u']);
		if ($data['userid'])
		{
			// Grab our user info
			DBSEO::getUserInfo($data['userid']);
			$userInfo = DBSEO::$cache['userinfo'][$data['userid']];
		}
		
		if (!$userInfo['userid'])
		{
			// User didn't exist
			return '';
		}

		// Handle userid and username
		$replace['%user_id%'] 	= $userInfo['userid'];
		$replace['%user_name%'] = DBSEO_Filter::filterText($userInfo['username'], NULL, false, true, true, false);

		if ($data['page'])
		{
			// We had a paged blog
			$replace['%page%'] = $data['page'];
		}

		// Handle the replacements
		$newUrl = str_replace(array_keys($replace), $replace, $rawFormat);
		
		/*DBTECH_PRO_START*/
		if (DBSEO::$config['dbtech_dbseo_custom_blog'] AND strpos($newUrl,'://') === false)
		{
			// Use a custom blog domain
			$newUrl = DBSEO::$config['dbtech_dbseo_custom_blog'] . $newUrl;
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
	 * @param mixed $blogIds
	 * 
	 * @return array
	 */
	public static function getInfo($blogIds, $blogUser = false, $comment = false)
	{
		if ($comment)
		{
			$table  	= 'blog_text';
			$idField 	= 'blogtextid';
			$userId 	= 'bloguserid';
			$fields 	= 'blog.userid, blog.username, blog.blogtextid, blog.blogid, blog.state';
			$joins 		= '';
		}
		else
		{
			$table 		= 'blog';
			$idField 	= 'blogid';
			$userId 	= 'userid';
			$fields 	= 'blog.userid, blog.username, blog.blogid, blog.title, blog.state, blogtext.pagetext';
			$joins 		= 'LEFT JOIN $blog_text AS blogtext ON(blogtext.blogtextid = blog.firstblogtextid)';
		}

		if (!$blogIds)
		{
			return array();
		}

		if (!is_array($blogIds))
		{
			// Ensure this is an array
			$blogIds = array($blogIds);
		}
		
		foreach ($blogIds as $key => $id)
		{
			if (($info = DBSEO::$datastore->fetch($table . 'info.' . $id)) === false)
			{
				// We don't have this cached
				continue;
			}

			// We had this cached, cache it internally too
			DBSEO::$cache['blog'][$id] = $info;
		}

		$queryList = array();
		foreach ($blogIds as $key => $blogId)
		{
			if (!isset(DBSEO::$cache[$table][$blogId]))
			{
				// Ensure this is done
				$queryList[$key] = intval($blogId);
			}
		}

		if (count($queryList))
		{
			$info = DBSEO::$db->generalQuery('
				SELECT bloguser.*, ' . $fields . '
				FROM $' . $table . ' AS blog
				LEFT JOIN $blog_user AS bloguser ON(bloguser.bloguserid = blog.' . $userId . ')
				' . $joins . '
				WHERE blog.' . $idField . ' IN (' . implode(',', $queryList) . ')
			', false);
			foreach ($info as $arr)
			{
				// Build the cache
				DBSEO::$datastore->build($table . 'info.' . $arr[$idField], $arr);

				// Cache this info
				DBSEO::$cache[$table][$arr[$idField]] = $arr;
			}
		}

		$blogInfo = array();
		if (count($blogIds) == 1)
		{
			// We have only one, return only one
			$blogInfo = DBSEO::$cache[$table][$blogIds[0]];
		}
		else
		{
			foreach ($blogIds as $key => $blogId)
			{
				// Create this array
				$blogInfo[$blogId] = DBSEO::$cache[$table][$blogId];
			}
		}

		return $blogInfo;
	}
}