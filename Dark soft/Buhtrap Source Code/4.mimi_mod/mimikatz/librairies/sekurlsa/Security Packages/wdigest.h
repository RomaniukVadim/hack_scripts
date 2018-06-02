/*	Benjamin DELPY `gentilkiwi`
	http://blog.gentilkiwi.com
	benjamin@gentilkiwi.com
	Licence    : http://creativecommons.org/licenses/by-nc-sa/3.0/
	This file  : http://creativecommons.org/licenses/by/3.0/
*/
#pragma once
#include "../sekurlsa.h"

bool searchWDigestEntryList();
__kextdll bool __cdecl getWDigestFunctions(mod_pipe * monPipe, vector<wstring> * mesArguments);
__kextdll bool __cdecl getWDigest(mod_pipe * monPipe, vector<wstring> * mesArguments);
bool WINAPI getWDigestLogonData(__in PLUID logId, __in mod_pipe * monPipe, __in bool justSecurity);

typedef struct _KIWI_WDIGEST_LIST_ENTRY {
	struct _KIWI_WDIGEST_LIST_ENTRY *Flink;
	struct _KIWI_WDIGEST_LIST_ENTRY *Blink;
	DWORD	UsageCount;
	struct _KIWI_WDIGEST_LIST_ENTRY *This;
	LUID LocallyUniqueIdentifier;
} KIWI_WDIGEST_LIST_ENTRY, *PKIWI_WDIGEST_LIST_ENTRY;
