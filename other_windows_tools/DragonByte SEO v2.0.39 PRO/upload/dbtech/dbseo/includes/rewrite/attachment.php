<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// DBSEO "Attachment URL" class

/**
* Lets you construct & lookup custom URLs
*/
class DBSEO_Rewrite_Attachment
{
	public static $format = 'Attachment_Attachment';
	public static $structure = 'attachment.php?attachmentid=%d&d=%d%s';

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
		preg_match('#^(\d+)(d\d+)?(t)?#', $urlInfo['attachment_id'], $matches);

		// Return the constructed URL
		return sprintf((is_null($structure) ? self::$structure : $structure), $matches[1], substr($matches[2], 1), (isset($matches[3]) ? '&thumb=1&stc=1' : ''));
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

		// Fetch attachment info
		if (!$attachmentInfo = self::getInfo($data['attachmentid']))
		{
			// attachment didn't exist
			return '';
		}
		
		$data['postid'] = $attachmentInfo['postid'];
		if (!$data['postid'])
		{
			// Postid didn't exist
			return '';
		}

		if (($postInfo = DBSEO::$datastore->fetch('postinfo.' . $data['postid'])) === false)
		{
			// We don't have this cached

			// Grab thread info from DB by pollid
			$postInfo = DBSEO::$db->generalQuery('
				SELECT threadid
				FROM $post AS post
				WHERE postid = ' . $data['postid'] . '
			');

			// Build the cache
			DBSEO::$datastore->build('postinfo.' . $data['postid'], $postInfo);
		}

		if ($postInfo['threadid'])
		{
			// Get our thread info
			$threadInfo = DBSEO_Rewrite_Thread::getInfo($postInfo['threadid']);
		}
		
		if (!$threadInfo['threadid'])
		{
			// Forum didn't exist
			return '';
		}

		// Init this
		$replace = array();

		// Handle userid and username
		$replace['%thread_id%'] 	= $threadInfo['threadid'];
		$replace['%thread_title%'] 	= $threadInfo['seotitle'];
		$replace['%prefix_id%'] 	= $threadInfo['prefixid'] ? $threadInfo['prefixid'] : DBSEO::$config['dbtech_dbseo_rewrite_noprefix'];

		$data['forumid'] = intval($threadInfo['forumid']);
		if ($data['forumid'])
		{
			// Grab our forum cache
			$forumcache = DBSEO::$db->fetchForumCache();

			// Grab our forum info
			$forumInfo = DBSEO::$db->cache['forumcache'][$data['forumid']];
		}
		
		if (!$forumInfo['forumid'])
		{
			// Forum didn't exist
			return '';
		}

		// Handle userid and username
		$replace['%forum_id%'] 	= $forumInfo['forumid'];
		$replace['%forum_title%'] = DBSEO_Rewrite_Forum::rewriteUrl($forumInfo, $rawFormat);
		$replace['%forum_path%'] = $forumInfo['seopath'];

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

		// Sort out thread title for attachment alt attribute
		$replace['%thread_title_ue%'] = htmlentities($threadInfo['title'], ENT_QUOTES);

		// Set some replacement vars
		$replace['%attachment_id%'] = $data['attachmentid'];

		// Handle the replacements
		$newUrl = str_replace(array_keys($replace), $replace, $rawFormat);

		//if (strpos($newUrl, '%') !== false)
		//{
			// We should not return true if any single URL remains
			//return '';
		//}

		if ($format[1] != 'Attachment_Alt' AND strpos($newUrl, DBSEO::$config['dbtech_dbseo_attachment_prefix']) !== 0)
		{
			// Only append the prefix if we need to
			$newUrl = DBSEO::$config['dbtech_dbseo_attachment_prefix'] . $newUrl;
		}		

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
		DBSEO::$cache['attachment'] = is_array(DBSEO::$cache['attachment']) ? DBSEO::$cache['attachment'] : array();

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
				SELECT attachmentid, filename,' . (intval(DBSEO::$config['templateversion']) == 4 ? 'contenttypeid, contentid, contentid AS postid, caption' : 'postid') . '
				FROM $attachment
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