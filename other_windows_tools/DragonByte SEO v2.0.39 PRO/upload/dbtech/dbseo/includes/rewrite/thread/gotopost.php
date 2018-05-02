<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// DBSEO "Thread URL" class

/**
* Lets you construct & lookup custom URLs
*/
class DBSEO_Rewrite_Thread_GoToPost extends DBSEO_Rewrite_Thread
{
	public static $format = 'Thread_Thread_GoToPost';
	public static $structure = 'showthread.php?p=%d';

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
		$urlInfo['threadid'] = intval($urlInfo['thread_id'] ? $urlInfo['thread_id'] : $urlInfo['t']);

		if ($urlInfo['post_id'] AND !$urlInfo['page'])
		{
			// We definitely need this now
			DBSEO::$cache['_objectIds']['prepostthread_ids'][] = $urlInfo['post_id'];

			// Get post info
			$postInfo = DBSEO::getThreadPostInfo($urlInfo['post_id'], true);

			if (!$urlInfo['threadid'])
			{
				// Set thread ID
				$urlInfo['threadid'] = $postInfo['threadid'];
			}

			// Get thread info
			$threadInfo = DBSEO_Rewrite_Thread::getInfo($urlInfo['threadid'], true);

			// Get page info
			$urlInfo['page'] = DBSEO::getPostPage($threadInfo, $urlInfo['post_id']);
		}

		$_urlFormat = 'Thread_Thread' . ((isset($urlInfo['page']) AND $urlInfo['page'] > 1) ? '_Page' : '');

		if ($_urlFormat AND $_redirectUrl = DBSEO_Url_Create::create($_urlFormat, $urlInfo) AND !DBSEO::isThreaded())
		{
			// Suggest we move to a showthread URL instead
			DBSEO_Url::$suggestedUrls[self::$format] = $_redirectUrl . '#post' . $urlInfo['post_id'];
		}

		return sprintf((is_null($structure) ? self::$structure : $structure), $urlInfo['post_id'], $urlInfo['page']);
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