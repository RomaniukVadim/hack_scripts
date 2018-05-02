<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// DBSEO "BlogComment URL" class

/**
* Lets you construct & lookup custom URLs
*/
class DBSEO_Rewrite_BlogComment
{
	public static $format = 'Blog_BlogComment';
	public static $structure = 'blog.php?bt=%d';

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
		return sprintf((is_null($structure) ? self::$structure : $structure), $urlInfo['comment_id']);
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

		$data['blogtextid'] = $data['blogtextid'] ? $data['blogtextid'] : $data['bt'];
		if ($data['blogtextid'])
		{
			// We had a comment ID
			$replace['%comment_id%'] = intval($data['blogtextid']);
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