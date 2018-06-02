#ifndef __OPERA_H_
#define __OPERA_H_

BOOL OPR_CreateEvent(PVNC_SESSION pSession);
BOOL OPR_LaunchA(PVNC_SESSION pSession,LPCSTR szPath);
BOOL OPR_LaunchW(PVNC_SESSION pSession,LPCWSTR szPath);
VOID OPR_Cleanup(PVNC_SESSION pSession);

#endif