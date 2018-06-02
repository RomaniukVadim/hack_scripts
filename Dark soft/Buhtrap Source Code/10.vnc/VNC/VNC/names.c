//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: names.c
// $Revision: 166 $
// $Date: 2014-02-14 19:47:48 +0400 (Пт, 14 фев 2014) $
// description:
//	Pseudo-random names generation engine.

#include "project.h"
#include "rt\str.h"

#define tczGuidStrTempl	_T("{%08X-%04X-%04X-%04X-%08X%04X}")
#define tczGuidStrTemp2	_T("%08X-%04X-%04X-%04X-%08X%04X")

#define	tczRoot			_T("\\\\.\\%s\\")


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//	Generates pseudo random number based on the specified seed value.
//
static ULONG MyRandom(PULONG pSeed)
{
	return(*pSeed = 1664525*(*pSeed)+1013904223);
}


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//	Generates a GUID based on the specified seed value. The same seeds will create the same GUIDs on the same machine.
//
VOID GenGuid(GUID* pGuid, PULONG pSeed)
{
	ULONG i;
	pGuid->Data1 = MyRandom(pSeed);
	pGuid->Data2 = (USHORT)MyRandom(pSeed);
	pGuid->Data3 = (USHORT)MyRandom(pSeed);
	for (i=0; i<8; i++)
		pGuid->Data4[i] = (UCHAR)MyRandom(pSeed);
}


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//	Converts the specified GUID structure into 0-terminated string.
//
LPTSTR GuidToString(GUID* pGuid, BOOL bQuoted)
{
	LPTSTR TempStr, GuidStr = (LPTSTR)hAlloc((GUID_STR_LENGTH + 1)*sizeof(_TCHAR));

	if (bQuoted)
		TempStr = tczGuidStrTempl;
	else
		TempStr = tczGuidStrTemp2;

	if (GuidStr)
		wsprintf(GuidStr, TempStr, 
		htonL(pGuid->Data1),
		htonS(pGuid->Data2),
		htonS(pGuid->Data3), 
		htonS(*(USHORT*)&pGuid->Data4[0]),
		htonL(*(ULONG*)&pGuid->Data4[2]),
		htonS(*(USHORT*)&pGuid->Data4[6])
		);

	return(GuidStr);
}

//
//	Fills the specified pGuidName with generated GUID value based on the specified pSeed.
//
VOID FillGuidName(
	IN OUT	PULONG	pSeed,
	OUT		LPTSTR	pGuidName
	)
{
	GUID	Guid;
	ULONG	bSize;

	GenGuid(&Guid, pSeed);
	bSize = wsprintf(pGuidName, tczGuidStrTempl, Guid.Data1, Guid.Data2, Guid.Data3, *(USHORT*)&Guid.Data4[0], *(ULONG*)&Guid.Data4[2],  *(USHORT*)&Guid.Data4[6]);
	ASSERT(bSize <= GUID_STR_LENGTH);
}



//
//	Generates a string containig the Prefix, random GUID based on spesified Seed, and the Postfix.
//
LPTSTR GenGuidName(
	IN OUT	PULONG	pSeed,					// pointer to a random seed value
	IN		LPTSTR	Prefix OPTIONAL,		// pointer to a prefix string (optional)
	IN		LPTSTR	Postfix OPTIONAL,		// pointer to a postfix string (optional)
	IN		BOOL	bQuoted
	)
{
	ULONG	NameLen = GUID_STR_LENGTH + 1;
	LPTSTR	GuidStr, Name = NULL;
	GUID	Guid;

	GenGuid(&Guid, pSeed);
	if (GuidStr = GuidToString(&Guid, bQuoted))
	{
		if (Prefix)
			NameLen += lstrlen(Prefix);
		if (Postfix)
			NameLen += lstrlen(Postfix);

		if (Name = (LPTSTR)hAlloc(NameLen*sizeof(_TCHAR)))
		{
			Name[0] = 0;

			if (Prefix)
				lstrcpy(Name, Prefix);
		
			lstrcat(Name, GuidStr);
			if (Postfix)
				lstrcat(Name, Postfix);
		}
		hFree(GuidStr);
		
	}	// if (GuidStr = 
	return(Name);
}

// decorates object name: NAME->{DESKTOP_GUID}_NAME
LPWSTR DecorateNameW(PVNC_DESKTOP pDesktop, LPWSTR szName)
{
	LPWSTR szNewName = szName;
	DWORD NameLen;

	if ( szName )
	{
		// check prefix
		if ( StrCompareNIWA( szName, pDesktop->Name, pDesktop->NameLength ) != 0 )
		{
			NameLen = lstrlenW(szName);
			szNewName = hAlloc((pDesktop->NameLength+NameLen+2)*sizeof(WCHAR)); //_ and \0
			if ( szNewName == NULL ){
				SetLastError(ERROR_NOT_ENOUGH_MEMORY);
				return NULL;
			}
			wsprintfW(szNewName,L"%S_%s",pDesktop->Name,szName);
		}
	}
	DbgPrint("%S->%S\n",szName,szNewName);
	return szNewName;
}

