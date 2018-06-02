//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// BCSRV project. Version 2.7.17.1
//	
// module: ini.h
// $Revision: 70 $
// $Date: 2014-07-22 14:01:15 +0400 (Вт, 22 июл 2014) $
// description:
//  INI-file management routines definitions.


// Save file flags
#define	FILE_FLAG_OVERWRITE		1	// overwrite an existing file
#define	FILE_FLAG_APPEND		2	// append an existing file
#define	FILE_FLAG_WAIT_SHARE	4	// wait until a file could be shared

//	Describes INI-file parameter of type: NAME=VALUE
typedef	struct _INI_PARAMETER
{
	ULONG	NameHash;	// CRC32 hash of the parameter name
	ULONG	Flags;		// variouse flags
	PCHAR	pValue;		// pointer to the string representing the value of the parameter
} INI_PARAMETER, *PINI_PARAMETER;

typedef struct _INI_PARAMETERS
{
	ULONG			Count;			// total number of the parameters avaliable
	INI_PARAMETER	Parameter[];	// parameters
} INI_PARAMETERS, *PINI_PARAMETERS;


#ifdef __cplusplus
extern "C" {
#endif

PVOID __stdcall	AppAlloc(ULONG Size);
VOID __stdcall	AppFree(PVOID pMem);


// ---- from ini.c ----------------------------------------------------------------------------------------------------------

//
//	Allocates a buffer and loads the specified file into it.
//
WINERROR __stdcall IniLoadFile(
	LPTSTR	FileName,	// full path to the file to load
	PCHAR*	pBuffer,	// receives a pointer to the buffer containing the loaded file
	PULONG	pSize		// receives the size of the loaded file in bytes
	);


//
//	Parses the specified parameter string of type: NAME=VALUE, devided by the specified delimiter.
//	Allocates and fills INI_PARAMETERS sructure, cotaining parameter hashes and pointers to specific values.
//
WINERROR __stdcall IniParseParamString(
	PCHAR	ParamStr,				// parameter string to parse
	CHAR	Delimiter,				// delimiter for the parameters
	PINI_PARAMETERS* ppParameters,	// variable to return pointer to INI_PARAMETERS structure
	BOOL	bCaseSensitive			// specifies how to parse parameter names and values: case sensitive or not
	);

//
//	Parces the specified file containing parameter strings of type: NAME=VALUE.
//	Each parameter string starts with a new line.
//
WINERROR __stdcall IniParseParamFile(
	PCHAR	ParamStr,				// parameter string to parse
	PINI_PARAMETERS* ppParameters,	// variable to return pointer to INI_PARAMETERS structure
	BOOL	bNameCaseSensitive,		// specifies how to parse parameter names: case sensitive or not
	BOOL	bValueCaseSensitive		// specifies how to parse parameter values: case sensitive or not
	);

//
//	Scans the specified INI_PARAMETERS structure for a parameter with the specified Name hash.
//	Returns pointer to the value of the parameter or NULL if the parameter not found.
//
PCHAR __stdcall IniGetParamValue(
	ULONG NameHash,					// CRC32 hash of the name to find a value for
	PINI_PARAMETERS	pParameters		// target parameters
	);

//
//	Allocates a memory buffer of the specified MinimumLength and duplicates the specified source string into it.
//	If MinimumLength is larger then a length of the specified source string then unused buffer is filled with zeoroes. 
//
LPTSTR __stdcall IniDupStr(
	LPTSTR	SourceStr,		// a string to duplicate
	ULONG	MinimumLength	// minimum size of the string buffer in chars
	);


//
//	Converts the specified address string of an IP:PORT format into two integers specifying IP-address and TCP-port values.
//
BOOL IniStringToTcpAddress(
	LPTSTR			pIpStr,		// address string of an IP:PORT format
	SOCKADDR_IN*	pAddress	// pointer to the structure that receives TCP/IP address
	);


WINERROR IniGetModulePath(
	HMODULE		hModule,		// a handle of the module within current process
	LPTSTR*		pModulePath		// returned module path
);


//
//	Caclulates CRC32 hash of the data within the specified buffer
//
ULONG Crc32(
	PCHAR pMem,		// data buffer
	ULONG uLen		// length of the buffer in bytes
	);
 
#ifdef __cplusplus
}
#endif