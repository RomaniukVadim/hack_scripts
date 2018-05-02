<?php if(!defined('IN_DBSEO')) die('Access denied.');

/**
 * DBSEO_Url
 *
 * @package DBSEO
 * @access public
 */
class DBSEO_Url
{
	/**
	* Duplicated config array
	*
	* @public	array
	*/
	protected static $config = array();

	/**
	* Array of configuration items
	*
	* @public	array
	*/
	protected static $cache = array();

	/**
	* Array of library items
	*
	* @public	array
	*/
	public static $libraries 		= array(
		'Forum' => array(
			'Forum' 							=> array('strict' => false, 'subsetting' => '', 			'priority' => 1030),
			'Forum_Page' 						=> array('strict' => false, 'subsetting' => '', 			'priority' => 1020),
			'Forum_Prefix' 						=> array('strict' => false, 'subsetting' => '', 			'priority' => 1032),
			'Forum_Prefix_Page' 				=> array('strict' => false, 'subsetting' => '', 			'priority' => 1031),
		),
		'Announcement' => array(
			'Announcement' 						=> array('strict' => false, 'subsetting' => '', 			'priority' => 1000),
			'Announcement_Multiple' 			=> array('strict' => false, 'subsetting' => '', 			'priority' => 1010),
		),
		'Thread' => array(
			'Thread' 							=> array('strict' => false, 'subsetting' => '', 			'priority' => 990),
			'Thread_Page' 						=> array('strict' => false, 'subsetting' => '', 			'priority' => 980),
			'Thread_LastPost' 					=> array('strict' => false, 'subsetting' => '', 			'priority' => 530),
			'Thread_NewPost' 					=> array('strict' => false, 'subsetting' => '', 			'priority' => 499),
			'Thread_GoToPost' 					=> array('strict' => false, 'subsetting' => '', 			'priority' => 498),
			'Thread_GoToPost_Page' 				=> array('strict' => false, 'subsetting' => '', 			'priority' => 499),
			'Thread_Previous' 					=> array('strict' => false, 'subsetting' => '', 			'priority' => 570),
			'Thread_Next' 						=> array('strict' => false, 'subsetting' => '', 			'priority' => 560),
			'PrintThread' 						=> array('strict' => false, 'subsetting' => '', 			'priority' => 970),
			'PrintThread_Page' 					=> array('strict' => false, 'subsetting' => '', 			'priority' => 960),
		),
		'ShowPost' => array(
			'ShowPost' 							=> array('strict' => false, 'subsetting' => '', 			'priority' => 510),
		),
		'Poll' => array(
			'Poll' 								=> array('strict' => false, 'subsetting' => '', 			'priority' => 490),
		),
		'MemberList' => array(
			'MemberList' 						=> array('strict' => false, 'subsetting' => '', 			'priority' => 590),
			'MemberList_Page' 					=> array('strict' => false, 'subsetting' => '', 			'priority' => 580),
			'MemberList_Letter' 				=> array('strict' => false, 'subsetting' => '', 			'priority' => 600),
		),
		'Avatar' => array(
			'Avatar' 							=> array('strict' => false, 'subsetting' => '', 			'priority' => 1099),
		),
		'NavBullet' => array(
			'NavBullet_Forum' 					=> array('strict' => false, 'subsetting' => '', 			'priority' => -1),
			'NavBullet_Thread' 					=> array('strict' => false, 'subsetting' => '', 			'priority' => -1),
		),
		'Attachment' => array(
			'Attachment' 						=> array('strict' => false, 'subsetting' => '', 			'priority' => 1100),
			'Attachment_Alt' 					=> array('strict' => false, 'subsetting' => '', 			'priority' => -1),
			'BlogAttachment' 					=> array('strict' => false, 'subsetting' => '', 			'priority' => 1110),
			'CMSAttachments' 					=> array('strict' => false, 'subsetting' => '', 			'priority' => 1120),
		),
		'Tags' => array(
			'TagList' 							=> array('strict' => true, 'subsetting' => '', 				'priority' => 800),
			'Tag_Single' 						=> array('strict' => true, 'subsetting' => '', 				'priority' => 790),
			'Tag_Single_Page' 					=> array('strict' => true, 'subsetting' => '',				'priority' => 780),
		),
		'CMS' => array(
			'CMSHome' 							=> array('strict' => true, 'subsetting' => '', 				'priority' => 420),
			'CMSSection' 						=> array('strict' => true, 'subsetting' => '', 				'priority' => 480),
			'CMSSection_Page' 					=> array('strict' => true, 'subsetting' => '', 				'priority' => 485),
			'CMSSection_List' 					=> array('strict' => true, 'subsetting' => '', 				'priority' => 460),
			'CMSSection_List_Page' 				=> array('strict' => true, 'subsetting' => '', 				'priority' => 470),
			'CMSCategory' 						=> array('strict' => true, 'subsetting' => '', 				'priority' => 440),
			'CMSCategory_Page' 					=> array('strict' => true, 'subsetting' => '', 				'priority' => 430),
			'CMSAuthor' 						=> array('strict' => true, 'subsetting' => '', 				'priority' => 460),
			'CMSAuthor_Page' 					=> array('strict' => true, 'subsetting' => '', 				'priority' => 450),
			'CMSEntry' 							=> array('strict' => true, 'subsetting' => '', 				'priority' => 410),
			'CMSEntry_Page' 					=> array('strict' => true, 'subsetting' => '', 				'priority' => 400),
			'CMSComments_Page' 					=> array('strict' => true, 'subsetting' => '', 				'priority' => 380),
		),
		'Blog' => array(
			'Blog' 								=> array('strict' => false, 'subsetting' => '', 			'priority' => 300),
			'Blog_Page' 						=> array('strict' => false, 'subsetting' => '', 			'priority' => 290),
			'Blogs' 							=> array('strict' => false, 'subsetting' => '', 			'priority' => 90),
			'BlogEntry' 						=> array('strict' => false, 'subsetting' => 'entry', 		'priority' => 85),
			'BlogEntry_Page' 					=> array('strict' => false, 'subsetting' => 'entry', 		'priority' => 80),
			'BlogComment' 						=> array('strict' => false, 'subsetting' => 'entry', 		'priority' => 117),
			'NextBlogEntry' 					=> array('strict' => false, 'subsetting' => 'entry', 		'priority' => 128),
			'PrevBlogEntry' 					=> array('strict' => false, 'subsetting' => 'entry', 		'priority' => 129),
			'BlogGlobalCategory' 				=> array('strict' => false, 'subsetting' => 'category', 	'priority' => 110),
			'BlogGlobalCategory_Page' 			=> array('strict' => false, 'subsetting' => 'category', 	'priority' => 100),
			'BlogCategory' 						=> array('strict' => false, 'subsetting' => 'category', 	'priority' => 130),
			'BlogCategory_Page' 				=> array('strict' => false, 'subsetting' => 'category', 	'priority' => 120),
			'BlogsByDay_Global' 				=> array('strict' => false, 'subsetting' => 'list', 		'priority' => 60),
			'BlogsByDay_Global_Page' 			=> array('strict' => false, 'subsetting' => 'list', 		'priority' => 50),
			'BlogsByMonth_Global' 				=> array('strict' => false, 'subsetting' => 'list', 		'priority' => 40),
			'BlogsByMonth_Global_Page' 			=> array('strict' => false, 'subsetting' => 'list', 		'priority' => 30),
			'AllBlogs' 							=> array('strict' => false, 'subsetting' => 'list', 		'priority' => 20),
			'AllBlogs_Page' 					=> array('strict' => false, 'subsetting' => 'list', 		'priority' => 10),
			'RecentBlogEntries' 				=> array('strict' => false, 'subsetting' => 'list', 		'priority' => 118),
			'RecentBlogEntries_Page' 			=> array('strict' => false, 'subsetting' => 'list', 		'priority' => 119),
			'LatestBlogEntries' 				=> array('strict' => false, 'subsetting' => 'list', 		'priority' => 60),
			'LatestBlogEntries_Page' 			=> array('strict' => false, 'subsetting' => 'list', 		'priority' => 50),
			'BestBlogEntries' 					=> array('strict' => false, 'subsetting' => 'list', 		'priority' => 350),
			'BestBlogEntries_Page' 				=> array('strict' => false, 'subsetting' => 'list', 		'priority' => 340),
			'BestBlogs' 						=> array('strict' => false, 'subsetting' => 'list', 		'priority' => 330),
			'BestBlogs_Page' 					=> array('strict' => false, 'subsetting' => 'list', 		'priority' => 320),
			'BlogComments' 						=> array('strict' => false, 'subsetting' => 'list', 		'priority' => 360),
			'BlogComments_Page' 				=> array('strict' => false, 'subsetting' => 'list', 		'priority' => 370),
			'BlogsByDay_User' 					=> array('strict' => false, 'subsetting' => 'list', 		'priority' => 270),
			'BlogsByMonth_User' 				=> array('strict' => false, 'subsetting' => 'list', 		'priority' => 280),
			'CustomBlog' 						=> array('strict' => false, 'subsetting' => 'custom', 		'priority' => 70),
			'BlogFeedUser' 						=> array('strict' => false, 'subsetting' => 'feed', 		'priority' => 140),
			'BlogFeedGlobal' 					=> array('strict' => false, 'subsetting' => 'feed', 		'priority' => 150),
			'BlogTags' 							=> array('strict' => false, 'subsetting' => 'tag', 			'priority' => 40),
			'BlogTag' 							=> array('strict' => false, 'subsetting' => 'tag', 			'priority' => 30),
			'BlogTag_Page' 						=> array('strict' => false, 'subsetting' => 'tag', 			'priority' => 20),
		),
		'SocialGroup' => array(
			'SocialGroup' 						=> array('strict' => false, 'subsetting' => '', 			'priority' => 760),
			'SocialGroup_Page' 					=> array('strict' => false, 'subsetting' => '', 			'priority' => 750),
			'SocialGroupDiscussion' 			=> array('strict' => false, 'subsetting' => '', 			'priority' => 660),
			'SocialGroupDiscussion_Page' 		=> array('strict' => false, 'subsetting' => '', 			'priority' => 650),
			'SocialGroupDiscussion_LastPost' 	=> array('strict' => false, 'subsetting' => '', 			'priority' => 655),
			'SocialGroupList' 					=> array('strict' => false, 'subsetting' => '', 			'priority' => 610),
			'SocialGroupList_Page' 				=> array('strict' => false, 'subsetting' => '', 			'priority' => 620),
			'SocialGroupMembers' 				=> array('strict' => false, 'subsetting' => '', 			'priority' => 680),
			'SocialGroupMembers_Page' 			=> array('strict' => false, 'subsetting' => '', 			'priority' => 670),
			'SocialGroupPictures' 				=> array('strict' => false, 'subsetting' => '', 			'priority' => 700),
			'SocialGroupPictures_Page' 			=> array('strict' => false, 'subsetting' => '', 			'priority' => 690),
			'SocialGroupPicture' 				=> array('strict' => false, 'subsetting' => '', 			'priority' => 720),
			'SocialGroupPicture_Page' 			=> array('strict' => false, 'subsetting' => '', 			'priority' => 710),
			'SocialGroupCategoryList' 			=> array('strict' => false, 'subsetting' => '', 			'priority' => 640),
			'SocialGroupCategoryList_Page' 		=> array('strict' => false, 'subsetting' => '', 			'priority' => 630),
			'SocialGroupCategory' 				=> array('strict' => false, 'subsetting' => '', 			'priority' => 750),
			'SocialGroupCategory_Page' 			=> array('strict' => false, 'subsetting' => '', 			'priority' => 740),
			'SocialGroupHome' 					=> array('strict' => false, 'subsetting' => '', 			'priority' => 770),
			'SocialGroupPictureFile' 			=> array('strict' => false, 'subsetting' => '', 			'priority' => 730),
		),
		'MemberProfile' => array(
			'MemberProfile' 					=> array('strict' => true, 'subsetting' => '', 			'priority' => 940),
			'VisitorMessage_Page' 				=> array('strict' => true, 'subsetting' => '', 			'priority' => 930),
			'VisitorMessage_Conversation' 		=> array('strict' => true, 'subsetting' => '', 			'priority' => 920),
			'VisitorMessage_Conversation_Page' 	=> array('strict' => true, 'subsetting' => '', 			'priority' => 910),
			'FriendsList_Page' 					=> array('strict' => true, 'subsetting' => '', 			'priority' => 900),
		),
		'Album' => array(
			'Album' 							=> array('strict' => true, 'subsetting' => '', 			'priority' => 890),
			'Album_Page' 						=> array('strict' => true, 'subsetting' => '', 			'priority' => 880),
			'AlbumList' 						=> array('strict' => true, 'subsetting' => '', 			'priority' => 860),
			'AlbumList_Page' 					=> array('strict' => true, 'subsetting' => '', 			'priority' => 870),
			'AlbumPicture' 						=> array('strict' => true, 'subsetting' => '', 			'priority' => 820),
			'AlbumPicture_Page' 				=> array('strict' => true, 'subsetting' => '', 			'priority' => 810),
			'MemberAlbums' 						=> array('strict' => true, 'subsetting' => '', 			'priority' => 850),
			'MemberAlbums_Page' 				=> array('strict' => true, 'subsetting' => '', 			'priority' => 840),
			'AlbumPictureFile' 					=> array('strict' => true, 'subsetting' => '', 			'priority' => 830),
		),
	);