LPSTR DecorateNameA(PVNC_DESKTOP pDesktop, LPSTR szName)
{
	LPSTR szNewName = szName;
	DWORD NameLen;

	if ( szName )
	{
		// check prefix
		if ( _strnicmp( szName, pDesktop->Name, pDesktop->NameLength ) != 0 )
		{
			NameLen = lstrlenA(szName);
			szNewName = hAlloc((pDesktop->NameLength+NameLen+2)*sizeof(CHAR)); //_ and \0
			if ( szNewName == NULL ){
				SetLastError(ERROR_NOT_ENOUGH_MEMORY);
				return NULL;
			}
			wsprintfA(szNewName,"%s_%s",pDesktop->Name,szName);
		}
	}
	DbgPrint("%s->%s\n",szName,szNewName);
	return szNewName;
}

// decorates desktop name 
// NAME->{DESKTOP_GUID}_NAME
// Default->{DESKTOP_GUID}
LPWSTR DecorateDesktopNameW(PVNC_DESKTOP pDesktop, LPWSTR szName)
{
	LPWSTR szNewName = szName;
	DWORD NameLen;

	if ( szName )
	{
		if ( StrCompareNIWA( szName, pDesktop->Name, pDesktop->NameLength ) != 0 )
		{
			NameLen = lstrlenW(szName);
			szNewName = hAlloc((pDesktop->NameLength+NameLen+2)*sizeof(WCHAR)); //_ and \0
			if ( szNewName == NULL ){
				SetLastError(ERROR_NOT_ENOUGH_MEMORY);
				return NULL;
			}
			wsprintfW(szNewName,L"%S",pDesktop->Name);
			if ( lstrcmpW(szName,L"Default") != 0 ){
				lstrcatW(szNewName,L"_");
				lstrcatW(szNewName,szName);
			}
		}
	}
	DbgPrint("%S->%S\n", szName,szNewName);
	return szNewName;
}

LPSTR DecorateDesktopNameA(PVNC_DESKTOP pDesktop, LPSTR szName)
{
	LPSTR szNewName = szName;
	DWORD NameLen;

	if ( szName )
	{
		if ( _strnicmp( szName, pDesktop->Name, pDesktop->NameLength ) != 0 )
		{
			NameLen = lstrlenA(szName);
			szNewName = hAlloc(pDesktop->NameLength+NameLen+2); //_ and \0
			if ( szNewName == NULL ){
				SetLastError(ERROR_NOT_ENOUGH_MEMORY);
				return NULL;
			}
			lstrcpyA(szNewName,pDesktop->Name);
			if ( lstrcmpA(szName,"Default") != 0 ){
				lstrcatA(szNewName,"_");
				lstrcatA(szNewName,szName);
			}
		}
	}
	DbgPrint("%s->%s\n", szName,szNewName);
	return szNewName;
}

