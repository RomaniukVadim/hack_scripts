<?php

class te0cri8u4ti9
{
	public $sessionKey;
	private $privateKey;
	public $hashSize = 32;
	public $hashAlgo = 'sha256';

	public function __construct($keyFile = NULL)
	{
		if ($keyFile) {
			$this->addPrivateKey($keyFile);
		}
	}

	public function addPrivateKey($keyFile)
	{
		return $this->privateKey = openssl_get_privatekey(file_get_contents($keyFile));
	}

	public function hash(&$data)
	{
		if ($this->hashAlgo == 'sha256') {
			return hash($this->hashAlgo, $data);
		}
		else if ($this->hashAlgo == 'sha512') {
			return substr(hash($this->hashAlgo, $data), 0, 32);
		}
	}

	public function checkHash(&$data, $url)
	{
		$hash = unpack('H*', substr($data, 0, $this->hashSize))[1];
		$baseBlock = substr($data, $this->hashSize);
		$buf = $url . $baseBlock;
		$calcHash = $this->hash($buf);
		$result = $hash == $calcHash;
		Log::message('Check hash: ' . (int) $result . ' (received=' . $hash . ', calculated=' . $calcHash . ')');
		return $result;
	}

	public function extractSessionKey($rsaBlock)
	{
		$st = openssl_private_decrypt($rsaBlock, $decrypt, $this->privateKey);

		if ($st) {
			$this->sessionKey = $decrypt;
		}

		Log::message('RSA decrypt, extract session key: ' . (int) $st . ' (session key=' . unpack('H*', $decrypt)[1] . ')');
		return $decrypt;
	}

	public function decryptAES(&$data)
	{
		$iv = substr($data, 0, 16);
		$result = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $this->sessionKey, substr($data, 16), MCRYPT_MODE_CBC, $iv);
		$zero = strpos(substr($result, strlen($result) - 16), "\0");

		if ($zero !== false) {
			$result = substr($result, 0, (strlen($result) - 16) + $zero);
		}

		$result = rtrim($result, "\0");
		Log::message('AES decrypt: key=' . unpack('H*', $this->sessionKey)[1] . ', iv=' . unpack('H*', $iv)[1]);
		return $result;
	}

	public function encryptAES(&$data)
	{
		$ivSize = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
		$iv = mcrypt_create_iv($ivSize, MCRYPT_RAND);
		$result = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $this->sessionKey, $data, MCRYPT_MODE_CBC, $iv);
		Log::message('AES encrypt: key=' . unpack('H*', $this->sessionKey)[1] . ', iv=' . unpack('H*', $iv)[1]);
		return $iv . $result;
	}

	public function decrypt(&$data)
	{
		Log::message('Cryptography::decrypt()');

		if (!$this->extractSessionKey(substr($data, $this->hashSize, 256))) {
			return NULL;
		}

		$baseData = substr($data, $this->hashSize + 256);
		return $this->decryptAES($baseData);
	}

	public function encrypt(&$data)
	{
		Log::message('Cryptography::encrypt()');
		$dataEncrypt = $this->encryptAES($data);
		$hash = pack('H*', $this->hash($dataEncrypt));
		return $hash . $dataEncrypt;
	}

	static public function generateKeys()
	{
		$config = array('private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA, 'digest_alg' => 'sha512');
		$keys = openssl_pkey_new($config);

		if (!$keys) {
			return NULL;
		}

		openssl_pkey_export($keys, $private);
		$public = openssl_pkey_get_details($keys);
		return array('public' => $public, 'private' => $private);
	}
}

include_once __DIR__ . '/Log.php';

?>
