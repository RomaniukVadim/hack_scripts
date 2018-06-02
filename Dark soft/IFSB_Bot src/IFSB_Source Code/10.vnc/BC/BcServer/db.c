//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// BCSRV project. Version 2.7.17.1
//	
// module: db.c
// $Revision: 70 $
// $Date: 2014-07-22 14:01:15 +0400 (Вт, 22 июл 2014) $
// description:
//  MySQL database abstraction module.

#include "main.h"
#include <mysql.h>
#include "bcclient.h"
#include "db.h"


// Send UsedId BASE64 encoded
//#define	_BASE64_ID			TRUE

#define	MAX_QUERY_LEN		1024	// chars

#define	szQuerySelectUser	"SELECT ServerPort FROM %s WHERE UserId = '%s'"
#define	szQueryInsertUser	"INSERT INTO %s VALUES ('%04u-%02u-%02u %02u:%02u:%02u', '%04u-%02u-%02u %02u:%02u:%02u', %u, %u, %u, %u, '%s', '%s', '%u.%u.%u.%u', %u)"
#define	szQueryUpdateUser	"UPDATE %s SET AccessTime = '%04u-%02u-%02u %02u:%02u:%02u', ServerPort = %u, ClientPort = %u, Address = %u, Flags = %u, LoadPct = %u WHERE UserId = '%s'"
#define	szQueryGeoipCode	"SELECT cc FROM %s WHERE %u BETWEEN start AND end"
#define	szQueryCleanup		"UPDATE %s SET Flags = 0, LoadPct = 0 WHERE Flags = 1"


/**
 * Base64 encode one byte
 */
static char B64EncodeByte(unsigned char u) 
{
  if(u < 26)  return 'A'+u;
  if(u < 52)  return 'a'+(u-26);
  if(u < 62)  return '0'+(u-52);
  if(u == 62) return '+';
  
  return '/';
}

static char *B64Encode(const char *src, int size, char *out) 
{
	char *p = out;
	int	i;
    
	for(i = 0; i < size; i += 3) 
	{
		unsigned char b1=0, b2=0, b3=0, b4=0, b5=0, b6=0, b7=0;
      
		b1 = src[i];
      
		if(i+1<size)
			b2 = src[i+1];
      
		if(i+2<size)
			b3 = src[i+2];
      
		b4= b1>>2;
		b5= ((b1&0x3)<<4)|(b2>>4);
		b6= ((b2&0xf)<<2)|(b3>>6);
		b7= b3&0x3f;
      
		*p++= B64EncodeByte(b4);
		*p++= B64EncodeByte(b5);
      
		if(i+1 < size) 
		{
			*p++= B64EncodeByte(b6);
		} 
		else 
		{
			*p++= '=';
		}
      
		if(i+2 < size) 
		{
			*p++= B64EncodeByte(b7);
		} else 
		{
			*p++= '=';
		}
	}

	return out;
}

								
//
//	Connects to the specified database. Returns the connection handle.
//
WINERROR DbConnect(
	PHANDLE	pHandle,	// variable that receives the connection handle
	LPSTR	Server,		// server host name or IP adress
	ULONG	Port,		// server TCP port
	LPSTR	Name,		// database name
	LPSTR	User,		// user name
	LPSTR	Password,	// user password
	LPSTR	SslCa,		// path to a SSL certification athority file
	LPSTR	SslCert,	// path to the client's SSL certificate file
	LPSTR	SslKey		// path to the client's SSL key file
	)
{
	WINERROR Status = ERROR_NOT_ENOUGH_MEMORY;
	MYSQL*	pConn;
	
	// Initializing MySQL connection object
	if (pConn = mysql_init(NULL))
	{
		if (SslCa && SslCert && SslKey && SslCa[0] && SslCert[0] && SslKey[0])
			mysql_ssl_set(pConn, SslKey, SslCert, SslCa, NULL, NULL);

		// Connecting to the server
		if (mysql_real_connect(pConn, Server, User, Password, Name, Port, NULL, 0))
		{
			*pHandle = pConn;
			Status = NO_ERROR;
		}
		else
		{
			DbgPrint("DB: Connect error: %s\n", mysql_error(pConn));
			Status = mysql_errno(pConn);
			mysql_close(pConn);
		}
	}	// if (Conn = mysql_init(NULL))

	return(Status);

}


