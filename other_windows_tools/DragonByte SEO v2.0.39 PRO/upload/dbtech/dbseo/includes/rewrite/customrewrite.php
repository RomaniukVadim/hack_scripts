<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// DBSEO "Custom URL" class

/**
* Lets you construct & lookup custom URLs
*/
class DBSEO_Rewrite_CustomRewrite
{
	/**
	 * Resolves the URL back to its original componen
	 *
	 * @param string $url
	 *
	 * @return mixed
	 */
	public static function resolveUrl($url)
	{
		if (!count(DBSEO::$cache['rawurls']))
		{
			// Ensure we got this kickstarted
			DBSEO::initUrlCache();
		}

		// Determine what URL type we should use
		$customRules = DBSEO::$cache['preparedurls']['custom'] ? DBSEO::$cache['preparedurls']['custom'][substr($url, -1) == '/' ? 0 : 1] : array();

		if (
			DBSEO_URL_QUERY AND (
				strpos(DBSEO_URL_QUERY, 'vbseourl=') !== false OR # Compatibility
				strpos(DBSEO_URL_QUERY, 'dbseourl=') !== false
			) AND
			strpos(implode("\s", array_values($customRules)), '?') !== false
		)
		{
			// We had a query string we need to take into consideration
			$url .= '?' . DBSEO_URL_QUERY;
		}

		$_restoredUrl = preg_replace(array_keys($customRules), $customRules, $url);

		if (substr($_restoredUrl, -1) == '$')
		{
			// Chop this off if we need to
			$_restoredUrl = substr($_restoredUrl, 0, -1);
		}

		if ($_restoredUrl != $url)
		{
			if (
				strpos($_restoredUrl, '#s#') !== false AND
				substr($url, -1) != '/' AND
				substr($url, -5) != '.html'
			)
			{
				// This is used in some URL lookups
				DBSEO_Url::$suggestedUrls['Custom_CustomRewrite'] = $url . '/';

				return '';
			}

			// Ensure this is done
			DBSEO::updateEnvironment($_restoredUrl);

			return str_replace('#s#', '', $_restoredUrl);
		}

		return '';
	}

	/**
	 * Creates a SEO'd URL based on the URL fed
	 *
	 * @param string $url
	 * @param array $data
	 *
	 * @return string
	 */
	public static function createUrl($url)
	{
		if (!count(DBSEO::$cache['rawurls']))
		{
			// Ensure we got this kickstarted
			DBSEO::initUrlCache();
		}

		if ($_POST['ajax'] OR $_POST['do'])
		{
			// Let POST requests go through unharmed
			return '';
		}

		//$pos = strpos(DBSEO_REQURL, $url);
		//$urlToMatch = (DBSEO_RELPATH AND $pos !== false) ? substr(DBSEO_REQURL, $pos) : DBSEO_REQURL;

		// Create our new URL
		return preg_replace(array_keys(DBSEO::$cache['rawurls']['custom']), DBSEO::$cache['rawurls']['custom'], $url);
	}
}