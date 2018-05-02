<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// Showpost class

/**
* Handles various functionality for Showpost
*/
class DBSEO_Script_Showpost
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

		if (!DBSEO::$config['dbtech_dbseo_rewrite_showpost'])
		{
			// We're not rewriting this
			return false;
		}

		if ($_POST)
		{
			// We're not touching POST requests
			return false;
		}

		if ($_REQUEST['ajax'] == 1)
		{
			// We're not touching POST requests
			return false;
		}

		// Not much to go on here
		$_urlFormat = 'ShowPost_ShowPost';

		if ($_urlFormat AND $_redirectUrl = DBSEO_Url_Create::create($_urlFormat, $_GET))
		{
			DBSEO::safeRedirect($_queryFile, array('p', 'postcount'));
		}

		// We found a file name
		return 'showpost.php';
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

		if ($_seoParameters['ajax'] == 1)
		{
			// We're not touching POST requests
			return $newUrl;
		}

		// Check up on this
		$_seoParameters['post_count'] = intval($_seoParameters['post_count'] 	? $_seoParameters['post_count'] 	: $_seoParameters['postcount']);
		$_seoParameters['postid'] 	= intval($_seoParameters['postid'] 			? $_seoParameters['postid'] 		: (isset($_seoParameters['p']) ? $_seoParameters['p'] : 0));

		if (!$_seoParameters['post_count'])
		{
			// We're missing post count
			if (!$postInfo = DBSEO::getThreadPostInfo($_seoParameters['postid']))
			{
				// Default value
				$postCount['postcount'] = 1;
			}
			else if (($postCount = DBSEO::$datastore->fetch('postcount.' . $postInfo['threadid'] . '.' . $postInfo['dateline'])) === false)
			{
				// Grab our page settings
				$postCount = DBSEO::$db->generalQuery('
					SELECT (COUNT(*) + 1) AS postcount
					FROM $post
					WHERE threadid = ' . $postInfo['threadid'] . '
						AND visible = 1
						AND dateline < ' . $postInfo['dateline'] . '
				', true);

				// Build the cache
				DBSEO::$datastore->build('postcount.' . $postInfo['threadid'] . '.' . $postInfo['dateline'], $postCount);
			}

			$_seoParameters['post_count'] = intval($postCount['postcount']);
		}

		if (DBSEO::$config['dbtech_dbseo_rewrite_showpost'] AND $_seoParameters['postid'])
		{
			// We're good for making this URL
			$newUrl = DBSEO_Url_Create::create('ShowPost_ShowPost', $_seoParameters);
			$_removeAllParameters = true;
		}

		if ($newUrl)
		{
			// We got this
			$_urlScript = $newUrl;
		}
		else
		{
			// Nope, keep the params
			$_removeAllParameters = false;
		}

		if ($GLOBALS['threadinfo']['replycount'] == ($_seoParameters['post_count'] - 1) AND ($_seoParameters['post_count'] % DBSEO::$config['maxposts']) == 1)
		{
			// Set nofollow
			$nofollow = true;
		}
		else
		{
			// We're good to follow
			$follow = true;
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

		// Check up on this
		$_seoParameters['post_count'] 	= intval($_seoParameters['post_count'] 	? $_seoParameters['post_count'] 	: $_seoParameters['postcount']);
		$_seoParameters['postid'] 		= intval($_seoParameters['postid'] 		? $_seoParameters['postid'] 		: (isset($_seoParameters['p']) ? $_seoParameters['p'] : 0));

		if (!DBSEO::$config['dbtech_dbseo_rewrite_showpost'] OR !$_seoParameters['postid'])
		{
			// We're not rewriting this
			return $newUrl;
		}

		if ($_seoParameters['ajax'] == 1)
		{
			// We're not touching POST requests
			return $newUrl;
		}

		if (!$_seoParameters['post_count'])
		{
			// We're missing post count
			if (!$postInfo = DBSEO::getThreadPostInfo($_seoParameters['postid']))
			{
				// Default value
				$postCount['postcount'] = 1;
			}
			else if (($postCount = DBSEO::$datastore->fetch('postcount.' . $postInfo['threadid'] . '.' . $postInfo['dateline'])) === false)
			{
				// Grab our page settings
				$postCount = DBSEO::$db->generalQuery('
					SELECT (COUNT(*) + 1) AS postcount
					FROM $post
					WHERE threadid = ' . $postInfo['threadid'] . '
						AND visible = 1
						AND dateline < ' . $postInfo['dateline'] . '
				', true);

				// Build the cache
				DBSEO::$datastore->build('postcount.' . $postInfo['threadid'] . '.' . $postInfo['dateline'], $postCount);
			}

			$_seoParameters['post_count'] = intval($postCount['postcount']);
		}

		return DBSEO_Url_Create::create('ShowPost_ShowPost', $_seoParameters);
	}
}
?>