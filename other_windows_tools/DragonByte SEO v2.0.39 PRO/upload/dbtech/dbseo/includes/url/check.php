<?php if(!defined('IN_DBSEO')) die('Access denied.');

/**
 * DBSEO_Url_Check
 *
 * @package DBSEO
 * @access public
 */
class DBSEO_Url_Check extends DBSEO_Url
{
	/**
	 * Checks a URL against a specified format.
	 *
	 * @param string $format
	 * @param string $url
	 * @param boolean $partial
	 * @param boolean $strict
	 *
	 * @return mixed
	 */
	public static function check($format, $url, $partial = false, $strict = false)
	{
		// Prepare the regexp format
		$preparedFormat = explode('_', $format, 2);
		$regexpFormat 	= DBSEO::$cache['preparedurls'][strtolower($preparedFormat[0])][$preparedFormat[1]];
		$rawFormat 		= DBSEO::$cache['rawurls'][strtolower($preparedFormat[0])][$preparedFormat[1]];

		switch (strtolower($preparedFormat[0]))
		{
			case 'attachment':
				if (strpos($url, DBSEO::$config['dbtech_dbseo_attachment_prefix']) === 0)
				{
					// Shorthand
					$url = substr($url, strlen(DBSEO::$config['dbtech_dbseo_attachment_prefix']));
				}
				else if (strlen(DBSEO::$config['dbtech_dbseo_attachment_prefix']))
				{
					// This didn't match :(
					return null;
				}
				break;

			case 'avatar':
				if (strpos($url, DBSEO::$config['dbtech_dbseo_avatar_prefix']) === 0)
				{
					// Shorthand
					$url = substr($url, strlen(DBSEO::$config['dbtech_dbseo_avatar_prefix']));
				}
				else if (strlen(DBSEO::$config['dbtech_dbseo_avatar_prefix']))
				{
					// This didn't match :(
					return null;
				}
				break;
		}

		// By default we're not in a folder
		$isFolder = false;
		if (substr($regexpFormat, -1) == '/' AND substr($url, -1) != '/' AND !file_exists($url))
		{
			// Whoops I guess we are, append a query string option
			$isFolder = true;
			$regexpFormat .= '?';
		}

		$regexpFormat = $partial ? '#' . $regexpFormat . '#' : '#^' . $regexpFormat . '$#';
		$urlToCheck = $url;
		if (strpos($regexpFormat, 'http\://') !== false AND strpos($urlToCheck, 'http:') === false AND strpos(DBSEO_URL_CLEAN, DBSEO_URL_BASE_PATH) === false)
		{
			// The rule had a URL in it
			$urlToCheck = DBSEO_URL_BASE_PATH  . '/' . $url;
		}

		/*
		if ($regexpFormat == '#^members/list/([a-z]|0|all)(\d+)\.html$#')
		{
			echo "<pre>";
			print_r($regexpFormat);
			echo "<br />";
			print_r($urlToCheck);
			echo "</pre>";
		}
		*/

		if (!preg_match($regexpFormat, $urlToCheck, $matches))
		{
			// No matches :(
			return null;
		}

		if ($isFolder AND $url)
		{
			// Suggest a URL
			self::$suggestedUrls[$format] = $url . '/';

			if ($strict)
			{
				// We're only checking strict matches here
				return null;
			}
		}

		$fields = $results = array();
		if (preg_match_all('#%([a-z_]+)%#', $rawFormat, $matches2, PREG_PATTERN_ORDER))
		{
			// Prepare the fields to use
			$fields = array_values(array_unique($matches2[1]));
			foreach ($fields as $key => $field)
			{
				// Construct the results array
				$results[$field] = $matches[$key + 1];
			}
		}

		return $results;
	}


