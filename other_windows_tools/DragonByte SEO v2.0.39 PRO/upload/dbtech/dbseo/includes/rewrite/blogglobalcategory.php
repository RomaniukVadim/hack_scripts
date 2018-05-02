<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// DBSEO "BlogGlobalCategory URL" class

/**
* Lets you construct & lookup custom URLs
*/
class DBSEO_Rewrite_BlogGlobalCategory extends DBSEO_Rewrite_BlogCategoryCore
{
	public static $format = 'Blog_BlogGlobalCategory';
	public static $structure = 'blog.php?blogcategoryid=%d';

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
		if (isset($urlInfo['category_id']) AND $urlInfo['category_id'] == 0)
		{
			// Set this if we've got a strange ID
			$urlInfo['category_id'] = -1;
		}

		if (!$urlInfo['category_id'])
		{
			// Lookup blog category ID
			if (!$urlInfo['category_id'] = DBSEO_Filter::reverseObject('blogcat', $urlInfo['category_title']))
			{
				// Uncategorised
				$urlInfo['category_id'] = -1;
			}
		}

		return sprintf((is_null($structure) ? self::$structure : $structure), $urlInfo['category_id'], $urlInfo['page']);
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

		if ($data['blogcategoryid'])
		{
			if ($data['blogcategoryid'] == -1)
			{
				$categoryInfo = array('blogcategoryid' => 0, 'title' => DBSEO::$config['dbtech_dbseo_blog_category_undefined']);
			}
			else
			{
				$categoryInfo = self::getInfo($data['blogcategoryid']);
			}

			if (!isset($categoryInfo['seotitle']))
			{
				$categoryInfo['seotitle'] = DBSEO_Filter::filterText($categoryInfo['title'], NULL, !(strpos($rawFormat, 'category_id') === false), (strpos($rawFormat, 'category_id') === false), true);

				// Set this
				DBSEO::$cache['blogcategory'][$categoryInfo['blogcategoryid']]['seotitle'] = $categoryInfo['seotitle'];
			}

			$replace['%category_id%'] = $categoryInfo['blogcategoryid'];
			$replace['%category_title%'] = $categoryInfo['seotitle'];
		}

		if ($data['page'])
		{
			// We had a paged blog
			$replace['%page%'] = $data['page'];
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