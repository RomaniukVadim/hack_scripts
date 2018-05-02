<?php
do
{
	if (!class_exists('DBSEO'))
	{
		// Set important constants
		define('DBSEO_CWD', 	DIR);
		define('DBSEO_TIMENOW', TIMENOW);
		define('IN_DBSEO', 		true);

		// Make sure we nab this class
		require_once(DBSEO_CWD . '/dbtech/dbseo/includes/class_core.php');

		// Init DBSEO
		DBSEO::init(true);
	}

	if (!DBSEO::$config['dbtech_dbseo_active'])
	{
		// Mod is disabled
		break;
	}

	if (DBSEO::$config['dbtech_dbseo_rewrite_memberprofile'])
	{
		// Pre-cache user info
		DBSEO::$cache['_objectIds']['userinfo'][$userinfo['userid']] = array(
			'userid' => $userinfo['userid'], 
			'username' => $userinfo['username']
		);
	}

	if (!DBSEO::$config['dbtech_dbseo_rewrite_attachment'] OR THIS_SCRIPT != 'attachment')
	{
		// We're not dealing with this
		break;
	}

	// Store the found object IDs
	DBSEO::$cache['_objectIds'][DBSEO::$config['_picturestorage']][] = intval($_GET[DBSEO::$config['_pictureid']]);

	if ($_GET['albumid'])
	{
		// We had an album
		DBSEO::$cache['_objectIds']['album'][] = intval($_GET['albumid']);

		// Create album picture file URL
		$newUrl = DBSEO_Url_Create::create('Album_AlbumPictureFile', array(
			DBSEO::$config['_pictureid'] => intval($_GET[DBSEO::$config['_pictureid']]),
			'thumb' 							=> (strpos($_GET[DBSEO::$config['_pictureid']], 't') !== false),
			'albumid' 							=> intval($_GET['albumid'])
		));
	}
	else
	{
		// Grab attachment info
		$attachmentInfo = DBSEO_Rewrite_Attachment::getInfo($_GET[DBSEO::$config['_pictureid']]);

		switch (DBSEO::getContentType($attachmentInfo))
		{
			case 'album':
				if (DBSEO::$config['dbtech_dbseo_rewrite_album'])
				{
					// We're rewriting album URLs
					$_urlFormat = 'Album_AlbumPictureFile';
				}
				break;

			case 'group':
				// Social group file
				$_urlFormat = 'SocialGroup_SocialGroupPictureFile';
				break;

			case 'blog':
				if (DBSEO::$config['dbtech_dbseo_rewrite_blogattachment'])
				{
					// We're rewriting blog attachments
					$_urlFormat = 'Attachment_Attachment_BlogAttachment';
				}
				break;

			case 'cms_article':
				// CMS Attachments
				$_urlFormat = 'Attachment_Attachment_CMSAttachments';
				break;

			default:
				// Plain ol' attachment
				$_urlFormat = 'Attachment_Attachment';
				break;
		}
		
		if ($_urlFormat)
		{
			// We had a redirect URL, so get to it!							
			$newUrl = DBSEO_Url_Create::create($_urlFormat, $_GET);
		}

		if (!$newUrl OR strpos($newUrl, '%') !== false)
		{
			// Invalid URL
			break;
		}
	}

	$attachmentUrl = preg_replace('#\?.*#', '', DBSEO_REQURL);
	if (strpos($newUrl, $attachmentUrl) === false AND strpos($newUrl, preg_replace('#(\d)(d(\d+))?t?#', '$1', $attachmentUrl)) === false)
	{
		if ($_GET['albumid'])
		{
			$attachmentInfo = DBSEO::$db->generalQuery('
				SELECT picturelegacy.*
				FROM $picturelegacy AS picturelegacy
				INNER JOIN $attachment AS a ON (picturelegacy.attachmentid = a.attachmentid)
				WHERE picturelegacy.pictureid = ' . intval($_GET[DBSEO::$config['_pictureid']]) . '
				' . ($_GET['albumid'] ? "AND picturelegacy.type = 'album' AND picturelegacy.primaryid = " . intval($_GET['albumid']) : "") . '
			');

			if ($attachmentInfo)
			{
				// Store attachment info
				DBSEO::$cache['_objectIds'][DBSEO::$config['_picturestorage']][] = $attachmentInfo;

				// Grab the album picture file
				$newUrl = DBSEO_Url_Create::create('Album_AlbumPictureFile', array(
					DBSEO::$config['_pictureid'] => intval($attachmentInfo[DBSEO::$config['_pictureid']]),
					'albumid' 							=> intval($_GET['albumid'])
				));
			}
		}

		if ($newUrl)
		{
			// Redirect to the correct URL
			DBSEO::safeRedirect($newUrl, array('albumid', DBSEO::$config['_pictureid']), true);
		}
	}
}
while (false);
?>