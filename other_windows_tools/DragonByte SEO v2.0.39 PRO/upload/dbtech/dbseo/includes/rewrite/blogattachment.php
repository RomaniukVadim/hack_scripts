<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// DBSEO "BlogAttachment URL" class

/**
* Lets you construct & lookup custom URLs
*/
class DBSEO_Rewrite_BlogAttachment
{
	public static $format = 'Attachment_BlogAttachment';
	public static $structure = '%s.php?attachmentid=%d&d=%d%s';

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
		// Check whether this is a thumbnail
		preg_match('#^(\d+)(d\d+)?(t)?#', $urlInfo['attachment_id'], $match);

		if (intval(DBSEO::$config['templateversion']) == 3)
		{
			// vB3 has no legacy
			$attachmentId = $match[1];
		}
		else if (($attachmentId = DBSEO::$datastore->fetch('attachment.' . $match[1])) === false)
		{
			// Test if we have a direct username match
			if (!$attachmentLegacy = DBSEO::$db->generalQuery('
				SELECT newattachmentid 
				FROM $blog_attachmentlegacy 
				WHERE oldattachmentid = \'' . $match[1] . '\'
				LIMIT 1
			'))
			{
				// We didn't have legacy
				$attachmentLegacy['newattachmentid'] = $match[1];
			}

			// Store this
			$attachmentId = $attachmentLegacy['newattachmentid'];

			// Build the cache
			DBSEO::$datastore->build('attachment.' . $match[1], $attachmentId);			
		}

		// Return the constructed URL
		return sprintf((is_null($structure) ? self::$structure : $structure), DBSEO::$config['_blogattach'], $attachmentId, substr($match[2], 1), (isset($match[3]) ? '&thumb=1' : ''));
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

		// Fetch blog attachment info
		if (!$attachmentInfo = self::getInfo($data['attachmentid']))
		{
			// Blog attachment didn't exist
			return '';
		}
		
		$data['blogid'] = $attachmentInfo['contentid'];
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

		// Init this
		$replace = array();

		// Set up the original filename
		$replace['%original_filename%'] = DBSEO_Filter::filterText($attachmentInfo['filename'], '.');

		if ($data['d'])
		{
			// Include the dateline
			$data['attachmentid'] .= 'd' . $data['d'];
		}

		if ($data['thumb'])
		{
			// This was a thumbnail
			$data['attachmentid'] .= 't';
		}

		// Set some replacement vars
		$replace['%attachment_id%'] = $data['attachmentid'];
		$replace['%blog_id%'] 		= $blogInfo['blogid'];
		$replace['%blog_title%'] 	= DBSEO_Filter::filterText($blogInfo['title']);

		if ($data['userid'])
		{
			// Get user Info here
			DBSEO::getUserInfo($data['userid']);
			$userInfo = DBSEO::$cache['userinfo'][$data['userid']];
		}
		
		if (!$userInfo['userid'])
		{
			$data['userid'] = $blogInfo['userid'];
			if (!$data['userid'])
			{
				// User didn't exist
				return '';
			}

			// Get user Info here
			DBSEO::getUserInfo($data['userid']);
			$userInfo = DBSEO::$cache['userinfo'][$data['userid']];

			if (!$userInfo['userid'])
			{
				// User didn't exist
				return '';
			}
		}

		// Handle userid and username
		$replace['%user_id%'] 	= $userInfo['userid'];
		$replace['%user_name%'] = DBSEO_Filter::filterText($userInfo['username'], NULL, false, true, true, false);

		// Handle the replacements
		$newUrl = str_replace(array_keys($replace), $replace, $rawFormat);
		
		if (strpos($newUrl, DBSEO::$config['dbtech_dbseo_attachment_prefix']) !== 0)
		{
			// Only append the prefix if we need to
			$newUrl = DBSEO::$config['dbtech_dbseo_attachment_prefix'] . $newUrl;
		}

		/*DBTECH_PRO_START*/
		if (DBSEO::$config['dbtech_dbseo_custom_blog'] AND strpos($newUrl, '://') === false)
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
	 * @param mixed $attachmentIds
	 * 
	 * @return array
	 */
	public static function getInfo($attachmentIds)
	{
		$attachmentInfo = array();

		if (!is_array($attachmentIds))
		{
			// Ensure this is an array
			$attachmentIds = array($attachmentIds);
		}

		foreach ($attachmentIds as $key => $id)
		{
			if (($info = DBSEO::$datastore->fetch('attachinfo.' . $id)) === false)
			{
				// We don't have this cached
				continue;
			}

			// We had this cached, cache it internally too
			DBSEO::$cache['attachment'][$id] = $info;
		}

		$queryList = array();
		foreach ($attachmentIds as $key => $attachmentId)
		{
			if (!isset(DBSEO::$cache['attachment'][$attachmentId]))
			{
				// Ensure this is done
				$queryList[$key] = intval($attachmentId);
			}
		}

		if (count($queryList))
		{
			$info = DBSEO::$db->generalQuery('
				SELECT *
				FROM $' . DBSEO::$config['_blogattach'] . '
				WHERE attachmentid IN (' . implode(',', $queryList) . ')
			', false);
			foreach ($info as $arr)
			{
				// Build the cache
				DBSEO::$datastore->build('attachinfo.' . $arr['attachmentid'], $arr);

				// Cache this info
				DBSEO::$cache['attachment'][$arr['attachmentid']] = $arr;
			}
		}

		if (count($attachmentIds) == 1)
		{
			// We have only one, return only one
			$attachmentInfo = DBSEO::$cache['attachment'][$attachmentIds[0]];
		}
		else
		{
			foreach ($attachmentIds as $key => $attachmentId)
			{
				// Create this array
				$attachmentInfo[$attachmentId] = DBSEO::$cache['attachment'][$attachmentId];
			}
		}

		return $attachmentInfo;
	}
}