<?php
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin 4.2.2 - Licence Number VBS9D7F856
|| # ---------------------------------------------------------------- # ||
|| # Copyright ©2000-2013 vBulletin Solutions Inc. All Rights Reserved. ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/

/**
* Helper class for running a multi-page sitemap generation process.
*
* @package	vBulletin
*/
class DBSEO_SiteMapRunner
{
	/**
	* The vBulletin registry object
	*
	* @var	vB_Registry
	*/
	protected $registry = null;

	/**
	* The vBulletin Database object
	*
	* @var	vBulletin_Database
	*/
	protected $dbobject = null;

	/**
	* The current sitemap runner session. Tracks progress across pages.
	*
	* @var	array
	*/
	protected $session = array();

	/**
	* Stores if the environment is ok for execution.
	*
	* @var	bool|null	Null = unknown, otherwise treat as value
	*/
	protected $environment_ok = null;

	/**
	* If the entire sitemap generation process is known to be finished.
	*
	* @var	bool
	*/
	public $is_finished = false;

	/**
	* Name of the written out filename
	*
	* @var	string
	*/
	public $written_filename = '';

	/**
	* Constructor. Automatically sets up the session.
	*
	* @param	vB_Registry	Registry object
	*/
	public function __construct(vB_Registry $registry)
	{
		$this->registry = $registry;
		$this->dbobject = $registry->db;
		$this->session = self::fetch_session_from_db($registry);

		if (!isset($this->registry->options['sitemap_priority']))
		{
			// Set the default priority value
			$this->registry->options['sitemap_priority'] = 0.5;
		}
	}

	/**
	* Fetches the session info for this run from the database.
	*
	* @param	vB_Registry
	*
	* @return	array	Array of session info; new session is created if needed
	*/
	public static function fetch_session_from_db(vB_Registry $registry)
	{
		global $vbulletin, $vbphrase;

		$sitemap_status = $registry->db->query_first_slave("SELECT text FROM " . TABLE_PREFIX . "adminutil WHERE title = 'dbtech_dbseo_sitemapsession'");
		if ($sitemap_status)
		{
			$session = unserialize($sitemap_status['text']);
		}

		if (!is_array($session))
		{
			$contenttypes = array('custom');

			if ($vbulletin->options['dbtech_dbseo_sitemap_include_showpost'])
			{
				$contenttypes[] = 'post';
			}

			if ($vbulletin->options['dbtech_dbseo_sitemap_include_member'])
			{
				$contenttypes[] = 'member';
			}

			if ($vbulletin->options['dbtech_dbseo_sitemap_include_showthread'])
			{
				$contenttypes[] = 'thread';
			}

			if ($vbulletin->options['dbtech_dbseo_sitemap_include_forumdisplay'])
			{
				$contenttypes[] = 'forum';
			}

			if ($vbulletin->options['dbtech_dbseo_sitemap_include_poll'])
			{
				$contenttypes[] = 'poll';
			}

			if (isset($vbulletin->products['vbblog']) AND $vbulletin->products['vbblog'] AND $vbulletin->options['dbtech_dbseo_sitemap_include_blog'])
			{
				$contenttypes[] = 'blog';
			}

			if (isset($vbulletin->products['vbblog']) AND $vbulletin->products['vbblog'] AND $vbulletin->options['dbtech_dbseo_sitemap_include_blogtag'])
			{
				$contenttypes[] = 'blogtag';
			}

			if ($vbulletin->options['dbtech_dbseo_sitemap_include_album'])
			{
				$contenttypes[] = 'album';
			}

			if ($vbulletin->options['dbtech_dbseo_sitemap_include_group'])
			{
				$contenttypes[] = 'group';
			}

			if ($vbulletin->options['dbtech_dbseo_sitemap_include_groupdiscuss'])
			{
				$contenttypes[] = 'groupdiscuss';
			}

			if ($vbulletin->options['dbtech_dbseo_sitemap_include_groupimage'])
			{
				//$contenttypes[] = 'groupimage';
			}

			if ($vbulletin->options['dbtech_dbseo_sitemap_include_tags'])
			{
				$contenttypes[] = 'tags';
			}

			if (isset($vbulletin->products['vbcms']) AND $vbulletin->products['vbcms'] AND $vbulletin->options['dbtech_dbseo_sitemap_include_content'])
			{
				$contenttypes[] = 'cms';
				$contenttypes[] = 'cmssection';
			}

			reset($contenttypes);
			$session = array(
				'types' => $contenttypes,
				'current_content' => current($contenttypes),
				'startat' => 0,
				'state' => 'start'
			);
		}

		($hook = vBulletinHook::fetch_hook('dbtech_dbseo_sitemap_add_content_types') OR $hook = $vbulletin->pluginlist['dbtech_dbseo_sitemap_add_content_types']) ? eval($hook) : false;

		return $session;
	}

	/**
	* Fetches the current, "in progress" session. This may differ from the state
	* in the DB if changes are pending.
	*
	* @return	array
	*/
	public function fetch_session()
	{
		return $this->session;
	}

	/**
	* Check that the environment is ok for building the sitemap.
	*
	* @return	array	Array of status information. Check 'error' key.
	*/
	public function check_environment()
	{
		$status = $this->check_environment_internal();
		$this->environment_ok = ($status['error'] != '');

		return $status;
	}

	/**
	* Internal function for checking the environment. This is where specific checks should be run.
	*
	* @return	array	Array of status info. Check 'error' key.
	*/
	protected function check_environment_internal()
	{
		$status = array(
			'error' => '',
			'loggable' => false
		);

		if ($this->session['state'] == 'failed')
		{
			$status['error'] = $this->session['failure_reason'];
			$status['loggable'] = false; // should be logged when it occurs, not each "hit"
		}

		if (!$this->registry->options['dbtech_dbseo_sitemap_path'] OR !is_writable($this->registry->options['dbtech_dbseo_sitemap_path']))
		{
			$status['error'] = 'dbtech_dbseo_sitemap_path_not_writable';
			// only log on the first occurance in a session
			$status['loggable'] = ($this->session['state'] == 'start');
		}

		return $status;
	}

	/**
	* Generates one "page" worth of a sitemap and prepares for the next page or finalizes.
	*
	* @return	bool	True on success
	*/
	public function generate()
	{
		if ($this->environment_ok === null)
		{
			$status = $this->check_environment();
			if ($status['error'])
			{
				return false;
			}
		}

		$first_page = ($this->session['state'] == 'start');

		$this->set_state();

		$sitemap_obj = self::get_content_handler($this->session['current_content'], $this->registry);
		if (!$sitemap_obj)
		{
			$this->trigger_failure('dbtech_dbseo_invalid_sitemap_content_type');
			return false;
		}

		if ($first_page)
		{
			$sitemap_obj->remove_sitemaps();
			/*DBTECH_PRO_START*/
			$sitemap_obj->import_custom_urls();
			/*DBTECH_PRO_END*/
		}

		$this->session['count'][$this->session['current_content']] += $sitemap_obj->generate_sitemap($this->session['startat'], $this->registry->options['dbtech_dbseo_sitemap_url_perpage']);
		if (!$this->write_sitemap($sitemap_obj))
		{
			$this->trigger_failure('dbtech_dbseo_sitemap_creation_failed');
			return false;
		}

		$this->is_finished = $this->is_finished($sitemap_obj);

		if ($this->is_finished)
		{
			return $this->finalize($sitemap_obj);
		}
		else
		{
			return $this->prepare_next_page();
		}
	}

	/**
	* Sets the session state at the beginning of generating a "page".
	*/
	protected function set_state()
	{
	}

	/**
	* Fetches the handler class for a particular type of content.
	*
	* @return	DBSEO_SiteMap	Subclass of DBSEO_SiteMap
	*/
	public static function get_content_handler($type, vB_Registry $registry)
	{
		if (empty($type))
		{
			return false;
		}

		$classname = 'DBSEO_SiteMap_' . ucfirst(strtolower($type));
		if (class_exists($classname, false))
		{
			return new $classname($registry);
		}
		return false;
	}

