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

	// Shorthand
	$pagetext = $this->fetch_field('pagetext', 'post');

	if (!is_array($this->registry->options['dbtech_dbseo_enable_tagging_thread_excludedforums']))
	{
		$this->registry->options['dbtech_dbseo_enable_tagging_thread_excludedforums'] = @unserialize($this->registry->options['dbtech_dbseo_enable_tagging_thread_excludedforums']);
		$this->registry->options['dbtech_dbseo_enable_tagging_thread_excludedforums'] = is_array($this->registry->options['dbtech_dbseo_enable_tagging_thread_excludedforums']) ? $this->registry->options['dbtech_dbseo_enable_tagging_thread_excludedforums'] : array();
	}

	if (
		$this->registry->options['threadtagging'] AND
		$this->registry->options['dbtech_dbseo_enable_tagging'] AND
		$this->registry->options['dbtech_dbseo_enable_tagging_thread'] AND
		!in_array($this->info['forum']['forumid'], $this->registry->options['dbtech_dbseo_enable_tagging_thread_excludedforums']) AND
		!in_array(-1, $this->registry->options['dbtech_dbseo_enable_tagging_thread_excludedforums'])
	)
	{
		// Get current tags
		$keywords = preg_split('#\s*,\s*#s', $this->fetch_field('taglist', 'thread'), -1, PREG_SPLIT_NO_EMPTY);

		// Shorthand
		$maxTags = ($this->registry->options['tagmaxstarter'] > 0 ? $this->registry->options['tagmaxstarter'] : $this->registry->options['tagmaxthread']);

		if (sizeof($keywords) < $maxTags)
		{
			// Preparations
			$prefix = '';
			$prefixId = $this->fetch_field('prefixid');

			if (!empty($prefixId))
			{
				// We had a thread prefix
				global $vbphrase;
				$prefix = trim($vbphrase["prefix_{$prefixId}_title_plain"]);
			}

			// Do the vB Content filtering
			DBSEO_Filter::vBContentFilter(
				$keywords,
				$prefix . ' ' . unhtmlspecialchars($this->fetch_field('title')) . ' ' . ($this->registry->options['dbtech_dbseo_enable_tagging_thread_onlytitle'] ? '' : $pagetext),
				$maxTags,
				$this->registry->options['tagminlen'],
				$this->registry->options['tagmaxlen'],
				$this->registry->options['tagforcelower'],
				preg_split('/\s+/s', vbstrtolower($this->registry->options['tagbadwords']), -1, PREG_SPLIT_NO_EMPTY),
				preg_split('/\s+/s', vbstrtolower($this->registry->options['taggoodwords']), -1, PREG_SPLIT_NO_EMPTY)
			);

			// Now set the tag list
			$this->registry->GPC['taglist'] = implode(', ', $keywords);
			$this->setr('taglist', $this->registry->GPC['taglist']);
		}
	}

	if (!$pagetext)
	{
		// Shouldn't happen
		break;
	}

	if (strpos($pagetext, '[post]') !== false)
	{
		// Replace post BBCode with full URL
		$pagetext = preg_replace(
			'#\[post\](\d+)\[\/post\]#',
			'[url]' . DBSEO::$config['_bburl'] . '/showthread.php?p=$1#post$1[/url]',
			$pagetext
		);
	}

	if (!$pagetext)
	{
		// Shouldn't happen
		break;
	}

	// Force text URL rewrite
	DBSEO::$config['dbtech_dbseo_rewrite_texturls'] = true;

	// Process the content
	$pagetext = DBSEO::processContent($pagetext);

	if (!$pagetext)
	{
		// Shouldn't happen
		break;
	}

	if (THIS_SCRIPT != 'vbcms')
	{
		// Link external titles
		$pagetext = DBSEO::linkExternalTitles($pagetext, false);

		if (!$pagetext)
		{
			// Shouldn't happen
			break;
		}
	}

	// Revert this
	DBSEO::$config['dbtech_dbseo_rewrite_texturls'] = false;

	// And finally set the pagetext back
	$this->do_set('pagetext', $pagetext, 'post');
}
while (false);
?>