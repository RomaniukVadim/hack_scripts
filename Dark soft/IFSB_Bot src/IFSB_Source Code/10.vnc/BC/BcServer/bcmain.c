/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// BCSRV project. Version 2.7.17.1
//	
// module: bcmain.c
// $Revision: 70 $
// $Date: 2014-07-22 14:01:15 +0400 (Вт, 22 июл 2014) $
// description:
//	Server startup and cleanup routines. INI-file parser.

#include "main.h"
#include "ini.h"
#include <conio.h>
#include <stdio.h>
#include "..\handle\handle.h"

#include "bcclient.h"
#include "db.h"
#include "bcserver.h"
#include "bitmap.h"


static	HANDLE		g_ServiceShutdownEvent;
HANDLE	g_AppHeap = 0;


PVOID __stdcall AppAlloc(ULONG Size)
{
	return(hAlloc(Size));
}

VOID __stdcall AppFree(PVOID pMem)
{
	hFree(pMem);
}


//
//	Resolves a string parameter of the specified IniParams by the specified hash.
//
static BOOL ResolveStringParameter(
	PINI_PARAMETERS	pIniParams,
	ULONG	Hash,
	LPTSTR	Name,
	LPTSTR	Default,
	LPTSTR*	ppValue
	)
{
	LPSTR	pValue;
	BOOL	Ret = TRUE;
	
	if (pValue = IniGetParamValue(Hash, pIniParams))
	{
		if (!(pValue = IniDupStr(pValue, 0)))
			Ret = FALSE;
	}
	else
	{
		if (pIniParams)
			printf("No %s value specified. Using default value of \"%s\".\n", Name, Default);

		if (!(pValue = IniDupStr(Default, 0)))
			Ret = FALSE;
	}

	if (Ret)
		*ppValue = pValue;

	return(Ret);
}


//
//	Allocates BC_PORT structures for the specified TCP port numbers and links theese structures into server's port list.
//
static WINERROR BcAllocatePorts(
	PBC_SERVER	pServer,	// Current BC_SERVER structure pointer
	LPTSTR		pValue,		// String containing number of ports devided by ','
	ULONG		iValue		// Predefined number of port
	)
{
	WINERROR Status = NO_ERROR;

	while(iValue || (pValue && StrToIntEx(pValue, 0, &iValue)))
	{
		PBC_PORT pPort;
		if (pPort = hAlloc(sizeof(BC_PORT)))
		{
			memset(pPort, 0, sizeof(BC_PORT));
			InitializeListHead(&pPort->Entry);

			pPort->Number = (USHORT)iValue;
			pPort->Socket = INVALID_SOCKET;
			pPort->aSocket = INVALID_SOCKET;
			InsertTailList(&pServer->PortListHead, &pPort->Entry);
			iValue = 0;
		}
		else
		{
			Status = ERROR_NOT_ENOUGH_MEMORY;
			break;
		}
		if (pValue && (pValue = StrChr(pValue, ',')))
			StrTrim(pValue, " ,\t");
	}	// while(pValue && StrToIntEx(pValue, 0, &iValue))

	if (pValue)
	{
		Status = ERROR_INVALID_PARAMETER;
		DbgPrint("BCSRV: Invalid TCP port number specified: %s\n", pValue);
	}

	return(Status);
}

