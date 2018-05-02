<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// DBSEO "SocialGroupDiscussion URL" class

/**
* Lets you construct & lookup custom URLs
*/
class DBSEO_Rewrite_SocialGroupDiscussion_LastPost extends DBSEO_Rewrite_SocialGroupDiscussion
{
	public static $format = 'SocialGroup_SocialGroupDiscussion_LastPost';
	public static $structure = 'group.php?gmid=%d&do=discuss#gmessage%d';

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
		// Determine if we have a structure
		$structure = is_null($structure) ? self::$structure : $structure;

		// Return the constructed URL
		return sprintf((is_null($structure) ? self::$structure : $structure), $urlInfo['gmid'], $urlInfo['gmid']);
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
		// Determine if we have a format
		$format = is_null($format) ? self::$format : $format;

		// Now create the URL
		return parent::createUrl($data, $format);
	}
}