<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// DBSEO "SocialGroupCategory URL" class

/**
* Lets you construct & lookup custom URLs
*/
class DBSEO_Rewrite_SocialGroupCategory
{
	public static $format = 'SocialGroup_SocialGroupCategory';
	public static $structure = 'group.php?cat=%d';

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
		if ($urlInfo['cat_name'] AND !$urlInfo['cat_id'])
		{
			// Reverse the category object
			$urlInfo['cat_id'] = DBSEO_Filter::reverseObject('groupcat', $urlInfo['cat_name']);
		}

		// Return the constructed URL
		return sprintf((is_null($structure) ? self::$structure : $structure), $urlInfo['cat_id'], $urlInfo['page']);
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

		$data['categoryid'] = $data['cat'] ? $data['cat'] : $data['categoryid'];
		if (!$data['categoryid'])
		{
			return '';
		}

		// Get group info here
		$groupCategoryInfo = self::getInfo($data['categoryid']);

		if (!$groupCategoryInfo['categoryid'])
		{
			// Groupid didn't exist
			return '';
		}

		$replace['%cat_id%'] 	= $groupCategoryInfo['categoryid'];
		$replace['%cat_name%'] 	= DBSEO_Filter::filterText($groupCategoryInfo['title'], NULL, !(strpos($rawFormat, 'group_id') === false), (strpos($rawFormat, 'cat_id') === false));

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

	/**
	 * Gets any extra information needed
	 *
	 * @param mixed $socialGroupCategoryIds
	 * 
	 * @return array
	 */
	public static function getInfo($socialGroupCategoryIds)
	{
		$socialGroupCategoryInfo = array();

		if (!is_array($socialGroupCategoryIds))
		{
			// Ensure this is an array
			$socialGroupCategoryIds = array($socialGroupCategoryIds);
		}

		foreach ($socialGroupCategoryIds as $key => $id)
		{
			if (($info = DBSEO::$datastore->fetch('sgcatinfo.' . $id)) === false)
			{
				// We don't have this cached
				continue;
			}

			// We had this cached, cache it internally too
			DBSEO::$cache['socialgroupcategory'][$id] = $info;
		}

		$queryList = array();
		foreach ($socialGroupCategoryIds as $key => $socialGroupCategoryId)
		{
			if (!isset(DBSEO::$cache['socialgroupcategory'][$socialGroupCategoryId]))
			{
				// Ensure this is done
				$queryList[$key] = intval($socialGroupCategoryId);
			}
		}

		if (count($queryList))
		{
			$info = DBSEO::$db->generalQuery('
				SELECT *
				FROM $socialgroupcategory
				WHERE socialgroupcategoryid IN(' . implode(',', $queryList) . ')
			', false);
			foreach ($info as $arr)
			{
				// Build the cache
				DBSEO::$datastore->build('sgcatinfo.' . $arr['socialgroupcategoryid'], $arr);

				// Cache this info
				DBSEO::$cache['socialgroupcategory'][$arr['socialgroupcategoryid']] = $arr;
			}
		}

		if (count($socialGroupCategoryIds) == 1)
		{
			// We have only one, return only one
			$socialGroupCategoryInfo = DBSEO::$cache['socialgroupcategory'][$socialGroupCategoryIds[0]];
		}
		else
		{
			foreach ($socialGroupCategoryIds as $key => $socialGroupCategoryId)
			{
				// Create this array
				$socialGroupCategoryInfo[$socialGroupCategoryId] = DBSEO::$cache['socialgroupcategory'][$socialGroupCategoryId];
			}
		}

		return $socialGroupCategoryInfo;
	}
}