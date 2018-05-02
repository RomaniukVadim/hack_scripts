<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// Blog class

/**
* Handles various functionality for Blog
*/
class DBSEO_Script_Blog
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
		
		if (!DBSEO::$config['dbtech_dbseo_rewrite_blog'])
		{
			// We're not rewriting this
			return false;
		}

		/*
		if (DBSEO_BASEURL == 'entry.php' AND !$_GET['b'])
		{
			// Blog entry file without a blog ID
			return false;
		}
		*/

		if (isset($_GET['userid']))
		{
			// We use "userid" instead of "u"
			$_GET['u'] =& $_GET['userid'];
		}

		if (!count($_GET))
		{
			// Normal blog
			$_urlFormat = 'Blog_Blogs';
		}
		else if ($_GET['blogcategoryid'])
		{
			// Browsing blog category
			if (DBSEO::$config['dbtech_dbseo_rewrite_blogcategory'])
			{
				// Only set this if it's enabled
				$_urlFormat = $_GET['u'] ? 'Blog_BlogCategory' . ($_GET['page'] > 1 ? '_Page' : '') : 'Blog_BlogGlobalCategory' . ($_GET['page'] > 1 ? '_Page' : '');
			}
		}
		else if ($_GET['tag'])
		{
			// Singular blog tag
			if (DBSEO::$config['dbtech_dbseo_rewrite_blogtag'] AND !$_GET['u'])
			{
				// Only set this if it's enabled
				$_urlFormat = 'Blog_BlogTag' . ($_GET['page'] > 1 ? '_Page' : '');
			}
		}
		else if ($_GET['cp'])
		{
			// Custom blog page
			if (DBSEO::$config['dbtech_dbseo_rewrite_blogcustom'])
			{
				// Cache this for the rewrites
				DBSEO::$cache['_objectIds']['blogcustomblock'] = $_GET['cp'];

				// Only set this if it's enabled
				$_urlFormat = 'Blog_CustomBlog';
			}
		}
		else if ($_GET['u'] AND !$_GET['page'] AND !$_GET['do'])
		{
			// User's blog
			$_urlFormat = 'Blog_Blog';
		}
		else if (($_GET['b'] OR $_GET['blogid']) AND count($_GET) == 1)
		{
			// Blog entry
			if (DBSEO::$config['dbtech_dbseo_rewrite_blogentry'])
			{
				// Only set this if it's enabled
				$_urlFormat = 'Blog_BlogEntry';
			}
		}
		else if (DBSEO::$config['dbtech_dbseo_rewrite_bloglist'] AND $_GET['do'] == 'comments' AND !$_GET['type'] AND !$_GET['u'])
		{
			// Blog comment list
			$_urlFormat = 'Blog_BlogComments' . ($_GET['page'] > 1 ? '_Page' : '');
		}
		else if (DBSEO::$config['dbtech_dbseo_rewrite_bloglist'] AND !$_GET['u'] AND $_GET['do'] == 'list' AND (!$_GET['blogtype'] OR in_array($_GET['blogtype'], array('latest', 'recent'))))
		{
			// Blog entry list
			if ($_GET['span'] == '24')
			{
				// Last 24h filter
				$_urlFormat = 'Blog_LatestBlogEntries' . ($_GET['page'] > 1 ? '_Page' : '');
			}
			else if ($_GET['d'])
			{
				// Daily list
				$_urlFormat = 'Blog_BlogsByDay_Global' . ($_GET['page'] > 1 ? '_Page' : '');
			}
			else if ($_GET['m'])
			{
				// Monthly list
				$_urlFormat = 'Blog_BlogsByMonth_Global' . ($_GET['page'] > 1 ? '_Page' : '');
			}
			else
			{
				// Full entry list
				$_urlFormat = 'Blog_RecentBlogEntries' . ($_GET['page'] > 1 ? '_Page' : '');
			}
		}
		else if (DBSEO::$config['dbtech_dbseo_rewrite_bloglist'] AND $_GET['do'] == 'bloglist')
		{
			// All Blogs list
			$_urlFormat = 'Blog_AllBlogs' . ($_GET['page'] > 1 ? '_Page' : '');
		}

		if ($_urlFormat AND $_redirectUrl = DBSEO_Url_Create::create($_urlFormat, $_GET))
		{
			// We had a redirect URL, so get to it!
			DBSEO::safeRedirect($_redirectUrl, array('u', 'userid', 'blogcategoryid', 'b', 'do', 'page', 'blogid', 'blogtype', 'd', 'm', 'y', 'tag', 'cp'));
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
		$newUrl = $_urlFormat = $_urlSuffix = '';
		$noClear = false;

		if (!DBSEO::$config['dbtech_dbseo_rewrite_blog'])
		{
			// We're not rewriting this
			return $newUrl;
		}

		if (isset($_seoParameters['userid']))
		{
			// We use "userid" instead of "u"
			$_seoParameters['u'] = $_seoParameters['userid'];
			unset($_seoParameters['userid']);
		}

		if (!count($_seoParameters))
		{
			// Normal blog
			$_urlFormat = 'Blog_Blogs';
		}
		else if ($_seoParameters['tag'] AND !$_seoParameters['u'])
		{
			// Singular blog tag
			if (DBSEO::$config['dbtech_dbseo_rewrite_blogtag'])
			{
				// Only set this if it's enabled
				$_urlFormat = 'Blog_BlogTag' . ($_seoParameters['page'] > 1 ? '_Page' : '');
			}
		}
		else if ($_seoParameters['u'])
		{
			// User blogs
			if (count($_seoParameters) == 1 OR (count($_seoParameters) == 2 AND $_seoParameters['blogtype'] == 'recent'))
			{
				// User blog
				$_urlFormat = 'Blog_Blog';
			}
			else if ($_seoParameters['page'] AND (count($_seoParameters) == 2 OR (count($_seoParameters) == 3 AND $_seoParameters['blogtype'] == 'recent')))
			{
				// User blog with page
				$_urlFormat = 'Blog_Blog_Page';
			}
			else if ($_seoParameters['blogcategoryid'])
			{
				if (DBSEO::$config['dbtech_dbseo_rewrite_blogcategory'])
				{
					$_urlFormat = 'Blog_BlogCategory' . ($_seoParameters['page'] > 1 ? '_Page' : '');
				}
			}
			else if (DBSEO::$config['dbtech_dbseo_rewrite_bloglist'])
			{
				if ($_seoParameters['d'] AND !$_seoParameters['page'])
				{
					// Daily user blogs
					$_urlFormat = 'Blog_BlogsByDay_User';
				}
				else if ($_seoParameters['m'] AND !$_seoParameters['page'])
				{
					// Monthly user blogs
					$_urlFormat = 'Blog_BlogsByMonth_User';
				}
			}
		}
		else if ($_seoParameters['blogcategoryid'])
		{
			// Browsing blog category
			if (DBSEO::$config['dbtech_dbseo_rewrite_blogcategory'])
			{
				// Only set this if it's enabled
				$_urlFormat = 'Blog_BlogGlobalCategory' . ($_seoParameters['page'] > 1 ? '_Page' : '');
			}
		}
		else if (($_seoParameters['b'] OR $_seoParameters['blogid']) AND (count($_seoParameters) == 1 OR $_seoParameters['goto'] == 'newpost'))
		{
			// Blog entry
			if (DBSEO::$config['dbtech_dbseo_rewrite_blogentry'])
			{
				// Only set this if it's enabled
				$_urlFormat = 'Blog_BlogEntry';
			}
		}
		else if ($_seoParameters['cp'] AND count($_seoParameters) == 1)
		{
			// Custom blog page
			if (DBSEO::$config['dbtech_dbseo_rewrite_blogcustom'])
			{
				// Only set this if it's enabled
				$_urlFormat = 'Blog_CustomBlog';
			}
		}
		else if (($_seoParameters['b'] OR $_seoParameters['blogid']) AND count($_seoParameters) == 2 AND $_seoParameters['page'])
		{
			// Blog entry
			if (DBSEO::$config['dbtech_dbseo_rewrite_blogentry'])
			{
				// Only set this if it's enabled
				$_urlFormat = 'Blog_BlogEntry_Page';
			}
		}
		else if ($_seoParameters['b'] AND $_seoParameters['goto'])
		{
			// Next / Prev blog
			if (DBSEO::$config['dbtech_dbseo_rewrite_blogentry'])
			{
				// Only set this if it's enabled
				$_urlFormat = 'Blog_' . ($_seoParameters['goto'] == 'next' ? 'Next' : 'Prev') . 'BlogEntry';
			}
		}
		else if ($_seoParameters['bt'] AND (strpos($url, 'blog.') !== false OR strpos($url, 'entry.') !== false) AND (count($_seoParameters) == 1 OR (count($_seoParameters) == 2 AND $_seoParameters['b'])))
		{
			// Blog text
			if (DBSEO::$config['dbtech_dbseo_rewrite_blogentry'])
			{
				// Singular blog comment
				$_urlFormat = 'Blog_BlogComment';
			}
		}
		else if (DBSEO::$config['dbtech_dbseo_rewrite_bloglist'] AND $_seoParameters['do'] == 'bloglist')
		{
			if (!$_seoParameters['blogtype'])
			{
				// All Blogs list
				$_urlFormat = 'Blog_AllBlogs' . ($_seoParameters['page'] > 1 ? '_Page' : '');

				// We're not clearing all params
				$noClear = true;

				// Get rid of a select few
				unset($_seoParameters['do']);
			}
			else if ($_seoParameters['blogtype'] == 'best')
			{
				// Best Blogs list
				$_urlFormat = 'Blog_BestBlogs' . ($_seoParameters['page'] > 1 ? '_Page' : '');
			}
		}
		else if (DBSEO::$config['dbtech_dbseo_rewrite_bloglist'] AND $_seoParameters['do'] == 'comments')
		{
			// Blog comments list
			$_urlFormat = 'Blog_BlogComments' . ($_seoParameters['page'] > 1 ? '_Page' : '');
		}
		else if (DBSEO::$config['dbtech_dbseo_rewrite_bloglist'] AND $_seoParameters['do'] == 'list')
		{
			// Blog entry list
			if ((!$_seoParameters['blogtype'] AND !$_seoParameters['y'] AND !$_seoParameters['span']) OR in_array($_seoParameters['blogtype'], array('recent', 'latest')))
			{
				// Recent blog entries list
				$_urlFormat = 'Blog_RecentBlogEntries' . ($_seoParameters['page'] > 1 ? '_Page' : ''); 
				
				// We're not removing all parameters
				$noClear = true;

				// Get rid of a select few
				unset($_seoParameters['do'], $_seoParameters['blogtype']);
			}
			else if ($_seoParameters['blogtype'] == 'best')
			{
				// Recent blog entries list
				$_urlFormat = 'Blog_BestBlogEntries' . ($_seoParameters['page'] > 1 ? '_Page' : ''); 
				
				// We're not removing all parameters
				$noClear = true;

				// Get rid of a select few
				unset($_seoParameters['do'], $_seoParameters['blogtype']);
			}
			else if ($_seoParameters['span'] == '24')
			{
				// Last 24h filter
				$_urlFormat = 'Blog_LatestBlogEntries' . ($_seoParameters['page'] > 1 ? '_Page' : '');
			}
			else if ($_seoParameters['d'])
			{
				// Daily list
				$_urlFormat = 'Blog_BlogsByDay_Global' . ($_seoParameters['page'] > 1 ? '_Page' : '');
			}
			else if ($_seoParameters['m'])
			{
				// Monthly list
				$_urlFormat = 'Blog_BlogsByMonth_Global' . ($_seoParameters['page'] > 1 ? '_Page' : '');
			}
		}

		if ($_urlFormat AND $newUrl = DBSEO_Url_Create::create($_urlFormat, $_seoParameters))
		{
			// Get rid of a select few
			unset($_seoParameters['page']);

			$_urlScript = $newUrl . $_urlSuffix;
			if (!$noClear)
			{
				// Unset all parameters
				$_removeAllParameters = true;
			}
		}
		else
		{
			// Don't process this further
			$_preventProcessing = true;
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

		if (!DBSEO::$config['dbtech_dbseo_rewrite_blog'])
		{
			// We're not rewriting this
			return $newUrl;
		}

		if (isset($_seoParameters['userid']))
		{
			// We use "userid" instead of "u"
			$_seoParameters['u'] = $_seoParameters['userid'];
		}

		if (!count($_seoParameters))
		{
			// Normal blog
			$_urlFormat = 'Blog_Blogs';
		}
		else if ($_seoParameters['tag'] AND !$_seoParameters['u'])
		{
			// Singular blog tag
			if (DBSEO::$config['dbtech_dbseo_rewrite_blogtag'])
			{
				// Only set this if it's enabled
				$_urlFormat = 'Blog_BlogTag' . ($_seoParameters['page'] > 1 ? '_Page' : '');
			}
		}
		else if ($_seoParameters['u'])
		{
			// User blogs
			if (count($_seoParameters) == 1 OR (count($_seoParameters) == 2 AND $_seoParameters['blogtype'] == 'recent'))
			{
				// User blog
				$_urlFormat = 'Blog_Blog';
			}
			else if ($_seoParameters['page'] AND (count($_seoParameters) == 2 OR (count($_seoParameters) == 3 AND $_seoParameters['blogtype'] == 'recent')))
			{
				// User blog with page
				$_urlFormat = 'Blog_Blog_Page';
			}
			else if ($_seoParameters['blogcategoryid'])
			{
				if (DBSEO::$config['dbtech_dbseo_rewrite_blogcategory'])
				{
					$_urlFormat = 'Blog_BlogCategory' . ($_seoParameters['page'] > 1 ? '_Page' : '');
				}
			}
			else if (DBSEO::$config['dbtech_dbseo_rewrite_bloglist'])
			{
				if ($_seoParameters['d'] AND !$_seoParameters['page'])
				{
					// Daily user blogs
					$_urlFormat = 'Blog_BlogsByDay_User';
				}
				else if ($_seoParameters['m'] AND !$_seoParameters['page'])
				{
					// Monthly user blogs
					$_urlFormat = 'Blog_BlogsByMonth_User';
				}
			}
		}
		else if ($_seoParameters['blogcategoryid'])
		{
			// Browsing blog category
			if (DBSEO::$config['dbtech_dbseo_rewrite_blogcategory'])
			{
				// Only set this if it's enabled
				$_urlFormat = 'Blog_BlogGlobalCategory' . ($_seoParameters['page'] > 1 ? '_Page' : '');
			}
		}
		else if (($_seoParameters['b'] OR $_seoParameters['blogid']) AND (count($_seoParameters) == 1 OR $_seoParameters['goto'] == 'newpost'))
		{
			// Blog entry
			if (DBSEO::$config['dbtech_dbseo_rewrite_blogentry'])
			{
				// Only set this if it's enabled
				$_urlFormat = 'Blog_BlogEntry';
			}
		}
		else if ($_seoParameters['cp'] AND count($_seoParameters) == 1)
		{
			// Custom blog page
			if (DBSEO::$config['dbtech_dbseo_rewrite_blogcustom'])
			{
				// Only set this if it's enabled
				$_urlFormat = 'Blog_CustomBlog';
			}
		}
		else if (($_seoParameters['b'] OR $_seoParameters['blogid']) AND count($_seoParameters) == 2 AND $_seoParameters['page'])
		{
			// Blog entry
			if (DBSEO::$config['dbtech_dbseo_rewrite_blogentry'])
			{
				// Only set this if it's enabled
				$_urlFormat = 'Blog_BlogEntry_Page';
			}
		}
		else if ($_seoParameters['b'] AND $_seoParameters['goto'])
		{
			// Next / Prev blog
			if (DBSEO::$config['dbtech_dbseo_rewrite_blogentry'])
			{
				// Only set this if it's enabled
				$_urlFormat = 'Blog_' . ($_seoParameters['goto'] == 'next' ? 'Next' : 'Prev') . 'BlogEntry';
			}
		}
		else if ($_seoParameters['bt'] AND (strpos($url, 'blog.') !== false OR strpos($url, 'entry.') !== false) AND (count($_seoParameters) == 1 OR (count($_seoParameters) == 2 AND $_seoParameters['b'])))
		{
			// Blog text
			if (DBSEO::$config['dbtech_dbseo_rewrite_blogentry'])
			{
				// Singular blog comment
				$_urlFormat = 'Blog_BlogComment';
			}
		}
		else if (DBSEO::$config['dbtech_dbseo_rewrite_bloglist'] AND $_seoParameters['do'] == 'bloglist')
		{
			if (!$_seoParameters['blogtype'])
			{
				// All Blogs list
				$_urlFormat = 'Blog_AllBlogs' . ($_seoParameters['page'] > 1 ? '_Page' : '');

				// Get rid of a select few
				unset($_seoParameters['do']);
			}
			else if ($_seoParameters['blogtype'] == 'best')
			{
				// Best Blogs list
				$_urlFormat = 'Blog_BestBlogs' . ($_seoParameters['page'] > 1 ? '_Page' : '');
			}
		}
		else if (DBSEO::$config['dbtech_dbseo_rewrite_bloglist'] AND $_seoParameters['do'] == 'comments')
		{
			// Blog comments list
			$_urlFormat = 'Blog_BlogComments' . ($_seoParameters['page'] > 1 ? '_Page' : '');
		}
		else if (DBSEO::$config['dbtech_dbseo_rewrite_bloglist'] AND $_seoParameters['do'] == 'list')
		{
			// Blog entry list
			if ((!$_seoParameters['blogtype'] AND !$_seoParameters['y'] AND !$_seoParameters['span']) OR in_array($_seoParameters['blogtype'], array('recent', 'latest')))
			{
				// Recent blog entries list
				$_urlFormat = 'Blog_RecentBlogEntries' . ($_seoParameters['page'] > 1 ? '_Page' : ''); 
			}
			else if ($_seoParameters['blogtype'] == 'best')
			{
				// Recent blog entries list
				$_urlFormat = 'Blog_BestBlogEntries' . ($_seoParameters['page'] > 1 ? '_Page' : ''); 
			}
			else if ($_seoParameters['span'] == '24')
			{
				// Last 24h filter
				$_urlFormat = 'Blog_LatestBlogEntries' . ($_seoParameters['page'] > 1 ? '_Page' : '');
			}
			else if ($_seoParameters['d'])
			{
				// Daily list
				$_urlFormat = 'Blog_BlogsByDay_Global' . ($_seoParameters['page'] > 1 ? '_Page' : '');
			}
			else if ($_seoParameters['m'])
			{
				// Monthly list
				$_urlFormat = 'Blog_BlogsByMonth_Global' . ($_seoParameters['page'] > 1 ? '_Page' : '');
			}
		}

		if (!$_urlFormat)
		{
			// We're not rewriting this
			return $newUrl;
		}

		return DBSEO_Url_Create::create($_urlFormat, $_seoParameters);
	}
}
?>