	/**
	 * Checks a URL against a specified format.
	 *
	 * @param string $format
	 * @param string $regexpFormat
	 * @param string $rawFormat
	 * @param string $url
	 * @param boolean $partial
	 * @param boolean $strict
	 *
	 * @return mixed
	 */
	public static function checkHistory($format, $regexpFormat, $rawFormat, $url, $partial = false, $strict = false)
	{
		// Prepare the regexp format
		$preparedFormat = explode('_', $format, 2);

		switch (strtolower($preparedFormat[0]))
		{
			case 'attachment':
				if (strpos($url, DBSEO::$config['dbtech_dbseo_attachment_prefix']) === 0)
				{
					// Shorthand
					$url = substr($url, strlen(DBSEO::$config['dbtech_dbseo_attachment_prefix']));
				}
				break;

			case 'avatar':
				$url = substr($url, strlen(DBSEO::$config['dbtech_dbseo_avatar_prefix']));
				break;
		}

		// By default we're not in a folder
		$isFolder = false;
		if (substr($regexpFormat, -1) == '/' AND substr($url, -1) != '/' AND !file_exists($url))
		{
			// Whoops I guess we are, append a query string option
			$isFolder = true;
			$regexpFormat .= '?';
		}

		$regexpFormat = $partial ? '#' . $regexpFormat . '#' : '#^' . $regexpFormat . '$#';
		$urlToCheck = $url;
		if (strpos($regexpFormat, 'http\://') !== false AND strpos($urlToCheck, 'http:') === false AND strpos(DBSEO_URL_CLEAN, DBSEO_URL_BASE_PATH) === false)
		{
			// The rule had a URL in it
			$urlToCheck = DBSEO_URL_BASE_PATH  . '/' . $url;
		}

		/*
		if ($regexpFormat == '#^members/list/([a-z]|0|all)(\d+)\.html$#')
		{
			echo "<pre>";
			print_r($regexpFormat);
			echo "<br />";
			print_r($urlToCheck);
			echo "</pre>";
		}
		*/

		if (!preg_match($regexpFormat, $urlToCheck, $matches))
		{
			// No matches :(
			return null;
		}

		$fields = $results = array();
		$results['_suggestedUrl'] = '';
		if ($isFolder AND $url)
		{
			// Suggest a URL
			$results['_suggestedUrl'] = $url . '/';

			if ($strict)
			{
				// We're only checking strict matches here
				return null;
			}
		}

		if (preg_match_all('#%([a-z_]+)%#', $rawFormat, $matches2, PREG_PATTERN_ORDER))
		{
			// Prepare the fields to use
			$fields = array_values(array_unique($matches2[1]));
			foreach ($fields as $key => $field)
			{
				// Construct the results array
				$results[$field] = $matches[$key + 1];
			}
		}

		return $results;
	}

	/**
	 * Checks whether the current URL is the canonical URL, and redirects if not
	 *
	 * @return void
	 */
	public static function checkCanonical()
	{
		$cleanQuery = '';
		if (DBSEO_URL_QUERY)
		{
			// Parse the query
			$queryStr = array();
			parse_str(DBSEO_URL_QUERY, $queryStr);

			// Unset dangerous variables
			unset($queryStr['mode']);

			// Set the cleaned query
			$cleanQuery = http_build_query($queryStr);
		}

		if (!$_SERVER['DBSEO_VALID_URL'])
		{
			$_queryFile 		= $_SERVER['DBSEO_FILE'] . '?' . $cleanQuery;
			$_fileExists 		= (file_exists($_queryFile) OR (file_exists(basename($_queryFile)) AND strpos($_queryFile, '.php') !== false)) AND substr($_queryFile, -1) != '/';
			$_fileExistsDeep 	= file_exists($_queryFile) AND strpos($_queryFile, '/') !== false;

			// Attempt a redirect
			DBSEO_Url::redirect($_queryFile, $_fileExists, $_fileExistsDeep);

			// We don't want to do anything more
			return;
		}

		// Run the script stuff to create $rewrittenUrl using updateEnvironment settings
		if (!$rewrittenUrl = DBSEO_Url_Create::createAny())
		{
			// This is the wrong page
			return;
		}

		// Create full URL
		$fullRewrittenUrl = DBSEO_Url_Create::createFull($rewrittenUrl, true);

		if (DBSEO_URL_QUERY)
		{
			// Now append it to the full URL
			$fullRewrittenUrl .= '?' . $cleanQuery;
		}

		if (DBSEO_Url::$config['dbtech_dbseo_enable_utf8'])
		{
			// Ensure this is using the correct characters
			$fullRewrittenUrl = html_entity_decode($fullRewrittenUrl, ENT_COMPAT | ENT_HTML401, 'UTF-8');
		}

		if (
			DBSEO_URL != $fullRewrittenUrl AND
			DBSEO_URL != utf8_encode($fullRewrittenUrl) AND
			urldecode(DBSEO_URL) != $fullRewrittenUrl AND
			urldecode(DBSEO_URL) != utf8_encode($fullRewrittenUrl) AND
			DBSEO_REQURL2 != $rewrittenUrl AND
			urldecode(DBSEO_REQURL2) != $rewrittenUrl
		)
		{
			if ($_SERVER['DBSEO_VALID_URL'])
			{
				// We only want to preserve existing queries
				$_SERVER['QUERY_STRING'] = $cleanQuery;
			}
			else
			{
				$_SERVER['QUERY_STRING'] = '';
			}

			// Redirec to the real URL
			DBSEO::safeRedirect($fullRewrittenUrl);
		}

		// We can now assume it's a valid URL
		$_SERVER['DBSEO_VALID_URL'] = true;
	}
}