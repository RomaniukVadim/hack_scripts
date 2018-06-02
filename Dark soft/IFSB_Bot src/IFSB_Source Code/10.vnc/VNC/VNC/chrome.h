#ifndef __CHROME_H_
#define __CHROME_H_

BOOL CR_CreateEvent(PVNC_SESSION pSession);
BOOL OPRN_CreateEvent(PVNC_SESSION pSession);

WINERROR CR_GetCommandLineW(PVNC_SESSION pSession, LPCWSTR szPath,LPWSTR *pNewCommandLine);
WINERROR CR_GetCommandLineA(PVNC_SESSION pSession, LPCSTR szPath,LPSTR *pNewCommandLine);

WINERROR NewOPR_GetCommandLineW(PVNC_SESSION pSession, LPCWSTR szPath,LPWSTR *pNewCommandLine);
WINERROR NewOPR_GetCommandLineA(PVNC_SESSION pSession, LPCSTR szPath, LPSTR *pNewCommandLine);

#endif