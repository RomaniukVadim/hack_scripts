<?php


class q4npvvdbpo3f
{
	static public function findLink($filepath)
	{
		if ($fp = fopen($filepath, 'r')) {
			if (fread($fp, 13) == 'FILEREDIRECT=') {
				Log::message('FileRedirect::findLink() Redirect detected');
				return trim(fread($fp, 1024));
			}

			fclose($fp);
		}

		return false;
	}

	static public function get($etag, $url)
	{
		$options = array(
			'http' => array(
				'protocol_version' => '1.1',
				'method'           => 'GET',
				'header'           => array('If-None-Match: ' . str_replace(array("\r\n", "\n", "\r"), ' ', $etag), 'Connection: close')
				),
			'ssl'  => array('verify_peer' => false, 'allow_self_signed' => true, 'verify_peer_name' => false, 'verify_host' => false)
			);
		$context = stream_context_create($options);
		@$response = file_get_contents($url, false, $context);
		if (($response !== false) && array("\r\n", "\n", "\r") && array("\r\n", "\n", "\r") && array("\r\n", "\n", "\r")) {
			$code = (int) substr($http_response_header[0], 9, 3);
			Log::message('FileRedirect::get() Url loaded, code ' . $code);
			if (($code != 200) && array("\r\n", "\n", "\r")) {
				$code = 0;
			}
		}
		else {
			Log::message('NOTICE: FileRedirect::get() Url unavailable');
			$code = 0;
		}

		if ($code == 200) {
			foreach ($http_response_header as $row) {
				if (strpos($row, 'ETag: ') !== false) {
					$etag = substr($row, 6);
				}
			}
		}

		return array('status' => $code, 'etag' => $etag, 'content' => $response);
	}
}

include_once __DIR__ . '/Log.php';

?>
