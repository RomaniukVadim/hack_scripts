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
	
	// Pre post cache
	$this->post['earlierPosts'] = $this->post['postcount'];

	// Store some caches
	DBSEO::$cache['post'][$this->post['postid']] = $this->post;
	DBSEO::$cache['thread'][$this->thread['threadid']] = $this->thread;

	if (!$this->registry->GPC['ajax'])
	{
		// Prepare replacements
		$this->post['message'] = DBSEO::replaceIds($this->post['message']);
	}

	if (!is_array($this->registry->options['dbtech_dbseo_socialshare_excludedforums']))
	{
		$this->registry->options['dbtech_dbseo_socialshare_excludedforums'] = @unserialize($this->registry->options['dbtech_dbseo_socialshare_excludedforums']);
		$this->registry->options['dbtech_dbseo_socialshare_excludedforums'] = is_array($this->registry->options['dbtech_dbseo_socialshare_excludedforums']) ? $this->registry->options['dbtech_dbseo_socialshare_excludedforums'] : array();
	}

	if (
		DBSEO::$config['dbtech_dbseo_enable_socialsharing'] AND
		!in_array($this->thread['forumid'], $this->registry->options['dbtech_dbseo_socialshare_excludedforums']) AND (
			!count(DBSEO::$config['dbtech_dbseo_socialshare_usergroups']) OR 
			is_member_of($this->registry->userinfo, DBSEO::$config['dbtech_dbseo_socialshare_usergroups'])
		) AND (
			DBSEO::$config['dbtech_dbseo_socialshare_thread_postcontent'] != 'none' OR
			DBSEO::$config['dbtech_dbseo_socialshare_thread_everypost'] != 'none' OR
			DBSEO::$config['dbtech_dbseo_socialshare_thread_firstpost'] != 'none'
		)
	)
	{
		if (!class_exists('vB_Template'))
		{
			// Ensure we have this
			require_once(DIR . '/dbtech/dbseo/includes/class_template.php');
		}

		if (DBSEO::$config['dbtech_dbseo_socialshare_thread_postcontent'] != 'none')
		{
			// Set URL
			$show['dbtech_dbseo_url'] = $this->registry->options['bburl'] . '/' . (DBSEO::$config['dbtech_dbseo_rewrite_showpost'] ? 
				DBSEO_Url_Create::create('ShowPost_ShowPost', $this->post) : 
				'showpost.php?p=' . $this->post['postid'] . '&post_count=' . $this->post['postcount']
			);

			if (intval($this->registry->versionnumber) == 3)
			{
				// Above post list
				eval('$template_hook[\'postbit_signature_start\'] .= "' . fetch_template('dbtech_dbseo_socialshare_' . DBSEO::$config['dbtech_dbseo_socialshare_thread_postcontent']) . '";');
			}
			else
			{
				// Above post list
				$template_hook['postbit_signature_start'] .= vB_Template::create('dbtech_dbseo_socialshare_' . DBSEO::$config['dbtech_dbseo_socialshare_thread_postcontent'])->render();
			}

			if (!$this->post['signature'])
			{
				// Dummy signature stuff
				$this->post['signature'] = '&nbsp;';
			}
		}

		if ((DBSEO::$config['dbtech_dbseo_socialshare_thread_firstpost'] != 'none' AND $this->post['isfirstshown']))
		{
			$show['dbtech_dbseo_url'] = $this->registry->options['bburl'] . '/' . (DBSEO::$config['dbtech_dbseo_rewrite_thread'] ? 
				DBSEO_Url_Create::create('Thread_Thread', $thread) : 
				'showthread.php?t=' . $thread['threadid']
			);

			if (intval($this->registry->versionnumber) == 3)
			{
				// Above post list
				eval('$template_hook[\'postbit_end\'] .= "' . fetch_template('dbtech_dbseo_socialshare_' . DBSEO::$config['dbtech_dbseo_socialshare_thread_firstpost']) . '";');
			}
			else
			{
				// Above post list
				$template_hook['postbit_end'] .= vB_Template::create('dbtech_dbseo_socialshare_' . DBSEO::$config['dbtech_dbseo_socialshare_thread_firstpost'])->render();
			}
		}
		else if (DBSEO::$config['dbtech_dbseo_socialshare_thread_everypost'] != 'none')
		{
			// Set URL
			$show['dbtech_dbseo_url'] = $this->registry->options['bburl'] . '/' . (DBSEO::$config['dbtech_dbseo_rewrite_showpost'] ? 
				DBSEO_Url_Create::create('ShowPost_ShowPost', $this->post) : 
				'showpost.php?p=' . $this->post['postid'] . '&post_count=' . $this->post['postcount']
			);

			if (intval($this->registry->versionnumber) == 3)
			{
				// Above post list
				eval('$template_hook[\'postbit_end\'] .= "' . fetch_template('dbtech_dbseo_socialshare_' . DBSEO::$config['dbtech_dbseo_socialshare_thread_everypost']) . '";');
			}
			else
			{
				// Above post list
				$template_hook['postbit_end'] .= vB_Template::create('dbtech_dbseo_socialshare_' . DBSEO::$config['dbtech_dbseo_socialshare_thread_everypost'])->render();
			}
		}
	}
}
while (false);
?>