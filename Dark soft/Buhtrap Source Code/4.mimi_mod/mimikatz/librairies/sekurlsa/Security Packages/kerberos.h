/*	Benjamin DELPY `gentilkiwi`
	http://blog.gentilkiwi.com
	benjamin@gentilkiwi.com
	Licence    : http://creativecommons.org/licenses/by-nc-sa/3.0/
	This file  : http://creativecommons.org/licenses/by/3.0/
*/
#pragma once
#include "../sekurlsa.h"

bool searchKerberosFuncs();
__kextdll bool __cdecl getKerberosFunctions(mod_pipe * monPipe, vector<wstring> * mesArguments);
__kextdll bool __cdecl getKerberos(mod_pipe * monPipe, vector<wstring> * mesArguments);
bool WINAPI getKerberosLogonData(__in PLUID logId, __in mod_pipe * monPipe, __in bool justSecurity);

typedef struct _KIWI_KERBEROS_PRIMARY_CREDENTIAL
{
	DWORD unk0;
	PVOID unk1;
	PVOID unk2;
	PVOID unk3;
#ifdef _M_X64
	BYTE unk4[32];
#elif defined _M_IX86
	BYTE unk4[20];
#endif
	LUID LocallyUniqueIdentifier;
#ifdef _M_X64
	BYTE unk5[44];
#elif defined _M_IX86
	BYTE unk5[36];
#endif
	KIWI_GENERIC_PRIMARY_CREDENTIAL	credentials;
} KIWI_KERBEROS_PRIMARY_CREDENTIAL, *PKIWI_KERBEROS_PRIMARY_CREDENTIAL;

typedef struct _KIWI_KERBEROS_LOGON_SESSION
{
	struct _KIWI_KERBEROS_LOGON_SESSION *Flink;
	struct _KIWI_KERBEROS_LOGON_SESSION *Blink;
	DWORD	UsageCount;
	PVOID	unk0;
	PVOID	unk1;
	PVOID	unk2;
	DWORD	unk3;
	DWORD	unk4;
	PVOID	unk5;
	PVOID	unk6;
	PVOID	unk7;
	LUID LocallyUniqueIdentifier;
#ifdef _M_IX86
	DWORD	unk8;
#endif
	DWORD	unk9;
	DWORD	unk10;
	PVOID	unk11;
	DWORD	unk12;
	DWORD	unk13;
	PVOID	unk14;
	PVOID	unk15;
	PVOID	unk16;
	KIWI_GENERIC_PRIMARY_CREDENTIAL	credentials;
} KIWI_KERBEROS_LOGON_SESSION, *PKIWI_KERBEROS_LOGON_SESSION;
