//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: file.c
// $Revision: 190 $
// $Date: 2014-07-11 16:51:41 +0400 (Пт, 11 июл 2014) $
// description: 
//	file utilities

#include "project.h"
#include <Winver.h>
#include <malloc.h>
#include "str.h"

//////////////////////////////////////////////////////////////////////////
// gets string from file
DWORD FileReadStringA(HANDLE hFile, LPSTR Buffer, DWORD Length )
{
	CHAR ch;
	BOOL bWasChar = FALSE;
	DWORD BytesRead = 0;
	CHAR *pointer = Buffer;
	if ( Buffer == NULL || Length == 0 ){
		return 0;
	}

	while ( --Length )
	{
		if ( !ReadFile(hFile,&ch,1,&BytesRead, NULL ) || BytesRead != 1 )
		{
			break;
		}
		if ( ch == '\n'){
			if ( bWasChar ){
				break;
			}
		}else if ( ch == '\r'){
			if ( bWasChar ){
				break;
			}
		}else{
			bWasChar = TRUE;
			*pointer++ = ch;
		}
	}
	*pointer = '\0';
	return (DWORD)(pointer-Buffer);
}

DWORD FileReadStringW(HANDLE hFile, LPWSTR Buffer, DWORD Length )
{
	CHAR ch;
	BOOL bWasChar = FALSE;
	DWORD BytesRead = 0;
	WCHAR *pointer = Buffer;
	if ( Buffer == NULL || Length == 0 ){
		return 0;
	}

	while ( --Length )
	{
		if ( !ReadFile(hFile,&ch,1,&BytesRead, NULL ) || BytesRead != 1 )
		{
			break;
		}
		if ( ch == '\n'){
			if ( bWasChar ){
				break;
			}
		}else if ( ch == '\r'){
			if ( bWasChar ){
				break;
			}
		}else{
			bWasChar = TRUE;
			*pointer++ = ch;
		}
	}
	*pointer = L'\0';
	return (DWORD)(pointer-Buffer);
}

//////////////////////////////////////////////////////////////////////////
// fixes path slashes
void FileFixSlashA( LPSTR szPath )
{
	while ( *szPath )
	{
		if ( *szPath == '/' ){
			*szPath = '\\';
		}
		szPath++;
	}
}

void FileFixSlashW( LPWSTR szPath )
{
	while ( *szPath )
	{
		if ( *szPath == L'/' ){
			*szPath = L'\\';
		}
		szPath++;
	}
}
//////////////////////////////////////////////////////////////////////////
// queries file version info from resources

typedef struct _VS_VERSIONINFO { 
	WORD  wLength; 
	WORD  wValueLength; 
	WORD  wType; 
	WCHAR szKey[sizeof("VS_VERSION_INFO")]; 
	WORD  Padding1[1]; 
	VS_FIXEDFILEINFO Value; 
	//WORD  Padding2[]; 
	//WORD  Children[]; 
}VS_VERSIONINFO,*PVS_VERSIONINFO;

typedef DWORD
(APIENTRY *FUNC_GetFileVersionInfoSizeA)(
        LPCSTR lptstrFilename, /* Filename of version stamped file */
        LPDWORD lpdwHandle       /* Information for use by GetFileVersionInfo */
        );  
typedef DWORD
(APIENTRY *FUNC_GetFileVersionInfoSizeW)(
        LPCWSTR lptstrFilename, /* Filename of version stamped file */
        LPDWORD lpdwHandle       /* Information for use by GetFileVersionInfo */
        );  

typedef BOOL
(APIENTRY *FUNC_GetFileVersionInfoA)(
        LPCSTR lptstrFilename, /* Filename of version stamped file */
        DWORD dwHandle,          /* Information from GetFileVersionSize */
        DWORD dwLen,             /* Length of buffer for info */
        LPVOID lpData            /* Buffer to place the data structure */
        ); 
typedef BOOL
(APIENTRY *FUNC_GetFileVersionInfoW)(
        LPCWSTR lptstrFilename, /* Filename of version stamped file */
        DWORD dwHandle,          /* Information from GetFileVersionSize */
        DWORD dwLen,             /* Length of buffer for info */
        LPVOID lpData            /* Buffer to place the data structure */
        ); 

static HANDLE hVersionDLL = NULL;
static FUNC_GetFileVersionInfoSizeW GetFileVersionInfoSizeWPtr = NULL;
static FUNC_GetFileVersionInfoW GetFileVersionInfoWPtr = NULL;

DWORD 
	FileGetVersionW( 
		IN LPWSTR FileName, 
		OUT LPWORD Major, 
		OUT LPWORD Minor,
		OUT LPWORD Build, 
		OUT LPWORD QFE
		)
{
	HANDLE hModule = NULL;
	DWORD Error = NO_ERROR;
	BOOL fbResult;
	DWORD hHandle = 0;

	PVS_VERSIONINFO FileVersion = NULL;
	DWORD InfoSize;

	do{
		if ( hVersionDLL == NULL ){
			HANDLE hModule = LoadLibrary(TEXT("version.dll"));
			if ( hModule == NULL ){
				Error = GetLastError();
				break;
			}

			GetFileVersionInfoSizeWPtr = 
				(FUNC_GetFileVersionInfoSizeW)
					GetProcAddress(hModule,"GetFileVersionInfoSizeW");
			if ( GetFileVersionInfoSizeWPtr == NULL ){
				Error = GetLastError();
				break;
			}
			GetFileVersionInfoWPtr = 
				(FUNC_GetFileVersionInfoW)
					GetProcAddress(hModule,"GetFileVersionInfoW");
			if ( GetFileVersionInfoWPtr == NULL ){
				Error = GetLastError();
				break;
			}
			hVersionDLL = hModule;
			hModule = NULL;
		}

		InfoSize = GetFileVersionInfoSizeWPtr(FileName,&hHandle);
		if ( InfoSize == 0 ){
			Error = GetLastError();
			break;
		}

		FileVersion = (PVS_VERSIONINFO)AppAlloc( InfoSize );
		if ( FileVersion == NULL ){
			Error = ERROR_NOT_ENOUGH_MEMORY;
			break;
		}
		fbResult =
			GetFileVersionInfoWPtr(
				FileName,
				0,InfoSize,
				FileVersion
				);

		if ( fbResult == FALSE ){
			break;
		}

		//construct file version
		if ( Major ){
			*Major = HIWORD(FileVersion->Value.dwFileVersionMS);
		}
		if ( Minor ){
			*Minor = LOWORD(FileVersion->Value.dwFileVersionMS);
		}
		if ( Build ){
			*Build = HIWORD(FileVersion->Value.dwFileVersionLS);
		}
		if ( QFE ){
			*QFE   = LOWORD(FileVersion->Value.dwFileVersionLS);
		}

	}while ( FALSE );

	if ( FileVersion ){
		AppFree ( FileVersion );
	}
	if ( hModule ){
		FreeLibrary(hModule);
	}
	return Error;
}

DWORD 
	FileGetVersionA( 
		IN LPSTR FileName, 
		OUT LPWORD Major, 
		OUT LPWORD Minor,
		OUT LPWORD Build, 
		OUT LPWORD QFE
		)
{
	USES_CONVERSION;
	return FileGetVersionW(A2W(FileName),Major,Minor,Build,QFE);
}