LPSTR UndcorateDesktopNameA(PVNC_DESKTOP pDesktop, LPSTR szName)
{
	LPSTR szNewName = szName;
	BOOL bDefault = FALSE;

	if ( szName )
	{
		DWORD NameLen = lstrlenA(szName);
		if ( NameLen >= pDesktop->NameLength )
		{
			if ( lstrcmpiA(szName,pDesktop->Name) == 0 ){
				bDefault = TRUE;
			}if ( _strnicmp(szName,pDesktop->Name,pDesktop->NameLength) == 0 ){
				if ( szName[pDesktop->NameLength] == '_' ){
					if ( NameLen >= pDesktop->NameLength + 1 )
					{
						if ( szName[pDesktop->NameLength+1] == '\0' ){
							bDefault = TRUE;
						}else{
							lstrcpyA(szName,szName+pDesktop->NameLength+1);
						}
					}else{
						bDefault = TRUE;
					}
				}
			}
		}
		// bug with explorer showing the "computer" page on the main desktop
		if ( bDefault )
		{
			// <win8 (for win8 bug is not reproduced)
			// if u launch explorer.exe process (from run menu, of by
			// clicking folder icon on the task bar)
			// it detects that the main explorer is already running
			// and tries to call main explorer using IExplorerHost interface
			// ole32 has special code that detects the desktop name
			// that current thread is running on and then call explorer on the same desktop (i'm not sure
			// but it looks like that)
			//                  user32!GetUserObjectInformationW
			//0241e564 76ee1749 ole32!CRpcResolver::GetThreadWinstaDesktop+0x87
			//0241ea4c 76ee1310 ole32!CRpcResolver::GetConnection+0xc2
			//0241eca0 76ecfaa2 ole32!CoInitializeSecurity+0x78
			//0241ecd0 76fed1e2 ole32!InitializeSecurity+0x3b
			//0241ecec 76ee768c ole32!ChannelProcessInitialize+0x16f
			//0241ed20 76ee760a ole32!CComApartment::InitRemoting+0xac
			//0241ed2c 76fed83e ole32!CComApartment::StartServer+0x13
			//0241ed3c 76eed0ff ole32!InitChannelIfNecessary+0x1e
			//0241ed4c 76eedfd1 ole32!CRpcResolver::BindToSCMProxy+0xe
			//0241edb0 76eee1b9 ole32!CRpcResolver::CreateInstance+0x74
			//0241f00c 76ef561a ole32!CClientContextActivator::CreateInstance+0x11f
			//0241f04c 76ef5542 ole32!ActivationPropertiesIn::DelegateCreateInstance+0x108
			//0241f828 76f05a26 ole32!ICoCreateInstanceEx+0x404
			//0241f888 76f05987 ole32!CComActivator::DoCreateInstance+0xd9
			//0241f8ac 76f05940 ole32!CoCreateInstanceEx+0x38
			//0241f8dc 71f25dbf ole32!CoCreateInstance+0x37
			//0241f900 71f1c699 EXPLORERFRAME!CExplorerLauncher::ShowWindowInHost+0x2f
			//0241f940 71f1c903 EXPLORERFRAME!CExplorerLauncher::ShowWindowUsingDefaultPolicy+0x64
			//0241f990 71f1c835 EXPLORERFRAME!CExecuteCommandBase::_ShowFolder+0xac
			//0241f9b8 75fff6ad EXPLORERFRAME!CSearchMSExecute::Execute+0x6d
			//0241f9d8 7612b542 SHELL32!CExecuteAssociation::_DoCommand+0x88
			//0241fa14 75fff716 SHELL32!CExecuteAssociation::_TryDelegate+0x86
			//0241fa30 75fe5305 SHELL32!CExecuteAssociation::Execute+0x46
			//0241fa5c 75ff1cb8 SHELL32!CShellExecute::_ExecuteAssoc+0x8c
			//0241fa78 75ff1ee6 SHELL32!CShellExecute::_DoExecute+0x89
			//0241fa8c 76d646bc SHELL32!CShellExecute::s_ExecuteThreadProc+0x30
			//0241fb14 770d1154 SHLWAPI!WrapperThreadProc+0x1b5
			// so if we return Default, it'll call explorer on the host

			//lstrcpyA(szName,"Default");
		}
	}
	return szName;
}

