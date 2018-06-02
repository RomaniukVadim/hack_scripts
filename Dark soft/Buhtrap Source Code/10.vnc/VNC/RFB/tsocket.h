//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: tsocket.h
// $Revision: 137 $
// $Date: 2013-07-23 16:57:05 +0400 (Вт, 23 июл 2013) $
// description:
//  Socket provider abstraction layer.
//	Defines CurrentTsocketTable pointer to a table containing pointers to functions of the socket provider currently being used.
//	Socket provider can be easily changed by replacing this pointer.
//	Defines socket-management macros that call socket functions from the CurrentTsocketTable.

#pragma once

#include "kipapi.h"

// Pointer to a function table of the current socket provider
extern PTSOCKET_TABLE	volatile g_CurrentTsocketTable;

#ifdef _KIP_SUPPORT

// Socket provider abstraction macros
#define	_socket(x, y, z)			(g_CurrentTsocketTable->socket)(x, y, z)
#define	_closesocket(x)				(g_CurrentTsocketTable->close)(x)
#define	_ioctlsocket(x, y, z)		(g_CurrentTsocketTable->ioctl)(x, y, z)
#define	_sendto(a, b, c, d, e, f)	(g_CurrentTsocketTable->sendto)(a, b, c, d, e, f)
#define	_recvfrom(a, b, c, d, e, f)	(g_CurrentTsocketTable->recvfrom)(a, b, c, d, e, f)
#define _getsockopt(a, b, c, d, e)	(g_CurrentTsocketTable->getsockopt)(a, b, c, d, e)
#define	_select(a, b, c, d, e)		(g_CurrentTsocketTable->select)(a, b, c, d, e)
#define	_connect(a, b, c)			(g_CurrentTsocketTable->connect)(a, b, c)
#define _setsockopt(a, b, c, d, e)	(g_CurrentTsocketTable->setsockopt)(a, b, c, d, e)
#define	_bind(a, b, c)				(g_CurrentTsocketTable->bind)(a, b, c)
#define	_shutdown(a, b)				(g_CurrentTsocketTable->shutdown)(a, b)
#define	_send(a, b, c, d)			(g_CurrentTsocketTable->send)(a, b, c, d)
#define	_getsockname(a, b, c)		(g_CurrentTsocketTable->getsockname)(a, b, c)
#define	_gethostbyname(a)			(g_CurrentTsocketTable->gethostbyname)(a)
#define	_gethostbynamer(a,b,c,d,e,f)(g_CurrentTsocketTable->gethostbynamer)(a,b,c,d,e,f)
#define	_recv(a, b, c, d)			(g_CurrentTsocketTable->recv)(a, b, c, d)
#define	_getpeername(a, b, c)		(g_CurrentTsocketTable->getpeername)(a, b, c)
#define	_listen(a, b )				(g_CurrentTsocketTable->listen)(a, b )
#define	_accept(a, b, c)			(g_CurrentTsocketTable->accept)(a, b, c)

#else

#define	_socket(x, y, z)			socket(x, y, z)
#define	_closesocket(x)				closesocket(x)
#define	_ioctlsocket(x, y, z)		ioctlsocket(x, y, z)
#define	_sendto(a, b, c, d, e, f)	sendto(a, b, c, d, e, f)
#define	_recvfrom(a, b, c, d, e, f)	recvfrom(a, b, c, d, e, f)
#define _getsockopt(a, b, c, d, e)	getsockopt(a, b, c, d, e)
#define	_select(a, b, c, d, e)		select(a, b, c, d, e)
#define	_connect(a, b, c)			connect(a, b, c)
#define _setsockopt(a, b, c, d, e)	setsockopt(a, b, c, d, e)
#define	_bind(a, b, c)				bind(a, b, c)
#define	_shutdown(a, b)				shutdown(a, b)
#define	_send(a, b, c, d)			send(a, b, c, d)
#define	_getsockname(a, b, c)		getsockname(a, b, c)
#define	_gethostbyname(a)			gethostbyname(a)
#define	_gethostbynamer(a,b,c,d,e,f)gethostbynamer(a,b,c,d,e,f)
#define	_recv(a, b, c, d)			recv(a, b, c, d)
#define	_getpeername(a, b, c)		getpeername(a, b, c)
#define	_listen(a, b )				listen(a, b )
#define	_accept(a, b, c)			accept(a, b, c)

#endif	// _KIP_SUPPORT




// Socket table management functions
VOID TsocketUseKip(VOID);
VOID TsocketUseWinsock(VOID);