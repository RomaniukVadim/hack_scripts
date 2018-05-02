<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// DBSEO "BlogEntry URL" class

/**
* Lets you construct & lookup custom URLs
*/
class DBSEO_Rewrite_BlogEntry
{
	public static $format = 'Blog_BlogEntry';
	public static $structure = '%s.php?b=%d';

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
		return sprintf((is_null($structure) ? self::$structure : $structure), DBSEO::$config['_blogentry'], $urlInfo['blog_id'], $urlInfo['page']);
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
		$data['blogid'] = $data['b'] ? $data['b'] : $data['blogid'];
		if (!$data['blogid'])
		{
			// Blogid didn't exist
			return '';
		}

		// Get blog info here
		$blogInfo = DBSEO_Rewrite_Blog::getInfo($data['blogid']);

		if (!$blogInfo['blogid'])
		{
			// Blogid didn't exist
			return '';
		}

		// Ensure this is set
		$data['userid'] = intval($data['bloguserid'] ? $data['bloguserid'] : $data['u']);
		$data['userid'] = $data['userid'] ? $data['userid'] : $blogInfo['userid'];

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

		/*DBTECH_PRO_START*/
		if (DBSEO::$config['dbtech_dbseo_rewrite_blogentry_smarttitle'])
		{
			// Content filter
			$blogInfo['seotitle'] = DBSEO_Filter::contentFilter($blogInfo['pagetext']);
		}
		/*DBTECH_PRO_END*/

		if (!$blogInfo['seotitle'])
		{
			// Normal filter
			$blogInfo['seotitle'] = DBSEO_Filter::filterText($blogInfo['title'], NULL, !(strpos($rawFormat, 'blog_id') === false), (strpos($rawFormat, 'blog_id') === false), true);
		}

		// Set SEO title
		$blogInfo['seotitle'] = $blogInfo['seotitle'] ? $blogInfo['seotitle'] : 'a';

		// Handle blog info
		$replace['%blog_id%'] 		= $blogInfo['blogid'];
		$replace['%blog_title%'] 	= $blogInfo['seotitle'];

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
}