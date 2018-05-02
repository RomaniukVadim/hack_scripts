<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// DBSEO "BlogCategoryCore URL" class

/**
* Lets you construct & lookup custom URLs
*/
class DBSEO_Rewrite_BlogCategoryCore
{
	/**
	 * Gets any extra information needed
	 *
	 * @param mixed $blogCategoryIds
	 * 
	 * @return array
	 */
	public static function getInfo($blogCategoryIds)
	{
		$blogCategoryInfo = array();

		if (!is_array($blogCategoryIds))
		{
			// Ensure this is an array
			$blogCategoryIds = array($blogCategoryIds);
		}

		foreach ($blogCategoryIds as $key => $id)
		{
			if (($info = DBSEO::$datastore->fetch('blogcatinfo.' . $id)) === false)
			{
				// We don't have this cached
				continue;
			}

			// We had this cached, cache it internally too
			DBSEO::$cache['blogcategory'][$id] = $info;
		}

		$queryList = array();
		foreach ($blogCategoryIds as $key => $blogCategoryId)
		{
			if (!isset(DBSEO::$cache['blogcategory'][$blogCategoryId]))
			{
				// Ensure this is done
				$queryList[$key] = intval($blogCategoryId);
			}
		}

		if (count($queryList))
		{
			$info = DBSEO::$db->generalQuery('
				SELECT blogcategoryid, title, userid
				FROM $blog_category
				WHERE blogcategoryid IN (' . implode(',', $queryList) . ')
			', false);
			foreach ($info as $arr)
			{
				// Build the cache
				DBSEO::$datastore->build('blogcatinfo.' . $arr['blogcategoryid'], $arr);

				// Cache this info
				DBSEO::$cache['blogcategory'][$arr['blogcategoryid']] = $arr;
			}
		}

		if (count($blogCategoryIds) == 1)
		{
			// We have only one, return only one
			$blogCategoryInfo = DBSEO::$cache['blogcategory'][$blogCategoryIds[0]];
		}
		else
		{
			foreach ($blogCategoryIds as $key => $blogCategoryId)
			{
				// Create this array
				$blogCategoryInfo[$blogCategoryId] = DBSEO::$cache['blogcategory'][$blogCategoryId];
			}
		}

		return $blogCategoryInfo;
	}
}