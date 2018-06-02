/*

	machineid.cpp
	Compatible machine id calculation

*/


#include <windows.h>

#include "mem.h"
#include "dbg.h"
#include "CryptoStrings.h"

#include "machineid.h"


// perform rol operation on 32-bit argument
static DWORD rol(DWORD dwArg, BYTE bPlaces)
{
    return ( (dwArg<<bPlaces)|(dwArg>>(32-bPlaces)) );
}

// make dword hash from string
DWORD _myHashStringW(LPWSTR wszString) 
{
    DWORD dwResult = 0;	// output result, temp hash value
    BYTE b_cr = 0;	// cr shift value
    ULONG i = 0;	// counter
	WORD *pwChar = (WORD *)wszString;

    // loop passed string
	while (*pwChar) {

        // make step's shift value, normalized to 4-byte shift (31 max)
		b_cr = (b_cr ^ (BYTE)(*pwChar)) & 0x1F;

        // xor hash with current char and rol hash, cr
		dwResult = rol(dwResult ^ (BYTE)(*pwChar), b_cr);

		pwChar++;

    }	// while !null char


    // output result
    return dwResult;
}

/*
	internal func
	Calculates hash for name of first physical disk
	HKEY_LOCAL_MACHINE\SYSTEM\CurrentControlSet\services\Disk\Enum, value name "0"
*/
DWORD _hwsFirstVolumeModelHash()
{
	DWORD dwRes = 0;	// func res
	HKEY hKey = NULL;	// RegOpenKeyEx() res
	LPWSTR wszSubkey, wszParamName;	// decrypt string buff
	DWORD dwDataLen = 0;	// key len
	LPWSTR wszBuff = NULL;	// key buff

	wszSubkey = CRSTRW("SYSTEM\\CurrentControlSet\\services\\Disk\\Enum", "\xfd\xdf\x1c\x06\xd6\xdf\x7c\x64\xed\xc0\x0a\x10\xc2\xf7\x1a\x0f\x0c\x31\xe1\xe9\x1d\x1b\xc1\xc9\x4c\x7b\xa3\x8e\x7b\x40\xb3\x8e\x9b\xa6\x79\x74\xbd\x91\x5c\x61\xfa\xfd\x3c\x36\xc2\xf1\x01\x08\x13\x28\x38");
	wszParamName = CRSTRW("0", "\xfe\x5f\x85\x07\xff\x5f\x86");

	do {	// not a loop

		if (ERROR_SUCCESS != RegOpenKeyEx(HKEY_LOCAL_MACHINE, wszSubkey, 0, KEY_READ, &hKey)) { DbgPrint("ERR: RegOpenKeyEx() failed %04Xh", GetLastError()); break; }

		// key opened ok, query value
		//if (ERROR_SUCCESS != RegQueryValueEx(hKey, wszParamName, NULL, NULL, NULL, &dwDataLen)) { DbgPrint("ERR: RegQueryValueEx() failed %04Xh", GetLastError()); break; }
		// 21-aug-2015: nod32: Win32/RA-based.NCM removed by commenting this line and setting a fixed buffer's length

		dwDataLen = 4096 * 2;

		// alloc buff
		if (!(wszBuff = (LPWSTR)my_alloc((dwDataLen + 1) * 2))) { DbgPrint("ERR: failed to alloc %u bytes", ((dwDataLen + 1) * 2) ); break; }
		
		if (ERROR_SUCCESS != RegQueryValueEx(hKey, wszParamName, NULL, NULL, (LPBYTE)wszBuff, &dwDataLen)) { DbgPrint("ERR: RegQueryValueEx() failed %04Xh", GetLastError()); break; }

		// calc hash and store it
		dwRes = _myHashStringW(wszBuff);

	} while (FALSE);	// not a loop

	if (wszBuff) { my_free(wszBuff); }
	if (hKey) { RegCloseKey(hKey); }

	my_free(wszParamName);
	my_free(wszSubkey);

	return dwRes;
}

// main magic is done here
UINT64 i64MakeMachineID()
{
	ULONG iBufferSize = MAX_COMPUTERNAME_LENGTH + 1;
    DWORD dwHash2 = 0;

	// current implementation does not require machine name in id
#ifdef USE_COMPNAME_IN_MACHINEID

	LPWSTR wszCompName;	// buffer for computer's name

    // and part2 using Computer name
	wszCompName = (LPWSTR)my_alloc(iBufferSize * 2);
		GetComputerName(wszCompName, &iBufferSize);
		dwHash2 = _myHashStringW(wszCompName);
	my_free(wszCompName);
#endif

    // dwHash1 & dwHash2 now contain the resulting parts of machine id hash
    return (UINT64)( ((UINT64)_hwsFirstVolumeModelHash() << 32) | (UINT64)dwHash2 );

} // func end