//
//	BC-Server initialization routine.
//	Searches for the server initialization file and uploads server parameters.
//
WINERROR BcServerInit(
	PBC_SERVER	pServer		// current BC-Server descriptor
	)
{
	LPTSTR	pIniFile = NULL;
	ULONG	IniSize, iValue;
	LPTSTR	pValue, pCurrentPath, pIniPath = NULL;
	WINERROR Status = ERROR_NOT_ENOUGH_MEMORY;
	PINI_PARAMETERS	pIniParams = NULL;

	ASSERT_BC_SERVER(pServer);

	do	// not a loop
	{

		// Allocating client ports bitmap
		if (!(pServer->PortBitmap = BmAllocate(BC_PORT_RANGE_TOP - BC_PORT_RANGE_BOTTOM)))
			break;

		// Trying to resolve a directory our server was started from and load INI-file from there
		if (IniGetModulePath(0, &pCurrentPath) == NO_ERROR)
		{
			LPTSTR	pShortName;
			ULONG	PathLen;
			if (pShortName = StrRChr(pCurrentPath, NULL, _T('\\')))
			{
				PathLen = (ULONG)(pShortName - pCurrentPath + 1);
				if (pIniPath = hAlloc((PathLen + cstrlen(szBcServerIni) + 1) * sizeof(_TCHAR)))
				{
					memcpy(pIniPath, pCurrentPath, PathLen * sizeof(_TCHAR));
					lstrcpy(pIniPath + PathLen, szBcServerIni);				
				}
			}	// if (pShortName = StrRChr(pCurrentPath, NULL, _T("\\")))
			AppFree(pCurrentPath);
		}	// if (IniGetModulePath(0, &pCurrentPath) == NO_ERROR)
	
		if (IniLoadFile((pIniPath ? pIniPath : szBcServerIni), &pIniFile, &IniSize) == NO_ERROR)
		{
			// There is the initialization file, parsing it...
			ASSERT(pIniFile[IniSize] == 0);
			printf("Parsing %s initialization file...\n", szBcServerIni);
			IniParseParamFile(pIniFile, &pIniParams, FALSE, TRUE);
		}
		else
			printf("Server initialization file %s not found. Using default settings.\n", szBcServerIni);

		// Resolving server Port parameter
		if (pValue = IniGetParamValue(CRC_PORT, pIniParams))
			Status = BcAllocatePorts(pServer, pValue, 0);

		if (!pValue || Status == ERROR_INVALID_PARAMETER)
		{
			if (pIniParams)
				printf("Invalid Port value specified, using default port %u.\n", BC_DEFAULT_SERVER_PORT);
			Status = BcAllocatePorts(pServer, NULL, BC_DEFAULT_SERVER_PORT);
		}

		if (Status != NO_ERROR)
			break;

		// Resolving Clients parameter
		if ((pValue = IniGetParamValue(CRC_CLIENTS, pIniParams)) && StrToIntEx(pValue, 0, &iValue) && (iValue != 0))
			pServer->ClientsLimit = iValue;
		else
		{
			if (pIniParams)
				printf("Invalid Clients value specified, using default value of %u.\n", BC_DEFAULT_CLIENTS);
			pServer->ClientsLimit = BC_DEFAULT_CLIENTS;
		}

		// Resolving DbServer parameter
		if (!ResolveStringParameter(pIniParams, CRC_DBSERVER, _T("DbServer"), BC_DEFAULT_DB_SERVER, &pServer->DbServer))
			break;

		// Resolving DbPort parameter
		if ((pValue = IniGetParamValue(CRC_DBPORT, pIniParams)) && StrToIntEx(pValue, 0, &iValue))
			pServer->DbPort = iValue;
		else
		{
			if (pIniParams)
				printf("Invalid DbPort value specified, using default value of %u.\n", BC_DEFAULT_DB_PORT);
			pServer->DbPort = BC_DEFAULT_DB_PORT;
		}

		// Resolving DbConnectLimit parameter
		if ((pValue = IniGetParamValue(CRC_DBCONNECTLIMIT, pIniParams)) && StrToIntEx(pValue, 0, &iValue))
			pServer->DbConnectLimit = iValue;
		else
		{
			if (pIniParams)
				printf("Invalid DbConnectLimit value specified, using default value of %u.\n", BC_DEFAULT_DB_CONNECT_LIMIT);
			pServer->DbConnectLimit = BC_DEFAULT_DB_CONNECT_LIMIT;
		}

		// Resolving DbName parameter
		if (!ResolveStringParameter(pIniParams, CRC_DBNAME, _T("DbName"), BC_DEFAULT_DB_NAME, &pServer->DbName))
			break;

		// Resolving DbUser parameter
		if (!ResolveStringParameter(pIniParams, CRC_DBUSER, _T("DbUser"), BC_DEFAULT_DB_USER, &pServer->DbUser))
			break;

		// Resolving DbPassword parameter
		if (!ResolveStringParameter(pIniParams, CRC_DBPASSWORD, _T("DbPassword"), BC_DEFAULT_DB_PASSWORD, &pServer->DbPassword))
			break;

		// Resolving DbTable parameter
		if (!ResolveStringParameter(pIniParams, CRC_DBTABLE, _T("DbTable"), BC_DEFAULT_DB_TABLE, &pServer->DbTable))
			break;

		// Resolving GeoipName parameter
		if (!ResolveStringParameter(pIniParams, CRC_GEOIPNAME, _T("GeoipName"), BC_DEFAULT_GEOIP_NAME, &pServer->GeoipName))
			break;

		// Resolving GeoipTable parameter
		if (!ResolveStringParameter(pIniParams, CRC_GEOIPTABLE, _T("GeoipTable"), BC_DEFAULT_GEOIP_TABLE, &pServer->GeoipTable))
			break;

		// Resolving SslCa parameter
		if (!ResolveStringParameter(pIniParams, CRC_SSLCA, _T("SslCa"), BC_DEFAULT_SSLCA, &pServer->SslCa))
			break;

		// Resolving SslCert parameter
		if (!ResolveStringParameter(pIniParams, CRC_SSLCERT, _T("SslCert"), BC_DEFAULT_SSLCERT, &pServer->SslCert))
			break;

		// Resolving SslKey parameter
		if (!ResolveStringParameter(pIniParams, CRC_SSLKEY, _T("SslKey"), BC_DEFAULT_SSLCERT, &pServer->SslKey))
			break;

		Status = NO_ERROR;
	} while(FALSE);

	if (pIniParams)
		hFree(pIniParams);

	if (pIniFile)
		hFree(pIniFile);

	if (pIniPath)
		hFree(pIniPath);

	return(Status);
}



