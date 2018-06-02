//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: clipbd.c
// $Revision: 194 $
// $Date: 2014-07-11 16:55:06 +0400 (Пт, 11 июл 2014) $
// description: 
//	host clipboard tracker

#include "project.h"
#pragma warning (disable:4244)

static ATOM g_ClipClass = 0;
//
//implements clipboard event interface
// it just adds new text to clipboard, associated with the current window
VOID ClipOnEvent(PVNC_SESSION pVncSession, PCHAR Text, int Length )
{
	HWND hPopupWnd,hFocusWnd;
	HWND hWnd = SS_GET_FOREGROUND_WND(pVncSession);

	if ((!hWnd) || (!IsWindowEx(&pVncSession->Desktop,hWnd)) || (!IsWindowVisibleEx(hWnd)) || (IsIconic(hWnd))){
		hWnd=GetLastActivePopup(pVncSession->Desktop.hDeskWnd);
	}
	else
	{
		hPopupWnd=GetTopPopupWindow(&pVncSession->Desktop,hWnd);
		if (hPopupWnd)
		{
			hWnd=hPopupWnd;
			SetForegroundWnd(pVncSession,hWnd);
		}

		hFocusWnd=GetWindowFocus(hWnd);
		if (hFocusWnd){
			hWnd = hFocusWnd;
		}
	}

	if ( OpenClipboard( hWnd ) )
	{
		HANDLE hClipboard = GlobalAlloc ( GMEM_MOVEABLE, Length + 1 );
		if ( hClipboard )
		{
			char* data = (char*) GlobalLock(hClipboard);
			memcpy(data, Text, Length+1);
			data[Length] = 0;
			GlobalUnlock(hClipboard);

			if ( EmptyClipboard() ){
				SetClipboardData(CF_TEXT, hClipboard);
			}
			GlobalFree(hClipboard);
		}
		CloseClipboard();
	}
}

//
// -=- CR/LF handlers
//

static char* dos2unix(const char* text) {
	int len = strlen(text)+1;
	char* unix = (char*)hAlloc(strlen(text)+1);
	int i, j=0;
	if ( unix )
	{
		for (i=0; i<len; i++) {
			if (text[i] != '\x0d')
				unix[j++] = text[i];
		}
	}
	return unix;
}

static char* unix2dos(const char* text) {
	int len = strlen(text)+1;
	char* dos = (char*)hAlloc(strlen(text)*2+1);
	int i, j=0;
	if ( dos )
	{
		for (i=0; i<len; i++) {
			if (text[i] == '\x0a')
				dos[j++] = '\x0d';
			dos[j++] = text[i];
		}
	}
	return dos;
}


//
// -=- ISO-8859-1 (Latin 1) filter (in-place)
//

static void ClipRemoveNonISOLatin1Chars(char* text) 
{
	int len = strlen(text);
	int i=0, j=0;
	for (; i<len; i++) {
		if (((text[i] >= 1) && (text[i] <= 127)) ||
			((text[i] >= 160) && (text[i] <= 255)))
			text[j++] = text[i];
	}
	text[j] = 0;
}

