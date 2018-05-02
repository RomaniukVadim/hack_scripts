<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// Showthread class

/**
* Handles various functionality for Showthread
*/
class DBSEO_Script_Showthread
{
	/**
	 * Checks for and redirects to proper URLs if needed
	 *
	 * @param string $url
	 * @param boolean $fileExists
	 * @param boolean $fileExistsDeep
	 *
	 * @return mixed
	 */
	public static function redirectUrl(&$url, &$fileExists, &$fileExistsDeep)
	{
		$_redirectUrl = $_urlFormat = '';

		if (!DBSEO::$config['dbtech_dbseo_rewrite_thread'])
		{
			// We're not rewriting this
			return false;
		}

		if ($_GET['viewfull'])
		{
			// Don't redirect this
			//return false;
		}

		if (
			DBSEO::isThreaded() AND (
				(isset($_GET['p']) AND $_GET['p']) OR
				(isset($_GET['postid']) AND $_GET['postid'])
			)
		)
		{
			if ($_GET['do'])
			{
				// This only works for showpost URLs
				return $newUrl;
			}

			// Create URL
			$_urlFormat = 'Thread_GoToPost';
		}
		else if (isset($_GET['goto']))
		{
			switch ($_GET['goto'])
			{
				case 'nextnewest':
					// Next thread
					$_urlFormat = 'Thread_Thread_Next';
					break;

				case 'nextoldest':
					// Previous thread
					$_urlFormat = 'Thread_Thread_Previous';
					break;
			}
		}
		else
		{
			$_threadId = intval($_GET['threadid'] ? $_GET['threadid'] : $_GET['t']);

			if ($_GET['p'] AND !$_GET['page'])
			{
				// We definitely need this now
				DBSEO::$cache['_objectIds']['prepostthread_ids'][] = $_GET['p'];

				// Get post info
				$postInfo = DBSEO::getThreadPostInfo($_GET['p'], true);

				if (!$_threadId)
				{
					// Set thread ID
					$_threadId = $postInfo['threadid'];
				}

				// Get thread info
				$threadInfo = DBSEO_Rewrite_Thread::getInfo($_threadId, true);

				// Get page info
				$_GET['page'] = DBSEO::getPostPage($threadInfo, $_GET['p']);

				// Set an anchor
				$postAnchor = '#post' . $_GET['p'];
			}

			$_urlFormat = 'Thread_Thread' . ((isset($_GET['page']) AND $_GET['page'] > 1) ? '_Page' : '');
		}

		if ($_urlFormat AND $_redirectUrl = DBSEO_Url_Create::create($_urlFormat, $_GET))
		{
			// We had a redirect URL, so get to it!
			DBSEO::safeRedirect($_redirectUrl . $postAnchor, array('', 't', 'p', 'threadid', 'postid', 'page', 'viewfull', ($_GET['pp'] == DBSEO::$config['maxposts']) ? 'pp' : ''));
		}

		return true;
	}