//
//	Closes the specified database handle obtained by successfull DbConnect() call.
//
VOID DbClose(
	HANDLE	DbHandle
	)
{
	MYSQL* pConn = (MYSQL*)DbHandle;
	mysql_close(pConn);
}

//
//	Returns  pointer to the last error message for the specified DB handle.
//
LPCSTR	DbGetLastError(
	HANDLE	DbHandle
	)
{
	MYSQL* pConn = (MYSQL*)DbHandle;
	return(mysql_error(pConn));
}

//
//	Updates BC-Database record.
//
WINERROR DbUpdateRecord(
	HANDLE		DbHandle,	// Database connection handle
	LPSTR		TableName,	// Table name
	PBC_RECORD	pRecord		// Pointer to BC_RECORD containing updated data
	)
{
	LPSTR	pQuery, pUserId, pB64UserId;
	ULONG	Length, ServerPort = 0;
	MYSQL_RES*	Result;
	MYSQL_ROW	Row;
	WINERROR	Status = ERROR_NOT_ENOUGH_MEMORY;
	PSYSTEMTIME	pA = &pRecord->LastAccessTime;
	MYSQL* pConn = (MYSQL*)DbHandle;

	if (pUserId = AppAlloc(sizeof(pRecord->UserId) * 5))
	{
		memset(pUserId, 0, (sizeof(pRecord->UserId) / sizeof(WCHAR)) * 5);
		// Converting UserId to multibytes
		if (wcstombs(pUserId, (PWCHAR)&pRecord->UserId, sizeof(pRecord->UserId) / sizeof(WCHAR)) == -1)
			lstrcpynA(pUserId, (LPSTR)&pRecord->UserId, sizeof(pRecord->UserId));

#ifdef _BASE64_ID
		// Converting UserId to BASE64
		pB64UserId = pUserId + sizeof(pRecord->UserId);
		B64Encode(pUserId, lstrlenA(pUserId), pB64UserId);
#else
		pB64UserId = pUserId;
#endif
		// Allocating a buffer for our requests
		if (pQuery = AppAlloc(MAX_QUERY_LEN))
		{
			Length = wsprintf(pQuery, szQuerySelectUser, TableName, pB64UserId);
			ASSERT(Length < MAX_QUERY_LEN);

			if (!mysql_real_query(pConn, pQuery, Length))
			{
				Result = mysql_store_result(pConn);
				if (Row = mysql_fetch_row(Result))
					StrToIntEx(Row[0], 0, &ServerPort);
				else
					Status = NO_ERROR;
				mysql_free_result(Result);
			}
			else
			{
				ASSERT(Status != NO_ERROR);
			}

			if (Status == NO_ERROR)
			{
				ULONG	Address = pRecord->Address;

				// Adding new user record
				Length = wsprintf(pQuery, szQueryInsertUser, TableName,
					pA->wYear, pA->wMonth, pA->wDay, pA->wHour, pA->wMinute, pA->wSecond,
					pA->wYear, pA->wMonth, pA->wDay, pA->wHour, pA->wMinute, pA->wSecond,
					pRecord->ServerPort,
					pRecord->ClientPort,
					pRecord->Address,
					pRecord->Flags,
					pB64UserId,
					(LPSTR)&pRecord->CountryCode,
					LOBYTE(LOWORD(Address)), HIBYTE(LOWORD(Address)), LOBYTE(HIWORD(Address)), HIBYTE(HIWORD(Address)),
					0
					);
				ASSERT(Length < MAX_QUERY_LEN);

				if (mysql_real_query(pConn, pQuery, Length))
				{
					DbgPrint("BCSRV: %s\n", mysql_error(pConn));
					Status = mysql_errno(pConn);
				}
			}	// if (Status == NO_ERROR)
			else if ((pRecord->Flags & BC_RECORD_FLAG_ACTIVE) || pRecord->ServerPort == ServerPort)
			{
				// Updating existing user record
				Length = wsprintf(pQuery, szQueryUpdateUser, TableName,
					pA->wYear, pA->wMonth, pA->wDay, pA->wHour, pA->wMinute, pA->wSecond,
					pRecord->ServerPort,
					pRecord->ClientPort,
					pRecord->Address,
					pRecord->Flags,
					pRecord->LoadPct,
					pB64UserId);
				ASSERT(Length < MAX_QUERY_LEN);

				if (mysql_real_query(pConn, pQuery, Length))
				{
					DbgPrint("BCSRV: %s\n", mysql_error(pConn));
					Status = mysql_errno(pConn);
				}
				else
				{
					Status = NO_ERROR;
				}

			}	// else // if (Status == NO_ERROR)

			AppFree(pQuery);
		}	// if (pQuery = AppAlloc(...

		AppFree(pUserId);
	}	// if (pUserId = AppAlloc((sizeof(pRecord->UserId) / sizeof(WCHAR)) * 5))

	return(Status);
}