//
//	Stops the specified BC-Server. Cleans up structures. Frees memory.
//
VOID BcSrvCleanup(
	PBC_SERVER	pServer
	)
{
	ASSERT_BC_SERVER(pServer);

	BcServerStop(pServer);

#if _DEBUG
	pServer->Header.Magic = 0;
#endif

	if (pServer->DbHandle)
		DbClose(pServer->DbHandle);
	if (pServer->GeoipHandle)
		DbClose(pServer->GeoipHandle);

	if (pServer->DbTable)
		hFree(pServer->DbTable);
	if (pServer->DbPassword)
		hFree(pServer->DbPassword);
	if (pServer->DbUser)
		hFree(pServer->DbUser);
	if (pServer->DbName)
		hFree(pServer->DbName);
	if (pServer->DbServer)
		hFree(pServer->DbServer);

	if (pServer->PortBitmap)
		BmFree(pServer->PortBitmap);

	hFree(pServer);
}

//
//	BC server application startup routine.
//	Searches for the server initialization file and uploads server parameters.
//
WINERROR BcSrvStartup(
	PVOID*	ppServer	
	)
{
	WINERROR	Status = ERROR_NOT_ENOUGH_MEMORY;
	PBC_SERVER	pServer;

	if (pServer = hAlloc(sizeof(BC_SERVER)))
	{
		memset(pServer, 0, sizeof(BC_SERVER));
		pServer->Header.Magic = BC_SERVER_MAGIC;
		InitializeListHead(&pServer->PortListHead);

		if ((Status = BcServerInit(pServer)) == NO_ERROR)
		{
			if (pServer->SslCa[0] == 0 || pServer->SslCert[0] == 0 || pServer->SslKey[0] == 0)
			{
				printf("No SSL-connection parameters specified. Using normal connection.\n");
				printf("Connectng MySQL server %s...\n", pServer->DbServer);
			}
			else
				printf("Connectng MySQL server %s over SSL...\n", pServer->DbServer);

			Status = DbConnect(&pServer->GeoipHandle, pServer->DbServer, pServer->DbPort, pServer->GeoipName, pServer->DbUser, pServer->DbPassword, 
				pServer->SslCa, pServer->SslCert, pServer->SslKey);

			if (Status != NO_ERROR)
			{
				printf("GeoIp database not found. BC-Server will be unable to resolve IP origin.\n");
				pServer->GeoipHandle = 0;
			}

			Status = DbConnect(&pServer->DbHandle, pServer->DbServer, pServer->DbPort, pServer->DbName, pServer->DbUser, pServer->DbPassword,
				pServer->SslCa, pServer->SslCert, pServer->SslKey);

			if (Status == NO_ERROR)
			{
				printf("BC-Server database connected. Starting the server...\n");

				// Setting all client records to inactive state 
				DbCleanupRecords(pServer->DbHandle, pServer->DbTable);

				// Starting the server
				if ((Status = BcServerStart(pServer)) == NO_ERROR)
				{
					*ppServer = pServer;
					Status = NO_ERROR;
				}
			}	// if ((Status = BcServerConnectDb(pServer)) == NO_ERROR) 
			else
			{
				printf("Unable to connect the database, error %u\n", Status);
			}
		}	// if ((Status = BcServerInit(pServer)) == NO_ERROR)
	}	// if (pServer = hAlloc(sizeof(BC_SERVER)))

	DbgPrint("BCSRV: Server startup routine ended with status %u\n", Status);

	return(Status);
}


#ifdef	_BC_SERVICE

// ---- BC service -------------------------------------------------------------------------------------------------------------


VOID WINAPI SvcControlHandler(
	DWORD fdwControl
	)
{
	switch(fdwControl) 
	{
	case SERVICE_CONTROL_STOP:
	case SERVICE_CONTROL_SHUTDOWN: 
		SetEvent(g_ServiceShutdownEvent);
		break;
	default:
		break;
	}

}

