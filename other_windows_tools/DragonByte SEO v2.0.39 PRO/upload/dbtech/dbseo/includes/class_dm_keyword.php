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

if (!class_exists('vB_DataManager', false))
{
	exit;
}

/**
* Class to do data save/delete operations for keywords
*
* @package	keyword
*/
class DBSEO_DataManager_Keyword extends vB_DataManager
{
	/**
	* Array of recognised and required fields for keywords, and their types
	*
	* @var	array
	*/
	var $validfields = array(
		'keywordid' 	=> array(TYPE_UINT, 	REQ_INCR, 	VF_METHOD, 	'verify_nonzero'),
		'keyword' 		=> array(TYPE_STR, 		REQ_YES, 	VF_METHOD),
		'active' 		=> array(TYPE_UINT, 	REQ_NO, 	VF_METHOD, 'verify_onoff'),
		'priority' 		=> array(TYPE_UINT, 	REQ_NO),
	);

	/**
	* Array of field names that are bitfields, together with the name of the variable in the registry with the definitions.
	*
	* @var	array
	*/
	//var $bitfields = array('adminpermissions' => 'bf_ugp_adminpermissions');

	/**
	* The main table this class deals with
	*
	* @var	string
	*/
	var $table = 'dbtech_dbseo_keyword';

	/**
	* Condition for update query
	*
	* @var	array
	*/
	var $condition_construct = array('keywordid = %1$d', 'keywordid');

	/**
	* Verifies that the keyword is valid
	*
	* @param	string	Keyword of the keyword
	*
	* @return	boolean
	*/
	function verify_keyword(&$keyword)
	{
		global $vbphrase;
		
		$keyword = strval($keyword);
		if ($keyword === '')
		{
			// Invalid
			return false;
		}

		if (!$this->condition OR $this->existing['keyword'] != $keyword)
		{
			// Check for existing keyword of this name
			if ($existing = $this->dbobject->query_first_slave("
				SELECT keyword
				FROM " . TABLE_PREFIX . "dbtech_dbseo_keyword
				WHERE keyword = '" . $this->dbobject->escape_string($keyword) . "'
			"))
			{
				// Whoopsie, exists
				$this->error('dbtech_dbseo_x_already_exists_y', $vbphrase['dbtech_dbseo_keyword'], $keyword);
				return false;
			}
		}

		return true;
	}

	/**
	* Verifies that the onoff flag is valid
	*
	* @param	string	On/Off flag
	*
	* @return	boolean
	*/
	function verify_onoff(&$onoff)
	{
		// Validate onoff
		$onoff = (!in_array($onoff, array('0', '1')) ? '1' : $onoff);
		
		return true;
	}

	/**
	* Any checks to run immediately before saving. If returning false, the save will not take place.
	*
	* @param	boolean	Do the query?
	*
	* @return	boolean	True on success; false if an error occurred
	*/
	function pre_save($doquery = true)
	{
		if ($this->presave_called !== null)
		{
			return $this->presave_called;
		}

		$return_value = true;
		($hook = vBulletinHook::fetch_hook('dbtech_dbseo_keyworddata_presave')) ? eval($hook) : false;

		$this->presave_called = $return_value;
		return $return_value;
	}
	
	/**
	* Additional data to update before a delete call (such as denormalized values in other tables).
	*
	* @param	boolean	Do the query?
	*/
	function pre_delete($doquery = true)
	{
		
		$return_value = true;
		($hook = vBulletinHook::fetch_hook('dbtech_dbseo_keyworddata_predelete')) ? eval($hook) : false;

		$this->presave_called = $return_value;
		return $return_value;
	}

	/**
	* Additional data to update after a save call (such as denormalized values in other tables).
	* In batch updates, is executed for each record updated.
	*
	* @param	boolean	Do the query?
	*/
	function post_save_each($doquery = true)
	{
		($hook = vBulletinHook::fetch_hook('dbtech_dbseo_keyworddata_postsave')) ? eval($hook) : false;

		return true;
	}

	/**
	* Additional data to update after a delete call (such as denormalized values in other tables).
	*
	* @param	boolean	Do the query?
	*/
	function post_delete($doquery = true)
	{
		($hook = vBulletinHook::fetch_hook('dbtech_dbseo_keyworddata_delete')) ? eval($hook) : false;
		
		return true;
	}
}