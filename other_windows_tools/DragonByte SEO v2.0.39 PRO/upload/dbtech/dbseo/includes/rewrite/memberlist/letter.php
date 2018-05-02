<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// DBSEO "MemberList URL" class

/**
* Lets you construct & lookup custom URLs
*/
class DBSEO_Rewrite_MemberList_Letter extends DBSEO_Rewrite_MemberList
{
	public static $format = 'MemberList_MemberList_Letter';
	public static $structure = 'memberlist.php?ltr=%s%s';

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
		// Ensure this is correct
		$urlInfo['letter'] = $urlInfo['letter'] == '0' ? '%23' : $urlInfo['letter'];

		return sprintf((is_null($structure) ? self::$structure : $structure), strtoupper($urlInfo['letter']), ($urlInfo['page'] > 1 ? '&page=' . $urlInfo['page'] : ''));
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