//
//	Our service main function.
//	Registers service control handler. Starts and stops BC-server.
//
VOID WINAPI SvcMain(
    DWORD   dwNumServicesArgs,
    LPSTR   *lpServiceArgVectors
    )
{
	PVOID		pServer;
	WINERROR	Status;
	SERVICE_STATUS          ServiceStatus;
	SERVICE_STATUS_HANDLE   hStatus;
	
	ServiceStatus.dwServiceType = SERVICE_WIN32;
	ServiceStatus.dwCurrentState = SERVICE_START_PENDING;
	ServiceStatus.dwControlsAccepted = SERVICE_ACCEPT_STOP | SERVICE_ACCEPT_SHUTDOWN;
	ServiceStatus.dwWin32ExitCode = NO_ERROR;
	ServiceStatus.dwServiceSpecificExitCode = 0;
	ServiceStatus.dwCheckPoint = 0;
	ServiceStatus.dwWaitHint = 0;

	if (g_ServiceShutdownEvent = CreateEvent(NULL, TRUE, FALSE, NULL))
	{
		hStatus = RegisterServiceCtrlHandler(szBcServer, (LPHANDLER_FUNCTION)SvcControlHandler);

		if (hStatus)
		{
			if ((Status = BcSrvStartup(&pServer)) == NO_ERROR)
			{
				ASSERT(pServer);
				// Init complete, report the running status to SCM. 
				ServiceStatus.dwCurrentState = SERVICE_RUNNING; 
				SetServiceStatus(hStatus, &ServiceStatus);

				// Waiting for the service shutdown event
				WaitForSingleObject(g_ServiceShutdownEvent, INFINITE);

				// Stopping the service
				ServiceStatus.dwCurrentState = SERVICE_STOP_PENDING;
				SetServiceStatus(hStatus, &ServiceStatus);

				BcSrvCleanup(pServer);

				ASSERT(Status == NO_ERROR);
			}	// f ((Status = BcSrvStartup(&pServer)) == NO_ERROR)

			// Initialization failed
			ServiceStatus.dwCurrentState = SERVICE_STOPPED;
			ServiceStatus.dwWin32ExitCode = Status;
			SetServiceStatus(hStatus, &ServiceStatus);
		}	// if (hStatus)
		else
		{
			DbgPrint("BCSRV: Registering service control handler failed.\n");
		}
		CloseHandle(g_ServiceShutdownEvent);
	}	// if (g_ServiceShutdownEvent = CreateEvent(NULL, TRUE, FALSE, NULL))
}


//
//	 This is our service EntryPoint function.
//
WINERROR SvcStartup(VOID)
{
	WINERROR Status;
	SERVICE_TABLE_ENTRY ServiceTable[2];

	ServiceTable[0].lpServiceName = szBcServer;
	ServiceTable[0].lpServiceProc = (LPSERVICE_MAIN_FUNCTION)SvcMain;
	ServiceTable[1].lpServiceName = NULL;
	ServiceTable[1].lpServiceProc = NULL;

	// Start the control dispatcher thread for our service
	Status = StartServiceCtrlDispatcher(ServiceTable);  

	return(Status);
}
#endif	// _BC_SERVICE

// -----------------------------------------------------------------------------------------------------------------------------

//
//	 This is our application EntryPoint function.
//
int _tmain(int argc, _TCHAR* argv[])
{
	WINERROR Status = NO_ERROR;
	WSADATA	WsaData;

	printf("BC-Server Version 2.7.17.1\n");
	DbgPrint("BCSRV: Started as win32 process 0x%x.\n", GetCurrentProcessId());

	// Creating server heap
	if (g_AppHeap = HeapCreate(0, BC_SERVER_HEAP_SIZE, 0))
	{
		if (!WSAStartup(0x0201, &WsaData))
		{
	#ifdef	_BC_SERVICE
			if (!GetStdHandle(STD_OUTPUT_HANDLE))
				// Started as WIN32 service
				Status = SvcStartup();
			else
	#endif
			{
				// Started as WIN32 application
				PVOID	pServer;

				if ((Status = BcSrvStartup(&pServer)) == NO_ERROR)
				{
					ASSERT(pServer);
					printf("Successfully started. Press SPACE to stop it...\n");

					do 
					{
						Sleep(200);
					} while(getch() != ' ');

					printf("Stopping server, please wait...\n");
					BcSrvCleanup(pServer);
					printf("Server stopped.\n");
				}	// if ((Status = BcSrvStartup(&hServer)) == NO_ERROR)
			}

			WSACleanup();
		}	// if (!WSAStartup(0x0201, &WsaData))

		HeapDestroy(g_AppHeap);
	}	// 	if (g_AppHeap = HeapCreate(0, 0x1000000, 0))
	else
	{
		Status = GetLastError();
		DbgPrint("BCSRV: Not enough memory to start the server.\n");
		printf("Not enough memory to start the server.\n");
	}

	DbgPrint("BCSRV: BC-Server process 0x%x finished with status %u.\n", GetCurrentProcessId(), Status);

	return(Status);
}