//
//	Queries GeoIp database for the country code for the specified IP address.
//
WINERROR DbQueryIpOriginCode(
	HANDLE	DbHandle,	// Database connection handle
	LPSTR	TableName,	// Name of the GeoIp database table
	ULONG	Address,	// IP address to resolve
	PCHAR	Buffer,		// Buffer to receive requests data
	ULONG	Size		// Size of the buffer in bytes
	)
{
	WINERROR	Status = ERROR_NOT_ENOUGH_MEMORY;
	LPSTR		pQuery;
	ULONG		Length;
	MYSQL_RES*	Result;
	MYSQL_ROW	Row;
	MYSQL* pConn = (MYSQL*)DbHandle;

	// Alocating a buffer for a MySQL query
	if (pQuery = AppAlloc(cstrlen(szQueryGeoipCode) + lstrlen(TableName) + 10 + 1))
	{
		Length = wsprintf(pQuery, szQueryGeoipCode, TableName, htonl(Address));

		ASSERT(Length < (cstrlen(szQueryGeoipCode) + lstrlen(TableName) + 10 + 1));

		// Sending request to GeoIp database
		if (!mysql_real_query(pConn, pQuery, Length))
		{
			// Checking if there's a result
			Result = mysql_store_result(pConn);
			if (Row = mysql_fetch_row(Result))
			{
				// Checking if the result fits the specified buffer
				if (Size >= ((ULONG)lstrlen((LPSTR)Row[0]) + 1))
				{
					lstrcpy(Buffer, (LPSTR)Row[0]);
					Status = NO_ERROR;
				}
				else
					Status = ERROR_INSUFFICIENT_BUFFER;
			}	// if (Row = mysql_fetch_row(Result))
			mysql_free_result(Result);
		}	// if (!mysql_real_query(pContext->Conn, pQuery, Length))
		else
		{
			DbgPrint("BCSRV: %s\n", mysql_error(pConn));
			Status = mysql_errno(pConn);
		}

		AppFree(pQuery);
	}	// if (pQuery = AppAlloc(cstrlen(szQueryGeoipCode) + lstrlen(TableName) + 10 + 1))

	return(Status);
}


//
//	Cleans up database. Sets all clients inactive, resets load PCT.
//
WINERROR DbCleanupRecords(
	HANDLE		DbHandle,	// Database connection handle
	LPSTR		TableName	// Table name
	)
{
	LPSTR	pQuery;
	ULONG	Length;
	MYSQL_RES*	Result;
	WINERROR	Status = ERROR_NOT_ENOUGH_MEMORY;
	MYSQL* pConn = (MYSQL*)DbHandle;

	// Allocating a buffer for our requests
	if (pQuery = AppAlloc(MAX_QUERY_LEN))
	{
		Length = wsprintf(pQuery, szQueryCleanup, TableName);
		ASSERT(Length < MAX_QUERY_LEN);

		if (!mysql_real_query(pConn, pQuery, Length))
		{
			if (Result = mysql_store_result(pConn))
				mysql_free_result(Result);
			Status = NO_ERROR;
		}
		else
		{
			DbgPrint("BCSRV: %s\n", mysql_error(pConn));
			Status = mysql_errno(pConn);
			ASSERT(Status != NO_ERROR);
		}

		AppFree(pQuery);
	}	// if (pQuery = AppAlloc(...

	return(Status);
}