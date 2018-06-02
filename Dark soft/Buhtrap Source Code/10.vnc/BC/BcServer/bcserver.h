/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// BCSRV project. Version 2.7.17.1
//	
// module: bcserver.h
// $Revision: 70 $
// $Date: 2014-07-22 14:01:15 +0400 (Вт, 22 июл 2014) $
// description:
//	BC-Server main definition file

// Run BC-server as WIN32 service
#define	_BC_SERVICE					TRUE

#define	BC_SERVER_HEAP_SIZE			0x1000000			// server heap size (16mb)

// BC-Server initialization file name
#define	szBcServerIni				_T("BCSERVER.INI")
#define	szBcServer					_T("BCServer")

// BC-Server configuration defaults
#define	BC_DEFAULT_SERVER_PORT		9955				// server control TCP port
#define	BC_DEFAULT_CLIENTS			1000				// maximum number of clients

#define	BC_DEFAULT_DB_SERVER		_T("localhost")		// MySQL database host 	
#define	BC_DEFAULT_DB_PORT			0					// MySQL database port
#define	BC_DEFAULT_DB_CONNECT_LIMIT	90					// Maximum number of concurent connections to the database
#define	BC_DEFAULT_DB_NAME			_T("bcserver_db")	// database name
#define	BC_DEFAULT_DB_USER			_T("bcserver")		// database user name
#define	BC_DEFAULT_DB_PASSWORD		_T("123")			// database user password
#define	BC_DEFAULT_DB_TABLE			_T("clients")		// database table name

#define	BC_DEFAULT_GEOIP_NAME		_T("geoip")			// GeoIp database name
#define	BC_DEFAULT_GEOIP_TABLE		_T("csv")			// Geoip database table name

#define	BC_DEFAULT_SSLCA			_T("")
#define	BC_DEFAULT_SSLCERT			_T("")
#define	BC_DEFAULT_SSLKEY			_T("")

//	BC-Server working port range
#define	BC_PORT_RANGE_TOP			(USHRT_MAX - 1)
#define	BC_PORT_RANGE_BOTTOM		1025


// BC-Server control parameters
#define	BC_PENDING_SESSION_TIMEOUT	30					// Number of seconds the pending session waits for a connection,
														//  after this period the session is being terminated

#define	BC_PENDING_LINK_TIMEOUT		60					// Number of seconds the link stays active without transfering a data

#define	BC_SESSION_UPDATE_TIMEOUT	60					// A period of seconds to update session statistics information

#define	_BC_ENABLE_KEEPALIVE		TRUE				// Enable keepalive option for the socket

#define	BC_KEEPALIVE_TIME			10*1000				// The timeout, in milliseconds, with no activity until the first keep-alive
														//	packet is sent
#define	BC_KEEPALIVE_INTERVAL		1*1000				// The interval, in milliseconds, between when successive keep-alive packets 
														//	are sent if no acknowledgement is received

#define	BC_NUMBER_OF_WORKERS		16					// Number of IoCompletion worker threads per logical processor

#if _DBG
 #ifdef _M_AMD64
  #define	BC_SESSION_BUFFER_SIZE		0x7fb0			// bytes
 #else
  #define	BC_SESSION_BUFFER_SIZE		0x7fc8			// bytes
 #endif
#else
 #ifdef _M_AMD64
  #define	BC_SESSION_BUFFER_SIZE		0x7fb4			// bytes
 #else
  #define	BC_SESSION_BUFFER_SIZE		0x7fcc			// bytes
 #endif
#endif

#define	BC_SESSION_WATCH_TIME		30*1000				// Period in milliseconds when BcSessionWatch() function is being executed
#define	BC_SESSION_WATCH_COUNT		ULONG_MAX			// Number of sessions to watch in a single attempt

//#define	_BC_CHECK_ACTIVE_LINKS		TRUE				// Checks all active links within a session if there was no data transfered through 
														//	it during BC_PENDING_LINK_TIMEOUT. Theese links are being terminated.
														// This option is usefull for highly-loaded SOCKS server.


// ---- Internal structures -------------------------------------------------------------------------------------------------------

typedef struct _BC_SESSION BC_SESSION, *PBC_SESSION;