	/**
	* Writes out the sitemap file for the current content.
	*
	* @param	DBSEO_SiteMap	Current sitemap object
	*
	* @return	boolean
	*/
	protected function write_sitemap($sitemap_obj)
	{
		if (!$sitemap_obj->get_content())
		{
			// We shouldn't write this sitemap
			return true;
		}

		$filename_suffix = $this->session['current_content'] . '_' . count($this->session['sitemaps']);
		$this->written_filename = $sitemap_obj->get_sitemap_filename_prefix() . "{$filename_suffix}.xml";

		if ($filename = $sitemap_obj->create_sitemap($filename_suffix))
		{
			$this->session['sitemaps'][] = array('loc' => $filename, 'lastmod' => TIMENOW);
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	* Determines if the sitemap generation is finished. This manipulates the session
	* and helps prepare for the next page.
	*
	* @param	DBSEO_SiteMap	Current sitemap object
	*
	* @return	boolean
	*/
	protected function is_finished($sitemap_obj)
	{
		$has_more = $sitemap_obj->has_more();
		if ($has_more === null)
		{
			// has_more wasn't definitive; use default handler
			$has_more = ($sitemap_obj->get_pagecount() == $this->registry->options['dbtech_dbseo_sitemap_url_perpage']);
		}

		if ($has_more)
		{
			$this->session['startat'] = $sitemap_obj->get_lastid() + 1;
			return false;
		}
		else
		{
			$this->step_content_type_forward();
			return (count($this->session['types']) == 0);
		}
	}

	/**
	* Moves forward to the next content type.
	*/
	protected function step_content_type_forward()
	{
		array_shift($this->session['types']);

		$this->session['current_content'] = reset($this->session['types']);
		$this->session['startat'] = 0;

		($hook = vBulletinHook::fetch_hook('dbtech_dbseo_sitemap_next_content_type') OR $hook = $this->registry->pluginlist['dbtech_dbseo_sitemap_next_content_type']) ? eval($hook) : false;
	}

	/**
	* Finalizes the sitemap build by writing an index and contacting the
	* selected search engines.
	*
	* @param	DBSEO_SiteMap	Sitemap object
	*
	* @return	boolean
	*/
	protected function finalize($sitemap_obj)
	{
		global $vbphrase;

		if ($sitemap_obj)
		{
			// Ensure all sitemaps together (possibly existing sitemap index file)
			$sitemap_obj->set_sitemap_index(array_merge($sitemap_obj->get_sitemap_index(), $this->session['sitemaps']));

			// Create the sitemap index file and write it out
			if (!$sitemap_obj->create_sitemap_index())
			{
				$this->trigger_failure('dbtech_dbseo_sitemap_creation_failed');
				return false;
			}

			$sitemap_obj->ping_search_engines();

			// This means the upgrade script has ran, but the details weren't filled in
			if (!$prevBuild = $this->dbobject->query_first_slave("
				SELECT builddetails
				FROM " . TABLE_PREFIX . "dbtech_dbseo_sitemapbuildlog
				ORDER BY sitemapbuildlogid DESC
				LIMIT 1
			"))
			{
				$this->session['prevcount'] = array();
				foreach ($log['builddetails'] as $contenttype => $numUrls)
				{
					// Default to 0
					$this->session['prevcount'][$contenttype] = 0;
				}
			}
			else
			{
				// Make sure this is an array
				$this->session['prevcount'] = @unserialize($prevBuild['builddetails']);
				$this->session['prevcount'] = is_array($this->session['prevcount']) ? $this->session['prevcount'] : array();
			}

			// Insert build log
			$this->dbobject->query_write("
				INSERT INTO " . TABLE_PREFIX . "dbtech_dbseo_sitemapbuildlog
					(dateline, builddetails, prevbuilddetails)
				VALUES (
					" . TIMENOW . ",
					" . $this->dbobject->sql_prepare(trim(serialize($this->session['count']))) . ",
					" . $this->dbobject->sql_prepare(trim(serialize($this->session['prevcount']))) . "
				)
			");

			if ($this->registry->options['dbtech_dbseo_sitemap_email'])
			{
				$contenttypes = array();
				$totals = array('current' => 0, 'prev' => 0);

				foreach ($this->session['count'] as $contenttype => $numUrls)
				{
					// Make sure this is set
					$this->session['prevcount'][$contenttype] = isset($this->session['prevcount'][$contenttype]) ? $this->session['prevcount'][$contenttype] : 0;

					// Calculate difference
					$difference = $numUrls - $this->session['prevcount'][$contenttype];

					$contenttypes[$contenttype] = (
						$vbphrase['dbtech_dbseo_contenttype_' . $contenttype] ?
						$vbphrase['dbtech_dbseo_contenttype_' . $contenttype] :
						'dbtech_dbseo_contenttype_' . $contenttype) .
					': ' . vb_number_format($numUrls, 0) . " - " . ($difference >= 0 ? '+' : '') . vb_number_format($difference, 0);

					$totals['current'] += $numUrls;
					$totals['prev'] += $this->session['prevcount'][$contenttype];
				}

				foreach ($this->session['prevcount'] as $contenttype => $numUrls)
				{
					if (isset($contenttypes[$contenttype]))
					{
						// Skip this
						continue;
					}

					$contenttypes[$contenttype] = (
						$vbphrase['dbtech_dbseo_contenttype_' . $contenttype] ?
						$vbphrase['dbtech_dbseo_contenttype_' . $contenttype] :
						'dbtech_dbseo_contenttype_' . $contenttype) .
					": 0 - -" . vb_number_format($numUrls, 0);

					$totals['prev'] += $numUrls;
				}

				// Calculate difference
				$difference = $totals['current'] - $totals['prev'];

				$contenttypes[] = $vbphrase['total'] . ': ' . vb_number_format($totals['current'], 0) . " - " . ($difference >= 0 ? '+' : '') . vb_number_format($difference, 0) . "\n";
				$contenttypes = implode("\n", $contenttypes);

				eval(fetch_email_phrases('dbtech_dbseo_sitemap_generation_report'));

				require_once(DIR . '/includes/class_bbcode_alt.php');
				$plaintext_parser = new vB_BbCodeParser_PlainText($this->registry, fetch_tag_list());
				$plaintext_parser->set_parsing_language(0); // email addresses don't have a language ID
				$message = $plaintext_parser->parse($message, 'privatemessage');
				vbmail($this->registry->options['dbtech_dbseo_sitemap_email'], $subject, $message, true);
			}
		}

		$this->dbobject->query_write("DELETE FROM " . TABLE_PREFIX . "adminutil WHERE title = 'dbtech_dbseo_sitemapsession'");

		return true;
	}

	/**
	* Prepares for the next page. This is only called when the build isn't finished.
	*
	* @return	boolean
	*/
	protected function prepare_next_page()
	{
		$this->write_session();

		return true;
	}

	/**
	* Writes the sitemap session out. Only needed when not finished.
	*/
	protected function write_session()
	{
		$this->dbobject->query_write("
			REPLACE INTO " . TABLE_PREFIX . "adminutil
				(title, text)
			VALUES
				('dbtech_dbseo_sitemapsession', '" . $this->dbobject->escape_string(serialize($this->session)) . "')
		");
	}

	/**
	* Triggers a failure event. This prevents the sitemap from being built
	* any further until the error is resolved. Calling this updates
	* the sitemap session automatically.
	*
	* @param	string	Phrase key (in "error messages") that describes the error
	*/
	protected function trigger_failure($error_phrase)
	{
		$this->session['state'] = 'failed';
		$this->session['failure_reason'] = $error_phrase;
		$this->write_session();
	}
}

/**
* Sitemap runner that uses cron-specific checks and triggers.
*
* @package	vBulletin
*/
class DBSEO_SiteMapRunner_Cron extends DBSEO_SiteMapRunner
{
	/**
	* Information about the cron item that triggers the sitemap builds.
	*
	* @var	array
	*/
	protected $cron_item = array();

	/**
	* Information about the cron-triggered sitemap builds (particularly last build time).
	*
	* @var	array
	*/
	protected $build_info = array();

	/**
	* Constructor. Fetches session (via parent) and populates build_info.
	*
	* @param	vB_Registry
	*/
	public function __construct(vB_Registry $registry)
	{
		require_once(DIR . '/includes/functions_cron.php');

		parent::__construct($registry);

		if ($build_info = $registry->db->query_first_slave("
			SELECT text
			FROM " . TABLE_PREFIX . "adminutil
			WHERE title = 'dbseositemapcronbuild'
		"))
		{
			$this->build_info = unserialize($build_info['text']);
		}
	}

	/**
	* Sets the cron item property.
	*
	* @param	array	Cron item info
	*/
	public function set_cron_item(array $cron_item)
	{
		$this->cron_item = $cron_item;
	}

	/**
	* Internal function for checking the environment. Checks cron-specific items
	* like being enabled and the last build time.
	*
	* @return	array	Array of status info. Check 'error' key.
	*/
	protected function check_environment_internal()
	{
		$status = parent::check_environment_internal();

		if ($this->session['state'] == 'running_admincp')
		{
			$status['error'] = 'dbtech_dbseo_sitemap_currently_generated_admincp';
			$status['loggable'] = false;
		}

		if (VB_AREA != 'Maintenance')
		{
			if (!$this->registry->options['dbtech_dbseo_sitemap_cron_enable'])
			{
				$status['error'] = 'dbtech_dbseo_sitemap_cron_option_not_enabled';
				$status['loggable']	= false;
			}

			if ($this->session['state'] == 'start'
				AND $this->build_info
				AND $this->build_info['last_build'] > (TIMENOW - $this->registry->options['dbtech_dbseo_sitemap_cron_frequency'] * 86400))
			{
				$status['error'] = 'dbtech_dbseo_sitemap_cron_build_not_scheduled';
				$status['loggable']	= false;
			}

			if ($this->session['state'] == 'start'
				AND date('G') != $this->registry->options['dbtech_dbseo_sitemap_runtime'])
			{
				$status['error'] = 'dbtech_dbseo_sitemap_cron_build_not_scheduled';
				$status['loggable']	= false;
			}
		}

		return $status;
	}

	/**
	* Sets the session state to running and updates the last build time if necessary.
	*/
	protected function set_state()
	{
		if ($this->session['state'] == 'start')
		{
			$this->build_info['last_build'] = TIMENOW;

			$this->dbobject->query_write("
				REPLACE INTO " . TABLE_PREFIX . "adminutil
					(title, text)
				VALUES
					('dbseositemapcronbuild',
					'" . $this->dbobject->escape_string(serialize($this->build_info)) . "')
			");
		}
		$this->session['state'] = 'running_cron';
	}

	/**
	* Prepares for the next "page" of building. Handles parent functions and
	* updates the cron to run almost immediately (to allow a multi-page build
	* to be completed quickly.
	*
	* @return	boolean
	*/
	protected function prepare_next_page()
	{
		if (!parent::prepare_next_page())
		{
			return false;
		}

		if ($this->cron_item)
		{
			// if we have more to do, run the next step in approximately a minute
			$this->dbobject->query_write("UPDATE " . TABLE_PREFIX . "cron SET nextrun = " . (TIMENOW + 60) . " WHERE cronid = " . intval($this->cron_item['cronid']));
			build_cron_next_run(TIMENOW + 60);
		}

		return true;
	}
}

/**
* Admin CP-based sitemap build helper.
*
* @package	vBulletin
*/
class DBSEO_SiteMapRunner_Admin extends DBSEO_SiteMapRunner
{
	/**
	* Internal function for checking the environment. Checks ACP-specific items
	* like whether the sitemap is being built by cron.
	*
	* @return	array	Array of status info. Check 'error' key.
	*/
	protected function check_environment_internal()
	{
		$status = parent::check_environment_internal();

		if ($this->session['state'] == 'running_cron')
		{
			$status['error'] = 'dbtech_dbseo_sitemap_running_cron';
		}

		return $status;
	}

	/**
	* Sets session state to running.
	*/
	protected function set_state()
	{
		$this->session['state'] = 'running_admincp';
	}
}

/**
* Abstract class to construct sitemap files and the index file. Must be subclassed for specific content types.
*
* @package	vBulletin
*/
abstract class DBSEO_SiteMap
{
	/**
	* The last id of the content, for per_page
	*
	* @var	int
	*/
	protected $lastid = 0;

	/**
	* An array of custom Forum priorities forumid => priority
	*
	* @var	array
	*/
	protected $forum_custom_priority = array();

	/**
	 * An array of custom priorities contenttype => forumid => priority
	 *
	 * @var	array
	 */
	protected $custom_priority = array();

	/**
	* The vBulletin registry object
	*
	* @var	vB_Registry
	*/
	protected $registry = null;

	/**
	* The vBulletin database object
	*
	* @var	vB_Database
	*/
	protected $dbobject = null;

	/**
	* A vB_XML_Parser database object
	*
	* @var	vB_XML_Parser
	*/
	protected $xmlobject = null;

	/**
	* String to save the content of the sitemap while being generated before being written out
	*
	* @var	string
	*/
	protected $content = '';

	/**
	* Counter for the numbers of URLs added to the current sitemap content
	*
	* @var	int
	*/
	protected $pagecount = 0;

	/**
	* Determines if there is more of this content type to process.
	*
	* @var	boolean|null	Null is unknown, boolean otherwise
	*/
	protected $has_more = null;

	/**
	* Array to store any errors encountered while building data
	*
	* @var	array
	*/
	protected $errors = array();

	/**
	* Array to store currently generated (or listed) site maps. Used to generate sitemap index file (the master one). ['loc'] && ['lastmod']
	*
	* @var	array
	*/
	protected $sitemap_index = array();

	/**
	* Default name for sitemap_index file
	*
	* @var	string
	*/
	private $sitemap_index_filename = 'dbseo_sitemap_index';


	/**
	* Default name for sitemap files, which is prepended by the sitemap file count
	*
	* @var	string
	*/
	private $sitemap_filename_prefix = 'dbseo_sitemap_';

	const FLAG_PING_GOOGLE      = 0x1;
	const FLAG_PING_LIVE_SEARCH = 0x2;
	const FLAG_PING_YAHOO       = 0x4;
	const FLAG_PING_ASK         = 0x8;
	const FLAG_PING_MOREOVER    = 0x10;
	const FLAG_PING_BING    	= 0x20;

	/**
	* Array of search engine urls' for sitemap call back, populated with defaults from options
	*
	* @var 	array
	*/
	public $search_engines = array(
		self::FLAG_PING_GOOGLE      => 'http://www.google.com/webmasters/sitemaps/ping?sitemap=',
		//self::FLAG_PING_LIVE_SEARCH => 'http://webmaster.live.com/ping.aspx?siteMap=',
		self::FLAG_PING_YAHOO       => 'http://search.yahooapis.com/SiteExplorerService/V1/ping?sitemap=',
		self::FLAG_PING_ASK         => 'http://submissions.ask.com/ping?sitemap=',
		self::FLAG_PING_MOREOVER    => 'http://api.moreover.com/ping?u=',
		self::FLAG_PING_BING    	=> 'http://www.bing.com/ping?sitemap=',
	);


	/**
	* Constructor - checks that the registry object has been passed correctly.
	*
	* @param	vB_Registry		Instance of the vBulletin data registry object - expected to have the database object as one of its $this->db member.
	*/
	function __construct(vB_Registry $registry)
	{
		$this->dbobject = $registry->db;
		$this->registry = $registry;

		if (intval($this->registry->versionnumber) == 4 AND !class_exists('vB_Types'))
		{
			require_once(DIR . '/includes/class_bootstrap_framework.php');
			require_once(DIR . '/vb/types.php');
			vB_Bootstrap_Framework::init();
		}

		($hook = vBulletinHook::fetch_hook('dbtech_dbseo_sitemap_generate_start') OR $hook = $this->registry->pluginlist['dbtech_dbseo_sitemap_generate_start']) ? eval($hook) : false;
	}

	### abstract ###

	/**
	* This function will generate the actual sitemap content.
	*/
	abstract protected function generate_sitemap();

	### Main ###

	/**
	* Gets the sitemap filename prefix
	*
	* @return	string
	*/
	function get_sitemap_filename_prefix()
	{
		return $this->sitemap_filename_prefix;
	}

	/**
	* Gets the content for the sitemap
	*
	* @return	array
	*/
	function get_content()
	{
		return $this->content;
	}

	/**
	* Gets the current data that will be used to build the sitemap index
	*
	* @return	array
	*/
	function get_sitemap_index()
	{
		return $this->sitemap_index;
	}

	/**
	* Sets the current data that will be used to build the sitemap index
	*
	* @param	array
	*/
	function set_sitemap_index($value)
	{
		$this->sitemap_index = $value;
	}

	/**
	* Returns indicator for whether there's more of this content to be processed.
	* Useful for the case where there is exactly the "per page" pieces of content.
	*
	* @return	boolean|null	Null is unknown, booling otherwise
	*/
	function has_more()
	{
		return $this->has_more;
	}

	/**
	 * Accessor - place holder
	 *
	 * @param 	int		The forum id to retrive custom priority for
	 * @return 	mixed	False or int
	 */
	public function get_forum_custom_priority($id)
	{
		if (isset($this->forum_custom_priority[$id]))
		{
			return $this->forum_custom_priority[$id];
		}

		return false;
	}

	/**
	* Returns the effective priority for a forum
	*
	* @param	integer	Forum ID
	*
	* @return	float	Usable priority
	*/
	public function get_effective_forum_priority($forumid)
	{
		return (isset($this->forum_custom_priority[$forumid])
			? $this->forum_custom_priority[$forumid]
			: $this->registry->options['sitemap_priority']
		);
	}

	/**
	 * Returns the effective priority for a non-forum type
	 *
	 * @param 	string	content type
	 * @param	integer	content ID
	 *
	 * @return	float	Usable priority
	 */
	public function get_effective_priority($contenttypeid, $contentid)
	{

		return(isset($this->custom_priority[$contenttypeid][$contentid])
			AND isset($this->custom_priority[$contenttypeid][$contentid]['priority'])
			AND ($this->custom_priority[$contenttypeid][$contentid]['priority'] !== false))
			? $this->custom_priority[$contenttypeid][$contentid]['priority']
			: $this->registry->options['sitemap_priority'];
	}

	/**
	 * Returns the priority for a given range
	 *
	 * @param 	string	content type
	 * @param	integer	current priority value
	 *
	 * @return	float	Usable priority
	 */
	public function getPriority($range, $value, $override = false)
	{
		// Grab the min/max of our range
		list($min, $max) = explode('-', ($override ? $override : $this->registry->options['dbtech_dbseo_sitemap_priority_' . $range]));

		if (!$max OR !$this->registry->options['dbtech_dbseo_sitemap_priority_smart'])
		{
			return $min;
		}

		return intval(($min + (min(1, max($value, 0))) * ($max - $min)) * 10000) / 10000;
	}

	/**
	 * Gets the average weight based on parameters
	 *
	 * @param	integer	weight
	 * @param	integer	minimum value
	 * @param	integer	maximum value
	 * @param	integer	average value
	 *
	 * @return	float	Usable weight
	 */
	public function getAvgWeight($value, $min, $max, $avg)
	{
		if ($value > $avg)
		{
			return (($max - $avg) > 0 ? ($value - $avg) / ($max - $avg) * 0.5 : 0) + 0.5;
		}
		else
		{
			return $avg > 0 ? ($avg - $value) * 0.5 / $avg : 0;
		}
	}

	/**
	 * Accessor
	 *
	 * @return int
	 */
	function get_pagecount()
	{
		return $this->pagecount;
	}


	/**
	 * Accessor
	 *
	 * @return int
	 */
	function get_lastid()
	{
		return $this->lastid;
	}


	/**
	* Write out the sitemap index file using sitemap file refrences in $this->sitemap_index
	*
	* @param	array
	*
	* @return	null		cron log
	*/
	final function create_sitemap_index()
	{
		$content = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

		foreach ($this->sitemap_index AS $sitemap)
		{
			$content .= "\n" . '<sitemap>';
			$content .= "\n\t" . '<loc>' . htmlspecialchars_uni($this->registry->options['bburl'] . '/dbseositemap.php?fn=' . urlencode($sitemap['loc'])) . '</loc>';
			$content .= "\n\t" . '<lastmod>' . gmdate(DATE_W3C, $sitemap['lastmod']) . '</lastmod>';
			$content .= "\n" . '</sitemap>';
		}

		$content .= "\n" . '</sitemapindex>';

		// Compress and add extension
		if (function_exists('gzencode'))
		{
			$content = gzencode($content);
			$output_filename = $this->sitemap_index_filename . '.xml.gz';
		}
		else
		{
			$output_filename = $this->sitemap_index_filename . '.xml';
		}

		// Try to write file
		if ($fp = @fopen($this->registry->options['dbtech_dbseo_sitemap_path'] . '/' . $output_filename, 'w'))
		{
			fwrite($fp, $content);
			fclose($fp);
			return true;
		}
		else
		{
			$this->errors[] = 'Error writing : ' . $this->registry->options['dbtech_dbseo_sitemap_path'] . '/' . $output_filename;
			return false;
		}
	}


	/**
	* Build the actual file for the sitemap
	*
	* @return boolean		completion state
	*/
	final function create_sitemap($filename)
	{
		$this->content =
			'<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
			'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' .
				$this->content .
			'</urlset>';

		// Next file name
		$new_file = $this->sitemap_filename_prefix . $filename;

		// Compress and add extension
		if (function_exists('gzencode'))
		{
			$content = gzencode($this->content);
			$new_file .= '.xml.gz';
		}
		else
		{
			$content = $this->content;
			$new_file .= '.xml';
		}

		// Create it and write it all out
		if ($fp = @fopen($this->registry->options['dbtech_dbseo_sitemap_path'] . '/' . $new_file, 'w'))
		{
			fwrite($fp, $content);
			fclose($fp);
			return $new_file;
		}
		else
		{
			$this->errors[] = 'Error writing : ' . $this->registry->options['dbtech_dbseo_sitemap_path'] . '/' . $new_file;
			return false;
		}
	}


	/**
	* Create a url XML text block for one URL
	*
	* @param	string		The URL (not encoded) to add to the sitemap
	* @param 	int			The unix timestamp of the last modifictaion time of the data in UTC
	* @param 	double		The priority of the data from 0.1 to 1.0
	* @param 	boolean		Enable formatting of the output
	*
	* @return	string		Formatted escaped <url> wrapped text
	*/
	protected function url_block($url, $lastmod, $pri = false, $changefreq = '', $formatting = false)
	{
		$l = "\n" . ($formatting ? "\t\t" : '');

		// Start block
		$data .= "\n" . ($formatting ? "\t" : '') . '<url>';

		$url_info = $this->parse_url($url);
		if(empty($url_info['scheme']) AND empty($url_info['host']))
		{
			$url = $this->registry->options['bburl'] . '/' . $url;
		}

		$data .= $l . '<loc>' . htmlspecialchars_uni(substr($url, 0, 2048)) . '</loc>';
		$data .= $l . '<lastmod>' . htmlspecialchars_uni(gmdate(DATE_W3C, $lastmod)) . '</lastmod>';

		if (!$changefreq)
		{
			if ($lastmod + 600 >= TIMENOW) // 10 mins
			{
				$changefreq = 'always';
			}
			else if ($lastmod + 3600 >= TIMENOW) // 1 hour
			{
				$changefreq = 'hourly';
			}
			else if ($lastmod + 86400 >= TIMENOW) // 1 day
			{
				$changefreq = 'daily';
			}
			else if ($lastmod + 604800 >= TIMENOW) // 1 week
			{
				$changefreq = 'weekly';
			}
			else if ($lastmod + 2629743 >= TIMENOW) // 1 month
			{
				$changefreq = 'monthly';
			}
			else     // Yearly, for yearly and in place of never
			{
				$changefreq = 'yearly';
			}
		}

		$data .= $l .'<changefreq>' . $changefreq . '</changefreq>';

		if ($pri !== false)
		{
			$data .= $l .'<priority>' . floatval($pri) . '</priority>';
		}

		$data .= "\n" . ($formatting ? "\t" : '')  . '</url>';

		return $data;
	}


	/**
	* Delete all sitemaps named : '*_sitemap.xml' '*_sitemap.xml.gz' 'sitemap_index.xml'
	*
	* @return	boolean		FALSE on any fails
	*/
	final function remove_sitemaps()
	{
		$path = $this->registry->options['dbtech_dbseo_sitemap_path'];
		$success = true;

		$all = scandir($path);
		foreach ($all AS $filename)
		{
			$is_index_file = (
				$filename == $this->sitemap_index_filename . '.xml'
				OR $filename == $this->sitemap_index_filename . '.xml.gz'
			);

			$is_sitemap_file = (
				substr($filename, 0, strlen($this->sitemap_filename_prefix)) == $this->sitemap_filename_prefix
				AND (substr($filename, -4) == '.xml' OR substr($filename, -7) == '.xml.gz')
			);

			if ($is_index_file OR $is_sitemap_file)
			{
				if (!@unlink("$path/$filename"))
				{
					$this->errors[] = "No Permission to delete sitemap : {$path}/{$filename}";
					$success = false;
				}

			}
		}

		return $success;
	}

	/*DBTECH_PRO_START*/
	/**
	* Imports custom sitemap URLs from the specified file
	*
	* @return	boolean		FALSE on any fails
	*/
	final function import_custom_urls()
	{
		$path = $this->registry->options['dbtech_dbseo_sitemap_autoimport'];

		if (file_exists($path) AND is_readable($path))
		{
			// got an uploaded file?
			$urls = preg_split('#[\r\n]+#', file_get_contents($path));
		}
		else
		{
			return false;
		}

		$urlsToCheck = $urlsToImport = array();
		foreach ($urls as $url)
		{
			// Store some arrays
			$urlsToCheck[$url] = "'" . $this->dbobject->escape_string($url) . "'";
			$urlsToImport[$url] = "(
				'" . $this->dbobject->escape_string($url) . "',
				'" . $this->dbobject->escape_string($this->registry->options['dbtech_dbseo_sitemap_frequency_autoimport']) . "',
				'" . $this->dbobject->escape_string($this->registry->options['dbtech_dbseo_sitemap_priority_autoimport']) . "',
				'" . TIMENOW . "'
			)";
		}

		if (count($urlsToCheck))
		{
			// Check for existing urls
			$foundUrls = $this->dbobject->query_read_slave("
				SELECT url
				FROM " . TABLE_PREFIX . "dbtech_dbseo_sitemapurl
				WHERE url IN(" . implode(',', $urlsToCheck) . ")
			");
			while ($foundUrl = $this->dbobject->fetch_array($foundUrls))
			{
				// Already had this URL
				unset($urlsToImport[$foundUrl['url']]);
			}
		}

		if (count($urlsToImport))
		{
			// We had some new ones to add
			$this->dbobject->query_write("
				INSERT INTO " . TABLE_PREFIX . "dbtech_dbseo_sitemapurl
					(url, frequency, priority, lastupdate)
				VALUES " . implode(',', $urlsToImport) . "
			");
		}

		return true;
	}
	/*DBTECH_PRO_END*/


	/**
	* Ping the search engines
	* @param 	object		A vB_vURL object
	*
	* @return	none		A blind call, no return currently parsed
	*/
	public function ping_search_engines()
	{
		if (!$this->registry->options['dbtech_dbseo_sitemap_se_submit'])
		{
			// value of 0 in bitfield means all search engines are disabled
			return;
		}

		require_once(DIR . '/includes/class_vurl.php');
		$vurl = new vB_vURL($this->registry);
		$vurl->set_option(VURL_HEADER, true);
		$vurl->set_option(VURL_RETURNTRANSFER, true);

		$map_url = urlencode($this->registry->options['bburl'] . "/dbseositemap.php");

		foreach ($this->search_engines as $bit_option => $callback_url)
		{
			if ($this->registry->options['dbtech_dbseo_sitemap_se_submit'] & $bit_option)
			{
				$vurl->set_option(VURL_URL, $callback_url . $map_url);
				$res = $vurl->exec();
			}
		}
	}

	/**
	* Builds the very basic array for the guest viewable forums
	*
	* @param 	bool	Whether the permission to view threads should be checked
	*/
	public static function get_allowed_forums($check_thread_view = true)
	{
		global $vbulletin;

		$guestuser = array(
			'userid'      => 0,
			'usergroupid' => 1,
		);

		cache_permissions($guestuser);

		if (!($guestuser['permissions']['forumpermissions'] & $vbulletin->bf_ugp_forumpermissions['canview']))
		{
			return array();
		}

		$viewable_forums = $excluded_forums = array();

		if (trim($vbulletin->options['dbtech_dbseo_sitemap_excludedforums']) != '')
		{
			// Grab our forum IDs
			$excluded_forums = explode(' ', $vbulletin->options['dbtech_dbseo_sitemap_excludedforums']);
		}

		foreach ($vbulletin->forumcache AS $forum)
		{
			$forumperms = $guestuser['forumpermissions']["$forum[forumid]"];

			if (!$forum['password']
				AND ($forumperms & $vbulletin->bf_ugp_forumpermissions['canview'])
				AND ($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers'])
				AND (!$check_thread_view OR ($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads']))
				AND !in_array($forum['forumid'], $excluded_forums)
			)
			{
				$viewable_forums[] = intval($forum['forumid']);
			}
		}

		return $viewable_forums;
	}


	/**
	* Builds the forum priority array
	*
	*/
	protected function set_forum_priorities()
	{
		global $vbulletin;

		if ($vbulletin->options['dbtech_dbseo_sitemap_priority_custom'])
		{
			$forum_priorities = $this->dbobject->query_read_slave("SELECT sourceid, prioritylevel FROM " . TABLE_PREFIX . "contentpriority WHERE contenttypeid = 'forum'");

			while ($f_pri = $this->dbobject->fetch_array($forum_priorities))
			{
				$this->forum_custom_priority["$f_pri[sourceid]"] = $f_pri['prioritylevel'];
			}
			$this->dbobject->free_result($forum_priorities);
			unset($f_pri);
		}
	}

	/**
	 * Builds the priority array for an arbitrary content type
	 *
	 */
	protected function set_priorities($contenttype)
	{
		if ($vbulletin->options['dbtech_dbseo_sitemap_priority_custom'])
		{
			$forum_priorities = $this->dbobject->query_read_slave("SELECT sourceid, prioritylevel FROM " . TABLE_PREFIX . "contentpriority WHERE contenttypeid = '$contenttype'");

			if (!isset($this->custom_priority[$contenttype]))
			{
				$this->custom_priority[$contenttype] = array();
			}

			while ($f_pri = $this->dbobject->fetch_array($forum_priorities))
			{
				if (!isset($this->custom_priority[$contenttype][$f_pri['sourceid']]))
				{
					$this->custom_priority[$contenttype][$f_pri['sourceid']] = array();
				}
				$this->custom_priority[$contenttype][$f_pri['sourceid']]['priority'] = $f_pri['prioritylevel'];
			}
			$this->dbobject->free_result($forum_priorities);
			unset($f_pri);
		}
	}

	/**
	 * returns an array of priorities
	 *
	 * @param	string	contenttype
	 *
	 * @return array		$key => priority
	 */
	public function get_priorities($contenttype)
	{
		if (isset($this->custom_priority[$contenttype]))
		{
			return $this->custom_priority[$contenttype];
		}
		return array();
	}

	/**
	*	Workaround for a UTF8 compatible parse_url
	*/
	protected function parse_url($url, $component = -1)
	{
		// Taken from /rfc3986#section-2
		$safechars =array(':', '/', '?', '#', '[', ']', '@', '!', '$', '&', '\'' ,'(', ')', '*', '+', ',', ';', '=');
		$trans = array('%3A', '%2F', '%3F', '%23', '%5B', '%5D', '%40', '%21', '%24', '%26', '%27', '%28', '%29', '%2A', '%2B', '%2C', '%3B', '%3D');
		$encodedurl = str_replace($trans, $safechars, urlencode($url));

		$parsed = @parse_url($encodedurl, $component);
		if(is_array($parsed))
		{
			foreach ($parsed AS $index => $element)
			{
				$parsed[$index] = urldecode($element);
			}
		}
		else
		{
			$parsed = urldecode($parsed);
		}

		return $parsed;
	}
}

/**
* Specific class for generating forum-related sitemaps
*
* @package	vBulletin
*/
class DBSEO_SiteMap_Forum extends DBSEO_SiteMap
{
	/**
	* Constructor - checks that the registry object has been passed correctly.
	*
	* @param	vB_Registry		Instance of the vBulletin data registry object - expected to have the database object as one of its $this->db member.
	* @param 	vB_XML_Parser 	Instance of the vBulletin XML parser.
	*/
	function __construct(vB_Registry $registry, vB_XML_Parser $xml_handler = null)
	{
		parent::__construct($registry, $xml_handler);

		$this->set_forum_priorities();

	}

	/**
	* Adds the URLs to $this->content
	*
	* @param 	int		forumdid to start at
	* @param 	int		perpage limit defaults to 30000
	*/
	public function generate_sitemap($startat = 0, $perpage = 30000)
	{
		if ($startat == 0)
		{
			$this->pagecount++;
			$this->content .= $this->url_block(
				$this->registry->options['bburl'] . '/',
				0,
				$this->getPriority('forumdisplay', 1.0),
				$this->registry->options['dbtech_dbseo_sitemap_frequency_forumdisplay']
			);
		}

		$viewable_forums = DBSEO_SiteMap::get_allowed_forums(false);
		if (!$viewable_forums)
		{
			return $this->pagecount;
		}

		$forumstats = $this->dbobject->query_first_slave("
			SELECT
				MAX(threadcount) AS maxreplies,
				MIN(threadcount) AS minreplies,
				AVG(threadcount) AS avgreplies
			FROM " . TABLE_PREFIX . "forum
		");

		$foruminfo = array();
		$threadinfo = $this->dbobject->query_read_slave("
			SELECT thread.forumid, COUNT(*) AS threads
			FROM " . TABLE_PREFIX . "thread AS thread
			LEFT JOIN " . TABLE_PREFIX . "forum AS forum USING(forumid)
			WHERE thread.sticky = 0
				AND thread.visible = 1
				AND thread.lastpost >= IF(forum.daysprune > 0, (UNIX_TIMESTAMP() - (forum.daysprune * 86400)), 0)
				AND thread.forumid IN (" . implode(',', $viewable_forums) . ")
			GROUP BY thread.forumid
		");
		while ($info = $this->dbobject->fetch_array($threadinfo))
		{
			// Store the forum info
			$foruminfo[$info['forumid']] = $info;
		}
		$this->dbobject->free_result($threadinfo);
		unset($info);

		$forums = $this->dbobject->query_read_slave("
			SELECT forumid, title, title_clean, lastpost, threadcount
			FROM " . TABLE_PREFIX . "forum
			WHERE forumid IN (" . implode(',', $viewable_forums) . ")
				AND forumid >= " . intval($startat) . " AND link = ''
			ORDER BY forumid
			LIMIT " . intval($perpage + 1) // for has_more check
		);

		$this->has_more = false;

		while ($forum = $this->dbobject->fetch_array($forums))
		{
			if ($this->pagecount >= $perpage)
			{
				$this->has_more = true;
				break;
			}

			// Store the last ID processed
			$this->lastid = $forum['forumid'];

			// Save some performance
			DBSEO::$cache['forum'][$forum['forumid']] = $forum;

			$totalpages = max(ceil($foruminfo[$forum['forumid']]['threads'] / $this->registry->options['maxthreads']), 1);
			for ($page = 1; $page <= $totalpages; $page++)
			{
				// Increment page counter
				$this->pagecount++;

				// Calculate the priority
				$priority = (
					($this->getAvgWeight($forum['threadcount'], $forumstats['minreplies'], $forumstats['maxreplies'], $forumstats['avgreplies']) * 0.8) +
					((($totalpages + 1 - $page) / $totalpages) * 0.2)
				) * $this->get_effective_forum_priority($forum['forumid']);

				$forum['page'] = $page;
				$this->content .= $this->url_block(
					$this->createUrl($forum, $page),
					$forum['lastpost'],
					$this->registry->options['dbtech_dbseo_sitemap_priority_custom'] ? $this->get_effective_forum_priority($thread['forumid']) : $this->getPriority('forumdisplay', $priority),
					$this->registry->options['dbtech_dbseo_sitemap_frequency_forumdisplay']
				);
			}
		}
		$this->dbobject->free_result($forums);
		unset($forum);

		// Return the amout done
		return $this->pagecount;
	}

	/**
	* Creates the URL based on parameters
	*
	* @param 	array	info to work with
	* @param 	int		page we're dealing with
	*/
	private function createUrl($forum, $page)
	{
		if (DBSEO::$config['dbtech_dbseo_rewrite_forum'])
		{
			return DBSEO_Url_Create::create('Forum_Forum' . ($page > 1 ? '_Page' : ''), $forum);
		}
		else
		{
			return intval($this->registry->versionnumber) == 4 ?
				unhtmlspecialchars(fetch_seo_url('forum|bburl|nosession', $forum, ($page > 1) ? array('page' => $page) : array())) :
				$this->registry->options['bburl'] . '/forumdisplay.php?f=' . $forum['forumid'] . ($page > 1 ? '&page=' . $page : '');
		}
	}
}

/**
* Specific class for generating thread-related sitemaps
*
* @package	vBulletin
*/
class DBSEO_SiteMap_Thread extends DBSEO_SiteMap
{
	/**
	* Constructor - checks that the registry object has been passed correctly.
	*
	* @param	vB_Registry		Instance of the vBulletin data registry object - expected to have the database object as one of its $this->db member.
	* @param 	vB_XML_Parser 	Instance of the vBulletin XML parser.
	*/
	function __construct(vB_Registry $registry, vB_XML_Parser $xml_handler = null)
	{
		parent::__construct($registry, $xml_handler);

		$this->set_forum_priorities();
	}

	/**
	* Adds the thread URLs to $this->content
	*
	* @param 	int		threadid to start at
	* @param 	int		perpage limit defaults to 30000
	*/
	public function generate_sitemap($startat = 0, $perpage = 30000)
	{
		$viewable_forums = DBSEO_SiteMap::get_allowed_forums(true);
		if (!$viewable_forums)
		{
			return $this->pagecount;
		}

		require_once(DIR . '/includes/functions_bigthree.php');
		$coventry = fetch_coventry('array', true);

		$foruminfo = array();
		$threadinfo = $this->dbobject->query_read_slave("
			SELECT
				forumid,
				COUNT(*) AS numthreads,
				MAX(views) AS maxviews,
				AVG(views) AS avgviews,
				MAX(replycount) AS maxreplies,
				AVG(replycount) AS avgreplies
			FROM " . TABLE_PREFIX . "thread
			WHERE visible = 1
			GROUP BY forumid
		");
		while ($info = $this->dbobject->fetch_array($threadinfo))
		{
			// Store the forum info
			$foruminfo[$info['forumid']] = $info;
		}
		$this->dbobject->free_result($threadinfo);
		unset($info);

		$threads = $this->dbobject->query_read_slave("
			SELECT threadid, forumid, title, lastpost, votenum, votetotal, views, replycount
			FROM " . TABLE_PREFIX . "thread
			WHERE forumid IN (" . implode(',', $viewable_forums) . ")
				AND visible = 1
				AND open <> 10
				" . ($coventry ? "AND postuserid NOT IN (" . implode(',', $coventry) . ")" : '') . "
				AND threadid >= " . intval($startat) . "
			ORDER BY threadid
			LIMIT " . intval($perpage + 1) // + 1 for has_more check
		);

		$this->has_more = false;

		while ($thread = $this->dbobject->fetch_array($threads))
		{
			if ($this->pagecount >= $perpage)
			{
				$this->has_more = true;
				break;
			}

			// Set our last thread ID
			$this->lastid = $thread['threadid'];

			// Default priority
			$priority = $this->get_effective_forum_priority($thread['forumid']);

			if ($this->registry->options['dbtech_dbseo_sitemap_priority_smart'])
			{
				if ($thread['sticky'])
				{
					// Always high priority
					$priority = 1;
				}
				else
				{
					// Calculate priority
					$priority = (
						($this->getAvgWeight($thread['views'], 		0, $foruminfo[$thread['forumid']]['maxviews'], 		$foruminfo[$thread['forumid']]['avgviews']) 	* 0.45) +
						($this->getAvgWeight($thread['replycount'], 0, $foruminfo[$thread['forumid']]['maxreplies'], 	$foruminfo[$thread['forumid']]['avgreplies']) 	* 0.25) +
						((($thread['votenum'] ? $thread['votetotal'] / $thread['votenum'] : 0) / 5) * 0.15)
					) * $this->get_effective_forum_priority($thread['forumid']);
				}
			}

 			if ($this->registry->options['dbtech_dbseo_sitemap_frequency_showthread_smart'])
			{
				// Calculate days passed
				$daysPassed = (time() - $thread['lastpost']) / 86400;

				if ($daysPassed < 3)
				{
					// Less than three days since last post
					$thread['lastupdate'] = 'daily';
				}
				else if ($daysPassed < 10)
				{
					// Less than 10 days since last post
					$thread['lastupdate'] = 'weekly';
				}
				else if ($daysPassed < 100)
				{
					// Less than 100 days since last post
					$thread['lastupdate'] = 'monthly';
				}
				else
				{
					// A long time, in short.
					$thread['lastupdate'] = 'yearly';
				}
			}
			else
			{
				// Set the frequency manually
 				$thread['lastupdate'] = $this->registry->options['dbtech_dbseo_sitemap_frequency_showthread'];
			}

			// Save some performance
			DBSEO::$cache['thread'][$thread['threadid']] = $thread;

			$totalpages = ceil(($thread['replycount'] + 1) / $this->registry->options['maxposts']);
			for ($page = 1; $page <= $totalpages; $page++)
			{
				// Add how many URLs we're processing
				$this->pagecount++;

				$thread['page'] = $page;
				$this->content .= $this->url_block(
					$this->createUrl($thread, $page),
					$thread['lastpost'],
					$this->registry->options['dbtech_dbseo_sitemap_priority_custom'] ? $this->get_effective_forum_priority($thread['forumid']) : $this->getPriority('showthread', $priority),
					$thread['lastupdate']
				);
			}
		}
		$this->dbobject->free_result($threads);
		unset($thread);

		return $this->pagecount;
	}

	/**
	* Creates the URL based on parameters
	*
	* @param 	array	info to work with
	* @param 	int		page we're dealing with
	*/
	private function createUrl($thread, $page)
	{
		if (DBSEO::$config['dbtech_dbseo_rewrite_thread'])
		{
			return DBSEO_Url_Create::create('Thread_Thread' . ($page > 1 ? '_Page' : ''), $thread);
		}
		else
		{
			return intval($this->registry->versionnumber) == 4 ?
				unhtmlspecialchars(fetch_seo_url('thread|bburl|nosession', $thread, ($page > 1) ? array('page' => $page) : array())) :
				$this->registry->options['bburl'] . '/showthread.php?t=' . $thread['threadid'] . ($page > 1 ? '&page=' . $page : '');
		}
	}
}

/**
* Specific class for generating thread-related sitemaps
*
* @package	vBulletin
*/
class DBSEO_SiteMap_Tags extends DBSEO_SiteMap
{
	/**
	* Constructor - checks that the registry object has been passed correctly.
	*
	* @param	vB_Registry		Instance of the vBulletin data registry object - expected to have the database object as one of its $this->db member.
	* @param 	vB_XML_Parser 	Instance of the vBulletin XML parser.
	*/
	function __construct(vB_Registry $registry, vB_XML_Parser $xml_handler = null)
	{
		parent::__construct($registry, $xml_handler);

		$this->set_forum_priorities();
	}

	/**
	* Adds the thread URLs to $this->content
	*
	* @param 	int		threadid to start at
	* @param 	int		perpage limit defaults to 30000
	*/
	public function generate_sitemap($startat = 0, $perpage = 30000)
	{
		if ($startat == 0)
		{
			$this->pagecount++;
			$this->content .= $this->url_block(
				$this->createUrl2(),
				0,
				$this->getPriority('tags', 1.0),
				$this->registry->options['dbtech_dbseo_sitemap_frequency_tags']
			);
		}

		$viewable_forums = DBSEO_SiteMap::get_allowed_forums(true);
		if (!$viewable_forums)
		{
			return $this->pagecount;
		}

		require_once(DIR . '/includes/functions_bigthree.php');
		$coventry = fetch_coventry('array', true);

		$table 			= intval($this->registry->versionnumber) == 3 ? 'tagthread' : 'tagcontent';
		$field 			= intval($this->registry->versionnumber) == 3 ? 'threadid' 	: 'contentid';
		$contenttypeid 	= intval($this->registry->versionnumber) == 3 ? ''			: vB_Types::instance()->getContentTypeID('vBForum_Thread');

		$taginfo = $this->dbobject->query_first_slave("
			SELECT COUNT(*) AS maxreplies
			FROM " . TABLE_PREFIX . $table . "
			GROUP BY tagid
			ORDER BY maxreplies DESC
			LIMIT 1
		");

		$tags = $this->dbobject->query_read_slave("
			SELECT
				tag.tagid,
				tag.tagtext AS tag,
				COUNT(*) AS numthreads,
				MAX(lastpost) AS lastupdate
			FROM " . TABLE_PREFIX . "thread AS thread
			INNER JOIN " . TABLE_PREFIX . $table . " AS tagcontent ON (tagcontent." . $field . " = thread.threadid)
			INNER JOIN " . TABLE_PREFIX . "tag AS tag ON (tag.tagid = tagcontent.tagid)
			WHERE thread.forumid IN(" . implode(',', $viewable_forums) . ")
				" . ($contenttypeid ? "AND tagcontent.contenttypeid = " . $contenttypeid : '') . "
				" . ($coventry ? "AND thread.postuserid NOT IN (" . implode(',', $coventry) . ")" : '') . "
				AND thread.visible = 1
				AND thread.open <> 10
				AND tag.tagid >= " . intval($startat) . "
			GROUP BY tag.tagid
			ORDER BY tag.tagid ASC
			LIMIT " . intval($perpage + 1) // + 1 for has_more check
		);

		$this->has_more = false;

		while ($tag = $this->dbobject->fetch_array($tags))
		{
			if ($this->pagecount >= $perpage)
			{
				$this->has_more = true;
				break;
			}

			// Set our last tag ID
			$this->lastid = $tag['tagid'];

			$totalpages = ceil(($tag['numthreads'] + 1) / $this->registry->options['maxthreads']);
			for ($page = 1; $page <= $totalpages; $page++)
			{
				// Add how many URLs we're processing
				$this->pagecount++;

				$tag['page'] = $page;
				$this->content .= $this->url_block(
					$this->createUrl($tag, $page),
					$tag['lastupdate'],
					$this->getPriority('tags', $this->getAvgWeight($tag['numthreads'], 0, $taginfo['maxreplies'], ($taginfo['maxreplies'] / 2))),
					$this->registry->options['dbtech_dbseo_sitemap_frequency_tags']
				);
			}
		}
		$this->dbobject->free_result($tags);
		unset($tag);

		return $this->pagecount;
	}

	/**
	* Creates the URL based on parameters
	*
	* @param 	array	info to work with
	*/
	private function createUrl($tag, $page)
	{
		if (DBSEO::$config['dbtech_dbseo_rewrite_tags'])
		{
			return DBSEO_Url_Create::create('Tags_Tag_Single' . ($page > 1 ? '_Page' : ''), $tag);
		}
		else
		{
			return $this->registry->options['bburl'] . '/tags.php?tag=' . $tag['tag'] . ($page > 1 ? '&page=' . $page : '');
		}
	}

	/**
	* Creates the URL based on parameters
	*
	* @param 	array	info to work with
	* @param 	int		page we're dealing with
	*/
	private function createUrl2()
	{
		if (DBSEO::$config['dbtech_dbseo_rewrite_tags'])
		{
			return DBSEO_Url_Create::create('Tags_TagList');
		}
		else
		{
			return $this->registry->options['bburl'] . '/tags.php';
		}
	}
}

/**
* Specific class for generating thread-related sitemaps
*
* @package	vBulletin
*/
class DBSEO_SiteMap_Blogtag extends DBSEO_SiteMap
{
	/**
	* Constructor - checks that the registry object has been passed correctly.
	*
	* @param	vB_Registry		Instance of the vBulletin data registry object - expected to have the database object as one of its $this->db member.
	* @param 	vB_XML_Parser 	Instance of the vBulletin XML parser.
	*/
	function __construct(vB_Registry $registry, vB_XML_Parser $xml_handler = null)
	{
		parent::__construct($registry, $xml_handler);
	}

	/**
	* Adds the thread URLs to $this->content
	*
	* @param 	int		threadid to start at
	* @param 	int		perpage limit defaults to 30000
	*/
	public function generate_sitemap($startat = 0, $perpage = 30000)
	{
		if ($startat == 0)
		{
			$this->pagecount++;
			$this->content .= $this->url_block(
				$this->createUrl2(),
				0,
				$this->getPriority('tags', 1.0),
				$this->registry->options['dbtech_dbseo_sitemap_frequency_blogtag']
			);
		}

		require_once(DIR . '/includes/functions_bigthree.php');
		$coventry = fetch_coventry('array', true);

		$table 			= intval($this->registry->versionnumber) == 3 ? 'blog_tagentry' : 'tagcontent';
		$table2 		= intval($this->registry->versionnumber) == 3 ? 'blog_tag' 		: 'tag';
		$field 			= intval($this->registry->versionnumber) == 3 ? 'blogid' 		: 'contentid';
		$contenttypeid 	= intval($this->registry->versionnumber) == 3 ? ''				: vB_Types::instance()->getContentTypeID('vBBlog_BlogEntry');

		$taginfo = $this->dbobject->query_first_slave("
			SELECT COUNT(*) AS maxreplies
			FROM " . TABLE_PREFIX . $table . "
			GROUP BY tagid
			ORDER BY maxreplies DESC
			LIMIT 1
		");

		$tags = $this->dbobject->query_read_slave("
			SELECT
				tag.tagid,
				tag.tagtext AS tag,
				COUNT(*) AS numblogs,
				MAX(lastcomment) AS lastupdate
			FROM " . TABLE_PREFIX . "blog AS blog
			INNER JOIN " . TABLE_PREFIX . $table . " AS tagcontent ON (tagcontent." . $field . " = blog.blogid)
			INNER JOIN " . TABLE_PREFIX . $table2 . " AS tag ON (tag.tagid = tagcontent.tagid)
			WHERE blog.state = 'visible'
				" . ($contenttypeid ? "AND tagcontent.contenttypeid = " . $contenttypeid : '') . "
				" . ($coventry ? "AND blog.userid NOT IN (" . implode(',', $coventry) . ")" : '') . "
				AND tag.tagid >= " . intval($startat) . "
			GROUP BY tag.tagid
			ORDER BY tag.tagid ASC
			LIMIT " . intval($perpage + 1) // + 1 for has_more check
		);

		$this->has_more = false;

		while ($tag = $this->dbobject->fetch_array($tags))
		{
			if ($this->pagecount >= $perpage)
			{
				$this->has_more = true;
				break;
			}

			// Set our last tag ID
			$this->lastid = $tag['tagid'];

			$totalpages = ceil(($tag['numblogs'] + 1) / $this->registry->options['vbblog_perpage']);
			for ($page = 1; $page <= $totalpages; $page++)
			{
				// Add how many URLs we're processing
				$this->pagecount++;

				$tag['page'] = $page;
				$this->content .= $this->url_block(
					$this->createUrl($tag, $page),
					$tag['lastupdate'],
					$this->getPriority('tags', $this->getAvgWeight($tag['numblogs'], 0, $taginfo['maxreplies'], ($taginfo['maxreplies'] / 2))),
					$this->registry->options['dbtech_dbseo_sitemap_frequency_blogtag']
				);
			}
		}
		$this->dbobject->free_result($tags);
		unset($tag);

		return $this->pagecount;
	}

	/**
	* Creates the URL based on parameters
	*
	* @param 	array	info to work with
	*/
	private function createUrl($tag, $page)
	{
		if (DBSEO::$config['dbtech_dbseo_rewrite_blogtag'])
		{
			return DBSEO_Url_Create::create('Blog_BlogTag' . ($page > 1 ? '_Page' : ''), $tag);
		}
		else
		{
			return $this->registry->options['bburl'] . '/blog.php?tag=' . $tag['tag'] . ($page > 1 ? '&page=' . $page : '');
		}
	}

	/**
	* Creates the URL based on parameters
	*
	* @param 	array	info to work with
	* @param 	int		page we're dealing with
	*/
	private function createUrl2()
	{
		if (DBSEO::$config['dbtech_dbseo_rewrite_blogtag'])
		{
			return DBSEO_Url_Create::create('Blog_BlogTags');
		}
		else
		{
			return $this->registry->options['bburl'] . '/blog_tag.php';
		}
	}
}

/**
* Specific class for generating thread-related sitemaps
*
* @package	vBulletin
*/
class DBSEO_SiteMap_Poll extends DBSEO_SiteMap
{
	/**
	* Constructor - checks that the registry object has been passed correctly.
	*
	* @param	vB_Registry		Instance of the vBulletin data registry object - expected to have the database object as one of its $this->db member.
	* @param 	vB_XML_Parser 	Instance of the vBulletin XML parser.
	*/
	function __construct(vB_Registry $registry, vB_XML_Parser $xml_handler = null)
	{
		parent::__construct($registry, $xml_handler);

		$this->set_forum_priorities();
	}

	/**
	* Adds the thread URLs to $this->content
	*
	* @param 	int		threadid to start at
	* @param 	int		perpage limit defaults to 30000
	*/
	public function generate_sitemap($startat = 0, $perpage = 30000)
	{
		$viewable_forums = DBSEO_SiteMap::get_allowed_forums(true);
		if (!$viewable_forums)
		{
			return $this->pagecount;
		}

		require_once(DIR . '/includes/functions_bigthree.php');
		$coventry = fetch_coventry('array', true);

		$pollinfo = $this->dbobject->query_first_slave("
			SELECT
				MAX(voters) AS maxreplies,
				MIN(voters) AS minreplies,
				AVG(voters) AS avgreplies
			FROM " . TABLE_PREFIX . "poll
		");

		$polls = $this->dbobject->query_read_slave("
			SELECT thread.threadid, thread.forumid, thread.title, thread.lastpost, thread.votenum, thread.votetotal, thread.views, thread.replycount, poll.pollid, poll.question, poll.voters, poll.dateline
			FROM " . TABLE_PREFIX . "thread AS thread
			LEFT JOIN " . TABLE_PREFIX . "poll AS poll USING(pollid)
			WHERE thread.forumid IN (" . implode(',', $viewable_forums) . ")
				AND thread.visible = 1
				AND thread.open <> 10
				AND thread.pollid > 0
				" . ($coventry ? "AND thread.postuserid NOT IN (" . implode(',', $coventry) . ")" : '') . "
				AND thread.threadid >= " . intval($startat) . "
			ORDER BY thread.threadid
			LIMIT " . intval($perpage + 1) // + 1 for has_more check
		);

		$this->has_more = false;

		while ($poll = $this->dbobject->fetch_array($polls))
		{
			if ($this->pagecount >= $perpage)
			{
				$this->has_more = true;
				break;
			}

			// Set our last thread ID
			$this->lastid = $poll['threadid'];

			// Calculate priority
			$priority = $this->getAvgWeight($poll['voters'], $pollinfo['minreplies'], $pollinfo['maxreplies'], $pollinfo['avgreplies']) * $this->get_effective_forum_priority($poll['forumid']);

			// Save some performance
			DBSEO::$cache['poll'][$poll['pollid']] = $poll;

			// Add how many URLs we're processing
			$this->pagecount++;

			$this->content .= $this->url_block(
				$this->createUrl($poll),
				$poll['dateline'],
				$this->registry->options['dbtech_dbseo_sitemap_priority_custom'] ? $this->get_effective_forum_priority($poll['forumid']) : $this->getPriority('poll', $priority),
				$this->registry->options['dbtech_dbseo_sitemap_frequency_poll']
			);
		}
		$this->dbobject->free_result($polls);
		unset($poll);

		return $this->pagecount;
	}

	/**
	* Creates the URL based on parameters
	*
	* @param 	array	info to work with
	*/
	private function createUrl($poll)
	{
		if (DBSEO::$config['dbtech_dbseo_rewrite_poll'])
		{
			return DBSEO_Url_Create::create('Poll_Poll', $poll);
		}
		else
		{
			return $this->registry->options['bburl'] . '/poll.php?do=showresults&pollid=' . $poll['pollid'];
		}
	}
}

/**
* Specific class for generating thread-related sitemaps
*
* @package	vBulletin
*/
class DBSEO_SiteMap_Post extends DBSEO_SiteMap
{
	/**
	* Constructor - checks that the registry object has been passed correctly.
	*
	* @param	vB_Registry		Instance of the vBulletin data registry object - expected to have the database object as one of its $this->db member.
	* @param 	vB_XML_Parser 	Instance of the vBulletin XML parser.
	*/
	function __construct(vB_Registry $registry, vB_XML_Parser $xml_handler = null)
	{
		parent::__construct($registry, $xml_handler);

		$this->set_forum_priorities();
	}

	/**
	* Adds the thread URLs to $this->content
	*
	* @param 	int		threadid to start at
	* @param 	int		perpage limit defaults to 30000
	*/
	public function generate_sitemap($startat = 0, $perpage = 30000)
	{
		$viewable_forums = DBSEO_SiteMap::get_allowed_forums(true);
		if (!$viewable_forums)
		{
			return $this->pagecount;
		}

		require_once(DIR . '/includes/functions_bigthree.php');
		$coventry = fetch_coventry('array', true);

		$foruminfo = array();
		$threadinfo = $this->dbobject->query_read_slave("
			SELECT
				forumid,
				COUNT(*) AS numthreads,
				MAX(views) AS maxviews,
				AVG(views) AS avgviews,
				MAX(replycount) AS maxreplies,
				AVG(replycount) AS avgreplies
			FROM " . TABLE_PREFIX . "thread
			WHERE visible = 1
			GROUP BY forumid
		");
		while ($info = $this->dbobject->fetch_array($threadinfo))
		{
			// Store the forum info
			$foruminfo[$info['forumid']] = $info;
		}
		$this->dbobject->free_result($threadinfo);
		unset($info);

		$threads = $this->dbobject->query_read_slave("
			SELECT threadid, forumid, title, lastpost, votenum, votetotal, views, replycount
			FROM " . TABLE_PREFIX . "thread
			WHERE forumid IN (" . implode(',', $viewable_forums) . ")
				AND visible = 1
				AND open <> 10
				" . ($coventry ? "AND postuserid NOT IN (" . implode(',', $coventry) . ")" : '') . "
				AND threadid >= " . intval($startat) . "
			ORDER BY threadid
			LIMIT " . intval($perpage + 1) // + 1 for has_more check
		);

		$this->has_more = false;

		while ($thread = $this->dbobject->fetch_array($threads))
		{
			if ($this->pagecount >= $perpage)
			{
				$this->has_more = true;
				break;
			}

			// Set our last thread ID
			$this->lastid = $thread['threadid'];

			// Default priority
			$priority = $this->get_effective_forum_priority($thread['forumid']);

			if ($this->registry->options['dbtech_dbseo_sitemap_priority_smart'])
			{
				if ($thread['sticky'])
				{
					// Always high priority
					$priority = 1;
				}
				else
				{
					// Calculate priority
					$priority = (
						($this->getAvgWeight($thread['views'], 		0, $foruminfo[$thread['forumid']]['maxviews'], 		$foruminfo[$thread['forumid']]['avgviews']) 	* 0.45) +
						($this->getAvgWeight($thread['replycount'], 0, $foruminfo[$thread['forumid']]['maxreplies'], 	$foruminfo[$thread['forumid']]['avgreplies']) 	* 0.25) +
						((($thread['votenum'] ? $thread['votetotal'] / $thread['votenum'] : 0) / 5) * 0.15)
					) * $this->get_effective_forum_priority($thread['forumid']);
				}
			}

 			if ($this->registry->options['dbtech_dbseo_sitemap_frequency_showthread_smart'])
			{
				// Calculate days passed
				$daysPassed = (time() - $thread['lastpost']) / 86400;

				if ($daysPassed < 3)
				{
					// Less than three days since last post
					$thread['lastupdate'] = 'daily';
				}
				else if ($daysPassed < 10)
				{
					// Less than 10 days since last post
					$thread['lastupdate'] = 'weekly';
				}
				else if ($daysPassed < 100)
				{
					// Less than 100 days since last post
					$thread['lastupdate'] = 'monthly';
				}
				else
				{
					// A long time, in short.
					$thread['lastupdate'] = 'yearly';
				}
			}
			else
			{
				// Set the frequency manually
 				$thread['lastupdate'] = $this->registry->options['dbtech_dbseo_sitemap_frequency_showthread'];
			}

			// Save some performance
			DBSEO::$cache['thread'][$thread['threadid']] = $thread;

			$posts = $this->dbobject->query("
				SELECT dateline, postid, threadid
				FROM " . TABLE_PREFIX . "post
				WHERE threadid = $thread[threadid]
					AND visible = 1
				ORDER BY dateline ASC
			");

			$i = 0;
			while ($post = $this->dbobject->fetch_array($posts))
			{
				// Add how many URLs we're processing
				$this->pagecount++;

				// Increment the counter
				$i++;

				// Create new superarray!
				$post = array_merge($thread, $post);

				// Store the post counter
				$post['post_count'] = $i;

				// Set priority
				$priority = (
					($priority * 0.8) +
					($i / ($thread['replycount'] + 1) * 0.2)
				) * $this->get_effective_forum_priority($thread['forumid']);

				// Add the content
				$this->content .= $this->url_block(
					$this->createUrl($post),
					$post['dateline'],
					$this->registry->options['dbtech_dbseo_sitemap_priority_custom'] ? $this->get_effective_forum_priority($post['forumid']) : $this->getPriority('showpost', $priority),
					$this->registry->options['dbtech_dbseo_sitemap_frequency_showpost']
				);
			}
		}
		$this->dbobject->free_result($threads);
		unset($thread);

		return $this->pagecount;
	}

	/**
	* Creates the URL based on parameters
	*
	* @param 	array	info to work with
	*/
	private function createUrl($post)
	{
		if (DBSEO::$config['dbtech_dbseo_rewrite_thread'])
		{
			return DBSEO_Url_Create::create('ShowPost_ShowPost', $post);
		}
		else
		{
			return intval($this->registry->versionnumber) == 4 ?
				unhtmlspecialchars(fetch_seo_url('thread|bburl|nosession', $post, array('p' => $post['postid']))) . "#post$post[postid]" :
				$this->registry->options['bburl'] . '/showpost.php?p=' . $post['postid'] . "#post$post[postid]";
		}
	}
}

/**
* Specific class for generating thread-related sitemaps
*
* @package	vBulletin
*/
class DBSEO_SiteMap_Member extends DBSEO_SiteMap
{
	/**
	* Constructor - checks that the registry object has been passed correctly.
	*
	* @param	vB_Registry		Instance of the vBulletin data registry object - expected to have the database object as one of its $this->db member.
	* @param 	vB_XML_Parser 	Instance of the vBulletin XML parser.
	*/
	function __construct(vB_Registry $registry, vB_XML_Parser $xml_handler = null)
	{
		parent::__construct($registry, $xml_handler);
	}

	/**
	* Adds the thread URLs to $this->content
	*
	* @param 	int		threadid to start at
	* @param 	int		perpage limit defaults to 30000
	*/
	public function generate_sitemap($startat = 0, $perpage = 30000)
	{
		require_once(DIR . '/includes/functions_bigthree.php');
		$coventry = fetch_coventry('array', true);

		$userinfo = $this->dbobject->query_first_slave("
			SELECT
				MAX(posts) AS maxreplies,
				MIN(posts) AS minreplies,
				AVG(posts) AS avgreplies
			FROM " . TABLE_PREFIX . "user
		");

		$users = $this->dbobject->query_read_slave("
			SELECT userid, username, lastpost, posts
			FROM " . TABLE_PREFIX . "user
			WHERE userid >= " . intval($startat) . "
				" . ($coventry ? "AND userid NOT IN (" . implode(',', $coventry) . ")" : '') . "
			ORDER BY userid
			LIMIT " . intval($perpage + 1) // + 1 for has_more check
		);

		$this->has_more = false;

		while ($user = $this->dbobject->fetch_array($users))
		{
			if ($this->pagecount >= $perpage)
			{
				$this->has_more = true;
				break;
			}

			// Set our last user ID
			$this->lastid = $user['userid'];

			// We had this cached, cache it internally too
			DBSEO::$cache['userinfo'][$user['userid']] = $user;

			// We had this cached, cache it internally too
			DBSEO::$cache['username'][strtolower($user['username'])] = $user;

			// Add how many URLs we're processing
			$this->pagecount++;

			// Add the content
			$this->content .= $this->url_block(
				$this->createUrl($user),
				$user['lastpost'],
				$this->getPriority('member', $this->getAvgWeight($user['posts'], $userinfo['minreplies'], $userinfo['maxreplies'], $userinfo['avgreplies'])),
				$this->registry->options['dbtech_dbseo_sitemap_frequency_member']
			);
		}
		$this->dbobject->free_result($users);
		unset($user);

		return $this->pagecount;
	}

	/**
	* Creates the URL based on parameters
	*
	* @param 	array	info to work with
	*/
	private function createUrl($user)
	{
		if (DBSEO::$config['dbtech_dbseo_rewrite_memberprofile'])
		{
			return DBSEO_Url_Create::create('MemberProfile_MemberProfile', $user);
		}
		else
		{
			return intval($this->registry->versionnumber) == 4 ?
				unhtmlspecialchars(fetch_seo_url('member|bburl|nosession', $user)) :
				$this->registry->options['bburl'] . '/member.php?u=' . $user['userid'];
		}
	}
}


/**
* Specific class for generating thread-related sitemaps
*
* @package	vBulletin
*/
class DBSEO_SiteMap_Album extends DBSEO_SiteMap
{
	/**
	* Constructor - checks that the registry object has been passed correctly.
	*
	* @param	vB_Registry		Instance of the vBulletin data registry object - expected to have the database object as one of its $this->db member.
	* @param 	vB_XML_Parser 	Instance of the vBulletin XML parser.
	*/
	function __construct(vB_Registry $registry, vB_XML_Parser $xml_handler = null)
	{
		parent::__construct($registry, $xml_handler);
	}

	/**
	* Adds the thread URLs to $this->content
	*
	* @param 	int		threadid to start at
	* @param 	int		perpage limit defaults to 30000
	*/
	public function generate_sitemap($startat = 0, $perpage = 30000)
	{
		require_once(DIR . '/includes/functions_bigthree.php');
		$coventry = fetch_coventry('array', true);

		$userinfo = $this->dbobject->query_first_slave("
			SELECT
				MAX(posts) AS maxreplies,
				MIN(posts) AS minreplies,
				AVG(posts) AS avgreplies
			FROM " . TABLE_PREFIX . "user
		");

		$albums = $this->dbobject->query_read_slave("
			SELECT album.*, user.userid, user.username, user.lastpost, user.posts
			FROM " . TABLE_PREFIX . "album AS album
			LEFT JOIN " . TABLE_PREFIX . "user AS user USING(userid)
			WHERE album.albumid >= " . intval($startat) . "
				" . ($coventry ? "AND album.userid NOT IN (" . implode(',', $coventry) . ")" : '') . "
				AND album.visible > 0
				AND album.state = 'public'
			ORDER BY album.userid
			LIMIT " . intval($perpage + 1) // + 1 for has_more check
		);

		$this->has_more = false;

		$lastUserId = 0;
		while ($album = $this->dbobject->fetch_array($albums))
		{
			if ($this->pagecount >= $perpage)
			{
				$this->has_more = true;
				break;
			}

			// Set our last user ID
			$this->lastid = $album['albumid'];

			// We had this cached, cache it internally too
			DBSEO::$cache['userinfo'][$album['userid']] = $album;

			// We had this cached, cache it internally too
			DBSEO::$cache['username'][strtolower($album['username'])] = $album;

			// Store priority
			$priority = $this->getAvgWeight($album['posts'], $userinfo['minreplies'], $userinfo['maxreplies'], $userinfo['avgreplies']);

			if ($lastUserId != $album['userid'])
			{
				// Add how many URLs we're processing
				$this->pagecount++;

				// Add the content
				$this->content .= $this->url_block(
					$this->createUrl($album),
					$album['lastpicturedate'],
					$this->getPriority('album', $priority),
					$this->registry->options['dbtech_dbseo_sitemap_frequency_album']
				);

				// Store last user ID so we don't add the album list all over again
				$lastUserId = $album['userid'];
			}

			// Add how many URLs we're processing
			$this->pagecount++;

			// Add the content
			$this->content .= $this->url_block(
				$this->createUrl2($album),
				$album['lastpicturedate'],
				$this->getPriority('album', ($priority * 0.8)),
				$this->registry->options['dbtech_dbseo_sitemap_frequency_album']
			);

			if (intval($this->registry->versionnumber) == 4)
			{
				$pictures = $this->dbobject->query_read_slave("
					SELECT *
					FROM " . TABLE_PREFIX . "attachment
					WHERE state = 'visible'
						AND contenttypeid = '" . vB_Types::instance()->getContentTypeID('vBForum_Album') . "'
						AND contentid = '" . $album['albumid'] . "'
				");
			}
			else
			{
				$pictures = $this->dbobject->query_read_slave("
					SELECT albumpicture.*, picture.caption
					FROM " . TABLE_PREFIX . "albumpicture AS albumpicture
					LEFT JOIN " . TABLE_PREFIX . "picture AS picture USING(pictureid)
					WHERE state = 'visible'
						AND albumid = '" . $album['albumid'] . "'
				");
			}

			while ($picture = $this->dbobject->fetch_array($pictures))
			{
				// Add how many URLs we're processing
				$this->pagecount++;

				// Ensure we got enough stuff
				$picture = array_merge($album, $picture);

				// Add the content
				$this->content .= $this->url_block(
					$this->createUrl3($picture),
					$picture['dateline'],
					$this->getPriority('album', $priority),
					$this->registry->options['dbtech_dbseo_sitemap_frequency_album']
				);
			}
			$this->dbobject->free_result($pictures);
			unset($picture);
		}
		$this->dbobject->free_result($albums);
		unset($album);

		return $this->pagecount;
	}

	/**
	* Creates the URL based on parameters
	*
	* @param 	array	info to work with
	*/
	private function createUrl($album)
	{
		if (DBSEO::$config['dbtech_dbseo_rewrite_album'])
		{
			return DBSEO_Url_Create::create('Album_AlbumList', $album);
		}
		else
		{
			return $this->registry->options['bburl'] . '/album.php?u=' . $album['userid'];
		}
	}

	/**
	* Creates the URL based on parameters
	*
	* @param 	array	info to work with
	*/
	private function createUrl2($album)
	{
		if (DBSEO::$config['dbtech_dbseo_rewrite_album'])
		{
			return DBSEO_Url_Create::create('Album_Album', $album);
		}
		else
		{
			return $this->registry->options['bburl'] . '/album.php?albumid=' . $album['albumid'];
		}
	}

	/**
	* Creates the URL based on parameters
	*
	* @param 	array	info to work with
	*/
	private function createUrl3($album)
	{
		if (DBSEO::$config['dbtech_dbseo_rewrite_album'])
		{
			return DBSEO_Url_Create::create('Album_AlbumPicture', $album);
		}
		else
		{
			return $this->registry->options['bburl'] . '/album.php?albumid=' . $album['albumid'] . '&' . DBSEO::$config['_pictureid'];
		}
	}
}

/**
 * Specific class for generating forum-related sitemaps
 *
 * @package	vBulletin
 */
class DBSEO_SiteMap_Cms extends DBSEO_SiteMap
{
	private $nodes = false;
	/**
	 * Constructor - checks that the registry object has been passed correctly.
	 *
	 * @param	vB_Registry		Instance of the vBulletin data registry object - expected to have the database object as one of its $this->db member.
	 * @param 	vB_XML_Parser 	Instance of the vBulletin XML parser.
	 */
	function __construct(vB_Registry $registry, vB_XML_Parser $xml_handler = null)
	{
		parent::__construct($registry, $xml_handler);

		if ($registry->options['dbtech_dbseo_sitemap_priority_custom'])
		{
			// We're using the vB custom priorities
			$this->load_data();
		}
	}

	/**
	 * Adds the CMS URLs to $this->content
	 *
	 * @param 	int		forumdid to start at
	 * @param 	int		perpage limit defaults to 30000
	 */
	public function generate_sitemap($startat = 0, $perpage = 30000)
	{
		global $config;

		if ($startat == 0)
		{
			$this->pagecount++;
			$this->content .= $this->url_block(
				$this->createUrl2(),
				0,
				$this->getPriority('content', 1.0),
				$this->registry->options['dbtech_dbseo_sitemap_frequency_content']
			);
		}

		// make sure we have the node information
		if (!$this->nodes)
		{
			$this->nodes = vBCms_ContentManager::getPublicContent($startat, $perpage);
		}

		$this->has_more = false;

		$nodeinfo = $this->dbobject->query_first_slave("
			SELECT
				MAX(viewcount) AS maxreplies,
				AVG(viewcount) as avgreplies
			FROM " . TABLE_PREFIX . "cms_nodeinfo
		");

		$route = vB_Route::create('vBCms_Route_Content');
		foreach($this->nodes as $node)
		{
			$this->pagecount++;
			$this->lastid = $node['nodeid'];
			$route->node = $node['nodeid'] . (empty($node['url']) ? '' : '-' . $node['url']);
			$rawurl = $route->getCurrentURL();
			$pageurl = str_replace('/' . vB::$vbulletin->config['Misc']['admincpdir'] . '/' , '/', $rawurl);

			$priority = $this->getAvgWeight($node['viewcount'], 0, $nodeinfo['maxreplies'], $nodeinfo['avgreplies']) * $this->get_effective_priority('cms', $node['sectionid']);

			$this->content .= $this->url_block(
				$this->createUrl(array('entryid' => $node['nodeid']), $pageurl),
				$node['publishdate'],
				$this->registry->options['dbtech_dbseo_sitemap_priority_custom'] ? $this->get_effective_priority('cms', $node['sectionid']) : $this->getPriority('content', $priority),
				$this->registry->options['dbtech_dbseo_sitemap_frequency_content']
			);

			if ($this->pagecount >= $perpage)
			{
				$this->has_more = true;
				break;
			}
		}

		// Return the amout done
		return $this->pagecount;
	}

	/**
	* Creates the URL based on parameters
	*
	* @param 	array	info to work with
	* @param 	string	default page URL
	*/
	private function createUrl($data, $pageurl)
	{
		if (DBSEO::$config['dbtech_dbseo_rewrite_cms'])
		{
			return DBSEO_Url_Create::create('CMS_CMSEntry', $data);
		}
		else
		{
			return $pageurl;
		}
	}


	/**
	* Creates the URL based on parameters
	*/
	private function createUrl2()
	{
		if (DBSEO::$config['dbtech_dbseo_rewrite_cms'])
		{
			return DBSEO_Url_Create::create('CMS_CMSHome');
		}
		else
		{
			return $this->registry->options['bburl'] . '/content.php';
		}
	}

	/**
	 * load the existing data
	 *
	 */
	private function load_data()
	{
		$sections = vBCms_ContentManager::getSections();
		$perms = vBCMS_Permissions::getPerms(0);
		$this->custom_priority['cms'] = array();
		$level = array();

		foreach ($sections as $nodeid => $section)
		{
			if ((!$section['hidden']) AND (in_array($section['permissionsfrom'], $perms['canview'])))
			{
				$section['priority'] = false;
				$this->custom_priority['cms'][$section['nodeid']] = $section;
			}
		}

		$this->set_priorities('cms');
	}
}

/**
 * Specific class for generating forum-related sitemaps
 *
 * @package	vBulletin
 */
class DBSEO_SiteMap_Cmssection extends DBSEO_SiteMap
{
	private $nodes = false;
	/**
	 * Constructor - checks that the registry object has been passed correctly.
	 *
	 * @param	vB_Registry		Instance of the vBulletin data registry object - expected to have the database object as one of its $this->db member.
	 * @param 	vB_XML_Parser 	Instance of the vBulletin XML parser.
	 */
	function __construct(vB_Registry $registry, vB_XML_Parser $xml_handler = null)
	{
		parent::__construct($registry, $xml_handler);

		if ($registry->options['dbtech_dbseo_sitemap_priority_custom'])
		{
			// We're using the vB custom priorities
			$this->load_data();
		}
	}

	/**
	 * Adds the CMS URLs to $this->content
	 *
	 * @param 	int		forumdid to start at
	 * @param 	int		perpage limit defaults to 30000
	 */
	public function generate_sitemap($startat = 0, $perpage = 30000)
	{
		global $config;

		// make sure we have the node information
		if (!$this->nodes)
		{
			$this->nodes = vBCms_ContentManager::getPublicContent($startat, $perpage);
		}

		$this->has_more = false;

		$nodeinfo = $this->dbobject->query_first_slave("
			SELECT
				MAX(viewcount) AS maxreplies,
				AVG(viewcount) as avgreplies
			FROM " . TABLE_PREFIX . "cms_nodeinfo
		");

		require_once(DIR . '/includes/class_bootstrap_framework.php');
		require_once(DIR . '/vb/types.php');
		vB_Bootstrap_Framework::init();

		$nodeIds = array();
		$publicSections = $this->dbobject->query("
			SELECT node.nodeid
			FROM " . TABLE_PREFIX . "cms_node AS node
			LEFT JOIN  " . TABLE_PREFIX . "cms_permissions AS nodepermissions ON(nodepermissions.nodeid = node.nodeid)
			LEFT JOIN  " . TABLE_PREFIX . "cms_nodeinfo AS nodeinfo ON(nodeinfo.nodeid = node.nodeid)
			WHERE nodepermissions.usergroupid = 2
				AND nodepermissions.permissions > 0
				AND contenttypeid = '" . vB_Types::instance()->getContentTypeID('vBCms_Section') . "'
		");
		while ($publicSection = $this->dbobject->fetch_array($publicSections))
		{
			// Store this
			$nodeIds[] = $publicSection['nodeid'];
		}
		$this->dbobject->free_result($publicSections);
		unset($publicSection);

		if (count($nodeIds))
		{
			$sections = $this->dbobject->query("
				SELECT node.*, nodeinfo.*
				FROM " . TABLE_PREFIX . "cms_node AS node
				LEFT JOIN  " . TABLE_PREFIX . "cms_nodeinfo AS nodeinfo ON(nodeinfo.nodeid = node.nodeid)
				WHERE (
						node.nodeid IN(" . implode(',', $nodeIds) . ") OR (
							node.parentnode IN (" . implode(',', $nodeIds) . ") AND
							contenttypeid = '" . vB_Types::instance()->getContentTypeID('vBCms_Section') . "'
						)
					)
					AND node.nodeid >= " . intval($startat) . "
				ORDER BY node.nodeid
				LIMIT " . intval($perpage + 1) // + 1 for has_more check
			);

			while ($node = $this->dbobject->fetch_array($sections))
			{
				$this->pagecount++;
				$this->lastid = $node['nodeid'];

				$rawurl = vB_Route::create('vBCms_Route_Content', $node['nodeid'] . (empty($node['url']) ? '' : '-' . $node['url']))->getCurrentURL();
				$pageurl = str_replace('/' . vB::$vbulletin->config['Misc']['admincpdir'] . '/' , '/', $rawurl);

				$priority = $this->getAvgWeight($node['viewcount'], 0, $nodeinfo['maxreplies'], $nodeinfo['avgreplies']) * $this->get_effective_priority('cms', $node['nodeid']);

				$this->content .= $this->url_block(
					$this->createUrl(array('sectionid' => $node['nodeid']), $pageurl),
					$node['publishdate'],
					$this->registry->options['dbtech_dbseo_sitemap_priority_custom'] ? $this->get_effective_priority('cms', $node['nodeid']) : $this->getPriority('section', $priority),
					$this->registry->options['dbtech_dbseo_sitemap_frequency_content']
				);

				if ($this->pagecount >= $perpage)
				{
					$this->has_more = true;
					break;
				}
			}
			$this->dbobject->free_result($sections);
			unset($section);
		}

		// Return the amout done
		return $this->pagecount;
	}

	/**
	* Creates the URL based on parameters
	*
	* @param 	array	info to work with
	* @param 	string	default page URL
	*/
	private function createUrl($data, $pageurl)
	{
		if (DBSEO::$config['dbtech_dbseo_rewrite_cms'])
		{
			return DBSEO_Url_Create::create('CMS_CMSSection', $data);
		}
		else
		{
			return $pageurl;
		}
	}

	/**
	 * load the existing data
	 *
	 */
	private function load_data()
	{
		$sections = vBCms_ContentManager::getSections();
		$perms = vBCMS_Permissions::getPerms(0);
		$this->custom_priority['cms'] = array();
		$level = array();

		foreach ($sections as $nodeid => $section)
		{
			if ((!$section['hidden']) AND (in_array($section['permissionsfrom'], $perms['canview'])))
			{
				$section['priority'] = false;
				$this->custom_priority['cms'][$section['nodeid']] = $section;
			}
		}

		$this->set_priorities('cms');
	}
}

/**
 * Specific class for generating forum-related sitemaps
 *
 * @package	vBulletin
 */
class DBSEO_SiteMap_Blog extends DBSEO_SiteMap
{
	private $blogs = false;

	private $defaults = array (
		'default' => 0.4,
		'age_pts' => 0.02,
		'age_max' => 0.2,
		'c_age_pts' =>  0.02,
		'c_age_max' => 0.2,
		'comm_pts' =>  0.02,
		'comm_max' => 0.2
	);

	/**
	 * Constructor - checks that the registry object has been passed correctly.
	 *
	 * @param	vB_Registry		Instance of the vBulletin data registry object - expected to have the database object as one of its $this->db member.
	 * @param 	vB_XML_Parser 	Instance of the vBulletin XML parser.
	 */
	function __construct(vB_Registry $registry, vB_XML_Parser $xml_handler = null)
	{
		parent::__construct($registry, $xml_handler);

		if ($registry->options['dbtech_dbseo_sitemap_priority_custom'])
		{
			$this->load_data();
		}
	}

	/**
	 * Adds the blog URLs to $this->content
	 *
	 * @param 	int		forumdid to start at
	 * @param 	int		perpage limit defaults to 30000
	 */
	public function generate_sitemap($startat = 0, $perpage = 30000)
	{
		global $vbulletin;

		if ($startat == 0)
		{
			$this->pagecount++;
			$this->content .= $this->url_block(
				$this->createUrl2(),
				0,
				$this->getPriority('blog', 1.0),
				$this->registry->options['dbtech_dbseo_sitemap_frequency_blog']
			);
		}

		//See if we need to bootstrap
		require_once(DIR . '/includes/blog_functions_shared.php');
		require_once(DIR . '/includes/blog_functions_search.php');
		require_once(DIR . '/includes/functions.php');
		$guestuser = array(
			'userid'      => 0,
			'usergroupid' => 0,
		);
		cache_permissions($guestuser, false);
		$perms = build_blog_permissions_query($guestuser);

		$bloginfo = $this->dbobject->query_first_slave("
			SELECT
				MAX(views) AS maxreplies,
				MIN(views) AS minreplies,
				AVG(views) AS avgreplies
			FROM " . TABLE_PREFIX . "blog
			WHERE state = 'visible'
		");

		$this->blogs = array();
		$sql = "SELECT blog.blogid, blog.title, blog.userid, blog.dateline,
			blog.lastcomment, blog.comments_visible, count(bt.blogtextid) AS qty
			FROM " . TABLE_PREFIX . "blog AS blog
			LEFT JOIN " . TABLE_PREFIX . "blog_text AS bt ON blog.blogid = bt.blogid
			" . $perms['join'] . "
			WHERE " . $perms['where'] . "
			GROUP BY blog.blogid, blog.title, blog.userid, blog.dateline,
			blog.lastcomment, blog.comments_visible LIMIT $startat, $perpage";

		$rst = $this->dbobject->query_read_slave($sql);
		$authorkeys = isset($this->custom_priority['blog']['authors']) ? array_keys($this->custom_priority['blog']['authors']) : array();

		while($blog = $this->dbobject->fetch_array($rst))
		{
			$this->pagecount++;
			$this->lastid = $blog['blogid'];

			//get the two ages- post and last comment, and the author points
			$postmonths = (intval($blog['lastcomment'])) ? floor((TIMENOW - $blog['lastcomment']) / (30 * 86400)) : 0;
			$agemonths = (intval($blog['dateline'])) ? floor((TIMENOW - $blog['dateline']) / (30 * 86400)) : 0;
			$commentmult = (intval($blog['qty']) AND intval($blog['comments_visible'])) ? floor($blog['qty']/10)  : 0;
			$authpoints = (in_array($blog['userid'], $authorkeys )) ? floatval($this->custom_priority['blog']['authors'][$blog['userid']]) : 0;

			//and calculate the rating
			$defaultPriority = $this->custom_priority['blog']['default']
				- min($postmonths * $this->custom_priority['blog']['age_pts'], $this->custom_priority['blog']['age_max'])
				- min($agemonths * $this->custom_priority['blog']['c_age_pts'], $this->custom_priority['blog']['c_age_max'])
				+ min($commentmult * $this->custom_priority['blog']['comm_pts'], $this->custom_priority['blog']['comm_max']);

			// We want a two-digit number between 0 and 1
			$defaultPriority = max(min($defaultPriority, 1), 0);
			$defaultPriority = round($defaultPriority, 2);

			$priority = $this->getAvgWeight($blog['views'], $bloginfo['minreplies'], $bloginfo['maxreplies'], $bloginfo['avgreplies']) * $this->getPriority('blog', $priority);

			$this->content .= $this->url_block(
				$this->createUrl($blog),
				$blog['lastcomment'],
				$this->registry->options['dbtech_dbseo_sitemap_priority_custom'] ? $defaultPriority : $this->getPriority('blog', $priority),
				$this->registry->options['dbtech_dbseo_sitemap_frequency_blog']
			);

			if ($this->pagecount >= $perpage)
			{
				$this->has_more = true;
				break;
			}
		}

		// Return the amout done
		return $this->pagecount;
	}

	/**
	* Creates the URL based on parameters
	*/
	private function createUrl($blog)
	{
		if (DBSEO::$config['dbtech_dbseo_rewrite_blogentry'])
		{
			return DBSEO_Url_Create::create('Blog_BlogEntry', $blog);
		}
		else
		{
			return intval($this->registry->versionnumber) == 4 ?
				unhtmlspecialchars(fetch_seo_url('entry|bburl|nosession', array('blogid' => $blog['blogid'], 'page' => 1 , 'blogtitle' => $blog['title'], 'title' => $blog['title'], 'id' => $blog['blogid']))) :
				$this->registry->options['bburl'] . '/blog.php?b=' . $blog['blogid'];

			return $pageurl;
		}
	}

	/**
	* Creates the URL based on parameters
	*/
	private function createUrl2()
	{
		if (DBSEO::$config['dbtech_dbseo_rewrite_blog'])
		{
			return DBSEO_Url_Create::create('Blog_Blogs');
		}
		else
		{
			return intval($this->registry->versionnumber) == 4 ?
				unhtmlspecialchars(fetch_seo_url('bloghome|bburl|nosession', array())) :
				$this->registry->options['bburl'] . '/blog.php';

			return $pageurl;
		}
	}

	/**
	 * load the existing data
	 *
	 */
	public function load_data()
	{
		$this->custom_priority['blog'] = $this->defaults;
		$authors = array();
		if ($rst = $this->dbobject->query_read_slave("SELECT name, value, weight from " . TABLE_PREFIX . "blog_sitemapconf"))
		{
			$weights = array();
			while($setting = $this->dbobject->fetch_array($rst))
			{
				if ($setting['name'] == 'author')
				{
					$weights[$setting['value']] = $setting['weight'];
				}
				else
				{
					$this->custom_priority['blog'][$setting['name']] = $setting['weight'];
				}
			}

			if (!empty($weights))
			{
				$sql = "SELECT userid, username FROM " . TABLE_PREFIX . "user WHERE userid in ("
					. implode(',', array_keys($weights)) . ")";

				if ($rst = $this->dbobject->query_read_slave($sql))
				{
					while($author = $this->dbobject->fetch_array($rst))
					{
						$authors[$author['userid']] = array('name' => $author['username'],
							'weight' => $weights[$author['userid']]);
					}
				}
			}
		}

		$this->custom_priority['blog']['authors'] = $authors;
	}
}

/**
* Specific class for generating forum-related sitemaps
*
* @package	vBulletin
*/
class DBSEO_SiteMap_Group extends DBSEO_SiteMap
{
	/**
	* Constructor - checks that the registry object has been passed correctly.
	*
	* @param	vB_Registry		Instance of the vBulletin data registry object - expected to have the database object as one of its $this->db member.
	* @param 	vB_XML_Parser 	Instance of the vBulletin XML parser.
	*/
	function __construct(vB_Registry $registry, vB_XML_Parser $xml_handler = null)
	{
		parent::__construct($registry, $xml_handler);
	}

	/**
	* Adds the URLs to $this->content
	*
	* @param 	int		forumdid to start at
	* @param 	int		perpage limit defaults to 30000
	*/
	public function generate_sitemap($startat = 0, $perpage = 30000)
	{
		if ($startat == 0)
		{
			$this->pagecount++;
			$this->content .= $this->url_block(
				$this->createUrl2(),
				0,
				$this->getPriority('socialgroup', 1.0),
				$this->registry->options['dbtech_dbseo_sitemap_frequency_socialgroup']
			);
		}

		$groupstats = $this->dbobject->query_first_slave("
			SELECT
				MAX(members) AS maxreplies,
				MIN(members) AS minreplies,
				AVG(members) AS avgreplies
			FROM " . TABLE_PREFIX . "socialgroup
			WHERE type = 'public'
		");

		$groupinfo = array();
		$discussioninfo = $this->dbobject->query_read_slave("
			SELECT
				discussion.groupid,
				COUNT(*) AS discussions
			FROM " . TABLE_PREFIX . "discussion AS discussion
			WHERE discussion.deleted = 0
			GROUP BY discussion.groupid
		");
		while ($info = $this->dbobject->fetch_array($discussioninfo))
		{
			// Store the forum info
			$groupinfo[$info['groupid']] = $info;
		}
		$this->dbobject->free_result($discussioninfo);
		unset($info);

		$socialgroups = $this->dbobject->query_read_slave("
			SELECT *
			FROM " . TABLE_PREFIX . "socialgroup
			WHERE type = 'public'
				AND groupid >= " . intval($startat) . "
			ORDER BY groupid
			LIMIT " . intval($perpage + 1) // for has_more check
		);

		$this->has_more = false;

		while ($group = $this->dbobject->fetch_array($socialgroups))
		{
			if ($this->pagecount >= $perpage)
			{
				$this->has_more = true;
				break;
			}

			// Store the last ID processed
			$this->lastid = $group['groupid'];

			// Save some performance
			DBSEO::$cache['socialgroup'][$group['groupid']] = $group;

			$totalpages = max(ceil($groupinfo[$group['groupid']]['discussions'] / $this->registry->options['sgd_perpage']), 1);
			for ($page = 1; $page <= $totalpages; $page++)
			{
				// Increment page counter
				$this->pagecount++;

				$group['page'] = $page;
				$this->content .= $this->url_block(
					$this->createUrl($group, $page),
					$group['lastpost'],
					$this->getPriority('socialgroup', $this->getAvgWeight($group['members'], $groupstats['minreplies'], $groupstats['maxreplies'], $groupstats['avgreplies'])),
					$this->registry->options['dbtech_dbseo_sitemap_frequency_socialgroup']
				);
			}
		}
		$this->dbobject->free_result($socialgroups);
		unset($group);

		// Return the amout done
		return $this->pagecount;
	}

	/**
	* Creates the URL based on parameters
	*
	* @param 	array	info to work with
	* @param 	int		page we're dealing with
	*/
	private function createUrl($group, $page)
	{
		if (DBSEO::$config['dbtech_dbseo_rewrite_socialgroup'])
		{
			return DBSEO_Url_Create::create('SocialGroup_SocialGroup' . ($page > 1 ? '_Page' : ''), $group);
		}
		else
		{
			return intval($this->registry->versionnumber) == 4 ?
				unhtmlspecialchars(fetch_seo_url('group|bburl|nosession', $group, ($page > 1) ? array('page' => $page) : array())) :
				$this->registry->options['bburl'] . '/group.php?groupid=' . $group['groupid'] . ($page > 1 ? '&page=' . $page : '');
		}
	}

	/**
	* Creates the URL based on parameters
	*/
	private function createUrl2()
	{
		if (DBSEO::$config['dbtech_dbseo_rewrite_socialgroup'])
		{
			return DBSEO_Url_Create::create('SocialGroup_SocialGroupHome', array());
		}
		else
		{
			return intval($this->registry->versionnumber) == 4 ?
				unhtmlspecialchars(fetch_seo_url('grouphome|bburl|nosession')) :
				$this->registry->options['bburl'] . '/group.php';
		}
	}
}

/**
* Specific class for generating forum-related sitemaps
*
* @package	vBulletin
*/
class DBSEO_SiteMap_Groupdiscuss extends DBSEO_SiteMap
{
	/**
	* Constructor - checks that the registry object has been passed correctly.
	*
	* @param	vB_Registry		Instance of the vBulletin data registry object - expected to have the database object as one of its $this->db member.
	* @param 	vB_XML_Parser 	Instance of the vBulletin XML parser.
	*/
	function __construct(vB_Registry $registry, vB_XML_Parser $xml_handler = null)
	{
		parent::__construct($registry, $xml_handler);
	}

	/**
	* Adds the URLs to $this->content
	*
	* @param 	int		forumdid to start at
	* @param 	int		perpage limit defaults to 30000
	*/
	public function generate_sitemap($startat = 0, $perpage = 30000)
	{
		$discussionstats = $this->dbobject->query_first_slave("
			SELECT
				MAX(visible) AS maxreplies,
				MIN(visible) AS minreplies,
				AVG(visible) AS avgreplies
			FROM " . TABLE_PREFIX . "discussion
			WHERE deleted = 0
				AND groupid > 0
		");

		$discussions = $this->dbobject->query_read_slave("
			SELECT socialgroup.*, discussion.*
			FROM " . TABLE_PREFIX . "discussion AS discussion
			LEFT JOIN " . TABLE_PREFIX . "socialgroup AS socialgroup USING (groupid)
			WHERE socialgroup.type = 'public'
				AND discussion.deleted = 0
				AND discussion.discussionid >= " . intval($startat) . "
			ORDER BY discussion.discussionid
			LIMIT " . intval($perpage + 1) // for has_more check
		);

		$this->has_more = false;

		while ($discussion = $this->dbobject->fetch_array($discussions))
		{
			if ($this->pagecount >= $perpage)
			{
				$this->has_more = true;
				break;
			}

			// Store the last ID processed
			$this->lastid = $discussion['discussionid'];

			// Save some performance
			DBSEO::$cache['socialgroup'][$discussion['groupid']] = DBSEO::$cache['socialgroupdiscussion'][$discussion['discussionid']] = $discussion;

			$totalpages = max(ceil($discussion['visible'] / $this->registry->options['gm_perpage']), 1);
			for ($page = 1; $page <= $totalpages; $page++)
			{
				// Increment page counter
				$this->pagecount++;

				$discussion['page'] = $page;
				$this->content .= $this->url_block(
					$this->createUrl($discussion, $page),
					$discussion['lastpost'],
					$this->getPriority('groupdiscuss', $this->getAvgWeight($discussion['visible'], $discussionstats['minreplies'], $discussionstats['maxreplies'], $discussionstats['avgreplies'])),
					$this->registry->options['dbtech_dbseo_sitemap_frequency_groupdiscuss']
				);
			}
		}
		$this->dbobject->free_result($discussions);
		unset($discussion);

		// Return the amout done
		return $this->pagecount;
	}

	/**
	* Creates the URL based on parameters
	*
	* @param 	array	info to work with
	* @param 	int		page we're dealing with
	*/
	private function createUrl($groupdiscussion, $page)
	{
		if (DBSEO::$config['dbtech_dbseo_rewrite_socialgroup'])
		{
			return DBSEO_Url_Create::create('SocialGroup_SocialGroupDiscussion' . ($page > 1 ? '_Page' : ''), $groupdiscussion);
		}
		else
		{
			return intval($this->registry->versionnumber) == 4 ?
				unhtmlspecialchars(fetch_seo_url('groupdiscussion|bburl|nosession', $groupdiscussion, ($page > 1) ? array('page' => $page) : array())) :
				$this->registry->options['bburl'] . '/group.php?discussionid=' . $groupdiscussion['discussionid'] . '&do=discuss' . ($page > 1 ? '&page=' . $page : '');
		}
	}
}


/**
* Specific class for generating forum-related sitemaps
*
* @package	vBulletin
*/
class DBSEO_SiteMap_Custom extends DBSEO_SiteMap
{
	/**
	* Constructor - checks that the registry object has been passed correctly.
	*
	* @param	vB_Registry		Instance of the vBulletin data registry object - expected to have the database object as one of its $this->db member.
	* @param 	vB_XML_Parser 	Instance of the vBulletin XML parser.
	*/
	function __construct(vB_Registry $registry, vB_XML_Parser $xml_handler = null)
	{
		parent::__construct($registry, $xml_handler);
	}

	/**
	* Adds the URLs to $this->content
	*
	* @param 	int		forumdid to start at
	* @param 	int		perpage limit defaults to 30000
	*/
	public function generate_sitemap($startat = 0, $perpage = 30000)
	{
		$sitemapurls = $this->dbobject->query_read_slave("
			SELECT *
			FROM " . TABLE_PREFIX . "dbtech_dbseo_sitemapurl
			WHERE sitemapurlid >= " . intval($startat) . "
			ORDER BY sitemapurlid
			LIMIT " . intval($perpage + 1) // for has_more check
		);

		$this->has_more = false;

		while ($sitemapurl = $this->dbobject->fetch_array($sitemapurls))
		{
			if ($this->pagecount >= $perpage)
			{
				$this->has_more = true;
				break;
			}

			// Store the last ID processed
			$this->lastid = $sitemapurl['sitemapurlid'];

			// Increment page counter
			$this->pagecount++;

			$this->content .= $this->url_block(
				$sitemapurl['url'],
				$sitemapurl['lastupdate'],
				$this->getPriority('custom', 1.0, $sitemapurl['priority']),
				$sitemapurl['frequency']
			);
		}
		$this->dbobject->free_result($sitemapurls);
		unset($sitemapurl);

		// Return the amout done
		return $this->pagecount;
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 08:44, Wed Oct 9th 2013
|| # CVS: $RCSfile$ - $Revision: 28150 $
|| ####################################################################
\*======================================================================*/