<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// DBSEO "CMSCategory URL" class

/**
* Lets you construct & lookup custom URLs
*/
class DBSEO_Rewrite_CMSCategory
{
	public static $format = 'CMS_CMSCategory';
	public static $structure = 'list.php?%s=category/%d-%s';

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
		return sprintf((is_null($structure) ? self::$structure : $structure), DBSEO::$config['route_requestvar'], $urlInfo['category_id'], $urlInfo['category_title'], $urlInfo['page']);
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

		$data['categoryid'] = intval($data['categoryid']);
		if ($data['categoryid'])
		{
			// Grab our category info
			$categoryInfo = DBSEO::getObjectInfo('cms_cat', $data['categoryid']);
			$categoryInfo['category'] = DBSEO_Filter::filterText($categoryInfo['category'], NULL, !(strpos($rawFormat, 'category_id') === false), (strpos($rawFormat, 'category_id') === false), true);
		}
		
		if (!$categoryInfo['categoryid'])
		{
			// User didn't exist
			return '';
		}

		// Handle userid and username
		$replace['%category_id%'] 	= $categoryInfo['categoryid'];
		$replace['%category_title%'] = $categoryInfo['category'];

		if ($data['page'])
		{
			// We had a paged cms
			$replace['%page%'] = $data['page'];
		}

		// Handle the replacements
		$newUrl = str_replace(array_keys($replace), $replace, $rawFormat);
		
		/*DBTECH_PRO_START*/
		if (DBSEO::$config['dbtech_dbseo_custom_cms'] AND strpos($newUrl, '://') === false)
		{
			// Use a custom cms domain
			$newUrl = DBSEO::$config['dbtech_dbseo_custom_cms'] . $newUrl;
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