typedef	struct _BC_HEADER
{
	ULONG			Magic;
	LONG volatile	ReferenceCount;
} BC_HEADER, *PBC_HEADER;

typedef struct _BC_OVERLAPPED
{
	OVERLAPPED	Overlapped;
	PBC_HEADER	pControlObject;
} BC_OVERLAPPED, *PBC_OVERLAPPED;

// BC-Server descriptor structure
typedef struct _BC_SERVER
{
	BC_HEADER			Header;

// BC-Session table
	PHANDLE_TABLE		SessionTable;

// List of server ports
	LIST_ENTRY			PortListHead;

// Bitmap of used client ports
	PCHAR				PortBitmap;

// SQL database connection parameters
	LPSTR				DbServer;			// Database server host name (or IP address)
	ULONG				DbPort;				// Database server TCP port
	ULONG				DbConnectLimit;		// Database connection limit
	LPSTR				DbName;				// BC-Database name
	LPSTR				DbUser;				// BC-Database user
	LPSTR				DbPassword;			// BC-Database password
	LPSTR				DbTable;			// BC-Server database table name

	LPSTR				SslCa;
	LPSTR				SslCert;
	LPSTR				SslKey;

	LPSTR				GeoipName;			// GeoIp database name
	LPSTR				GeoipTable;			// GeoIp database table name

// SQL database current connection information
	HANDLE				DbSemaphore;
	HANDLE				DbHandle;			// Handle to BC-Server database
	HANDLE				GeoipHandle;		// Handle to GeoIp database

// Server parameters
	LONG				ClientsLimit;
	HANDLE				SessionCheckTimer;

// Io completion port and worker threads
	HANDLE				hIoCompletionPort;
	PHANDLE				pIoWorkers;		
	ULONG				IoWorkersCount;

// Pending handshake list
	LIST_ENTRY			HandshakeListHead;
	CRITICAL_SECTION	HandshakeListLock;
	ULONG				LastPendingAddress;


	LONG volatile		NumberOfSessions;
#if _DEBUG
	LONG volatile		NumberOfHandshakes;
	LONG volatile		NumberOfLinks;
#endif
} BC_SERVER, *PBC_SERVER;

#define	BC_SERVER_MAGIC			'rScB'
#define	ASSERT_BC_SERVER(x)		ASSERT(x->Header.Magic == BC_SERVER_MAGIC)

#pragma pack(push)
#pragma pack(1)
// Describes a socket and a buffer to read a data from the socket
typedef struct _BC_CONTEXT
{
	SOCKET				Socket;				//	Socket to send\receive data
	BC_OVERLAPPED		BcOverlapped;		//	Overlapped structure used in asynchronous operations
	ULONG				Size;
	CHAR				OpCode;				//	Context operation code
	CHAR				Reserved[3];		//	Reserved for alingment
	CHAR				Buffer[BC_SESSION_BUFFER_SIZE];	// Buffer to receive data
} BC_CONTEXT, *PBC_CONTEXT;
#pragma pack(pop)


#define	BC_CONTEXT_SEND			1
#define	BC_CONTEXT_RECV			2
#define	BC_CONTEXT_KEEPALIVE	3

typedef struct _ACCEPT_ADDRESS_EX
{
	CHAR				Reserved[0x10];
	SOCKADDR_IN			Addr;
} ACCEPT_ADDRESS_EX, *PACCEPT_ADDRESS_EX;

// TCP port descriptor
typedef	struct _BC_PORT
{
	LIST_ENTRY			Entry;				// Port list entry if any
	BC_OVERLAPPED		BcOverlapped;		// Overlapped strucutre used for asynchronous accept
	SOCKET				Socket;				// Socket binded to the current TCP port
	SOCKET				aSocket;			// Socket used to asynchronously accept incomming connection
	USHORT				Number;				// Current TCP port number
	ACCEPT_ADDRESS_EX	Address;
} BC_PORT, *PBC_PORT;

#define	BC_SERVER_SIDE	0
#define	BC_CLIENT_SIDE	1