	/**
	* Array of suggested URLs
	*
	* @public	array
	*/
	public static $suggestedUrls = array();


	/**
	 * Initialises the URL relevant stuff
	 */
	public static function __init()
	{
		// Compatibility
		self::$config =& DBSEO::$config;
		self::$cache =& DBSEO::$cache;

		// Normal icons
		$d = dir(DBSEO_CWD . '/dbtech/dbseo/includes/addons/library');
		while (false !== ($file = $d->read()))
		{
			if (!in_array($file, array('.', '..', 'index.html', 'library')) AND pathinfo($file, PATHINFO_EXTENSION) == 'php')
			{
				// Grab our config file
				require_once(DBSEO_CWD . '/dbtech/dbseo/includes/addons/library/' . $file);
			}

		}
		$d->close();

		switch (DBSEO::$config['dbtech_dbseo_filter_nonlatin_chars'])
		{
			case 0:
				$chars = '\S';
				$set = '[^/]';
				break;

			case 1:
				$chars = 'a-z\._';
				$set = '[' . $chars . 'A-Z\d-]';
				break;

			default:
				$chars = 'a-z\._\\' . DBSEO::$config['dbtech_dbseo_rewrite_separator'] . 'ŠŽšžŸÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÑÒÓÔÕÖØÙÚÛÜÝàáâãäåçèéêëìíîïñòóôõöøùúûüýÿµ';
				$set = '[' . $chars . 'A-Z\d-]';
				break;
		}

		$replace = array(
			'#%attachment_id%#' 		=> '([dt\d]+)',
			'#%picture_id%#' 			=> '([dt\d]+)',
			'#%[a-z_]+_id%#' 			=> '(\d+)',
			'#%year%#' 					=> '(\d+)',
			'#%month%#' 				=> '(\d+)',
			'#%day%#' 					=> '(\d+)',
			'#%[a-z_]+_path%#' 			=> '([' . $chars . 'A-Z\d/-]+)',
			'#%[a-z_]+_filename%#' 		=> '(.+)',
			'#%tag%#' 					=> '(.+)',
			'#%(album|group)_title%#' 	=> '([^/]+)',
			'#%[a-z_]+_name%#' 			=> '([^/]+)',
			'#%[a-z_]+_title%#' 		=> '(' . $set . '+)',
			'#%[a-z_]+_ext%#' 			=> '([^/]+)',
			'#%post_count%#' 			=> '(\d*?)',
			'#%letter%#' 				=> '([a-z]|0|all)',
			'#%[a-z_]*page%#' 			=> '(\d+)',
			'#%[a-z_]+%#' 				=> '(' . $set . ')+',
		);

		if (DBSEO::$config['dbtech_dbseo_rewrite_rule_forumpath'] == 'custom')
		{
			// Override forum path with the custom string
			DBSEO::$config['dbtech_dbseo_rewrite_rule_forumpath'] = str_replace(array('[', ']'), '%', DBSEO::$config['dbtech_dbseo_rewrite_rule_forumpath_custom']);
		}

		foreach (self::$libraries as $optionGroup => $options)
		{
			// Shorthand
			$optionGroup = strtolower($optionGroup);

			foreach ($options as $option => $optionInfo)
			{
				// Ensure this is properly set
				$optionKey = 'dbtech_dbseo_rewrite_rule_' . strtolower($option);

				// By default, assume actual value
				$optionValue = DBSEO::$config[$optionKey];

				if ($optionValue == 'custom')
				{
					// We need to do some replacement trickery for custom ones
					$optionValue = str_replace(array('[', ']'), '%', DBSEO::$config[$optionKey . '_custom']);
				}

				// Store the raw URLs
				DBSEO::$cache['rawurls'][$optionGroup][$option] = $optionValue;

				// Store the prepared URLs
				DBSEO::$cache['preparedurls'][$optionGroup][$option] = preg_replace(array_keys($replace), $replace, preg_quote($optionValue, '#'));
			}
		}

		/*DBTECH_PRO_START*/
		$customRedirects = preg_split('#\r?\n#s', DBSEO::$config['dbtech_dbseo_customredirect'], -1, PREG_SPLIT_NO_EMPTY);
		foreach ($customRedirects as $key => $val)
		{
			$pos = strpos($val, '//');
			if ($pos !== false AND $pos == 0)
			{
				// Skip this, it was disabled
				continue;
			}

			// Ensure we split this properly
			$val = preg_split('#\s*=>\s*#s', $val, -1, PREG_SPLIT_NO_EMPTY);

			$val[0] = substr($val[0], 1, -1);
			$val[1] = substr($val[1], 1, -1);

			// Store the raw URLs
			DBSEO::$cache['rawurls']['customredirect']['#' . str_replace(array('#', '&'), array('\#', '&(?:amp;)?'), $val[0]) . '#'] = str_replace('[NF]', '', $val[1]);
		}

		$customRules = preg_split('#\r?\n#s', DBSEO::$config['dbtech_dbseo_customrewrite'], -1, PREG_SPLIT_NO_EMPTY);
		foreach ($customRules as $key => $val)
		{
			$pos = strpos($val, '//');
			if ($pos !== false AND $pos == 0)
			{
				// Skip this, it was disabled
				continue;
			}

			// Ensure we split this properly
			$val = preg_split('#\s*=>\s*#s', $val, -1, PREG_SPLIT_NO_EMPTY);

			$val[0] = substr($val[0], 1, -1);
			$val[1] = substr($val[1], 1, -1);

			// Store the raw URLs
			DBSEO::$cache['rawurls']['custom']['#' . str_replace(array('#', '&'), array('\#', '&(?:amp;)?'), $val[0]) . '#'] = $val[1];

			if (substr($val[1], 0, 1) == '/')
			{
				// Get rid of starting dir mark
				$val[1] = substr($val[1], 1);
			}

			// Get rid of this
			$val[1] = str_replace('[NF]', '', $val[1]);

			// Store the matches we need
			preg_match_all('#\$(\d+)#', $val[1], DBSEO::$cache['numberMatches']);
			preg_match_all('#\(.*?\)#', $val[0], DBSEO::$cache['otherMatches']);

			// Replace the other matches
			$val[1] = preg_replace_callback(
				'#\$(\d+)#i',
				array('DBSEO', 'customRuleMatchOther'),
				str_replace('\$', '$', preg_quote($val[1], '#'))
			);

			// Init the counter
			DBSEO::$cache['numberMatchCounter'] = 0;

			// First get rid of a bunch of slashes
			$val[0] = preg_replace('#[^\\\\\]\)]\?#', 	'', $val[0]);

			// Do number replacements
			$val[0] = preg_replace_callback(
				'#\(.*?\)#i',
				array('DBSEO', 'customRuleMatchNumber'),
				stripslashes($val[0])
			);

			// Then do the last 2 replacements
			$val[0] = preg_replace(array('#\$\d+\?#', '#.[\*\+]\??#'), '', $val[0]);

			if ($val[0][0] == '^')
			{
				// We started with a ^
				$val[1] = '^' . $val[1];
				$val[0] = substr($val[0], 1);
			}

			$suffix = '';
			if (substr($val[0], -1) == '$')
			{
				// We need to suffix our EOL
				$suffix = '$';
				$val[0] = substr($val[0], 0, strlen($val[0]) - 1);
			}

			// Store the URL with suffix in the pattern
			DBSEO::$cache['preparedurls']['custom'][0]['#' . $val[1] . $suffix . '#'] = $val[0];

			if (substr($val[1], -1) == '/')
			{
				// Allow for URL params
				$val[1] .= '?';
				$val[0] .= '#s#';
			}

			// Suffix the, well, suffix
			$val[1] .= $suffix;

			// Store the URL with the suffix in the rule
			DBSEO::$cache['preparedurls']['custom'][1]['#' . $val[1] . '#'] = $val[0];
		}
		/*DBTECH_PRO_END*/
	}

