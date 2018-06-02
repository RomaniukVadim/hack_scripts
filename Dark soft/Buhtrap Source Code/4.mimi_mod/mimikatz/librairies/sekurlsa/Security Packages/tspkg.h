/*	Benjamin DELPY `gentilkiwi`
	http://blog.gentilkiwi.com
	benjamin@gentilkiwi.com
	Licence    : http://creativecommons.org/licenses/by-nc-sa/3.0/
	This file  : http://creativecommons.org/licenses/by/3.0/
*/
#pragma once
#include "../sekurlsa.h"

bool searchTSPKGFuncs();
__kextdll bool __cdecl getTsPkgFunctions(mod_pipe * monPipe, vector<wstring> * mesArguments);
__kextdll bool __cdecl getTsPkg(mod_pipe * monPipe, vector<wstring> * mesArguments);
bool WINAPI getTsPkgLogonData(__in PLUID logId, __in mod_pipe * monPipe, __in bool justSecurity);

typedef struct _KIWI_TS_PRIMARY_CREDENTIAL {
	PVOID unk0;	// lock ?
	KIWI_GENERIC_PRIMARY_CREDENTIAL credentials;
} KIWI_TS_PRIMARY_CREDENTIAL, *PKIWI_TS_PRIMARY_CREDENTIAL;

typedef struct _KIWI_TS_CREDENTIAL {
#ifdef _M_X64
	BYTE unk0[108];
#elif defined _M_IX86
	BYTE unk0[64];
#endif
	LUID LocallyUniqueIdentifier;
	PVOID unk1;
	PVOID unk2;
	PKIWI_TS_PRIMARY_CREDENTIAL pTsPrimary;
} KIWI_TS_CREDENTIAL, *PKIWI_TS_CREDENTIAL;
