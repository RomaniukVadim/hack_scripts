//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: layout.h
// $Revision: 186 $
// $Date: 2014-07-04 19:05:58 +0400 (Пт, 04 июл 2014) $
// description: 
//	keyboard layout switcher

#ifndef LAYOUTSWITCHER_H_INCLUDED
#define LAYOUTSWITCHER_H_INCLUDED

#include <shellapi.h>

#define IDM_AUTODETECT 100
#define ICONWIDTH   16
#define ICONHEIGHT  16
#define WM_TRAYMENU WM_USER+1488

void LS_Start(PVNC_SESSION pSession);
void LS_Stop(PVNC_SESSION pSession);

#endif // LAYOUTSWITCHER_H_INCLUDED
