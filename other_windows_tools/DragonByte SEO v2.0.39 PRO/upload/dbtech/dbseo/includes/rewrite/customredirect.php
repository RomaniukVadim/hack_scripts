<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// DBSEO "Custom URL" class

/**
* Lets you construct & lookup custom URLs
*/
class DBSEO_Rewrite_CustomRedirect
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
		$customRedirects = DBSEO::$cache['rawurls']['customredirect'] ? DBSEO::$cache['rawurls']['customredirect'] : array();

		// Sort out the new redirect URL
		$_newUrl = count($customRedirects) ? preg_replace(array_keys($customRedirects), $customRedirects, $url) : $url;
		
		if ($_newUrl != $url)
		{
			// We had a new URL, go there plz
			DBSEO::safeRedirect($_newUrl, array(), true);
		}

		return '';
	}
}