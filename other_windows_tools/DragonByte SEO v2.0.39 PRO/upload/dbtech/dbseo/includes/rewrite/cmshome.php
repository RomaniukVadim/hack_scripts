<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// DBSEO "CMSHome URL" class

/**
* Lets you construct & lookup custom URLs
*/
class DBSEO_Rewrite_CMSHome
{
	public static $format = 'CMS_CMSHome';
	public static $structure = 'content.php';

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
		return (is_null($structure) ? self::$structure : $structure);
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
		$format 		= explode('_', (is_null($format) ? self::$format : $format), 2);
		$rawFormat 		= DBSEO::$cache['rawurls'][strtolower($format[0])][$format[1]];

		// Handle the replacements
		$newUrl = $rawFormat;
		
		/*DBTECH_PRO_START*/
		if (DBSEO::$config['dbtech_dbseo_custom_cms'] AND strpos($newUrl, '://') === false)
		{
			// Use a custom cms domain
			$newUrl = DBSEO::$config['dbtech_dbseo_custom_cms'] . $newUrl;
		}
		/*DBTECH_PRO_END*/

		//if (strpos($newUrl, '%') !== false)
		//{
			// We should not return true if any single URL remains
			//return '';
		//}

		// Return the new URL
		return $newUrl;
	}
}