	/**
	 * Resolves a SEO'd URL back to its original counterpart
	 *
	 * @param string $url
	 *
	 * @return mixed
	 */
	public static function lookup(&$url, &$fileExists, &$fileExistsDeep)
	{
		// Default retval
		$_retVal = array(false, '', '');

		if (!$url OR $fileExists OR ($fileExistsDeep AND $url != '/'))
		{
			return $_retVal;
		}

		// Check custom redirects
		DBSEO_Url::resolve('Custom_CustomRedirect', $url);

		if ($_fileName = DBSEO_Url::resolve('Custom_CustomRewrite', $url) OR count(self::$suggestedUrls))
		{
			// Ensure we've updated our environment
			//DBSEO::updateEnvironment(DBSEO_RELPATH . $_fileName);

			// Parse the resulting file
			$parsedUrl = @parse_url(preg_replace('#\?.*$#', '', $_fileName));
			if (is_array($parsedUrl))
			{
				// We're done here
				return array(!count(self::$suggestedUrls), $parsedUrl['path'], array_shift(self::$suggestedUrls));
			}
			else
			{
				return array(!count(self::$suggestedUrls), '', array_shift(self::$suggestedUrls));
			}
		}

		if (DBSEO_RELPATH)
		{
			// We have a relpath or whatever
			return $_retVal;
		}

		// By default, we need to continue
		$_continue = true;

		if (DBSEO::$config['dbtech_dbseo_persistenturl'])
		{
			if ($info = DBSEO::$db->generalQuery('
				SELECT *
				FROM $dbtech_dbseo_resolvedurl
				WHERE seourl = \'' . DBSEO::$db->escape_string($url) . '\'
				LIMIT 1
			'))
			{
				// Set environment
				DBSEO::updateEnvironment($info['forumurl']);

				// Set file name
				$_fileName = $_SERVER['DBSEO_FILE'];

				// Return the proper values
				return array(true, $_fileName, '');
			}
		}

		// Init some arrays we need
		$_formatsByLibrary = $_formatPriorities = $_urlMatches = array();

		//echo "<pre>";
		//print_r(self::$libraries);
		//die();

		foreach (self::$libraries as $optionGroup => $options)
		{
			if (!DBSEO::$config['dbtech_dbseo_rewrite_' . strtolower($optionGroup)])
			{
				// We're not rewriting this at all
				//echo "<pre>$optionGroup</pre>";
				continue;
			}

			foreach ($options as $option => $optionInfo)
			{
				if ($optionInfo['subsetting'])
				{
					if (!DBSEO::$config['dbtech_dbseo_rewrite_' . strtolower($optionGroup) . $optionInfo['subsetting']])
					{
						// We're not rewriting this at all
						continue;
					}
				}

				if ($optionInfo['priority'] == -1)
				{
					// Skip this
					continue;
				}

				// Store this information in a lookup-friendly way
				$_formatsByLibrary[$optionGroup . '_' . $option] =& self::$libraries[$optionGroup][$option];

				// Store this information in a sort-friendly way
				$_formatPriorities[$optionGroup . '_' . $option] = $optionInfo['priority'];
			}
		}

		//echo "<pre>";
		//print_r($_formatPriorities);
		//die();

		// Sort by priority
		asort($_formatPriorities, SORT_NUMERIC);

		// All the URL checks go here
		foreach ($_formatPriorities as $format => $priority)
		{
			$_parsedUrl = DBSEO_Url_Check::check($format, $url, false, $_formatsByLibrary[$format]['strict']);
			if ($_parsedUrl !== NULL)
			{
				// Store this potential URL match
				$_urlMatches[] = array('format' => $format, 'info' => $_parsedUrl, 'history' => false);
			}
		}

		/*
		echo "<pre>";
		print_r($_urlMatches);
		die();
		*/

		//if (!count($_urlMatches))
		{
			// We had no matches, let's see what the history has for us

			// Store the URL history
			$urlHistory = array();

			$info = DBSEO::$db->generalQuery('
				SELECT *
				FROM $dbtech_dbseo_urlhistory
			', false);
			foreach ($info as $arr)
			{
				if (!isset($urlHistory[$arr['setting']]))
				{
					// Store an array of values for this setting
					$urlHistory[$arr['setting']] = array();
				}

				// Store this value
				$urlHistory[$arr['setting']][] = array('regexpformat' => $arr['regexpformat'], 'rawformat' => $arr['rawformat']);
			}

			// All the URL checks go here
			foreach ($_formatPriorities as $format => $priority)
			{
				if (!isset($urlHistory[$format]))
				{
					// This format had no history
					continue;
				}

				foreach ($urlHistory[$format] as $history)
				{
					$_parsedUrl = DBSEO_Url_Check::checkHistory($format, $history['regexpformat'], $history['rawformat'], $url, false, $_formatsByLibrary[$format]['strict']);
					if ($_parsedUrl !== NULL)
					{
						// Store this potential URL match
						$_urlMatches[] = array('format' => $format, 'info' => $_parsedUrl, 'history' => true);
					}
				}
			}
		}

		// No need for this anymore
		unset($_formatPriorities);

		// Default to fail
		$successfulUrl = false;

		// Default to no file
		$_fileName = '';

		//if (!$_SERVER['DBSEO_SUGGESTED_URI'])
		//{
		do
		{
			// Grab the first url match
			$urlMatch = array_shift($_urlMatches);

			// Resolve URL via the library
			$successfulUrl = DBSEO_Url::resolve($urlMatch['format'], $urlMatch['info'], $urlMatch['history']);
		}
		while (count($_urlMatches) AND !$successfulUrl);

		if ($successfulUrl)
		{
			if ($urlMatch['history'])
			{
				// History
				$_suggestedUrl = $urlMatch['_suggestedUrl'];
			}
			else
			{
				// Default
				$_suggestedUrl = isset(self::$suggestedUrls[$urlMatch['format']]) ? self::$suggestedUrls[$urlMatch['format']] : $_suggestedUrl;
			}
		}
		else if (count(self::$suggestedUrls))
		{
			// We need to super guess the URL
			$_suggestedUrl = array_shift(self::$suggestedUrls);
		}

		if ($_suggestedUrl)
		{
			// We need to redirect
			$successfulUrl = false;
		}
		else
		{
			if ($successfulUrl)
			{
				if ($urlMatch['history'])
				{
					// Set environment
					DBSEO::updateEnvironment($successfulUrl);

					// Reconstruct the URL and set it as the suggested URL
					$_suggestedUrl = DBSEO_Url_Create::create($urlMatch['format'], $_REQUEST);

					// Set the various query string variables
					$_SERVER['QUERY_STRING'] = $_ENV['QUERY_STRING'] = $GLOBALS['QUERY_STRING'] = $query;

					// Parse the query into neat little key->value pairs with automagic urldecode
					parse_str($_SERVER['QUERY_STRING'], $params);

					foreach ($params as $name => $value)
					{
						// Set this now instead
						unset($_REQUEST[$name], $_GET[$name]);
					}

					// We need to redirect
					$successfulUrl = false;
				}
				else
				{
					if (DBSEO::$config['dbtech_dbseo_persistenturl'])
					{
						if (!$info = DBSEO::$db->generalQuery('
							SELECT *
							FROM $dbtech_dbseo_resolvedurl
							WHERE seourl = \'' . DBSEO::$db->escape_string($url) . '\'
						'))
						{
							// Update our bot info (spider)
							DBSEO::$db->modifyQuery('
								INSERT INTO $dbtech_dbseo_resolvedurl
									(forumurl, seourl, urldata, format)
								VALUES (
									\'' . DBSEO::$db->escape_string($successfulUrl) . '\',
									\'' . DBSEO::$db->escape_string($url) . '\',
									\'' . DBSEO::$db->escape_string(trim(serialize($urlMatch['info']))) . '\',
									\'' . DBSEO::$db->escape_string($urlMatch['format']) . '\'
								)
							');
						}
					}

					// Set environment
					DBSEO::updateEnvironment($successfulUrl);

					// Set file name
					$_fileName = $_SERVER['DBSEO_FILE'];
				}
			}
		}
		//}

		// Return the proper values
		return array((bool)$successfulUrl, $_fileName, $_suggestedUrl);
	}


	/**
	 * Checks for and redirects to proper URLs if needed
	 *
	 * @param string $url
	 * @param boolean $fileExists
	 * @param boolean $fileExistsDeep
	 *
	 * @return mixed
	 */
	public static function redirect(&$url, &$fileExists, &$fileExistsDeep)
	{
		// Fetch the URL to our thread icon
		preg_match('#^(.+?)(_(?:ltr|rtl)?)(\.gif)$#', $url, $_treeIcon);
		$iconFile = isset($_treeIcon[1]) ? $_treeIcon[1] . $_treeIcon[3] : '';

		if (!$iconFile)
		{
			// Ensure this is set
			$iconFile = $url;
		}

		if (DBSEO::$config['dbtech_dbseo_rewrite_navbullet'])
		{
			$gifpos = strpos($url, '.gif');

			do
			{
				if ($gifpos === false)
				{
					// Not a gif
					break;
				}

				if (substr($url, 0, strlen(DBSEO::$config['dbtech_dbseo_navbullet_prefix'])) != DBSEO::$config['dbtech_dbseo_navbullet_prefix'])
				{
					// Not a nav bullet icon
					break;
				}

				// Shorthand
				$_url = substr($iconFile, strlen(DBSEO::$config['dbtech_dbseo_navbullet_prefix']), $gifpos + 4);

				if (
					!DBSEO_Url_Check::check('NavBullet_NavBullet_Thread', $_url, true) AND
					!DBSEO_Url_Check::check('NavBullet_NavBullet_Forum', $_url, true)
				)
				{
					// Didn't match thread or forum icon
					break;
				}

				// Construct the file path
				$_filePath = $fileExists ? $url : 'images/misc/navbits_finallink' . $_treeIcon[2] . '.gif';

				// Send some shiny headers
				header('Content-type: image/gif');
				header('Content-Length: ' . filesize($_filePath));

				// Display the image
				die(file_get_contents($_filePath));
			}
			while (false);
		}
		else if (
			strpos(DBSEO_HTTP_HOST, 'localhost') === false AND
			DBSEO::$config['dbtech_dbseo_www'] AND
			strpos(DBSEO_HTTP_HOST, 'www.') === false AND
			strpos(DBSEO::$config['bburl'], 'www.') !== false
		)
		{
			// Redirect to the www-included URL
			DBSEO::safeRedirect(DBSEO::$config['bburl'] . '/' . ($url == '/' ? '' : $url));
		}

		if (DBSEO_SPIDER)
		{
			// If we're a spider
			$nonClean = array('pp', 'highlight', 'order', 'sort', 'daysprune', 'referrerid');
			foreach ($nonClean as $var)
			{
				if (isset($_GET[$var]))
				{
					// We had one of the non-cleanable variables
					DBSEO::safeRedirect($_queryFile, $nonClean);
				}
			}
		}

		// By default, assume we haven't processed nothin'
		$_processed = false;

		do
		{
			if ($_POST OR !$fileExists)
			{
				// Ensure we don't overwrite $_fileName with anything
				break;
			}

			/*
			if (DBSEO_RELPATH OR $fileExistsDeep)
			{
				// Either a relative URL or an actual file
				break;
			}
			*/

			/*DBTECH_PRO_START*/
			if (DBSEO_SPIDER)
			{
				// Track spider hit
				DBSEO::trackSpider(DBSEO_BASEURL);
			}
			/*DBTECH_PRO_END*/

			// Even if we fail the rewrite checks or whatever, still count as processed
			$_processed = true;

			// Detect file name
			$_strippedFileName = preg_replace('/[^\w\.-]/i', '', DBSEO_BASEURL);

			// Detect script alias if any exists
			$_strippedFileName = pathinfo(isset(DBSEO::$cache['_scriptAlias'][$_strippedFileName]) ? DBSEO::$cache['_scriptAlias'][$_strippedFileName] : $_strippedFileName, PATHINFO_FILENAME);

			if (
				!$_strippedFileName OR
				$_strippedFileName == '.' OR
				$_strippedFileName == '..'
			)
			{
				// Get out of this area
				break;
			}

			$class = 'DBSEO_Script_' . ucfirst($_strippedFileName);
			if (!class_exists($class))
			{
				// Compatibility layer
				if (!file_exists(DBSEO_CWD . '/dbtech/dbseo/includes/scripts/' . $_strippedFileName . '.php'))
				{
					// Git oot.
					break;
				}

				// This file holds all subclasses as well
				require_once(DBSEO_CWD . '/dbtech/dbseo/includes/scripts/' . $_strippedFileName . '.php');

				if (!class_exists($class))
				{
					// Git oot.
					break;
				}
			}

			if (!method_exists($class, 'redirectUrl'))
			{
				// Git oot.
				break;
			}

			$result = call_user_func_array(array($class, 'redirectUrl'), array(&$url, &$fileExists, &$fileExistsDeep));
			if (is_bool($result) === false)
			{
				// We had a different result
				return $result;
			}
		}
		while (false);

		if (!$_processed AND !$fileExists)
		{
			// Check custom URL
			if ($newUrl = DBSEO_Url_Create::create('Custom_CustomRewrite', $url))
			{
				// Get rid of this
				$newUrl = str_replace('[NF]', '', $newUrl);

				if (DBSEO_REQURL != $newUrl AND strpos($newUrl, $url) === false)
				{
					// The URL was different
					if (DBSEO_RELPATH AND strpos($newUrl, DBSEO_RELPATH) === false)
					{
						// Add the relpath to the URL
						$newUrl = DBSEO_RELPATH . $newUrl;
					}

					// Redirect to the new URL
					DBSEO::safeRedirect($newUrl, array(), true);
				}
			}
		}

		// Ensure we don't overwrite $_fileName with anything
		return '';
	}


	/**
	 * Automatically adjusts a URL based on parameters
	 *
	 * @param string $url
	 *
	 * @return mixed
	 */
	public static function adjust($_requestUrl, $excludedParams = array(), $forceRedirect = false)
	{
		// Initialise some important variables
		$decodedUrl = urldecode($_requestUrl);
		$doubleDecode = urldecode($decodedUrl);
		$_requestUrl2 = preg_replace('#\?.*$#', '', DBSEO_REQURL);

		if (
			(
				$_requestUrl != $_requestUrl2 AND
				!isset($_GET['vbseodirect']) AND  # COMPATIBILITY
				!isset($_GET['dbseodirect']) AND
				(
					(strpos(DBSEO_URL_CLEAN, DBSEO_URL_BASE_PATH) !== false) OR
					/*DBTECH_PRO_START*/
					(DBSEO::$config['dbtech_dbseo_custom_blog'] 	AND strpos(DBSEO::$config['dbtech_dbseo_custom_blog'], 	DBSEO_HTTP_HOST) !== false) OR
					(DBSEO::$config['dbtech_dbseo_custom_cms'] 		AND strpos(DBSEO::$config['dbtech_dbseo_custom_cms'], 	DBSEO_HTTP_HOST) !== false) OR
					(DBSEO::$config['dbtech_dbseo_custom_forum'] 	AND strpos(DBSEO::$config['dbtech_dbseo_custom_forum'], DBSEO_HTTP_HOST) !== false) OR
					/*DBTECH_PRO_END*/
					(strpos(DBSEO::$config['dbtech_dbseo_rewrite_rule_blogs'], 		'://') !== false AND strpos(DBSEO_URL_CLEAN, DBSEO::$config['dbtech_dbseo_rewrite_rule_blogs']) 	!== false) OR
					(strpos(DBSEO::$config['dbtech_dbseo_rewrite_rule_cmshome'], 	'://') !== false AND strpos(DBSEO_URL_CLEAN, DBSEO::$config['dbtech_dbseo_rewrite_rule_cmshome']) 	!== false)
				) AND
				($doubleDecode != $_requestUrl2) AND
				($doubleDecode != urldecode(DBSEO_REQURL)) AND
				($_requestUrl != DBSEO_URL_BASE_PATH . $_requestUrl2) AND (
					($_requestUrl == $decodedUrl) OR
					($_requestUrl != substr($decodedUrl, 0, strlen($_requestUrl)))
				)
			) OR $forceRedirect
		)
		{
			// URL needed adjustment
			DBSEO::safeRedirect($_requestUrl, $excludedParams);
		}
	}

	/**
	 * Creates a SEO'd URL based on the specified library.
	 *
	 * @param string $library
	 * @param array $data
	 *
	 * @return mixed
	 */
	public static function resolve($library, $data = array(), $history = false)
	{
		if (!$library)
		{
			// Sadly we couldn't handle this
			return false;
		}

		// Sort out this addition
		$libraryParts = explode('_', $library, 2);

		// Strip non-valid characters
		$libraryParts[0] = strtolower(preg_replace('/[^\w-]/i', '', $libraryParts[0]));

		if ($libraryParts[1] != 'CustomRedirect' AND !isset(DBSEO::$cache['preparedurls'][$libraryParts[0]]))
		{
			// Git oot.
			return false;
		}

		$class = 'DBSEO_Rewrite_' . $libraryParts[1];
		if (!class_exists($class))
		{
			// Compatibility layer
			if (!file_exists(DBSEO_CWD . '/dbtech/dbseo/includes/rewrite/' . $libraryParts[0] . '.php'))
			{
				// Git oot.
				return false;
			}

			// This file holds all subclasses as well
			require_once(DBSEO_CWD . '/dbtech/dbseo/includes/rewrite/' . $libraryParts[0] . '.php');

			if (!class_exists($class))
			{
				// Git oot.
				return false;
			}
		}

		if (!method_exists($class, 'resolveUrl'))
		{
			// Git oot.
			return false;
		}

		return call_user_func(array($class, 'resolveUrl'), $data);
	}

	/**
	 * Parse custom rewrite rules
	 */
	public static function parseCustomRewrites($_originalUrl, &$nofollow)
	{
		$newUrl = '';

		if (strpos($_originalUrl, '#') !== false)
		{
			// We have an anchor
			list($url, $anchor) = explode('#', $_originalUrl);
		}
		else
		{
			$url = $_originalUrl;
			$anchor = '';
		}

		// Create our custom URL with the anchor suffixed
		if (!$_newUrl = DBSEO_Url_Create::create('Custom_CustomRewrite', $url))
		{
			// Return blank
			return $newUrl;
		}

		// Append anchor if we had it
		$_newUrl .= ($anchor ? '#' . $anchor : '');

		if ($_originalUrl == $_newUrl)
		{
			// Return blank
			return $newUrl;
		}

		// The URL was different
		$newUrl = $_newUrl;

		if (strpos($newUrl, '[NF]') !== false)
		{
			// We had noFollow tag
			$newUrl = str_replace('[NF]', '', $newUrl);

			// Ensure we append rel="nofollow"
			$nofollow = true;
		}

		return $newUrl;
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
	 * @return mixed
	 */
	public static function replace($urlPrefix, $url, $urlAttributes = '', $urlSuffix = '', $inTag = '', $closeTag = '')
	{
		global $session, $vbulletin;

		if (
			!$url
			OR strpos($url, 'cron.php') !== false
			OR strpos($url, '.js') !== false
		)
		{
			// This didn't really have anything to rewrite
			return $urlPrefix . $url . $urlSuffix . $inTag . $closeTag;
		}

		if ($url[0] == '/' AND substr($url, 0, 2) != '//')
		{
			// Update the URL to use the Home URL setting instead
			$url = DBSEO::$config['homeurl'] . $url;
		}

		//vbstop($urlPrefix . $url, false, true);

		if (strpos($url, 'javascript:') !== false OR preg_match('#\<(script|base|form)+#i', $urlPrefix))
		{
			if ($url AND strpos($url, 'javascript:') === false AND strpos($url, '://') === false AND substr($url, 0, 2) !== '//')
			{
				$url = DBSEO::$config['_bburl'] . '/' . $url;
			}
			return $urlPrefix . $url . $urlSuffix . $inTag . $closeTag;
		}

		if (strpos($urlPrefix, 'meta') !== false AND strpos($urlPrefix, 'og:url') === false)
		{
			return $urlPrefix . $url . $urlSuffix . $inTag . $closeTag;
		}

		if (
			strpos($urlPrefix, 'img') !== false
			AND strpos($urlPrefix, 'alt') === false
			AND strpos($urlSuffix, 'alt') === false
			AND defined('THIS_SCRIPT')
			AND THIS_SCRIPT == 'showthread'
		)
		{
			$urlPrefix = preg_replace('#(<img\s)#is', '\\1alt="' . htmlspecialchars($GLOBALS['threadinfo']['title']) . '" ', $urlPrefix);
		}

		if ($url AND $url != 'javascript:' AND strpos($url, '://') === false AND substr($url, 0, 2) !== '//' AND preg_match('#\<(link)+#i', $urlPrefix))
		{
			$url = DBSEO::$config['_bburl'] . '/' . $url;
		}

		foreach (array('urlPrefix', 'urlSuffix', 'inTag', 'closeTag') as $var)
		{
			// Clean the variable
			$$var = str_replace('\"', '"', $$var);
		}

		$parseString = '';
		if (substr($urlPrefix, -1) == '"' AND (($quotePos = strpos($urlSuffix, '"')) > 0) AND substr($urlSuffix, 0, 1) != "'")
		{
			// Clean up the URL and the suffix
			$url .= substr($urlSuffix, 0, $quotePos);
			$urlSuffix = substr($urlSuffix, $quotePos);
		}

		if (
			(
				substr($urlPrefix, -1) != substr($urlSuffix, 0, 1) AND
				$urlSuffix AND
				!DBSEO::$config['dbtech_dbseo_rewrite_texturls'] AND
				!DBSEO::$config['_rewritePrintThread'] AND
				!DBSEO::$config['_isXML']
			) OR
			strpos($url, 'data:') !== false
		)
		{
			// We're not rewriting this URL
			return $urlPrefix . $url . $urlSuffix . $inTag . $closeTag;
		}

		//vbstop($url, false, false);
		//echo "<pre>$url</pre>";

		if (
			strpos($urlPrefix,'rel="novbseo"') !== false OR   # Compatibility
			strpos($urlPrefix,'rel=\'novbseo\'') !== false OR # Compatibility
			strpos($urlPrefix,'rel="nodbseo"') !== false OR
			strpos($urlPrefix,'rel=\'nodbseo\'') !== false
		)
		{
			// Return the URL as-is
			return preg_replace('#rel=[\'"]no(v|d)bseo[\'"]#', '', $urlPrefix) . $url . $urlSuffix . $inTag . $closeTag;
		}

		if (substr($url, 0, 1) == '#')
		{
			// Simple marker
			return $urlPrefix . ((DBSEO_BASEDEPTH AND DBSEO::$config['_preprocessed']) ? htmlspecialchars(DBSEO_URL_CLEAN) : '' ) . $url . $urlSuffix . $inTag . $closeTag;
		}

		// Store this
		$_uniqId = md5($urlPrefix . $url . $urlSuffix . $inTag . $closeTag);

		//vbstop($urlPrefix . $url . $urlSuffix . $inTag . $closeTag, false, true);

		if (DBSEO::$config['dbtech_dbseo_linktitles'] AND substr($url, 0, 1) == '!')
		{
			// Detect the markers
			preg_match('#^\!([mpg])?(\d+)#', $url, $matches);

			// Get rid of the marker
			$url = preg_replace('#^\![mpg]?\d+\!#', '', $url);

			if (($info = DBSEO::$datastore->fetch('threadtitle.' . $_uniqId)) === false)
			{
				if (strpos($url, 'showthread.php') === false)
				{
					switch ($matches[1])
					{
						case 'm':
							// Shorthand some information
							$threadId = $matches[2];
							$threadTitle = DBSEO::$cache['thread'][$threadId]['title'];

							// Paged Thread URL
							$threadUrl = DBSEO_Url_Create::create('Thread_Thread_Page', array('threadid' => $threadId, 'page' => '#m#'));
							if (!preg_match('#' . str_replace('\\#m\\#', '\d+', preg_quote($threadUrl,'#')) . '#', $url))
							{
								// We failed :(
								$threadTitle = '';
							}
							break;

						case 'p':
							if (!isset(DBSEO::$cache['post'][$matches[2]]))
							{
								// We definitely need this now
								DBSEO::$cache['_objectIds']['prepostthread_ids'][] = $matches[2];

								// Ensure we have this
								DBSEO::getThreadPostInfo($matches[2]);
							}

							// Shorthand some information
							$threadId = DBSEO::$cache['post'][$matches[2]]['threadid'];
							$threadTitle = DBSEO::$cache['thread'][$threadId]['title'];

							// Post URL
							$threadUrl = DBSEO_Url_Create::create('ShowPost_ShowPost', array('threadid' => $threadId, 'postid' => $matches[2]));
							if (!$threadUrl OR strpos($url, $threadUrl) === false)
							{
								// We failed :(
								$threadTitle = '';
							}
							break;

						case 'g':
							if (!isset(DBSEO::$cache['post'][$matches[2]]))
							{
								// We definitely need this now
								DBSEO::$cache['_objectIds']['prepostthread_ids'][] = $matches[2];

								// Ensure we have this
								DBSEO::getThreadPostInfo($matches[2]);
							}

							// Shorthand some information
							$threadId = DBSEO::$cache['post'][$matches[2]]['threadid'];
							$threadTitle = DBSEO::$cache['thread'][$threadId]['title'];

							$threadUrl = DBSEO_Url_Create::create('Thread_Thread_GoToPost', array('threadid' => $threadId, 'postid' => $matches[2]));
							if (!$threadUrl OR strpos($url, $threadUrl) === false)
							{
								// We failed :(
								$threadTitle = '';
							}
							break;

						case '':
							// Shorthand some information
							$threadId = $matches[2];
							$threadTitle = DBSEO::$cache['thread'][$threadId]['title'];


							$threadUrl = DBSEO_Url_Create::create('Thread_Thread', array('threadid' => $threadId));
							if (!$threadUrl OR ($matches[1] != 'g' AND strpos($url, $threadUrl) === false))
							{
								// We failed :(
								$threadTitle = '';
							}
							break;
					}
				}

				if ($threadTitle AND $inTag != $threadTitle)
				{
					switch (DBSEO::$config['dbtech_dbseo_linktitles'])
					{
						case 1:
							// Title attribute
							$urlPrefix = preg_replace('#(<a\s)#is', '\\1title="' . htmlspecialchars($threadTitle) . '" ', $urlPrefix);
							break;

						case 2:
							// Append
							$inTag = $inTag . " ($threadTitle)";
							break;

						case 3:
							if (preg_match('#^https?:#', $inTag))
							{
								// Overwrite
								$inTag = $threadTitle;
							}
							break;
					}
				}

				$info = array(
					'urlPrefix' => $urlPrefix,
					'inTag' 	=> $inTag
				);

				// Build the cache
				DBSEO::$datastore->build('threadtitle.' . $_uniqId, $info);
			}

			foreach ($info as $var => $val)
			{
				// Set this
				$$var = $val;
			}
		}

		// Set the quote character
		$quoteChar = substr($urlPrefix, -1);
		$quoteChar = $quoteChar == '"' ? '"' : "'";

		// Prepare the "nofollow" attribute
		$_noFollow = 'rel=' . $quoteChar . 'nofollow' . $quoteChar;

		if (
			substr($url, 0, 7) == 'mailto:' OR
			substr($url, 0, 11) == 'javascript:' OR
			(
				($cproto = 1) AND
				strpos($url, '://') !== false AND
				strpos($url, DBSEO_HTTP_HOST) === false AND
				strpos($url, DBSEO::$config['_bburl']) === false
			)
		)
		{
			// Match the host name and check whether we're an external url
			preg_match('#(?:www\.)?(.+)$#', DBSEO_HTTP_HOST, $matches);
			$_isExternal = !preg_match('#^[^/]*://(www\.)?' . preg_quote($matches[1], '#') . '#', $url);

			if ($_isExternal)
			{
				$url_parts = @parse_url($url);
				if (is_array($url_parts))
				{
					$url_host = strtolower(str_replace('www.', '', $url_parts['host']));

					do
					{
						if (!DBSEO::$config['dbtech_dbseo_externalurls'])
						{
							// External URLs are disabled
							break;
						}

						if (strpos($urlPrefix . $urlAttributes . $urlSuffix, 'rel=') !== false)
						{
							// A rel attr already existed
							break;
						}

						if (
							DBSEO::$config['dbtech_dbseo_externalurls_whitelist']
							AND in_array($url_host, DBSEO::$config['dbtech_dbseo_externalurls_whitelist'])
						)
						{
							// Whitelisted
							break;
						}

						if (
							(
								DBSEO::$config['dbtech_dbseo_externalurls_blacklist']
								AND in_array($url_host, DBSEO::$config['dbtech_dbseo_externalurls_blacklist'])
							)
							OR !$GLOBALS['foruminfo']
							OR !in_array($GLOBALS['foruminfo']['forumid'], DBSEO::$config['dbtech_dbseo_externalurls_forumexclude'])
						)
						{
							// We didn't have a rel tag already, add one
							$urlPrefix = preg_replace('#(<a\s)#is', '\\1' . $_noFollow . ' ', $urlPrefix);
						}
					}
					while (false);

					// Add external link tracking
					DBSEO::trackExternalLink($urlPrefix, $url, $urlSuffix, (substr($inTag, 0, 5) == 'Visit' ? 'onmouseup' : ''));
				}
			}

			if (
				DBSEO::$config['dbtech_dbseo_externalurls_anonymise'] AND
				(
					strpos($url, 'http://') !== false OR
					strpos($url, 'https://') !== false
				) AND
				in_array(THIS_SCRIPT, array('showthread', 'printthread', 'showpost', 'forumdisplay', 'newreply')) AND
				strpos($urlPrefix,'<a') !== false AND
				strpos($urlPrefix, 'href') !== false AND
				$_isExternal
			)
			{
				// Ensure external URLs have redirects
				$url = DBSEO::$config['_bburl'] . '/redirect-to/?redirect=' . urlencode(html_entity_decode($url, ENT_QUOTES | ENT_HTML401));
			}

			// We don't need to do anything else to this URL
			return $urlPrefix . $url . $urlSuffix . $inTag . $closeTag;
		}

		// Init some important variables
		$url = preg_replace('#([^:]/)/+#', '$1', $url);
		$_urlPlace = $_urlAppend = $_urlParameters = $_cmsUrlAppend = '';

		if (strpos($url, '?') !== false)
		{
			// We have a query parameter
			list($_urlScript, $_urlAppend) = explode('?', $url, 2);
		}
		else
		{
			// Just go with the flat URL
			$_urlScript = $url;
		}

		if ($_urlAppend AND substr($_urlAppend, 0, 1) == '?')
		{
			// We gotta remove the question mark
			$_urlAppend = substr($_urlAppend, 1);
		}

		if (strpos($_urlScript, '#') !== false AND preg_match('#^(.+[^\&])\#(.*)$#', $_urlScript, $matches))
		{
			// The URL script had an anchor
			$_urlScript = $matches[1];
			$_urlPlace = $matches[2];
		}
		else if (strpos($_urlAppend, '#') !== false AND preg_match('#^(.+[^\&])\#(.*)$#', $_urlAppend, $matches))
		{
			// URL append had an anchor
			$_urlParameters = $matches[1];
			$_urlPlace = $matches[2];
		}
		else
		{
			// Just set the parameters
			$_urlParameters = $_urlAppend;
		}

		if (preg_match('#^([^/]*?\.php)/(.+)$#', $_urlScript, $matches))
		{
			$_cmsUrlAppend = $matches[2];
			$_urlScript  = $matches[1];
		}

		// Match base and directory
		preg_match('#^(.*?)([^/]*)$#', $_urlScript, $matches);
		$_baseScript = $matches[2];
		$_dirScript = $matches[1];

		// Detect whether we're in a vB directory
		$_isvBDir = (
			(
				!$_dirScript AND (
					!DBSEO_BASEDEPTH OR
					DBSEO::$config['_inAjax'] OR
					DBSEO::$config['_baseHref'] OR
					DBSEO::$config['_preprocessed']
				)
			) OR
			strcasecmp($_dirScript, DBSEO_URL_SCRIPT_PATH) == 0 OR
			strcasecmp($_dirScript, DBSEO_URL_BASE_PATH) == 0 OR
			strcasecmp($_dirScript, DBSEO::$config['_bburl'] . '/') == 0 OR
			strcasecmp(str_replace('www.', '', $_dirScript), DBSEO_URL_BASE_PATH) == 0
		);

		// Detect whether this is a vB URL
		$_isvBUrl = strpos($_urlScript, DBSEO::$config['_bburl']) !== false;

		// Shorthand
		$_topUrl = DBSEO::$config['_bburl'] . '/';

		if ($_urlParameters == '&amp;')
		{
			// We don't want urlencoded url parameters
			$_urlParameters = '';
		}

		// Ensure we don't have any encoded parameters
		$_preparedUrlParameters = str_replace('&amp;', '&', $_urlParameters) . '&';

		// Init some important variables
		$_seoParameters = $_stringParameters = array();
		$_position1 = $i = 0;
		$_position2 = -1;

		/*
		while (
			($_position2 + 1) > strlen($_preparedUrlParameters) AND
			($_position2 = strpos($_preparedUrlParameters, '&', ($_position2 + 1)) !== false) AND
			$i++ < 20
		)
		*/

		while (
			(
				($_position2 = strpos($_preparedUrlParameters, '&', $_position2 + 1)) !== false
			) AND
			(
				$i++ < 20
			)
		)
		{
			// Fetch this parameter alone
			$_subParameter = substr($_preparedUrlParameters, $_position1, $_position2 - $_position1);

			if (substr($_preparedUrlParameters, $_position2, 1) == '#')
			{
				// We don't need anything in the anchor
				continue;
			}

			// Continue from where we left off
			$_position1 = $_position2 + 1;

			$value = '';
			if (strpos($_subParameter, '=') !== false)
			{
				// Grab the key/value pair
				list($key, $value) = explode('=', $_subParameter, 2);
			}
			else
			{
				// Singular parameter
				$key = $_subParameter;
			}

			$key = trim($key);
			if ($key)
			{
				// Grab the decoded value
				$decodedValue = urldecode($value);

				if (strpos($decodedValue, 'http:') !== false AND substr($decodedValue, 0, strlen(DBSEO::$config['_bburl'])) == DBSEO::$config['_bburl'])
				{
					// Replace URLs in the value
					$decodedValue = DBSEO_Url::replace('', $decodedValue);

					// Re-encode this
					$value = urlencode($decodedValue);
				}

				// Parameters to pass to the URL generation function
				$_seoParameters[$key] = $value;
				$_stringParameters[] = array($key, $value);
			}
		}

		/*
		if ($_dirScript == DBSEO::$config['avatarurl'] . '/')
		{
			// Grab our info for custom avatars
			preg_match('#avatar(\d+)_(\d+).gif#', $_baseScript, $avatarUser);

			// Set parameters
			$_seoParameters['u'] = $avatarUser[1];
			//$_seoParameters['type'] = 'profile';
			$_stringParameters[] = array('u', $avatarUser[1]);
			//$_stringParameters[] = array('type', 'profile');

			// Override some stuff
			$_isvBDir = true;
			$_baseScript = 'image.php';
			$_dirScript = '';
		}
		*/

		if (THIS_SCRIPT == 'online')
		{
			if (strpos($urlPrefix, 'alt=') !== false)
			{
				// Replace alt attribute
				$urlPrefix = preg_replace_callback(
					'#(alt=")([^"]+)#is',
					array('DBSEO', 'replaceTextUrls'),
					$urlPrefix
				);
			}

			if (strpos($urlSuffix, 'alt=') !== false)
			{
				// Replace alt attribute
				$urlSuffix = preg_replace_callback(
					'#(alt=")([^"]+)#is',
					array('DBSEO', 'replaceTextUrls'),
					$urlSuffix
				);
			}
		}

		if (!isset($session) AND isset($vbulletin->session))
		{
			$session = $vbulletin->session->vars;
		}

		$_appendSession = '';
		if (isset($_seoParameters['s']))
		{
			if (!(
				$vbulletin->userinfo['userid'] AND (
					(
						isset($session) AND
						in_array($_seoParameters['s'], $session) AND
						DBSEO::$config['_stripsessionhash']
					) OR
					isset(DBSEO::$config['dbtech_dbseo_rewrite_texturls'])
				)
			))
			{
				// We want to put the session back
				$_appendSession = 's=' . $_seoParameters['s'];
			}

			// Ensure we don't include this in the parameters
			unset($_seoParameters['s']);

			// Also replace them from this string
			$_urlParameters = preg_replace('#^s=[\da-z]+(&amp;|&)*#', '', $_urlParameters);
			$_urlParameters = preg_replace('#(&amp;|&)s=[\da-z]+#', '', $_urlParameters);

			if (count($_stringParameters) == 1 AND $_stringParameters[0][0] == 's')
			{
				// Reset this array, since we're stripping session
				$_stringParameters = array();
			}
		}

		$_removeAllParameters = false;

		if (count($_seoParameters) == 1 AND preg_match('#^[ft]-#', $_urlParameters))
		{
			// We had only one parameter, and it was forumid or threadid
			$parseString = $_urlAppend;
			$_topUrl = '';
			$_removeAllParameters = true;
		}

		$nofollow = $follow = $_preventProcessing = false;
		if (isset($_seoParameters['threadid']))
		{
			// Ensure we set this shorthand
			$_seoParameters['t'] = $_seoParameters['threadid'];
		}

		if (!$_removeAllParameters AND $_isvBDir)
		{
			do
			{
				if ($_baseScript == DBSEO::$config['homePage'] AND !$_urlParameters AND DBSEO::$config['dbtech_dbseo_force_directory_index'])
				{
					$_urlScript = ((isset(DBSEO::$config['dbtech_dbseo_rewrite_texturls']) OR THIS_SCRIPT == 'sendmessage2') ? '' : $_topUrl) . DBSEO::$config['_homepage'];
					$_preventProcessing = true;
					break;
				}

				if (($info = DBSEO::$datastore->fetch('urlrewrite.' . $_uniqId)) === false)
				{
					// Detect file name
					$_strippedFileName = preg_replace('/[^\w\.-]/i', '', $_baseScript);

					// Detect script alias if any exists
					$_strippedFileName = pathinfo(isset(DBSEO::$cache['_scriptAlias'][$_strippedFileName]) ? DBSEO::$cache['_scriptAlias'][$_strippedFileName] : $_strippedFileName, PATHINFO_FILENAME);

					if (
						!$_strippedFileName OR
						$_strippedFileName == '.' OR
						$_strippedFileName == '..'
					)
					{
						// Git oot.
						$_preventProcessing = true;
						if (isset($_seoParameters['do']) AND $_seoParameters['do'] == 'getdaily')
						{
							// We're in the getdaily script
							$follow = true;
						}
						break;
					}

					$class = 'DBSEO_Script_' . ucfirst($_strippedFileName);
					if (!class_exists($class))
					{
						// Compatibility layer
						if (!file_exists(DBSEO_CWD . '/dbtech/dbseo/includes/scripts/' . $_strippedFileName . '.php'))
						{
							// Git oot.
							$_preventProcessing = true;
							if (isset($_seoParameters['do']) AND $_seoParameters['do'] == 'getdaily')
							{
								// We're in the getdaily script
								$follow = true;
							}
							break;
						}

						// This file holds all subclasses as well
						require_once(DBSEO_CWD . '/dbtech/dbseo/includes/scripts/' . $_strippedFileName . '.php');

						if (!class_exists($class))
						{
							// Git oot.
							$_preventProcessing = true;
							if (isset($_seoParameters['do']) AND $_seoParameters['do'] == 'getdaily')
							{
								// We're in the getdaily script
								$follow = true;
							}
							break;
						}
					}

					if (!method_exists($class, 'replaceUrls'))
					{
						// Git oot.
						$_preventProcessing = true;
						if (isset($_seoParameters['do']) AND $_seoParameters['do'] == 'getdaily')
						{
							// We're in the getdaily script
							$follow = true;
						}
						break;
					}

					$newUrl = call_user_func_array(array($class, 'replaceUrls'), array(
						&$_preventProcessing,
						&$_seoParameters,
						&$urlPrefix,
						&$url,
						&$urlSuffix,
						&$inTag,
						&$_urlScript,
						&$_urlPlace,
						&$_urlParameters,
						&$_removeAllParameters,
						&$_cmsUrlAppend,
						&$nofollow,
						&$follow
					));
					if ($newUrl == '-')
					{
						// We had a different result
						return $newUrl;
					}
					else if ($newUrl === false)
					{
						// We returned boolean false
						break;
					}

					$info = array(
						'_preventProcessing' 	=> $_preventProcessing,
						'_seoParameters' 		=> $_seoParameters,
						'urlPrefix' 			=> $urlPrefix,
						'url' 					=> $url,
						'urlSuffix' 			=> $urlSuffix,
						'inTag' 				=> $inTag,
						'_urlScript' 			=> $_urlScript,
						'_urlPlace' 			=> $_urlPlace,
						'_urlParameters' 		=> $_urlParameters,
						'_removeAllParameters' 	=> $_removeAllParameters,
						'_cmsUrlAppend' 		=> $_cmsUrlAppend,
						'nofollow' 				=> $nofollow,
						'follow' 				=> $follow
					);

					//vbstop($info, false, false);

					// Build the cache
					DBSEO::$datastore->build('urlrewrite.' . $_uniqId, $info);
				}

				foreach ($info as $var => $val)
				{
					// Set this
					$$var = $val;
				}
			}
			while (false);
		}
		else
		{
			$_preventProcessing = true;
		}

		if ($_preventProcessing)
		{
			// Duplicate this to ensure we don't overwrite the original URL
			$customUrl = $_urlScript;

			if ($_isvBDir)
			{
				// Set the custom URL if we're in vB
				$customUrl = (!$_baseScript OR strpos($_urlScript, $_baseScript) !== false) ? $_baseScript : preg_replace('#^(.*?)([^/]*)$#', '$2', $_urlScript);
			}

			//echo "<pre>$customUrl</pre>";

			if ($newUrl = DBSEO_Url::parseCustomRewrites($customUrl . ($_urlParameters ? '?' . $_urlParameters : ''), $nofollow))
			{
				// We had a new URL
				$_urlScript = $newUrl;
				$_removeAllParameters = true;

				if ($_isvBDir)
				{
					// Don't block any further processing
					$_preventProcessing = false;
				}
			}
		}

		if (
			(
				$_preventProcessing AND
				!$_isvBDir AND
				(
					stripos(DBSEO_BASE, DBSEO_URL_SCRIPT_PATH) === false OR substr($url, 0, 1) == '/' OR !(
						DBSEO_BASEDEPTH AND
						DBSEO::$config['_preprocessed']
					)
				)
			) OR
			(
				isset(DBSEO::$config['dbtech_dbseo_rewrite_texturls']) OR THIS_SCRIPT == 'sendmessage'
			) OR
			(
				!DBSEO::$config['_preprocessed'] AND
				!$_isvBUrl AND
				!DBSEO_BASEDEPTH AND
				THIS_SCRIPT != 'index'
			)
		)
		{
			// Reset top URL
			$_topUrl = '';
		}

		// Shorthand
		$ampersand = (isset(DBSEO::$config['dbtech_dbseo_rewrite_texturls']) AND !isset(DBSEO::$config['dbtech_dbseo_rewrite_external'])) ? '&' : '&amp;';

		if (!$_removeAllParameters)
		{
			if (
				($_urlParameters AND substr($_urlParameters, 0, 1) == '=') OR
				strpos($_urlParameters, '=') === false
			)
			{
				// Suffix the URL parameters
				$parseString .= $_urlParameters;
			}
			else if (
				(strpos($_urlParameters, '=') === false OR substr($_urlParameters, 0, 1) == '=') AND
				count($_stringParameters) == 1
			)
			{
				// We had only one string param
				$parseString .= $_stringParameters[0][0];
			}
			else
			{
				for ($i = 0; $i < count($_stringParameters); $i++)
				{
					if (isset($_seoParameters[$_stringParameters[$i][0]]))
					{
						// We had this parameter, add it
						$parseString .= ($parseString ? $ampersand : '') . $_stringParameters[$i][0] . '=' . $_stringParameters[$i][1];
					}
				}
			}
		}
		else
		{
			// We're getting rid of all parameters
			unset($_seoParameters);
		}

		if ($_appendSession)
		{
			// We need to append our session
			$parseString .= ($parseString ? $ampersand : '') . $_appendSession;
		}

		if ($_urlScript == '/')
		{
			// Overwrite the URL Script
			$_urlScript = DBSEO::$config['_bburl'] .  $_urlScript;
		}
		else if (
			substr($_urlScript, 0, 1) != '/' AND
			strpos(substr($_urlScript, 3, 5), ':') === false
		)
		{
			// Overwrite the URL Script
			$_urlScript = ($urlPrefix ? $_topUrl : DBSEO::$config['_bburl'] . '/') .  $_urlScript;
		}

		/*
		if ($inTag == 'Product Info / Purchase')
		{
			echo $_urlScript;
			echo "\n";
			vbstop($info, true, false);
		}
		*/

		// Set the new URL
		$newUrl = $_urlScript . ($parseString ? '?' . $parseString : '') . (($_urlPlace AND strpos($_urlScript, '#') === false) ? '#' . $_urlPlace : '');

		if ($follow)
		{
			// Get rid of noFollow from the prefix and the suffix
			$urlSuffix = str_replace($_noFollow, '', $urlSuffix);
			$urlPrefix = str_replace($_noFollow, '', $urlPrefix);
		}
		else if (
			($nofollow OR isset($_seoParameters['sort']) OR $_seoParameters) AND
			strpos($urlPrefix . $urlAttributes . $urlSuffix, 'rel=') === false
		)
		{
			// Add the rel if needed
			$urlPrefix = preg_replace('#(<a\s)#is', '\\1' . $_noFollow . ' ', $urlPrefix);
		}

		if (DBSEO::$config['dbtech_dbseo_rewrite_texturls'] AND strpos($newUrl, 'http://') !== false AND strpos($urlPrefix, 'http://') !== false)
		{
			// Unset the prefix
			$urlPrefix = '';
		}

		if ($inTag AND $url == $inTag)
		{
			/// Set the final URL component
			$inTag = $newUrl;
		}

		// Reconstruct the URL!
		return $urlPrefix . $newUrl . $urlSuffix . $inTag . $closeTag;
	}
}
?>