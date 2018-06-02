/*
  	Prepares encrypted structure with hash field as remote side expects

	MD5HASH_SIZE 16
   typedef struct
  {
    BYTE randData[20];                // random data
    DWORD size;                       // full size, header + appended data
    DWORD flags;                      // flags
    DWORD count;                      // opt count
    BYTE md5Hash[MD5HASH_SIZE]; 	  // hash of data appended, to check decryption status
  }STORAGE;
*/
function formAnswerBuffer($plain_data)
{
	global $key;

	@ob_end_clean();

	// calc md5 hash
    $hash = md5($plain_data, TRUE);

	// form resulting plain chunk
	$chunk = rnd_string(20).pack("VVV", strlen($plain_data)+48, 0, 0).$hash.$plain_data;

	// do encryption
 	visualEncrypt($chunk);
	$key_bin = rc4Init($key);
	rc4($chunk, $key_bin);

   	// output answer to user stream
	echo $chunk;
}
