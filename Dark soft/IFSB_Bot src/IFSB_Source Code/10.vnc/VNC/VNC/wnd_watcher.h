//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: wnd_watcher.h
// $Revision: 186 $
// $Date: 2014-07-04 19:05:58 +0400 (Пт, 04 июл 2014) $
// description: 
//	windows list tracker (windows manager)

#ifndef WND_WATCHER_H_INCLUDED
#define WND_WATCHER_H_INCLUDED

PZ_ORDER_LIST_ITEM 
	WW_AppendWndToList(
		IN PVNC_SESSION pSession, 
		IN HWND hWnd
		);
VOID WW_SetFrgWnd(PVNC_SESSION pSession, HWND hWnd, BOOL bLock );
DWORD WW_Start ( PVNC_SESSION pSession );
VOID WW_Shutdown ( PVNC_SESSION pSession );
VOID WW_Stop ( PVNC_SESSION pSession );

HWND WW_WindowFromPointEx( PVNC_SESSION pSession, POINT pt, HWND *lphParent);
PZ_ORDER_LIST_ITEM WW_GetWndFromList(IN PVNC_SESSION pSession,IN HWND hWnd);
BOOL WW_IsItMsgBox(PVNC_SESSION pSession,HWND hWnd);

#define WW_LockWndList( _Session ) EnterCriticalSection(&((_Session)->Desktop.WndWatcher.csWndsList))
#define WW_UnlockWndList( _Session ) LeaveCriticalSection(&((_Session)->Desktop.WndWatcher.csWndsList))

#endif // WND_WATCHER_H_INCLUDED
