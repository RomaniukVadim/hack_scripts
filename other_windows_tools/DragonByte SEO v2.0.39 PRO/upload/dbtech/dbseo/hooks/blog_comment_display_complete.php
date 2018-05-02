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

	if (
		DBSEO::$config['dbtech_dbseo_enable_socialsharing'] AND 
		(!count(DBSEO::$config['dbtech_dbseo_socialshare_usergroups']) OR is_member_of($this->registry->userinfo, DBSEO::$config['dbtech_dbseo_socialshare_usergroups'])) AND 
		DBSEO::$config['dbtech_dbseo_socialshare_blog_postcontent'] != 'none'
	)
	{
		if (!class_exists('vB_Template'))
		{
			// Ensure we have this
			require_once(DIR . '/dbtech/dbseo/includes/class_template.php');
		}

		// Set URL
		$show['dbtech_dbseo_url'] = $this->registry->options['bburl'] . '/' . (DBSEO::$config['dbtech_dbseo_rewrite_blogentry'] ? 
			DBSEO_Url_Create::create('Blog_BlogComment', $this->response) : 
			'blog.php?bt=' . $this->response['blogtextid']
		);

		if (intval($this->registry->versionnumber) == 3)
		{
			// Above post list
			eval('$response[\'message\'] .= "' . fetch_template('dbtech_dbseo_socialshare_' . DBSEO::$config['dbtech_dbseo_socialshare_blog_postcontent']) . '";');
		}
		else
		{
			// Above post list
			$response['message'] .= vB_Template::create('dbtech_dbseo_socialshare_' . DBSEO::$config['dbtech_dbseo_socialshare_blog_postcontent'])->render();
		}
	}
}
while (false);
?>