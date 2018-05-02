<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// DBSEO "MemberList URL" class

/**
* Lets you construct & lookup custom URLs
*/
class DBSEO_Rewrite_MemberList
{
	public static $format = 'MemberList_MemberList';
	public static $structure = 'memberlist.php';

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
		return sprintf((is_null($structure) ? self::$structure : $structure), $urlInfo['page']);
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

		if ($data['ltr'])
		{
			// We had a paged blog
			$replace['%letter%'] = $data['ltr'] == '%23' ? '0' : strtolower($data['ltr']);
		}

		if ($data['page'])
		{
			// We had a paged blog
			$replace['%page%'] = $data['page'];
		}
		else
		{
			// We didn't have a paged blog
			$replace['%page%'] = 1;
		}

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
}