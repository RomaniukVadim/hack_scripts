<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// DBSEO "Thread URL" class

/**
* Lets you construct & lookup custom URLs
*/
class DBSEO_Rewrite_Thread_Previous extends DBSEO_Rewrite_Thread
{
	public static $format = 'Thread_Thread_Previous';
	public static $structure = 'showthread.php?t=%d&goto=nextoldest';

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
		if (!$thread = parent::getInfo($urlInfo['thread_id']))
		{
			// Couldn't load thread info
			return sprintf((is_null($structure) ? self::$structure : $structure), $urlInfo['thread_id']);
		}

		if (!$previousThread = self::getInfo($thread))
		{
			// Couldn't load previous thread info
			return sprintf((is_null($structure) ? self::$structure : $structure), $urlInfo['thread_id']);
		}

		// Git oot
		DBSEO::safeRedirect(self::createUrl($previousThread, 'Thread_Thread'));
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

	/**
	 * Gets any extra information needed
	 *
	 * @param mixed $thread
	 * @param boolean $force
	 * 
	 * @return array
	 */
	public static function getInfo($thread, $force = false)
	{
		$threadInfo = array();

		if (($info = DBSEO::$datastore->fetch('prevthread.' . intval($thread['forumid']) . '.' . intval($thread['lastpost']))) === false OR $force)
		{
			$info = DBSEO::$db->generalQuery('
				SELECT *
				FROM $thread
				WHERE forumid = ' . intval($thread['forumid']) . '
					AND lastpost < ' . intval($thread['lastpost']) . '
					AND visible = 1
					AND open <> 10
				ORDER BY lastpost DESC
				LIMIT 1
			', false);

			// Build the cache
			DBSEO::$datastore->build('prevthread.' . intval($thread['forumid']) . '.' . intval($thread['lastpost']), $info);
		}

		foreach ($info as $arr)
		{
			if (!isset($arr['seotitle']))
			{
				// Set this
				DBSEO::$cache['thread'][$arr['threadid']]['seotitle'] = $arr['seotitle'] = DBSEO_Filter::filterText($arr['title']);
			}

			// Cache this info
			DBSEO::$cache['thread'][$arr['threadid']] = $arr;
		}

		return $arr;
	}
}