<?php if(!defined('IN_DBSEO')) die('Access denied.');

/**
 * DBSEO_Url_Create
 *
 * @package DBSEO
 * @access public
 */
class DBSEO_Url_Create extends DBSEO_Url
{
	/**
	 * Creates a SEO'd URL based on the specified library.
	 *
	 * @param string $library
	 * @param array $data
	 *
	 * @return mixed
	 */
	public static function create($library, $data = array())
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

		if (!isset(DBSEO::$cache['preparedurls'][$libraryParts[0]]))
		{
			// Git oot.
			return false;
		}

		$class = 'DBSEO_Rewrite_' . $libraryParts[1];
		if (!class_exists($class))
		{
			// Compatibility layer
			if (!file_exists(DBSEO_CWD . '/dbtech/dbseo/includes/library/' . $libraryParts[0] . '.php'))
			{
				// Git oot.
				return false;
			}

			// This file holds all subclasses as well
			require_once(DBSEO_CWD . '/dbtech/dbseo/includes/library/' . $libraryParts[0] . '.php');

			if (!class_exists($class))
			{
				// Git oot.
				return false;
			}
		}

		if (!method_exists($class, 'createUrl'))
		{
			// Git oot.
			return false;
		}

		return call_user_func(array($class, 'createUrl'), $data);
	}

	/**
	 * Creates a SEO'd URL based on the current environment.
	 *
	 * @return mixed
	 */
	public static function createAny()
	{
		if (!$_SERVER['DBSEO_FILE'])
		{
			// Git oot.
			return false;
		}

		// Dummy var
		$nofollow = false;
		if ($newUrl = DBSEO_Url_Create::parseCustomRewrites($_SERVER['DBSEO_FILE'] . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''), $nofollow))
		{
			// We had a custom rewrite rule that matched
			return $newUrl;
		}

		// Shorthand
		$_strippedFileName = pathinfo($_SERVER['DBSEO_FILE'], PATHINFO_FILENAME);

		$class = 'DBSEO_Script_' . ucfirst($_strippedFileName);
		if (!class_exists($class))
		{
			// Compatibility layer
			if (!file_exists(DBSEO_CWD . '/dbtech/dbseo/includes/scripts/' . $_strippedFileName . '.php'))
			{
				// Git oot.
				return false;
			}

			// This file holds all subclasses as well
			require_once(DBSEO_CWD . '/dbtech/dbseo/includes/scripts/' . $_strippedFileName . '.php');

			if (!class_exists($class))
			{
				// Git oot.
				return false;
			}

			// Git oot.
			return false;
		}

		if (!method_exists($class, 'createUrl'))
		{
			// Git oot.
			return false;
		}

		return call_user_func_array(array($class, 'createUrl'), array($_REQUEST));
	}

	/**
	 * Converts a URL to a full URL
	 *
	 * @param string $url
	 * @param string $thisDomain
	 *
	 * @return str
	 */
	public static function createFull($url, $thisDomain = false)
	{
		if (strpos($url, '://') !== false)
		{
			// Already a full URL
			return $url;
		}

		if (substr($url, 0, 1) == '/')
		{
			// URL started with a slash
			$url = ($thisDomain ? DBSEO_URL_SCHEME . '://' . DBSEO_HTTP_HOST : preg_replace('#^(.*?//[^/]*).*#', '$1', DBSEO_Url::$config['bburl'])) . $url;
		}
		else
		{
			// Ensure there's no trailing slashes in BB URL
			$url = preg_replace('#/$#', '', DBSEO_Url::$config['bburl']) . '/' . $url;
		}

		return $url;
	}

	/**
	 * Creates a SEO'd CMS URL based on the specified library.
	 *
	 * @param string $library
	 * @param array $data
	 *
	 * @return mixed
	 */
	public static function createCMS($routeInfo, $routeType = '', $data = array())
	{
		$_isDefault = false;
		if (!$routeInfo AND $data['page'])
		{
			// We had a page but no route info
			$routeInfo = DBSEO_Url::$config['default_page'];
			$_isDefault = true;
			$routeType = 'section';
		}

		// Match potential URLs
		preg_match('#^(list/)?(category|content|author|section)?/?(\d+)-?(.+?)?(?:/(?:view/)?(\d+))?$#', $routeInfo, $matches);
		$_isList = $matches[1];

		if (!$routeType)
		{
			// We had no route type set
			$routeType = $matches[2] ? $matches[2] : ($_isDefault ? 'section' : ($_isList ? 'category' : 'content'));
		}

		if (!$matches AND (!$routeInfo OR $routeInfo == 'content'))
		{
			// We tried to set content, but we had no route info
			$routeType = $matches = 'home';
		}

		if (preg_match('#/(edit|addcontent|rate)$#', $routeInfo) OR strpos(implode(' ', array_keys($data)), '/rate') !== false)
		{
			// We're not rewriting this
			return '';
		}

		switch ($routeType)
		{
			case 'category':
				$data['categoryid'] = $matches[3];
				$data['title'] = $matches[4];
				break;

			case 'content':
				$data['entryid'] = $matches[3];
				if (($contenttypeid = DBSEO::$datastore->fetch('contenttype.' . $data['entryid'])) === false)
				{
					// Test if we have a direct username match
					$contentInfo = DBSEO::$db->generalQuery('
						SELECT contenttypeid
						FROM $cms_node
						WHERE nodeid = ' . intval($data['entryid']) . '
					');

					// Store this
					$contenttypeid = $contentInfo['contenttypeid'];

					// Build the cache
					DBSEO::$datastore->build('contenttype.' . $data['entryid'], $contenttypeid);
				}

				if (DBSEO::getContentTypeById($contenttypeid) == 'cms_section')
				{
					// Set the content type
					$routeType = 'section';
					$data['sectionid'] = $data['entryid'];
				}

				if ($matches[5])
				{
					// Multi-page article
					$data['page'] = $matches[5];
				}

				break;

			case 'author':
				$data['userid'] = $matches[3];
				break;
		}

		// Shorthand
		$libraries = array(
			'section' 	=> 'CMS_CMSSection',
			'category' 	=> 'CMS_CMSCategory',
			'content' 	=> 'CMS_CMSEntry',
			'author'  	=> 'CMS_CMSAuthor',
			'home' 		=> 'CMS_CMSHome',
		);

		if (!$routeType OR !$matches OR !isset($libraries[$routeType]))
		{
			// Epic fail
			return '';
		}

		// And we're done
		return DBSEO_Url_Create::create($libraries[$routeType] . ($data['page'] > 1 ? '_Page' : ''), $data);
	}

	/**
	 * Adds a canonical URL tag
	 *
	 * @param string $headinclude
	 * @param string $url
	 * @param boolean $validUrl
	 *
	 * @return void
	 */
	public static function addCanonical(&$headinclude, $url, $validUrl = true)
	{
		// Drop page from the URL
		$_url = preg_replace('#&page=$#', '', $url);

		if (!$_url)
		{
			// There was nothing left
			return;
		}

		if (!$_SERVER['DBSEO_VALID_URL'])
		{
			// Check Canonical URL
			DBSEO_Url_Check::checkCanonical();
		}

		if (defined('NOHEADER') AND NOHEADER)
		{
			// This isn't even.
			return;
		}

		if (!DBSEO_Url::$config['dbtech_dbseo_add_canonical'])
		{
			// We're not adding this tag
			return;
		}

		if (
			strpos($headinclude, 'rel="canonical"') !== false OR (
				intval(DBSEO_Url::$config['templateversion']) == 4 AND
				in_array(THIS_SCRIPT, array(
					'showthread'
				))
			)
		)
		{
			// We already had a canonical tag
			return;
		}

		// Create full URL
		$fullUrl = htmlspecialchars(DBSEO_Url_Create::createFull($_url, true));

		// Preprend the canonical tag
		$headinclude = '<link rel="canonical" href="'. $fullUrl . '" />' . "\n" . $headinclude;

		// Update FB's Meta tags
		DBSEO::updateFBMeta($headinclude, 'url', $fullUrl);
	}
}