<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// DBSEO "Attachment_Alt URL" class

/**
* Lets you construct & lookup custom URLs
*/
class DBSEO_Rewrite_Attachment_Alt extends DBSEO_Rewrite_Attachment
{
	public static $format = 'Attachment_Attachment_Alt';

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
		// There's no URL for the alt attribute
		return false;
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
		if (!count(DBSEO::$cache['rawurls']))
		{
			// Ensure we got this kickstarted
			DBSEO::initUrlCache();
		}

		// Prepare the regexp format
		$format = (is_null($format) ? self::$format : $format);
		$formatTmp = explode('_', $format, 2);
		
		// Set this back
		DBSEO::$cache['rawurls'][strtolower($formatTmp[0])][$formatTmp[1]] = str_replace('%thread_title%', '%thread_title_ue%', DBSEO::$cache['rawurls'][strtolower($formatTmp[0])][$formatTmp[1]]);

		// Now pass this on to the parent
		return parent::createUrl($data, $format);
	}
}