	/**
	 * Replace urls
	 *
	 * @param string $urlPrefix
	 * @param string $url
	 * @param string $urlAttributes
	 * @param string $urlSuffix
	 * @param string $inTag
	 * @param string $closeTag
	 *
	 * @return string
	 */
	public static function replaceUrls(&$_preventProcessing, &$_seoParameters, &$urlPrefix, &$url, &$urlSuffix, &$inTag, &$_urlScript, &$_urlPlace, &$_urlParameters, &$_removeAllParameters, &$_cmsUrlAppend, &$nofollow, &$follow)
	{
		$newUrl = $_urlFormat = '';

		if (!DBSEO::$config['dbtech_dbseo_rewrite_thread'])
		{
			// We're not rewriting this
			return $newUrl;
		}

		/*
		if ($_seoParameters['viewfull'] == 1)
		{
			// Don't rewrite this
			return false;
		}
		*/

		// Make sure this is set
		$_seoParameters['threadid'] = intval($_seoParameters['threadid'] ? $_seoParameters['threadid'] : $_seoParameters['t']);

		if (preg_match('#^post(\d+)$#', $_urlPlace, $matches))
		{
			// Extract post ID
			$_seoParameters['p'] = $matches[1];

			if (!$_seoParameters['page'])
			{
				// We definitely need this now
				DBSEO::$cache['_objectIds']['prepostthread_ids'][] = $_seoParameters['p'];

				// Get post info
				$postInfo = DBSEO::getThreadPostInfo($_seoParameters['p'], true);

				if (!$_seoParameters['threadid'])
				{
					// Set thread ID
					$_seoParameters['threadid'] = $postInfo['threadid'];
				}

				// Get thread info
				$threadInfo = DBSEO_Rewrite_Thread::getInfo($_seoParameters['threadid'], true);

				// Get page info
				$_seoParameters['page'] = DBSEO::getPostPage($threadInfo, $_seoParameters['p']);
			}
		}

		if (!isset($threadInfo))
		{
			// Get thread info
			$threadInfo = DBSEO_Rewrite_Thread::getInfo($_seoParameters['threadid']);
		}

		if (!is_array(DBSEO::$config['dbtech_dbseo_rewrite_forum_nofollow']))
		{
			DBSEO::$config['dbtech_dbseo_rewrite_forum_nofollow'] = @unserialize(DBSEO::$config['dbtech_dbseo_rewrite_forum_nofollow']);
			DBSEO::$config['dbtech_dbseo_rewrite_forum_nofollow'] = is_array(DBSEO::$config['dbtech_dbseo_rewrite_forum_nofollow']) ? DBSEO::$config['dbtech_dbseo_rewrite_forum_nofollow'] : array();
		}

		if (in_array($threadInfo['forumid'], DBSEO::$config['dbtech_dbseo_rewrite_forum_nofollow']))
		{
			// Set nofollow
			$nofollow = true;
		}

		if (
			(isset($_seoParameters['p']) AND $_seoParameters['p']) OR
			(isset($_seoParameters['postid']) AND $_seoParameters['postid'])
		)
		{
			if ($_seoParameters['do'])
			{
				// This only works for showpost URLs
				return $newUrl;
			}

			if ($newUrl = DBSEO_Url_Create::create('Thread_Thread_GoToPost', $_seoParameters))
			{
				if ($_urlPlace)
				{
					// Get rid of hash tags
					$newUrl = preg_replace('|#.*|', '', $newUrl);
				}
			}
		}
		else if (isset($_seoParameters['goto']) AND $_seoParameters['goto'])
		{
			switch ($_seoParameters['goto'])
			{
				case 'newpost':
					// Next thread
					$_urlFormat = 'Thread_Thread_NewPost';
					break;

				case 'lastpost':
					// Previous thread
					$_urlFormat = 'Thread_Thread_LastPost';
					break;

				case 'nextnewest':
					// Next thread
					$_urlFormat = 'Thread_Thread_Next';
					break;

				case 'nextoldest':
					// Previous thread
					$_urlFormat = 'Thread_Thread_Previous';
					break;
			}

			if ($_urlFormat)
			{
				// We had an url format
				$newUrl = DBSEO_Url_Create::create($_urlFormat, $_seoParameters);
			}
		}
		else
		{
			// Plain old thread URL
			$newUrl = DBSEO_Url_Create::create('Thread_Thread' . (isset($_seoParameters['page']) ? '_Page' : ''), $_seoParameters);
		}

		if ($newUrl)
		{
			$_urlScript = $newUrl;
			unset(
				$_seoParameters['p'], 			$_seoParameters['post'], 		$_seoParameters['postid'],
				$_seoParameters['viewfull'], 	$_seoParameters['t'], 			$_seoParameters['page'],
				$_seoParameters['pagenumber'], 	$_seoParameters['threadid'], 	$_seoParameters['goto']
			);

			if (
				$_seoParameters['pp'] == $GLOBALS['perpage'] OR
				$_seoParameters['pp'] == $GLOBALS['vbulletin']->userinfo['maxposts']
			)
			{
				// Default perpage
				unset($_seoParameters['pp']);
			}
		}

		return $newUrl;
	}

	/**
	 * Create URL
	 *
	 * @param string $_seoParameters
	 *
	 * @return string
	 */
	public static function createUrl($_seoParameters)
	{
		$newUrl = $_urlFormat = '';

		if (!DBSEO::$config['dbtech_dbseo_rewrite_thread'])
		{
			// We're not rewriting this
			return $newUrl;
		}

		if (
			(isset($_seoParameters['p']) AND $_seoParameters['p']) OR
			(isset($_seoParameters['postid']) AND $_seoParameters['postid'])
		)
		{
			if ($_seoParameters['do'])
			{
				// This only works for showpost URLs
				return $newUrl;
			}

			// Create URL
			$_urlFormat = 'Thread_GoToPost';
		}
		else if (isset($_seoParameters['goto']) AND $_seoParameters['goto'])
		{
			switch ($_seoParameters['goto'])
			{
				case 'newpost':
					// Next thread
					$_urlFormat = 'Thread_NewPost';
					break;

				case 'lastpost':
					// Previous thread
					$_urlFormat = 'Thread_LastPost';
					break;

				case 'nextnewest':
					// Next thread
					$_urlFormat = 'Thread_Next';
					break;

				case 'nextoldest':
					// Previous thread
					$_urlFormat = 'Thread_Previous';
					break;
			}
		}
		else
		{
			// Plain old thread URL
			$_urlFormat = 'Thread' . (isset($_seoParameters['page']) ? '_Page' : '');
		}

		if (!$_urlFormat)
		{
			// We had no format
			return $newUrl;
		}

		return call_user_func(array('DBSEO_Rewrite_' . $_urlFormat, 'createUrl'), $_seoParameters);
	}
}
?>