LPWSTR UndcorateDesktopNameW(PVNC_DESKTOP pDesktop, LPWSTR szName)
{
	LPWSTR szNewName = szName;
	BOOL bDefault = FALSE;

	if ( szName )
	{
		DWORD NameLen = lstrlenW(szName);
		if ( NameLen >= pDesktop->NameLength )
		{
			if ( StrCompareNIWA( szName, pDesktop->Name, pDesktop->NameLength ) == 0 )
			{
				bDefault = (NameLen == pDesktop->NameLength);
				if ( szName[pDesktop->NameLength] == '_' ){
					if ( NameLen >= pDesktop->NameLength + 1 )
					{
						if ( szName[pDesktop->NameLength+1] == '\0' ){
							bDefault = TRUE;
						}else{
							lstrcpyW(szName,szName+pDesktop->NameLength+1);
						}
					}else{
						bDefault = TRUE;
					}
				}
			}
		}
		// bug with explorer showing the "computer" page on the main desktop
		if ( bDefault )
		{
			// <win8 (for win8 bug is not reproduced)
			// if u launch explorer.exe process (from run menu, of by
			// clicking folder icon on the task bar)
			// it detects that the main explorer is already running
			// and tries to call main explorer using IExplorerHost interface
			// ole32 has special code that detects the desktop name
			// that current thread is running on and then call explorer on the same desktop (i'm not sure
			// but it looks like that)
			//                  user32!GetUserObjectInformationW
			//0241e564 76ee1749 ole32!CRpcResolver::GetThreadWinstaDesktop+0x87
			//0241ea4c 76ee1310 ole32!CRpcResolver::GetConnection+0xc2
			//0241eca0 76ecfaa2 ole32!CoInitializeSecurity+0x78
			//0241ecd0 76fed1e2 ole32!InitializeSecurity+0x3b
			//0241ecec 76ee768c ole32!ChannelProcessInitialize+0x16f
			//0241ed20 76ee760a ole32!CComApartment::InitRemoting+0xac
			//0241ed2c 76fed83e ole32!CComApartment::StartServer+0x13
			//0241ed3c 76eed0ff ole32!InitChannelIfNecessary+0x1e
			//0241ed4c 76eedfd1 ole32!CRpcResolver::BindToSCMProxy+0xe
			//0241edb0 76eee1b9 ole32!CRpcResolver::CreateInstance+0x74
			//0241f00c 76ef561a ole32!CClientContextActivator::CreateInstance+0x11f
			//0241f04c 76ef5542 ole32!ActivationPropertiesIn::DelegateCreateInstance+0x108
			//0241f828 76f05a26 ole32!ICoCreateInstanceEx+0x404
			//0241f888 76f05987 ole32!CComActivator::DoCreateInstance+0xd9
			//0241f8ac 76f05940 ole32!CoCreateInstanceEx+0x38
			//0241f8dc 71f25dbf ole32!CoCreateInstance+0x37
			//0241f900 71f1c699 EXPLORERFRAME!CExplorerLauncher::ShowWindowInHost+0x2f
			//0241f940 71f1c903 EXPLORERFRAME!CExplorerLauncher::ShowWindowUsingDefaultPolicy+0x64
			//0241f990 71f1c835 EXPLORERFRAME!CExecuteCommandBase::_ShowFolder+0xac
			//0241f9b8 75fff6ad EXPLORERFRAME!CSearchMSExecute::Execute+0x6d
			//0241f9d8 7612b542 SHELL32!CExecuteAssociation::_DoCommand+0x88
			//0241fa14 75fff716 SHELL32!CExecuteAssociation::_TryDelegate+0x86
			//0241fa30 75fe5305 SHELL32!CExecuteAssociation::Execute+0x46
			//0241fa5c 75ff1cb8 SHELL32!CShellExecute::_ExecuteAssoc+0x8c
			//0241fa78 75ff1ee6 SHELL32!CShellExecute::_DoExecute+0x89
			//0241fa8c 76d646bc SHELL32!CShellExecute::s_ExecuteThreadProc+0x30
			//0241fb14 770d1154 SHLWAPI!WrapperThreadProc+0x1b5
			// so if we return Default, it'll call explorer on the host
			//lstrcpyW(szName,L"Default");
		}
	}
	return szName;
}

LPSTR DecorateDeskWinstaNameA(PVNC_DESKTOP pDesktop, LPSTR szName)
{
	LPSTR szNewName = szName;
	LPSTR ptr;
	int Offset;

	if ( szName )
	{
		DWORD NameLen = lstrlenA(szName);

		if ( ptr = strrchr(szName,'\\') )
		{
			Offset = (int)(ptr-szName)+1;
			if ( _strnicmp(szName+Offset,pDesktop->Name, pDesktop->NameLength ) != 0 )
			{
				szNewName = hAlloc(pDesktop->NameLength+NameLen+2); //_ and \0
				if ( szNewName == NULL ){
					SetLastError(ERROR_NOT_ENOUGH_MEMORY);
					return NULL;
				}

				memcpy(szNewName,szName,Offset);
				lstrcpyA(szNewName+Offset,pDesktop->Name);
				if ( lstrcmpiA(szName+Offset,"Default") != 0 )
				{
					lstrcatA(szNewName,"_");
					lstrcatA(szNewName,szName+Offset);
				}
			}
		}else{
			szNewName = DecorateDesktopNameA(pDesktop,szName);
		}
	}
	DbgPrint("%s->%s\n", szName,szNewName);
	return szNewName;
}

LPWSTR DecorateDeskWinstaNameW(PVNC_DESKTOP pDesktop, LPWSTR szName )
{
	LPWSTR szNewName = szName;
	LPWSTR ptr;
	int Offset;

	if ( szName )
	{
		DWORD NameLen = lstrlenW(szName);
		if ( ptr = wcsrchr(szName,L'\\') )
		{
			Offset = (int)(ptr-szName)+1;
			if ( StrCompareNIWA( szName, pDesktop->Name, pDesktop->NameLength ) != 0 )
			{
				szNewName = hAlloc((pDesktop->NameLength+NameLen+2)*sizeof(WCHAR)); //_ and \0
				if ( szNewName == NULL ){
					SetLastError(ERROR_NOT_ENOUGH_MEMORY);
					return NULL;
				}				
				memcpy(szNewName,szName,Offset*sizeof(WCHAR));
				wsprintfW(szNewName+Offset,L"%S",pDesktop->Name);
				if ( lstrcmpiW(szName+Offset,L"Default") != 0 )
				{
					lstrcatW(szNewName,L"_");
					lstrcatW(szNewName,szName+Offset);
				}
			}
		}else{
			szNewName = DecorateDesktopNameW(pDesktop,szName);
		}
	}
	DbgPrint("%S->%S\n", szName,szNewName);
	return szNewName;
}
