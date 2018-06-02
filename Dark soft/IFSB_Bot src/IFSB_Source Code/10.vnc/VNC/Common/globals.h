//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// AcDLL project. Version 1.9.17.3
//	
// module: globals.h
// $Revision: 192 $
// $Date: 2014-07-11 16:53:02 +0400 (Пт, 11 июл 2014) $
// description: 
//	 Global constants and variables

#ifndef __GLOBALS_H_
#define __GLOBALS_H_

#define G_SYSTEM_VERSION		1		// OS version
#define	G_CURRENT_PROCESS_ID	2		// Current process ID
#define G_CURRENT_MODULE_PATH	4		// Current module full path for DLL (equal to G_PROCESS_MODULE_PATH for EXE)
#define G_CURRENT_PROCESS_PATH	8		// Current process module full path (for both DLL and EXE)
#define G_APP_SHUTDOWN_EVENT	0x10	// Application shutdown event

#define	HOST_EXPLORER		0x74fc6984 // EXPLORER.EXE
#define	HOST_IEXPLORE		0x0922df04 // IEXPLORE.EXE
#define HOST_FIREFOX		0x662d9d39 // FIREFOX.EXE
#define HOST_CHROME			0xc84f40f0 // CHROME.EXE
#define HOST_LAUNCHER		0x60309c56 // LAUNCHER.EXE
#define HOST_OPERA			0x3d75a3ff // OPERA.EXE

// Global variables
extern	DWORD			g_CurrentProcessId;
extern	DWORD			g_SystemVersion;
extern	DWORD			g_CurrentProcessFlags;
extern	HMODULE			g_CurrentProcessModule;
extern	HMODULE			g_CurrentModule;
extern	LPTSTR			g_CurrentProcessPath;
extern	LPTSTR			g_CurrentModulePath;
extern	HANDLE			g_AppShutdownEvent;
extern	LPTSTR			g_CurrentProcessName;
extern	ULONG			g_HostProcess;


// Global process flags
#define	GF_WOW64_PROCESS	1	

WINERROR	InitGlobals(HMODULE CurrentModule, ULONG Flags);
VOID		ReleaseGlobals(VOID);

#endif //__GLOBALS_H_