LRESULT CALLBACK 
	ClipNotifierProc(
		HWND hWnd,
		UINT uMsg,
		WPARAM wParam,
		LPARAM lParam
		)
{
	PVNC_SESSION pSession = (PVNC_SESSION)(LONG_PTR)GetWindowLongPtr(hWnd,GWLP_USERDATA);
	BOOL fbResult = TRUE;

	switch (uMsg)
	{
	case WM_CREATE:
		{
			LPCREATESTRUCT lpCreate = (LPCREATESTRUCT)lParam;
			if ( lpCreate ){
				pSession = (PVNC_SESSION)lpCreate->lpCreateParams;
				SetWindowLongPtr(hWnd,GWLP_USERDATA,(LONG_PTR)pSession);
			}
			break;
		}
	case WM_CHANGECBCHAIN:
		if ( pSession )
		{
			if ((HWND) wParam == pSession->Desktop.WndClipNextNotifier){
				pSession->Desktop.WndClipNextNotifier = (HWND) lParam;
			}
			else if (pSession->Desktop.WndClipNextNotifier != NULL ){
				SendNotifyMessage(pSession->Desktop.WndClipNextNotifier, uMsg, wParam, lParam);
			}
			return 0;
		}
	case WM_DRAWCLIPBOARD:
		if ( pSession )
		{
			HWND owner = GetClipboardOwner();
			if (owner == hWnd) {
				DbgPrint("local clipboard changed by us\n");
			}else {
				DbgPrint("local clipboard changed by %x\n", owner);

				// Open the clipboard
				if (OpenClipboard(hWnd)) {
					// Get the clipboard data
					HGLOBAL cliphandle = GetClipboardData(CF_TEXT);
					if (cliphandle) 
					{
						char* clipdata = (char*) GlobalLock(cliphandle);
						// Notify client
						if (!clipdata)
						{
							//send server message
							RfbSendServerText(pSession->RfbSession,NULL,0);
						} 
						else 
						{
							char * unix_text;
							unix_text = dos2unix(clipdata);
							if ( unix_text )
							{
								ClipRemoveNonISOLatin1Chars(unix_text);

								//send server message
								RfbSendServerText(pSession->RfbSession,unix_text,strlen(unix_text)+1);
								hFree ( unix_text );
							}
						}

						// Release the buffer and close the clipboard
						GlobalUnlock(cliphandle);
					}

					CloseClipboard();
				}
			}
			if (pSession->Desktop.WndClipNextNotifier){
				return SendNotifyMessage(pSession->Desktop.WndClipNextNotifier, uMsg, wParam, lParam);
			}
			return 0;
		}
	}
	return DefWindowProc(hWnd, uMsg, wParam, lParam);
}

static TCHAR szClipWindowClass[GUID_STR_LENGTH+1];

WINERROR
	ClipStartViewer(
		PVNC_SESSION pSession
		)
{
	WINERROR Status = NO_ERROR;
//TEST
return 0;

	DbgPrint("Starting clipboard monitor\n");

	// creating clipboard notification window
	pSession->Desktop.WndClipNotifier = 
		CreateWindow(
			szClipWindowClass,
			0,0,1,1,1,1,
			NULL,NULL,
			GetModuleHandle(0),
			pSession
			)
			;
	if ( pSession->Desktop.WndClipNotifier ){
		// monitor clipboard event
		pSession->Desktop.WndClipNextNotifier = 
			SetClipboardViewer(
				pSession->Desktop.WndClipNotifier
				);
	}
	else
	{
		Status = GetLastError();
		DbgPrint("[ClipStartViewer] CreateWindow failed, err = %lu\n",Status);
	}

	return Status;
}

VOID
	ClipStopViewer(
		PVNC_SESSION pSession
		)
{

	if ( pSession->Desktop.WndClipNotifier )
	{
		ChangeClipboardChain(
			pSession->Desktop.WndClipNotifier,
			pSession->Desktop.WndClipNextNotifier
			);
		DestroyWindow( pSession->Desktop.WndClipNotifier );
		pSession->Desktop.WndClipNotifier = NULL;
	}

}

// function creates clipboard tracker window class
ATOM ClipCreateWindowsClass ( VOID )
{
	WNDCLASS wc = {0};
	ULONG NameSeed = GetTickCount();

	// gen random class name
	FillGuidName(&NameSeed,szClipWindowClass);

	// registering windows that will receive 
	// clipboard notifications
	wc.style         = 0;
	wc.lpfnWndProc   = (WNDPROC)ClipNotifierProc;
	wc.cbClsExtra    = 0;
	wc.cbWndExtra    = 0;
	wc.hInstance     = GetModuleHandle(0);
	wc.hIcon         = NULL;
	wc.hCursor       = NULL;
	wc.hbrBackground = NULL;
	wc.lpszMenuName  = NULL;
	wc.lpszClassName = szClipWindowClass;

	return RegisterClass(&wc);
}

WINERROR
	ClipInitialize(
		VOID
		)
{
	WINERROR Status = NO_ERROR;
	g_ClipClass = ClipCreateWindowsClass();
	if ( !g_ClipClass ){
		Status = GetLastError();
		DbgPrint("[ClipInitialize] ClipCreateWindowsClass failed, err = %lu\n",Status);
	}
	return Status;
}