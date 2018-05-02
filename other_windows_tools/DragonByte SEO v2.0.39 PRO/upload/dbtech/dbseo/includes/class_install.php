<?php
/*======================================================================*\
|| #################################################################### ||
|| # ---------------------------------------------------------------- # ||
|| # Copyright ©2013 Fillip Hannisdal AKA Revan/NeoRevan/Belazor 	  # ||
|| # All Rights Reserved. 											  # ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------------------------------------------------------- # ||
|| # You are not allowed to use this on your server unless the files  # ||
|| # you downloaded were done so with permission.					  # ||
|| # ---------------------------------------------------------------- # ||
|| #################################################################### ||
\*======================================================================*/

@set_time_limit(0);
ignore_user_abort(1);


// #############################################################################
// Core installer class

/**
* Handles everything to do with Activity.
*/
class DBTech_Install
{
	/**
	* The vB_Database_Alter_MySQL object
	*
	* @public	vB_Database_Alter_MySQL
	*/		
	protected static $db_alter = NULL;
	
	/**
	* The vB_Registry object
	*
	* @public	vB_Registry
	*/		
	protected static $vbulletin = NULL;
	
	/**
	* The vB_Database object
	*
	* @public	vB_Database
	*/		
	protected static $db = NULL;
	
	/**
	* Path to the installer files
	*
	* @protected	string
	*/		
	protected static $installpath = '';
	
	/**
	* Product ID
	*
	* @protected	string
	*/		
	protected static $productid = '';
	
	protected static $hightrafficengine = '';
	
	/**
	* Initialises the installer and ensures we have
	* all the classes we need
	*
	* @param	string	Path to the installer files
	* @param	string	Product ID we are setting
	*/
	public static function init($installpath, $productid = '')
	{
		global $db, $vbulletin;
		
		if (!is_dir($installpath))
		{
			// Wrong install path
			self::error($installpath . ' is not a directory. Please upload all files that came with the product.');
		}
		
		// Install path is valid
		self::$installpath = $installpath;
		
		if (!file_exists(DIR . '/includes/xml/bitfield_' . $productid . '.xml') AND $productid)
		{
			// Missing bitfields
			self::error('bitfield_' . $productid . '.xml not found in directory ' . DIR . '/includes/xml/');
		}
		
		// Get the high concurrency table engine (innodb support check)
		self::get_high_concurrency_table_engine($db);
		
		// Grab the DBAlter class
		require_once(DIR . '/includes/class_dbalter.php');
		
		// Set some important variables
		self::$db_alter = new vB_Database_Alter_MySQL($db);
		self::$db =& $db;
		self::$vbulletin =& $vbulletin;
		self::$productid = $productid;
	}
	
	/**
	* Installs / Upgrades to a version
	*
	* @param	integer	Version number to install
	* @param	string	Textual representation of the version
	*/
	public static function install($version, $fullversion)
	{
		global $vbulletin, $code, $arr;
		
		// To avoid any h4x0rz
		//$version = intval($version);
		
		if (!file_exists(self::$installpath . '/' . $version . '.php'))
		{
			// Missing version file
			self::error($version . '.php not found in directory ' . self::$installpath);
		}
		
		// Run the install
		self::report('<strong>Updating Version Number To</strong>:', $fullversion, 'finalise');
		
		echo '<ul>';
		require_once(self::$installpath . '/' . $version . '.php');
		echo '</ul>';
		
		if (self::$productid)
		{
			$shortname = self::$productid;
			
			require_once(DIR . '/includes/class_bitfield_builder.php');
			if (vB_Bitfield_Builder::build(false) !== false)
			{
				$myobj =& vB_Bitfield_Builder::init();
				$myobj->data = $myobj->fetch(DIR . '/includes/xml/bitfield_' . $shortname . '.xml', false, true);
			}
			else
			{
				echo "<strong>error</strong>\n";
				print_r(vB_Bitfield_Builder::fetch_errors());
			}
			
			
			$groupinfo = array();
			foreach ((array)$myobj->data['ugp']["{$shortname}permissions"] AS $permtitle => $permvalue)
			{
				if (empty($permvalue['group']))
				{
					continue;
				}
			
				if (!empty($permvalue['install']))
				{
					foreach ($permvalue['install'] AS $gid)
					{
						$groupinfo["$gid"]["{$shortname}permissions"] += $permvalue['value'];
					}
				}
			}
			
			foreach ($groupinfo as $usergroupid => $permissions)
			{
				$perms = $permissions["{$shortname}permissions"];
				self::$db->query_write("
					UPDATE " . TABLE_PREFIX . "usergroup
					SET {$shortname}permissions = $perms
					WHERE usergroupid = $usergroupid
				");
			}
			build_forum_permissions();
		}
		
		// Update settings
		build_options();
		vBulletinHook::build_datastore(self::$db);		
	}
	
	/**
	* Uninstalls the product
	*/
	public static function uninstall()
	{
		if (!file_exists(self::$installpath . '/uninstall.php'))
		{
			// Missing version file
			self::error('uninstall.php not found in directory ' . self::$installpath);
		}
		
		echo '<ul>';
		require_once(self::$installpath . '/uninstall.php');
		echo '</ul>';
	}
	
	/**
	* Print out an informational notice
	*
	* @param	string	What we were doing
	* @param	string	What the action returned
	* @param	string	What type of message we are printing
	*/
	public static function report($action, $message, $type = 'action')
	{
		if ($type == 'action')
		{
			// During install
			echo '<li><strong>' . $action . ':</strong> <em>' . TABLE_PREFIX . $message . '</em></li>';
		}
		else
		{
			// Finalise
			echo '<p>' . $action . ' ' . $message . '</p>';
		}
		
		vbflush();
		usleep(500000);
	}

	/**
	* Something went boom, print a message.
	*
	* @param	string	Error message
	*/
	public static function error($message = '')
	{
		print_dots_stop();
		print_cp_message('<strong>Installation Failed</strong><br />Sorry, the product encountered an error during installation. More information is provided below to help address the issue.<br /><br />' . $message);
	}
	
	/**
	* Determine if we can use InnoDB
	*
	* @param	string	Error message
	*/
	public static function get_high_concurrency_table_engine($db)
	{
		if (self::$hightrafficengine)
		{
			return self::$hightrafficengine;
		}
		
		if (defined('SKIPDB'))
		{
			self::$hightrafficengine = 'MyISAM';
			return self::$hightrafficengine;
		}
	
		$set = $db->query('SHOW ENGINES');
	
		while ($row = $db->fetch_array($set))
		{
			if (
				strcasecmp($row['Engine'], 'innodb') == 0 AND
				(
					(strcasecmp($row['Support'], 'yes') == 0) OR
					(strcasecmp($row['Support'], 'default') == 0)
				)
			)
			{
				self::$hightrafficengine = 'InnoDB';
				return self::$hightrafficengine;
			}
	
		}
		
		self::$hightrafficengine = 'MyISAM';
		return self::$hightrafficengine;
	}	
}