// BC-Session descriptor structure
typedef struct _BC_SESSION
{
	BC_HEADER			Header;

	LIST_ENTRY			PendingLinksList;	//	List of pending links (where only one side of a link is currently ready)
	LIST_ENTRY			ActiveLinksList;	//	List of active links (where both sides of a link are ready and data is being trasmited)
	LIST_ENTRY			FreeLinksList;		//	List of free links (not used)

	CRITICAL_SECTION	SessionLock;		//	Session structure lock. Used to modify PendingLinksList and ActiveLinksList. 

	PBC_SERVER			pServer;			//	Pointer to the current server structure

	LONG volatile		ActiveLinks;		//	Number of active links
	LONG volatile		ServerLinks;		//	Number of pending server links
	LONG volatile		FreeLinks;			//	Number of free links

	LONG volatile		PendingLinksCount;	//	Number of pending links added
	LONG volatile		ActiveLinksCount;	//	Number of links became active
	LONG volatile		ActiveLinksMax;		//	Maximum number of links became active

	BC_PORT				Port;				//	Describes session client socket and port

	LARGE_INTEGER		PendingTime;		//	Time stamp when the session entered the wait for connect state
	LARGE_INTEGER		UpdateTime;			//	Session statistics update time

	BC_RECORD			BcRecord;			//	Database record data for the session

} BC_SESSION, *PBC_SESSION;

#define	BC_SESSION_MAGIC		'sScB'
#define	ASSERT_BC_SESSION(x)	ASSERT(x->Header.Magic == BC_SESSION_MAGIC)

typedef struct _BC_HANDSHAKE
{
	BC_HEADER			Header;
	ULONG				Read;				//	Number of bytes currently read from the handshake message
	LIST_ENTRY			Entry;				//  pServer->HandshakeListHead entry
	LARGE_INTEGER		PendingTime;
	PBC_SERVER			pServer;			//	Current BC_SERVER structure
	SOCKET				Socket;				//	Socket that accepts the server side connection
	BC_OVERLAPPED		BcOverlapped;
	BC_RECORD			BcRecord;			//	Database record data for the session
} BC_HANDSHAKE, *PBC_HANDSHAKE;

#define	BC_HANDSHAKE_MAGIC		'sHcB'
#define	ASSERT_BC_HANDSHAKE(x)	ASSERT(x->Header.Magic == BC_HANDSHAKE_MAGIC)


// Describes BC-Link: a pair of linked sockets
typedef struct _BC_LINK
{
	BC_HEADER			Header;

#if _DBG
	ULONG				SentToClient;
	ULONG				RecvdFromClient;
#endif
	LIST_ENTRY			Entry;				//	Entry linked to BC_SESSION.PendingLinksList or BC_SESSION.ActiveLinksList
											//		depending on Context(s) states.
	LARGE_INTEGER		TransferTime;
	PBC_SESSION			pSession;			//	Pointer to BC_SESSION strucutre the link belongs to
	BC_CONTEXT			Context[2];			//	A pair of send\receive contexts
} BC_LINK, *PBC_LINK;

#define	BC_LINK_MAGIC			'nLcB'
#define	ASSERT_BC_LINK(x)		ASSERT(x->Header.Magic == BC_LINK_MAGIC)

C_ASSERT(sizeof(BC_LINK) == 0x10000);


// TCP/IP keepalive option parameters
typedef struct _TCP_KEEPALIVE
{
	BOOL	OnOff;
    ULONG	KeepaliveTime;
    ULONG	KeepaliveInterval;
} TCP_KEEPALIVE, *PTCP_KEEPALIVE;


// Server initialization file parameters hashes
#define	CRC_PORT				0x8afb4f87
#define	CRC_CLIENTS				0xc0722f91
#define	CRC_DBSERVER			0xffc93b02
#define	CRC_DBPORT				0xac87726b
#define	CRC_DBNAME				0xb13551a1
#define	CRC_DBUSER				0x6285f9ee
#define	CRC_DBPASSWORD			0xed931e22
#define	CRC_DBTABLE				0x57186067
#define	CRC_GEOIPNAME			0x298d9447
#define	CRC_GEOIPTABLE			0x1ee99fef
#define	CRC_DBCONNECTLIMIT		0x1

#define	CRC_SSLCA				0x3cd904d1
#define	CRC_SSLCERT				0x7ccc0e79
#define	CRC_SSLKEY				0x892046aa


WINERROR BcServerStart(
	PBC_SERVER	pServer
	);

VOID BcServerStop(
	PBC_SERVER	pServer
	);