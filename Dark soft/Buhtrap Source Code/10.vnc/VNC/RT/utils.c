#include "project.h"
#include "str.h"

LONGLONG Now( VOID )
{
	LARGE_INTEGER li;
	GetSystemTimeAsFileTime((PFILETIME)&li);
	return li.QuadPart;
}

DWORD GetProcessNameHash( IN DWORD ProcessID )
{
	DWORD Hash = 0;
	HANDLE hProcess = OpenProcess(ProcessID,FALSE,ProcessID);
	if ( hProcess )
	{
		CHAR szName[MAX_PATH];
		if ( GetProcessImageFileNameA(hProcess,szName,MAX_PATH-1) )
		{
			Hash = StrHashA(szName);
		}
		CloseHandle ( hProcess );
	}
	return Hash;
}