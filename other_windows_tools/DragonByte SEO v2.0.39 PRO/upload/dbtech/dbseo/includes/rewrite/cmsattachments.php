<?php if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
// DBSEO "CMSAttachments URL" class

/**
* Lets you construct & lookup custom URLs
*/
class DBSEO_Rewrite_CMSAttachments extends DBSEO_Rewrite_Attachment
{
	public static $format = 'Attachment_CMSAttachments';

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
		// Prepare the format
		$format = is_null($format) ? self::$format : $format;

		if (!count(DBSEO::$cache['rawurls']))
		{
			// Ensure we got this kickstarted
			DBSEO::initUrlCache();
		}

		// Prepare the regexp format
		$format 		= explode('_', (is_null($format) ? self::$format : $format), 2);
		$rawFormat 		= DBSEO::$cache['rawurls'][strtolower($format[0])][$format[1]];
		
		// Fetch attachment info
		if (!$attachmentInfo = self::getInfo($data['attachmentid']))
		{
			// Blog attachment didn't exist
			return '';
		}
		
		// Init this
		$replace = array();

		// Set up the original filename
		$replace['%original_filename%'] = DBSEO_Filter::filterText($attachmentInfo['filename'], '.');

		if ($data['d'])
		{
			// Include the dateline
			$data['attachmentid'] .= 'd' . $data['d'];
		}

		if ($data['thumb'])
		{
			// This was a thumbnail
			$data['attachmentid'] .= 't';
		}

		// Set some replacement vars
		$replace['%attachment_id%'] = $data['attachmentid'];

		// Handle the replacements
		$newUrl = str_replace(array_keys($replace), $replace, $rawFormat);

		if (strpos($newUrl, DBSEO::$config['dbtech_dbseo_attachment_prefix']) !== 0)
		{
			// Only append the prefix if we need to
			$newUrl = DBSEO::$config['dbtech_dbseo_attachment_prefix'] . $newUrl;
		}
		
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